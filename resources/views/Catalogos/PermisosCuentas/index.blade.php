@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('warningGuardar'))
@php
        echo '<script language="JavaScript">';
        echo 'const Toast = Swal.mixin({';
        echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
        echo 'didOpen: (toast) => {';
        echo '  toast.onmouseenter = Swal.stopTimer;';
        echo '  toast.onmouseleave = Swal.resumeTimer;}});';
        echo 'Toast.fire({ icon: "warning",title: "¡No se a efectuado la acción!", text: "Seleccione un empleado antes de guardar"});';
        echo '</script>';
@endphp
@endif

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    #accordionPermisosModulos .accordion-item {
        border: 1px solid #dee2e6;
        border-radius: 10px !important;
        margin-bottom: 0.75rem;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    #accordionPermisosModulos .accordion-button {
        padding: 0.85rem 1rem;
        background: #f8f9fa;
        box-shadow: none !important;
    }
    #accordionPermisosModulos .accordion-button:not(.collapsed) {
        background: #eef3f8;
        color: inherit;
    }
    #accordionPermisosModulos .accordion-header.modulo-accordion-header {
        display: flex;
        align-items: stretch;
    }
    #accordionPermisosModulos .modulo-toggle-btn {
        flex: 1;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    #accordionPermisosModulos .modulo-actions-panel {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0 0.85rem;
        background: #f8f9fa;
        border-left: 1px solid #dee2e6;
        border-bottom: 1px solid #dee2e6;
    }
    #accordionPermisosModulos .accordion-button:not(.collapsed) + .modulo-actions-panel,
    #accordionPermisosModulos .accordion-item:has(.accordion-button:not(.collapsed)) .modulo-actions-panel {
        background: #eef3f8;
    }
    .accordion-header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .modulo-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
        flex: 1;
    }
    .modulo-info-text {
        min-width: 0;
    }
    .modulo-info-text strong {
        font-size: 0.92rem;
    }
    .modulo-depto {
        font-size: 0.78rem;
        color: #6c757d;
    }
    .modulo-count-badge {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.3rem 0.55rem;
        border-radius: 999px;
        white-space: nowrap;
        background: #e9ecef;
        color: #495057;
    }
    .modulo-count-badge.has-selection {
        background: rgba(25, 135, 84, 0.15);
        color: #198754;
    }
    .modulo-quick-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        flex-shrink: 0;
    }
    .modulo-action-btn {
        border: 1px solid transparent;
        border-radius: 999px;
        padding: 0.35rem 0.85rem;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.18s ease;
        white-space: nowrap;
    }
    .modulo-action-btn i {
        font-size: 0.7rem;
    }
    .modulo-action-select {
        background: linear-gradient(135deg, #d1e7dd 0%, #c3e6cb 100%);
        color: #146c43;
        border-color: #badbcc;
    }
    .modulo-action-select:hover {
        background: #198754;
        color: #fff;
        border-color: #198754;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(25, 135, 84, 0.25);
    }
    .modulo-action-clear {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #6c757d;
        border-color: #dee2e6;
    }
    .modulo-action-clear:hover {
        background: #6c757d;
        color: #fff;
        border-color: #6c757d;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(108, 117, 125, 0.25);
    }
    .permiso-item {
        padding: 0.35rem 0.5rem;
        border-radius: 4px;
        transition: background 0.15s;
    }
    .permiso-item:hover {
        background: #f1f3f5;
    }
    .permiso-item.hidden-by-filter {
        display: none !important;
    }
    .modulo-permisos-card.hidden-by-filter {
        display: none !important;
    }
    .permisos-scroll {
        max-height: 220px;
        overflow-y: auto;
    }
    .tipo-badge {
        font-size: 0.7rem;
        min-width: 72px;
        text-align: center;
    }
    #permisosContainer.disabled-section {
        opacity: 0.5;
        pointer-events: none;
    }
    .sticky-save-bar {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 1px solid #dee2e6;
        padding: 1rem;
        z-index: 10;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.06);
    }
    @media (max-width: 768px) {
        #accordionPermisosModulos .accordion-header.modulo-accordion-header {
            flex-direction: column;
        }
        #accordionPermisosModulos .modulo-toggle-btn {
            border-radius: 0;
        }
        #accordionPermisosModulos .modulo-actions-panel {
            justify-content: center;
            padding: 0.6rem;
            border-left: none;
            border-top: 1px solid #dee2e6;
        }
        .modulo-action-btn span {
            display: none;
        }
        .modulo-action-btn {
            padding: 0.45rem 0.6rem;
        }
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Permisos de Cuentas</h2>
                        <p class="text-muted mb-0">Asigne cuentas y cajas por módulo con un solo guardado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <div class="card-icon me-3">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h5 class="mb-0 fw-bold">Gestionar Permisos</h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="/CatalogoGeneral/PermisosCuentas/AñadirPermiso" method="POST" class="needs-validation modern-form" id="formPermisos" novalidate>
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-5 col-12 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-user me-2"></i>Empleado activo
                                </label>
                                <select class="form-select modern-select select2" name="idusuario" id="idusuario" required>
                                    <option value="">Buscar empleado...</option>
                                    @foreach($varusers as $usuario)
                                        <option value="{{ $usuario->id }}">
                                            {{ $usuario->Nombre }} — {{ $usuario->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Seleccione un empleado.</div>
                            </div>
                            <div class="col-md-4 col-12 mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-search me-2"></i>Filtrar cuentas / cajas
                                </label>
                                <input type="text" class="form-control modern-input" id="filtroPermisos" placeholder="Escriba para buscar..." disabled>
                            </div>
                            <div class="col-md-3 col-12 mb-3 d-flex align-items-end">
                                <div class="w-100">
                                    <span class="badge text-bg-secondary w-100 py-2" id="contadorPermisos">
                                        Seleccione un empleado
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div id="permisosLoading" class="text-center py-4 d-none">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted mt-2 mb-0">Cargando permisos del empleado...</p>
                        </div>

                        <div id="permisosContainer" class="disabled-section">
                            <div class="accordion" id="accordionPermisosModulos">
                            @foreach($varModulosCuentas as $modulo)
                                <div class="accordion-item modulo-permisos-card" data-modulo-id="{{ $modulo->id }}">
                                    <h2 class="accordion-header modulo-accordion-header" id="headingModulo{{ $modulo->id }}">
                                        <button class="accordion-button accordion_bg collapsed modulo-toggle-btn" type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#modulo{{ $modulo->id }}"
                                            aria-expanded="false"
                                            aria-controls="modulo{{ $modulo->id }}">
                                            <div class="accordion-header-content">
                                                <div class="modulo-info">
                                                    <span class="badge text-bg-dark rounded-pill">{{ $modulo->id }}</span>
                                                    <div class="modulo-info-text">
                                                        <strong>{{ $modulo->nombreModulo }}</strong>
                                                        <div class="modulo-depto">{{ $modulo->nombreDepartamento }}</div>
                                                    </div>
                                                </div>
                                                <span class="modulo-count-badge" id="badgeModulo{{ $modulo->id }}">0 seleccionados</span>
                                            </div>
                                        </button>
                                        <div class="modulo-actions-panel">
                                            <button type="button" class="modulo-action-btn modulo-action-select btn-seleccionar-modulo" data-modulo="{{ $modulo->id }}" title="Seleccionar todo en este módulo">
                                                <i class="fas fa-check-double"></i>
                                                <span>Seleccionar todo</span>
                                            </button>
                                            <button type="button" class="modulo-action-btn modulo-action-clear btn-quitar-modulo" data-modulo="{{ $modulo->id }}" title="Quitar todo en este módulo">
                                                <i class="fas fa-eraser"></i>
                                                <span>Quitar todo</span>
                                            </button>
                                        </div>
                                    </h2>
                                    <div id="modulo{{ $modulo->id }}" class="accordion-collapse collapse"
                                        aria-labelledby="headingModulo{{ $modulo->id }}"
                                        data-bs-parent="#accordionPermisosModulos">
                                        <div class="accordion-body p-3">
                                        <div class="row g-3">
                                            <div class="col-md-{{ $modulo->aplicacion == 'todo' ? '4' : ($modulo->aplicacion == 'cuenta_caja' ? '6' : '12') }}">
                                                <div class="border bg-light p-2 rounded-1 h-100">
                                                    <h6 class="mb-2"><i class="fas fa-credit-card me-2"></i>Cuentas</h6>
                                                    <div class="permisos-scroll">
                                                        @foreach($varCuentas as $cuenta)
                                                            @php
                                                                $permisoKey = $modulo->id . '|cuenta|' . $cuenta->id;
                                                                $permisoId = 'permiso_' . $modulo->id . '_cuenta_' . $cuenta->id;
                                                            @endphp
                                                            <div class="permiso-item" data-label="{{ strtolower($cuenta->id . ' ' . $cuenta->descripcion) }}">
                                                                <div class="form-check">
                                                                    <input class="form-check-input permiso-check"
                                                                        type="checkbox"
                                                                        name="permisos[]"
                                                                        value="{{ $permisoKey }}"
                                                                        id="{{ $permisoId }}"
                                                                        data-modulo="{{ $modulo->id }}"
                                                                        data-tipo="cuenta"
                                                                        data-id="{{ $cuenta->id }}"
                                                                        disabled>
                                                                    <label class="form-check-label" for="{{ $permisoId }}">
                                                                        <span class="badge text-bg-primary tipo-badge me-1">Cuenta</span>
                                                                        {{ $cuenta->id }} — {{ $cuenta->descripcion }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>

                                            @if($modulo->aplicacion == 'todo' || $modulo->aplicacion == 'cuenta_caja')
                                            <div class="col-md-{{ $modulo->aplicacion == 'todo' ? '4' : '6' }}">
                                                <div class="border bg-light p-2 rounded-1 h-100">
                                                    <h6 class="mb-2"><i class="fas fa-cash-register me-2"></i>Cajas</h6>
                                                    <div class="permisos-scroll">
                                                        @foreach($varCajas as $caja)
                                                            @if($caja->tipo == 'caja')
                                                                @php
                                                                    $permisoKey = $modulo->id . '|caja|' . $caja->id;
                                                                    $permisoId = 'permiso_' . $modulo->id . '_caja_' . $caja->id;
                                                                    $sucursal = $caja->nombre_sucursal ?? '';
                                                                @endphp
                                                                <div class="permiso-item" data-label="{{ strtolower($caja->id . ' ' . $caja->nombre . ' ' . $sucursal) }}">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input permiso-check"
                                                                            type="checkbox"
                                                                            name="permisos[]"
                                                                            value="{{ $permisoKey }}"
                                                                            id="{{ $permisoId }}"
                                                                            data-modulo="{{ $modulo->id }}"
                                                                            data-tipo="caja"
                                                                            data-id="{{ $caja->id }}"
                                                                            disabled>
                                                                        <label class="form-check-label" for="{{ $permisoId }}">
                                                                            <span class="badge text-bg-success tipo-badge me-1">Caja</span>
                                                                            {{ $caja->id }} — {{ $caja->nombre }}
                                                                            @if($sucursal)
                                                                                <small class="text-muted">({{ $sucursal }})</small>
                                                                            @endif
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            @endif

                                            @if($modulo->aplicacion == 'todo')
                                            <div class="col-md-4">
                                                <div class="border bg-light p-2 rounded-1 h-100">
                                                    <h6 class="mb-2"><i class="fas fa-wallet me-2"></i>Cajas Chicas</h6>
                                                    <div class="permisos-scroll">
                                                        @foreach($varCajas as $cajaChica)
                                                            @if($cajaChica->tipo == 'caja_chica')
                                                                @php
                                                                    $permisoKey = $modulo->id . '|caja_chica|' . $cajaChica->id;
                                                                    $permisoId = 'permiso_' . $modulo->id . '_caja_chica_' . $cajaChica->id;
                                                                @endphp
                                                                <div class="permiso-item" data-label="{{ strtolower($cajaChica->id . ' ' . $cajaChica->nombre) }}">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input permiso-check"
                                                                            type="checkbox"
                                                                            name="permisos[]"
                                                                            value="{{ $permisoKey }}"
                                                                            id="{{ $permisoId }}"
                                                                            data-modulo="{{ $modulo->id }}"
                                                                            data-tipo="caja_chica"
                                                                            data-id="{{ $cajaChica->id }}"
                                                                            disabled>
                                                                        <label class="form-check-label" for="{{ $permisoId }}">
                                                                            <span class="badge text-bg-info tipo-badge me-1">C. Chica</span>
                                                                            {{ $cajaChica->id }} — {{ $cajaChica->nombre }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            </div>
                        </div>

                        <div class="sticky-save-bar mt-3 rounded">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Al marcar o desmarcar y guardar, los permisos se agregan o eliminan automáticamente.
                                </small>
                                @if($permisos1 == "añadir_permisoCuenta")
                                    <button class="btn btn-green px-4" type="submit" id="btnGuardarPermisos" disabled>
                                        <i class="fa-solid fa-floppy-disk"></i> Guardar permisos
                                    </button>
                                @else
                                    <button class="btn btn-green px-4" type="button" disabled>
                                        <i class="fa-solid fa-floppy-disk"></i> Guardar permisos
                                    </button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center card-header">
                        <div class="d-flex align-items-center">
                            <div class="card-icon m-2">
                                <i class="fas fa-table"></i>
                            </div>
                            <h5 class="mb-0 fw-bold">Permisos Aplicados</h5>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-greendark text-light m-3" id="totalRegistros">
                                <b>Total</b> {{ count($varModulosPermisosUser) }} registros
                            </span>
                        </div>
                    </div>

                    <div class="table-wrapper p-3 pt-0">
                        <table class="table modern-table table-hover table-striped" id="table">
                            <thead>
                                <tr>
                                    <th class="table-header text-truncate"><i class="fas fa-user me-2"></i>Usuario</th>
                                    <th class="table-header text-truncate"><i class="fas fa-id-badge me-2"></i>Empleado</th>
                                    <th class="table-header text-truncate"><i class="fas fa-cube me-2"></i>Módulo</th>
                                    <th class="table-header text-truncate"><i class="fas fa-tag me-2"></i>Tipo</th>
                                    <th class="table-header text-truncate"><i class="fas fa-credit-card me-2"></i>Cuenta / Caja</th>
                                    <th class="table-header text-truncate"><i class="fas fa-cogs me-2"></i>Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($varModulosPermisosUser as $date)
                                <tr class="table-row">
                                    <td class="table-cell"><span class="fw-bold text-primary">{{ $date->nameUser }}</span></td>
                                    <td class="table-cell">{{ $date->nombreEmpleado }}</td>
                                    <td class="table-cell">{{ $date->modulo }}</td>
                                    <td class="table-cell">
                                        @if($date->tipo == "cuenta")
                                            <span class="badge text-bg-primary fw-light">Cuenta</span>
                                        @elseif($date->tipo == "caja")
                                            <span class="badge text-bg-success fw-light">Caja</span>
                                        @elseif($date->tipo == "caja_chica")
                                            <span class="badge text-bg-info fw-light">Caja Chica</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">{{ $date->tipo_nombre }}</td>
                                    <td class="table-cell">
                                        @if($permisos2 == "eliminar_permisoCuenta")
                                            <button class="btn btn-danger m-0" type="button" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $date->id }}">
                                                <i class="fa-solid fa-trash fs-8"></i> Eliminar
                                            </button>
                                        @else
                                            <button class="btn btn-danger m-0" type="button" disabled>
                                                <i class="fa-solid fa-trash fs-8"></i> Eliminar
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($varModulosPermisosUser as $date)
<div class="modal fade" id="cancelModal{{ $date->id }}" tabindex="-1" aria-labelledby="exampleModalLabel{{ $date->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <div class="card-icon me-3"><i class="fas fa-trash"></i></div>
                    <h4 class="modal-title mb-0" id="exampleModalLabel{{ $date->id }}">Eliminar Permiso Aplicado</h4>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <p class="text-dark fs-5">¿Está seguro que desea eliminar este permiso aplicado?</p>
            </div>
            <div class="modal-footer text-end">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark"></i> Cerrar
                </button>
                <a href="/CatalogoGeneral/PermisosCuentas/EliminarPermiso/{{ $date->id }}" class="btn btn-green">
                    <i class="fa-solid fa-check"></i> Aplicar
                </a>
            </div>
        </div>
    </div>
</div>
@endforeach

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectUsuario = document.getElementById('idusuario');
    const filtroInput = document.getElementById('filtroPermisos');
    const contador = document.getElementById('contadorPermisos');
    const container = document.getElementById('permisosContainer');
    const loading = document.getElementById('permisosLoading');
    const btnGuardar = document.getElementById('btnGuardarPermisos');
    const checks = () => document.querySelectorAll('.permiso-check');

    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('#idusuario').select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar empleado activo...',
            allowClear: true,
            width: '100%'
        });
    }

    function actualizarContadorModulo(moduloId) {
        const badge = document.getElementById('badgeModulo' + moduloId);
        if (!badge) return;
        const total = document.querySelectorAll('.permiso-check[data-modulo="' + moduloId + '"]:checked').length;
        badge.textContent = total + (total === 1 ? ' seleccionado' : ' seleccionados');
        badge.classList.toggle('has-selection', total > 0);
    }

    function actualizarContadoresModulos() {
        document.querySelectorAll('.modulo-permisos-card').forEach(function (card) {
            actualizarContadorModulo(card.dataset.moduloId);
        });
    }

    function actualizarContador() {
        const total = document.querySelectorAll('.permiso-check:checked').length;
        contador.textContent = total + ' permiso(s) seleccionado(s)';
        contador.className = 'badge w-100 py-2 ' + (total > 0 ? 'text-bg-success' : 'text-bg-secondary');
        actualizarContadoresModulos();
    }

    function expandirModulosConPermisos() {
        document.querySelectorAll('.modulo-permisos-card').forEach(function (card) {
            const moduloId = card.dataset.moduloId;
            const tieneSeleccion = document.querySelectorAll('.permiso-check[data-modulo="' + moduloId + '"]:checked').length > 0;
            if (tieneSeleccion) {
                const collapse = document.getElementById('modulo' + moduloId);
                if (collapse && typeof bootstrap !== 'undefined') {
                    bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
                }
            }
        });
    }

    function habilitarPermisos(habilitar) {
        checks().forEach(function (chk) {
            chk.disabled = !habilitar;
        });
        filtroInput.disabled = !habilitar;
        container.classList.toggle('disabled-section', !habilitar);
        if (btnGuardar) {
            btnGuardar.disabled = !habilitar;
        }
    }

    function limpiarSeleccion() {
        checks().forEach(function (chk) {
            chk.checked = false;
        });
        actualizarContador();
    }

    function marcarPermisos(permisos) {
        limpiarSeleccion();
        permisos.forEach(function (p) {
            const id = 'permiso_' + p.id_modulo + '_' + p.tipo + '_' + p.id_tipo;
            const el = document.getElementById(id);
            if (el) {
                el.checked = true;
            }
        });
        actualizarContador();
    }

    function cargarPermisosUsuario(userId) {
        if (!userId) {
            habilitarPermisos(false);
            limpiarSeleccion();
            contador.textContent = 'Seleccione un empleado';
            return;
        }

        loading.classList.remove('d-none');
        container.classList.add('disabled-section');

        fetch('/CatalogoGeneral/PermisosCuentas/PermisosUsuario/' + userId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            marcarPermisos(data);
            habilitarPermisos(true);
            expandirModulosConPermisos();
        })
        .catch(function () {
            limpiarSeleccion();
            habilitarPermisos(true);
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los permisos del empleado.' });
            }
        })
        .finally(function () {
            loading.classList.add('d-none');
        });
    }

    function aplicarFiltro(texto) {
        const termino = texto.trim().toLowerCase();
        document.querySelectorAll('.modulo-permisos-card').forEach(function (card) {
            let visiblesEnModulo = 0;
            card.querySelectorAll('.permiso-item').forEach(function (item) {
                const coincide = !termino || (item.dataset.label || '').includes(termino);
                item.classList.toggle('hidden-by-filter', !coincide);
                if (coincide) visiblesEnModulo++;
            });
            card.classList.toggle('hidden-by-filter', termino.length > 0 && visiblesEnModulo === 0);
        });
    }

    $('#idusuario').on('change', function () {
        cargarPermisosUsuario(this.value);
    });

    filtroInput.addEventListener('input', function () {
        aplicarFiltro(this.value);
    });

    document.querySelectorAll('.permiso-check').forEach(function (chk) {
        chk.addEventListener('change', actualizarContador);
    });

    document.querySelectorAll('.btn-seleccionar-modulo, .btn-quitar-modulo').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    document.querySelectorAll('.btn-seleccionar-modulo').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const moduloId = this.dataset.modulo;
            document.querySelectorAll('.permiso-check[data-modulo="' + moduloId + '"]').forEach(function (chk) {
                if (!chk.closest('.permiso-item').classList.contains('hidden-by-filter')) {
                    chk.checked = true;
                }
            });
            actualizarContador();
        });
    });

    document.querySelectorAll('.btn-quitar-modulo').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const moduloId = this.dataset.modulo;
            document.querySelectorAll('.permiso-check[data-modulo="' + moduloId + '"]').forEach(function (chk) {
                if (!chk.closest('.permiso-item').classList.contains('hidden-by-filter')) {
                    chk.checked = false;
                }
            });
            actualizarContador();
        });
    });

    document.getElementById('formPermisos').addEventListener('submit', function () {
        checks().forEach(function (chk) {
            chk.disabled = false;
        });
    });
});
</script>

@endsection
