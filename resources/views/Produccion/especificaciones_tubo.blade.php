@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .espec-tubo-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .espec-tubo-filters form {
        gap: 0.35rem 0.65rem;
    }
    .espec-tubo-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
        max-width: none;
    }
    .espec-tubo-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .espec-tubo-filters .form-select-sm,
    .espec-tubo-filters .form-control-sm {
        min-height: 28px;
        height: 28px;
        font-size: 0.78rem;
        padding: 0.15rem 0.4rem;
        width: auto;
        min-width: 120px;
        max-width: 200px;
        background: #ffffff !important;
        border: 1px solid var(--table-border, #e5e7eb) !important;
        border-radius: 10px !important;
        color: var(--table-text, #111827) !important;
    }
    .espec-tubo-filters .form-control-sm.filter-search {
        min-width: 180px;
        max-width: 260px;
    }
    .espec-tubo-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    .espec-tubo-table-card .td-actions {
        white-space: nowrap !important;
    }
    .espec-tubo-table-card .td-actions .btn {
        display: inline-flex !important;
        margin: 0 2px !important;
    }
    @media (max-width: 768px) {
        .espec-tubo-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .espec-tubo-filters .form-select-sm,
        .espec-tubo-filters .form-control-sm {
            flex: 1;
            max-width: none;
        }
    }
</style>

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-ruler-combined"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.ordenes') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Especificaciones de tubo</h2>
                        <p class="text-muted mb-0">
                            Catálogo técnico por producto — {{ number_format($totalEspecificaciones) }} especificaciones registradas
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevaEspecificacion">
                        <i class="fa-solid fa-plus"></i> Nueva especificación
                    </button>
                    <button class="btn btn-baseColor-light" type="button" data-bs-toggle="modal" data-bs-target="#modalImportacionExcel">
                        <i class="fa-solid fa-file-excel"></i> Importar Excel
                    </button>
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
        <div class="table-responsive espec-tubo-table-card">
            <div class="filters espec-tubo-filters">
                <form method="GET" action="{{ route('produccion.especificaciones.tubo') }}" class="d-flex flex-wrap align-items-center" id="filtroEspecificacionesTubo">
                    <div class="filter-field">
                        <label class="form-label" for="filtro_buscar">Buscar</label>
                        <input type="text" class="form-control form-control-sm filter-search" id="filtro_buscar" name="buscar"
                            value="{{ $buscar }}" placeholder="SKU, nombre, diámetro…" aria-label="Buscar">
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_producto">Producto</label>
                        <select class="form-select form-select-sm" id="filtro_producto" name="producto_id" aria-label="Filtrar por producto">
                            <option value="">Todos</option>
                            @foreach ($productosConSpecs as $item)
                                <option value="{{ $item->producto_id }}" {{ (int) $productoId === (int) $item->producto_id ? 'selected' : '' }}>
                                    {{ $item->producto->sku ?? '' }} — {{ \Illuminate\Support\Str::limit($item->producto->nombre ?? 'Producto', 40) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_diametro">Diámetro</label>
                        <select class="form-select form-select-sm" id="filtro_diametro" name="diametro_nominal" aria-label="Filtrar por diámetro">
                            <option value="">Todos</option>
                            @foreach ($diametros as $diametro)
                                <option value="{{ $diametro }}" {{ request('diametro_nominal') === $diametro ? 'selected' : '' }}>
                                    {{ $diametro }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_psi">PSI</label>
                        <select class="form-select form-select-sm" id="filtro_psi" name="psi" aria-label="Filtrar por PSI">
                            <option value="">Todos</option>
                            @foreach ($gradosPsi as $psi)
                                <option value="{{ $psi }}" {{ (string) request('psi') === (string) $psi ? 'selected' : '' }}>
                                    {{ number_format($psi, 0) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_estatus">Estatus</label>
                        <select class="form-select form-select-sm" id="filtro_estatus" name="estatus" aria-label="Filtrar por estatus">
                            <option value="">Activos</option>
                            <option value="TODOS" {{ request('estatus') === 'TODOS' ? 'selected' : '' }}>Todos</option>
                            <option value="INACTIVO" {{ request('estatus') === 'INACTIVO' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    @if ($buscar || $productoId || request('diametro_nominal') || request('psi') || request('estatus'))
                        <a href="{{ route('produccion.especificaciones.tubo') }}" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                            <i class="fa-solid fa-xmark"></i> Limpiar
                        </a>
                    @endif
                </form>
            </div>

            <table class="table table-striped table-hover display modern-table w-100" id="table">
                <thead>
                    <tr>
                        <th class="text-center">Acciones</th>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th>Estatus</th>
                        <th>Material</th>
                        <th>Diám. nominal</th>
                        <th>Diám. exterior</th>
                        <th>PSI</th>
                        <th>RD</th>
                        <th>Espesor</th>
                        <th>Peso kg/m</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($especificaciones as $spec)
                        <tr>
                            <td class="td-actions text-center">
                                <button class="btn btn-primary m-0 btn-editar-espec" type="button" title="Editar"
                                    data-id="{{ $spec->id }}"
                                    data-producto-id="{{ $spec->producto_id }}"
                                    data-material="{{ e($spec->material ?? 'PE-100') }}"
                                    data-diametro-nominal="{{ e($spec->diametro_nominal) }}"
                                    data-diametro-exterior="{{ $spec->diametro_exterior_pulg }}"
                                    data-psi="{{ $spec->psi }}"
                                    data-rd="{{ $spec->rd }}"
                                    data-estatus="{{ $spec->estatus }}"
                                    data-espesor="{{ $spec->espesor_pulg }}"
                                    data-peso="{{ $spec->peso_kg_m }}">
                                    <i class="fa-solid fa-pen fs-8"></i>
                                </button>
                            </td>
                            <td class="text-nowrap">{{ $spec->producto?->sku ?? '—' }}</td>
                            <td class="text-truncate" style="max-width: 240px;" title="{{ $spec->producto?->nombre }}">
                                {{ \Illuminate\Support\Str::limit($spec->producto?->nombre ?? '—', 55) }}
                            </td>
                            <td>
                                @if ($spec->estatus === 'ACTIVO')
                                    <span class="badge badge-success-dark fs-9">Activo</span>
                                @else
                                    <span class="badge badge-danger-dark fs-9">Inactivo</span>
                                @endif
                            </td>
                            <td>{{ $spec->material ?? '—' }}</td>
                            <td>{{ $spec->diametro_nominal }}</td>
                            <td>{{ $spec->diametro_exterior_pulg !== null ? number_format($spec->diametro_exterior_pulg, 4) : '—' }}</td>
                            <td>{{ number_format($spec->psi, 0) }}</td>
                            <td>{{ $spec->rd !== null ? number_format($spec->rd, 1) : '—' }}</td>
                            <td>{{ $spec->espesor_pulg !== null ? number_format($spec->espesor_pulg, 4) : '—' }}</td>
                            <td>{{ $spec->peso_kg_m !== null ? number_format($spec->peso_kg_m, 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-muted text-center py-4">
                                Sin especificaciones. Use <strong>Nueva especificación</strong> o importe desde Excel.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal importación Excel --}}
<div class="modal fade" id="modalImportacionExcel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-marino fw-bold">
                    <i class="fa-solid fa-file-excel text-success me-2"></i>Importación masiva desde Excel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('produccion.especificaciones.tubo.importar') }}" method="POST" enctype="multipart/form-data" class="modern-form mb-3">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Archivo Excel</label>
                            <input type="file" class="form-control" name="archivo" accept=".xlsx,.xls" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ID producto (opcional)</label>
                            <input type="number" class="form-control" name="producto_id" placeholder="Auto">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Material</label>
                            <input type="text" class="form-control" name="material" value="HDPE PE4710">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="submit" class="btn btn-baseColor">
                            <i class="fa-solid fa-upload"></i> Importar
                        </button>
                    </div>
                </form>
                <hr>
                <form action="{{ route('produccion.especificaciones.tubo.vincular') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-baseColor-light btn-sm">
                        <i class="fa-solid fa-link"></i> Generar productos por diámetro
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal nueva especificación --}}
<div class="modal fade" id="modalNuevaEspecificacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('produccion.especificaciones.tubo.store') }}" method="POST" class="modern-form">
                @csrf
                <input type="hidden" name="filtro_producto_id" value="{{ $productoId ?: '' }}">
                <input type="hidden" name="filtro_diametro_nominal" value="{{ request('diametro_nominal') }}">
                <input type="hidden" name="filtro_psi" value="{{ request('psi') }}">
                <input type="hidden" name="filtro_buscar" value="{{ $buscar }}">
                <input type="hidden" name="filtro_estatus" value="{{ request('estatus') }}">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-marino fw-bold">Nueva especificación de tubo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    @include('Produccion.partials.form_especificacion_tubo', [
                        'prefijo' => 'nueva',
                        'productosTubo' => $productosTubo,
                        'materialDefault' => $material ?? 'PE-100',
                    ])
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-baseColor">
                        <i class="fa-solid fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal editar (único, se llena al abrir) --}}
<div class="modal fade" id="modalEditarEspecificacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formEditarEspecificacion" action="#" method="POST" class="modern-form">
                @csrf
                <input type="hidden" name="filtro_producto_id" value="{{ $productoId ?: '' }}">
                <input type="hidden" name="filtro_diametro_nominal" value="{{ request('diametro_nominal') }}">
                <input type="hidden" name="filtro_psi" value="{{ request('psi') }}">
                <input type="hidden" name="filtro_buscar" value="{{ $buscar }}">
                <input type="hidden" name="filtro_estatus" value="{{ request('estatus') }}">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-marino fw-bold" id="tituloEditarEspecificacion">Editar especificación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    @include('Produccion.partials.form_especificacion_tubo', [
                        'prefijo' => 'edit',
                        'productosTubo' => $productosTubo,
                        'materialDefault' => $material ?? 'PE-100',
                    ])
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-baseColor">
                        <i class="fa-solid fa-save"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var updateUrlTemplate = @json(url('/produccion/especificaciones-tubo/__ID__'));

    function setFieldValue(form, name, value) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) {
            return;
        }
        field.value = value == null ? '' : value;
    }

    function openEditModal(btn) {
        var form = document.getElementById('formEditarEspecificacion');
        var title = document.getElementById('tituloEditarEspecificacion');
        var modalEl = document.getElementById('modalEditarEspecificacion');
        if (!form || !modalEl || !btn) {
            return;
        }

        var id = btn.getAttribute('data-id');
        form.action = updateUrlTemplate.replace('__ID__', id);
        if (title) {
            title.textContent = 'Editar especificación #' + id;
        }

        var productoId = btn.getAttribute('data-producto-id');
        var productoSelect = form.querySelector('select[name="producto_id"]');
        if (productoSelect) {
            productoSelect.value = productoId || '';
            productoSelect.disabled = true;
        }

        var hiddenProducto = form.querySelector('input[type="hidden"][name="producto_id"]');
        if (!hiddenProducto) {
            hiddenProducto = document.createElement('input');
            hiddenProducto.type = 'hidden';
            hiddenProducto.name = 'producto_id';
            form.appendChild(hiddenProducto);
        }
        hiddenProducto.value = productoId || '';

        setFieldValue(form, 'material', btn.getAttribute('data-material'));
        setFieldValue(form, 'diametro_nominal', btn.getAttribute('data-diametro-nominal'));
        setFieldValue(form, 'diametro_exterior_pulg', btn.getAttribute('data-diametro-exterior'));
        setFieldValue(form, 'psi', btn.getAttribute('data-psi'));
        setFieldValue(form, 'rd', btn.getAttribute('data-rd'));
        setFieldValue(form, 'estatus', btn.getAttribute('data-estatus'));
        setFieldValue(form, 'espesor_pulg', btn.getAttribute('data-espesor'));
        setFieldValue(form, 'peso_kg_m', btn.getAttribute('data-peso'));

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    $(document).on('click', '#table .btn-editar-espec', function () {
        openEditModal(this);
    });

    @if ($errors->any())
        var modalNueva = document.getElementById('modalNuevaEspecificacion');
        if (modalNueva) {
            bootstrap.Modal.getOrCreateInstance(modalNueva).show();
        }
    @endif
});
</script>
@endsection
