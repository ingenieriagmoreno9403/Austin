@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.maquinas') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Catálogo de máquinas
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Refacciones de máquina</h2>
                        <p class="text-muted mb-0">{{ $maquina->codigo }} — {{ $maquina->nombre }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <span class="text-muted d-block fs-8">Almacén asignado</span>
                    <strong>{{ $maquina->almacen->folio_interno ?? '—' }}</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted d-block fs-8">Ubicación en almacén</span>
                    <strong>
                        @if ($maquina->ubicacionAlmacen)
                            {{ $maquina->ubicacionAlmacen->folio_interno }} — {{ $maquina->ubicacionAlmacen->descripcion }}
                        @else
                            —
                        @endif
                    </strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted d-block fs-8">Ubicación en planta</span>
                    <strong>{{ $maquina->ubicacion ?: '—' }}</strong>
                </div>
            </div>
        </div>
    </div>

    @if (!$maquina->id_almacen || !$maquina->id_ubicacion)
        <div class="alert alert-warning border-0 rounded-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            Esta máquina no tiene almacén y ubicación asignados. Edítala en el catálogo para consultar existencias.
        </div>
    @else
        <div class="card border-0 shadow p-3 bg-body rounded-5">
            <div class="card-header bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Existencia en ubicación asignada
                </h5>
                <p class="text-muted fs-8 mb-0">
                    Productos y refacciones registrados en inventario para el almacén y ubicación de esta máquina.
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-stripped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Refacción</th>
                            <th>Categoría</th>
                            <th>Existencia</th>
                            <th>Reservada</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($refacciones as $refaccion)
                            <tr>
                                <td>{{ $refaccion->sku ?: '—' }}</td>
                                <td class="text-start">{{ $refaccion->nombre }}</td>
                                <td>{{ $refaccion->subcategoria ?: '—' }}</td>
                                <td>{{ number_format((float) $refaccion->cantidad_existente, 2) }}</td>
                                <td>{{ number_format((float) $refaccion->cantidad_reservada, 2) }}</td>
                                <td>
                                    @if ((float) $refaccion->cantidad_existente > 0)
                                        <span class="badge badge-success-dark fs-9">Disponible</span>
                                    @else
                                        <span class="badge badge-danger-dark fs-9">Sin existencia</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted text-center py-4">
                                    No hay productos con existencia registrados en esta ubicación del almacén.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@endsection
