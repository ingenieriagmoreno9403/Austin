@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('warningSaldo'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡Saldo Insuficiente!", text: "No se a efectuado la acción, necesita un mayor saldo para hacer la transferencia"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('warningFecha'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "info",title: "¡No existen coincidencias!", text: "Pruebe como otro rango de fechas"});';
            echo '</script>'; 
    @endphp
@endif


<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Cajas</h2>
                        <p class="text-muted mb-0">Gestión y control de cajas, con visualización de detalle</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if ($permisos1 == 'nueva_caja')
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            <i class="fa-solid fa-plus"></i> Nueva Caja
                        </button>
                    @else
                        <button class="btn btn-baseColor" disabled type="button">
                            <i class="fa-solid fa-plus"></i> Nueva Caja
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="tableCaja">
                <thead>
                    <tr>
                        <th class="text-truncate">Nombre</th>
                        <th class="text-truncate">Estado</th>
                        <th class="text-truncate">Responsable</th>
                        <th class="text-truncate">Saldo Inicial</th>
                        <th class="text-truncate">Saldo Actual</th>
                        <th class="text-truncate">Tipo</th>
                        <th class="text-truncate">Sucursal</th>
                        <th class="text-truncate">Fecha Alta</th>
                        <th class="text-truncate">Opciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($varCajas as $datos)
                        <tr class="boder-sec">
                            <td>{{ $datos->nombre }}</td>

                            <td class="text-truncate">
                                @if ($datos->status == 'A')
                                    <span class="badge badge-success-dark fs-9">Activa</span>
                                @endif
                                @if ($datos->status == 'I')
                                    <span class="badge badge-secondary-dark fs-9">Inactiva</span>
                                @endif
                                @if ($datos->status == 'C')
                                    <span class="badge badge-danger-dark fs-9">Cancelada</span>
                                @endif
                            </td>

                            <td>{{ $datos->nombreResponsable }}</td>
                            <td class="text-center text-truncate">${{ number_format($datos->saldo_inicial,2) }}</td>
                            <td class="text-center text-truncate">${{ number_format($datos->saldo_actual,2) }}</td>
                            
                            <td class="text-center text-truncate">
                                @if ($datos->tipo == 'caja_chica')
                                    <span class="badge badge-orange">Chica</span>
                                @endif

                                @if ($datos->tipo == 'caja')
                                    <span class="badge badge-success">Caja</span>
                                @endif
                            </td>

                            <td class="text-center text-start">{{ $datos->nombre_sucursal }}</td>
                            <td class="text-center text-truncate">{{ $datos->fecha_alta }}</td>

                            <td class="text-start text-truncate">
                                @if ($permisos5 == 'asignar_responsable_caja')
                                    @if ($datos->status != 'C')
                                        @if ($datos->responsable_status == 'N')
                                            <button class="btn btn-baseColor border-0 m-0" data-bs-toggle="modal"  data-bs-target="#modalDocumentos{{ $datos->id }}">
                                                <i class="fa-solid fa-pause fs-8"></i>
                                            </button> 
                                        @else
                                            <button class="btn btn-baseColor border-0 m-0" data-bs-toggle="modal" data-bs-target="#modalVer{{ $datos->id }}">
                                                <i class="fa-solid fa-file fs-8"></i>
                                            </button> 
                                        @endif
                                    @else
                                        <button class="btn btn-baseColor border-0 m-0" disabled>
                                            <i class="fa-solid fa-file fs-8"></i>
                                        </button> 
                                    @endif
                                @else
                                    <button class="btn btn-baseColor border-0 m-0" disabled>
                                        <i class="fa-solid fa-file fs-8"></i>
                                    </button> 
                                @endif

                                @if ($permisos2 == 'editar_caja')
                                    @if ($datos->status != 'C')
                                        <button class="btn btn-primary border-0 m-0" type="button" data-bs-toggle="modal"  data-bs-target="#modalEditar{{ $datos->id }}">
                                            <i class="fa-solid fa-pen fs-8"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-primary border-0 m-0" type="button"  disabled>
                                            <i class="fa-solid fa-pen fs-8"></i>
                                        </button>
                                    @endif
                                @else
                                    <button class="btn btn-primary border-0 m-0" type="button" disabled>
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                @endif

                                @if ($permisos3 == 'eliminar_caja')
                                    @if ($datos->status != 'C')
                                        <button class="btn btn-danger m-0" type="button" data-bs-toggle="modal" data-bs-target="#ModalCancel{{ $datos->id }}">
                                            <i class="fa-solid fa-ban fs-8"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-secondary m-0" type="button" disabled>
                                            <i class="fa-solid fa-ban fs-8"></i>
                                        </button>
                                    @endif
                                @else
                                    <button class="btn btn-secondary m-0" type="button" disabled>
                                        <i class="fa-solid fa-ban fs-8"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2"></th>
                        <th>Subtotal</th>
                        <th></th>
                        <th></th>
                        <th colspan="5"></th>
                    </tr>
                    <tr>
                        <th colspan="2"></th>
                        <th>Total</th>
                        <th></th>
                        <th></th>
                        <th colspan="5"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @foreach ($varCajas as $datos)
        {{-- subir documentacion --}}
        <div class="modal fade" id="modalDocumentos{{ $datos->id }}" tabindex="-1"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-dark" id="exampleModalLabel">Completar
                            Documentación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form
                        action="/CatalogoGeneral/Cajas/subirDocumentos/{{ $datos->id }}/{{ $datos->nombre_sucursal }}/{{ $datos->nombre }}"
                        method="POST" enctype="multipart/form-data"
                        class="form g-3 needs-validation text-start" novalidate>
                        @csrf
                        <div class="modal-body">

                            <div class="row mb-2 p-2">
                                <div class="col-lg-2 mb-2 start-center">
                                    <img src="{{ asset('Images/Perfil/' . $datos->nombre_foto) }}"
                                        style="height:100px;width:100px;border-radius:100px;object-fit: cover;"
                                        alt="{{ $datos->nombreResponsable }}">
                                </div>

                                <div class="col-lg-5 mb-2">
                                    <h6 class="text-dark text-truncate">
                                        {{ $datos->nombreResponsable }}</h6>
                                    <p class="text-dark fs-8 fw-normal text-truncate">
                                        Puesto: {{ $datos->puesto }} </br>
                                        Sucursal: <b class="text-secondary">{{ $datos->sucursalEmpleado }}</b>
                                    </p>
                                </div>

                                <div class="col card p-3">
                                    <h6 class="text-dark fs-8"> 1. Carta Responsiva&nbsp;
                                        &nbsp; <a
                                            href="/CatalogoGeneral/Cajas/Cartaresponsiva/{{ $datos->id }}/{{ $datos->tipo }}/{{ $datos->nombreResponsable }}/{{ $datos->idempleado }}/{{ $datos->nombre_empresa }}/{{ $datos->nombre }}"
                                            class="btn text-orange  fs-8 rounded-4  border-0 text-truncate"><i
                                                class="fa-solid fa-download"></i> Descargar</a>
                                    </h6>
                                    <h6 class="text-dark fs-8"> 2. Políticas&nbsp; &nbsp; <a
                                            href="/CatalogoGeneral/Cajas/Politicas/{{ $datos->tipo }}"
                                            class="btn text-orange  fs-8 rounded-4 border-0 text-truncate"><i
                                                class="fa-solid fa-download"></i> Descargar</a>
                                    </h6>
                                </div>
                            </div>

                            
                            <div class="row  p-2 m-2">
                                <h6 class="text-start">Documentación</h6>

                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <label for="file1" class="drop-container2 mt-2">
                                            <span class="drop-title2">Subir Carta
                                                Responsiva</span>
                                                <small>Archivo importado PDF</small>
                                            <input type="file" name="cartaResponsiva" class="validaPDF" required>
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </label>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <label for="file1" class="drop-container2 mt-2">
                                            <span class="drop-title2">Subir Pagaré</span>
                                            <small>Archivo importado PDF</small>
                                            <input type="file" name="pagare"class="validaPDF" required>
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="container">
                            <div class="row justify-content-center p-3">
                                <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ver documentacion --}}
        <div class="modal fade" id="modalVer{{ $datos->id }}" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-dark" id="exampleModalLabel">Perfil de
                            Cajera</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row mb-2 p-2">
                            <div class="col-md-2 mb-2 start-center">
                                <img src="{{ asset('Images/Perfil/' . $datos->nombre_foto) }}"
                                    style="height:100px;width:100px;border-radius:100px;object-fit: cover;"
                                    alt="{{ $datos->nombreResponsable }}">
                            </div>

                            <div class="col-md-5 mb-2 text-start">
                                <h6 class="text-dark text-truncate">
                                    {{ $datos->nombreResponsable }}</h6>
                                <p class="text-dark fs-8 fw-normal text-truncate">
                                    Puesto: {{ $datos->puesto }} </br>
                                    Sucursal: <b class="text-secondary">{{ $datos->sucursalEmpleado }}</b>
                                </p>
                            </div>

                            <div class="col card p-3 text-start">
                                <h6 class="text-dark fs-8"> 1. Carta Responsiva&nbsp; &nbsp; <a
                                        href="/CatalogoGeneral/Cajas/Cartaresponsiva/{{ $datos->id }}/{{ $datos->tipo }}/{{ $datos->nombreResponsable }}/{{ $datos->idempleado }}/{{ $datos->nombre_empresa }}/{{ $datos->nombre }}"
                                        class="btn text-orange  fs-8 rounded-4 border-0 text-truncate"><i
                                            class="fa-solid fa-download"></i> Descargar</a>
                                </h6>
                                <h6 class="text-dark fs-8"> 2. Políticas&nbsp; &nbsp; <a
                                        href="/CatalogoGeneral/Cajas/Politicas/{{ $datos->tipo }}"
                                        class="btn text-orange  fs-8 rounded-4 border-0 text-truncate"><i
                                            class="fa-solid fa-download"></i> Descargar</a>
                                </h6>
                            </div>
                        </div>

                        
                        <div class="row card rounder-4 p-2 m-2 text-start ">
                            <h6 class="text-start">Documentación</h6>

                            <div class="row">
                                <div class="col">
                                    <h6 class="text-dark fw-normal fs-8">1. Carta Responsiva Firmada &nbsp;
                                        &nbsp; <a target="_blank"
                                            class="btn btn-baseColor-light fs-8"
                                            href="{{ asset('Tesoreria/Responsivas/' . $datos->ruta_comprobante_carta) }}"><i
                                                class="fa-solid fa-eye"></i> Ver</a></h6>
                                    <h6 class="text-dark fw-normal fs-8">2. Pagaré Firmado &nbsp; &nbsp; <a target="_blank"
                                            class="btn btn-baseColor-light fs-8"
                                            href="{{ asset('Tesoreria/Responsivas/' . $datos->ruta_comprobante_pagare) }}"><i
                                                class="fa-solid fa-eye"></i> Ver</a></h6>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar Caja -->
        <div class="modal fade" id="modalEditar{{ $datos->id }}" tabindex="-1"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-dark" id="exampleModalLabel">Editar Caja
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form action="/CatalogoGeneral/Cajas/EditarCaja/{{ $datos->id }}" method="POST"
                        enctype="multipart/form-data" class="form g-3 needs-validation mt-1 text-start" novalidate>
                        @csrf
                        <div class="modal-body">
                            <div class="row mb-1">
                                <div class="col-12">
                                    <div class="form-outline mb-2">
                                        <label class="form-label text-dark fs-8" for="form8Example4">Nombre de
                                            Caja</label>
                                        <input type="text" class="form-control text" name="nombre"
                                            id="nombre" maxlength="50" value="{{ $datos->nombre }}" required />
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
                                <div class="col-lg-6 col-12">
                                    <div class="form-outline mb-2">
                                        <label class="form-label text-dark fs-8" for="form8Example4">Fecha de
                                            Alta</label>
                                        <input type="date"  value="{{$datos->fecha_alta}}" class="form-control" name="fecha_alta" id="fecha_alta"
                                            required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>


                                <div class="col-lg-6 col-12">
                                    <div class="form-outline mb-2">
                                        <label class="form-label text-dark fs-8" for="form8Example4">Sucursal</label>
                                        <select class="form-select" name="sucursal" id="sucursal" required>
                                            @foreach ($varsucursales as $sucursal)
                                                @if ($datos->id_sucursal == $sucursal->id)
                                                    <option value="{{ $sucursal->id }}" selected>
                                                        {{ $sucursal->nombre }}
                                                    </option>
                                                @else
                                                    <option value="{{ $sucursal->id }}">
                                                        {{ $sucursal->nombre }}</option>
                                                @endif
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

                                <div class="col-12">
                                    <div class="form-outline text-start">
                                        <label class="form-label text-dark fs-8" for="form8Example4">Caja chica
                                        </label>
                                        <div class="form-check form-switch">
                                            @if ($datos->tipo == 'caja_chica')
                                                <input class="form-check-input"
                                                    style="width: 45px!important;height: 18px!important;"
                                                    type="checkbox" value="caja_chica" role="switch"
                                                    name="tipoCaja" id="tipoCaja" checked>
                                            @else
                                                <input class="form-check-input"
                                                    style="width: 45px!important;height: 18px!important;"
                                                    type="checkbox" value="caja_chica" role="switch"
                                                    name="tipoCaja" id="tipoCaja">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="container">
                            <div class="row justify-content-center p-3">
                                <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Cancelar Caja -->
        <div class="modal fade" id="ModalCancel{{ $datos->id }}" tabindex="-1"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="exampleModalLabel">Cancelación de Caja
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <form action="/CatalogoGeneral/Cajas/EliminarCaja/{{ $datos->id }}" method="POST"
                        class="form g-3 needs-validation mt-1 text-start" novalidate>
                        @csrf
                        <div class="modal-body">
                            <h6 class="text-break">¿Esta seguro de "<i class="text-danger fa-solid fa-ban"></i>
                                Cancelar" la caja
                                {{ $datos->nombre }}?</h6>
                            <span class="text-break">Caja de {{ $datos->nombre_sucursal }} con
                                saldo inicial de $ {{ $datos->saldo_inicial }} y un saldo
                                actual de $ {{ $datos->saldo_actual }}</span>

                            <div class="row mt-4 mb-0">
                                <div class="col">
                                    <div class="form-floating">
                                        <textarea class="form-control fs-8 text" placeholder="Leave a comment here" name="descripcion"
                                            id="floatingTextarea2" style="height: 100px" maxlength="250" required></textarea>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                        <label for="floatingTextarea2">Descripción</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="container">
                           <div class="row justify-content-center p-3">
                                <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Modal Nueva Caja -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="exampleModalLabel">Nueva Caja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="/CatalogoGeneral/Cajas/NuevaCaja" method="POST" enctype="multipart/form-data"
                    class="g-3 form needs-validation mt-1" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-6 col-12 p-2">
                                    <div class="row mb-2">
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Nombre de Caja</label>
                                            <input type="text" class="form-control text" name="nombre"
                                                id="nombre" maxlength="50" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Fecha de Alta</label>
                                            <input type="date" class="form-control" name="fecha_alta"
                                                id="fecha_alta" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="form-outline">
                                            <label class="form-label text-dark fs-8"
                                                for="form8Example4">Responsable</label>
                                            <select class="form-select select2-responsable-caja" name="responsable" id="responsable" required>
                                                <option value="" selected>Selecciona...</option>
                                                @foreach ($varusers as $users)
                                                    <option value="{{ $users->id_empleado }}">
                                                        {{ $users->Nombre }}
                                                        @if ($users->puesto)
                                                            - {{ $users->puesto }}
                                                        @endif
                                                        @if ($users->name)
                                                            ({{ $users->name }})
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
                                </div>

                                <div class="col-md-6 col-12 p-2">
                                    <div class="row mb-2">
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Sucursal</label>
                                            <select class="form-select" name="sucursal" id="sucursal" required>
                                                <option value="">Seleccionar...</option>
                                                @foreach ($varsucursales as $sucursal)
                                                    <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}
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

                                    <div class="row mb-2">
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Saldo Inicial</label>
                                            <input type="number" class="form-control text" name="saldo_inicial"
                                                id="saldo_inicial" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <label class="fw-bold">Tipo de Caja</label>

                                        <div class="form-outline text-start">
                                            <label class="form-label text-dark fs-9" for="form8Example4">Caja chica
                                            </label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input"
                                                    style="width: 45px!important;height: 18px!important;"
                                                    type="checkbox" value="caja_chica" role="switch"
                                                    name="tipoCaja" id="tipoCaja">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container">
                        <div class="row justify-content-center p-3">
                            <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/tableX.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        const $responsable = $('#responsable');

        if (!$responsable.length || typeof $.fn.select2 === 'undefined') {
            return;
        }

        if ($responsable.hasClass('select2-hidden-accessible')) {
            $responsable.select2('destroy');
        }

        $responsable.select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            placeholder: 'Buscar empleado...',
            dropdownParent: $('#exampleModal'),
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
</script>
@endsection
