@extends('layouts.app')
@section('content')

<div class="container-fluid format_page">
    <!-- Encabezado -->
    <div class="row">
        <div class="col-lg-3 col-12 start-center">
            <h3 class="mt-1 animate_animated animate_backInLeft">Divisiones del Proyecto</h3>
            <span class="p-0 m-0 d-none d-md-block fs-8">Gestión y seguimiento de divisiones temporales.</span>
        </div>

        <div class="col-lg-9 col-12 center-end">
            <a href="{{ route('proyectos.index') }}" class="btn btn-secondary fs-7 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Volver a Proyectos
            </a>

            
        </div>
    </div>

    <!-- Información del Servicio -->
    @if($servicio)
    <div class="row mt-3 mb-3">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="mb-1">Cliente</h6>
                            <p class="mb-0 fw-bold">{{ $servicio->cliente }}</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Contrato</h6>
                            <p class="mb-0 fw-bold">{{ $servicio->folio }}</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Fecha Inicio</h6>
                            <p class="mb-0 fw-bold">{{ \Carbon\Carbon::parse($servicio->fecha_inicio)->format('d/m/Y') }}</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Fecha Límite</h6>
                            <p class="mb-0 fw-bold">{{ \Carbon\Carbon::parse($servicio->fecha_limite)->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Tarjetas resumen -->
    <div class="row mt-3 mb-3">
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Total Divisiones</h6>
                            <h1 class="p-3">{{ count($divisiones) }}</h1>
                        </div>
                        <h1 class="fs-max-6 p-3 opacity-50">
                            <i class="fa-solid fa-list-ol"></i>
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Monto Total</h6>
                            <h1 class="p-3">
                                @php
                                    $montoTotal = 0;
                                    foreach($divisiones as $division) {
                                        $montoTotal += $division->monto;
                                    }
                                @endphp
                                ${{ number_format($montoTotal, 2) }}
                            </h1>
                        </div>
                        <h1 class="fs-max-6 p-3 opacity-50">
                            <i class="fa-solid fa-dollar-sign"></i>
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Pendientes</h6>
                            <h1 class="p-3">
                                @php
                                    $pendientes = 0;
                                    foreach($divisiones as $division) {
                                        if ($division->estado == 'Pendiente') {
                                            $pendientes++;
                                        }
                                    }
                                @endphp
                                {{ $pendientes }}
                            </h1>
                        </div>
                        <h1 class="fs-max-6 p-3 opacity-50">
                            <i class="fa-solid fa-clock"></i>
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Vencidas</h6>
                            <h1 class="p-3">
                                @php
                                    $vencidas = 0;
                                    foreach($divisiones as $division) {
                                        if ($division->estado == 'Vencida') {
                                            $vencidas++;
                                        }
                                    }
                                @endphp
                                {{ $vencidas }}
                            </h1>
                        </div>
                        <h1 class="fs-max-6 p-3 opacity-50">
                            <i class="fa-solid fa-exclamation-triangle"></i>
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Divisiones -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-table"></i> Lista de Divisiones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaDivisiones">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Plazo</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th>Monto</th>
                                    <th>Porcentaje</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($divisiones as $division)
                                    @php
                                        $fechaFin = \Carbon\Carbon::parse($division->fecha_fin);
                                        $hoy = \Carbon\Carbon::now();
                                        $diasRestantes = $hoy->diffInDays($fechaFin, false);
                                        
                                        if ($diasRestantes <= 0) {
                                            $estado = 'Vencida';
                                            $estadoClass = 'danger';
                                        } elseif ($diasRestantes <= 5) {
                                            $estado = 'Urgente';
                                            $estadoClass = 'warning';
                                        } elseif ($diasRestantes <= 15) {
                                            $estado = 'Próximo';
                                            $estadoClass = 'info';
                                        } else {
                                            $estado = 'En Tiempo';
                                            $estadoClass = 'success';
                                        }
                                        $sobre = (float)($division->total_ingresos ?? 0) - (float)($division->monto ?? 0);
                                    @endphp
                                    <tr class="{{ $sobre > 0 ? 'table-danger' : '' }}">
                                        <td>{{ $division->id }}</td>
                                        <td>
                                            <span class="badge bg-primary">Plazo {{ $division->plazo }}</span>
                                        </td>
                                        <td>
                                            <i class="fa-solid fa-calendar-day text-muted me-1"></i>
                                            {{ \Carbon\Carbon::parse($division->fecha_inicio)->format('d/m/Y') }}
                                        </td>
                                        <td>
                                            <i class="fa-solid fa-calendar-check text-muted me-1"></i>
                                            {{ \Carbon\Carbon::parse($division->fecha_fin)->format('d/m/Y') }}
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold {{ $sobre > 0 ? 'text-danger' : 'text-success' }}">
                                                    ${{ number_format($division->monto, 2) }}
                                                </span>
                                                @if($sobre > 0)
                                                    <small class="text-danger">+${{ number_format($sobre, 2) }} sobre</small>
                                                @else
                                                    <small class="text-muted">Ingresado: ${{ number_format(($division->total_ingresos ?? 0), 2) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $division->porcentaje }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $estadoClass }}">
                                                {{ $estado }}
                                                @if($diasRestantes > 0)
                                                    ({{ $diasRestantes }} días)
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="tooltip" data-bs-placement="top" 
                                                        title="Editar División">
                                                    <i class="fa-solid fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success" 
                                                        data-bs-toggle="tooltip" data-bs-placement="top" 
                                                        title="Marcar como Completada">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fa-solid fa-inbox fa-3x mb-3"></i>
                                                <h5>No hay divisiones disponibles</h5>
                                                <p>No se encontraron divisiones para este proyecto.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Inicializar DataTable
        $('#tablaDivisiones').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            responsive: true,
            order: [[1, 'asc']], // Ordenar por plazo
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
        });

        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush 