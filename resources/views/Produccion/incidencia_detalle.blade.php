@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.maquinas.incidencias', $maquina->id) }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Incidencias
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Detalle de incidencia</h2>
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
                    <span class="text-muted d-block fs-8">Fecha</span>
                    <strong>{{ $incidencia->fecha->format('d/m/Y') }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Tipo de incidencia</span>
                    <strong>{{ $incidencia->titulo }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Nivel de riesgo</span>
                    <strong>{{ $incidencia->prioridad_texto }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Estatus</span>
                    <strong>{{ $incidencia->estatus_texto }}</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted d-block fs-8">Responsable de atención</span>
                    <strong>
                        @if ($incidencia->responsable)
                            {{ $incidencia->responsable->primer_nombre }} {{ $incidencia->responsable->segundo_nombre }}
                            {{ $incidencia->responsable->apellido_paterno }} {{ $incidencia->responsable->apellido_materno }}
                        @else
                            —
                        @endif
                    </strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted d-block fs-8">Fecha compromiso</span>
                    <strong>{{ $incidencia->fecha_compromiso ? $incidencia->fecha_compromiso->format('d/m/Y') : '—' }}</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted d-block fs-8">Fecha de cierre</span>
                    <strong>{{ $incidencia->fecha_cierre ? $incidencia->fecha_cierre->format('d/m/Y') : '—' }}</strong>
                </div>
                @if ($incidencia->inspeccion)
                    <div class="col-md-6">
                        <span class="text-muted d-block fs-8">Inspección relacionada</span>
                        <strong>
                            <a href="{{ route('produccion.maquinas.inspecciones.detalle', [$maquina->id, $incidencia->inspeccion_id]) }}">
                                {{ $incidencia->inspeccion->fecha_inspeccion->format('d/m/Y') }} — {{ $incidencia->inspeccion->resultado_general_texto }}
                            </a>
                        </strong>
                    </div>
                @endif
                <div class="col-12">
                    <span class="text-muted d-block fs-8">Descripción</span>
                    <p class="mb-0">{{ $incidencia->descripcion }}</p>
                </div>
            </div>
        </div>
    </div>

    @if ($incidencia->estatus !== 'CERRADA')
        <div class="card border-0 shadow p-3 bg-body rounded-5">
            <div class="card-header bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-pen me-2"></i>Actualizar incidencia
                </h5>
                <p class="text-muted fs-8 mb-0">Puede modificar el estatus, la fecha compromiso y registrar el cierre.</p>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('produccion.maquinas.incidencias.update', [$maquina->id, $incidencia->id]) }}"
                    method="POST" class="modern-form needs-validation" novalidate>
                    @csrf
                    <div class="row">
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="estatus" required>
                                <option value="ABIERTA" {{ old('estatus', $incidencia->estatus) === 'ABIERTA' ? 'selected' : '' }}>Abierta</option>
                                <option value="EN_PROCESO" {{ old('estatus', $incidencia->estatus) === 'EN_PROCESO' ? 'selected' : '' }}>En proceso</option>
                                <option value="CERRADA" {{ old('estatus', $incidencia->estatus) === 'CERRADA' ? 'selected' : '' }}>Cerrada</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Fecha compromiso</label>
                            <input type="date" class="form-control" name="fecha_compromiso"
                                value="{{ old('fecha_compromiso', $incidencia->fecha_compromiso ? $incidencia->fecha_compromiso->format('Y-m-d') : '') }}">
                        </div>
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Fecha de cierre</label>
                            <input type="date" class="form-control" name="fecha_cierre"
                                value="{{ old('fecha_cierre', $incidencia->fecha_cierre ? $incidencia->fecha_cierre->format('Y-m-d') : '') }}">
                            <div class="form-text">Requerida al cerrar la incidencia. Si se deja vacía, se usa la fecha de hoy.</div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-baseColor">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
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
