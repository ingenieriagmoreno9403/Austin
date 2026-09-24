@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}?v={{ (int) @filemtime(public_path('css/centros-costos.css')) }}" rel="stylesheet">
@endsection

@section('content')
<div class="cc-page" id="cc-app">
    <div class="cc-header">
        <div>
            <div class="cc-kicker">Ciclo <span id="period-codigo">{{ $bootstrap['cicloCodigo'] ?? '' }}</span> · centros y permisos</div>
            <h1 class="cc-title" id="period-nombre">Ciclo</h1>
            <p class="cc-sub">Revisa las asignaciones del ciclo o crea una nueva: empresa SAP,
                 usuario, centro, cuentas y permisos.</p>
        </div>
        <div class="cc-header-actions">
            <a class="cc-btn" href="{{ route('centros.admin') }}">
                <i class="fa-solid fa-arrow-left"></i> Ciclos
            </a>
            <button type="button" class="cc-btn" id="btn-importar-usuarios">
                <i class="fa-solid fa-unlock"></i> Importe masivo
            </button>
            <a class="cc-btn cc-btn-ink" href="{{ route('centros.asignar', $bootstrap['cicloCodigo'] ?? '') }}">
                <i class="fa-solid fa-user-plus"></i> Nueva asignación
            </a>
        </div>
    </div>

    @include('CentrosCostos.partials.nav')

    <div class="cc-banner">
        <div>
            <strong>Ciclo para presupuestar</strong>
            <p>
                Real <span id="period-anio-ref">2026</span> / Ppto <span id="period-anio">2027</span>
                · Ventana: <span id="period-rango">—</span>
            </p>
        </div>
        <div class="cc-banner-meta">
            <div class="cc-estado-pick cc-estado-pick--banner" id="period-estado-pick" data-estado="abierto">
                <button type="button" class="cc-estado-btn" id="period-estado-btn" aria-haspopup="listbox" aria-expanded="false" aria-label="Estado del ciclo" title="Cambiar estado">
                    <span class="cc-estado-dot"></span>
                    <span class="cc-estado-label" id="period-estado-label">Abierto</span>
                    <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </button>
                <div class="cc-estado-menu" id="period-estado-menu" role="listbox" hidden></div>
            </div>
            <span class="cc-badge cc-banner-fx" id="period-fx">—</span>
            <div class="cc-banner-actions">
                <button type="button" class="cc-btn" onclick="CC.showModal('modalPeriodo')">
                    <i class="fa-solid fa-pen"></i> Editar
                </button>
                <button type="button" class="cc-btn cc-btn-danger" id="btn-eliminar-ciclo">
                    <i class="fa-solid fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    <div class="cc-kpis" id="asig-kpis">
        <div class="cc-kpi">
            <div class="label">Empresas</div>
            <div class="value" id="kpi-asig-emp">—</div>
            <div class="hint" id="kpi-asig-emp-hint">Empresas SAP con algo asignado</div>
        </div>
        <div class="cc-kpi">
            <div class="label">Centros de costos</div>
            <div class="value" id="kpi-asig-cc">—</div>
            <div class="hint" id="kpi-asig-cc-hint">Centros que ya tienen asignación</div>
        </div>
        <div class="cc-kpi">
            <div class="label">Usuarios</div>
            <div class="value" id="kpi-asig-user">—</div>
            <div class="hint" id="kpi-asig-user-hint">Usuarios con centros de costos</div>
        </div>
    </div>

    <div class="cc-panel">
        <div class="cc-panel-head">
            <div>
                <h3><i class="fa-solid fa-user-lock"></i> Asignaciones del ciclo</h3>
                <div class="text-muted" id="asig-tabla-meta" style="font-size:.75rem;font-weight:600;margin-top:.2rem"></div>
            </div>
        </div>
        <div class="cc-filters cc-filters-asig">
            <div class="cc-pick-search">
                <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-building"></i></span>
                <input id="asig-q-empresa" class="cc-input" type="search" placeholder="Buscar por empresa…" aria-label="Buscar por empresa">
            </div>
            <div class="cc-pick-search">
                <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                <input id="asig-q-usuario" class="cc-input" type="search" placeholder="Buscar por usuario…" aria-label="Buscar por usuario">
            </div>
            <div class="cc-pick-search">
                <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-sitemap"></i></span>
                <input id="asig-q-centro" class="cc-input" type="search" placeholder="Buscar por centro de costo…" aria-label="Buscar por centro de costo">
            </div>
        </div>
        <div class="cc-table-wrap">
            <table class="cc-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Empresa</th>
                        <th>Centro de costos</th>
                        <th>Cuentas</th>
                        <th>Permisos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="asig-tbody">
                    <tr><td colspan="6"><div class="cc-empty">Aún no hay asignaciones. Usa Nueva asignación para crear la primera.</div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('CentrosCostos.partials.modal-ciclo')
@include('CentrosCostos.partials.modals-asignacion')

<div class="modal fade cc-modal" id="modalImportarUsuarios" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="form-importar-usuarios">
            <div class="modal-header">
                <h5 class="modal-title">Importar masivo por usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:.85rem">
                    Este permiso no es por empresa ni por centro. Quien lo tenga puede bajar la plantilla con <strong>todas</strong> sus asignaciones del ciclo e importar los montos de una vez.
                </p>
                <div class="cc-form-kicker">Agregar usuario</div>
                <div class="cc-pick-search">
                    <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input id="imp-user-q" class="cc-input" type="search" placeholder="Buscar por nombre o correo…">
                </div>
                <div id="imp-user-pick" class="cc-pick-list cc-imp-user-pick" hidden role="listbox"></div>
                <div class="cc-table-wrap" style="max-height:280px">
                    <table class="cc-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Asignaciones</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="imp-user-tbody">
                            <tr><td colspan="3"><div class="cc-empty">Nadie puede importar masivo en este ciclo.</div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="cc-modal-actions">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="cc-btn cc-btn-ink">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('js/centros-costos.js') }}?v={{ (int) @filemtime(public_path('js/centros-costos.js')) }}"></script>
<script src="{{ asset('js/centros-asignaciones.js') }}?v={{ (int) @filemtime(public_path('js/centros-asignaciones.js')) }}"></script>
<script>
    CC.boot(Object.assign(@json($bootstrap), { page: 'admin-ciclo' }));
    CCAsig.initCiclo({
        ciclo: @json($bootstrap['cicloCodigo'] ?? ''),
        usuarios: @json($bootstrap['usuarios'] ?? []),
        permisos: @json($bootstrap['permisosCatalogo'] ?? []),
        csrf: @json(csrf_token())
    });
</script>
@endsection
