@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="cc-page">
    <div class="cc-header">
        <div>
            <div class="cc-kicker" id="an-kicker">Supervisión · proyección {{ $bootstrap['anioPresupuesto'] }}</div>
            <h1 class="cc-title">Análisis de Proyecciones</h1>
            <p class="cc-sub" id="an-sub">Cuánto se lleva capturado por empresa, cliente, producto o usuario, y la diferencia vs la venta real (cantidad × costo).</p>
        </div>
        <div class="cc-header-actions">
            <span id="sap-flag" class="cc-sap-flag off">Catálogo</span>
            <select id="an-ciclo" class="cc-select cc-header-ciclo" aria-label="Ciclo"></select>
            <a class="cc-btn" href="{{ route('pv.control', ['vista' => 'visor']) }}"><i class="fa-solid fa-pen-to-square"></i> Ir a captura</a>
        </div>
    </div>

    @include('ProyeccionesVentas.partials.nav')

    <div class="cc-panel">
        <div class="cc-filters cc-filters-asig">
            <label class="cc-filter-field" for="an-empresa">Empresa
                <select id="an-empresa" class="cc-select"></select>
            </label>
            <label class="cc-filter-field" for="an-cliente">Cliente
                <select id="an-cliente" class="cc-select"></select>
            </label>
            <label class="cc-filter-field" for="an-user">Usuario
                <select id="an-user" class="cc-select"></select>
            </label>
        </div>
    </div>

    <div id="an-empty" class="cc-panel" hidden>
        <div class="cc-empty" style="padding:2.2rem 1rem">
            <i class="fa-solid fa-chart-line"></i>
            No hay clientes asignados en este ciclo. Asigna usuarios y productos en Proyecciones de ventas.
        </div>
    </div>

    <div id="an-work">
    <div class="cc-kpis cc-kpis-equal">
        <div class="cc-kpi"><div class="label">Avance promedio</div><div class="value" id="an-kpi-avance">—</div><div class="hint">Productos ya capturados</div></div>
        <div class="cc-kpi alert"><div class="label">Poca captura</div><div class="value" id="an-kpi-poco">—</div><div class="hint">Clientes debajo del 40%</div></div>
        <div class="cc-kpi"><div class="label">Sobre el límite</div><div class="value" id="an-kpi-over">—</div><div class="hint" id="an-kpi-over-hint">Proy. &gt; 110% de la venta real</div></div>
        <div class="cc-kpi"><div class="label">Variación vs venta</div><div class="value" id="an-kpi-yoy">—</div><div class="hint" id="an-kpi-yoy-hint">Promedio de aumento</div></div>
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
                <h3><i class="fa-solid fa-chart-pie"></i> Estados de clientes</h3>
            </div>
            <div class="cc-chart cc-chart-sm"><canvas id="chart-estados"></canvas></div>
        </div>
    </div>

    <div class="cc-split">
        <div class="cc-panel">
            <div class="cc-panel-head">
                <h3 id="an-chart-emp-title"><i class="fa-solid fa-chart-column"></i> Venta {{ $bootstrap['anioGasto'] }} vs proyección {{ $bootstrap['anioPresupuesto'] }}</h3>
                <label class="cc-chart-prod" for="an-chart-producto">Producto
                    <select id="an-chart-producto" class="cc-select"></select>
                </label>
            </div>
            <p class="text-muted mb-2" style="font-size:.82rem" id="an-chart-hint">Venta y proyección por empresa</p>
            <div class="cc-chart"><canvas id="chart-empresas"></canvas></div>
        </div>
        <div class="cc-panel">
            <div class="cc-panel-head">
                <h3><i class="fa-solid fa-grip"></i> Estacionalidad</h3>
                <div class="cc-panel-head-tools">
                    <input id="an-heat-q" class="cc-input cc-heat-search" type="search" placeholder="Buscar empresa o producto…" aria-label="Buscar en estacionalidad">
                </div>
            </div>
            <p class="text-muted mb-2" style="font-size:.82rem" id="an-heat-label">Venta {{ $bootstrap['anioGasto'] }} · por empresa</p>
            <div class="cc-heat-scroll">
                <div class="cc-heat-stack" id="an-heat"></div>
                <div class="cc-heat-empty" id="an-heat-empty" hidden>Sin coincidencias</div>
            </div>
            <div class="cc-heat-months is-labeled" id="an-heat-months"></div>
            <div class="cc-legend">
                <span>Ene → Dic · intensidad = venta real · elige una empresa para ver productos</span>
            </div>
        </div>
    </div>

    <div class="cc-panel">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-list-check"></i> Detalle por producto</h3>
            <div class="cc-panel-head-tools">
                <input id="an-q" class="cc-input cc-table-search" type="search" placeholder="Buscar producto, cliente o usuario…">
            </div>
        </div>
        <div class="cc-table-wrap">
            <table class="cc-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cliente</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th>Capturado</th>
                        <th class="num" id="an-th-gasto">Venta {{ $bootstrap['anioGasto'] }}</th>
                        <th class="num" id="an-th-ppto">Proy. {{ $bootstrap['anioPresupuesto'] }}</th>
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
<script src="{{ asset('js/proyecciones-ventas.js') }}"></script>
<script>
    CC.boot(Object.assign(@json($bootstrap), { page: 'analisis' }));
</script>
@endsection
