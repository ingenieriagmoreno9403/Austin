@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="cc-page" id="cc-app">
    <div class="cc-header">
        <div>
            <div class="cc-kicker">Contabilidad · ciclos de presupuesto</div>
            <h1 class="cc-title">Budgets y Asignaciones</h1>
            <p class="cc-sub">Elige el ciclo que vas a presupuestar. Al abrirlo entras a centros, cuentas, usuarios y permisos de ese periodo.</p>
        </div>
        <div class="cc-header-actions">
            <button type="button" class="cc-btn cc-btn-ink" onclick="CC.showModal('modalPeriodo')">
                <i class="fa-solid fa-unlock"></i> Abrir nuevo ciclo
            </button>
        </div>
    </div>

    @include('CentrosCostos.partials.nav')

    <div class="cc-kpis">
        <div class="cc-kpi"><div class="label">Ciclos</div><div class="value" id="kpi-ciclos">—</div><div class="hint">Periodos de presupuesto</div></div>
        <div class="cc-kpi"><div class="label">Abiertos / en proceso</div><div class="value" id="kpi-ciclos-abiertos">—</div><div class="hint">Listos para captura</div></div>
        <div class="cc-kpi"><div class="label">En revisión</div><div class="value" id="kpi-ciclos-revision">—</div><div class="hint">Pendientes de contabilidad</div></div>
        <div class="cc-kpi"><div class="label">Cerrados</div><div class="value" id="kpi-ciclos-cerrados">—</div><div class="hint">Terminados o aceptados</div></div>
    </div>

    <div class="cc-panel">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-layer-group"></i> Ciclos a presupuestar</h3>
            <div class="cc-chips" id="ciclo-estado-chips" data-value=""></div>
        </div>
        <div class="cc-filters" style="grid-template-columns: 1fr;">
            <input id="ciclo-q" class="cc-input" type="search" placeholder="Buscar por código, nombre u observaciones…">
        </div>
        <div class="cc-ciclo-grid" id="ciclo-grid"></div>
    </div>
</div>

@include('CentrosCostos.partials.modal-ciclo')
@endsection

@section('js')
<script src="{{ asset('js/centros-costos.js') }}"></script>
<script>
    CC.boot(Object.assign(@json($bootstrap), { page: 'admin' }));
</script>
@endsection
