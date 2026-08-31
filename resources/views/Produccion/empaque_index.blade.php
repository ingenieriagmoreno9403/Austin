@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $estatusBadge = [
        'REVISION_CALIDAD' => 'bg-info text-dark',
        'PREPARAR_EMPAQUE' => 'bg-primary',
        'ENROLLANDO' => 'bg-primary',
        'A_LONGITUD' => 'bg-primary',
        'CORTAR_FLEJAR' => 'bg-primary',
        'EMPACANDO' => 'bg-primary',
        'TERMINADA' => 'badge-success-dark',
    ];
    $tieneFiltros = filled(request('q')) || ($filtro !== 'pendientes');
    $totalOrdenes = $ordenes->count();
@endphp

<style>
    .empaque-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .empaque-filters form {
        gap: 0.35rem 0.65rem;
    }
    .empaque-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
        max-width: none;
    }
    .empaque-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .empaque-filters .form-select-sm,
    .empaque-filters .form-control-sm {
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
    .empaque-filters .form-control-sm.filter-search {
        min-width: 200px;
        max-width: 280px;
    }
    .empaque-filters .form-select-sm.filter-cola {
        min-width: 180px;
        max-width: 240px;
    }
    .empaque-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    .empaque-table-card .td-actions {
        white-space: nowrap !important;
    }
    .empaque-table-card .td-actions .btn {
        display: inline-flex !important;
        margin: 0 2px !important;
    }
    .empaque-kpi {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.85rem;
    }
    .empaque-kpi .kpi-chip {
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
    .empaque-kpi .kpi-chip strong {
        color: #1e3a5f;
        font-size: 0.9rem;
    }
    @media (max-width: 768px) {
        .empaque-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .empaque-filters .form-select-sm,
        .empaque-filters .form-control-sm {
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
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Empaque y almacenamiento</h2>
                        <p class="text-muted mb-0">
                            Rollo/tramos → enrollar → longitud → cortar/flejar → almacén
                            — {{ number_format($totalOrdenes) }} órdenes en vista
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <a class="btn btn-baseColor-light" href="{{ route('produccion.tipos_empaque') }}">
                        <i class="fa-solid fa-boxes-packing"></i> Tipos de empaque
                    </a>
                    <a class="btn btn-baseColor-light" href="{{ route('produccion.ordenes') }}">
                        <i class="fa-solid fa-industry"></i> Producción
                    </a>
                    <a class="btn btn-baseColor-light" href="{{ route('almacen.cargas') }}">
                        <i class="fa-solid fa-truck"></i> Cargas
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

    <div class="empaque-kpi">
        <span class="kpi-chip">
            <i class="fa-solid fa-clock"></i>
            Pendientes <strong>{{ number_format($conteos['pendientes'] ?? 0) }}</strong>
        </span>
        <span class="kpi-chip">
            <i class="fa-solid fa-circle-check"></i>
            Terminadas (14 d) <strong>{{ number_format($conteos['terminadas'] ?? 0) }}</strong>
        </span>
    </div>

    <div class="row mt-1">
        <div class="table-responsive empaque-table-card">
            <div class="filters empaque-filters">
                <form method="GET" action="{{ route('produccion.empaque') }}" class="d-flex flex-wrap align-items-center" id="filtroEmpaque">
                    <div class="filter-field">
                        <label class="form-label" for="filtro_q">Buscar</label>
                        <input type="text" class="form-control form-control-sm filter-search" id="filtro_q" name="q"
                            value="{{ request('q') }}" placeholder="OP, pedido o cliente" aria-label="Buscar">
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_estatus">Cola</label>
                        <select class="form-select form-select-sm filter-cola" id="filtro_estatus" name="estatus" aria-label="Filtrar por cola">
                            <option value="pendientes" {{ $filtro === 'pendientes' ? 'selected' : '' }}>
                                Pendientes ({{ $conteos['pendientes'] ?? 0 }})
                            </option>
                            <option value="terminadas" {{ $filtro === 'terminadas' ? 'selected' : '' }}>
                                Terminadas 14 d ({{ $conteos['terminadas'] ?? 0 }})
                            </option>
                            <option value="todas" {{ $filtro === 'todas' ? 'selected' : '' }}>Todas</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    @if ($tieneFiltros)
                        <a href="{{ route('produccion.empaque') }}" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                            <i class="fa-solid fa-xmark"></i> Limpiar
                        </a>
                    @endif
                </form>
            </div>

            <table class="table table-striped table-hover display modern-table w-100" id="table">
                <thead>
                    <tr>
                        <th class="text-center">Acciones</th>
                        <th>Orden</th>
                        <th>Máquina</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Tipo / longitud</th>
                        <th>Ubicación</th>
                        <th>Estatus</th>
                        <th>Actualizado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ordenes as $orden)
                        <tr>
                            <td class="td-actions text-center">
                                <a href="{{ route('produccion.empaque.detalle', $orden->id) }}"
                                   class="btn btn-primary m-0" title="Operar empaque">
                                    <i class="fa-solid fa-box-open fs-8"></i>
                                </a>
                            </td>
                            <td class="text-nowrap">
                                <strong>{{ $orden->folio }}</strong>
                            </td>
                            <td class="text-nowrap">{{ $orden->maquina?->codigo ?: '—' }}</td>
                            <td class="text-nowrap">{{ $orden->pedido?->folio ?? '—' }}</td>
                            <td class="text-truncate" style="max-width: 220px;" title="{{ $orden->pedido?->cliente?->nombre }}">
                                {{ \Illuminate\Support\Str::limit($orden->pedido?->cliente?->nombre ?? '—', 40) }}
                            </td>
                            <td>
                                {{ \App\Models\TipoEmpaque::etiqueta($orden->tipo_empaque) }}
                                <div class="text-muted fs-9">
                                    {{ $orden->longitud_objetivo_m !== null ? number_format((float) $orden->longitud_objetivo_m, 2) . ' m' : '—' }}
                                </div>
                            </td>
                            <td class="text-nowrap">{{ $orden->etiquetaNaveDestino() }}</td>
                            <td>
                                <span class="badge {{ $estatusBadge[$orden->estatus] ?? 'bg-secondary' }} fs-9">
                                    {{ $orden->estatus_texto }}
                                </span>
                            </td>
                            <td data-order="{{ optional($orden->updated_at)->format('Y-m-d H:i:s') }}">
                                {{ optional($orden->updated_at)->format('d/m/Y H:i') ?: '—' }}
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
