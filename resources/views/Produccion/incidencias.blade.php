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
                            <a href="{{ route('produccion.maquinas') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Catálogo de máquinas
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Incidencias</h2>
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
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-circle-plus me-2"></i>Registrar incidencia
            </h5>
            <p class="text-muted fs-8 mb-0">Capture el tipo, nivel de riesgo y responsable de atención.</p>
        </div>
        <div class="card-body pt-0">
            <form action="{{ route('produccion.maquinas.incidencias.store', $maquina->id) }}" method="POST"
                class="modern-form needs-validation" novalidate>
                @csrf

                <h6 class="text-marino mt-2 mb-2">Información</h6>
                <div class="row">
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="fecha"
                            value="{{ old('fecha', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-5 col-12 mt-2">
                        <label class="form-label">Tipo de incidencia <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text" name="titulo" maxlength="200"
                            value="{{ old('titulo') }}" required placeholder="Ej. Fuga de aceite en motor principal">
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Nivel de riesgo <span class="text-danger">*</span></label>
                        <select class="form-select" name="prioridad" required>
                            <option value="BAJA" {{ old('prioridad') === 'BAJA' ? 'selected' : '' }}>Baja</option>
                            <option value="MEDIA" {{ old('prioridad', 'MEDIA') === 'MEDIA' ? 'selected' : '' }}>Media</option>
                            <option value="ALTA" {{ old('prioridad') === 'ALTA' ? 'selected' : '' }}>Alta</option>
                            <option value="CRITICA" {{ old('prioridad') === 'CRITICA' ? 'selected' : '' }}>Crítica</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mt-2">
                        <label class="form-label">Descripción <span class="text-danger">*</span></label>
                        <textarea class="form-control text" name="descripcion" rows="3" required>{{ old('descripcion') }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Responsable de atención <span class="text-danger">*</span></label>
                        <select class="form-select" name="responsable_id" required>
                            <option value="">— Seleccione —</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}" {{ (int) old('responsable_id') === (int) $empleado->id ? 'selected' : '' }}>
                                    {{ $empleado->primer_nombre }} {{ $empleado->segundo_nombre }}
                                    {{ $empleado->apellido_paterno }} {{ $empleado->apellido_materno }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Fecha compromiso</label>
                        <input type="date" class="form-control" name="fecha_compromiso" value="{{ old('fecha_compromiso') }}">
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Estatus <span class="text-danger">*</span></label>
                        <select class="form-select" name="estatus" required>
                            <option value="ABIERTA" {{ old('estatus', 'ABIERTA') === 'ABIERTA' ? 'selected' : '' }}>Abierta</option>
                            <option value="EN_PROCESO" {{ old('estatus') === 'EN_PROCESO' ? 'selected' : '' }}>En proceso</option>
                            <option value="CERRADA" {{ old('estatus') === 'CERRADA' ? 'selected' : '' }}>Cerrada</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-12 mt-2">
                        <label class="form-label">Inspección relacionada</label>
                        <select class="form-select" name="inspeccion_id">
                            <option value="">— Sin inspección —</option>
                            @foreach ($inspecciones as $inspeccion)
                                <option value="{{ $inspeccion->id }}" {{ (int) old('inspeccion_id') === (int) $inspeccion->id ? 'selected' : '' }}>
                                    {{ $inspeccion->fecha_inspeccion->format('d/m/Y') }} — {{ $inspeccion->resultado_general_texto }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-baseColor">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar incidencia
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow p-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de incidencias
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-stripped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Prioridad</th>
                        <th>Responsable</th>
                        <th>Compromiso</th>
                        <th>Estatus</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historial as $item)
                        <tr>
                            <td>{{ $item->fecha->format('d/m/Y') }}</td>
                            <td>{{ $item->titulo }}</td>
                            <td>
                                @if ($item->prioridad === 'CRITICA')
                                    <span class="badge badge-danger-dark fs-9">{{ $item->prioridad_texto }}</span>
                                @elseif ($item->prioridad === 'ALTA')
                                    <span class="badge badge-warning-dark fs-9">{{ $item->prioridad_texto }}</span>
                                @elseif ($item->prioridad === 'MEDIA')
                                    <span class="badge badge-secondary fs-9">{{ $item->prioridad_texto }}</span>
                                @else
                                    <span class="badge badge-success-dark fs-9">{{ $item->prioridad_texto }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($item->responsable)
                                    {{ $item->responsable->primer_nombre }} {{ $item->responsable->apellido_paterno }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $item->fecha_compromiso ? $item->fecha_compromiso->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if ($item->estatus === 'CERRADA')
                                    <span class="badge badge-success-dark fs-9">Cerrada</span>
                                @elseif ($item->estatus === 'EN_PROCESO')
                                    <span class="badge badge-secondary fs-9">En proceso</span>
                                @else
                                    <span class="badge badge-warning-dark fs-9">Abierta</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('produccion.maquinas.incidencias.detalle', [$maquina->id, $item->id]) }}"
                                    class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted text-center py-4">
                                No hay incidencias registradas para esta máquina.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
@endsection
