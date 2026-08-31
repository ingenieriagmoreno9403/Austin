@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/ubicaciondet/{{$idalm}}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Ubicaciones
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Nueva Ubicación</h2>
                        <p class="text-muted mb-0">{{ $nomalm }} — registro de una nueva ubicación en el almacén</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-body rounded-2 p-4 mb-4 border">
        <form class="modern-form needs-validation" method="post" action="{{ route('minsertaubi') }}" enctype="multipart/form-data" novalidate>
            @csrf
            <input name="id_almacen" id="id_almacen" value="{{ $idalm }}" hidden required />

            <h6 class="text-marino mt-1 mb-2">Identificación</h6>
            <div class="row">
                <div class="col-md-4 col-12 mt-2">
                    <label class="form-label" for="folio_interno">Folio interno <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text" name="folio_interno" id="folio_interno" minlength="3" maxlength="100" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-4 col-12 mt-2">
                    <label class="form-label" for="id_tipo_ubicacion">Tipo ubicación <span class="text-danger">*</span></label>
                    <select name="id_tipo_ubicacion" id="id_tipo_ubicacion" class="form-select" required>
                        <option value="">— Seleccione —</option>
                        @foreach($ubicaciones as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-4 col-12 mt-2">
                    <label class="form-label" for="capacidad">Capacidad <span class="text-danger">*</span></label>
                    <input type="number" step="any" placeholder="00.00" name="capacidad" id="capacidad" class="form-control text" min="0" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
            </div>

            <h6 class="text-marino mt-4 mb-2">Posición</h6>
            <div class="row">
                <div class="col-md-3 col-12 mt-2">
                    <label class="form-label" for="espacio">Espacio <span class="text-danger">*</span></label>
                    <input type="number" placeholder="00" name="espacio" id="espacio" class="form-control text" min="0" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-3 col-12 mt-2">
                    <label class="form-label" for="nivel">Nivel <span class="text-danger">*</span></label>
                    <input type="text" name="nivel" id="nivel" class="form-control text" minlength="1" maxlength="100" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-3 col-12 mt-2">
                    <label class="form-label" for="ubicacion">Ubicación <span class="text-danger">*</span></label>
                    <input type="number" name="ubicacion" id="ubicacion" placeholder="00" class="form-control text" min="0" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-3 col-12 mt-2">
                    <label class="form-label" for="cordenadas">Coordenadas <span class="text-danger">*</span></label>
                    <input type="text" name="cordenadas" id="cordenadas" class="form-control text" minlength="1" maxlength="100" required />
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
            </div>

            <h6 class="text-marino mt-4 mb-2">Detalle</h6>
            <div class="row">
                <div class="col-md-6 col-12 mt-2">
                    <label class="form-label" for="descripcion">Descripción <span class="text-danger">*</span></label>
                    <textarea name="descripcion" id="descripcion" class="form-control text" minlength="3" maxlength="60" rows="3" required></textarea>
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>
                <div class="col-md-6 col-12 mt-2">
                    <label class="form-label" for="observaciones">Observaciones <span class="text-danger">*</span></label>
                    <textarea name="observaciones" id="observaciones" class="form-control text" minlength="3" maxlength="100" rows="3" required></textarea>
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
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
