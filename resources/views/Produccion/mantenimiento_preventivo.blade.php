@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.maquinas') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Catálogo de máquinas
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Mantenimiento preventivo</h2>
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
                <i class="fa-solid fa-calendar-plus me-2"></i>Programar actividad
            </h5>
            <p class="text-muted fs-8 mb-0">Defina la frecuencia y la próxima fecha de ejecución.</p>
        </div>
        <div class="card-body pt-0">
            <form action="{{ route('produccion.maquinas.mantenimiento_preventivo.programacion', $maquina->id) }}"
                method="POST" class="modern-form needs-validation" novalidate>
                @csrf
                <div class="row">
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Actividad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text" name="actividad" list="actividades-preventivas"
                            maxlength="200" value="{{ old('actividad') }}" required>
                        <datalist id="actividades-preventivas">
                            @foreach ($actividadesSugeridas as $actividad)
                                <option value="{{ $actividad }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Frecuencia <span class="text-danger">*</span></label>
                        <select class="form-select" name="frecuencia" required>
                            <option value="SEMANAL" {{ old('frecuencia') === 'SEMANAL' ? 'selected' : '' }}>Semanal</option>
                            <option value="QUINCENAL" {{ old('frecuencia') === 'QUINCENAL' ? 'selected' : '' }}>Quincenal</option>
                            <option value="MENSUAL" {{ old('frecuencia', 'MENSUAL') === 'MENSUAL' ? 'selected' : '' }}>Mensual</option>
                            <option value="TRIMESTRAL" {{ old('frecuencia') === 'TRIMESTRAL' ? 'selected' : '' }}>Trimestral</option>
                            <option value="SEMESTRAL" {{ old('frecuencia') === 'SEMESTRAL' ? 'selected' : '' }}>Semestral</option>
                            <option value="ANUAL" {{ old('frecuencia') === 'ANUAL' ? 'selected' : '' }}>Anual</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Próxima ejecución <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="proxima_ejecucion"
                            value="{{ old('proxima_ejecucion', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">Responsable</label>
                        <select class="form-select" name="responsable_id">
                            <option value="">—</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}" {{ (int) old('responsable_id') === (int) $empleado->id ? 'selected' : '' }}>
                                    {{ $empleado->primer_nombre }} {{ $empleado->apellido_paterno }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-baseColor">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar programación
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-0">
                <i class="fa-solid fa-list me-2"></i>Actividades programadas
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-stripped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Actividad</th>
                        <th>Frecuencia</th>
                        <th>Última ejecución</th>
                        <th>Próxima ejecución</th>
                        <th>Responsable</th>
                        <th>Registrar</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($programaciones as $prog)
                        <tr>
                            <td>{{ $prog->actividad }}</td>
                            <td>{{ $prog->frecuencia_texto }}</td>
                            <td>{{ $prog->ultima_ejecucion ? $prog->ultima_ejecucion->format('d/m/Y') : '—' }}</td>
                            <td>{{ $prog->proxima_ejecucion ? $prog->proxima_ejecucion->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if ($prog->responsable)
                                    {{ $prog->responsable->primer_nombre }} {{ $prog->responsable->apellido_paterno }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('produccion.maquinas.mantenimiento_preventivo', [$maquina->id, 'programacion_id' => $prog->id]) }}#registrar-ejecucion"
                                    class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-check"></i> Ejecutar
                                </a>
                            </td>
                            <td>
                                <form action="{{ route('produccion.maquinas.mantenimiento_preventivo.inactivar', [$maquina->id, $prog->id]) }}"
                                    method="POST" class="d-inline"
                                    onsubmit="return confirm('¿Inactivar esta programación?');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm" title="Inactivar">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted text-center py-4">No hay actividades programadas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5" id="registrar-ejecucion">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-clipboard-list me-2"></i>Registrar ejecución preventiva
            </h5>
            <p class="text-muted fs-8 mb-0">Capture fecha, responsable, actividad, observaciones, resultado y evidencia fotográfica.</p>
        </div>
        <div class="card-body pt-0">
            <form action="{{ route('produccion.maquinas.mantenimiento_preventivo.ejecutar', $maquina->id) }}"
                method="POST" enctype="multipart/form-data" class="modern-form needs-validation" novalidate>
                @csrf
                <div class="row">
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Actividad programada</label>
                        <select class="form-select" name="programacion_id">
                            <option value="">— Sin programación —</option>
                            @foreach ($programaciones as $prog)
                                <option value="{{ $prog->id }}"
                                    {{ (int) old('programacion_id', optional($programacionSeleccionada)->id) === (int) $prog->id ? 'selected' : '' }}>
                                    {{ $prog->actividad }} ({{ $prog->frecuencia_texto }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Actividad realizada <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text" name="actividad" list="actividades-ejecucion"
                            maxlength="200"
                            value="{{ old('actividad', optional($programacionSeleccionada)->actividad) }}" required>
                        <datalist id="actividades-ejecucion">
                            @foreach ($actividadesSugeridas as $actividad)
                                <option value="{{ $actividad }}">
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-md-4 col-12 mt-2">
                        <label class="form-label">Fecha programada <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="fecha_programada"
                            value="{{ old('fecha_programada', date('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-12 mt-2">
                        <label class="form-label">Responsable <span class="text-danger">*</span></label>
                        <select class="form-select" name="responsable_id" required>
                            <option value="">— Seleccione —</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}"
                                    {{ (int) old('responsable_id', optional($programacionSeleccionada)->responsable_id) === (int) $empleado->id ? 'selected' : '' }}>
                                    {{ $empleado->primer_nombre }} {{ $empleado->segundo_nombre }}
                                    {{ $empleado->apellido_paterno }} {{ $empleado->apellido_materno }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @include('Produccion.partials.select_incidencia_maquina', ['cols' => 6])
                </div>
                <div class="row">
                    <div class="col-12 mt-2">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control text" name="observaciones" rows="2">{{ old('observaciones') }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 mt-2">
                        <label class="form-label">Resultado <span class="text-danger">*</span></label>
                        <textarea class="form-control text" name="resultado" rows="2" required>{{ old('resultado') }}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-12 mt-2">
                        <label class="form-label">Evidencias fotográficas</label>
                        <input type="file" class="form-control" name="fotografias[]" multiple
                            accept=".jpg,.jpeg,.png,.webp,image/*">
                    </div>
                    <div class="col-md-6 col-12 mt-2">
                        <label class="form-label">Descripción de las fotografías</label>
                        <input type="text" class="form-control text" name="descripcion_fotos" maxlength="500"
                            value="{{ old('descripcion_fotos') }}">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-baseColor">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar ejecución
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow p-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-0">
                <i class="fa-solid fa-clock-rotate-left me-2"></i>Historial preventivo
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-stripped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Actividad</th>
                        <th>Responsable</th>
                        <th>Resultado</th>
                        <th>Fotos</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historial as $item)
                        <tr>
                            <td>{{ $item->fecha_programada->format('d/m/Y') }}</td>
                            <td>{{ \Illuminate\Support\Str::before($item->descripcion, "\n") }}</td>
                            <td>
                                @if ($item->responsable)
                                    {{ $item->responsable->primer_nombre }} {{ $item->responsable->apellido_paterno }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($item->resultado, 60) ?: '—' }}</td>
                            <td>{{ $item->fotos_count }}</td>
                            <td>
                                <a href="{{ route('produccion.maquinas.mantenimientos.detalle', [$maquina->id, $item->id]) }}"
                                    class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted text-center py-4">No hay ejecuciones preventivas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
@endsection
