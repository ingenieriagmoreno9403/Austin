@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="cc-page">
    <div class="cc-header">
        <div>
            <div class="cc-kicker">Supervisión · presupuesto {{ $bootstrap['anioPresupuesto'] }}</div>
            <h1 class="cc-title">Análisis de progreso</h1>
            <p class="cc-sub">Cuánto se lleva capturado por empresa, centro, cuenta o usuario, y dónde se pasó el límite vs 2026.</p>
        </div>
        <div class="cc-header-actions">
            <span id="sap-flag" class="cc-sap-flag off">Catálogo</span>
            <a class="cc-btn" href="{{ route('centros.control') }}"><i class="fa-solid fa-pen-to-square"></i> Ir a captura</a>
        </div>
    </div>

    @include('CentrosCostos.partials.nav')

    <div class="cc-panel">
        <div class="cc-filters">
            <input id="an-q" class="cc-input" type="search" placeholder="Buscar empresa, centro, cuenta o usuario…">
            <select id="an-empresa" class="cc-select"></select>
            <select id="an-depto" class="cc-select"></select>
            <select id="an-user" class="cc-select"></select>
        </div>
    </div>

    <div class="cc-kpis">
        <div class="cc-kpi"><div class="label">Avance promedio</div><div class="value" id="an-kpi-avance">—</div><div class="hint">Cuentas ya capturadas</div></div>
        <div class="cc-kpi alert"><div class="label">Poca captura</div><div class="value" id="an-kpi-poco">—</div><div class="hint">Centros debajo del 40%</div></div>
        <div class="cc-kpi"><div class="label">Sobre el límite</div><div class="value" id="an-kpi-over">—</div><div class="hint">Ppto 2027 &gt; 110% del gasto 2026</div></div>
        <div class="cc-kpi"><div class="label">Variación vs 2026</div><div class="value" id="an-kpi-yoy">—</div><div class="hint">Promedio de aumento</div></div>
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
                <h3><i class="fa-solid fa-chart-column"></i> Gasto 2026 vs presupuesto 2027</h3>
            </div>
            <div class="cc-chart"><canvas id="chart-empresas"></canvas></div>
        </div>
        <div class="cc-panel">
            <div class="cc-panel-head">
                <h3><i class="fa-solid fa-grip"></i> Estacionalidad</h3>
            </div>
            <p class="text-muted mb-2" style="font-size:.82rem" id="an-heat-label">Gasto 2026</p>
            <div class="cc-heat mb-2" id="an-heat"></div>
            <div class="cc-legend">
                <span>Ene → Dic · intensidad = gasto real del año pasado</span>
            </div>
        </div>
    </div>

    <div class="cc-panel">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-list-check"></i> Detalle de captura y aplicación</h3>
        </div>
        <div class="cc-table-wrap">
            <table class="cc-table">
                <thead>
                    <tr>
                        <th>Centro</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Capturado</th>
                        <th class="num">Gasto 2026</th>
                        <th class="num">Ppto 2027</th>
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
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/centros-costos.js') }}"></script>
<script>
    CC.boot(Object.assign(@json($bootstrap), { page: 'analisis' }));
</script>
@endsection
