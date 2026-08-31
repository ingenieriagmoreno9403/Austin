@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .espec-conex-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .espec-conex-filters form {
        gap: 0.35rem 0.65rem;
    }
    .espec-conex-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
        max-width: none;
    }
    .espec-conex-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .espec-conex-filters .form-select-sm,
    .espec-conex-filters .form-control-sm {
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
    .espec-conex-filters .form-control-sm.filter-search {
        min-width: 180px;
        max-width: 260px;
    }
    .espec-conex-filters .form-select-sm.filter-producto {
        min-width: 220px;
        max-width: 320px;
    }
    .espec-conex-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    @media (max-width: 768px) {
        .espec-conex-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .espec-conex-filters .form-select-sm,
        .espec-conex-filters .form-control-sm {
            flex: 1;
            max-width: none;
        }
    }
</style>

@php
    $tieneFiltros = filled($buscar) || filled($tipo) || $productoId || filled(request('estatus'));
@endphp

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-diagram-project"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.ordenes') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Especificaciones conexiones</h2>
                        <p class="text-muted mb-0">
                            Catálogo HDPE (codo, tee, brida, cople, tapón, reducción) —
                            {{ number_format($totalEspecificaciones) }} registradas
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalImportarConexiones">
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
            <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row mt-3">
        <div class="table-responsive espec-conex-table-card">
            <div class="filters espec-conex-filters">
                <form method="GET" action="{{ route('produccion.especificaciones.conexiones') }}" class="d-flex flex-wrap align-items-center" id="filtroEspecificacionesConexiones">
                    <div class="filter-field">
                        <label class="form-label" for="filtro_buscar">Buscar</label>
                        <input type="text" class="form-control form-control-sm filter-search" id="filtro_buscar" name="buscar"
                            value="{{ $buscar }}" placeholder="Código, tipo, Ø…" aria-label="Buscar">
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_tipo">Tipo</label>
                        <select class="form-select form-select-sm" id="filtro_tipo" name="tipo" aria-label="Filtrar por tipo">
                            <option value="">Todos</option>
                            @foreach ($tipos as $t)
                                <option value="{{ $t }}" @selected($tipo === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_producto">Producto</label>
                        <select class="form-select form-select-sm filter-producto" id="filtro_producto" name="producto_id" aria-label="Filtrar por producto">
                            <option value="">Todos</option>
                            @foreach ($productos as $p)
                                <option value="{{ $p->id }}" @selected((int) $productoId === (int) $p->id)>
                                    {{ $p->sku }} — {{ \Illuminate\Support\Str::limit($p->nombre, 40) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_estatus">Estatus</label>
                        <select class="form-select form-select-sm" id="filtro_estatus" name="estatus" aria-label="Filtrar por estatus">
                            <option value="">Activos</option>
                            <option value="INACTIVO" @selected(request('estatus') === 'INACTIVO')>Inactivos</option>
                            <option value="TODOS" @selected(request('estatus') === 'TODOS')>Todos</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    @if ($tieneFiltros)
                        <a href="{{ route('produccion.especificaciones.conexiones') }}" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                            <i class="fa-solid fa-xmark"></i> Limpiar
                        </a>
                    @endif
                </form>
            </div>

            <table class="table table-striped table-hover display modern-table w-100" id="table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Estatus</th>
                        <th>Tipo</th>
                        <th>Ø pulg</th>
                        <th>Ø mm</th>
                        <th>RD</th>
                        <th class="text-end">kg/m tubo</th>
                        <th class="text-end">kg/pieza</th>
                        <th>Material</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($especificaciones as $spec)
                        <tr>
                            <td class="fw-semibold text-nowrap">{{ $spec->codigo ?: '—' }}</td>
                            <td class="text-truncate" style="max-width: 260px;" title="{{ $spec->producto?->sku }} — {{ $spec->producto?->nombre }}">
                                @if ($spec->producto)
                                    <div class="text-nowrap">{{ $spec->producto->sku }}</div>
                                    <div class="text-muted">{{ \Illuminate\Support\Str::limit($spec->producto->nombre, 45) }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($spec->estatus === 'ACTIVO')
                                    <span class="badge badge-success-dark fs-9">Activo</span>
                                @else
                                    <span class="badge badge-danger-dark fs-9">Inactivo</span>
                                @endif
                            </td>
                            <td>{{ $spec->tipo }}</td>
                            <td>{{ $spec->diametro_nominal ?: '—' }}</td>
                            <td>{{ $spec->diametro_mm !== null ? number_format((float) $spec->diametro_mm, 1) : '—' }}</td>
                            <td>{{ $spec->rd !== null ? number_format((float) $spec->rd, 1) : '—' }}</td>
                            <td class="text-end">{{ $spec->peso_tubo_kg_m !== null ? number_format((float) $spec->peso_tubo_kg_m, 3) : '—' }}</td>
                            <td class="text-end">{{ $spec->peso_kg_pieza !== null ? number_format((float) $spec->peso_kg_pieza, 3) : '—' }}</td>
                            <td>{{ $spec->material ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Sin especificaciones. Importe el Excel <strong>Catalogo_Conexiones_HDPE.xlsx</strong>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImportarConexiones" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('produccion.especificaciones.conexiones.importar') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title text-marino fw-bold">
                        <i class="fa-solid fa-file-excel text-success me-2"></i>Importar catálogo de conexiones
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="fs-8 text-muted mb-3">
                        Use la hoja <code>Catalogo_Conexiones</code>. Crea/actualiza productos por SKU y los marca como
                        <strong>CONEXIÓN</strong> (proceso aparte de tubo y flange). Se fabrican en órdenes de producción por piezas.
                    </p>
                    <label class="form-label">Archivo Excel</label>
                    <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls" required>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-baseColor">
                        <i class="fa-solid fa-upload"></i> Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
@endsection
