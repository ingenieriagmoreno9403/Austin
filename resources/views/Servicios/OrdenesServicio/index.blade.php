@extends('layouts.app')
@section('css')
<style>
    .ServiciosOrdenesServicio {
        --os-bg-soft: #f6f9fc;
        --os-card: #ffffff;
        --os-border: #e8eef5;
        --os-text: #1f2937;
        --os-muted: #64748b;
        --os-primary: #2563eb;
        --os-primary-soft: #eff6ff;
        --os-shadow-sm: 0 6px 18px rgba(15, 23, 42, 0.06);
        --os-shadow-md: 0 14px 30px rgba(15, 23, 42, 0.08);
    }
    .ServiciosOrdenesServicio .card,
    .ServiciosOrdenesServicio .modal-content {
        background: var(--os-card);
        border: 1px solid var(--os-border) !important;
    }
    .ServiciosOrdenesServicio .card {
        border-radius: 14px;
        transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease, color .2s ease;
        box-shadow: var(--os-shadow-sm);
    }
    .ServiciosOrdenesServicio.compact .card-body {
        padding: .8rem !important;
    }
    .ServiciosOrdenesServicio.compact .row.g-3 {
        --bs-gutter-y: .7rem;
    }
    .ServiciosOrdenesServicio .card:hover {
        transform: translateY(-2px);
        box-shadow: var(--os-shadow-md) !important;
    }
    .ServiciosOrdenesServicio .card-header {
        border-radius: 14px 14px 0 0 !important;
        border-bottom: 1px solid var(--os-border) !important;
        background: linear-gradient(180deg, #f8fbff 0%, #f3f8ff 100%) !important;
    }
    .ServiciosOrdenesServicio .card-header b,
    .ServiciosOrdenesServicio .card-header .fw-bold {
        color: #0f172a;
    }
    .ServiciosOrdenesServicio .btn {
        border-radius: 10px;
        transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        font-weight: 600;
    }
    .ServiciosOrdenesServicio .btn:hover {
        transform: translateY(-1px);
        filter: saturate(1.06);
    }
    .ServiciosOrdenesServicio .btn-primary {
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
    }
    .ServiciosOrdenesServicio .btn-outline-primary,
    .ServiciosOrdenesServicio .btn-outline-success {
        background: #fff;
    }
    .ServiciosOrdenesServicio #tbOrdenes tr {
        transition: background-color .15s ease, box-shadow .15s ease;
    }
    .ServiciosOrdenesServicio #tbOrdenes tr:hover td {
        filter: brightness(0.98);
    }
    .ServiciosOrdenesServicio .table {
        color: var(--os-text);
    }
    .ServiciosOrdenesServicio .catalog-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 14px;
        transition: all .2s ease;
        background: #fff;
    }
    .ServiciosOrdenesServicio .catalog-card:hover {
        border-color: #0d6efd;
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(13, 110, 253, 0.14);
    }
    .ServiciosOrdenesServicio.os-dark .catalog-card {
        background: #0f172a;
        border-color: #334155;
    }
    .ServiciosOrdenesServicio .mini-input {
        min-width: 90px;
    }
    .ServiciosOrdenesServicio .table th,
    .ServiciosOrdenesServicio .table td {
        white-space: nowrap;
    }
    .ServiciosOrdenesServicio .table thead th {
        font-size: .79rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
        color: #1e3a8a;
        border-bottom: 1px solid #dbe8ff;
        background: #eef4ff !important;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .ServiciosOrdenesServicio .table tbody td {
        border-color: #eef2f7;
        vertical-align: middle;
    }
    .ServiciosOrdenesServicio .table tbody tr:nth-child(even) td {
        background: #fcfdff;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal th:first-child,
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal td:first-child {
        position: sticky;
        left: 0;
        z-index: 3;
        background: #f8fbff;
        box-shadow: 2px 0 0 #e7eef8;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody td:first-child {
        z-index: 1;
        background: #ffffff;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr:nth-child(even) td:first-child {
        background: #fcfdff;
    }
    /* Colores dinamicos por fila completa, segun proceso/estado */
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr.os-row-normal td {
        background: #e0f2fe !important;
        color: #0f3f66;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr.os-row-entregar td {
        background: #dcfce7 !important;
        color: #14532d;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr.os-row-expirado td {
        background: #fee2e2 !important;
        color: #7f1d1d;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr.os-row-refacciones td {
        background: #ffedd5 !important;
        color: #7c2d12;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr.os-row-surtido td {
        background: #f3e8ff !important;
        color: #581c87;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr.os-row-espera td {
        background: #e5e7eb !important;
        color: #1f2937;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr.os-row-proceso td {
        background: #fef3c7 !important;
        color: #78350f;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr.os-row-finalizado td {
        background: #d1fae5 !important;
        color: #065f46;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr.os-row-otro td {
        background: #ecfeff !important;
        color: #155e75;
    }
    .ServiciosOrdenesServicio .table#tablaOrdenesPrincipal tbody tr[class*="os-row-"] td:first-child {
        box-shadow: 2px 0 0 #d7e3ef;
    }
    .ServiciosOrdenesServicio .form-control,
    .ServiciosOrdenesServicio .form-select {
        border-radius: 10px;
        border: 1px solid #dbe5f0;
        box-shadow: none;
    }
    .ServiciosOrdenesServicio .form-control:focus,
    .ServiciosOrdenesServicio .form-select:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, 0.14);
    }
    .ServiciosOrdenesServicio .alert-info {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e3a8a;
    }
    .ServiciosOrdenesServicio .flow-col {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        min-height: 220px;
    }
    .ServiciosOrdenesServicio .flow-col .flow-head {
        border-bottom: 1px solid #eef2f7;
        padding: 10px 12px;
        font-weight: 700;
    }
    .ServiciosOrdenesServicio .flow-col .flow-body {
        padding: 10px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
        align-content: start;
        min-height: 180px;
    }
    .ServiciosOrdenesServicio .flow-chip {
        border-radius: 12px;
        padding: 10px;
        font-size: .82rem;
        font-weight: 700;
        border: 1px solid transparent;
        box-shadow: 0 5px 12px rgba(15, 23, 42, 0.08);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .ServiciosOrdenesServicio .flow-chip:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
    }
    .ServiciosOrdenesServicio .flow-chip-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
        gap: 8px;
    }
    .ServiciosOrdenesServicio .flow-chip-ord {
        font-size: .9rem;
        letter-spacing: .02em;
    }
    .ServiciosOrdenesServicio .flow-chip-meta {
        font-size: .74rem;
        font-weight: 700;
        opacity: .9;
        white-space: nowrap;
    }
    .ServiciosOrdenesServicio .flow-chip-service {
        font-size: .76rem;
        font-weight: 600;
        opacity: .95;
        line-height: 1.2;
    }
    .ServiciosOrdenesServicio .flow-chip-vehicle {
        font-size: .74rem;
        font-weight: 500;
        opacity: .8;
        margin-top: 4px;
        line-height: 1.2;
    }
    .ServiciosOrdenesServicio .chip-pendiente { background: #fef3c7; color: #7c5a03; border-color: #fde68a; }
    .ServiciosOrdenesServicio .chip-proceso { background: #dbeafe; color: #1e3a8a; border-color: #bfdbfe; }
    .ServiciosOrdenesServicio .chip-finalizado { background: #dcfce7; color: #166534; border-color: #bbf7d0; }

    /* Badges pastel para estados/procesos */
    .ServiciosOrdenesServicio .badge-soft {
        border: 1px solid transparent;
        font-weight: 600;
    }
    .ServiciosOrdenesServicio .badge-soft-blue { background: #dbeafe; color: #1e3a8a; border-color: #bfdbfe; }
    .ServiciosOrdenesServicio .badge-soft-yellow { background: #fef3c7; color: #854d0e; border-color: #fde68a; }
    .ServiciosOrdenesServicio .badge-soft-red { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    .ServiciosOrdenesServicio .badge-soft-green { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .ServiciosOrdenesServicio .badge-soft-orange { background: #ffedd5; color: #9a3412; border-color: #fed7aa; }
    .ServiciosOrdenesServicio .badge-soft-purple { background: #f3e8ff; color: #6b21a8; border-color: #e9d5ff; }
    .ServiciosOrdenesServicio .badge-soft-cyan { background: #cffafe; color: #155e75; border-color: #a5f3fc; }
    .ServiciosOrdenesServicio .badge-soft-gray { background: #e5e7eb; color: #374151; border-color: #d1d5db; }
    .ServiciosOrdenesServicio .is-invalid-soft {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.18) !important;
    }
    .ServiciosOrdenesServicio .suggestions-box {
        position: absolute;
        width: 100%;
        z-index: 1056;
        max-height: 220px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #dee2e6;
        border-top: 0;
        border-radius: 0 0 10px 10px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, .08);
    }
    .ServiciosOrdenesServicio .suggestion-item {
        padding: 8px 12px;
        cursor: pointer;
        font-size: .9rem;
    }
    .ServiciosOrdenesServicio .suggestion-item:hover {
        background: #f1f5f9;
    }
    .ServiciosOrdenesServicio .modal-content {
        border-radius: 16px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.2);
    }
    .ServiciosOrdenesServicio .modal-header .modal-title {
        color: #0f172a;
    }
    .ServiciosOrdenesServicio .badge {
        border-radius: 999px;
        padding: .38rem .62rem;
    }
    @keyframes fadeSlideIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .ServiciosOrdenesServicio .table-animated tbody tr {
        animation: fadeSlideIn .28s ease both;
    }
    .ServiciosOrdenesServicio .report-bar-wrap {
        height: 28px;
        background: #eef2ff;
        border-radius: 10px;
        overflow: hidden;
    }
    .ServiciosOrdenesServicio .report-bar {
        height: 100%;
        background: linear-gradient(90deg, #0d6efd 0%, #6f42c1 100%);
        color: #fff;
        font-weight: 700;
        font-size: .78rem;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: .55rem;
        min-width: 34px;
        transition: width .35s ease;
    }
    .ServiciosOrdenesServicio .link-box {
        background: #f8fbff;
        border: 1px solid #dbeafe;
        border-radius: 10px;
        padding: 10px 12px;
    }
    .ServiciosOrdenesServicio .link-box code {
        display: block;
        font-size: .75rem;
        color: #1e3a8a;
        word-break: break-all;
    }
    .ServiciosOrdenesServicio .foto-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 6px;
    }
    .ServiciosOrdenesServicio .foto-thumb {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #dbeafe;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.12);
    }
    .ServiciosOrdenesServicio .upload-inline {
        min-width: 190px;
    }
    /* Modo demo antibloqueo: sin backdrop para que nunca tape la pantalla */
    .ServiciosOrdenesServicio ~ .modal-backdrop,
    .modal-backdrop {
        display: none !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }
    body.modal-open {
        overflow: auto !important;
        padding-right: 0 !important;
    }
</style>
@endsection
@section('content')

<div class="container-fluid format_page ServiciosOrdenesServicio">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <h3 class="m-0 animate__animated animate__backInLeft">Ordenes de Servicio</h3>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="/Servicios" class="btn btn-outline-primary">
                        <i class="fa-solid fa-house me-2"></i>Inicio
                    </a>
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalCatalogosCrud">
                        <i class="fa-solid fa-layer-group me-2"></i>Catalogos CRUD
                    </button>
                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalReportesServicio">
                        <i class="fa-solid fa-chart-column me-2"></i>Reportes
                    </button>
                    <button type="button" class="btn btn-primary" id="btnNuevaOrden">
                        <i class="fa-solid fa-plus me-2"></i>Nueva Orden
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <b>Filtros rapidos</b>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-4">
                        <label class="form-label fw-semibold">Buscar</label>
                        <input id="txtBuscar" class="form-control" placeholder="No. orden, servicio o descripcion">
                    </div>
                    <div class="col-12 col-md-4 col-xl-3">
                        <label class="form-label fw-semibold">Estado</label>
                        <select id="cmbEstado" class="form-select">
                            <option value="ALL">Todos</option>
                            <option value="Proceso">Proceso</option>
                            <option value="En espera">En espera</option>
                            <option value="En proceso">En proceso</option>
                            <option value="Finalizado">Finalizado</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 col-xl-2 d-flex align-items-end">
                        <div class="d-flex justify-content-between align-items-center w-100">
                        <span class="text-secondary fs-8">Resultados</span>
                        <span id="lblResultados" class="badge bg-primary">0</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-12 col-xl-3 d-flex align-items-end">
                    <div class="alert alert-info mb-0 w-100">
                        Orden seleccionada:
                        <b id="lblSeleccion">Ninguna</b>
                    </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 d-flex justify-content-between align-items-center">
                    <b>Listado de ordenes</b>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary fs-8">Haz clic en una fila para cargar sus datos</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAbrirLigasSeguimiento" data-bs-toggle="modal" data-bs-target="#modalLigasSeguimiento">
                            <i class="fa-solid fa-link me-1"></i>Ligas de seguimiento de orden
                        </button>
                    </div>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 table-animated" id="tablaOrdenesPrincipal">
                            <thead class="table-primary">
                                <tr>
                                    <th class="text-center">No. Orden</th>
                                    <th class="text-center">Vehiculo</th>
                                    <th class="text-center">Torre</th>
                                    <th class="text-center">Fecha/Hora</th>
                                    <th class="text-center">Promesa</th>
                                    <th class="text-center">En cual va</th>
                                    <th class="text-center">Servicio actual</th>
                                    <th class="text-center">Proceso detalle</th>
                                    <th class="text-center">Estado orden</th>
                                    <th class="text-center">Total servicios</th>
                                    <th class="text-center">Detalle</th>
                                </tr>
                            </thead>
                            <tbody id="tbOrdenes">
                                <tr><td colspan="11" class="text-center text-secondary">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <b>Tablero de avance por tipo de servicio</b>
                        <div class="fs-8 text-secondary">Visualiza en que va cada No.S / No. Orden separado por servicio</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="fs-8 text-secondary m-0">Servicio</label>
                        <select id="cmbServicioTablero" class="form-select form-select-sm" style="min-width: 260px;">
                            <option value="ALL">Todos</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="flow-col">
                                <div class="flow-head">Pendientes</div>
                                <div class="flow-body" id="flowPendiente"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="flow-col">
                                <div class="flow-head">En proceso</div>
                                <div class="flow-body" id="flowProceso"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="flow-col">
                                <div class="flow-head">Finalizadas</div>
                                <div class="flow-body" id="flowFinalizado"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reportes -->
<div class="modal fade" id="modalReportesServicio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-chart-column me-2 text-info"></i>Reportes de Incidencias
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="alert alert-info mb-3">
                    Selecciona un reporte y da clic en <b>Ver reporte</b>. Se abrira en una nueva pestaña con tabla y grafica.
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-8">
                        <input id="txtBuscarReporte" class="form-control" placeholder="Buscar reporte... (ej. lavado, vencidas, torre)">
                    </div>
                    <div class="col-12 col-md-4">
                        <select id="cmbCategoriaReporte" class="form-select">
                            <option value="ALL">Todas las categorias</option>
                            <option value="calidad">Calidad</option>
                            <option value="productividad">Productividad</option>
                            <option value="operacion">Operacion</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>Reporte</th>
                                <th>Descripcion</th>
                                <th class="text-center">Accion</th>
                            </tr>
                        </thead>
                        <tbody id="tbListaReportes">
                            <tr class="table-light reporte-section-row" data-cat="calidad">
                                <td class="fw-bold">Calidad</td>
                                <td class="text-secondary">Seguimiento a incidencias y reclamaciones.</td>
                                <td></td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="calidad">
                                <td class="fw-semibold">Incidencias por Servicio</td>
                                <td class="text-secondary">Frecuencia de incidencias capturadas en servicios de la orden.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'incidencias']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="calidad">
                                <td class="fw-semibold">Reclamaciones</td>
                                <td class="text-secondary">Incidencias reportadas por los servicios.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'reclamaciones']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>

                            <tr class="table-light reporte-section-row" data-cat="productividad">
                                <td class="fw-bold">Productividad</td>
                                <td class="text-secondary">Indicadores operativos para taller y seguimiento.</td>
                                <td></td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="productividad">
                                <td class="fw-semibold">De productividad (en tiempo x empleado)</td>
                                <td class="text-secondary">Servicios finalizados a tiempo y fuera de tiempo por tecnico, con cumplimiento.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'productividad-empleado-tiempo']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="productividad">
                                <td class="fw-semibold">Servicios por Dia segun Estado</td>
                                <td class="text-secondary">Conteo diario de servicios pendientes, en proceso y finalizados.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'servicios-dia-estados']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="productividad">
                                <td class="fw-semibold">Total Servicios</td>
                                <td class="text-secondary">Servicios mas frecuentes registrados.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'total-servicios']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="productividad">
                                <td class="fw-semibold">Total Unidades</td>
                                <td class="text-secondary">Unidades atendidas por tipo de vehiculo.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'total-unidades']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="productividad">
                                <td class="fw-semibold">Total Horas x Linea</td>
                                <td class="text-secondary">Suma de horas reales por tipo de servicio.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'total-horas-linea']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="productividad">
                                <td class="fw-semibold">Ordenes detenidas</td>
                                <td class="text-secondary">Servicios detenidos por pendientes de autorizacion/refacciones.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'ordenes-detenidas']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="productividad">
                                <td class="fw-semibold">Tareas Vencidas x Tecnico</td>
                                <td class="text-secondary">Servicios con T.Real mayor a T.Tab por tecnico.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'tareas-vencidas-tecnico']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="productividad">
                                <td class="fw-semibold">Tareas Vencida x Lavador</td>
                                <td class="text-secondary">Tareas de lavado vencidas por empleado.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'tareas-vencidas-lavador']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="table-light reporte-section-row" data-cat="operacion">
                                <td class="fw-bold">Operacion</td>
                                <td class="text-secondary">Vista operativa por torre, empleado, tiempos y ordenes.</td>
                                <td></td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="operacion">
                                <td class="fw-semibold">Servicios por Torre</td>
                                <td class="text-secondary">Servicios capturados por cada torre/ubicacion del taller.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'servicios-por-torre']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="operacion">
                                <td class="fw-semibold">Servicios por Empleado</td>
                                <td class="text-secondary">Carga de servicios asignados por tecnico.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'servicios-por-empleado']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="operacion">
                                <td class="fw-semibold">Tiempos Promedio por Servicio</td>
                                <td class="text-secondary">Comparativo de tiempo tabulado vs tiempo real por servicio.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'tiempos-promedio-servicio']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="operacion">
                                <td class="fw-semibold">Ordenes por Tipo de Vehiculo</td>
                                <td class="text-secondary">Participacion por tipo de vehiculo en el total de ordenes.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'ordenes-por-tipo-vehiculo']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="operacion">
                                <td class="fw-semibold">Lista Ordenes</td>
                                <td class="text-secondary">Ordenes con mayor cantidad de servicios.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'lista-ordenes']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="operacion">
                                <td class="fw-semibold">Lavado Carroceria</td>
                                <td class="text-secondary">Estatus de servicios relacionados a lavado de carroceria.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'lavado-carroceria']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                            <tr class="reporte-item-row" data-cat="operacion">
                                <td class="fw-semibold">Lavado Motor</td>
                                <td class="text-secondary">Estatus de servicios relacionados a lavado de motor.</td>
                                <td class="text-center">
                                    <a href="{{ route('servicios.ordenes.reporte', ['reporte' => 'lavado-motor']) }}" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fa-solid fa-up-right-from-square me-1"></i>Ver reporte
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Orden con detalle dinamico -->
<div class="modal fade" id="modalNuevaOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-car-side me-2 text-primary"></i>Nueva Orden de Servicio
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header text-white" style="background: linear-gradient(90deg, #1e67ff 0%, #0d4fb6 100%);">
                        <b>Datos de la orden</b>
                        <div class="fs-8 text-white-50">Formulario para captura de orden</div>
                    </div>
                    <div class="card-body">
                        <form id="formNuevaOrden">
                            <div class="row g-3">
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">No. Orden</label>
                                    <input id="nNoOrden" class="form-control" placeholder="AUTO">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">T. Vehiculo</label>
                                    <select id="nVehiculo" class="form-select">
                                        @if(isset($tiposVehiculos) && $tiposVehiculos->count() > 0)
                                            @foreach($tiposVehiculos as $tv)
                                                <option value="{{ $tv->tipo_vehiculo }}">{{ $tv->tipo_vehiculo }}</option>
                                            @endforeach
                                        @else
                                            <option>JETTA AX</option>
                                            <option>VENTO</option>
                                            <option>TIGUAN</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label fw-semibold">Torre</label>
                                    <select id="nTorre" class="form-select">
                                        @if(isset($torresTaller) && $torresTaller->count() > 0)
                                            @foreach($torresTaller as $torre)
                                                <option value="{{ $torre->codigo_torre }}">{{ $torre->codigo_torre }} - {{ $torre->nombre_ubicacion }}</option>
                                            @endforeach
                                        @else
                                            <option value="01BCA">01BCA - TORRE 01</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label fw-semibold">Estado</label>
                                    <select id="nEstado" class="form-select">
                                        <option>Proceso</option>
                                        <option>En espera</option>
                                        <option>En proceso</option>
                                        <option>Finalizado</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">Fecha/Hora</label>
                                    <input id="nFecha" type="datetime-local" class="form-control">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label fw-semibold">Promesa</label>
                                    <input id="nPromesa" type="datetime-local" class="form-control">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0">
                        <b>Detalle de trabajos/servicios del carro</b>
                        <div class="fs-8 text-secondary">Agrega y elimina filas dinamicamente</div>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end mb-3">
                            <div class="col-12 col-md-5">
                                <label class="form-label fw-semibold">Servicio</label>
                                <div class="input-group position-relative">
                                    <input id="dServicio" class="form-control" placeholder="Escribe para buscar..." list="dServicioList">
                                    <datalist id="dServicioList"></datalist>
                                    <button id="btnNuevoServicioCatalogo" type="button" class="btn btn-outline-primary">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                    <div id="servicioSuggestions" class="suggestions-box d-none"></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label fw-semibold">Descripcion</label>
                                <input id="dDescripcion" class="form-control" placeholder="Trabajo a realizar">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-semibold">T. Tab</label>
                                <input id="dTab" class="form-control text-center mini-input" placeholder="0.00">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label fw-semibold">T. Real</label>
                                <input id="dReal" class="form-control text-center mini-input" placeholder="0.00">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold">Proceso</label>
                                <select id="dProceso" class="form-select">
                                    <option value="PROCESO NORMAL">PROCESO NORMAL</option>
                                    <option value="PENDIENTE X AUTORIZAR">PENDIENTE X AUTORIZAR</option>
                                    <option value="PENDIENTE X REFACCIONES">PENDIENTE X REFACCIONES</option>
                                    <option value="X REFACCIONES, YA SURTIDO">X REFACCIONES, YA SURTIDO</option>
                                    <option value="PENDIENTE (OTRO)">PENDIENTE (OTRO)</option>
                                    <option value="DOBLE TRABAJO ASIGNADO">DOBLE TRABAJO ASIGNADO</option>
                                    <option value="PROCESO EXPIRADO X TECNICO">PROCESO EXPIRADO X TECNICO</option>
                                    <option value="PROCESO A PUNTO DE ENTREGAR">PROCESO A PUNTO DE ENTREGAR</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold">Empleado (linea)</label>
                                <select id="dEmpleado" class="form-select">
                                    <option value="">-- Sin asignar --</option>
                                    @if(isset($empleadosTaller) && $empleadosTaller->count() > 0)
                                        @foreach($empleadosTaller as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->nombre_completo }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold">Incidencia (linea)</label>
                                <select id="dIncidencia" class="form-select">
                                    <option value="">-- Sin incidencia --</option>
                                    @if(isset($incidenciasCatalogo) && $incidenciasCatalogo->count() > 0)
                                        @foreach($incidenciasCatalogo as $inc)
                                            <option value="{{ $inc->id }}">{{ $inc->nombre_incidencia }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button id="btnAgregarDetalle" type="button" class="btn btn-success">
                                    <i class="fa-solid fa-plus me-2"></i>Agregar detalle
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="text-center">No.S</th>
                                        <th class="text-center">Servicio</th>
                                        <th class="text-center">Descripcion</th>
                                        <th class="text-center">T. Tab</th>
                                        <th class="text-center">T. Real</th>
                                        <th class="text-center">Proceso</th>
                                        <th class="text-center">Empleado</th>
                                        <th class="text-center">Incidencia</th>
                                        <th class="text-center">Accion</th>
                                    </tr>
                                </thead>
                                <tbody id="tbDetalleOrden">
                                    <tr>
                                        <td colspan="9" class="text-center text-secondary">Sin detalles agregados</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarNuevaOrdenDemo">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Orden
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal alta rapida de servicio catalogo -->
<div class="modal fade" id="modalNuevoServicioCatalogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Nuevo Servicio de Taller</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <label class="form-label fw-semibold">Nombre del servicio</label>
                <input id="nuevoNombreServicio" class="form-control" placeholder="Ej. CAMBIO DE AMORTIGUADOR">
                <div class="fs-8 text-secondary mt-2">Se guardara en catalogo para reuso.</div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarNuevoServicioCatalogo">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal central para accesos a CRUD de catalogos -->
<div class="modal fade" id="modalCatalogosCrud" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-layer-group me-2 text-primary"></i>Catalogos CRUD
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <p class="text-secondary mb-3">
                    Este modal centraliza los catalogos para Ordenes de Servicio.
                    Aqui iremos agregando los CRUD que necesites.
                </p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="catalog-card h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="m-0 fw-bold">Tipos de Vehiculos</h6>
                                <span class="badge bg-success">Activo</span>
                            </div>
                            <p class="fs-8 text-secondary mb-3">
                                Administra tipos como JETTA AX, VENTO, TIGUAN, etc.
                            </p>
                            <a href="/CatalogoGeneral/TiposVehiculos" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-arrow-up-right-from-square me-2"></i>Abrir CRUD
                            </a>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="catalog-card h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="m-0 fw-bold">Proximo Catalogo</h6>
                                <span class="badge bg-secondary">Pendiente</span>
                            </div>
                            <p class="fs-8 text-secondary mb-3">
                                Espacio reservado para el siguiente CRUD (ej. marcas, modelos, colores, etc.).
                            </p>
                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                En construccion
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: listado de ordenes (no servicios) -->
<div class="modal fade" id="modalDetalleOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-list-check me-2 text-primary"></i>Detalle de Ordenes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">No. Orden</th>
                                <th class="text-center">Vehiculo</th>
                                <th class="text-center">Torre</th>
                                <th class="text-center">En cual va</th>
                                <th class="text-center">Proceso</th>
                                <th class="text-center">Accion</th>
                            </tr>
                        </thead>
                        <tbody id="tbOrdenesRelacionadas">
                            <tr><td colspan="6" class="text-center text-secondary">Sin informacion</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: ligas de seguimiento por orden -->
<div class="modal fade" id="modalLigasSeguimiento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-link me-2 text-primary"></i>Ligas de seguimiento de orden
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="alert alert-info mb-3">
                    Comparte esta liga con el cliente para que pueda ver el avance y detalles de su orden.
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">No. Orden</th>
                                <th class="text-center">Estado</th>
                                <th>Liga de seguimiento</th>
                                <th class="text-center">Accion</th>
                            </tr>
                        </thead>
                        <tbody id="tbLigasSeguimiento">
                            <tr><td colspan="4" class="text-center text-secondary">Sin informacion</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: servicios de una orden -->
<div class="modal fade" id="modalServiciosOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-screwdriver-wrench me-2 text-primary"></i>Servicios de la Orden
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center">No.S</th>
                                <th class="text-center">Servicio</th>
                                <th class="text-center">Descripcion</th>
                                <th class="text-center">Proceso</th>
                                <th class="text-center">Empleado asignado</th>
                                <th class="text-center">Incidencia</th>
                                <th class="text-center">Accion</th>
                            </tr>
                        </thead>
                        <tbody id="tbDetalleAsignacion">
                            <tr><td colspan="7" class="text-center text-secondary">Sin informacion</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" id="btnGuardarCambiosDetalleOrden">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Guardar cambios
                </button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const data = @json($ordenesResumen ?? []);
    const empleadosTaller = @json($empleadosTaller ?? []);
    const detallesTablero = @json($detallesTablero ?? []);
    const incidenciasCatalogo = @json($incidenciasCatalogo ?? []);

    const $ = (id) => document.getElementById(id);
    const tb = $('tbOrdenes');
    const txtBuscar = $('txtBuscar');
    const cmbEstado = $('cmbEstado');
    const lblResultados = $('lblResultados');
    const lblSeleccion = $('lblSeleccion');
    const btnNuevaOrden = $('btnNuevaOrden');
    const dServicio = $('dServicio');
    const dServicioList = $('dServicioList');
    const servicioSuggestions = $('servicioSuggestions');
    const btnNuevoServicioCatalogo = $('btnNuevoServicioCatalogo');
    const btnGuardarNuevoServicioCatalogo = $('btnGuardarNuevoServicioCatalogo');
    const nuevoNombreServicio = $('nuevoNombreServicio');
    const tbDetalleOrden = $('tbDetalleOrden');
    const btnAgregarDetalle = $('btnAgregarDetalle');
    const btnGuardarNuevaOrdenDemo = $('btnGuardarNuevaOrdenDemo');
    const tbDetalleAsignacion = $('tbDetalleAsignacion');
    const tbOrdenesRelacionadas = $('tbOrdenesRelacionadas');
    const btnGuardarCambiosDetalleOrden = $('btnGuardarCambiosDetalleOrden');
    const btnAbrirLigasSeguimiento = $('btnAbrirLigasSeguimiento');
    const tbLigasSeguimiento = $('tbLigasSeguimiento');
    const cmbServicioTablero = $('cmbServicioTablero');
    const flowPendiente = $('flowPendiente');
    const flowProceso = $('flowProceso');
    const flowFinalizado = $('flowFinalizado');
    const txtBuscarReporte = $('txtBuscarReporte');
    const cmbCategoriaReporte = $('cmbCategoriaReporte');
    const tbListaReportes = $('tbListaReportes');
    const dProceso = $('dProceso');
    const dEmpleado = $('dEmpleado');
    const dIncidencia = $('dIncidencia');
    const detallesNuevaOrden = [];
    let currentDetalleOrdenId = null;
    let rowActiva = null;

    // Modo ejecutivo: mayor densidad de informacion sin cambiar estructura.
    document.querySelector('.ServiciosOrdenesServicio')?.classList.add('compact');

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    function msgError(texto) {
        Toast.fire({
            icon: 'error',
            title: 'Oops...',
            text: texto
        });
    }

    function msgOk(texto) {
        Toast.fire({
            icon: 'success',
            title: 'Exito',
            text: texto,
            timer: 2400
        });
    }

    function badge(estado) {
        if (estado === 'PROCESO NORMAL') return 'badge-soft badge-soft-blue';
        if (estado === 'PROCESO A PUNTO DE ENTREGAR') return 'badge-soft badge-soft-green';
        if (estado === 'PROCESO EXPIRADO X TECNICO') return 'badge-soft badge-soft-red';
        if (estado === 'PENDIENTE X AUTORIZAR') return 'badge-soft badge-soft-green';
        if (estado === 'PENDIENTE X REFACCIONES') return 'badge-soft badge-soft-orange';
        if (estado === 'X REFACCIONES, YA SURTIDO') return 'badge-soft badge-soft-purple';
        if (estado === 'PENDIENTE (OTRO)') return 'badge-soft badge-soft-cyan';
        if (estado === 'DOBLE TRABAJO ASIGNADO') return 'badge-soft badge-soft-purple';
        if (estado === 'Finalizado') return 'badge-soft badge-soft-green';
        if (estado === 'En espera') return 'badge-soft badge-soft-gray';
        if (estado === 'En proceso') return 'badge-soft badge-soft-yellow';
        return 'badge-soft badge-soft-blue';
    }

    function filaColorClass(item) {
        const detalle = (item.procesoDetalle || '').toUpperCase().trim();
        const estado = (item.estado || '').toUpperCase().trim();

        if (detalle === 'PROCESO NORMAL') return 'os-row-normal';
        if (detalle === 'PROCESO A PUNTO DE ENTREGAR') return 'os-row-entregar';
        if (detalle === 'PROCESO EXPIRADO X TECNICO') return 'os-row-expirado';
        if (detalle === 'PENDIENTE X REFACCIONES') return 'os-row-refacciones';
        if (detalle === 'X REFACCIONES, YA SURTIDO') return 'os-row-surtido';
        if (detalle === 'PENDIENTE X AUTORIZAR') return 'os-row-espera';
        if (detalle === 'PENDIENTE (OTRO)' || detalle === 'DOBLE TRABAJO ASIGNADO') return 'os-row-otro';

        if (estado === 'FINALIZADO') return 'os-row-finalizado';
        if (estado === 'EN PROCESO' || estado === 'PROCESO') return 'os-row-proceso';
        if (estado === 'EN ESPERA') return 'os-row-espera';
        return 'os-row-normal';
    }

    function renderFlowBoard() {
        const servicioFiltro = (cmbServicioTablero.value || 'ALL');
        // Tablero por ORDEN (no por cada servicio) para evitar duplicados.
        const lista = (data || []).filter((o) => {
            return servicioFiltro === 'ALL' ? true : (o.servicio || '') === servicioFiltro;
        });

        flowPendiente.innerHTML = '';
        flowProceso.innerHTML = '';
        flowFinalizado.innerHTML = '';

        const addChip = (target, item, css) => {
            const chip = document.createElement('div');
            const avance = ((item.nos && item.totalDetalles) ? (item.nos + '/' + item.totalDetalles) : '0/0');
            chip.className = 'flow-chip ' + css;
            chip.title = (item.servicio || '-') + ' - ' + (item.vehiculo || '-');
            chip.innerHTML = `
                <div class="flow-chip-top">
                    <span class="flow-chip-ord">ORD ${item.noOrden}</span>
                    <span class="flow-chip-meta">${avance}</span>
                </div>
                <div class="flow-chip-service">${item.servicio || 'Sin servicio'}</div>
                <div class="flow-chip-vehicle">${item.vehiculo || '-'}</div>
            `;
            chip.style.cursor = 'pointer';
            chip.addEventListener('click', () => abrirServiciosDeOrden(item.id));
            target.appendChild(chip);
        };

        lista.forEach((item) => {
            const est = (item.estado || '').toUpperCase();
            if (est === 'FINALIZADO') {
                addChip(flowFinalizado, item, 'chip-finalizado');
            } else if (est === 'PROCESO' || est === 'EN PROCESO') {
                addChip(flowProceso, item, 'chip-proceso');
            } else {
                // EN ESPERA y cualquier otro estado no finalizado
                addChip(flowPendiente, item, 'chip-pendiente');
            }
        });

        if (!flowPendiente.children.length) flowPendiente.innerHTML = '<span class="text-secondary fs-8">Sin elementos</span>';
        if (!flowProceso.children.length) flowProceso.innerHTML = '<span class="text-secondary fs-8">Sin elementos</span>';
        if (!flowFinalizado.children.length) flowFinalizado.innerHTML = '<span class="text-secondary fs-8">Sin elementos</span>';
    }

    function initServicioFilter() {
        const unicos = [...new Set((data || []).map((o) => o.servicio).filter(Boolean))];
        unicos.forEach((serv) => {
            const option = document.createElement('option');
            option.value = serv;
            option.textContent = serv;
            cmbServicioTablero.appendChild(option);
        });
        cmbServicioTablero.addEventListener('change', renderFlowBoard);
        renderFlowBoard();
    }

    function renderLigasSeguimiento() {
        tbLigasSeguimiento.innerHTML = '';
        if (!data.length) {
            tbLigasSeguimiento.innerHTML = '<tr><td colspan="4" class="text-center text-secondary">Sin ordenes</td></tr>';
            return;
        }

        data.forEach((x) => {
            const tr = document.createElement('tr');
            const estado = x.estado || 'En espera';
            tr.innerHTML = `
                <td class="text-center fw-semibold">${x.noOrden || '-'}</td>
                <td class="text-center"><span class="badge ${badge(estado)}">${estado}</span></td>
                <td>
                    <div class="link-box">
                        <code>${x.seguimientoUrl || ''}</code>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary btn-copiar-link" data-link="${x.seguimientoUrl || ''}">
                        <i class="fa-solid fa-copy me-1"></i>Copiar
                    </button>
                </td>
            `;
            tbLigasSeguimiento.appendChild(tr);
        });

        tbLigasSeguimiento.querySelectorAll('.btn-copiar-link').forEach((btn) => {
            btn.addEventListener('click', async function() {
                const liga = this.getAttribute('data-link') || '';
                if (!liga) return;
                try {
                    await navigator.clipboard.writeText(liga);
                    msgOk('Liga copiada al portapapeles');
                } catch (e) {
                    msgError('No se pudo copiar la liga');
                }
            });
        });
    }

    function htmlFotosMini(fotos = []) {
        if (!Array.isArray(fotos) || !fotos.length) {
            return '<span class="fs-8 text-secondary">Sin fotos</span>';
        }
        return `
            <div class="foto-grid">
                ${fotos.slice(0, 8).map(f => `<a href="${f.foto_url}" target="_blank"><img src="${f.foto_url}" class="foto-thumb" alt="foto avance"></a>`).join('')}
            </div>
        `;
    }

    async function subirFotoDetalle(idDetalle, inputFile, fotosWrap) {
        const file = inputFile?.files?.[0];
        if (!file) {
            msgError('Selecciona una foto primero');
            return;
        }
        const formData = new FormData();
        formData.append('foto', file);
        try {
            const response = await fetch('/Servicios/OrdenesServicio/Detalle/SubirFoto/' + idDetalle, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData,
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) {
                msgError(json.message || 'No se pudo subir la foto');
                return;
            }
            if (fotosWrap) {
                fotosWrap.innerHTML = htmlFotosMini(json.fotos || []);
            }
            inputFile.value = '';
            msgOk('Foto subida correctamente');
        } catch (error) {
            console.error(error);
            msgError('Error al subir la foto');
        }
    }

    function filtrar() {
        const q = (txtBuscar.value || '').toLowerCase().trim();
        const est = cmbEstado.value;
        const list = data.filter(x => {
            const okEst = est === 'ALL' ? true : x.estado === est;
            const hay = (x.noOrden + ' ' + x.servicio + ' ' + x.descripcion).toLowerCase();
            const okQ = q ? hay.includes(q) : true;
            return okEst && okQ;
        });
        render(list);
    }

    function render(list) {
        tb.innerHTML = '';
        lblResultados.textContent = String(list.length);
        if (!list.length) {
            tb.innerHTML = '<tr><td colspan="11" class="text-center text-secondary">Sin resultados</td></tr>';
            return;
        }
        list.forEach(x => {
            const tr = document.createElement('tr');
            tr.className = filaColorClass(x);
            tr.style.cursor = 'pointer';
            tr.innerHTML = `
                <td class="text-center fw-semibold">${x.noOrden}</td>
                <td class="text-center">${x.vehiculo || '-'}</td>
                <td class="text-center">${x.torre || '-'}</td>
                <td class="text-center">${x.fecha || '-'}</td>
                <td class="text-center">${x.promesa || '-'}</td>
                <td class="text-center">${x.nos || '-'}</td>
                <td class="text-center">${x.servicio || '-'}</td>
                <td class="text-center"><span class="badge ${badge(x.procesoDetalle || 'Pendiente')}">${x.procesoDetalle || 'Pendiente'}</span></td>
                <td class="text-center"><span class="badge ${badge(x.estado)}">${x.estado}</span></td>
                <td class="text-center fw-semibold">${(x.totalDetalles && x.nos) ? `${x.nos}/${x.totalDetalles}` : (x.totalDetalles ? `0/${x.totalDetalles}` : '0/0')}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary btn-detalle" data-id="${x.id}">
                        <i class="fa-solid fa-eye me-1"></i>Detalle
                    </button>
                </td>`;
            tr.addEventListener('click', () => seleccionar(x, tr));
            tb.appendChild(tr);
        });

        tb.querySelectorAll('.btn-detalle').forEach((btn) => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const idOrden = this.getAttribute('data-id');
                abrirServiciosDeOrden(idOrden);
            });
        });
    }

    function toggleForm(enable) {
        // Se mantiene por compatibilidad de flujo en pantalla principal
        // aunque ahora el formulario principal fue movido al modal de Nueva Orden.
    }

    function seleccionar(x, tr) {
        if (rowActiva) rowActiva.classList.remove('table-active');
        rowActiva = tr;
        rowActiva.classList.add('table-active');
        lblSeleccion.textContent = x.noOrden + ' - ' + x.estado;
    }

    function renderDetalleAsignacion(detalles) {
        tbDetalleAsignacion.innerHTML = '';
        if (!detalles.length) {
            tbDetalleAsignacion.innerHTML = '<tr><td colspan="7" class="text-center text-secondary">Sin detalles</td></tr>';
            return;
        }

        detalles.forEach((d) => {
            const tr = document.createElement('tr');
            const optionsEmpleados = empleadosTaller.map((emp) => {
                const selected = Number(emp.id) === Number(d.id_empleado_asignado) ? 'selected' : '';
                return `<option value="${emp.id}" ${selected}>${emp.nombre_completo}</option>`;
            }).join('');
            const optionsIncidencias = incidenciasCatalogo.map((inc) => {
                const selected = Number(inc.id) === Number(d.id_incidencia) ? 'selected' : '';
                return `<option value="${inc.id}" ${selected}>${inc.nombre_incidencia}</option>`;
            }).join('');

            tr.innerHTML = `
                <td class="text-center">${d.no_s}</td>
                <td class="text-center">${d.servicio}</td>
                <td class="text-center">${d.descripcion}</td>
                <td>
                    <select class="form-select form-select-sm proceso-det" data-id="${d.id}">
                        <option value="PROCESO NORMAL" ${d.proceso_estado === 'PROCESO NORMAL' ? 'selected' : ''}>PROCESO NORMAL</option>
                        <option value="PROCESO A PUNTO DE ENTREGAR" ${d.proceso_estado === 'PROCESO A PUNTO DE ENTREGAR' ? 'selected' : ''}>PROCESO A PUNTO DE ENTREGAR</option>
                        <option value="PROCESO EXPIRADO X TECNICO" ${d.proceso_estado === 'PROCESO EXPIRADO X TECNICO' ? 'selected' : ''}>PROCESO EXPIRADO X TECNICO</option>
                        <option value="PENDIENTE X AUTORIZAR" ${d.proceso_estado === 'PENDIENTE X AUTORIZAR' ? 'selected' : ''}>PENDIENTE X AUTORIZAR</option>
                        <option value="PENDIENTE X REFACCIONES" ${d.proceso_estado === 'PENDIENTE X REFACCIONES' ? 'selected' : ''}>PENDIENTE X REFACCIONES</option>
                        <option value="X REFACCIONES, YA SURTIDO" ${d.proceso_estado === 'X REFACCIONES, YA SURTIDO' ? 'selected' : ''}>X REFACCIONES, YA SURTIDO</option>
                        <option value="PENDIENTE (OTRO)" ${d.proceso_estado === 'PENDIENTE (OTRO)' ? 'selected' : ''}>PENDIENTE (OTRO)</option>
                        <option value="DOBLE TRABAJO ASIGNADO" ${d.proceso_estado === 'DOBLE TRABAJO ASIGNADO' ? 'selected' : ''}>DOBLE TRABAJO ASIGNADO</option>
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm empleado-det" data-id="${d.id}">
                        <option value="">-- Sin asignar --</option>
                        ${optionsEmpleados}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm incidencia-det" data-id="${d.id}">
                        <option value="">-- Sin incidencia --</option>
                        ${optionsIncidencias}
                    </select>
                </td>
                <td class="text-center">
                    <div class="d-flex flex-column align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-warning btn-terminar-det mt-1" data-id="${d.id}">
                            Terminado
                        </button>
                        <div class="upload-inline">
                            <input type="file" class="form-control form-control-sm file-foto-det mt-1" data-id="${d.id}" accept="image/*">
                            <button type="button" class="btn btn-sm btn-outline-primary mt-1 btn-subir-foto-det" data-id="${d.id}">
                                <i class="fa-solid fa-camera me-1"></i>Subir foto
                            </button>
                            <div class="fotos-mini-wrap" data-id="${d.id}">
                                ${htmlFotosMini(d.fotos || [])}
                            </div>
                        </div>
                    </div>
                </td>
            `;
            tbDetalleAsignacion.appendChild(tr);
        });

        tbDetalleAsignacion.querySelectorAll('.btn-terminar-det').forEach((btn) => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                await terminarDetalle(id);
            });
        });

        tbDetalleAsignacion.querySelectorAll('.btn-subir-foto-det').forEach((btn) => {
            btn.addEventListener('click', async function() {
                const id = this.getAttribute('data-id');
                const row = this.closest('tr');
                const input = row?.querySelector('.file-foto-det[data-id="' + id + '"]');
                const fotosWrap = row?.querySelector('.fotos-mini-wrap[data-id="' + id + '"]');
                await subirFotoDetalle(id, input, fotosWrap);
            });
        });
    }

    function renderOrdenesRelacionadas(idOrdenSeleccionada = null) {
        tbOrdenesRelacionadas.innerHTML = '';
        if (!data.length) {
            tbOrdenesRelacionadas.innerHTML = '<tr><td colspan="6" class="text-center text-secondary">Sin ordenes</td></tr>';
            return;
        }
        data.forEach((o) => {
            const tr = document.createElement('tr');
            if (idOrdenSeleccionada && Number(o.id) === Number(idOrdenSeleccionada)) {
                tr.classList.add('table-active');
            }
            tr.innerHTML = `
                <td class="text-center fw-semibold">${o.noOrden}</td>
                <td class="text-center">${o.vehiculo || '-'}</td>
                <td class="text-center">${o.torre || '-'}</td>
                <td class="text-center">${o.nos || '-'}</td>
                <td class="text-center"><span class="badge ${badge(o.procesoDetalle || 'Pendiente')}">${o.procesoDetalle || 'Pendiente'}</span></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary btn-ver-servicios" data-id="${o.id}">
                        Ver servicios
                    </button>
                </td>
            `;
            tbOrdenesRelacionadas.appendChild(tr);
        });

        tbOrdenesRelacionadas.querySelectorAll('.btn-ver-servicios').forEach((btn) => {
            btn.addEventListener('click', function() {
                const idOrden = this.getAttribute('data-id');
                abrirServiciosDeOrden(idOrden);
            });
        });
    }

    function abrirModalDetalleOrdenes(idOrdenSeleccionada = null) {
        renderOrdenesRelacionadas(idOrdenSeleccionada);
        const modal = new bootstrap.Modal(document.getElementById('modalDetalleOrden'));
        modal.show();
    }

    async function abrirServiciosDeOrden(idOrden) {
        try {
            currentDetalleOrdenId = idOrden;
            const response = await fetch('/Servicios/OrdenesServicio/Detalle/' + idOrden);
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) {
                msgError(json.message || 'No se pudo cargar el detalle.');
                return;
            }
            renderDetalleAsignacion(json.data || []);
            const modal = new bootstrap.Modal(document.getElementById('modalServiciosOrden'));
            modal.show();
        } catch (error) {
            console.error(error);
            msgError('Error al cargar el detalle.');
        }
    }

    async function guardarCambiosDetalleOrden() {
        try {
            const filas = Array.from(tbDetalleAsignacion.querySelectorAll('tr'));
            if (!filas.length) {
                msgError('No hay lineas para guardar.');
                return;
            }

            const detalles = [];
            filas.forEach((tr) => {
                const selProceso = tr.querySelector('.proceso-det');
                const selEmpleado = tr.querySelector('.empleado-det');
                const selIncidencia = tr.querySelector('.incidencia-det');
                if (!selProceso) return;
                const id = Number(selProceso.getAttribute('data-id'));
                detalles.push({
                    id,
                    proceso_estado: selProceso.value,
                    id_empleado_asignado: selEmpleado && selEmpleado.value !== '' ? Number(selEmpleado.value) : null,
                    id_incidencia: selIncidencia && selIncidencia.value !== '' ? Number(selIncidencia.value) : null,
                });
            });

            if (!detalles.length) {
                msgError('No se detectaron detalles validos.');
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('/Servicios/OrdenesServicio/Detalle/GuardarCambios', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ detalles })
            });

            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) {
                msgError(json.message || 'No se pudieron guardar los cambios.');
                return;
            }

            msgOk('Cambios guardados correctamente.');
            if (currentDetalleOrdenId) {
                await abrirServiciosDeOrden(currentDetalleOrdenId);
            }
            window.location.reload();
        } catch (error) {
            console.error(error);
            msgError('Error al guardar cambios del detalle.');
        }
    }

    async function terminarDetalle(idDetalle) {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('/Servicios/OrdenesServicio/Detalle/Terminar/' + idDetalle, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) {
                msgError(json.message || 'No se pudo terminar el servicio.');
                return;
            }
            msgOk('Servicio marcado como terminado.');
            if (currentDetalleOrdenId) {
                await abrirServiciosDeOrden(currentDetalleOrdenId);
            }
            window.location.reload();
        } catch (error) {
            console.error(error);
            msgError('Error al terminar servicio.');
        }
    }

    function renderDetalleNuevaOrden() {
        tbDetalleOrden.innerHTML = '';
        if (!detallesNuevaOrden.length) {
            tbDetalleOrden.innerHTML = '<tr><td colspan="9" class="text-center text-secondary">Sin detalles agregados</td></tr>';
            return;
        }

        detallesNuevaOrden.forEach((item, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="text-center fw-semibold">${idx + 1}</td>
                <td class="text-center">${item.servicio}</td>
                <td class="text-center">${item.descripcion}</td>
                <td class="text-center">${item.tab}</td>
                <td class="text-center">${item.real}</td>
                <td class="text-center">${item.proceso_estado || '-'}</td>
                <td class="text-center">${item.empleado_nombre || '-'}</td>
                <td class="text-center">${item.incidencia_nombre || '-'}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger" data-index="${idx}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>`;
            tbDetalleOrden.appendChild(tr);
        });

        tbDetalleOrden.querySelectorAll('button[data-index]').forEach((btn) => {
            btn.addEventListener('click', function() {
                const index = Number(this.getAttribute('data-index'));
                detallesNuevaOrden.splice(index, 1);
                renderDetalleNuevaOrden();
            });
        });
    }

    function filtrarReportes() {
        if (!tbListaReportes) return;
        const q = (txtBuscarReporte?.value || '').toLowerCase().trim();
        const cat = (cmbCategoriaReporte?.value || 'ALL').toLowerCase();
        const rows = Array.from(tbListaReportes.querySelectorAll('tr'));

        rows.forEach((row) => {
            if (row.classList.contains('reporte-item-row')) {
                const rowCat = (row.getAttribute('data-cat') || '').toLowerCase();
                const txt = row.textContent.toLowerCase();
                const okQ = q ? txt.includes(q) : true;
                const okCat = (cat === 'all') ? true : rowCat === cat;
                row.style.display = (okQ && okCat) ? '' : 'none';
            }
        });

        // Mostrar encabezado de seccion solo si hay filas visibles de esa categoria
        const secciones = Array.from(tbListaReportes.querySelectorAll('.reporte-section-row'));
        secciones.forEach((sec) => {
            const secCat = (sec.getAttribute('data-cat') || '').toLowerCase();
            const visibles = rows.some((row) =>
                row.classList.contains('reporte-item-row') &&
                (row.getAttribute('data-cat') || '').toLowerCase() === secCat &&
                row.style.display !== 'none'
            );
            sec.style.display = visibles ? '' : 'none';
        });
    }

    btnNuevaOrden.addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('modalNuevaOrden'));
        modal.show();
    });

    async function buscarServiciosCoincidencia(texto = '') {
        try {
            const response = await fetch('/Servicios/Catalogo/Servicios/Buscar?q=' + encodeURIComponent(texto));
            if (!response.ok) return [];
            const json = await response.json();
            dServicioList.innerHTML = '';
            const lista = (json.data || []);
            lista.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.nombre_servicio;
                dServicioList.appendChild(option);
            });
            return lista;
        } catch (error) {
            console.error(error);
            return [];
        }
    }

    function existeServicioEnCatalogo(valor) {
        const buscado = (valor || '').trim().toUpperCase();
        if (!buscado) return false;
        const opciones = Array.from(dServicioList.options || []);
        return opciones.some((opt) => (opt.value || '').trim().toUpperCase() === buscado);
    }

    function pintarSugerencias(lista) {
        servicioSuggestions.innerHTML = '';
        if (!lista.length) {
            servicioSuggestions.classList.add('d-none');
            return;
        }

        lista.forEach((item) => {
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.textContent = item.nombre_servicio;
            div.addEventListener('click', () => {
                dServicio.value = item.nombre_servicio;
                dServicio.classList.remove('is-invalid-soft');
                servicioSuggestions.classList.add('d-none');
            });
            servicioSuggestions.appendChild(div);
        });

        servicioSuggestions.classList.remove('d-none');
    }

    dServicio.addEventListener('input', async function() {
        this.classList.remove('is-invalid-soft');
        const lista = await buscarServiciosCoincidencia(this.value || '');
        pintarSugerencias(lista);
    });

    dServicio.addEventListener('focus', async function() {
        const lista = await buscarServiciosCoincidencia(this.value || '');
        pintarSugerencias(lista);
    });

    document.addEventListener('click', function(e) {
        if (!servicioSuggestions.contains(e.target) && e.target !== dServicio) {
            servicioSuggestions.classList.add('d-none');
        }
    });

    btnNuevoServicioCatalogo.addEventListener('click', () => {
        const modal = new bootstrap.Modal(document.getElementById('modalNuevoServicioCatalogo'));
        nuevoNombreServicio.value = (dServicio.value || '').trim();
        modal.show();
    });

    btnGuardarNuevoServicioCatalogo.addEventListener('click', async () => {
        const nombre = (nuevoNombreServicio.value || '').trim();
        if (!nombre) {
            msgError('Ingresa el nombre del servicio.');
            return;
        }

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('/Servicios/Catalogo/Servicios/Nuevo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ nombre_servicio: nombre })
            });

            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) {
                msgError(json.message || 'No se pudo guardar el servicio.');
                return;
            }

            dServicio.value = (json.data && json.data.nombre_servicio) ? json.data.nombre_servicio : nombre.toUpperCase();
            const lista = await buscarServiciosCoincidencia('');
            pintarSugerencias(lista);
            bootstrap.Modal.getInstance(document.getElementById('modalNuevoServicioCatalogo')).hide();
        } catch (error) {
            console.error(error);
            msgError('Ocurrio un error al guardar el servicio.');
        }
    });

    btnAgregarDetalle.addEventListener('click', () => {
        const servicio = ($('dServicio').value || '').trim();
        const descripcion = ($('dDescripcion').value || '').trim();
        const tab = ($('dTab').value || '').trim();
        const real = ($('dReal').value || '').trim();
        const proceso = (dProceso?.value || '').trim();
        const idEmpleado = (dEmpleado?.value || '').trim();
        const idIncidencia = (dIncidencia?.value || '').trim();
        const empleadoNombre = dEmpleado?.options?.[dEmpleado.selectedIndex]?.text || '';
        const incidenciaNombre = dIncidencia?.options?.[dIncidencia.selectedIndex]?.text || '';

        if (!servicio || !descripcion) {
            msgError('Completa servicio y descripcion para agregar el detalle.');
            return;
        }

        if (!existeServicioEnCatalogo(servicio)) {
            $('dServicio').classList.add('is-invalid-soft');
            msgError('El servicio no existe en el catalogo. Selecciona uno valido o agregalo con el boton +.');
            return;
        }
        $('dServicio').classList.remove('is-invalid-soft');

        detallesNuevaOrden.push({
            servicio,
            descripcion,
            tab: tab || '0.00',
            real: real || '0.00',
            proceso_estado: proceso || 'PROCESO NORMAL',
            id_empleado_asignado: idEmpleado ? Number(idEmpleado) : null,
            id_incidencia: idIncidencia ? Number(idIncidencia) : null,
            empleado_nombre: idEmpleado ? empleadoNombre : '',
            incidencia_nombre: idIncidencia ? incidenciaNombre : ''
        });

        $('dServicio').value = '';
        $('dDescripcion').value = '';
        $('dTab').value = '';
        $('dReal').value = '';
        if (dProceso) dProceso.value = 'PROCESO NORMAL';
        if (dEmpleado) dEmpleado.value = '';
        if (dIncidencia) dIncidencia.value = '';
        renderDetalleNuevaOrden();
    });

    btnGuardarNuevaOrdenDemo.addEventListener('click', async () => {
        const payload = {
            no_orden: ($('nNoOrden').value || '').trim(),
            tipo_vehiculo: ($('nVehiculo').value || '').trim(),
            codigo_torre: ($('nTorre').value || '').trim(),
            estado: ($('nEstado').value || '').trim(),
            fecha_hora: ($('nFecha').value || '').trim(),
            fecha_promesa: ($('nPromesa').value || '').trim(),
            detalles: detallesNuevaOrden
        };

        if (!payload.no_orden || !payload.fecha_hora) {
            msgError('Completa No. Orden y Fecha/Hora.');
            return;
        }
        if (!payload.detalles.length) {
            msgError('Agrega al menos un detalle de servicio.');
            return;
        }

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch('/Servicios/OrdenesServicio/Guardar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const json = await response.json().catch(() => ({}));
            if (!response.ok || json.success === false) {
                msgError(json.message || 'No se pudo guardar la orden.');
                return;
            }

            msgOk('Orden guardada correctamente: ' + (json.no_orden || ''));
            detallesNuevaOrden.length = 0;
            renderDetalleNuevaOrden();
            document.getElementById('formNuevaOrden').reset();
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevaOrden'));
            if (modal) modal.hide();
        } catch (error) {
            console.error(error);
            msgError('Error al guardar la orden.');
        }
    });

    txtBuscar.addEventListener('input', filtrar);
    cmbEstado.addEventListener('change', filtrar);
    txtBuscarReporte?.addEventListener('input', filtrarReportes);
    cmbCategoriaReporte?.addEventListener('change', filtrarReportes);
    btnGuardarCambiosDetalleOrden?.addEventListener('click', guardarCambiosDetalleOrden);
    const modalLigasSeguimiento = $('modalLigasSeguimiento');
    modalLigasSeguimiento?.addEventListener('show.bs.modal', renderLigasSeguimiento);

    // Recuperación inicial por si venía bloqueado de una sesión previa.
    document.querySelectorAll('.modal-backdrop').forEach((bd) => bd.remove());
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');

    // Modo demo: forzar modales sin backdrop para evitar bloqueos.
    document.querySelectorAll('.modal').forEach((m) => {
        m.setAttribute('data-bs-backdrop', 'false');
        m.setAttribute('data-bs-keyboard', 'true');
    });

    filtrar();
    renderDetalleNuevaOrden();
    filtrarReportes();
    buscarServiciosCoincidencia('');
    initServicioFilter();
});
</script>
@endsection
