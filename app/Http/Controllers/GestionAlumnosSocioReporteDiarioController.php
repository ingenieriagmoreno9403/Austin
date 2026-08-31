<?php

namespace App\Http\Controllers;

use App\Models\PagoSocioDet;
use App\Models\Socio;
use App\Models\SocioCheckoutEnc;
use App\Models\SocioReporteDiario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GestionAlumnosSocioReporteDiarioController extends Controller
{
    public function resumen(Request $request): JsonResponse
    {
        $fecha = $this->resolverFecha($request->query('fecha'));

        $nuevosSocios = Socio::query()
            ->whereDate('created_at', $fecha)
            ->count();

        $sociosEntradas = SocioCheckoutEnc::query()
            ->whereDate('fecha_hora_entrada', $fecha)
            ->where('resultado', 'permitido')
            ->count();

        $montoCobradoDia = (float) PagoSocioDet::query()
            ->whereDate('fecha_pago', $fecha)
            ->where('status', 'pagado')
            ->sum(DB::raw('COALESCE(monto_pagado,0)'));

        $montoCanceladoDia = 0.0;
        if (Schema::hasTable('tblpagos_socios_cancelados')) {
            $montoCanceladoDia = (float) DB::table('tblpagos_socios_cancelados')
                ->whereDate('fecha_cancelacion', $fecha)
                ->sum(DB::raw('COALESCE(monto_cancelado,0)'));
        }

        $corteCajaEsperado = $montoCobradoDia - $montoCanceladoDia;

        $reporte = SocioReporteDiario::query()
            ->whereDate('fecha_reporte', $fecha)
            ->first();

        return response()->json([
            'data' => [
                'fecha_reporte' => $fecha,
                'nuevos_socios' => $nuevosSocios,
                'socios_entradas' => $sociosEntradas,
                'monto_cobrado_dia' => round($montoCobradoDia, 2),
                'monto_cancelado_dia' => round($montoCanceladoDia, 2),
                'corte_caja_esperado' => round($corteCajaEsperado, 2),
                'caja_fisica_reportada' => $reporte ? (float) $reporte->caja_fisica_reportada : null,
                'diferencia_corte' => $reporte ? (float) $reporte->diferencia_corte : null,
                'observaciones' => $reporte->observaciones ?? null,
            ],
        ]);
    }

    public function guardarCorte(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fecha_reporte' => ['required', 'date'],
            'caja_fisica_reportada' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        $fecha = $this->resolverFecha($validated['fecha_reporte']);
        $resumen = $this->obtenerDatosResumen($fecha);
        $usuario = optional(auth()->user())->name;
        $cajaFisica = (float) $validated['caja_fisica_reportada'];
        $diferencia = round($cajaFisica - $resumen['corte_caja_esperado'], 2);

        $row = SocioReporteDiario::query()->updateOrCreate(
            ['fecha_reporte' => $fecha],
            [
                'nuevos_socios' => $resumen['nuevos_socios'],
                'socios_entradas' => $resumen['socios_entradas'],
                'monto_cobrado_dia' => $resumen['monto_cobrado_dia'],
                'monto_cancelado_dia' => $resumen['monto_cancelado_dia'],
                'corte_caja_esperado' => $resumen['corte_caja_esperado'],
                'caja_fisica_reportada' => $cajaFisica,
                'diferencia_corte' => $diferencia,
                'observaciones' => $validated['observaciones'] ?? null,
                'updated_by' => $usuario,
                'created_by' => $usuario,
            ]
        );

        return response()->json([
            'message' => 'Corte diario guardado correctamente.',
            'data' => $row,
        ]);
    }

    public function historial(): JsonResponse
    {
        $data = SocioReporteDiario::query()
            ->orderByDesc('fecha_reporte')
            ->limit(60)
            ->get();

        return response()->json(['data' => $data]);
    }

    public function trafico(Request $request): JsonResponse
    {
        $fechaFin = Carbon::parse($this->resolverFecha($request->query('fecha')))->startOfDay();
        $dias = max(1, min(31, (int) $request->query('dias', 14)));
        $fechaInicio = $fechaFin->copy()->subDays($dias - 1)->startOfDay();

        $entradasPorDiaRaw = SocioCheckoutEnc::query()
            ->selectRaw('DATE(fecha_hora_entrada) as fecha, COUNT(*) as total')
            ->where('resultado', 'permitido')
            ->whereBetween('fecha_hora_entrada', [$fechaInicio->toDateTimeString(), $fechaFin->copy()->endOfDay()->toDateTimeString()])
            ->groupBy('fecha')
            ->pluck('total', 'fecha')
            ->toArray();

        $labelsDia = [];
        $valuesDia = [];
        $cursor = $fechaInicio->copy();
        while ($cursor->lte($fechaFin)) {
            $key = $cursor->toDateString();
            $labelsDia[] = $key;
            $valuesDia[] = (int) ($entradasPorDiaRaw[$key] ?? 0);
            $cursor->addDay();
        }

        $entradasPorHoraRaw = SocioCheckoutEnc::query()
            ->selectRaw('HOUR(fecha_hora_entrada) as hora, COUNT(*) as total')
            ->where('resultado', 'permitido')
            ->whereDate('fecha_hora_entrada', $fechaFin->toDateString())
            ->groupBy('hora')
            ->pluck('total', 'hora')
            ->toArray();

        $labelsHora = [];
        $valuesHora = [];
        for ($h = 0; $h <= 23; $h++) {
            $labelsHora[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
            $valuesHora[] = (int) ($entradasPorHoraRaw[$h] ?? 0);
        }

        return response()->json([
            'data' => [
                'rango' => [
                    'inicio' => $fechaInicio->toDateString(),
                    'fin' => $fechaFin->toDateString(),
                ],
                'entradas_por_dia' => [
                    'labels' => $labelsDia,
                    'values' => $valuesDia,
                ],
                'entradas_por_hora' => [
                    'labels' => $labelsHora,
                    'values' => $valuesHora,
                ],
            ],
        ]);
    }

    public function pagosGrafica(Request $request): JsonResponse
    {
        $fechaFin = Carbon::parse($this->resolverFecha($request->query('fecha')))->startOfDay();
        $dias = max(1, min(31, (int) $request->query('dias', 14)));
        $fechaInicio = $fechaFin->copy()->subDays($dias - 1)->startOfDay();

        $pagadosRaw = PagoSocioDet::query()
            ->selectRaw('DATE(fecha_pago) as fecha, SUM(COALESCE(monto_pagado,0)) as total')
            ->where('status', 'pagado')
            ->whereNotNull('fecha_pago')
            ->whereBetween('fecha_pago', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->groupBy('fecha')
            ->pluck('total', 'fecha')
            ->toArray();

        $canceladosRaw = [];
        if (Schema::hasTable('tblpagos_socios_cancelados')) {
            $canceladosRaw = DB::table('tblpagos_socios_cancelados')
                ->selectRaw('DATE(fecha_cancelacion) as fecha, SUM(COALESCE(monto_cancelado,0)) as total')
                ->whereNotNull('fecha_cancelacion')
                ->whereBetween('fecha_cancelacion', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
                ->groupBy('fecha')
                ->pluck('total', 'fecha')
                ->toArray();
        }

        $labels = [];
        $pagados = [];
        $cancelados = [];
        $cursor = $fechaInicio->copy();
        while ($cursor->lte($fechaFin)) {
            $key = $cursor->toDateString();
            $labels[] = $key;
            $pagados[] = round((float) ($pagadosRaw[$key] ?? 0), 2);
            $cancelados[] = round((float) ($canceladosRaw[$key] ?? 0), 2);
            $cursor->addDay();
        }

        return response()->json([
            'data' => [
                'labels' => $labels,
                'pagados' => $pagados,
                'cancelados' => $cancelados,
            ],
        ]);
    }

    private function resolverFecha($fecha): string
    {
        if (is_string($fecha) && trim($fecha) !== '') {
            return Carbon::parse($fecha)->toDateString();
        }

        return Carbon::today()->toDateString();
    }

    private function obtenerDatosResumen(string $fecha): array
    {
        $nuevosSocios = Socio::query()->whereDate('created_at', $fecha)->count();
        $sociosEntradas = SocioCheckoutEnc::query()
            ->whereDate('fecha_hora_entrada', $fecha)
            ->where('resultado', 'permitido')
            ->count();

        $montoCobradoDia = (float) PagoSocioDet::query()
            ->whereDate('fecha_pago', $fecha)
            ->where('status', 'pagado')
            ->sum(DB::raw('COALESCE(monto_pagado,0)'));

        $montoCanceladoDia = 0.0;
        if (Schema::hasTable('tblpagos_socios_cancelados')) {
            $montoCanceladoDia = (float) DB::table('tblpagos_socios_cancelados')
                ->whereDate('fecha_cancelacion', $fecha)
                ->sum(DB::raw('COALESCE(monto_cancelado,0)'));
        }

        return [
            'nuevos_socios' => (int) $nuevosSocios,
            'socios_entradas' => (int) $sociosEntradas,
            'monto_cobrado_dia' => round($montoCobradoDia, 2),
            'monto_cancelado_dia' => round($montoCanceladoDia, 2),
            'corte_caja_esperado' => round($montoCobradoDia - $montoCanceladoDia, 2),
        ];
    }
}
