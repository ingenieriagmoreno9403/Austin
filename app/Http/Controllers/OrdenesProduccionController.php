<?php

namespace App\Http\Controllers;

use App\Models\CausaParo;
use App\Models\Clientes;
use App\Models\CostoPeadMensual;
use App\Models\InspeccionCalidadOrdenTrabajo;
use App\Models\Maquina;
use App\Models\MotivoCancelacionProduccion;
use App\Models\OrdenProduccion;
use App\Models\OrdenProduccionDetalle;
use App\Models\OrdenProduccionDetalleSalida;
use App\Models\OrdenProduccionMaquina;
use App\Models\ProcesoProduccionResponsable;
use App\Models\ProductoConexionEspecificacion;
use App\Models\ProductoFlangeEspecificacion;
use App\Models\ProductoTuboEspecificacion;
use App\Models\Productos;
use App\Models\Receta;
use App\Models\RegistroProduccion;
use App\Models\ReporteNoExistencia;
use App\Models\Turno;
use App\Models\TipoEmpaque;
use App\Models\Ubicaciones;
use App\Models\VentaPedido;
use App\Services\RegistroProduccionCalculator;
use App\Traits\AlmacenesTraits;
use App\Traits\MenuTrait;
use App\Traits\SistemasTraits;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrdenesProduccionController extends Controller
{
    use MenuTrait;
    use AlmacenesTraits;
    use SistemasTraits;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $filtros = $request->only([
            'fecha_desde', 'fecha_hasta', 'estatus', 'cliente_id',
            'maquina_id', 'operador_id', 'turno_id', 'pedido',
        ]);

        $turnoActual = $this->resolverTurnoActual();
        $maquinas = Maquina::where('estatus', 'A')->orderBy('nombre')->get();
        $empleados = $this->Listadoempleadosalmacen();
        $turnos = Turno::where('estatus', 'ACTIVO')->orderBy('hora_inicio')->get();

        $ordenesQuery = OrdenProduccion::with([
            'maquina', 'turno', 'operador', 'supervisor',
            'pedido.cliente', 'pedido.detalles', 'detalles.producto', 'etapasMaquina.maquina',
        ])->whereHas('detalles', function ($q) {
            // Solo órdenes que fabrican algo (no solo productos de existencia).
            $q->where('tipo_linea', 'FABRICAR');
        });

        $this->aplicarFiltrosOrdenesMesa($ordenesQuery, $filtros);

        $ordenes = $ordenesQuery
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $pedidosPendientesQuery = VentaPedido::with(['cliente', 'detalles.producto'])
            ->whereIn('estatus', ['EN_PRODUCCION', 'PENDIENTE_OC', 'CONFIRMADO'])
            ->whereDoesntHave('ordenProduccion');

        $this->restringirPedidosConProductoAProducir($pedidosPendientesQuery);
        $this->aplicarFiltrosPedidosPendientesMesa($pedidosPendientesQuery, $filtros);

        $pedidosPendientes = empty($filtros['estatus']) || $filtros['estatus'] === 'PENDIENTE_PROGRAMACION'
            ? $pedidosPendientesQuery->orderByDesc('fecha')->limit(50)->get()
            : collect();

        $filasMesa = $this->construirFilasMesa($ordenes, $pedidosPendientes);
        $kpis = $this->construirKpisMesa();

        $clientes = Clientes::query()
            ->whereIn('id', VentaPedido::query()->distinct()->pluck('cliente_id')->filter())
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $pedidosDisponibles = VentaPedido::with('cliente')
            ->whereIn('estatus', ['CONFIRMADO', 'EN_PRODUCCION', 'PENDIENTE_OC'])
            ->whereDoesntHave('ordenProduccion');
        $this->restringirPedidosConProductoAProducir($pedidosDisponibles);
        $pedidosDisponibles = $pedidosDisponibles
            ->orderByDesc('fecha')
            ->limit(30)
            ->get();

        $ordenesActivas = OrdenProduccion::with(['maquina', 'turno', 'operador', 'detalles'])
            ->whereIn('estatus', OrdenProduccion::ESTATUS_ACTIVAS)
            ->get()
            ->keyBy('maquina_id');

        $seguimientoMaquinas = $maquinas->map(function (Maquina $maquina) use ($ordenesActivas) {
            $orden = $ordenesActivas->get($maquina->id);

            return [
                'maquina' => $maquina,
                'orden' => $orden,
                'metros' => $orden ? $orden->total_metros_producidos : 0,
                'piezas' => $orden ? $orden->total_piezas_producidas : 0,
            ];
        });

        $abrirOrdenId = (int) $request->input('abrir_orden', 0);
        $puedeCancelar = $this->puedeCancelarOrdenes();
        $motivosCancelacion = MotivoCancelacionProduccion::activos()->get();

        return view('Produccion.ordenes', compact(
            'varpantallas',
            'varsubmenus',
            'turnoActual',
            'seguimientoMaquinas',
            'empleados',
            'turnos',
            'maquinas',
            'pedidosDisponibles',
            'filasMesa',
            'kpis',
            'filtros',
            'clientes',
            'abrirOrdenId',
            'puedeCancelar',
            'motivosCancelacion'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'maquina_id' => ['nullable', 'integer', 'exists:tbl_maquinas,id'],
            'turno_id' => ['required', 'integer', 'exists:tbl_turnos,id'],
            'operador_id' => ['nullable', 'integer', 'exists:tblempleados,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:tblempleados,id'],
            'pedido_id' => ['nullable', 'integer', 'exists:tbl_pedidos,id'],
            'observaciones' => ['nullable', 'string'],
        ]);

        if (!empty($validated['pedido_id']) && !empty($validated['maquina_id']) && $this->pedidoBloqueaAsignacionMaquina((int) $validated['pedido_id'])) {
            throw ValidationException::withMessages([
                'maquina_id' => 'No puede asignar máquina: este pedido tiene materiales pendientes de compra. Deje la máquina sin asignar.',
            ]);
        }

        if (!empty($validated['maquina_id'])) {
            $this->validarMaquinaDisponible((int) $validated['maquina_id']);
        }

        $orden = DB::transaction(function () use ($validated) {
            $estatusInicial = OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS;
            if (!empty($validated['pedido_id']) && $this->pedidoBloqueaAsignacionMaquina((int) $validated['pedido_id'])) {
                $estatusInicial = OrdenProduccion::ESTATUS_EN_ESPERA_MATERIALES;
            }

            $orden = OrdenProduccion::create([
                'folio' => $this->generarFolio(),
                'fecha' => $validated['fecha'],
                'pedido_id' => $validated['pedido_id'] ?? null,
                'maquina_id' => $validated['maquina_id'] ?? null,
                'turno_id' => $validated['turno_id'],
                'operador_id' => $validated['operador_id'] ?? null,
                'supervisor_id' => $validated['supervisor_id'] ?? null,
                'estatus' => $estatusInicial,
                'observaciones' => $validated['observaciones'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            if (!empty($validated['maquina_id'])) {
                OrdenProduccionMaquina::create([
                    'orden_id' => $orden->id,
                    'maquina_id' => (int) $validated['maquina_id'],
                    'secuencia' => 1,
                    'estatus' => $estatusInicial,
                    'operador_id' => $validated['operador_id'] ?? null,
                    'fecha_inicio' => now(),
                    'created_at' => now(),
                ]);
            }

            if (!empty($validated['pedido_id'])) {
                $this->copiarDetalleDesdePedido($orden, (int) $validated['pedido_id']);
                VentaPedido::where('id', $validated['pedido_id'])
                    ->where('estatus', 'CONFIRMADO')
                    ->update(['estatus' => 'EN_PRODUCCION', 'updated_at' => now()]);
            }

            return $orden;
        });

        return redirect()
            ->route('produccion.ordenes', ['abrir_orden' => $orden->id])
            ->with('success', 'Orden de producción registrada correctamente.');
    }

    public function panelDetalle(int $id): View
    {
        $orden = OrdenProduccion::with([
            'maquina',
            'turno',
            'operador',
            'supervisor',
            'pedido.cliente',
            'pedido.detalles.producto',
            'detalles.producto',
            'etapasMaquina.maquina',
        ])->findOrFail($id);

        $maquinas = Maquina::where('estatus', 'A')->orderBy('nombre')->get();
        $empleados = $this->Listadoempleadosalmacen();
        $turnos = Turno::where('estatus', 'ACTIVO')->orderBy('hora_inicio')->get();
        $editable = ! in_array($orden->estatus, OrdenProduccion::ESTATUS_FINALES, true);
        $bloqueaMaquina = $orden->pedido_id && $this->pedidoBloqueaAsignacionMaquina((int) $orden->pedido_id);

        return view('Produccion.partials.mesa_panel_detalle', compact(
            'orden',
            'maquinas',
            'empleados',
            'turnos',
            'editable',
            'bloqueaMaquina'
        ));
    }

    public function importarPedidosPendientes(Request $request): RedirectResponse
    {
        $turnoActual = $this->resolverTurnoActual();
        if (!$turnoActual) {
            return redirect()
                ->route('produccion.ordenes')
                ->with('warning', 'No hay turno activo configurado. Defina un turno antes de importar pedidos.');
        }

        $pedidosQuery = VentaPedido::whereIn('estatus', ['EN_PRODUCCION', 'PENDIENTE_OC', 'CONFIRMADO'])
            ->whereDoesntHave('ordenProduccion');
        $this->restringirPedidosConProductoAProducir($pedidosQuery);
        $pedidos = $pedidosQuery->orderBy('fecha')->get();

        $creadas = 0;
        DB::transaction(function () use ($pedidos, $turnoActual, &$creadas) {
            foreach ($pedidos as $pedido) {
                $this->crearOrdenDesdePedido($pedido, $turnoActual);
                if ($pedido->estatus === 'CONFIRMADO') {
                    $pedido->update(['estatus' => 'EN_PRODUCCION', 'updated_at' => now()]);
                }
                $creadas++;
            }
        });

        $mensaje = $creadas > 0
            ? "Se importaron {$creadas} pedido(s) pendiente(s) a órdenes de producción."
            : 'No había pedidos pendientes por importar.';

        return redirect()
            ->route('produccion.ordenes')
            ->with($creadas > 0 ? 'success' : 'warning', $mensaje);
    }

    public function detalle(int $id): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $orden = OrdenProduccion::with([
            'maquina',
            'turno',
            'operador',
            'supervisor',
            'pedido.cliente',
            'pedido.detalles',
            'ubicacionDestino',
            'detalles.producto',
            'detalles.salidas.ubicacionDestino',
            'detalles.salidas.inspeccion.auditor',
            'detalles.ubicacionDestino',
            'etapasMaquina.maquina',
            'etapasMaquina.operador',
            'registroProduccion',
            'paros.causaParo',
        ])->findOrFail($id);

        $maquinas = Maquina::where('estatus', 'A')->orderBy('nombre')->get();

        $productosTubo = Productos::query()
            ->whereIn('id', ProductoTuboEspecificacion::query()->select('producto_id')->distinct())
            ->orderBy('nombre')
            ->get(['id', 'sku', 'nombre']);

        $productosExistencia = Productos::query()
            ->whereNotIn('id', ProductoTuboEspecificacion::query()->select('producto_id')->distinct())
            ->orderBy('nombre')
            ->limit(800)
            ->get(['id', 'sku', 'nombre']);

        $empleados = $this->Listadoempleadosalmacen();
        $turnos = Turno::where('estatus', 'ACTIVO')->orderBy('hora_inicio')->get();
        $almacenes = $this->Listadoalmacenes();
        $ubicaciones = collect();
        foreach ($almacenes as $almacen) {
            $ubicaciones = $ubicaciones->merge($this->Listadoubicacionesxidalmacen($almacen->id));
        }
        $tiposUbicacion = $this->Listadotiposubi();

        $puedeCancelar = $this->puedeCancelarOrdenes();
        $motivosCancelacion = MotivoCancelacionProduccion::activos()->get();
        $responsablesProceso = ProcesoProduccionResponsable::mapaResponsables();
        $causasParo = CausaParo::activas()->get();
        $registroOee = $orden->registroProduccion;
        $costoPeadDefault = CostoPeadMensual::costoParaFecha($orden->fecha);
        $calcOee = app(RegistroProduccionCalculator::class);
        $metrosOee = $calcOee->metrosDesdeOrden($orden);
        $piezasOee = $calcOee->piezasDesdeOrden($orden);

        return view('Produccion.orden_detalle', compact(
            'varpantallas',
            'varsubmenus',
            'orden',
            'productosTubo',
            'productosExistencia',
            'empleados',
            'turnos',
            'almacenes',
            'ubicaciones',
            'tiposUbicacion',
            'maquinas',
            'puedeCancelar',
            'motivosCancelacion',
            'responsablesProceso',
            'causasParo',
            'registroOee',
            'costoPeadDefault',
            'metrosOee',
            'piezasOee'
        ));
    }

    public function actualizarCabecera(Request $request, int $id): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);
        $this->validarOrdenEditable($orden);

        $validated = $request->validate([
            'turno_id' => ['required', 'integer', 'exists:tbl_turnos,id'],
            'operador_id' => ['nullable', 'integer', 'exists:tblempleados,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:tblempleados,id'],
            'maquina_id' => ['nullable', 'integer', 'exists:tbl_maquinas,id'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        if (!empty($validated['maquina_id']) && $orden->pedido_id && $this->pedidoBloqueaAsignacionMaquina((int) $orden->pedido_id)) {
            throw ValidationException::withMessages([
                'maquina_id' => 'No puede asignar máquina mientras el pedido tenga materiales pendientes.',
            ]);
        }

        if (!empty($validated['maquina_id'])) {
            $this->validarMaquinaDisponible((int) $validated['maquina_id'], (int) $orden->id);
        }

        $orden->update(array_merge($validated, ['updated_at' => now()]));

        return redirect()
            ->route('produccion.ordenes', ['abrir_orden' => $orden->id])
            ->with('success', 'Programación de la orden actualizada.');
    }

    public function especificacionesTuboProducto(int $productoId): \Illuminate\Http\JsonResponse
    {
        Productos::findOrFail($productoId);

        $especificaciones = ProductoTuboEspecificacion::query()
            ->where('producto_id', $productoId)
            ->where('estatus', 'ACTIVO')
            ->orderByDesc('psi')
            ->get([
                'id',
                'psi',
                'rd',
                'diametro_nominal',
                'diametro_exterior_pulg',
                'espesor_pulg',
                'peso_kg_m',
            ]);

        return response()->json($especificaciones);
    }

    public function storeDetalle(Request $request, int $id): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);
        $this->validarOrdenEditable($orden);

        $validated = $request->validate($this->reglasDetalle());
        $tipoLinea = $validated['tipo_linea'] ?? $this->resolverTipoLinea((int) $validated['producto_id']);
        $validated['tipo_linea'] = $tipoLinea;

        if ($tipoLinea === 'FABRICAR') {
            $this->aplicarEspecificacionTubo($validated);
            $this->aplicarEspecificacionPieza($validated, $orden);
            $esTubo = ProductoTuboEspecificacion::where('producto_id', (int) $validated['producto_id'])
                ->where('estatus', 'ACTIVO')
                ->exists();
            if ($esTubo && empty($validated['psi'])) {
                throw ValidationException::withMessages([
                    'psi' => 'Seleccione el grado PSI para el tubo a fabricar.',
                ]);
            }
        } else {
            unset($validated['psi'], $validated['diametro'], $validated['rd'], $validated['espesor'], $validated['kg_metro']);
        }

        unset($validated['especificacion_id']);

        OrdenProduccionDetalle::create(array_merge($validated, [
            'orden_id' => $orden->id,
            'metros_producidos' => 0,
            'piezas_producidas' => 0,
            'created_at' => now(),
        ]));

        if ($orden->estatus === OrdenProduccion::ESTATUS_CALENTANDO) {
            $orden->update(['estatus' => OrdenProduccion::ESTATUS_EN_PRODUCCION, 'updated_at' => now()]);
        }

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', 'Línea de producción agregada.');
    }

    public function actualizarDetalle(Request $request, int $id, int $detalleId): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);
        $this->validarOrdenEditable($orden);

        $detalle = OrdenProduccionDetalle::where('orden_id', $orden->id)->findOrFail($detalleId);

        if ($detalle->esFabricar()) {
            return $this->actualizarPlanFabricar($request, $orden, $detalle);
        }

        return $this->actualizarExistencia($request, $orden, $detalle);
    }

    public function storeSalida(Request $request, int $id, int $detalleId): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);
        $this->validarOrdenEditable($orden);

        $detalle = OrdenProduccionDetalle::where('orden_id', $orden->id)->findOrFail($detalleId);

        if (!$detalle->esFabricar()) {
            throw ValidationException::withMessages([
                'salida' => 'Solo se registran salidas en líneas de fabricación.',
            ]);
        }

        $esFlange = $orden->esFlange();

        $rules = [
            'diametro_real' => ['nullable', 'string', 'max:50'],
            'rd_real' => ['nullable', 'string', 'max:50'],
            'espesor_real' => ['nullable', 'numeric', 'min:0'],
            'largo_tramo' => ['nullable', 'numeric', 'min:0'],
            'piezas' => ['nullable', 'integer', 'min:0'],
            'piezas_buenas' => ['nullable', 'integer', 'min:0'],
            'piezas_malas' => ['nullable', 'integer', 'min:0'],
            'kg_retrabajo' => ['nullable', 'numeric', 'min:0'],
            'ubicacion_destino_id' => ['nullable', 'integer', 'exists:tblubicaciones,id'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];

        if ($esFlange) {
            $rules['metros'] = ['nullable', 'numeric', 'min:0'];
            $rules['kg_real'] = ['nullable', 'numeric', 'min:0'];
            $rules['piezas_buenas'] = ['required', 'integer', 'min:0'];
            $rules['piezas_malas'] = ['nullable', 'integer', 'min:0'];
        } else {
            $rules['metros'] = ['required', 'numeric', 'min:0.001'];
            $rules['kg_real'] = ['required', 'numeric', 'min:0'];
        }

        $validated = $request->validate($rules);

        $metros = (float) ($validated['metros'] ?? 0);
        $kgReal = (float) ($validated['kg_real'] ?? 0);
        $piezasBuenas = (int) ($validated['piezas_buenas'] ?? 0);
        $piezasMalas = (int) ($validated['piezas_malas'] ?? 0);
        $piezas = $esFlange
            ? ($piezasBuenas + $piezasMalas)
            : (int) ($validated['piezas'] ?? 0);

        $orden->loadMissing(['pedido.detalles']);

        if ($esFlange) {
            $this->validarMedidasSalidaFlange($orden, $detalle, $piezasBuenas, $piezasMalas);
        } else {
            $this->validarMedidasSalidaTubo($orden, $detalle, $metros, $kgReal);
        }

        $kgTeorico = $esFlange
            ? round(((float) ($detalle->kg_metro ?? 0)) * max($piezasBuenas, 1), 3)
            : $this->calcularKgTeorico($metros, (float) ($detalle->kg_metro ?? 0));
        $kgMerma = null;
        $porcentajeMerma = null;

        if (!$esFlange && $kgTeorico > 0) {
            $kgMerma = max(round($kgTeorico - $kgReal, 3), 0);
            $porcentajeMerma = round(($kgMerma / $kgTeorico) * 100, 3);
        }

        OrdenProduccionDetalleSalida::create([
            'orden_detalle_id' => $detalle->id,
            'diametro_real' => $validated['diametro_real'] ?? $detalle->diametro,
            'rd_real' => $validated['rd_real'] ?? $detalle->rd,
            'espesor_real' => $validated['espesor_real'] ?? null,
            'metros' => $esFlange ? 0 : $metros,
            'largo_tramo' => $validated['largo_tramo'] ?? null,
            'piezas' => $piezas,
            'piezas_buenas' => $esFlange ? $piezasBuenas : null,
            'piezas_malas' => $esFlange ? $piezasMalas : null,
            'kg_real' => $kgReal > 0 ? $kgReal : null,
            'kg_teorico' => $kgTeorico > 0 ? $kgTeorico : null,
            'kg_merma' => $kgMerma,
            'kg_retrabajo' => $validated['kg_retrabajo'] ?? null,
            'porcentaje_merma' => $porcentajeMerma,
            'ubicacion_destino_id' => $validated['ubicacion_destino_id'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'created_at' => now(),
        ]);

        $detalle->recalcularTotalesDesdeSalidas();

        if (in_array($orden->estatus, [
            OrdenProduccion::ESTATUS_CALENTANDO,
            OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS,
        ], true)) {
            $orden->update(['estatus' => OrdenProduccion::ESTATUS_EN_PRODUCCION, 'updated_at' => now()]);
        }

        $registroOeeExistente = RegistroProduccion::where('orden_id', $orden->id)->first();
        if ($registroOeeExistente) {
            app(RegistroProduccionCalculator::class)->recalcularYGuardar(
                $orden->fresh(['maquina', 'detalles.salidas']),
                $registroOeeExistente
            );
        }

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', $esFlange
                ? ($orden->esConexion()
                    ? 'Avance de conexiones registrado (' . $piezasBuenas . ' buenas / ' . $piezasMalas . ' malas).'
                    : 'Avance de inyección flange registrado (' . $piezasBuenas . ' buenas / ' . $piezasMalas . ' malas).')
                : 'Salida de producción registrada.');
    }

    /**
     * Inspección de calidad según procedimiento:
     * 8 espesores (±4%) → co-extrusora → tatuadora → aceptado / rechazado.
     */
    public function inspeccionarSalida(Request $request, int $id, int $salidaId): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);

        $salida = OrdenProduccionDetalleSalida::with('detalle')
            ->whereHas('detalle', fn ($q) => $q->where('orden_id', $orden->id))
            ->findOrFail($salidaId);

        $detalle = $salida->detalle;
        $accion = $request->input('accion', 'evaluar_espesores');

        // Flange: aceptación/rechazo simplificado (sin 8 espesores / tatuadora).
        if ($orden->esFlange() && in_array($accion, ['aceptar', 'rechazar', 'aceptar_con_detalle'], true)) {
            return $this->inspeccionarSalidaFlange($request, $orden, $salida, $detalle, $accion);
        }

        $validated = $request->validate([
            'accion' => ['nullable', 'string', Rule::in([
                'evaluar_espesores',
                'co_extrusora',
                'tatuadora',
                'aceptar',
                'aceptar_con_detalle',
                'rechazar',
            ])],
            'espesor_1' => ['nullable', 'numeric', 'min:0'],
            'espesor_2' => ['nullable', 'numeric', 'min:0'],
            'espesor_3' => ['nullable', 'numeric', 'min:0'],
            'espesor_4' => ['nullable', 'numeric', 'min:0'],
            'espesor_5' => ['nullable', 'numeric', 'min:0'],
            'espesor_6' => ['nullable', 'numeric', 'min:0'],
            'espesor_7' => ['nullable', 'numeric', 'min:0'],
            'espesor_8' => ['nullable', 'numeric', 'min:0'],
            'color_linea' => ['nullable', 'string', Rule::in(array_keys(InspeccionCalidadOrdenTrabajo::$coloresLinea))],
            'co_extrusora_ok' => ['nullable', 'boolean'],
            'tatuadora_ok' => ['nullable', 'boolean'],
            'tatuaje_diametro' => ['nullable', 'string', 'max:50'],
            'tatuaje_rd' => ['nullable', 'string', 'max:50'],
            'tatuaje_lote' => ['nullable', 'string', 'max:100'],
            'tatuaje_fecha' => ['nullable', 'date'],
            'tatuaje_hora' => ['nullable', 'string', 'max:10'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $nominal = (float) ($detalle->espesor ?? $salida->espesor_real ?? 0);
        $rango = InspeccionCalidadOrdenTrabajo::rangoEspesor($nominal > 0 ? $nominal : null);

        $medidas = [];
        for ($i = 1; $i <= 8; $i++) {
            $key = 'espesor_' . $i;
            $medidas[$i] = isset($validated[$key]) && $validated[$key] !== null && $validated[$key] !== ''
                ? (float) $validated[$key]
                : null;
        }

        $base = [
            'orden_id' => $orden->id,
            'orden_detalle_id' => $salida->orden_detalle_id,
            'diametro_real' => $salida->diametro_real,
            'rd_real' => $salida->rd_real,
            'espesor_real' => $salida->espesor_real,
            'metros' => $salida->metros,
            'kg_real' => $salida->kg_real,
            'kg_teorico' => $salida->kg_teorico,
            'kg_merma' => $salida->kg_merma,
            'porcentaje_merma' => $salida->porcentaje_merma,
            'espesor_nominal' => $nominal > 0 ? $nominal : null,
            'espesor_min' => $rango['min'] ?? null,
            'espesor_max' => $rango['max'] ?? null,
            'auditor_id' => auth()->id(),
            'inspeccionado_at' => now(),
            'observaciones' => $validated['observaciones'] ?? null,
        ];

        for ($i = 1; $i <= 8; $i++) {
            $base['espesor_' . $i] = $medidas[$i];
        }

        if ($accion === 'rechazar') {
            $base['resultado'] = InspeccionCalidadOrdenTrabajo::RESULTADO_RECHAZADO;
            $base['estatus_calidad'] = InspeccionCalidadOrdenTrabajo::ESTATUS_RECHAZADO;
            $base['espesores_en_rango'] = false;

            InspeccionCalidadOrdenTrabajo::updateOrCreate(['salida_id' => $salida->id], $base);

            $inspeccion = InspeccionCalidadOrdenTrabajo::where('salida_id', $salida->id)->first();
            $lote = null;
            if ($inspeccion) {
                try {
                    $lote = \App\Models\Reproceso::crearDesdeRechazoCalidad($salida->fresh(), $inspeccion, $orden);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $this->cerrarOrdenPorRechazoCalidad($orden, $validated['observaciones'] ?? null);

            $msg = 'OP rechazada por calidad. Proceso terminado → inventario de merma (RECHAZADOS POR CALIDAD).';
            if ($lote) {
                $msg .= ' Lote ' . $lote->folio . '.';
            }

            return redirect()
                ->route('produccion.ordenes.detalle', $orden->id)
                ->with('success', $msg)
                ->with('reproceso_id', $lote?->id);
        }

        if ($accion !== 'aceptar_con_detalle' && ($nominal <= 0 || !$rango)) {
            throw ValidationException::withMessages([
                'espesor' => 'La línea no tiene espesor nominal de plan. Configure el espesor en la especificación del tubo.',
            ]);
        }

        if (in_array($accion, ['evaluar_espesores', 'co_extrusora', 'tatuadora', 'aceptar', 'aceptar_con_detalle'], true)) {
            foreach ($medidas as $idx => $valor) {
                if ($valor === null || $valor <= 0) {
                    throw ValidationException::withMessages([
                        'espesor_' . $idx => 'Capture los 8 espesores con vernier calibrado.',
                    ]);
                }
            }
        }

        $enRango = ($rango !== null)
            ? InspeccionCalidadOrdenTrabajo::espesoresDentroDeRango(
                array_values($medidas),
                (float) $rango['min'],
                (float) $rango['max']
            )
            : false;
        $base['espesores_en_rango'] = $enRango;

        $inspActual = InspeccionCalidadOrdenTrabajo::where('salida_id', $salida->id)->first();

        // Aceptación excepcional: pasa aunque esté fuera de tolerancia (requiere detalle).
        if ($accion === 'aceptar_con_detalle') {
            $obs = trim((string) ($validated['observaciones'] ?? ''));
            if ($obs === '') {
                throw ValidationException::withMessages([
                    'observaciones' => 'Capture el detalle / motivo para aceptar aunque no cumpla la especificación.',
                ]);
            }

            $prefijo = $enRango
                ? '[ACEPTADO CON DETALLE] '
                : '[ACEPTADO CON DETALLE · fuera de rango ±'
                    . number_format(InspeccionCalidadOrdenTrabajo::TOLERANCIA_ERROR_PCT, 0)
                    . '%] ';

            $base['observaciones'] = str_starts_with($obs, '[ACEPTADO CON DETALLE')
                ? $obs
                : ($prefijo . $obs);
            $base['resultado'] = InspeccionCalidadOrdenTrabajo::RESULTADO_ACEPTADO;
            $base['estatus_calidad'] = InspeccionCalidadOrdenTrabajo::ESTATUS_ACEPTADO;
            $base['color_linea'] = $validated['color_linea']
                ?? $inspActual?->color_linea;
            $base['tatuaje_diametro'] = $validated['tatuaje_diametro']
                ?? $inspActual?->tatuaje_diametro
                ?? $salida->diametro_real
                ?? $detalle->diametro;
            $base['tatuaje_rd'] = $validated['tatuaje_rd']
                ?? $inspActual?->tatuaje_rd
                ?? $salida->rd_real
                ?? $detalle->rd;
            $base['tatuaje_lote'] = $validated['tatuaje_lote'] ?? $inspActual?->tatuaje_lote;
            $base['tatuaje_fecha'] = $validated['tatuaje_fecha']
                ?? ($inspActual?->tatuaje_fecha?->format('Y-m-d'))
                ?? now()->toDateString();
            $base['tatuaje_hora'] = $validated['tatuaje_hora']
                ?? $inspActual?->tatuaje_hora
                ?? now()->format('H:i');
            $base['co_extrusora_ok'] = (bool) ($inspActual?->co_extrusora_ok || !empty($base['color_linea']));
            $base['tatuadora_ok'] = (bool) ($inspActual?->tatuadora_ok || !empty($base['tatuaje_lote']));

            InspeccionCalidadOrdenTrabajo::updateOrCreate(['salida_id' => $salida->id], $base);

            if (in_array($orden->estatus, [
                OrdenProduccion::ESTATUS_EN_PRODUCCION,
                OrdenProduccion::ESTATUS_REVISION_CALIDAD,
                OrdenProduccion::ESTATUS_CALENTANDO,
                OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS,
            ], true)) {
                $this->avanzarTrasCalidadAceptada($orden);
            }

            $msg = $enRango
                ? 'Salida aceptada con detalle.'
                : 'Salida aceptada con detalle aunque los espesores estén fuera de rango.';

            return redirect()
                ->route($orden->esFlange() ? 'produccion.ordenes.detalle' : 'produccion.empaque.detalle', $orden->id)
                ->with($orden->esFlange() ? 'success' : 'warning', $msg . ($orden->esFlange() ? '' : ' Continúe en Empaque.'));
        }

        if ($nominal <= 0 || !$rango) {
            throw ValidationException::withMessages([
                'espesor' => 'La línea no tiene espesor nominal de plan. Configure el espesor en la especificación del tubo.',
            ]);
        }

        if (!$enRango) {
            $base['resultado'] = InspeccionCalidadOrdenTrabajo::RESULTADO_PENDIENTE;
            $base['estatus_calidad'] = InspeccionCalidadOrdenTrabajo::ESTATUS_AJUSTAR;
            $base['co_extrusora_ok'] = false;
            $base['tatuadora_ok'] = false;

            InspeccionCalidadOrdenTrabajo::updateOrCreate(['salida_id' => $salida->id], $base);
            $this->marcarOrdenEnRevisionCalidad($orden);

            return redirect()
                ->route('produccion.ordenes.detalle', $orden->id)
                ->with('warning', 'Espesores fuera de rango (±'
                    . number_format(InspeccionCalidadOrdenTrabajo::TOLERANCIA_ERROR_PCT, 0)
                    . '%). Ajuste parámetros de extrusor y jalador y vuelva a medir los 8 puntos. Si debe pasar de todos modos, use «Aceptado con detalle» e indique el motivo.');
        }

        // Espesores OK
        $base['color_linea'] = $validated['color_linea']
            ?? $inspActual?->color_linea;
        $base['tatuaje_diametro'] = $validated['tatuaje_diametro']
            ?? $inspActual?->tatuaje_diametro
            ?? $salida->diametro_real
            ?? $detalle->diametro;
        $base['tatuaje_rd'] = $validated['tatuaje_rd']
            ?? $inspActual?->tatuaje_rd
            ?? $salida->rd_real
            ?? $detalle->rd;
        $base['tatuaje_lote'] = $validated['tatuaje_lote'] ?? $inspActual?->tatuaje_lote;
        $base['tatuaje_fecha'] = $validated['tatuaje_fecha']
            ?? ($inspActual?->tatuaje_fecha?->format('Y-m-d'))
            ?? now()->toDateString();
        $base['tatuaje_hora'] = $validated['tatuaje_hora']
            ?? $inspActual?->tatuaje_hora
            ?? now()->format('H:i');

        if ($accion === 'evaluar_espesores') {
            $base['resultado'] = InspeccionCalidadOrdenTrabajo::RESULTADO_PENDIENTE;
            $base['estatus_calidad'] = InspeccionCalidadOrdenTrabajo::ESTATUS_CO_EXTRUSORA;
            $base['co_extrusora_ok'] = false;
            $base['tatuadora_ok'] = false;

            InspeccionCalidadOrdenTrabajo::updateOrCreate(['salida_id' => $salida->id], $base);
            $this->marcarOrdenEnRevisionCalidad($orden);

            return redirect()
                ->route('produccion.ordenes.detalle', $orden->id)
                ->with('success', 'Los 8 espesores están dentro de rango. Siguiente: encender co-extrusora (línea de identificación).');
        }

        if ($accion === 'co_extrusora') {
            if (empty($base['color_linea'])) {
                throw ValidationException::withMessages([
                    'color_linea' => 'Seleccione el color de línea (azul, amarillo, morado o rojo).',
                ]);
            }

            $base['resultado'] = InspeccionCalidadOrdenTrabajo::RESULTADO_PENDIENTE;
            $base['estatus_calidad'] = InspeccionCalidadOrdenTrabajo::ESTATUS_TATUADORA;
            $base['co_extrusora_ok'] = true;
            $base['tatuadora_ok'] = false;

            InspeccionCalidadOrdenTrabajo::updateOrCreate(['salida_id' => $salida->id], $base);
            $this->marcarOrdenEnRevisionCalidad($orden);

            return redirect()
                ->route('produccion.ordenes.detalle', $orden->id)
                ->with('success', 'Co-extrusora registrada (color ' . ($base['color_linea']) . '). Siguiente: encender tatuadora y verificar leyenda.');
        }

        if ($accion === 'tatuadora' || $accion === 'aceptar') {
            if (empty($base['color_linea'])) {
                throw ValidationException::withMessages([
                    'color_linea' => 'Falta el color de co-extrusora.',
                ]);
            }
            if (empty($base['tatuaje_lote'])) {
                throw ValidationException::withMessages([
                    'tatuaje_lote' => 'Capture el lote de la leyenda de tatuadora.',
                ]);
            }

            $base['resultado'] = InspeccionCalidadOrdenTrabajo::RESULTADO_ACEPTADO;
            $base['estatus_calidad'] = InspeccionCalidadOrdenTrabajo::ESTATUS_ACEPTADO;
            $base['co_extrusora_ok'] = true;
            $base['tatuadora_ok'] = true;

            InspeccionCalidadOrdenTrabajo::updateOrCreate(['salida_id' => $salida->id], $base);

            // Si la calidad pasó, continúa: flange → ruta; tubo → empaque.
            if (in_array($orden->estatus, [
                OrdenProduccion::ESTATUS_EN_PRODUCCION,
                OrdenProduccion::ESTATUS_REVISION_CALIDAD,
                OrdenProduccion::ESTATUS_CALENTANDO,
                OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS,
            ], true)) {
                $this->avanzarTrasCalidadAceptada($orden);
            }

            if ($orden->esFlange()) {
                $msgCalidad = $orden->esConexion()
                    ? 'Conexiones aceptadas en calidad. Continúe con acabado (torno/corte si aplica).'
                    : 'Flange aceptado en calidad. Continúe con torno/corte según diámetro.';

                return redirect()
                    ->route('produccion.ordenes.detalle', $orden->id)
                    ->with('success', $msgCalidad);
            }

            return redirect()
                ->route('produccion.empaque.detalle', $orden->id)
                ->with('success', 'Inspección de calidad ACEPTADA. Tatuadora OK (Ø ' . $base['tatuaje_diametro']
                    . ' · RD ' . $base['tatuaje_rd'] . ' · Lote ' . $base['tatuaje_lote']
                    . '). Continúe en el módulo Empaque (rollo o tramos).');
        }

        throw ValidationException::withMessages([
            'accion' => 'Acción de inspección no válida.',
        ]);
    }

    /**
     * Calidad simplificada para OP flange (piezas inyectadas).
     */
    private function inspeccionarSalidaFlange(
        Request $request,
        OrdenProduccion $orden,
        OrdenProduccionDetalleSalida $salida,
        OrdenProduccionDetalle $detalle,
        string $accion
    ): RedirectResponse {
        $validated = $request->validate([
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'piezas_buenas' => ['nullable', 'integer', 'min:0'],
            'piezas_malas' => ['nullable', 'integer', 'min:0'],
            'kg_retrabajo' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (isset($validated['piezas_buenas']) || isset($validated['piezas_malas']) || isset($validated['kg_retrabajo'])) {
            $salida->piezas_buenas = $validated['piezas_buenas'] ?? $salida->piezas_buenas;
            $salida->piezas_malas = $validated['piezas_malas'] ?? $salida->piezas_malas;
            $salida->kg_retrabajo = $validated['kg_retrabajo'] ?? $salida->kg_retrabajo;
            $salida->piezas = (int) ($salida->piezas_buenas ?? 0) + (int) ($salida->piezas_malas ?? 0);
            $salida->save();
            $detalle->recalcularTotalesDesdeSalidas();
        }

        $base = [
            'orden_id' => $orden->id,
            'orden_detalle_id' => $salida->orden_detalle_id,
            'diametro_real' => $salida->diametro_real ?? $detalle->diametro,
            'rd_real' => $salida->rd_real ?? $detalle->rd,
            'metros' => $salida->metros,
            'kg_real' => $salida->kg_real,
            'auditor_id' => auth()->id(),
            'inspeccionado_at' => now(),
            'observaciones' => $validated['observaciones'] ?? null,
        ];

        if ($accion === 'rechazar') {
            $base['resultado'] = InspeccionCalidadOrdenTrabajo::RESULTADO_RECHAZADO;
            $base['estatus_calidad'] = InspeccionCalidadOrdenTrabajo::ESTATUS_RECHAZADO;
            $base['espesores_en_rango'] = false;
            InspeccionCalidadOrdenTrabajo::updateOrCreate(['salida_id' => $salida->id], $base);

            $lote = null;
            try {
                $insp = InspeccionCalidadOrdenTrabajo::where('salida_id', $salida->id)->first();
                if ($insp) {
                    $lote = \App\Models\Reproceso::crearDesdeRechazoCalidad($salida->fresh(), $insp, $orden);
                }
            } catch (\Throwable $e) {
                report($e);
            }

            $this->cerrarOrdenPorRechazoCalidad($orden, $validated['observaciones'] ?? null);

            $tipoTxt = $orden->esConexion() ? 'Conexión' : 'Flange';
            $msg = $tipoTxt . ' rechazado por calidad. Proceso terminado → inventario de merma (RECHAZADOS POR CALIDAD).';
            if ($lote) {
                $msg .= ' Lote ' . $lote->folio . '.';
            }

            return redirect()
                ->route('produccion.ordenes.detalle', $orden->id)
                ->with('success', $msg)
                ->with('reproceso_id', $lote?->id);
        }

        $base['resultado'] = InspeccionCalidadOrdenTrabajo::RESULTADO_ACEPTADO;
        $base['estatus_calidad'] = InspeccionCalidadOrdenTrabajo::ESTATUS_ACEPTADO;
        $base['espesores_en_rango'] = true;
        if ($accion === 'aceptar_con_detalle') {
            $detalleObs = trim((string) ($validated['observaciones'] ?? ''));
            $base['observaciones'] = 'ACEPTADO CON DETALLE'
                . ($detalleObs !== '' ? ': ' . $detalleObs : '');
        }
        InspeccionCalidadOrdenTrabajo::updateOrCreate(['salida_id' => $salida->id], $base);

        if (in_array($orden->estatus, [
            OrdenProduccion::ESTATUS_EN_PRODUCCION,
            OrdenProduccion::ESTATUS_REVISION_CALIDAD,
            OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS,
            OrdenProduccion::ESTATUS_CALIDAD_FINAL,
        ], true)) {
            if ($orden->estatus === OrdenProduccion::ESTATUS_CALIDAD_FINAL) {
                $orden->update([
                    'estatus' => OrdenProduccion::ESTATUS_ENTREGA_ALMACEN,
                    'updated_at' => now(),
                ]);
            } else {
                $this->avanzarTrasCalidadAceptada($orden);
            }
        }

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', $orden->esConexion() ? 'Calidad de conexiones aceptada.' : 'Calidad flange aceptada.');
    }

    private function avanzarTrasCalidadAceptada(OrdenProduccion $orden): void
    {
        if ($orden->esFlange()) {
            if (empty($orden->ruta_flange)) {
                $det = $orden->detalles()->where('tipo_linea', 'FABRICAR')->first();
                $orden->ruta_flange = ProductoFlangeEspecificacion::resolverRuta(
                    $det?->diametro,
                    $det?->rd
                );
            }
            $siguiente = match ($orden->ruta_flange) {
                OrdenProduccion::RUTA_FLANGE_TORNO_EXTERNO => OrdenProduccion::ESTATUS_RUTA_TORNO_EXTERNO,
                OrdenProduccion::RUTA_FLANGE_CORTE => OrdenProduccion::ESTATUS_RUTA_CORTE,
                default => OrdenProduccion::ESTATUS_RUTA_TORNO,
            };
            $orden->update([
                'ruta_flange' => $orden->ruta_flange,
                'estatus' => $siguiente,
                'updated_at' => now(),
            ]);

            return;
        }

        $orden->update([
            'estatus' => OrdenProduccion::ESTATUS_PREPARAR_EMPAQUE,
            'updated_at' => now(),
        ]);
    }

    private function marcarOrdenEnRevisionCalidad(OrdenProduccion $orden): void
    {
        if (in_array($orden->estatus, [
            OrdenProduccion::ESTATUS_EN_PRODUCCION,
            OrdenProduccion::ESTATUS_CALENTANDO,
            OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS,
        ], true)) {
            $orden->update([
                'estatus' => OrdenProduccion::ESTATUS_REVISION_CALIDAD,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Cierra la OP por rechazo de calidad: termina el proceso y deja el material en merma.
     */
    private function cerrarOrdenPorRechazoCalidad(OrdenProduccion $orden, ?string $nota = null): void
    {
        if ($orden->estatus === OrdenProduccion::ESTATUS_RECHAZADA_POR_CALIDAD) {
            return;
        }

        $obs = trim((string) ($orden->observaciones ?? ''));
        $linea = '[' . now()->format('d/m/Y H:i') . '] Rechazada por calidad → inventario de merma (RECHAZADOS POR CALIDAD).';
        $nota = trim((string) ($nota ?? ''));
        if ($nota !== '') {
            $linea .= ' Motivo: ' . $nota;
        }

        $orden->update([
            'estatus' => OrdenProduccion::ESTATUS_RECHAZADA_POR_CALIDAD,
            'observaciones' => trim($obs !== '' ? ($obs . "\n" . $linea) : $linea),
            'updated_at' => now(),
        ]);
    }

    /**
     * Avanza el flujo de empaque (punto D del procedimiento) tras calidad OK.
     */
    public function avanzarEmpaque(Request $request, int $id): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);
        $this->validarOrdenEditable($orden);

        $validated = $request->validate([
            'accion' => ['required', 'string', Rule::in([
                'preparar',
                'enrollar',
                'longitud',
                'cortar_flejar',
                'terminar',
            ])],
            'tipo_empaque' => ['nullable', 'string', Rule::in(array_keys(TipoEmpaque::opcionesActivas() ?: OrdenProduccion::$tiposEmpaque))],
            'longitud_objetivo_m' => ['nullable', 'numeric', 'min:0.001'],
            'ubicacion_destino_id' => ['nullable', 'integer', 'exists:tblubicaciones,id'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $accion = $validated['accion'];
        $datos = ['updated_at' => now()];

        if (!empty($validated['tipo_empaque'])) {
            $datos['tipo_empaque'] = $validated['tipo_empaque'];
        }
        if (isset($validated['longitud_objetivo_m'])) {
            $datos['longitud_objetivo_m'] = $validated['longitud_objetivo_m'];
        }
        if (!empty($validated['ubicacion_destino_id'])) {
            $ubi = Ubicaciones::find((int) $validated['ubicacion_destino_id']);
            if (!$ubi) {
                throw ValidationException::withMessages([
                    'ubicacion_destino_id' => 'La ubicación de almacén no existe.',
                ]);
            }
            $datos['ubicacion_destino_id'] = (int) $ubi->id;
            $datos['nave_destino'] = trim(($ubi->folio_interno ?? '') . ($ubi->descripcion ? ' — ' . $ubi->descripcion : ''));
        }
        if (!empty($validated['observaciones'])) {
            $datos['observaciones'] = trim(
                ($orden->observaciones ? $orden->observaciones . "\n" : '')
                . '[' . now()->format('d/m H:i') . '] ' . $validated['observaciones']
            );
        }

        $tipo = $datos['tipo_empaque'] ?? $orden->tipo_empaque;
        $requiereEnrollar = TipoEmpaque::requiereEnrollar($tipo);
        $etiquetaTipo = TipoEmpaque::etiqueta($tipo);

        $mensaje = match ($accion) {
            'preparar' => (function () use (&$datos, $tipo, $requiereEnrollar, $etiquetaTipo) {
                if (empty($tipo)) {
                    throw ValidationException::withMessages([
                        'tipo_empaque' => 'Seleccione el tipo de empaque.',
                    ]);
                }
                // Con enrollador → ENROLLANDO; sin → directo a longitud.
                $datos['estatus'] = $requiereEnrollar
                    ? OrdenProduccion::ESTATUS_ENROLLANDO
                    : OrdenProduccion::ESTATUS_A_LONGITUD;

                return $requiereEnrollar
                    ? 'Empaque preparado (' . $etiquetaTipo . '). Siguiente: sujetar tubería al enrollador.'
                    : 'Empaque preparado (' . $etiquetaTipo . '). Siguiente: esperar longitud requerida.';
            })(),
            'enrollar' => (function () use (&$datos, $requiereEnrollar) {
                if (!$requiereEnrollar) {
                    throw ValidationException::withMessages([
                        'tipo_empaque' => 'Enrollar solo aplica cuando el tipo de empaque requiere enrollador.',
                    ]);
                }
                $datos['estatus'] = OrdenProduccion::ESTATUS_A_LONGITUD;

                return 'Tubería sujeta al enrollador. Espere la longitud requerida.';
            })(),
            'longitud' => (function () use (&$datos, $validated, $orden) {
                $largo = $validated['longitud_objetivo_m'] ?? $orden->longitud_objetivo_m;
                if (empty($largo) || (float) $largo <= 0) {
                    throw ValidationException::withMessages([
                        'longitud_objetivo_m' => 'Indique la longitud requerida (metros).',
                    ]);
                }
                $datos['longitud_objetivo_m'] = $largo;
                $datos['estatus'] = OrdenProduccion::ESTATUS_CORTAR_FLEJAR;

                return 'Longitud alcanzada (' . number_format((float) $largo, 2) . ' m). Siguiente: cortar y flejar.';
            })(),
            'cortar_flejar' => (function () use (&$datos) {
                $datos['estatus'] = OrdenProduccion::ESTATUS_CORTAR_FLEJAR;

                return 'Corte y flejado listos. Seleccione ubicación de almacén e indique «Almacenar / Terminar».';
            })(),
            'terminar' => (function () use (&$datos, $validated, $orden) {
                $ubicacionId = (int) ($validated['ubicacion_destino_id'] ?? $orden->ubicacion_destino_id ?? 0);
                if ($ubicacionId <= 0) {
                    throw ValidationException::withMessages([
                        'ubicacion_destino_id' => 'Seleccione la ubicación de almacén (nave) donde se almacenará el producto.',
                    ]);
                }

                $ubi = Ubicaciones::find($ubicacionId);
                if (!$ubi) {
                    throw ValidationException::withMessages([
                        'ubicacion_destino_id' => 'La ubicación de almacén no existe.',
                    ]);
                }

                $etiqueta = trim(($ubi->folio_interno ?? '') . ($ubi->descripcion ? ' — ' . $ubi->descripcion : ''));
                $datos['ubicacion_destino_id'] = $ubicacionId;
                $datos['nave_destino'] = $etiqueta !== '' ? $etiqueta : ('Ubicación #' . $ubicacionId);
                $datos['estatus'] = OrdenProduccion::ESTATUS_TERMINADA;

                // Al terminar producción, el pedido queda listo para planificar carga en Almacén.
                if ($orden->pedido_id) {
                    VentaPedido::where('id', $orden->pedido_id)
                        ->whereNotIn('estatus', ['LISTO_PARA_CARGA', 'CARGA_AVISADA', 'DESPACHADO'])
                        ->update(['estatus' => 'LISTO_PARA_CARGA', 'updated_at' => now()]);
                }

                return 'Orden terminada. Producto enviado a ' . $datos['nave_destino']
                    . '. Ya aparece en Cargas (Almacén) para acomodar en el camión.';
            })(),
            default => 'Estatus de empaque actualizado.',
        };

        // Si está en calidad / empaque legado, permitir arrancar empaque.
        if ($accion === 'preparar' && !in_array($orden->estatus, array_merge(
            OrdenProduccion::ESTATUS_EMPAQUE,
            [OrdenProduccion::ESTATUS_REVISION_CALIDAD, OrdenProduccion::ESTATUS_EMPACANDO, OrdenProduccion::ESTATUS_EN_PRODUCCION]
        ), true)) {
            throw ValidationException::withMessages([
                'estatus' => 'La orden aún no está en etapa de empaque/calidad.',
            ]);
        }

        $orden->update($datos);

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', $mensaje);
    }

    /**
     * Alta rápida de ubicación de almacén (nave) desde el detalle de la OP.
     */
    public function storeUbicacionAlmacen(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);

        $validated = $request->validate([
            'id_almacen' => ['required', 'integer', 'exists:tblalmacenes,id'],
            'folio_interno' => ['required', 'string', 'min:2', 'max:100'],
            'descripcion' => ['required', 'string', 'min:2', 'max:100'],
            'id_tipo_ubicacion' => ['required', 'integer', 'exists:tbltipos_ubicaciones,id'],
            'capacidad' => ['nullable', 'numeric', 'min:0'],
            'nivel' => ['nullable', 'string', 'max:10'],
            'observaciones' => ['nullable', 'string', 'max:100'],
        ]);

        $ubi = new Ubicaciones();
        $ubi->id_almacen = (int) $validated['id_almacen'];
        $ubi->folio_interno = trim($validated['folio_interno']);
        $ubi->descripcion = trim($validated['descripcion']);
        $ubi->id_tipo_ubicacion = (int) $validated['id_tipo_ubicacion'];
        $ubi->capacidad = $validated['capacidad'] ?? null;
        $ubi->nivel = $validated['nivel'] ?? null;
        $ubi->observaciones = $validated['observaciones'] ?? null;
        $ubi->espacio = 0;
        $ubi->ubicacion = 0;
        $ubi->created_at = now();
        $ubi->save();

        $etiqueta = trim($ubi->folio_interno . ' — ' . $ubi->descripcion);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'ubicacion' => [
                    'id' => (int) $ubi->id,
                    'id_almacen' => (int) $ubi->id_almacen,
                    'folio_interno' => $ubi->folio_interno,
                    'descripcion' => $ubi->descripcion,
                    'etiqueta' => $etiqueta,
                ],
            ]);
        }

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', 'Ubicación ' . $etiqueta . ' creada. Selecciónela como nave destino.');
    }

    private function actualizarPlanFabricar(Request $request, OrdenProduccion $orden, OrdenProduccionDetalle $detalle): RedirectResponse
    {
        $validated = $request->validate([
            'especificacion_id' => ['nullable', 'integer', 'exists:tbl_producto_tubo_especificaciones,id'],
            'diametro' => ['nullable', 'string', 'max:50'],
            'rd' => ['nullable', 'string', 'max:50'],
            'psi' => ['nullable', 'numeric', 'min:0'],
            'espesor' => ['nullable', 'numeric', 'min:0'],
            'kg_metro' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $validated['producto_id'] = $detalle->producto_id;
        $this->aplicarEspecificacionTubo($validated);
        unset($validated['especificacion_id']);

        $detalle->update($validated);

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', 'Especificación nominal actualizada.');
    }

    private function actualizarExistencia(Request $request, OrdenProduccion $orden, OrdenProduccionDetalle $detalle): RedirectResponse
    {
        $validated = $request->validate([
            'piezas_producidas' => ['required', 'integer', 'min:0'],
            'ubicacion_destino_id' => ['nullable', 'integer', 'exists:tblubicaciones,id'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $detalle->update($validated);

        if ($orden->estatus === OrdenProduccion::ESTATUS_CALENTANDO) {
            $orden->update(['estatus' => OrdenProduccion::ESTATUS_EN_PRODUCCION, 'updated_at' => now()]);
        }

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', 'Envío a embalaje registrado.');
    }

    public function cambiarEstatus(Request $request, int $id): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);

        $validated = $request->validate([
            'estatus' => ['required', Rule::in(array_keys(OrdenProduccion::$estatusFlujo))],
            'origen' => ['nullable', 'string', Rule::in(['mesa', 'detalle'])],
        ]);

        $origen = $validated['origen'] ?? 'detalle';

        if (in_array($orden->estatus, OrdenProduccion::ESTATUS_FINALES, true)) {
            return $this->redirigirTrasEstatus($orden, $origen)
                ->with('warning', 'Esta orden ya está terminada o cancelada.');
        }

        $destino = $validated['estatus'];

        // Sin cambios: no hacer nada.
        if ($destino === $orden->estatus) {
            return $this->redirigirTrasEstatus($orden, $origen)
                ->with('warning', 'La orden ya está en ese estatus.');
        }

        // Cancelar tiene su propio flujo con permiso y motivo obligatorio.
        if ($destino === OrdenProduccion::ESTATUS_CANCELADA) {
            throw ValidationException::withMessages([
                'estatus' => 'La cancelación se realiza con el botón «Cancelar orden» (requiere permiso y motivo).',
            ]);
        }

        // Pausar: solo desde extrusión.
        if ($destino === OrdenProduccion::ESTATUS_PAUSADA) {
            if ($orden->estatus !== OrdenProduccion::ESTATUS_EN_PRODUCCION) {
                throw ValidationException::withMessages([
                    'estatus' => 'Solo puede pausar una orden que está en extrusión.',
                ]);
            }

            $orden->update(['estatus' => $destino, 'updated_at' => now()]);

            return $this->redirigirTrasEstatus($orden, $origen)
                ->with('success', 'Orden pausada.');
        }

        // Avance normal: únicamente al paso inmediato siguiente (sin retroceder ni brincar).
        $permitido = $orden->siguienteEstatus();

        if ($permitido === null || $destino !== $permitido) {
            throw ValidationException::withMessages([
                'estatus' => 'Solo puede avanzar al siguiente paso del flujo. No es posible regresar a un estatus anterior ni saltarse pasos.',
            ]);
        }

        // Flange: cierre al llegar a TERMINADA desde entrega a almacén.
        if ($orden->esFlange() && $destino === OrdenProduccion::ESTATUS_TERMINADA) {
            $extra = $request->validate([
                'ubicacion_destino_id' => ['nullable', 'integer', 'exists:tblubicaciones,id'],
            ]);
            $data = ['estatus' => $destino, 'updated_at' => now()];
            if (!empty($extra['ubicacion_destino_id'])) {
                $ubicacion = Ubicaciones::find($extra['ubicacion_destino_id']);
                $data['ubicacion_destino_id'] = $ubicacion?->id;
                $data['nave_destino'] = $ubicacion?->folio_interno;
            }
            $orden->update($data);
            if ($orden->pedido_id) {
                VentaPedido::where('id', $orden->pedido_id)->update([
                    'estatus' => 'LISTO_PARA_CARGA',
                    'updated_at' => now(),
                ]);
            }

            return $this->redirigirTrasEstatus($orden, $origen)
                ->with('success', ($orden->esConexion() ? 'OP de conexiones' : 'OP flange') . ' terminada · piezas liberadas a almacén.');
        }

        if ($destino === OrdenProduccion::ESTATUS_EN_PRODUCCION) {
            $this->validarMaterialesParaIniciarProduccion($orden);
        }

        $orden->update([
            'estatus' => $destino,
            'updated_at' => now(),
        ]);

        return $this->redirigirTrasEstatus($orden, $origen)
            ->with('success', 'Estatus de la orden actualizado a "' . ($orden->estatus_texto) . '".');
    }

    private function redirigirTrasEstatus(OrdenProduccion $orden, string $origen): RedirectResponse
    {
        if ($origen === 'mesa') {
            return redirect()->route('produccion.ordenes', ['abrir_orden' => $orden->id]);
        }

        return redirect()->route('produccion.ordenes.detalle', $orden->id);
    }

    /**
     * ¿El usuario tiene permiso para cancelar órdenes de producción?
     */
    private function puedeCancelarOrdenes(): bool
    {
        return $this->forpermisos('cancelar_orden_produccion') === 'cancelar_orden_produccion';
    }

    /**
     * Cancela una orden de producción: exige permiso y motivo, reintegra la MP
     * a la ubicación «MATERIAL REGRESADO», libera la máquina y ajusta el pedido.
     */
    public function cancelar(Request $request, int $id): RedirectResponse
    {
        if (!$this->puedeCancelarOrdenes()) {
            abort(403, 'No tiene permiso para cancelar órdenes de producción.');
        }

        $orden = OrdenProduccion::with(['pedido', 'detalles.producto'])->findOrFail($id);

        $validated = $request->validate([
            'motivo_cancelacion_id' => ['required', 'integer', 'exists:tbl_motivos_cancelacion_produccion,id'],
            'motivo_cancelacion_nota' => ['nullable', 'string', 'max:500'],
            'origen' => ['nullable', 'string', Rule::in(['mesa', 'detalle'])],
        ]);

        $origen = $validated['origen'] ?? 'detalle';

        if ($orden->estatus === OrdenProduccion::ESTATUS_CANCELADA) {
            return $this->redirigirTrasEstatus($orden, $origen)
                ->with('warning', 'Esta orden ya estaba cancelada.');
        }

        if ($orden->estatus === OrdenProduccion::ESTATUS_TERMINADA) {
            throw ValidationException::withMessages([
                'motivo_cancelacion_id' => 'No se puede cancelar una orden ya terminada.',
            ]);
        }

        $motivo = MotivoCancelacionProduccion::findOrFail($validated['motivo_cancelacion_id']);

        if ($motivo->requiere_nota && trim((string) ($validated['motivo_cancelacion_nota'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'motivo_cancelacion_nota' => 'Este motivo requiere una nota que explique la cancelación.',
            ]);
        }

        $reintegros = [];

        DB::transaction(function () use ($orden, $motivo, $validated, &$reintegros) {
            // 1) Reintegrar materiales (si ya se habían tomado) a MATERIAL REGRESADO.
            if (!empty($orden->materiales_tomados_at)) {
                $reintegros = $this->reintegrarMaterialesAMaterialRegresado($orden);
            }

            // 2) Liberar la máquina: cerrar etapas activas de la orden.
            OrdenProduccionMaquina::where('orden_id', $orden->id)
                ->whereNotIn('estatus', OrdenProduccion::ESTATUS_FINALES)
                ->update([
                    'estatus' => OrdenProduccion::ESTATUS_CANCELADA,
                    'fecha_fin' => now(),
                    'updated_at' => now(),
                ]);

            // 3) Pedido ligado en producción → regresar a CONFIRMADO para poder recrear otra OP.
            if ($orden->pedido_id) {
                VentaPedido::where('id', $orden->pedido_id)
                    ->where('estatus', 'EN_PRODUCCION')
                    ->update(['estatus' => 'CONFIRMADO', 'updated_at' => now()]);
            }

            // 4) Cerrar la orden con trazabilidad.
            $notaReintegro = empty($reintegros)
                ? ''
                : ' · Reintegrado a MATERIAL REGRESADO: ' . implode('; ', $reintegros);

            $orden->update([
                'estatus' => OrdenProduccion::ESTATUS_CANCELADA,
                'motivo_cancelacion_id' => $motivo->id,
                'motivo_cancelacion_nota' => $validated['motivo_cancelacion_nota'] ?? null,
                'cancelada_por' => auth()->id(),
                'cancelada_at' => now(),
                'observaciones' => trim(
                    ($orden->observaciones ? $orden->observaciones . "\n" : '')
                    . '[' . now()->format('d/m/Y H:i') . '] Cancelada: ' . $motivo->nombre
                    . (!empty($validated['motivo_cancelacion_nota']) ? ' · ' . $validated['motivo_cancelacion_nota'] : '')
                    . $notaReintegro
                ),
                'updated_at' => now(),
            ]);
        });

        $mensaje = 'Orden ' . $orden->folio . ' cancelada (' . $motivo->nombre . ').';
        if (!empty($reintegros)) {
            $mensaje .= ' Materia prima reintegrada a la ubicación MATERIAL REGRESADO: ' . implode('; ', $reintegros)
                . '. Trasládela a su ubicación definitiva desde Inventarios.';
        }

        return $this->redirigirTrasEstatus($orden, $origen)->with('success', $mensaje);
    }

    /**
     * Reintegra las materias primas descontadas de la OP a la ubicación
     * «MATERIAL REGRESADO» del almacén general, con su movimiento de entrada.
     *
     * @return array<int, string> Descripción legible de lo reintegrado.
     */
    private function reintegrarMaterialesAMaterialRegresado(OrdenProduccion $orden): array
    {
        $almacen = $this->obtenerAlmacenGeneralProduccion();
        if (!$almacen) {
            throw ValidationException::withMessages([
                'almacen' => 'No se encontró el almacén general para reintegrar materiales.',
            ]);
        }

        $ubicacion = $this->obtenerUbicacionMaterialRegresado((int) $almacen->id);
        if (!$ubicacion) {
            throw ValidationException::withMessages([
                'ubicacion' => 'No existe la ubicación MATERIAL REGRESADO en el almacén general.',
            ]);
        }

        $resumen = $this->obtenerResumenMaterialesOrden($orden);
        if (empty($resumen['materiales'])) {
            return [];
        }

        $tipoReintegro = (int) (DB::table('tbltipos_movimientos_inventario')
            ->where('nombre_movimiento', 'Reintegro por cancelación')
            ->value('id') ?: 0);
        $usuarioId = (int) (auth()->id() ?? 0);
        $fecha = now()->format('Y-m-d');
        $descripciones = [];

        foreach ($resumen['materiales'] as $mp) {
            $productoId = (int) $mp['producto_mp_id'];
            $cantidad = round((float) $mp['requerido'], 2);
            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            $existencia = DB::table('tblexistencias')
                ->where('id_almacen', $almacen->id)
                ->where('id_producto', $productoId)
                ->where('id_ubicacion', $ubicacion->id)
                ->first();

            if ($existencia) {
                DB::table('tblexistencias')
                    ->where('id', $existencia->id)
                    ->update([
                        'cantidad_existente' => round((float) $existencia->cantidad_existente + $cantidad, 2),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('tblexistencias')->insert([
                    'id_producto' => $productoId,
                    'id_almacen' => $almacen->id,
                    'id_ubicacion' => $ubicacion->id,
                    'cantidad_existente' => $cantidad,
                    'cantidad_reservada' => 0,
                    'id_estado_movinv' => 2,
                    'productos_arecibir' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($tipoReintegro > 0 && $usuarioId > 0) {
                DB::table('tblmovimientos_inventario')->insert([
                    'id_producto' => $productoId,
                    'id_almacen' => $almacen->id,
                    'id_ubicacion' => $ubicacion->id,
                    'id_tipo_movimiento' => $tipoReintegro,
                    'cantidad_producto_movimiento' => (int) max(1, (int) round($cantidad)),
                    'fecha_movimiento' => $fecha,
                    'documento_referencia' => Str::limit((string) $orden->folio, 200, ''),
                    'observaciones' => 'Reintegro por cancelación OP ' . $orden->folio . ' · ' . ($mp['nombre'] ?? ('MP #' . $productoId))
                        . ' · cant. ' . number_format($cantidad, 2),
                    'id_estado_movinv' => 2,
                    'usuario_movimiento' => $usuarioId,
                    'created_at' => now(),
                ]);
            }

            $descripciones[] = ($mp['nombre'] ?? ('MP #' . $productoId))
                . ': ' . number_format($cantidad, 2) . ($mp['unidad'] ? ' ' . $mp['unidad'] : '');
        }

        return $descripciones;
    }

    private function obtenerUbicacionMaterialRegresado(int $almacenId): ?object
    {
        return DB::table('tblubicaciones')
            ->where('id_almacen', $almacenId)
            ->whereRaw('UPPER(folio_interno) = ?', ['MATERIAL REGRESADO'])
            ->first();
    }

    /**
     * Agrega una máquina (etapa) por la que pasa la orden. Cada etapa inicia en
     * "Calentando máquina".
     */
    public function agregarEtapaMaquina(Request $request, int $id): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);
        $this->validarOrdenEditable($orden);

        $validated = $request->validate([
            'maquina_id' => ['required', 'integer', 'exists:tbl_maquinas,id'],
            'operador_id' => ['nullable', 'integer', 'exists:tblempleados,id'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $this->validarMaquinaDisponible((int) $validated['maquina_id'], $orden->id);

        $secuencia = (int) OrdenProduccionMaquina::where('orden_id', $orden->id)->max('secuencia') + 1;

        OrdenProduccionMaquina::create([
            'orden_id' => $orden->id,
            'maquina_id' => (int) $validated['maquina_id'],
            'secuencia' => $secuencia,
            'estatus' => OrdenProduccion::ESTATUS_CALENTANDO,
            'operador_id' => $validated['operador_id'] ?? null,
            'fecha_inicio' => now(),
            'observaciones' => $validated['observaciones'] ?? null,
            'created_at' => now(),
        ]);

        // La máquina actual de la orden pasa a ser la recién agregada.
        $orden->update(['maquina_id' => (int) $validated['maquina_id'], 'updated_at' => now()]);

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', 'Máquina agregada a la ruta de la orden (etapa ' . $secuencia . ').');
    }

    /**
     * Cambia el estatus de una etapa (paso por máquina) de la orden.
     */
    public function cambiarEstatusEtapa(Request $request, int $id, int $etapaId): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);

        $validated = $request->validate([
            'estatus' => ['required', Rule::in(array_keys(OrdenProduccion::$estatusFlujo))],
        ]);

        $etapa = OrdenProduccionMaquina::where('orden_id', $orden->id)->findOrFail($etapaId);

        if ($validated['estatus'] === OrdenProduccion::ESTATUS_EN_PRODUCCION) {
            $this->validarMaterialesParaIniciarProduccion($orden);
        }

        $datos = ['estatus' => $validated['estatus'], 'updated_at' => now()];

        if (in_array($validated['estatus'], OrdenProduccion::ESTATUS_FINALES, true) && $etapa->fecha_fin === null) {
            $datos['fecha_fin'] = now();
        } elseif (! in_array($validated['estatus'], OrdenProduccion::ESTATUS_FINALES, true) && $etapa->fecha_inicio === null) {
            $datos['fecha_inicio'] = now();
        }

        $etapa->update($datos);

        // Reflejar el avance en la orden si sigue activa y la etapa no es final.
        if ($orden->esActiva() && ! in_array($validated['estatus'], OrdenProduccion::ESTATUS_FINALES, true)) {
            $orden->update([
                'estatus' => $validated['estatus'],
                'maquina_id' => $etapa->maquina_id,
                'updated_at' => now(),
            ]);
        }

        return redirect()
            ->route('produccion.ordenes.detalle', $orden->id)
            ->with('success', 'Estatus de la máquina actualizado.');
    }

    /**
     * Devuelve la lista de materiales (receta) con stock para una OP en espera.
     */
    public function materialesOrden(int $id): JsonResponse
    {
        $orden = OrdenProduccion::with(['pedido.cliente', 'pedido.detalles', 'detalles.producto'])->findOrFail($id);
        $resumen = $this->obtenerResumenMaterialesOrden($orden);

        return response()->json([
            'success' => true,
            'orden_id' => $orden->id,
            'folio' => $orden->folio,
            'pedido' => $orden->pedido?->folio,
            'cliente' => $orden->pedido?->cliente?->nombre,
            'estatus' => $orden->estatus,
            'productos' => $resumen['productos'],
            'materiales' => $resumen['materiales'],
            'puede_tomar' => $resumen['puede_tomar'],
            'materiales_ya_tomados' => !empty($orden->materiales_tomados_at),
            'tiene_orden_materiales' => !empty($orden->orden_materiales_ruta),
        ]);
    }

    /**
     * Toma materiales del inventario, registra movimientos, sube la orden de materiales
     * y pasa la OP a PREPARANDO_MAQUINAS.
     */
    public function ingresarMateriales(Request $request, int $id): RedirectResponse
    {
        $orden = OrdenProduccion::with(['pedido.detalles', 'detalles.producto'])->findOrFail($id);

        if ($orden->estatus !== OrdenProduccion::ESTATUS_EN_ESPERA_MATERIALES) {
            throw ValidationException::withMessages([
                'estatus' => 'Solo se pueden ingresar materiales cuando la orden está en espera de materiales.',
            ]);
        }

        if (!empty($orden->materiales_tomados_at)) {
            throw ValidationException::withMessages([
                'materiales' => 'Los materiales de esta orden ya fueron tomados del inventario.',
            ]);
        }

        $validated = $request->validate([
            'orden_materiales' => ['required', 'file', 'max:15360', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $resumen = $this->obtenerResumenMaterialesOrden($orden);

        if (empty($resumen['materiales'])) {
            throw ValidationException::withMessages([
                'materiales' => 'No se encontró receta/materiales para los productos de esta orden.',
            ]);
        }

        if (!$resumen['puede_tomar']) {
            $faltantes = collect($resumen['materiales'])
                ->filter(fn ($m) => !$m['suficiente'])
                ->map(fn ($m) => $m['nombre'] . ' (falta ' . number_format($m['faltante'], 2) . ')')
                ->implode('; ');

            throw ValidationException::withMessages([
                'materiales' => 'Aún no hay existencia suficiente para fabricar: ' . $faltantes,
            ]);
        }

        $almacenGeneral = $this->obtenerAlmacenGeneralProduccion();
        if (!$almacenGeneral) {
            throw ValidationException::withMessages([
                'almacen' => 'No se encontró el almacén general para descontar materias primas.',
            ]);
        }

        $archivo = $this->guardarOrdenMaterialesArchivo(
            $request->file('orden_materiales'),
            (int) $orden->id,
            $orden->orden_materiales_ruta
        );

        $detalleMovimientos = [];

        DB::transaction(function () use ($orden, $resumen, $almacenGeneral, $archivo, $validated, &$detalleMovimientos) {
            foreach ($resumen['materiales'] as $mp) {
                $this->descontarMpConMovimiento(
                    (int) $mp['producto_mp_id'],
                    (float) $mp['requerido'],
                    (int) $almacenGeneral->id,
                    $orden->folio,
                    'Salida a producción OP ' . $orden->folio . ' · ' . $mp['nombre']
                );
                $detalleMovimientos[] = $mp['nombre'] . ': ' . number_format($mp['requerido'], 2)
                    . ($mp['unidad'] ? ' ' . $mp['unidad'] : '');
            }

            $orden->update([
                'estatus' => OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS,
                'orden_materiales_ruta' => $archivo['ruta'],
                'orden_materiales_nombre' => $archivo['nombre'],
                'materiales_tomados_at' => now(),
                'materiales_tomados_by' => auth()->id(),
                'observaciones' => trim(
                    ($orden->observaciones ? $orden->observaciones . "\n" : '')
                    . 'Materiales ingresados ' . now()->format('d/m/Y H:i')
                    . (!empty($validated['observaciones']) ? ' · ' . $validated['observaciones'] : '')
                ),
                'updated_at' => now(),
            ]);

            if ($orden->pedido_id) {
                ReporteNoExistencia::where('pedido_id', $orden->pedido_id)
                    ->where('tipo', ReporteNoExistencia::TIPO_MATERIA_PRIMA)
                    ->whereIn('estatus', [
                        ReporteNoExistencia::ESTATUS_PENDIENTE,
                        ReporteNoExistencia::ESTATUS_EN_COMPRA,
                    ])
                    ->update([
                        'estatus' => ReporteNoExistencia::ESTATUS_ATENDIDO,
                        'updated_at' => now(),
                    ]);

                VentaPedido::where('id', $orden->pedido_id)
                    ->where('estatus', 'PENDIENTE_OC')
                    ->update(['estatus' => 'EN_PRODUCCION', 'updated_at' => now()]);
            }
        });

        $explicacion = 'Materiales ingresados correctamente para la orden ' . $orden->folio . '. '
            . 'Se descontaron del almacén general: ' . implode('; ', $detalleMovimientos) . '. '
            . 'Se registraron los movimientos de inventario (salida a producción). '
            . 'El documento «' . $archivo['nombre'] . '» quedó disponible en la misma fila de la mesa. '
            . 'La orden pasó a estatus Preparando máquinas.';

        return redirect()
            ->route('produccion.ordenes', ['abrir_orden' => $orden->id])
            ->with('success', $explicacion);
    }

    public function verOrdenMateriales(int $id): BinaryFileResponse
    {
        $orden = OrdenProduccion::findOrFail($id);

        if (empty($orden->orden_materiales_ruta)) {
            abort(404, 'Esta orden no tiene documento de materiales.');
        }

        $ruta = public_path($orden->orden_materiales_ruta);
        if (!is_file($ruta)) {
            abort(404, 'Archivo no encontrado en el servidor.');
        }

        $nombre = $orden->orden_materiales_nombre ?: basename($ruta);

        return response()->download($ruta, $nombre);
    }

    /**
     * Sube el archivo fuente de la orden de producción (plano / documento recibido).
     */
    public function subirArchivoOp(Request $request, int $id): RedirectResponse
    {
        $orden = OrdenProduccion::findOrFail($id);

        if (in_array($orden->estatus, OrdenProduccion::ESTATUS_FINALES, true)) {
            throw ValidationException::withMessages([
                'archivo_op' => 'No se puede cargar archivo en una orden terminada o cancelada.',
            ]);
        }

        $validated = $request->validate([
            'archivo_op' => ['required', 'file', 'max:15360', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,rar'],
        ]);

        $archivo = $this->guardarArchivoOp(
            $request->file('archivo_op'),
            (int) $orden->id,
            $orden->archivo_op_ruta
        );

        $orden->update([
            'archivo_op_ruta' => $archivo['ruta'],
            'archivo_op_nombre' => $archivo['nombre'],
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('produccion.ordenes', ['abrir_orden' => $orden->id])
            ->with('success', 'Archivo de la orden «' . $archivo['nombre'] . '» cargado correctamente.');
    }

    public function verArchivoOp(int $id): BinaryFileResponse
    {
        $orden = OrdenProduccion::findOrFail($id);

        if (empty($orden->archivo_op_ruta)) {
            abort(404, 'Esta orden no tiene archivo cargado.');
        }

        $ruta = public_path($orden->archivo_op_ruta);
        if (!is_file($ruta)) {
            abort(404, 'Archivo no encontrado en el servidor.');
        }

        $nombre = $orden->archivo_op_nombre ?: basename($ruta);

        return response()->download($ruta, $nombre);
    }

    private function validarMaquinaDisponible(int $maquinaId, ?int $ordenActualId = null): void
    {
        $query = OrdenProduccionMaquina::where('maquina_id', $maquinaId)
            ->whereNotIn('estatus', OrdenProduccion::ESTATUS_FINALES);

        if ($ordenActualId !== null) {
            $query->where('orden_id', '!=', $ordenActualId);
        }

        $ocupada = $query->whereHas('orden', function ($q) {
            $q->whereIn('estatus', OrdenProduccion::ESTATUS_ACTIVAS);
        })->exists();

        if ($ocupada) {
            throw ValidationException::withMessages([
                'maquina_id' => 'La máquina seleccionada ya tiene una orden activa en curso.',
            ]);
        }
    }

    public function seguimientoJson(): \Illuminate\Http\JsonResponse
    {
        $turnoActual = $this->resolverTurnoActual();
        $maquinas = Maquina::where('estatus', 'A')->orderBy('nombre')->get(['id', 'codigo', 'nombre']);
        $ordenesActivas = OrdenProduccion::with(['turno', 'operador', 'detalles'])
            ->whereIn('estatus', OrdenProduccion::ESTATUS_ACTIVAS)
            ->get()
            ->keyBy('maquina_id');

        $items = $maquinas->map(function (Maquina $maquina) use ($ordenesActivas) {
            $orden = $ordenesActivas->get($maquina->id);

            return [
                'maquina_id' => $maquina->id,
                'codigo' => $maquina->codigo,
                'nombre' => $maquina->nombre,
                'orden_id' => $orden?->id,
                'folio' => $orden?->folio,
                'estatus' => $orden?->estatus,
                'turno' => $orden?->turno?->nombre,
                'operador' => $orden?->operador
                    ? trim($orden->operador->primer_nombre . ' ' . $orden->operador->apellido_paterno)
                    : null,
                'metros' => $orden ? $orden->total_metros_producidos : 0,
                'piezas' => $orden ? $orden->total_piezas_producidas : 0,
            ];
        });

        return response()->json([
            'turno_actual' => $turnoActual?->nombre,
            'actualizado' => now()->format('H:i:s'),
            'maquinas' => $items,
        ]);
    }

    private function copiarDetalleDesdePedido(OrdenProduccion $orden, int $pedidoId): void
    {
        $pedido = VentaPedido::with('detalles')->findOrFail($pedidoId);

        foreach ($pedido->detalles as $linea) {
            $tipoLinea = $this->resolverTipoLinea((int) $linea->producto_id);
            // Productos de existencia (ya hechos) no entran a la OP; van a embalaje/carga.
            if ($tipoLinea !== 'FABRICAR') {
                continue;
            }

            $data = [
                'orden_id' => $orden->id,
                'producto_id' => $linea->producto_id,
                'tipo_linea' => 'FABRICAR',
                'observaciones' => $linea->descripcion,
                'metros_producidos' => 0,
                'piezas_producidas' => 0,
                'created_at' => now(),
            ];

            $spec = ProductoTuboEspecificacion::where('producto_id', $linea->producto_id)
                ->where('estatus', 'ACTIVO')
                ->orderByDesc('psi')
                ->first();

            if ($spec) {
                $data['diametro'] = $spec->diametro_nominal;
                $data['rd'] = $spec->rd !== null ? (string) $spec->rd : null;
                $data['psi'] = $spec->psi;
                $data['espesor'] = $spec->espesor_pulg;
                $data['kg_metro'] = $spec->peso_kg_m;
            } else {
                $specFlange = ProductoFlangeEspecificacion::where('producto_id', $linea->producto_id)
                    ->where('estatus', 'ACTIVO')
                    ->orderByDesc('id')
                    ->first();
                if ($specFlange) {
                    $data['diametro'] = $specFlange->diametro_nominal;
                    $data['rd'] = $specFlange->rd !== null ? (string) $specFlange->rd : null;
                    $data['kg_metro'] = $specFlange->peso_kg_pieza;
                    if (empty($orden->tipo_proceso) || $orden->tipo_proceso === OrdenProduccion::TIPO_PROCESO_TUBO) {
                        $orden->update([
                            'tipo_proceso' => OrdenProduccion::TIPO_PROCESO_FLANGE,
                            'ruta_flange' => ProductoFlangeEspecificacion::resolverRuta(
                                $specFlange->diametro_nominal,
                                $specFlange->rd
                            ),
                            'updated_at' => now(),
                        ]);
                    }
                } else {
                    $specConexion = ProductoConexionEspecificacion::where('producto_id', $linea->producto_id)
                        ->where('estatus', 'ACTIVO')
                        ->orderByDesc('id')
                        ->first();
                    if ($specConexion) {
                        $data['diametro'] = $specConexion->diametro_nominal;
                        $data['rd'] = $specConexion->rd !== null ? (string) $specConexion->rd : null;
                        $data['kg_metro'] = $specConexion->peso_kg_pieza;
                        if (empty($orden->tipo_proceso) || $orden->tipo_proceso === OrdenProduccion::TIPO_PROCESO_TUBO) {
                            $orden->update([
                                'tipo_proceso' => OrdenProduccion::TIPO_PROCESO_CONEXION,
                                'ruta_flange' => ProductoFlangeEspecificacion::resolverRuta(
                                    $specConexion->diametro_nominal,
                                    $specConexion->rd
                                ),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            OrdenProduccionDetalle::create($data);
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<OrdenProduccion>  $query
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltrosOrdenesMesa($query, array $filtros): void
    {
        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha', '>=', $filtros['fecha_desde']);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('fecha', '<=', $filtros['fecha_hasta']);
        }
        if (!empty($filtros['estatus'])) {
            if ($filtros['estatus'] === 'PENDIENTE_PROGRAMACION') {
                $query->whereRaw('0 = 1');
            } else {
                $query->where('estatus', $filtros['estatus']);
            }
        }
        if (!empty($filtros['maquina_id'])) {
            $query->where('maquina_id', (int) $filtros['maquina_id']);
        }
        if (!empty($filtros['operador_id'])) {
            $query->where('operador_id', (int) $filtros['operador_id']);
        }
        if (!empty($filtros['turno_id'])) {
            $query->where('turno_id', (int) $filtros['turno_id']);
        }
        if (!empty($filtros['cliente_id'])) {
            $query->whereHas('pedido', fn ($q) => $q->where('cliente_id', (int) $filtros['cliente_id']));
        }
        if (!empty($filtros['pedido'])) {
            $termino = trim((string) $filtros['pedido']);
            $query->where(function ($q) use ($termino) {
                $q->where('folio', 'like', "%{$termino}%")
                    ->orWhereHas('pedido', fn ($p) => $p->where('folio', 'like', "%{$termino}%"));
            });
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<VentaPedido>  $query
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltrosPedidosPendientesMesa($query, array $filtros): void
    {
        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', (int) $filtros['cliente_id']);
        }
        if (!empty($filtros['pedido'])) {
            $termino = trim((string) $filtros['pedido']);
            $query->where('folio', 'like', "%{$termino}%");
        }
    }

    /**
     * @return array<string, int>
     */
    private function construirKpisMesa(): array
    {
        $pedidosPendientesQuery = VentaPedido::whereIn('estatus', ['EN_PRODUCCION', 'PENDIENTE_OC', 'CONFIRMADO'])
            ->whereDoesntHave('ordenProduccion');
        $this->restringirPedidosConProductoAProducir($pedidosPendientesQuery);

        return [
            'pedidos_pendientes' => $pedidosPendientesQuery->count(),
            'ordenes_programadas' => OrdenProduccion::whereIn('estatus', [
                OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS,
                OrdenProduccion::ESTATUS_CALENTANDO,
            ])->whereHas('detalles', fn ($q) => $q->where('tipo_linea', 'FABRICAR'))->count(),
            'en_produccion' => OrdenProduccion::whereIn('estatus', [
                OrdenProduccion::ESTATUS_EN_PRODUCCION,
                OrdenProduccion::ESTATUS_PAUSADA,
                OrdenProduccion::ESTATUS_REVISION_CALIDAD,
                OrdenProduccion::ESTATUS_PREPARAR_EMPAQUE,
                OrdenProduccion::ESTATUS_ENROLLANDO,
                OrdenProduccion::ESTATUS_A_LONGITUD,
                OrdenProduccion::ESTATUS_CORTAR_FLEJAR,
                OrdenProduccion::ESTATUS_EMPACANDO,
            ])->whereHas('detalles', fn ($q) => $q->where('tipo_linea', 'FABRICAR'))->count(),
            'esperando_material' => OrdenProduccion::where('estatus', OrdenProduccion::ESTATUS_EN_ESPERA_MATERIALES)
                ->whereHas('detalles', fn ($q) => $q->where('tipo_linea', 'FABRICAR'))
                ->count(),
            'terminadas_hoy' => OrdenProduccion::where('estatus', OrdenProduccion::ESTATUS_TERMINADA)
                ->whereDate('updated_at', today())
                ->count(),
            'canceladas_mes' => OrdenProduccion::where('estatus', OrdenProduccion::ESTATUS_CANCELADA)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
        ];
    }

    /**
     * Pedidos que deben fabricar al menos un producto (tubo, flange, conexión o receta).
     * Los de solo existencia no aparecen en la mesa de producción.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<VentaPedido>  $query
     */
    private function restringirPedidosConProductoAProducir($query): void
    {
        $query->where(function ($q) {
            $q->whereHas('detalles.producto.especificacionesTubo', function ($e) {
                $e->where('estatus', 'ACTIVO');
            })->orWhereHas('detalles.producto.especificacionesFlange', function ($e) {
                $e->where('estatus', 'ACTIVO');
            })->orWhereHas('detalles.producto.especificacionesConexion', function ($e) {
                $e->where('estatus', 'ACTIVO');
            })->orWhereHas('detalles.producto.recetas', function ($r) {
                $r->where('estatus', 'ACTIVA');
            });
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, OrdenProduccion>  $ordenes
     * @param  \Illuminate\Support\Collection<int, VentaPedido>  $pedidosPendientes
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function construirFilasMesa($ordenes, $pedidosPendientes)
    {
        $filas = collect();

        foreach ($ordenes as $orden) {
            $filas->push($this->filaDesdeOrden($orden));
        }

        foreach ($pedidosPendientes as $pedido) {
            $filas->push($this->filaDesdePedidoPendiente($pedido));
        }

        $prioridadOrden = ['alta' => 0, 'media' => 1, 'baja' => 2];

        return $filas->sortBy(function (array $fila) use ($prioridadOrden) {
            return [
                $prioridadOrden[$fila['prioridad']] ?? 1,
                $fila['fecha_entrega_sort'] ?? '9999-12-31',
            ];
        })->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function filaDesdeOrden(OrdenProduccion $orden): array
    {
        $resumen = $this->resumirProductoOrden($orden);
        $fechaEntrega = $orden->pedido?->fecha_entrega;

        return [
            'tipo' => 'orden',
            'orden_id' => $orden->id,
            'orden_folio' => $orden->folio,
            'pedido_id' => $orden->pedido_id,
            'pedido_folio' => $orden->pedido?->folio,
            'prioridad' => $this->calcularPrioridadEntrega($fechaEntrega),
            'cliente' => $orden->pedido?->cliente?->nombre ?? '—',
            'producto' => $resumen['producto'],
            'cantidad' => $resumen['cantidad'],
            'cantidad_label' => $resumen['label'],
            'fecha_entrega' => $fechaEntrega?->format('d/m/Y'),
            'fecha_entrega_sort' => $fechaEntrega?->format('Y-m-d') ?? '9999-12-31',
            'fecha_entrega_vencida' => $fechaEntrega && $fechaEntrega->isPast(),
            'maquina' => $orden->maquina?->nombre ?? ($orden->maquina?->codigo ?? '—'),
            'turno' => $orden->turno?->nombre ?? '—',
            'operador' => $orden->operador
                ? trim($orden->operador->primer_nombre . ' ' . $orden->operador->apellido_paterno)
                : '—',
            'estatus' => $orden->estatus,
            'estatus_label' => $orden->estatus_texto,
            'estatus_ayuda' => $orden->ayudaEstatusActual(),
            'es_flange' => $orden->esSoloFlange(),
            'es_conexion' => $orden->esConexion(),
            'es_pieza' => $orden->esFlange(),
            'tipo_proceso' => $orden->tipo_proceso ?? OrdenProduccion::TIPO_PROCESO_TUBO,
            'tipo_proceso_badge' => $orden->tipo_proceso_badge,
            'ruta_flange' => $orden->ruta_flange,
            'avance' => $this->calcularAvanceOrden($orden),
            'puede_ingresar_materiales' => $orden->estatus === OrdenProduccion::ESTATUS_EN_ESPERA_MATERIALES
                && empty($orden->materiales_tomados_at),
            'tiene_orden_materiales' => !empty($orden->orden_materiales_ruta),
            'orden_materiales_nombre' => $orden->orden_materiales_nombre,
            'tiene_archivo_op' => !empty($orden->archivo_op_ruta),
            'archivo_op_nombre' => $orden->archivo_op_nombre,
            'siguiente_estatus' => $orden->siguienteEstatus(),
            'siguiente_estatus_label' => ($sig = $orden->siguienteEstatus())
                ? $orden->etiquetaEstatusPara($sig)
                : null,
            'usa_avance_manual' => $orden->usaAvanceManual(),
            'es_pausada' => $orden->estatus === OrdenProduccion::ESTATUS_PAUSADA,
            'es_produccion' => $orden->estatus === OrdenProduccion::ESTATUS_EN_PRODUCCION,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filaDesdePedidoPendiente(VentaPedido $pedido): array
    {
        $primera = $pedido->detalles->first();
        $producto = $primera
            ? ($primera->descripcion ?: ($primera->producto?->nombre ?? '—'))
            : '—';
        $cantidad = $primera ? (float) $primera->cantidad : 0;
        $extra = $pedido->detalles->count() > 1 ? ' (+' . ($pedido->detalles->count() - 1) . ')' : '';

        $prodModelo = $primera?->producto;
        $esConexion = $prodModelo?->esConexion()
            || ($primera && ProductoConexionEspecificacion::where('producto_id', $primera->producto_id)->where('estatus', 'ACTIVO')->exists());
        $esFlange = !$esConexion && (
            $prodModelo?->esSoloFlange()
            || ($primera && ProductoFlangeEspecificacion::where('producto_id', $primera->producto_id)->where('estatus', 'ACTIVO')->exists())
        );

        return [
            'tipo' => 'pedido',
            'orden_id' => null,
            'orden_folio' => null,
            'pedido_id' => $pedido->id,
            'pedido_folio' => $pedido->folio,
            'prioridad' => $this->calcularPrioridadEntrega($pedido->fecha_entrega),
            'cliente' => $pedido->cliente?->nombre ?? '—',
            'producto' => $producto . $extra,
            'cantidad' => $cantidad,
            'cantidad_label' => number_format($cantidad, $esConexion || $esFlange ? 0 : 2)
                . ($esConexion || $esFlange ? ' pzas' : ' pza'),
            'fecha_entrega' => $pedido->fecha_entrega?->format('d/m/Y'),
            'fecha_entrega_sort' => $pedido->fecha_entrega?->format('Y-m-d') ?? '9999-12-31',
            'fecha_entrega_vencida' => $pedido->fecha_entrega && $pedido->fecha_entrega->isPast(),
            'maquina' => '—',
            'turno' => '—',
            'operador' => '—',
            'estatus' => 'PENDIENTE_PROGRAMACION',
            'estatus_label' => 'Pendiente programación',
            'estatus_ayuda' => $esConexion
                ? 'Pedido con conexiones a fabricar. Genere la OP desde la mesa.'
                : ($esFlange
                    ? 'Pedido con flanges a fabricar. Genere la OP desde la mesa.'
                    : 'Pedido pendiente de programar en producción.'),
            'es_flange' => (bool) $esFlange,
            'es_conexion' => (bool) $esConexion,
            'es_pieza' => (bool) ($esFlange || $esConexion),
            'tipo_proceso' => $esConexion
                ? OrdenProduccion::TIPO_PROCESO_CONEXION
                : ($esFlange ? OrdenProduccion::TIPO_PROCESO_FLANGE : OrdenProduccion::TIPO_PROCESO_TUBO),
            'tipo_proceso_badge' => $esConexion ? 'CONEXIÓN' : ($esFlange ? 'FLANGE' : 'TUBO'),
            'avance' => 0,
            'puede_ingresar_materiales' => false,
            'tiene_orden_materiales' => false,
            'orden_materiales_nombre' => null,
            'tiene_archivo_op' => false,
            'archivo_op_nombre' => null,
            'siguiente_estatus' => null,
            'siguiente_estatus_label' => null,
            'usa_avance_manual' => false,
            'es_pausada' => false,
            'es_produccion' => false,
        ];
    }

    /**
     * @return array{producto: string, cantidad: float, label: string}
     */
    private function resumirProductoOrden(OrdenProduccion $orden): array
    {
        $detalle = $orden->detalles->first();
        if (!$detalle) {
            return ['producto' => '—', 'cantidad' => 0, 'label' => '—'];
        }

        $nombre = $detalle->producto?->nombre ?? ($detalle->observaciones ?: '—');
        $extra = $orden->detalles->count() > 1 ? ' (+' . ($orden->detalles->count() - 1) . ')' : '';
        $cantidadPedido = 0.0;

        if ($orden->pedido) {
            $lineaPedido = $orden->pedido->detalles->firstWhere('producto_id', $detalle->producto_id);
            $cantidadPedido = $lineaPedido ? (float) $lineaPedido->cantidad : 0;
        }

        $unidad = $orden->esFlange()
            ? 'pza'
            : ($detalle->esFabricar() ? 'm' : 'pza');
        $cantidad = $cantidadPedido > 0 ? $cantidadPedido : max((float) $detalle->metros_producidos, (float) $detalle->piezas_producidas);

        return [
            'producto' => $nombre . $extra,
            'cantidad' => $cantidad,
            'label' => number_format($cantidad, 2) . ' ' . $unidad,
        ];
    }

    private function calcularPrioridadEntrega($fechaEntrega): string
    {
        if (!$fechaEntrega) {
            return 'media';
        }

        $fecha = $fechaEntrega instanceof Carbon ? $fechaEntrega->copy()->startOfDay() : Carbon::parse($fechaEntrega)->startOfDay();
        $dias = now()->startOfDay()->diffInDays($fecha, false);

        if ($dias < 0 || $dias <= 2) {
            return 'alta';
        }
        if ($dias <= 7) {
            return 'media';
        }

        return 'baja';
    }

    private function calcularAvanceOrden(OrdenProduccion $orden): int
    {
        if ($orden->estatus === OrdenProduccion::ESTATUS_TERMINADA) {
            return 100;
        }

        $orden->loadMissing(['detalles', 'pedido.detalles']);
        $meta = 0.0;
        $producido = 0.0;

        foreach ($orden->detalles as $detalle) {
            $cantidadPedido = 0.0;
            if ($orden->pedido) {
                $lineaPedido = $orden->pedido->detalles->firstWhere('producto_id', $detalle->producto_id);
                $cantidadPedido = $lineaPedido ? (float) $lineaPedido->cantidad : 0;
            }

            if ($cantidadPedido > 0) {
                $meta += $cantidadPedido;
                $producido += max((float) $detalle->piezas_producidas, (float) $detalle->metros_producidos);
            }
        }

        if ($meta <= 0) {
            return match ($orden->estatus) {
                OrdenProduccion::ESTATUS_EN_PRODUCCION,
                OrdenProduccion::ESTATUS_REVISION_CALIDAD,
                OrdenProduccion::ESTATUS_EMPACANDO,
                OrdenProduccion::ESTATUS_RUTA_TORNO,
                OrdenProduccion::ESTATUS_RUTA_TORNO_EXTERNO,
                OrdenProduccion::ESTATUS_RUTA_CORTE => 50,
                OrdenProduccion::ESTATUS_CALIDAD_FINAL => 70,
                OrdenProduccion::ESTATUS_ENTREGA_ALMACEN => 90,
                OrdenProduccion::ESTATUS_PAUSADA => 30,
                OrdenProduccion::ESTATUS_CALENTANDO => 15,
                OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS => 10,
                default => 0,
            };
        }

        return min(100, (int) round(($producido / $meta) * 100));
    }

    private function crearOrdenDesdePedido(VentaPedido $pedido, Turno $turno): OrdenProduccion
    {
        $estatusInicial = $this->pedidoBloqueaAsignacionMaquina((int) $pedido->id)
            ? OrdenProduccion::ESTATUS_EN_ESPERA_MATERIALES
            : OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS;

        $orden = OrdenProduccion::create([
            'folio' => $this->generarFolio(),
            'fecha' => now()->toDateString(),
            'pedido_id' => $pedido->id,
            'maquina_id' => null,
            'turno_id' => $turno->id,
            'operador_id' => null,
            'supervisor_id' => null,
            'estatus' => $estatusInicial,
            'observaciones' => 'Importada desde pedido ' . ($pedido->folio ?: ('#' . $pedido->id)),
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        $this->copiarDetalleDesdePedido($orden, (int) $pedido->id);

        return $orden;
    }

    private function generarFolio(): string
    {
        $prefijo = 'OP-' . now()->format('Ymd') . '-';
        $ultimo = OrdenProduccion::where('folio', 'like', $prefijo . '%')
            ->orderByDesc('id')
            ->value('folio');

        $consecutivo = 1;
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $matches)) {
            $consecutivo = (int) $matches[1] + 1;
        }

        return $prefijo . str_pad((string) $consecutivo, 3, '0', STR_PAD_LEFT);
    }

    private function resolverTurnoActual(): ?Turno
    {
        $ahora = Carbon::now()->format('H:i:s');

        return Turno::where('estatus', 'ACTIVO')
            ->get()
            ->first(function (Turno $turno) use ($ahora) {
                $inicio = $turno->hora_inicio;
                $fin = $turno->hora_fin;

                if ($inicio <= $fin) {
                    return $ahora >= $inicio && $ahora < $fin;
                }

                return $ahora >= $inicio || $ahora < $fin;
            });
    }

    private function validarOrdenEditable(OrdenProduccion $orden): void
    {
        if (in_array($orden->estatus, OrdenProduccion::ESTATUS_FINALES, true)) {
            throw ValidationException::withMessages([
                'orden' => 'No se puede modificar una orden terminada o cancelada.',
            ]);
        }
    }

    private function pedidoBloqueaAsignacionMaquina(int $pedidoId): bool
    {
        $pedido = VentaPedido::find($pedidoId);
        if (!$pedido) {
            return false;
        }

        if ($pedido->estatus === 'PENDIENTE_OC') {
            return true;
        }

        return ReporteNoExistencia::where('pedido_id', $pedidoId)
            ->where('tipo', ReporteNoExistencia::TIPO_MATERIA_PRIMA)
            ->whereIn('estatus', [
                ReporteNoExistencia::ESTATUS_PENDIENTE,
                ReporteNoExistencia::ESTATUS_EN_COMPRA,
            ])
            ->exists();
    }

    /**
     * Evita iniciar producción mientras existan faltantes de MP en el pedido ligado.
     */
    private function validarMaterialesParaIniciarProduccion(OrdenProduccion $orden): void
    {
        if (empty($orden->pedido_id)) {
            return;
        }

        $faltantes = ReporteNoExistencia::where('pedido_id', $orden->pedido_id)
            ->where('tipo', ReporteNoExistencia::TIPO_MATERIA_PRIMA)
            ->whereIn('estatus', [
                ReporteNoExistencia::ESTATUS_PENDIENTE,
                ReporteNoExistencia::ESTATUS_EN_COMPRA,
            ])
            ->count();

        if ($faltantes > 0) {
            throw ValidationException::withMessages([
                'estatus' => 'No puede iniciar producción: todavía hay materiales pendientes por comprar para este pedido.',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function aplicarEspecificacionTubo(array &$validated): void
    {
        $spec = null;

        if (!empty($validated['especificacion_id'])) {
            $spec = ProductoTuboEspecificacion::where('estatus', 'ACTIVO')
                ->find($validated['especificacion_id']);
        } elseif (!empty($validated['producto_id']) && isset($validated['psi']) && $validated['psi'] !== '') {
            $spec = ProductoTuboEspecificacion::where('producto_id', $validated['producto_id'])
                ->where('psi', $validated['psi'])
                ->where('estatus', 'ACTIVO')
                ->first();
        }

        if (!$spec) {
            return;
        }

        if (empty($validated['producto_id'])) {
            $validated['producto_id'] = $spec->producto_id;
        }

        $validated['diametro'] = $validated['diametro'] ?? $spec->diametro_nominal;
        $validated['rd'] = $validated['rd'] ?? ($spec->rd !== null ? (string) $spec->rd : null);
        $validated['psi'] = $spec->psi;
        $validated['espesor'] = $validated['espesor'] ?? $spec->espesor_pulg;
        $validated['kg_metro'] = $validated['kg_metro'] ?? $spec->peso_kg_m;
    }

    /**
     * Completa Ø/RD/peso pieza desde specs flange o conexión y marca la OP.
     *
     * @param array<string, mixed> $validated
     */
    private function aplicarEspecificacionPieza(array &$validated, OrdenProduccion $orden): void
    {
        $productoId = (int) ($validated['producto_id'] ?? 0);
        if ($productoId <= 0) {
            return;
        }

        $specFlange = ProductoFlangeEspecificacion::where('producto_id', $productoId)
            ->where('estatus', 'ACTIVO')
            ->orderByDesc('id')
            ->first();

        if ($specFlange) {
            $validated['diametro'] = $validated['diametro'] ?? $specFlange->diametro_nominal;
            $validated['rd'] = $validated['rd'] ?? ($specFlange->rd !== null ? (string) $specFlange->rd : null);
            $validated['kg_metro'] = $validated['kg_metro'] ?? $specFlange->peso_kg_pieza;
            if (!$orden->esFlange()) {
                $orden->update([
                    'tipo_proceso' => OrdenProduccion::TIPO_PROCESO_FLANGE,
                    'ruta_flange' => ProductoFlangeEspecificacion::resolverRuta(
                        $specFlange->diametro_nominal,
                        $specFlange->rd
                    ),
                    'updated_at' => now(),
                ]);
            }

            return;
        }

        $specConexion = ProductoConexionEspecificacion::where('producto_id', $productoId)
            ->where('estatus', 'ACTIVO')
            ->orderByDesc('id')
            ->first();

        if (!$specConexion) {
            return;
        }

        $validated['diametro'] = $validated['diametro'] ?? $specConexion->diametro_nominal;
        $validated['rd'] = $validated['rd'] ?? ($specConexion->rd !== null ? (string) $specConexion->rd : null);
        $validated['kg_metro'] = $validated['kg_metro'] ?? $specConexion->peso_kg_pieza;

        if (!$orden->esFlange()) {
            $orden->update([
                'tipo_proceso' => OrdenProduccion::TIPO_PROCESO_CONEXION,
                'ruta_flange' => ProductoFlangeEspecificacion::resolverRuta(
                    $specConexion->diametro_nominal,
                    $specConexion->rd
                ),
                'updated_at' => now(),
            ]);
        }
    }

    private function resolverTipoLinea(int $productoId): string
    {
        $esTubo = ProductoTuboEspecificacion::where('producto_id', $productoId)
            ->where('estatus', 'ACTIVO')
            ->exists();

        if ($esTubo) {
            return 'FABRICAR';
        }

        $esFlange = ProductoFlangeEspecificacion::where('producto_id', $productoId)
            ->where('estatus', 'ACTIVO')
            ->exists();

        if ($esFlange) {
            return 'FABRICAR';
        }

        $esConexion = ProductoConexionEspecificacion::where('producto_id', $productoId)
            ->where('estatus', 'ACTIVO')
            ->exists();

        if ($esConexion) {
            return 'FABRICAR';
        }

        $prod = Productos::find($productoId);
        if ($prod && $prod->esFlange()) {
            return 'FABRICAR';
        }

        $tieneReceta = Receta::where('producto_id', $productoId)
            ->where('estatus', 'ACTIVA')
            ->exists();

        return $tieneReceta ? 'FABRICAR' : 'EXISTENCIA';
    }

    /**
     * @return array<string, mixed>
     */
    private function reglasDetalle(): array
    {
        return [
            'tipo_linea' => ['nullable', Rule::in(['FABRICAR', 'EXISTENCIA'])],
            'producto_id' => ['required', 'integer', 'exists:tblproductos,id'],
            'especificacion_id' => ['nullable', 'integer', 'exists:tbl_producto_tubo_especificaciones,id'],
            'diametro' => ['nullable', 'string', 'max:50'],
            'rd' => ['nullable', 'string', 'max:50'],
            'psi' => ['nullable', 'numeric', 'min:0'],
            'espesor' => ['nullable', 'numeric', 'min:0'],
            'kg_metro' => ['nullable', 'numeric', 'min:0'],
            'ubicacion_destino_id' => ['nullable', 'integer', 'exists:tblubicaciones,id'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    private function calcularKgTeorico(float $metros, float $kgMetro): float
    {
        if ($metros <= 0 || $kgMetro <= 0) {
            return 0;
        }

        return round($metros * $kgMetro, 3);
    }

    /** Holgura máxima de metros sobre lo pedido / longitud objetivo (5%). */
    private const MERMA_METROS_TOLERANCIA_PCT = 5.0;

    /** Holgura máxima de kg real sobre el teórico de la salida (15%). */
    private const MERMA_KG_TOLERANCIA_PCT = 15.0;

    /**
     * Cantidad pedida (m o pzas) y tope permitido con holgura.
     *
     * @return array{esperada: float, maxima: float, origen: string|null}
     */
    private function cantidadEsperadaDetalle(OrdenProduccion $orden, OrdenProduccionDetalle $detalle): array
    {
        $orden->loadMissing(['pedido.detalles']);
        $productoId = (int) ($detalle->producto_id ?? 0);
        $esperada = 0.0;
        $origen = null;

        if ($orden->esFlange()) {
            if ($orden->pedido && $productoId > 0) {
                $linea = $orden->pedido->detalles->firstWhere('producto_id', $productoId);
                if ($linea && (float) $linea->cantidad > 0) {
                    $esperada = (float) $linea->cantidad;
                    $origen = 'pedido';
                }
            }
        } else {
            if ($orden->longitud_objetivo_m && (float) $orden->longitud_objetivo_m > 0) {
                $esperada = (float) $orden->longitud_objetivo_m;
                $origen = 'longitud objetivo';
            } elseif ($orden->pedido && $productoId > 0) {
                $linea = $orden->pedido->detalles->firstWhere('producto_id', $productoId);
                if ($linea && (float) $linea->cantidad > 0) {
                    $esperada = (float) $linea->cantidad;
                    $origen = 'pedido';
                }
            }
        }

        if ($esperada <= 0) {
            return ['esperada' => 0.0, 'maxima' => 0.0, 'origen' => null];
        }

        $holgura = max($esperada * (self::MERMA_METROS_TOLERANCIA_PCT / 100), $orden->esFlange() ? 1.0 : 0.5);

        return [
            'esperada' => $esperada,
            'maxima' => round($esperada + $holgura, 3),
            'origen' => $origen,
        ];
    }

    private function validarMedidasSalidaTubo(
        OrdenProduccion $orden,
        OrdenProduccionDetalle $detalle,
        float $metros,
        float $kgReal
    ): void {
        $limites = $this->cantidadEsperadaDetalle($orden, $detalle);
        $acumulado = (float) ($detalle->metros_producidos ?? 0);
        $totalTrasSalida = round($acumulado + $metros, 3);

        if ($limites['esperada'] > 0 && $totalTrasSalida > $limites['maxima']) {
            $restante = max(round($limites['maxima'] - $acumulado, 3), 0);
            throw ValidationException::withMessages([
                'metros' => 'Los metros capturados son ilógicos: acumularían '
                    . number_format($totalTrasSalida, 2) . ' m y el tope es '
                    . number_format($limites['maxima'], 2) . ' m ('
                    . number_format($limites['esperada'], 2) . ' m de '
                    . ($limites['origen'] ?? 'plan')
                    . ' + ' . number_format(self::MERMA_METROS_TOLERANCIA_PCT, 0) . '%). '
                    . 'Ya hay ' . number_format($acumulado, 2) . ' m; máximo en esta salida: '
                    . number_format($restante, 2) . ' m.',
            ]);
        }

        $kgMetro = (float) ($detalle->kg_metro ?? 0);
        $kgTeorico = $this->calcularKgTeorico($metros, $kgMetro);

        if ($kgTeorico > 0 && $kgReal > 0) {
            $kgMax = round($kgTeorico * (1 + self::MERMA_KG_TOLERANCIA_PCT / 100), 3);
            if ($kgReal > $kgMax) {
                throw ValidationException::withMessages([
                    'kg_real' => 'El kg real (' . number_format($kgReal, 2)
                        . ') es ilógico frente al teórico de esta salida ('
                        . number_format($kgTeorico, 2) . ' kg = '
                        . number_format($metros, 2) . ' m × '
                        . number_format($kgMetro, 4) . ' kg/m). '
                        . 'Máximo permitido: ' . number_format($kgMax, 2)
                        . ' kg (+' . number_format(self::MERMA_KG_TOLERANCIA_PCT, 0) . '%). '
                        . 'Revise metros o el peso en báscula.',
                ]);
            }
        }
    }

    private function validarMedidasSalidaFlange(
        OrdenProduccion $orden,
        OrdenProduccionDetalle $detalle,
        int $piezasBuenas,
        int $piezasMalas
    ): void {
        $piezas = $piezasBuenas + $piezasMalas;
        if ($piezas <= 0) {
            throw ValidationException::withMessages([
                'piezas_buenas' => 'Indique al menos una pieza (buena o mala).',
            ]);
        }

        $limites = $this->cantidadEsperadaDetalle($orden, $detalle);
        $acumulado = (int) ($detalle->piezas_producidas ?? 0);
        // Acumulado solo cuenta buenas; el tope aplica a buenas+malas de esta tanda + buenas previas.
        $totalTras = $acumulado + $piezas;

        if ($limites['esperada'] > 0 && $totalTras > $limites['maxima']) {
            $restante = max((int) floor($limites['maxima'] - $acumulado), 0);
            throw ValidationException::withMessages([
                'piezas_buenas' => 'Las piezas capturadas son ilógicas: acumularían '
                    . $totalTras . ' y el tope es ' . number_format($limites['maxima'], 0)
                    . ' (pedido ' . number_format($limites['esperada'], 0)
                    . ' + ' . number_format(self::MERMA_METROS_TOLERANCIA_PCT, 0) . '%). '
                    . 'Máximo en este avance: ' . $restante . ' pzas.',
            ]);
        }
    }

    /**
     * Consolida materiales de receta de las líneas FABRICAR de la orden (según cantidad del pedido).
     *
     * @return array{productos: array<int, array<string, mixed>>, materiales: array<int, array<string, mixed>>, puede_tomar: bool}
     */
    private function obtenerResumenMaterialesOrden(OrdenProduccion $orden): array
    {
        $orden->loadMissing(['pedido.detalles', 'detalles.producto']);

        $productos = [];
        $materiales = [];

        foreach ($orden->detalles as $detalle) {
            if (method_exists($detalle, 'esFabricar') && !$detalle->esFabricar()) {
                continue;
            }

            $productoId = (int) ($detalle->producto_id ?? 0);
            if ($productoId <= 0) {
                continue;
            }

            $cantidad = 0.0;
            if ($orden->pedido) {
                $linea = $orden->pedido->detalles->firstWhere('producto_id', $productoId);
                $cantidad = $linea ? (float) $linea->cantidad : 0.0;
            }

            if ($cantidad <= 0) {
                $cantidad = 1.0;
            }

            $info = $this->materialesRecetaParaProduccion($productoId, $cantidad);
            $productos[] = [
                'producto_id' => $productoId,
                'nombre' => $detalle->producto?->nombre ?? ('Producto #' . $productoId),
                'cantidad' => $cantidad,
                'tiene_receta' => $info['tiene_receta'],
                'producible' => $info['producible'],
            ];

            foreach ($info['materiales'] as $m) {
                $mpId = (int) $m['producto_mp_id'];
                if ($mpId <= 0) {
                    continue;
                }
                if (!isset($materiales[$mpId])) {
                    $materiales[$mpId] = $m;
                } else {
                    $materiales[$mpId]['requerido'] = round($materiales[$mpId]['requerido'] + $m['requerido'], 4);
                    $materiales[$mpId]['faltante'] = max(0, round($materiales[$mpId]['requerido'] - $materiales[$mpId]['disponible'], 4));
                    $materiales[$mpId]['suficiente'] = $materiales[$mpId]['disponible'] >= $materiales[$mpId]['requerido'];
                }
            }
        }

        $lista = array_values($materiales);
        $puedeTomar = !empty($lista) && collect($lista)->every(fn ($m) => $m['suficiente']);

        return [
            'productos' => $productos,
            'materiales' => $lista,
            'puede_tomar' => $puedeTomar,
        ];
    }

    /**
     * @return array{tiene_receta: bool, producible: bool, materiales: array<int, array<string, mixed>>}
     */
    private function materialesRecetaParaProduccion(int $productoId, float $cantidad): array
    {
        $receta = Receta::with(['detalles.productoMp', 'detalles.unidad'])
            ->where('producto_id', $productoId)
            ->where('estatus', 'ACTIVA')
            ->orderByDesc('id')
            ->first();

        if (!$receta) {
            return ['tiene_receta' => false, 'producible' => false, 'materiales' => []];
        }

        $almacen = $this->obtenerAlmacenGeneralProduccion();
        $almacenId = $almacen ? (int) $almacen->id : 0;
        $cantidad = max(0, $cantidad);
        $materiales = [];
        $producible = true;

        foreach ($receta->detalles as $detalle) {
            $mpId = (int) $detalle->producto_mp_id;
            $requerido = round((float) $detalle->cantidad * $cantidad, 4);
            $disponible = $mpId > 0 && $almacenId > 0
                ? $this->existenciaDisponibleAlmacen($mpId, $almacenId)
                : 0.0;
            $suficiente = $disponible >= $requerido;
            if (!$suficiente) {
                $producible = false;
            }

            $materiales[] = [
                'producto_mp_id' => $mpId,
                'sku' => $detalle->productoMp->sku ?? null,
                'nombre' => $detalle->productoMp->nombre ?? ('MP #' . $mpId),
                'unidad' => $detalle->unidad->nombre ?? null,
                'requerido' => $requerido,
                'disponible' => round($disponible, 4),
                'faltante' => $suficiente ? 0 : round($requerido - $disponible, 4),
                'suficiente' => $suficiente,
            ];
        }

        return [
            'tiene_receta' => true,
            'producible' => $producible,
            'materiales' => $materiales,
        ];
    }

    private function existenciaDisponibleAlmacen(int $productoId, int $almacenId): float
    {
        $rows = DB::table('tblexistencias')
            ->where('id_almacen', $almacenId)
            ->where('id_producto', $productoId)
            ->get(['cantidad_existente', 'cantidad_reservada']);

        $total = 0.0;
        foreach ($rows as $row) {
            $total += max(0, (float) $row->cantidad_existente - (float) ($row->cantidad_reservada ?? 0));
        }

        return round($total, 4);
    }

    private function obtenerAlmacenGeneralProduccion(): ?object
    {
        return DB::table('tblalmacenes')
            ->where('estado', 'A')
            ->whereRaw('UPPER(folio_interno) LIKE ?', ['%ALMACEN GENERAL%'])
            ->first();
    }

    private function descontarMpConMovimiento(
        int $productoId,
        float $cantidad,
        int $idAlmacen,
        string $documentoReferencia,
        string $observaciones
    ): void {
        $pendiente = round($cantidad, 2);
        $tipoSalida = (int) (DB::table('tbltipos_movimientos_inventario')
            ->where('nombre_movimiento', 'Salida a producción')
            ->value('id') ?: 0);
        $usuarioId = (int) (auth()->id() ?? 0);
        $fecha = now()->format('Y-m-d');
        $doc = Str::limit($documentoReferencia, 200, '');

        $existencias = DB::table('tblexistencias')
            ->where('id_almacen', $idAlmacen)
            ->where('id_producto', $productoId)
            ->orderBy('id')
            ->get();

        foreach ($existencias as $existencia) {
            if ($pendiente <= 0) {
                break;
            }

            $disponible = max(0, (float) $existencia->cantidad_existente - (float) ($existencia->cantidad_reservada ?? 0));
            if ($disponible <= 0) {
                continue;
            }

            $aDescontar = min($pendiente, $disponible);
            DB::table('tblexistencias')
                ->where('id', $existencia->id)
                ->update(['cantidad_existente' => round((float) $existencia->cantidad_existente - $aDescontar, 2)]);

            if ($tipoSalida > 0 && $usuarioId > 0) {
                DB::table('tblmovimientos_inventario')->insert([
                    'id_producto' => $productoId,
                    'id_almacen' => $idAlmacen,
                    'id_ubicacion' => (int) ($existencia->id_ubicacion ?? 0),
                    'id_tipo_movimiento' => $tipoSalida,
                    'cantidad_producto_movimiento' => (int) max(1, (int) round($aDescontar)),
                    'fecha_movimiento' => $fecha,
                    'documento_referencia' => $doc,
                    'observaciones' => $observaciones . ' · cant. ' . number_format($aDescontar, 2),
                    'id_estado_movinv' => 2,
                    'usuario_movimiento' => $usuarioId,
                    'created_at' => now(),
                ]);
            }

            $pendiente = round($pendiente - $aDescontar, 2);
        }

        if ($pendiente > 0) {
            throw ValidationException::withMessages([
                'existencia' => 'No fue posible descontar la existencia completa del producto #' . $productoId . '.',
            ]);
        }
    }

    /**
     * @return array{ruta: string, nombre: string}
     */
    private function guardarOrdenMaterialesArchivo($archivo, int $ordenId, ?string $rutaAnterior = null): array
    {
        $carpetaRelativa = 'archivos/ordenes_materiales';
        $destino = public_path($carpetaRelativa);
        if (!is_dir($destino)) {
            mkdir($destino, 0755, true);
        }

        if ($rutaAnterior && is_file(public_path($rutaAnterior))) {
            @unlink(public_path($rutaAnterior));
        }

        $nombreOriginal = $archivo->getClientOriginalName();
        $extension = $archivo->getClientOriginalExtension();
        $base = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $baseSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', $base) ?: 'orden_materiales';
        $nombreAlmacenado = 'op' . $ordenId . '_' . time() . '_' . $baseSeguro . ($extension ? '.' . $extension : '');
        $archivo->move($destino, $nombreAlmacenado);

        return [
            'ruta' => $carpetaRelativa . '/' . $nombreAlmacenado,
            'nombre' => $nombreOriginal,
        ];
    }

    /**
     * @return array{ruta: string, nombre: string}
     */
    private function guardarArchivoOp($archivo, int $ordenId, ?string $rutaAnterior = null): array
    {
        $carpetaRelativa = 'archivos/ordenes_produccion';
        $destino = public_path($carpetaRelativa);
        if (!is_dir($destino)) {
            mkdir($destino, 0755, true);
        }

        if ($rutaAnterior && is_file(public_path($rutaAnterior))) {
            @unlink(public_path($rutaAnterior));
        }

        $nombreOriginal = $archivo->getClientOriginalName();
        $extension = $archivo->getClientOriginalExtension();
        $base = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $baseSeguro = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $base) ?: 'archivo_op';
        $nombreAlmacenado = 'op' . $ordenId . '_' . time() . '_' . $baseSeguro . ($extension ? '.' . $extension : '');
        $archivo->move($destino, $nombreAlmacenado);

        return [
            'ruta' => $carpetaRelativa . '/' . $nombreAlmacenado,
            'nombre' => $nombreOriginal,
        ];
    }
}