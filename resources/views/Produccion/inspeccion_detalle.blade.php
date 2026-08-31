@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.maquinas.inspecciones', $maquina->id) }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Inspecciones semanales
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Detalle de inspección</h2>
                        <p class="text-muted mb-0">{{ $maquina->codigo }} — {{ $maquina->nombre }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
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

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Fecha de inspección</span>
                    <strong>{{ $inspeccion->fecha_inspeccion->format('d/m/Y') }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Inspector</span>
                    <strong>
                        @if ($inspeccion->inspector)
                            {{ $inspeccion->inspector->primer_nombre }} {{ $inspeccion->inspector->segundo_nombre }}
                            {{ $inspeccion->inspector->apellido_paterno }} {{ $inspeccion->inspector->apellido_materno }}
                        @else
                            —
                        @endif
                    </strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Supervisor</span>
                    <strong>
                        @if ($inspeccion->supervisor)
                            {{ $inspeccion->supervisor->primer_nombre }} {{ $inspeccion->supervisor->segundo_nombre }}
                            {{ $inspeccion->supervisor->apellido_paterno }} {{ $inspeccion->supervisor->apellido_materno }}
                        @else
                            —
                        @endif
                    </strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Resultado general</span>
                    <strong>{{ $inspeccion->resultado_general_texto }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Estatus</span>
                    <strong>{{ $inspeccion->estatus_texto }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Requiere mantenimiento</span>
                    <strong>{{ $inspeccion->requiere_mantenimiento ? 'Sí' : 'No' }}</strong>
                </div>
                @if ($inspeccion->observaciones_generales)
                    <div class="col-12">
                        <span class="text-muted d-block fs-8">Observaciones generales</span>
                        <p class="mb-0">{{ $inspeccion->observaciones_generales }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-0">
                <i class="fa-solid fa-list-check me-2"></i>Lista de revisión
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-stripped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Resultado</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inspeccion->detalles as $detalle)
                        <tr>
                            <td>{{ $detalle->concepto }}</td>
                            <td>
                                @if ($detalle->resultado === 'OK')
                                    <span class="badge badge-success-dark fs-9">OK</span>
                                @elseif ($detalle->resultado === 'OBSERVACION')
                                    <span class="badge badge-warning-dark fs-9">Observación</span>
                                @else
                                    <span class="badge badge-danger-dark fs-9">Crítico</span>
                                @endif
                            </td>
                            <td>{{ $detalle->observaciones ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-0">
                <i class="fa-solid fa-camera me-2"></i>Evidencia fotográfica
            </h5>
        </div>
        <div class="card-body pt-0">
            @php
                $fotosPorCategoria = $inspeccion->fotos->groupBy('descripcion');
            @endphp
            @forelse ($fotosPorCategoria as $categoria => $fotos)
                <h6 class="text-secondary mt-3 mb-2">{{ $categoria }}</h6>
                <div class="row g-2 mb-2">
                    @foreach ($fotos as $foto)
                        <div class="col-md-4">
                            <a href="{{ asset($foto->ruta) }}" target="_blank" rel="noopener">
                                <img src="{{ asset($foto->ruta) }}" alt="{{ $foto->nombre_archivo }}"
                                    class="img-fluid rounded border">
                            </a>
                            <small class="text-muted d-block mt-1">{{ $foto->nombre_archivo }}</small>
                        </div>
                    @endforeach
                </div>
            @empty
                <p class="text-muted mb-0">Sin fotografías adjuntas.</p>
            @endforelse
        </div>
    </div>

    @if ($inspeccion->estatus === 'ABIERTA')
        <div class="card border-0 shadow p-3 bg-body rounded-5">
            <div class="card-header bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-lock me-2"></i>Cerrar inspección
                </h5>
                <p class="text-muted fs-8 mb-0">El supervisor valida y cierra la inspección semanal.</p>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('produccion.maquinas.inspecciones.cerrar', [$maquina->id, $inspeccion->id]) }}"
                    method="POST" class="modern-form needs-validation" novalidate>
                    @csrf
                    <div class="row">
                        <div class="col-md-6 col-12 mt-2">
                            <label class="form-label">Supervisor que valida <span class="text-danger">*</span></label>
                            <select class="form-select" name="supervisor_id" required>
                                <option value="">— Seleccione —</option>
                                @foreach ($empleados as $empleado)
                                    <option value="{{ $empleado->id }}" {{ (int) old('supervisor_id', $inspeccion->supervisor_id) === (int) $empleado->id ? 'selected' : '' }}>
                                        {{ $empleado->primer_nombre }} {{ $empleado->segundo_nombre }}
                                        {{ $empleado->apellido_paterno }} {{ $empleado->apellido_materno }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-baseColor">
                                <i class="fa-solid fa-check"></i> Cerrar inspección
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

<script src="{{ asset('js/validation.js') }}"></script>
@endsection
