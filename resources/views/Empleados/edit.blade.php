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
                            <i class="fas fa-user-pen"></i>
                        </div>
                        <div>
                            <div class="mb-1">
                                <a href="/Empleados" class="text-muted text-decoration-none fs-8">
                                    <i class="fa-solid fa-chevron-left me-1"></i>Empleados
                                </a>
                            </div>
                            <h2 class="mb-0 text-marino fw-bold">Editar Empleado</h2>
                            <p class="text-muted mb-0">Actualiza la información del colaborador</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($obtenerempleado as $empleado)
            <div class="empleado-form-toolbar mb-3">
                <nav id="navbar-empleado-edit" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <ul class="nav nav-pills empleado-step-nav gap-1 mb-0">
                        <li class="nav-item">
                            <a class="nav-link active" href="#paso1"><span>1</span> General</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#paso-contrato"><span>2</span> Contrato</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#paso2"><span>3</span> Contratación</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#paso3"><span>4</span> Salario</a>
                        </li>
                    </ul>
                    <div class="empleado-ingreso-progress-wrap">
                        <div class="d-flex justify-content-between fs-8 text-muted mb-1">
                            <span>Avance</span>
                            <span id="empleadoFormProgressText">0%</span>
                        </div>
                        <div class="progress empleado-ingreso-progress">
                            <div id="empleadoFormProgress" class="progress-bar" role="progressbar"
                                style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </nav>
            </div>

            <form action="{{ route('cambiar', $empleado->idempleado) }}" method="POST"
                enctype="multipart/form-data" class="empleado-form-shell g-3 needs-validation modern-form" novalidate>
                @csrf
                @method('PUT')

                <div id="empleadoFormScroll" class="empleado-form-scroll">
                    <!-- Datos generales-->
                    <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-form-card">
                        <div class="card-body p-4 bg-white rounded-3">
                            <h4 id="paso1" class="empleado-section-title">
                                <i class="fa-solid fa-user text-orange"></i>
                                <span><b class="text-orange">Paso 1.</b> General</span>
                            </h4>

                        <div class="row">
                            <div class="col-lg-9 col-12 order-lg-first order-last mt-2">
                                <div class="row">
                                    <div class="col-md-3 col-12 mt-2">
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Primer Nombre</label>
                                            <input type="text" hidden class="form-control text" name="idnomina"
                                                value="{{ $empleado->idnom }}"required />
                                            <input type="text" class="form-control text" name="primer_nombre"
                                                id="primer_nombre" value="{{ $empleado->primer_nombre }}" minlength="3"
                                                maxlength="20" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12 mt-2">
                                        <div class="form-outline">
                                            <label class="form-label">Segundo Nombre</label>
                                            <input type="text" id="segundo_nombre" name="segundo_nombre"
                                                class="form-control text" value="{{ $empleado->segundo_nombre }}"
                                                minlength="3" maxlength="20" />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12 mt-2">
                                        <div class="form-outline">
                                            <label class="form-label">Apellido Paterno</label>
                                            <input type="text" name="apellido_paterno" id="apellido_paterno"
                                                class="form-control text" value="{{ $empleado->apellido_paterno }}"
                                                minlength="3" maxlength="20" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12 mt-2">
                                        <div class="form-outline">
                                            <label class="form-label">Apellido Materno</label>
                                            <input type="text" name="apellido_materno" id="apellido_materno"
                                                class="form-control text" value="{{ $empleado->apellido_materno }}"
                                                minlength="3" maxlength="20"required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 col-12 mt-2">
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Telefono</label>
                                            <input type="numeric" name="telefono" id="telefono"
                                                class="form-control text" value="{{ $empleado->telefono }}"
                                                minlength="6" maxlength="10" />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-12 mt-2">
                                        <div class="form-outline">
                                            <label class="form-label">Correo</label>
                                            <input type="text" name="correo" id="correo"
                                                class="form-control text" value="{{ $empleado->correo }}" minlength="3"
                                                maxlength="60" />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-12 mt-2">
                                        <!-- Name input -->
                                        <div class="form-outline">
                                            <label class="form-label">Grado de estudio</label>
                                            <input type="text" name="grado_estudio" id="grado_estudio"
                                                class="form-control text" value="{{ $empleado->grado_estudio }}"
                                                minlength="3" maxlength="100" required />
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3 col-12 order-lg-last order-first mt-2">
                                <div class="text-center">
                                    @if ($empleado->foto == '1')
                                        @php($foto = $empleado->nombre_foto)
                                        <img src="{{ asset('Images/Perfil/' . $foto) }}" class="rounded-3 shadow"
                                            style="height: 120px;width: 120px;object-fit: cover;" alt="foto_perfil">
                                    @else
                                        <img src="{{ asset('Images/Perfil/0.jpg') }}" class="rounded-3 shadow"
                                            style="height: 120px;width: 120px;object-fit: cover;" alt="foto_perfil">
                                    @endif
                                </div>

                                <div class="text-center">
                                    <button type="button" class="btn btn-baseColor fs-6_5 mt-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#exampleModal{{ $empleado->idempleado }}">
                                        <i class="fa-solid fa-pen"></i> Editar Foto de Perfil
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <!-- Name input -->
                                <div class="form-outline">
                                    <label class="form-label">Nacionalidad</label>
                                    <input type="text" name="nacionalidad" id="nacionalidad"
                                        class="form-control text" value="{{ $empleado->nacionalidad }}"
                                        minlength="3" maxlength="100" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <!-- Name input -->
                                <div class="form-outline">
                                    <label class="form-label">Ciudad</label>
                                    <select name="ciudad" id="ciudad" class="form-select" required>
                                        @foreach ($varciudades as $obtenerciudades)
                                            @if ($empleado->ciudad == $obtenerciudades->nombre)
                                                <option value="{{ $obtenerciudades->id }}" selected>
                                                    {{ $obtenerciudades->nombre }}</option>
                                            @else
                                                <option value="{{ $obtenerciudades->id }}">
                                                    {{ $obtenerciudades->nombre }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <!-- Email input -->
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Colonia</label>
                                    <input type="text" name="colonia" id="colonia" class="form-control text"
                                        value="{{ $empleado->colonia }}" minlength="3" maxlength="60" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 col-12 mt-2">
                                <!-- Email input -->
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Calle</label>
                                    <input type="text" name="calle" id="calle" class="form-control text"
                                        value="{{ $empleado->calle }}" minlength="3" maxlength="60" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-3 col-12 mt-2">
                                <!-- Email input -->
                                <div class="form-outline">
                                    <label class="form-label">Numero Interior</label>
                                    <input type="text" name="numero_interior" id="numero_interior"
                                        class="form-control text" placeholder="00"
                                        value="{{ $empleado->numero_interior }}" maxlength="10" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-3 col-12 mt-2">
                                <!-- Name input -->
                                <div class="form-outline">
                                    <label class="form-label">Numero Exterior</label>
                                    <input type="text" class="form-control text" name="numero_exterior"
                                        id="numero_exterior" placeholder="00"
                                        value="{{ $empleado->numero_exterior }}" maxlength="10" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-3 col-12 mt-2">
                                <!-- Email input -->
                                <div class="form-outline">
                                    <label class="form-label">Codigo Postal</label>
                                    <input type="text" name="codigo_postal" id="codigo_postal"
                                        class="form-control text" placeholder="00000"
                                        value="{{ $empleado->codigo_postal }}" maxlength="6" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <label class="form-label">Estado Civil</label>
                                <select class="form-select" name="estado_civil" id="estado_civil" required>
                                    <option value="{{ $empleado->estado_civil }}" selected>
                                        {{ $empleado->estado_civil }}</option>
                                    <option value="SOLTERO">SOLTERO</option>
                                    <option value="CASADO">CASADO</option>
                                    <option value="UNION LIBRE">UNION LIBRE</option>
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <!-- Email input -->
                                <div class="form-outline">
                                    <label class="form-label">Sexo</label>
                                    <select class="form-select" id="sexo" name="sexo" required>
                                        <option value="{{ $empleado->sexo }}" selected>{{ $empleado->sexo }}</option>
                                        <option value="M">M</option>
                                        <option value="F">F</option>
                                    </select>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <!-- Email input -->
                                <div class="form-outline">
                                    <label class="form-label">Tipo de Sangre</label>
                                    <input type="text" name="tipo_sangre" id="tipo_sangre"
                                        class="form-control text" value="{{ $empleado->tipo_sangre }}"
                                        maxlength="3" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <!-- Email input -->
                                <div class="form-outline">
                                    <label class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                                        class="form-control" value="{{ $empleado->fecha_nacimiento }}" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Fecha de alta</label>
                                    <input type="date" name="fecha_alta" id="fecha_alta" class="form-control"
                                        value="{{ $empleado->fecha_ingreso }}" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Contacto de Emergencias</label>
                                    <input type="text" name="contacto_emergencias" id="contacto_emergencias"
                                        class="form-control text" value="{{ $empleado->contacto_emergencia }}"
                                        maxlength="50" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Telefono de emergencia </label>
                                    <input type="text" name="telefono_emergencia" id="telefono_emergencia"
                                        class="form-control text" value="{{ $empleado->telefono_emergencia }}"
                                        maxlength="10" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Contrato-->
                    <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-form-card">
                        <div class="card-body p-4 bg-white rounded-3">
                            <h4 id="paso-contrato" class="empleado-section-title">
                                <i class="fa-solid fa-file-contract text-orange"></i>
                                <span><b class="text-orange">Paso 2.</b> Contrato</span>
                            </h4>

                        <div class="row">
                            <div class="col-md-5 col-12 p-5 pt-0 pb-0">
                                <div class="form-outline mb-4 text-start">
                                    <label class="form-label">Tipo Contratacion</label>
                                    <select name="tipo_contratacion" class="form-select" id="tipo_contratacion" onchange="getComboA(this)" required>
                                        <option value="INDEFINIDA" {{ $empleado->tipo_contratacion == 'INDEFINIDA' ? 'selected' : '' }}>INDEFINIDA</option>
                                        <option value="DEFINIDA" {{ $empleado->tipo_contratacion == 'DEFINIDA' ? 'selected' : '' }}>DEFINIDA</option>
                                    </select>
    
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline mb-4 text-start" id="fecha_determinado_wrap" style="display:none;">
                                    <label class="form-label">Fecha de vencimiento de contrato</label>
                                    <input type="date" name="fecha_determinado" id="fecha_determinado" class="form-control"
                                        value="{{ $empleado->fecha_determinado }}" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            
                                <div class="row text-start" id="cont1">
                                    <label class="form-label">Contrato por Tiempo Definido</label>
                                    <a class="btn btn-baseColor fs-7 mb-4"
                                        href="/Empleados/getdownloadContratoDeterminado/{{ $empleado->idempleado }}" target="_blank">
                                        <i class="fa-solid fa-download"></i> Descargar
                                    </a>
                                </div>

                                <div class="row text-start" id="cont2">
                                    <label class="form-label">Contrato por Tiempo Indefinido</label>
                                    <a class="btn btn-baseColor fs-7 mb-4"
                                        href="/Empleados/getdownloadContrato/{{ $empleado->idempleado }}" target="_blank">
                                        <i class="fa-solid fa-download"></i> Descargar
                                    </a>
                                </div>
                            </div>

                            <div class="col-md-7 col-12 text-end p-5 pt-0 pb-0">
                                @if ($empleado->status_contrato == 'A')
                                    <button class="btn btn-primary fs-8 mb-3" data-bs-toggle="modal" type="button" data-bs-target="#modalContrato{{ $empleado->idempleado }}">
                                        <i class="fa-solid fa-pen"></i> Actualizar
                                    </button>
        
                                    <iframe  src="{{ asset('DetallesEmpleados/contratos/' . $empleado->ruta_contrato) }}"
                                        style="width:100%; height:500px;border-bottom: 5px solid #3d3d3d;border-right: 5px solid #3d3d3d" frameborder="0"></iframe>
                                @else
                                    <div class="mb-4">
                                        <div class="row mt-4 justify-content-center">
                                            <div class="modern-file-input" data-input-id="contrato-upload-{{ $empleado->idempleado }}">
                                                <div class="file-input-wrapper" id="wrapper-contrato-upload-{{ $empleado->idempleado }}">
                                                    <div class="file-input-content">
                                                        <div class="file-input-icon">
                                                            <i class="fas fa-cloud-upload-alt"></i>
                                                        </div>
                                                        <p class="file-input-text">Arrastra tu archivo aquí</p>
                                                        <p class="file-input-subtext">o haz clic para seleccionar</p>
                                                        <small class="file-input-subtext d-block mt-2">Solo se admite formato .pdf</small>
                                                    </div>
                                                    <input type="file" name="contrato" id="contrato-upload-{{ $empleado->idempleado }}" class="hidden-file-input" accept=".pdf"/>
                                                </div>
                                                <div class="file-preview" id="preview-contrato-upload-{{ $empleado->idempleado }}">
                                                    <div class="file-preview-item">
                                                        <div class="file-preview-info">
                                                            <div class="file-preview-icon">
                                                                <i class="fas fa-file-pdf"></i>
                                                            </div>
                                                            <div class="file-preview-details">
                                                                <h6 id="filename-contrato-upload-{{ $empleado->idempleado }}"></h6>
                                                                <small id="filesize-contrato-upload-{{ $empleado->idempleado }}"></small>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="file-preview-remove" onclick="removeFile('contrato-upload-{{ $empleado->idempleado }}')">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <div class="progress-bar">
                                                        <div class="progress-fill" id="progress-contrato-upload-{{ $empleado->idempleado }}"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- Contratación-->
                    <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-form-card">
                        <div class="card-body p-4 bg-white rounded-3">
                            <h4 id="paso2" class="empleado-section-title">
                                <i class="fa-solid fa-briefcase text-orange"></i>
                                <span><b class="text-orange">Paso 3.</b> Datos de Contratación</span>
                            </h4>
                        
                        <div class="mb-2 pb-4 border-bottom">
                            <div class="row">
                                <div class="col-md-6 col-4 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Puesto</label>
                                        <select name="puesto" id="puesto" class="form-select" required>

                                            <option value="{{ $empleado->idpuesto }}" selected>{{ $empleado->puesto }}
                                            </option>
                                            @foreach ($varpuestos as $obtenerpuesto)
                                                <option value="{{ $obtenerpuesto->id }}">{{ $obtenerpuesto->nombre }}
                                                </option>
                                            @endforeach

                                        </select>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-4 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Sucursal</label>
                                        <select name="sucursal" id="sucursal" class="form-select" required>
                                            @foreach ($varsucursales as $obtenersucursales)
                                                @if ($obtenersucursales->id == $empleado->idsucursal)
                                                    <option selected value="{{ $obtenersucursales->id }}">
                                                        {{ $obtenersucursales->nombre }}</option>
                                                @else
                                                    <option value="{{ $obtenersucursales->id }}">
                                                        {{ $obtenersucursales->nombre }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                
                                <div class="col-md-6 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">CURP</label>
                                        <input type="text" name="curp" id="curp" class="form-control text"
                                            value="{{ $empleado->curp }}" maxlength="18" required />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">NSS</label>
                                        <input type="text" name="nss" id="nss" class="form-control text"
                                            value="{{ $empleado->nss }}" maxlength="12" required />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                       
                        <div class="mb-2 pb-4 border-bottom">
                            <h6 class="empleado-subsection-title">Datos Fiscales</h6>

                            <div class="row">
                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">RFC</label>
                                        <input type="text" name="rfc" id="rfc" class="form-control text"
                                            value="{{ $empleado->rfc }}" maxlength="13" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>

                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Colonia</label>
                                        <input type="text" name="colonia_f" id="colonia_f" class="form-control text"
                                            value="{{ $empleado->colonia_f }}" maxlength="50" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>


                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Calle</label>
                                        <input type="text" name="calle_f" id="calle_f" class="form-control text"
                                            value="{{ $empleado->calle_f }}" maxlength="50" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">No. Interior</label>
                                        <input type="text" name="no_interior_f" id="no_interior_f" class="form-control text"
                                            value="{{ $empleado->no_interior_f }}" maxlength="10" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>

                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">No. Exterior</label>
                                        <input type="text" name="no_exterior_f" id="no_exterior_f" class="form-control text"
                                            value="{{ $empleado->no_exterior_f }}" maxlength="10"  />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>


                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Codigo Postal</label>
                                        <input type="number" name="codigo_postal_f" id="codigo_postal_f" class="form-control text"
                                            value="{{ $empleado->codigo_postal_f }}" maxlength="11" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-2 pb-2">
                            <h6 class="empleado-subsection-title">Datos Bancarios</h6>

                            <div class="row">
                                <div class="col-md-2 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Banco</label>
                                        <select name="banco" id="banco" class="form-select" required>
                                            @foreach ($varbancos as $obtenerbancos)
                                                @if ($empleado->idbanco == $obtenerbancos->id)
                                                    <option value="{{ $obtenerbancos->id }}" selected>
                                                        {{ $obtenerbancos->nombre }}</option>
                                                @else
                                                    <option value="{{ $obtenerbancos->id }}">{{ $obtenerbancos->nombre }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>

                                <div class="col-md-2 col-6 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">ID Banca</label>
                                        <input type="text" name="idbanca" value="{{$empleado->idbanca}}"
                                            class="form-control text" maxlength="11" />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Numero de Tarjeta</label>
                                        <input type="text" name="numero_tarjeta" id="numero_tarjeta"
                                            class="form-control text" value="{{ $empleado->numero_tarjeta }}"
                                            maxlength="16" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>

                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Numero de Cuenta</label>
                                        <input type="text" name="numero_cuenta" id="numero_cuenta"
                                            class="form-control text" maxlength="18"
                                            value="{{ $empleado->numero_cuenta }}" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @include('Empleados.partials.horarios-semana', [
                            'vardias' => $vardias,
                            'varhorarios' => $varhorarios,
                            'horariosEmpleado' => $horariosEmpleado,
                        ])
                        </div>
                    </div>


                    <!-- Salario -->
                    <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-form-card">
                        <div class="card-body p-4 bg-white rounded-3">
                            <h4 id="paso3" class="empleado-section-title">
                                <i class="fa-solid fa-coins text-orange"></i>
                                <span><b class="text-orange">Paso 4.</b> Salario</span>
                            </h4>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Fecha de ingreso a IMSS</label>
                                    <input type="date" name="fecha_ingreso_imss" id="fecha_ingreso_imss"
                                        class="form-control" value="{{ $empleado->fecha_ingreso_imss }}"
                                        maxlength="12" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Empresa</label>
                                    <select class="form-select form-select mb-3" id="cmbempresas" name="cmbempresas"
                                        required>
                                        @foreach ($varempresas as $obtenerempresa)
                                            @if ($empleado->id == $obtenerempresa->id)
                                                @if ($obtenerempresa->efectivo == 1)
                                                    <option value="{{ $obtenerempresa->id }}" selected>Efectivo -
                                                        {{ $obtenerempresa->nombre_empresa }}</option>
                                                @else
                                                    <option value="{{ $obtenerempresa->id }}" selected>
                                                        {{ $obtenerempresa->nombre_empresa }}</option>
                                                @endif
                                            @else
                                                @if ($obtenerempresa->efectivo == 1)
                                                    <option value="{{ $obtenerempresa->id }}">Efectivo -
                                                        {{ $obtenerempresa->nombre_empresa }}</option>
                                                @else
                                                    <option value="{{ $obtenerempresa->id }}">
                                                        {{ $obtenerempresa->nombre_empresa }}</option>
                                                @endif
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Zona</label>
                                    <select class="form-select mb-3" name="zona" id="zona" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="ZFN" {{ ($empleado->zona ?? '') === 'ZFN' ? 'selected' : '' }}>
                                            Zona Libre de la Frontera Norte (ZFN)
                                        </option>
                                        <option value="RP" {{ ($empleado->zona ?? '') === 'RP' ? 'selected' : '' }}>
                                            Resto del país (RP)
                                        </option>
                                    </select>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Sueldo Mensual</label>
                                    <input type="text" name="salario_bruto" id="salario_bruto"
                                        class="form-control text" value="{{ $empleado->salario_bruto }}"
                                        maxlength="10" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2" id="salario_fijo">
                                <div class="form-outline">
                                    <label class="form-label">Salario Diario</label>
                                    <input type="text" name="salario_fijo" id="salario_fijo"
                                        class="form-control text" value="{{ $empleado->salario_fijo }}" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            @unless($visorFiscalActivo)
                             <div class="col-md-4 col-12 mt-2" id="excedente">
                                <label class="form-label">Salario Diario Excedente</label>
                                <input type="text" name="excedente" id="excedente" class="form-control text"
                                    value="{{ $empleado->excedente }}" maxlength="8" required />
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                            @else
                            <input type="hidden" name="excedente" value="{{ $empleado->excedente }}">
                            @endunless

                           {{-- <div class="col-md-3 col-12 mt-2" id="Efectivo">
                                <label class="form-label">Salario Diario Efectivo</label>
                                <input type="text" name="efectivo" id="efectivo" class="form-control text"
                                    value="{{ $empleado->efectivo }}" maxlength="8" required />
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div> --}}
                        </div>

                        <div class="empleado-subsection-title mt-4 mb-3">Crédito Infonavit</div>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2" id="tipo_descuento_infonavit">
                                <div class="form-outline">
                                    <label class="form-label">Tipo de descuento</label>
                                    <select class="form-select" name="tipo_infonavit">
                                        @foreach ($vartipodescinfo as $obtenertipo)
                                            @if ($empleado->idinfonavit == $obtenertipo->id)
                                                <option value="{{ $empleado->idinfonavit }}" selected>
                                                    {{ $empleado->nombreinfonavit }}</option>
                                            @else
                                                <option value="{{ $obtenertipo->id }}">{{ $obtenertipo->Nombre }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2" id="numero_credito_infonavit">
                                <div class="form-outline">
                                    <label class="form-label">Numero de crédito</label>
                                    <input type="text" name="numero_credito_infonavit" class="form-control text"
                                        value="{{ $empleado->numero_credito_infonavit }}" maxlength="10" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2" id="factor_sua">
                                <div class="form-outline">
                                    <label class="form-label">Cuota fija</label>
                                    <input type="text" name="factor_sua" class="form-control text"
                                        value="{{ $empleado->factor_sua }}" maxlength="8" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            {{-- <div class="col-md-3 col-12 mt-2" id="descuento_quincenal">
                                <div class="form-outline">
                                    <label class="form-label">Descuento quincenal</label>
                                    <input type="text" name="descuento_quincenal" class="form-control text"
                                        value="{{ $empleado->descuento_quincenal }}" maxlength="8" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div> --}}
                        </div>
                        </div>
                    </div>
                </div>

                <div class="empleado-form-footer">
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-baseColor px-4" type="submit">
                            <i class="fa-solid fa-check"></i> Guardar cambios
                        </button>
                    </div>
                </div>
            </form>

                <!-- Modal Contrato -->
                <div class="modal fade" id="modalContrato{{ $empleado->idempleado }}" tabindex="-1"
                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <form action="/Empleados/contratoSubir/{{ $empleado->idempleado }}" method="POST"
                             enctype="multipart/form-data"  class="g-3 needs-validation form msform" novalidate>
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h6 class="modal-title" id="exampleModalLabel">Actualizar Contrato de Empleado</h6>

                                    <button type="button" class="btn-close" data-bs-dismiss="modal"  aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="mb-2 container">
                                        <div class="modern-file-input" data-input-id="contrato-modal-{{ $empleado->idempleado }}">
                                            <div class="file-input-wrapper" id="wrapper-contrato-modal-{{ $empleado->idempleado }}">
                                                <div class="file-input-content">
                                                    <div class="file-input-icon">
                                                        <i class="fas fa-cloud-upload-alt"></i>
                                                    </div>
                                                    <p class="file-input-text">Arrastra tu archivo aquí</p>
                                                    <p class="file-input-subtext">o haz clic para seleccionar</p>
                                                    <small class="file-input-subtext d-block mt-2">Solo se admite formato .pdf</small>
                                                </div>
                                                <input type="file" name="contrato" id="contrato-modal-{{ $empleado->idempleado }}" class="hidden-file-input" required accept=".pdf"/>
                                            </div>
                                            <div class="file-preview" id="preview-contrato-modal-{{ $empleado->idempleado }}">
                                                <div class="file-preview-item">
                                                    <div class="file-preview-info">
                                                        <div class="file-preview-icon">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </div>
                                                        <div class="file-preview-details">
                                                            <h6 id="filename-contrato-modal-{{ $empleado->idempleado }}"></h6>
                                                            <small id="filesize-contrato-modal-{{ $empleado->idempleado }}"></small>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="file-preview-remove" onclick="removeFile('contrato-modal-{{ $empleado->idempleado }}')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" id="progress-contrato-modal-{{ $empleado->idempleado }}"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end mb-3 p-3 pt-0 border-0">
                                    <button type="submit" class="btn btn-baseColor fs-6_5 rounded-1"><i
                                            class="fa-solid fa-check"></i> Guardar cambios
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal Foto -->
                <div class="modal fade" id="exampleModal{{ $empleado->idempleado }}" tabindex="-1"
                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <form action="/Empleados/edit/fotoPerfil/{{ $empleado->idempleado }}" method="POST"
                            enctype="multipart/form-data"  class="g-3 needs-validation form msform" novalidate>
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header border-0">
                                    <h6 class="modal-title" id="exampleModalLabel">Nueva Foto de Perfil</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="modern-file-input" data-input-id="foto-perfil-{{ $empleado->idempleado }}">
                                        <div class="file-input-wrapper" id="wrapper-foto-perfil-{{ $empleado->idempleado }}">
                                            <div class="file-input-content">
                                                <div class="file-input-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <p class="file-input-text">Arrastra tu foto aquí</p>
                                                <p class="file-input-subtext">o haz clic para seleccionar</p>
                                                <small class="file-input-subtext d-block mt-2">Formatos: .png, .jpg, .jpeg</small>
                                            </div>
                                            <input type="file" name="foto" id="foto-perfil-{{ $empleado->idempleado }}" class="hidden-file-input" required accept=".png,.jpg,.jpeg"/>
                                        </div>
                                        <div class="file-preview" id="preview-foto-perfil-{{ $empleado->idempleado }}">
                                            <div class="file-preview-item">
                                                <div class="file-preview-info">
                                                    <div class="file-preview-icon">
                                                        <i class="fas fa-file-image"></i>
                                                    </div>
                                                    <div class="file-preview-details">
                                                        <h6 id="filename-foto-perfil-{{ $empleado->idempleado }}"></h6>
                                                        <small id="filesize-foto-perfil-{{ $empleado->idempleado }}"></small>
                                                    </div>
                                                </div>
                                                <button type="button" class="file-preview-remove" onclick="removeFile('foto-perfil-{{ $empleado->idempleado }}')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="progress-bar">
                                                <div class="progress-fill" id="progress-foto-perfil-{{ $empleado->idempleado }}"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="container">
                                    <div class="row p-3">
                                        <button type="button" class="col btn btn-secondary fs-8 rounded-1 m-2 mt-0"  data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
                                        <button type="submit" class="col-9 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const progressBar = document.getElementById('empleadoFormProgress');
            const progressText = document.getElementById('empleadoFormProgressText');
            const steps = ['paso1', 'paso-contrato', 'paso2', 'paso3'];
            const toolbar = document.querySelector('.empleado-form-toolbar');

            const updateProgress = () => {
                const scrollTop = window.scrollY || document.documentElement.scrollTop;
                const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
                const percent = scrollHeight > 0 ? Math.min(100, Math.round((scrollTop / scrollHeight) * 100)) : 0;

                if (progressBar) {
                    progressBar.style.width = percent + '%';
                    progressBar.setAttribute('aria-valuenow', percent.toString());
                }

                if (progressText) {
                    progressText.textContent = percent + '%';
                }

                const offset = (toolbar ? toolbar.offsetHeight : 0) + 24;

                steps.forEach((stepId, index) => {
                    const section = document.getElementById(stepId);
                    const navLink = document.querySelector(`#navbar-empleado-edit a[href="#${stepId}"]`);
                    const nextStep = steps[index + 1] ? document.getElementById(steps[index + 1]) : null;

                    if (!section || !navLink) {
                        return;
                    }

                    const sectionTop = section.getBoundingClientRect().top + scrollTop;
                    const nextTop = nextStep
                        ? nextStep.getBoundingClientRect().top + scrollTop
                        : document.documentElement.scrollHeight;
                    const isActive = scrollTop >= sectionTop - offset && scrollTop < nextTop - offset;

                    navLink.classList.toggle('active', isActive);
                });
            };

            document.querySelectorAll('#navbar-empleado-edit a[href^="#"]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    const targetId = link.getAttribute('href');
                    const target = targetId ? document.querySelector(targetId) : null;

                    if (!target) {
                        return;
                    }

                    event.preventDefault();
                    const offset = (toolbar ? toolbar.offsetHeight : 0) + 16;
                    const scrollTop = window.scrollY || document.documentElement.scrollTop;
                    const targetTop = target.getBoundingClientRect().top + scrollTop - offset;

                    window.scrollTo({
                        top: targetTop,
                        behavior: 'smooth'
                    });
                });
            });

            window.addEventListener('scroll', updateProgress, { passive: true });
            updateProgress();
        });
    </script>

    <script>
        function toggleContratacionFields() {
            var tipo = $('#tipo_contratacion').val();
            var fechaInput = $('#fecha_determinado');

            if (tipo === 'DEFINIDA') {
                $('#cont1').show();
                $('#cont2').hide();
                $('#fecha_determinado_wrap').show();
                fechaInput.prop('required', true);
            } else {
                $('#cont1').hide();
                $('#cont2').show();
                $('#fecha_determinado_wrap').hide();
                fechaInput.prop('required', false);
            }
        }

        $(document).ready(function() {
            toggleContratacionFields();
        });

        function getComboA(selectObject) {
            toggleContratacionFields();
        }
    </script>
    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/files.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.modal').forEach(function(modal) {
            if (!modal.querySelector('.modern-file-input')) return;
            modal.addEventListener('shown.bs.modal', function() {
                if (typeof bindModernFileInputs === 'function') {
                    bindModernFileInputs(modal);
                }
            });
        });
    });
    </script>
@endsection
