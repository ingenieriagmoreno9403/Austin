@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/inventario" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Almacenes
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Nuevo Almacén</h2>
                        <p class="text-muted mb-0">Registro de un nuevo almacén</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-body rounded-2 p-4 mb-4 border">
        <form class="modern-form needs-validation" method="post" action="{{ route('minsertaalm') }}" enctype="multipart/form-data" novalidate>
            @csrf

            <h6 class="text-marino mt-1 mb-2">Identificación</h6>
            <div class="row">
                <div class="col-md-3 col-12 mt-2">
                    <label class="form-label" for="folio_interno">Folio interno <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text" name="folio_interno" id="folio_interno" minlength="3" maxlength="100" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-3 col-12 mt-2">
                    <label class="form-label" for="id_tipo_almacen">Tipo almacén <span class="text-danger">*</span></label>
                    <select name="id_tipo_almacen" id="id_tipo_almacen" class="form-select" required>
                        <option value="">— Seleccione —</option>
                        @foreach($Listadotiposalmacen as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->tipo_almacen }}</option>
                        @endforeach
                    </select>
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-3 col-12 mt-2">
                    <label class="form-label" for="id_municipio">Ciudad <span class="text-danger">*</span></label>
                    <select name="id_municipio" id="id_municipio" class="form-select" required>
                        <option value="">— Seleccione —</option>
                        @foreach($Listadomunicipios as $listamuni)
                            <option value="{{ $listamuni->id }}">{{ $listamuni->nombre }}</option>
                        @endforeach
                    </select>
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-3 col-12 mt-2">
                    <label class="form-label" for="id_encargado">Encargado <span class="text-danger">*</span></label>
                    <select name="id_encargado" id="id_encargado" class="form-select" required>
                        <option value="">— Seleccione —</option>
                        @foreach($Listadoempleadosalmacen as $empleados)
                            <option value="{{ $empleados->id }}">
                                {{ $empleados->primer_nombre }} {{ $empleados->segundo_nombre }}
                                {{ $empleados->apellido_paterno }} {{ $empleados->apellido_materno }}
                            </option>
                        @endforeach
                    </select>
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
            </div>

            <h6 class="text-marino mt-4 mb-2">Ubicación y contacto</h6>
            <div class="row">
                <div class="col-md-4 col-12 mt-2">
                    <label class="form-label" for="direccion">Dirección <span class="text-danger">*</span></label>
                    <input type="text" name="direccion" id="direccion" class="form-control text" minlength="50" maxlength="100" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-4 col-12 mt-2">
                    <label class="form-label" for="codigo_postal">Código postal <span class="text-danger">*</span></label>
                    <input type="text" name="codigo_postal" id="codigo_postal" class="form-control text" minlength="3" maxlength="10" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-4 col-12 mt-2">
                    <label class="form-label" for="telefono">Teléfono <span class="text-danger">*</span></label>
                    <input type="text" name="telefono" id="telefono" class="form-control text" minlength="3" maxlength="10" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-4 col-12 mt-2">
                    <label class="form-label" for="correo_electronico">Correo electrónico <span class="text-danger">*</span></label>
                    <input type="text" name="correo_electronico" id="correo_electronico" class="form-control text" minlength="3" maxlength="100" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-4 col-12 mt-2">
                    <label class="form-label" for="contacto">Contacto <span class="text-danger">*</span></label>
                    <input type="text" name="contacto" id="contacto" class="form-control text" minlength="3" maxlength="100" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-4 col-12 mt-2">
                    <label class="form-label" for="capacidad">Capacidad <span class="text-danger">*</span></label>
                    <input type="number" step="any" name="capacidad" id="capacidad" placeholder="00.00" class="form-control text" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
            </div>

            <h6 class="text-marino mt-4 mb-2">Notas</h6>
            <div class="row">
                <div class="col-md-8 col-12 mt-2">
                    <label class="form-label" for="comentarios">Comentarios <span class="text-danger">*</span></label>
                    <textarea name="comentarios" id="comentarios" class="form-control text @error('comentarios') is-invalid @enderror" minlength="3" maxlength="100" rows="3" required placeholder="Describe observaciones o notas del almacén">{{ old('comentarios') }}</textarea>
                    @error('comentarios')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, escribe los comentarios (mínimo 3 caracteres).</div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <button class="btn btn-baseColor" type="submit" id="btn">
                        <i class="fa-solid fa-check"></i> Guardar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection
