@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<link href="{{ asset('css/inputfile.css') }}" rel="stylesheet">



<div class="container-fluid format_page">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Empleados/Incapacidades" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Incapacidades
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Alta de Incapacidad</h2>
                        <p class="text-muted mb-0">Registro y carga de evidencia</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-4 pb-0 bg-light rounded-4 shadow-sm border-0 modern-form">

        <div class="row">
            <form action="/Empleados/Incapacidades/Insertar" method="POST" enctype="multipart/form-data" class="g-3 needs-validation form modern-form" novalidate>
                @csrf
                    <div class="modal-body row">
                        <div class="col-md-8">
                            <div class="p-4  mb-4 rounded-3">
                                <h5 class="text-dark mb-3">
                                    <b class="fs-4">1.</b> Información
                                </h5>

                                <div class="row mb-2">
                                    <div class="col-md-12 col-12 form-outline mb-2">
                                        <label class="form-label">Empleado</label>
                                        <select name="id_empleado" class="form-select select2" required>
                                            <option value="">Seleccionar empleado... </option>
                                            @foreach ($varlistaempleados as $vis)
                                            <option value="{{$vis->id}}">
                                                {{$vis->Nombre}}
                                            </option>
                                            @endforeach
                                        </select>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
        
                                    <div class="col-md-6 col-12 form-outline mb-2">
                                        <label class="form-label">Fecha de Incidencia</label>
                                        <input class="form-control" type="date" name="fecha_incidencia" id="fecha_incidencia" maxlength="10" required>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
        
                                    <div class="col-md-6 col-12 form-outline mb-2">
                                        <label class="form-label">Fecha Incio de Incapacidad</label>
                                        <input class="form-control" type="date" name="fecha_inicio" id="fecha_inicio" maxlength="10" required>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
        
        
                                    <div class="col-md-6 col-12 form-outline mb-2">
                                        <label class="form-label">Fecha Fin de Incapacidad</label>
                                        <input class="form-control" type="date" name="fecha_fin" id="fecha_fin" maxlength="10" required>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="col-md-6 col-12 form-outline mb-2">
                                        <label class="form-label">Folio de la incapacidad</label>
                                        <input class="form-control" type="text" name="folio" id="folio" maxlength="50" required>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="col-md-6 col-12 form-outline mb-2">
                                        <label class="form-label">Tipo de incapacidad</label>
                                        <select name="tipo" class="form-select" required>
                                            <option value="">Seleccionar tipo...</option>
                                            <option value="Inicial">Inicial</option>
                                            <option value="Subsecuente">Subsecuente</option>
                                            <option value="Final">Final</option>
                                        </select>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="col-md-6 col-12 form-outline mb-2">
                                        <label class="form-label">Ramo de seguro</label>
                                        <select name="ramo_seguro" class="form-select" required>
                                            <option value="">Seleccionar ramo...</option>
                                            <option value="Enfermedad General">Enfermedad General</option>
                                            <option value="Riesgo de Trabajo">Riesgo de Trabajo</option>
                                            <option value="Maternidad">Maternidad</option>
                                        </select>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
        
        
                                    <div class="form-outline mt-4 mb-2">
                                        <label for="">Escriba una breve descripción informativa:</label>
                                        <div class="form-floating">
                                            <textarea class="form-control" name="descripcion" placeholder="Descripción" id="floatingTextarea" maxlength="300" required></textarea>
                                            <!-- <label for="floatingTextarea">Descripción</label> -->
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
        
                        <div class="col-md-4">
                            <div class="shadow-sm p-4 bg-light mb-4 rounded-3">
                                <h5 class="text-dark mb-3">
                                    <i class="fa-solid fa-paperclip text-secondary me-2"></i>
                                    <b class="fs-4">2.</b> Evidencias
                                </h5>
                                
                                <div class="row p-3 pt-2 justify-content-center">
                                    <div class="modern-file-input" data-input-id="evidencia1">
                                        <div class="file-input-wrapper" id="wrapper-evidencia1">
                                            <div class="file-input-content">
                                                <div class="file-input-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <p class="file-input-text">Arrastra tu archivo aquí</p>
                                                <p class="file-input-subtext">o haz clic para seleccionar</p>
                                            </div>
                                            <input type="file" name="ruta_evidencia" id="evidencia1" class="hidden-file-input" accept=".pdf,.jpg,.jpeg,.png"/>
                                        </div>
                                        <div class="file-preview" id="preview-evidencia1">
                                            <div class="file-preview-item">
                                                <div class="file-preview-info">
                                                    <div class="file-preview-icon">
                                                        <i class="fas fa-file"></i>
                                                    </div>
                                                    <div class="file-preview-details">
                                                        <h6 id="filename-evidencia1"></h6>
                                                        <small id="filesize-evidencia1"></small>
                                                    </div>
                                                </div>
                                                <button type="button" class="file-preview-remove" onclick="removeFile('evidencia1')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" id="progress-evidencia1"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="container">
                        <div class="row justify-content-center p-3">
                            <div class="text-end">
                                <button type="submit" class="btn btn-baseColor"><i class="fa-solid fa-check"></i> Guardar</button>
                            </div>
                        </div>
                    </div>
            </form>
        </div>
    </div>
</div>


<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/files.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const requiredFields = document.querySelectorAll('form [required]');

        requiredFields.forEach((field) => {
            const wrapper = field.closest('.form-outline');
            const label = wrapper ? wrapper.querySelector('label') : null;

            if (!label) {
                return;
            }

            if (label.querySelector('.required-asterisk')) {
                return;
            }

            const asterisk = document.createElement('span');
            asterisk.className = 'required-asterisk text-danger';
            asterisk.textContent = ' *';
            label.appendChild(asterisk);
        });
    });
</script>
@endsection
