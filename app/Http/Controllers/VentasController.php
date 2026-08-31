<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\Carga;
use App\Models\Chofer;
use App\Models\Clientes;
use App\Models\CondicionPago;
use App\Models\facturacionproductos;
use App\Models\Moneda;
use App\Models\OrdenCompra;
use App\Models\OrdenCompra_det;
use App\Models\OrdenProduccion;
use App\Models\OrdenProduccionDetalle;
use App\Models\ProductoConexionEspecificacion;
use App\Models\ProductoFlangeEspecificacion;
use App\Models\ProductoTuboEspecificacion;
use App\Models\Productos;
use App\Models\Receta;
use App\Models\ReporteNoExistencia;
use App\Models\RutaCarga;
use App\Models\TipoFlete;
use App\Models\TipoIva;
use App\Models\User;
use App\Models\VentaCotizacion;
use App\Models\VentaCotizacionDetalle;
use App\Models\VentaPedido;
use App\Models\VentaPedidoDetalle;
use App\Traits\AlmacenesTraits;
use App\Traits\DatosimpleTraits;
use App\Traits\MenuTrait;
use App\Traits\ProductosTraits;
use App\Traits\SistemasTraits;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VentasController extends Controller
{
    use MenuTrait;
    use AlmacenesTraits;
    use ProductosTraits;
    use DatosimpleTraits;
    use SistemasTraits;

    private const SESSION_BORRADOR = 'ventas_cotizacion_borrador';

    private const SESSION_BORRADOR_PEDIDO = 'ventas_pedido_borrador';

    /** Estatus que permiten modificar encabezado, líneas y agregar productos. */
    private const ESTATUS_COTIZACION_EDITABLE = ['BORRADOR', 'ENVIADA', 'ACEPTADA'];

    /** Estatus de cotización que pueden convertirse a pedido. */
    private const ESTATUS_COTIZACION_CONVERTIBLE_PEDIDO = ['ENVIADA', 'ACEPTADA'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function cotizaciones(): View
    {
        return $this->pedidos(request());
    }

    public function pedidos(Request $request): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $esMaster = $this->usuarioEsMaster();
        $puedeEditarPrecio = $this->puedeEditarPrecioVenta();
        $vendedorId = $esMaster
            ? (int) $request->input('vendedor_id', auth()->id())
            : (int) auth()->id();
        $almacenGeneral = $this->obtenerAlmacenGeneral();
        $ubicaciones = $almacenGeneral
            ? $this->Listadoubicacionesxidalmacen((int) $almacenGeneral->id)
            : collect();

        $abrirModalCotizacion = $request->boolean('cotizacion')
            || $request->boolean('abrir_cotizacion')
            || session('abrir_modal_cotizacion');

        $borrador = $this->obtenerBorrador();
        $totales = $this->calcularTotales(
            $borrador['lineas'],
            (float) ($borrador['encabezado']['descuento'] ?? 0),
            (float) ($borrador['encabezado']['importe_flete'] ?? 0),
            (bool) ($borrador['encabezado']['flete_en_precios'] ?? false),
            (bool) ($borrador['encabezado']['iva_en_precios'] ?? false),
            (float) ($borrador['encabezado']['porcentaje_iva'] ?? $this->porcentajeIvaPorDefecto())
        );
        $borrador = $this->borradorConMontosVisibles($borrador, $totales);

        $borradorPedido = $this->obtenerBorradorPedido();
        $totalesPedido = $this->calcularTotales(
            $borradorPedido['lineas'],
            (float) ($borradorPedido['encabezado']['descuento'] ?? 0),
            (float) ($borradorPedido['encabezado']['importe_flete'] ?? 0),
            (bool) ($borradorPedido['encabezado']['flete_en_precios'] ?? false),
            (bool) ($borradorPedido['encabezado']['iva_en_precios'] ?? false),
            (float) ($borradorPedido['encabezado']['porcentaje_iva'] ?? $this->porcentajeIvaPorDefecto())
        );
        $borradorPedido = $this->borradorConMontosVisibles($borradorPedido, $totalesPedido);

        $abrirModalNuevoPedido = $request->boolean('abrir_pedido_nuevo')
            || session('abrir_modal_pedido_nuevo')
            || old('contexto_formulario') === 'nuevo_pedido';

        // Activas: sin CONVERTIDA (ya son pedido → historial), ni RECHAZADA/VENCIDA.
        $cotizaciones = VentaCotizacion::with(['cliente', 'detalles.producto'])
            ->withCount('detalles')
            ->where('usuario_id', $vendedorId)
            ->whereNotIn('estatus', ['RECHAZADA', 'VENCIDA', 'CONVERTIDA'])
            ->orderByDesc('id')
            ->get()
            ->map(function (VentaCotizacion $cotizacion) {
                $faltantes = $this->analizarFaltantesCotizacion($cotizacion);
                $cotizacion->tiene_faltantes_existencia = ($faltantes['total'] > 0);
                $cotizacion->tiene_faltantes_compra = count($faltantes['comprables']) > 0;

                return $cotizacion;
            });

        $cotizacionesDisponiblesPedido = $this->obtenerCotizacionesDisponiblesParaPedido($vendedorId);

        $vendedores = User::query()
            ->whereIn('id', VentaCotizacion::query()->distinct()->pluck('usuario_id')->push(auth()->id())->unique()->filter())
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($vendedores->isEmpty()) {
            $vendedores = collect([auth()->user()]);
        }

        $clientes = $this->obtenerClientesActivos();
        $clientes = $this->enriquecerClientesConPersonaAtencion($clientes);
        $monedas = Moneda::where('estatus', 'A')->orderBy('codigo')->get();
        $tiposFlete = TipoFlete::where('estatus', 'A')->orderBy('nombre')->get();
        $tiposIva = TipoIva::where('estatus', 'A')->orderBy('porcentaje')->orderBy('nombre')->get();
        $condicionesPago = CondicionPago::where('estatus', 'A')->orderBy('dias_credito')->orderBy('nombre')->get();

        $pedidos = VentaPedido::with(['cliente', 'cotizacion'])
            ->where('usuario_id', $vendedorId)
            ->orderByDesc('id')
            ->get();

        $pedidoIdModal = (int) $request->input('pedido_id', 0);
        $abrirModalPedido = $request->boolean('abrir_pedido') || session('abrir_modal_pedido');
        $pedidoSeleccionado = null;
        $facturaPedido = null;
        $facturasPedido = collect();
        $faltantesPedido = ['producibles' => [], 'comprables' => [], 'total' => 0];

        if ($pedidoIdModal > 0) {
            $pedidoSeleccionado = VentaPedido::with(['cliente', 'cotizacion', 'usuario', 'detalles.producto.unidadMedida', 'ordenProduccion', 'carga.ruta', 'moneda', 'tipoFlete', 'tipoIva', 'condicionPago'])
                ->where('id', $pedidoIdModal)
                ->where('usuario_id', $vendedorId)
                ->first();

            if ($pedidoSeleccionado) {
                $faltantesPedido = $this->analizarFaltantesPedido($pedidoSeleccionado);
                $facturasPedido = $this->obtenerFacturasPedido(
                    (int) $pedidoSeleccionado->id,
                    (string) ($pedidoSeleccionado->folio ?? '')
                );
                $facturaPedido = $facturasPedido->first();
            }
        }

        return view('Ventas.pedidos', compact(
            'varpantallas',
            'varsubmenus',
            'cotizaciones',
            'cotizacionesDisponiblesPedido',
            'pedidos',
            'vendedores',
            'vendedorId',
            'clientes',
            'monedas',
            'tiposFlete',
            'tiposIva',
            'condicionesPago',
            'almacenGeneral',
            'ubicaciones',
            'borrador',
            'totales',
            'borradorPedido',
            'totalesPedido',
            'abrirModalCotizacion',
            'abrirModalNuevoPedido',
            'abrirModalPedido',
            'pedidoSeleccionado',
            'facturaPedido',
            'facturasPedido',
            'faltantesPedido',
            'pedidoIdModal',
            'esMaster',
            'puedeEditarPrecio'
        ));
    }

    /**
     * Logística: rutas de envío con pedidos facturados y confirmados.
     */
    public function logistica(Request $request): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $rutas = RutaCarga::with([
                'camion',
                'chofer',
                'cargas' => fn ($q) => $q->orderBy('orden_entrega')->orderBy('id'),
                'cargas.pedido.cliente.ciudad',
            ])
            ->withCount('cargas')
            ->where('estatus', RutaCarga::ESTATUS_ARMADA)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        $rutaId = (int) $request->input('ruta_id', 0);
        $rutaSeleccionada = null;
        if ($rutaId > 0) {
            $rutaSeleccionada = $rutas->firstWhere('id', $rutaId);
        }

        $pedidosDisponibles = $this->obtenerPedidosDisponiblesLogistica();
        $camiones = Camion::activos()->orderBy('placas')->get();
        $choferes = Chofer::activos()->with('empleado')->orderBy('nombre')->get();
        $abrirNuevaRuta = $request->boolean('nueva') || old('abrir_nueva_ruta');

        return view('Ventas.logistica', compact(
            'varpantallas',
            'varsubmenus',
            'rutas',
            'rutaSeleccionada',
            'pedidosDisponibles',
            'camiones',
            'choferes',
            'abrirNuevaRuta'
        ));
    }

    /**
     * Crea una ruta logística con pedidos facturados + confirmados.
     */
    public function crearRutaLogistica(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'camion_id' => ['nullable', 'integer', 'exists:tbl_camiones,id'],
            'chofer_id' => ['nullable', 'integer', 'exists:tbl_choferes,id'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'pedido_ids' => ['required', 'array', 'min:1'],
            'pedido_ids.*' => ['integer', 'exists:tbl_pedidos,id'],
        ], [
            'pedido_ids.required' => 'Seleccione al menos un pedido facturado y confirmado.',
            'pedido_ids.min' => 'Seleccione al menos un pedido facturado y confirmado.',
        ]);

        $pedidoIds = array_values(array_unique(array_map('intval', $validated['pedido_ids'])));

        try {
            $ruta = DB::transaction(function () use ($validated, $pedidoIds) {
                $camion = !empty($validated['camion_id'])
                    ? Camion::find((int) $validated['camion_id'])
                    : null;
                $chofer = !empty($validated['chofer_id'])
                    ? Chofer::find((int) $validated['chofer_id'])
                    : null;

                $cargaIds = $this->crearCargasDesdePedidosLogistica($pedidoIds);
                if ($cargaIds === []) {
                    throw ValidationException::withMessages([
                        'pedido_ids' => 'Ningún pedido seleccionado está disponible (debe estar CONFIRMADO, facturado y sin carga).',
                    ]);
                }

                $ruta = RutaCarga::create([
                    'folio' => $this->generarFolioRutaLogistica(),
                    'fecha' => $validated['fecha'],
                    'estatus' => RutaCarga::ESTATUS_ARMADA,
                    'camion_id' => $camion?->id,
                    'unidad' => $camion
                        ? ($camion->placas . ($camion->nombre ? ' — ' . $camion->nombre : ''))
                        : null,
                    'chofer_id' => $chofer?->id,
                    'chofer_nombre' => $chofer?->nombre,
                    'chofer_tipo' => $chofer?->tipo,
                    'observaciones' => $validated['observaciones'] ?? null,
                    'creado_por' => auth()->id(),
                ]);

                $this->asignarCargasARutaLogistica($ruta, $cargaIds);

                return $ruta;
            });
        } catch (ValidationException $e) {
            return redirect()
                ->route('ventas.pedidos.logistica', ['nueva' => 1])
                ->withInput($request->all() + ['abrir_nueva_ruta' => 1])
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('ventas.pedidos.logistica', ['ruta_id' => $ruta->id])
            ->with('success', 'Ruta ' . $ruta->folio . ' creada con ' . $ruta->cargas()->count() . ' pedido(s).');
    }

    /**
     * Agrega más pedidos facturados/confirmados a una ruta existente.
     */
    public function agregarPedidosRutaLogistica(Request $request, int $rutaId): RedirectResponse
    {
        $ruta = RutaCarga::findOrFail($rutaId);
        if ($ruta->estaCerrada()) {
            return redirect()
                ->route('ventas.pedidos.logistica', ['ruta_id' => $ruta->id])
                ->with('warning', 'La ruta ya está cerrada; no se pueden agregar pedidos.');
        }

        $validated = $request->validate([
            'pedido_ids' => ['required', 'array', 'min:1'],
            'pedido_ids.*' => ['integer', 'exists:tbl_pedidos,id'],
        ], [
            'pedido_ids.required' => 'Seleccione al menos un pedido para agregar.',
        ]);

        $pedidoIds = array_values(array_unique(array_map('intval', $validated['pedido_ids'])));

        try {
            DB::transaction(function () use ($ruta, $pedidoIds) {
                $cargaIds = $this->crearCargasDesdePedidosLogistica($pedidoIds);
                if ($cargaIds === []) {
                    throw ValidationException::withMessages([
                        'pedido_ids' => 'Ningún pedido seleccionado está disponible para agregar.',
                    ]);
                }
                $this->asignarCargasARutaLogistica($ruta, $cargaIds);
            });
        } catch (ValidationException $e) {
            return redirect()
                ->route('ventas.pedidos.logistica', ['ruta_id' => $ruta->id])
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('ventas.pedidos.logistica', ['ruta_id' => $ruta->id])
            ->with('success', 'Pedidos agregados a la ruta ' . $ruta->folio . '.');
    }

    /**
     * Elimina una ruta y libera sus pedidos para reasignarlos a otra.
     */
    public function eliminarRutaLogistica(int $rutaId): RedirectResponse
    {
        $ruta = RutaCarga::with('cargas.pedido')->findOrFail($rutaId);

        if ($ruta->estaCerrada()) {
            return redirect()
                ->route('ventas.pedidos.logistica', ['ruta_id' => $ruta->id])
                ->with('warning', 'No se puede eliminar una ruta ya cerrada / con salida.');
        }

        $tieneDespachadas = $ruta->cargas->contains(
            fn (Carga $c) => $c->estatus === Carga::ESTATUS_DESPACHADA
        );
        if ($tieneDespachadas) {
            return redirect()
                ->route('ventas.pedidos.logistica', ['ruta_id' => $ruta->id])
                ->with('warning', 'No se puede eliminar: hay pedidos ya despachados en esta ruta.');
        }

        $folio = $ruta->folio;
        $pedidosLiberados = $ruta->cargas->count();

        DB::transaction(function () use ($ruta) {
            // Una carga = un pedido; al borrarlas quedan libres para otra ruta.
            Carga::where('ruta_id', $ruta->id)->delete();
            $ruta->evidencias()->delete();
            $ruta->delete();
        });

        return redirect()
            ->route('ventas.pedidos.logistica')
            ->with('success', "Ruta {$folio} eliminada. Se liberaron {$pedidosLiberados} pedido(s) para reasignar.");
    }

    /**
     * Quita un pedido de la ruta y lo deja disponible para otra.
     */
    public function quitarPedidoRutaLogistica(int $rutaId, int $cargaId): RedirectResponse
    {
        $ruta = RutaCarga::findOrFail($rutaId);

        if ($ruta->estaCerrada()) {
            return redirect()
                ->route('ventas.pedidos.logistica', ['ruta_id' => $ruta->id])
                ->with('warning', 'La ruta está cerrada; no se pueden quitar pedidos.');
        }

        $carga = Carga::where('id', $cargaId)->where('ruta_id', $ruta->id)->firstOrFail();

        if ($carga->estatus === Carga::ESTATUS_DESPACHADA) {
            return redirect()
                ->route('ventas.pedidos.logistica', ['ruta_id' => $ruta->id])
                ->with('warning', 'No se puede liberar un pedido ya despachado.');
        }

        $pedidoFolio = $carga->pedido?->folio ?? ('#' . $carga->pedido_id);

        DB::transaction(function () use ($carga, $ruta) {
            $carga->delete();
            $ruta->recalcularOrdenes(false);
        });

        return redirect()
            ->route('ventas.pedidos.logistica', ['ruta_id' => $ruta->id])
            ->with('success', "Pedido {$pedidoFolio} liberado; ya puede asignarse a otra ruta.");
    }

    /**
     * Pedidos CONFIRMADO + CFDI vigente + sin carga (listos para armar ruta).
     *
     * @return \Illuminate\Support\Collection<int, VentaPedido>
     */
    private function obtenerPedidosDisponiblesLogistica()
    {
        $pedidoIdsFacturados = facturacionproductos::query()
            ->where('tipo_serv', 'pedido_venta')
            ->whereNotNull('Uuid')
            ->where('Uuid', '!=', '')
            ->where(function ($q) {
                $q->whereNull('cancelada')->orWhere('cancelada', 'A');
            })
            ->whereNotNull('id_serv_enc')
            ->where('id_serv_enc', '>', 0)
            ->pluck('id_serv_enc')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($pedidoIdsFacturados === []) {
            return collect();
        }

        return VentaPedido::with(['cliente.ciudad', 'detalles'])
            ->where('estatus', 'CONFIRMADO')
            ->whereDoesntHave('carga')
            ->whereIn('id', $pedidoIdsFacturados)
            ->orderBy('fecha_entrega')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    /**
     * @param  array<int, int>  $pedidoIds
     * @return array<int, int>
     */
    private function crearCargasDesdePedidosLogistica(array $pedidoIds): array
    {
        $disponibles = $this->obtenerPedidosDisponiblesLogistica()
            ->whereIn('id', $pedidoIds)
            ->keyBy('id');

        $ids = [];
        foreach ($pedidoIds as $pedidoId) {
            $pedido = $disponibles->get($pedidoId);
            if (!$pedido) {
                continue;
            }

            // Un pedido solo puede tener una carga / una ruta.
            $cargaExistente = Carga::where('pedido_id', $pedido->id)->lockForUpdate()->first();
            if ($cargaExistente) {
                if ($cargaExistente->ruta_id !== null) {
                    throw ValidationException::withMessages([
                        'pedido_ids' => 'El pedido ' . ($pedido->folio ?? '#' . $pedido->id)
                            . ' ya está asignado a otra ruta y no puede duplicarse.',
                    ]);
                }
                $ids[] = (int) $cargaExistente->id;
                continue;
            }

            $pedido->loadMissing(['cliente.ciudad', 'ordenProduccion']);

            $carga = Carga::create([
                'folio' => $this->generarFolioCargaLogistica(),
                'pedido_id' => $pedido->id,
                'orden_produccion_id' => $pedido->ordenProduccion?->id,
                'estatus' => Carga::ESTATUS_PROGRAMADA,
                'destino_texto' => Carga::destinoDesdeCliente($pedido->cliente),
                'fecha_programada' => now()->toDateString(),
                'avisado_por' => auth()->id(),
                'avisado_at' => now(),
                'observaciones' => 'Incluido desde Logística (pedido facturado y confirmado).',
            ]);

            $ids[] = (int) $carga->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, int>  $cargaIds
     */
    private function asignarCargasARutaLogistica(RutaCarga $ruta, array $cargaIds): void
    {
        $cargas = Carga::with('pedido.cliente.ciudad')
            ->whereIn('id', $cargaIds)
            ->whereNull('ruta_id')
            ->get();

        if ($cargas->isEmpty()) {
            throw ValidationException::withMessages([
                'pedido_ids' => 'Los pedidos seleccionados ya están en otra ruta.',
            ]);
        }

        $siguiente = ((int) $ruta->cargas()->max('orden_entrega')) + 1;

        foreach ($cargas as $carga) {
            $destino = Carga::destinoDesdeCliente($carga->pedido?->cliente);
            if ($destino) {
                $carga->destino_texto = $destino;
            }
            $carga->ruta_id = $ruta->id;
            $carga->estatus = Carga::ESTATUS_EN_RUTA;
            $carga->orden_entrega = $siguiente++;
            $carga->save();
        }

        $ruta->recalcularOrdenes(false);
    }

    private function generarFolioRutaLogistica(): string
    {
        $prefijo = 'RT-' . now()->format('Ymd') . '-';
        $ultimo = RutaCarga::where('folio', 'like', $prefijo . '%')
            ->orderByDesc('id')
            ->value('folio');

        $consecutivo = 1;
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $consecutivo = ((int) $m[1]) + 1;
        }

        return $prefijo . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    private function generarFolioCargaLogistica(): string
    {
        $prefijo = 'CG-' . now()->format('Ymd') . '-';
        $ultimo = Carga::where('folio', 'like', $prefijo . '%')
            ->orderByDesc('id')
            ->value('folio');

        $consecutivo = 1;
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $consecutivo = ((int) $m[1]) + 1;
        }

        return $prefijo . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Historial de cotizaciones: el usuario ve las suyas; perfil Master ve todas.
     */
    public function historialCotizaciones(Request $request): View
    {
        $varpantallas = $this->Traermenuenc();
        $varsubmenus = $this->Traermenudet();

        $esMaster = $this->usuarioEsMaster();
        $vendedorFiltro = $request->input('vendedor_id');
        $vendedorId = $esMaster
            ? (int) ($vendedorFiltro ?? 0)
            : (int) auth()->id();
        $buscar = trim((string) $request->input('buscar', ''));
        $estatus = $request->input('estatus');

        $query = VentaCotizacion::with(['cliente', 'usuario'])
            ->withCount('detalles')
            ->orderByDesc('id');

        if (!$esMaster) {
            $query->where('usuario_id', auth()->id());
        } elseif ($vendedorFiltro !== null && $vendedorFiltro !== '') {
            $query->where('usuario_id', (int) $vendedorFiltro);
        }

        if ($estatus && in_array($estatus, ['BORRADOR', 'ENVIADA', 'ACEPTADA', 'RECHAZADA', 'VENCIDA', 'CONVERTIDA'], true)) {
            $query->where('estatus', $estatus);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('folio', 'like', "%{$buscar}%")
                    ->orWhere('observaciones', 'like', "%{$buscar}%")
                    ->orWhereHas('cliente', function ($c) use ($buscar) {
                        $c->where('nombre', 'like', "%{$buscar}%");
                    });
            });
        }

        $cotizaciones = $query->paginate(25)->withQueryString();

        $vendedores = collect();
        if ($esMaster) {
            $vendedores = User::query()
                ->whereIn('id', VentaCotizacion::query()->distinct()->pluck('usuario_id')->filter())
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('Ventas.historial_cotizaciones', compact(
            'varpantallas',
            'varsubmenus',
            'cotizaciones',
            'vendedores',
            'vendedorId',
            'esMaster',
            'buscar',
            'estatus'
        ));
    }

    /**
     * Descarga el archivo que el cliente envió para cotizar.
     */
    public function descargarArchivoClienteCotizacion(Request $request, int $id)
    {
        $cotizacion = VentaCotizacion::findOrFail($id);
        $this->validarAccesoCotizacion($cotizacion);

        if (!$cotizacion->archivo_cliente_ruta) {
            abort(404, 'Esta cotización no tiene archivo del cliente.');
        }

        $rutaAbsoluta = public_path($cotizacion->archivo_cliente_ruta);
        if (!is_file($rutaAbsoluta)) {
            abort(404, 'No se encontró el archivo en el servidor.');
        }

        $nombre = $cotizacion->archivo_cliente_nombre ?: basename($rutaAbsoluta);

        return response()->download($rutaAbsoluta, $nombre);
    }

    /**
     * JSON: coincidencias de productos en la ubicación (autocompletado).
     */
    public function productosCoincidenciasCotizacion(Request $request): JsonResponse
    {
        $request->validate([
            'id_ubicacion' => ['required', 'integer', 'exists:tblubicaciones,id'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $idUbicacion = (int) $request->input('id_ubicacion');
        $termino = trim((string) $request->input('q', ''));

        if (mb_strlen($termino) < 2) {
            return response()->json([
                'success' => true,
                'coincidencias' => [],
            ]);
        }

        $this->validarUbicacionAlmacenGeneral($idUbicacion);
        $almacenGeneral = $this->obtenerAlmacenGeneral();
        $productos = $this->productosParaCotizacion(
            $idUbicacion,
            $termino,
            $almacenGeneral ? (int) $almacenGeneral->id : null
        );

        $coincidencias = $productos->take(15)->map(function ($producto) {
            return [
                'producto_id' => (int) $producto->id_producto,
                'sku' => $producto->sku,
                'nombre' => $producto->nombrepro,
                'etiqueta' => trim(($producto->sku ?: '—') . ' — ' . $producto->nombrepro),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'coincidencias' => $coincidencias,
        ]);
    }

    /**
     * JSON: detalle de un producto en la ubicación (al seleccionar del buscador).
     */
    public function productoCotizacionDetalle(Request $request, int $productoId): JsonResponse
    {
        $request->validate([
            'id_ubicacion' => ['required', 'integer', 'exists:tblubicaciones,id'],
        ]);

        $idUbicacion = (int) $request->input('id_ubicacion');
        $this->validarUbicacionAlmacenGeneral($idUbicacion);

        $producto = $this->obtenerProductoEnUbicacion($idUbicacion, $productoId);
        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'El producto no está registrado en la ubicación seleccionada.',
            ], 404);
        }

        $disponible = max(0, (float) $producto->cantidad_existente - (float) $producto->cantidad_reservada);

        return response()->json([
            'success' => true,
            'producto' => [
                'producto_id' => (int) $producto->id_producto,
                'sku' => $producto->sku,
                'nombre' => $producto->nombrepro,
                'descripcion' => $producto->descripcion,
                'ubicacion' => $producto->ubi,
                'disponible' => $disponible,
                'precio_unitario' => round((float) ($producto->costo_venta ?? $producto->precio_unitario ?? 0), 2),
                'sin_existencia' => $disponible <= 0,
            ],
        ]);
    }

    public function buscarProductosCotizacion(Request $request): RedirectResponse
    {
        $params = array_filter([
            'vendedor_id' => $request->input('vendedor_id', auth()->id()),
            'id_ubicacion' => $request->input('id_ubicacion'),
            'buscar' => $request->input('buscar'),
            'cliente_id' => $request->input('cliente_id'),
            'fecha_vencimiento' => $request->input('fecha_vencimiento'),
            'observaciones' => $request->input('observaciones'),
            'descuento' => $request->input('descuento'),
            'estatus' => $request->input('estatus'),
        ], function ($valor) {
            return $valor !== null && $valor !== '';
        });

        $params['cotizacion'] = 1;

        return redirect()->route('ventas.pedidos', $params);
    }

    /**
     * Carga una cotización guardada en el borrador de sesión y abre el modal.
     */
    public function cargarCotizacion(Request $request, int $id): RedirectResponse
    {
        $vendedorId = (int) $request->input('vendedor_id', auth()->id());

        $cotizacion = VentaCotizacion::with(['detalles.producto.unidadMedida'])
            ->where('id', $id)
            ->firstOrFail();

        $this->validarAccesoCotizacion($cotizacion);

        $descuentoPorcentaje = 0;
        if ((float) $cotizacion->subtotal > 0) {
            $descuentoPorcentaje = round(((float) $cotizacion->descuento / (float) $cotizacion->subtotal) * 100, 2);
        }

        $lineas = [];
        foreach ($cotizacion->detalles as $detalle) {
            $producto = $detalle->producto;
            $productoId = (int) $detalle->producto_id;
            $cantidad = (float) $detalle->cantidad;
            $linea = [
                'producto_id' => $productoId,
                'sku' => $producto->sku ?? '',
                'nombre' => $producto->nombre ?? $detalle->descripcion,
                'descripcion' => $detalle->descripcion,
                'ubicacion' => '',
                'cantidad' => $cantidad,
                'precio_unitario' => (float) $detalle->precio_unitario,
                'descuento' => (float) $detalle->descuento,
                'existencia_disponible' => 0,
                'sin_existencia' => false,
                'importe' => (float) $detalle->importe,
                'detalle_id' => (int) $detalle->id,
                'unidad' => $this->resolverUnidadProducto($productoId),
                'es_produccion' => false,
            ];

            if ($productoId > 0 && $this->productoEsProducible($productoId)) {
                $materiales = $this->materialesRecetaProducto($productoId, $cantidad);
                $linea['es_produccion'] = true;
                $linea['tiene_receta'] = $materiales['tiene_receta'];
                $linea['materiales'] = $materiales['materiales'];
                $linea['materiales_suficientes'] = $materiales['tiene_receta'] ? $materiales['producible'] : true;
                $linea['tipo_proceso'] = $producto->tipo_proceso ?? Productos::TIPO_PROCESO_TUBO;
            }

            $lineas[] = $linea;
        }

        session([self::SESSION_BORRADOR => [
            'lineas' => $lineas,
            'encabezado' => [
                'cotizacion_id' => (int) $cotizacion->id,
                'folio' => $cotizacion->folio,
                'cliente_id' => $cotizacion->cliente_id,
                'persona_atencion' => $cotizacion->persona_atencion,
                'fecha_vencimiento' => $cotizacion->fecha_vencimiento
                    ? $cotizacion->fecha_vencimiento->format('Y-m-d')
                    : null,
                'observaciones' => $cotizacion->observaciones,
                'tiempo_entrega' => $cotizacion->tiempo_entrega,
                'moneda_id' => $cotizacion->moneda_id,
                'tipo_flete_id' => $cotizacion->tipo_flete_id,
                'condicion_pago_id' => $cotizacion->condicion_pago_id,
                'importe_flete' => (float) ($cotizacion->importe_flete ?? 0),
                'flete_en_precios' => (bool) ($cotizacion->flete_en_precios ?? false),
                'iva_en_precios' => (bool) ($cotizacion->iva_en_precios ?? false),
                'tipo_iva_id' => $cotizacion->tipo_iva_id,
                'porcentaje_iva' => (float) ($cotizacion->porcentaje_iva ?? $this->porcentajeIvaPorDefecto()),
                'descuento' => $descuentoPorcentaje,
                'id_ubicacion' => null,
                'buscar' => '',
                'estatus' => $cotizacion->estatus,
                'archivo_cliente_ruta' => $cotizacion->archivo_cliente_ruta,
                'archivo_cliente_nombre' => $cotizacion->archivo_cliente_nombre,
            ],
        ]]);

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $vendedorId])
            ->with('abrir_modal_cotizacion', true);
    }

    /**
     * Descarga el formato PDF de la cotización y la marca como ENVIADA si aplica.
     */
    public function descargarCotizacionPdf(Request $request, int $id)
    {
        $vendedorId = (int) $request->input('vendedor_id', auth()->id());

        $cotizacion = VentaCotizacion::with([
                'cliente.personasAtencion',
                'usuario',
                'detalles.producto.unidadMedida',
                'moneda',
                'tipoFlete',
                'tipoIva',
                'condicionPago',
            ])
            ->where('id', $id)
            ->where('usuario_id', $vendedorId)
            ->firstOrFail();

        if (in_array($cotizacion->estatus, ['BORRADOR', 'ACEPTADA'], true)) {
            $cotizacion->update([
                'estatus' => 'ENVIADA',
                'updated_at' => now(),
            ]);
            $cotizacion->estatus = 'ENVIADA';
            $this->sincronizarEstatusBorrador((int) $cotizacion->id, 'ENVIADA');
            session()->flash('success', 'Formato descargado. La cotización ' . $cotizacion->folio . ' fue marcada como ENVIADA.');
        }

        $descuentoPorcentaje = 0;
        if ((float) $cotizacion->subtotal > 0) {
            $descuentoPorcentaje = round(((float) $cotizacion->descuento / (float) $cotizacion->subtotal) * 100, 2);
        }

        $cliente = $cotizacion->cliente;
        $direccionCliente = $this->formatearDireccionCliente($cliente);

        $fechaRef = $cotizacion->fecha ? Carbon::parse($cotizacion->fecha) : Carbon::now();
        $fechaEmision = $fechaRef->copy()->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        $fechaCorta = $fechaRef->copy()->locale('es')->isoFormat('D/MMM./YYYY');

        $vigenciaCorta = $cotizacion->fecha_vencimiento
            ? Carbon::parse($cotizacion->fecha_vencimiento)->locale('es')->isoFormat('D/MMM./YYYY')
            : null;

        $atencionNombre = trim((string) ($cotizacion->persona_atencion ?? ''));
        if ($atencionNombre === '') {
            $atencionNombre = $this->resolverNombrePersonaAtencion($cliente);
        }
        $atencionNombre = $atencionNombre !== '' ? strtoupper($atencionNombre) : null;

        $monedaNombre = match (strtoupper((string) ($cotizacion->moneda->codigo ?? $cotizacion->moneda->abreviacion ?? 'MXN'))) {
            'USD' => 'dolares americanos',
            'EUR' => 'euros',
            default => 'pesos',
        };

        $totalEnLetras = null;
        try {
            $formatter = new \Luecano\NumeroALetras\NumeroALetras();
            $entero = (int) floor((float) $cotizacion->total);
            $centavos = (int) round((((float) $cotizacion->total) - $entero) * 100);
            $palabras = $formatter->toWords($entero);
            $totalEnLetras = ucfirst(mb_strtolower($palabras)) . ' ' . $monedaNombre
                . ' ' . str_pad((string) $centavos, 2, '0', STR_PAD_LEFT) . '/100';
        } catch (\Throwable $e) {
            $totalEnLetras = null;
        }

        $fleteEnPrecios = (bool) ($cotizacion->flete_en_precios ?? false);
        $ivaEnPrecios = (bool) ($cotizacion->iva_en_precios ?? false);
        $porcentajeIva = (float) ($cotizacion->porcentaje_iva ?? $this->porcentajeIvaPorDefecto());
        $factorIva = $this->factorIva($porcentajeIva);
        $importeFlete = (float) ($cotizacion->importe_flete ?? 0);
        $detallesPdf = $cotizacion->detalles;
        if ($fleteEnPrecios && $importeFlete > 0 && $detallesPdf->isNotEmpty()) {
            $detallesPdf = $this->prorratearFleteEnDetalles($detallesPdf, $importeFlete);
        }
        if ($ivaEnPrecios && $detallesPdf->isNotEmpty()) {
            $detallesPdf = $this->aplicarIvaEnDetalles($detallesPdf, $porcentajeIva);
        }

        $subtotalPdf = (float) $cotizacion->subtotal;
        $descuentoPdf = (float) $cotizacion->descuento;
        if ($ivaEnPrecios) {
            $subtotalPdf = round($subtotalPdf * $factorIva, 2);
            $descuentoPdf = round($descuentoPdf * $factorIva, 2);
        }

        $pdf = Pdf::loadView('Ventas.pdf.cotizacion', [
            'cotizacion' => $cotizacion,
            'cliente' => $cliente,
            'vendedor' => $cotizacion->usuario,
            'detalles' => $detallesPdf,
            'fechaEmision' => $fechaEmision,
            'fechaCorta' => $fechaCorta,
            'vigenciaCorta' => $vigenciaCorta,
            'fechaGeneracion' => Carbon::now()->format('d/m/Y H:i'),
            'descuentoPorcentaje' => $descuentoPorcentaje,
            'direccionCliente' => $direccionCliente,
            'atencionNombre' => $atencionNombre,
            'condiciones' => $cotizacion->condicionPago->nombre ?? null,
            'totalEnLetras' => $totalEnLetras,
            'fleteEnPrecios' => $fleteEnPrecios,
            'ivaEnPrecios' => $ivaEnPrecios,
            'porcentajeIva' => $porcentajeIva,
            'importeFlete' => $importeFlete,
            'subtotalPdf' => $subtotalPdf,
            'descuentoPdf' => $descuentoPdf,
            'mostrarConceptoFlete' => !$fleteEnPrecios && $importeFlete > 0,
            'mostrarConceptoIva' => !$ivaEnPrecios,
        ]);

        $pdf->setPaper('letter', 'portrait');

        $nombreArchivo = ($cotizacion->folio ?: 'COTIZACION-' . $cotizacion->id) . '.pdf';

        return $pdf->download($nombreArchivo);
    }

    /**
     * Convierte una cotización en pedido.
     * No descuenta inventario en esta conversión.
     * Si hay faltantes de productos de compra, se registran en el reporte de no existencias.
     */
    public function convertirCotizacionAPedido(Request $request, int $id): RedirectResponse
    {
        $vendedorId = (int) $request->input('vendedor_id', auth()->id());

        $cotizacion = VentaCotizacion::with(['detalles.producto', 'cliente'])
            ->where('id', $id)
            ->where('usuario_id', $vendedorId)
            ->firstOrFail();

        if (!in_array($cotizacion->estatus, self::ESTATUS_COTIZACION_CONVERTIBLE_PEDIDO, true)) {
            throw ValidationException::withMessages([
                'cotizacion' => 'Solo se pueden convertir cotizaciones en estatus ENVIADA o ACEPTADA.',
            ]);
        }

        if ($cotizacion->detalles->isEmpty()) {
            throw ValidationException::withMessages([
                'lineas' => 'La cotización no tiene productos para convertir.',
            ]);
        }

        if (VentaPedido::where('cotizacion_id', $cotizacion->id)->exists()) {
            throw ValidationException::withMessages([
                'cotizacion' => 'Esta cotización ya tiene un pedido asociado.',
            ]);
        }

        $resultado = $this->ejecutarConversionCotizacionAPedido($cotizacion);

        session()->forget(self::SESSION_BORRADOR);

        return redirect()
            ->route('ventas.pedidos', [
                'vendedor_id' => $vendedorId,
                'abrir_pedido' => 1,
                'pedido_id' => $resultado['pedido']->id,
            ])
            ->with('success', $this->mensajeConversionCotizacionAPedido($cotizacion, $resultado));
    }

    /**
     * @return array{pedido: VentaPedido, reportes: int, producibles: int}
     */
    private function ejecutarConversionCotizacionAPedido(VentaCotizacion $cotizacion): array
    {
        $cotizacion->loadMissing(['detalles.producto', 'cliente']);

        $faltantes = $this->analizarFaltantesCotizacion($cotizacion);

        return DB::transaction(function () use ($cotizacion, $faltantes) {
            $pedido = VentaPedido::create([
                'folio' => $this->generarFolioPedido(),
                'cotizacion_id' => (int) $cotizacion->id,
                'cliente_id' => (int) $cotizacion->cliente_id,
                'persona_atencion' => $cotizacion->persona_atencion,
                'usuario_id' => auth()->id(),
                'fecha' => now(),
                'subtotal' => $cotizacion->subtotal,
                'descuento' => $cotizacion->descuento,
                'iva' => $cotizacion->iva,
                'total' => $cotizacion->total,
                'estatus' => 'CONFIRMADO',
                'observaciones' => $cotizacion->observaciones,
                'tiempo_entrega' => $cotizacion->tiempo_entrega,
                'moneda_id' => $cotizacion->moneda_id,
                'tipo_flete_id' => $cotizacion->tipo_flete_id,
                'condicion_pago_id' => $cotizacion->condicion_pago_id,
                'importe_flete' => (float) ($cotizacion->importe_flete ?? 0),
                'flete_en_precios' => (bool) ($cotizacion->flete_en_precios ?? false),
                'iva_en_precios' => (bool) ($cotizacion->iva_en_precios ?? false),
                'tipo_iva_id' => $cotizacion->tipo_iva_id,
                'porcentaje_iva' => (float) ($cotizacion->porcentaje_iva ?? $this->porcentajeIvaPorDefecto()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $detallesPedidoPorProducto = [];
            foreach ($cotizacion->detalles as $detalle) {
                $conIva = $this->montosConIvaLinea(
                    (float) $detalle->precio_unitario,
                    (float) $detalle->cantidad,
                    (float) ($detalle->descuento ?? 0),
                    (float) $detalle->importe,
                    (float) ($cotizacion->porcentaje_iva ?? $this->porcentajeIvaPorDefecto())
                );
                $pedidoDetalle = VentaPedidoDetalle::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => (int) $detalle->producto_id,
                    'descripcion' => $detalle->descripcion,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => $detalle->precio_unitario,
                    'precio_unitario_con_iva' => $detalle->precio_unitario_con_iva ?? $conIva['precio_unitario_con_iva'],
                    'descuento' => $detalle->descuento,
                    'importe' => $detalle->importe,
                    'importe_con_iva' => $detalle->importe_con_iva ?? $conIva['importe_con_iva'],
                    'created_at' => now(),
                ]);
                $detallesPedidoPorProducto[(int) $detalle->producto_id] = (int) $pedidoDetalle->id;
            }

            // No se descuenta inventario en la conversión.
            $reportes = 0;
            foreach ($faltantes['comprables'] as $fila) {
                ReporteNoExistencia::create([
                    'folio' => $this->generarFolioReporteNoExistencia(),
                    'pedido_id' => $pedido->id,
                    'pedido_detalle_id' => $detallesPedidoPorProducto[(int) $fila['producto_id']] ?? null,
                    'cotizacion_id' => $cotizacion->id,
                    'cliente_id' => $pedido->cliente_id,
                    'producto_id' => $fila['producto_id'],
                    'tipo' => ReporteNoExistencia::TIPO_PRODUCTO,
                    'descripcion' => $fila['nombre'],
                    'cantidad_solicitada' => $fila['cantidad'],
                    'existencia_disponible' => $fila['disponible'],
                    'cantidad_faltante' => $fila['faltante'],
                    'estatus' => ReporteNoExistencia::ESTATUS_PENDIENTE,
                    'observaciones' => 'Faltante al convertir cotización ' . ($cotizacion->folio ?: ('#' . $cotizacion->id))
                        . ' a pedido ' . $pedido->folio . '. Sin movimiento de almacén.',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $reportes++;
            }

            $cotizacion->update([
                'estatus' => 'CONVERTIDA',
                'updated_at' => now(),
            ]);

            return [
                'pedido' => $pedido,
                'reportes' => $reportes,
                'producibles' => count($faltantes['producibles']),
            ];
        });
    }

    /**
     * @param array{pedido: VentaPedido, reportes: int, producibles: int} $resultado
     */
    private function mensajeConversionCotizacionAPedido(VentaCotizacion $cotizacion, array $resultado): string
    {
        $pedido = $resultado['pedido'];
        $mensaje = 'Pedido ' . $pedido->folio . ' creado. La cotización ' . $cotizacion->folio . ' quedó como CONVERTIDA.';
        if ($resultado['reportes'] > 0) {
            $mensaje .= ' ' . $resultado['reportes'] . ' producto(s) sin existencia se enviaron al reporte de no existencias. No se descontó almacén.';
        } else {
            $mensaje .= ' No se descontó inventario en esta conversión.';
        }
        if ($resultado['producibles'] > 0) {
            $mensaje .= ' Hay ' . $resultado['producibles'] . ' linea(s) producible(s) pendientes; al pasar a producción se gestionan materiales.';
        }

        return $mensaje;
    }

    /**
     * Si la cotización quedó ACEPTADA, genera el pedido automáticamente.
     */
    private function convertirSiAceptada(VentaCotizacion $cotizacion, int $vendedorId, string $mensajeBase): RedirectResponse
    {
        $cotizacion->refresh();
        $cotizacion->load(['detalles.producto', 'cliente']);

        if ($cotizacion->estatus !== 'ACEPTADA') {
            return redirect()
                ->route('ventas.pedidos', ['vendedor_id' => $vendedorId])
                ->with('success', $mensajeBase);
        }

        if ($cotizacion->detalles->isEmpty()) {
            return redirect()
                ->route('ventas.pedidos', ['vendedor_id' => $vendedorId])
                ->with('success', $mensajeBase)
                ->with('warning', 'La cotización quedó ACEPTADA pero no tiene partidas para convertir a pedido.');
        }

        if (VentaPedido::where('cotizacion_id', $cotizacion->id)->exists()) {
            $pedidoExistente = VentaPedido::where('cotizacion_id', $cotizacion->id)->orderByDesc('id')->first();

            return redirect()
                ->route('ventas.pedidos', [
                    'vendedor_id' => $vendedorId,
                    'abrir_pedido' => 1,
                    'pedido_id' => $pedidoExistente?->id,
                ])
                ->with('success', $mensajeBase . ' Ya existía un pedido asociado.');
        }

        try {
            $resultado = $this->ejecutarConversionCotizacionAPedido($cotizacion);
            session()->forget(self::SESSION_BORRADOR);

            return redirect()
                ->route('ventas.pedidos', [
                    'vendedor_id' => $vendedorId,
                    'abrir_pedido' => 1,
                    'pedido_id' => $resultado['pedido']->id,
                ])
                ->with('success', $mensajeBase . ' ' . $this->mensajeConversionCotizacionAPedido($cotizacion, $resultado));
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('ventas.pedidos', [
                    'vendedor_id' => $vendedorId,
                    'abrir_cotizacion' => 1,
                ])
                ->with('success', $mensajeBase)
                ->with('warning', 'La cotización quedó ACEPTADA, pero no se pudo convertir automáticamente a pedido: ' . $e->getMessage());
        }
    }

    /**
     * Abre el modal de detalle del pedido.
     */
    public function cargarPedido(Request $request, int $id): RedirectResponse
    {
        $vendedorId = (int) $request->input('vendedor_id', auth()->id());

        VentaPedido::where('id', $id)
            ->where('usuario_id', $vendedorId)
            ->firstOrFail();

        return redirect()->route('ventas.pedidos', [
            'vendedor_id' => $vendedorId,
            'abrir_pedido' => 1,
            'pedido_id' => $id,
        ]);
    }

    /**
     * Alta rápida de tipo de flete desde cotización/pedido.
     */
    public function storeTipoFlete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:30', 'unique:tbl_tipos_flete,codigo'],
        ]);

        $nombre = trim($validated['nombre']);
        $codigo = strtoupper(trim((string) ($validated['codigo'] ?? '')));

        if ($codigo === '') {
            $base = Str::upper(Str::slug($nombre, '_'));
            $base = Str::limit(preg_replace('/[^A-Z0-9_]/', '', $base) ?: 'FLETE', 24, '');
            $codigo = $base;
            $n = 1;
            while (TipoFlete::where('codigo', $codigo)->exists()) {
                $codigo = Str::limit($base, 24, '') . '_' . $n;
                $n++;
            }
        }

        $tipo = TipoFlete::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'descripcion' => $validated['descripcion'] ?? null,
            'estatus' => 'A',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de flete agregado.',
            'tipo' => [
                'id' => $tipo->id,
                'codigo' => $tipo->codigo,
                'nombre' => $tipo->nombre,
            ],
        ]);
    }

    /**
     * Alta rápida de tipo de IVA desde cotización/pedido.
     */
    public function storeTipoIva(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:30', 'unique:tbl_tipos_iva,codigo'],
        ]);

        $nombre = trim($validated['nombre']);
        $porcentaje = round((float) $validated['porcentaje'], 2);
        $codigo = strtoupper(trim((string) ($validated['codigo'] ?? '')));

        if ($codigo === '') {
            $base = Str::upper(Str::slug($nombre, '_'));
            $base = Str::limit(preg_replace('/[^A-Z0-9_]/', '', $base) ?: 'IVA', 24, '');
            $codigo = $base;
            $n = 1;
            while (TipoIva::where('codigo', $codigo)->exists()) {
                $codigo = Str::limit($base, 24, '') . '_' . $n;
                $n++;
            }
        }

        $tipo = TipoIva::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'porcentaje' => $porcentaje,
            'descripcion' => $validated['descripcion'] ?? null,
            'estatus' => 'A',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tipo de IVA agregado.',
            'tipo' => [
                'id' => $tipo->id,
                'codigo' => $tipo->codigo,
                'nombre' => $tipo->nombre,
                'porcentaje' => (float) $tipo->porcentaje,
                'etiqueta' => $tipo->etiqueta(),
            ],
        ]);
    }

    /**
     * Paso 1 de Cargas: Ventas avisa que habrá una carga.
     * Crea el registro de carga (PROGRAMADA) y marca el pedido como CARGA_AVISADA.
     */
    public function avisarCarga(Request $request, int $id): RedirectResponse
    {
        $vendedorId = (int) $request->input('vendedor_id', auth()->id());

        $validated = $request->validate([
            'fecha_programada' => ['nullable', 'date'],
            'hora_programada' => ['nullable', 'date_format:H:i'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $pedido = VentaPedido::with(['ordenProduccion', 'carga', 'cliente.ciudad'])
            ->where('id', $id)
            ->where('usuario_id', $vendedorId)
            ->firstOrFail();

        $orden = $pedido->ordenProduccion;
        $opTerminada = $orden && $orden->estatus === OrdenProduccion::ESTATUS_TERMINADA;

        if (!$opTerminada && $pedido->estatus !== 'LISTO_PARA_CARGA') {
            throw ValidationException::withMessages([
                'carga' => 'Solo puede avisar la carga cuando la orden de producción ya está terminada.',
            ]);
        }

        if ($pedido->carga) {
            return redirect()
                ->route('ventas.pedidos', [
                    'vendedor_id' => $vendedorId,
                    'abrir_pedido' => 1,
                    'pedido_id' => $pedido->id,
                ])
                ->with('warning', 'Este pedido ya tiene una carga avisada: ' . $pedido->carga->folio);
        }

        $carga = DB::transaction(function () use ($pedido, $orden, $validated) {
            // Si venía de EN_PRODUCCION con OP terminada, alinear estatus.
            if ($pedido->estatus === 'EN_PRODUCCION') {
                $pedido->update(['estatus' => 'LISTO_PARA_CARGA', 'updated_at' => now()]);
            }

            $carga = Carga::create([
                'folio' => $this->generarFolioCarga(),
                'pedido_id' => $pedido->id,
                'orden_produccion_id' => $orden?->id,
                'estatus' => Carga::ESTATUS_PROGRAMADA,
                'destino_texto' => Carga::destinoDesdeCliente($pedido->cliente),
                'fecha_programada' => $validated['fecha_programada'] ?? now()->toDateString(),
                'hora_programada' => $validated['hora_programada'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'avisado_por' => auth()->id(),
                'avisado_at' => now(),
            ]);

            $pedido->update(['estatus' => 'CARGA_AVISADA', 'updated_at' => now()]);

            return $carga;
        });

        return redirect()
            ->route('ventas.pedidos', [
                'vendedor_id' => $vendedorId,
                'abrir_pedido' => 1,
                'pedido_id' => $pedido->id,
            ])
            ->with('success', 'Carga ' . $carga->folio . ' avisada. Ahora puede asignarla a una ruta de camión.');
    }

    private function generarFolioCarga(): string
    {
        $prefijo = 'CG-' . now()->format('Ymd') . '-';
        $ultimo = Carga::where('folio', 'like', $prefijo . '%')
            ->orderByDesc('id')
            ->value('folio');

        $consecutivo = 1;
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $consecutivo = ((int) $m[1]) + 1;
        }

        return $prefijo . str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Actualiza datos del pedido (fechas, estatus, observaciones).
     */
    public function actualizarPedido(Request $request, int $id): RedirectResponse
    {
        $vendedorId = (int) $request->input('vendedor_id', auth()->id());

        $validated = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:tblclientes,id'],
            'fecha_pedido' => ['required', 'date'],
            'hora_pedido' => ['nullable', 'date_format:H:i'],
            'fecha_entrega' => ['nullable', 'date'],
            'hora_entrega' => ['nullable', 'date_format:H:i'],
            'estatus' => ['required', 'in:CONFIRMADO,EN_PRODUCCION'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'tiempo_entrega' => ['nullable', 'string', 'max:255'],
            'persona_atencion' => ['nullable', 'string', 'max:255'],
            'moneda_id' => ['nullable', 'integer', 'exists:tblmonedas,id'],
            'tipo_flete_id' => ['nullable', 'integer', 'exists:tbl_tipos_flete,id'],
            'condicion_pago_id' => ['nullable', 'integer', 'exists:tbl_condiciones_pago,id'],
            'importe_flete' => ['nullable', 'numeric', 'min:0'],
            'flete_en_precios' => ['nullable', 'boolean'],
            'iva_en_precios' => ['nullable', 'boolean'],
            'tipo_iva_id' => ['nullable', 'integer', 'exists:tbl_tipos_iva,id'],
        ]);

        $pedido = VentaPedido::with('ordenProduccion')
            ->where('id', $id)
            ->where('usuario_id', $vendedorId)
            ->firstOrFail();

        if (in_array($pedido->estatus, ['EN_PRODUCCION', 'PENDIENTE_OC'], true) && !$this->pedidoProduccionCancelada($pedido)) {
            throw ValidationException::withMessages([
                'estatus' => 'El pedido está en producción o pendiente de materiales y no puede editarse. Solo se desbloquea si la producción fue cancelada.',
            ]);
        }

        $horaPedido = $validated['hora_pedido'] ?: '00:00';
        $fechaPedido = Carbon::parse($validated['fecha_pedido'] . ' ' . $horaPedido);
        $monedaId = $this->resolverMonedaId(
            isset($validated['moneda_id']) ? (int) $validated['moneda_id'] : null,
            (int) $validated['cliente_id']
        );
        $importeFlete = round((float) ($validated['importe_flete'] ?? $pedido->importe_flete ?? 0), 2);
        $fleteEnPrecios = $request->boolean('flete_en_precios');
        $ivaEnPrecios = $request->boolean('iva_en_precios');
        $tipoIvaId = !empty($validated['tipo_iva_id']) ? (int) $validated['tipo_iva_id'] : $this->tipoIvaIdPorDefecto();
        $porcentajeIva = $this->resolverPorcentajeIva($tipoIvaId, (float) ($pedido->porcentaje_iva ?? $this->porcentajeIvaPorDefecto()));
        $personaAtencion = $this->resolverPersonaAtencionRequest(
            $validated['persona_atencion'] ?? null,
            (int) $validated['cliente_id']
        );

        // Recalcular IVA y total con la tasa seleccionada.
        $baseProductos = round(max(0, (float) $pedido->subtotal - (float) $pedido->descuento), 2);
        $ivaRecalc = round($baseProductos * ($porcentajeIva / 100), 2);
        $baseConIva = round($baseProductos + $ivaRecalc, 2);
        $totalConFlete = $fleteEnPrecios
            ? $baseConIva
            : round($baseConIva + $importeFlete, 2);

        $mensajeExtra = '';
        $estatusAnterior = $pedido->estatus;
        DB::transaction(function () use ($pedido, $validated, $fechaPedido, $estatusAnterior, $monedaId, $importeFlete, $fleteEnPrecios, $ivaEnPrecios, $tipoIvaId, $porcentajeIva, $ivaRecalc, $totalConFlete, $personaAtencion, &$mensajeExtra) {
            $pedido->update([
                'cliente_id' => (int) $validated['cliente_id'],
                'persona_atencion' => $personaAtencion,
                'fecha' => $fechaPedido,
                'fecha_entrega' => $validated['fecha_entrega'] ?? null,
                'hora_entrega' => $validated['hora_entrega'] ?? null,
                'estatus' => $validated['estatus'],
                'observaciones' => $validated['observaciones'] ?? null,
                'tiempo_entrega' => $validated['tiempo_entrega'] ?? null,
                'moneda_id' => $monedaId,
                'tipo_flete_id' => $validated['tipo_flete_id'] ?? null,
                'condicion_pago_id' => $validated['condicion_pago_id'] ?? null,
                'importe_flete' => $importeFlete,
                'flete_en_precios' => $fleteEnPrecios,
                'iva_en_precios' => $ivaEnPrecios,
                'tipo_iva_id' => $tipoIvaId,
                'porcentaje_iva' => $porcentajeIva,
                'iva' => $ivaRecalc,
                'total' => $totalConFlete,
                'updated_at' => now(),
            ]);

            if ($validated['estatus'] === 'EN_PRODUCCION' && $estatusAnterior !== 'EN_PRODUCCION') {
                $resultado = $this->procesarPedidoAProduccionConMateriales($pedido);
                if (empty($resultado['orden_id'])) {
                    throw ValidationException::withMessages([
                        'estatus' => $resultado['mensaje'] ?: 'No fue posible enviar el pedido a producción.',
                    ]);
                }
                $mensajeExtra = $resultado['mensaje'] ?? '';
            }
        });

        return redirect()
            ->route('ventas.pedidos', [
                'vendedor_id' => $vendedorId,
                'abrir_pedido' => 1,
                'pedido_id' => $pedido->id,
            ])
            ->with('success', 'Pedido ' . $pedido->folio . ' actualizado correctamente.' . ($mensajeExtra ? ' ' . $mensajeExtra : ''));
    }

    public function limpiarBorradorPedido(Request $request): RedirectResponse
    {
        session()->forget(self::SESSION_BORRADOR_PEDIDO);

        $redirect = redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $request->input('vendedor_id', auth()->id())]);

        if ($request->boolean('abrir_modal')) {
            return $redirect->with('abrir_modal_pedido_nuevo', true);
        }

        return $redirect->with('success', 'Borrador de pedido limpiado.');
    }

    public function agregarLineaPedido(Request $request)
    {
        $this->normalizarRequestPedido($request);

        $borradorActual = $this->obtenerBorradorPedido();
        $tipoVenta = $request->input('tipo_venta', $borradorActual['encabezado']['tipo_venta'] ?? 'EXISTENCIA');
        $esProduccion = $tipoVenta === 'PRODUCCION';

        $validated = $request->validate([
            'id_ubicacion' => [$esProduccion ? 'nullable' : 'required', 'integer', 'exists:tblubicaciones,id'],
            'producto_id' => ['required', 'integer', 'exists:tblproductos,id'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'cliente_id' => ['nullable', 'integer', 'exists:tblclientes,id'],
            'cotizacion_id' => ['nullable', 'integer', 'exists:tbl_cotizaciones,id'],
            'fecha_pedido' => ['nullable', 'date'],
            'hora_pedido' => ['nullable', 'date_format:H:i'],
            'fecha_entrega' => ['nullable', 'date'],
            'hora_entrega' => ['nullable', 'date_format:H:i'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'descuento' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);

        $cantidad = round((float) $validated['cantidad'], 2);

        if ($esProduccion) {
            $nuevaLinea = $this->construirLineaProduccion((int) $validated['producto_id'], $cantidad, $validated['precio_unitario'] ?? null);
        } else {
            $this->validarUbicacionAlmacenGeneral((int) $validated['id_ubicacion']);
            $producto = $this->obtenerProductoEnUbicacion(
                (int) $validated['id_ubicacion'],
                (int) $validated['producto_id']
            );

            if (!$producto) {
                throw ValidationException::withMessages([
                    'producto_id' => 'El producto no está registrado en la ubicación seleccionada.',
                ]);
            }

            $precioUnitario = round((float) ($validated['precio_unitario'] ?? $producto->costo_venta ?? $producto->precio_unitario ?? 0), 2);

            $nuevaLinea = [
                'producto_id' => (int) $producto->id_producto,
                'sku' => $producto->sku,
                'nombre' => $producto->nombrepro,
                'descripcion' => $producto->descripcion,
                'ubicacion' => $producto->ubi,
                'id_ubicacion' => (int) $validated['id_ubicacion'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'descuento' => 0,
                'es_produccion' => false,
                'unidad' => $this->resolverUnidadProducto((int) $producto->id_producto, $producto->unidad ?? null),
            ];
            $nuevaLinea = array_merge($nuevaLinea, $this->evaluarExistenciaLineaPedido($nuevaLinea));
        }

        $nuevaLinea['importe'] = $this->calcularImporteLinea($nuevaLinea);

        if (round((float) ($nuevaLinea['precio_unitario'] ?? 0), 2) <= 0) {
            throw ValidationException::withMessages([
                'precio_unitario' => 'El producto "' . ($nuevaLinea['nombre'] ?? 'seleccionado') . '" no tiene precio unitario. Capture el precio de venta antes de agregarlo.',
            ]);
        }

        $borrador = $this->obtenerBorradorPedido();
        $borrador['encabezado'] = array_merge($borrador['encabezado'], [
            'id_ubicacion' => $esProduccion ? ($borrador['encabezado']['id_ubicacion'] ?? null) : (int) $validated['id_ubicacion'],
            'buscar' => trim((string) ($validated['buscar'] ?? '')),
            'tipo_venta' => $tipoVenta,
            'cliente_id' => $validated['cliente_id'] ?? ($borrador['encabezado']['cliente_id'] ?? null),
            'cotizacion_id' => $validated['cotizacion_id'] ?? ($borrador['encabezado']['cotizacion_id'] ?? null),
            'fecha_pedido' => $validated['fecha_pedido'] ?? ($borrador['encabezado']['fecha_pedido'] ?? null),
            'hora_pedido' => $validated['hora_pedido'] ?? ($borrador['encabezado']['hora_pedido'] ?? null),
            'fecha_entrega' => $validated['fecha_entrega'] ?? ($borrador['encabezado']['fecha_entrega'] ?? null),
            'hora_entrega' => $validated['hora_entrega'] ?? ($borrador['encabezado']['hora_entrega'] ?? null),
            'observaciones' => $validated['observaciones'] ?? ($borrador['encabezado']['observaciones'] ?? null),
            'tiempo_entrega' => $request->input('tiempo_entrega') ?? ($borrador['encabezado']['tiempo_entrega'] ?? null),
            'persona_atencion' => $request->input('persona_atencion') ?? ($borrador['encabezado']['persona_atencion'] ?? null),
            'moneda_id' => $request->input('moneda_id') ?? ($borrador['encabezado']['moneda_id'] ?? null),
            'tipo_flete_id' => $request->input('tipo_flete_id') ?? ($borrador['encabezado']['tipo_flete_id'] ?? null),
            'condicion_pago_id' => $request->input('condicion_pago_id') ?? ($borrador['encabezado']['condicion_pago_id'] ?? null),
            'importe_flete' => is_numeric($request->input('importe_flete'))
                ? (float) $request->input('importe_flete')
                : (float) ($borrador['encabezado']['importe_flete'] ?? 0),
            'flete_en_precios' => $request->has('flete_en_precios')
                ? $request->boolean('flete_en_precios')
                : (bool) ($borrador['encabezado']['flete_en_precios'] ?? false),
            'iva_en_precios' => $request->has('iva_en_precios')
                ? $request->boolean('iva_en_precios')
                : (bool) ($borrador['encabezado']['iva_en_precios'] ?? false),
            'tipo_iva_id' => $request->filled('tipo_iva_id')
                ? (int) $request->input('tipo_iva_id')
                : ($borrador['encabezado']['tipo_iva_id'] ?? $this->tipoIvaIdPorDefecto()),
            'porcentaje_iva' => $this->resolverPorcentajeIva(
                $request->filled('tipo_iva_id')
                    ? (int) $request->input('tipo_iva_id')
                    : (isset($borrador['encabezado']['tipo_iva_id']) ? (int) $borrador['encabezado']['tipo_iva_id'] : null),
                (float) ($borrador['encabezado']['porcentaje_iva'] ?? $this->porcentajeIvaPorDefecto())
            ),
            'descuento' => $validated['descuento'] ?? ($borrador['encabezado']['descuento'] ?? 0),
        ]);

        $lineaExistente = null;
        foreach ($borrador['lineas'] as $index => $linea) {
            if ((int) $linea['producto_id'] === (int) $validated['producto_id']) {
                $lineaExistente = $index;
                break;
            }
        }

        if ($lineaExistente !== null) {
            $borrador['lineas'][$lineaExistente] = $nuevaLinea;
        } else {
            $borrador['lineas'][] = $nuevaLinea;
        }

        session([self::SESSION_BORRADOR_PEDIDO => $borrador]);

        if ($esProduccion) {
            $mensaje = empty($nuevaLinea['materiales_suficientes'])
                ? 'Producto de producción agregado. Revise los materiales de la receta marcados en rojo.'
                : 'Producto de producción agregado. Materiales de receta disponibles.';
        } else {
            $mensaje = $nuevaLinea['sin_existencia']
                ? 'Producto agregado. Revise la existencia marcada en rojo (ubicación y almacén general).'
                : 'Producto agregado al pedido.';
        }

        if ($request->expectsJson()) {
            return $this->respuestaFragmentosPedido(
                $borrador,
                (int) $request->input('vendedor_id', auth()->id()),
                $mensaje
            );
        }

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $request->input('vendedor_id', auth()->id())])
            ->with('abrir_modal_pedido_nuevo', true);
    }

    public function actualizarLineaPedido(Request $request)
    {
        $this->normalizarRequestPedido($request);

        $validated = $request->validate([
            'indice' => ['required', 'integer', 'min:0'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'cliente_id' => ['nullable', 'integer', 'exists:tblclientes,id'],
            'cotizacion_id' => ['nullable', 'integer', 'exists:tbl_cotizaciones,id'],
            'fecha_pedido' => ['nullable', 'date'],
            'hora_pedido' => ['nullable', 'date_format:H:i'],
            'fecha_entrega' => ['nullable', 'date'],
            'hora_entrega' => ['nullable', 'date_format:H:i'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'descuento_global' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'id_ubicacion' => ['nullable', 'integer'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);

        $borrador = $this->obtenerBorradorPedido();
        $indice = (int) $validated['indice'];

        if (!isset($borrador['lineas'][$indice])) {
            throw ValidationException::withMessages([
                'indice' => 'La línea indicada ya no existe en el pedido.',
            ]);
        }

        $linea = $borrador['lineas'][$indice];
        $linea['cantidad'] = round((float) $validated['cantidad'], 2);
        if ($this->puedeEditarPrecioVenta() && array_key_exists('precio_unitario', $validated) && $validated['precio_unitario'] !== null) {
            $linea['precio_unitario'] = round((float) $validated['precio_unitario'], 2);
        } else {
            $linea['precio_unitario'] = round((float) ($linea['precio_unitario'] ?? 0), 2);
        }
        $linea['descuento'] = round((float) ($validated['descuento'] ?? 0), 2);
        if (!empty($linea['es_produccion'])) {
            $materiales = $this->materialesRecetaProducto((int) $linea['producto_id'], (float) $linea['cantidad']);
            $linea['materiales'] = $materiales['materiales'];
            $linea['materiales_suficientes'] = $materiales['producible'];
            $linea['tiene_receta'] = $materiales['tiene_receta'];
            $linea['sin_existencia'] = false;
        } else {
            $linea = array_merge($linea, $this->evaluarExistenciaLineaPedido($linea));
        }
        $linea['importe'] = $this->calcularImporteLinea($linea);
        $borrador['lineas'][$indice] = $linea;

        $borrador = $this->aplicarEncabezadoPedidoDesdeRequest($request, $borrador);
        if (array_key_exists('descuento_global', $validated) && $validated['descuento_global'] !== null) {
            $borrador['encabezado']['descuento'] = round((float) $validated['descuento_global'], 2);
        }

        session([self::SESSION_BORRADOR_PEDIDO => $borrador]);

        if ($request->expectsJson()) {
            return $this->respuestaFragmentosPedido(
                $borrador,
                (int) $request->input('vendedor_id', auth()->id()),
                'Línea y totales actualizados (IVA / flete).'
            );
        }

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $request->input('vendedor_id', auth()->id())])
            ->with('abrir_modal_pedido_nuevo', true);
    }

    public function quitarLineaPedido(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'indice' => ['required', 'integer', 'min:0'],
        ]);

        $borrador = $this->obtenerBorradorPedido();
        $indice = (int) $validated['indice'];

        if (isset($borrador['lineas'][$indice])) {
            array_splice($borrador['lineas'], $indice, 1);
            session([self::SESSION_BORRADOR_PEDIDO => $borrador]);
        }

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $request->input('vendedor_id', auth()->id())])
            ->with('abrir_modal_pedido_nuevo', true);
    }

    public function guardarPedido(Request $request): RedirectResponse
    {
        $this->normalizarRequestPedido($request);

        $validated = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:tblclientes,id'],
            'cotizacion_id' => ['nullable', 'integer', 'exists:tbl_cotizaciones,id'],
            'fecha_pedido' => ['required', 'date'],
            'hora_pedido' => ['nullable', 'date_format:H:i'],
            'fecha_entrega' => ['nullable', 'date'],
            'hora_entrega' => ['nullable', 'date_format:H:i'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'tiempo_entrega' => ['nullable', 'string', 'max:255'],
            'persona_atencion' => ['nullable', 'string', 'max:255'],
            'moneda_id' => ['nullable', 'integer', 'exists:tblmonedas,id'],
            'tipo_flete_id' => ['nullable', 'integer', 'exists:tbl_tipos_flete,id'],
            'condicion_pago_id' => ['nullable', 'integer', 'exists:tbl_condiciones_pago,id'],
            'importe_flete' => ['nullable', 'numeric', 'min:0'],
            'flete_en_precios' => ['nullable', 'boolean'],
            'iva_en_precios' => ['nullable', 'boolean'],
            'tipo_iva_id' => ['nullable', 'integer', 'exists:tbl_tipos_iva,id'],
            'descuento' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        if (!empty($validated['cotizacion_id'])) {
            $this->validarCotizacionDisponibleParaPedido((int) $validated['cotizacion_id'], auth()->id());
        }

        $borrador = $this->obtenerBorradorPedido();

        if (empty($borrador['lineas'])) {
            throw ValidationException::withMessages([
                'lineas' => 'Agregue al menos un producto al pedido.',
            ]);
        }

        $monedaId = $this->resolverMonedaId(
            isset($validated['moneda_id']) ? (int) $validated['moneda_id'] : null,
            (int) $validated['cliente_id']
        );
        $importeFlete = round((float) ($validated['importe_flete'] ?? 0), 2);
        $fleteEnPrecios = $request->boolean('flete_en_precios');
        $ivaEnPrecios = $request->boolean('iva_en_precios');
        $tipoIvaId = !empty($validated['tipo_iva_id']) ? (int) $validated['tipo_iva_id'] : $this->tipoIvaIdPorDefecto();
        $porcentajeIva = $this->resolverPorcentajeIva($tipoIvaId);
        $personaAtencion = $this->resolverPersonaAtencionRequest(
            $validated['persona_atencion'] ?? null,
            (int) $validated['cliente_id']
        );

        $borrador['encabezado'] = array_merge($borrador['encabezado'], [
            'cliente_id' => (int) $validated['cliente_id'],
            'persona_atencion' => $personaAtencion,
            'cotizacion_id' => $validated['cotizacion_id'] ?? null,
            'fecha_pedido' => $validated['fecha_pedido'],
            'hora_pedido' => $validated['hora_pedido'] ?? null,
            'fecha_entrega' => $validated['fecha_entrega'] ?? null,
            'hora_entrega' => $validated['hora_entrega'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'tiempo_entrega' => $validated['tiempo_entrega'] ?? null,
            'moneda_id' => $monedaId,
            'tipo_flete_id' => $validated['tipo_flete_id'] ?? null,
            'condicion_pago_id' => $validated['condicion_pago_id'] ?? null,
            'importe_flete' => $importeFlete,
            'flete_en_precios' => $fleteEnPrecios,
            'iva_en_precios' => $ivaEnPrecios,
            'tipo_iva_id' => $tipoIvaId,
            'porcentaje_iva' => $porcentajeIva,
            'descuento' => $validated['descuento'] ?? 0,
        ]);

        $erroresExistencia = [];
        foreach ($borrador['lineas'] as $indice => $linea) {
            // Las líneas de producción no se validan contra el stock del producto terminado.
            if (!empty($linea['es_produccion'])) {
                continue;
            }

            $evaluacion = $this->evaluarExistenciaLineaPedido($linea);
            $borrador['lineas'][$indice] = array_merge($linea, $evaluacion);

            if ($evaluacion['sin_existencia']) {
                $nombre = $linea['nombre'] ?? 'Producto #' . ($linea['producto_id'] ?? '');
                $erroresExistencia[] = '"' . $nombre . '": ubicación '
                    . number_format($evaluacion['existencia_disponible'], 2)
                    . ', almacén general '
                    . number_format($evaluacion['existencia_almacen_general'], 2)
                    . ', solicitado '
                    . number_format((float) $linea['cantidad'], 2) . '.';
            }
        }

        if (!empty($erroresExistencia)) {
            session([self::SESSION_BORRADOR_PEDIDO => $borrador]);

            throw ValidationException::withMessages([
                'existencia' => 'No hay existencia suficiente: ' . implode(' ', $erroresExistencia),
            ]);
        }

        $almacenGeneral = $this->obtenerAlmacenGeneral();
        if (!$almacenGeneral) {
            throw ValidationException::withMessages([
                'almacen' => 'No se encontró el almacén general configurado.',
            ]);
        }

        $descuentoPorcentaje = round((float) ($validated['descuento'] ?? 0), 2);
        $totales = $this->calcularTotales($borrador['lineas'], $descuentoPorcentaje, $importeFlete, $fleteEnPrecios, $ivaEnPrecios, $porcentajeIva);
        $horaPedido = $validated['hora_pedido'] ?: '00:00';
        $fechaPedido = Carbon::parse($validated['fecha_pedido'] . ' ' . $horaPedido);
        $vendedorId = (int) $request->input('vendedor_id', auth()->id());

        DB::beginTransaction();

        try {
            $pedido = VentaPedido::create([
                'folio' => $this->generarFolioPedido(),
                'cotizacion_id' => !empty($validated['cotizacion_id']) ? (int) $validated['cotizacion_id'] : null,
                'cliente_id' => (int) $validated['cliente_id'],
                'persona_atencion' => $personaAtencion,
                'usuario_id' => auth()->id(),
                'fecha' => $fechaPedido,
                'fecha_entrega' => $validated['fecha_entrega'] ?? null,
                'hora_entrega' => $validated['hora_entrega'] ?? null,
                'subtotal' => $totales['subtotal'],
                'descuento' => $totales['descuento_importe'],
                'iva' => $totales['iva'],
                'total' => $totales['total'],
                'estatus' => 'CONFIRMADO',
                'observaciones' => $validated['observaciones'] ?? null,
                'tiempo_entrega' => $validated['tiempo_entrega'] ?? null,
                'moneda_id' => $monedaId,
                'tipo_flete_id' => $validated['tipo_flete_id'] ?? null,
                'condicion_pago_id' => $validated['condicion_pago_id'] ?? null,
                'importe_flete' => $importeFlete,
                'flete_en_precios' => $fleteEnPrecios,
                'iva_en_precios' => $ivaEnPrecios,
                'tipo_iva_id' => $tipoIvaId,
                'porcentaje_iva' => $porcentajeIva,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($borrador['lineas'] as $linea) {
                $importe = (float) ($linea['importe'] ?? $this->calcularImporteLinea($linea));
                $conIva = $this->montosConIvaLinea(
                    (float) ($linea['precio_unitario'] ?? 0),
                    (float) ($linea['cantidad'] ?? 0),
                    (float) ($linea['descuento'] ?? 0),
                    $importe,
                    $porcentajeIva
                );
                VentaPedidoDetalle::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => (int) $linea['producto_id'],
                    'descripcion' => $linea['descripcion'] ?: $linea['nombre'],
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'precio_unitario_con_iva' => $conIva['precio_unitario_con_iva'],
                    'descuento' => $linea['descuento'],
                    'importe' => $importe,
                    'importe_con_iva' => $conIva['importe_con_iva'],
                    'created_at' => now(),
                ]);

                // El producto de producción no descuenta stock: se fabricará.
                if (empty($linea['es_produccion'])) {
                    $this->descontarExistenciaAlmacenGeneral(
                        (int) $linea['producto_id'],
                        (float) $linea['cantidad'],
                        (int) $almacenGeneral->id
                    );
                }
            }

            DB::commit();
            session()->forget(self::SESSION_BORRADOR_PEDIDO);

            return redirect()
                ->route('ventas.pedidos', [
                    'vendedor_id' => $vendedorId,
                    'abrir_pedido' => 1,
                    'pedido_id' => $pedido->id,
                ])
                ->with('success', 'Pedido ' . $pedido->folio . ' registrado correctamente. El inventario del almacén general fue actualizado.');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Analiza las líneas de un pedido y detecta faltantes de existencia,
     * clasificándolos entre productos que se pueden fabricar (producibles)
     * y productos que se compran a proveedor (comprables).
     *
     * @return array{producibles: array<int, array<string, mixed>>, comprables: array<int, array<string, mixed>>, total: int}
     */
    private function analizarFaltantesPedido(VentaPedido $pedido): array
    {
        $producibles = [];
        $comprables = [];

        foreach ($pedido->detalles as $detalle) {
            $productoId = (int) $detalle->producto_id;
            if ($productoId <= 0) {
                continue;
            }

            $cantidad = (float) $detalle->cantidad;
            $disponible = $this->obtenerExistenciaTotalAlmacenGeneral($productoId);

            if ($disponible >= $cantidad) {
                continue;
            }

            $faltante = round($cantidad - max(0, $disponible), 2);

            $fila = [
                'detalle_id' => (int) $detalle->id,
                'producto_id' => $productoId,
                'nombre' => $detalle->descripcion ?: ($detalle->producto->nombre ?? ('Producto #' . $productoId)),
                'sku' => $detalle->producto->sku ?? null,
                'cantidad' => $cantidad,
                'disponible' => max(0, $disponible),
                'faltante' => $faltante,
            ];

            if ($this->productoEsProducible($productoId)) {
                $producibles[] = $fila;
            } else {
                $comprables[] = $fila;
            }
        }

        return [
            'producibles' => $producibles,
            'comprables' => $comprables,
            'total' => count($producibles) + count($comprables),
        ];
    }

    /**
     * @return array{producibles: array<int, array<string, mixed>>, comprables: array<int, array<string, mixed>>, total: int}
     */
    private function analizarFaltantesCotizacion(VentaCotizacion $cotizacion): array
    {
        $producibles = [];
        $comprables = [];

        foreach ($cotizacion->detalles as $detalle) {
            $productoId = (int) $detalle->producto_id;
            if ($productoId <= 0) {
                continue;
            }

            $cantidad = (float) $detalle->cantidad;
            $disponible = $this->obtenerExistenciaTotalAlmacenGeneral($productoId);

            if ($disponible >= $cantidad) {
                continue;
            }

            $faltante = round($cantidad - max(0, $disponible), 2);
            $fila = [
                'detalle_id' => (int) $detalle->id,
                'producto_id' => $productoId,
                'nombre' => $detalle->descripcion ?: ($detalle->producto->nombre ?? ('Producto #' . $productoId)),
                'sku' => $detalle->producto->sku ?? null,
                'cantidad' => $cantidad,
                'disponible' => max(0, $disponible),
                'faltante' => $faltante,
            ];

            if ($this->productoEsProducible($productoId)) {
                $producibles[] = $fila;
            } else {
                $comprables[] = $fila;
            }
        }

        return [
            'producibles' => $producibles,
            'comprables' => $comprables,
            'total' => count($producibles) + count($comprables),
        ];
    }

    /**
     * Un producto es producible si tiene especificación de tubo, flange, conexión o receta activa.
     */
    private function productoEsProducible(int $productoId): bool
    {
        $producto = Productos::find($productoId);

        if ($producto && $producto->esConexion()) {
            if (ProductoConexionEspecificacion::where('producto_id', $productoId)->where('estatus', 'ACTIVO')->exists()) {
                return true;
            }
        }

        if ($producto && $producto->esSoloFlange()) {
            if (ProductoFlangeEspecificacion::where('producto_id', $productoId)->where('estatus', 'ACTIVO')->exists()) {
                return true;
            }
        }

        if (ProductoTuboEspecificacion::where('producto_id', $productoId)->where('estatus', 'ACTIVO')->exists()) {
            return true;
        }

        if (ProductoFlangeEspecificacion::where('producto_id', $productoId)->where('estatus', 'ACTIVO')->exists()) {
            return true;
        }

        if (ProductoConexionEspecificacion::where('producto_id', $productoId)->where('estatus', 'ACTIVO')->exists()) {
            return true;
        }

        return Receta::where('producto_id', $productoId)
            ->where('estatus', 'ACTIVA')
            ->exists();
    }

    /**
     * IDs de productos que se pueden vender en modo producción (spec o receta).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function idsProductosVendiblesProduccion()
    {
        $idsReceta = Receta::where('estatus', 'ACTIVA')->pluck('producto_id');
        $idsTubo = ProductoTuboEspecificacion::where('estatus', 'ACTIVO')->pluck('producto_id');
        $idsFlange = ProductoFlangeEspecificacion::where('estatus', 'ACTIVO')->pluck('producto_id');
        $idsConexion = ProductoConexionEspecificacion::where('estatus', 'ACTIVO')->pluck('producto_id');

        return $idsReceta->merge($idsTubo)->merge($idsFlange)->merge($idsConexion)->unique()->values();
    }

    /**
     * Calcula, a partir de la receta activa de un producto, los materiales (materia prima)
     * necesarios para producir la cantidad solicitada y verifica su existencia total
     * (todos los almacenes / ubicaciones).
     *
     * @return array{tiene_receta: bool, producible: bool, receta_version: mixed, materiales: array<int, array<string, mixed>>}
     */
    private function materialesRecetaProducto(int $productoId, float $cantidad): array
    {
        $receta = Receta::with(['detalles.productoMp', 'detalles.unidad'])
            ->where('producto_id', $productoId)
            ->where('estatus', 'ACTIVA')
            ->orderByDesc('id')
            ->first();

        if (!$receta) {
            return [
                'tiene_receta' => false,
                'producible' => false,
                'receta_version' => null,
                'materiales' => [],
            ];
        }

        $cantidad = max(0, $cantidad);
        $materiales = [];
        $producible = true;

        foreach ($receta->detalles as $detalle) {
            $mpId = (int) $detalle->producto_mp_id;
            $requerido = round((float) $detalle->cantidad * $cantidad, 4);
            // MP puede estar en cualquier almacén (p. ej. entrada a un almacén distinto del general).
            $disponible = $mpId > 0 ? $this->obtenerExistenciaTotalProducto($mpId) : 0.0;
            $suficiente = $disponible >= $requerido;

            if (!$suficiente) {
                $producible = false;
            }

            $materiales[] = [
                'producto_mp_id' => $mpId,
                'sku' => $detalle->productoMp->sku ?? null,
                'nombre' => $detalle->productoMp->nombre ?? ('MP #' . $mpId),
                'unidad' => $detalle->unidad->nombre ?? null,
                'cantidad_unitaria' => (float) $detalle->cantidad,
                'requerido' => $requerido,
                'disponible' => round($disponible, 4),
                'faltante' => $suficiente ? 0 : round($requerido - $disponible, 4),
                'suficiente' => $suficiente,
            ];
        }

        return [
            'tiene_receta' => true,
            'producible' => $producible,
            'receta_version' => $receta->version,
            'materiales' => $materiales,
        ];
    }

    /**
     * Construye una línea de "venta de producción" a partir del catálogo de productos.
     * Acepta productos con receta activa o con especificación de tubo/flange/conexión.
     *
     * @return array<string, mixed>
     */
    private function construirLineaProduccion(int $productoId, float $cantidad, $precioUnitario = null): array
    {
        $producto = Productos::find($productoId);
        if (!$producto) {
            throw ValidationException::withMessages([
                'producto_id' => 'El producto seleccionado no existe.',
            ]);
        }

        if (!$this->productoEsProducible($productoId)) {
            throw ValidationException::withMessages([
                'producto_id' => 'El producto "' . $producto->nombre . '" no es fabricable (sin receta ni especificación de tubo/flange/conexión).',
            ]);
        }

        $materiales = $this->materialesRecetaProducto($productoId, $cantidad);
        $precio = round((float) ($precioUnitario ?? $producto->costo_venta ?? 0), 2);

        return [
            'producto_id' => (int) $producto->id,
            'sku' => $producto->sku,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion ?? $producto->nombre,
            'ubicacion' => '',
            'id_ubicacion' => null,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'descuento' => 0,
            'es_produccion' => true,
            'existencia_disponible' => 0,
            'existencia_almacen_general' => 0,
            'sin_existencia' => false,
            'tiene_receta' => $materiales['tiene_receta'],
            'materiales' => $materiales['materiales'],
            'materiales_suficientes' => $materiales['tiene_receta'] ? $materiales['producible'] : true,
            'tipo_proceso' => $producto->tipo_proceso ?? Productos::TIPO_PROCESO_TUBO,
            'unidad' => $this->resolverUnidadProducto((int) $producto->id),
        ];
    }

    /**
     * JSON: materiales de la receta de un producto y su disponibilidad para producir
     * la cantidad solicitada. Se usa en el modo "venta de producción".
     */
    public function productoRecetaMateriales(Request $request, int $productoId): JsonResponse
    {
        $cantidad = (float) $request->input('cantidad', 1);
        if ($cantidad <= 0) {
            $cantidad = 1;
        }

        $producto = Productos::find($productoId);
        if (!$producto) {
            return response()->json(['success' => false, 'message' => 'Producto no encontrado.'], 404);
        }

        $info = $this->materialesRecetaProducto($productoId, $cantidad);
        $esProducible = $this->productoEsProducible($productoId);
        $tipoProceso = $producto->tipo_proceso ?? Productos::TIPO_PROCESO_TUBO;

        return response()->json(array_merge(['success' => true], $info, [
            'es_producible' => $esProducible,
            'tipo_proceso' => $tipoProceso,
            'fabricacion_por_especificacion' => $esProducible && empty($info['tiene_receta']),
            'producto' => [
                'producto_id' => $producto->id,
                'sku' => $producto->sku,
                'nombre' => $producto->nombre,
                'precio_unitario' => round((float) ($producto->costo_venta ?? 0), 2),
                'tipo_proceso' => $tipoProceso,
            ],
            'cantidad' => $cantidad,
        ]));
    }

    /**
     * JSON: coincidencias de productos fabricables (receta o spec tubo/flange/conexión).
     */
    public function productosProduccionCoincidencias(Request $request): JsonResponse
    {
        $termino = trim((string) $request->input('q', ''));

        if (mb_strlen($termino) < 2) {
            return response()->json(['success' => true, 'coincidencias' => []]);
        }

        $ids = $this->idsProductosVendiblesProduccion();

        $productos = Productos::whereIn('id', $ids)
            ->where(function ($w) use ($termino) {
                $w->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('sku', 'like', "%{$termino}%");
            })
            ->orderBy('nombre')
            ->limit(15)
            ->get(['id', 'sku', 'nombre', 'tipo_proceso']);

        $coincidencias = $productos->map(function ($p) {
            $tipo = match ($p->tipo_proceso ?? '') {
                Productos::TIPO_PROCESO_CONEXION => 'Conexión',
                Productos::TIPO_PROCESO_FLANGE => 'Flange',
                Productos::TIPO_PROCESO_TUBO => 'Tubo',
                default => null,
            };
            $base = trim(($p->sku ?: '—') . ' — ' . $p->nombre);

            return [
                'producto_id' => (int) $p->id,
                'sku' => $p->sku,
                'nombre' => $p->nombre,
                'tipo_proceso' => $p->tipo_proceso,
                'etiqueta' => $tipo ? $base . ' [' . $tipo . ']' : $base,
            ];
        })->values();

        return response()->json(['success' => true, 'coincidencias' => $coincidencias]);
    }

    public function establecerTipoVentaPedido(Request $request): RedirectResponse
    {
        $tipo = $request->input('tipo_venta') === 'PRODUCCION' ? 'PRODUCCION' : 'EXISTENCIA';
        $borrador = $this->obtenerBorradorPedido();
        $borrador['encabezado']['tipo_venta'] = $tipo;
        session([self::SESSION_BORRADOR_PEDIDO => $borrador]);

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $request->input('vendedor_id', auth()->id())])
            ->with('abrir_modal_pedido_nuevo', true);
    }

    public function establecerTipoVentaCotizacion(Request $request): RedirectResponse
    {
        $tipo = $request->input('tipo_venta') === 'PRODUCCION' ? 'PRODUCCION' : 'EXISTENCIA';
        $borrador = $this->obtenerBorrador();
        $borrador['encabezado']['tipo_venta'] = $tipo;
        session([self::SESSION_BORRADOR => $borrador]);

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $request->input('vendedor_id', auth()->id()), 'abrir_cotizacion' => 1]);
    }

    /**
     * Genera la orden de producción desde el pedido y gestiona materia prima:
     * - Si alcanza MP: descuenta MP, registra movimientos y deja la OP en PREPARANDO_MAQUINAS.
     * - Si no alcanza MP: genera OC+reporte y deja OP en EN_ESPERA_MATERIALES.
     *
     * @return array{orden_id:int|null,mensaje:string}
     */
    private function procesarPedidoAProduccionConMateriales(VentaPedido $pedido): array
    {
        if (OrdenProduccion::where('pedido_id', $pedido->id)->exists()) {
            return ['orden_id' => null, 'mensaje' => 'Ya existe una orden de producción para este pedido.'];
        }

        $faltantes = $this->analizarFaltantesPedido($pedido);
        if (empty($faltantes['producibles'])) {
            // Productos de existencia (ya hechos): no generan OP; pasan a embalaje/carga.
            $pedido->update([
                'estatus' => 'LISTO_PARA_CARGA',
                'updated_at' => now(),
            ]);

            return [
                'orden_id' => null,
                'mensaje' => 'El pedido solo tiene productos de existencia (ya hechos). Se marcó como LISTO_PARA_CARGA para embalaje/despacho; no requiere orden de producción.',
            ];
        }

        $materiales = [];
        foreach ($faltantes['producibles'] as $fila) {
            $info = $this->materialesRecetaProducto((int) $fila['producto_id'], (float) $fila['faltante']);
            foreach ($info['materiales'] as $m) {
                $mpId = (int) $m['producto_mp_id'];
                if ($mpId <= 0) {
                    continue;
                }
                if (!isset($materiales[$mpId])) {
                    $materiales[$mpId] = [
                        'producto_id' => $mpId,
                        'nombre' => $m['nombre'],
                        'requerido' => 0.0,
                        'disponible' => (float) $m['disponible'],
                    ];
                }
                $materiales[$mpId]['requerido'] += (float) $m['requerido'];
            }
        }

        $faltantesMp = [];
        foreach ($materiales as $mp) {
            $faltante = round(max(0, $mp['requerido'] - $mp['disponible']), 2);
            if ($faltante > 0) {
                $mp['faltante'] = $faltante;
                $faltantesMp[] = $mp;
            }
        }

        $estatusOrden = empty($faltantesMp)
            ? OrdenProduccion::ESTATUS_PREPARANDO_MAQUINAS
            : OrdenProduccion::ESTATUS_EN_ESPERA_MATERIALES;

        $tipoProceso = OrdenProduccion::TIPO_PROCESO_TUBO;
        foreach ($faltantes['producibles'] as $filaCheck) {
            $prod = Productos::find($filaCheck['producto_id']);
            $pid = (int) $filaCheck['producto_id'];

            if (($prod && $prod->esConexion())
                || ProductoConexionEspecificacion::where('producto_id', $pid)->where('estatus', 'ACTIVO')->exists()) {
                $tipoProceso = OrdenProduccion::TIPO_PROCESO_CONEXION;
                break;
            }

            if (($prod && $prod->esSoloFlange())
                || ProductoFlangeEspecificacion::where('producto_id', $pid)->where('estatus', 'ACTIVO')->exists()) {
                $tipoProceso = OrdenProduccion::TIPO_PROCESO_FLANGE;
                break;
            }
        }

        $orden = OrdenProduccion::create([
            'folio' => $this->generarFolioOrdenProduccion(),
            'fecha' => now()->toDateString(),
            'pedido_id' => $pedido->id,
            'maquina_id' => null,
            'turno_id' => null,
            'estatus' => $estatusOrden,
            'tipo_proceso' => $tipoProceso,
            'observaciones' => 'Generada desde pedido ' . ($pedido->folio ?: ('#' . $pedido->id)) . '.',
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        $rutaFlange = null;
        foreach ($faltantes['producibles'] as $fila) {
            $data = [
                'orden_id' => $orden->id,
                'producto_id' => $fila['producto_id'],
                'tipo_linea' => 'FABRICAR',
                'observaciones' => 'Pedido ' . ($pedido->folio ?: ('#' . $pedido->id)) . ' · faltante ' . number_format($fila['faltante'], 2),
                'metros_producidos' => 0,
                'piezas_producidas' => 0,
                'created_at' => now(),
            ];

            $specTubo = ProductoTuboEspecificacion::where('producto_id', $fila['producto_id'])
                ->where('estatus', 'ACTIVO')
                ->orderByDesc('psi')
                ->first();

            if ($specTubo) {
                $data['diametro'] = $specTubo->diametro_nominal;
                $data['rd'] = $specTubo->rd !== null ? (string) $specTubo->rd : null;
                $data['psi'] = $specTubo->psi;
                $data['espesor'] = $specTubo->espesor_pulg;
                $data['kg_metro'] = $specTubo->peso_kg_m;
            } else {
                $specFlange = ProductoFlangeEspecificacion::where('producto_id', $fila['producto_id'])
                    ->where('estatus', 'ACTIVO')
                    ->orderByDesc('id')
                    ->first();
                if ($specFlange) {
                    $data['diametro'] = $specFlange->diametro_nominal;
                    $data['rd'] = $specFlange->rd !== null ? (string) $specFlange->rd : null;
                    $data['kg_metro'] = $specFlange->peso_kg_pieza;
                    if ($rutaFlange === null) {
                        $rutaFlange = ProductoFlangeEspecificacion::resolverRuta(
                            $specFlange->diametro_nominal,
                            $specFlange->rd
                        );
                    }
                } else {
                    $specConexion = ProductoConexionEspecificacion::where('producto_id', $fila['producto_id'])
                        ->where('estatus', 'ACTIVO')
                        ->orderByDesc('id')
                        ->first();
                    if ($specConexion) {
                        $data['diametro'] = $specConexion->diametro_nominal;
                        $data['rd'] = $specConexion->rd !== null ? (string) $specConexion->rd : null;
                        $data['kg_metro'] = $specConexion->peso_kg_pieza;
                        if ($rutaFlange === null) {
                            $rutaFlange = ProductoFlangeEspecificacion::resolverRuta(
                                $specConexion->diametro_nominal,
                                $specConexion->rd
                            );
                        }
                    }
                }
            }

            OrdenProduccionDetalle::create($data);
        }

        if (in_array($tipoProceso, [OrdenProduccion::TIPO_PROCESO_FLANGE, OrdenProduccion::TIPO_PROCESO_CONEXION], true) && $rutaFlange) {
            $orden->update(['ruta_flange' => $rutaFlange, 'updated_at' => now()]);
        }

        if (empty($faltantesMp)) {
            $almacenGeneral = $this->obtenerAlmacenGeneral();
            if (!$almacenGeneral) {
                throw ValidationException::withMessages([
                    'almacen' => 'No se encontró el almacén general para descontar materias primas.',
                ]);
            }

            foreach ($materiales as $mp) {
                $this->descontarExistenciaAlmacenGeneral(
                    (int) $mp['producto_id'],
                    (float) $mp['requerido'],
                    (int) $almacenGeneral->id,
                    $orden->folio,
                    'Salida a producción OP ' . $orden->folio . ' · ' . ($mp['nombre'] ?? '')
                );
            }

            $orden->update([
                'materiales_tomados_at' => now(),
                'materiales_tomados_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            $pedido->update(['estatus' => 'EN_PRODUCCION', 'updated_at' => now()]);

            return [
                'orden_id' => (int) $orden->id,
                'mensaje' => 'Se generó la orden de producción ' . $orden->folio
                    . ' en Preparando máquinas. Se descontaron materias primas del almacén y se registraron los movimientos de inventario.',
            ];
        }

        $ordenCompra = OrdenCompra::create(OrdenCompra::datosBorradorAutomatico([
            'folio' => $this->generarFolioOrdenCompraFaltantes(),
            'nombre' => 'Materia prima faltante pedido ' . ($pedido->folio ?: ('#' . $pedido->id)),
            'descripcion_detalle' => 'Generada por faltante de materia prima del pedido ' . ($pedido->folio ?: ('#' . $pedido->id)),
            'observaciones' => 'Revisar y asignar proveedor. Origen: faltante de materia prima para producción.',
        ]));

        foreach ($faltantesMp as $mp) {
            $producto = Productos::find($mp['producto_id']);
            $costo = $producto ? (float) ($producto->costo_compra ?? 0) : 0;

            $ordenCompra->detalles()->create([
                'producto_id' => $mp['producto_id'],
                'cantidad' => $mp['faltante'],
                'observaciones' => 'Pedido ' . ($pedido->folio ?: ('#' . $pedido->id)) . ' · MP faltante',
                'costo' => $costo,
            ]);

            ReporteNoExistencia::create([
                'folio' => $this->generarFolioReporteNoExistencia(),
                'pedido_id' => $pedido->id,
                'cliente_id' => $pedido->cliente_id,
                'producto_id' => $mp['producto_id'],
                'tipo' => ReporteNoExistencia::TIPO_MATERIA_PRIMA,
                'descripcion' => $mp['nombre'],
                'cantidad_solicitada' => round($mp['requerido'], 2),
                'existencia_disponible' => round($mp['disponible'], 2),
                'cantidad_faltante' => round($mp['faltante'], 2),
                'estatus' => ReporteNoExistencia::ESTATUS_EN_COMPRA,
                'orden_compra_id' => $ordenCompra->id,
                'observaciones' => 'Materia prima faltante para pedido ' . ($pedido->folio ?: ('#' . $pedido->id)),
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $pedido->update(['estatus' => 'PENDIENTE_OC', 'updated_at' => now()]);

        return [
            'orden_id' => (int) $orden->id,
            'mensaje' => 'Se generó la orden de producción ' . $orden->folio
                . ' en espera de materiales y la orden de compra ' . $ordenCompra->folio . '.',
        ];
    }

    public function enviarPedidoAProduccion(Request $request, int $id): RedirectResponse
    {
        $vendedorId = (int) $request->input('vendedor_id', auth()->id());
        $pedido = VentaPedido::with(['detalles.producto', 'cliente'])->findOrFail($id);

        $resultado = DB::transaction(function () use ($pedido) {
            if ($pedido->estatus !== 'EN_PRODUCCION') {
                $pedido->update(['estatus' => 'EN_PRODUCCION', 'updated_at' => now()]);
            }

            return $this->procesarPedidoAProduccionConMateriales($pedido);
        });

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $vendedorId, 'abrir_pedido' => 1, 'pedido_id' => $pedido->id])
            ->with('success', $resultado['mensaje']);
    }

    /**
     * Registra un reporte de no existencias para las líneas comprables sin existencia
     * y genera un borrador de orden de compra con esos productos.
     */
    public function enviarFaltantesACompras(Request $request, int $id): RedirectResponse
    {
        $vendedorId = (int) $request->input('vendedor_id', auth()->id());

        $pedido = VentaPedido::with(['detalles.producto', 'cliente'])->findOrFail($id);
        $faltantes = $this->analizarFaltantesPedido($pedido);

        if (empty($faltantes['comprables'])) {
            return redirect()
                ->route('ventas.pedidos', ['vendedor_id' => $vendedorId, 'abrir_pedido' => 1, 'pedido_id' => $pedido->id])
                ->with('warning', 'No hay productos de compra sin existencia en este pedido.');
        }

        $ordenCompra = DB::transaction(function () use ($pedido, $faltantes) {
            $ordenCompra = OrdenCompra::create(OrdenCompra::datosBorradorAutomatico([
                'folio' => $this->generarFolioOrdenCompraFaltantes(),
                'nombre' => 'Faltantes pedido ' . ($pedido->folio ?: ('#' . $pedido->id)),
                'descripcion_detalle' => 'Generada automáticamente desde el reporte de no existencias del pedido '
                    . ($pedido->folio ?: ('#' . $pedido->id))
                    . ($pedido->cliente ? ' · Cliente: ' . $pedido->cliente->nombre : ''),
                'observaciones' => 'Revisar y asignar proveedor. Origen: pedido de venta sin existencia.',
            ]));

            foreach ($faltantes['comprables'] as $fila) {
                $producto = Productos::find($fila['producto_id']);
                $costo = $producto ? (float) ($producto->costo_compra ?? 0) : 0;

                $ordenCompra->detalles()->create([
                    'producto_id' => $fila['producto_id'],
                    'cantidad' => $fila['faltante'],
                    'observaciones' => 'Pedido ' . ($pedido->folio ?: ('#' . $pedido->id)),
                    'costo' => $costo,
                ]);

                ReporteNoExistencia::create([
                    'folio' => $this->generarFolioReporteNoExistencia(),
                    'pedido_id' => $pedido->id,
                    'pedido_detalle_id' => $fila['detalle_id'],
                    'cliente_id' => $pedido->cliente_id,
                    'producto_id' => $fila['producto_id'],
                    'descripcion' => $fila['nombre'],
                    'cantidad_solicitada' => $fila['cantidad'],
                    'existencia_disponible' => $fila['disponible'],
                    'cantidad_faltante' => $fila['faltante'],
                    'estatus' => ReporteNoExistencia::ESTATUS_EN_COMPRA,
                    'orden_compra_id' => $ordenCompra->id,
                    'observaciones' => 'Enviado a compras desde pedido ' . ($pedido->folio ?: ('#' . $pedido->id)),
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                ]);
            }

            return $ordenCompra;
        });

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $vendedorId, 'abrir_pedido' => 1, 'pedido_id' => $pedido->id])
            ->with('success', 'Reporte de no existencias registrado y borrador de orden de compra ' . $ordenCompra->folio . ' generado. Revíselo en el módulo de Órdenes de Compra para asignar proveedor.');
    }

    private function generarFolioOrdenProduccion(): string
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

    private function generarFolioOrdenCompraFaltantes(): string
    {
        $prefijo = 'OC-FALT-' . now()->format('Ymd') . '-';
        $ultimo = OrdenCompra::where('folio', 'like', $prefijo . '%')
            ->orderByDesc('id')
            ->value('folio');

        $consecutivo = 1;
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $matches)) {
            $consecutivo = (int) $matches[1] + 1;
        }

        return $prefijo . str_pad((string) $consecutivo, 3, '0', STR_PAD_LEFT);
    }

    private function generarFolioReporteNoExistencia(): string
    {
        $anio = date('Y');
        $consecutivo = ReporteNoExistencia::whereRaw('YEAR(created_at) = ?', [$anio])->count() + 1;

        return sprintf('RNE-%s-%04d', $anio, $consecutivo);
    }

    /**
     * Genera reportes de no existencia (para compras) por cada material de receta que
     * falte, según las líneas de producción de la cotización. Regenera los pendientes
     * previos de la misma cotización para no duplicar.
     *
     * @param array<int, array<string, mixed>> $lineas
     */
    private function generarReporteFaltantesMateriaPrima(VentaCotizacion $cotizacion, array $lineas): int
    {
        // Se limpian solo los pendientes de esta cotización (no los que ya están en compra/atendidos).
        ReporteNoExistencia::where('cotizacion_id', $cotizacion->id)
            ->where('tipo', ReporteNoExistencia::TIPO_MATERIA_PRIMA)
            ->where('estatus', ReporteNoExistencia::ESTATUS_PENDIENTE)
            ->delete();

        // Consolida faltantes por material (varias líneas pueden usar la misma materia prima).
        $faltantesPorMaterial = [];
        foreach ($lineas as $linea) {
            if (empty($linea['es_produccion'])) {
                continue;
            }

            $info = $this->materialesRecetaProducto((int) $linea['producto_id'], (float) ($linea['cantidad'] ?? 0));
            foreach ($info['materiales'] as $material) {
                if (!empty($material['suficiente'])) {
                    continue;
                }

                $mpId = (int) $material['producto_mp_id'];
                if (!isset($faltantesPorMaterial[$mpId])) {
                    $faltantesPorMaterial[$mpId] = [
                        'producto_id' => $mpId,
                        'nombre' => $material['nombre'],
                        'unidad' => $material['unidad'],
                        'disponible' => (float) $material['disponible'],
                        'requerido' => 0.0,
                        'productos_finales' => [],
                    ];
                }

                $faltantesPorMaterial[$mpId]['requerido'] += (float) $material['requerido'];
                $faltantesPorMaterial[$mpId]['productos_finales'][] = $linea['nombre'] ?? ('Producto #' . $linea['producto_id']);
            }
        }

        if (empty($faltantesPorMaterial)) {
            return 0;
        }

        $creados = 0;
        foreach ($faltantesPorMaterial as $material) {
            $faltante = round(max(0, $material['requerido'] - $material['disponible']), 2);
            if ($faltante <= 0) {
                continue;
            }

            $finales = implode(', ', array_unique($material['productos_finales']));

            ReporteNoExistencia::create([
                'folio' => $this->generarFolioReporteNoExistencia(),
                'cotizacion_id' => $cotizacion->id,
                'cliente_id' => $cotizacion->cliente_id,
                'producto_id' => $material['producto_id'],
                'tipo' => ReporteNoExistencia::TIPO_MATERIA_PRIMA,
                'descripcion' => $material['nombre'] . ($material['unidad'] ? ' (' . $material['unidad'] . ')' : ''),
                'cantidad_solicitada' => round($material['requerido'], 2),
                'existencia_disponible' => round($material['disponible'], 2),
                'cantidad_faltante' => $faltante,
                'estatus' => ReporteNoExistencia::ESTATUS_PENDIENTE,
                'observaciones' => 'Materia prima faltante para producir ' . $finales
                    . ' (cotización ' . ($cotizacion->folio ?: ('#' . $cotizacion->id)) . ').',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $creados++;
        }

        return $creados;
    }

    private function normalizarRequestPedido(Request $request): void
    {
        $request->merge([
            'cliente_id' => $request->input('cliente_id') ?: null,
            'cotizacion_id' => $request->input('cotizacion_id') ?: null,
            'fecha_pedido' => $request->input('fecha_pedido') ?: null,
            'hora_pedido' => $request->input('hora_pedido') ?: null,
            'fecha_entrega' => $request->input('fecha_entrega') ?: null,
            'hora_entrega' => $request->input('hora_entrega') ?: null,
            'observaciones' => $request->input('observaciones') ?: null,
            'tiempo_entrega' => $request->input('tiempo_entrega') ?: null,
            'persona_atencion' => $request->input('persona_atencion') ?: null,
            'moneda_id' => $request->input('moneda_id') ?: null,
            'tipo_flete_id' => $request->input('tipo_flete_id') ?: null,
            'condicion_pago_id' => $request->input('condicion_pago_id') ?: null,
            'importe_flete' => is_numeric($request->input('importe_flete')) ? $request->input('importe_flete') : 0,
            'flete_en_precios' => $request->boolean('flete_en_precios'),
            'iva_en_precios' => $request->boolean('iva_en_precios'),
            'tipo_iva_id' => $request->input('tipo_iva_id') ?: null,
            'descuento' => is_numeric($request->input('descuento')) ? $request->input('descuento') : 0,
            'buscar' => trim((string) $request->input('buscar', '')),
        ]);
    }

    /**
     * @return array{lineas: array<int, array<string, mixed>>, encabezado: array<string, mixed>}
     */
    private function obtenerBorradorPedido(): array
    {
        $borrador = session(self::SESSION_BORRADOR_PEDIDO, []);

        if (!empty($borrador['lineas'])) {
            foreach ($borrador['lineas'] as $indice => $linea) {
                $productoId = (int) ($linea['producto_id'] ?? 0);
                if ($productoId > 0 && empty($linea['unidad'])) {
                    $borrador['lineas'][$indice]['unidad'] = $this->resolverUnidadProducto($productoId);
                    $linea = $borrador['lineas'][$indice];
                }

                if (!empty($linea['es_produccion'])) {
                    // Las líneas de producción no se validan contra el stock del producto
                    // terminado, sino contra los materiales de su receta.
                    $materiales = $this->materialesRecetaProducto(
                        $productoId,
                        (float) ($linea['cantidad'] ?? 0)
                    );
                    $borrador['lineas'][$indice] = array_merge($linea, [
                        'existencia_disponible' => 0,
                        'existencia_almacen_general' => 0,
                        'sin_existencia' => false,
                        'materiales' => $materiales['materiales'],
                        'materiales_suficientes' => $materiales['producible'],
                        'tiene_receta' => $materiales['tiene_receta'],
                    ]);
                    continue;
                }

                $borrador['lineas'][$indice] = array_merge(
                    $linea,
                    $this->evaluarExistenciaLineaPedido($linea)
                );
                $idUbicacion = (int) ($linea['id_ubicacion'] ?? 0);
                if ($idUbicacion > 0 && $productoId > 0) {
                    $producto = $this->obtenerProductoEnUbicacion($idUbicacion, $productoId);
                    if ($producto && !empty($producto->ubi)) {
                        $borrador['lineas'][$indice]['ubicacion'] = $producto->ubi;
                    }
                    if ($producto && empty($borrador['lineas'][$indice]['unidad']) && !empty($producto->unidad)) {
                        $borrador['lineas'][$indice]['unidad'] = (string) $producto->unidad;
                    }
                }
            }
            session([self::SESSION_BORRADOR_PEDIDO => $borrador]);
        }

        $encabezado = array_merge([
            'cliente_id' => null,
            'cotizacion_id' => null,
            'fecha_pedido' => date('Y-m-d'),
            'hora_pedido' => date('H:i'),
            'fecha_entrega' => null,
            'hora_entrega' => null,
            'observaciones' => null,
            'tiempo_entrega' => null,
            'persona_atencion' => null,
            'moneda_id' => null,
            'tipo_flete_id' => null,
            'condicion_pago_id' => null,
            'importe_flete' => 0,
            'flete_en_precios' => false,
            'iva_en_precios' => false,
            'tipo_iva_id' => $this->tipoIvaIdPorDefecto(),
            'porcentaje_iva' => $this->porcentajeIvaPorDefecto(),
            'descuento' => 0,
            'id_ubicacion' => null,
            'buscar' => '',
            'tipo_venta' => 'EXISTENCIA',
        ], $borrador['encabezado'] ?? []);

        $cotizacionId = (int) ($encabezado['cotizacion_id'] ?? 0);
        if ($cotizacionId > 0 && !$this->cotizacionEstaDisponibleParaPedido($cotizacionId)) {
            $encabezado['cotizacion_id'] = null;
            $borrador['encabezado'] = $encabezado;
            session([self::SESSION_BORRADOR_PEDIDO => $borrador]);
        }

        return [
            'lineas' => $borrador['lineas'] ?? [],
            'encabezado' => $encabezado,
        ];
    }

    /**
     * @param array<string, mixed> $linea
     * @return array{existencia_disponible: float, existencia_almacen_general: float, sin_existencia: bool}
     */
    private function evaluarExistenciaLineaPedido(array $linea): array
    {
        $productoId = (int) ($linea['producto_id'] ?? 0);
        $cantidad = (float) ($linea['cantidad'] ?? 0);
        $disponibleUbicacion = $this->obtenerExistenciaDisponibleLinea($linea);
        $disponibleGeneral = $productoId > 0
            ? $this->obtenerExistenciaTotalAlmacenGeneral($productoId)
            : 0.0;

        $sinExistencia = $cantidad <= 0
            || $disponibleUbicacion <= 0
            || $cantidad > $disponibleUbicacion
            || $disponibleGeneral <= 0
            || $cantidad > $disponibleGeneral;

        return [
            'existencia_disponible' => $disponibleUbicacion,
            'existencia_almacen_general' => $disponibleGeneral,
            'sin_existencia' => $sinExistencia,
        ];
    }

    /**
     * @param array<string, mixed> $borrador
     */
    private function respuestaFragmentosPedido(array $borrador, int $vendedorId, string $mensaje): JsonResponse
    {
        $totales = $this->calcularTotales(
            $borrador['lineas'],
            (float) ($borrador['encabezado']['descuento'] ?? 0),
            (float) ($borrador['encabezado']['importe_flete'] ?? 0),
            (bool) ($borrador['encabezado']['flete_en_precios'] ?? false),
            (bool) ($borrador['encabezado']['iva_en_precios'] ?? false),
            (float) ($borrador['encabezado']['porcentaje_iva'] ?? $this->porcentajeIvaPorDefecto())
        );

        $borradorVista = $this->borradorConMontosVisibles($borrador, $totales);

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'html_lineas' => view('Ventas.partials.pedido_lineas_tbody', [
                'borradorPedido' => $borradorVista,
                'vendedorId' => $vendedorId,
                'puedeEditarPrecio' => $this->puedeEditarPrecioVenta(),
                'totales' => $totales,
            ])->render(),
            'html_totales' => view('Ventas.partials.cotizacion_totales', [
                'borrador' => $borrador,
                'totales' => $totales,
                'monedas' => Moneda::where('estatus', 'A')->orderBy('codigo')->get(),
            ])->render(),
            'totales' => $totales,
        ]);
    }

    private function normalizarRequestCotizacion(Request $request): void
    {
        $request->merge([
            'cliente_id' => $request->input('cliente_id') ?: null,
            'fecha_vencimiento' => $request->input('fecha_vencimiento') ?: null,
            'observaciones' => $request->input('observaciones') ?: null,
            'tiempo_entrega' => $request->input('tiempo_entrega') ?: null,
            'persona_atencion' => $request->input('persona_atencion') ?: null,
            'moneda_id' => $request->input('moneda_id') ?: null,
            'tipo_flete_id' => $request->input('tipo_flete_id') ?: null,
            'condicion_pago_id' => $request->input('condicion_pago_id') ?: null,
            'importe_flete' => is_numeric($request->input('importe_flete')) ? $request->input('importe_flete') : 0,
            'flete_en_precios' => $request->boolean('flete_en_precios'),
            'iva_en_precios' => $request->boolean('iva_en_precios'),
            'tipo_iva_id' => $request->input('tipo_iva_id') ?: null,
            'descuento' => is_numeric($request->input('descuento')) ? $request->input('descuento') : 0,
            'buscar' => trim((string) $request->input('buscar', '')),
        ]);
    }

    public function agregarLineaCotizacion(Request $request)
    {
        $this->normalizarRequestCotizacion($request);
        $this->validarBorradorEditable();

        $borradorActual = $this->obtenerBorrador();
        $tipoVenta = $request->input('tipo_venta', $borradorActual['encabezado']['tipo_venta'] ?? 'EXISTENCIA');
        $esProduccion = $tipoVenta === 'PRODUCCION';

        $validated = $request->validate([
            'id_ubicacion' => [$esProduccion ? 'nullable' : 'required', 'integer', 'exists:tblubicaciones,id'],
            'producto_id' => ['required', 'integer', 'exists:tblproductos,id'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'cliente_id' => ['nullable', 'integer', 'exists:tblclientes,id'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'descuento' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);

        $cantidad = round((float) $validated['cantidad'], 2);
        $descripcionLinea = trim((string) ($validated['descripcion'] ?? ''));
        $sinExistencia = false;

        if ($esProduccion) {
            $nuevaLinea = $this->construirLineaProduccion((int) $validated['producto_id'], $cantidad, $validated['precio_unitario'] ?? null);
        } else {
            $this->validarUbicacionAlmacenGeneral((int) $validated['id_ubicacion']);
            $producto = $this->obtenerProductoEnUbicacion(
                (int) $validated['id_ubicacion'],
                (int) $validated['producto_id']
            );

            if (!$producto) {
                throw ValidationException::withMessages([
                    'producto_id' => 'El producto no está registrado en la ubicación seleccionada.',
                ]);
            }

            $precioUnitario = round((float) ($validated['precio_unitario'] ?? $producto->costo_venta ?? $producto->precio_unitario ?? 0), 2);
            $disponible = max(0, (float) $producto->cantidad_existente - (float) $producto->cantidad_reservada);
            $sinExistencia = $disponible <= 0 || $cantidad > $disponible;

            $nuevaLinea = [
                'producto_id' => (int) $producto->id_producto,
                'sku' => $producto->sku,
                'nombre' => $producto->nombrepro,
                'descripcion' => $producto->descripcion,
                'ubicacion' => $producto->ubi,
                'id_ubicacion' => (int) $validated['id_ubicacion'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'descuento' => 0,
                'es_produccion' => false,
                'existencia_disponible' => $disponible,
                'sin_existencia' => $sinExistencia,
                'unidad' => $this->resolverUnidadProducto((int) $producto->id_producto, $producto->unidad ?? null),
            ];
        }

        if ($descripcionLinea !== '') {
            $nuevaLinea['descripcion'] = $descripcionLinea;
        } elseif (empty(trim((string) ($nuevaLinea['descripcion'] ?? '')))) {
            $nuevaLinea['descripcion'] = $nuevaLinea['nombre'] ?? '';
        }

        $nuevaLinea['importe'] = $this->calcularImporteLinea($nuevaLinea);

        if (round((float) ($nuevaLinea['precio_unitario'] ?? 0), 2) <= 0) {
            throw ValidationException::withMessages([
                'precio_unitario' => 'El producto "' . ($nuevaLinea['nombre'] ?? 'seleccionado') . '" no tiene precio unitario. Capture el precio de venta antes de agregarlo.',
            ]);
        }

        $borrador = $this->obtenerBorrador();
        $borrador['encabezado'] = array_merge($borrador['encabezado'], [
            'id_ubicacion' => $esProduccion ? ($borrador['encabezado']['id_ubicacion'] ?? null) : (int) $validated['id_ubicacion'],
            'buscar' => trim((string) ($validated['buscar'] ?? '')),
            'tipo_venta' => $tipoVenta,
            'cliente_id' => $validated['cliente_id'] ?? ($borrador['encabezado']['cliente_id'] ?? null),
            'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? ($borrador['encabezado']['fecha_vencimiento'] ?? null),
            'observaciones' => $validated['observaciones'] ?? ($borrador['encabezado']['observaciones'] ?? null),
            'tiempo_entrega' => $request->input('tiempo_entrega') ?? ($borrador['encabezado']['tiempo_entrega'] ?? null),
            'persona_atencion' => $request->input('persona_atencion') ?? ($borrador['encabezado']['persona_atencion'] ?? null),
            'moneda_id' => $request->input('moneda_id') ?? ($borrador['encabezado']['moneda_id'] ?? null),
            'tipo_flete_id' => $request->input('tipo_flete_id') ?? ($borrador['encabezado']['tipo_flete_id'] ?? null),
            'condicion_pago_id' => $request->input('condicion_pago_id') ?? ($borrador['encabezado']['condicion_pago_id'] ?? null),
            'importe_flete' => is_numeric($request->input('importe_flete'))
                ? (float) $request->input('importe_flete')
                : (float) ($borrador['encabezado']['importe_flete'] ?? 0),
            'flete_en_precios' => $request->has('flete_en_precios')
                ? $request->boolean('flete_en_precios')
                : (bool) ($borrador['encabezado']['flete_en_precios'] ?? false),
            'iva_en_precios' => $request->has('iva_en_precios')
                ? $request->boolean('iva_en_precios')
                : (bool) ($borrador['encabezado']['iva_en_precios'] ?? false),
            'tipo_iva_id' => $request->filled('tipo_iva_id')
                ? (int) $request->input('tipo_iva_id')
                : ($borrador['encabezado']['tipo_iva_id'] ?? $this->tipoIvaIdPorDefecto()),
            'porcentaje_iva' => $this->resolverPorcentajeIva(
                $request->filled('tipo_iva_id')
                    ? (int) $request->input('tipo_iva_id')
                    : (isset($borrador['encabezado']['tipo_iva_id']) ? (int) $borrador['encabezado']['tipo_iva_id'] : null),
                (float) ($borrador['encabezado']['porcentaje_iva'] ?? $this->porcentajeIvaPorDefecto())
            ),
            'descuento' => $validated['descuento'] ?? ($borrador['encabezado']['descuento'] ?? 0),
        ]);

        $lineaExistente = null;
        foreach ($borrador['lineas'] as $index => $linea) {
            if ((int) $linea['producto_id'] === (int) $validated['producto_id']) {
                $lineaExistente = $index;
                break;
            }
        }

        if ($lineaExistente !== null) {
            $borrador['lineas'][$lineaExistente] = $nuevaLinea;
        } else {
            $borrador['lineas'][] = $nuevaLinea;
        }

        session([self::SESSION_BORRADOR => $borrador]);

        if ($request->expectsJson()) {
            if ($esProduccion) {
                $mensajeCot = empty($nuevaLinea['materiales_suficientes'])
                    ? 'Producto de producción agregado. Revise los materiales de la receta marcados en rojo.'
                    : 'Producto de producción agregado. Materiales de receta disponibles.';
            } else {
                $mensajeCot = $sinExistencia
                    ? 'Producto agregado. Revise la existencia marcada en rojo.'
                    : 'Producto agregado a la cotización.';
            }

            return $this->respuestaFragmentosCotizacion(
                $borrador,
                (int) $request->input('vendedor_id', auth()->id()),
                $mensajeCot
            );
        }

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $request->input('vendedor_id', auth()->id())])
            ->with('abrir_modal_cotizacion', true);
    }

    public function actualizarLineaCotizacion(Request $request)
    {
        $this->normalizarRequestCotizacion($request);
        $this->validarBorradorEditable();

        $validated = $request->validate([
            'indice' => ['required', 'integer', 'min:0'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'cliente_id' => ['nullable', 'integer', 'exists:tblclientes,id'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'descuento_global' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'id_ubicacion' => ['nullable', 'integer'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);

        $borrador = $this->obtenerBorrador();
        $indice = (int) $validated['indice'];

        if (!isset($borrador['lineas'][$indice])) {
            throw ValidationException::withMessages([
                'indice' => 'La línea indicada ya no existe en la cotización.',
            ]);
        }

        $linea = $borrador['lineas'][$indice];
        $linea['cantidad'] = round((float) $validated['cantidad'], 2);
        if ($this->puedeEditarPrecioVenta() && array_key_exists('precio_unitario', $validated) && $validated['precio_unitario'] !== null) {
            $linea['precio_unitario'] = round((float) $validated['precio_unitario'], 2);
        } else {
            $linea['precio_unitario'] = round((float) ($linea['precio_unitario'] ?? 0), 2);
        }
        $linea['descuento'] = round((float) ($validated['descuento'] ?? 0), 2);
        if (array_key_exists('descripcion', $validated)) {
            $descripcionLinea = trim((string) ($validated['descripcion'] ?? ''));
            $linea['descripcion'] = $descripcionLinea !== ''
                ? $descripcionLinea
                : ($linea['nombre'] ?? $linea['descripcion'] ?? '');
        }

        if (!empty($linea['es_produccion'])) {
            $materiales = $this->materialesRecetaProducto((int) $linea['producto_id'], (float) $linea['cantidad']);
            $linea['materiales'] = $materiales['materiales'];
            $linea['materiales_suficientes'] = $materiales['producible'];
            $linea['tiene_receta'] = $materiales['tiene_receta'];
            $linea['existencia_disponible'] = 0;
            $linea['sin_existencia'] = false;
        } else {
            $disponible = $this->obtenerExistenciaDisponibleLinea($linea);
            $linea['existencia_disponible'] = $disponible;
            $linea['sin_existencia'] = $disponible <= 0 || $linea['cantidad'] > $disponible;
        }
        $linea['importe'] = $this->calcularImporteLinea($linea);
        $borrador['lineas'][$indice] = $linea;

        $borrador = $this->aplicarEncabezadoCotizacionDesdeRequest($request, $borrador);
        if (array_key_exists('descuento_global', $validated) && $validated['descuento_global'] !== null) {
            $borrador['encabezado']['descuento'] = round((float) $validated['descuento_global'], 2);
        }

        session([self::SESSION_BORRADOR => $borrador]);

        if ($request->expectsJson()) {
            return $this->respuestaFragmentosCotizacion(
                $borrador,
                (int) $request->input('vendedor_id', auth()->id()),
                'Línea y totales actualizados (IVA / flete).'
            );
        }

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $request->input('vendedor_id', auth()->id())])
            ->with('abrir_modal_cotizacion', true);
    }

    public function quitarLineaCotizacion(Request $request): RedirectResponse
    {
        $this->validarBorradorEditable();

        $validated = $request->validate([
            'indice' => ['required', 'integer', 'min:0'],
        ]);

        $borrador = $this->obtenerBorrador();
        $indice = (int) $validated['indice'];

        if (isset($borrador['lineas'][$indice])) {
            array_splice($borrador['lineas'], $indice, 1);
            session([self::SESSION_BORRADOR => $borrador]);
        }

        return redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $request->input('vendedor_id', auth()->id())])
            ->with('abrir_modal_cotizacion', true);
    }

    public function limpiarBorradorCotizacion(Request $request): RedirectResponse
    {
        session()->forget(self::SESSION_BORRADOR);

        $redirect = redirect()
            ->route('ventas.pedidos', ['vendedor_id' => $request->input('vendedor_id', auth()->id())]);

        if ($request->boolean('abrir_modal')) {
            return $redirect->with('abrir_modal_cotizacion', true);
        }

        return $redirect->with('success', 'Borrador de cotización limpiado.');
    }

    public function guardarCotizacion(Request $request): RedirectResponse
    {
        $this->normalizarRequestCotizacion($request);

        $validated = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:tblclientes,id'],
            'fecha_vencimiento' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'tiempo_entrega' => ['nullable', 'string', 'max:255'],
            'persona_atencion' => ['nullable', 'string', 'max:255'],
            'moneda_id' => ['nullable', 'integer', 'exists:tblmonedas,id'],
            'tipo_flete_id' => ['nullable', 'integer', 'exists:tbl_tipos_flete,id'],
            'condicion_pago_id' => ['nullable', 'integer', 'exists:tbl_condiciones_pago,id'],
            'importe_flete' => ['nullable', 'numeric', 'min:0'],
            'flete_en_precios' => ['nullable', 'boolean'],
            'iva_en_precios' => ['nullable', 'boolean'],
            'tipo_iva_id' => ['nullable', 'integer', 'exists:tbl_tipos_iva,id'],
            'descuento' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'estatus' => ['nullable', 'in:BORRADOR,ENVIADA,ACEPTADA,RECHAZADA,VENCIDA,CONVERTIDA'],
            'cotizacion_id' => ['nullable', 'integer', 'exists:tbl_cotizaciones,id'],
            'archivo_cliente' => ['nullable', 'file', 'max:15360', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,rar,txt'],
        ]);

        $borrador = $this->obtenerBorrador();
        $cotizacionId = (int) ($validated['cotizacion_id'] ?? ($borrador['encabezado']['cotizacion_id'] ?? 0));

        if (empty($borrador['lineas'])) {
            throw ValidationException::withMessages([
                'lineas' => 'Agregue al menos un producto a la cotización.',
            ]);
        }

        $this->validarPreciosUnitariosCotizacion($borrador['lineas']);

        $monedaId = $this->resolverMonedaId(
            isset($validated['moneda_id']) ? (int) $validated['moneda_id'] : null,
            (int) $validated['cliente_id']
        );
        $importeFlete = round((float) ($validated['importe_flete'] ?? 0), 2);
        $fleteEnPrecios = $request->boolean('flete_en_precios');
        $ivaEnPrecios = $request->boolean('iva_en_precios');
        $tipoIvaId = !empty($validated['tipo_iva_id']) ? (int) $validated['tipo_iva_id'] : $this->tipoIvaIdPorDefecto();
        $porcentajeIva = $this->resolverPorcentajeIva($tipoIvaId);
        $descuentoPorcentaje = round((float) ($validated['descuento'] ?? 0), 2);
        $totales = $this->calcularTotales($borrador['lineas'], $descuentoPorcentaje, $importeFlete, $fleteEnPrecios, $ivaEnPrecios, $porcentajeIva);
        $personaAtencion = $this->resolverPersonaAtencionRequest(
            $validated['persona_atencion'] ?? null,
            (int) $validated['cliente_id']
        );

        $datosCabecera = [
            'cliente_id' => (int) $validated['cliente_id'],
            'persona_atencion' => $personaAtencion,
            'fecha_vencimiento' => $validated['fecha_vencimiento'] ?? null,
            'subtotal' => $totales['subtotal'],
            'descuento' => $totales['descuento_importe'],
            'iva' => $totales['iva'],
            'total' => $totales['total'],
            'observaciones' => $validated['observaciones'] ?? null,
            'tiempo_entrega' => $validated['tiempo_entrega'] ?? null,
            'moneda_id' => $monedaId,
            'tipo_flete_id' => $validated['tipo_flete_id'] ?? null,
            'condicion_pago_id' => $validated['condicion_pago_id'] ?? null,
            'importe_flete' => $importeFlete,
            'flete_en_precios' => $fleteEnPrecios,
            'iva_en_precios' => $ivaEnPrecios,
            'tipo_iva_id' => $tipoIvaId,
            'porcentaje_iva' => $porcentajeIva,
            'updated_at' => now(),
        ];

        DB::beginTransaction();

        try {
            if ($cotizacionId > 0) {
                $cotizacion = VentaCotizacion::where('id', $cotizacionId)->firstOrFail();
                $this->validarAccesoCotizacion($cotizacion);

                $this->validarCotizacionEditable($cotizacion);

                $cotizacion->update(array_merge($datosCabecera, [
                    'estatus' => $validated['estatus'] ?? $cotizacion->estatus,
                ]));

                VentaCotizacionDetalle::where('cotizacion_id', $cotizacion->id)->delete();

                foreach ($borrador['lineas'] as $linea) {
                    $importe = (float) ($linea['importe'] ?? $this->calcularImporteLinea($linea));
                    $conIva = $this->montosConIvaLinea(
                        (float) ($linea['precio_unitario'] ?? 0),
                        (float) ($linea['cantidad'] ?? 0),
                        (float) ($linea['descuento'] ?? 0),
                        $importe,
                        $porcentajeIva
                    );
                    VentaCotizacionDetalle::create([
                        'cotizacion_id' => $cotizacion->id,
                        'producto_id' => $linea['producto_id'],
                        'descripcion' => $linea['descripcion'] ?: $linea['nombre'],
                        'cantidad' => $linea['cantidad'],
                        'precio_unitario' => $linea['precio_unitario'],
                        'precio_unitario_con_iva' => $conIva['precio_unitario_con_iva'],
                        'descuento' => $linea['descuento'],
                        'importe' => $importe,
                        'importe_con_iva' => $conIva['importe_con_iva'],
                        'created_at' => now(),
                    ]);
                }

                $reportesGenerados = $this->generarReporteFaltantesMateriaPrima($cotizacion, $borrador['lineas']);

                $this->aplicarArchivoClienteCotizacion($request, $cotizacion, (int) $validated['cliente_id']);

                DB::commit();
                session()->forget(self::SESSION_BORRADOR);

                $mensaje = 'Cotización ' . $cotizacion->folio . ' actualizada correctamente.';
                if ($reportesGenerados > 0) {
                    $mensaje .= ' Se generó reporte de ' . $reportesGenerados . ' material(es) faltante(s) para compras.';
                }

                return $this->convertirSiAceptada($cotizacion, (int) auth()->id(), $mensaje);
            }

            $cotizacion = VentaCotizacion::create(array_merge($datosCabecera, [
                'folio' => $this->generarFolio(),
                'usuario_id' => auth()->id(),
                'fecha' => now(),
                'estatus' => $validated['estatus'] ?? 'BORRADOR',
                'created_at' => now(),
            ]));

            foreach ($borrador['lineas'] as $linea) {
                $importe = (float) ($linea['importe'] ?? $this->calcularImporteLinea($linea));
                $conIva = $this->montosConIvaLinea(
                    (float) ($linea['precio_unitario'] ?? 0),
                    (float) ($linea['cantidad'] ?? 0),
                    (float) ($linea['descuento'] ?? 0),
                    $importe,
                    $porcentajeIva
                );
                VentaCotizacionDetalle::create([
                    'cotizacion_id' => $cotizacion->id,
                    'producto_id' => $linea['producto_id'],
                    'descripcion' => $linea['descripcion'] ?: $linea['nombre'],
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'precio_unitario_con_iva' => $conIva['precio_unitario_con_iva'],
                    'descuento' => $linea['descuento'],
                    'importe' => $importe,
                    'importe_con_iva' => $conIva['importe_con_iva'],
                    'created_at' => now(),
                ]);
            }

            $reportesGenerados = $this->generarReporteFaltantesMateriaPrima($cotizacion, $borrador['lineas']);

            $this->aplicarArchivoClienteCotizacion($request, $cotizacion, (int) $validated['cliente_id']);

            DB::commit();
            session()->forget(self::SESSION_BORRADOR);

            $mensaje = 'Cotización ' . $cotizacion->folio . ' guardada correctamente.';
            if ($reportesGenerados > 0) {
                $mensaje .= ' Se generó reporte de ' . $reportesGenerados . ' material(es) faltante(s) para compras.';
            }

            return $this->convertirSiAceptada($cotizacion, (int) auth()->id(), $mensaje);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @return array{lineas: array<int, array<string, mixed>>, encabezado: array<string, mixed>}
     */
    private function obtenerBorrador(): array
    {
        $borrador = session(self::SESSION_BORRADOR, []);

        if (!empty($borrador['lineas'])) {
            $borrador['lineas'] = $this->actualizarExistenciaLineas($borrador['lineas']);
            session([self::SESSION_BORRADOR => $borrador]);
        }

        $encabezado = array_merge([
            'cotizacion_id' => null,
            'folio' => null,
            'cliente_id' => null,
            'fecha_vencimiento' => null,
            'observaciones' => null,
            'tiempo_entrega' => null,
            'persona_atencion' => null,
            'moneda_id' => null,
            'tipo_flete_id' => null,
            'condicion_pago_id' => null,
            'importe_flete' => 0,
            'flete_en_precios' => false,
            'iva_en_precios' => false,
            'tipo_iva_id' => $this->tipoIvaIdPorDefecto(),
            'porcentaje_iva' => $this->porcentajeIvaPorDefecto(),
            'descuento' => 0,
            'id_ubicacion' => null,
            'buscar' => '',
            'estatus' => 'BORRADOR',
            'tipo_venta' => 'EXISTENCIA',
            'archivo_cliente_ruta' => null,
            'archivo_cliente_nombre' => null,
        ], $borrador['encabezado'] ?? []);

        return [
            'lineas' => $borrador['lineas'] ?? [],
            'encabezado' => $encabezado,
        ];
    }

    private function sincronizarEstatusBorrador(int $cotizacionId, string $estatus): void
    {
        $borrador = session(self::SESSION_BORRADOR, []);

        if ((int) ($borrador['encabezado']['cotizacion_id'] ?? 0) !== $cotizacionId) {
            return;
        }

        $borrador['encabezado']['estatus'] = $estatus;
        session([self::SESSION_BORRADOR => $borrador]);
    }

    private function formatearDireccionCliente(?Clientes $cliente): string
    {
        if (!$cliente) {
            return '';
        }

        $partes = array_filter([
            trim(($cliente->calle ?? '') . ' ' . ($cliente->numero_ext ?? '') . (!empty($cliente->numero_int) ? ' INT ' . $cliente->numero_int : '')),
            $cliente->colonia ? 'Col. ' . $cliente->colonia : null,
            $cliente->cp ? 'CP: ' . $cliente->cp : null,
        ]);

        return implode(', ', $partes);
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $clientes
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function enriquecerClientesConPersonaAtencion($clientes)
    {
        $ids = $clientes->pluck('id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return $clientes;
        }

        $personas = \App\Models\ClientesAtencion::whereIn('id_cliente', $ids)
            ->orderBy('id')
            ->get()
            ->groupBy('id_cliente');

        return $clientes->map(function ($cliente) use ($personas) {
            $persona = optional($personas->get((int) $cliente->id))->first();
            if ($persona) {
                $cliente->persona_atencion_nombre = strtoupper(trim((string) $persona->nombre_completo));
            } else {
                $cliente->persona_atencion_nombre = strtoupper(trim((string) ($cliente->nombre ?? '')));
            }

            return $cliente;
        });
    }

    private function resolverNombrePersonaAtencion($cliente): string
    {
        if (!$cliente) {
            return '';
        }

        if ($cliente instanceof Clientes) {
            return $cliente->nombrePersonaAtencion();
        }

        if (!empty($cliente->persona_atencion_nombre)) {
            return trim((string) $cliente->persona_atencion_nombre);
        }

        if (isset($cliente->id)) {
            $persona = \App\Models\ClientesAtencion::where('id_cliente', (int) $cliente->id)->orderBy('id')->first();
            if ($persona) {
                return strtoupper(trim((string) $persona->nombre_completo));
            }
        }

        return strtoupper(trim((string) ($cliente->nombre ?? '')));
    }

    private function resolverPersonaAtencionRequest(?string $valor, int $clienteId): ?string
    {
        $nombre = strtoupper(trim((string) $valor));
        if ($nombre !== '') {
            return $nombre;
        }

        if ($clienteId <= 0) {
            return null;
        }

        $cliente = Clientes::with('personasAtencion')->find($clienteId);
        $resuelto = $this->resolverNombrePersonaAtencion($cliente);

        return $resuelto !== '' ? $resuelto : null;
    }

    private function validarCotizacionEditable(VentaCotizacion $cotizacion): void
    {
        if (!in_array($cotizacion->estatus, self::ESTATUS_COTIZACION_EDITABLE, true)) {
            throw ValidationException::withMessages([
                'estatus' => 'La cotización no puede editarse porque está cerrada o ya fue convertida a pedido.',
            ]);
        }
    }

    private function validarBorradorEditable(): void
    {
        $borrador = $this->obtenerBorrador();
        $cotizacionId = (int) ($borrador['encabezado']['cotizacion_id'] ?? 0);

        if ($cotizacionId <= 0) {
            return;
        }

        $cotizacion = VentaCotizacion::find($cotizacionId);
        if ($cotizacion) {
            $this->validarCotizacionEditable($cotizacion);
        }
    }

    private function obtenerAlmacenGeneral(): ?object
    {
        return DB::table('tblalmacenes')
            ->where('estado', 'A')
            ->whereRaw('UPPER(folio_interno) LIKE ?', ['%ALMACEN GENERAL%'])
            ->first();
    }

    /**
     * Cotizaciones que pueden vincularse opcionalmente a un pedido nuevo.
     */
    private function obtenerCotizacionesDisponiblesParaPedido(int $vendedorId)
    {
        $idsConPedido = VentaPedido::query()
            ->whereNotNull('cotizacion_id')
            ->pluck('cotizacion_id');

        return VentaCotizacion::with(['cliente'])
            ->where('usuario_id', $vendedorId)
            ->where('estatus', '!=', 'CONVERTIDA')
            ->whereNotIn('id', $idsConPedido)
            ->orderByDesc('id')
            ->get();
    }

    private function cotizacionEstaDisponibleParaPedido(int $cotizacionId): bool
    {
        if ($cotizacionId <= 0) {
            return false;
        }

        return VentaCotizacion::where('id', $cotizacionId)
            ->where('estatus', '!=', 'CONVERTIDA')
            ->whereNotIn('id', VentaPedido::query()->whereNotNull('cotizacion_id')->pluck('cotizacion_id'))
            ->exists();
    }

    private function validarCotizacionDisponibleParaPedido(int $cotizacionId, int $usuarioId): void
    {
        $cotizacion = VentaCotizacion::where('id', $cotizacionId)
            ->where('usuario_id', $usuarioId)
            ->first();

        if (!$cotizacion) {
            throw ValidationException::withMessages([
                'cotizacion_id' => 'La cotización seleccionada no está disponible.',
            ]);
        }

        if ($cotizacion->estatus === 'CONVERTIDA' || VentaPedido::where('cotizacion_id', $cotizacionId)->exists()) {
            throw ValidationException::withMessages([
                'cotizacion_id' => 'La cotización ' . ($cotizacion->folio ?: '#' . $cotizacionId) . ' ya fue convertida a pedido.',
            ]);
        }
    }

    private function validarUbicacionAlmacenGeneral(int $idUbicacion): void
    {
        $almacenGeneral = $this->obtenerAlmacenGeneral();

        if (!$almacenGeneral) {
            throw ValidationException::withMessages([
                'id_ubicacion' => 'No se encontró el almacén general configurado.',
            ]);
        }

        $ubicacion = $this->Listaubixid($idUbicacion)->first();

        if (!$ubicacion || (int) $ubicacion->id_almacen !== (int) $almacenGeneral->id) {
            throw ValidationException::withMessages([
                'id_ubicacion' => 'La ubicación no pertenece al almacén general.',
            ]);
        }
    }

    private function productosParaCotizacion(int $idUbicacion, string $buscar, ?int $idAlmacenGeneral)
    {
        return $this->Listadoproductosxubicacion($idUbicacion)
            ->filter(function ($producto) use ($idAlmacenGeneral) {
                return $idAlmacenGeneral === null || (int) $producto->alma === $idAlmacenGeneral;
            })
            ->filter(function ($producto) use ($buscar) {
                if ($buscar === '') {
                    return true;
                }

                $nombre = mb_strtolower((string) $producto->nombrepro);
                $sku = mb_strtolower((string) ($producto->sku ?? ''));
                $codigo = mb_strtolower((string) ($producto->codigo_barras ?? ''));
                $palabras = preg_split('/\s+/', mb_strtolower(trim($buscar))) ?: [];

                foreach ($palabras as $palabra) {
                    if ($palabra === '') {
                        continue;
                    }

                    $coincide = str_contains($nombre, $palabra)
                        || str_contains($sku, $palabra)
                        || str_contains($codigo, $palabra);

                    if (!$coincide) {
                        return false;
                    }
                }

                return true;
            })
            ->map(function ($producto) {
                $disponible = max(0, (float) $producto->cantidad_existente - (float) $producto->cantidad_reservada);
                $producto->disponible = $disponible;
                $producto->sin_existencia = $disponible <= 0;

                return $producto;
            })
            ->values();
    }

    private function obtenerProductoEnUbicacion(int $idUbicacion, int $idProducto): ?object
    {
        return $this->Listadoproductosxubicacion($idUbicacion)
            ->first(function ($producto) use ($idProducto) {
                return (int) $producto->id_producto === $idProducto;
            });
    }

    /**
     * @param array<int, array<string, mixed>> $lineas
     * @return array<int, array<string, mixed>>
     */
    private function actualizarExistenciaLineas(array $lineas): array
    {
        foreach ($lineas as $indice => $linea) {
            $productoId = (int) ($linea['producto_id'] ?? 0);
            if ($productoId <= 0) {
                continue;
            }

            if (empty($linea['unidad'])) {
                $lineas[$indice]['unidad'] = $this->resolverUnidadProducto($productoId);
            }

            if (!empty($linea['es_produccion'])) {
                $materiales = $this->materialesRecetaProducto($productoId, (float) ($linea['cantidad'] ?? 0));
                $lineas[$indice]['existencia_disponible'] = 0;
                $lineas[$indice]['sin_existencia'] = false;
                $lineas[$indice]['materiales'] = $materiales['materiales'];
                $lineas[$indice]['materiales_suficientes'] = $materiales['producible'];
                $lineas[$indice]['tiene_receta'] = $materiales['tiene_receta'];
                continue;
            }

            $disponible = $this->obtenerExistenciaDisponibleLinea($linea);
            $cantidad = (float) ($linea['cantidad'] ?? 0);

            $lineas[$indice]['existencia_disponible'] = $disponible;
            $lineas[$indice]['sin_existencia'] = $disponible <= 0 || $cantidad > $disponible;

            $idUbicacion = (int) ($linea['id_ubicacion'] ?? 0);
            if ($idUbicacion > 0) {
                $producto = $this->obtenerProductoEnUbicacion($idUbicacion, $productoId);
                if ($producto && !empty($producto->ubi)) {
                    $lineas[$indice]['ubicacion'] = $producto->ubi;
                }
                if ($producto && empty($lineas[$indice]['unidad']) && !empty($producto->unidad)) {
                    $lineas[$indice]['unidad'] = (string) $producto->unidad;
                }
            }
        }

        return $lineas;
    }

    /** Abreviación o nombre de la unidad de medida del producto. */
    private function resolverUnidadProducto(int $productoId, ?string $fallback = null): string
    {
        if ($fallback !== null && trim($fallback) !== '') {
            return trim($fallback);
        }

        if ($productoId <= 0) {
            return '';
        }

        $producto = Productos::with('unidadMedida')->find($productoId);
        if (!$producto || !$producto->unidadMedida) {
            return '';
        }

        $um = $producto->unidadMedida;

        return trim((string) ($um->abreviacion ?: $um->nombre ?: ''));
    }

    /**
     * @param array<string, mixed> $linea
     */
    private function obtenerExistenciaDisponibleLinea(array $linea): float
    {
        $productoId = (int) ($linea['producto_id'] ?? 0);
        $idUbicacion = (int) ($linea['id_ubicacion'] ?? 0);

        if ($productoId <= 0) {
            return 0;
        }

        if ($idUbicacion > 0) {
            $producto = $this->obtenerProductoEnUbicacion($idUbicacion, $productoId);

            return $producto
                ? max(0, (float) $producto->cantidad_existente - (float) $producto->cantidad_reservada)
                : 0;
        }

        return $this->obtenerExistenciaTotalAlmacenGeneral($productoId);
    }

    private function obtenerExistenciaTotalAlmacenGeneral(int $productoId): float
    {
        $almacenGeneral = $this->obtenerAlmacenGeneral();
        if (!$almacenGeneral) {
            return 0;
        }

        $almacenId = (int) $almacenGeneral->id;
        $ubicacionIds = DB::table('tblubicaciones')
            ->where('id_almacen', $almacenId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Suma directa: no depende de joins de tipos de ubicación / listados.
        $total = (float) DB::table('tblexistencias')
            ->where('id_producto', $productoId)
            ->where(function ($q) use ($almacenId, $ubicacionIds) {
                $q->where('id_almacen', $almacenId);
                if (!empty($ubicacionIds)) {
                    $q->orWhereIn('id_ubicacion', $ubicacionIds);
                }
            })
            ->selectRaw('COALESCE(SUM(GREATEST(0, COALESCE(cantidad_existente,0) - COALESCE(cantidad_reservada,0))), 0) as total')
            ->value('total');

        return round($total, 4);
    }

    /**
     * Existencia disponible del producto en todos los almacenes/ubicaciones.
     * Se usa para validar materiales de receta en cotización/pedido (no solo AG).
     */
    private function obtenerExistenciaTotalProducto(int $productoId): float
    {
        if ($productoId <= 0) {
            return 0;
        }

        $total = (float) DB::table('tblexistencias')
            ->where('id_producto', $productoId)
            ->selectRaw('COALESCE(SUM(GREATEST(0, COALESCE(cantidad_existente,0) - COALESCE(cantidad_reservada,0))), 0) as total')
            ->value('total');

        return round($total, 4);
    }

    /**
     * @param array<string, mixed> $linea
     */
    private function calcularImporteLinea(array $linea): float
    {
        $bruto = (float) $linea['cantidad'] * (float) $linea['precio_unitario'];
        $descuento = (float) ($linea['descuento'] ?? 0);

        return round(max(0, $bruto - $descuento), 2);
    }

    /**
     * @param array<int, array<string, mixed>> $lineas
     * @return array{
     *   subtotal: float,
     *   descuento_porcentaje: float,
     *   descuento_importe: float,
     *   iva: float,
     *   iva_visible: float,
     *   importe_flete: float,
     *   flete_en_precios: bool,
     *   iva_en_precios: bool,
     *   total: float,
     *   lineas_efectivas: array<int, array<string, mixed>>
     * }
     */
    private function calcularTotales(
        array $lineas,
        float $descuentoPorcentaje = 0,
        float $importeFlete = 0,
        bool $fleteEnPrecios = false,
        bool $ivaEnPrecios = false,
        float $porcentajeIva = 16.0
    ): array {
        $importeFlete = max(0, round($importeFlete, 2));
        $porcentajeIva = max(0, round($porcentajeIva, 2));
        $factorIva = $this->factorIva($porcentajeIva);
        $lineasEfectivas = $lineas;

        // Si el check está activo, el flete se reparte en los importes de productos
        // y ya no se suma aparte al total.
        if ($fleteEnPrecios && $importeFlete > 0) {
            $lineasEfectivas = $this->prorratearFleteEnLineas($lineas, $importeFlete);
            $importeFleteVisible = 0.0;
        } else {
            $importeFleteVisible = $importeFlete;
        }

        $subtotal = round(collect($lineasEfectivas)->sum(function ($linea) {
            return (float) ($linea['importe'] ?? $this->calcularImporteLinea($linea));
        }), 2);

        $descuentoPorcentaje = min(100, max(0, round($descuentoPorcentaje, 2)));
        $descuentoImporte = round($subtotal * ($descuentoPorcentaje / 100), 2);
        $base = round(max(0, $subtotal - $descuentoImporte), 2);
        $iva = round($base * ($porcentajeIva / 100), 2);
        $ivaVisible = $ivaEnPrecios ? 0.0 : $iva;
        $total = round($base + $iva + $importeFleteVisible, 2);

        // Para vista/PDF con IVA en precios: líneas ya con IVA incluido.
        $lineasParaMostrar = $ivaEnPrecios
            ? $this->aplicarIvaEnLineas($lineasEfectivas, $porcentajeIva)
            : $lineasEfectivas;

        return [
            'subtotal' => $subtotal,
            'subtotal_visible' => $ivaEnPrecios ? round($base + $iva, 2) : $subtotal,
            'descuento_porcentaje' => $descuentoPorcentaje,
            'descuento_importe' => $descuentoImporte,
            'iva' => $iva,
            'iva_visible' => $ivaVisible,
            'porcentaje_iva' => $porcentajeIva,
            'importe_flete' => $importeFlete,
            'importe_flete_visible' => $importeFleteVisible,
            'flete_en_precios' => $fleteEnPrecios,
            'iva_en_precios' => $ivaEnPrecios,
            'mostrar_concepto_flete' => !$fleteEnPrecios && $importeFleteVisible > 0,
            'mostrar_concepto_iva' => !$ivaEnPrecios,
            'total' => $total,
            'lineas_efectivas' => $lineasEfectivas,
            'lineas_para_mostrar' => $lineasParaMostrar,
            'factor_iva' => $factorIva,
        ];
    }

    /**
     * Aplica la tasa de IVA a precio e importe de cada línea (para mostrar / PDF).
     *
     * @param array<int, array<string, mixed>> $lineas
     * @return array<int, array<string, mixed>>
     */
    private function aplicarIvaEnLineas(array $lineas, float $porcentajeIva = 16.0): array
    {
        $factor = $this->factorIva($porcentajeIva);
        $salida = [];
        foreach ($lineas as $linea) {
            $cantidad = (float) ($linea['cantidad'] ?? 0);
            $importe = round((float) ($linea['importe'] ?? $this->calcularImporteLinea($linea)), 2);
            $importeConIva = round($importe * $factor, 2);
            $precio = round((float) ($linea['precio_unitario'] ?? 0), 2);
            $precioConIva = $cantidad > 0
                ? round($importeConIva / $cantidad, 2)
                : round($precio * $factor, 2);

            $linea['precio_unitario'] = $precioConIva;
            $linea['importe'] = $importeConIva;
            $linea['precio_unitario_con_iva'] = $precioConIva;
            $linea['importe_con_iva'] = $importeConIva;
            $salida[] = $linea;
        }

        return $salida;
    }

    /**
     * Montos con IVA a partir de una línea sin IVA.
     *
     * @return array{precio_unitario_con_iva: float, importe_con_iva: float}
     */
    private function montosConIvaLinea(
        float $precioUnitario,
        float $cantidad,
        float $descuento,
        float $importe,
        float $porcentajeIva = 16.0
    ): array {
        $factor = $this->factorIva($porcentajeIva);
        $importeConIva = round($importe * $factor, 2);
        $precioConIva = $cantidad > 0
            ? round($importeConIva / $cantidad, 2)
            : round($precioUnitario * $factor, 2);

        return [
            'precio_unitario_con_iva' => $precioConIva,
            'importe_con_iva' => $importeConIva,
        ];
    }

    /**
     * Reparte el importe de flete proporcionalmente al importe de cada línea.
     *
     * @param array<int, array<string, mixed>> $lineas
     * @return array<int, array<string, mixed>>
     */
    private function prorratearFleteEnLineas(array $lineas, float $importeFlete): array
    {
        if ($importeFlete <= 0 || empty($lineas)) {
            return $lineas;
        }

        $baseImportes = [];
        $suma = 0.0;
        foreach ($lineas as $i => $linea) {
            $importe = round((float) ($linea['importe'] ?? $this->calcularImporteLinea($linea)), 2);
            $baseImportes[$i] = max(0, $importe);
            $suma += $baseImportes[$i];
        }

        if ($suma <= 0) {
            // Sin importes: repartir por cantidad.
            $sumaCant = 0.0;
            foreach ($lineas as $linea) {
                $sumaCant += max(0, (float) ($linea['cantidad'] ?? 0));
            }
            if ($sumaCant <= 0) {
                return $lineas;
            }
            $asignado = 0.0;
            $indices = array_keys($lineas);
            $ultimo = end($indices);
            foreach ($lineas as $i => $linea) {
                $cant = max(0, (float) ($linea['cantidad'] ?? 0));
                $parte = ($i === $ultimo)
                    ? round($importeFlete - $asignado, 2)
                    : round($importeFlete * ($cant / $sumaCant), 2);
                $asignado += $parte;
                $nuevoImporte = round((float) ($linea['importe'] ?? 0) + $parte, 2);
                $lineas[$i]['importe'] = $nuevoImporte;
                if ($cant > 0) {
                    $lineas[$i]['precio_unitario'] = round($nuevoImporte / $cant, 4);
                }
            }

            return $lineas;
        }

        $asignado = 0.0;
        $indices = array_keys($lineas);
        $ultimo = end($indices);
        foreach ($lineas as $i => $linea) {
            $parte = ($i === $ultimo)
                ? round($importeFlete - $asignado, 2)
                : round($importeFlete * ($baseImportes[$i] / $suma), 2);
            $asignado += $parte;
            $nuevoImporte = round($baseImportes[$i] + $parte, 2);
            $cantidad = max(0, (float) ($linea['cantidad'] ?? 0));
            $lineas[$i]['importe'] = $nuevoImporte;
            if ($cantidad > 0) {
                $lineas[$i]['precio_unitario'] = round($nuevoImporte / $cantidad, 4);
            }
        }

        return $lineas;
    }

    /**
     * Prorratea flete sobre colección de detalles Eloquent (solo para PDF / vista).
     *
     * @param \Illuminate\Support\Collection<int, mixed> $detalles
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function prorratearFleteEnDetalles($detalles, float $importeFlete)
    {
        $lineas = [];
        foreach ($detalles as $detalle) {
            $lineas[] = [
                'cantidad' => (float) $detalle->cantidad,
                'precio_unitario' => (float) $detalle->precio_unitario,
                'descuento' => (float) ($detalle->descuento ?? 0),
                'importe' => (float) $detalle->importe,
            ];
        }

        $ajustadas = $this->prorratearFleteEnLineas($lineas, $importeFlete);

        return $detalles->values()->map(function ($detalle, $i) use ($ajustadas) {
            $clon = clone $detalle;
            $clon->precio_unitario = $ajustadas[$i]['precio_unitario'] ?? $detalle->precio_unitario;
            $clon->importe = $ajustadas[$i]['importe'] ?? $detalle->importe;

            return $clon;
        });
    }

    /**
     * Aplica la tasa de IVA a precio e importe de cada detalle (para PDF / vista).
     *
     * @param \Illuminate\Support\Collection|array $detalles
     * @return \Illuminate\Support\Collection
     */
    private function aplicarIvaEnDetalles($detalles, float $porcentajeIva = 16.0)
    {
        $factor = $this->factorIva($porcentajeIva);

        return collect($detalles)->values()->map(function ($detalle) use ($factor) {
            $clon = clone $detalle;
            $cantidad = (float) ($detalle->cantidad ?? 0);
            $importe = round((float) ($detalle->importe ?? 0), 2);
            $precio = round((float) ($detalle->precio_unitario ?? 0), 2);
            $importeConIva = round($importe * $factor, 2);

            $clon->importe = $importeConIva;
            $clon->precio_unitario = $cantidad > 0
                ? round($importeConIva / $cantidad, 2)
                : round($precio * $factor, 2);

            return $clon;
        });
    }

    private function porcentajeIvaPorDefecto(): float
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $pct = TipoIva::where('codigo', 'GENERAL')->where('estatus', 'A')->value('porcentaje');
        $cache = $pct !== null ? round((float) $pct, 2) : 16.0;

        return $cache;
    }

    private function tipoIvaIdPorDefecto(): ?int
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache ?: null;
        }

        $id = TipoIva::where('codigo', 'GENERAL')->where('estatus', 'A')->value('id')
            ?: TipoIva::where('estatus', 'A')->orderBy('porcentaje')->value('id');
        $cache = $id ? (int) $id : 0;

        return $cache ?: null;
    }

    private function resolverPorcentajeIva(?int $tipoIvaId, ?float $fallback = null): float
    {
        if ($tipoIvaId) {
            $pct = TipoIva::where('id', $tipoIvaId)->where('estatus', 'A')->value('porcentaje');
            if ($pct !== null) {
                return round((float) $pct, 2);
            }
        }

        return round($fallback ?? $this->porcentajeIvaPorDefecto(), 2);
    }

    private function factorIva(float $porcentajeIva): float
    {
        return 1 + (max(0, $porcentajeIva) / 100);
    }

    private function resolverMonedaId(?int $monedaId, int $clienteId): ?int
    {
        if ($monedaId && Moneda::where('id', $monedaId)->exists()) {
            return $monedaId;
        }

        $clienteMoneda = Clientes::where('id', $clienteId)->value('moneda_id');
        if ($clienteMoneda) {
            return (int) $clienteMoneda;
        }

        $mxn = Moneda::where('codigo', 'MXN')->value('id');

        return $mxn ? (int) $mxn : null;
    }

    private function generarFolio(): string
    {
        $anio = date('Y');
        $consecutivo = VentaCotizacion::whereRaw('YEAR(created_at) = ?', [$anio])->count() + 1;

        return sprintf('COT-%s-%04d', $anio, $consecutivo);
    }

    private function generarFolioPedido(): string
    {
        $anio = date('Y');
        $consecutivo = VentaPedido::whereRaw('YEAR(created_at) = ?', [$anio])->count() + 1;

        return sprintf('PED-%s-%04d', $anio, $consecutivo);
    }

    private function usuarioEsMaster(?int $userId = null): bool
    {
        $userId = $userId ?? (int) auth()->id();

        return DB::table('tblusuario_perfiles')
            ->join('tblperfiles', 'tblperfiles.id', '=', 'tblusuario_perfiles.id_perfil')
            ->where('tblusuario_perfiles.id_usuario', $userId)
            ->whereRaw('LOWER(tblperfiles.nombre) = ?', ['master'])
            ->exists();
    }

    private function puedeEditarPrecioVenta(): bool
    {
        if ($this->usuarioEsMaster()) {
            return true;
        }

        $userId = (int) auth()->id();
        if ($userId <= 0) {
            return false;
        }

        return DB::table('tblusuario_perfiles')
            ->join('tblperfil_acciones', 'tblperfil_acciones.idperfil', '=', 'tblusuario_perfiles.id_perfil')
            ->join('tblacciones', 'tblacciones.id', '=', 'tblperfil_acciones.idaccion')
            ->where('tblusuario_perfiles.id_usuario', $userId)
            ->where('tblacciones.nombre_accion', 'editar_precio_venta')
            ->exists();
    }

    private function validarAccesoCotizacion(VentaCotizacion $cotizacion): void
    {
        if ($this->usuarioEsMaster()) {
            return;
        }

        if ((int) $cotizacion->usuario_id !== (int) auth()->id()) {
            abort(403, 'No tiene permiso para ver esta cotización.');
        }
    }

    private function validarPreciosUnitariosCotizacion(array $lineas): void
    {
        foreach ($lineas as $indice => $linea) {
            $precio = round((float) ($linea['precio_unitario'] ?? 0), 2);
            if ($precio <= 0) {
                $nombre = trim((string) ($linea['nombre'] ?? $linea['sku'] ?? ('Línea ' . ((int) $indice + 1))));
                throw ValidationException::withMessages([
                    'lineas' => 'El producto "' . $nombre . '" no tiene precio unitario. Registre el costo de venta del producto antes de guardar la cotización.',
                ]);
            }
        }
    }

    private function pedidoProduccionCancelada(VentaPedido $pedido): bool
    {
        $orden = $pedido->ordenProduccion;
        if (!$orden) {
            $orden = OrdenProduccion::where('pedido_id', $pedido->id)->orderByDesc('id')->first();
        }

        return $orden && $orden->estatus === OrdenProduccion::ESTATUS_CANCELADA;
    }

    private function aplicarArchivoClienteCotizacion(Request $request, VentaCotizacion $cotizacion, int $clienteId): void
    {
        if (!$request->hasFile('archivo_cliente')) {
            return;
        }

        $archivo = $this->guardarArchivoClienteCotizacion(
            $request->file('archivo_cliente'),
            $clienteId,
            (int) $cotizacion->id,
            $cotizacion->archivo_cliente_ruta
        );

        $cotizacion->update([
            'archivo_cliente_ruta' => $archivo['ruta'],
            'archivo_cliente_nombre' => $archivo['nombre'],
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{ruta: string, nombre: string}
     */
    private function guardarArchivoClienteCotizacion($archivo, int $clienteId, int $cotizacionId, ?string $rutaAnterior = null): array
    {
        $cliente = Clientes::find($clienteId);
        $slug = $cliente ? Str::slug(Str::limit($cliente->nombre, 40, '')) : 'cliente';
        if ($slug === '') {
            $slug = 'cliente';
        }

        $carpetaRelativa = 'archivos/cotizaciones/' . $clienteId . '_' . $slug;
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
        $baseSeguro = preg_replace('/[^a-zA-Z0-9._-]/', '_', $base) ?: 'archivo';
        $nombreAlmacenado = 'cot' . $cotizacionId . '_' . time() . '_' . $baseSeguro . ($extension ? '.' . $extension : '');

        $archivo->move($destino, $nombreAlmacenado);

        return [
            'ruta' => $carpetaRelativa . '/' . $nombreAlmacenado,
            'nombre' => $nombreOriginal,
        ];
    }

    private function descontarExistenciaAlmacenGeneral(
        int $productoId,
        float $cantidad,
        int $idAlmacen,
        ?string $documentoReferencia = null,
        ?string $observaciones = null
    ): void {
        $pendiente = round($cantidad, 2);
        $tipoSalida = (int) (DB::table('tbltipos_movimientos_inventario')
            ->where('nombre_movimiento', 'Salida a producción')
            ->value('id') ?: 0);
        $usuarioId = (int) (auth()->id() ?? 0);
        $fecha = now()->format('Y-m-d');
        $doc = Str::limit($documentoReferencia ?: ('OP-' . $fecha), 200, '');

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
            $nuevaCantidad = round((float) $existencia->cantidad_existente - $aDescontar, 2);

            DB::table('tblexistencias')
                ->where('id', $existencia->id)
                ->update(['cantidad_existente' => $nuevaCantidad]);

            if ($tipoSalida > 0 && $usuarioId > 0 && $documentoReferencia !== null) {
                $cantidadMov = (int) max(1, (int) round($aDescontar));
                DB::table('tblmovimientos_inventario')->insert([
                    'id_producto' => $productoId,
                    'id_almacen' => $idAlmacen,
                    'id_ubicacion' => (int) ($existencia->id_ubicacion ?? 0),
                    'id_tipo_movimiento' => $tipoSalida,
                    'cantidad_producto_movimiento' => $cantidadMov,
                    'fecha_movimiento' => $fecha,
                    'documento_referencia' => $doc,
                    'observaciones' => $observaciones
                        ?: ('Salida a producción · producto #' . $productoId . ' · cant. ' . number_format($aDescontar, 2)),
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
     * Recalcula totales del borrador de cotización con IVA / flete actuales.
     */
    public function recalcularCotizacion(Request $request): JsonResponse
    {
        $this->normalizarRequestCotizacion($request);
        $this->validarBorradorEditable();

        $borrador = $this->aplicarEncabezadoCotizacionDesdeRequest($request, $this->obtenerBorrador());
        session([self::SESSION_BORRADOR => $borrador]);

        return $this->respuestaFragmentosCotizacion(
            $borrador,
            (int) $request->input('vendedor_id', auth()->id()),
            'Totales actualizados con IVA y flete.'
        );
    }

    /**
     * Recalcula totales del borrador de pedido con IVA / flete actuales.
     */
    public function recalcularPedido(Request $request): JsonResponse
    {
        $this->normalizarRequestPedido($request);

        $borrador = $this->aplicarEncabezadoPedidoDesdeRequest($request, $this->obtenerBorradorPedido());
        session([self::SESSION_BORRADOR_PEDIDO => $borrador]);

        return $this->respuestaFragmentosPedido(
            $borrador,
            (int) $request->input('vendedor_id', auth()->id()),
            'Totales actualizados con IVA y flete.'
        );
    }

    /**
     * @param array{lineas: array<int, array<string, mixed>>, encabezado: array<string, mixed>} $borrador
     * @return array{lineas: array<int, array<string, mixed>>, encabezado: array<string, mixed>}
     */
    private function aplicarEncabezadoCotizacionDesdeRequest(Request $request, array $borrador): array
    {
        $tipoIvaId = $request->filled('tipo_iva_id')
            ? (int) $request->input('tipo_iva_id')
            : (isset($borrador['encabezado']['tipo_iva_id']) ? (int) $borrador['encabezado']['tipo_iva_id'] : $this->tipoIvaIdPorDefecto());

        $borrador['encabezado'] = array_merge($borrador['encabezado'] ?? [], [
            'cliente_id' => $request->input('cliente_id') ?: ($borrador['encabezado']['cliente_id'] ?? null),
            'fecha_vencimiento' => $request->input('fecha_vencimiento') ?: ($borrador['encabezado']['fecha_vencimiento'] ?? null),
            'observaciones' => $request->input('observaciones') ?: ($borrador['encabezado']['observaciones'] ?? null),
            'tiempo_entrega' => $request->input('tiempo_entrega') ?? ($borrador['encabezado']['tiempo_entrega'] ?? null),
            'persona_atencion' => $request->input('persona_atencion') ?? ($borrador['encabezado']['persona_atencion'] ?? null),
            'moneda_id' => $request->input('moneda_id') ?: ($borrador['encabezado']['moneda_id'] ?? null),
            'tipo_flete_id' => $request->input('tipo_flete_id') ?: ($borrador['encabezado']['tipo_flete_id'] ?? null),
            'condicion_pago_id' => $request->input('condicion_pago_id') ?: ($borrador['encabezado']['condicion_pago_id'] ?? null),
            'importe_flete' => is_numeric($request->input('importe_flete'))
                ? (float) $request->input('importe_flete')
                : (float) ($borrador['encabezado']['importe_flete'] ?? 0),
            'flete_en_precios' => $request->has('flete_en_precios')
                ? $request->boolean('flete_en_precios')
                : (bool) ($borrador['encabezado']['flete_en_precios'] ?? false),
            'iva_en_precios' => $request->has('iva_en_precios')
                ? $request->boolean('iva_en_precios')
                : (bool) ($borrador['encabezado']['iva_en_precios'] ?? false),
            'tipo_iva_id' => $tipoIvaId,
            'porcentaje_iva' => $this->resolverPorcentajeIva(
                $tipoIvaId,
                (float) ($borrador['encabezado']['porcentaje_iva'] ?? $this->porcentajeIvaPorDefecto())
            ),
            'descuento' => is_numeric($request->input('descuento'))
                ? (float) $request->input('descuento')
                : (float) ($borrador['encabezado']['descuento'] ?? 0),
            'id_ubicacion' => $request->input('id_ubicacion') ?: ($borrador['encabezado']['id_ubicacion'] ?? null),
            'buscar' => trim((string) ($request->input('buscar', $borrador['encabezado']['buscar'] ?? ''))),
        ]);

        return $borrador;
    }

    /**
     * @param array{lineas: array<int, array<string, mixed>>, encabezado: array<string, mixed>} $borrador
     * @return array{lineas: array<int, array<string, mixed>>, encabezado: array<string, mixed>}
     */
    private function aplicarEncabezadoPedidoDesdeRequest(Request $request, array $borrador): array
    {
        $tipoIvaId = $request->filled('tipo_iva_id')
            ? (int) $request->input('tipo_iva_id')
            : (isset($borrador['encabezado']['tipo_iva_id']) ? (int) $borrador['encabezado']['tipo_iva_id'] : $this->tipoIvaIdPorDefecto());

        $borrador['encabezado'] = array_merge($borrador['encabezado'] ?? [], [
            'cliente_id' => $request->input('cliente_id') ?: ($borrador['encabezado']['cliente_id'] ?? null),
            'cotizacion_id' => $request->input('cotizacion_id') ?: ($borrador['encabezado']['cotizacion_id'] ?? null),
            'fecha_pedido' => $request->input('fecha_pedido') ?: ($borrador['encabezado']['fecha_pedido'] ?? null),
            'hora_pedido' => $request->input('hora_pedido') ?: ($borrador['encabezado']['hora_pedido'] ?? null),
            'fecha_entrega' => $request->input('fecha_entrega') ?: ($borrador['encabezado']['fecha_entrega'] ?? null),
            'hora_entrega' => $request->input('hora_entrega') ?: ($borrador['encabezado']['hora_entrega'] ?? null),
            'observaciones' => $request->input('observaciones') ?: ($borrador['encabezado']['observaciones'] ?? null),
            'tiempo_entrega' => $request->input('tiempo_entrega') ?? ($borrador['encabezado']['tiempo_entrega'] ?? null),
            'persona_atencion' => $request->input('persona_atencion') ?? ($borrador['encabezado']['persona_atencion'] ?? null),
            'moneda_id' => $request->input('moneda_id') ?: ($borrador['encabezado']['moneda_id'] ?? null),
            'tipo_flete_id' => $request->input('tipo_flete_id') ?: ($borrador['encabezado']['tipo_flete_id'] ?? null),
            'condicion_pago_id' => $request->input('condicion_pago_id') ?: ($borrador['encabezado']['condicion_pago_id'] ?? null),
            'importe_flete' => is_numeric($request->input('importe_flete'))
                ? (float) $request->input('importe_flete')
                : (float) ($borrador['encabezado']['importe_flete'] ?? 0),
            'flete_en_precios' => $request->has('flete_en_precios')
                ? $request->boolean('flete_en_precios')
                : (bool) ($borrador['encabezado']['flete_en_precios'] ?? false),
            'iva_en_precios' => $request->has('iva_en_precios')
                ? $request->boolean('iva_en_precios')
                : (bool) ($borrador['encabezado']['iva_en_precios'] ?? false),
            'tipo_iva_id' => $tipoIvaId,
            'porcentaje_iva' => $this->resolverPorcentajeIva(
                $tipoIvaId,
                (float) ($borrador['encabezado']['porcentaje_iva'] ?? $this->porcentajeIvaPorDefecto())
            ),
            'descuento' => is_numeric($request->input('descuento'))
                ? (float) $request->input('descuento')
                : (float) ($borrador['encabezado']['descuento'] ?? 0),
            'id_ubicacion' => $request->input('id_ubicacion') ?: ($borrador['encabezado']['id_ubicacion'] ?? null),
            'buscar' => trim((string) ($request->input('buscar', $borrador['encabezado']['buscar'] ?? ''))),
        ]);

        return $borrador;
    }

    /**
     * Respuesta JSON con fragmentos Blade para actualizar el modal sin recargar.
     *
     * @param array<string, mixed> $borrador
     */
    private function respuestaFragmentosCotizacion(array $borrador, int $vendedorId, string $mensaje): JsonResponse
    {
        $totales = $this->calcularTotales(
            $borrador['lineas'],
            (float) ($borrador['encabezado']['descuento'] ?? 0),
            (float) ($borrador['encabezado']['importe_flete'] ?? 0),
            (bool) ($borrador['encabezado']['flete_en_precios'] ?? false),
            (bool) ($borrador['encabezado']['iva_en_precios'] ?? false),
            (float) ($borrador['encabezado']['porcentaje_iva'] ?? $this->porcentajeIvaPorDefecto())
        );

        $borradorVista = $this->borradorConMontosVisibles($borrador, $totales);

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'html_lineas' => view('Ventas.partials.cotizacion_lineas_tbody', [
                'borrador' => $borradorVista,
                'vendedorId' => $vendedorId,
                'puedeEditarPrecio' => $this->puedeEditarPrecioVenta(),
                'soloLectura' => false,
                'totales' => $totales,
            ])->render(),
            'html_totales' => view('Ventas.partials.cotizacion_totales', [
                'borrador' => $borrador,
                'totales' => $totales,
                'monedas' => Moneda::where('estatus', 'A')->orderBy('codigo')->get(),
            ])->render(),
            'totales' => $totales,
        ]);
    }

    /**
     * Añade precio/importe visibles (con flete e IVA en precios si aplica) sin alterar la base editable.
     *
     * @param array{lineas: array<int, array<string, mixed>>, encabezado: array<string, mixed>} $borrador
     * @param array<string, mixed> $totales
     * @return array{lineas: array<int, array<string, mixed>>, encabezado: array<string, mixed>}
     */
    private function borradorConMontosVisibles(array $borrador, array $totales): array
    {
        $mostrar = $totales['lineas_para_mostrar'] ?? ($totales['lineas_efectivas'] ?? $borrador['lineas']);
        $flagsActivos = !empty($totales['flete_en_precios']) || !empty($totales['iva_en_precios']);

        foreach ($borrador['lineas'] as $i => $linea) {
            $visible = $mostrar[$i] ?? $linea;
            $borrador['lineas'][$i]['precio_visible'] = (float) ($visible['precio_unitario'] ?? $linea['precio_unitario'] ?? 0);
            $borrador['lineas'][$i]['importe_visible'] = (float) ($visible['importe'] ?? $linea['importe'] ?? 0);
            $borrador['lineas'][$i]['mostrar_precio_efectivo'] = $flagsActivos;
        }

        return $borrador;
    }

    /**
     * Facturas CFDI timbradas ligadas al pedido (por id y/o folio).
     */
    private function obtenerFacturasPedido(int $pedidoId, string $folio = '')
    {
        $folio = trim($folio);

        return facturacionproductos::query()
            ->where('tipo_serv', 'pedido_venta')
            ->whereNotNull('Uuid')
            ->where('Uuid', '!=', '')
            ->where(function ($q) use ($pedidoId, $folio) {
                $q->where('id_serv_enc', $pedidoId);
                if ($folio !== '') {
                    $q->orWhere('folio', $folio);
                }
            })
            ->orderByDesc('id')
            ->get()
            ->unique('id')
            ->values();
    }
}
