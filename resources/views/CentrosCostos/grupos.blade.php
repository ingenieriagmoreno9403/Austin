@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}?v={{ (int) @filemtime(public_path('css/centros-costos.css')) }}" rel="stylesheet">
@endsection

@section('content')
<div class="cc-page" id="cc-grupos-app">
    <div class="cc-header">
        <div>
            <div class="cc-kicker">Contabilidad · catálogo</div>
            <h1 class="cc-title">Grupos de cuentas</h1>
            <p class="cc-sub">Elige la empresa, crea o selecciona una agrupación a la izquierda, y en el panel grande marca las cuentas.</p>
        </div>
        <div class="cc-header-actions">
            <a class="cc-btn" href="{{ route('centros.admin') }}">
                <i class="fa-solid fa-arrow-left"></i> Budgets
            </a>
        </div>
    </div>

    <div class="cc-panel">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-building"></i> Empresa</h3>
            <span id="grp-sap-flag" class="cc-sap-flag off">Catálogo SAP</span>
        </div>
        <p class="text-muted mb-3" style="font-size:.85rem">Primero la empresa. Después aparecen sus cuentas y puedes crear o editar grupos.</p>
        <div class="cc-ciclo-grid" id="grp-empresa-grid">
            <div class="cc-empty" style="grid-column:1/-1">Cargando empresas…</div>
        </div>
    </div>

    <div id="grp-workspace" hidden>
        <div class="cc-asig-pair cc-grupos-pair">
            <div class="cc-panel cc-asig-col" id="grp-opciones">
                <div class="cc-panel-head">
                    <h3><i class="fa-solid fa-layer-group"></i> Agrupaciones</h3>
                    <span class="cc-badge cc-badge-solo_revision" id="grp-list-meta">—</span>
                </div>
                <div id="grp-list" class="cc-pick-list cc-grupos-list" role="listbox" aria-label="Grupos de cuentas">
                    <div class="cc-empty">Elige una empresa. Crea una nueva o pulsa una existente.</div>
                </div>
            </div>

            <div class="cc-panel cc-asig-col" id="grp-editor">
                <div class="cc-panel-head">
                    <h3 id="grp-editor-title"><i class="fa-solid fa-list"></i> Seleccionar cuentas</h3>
                    <button type="button" class="cc-btn cc-btn-danger" id="grp-borrar" hidden>
                        <i class="fa-solid fa-trash"></i> Eliminar
                    </button>
                </div>
                <div class="cc-grupos-cta-tools">
                    <select id="grp-mask" class="cc-select">
                        <option value="">Todos los GroupMask</option>
                    </select>
                    <div class="cc-pick-search">
                        <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input id="grp-cta-q" class="cc-input" type="search" placeholder="Buscar cuenta por código, nombre o agrupación SAP…">
                    </div>
                </div>
                <label class="cc-grupos-todas">
                    <input type="checkbox" id="grp-cta-todas">
                    <span>Seleccionar las cuentas visibles</span>
                    <small id="grp-cta-meta">—</small>
                </label>
                <div id="grp-cta-list" class="cc-pick-list cc-grupos-ctas">
                    <div class="cc-empty">Pon un nombre a la izquierda y selecciona las cuentas aquí</div>
                </div>
            </div>
        </div>
        <div class="cc-grupos-actions">
            <button type="button" class="cc-btn" id="grp-cancelar">Cancelar</button>
            <div class="cc-grupos-actions-save">
                <button type="button" class="cc-btn" id="grp-guardar-salir">
                    <i class="fa-solid fa-arrow-left"></i> Guardar y salir
                </button>
                <button type="button" class="cc-btn cc-btn-ink" id="grp-guardar">
                    <i class="fa-solid fa-check"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('js/centros-grupos.js') }}?v={{ (int) @filemtime(public_path('js/centros-grupos.js')) }}"></script>
<script>
    CCGrupos.boot(@json($bootstrap));
</script>
@endsection
