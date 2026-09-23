@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="cc-page">
    <div class="cc-header">
        <div>
            <div class="cc-kicker" id="an-kicker">Supervisión · presupuesto {{ $bootstrap['anioPresupuesto'] }}</div>
            <h1 class="cc-title">Análisis de progreso</h1>
            <p class="cc-sub" id="an-sub">Cuánto se lleva capturado por empresa, centro, cuenta o usuario, y dónde se pasó el límite vs el gasto real.</p>
        </div>
        <div class="cc-header-actions">
            <span id="sap-flag" class="cc-sap-flag off">Catálogo</span>
            <select id="an-ciclo" class="cc-select cc-header-ciclo" aria-label="Presupuesto"></select>
            <a class="cc-btn" href="{{ route('centros.control', ['vista' => 'visor']) }}"><i class="fa-solid fa-pen-to-square"></i> Ir a captura</a>
        </div>
    </div>

    @include('CentrosCostos.partials.nav')

    <div class="cc-panel">
        <div class="cc-filters cc-filters-asig">
            <label class="cc-filter-field" for="an-empresa">Empresa
                <select id="an-empresa" class="cc-select"></select>
            </label>
            <label class="cc-filter-field" for="an-centro">Centro de costos
                <select id="an-centro" class="cc-select"></select>
            </label>
            <label class="cc-filter-field" for="an-user">Usuario
                <select id="an-user" class="cc-select"></select>
            </label>
        </div>
    </div>

    <div id="an-empty" class="cc-panel" hidden>
        <div class="cc-empty" style="padding:2.2rem 1rem">
            <i class="fa-solid fa-chart-line"></i>
            No hay centros asignados en este ciclo. Asigna usuarios y cuentas en Budgets y Asignaciones.
        </div>
    </div>

    <div id="an-work">
    <div class="cc-kpis cc-kpis-equal">
        <div class="cc-kpi"><div class="label">Avance promedio</div><div class="value" id="an-kpi-avance">—</div><div class="hint">Cuentas ya capturadas</div></div>
        <div class="cc-kpi alert"><div class="label">Poca captura</div><div class="value" id="an-kpi-poco">—</div><div class="hint">Centros debajo del 40%</div></div>
        <div class="cc-kpi"><div class="label">Sobre el límite</div><div class="value" id="an-kpi-over">—</div><div class="hint" id="an-kpi-over-hint">Ppto &gt; 110% del gasto real</div></div>
        <div class="cc-kpi"><div class="label">Variación vs gasto</div><div class="value" id="an-kpi-yoy">—</div><div class="hint" id="an-kpi-yoy-hint">Promedio de aumento</div></div>
    </div>

    <div class="cc-split">
        <div class="cc-panel">
            <div class="cc-panel-head">
                <h3><i class="fa-solid fa-building"></i> Panel del superior · por empresa</h3>
            </div>
            <div id="an-empresas"></div>
        </div>
        <div class="cc-panel">
            <div class="cc-panel-head">
                <h3><i class="fa-solid fa-chart-pie"></i> Estados de centros</h3>
            </div>
            <div class="cc-chart cc-chart-sm"><canvas id="chart-estados"></canvas></div>
        </div>
    </div>

    <div class="cc-split">
        <div class="cc-panel">
            <div class="cc-panel-head">
                <h3 id="an-chart-emp-title"><i class="fa-solid fa-chart-column"></i> Gasto {{ $bootstrap['anioGasto'] }} vs presupuesto {{ $bootstrap['anioPresupuesto'] }}</h3>
            </div>
            <div class="cc-chart"><canvas id="chart-empresas"></canvas></div>
        </div>
        <div class="cc-panel">
            <div class="cc-panel-head">
                <h3><i class="fa-solid fa-grip"></i> Estacionalidad</h3>
                <div class="cc-panel-head-tools">
                    <input id="an-heat-q" class="cc-input cc-heat-search" type="search" placeholder="Buscar empresa o centro…" aria-label="Buscar en estacionalidad">
                </div>
            </div>
            <p class="text-muted mb-2" style="font-size:.82rem" id="an-heat-label">Gasto {{ $bootstrap['anioGasto'] }} · por empresa</p>
            <div class="cc-heat-scroll">
                <div class="cc-heat-stack" id="an-heat"></div>
                <div class="cc-heat-empty" id="an-heat-empty" hidden>Sin coincidencias</div>
            </div>
            <div class="cc-heat-months is-labeled" id="an-heat-months"></div>
            <div class="cc-legend">
                <span>Ene → Dic · intensidad = gasto real · elige una empresa para ver centros</span>
            </div>
        </div>
    </div>

    <div class="cc-panel">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-list-check"></i> Detalle de captura y aplicación</h3>
            <div class="cc-panel-head-tools">
                <input id="an-q" class="cc-input cc-table-search" type="search" placeholder="Buscar en la tabla…">
            </div>
        </div>
        <div class="cc-table-wrap">
            <table class="cc-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Centro</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Capturado</th>
                        <th class="num" id="an-th-gasto">Gasto {{ $bootstrap['anioGasto'] }}</th>
                        <th class="num" id="an-th-ppto">Ppto {{ $bootstrap['anioPresupuesto'] }}</th>
                        <th class="num">Δ año</th>
                        <th>Límite</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="an-tbody"></tbody>
            </table>
        </div>
    </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/centros-costos.js') }}"></script>
<script>
    CC.boot(Object.assign(@json($bootstrap), { page: 'analisis' }));
</script>
@endsection
