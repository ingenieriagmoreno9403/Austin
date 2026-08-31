@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .costo-pead-form {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.75rem 0.85rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .costo-pead-form .form-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0.25rem;
    }
    .costo-pead-form .form-control,
    .costo-pead-form .form-select {
        min-height: 32px;
        font-size: 0.8rem;
        padding: 0.2rem 0.5rem;
        background: #ffffff !important;
        border: 1px solid var(--table-border, #e5e7eb) !important;
        border-radius: 10px !important;
        color: var(--table-text, #111827) !important;
    }
    .costo-pead-table-card .td-actions {
        white-space: nowrap !important;
    }
    .costo-pead-table-card .inline-edit {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
        justify-content: flex-end;
    }
    .costo-pead-table-card .inline-edit .form-control-sm {
        min-height: 28px;
        height: 28px;
        font-size: 0.78rem;
        padding: 0.15rem 0.4rem;
        background: #ffffff !important;
        border: 1px solid var(--table-border, #e5e7eb) !important;
        border-radius: 10px !important;
    }
    .costo-pead-table-card .inline-edit .inp-costo { width: 110px; }
    .costo-pead-table-card .inline-edit .inp-prov { width: 140px; }
    .costo-pead-table-card .inline-edit .inp-obs { width: 160px; }
    .costo-pead-kpi {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.85rem;
    }
    .costo-pead-kpi .kpi-chip {
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
    .costo-pead-kpi .kpi-chip strong {
        color: #1e3a5f;
        font-size: 0.9rem;
    }
</style>

@php
    $totalCostos = $costos->count();
    $ultimo = $costos->first();
@endphp

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-dollar-sign"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Costo PEAD mensual</h2>
                        <p class="text-muted mb-0">
                            $/kg por mes para costear el registro de producción OEE
                            — {{ number_format($totalCostos) }} meses capturados
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <a class="btn btn-baseColor-light" href="{{ route('produccion.reportes.oee') }}">
                        <i class="fa-solid fa-gauge-high"></i> Reporte OEE
                    </a>
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
            <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($ultimo)
        <div class="costo-pead-kpi">
            <span class="kpi-chip">
                <i class="fa-solid fa-calendar"></i>
                Último mes <strong>{{ $ultimo->mes }}</strong>
            </span>
            <span class="kpi-chip">
                <i class="fa-solid fa-tag"></i>
                Costo <strong>${{ number_format((float) $ultimo->costo_kg, 4) }}/kg</strong>
            </span>
            @if ($ultimo->proveedor)
                <span class="kpi-chip">
                    <i class="fa-solid fa-truck"></i>
                    Proveedor <strong>{{ $ultimo->proveedor }}</strong>
                </span>
            @endif
        </div>
    @endif

    <div class="filters costo-pead-form">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa-solid fa-plus text-muted"></i>
            <span class="fw-semibold text-secondary fs-8">Capturar / actualizar mes</span>
        </div>
        <form method="POST" action="{{ route('produccion.costo_pead.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-2">
                <label class="form-label" for="mes">Mes</label>
                <input type="month" id="mes" name="mes" class="form-control" value="{{ old('mes', date('Y-m')) }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="costo_kg">Costo PEAD ($/kg)</label>
                <input type="number" step="0.0001" min="0" id="costo_kg" name="costo_kg" class="form-control" value="{{ old('costo_kg') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="proveedor">Proveedor</label>
                <input type="text" id="proveedor" name="proveedor" class="form-control" maxlength="150" value="{{ old('proveedor') }}" placeholder="Opcional">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="observaciones">Observaciones</label>
                <input type="text" id="observaciones" name="observaciones" class="form-control" maxlength="500" value="{{ old('observaciones') }}" placeholder="Opcional">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-baseColor w-100">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>
            </div>
        </form>
    </div>

    <div class="row mt-1">
        <div class="table-responsive costo-pead-table-card">
            <table class="table table-striped table-hover display modern-table w-100" id="table">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="text-end">Costo ($/kg)</th>
                        <th>Proveedor</th>
                        <th>Observaciones</th>
                        <th class="text-center">Actualizar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($costos as $c)
                        <tr>
                            <td class="fw-semibold text-nowrap">{{ $c->mes }}</td>
                            <td class="text-end text-nowrap">${{ number_format((float) $c->costo_kg, 4) }}</td>
                            <td>{{ $c->proveedor ?: '—' }}</td>
                            <td class="text-truncate" style="max-width: 240px;" title="{{ $c->observaciones }}">
                                {{ $c->observaciones ?: '—' }}
                            </td>
                            <td class="td-actions">
                                <form method="POST" action="{{ route('produccion.costo_pead.update', $c->id) }}" class="inline-edit">
                                    @csrf
                                    <input type="number" step="0.0001" min="0" name="costo_kg" class="form-control form-control-sm inp-costo"
                                        value="{{ $c->costo_kg }}" required aria-label="Costo $/kg">
                                    <input type="text" name="proveedor" class="form-control form-control-sm inp-prov"
                                        value="{{ $c->proveedor }}" placeholder="Proveedor" maxlength="150" aria-label="Proveedor">
                                    <input type="text" name="observaciones" class="form-control form-control-sm inp-obs"
                                        value="{{ $c->observaciones }}" placeholder="Obs." maxlength="500" aria-label="Observaciones">
                                    <button class="btn btn-baseColor-light btn-sm" type="submit" title="Actualizar">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                    </button>
                                </form>
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
