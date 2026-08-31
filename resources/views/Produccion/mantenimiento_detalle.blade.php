@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.maquinas.mantenimientos', $maquina->id) }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Mantenimientos
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Detalle de mantenimiento</h2>
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
                    <span class="text-muted d-block fs-8">Orden Nº</span>
                    <strong>{{ $mantenimiento->folio ?: ('#' . $mantenimiento->id) }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Fecha reporte</span>
                    <strong>{{ ($mantenimiento->fecha_reporte ?? $mantenimiento->fecha_programada)?->format('d/m/Y') }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Fecha programada</span>
                    <strong>{{ $mantenimiento->fecha_programada->format('d/m/Y') }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Máquina (ID)</span>
                    <strong>{{ $maquina->codigo }} — {{ $maquina->nombre }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Tipo</span>
                    <strong>{{ $mantenimiento->tipo_texto }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Estado</span>
                    <strong>{{ $mantenimiento->estatus_texto }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Técnico</span>
                    <strong>
                        @if ($mantenimiento->responsable)
                            {{ $mantenimiento->responsable->primer_nombre }} {{ $mantenimiento->responsable->segundo_nombre }}
                            {{ $mantenimiento->responsable->apellido_paterno }} {{ $mantenimiento->responsable->apellido_materno }}
                        @else
                            —
                        @endif
                    </strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Incidencia relacionada</span>
                    <strong>
                        @if ($mantenimiento->incidencia)
                            <a href="{{ route('produccion.maquinas.incidencias.detalle', [$maquina->id, $mantenimiento->incidencia->id]) }}">
                                #{{ $mantenimiento->incidencia->id }} — {{ $mantenimiento->incidencia->titulo }}
                            </a>
                        @else
                            —
                        @endif
                    </strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Causa (CF-xx)</span>
                    <strong>
                        @if ($mantenimiento->causaFalla)
                            {{ $mantenimiento->causaFalla->clave }} — {{ $mantenimiento->causaFalla->nombre }}
                        @else
                            —
                        @endif
                    </strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Hora paro</span>
                    <strong>{{ $mantenimiento->fecha_inicio ? $mantenimiento->fecha_inicio->format('d/m/Y H:i') : '—' }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Hora reinicio</span>
                    <strong>{{ $mantenimiento->fecha_fin ? $mantenimiento->fecha_fin->format('d/m/Y H:i') : '—' }}</strong>
                </div>
                <div class="col-md-2">
                    <span class="text-muted d-block fs-8">Duración paro (h)</span>
                    <strong>{{ $mantenimiento->horas_paro !== null ? number_format($mantenimiento->horas_paro, 2) : '—' }}</strong>
                </div>
                <div class="col-md-2">
                    <span class="text-muted d-block fs-8">Costo mano obra ($)</span>
                    <strong>{{ $mantenimiento->costo_mano_obra !== null ? '$' . number_format($mantenimiento->costo_mano_obra, 2) : '—' }}</strong>
                </div>
                <div class="col-md-2">
                    <span class="text-muted d-block fs-8">Costo refacción ($)</span>
                    <strong>{{ $mantenimiento->costo_refacciones !== null ? '$' . number_format($mantenimiento->costo_refacciones, 2) : '—' }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted d-block fs-8">Costo total ($)</span>
                    <strong>{{ $mantenimiento->costo_total !== null ? '$' . number_format($mantenimiento->costo_total, 2) : '—' }}</strong>
                </div>
                @if ($mantenimiento->refacciones->isNotEmpty())
                    <div class="col-12">
                        <span class="text-muted d-block fs-8 mb-1">Refacción usada</span>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Refacción (REF-)</th>
                                        <th>Cant.</th>
                                        <th>Costo unitario</th>
                                        <th>Costo total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($mantenimiento->refacciones as $ref)
                                        <tr>
                                            <td>
                                                @if ($ref->producto)
                                                    {{ $ref->producto->sku ?: $ref->producto->nombre }} — {{ $ref->producto->nombre }}
                                                @else
                                                    #{{ $ref->producto_id }}
                                                @endif
                                            </td>
                                            <td>{{ number_format((float) $ref->cantidad, 3) }}</td>
                                            <td>{{ $ref->costo_unitario !== null ? '$' . number_format($ref->costo_unitario, 2) : '—' }}</td>
                                            <td>{{ $ref->costo_total !== null ? '$' . number_format($ref->costo_total, 2) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
                <div class="col-12">
                    <span class="text-muted d-block fs-8">Descripción falla</span>
                    <p class="mb-0">{{ $mantenimiento->descripcion }}</p>
                </div>
                @if ($mantenimiento->resultado)
                    <div class="col-12">
                        <span class="text-muted d-block fs-8">Resultado</span>
                        <p class="mb-0">{{ $mantenimiento->resultado }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-0">
                <i class="fa-solid fa-camera me-2"></i>Fotografías de evidencia
            </h5>
        </div>
        <div class="card-body pt-0">
            @if ($mantenimiento->fotos->isNotEmpty())
                <div class="row g-2">
                    @foreach ($mantenimiento->fotos as $foto)
                        <div class="col-md-4">
                            <a href="{{ asset($foto->ruta) }}" target="_blank" rel="noopener">
                                <img src="{{ asset($foto->ruta) }}" alt="{{ $foto->nombre_archivo }}"
                                    class="img-fluid rounded border">
                            </a>
                            <small class="text-muted d-block mt-1">{{ $foto->nombre_archivo }}</small>
                            @if ($foto->descripcion)
                                <small class="d-block">{{ $foto->descripcion }}</small>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted mb-0">Sin fotografías adjuntas.</p>
            @endif
        </div>
    </div>

    @if (!in_array($mantenimiento->estatus, ['FINALIZADO', 'CANCELADO']))
        <div class="card border-0 shadow p-3 bg-body rounded-5">
            <div class="card-header bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-pen me-2"></i>Actualizar mantenimiento
                </h5>
                <p class="text-muted fs-8 mb-0">Solo puede actualizar el estatus, la fecha de fin, las horas de paro y el costo de mano de obra.</p>
            </div>
            <div class="card-body pt-0">
                <form action="{{ route('produccion.maquinas.mantenimientos.update', [$maquina->id, $mantenimiento->id]) }}"
                    method="POST" class="modern-form needs-validation" novalidate>
                    @csrf
                    <div class="row">
                        <div class="col-md-3 col-12 mt-2">
                            <label class="form-label">Estatus <span class="text-danger">*</span></label>
                            <select class="form-select" name="estatus" required>
                                <option value="PENDIENTE" {{ old('estatus', $mantenimiento->estatus) === 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                                <option value="EN_PROCESO" {{ old('estatus', $mantenimiento->estatus) === 'EN_PROCESO' ? 'selected' : '' }}>En proceso</option>
                                <option value="FINALIZADO" {{ old('estatus', $mantenimiento->estatus) === 'FINALIZADO' ? 'selected' : '' }}>Finalizado</option>
                                <option value="CANCELADO" {{ old('estatus', $mantenimiento->estatus) === 'CANCELADO' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-12 mt-2">
                            <label class="form-label">Fecha fin</label>
                            <input type="datetime-local" class="form-control" name="fecha_fin"
                                value="{{ old('fecha_fin', $mantenimiento->fecha_fin ? $mantenimiento->fecha_fin->format('Y-m-d\TH:i') : '') }}">
                        </div>
                        <div class="col-md-3 col-12 mt-2">
                            <label class="form-label">Horas de paro</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="horas_paro"
                                value="{{ old('horas_paro', $mantenimiento->horas_paro) }}">
                        </div>
                        <div class="col-md-3 col-12 mt-2">
                            <label class="form-label">Costo mano de obra</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="costo_mano_obra"
                                value="{{ old('costo_mano_obra', $mantenimiento->costo_mano_obra) }}">
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
