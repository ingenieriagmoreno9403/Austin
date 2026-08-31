@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-user-lock"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Asignar permisos</h2>
                        <p class="text-muted mb-0">Asignar permisos a usuarios por departamento y vista.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-baseColor-light fs-7 mb-2" href="/Panel">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card border-0 shadow p-3 mt-2 bg-body rounded-5">
                <div class="card-header text-start bg-body border-0">
                    <h5 class="text-secondary">Nuevo permiso</h5>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('asignarPermiso') }}" class="g-3 needs-validation" id="frm" novalidate>
                        @csrf

                        <div class="row mb-3">
                            <div class="form-outline">
                                <label class="form-label">Usuarios</label>
                                <select name="idusuario" id="idusuario" class="form-select select2" required>
                                    <option value="">Seleccionar empleado... </option>
                                    @foreach($varlistausers as $usuario)
                                        <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                                    @endforeach
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="form-outline">
                                <label class="form-label">Departamento</label>
                                <select name="iddepartamento" id="iddepartamento" class="form-select select2" required>
                                    <option value="">Seleccionar departamento... </option>
                                    @foreach($varlistadepas as $depas)
                                        <option value="{{ $depas->id }}">{{ $depas->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="form-outline">
                                <label class="form-label">Vistas</label>
                                <select name="idvista" id="idvista" class="form-select select2" required>
                                    <option value="">Seleccionar vista... </option>
                                    @foreach($varlistavistas as $vista)
                                        <option value="{{ $vista->id }}">{{ $vista->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>

                        <div class="row text-end mb-2">
                            <div>
                                <button type="submit" class="fs-6 btn btn-baseColor">
                                    <i class="fas fa-user-lock"></i> Añadir permiso
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
@endsection
