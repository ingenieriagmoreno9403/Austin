<?php

namespace App\Services;

use App\Models\LicParametro;
use App\Models\LicParamPlantilla;
use App\Models\LicParamScore;
use App\Models\LicParamTipo;
use App\Models\Licitacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LicitacionParametroService
{
    public function clonarPlantillaALicitacion(Licitacion $lic, ?int $tipoId): void
    {
        if (! $tipoId) {
            return;
        }

        $tipo = LicParamTipo::query()->with('plantillaActiva')->find($tipoId);
        if (! $tipo) {
            return;
        }

        LicParametro::query()->where('licitacion_id', $lic->id)->delete();
        LicParamScore::query()->where('licitacion_id', $lic->id)->delete();

        $orden = 1;
        foreach ($tipo->plantillaActiva as $p) {
            LicParametro::query()->create([
                'licitacion_id' => $lic->id,
                'plantilla_id' => $p->id,
                'nombre' => $p->nombre,
                'peso_pct' => $p->peso_pct,
                'sentido' => $p->sentido,
                'tipo_dato' => $p->tipo_dato,
                'obligatorio' => $p->obligatorio,
                'orden' => $p->orden ?: $orden,
            ]);
            $orden++;
        }

        $lic->tipo_param_id = $tipoId;
        $lic->save();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function syncParametrosLicitacion(Licitacion $lic, array $items): void
    {
        $this->assertSumaCien(collect($items)->pluck('peso_pct'));

        $keepIds = [];
        $orden = 1;
        foreach ($items as $item) {
            $nombre = trim((string) ($item['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }
            $payload = [
                'licitacion_id' => $lic->id,
                'nombre' => $nombre,
                'peso_pct' => (float) ($item['peso_pct'] ?? 0),
                'sentido' => $item['sentido'] ?? 'menor_mejor',
                'tipo_dato' => $item['tipo_dato'] ?? 'numero',
                'obligatorio' => ! empty($item['obligatorio']),
                'orden' => (int) ($item['orden'] ?? $orden),
            ];
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0) {
                $row = LicParametro::query()->where('licitacion_id', $lic->id)->where('id', $id)->first();
                if ($row) {
                    $row->update($payload);
                    $keepIds[] = $row->id;
                    $orden++;
                    continue;
                }
            }
            $payload['plantilla_id'] = $item['plantilla_id'] ?? null;
            $created = LicParametro::query()->create($payload);
            $keepIds[] = $created->id;
            $orden++;
        }

        LicParametro::query()
            ->where('licitacion_id', $lic->id)
            ->whereNotIn('id', $keepIds ?: [0])
            ->delete();

        LicParamScore::query()
            ->where('licitacion_id', $lic->id)
            ->whereNotIn('parametro_id', $keepIds ?: [0])
            ->delete();
    }

    public function assertSumaCien(Collection $pesos): void
    {
        $suma = round((float) $pesos->sum(fn ($v) => (float) $v), 2);
        if (abs($suma - 100.0) > 0.05) {
            throw ValidationException::withMessages([
                'peso_pct' => "La suma de ponderaciones debe ser 100%. Actual: {$suma}%",
            ]);
        }
    }

    public function assertPlantillaTipoCien(int $tipoId): void
    {
        $suma = round((float) LicParamPlantilla::query()
            ->where('tipo_id', $tipoId)
            ->where('activo', true)
            ->sum('peso_pct'), 2);
        if ($suma > 0 && abs($suma - 100.0) > 0.05) {
            throw ValidationException::withMessages([
                'peso_pct' => "La suma de ponderaciones del tipo debe ser 100%. Actual: {$suma}%",
            ]);
        }
    }

    /**
     * Guarda valores crudos y recalcula score_normalizado + totales.
     *
     * @param  array<int, array{parametro_id:int, proveedor_id:int, valor:?float, comentario?:?string}>  $rows
     * @return array{ranking: array<int, float>, scores: Collection}
     */
    public function guardarYNormalizarScores(Licitacion $lic, array $rows): array
    {
        $parametros = LicParametro::query()
            ->where('licitacion_id', $lic->id)
            ->orderBy('orden')
            ->get()
            ->keyBy('id');

        foreach ($rows as $r) {
            $paramId = (int) ($r['parametro_id'] ?? 0);
            $provId = (int) ($r['proveedor_id'] ?? 0);
            if (! $paramId || ! $provId || ! $parametros->has($paramId)) {
                continue;
            }
            $valor = $r['valor'];
            $valor = ($valor === '' || $valor === null) ? null : (float) $valor;

            LicParamScore::query()->updateOrCreate(
                [
                    'parametro_id' => $paramId,
                    'proveedor_id' => $provId,
                ],
                [
                    'licitacion_id' => $lic->id,
                    'valor' => $valor,
                    'comentario' => $r['comentario'] ?? null,
                    'score_normalizado' => null,
                ]
            );
        }

        return $this->recalcularNormalizacion($lic);
    }

    /**
     * @return array{ranking: array<int, float>, scores: Collection}
     */
    public function recalcularNormalizacion(Licitacion $lic): array
    {
        $parametros = LicParametro::query()
            ->where('licitacion_id', $lic->id)
            ->orderBy('orden')
            ->get();

        $scores = LicParamScore::query()
            ->where('licitacion_id', $lic->id)
            ->get();

        foreach ($parametros as $param) {
            $grupo = $scores->where('parametro_id', $param->id)
                ->filter(fn ($s) => $s->valor !== null);
            if ($grupo->isEmpty()) {
                continue;
            }

            $valores = $grupo->pluck('valor');
            $min = (float) $valores->min();
            $max = (float) $valores->max();

            foreach ($grupo as $s) {
                $v = (float) $s->valor;
                if ($param->sentido === 'menor_mejor') {
                    // mejor = min → 100; peor = max → ~0
                    if ($max == $min) {
                        $norm = 100.0;
                    } else {
                        $norm = 100.0 * (($max - $v) / ($max - $min));
                    }
                } else {
                    // mayor_mejor
                    if ($max == $min) {
                        $norm = 100.0;
                    } else {
                        $norm = 100.0 * (($v - $min) / ($max - $min));
                    }
                }
                $s->score_normalizado = round($norm, 2);
                $s->save();
            }
        }

        $scores = LicParamScore::query()
            ->where('licitacion_id', $lic->id)
            ->get();

        $ranking = [];
        $provIds = $scores->pluck('proveedor_id')->unique();
        foreach ($provIds as $provId) {
            $total = 0.0;
            foreach ($parametros as $param) {
                $s = $scores->first(fn ($x) => (int) $x->parametro_id === (int) $param->id && (int) $x->proveedor_id === (int) $provId);
                if ($s && $s->score_normalizado !== null) {
                    $total += ((float) $s->score_normalizado) * (((float) $param->peso_pct) / 100.0);
                }
            }
            $ranking[(int) $provId] = round($total, 2);
        }
        arsort($ranking);

        return ['ranking' => $ranking, 'scores' => $scores];
    }

    public function rankingActual(Licitacion $lic): array
    {
        return $this->recalcularNormalizacion($lic)['ranking'];
    }

    /**
     * Parámetro de precio (moneda / nombre) de la licitación, si existe.
     */
    public function parametroPrecio(Licitacion $lic): ?LicParametro
    {
        return LicParametro::query()
            ->where('licitacion_id', $lic->id)
            ->where(function ($q) {
                $q->where('tipo_dato', 'moneda')
                    ->orWhereRaw('LOWER(nombre) LIKE ?', ['%precio%']);
            })
            ->orderBy('orden')
            ->first();
    }

    /**
     * Totales de cotización por proveedor (costo × cantidad de partidas).
     *
     * @return array<int, float>
     */
    public function totalesCotizacionPorProveedor(int $licitacionId): array
    {
        $rows = DB::table('tbllicitacionproducto_proveedor as lpp')
            ->join('tbllicitacion_det as ld', function ($j) use ($licitacionId) {
                $j->on('ld.producto_id', '=', 'lpp.producto_id')
                    ->where('ld.licitacion_id', '=', $licitacionId);
            })
            ->where('lpp.licitacion_id', $licitacionId)
            ->where('lpp.costo', '>', 0)
            ->groupBy('lpp.proveedor_id')
            ->select('lpp.proveedor_id', DB::raw('SUM(lpp.costo * ld.cantidad) as total'))
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->proveedor_id] = round((float) $r->total, 2);
        }

        return $out;
    }

    /**
     * Si hay cotizaciones, vuelca el total al parámetro Precio (menor mejor).
     * Así la tabla de precios alimenta el ranking ponderado.
     */
    public function sincronizarPrecioDesdeCotizaciones(Licitacion $lic): bool
    {
        $param = $this->parametroPrecio($lic);
        if (! $param) {
            return false;
        }

        $totales = $this->totalesCotizacionPorProveedor((int) $lic->id);
        if ($totales === []) {
            return false;
        }

        $provIds = DB::table('tbllicitacion_proveedor')
            ->where('licitacion_id', $lic->id)
            ->pluck('proveedor_id');

        foreach ($provIds as $provId) {
            $provId = (int) $provId;
            $valor = array_key_exists($provId, $totales) ? $totales[$provId] : null;
            LicParamScore::query()->updateOrCreate(
                [
                    'parametro_id' => $param->id,
                    'proveedor_id' => $provId,
                ],
                [
                    'licitacion_id' => $lic->id,
                    'valor' => $valor,
                    'score_normalizado' => null,
                ]
            );
        }

        return true;
    }
}
