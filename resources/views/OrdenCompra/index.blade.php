@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.0/css/fixedColumns.dataTables.min.css">
@endsection

@section('content')

    @if ($mensaje = Session::get('successcalcular'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'swal("¡Orden de Compra calculada exitosamente!","Proceso realizado de forma correcta","success", {buttons: false,timer: 2000});';
                echo '
            </script>';
        @endphp
    @elseif($mensaje = Session::get('Errorpermisos'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'swal("¡No se encontro el permiso para efectuar la accion!","Comunicate al area de sistemas para validar permisos","warning", {buttons: false,timer: 5000});';
                echo '
            </script>';
        @endphp
    @elseif($mensaje = Session::get('errorFechas'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'swal("¡Estas fechas no son validas!","La fecha ya se encuentra en sistema","warning", {buttons: false,timer: 5000});';
                echo '
            </script>';
        @endphp
    @elseif($mensaje = Session::get('warningBD'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'swal("¡Fallo la acción!","Contacte a un superior para ver el error","warning", {buttons: false,timer: 4000});';
                echo '
            </script>';
        @endphp
    @elseif($mensaje = Session::get('successgenordencompra'))
        @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "success",title: "Exito!", text: "'.$mensaje.'."});';
            echo '</script>';
        @endphp
    @elseif($mensaje = Session::get('errorgenordencompra'))
        @php
            echo '<script language="JavaScript">';
            echo 'Swal.fire({';
            echo '  icon: "error",';
            echo '  title: "Error al generar la orden de compra",';
            echo '  text: "'.$mensaje.'",';
            echo '  confirmButtonText: "Entendido",';
            echo '  confirmButtonColor: "#d33",';
            echo '  showClass: {';
            echo '    popup: "animate__animated animate__fadeInDown"';
            echo '  },';
            echo '  hideClass: {';
            echo '    popup: "animate__animated animate__fadeOutUp"';
            echo '  }';
            echo '});';
            echo '</script>';
        @endphp
    @endif

    @if(session('success_msg'))
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: @json(session('success_msg')), showConfirmButton: false, timer: 6000 });
        });
        </script>
    @endif
    @if(session('warning_msg'))
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({ icon: 'warning', title: 'Atención', text: @json(session('warning_msg')) });
        });
        </script>
    @endif

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <style>
        .oc-list-page .ordenes-table-meta {
            font-size: 0.82rem;
            color: var(--table-muted, #6b7280);
            margin-bottom: 0.65rem;
        }
        .oc-list-page .ordenes-table-card.table-responsive {
            overflow: visible !important;
        }
        .oc-list-page .ordenes-table-card div.dt-scroll,
        .oc-list-page .ordenes-table-card div.dataTables_scroll {
            border: 0 !important;
            margin: 0 !important;
            position: relative !important;
            width: 100% !important;
        }
        .oc-list-page .ordenes-table-card div.dt-scroll-body,
        .oc-list-page .ordenes-table-card div.dataTables_scrollBody {
            overflow-x: auto !important;
            overflow-y: auto !important;
            width: 100% !important;
        }
        .oc-list-page .modern-table tbody tr:hover {
            transform: none;
        }
        body.oc-list-dt table.dataTable tr > .dtfc-fixed-start,
        body.oc-list-dt table.dataTable tr > .dtfc-fixed-end {
            position: sticky !important;
            background-clip: padding-box !important;
        }
        body.oc-list-dt table.dataTable thead tr > .dtfc-fixed-start,
        body.oc-list-dt table.dataTable thead tr > .dtfc-fixed-end {
            background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%) !important;
            z-index: 5 !important;
        }
        body.oc-list-dt table.dataTable tbody tr > .dtfc-fixed-start,
        body.oc-list-dt table.dataTable tbody tr > .dtfc-fixed-end {
            background: #fff !important;
            z-index: 4 !important;
        }
        body.oc-list-dt table.dataTable tbody tr:hover > .dtfc-fixed-start,
        body.oc-list-dt table.dataTable tbody tr:hover > .dtfc-fixed-end {
            background: #f3f4f6 !important;
        }
        .oc-list-page .oc-tabla .badge {
            font-size: 0.65rem !important;
            font-weight: 650 !important;
            letter-spacing: 0.01em !important;
            line-height: 1.2 !important;
            padding: 0.2rem 0.42rem !important;
            border-radius: 999px !important;
            white-space: nowrap;
            vertical-align: middle;
        }
        .oc-list-page .oc-avance {
            min-width: 130px;
        }
        .oc-list-page .oc-avance .progress {
            height: 12px;
            border-radius: 999px;
            background: #e2e8f0;
            min-width: 72px;
        }
        .oc-list-page .oc-avance .progress-bar {
            border-radius: 999px;
        }
        .oc-list-page .oc-avance .progress-bar.oc-barra-rojo {
            background-color: #dc3545 !important;
        }
        .oc-list-page .oc-avance .progress-bar.oc-barra-amarillo {
            background-color: #ffc107 !important;
        }
        .oc-list-page .oc-avance .progress-bar.oc-barra-verde {
            background-color: #198754 !important;
        }
        .oc-list-page .oc-avance .avance-pct {
            font-size: 0.9rem;
            font-weight: 700;
            color: #6c757d;
            min-width: 3.2rem;
        }
        .oc-list-page .oc-acciones {
            white-space: nowrap;
        }
        .oc-list-page .oc-acciones .oc-acciones-row {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: nowrap;
            white-space: nowrap;
        }
        .oc-list-page .oc-acciones .btn {
            width: 2.15rem;
            height: 2.15rem;
            padding: 0;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.55rem;
            font-size: 0.85rem;
        }
        .oc-list-page .oc-folio {
            font-size: 0.78rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            white-space: normal;
            word-break: break-word;
        }
        .oc-list-page .oc-nombre {
            min-width: 160px;
            max-width: 240px;
            white-space: normal;
            word-break: break-word;
        }
        .oc-list-page .oc-tabla tr.oc-incompleta > td {
            background-color: #fff8e6 !important;
        }
        .oc-list-page .oc-tabla tr.oc-incompleta:hover > td {
            background-color: #ffefc7 !important;
        }
        body.oc-list-dt table.dataTable tbody tr.oc-incompleta > .dtfc-fixed-start,
        body.oc-list-dt table.dataTable tbody tr.oc-incompleta > .dtfc-fixed-end {
            background: #fff8e6 !important;
        }
        body.oc-list-dt table.dataTable tbody tr.oc-incompleta:hover > .dtfc-fixed-start,
        body.oc-list-dt table.dataTable tbody tr.oc-incompleta:hover > .dtfc-fixed-end {
            background: #ffefc7 !important;
        }
        .oc-list-page .oc-badge-incompleta {
            background: #f59e0b !important;
            color: #1f2937 !important;
        }
        .oc-notif-card {
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }
        .oc-notif-card:hover {
            box-shadow: 0 0.35rem 0.85rem rgba(15, 23, 42, 0.12) !important;
            transform: translateY(-1px);
        }
        .oc-notif-card:focus-visible {
            outline: 2px solid #0d6efd;
            outline-offset: 2px;
        }
        .oc-notif-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.4rem;
            margin-top: 0.75rem;
            padding-top: 0.65rem;
            border-top: 1px solid #eef2f7;
        }
        .oc-notif-actions .oc-notif-btn {
            height: 1.85rem;
            padding: 0 0.55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            border-radius: 0.4rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }
        .oc-notif-actions .oc-notif-btn i {
            font-size: 0.68rem;
        }
        .oc-notif-actions .oc-notif-btn:hover {
            background: #fff;
        }
        .oc-notif-actions .oc-notif-btn-ok:hover {
            color: #15803d;
            border-color: #86efac;
            background: #f0fdf4;
        }
        .oc-notif-actions .oc-notif-btn-danger:hover {
            color: #b91c1c;
            border-color: #fca5a5;
            background: #fef2f2;
        }
        .oc-notif-actions .oc-notif-btn-info:hover {
            color: #0369a1;
            border-color: #7dd3fc;
            background: #f0f9ff;
        }
        .oc-notif-actions .oc-notif-btn-mute:hover {
            color: #334155;
            border-color: #cbd5e1;
            background: #f1f5f9;
        }

        .oc-filters {
            background: var(--table-soft, #f9fafb);
            border: 1px solid var(--table-border, #e5e7eb);
            margin-bottom: 0.85rem;
            padding: 0.55rem 0.75rem !important;
            border-radius: 14px !important;
            box-shadow: none;
        }
        .oc-filters form {
            gap: 0.35rem 0.65rem;
        }
        .oc-filters .filter-field {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex: 0 1 auto;
            max-width: none;
        }
        .oc-filters .form-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--table-muted, #6b7280);
            margin-bottom: 0;
            white-space: nowrap;
        }
        .oc-filters .form-select-sm,
        .oc-filters .form-control-sm {
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
        .oc-filters .form-control-sm[type="date"] {
            min-width: 140px;
            max-width: 160px;
        }
        .oc-filters .btn-sm {
            min-height: 28px;
            padding: 0.15rem 0.55rem;
            font-size: 0.78rem;
        }
        @media (max-width: 768px) {
            .oc-filters .filter-field {
                flex: 1 1 calc(50% - 0.5rem);
            }
            .oc-filters .form-select-sm,
            .oc-filters .form-control-sm {
                flex: 1;
                max-width: none;
            }
        }
    </style>

    <!-- pantalla principal -->
    <div class="container-fluid format_page oc-list-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-cart-shopping"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Órdenes de Compra</h2>
                            <p class="text-muted mb-0">Administración de órdenes de compra de la empresa</p>
                        </div>
                    </div>
                    <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ route('ordcompras.create') }}" class="btn btn-baseColor">
                            <i class="fa-solid fa-plus"></i> Nueva
                        </a>
                        <button class="btn btn-baseColor-light" type="button" data-bs-toggle="modal" data-bs-target="#modalDesdeLicitacion">
                            <i class="fa-solid fa-file-circle-plus"></i> Desde Comparativa
                        </button>

                        @if($permiso2 == 'revision_ordenesCompra')
                        @php
                            $ordenesEnRevision = $varlista->where('estado', 'revision');
                        @endphp
                        @if($ordenesEnRevision->count() > 0)
                            <button type="button" class="btn btn-baseColor position-relative" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNotificaciones" aria-controls="offcanvasNotificaciones">
                                <i class="fa-solid fa-bell me-2"></i>
                                Revisión
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $ordenesEnRevision->count() }}
                                    <span class="visually-hidden">órdenes pendientes</span>
                                </span>
                            </button>
                        @else
                            <button type="button" class="btn btn-baseColor-light" disabled>
                                <i class="fa-regular fa-bell me-2"></i>
                                Revisión (0)
                            </button>
                        @endif
                        @endif

                        @if($permiso1 == 'acargo_ordenesCompra')
                        @php
                            $ordenesAceptadas = $varlista->where('estado', 'aceptado');
                        @endphp
                        @if($ordenesAceptadas->count() > 0)
                            <button type="button" class="btn btn-baseColor position-relative" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNotificacionesAceptadas" aria-controls="offcanvasNotificacionesAceptadas">
                                <i class="fa-solid fa-bell me-2"></i>
                                Aceptadas
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success text-light">
                                    {{ $ordenesAceptadas->count() }}
                                    <span class="visually-hidden">órdenes aceptadas</span>
                                </span>
                            </button>
                        @else
                            <button type="button" class="btn btn-baseColor-light" disabled>
                                <i class="fa-regular fa-bell me-2"></i>
                                Aceptadas (0)
                            </button>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

  

        <!-- Dashboard de Órdenes de Compra -->
        <div class="row g-3 compras-kpi-row">
            <div class="col-6 col-md-3">
                <div class="compras-kpi compras-kpi--warning">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">En revisión</span>
                        <span class="compras-kpi__value">{{ $varlista->where('estado', 'revision')->count() }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="compras-kpi compras-kpi--success">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Aceptadas</span>
                        <span class="compras-kpi__value">{{ $varlista->where('estado', 'aceptado')->count() }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="compras-kpi compras-kpi--info">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Enviadas</span>
                        <span class="compras-kpi__value">{{ $varlista->where('estado', 'enviado_proveedor')->count() }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-paper-plane"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="compras-kpi compras-kpi--secondary">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Cerradas / Rechazadas</span>
                        <span class="compras-kpi__value">{{ $varlista->whereIn('estado', ['cerrada', 'rechazado'])->count() }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-flag-checkered"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- tabla de Ordenes de Compra principal  --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-orange">Listado de Órdenes de Compra</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive ordenes-table-card">
                    <div class="filters oc-filters">
                        <form id="filtroOrdenes" class="d-flex flex-wrap align-items-center">
                            <div class="filter-field">
                                <label class="form-label" for="filtroEstado">Estado</label>
                                <select class="form-select form-select-sm" id="filtroEstado" aria-label="Filtrar por estado">
                                    <option value="">Todos</option>
                                    @foreach(App\Models\OrdenCompra::$estados as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-field">
                                <label class="form-label" for="filtroProveedor">Proveedor</label>
                                <select class="form-select form-select-sm" id="filtroProveedor" aria-label="Filtrar por proveedor">
                                    <option value="">Todos</option>
                                    @php
                                        $proveedores = $varlista->pluck('proveedor.nombre', 'proveedor_id')->unique();
                                    @endphp
                                    @foreach($proveedores as $id => $nombre)
                                        @if($id && $nombre)
                                            <option value="{{ $id }}">{{ $nombre }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-field">
                                <label class="form-label" for="filtroFechaDesde">Desde</label>
                                <input type="date" class="form-control form-control-sm" id="filtroFechaDesde" aria-label="Fecha desde">
                            </div>
                            <div class="filter-field">
                                <label class="form-label" for="filtroFechaHasta">Hasta</label>
                                <input type="date" class="form-control form-control-sm" id="filtroFechaHasta" aria-label="Fecha hasta">
                            </div>
                            <button type="button" class="btn btn-baseColor-light btn-sm flex-shrink-0" id="aplicarFiltros">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" id="limpiarFiltros">
                                Limpiar
                            </button>
                        </form>
                    </div>

                    <div class="d-flex justify-content-between align-items-center ordenes-table-meta">
                        <span>
                            <i class="fa-solid fa-list me-1"></i>
                            Órdenes de compra ({{ $varlista->count() }})
                        </span>
                    </div>
                    <table class="table table-striped table-hover display modern-table w-100 oc-tabla" id="tableOC">
                        <thead>
                            <tr>
                                <th class="text-center">Acciones</th>
                                <th>Folio</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Progreso</th>
                                <th>No.</th>
                                <th>Proveedor</th>
                                <th>Fecha Creación</th>
                                <th>Fecha Límite</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($varlista as $vis)
                                @php
                                    $badgeClass = 'secondary';
                                    $estadoTexto = $vis->estado_legible ?? ucfirst($vis->estado);
                                    $datosIncompletos = empty($vis->proveedor_id) || empty($vis->persona_atencion_id);

                                    switch($vis->estado) {
                                        case 'borrador': $badgeClass = 'secondary'; break;
                                        case 'revision': $badgeClass = 'warning'; break;
                                        case 'aceptado': $badgeClass = 'success'; break;
                                        case 'rechazado': $badgeClass = 'danger'; break;
                                        case 'enviado_proveedor': $badgeClass = 'info'; break;
                                        case 'cerrada': $badgeClass = 'dark'; break;
                                    }

                                    $tieneFactura = false;
                                    if ($vis->referencia_licitacion_id) {
                                        $tieneFactura = DB::table('tblfacturas_xml')
                                            ->where('licitacion_id', $vis->referencia_licitacion_id)
                                            ->exists();
                                    }

                                    // Progreso según estado del flujo de la orden
                                    switch ($vis->estado) {
                                        case 'revision':
                                        case 'aceptado':
                                            $porcentaje = 25;
                                            break;
                                        case 'enviado_proveedor':
                                            $porcentaje = 50;
                                            break;
                                        case 'cerrada':
                                            $porcentaje = 100;
                                            break;
                                        case 'borrador':
                                        case 'rechazado':
                                        default:
                                            $porcentaje = 0;
                                            break;
                                    }
                                @endphp
                                <tr class="{{ $datosIncompletos ? 'oc-incompleta' : '' }}">
                                    <td class="text-center oc-acciones td-actions">
                                        <div class="oc-acciones-row">
                                            {{-- <a class="btn btn-info btn-sm" title="Ver Orden de Compra" href="{{ route('ordcompras.show', $vis->id) }}">
                                                <i class="fa-solid fa-eye"></i>
                                            </a> --}}

                                            @if ($vis->estado == 'cerrada')
                                                <button type="button" class="btn btn-primary btn-sm" title="No se puede editar: orden cerrada" disabled>
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning btn-sm" title="No se puede cambiar estado: orden cerrada" disabled>
                                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                                </button>
                                            @else
                                                <a class="btn btn-primary btn-sm" title="{{ $datosIncompletos ? 'Completar datos de la Orden de Compra' : 'Editar Orden de Compra' }}" href="{{ route('ordcompras.editar', $vis->id) }}">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>

                                                @if($vis->estado == 'revision' && auth()->user()->permiso2 != 'revision_ordenesCompra')
                                                    <button type="button" class="btn btn-secondary btn-sm"
                                                            title="No tienes permisos para cambiar este estado" disabled>
                                                        <i class="fa-solid fa-lock"></i>
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-warning btn-sm"
                                                            data-bs-toggle="modal" data-bs-target="#cambiarEstadoModal"
                                                            data-id="{{ $vis->id }}" data-estado="{{ $vis->estado }}"
                                                            title="Cambiar Estado">
                                                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                                    </button>
                                                @endif
                                            @endif

                                            @if ($vis->referencia_licitacion_id)
                                                <button type="button" class="btn btn-success btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#comparativaModal"
                                                    data-id="{{ $vis->id }}"
                                                    data-licitacion="{{ $vis->referencia_licitacion_id }}"
                                                    title="Comparar Licitación vs Factura">
                                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                                </button>
                                            @endif

                                            <a class="btn btn-secondary btn-sm"
                                               href="{{ route('ordcompras.pdf', $vis->id) }}"
                                               target="_blank"
                                               title="Generar Reporte PDF">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </a>

                                            @if ($vis->estado == 'cerrada')
                                                <button type="button"
                                                    class="btn btn-danger btn-sm"
                                                    title="No se puede eliminar: orden cerrada"
                                                    disabled>
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            @else
                                                <button type="button"
                                                    class="btn btn-danger btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#cancelModal"
                                                    data-id="{{ $vis->id }}"
                                                    title="Eliminar Orden de Compra">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="oc-folio" title="{{ $vis->folio }}">{{ $vis->folio }}</div>
                                        @if ($datosIncompletos)
                                            <span class="badge oc-badge-incompleta mt-1" title="Falta proveedor y/o persona de atención">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i>Por completar
                                            </span>
                                        @endif
                                    </td>
                                    <td class="oc-nombre" title="{{ $vis->nombre }}">{{ $vis->nombre }}</td>
                                    <td>
                                        <span class="badge bg-{{ $badgeClass }}">
                                            {{ $estadoTexto }}
                                        </span>

                                        @if($tieneFactura)
                                            <span class="badge bg-success ms-1" title="El proveedor ha enviado factura">
                                                <i class="fa-solid fa-file-invoice"></i> Con Factura
                                            </span>
                                        @endif
                                    </td>
                                    <td class="oc-avance" data-order="{{ $porcentaje }}">
                                        @php
                                            // Semáforo: rojo (inicio), amarillo (en proceso), verde (cerrada)
                                            if ($porcentaje >= 100) {
                                                $barraClass = 'oc-barra-verde';
                                            } elseif ($porcentaje > 0) {
                                                $barraClass = 'oc-barra-amarillo';
                                            } else {
                                                $barraClass = 'oc-barra-rojo';
                                            }
                                        @endphp
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar {{ $barraClass }}"
                                                     role="progressbar" style="width: {{ $porcentaje > 0 ? $porcentaje : 8 }}%"
                                                     aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="avance-pct">{{ $porcentaje }}%</span>
                                        </div>
                                    </td>
                                    <td>{{ $vis->id }}</td>
                                    <td>{{ $vis->proveedor->nombre ?? 'Sin proveedor' }}</td>
                                    <td data-order="{{ $vis->fecha_creacion }}">{{ \Carbon\Carbon::parse($vis->fecha_creacion)->format('d/m/Y') }}</td>
                                    <td data-order="{{ $vis->fecha_limite }}">{{ \Carbon\Carbon::parse($vis->fecha_limite)->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal preguntar eliminado-->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="modalDeleteForm">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title text-dark fs-5" id="exampleModalLabel">Eliminar Orden de Compra</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <h6 class="text-dark">¿Está seguro que desea eliminar esta Orden de Compra?</h6>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fs-8" data-bs-dismiss="modal"> <i class="fa-solid fa-xmark"></i> Cancelar</button>
                        <button type="submit" class="btn btn-danger fs-8">Eliminar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Cambiar Estado -->
    <div class="modal fade" id="cambiarEstadoModal" tabindex="-1" aria-labelledby="cambiarEstadoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formCambiarEstado">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cambiarEstadoModalLabel">Cambiar Estado de la Orden</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="estado" class="form-label">Nuevo Estado</label>
                            <select class="form-select" id="estadoSelect" name="estado" required>
                                <option value="">-- Selecciona un estado --</option>
                                <!-- Los estados disponibles se cargarán dinámicamente con JavaScript -->
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambio</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Nueva OC desde Licitación -->
    <div class="modal fade" id="modalDesdeLicitacion" tabindex="-1" aria-labelledby="modalDesdeLicitacionLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('ordcompras.generardesdelicitacion') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalDesdeLicitacionLabel">Generar Orden de Compra desde Comparativa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Al generar una orden de compra desde una comparativa, se aplicarán los precios y condiciones acordados con el proveedor adjudicado.
                            <hr>
                            <p class="mb-0"><strong>Requisitos:</strong></p>
                            <ul class="mb-0">
                                <li>La comparativa debe estar en estado "adjudicada"</li>
                                <li>Debe tener un proveedor adjudicado válido</li>
                                <li>Todos los productos deben tener precios asignados por el proveedor adjudicado</li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <label for="licitacion_id" class="form-label">Seleccionar comparativa</label>
                            <select class="form-select" id="licitacion_id" name="licitacion_id" required>
                                <option value="">-- Selecciona una comparativa --</option>
                                @foreach ($licitacionesDisponibles as $licitacion)
                                    <option value="{{ $licitacion->id }}">
                                        {{ $licitacion->id }} - {{ $licitacion->nombre }} 
                                        (Proveedor: {{ DB::table('tblprovedores')->where('id', $licitacion->proveedor_adjudicado_id)->value('nombre') ?? 'No encontrado' }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="valid-feedback">¡Se ve bien! </div>
                            <div class="invalid-feedback"> Por favor, completa la información requerida. </div>
                        </div>
                        
                        <div id="licitacionDetails" class="mt-4 d-none">
                            <h6 class="mb-3">Detalles de la licitación seleccionada</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nombre:</strong> <span id="licitacionNombre"></span></p>
                                    <p><strong>Fecha creación:</strong> <span id="licitacionFechaCreacion"></span></p>
                                    <p><strong>Fecha límite:</strong> <span id="licitacionFechaLimite"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Proveedor adjudicado:</strong> <span id="licitacionProveedor"></span></p>
                                    <p><strong>Cantidad de productos:</strong> <span id="licitacionProductos"></span></p>
                                    <p><strong>Total aproximado:</strong> <span id="licitacionTotal"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-file-invoice"></i> Generar Orden de Compra
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Comparativa Licitación vs Factura -->
    <div class="modal fade" id="comparativaModal" tabindex="-1" aria-labelledby="comparativaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="comparativaModalLabel">Comparativa: Licitación vs Factura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center" id="comparativaLoader">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2">Cargando información comparativa...</p>
                    </div>
                    
                    <div id="comparativaContent" class="d-none">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">Información de Licitación</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>No. Comparativa:</strong> <span id="licitacionId"></span></p>
                                                <p><strong>Nombre:</strong> <span id="compLicitacionNombre"></span></p>
                                                <p><strong>Fecha:</strong> <span id="compLicitacionFecha"></span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Proveedor:</strong> <span id="compLicitacionProveedor"></span></p>
                                                <p><strong>Subtotal:</strong> <span id="compLicitacionSubtotal"></span></p>
                                                <p><strong>Total:</strong> <span id="compLicitacionTotal"></span></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">Información de Factura</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row" id="facturaInfo">
                                            <div class="col-md-6">
                                                <p><strong>UUID:</strong> <span id="facturaUUID"></span></p>
                                                <p><strong>Emisor:</strong> <span id="facturaEmisor"></span></p>
                                                <p><strong>Fecha:</strong> <span id="facturaFecha"></span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>RFC Emisor:</strong> <span id="facturaRfcEmisor"></span></p>
                                                <p><strong>Subtotal:</strong> <span id="facturaSubtotal"></span></p>
                                                <p><strong>Total:</strong> <span id="facturaTotal"></span></p>
                                            </div>
                                        </div>
                                        <div id="sinFacturaInfo" class="d-none">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle"></i> No se encontraron facturas relacionadas con esta licitación.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Tabla Comparativa de Montos</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="tablaComparativa">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Concepto</th>
                                                <th class="text-end">Licitación</th>
                                                <th class="text-end">Factura</th>
                                                <th class="text-end">Diferencia</th>
                                                <th class="text-end">Diferencia %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Subtotal</td>
                                                <td class="text-end" id="compSubtotalLic"></td>
                                                <td class="text-end" id="compSubtotalFac"></td>
                                                <td class="text-end" id="compSubtotalDif"></td>
                                                <td class="text-end" id="compSubtotalPct"></td>
                                            </tr>
                                            <tr>
                                                <td>IVA</td>
                                                <td class="text-end" id="compIvaLic">Calculado (16%)</td>
                                                <td class="text-end" id="compIvaFac"></td>
                                                <td class="text-end" id="compIvaDif"></td>
                                                <td class="text-end" id="compIvaPct"></td>
                                            </tr>
                                            <tr class="fw-bold table-secondary">
                                                <td>Total</td>
                                                <td class="text-end" id="compTotalLic"></td>
                                                <td class="text-end" id="compTotalFac"></td>
                                                <td class="text-end" id="compTotalDif"></td>
                                                <td class="text-end" id="compTotalPct"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnImprimirComparativa">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Offcanvas de Notificaciones de Revisión -->
    @if($permiso2 == 'revision_ordenesCompra' && $ordenesEnRevision->count() > 0)
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNotificaciones" aria-labelledby="offcanvasNotificacionesLabel">
        <div class="offcanvas-header bg-opacity-10">
            <h5 class="offcanvas-title text-orange" id="offcanvasNotificacionesLabel">
                <i class="fa-solid fa-bell me-2"></i>
                Pendientes de Revisión
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body">
            <div class="alert alert-info fs-9">
                <i class="fa-solid fa-info-circle me-2"></i>
                Tienes <strong>{{ $ordenesEnRevision->count() }}</strong> orden(es) de compra esperando tu decisión de aprobación o rechazo.
            </div>
            
            <div class="list-group">
                @foreach($ordenesEnRevision as $orden)
                <div class="card p-3 mb-4 shadow-sm oc-notif-card"
                     role="link"
                     tabindex="0"
                     title="Ver / Editar orden"
                     onclick="window.location.href='{{ route('ordcompras.editar', $orden->id) }}'"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='{{ route('ordcompras.editar', $orden->id) }}';}"
                     style="cursor:pointer;">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <span class="badge bg-secondary me-2">{{ $orden->folio }}</span> <br><br>
                                {{ Str::limit($orden->nombre, 40) }}
                            </h6>
                            <p class="mb-1 text-muted">
                                <i class="fa-solid fa-building me-1"></i>
                                {{ $orden->proveedor->nombre ?? 'Sin proveedor' }}
                            </p>
                            <small class="text-muted">
                                <i class="fa-solid fa-calendar me-1"></i>
                                Creada: {{ \Carbon\Carbon::parse($orden->fecha_creacion)->format('d/m/Y') }}
                                <br>
                                <i class="fa-solid fa-clock me-1"></i>
                                Límite: {{ \Carbon\Carbon::parse($orden->fecha_limite)->format('d/m/Y') }}
                            </small>
                        </div>
                    </div>
                    
                    <div class="oc-notif-actions" onclick="event.stopPropagation();">
                        <button type="button"
                                class="oc-notif-btn oc-notif-btn-ok"
                                onclick="cambiarEstadoRapido({{ $orden->id }}, 'aceptado')"
                                title="Aprobar Orden">
                            <i class="fa-solid fa-check"></i>
                            Aprobar
                        </button>
                        <button type="button"
                                class="oc-notif-btn oc-notif-btn-danger"
                                onclick="cambiarEstadoRapido({{ $orden->id }}, 'rechazado')"
                                title="Rechazar Orden">
                            <i class="fa-solid fa-times"></i>
                            Rechazar
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="mt-4 text-center">
                <small class="text-muted">
                    <i class="fa-solid fa-lightbulb me-1"></i>
                    Tip: Puedes hacer clic en los botones de acción para aprobar o rechazar rápidamente las órdenes.
                </small>
            </div>
        </div>
    </div>
    @endif

    <!-- Offcanvas de Notificaciones de Órdenes Aceptadas -->
    @if($permiso1 == 'acargo_ordenesCompra' && $ordenesAceptadas->count() > 0)
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNotificacionesAceptadas" aria-labelledby="offcanvasNotificacionesAceptadasLabel">
        <div class="offcanvas-header bg-success bg-opacity-10">
            <h5 class="offcanvas-title text-success" id="offcanvasNotificacionesAceptadasLabel">
                <i class="fa-solid fa-check-circle me-2"></i>
                Órdenes Aceptadas
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body">
            <div class="alert alert-success fs-9">
                <i class="fa-solid fa-info-circle me-2"></i>
                Tienes <strong>{{ $ordenesAceptadas->count() }}</strong> orden(es) de compra aceptadas que requieren tu atención para enviar al proveedor o cerrar.
            </div>
            
            <div class="list-group">
                @foreach($ordenesAceptadas as $orden)
                <div class="card p-3 mb-4 shadow-sm oc-notif-card"
                     role="link"
                     tabindex="0"
                     title="Ver / Editar orden"
                     onclick="window.location.href='{{ route('ordcompras.editar', $orden->id) }}'"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='{{ route('ordcompras.editar', $orden->id) }}';}"
                     style="cursor:pointer;">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <span class="badge bg-success me-2">{{ $orden->folio }}</span> <br><br>
                                {{ Str::limit($orden->nombre, 40) }}
                            </h6>
                            <p class="mb-1 text-muted">
                                <i class="fa-solid fa-building me-1"></i>
                                {{ $orden->proveedor->nombre ?? 'Sin proveedor' }}
                            </p>
                            <small class="text-muted">
                                <i class="fa-solid fa-calendar me-1"></i>
                                Creada: {{ \Carbon\Carbon::parse($orden->fecha_creacion)->format('d/m/Y') }}
                                <br>
                                <i class="fa-solid fa-clock me-1"></i>
                                Límite: {{ \Carbon\Carbon::parse($orden->fecha_limite)->format('d/m/Y') }}
                            </small>
                        </div>
                    </div>
                    
                    <div class="oc-notif-actions" onclick="event.stopPropagation();">
                        <button type="button"
                                class="oc-notif-btn oc-notif-btn-info"
                                onclick="cambiarEstadoRapido({{ $orden->id }}, 'enviado_proveedor')"
                                title="Enviar al Proveedor">
                            <i class="fa-solid fa-paper-plane"></i>
                            Enviar
                        </button>
                        <button type="button"
                                class="oc-notif-btn oc-notif-btn-mute"
                                onclick="cambiarEstadoRapido({{ $orden->id }}, 'cerrada')"
                                title="Cerrar Orden">
                            <i class="fa-solid fa-lock"></i>
                            Cerrar
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="mt-4 text-center">
                <small class="text-muted">
                    <i class="fa-solid fa-lightbulb me-1"></i>
                    Tip: Puedes enviar las órdenes al proveedor o cerrarlas directamente desde aquí.
                </small>
            </div>
        </div>
    </div>
    @endif

    <!-- Scripts para los modales y filtros -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal de eliminación
            document.getElementById('cancelModal').addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const form = document.getElementById('modalDeleteForm');
                form.action = `/OrdenCompras/destroy/${id}`;
            });
            
            // Modal de cambio de estado
            document.getElementById('cambiarEstadoModal').addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const estadoActual = button.getAttribute('data-estado');
                const form = document.getElementById('formCambiarEstado');
                const selectEstado = document.getElementById('estadoSelect');
                
                // Limpiar opciones anteriores
                selectEstado.innerHTML = '<option value="">-- Selecciona un estado --</option>';
                
                // Obtener estados permitidos según el estado actual
                const estadosPermitidos = obtenerEstadosPermitidos(estadoActual);
                
                // Poblar el select con los estados permitidos
                if (estadosPermitidos.length > 0) {
                    estadosPermitidos.forEach(estado => {
                        const option = document.createElement('option');
                        option.value = estado.valor;
                        option.textContent = estado.texto;
                        selectEstado.appendChild(option);
                    });
                } else {
                    // Si no hay estados disponibles, mostrar mensaje
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No tienes permisos para cambiar este estado';
                    option.disabled = true;
                    selectEstado.appendChild(option);
                }
                
                form.action = `/OrdenCompras/cambiarEstado/${id}`;
            });
            
            // Modal de comparativa
            if (document.getElementById('comparativaModal')) {
                document.getElementById('comparativaModal').addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const ordenId = button.getAttribute('data-id');
                    const licitacionId = button.getAttribute('data-licitacion');
                    
                    // Mostrar loader y ocultar contenido
                    document.getElementById('comparativaLoader').classList.remove('d-none');
                    document.getElementById('comparativaContent').classList.add('d-none');
                    
                    // Realizar la petición para obtener los datos comparativos
                    fetch(`/api/comparativa-licitacion-factura/${licitacionId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error al cargar los datos comparativos');
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Llenar datos de la licitación
                            document.getElementById('licitacionId').textContent = data.licitacion.id;
                            document.getElementById('compLicitacionNombre').textContent = data.licitacion.nombre;
                            document.getElementById('compLicitacionFecha').textContent = data.licitacion.fecha_creacion;
                            document.getElementById('compLicitacionProveedor').textContent = data.licitacion.proveedor_nombre;
                            
                            const subtotalLic = parseFloat(data.licitacion.subtotal);
                            const totalLic = parseFloat(data.licitacion.total);
                            
                            document.getElementById('compLicitacionSubtotal').textContent = formatCurrency(subtotalLic);
                            document.getElementById('compLicitacionTotal').textContent = formatCurrency(totalLic);
                            
                            // Verificar si hay factura
                            if (data.factura) {
                                document.getElementById('facturaInfo').classList.remove('d-none');
                                document.getElementById('sinFacturaInfo').classList.add('d-none');
                                
                                document.getElementById('facturaUUID').textContent = data.factura.uuid;
                                document.getElementById('facturaEmisor').textContent = data.factura.nombre_emisor;
                                document.getElementById('facturaFecha').textContent = data.factura.fecha;
                                document.getElementById('facturaRfcEmisor').textContent = data.factura.rfc_emisor;
                                
                                const subtotalFac = parseFloat(data.factura.subtotal);
                                const totalFac = parseFloat(data.factura.total);
                                
                                document.getElementById('facturaSubtotal').textContent = formatCurrency(subtotalFac);
                                document.getElementById('facturaTotal').textContent = formatCurrency(totalFac);
                                
                                // Calcular diferencias
                                const difSubtotal = subtotalFac - subtotalLic;
                                const difTotal = totalFac - totalLic;
                                const pctSubtotal = (difSubtotal / subtotalLic) * 100;
                                const pctTotal = (difTotal / totalLic) * 100;
                                
                                // Calcular IVA
                                const ivaLic = totalLic - subtotalLic;
                                const ivaFac = totalFac - subtotalFac;
                                const difIva = ivaFac - ivaLic;
                                const pctIva = (difIva / ivaLic) * 100;
                                
                                // Actualizar tabla comparativa
                                document.getElementById('compSubtotalLic').textContent = formatCurrency(subtotalLic);
                                document.getElementById('compSubtotalFac').textContent = formatCurrency(subtotalFac);
                                document.getElementById('compSubtotalDif').textContent = formatCurrency(difSubtotal);
                                document.getElementById('compSubtotalPct').textContent = formatPercent(pctSubtotal);
                                
                                document.getElementById('compIvaLic').textContent = formatCurrency(ivaLic);
                                document.getElementById('compIvaFac').textContent = formatCurrency(ivaFac);
                                document.getElementById('compIvaDif').textContent = formatCurrency(difIva);
                                document.getElementById('compIvaPct').textContent = formatPercent(pctIva);
                                
                                document.getElementById('compTotalLic').textContent = formatCurrency(totalLic);
                                document.getElementById('compTotalFac').textContent = formatCurrency(totalFac);
                                document.getElementById('compTotalDif').textContent = formatCurrency(difTotal);
                                document.getElementById('compTotalPct').textContent = formatPercent(pctTotal);
                                
                                // Colorear las diferencias
                                colorearDiferencias('compSubtotalDif', 'compSubtotalPct', difSubtotal);
                                colorearDiferencias('compIvaDif', 'compIvaPct', difIva);
                                colorearDiferencias('compTotalDif', 'compTotalPct', difTotal);
                            } else {
                                document.getElementById('facturaInfo').classList.add('d-none');
                                document.getElementById('sinFacturaInfo').classList.remove('d-none');
                                
                                // Limpiar tabla comparativa
                                document.getElementById('compSubtotalFac').textContent = 'N/A';
                                document.getElementById('compSubtotalDif').textContent = 'N/A';
                                document.getElementById('compSubtotalPct').textContent = 'N/A';
                                
                                document.getElementById('compIvaFac').textContent = 'N/A';
                                document.getElementById('compIvaDif').textContent = 'N/A';
                                document.getElementById('compIvaPct').textContent = 'N/A';
                                
                                document.getElementById('compTotalFac').textContent = 'N/A';
                                document.getElementById('compTotalDif').textContent = 'N/A';
                                document.getElementById('compTotalPct').textContent = 'N/A';
                            }
                            
                            // Ocultar loader y mostrar contenido
                            document.getElementById('comparativaLoader').classList.add('d-none');
                            document.getElementById('comparativaContent').classList.remove('d-none');
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            
                            // Mostrar mensaje de error
                            document.getElementById('comparativaLoader').innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle"></i> 
                                    Error al cargar los datos comparativos: ${error.message}
                                </div>
                            `;
                        });
                });
                
                // Botón de imprimir
                document.getElementById('btnImprimirComparativa').addEventListener('click', function() {
                    const contenido = document.getElementById('comparativaContent').innerHTML;
                    const ventanaImpresion = window.open('', '_blank');
                    
                    ventanaImpresion.document.write(`
                        <html>
                            <head>
                                <title>Comparativa: Licitación vs Factura</title>
                                <link href="{{ asset('bootstrap-5.3.3/css/bootstrap.min.css') }}" rel="stylesheet">
                                <style>
                                    body { padding: 20px; }
                                    @media print {
                                        .no-print { display: none; }
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="container">
                                    <h2 class="mb-4 text-center">Comparativa: Licitación vs Factura</h2>
                                    ${contenido}
                                    <div class="mt-4 text-center no-print">
                                        <button class="btn btn-primary" onclick="window.print()">Imprimir</button>
                                        <button class="btn btn-secondary" onclick="window.close()">Cerrar</button>
                                    </div>
                                </div>
                            </body>
                        </html>
                    `);
                    
                    ventanaImpresion.document.close();
                });
            }
            
            // Carga dinámica de detalles de licitación
            const selectLicitacion = document.getElementById('licitacion_id');
            if (selectLicitacion) {
                selectLicitacion.addEventListener('change', function() {
                    const licitacionId = this.value;
                    const licitacionDetails = document.getElementById('licitacionDetails');
                    
                    if (!licitacionId) {
                        licitacionDetails.classList.add('d-none');
                        return;
                    }
                    
                    // Solicitar detalles de la licitación vía fetch
                    fetch(`/api/licitaciones/${licitacionId}/detalles`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('No se pudieron cargar los detalles de la licitación');
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Llenar los detalles
                            document.getElementById('licitacionNombre').textContent = data.nombre || 'No disponible';
                            document.getElementById('licitacionFechaCreacion').textContent = data.fecha_creacion || 'No disponible';
                            document.getElementById('licitacionFechaLimite').textContent = data.fecha_limite || 'No disponible';
                            document.getElementById('licitacionProveedor').textContent = data.proveedor?.nombre || 'No disponible';
                            document.getElementById('licitacionProductos').textContent = data.productos_count || '0';
                            document.getElementById('licitacionTotal').textContent = `$${data.total_aproximado || '0.00'}`;
                            
                            // Mostrar el div de detalles
                            licitacionDetails.classList.remove('d-none');
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Si hay error, rellenar con datos de la opción seleccionada
                            const selectedOption = selectLicitacion.options[selectLicitacion.selectedIndex];
                            document.getElementById('licitacionNombre').textContent = selectedOption.textContent.split(' - ')[1] || 'No disponible';
                            
                            // Para los demás campos, mostrar mensaje de carga fallida
                            const fallbackMessage = 'Carga fallida';
                            document.getElementById('licitacionFechaCreacion').textContent = fallbackMessage;
                            document.getElementById('licitacionFechaLimite').textContent = fallbackMessage;
                            document.getElementById('licitacionProveedor').textContent = selectedOption.textContent.includes('Proveedor:') 
                                ? selectedOption.textContent.split('Proveedor: ')[1].replace(')', '') 
                                : fallbackMessage;
                            document.getElementById('licitacionProductos').textContent = fallbackMessage;
                            document.getElementById('licitacionTotal').textContent = fallbackMessage;
                            
                            // Mostrar el div de detalles aunque haya fallado la carga
                            licitacionDetails.classList.remove('d-none');
                        });
                });
            }
            
            // Filtros
            document.getElementById('aplicarFiltros').addEventListener('click', function() {
                const dataTable = window.ocDataTable;
                if (!dataTable) return;

                const estadoSelect = document.getElementById('filtroEstado');
                const proveedorSelect = document.getElementById('filtroProveedor');
                const estadoTexto = estadoSelect.options[estadoSelect.selectedIndex]?.text || '';
                const proveedorTexto = proveedorSelect.options[proveedorSelect.selectedIndex]?.text || '';
                const estado = estadoSelect.value ? estadoTexto : '';
                const proveedor = proveedorSelect.value ? proveedorTexto : '';

                // Columnas: 3 Estado, 6 Proveedor
                dataTable.column(3).search(estado, true, false);
                dataTable.column(6).search(proveedor, true, false);
                dataTable.draw();
            });
            
            document.getElementById('limpiarFiltros').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('filtroEstado').value = '';
                document.getElementById('filtroProveedor').value = '';
                document.getElementById('filtroFechaDesde').value = '';
                document.getElementById('filtroFechaHasta').value = '';
                
                const dataTable = window.ocDataTable;
                if (dataTable) {
                    dataTable.search('').columns().search('').draw();
                }
            });
            
            // Función para obtener estados permitidos según el estado actual y permisos del usuario
            function obtenerEstadosPermitidos(estadoActual) {
                // Esta información debería venir del backend, pero por simplicidad lo definimos aquí
                const cambiosPermitidos = {
                    'borrador': ['revision'],
                    'revision': ['aceptado', 'rechazado'],
                    'aceptado': ['enviado_proveedor', 'cerrada'],
                    'rechazado': ['borrador'],
                    'enviado_proveedor': ['cerrada'],
                    'cerrada': []
                };
                
                const estadosLegibles = {
                    'borrador': 'Borrador',
                    'revision': 'Revisión',
                    'aceptado': 'Aceptado',
                    'rechazado': 'Rechazado',
                    'enviado_proveedor': 'Enviado al Proveedor',
                    'cerrada': 'Cerrada'
                };
                
                // Obtener permisos del usuario desde el backend (asumiendo que está disponible en el frontend)
                const tienePermisoRevision = '{{ auth()->user()->permiso2 ?? "" }}' === 'revision_ordenesCompra';
                
                let estadosDisponibles = cambiosPermitidos[estadoActual] || [];
                
                // Si el estado actual es 'revision' y el usuario no tiene permiso de revisión,
                // no puede cambiar a 'aceptado' o 'rechazado'
                if (estadoActual === 'revision' && !tienePermisoRevision) {
                    estadosDisponibles = [];
                }
                
                return estadosDisponibles.map(estado => ({
                    valor: estado,
                    texto: estadosLegibles[estado] || estado
                }));
            }
            
            // Función para formatear valores monetarios
            function formatCurrency(value) {
                return new Intl.NumberFormat('es-MX', {
                    style: 'currency',
                    currency: 'MXN'
                }).format(value);
            }
            
            // Función para formatear porcentajes
            function formatPercent(value) {
                return new Intl.NumberFormat('es-MX', {
                    style: 'percent',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(value / 100);
            }
            
            // Función para colorear las diferencias según su valor
            function colorearDiferencias(idDif, idPct, valor) {
                const elemDif = document.getElementById(idDif);
                const elemPct = document.getElementById(idPct);
                
                if (valor > 0) {
                    // Si la factura es mayor que la licitación (diferencia positiva)
                    elemDif.classList.add('text-danger');
                    elemPct.classList.add('text-danger');
                } else if (valor < 0) {
                    // Si la factura es menor que la licitación (diferencia negativa)
                    elemDif.classList.add('text-success');
                    elemPct.classList.add('text-success');
                } else {
                    // Si son iguales
                    elemDif.classList.add('text-secondary');
                    elemPct.classList.add('text-secondary');
                }
            }
            
        });
    </script>
    
    <!-- Función global para cambiar estado rápidamente -->
    <script>
        // Función para cambiar estado rápidamente desde el centro de notificaciones
        function cambiarEstadoRapido(ordenId, nuevoEstado) {
            // Configurar mensajes según el estado
            let estadoTexto, icono, titulo, mensaje, confirmButtonColor, confirmButtonText;
            
            switch(nuevoEstado) {
                case 'aceptado':
                    estadoTexto = 'aceptada';
                    icono = 'success';
                    titulo = 'Aprobar Orden';
                    mensaje = '¿Estás seguro de que deseas aprobar esta orden de compra?';
                    confirmButtonColor = '#28a745';
                    confirmButtonText = 'Sí, aprobar';
                    break;
                case 'rechazado':
                    estadoTexto = 'rechazada';
                    icono = 'warning';
                    titulo = 'Rechazar Orden';
                    mensaje = '¿Estás seguro de que deseas rechazar esta orden de compra?';
                    confirmButtonColor = '#dc3545';
                    confirmButtonText = 'Sí, rechazar';
                    break;
                case 'enviado_proveedor':
                    estadoTexto = 'enviada al proveedor';
                    icono = 'info';
                    titulo = 'Enviar al Proveedor';
                    mensaje = '¿Estás seguro de que deseas enviar esta orden al proveedor?';
                    confirmButtonColor = '#17a2b8';
                    confirmButtonText = 'Sí, enviar';
                    break;
                case 'cerrada':
                    estadoTexto = 'cerrada';
                    icono = 'question';
                    titulo = 'Cerrar Orden';
                    mensaje = '¿Estás seguro de que deseas cerrar esta orden de compra?';
                    confirmButtonColor = '#343a40';
                    confirmButtonText = 'Sí, cerrar';
                    break;
                default:
                    estadoTexto = nuevoEstado;
                    icono = 'question';
                    titulo = 'Cambiar Estado';
                    mensaje = `¿Estás seguro de que deseas cambiar el estado a ${nuevoEstado}?`;
                    confirmButtonColor = '#6c757d';
                    confirmButtonText = 'Sí, cambiar';
            }
            
            Swal.fire({
                title: titulo,
                text: mensaje,
                icon: icono,
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Crear formulario para enviar el cambio de estado
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/OrdenCompras/cambiarEstado/${ordenId}`;
                    
                    // Agregar token CSRF
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);
                    
                    // Agregar método PUT
                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'PUT';
                    form.appendChild(methodField);
                    
                    // Agregar nuevo estado
                    const estadoField = document.createElement('input');
                    estadoField.type = 'hidden';
                    estadoField.name = 'estado';
                    estadoField.value = nuevoEstado;
                    form.appendChild(estadoField);
                    
                    // Enviar formulario
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
@endsection

@section('js')
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
<script>
$(document).ready(function () {
    document.body.classList.add('oc-list-dt');

    var $table = $('#tableOC');
    if (!$table.length || $.fn.DataTable.isDataTable($table)) {
        return;
    }

    var isMobileTable = window.matchMedia('(max-width: 768px)').matches;

    var table = $table.DataTable({
        responsive: false,
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        select: false,
        order: [[5, 'desc']],
        scrollY: isMobileTable ? false : '500px',
        paging: true,
        columnDefs: [
            { targets: 0, orderable: false, className: 'td-actions text-center', width: isMobileTable ? '170px' : '210px' },
            { targets: 1, width: '110px' },
            { targets: 2, width: '200px' },
            { targets: 3, orderable: false, width: '140px' },
            { targets: 4, orderable: true, width: '140px' }
        ],
        fixedColumns: isMobileTable ? false : { start: 3, end: 0 },
        fixedHeader: isMobileTable
            ? false
            : {
                header: true,
                footer: false
            },
        layout: {
            topStart: [
                'pageLength',
                {
                    buttons: [
                        {
                            extend: 'copy',
                            text: '<i class="fa-regular fa-copy"></i>',
                            titleAttr: 'Copiar',
                            className: 'btn btn-tool-copy push'
                        },
                        {
                            extend: 'excel',
                            text: '<i class="fa-regular fa-file-excel"></i>',
                            titleAttr: 'Excel',
                            className: 'btn btn-tool-excel push'
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fa-regular fa-file-pdf"></i>',
                            titleAttr: 'PDF',
                            className: 'btn btn-tool-pdf push'
                        },
                        {
                            extend: 'print',
                            text: '<i class="fa-solid fa-print"></i>',
                            titleAttr: 'Imprimir',
                            className: 'btn btn-tool-print push'
                        },
                        {
                            extend: 'colvis',
                            text: '<i class="fa-solid fa-filter"></i>',
                            titleAttr: 'Columnas',
                            className: 'btn btn-tool-colvis push'
                        }
                    ]
                }
            ],
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging'
        },
        oLanguage: {
            sSearch: '<i class="fa-solid fa-magnifying-glass"></i>'
        },
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json',
            emptyTable: 'No hay órdenes de compra registradas.'
        },
        initComplete: function () {
            adjustOcTable(this.api());
        }
    });

    window.ocDataTable = table;

    function adjustOcTable(api) {
        api = api || table;
        api.columns.adjust();

        if (!isMobileTable && typeof api.fixedColumns === 'function') {
            var fc = api.settings()[0]._fixedColumns;
            if (fc && typeof fc.start === 'function') {
                fc.start(fc.start());
            }
        }

        if (typeof api.fixedHeader === 'function') {
            try {
                var fh = api.fixedHeader();
                if (fh && typeof fh.adjust === 'function') {
                    fh.adjust();
                }
            } catch (e) {
                // FixedHeader puede no estar activo con scrollY
            }
        }
    }

    $(window).on('resize', function () {
        adjustOcTable();
    });

    table.on('draw.dt column-visibility.dt length.dt', function () {
        adjustOcTable();
    });
});
</script>
@endsection
