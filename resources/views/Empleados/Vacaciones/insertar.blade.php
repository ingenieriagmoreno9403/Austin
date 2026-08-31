@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('PDFwarning'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡Seleccione el archivo correcto!", text: "El formato permitido de archivos admitido es .pdf"});';
            echo '</script>';
    @endphp
@elseif($mensaje = Session::get('warningBD'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 4000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "No se pudo guardar", text: "Verifique la información e intente de nuevo."});';
            echo '</script>';
    @endphp
@endif
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<link href="{{ asset('css/inputfile.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-umbrella-beach"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('Empleados.vacaciones') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Vacaciones
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Alta de Vacaciones</h2>
                        <p class="text-muted mb-0">Registro y carga de evidencia</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-4 pb-0 bg-light rounded-4 shadow-sm border-0 modern-form">
        <div class="row">
            <form action="{{ route('vacaciones_insertar') }}" method="POST" enctype="multipart/form-data" class="g-3 needs-validation form modern-form" novalidate>
                @csrf
                <div class="modal-body row">
                    <div class="col-md-8">
                        <div class="p-4 mb-4 rounded-3">
                            <h5 class="text-dark mb-3">
                                <b class="fs-4">1.</b> Información
                            </h5>

                            <div class="row mb-2">
                                <div class="col-md-6 col-12 form-outline mb-2">
                                    <label class="form-label">Empleado</label>
                                    <select name="id_empleado" class="form-select select2" required>
                                        <option value="">Seleccionar empleado...</option>
                                        @foreach ($varlistaempleados as $vis)
                                            <option value="{{ $vis->id }}">
                                                {{ $vis->Nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="col-md-6 col-12 form-outline mb-2">
                                    <label class="form-label">Fecha de solicitud</label>
                                    <input class="form-control" type="date" name="fecha_solicitante" id="fecha_solicitante" maxlength="10" value="{{ old('fecha_solicitante') }}" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="col-md-6 col-12 form-outline mb-2">
                                    <label class="form-label">Fecha inicio de vacaciones</label>
                                    <input class="form-control" type="date" name="fecha_inicio" id="fecha_inicio" maxlength="10" value="{{ old('fecha_inicio') }}" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="col-md-6 col-12 form-outline mb-2">
                                    <label class="form-label">Fecha fin de vacaciones</label>
                                    <input class="form-control" type="date" name="fecha_fin" id="fecha_fin" maxlength="10" value="{{ old('fecha_fin') }}" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline mt-4 mb-2">
                                    <label for="">Escriba una breve descripción informativa:</label>
                                    <div class="form-floating">
                                        <textarea class="form-control" name="descripcion" placeholder="Descripción" id="floatingTextarea" maxlength="300" required>{{ old('descripcion') }}</textarea>
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
                                <div class="modern-file-input" data-input-id="evidenciaVacaciones">
                                    <div class="file-input-wrapper" id="wrapper-evidenciaVacaciones">
                                        <div class="file-input-content">
                                            <div class="file-input-icon">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <p class="file-input-text">Arrastra tu archivo aquí</p>
                                            <p class="file-input-subtext">o haz clic para seleccionar</p>
                                            <small class="file-input-subtext d-block mt-2">Solo se admite formato .pdf</small>
                                        </div>
                                        <input type="file" name="ruta_evidencia" id="evidenciaVacaciones" class="hidden-file-input" accept=".pdf" required/>
                                    </div>
                                    <div class="file-preview" id="preview-evidenciaVacaciones">
                                        <div class="file-preview-item">
                                            <div class="file-preview-info">
                                                <div class="file-preview-icon">
                                                    <i class="fas fa-file"></i>
                                                </div>
                                                <div class="file-preview-details">
                                                    <h6 id="filename-evidenciaVacaciones"></h6>
                                                    <small id="filesize-evidenciaVacaciones"></small>
                                                </div>
                                            </div>
                                            <button type="button" class="file-preview-remove" onclick="removeFile('evidenciaVacaciones')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-fill" id="progress-evidenciaVacaciones"></div>
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
                            <button type="submit" class="btn btn-baseColor">
                                <i class="fa-solid fa-check"></i> Guardar
                            </button>
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

        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');

        if (fechaInicio && fechaFin) {
            fechaFin.addEventListener('change', function () {
                if (fechaInicio.value && fechaFin.value && fechaFin.value < fechaInicio.value) {
                    fechaFin.setCustomValidity('La fecha fin debe ser igual o posterior a la fecha inicio.');
                } else {
                    fechaFin.setCustomValidity('');
                }
            });
        }
    });
</script>
@endsection
