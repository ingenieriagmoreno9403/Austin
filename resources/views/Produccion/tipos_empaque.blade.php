@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-boxes-packing"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Tipos de empaque</h2>
                        <p class="text-muted mb-0 fs-8">Catálogo usado al preparar el empaque de órdenes de producción.</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-blue-light fs-7" href="{{ route('produccion.empaque') }}">
                        <i class="fa-solid fa-box-open"></i> Cola empaque
                    </a>
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
                <i class="fa-solid fa-circle-plus me-2"></i>Nuevo tipo
            </h5>
            <p class="text-muted fs-8 mb-0">
                Marque «Requiere enrollador» si el flujo debe pasar por enrollar (como el rollo).
            </p>
        </div>
        <div class="card-body pt-0">
            <form method="POST" action="{{ route('produccion.tipos_empaque.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-2">
                    <label class="form-label fs-8">Código</label>
                    <input type="text" name="codigo" class="form-control form-control-sm" maxlength="30"
                        value="{{ old('codigo') }}" placeholder="Opcional">
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control form-control-sm" maxlength="120"
                        value="{{ old('nombre') }}" required placeholder="Ej. Rollo especial">
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8">Descripción</label>
                    <input type="text" name="descripcion" class="form-control form-control-sm" maxlength="255"
                        value="{{ old('descripcion') }}">
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="requiere_enrollar" value="1" id="nuevoRequiere"
                            {{ old('requiere_enrollar') ? 'checked' : '' }}>
                        <label class="form-check-label fs-8" for="nuevoRequiere">Requiere enrollador</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-blue btn-sm w-100">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow p-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-0">
                <i class="fa-solid fa-list me-2"></i>Catálogo
            </h5>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="fs-8">Orden</th>
                            <th class="fs-8">Código</th>
                            <th class="fs-8">Nombre</th>
                            <th class="fs-8">Descripción</th>
                            <th class="fs-8 text-center">Enrollador</th>
                            <th class="fs-8">Estatus</th>
                            <th class="fs-8 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tipos as $tipo)
                            <tr>
                                <td colspan="7" class="p-2 border-0">
                                    <form method="POST" action="{{ route('produccion.tipos_empaque.actualizar', $tipo->id) }}"
                                        class="row g-2 align-items-center">
                                        @csrf
                                        <div class="col-md-1">
                                            <input type="number" name="orden" class="form-control form-control-sm"
                                                value="{{ $tipo->orden }}" min="0" max="999">
                                        </div>
                                        <div class="col-md-2">
                                            <span class="badge bg-secondary">{{ $tipo->codigo }}</span>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="nombre" class="form-control form-control-sm"
                                                value="{{ $tipo->nombre }}" maxlength="120" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="descripcion" class="form-control form-control-sm"
                                                value="{{ $tipo->descripcion }}" maxlength="255">
                                        </div>
                                        <div class="col-md-1 text-center">
                                            <input type="checkbox" name="requiere_enrollar" value="1" class="form-check-input"
                                                {{ $tipo->requiere_enrollar ? 'checked' : '' }} title="Requiere enrollador">
                                        </div>
                                        <div class="col-md-1">
                                            <select name="estatus" class="form-select form-select-sm">
                                                <option value="A" {{ $tipo->estatus === 'A' ? 'selected' : '' }}>Activo</option>
                                                <option value="I" {{ $tipo->estatus === 'I' ? 'selected' : '' }}>Inactivo</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button type="submit" class="btn btn-blue-light btn-sm">
                                                <i class="fa-solid fa-check"></i> Actualizar
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted text-center py-4">Sin tipos de empaque.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
