@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
    .partida-row {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: .85rem;
        margin-bottom: .65rem;
        background: #fafbfc;
    }
    .partida-row .form-label {
        font-size: .78rem;
        font-weight: 700;
        color: #4b5563;
        margin-bottom: .2rem;
    }
    .req-prod-ac {
        position: relative;
    }
    .req-prod-sugerencias {
        z-index: 1080;
        max-height: 260px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        background: #fff;
    }
    .req-prod-sugerencias .list-group-item {
        cursor: pointer;
        font-size: .85rem;
        padding: .45rem .7rem;
        border-left: 0;
        border-right: 0;
    }
    .req-prod-sugerencias .list-group-item:hover,
    .req-prod-sugerencias .list-group-item.active {
        background: #f3f4f6;
    }
    .req-prod-sugerencias .req-prod-sku {
        color: #6b7280;
        font-size: .75rem;
    }
    .req-prod-hint {
        font-size: .72rem;
        color: #6b7280;
        margin-top: .2rem;
    }
</style>

@php
    $edit = (bool) $requisicion;
    $prioridadSel = old('prioridad', $requisicion->prioridad ?? 'normal');
@endphp

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">
                            {{ $edit ? ('Editar '.$requisicion->folio) : 'Nueva requisición' }}
                        </h2>
                        <p class="text-muted mb-0">
                            {{ $edit ? 'Modifica encabezado y partidas del borrador' : 'Captura necesidad con partidas y justificación' }}
                        </p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="{{ $edit ? route('requisiciones.show', $requisicion->id) : route('requisiciones.index') }}"
                       class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="post"
          action="{{ $edit ? route('requisiciones.update', $requisicion->id) : route('requisiciones.store') }}"
          id="form-req"
          class="needs-validation"
          novalidate>
        @csrf
        @if($edit) @method('PUT') @endif

        <div class="lic-form__section">
            <div class="lic-form__section-head">
                <div class="lic-form__section-title-wrap">
                    <div class="lic-form__section-icon" aria-hidden="true">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div>
                        <h3 class="lic-form__section-title">1. Encabezado</h3>
                        <p class="lic-form__section-subtitle">Datos generales de la solicitud.</p>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="lic-form__label" for="fecha_solicitud">Fecha solicitud</label>
                    <input type="date" id="fecha_solicitud" name="fecha_solicitud" class="form-control"
                           value="{{ old('fecha_solicitud', optional($requisicion->fecha_solicitud ?? now())->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="lic-form__label" for="fecha_requerida">Fecha requerida</label>
                    <input type="date" id="fecha_requerida" name="fecha_requerida" class="form-control"
                           value="{{ old('fecha_requerida', optional($requisicion->fecha_requerida ?? null)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="lic-form__label" for="prioridad">Prioridad <span class="text-danger">*</span></label>
                    <select id="prioridad" name="prioridad" class="form-select" required>
                        @foreach($prioridades as $k => $label)
                            <option value="{{ $k }}" {{ $prioridadSel === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="lic-form__label">Solicitante</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                </div>
                <div class="col-md-6">
                    <label class="lic-form__label" for="tipo_param_id">Tipo / familia <span class="text-danger">*</span></label>
                    @php $tipoSel = old('tipo_param_id', $requisicion->tipo_param_id ?? ''); @endphp
                    <select id="tipo_param_id" name="tipo_param_id" class="form-select" required>
                        <option value="">Selecciona…</option>
                        @foreach(($tiposParam ?? []) as $t)
                            <option value="{{ $t->id }}" {{ (string) $tipoSel === (string) $t->id ? 'selected' : '' }}
                                    title="{{ $t->descripcion }}">
                                {{ $t->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Define los parámetros de evaluación al pasar a licitación.</div>
                </div>
                <div class="col-md-6">
                    <label class="lic-form__label" for="centro_costo">Centro de costos <span class="text-danger">*</span></label>
                    @php $ccSel = old('centro_costo', $requisicion->centro_costo ?? ''); @endphp
                    <select id="centro_costo" name="centro_costo" class="form-select" required>
                        <option value="">Selecciona…</option>
                        @forelse(($centrosCosto ?? []) as $cc)
                            <option value="{{ $cc->value }}" {{ $ccSel === $cc->value ? 'selected' : '' }}>
                                {{ $cc->label }}
                            </option>
                        @empty
                            <option value="" disabled>No tienes centros de costo asignados</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-12">
                    <label class="lic-form__label" for="justificacion">Justificación</label>
                    <textarea id="justificacion" name="justificacion" class="form-control" rows="3"
                              placeholder="¿Para qué se necesita?">{{ old('justificacion', $requisicion->justificacion ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="lic-form__section">
            <div class="lic-form__section-head">
                <div class="lic-form__section-title-wrap">
                    <div class="lic-form__section-icon" aria-hidden="true">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <h3 class="lic-form__section-title">2. Partidas</h3>
                        <p class="lic-form__section-subtitle">Productos del catálogo o conceptos no catalogados.</p>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-nuevo-producto">
                        <i class="fa-solid fa-box-open"></i> Nuevo producto en catálogo
                    </button>
                    <button type="button" class="btn btn-baseColor btn-sm" id="btn-add-partida">
                        <i class="fa-solid fa-plus"></i> Agregar partida
                    </button>
                </div>
            </div>

            <div id="partidas-box"
                 data-url-productos="{{ route('requisiciones.api.productos') }}"
                 data-url-crear-producto="{{ route('requisiciones.api.productos.store') }}"
                 data-csrf="{{ csrf_token() }}"></div>
            <p class="text-muted mb-0 mt-2" style="font-size:.8rem">
                Busca coincidencias (2+ caracteres), captura un concepto libre, o registra un producto nuevo en el catálogo del sistema.
            </p>
        </div>

        <div class="lic-form__actions">
            <a href="{{ $edit ? route('requisiciones.show', $requisicion->id) : route('requisiciones.index') }}"
               class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver
            </a>
            <button type="submit" name="enviar" value="0" class="btn btn-outline-secondary">
                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar borrador
            </button>
            <button type="submit" name="enviar" value="1" class="btn btn-baseColor">
                <i class="fa-solid fa-paper-plane me-1"></i> Guardar y enviar
            </button>
        </div>
    </form>
</div>

<template id="tpl-partida">
    <div class="partida-row" data-row>
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Descripción <span class="text-danger">*</span></label>
                <input type="hidden" name="partidas[__I__][producto_id]" data-f="producto_id">
                <input type="hidden" name="partidas[__I__][codigo]" data-f="codigo">
                <input type="hidden" name="partidas[__I__][es_no_catalogado]" value="1" data-f="es_no_catalogado">
                <div class="req-prod-ac">
                    <input type="text" name="partidas[__I__][descripcion]" class="form-control" data-f="descripcion"
                           required placeholder="Buscar por SKU o nombre…" autocomplete="off">
                    <div class="list-group position-absolute w-100 req-prod-sugerencias d-none" data-sugerencias></div>
                </div>
                <div class="req-prod-hint">Mín. 2 caracteres · Enter selecciona la primera coincidencia</div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Unidad <span class="text-danger">*</span></label>
                <input type="text" name="partidas[__I__][unidad_medida]" class="form-control" data-f="unidad_medida"
                       required list="um-suggest" placeholder="PZA" value="PZA">
            </div>
            <div class="col-md-2">
                <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                <input type="number" step="0.0001" min="0.0001" name="partidas[__I__][cantidad]"
                       class="form-control" data-f="cantidad" required value="1">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fecha req. partida</label>
                <input type="date" name="partidas[__I__][fecha_requerida]" class="form-control" data-f="fecha_requerida">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger w-100" data-del title="Quitar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="col-md-6">
                <label class="form-label">Especificaciones</label>
                <input type="text" name="partidas[__I__][especificaciones]" class="form-control" data-f="especificaciones">
            </div>
            <div class="col-md-6">
                <label class="form-label">Observaciones / motivo</label>
                <input type="text" name="partidas[__I__][motivo]" class="form-control" data-f="motivo">
            </div>
            <div class="col-12">
                <div class="form-check mt-1" data-catalogo-wrap>
                    <input class="form-check-input" type="checkbox" value="1"
                           name="partidas[__I__][registrar_catalogo]" data-f="registrar_catalogo" id="regcat__I__">
                    <label class="form-check-label" for="regcat__I__" style="font-size:.82rem">
                        Si no existe: registrar también en el catálogo de productos al guardar
                    </label>
                </div>
                <div class="row g-2 mt-1 d-none" data-sku-nuevo-wrap>
                    <div class="col-md-4">
                        <label class="form-label">SKU nuevo</label>
                        <input type="text" name="partidas[__I__][sku_nuevo]" class="form-control form-control-sm"
                               data-f="sku_nuevo" maxlength="100" placeholder="Ej. REQ-TORNILLO-01">
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-abrir-modal-prod>
                            <i class="fa-solid fa-box-open"></i> Completar alta ahora
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

{{-- Modal alta rápida a tblproductos --}}
<div class="modal fade" id="modalNuevoProducto" tabindex="-1" aria-labelledby="modalNuevoProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="modalNuevoProductoLabel">
                    <i class="fa-solid fa-box-open me-1"></i> Nuevo producto en catálogo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="form-nuevo-producto">
                <div class="modal-body pt-0">
                    <p class="text-muted" style="font-size:.85rem">
                        Se guardará en <strong>tblproductos</strong> y quedará disponible para esta y futuras requisiciones.
                    </p>
                    <div class="alert alert-danger py-2 d-none" id="np-error"></div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="sku" id="np-sku" required minlength="3" maxlength="100">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" id="np-nombre" required minlength="2" maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unidad <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_unidad_medida" id="np-unidad" required>
                                <option value="">Selecciona…</option>
                                @foreach(($unidadesMedida ?? []) as $u)
                                    <option value="{{ $u->id }}"
                                        data-abr="{{ $u->abreviacion ?? '' }}"
                                        {{ (($u->abreviacion ?? '') === 'PZA' || strtoupper($u->nombre ?? '') === 'PIEZAS') ? 'selected' : '' }}>
                                        {{ $u->nombre }}{{ !empty($u->abreviacion) ? ' ('.$u->abreviacion.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" name="id_categoria" id="np-categoria">
                                <option value="">Por defecto</option>
                                @foreach(($categoriasProducto ?? []) as $c)
                                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Código de barras</label>
                            <input type="text" class="form-control" name="codigo_barras" id="np-barras" maxlength="100"
                                   placeholder="Opcional (usa SKU si vacío)">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="np-descripcion" rows="2"
                                      placeholder="Opcional"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-baseColor" id="np-submit">
                        <i class="fa-solid fa-check me-1"></i> Guardar en catálogo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<datalist id="um-suggest">
    @foreach(($unidades ?? ['PZA','KG','LT','M','SERV']) as $um)
        <option value="{{ $um }}"></option>
    @endforeach
</datalist>

<script>
(function () {
    var box = document.getElementById('partidas-box');
    var tpl = document.getElementById('tpl-partida').innerHTML;
    var urlProductos = box.getAttribute('data-url-productos') || '';
    var urlCrearProducto = box.getAttribute('data-url-crear-producto') || '';
    var csrfToken = box.getAttribute('data-csrf') || '';
    var idx = 0;
    var timers = new WeakMap();
    var controllers = new WeakMap();
    var filaModalTarget = null;
    var modalEl = document.getElementById('modalNuevoProducto');
    var formNuevo = document.getElementById('form-nuevo-producto');
    var npError = document.getElementById('np-error');
    var npSubmit = document.getElementById('np-submit');
    var bsModal = null;
    if (modalEl && window.bootstrap && bootstrap.Modal) {
        bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    }

    @php
        $partidasJs = old('partidas');
        if ($partidasJs === null) {
            $partidasJs = collect($detalles ?? [])->map(function ($d) {
                return [
                    'producto_id' => $d->producto_id,
                    'codigo' => $d->codigo,
                    'descripcion' => $d->descripcion,
                    'unidad_medida' => $d->unidad_medida,
                    'cantidad' => $d->cantidad,
                    'especificaciones' => $d->especificaciones,
                    'motivo' => $d->motivo,
                    'fecha_requerida' => optional($d->fecha_requerida)->format('Y-m-d'),
                    'es_no_catalogado' => $d->es_no_catalogado ? 1 : 0,
                ];
            })->values()->all();
        }
    @endphp
    var existentes = @json($partidasJs);

    function escaparHtml(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function ocultarSugerencias(row) {
        var lista = row.querySelector('[data-sugerencias]');
        if (!lista) return;
        lista.innerHTML = '';
        lista.classList.add('d-none');
        lista._items = [];
        lista._active = -1;
    }

    function syncCatalogoUi(row) {
        var pid = row.querySelector('[data-f="producto_id"]');
        var wrap = row.querySelector('[data-catalogo-wrap]');
        var skuWrap = row.querySelector('[data-sku-nuevo-wrap]');
        var chk = row.querySelector('[data-f="registrar_catalogo"]');
        var tieneProducto = !!(pid && pid.value);
        if (wrap) wrap.classList.toggle('d-none', tieneProducto);
        if (skuWrap) {
            var showSku = !tieneProducto && chk && chk.checked;
            skuWrap.classList.toggle('d-none', !showSku);
        }
        if (tieneProducto && chk) {
            chk.checked = false;
            if (skuWrap) skuWrap.classList.add('d-none');
        }
    }

    function marcarCatalogado(row, item) {
        var pid = row.querySelector('[data-f="producto_id"]');
        var cod = row.querySelector('[data-f="codigo"]');
        var nocat = row.querySelector('[data-f="es_no_catalogado"]');
        var desc = row.querySelector('[data-f="descripcion"]');
        var um = row.querySelector('[data-f="unidad_medida"]');
        if (pid) pid.value = item.id || '';
        if (cod) cod.value = item.codigo || '';
        if (nocat) nocat.value = '0';
        if (desc) desc.value = item.nombre || '';
        if (um && item.unidad) um.value = item.unidad;
        ocultarSugerencias(row);
        syncCatalogoUi(row);
    }

    function marcarLibre(row) {
        var pid = row.querySelector('[data-f="producto_id"]');
        var cod = row.querySelector('[data-f="codigo"]');
        var nocat = row.querySelector('[data-f="es_no_catalogado"]');
        if (pid && pid.value) {
            pid.value = '';
            if (cod) cod.value = '';
            if (nocat) nocat.value = '1';
        } else if (nocat) {
            nocat.value = '1';
        }
        syncCatalogoUi(row);
    }

    function abrirModalProducto(row, prefijoNombre) {
        filaModalTarget = row || box.querySelector('[data-row]') || null;
        if (npError) {
            npError.classList.add('d-none');
            npError.textContent = '';
        }
        if (formNuevo) formNuevo.reset();
        var nombrePref = prefijoNombre || (filaModalTarget && filaModalTarget.querySelector('[data-f="descripcion"]')
            ? filaModalTarget.querySelector('[data-f="descripcion"]').value : '');
        var skuPref = filaModalTarget && filaModalTarget.querySelector('[data-f="sku_nuevo"]')
            ? filaModalTarget.querySelector('[data-f="sku_nuevo"]').value : '';
        var npNombre = document.getElementById('np-nombre');
        var npSku = document.getElementById('np-sku');
        var npDesc = document.getElementById('np-descripcion');
        if (npNombre && nombrePref) npNombre.value = nombrePref;
        if (npSku && skuPref) npSku.value = skuPref;
        if (npDesc && nombrePref) npDesc.value = nombrePref;
        // re-seleccionar PZA si el reset la quitó
        var sel = document.getElementById('np-unidad');
        if (sel && !sel.value) {
            var pza = Array.prototype.find.call(sel.options, function (o) {
                return (o.getAttribute('data-abr') || '').toUpperCase() === 'PZA';
            });
            if (pza) sel.value = pza.value;
        }
        if (bsModal) bsModal.show();
        else if (modalEl) {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    }

    function mostrarSugerencias(row, items) {
        var lista = row.querySelector('[data-sugerencias]');
        if (!lista) return;
        lista._items = items || [];
        lista._active = items.length ? 0 : -1;

        if (!items.length) {
            lista.innerHTML =
                '<div class="list-group-item text-muted">Sin coincidencias</div>' +
                '<button type="button" class="list-group-item list-group-item-action text-primary" data-crear-producto>' +
                '<i class="fa-solid fa-box-open me-1"></i> Registrar producto nuevo en catálogo</button>';
            lista.classList.remove('d-none');
            return;
        }

        lista.innerHTML = items.map(function (item, i) {
            return '<button type="button" class="list-group-item list-group-item-action' + (i === 0 ? ' active' : '') + '" data-idx="' + i + '">' +
                (item.codigo ? '<span class="req-prod-sku">' + escaparHtml(item.codigo) + '</span><br>' : '') +
                escaparHtml(item.nombre || item.etiqueta || '') +
                (item.unidad ? ' <span class="req-prod-sku">(' + escaparHtml(item.unidad) + ')</span>' : '') +
                '</button>';
        }).join('') +
            '<button type="button" class="list-group-item list-group-item-action text-primary" data-crear-producto>' +
            '<i class="fa-solid fa-box-open me-1"></i> No está · crear en catálogo</button>';
        lista.classList.remove('d-none');
    }

    function moverActivo(row, delta) {
        var lista = row.querySelector('[data-sugerencias]');
        if (!lista || lista.classList.contains('d-none') || !lista._items || !lista._items.length) return;
        var n = lista._items.length;
        lista._active = (lista._active + delta + n) % n;
        lista.querySelectorAll('[data-idx]').forEach(function (btn) {
            btn.classList.toggle('active', parseInt(btn.getAttribute('data-idx'), 10) === lista._active);
        });
        var activo = lista.querySelector('[data-idx="' + lista._active + '"]');
        if (activo && activo.scrollIntoView) activo.scrollIntoView({ block: 'nearest' });
    }

    function seleccionarActivo(row) {
        var lista = row.querySelector('[data-sugerencias]');
        if (!lista || !lista._items || lista._active < 0) return false;
        var item = lista._items[lista._active];
        if (!item) return false;
        marcarCatalogado(row, item);
        return true;
    }

    function buscar(row, termino) {
        termino = (termino || '').trim();
        if (termino.length < 2 || !urlProductos) {
            ocultarSugerencias(row);
            return;
        }

        var prev = controllers.get(row);
        if (prev) prev.abort();
        var ctrl = new AbortController();
        controllers.set(row, ctrl);

        fetch(urlProductos + '?q=' + encodeURIComponent(termino), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: ctrl.signal
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                mostrarSugerencias(row, (data && data.items) ? data.items : []);
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                ocultarSugerencias(row);
            })
            .finally(function () {
                if (controllers.get(row) === ctrl) controllers.delete(row);
            });
    }

    function programarBusqueda(row, termino) {
        var t = timers.get(row);
        if (t) clearTimeout(t);
        timers.set(row, setTimeout(function () {
            buscar(row, termino);
        }, 250));
    }

    function wireRow(row) {
        var desc = row.querySelector('[data-f="descripcion"]');
        var lista = row.querySelector('[data-sugerencias]');
        var chk = row.querySelector('[data-f="registrar_catalogo"]');
        if (!desc || !lista) return;

        desc.addEventListener('input', function () {
            marcarLibre(row);
            programarBusqueda(row, desc.value);
        });

        desc.addEventListener('keydown', function (ev) {
            if (ev.key === 'ArrowDown') {
                ev.preventDefault();
                moverActivo(row, 1);
            } else if (ev.key === 'ArrowUp') {
                ev.preventDefault();
                moverActivo(row, -1);
            } else if (ev.key === 'Enter') {
                if (!lista.classList.contains('d-none') && lista._items && lista._items.length) {
                    ev.preventDefault();
                    seleccionarActivo(row);
                }
            } else if (ev.key === 'Escape') {
                ocultarSugerencias(row);
            }
        });

        desc.addEventListener('blur', function () {
            setTimeout(function () { ocultarSugerencias(row); }, 180);
        });

        lista.addEventListener('mousedown', function (ev) {
            var crear = ev.target.closest('[data-crear-producto]');
            if (crear) {
                ev.preventDefault();
                abrirModalProducto(row, desc.value);
                return;
            }
            var btn = ev.target.closest('[data-idx]');
            if (!btn) return;
            ev.preventDefault();
            var i = parseInt(btn.getAttribute('data-idx'), 10);
            if (lista._items && lista._items[i]) marcarCatalogado(row, lista._items[i]);
        });

        if (chk) {
            chk.addEventListener('change', function () { syncCatalogoUi(row); });
        }

        var btnModal = row.querySelector('[data-abrir-modal-prod]');
        if (btnModal) {
            btnModal.addEventListener('click', function () {
                abrirModalProducto(row, desc.value);
            });
        }

        syncCatalogoUi(row);
    }

    function addRow(data) {
        data = data || {};
        var html = tpl.replace(/__I__/g, String(idx++));
        box.insertAdjacentHTML('beforeend', html);
        var row = box.lastElementChild;
        Object.keys(data).forEach(function (k) {
            var el = row.querySelector('[data-f="'+k+'"]');
            if (el && data[k] != null) el.value = data[k];
        });
        wireRow(row);
        var desc = row.querySelector('[data-f="descripcion"]');
        if (desc && !data.descripcion) desc.focus();
    }

    document.getElementById('btn-add-partida').addEventListener('click', function () { addRow({}); });

    var btnNuevoProd = document.getElementById('btn-nuevo-producto');
    if (btnNuevoProd) {
        btnNuevoProd.addEventListener('click', function () {
            var focusRow = box.querySelector('[data-row]:last-child');
            abrirModalProducto(focusRow, focusRow ? (focusRow.querySelector('[data-f="descripcion"]') || {}).value : '');
        });
    }

    box.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-del]');
        if (!btn) return;
        var rows = box.querySelectorAll('[data-row]');
        if (rows.length <= 1) {
            var row = btn.closest('[data-row]');
            row.querySelectorAll('input').forEach(function (i) {
                if (i.type === 'checkbox') {
                    i.checked = false;
                    return;
                }
                if (i.type === 'hidden') {
                    if (i.getAttribute('data-f') === 'es_no_catalogado') i.value = '1';
                    else if (i.getAttribute('data-f') === 'producto_id' || i.getAttribute('data-f') === 'codigo') i.value = '';
                    return;
                }
                i.value = i.name.indexOf('cantidad') >= 0 ? '1' : (i.name.indexOf('unidad') >= 0 ? 'PZA' : '');
            });
            ocultarSugerencias(row);
            syncCatalogoUi(row);
            return;
        }
        btn.closest('[data-row]').remove();
    });

    document.addEventListener('click', function (ev) {
        if (ev.target.closest('.req-prod-ac')) return;
        box.querySelectorAll('[data-row]').forEach(ocultarSugerencias);
    });

    if (formNuevo) {
        formNuevo.addEventListener('submit', function (ev) {
            ev.preventDefault();
            if (!urlCrearProducto) return;
            if (npError) {
                npError.classList.add('d-none');
                npError.textContent = '';
            }
            var fd = new FormData(formNuevo);
            if (npSubmit) {
                npSubmit.disabled = true;
                npSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando…';
            }
            fetch(urlCrearProducto, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: fd
            })
                .then(function (r) {
                    return r.json().then(function (data) {
                        return { status: r.status, data: data };
                    });
                })
                .then(function (res) {
                    if (!res.data || !res.data.ok) {
                        var msg = (res.data && res.data.message) ? res.data.message : 'No se pudo crear el producto.';
                        if (res.data && res.data.errors) {
                            msg = Object.values(res.data.errors).flat().join(' ');
                        }
                        throw new Error(msg);
                    }
                    var item = res.data.item;
                    var target = filaModalTarget;
                    if (!target) {
                        addRow({});
                        target = box.querySelector('[data-row]:last-child');
                    }
                    if (target) marcarCatalogado(target, item);
                    if (bsModal) bsModal.hide();
                    else if (modalEl) {
                        modalEl.classList.remove('show');
                        modalEl.style.display = 'none';
                    }
                })
                .catch(function (err) {
                    if (npError) {
                        npError.textContent = err.message || 'Error al registrar el producto.';
                        npError.classList.remove('d-none');
                    }
                })
                .finally(function () {
                    if (npSubmit) {
                        npSubmit.disabled = false;
                        npSubmit.innerHTML = '<i class="fa-solid fa-check me-1"></i> Guardar en catálogo';
                    }
                });
        });
    }

    if (existentes && existentes.length) {
        existentes.forEach(addRow);
    } else {
        addRow({});
    }
})();
</script>
@endsection
