@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.0/css/fixedColumns.dataTables.min.css">
@endsection

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $estatusBadgeProd = [
        'PREPARANDO_MAQUINAS' => 'bg-info text-dark',
        'CALENTANDO_MAQUINA' => 'badge-warning-dark',
        'EN_ESPERA_MATERIALES' => 'badge-warning text-dark',
        'EN_PRODUCCION' => 'badge-secondary',
        'PAUSADA' => 'bg-warning text-dark',
        'REVISION_CALIDAD' => 'bg-info text-dark',
        'PREPARAR_EMPAQUE' => 'bg-primary',
        'ENROLLANDO' => 'bg-primary',
        'A_LONGITUD' => 'bg-primary',
        'CORTAR_FLEJAR' => 'bg-primary',
        'EMPACANDO' => 'bg-primary',
        'RUTA_TORNO' => 'bg-primary',
        'RUTA_TORNO_EXTERNO' => 'bg-warning text-dark',
        'RUTA_CORTE' => 'bg-primary',
        'CALIDAD_FINAL' => 'bg-info text-dark',
        'ENTREGA_ALMACEN' => 'bg-secondary',
        'TERMINADA' => 'badge-success-dark',
        'CANCELADA' => 'badge-danger-dark',
        'RECHAZADA_POR_CALIDAD' => 'badge-danger-dark',
        'PENDIENTE_PROGRAMACION' => 'bg-light text-dark border',
    ];
    $estatusBadgeCorto = [
        'EN_ESPERA_MATERIALES' => 'Esp. materiales',
        'PREPARANDO_MAQUINAS' => 'Prep. máquinas',
        'CALENTANDO_MAQUINA' => 'Llenando tinas',
        'EN_PRODUCCION' => 'En producción',
        'PAUSADA' => 'Pausada',
        'REVISION_CALIDAD' => 'Rev. calidad',
        'PREPARAR_EMPAQUE' => 'Prep. empaque',
        'ENROLLANDO' => 'Enrollando',
        'A_LONGITUD' => 'A longitud',
        'CORTAR_FLEJAR' => 'Cortar/flejar',
        'EMPACANDO' => 'Empacando',
        'RUTA_TORNO' => 'Torno',
        'RUTA_TORNO_EXTERNO' => 'Torno ext.',
        'RUTA_CORTE' => 'Corte tapa',
        'CALIDAD_FINAL' => 'Cal. final',
        'ENTREGA_ALMACEN' => 'A almacén',
        'TERMINADA' => 'Terminada',
        'CANCELADA' => 'Cancelada',
        'RECHAZADA_POR_CALIDAD' => 'Rechazada',
        'PENDIENTE_PROGRAMACION' => 'Pend. prog.',
    ];
    $prioridadIcon = [
        'alta' => ['fa-circle-exclamation', 'text-danger'],
        'media' => ['fa-circle-minus', 'text-warning'],
        'baja' => ['fa-circle-check', 'text-success'],
    ];
    $filtros = $filtros ?? [];
@endphp

<style>
.mesa-control-page .kpi-card {
    border: 1px solid var(--table-border, #e5e7eb);
    border-radius: 14px;
    padding: 1rem 1.1rem;
    background: #fff;
    height: 100%;
}
.mesa-control-page .kpi-card .kpi-valor {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1.1;
    color: #1e3a5f;
}
.mesa-control-page .ordenes-filters {
    background: var(--table-soft, #f9fafb);
    border: 1px solid var(--table-border, #e5e7eb);
    margin-bottom: 0.85rem;
    padding: 0.75rem 0.85rem !important;
    border-radius: 14px !important;
    box-shadow: none;
}
.mesa-control-page .ordenes-filters form {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.65rem 0.75rem;
    align-items: end;
}
.mesa-control-page .ordenes-filters .filter-field {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0.25rem;
    min-width: 0;
    max-width: none;
    flex: none;
}
.mesa-control-page .ordenes-filters .form-label {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: var(--table-muted, #6b7280);
    margin-bottom: 0;
    white-space: nowrap;
    line-height: 1.2;
}
.mesa-control-page .ordenes-filters .form-select-sm,
.mesa-control-page .ordenes-filters .form-control-sm {
    min-height: 32px;
    height: 32px;
    width: 100% !important;
    min-width: 0 !important;
    max-width: none !important;
    font-size: 0.8rem;
    padding: 0.2rem 0.5rem;
    background: #ffffff !important;
    border: 1px solid var(--table-border, #e5e7eb) !important;
    border-radius: 10px !important;
    color: var(--table-text, #111827) !important;
}
.mesa-control-page .ordenes-filters .filter-actions {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 0.45rem;
    padding-top: 0.15rem;
}
.mesa-control-page .ordenes-filters .filter-actions .btn-sm {
    min-height: 32px;
    height: 32px;
    padding: 0.2rem 0.85rem;
    font-size: 0.78rem;
    white-space: nowrap;
}
@media (max-width: 1199px) {
    .mesa-control-page .ordenes-filters form {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 768px) {
    .mesa-control-page .ordenes-filters form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .mesa-control-page .ordenes-filters .filter-actions {
        justify-content: stretch;
    }
    .mesa-control-page .ordenes-filters .filter-actions .btn-sm {
        flex: 1 1 auto;
    }
}
.mesa-control-page .ordenes-seguimiento {
    --seg-line: #dbe3ee;
    --seg-ink: #102d49;
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(1200px 180px at 0% 0%, rgba(16, 45, 73, 0.06), transparent 55%),
        radial-gradient(900px 160px at 100% 100%, rgba(34, 197, 94, 0.08), transparent 50%),
        linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid var(--table-border, #e5e7eb);
    border-radius: 18px;
    padding: 1rem 1.15rem 1.1rem;
    margin-bottom: 0.95rem;
}
.mesa-control-page .ordenes-seguimiento::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, #102d49 0%, #3b82f6 45%, #22c55e 100%);
}
.mesa-control-page .ordenes-seguimiento-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.9rem;
}
.mesa-control-page .ordenes-seguimiento-title {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 0;
}
.mesa-control-page .ordenes-seguimiento-icon {
    width: 2.1rem;
    height: 2.1rem;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #102d49, #1e4a73);
    color: #fff;
    font-size: 0.85rem;
    flex-shrink: 0;
    box-shadow: 0 8px 18px rgba(16, 45, 73, 0.18);
}
.mesa-control-page .ordenes-seguimiento-title h3 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--seg-ink);
    line-height: 1.2;
}
.mesa-control-page .ordenes-seguimiento-title p {
    margin: 0.1rem 0 0;
    font-size: 0.75rem;
    color: var(--table-muted, #6b7280);
}
.mesa-control-page .ordenes-seguimiento-pill {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #1e4a73;
    background: rgba(16, 45, 73, 0.08);
    border: 1px solid rgba(16, 45, 73, 0.12);
    border-radius: 999px;
    padding: 0.28rem 0.65rem;
    white-space: nowrap;
}
.mesa-control-page .ordenes-seguimiento-track {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.35rem;
    position: relative;
}
.mesa-control-page .ordenes-seguimiento-track::before {
    content: "";
    position: absolute;
    top: 1.15rem;
    left: 7%;
    right: 7%;
    height: 2px;
    background: linear-gradient(90deg, #f59e0b, #3b82f6, #6366f1, #0ea5e9, #22c55e);
    opacity: 0.35;
    z-index: 0;
}
.mesa-control-page .seg-step {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.45rem;
    min-width: 0;
    padding: 0.15rem 0.2rem 0.25rem;
    border: 0;
    background: transparent;
    transition: transform 0.2s ease;
}
.mesa-control-page .seg-step:hover {
    transform: translateY(-2px);
}
.mesa-control-page .seg-step-node {
    width: 2.3rem;
    height: 2.3rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 800;
    color: #fff;
    box-shadow: 0 8px 16px rgba(15, 23, 42, 0.12);
    border: 3px solid #fff;
    position: relative;
}
.mesa-control-page .seg-step-node i {
    font-size: 0.78rem;
}
.mesa-control-page .seg-step[data-tone="amber"] .seg-step-node { background: linear-gradient(135deg, #f59e0b, #d97706); }
.mesa-control-page .seg-step[data-tone="sky"] .seg-step-node { background: linear-gradient(135deg, #38bdf8, #0284c7); }
.mesa-control-page .seg-step[data-tone="orange"] .seg-step-node { background: linear-gradient(135deg, #fb923c, #ea580c); }
.mesa-control-page .seg-step[data-tone="indigo"] .seg-step-node { background: linear-gradient(135deg, #818cf8, #4f46e5); }
.mesa-control-page .seg-step[data-tone="cyan"] .seg-step-node { background: linear-gradient(135deg, #22d3ee, #0891b2); }
.mesa-control-page .seg-step[data-tone="blue"] .seg-step-node { background: linear-gradient(135deg, #60a5fa, #2563eb); }
.mesa-control-page .seg-step[data-tone="green"] .seg-step-node { background: linear-gradient(135deg, #4ade80, #16a34a); }
.mesa-control-page .seg-step-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #334155;
    line-height: 1.25;
    max-width: 100%;
}
.mesa-control-page .seg-step-num {
    display: block;
    font-size: 0.64rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94a3b8;
    margin-bottom: 0.1rem;
}
@media (max-width: 991px) {
    .mesa-control-page .ordenes-seguimiento-track {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        row-gap: 0.85rem;
    }
    .mesa-control-page .ordenes-seguimiento-track::before {
        display: none;
    }
}
@media (max-width: 575px) {
    .mesa-control-page .ordenes-seguimiento-track {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .mesa-control-page .ordenes-seguimiento-head {
        flex-direction: column;
        align-items: flex-start;
    }
}
.mesa-control-page .ordenes-table-meta {
    font-size: 0.82rem;
    color: var(--table-muted, #6b7280);
    margin-bottom: 0.65rem;
}
.mesa-control-page .ordenes-table-card.table-responsive {
    overflow: visible !important;
}
.mesa-control-page .ordenes-table-card div.dt-scroll,
.mesa-control-page .ordenes-table-card div.dataTables_scroll {
    border: 0 !important;
    margin: 0 !important;
    position: relative !important;
    width: 100% !important;
}
.mesa-control-page .ordenes-table-card div.dt-scroll-body,
.mesa-control-page .ordenes-table-card div.dataTables_scrollBody {
    overflow-x: auto !important;
    overflow-y: auto !important;
    width: 100% !important;
}
.mesa-control-page .modern-table tbody tr:hover {
    transform: none;
}
.mesa-control-page .mesa-tabla tbody tr {
    cursor: pointer;
}
.mesa-control-page .mesa-tabla tbody tr.mesa-fila-activa {
    background: #eef4ff !important;
}
.mesa-control-page .mesa-tabla tbody tr.mesa-fila-activa > td {
    background: #eef4ff !important;
}
body.mesa-control-dt table.dataTable tr > .dtfc-fixed-start,
body.mesa-control-dt table.dataTable tr > .dtfc-fixed-end {
    position: sticky !important;
    background-clip: padding-box !important;
}
body.mesa-control-dt table.dataTable thead tr > .dtfc-fixed-start,
body.mesa-control-dt table.dataTable thead tr > .dtfc-fixed-end {
    background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%) !important;
    z-index: 5 !important;
}
body.mesa-control-dt table.dataTable tbody tr > .dtfc-fixed-start,
body.mesa-control-dt table.dataTable tbody tr > .dtfc-fixed-end {
    background: #fff !important;
    z-index: 4 !important;
}
body.mesa-control-dt table.dataTable tbody tr:hover > .dtfc-fixed-start,
body.mesa-control-dt table.dataTable tbody tr:hover > .dtfc-fixed-end {
    background: #f3f4f6 !important;
}
body.mesa-control-dt table.dataTable tbody tr.mesa-fila-activa > .dtfc-fixed-start,
body.mesa-control-dt table.dataTable tbody tr.mesa-fila-activa > .dtfc-fixed-end {
    background: #eef4ff !important;
}
.mesa-control-page .mesa-tabla .badge {
    font-size: 0.65rem !important;
    font-weight: 650 !important;
    letter-spacing: 0.01em !important;
    line-height: 1.2 !important;
    padding: 0.2rem 0.42rem !important;
    border-radius: 999px !important;
    white-space: nowrap;
    max-width: 11.5rem;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}
.mesa-control-page .mesa-tabla .mesa-estatus-cell {
    min-width: 0;
    padding-left: 0.4rem;
    padding-right: 0.4rem;
}
.mesa-control-page .mesa-tabla .mesa-estatus-badges {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.2rem;
}
.mesa-control-page .mesa-tabla .mesa-estatus-badges .badge-tipo {
    font-size: 0.6rem !important;
    padding: 0.15rem 0.35rem !important;
    max-width: none;
}
.mesa-control-page .mesa-avance {
    min-width: 130px;
}
.mesa-control-page .mesa-avance .progress {
    height: 12px;
    border-radius: 999px;
    background: #e2e8f0;
    min-width: 72px;
}
.mesa-control-page .mesa-avance .progress-bar {
    border-radius: 999px;
}
.mesa-control-page .mesa-avance .avance-pct {
    font-size: 0.9rem;
    font-weight: 700;
    color: #1e3a5f;
    min-width: 3.2rem;
}
.mesa-control-page .mesa-acciones {
    white-space: nowrap;
}
.mesa-control-page .mesa-acciones .btn {
    width: 2.15rem;
    height: 2.15rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.55rem;
    font-size: 0.95rem;
}
.mesa-control-page .mesa-pedido-cell {
    min-width: 150px;
    max-width: 220px;
}
.mesa-control-page .mesa-pedido-folio {
    font-size: 0.68rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
}
.mesa-control-page .mesa-pedido-op {
    font-size: 0.62rem;
    color: #64748b;
    line-height: 1.25;
    white-space: normal;
    word-break: break-word;
}
.mesa-control-page .panel-card {
    border: 1px solid var(--table-border, #e5e7eb);
    border-radius: 18px;
    background: #fff;
    min-height: 520px;
    position: sticky;
    top: 1rem;
    padding: 0 !important;
    overflow: hidden;
}
.mesa-control-page .panel-placeholder {
    color: #94a3b8;
    text-align: center;
    padding: 4rem 1.5rem;
    font-size: 0.9rem;
}
.mesa-control-page .mesa-panel-placeholder-icon {
    width: 3.25rem;
    height: 3.25rem;
    margin: 0 auto;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--table-soft, #f9fafb);
    border: 1px solid var(--table-border, #e5e7eb);
    color: #64748b;
    font-size: 1.25rem;
}
.mesa-control-page #mesa-panel-contenido {
    height: 100%;
    min-height: 520px;
}
.mesa-control-page .mesa-panel-detalle {
    padding: 1rem 1.1rem 1.15rem;
}
.mesa-control-page .mesa-panel-header {
    margin-bottom: 0.9rem;
    padding-bottom: 0.85rem;
    border-bottom: 1px solid var(--table-border, #e5e7eb);
}
.mesa-control-page .mesa-panel-kicker {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--table-muted, #6b7280);
    margin-bottom: 0.15rem;
}
.mesa-control-page .mesa-panel-folio {
    font-size: 1.15rem;
    font-weight: 800;
    color: #102d49;
    line-height: 1.2;
}
.mesa-control-page .mesa-panel-close {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: 1px solid var(--table-border, #e5e7eb);
    background: var(--table-soft, #f9fafb);
    color: #475569;
    flex-shrink: 0;
}
.mesa-control-page .mesa-panel-close:hover {
    background: #f3f4f6;
    color: #111827;
}
.mesa-control-page .mesa-panel-section {
    margin-bottom: 0.95rem;
}
.mesa-control-page .mesa-panel-section-title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.78rem;
    font-weight: 700;
    color: #334155;
    margin-bottom: 0.55rem;
}
.mesa-control-page .mesa-panel-section-title i {
    color: #64748b;
    font-size: 0.8rem;
}
.mesa-control-page .mesa-panel-count {
    margin-left: auto;
    min-width: 1.35rem;
    height: 1.35rem;
    padding: 0 0.35rem;
    border-radius: 999px;
    background: var(--table-soft, #f9fafb);
    border: 1px solid var(--table-border, #e5e7eb);
    color: var(--table-muted, #6b7280);
    font-size: 0.7rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.mesa-control-page .mesa-panel-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    background: var(--table-soft, #f9fafb);
    border: 1px solid var(--table-border, #e5e7eb);
    border-radius: 14px;
    padding: 0.7rem 0.75rem;
}
.mesa-control-page .mesa-panel-info-item--full {
    grid-column: 1 / -1;
}
.mesa-control-page .mesa-panel-info-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--table-muted, #6b7280);
    margin-bottom: 0.1rem;
}
.mesa-control-page .mesa-panel-info-value {
    display: block;
    font-size: 0.84rem;
    font-weight: 700;
    color: #111827;
    word-break: break-word;
}
.mesa-control-page .mesa-panel-products {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
    max-height: 180px;
    overflow-y: auto;
}
.mesa-control-page .mesa-panel-product {
    border: 1px solid var(--table-border, #e5e7eb);
    border-radius: 12px;
    padding: 0.55rem 0.7rem;
    background: #fff;
}
.mesa-control-page .mesa-panel-product-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 0.25rem;
}
.mesa-control-page .mesa-panel-product-meta {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.72rem;
    color: var(--table-muted, #6b7280);
}
.mesa-control-page .mesa-panel-product-meta strong {
    color: #1e293b;
}
.mesa-control-page .mesa-panel-empty {
    border: 1px dashed var(--table-border, #e5e7eb);
    border-radius: 12px;
    padding: 0.85rem;
    text-align: center;
    font-size: 0.8rem;
    color: var(--table-muted, #6b7280);
    background: var(--table-soft, #f9fafb);
}
.mesa-control-page .mesa-panel-form .form-label {
    margin-bottom: 0.25rem;
}
.mesa-control-page .mesa-panel-form .form-select-sm,
.mesa-control-page .mesa-panel-form .form-control-sm {
    min-height: 34px !important;
    height: auto;
    font-size: 0.8rem !important;
    border-radius: 10px !important;
    box-shadow: none !important;
}
.mesa-control-page .mesa-panel-form textarea.form-control-sm {
    min-height: 68px !important;
}
.mesa-control-page .mesa-panel-actions {
    margin-top: auto;
    padding-top: 0.85rem;
    border-top: 1px solid var(--table-border, #e5e7eb);
}
.mesa-control-page .mesa-panel-readonly {
    border-radius: 12px;
    background: var(--table-soft, #f9fafb);
    border: 1px solid var(--table-border, #e5e7eb);
    color: #64748b;
    font-size: 0.82rem;
    padding: 0.75rem 0.85rem;
    margin-bottom: 0.85rem;
}
@media (max-width: 1199px) {
    .mesa-control-page .panel-card {
        position: static;
        min-height: auto;
        margin-top: 1rem;
    }
}
</style>

<div class="container-fluid format_page mesa-control-page">
    {{-- Encabezado --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-table-columns"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Mesa de Control de Producción</h2>
                        <p class="text-muted mb-0">Seguimiento de fabricación. Calidad en el detalle; empaque en su propio módulo.</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn btn-baseColor" data-bs-toggle="modal" data-bs-target="#modalNuevaOrden">
                        <i class="fa-solid fa-plus"></i> Nueva orden
                    </button>
                    <form method="POST" action="{{ route('produccion.ordenes.importar_pedidos') }}" class="d-inline"
                        onsubmit="return confirm('¿Importar todos los pedidos pendientes sin orden de producción?');">
                        @csrf
                        <button type="submit" class="btn btn-baseColor-light">
                            <i class="fa-solid fa-file-import"></i> Importar pedidos
                        </button>
                    </form>
                    <a href="{{ route('produccion.ordenes', request()->query()) }}" class="btn btn-baseColor-light">
                        <i class="fa-solid fa-rotate"></i> Actualizar
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-baseColor-light dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-ellipsis"></i> Más
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('produccion.especificaciones.tubo') }}">Especificaciones tubo</a></li>
                            <li><a class="dropdown-item" href="{{ route('produccion.especificaciones.conexiones') }}">Especificaciones conexiones</a></li>
                            <li><a class="dropdown-item" href="{{ route('produccion.especificaciones.flange') }}">Especificaciones flange</a></li>
                            <li><a class="dropdown-item" href="{{ route('produccion.recetas') }}">Recetas</a></li>
                            <li><a class="dropdown-item" href="{{ route('produccion.maquinas') }}">Catálogo de máquinas</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 rounded-4 py-2 mb-3">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning border-0 rounded-4 py-2 mb-3">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 rounded-4 py-2 mb-3">
            <ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- KPIs --}}
    <div class="row g-3 mb-3">
        @php
            $kpiCards = [
                ['label' => 'Pedidos pendientes', 'valor' => $kpis['pedidos_pendientes'] ?? 0, 'sub' => 'Por programar', 'color' => '#f59e0b'],
                ['label' => 'Preparación', 'valor' => $kpis['ordenes_programadas'] ?? 0, 'sub' => 'Prep. / Llenando tinas', 'color' => '#3b82f6'],
                ['label' => 'En extrusión', 'valor' => $kpis['en_produccion'] ?? 0, 'sub' => 'Produciendo', 'color' => '#6366f1'],
                ['label' => 'Esperando material', 'valor' => $kpis['esperando_material'] ?? 0, 'sub' => 'En espera', 'color' => '#f97316'],
                ['label' => 'Terminadas hoy', 'valor' => $kpis['terminadas_hoy'] ?? 0, 'sub' => 'Completadas', 'color' => '#22c55e'],
                ['label' => 'Canceladas', 'valor' => $kpis['canceladas_mes'] ?? 0, 'sub' => 'Este mes', 'color' => '#ef4444'],
            ];
        @endphp
        @foreach ($kpiCards as $kpi)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="kpi-card shadow-sm">
                    <div class="text-muted fs-8 mb-1">{{ $kpi['label'] }}</div>
                    <div class="kpi-valor" style="color: {{ $kpi['color'] }}">{{ $kpi['valor'] }}</div>
                    <div class="text-muted fs-9">{{ $kpi['sub'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="ordenes-seguimiento">
        <div class="ordenes-seguimiento-head">
            <div class="ordenes-seguimiento-title">
                <span class="ordenes-seguimiento-icon"><i class="fa-solid fa-route"></i></span>
                <div>
                    <h3>Seguimiento simplificado</h3>
                    <p>Flujo visual del proceso de fabricación, de materiales a almacén.</p>
                </div>
            </div>
            <span class="ordenes-seguimiento-pill">7 etapas</span>
        </div>
        <div class="ordenes-seguimiento-track">
            <div class="seg-step" data-tone="amber" title="{{ \App\Models\OrdenProduccion::$estatusAyuda['EN_ESPERA_MATERIALES'] }}">
                <span class="seg-step-node"><i class="fa-solid fa-boxes-stacked"></i></span>
                <span class="seg-step-label"><span class="seg-step-num">Paso 1</span>Espera materiales</span>
            </div>
            <div class="seg-step" data-tone="sky" title="{{ \App\Models\OrdenProduccion::$estatusAyuda['PREPARANDO_MAQUINAS'] }}">
                <span class="seg-step-node"><i class="fa-solid fa-gears"></i></span>
                <span class="seg-step-label"><span class="seg-step-num">Paso 2</span>Preparando</span>
            </div>
            <div class="seg-step" data-tone="orange" title="{{ \App\Models\OrdenProduccion::$estatusAyuda['CALENTANDO_MAQUINA'] }}">
                <span class="seg-step-node"><i class="fa-solid fa-temperature-high"></i></span>
                <span class="seg-step-label"><span class="seg-step-num">Paso 3</span>Llenando tinas</span>
            </div>
            <div class="seg-step" data-tone="indigo" title="{{ \App\Models\OrdenProduccion::$estatusAyuda['EN_PRODUCCION'] }}">
                <span class="seg-step-node"><i class="fa-solid fa-industry"></i></span>
                <span class="seg-step-label"><span class="seg-step-num">Paso 4</span>En extrusión</span>
            </div>
            <div class="seg-step" data-tone="cyan" title="{{ \App\Models\OrdenProduccion::$estatusAyuda['REVISION_CALIDAD'] }}">
                <span class="seg-step-node"><i class="fa-solid fa-clipboard-check"></i></span>
                <span class="seg-step-label"><span class="seg-step-num">Paso 5</span>Calidad</span>
            </div>
            <div class="seg-step" data-tone="blue" title="{{ \App\Models\OrdenProduccion::$estatusAyuda['PREPARAR_EMPAQUE'] }}">
                <span class="seg-step-node"><i class="fa-solid fa-box-open"></i></span>
                <span class="seg-step-label"><span class="seg-step-num">Paso 6</span>Empaque</span>
            </div>
            <div class="seg-step" data-tone="green" title="{{ \App\Models\OrdenProduccion::$estatusAyuda['TERMINADA'] }}">
                <span class="seg-step-node"><i class="fa-solid fa-warehouse"></i></span>
                <span class="seg-step-label"><span class="seg-step-num">Paso 7</span>Almacén / Fin</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        {{-- Tabla principal --}}
        <div class="col-xl-9">
            <div class="table-responsive ordenes-table-card">
                <div class="filters ordenes-filters">
                    <form method="GET" action="{{ route('produccion.ordenes') }}" id="filtroOrdenes">
                        <div class="filter-field">
                            <label class="form-label" for="filtro_fecha_desde">Desde</label>
                            <input type="date" class="form-control form-control-sm" id="filtro_fecha_desde" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}">
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="filtro_fecha_hasta">Hasta</label>
                            <input type="date" class="form-control form-control-sm" id="filtro_fecha_hasta" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}">
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="filtro_estatus">Estatus</label>
                            <select class="form-select form-select-sm" id="filtro_estatus" name="estatus" aria-label="Filtrar por estatus">
                                <option value="">Todos</option>
                                <option value="PENDIENTE_PROGRAMACION" {{ ($filtros['estatus'] ?? '') === 'PENDIENTE_PROGRAMACION' ? 'selected' : '' }}>Pendiente programación</option>
                                @foreach (\App\Models\OrdenProduccion::$estatusFlujo as $key => $label)
                                    <option value="{{ $key }}"
                                        title="{{ \App\Models\OrdenProduccion::$estatusAyuda[$key] ?? '' }}"
                                        {{ ($filtros['estatus'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="filtro_cliente">Cliente</label>
                            <select class="form-select form-select-sm" id="filtro_cliente" name="cliente_id" aria-label="Filtrar por cliente">
                                <option value="">Todos</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" {{ (int) ($filtros['cliente_id'] ?? 0) === (int) $cliente->id ? 'selected' : '' }}>
                                        {{ $cliente->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="filtro_maquina">Máquina</label>
                            <select class="form-select form-select-sm" id="filtro_maquina" name="maquina_id" aria-label="Filtrar por máquina">
                                <option value="">Todas</option>
                                @foreach ($maquinas as $maq)
                                    <option value="{{ $maq->id }}" {{ (int) ($filtros['maquina_id'] ?? 0) === (int) $maq->id ? 'selected' : '' }}>
                                        {{ $maq->codigo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="filtro_operador">Operador</label>
                            <select class="form-select form-select-sm" id="filtro_operador" name="operador_id" aria-label="Filtrar por operador">
                                <option value="">Todos</option>
                                @foreach ($empleados as $empleado)
                                    <option value="{{ $empleado->id }}" {{ (int) ($filtros['operador_id'] ?? 0) === (int) $empleado->id ? 'selected' : '' }}>
                                        {{ $empleado->primer_nombre }} {{ $empleado->apellido_paterno }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="filtro_turno">Turno</label>
                            <select class="form-select form-select-sm" id="filtro_turno" name="turno_id" aria-label="Filtrar por turno">
                                <option value="">Todos</option>
                                @foreach ($turnos as $turno)
                                    <option value="{{ $turno->id }}" {{ (int) ($filtros['turno_id'] ?? 0) === (int) $turno->id ? 'selected' : '' }}>
                                        {{ $turno->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="filter-field">
                            <label class="form-label" for="filtro_pedido">Pedido</label>
                            <input type="text" class="form-control form-control-sm" id="filtro_pedido" name="pedido" value="{{ $filtros['pedido'] ?? '' }}" placeholder="Folio...">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-baseColor-light btn-sm">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>
                            <a href="{{ route('produccion.ordenes') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                        </div>
                    </form>
                </div>

                <div class="d-flex justify-content-between align-items-center ordenes-table-meta">
                    <span>
                        <i class="fa-solid fa-list me-1"></i>
                        Órdenes y pedidos ({{ $filasMesa->count() }})
                    </span>
                    <span>
                        Turno: <strong class="text-dark">{{ $turnoActual?->nombre ?? 'Sin turno' }}</strong>
                    </span>
                </div>

                <table class="table table-striped table-hover display modern-table w-100 mesa-tabla" id="table">
                    <thead>
                        <tr>
                            <th style="width:42px" title="Prioridad"></th>
                            <th class="text-center">Acciones</th>
                            <th>Pedido</th>
                            <th>Avance</th>
                            <th>Cliente</th>
                            <th>Estatus</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Entrega</th>
                            <th>Máquina</th>
                            <th>Turno</th>
                            <th>Operador</th>
                        </tr>
                    </thead>
                    <tbody id="mesa-tabla-body">
                            @foreach ($filasMesa as $fila)
                                @php
                                    $prio = $prioridadIcon[$fila['prioridad']] ?? $prioridadIcon['media'];
                                @endphp
                                <tr class="mesa-fila"
                                    data-orden-id="{{ $fila['orden_id'] ?? '' }}"
                                    data-tipo="{{ $fila['tipo'] }}">
                                    <td class="text-center">
                                        <i class="fa-solid {{ $prio[0] }} {{ $prio[1] }} fa-lg" title="Prioridad {{ $fila['prioridad'] }}"></i>
                                    </td>
                                    <td class="text-center mesa-acciones td-actions">
                                        @if ($fila['orden_id'])
                                            <div class="d-inline-flex align-items-center gap-1">
                                            @if (!empty($fila['tiene_archivo_op']))
                                                <a href="{{ route('produccion.ordenes.archivo_op.ver', $fila['orden_id']) }}"
                                                    class="btn btn-success btn-sm"
                                                    title="{{ $fila['archivo_op_nombre'] ?? 'Ver archivo de la OP' }}"
                                                    onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-file-arrow-down"></i>
                                                </a>
                                            @endif
                                            @if (!in_array($fila['estatus'], ['TERMINADA', 'CANCELADA', 'RECHAZADA_POR_CALIDAD'], true))
                                                <button type="button"
                                                    class="btn btn-primary btn-sm js-cargar-archivo-op"
                                                    data-orden-id="{{ $fila['orden_id'] }}"
                                                    data-folio="{{ $fila['orden_folio'] ?? ('OP #' . $fila['orden_id']) }}"
                                                    data-tiene-archivo="{{ !empty($fila['tiene_archivo_op']) ? '1' : '0' }}"
                                                    data-nombre-archivo="{{ $fila['archivo_op_nombre'] ?? '' }}"
                                                    title="{{ !empty($fila['tiene_archivo_op']) ? 'Reemplazar archivo de la OP' : 'Cargar archivo de la OP' }}"
                                                    onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-upload"></i>
                                                </button>
                                            @endif
                                            @if ($fila['estatus'] === 'REVISION_CALIDAD')
                                                <a href="{{ route('produccion.ordenes.detalle', $fila['orden_id']) }}"
                                                    class="btn btn-primary btn-sm" title="Revisión de calidad"
                                                    onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-clipboard-check"></i>
                                                </a>
                                            @elseif (in_array($fila['estatus'], ['PREPARAR_EMPAQUE', 'ENROLLANDO', 'A_LONGITUD', 'CORTAR_FLEJAR'], true))
                                                <a href="{{ route('produccion.empaque.detalle', $fila['orden_id']) }}"
                                                    class="btn btn-primary btn-sm" title="Ir a Empaque"
                                                    onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-box-open"></i>
                                                </a>
                                            @else
                                                <button type="button"
                                                    class="btn btn-secondary btn-sm" disabled
                                                    title="Entrada al detalle disponible en Revisión de calidad o Empaque"
                                                    onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-clipboard-check"></i>
                                                </button>
                                            @endif
                                            @if (!empty($puedeCancelar) && !in_array($fila['estatus'], ['TERMINADA', 'CANCELADA', 'RECHAZADA_POR_CALIDAD', 'PENDIENTE_PROGRAMACION'], true))
                                                <button type="button"
                                                    class="btn btn-danger btn-sm js-cancelar-orden"
                                                    data-orden-id="{{ $fila['orden_id'] }}"
                                                    data-folio="{{ $fila['orden_folio'] ?? ('OP #' . $fila['orden_id']) }}"
                                                    title="Cancelar orden"
                                                    onclick="event.stopPropagation();">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                            @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="mesa-pedido-cell">
                                        <div class="mesa-pedido-folio" title="{{ $fila['pedido_folio'] ?? $fila['orden_folio'] ?? '' }}">{{ $fila['pedido_folio'] ?? $fila['orden_folio'] ?? '—' }}</div>
                                        @if ($fila['orden_folio'] && $fila['pedido_folio'])
                                            <div class="mesa-pedido-op" title="{{ $fila['orden_folio'] }}">{{ $fila['orden_folio'] }}</div>
                                        @endif
                                    </td>
                                    <td class="mesa-avance">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar bg-primary" style="width: {{ $fila['avance'] }}%"></div>
                                            </div>
                                            <span class="avance-pct">{{ $fila['avance'] }}%</span>
                                        </div>
                                    </td>
                                    <td>{{ $fila['cliente'] }}</td>
                                    <td class="mesa-estatus-cell">
                                        @php
                                            $estatusLabelFull = $fila['estatus_label'] ?? (\App\Models\OrdenProduccion::$estatusFlujo[$fila['estatus']] ?? $fila['estatus']);
                                            $estatusLabelCorto = $estatusBadgeCorto[$fila['estatus']] ?? $estatusLabelFull;
                                            $estatusTitle = trim(($estatusLabelFull !== $estatusLabelCorto ? $estatusLabelFull . ' — ' : '') . ($fila['estatus_ayuda'] ?? (\App\Models\OrdenProduccion::$estatusAyuda[$fila['estatus']] ?? '')));
                                        @endphp
                                        <div class="mesa-estatus-badges">
                                            <span class="badge {{ $estatusBadgeProd[$fila['estatus']] ?? 'badge-secondary' }}"
                                                title="{{ $estatusTitle }}">
                                                {{ $estatusLabelCorto }}
                                            </span>
                                            @if (!empty($fila['es_conexion']))
                                                <span class="badge badge-tipo bg-info text-dark" title="Conexión">Conex.</span>
                                            @elseif (!empty($fila['es_flange']))
                                                <span class="badge badge-tipo bg-warning text-dark" title="Flange">Flange</span>
                                            @endif
                                        </div>
                                        @if (!empty($fila['puede_ingresar_materiales']) || !empty($fila['tiene_orden_materiales']))
                                            <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                @if (!empty($fila['puede_ingresar_materiales']))
                                                    <button type="button"
                                                        class="btn btn-warning btn-sm js-ingresar-materiales"
                                                        data-orden-id="{{ $fila['orden_id'] }}"
                                                        title="Ingresar materiales">
                                                        <i class="fa-solid fa-boxes-stacked me-1"></i>Ingresar
                                                    </button>
                                                @endif
                                                @if (!empty($fila['tiene_orden_materiales']))
                                                    <a href="{{ route('produccion.ordenes.orden_materiales', $fila['orden_id']) }}"
                                                        class="btn btn-secondary btn-sm"
                                                        title="{{ $fila['orden_materiales_nombre'] ?? 'Ver orden de materiales' }}"
                                                        onclick="event.stopPropagation();">
                                                        <i class="fa-solid fa-paperclip"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif

                                        @if (!empty($fila['usa_avance_manual']) && !empty($fila['siguiente_estatus']))
                                            <form method="POST" action="{{ route('produccion.ordenes.estatus', $fila['orden_id']) }}"
                                                class="d-flex align-items-center gap-1 mt-2 mesa-avanzar-form"
                                                onclick="event.stopPropagation();">
                                                @csrf
                                                <input type="hidden" name="origen" value="mesa">
                                                <select name="estatus" class="form-select form-select-sm" style="max-width: 170px;">
                                                    <option value="{{ $fila['estatus'] }}">{{ $fila['estatus_label'] }} (actual)</option>
                                                    <option value="{{ $fila['siguiente_estatus'] }}" selected>
                                                        &#10142; {{ $fila['siguiente_estatus_label'] }}
                                                    </option>
                                                </select>
                                                <button type="submit" class="btn btn-blue btn-sm" title="Avanzar al siguiente paso">
                                                    <i class="fa-solid fa-forward-step"></i>
                                                </button>
                                            </form>
                                        @elseif (!empty($fila['es_pausada']))
                                            <form method="POST" action="{{ route('produccion.ordenes.estatus', $fila['orden_id']) }}"
                                                class="mt-2 mesa-avanzar-form" onclick="event.stopPropagation();">
                                                @csrf
                                                <input type="hidden" name="origen" value="mesa">
                                                <input type="hidden" name="estatus" value="EN_PRODUCCION">
                                                <button type="submit" class="btn btn-success btn-sm" title="Reanudar producción">
                                                    <i class="fa-solid fa-play me-1"></i>Reanudar
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="text-truncate" style="max-width:180px" title="{{ $fila['producto'] }}">{{ $fila['producto'] }}</td>
                                    <td class="fw-semibold">{{ $fila['cantidad_label'] }}</td>
                                    <td class="{{ !empty($fila['fecha_entrega_vencida']) ? 'text-danger fw-semibold' : '' }}">
                                        {{ $fila['fecha_entrega'] ?? '—' }}
                                    </td>
                                    <td>{{ $fila['maquina'] }}</td>
                                    <td>{{ $fila['turno'] }}</td>
                                    <td>{{ $fila['operador'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
            </div>
        </div>

        {{-- Panel lateral --}}
        <div class="col-xl-3">
            <div class="panel-card" id="mesa-panel-contenedor">
                <div class="panel-placeholder" id="mesa-panel-placeholder">
                    <div class="mesa-panel-placeholder-icon mb-3">
                        <i class="fa-solid fa-hand-pointer"></i>
                    </div>
                    <div class="fw-semibold text-secondary mb-1">Sin orden seleccionada</div>
                    Seleccione una fila de la tabla para ver y editar su programación.
                </div>
                <div id="mesa-panel-contenido" class="d-none"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal nueva orden --}}
<div class="modal fade" id="modalNuevaOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-secondary"><i class="fa-solid fa-plus me-2"></i>Nueva orden de producción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body modern-form">
                <form action="{{ route('produccion.ordenes.store') }}" method="POST" id="form-nueva-orden">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fs-8">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-8">Turno <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="turno_id" required>
                                <option value="">—</option>
                                @foreach ($turnos as $turno)
                                    <option value="{{ $turno->id }}" {{ (int) old('turno_id', optional($turnoActual)->id) === (int) $turno->id ? 'selected' : '' }}>
                                        {{ $turno->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-8">Máquina inicial</label>
                            <select class="form-select form-select-sm" name="maquina_id" id="orden-maquina-id">
                                <option value="">— Asignar después —</option>
                                @foreach ($seguimientoMaquinas as $item)
                                    <option value="{{ $item['maquina']->id }}" {{ (int) old('maquina_id') === (int) $item['maquina']->id ? 'selected' : '' }}
                                        {{ $item['orden'] ? 'disabled' : '' }}>
                                        {{ $item['maquina']->codigo }} {{ $item['orden'] ? '(ocupada)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-8">Operador</label>
                            <select class="form-select form-select-sm" name="operador_id">
                                <option value="">—</option>
                                @foreach ($empleados as $empleado)
                                    <option value="{{ $empleado->id }}">{{ $empleado->primer_nombre }} {{ $empleado->apellido_paterno }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-8">Supervisor</label>
                            <select class="form-select form-select-sm" name="supervisor_id">
                                <option value="">—</option>
                                @foreach ($empleados as $empleado)
                                    <option value="{{ $empleado->id }}">{{ $empleado->primer_nombre }} {{ $empleado->apellido_paterno }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fs-8">Pedido de venta (opcional)</label>
                            <select class="form-select form-select-sm" name="pedido_id" id="orden-pedido-id">
                                <option value="">— Sin pedido —</option>
                                @foreach ($pedidosDisponibles as $pedido)
                                    <option value="{{ $pedido->id }}"
                                        data-bloquea-maquina="{{ $pedido->estatus === 'PENDIENTE_OC' ? '1' : '0' }}">
                                        {{ $pedido->folio }} — {{ $pedido->cliente->nombre ?? '' }} ({{ $pedido->estatus }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text fs-9" id="orden-pedido-help">Si selecciona pedido, se copian los productos.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fs-8">Observaciones</label>
                            <input type="text" class="form-control form-control-sm" name="observaciones" maxlength="500">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-nueva-orden" class="btn btn-blue btn-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Crear orden
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal cargar archivo de la OP --}}
<div class="modal fade" id="modalCargarArchivoOp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="form-cargar-archivo-op" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title text-secondary mb-0">
                        <i class="fa-solid fa-file-arrow-up me-1"></i>
                        Archivo de la OP <span id="ao-folio" class="fw-normal"></span>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="fs-9 text-muted mb-2" id="ao-ayuda">
                        Sube el documento que recibieron para esta orden de producción.
                    </p>
                    <div class="alert alert-light border rounded-3 py-2 px-2 fs-9 d-none mb-2" id="ao-actual"></div>
                    <label class="form-label fs-8" for="ao-archivo">Archivo</label>
                    <input type="file" class="form-control form-control-sm" name="archivo_op" id="ao-archivo"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar" required>
                    <div class="form-text fs-9">PDF, Word, Excel, imagen o ZIP (máx. 15 MB).</div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-blue btn-sm">
                        <i class="fa-solid fa-upload"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal ingresar materiales --}}
<div class="modal fade" id="modalIngresarMateriales" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-secondary">
                    <i class="fa-solid fa-boxes-stacked me-2"></i>Ingresar materiales
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="form-ingresar-materiales" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-2 fs-8">
                        <strong id="im-folio"></strong>
                        <span class="text-muted" id="im-pedido"></span>
                        <div class="text-muted" id="im-cliente"></div>
                    </div>
                    <div id="im-alerta" class="alert alert-warning border-0 fs-8 d-none"></div>
                    <h6 class="text-marino fs-8 text-uppercase mb-2">Productos a fabricar</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm fs-8 mb-0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-end">Cantidad</th>
                                    <th>Receta</th>
                                </tr>
                            </thead>
                            <tbody id="im-productos"></tbody>
                        </table>
                    </div>
                    <h6 class="text-marino fs-8 text-uppercase mb-2">Materiales (BOM) y stock</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm fs-8 mb-0">
                            <thead>
                                <tr>
                                    <th>Material</th>
                                    <th class="text-end">Requerido</th>
                                    <th class="text-end">Disponible</th>
                                    <th class="text-end">Faltante</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="im-materiales"></tbody>
                        </table>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fs-8">Orden de materiales (archivo para almacén) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" name="orden_materiales" id="im-archivo"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" required>
                        <div class="form-text fs-9">Se guarda en la misma fila de la mesa para consulta del almacenista.</div>
                    </div>
                    <div>
                        <label class="form-label fs-8">Observaciones</label>
                        <input type="text" class="form-control form-control-sm" name="observaciones" maxlength="1000">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-blue btn-sm" id="im-btn-confirmar" disabled>
                        <i class="fa-solid fa-warehouse me-1"></i> Tomar materiales y pasar a Preparando máquinas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if (!empty($puedeCancelar))
{{-- Modal cancelar orden --}}
<div class="modal fade" id="modalCancelarOrdenMesa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="form-cancelar-orden">
                @csrf
                <input type="hidden" name="origen" value="mesa">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger">
                        <i class="fa-solid fa-ban me-2"></i>Cancelar orden <span id="cancel-folio-mesa"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning border-0 fs-8">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        Se liberará la máquina y, si ya se tomaron materiales, se reintegrarán a la ubicación
                        <strong>MATERIAL REGRESADO</strong> para su traslado posterior. Esta acción no se puede revertir.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fs-8">Motivo de cancelación <span class="text-danger">*</span></label>
                        <select name="motivo_cancelacion_id" id="cancel-motivo-mesa" class="form-select form-select-sm" required>
                            <option value="">— Seleccione un motivo —</option>
                            @foreach ($motivosCancelacion->groupBy('categoria') as $categoria => $motivos)
                                <optgroup label="{{ \App\Models\MotivoCancelacionProduccion::$categorias[$categoria] ?? $categoria }}">
                                    @foreach ($motivos as $motivo)
                                        <option value="{{ $motivo->id }}" data-requiere="{{ $motivo->requiere_nota ? '1' : '0' }}">
                                            {{ $motivo->nombre }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fs-8">Nota / detalle <span class="text-danger cancel-nota-req-mesa d-none">*</span></label>
                        <textarea name="motivo_cancelacion_nota" id="cancel-nota-mesa" class="form-control form-control-sm"
                            rows="2" maxlength="500" placeholder="Explique la cancelación (obligatorio en algunos motivos)"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-ban me-1"></i> Confirmar cancelación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
(function () {
    var urlPanel = @json(route('produccion.ordenes.panel', ['id' => '__ID__']));
    var urlCancelar = @json(route('produccion.ordenes.cancelar', ['id' => '__ID__']));
    var urlMateriales = @json(route('produccion.ordenes.materiales', ['id' => '__ID__']));
    var urlIngresar = @json(route('produccion.ordenes.ingresar_materiales', ['id' => '__ID__']));
    var panelContenido = document.getElementById('mesa-panel-contenido');
    var panelPlaceholder = document.getElementById('mesa-panel-placeholder');
    var filaActiva = null;
    var modalMaterialesEl = document.getElementById('modalIngresarMateriales');
    var formIngresar = document.getElementById('form-ingresar-materiales');
    var btnConfirmar = document.getElementById('im-btn-confirmar');
    var ordenMaterialesActual = null;

    function obtenerModalMateriales() {
        if (!modalMaterialesEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return null;
        }
        return bootstrap.Modal.getOrCreateInstance(modalMaterialesEl);
    }

    function cerrarPanel() {
        if (filaActiva) filaActiva.classList.remove('mesa-fila-activa');
        filaActiva = null;
        if (panelContenido) {
            panelContenido.classList.add('d-none');
            panelContenido.innerHTML = '';
        }
        if (panelPlaceholder) panelPlaceholder.classList.remove('d-none');
    }

    function cargarPanel(ordenId) {
        if (!ordenId || !panelContenido || !panelPlaceholder) return;
        panelPlaceholder.classList.add('d-none');
        panelContenido.classList.remove('d-none');
        panelContenido.innerHTML = '<div class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>';

        fetch(urlPanel.replace('__ID__', ordenId), { headers: { 'Accept': 'text/html' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                panelContenido.innerHTML = html;
                var btnCerrar = document.getElementById('mesa-panel-cerrar');
                if (btnCerrar) btnCerrar.addEventListener('click', cerrarPanel);
            })
            .catch(function () {
                panelContenido.innerHTML = '<div class="alert alert-danger fs-8">No se pudo cargar el detalle.</div>';
            });
    }

    function cargarDatosMateriales(ordenId) {
        if (!ordenId) return;

        ordenMaterialesActual = ordenId;
        if (formIngresar) {
            formIngresar.action = urlIngresar.replace('__ID__', ordenId);
        }

        var tbodyP = document.getElementById('im-productos');
        var tbodyM = document.getElementById('im-materiales');
        var alerta = document.getElementById('im-alerta');

        if (tbodyP) tbodyP.innerHTML = '<tr><td colspan="3" class="text-muted">Cargando...</td></tr>';
        if (tbodyM) tbodyM.innerHTML = '<tr><td colspan="5" class="text-muted">Cargando...</td></tr>';
        if (alerta) alerta.classList.add('d-none');
        if (btnConfirmar) btnConfirmar.disabled = true;
        if (document.getElementById('im-archivo')) document.getElementById('im-archivo').value = '';

        fetch(urlMateriales.replace('__ID__', ordenId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                var folioEl = document.getElementById('im-folio');
                var pedidoEl = document.getElementById('im-pedido');
                var clienteEl = document.getElementById('im-cliente');
                if (folioEl) folioEl.textContent = data.folio || ('OP #' + ordenId);
                if (pedidoEl) pedidoEl.textContent = data.pedido ? (' · Pedido ' + data.pedido) : '';
                if (clienteEl) clienteEl.textContent = data.cliente || '';

                if (tbodyP) {
                    tbodyP.innerHTML = '';
                    (data.productos || []).forEach(function (p) {
                        var tr = document.createElement('tr');
                        tr.innerHTML = '<td>' + (p.nombre || '') + '</td>'
                            + '<td class="text-end">' + Number(p.cantidad || 0).toFixed(2) + '</td>'
                            + '<td>' + (p.tiene_receta ? 'Sí' : '<span class="text-danger">Sin receta</span>') + '</td>';
                        tbodyP.appendChild(tr);
                    });
                    if (!(data.productos || []).length) {
                        tbodyP.innerHTML = '<tr><td colspan="3" class="text-muted">Sin productos</td></tr>';
                    }
                }

                if (tbodyM) {
                    tbodyM.innerHTML = '';
                    (data.materiales || []).forEach(function (m) {
                        var tr = document.createElement('tr');
                        var badge = m.suficiente
                            ? '<span class="badge bg-success">OK</span>'
                            : '<span class="badge bg-danger">Falta</span>';
                        tr.innerHTML = '<td>' + (m.nombre || '') + (m.sku ? ' <span class="text-muted">(' + m.sku + ')</span>' : '') + '</td>'
                            + '<td class="text-end">' + Number(m.requerido || 0).toFixed(2) + (m.unidad ? ' ' + m.unidad : '') + '</td>'
                            + '<td class="text-end">' + Number(m.disponible || 0).toFixed(2) + '</td>'
                            + '<td class="text-end">' + Number(m.faltante || 0).toFixed(2) + '</td>'
                            + '<td>' + badge + '</td>';
                        tbodyM.appendChild(tr);
                    });
                    if (!(data.materiales || []).length) {
                        tbodyM.innerHTML = '<tr><td colspan="5" class="text-muted">Sin materiales en receta</td></tr>';
                    }
                }

                if (!alerta || !btnConfirmar) return;

                if (data.materiales_ya_tomados) {
                    alerta.textContent = 'Los materiales de esta orden ya fueron tomados.';
                    alerta.classList.remove('d-none');
                    btnConfirmar.disabled = true;
                } else if (!data.puede_tomar) {
                    alerta.textContent = 'Todavía no hay stock suficiente. Cuando exista inventario completo podrá tomar los materiales.';
                    alerta.classList.remove('d-none');
                    btnConfirmar.disabled = true;
                } else {
                    alerta.classList.add('d-none');
                    btnConfirmar.disabled = false;
                }
            })
            .catch(function () {
                if (alerta) {
                    alerta.textContent = 'No se pudo cargar la lista de materiales.';
                    alerta.classList.remove('d-none');
                }
                if (btnConfirmar) btnConfirmar.disabled = true;
            });
    }

    function abrirIngresarMateriales(ordenId) {
        if (!ordenId) return;
        cargarDatosMateriales(ordenId);

        var intentar = function (intentos) {
            var modal = obtenerModalMateriales();
            if (modal) {
                modal.show();
                return;
            }
            if (intentos <= 0) {
                window.alert('No se pudo abrir el modal de materiales. Recargue la página e intente de nuevo.');
                return;
            }
            window.setTimeout(function () { intentar(intentos - 1); }, 50);
        };
        intentar(40);
    }

    var tablaBody = document.getElementById('mesa-tabla-body');
    if (tablaBody) {
        tablaBody.addEventListener('click', function (event) {
            var fila = event.target.closest('tr.mesa-fila');
            if (!fila || !tablaBody.contains(fila)) return;
            if (event.target.closest('.js-ingresar-materiales, a, button, input, select, label, textarea, form')) {
                return;
            }
            var ordenId = fila.getAttribute('data-orden-id');
            if (!ordenId) return;

            if (filaActiva) filaActiva.classList.remove('mesa-fila-activa');
            filaActiva = fila;
            fila.classList.add('mesa-fila-activa');
            cargarPanel(ordenId);
        });
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.js-ingresar-materiales');
        if (!btn) return;
        event.preventDefault();
        event.stopPropagation();
        abrirIngresarMateriales(btn.getAttribute('data-orden-id'));
    });

    var formCancelar = document.getElementById('form-cancelar-orden');
    var modalCancelarEl = document.getElementById('modalCancelarOrdenMesa');
    var motivoMesa = document.getElementById('cancel-motivo-mesa');
    var notaReqMesa = document.querySelector('.cancel-nota-req-mesa');
    var notaMesa = document.getElementById('cancel-nota-mesa');

    if (motivoMesa && notaReqMesa && notaMesa) {
        motivoMesa.addEventListener('change', function () {
            var opt = motivoMesa.options[motivoMesa.selectedIndex];
            var requiere = opt && opt.getAttribute('data-requiere') === '1';
            notaReqMesa.classList.toggle('d-none', !requiere);
            notaMesa.required = requiere;
        });
    }

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.js-cancelar-orden');
        if (!btn || !formCancelar || !modalCancelarEl) return;
        event.preventDefault();
        event.stopPropagation();

        var ordenId = btn.getAttribute('data-orden-id');
        formCancelar.action = urlCancelar.replace('__ID__', ordenId);

        var folioSpan = document.getElementById('cancel-folio-mesa');
        if (folioSpan) folioSpan.textContent = btn.getAttribute('data-folio') || '';
        if (motivoMesa) motivoMesa.value = '';
        if (notaMesa) { notaMesa.value = ''; notaMesa.required = false; }
        if (notaReqMesa) notaReqMesa.classList.add('d-none');

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalCancelarEl).show();
        }
    });

    if (modalMaterialesEl) {
        modalMaterialesEl.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            var ordenId = trigger && trigger.getAttribute
                ? trigger.getAttribute('data-orden-id')
                : ordenMaterialesActual;
            if (ordenId && ordenId !== ordenMaterialesActual) {
                cargarDatosMateriales(ordenId);
            }
        });
    }

    if (formIngresar) {
        formIngresar.addEventListener('submit', function (event) {
            if (btnConfirmar && btnConfirmar.disabled) {
                event.preventDefault();
                return;
            }
            if (!confirm('¿Confirmar toma de materiales? Se descontará del inventario, se registrarán movimientos y la orden pasará a Preparando máquinas.')) {
                event.preventDefault();
            }
        });
    }

    var abrirOrden = @json($abrirOrdenId ?? 0);
    if (abrirOrden > 0) {
        var fila = document.querySelector('.mesa-fila[data-orden-id="' + abrirOrden + '"]');
        if (fila) {
            fila.click();
        } else {
            cargarPanel(abrirOrden);
        }
    }

    (function enlazarCargaArchivoOp() {
        var modalEl = document.getElementById('modalCargarArchivoOp');
        var form = document.getElementById('form-cargar-archivo-op');
        var folioEl = document.getElementById('ao-folio');
        var ayudaEl = document.getElementById('ao-ayuda');
        var actualEl = document.getElementById('ao-actual');
        var input = document.getElementById('ao-archivo');
        if (!modalEl || !form) return;

        document.querySelectorAll('.js-cargar-archivo-op').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var ordenId = btn.getAttribute('data-orden-id');
                var folio = btn.getAttribute('data-folio') || '';
                var tiene = btn.getAttribute('data-tiene-archivo') === '1';
                var nombre = btn.getAttribute('data-nombre-archivo') || '';
                form.action = '{{ url('/produccion') }}/' + ordenId + '/archivo-op';
                if (folioEl) folioEl.textContent = folio;
                if (input) input.value = '';
                if (actualEl) {
                    if (tiene && nombre) {
                        actualEl.textContent = 'Archivo actual: ' + nombre + ' (se reemplazará al guardar).';
                        actualEl.classList.remove('d-none');
                    } else {
                        actualEl.textContent = '';
                        actualEl.classList.add('d-none');
                    }
                }
                if (ayudaEl) {
                    ayudaEl.textContent = tiene
                        ? 'Puedes reemplazar el documento de esta orden.'
                        : 'Sube el documento que recibieron para esta orden de producción.';
                }
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            });
        });
    })();

    var selectPedido = document.getElementById('orden-pedido-id');
    var selectMaquina = document.getElementById('orden-maquina-id');
    var helpPedido = document.getElementById('orden-pedido-help');

    function actualizarRestriccionMaquina() {
        if (!selectPedido || !selectMaquina) return;
        var opt = selectPedido.options[selectPedido.selectedIndex];
        var bloquea = opt && opt.getAttribute('data-bloquea-maquina') === '1';
        if (bloquea) {
            selectMaquina.value = '';
            selectMaquina.disabled = true;
            if (helpPedido) helpPedido.textContent = 'Pedido PENDIENTE_OC: no asigne máquina hasta tener materiales.';
        } else {
            selectMaquina.disabled = false;
            if (helpPedido) helpPedido.textContent = 'Si selecciona pedido, se copian los productos.';
        }
    }

    if (selectPedido) {
        selectPedido.addEventListener('change', actualizarRestriccionMaquina);
        actualizarRestriccionMaquina();
    }
})();
</script>
@endsection

@section('js')
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
<script>
$(document).ready(function () {
    document.body.classList.add('mesa-control-dt');

    var $table = $('#table');
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
        order: [],
        scrollY: isMobileTable ? false : '500px',
        paging: true,
        columnDefs: [
            { targets: 0, orderable: false, width: '42px' },
            { targets: 1, orderable: false, className: 'td-actions text-center', width: isMobileTable ? '120px' : '150px' },
            { targets: 2, width: '180px' },
            { targets: 3, orderable: false, width: '140px' },
            { targets: 5, orderable: false }
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
            emptyTable: 'No hay registros con los filtros actuales.'
        },
        initComplete: function () {
            adjustOrdenesTable(this.api());
        }
    });

    function adjustOrdenesTable(api) {
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
        adjustOrdenesTable();
    });

    table.on('draw.dt column-visibility.dt length.dt', function () {
        adjustOrdenesTable();
    });
});
</script>
@endsection
