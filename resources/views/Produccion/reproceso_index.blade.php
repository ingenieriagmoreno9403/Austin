@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $estatusBadge = [
        'DISPONIBLE' => 'bg-warning text-dark',
        'CORTADA' => 'bg-info text-dark',
        'PESADA' => 'bg-primary',
        'REPORTADA' => 'bg-secondary',
        'TRITURADA' => 'bg-dark',
        'SACA_PESADA' => 'bg-info text-dark',
        'IDENTIFICADA' => 'bg-primary',
        'LISTO_PELETIZADO' => 'bg-warning text-dark',
        'EN_PELETIZADO' => 'bg-primary',
        'PELETIZADO' => 'bg-dark',
        'SACA_RESINA_DESMONTADA' => 'bg-info text-dark',
        'RESINA_IDENTIFICADA' => 'bg-primary',
        'SACA_RESINA_PESADA' => 'bg-secondary',
        'RESINA_ALMACENADA' => 'bg-warning text-dark',
        'PRODUCCION_REPORTADA' => 'bg-secondary',
        'KILOS_RESINA_SISTEMA' => 'bg-info text-dark',
        'STOCK_RESINA' => 'badge-success-dark',
        'CANCELADA' => 'badge-danger-dark',
    ];
    $tieneFiltros = filled(request('q')) || ($filtro !== 'activos');
    $totalLotes = $lotes->count();
@endphp

<style>
    .reproceso-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .reproceso-filters form {
        gap: 0.35rem 0.65rem;
    }
    .reproceso-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
        max-width: none;
    }
    .reproceso-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .reproceso-filters .form-select-sm,
    .reproceso-filters .form-control-sm {
        min-height: 28px;
        height: 28px;
        font-size: 0.78rem;
        padding: 0.15rem 0.4rem;
        width: auto;
        min-width: 120px;
        max-width: 280px;
        background: #ffffff !important;
        border: 1px solid var(--table-border, #e5e7eb) !important;
        border-radius: 10px !important;
        color: var(--table-text, #111827) !important;
    }
    .reproceso-filters .form-control-sm.filter-search {
        min-width: 200px;
        max-width: 280px;
    }
    .reproceso-filters .form-select-sm.filter-cola {
        min-width: 180px;
        max-width: 240px;
    }
    .reproceso-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    .reproceso-table-card .td-actions {
        white-space: nowrap !important;
    }
    .reproceso-table-card .td-actions .btn {
        display: inline-flex !important;
        margin: 0 2px !important;
    }
    .reproceso-kpi {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.85rem;
    }
    .reproceso-kpi .kpi-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        border: 1px solid var(--table-border, #e5e7eb);
        background: #fff;
        font-size: 0.78rem;
        color: var(--table-muted, #6b7280);
    }
    .reproceso-kpi .kpi-chip strong {
        color: #1e3a5f;
        font-size: 0.9rem;
    }
    .reproceso-hint {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        border-radius: 14px;
        padding: 0.65rem 0.85rem;
        font-size: 0.78rem;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0.85rem;
    }
    @media (max-width: 768px) {
        .reproceso-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .reproceso-filters .form-select-sm,
        .reproceso-filters .form-control-sm {
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
                        <i class="fa-solid fa-recycle"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Reproceso</h2>
                        <p class="text-muted mb-0">
                            Calidad rechazada → triturado → peletizado → stock resina
                            — {{ number_format($totalLotes) }} lotes en vista
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <a class="btn btn-baseColor-light" href="{{ route('produccion.ordenes') }}">
                        <i class="fa-solid fa-industry"></i> Producción
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
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="reproceso-kpi">
        <span class="kpi-chip">
            <i class="fa-solid fa-bolt"></i>
            Activos <strong>{{ number_format($conteos['activos'] ?? 0) }}</strong>
        </span>
        <span class="kpi-chip">
            <i class="fa-solid fa-scissors"></i>
            Triturado <strong>{{ number_format($conteos['triturado'] ?? 0) }}</strong>
        </span>
        <span class="kpi-chip">
            <i class="fa-solid fa-cubes"></i>
            Peletizado <strong>{{ number_format($conteos['peletizado'] ?? 0) }}</strong>
        </span>
        <span class="kpi-chip">
            <i class="fa-solid fa-warehouse"></i>
            Almacén <strong>{{ number_format($conteos['almacen'] ?? 0) }}</strong>
        </span>
    </div>

    <div class="reproceso-hint">
        <i class="fa-solid fa-circle-info me-1"></i>
        Los lotes se generan al <strong>rechazar una salida</strong> en calidad de la OP
        (ubicación <strong>{{ $ubicacion->folio_interno ?? 'RECHAZADOS POR CALIDAD' }}</strong>).
        No hay altas manuales. Al inicio se asigna la <strong>máquina de reproceso</strong>.
    </div>

    <div class="row mt-1">
        <div class="table-responsive reproceso-table-card">
            <div class="filters reproceso-filters">
                <form method="GET" action="{{ route('produccion.reproceso') }}" class="d-flex flex-wrap align-items-center" id="filtroReproceso">
                    <div class="filter-field">
                        <label class="form-label" for="filtro_q">Buscar</label>
                        <input type="text" class="form-control form-control-sm filter-search" id="filtro_q" name="q"
                            value="{{ request('q') }}" placeholder="REP, OP o etiqueta" aria-label="Buscar">
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_estatus">Cola</label>
                        <select class="form-select form-select-sm filter-cola" id="filtro_estatus" name="estatus" aria-label="Filtrar por cola">
                            <option value="activos" {{ $filtro === 'activos' ? 'selected' : '' }}>
                                Activos ({{ $conteos['activos'] ?? 0 }})
                            </option>
                            <option value="triturado" {{ $filtro === 'triturado' ? 'selected' : '' }}>
                                Triturado ({{ $conteos['triturado'] ?? 0 }})
                            </option>
                            <option value="peletizado" {{ $filtro === 'peletizado' ? 'selected' : '' }}>
                                Peletizado ({{ $conteos['peletizado'] ?? 0 }})
                            </option>
                            <option value="almacen" {{ $filtro === 'almacen' ? 'selected' : '' }}>
                                Almacén ({{ $conteos['almacen'] ?? 0 }})
                            </option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    @if ($tieneFiltros)
                        <a href="{{ route('produccion.reproceso') }}" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                            <i class="fa-solid fa-xmark"></i> Limpiar
                        </a>
                    @endif
                </form>
            </div>

            <table class="table table-striped table-hover display modern-table w-100" id="table">
                <thead>
                    <tr>
                        <th class="text-center">Acciones</th>
                        <th>Lote</th>
                        <th>Origen</th>
                        <th>OP / Pedido</th>
                        <th>Máquina</th>
                        <th>Ubicación</th>
                        <th>Kg</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lotes as $lote)
                        <tr>
                            <td class="td-actions text-center">
                                <a href="{{ route('produccion.reproceso.detalle', $lote->id) }}"
                                   class="btn btn-primary m-0" title="Abrir lote">
                                    <i class="fa-solid fa-recycle fs-8"></i>
                                </a>
                            </td>
                            <td class="text-nowrap">
                                <strong>{{ $lote->folio }}</strong>
                                @if ($lote->etiqueta_resina)
                                    <div class="text-muted fs-9">Resina {{ $lote->etiqueta_resina }}</div>
                                @elseif ($lote->etiqueta_triturado)
                                    <div class="text-muted fs-9">Trit. {{ $lote->etiqueta_triturado }}</div>
                                @endif
                            </td>
                            <td>{{ $lote->origen_texto }}</td>
                            <td>
                                @if ($lote->orden)
                                    <a href="{{ route('produccion.ordenes.detalle', $lote->orden_id) }}" class="text-decoration-none">
                                        {{ $lote->orden->folio }}
                                    </a>
                                    <div class="text-muted fs-9 text-truncate" style="max-width: 180px;"
                                         title="{{ $lote->orden->pedido->cliente->nombre ?? '' }}">
                                        {{ \Illuminate\Support\Str::limit($lote->orden->pedido->cliente->nombre ?? '—', 35) }}
                                    </div>
                                @else
                                    <span class="text-muted">Sin OP</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($lote->maquina)
                                    {{ $lote->maquina->codigo ? $lote->maquina->codigo . ' · ' : '' }}{{ $lote->maquina->nombre }}
                                @else
                                    <span class="badge badge-danger-dark fs-9">Sin máquina</span>
                                @endif
                            </td>
                            <td class="text-nowrap">{{ $lote->ubicacion->folio_interno ?? '—' }}</td>
                            <td class="text-nowrap">
                                @if ($lote->kg_produccion_reportado)
                                    <strong>{{ number_format((float) $lote->kg_produccion_reportado, 2) }}</strong> kg prod.
                                @elseif ($lote->kg_saca_resina)
                                    {{ number_format((float) $lote->kg_saca_resina, 2) }} kg resina
                                @elseif ($lote->kg_saca_triturada)
                                    {{ number_format((float) $lote->kg_saca_triturada, 2) }} kg saca
                                @elseif ($lote->kg_reportado)
                                    {{ number_format((float) $lote->kg_reportado, 2) }} kg
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $estatusBadge[$lote->estatus] ?? 'bg-secondary' }} fs-9">
                                    {{ $lote->estatus_texto }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
@endsection
