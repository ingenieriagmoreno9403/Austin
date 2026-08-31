@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    /* Select2 dentro del modal: no recortar dropdowns de producto / MP */
    #form-nueva-receta,
    #form-nueva-receta .modal-body,
    .tabla-componentes-wrap,
    #tabla-componentes,
    #tabla-componentes td {
        overflow: visible !important;
    }

    .recetas-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .recetas-filters form {
        gap: 0.35rem 0.65rem;
    }
    .recetas-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
        max-width: none;
    }
    .recetas-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .recetas-filters .form-select-sm,
    .recetas-filters .form-control-sm {
        min-height: 28px;
        height: 28px;
        font-size: 0.78rem;
        padding: 0.15rem 0.4rem;
        width: auto;
        min-width: 120px;
        max-width: 280px;
        background: #ffffff !important;
        border: 1px solid var(--table-border, #e5e7eb) !important;
        border-radius: 10px !important;
        color: var(--table-text, #111827) !important;
    }
    .recetas-filters .form-select-sm.filter-producto {
        min-width: 220px;
        max-width: 320px;
    }
    .recetas-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    .recetas-table-card .td-actions {
        white-space: nowrap !important;
    }
    .recetas-table-card .td-actions .btn {
        display: inline-flex !important;
        margin: 0 2px !important;
    }
    /* Select2 dentro del modal: que se pueda abrir, buscar y elegir */
    #modalNuevaReceta .select2-container {
        z-index: 1;
        width: 100% !important;
    }
    #modalNuevaReceta .select2-container--open {
        z-index: 2060;
    }
    #modalNuevaReceta .select2-dropdown {
        z-index: 2065 !important;
    }
    #modalNuevaReceta.modal {
        overflow: visible !important;
    }
    #modalNuevaReceta .modal-dialog,
    #modalNuevaReceta .modal-content {
        overflow: visible !important;
    }
    @media (max-width: 768px) {
        .recetas-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .recetas-filters .form-select-sm,
        .recetas-filters .form-control-sm {
            flex: 1;
            max-width: none;
        }
    }
</style>

@php
    $filtroProducto = request('producto_id');
    $filtroEstatus = request('estatus');
    $tieneFiltros = filled($filtroProducto) || filled($filtroEstatus);
    $totalRecetas = $recetas->count();
@endphp

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-flask"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Recetas de producción</h2>
                        <p class="text-muted mb-0">
                            Materia prima y cantidades — {{ number_format($totalRecetas) }} recetas registradas
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevaReceta">
                        <i class="fa-solid fa-plus"></i> Nueva receta
                    </button>
                    <a class="btn btn-baseColor-light" href="{{ route('produccion.ordenes') }}">
                        <i class="fa-solid fa-industry"></i> Producción
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row mt-3">
        <div class="table-responsive recetas-table-card">
            <div class="filters recetas-filters">
                <form method="GET" action="{{ route('produccion.recetas') }}" class="d-flex flex-wrap align-items-center" id="filtroRecetas">
                    <div class="filter-field">
                        <label class="form-label" for="filtro_producto">Producto</label>
                        <select class="form-select form-select-sm filter-producto" id="filtro_producto" name="producto_id" aria-label="Filtrar por producto">
                            <option value="">Todos</option>
                            @foreach (($productosTerminados ?? $productosTubo) as $producto)
                                @php $tipo = $producto->tipo_etiqueta ?? strtoupper((string) ($producto->tipo_proceso ?: 'TUBO')); @endphp
                                <option value="{{ $producto->id }}" {{ (int) $filtroProducto === (int) $producto->id ? 'selected' : '' }}>
                                    [{{ $tipo }}] {{ $producto->sku }} — {{ \Illuminate\Support\Str::limit($producto->nombre, 40) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_estatus">Estatus</label>
                        <select class="form-select form-select-sm" id="filtro_estatus" name="estatus" aria-label="Filtrar por estatus">
                            <option value="">Todos</option>
                            <option value="ACTIVA" {{ $filtroEstatus === 'ACTIVA' ? 'selected' : '' }}>Activa</option>
                            <option value="INACTIVA" {{ $filtroEstatus === 'INACTIVA' ? 'selected' : '' }}>Inactiva</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    @if ($tieneFiltros)
                        <a href="{{ route('produccion.recetas') }}" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                            <i class="fa-solid fa-xmark"></i> Limpiar
                        </a>
                    @endif
                </form>
            </div>

            <table class="table table-striped table-hover display modern-table w-100" id="table">
                <thead>
                    <tr>
                        <th class="text-center">Acciones</th>
                        <th>#</th>
                        <th>Producto</th>
                        <th>SKU</th>
                        <th>Versión</th>
                        <th>Estatus</th>
                        <th>Componentes</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recetas as $receta)
                        <tr>
                            <td class="td-actions text-center">
                                <a href="{{ route('produccion.recetas.detalle', $receta->id) }}"
                                   class="btn btn-primary m-0" title="Ver / editar">
                                    <i class="fa-solid fa-pen fs-8"></i>
                                </a>
                            </td>
                            <td>{{ $receta->id }}</td>
                            <td class="text-truncate" style="max-width: 260px;" title="{{ $receta->producto?->nombre }}">
                                {{ \Illuminate\Support\Str::limit($receta->producto?->nombre ?? '—', 55) }}
                            </td>
                            <td class="text-nowrap">{{ $receta->producto?->sku ?? '—' }}</td>
                            <td>{{ $receta->version ?? '—' }}</td>
                            <td>
                                @if ($receta->estatus === 'ACTIVA')
                                    <span class="badge badge-success-dark fs-9">Activa</span>
                                @else
                                    <span class="badge badge-danger-dark fs-9">Inactiva</span>
                                @endif
                            </td>
                            <td>{{ $receta->detalles_count }}</td>
                            <td data-order="{{ $receta->created_at?->format('Y-m-d') }}">
                                {{ $receta->created_at?->format('d/m/Y') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal nueva receta --}}
<div class="modal fade" id="modalNuevaReceta" tabindex="-1" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="{{ route('produccion.recetas.store') }}" method="POST" class="modern-form" id="form-nueva-receta">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title text-marino fw-bold">
                        <i class="fa-solid fa-circle-plus me-2"></i>Nueva receta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted fs-8 mb-3">
                        Elija el producto terminado (tubo, flange o conexión) y agregue los materiales del catálogo con la cantidad por metro o por pieza.
                    </p>
                    <div class="row">
                        <div class="col-md-5 col-12 mt-2">
                            <label class="form-label" for="receta_producto_id">Producto terminado <span class="text-danger">*</span></label>
                            <select class="form-select select2-producto-receta" name="producto_id" id="receta_producto_id" data-placeholder="— Tubo / flange / conexión —">
                                <option value=""></option>
                                @foreach (($productosTerminados ?? $productosTubo) as $producto)
                                    @php $tipo = $producto->tipo_etiqueta ?? strtoupper((string) ($producto->tipo_proceso ?: 'TUBO')); @endphp
                                    <option value="{{ $producto->id }}" {{ (int) old('producto_id', request('producto_id')) === (int) $producto->id ? 'selected' : '' }}>
                                        [{{ $tipo }}] {{ $producto->sku }} — {{ $producto->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 col-12 mt-2">
                            <label class="form-label">Versión</label>
                            <input type="text" class="form-control" name="version" value="{{ old('version', '1.0') }}" maxlength="20">
                        </div>
                        <div class="col-md-2 col-12 mt-2">
                            <label class="form-label">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="estatus" required>
                                <option value="ACTIVA" {{ old('estatus', 'ACTIVA') === 'ACTIVA' ? 'selected' : '' }}>Activa</option>
                                <option value="INACTIVA" {{ old('estatus') === 'INACTIVA' ? 'selected' : '' }}>Inactiva</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Observaciones</label>
                            <input type="text" class="form-control text" name="observaciones" value="{{ old('observaciones') }}">
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="text-secondary mb-0">
                            <i class="fa-solid fa-cubes me-1"></i>Componentes de la receta
                        </h6>
                        <button type="button" class="btn btn-sm btn-baseColor-light" id="btn-agregar-componente">
                            <i class="fa-solid fa-plus"></i> Agregar producto
                        </button>
                    </div>

                    <div class="tabla-componentes-wrap">
                        <table class="table table-bordered align-middle mb-2" id="tabla-componentes">
                            <thead>
                                <tr>
                                    <th style="min-width: 280px;">Materia prima (catálogo) <span class="text-danger">*</span></th>
                                    <th style="width: 130px;">Cantidad <span class="text-danger">*</span></th>
                                    <th style="width: 180px;">Unidad</th>
                                    <th style="width: 110px;">% mezcla</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="componentes-body">
                                @php
                                    $oldComponentes = old('componentes', [['producto_mp_id' => '', 'cantidad' => '', 'unidad_id' => '', 'porcentaje' => '']]);
                                @endphp
                                @foreach ($oldComponentes as $i => $comp)
                                    <tr class="fila-componente">
                                        <td>
                                            <select class="form-select form-select-sm select2-mp"
                                                name="componentes[{{ $i }}][producto_mp_id]"
                                                data-placeholder="Buscar por SKU o nombre...">
                                                <option value=""></option>
                                                @foreach ($productosMp as $mp)
                                                    <option value="{{ $mp->id }}"
                                                        data-unidad="{{ $mp->id_unidad_medida }}"
                                                        {{ (string) ($comp['producto_mp_id'] ?? '') === (string) $mp->id ? 'selected' : '' }}>
                                                        {{ trim(($mp->sku ?: '—') . ' — ' . $mp->nombre) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="0.0001" min="0.0001" class="form-control form-control-sm"
                                                name="componentes[{{ $i }}][cantidad]" value="{{ $comp['cantidad'] ?? '' }}" required placeholder="0.00">
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm select-unidad" name="componentes[{{ $i }}][unidad_id]">
                                                <option value="">— Default —</option>
                                                @foreach ($unidades as $unidad)
                                                    <option value="{{ $unidad->id }}"
                                                        {{ (string) ($comp['unidad_id'] ?? '') === (string) $unidad->id ? 'selected' : '' }}>
                                                        {{ $unidad->abreviacion ?? $unidad->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="0.0001" min="0" max="100" class="form-control form-control-sm"
                                                name="componentes[{{ $i }}][porcentaje]" value="{{ $comp['porcentaje'] ?? '' }}" placeholder="%">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-quitar-fila" title="Quitar">
                                                <i class="fa-solid fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted fs-8 mb-0">Ejemplo: resina PE4710 0.85 kg/m, negro de humo 0.03 kg/m, aditivo UV 0.02 kg/m.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-baseColor">
                        <i class="fa-solid fa-save"></i> Guardar receta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="tpl-fila-componente">
    <tr class="fila-componente">
        <td>
            <select class="form-select form-select-sm select2-mp"
                name="componentes[__INDEX__][producto_mp_id]"
                data-placeholder="Buscar por SKU o nombre...">
                <option value=""></option>
                @foreach ($productosMp as $mp)
                    <option value="{{ $mp->id }}" data-unidad="{{ $mp->id_unidad_medida }}">
                        {{ trim(($mp->sku ?: '—') . ' — ' . $mp->nombre) }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="0.0001" min="0.0001" class="form-control form-control-sm"
                name="componentes[__INDEX__][cantidad]" required placeholder="0.00">
        </td>
        <td>
            <select class="form-select form-select-sm select-unidad" name="componentes[__INDEX__][unidad_id]">
                <option value="">— Default —</option>
                @foreach ($unidades as $unidad)
                    <option value="{{ $unidad->id }}">{{ $unidad->abreviacion ?? $unidad->nombre }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="0.0001" min="0" max="100" class="form-control form-control-sm"
                name="componentes[__INDEX__][porcentaje]" placeholder="%">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger btn-quitar-fila" title="Quitar">
                <i class="fa-solid fa-times"></i>
            </button>
        </td>
    </tr>
</template>

<script src="{{ asset('js/table.js') }}"></script>

@endsection

@section('js')
<script>
$(function () {
    var $modal = $('#modalNuevaReceta');
    var $select = $('#receta_producto_id');
    var $tbody = $('#componentes-body');
    var tpl = document.getElementById('tpl-fila-componente');
    var selectInited = false;

    function select2Opts(placeholder) {
        return {
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: {
                id: '',
                text: placeholder || 'Buscar...'
            },
            dropdownParent: $modal,
            language: {
                noResults: function () { return 'Sin resultados'; },
                searching: function () { return 'Buscando...'; }
            }
        };
    }

    function initSelectProducto() {
        if (!$select.length || typeof $.fn.select2 === 'undefined') {
            return;
        }
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        $select.select2(select2Opts('— Tubo / flange / conexión —'));
        selectInited = true;
    }

    function aplicarUnidadDesdeMp($selectMp) {
        var unidad = $selectMp.find('option:selected').data('unidad');
        var $unidad = $selectMp.closest('tr').find('.select-unidad');
        if ($unidad.length && unidad) {
            $unidad.val(String(unidad));
        }
    }

    function initSelectMp($el) {
        if (!$el || !$el.length || typeof $.fn.select2 === 'undefined') {
            return;
        }
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2(select2Opts($el.data('placeholder') || 'Buscar por SKU o nombre...'));
        $el.off('change.recetaMp').on('change.recetaMp', function () {
            aplicarUnidadDesdeMp($(this));
        });
    }

    function initAllSelectMp() {
        $tbody.find('select.select2-mp').each(function () {
            initSelectMp($(this));
        });
    }

    function destroySelectMp($el) {
        if ($el && $el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
    }

    function reindexFilas() {
        $tbody.find('.fila-componente').each(function (index) {
            $(this).find('[name^="componentes["]').each(function () {
                this.name = this.name.replace(/componentes\[\d+\]/, 'componentes[' + index + ']');
            });
        });
    }

    function enlazarQuitar($fila) {
        $fila.find('.btn-quitar-fila').off('click.recetaMp').on('click.recetaMp', function () {
            if ($tbody.find('.fila-componente').length <= 1) {
                return;
            }
            destroySelectMp($fila.find('select.select2-mp'));
            $fila.remove();
            reindexFilas();
        });
    }

    $tbody.find('.fila-componente').each(function () {
        enlazarQuitar($(this));
    });

    $('#btn-agregar-componente').on('click', function () {
        if (!tpl) return;
        var index = $tbody.find('.fila-componente').length;
        var html = tpl.innerHTML.replace(/__INDEX__/g, index);
        var temp = document.createElement('tbody');
        temp.innerHTML = html.trim();
        var fila = temp.firstElementChild;
        if (!fila) return;
        var $fila = $(fila);
        $tbody.append($fila);
        if ($modal.hasClass('show')) {
            initSelectMp($fila.find('select.select2-mp'));
        }
        enlazarQuitar($fila);
    });

    // Evita que Bootstrap robe el focus del buscador de Select2
    document.addEventListener('focusin', function (e) {
        if ($(e.target).closest('.select2-container, .select2-dropdown').length) {
            e.stopImmediatePropagation();
        }
    }, true);

    $modal.on('shown.bs.modal', function () {
        if (!selectInited) {
            initSelectProducto();
        } else {
            $select.trigger('change.select2');
        }
        initAllSelectMp();
        setTimeout(function () {
            $select.select2('focus');
        }, 50);
    });

    $modal.on('hidden.bs.modal', function () {
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('close');
        }
        $tbody.find('select.select2-mp').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('close');
            }
        });
    });

    // Validación: el select nativo queda oculto y "required" rompe el focus del modal
    $('#form-nueva-receta').on('submit', function (e) {
        if (!$select.val()) {
            e.preventDefault();
            e.stopImmediatePropagation();
            Swal.fire({
                icon: 'warning',
                title: 'Producto requerido',
                text: 'Seleccione el producto terminado.',
                confirmButtonColor: '#102d49'
            });
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('open');
            }
            return false;
        }

        var tuboId = String($select.val() || '');
        var vistos = {};
        var filas = $tbody.find('.fila-componente');
        for (var i = 0; i < filas.length; i++) {
            var $fila = $(filas[i]);
            var $mp = $fila.find('select.select2-mp');
            var val = String($mp.val() || '');
            if (!val) {
                e.preventDefault();
                e.stopImmediatePropagation();
                Swal.fire({
                    icon: 'warning',
                    title: 'Materia prima requerida',
                    text: 'Seleccione el producto de cada componente.',
                    confirmButtonColor: '#102d49'
                });
                if ($mp.hasClass('select2-hidden-accessible')) {
                    $mp.select2('open');
                }
                return false;
            }
            if (tuboId && val === tuboId) {
                e.preventDefault();
                e.stopImmediatePropagation();
                Swal.fire({
                    icon: 'warning',
                    title: 'Producto inválido',
                    text: 'El producto terminado no puede ser materia prima de su propia receta.',
                    confirmButtonColor: '#102d49'
                });
                return false;
            }
            if (vistos[val]) {
                e.preventDefault();
                e.stopImmediatePropagation();
                Swal.fire({
                    icon: 'warning',
                    title: 'Producto duplicado',
                    text: 'No repita el mismo producto en la receta.',
                    confirmButtonColor: '#102d49'
                });
                return false;
            }
            vistos[val] = true;
        }
    });

    @if ($errors->any() || request()->boolean('nueva'))
        var productoId = @json((string) request('producto_id'));
        initSelectProducto();
        initAllSelectMp();
        if (productoId) {
            $select.val(productoId).trigger('change');
        }
        bootstrap.Modal.getOrCreateInstance($modal[0]).show();
    @endif
});
</script>
@endsection
