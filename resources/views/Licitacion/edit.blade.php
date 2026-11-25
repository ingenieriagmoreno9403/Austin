@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">
                            Editar Licitación
                            <span class="badge bg-orange fs-6">Folio: {{ $licitacion->folio }}</span>
                        </h2>
                        <p class="text-muted mb-0">Modificación de la licitación de precios</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-baseColor-light" title="Comparar Precios"
                        href="{{ route('licitaciones.comparar', $licitacion->id) }}">
                        <i class="fa-solid fa-scale-balanced me-1"></i> Ver Licitación
                    </a>
                </div>
            </div>
        </div>
    </div>

    <form id="form-licitacion" action="{{ route('licitaciones.update', $licitacion->id) }}" method="POST"
        enctype="multipart/form-data" class="needs-validation" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" id="detalle_json" name="detalle_json">

        <div class="lic-form__section">
            <div class="lic-form__section-head">
                <div class="lic-form__section-title-wrap">
                    <div class="lic-form__section-icon" aria-hidden="true">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div>
                        <h3 class="lic-form__section-title">1. Información general</h3>
                        <p class="lic-form__section-subtitle">Datos básicos de la licitación.</p>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="lic-form__label" for="nombre">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre" id="nombre"
                        value="{{ $licitacion->nombre }}" minlength="3" maxlength="50" required>
                </div>
                <div class="col-md-3">
                    <label class="lic-form__label" for="fecha_creacion">Fecha creación <span class="text-danger">*</span></label>
                    <input type="date" id="fecha_creacion" name="fecha_creacion"
                        class="form-control" value="{{ $licitacion->fecha_creacion }}" required>
                </div>
                <div class="col-md-3">
                    <label class="lic-form__label" for="fecha_limite">Fecha límite <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_limite" id="fecha_limite"
                        class="form-control" value="{{ $licitacion->fecha_limite }}" required>
                </div>
                <div class="col-12">
                    <label class="lic-form__label" for="descripcion_detalle">Descripción <span class="text-danger">*</span></label>
                    <textarea name="descripcion_detalle" id="descripcion_detalle"
                        class="form-control" required>{{ $licitacion->descripcion_detalle }}</textarea>
                </div>
                <div class="col-12">
                    <label class="lic-form__label" for="condiciones">Condiciones / reglas para proveedores <span class="text-danger">*</span></label>
                    <textarea name="condiciones" id="condiciones" class="form-control" rows="5" required
                        placeholder="Reglas de la empresa para poder cumplir esta licitación...">{{ $licitacion->condiciones }}</textarea>
                    <div class="form-text">Sin catálogo: las defines tú al crear/editar. El proveedor las ve y debe aceptarlas para cotizar.</div>
                </div>
            </div>
        </div>

        <div class="lic-form__section lic-form__section--proveedores" id="seccion-proveedores">
            <div class="lic-form__section-head">
                <div class="lic-form__section-title-wrap">
                    <div class="lic-form__section-icon" aria-hidden="true">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div>
                        <h3 class="lic-form__section-title">2. Proveedores invitados</h3>
                        <p class="lic-form__section-subtitle">Selecciona al menos un proveedor para continuar.</p>
                    </div>
                </div>
                <span class="lic-form__badge-req">
                    <i class="fa-solid fa-asterisk"></i> Obligatorio
                </span>
            </div>

            @livewire('proveedor-selector', ['licitacionId' => $licitacion->id, 'sololectura' => false])
            @error('proveedoresSeleccionados')
                <div class="alert alert-danger mt-3 mb-0">
                    {{ $message }}
                </div>
            @enderror

            <div class="row g-3 mt-3 pt-3" style="border-top: 1px solid #eef2f7;">
                <div class="col-md-6">
                    <label class="lic-form__label" for="proveedor_adjudicado_id">Proveedor adjudicado</label>
                    <select name="proveedor_adjudicado_id" id="proveedor_adjudicado_id" class="form-select">
                        <option value="">-- Selecciona --</option>
                        @foreach ($proveedoresInvitados as $proveedor)
                            @if(isset($proveedor->id) && isset($proveedor->nombre))
                                <option value="{{ $proveedor->id }}" {{ (string) $licitacion->proveedor_adjudicado_id === (string) $proveedor->id ? 'selected' : '' }}>
                                    {{ $proveedor->nombre }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div id="productos-container">
            <div id="alerta-productos-vacios" class="alert alert-warning mb-3" style="display: none;">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                <strong>Atención:</strong> Debe agregar al menos un producto a la licitación.
            </div>

            @if(is_null($licitacion->id_servicio))
                @livewire('licitacion-detalle', ['detalle' => old('detalle', $detalle ?? []), 'sololectura' => false])
            @else
                @livewire('licitacion-detalle', ['detalle' => old('detalle', $detalle ?? []), 'sololectura' => true])
            @endif
        </div>

        <div class="lic-form__actions">
            <button type="button" class="btn btn-outline-secondary"
                onclick="window.location='{{ route('licitaciones.index') }}'">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver
            </button>

            <button type="button" id="btn-guardar" onclick="guardarLicitacion()" class="btn btn-baseColor">
                <i class="fa-solid fa-check me-1"></i> Guardar cambios
            </button>

            <button type="button" id="btn-guardando" class="btn btn-baseColor d-none" disabled>
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Guardando...
            </button>
        </div>
    </form>

    {{-- Parámetros de evaluación (formulario independiente) --}}
    <div class="lic-form__section mt-3" id="seccion-parametros">
        <div class="lic-form__section-head">
            <div class="lic-form__section-title-wrap">
                <div class="lic-form__section-icon" aria-hidden="true">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <h3 class="lic-form__section-title">Parámetros de evaluación</h3>
                    <p class="lic-form__section-subtitle">
                        Ponderación por tipo/familia. La suma de pesos debe ser 100%.
                        @if($licitacion->tipoParam)
                            · Tipo actual: <strong>{{ $licitacion->tipoParam->nombre }}</strong>
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('licitaciones.parametros.index') }}" class="btn btn-sm btn-outline-secondary">Catálogo</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="post" action="{{ route('licitaciones.parametros.aplicarTipo', $licitacion->id) }}" class="row g-2 align-items-end mb-3">
            @csrf
            <div class="col-md-8">
                <label class="form-label">Aplicar plantilla de tipo</label>
                <select name="tipo_param_id" class="form-select" required>
                    <option value="">Selecciona tipo…</option>
                    @foreach(($tiposParam ?? []) as $t)
                        <option value="{{ $t->id }}" {{ (int) $licitacion->tipo_param_id === (int) $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-secondary w-100"
                        onclick="return confirm('Esto reemplaza los parámetros actuales de la licitación. ¿Continuar?');">
                    <i class="fa-solid fa-copy me-1"></i> Aplicar plantilla
                </button>
            </div>
        </form>

        @php
            $params = $licitacion->parametrosEval ?? collect();
            $sumaPesos = round($params->sum('peso_pct'), 2);
        @endphp

        <form method="post" action="{{ route('licitaciones.parametros.sync', $licitacion->id) }}" id="form-params-lic">
            @csrf
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge {{ abs($sumaPesos - 100) <= 0.05 ? 'bg-success' : 'bg-warning text-dark' }}">
                    Suma pesos: <span id="suma-pesos-lic">{{ $sumaPesos }}</span>%
                </span>
                <button type="button" class="btn btn-sm btn-baseColor" id="btn-add-param-lic">
                    <i class="fa-solid fa-plus"></i> Agregar parámetro
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle" id="tabla-params-lic">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Nombre</th>
                            <th>Peso %</th>
                            <th>Sentido</th>
                            <th>Tipo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($params as $i => $p)
                            <tr data-row>
                                <td>
                                    <input type="hidden" name="parametros[{{ $i }}][id]" value="{{ $p->id }}">
                                    <input type="number" name="parametros[{{ $i }}][orden]" class="form-control form-control-sm" value="{{ $p->orden }}" min="1" style="width:70px">
                                </td>
                                <td><input type="text" name="parametros[{{ $i }}][nombre]" class="form-control form-control-sm" value="{{ $p->nombre }}" required></td>
                                <td><input type="number" step="0.01" min="0" max="100" name="parametros[{{ $i }}][peso_pct]" class="form-control form-control-sm peso-input" value="{{ $p->peso_pct }}" required style="width:90px"></td>
                                <td>
                                    <select name="parametros[{{ $i }}][sentido]" class="form-select form-select-sm">
                                        @foreach(($sentidos ?? []) as $k => $label)
                                            <option value="{{ $k }}" {{ $p->sentido === $k ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="parametros[{{ $i }}][tipo_dato]" class="form-select form-select-sm">
                                        @foreach(($tiposDato ?? []) as $k => $label)
                                            <option value="{{ $k }}" {{ $p->tipo_dato === $k ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><button type="button" class="btn btn-sm btn-danger" data-del-param><i class="fa-solid fa-xmark"></i></button></td>
                            </tr>
                        @empty
                            <tr class="text-muted" id="params-empty-row"><td colspan="6" class="text-center py-3">Sin parámetros. Aplica un tipo o agrega manualmente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-2">
                <button type="submit" class="btn btn-baseColor">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar parámetros
                </button>
            </div>
        </form>
    </div>
</div>

<template id="tpl-param-lic">
    <tr data-row>
        <td>
            <input type="hidden" name="parametros[__I__][id]" value="">
            <input type="number" name="parametros[__I__][orden]" class="form-control form-control-sm" value="__ORD__" min="1" style="width:70px">
        </td>
        <td><input type="text" name="parametros[__I__][nombre]" class="form-control form-control-sm" required></td>
        <td><input type="number" step="0.01" min="0" max="100" name="parametros[__I__][peso_pct]" class="form-control form-control-sm peso-input" value="0" required style="width:90px"></td>
        <td>
            <select name="parametros[__I__][sentido]" class="form-select form-select-sm">
                @foreach(($sentidos ?? []) as $k => $label)
                    <option value="{{ $k }}">{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="parametros[__I__][tipo_dato]" class="form-select form-select-sm">
                @foreach(($tiposDato ?? []) as $k => $label)
                    <option value="{{ $k }}">{{ $label }}</option>
                @endforeach
            </select>
        </td>
        <td><button type="button" class="btn btn-sm btn-danger" data-del-param><i class="fa-solid fa-xmark"></i></button></td>
    </tr>
</template>

<script>
(function () {
    var tbody = document.querySelector('#tabla-params-lic tbody');
    var tpl = document.getElementById('tpl-param-lic');
    var idx = {{ $params->count() }};
    var btnAdd = document.getElementById('btn-add-param-lic');
    if (!tbody || !tpl || !btnAdd) return;

    function recalc() {
        var sum = 0;
        tbody.querySelectorAll('.peso-input').forEach(function (el) {
            sum += parseFloat(el.value || '0') || 0;
        });
        var badge = document.getElementById('suma-pesos-lic');
        if (badge) badge.textContent = sum.toFixed(2);
    }

    btnAdd.addEventListener('click', function () {
        var empty = document.getElementById('params-empty-row');
        if (empty) empty.remove();
        var html = tpl.innerHTML.replace(/__I__/g, String(idx++)).replace(/__ORD__/g, String(idx));
        tbody.insertAdjacentHTML('beforeend', html);
        recalc();
    });

    tbody.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-del-param]');
        if (!btn) return;
        btn.closest('tr').remove();
        if (!tbody.querySelector('[data-row]')) {
            tbody.innerHTML = '<tr class="text-muted" id="params-empty-row"><td colspan="6" class="text-center py-3">Sin parámetros.</td></tr>';
        }
        recalc();
    });
    tbody.addEventListener('input', function (ev) {
        if (ev.target.classList.contains('peso-input')) recalc();
    });
})();
</script>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
<script>
    function toggleBotonesGuardar(mostrarSpinner) {
        const btnGuardar = document.getElementById('btn-guardar');
        const btnGuardando = document.getElementById('btn-guardando');

        btnGuardar.classList.toggle('d-none', mostrarSpinner);
        btnGuardando.classList.toggle('d-none', !mostrarSpinner);
    }

    function guardarLicitacion() {
        document.getElementById('alerta-productos-vacios').style.display = 'none';
        toggleBotonesGuardar(true);
        formSubmitted = false;
        Livewire.emit('validarProductos');
    }

    window.addEventListener('producto-duplicado', event => {
        toggleBotonesGuardar(false);

        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = 11;
        toast.innerHTML = `
            <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        ${event.detail.mensaje}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        document.body.appendChild(toast);

        const toastElement = toast.querySelector('.toast');
        const bsToast = new bootstrap.Toast(toastElement, {autohide: true, delay: 5000});
        bsToast.show();

        toastElement.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    });

    let formSubmitted = false;

    window.addEventListener('set-detalle-json', function (event) {
        document.getElementById('detalle_json').value = event.detail.detalle;
    });

    window.addEventListener('productos-validados-listos', function() {
        Livewire.emit('validarProveedores');
    });

    window.addEventListener('proveedores-validos', function () {
        if (!formSubmitted) {
            const detalleJson = document.getElementById('detalle_json').value;
            if (!detalleJson) {
                toggleBotonesGuardar(false);
                Swal.fire({
                    icon: 'error',
                    title: '¡Error!',
                    text: 'No se pudieron procesar los detalles de los productos. Por favor, inténtelo de nuevo.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            formSubmitted = true;
            document.querySelector('#form-licitacion').submit();
        }
    });

    window.addEventListener('proveedores-no-validos', function (event) {
        toggleBotonesGuardar(false);
        formSubmitted = false;
        const seccion = document.getElementById('seccion-proveedores');
        if (seccion) {
            seccion.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        Swal.fire({
            icon: 'warning',
            title: 'Proveedores requeridos',
            text: event.detail.mensaje || 'Debes seleccionar al menos un proveedor.',
            confirmButtonColor: '#0284c7'
        });
    });

    window.addEventListener('error-evento', function (event) {
        toggleBotonesGuardar(false);
        formSubmitted = false;

        Swal.fire({
            icon: 'error',
            title: '¡Error!',
            text: event.detail.mensaje,
            confirmButtonColor: '#3085d6'
        });

        document.getElementById('productos-container').scrollIntoView({behavior: 'smooth'});
    });
</script>
@endsection
