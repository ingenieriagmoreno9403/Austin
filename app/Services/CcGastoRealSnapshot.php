<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Copia local del gasto real de centros de costo.
 * El análisis lee los meses ya sumados. La API de gasto real llena cada movimiento y se reescribe solo si cambió.
 */
class CcGastoRealSnapshot
{
    public function disponible(): bool
    {
        return Schema::hasTable('tbl_cc_gasto_real_snap')
            && Schema::hasTable('tbl_cc_gasto_real_cc')
            && Schema::hasTable('tbl_cc_gasto_real_carga');
    }

    public function cubreEmpresa(string $empresa, int $year): bool
    {
        if (! $this->disponible()) {
            return false;
        }

        return DB::table('tbl_cc_gasto_real_carga')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->where('completa', true)
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    public function centrosPresentes(string $empresa, int $year): array
    {
        if (! $this->disponible()) {
            return [];
        }

        return DB::table('tbl_cc_gasto_real_cc')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->pluck('centro_codigo')
            ->map(function ($cc) {
                return (string) $cc;
            })
            ->all();
    }

    /**
     * @return array<int, array{empresa: string, anio: int, filas: int, origen: string, synced_at: string|null}>
     */
    public function resumen(int $year): array
    {
        if (! Schema::hasTable('tbl_cc_gasto_real_carga')) {
            return [];
        }

        return DB::table('tbl_cc_gasto_real_carga')
            ->where('anio', $year)
            ->orderBy('empresa')
            ->get(['empresa', 'anio', 'filas', 'origen', 'synced_at', 'completa'])
            ->map(function ($row) {
                return [
                    'empresa' => (string) $row->empresa,
                    'anio' => (int) $row->anio,
                    'filas' => (int) $row->filas,
                    'origen' => (string) $row->origen,
                    'completa' => (bool) $row->completa,
                    'synced_at' => $row->synced_at ? (string) $row->synced_at : null,
                ];
            })
            ->all();
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function mapa(string $empresa, int $year): array
    {
        if (! $this->disponible()) {
            return [];
        }

        $porCc = [];
        $rows = DB::table('tbl_cc_gasto_real_snap')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->get();

        foreach ($rows as $row) {
            $cc = strtoupper(trim((string) $row->centro_codigo));
            $codigo = trim((string) $row->cuenta_codigo);
            if ($cc === '' || $codigo === '') {
                continue;
            }
            $gasto = [];
            $usd = [];
            for ($i = 1; $i <= 12; $i++) {
                $suf = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $gasto[] = round((float) ($row->{'mes_'.$suf} ?? 0), 2);
                $usd[] = round((float) ($row->{'usd_'.$suf} ?? 0), 2);
            }
            $item = [
                'codigo' => $codigo,
                'nombre' => (string) ($row->cuenta_nombre ?? ''),
                'depto' => (string) ($row->depto ?? ''),
                'gasto' => $gasto,
                'gasto_usd' => $usd,
            ];
            $porCc[$cc][$codigo] = $item;
            $digits = $this->digitos($codigo);
            if ($digits !== '' && $digits !== $codigo) {
                $porCc[$cc][$digits] = $item;
            }
            $altCuenta = ltrim($digits !== '' ? $digits : $codigo, '0');
            if ($altCuenta !== '' && ! isset($porCc[$cc][$altCuenta])) {
                $porCc[$cc][$altCuenta] = $item;
            }
        }

        $extra = [];
        foreach ($porCc as $cc => $por) {
            $alt = ltrim($cc, '0');
            if ($alt !== '' && $alt !== $cc && ! isset($porCc[$alt])) {
                $extra[$alt] = $por;
            }
        }

        return $porCc + $extra;
    }

    /**
     * Guarda el mapa ya agregado (respuesta de SAP).
     *
     * @param  array<string, array<string, array<string, mixed>>>  $mapa
     */
    public function guardarMapa(string $empresa, int $year, array $mapa, string $origen, ?int $userId, bool $completa, bool $conservarUltimosMeses = false): void
    {
        if (! $this->disponible() || $mapa === []) {
            if ($completa && $this->disponible()) {
                $this->marcarCarga($empresa, $year, $origen, 0, $userId);
            }

            return;
        }

        $centros = $this->centrosCanonicos($mapa);
        $ahora = now();
        $snapRows = [];
        $ccRows = [];
        $previos = $conservarUltimosMeses ? $this->ultimosMesesGuardados($empresa, $year, array_keys($centros)) : [];
        foreach ($centros as $cc => $por) {
            $cuentas = $this->cuentasCanonicas($por);
            $ccRows[] = [
                'empresa' => $empresa,
                'anio' => $year,
                'centro_codigo' => $cc,
                'cuentas' => count($cuentas),
                'origen' => $origen,
                'synced_at' => $ahora,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
            foreach ($cuentas as $codigo => $item) {
                $visible = trim((string) ($item['codigo'] ?? $codigo));
                $fila = $this->filaSnap($empresa, $year, $cc, $visible !== '' ? $visible : (string) $codigo, $item, $origen, $userId, $ahora);
                $clave = $cc.'|'.$this->digitos($visible !== '' ? $visible : (string) $codigo);
                if (isset($previos[$clave])) {
                    foreach (['10', '11', '12'] as $suf) {
                        $fila['mes_'.$suf] = $previos[$clave]['mes_'.$suf];
                        $fila['usd_'.$suf] = $previos[$clave]['usd_'.$suf];
                    }
                }
                $snapRows[] = $fila;
            }
        }

        DB::transaction(function () use ($empresa, $year, $completa, $centros, $snapRows, $ccRows, $origen, $userId, $ahora) {
            if ($completa) {
                DB::table('tbl_cc_gasto_real_snap')->where('empresa', $empresa)->where('anio', $year)->delete();
                DB::table('tbl_cc_gasto_real_cc')->where('empresa', $empresa)->where('anio', $year)->delete();
            } else {
                $codigos = array_keys($centros);
                if ($codigos) {
                    DB::table('tbl_cc_gasto_real_snap')->where('empresa', $empresa)->where('anio', $year)->whereIn('centro_codigo', $codigos)->delete();
                    DB::table('tbl_cc_gasto_real_cc')->where('empresa', $empresa)->where('anio', $year)->whereIn('centro_codigo', $codigos)->delete();
                }
            }
            foreach (array_chunk($snapRows, 400) as $chunk) {
                DB::table('tbl_cc_gasto_real_snap')->insert($chunk);
            }
            foreach (array_chunk($ccRows, 400) as $chunk) {
                DB::table('tbl_cc_gasto_real_cc')->insert($chunk);
            }
            if ($completa) {
                $this->marcarCarga($empresa, $year, $origen, count($snapRows), $userId, $ahora);
            }
        });

        Cache::forget('cc.gasto-emp.v4.'.$empresa.'.'.$year);
    }

    /**
     * Guarda la bajada de la API solo cuando la firma del gasto cambió.
     *
     * @param  array<string, array<string, array<string, mixed>>>  $mapa
     * @param  array<int, array<string, mixed>>  $filas
     */
    public function guardarSiCambio(string $empresa, int $year, array $mapa, array $filas, bool $completa, string $origen, ?int $userId): bool
    {
        if (! $this->disponible() || ($mapa === [] && $filas === [])) {
            return false;
        }

        $firma = $this->firmaDe($mapa, count($filas));
        $guardada = $this->firmaGuardada($empresa, $year);
        $tieneSap = $this->tieneFilasSap($empresa, $year);
        if ($guardada !== null && hash_equals($guardada, $firma) && ($filas === [] || $tieneSap)) {
            $this->tocarCarga($empresa, $year);

            return false;
        }

        if ($filas !== [] && Schema::hasTable('tbl_cc_gasto_real_sap')) {
            $this->reemplazarFilasSap($empresa, $year, $filas, $userId, $completa);
        }
        if ($mapa !== []) {
            $this->guardarMapa($empresa, $year, $mapa, $origen, $userId, $completa);
        }
        $sigueCompleta = $completa || $this->cubreEmpresa($empresa, $year);
        $this->marcarCarga($empresa, $year, $origen, count($filas), $userId, now(), $sigueCompleta, $firma);
        Cache::forget('cc.gasto-emp.v4.'.$empresa.'.'.$year);

        return true;
    }

    /**
     * Sustituye una sola cuenta del centro. El resto de la copia local se queda.
     *
     * @param  array<string, mixed>  $item
     * @param  array<int, array<string, mixed>>  $filas
     */
    public function reemplazarCuenta(string $empresa, int $year, string $cc, string $cuenta, array $item, array $filas, ?int $userId): void
    {
        if (! $this->disponible()) {
            return;
        }

        $empresa = strtoupper(trim($empresa));
        $cc = strtoupper(trim($cc));
        $digits = $this->digitos($cuenta);
        $needle = ltrim($digits !== '' ? $digits : trim($cuenta), '0');
        if ($empresa === '' || $cc === '' || $needle === '') {
            return;
        }

        $ahora = now();
        $codigos = [trim($cuenta)];
        $borrar = [];
        $existentes = DB::table('tbl_cc_gasto_real_snap')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->where('centro_codigo', $cc)
            ->get();
        $conservar = null;
        foreach ($existentes as $row) {
            $d = $this->digitos((string) $row->cuenta_codigo);
            $alt = ltrim($d !== '' ? $d : (string) $row->cuenta_codigo, '0');
            if ($alt !== '' && $alt === $needle) {
                $borrar[] = $row->id;
                $codigos[] = (string) $row->cuenta_codigo;
                if ($conservar === null) {
                    $conservar = $row;
                }
            }
        }
        $codigos = array_values(array_unique(array_filter($codigos)));
        foreach (['gasto', 'gasto_usd'] as $campo) {
            if (! isset($item[$campo]) || ! is_array($item[$campo])) {
                continue;
            }
            $serie = array_values($item[$campo]);
            for ($i = 9; $i < 12; $i++) {
                $serie[$i] = 0.0;
            }
            $item[$campo] = $serie;
        }
        $visible = trim((string) ($item['codigo'] ?? $cuenta));
        $snapRow = $this->filaSnap($empresa, $year, $cc, $visible !== '' ? $visible : $cuenta, $item, 'api', $userId, $ahora);
        if ($conservar) {
            foreach (['10', '11', '12'] as $suf) {
                $snapRow['mes_'.$suf] = round((float) ($conservar->{'mes_'.$suf} ?? 0), 2);
                $snapRow['usd_'.$suf] = round((float) ($conservar->{'usd_'.$suf} ?? 0), 2);
            }
        }

        $sap = [];
        foreach ($filas as $fila) {
            $mes = (int) ($fila['mes'] ?? 0);
            $cta = trim((string) ($fila['cuenta'] ?? ''));
            if ($mes < 1 || $mes > 9 || $cta === '') {
                continue;
            }
            $fecha = $fila['fecha'] ?? null;
            $sap[] = [
                'empresa' => $empresa,
                'anio' => $year,
                'centro_codigo' => $cc,
                'cuenta_codigo' => $this->corte($cta, 40),
                'cuenta_nombre' => $this->corte((string) ($fila['cuenta_nombre'] ?? ''), 180),
                'depto' => $this->corte((string) ($fila['depto'] ?? ''), 80),
                'group_mask' => $this->corte((string) ($fila['group_mask'] ?? ''), 20),
                'fecha' => $fecha !== '' ? $fecha : null,
                'mes' => $mes,
                'importe' => round((float) ($fila['importe'] ?? 0), 2),
                'importe_usd' => round((float) ($fila['importe_usd'] ?? 0), 2),
                'fila' => json_encode($fila['fila'] ?? [], JSON_UNESCAPED_UNICODE) ?: '{}',
                'origen' => 'api',
                'synced_by' => $userId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        DB::transaction(function () use ($empresa, $year, $cc, $borrar, $codigos, $snapRow, $sap, $ahora) {
            if ($borrar) {
                DB::table('tbl_cc_gasto_real_snap')->whereIn('id', $borrar)->delete();
            }
            DB::table('tbl_cc_gasto_real_snap')->insert($snapRow);
            if (Schema::hasTable('tbl_cc_gasto_real_sap') && $codigos) {
                DB::table('tbl_cc_gasto_real_sap')
                    ->where('empresa', $empresa)
                    ->where('anio', $year)
                    ->where('centro_codigo', $cc)
                    ->whereIn('cuenta_codigo', $codigos)
                    ->where('mes', '<=', 9)
                    ->delete();
                foreach (array_chunk($sap, 300) as $chunk) {
                    DB::table('tbl_cc_gasto_real_sap')->insert($chunk);
                }
            }
            $cuentas = (int) DB::table('tbl_cc_gasto_real_snap')
                ->where('empresa', $empresa)
                ->where('anio', $year)
                ->where('centro_codigo', $cc)
                ->count();
            $ccQuery = DB::table('tbl_cc_gasto_real_cc')
                ->where('empresa', $empresa)
                ->where('anio', $year)
                ->where('centro_codigo', $cc);
            if ($ccQuery->exists()) {
                $ccQuery->update([
                    'cuentas' => $cuentas,
                    'origen' => 'api',
                    'synced_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            } else {
                DB::table('tbl_cc_gasto_real_cc')->insert([
                    'empresa' => $empresa,
                    'anio' => $year,
                    'centro_codigo' => $cc,
                    'cuentas' => $cuentas,
                    'origen' => 'api',
                    'synced_at' => $ahora,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        });

        $filasN = Schema::hasTable('tbl_cc_gasto_real_sap')
            ? (int) DB::table('tbl_cc_gasto_real_sap')->where('empresa', $empresa)->where('anio', $year)->count()
            : count($filas);
        $completa = (bool) DB::table('tbl_cc_gasto_real_carga')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->value('completa');
        $this->marcarCarga($empresa, $year, 'api', $filasN, $userId, $ahora, $completa, $this->firmaDe($this->mapa($empresa, $year), $filasN));
        Cache::forget('cc.gasto-emp.v4.'.$empresa.'.'.$year);
    }

    /**
     * Incorpora un centro a la copia local sin borrar el resto de la empresa.
     *
     * @param  array<string, array<string, array<string, mixed>>>  $mapa
     * @param  array<int, array<string, mixed>>  $filas
     */
    public function fusionarCentro(string $empresa, int $year, array $mapa, array $filas, ?int $userId): void
    {
        if (! $this->disponible() || ($mapa === [] && $filas === [])) {
            return;
        }

        $empresa = strtoupper(trim($empresa));
        $mapa = $this->anularMesesPosteriores($mapa);
        if ($mapa !== []) {
            $this->guardarMapa($empresa, $year, $mapa, 'api', $userId, false, true);
        }
        $filas = $this->filasHastaMes($filas, 9);
        if ($filas !== [] && Schema::hasTable('tbl_cc_gasto_real_sap')) {
            $this->reemplazarFilasSap($empresa, $year, $filas, $userId, false, 9);
        }

        $filasN = Schema::hasTable('tbl_cc_gasto_real_sap')
            ? (int) DB::table('tbl_cc_gasto_real_sap')->where('empresa', $empresa)->where('anio', $year)->count()
            : count($filas);
        $completa = (bool) DB::table('tbl_cc_gasto_real_carga')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->value('completa');
        $this->marcarCarga($empresa, $year, 'api', $filasN, $userId, now(), $completa, $this->firmaDe($this->mapa($empresa, $year), $filasN));
        Cache::forget('cc.gasto-emp.v4.'.$empresa.'.'.$year);
    }

    /**
     * El centro ya guardado trae las mismas cuentas y los mismos meses.
     *
     * @param  array<string, array<string, mixed>>  $por
     * @param  array<int, array<string, mixed>>  $filas
     */
    public function centroIgual(string $empresa, int $year, string $cc, array $por, array $filas): bool
    {
        $empresa = strtoupper(trim($empresa));
        $cc = strtoupper(trim($cc));
        $existe = DB::table('tbl_cc_gasto_real_cc')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->where('centro_codigo', $cc)
            ->exists();
        if (! $existe) {
            return false;
        }

        $guardadas = $this->firmaSerieCentro($this->cuentasGuardadas($empresa, $year, $cc), 9);
        $nuevas = $this->firmaSerieCentro($this->cuentasCanonicas($por), 9);

        return hash_equals($guardadas, $nuevas);
    }

    public function firmaGuardada(string $empresa, int $year): ?string
    {
        if (! Schema::hasTable('tbl_cc_gasto_real_carga') || ! Schema::hasColumn('tbl_cc_gasto_real_carga', 'firma')) {
            return null;
        }
        $firma = DB::table('tbl_cc_gasto_real_carga')->where('empresa', $empresa)->where('anio', $year)->value('firma');

        return $firma !== null && $firma !== '' ? (string) $firma : null;
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $mapa
     */
    public function firmaDe(array $mapa, int $filas): string
    {
        $plain = [];
        foreach ($this->centrosCanonicos($mapa) as $cc => $por) {
            $cuentas = $this->cuentasCanonicas($por);
            ksort($cuentas);
            foreach ($cuentas as $ck => $item) {
                $gasto = $this->serieFirma($item['gasto'] ?? []);
                $usd = $this->serieFirma($item['gasto_usd'] ?? []);
                $plain[] = $cc.'|'.$ck.'|'.$gasto.'|'.$usd;
            }
        }
        sort($plain);

        return hash('sha256', implode("\n", $plain).'|'.$filas);
    }

    /**
     * @param  array<string, array<string, mixed>>  $cuentas
     */
    protected function firmaSerieCentro(array $cuentas, int $meses = 12): string
    {
        ksort($cuentas);
        $plain = [];
        foreach ($cuentas as $ck => $item) {
            $plain[] = $ck.'|'.$this->serieFirma($item['gasto'] ?? [], $meses).'|'.$this->serieFirma($item['gasto_usd'] ?? [], $meses);
        }

        return hash('sha256', implode("\n", $plain));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function cuentasGuardadas(string $empresa, int $year, string $cc): array
    {
        $por = [];
        $rows = DB::table('tbl_cc_gasto_real_snap')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->where('centro_codigo', $cc)
            ->get();
        foreach ($rows as $row) {
            $gasto = [];
            $usd = [];
            for ($i = 1; $i <= 12; $i++) {
                $suf = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                $gasto[] = round((float) ($row->{'mes_'.$suf} ?? 0), 2);
                $usd[] = round((float) ($row->{'usd_'.$suf} ?? 0), 2);
            }
            $codigo = trim((string) $row->cuenta_codigo);
            $por[$codigo] = [
                'codigo' => $codigo,
                'gasto' => $gasto,
                'gasto_usd' => $usd,
            ];
        }

        return $this->cuentasCanonicas($por);
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     */
    protected function firmaMovimientos(array $filas): string
    {
        $plain = [];
        foreach ($filas as $fila) {
            if (! is_array($fila)) {
                continue;
            }
            $fecha = (string) ($fila['fecha'] ?? '');
            if (strlen($fecha) >= 10) {
                $fecha = substr($fecha, 0, 10);
            }
            $mes = (int) ($fila['mes'] ?? 0);
            if ($mes < 1 || $mes > 9) {
                continue;
            }
            $plain[] = implode('|', [
                $this->digitos((string) ($fila['cuenta'] ?? '')),
                (int) ($fila['mes'] ?? 0),
                $fecha,
                number_format(round((float) ($fila['importe'] ?? 0), 2), 2, '.', ''),
                number_format(round((float) ($fila['importe_usd'] ?? 0), 2), 2, '.', ''),
            ]);
        }
        sort($plain);

        return hash('sha256', implode("\n", $plain));
    }

    protected function firmaMovimientosGuardados(string $empresa, int $year, string $cc): string
    {
        if (! Schema::hasTable('tbl_cc_gasto_real_sap')) {
            return $this->firmaMovimientos([]);
        }
        $filas = [];
        $rows = DB::table('tbl_cc_gasto_real_sap')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->where('centro_codigo', $cc)
            ->where('mes', '<=', 9)
            ->get(['cuenta_codigo', 'mes', 'fecha', 'importe', 'importe_usd']);
        foreach ($rows as $row) {
            $filas[] = [
                'cuenta' => (string) $row->cuenta_codigo,
                'mes' => (int) $row->mes,
                'fecha' => $row->fecha ? (string) $row->fecha : '',
                'importe' => (float) $row->importe,
                'importe_usd' => (float) $row->importe_usd,
            ];
        }

        return $this->firmaMovimientos($filas);
    }

    /**
     * Reemplaza el año de cada empresa presente en el archivo y conserva cada columna de SAP.
     *
     * @param  array<int, array<string, mixed>>  $filas
     * @return array{filas: int, cuentas: int, empresas: array<int, string>, omitidas: int}
     */
    public function reemplazarDesdeArchivo(array $filas, ?int $userId): array
    {
        if (! Schema::hasTable('tbl_cc_gasto_real_sap') || ! $this->disponible()) {
            throw new \RuntimeException('Falta ejecutar la migración del gasto real.');
        }

        $grupos = [];
        $omitidas = 0;
        foreach ($filas as $fila) {
            $empresa = strtoupper(trim((string) ($fila['empresa'] ?? '')));
            $centro = strtoupper(trim((string) ($fila['centro'] ?? '')));
            $cuenta = trim((string) ($fila['cuenta'] ?? ''));
            $anio = (int) ($fila['anio'] ?? 0);
            $mes = (int) ($fila['mes'] ?? 0);
            if ($empresa === '' || $centro === '' || $cuenta === '' || $anio < 2000 || $anio > 2100 || $mes < 1 || $mes > 12) {
                $omitidas++;

                continue;
            }
            $grupos[$empresa.'|'.$anio][] = $fila;
        }

        $empresas = [];
        $total = 0;
        $cuentas = 0;
        foreach ($grupos as $clave => $rows) {
            [$empresa, $anioTxt] = explode('|', $clave, 2);
            $anio = (int) $anioTxt;
            $guardadas = $this->guardarGrupoArchivo($empresa, $anio, $rows, $userId);
            $total += $guardadas['filas'];
            $cuentas += $guardadas['cuentas'];
            $empresas[] = $empresa.' '.$anio;
            Cache::forget('cc.gasto-emp.v4.'.$empresa.'.'.$anio);
        }

        return [
            'filas' => $total,
            'cuentas' => $cuentas,
            'empresas' => $empresas,
            'omitidas' => $omitidas,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{filas: int, cuentas: int}
     */
    protected function guardarGrupoArchivo(string $empresa, int $anio, array $rows, ?int $userId): array
    {
        $ahora = now();
        $sap = [];
        $acum = [];
        foreach ($rows as $fila) {
            $centro = strtoupper(trim((string) $fila['centro']));
            $cuenta = trim((string) $fila['cuenta']);
            $mes = (int) $fila['mes'];
            $ck = $this->digitos($cuenta) ?: $cuenta;
            $bucket = $centro.'|'.$ck;
            if (! isset($acum[$bucket])) {
                $acum[$bucket] = [
                    'centro' => $centro,
                    'cuenta' => $cuenta,
                    'nombre' => trim((string) ($fila['cuenta_nombre'] ?? '')),
                    'depto' => trim((string) ($fila['depto'] ?? '')),
                    'group_mask' => trim((string) ($fila['group_mask'] ?? '')),
                    'gasto' => array_fill(0, 12, 0.0),
                    'gasto_usd' => array_fill(0, 12, 0.0),
                ];
            }
            if ($acum[$bucket]['nombre'] === '' && ! empty($fila['cuenta_nombre'])) {
                $acum[$bucket]['nombre'] = trim((string) $fila['cuenta_nombre']);
            }
            if ($acum[$bucket]['depto'] === '' && ! empty($fila['depto'])) {
                $acum[$bucket]['depto'] = trim((string) $fila['depto']);
            }
            $acum[$bucket]['gasto'][$mes - 1] = round($acum[$bucket]['gasto'][$mes - 1] + (float) $fila['importe'], 2);
            $acum[$bucket]['gasto_usd'][$mes - 1] = round($acum[$bucket]['gasto_usd'][$mes - 1] + (float) $fila['importe_usd'], 2);

            $fecha = $fila['fecha'] ?? null;
            $sap[] = [
                'empresa' => $empresa,
                'anio' => $anio,
                'centro_codigo' => $centro,
                'cuenta_codigo' => $cuenta,
                'cuenta_nombre' => $this->corte((string) ($fila['cuenta_nombre'] ?? ''), 180),
                'depto' => $this->corte((string) ($fila['depto'] ?? ''), 80),
                'group_mask' => $this->corte((string) ($fila['group_mask'] ?? ''), 20),
                'fecha' => $fecha !== '' ? $fecha : null,
                'mes' => $mes,
                'importe' => round((float) $fila['importe'], 2),
                'importe_usd' => round((float) $fila['importe_usd'], 2),
                'fila' => json_encode($fila['fila'] ?? [], JSON_UNESCAPED_UNICODE) ?: '{}',
                'origen' => 'archivo',
                'synced_by' => $userId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        $mapa = [];
        foreach ($acum as $item) {
            $mapa[$item['centro']][$item['cuenta']] = [
                'codigo' => $item['cuenta'],
                'nombre' => $item['nombre'],
                'depto' => $item['depto'],
                'group_mask' => $item['group_mask'],
                'gasto' => $item['gasto'],
                'gasto_usd' => $item['gasto_usd'],
            ];
        }

        DB::transaction(function () use ($empresa, $anio, $sap, $mapa, $userId, $ahora) {
            DB::table('tbl_cc_gasto_real_sap')->where('empresa', $empresa)->where('anio', $anio)->delete();
            DB::table('tbl_cc_gasto_real_snap')->where('empresa', $empresa)->where('anio', $anio)->delete();
            DB::table('tbl_cc_gasto_real_cc')->where('empresa', $empresa)->where('anio', $anio)->delete();
            foreach (array_chunk($sap, 300) as $chunk) {
                DB::table('tbl_cc_gasto_real_sap')->insert($chunk);
            }
            $this->guardarMapa($empresa, $anio, $mapa, 'archivo', $userId, true);
            $this->marcarCarga($empresa, $anio, 'archivo', count($sap), $userId, $ahora);
        });

        return ['filas' => count($sap), 'cuentas' => count($acum)];
    }

    /**
     * @param  array<string, array<string, mixed>>  $por
     * @return array<string, array<string, mixed>>
     */
    protected function cuentasCanonicas(array $por): array
    {
        $out = [];
        foreach ($por as $key => $item) {
            if (! is_array($item) || ! isset($item['gasto']) || ! is_array($item['gasto'])) {
                continue;
            }
            $codigo = trim((string) ($item['codigo'] ?? $key));
            $digits = $this->digitos($codigo);
            $ck = $digits !== '' ? $digits : $codigo;
            if ($ck === '' || isset($out[$ck])) {
                continue;
            }
            $item['codigo'] = $codigo !== '' ? $codigo : $ck;
            $out[$ck] = $item;
        }

        return $out;
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $mapa
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function centrosCanonicos(array $mapa): array
    {
        $grupos = [];
        foreach ($mapa as $cc => $por) {
            if (! is_array($por)) {
                continue;
            }
            $cc = strtoupper(trim((string) $cc));
            if ($cc === '') {
                continue;
            }
            $alt = ltrim($cc, '0');
            if ($alt === '') {
                $alt = '0';
            }
            if (! isset($grupos[$alt]) || strlen($cc) >= strlen($grupos[$alt]['cc'])) {
                $grupos[$alt] = ['cc' => $cc, 'por' => $por];
            }
        }
        $out = [];
        foreach ($grupos as $grupo) {
            $out[$grupo['cc']] = $grupo['por'];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function filaSnap(string $empresa, int $year, string $cc, string $codigo, array $item, string $origen, ?int $userId, $ahora): array
    {
        $gasto = array_values($item['gasto'] ?? []);
        $usd = array_values($item['gasto_usd'] ?? []);
        $row = [
            'empresa' => $empresa,
            'anio' => $year,
            'centro_codigo' => $cc,
            'cuenta_codigo' => $this->corte($codigo, 40),
            'cuenta_nombre' => $this->corte((string) ($item['nombre'] ?? ''), 180),
            'depto' => $this->corte((string) ($item['depto'] ?? ''), 80),
            'group_mask' => $this->corte((string) ($item['group_mask'] ?? ''), 20),
            'origen' => $origen,
            'synced_at' => $ahora,
            'synced_by' => $userId,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];
        for ($i = 1; $i <= 12; $i++) {
            $suf = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $row['mes_'.$suf] = round((float) ($gasto[$i - 1] ?? 0), 2);
            $row['usd_'.$suf] = round((float) ($usd[$i - 1] ?? 0), 2);
        }

        return $row;
    }

    protected function marcarCarga(string $empresa, int $year, string $origen, int $filas, ?int $userId, $ahora = null, bool $completa = true, ?string $firma = null): void
    {
        $ahora = $ahora ?: now();
        $existe = DB::table('tbl_cc_gasto_real_carga')->where('empresa', $empresa)->where('anio', $year)->exists();
        $datos = [
            'completa' => $completa,
            'filas' => $filas,
            'origen' => $origen,
            'synced_at' => $ahora,
            'synced_by' => $userId,
            'updated_at' => $ahora,
        ];
        if ($firma !== null && Schema::hasColumn('tbl_cc_gasto_real_carga', 'firma')) {
            $datos['firma'] = $firma;
        }
        if ($existe) {
            DB::table('tbl_cc_gasto_real_carga')->where('empresa', $empresa)->where('anio', $year)->update($datos);

            return;
        }
        $datos['empresa'] = $empresa;
        $datos['anio'] = $year;
        $datos['created_at'] = $ahora;
        DB::table('tbl_cc_gasto_real_carga')->insert($datos);
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     */
    protected function reemplazarFilasSap(string $empresa, int $year, array $filas, ?int $userId, bool $completa, ?int $mesMax = null): void
    {
        $ahora = now();
        $sap = [];
        $centros = [];
        foreach ($filas as $fila) {
            $centro = strtoupper(trim((string) ($fila['centro'] ?? '')));
            $cuenta = trim((string) ($fila['cuenta'] ?? ''));
            $mes = (int) ($fila['mes'] ?? 0);
            $anio = (int) ($fila['anio'] ?? $year);
            if ($centro === '' || $cuenta === '' || $mes < 1 || $mes > 12 || $anio !== $year) {
                continue;
            }
            if ($mesMax !== null && $mes > $mesMax) {
                continue;
            }
            $centros[$centro] = $centro;
            $fecha = $fila['fecha'] ?? null;
            $sap[] = [
                'empresa' => $empresa,
                'anio' => $year,
                'centro_codigo' => $centro,
                'cuenta_codigo' => $this->corte($cuenta, 40),
                'cuenta_nombre' => $this->corte((string) ($fila['cuenta_nombre'] ?? ''), 180),
                'depto' => $this->corte((string) ($fila['depto'] ?? ''), 80),
                'group_mask' => $this->corte((string) ($fila['group_mask'] ?? ''), 20),
                'fecha' => $fecha !== '' ? $fecha : null,
                'mes' => $mes,
                'importe' => round((float) ($fila['importe'] ?? 0), 2),
                'importe_usd' => round((float) ($fila['importe_usd'] ?? 0), 2),
                'fila' => json_encode($fila['fila'] ?? [], JSON_UNESCAPED_UNICODE) ?: '{}',
                'origen' => 'api',
                'synced_by' => $userId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        DB::transaction(function () use ($empresa, $year, $sap, $centros, $completa, $mesMax) {
            $q = DB::table('tbl_cc_gasto_real_sap')->where('empresa', $empresa)->where('anio', $year);
            if (! $completa && $centros) {
                $q->whereIn('centro_codigo', array_values($centros));
            }
            if ($mesMax !== null) {
                $q->where('mes', '<=', $mesMax);
            }
            $q->delete();
            foreach (array_chunk($sap, 300) as $chunk) {
                DB::table('tbl_cc_gasto_real_sap')->insert($chunk);
            }
        });
    }

    protected function tieneFilasSap(string $empresa, int $year): bool
    {
        if (! Schema::hasTable('tbl_cc_gasto_real_sap')) {
            return false;
        }

        return DB::table('tbl_cc_gasto_real_sap')->where('empresa', $empresa)->where('anio', $year)->exists();
    }

    protected function tocarCarga(string $empresa, int $year): void
    {
        if (! Schema::hasTable('tbl_cc_gasto_real_carga')) {
            return;
        }
        DB::table('tbl_cc_gasto_real_carga')->where('empresa', $empresa)->where('anio', $year)->update([
            'synced_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, array<string, array<string, mixed>>>  $mapa
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function anularMesesPosteriores(array $mapa): array
    {
        foreach ($mapa as $cc => $por) {
            if (! is_array($por)) {
                continue;
            }
            foreach ($por as $ck => $item) {
                if (! is_array($item)) {
                    continue;
                }
                foreach (['gasto', 'gasto_usd'] as $campo) {
                    if (! isset($item[$campo]) || ! is_array($item[$campo])) {
                        continue;
                    }
                    $serie = array_values($item[$campo]);
                    for ($i = 9; $i < 12; $i++) {
                        $serie[$i] = 0.0;
                    }
                    $item[$campo] = $serie;
                }
                $mapa[$cc][$ck] = $item;
            }
        }

        return $mapa;
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     * @return array<int, array<string, mixed>>
     */
    protected function filasHastaMes(array $filas, int $mesMax): array
    {
        return array_values(array_filter($filas, function ($fila) use ($mesMax) {
            return is_array($fila) && (int) ($fila['mes'] ?? 0) >= 1 && (int) ($fila['mes'] ?? 0) <= $mesMax;
        }));
    }

    /**
     * @param  array<int, string>  $centros
     * @return array<string, array<string, float>>
     */
    protected function ultimosMesesGuardados(string $empresa, int $year, array $centros): array
    {
        if (! $centros) {
            return [];
        }
        $out = [];
        $rows = DB::table('tbl_cc_gasto_real_snap')
            ->where('empresa', $empresa)
            ->where('anio', $year)
            ->whereIn('centro_codigo', $centros)
            ->get();
        foreach ($rows as $row) {
            $cc = strtoupper(trim((string) $row->centro_codigo));
            $clave = $cc.'|'.$this->digitos((string) $row->cuenta_codigo);
            $guardado = [];
            foreach (['10', '11', '12'] as $suf) {
                $guardado['mes_'.$suf] = round((float) ($row->{'mes_'.$suf} ?? 0), 2);
                $guardado['usd_'.$suf] = round((float) ($row->{'usd_'.$suf} ?? 0), 2);
            }
            $out[$clave] = $guardado;
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $serie
     */
    protected function serieFirma(array $serie, int $meses = 12): string
    {
        $out = [];
        $meses = max(1, min(12, $meses));
        for ($i = 0; $i < $meses; $i++) {
            $out[] = number_format(round((float) ($serie[$i] ?? 0), 2), 2, '.', '');
        }

        return implode(',', $out);
    }

    protected function digitos(string $codigo): string
    {
        $digits = preg_replace('/\D+/', '', $codigo) ?? '';

        return $digits !== '' ? $digits : '';
    }

    protected function corte(string $valor, int $max): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        if (function_exists('mb_substr')) {
            return mb_substr($valor, 0, $max);
        }

        return substr($valor, 0, $max);
    }
}
