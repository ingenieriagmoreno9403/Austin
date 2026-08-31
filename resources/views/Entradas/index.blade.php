@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
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
    .entradas-create {
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        border: 1px solid var(--table-border, #e5e7eb);
        border-radius: 16px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        padding: 1.15rem 1.25rem 1.25rem;
        margin-bottom: 1.25rem;
    }
    .entradas-create__header {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }
    .entradas-create__icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        color: #0284c7;
        font-size: 1rem;
    }
    .entradas-create__title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 750;
        color: #0f172a;
        line-height: 1.25;
    }
    .entradas-create__subtitle {
        margin: 0.15rem 0 0;
        font-size: 0.8rem;
        color: var(--table-muted, #6b7280);
    }
    .entradas-create__form-row {
        display: block;
    }
    .entradas-create__field {
        min-width: 0;
    }
    .entradas-create__label {
        display: block;
        font-size: 0.78rem;
        font-weight: 650;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0.35rem;
    }
    .entradas-create__controls {
        display: flex;
        align-items: stretch;
        gap: 0.75rem;
    }
    .entradas-create__select {
        flex: 1 1 auto;
        min-width: 0;
    }
    .entradas-create__hint {
        display: block;
        margin-top: 0.4rem;
        font-size: 0.72rem;
        color: #94a3b8;
    }
    .entradas-create__actions {
        flex: 0 0 auto;
        display: flex;
        align-items: stretch;
    }
    .entradas-create__actions .btn {
        height: 38px;
        min-height: 38px;
        padding-left: 1.1rem;
        padding-right: 1.1rem;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .entradas-create__preview {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
        padding: 0.85rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #f8fafc;
    }
    .entradas-create__preview-item {
        min-width: 0;
    }
    .entradas-create__preview-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.2rem;
    }
    .entradas-create__preview-value {
        font-size: 0.88rem;
        font-weight: 650;
        color: #0f172a;
        word-break: break-word;
    }
    @media (max-width: 768px) {
        .entradas-create__controls {
            flex-direction: column;
            align-items: stretch;
        }
        .entradas-create__actions,
        .entradas-create__actions .btn {
            width: 100%;
        }
        .entradas-create__preview {
            grid-template-columns: 1fr 1fr;
        }
    }
    #ordenCompraId + .select2-container {
        width: 100% !important;
    }
    #ordenCompraId + .select2-container .select2-selection--single {
        min-height: 38px !important;
        height: 38px !important;
        display: flex;
        align-items: center;
        border: 1px solid var(--table-border, #e5e7eb) !important;
        border-radius: 10px !important;
    }
    #ordenCompraId + .select2-container .select2-selection__rendered {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-right: 1.5rem;
    }
    .select2-results__option {
        font-size: 0.85rem;
        line-height: 1.35;
        padding: 0.45rem 0.75rem;
    }
    .oc-select2-option-main {
        color: #0f172a;
        font-size: 0.86rem;
    }
    .oc-select2-option-meta {
        color: #64748b;
        font-size: 0.75rem;
        margin-top: 0.1rem;
    }

    .entradas-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .entradas-filters form {
        gap: 0.35rem 0.65rem;
    }
    .entradas-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
        max-width: none;
    }
    .entradas-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .entradas-filters .form-select-sm,
    .entradas-filters .form-control-sm {
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
    .entradas-filters .form-control-sm[type="date"] {
        min-width: 140px;
        max-width: 160px;
    }
    .entradas-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    @media (max-width: 768px) {
        .entradas-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .entradas-filters .form-select-sm,
        .entradas-filters .form-control-sm {
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
                            <i class="fas fa-dolly"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Recepción de Mercancía</h2>
                            <p class="text-muted mb-0">Administración de mercancía recibida</p>
                        </div>
                    </div>
                    <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                        @if($ordenesSinRecepcion->count() > 0)
                            <button type="button" class="btn btn-baseColor position-relative" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNotificacionesRecepcion" aria-controls="offcanvasNotificacionesRecepcion">
                                <i class="fa-solid fa-bell me-2"></i>
                                Pendientes
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $ordenesSinRecepcion->count() }}
                                    <span class="visually-hidden">órdenes sin recepción</span>
                                </span>
                            </button>
                        @else
                            <button type="button" class="btn btn-baseColor-light" disabled>
                                <i class="fa-regular fa-bell me-2"></i>
                                Pendientes (0)
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @php
            $recepcionesMes = $entradas->where('created_at', '>=', now()->startOfMonth())->count();
            $totalProductosMes = $entradas->where('created_at', '>=', now()->startOfMonth())
                ->sum(function ($entrada) {
                    return $entrada->detalles->sum('cantidad_recibida');
                });
            $ordenesCompletadas = $entradas->where('status', 'completada')->count();
        @endphp

        <!-- Dashboard de Entradas -->
        <div class="row g-3 compras-kpi-row">
            <div class="col-6 col-md-3">
                <div class="compras-kpi compras-kpi--info">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Pendientes</span>
                        <span class="compras-kpi__value">{{ $ordenesCompra->count() }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="compras-kpi compras-kpi--success">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Recepciones (mes)</span>
                        <span class="compras-kpi__value">{{ $recepcionesMes }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="compras-kpi compras-kpi--secondary">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Productos (mes)</span>
                        <span class="compras-kpi__value">{{ $totalProductosMes }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="compras-kpi compras-kpi--warning">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Completadas</span>
                        <span class="compras-kpi__value">{{ $ordenesCompletadas }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="entradas-create">
            <div class="entradas-create__header">
                <div class="entradas-create__icon" aria-hidden="true">
                    <i class="fa-solid fa-dolly"></i>
                </div>
                <div>
                    <h3 class="entradas-create__title">Crear nueva recepción</h3>
                    <p class="entradas-create__subtitle">Selecciona una orden de compra pendiente para iniciar la recepción de mercancía.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('Entradas.store', ['ordenCompraId' => 0]) }}"
                onsubmit="return validarSeleccion();">
                @csrf
                <div class="entradas-create__form-row">
                    <div class="entradas-create__field">
                        <label for="ordenCompraId" class="entradas-create__label">
                            Orden de compra <span class="text-danger">*</span>
                        </label>
                        <div class="entradas-create__controls">
                            <div class="entradas-create__select">
                                <select name="ordenCompraId" id="ordenCompraId" class="form-select select2" required>
                                    <option value="">Selecciona una orden de compra...</option>
                                    @foreach ($ordenesCompra as $orden)
                                        <option value="{{ $orden->id }}"
                                            data-folio="{{ $orden->folio }}"
                                            data-nombre="{{ $orden->nombre ?? 'Sin nombre' }}"
                                            data-proveedor="{{ $orden->proveedor->nombre ?? 'Sin proveedor' }}"
                                            data-fecha="{{ \Carbon\Carbon::parse($orden->fecha_creacion)->format('d/m/Y') }}">
                                            {{ $orden->folio }} — {{ Str::limit($orden->nombre ?? 'Sin nombre', 45) }} · {{ $orden->proveedor->nombre ?? 'Sin proveedor' }} · {{ \Carbon\Carbon::parse($orden->fecha_creacion)->format('d/m/Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="entradas-create__actions">
                                <button class="btn btn-baseColor" type="submit">
                                    <i class="fa-solid fa-arrow-right me-1"></i> Continuar
                                </button>
                            </div>
                        </div>
                        <small class="entradas-create__hint">
                            Solo se muestran órdenes pendientes o con recepción parcial.
                        </small>
                    </div>
                </div>

                <div id="ordenSeleccionadaInfo" class="entradas-create__preview d-none">
                    <div class="entradas-create__preview-item">
                        <div class="entradas-create__preview-label">Folio</div>
                        <div class="entradas-create__preview-value" id="infoOrdenFolio">—</div>
                    </div>
                    <div class="entradas-create__preview-item">
                        <div class="entradas-create__preview-label">Nombre</div>
                        <div class="entradas-create__preview-value" id="infoOrdenNombre">—</div>
                    </div>
                    <div class="entradas-create__preview-item">
                        <div class="entradas-create__preview-label">Proveedor</div>
                        <div class="entradas-create__preview-value" id="infoOrdenProveedor">—</div>
                    </div>
                    <div class="entradas-create__preview-item">
                        <div class="entradas-create__preview-label">Fecha</div>
                        <div class="entradas-create__preview-value" id="infoOrdenFecha">—</div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-orange">Historial de Recepciones</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <div class="filters entradas-filters">
                        <form id="formFiltros" class="d-flex flex-wrap align-items-center">
                            <div class="filter-field">
                                <label class="form-label" for="filtroEstado">Estado</label>
                                <select class="form-select form-select-sm" id="filtroEstado" aria-label="Filtrar por estado">
                                    <option value="">Todos</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="parcial">Parcial</option>
                                    <option value="completada">Completada</option>
                                </select>
                            </div>
                            <div class="filter-field">
                                <label class="form-label" for="filtroProveedor">Proveedor</label>
                                <select class="form-select form-select-sm" id="filtroProveedor" aria-label="Filtrar por proveedor">
                                    <option value="">Todos</option>
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
                            <button type="button" class="btn btn-baseColor-light btn-sm flex-shrink-0" id="btnAplicarFiltros">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" id="btnLimpiarFiltros">
                                Limpiar
                            </button>
                        </form>
                    </div>

                    @php
                        // % por recepción (no el acumulado de la OC). Si por redondeo o sobrerecepción
                        // la suma de una misma OC pasa de 100, se escala y reparte en enteros ≤ 100.
                        $porcentajesEntrega = [];
                        foreach ($entradas->groupBy('orden_compra_id') as $grupo) {
                            $primera = $grupo->first();
                            $totalPedidoOrden = 0.0;
                            if ($primera && $primera->ordenCompra && $primera->ordenCompra->detalles) {
                                $totalPedidoOrden = (float) $primera->ordenCompra->detalles->sum('cantidad');
                            }

                            $shares = [];
                            foreach ($grupo as $e) {
                                $recibido = (float) $e->detalles->sum('cantidad_recibida');
                                $shares[$e->id] = $totalPedidoOrden > 0
                                    ? ($recibido / $totalPedidoOrden) * 100
                                    : 0.0;
                            }

                            $suma = array_sum($shares);
                            if ($suma > 100 && $suma > 0) {
                                foreach ($shares as $id => $share) {
                                    $shares[$id] = ($share / $suma) * 100;
                                }
                                $suma = 100.0;
                            }

                            $target = (int) min(100, round($suma));
                            $floors = [];
                            $remanentes = [];
                            foreach ($shares as $id => $share) {
                                $floors[$id] = (int) floor($share);
                                $remanentes[$id] = $share - $floors[$id];
                            }

                            $diff = $target - array_sum($floors);
                            arsort($remanentes);
                            foreach (array_keys($remanentes) as $id) {
                                if ($diff <= 0) {
                                    break;
                                }
                                $floors[$id]++;
                                $diff--;
                            }

                            foreach ($floors as $id => $pct) {
                                $porcentajesEntrega[$id] = max(0, min(100, (int) $pct));
                            }
                        }
                    @endphp

                    <table class="table table-striped table-hover display modern-table w-100" id="tablaEntradas">
                        <thead>
                            <tr>
                                <th class="text-center">Acciones</th>
                                <th class="text-center">No.</th>
                                <th class="text-center">Orden de Compra</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Nivel de Entrega</th>
                                <th class="text-center">Proveedor</th>
                                <th class="text-center">Fecha de Recepción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entradas as $entrada)
                                @php
                                    $porcentaje = $porcentajesEntrega[$entrada->id] ?? 0;

                                    // Colores semáforo para nivel de entrega
                                    if ($porcentaje >= 100) {
                                        $progresoBarClass = 'bg-success';
                                    } elseif ($porcentaje >= 50) {
                                        $progresoBarClass = 'bg-warning';
                                    } elseif ($porcentaje > 0) {
                                        $progresoBarClass = 'bg-danger';
                                    } else {
                                        $progresoBarClass = 'bg-secondary';
                                    }

                                    $badgeClass = 'secondary';
                                    switch($entrada->status) {
                                        case 'pendiente': $badgeClass = 'warning'; break;
                                        case 'parcial': $badgeClass = 'info'; break;
                                        case 'completada':
                                        case 'cerrada': $badgeClass = 'success'; break;
                                    }
                                @endphp
                                <tr class="fila-entrada" data-status="{{ $entrada->status ?? 'pendiente' }}">
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <a href="{{ route('Entradas.show', $entrada->id) }}" class="btn btn-secondary btn-sm" title="Ver detalles">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            @php
                                                $puedeEliminar = $entrada->created_at && $entrada->created_at->gte(now()->subDay());
                                            @endphp
                                            @if($puedeEliminar)
                                                <button type="button" class="btn btn-danger btn-sm" title="Eliminar"
                                                        onclick="confirmarEliminar({{ $entrada->id }})">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-outline-secondary btn-sm" disabled
                                                        title="No se puede eliminar: tiene más de un día de creada">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="fw-bold text-center">{{ $entrada->id }}</td>
                                    <td class="text-center">
                                        <!-- {{ $entrada->orden_compra_id }}&nbsp;  -->
                                        <b class="badge badge-secondary fw-bold fs-10"> FOLIO {{ $entrada->ordenCompra->folio}}</b>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $badgeClass }}">
                                            {{ ucfirst($entrada->status ?? 'pendiente') }}
                                        </span>
                                    </td>
                                    <td data-order="{{ $porcentaje }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 12px; min-width: 70px; background: #e9ecef;">
                                                <div class="progress-bar {{ $progresoBarClass }}"
                                                    role="progressbar"
                                                    style="width: {{ $porcentaje }}%"
                                                    aria-valuenow="{{ $porcentaje }}"
                                                    aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                            <span class="fw-bold text-muted" style="min-width: 3rem;">{{ $porcentaje }}%</span>
                                        </div>
                                    </td>
                                    <td>{{ $entrada->ordenCompra->proveedor->nombre ?? 'Sin proveedor' }}</td>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($entrada->fecha_recepcion)->format('d/m/Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario oculto para eliminar (confirmación con SweetAlert) -->
    <form id="formEliminar" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>

    <!-- Offcanvas de Notificaciones de Recepción -->
    @if($ordenesSinRecepcion->count() > 0)
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNotificacionesRecepcion" aria-labelledby="offcanvasNotificacionesRecepcionLabel">
        <div class="offcanvas-header bg-opacity-10">
            <h5 class="offcanvas-title text-orange" id="offcanvasNotificacionesRecepcionLabel">
                <i class="fa-solid fa-truck-ramp-box me-2"></i>
                Pendientes de Recepción
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body">
            <div class="alert alert-warning fs-9">
                <i class="fa-solid fa-info-circle me-2"></i>
                Tienes <strong>{{ $ordenesSinRecepcion->count() }}</strong> orden(es) con mercancía pendiente de recepción (incluye recepciones parciales).
            </div>
            
            <div class="list-group">
                @foreach($ordenesSinRecepcion as $orden)
                <div class="card p-3 mb-4 shadow-sm oc-notif-card"
                     role="link"
                     tabindex="0"
                     title="Ver orden"
                     onclick="window.location.href='/OrdenCompras/{{ $orden->id }}'"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='/OrdenCompras/{{ $orden->id }}';}"
                     style="cursor:pointer;">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <span class="badge bg-info me-2">{{ $orden->folio }}</span>
                                {{ Str::limit($orden->nombre, 40) }}
                            </h6>
                            <p class="mb-1 text-muted">
                                <i class="fa-solid fa-building me-1"></i>
                                {{ $orden->proveedor->nombre ?? 'Sin proveedor' }}
                            </p>
                            <small class="text-muted">
                                <i class="fa-solid fa-calendar me-1"></i>
                                Enviada: {{ \Carbon\Carbon::parse($orden->updated_at)->format('d/m/Y') }}
                                <br>
                                <i class="fa-solid fa-clock me-1"></i>
                                Límite: {{ \Carbon\Carbon::parse($orden->fecha_limite)->format('d/m/Y') }}
                            </small>
                        </div>
                    </div>

                    <div class="oc-notif-actions" onclick="event.stopPropagation();">
                        <button type="button"
                                class="oc-notif-btn oc-notif-btn-ok"
                                onclick="crearRecepcion({{ $orden->id }})"
                                title="Crear Recepción">
                            <i class="fa-solid fa-plus-circle"></i>
                            Crear recepción
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="mt-4">
                <h6 class="text-success mb-3">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    Órdenes con Recepción ({{ $ordenesConRecepcion->count() }})
                </h6>
                
                @if($ordenesConRecepcion->count() > 0)
                <div class="list-group">
                    @foreach($ordenesConRecepcion->take(3) as $orden)
                    <div class="card p-2 mb-2 border-success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-success me-2">{{ $orden->folio }}</span>
                                <small>{{ Str::limit($orden->nombre, 30) }}</small>
                            </div>
                            <small class="text-success">
                                <i class="fa-solid fa-check me-1"></i>
                                {{ $orden->entradasInventario->count() }} recepción(es)
                            </small>
                        </div>
                    </div>
                    @endforeach
                    
                    @if($ordenesConRecepcion->count() > 3)
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Y {{ $ordenesConRecepcion->count() - 3 }} orden(es) más con recepción...
                        </small>
                    </div>
                    @endif
                </div>
                @else
                <div class="alert alert-info fs-9">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    No hay órdenes con recepción registrada.
                </div>
                @endif
            </div>
            
            <div class="mt-4 text-center">
                <small class="text-muted">
                    <i class="fa-solid fa-lightbulb me-1"></i>
                    Tip: Haz clic en el card para ver la orden, o usa el botón para crear la recepción.
                </small>
            </div>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar DataTable
            const dataTable = new DataTable('#tablaEntradas', {
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json',
                    emptyTable: 'No hay recepciones registradas.'
                },
                order: [[1, 'desc']],
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                responsive: false,
                scrollX: true,
                autoWidth: false,
                columnDefs: [
                    { targets: 0, orderable: false, searchable: false, className: 'text-center' }
                ],
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
                }
            });

            // Llenar el dropdown de proveedores
            const proveedoresSet = new Set();
            document.querySelectorAll('#tablaEntradas tbody tr').forEach(row => {
                const proveedor = row.cells[5].textContent.trim();
                if (proveedor && proveedor !== 'Sin proveedor') {
                    proveedoresSet.add(proveedor);
                }
            });

            const selectProveedor = document.getElementById('filtroProveedor');
            Array.from(proveedoresSet).sort().forEach(proveedor => {
                const option = document.createElement('option');
                option.value = proveedor;
                option.textContent = proveedor;
                selectProveedor.appendChild(option);
            });

            const formFiltros = document.getElementById('formFiltros');
            if (formFiltros) {
                formFiltros.addEventListener('submit', function(e) {
                    e.preventDefault();
                    document.getElementById('btnAplicarFiltros').click();
                });
            }

            document.getElementById('btnAplicarFiltros').addEventListener('click', function() {
                const proveedor = document.getElementById('filtroProveedor').value;
                const estado = document.getElementById('filtroEstado').value;

                dataTable.column(5).search(proveedor);
                dataTable.column(3).search(estado);
                dataTable.draw();
            });

            document.getElementById('btnLimpiarFiltros').addEventListener('click', function() {
                document.getElementById('filtroFechaDesde').value = '';
                document.getElementById('filtroFechaHasta').value = '';
                document.getElementById('filtroProveedor').value = '';
                document.getElementById('filtroEstado').value = '';

                dataTable.search('').columns().search('').draw();
            });
        });
        
        function validarSeleccion() {
            const orden = document.getElementById('ordenCompraId').value;
            if (orden == "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selección requerida',
                    text: 'Por favor seleccione una orden de compra.',
                    confirmButtonText: 'Entendido'
                });
                return false;
            }
            return true;
        }

        $(function () {
            const $select = $('#ordenCompraId');
            if (!$select.length) return;

            function formatOrdenResult(option) {
                if (!option.id) {
                    return option.text;
                }
                const el = option.element;
                if (!el) {
                    return option.text;
                }

                const folio = el.getAttribute('data-folio') || '';
                const nombre = el.getAttribute('data-nombre') || '';
                const proveedor = el.getAttribute('data-proveedor') || '';
                const fecha = el.getAttribute('data-fecha') || '';

                const $wrap = $('<div class="oc-select2-option"></div>');
                const $main = $('<div class="oc-select2-option-main"></div>');
                $main.append($('<strong></strong>').text(folio));
                $main.append(document.createTextNode(' · ' + nombre));
                const $meta = $('<div class="oc-select2-option-meta"></div>').text(proveedor + ' · ' + fecha);
                $wrap.append($main).append($meta);
                return $wrap;
            }

            function formatOrdenSelection(option) {
                if (!option.id) {
                    return option.text;
                }
                const el = option.element;
                if (!el) {
                    return option.text;
                }
                return (el.getAttribute('data-folio') || '') + ' — ' + (el.getAttribute('data-nombre') || '');
            }

            function actualizarInfoOrden() {
                const opt = $select.find('option:selected').get(0);
                const panel = document.getElementById('ordenSeleccionadaInfo');
                if (!panel) return;

                if (!opt || !opt.value) {
                    panel.classList.add('d-none');
                    return;
                }

                document.getElementById('infoOrdenFolio').textContent = opt.dataset.folio || '—';
                document.getElementById('infoOrdenNombre').textContent = opt.dataset.nombre || '—';
                document.getElementById('infoOrdenProveedor').textContent = opt.dataset.proveedor || '—';
                document.getElementById('infoOrdenFecha').textContent = opt.dataset.fecha || '—';
                panel.classList.remove('d-none');
            }

            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '-- Selecciona una Orden de Compra --',
                allowClear: true,
                templateResult: formatOrdenResult,
                templateSelection: formatOrdenSelection
            });

            $select.on('change', actualizarInfoOrden);
            actualizarInfoOrden();
        });
        
        function confirmarEliminar(id) {
            Swal.fire({
                title: '¿Eliminar recepción?',
                html: 'Se revertirán las <strong>existencias</strong> y las cantidades de la orden de compra.<br><br>Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('formEliminar');
                    form.action = `/entradas/${id}`;
                    form.submit();
                }
            });
        }
        
    </script>

    <!-- Función para crear recepción desde notificaciones -->
    <script>
        function crearRecepcion(ordenId) {
            Swal.fire({
                title: 'Crear Recepción',
                text: '¿Deseas crear una nueva recepción para esta orden de compra?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, crear recepción',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Crear formulario para enviar la creación de recepción
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/entradas/store`;
                    
                    // Agregar token CSRF
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);
                    
                    // Agregar ID de orden de compra
                    const ordenField = document.createElement('input');
                    ordenField.type = 'hidden';
                    ordenField.name = 'ordenCompraId';
                    ordenField.value = ordenId;
                    form.appendChild(ordenField);
                    
                    // Enviar formulario
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
@endsection
