@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}" rel="stylesheet">
@endsection

@section('content')
@php
    $ciclo = $bootstrap['cicloCodigo'] ?? '';
@endphp
<div class="cc-page" id="cc-app" data-ciclo="{{ $ciclo }}">
    <div class="cc-header">
        <div>
            <div class="cc-kicker" id="asig-kicker">{{ $ciclo }}</div>
            <h1 class="cc-title">Asignar clientes y productos</h1>
            <p class="cc-sub" id="asig-sub">Clientes (CardName) y productos (ItemName) salen de Ventas y notas de crédito (OINV + ORIN) de esa empresa. Primero el usuario, luego empresa, cliente y productos.</p>
        </div>
        <div class="cc-header-actions">
            <a class="cc-btn" href="{{ route('pv.ciclo', $ciclo) }}">
                <i class="fa-solid fa-arrow-left"></i> Ciclo
            </a>
        </div>
    </div>

    <div class="cc-wizard">
        <div class="cc-step is-on" data-step="1"><span>1</span> Usuario</div>
        <div class="cc-step" data-step="2"><span>2</span> Empresa</div>
        <div class="cc-step" data-step="3"><span>3</span> Cliente</div>
        <div class="cc-step" data-step="4"><span>4</span> Productos y permisos</div>
    </div>

    <div class="cc-panel" id="asig-user-panel">
        <div class="cc-panel-head"><h3><i class="fa-solid fa-user"></i> 1. Usuario del sistema  · <b class="fw-normal">Quién va a proyectar</b></h3></div>
        <div id="asig-user-pick">
            <div class="cc-pick-search">
                <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input id="asig-user-q" class="cc-input" type="search" placeholder="Buscar usuario por nombre o correo…">
            </div>
            <select id="asig-user" class="cc-sr-only" aria-hidden="true" tabindex="-1">
                <option value="">Selecciona un usuario…</option>
                @foreach($bootstrap['usuarios'] ?? [] as $u)
                    <option value="{{ $u['id'] }}" data-nombre="{{ $u['nombre'] }}" data-email="{{ $u['email'] ?? '' }}">{{ $u['nombre'] }}{{ !empty($u['email']) ? ' · '.$u['email'] : '' }}</option>
                @endforeach
            </select>
            <div id="asig-user-list" class="cc-pick-list cc-user-list" role="listbox" aria-label="Usuarios">
                <div class="cc-empty">Cargando usuarios…</div>
            </div>
        </div>
        <div id="asig-user-chosen" class="cc-user-chosen" hidden>
            <div class="cc-user-chosen-info">
                <span class="cc-avatar" id="asig-user-chosen-av">—</span>
                <div>
                    <strong id="asig-user-chosen-name"></strong>
                    <small id="asig-user-chosen-email"></small>
                </div>
            </div>
            <button type="button" class="cc-icon-btn" id="asig-user-clear" title="Cambiar usuario">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <div id="asig-empresas" class="cc-panel is-locked">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-building"></i> 2. Empresas (AutinApi / SAP)</h3>
            <span id="sap-flag" class="cc-sap-flag off">Catálogo SAP</span>
        </div>
        <p class="text-muted mb-3" style="font-size:.85rem" id="asig-emp-hint">Elige un usuario para habilitar las empresas.</p>
        <div class="cc-ciclo-grid" id="empresa-grid">
            <div class="cc-empty" style="grid-column:1/-1">Cargando empresas…</div>
        </div>
    </div>

    <div id="asig-detalle" hidden>
        <div class="cc-asig-pair">
            <div class="cc-panel cc-asig-col">
                <div class="cc-panel-head"><h3 id="asig-cc-title"><i class="fa-solid fa-sitemap"></i> 3. Cliente</h3></div>
                <div class="cc-pick-search">
                    <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input id="asig-cc-q" class="cc-input" type="search" placeholder="Buscar cliente por código o nombre…">
                </div>
                <select id="asig-centro" class="cc-sr-only" aria-hidden="true" tabindex="-1">
                    <option value="">Elige un cliente…</option>
                </select>
                <div id="asig-cc-list" class="cc-pick-list" role="listbox" aria-label="Clientes">
                    <div class="cc-empty">Elige una empresa para ver clientes.</div>
                </div>
                <div class="cc-asig-foot">
                    <div class="cc-legend mb-0">
                        <span><i style="background:#ecfdf5;border:1px solid #047857"></i> Ya asignado</span>
                        <span><i style="background:#f4f4f5;border:1px solid #0a0a0a"></i> Seleccionado ahora</span>
                    </div>
                    <div class="text-muted" style="font-size:.75rem" id="asig-cc-meta">Elige una empresa para cargar CardName de OINV + ORIN</div>
                </div>
            </div>
            <div class="cc-panel cc-asig-col">
                <div class="cc-panel-head">
                    <h3><i class="fa-solid fa-list"></i> 4. Productos con acceso</h3>
                    <label class="cc-todas-toggle">
                        <input type="checkbox" id="asig-cta-todas"> Todas
                    </label>
                </div>
                <div class="cc-grupos-cta-tools">
                    <select id="asig-cta-linea" class="cc-select" aria-label="Línea U_LINEA_QV" disabled>
                        <option value="">Todas las líneas</option>
                    </select>
                    <div class="cc-pick-search">
                        <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input id="asig-cta-q" class="cc-input" type="search" placeholder="Buscar producto…">
                    </div>
                </div>
                <div class="cc-pick-list cc-check-list" id="asig-cta-list">
                    <div class="cc-empty">Elige un cliente para ver los productos</div>
                </div>
                <div class="cc-asig-foot" id="asig-cta-foot">
                    <span class="text-muted" style="font-size:.78rem" id="asig-cta-sel">0 seleccionadas</span>
                </div>
            </div>
        </div>
        <div class="cc-panel cc-perm-panel">
            <div class="cc-panel-head"><h3><i class="fa-solid fa-shield"></i> Permisos</h3></div>
            <div class="cc-perm-grid is-wide" id="asig-permisos">
                @foreach($bootstrap['permisosCatalogo'] ?? [] as $p)
                    <label class="cc-perm-card">
                        <input type="checkbox" name="permiso" value="{{ $p['clave'] }}" {{ $p['clave'] === 'capturar' ? 'checked' : '' }}>
                        <strong>{{ $p['nombre'] }}</strong>
                        <span>{{ $p['descripcion'] ?? '' }}</span>
                    </label>
                @endforeach
                @if(empty($bootstrap['permisosCatalogo']))
                    <label class="cc-perm-card"><input type="checkbox" name="permiso" value="capturar" checked><strong>Capturar</strong><span>Puede capturar proyección mientras el ciclo esté Abierto</span></label>
                    <label class="cc-perm-card"><input type="checkbox" name="permiso" value="editar"><strong>Editar</strong><span>Puede modificar cantidades cuando el ciclo está En revisión o Cerrado</span></label>
                    <label class="cc-perm-card"><input type="checkbox" name="permiso" value="revisar"><strong>Revisar</strong><span>Puede consultar sin editar</span></label>
                @endif
            </div>
        </div>
        <div class="cc-apply-bar mb-2">
            <span class="cc-apply-hint">Aplica el cliente y los productos a la tabla de abajo. El usuario y la empresa se quedan seleccionados.</span>
            <button type="button" class="cc-btn cc-btn-ink cc-btn-lg" id="asig-guardar">
                <i class="fa-solid fa-plus"></i> Asignar
            </button>
        </div>
    </div>

    <div id="asig-resultados" class="cc-panel" hidden>
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-table"></i> Resultados de asignación</h3>
            <span class="text-muted" style="font-size:.8rem" id="asig-resumen-meta"></span>
        </div>
        <p class="text-muted mb-3" style="font-size:.85rem">Lo que vas asignando a este usuario, por empresa y cliente.</p>
        <div class="cc-table-wrap">
            <table class="cc-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Permisos</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="asig-resumen"></tbody>
            </table>
        </div>
    </div>

    <div class="cc-footer-actions">
        <a class="cc-btn" href="{{ route('pv.ciclo', $ciclo) }}">
            <i class="fa-solid fa-arrow-left"></i> Volver al ciclo
        </a>
        <div class="cc-footer-actions-end">
            <button type="button" class="cc-btn" id="asig-fin-seguir">
                <i class="fa-solid fa-arrow-left"></i> Guardar y salir
            </button>
            <button type="button" class="cc-btn cc-btn-ink" id="asig-fin-guardar">
                <i class="fa-solid fa-check"></i> Guardar
            </button>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('js/proyecciones-ventas.js') }}?v={{ (int) @filemtime(public_path('js/proyecciones-ventas.js')) }}"></script>
<script src="{{ asset('js/proyecciones-asignaciones.js') }}?v={{ (int) @filemtime(public_path('js/proyecciones-asignaciones.js')) }}"></script>
<script>
    CC.boot(Object.assign(@json($bootstrap), { page: 'asignacion' }));
    CCAsig.initWizard({
        cicloUrl: @json(route('pv.ciclo', $ciclo)),
        listUrl: @json(route('pv.asignaciones.index', $ciclo)),
        storeUrl: @json(route('pv.asignaciones.store', $ciclo)),
        csrf: @json(csrf_token()),
        usuarios: @json($bootstrap['usuarios'] ?? [])
    });
</script>
@endsection
