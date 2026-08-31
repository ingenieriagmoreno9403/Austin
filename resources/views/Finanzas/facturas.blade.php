@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .facturas-kpi {
        height: 100%;
        padding: 1.15rem 1.25rem;
        background: #fff;
        border: 1px solid rgba(17, 24, 39, 0.09);
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(17, 24, 39, 0.06);
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .facturas-kpi::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--kpi-accent, #111827);
    }
    .facturas-kpi:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 22px rgba(17, 24, 39, 0.1);
        border-color: rgba(17, 24, 39, 0.14);
    }
    .facturas-kpi.is-pendiente { --kpi-accent: #d97706; }
    .facturas-kpi.is-pagado { --kpi-accent: #16a34a; }
    .facturas-kpi.is-cobrando { --kpi-accent: #dc2626; }
    .facturas-kpi.is-total { --kpi-accent: #2563eb; }
    .facturas-kpi-label {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 0.35rem;
    }
    .facturas-kpi-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: #111827;
        margin: 0 0 0.25rem;
        line-height: 1.2;
    }
    .facturas-kpi-hint {
        font-size: 0.78rem;
        color: #9ca3af;
        margin: 0;
    }
    .facturas-kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--primary-soft-bg, #f3f4f6);
        color: var(--kpi-accent, #111827);
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .facturas-panel {
        background: #fff;
        border: 1px solid rgba(17, 24, 39, 0.09);
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(17, 24, 39, 0.06);
        padding: 1.25rem;
    }
    .facturas-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .facturas-filters form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem 0.65rem;
    }
    .facturas-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
    }
    .facturas-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .facturas-filters .form-control-sm,
    .facturas-filters .form-select-sm {
        min-height: 28px;
        height: 28px;
        font-size: 0.78rem;
        padding: 0.15rem 0.4rem;
        width: auto;
        min-width: 120px;
        max-width: 180px;
        background: #ffffff !important;
        border: 1px solid var(--table-border, #e5e7eb) !important;
        border-radius: 10px !important;
        color: var(--table-text, #111827) !important;
    }
    .facturas-filters .filter-field-wide .form-control-sm,
    .facturas-filters .filter-field-wide .form-select-sm {
        min-width: 150px;
        max-width: 220px;
    }
    .facturas-filters input[type="date"] {
        min-width: 128px;
        max-width: 145px;
    }
    .facturas-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    .facturas-table-card .td-actions {
        white-space: nowrap !important;
    }
    .facturas-table-card .td-actions .btn {
        display: inline-flex !important;
        margin: 0 2px !important;
    }
    .modal .modal-header.facturas-modal-header {
        background: linear-gradient(135deg, #030712 0%, #111827 58%, #4b5563 100%);
        color: #fff;
        border-bottom: 0;
        border-radius: 0;
    }
    .modal .modal-header.facturas-modal-header .btn-close {
        opacity: 0.85;
    }
    .modal .modal-content {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 12px 40px rgba(17, 24, 39, 0.18);
    }
    .facturas-info-card {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1rem 1.1rem;
    }
    .facturas-info-card p {
        margin-bottom: 0.35rem;
        font-size: 0.9rem;
    }
    .facturas-info-card p:last-child {
        margin-bottom: 0;
    }
    @media (max-width: 768px) {
        .facturas-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .facturas-filters .form-control-sm,
        .facturas-filters .form-select-sm,
        .facturas-filters input[type="date"] {
            flex: 1;
            max-width: none;
        }
    }
</style>

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Gestión de Facturas</h2>
                        <p class="text-muted mb-0">Cobranza, seguimiento de estados y facturación de servicios</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-baseColor" data-bs-toggle="modal" data-bs-target="#modalFactura">
                        <i class="fa-solid fa-plus"></i> Nueva Factura
                    </button>
                    <a href="{{ route('facturas.index') }}" class="btn btn-baseColor-light">
                        <i class="fa-solid fa-file-invoice"></i> CFDI en Tesorería
                    </a>
                    <a href="{{ route('finanzas') }}" class="btn btn-baseColor-light">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4 alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <strong>Errores de validación:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="facturas-kpi is-pendiente" onclick="filtrarPorEstado('Pendiente')" title="Filtrar pendientes">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="facturas-kpi-label">Pendientes</div>
                        <p class="facturas-kpi-value" id="totalPendientes">${{ number_format($totalPendientes, 2) }}</p>
                        <p class="facturas-kpi-hint">Por cobrar</p>
                    </div>
                    <div class="facturas-kpi-icon"><i class="fa-solid fa-clock"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="facturas-kpi is-pagado" onclick="filtrarPorEstado('Pagado')" title="Filtrar pagadas">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="facturas-kpi-label">Pagadas</div>
                        <p class="facturas-kpi-value" id="totalPagadas">${{ number_format($totalPagadas, 2) }}</p>
                        <p class="facturas-kpi-hint">Cobradas</p>
                    </div>
                    <div class="facturas-kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="facturas-kpi is-cobrando" onclick="filtrarPorEstado('Cobrando')" title="Filtrar en cobranza">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="facturas-kpi-label">Cobrando</div>
                        <p class="facturas-kpi-value" id="totalVencidas">${{ number_format($totalCobrando, 2) }}</p>
                        <p class="facturas-kpi-hint">En cobranza</p>
                    </div>
                    <div class="facturas-kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="facturas-kpi is-total" onclick="window.location.href='{{ route('finanzas.facturas') }}'" title="Ver todas">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="facturas-kpi-label">Total</div>
                        <p class="facturas-kpi-value" id="totalFacturas">${{ number_format($totalFacturas, 2) }}</p>
                        <p class="facturas-kpi-hint">Todas las facturas</p>
                    </div>
                    <div class="facturas-kpi-icon"><i class="fa-solid fa-file-invoice"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="facturas-panel">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h5 class="mb-0 fw-bold text-marino">Listado de facturas</h5>
                <p class="text-muted small mb-0">{{ $facturas->count() }} resultado(s)</p>
            </div>
            @if(request()->hasAny(['cliente', 'estado', 'nombre_servicio', 'fecha_desde', 'fecha_hasta']))
                <span class="badge bg-secondary">Filtros aplicados</span>
            @endif
        </div>

        <div class="filters facturas-filters">
            <form method="GET" action="{{ route('finanzas.facturas') }}">
                <div class="filter-field filter-field-wide">
                    <label class="form-label" for="filtroCliente">Cliente</label>
                    <select class="form-select form-select-sm" id="filtroCliente" name="cliente">
                        <option value="">Todos</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente }}" @selected(request('cliente') == $cliente)>{{ $cliente }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-field">
                    <label class="form-label" for="filtroEstado">Estado</label>
                    <select class="form-select form-select-sm" id="filtroEstado" name="estado">
                        <option value="">Todos</option>
                        <option value="Pendiente" @selected(request('estado') == 'Pendiente')>Pendiente</option>
                        <option value="Pagado" @selected(request('estado') == 'Pagado')>Pagado</option>
                        <option value="Cobrando" @selected(request('estado') == 'Cobrando')>Cobrando</option>
                    </select>
                </div>
                <div class="filter-field filter-field-wide">
                    <label class="form-label" for="filtroNombreServicio">Servicio</label>
                    <input type="text" class="form-control form-control-sm" id="filtroNombreServicio" name="nombre_servicio"
                           value="{{ request('nombre_servicio') }}" placeholder="Nombre del servicio">
                </div>
                <div class="filter-field">
                    <label class="form-label" for="filtroFechaDesde">Desde</label>
                    <input type="date" class="form-control form-control-sm" id="filtroFechaDesde" name="fecha_desde" value="{{ request('fecha_desde') }}">
                </div>
                <div class="filter-field">
                    <label class="form-label" for="filtroFechaHasta">Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="filtroFechaHasta" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                </div>
                <button type="submit" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                @if(request()->hasAny(['cliente', 'estado', 'nombre_servicio', 'fecha_desde', 'fecha_hasta']))
                    <a href="{{ route('finanzas.facturas') }}" class="btn btn-outline-secondary btn-sm flex-shrink-0" title="Limpiar filtros">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive facturas-table-card">
            <table class="table table-striped table-hover display modern-table w-100 align-middle" id="tablaFacturas">
                <thead>
                    <tr>
                        <th class="text-center">Acciones</th>
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>RFC</th>
                        <th>Fecha Emisión</th>
                        <th>Nombre</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Total</th>
                        <th>Método</th>
                    </tr>
                </thead>
                <tbody id="tbodyFacturas">
                    @forelse($facturas as $factura)
                        @php
                            $estadoBadge = match($factura->estado) {
                                'Pendiente' => 'warning',
                                'Pagado' => 'success',
                                'Cobrando' => 'info',
                                'Activo' => 'secondary',
                                default => 'secondary',
                            };
                            $cancelada = $factura->cancelada == 'C';
                        @endphp
                        <tr data-factura-id="{{ $factura->id }}"
                            data-folio="{{ $factura->folio }}"
                            data-cliente="{{ $factura->reciver_nombre }}"
                            data-rfc="{{ $factura->reciver_rfc }}"
                            data-fecha="{{ $factura->date }}"
                            data-total="${{ number_format($factura->total, 2) }}"
                            data-estado="{{ $cancelada ? 'Cancelada' : $factura->estado }}"
                            data-cancelada="{{ $factura->cancelada ?? 'A' }}">
                            <td class="td-actions text-center text-nowrap">
                                <button type="button" class="btn btn-sm btn-warning" onclick="editarFactura({{ $factura->id }})" title="Editar estado">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                @if(!empty($factura->Uuid))
                                    <a href="{{ route('facturas.ver', $factura->id) }}"
                                       class="btn btn-sm btn-primary"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       title="Ver CFDI timbrado">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </a>
                                @endif
                                @if(($factura->cancelada ?? 'A') == 'A')
                                    <button type="button" class="btn btn-sm btn-danger" onclick="abrirModalCancelacion({{ $factura->id }})" title="Cancelar factura">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                @endif
                            </td>
                            <td class="text-nowrap"><strong>{{ $factura->folio }}</strong></td>
                            <td>{{ $factura->reciver_nombre }}</td>
                            <td>
                                @if($cancelada)
                                    <span class="badge bg-danger">Cancelada</span>
                                @else
                                    <span class="badge bg-{{ $estadoBadge }}">{{ $factura->estado }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $factura->reciver_rfc }}</td>
                            <td class="text-nowrap">{{ $factura->date }}</td>
                            <td>
                                @if($factura->nombre_servicio)
                                    {{ $factura->nombre_servicio }}
                                @else
                                    <span class="text-muted">{{ $factura->tipo_serv ?: 'Sin servicio' }}</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">${{ number_format($factura->subtotal, 2) }}</td>
                            <td class="text-end text-nowrap fw-semibold">${{ number_format($factura->total, 2) }}</td>
                            <td>
                                @if(($factura->metodo_pago ?? '') === 'PPD')
                                    <span class="badge bg-info text-dark">PPD</span>
                                @elseif(($factura->metodo_pago ?? '') === 'PUE')
                                    <span class="badge bg-primary">PUE</span>
                                @else
                                    <span class="text-muted">{{ $factura->metodo_pago ?? '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No hay facturas para mostrar</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Nueva Factura --}}
<div class="modal fade" id="modalFactura" tabindex="-1" aria-labelledby="modalFacturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header facturas-modal-header">
                <h5 class="modal-title" id="modalFacturaLabel">
                    <i class="fa-solid fa-file-invoice-dollar me-2"></i>
                    Nueva Factura
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formFactura" class="modern-form" action="{{ route('finanzas.facturar-servicio') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="servicio_id" class="form-label">Servicio a facturar <span class="text-danger">*</span></label>
                        <select class="form-control" id="servicio_id" name="servicio_id" required>
                            <option value="">Seleccionar servicio</option>
                            @foreach($servicios as $servicio)
                                <option value="{{ $servicio->id }}">
                                    {{ $servicio->folio }} - {{ $servicio->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-baseColor">
                        <i class="fa-solid fa-file-invoice-dollar me-1"></i> Facturar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar Estado --}}
<div class="modal fade" id="modalEditarFactura" tabindex="-1" aria-labelledby="modalEditarFacturaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header facturas-modal-header">
                <h5 class="modal-title" id="modalEditarFacturaLabel">
                    <i class="fa-solid fa-pen me-2"></i>
                    Editar estado de factura
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarFactura" class="modern-form" action="{{ route('finanzas.actualizar-estado-factura') }}" method="POST">
                @csrf
                <input type="hidden" id="factura_id_editar" name="factura_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Información de la factura</label>
                        <div class="facturas-info-card">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Folio:</strong> <span id="folio_editar"></span></p>
                                    <p><strong>Cliente:</strong> <span id="cliente_editar"></span></p>
                                    <p><strong>RFC:</strong> <span id="rfc_editar"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Fecha:</strong> <span id="fecha_editar"></span></p>
                                    <p><strong>Total:</strong> <span id="total_editar"></span></p>
                                    <p><strong>Estado actual:</strong> <span id="estado_actual_editar"></span></p>
                                    <p><strong>Cancelación:</strong> <span id="cancelada_actual_editar"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="nuevo_estado" class="form-label">Nuevo estado <span class="text-danger">*</span></label>
                        <select class="form-control" id="nuevo_estado" name="nuevo_estado" required>
                            <option value="">Seleccionar nuevo estado</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Pagado">Pagado</option>
                            <option value="Cobrando">Cobrando</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label for="nuevo_cancelada" class="form-label">Estado de cancelación <span class="text-danger">*</span></label>
                        <select class="form-control" id="nuevo_cancelada" name="nuevo_cancelada" required>
                            <option value="">Seleccionar estado</option>
                            <option value="A">Activa</option>
                            <option value="C">Cancelada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-baseColor">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Actualizar estado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Cancelar Factura --}}
<div class="modal fade" id="modalCancelarFactura" tabindex="-1" aria-labelledby="modalCancelarFacturaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header facturas-modal-header">
                <h5 class="modal-title" id="modalCancelarFacturaLabel">
                    <i class="fa-solid fa-ban me-2"></i>
                    Cancelar factura
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCancelarFactura" class="modern-form" action="{{ route('finanzas.actualizar-estado-factura') }}" method="POST" onsubmit="return validarFormularioCancelacion()">
                @csrf
                <input type="hidden" id="factura_id_cancelar" name="factura_id">
                <input type="hidden" id="nuevo_estado_cancelar" name="nuevo_estado" value="Pendiente">
                <input type="hidden" id="nuevo_cancelada_cancelar" name="nuevo_cancelada" value="C">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Información de la factura</label>
                        <div class="facturas-info-card">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Folio:</strong> <span id="folio_cancelar"></span></p>
                                    <p><strong>Cliente:</strong> <span id="cliente_cancelar"></span></p>
                                    <p><strong>RFC:</strong> <span id="rfc_cancelar"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Fecha:</strong> <span id="fecha_cancelar"></span></p>
                                    <p><strong>Total:</strong> <span id="total_cancelar"></span></p>
                                    <p><strong>Estado actual:</strong> <span id="estado_actual_cancelar"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label for="motivo_cancelacion" class="form-label">Motivo de cancelación <span class="text-danger">*</span></label>
                        <select class="form-control" id="motivo_cancelacion" name="comentario_cancelacion" required>
                            <option value="">Seleccionar motivo</option>
                            <option value="01 - Comprobante emitido con errores con relación">01 - Comprobante emitido con errores con relación</option>
                            <option value="02 - Comprobante emitido con errores sin relación">02 - Comprobante emitido con errores sin relación</option>
                            <option value="03 - No se llevó a cabo la operación">03 - No se llevó a cabo la operación</option>
                            <option value="04 - Operación nominativa relacionada con una factura global">04 - Operación nominativa relacionada con una factura global</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cerrar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-ban me-1"></i> Confirmar cancelación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function filtrarPorEstado(estado) {
    window.location.href = '{{ route("finanzas.facturas") }}?estado=' + encodeURIComponent(estado);
}

function getFacturaRow(id) {
    return document.querySelector('tr[data-factura-id="' + id + '"]');
}

function editarFactura(id) {
    const row = getFacturaRow(id);
    if (!row) {
        alert('Error: No se encontró la factura');
        return;
    }

    const cancelada = row.dataset.cancelada || 'A';
    document.getElementById('factura_id_editar').value = id;
    document.getElementById('folio_editar').textContent = row.dataset.folio || '';
    document.getElementById('cliente_editar').textContent = row.dataset.cliente || '';
    document.getElementById('rfc_editar').textContent = row.dataset.rfc || '';
    document.getElementById('fecha_editar').textContent = row.dataset.fecha || '';
    document.getElementById('total_editar').textContent = row.dataset.total || '';
    document.getElementById('estado_actual_editar').textContent = row.dataset.estado || '';
    document.getElementById('cancelada_actual_editar').textContent = cancelada === 'A' ? 'Activa' : 'Cancelada';
    document.getElementById('nuevo_estado').value = '';
    document.getElementById('nuevo_cancelada').value = cancelada;

    new bootstrap.Modal(document.getElementById('modalEditarFactura')).show();
}

function abrirModalCancelacion(id) {
    const row = getFacturaRow(id);
    if (!row) {
        alert('Error: No se encontró la factura');
        return;
    }

    document.getElementById('factura_id_cancelar').value = id;
    document.getElementById('folio_cancelar').textContent = row.dataset.folio || '';
    document.getElementById('cliente_cancelar').textContent = row.dataset.cliente || '';
    document.getElementById('rfc_cancelar').textContent = row.dataset.rfc || '';
    document.getElementById('fecha_cancelar').textContent = row.dataset.fecha || '';
    document.getElementById('total_cancelar').textContent = row.dataset.total || '';
    document.getElementById('estado_actual_cancelar').textContent = row.dataset.estado || '';
    document.getElementById('motivo_cancelacion').value = '';

    new bootstrap.Modal(document.getElementById('modalCancelarFactura')).show();
}

function validarFormularioCancelacion() {
    const motivo = document.getElementById('motivo_cancelacion').value;
    if (!motivo) {
        alert('Por favor seleccione un motivo de cancelación');
        return false;
    }
    return confirm('¿Está seguro de que desea cancelar esta factura? Esta acción no se puede deshacer.');
}

document.addEventListener('DOMContentLoaded', function () {
    const formEditarFactura = document.getElementById('formEditarFactura');
    if (!formEditarFactura) return;

    formEditarFactura.addEventListener('submit', function (e) {
        e.preventDefault();

        const nuevoEstado = document.getElementById('nuevo_estado').value;
        const nuevoCancelada = document.getElementById('nuevo_cancelada').value;
        const estadoActual = document.getElementById('estado_actual_editar').textContent;

        if (!nuevoEstado) {
            alert('Por favor seleccione un nuevo estado');
            return;
        }
        if (!nuevoCancelada) {
            alert('Por favor seleccione el estado de cancelación');
            return;
        }
        if (nuevoEstado === estadoActual && nuevoCancelada === (estadoActual === 'Cancelada' ? 'C' : 'A')) {
            alert('Debe modificar al menos el estado o la cancelación');
            return;
        }

        const mensaje = '¿Está seguro de cambiar el estado a "' + nuevoEstado + '" y la cancelación a "' +
            (nuevoCancelada === 'A' ? 'Activa' : 'Cancelada') + '"?';

        if (confirm(mensaje)) {
            this.submit();
        }
    });
});
</script>
@endpush
