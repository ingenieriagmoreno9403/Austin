@extends('layouts.app')
@section('content')

    <div class="container-fluid format_page">
        <div class="row">
            <div class="center">
                <h3 class="mt-1 animate__animated animate__backInLeft">Editar Empleado</h3>
            </div>
        </div>

        @foreach ($obtenerempleado as $empleado)
            <div data-bs-spy="scroll" data-bs-target="#navbar-example" data-bs-offset="0" class="scrollspy-example"
                tabindex="0">

                <form action="{{ route('cambiar', $empleado->idempleado) }}" method="POST"
                    enctype="multipart/form-data" class="g-3 needs-validation form" novalidate>
                    @csrf
                    @method('PUT')
                    <!-- Datos generales-->
                    <div class="bg-body rounded-2 p-4 mb-4">
                        <div class="row">
                            <h4 class="col-md-2" id="paso1"><b class="fs-3 text-orange">1. </b> General</h4>
                        </div>

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
                    </div>

                    <!-- Contrato-->
                    <div class="bg-body rounded-2 p-4 mb-4 text-center">
                        @if ($empleado->status_contrato == 'A')
                            <h5>Contrato de Empleado <button class="btn border-0 fs-8" data-bs-toggle="modal"
                                    type="button" data-bs-target="#modalContrato{{ $empleado->idempleado }}"><i
                                        class="fa-solid fa-pen text-primary"></i></button></h5>
                            <iframe src="{{ asset('DetallesEmpleados/contratos/' . $empleado->ruta_contrato) }}"
                                style="width:100%; height:500px;" frameborder="0"></iframe>
                        @else
                            <div class="row mb-4">
                                <h5>Contrato de Empleado
                                    <a href="/Empleados/getdownloadContrato/{{ $empleado->idempleado }}"
                                        class="btn text-primary push rounded-5 fs-8 border-0"><i
                                            class="fa-solid fa-download"></i> Descargar</a>
                                </h5>

                                <label for="file1" class="drop-container2">
                                    <span class="drop-title">Suelte archivo aquí</span>
                                    ó
                                    <input class="validaPDF" type="file" id="my-fileContra" type="file"
                                        name="contrato">
                                </label>
                                <spam class="fs-8 text-secondary">Recuerde que solo se admite el formato ".pdf"</spam>
                            </div>
                        @endif
                    </div>

                    <!-- Contratación-->
                    <div class="bg-body rounded-2 p-4 mb-4">
                        <div class="row">
                            <h4 class="col-md-5" id="paso2"><b class="fs-3 text-orange">2.</b> Datos de
                                Contratación</h4>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
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

                            <div class="col-md-6 col-12 mt-2">
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
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">RFC</label>
                                    <input type="text" name="rfc" id="rfc" class="form-control text"
                                        value="{{ $empleado->rfc }}" maxlength="13" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">CURP</label>
                                    <input type="text" name="curp" id="curp" class="form-control text"
                                        value="{{ $empleado->curp }}" maxlength="18" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">NSS</label>
                                    <input type="text" name="nss" id="nss" class="form-control text"
                                        value="{{ $empleado->nss }}" maxlength="12" required />
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

                        <br>
                        <hr>

                        <h6 class="text-center">Datos Bancarios</h6>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
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


                    <!-- Salario -->
                    <div class="bg-body rounded-2 p-4 mb-4">
                        <div class="row">
                            <h4 id="paso3"><b class="fs-3 text-orange">3.</b> Salario</h4>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Fecha de ingreo a IMSS</label>
                                    <input type="date" name="fecha_ingreso_imss" id="fecha_ingreso_imss"
                                        class="form-control" value="{{ $empleado->fecha_ingreso_imss }}"
                                        maxlength="12" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-12 mt-2">
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
                        </div>

                        <div class="row">
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Sueldo Mensual</label>
                                    <input type="text" name="salario_bruto" id="salario_bruto"
                                        class="form-control text" value="{{ $empleado->salario_bruto }}"
                                        maxlength="10" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-3 col-12 mt-2" id="salario_fijo">
                                <div class="form-outline">
                                    <label class="form-label">Salario Diario</label>
                                    <input type="text" name="salario_fijo" id="salario_fijo"
                                        class="form-control text" value="{{ $empleado->salario_fijo }}" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-3 col-12 mt-2" id="excedente">
                                <label class="form-label">Salario Diario Excedente</label>
                                <input type="text" name="excedente" id="excedente" class="form-control text"
                                    value="{{ $empleado->excedente }}" maxlength="8" required />
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-3 col-12 mt-2" id="Efectivo">
                                <label class="form-label">Salario Diario Efectivo</label>
                                <input type="text" name="efectivo" id="efectivo" class="form-control text"
                                    value="{{ $empleado->efectivo }}" maxlength="8" required />
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>

                        <div class="row text-center mt-4 mb-2">
                            <hr />
                            <div class="col">
                                <h6>Crédito Infonavit </h6>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 col-12 mt-2" id="tipo_descuento_infonavit">
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

                            <div class="col-md-3 col-12 mt-2" id="numero_credito_infonavit">
                                <div class="form-outline">
                                    <label class="form-label">Numero de crédito</label>
                                    <input type="text" name="numero_credito_infonavit" class="form-control text"
                                        value="{{ $empleado->numero_credito_infonavit }}" maxlength="10" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-3 col-12 mt-2" id="factor_sua">
                                <div class="form-outline">
                                    <label class="form-label">Factor SUA</label>
                                    <input type="text" name="factor_sua" class="form-control text"
                                        value="{{ $empleado->factor_sua }}" maxlength="8" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-3 col-12 mt-2" id="descuento_quincenal">
                                <div class="form-outline">
                                    <label class="form-label">Descuento quincenal</label>
                                    <input type="text" name="descuento_quincenal" class="form-control text"
                                        value="{{ $empleado->descuento_quincenal }}" maxlength="8" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Guardar-->
                    <div class="mb-5 text-center" style="padding:10px;">
                        <div>
                            <button class="btn btn-primary rounded-5 fs-6_5 push" type="submit">&nbsp;<i
                                    class="fa-solid fa-check"></i>&nbsp;Guardar cambios&nbsp;</button>
                            <br><br>
                        </div>
                    </div>
                </form>

                <!-- Modal Contrato -->
                <div class="modal fade" id="modalContrato{{ $empleado->idempleado }}" tabindex="-1"
                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <form action="/Empleados/contratoSubir/{{ $empleado->idempleado }}" method="POST"
                            class="form" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title" id="exampleModalLabel">Contrato de Empleado <a
                                            href="/Empleados/getdownloadContrato/{{ $empleado->idempleado }}"
                                            class="btn text-primary push rounded-5 fs-6_5 border-0"><i
                                                class="fa-solid fa-download"></i> Descargar</a></h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-2 container">
                                        <label for="file1" class="drop-container2">
                                            <span class="drop-title">Suelte archivo aquí</span>
                                            ó
                                            <input class="validaPDF" type="file" id="my-fileContra"
                                                type="file" name="contrato">
                                        </label>
                                        <spam class="fs-8 text-secondary">Recuerde que solo se admite el formato ".pdf"
                                        </spam>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center mb-3">
                                    <button type="submit" class="btn btn-primary fs-6_5 rounded-5"><i
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
                        <form action="/Empleados/edit/fotoPerfil/{{ $empleado->idempleado }}" method="POST" class="form"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h6 class="modal-title" id="exampleModalLabel">Nueva Foto de Perfil</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <label for="file1" class="drop-container2">
                                        <span class="drop-title">Suelte foto aquí</span>
                                        ó
                                        <input class="validaFOTO" type="file" id="my-foto" type="file"
                                            name="foto">
                                    </label>
                                    <spam class="fs-8 text-secondary">Recuerde que solo se admite el formato ".png,
                                        .jpg, jpeg"</spam>
                                </div>
                                <hr>
                                <div class="text-center mb-3">
                                    <button type="submit" class="btn btn-primary fs-6_5 rounded-5"><i
                                            class="fa-solid fa-check"></i> Guardar cambios</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/validaPDF.js') }}"></script>
@endsection
