@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $enc = $borrador['encabezado'] ?? [];
    $estatusClase = [
        'ACEPTADA' => 'cotizacion-badge--autorizada',
        'ENVIADA' => 'cotizacion-badge--enviada',
        'BORRADOR' => 'cotizacion-badge--borrador',
        'RECHAZADA' => 'cotizacion-badge--borrador',
        'VENCIDA' => 'cotizacion-badge--borrador',
        'CONVERTIDA' => 'cotizacion-badge--cerrada',
    ];
    $cotizacionId = (int) ($enc['cotizacion_id'] ?? 0);
    $folioCotizacion = $enc['folio'] ?? '';
    $estatusCotizacion = $enc['estatus'] ?? 'BORRADOR';
    $estatusNoEditables = ['CONVERTIDA', 'RECHAZADA', 'VENCIDA'];
    $cotizacionSoloLectura = $cotizacionId > 0 && in_array($estatusCotizacion, $estatusNoEditables, true);
    $cotizacionEnEdicion = $cotizacionId > 0 && !$cotizacionSoloLectura;
    $puedeConvertirPedido = $cotizacionId > 0 && in_array($estatusCotizacion, ['ENVIADA', 'ACEPTADA'], true);
    $cotizacionConvertirTieneFaltantes = false;
    if ($puedeConvertirPedido) {
        $cotizacionConvertir = $cotizaciones->firstWhere('id', $cotizacionId);
        $cotizacionConvertirTieneFaltantes = (bool) ($cotizacionConvertir->tiene_faltantes_existencia ?? false);
    }
@endphp

<div class="container-fluid acciones-config-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start header">
                <div class="d-flex align-items-center min-w-0">
                    <div class="header-icon me-3 flex-shrink-0">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="mb-1">
                            <a href="javascript:history.back()" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Volver
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Pedidos y cotizaciones</h2>
                        <p class="text-muted mb-0">Gestiona pedidos y cotizaciones del vendedor.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <form method="POST" action="{{ route('ventas.pedido.limpiar') }}" class="m-0">
                        @csrf
                        <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                        <input type="hidden" name="abrir_modal" value="1">
                        <button type="submit" class="btn btn-baseColor fs-7">
                            <i class="fa-solid fa-plus"></i>
                            <span>Nuevo pedido</span>
                        </button>
                    </form>
                    <a class="btn btn-baseColor-light fs-7" href="{{ route('almacen.cargas') }}">
                        <i class="fa-solid fa-truck"></i>
                        <span>Cargas</span>
                    </a>
                    <a class="btn btn-baseColor-light fs-7" href="{{ route('ventas.cotizaciones.historial', ['vendedor_id' => $vendedorId]) }}">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Historial</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="pedidos-workspace">
        <aside id="panelCotizaciones" class="cotizaciones-panel">
            <div class="cotizaciones-panel__header">
                <button type="button" class="cotizaciones-panel__toggle" id="btnToggleCotizaciones" title="Ocultar panel">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <div class="cotizaciones-panel__title cotizaciones-panel__expandable">
                    <h6><i class="fa-solid fa-file-invoice-dollar me-1"></i> Cotizaciones</h6>
                    <p>Del vendedor seleccionado</p>
                </div>
            </div>

            <div class="cotizaciones-panel__collapsed-label" id="labelExpandirCotizaciones" title="Mostrar cotizaciones">
                <span class="cotizaciones-panel__collapsed-icon">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </span>
                <span class="cotizaciones-panel__collapsed-text">Cotizaciones</span>
            </div>

            <div class="cotizaciones-panel__expandable cotizaciones-panel__top-actions">
                <form method="POST" action="{{ route('ventas.cotizacion.limpiar') }}">
                    @csrf
                    <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                    <input type="hidden" name="abrir_modal" value="1">
                    <button type="submit" class="btn btn-baseColor w-100 fs-8">
                        <i class="fa-solid fa-plus"></i> Nueva cotización
                    </button>
                </form>
            </div>

            <div class="cotizaciones-panel__expandable cotizaciones-panel__filters">
                <form method="GET" action="{{ route('ventas.pedidos') }}">
                    <label class="form-label fs-8 mb-1 text-muted">Vendedor</label>
                    <select class="form-select form-select-sm mb-2" name="vendedor_id">
                        @foreach ($vendedores as $vendedor)
                            <option value="{{ $vendedor->id }}" {{ (int) $vendedorId === (int) $vendedor->id ? 'selected' : '' }}>
                                {{ $vendedor->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-blue-light btn-sm w-100">Filtrar</button>
                </form>
            </div>

            <div class="cotizaciones-panel__expandable cotizaciones-panel__body">
                @forelse ($cotizaciones as $cotizacion)
                    <div class="cotizacion-item" data-cotizacion-id="{{ $cotizacion->id }}">
                        <button class="cotizacion-item__trigger collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#cotizacionDetalle{{ $cotizacion->id }}"
                            aria-expanded="false">
                            <i class="fa-solid fa-chevron-right cotizacion-item__chevron"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between align-items-start gap-1">
                                    <span class="cotizacion-item__folio">{{ $cotizacion->folio ?: 'Sin folio' }}</span>
                                    <span class="cotizacion-badge {{ $estatusClase[$cotizacion->estatus] ?? 'cotizacion-badge--borrador' }}">
                                        {{ $cotizacion->estatus }}
                                    </span>
                                </div>
                                <div class="cotizacion-item__cliente">{{ $cotizacion->cliente->nombre ?? '—' }}</div>
                                <div class="cotizacion-item__meta">
                                    <span>{{ $cotizacion->fecha ? $cotizacion->fecha->format('d/m/Y') : '—' }}</span>
                                    <span class="fw-semibold text-dark">${{ number_format($cotizacion->total, 2) }}</span>
                                </div>
                            </div>
                        </button>
                        <div class="cotizacion-item__actions">
                            <form method="POST" action="{{ route('ventas.cotizacion.cargar', $cotizacion->id) }}">
                                @csrf
                                <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                                <button type="submit" class="btn btn-blue-light btn-sm w-100 cotizacion-item__ver">
                                    <i class="fa-solid fa-eye"></i> Ver detalle
                                </button>
                            </form>
                            @if (in_array($cotizacion->estatus, ['ENVIADA', 'ACEPTADA'], true))
                                @php
                                    $msgConvertir = !empty($cotizacion->tiene_faltantes_existencia)
                                        ? '¿Convertir la cotización ' . $cotizacion->folio . ' a pedido? Hay productos sin existencia: irán al reporte de no existencias y NO se descontará nada del almacén.'
                                        : '¿Convertir la cotización ' . $cotizacion->folio . ' a pedido? En esta conversión no se descuenta inventario del almacén.';
                                @endphp
                                <form method="POST" action="{{ route('ventas.cotizacion.convertir_pedido', $cotizacion->id) }}" class="mt-1"
                                    onsubmit="return confirm(@json($msgConvertir));">
                                    @csrf
                                    <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                                    <button type="submit" class="btn btn-blue btn-sm w-100">
                                        <i class="fa-solid fa-cart-arrow-down"></i> Convertir a pedido
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div class="collapse" id="cotizacionDetalle{{ $cotizacion->id }}">
                            <div class="cotizacion-item__body">
                                <div class="row g-2 fs-8 text-muted mb-2">
                                    <div class="col-6">Partidas: <strong class="text-dark">{{ $cotizacion->detalles_count }}</strong></div>
                                    <div class="col-6">Vence:
                                        <strong class="text-dark">
                                            {{ $cotizacion->fecha_vencimiento ? $cotizacion->fecha_vencimiento->format('d/m/Y') : '—' }}
                                        </strong>
                                    </div>
                                </div>
                                @if ($cotizacion->observaciones)
                                    <p class="fs-8 text-muted mb-0">{{ $cotizacion->observaciones }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="cotizaciones-empty">
                        <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                        Sin cotizaciones para este vendedor.
                    </div>
                @endforelse
            </div>
        </aside>

        <div class="pedidos-main">
            <div class="card border-0 shadow bg-body rounded-5 pedidos-main-card overflow-hidden">
                <div class="cotizaciones-panel__header">
                    <div class="cotizaciones-panel__title">
                        <h6><i class="fa-solid fa-list-check me-1"></i> Listado de pedidos</h6>
                        <p>Consulta, edita o da de alta pedidos de venta.</p>
                    </div>
                </div>
                <div class="table-responsive p-3">
                    <table class="table table-striped table-hover display modern-table mb-0" id="table">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Cotización</th>
                                <th>Entrega</th>
                                <th>Total</th>
                                <th>Estatus</th>
                                <th>Opciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pedidos as $pedido)
                                <tr>
                                    <td>{{ $pedido->folio ?: '—' }}</td>
                                    <td>{{ $pedido->fecha ? $pedido->fecha->format('d/m/Y H:i') : '—' }}</td>
                                    <td>{{ $pedido->cliente->nombre ?? '—' }}</td>
                                    <td>{{ $pedido->cotizacion->folio ?? '—' }}</td>
                                    <td>
                                        @if ($pedido->fecha_entrega)
                                            {{ $pedido->fecha_entrega->format('d/m/Y') }}
                                            @if ($pedido->hora_entrega)
                                                {{ substr((string) $pedido->hora_entrega, 0, 5) }}
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>${{ number_format($pedido->total, 2) }}</td>
                                    <td>{{ $pedido->estatus }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('ventas.pedido.cargar', $pedido->id) }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                                            <button type="submit" class="btn btn-blue-light btn-sm">
                                                <i class="fa-solid fa-eye"></i> Detalle
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-muted text-center py-4">Sin pedidos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $encPedido = $borradorPedido['encabezado'] ?? [];
@endphp

<div class="modal fade" id="modalNuevoPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-secondary"><i class="fa-solid fa-circle-plus me-2"></i>Nuevo pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0 modern-form" id="pedido-borrador-panel">
                @include('Ventas.partials.pedido_borrador')
            </div>
            <div class="modal-footer border-0">
                <form method="POST" action="{{ route('ventas.pedido.limpiar') }}" class="d-inline me-auto"
                    onsubmit="return confirm('¿Limpiar el borrador del pedido?');">
                    @csrf
                    <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                    <button type="submit" class="btn btn-outline-danger">Limpiar borrador</button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <form method="POST" action="{{ route('ventas.pedido.guardar') }}" class="d-inline" id="form-guardar-pedido">
                    @csrf
                    <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                    <input type="hidden" name="contexto_formulario" value="nuevo_pedido">
                    <input type="hidden" name="cliente_id" value="{{ $encPedido['cliente_id'] ?? '' }}">
                    <input type="hidden" name="persona_atencion" value="{{ $encPedido['persona_atencion'] ?? '' }}">
                    <input type="hidden" name="cotizacion_id" value="{{ $encPedido['cotizacion_id'] ?? '' }}">
                    <input type="hidden" name="fecha_pedido" value="{{ $encPedido['fecha_pedido'] ?? date('Y-m-d') }}">
                    <input type="hidden" name="hora_pedido" value="{{ $encPedido['hora_pedido'] ?? date('H:i') }}">
                    <input type="hidden" name="fecha_entrega" value="{{ $encPedido['fecha_entrega'] ?? '' }}">
                    <input type="hidden" name="hora_entrega" value="{{ $encPedido['hora_entrega'] ?? '' }}">
                    <input type="hidden" name="observaciones" value="{{ $encPedido['observaciones'] ?? '' }}">
                    <input type="hidden" name="tiempo_entrega" value="{{ $encPedido['tiempo_entrega'] ?? '' }}">
                    <input type="hidden" name="moneda_id" value="{{ $encPedido['moneda_id'] ?? '' }}">
                    <input type="hidden" name="condicion_pago_id" value="{{ $encPedido['condicion_pago_id'] ?? '' }}">
                    <input type="hidden" name="tipo_flete_id" value="{{ $encPedido['tipo_flete_id'] ?? '' }}">
                    <input type="hidden" name="importe_flete" value="{{ $encPedido['importe_flete'] ?? 0 }}">
                    <input type="hidden" name="flete_en_precios" value="{{ !empty($encPedido['flete_en_precios']) ? 1 : 0 }}">
                    <input type="hidden" name="iva_en_precios" value="{{ !empty($encPedido['iva_en_precios']) ? 1 : 0 }}">
                    <input type="hidden" name="tipo_iva_id" value="{{ $encPedido['tipo_iva_id'] ?? '' }}">
                    <input type="hidden" name="descuento" value="{{ $encPedido['descuento'] ?? 0 }}">
                    <button type="submit" class="btn btn-blue" {{ empty($encPedido['cliente_id']) && empty(old('cliente_id')) ? 'disabled' : '' }}
                        id="btn-guardar-pedido">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar pedido
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaCotizacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-secondary">
                    @if ($cotizacionSoloLectura)
                        <i class="fa-solid fa-file-invoice me-2"></i>Cotización {{ $folioCotizacion ?: 'sin folio' }}
                    @elseif ($cotizacionEnEdicion)
                        <i class="fa-solid fa-pen-to-square me-2"></i>Editar cotización {{ $folioCotizacion }}
                    @else
                        <i class="fa-solid fa-circle-plus me-2"></i>Nueva cotización
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0 modern-form" id="cotizacion-borrador-panel">
                @if ($cotizacionSoloLectura)
                    <div class="alert alert-secondary border-0 rounded-3 py-2 mb-2 fs-8">
                        <i class="fa-solid fa-lock me-1"></i>
                        Esta cotización está en estatus <strong>{{ $estatusCotizacion }}</strong> y no puede modificarse.
                    </div>
                @endif
                @include('Ventas.partials.cotizacion_borrador', ['soloLectura' => $cotizacionSoloLectura])
            </div>
            <div class="modal-footer border-0">
                @if (!$cotizacionSoloLectura && !$cotizacionEnEdicion)
                    <form method="POST" action="{{ route('ventas.cotizacion.limpiar') }}" class="d-inline me-auto"
                        onsubmit="return confirm('¿Limpiar el borrador de cotización?');">
                        @csrf
                        <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                        <button type="submit" class="btn btn-outline-danger">Limpiar borrador</button>
                    </form>
                @endif
                @if ($cotizacionId > 0)
                    <a href="{{ route('ventas.cotizacion.pdf', ['id' => $cotizacionId, 'vendedor_id' => $vendedorId]) }}"
                        class="btn btn-outline-danger me-auto"
                        target="_blank"
                        id="btn-descargar-cotizacion-pdf"
                        data-actualizar-estatus="{{ in_array($estatusCotizacion, ['BORRADOR', 'ACEPTADA'], true) ? '1' : '0' }}">
                        <i class="fa-solid fa-file-pdf"></i> Descargar formato
                    </a>
                @elseif (!$cotizacionSoloLectura)
                    <span class="me-auto"></span>
                @endif
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                @if ($puedeConvertirPedido)
                    @php
                        $msgConvertirModal = $cotizacionConvertirTieneFaltantes
                            ? '¿Convertir esta cotización a pedido? Hay productos sin existencia: irán al reporte de no existencias y NO se descontará nada del almacén.'
                            : '¿Convertir esta cotización a pedido? En esta conversión no se descuenta inventario del almacén.';
                    @endphp
                    <form method="POST" action="{{ route('ventas.cotizacion.convertir_pedido', $cotizacionId) }}" class="d-inline"
                        onsubmit="return confirm(@json($msgConvertirModal));">
                        @csrf
                        <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                        <button type="submit" class="btn btn-blue">
                            <i class="fa-solid fa-cart-arrow-down"></i> Convertir a pedido
                        </button>
                    </form>
                @endif
                @if (!$cotizacionSoloLectura)
                    <form method="POST" action="{{ route('ventas.cotizacion.guardar') }}" class="d-inline" id="form-guardar-cotizacion" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                        @if ($cotizacionEnEdicion)
                            <input type="hidden" name="cotizacion_id" value="{{ $cotizacionId }}">
                        @endif
                        <input type="hidden" name="cliente_id" value="{{ $enc['cliente_id'] ?? '' }}">
                        <input type="hidden" name="persona_atencion" value="{{ $enc['persona_atencion'] ?? '' }}">
                        <input type="hidden" name="fecha_vencimiento" value="{{ $enc['fecha_vencimiento'] ?? '' }}">
                        <input type="hidden" name="observaciones" value="{{ $enc['observaciones'] ?? '' }}">
                        <input type="hidden" name="tiempo_entrega" value="{{ $enc['tiempo_entrega'] ?? '' }}">
                        <input type="hidden" name="moneda_id" value="{{ $enc['moneda_id'] ?? '' }}">
                        <input type="hidden" name="condicion_pago_id" value="{{ $enc['condicion_pago_id'] ?? '' }}">
                        <input type="hidden" name="tipo_flete_id" value="{{ $enc['tipo_flete_id'] ?? '' }}">
                        <input type="hidden" name="importe_flete" value="{{ $enc['importe_flete'] ?? 0 }}">
                        <input type="hidden" name="flete_en_precios" value="{{ !empty($enc['flete_en_precios']) ? 1 : 0 }}">
                        <input type="hidden" name="iva_en_precios" value="{{ !empty($enc['iva_en_precios']) ? 1 : 0 }}">
                        <input type="hidden" name="tipo_iva_id" value="{{ $enc['tipo_iva_id'] ?? '' }}">
                        <input type="hidden" name="descuento" value="{{ $enc['descuento'] ?? 0 }}">
                        <input type="hidden" name="estatus" value="{{ $enc['estatus'] ?? 'BORRADOR' }}">
                        <button type="submit" class="btn btn-blue" id="btn-guardar-cotizacion"
                            {{ empty($enc['cliente_id']) && empty(old('cliente_id')) ? 'disabled' : '' }}>
                            <i class="fa-solid fa-floppy-disk"></i>
                            {{ $cotizacionEnEdicion ? 'Actualizar cotización' : 'Guardar cotización' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@if (!empty($pedidoSeleccionado))
    @php
        $pedidoProduccionCanceladaModal = !empty($pedidoSeleccionado->ordenProduccion)
            && $pedidoSeleccionado->ordenProduccion->estatus === \App\Models\OrdenProduccion::ESTATUS_CANCELADA;
        $pedidoSoloLecturaModal = in_array($pedidoSeleccionado->estatus, ['EN_PRODUCCION', 'PENDIENTE_OC', 'LISTO_PARA_CARGA', 'CARGA_AVISADA', 'DESPACHADO'], true)
            && !$pedidoProduccionCanceladaModal;
        $facturasPedido = $facturasPedido ?? collect();
        $facturaPedido = $facturaPedido ?? $facturasPedido->first();
        $pedidoYaTimbrado = $facturasPedido->isNotEmpty();
        $puedeFacturarPedido = $pedidoSeleccionado->estatus !== 'CANCELADO'
            && !$pedidoYaTimbrado
            && $pedidoSeleccionado->detalles->isNotEmpty();
    @endphp
    <div class="modal fade" id="modalDetallePedido" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-secondary">
                        <i class="fa-solid fa-clipboard-list me-2"></i>
                        Pedido {{ $pedidoSeleccionado->folio }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0 modern-form">
                    @if ($pedidoSoloLecturaModal)
                        <div class="alert alert-secondary border-0 rounded-3 py-2 mb-2 fs-8">
                            <i class="fa-solid fa-lock me-1"></i>
                            Este pedido está en estatus <strong>{{ $pedidoSeleccionado->estatus }}</strong> y ya no puede modificarse.
                        </div>
                    @endif
                    @include('Ventas.partials.pedido_detalle')

                    <div class="border rounded-3 p-3 mt-3">
                        <h6 class="text-secondary mb-3">
                            <i class="fa-solid fa-file-invoice me-2"></i>Facturas CFDI del pedido
                        </h6>
                        @if ($puedeFacturarPedido)
                            <div class="border rounded-3 p-2 mb-3 bg-light">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="chk-carta-porte-pedido"
                                        value="1">
                                    <label class="form-check-label fw-semibold" for="chk-carta-porte-pedido">
                                        <i class="fa-solid fa-truck-fast me-1 text-secondary"></i>
                                        Este pedido requiere Carta Porte
                                    </label>
                                </div>
                                <div class="fs-8 text-muted ms-4">
                                    Al marcarlo, el botón de facturación cambia a <strong>Facturar con carta porte</strong>.
                                </div>
                            </div>
                        @endif
                        @if ($facturasPedido->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Folio</th>
                                            <th>UUID</th>
                                            <th>Fecha</th>
                                            <th>RFC receptor</th>
                                            <th>Total</th>
                                            <th>Estatus</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($facturasPedido as $factura)
                                            @php
                                                $facturaVigente = ($factura->cancelada ?? 'A') === 'A';
                                            @endphp
                                            <tr>
                                                <td class="text-nowrap">{{ $factura->folio ?? '—' }}</td>
                                                <td class="small text-break" style="max-width: 220px;">{{ $factura->Uuid }}</td>
                                                <td class="text-nowrap">
                                                    @if (!empty($factura->date))
                                                        {{ \Carbon\Carbon::parse($factura->date)->format('d/m/Y H:i') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="text-nowrap">{{ $factura->reciver_rfc ?? '—' }}</td>
                                                <td class="text-nowrap">${{ number_format((float) ($factura->total ?? 0), 2) }}</td>
                                                <td>
                                                    @if ($facturaVigente)
                                                        <span class="badge bg-success rounded-pill">Vigente</span>
                                                    @else
                                                        <span class="badge bg-danger rounded-pill">Cancelada</span>
                                                    @endif
                                                </td>
                                                <td class="text-center text-nowrap">
                                                    <a href="{{ route('verfacturaproductos', ['folio' => $factura->folio, 'uuid' => $factura->Uuid]) }}"
                                                        class="btn btn-outline-primary btn-sm"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        title="Ver factura timbrada">
                                                        <i class="fa-solid fa-eye me-1"></i> Ver factura
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted fs-8 mb-0">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Este pedido aún no tiene facturas CFDI timbradas.
                            </p>
                        @endif
                    </div>
                </div>
                <div class="modal-footer border-0 flex-wrap gap-2">
                    <div class="me-auto d-flex flex-wrap align-items-center gap-2">
                        @if ($pedidoYaTimbrado)
                            <span class="badge bg-success fs-7 py-2 px-3 rounded-pill">
                                <i class="fa-solid fa-circle-check me-1"></i>
                                {{ $facturasPedido->count() }} factura(s) timbrada(s)
                            </span>
                        @elseif ($puedeFacturarPedido)
                            <a href="{{ route('facturacion', ['id' => $pedidoSeleccionado->id, 'servicio' => 'pedido']) }}"
                                id="btn-facturar-pedido"
                                class="btn btn-success"
                                data-url-base="{{ route('facturacion', ['id' => $pedidoSeleccionado->id, 'servicio' => 'pedido']) }}"
                                data-url-carta-porte="{{ route('facturacion.pedido.carta_porte', $pedidoSeleccionado->id) }}"
                                title="Revisar y timbrar CFDI del pedido">
                                <i class="fa-solid fa-file-invoice-dollar me-1"></i>
                                <span class="js-facturar-label">Facturar</span>
                            </a>
                        @elseif ($pedidoSeleccionado->estatus === 'CANCELADO')
                            <small class="text-muted">Pedido cancelado: no se puede facturar.</small>
                        @elseif ($pedidoSeleccionado->detalles->isEmpty())
                            <small class="text-muted">Sin partidas para facturar.</small>
                        @endif
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    @if (!$pedidoSoloLecturaModal)
                        <button type="submit" form="form-actualizar-pedido" class="btn btn-blue">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar pedido
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Alta rápida de tipo de flete --}}
<div class="modal fade" id="modalNuevoTipoFlete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-secondary mb-0">
                    <i class="fa-solid fa-truck me-1"></i> Nuevo tipo de flete
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-nuevo-tipo-flete" class="modern-form">
                @csrf
                <div class="modal-body pt-2">
                    <div class="mb-2">
                        <label class="form-label fs-8" for="nuevo-flete-nombre">Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="nuevo-flete-nombre"
                            name="nombre" maxlength="120" required placeholder="Ej. Flete consolidado">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fs-8" for="nuevo-flete-codigo">Código <span class="text-muted">(opcional)</span></label>
                        <input type="text" class="form-control form-control-sm" id="nuevo-flete-codigo"
                            name="codigo" maxlength="30" placeholder="Se genera solo si lo dejas vacío">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fs-8" for="nuevo-flete-descripcion">Descripción</label>
                        <input type="text" class="form-control form-control-sm" id="nuevo-flete-descripcion"
                            name="descripcion" maxlength="255" placeholder="Opcional">
                    </div>
                    <div class="text-danger fs-9 mt-2 d-none" id="nuevo-flete-error"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-blue btn-sm" id="btn-guardar-tipo-flete">
                        <i class="fa-solid fa-plus"></i> Agregar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Alta rápida de tipo de IVA --}}
<div class="modal fade" id="modalNuevoTipoIva" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-secondary mb-0">
                    <i class="fa-solid fa-percent me-1"></i> Nuevo tipo de IVA
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-nuevo-tipo-iva" class="modern-form">
                @csrf
                <div class="modal-body pt-2">
                    <div class="mb-2">
                        <label class="form-label fs-8" for="nuevo-iva-nombre">Nombre</label>
                        <input type="text" class="form-control form-control-sm" id="nuevo-iva-nombre"
                            name="nombre" maxlength="120" required placeholder="Ej. IVA frontera 8%">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fs-8" for="nuevo-iva-porcentaje">Porcentaje</label>
                        <input type="number" class="form-control form-control-sm" id="nuevo-iva-porcentaje"
                            name="porcentaje" min="0" max="100" step="0.01" required placeholder="8">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fs-8" for="nuevo-iva-codigo">Código <span class="text-muted">(opcional)</span></label>
                        <input type="text" class="form-control form-control-sm" id="nuevo-iva-codigo"
                            name="codigo" maxlength="30" placeholder="Se genera solo si lo dejas vacío">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fs-8" for="nuevo-iva-descripcion">Descripción</label>
                        <input type="text" class="form-control form-control-sm" id="nuevo-iva-descripcion"
                            name="descripcion" maxlength="255" placeholder="Opcional">
                    </div>
                    <div class="text-danger fs-9 mt-2 d-none" id="nuevo-iva-error"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-blue btn-sm" id="btn-guardar-tipo-iva">
                        <i class="fa-solid fa-plus"></i> Agregar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/cotizacion-venta.js') }}"></script>
<script src="{{ asset('js/pedido-venta.js') }}"></script>
@if (!empty($abrirModalCotizacion))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modalNuevaCotizacion');
    if (modal) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
});
</script>
@endif
@if (!empty($abrirModalNuevoPedido))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modalNuevoPedido');
    if (modal) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
});
</script>
@endif
@if (!empty($abrirModalPedido) && !empty($pedidoSeleccionado))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('modalDetallePedido');
    if (modal) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }

    var chkCartaPorte = document.getElementById('chk-carta-porte-pedido');
    var btnFacturar = document.getElementById('btn-facturar-pedido');
    if (chkCartaPorte && btnFacturar) {
        var sincronizarFacturarCartaPorte = function () {
            var base = btnFacturar.getAttribute('data-url-base') || btnFacturar.href;
            var urlCartaPorte = btnFacturar.getAttribute('data-url-carta-porte') || (base + '/carta-porte');
            var label = btnFacturar.querySelector('.js-facturar-label');
            if (chkCartaPorte.checked) {
                btnFacturar.href = urlCartaPorte;
                btnFacturar.title = 'Revisar y timbrar CFDI con Carta Porte 3.1';
                if (label) {
                    label.textContent = 'Facturar con carta porte';
                }
            } else {
                btnFacturar.href = base;
                btnFacturar.title = 'Revisar y timbrar CFDI del pedido';
                if (label) {
                    label.textContent = 'Facturar';
                }
            }
        };
        chkCartaPorte.addEventListener('change', sincronizarFacturarCartaPorte);
        sincronizarFacturarCartaPorte();
    }
});
</script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    var formGuardar = document.getElementById('form-guardar-cotizacion');
    var panelEncabezado = document.getElementById('cotizacion-encabezado');
    var btnGuardarCotizacion = document.getElementById('btn-guardar-cotizacion');
    var tbodyLineas = document.getElementById('cotizacion-lineas-tbody');

    function cotizacionTieneLineas() {
        if (!tbodyLineas) return false;
        return tbodyLineas.querySelectorAll('tr.cotizacion-linea').length > 0;
    }

    function cotizacionTienePreciosValidos() {
        if (!tbodyLineas) return true;
        var lineas = tbodyLineas.querySelectorAll('tr.cotizacion-linea');
        for (var i = 0; i < lineas.length; i++) {
            var precioInput = lineas[i].querySelector('input[name="precio_unitario"]');
            var precio = precioInput
                ? parseFloat(precioInput.value || '0')
                : parseFloat(lineas[i].getAttribute('data-precio-unitario') || '0');
            if (isNaN(precio) || precio <= 0) {
                return false;
            }
        }
        return true;
    }

    function syncCotizacionGuardar() {
        if (!formGuardar || !panelEncabezado) return;

        ['cliente_id', 'fecha_vencimiento', 'observaciones', 'tiempo_entrega', 'persona_atencion', 'moneda_id', 'condicion_pago_id', 'tipo_flete_id', 'tipo_iva_id', 'importe_flete', 'descuento', 'estatus'].forEach(function (nombre) {
            var campo = panelEncabezado.querySelector('[name="' + nombre + '"]');
            var hidden = formGuardar.querySelector('[name="' + nombre + '"]');
            if (campo && hidden) {
                hidden.value = campo.value;
            }
        });

        var checkFlete = panelEncabezado.querySelector('input[type="checkbox"][name="flete_en_precios"]');
        var hiddenFlete = formGuardar.querySelector('[name="flete_en_precios"]');
        if (hiddenFlete) {
            hiddenFlete.value = (checkFlete && checkFlete.checked) ? '1' : '0';
        }

        var checkIva = panelEncabezado.querySelector('input[type="checkbox"][name="iva_en_precios"]');
        var hiddenIva = formGuardar.querySelector('[name="iva_en_precios"]');
        if (hiddenIva) {
            hiddenIva.value = (checkIva && checkIva.checked) ? '1' : '0';
        }

        var ayudaFlete = panelEncabezado.querySelector('.js-flete-ayuda');
        if (ayudaFlete) {
            ayudaFlete.textContent = (checkFlete && checkFlete.checked)
                ? 'Se reparte en los productos; no aparece como concepto.'
                : 'Se muestra como concepto de flete en el formato.';
        }

        var ayudaIva = panelEncabezado.querySelector('.js-iva-ayuda');
        if (ayudaIva) {
            ayudaIva.textContent = (checkIva && checkIva.checked)
                ? 'El IVA va en cada precio; no aparece como concepto aparte.'
                : 'El IVA se muestra como concepto aparte en el formato.';
        }

        if (btnGuardarCotizacion) {
            var cliente = panelEncabezado.querySelector('[name="cliente_id"]');
            var tieneLineas = cotizacionTieneLineas();
            var preciosValidos = cotizacionTienePreciosValidos();
            var puedeGuardar = cliente && cliente.value && tieneLineas && preciosValidos;
            btnGuardarCotizacion.disabled = !puedeGuardar;
            if (!cliente || !cliente.value) {
                btnGuardarCotizacion.title = 'Seleccione cliente y agregue al menos un producto';
            } else if (!tieneLineas) {
                btnGuardarCotizacion.title = 'Agregue al menos un producto';
            } else if (!preciosValidos) {
                btnGuardarCotizacion.title = 'Todos los productos deben tener precio unitario mayor a cero';
            } else {
                btnGuardarCotizacion.title = '';
            }
        }
    }

    if (formGuardar && panelEncabezado) {
        panelEncabezado.addEventListener('change', syncCotizacionGuardar);
        panelEncabezado.addEventListener('input', syncCotizacionGuardar);
        formGuardar.addEventListener('submit', function (event) {
            syncCotizacionGuardar();
            if (!cotizacionTienePreciosValidos()) {
                event.preventDefault();
                alert('No se puede guardar la cotización: hay productos sin precio unitario. Registre el costo de venta en el catálogo.');
            }
        });
        syncCotizacionGuardar();
    }

    window.syncCotizacionGuardarEncabezado = syncCotizacionGuardar;

    var formGuardarPedido = document.getElementById('form-guardar-pedido');
    var panelPedidoEncabezado = document.getElementById('pedido-encabezado');
    var btnGuardarPedido = document.getElementById('btn-guardar-pedido');
    if (formGuardarPedido && panelPedidoEncabezado) {
        var syncPedidoGuardar = function () {
            ['cliente_id', 'cotizacion_id', 'fecha_pedido', 'hora_pedido', 'fecha_entrega', 'hora_entrega', 'observaciones', 'tiempo_entrega', 'persona_atencion', 'moneda_id', 'condicion_pago_id', 'tipo_flete_id', 'tipo_iva_id', 'importe_flete', 'descuento'].forEach(function (nombre) {
                var campo = panelPedidoEncabezado.querySelector('[name="' + nombre + '"]');
                var hidden = formGuardarPedido.querySelector('[name="' + nombre + '"]');
                if (campo && hidden) {
                    hidden.value = campo.value;
                }
            });
            var checkFletePed = panelPedidoEncabezado.querySelector('input[type="checkbox"][name="flete_en_precios"]');
            var hiddenFletePed = formGuardarPedido.querySelector('[name="flete_en_precios"]');
            if (hiddenFletePed) {
                hiddenFletePed.value = (checkFletePed && checkFletePed.checked) ? '1' : '0';
            }
            var checkIvaPed = panelPedidoEncabezado.querySelector('input[type="checkbox"][name="iva_en_precios"]');
            var hiddenIvaPed = formGuardarPedido.querySelector('[name="iva_en_precios"]');
            if (hiddenIvaPed) {
                hiddenIvaPed.value = (checkIvaPed && checkIvaPed.checked) ? '1' : '0';
            }
            if (btnGuardarPedido) {
                var cliente = panelPedidoEncabezado.querySelector('[name="cliente_id"]');
                btnGuardarPedido.disabled = !cliente || !cliente.value;
            }
        };
        panelPedidoEncabezado.addEventListener('change', syncPedidoGuardar);
        panelPedidoEncabezado.addEventListener('input', syncPedidoGuardar);
        formGuardarPedido.addEventListener('submit', syncPedidoGuardar);
        syncPedidoGuardar();
        window.syncPedidoGuardarEncabezado = syncPedidoGuardar;
    }

    function enlazarDatosDesdeCliente(panel, monedaSelectId, condicionSelectId, personaAtencionId) {
        if (!panel) return;
        var selectCliente = panel.querySelector('select[name="cliente_id"]');
        var selectMoneda = monedaSelectId ? panel.querySelector(monedaSelectId) : panel.querySelector('select[name="moneda_id"]');
        var selectCondicion = condicionSelectId ? panel.querySelector(condicionSelectId) : panel.querySelector('select[name="condicion_pago_id"]');
        var inputAtencion = personaAtencionId
            ? panel.querySelector(personaAtencionId)
            : panel.querySelector('input[name="persona_atencion"]');
        if (!selectCliente) return;

        function aplicarDesdeCliente() {
            var opt = selectCliente.options[selectCliente.selectedIndex];
            if (!opt || !opt.value) {
                return;
            }
            var monedaId = opt.getAttribute('data-moneda-id') || '';
            var condicionId = opt.getAttribute('data-condicion-pago-id') || '';
            var personaAtencion = opt.getAttribute('data-persona-atencion') || '';

            // Solo selecciona el valor del cliente; no borra las demás opciones del select.
            if (selectMoneda && monedaId) {
                selectMoneda.value = monedaId;
            }
            if (selectCondicion) {
                selectCondicion.value = condicionId || '';
            }
            if (inputAtencion) {
                inputAtencion.value = personaAtencion;
            }

            if (typeof window.syncCotizacionGuardarEncabezado === 'function' && panel.id === 'cotizacion-encabezado') {
                window.syncCotizacionGuardarEncabezado();
            }
            if (typeof window.syncPedidoGuardarEncabezado === 'function' && panel.id === 'pedido-encabezado') {
                window.syncPedidoGuardarEncabezado();
            }
        }

        selectCliente.addEventListener('change', aplicarDesdeCliente);
    }

    enlazarDatosDesdeCliente(panelEncabezado, '#cotizacion-moneda-id', '#cotizacion-condicion-pago-id', '#cotizacion-persona-atencion');
    enlazarDatosDesdeCliente(panelPedidoEncabezado, '#pedido-moneda-id', '#pedido-condicion-pago-id', '#pedido-persona-atencion');
    enlazarDatosDesdeCliente(document.getElementById('form-actualizar-pedido'), null, null, null);

    (function enlazarAltaTipoFlete() {
        var form = document.getElementById('form-nuevo-tipo-flete');
        var modalEl = document.getElementById('modalNuevoTipoFlete');
        var errEl = document.getElementById('nuevo-flete-error');
        var btn = document.getElementById('btn-guardar-tipo-flete');
        if (!form || !modalEl) return;

        var selectOrigen = null;
        var modalPadreIds = ['modalNuevaCotizacion', 'modalNuevoPedido', 'modalDetallePedido'];

        function mostrarError(msg) {
            if (!errEl) return;
            errEl.textContent = msg || 'No se pudo guardar.';
            errEl.classList.remove('d-none');
        }

        function limpiarError() {
            if (!errEl) return;
            errEl.textContent = '';
            errEl.classList.add('d-none');
        }

        function modalPadreVisible() {
            for (var i = 0; i < modalPadreIds.length; i++) {
                var el = document.getElementById(modalPadreIds[i]);
                if (el && el.classList.contains('show')) {
                    return el;
                }
            }
            return null;
        }

        function restaurarModalPadre() {
            var padre = modalPadreVisible();
            if (!padre) return;
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            if (!document.querySelector('.modal-backdrop')) {
                var bd = document.createElement('div');
                bd.className = 'modal-backdrop fade show';
                document.body.appendChild(bd);
            }
            // Reasegura que el modal de cotización/pedido siga encima y enfocado.
            padre.style.display = 'block';
            padre.classList.add('show');
            padre.removeAttribute('aria-hidden');
            padre.setAttribute('aria-modal', 'true');
        }

        function agregarOpcionASelects(tipo) {
            var idStr = String(tipo.id);
            document.querySelectorAll('select.js-select-tipo-flete').forEach(function (sel) {
                if (sel.querySelector('option[value="' + idStr + '"]')) {
                    return;
                }
                var opt = document.createElement('option');
                opt.value = idStr;
                opt.textContent = tipo.nombre;
                sel.appendChild(opt);
            });

            // Preferir el select desde el que se abrió el alta (p. ej. cotización).
            var destino = selectOrigen;
            if (!destino || destino.disabled) {
                var padre = modalPadreVisible();
                if (padre) {
                    destino = padre.querySelector('select.js-select-tipo-flete:not([disabled])');
                }
            }
            if (!destino) {
                destino = document.querySelector('select.js-select-tipo-flete:not([disabled])');
            }
            if (destino) {
                destino.value = idStr;
                destino.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (typeof window.syncCotizacionGuardarEncabezado === 'function') {
                window.syncCotizacionGuardarEncabezado();
            }
            if (typeof window.syncPedidoGuardarEncabezado === 'function') {
                window.syncPedidoGuardarEncabezado();
            }
        }

        // Abrir con JS (no data-bs-toggle) para no cerrar el modal padre.
        document.querySelectorAll('[data-bs-target="#modalNuevoTipoFlete"]').forEach(function (btnPlus) {
            btnPlus.removeAttribute('data-bs-toggle');
            btnPlus.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var grupo = btnPlus.closest('.input-group');
                selectOrigen = grupo ? grupo.querySelector('select.js-select-tipo-flete') : null;
                limpiarError();
                form.reset();
                bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, keyboard: true }).show();
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            limpiarError();
            if (btn) {
                btn.disabled = true;
            }

            var token = document.querySelector('meta[name="csrf-token"]');
            var body = new FormData(form);

            fetch('{{ route('ventas.tipos_flete.store') }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                },
                body: body
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, status: res.status, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data.success) {
                        var msg = result.data.message || 'No se pudo guardar.';
                        if (result.data.errors) {
                            var first = Object.values(result.data.errors)[0];
                            if (Array.isArray(first) && first[0]) {
                                msg = first[0];
                            }
                        }
                        mostrarError(msg);
                        return;
                    }
                    agregarOpcionASelects(result.data.tipo);
                    form.reset();
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                })
                .catch(function () {
                    mostrarError('Error de conexión al guardar el tipo de flete.');
                })
                .finally(function () {
                    if (btn) {
                        btn.disabled = false;
                    }
                });
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            limpiarError();
            form.reset();
            // Bootstrap quita body.modal-open al cerrar el hijo; restaurar cotización/pedido.
            restaurarModalPadre();
            selectOrigen = null;
        });
    })();

    (function enlazarAltaTipoIva() {
        var form = document.getElementById('form-nuevo-tipo-iva');
        var modalEl = document.getElementById('modalNuevoTipoIva');
        var errEl = document.getElementById('nuevo-iva-error');
        var btn = document.getElementById('btn-guardar-tipo-iva');
        if (!form || !modalEl) return;

        var selectOrigen = null;
        var modalPadreIds = ['modalNuevaCotizacion', 'modalNuevoPedido', 'modalDetallePedido'];

        function mostrarError(msg) {
            if (!errEl) return;
            errEl.textContent = msg || 'No se pudo guardar.';
            errEl.classList.remove('d-none');
        }

        function limpiarError() {
            if (!errEl) return;
            errEl.textContent = '';
            errEl.classList.add('d-none');
        }

        function modalPadreVisible() {
            for (var i = 0; i < modalPadreIds.length; i++) {
                var el = document.getElementById(modalPadreIds[i]);
                if (el && el.classList.contains('show')) {
                    return el;
                }
            }
            return null;
        }

        function restaurarModalPadre() {
            var padre = modalPadreVisible();
            if (!padre) return;
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            if (!document.querySelector('.modal-backdrop')) {
                var bd = document.createElement('div');
                bd.className = 'modal-backdrop fade show';
                document.body.appendChild(bd);
            }
            padre.style.display = 'block';
            padre.classList.add('show');
            padre.removeAttribute('aria-hidden');
            padre.setAttribute('aria-modal', 'true');
        }

        function agregarOpcionASelects(tipo) {
            var idStr = String(tipo.id);
            var etiqueta = tipo.etiqueta || tipo.nombre;
            document.querySelectorAll('select.js-select-tipo-iva').forEach(function (sel) {
                if (sel.querySelector('option[value="' + idStr + '"]')) {
                    return;
                }
                var opt = document.createElement('option');
                opt.value = idStr;
                opt.textContent = etiqueta;
                opt.setAttribute('data-porcentaje', tipo.porcentaje);
                sel.appendChild(opt);
            });

            var destino = selectOrigen;
            if (!destino || destino.disabled) {
                var padre = modalPadreVisible();
                if (padre) {
                    destino = padre.querySelector('select.js-select-tipo-iva:not([disabled])');
                }
            }
            if (!destino) {
                destino = document.querySelector('select.js-select-tipo-iva:not([disabled])');
            }
            if (destino) {
                destino.value = idStr;
                destino.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (typeof window.syncCotizacionGuardarEncabezado === 'function') {
                window.syncCotizacionGuardarEncabezado();
            }
            if (typeof window.syncPedidoGuardarEncabezado === 'function') {
                window.syncPedidoGuardarEncabezado();
            }
        }

        document.querySelectorAll('[data-bs-target="#modalNuevoTipoIva"]').forEach(function (btnPlus) {
            btnPlus.removeAttribute('data-bs-toggle');
            btnPlus.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var grupo = btnPlus.closest('.input-group');
                selectOrigen = grupo ? grupo.querySelector('select.js-select-tipo-iva') : null;
                limpiarError();
                form.reset();
                bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, keyboard: true }).show();
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            e.stopPropagation();
            limpiarError();
            if (btn) {
                btn.disabled = true;
            }

            var token = document.querySelector('meta[name="csrf-token"]');
            var body = new FormData(form);

            fetch('{{ route('ventas.tipos_iva.store') }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                },
                body: body
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, status: res.status, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data.success) {
                        var msg = result.data.message || 'No se pudo guardar.';
                        if (result.data.errors) {
                            var first = Object.values(result.data.errors)[0];
                            if (Array.isArray(first) && first[0]) {
                                msg = first[0];
                            }
                        }
                        mostrarError(msg);
                        return;
                    }
                    agregarOpcionASelects(result.data.tipo);
                    form.reset();
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                })
                .catch(function () {
                    mostrarError('Error de conexión al guardar el tipo de IVA.');
                })
                .finally(function () {
                    if (btn) {
                        btn.disabled = false;
                    }
                });
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            limpiarError();
            form.reset();
            restaurarModalPadre();
            selectOrigen = null;
        });
    })();

    var btnPdf = document.getElementById('btn-descargar-cotizacion-pdf');
    if (btnPdf && btnPdf.dataset.actualizarEstatus === '1') {
        btnPdf.addEventListener('click', function () {
            setTimeout(function () {
                window.location.href = '{{ route('ventas.pedidos', ['vendedor_id' => $vendedorId, 'abrir_cotizacion' => 1]) }}';
            }, 1200);
        });
    }
});
</script>
<script>
(function () {
    var panel = document.getElementById('panelCotizaciones');
    var btnToggle = document.getElementById('btnToggleCotizaciones');
    var labelExpandir = document.getElementById('labelExpandirCotizaciones');
    if (!panel || !btnToggle) return;
    function togglePanel() {
        panel.classList.toggle('is-collapsed');
        btnToggle.title = panel.classList.contains('is-collapsed') ? 'Mostrar panel' : 'Ocultar panel';
    }
    btnToggle.addEventListener('click', togglePanel);
    if (labelExpandir) labelExpandir.addEventListener('click', togglePanel);
})();
</script>
@endsection
