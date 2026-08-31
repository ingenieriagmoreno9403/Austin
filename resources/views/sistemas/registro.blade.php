@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid registro-usuarios-page">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Registro</h2>
                        <p class="text-muted mb-0">Alta de usuarios y restablecimiento de contraseñas</p>
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

    <div class="row justify-content-center mb-4">
        <div class="card border-0 shadow p-3 mt-2 bg-body rounded-5 registro-card">
            <div class="card-header text-start bg-body border-0 pb-0">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h5 class="text-secondary mb-1">
                            <i class="fa-solid fa-user-plus me-2"></i>Registrar usuario
                        </h5>
                        <p class="text-muted fs-8 mb-0">Selecciona el tipo, busca la persona relacionada y captura sus credenciales.</p>
                    </div>
                    <span class="registro-card-badge">
                        <i class="fa-solid fa-magnifying-glass"></i> Buscadores activos
                    </span>
                </div>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('createUser') }}" method="POST"
                    class="g-3 needs-validation form modern-form" enctype="multipart/form-data" id="frm" novalidate>
                    @csrf
                    <div class="registro-form-shell">
                        <div class="registro-section-title">
                            <span class="registro-section-title__icon">
                                <i class="fa-solid fa-address-card"></i>
                            </span>
                            <div>
                                <h6>Tipo y relación</h6>
                                <p>Elige cómo se dará de alta el usuario. Master no requiere relación.</p>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-lg-3 col-12">
                                <label class="form-label d-block">Tipo de usuario</label>
                                <div class="registro-tipo-options">
                                    <label class="registro-tipo-option" for="tipoEmpleado">
                                        <input class="form-check-input" type="radio" name="tipo" id="tipoEmpleado"
                                            value="empleado" onclick="mostrarTipoUsuario('empleado')">
                                        <span><i class="fa-solid fa-user-tie"></i> Empleado</span>
                                    </label>

                                    <label class="registro-tipo-option" for="tipoProveedor">
                                        <input class="form-check-input" type="radio" name="tipo" id="tipoProveedor"
                                            value="proveedor" onclick="mostrarTipoUsuario('proveedor')">
                                        <span><i class="fa-solid fa-truck-field"></i> Proveedor</span>
                                    </label>

                                    <label class="registro-tipo-option" for="tipoAlumno">
                                        <input class="form-check-input" type="radio" name="tipo" id="tipoAlumno"
                                            value="alumno" onclick="mostrarTipoUsuario('alumno')">
                                        <span><i class="fa-solid fa-graduation-cap"></i> Alumno</span>
                                    </label>

                                    <label class="registro-tipo-option" for="tipoSocio">
                                        <input class="form-check-input" type="radio" name="tipo" id="tipoSocio"
                                            value="socio" onclick="mostrarTipoUsuario('socio')">
                                        <span><i class="fa-solid fa-handshake"></i> Socio</span>
                                    </label>

                                    <label class="registro-tipo-option" for="tipoEmpresa">
                                        <input class="form-check-input" type="radio" name="tipo" id="tipoEmpresa"
                                            value="empresa" onclick="mostrarTipoUsuario('empresa')">
                                        <span><i class="fa-solid fa-building"></i> Empresa</span>
                                    </label>

                                    <label class="registro-tipo-option" for="tipoMaster">
                                        <input class="form-check-input" type="radio" name="tipo" id="tipoMaster"
                                            value="master" onclick="mostrarTipoUsuario('master')">
                                        <span><i class="fa-solid fa-user-shield"></i> Master</span>
                                    </label>
                                </div>
                            </div>

                            <div class="col-lg-9 col-12 mb-3">
                                <div class="alert alert-info border-0 rounded-4 mb-3" id="masterInfo">
                                    <i class="fa-solid fa-circle-info me-2"></i>
                                    El usuario master no requiere proveedor, empleado, alumno, socio o empresa.
                                </div>

                                <div class="form-outline registro-tipo-panel" id="empleado">
                                    <label class="form-label">Empleados</label>
                                    <select name="id_tipo_empleado" id="id_tipo_empleado" class="form-select select2 registro-buscador"
                                        data-placeholder="Buscar empleado por número o nombre...">
                                        <option value="">Seleccionar empleado... </option>
                                        @foreach ($varlistaempleados as $vis)
                                            @if ($vis->estado == 'A' && is_null($vis->id_user))
                                                <option value="{{ $vis->idempleado }}">{{ $vis->idempleado }} -
                                                    {{ $vis->primer_nombre . ' ' . $vis->segundo_nombre . ' ' . $vis->apellido_paterno . ' ' . $vis->apellido_materno }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <small class="registro-search-help">
                                        <i class="fa-solid fa-keyboard me-1"></i>Escribe el número o nombre del empleado.
                                    </small>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline registro-tipo-panel" id="proveedor">
                                    <label class="form-label">Proveedor</label>
                                    <select name="id_tipo_proveedor" id="id_tipo_proveedor" class="form-select select2 registro-buscador"
                                        data-placeholder="Buscar proveedor...">
                                        <option value="">Seleccionar proveedor... </option>
                                        @foreach ($optenerproveedoresuser as $vis)
                                            <option value="{{ $vis->id }}">{{ $vis->nombre }} </option>
                                        @endforeach
                                    </select>
                                    <small class="registro-search-help">
                                        <i class="fa-solid fa-keyboard me-1"></i>Solo aparecen proveedores sin usuario asignado.
                                    </small>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline registro-tipo-panel" id="alumno">
                                    <label class="form-label">Alumno</label>
                                    <select name="id_tipo_alumno" id="id_tipo_alumno" class="form-select select2 registro-buscador"
                                        data-placeholder="Buscar alumno...">
                                        <option value="">Seleccionar alumno... </option>
                                        @foreach ($varlistaalumnos as $alumno)
                                            <option value="{{ $alumno->id }}">
                                                {{ $alumno->id }} -
                                                {{ $alumno->nombres }} {{ $alumno->apellido_paterno }} {{ $alumno->apellido_materno }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="registro-search-help">
                                        <i class="fa-solid fa-keyboard me-1"></i>Busca por matrícula, nombre o apellidos.
                                    </small>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline registro-tipo-panel" id="socio">
                                    <label class="form-label">Socio</label>
                                    <select name="id_tipo_socio" id="id_tipo_socio" class="form-select select2 registro-buscador"
                                        data-placeholder="Buscar socio...">
                                        <option value="">Seleccionar socio... </option>
                                        @foreach ($varlistasocios as $socio)
                                            <option value="{{ $socio->id }}">
                                                {{ $socio->numero_socio }} -
                                                {{ $socio->nombre }} {{ $socio->ap_paterno }} {{ $socio->ap_materno }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="registro-search-help">
                                        <i class="fa-solid fa-keyboard me-1"></i>Busca por número de socio o nombre.
                                    </small>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline registro-tipo-panel" id="empresa">
                                    <label class="form-label">Empresa</label>
                                    <select name="id_tipo_empresa" id="id_tipo_empresa" class="form-select select2 registro-buscador"
                                        data-placeholder="Buscar empresa...">
                                        <option value="">Seleccionar empresa... </option>
                                        @foreach ($varlistaempresas as $empresa)
                                            <option value="{{ $empresa->id }}">
                                                {{ $empresa->id }} -
                                                {{ $empresa->nombre }}
                                                @if (!empty($empresa->municipio))
                                                    ({{ $empresa->municipio }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="registro-search-help">
                                        <i class="fa-solid fa-keyboard me-1"></i>Solo aparecen empresas sin usuario asignado.
                                    </small>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>

                        <div class="registro-section-title">
                            <span class="registro-section-title__icon registro-section-title__icon--green">
                                <i class="fa-solid fa-key"></i>
                            </span>
                            <div>
                                <h6>Credenciales de acceso</h6>
                                <p>Define usuario, correo y contraseña para iniciar sesión.</p>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-outline">
                                    <label class="form-label">Nombre de Usuario</label>
                                    <input type="" id="name" name="name" class="form-control"
                                        minlength="3" maxlength="100" placeholder="Nombre de usuario..."
                                        required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="form-outline">
                                    <label class="form-label">Correo</label>
                                    <input type="email" id="email" name="email" class="form-control"
                                        minlength="3" maxlength="100" placeholder="user@example.com" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="pass1">Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" id="txtPassword" name="contrasena"
                                            class="form-control"
                                            placeholder="Ingrese su contraseña..."
                                            minlength="8" required>
                                        <div class="input-group-append">
                                            <a id="show_password" class="btn__password fs-6" type="button"
                                                onclick="mostrarPassword()"
                                                style="margin-top: 4px;margin-left:5px;color:#a6a6a6;"> <span
                                                    class="fa fa-eye-slash icon"></span> </a>
                                        </div>
                                    </div>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Mínimo 8 caracteres.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="pass2">Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" id="inputPassword" name="recontrasena"
                                            class="form-control"
                                            placeholder="Confirme su contraseña..."
                                            minlength="8" required>
                                        <div class="input-group-append">
                                            <a id="show_password" class="btn__password fs-6" type="button"
                                                onclick="showPassword()"
                                                style="margin-top: 4px;margin-left:5px;color:#a6a6a6;"> <span
                                                    class="fa fa-eye-slash icon1"></span> </a>
                                        </div>
                                    </div>


                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Mínimo 8 caracteres.</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>

                <div class="registro-action-bar">
                    <div class="text-muted fs-8">
                        <i class="fa-solid fa-circle-info me-1"></i>Los campos requeridos se validan antes de guardar.
                    </div>
                    <div>
                        <button type="button" value="Registrar" onclick="validarPasswords()"
                            class="fs-6 btn btn-baseColor">
                            <i class="fas fa-check"></i> Registrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mb-4">
        <div class="card border-0 shadow p-3 mt-2 bg-body rounded-5 registro-card">
            <div class="card-header text-start bg-body border-0 pb-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-unlock-keyhole me-2"></i>Restablecer contraseña
                </h5>
                <p class="text-muted fs-8 mb-0">Busca el usuario y captura su nueva contraseña.</p>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('updatepass') }}" method="POST"
                    class="g-3 needs-validation form modern-form" enctype="multipart/form-data" id="frm2" novalidate>
                    @csrf
                    <div class="p-2 pt-0">
                        <div class="row mb-3">
                            <div class="form-outline">
                                <label class="form-label">Usuarios</label>
                                <select name="iduser" id="iduser" class="form-select select2 registro-buscador" required
                                    data-placeholder="Buscar usuario por id, nombre o estado...">
                                    <option value="">Seleccionar usuario... </option>
                                    @foreach ($valusers as $item)
                                        <option value="{{ $item->id }}">{{ $item->id }} -
                                            {{ $item->name }}
                                            @if ($item->estado_user == 'A')
                                                (Activo)
                                            @else
                                                (Inactivo)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>



                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pass1">Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" id="txtPassword2" name="contrasena"
                                            class="form-control"
                                            placeholder="Ingrese su contraseña..."
                                            minlength="8" required>
                                        <div class="input-group-append">
                                            <a id="show_password" class="btn__password fs-6" type="button"
                                                onclick="mostrarPassword2()"
                                                style="margin-top: 4px;margin-left:5px;color:#a6a6a6;"> <span
                                                    class="fa fa-eye-slash icon2"></span> </a>
                                        </div>
                                    </div>


                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Mínimo 8 caracteres.</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pass2">Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" id="inputPassword2" name="recontrasena"
                                            class="form-control"
                                            placeholder="Confirme su contraseña..."
                                            minlength="8" required>
                                        <div class="input-group-append">
                                            <a id="show_password" class="btn__password fs-6" type="button"
                                                onclick="showPassword2()"
                                                style="margin-top: 4px;margin-left:5px;color:#a6a6a6;"> <span
                                                    class="fa fa-eye-slash icon22"></span> </a>
                                        </div>
                                    </div>


                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Mínimo 8 caracteres.</div>
                                </div>
                            </div>


                        </div>

                    </div>
                </form>

                <div class="registro-action-bar">
                    <div class="text-muted fs-8">
                        <i class="fa-solid fa-circle-info me-1"></i>Elige el usuario y confirma la nueva contraseña.
                    </div>
                    <div>
                        <button type="button" onclick="validarPasswords2()" class="fs-6 btn btn-baseColor">
                            <i class="fas fa-check"></i> Restablecer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mb-4">
        <div class="card border-0 shadow p-3 mt-2 bg-body rounded-5 registro-card">
            <div class="card-header text-start bg-body border-0 pb-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-user-slash me-2"></i>Inactivar usuario
                </h5>
                <p class="text-muted fs-8 mb-0">Cambia el estado del usuario a inactivo para impedir su acceso al sistema.</p>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('inactivarUser') }}" class="g-3 needs-validation form modern-form"
                    enctype="multipart/form-data" id="frmInactivar" novalidate>
                    @csrf
                    <div class="p-2 pt-0">
                        <div class="row mb-4">
                            <div class="form-outline">
                                <label class="form-label">Usuarios activos</label>
                                <select name="iduser" id="iduser_inactivar" class="form-select select2 registro-buscador" required
                                    data-placeholder="Buscar usuario activo para inactivar...">
                                    <option value="">Seleccionar usuario... </option>
                                    @foreach ($valusers as $item)
                                        @if ($item->estado_user == 'A')
                                            <option value="{{ $item->id }}">{{ $item->id }} -
                                                {{ $item->name }} (Activo)
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <small class="registro-search-help">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>Al inactivarlo ya no podrá iniciar sesión.
                                </small>
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Selecciona un usuario activo.
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="registro-action-bar registro-action-bar--danger">
                    <div class="text-muted fs-8">
                        <i class="fa-solid fa-circle-info me-1"></i>Esta acción cambia `estado_user` a `I`.
                    </div>
                    <div>
                        <button type="button" onclick="validarInactivarUsuario()" class="fs-6 btn btn-danger">
                            <i class="fa-solid fa-user-slash"></i> Inactivar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="card border-0 shadow p-3 mt-2 bg-body rounded-5 registro-card">
            <div class="card-header text-start bg-body border-0 pb-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-user-check me-2"></i>Activar usuario
                </h5>
                <p class="text-muted fs-8 mb-0">Cambia el estado del usuario a activo para permitir su acceso al sistema.</p>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('activarUser') }}" class="g-3 needs-validation form modern-form"
                    enctype="multipart/form-data" id="frmActivar" novalidate>
                    @csrf
                    <div class="p-2 pt-0">
                        <div class="row mb-4">
                            <div class="form-outline">
                                <label class="form-label">Usuarios inactivos</label>
                                <select name="iduser" id="iduser_activar" class="form-select select2 registro-buscador" required
                                    data-placeholder="Buscar usuario inactivo para activar...">
                                    <option value="">Seleccionar usuario... </option>
                                    @foreach ($valusers as $item)
                                        @if ($item->estado_user == 'I')
                                            <option value="{{ $item->id }}">{{ $item->id }} -
                                                {{ $item->name }} (Inactivo)
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <small class="registro-search-help">
                                    <i class="fa-solid fa-circle-check me-1"></i>Al activarlo podrá iniciar sesión nuevamente.
                                </small>
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Selecciona un usuario inactivo.
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="registro-action-bar registro-action-bar--success">
                    <div class="text-muted fs-8">
                        <i class="fa-solid fa-circle-info me-1"></i>Esta acción cambia `estado_user` a `A`.
                    </div>
                    <div>
                        <button type="button" onclick="validarActivarUsuario()" class="fs-6 btn btn-success">
                            <i class="fa-solid fa-user-check"></i> Activar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
<script>
    const tipoSelects = {
        empleado: '#id_tipo_empleado',
        proveedor: '#id_tipo_proveedor',
        alumno: '#id_tipo_alumno',
        socio: '#id_tipo_socio',
        empresa: '#id_tipo_empresa',
    };

    $('.registro-tipo-panel').hide();
    $('#masterInfo').hide();
    Object.values(tipoSelects).forEach(function(selector) {
        document.querySelector(selector).required = false;
    });

    function mostrarTipoUsuario(tipo) {
        $('.registro-tipo-panel').hide();
        $('#masterInfo').toggle(tipo === 'master');
        $('.registro-tipo-option').removeClass('is-selected');
        $('#tipo' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).closest('.registro-tipo-option').addClass('is-selected');

        Object.values(tipoSelects).forEach(function(selector) {
            const select = document.querySelector(selector);
            select.required = false;
            select.value = '';
            $(selector).trigger('change');
        });

        if (tipoSelects[tipo]) {
            $(tipo === 'empleado' ? '#empleado' : '#' + tipo).show();
            document.querySelector(tipoSelects[tipo]).required = true;
            inicializarBuscadoresRegistro();
        }
    }
</script>

<script>
    // Ejemplo de JavaScript inicial para deshabilitar el envío de formularios si hay campos no válidos
    (function() {
        'use strict'

        // Obtener todos los formularios a los que queremos aplicar estilos de validación de Bootstrap personalizados
        var forms = document.querySelectorAll('.needs-validation')
        // Bucle sobre ellos y evitar el envío
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>

<script type="text/javascript">
    const PASSWORD_MIN_LENGTH = 8;

    function swalAlerta(icon, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'Entendido'
            });
        } else {
            alert(title + '\n' + text);
        }
    }

    function esPasswordValida(pass) {
        return pass.length >= PASSWORD_MIN_LENGTH;
    }

    function validarParPasswords(pass, repass, passInputId, repassInputId) {
        if (!esPasswordValida(pass)) {
            swalAlerta('warning', 'Contraseña no válida', 'La contraseña debe tener al menos 8 caracteres.');
            document.getElementById(passInputId).focus();
            return false;
        }

        if (pass !== repass) {
            swalAlerta('error', 'Las contraseñas no coinciden', 'Verifica que ambas contraseñas sean iguales e inténtalo de nuevo.');
            document.getElementById(repassInputId).focus();
            return false;
        }

        return true;
    }

    function validarPasswords() {
        const form = document.getElementById('frm');
        const pass = document.getElementById('txtPassword').value.trim();
        const repass = document.getElementById('inputPassword').value.trim();
        const tipo = document.querySelector('input[name="tipo"]:checked');

        if (!tipo) {
            swalAlerta('warning', 'Tipo requerido', 'Selecciona si el usuario es empleado, proveedor, alumno, socio, empresa o master.');
            return false;
        }

        form.classList.add('was-validated');
        if (!form.checkValidity()) {
            swalAlerta('warning', 'Campos incompletos', 'Por favor, completa toda la información requerida.');
            return false;
        }

        if (!validarParPasswords(pass, repass, 'txtPassword', 'inputPassword')) {
            return false;
        }

        form.submit();
        return true;
    }

    function validarPasswords2() {
        const form = document.getElementById('frm2');
        const pass = document.getElementById('txtPassword2').value.trim();
        const repass = document.getElementById('inputPassword2').value.trim();

        form.classList.add('was-validated');
        if (!form.checkValidity()) {
            swalAlerta('warning', 'Campos incompletos', 'Por favor, completa toda la información requerida.');
            return false;
        }

        if (!validarParPasswords(pass, repass, 'txtPassword2', 'inputPassword2')) {
            return false;
        }

        form.submit();
        return true;
    }

    function validarInactivarUsuario() {
        const form = document.getElementById('frmInactivar');

        form.classList.add('was-validated');
        if (!form.checkValidity()) {
            swalAlerta('warning', 'Usuario requerido', 'Selecciona el usuario que deseas inactivar.');
            return false;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Inactivar usuario',
                text: 'El usuario seleccionado ya no podrá iniciar sesión.',
                showCancelButton: true,
                confirmButtonText: 'Sí, inactivar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return true;
        }

        if (confirm('El usuario seleccionado ya no podrá iniciar sesión. ¿Deseas continuar?')) {
            form.submit();
        }

        return true;
    }

    function validarActivarUsuario() {
        const form = document.getElementById('frmActivar');

        form.classList.add('was-validated');
        if (!form.checkValidity()) {
            swalAlerta('warning', 'Usuario requerido', 'Selecciona el usuario que deseas activar.');
            return false;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'question',
                title: 'Activar usuario',
                text: 'El usuario seleccionado podrá iniciar sesión nuevamente.',
                showCancelButton: true,
                confirmButtonText: 'Sí, activar',
                cancelButtonText: 'Cancelar'
            }).then(function(result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return true;
        }

        if (confirm('El usuario seleccionado podrá iniciar sesión nuevamente. ¿Deseas continuar?')) {
            form.submit();
        }

        return true;
    }

    function toggleVisibilidadPassword(inputId, iconSelector) {
        const input = document.getElementById(inputId);
        const icon = document.querySelector(iconSelector);
        if (!input || !icon) return;

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }

    function mostrarPassword() {
        toggleVisibilidadPassword('txtPassword', '#frm .icon');
    }

    function showPassword() {
        toggleVisibilidadPassword('inputPassword', '#frm .icon1');
    }

    function mostrarPassword2() {
        toggleVisibilidadPassword('txtPassword2', '#frm2 .icon2');
    }

    function showPassword2() {
        toggleVisibilidadPassword('inputPassword2', '#frm2 .icon22');
    }
</script>
@endsection

@section('js')
<script>
    function inicializarBuscadoresRegistro() {
        $('.registro-buscador').each(function() {
            const $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }

            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                allowClear: true,
                placeholder: $select.data('placeholder') || 'Buscar...',
                language: {
                    noResults: function() {
                        return 'Sin resultados';
                    },
                    searching: function() {
                        return 'Buscando...';
                    }
                }
            });
        });
    }

    $(document).ready(function() {
        inicializarBuscadoresRegistro();
    });
</script>
@endsection
