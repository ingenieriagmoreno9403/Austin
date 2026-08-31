<?php

namespace App\Http\Controllers;

use App\Exports\ExportarMermasProduccion;
use App\Exports\ExportarReporteProduccion;
use App\Models\Empleados;
use App\Models\InspeccionCalidadOrdenTrabajo;
use App\Models\Maquina;
use App\Models\Mantenimiento;
use App\Models\OrdenProduccion;
use App\Models\OrdenProduccionDetalleSalida;
use App\Models\Receta;
use App\Models\RegistroProduccion;
use App\Models\Reproceso;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProduccionReportesController extends Controller
{
    use MenuTrait;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    private function assertPuedeVer(): void
    {
        $ok = $this->forpermisos('ver_reportes_produccion') === 'ver_reportes_produccion'
            || $this->forpermisos('ver_reporte_mermas_produccion') === 'ver_reporte_mermas_produccion'
            || $this->forpermisos('ver_produccion') === 'ver_produccion';
        if (!$ok) {
            abort(403, 'No tiene permiso para reportes de producción.');
        }
    }

    public function index(): View
    {
        $this->assertPuedeVer();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $reportes = [
            [
                'clave' => 'mermas',
                'titulo' => 'Reporte de mermas',
                'descripcion' => 'Merma de tubo/flange (kg teórico vs real), resina recuperada y materiales según receta. Incluye gráficas y exportación.',
                'icono' => 'fa-chart-line',
                'ruta' => route('produccion.reportes.mermas'),
                'color' => '#1e3a5f',
                'activo' => true,
            ],
            [
                'clave' => 'produccion_diaria',
                'titulo' => 'Producción diaria',
                'descripcion' => 'Avance de metros/piezas por OP, máquina y turno. Incluye gráficas y exportación.',
                'icono' => 'fa-industry',
                'ruta' => route('produccion.reportes.produccion_diaria'),
                'color' => '#0f766e',
                'activo' => true,
            ],
            [
                'clave' => 'empleado_maquina',
                'titulo' => 'Producción por empleado / máquina',
                'descripcion' => 'Metros, piezas y kg producidos agrupados por operador y máquina.',
                'icono' => 'fa-user-gear',
                'ruta' => route('produccion.reportes.empleado_maquina'),
                'color' => '#7c3aed',
                'activo' => true,
            ],
            [
                'clave' => 'calidad',
                'titulo' => 'Calidad y rechazos',
                'descripcion' => 'Inspecciones aceptadas/rechazadas, espesores fuera de rango y lotes a reproceso.',
                'icono' => 'fa-clipboard-check',
                'ruta' => route('produccion.reportes.calidad'),
                'color' => '#b45309',
                'activo' => true,
            ],
            [
                'clave' => 'reproceso',
                'titulo' => 'Reproceso y resina',
                'descripcion' => 'Seguimiento de triturado, peletizado y stock de resina recuperada.',
                'icono' => 'fa-recycle',
                'ruta' => route('produccion.reportes.reproceso'),
                'color' => '#15803d',
                'activo' => true,
            ],
            [
                'clave' => 'kpis_mantenimiento',
                'titulo' => 'Indicadores de mantenimiento (KPIs)',
                'descripcion' => 'Fallas, horas de paro, MTTR, MTBF, costo y causa más frecuente por máquina (desde órdenes correctivas).',
                'icono' => 'fa-screwdriver-wrench',
                'ruta' => route('produccion.reportes.kpis_mantenimiento'),
                'color' => '#9f1239',
                'activo' => true,
            ],
            [
                'clave' => 'oee',
                'titulo' => 'OEE / Registro de producción',
                'descripcion' => 'Disponibilidad, rendimiento, calidad, OEE %, costos PEAD y paros por orden de producción.',
                'icono' => 'fa-gauge-high',
                'ruta' => route('produccion.reportes.oee'),
                'color' => '#0369a1',
                'activo' => true,
            ],
        ];

        return view('Produccion.reportes_index', compact(
            'varpantallas',
            'varsubmenus',
            'reportes'
        ));
    }

    public function mermas(Request $request): View
    {
        $this->assertPuedeVer();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $seccion = $request->input('seccion', 'tubo');
        if (!in_array($seccion, ['tubo', 'resina', 'materiales'], true)) {
            $seccion = 'tubo';
        }

        $q = trim((string) $request->input('q', ''));
        $tipoProceso = $request->input('tipo_proceso', '');

        $filasTubo = $this->obtenerMermasTubo($fechaInicio, $fechaFin, $q, $tipoProceso);
        $filasResina = $this->obtenerMermasResina($fechaInicio, $fechaFin, $q);
        $filasMateriales = $this->obtenerMermasMateriales($fechaInicio, $fechaFin, $q, $tipoProceso);

        $resumen = [
            'tubo_kg_merma' => round((float) $filasTubo->sum('kg_merma'), 3),
            'tubo_kg_teorico' => round((float) $filasTubo->sum('kg_teorico'), 3),
            'tubo_metros_faltantes' => round((float) $filasTubo->sum('metros_faltantes'), 3),
            'resina_kg_pesado' => round((float) $filasResina->sum('kg_pesado'), 3),
            'resina_kg_recuperada' => round((float) $filasResina->sum('kg_recuperada'), 3),
            'materiales_merma_kg' => round((float) $filasMateriales->sum('merma_estimada'), 3),
            'materiales_tomados' => round((float) $filasMateriales->sum('tomado'), 3),
        ];

        $pctTubo = $resumen['tubo_kg_teorico'] > 0
            ? round(($resumen['tubo_kg_merma'] / $resumen['tubo_kg_teorico']) * 100, 2)
            : 0;

        $graficas = $this->construirDatosGraficas($filasTubo, $filasResina, $filasMateriales, $resumen);

        return view('Produccion.reporte_mermas', compact(
            'varpantallas',
            'varsubmenus',
            'fechaInicio',
            'fechaFin',
            'seccion',
            'q',
            'tipoProceso',
            'filasTubo',
            'filasResina',
            'filasMateriales',
            'resumen',
            'pctTubo',
            'graficas'
        ));
    }

    public function exportarMermas(Request $request): BinaryFileResponse
    {
        $this->assertPuedeVer();

        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $seccion = $request->input('seccion', 'tubo');
        if (!in_array($seccion, ['tubo', 'resina', 'materiales'], true)) {
            $seccion = 'tubo';
        }
        $q = trim((string) $request->input('q', ''));
        $tipoProceso = $request->input('tipo_proceso', '');

        $filas = match ($seccion) {
            'resina' => $this->obtenerMermasResina($fechaInicio, $fechaFin, $q),
            'materiales' => $this->obtenerMermasMateriales($fechaInicio, $fechaFin, $q, $tipoProceso),
            default => $this->obtenerMermasTubo($fechaInicio, $fechaFin, $q, $tipoProceso),
        };

        $nombre = 'MERMAS_' . strtoupper($seccion) . '_' . $fechaInicio . '_A_' . $fechaFin . '.xlsx';

        return Excel::download(
            new ExportarMermasProduccion($seccion, $filas, $fechaInicio, $fechaFin),
            $nombre
        );
    }

    public function produccionDiaria(Request $request): View
    {
        $this->assertPuedeVer();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $q = trim((string) $request->input('q', ''));
        $tipoProceso = $request->input('tipo_proceso', '');

        $filas = $this->obtenerProduccionDiaria($fechaInicio, $fechaFin, $q, $tipoProceso);

        $resumen = [
            'ops' => $filas->pluck('orden_id')->unique()->filter()->count(),
            'metros' => round((float) $filas->sum('metros'), 3),
            'piezas' => (int) $filas->sum('piezas'),
            'kg_real' => round((float) $filas->sum('kg_real'), 3),
            'kg_teorico' => round((float) $filas->sum('kg_teorico'), 3),
            'salidas' => $filas->count(),
        ];

        $graficas = $this->graficasProduccionDiaria($filas);

        return view('Produccion.reporte_produccion_diaria', compact(
            'varpantallas',
            'varsubmenus',
            'fechaInicio',
            'fechaFin',
            'q',
            'tipoProceso',
            'filas',
            'resumen',
            'graficas'
        ));
    }

    public function exportarProduccionDiaria(Request $request): BinaryFileResponse
    {
        $this->assertPuedeVer();
        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $filas = $this->obtenerProduccionDiaria(
            $fechaInicio,
            $fechaFin,
            trim((string) $request->input('q', '')),
            $request->input('tipo_proceso', '')
        );

        return Excel::download(
            new ExportarReporteProduccion(
                'Produccion diaria',
                ['Fecha', 'OP', 'Pedido', 'Proceso', 'Producto', 'SKU', 'Máquina', 'Turno', 'Metros', 'Piezas', 'Kg teórico', 'Kg real', 'Kg merma', 'Estatus OP'],
                ['fecha', 'folio_op', 'folio_pedido', 'tipo_proceso', 'producto', 'sku', 'maquina', 'turno', 'metros', 'piezas', 'kg_teorico', 'kg_real', 'kg_merma', 'estatus_op'],
                $filas
            ),
            'PRODUCCION_DIARIA_' . $fechaInicio . '_A_' . $fechaFin . '.xlsx'
        );
    }

    public function empleadoMaquina(Request $request): View
    {
        $this->assertPuedeVer();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $q = trim((string) $request->input('q', ''));
        $maquinaId = $request->filled('maquina_id') ? (int) $request->input('maquina_id') : null;
        $operadorId = $request->filled('operador_id') ? (int) $request->input('operador_id') : null;
        $tipoProceso = $request->input('tipo_proceso', '');

        $detalle = $this->obtenerProduccionEmpleadoMaquinaDetalle(
            $fechaInicio,
            $fechaFin,
            $q,
            $tipoProceso,
            $maquinaId,
            $operadorId
        );
        $filas = $this->agregarProduccionEmpleadoMaquina($detalle);
        $graficas = $this->graficasEmpleadoMaquina($filas);

        $resumen = [
            'pares' => $filas->count(),
            'empleados' => $filas->pluck('operador_id')->unique()->filter()->count(),
            'maquinas' => $filas->pluck('maquina_id')->unique()->filter()->count(),
            'metros' => round((float) $filas->sum('metros'), 3),
            'piezas' => (int) $filas->sum('piezas'),
            'kg_real' => round((float) $filas->sum('kg_real'), 3),
            'kg_merma' => round((float) $filas->sum('kg_merma'), 3),
            'salidas' => (int) $filas->sum('salidas'),
        ];

        $maquinas = Maquina::query()->orderBy('nombre')->get(['id', 'nombre', 'codigo']);
        $operadores = Empleados::query()
            ->orderBy('apellido_paterno')
            ->orderBy('primer_nombre')
            ->get(['id', 'primer_nombre', 'segundo_nombre', 'apellido_paterno', 'apellido_materno']);

        return view('Produccion.reporte_empleado_maquina', compact(
            'varpantallas',
            'varsubmenus',
            'fechaInicio',
            'fechaFin',
            'q',
            'tipoProceso',
            'maquinaId',
            'operadorId',
            'filas',
            'resumen',
            'graficas',
            'maquinas',
            'operadores'
        ));
    }

    public function exportarEmpleadoMaquina(Request $request): BinaryFileResponse
    {
        $this->assertPuedeVer();
        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $detalle = $this->obtenerProduccionEmpleadoMaquinaDetalle(
            $fechaInicio,
            $fechaFin,
            trim((string) $request->input('q', '')),
            $request->input('tipo_proceso', ''),
            $request->filled('maquina_id') ? (int) $request->input('maquina_id') : null,
            $request->filled('operador_id') ? (int) $request->input('operador_id') : null
        );
        $filas = $this->agregarProduccionEmpleadoMaquina($detalle);

        return Excel::download(
            new ExportarReporteProduccion(
                'Empleado maquina',
                ['Empleado', 'Máquina', 'Código máq.', 'Salidas', 'OPs', 'Metros', 'Piezas', 'Kg teórico', 'Kg real', 'Kg merma', '% merma'],
                ['empleado', 'maquina', 'maquina_codigo', 'salidas', 'ops', 'metros', 'piezas', 'kg_teorico', 'kg_real', 'kg_merma', 'porcentaje_merma'],
                $filas
            ),
            'PRODUCCION_EMPLEADO_MAQUINA_' . $fechaInicio . '_A_' . $fechaFin . '.xlsx'
        );
    }

    public function calidad(Request $request): View
    {
        $this->assertPuedeVer();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $q = trim((string) $request->input('q', ''));
        $resultado = $request->input('resultado', '');

        $filas = $this->obtenerCalidadRechazos($fechaInicio, $fechaFin, $q, $resultado);

        $aceptadas = $filas->where('resultado_codigo', InspeccionCalidadOrdenTrabajo::RESULTADO_ACEPTADO)->count();
        $rechazadas = $filas->where('resultado_codigo', InspeccionCalidadOrdenTrabajo::RESULTADO_RECHAZADO)->count();
        $fueraRango = $filas->where('espesores_en_rango', false)->count();
        $conReproceso = $filas->where('tiene_reproceso', true)->count();

        $resumen = [
            'total' => $filas->count(),
            'aceptadas' => $aceptadas,
            'rechazadas' => $rechazadas,
            'fuera_rango' => $fueraRango,
            'con_reproceso' => $conReproceso,
            'pct_rechazo' => $filas->count() > 0
                ? round(($rechazadas / $filas->count()) * 100, 2)
                : 0,
        ];

        $graficas = $this->graficasCalidad($filas);

        return view('Produccion.reporte_calidad', compact(
            'varpantallas',
            'varsubmenus',
            'fechaInicio',
            'fechaFin',
            'q',
            'resultado',
            'filas',
            'resumen',
            'graficas'
        ));
    }

    public function exportarCalidad(Request $request): BinaryFileResponse
    {
        $this->assertPuedeVer();
        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $filas = $this->obtenerCalidadRechazos(
            $fechaInicio,
            $fechaFin,
            trim((string) $request->input('q', '')),
            $request->input('resultado', '')
        );

        return Excel::download(
            new ExportarReporteProduccion(
                'Calidad rechazos',
                ['Fecha', 'OP', 'Producto', 'Resultado', 'Estatus', 'En rango', 'Metros', 'Kg real', 'Kg merma', 'Reproceso', 'Auditor', 'Observaciones'],
                ['fecha', 'folio_op', 'producto', 'resultado', 'estatus', 'en_rango_texto', 'metros', 'kg_real', 'kg_merma', 'folio_reproceso', 'auditor', 'observaciones'],
                $filas
            ),
            'CALIDAD_RECHAZOS_' . $fechaInicio . '_A_' . $fechaFin . '.xlsx'
        );
    }

    public function reprocesoResina(Request $request): View
    {
        $this->assertPuedeVer();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();
        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $q = trim((string) $request->input('q', ''));
        $estatus = $request->input('estatus', '');

        $filas = $this->obtenerReprocesoResina($fechaInicio, $fechaFin, $q, $estatus);

        $resumen = [
            'total' => $filas->count(),
            'kg_origen' => round((float) $filas->sum('kg_origen'), 3),
            'kg_triturado' => round((float) $filas->sum('kg_triturado'), 3),
            'kg_resina' => round((float) $filas->sum('kg_resina'), 3),
            'en_proceso' => $filas->where('cerrado', false)->count(),
            'cerrados' => $filas->where('cerrado', true)->count(),
            'rendimiento_pct' => (float) $filas->sum('kg_origen') > 0
                ? round(((float) $filas->sum('kg_resina') / (float) $filas->sum('kg_origen')) * 100, 2)
                : 0,
        ];

        $graficas = $this->graficasReproceso($filas);

        return view('Produccion.reporte_reproceso', compact(
            'varpantallas',
            'varsubmenus',
            'fechaInicio',
            'fechaFin',
            'q',
            'estatus',
            'filas',
            'resumen',
            'graficas'
        ));
    }

    public function exportarReprocesoResina(Request $request): BinaryFileResponse
    {
        $this->assertPuedeVer();
        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $filas = $this->obtenerReprocesoResina(
            $fechaInicio,
            $fechaFin,
            trim((string) $request->input('q', '')),
            $request->input('estatus', '')
        );

        return Excel::download(
            new ExportarReporteProduccion(
                'Reproceso resina',
                ['Fecha', 'Folio', 'OP', 'Origen', 'Estatus', 'Producto', 'Resina', 'Metros', 'Kg origen', 'Kg triturado', 'Kg resina', 'Rendimiento %', 'Máquina'],
                ['fecha', 'folio_reproceso', 'folio_op', 'origen', 'estatus', 'producto_origen', 'producto_resina', 'metros', 'kg_origen', 'kg_triturado', 'kg_resina', 'rendimiento_pct', 'maquina'],
                $filas
            ),
            'REPROCESO_RESINA_' . $fechaInicio . '_A_' . $fechaFin . '.xlsx'
        );
    }

    public function kpisMantenimiento(Request $request): View
    {
        $this->assertPuedeVer();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $maquinaId = (int) $request->input('maquina_id', 0);
        $horasDia = (float) $request->input('horas_programadas_dia', 8);
        if ($horasDia <= 0) {
            $horasDia = 8;
        }

        $maquinas = Maquina::activas()->orderBy('codigo')->orderBy('nombre')->get(['id', 'codigo', 'nombre']);
        $payload = $this->obtenerKpisMantenimiento($fechaInicio, $fechaFin, $maquinaId, $horasDia);

        return view('Produccion.reporte_kpis_mantenimiento', array_merge([
            'varpantallas' => $varpantallas,
            'varsubmenus' => $varsubmenus,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'maquinaId' => $maquinaId,
            'horasDia' => $horasDia,
            'maquinas' => $maquinas,
            'definiciones' => $this->definicionesKpisMantenimiento(),
        ], $payload));
    }

    public function exportarKpisMantenimiento(Request $request): BinaryFileResponse
    {
        $this->assertPuedeVer();
        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $maquinaId = (int) $request->input('maquina_id', 0);
        $horasDia = (float) $request->input('horas_programadas_dia', 8);
        if ($horasDia <= 0) {
            $horasDia = 8;
        }

        $payload = $this->obtenerKpisMantenimiento($fechaInicio, $fechaFin, $maquinaId, $horasDia);

        return Excel::download(
            new ExportarReporteProduccion(
                'KPIs mantenimiento',
                ['Máquina', 'Código', 'Nº fallas', 'Horas paro', 'Costo total ($)', 'MTTR (h)', 'MTBF (h)', 'Disponibilidad %', 'Causa más frecuente'],
                ['maquina', 'codigo', 'num_fallas', 'horas_paro', 'costo_total', 'mttr', 'mtbf', 'disponibilidad_pct', 'causa_frecuente'],
                $payload['filas']
            ),
            'KPIS_MANTENIMIENTO_' . $fechaInicio . '_A_' . $fechaFin . '.xlsx'
        );
    }

    public function oee(Request $request): View
    {
        $this->assertPuedeVer();

        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $maquinaId = (int) $request->input('maquina_id', 0);
        $tipoProceso = (string) $request->input('tipo_proceso', '');

        $maquinas = Maquina::activas()->orderBy('codigo')->orderBy('nombre')->get(['id', 'codigo', 'nombre']);
        $filas = $this->obtenerFilasOee($fechaInicio, $fechaFin, $maquinaId, $tipoProceso);

        $resumen = [
            'ordenes' => $filas->count(),
            'oee_prom' => $filas->whereNotNull('oee_pct')->avg('oee_pct'),
            'disp_prom' => $filas->whereNotNull('disponibilidad_pct')->avg('disponibilidad_pct'),
            'metros' => round((float) $filas->sum('metros'), 2),
            'piezas_buenas' => (int) $filas->sum('piezas_buenas'),
            'piezas_malas' => (int) $filas->sum('piezas_malas'),
            'costo_material' => round((float) $filas->sum('costo_material'), 2),
            'horas_paro' => round((float) $filas->sum('t_paros_h'), 2),
        ];

        $topOee = $filas->filter(fn ($f) => $f['oee_pct'] !== null)->sortByDesc('oee_pct')->take(10)->values();
        $graficas = [
            'oee' => [
                'labels' => $topOee->pluck('folio')->all(),
                'valores' => $topOee->pluck('oee_pct')->all(),
            ],
        ];

        return view('Produccion.reporte_oee', compact(
            'varpantallas',
            'varsubmenus',
            'fechaInicio',
            'fechaFin',
            'maquinaId',
            'tipoProceso',
            'maquinas',
            'filas',
            'resumen',
            'graficas'
        ));
    }

    public function exportarOee(Request $request): BinaryFileResponse
    {
        $this->assertPuedeVer();
        [$fechaInicio, $fechaFin] = $this->resolverRangoFechas($request);
        $maquinaId = (int) $request->input('maquina_id', 0);
        $tipoProceso = (string) $request->input('tipo_proceso', '');
        $filas = $this->obtenerFilasOee($fechaInicio, $fechaFin, $maquinaId, $tipoProceso);

        return Excel::download(
            new ExportarReporteProduccion(
                'OEE produccion',
                ['Orden', 'Tipo', 'Fecha', 'Máquina', 'Turno', 'T.Prog', 'T.Paros', 'T.Operando', 'Metros', 'Piezas B', 'Piezas M', 'Bueno kg', 'Merma %', 'Disp %', 'Rend OEE %', 'Calidad %', 'OEE %', 'Costo mat', 'Costo $/m', 'Costo $/pza'],
                ['folio', 'tipo_proceso', 'fecha', 'maquina', 'turno', 't_programado_h', 't_paros_h', 't_operando_h', 'metros', 'piezas_buenas', 'piezas_malas', 'prod_bueno_kg', 'porcentaje_merma', 'disponibilidad_pct', 'rendimiento_oee_pct', 'calidad_pct', 'oee_pct', 'costo_material', 'costo_unit_m', 'costo_unit_pieza'],
                $filas
            ),
            'OEE_PRODUCCION_' . $fechaInicio . '_A_' . $fechaFin . '.xlsx'
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolverRangoFechas(Request $request): array
    {
        $inicio = $request->input('fecha_inicio');
        $fin = $request->input('fecha_fin');

        if (!$inicio || !$fin) {
            $inicio = Carbon::now()->startOfMonth()->format('Y-m-d');
            $fin = Carbon::now()->format('Y-m-d');
        }

        return [
            Carbon::parse($inicio)->format('Y-m-d'),
            Carbon::parse($fin)->format('Y-m-d'),
        ];
    }

    /**
     * Merma por tubo/flange según kg teórico (metros × kg/m o piezas × kg) vs kg real pesado.
     * También compara metros/piezas pedido vs producidos.
     */
    private function obtenerMermasTubo(string $fechaInicio, string $fechaFin, string $q, string $tipoProceso): Collection
    {
        $salidas = OrdenProduccionDetalleSalida::query()
            ->with([
                'detalle.producto:id,sku,nombre',
                'detalle.orden.pedido.detalles',
                'detalle.orden.maquina:id,nombre',
            ])
            ->whereDate('created_at', '>=', $fechaInicio)
            ->whereDate('created_at', '<=', $fechaFin)
            ->whereHas('detalle.orden', function ($w) use ($tipoProceso) {
                $w->where('estatus', '!=', OrdenProduccion::ESTATUS_CANCELADA);
                if ($tipoProceso === 'TUBO' || $tipoProceso === 'FLANGE' || $tipoProceso === 'CONEXION') {
                    $w->where('tipo_proceso', $tipoProceso);
                }
            })
            ->orderByDesc('created_at')
            ->get();

        if ($q !== '') {
            $salidas = $salidas->filter(function ($salida) use ($q) {
                $orden = $salida->detalle?->orden;
                $producto = $salida->detalle?->producto;
                $haystack = strtoupper(implode(' ', array_filter([
                    $orden?->folio,
                    $orden?->pedido?->folio,
                    $producto?->sku,
                    $producto?->nombre,
                    $salida->diametro_real,
                    $salida->rd_real,
                ])));

                return str_contains($haystack, strtoupper($q));
            })->values();
        }

        return $salidas->map(function (OrdenProduccionDetalleSalida $salida) {
            $detalle = $salida->detalle;
            $orden = $detalle?->orden;
            $esFlange = $orden?->esFlange() ?? false;
            $productoId = (int) ($detalle->producto_id ?? 0);

            $metrosPedido = 0.0;
            $piezasPedido = 0.0;
            if ($orden?->pedido && $productoId > 0) {
                $linea = $orden->pedido->detalles->firstWhere('producto_id', $productoId);
                $cant = $linea ? (float) $linea->cantidad : 0.0;
                if ($esFlange) {
                    $piezasPedido = $cant;
                } else {
                    $metrosPedido = $cant;
                }
            }

            $metrosEsperados = $esFlange
                ? 0.0
                : (float) ($orden->longitud_objetivo_m ?: $metrosPedido);
            $metrosReales = $esFlange ? 0.0 : (float) ($salida->metros ?? 0);
            $metrosFaltantes = $esFlange ? 0.0 : max(round($metrosEsperados - $metrosReales, 3), 0);

            $piezasBuenas = (int) ($salida->piezas_buenas ?? 0);
            $piezasMalas = (int) ($salida->piezas_malas ?? 0);
            $kgTeorico = (float) ($salida->kg_teorico ?? 0);
            $kgReal = (float) ($salida->kg_real ?? 0);
            $kgMerma = (float) ($salida->kg_merma ?? 0);
            $kgRetrabajo = (float) ($salida->kg_retrabajo ?? 0);

            if ($esFlange && $kgMerma <= 0 && $piezasMalas > 0 && (float) ($detalle->kg_metro ?? 0) > 0) {
                $kgMerma = round($piezasMalas * (float) $detalle->kg_metro, 3);
            }

            $pct = $kgTeorico > 0
                ? round(($kgMerma / $kgTeorico) * 100, 2)
                : (float) ($salida->porcentaje_merma ?? 0);

            return [
                'fecha' => optional($salida->created_at)->format('Y-m-d H:i'),
                'folio_op' => $orden?->folio,
                'folio_pedido' => $orden?->pedido?->folio,
                'tipo_proceso' => $orden?->tipo_proceso
                    ?? ($esFlange ? OrdenProduccion::TIPO_PROCESO_FLANGE : OrdenProduccion::TIPO_PROCESO_TUBO),
                'producto' => $detalle?->producto?->nombre ?? ('#' . $productoId),
                'sku' => $detalle?->producto?->sku,
                'diametro' => $salida->diametro_real ?: $detalle?->diametro,
                'rd' => $salida->rd_real ?: $detalle?->rd,
                'kg_metro' => (float) ($detalle->kg_metro ?? 0),
                'maquina' => $orden?->maquina?->nombre,
                'metros_esperados' => $metrosEsperados,
                'metros_reales' => $metrosReales,
                'metros_faltantes' => $metrosFaltantes,
                'piezas_pedido' => $piezasPedido,
                'piezas_buenas' => $piezasBuenas,
                'piezas_malas' => $piezasMalas,
                'kg_teorico' => $kgTeorico,
                'kg_real' => $kgReal,
                'kg_merma' => $kgMerma,
                'kg_retrabajo' => $kgRetrabajo,
                'porcentaje_merma' => $pct,
                'orden_id' => $orden?->id,
                'salida_id' => $salida->id,
            ];
        });
    }

    /**
     * Merma recuperada / convertida a resina vía reproceso-peletizado.
     */
    private function obtenerMermasResina(string $fechaInicio, string $fechaFin, string $q): Collection
    {
        $reprocesos = Reproceso::query()
            ->with([
                'orden:id,folio,pedido_id',
                'orden.pedido:id,folio',
                'detalle.producto:id,sku,nombre',
                'productoResina:id,sku,nombre',
                'maquina:id,nombre',
            ])
            ->where('estatus', '!=', Reproceso::ESTATUS_CANCELADA)
            ->where(function ($w) use ($fechaInicio, $fechaFin) {
                $w->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
                    ->orWhereBetween('stock_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
                    ->orWhereBetween('peletizado_fin_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            })
            ->orderByDesc('id')
            ->get();

        if ($q !== '') {
            $reprocesos = $reprocesos->filter(function (Reproceso $r) use ($q) {
                $haystack = strtoupper(implode(' ', array_filter([
                    $r->folio,
                    $r->orden?->folio,
                    $r->orden?->pedido?->folio,
                    $r->detalle?->producto?->nombre,
                    $r->productoResina?->nombre,
                    $r->identificacion_resina,
                    $r->identificacion_material,
                ])));

                return str_contains($haystack, strtoupper($q));
            })->values();
        }

        return $reprocesos->map(function (Reproceso $r) {
            $kgOrigen = (float) ($r->kg_pesado ?: $r->kg_reportado ?: $r->kg_estimado ?: 0);
            $kgRecuperada = (float) ($r->kg_saca_resina ?: $r->kg_resina_sistema ?: $r->kg_produccion_reportado ?: 0);
            $perdida = $kgOrigen > 0 && $kgRecuperada > 0
                ? max(round($kgOrigen - $kgRecuperada, 3), 0)
                : null;
            $rendimiento = $kgOrigen > 0 && $kgRecuperada > 0
                ? round(($kgRecuperada / $kgOrigen) * 100, 2)
                : null;

            return [
                'fecha' => optional($r->created_at)->format('Y-m-d H:i'),
                'folio_reproceso' => $r->folio,
                'folio_op' => $r->orden?->folio,
                'origen' => $r->origen_texto,
                'estatus' => $r->estatus_texto,
                'producto_origen' => $r->detalle?->producto?->nombre,
                'producto_resina' => $r->productoResina?->nombre
                    ?: ($r->identificacion_resina ?: $r->identificacion_material),
                'metros' => (float) ($r->metros ?? 0),
                'kg_estimado' => (float) ($r->kg_estimado ?? 0),
                'kg_pesado' => $kgOrigen,
                'kg_triturado' => (float) ($r->kg_saca_triturada ?? 0),
                'kg_recuperada' => $kgRecuperada,
                'kg_perdida_proceso' => $perdida,
                'rendimiento_pct' => $rendimiento,
                'maquina' => $r->maquina?->nombre,
                'orden_id' => $r->orden_id,
                'reproceso_id' => $r->id,
            ];
        });
    }

    /**
     * Materiales: receta × metros/piezas pedidas (tomados) vs receta × producidos.
     * La diferencia + kg_merma de salidas estima material mermado o sobrante.
     */
    private function obtenerMermasMateriales(string $fechaInicio, string $fechaFin, string $q, string $tipoProceso): Collection
    {
        $ordenes = OrdenProduccion::query()
            ->with([
                'pedido.detalles',
                'detalles.producto:id,sku,nombre',
                'detalles.salidas',
                'maquina:id,nombre',
            ])
            ->whereNotNull('materiales_tomados_at')
            ->whereDate('materiales_tomados_at', '>=', $fechaInicio)
            ->whereDate('materiales_tomados_at', '<=', $fechaFin)
            ->where('estatus', '!=', OrdenProduccion::ESTATUS_CANCELADA)
            ->when(in_array($tipoProceso, ['TUBO', 'FLANGE', 'CONEXION'], true), fn ($w) => $w->where('tipo_proceso', $tipoProceso))
            ->orderByDesc('materiales_tomados_at')
            ->get();

        if ($q !== '') {
            $ordenes = $ordenes->filter(function (OrdenProduccion $orden) use ($q) {
                $haystack = strtoupper(implode(' ', array_filter([
                    $orden->folio,
                    $orden->pedido?->folio,
                    ...$orden->detalles->map(fn ($d) => ($d->producto?->sku ?? '') . ' ' . ($d->producto?->nombre ?? ''))->all(),
                ])));

                return str_contains($haystack, strtoupper($q));
            })->values();
        }

        $filas = collect();

        foreach ($ordenes as $orden) {
            $esFlange = $orden->esFlange();

            foreach ($orden->detalles as $detalle) {
                if (!$detalle->esFabricar()) {
                    continue;
                }

                $productoId = (int) ($detalle->producto_id ?? 0);
                if ($productoId <= 0) {
                    continue;
                }

                $cantidadPedido = 0.0;
                if ($orden->pedido) {
                    $linea = $orden->pedido->detalles->firstWhere('producto_id', $productoId);
                    $cantidadPedido = $linea ? (float) $linea->cantidad : 0.0;
                }

                $cantidadProducida = $esFlange
                    ? (float) ($detalle->piezas_producidas ?? 0)
                    : (float) ($detalle->metros_producidos ?? 0);

                if ($cantidadPedido <= 0) {
                    $cantidadPedido = max($cantidadProducida, 1.0);
                }

                $receta = Receta::activaParaProducto($productoId);
                if (!$receta || $receta->detalles->isEmpty()) {
                    continue;
                }

                $kgMermaProceso = (float) ($detalle->kg_merma ?? 0);
                $kgTeoricoProd = (float) ($detalle->kg_teorico ?? 0);

                foreach ($receta->detalles as $comp) {
                    $factor = (float) $comp->cantidad;
                    $tomado = round($factor * $cantidadPedido, 4);
                    $teoricoProducido = round($factor * $cantidadProducida, 4);
                    // Material tomado de más respecto a lo realmente producido
                    $excesoVsProducido = max(round($tomado - $teoricoProducido, 4), 0);
                    // Merma de tubo prorrateada al componente (por % de receta o proporción de cantidad)
                    $proporcion = null;
                    $sumaFactores = (float) $receta->detalles->sum('cantidad');
                    if ($comp->porcentaje !== null && (float) $comp->porcentaje > 0) {
                        $proporcion = (float) $comp->porcentaje / 100;
                    } elseif ($sumaFactores > 0) {
                        $proporcion = $factor / $sumaFactores;
                    } else {
                        $proporcion = 0;
                    }
                    $mermaProcesoMp = round($kgMermaProceso * $proporcion, 4);
                    $mermaEstimada = round(max($excesoVsProducido, $mermaProcesoMp), 4);

                    $filas->push([
                        'fecha' => optional($orden->materiales_tomados_at)->format('Y-m-d H:i'),
                        'folio_op' => $orden->folio,
                        'folio_pedido' => $orden->pedido?->folio,
                        'tipo_proceso' => $orden->tipo_proceso
                            ?? ($esFlange ? OrdenProduccion::TIPO_PROCESO_FLANGE : OrdenProduccion::TIPO_PROCESO_TUBO),
                        'producto_pt' => $detalle->producto?->nombre,
                        'sku_pt' => $detalle->producto?->sku,
                        'materia_prima' => $comp->productoMp?->nombre ?? ('MP #' . $comp->producto_mp_id),
                        'sku_mp' => $comp->productoMp?->sku,
                        'unidad' => $comp->unidad?->abreviacion ?: $comp->unidad?->nombre,
                        'factor_receta' => $factor,
                        'cantidad_pedida' => $cantidadPedido,
                        'cantidad_producida' => $cantidadProducida,
                        'unidad_pt' => $esFlange ? 'pzas' : 'm',
                        'tomado' => $tomado,
                        'teorico_producido' => $teoricoProducido,
                        'exceso_vs_producido' => $excesoVsProducido,
                        'kg_merma_proceso' => $kgMermaProceso,
                        'merma_estimada' => $mermaEstimada,
                        'kg_teorico_pt' => $kgTeoricoProd,
                        'porcentaje_receta' => $comp->porcentaje !== null ? (float) $comp->porcentaje : null,
                        'maquina' => $orden->maquina?->nombre,
                        'orden_id' => $orden->id,
                    ]);
                }
            }
        }

        return $filas;
    }

    private function obtenerProduccionDiaria(string $fechaInicio, string $fechaFin, string $q, string $tipoProceso): Collection
    {
        $salidas = OrdenProduccionDetalleSalida::query()
            ->with([
                'detalle.producto:id,sku,nombre',
                'detalle.orden.pedido:id,folio',
                'detalle.orden.maquina:id,nombre',
                'detalle.orden.turno:id,nombre',
            ])
            ->whereDate('created_at', '>=', $fechaInicio)
            ->whereDate('created_at', '<=', $fechaFin)
            ->whereHas('detalle.orden', function ($w) use ($tipoProceso) {
                $w->where('estatus', '!=', OrdenProduccion::ESTATUS_CANCELADA);
                if (in_array($tipoProceso, ['TUBO', 'FLANGE', 'CONEXION'], true)) {
                    $w->where('tipo_proceso', $tipoProceso);
                }
            })
            ->orderByDesc('created_at')
            ->get();

        if ($q !== '') {
            $salidas = $salidas->filter(function ($salida) use ($q) {
                $orden = $salida->detalle?->orden;
                $producto = $salida->detalle?->producto;
                $haystack = strtoupper(implode(' ', array_filter([
                    $orden?->folio,
                    $orden?->pedido?->folio,
                    $producto?->sku,
                    $producto?->nombre,
                    $orden?->maquina?->nombre,
                    $orden?->turno?->nombre,
                ])));

                return str_contains($haystack, strtoupper($q));
            })->values();
        }

        return $salidas->map(function (OrdenProduccionDetalleSalida $salida) {
            $detalle = $salida->detalle;
            $orden = $detalle?->orden;
            $esFlange = $orden?->esFlange() ?? false;
            $piezas = $esFlange
                ? ((int) ($salida->piezas_buenas ?? 0) + (int) ($salida->piezas_malas ?? 0))
                : (int) ($salida->piezas ?? 0);

            return [
                'fecha' => optional($salida->created_at)->format('Y-m-d H:i'),
                'dia' => optional($salida->created_at)->format('Y-m-d'),
                'folio_op' => $orden?->folio,
                'folio_pedido' => $orden?->pedido?->folio,
                'tipo_proceso' => $orden?->tipo_proceso
                    ?? ($esFlange ? OrdenProduccion::TIPO_PROCESO_FLANGE : OrdenProduccion::TIPO_PROCESO_TUBO),
                'producto' => $detalle?->producto?->nombre,
                'sku' => $detalle?->producto?->sku,
                'maquina' => $orden?->maquina?->nombre ?: 'Sin máquina',
                'turno' => $orden?->turno?->nombre ?: 'Sin turno',
                'metros' => $esFlange ? 0.0 : (float) ($salida->metros ?? 0),
                'piezas' => $piezas,
                'piezas_buenas' => (int) ($salida->piezas_buenas ?? 0),
                'piezas_malas' => (int) ($salida->piezas_malas ?? 0),
                'kg_teorico' => (float) ($salida->kg_teorico ?? 0),
                'kg_real' => (float) ($salida->kg_real ?? 0),
                'kg_merma' => (float) ($salida->kg_merma ?? 0),
                'estatus_op' => $orden?->estatus_texto ?? $orden?->estatus,
                'orden_id' => $orden?->id,
                'salida_id' => $salida->id,
            ];
        });
    }

    private function nombreEmpleado(?object $empleado): string
    {
        if (!$empleado) {
            return 'Sin operador';
        }

        return trim(implode(' ', array_filter([
            $empleado->primer_nombre ?? null,
            $empleado->segundo_nombre ?? null,
            $empleado->apellido_paterno ?? null,
            $empleado->apellido_materno ?? null,
        ]))) ?: ('Empleado #' . ($empleado->id ?? ''));
    }

    private function obtenerProduccionEmpleadoMaquinaDetalle(
        string $fechaInicio,
        string $fechaFin,
        string $q,
        string $tipoProceso,
        ?int $maquinaId,
        ?int $operadorId
    ): Collection {
        $salidas = OrdenProduccionDetalleSalida::query()
            ->with([
                'detalle.producto:id,sku,nombre',
                'detalle.orden.pedido:id,folio',
                'detalle.orden.maquina:id,nombre,codigo',
                'detalle.orden.operador',
                'detalle.orden.etapasMaquina.maquina:id,nombre,codigo',
                'detalle.orden.etapasMaquina.operador',
            ])
            ->whereDate('created_at', '>=', $fechaInicio)
            ->whereDate('created_at', '<=', $fechaFin)
            ->whereHas('detalle.orden', function ($w) use ($tipoProceso, $maquinaId, $operadorId) {
                $w->where('estatus', '!=', OrdenProduccion::ESTATUS_CANCELADA);
                if (in_array($tipoProceso, ['TUBO', 'FLANGE', 'CONEXION'], true)) {
                    $w->where('tipo_proceso', $tipoProceso);
                }
                if ($maquinaId) {
                    $w->where(function ($x) use ($maquinaId) {
                        $x->where('maquina_id', $maquinaId)
                            ->orWhereHas('etapasMaquina', fn ($e) => $e->where('maquina_id', $maquinaId));
                    });
                }
                if ($operadorId) {
                    $w->where(function ($x) use ($operadorId) {
                        $x->where('operador_id', $operadorId)
                            ->orWhereHas('etapasMaquina', fn ($e) => $e->where('operador_id', $operadorId));
                    });
                }
            })
            ->orderByDesc('created_at')
            ->get();

        $detalle = $salidas->map(function (OrdenProduccionDetalleSalida $salida) {
            $linea = $salida->detalle;
            $orden = $linea?->orden;
            $esFlange = $orden?->esFlange() ?? false;

            // Preferir operador/máquina de la etapa más reciente; fallback a cabecera OP.
            $etapa = $orden?->etapasMaquina
                ?->sortByDesc(fn ($e) => $e->secuencia ?? 0)
                ->first();

            $maquina = $etapa?->maquina ?: $orden?->maquina;
            $operador = $etapa?->operador ?: $orden?->operador;

            $piezas = $esFlange
                ? ((int) ($salida->piezas_buenas ?? 0) + (int) ($salida->piezas_malas ?? 0))
                : (int) ($salida->piezas ?? 0);

            return [
                'fecha' => optional($salida->created_at)->format('Y-m-d H:i'),
                'folio_op' => $orden?->folio,
                'orden_id' => $orden?->id,
                'tipo_proceso' => $orden?->tipo_proceso
                    ?? ($esFlange ? OrdenProduccion::TIPO_PROCESO_FLANGE : OrdenProduccion::TIPO_PROCESO_TUBO),
                'producto' => $linea?->producto?->nombre,
                'operador_id' => $operador?->id,
                'empleado' => $this->nombreEmpleado($operador),
                'maquina_id' => $maquina?->id,
                'maquina' => $maquina?->nombre ?: 'Sin máquina',
                'maquina_codigo' => $maquina?->codigo,
                'metros' => $esFlange ? 0.0 : (float) ($salida->metros ?? 0),
                'piezas' => $piezas,
                'kg_teorico' => (float) ($salida->kg_teorico ?? 0),
                'kg_real' => (float) ($salida->kg_real ?? 0),
                'kg_merma' => (float) ($salida->kg_merma ?? 0),
            ];
        });

        if ($q !== '') {
            $detalle = $detalle->filter(function ($row) use ($q) {
                $haystack = strtoupper(implode(' ', array_filter([
                    $row['folio_op'] ?? null,
                    $row['empleado'] ?? null,
                    $row['maquina'] ?? null,
                    $row['maquina_codigo'] ?? null,
                    $row['producto'] ?? null,
                ])));

                return str_contains($haystack, strtoupper($q));
            })->values();
        }

        if ($maquinaId) {
            $detalle = $detalle->where('maquina_id', $maquinaId)->values();
        }
        if ($operadorId) {
            $detalle = $detalle->where('operador_id', $operadorId)->values();
        }

        return $detalle;
    }

    private function agregarProduccionEmpleadoMaquina(Collection $detalle): Collection
    {
        return $detalle
            ->groupBy(fn ($r) => ($r['operador_id'] ?: 0) . '|' . ($r['maquina_id'] ?: 0))
            ->map(function (Collection $g) {
                $first = $g->first();
                $kgTeorico = round((float) $g->sum('kg_teorico'), 3);
                $kgMerma = round((float) $g->sum('kg_merma'), 3);

                return [
                    'operador_id' => $first['operador_id'],
                    'empleado' => $first['empleado'],
                    'maquina_id' => $first['maquina_id'],
                    'maquina' => $first['maquina'],
                    'maquina_codigo' => $first['maquina_codigo'],
                    'salidas' => $g->count(),
                    'ops' => $g->pluck('orden_id')->unique()->filter()->count(),
                    'metros' => round((float) $g->sum('metros'), 3),
                    'piezas' => (int) $g->sum('piezas'),
                    'kg_teorico' => $kgTeorico,
                    'kg_real' => round((float) $g->sum('kg_real'), 3),
                    'kg_merma' => $kgMerma,
                    'porcentaje_merma' => $kgTeorico > 0
                        ? round(($kgMerma / $kgTeorico) * 100, 2)
                        : 0,
                ];
            })
            ->sortByDesc(fn ($r) => $r['metros'] + $r['piezas'] + $r['kg_real'])
            ->values();
    }

    private function graficasEmpleadoMaquina(Collection $filas): array
    {
        $topEmpleados = $filas
            ->groupBy('empleado')
            ->map(fn (Collection $g, $k) => [
                'label' => \Illuminate\Support\Str::limit((string) $k, 28),
                'metros' => round((float) $g->sum('metros'), 2),
                'piezas' => (int) $g->sum('piezas'),
                'kg_real' => round((float) $g->sum('kg_real'), 2),
            ])
            ->sortByDesc(fn ($x) => $x['metros'] + $x['piezas'])
            ->take(8)
            ->values();

        $topMaquinas = $filas
            ->groupBy('maquina')
            ->map(fn (Collection $g, $k) => [
                'label' => \Illuminate\Support\Str::limit((string) $k, 28),
                'metros' => round((float) $g->sum('metros'), 2),
                'piezas' => (int) $g->sum('piezas'),
                'kg_real' => round((float) $g->sum('kg_real'), 2),
            ])
            ->sortByDesc(fn ($x) => $x['metros'] + $x['piezas'])
            ->take(8)
            ->values();

        $pares = $filas->take(10)->map(fn ($r) => [
            'label' => \Illuminate\Support\Str::limit(
                ($r['empleado'] ?? '—') . ' · ' . ($r['maquina'] ?? '—'),
                40
            ),
            'metros' => (float) $r['metros'],
            'piezas' => (int) $r['piezas'],
            'kg_real' => (float) $r['kg_real'],
        ]);

        return [
            'empleados' => [
                'labels' => $topEmpleados->pluck('label')->all(),
                'metros' => $topEmpleados->pluck('metros')->all(),
                'piezas' => $topEmpleados->pluck('piezas')->all(),
            ],
            'maquinas' => [
                'labels' => $topMaquinas->pluck('label')->all(),
                'metros' => $topMaquinas->pluck('metros')->all(),
                'piezas' => $topMaquinas->pluck('piezas')->all(),
            ],
            'pares' => [
                'labels' => $pares->pluck('label')->all(),
                'metros' => $pares->pluck('metros')->all(),
                'piezas' => $pares->pluck('piezas')->all(),
                'kg_real' => $pares->pluck('kg_real')->all(),
            ],
        ];
    }

    private function obtenerCalidadRechazos(string $fechaInicio, string $fechaFin, string $q, string $resultado): Collection
    {
        $query = InspeccionCalidadOrdenTrabajo::query()
            ->with([
                'orden:id,folio,pedido_id,tipo_proceso',
                'orden.pedido:id,folio',
                'detalle.producto:id,sku,nombre',
                'auditor:id,name',
                'salida.reproceso:id,folio,salida_id',
                'salida:id,piezas_buenas,piezas_malas',
            ])
            ->where(function ($w) use ($fechaInicio, $fechaFin) {
                $w->whereBetween('inspeccionado_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
                    ->orWhere(function ($x) use ($fechaInicio, $fechaFin) {
                        $x->whereNull('inspeccionado_at')
                            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
                    });
            })
            ->orderByDesc('id');

        if (in_array($resultado, [
            InspeccionCalidadOrdenTrabajo::RESULTADO_ACEPTADO,
            InspeccionCalidadOrdenTrabajo::RESULTADO_RECHAZADO,
            InspeccionCalidadOrdenTrabajo::RESULTADO_PENDIENTE,
        ], true)) {
            $query->where('resultado', $resultado);
        }

        $inspecciones = $query->get();

        if ($q !== '') {
            $inspecciones = $inspecciones->filter(function (InspeccionCalidadOrdenTrabajo $insp) use ($q) {
                $haystack = strtoupper(implode(' ', array_filter([
                    $insp->orden?->folio,
                    $insp->orden?->pedido?->folio,
                    $insp->detalle?->producto?->sku,
                    $insp->detalle?->producto?->nombre,
                    $insp->resultado,
                    $insp->estatus_calidad,
                    $insp->salida?->reproceso?->folio,
                ])));

                return str_contains($haystack, strtoupper($q));
            })->values();
        }

        return $inspecciones->map(function (InspeccionCalidadOrdenTrabajo $insp) {
            $enRango = (bool) $insp->espesores_en_rango;
            $reproceso = $insp->salida?->reproceso;
            $resultadoCodigo = $insp->resultado ?: InspeccionCalidadOrdenTrabajo::RESULTADO_PENDIENTE;

            return [
                'fecha' => optional($insp->inspeccionado_at ?: $insp->created_at)->format('Y-m-d H:i'),
                'dia' => optional($insp->inspeccionado_at ?: $insp->created_at)->format('Y-m-d'),
                'folio_op' => $insp->orden?->folio,
                'tipo_proceso' => $insp->orden?->tipo_proceso ?? OrdenProduccion::TIPO_PROCESO_TUBO,
                'producto' => $insp->detalle?->producto?->nombre,
                'sku' => $insp->detalle?->producto?->sku,
                'resultado_codigo' => $resultadoCodigo,
                'resultado' => $insp->resultado_texto ?? $resultadoCodigo,
                'estatus' => $insp->estatus_calidad_texto ?? $insp->estatus_calidad,
                'espesores_en_rango' => $enRango,
                'en_rango_texto' => $enRango ? 'Sí' : 'No',
                'metros' => (float) ($insp->metros ?? 0),
                'piezas_buenas' => (int) ($insp->salida?->piezas_buenas ?? 0),
                'piezas_malas' => (int) ($insp->salida?->piezas_malas ?? 0),
                'kg_real' => (float) ($insp->kg_real ?? 0),
                'kg_merma' => (float) ($insp->kg_merma ?? 0),
                'tiene_reproceso' => (bool) $reproceso,
                'folio_reproceso' => $reproceso?->folio,
                'reproceso_id' => $reproceso?->id,
                'auditor' => $insp->auditor?->name,
                'observaciones' => $insp->observaciones,
                'orden_id' => $insp->orden_id,
                'inspeccion_id' => $insp->id,
            ];
        });
    }

    private function obtenerReprocesoResina(string $fechaInicio, string $fechaFin, string $q, string $estatus): Collection
    {
        $query = Reproceso::query()
            ->with([
                'orden:id,folio,pedido_id',
                'orden.pedido:id,folio',
                'detalle.producto:id,sku,nombre',
                'productoResina:id,sku,nombre',
                'maquina:id,nombre',
            ])
            ->where('estatus', '!=', Reproceso::ESTATUS_CANCELADA)
            ->where(function ($w) use ($fechaInicio, $fechaFin) {
                $w->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
                    ->orWhereBetween('stock_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
                    ->orWhereBetween('peletizado_fin_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            })
            ->orderByDesc('id');

        if ($estatus !== '' && array_key_exists($estatus, Reproceso::$estatuses)) {
            $query->where('estatus', $estatus);
        }

        $reprocesos = $query->get();

        if ($q !== '') {
            $reprocesos = $reprocesos->filter(function (Reproceso $r) use ($q) {
                $haystack = strtoupper(implode(' ', array_filter([
                    $r->folio,
                    $r->orden?->folio,
                    $r->detalle?->producto?->nombre,
                    $r->productoResina?->nombre,
                    $r->estatus,
                    $r->identificacion_resina,
                ])));

                return str_contains($haystack, strtoupper($q));
            })->values();
        }

        return $reprocesos->map(function (Reproceso $r) {
            $kgOrigen = (float) ($r->kg_pesado ?: $r->kg_reportado ?: $r->kg_estimado ?: 0);
            $kgResina = (float) ($r->kg_saca_resina ?: $r->kg_resina_sistema ?: $r->kg_produccion_reportado ?: 0);
            $rendimiento = $kgOrigen > 0 && $kgResina > 0
                ? round(($kgResina / $kgOrigen) * 100, 2)
                : null;

            return [
                'fecha' => optional($r->created_at)->format('Y-m-d H:i'),
                'dia' => optional($r->created_at)->format('Y-m-d'),
                'folio_reproceso' => $r->folio,
                'folio_op' => $r->orden?->folio,
                'origen' => $r->origen_texto,
                'estatus_codigo' => $r->estatus,
                'estatus' => $r->estatus_texto,
                'producto_origen' => $r->detalle?->producto?->nombre,
                'producto_resina' => $r->productoResina?->nombre
                    ?: ($r->identificacion_resina ?: $r->identificacion_material),
                'metros' => (float) ($r->metros ?? 0),
                'kg_origen' => $kgOrigen,
                'kg_triturado' => (float) ($r->kg_saca_triturada ?? 0),
                'kg_resina' => $kgResina,
                'rendimiento_pct' => $rendimiento,
                'maquina' => $r->maquina?->nombre,
                'cerrado' => $r->estaCerrada(),
                'orden_id' => $r->orden_id,
                'reproceso_id' => $r->id,
            ];
        });
    }

    private function graficasProduccionDiaria(Collection $filas): array
    {
        $porDia = $filas->groupBy('dia')->sortKeys()->map(fn (Collection $g) => [
            'metros' => round((float) $g->sum('metros'), 2),
            'piezas' => (int) $g->sum('piezas'),
            'kg_real' => round((float) $g->sum('kg_real'), 2),
        ]);

        $porMaquina = $filas->groupBy('maquina')->map(fn (Collection $g, $k) => [
            'label' => \Illuminate\Support\Str::limit((string) $k, 28),
            'metros' => round((float) $g->sum('metros'), 2),
            'piezas' => (int) $g->sum('piezas'),
        ])->sortByDesc(fn ($x) => $x['metros'] + $x['piezas'])->take(8)->values();

        $porTurno = $filas->groupBy('turno')->map(fn (Collection $g, $k) => [
            'label' => (string) $k,
            'metros' => round((float) $g->sum('metros'), 2),
            'piezas' => (int) $g->sum('piezas'),
        ])->values();

        return [
            'dias' => [
                'labels' => $porDia->keys()->values()->all(),
                'metros' => $porDia->pluck('metros')->values()->all(),
                'piezas' => $porDia->pluck('piezas')->values()->all(),
                'kg_real' => $porDia->pluck('kg_real')->values()->all(),
            ],
            'maquinas' => [
                'labels' => $porMaquina->pluck('label')->all(),
                'metros' => $porMaquina->pluck('metros')->all(),
                'piezas' => $porMaquina->pluck('piezas')->all(),
            ],
            'turnos' => [
                'labels' => $porTurno->pluck('label')->all(),
                'metros' => $porTurno->pluck('metros')->all(),
                'piezas' => $porTurno->pluck('piezas')->all(),
            ],
        ];
    }

    private function graficasCalidad(Collection $filas): array
    {
        $porResultado = [
            'Aceptadas' => $filas->where('resultado_codigo', InspeccionCalidadOrdenTrabajo::RESULTADO_ACEPTADO)->count(),
            'Rechazadas' => $filas->where('resultado_codigo', InspeccionCalidadOrdenTrabajo::RESULTADO_RECHAZADO)->count(),
            'Pendientes' => $filas->where('resultado_codigo', InspeccionCalidadOrdenTrabajo::RESULTADO_PENDIENTE)->count(),
        ];

        $porDia = $filas->groupBy('dia')->sortKeys()->map(fn (Collection $g) => [
            'aceptadas' => $g->where('resultado_codigo', InspeccionCalidadOrdenTrabajo::RESULTADO_ACEPTADO)->count(),
            'rechazadas' => $g->where('resultado_codigo', InspeccionCalidadOrdenTrabajo::RESULTADO_RECHAZADO)->count(),
        ]);

        return [
            'resultados' => [
                'labels' => array_keys($porResultado),
                'valores' => array_values($porResultado),
            ],
            'dias' => [
                'labels' => $porDia->keys()->values()->all(),
                'aceptadas' => $porDia->pluck('aceptadas')->values()->all(),
                'rechazadas' => $porDia->pluck('rechazadas')->values()->all(),
            ],
            'rango' => [
                'labels' => ['En rango', 'Fuera de rango'],
                'valores' => [
                    $filas->where('espesores_en_rango', true)->count(),
                    $filas->where('espesores_en_rango', false)->count(),
                ],
            ],
        ];
    }

    private function graficasReproceso(Collection $filas): array
    {
        $porDia = $filas->groupBy('dia')->sortKeys()->map(fn (Collection $g) => [
            'origen' => round((float) $g->sum('kg_origen'), 2),
            'resina' => round((float) $g->sum('kg_resina'), 2),
        ]);

        $porEstatus = $filas->groupBy('estatus')->map(fn (Collection $g, $k) => [
            'label' => \Illuminate\Support\Str::limit((string) $k, 30),
            'n' => $g->count(),
        ])->sortByDesc('n')->take(8)->values();

        return [
            'dias' => [
                'labels' => $porDia->keys()->values()->all(),
                'origen' => $porDia->pluck('origen')->values()->all(),
                'resina' => $porDia->pluck('resina')->values()->all(),
            ],
            'estatus' => [
                'labels' => $porEstatus->pluck('label')->all(),
                'valores' => $porEstatus->pluck('n')->all(),
            ],
            'balance' => [
                'labels' => ['Kg origen', 'Kg triturado', 'Kg resina'],
                'valores' => [
                    round((float) $filas->sum('kg_origen'), 2),
                    round((float) $filas->sum('kg_triturado'), 2),
                    round((float) $filas->sum('kg_resina'), 2),
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $filasTubo
     * @param  Collection<int, array<string, mixed>>  $filasResina
     * @param  Collection<int, array<string, mixed>>  $filasMateriales
     * @param  array<string, float>  $resumen
     * @return array<string, mixed>
     */
    private function construirDatosGraficas(
        Collection $filasTubo,
        Collection $filasResina,
        Collection $filasMateriales,
        array $resumen
    ): array {
        $porDia = $filasTubo
            ->groupBy(fn ($f) => substr((string) ($f['fecha'] ?? ''), 0, 10))
            ->filter(fn ($_, $dia) => $dia !== '')
            ->sortKeys()
            ->map(fn (Collection $g) => [
                'kg_merma' => round((float) $g->sum('kg_merma'), 2),
                'kg_teorico' => round((float) $g->sum('kg_teorico'), 2),
                'kg_real' => round((float) $g->sum('kg_real'), 2),
            ]);

        $topProductos = $filasTubo
            ->groupBy(fn ($f) => $f['sku'] ?: ($f['producto'] ?? 'Sin producto'))
            ->map(fn (Collection $g, $key) => [
                'label' => \Illuminate\Support\Str::limit((string) ($g->first()['producto'] ?? $key), 36),
                'kg_merma' => round((float) $g->sum('kg_merma'), 2),
            ])
            ->sortByDesc('kg_merma')
            ->take(8)
            ->values();

        $topMp = $filasMateriales
            ->groupBy(fn ($f) => $f['sku_mp'] ?: ($f['materia_prima'] ?? 'MP'))
            ->map(fn (Collection $g) => [
                'label' => \Illuminate\Support\Str::limit((string) ($g->first()['materia_prima'] ?? 'MP'), 32),
                'tomado' => round((float) $g->sum('tomado'), 2),
                'merma' => round((float) $g->sum('merma_estimada'), 2),
            ])
            ->sortByDesc('merma')
            ->take(8)
            ->values();

        $resinaPorDia = $filasResina
            ->groupBy(fn ($f) => substr((string) ($f['fecha'] ?? ''), 0, 10))
            ->filter(fn ($_, $dia) => $dia !== '')
            ->sortKeys()
            ->map(fn (Collection $g) => [
                'origen' => round((float) $g->sum('kg_pesado'), 2),
                'recuperada' => round((float) $g->sum('kg_recuperada'), 2),
            ]);

        return [
            'tubo_dias' => [
                'labels' => $porDia->keys()->values()->all(),
                'merma' => $porDia->pluck('kg_merma')->values()->all(),
                'teorico' => $porDia->pluck('kg_teorico')->values()->all(),
                'real' => $porDia->pluck('kg_real')->values()->all(),
            ],
            'tubo_productos' => [
                'labels' => $topProductos->pluck('label')->all(),
                'merma' => $topProductos->pluck('kg_merma')->all(),
            ],
            'tubo_vs' => [
                'labels' => ['Kg teórico', 'Kg real', 'Kg merma'],
                'valores' => [
                    round((float) ($resumen['tubo_kg_teorico'] ?? 0), 2),
                    round((float) $filasTubo->sum('kg_real'), 2),
                    round((float) ($resumen['tubo_kg_merma'] ?? 0), 2),
                ],
            ],
            'resina_dias' => [
                'labels' => $resinaPorDia->keys()->values()->all(),
                'origen' => $resinaPorDia->pluck('origen')->values()->all(),
                'recuperada' => $resinaPorDia->pluck('recuperada')->values()->all(),
            ],
            'resina_vs' => [
                'labels' => ['Kg origen', 'Kg recuperada', 'Pérdida'],
                'valores' => [
                    round((float) ($resumen['resina_kg_pesado'] ?? 0), 2),
                    round((float) ($resumen['resina_kg_recuperada'] ?? 0), 2),
                    round(max(
                        (float) ($resumen['resina_kg_pesado'] ?? 0) - (float) ($resumen['resina_kg_recuperada'] ?? 0),
                        0
                    ), 2),
                ],
            ],
            'materiales_mp' => [
                'labels' => $topMp->pluck('label')->all(),
                'tomado' => $topMp->pluck('tomado')->all(),
                'merma' => $topMp->pluck('merma')->all(),
            ],
            'materiales_vs' => [
                'labels' => ['Tomado', 'Merma estimada'],
                'valores' => [
                    round((float) ($resumen['materiales_tomados'] ?? 0), 2),
                    round((float) ($resumen['materiales_merma_kg'] ?? 0), 2),
                ],
            ],
        ];
    }

    /**
     * KPIs de mantenimiento correctivo por máquina.
     *
     * @return array{filas: Collection, resumen: array<string, mixed>, causaGlobal: ?array, graficas: array<string, mixed>}
     */
    private function obtenerKpisMantenimiento(
        string $fechaInicio,
        string $fechaFin,
        int $maquinaId,
        float $horasDia
    ): array {
        $dias = max(1, Carbon::parse($fechaInicio)->diffInDays(Carbon::parse($fechaFin)) + 1);
        $horasProgramadas = round($dias * $horasDia, 2);

        $maquinasQuery = Maquina::activas()->orderBy('codigo')->orderBy('nombre');
        if ($maquinaId > 0) {
            $maquinasQuery->where('id', $maquinaId);
        }
        $maquinas = $maquinasQuery->get(['id', 'codigo', 'nombre']);

        $ordenes = Mantenimiento::query()
            ->with(['causaFalla:id,clave,nombre'])
            ->where('tipo', 'CORRECTIVO')
            ->where('estatus', '!=', 'CANCELADO')
            ->whereRaw('COALESCE(fecha_reporte, fecha_programada) BETWEEN ? AND ?', [$fechaInicio, $fechaFin])
            ->when($maquinaId > 0, fn ($q) => $q->where('maquina_id', $maquinaId))
            ->get();

        $porMaquina = $ordenes->groupBy('maquina_id');

        $filas = $maquinas->map(function (Maquina $maquina) use ($porMaquina, $horasProgramadas) {
            $ots = $porMaquina->get($maquina->id, collect());
            $numFallas = $ots->count();
            $horasParo = round((float) $ots->sum(fn ($ot) => (float) ($ot->horas_paro ?? 0)), 2);
            $costo = round((float) $ots->sum(fn ($ot) => (float) ($ot->costo_total ?? 0)), 2);
            $mttr = $numFallas > 0 ? round($horasParo / $numFallas, 2) : 0.0;
            $horasOperacion = max(0, round($horasProgramadas - $horasParo, 2));
            $mtbf = $numFallas > 0 ? round($horasOperacion / $numFallas, 2) : null;
            $disponibilidad = $horasProgramadas > 0
                ? round(($horasOperacion / $horasProgramadas) * 100, 2)
                : 100.0;

            $causa = $ots
                ->filter(fn ($ot) => $ot->causaFalla)
                ->groupBy('causa_falla_id')
                ->map(function ($grupo) {
                    $c = $grupo->first()->causaFalla;

                    return [
                        'clave' => $c->clave,
                        'nombre' => $c->nombre,
                        'veces' => $grupo->count(),
                        'label' => $c->clave . ' — ' . $c->nombre,
                    ];
                })
                ->sortByDesc('veces')
                ->first();

            return [
                'maquina_id' => $maquina->id,
                'maquina' => $maquina->nombre,
                'codigo' => $maquina->codigo ?: '—',
                'num_fallas' => $numFallas,
                'horas_paro' => $horasParo,
                'costo_total' => $costo,
                'mttr' => $mttr,
                'mtbf' => $mtbf,
                'disponibilidad_pct' => $disponibilidad,
                'horas_programadas' => $horasProgramadas,
                'horas_operacion' => $horasOperacion,
                'causa_frecuente' => $causa['label'] ?? '—',
                'causa_clave' => $causa['clave'] ?? null,
            ];
        })->values();

        $causaGlobal = $ordenes
            ->filter(fn ($ot) => $ot->causaFalla)
            ->groupBy('causa_falla_id')
            ->map(function ($grupo) {
                $c = $grupo->first()->causaFalla;

                return [
                    'clave' => $c->clave,
                    'nombre' => $c->nombre,
                    'veces' => $grupo->count(),
                    'label' => $c->clave . ' — ' . $c->nombre,
                ];
            })
            ->sortByDesc('veces')
            ->first();

        $totalFallas = (int) $filas->sum('num_fallas');
        $totalParo = round((float) $filas->sum('horas_paro'), 2);
        $totalCosto = round((float) $filas->sum('costo_total'), 2);

        $resumen = [
            'maquinas' => $filas->count(),
            'fallas' => $totalFallas,
            'horas_paro' => $totalParo,
            'costo_total' => $totalCosto,
            'mttr' => $totalFallas > 0 ? round($totalParo / $totalFallas, 2) : 0.0,
            'dias' => $dias,
            'horas_programadas_dia' => $horasDia,
            'causa_frecuente' => $causaGlobal['label'] ?? '—',
        ];

        $topFallas = $filas->sortByDesc('num_fallas')->take(10)->values();
        $topParo = $filas->sortByDesc('horas_paro')->take(10)->values();
        $topCosto = $filas->sortByDesc('costo_total')->take(10)->values();

        $causasChart = $ordenes
            ->filter(fn ($ot) => $ot->causaFalla)
            ->groupBy(fn ($ot) => $ot->causaFalla->clave)
            ->map(fn ($g) => $g->count())
            ->sortDesc()
            ->take(8);

        $graficas = [
            'fallas' => [
                'labels' => $topFallas->map(fn ($f) => $f['codigo'] !== '—' ? $f['codigo'] : $f['maquina'])->all(),
                'valores' => $topFallas->pluck('num_fallas')->all(),
            ],
            'paro' => [
                'labels' => $topParo->map(fn ($f) => $f['codigo'] !== '—' ? $f['codigo'] : $f['maquina'])->all(),
                'valores' => $topParo->pluck('horas_paro')->all(),
            ],
            'costo' => [
                'labels' => $topCosto->map(fn ($f) => $f['codigo'] !== '—' ? $f['codigo'] : $f['maquina'])->all(),
                'valores' => $topCosto->pluck('costo_total')->all(),
            ],
            'causas' => [
                'labels' => $causasChart->keys()->values()->all(),
                'valores' => $causasChart->values()->all(),
            ],
        ];

        return [
            'filas' => $filas,
            'resumen' => $resumen,
            'causaGlobal' => $causaGlobal,
            'graficas' => $graficas,
        ];
    }

    /**
     * @return list<array{kpi: string, formula: string, como: string}>
     */
    private function definicionesKpisMantenimiento(): array
    {
        return [
            [
                'kpi' => 'Nº de fallas por máquina',
                'formula' => 'Conteo de órdenes correctivas por máquina',
                'como' => 'COUNT de órdenes (excluye canceladas)',
            ],
            [
                'kpi' => 'Horas de paro por máquina',
                'formula' => 'Suma de duración de paro',
                'como' => 'SUM de horas_paro',
            ],
            [
                'kpi' => 'MTTR',
                'formula' => 'Horas de paro ÷ Nº de fallas',
                'como' => 'Tiempo medio de reparación',
            ],
            [
                'kpi' => 'MTBF',
                'formula' => 'Horas de operación ÷ Nº de fallas',
                'como' => 'Operación ≈ horas programadas − paro (parámetro horas/día)',
            ],
            [
                'kpi' => 'Costo de mantenimiento por máquina',
                'formula' => 'Suma de costo total',
                'como' => 'SUM de costo_total (MO + refacciones)',
            ],
            [
                'kpi' => 'Causa de falla más frecuente',
                'formula' => 'Moda de causa (CF-xx)',
                'como' => 'Conteo por clave de causa',
            ],
            [
                'kpi' => 'Disponibilidad para OEE',
                'formula' => '(Tiempo programado − paro) ÷ Tiempo programado',
                'como' => 'Usa horas programadas/día del filtro × días del periodo',
            ],
        ];
    }

    private function obtenerFilasOee(string $fechaInicio, string $fechaFin, int $maquinaId, string $tipoProceso = ''): Collection
    {
        $regs = RegistroProduccion::query()
            ->with([
                'orden.maquina:id,codigo,nombre',
                'orden.turno:id,nombre',
            ])
            ->whereHas('orden', function ($q) use ($fechaInicio, $fechaFin, $maquinaId, $tipoProceso) {
                $q->whereDate('fecha', '>=', $fechaInicio)
                    ->whereDate('fecha', '<=', $fechaFin)
                    ->where('estatus', '!=', OrdenProduccion::ESTATUS_CANCELADA);
                if ($maquinaId > 0) {
                    $q->where('maquina_id', $maquinaId);
                }
                if (in_array($tipoProceso, ['TUBO', 'FLANGE', 'CONEXION'], true)) {
                    $q->where('tipo_proceso', $tipoProceso);
                }
            })
            ->orderByDesc('id')
            ->get();

        return $regs->map(function (RegistroProduccion $r) {
            $orden = $r->orden;

            return [
                'folio' => $orden?->folio ?? ('#' . $r->orden_id),
                'tipo_proceso' => $orden?->tipo_proceso ?? OrdenProduccion::TIPO_PROCESO_TUBO,
                'fecha' => $orden?->fecha?->format('Y-m-d') ?? '',
                'maquina' => $orden?->maquina
                    ? trim(($orden->maquina->codigo ? $orden->maquina->codigo . ' — ' : '') . $orden->maquina->nombre)
                    : '—',
                'turno' => $orden?->turno?->nombre ?? '—',
                't_programado_h' => $r->t_programado_h,
                't_paros_h' => $r->t_paros_h,
                't_operando_h' => $r->t_operando_h,
                'metros' => $r->metros,
                'piezas_buenas' => $r->piezas_buenas,
                'piezas_malas' => $r->piezas_malas,
                'prod_bueno_kg' => $r->prod_bueno_kg,
                'porcentaje_merma' => $r->porcentaje_merma,
                'disponibilidad_pct' => $r->disponibilidad_pct,
                'rendimiento_oee_pct' => $r->rendimiento_oee_pct,
                'calidad_pct' => $r->calidad_pct,
                'oee_pct' => $r->oee_pct,
                'costo_material' => $r->costo_material,
                'costo_unit_m' => $r->costo_unit_m,
                'costo_unit_pieza' => $r->costo_unit_pieza,
                'orden_id' => $orden?->id,
            ];
        })->values();
    }
}
