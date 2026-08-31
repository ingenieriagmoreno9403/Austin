@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .cuentas-kpi {
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
    .cuentas-kpi::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--kpi-accent, #111827);
    }
    .cuentas-kpi:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 22px rgba(17, 24, 39, 0.1);
        border-color: rgba(17, 24, 39, 0.14);
    }
    .cuentas-kpi.is-ingresos { --kpi-accent: #16a34a; }
    .cuentas-kpi.is-egresos { --kpi-accent: #dc2626; }
    .cuentas-kpi.is-cobrar { --kpi-accent: #2563eb; }
    .cuentas-kpi.is-pagar { --kpi-accent: #d97706; }
    .cuentas-kpi.is-facturas { --kpi-accent: #111827; }
    .cuentas-kpi-label {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 0.35rem;
    }
    .cuentas-kpi-value {
        font-size: 1.45rem;
        font-weight: 800;
        color: #111827;
        margin: 0 0 0.25rem;
        line-height: 1.2;
    }
    .cuentas-kpi-hint {
        font-size: 0.78rem;
        color: #9ca3af;
        margin: 0;
    }
    .cuentas-kpi-icon {
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
    .cuentas-panel {
        background: #fff;
        border: 1px solid rgba(17, 24, 39, 0.09);
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(17, 24, 39, 0.06);
        padding: 1.25rem;
    }
    .cuentas-tabs {
        border-bottom: 1px solid #e5e7eb;
        gap: 0.25rem;
        flex-wrap: wrap;
    }
    .cuentas-tabs .nav-link {
        border: 0;
        border-radius: 10px;
        color: #6b7280;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 0.55rem 0.9rem;
        margin-bottom: 0.5rem;
        background: transparent;
    }
    .cuentas-tabs .nav-link:hover {
        background: #f3f4f6;
        color: #111827;
    }
    .cuentas-tabs .nav-link.active {
        background: #111827;
        color: #fff;
    }
    .cuentas-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin: 1rem 0 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .cuentas-filters form,
    .cuentas-filters .filters-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem 0.65rem;
    }
    .cuentas-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
    }
    .cuentas-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .cuentas-filters .form-control-sm,
    .cuentas-filters .form-select-sm {
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
    .cuentas-filters input[type="date"] {
        min-width: 128px;
        max-width: 145px;
    }
    .cuentas-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    @media (max-width: 768px) {
        .cuentas-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .cuentas-filters .form-control-sm,
        .cuentas-filters .form-select-sm,
        .cuentas-filters input[type="date"] {
            flex: 1;
            max-width: none;
        }
    }
    .modal .modal-header.cuentas-modal-header {
        background: linear-gradient(135deg, #030712 0%, #111827 58%, #4b5563 100%);
        color: #fff;
        border-bottom: 0;
        border-radius: 0;
    }
    .modal .modal-header.cuentas-modal-header .btn-close {
        opacity: 0.85;
    }
    .modal .modal-content {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 12px 40px rgba(17, 24, 39, 0.18);
    }
</style>

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Control de Cuentas</h2>
                        <p class="text-muted mb-0">Resumen de ingresos, egresos, deudas y facturación</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="{{ route('finanzas.facturas') }}" class="btn btn-baseColor fs-7 mb-2">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Facturación
                    </a>
                    <a href="/Panel" class="btn btn-baseColor-light fs-7 mb-2">
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
        <div class="col-xl col-md-6">
            <div class="cuentas-kpi is-ingresos" data-bs-toggle="modal" data-bs-target="#modalIngreso" title="Registrar ingreso">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="cuentas-kpi-label">Ingresos</div>
                        <p class="cuentas-kpi-value" id="totalIngresos">${{ number_format($saldoActual, 2) }}</p>
                        <p class="cuentas-kpi-hint">Saldo actual de cuenta</p>
                    </div>
                    <div class="cuentas-kpi-icon"><i class="fa-solid fa-arrow-up"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="cuentas-kpi is-egresos" data-bs-toggle="modal" data-bs-target="#modalEgreso" title="Registrar egreso">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="cuentas-kpi-label">Egresos</div>
                        <p class="cuentas-kpi-value" id="totalEgresos">${{ number_format($totalEgresos, 2) }}</p>
                        <p class="cuentas-kpi-hint">Total de egresos</p>
                    </div>
                    <div class="cuentas-kpi-icon"><i class="fa-solid fa-arrow-down"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="cuentas-kpi is-cobrar" data-bs-toggle="modal" data-bs-target="#modalDeudaCobrar" title="Registrar deuda por cobrar">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="cuentas-kpi-label">Por cobrar</div>
                        <p class="cuentas-kpi-value" id="totalDeudasCobrar">${{ number_format($totalDeudasCobrar, 2) }}</p>
                        <p class="cuentas-kpi-hint">Pendientes de cobro</p>
                    </div>
                    <div class="cuentas-kpi-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="cuentas-kpi is-pagar" data-bs-toggle="modal" data-bs-target="#modalDeudaPagar" title="Registrar deuda por pagar">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="cuentas-kpi-label">Por pagar</div>
                        <p class="cuentas-kpi-value" id="totalDeudasPagar">${{ number_format($totalDeudasPagar, 2) }}</p>
                        <p class="cuentas-kpi-hint">Pendientes de pago</p>
                    </div>
                    <div class="cuentas-kpi-icon"><i class="fa-solid fa-credit-card"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="cuentas-kpi is-facturas" onclick="window.location.href='{{ route('finanzas.facturas') }}'" title="Ir a facturación">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="cuentas-kpi-label">Facturación</div>
                        <p class="cuentas-kpi-value" id="totalfacturas">${{ number_format($totalfacturas, 2) }}</p>
                        <p class="cuentas-kpi-hint">Gestión de facturas</p>
                    </div>
                    <div class="cuentas-kpi-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="cuentas-panel">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <h5 class="mb-0 fw-bold text-marino">Registros financieros</h5>
                <p class="text-muted small mb-0">Consulta y filtra movimientos por tipo</p>
            </div>
        </div>

        <ul class="nav nav-tabs cuentas-tabs" id="finanzasTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="ingresos-tab" data-bs-toggle="tab" data-bs-target="#ingresos" type="button" role="tab" aria-controls="ingresos" aria-selected="true">
                    <i class="fa-solid fa-arrow-up me-1"></i> Ingresos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="egresos-tab" data-bs-toggle="tab" data-bs-target="#egresos" type="button" role="tab" aria-controls="egresos" aria-selected="false">
                    <i class="fa-solid fa-arrow-down me-1"></i> Egresos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="deudas-cobrar-tab" data-bs-toggle="tab" data-bs-target="#deudas-cobrar" type="button" role="tab" aria-controls="deudas-cobrar" aria-selected="false">
                    <i class="fa-solid fa-hand-holding-dollar me-1"></i> Por cobrar
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="deudas-pagar-tab" data-bs-toggle="tab" data-bs-target="#deudas-pagar" type="button" role="tab" aria-controls="deudas-pagar" aria-selected="false">
                    <i class="fa-solid fa-credit-card me-1"></i> Por pagar
                </button>
            </li>
        </ul>

        <div class="tab-content" id="finanzasTabsContent">
            <div class="tab-pane fade show active" id="ingresos" role="tabpanel" aria-labelledby="ingresos-tab">
                <div class="cuentas-filters">
                    <div class="filters-row">
                        <div class="filter-field">
                            <label for="fecha_desde_ingresos" class="form-label">Desde</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_desde_ingresos">
                        </div>
                        <div class="filter-field">
                            <label for="fecha_hasta_ingresos" class="form-label">Hasta</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_hasta_ingresos">
                        </div>
                        <div class="filter-field">
                            <label for="categoria_filtro_ingresos" class="form-label">Categoría</label>
                            <select class="form-select form-select-sm" id="categoria_filtro_ingresos">
                                <option value="">Todas</option>
                                <option value="ventas">Ventas</option>
                                <option value="servicios">Servicios</option>
                                <option value="inversiones">Inversiones</option>
                                <option value="prestamos">Préstamos</option>
                                <option value="Facturación CFDI">Facturación CFDI</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-baseColor-light btn-sm" onclick="filtrarIngresos()">
                            <i class="fa-solid fa-filter"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosIngresos()" title="Limpiar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover display modern-table w-100 align-middle" id="tablaIngresos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th>Monto</th>
                                <th>Categoría</th>
                                <th>Método de Pago</th>
                                <th>Factura CFDI</th>
                                <th>Servicio</th>
                                <th>Descripción</th>
                                <th>Archivo</th>
                                <th>Registrado por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="egresos" role="tabpanel" aria-labelledby="egresos-tab">
                <div class="cuentas-filters">
                    <div class="filters-row">
                        <div class="filter-field">
                            <label for="fecha_desde_egresos" class="form-label">Desde</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_desde_egresos">
                        </div>
                        <div class="filter-field">
                            <label for="fecha_hasta_egresos" class="form-label">Hasta</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_hasta_egresos">
                        </div>
                        <div class="filter-field">
                            <label for="categoria_filtro_egresos" class="form-label">Categoría</label>
                            <select class="form-select form-select-sm" id="categoria_filtro_egresos">
                                <option value="">Todas</option>
                                <option value="gastos_operacion">Gastos de Operación</option>
                                <option value="gastos_administrativos">Gastos Administrativos</option>
                                <option value="gastos_ventas">Gastos de Ventas</option>
                                <option value="gastos_financieros">Gastos Financieros</option>
                                <option value="Facturación CFDI">Facturación CFDI</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-baseColor-light btn-sm" onclick="filtrarEgresos()">
                            <i class="fa-solid fa-filter"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosEgresos()" title="Limpiar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover display modern-table w-100 align-middle" id="tablaEgresos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th>Monto</th>
                                <th>Categoría</th>
                                <th>Método de Pago</th>
                                <th>Factura CFDI</th>
                                <th>Descripción</th>
                                <th>Archivo</th>
                                <th>Registrado por</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="deudas-cobrar" role="tabpanel" aria-labelledby="deudas-cobrar-tab">
                <div class="cuentas-filters">
                    <div class="filters-row">
                        <div class="filter-field">
                            <label for="fecha_desde_deudas_cobrar" class="form-label">Desde</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_desde_deudas_cobrar">
                        </div>
                        <div class="filter-field">
                            <label for="fecha_hasta_deudas_cobrar" class="form-label">Hasta</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_hasta_deudas_cobrar">
                        </div>
                        <div class="filter-field">
                            <label for="estado_filtro_deudas_cobrar" class="form-label">Estado</label>
                            <select class="form-select form-select-sm" id="estado_filtro_deudas_cobrar">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="vencida">Vencida</option>
                                <option value="cobrada">Cobrada</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-baseColor-light btn-sm" onclick="filtrarDeudasCobrar()">
                            <i class="fa-solid fa-filter"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosDeudasCobrar()" title="Limpiar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover display modern-table w-100 align-middle" id="tablaDeudasCobrar">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Deudor</th>
                                <th>Monto</th>
                                <th>Fecha Vencimiento</th>
                                <th>Estado</th>
                                <th>Factura CFDI</th>
                                <th>Servicio</th>
                                <th>Descripción</th>
                                <th>Archivo</th>
                                <th>Fecha Cobro</th>
                                <th>Monto Cobrado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="deudas-pagar" role="tabpanel" aria-labelledby="deudas-pagar-tab">
                <div class="cuentas-filters">
                    <div class="filters-row">
                        <div class="filter-field">
                            <label for="fecha_desde_deudas_pagar" class="form-label">Desde</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_desde_deudas_pagar">
                        </div>
                        <div class="filter-field">
                            <label for="fecha_hasta_deudas_pagar" class="form-label">Hasta</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_hasta_deudas_pagar">
                        </div>
                        <div class="filter-field">
                            <label for="estado_filtro_deudas_pagar" class="form-label">Estado</label>
                            <select class="form-select form-select-sm" id="estado_filtro_deudas_pagar">
                                <option value="">Todos</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="parcial">Parcial</option>
                                <option value="vencida">Vencida</option>
                                <option value="pagada">Pagada</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-baseColor-light btn-sm" onclick="filtrarDeudasPagar()">
                            <i class="fa-solid fa-filter"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosDeudasPagar()" title="Limpiar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover display modern-table w-100 align-middle" id="tablaDeudasPagar">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Acreedor</th>
                                <th>OC</th>
                                <th>Monto</th>
                                <th>Saldo</th>
                                <th>Fecha Vencimiento</th>
                                <th>Estado</th>
                                <th>Descripción</th>
                                <th>Archivo</th>
                                <th>Fecha Pago</th>
                                <th>Monto Pagado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ingresos -->
<div class="modal fade" id="modalIngreso" tabindex="-1" role="dialog" aria-labelledby="modalIngresoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header cuentas-modal-header">
                <h5 class="modal-title" id="modalIngresoLabel">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Registrar Nuevo Ingreso
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formIngreso" class="modern-form" action="{{ route('finanzas.guardar-ingreso') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_ingreso" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_ingreso" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monto_ingreso" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="monto_ingreso" name="monto" 
                                           step="0.01" min="0" placeholder="0.00" value="{{ old('monto') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="servicio_ingreso" class="form-label">Servicio relacionado</label>
                                <select class="form-control" id="servicio_ingreso" name="servicio_id">
                                    <option value="">Seleccionar servicio</option>
                                    @foreach($serviciosenc as $servicioenc)
                                        <option value="{{ $servicioenc->id }}" {{ old('servicio_id') == $servicioenc->id ? 'selected' : '' }}>
                                            {{ $servicioenc->nombre }} (Folio: {{ $servicioenc->folio }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="servicio_ingreso" class="form-label">Cuenta Bancaria</label>
                                <select class="form-control" id="servicio_ingreso_cuenta" name="cuenta_id">
                                    <option value="">Seleccionar cuenta</option>
                                    @foreach($cuentas as $cuenta)
                                        <option value="{{ $cuenta->id}}" {{ old('cuenta_id') == $cuenta->id ? 'selected' : '' }}>
                                            {{ $cuenta->nombre }} (Saldo: ${{ $cuenta->saldo_actual }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="concepto_ingreso" class="form-label">Concepto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="concepto_ingreso" name="concepto" 
                                       placeholder="Ej: Venta de productos" value="{{ old('concepto') }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="categoria_ingreso" class="form-label">Categoría</label>
                                <select class="form-control" id="categoria_ingreso" name="categoria">
                                    <option value="">Seleccionar categoría</option>
                                    <option value="ventas" {{ old('categoria') == 'ventas' ? 'selected' : '' }}>Ventas</option>
                                    <option value="servicios" {{ old('categoria') == 'servicios' ? 'selected' : '' }}>Servicios</option>
                                    <option value="inversiones" {{ old('categoria') == 'inversiones' ? 'selected' : '' }}>Inversiones</option>
                                    <option value="prestamos" {{ old('categoria') == 'prestamos' ? 'selected' : '' }}>Préstamos</option>
                                    <option value="otros" {{ old('categoria') == 'otros' ? 'selected' : '' }}>Otros</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="metodo_pago_ingreso" class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-control" id="metodo_pago_ingreso" name="metodo_pago" required>
                                    <option value="">Seleccionar método</option>
                                    <option value="efectivo" {{ old('metodo_pago') == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                                    <option value="tarjeta" {{ old('metodo_pago') == 'tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                                    <option value="transferencia" {{ old('metodo_pago') == 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                                    <option value="cheque" {{ old('metodo_pago') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                    <option value="deposito" {{ old('metodo_pago') == 'deposito' ? 'selected' : '' }}>Depósito</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="descripcion_ingreso" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_ingreso" name="descripcion" 
                                          rows="3" placeholder="Descripción detallada del ingreso">{{ old('descripcion') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="archivo_ingreso" class="form-label">
                                    <i class="fas fa-file-upload mr-1"></i>
                                    Archivo de respaldo (PDF o XML)
                                </label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="archivo_ingreso" name="archivo" 
                                           accept=".pdf,.xml" 
                                           onchange="validarArchivo(this)">
                                    <span class="input-group-text" data-bs-toggle="tooltip" title="Formatos permitidos: PDF, XML. Tamaño máximo: 5MB"><i class="fa-solid fa-circle-info"></i></span>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Formatos permitidos: PDF, XML. Tamaño máximo: 5MB
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i>
                        Guardar Ingreso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Egresos -->
<div class="modal fade" id="modalEgreso" tabindex="-1" role="dialog" aria-labelledby="modalEgresoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header cuentas-modal-header">
                <h5 class="modal-title" id="modalEgresoLabel">
                    <i class="fas fa-minus-circle mr-2"></i>
                    Registrar Nuevo Egreso
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEgreso2" class="modern-form" action="{{ route('finanzas.guardar-egreso') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_egreso" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_egreso" name="fecha" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monto_egreso" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="monto_egreso" name="monto" 
                                           step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="servicio_ingreso" class="form-label">Cuenta Bancaria</label>
                                <select class="form-control" id="servicio_ingreso_cuenta" name="cuenta_id">
                                    <option value="">Seleccionar cuenta</option>
                                    @foreach($cuentas as $cuenta)
                                        <option value="{{ $cuenta->id}}" {{ old('cuenta_id') == $cuenta->id ? 'selected' : '' }}>
                                            {{ $cuenta->nombre }} (Saldo: ${{ $cuenta->saldo_actual }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div> -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="concepto_egreso" class="form-label">Concepto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="concepto_egreso" name="concepto" 
                                       placeholder="Ej: Compra de materiales" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="categoria_egreso" class="form-label">Categoría</label>
                                <select class="form-control" id="categoria_egreso" name="categoria">
                                    <option value="">Seleccionar categoría</option>
                                    <option value="gastos_operacion">Gastos de Operación</option>
                                    <option value="gastos_administrativos">Gastos Administrativos</option>
                                    <option value="gastos_ventas">Gastos de Ventas</option>
                                    <option value="gastos_financieros">Gastos Financieros</option>
                                    <option value="otros">Otros</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="metodo_pago_egreso" class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-control" id="metodo_pago_egreso" name="metodo_pago" required>
                                    <option value="">Seleccionar método</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="descripcion_egreso" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_egreso" name="descripcion" 
                                          rows="3" placeholder="Descripción detallada del egreso"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="archivo_egreso" class="form-label">
                                    <i class="fas fa-file-upload mr-1"></i>
                                    Archivo de respaldo (PDF o XML)
                                </label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="archivo_egreso" name="archivo" 
                                           accept=".pdf,.xml" 
                                           onchange="validarArchivo(this)">
                                    <span class="input-group-text" data-bs-toggle="tooltip" title="Formatos permitidos: PDF, XML. Tamaño máximo: 5MB"><i class="fa-solid fa-circle-info"></i></span>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Formatos permitidos: PDF, XML. Tamaño máximo: 5MB
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save mr-1"></i>
                        Guardar Egreso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Deudas por Cobrar -->
<div class="modal fade" id="modalDeudaCobrar" tabindex="-1" role="dialog" aria-labelledby="modalDeudaCobrarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header cuentas-modal-header">
                <h5 class="modal-title" id="modalDeudaCobrarLabel">
                    <i class="fas fa-hand-holding-usd mr-2"></i>
                    Registrar Deuda por Cobrar
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDeudaCobrar" class="modern-form" action="{{ route('finanzas.guardar-deuda-cobrar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="deudor" class="form-label">Deudor <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="deudor" name="deudor" 
                                       placeholder="Nombre del deudor" value="{{ old('deudor') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monto_deuda_cobrar" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="monto_deuda_cobrar" name="monto" 
                                           step="0.01" min="0" placeholder="0.00" value="{{ old('monto') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="servicio_deuda_cobrar" class="form-label">Servicio relacionado</label>
                                <select class="form-control" id="servicio_deuda_cobrar" name="servicio_id">
                                    <option value="">Seleccionar servicio</option>
                                    @foreach($serviciosenc as $servicio)
                                        <option value="{{ $servicio->id }}" {{ old('servicio_id') == $servicio->id ? 'selected' : '' }}>
                                            {{ $servicio->nombre }} (Folio: {{ $servicio->folio }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <!-- <div class="col-md-6">
                            <div class="form-group">
                                <label for="cuenta_deuda_cobrar" class="form-label">Cuenta Bancaria</label>
                                <select class="form-control" id="cuenta_deuda_cobrar" name="cuenta_id">
                                    <option value="">Seleccionar cuenta</option>
                                    @foreach($cuentas as $cuenta)
                                        <option value="{{ $cuenta->id }}" {{ old('cuenta_id') == $cuenta->id ? 'selected' : '' }}>
                                            {{ $cuenta->nombre }} (Saldo: ${{ $cuenta->saldo_actual }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div> -->
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_vencimiento_cobrar" class="form-label">Fecha de Vencimiento <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_vencimiento_cobrar" name="fecha_vencimiento" 
                                       value="{{ old('fecha_vencimiento', date('Y-m-d', strtotime('+30 days'))) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado_deuda_cobrar" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-control" id="estado_deuda_cobrar" name="estado" required>
                                    <option value="">Seleccionar estado</option>
                                    <option value="pendiente" {{ old('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="vencida" {{ old('estado') == 'vencida' ? 'selected' : '' }}>Vencida</option>
                                    <option value="cobrada" {{ old('estado') == 'cobrada' ? 'selected' : '' }}>Cobrada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="descripcion_deuda_cobrar" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_deuda_cobrar" name="descripcion" 
                                          rows="3" placeholder="Descripción de la deuda por cobrar">{{ old('descripcion') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="archivo_deuda_cobrar" class="form-label">
                                    <i class="fas fa-file-upload mr-1"></i>
                                    Archivo de respaldo (PDF o XML)
                                </label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="archivo_deuda_cobrar" name="archivo" 
                                           accept=".pdf,.xml" 
                                           onchange="validarArchivo(this)">
                                    <span class="input-group-text" data-bs-toggle="tooltip" title="Formatos permitidos: PDF, XML. Tamaño máximo: 5MB"><i class="fa-solid fa-circle-info"></i></span>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Formatos permitidos: PDF, XML. Tamaño máximo: 5MB
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>
                        Guardar Deuda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Deudas por Pagar -->
<div class="modal fade" id="modalDeudaPagar" tabindex="-1" role="dialog" aria-labelledby="modalDeudaPagarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header cuentas-modal-header">
                <h5 class="modal-title" id="modalDeudaPagarLabel">
                    <i class="fas fa-credit-card mr-2"></i>
                    Registrar Deuda por Pagar
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDeudaPagar" class="modern-form" action="{{ route('finanzas.guardar-deuda-pagar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="acreedor" class="form-label">Acreedor <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="acreedor" name="acreedor" 
                                       placeholder="Nombre del acreedor" value="{{ old('acreedor') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="monto_deuda_pagar" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="monto_deuda_pagar" name="monto" 
                                           step="0.01" min="0" placeholder="0.00" value="{{ old('monto') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                   <!-- <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="cuenta_deuda_pagar" class="form-label">Cuenta Bancaria</label>
                                <select class="form-control" id="cuenta_deuda_pagar" name="cuenta_id">
                                    <option value="">Seleccionar cuenta</option>
                                    @foreach($cuentas as $cuenta)
                                        <option value="{{ $cuenta->id }}" {{ old('cuenta_id') == $cuenta->id ? 'selected' : '' }}>
                                            {{ $cuenta->nombre }} (Saldo: ${{ $cuenta->saldo_actual }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div> -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_vencimiento_pagar" class="form-label">Fecha de Vencimiento <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="fecha_vencimiento_pagar" name="fecha_vencimiento" 
                                       value="{{ old('fecha_vencimiento', date('Y-m-d', strtotime('+30 days'))) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado_deuda_pagar" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-control" id="estado_deuda_pagar" name="estado" required>
                                    <option value="">Seleccionar estado</option>
                                    <option value="pendiente" {{ old('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="vencida" {{ old('estado') == 'vencida' ? 'selected' : '' }}>Vencida</option>
                                    <option value="pagada" {{ old('estado') == 'pagada' ? 'selected' : '' }}>Pagada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="descripcion_deuda_pagar" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_deuda_pagar" name="descripcion" 
                                          rows="3" placeholder="Descripción de la deuda por pagar">{{ old('descripcion') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="archivo_deuda_pagar" class="form-label">
                                    <i class="fas fa-file-upload mr-1"></i>
                                    Archivo de respaldo (PDF o XML)
                                </label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="archivo_deuda_pagar" name="archivo" 
                                           accept=".pdf,.xml" 
                                           onchange="validarArchivo(this)">
                                    <span class="input-group-text" data-bs-toggle="tooltip" title="Formatos permitidos: PDF, XML. Tamaño máximo: 5MB"><i class="fa-solid fa-circle-info"></i></span>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Formatos permitidos: PDF, XML. Tamaño máximo: 5MB
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i>
                        Guardar Deuda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Deudas por Pagar -->
<div class="modal fade" id="modalEditarDeudaPagar" tabindex="-1" role="dialog" aria-labelledby="modalEditarDeudaPagarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header cuentas-modal-header">
                <h5 class="modal-title" id="modalEditarDeudaPagarLabel">
                    <i class="fas fa-edit mr-2"></i>
                    Editar Deuda por Pagar
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarDeudaPagar" class="modern-form">
                <div class="modal-body">
                    <input type="hidden" id="edit_deuda_pagar_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_acreedor" class="form-label">Acreedor</label>
                                <input type="text" class="form-control" id="edit_acreedor" readonly>
                                <small class="form-text text-muted">Este campo no se puede editar</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_monto_deuda_pagar" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="edit_monto_deuda_pagar" name="monto" 
                                           step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_fecha_vencimiento_pagar" class="form-label">Fecha de Vencimiento <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_fecha_vencimiento_pagar" name="fecha_vencimiento" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_estado_deuda_pagar" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_estado_deuda_pagar" name="estado" required>
                                    <option value="">Seleccionar estado</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="parcial">Parcial</option>
                                    <option value="vencida">Vencida</option>
                                    <option value="pagada">Pagada</option>
                                    <option value="anulada">Anulada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_monto_pagado" class="form-label">Monto Pagado</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="edit_monto_pagado" name="monto_pagado" 
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_fecha_pago" class="form-label">Fecha de Pago</label>
                                <input type="date" class="form-control" id="edit_fecha_pago" name="fecha_pago">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_descripcion_deuda_pagar" class="form-label">Descripción</label>
                                <textarea class="form-control" id="edit_descripcion_deuda_pagar" rows="3" readonly></textarea>
                                <small class="form-text text-muted">Este campo no se puede editar</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i>
                        Actualizar Deuda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal abono deuda por pagar -->
<div class="modal fade" id="modalAbonoDeudaPagar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header cuentas-modal-header">
                <h5 class="modal-title"><i class="fas fa-dollar-sign me-2"></i>Registrar abono a proveedor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAbonoDeudaPagar">
                @csrf
                <input type="hidden" id="abono_deuda_id">
                <div class="modal-body">
                    <p class="mb-2"><strong id="abono_deuda_acreedor">—</strong></p>
                    <p class="text-muted fs-8 mb-3">Saldo pendiente: <strong id="abono_deuda_saldo">$0.00</strong></p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Monto *</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="abono_monto" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha *</label>
                            <input type="date" class="form-control" id="abono_fecha" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Método</label>
                            <select class="form-select" id="abono_metodo">
                                <option value="Transferencia electrónica">Transferencia</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Cheque nominativo">Cheque</option>
                                <option value="Tarjeta de débito">Tarjeta de débito</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cuenta bancaria</label>
                            <select class="form-select" id="abono_cuenta_id">
                                <option value="">Sin movimiento de cuenta</option>
                                @foreach(($cuentas ?? []) as $cuenta)
                                    <option value="{{ $cuenta->id }}">{{ $cuenta->nombre }} ({{ number_format((float)$cuenta->saldo_actual, 2) }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Si eliges cuenta, se crea un egreso y baja el saldo.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Referencia</label>
                            <input type="text" class="form-control" id="abono_referencia" maxlength="120" placeholder="SPEI, cheque, etc.">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <input type="text" class="form-control" id="abono_notas" maxlength="500">
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6 class="fs-8 text-uppercase text-muted">Últimos abonos</h6>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Ref.</th><th>Usuario</th></tr></thead>
                                <tbody id="abono_historial_body">
                                    <tr><td colspan="5" class="text-muted">Sin abonos</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar abono</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Egresos -->
<div class="modal fade" id="modalEditarEgreso" tabindex="-1" role="dialog" aria-labelledby="modalEditarEgresoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header cuentas-modal-header">
                <h5 class="modal-title" id="modalEditarEgresoLabel">
                    <i class="fas fa-edit mr-2"></i>
                    Editar Egreso
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarEgreso" class="modern-form">
                <div class="modal-body">
                    <input type="hidden" id="edit_egreso_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_fecha_egreso" class="form-label">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_fecha_egreso" name="fecha" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_monto_egreso" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="edit_monto_egreso" name="monto" 
                                           step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_concepto_egreso" class="form-label">Concepto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_concepto_egreso" name="concepto" 
                                       placeholder="Ej: Compra de materiales" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_categoria_egreso" class="form-label">Categoría</label>
                                <select class="form-control" id="edit_categoria_egreso" name="categoria">
                                    <option value="">Seleccionar categoría</option>
                                    <option value="gastos_operacion">Gastos de Operación</option>
                                    <option value="gastos_administrativos">Gastos Administrativos</option>
                                    <option value="gastos_ventas">Gastos de Ventas</option>
                                    <option value="gastos_financieros">Gastos Financieros</option>
                                    <option value="otros">Otros</option>
                                </select>
                                <small class="form-text text-muted">Este campo no se puede editar</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_metodo_pago_egreso" class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_metodo_pago_egreso" name="metodo_pago" required>
                                    <option value="">Seleccionar método</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_descripcion_egreso" class="form-label">Descripción</label>
                                <textarea class="form-control" id="edit_descripcion_egreso" rows="3" readonly></textarea>
                                <small class="form-text text-muted">Este campo no se puede editar</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save mr-1"></i>
                        Actualizar Egreso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Deudas por Cobrar -->
<div class="modal fade" id="modalEditarDeudaCobrar" tabindex="-1" role="dialog" aria-labelledby="modalEditarDeudaCobrarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header cuentas-modal-header">
                <h5 class="modal-title" id="modalEditarDeudaCobrarLabel">
                    <i class="fas fa-edit mr-2"></i>
                    Editar Deuda por Cobrar
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarDeudaCobrar" class="modern-form">
                <div class="modal-body">
                    <input type="hidden" id="edit_deuda_cobrar_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_deudor" class="form-label">Deudor</label>
                                <input type="text" class="form-control" id="edit_deudor" readonly>
                                <small class="form-text text-muted">Este campo no se puede editar</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_monto_deuda_cobrar" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="edit_monto_deuda_cobrar" name="monto" 
                                           step="0.01" min="0" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_fecha_vencimiento_cobrar" class="form-label">Fecha de Vencimiento <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_fecha_vencimiento_cobrar" name="fecha_vencimiento" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_estado_deuda_cobrar" class="form-label">Estado <span class="text-danger">*</span></label>
                                <select class="form-control" id="edit_estado_deuda_cobrar" name="estado" required>
                                    <option value="">Seleccionar estado</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="vencida">Vencida</option>
                                    <option value="cobrada">Cobrada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_monto_cobrado" class="form-label">Monto Cobrado</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="edit_monto_cobrado" name="monto_cobrado" 
                                           step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_fecha_cobro" class="form-label">Fecha de Cobro</label>
                                <input type="date" class="form-control" id="edit_fecha_cobro" name="fecha_cobro">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_descripcion_deuda_cobrar" class="form-label">Descripción</label>
                                <textarea class="form-control" id="edit_descripcion_deuda_cobrar" rows="3" readonly></textarea>
                                <small class="form-text text-muted">Este campo no se puede editar</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>
                        Actualizar Deuda
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Establecer fecha actual en los modales
    var today = new Date().toISOString().split('T')[0];
    $('#fecha_ingreso, #fecha_egreso').val(today);
    
    // Establecer fechas por defecto en los modales de deudas
    $('#fecha_vencimiento').val(today);
    $('#fecha_cobro').val(today);
    $('#fecha_pago').val(today);
    
    // Función para validar archivos
    window.validarArchivo = function(input) {
        const file = input.files[0];
        if (file) {
            // Validar tipo de archivo
            const allowedTypes = ['application/pdf', 'text/xml', 'application/xml'];
            const fileType = file.type;
            
            if (!allowedTypes.includes(fileType) && !file.name.toLowerCase().endsWith('.pdf') && !file.name.toLowerCase().endsWith('.xml')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Tipo de archivo no válido',
                    text: 'Solo se permiten archivos PDF o XML.',
                    confirmButtonText: 'Entendido'
                });
                input.value = '';
                return false;
            }
            
            // Validar tamaño (5MB = 5 * 1024 * 1024 bytes)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'Archivo demasiado grande',
                    text: 'El archivo no debe superar los 5MB.',
                    confirmButtonText: 'Entendido'
                });
                input.value = '';
                return false;
            }
            
            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: 'Archivo válido',
                text: 'El archivo ha sido seleccionado correctamente.',
                timer: 1500,
                showConfirmButton: false
            });
        }
    };
    
    // Manejar envío del formulario de ingreso (comentado para usar POST tradicional)
    /*
    $('#formIngreso').on('submit', function(e) {
        e.preventDefault();
        
        // Crear FormData para manejar archivos
        var formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("finanzas.guardar-ingreso") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Cerrar modal y limpiar formulario
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalIngreso'));
                    modal.hide();
                    $('#formIngreso')[0].reset();
                    $('#fecha_ingreso').val(today);
                    
                    // Actualizar totales (aquí puedes agregar lógica para actualizar los totales)
                    actualizarTotales();
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                var message = 'Error al procesar la solicitud';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });
    */
    
    // Limpiar formulario cuando se cierre el modal de ingreso
    $('#modalIngreso').on('hidden.bs.modal', function () {
        $('#formIngreso')[0].reset();
        $('#fecha_ingreso').val(today);
    });

    // Limpiar formulario cuando se cierre el modal de deuda por cobrar
    $('#modalDeudaCobrar').on('hidden.bs.modal', function () {
        $('#formDeudaCobrar')[0].reset();
        $('#fecha_vencimiento').val('');
        $('#fecha_cobro').val('');
    });

    // Limpiar formulario cuando se cierre el modal de deuda por pagar
    $('#modalDeudaPagar').on('hidden.bs.modal', function () {
        $('#formDeudaPagar')[0].reset();
        $('#fecha_vencimiento').val('');
        $('#fecha_pago').val('');
    });
    
    // Manejar envío del formulario de egreso
    $('#formEgreso').on('submit', function(e) {
        e.preventDefault();
        
        // Crear FormData para manejar archivos
        var formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("finanzas.guardar-egreso") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalEgreso'));
                    modal.hide();
                    $('#formEgreso')[0].reset();
                    $('#fecha_egreso').val(today);
                    
                    actualizarTotales();
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                var message = 'Error al procesar la solicitud';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });
    
    // Manejar envío del formulario de deuda por cobrar
    $('#formDeudaCobrar').on('submit', function(e) {
        e.preventDefault();
        
        // Crear FormData para manejar archivos
        var formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("finanzas.guardar-deuda-cobrar") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalDeudaCobrar'));
                    modal.hide();
                    $('#formDeudaCobrar')[0].reset();
                    
                    actualizarTotales();
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                var message = 'Error al procesar la solicitud';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });
    
    // Manejar envío del formulario de deuda por pagar
    $('#formDeudaPagar').on('submit', function(e) {
        e.preventDefault();
        
        // Crear FormData para manejar archivos
        var formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("finanzas.guardar-deuda-pagar") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalDeudaPagar'));
                    modal.hide();
                    $('#formDeudaPagar')[0].reset();
                    
                    actualizarTotales();
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                var message = 'Error al procesar la solicitud';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });
    
    function renderFilaIngreso(ingreso) {
        return '<tr>' +
            '<td>' + ingreso.id + '</td>' +
            '<td>' + ingreso.fecha + '</td>' +
            '<td>' + ingreso.concepto + '</td>' +
            '<td>$' + ingreso.monto + '</td>' +
            '<td>' + (ingreso.categoria || 'N/A') + '</td>' +
            '<td>' + ingreso.metodo_pago + '</td>' +
            '<td>' + (ingreso.factura || 'N/A') + '</td>' +
            '<td>' + ingreso.servicio + '</td>' +
            '<td>' + (ingreso.descripcion || 'N/A') + '</td>' +
            '<td>' + ingreso.archivo + '</td>' +
            '<td>' + ingreso.registrado_por + '</td>' +
            '<td>' + ingreso.acciones + '</td>' +
            '</tr>';
    }

    function renderFilaEgreso(egreso) {
        return '<tr>' +
            '<td>' + egreso.id + '</td>' +
            '<td>' + egreso.fecha + '</td>' +
            '<td>' + egreso.concepto + '</td>' +
            '<td>$' + egreso.monto + '</td>' +
            '<td>' + (egreso.categoria || 'N/A') + '</td>' +
            '<td>' + egreso.metodo_pago + '</td>' +
            '<td>' + (egreso.factura || 'N/A') + '</td>' +
            '<td>' + (egreso.descripcion || 'N/A') + '</td>' +
            '<td>' + egreso.archivo + '</td>' +
            '<td>' + egreso.registrado_por + '</td>' +
            '<td>' + egreso.acciones + '</td>' +
            '</tr>';
    }

    function renderFilaDeudaCobrar(deuda) {
        return '<tr>' +
            '<td>' + deuda.id + '</td>' +
            '<td>' + deuda.deudor + '</td>' +
            '<td>$' + deuda.monto + '</td>' +
            '<td>' + deuda.fecha_vencimiento + '</td>' +
            '<td>' + deuda.estado + '</td>' +
            '<td>' + (deuda.factura || 'N/A') + '</td>' +
            '<td>' + (deuda.servicio || 'N/A') + '</td>' +
            '<td>' + (deuda.descripcion || 'N/A') + '</td>' +
            '<td>' + (deuda.archivo || 'N/A') + '</td>' +
            '<td>' + (deuda.fecha_cobro || 'N/A') + '</td>' +
            '<td>$' + (deuda.monto_cobrado || '0.00') + '</td>' +
            '<td>' + deuda.acciones + '</td>' +
            '</tr>';
    }

    function renderFilaDeudaPagar(deuda) {
        return '<tr>' +
            '<td>' + deuda.id + '</td>' +
            '<td>' + deuda.acreedor + '</td>' +
            '<td>' + (deuda.oc || '—') + '</td>' +
            '<td>$' + deuda.monto + '</td>' +
            '<td>$' + (deuda.saldo || '0.00') + '</td>' +
            '<td>' + deuda.fecha_vencimiento + '</td>' +
            '<td>' + deuda.estado + '</td>' +
            '<td>' + (deuda.descripcion || 'N/A') + '</td>' +
            '<td>' + (deuda.archivo || 'N/A') + '</td>' +
            '<td>' + (deuda.fecha_pago || 'N/A') + '</td>' +
            '<td>$' + (deuda.monto_pagado || '0.00') + '</td>' +
            '<td>' + deuda.acciones + '</td>' +
            '</tr>';
    }

    var cuentasDtLanguage = {
        decimal: '.',
        thousands: ',',
        emptyTable: 'No hay registros disponibles',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        infoFiltered: '(filtrado de _MAX_ registros)',
        lengthMenu: 'Mostrar _MENU_ registros',
        loadingRecords: 'Cargando...',
        processing: 'Procesando...',
        search: 'Buscar:',
        zeroRecords: 'No se encontraron resultados',
        paginate: {
            first: 'Primero',
            last: 'Último',
            next: 'Siguiente',
            previous: 'Anterior'
        }
    };

    function isTabPaneVisible($table) {
        var $pane = $table.closest('.tab-pane');
        if (!$pane.length) {
            return true;
        }
        return $pane.hasClass('active') || $pane.hasClass('show') || $pane.is(':visible');
    }

    function destroyCuentasDataTable($table) {
        if ($.fn.DataTable.isDataTable($table)) {
            $table.DataTable().clear().destroy();
        }

        var $wrapper = $table.closest('.dataTables_wrapper');
        if ($wrapper.length) {
            $table.insertBefore($wrapper);
            $wrapper.remove();
        }

        $table.removeClass('dataTable no-footer dtr-inline');
        $table.css('width', '');
        $table.find('tbody').show();
    }

    function initCuentasDataTable(selector) {
        var $table = $(selector);
        if (!$table.length || typeof $.fn.DataTable !== 'function') {
            console.error('DataTables no disponible para', selector);
            return null;
        }

        if ($.fn.DataTable.isDataTable($table)) {
            return $table.DataTable();
        }

        var language = $.extend({}, cuentasDtLanguage, {
            emptyTable: $table.data('dt-empty') || cuentasDtLanguage.emptyTable
        });

        try {
            return $table.DataTable({
                responsive: false,
                scrollX: true,
                autoWidth: false,
                pageLength: 10,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: -1, className: 'td-actions text-nowrap' }
                ],
                language: language,
                layout: {
                    topStart: 'pageLength',
                    topEnd: 'search',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                }
            });
        } catch (err) {
            console.error('Error al inicializar DataTable', selector, err);
            return null;
        }
    }

    function cargarFilasDataTable(selector, rowsHtml, emptyMessage) {
        var $table = $(selector);
        if (!$table.length) {
            return;
        }

        destroyCuentasDataTable($table);
        $table.find('tbody').html(rowsHtml || '');
        $table.data('dt-empty', emptyMessage || 'No hay registros disponibles');

        // Ingresos (activa) siempre se inicializa; el resto al mostrarse
        if (selector === '#tablaIngresos' || isTabPaneVisible($table)) {
            $table.removeData('dt-pending');
            // Esperar un frame para que el tab visible tenga layout
            requestAnimationFrame(function () {
                initCuentasDataTable(selector);
            });
            return;
        }

        $table.data('dt-pending', true);
    }

    function ensureVisibleCuentasTable(selector) {
        var $table = $(selector);
        if (!$table.length) {
            return;
        }

        if ($table.data('dt-pending') || !$.fn.DataTable.isDataTable($table)) {
            $table.removeData('dt-pending');
            requestAnimationFrame(function () {
                initCuentasDataTable(selector);
            });
            return;
        }

        $table.DataTable().columns.adjust().draw(false);
    }

    $('#finanzasTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr('data-bs-target');
        var $table = $(target).find('table[id^="tabla"]').first();
        if ($table.length) {
            ensureVisibleCuentasTable('#' + $table.attr('id'));
        }
    });

    // Función para cargar tabla de ingresos
    function cargarTablaIngresos(filtros) {
        $.ajax({
            url: '{{ route("finanzas.obtener-ingresos") }}',
            type: 'GET',
            data: filtros || {},
            success: function(response) {
                if (response.success) {
                    var rows = '';
                    (response.data || []).forEach(function(ingreso) {
                        rows += renderFilaIngreso(ingreso);
                    });
                    cargarFilasDataTable(
                        '#tablaIngresos',
                        rows,
                        'No hay ingresos ligados a facturas CFDI.'
                    );
                }
            },
            error: function(xhr) {
                console.error('Error al cargar ingresos:', xhr);
            }
        });
    }
    
    // Función para cargar tabla de egresos
    function cargarTablaEgresos(filtros) {
        $.ajax({
            url: '{{ route("finanzas.obtener-egresos") }}',
            type: 'GET',
            data: filtros || {},
            success: function(response) {
                if (response.success) {
                    var rows = '';
                    (response.data || []).forEach(function(egreso) {
                        rows += renderFilaEgreso(egreso);
                    });
                    cargarFilasDataTable(
                        '#tablaEgresos',
                        rows,
                        'No hay egresos ni notas de crédito ligadas a facturas.'
                    );
                }
            },
            error: function(xhr) {
                console.error('Error al cargar egresos:', xhr);
            }
        });
    }
    
    // Función para cargar tabla de deudas por cobrar
    function cargarTablaDeudasCobrar(filtros) {
        $.ajax({
            url: '{{ route("finanzas.obtener-deudas-cobrar") }}',
            type: 'GET',
            data: filtros || {},
            success: function(response) {
                if (response.success) {
                    var rows = '';
                    (response.data || []).forEach(function(deuda) {
                        rows += renderFilaDeudaCobrar(deuda);
                    });
                    cargarFilasDataTable(
                        '#tablaDeudasCobrar',
                        rows,
                        'No hay deudas por cobrar ligadas a facturas PPD.'
                    );
                }
            },
            error: function(xhr) {
                console.error('Error al cargar deudas por cobrar:', xhr);
            }
        });
    }
    
    // Función para cargar tabla de deudas por pagar
    function cargarTablaDeudasPagar(filtros) {
        $.ajax({
            url: '{{ route("finanzas.obtener-deudas-pagar") }}',
            type: 'GET',
            data: filtros || {},
            success: function(response) {
                if (response.success) {
                    var rows = '';
                    (response.data || []).forEach(function(deuda) {
                        rows += renderFilaDeudaPagar(deuda);
                    });
                    cargarFilasDataTable(
                        '#tablaDeudasPagar',
                        rows,
                        'No hay deudas por pagar registradas.'
                    );
                }
            },
            error: function(xhr) {
                console.error('Error al cargar deudas por pagar:', xhr);
            }
        });
    }

    function cargarTodasLasTablas() {
        cargarTablaIngresos();
        cargarTablaEgresos();
        cargarTablaDeudasCobrar();
        cargarTablaDeudasPagar();
    }
    
    // Actualizar totales y, opcionalmente, recargar tablas
    function actualizarTotales(recargarTablas) {
        $.ajax({
            url: '{{ route("finanzas.obtener-resumen") }}',
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    var data = response.data;
                    if (data.ingresos !== undefined) {
                        $('#totalIngresos').text('$' + parseFloat(data.ingresos).toFixed(2));
                    }
                    if (data.egresos !== undefined) {
                        $('#totalEgresos').text('$' + parseFloat(data.egresos).toFixed(2));
                    }
                    if (data.deudas_cobrar !== undefined) {
                        $('#totalDeudasCobrar').text('$' + parseFloat(data.deudas_cobrar).toFixed(2));
                    }
                    if (data.deudas_pagar !== undefined) {
                        $('#totalDeudasPagar').text('$' + parseFloat(data.deudas_pagar).toFixed(2));
                    }
                }

                if (recargarTablas !== false) {
                    cargarTodasLasTablas();
                }
            },
            error: function() {
                if (recargarTablas !== false) {
                    cargarTodasLasTablas();
                }
            }
        });
    }

    // Carga inicial: DataTables en Ingresos al instante, luego datos y KPIs
    $('#tablaIngresos').data('dt-empty', 'Cargando ingresos...');
    initCuentasDataTable('#tablaIngresos');
    cargarTodasLasTablas();
    actualizarTotales(false);
    
    // Funciones placeholder para editar y eliminar (se pueden implementar después)
    window.editarIngreso = function(id) {
        Swal.fire({
            icon: 'info',
            title: 'Función en desarrollo',
            text: 'La función de editar ingreso estará disponible próximamente.'
        });
    };
    
    window.eliminarIngreso = function(id) {
        Swal.fire({
            icon: 'info',
            title: 'Función en desarrollo',
            text: 'La función de eliminar ingreso estará disponible próximamente.'
        });
    };
    
    // Funciones para filtrar ingresos
    window.filtrarIngresos = function() {
        cargarTablaIngresos({
            fecha_desde: $('#fecha_desde_ingresos').val(),
            fecha_hasta: $('#fecha_hasta_ingresos').val(),
            categoria: $('#categoria_filtro_ingresos').val()
        });
    };
    
    window.limpiarFiltrosIngresos = function() {
        $('#fecha_desde_ingresos').val('');
        $('#fecha_hasta_ingresos').val('');
        $('#categoria_filtro_ingresos').val('');
        cargarTablaIngresos();
    };
    
    // Funciones para filtrar egresos
    window.filtrarEgresos = function() {
        cargarTablaEgresos({
            fecha_desde: $('#fecha_desde_egresos').val(),
            fecha_hasta: $('#fecha_hasta_egresos').val(),
            categoria: $('#categoria_filtro_egresos').val()
        });
    };
    
    window.limpiarFiltrosEgresos = function() {
        $('#fecha_desde_egresos').val('');
        $('#fecha_hasta_egresos').val('');
        $('#categoria_filtro_egresos').val('');
        cargarTablaEgresos();
    };
    
    // Funciones para filtrar deudas por cobrar
    window.filtrarDeudasCobrar = function() {
        cargarTablaDeudasCobrar({
            fecha_desde: $('#fecha_desde_deudas_cobrar').val(),
            fecha_hasta: $('#fecha_hasta_deudas_cobrar').val(),
            estado: $('#estado_filtro_deudas_cobrar').val()
        });
    };
    
    window.limpiarFiltrosDeudasCobrar = function() {
        $('#fecha_desde_deudas_cobrar').val('');
        $('#fecha_hasta_deudas_cobrar').val('');
        $('#estado_filtro_deudas_cobrar').val('');
        cargarTablaDeudasCobrar();
    };
    
    // Funciones para filtrar deudas por pagar
    window.filtrarDeudasPagar = function() {
        cargarTablaDeudasPagar({
            fecha_desde: $('#fecha_desde_deudas_pagar').val(),
            fecha_hasta: $('#fecha_hasta_deudas_pagar').val(),
            estado: $('#estado_filtro_deudas_pagar').val()
        });
    };
    
    window.limpiarFiltrosDeudasPagar = function() {
        $('#fecha_desde_deudas_pagar').val('');
        $('#fecha_hasta_deudas_pagar').val('');
        $('#estado_filtro_deudas_pagar').val('');
        cargarTablaDeudasPagar();
    };
    
    window.editarEgreso = function(id) {
        // Obtener los datos del egreso
        $.ajax({
            url: '{{ route("finanzas.obtener-egreso", ":id") }}'.replace(':id', id),
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var egreso = response.data;
                    
                    // Llenar el modal con los datos
                    $('#edit_egreso_id').val(egreso.id);
                    $('#edit_fecha_egreso').val(egreso.fecha);
                    $('#edit_monto_egreso').val(egreso.monto);
                    $('#edit_concepto_egreso').val(egreso.concepto);
                    $('#edit_categoria_egreso').val(egreso.categoria);
                    $('#edit_metodo_pago_egreso').val(egreso.metodo_pago);
                    $('#edit_descripcion_egreso').val(egreso.descripcion);
                    
                    // Mostrar el modal
                    var modal = new bootstrap.Modal(document.getElementById('modalEditarEgreso'));
                    modal.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                var message = 'Error al obtener los datos del egreso';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    };
    
    // Manejar envío del formulario de editar egreso
    $('#formEditarEgreso').on('submit', function(e) {
        e.preventDefault();
        
        var id = $('#edit_egreso_id').val();
        var formData = {
            fecha: $('#edit_fecha_egreso').val(),
            monto: $('#edit_monto_egreso').val(),
            concepto: $('#edit_concepto_egreso').val(),
            metodo_pago: $('#edit_metodo_pago_egreso').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: '{{ route("finanzas.actualizar-egreso", ":id") }}'.replace(':id', id),
            type: 'PUT',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Cerrar modal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarEgreso'));
                    modal.hide();
                    
                    // Recargar las tablas y totales
                    actualizarTotales();
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                var message = 'Error al actualizar el egreso';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });
    
    window.eliminarEgreso = function(id) {
        // Mostrar confirmación antes de eliminar
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer. El egreso será eliminado permanentemente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Realizar la eliminación
                $.ajax({
                    url: '{{ route("finanzas.eliminar-egreso", ":id") }}'.replace(':id', id),
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Recargar las tablas y totales
                            actualizarTotales();
                            
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        var message = 'Error al eliminar el egreso';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                    }
                });
            }
        });
    };
    
    window.editarDeudaCobrar = function(id) {
        // Obtener los datos de la deuda por cobrar
        $.ajax({
            url: '{{ route("finanzas.obtener-deuda-cobrar", ":id") }}'.replace(':id', id),
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var deuda = response.data;
                    
                    // Llenar el modal con los datos
                    $('#edit_deuda_cobrar_id').val(deuda.id);
                    $('#edit_deudor').val(deuda.deudor);
                    $('#edit_monto_deuda_cobrar').val(deuda.monto);
                    $('#edit_fecha_vencimiento_cobrar').val(deuda.fecha_vencimiento);
                    $('#edit_estado_deuda_cobrar').val(deuda.estado);
                    $('#edit_monto_cobrado').val(deuda.monto_cobrado || '');
                    $('#edit_fecha_cobro').val(deuda.fecha_cobro || '');
                    $('#edit_descripcion_deuda_cobrar').val(deuda.descripcion);
                    
                    // Mostrar el modal
                    var modal = new bootstrap.Modal(document.getElementById('modalEditarDeudaCobrar'));
                    modal.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                var message = 'Error al obtener los datos de la deuda por cobrar';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    };
    
    // Manejar envío del formulario de editar deuda por cobrar
    $('#formEditarDeudaCobrar').on('submit', function(e) {
        e.preventDefault();
        
        var id = $('#edit_deuda_cobrar_id').val();
        var formData = {
            monto: $('#edit_monto_deuda_cobrar').val(),
            fecha_vencimiento: $('#edit_fecha_vencimiento_cobrar').val(),
            estado: $('#edit_estado_deuda_cobrar').val(),
            monto_cobrado: $('#edit_monto_cobrado').val(),
            fecha_cobro: $('#edit_fecha_cobro').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: '{{ route("finanzas.actualizar-deuda-cobrar", ":id") }}'.replace(':id', id),
            type: 'PUT',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Cerrar modal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarDeudaCobrar'));
                    modal.hide();
                    
                    // Recargar las tablas y totales
                    actualizarTotales();
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                var message = 'Error al actualizar la deuda por cobrar';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });
    
    window.eliminarDeudaCobrar = function(id) {
        // Mostrar confirmación antes de eliminar
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer. La deuda por cobrar será eliminada permanentemente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Realizar la eliminación
                $.ajax({
                    url: '{{ route("finanzas.eliminar-deuda-cobrar", ":id") }}'.replace(':id', id),
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Recargar las tablas y totales
                            actualizarTotales();
                            
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        var message = 'Error al eliminar la deuda por cobrar';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                    }
                });
            }
        });
    };
    
    window.abrirAbonoDeudaPagar = function(id) {
        $.ajax({
            url: '{{ route("finanzas.obtener-deuda-pagar", ":id") }}'.replace(':id', id),
            type: 'GET',
            success: function(response) {
                if (!response.success) {
                    return;
                }
                var deuda = response.data;
                $('#abono_deuda_id').val(deuda.id);
                $('#abono_deuda_acreedor').text(deuda.acreedor || ('Deuda #' + deuda.id));
                $('#abono_deuda_saldo').text('$' + parseFloat(deuda.saldo || 0).toFixed(2));
                $('#abono_monto').val('');
                $('#abono_fecha').val('{{ date("Y-m-d") }}');
                $('#abono_referencia').val('');
                $('#abono_notas').val('');
                cargarHistorialAbonos(deuda.id);
                var modal = new bootstrap.Modal(document.getElementById('modalAbonoDeudaPagar'));
                modal.show();
            }
        });
    };

    function cargarHistorialAbonos(id) {
        $.get('{{ url("/Finanzas/deudas-pagar") }}/' + id + '/abonos', function(res) {
            var body = $('#abono_historial_body');
            if (!res.success || !res.data || !res.data.length) {
                body.html('<tr><td colspan="5" class="text-muted">Sin abonos</td></tr>');
                return;
            }
            var html = '';
            res.data.forEach(function(p) {
                html += '<tr><td>' + (p.fecha || '') + '</td><td>$' + p.monto + '</td><td>' + (p.metodo_pago || '') + '</td><td>' + (p.referencia || '—') + '</td><td>' + (p.usuario || '—') + '</td></tr>';
            });
            body.html(html);
        });
    }

    $('#formAbonoDeudaPagar').on('submit', function(e) {
        e.preventDefault();
        var id = $('#abono_deuda_id').val();
        $.ajax({
            url: '{{ url("/Finanzas/deudas-pagar") }}/' + id + '/abonos',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                monto: $('#abono_monto').val(),
                fecha: $('#abono_fecha').val(),
                metodo_pago: $('#abono_metodo').val(),
                cuenta_id: $('#abono_cuenta_id').val(),
                referencia: $('#abono_referencia').val(),
                notas: $('#abono_notas').val()
            },
            success: function(response) {
                if (response.success) {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalAbonoDeudaPagar'));
                    if (modal) modal.hide();
                    Swal.fire({ icon: 'success', title: 'Abono registrado', text: response.message, timer: 2500, showConfirmButton: false });
                    cargarTablaDeudasPagar();
                    actualizarTotales(false);
                } else {
                    Swal.fire({ icon: 'error', title: 'No se pudo abonar', text: response.message });
                }
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error al registrar el abono';
                Swal.fire({ icon: 'error', title: 'Error', text: message });
            }
        });
    });

    window.editarDeudaPagar = function(id) {
        // Obtener los datos de la deuda por pagar
        $.ajax({
            url: '{{ route("finanzas.obtener-deuda-pagar", ":id") }}'.replace(':id', id),
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var deuda = response.data;
                    
                    // Llenar el modal con los datos
                    $('#edit_deuda_pagar_id').val(deuda.id);
                    $('#edit_acreedor').val(deuda.acreedor);
                    $('#edit_monto_deuda_pagar').val(deuda.monto);
                    $('#edit_fecha_vencimiento_pagar').val(deuda.fecha_vencimiento);
                    $('#edit_estado_deuda_pagar').val(deuda.estado);
                    $('#edit_monto_pagado').val(deuda.monto_pagado || '');
                    $('#edit_fecha_pago').val(deuda.fecha_pago || '');
                    $('#edit_descripcion_deuda_pagar').val(deuda.descripcion);
                    
                    // Mostrar el modal
                    var modal = new bootstrap.Modal(document.getElementById('modalEditarDeudaPagar'));
                    modal.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                var message = 'Error al obtener los datos de la deuda por pagar';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    };
    
    // Manejar envío del formulario de editar deuda por pagar
    $('#formEditarDeudaPagar').on('submit', function(e) {
        e.preventDefault();
        
        var id = $('#edit_deuda_pagar_id').val();
        var formData = {
            monto: $('#edit_monto_deuda_pagar').val(),
            fecha_vencimiento: $('#edit_fecha_vencimiento_pagar').val(),
            estado: $('#edit_estado_deuda_pagar').val(),
            monto_pagado: $('#edit_monto_pagado').val(),
            fecha_pago: $('#edit_fecha_pago').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: '{{ route("finanzas.actualizar-deuda-pagar", ":id") }}'.replace(':id', id),
            type: 'PUT',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Cerrar modal
                    var modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarDeudaPagar'));
                    modal.hide();
                    
                    // Recargar las tablas y totales
                    actualizarTotales();
                    
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                var message = 'Error al actualizar la deuda por pagar';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
                });
            }
        });
    });
    
    window.eliminarDeudaPagar = function(id) {
        // Mostrar confirmación antes de eliminar
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer. La deuda por pagar será eliminada permanentemente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Realizar la eliminación
                $.ajax({
                    url: '{{ route("finanzas.eliminar-deuda-pagar", ":id") }}'.replace(':id', id),
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Recargar las tablas y totales
                            actualizarTotales();
                            
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        var message = 'Error al eliminar la deuda por pagar';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                    }
                });
            }
        });
    };
});
</script>

@endsection
