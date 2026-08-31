@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $estatusFinales = \App\Models\OrdenProduccion::ESTATUS_FINALES;
    $editable = !in_array($orden->estatus, $estatusFinales, true);
    $lineasFabricar = $orden->detalles->filter(fn ($d) => $d->esFabricar());
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
        'PENDIENTE_PROGRAMACION' => 'bg-light text-dark border',
    ];
    $enEmpaque = !$orden->esFlange() && in_array($orden->estatus, [
        'REVISION_CALIDAD', 'PREPARAR_EMPAQUE', 'ENROLLANDO', 'A_LONGITUD', 'CORTAR_FLEJAR', 'EMPACANDO',
    ], true);
    $estatusFlujoLabels = \App\Models\OrdenProduccion::$estatusFlujo;
    $estatusAyuda = \App\Models\OrdenProduccion::$estatusAyuda;
    $secuenciaFlujo = $orden->secuenciaActiva();
    $siguienteEstatus = $orden->siguienteEstatus();
    $usaAvanceManual = $orden->usaAvanceManual();
    $indiceActual = $orden->indiceFlujo();
    $esFlange = $orden->esFlange();
    $esConexion = $orden->esConexion();
    $esSoloFlange = $orden->esSoloFlange();
@endphp

<style>
    .orden-detalle-page {
        --od-surface: #ffffff;
        --od-soft: #f8fafc;
        --od-border: #e5e7eb;
        --od-text: #111827;
        --od-muted: #6b7280;
        --od-accent: #1e3a5f;
        --od-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }
    .orden-detalle-summary {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.85rem;
        margin-bottom: 1.1rem;
    }
    .orden-detalle-summary .summary-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        background: #fff;
        border: 1px solid var(--od-border);
        border-radius: 16px;
        padding: 0.95rem 1rem;
        min-height: 88px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }
    .orden-detalle-summary .summary-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        transform: translateY(-1px);
    }
    .orden-detalle-summary .summary-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        flex-shrink: 0;
        font-size: 0.95rem;
        background: #eff6ff;
        color: #1e3a5f;
    }
    .orden-detalle-summary .summary-item.is-estatus .summary-icon {
        background: #e0f2fe;
        color: #0369a1;
    }
    .orden-detalle-summary .summary-item.is-pedido .summary-icon {
        background: #ecfdf5;
        color: #047857;
    }
    .orden-detalle-summary .summary-item.is-cliente .summary-icon {
        background: #fff7ed;
        color: #c2410c;
    }
    .orden-detalle-summary .summary-item.is-maquina .summary-icon {
        background: #f5f3ff;
        color: #6d28d9;
    }
    .orden-detalle-summary .summary-item.is-produccion .summary-icon {
        background: #fef3c7;
        color: #b45309;
    }
    .orden-detalle-summary .summary-content {
        min-width: 0;
        flex: 1;
    }
    .orden-detalle-summary .summary-item .label,
    .orden-detalle-empaque-grid .label {
        display: block;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--od-muted);
        margin-bottom: 0.3rem;
    }
    .orden-detalle-summary .summary-item .value,
    .orden-detalle-empaque-grid .value {
        font-size: 0.98rem;
        font-weight: 700;
        color: var(--od-accent);
        word-break: break-word;
        line-height: 1.25;
    }
    .orden-detalle-summary .summary-item .value-sub {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.72rem;
        font-weight: 500;
        color: var(--od-muted);
    }
    .orden-detalle-empaque-grid .summary-item {
        background: #fff;
        border: 1px solid var(--od-border);
        border-radius: 14px;
        padding: 0.8rem 0.95rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        min-height: 72px;
    }
    .orden-detalle-card {
        border: 1px solid var(--od-border);
        border-radius: 18px;
        background: var(--od-surface);
        padding: 1.15rem 1.25rem;
        margin-bottom: 1.1rem;
        box-shadow: var(--od-shadow);
    }
    .orden-detalle-card .card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--od-accent);
        margin-bottom: 0.25rem;
    }
    .orden-detalle-card .card-title i {
        opacity: 0.85;
    }
    .orden-detalle-flujo {
        display: flex;
        align-items: flex-start;
        gap: 0;
        overflow-x: auto;
        margin-bottom: 1rem;
        padding: 0.85rem 0.5rem 0.5rem;
        background: var(--od-soft);
        border: 1px solid var(--od-border);
        border-radius: 14px;
        scrollbar-width: thin;
    }
    .orden-flujo-step {
        flex: 1 0 100px;
        min-width: 100px;
        max-width: 150px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        padding: 0 0.3rem;
    }
    .orden-flujo-step:not(:last-child)::after {
        content: "";
        position: absolute;
        top: 15px;
        left: calc(50% + 16px);
        width: calc(100% - 32px);
        height: 2px;
        border-radius: 999px;
        background: #e5e7eb;
        z-index: 0;
    }
    .orden-flujo-step.is-done:not(:last-child)::after {
        background: #22c55e;
    }
    .orden-flujo-step.is-current:not(:last-child)::after {
        background: linear-gradient(90deg, #2563eb, #e5e7eb);
    }
    .orden-flujo-dot {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: 0.75rem;
        font-weight: 700;
        border: 2px solid #d1d5db;
        background: #fff;
        color: #9ca3af;
        position: relative;
        z-index: 1;
    }
    .orden-flujo-step.is-done .orden-flujo-dot {
        background: #22c55e;
        border-color: #22c55e;
        color: #fff;
    }
    .orden-flujo-step.is-current .orden-flujo-dot {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    .orden-flujo-label {
        margin-top: 0.45rem;
        font-size: 0.72rem;
        font-weight: 600;
        line-height: 1.25;
        color: #9ca3af;
    }
    .orden-flujo-step.is-done .orden-flujo-label {
        color: #15803d;
    }
    .orden-flujo-step.is-current .orden-flujo-label {
        color: #1e3a5f;
        font-weight: 700;
    }
    .orden-detalle-actions {
        background: var(--od-soft);
        border: 1px solid var(--od-border);
        border-radius: 14px;
        padding: 0.9rem 1rem;
    }
    .orden-detalle-empaque-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
        margin-bottom: 0.85rem;
    }
    .orden-fabricacion-card .fab-head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding-bottom: 0.9rem;
        border-bottom: 1px solid var(--od-border);
    }
    .orden-fabricacion-card .fab-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: #eff6ff;
        color: #1e3a5f;
        flex-shrink: 0;
    }
    .orden-fabricacion-card .fab-steps {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.55rem;
    }
    .orden-fabricacion-card .fab-steps .fab-step {
        font-size: 0.7rem;
        font-weight: 600;
        color: #475569;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
    }
    .orden-detalle-page .orden-linea-card {
        border: 1px solid var(--od-border);
        border-radius: 16px;
        background: #fff;
        padding: 0;
        margin-bottom: 1rem;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    }
    .orden-detalle-page .orden-linea-card .linea-head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 0.85rem;
        padding: 1rem 1.1rem;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        border-bottom: 1px solid var(--od-border);
    }
    .orden-detalle-page .orden-linea-card .linea-kpis {
        display: grid;
        grid-template-columns: repeat(3, minmax(84px, 1fr));
        gap: 0.45rem;
        min-width: 260px;
    }
    .orden-detalle-page .orden-linea-card .linea-kpi {
        background: #fff;
        border: 1px solid var(--od-border);
        border-radius: 12px;
        padding: 0.45rem 0.55rem;
        text-align: center;
    }
    .orden-detalle-page .orden-linea-card .linea-kpi .kpi-label {
        display: block;
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--od-muted);
        margin-bottom: 0.15rem;
    }
    .orden-detalle-page .orden-linea-card .linea-kpi .kpi-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--od-accent);
        line-height: 1.1;
    }
    .orden-detalle-page .orden-linea-card .linea-body {
        padding: 1rem 1.1rem;
    }
    .orden-detalle-page .orden-linea-card .linea-section-title {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--od-accent);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 0.75rem;
    }
    .orden-detalle-page .orden-salida-card {
        border: 1px solid var(--od-border);
        border-radius: 14px;
        background: #fff;
        padding: 0;
        margin-bottom: 0.85rem;
        overflow: hidden;
    }
    .orden-detalle-page .orden-salida-card .salida-head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 0.9rem;
        background: #f8fafc;
        border-bottom: 1px solid var(--od-border);
    }
    .orden-detalle-page .orden-salida-card .salida-body {
        padding: 0.85rem 0.9rem;
    }
    .orden-detalle-page .orden-salida-card.border-success {
        border-color: #86efac !important;
    }
    .orden-detalle-page .orden-salida-card.border-success .salida-head {
        background: #f0fdf4;
    }
    .orden-detalle-page .orden-salida-card.border-danger {
        border-color: #fca5a5 !important;
    }
    .orden-detalle-page .orden-salida-card.border-danger .salida-head {
        background: #fef2f2;
    }
    .orden-detalle-page .orden-linea-card .orden-detalle-actions {
        margin-top: 0.25rem;
    }
    @media (max-width: 768px) {
        .orden-detalle-page .orden-linea-card .linea-kpis {
            grid-template-columns: repeat(3, 1fr);
            min-width: 0;
            width: 100%;
        }
    }
    #registro-oee.orden-oee-card {
        border: 1px solid var(--od-border);
        border-radius: 18px;
        box-shadow: var(--od-shadow);
        background: #fff;
        padding: 1.15rem 1.25rem;
        margin-bottom: 1.1rem;
    }
    #registro-oee .oee-head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding-bottom: 0.9rem;
        border-bottom: 1px solid var(--od-border);
    }
    #registro-oee .oee-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: #ecfeff;
        color: #0e7490;
        flex-shrink: 0;
    }
    #registro-oee .oee-score {
        min-width: 88px;
        padding: 0.55rem 0.75rem;
        border-radius: 14px;
        background: linear-gradient(160deg, #1e3a5f 0%, #0e7490 100%);
        color: #fff;
        text-align: center;
        box-shadow: 0 8px 18px rgba(14, 116, 144, 0.22);
    }
    #registro-oee .oee-score .score-label {
        display: block;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        opacity: 0.85;
    }
    #registro-oee .oee-score .score-value {
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1.1;
    }
    #registro-oee .oee-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(95px, 1fr));
        gap: 0.35rem;
        margin-bottom: 0.85rem;
    }
    #registro-oee .oee-meta .meta-item {
        background: #f8fafc;
        border: 1px solid var(--od-border);
        border-radius: 10px;
        padding: 0.35rem 0.5rem;
        min-height: 0;
    }
    #registro-oee .oee-meta .meta-item .label {
        display: block;
        font-size: 0.58rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        color: var(--od-muted);
        margin-bottom: 0.08rem;
        line-height: 1.1;
    }
    #registro-oee .oee-meta .meta-item .value {
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--od-accent);
        word-break: break-word;
        line-height: 1.2;
    }
    #registro-oee .oee-section {
        border: 1px solid var(--od-border);
        border-radius: 14px;
        background: #f8fafc;
        padding: 0.85rem 0.95rem;
        margin-bottom: 0.75rem;
    }
    #registro-oee .oee-section-title {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--od-accent);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 0.7rem;
    }
    #registro-oee .oee-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.85rem;
    }
    #registro-oee .oee-legend span {
        font-size: 0.72rem;
        font-weight: 600;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
        border: 1px solid var(--od-border);
        background: #fff;
        color: #475569;
    }
    #registro-oee .oee-legend .cap {
        color: #0369a1;
        border-color: #bae6fd;
        background: #f0f9ff;
    }
    #registro-oee .oee-legend .calc {
        color: #334155;
        border-color: #e2e8f0;
        background: #f8fafc;
    }
    #registro-oee .oee-paros {
        border-top: 1px solid var(--od-border);
        margin-top: 1rem;
        padding-top: 1rem;
    }
    #registro-oee .oee-paros-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--od-accent);
        margin-bottom: 0.75rem;
    }
    #registro-oee .oee-table {
        border: 1px solid var(--od-border);
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    #registro-oee .oee-table table {
        margin-bottom: 0;
    }
    #registro-oee .oee-table thead th {
        background: #f8fafc;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--od-muted);
        border-bottom-color: var(--od-border);
    }
    .orden-detalle-page .modern-form .form-control-sm,
    .orden-detalle-page .modern-form .form-select-sm {
        border-radius: 10px !important;
    }
    @media (max-width: 1200px) {
        .orden-detalle-summary {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 992px) {
        .orden-detalle-summary,
        .orden-detalle-empaque-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 576px) {
        .orden-detalle-summary,
        .orden-detalle-empaque-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid format_page orden-detalle-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.ordenes', ['abrir_orden' => $orden->id]) }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Órdenes
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Detalle de producción · {{ $orden->folio }}</h2>
                        <p class="text-muted mb-0">
                            @if ($esConexion)
                                <span class="badge bg-info text-dark">CONEXIÓN</span>
                                @if ($orden->ruta_flange)
                                    · Ruta {{ $orden->ruta_flange }}
                                @endif
                                · Piezas → calidad → acabado
                            @elseif ($esSoloFlange)
                                <span class="badge bg-warning text-dark">FLANGE</span>
                                @if ($orden->ruta_flange)
                                    · Ruta {{ $orden->ruta_flange }}
                                @endif
                                · Inyección → calidad → torno/corte → almacén
                            @else
                                Medidas reales, merma e inspección de calidad
                            @endif
                        </p>
                    </div>
                </div>
                @if (!$esFlange && $orden->estatus !== 'CANCELADA')
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <a class="btn btn-baseColor-light" href="{{ route('produccion.empaque.detalle', $orden->id) }}">
                        <i class="fa-solid fa-box-open"></i> Empaque
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            @if (session('reproceso_id'))
                <a class="ms-2 fw-semibold" href="{{ route('produccion.reproceso.detalle', session('reproceso_id')) }}">
                    Abrir reproceso →
                </a>
            @endif
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

    <div class="orden-detalle-summary">
        <div class="summary-item is-estatus">
            <div class="summary-icon"><i class="fa-solid fa-flag"></i></div>
            <div class="summary-content">
                <span class="label">Estatus</span>
                <span class="badge {{ $estatusBadgeProd[$orden->estatus] ?? 'badge-secondary' }} fs-9">
                    {{ $orden->estatus_texto }}
                </span>
            </div>
        </div>
        <div class="summary-item is-pedido">
            <div class="summary-icon"><i class="fa-solid fa-file-invoice"></i></div>
            <div class="summary-content">
                <span class="label">Pedido</span>
                <span class="value">{{ $orden->pedido?->folio ?? '—' }}</span>
            </div>
        </div>
        <div class="summary-item is-cliente">
            <div class="summary-icon"><i class="fa-solid fa-building-user"></i></div>
            <div class="summary-content">
                <span class="label">Cliente</span>
                <span class="value" title="{{ $orden->pedido?->cliente?->nombre }}">
                    {{ \Illuminate\Support\Str::limit($orden->pedido?->cliente?->nombre ?? '—', 28) }}
                </span>
            </div>
        </div>
        <div class="summary-item is-maquina">
            <div class="summary-icon"><i class="fa-solid fa-gears"></i></div>
            <div class="summary-content">
                <span class="label">Máquina</span>
                <span class="value">{{ $orden->maquina?->codigo ?? '—' }}</span>
                @if ($orden->maquina?->nombre)
                    <span class="value-sub">{{ \Illuminate\Support\Str::limit($orden->maquina->nombre, 24) }}</span>
                @endif
            </div>
        </div>
        <div class="summary-item is-produccion">
            <div class="summary-icon">
                <i class="fa-solid {{ $esFlange ? 'fa-cubes' : 'fa-ruler-horizontal' }}"></i>
            </div>
            <div class="summary-content">
                @if ($esFlange)
                    <span class="label">Piezas producidas</span>
                    <span class="value">{{ (int) $orden->total_piezas_producidas }}</span>
                @else
                    <span class="label">Metros producidos</span>
                    <span class="value">{{ number_format($orden->total_metros_producidos, 2) }} m</span>
                @endif
            </div>
        </div>
    </div>

    <div class="orden-detalle-card">
        <h5 class="card-title">
            <i class="fa-solid fa-diagram-project me-2"></i>Avance del proceso
        </h5>
        <p class="text-muted fs-8 mb-3">
            El proceso solo avanza hacia adelante, un paso a la vez. No es posible regresar a un estatus anterior ni saltarse pasos.
        </p>

        <div class="orden-detalle-flujo" role="list" aria-label="Secuencia del proceso">
            @foreach ($secuenciaFlujo as $i => $codEstatus)
                @php
                    $esActual = $codEstatus === $orden->estatus;
                    $completado = ($indiceActual >= 0 && $i < $indiceActual)
                        || ($orden->estatus === 'TERMINADA' && $i <= $indiceActual);
                    $estadoPaso = $esActual ? 'is-current' : ($completado ? 'is-done' : 'is-pending');
                @endphp
                <div class="orden-flujo-step {{ $estadoPaso }}" role="listitem"
                    title="{{ $orden->ayudaEstatusPara($codEstatus) }}">
                    <div class="orden-flujo-dot">
                        @if ($completado && !$esActual)
                            <i class="fa-solid fa-check"></i>
                        @elseif ($esActual)
                            <i class="fa-solid fa-circle" style="font-size:0.45rem;"></i>
                        @else
                            {{ $i + 1 }}
                        @endif
                    </div>
                    <div class="orden-flujo-label">{{ $orden->etiquetaEstatusPara($codEstatus) }}</div>
                </div>
            @endforeach
        </div>

        @if ($editable)
            <div class="orden-detalle-actions modern-form d-flex flex-wrap align-items-end gap-3">
                @if ($orden->estatus === 'EN_ESPERA_MATERIALES')
                    <div class="alert alert-warning border-0 mb-0 py-2 px-3 fs-8">
                        <i class="fa-solid fa-truck-ramp-box me-1"></i>
                        Para avanzar, use el botón «Ingresar materiales» en la mesa de control.
                    </div>
                @elseif ($orden->estatus === 'PAUSADA')
                    <form method="POST" action="{{ route('produccion.ordenes.estatus', $orden->id) }}" class="modern-form">
                        @csrf
                        <input type="hidden" name="estatus" value="EN_PRODUCCION">
                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-play me-1"></i> Reanudar producción
                        </button>
                    </form>
                @elseif ($usaAvanceManual && $siguienteEstatus)
                    <form method="POST" action="{{ route('produccion.ordenes.estatus', $orden->id) }}" class="modern-form d-flex align-items-end gap-2 flex-wrap flex-grow-1">
                        @csrf
                        <div style="min-width: 260px; flex: 1 1 260px;">
                            <label class="form-label" for="orden-estatus-select">Avanzar a</label>
                            <select name="estatus" id="orden-estatus-select" class="form-select">
                                <option value="{{ $orden->estatus }}">
                                    {{ $orden->etiquetaEstatusPara($orden->estatus) }} (actual)
                                </option>
                                <option value="{{ $siguienteEstatus }}" selected
                                    data-ayuda="{{ $orden->ayudaEstatusPara($siguienteEstatus) }}">
                                    &#10142; {{ $orden->etiquetaEstatusPara($siguienteEstatus) }}
                                </option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-baseColor">
                            <i class="fa-solid fa-forward-step me-1"></i> Avanzar
                        </button>
                        <span class="text-muted fs-8" id="orden-estatus-ayuda">
                            {{ $orden->ayudaEstatusPara($siguienteEstatus) }}
                        </span>
                    </form>
                @else
                    <div class="text-muted fs-8">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        @if ($esConexion)
                            Este estatus avanza al aceptar calidad de conexiones en la sección de abajo.
                        @elseif ($esSoloFlange)
                            Este estatus avanza al aceptar calidad (post-inyección) en la sección de abajo.
                        @else
                            Este estatus avanza desde las secciones de <strong>calidad</strong> y <strong>empaque</strong> de abajo.
                        @endif
                    </div>
                @endif

                <div class="ms-auto d-flex gap-2">
                    @if ($orden->estatus === 'EN_PRODUCCION')
                        <form method="POST" action="{{ route('produccion.ordenes.estatus', $orden->id) }}" class="modern-form">
                            @csrf
                            <input type="hidden" name="estatus" value="PAUSADA">
                            <button type="submit" class="btn btn-outline-warning">
                                <i class="fa-solid fa-pause me-1"></i> Pausar
                            </button>
                        </form>
                    @endif
                    @if (!empty($puedeCancelar))
                        <button type="button" class="btn btn-outline-danger"
                            data-bs-toggle="modal" data-bs-target="#modalCancelarOrden">
                            <i class="fa-solid fa-ban me-1"></i> Cancelar orden
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="orden-detalle-card orden-fabricacion-card">
        <div class="fab-head">
            <div class="d-flex align-items-start gap-3">
                <div class="fab-icon">
                    <i class="fa-solid {{ $esConexion ? 'fa-diagram-project' : ($esSoloFlange ? 'fa-gears' : 'fa-industry') }}"></i>
                </div>
                <div>
                    <h5 class="card-title mb-1">
                        @if ($esConexion)
                            Fabricación y calidad de conexiones
                        @elseif ($esSoloFlange)
                            Inyección y calidad flange
                        @else
                            Producción e inspección
                        @endif
                    </h5>
                    <p class="text-muted fs-8 mb-0">
                        @if ($esConexion)
                            Registre piezas fabricadas y valide calidad por lote.
                        @elseif ($esSoloFlange)
                            Registre piezas inyectadas y valide calidad por lote.
                        @else
                            Registre salidas y complete el proceso de inspección.
                        @endif
                    </p>
                    <div class="fab-steps">
                        @if ($esConexion)
                            <span class="fab-step">Fabricar</span>
                            <span class="fab-step">Calidad</span>
                            @if (($orden->ruta_flange ?? '') === 'CORTE')
                                <span class="fab-step">Corte / termofusión</span>
                            @elseif (($orden->ruta_flange ?? '') === 'TORNO_EXTERNO')
                                <span class="fab-step">Maquinado externo</span>
                            @else
                                <span class="fab-step">Acabado</span>
                            @endif
                            <span class="fab-step">Calidad final</span>
                            <span class="fab-step">Almacén</span>
                        @elseif ($esSoloFlange)
                            <span class="fab-step">Inyectar</span>
                            <span class="fab-step">Calidad</span>
                            @if (($orden->ruta_flange ?? '') === 'CORTE')
                                <span class="fab-step">Corte de tapa</span>
                            @elseif (($orden->ruta_flange ?? '') === 'TORNO_EXTERNO')
                                <span class="fab-step">Torno externo</span>
                            @else
                                <span class="fab-step">Torno</span>
                            @endif
                            <span class="fab-step">Calidad final</span>
                            <span class="fab-step">Almacén</span>
                        @else
                            <span class="fab-step">Espesores ±{{ number_format(\App\Models\InspeccionCalidadOrdenTrabajo::TOLERANCIA_ERROR_PCT, 0) }}%</span>
                            <span class="fab-step">Co-extrusora</span>
                            <span class="fab-step">Tatuadora</span>
                            <span class="fab-step">Aceptado</span>
                        @endif
                    </div>
                </div>
            </div>
            <span class="badge {{ $estatusBadgeProd[$orden->estatus] ?? 'badge-secondary' }} fs-9 align-self-start">
                {{ $orden->estatus_texto }}
            </span>
        </div>

        @forelse ($lineasFabricar as $detalle)
            @include('Produccion.partials.orden_detalle_linea', compact('detalle', 'orden', 'editable', 'ubicaciones', 'responsablesProceso'))
        @empty
            <p class="text-muted text-center py-4 mb-0">
                Esta orden no tiene líneas de fabricación
                @if ($esConexion)
                    de conexiones
                @elseif ($esSoloFlange)
                    de flange
                @else
                    de tubo
                @endif.
            </p>
        @endforelse
    </div>

    @include('Produccion.partials.registro_oee')

    @if ($esFlange && $editable && in_array($orden->estatus, ['RUTA_TORNO', 'RUTA_TORNO_EXTERNO', 'RUTA_CORTE', 'CALIDAD_FINAL', 'ENTREGA_ALMACEN'], true))
        <div class="orden-detalle-card">
            <h5 class="card-title">
                <i class="fa-solid fa-gears me-2"></i>
                {{ $orden->etiquetaEstatusPara($orden->estatus) }}
            </h5>
            <p class="text-muted fs-8 mb-3">
                {{ $orden->ayudaEstatusActual() }}
            </p>

            @if (in_array($orden->estatus, ['RUTA_TORNO', 'RUTA_TORNO_EXTERNO', 'RUTA_CORTE'], true) && $siguienteEstatus)
                <div class="alert alert-info border-0 fs-8 mb-0">
                    Al terminar el maquinado/corte, avance a <strong>{{ $orden->etiquetaEstatusPara($siguienteEstatus) }}</strong>.
                </div>
            @elseif ($orden->estatus === 'CALIDAD_FINAL' && $siguienteEstatus)
                <div class="alert alert-info border-0 fs-8 mb-0">
                    Verificar {{ $esConexion ? 'conexiones' : 'flanges' }} sin imperfecciones. Si pasan, avance a <strong>Entrega a almacén</strong>.
                </div>
            @elseif ($orden->estatus === 'ENTREGA_ALMACEN')
                <div class="alert alert-warning border-0 fs-8 mb-3">
                    Confirme ubicación destino y cierre la OP. El pedido quedará listo para carga.
                </div>
                <form method="POST" action="{{ route('produccion.ordenes.estatus', $orden->id) }}" class="modern-form orden-detalle-actions row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="estatus" value="TERMINADA">
                    <div class="col-md-6">
                        <label class="form-label">Ubicación destino</label>
                        <select name="ubicacion_destino_id" class="form-select">
                            <option value="">— Opcional —</option>
                            @foreach ($ubicaciones as $ubicacion)
                                <option value="{{ $ubicacion->id_ubicacion }}"
                                    @selected((int) ($orden->ubicacion_destino_id ?? 0) === (int) $ubicacion->id_ubicacion)>
                                    {{ $ubicacion->folio_interno }} — {{ $ubicacion->descripcion }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success"
                            onclick="return confirm('¿Terminar OP y liberar a almacén?');">
                            <i class="fa-solid fa-warehouse me-1"></i> Entregar y terminar
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    @if (!$esFlange && ($enEmpaque || in_array($orden->estatus, ['PREPARAR_EMPAQUE', 'ENROLLANDO', 'A_LONGITUD', 'CORTAR_FLEJAR', 'TERMINADA'], true)))
        <div class="orden-detalle-card">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h5 class="card-title mb-1">
                        <i class="fa-solid fa-box-open me-2"></i>Empaque y almacenamiento
                    </h5>
                    <p class="text-muted fs-8 mb-0">
                        Este paso lo opera el módulo <strong>Empaque</strong> (puede ser otro responsable).
                    </p>
                </div>
                @if ($orden->estatus !== 'TERMINADA')
                    <a class="btn btn-baseColor btn-sm" href="{{ route('produccion.empaque.detalle', $orden->id) }}">
                        <i class="fa-solid fa-box-open me-1"></i> Ir a Empaque
                    </a>
                @endif
            </div>

            <div class="orden-detalle-empaque-grid">
                <div class="summary-item">
                    <span class="label">Tipo empaque</span>
                    <span class="value">{{ \App\Models\TipoEmpaque::etiqueta($orden->tipo_empaque) }}</span>
                </div>
                <div class="summary-item">
                    <span class="label">Longitud objetivo</span>
                    <span class="value">{{ $orden->longitud_objetivo_m !== null ? number_format((float) $orden->longitud_objetivo_m, 2) . ' m' : '—' }}</span>
                </div>
                <div class="summary-item">
                    <span class="label">Nave / ubicación destino</span>
                    <span class="value">{{ $orden->etiquetaNaveDestino() }}</span>
                </div>
                <div class="summary-item">
                    <span class="label">Estatus</span>
                    <span class="badge {{ $estatusBadgeProd[$orden->estatus] ?? 'badge-secondary' }} fs-9">
                        {{ $orden->estatus_texto }}
                    </span>
                </div>
            </div>

            @if ($orden->estatus === 'TERMINADA')
                <div class="alert alert-success border-0 fs-8 mb-0">
                    <i class="fa-solid fa-circle-check me-1"></i>
                    Tubería almacenada en {{ $orden->etiquetaNaveDestino() }}. Orden terminada.
                    <a class="ms-2" href="{{ route('almacen.cargas') }}">Ir a Cargas</a>
                    <a class="ms-2" href="{{ route('produccion.empaque.detalle', $orden->id) }}">Ver en Empaque</a>
                </div>
            @else
                <div class="alert alert-info border-0 fs-8 mb-0">
                    Responsable: {{ $responsablesProceso[$orden->estatus] ?? 'Operador de Empaque' }}.
                    Continúe el flujo en el módulo Empaque.
                </div>
            @endif
        </div>
    @endif
</div>

@if (!empty($puedeCancelar) && $editable)
    <div class="modal fade" id="modalCancelarOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('produccion.ordenes.cancelar', $orden->id) }}" class="modern-form">
                    @csrf
                    <input type="hidden" name="origen" value="detalle">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-danger fw-bold">
                            <i class="fa-solid fa-ban me-2"></i>Cancelar orden {{ $orden->folio }}
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
                            <label class="form-label">Motivo de cancelación <span class="text-danger">*</span></label>
                            <select name="motivo_cancelacion_id" id="cancel-motivo-detalle" class="form-select" required>
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
                            <label class="form-label">Nota / detalle <span class="text-danger cancel-nota-req d-none">*</span></label>
                            <textarea name="motivo_cancelacion_nota" id="cancel-nota-detalle" class="form-control"
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
        const motivoSel = document.getElementById('cancel-motivo-detalle');
        const notaReq = document.querySelector('.cancel-nota-req');
        const notaInput = document.getElementById('cancel-nota-detalle');
        if (motivoSel && notaReq && notaInput) {
            motivoSel.addEventListener('change', function () {
                const opt = motivoSel.options[motivoSel.selectedIndex];
                const requiere = opt && opt.getAttribute('data-requiere') === '1';
                notaReq.classList.toggle('d-none', !requiere);
                notaInput.required = requiere;
            });
        }
    })();

    (function () {
        const select = document.getElementById('orden-estatus-select');
        const ayuda = document.getElementById('orden-estatus-ayuda');
        if (!select || !ayuda) {
            return;
        }
        select.addEventListener('change', function () {
            const opcion = select.options[select.selectedIndex];
            ayuda.textContent = opcion.getAttribute('data-ayuda') || '';
        });
    })();
</script>
@endsection
