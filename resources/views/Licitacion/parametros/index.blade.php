@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-sliders"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Parámetros de evaluación</h2>
                        <p class="text-muted mb-0">Tipos / familias con ponderación (%) para comparar distribuidores</p>
                    </div>
                </div>
                <div class="header-actions d-flex gap-2">
                    <a href="{{ route('licitaciones.index') }}" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-arrow-left me-1"></i> Licitaciones
                    </a>
                    <button type="button" class="btn btn-baseColor" data-bs-toggle="modal" data-bs-target="#modalNuevoTipo">
                        <i class="fa-solid fa-plus"></i> Nuevo tipo
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning py-2">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3">
        @forelse($tipos as $tipo)
            @php $suma = round($tipo->plantilla->sum('peso_pct'), 2); @endphp
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                        <div>
                            <h6 class="mb-0 fw-bold text-orange">{{ $tipo->nombre }}</h6>
                            <small class="text-muted">{{ $tipo->descripcion ?: 'Sin descripción' }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ abs($suma - 100) <= 0.05 ? 'bg-success' : 'bg-warning text-dark' }}">
                                Suma pesos: {{ $suma }}%
                            </span>
                            <span class="badge bg-secondary">{{ $tipo->parametros_count }} params</span>
                            @if(!$tipo->activo)
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Orden</th>
                                        <th>Parámetro</th>
                                        <th>Peso %</th>
                                        <th>Sentido</th>
                                        <th>Tipo dato</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tipo->plantilla as $p)
                                        <tr>
                                            <td>
                                                <form id="upd-param-{{ $p->id }}" method="post" action="{{ route('licitaciones.parametros.update', $p->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="number" name="orden" class="form-control form-control-sm" value="{{ $p->orden }}" min="1" style="width:70px">
                                                </form>
                                            </td>
                                            <td>
                                                <input form="upd-param-{{ $p->id }}" type="text" name="nombre" class="form-control form-control-sm" value="{{ $p->nombre }}" required>
                                                <input form="upd-param-{{ $p->id }}" type="hidden" name="clave" value="{{ $p->clave }}">
                                            </td>
                                            <td>
                                                <input form="upd-param-{{ $p->id }}" type="number" step="0.01" min="0" max="100" name="peso_pct" class="form-control form-control-sm" value="{{ $p->peso_pct }}" required style="width:90px">
                                            </td>
                                            <td>
                                                <select form="upd-param-{{ $p->id }}" name="sentido" class="form-select form-select-sm">
                                                    @foreach($sentidos as $k => $label)
                                                        <option value="{{ $k }}" {{ $p->sentido === $k ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <select form="upd-param-{{ $p->id }}" name="tipo_dato" class="form-select form-select-sm">
                                                    @foreach($tiposDato as $k => $label)
                                                        <option value="{{ $k }}" {{ $p->tipo_dato === $k ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <button form="upd-param-{{ $p->id }}" class="btn btn-sm btn-primary" title="Guardar"><i class="fa-solid fa-floppy-disk"></i></button>
                                                <form method="post" action="{{ route('licitaciones.parametros.destroy', $p->id) }}" class="d-inline"
                                                      onsubmit="return confirm('¿Eliminar parámetro?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted text-center py-3">Sin parámetros. Agrega al menos uno.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <form method="post" action="{{ route('licitaciones.parametros.store', $tipo->id) }}" class="row g-2 align-items-end border-top pt-3">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label mb-0" style="font-size:.75rem">Nuevo parámetro</label>
                                <input type="text" name="nombre" class="form-control form-control-sm" required placeholder="Nombre">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-0" style="font-size:.75rem">Peso %</label>
                                <input type="number" step="0.01" min="0" max="100" name="peso_pct" class="form-control form-control-sm" required value="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-0" style="font-size:.75rem">Sentido</label>
                                <select name="sentido" class="form-select form-select-sm">
                                    @foreach($sentidos as $k => $label)
                                        <option value="{{ $k }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-0" style="font-size:.75rem">Tipo dato</label>
                                <select name="tipo_dato" class="form-select form-select-sm">
                                    @foreach($tiposDato as $k => $label)
                                        <option value="{{ $k }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-0" style="font-size:.75rem">Orden</label>
                                <input type="number" name="orden" class="form-control form-control-sm" min="1" value="{{ ($tipo->plantilla->max('orden') ?? 0) + 1 }}">
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-baseColor btn-sm w-100"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Aún no hay tipos. Crea el primero (ej. Tecnologías).</div>
            </div>
        @endforelse
    </div>
</div>

<div class="modal fade" id="modalNuevoTipo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('licitaciones.parametros.tipos.store') }}">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title">Nuevo tipo / familia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" required maxlength="120" placeholder="Ej. Tecnologías">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2" maxlength="500"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-baseColor">Crear tipo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
