@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="cc-page">
    <div class="cc-header">
        <div>
            <div class="cc-kicker" id="cc-page-kicker">Detalle del centro</div>
            <h1 class="cc-title" id="cc-page-title">Centro de costos</h1>
        </div>
        <div class="cc-header-actions">
            <a class="cc-btn" href="{{ route('centros.control', ['vista' => 'visor']) }}">
                <i class="fa-solid fa-arrow-left"></i> Volver al visor
            </a>
        </div>
    </div>
    <div class="cc-detalle-facts" id="cc-detalle-facts"></div>

    <select id="ctl-ciclo" hidden></select>
    <input type="hidden" id="ctl-empresa" value="{{ $bootstrap['empresaInicial'] ?? '' }}">
    <input type="hidden" id="ctl-centro" value="{{ $bootstrap['centroInicial'] ?? '' }}">

    <div class="cc-panel cc-captura-results">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-table"></i> Detalle por cuenta y mes</h3>
            <div class="cc-legend">
                <span><i style="background:#fffbeb"></i> Sin capturar</span>
                <span><i style="background:#ecfdf5"></i> Capturado</span>
                <span><i style="background:#fef2f2"></i> +20% vs {{ $bootstrap['anioGasto'] }}</span>
            </div>
        </div>
        <p class="text-muted" style="font-size:.8rem;margin:-.35rem 0 .7rem">Consulta el presupuesto capturado de este centro, mes a mes.</p>
        <div class="cc-table-wrap">
            <table class="cc-table">
                <thead id="det-thead"></thead>
                <tbody id="det-tbody" class="is-readonly"></tbody>
            </table>
        </div>
        <div class="cc-sticky-totales">
            <div>Total gasto {{ $bootstrap['anioGasto'] }} <strong id="det-tot-gasto">—</strong></div>
            <div>Total ppto {{ $bootstrap['anioPresupuesto'] }} <strong id="det-tot-ppto">—</strong></div>
            <div>Cuentas pendientes <strong id="det-pend">—</strong></div>
        </div>
    </div>

    <div class="cc-panel cc-captura-chart">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-chart-column"></i> Contraste mes a mes</h3>
            <span class="text-muted" style="font-size:.78rem" id="det-chart-hint">Gasto {{ $bootstrap['anioGasto'] }} vs presupuesto {{ $bootstrap['anioPresupuesto'] }}</span>
        </div>
        <div class="cc-chart"><canvas id="chart-detalle"></canvas></div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/centros-costos.js') }}"></script>
<script>
    CC.boot(Object.assign(@json($bootstrap), { page: 'detalle' }));
</script>
@endsection
