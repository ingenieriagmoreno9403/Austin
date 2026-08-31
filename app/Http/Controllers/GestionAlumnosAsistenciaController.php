<?php

namespace App\Http\Controllers;

use App\Models\AsistenciaAlumno;
use App\Models\ConvenioAlumnoEmpresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GestionAlumnosAsistenciaController extends Controller
{
    /**
     * Devuelve las asistencias de una empresa para un mes/año.
     * GET /api/asistencias?empresa_id=X&mes=M&anio=Y
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'empresa_id' => ['required', 'integer'],
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'between:2020,2050'],
        ]);

        $empresaId = (int) $request->query('empresa_id');
        $mes = (int) $request->query('mes');
        $anio = (int) $request->query('anio');

        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfDay();
        $fechaFin = $fechaInicio->copy()->endOfMonth();

        $rows = AsistenciaAlumno::query()
            ->where('empresa_id', $empresaId)
            ->whereBetween('fecha', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->get(['id', 'alumno_id', 'empresa_id', 'convenio_id', 'fecha', 'estado', 'observaciones']);

        $data = [];
        foreach ($rows as $row) {
            $alumnoId = (string) $row->alumno_id;
            $dia = (int) $row->fecha->day;
            if (!isset($data[$alumnoId])) {
                $data[$alumnoId] = [];
            }
            $data[$alumnoId][$dia] = $row->estado;
        }

        return response()->json([
            'data' => $data,
            'empresa_id' => $empresaId,
            'mes' => $mes,
            'anio' => $anio,
        ]);
    }

    /**
     * Guarda masivamente las asistencias de una empresa para un mes/año.
     * POST /api/asistencias
     * Body: { empresa_id, mes, anio, asistencias: { alumno_id: { dia: estado, ... }, ... } }
     */
    public function guardarMasivo(Request $request): JsonResponse
    {
        $request->validate([
            'empresa_id' => ['required', 'integer'],
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'between:2020,2050'],
            'asistencias' => ['required', 'array'],
        ]);

        $empresaId = (int) $request->input('empresa_id');
        $mes = (int) $request->input('mes');
        $anio = (int) $request->input('anio');
        $asistencias = $request->input('asistencias');

        $convenios = ConvenioAlumnoEmpresa::query()
            ->where('empresa_id', $empresaId)
            ->where('estado', 'activo')
            ->pluck('id', 'alumno_id');

        $insertados = 0;
        $actualizados = 0;

        DB::transaction(function () use ($asistencias, $empresaId, $mes, $anio, $convenios, &$insertados, &$actualizados) {
            foreach ($asistencias as $alumnoId => $dias) {
                if (empty($dias) || !is_array($dias)) continue;

                $alumnoId = (int) $alumnoId;
                $convenioId = $convenios->get($alumnoId);

                foreach ($dias as $dia => $estado) {
                    $dia = (int) $dia;
                    if ($dia < 1 || $dia > 31) continue;
                    if (!in_array($estado, ['A', 'F', 'R', 'J'], true)) continue;

                    $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);

                    $existing = AsistenciaAlumno::query()
                        ->where('alumno_id', $alumnoId)
                        ->where('fecha', $fecha)
                        ->first();

                    if ($existing) {
                        if ($existing->estado !== $estado) {
                            $existing->update([
                                'estado' => $estado,
                                'empresa_id' => $empresaId,
                                'convenio_id' => $convenioId,
                            ]);
                            $actualizados++;
                        }
                    } else {
                        AsistenciaAlumno::create([
                            'alumno_id' => $alumnoId,
                            'empresa_id' => $empresaId,
                            'convenio_id' => $convenioId,
                            'fecha' => $fecha,
                            'estado' => $estado,
                        ]);
                        $insertados++;
                    }
                }
            }

            $this->actualizarResumenMensual($empresaId, $mes, $anio);
        });

        return response()->json([
            'message' => 'Asistencias guardadas correctamente.',
            'insertados' => $insertados,
            'actualizados' => $actualizados,
        ]);
    }

    /**
     * Guarda el cálculo de pagos en tblga_asistencias_resumen_mensual.
     * POST /api/asistencias/guardar-pagos
     */
    public function guardarPagos(Request $request): JsonResponse
    {
        $request->validate([
            'empresa_id' => ['required', 'integer'],
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'between:2020,2050'],
            'precio_mensual_alumno' => ['required', 'numeric', 'min:0'],
            'dias_promedio_mes' => ['required', 'numeric', 'min:0.1'],
            'pagos' => ['required', 'array'],
        ]);

        $empresaId = (int) $request->input('empresa_id');
        $mes = (int) $request->input('mes');
        $anio = (int) $request->input('anio');
        $precioMensual = (float) $request->input('precio_mensual_alumno');
        $diasPromedio = (float) $request->input('dias_promedio_mes');
        $pagos = $request->input('pagos');

        $actualizados = 0;

        DB::transaction(function () use ($pagos, $empresaId, $mes, $anio, $precioMensual, $diasPromedio, &$actualizados) {
            foreach ($pagos as $pago) {
                if (!is_array($pago) || empty($pago['alumno_id'])) continue;

                $alumnoId = (int) $pago['alumno_id'];
                $diasCobrados = (float) ($pago['dias_cobrados'] ?? 0);
                $montoCal = (float) ($pago['monto_calculado'] ?? 0);

                DB::table('tblga_asistencias_resumen_mensual')->updateOrInsert(
                    [
                        'alumno_id' => $alumnoId,
                        'empresa_id' => $empresaId,
                        'anio' => $anio,
                        'mes' => $mes,
                    ],
                    [
                        'precio_mensual_alumno' => $precioMensual,
                        'dias_promedio_mes' => $diasPromedio,
                        'dias_cobrados' => $diasCobrados,
                        'monto_calculado' => $montoCal,
                        'estado_pago' => 'pendiente',
                        'updated_at' => now(),
                    ]
                );
                $actualizados++;
            }
        });

        return response()->json([
            'message' => 'Cálculo de pagos guardado correctamente.',
            'actualizados' => $actualizados,
        ]);
    }

    private function actualizarResumenMensual(int $empresaId, int $mes, int $anio): void
    {
        $fechaInicio = Carbon::create($anio, $mes, 1)->startOfDay();
        $fechaFin = $fechaInicio->copy()->endOfMonth();

        $diasHabiles = 0;
        for ($d = 1; $d <= $fechaFin->day; $d++) {
            $dow = Carbon::create($anio, $mes, $d)->dayOfWeek;
            if ($dow !== Carbon::SUNDAY && $dow !== Carbon::SATURDAY) {
                $diasHabiles++;
            }
        }

        $resumen = AsistenciaAlumno::query()
            ->selectRaw('alumno_id, empresa_id,
                SUM(CASE WHEN estado = "A" THEN 1 ELSE 0 END) as total_a,
                SUM(CASE WHEN estado = "F" THEN 1 ELSE 0 END) as total_f,
                SUM(CASE WHEN estado = "R" THEN 1 ELSE 0 END) as total_r,
                SUM(CASE WHEN estado = "J" THEN 1 ELSE 0 END) as total_j')
            ->where('empresa_id', $empresaId)
            ->whereBetween('fecha', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->groupBy('alumno_id', 'empresa_id')
            ->get();

        $convenios = ConvenioAlumnoEmpresa::query()
            ->where('empresa_id', $empresaId)
            ->where('estado', 'activo')
            ->pluck('id', 'alumno_id');

        foreach ($resumen as $row) {
            $totalA = (int) $row->total_a;
            $porcentaje = $diasHabiles > 0 ? round(($totalA / $diasHabiles) * 100, 2) : null;

            DB::table('tblga_asistencias_resumen_mensual')->updateOrInsert(
                [
                    'alumno_id' => $row->alumno_id,
                    'empresa_id' => $row->empresa_id,
                    'anio' => $anio,
                    'mes' => $mes,
                ],
                [
                    'convenio_id' => $convenios->get($row->alumno_id),
                    'total_asistencias' => $totalA,
                    'total_faltas' => (int) $row->total_f,
                    'total_retardos' => (int) $row->total_r,
                    'total_justificadas' => (int) $row->total_j,
                    'dias_habiles' => $diasHabiles,
                    'porcentaje_asistencia' => $porcentaje,
                    'updated_at' => now(),
                ]
            );
        }
    }
}
