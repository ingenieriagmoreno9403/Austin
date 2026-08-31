<?php

namespace App\Services;

use App\Models\CostoPeadMensual;
use App\Models\OrdenProduccion;
use App\Models\ParoProduccion;
use App\Models\RegistroProduccion;
use Carbon\Carbon;

class RegistroProduccionCalculator
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function calcular(OrdenProduccion $orden, array $input, ?float $tParosOverride = null): array
    {
        $esPieza = $orden->esFlange();
        $metros = $esPieza ? 0.0 : $this->metrosDesdeOrden($orden);
        $piezas = $esPieza
            ? $this->piezasDesdeOrden($orden)
            : ['buenas' => 0, 'malas' => 0];
        $piezasBuenas = (int) $piezas['buenas'];
        $piezasMalas = (int) $piezas['malas'];
        $piezasTotal = $piezasBuenas + $piezasMalas;

        $capacidad = $orden->maquina ? (float) ($orden->maquina->capacidad_pr_hora ?? 0) : 0.0;

        $tParos = $tParosOverride;
        if ($tParos === null) {
            $tParos = (float) ParoProduccion::query()
                ->where('orden_id', $orden->id)
                ->sum('duracion_h');
        }

        $tProgramado = $this->num($input['t_programado_h'] ?? null);
        $tArranque = $this->num($input['t_arranque_h'] ?? null) ?? 0.0;

        $horaInicio = !empty($input['hora_inicio']) ? Carbon::parse($input['hora_inicio']) : null;
        $horaFin = !empty($input['hora_fin']) ? Carbon::parse($input['hora_fin']) : null;

        if ($tProgramado === null && $horaInicio && $horaFin && $horaFin->gte($horaInicio)) {
            $tProgramado = round($horaInicio->floatDiffInHours($horaFin), 3);
        }

        $tOperando = null;
        if ($tProgramado !== null) {
            $tOperando = max(0, round($tProgramado - $tArranque - (float) $tParos, 3));
        }

        $matInicial = $this->num($input['mat_inicial_kg'] ?? null) ?? 0.0;
        $matReciclado = $this->num($input['mat_reciclado_kg'] ?? null) ?? 0.0;
        $aditivos = $this->num($input['aditivos_kg'] ?? null) ?? 0.0;
        $matSobrante = $this->num($input['mat_sobrante_kg'] ?? null) ?? 0.0;
        $matUtilizado = round($matInicial + $matReciclado + $aditivos - $matSobrante, 3);

        $bueno = $this->num($input['prod_bueno_kg'] ?? null);
        $defectuoso = $this->num($input['prod_defectuoso_kg'] ?? null);

        // Flange/conexión: si no capturaron kg, estimar desde piezas × peso ref.
        if ($esPieza && $piezasTotal > 0) {
            $pesoPieza = $this->pesoPiezaRef($orden);
            if ($bueno === null && $pesoPieza > 0) {
                $bueno = round($piezasBuenas * $pesoPieza, 3);
            }
            if ($defectuoso === null && $pesoPieza > 0) {
                $defectuoso = round($piezasMalas * $pesoPieza, 3);
            }
        }

        $bueno = $bueno ?? 0.0;
        $defectuoso = $defectuoso ?? 0.0;
        $total = round($bueno + $defectuoso, 3);

        // Calidad / merma: en piezas prioriza conteo de piezas; si no, kg.
        if ($esPieza && $piezasTotal > 0) {
            $porcentajeMerma = round(($piezasMalas / $piezasTotal) * 100, 3);
            $calidad = round(($piezasBuenas / $piezasTotal) * 100, 3);
        } else {
            $porcentajeMerma = $total > 0 ? round(($defectuoso / $total) * 100, 3) : null;
            $calidad = $total > 0 ? round(($bueno / $total) * 100, 3) : null;
        }

        $rendimientoPct = $matUtilizado > 0 ? round(($bueno / $matUtilizado) * 100, 3) : null;
        $prodHora = ($tOperando !== null && $tOperando > 0) ? round($bueno / $tOperando, 3) : null;
        $prodHoraPiezas = ($esPieza && $tOperando !== null && $tOperando > 0 && $piezasBuenas > 0)
            ? round($piezasBuenas / $tOperando, 3)
            : null;

        $disponibilidad = ($tProgramado !== null && $tProgramado > 0 && $tOperando !== null)
            ? round(($tOperando / $tProgramado) * 100, 3)
            : null;
        $rendimientoOee = ($prodHora !== null && $capacidad > 0)
            ? round(($prodHora / $capacidad) * 100, 3)
            : null;

        $oee = null;
        if ($disponibilidad !== null && $rendimientoOee !== null && $calidad !== null) {
            $oee = round(($disponibilidad / 100) * ($rendimientoOee / 100) * ($calidad / 100) * 100, 3);
        }

        $costoPead = $this->num($input['costo_pead_kg'] ?? null);
        if ($costoPead === null) {
            $costoPead = CostoPeadMensual::costoParaFecha($orden->fecha);
        }

        $costoMaterial = ($costoPead !== null) ? round($matUtilizado * $costoPead, 2) : null;
        $costoUnitM = ($costoMaterial !== null && $metros > 0) ? round($costoMaterial / $metros, 4) : null;
        $costoUnitPieza = ($costoMaterial !== null && $piezasBuenas > 0)
            ? round($costoMaterial / $piezasBuenas, 4)
            : null;

        $kwh = $this->num($input['kwh'] ?? null);
        $kwhKg = ($kwh !== null && $bueno > 0) ? round($kwh / $bueno, 4) : null;

        return [
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            't_programado_h' => $tProgramado,
            't_arranque_h' => $this->num($input['t_arranque_h'] ?? null),
            't_paros_h' => round((float) $tParos, 3),
            't_operando_h' => $tOperando,
            'mat_inicial_kg' => $this->num($input['mat_inicial_kg'] ?? null),
            'mat_reciclado_kg' => $this->num($input['mat_reciclado_kg'] ?? null),
            'aditivos_kg' => $this->num($input['aditivos_kg'] ?? null),
            'mat_sobrante_kg' => $this->num($input['mat_sobrante_kg'] ?? null),
            'mat_utilizado_kg' => $matUtilizado,
            'prod_bueno_kg' => $this->num($input['prod_bueno_kg'] ?? null) ?? ($esPieza && $bueno > 0 ? $bueno : null),
            'prod_defectuoso_kg' => $this->num($input['prod_defectuoso_kg'] ?? null) ?? ($esPieza && $defectuoso > 0 ? $defectuoso : null),
            'total_producido_kg' => $total,
            'metros' => $metros > 0 ? $metros : null,
            'piezas_buenas' => $esPieza ? $piezasBuenas : null,
            'piezas_malas' => $esPieza ? $piezasMalas : null,
            'porcentaje_merma' => $porcentajeMerma,
            'rendimiento_pct' => $rendimientoPct,
            'prod_hora_kg' => $prodHora,
            'prod_hora_piezas' => $prodHoraPiezas,
            'disponibilidad_pct' => $disponibilidad,
            'rendimiento_oee_pct' => $rendimientoOee,
            'calidad_pct' => $calidad,
            'oee_pct' => $oee,
            'costo_pead_kg' => $costoPead,
            'costo_material' => $costoMaterial,
            'costo_unit_m' => $costoUnitM,
            'costo_unit_pieza' => $costoUnitPieza,
            'kwh' => $kwh,
            'kwh_kg' => $kwhKg,
            'observaciones' => $input['observaciones'] ?? null,
        ];
    }

    public function recalcularYGuardar(OrdenProduccion $orden, ?RegistroProduccion $registro = null): RegistroProduccion
    {
        $registro = $registro ?: RegistroProduccion::firstOrNew(['orden_id' => $orden->id]);

        $input = $registro->exists ? $registro->toArray() : [];
        $calc = $this->calcular($orden, $input);

        $registro->fill(array_merge($calc, [
            'orden_id' => $orden->id,
            'updated_by' => auth()->id(),
        ]));
        $registro->save();

        return $registro;
    }

    public function metrosDesdeOrden(OrdenProduccion $orden): float
    {
        if (!$orden->relationLoaded('detalles')) {
            $orden->load('detalles.salidas');
        }

        $metros = 0.0;
        foreach ($orden->detalles as $detalle) {
            $metros += (float) $detalle->salidas->sum('metros');
        }

        return round($metros, 3);
    }

    /**
     * @return array{buenas: int, malas: int}
     */
    public function piezasDesdeOrden(OrdenProduccion $orden): array
    {
        if (!$orden->relationLoaded('detalles')) {
            $orden->load('detalles.salidas');
        }

        $buenas = 0;
        $malas = 0;
        foreach ($orden->detalles as $detalle) {
            foreach ($detalle->salidas as $salida) {
                $buenas += (int) ($salida->piezas_buenas ?? 0);
                $malas += (int) ($salida->piezas_malas ?? 0);
            }
        }

        return ['buenas' => $buenas, 'malas' => $malas];
    }

    private function pesoPiezaRef(OrdenProduccion $orden): float
    {
        if (!$orden->relationLoaded('detalles')) {
            $orden->load('detalles');
        }

        foreach ($orden->detalles as $detalle) {
            if ($detalle->esFabricar() && (float) ($detalle->kg_metro ?? 0) > 0) {
                return (float) $detalle->kg_metro;
            }
        }

        return 0.0;
    }

    private function num(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (float) $v;
    }
}
