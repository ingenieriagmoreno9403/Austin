@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('successPendiente'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "success",title: "¡Acción exitosa!", text: "Guardado en Prestamos Pendientes, esperando Autorización"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('successAutorizado'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "success",title: "¡Acción exitosa!", text: "Prestamo Autorizado correctamente"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('ExistePres'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "success",title: "¡Ya existe un prestamo Pendiente o Activo!", text: "Verifique antes de calcular"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('warningDia'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "info",title: "¡Día no permitido!", text: "Verifique el día que puede aplicar"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('warningBD'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡Fallo la acción!", text: "Contacte a un superior para ver el error"});';
            echo '</script>'; 
    @endphp
@endif

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">CátalogoPréstamos de Nómina</h2>
                        <p class="text-muted mb-0">Administración de préstamos en empleados</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-baseColor fs-7 mb-2" href="/prestamosnominas/capturaPrestamos">
                        <i class="fa-solid fa-plus"></i> Nuevo
                    </a> 

                    @if ($permisos4 == 'exportar_presnom')
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="/exportar_excel_creditos_empleados">
                            <i class="fa-solid fa-file-excel"></i> Exportar
                        </a> 
                    @else
                        <button class="btn btn-baseColor-light fs-7 mb-2" disabled type="button">
                            <i class="fa-solid fa-file-excel"></i> Exportar
                        </button> 
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="tablePrestamoNomina">
                <thead>
                    <tr class="text-tr">
                        <th class="text-center text-truncate">Nombre</th>
                        <th class="text-center text-truncate">Estado</th>
                        <th class="text-center text-truncate">Edo. Cuenta</th>
                        <th class="text-center text-truncate">Doc.</th>
                        <th class="text-center text-truncate">Tipo Credito</th>
                        <th class="text-center text-truncate">Plazos</th>
                        <th class="text-center text-truncate">Pago Quincenal</th>
                        <th class="text-center text-truncate">Fecha Inicio</th>
                        <th class="text-center text-truncate">Monto</th>
                        <th class="text-center text-truncate">Total</th>
                        <th class="text-center text-truncate">Opciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($varPresEnc as $datos)
                        <tr class="boder-sec">
                            <td class="text-start">
                                {{ $datos->Nombre }}
                            </td>

                            <td class="text-truncate">
                                @if ($datos->estado == 'A')
                                    <button class="btn btn-success rounded-5 pointer_none fs-8">Activo</button>
                                @endif
                                @if ($datos->estado == 'C')
                                    <button class="btn btn-danger rounded-5 pointer_none fs-8">Cancelado</button>
                                @endif
                                @if ($datos->estado == 'S')
                                    <button class="btn btn-secondary rounded-5 pointer_none fs-8">Saldado</button>
                                @endif
                            </td>

                            <td class="text-truncate">
                                {{-- @if ($datos->estado_cuenta_status != 'A')
                                    <button class="btn text-primary rounded-5 fs-8 border-0" disabled><i
                                            class="fa-solid fa-download"></i></button>
                                @else --}}
                                    <form class="modern-form"
                                        action="/downloadEstadoCuenta/catalogo/{{ $datos->id }}/{{ $datos->id_empleado }}/{{ $datos->idcoordinador }}">
                                        @if ($permisos2 == 'descargar_estado_cuenta')
                                            <button class="btn text-primary rounded-5 fs-8 border-0" type="submit"><i
                                                    class="fa-solid fa-download"></i></button>
                                        @else
                                            <button class="btn text-secondary rounded-5 fs-8 border-0" disabled><i
                                                    class="fa-solid fa-download"></i></button>
                                        @endif
                                    </form>
                                {{-- @endif --}}
                            </td>

                            <td class="text-truncate">
                                @if ($datos->estado_cuenta_status == 'A')
                                    @if ($permisos3 == 'documentos_cred_emp')
                                        <button class="btn text-primary rounded-5 fs-8 border-0" type="button"
                                            data-bs-toggle="offcanvas"
                                            data-bs-target="#offcanvasArchivos{{ $datos->id }}"
                                            aria-controls="offcanvasBottom"title="Ver Archivo"><i
                                                class="fa-solid fa-eye"></i></button>
                                    @else
                                        <button class="btn text-secondary rounded-5 fs-8 border-0" type="button"
                                            title="Subir Archivo" disabled><i
                                                class="fa-solid fa-arrow-up-from-bracket"></i></button>
                                    @endif
                                @else
                                    <button class="btn text-primary rounded-5 fs-8 border-0" type="button"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target="#offcanvasBottom{{ $datos->id }}"
                                        aria-controls="offcanvasBottom"title="Subir Archivo"><i
                                            class="fa-solid fa-arrow-up-from-bracket"></i></button>
                                @endif
                            </td>

                            <td class="text-truncate">
                                @if ($datos->tipo_credito == 'nomina')
                                    <button class="rounded-5 btn bg_success text-success fs-8">Nómina</button>
                                @endif
                                @if ($datos->tipo_credito == 'automovil')
                                    <button class="rounded-5 btn bg_orange text-orange fs-8">Automóvil</button>
                                @endif
                            </td>

                            <td class="text-truncate">
                                @php($plazosPagados = 0)
                                @foreach ($varPrestEmpEncCompleto as $complePres)
                                    @if ($datos->id == $complePres->id_credito)
                                        @if ($complePres->estado == 'S')
                                            @php($plazosPagados = $plazosPagados + 1)
                                        @endif
                                    @endif
                                @endforeach
                                {{ $plazosPagados }}/{{ $datos->plazos }}
                            </td>

                            <td>
                                @php($pagoQuincenal = 0)
                                @foreach ($varPrestEmpEncCompleto as $pago)
                                    @if ($datos->id == $pago->id_credito)
                                        @php($pagoQuincenal = $pago->pago_quincenal)
                                    @endif
                                @endforeach
                                $ {{ $formattedNum = number_format($pagoQuincenal, 2) }}
                            </td>

                            <td class="text-truncate">
                                {{ $datos->fecha_inicio }}
                            </td>

                            <td class="text-truncate">
                                $ {{ $formattedNum = number_format($datos->monto, 2) }}
                            </td>

                            <td class="text-truncate">
                                $ {{ $formattedNum = number_format($datos->total, 2) }}
                            </td>

                            <td class="text-truncate">
                                @if ($permisos1 == 'cancel_pres_emp')
                                    <button type="button"
                                        class="btn btn-danger text-truncate fs-8 border-0"
                                        data-bs-toggle="modal" data-bs-target="#ModalCancelar{{ $datos->id }}"><i
                                            class="fa-solid fa-trash"></i></button>
                                @else
                                    <button class="btn btn-secondary text-truncate fs-8 border-0"
                                        type="button" data-bs-toggle="modal"
                                        data-bs-target="#ModalPedirCancelacion{{ $datos->id }}"><i
                                            class="fa-solid fa-ban"></i></button>
                                @endif
                                <a type="button" class="btn btn-primary fs-8  border-0 fw-normal"
                                    href="/prestamosnominas/detalle/catalogo/{{ $datos->id }}/{{ $datos->idcoordinador }}">
                                    <i class="fa-solid fa-eye fs-8"></i>
                                </a>

                                @if ($datos->status_comentario == '1')
                                    <button class="btn btn-secondary border-0 fs-8 m-0"
                                        style="max-width: 80px;" data-bs-toggle="modal"
                                        data-bs-target="#Modalcomentario{{ $datos->id }}"><i
                                            class="fa-solid fa-comment"></i></button>
                                @else
                                    <button class="btn btn-secondary border-0 fs-8 m-0"
                                        style="max-width: 80px;" data-bs-toggle="modal"
                                        data-bs-target="#Modalcomentario{{ $datos->id }}"><i
                                            class="fa-solid fa-comment"></i></button>
                                @endif

                            </td>
                        </tr>

                        <!-- Modal Comentario -->
                        <div class="modal fade" id="Modalcomentario{{ $datos->id }}" tabindex="-1"
                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title text-center" id="exampleModalLabel">Comentario
                                        </h4>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <form action="/PrestamoEmpleados/Comentario/{{ $datos->id }}/activo"
                                        method="POST" class="modern-form">
                                        @csrf
                                        <div class="modal-body">
                                            @if ($datos->status_comentario == '1')
                                                <div class="badge  text-wrap" style="width: 90%;">
                                                    <p class="text-dark text-break"
                                                        style="text-align: justify!important;font-weight: normal!important;">
                                                        <i class="text-primary fa-regular fa-comment"></i>
                                                        {{ $datos->comentario }}
                                                    </p>
                                                </div>
                                                <hr>
                                                <h6 class="fs-8 mb-2">Remplazar Comentario</h6>
                                            @endif
                                            <div class="row mt-1">
                                                <div class="form-floating">
                                                    <textarea class="form-control fs-6" placeholder="Escribe un comentario..." name="comentario" id="floatingTextarea2"
                                                        style="height: 50px"></textarea>
                                                    <label for="floatingTextarea2"
                                                        style="margin-top:-10px;margin-left:10px;">Nuevo
                                                        Comentario</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button class="btn btn-primary rounded-5 fs-6" type="submit">
                                                <i class="fa-solid fa-check"></i> Guardar Comentario</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Cancelar Autorizado-->
                        <div class="modal fade" id="ModalCancelar{{ $datos->id }}" tabindex="-1"
                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <form action="/PrestamoEmpleados/CancelarPrestamoAct/{{ $datos->id }}"
                                        method="POST" class="modern-form">
                                        @csrf
                                        <div class="modal-body">
                                            <div class="row text-center">
                                                <h5 class="text-break">¿Esta seguro de "<i
                                                        class="text-danger fa-solid fa-ban"></i> Cancelar" el
                                                    prestamo a {{ $datos->Nombre }}?</h5>
                                                <span class="text-break fs-6">Prestamo de
                                                    {{ $datos->tipo_credito }}
                                                    con un monto liberado de <b>$ {{ $datos->monto }}</b> y un
                                                    saldo a pagar de <b>$
                                                        {{ $datos->totalredondeado }}</b></span>
                                            </div>

                                            <input type="text" name="id_credito" id="id_credito"
                                                value="{{ $datos->id }}" hidden>
                                            <input type="text" name="tipocredito" id="tipocredito"
                                                value="{{ $datos->tipo_credito }}" hidden>
                                            <input type="text" name="empleado" id="empleado"
                                                value="{{ $datos->id_empleado }}" hidden>
                                            <input type="text" name="coordinador" id="coordinador"
                                                value="{{ $datos->idcoordinador }}" hidden>
                                            <input type="text" name="fechai" id="fechai"
                                                value="{{ $datos->fecha_inicio }}" hidden>
                                            <input type="text" name="tasa" id="tasa"
                                                value="{{ $datos->idtasa }}" hidden>
                                            <input type="text" name="monto" id="monto"
                                                value="{{ $datos->monto }}" hidden>
                                            <input type="text" name="tipop" id="tipop"
                                                value="{{ $datos->tipo_plazo }}" hidden>
                                            <input type="text" name="numplazo" id="numplazo"
                                                value="{{ $datos->plazos }}" hidden>
                                            <input type="text" name="cuenta" id="cuenta"
                                                value="{{ $datos->id_cuenta }}" hidden>

                                            <div class="row mt-3">
                                                <div class="form-floating">
                                                    <textarea class="form-control fs-6" placeholder="Escribe un comentario..." name="comentario" id="floatingTextarea2"
                                                        style="height: 50px"></textarea>
                                                    <label for="floatingTextarea2"
                                                        style="margin-top:-10px;margin-left:10px;">Nuevo
                                                        Comentario</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button class="btn btn-primary rounded-5 fs-6" type="submit">
                                                <i class="fa-solid fa-check"></i> Aceptar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Cancelar No Autorizado -->
                        <div class="modal fade" id="ModalPedirCancelacion{{ $datos->id }}" tabindex="-1"
                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title text-dark" id="exampleModalLabel">Solicitar la
                                            Cancelación</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <form action="/PrestamoEmpleados/SolicitarCancelacion/{{ $datos->id }}"
                                        method="POST" class="form g-3 needs-validation mt-1 text-start modern-form" novalidate>
                                        @csrf
                                        <div class="modal-body">
                                            <h6 class="text-break">¿Esta seguro de solicitar la "<i
                                                    class="text-danger fa-solid fa-ban"></i> Cancelación" del
                                                prestamo a {{ $datos->Nombre }}?</h6>
                                            <span class="text-break">Prestamo de {{ $datos->tipo_credito }}
                                                con un monto liberado de <b>$ {{ $datos->monto }}</b> y un
                                                saldo a pagar de <b>$ {{ $datos->totalredondeado }}</b></span>

                                            <div class="row mt-4 mb-2">
                                                <div class="col">
                                                    <div class="form-floating">
                                                        <textarea class="form-control fs-8" placeholder="Leave a comment here" name="descripcion" id="floatingTextarea2"
                                                            style="height: 100px" required></textarea>
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

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-danger fs-8 rounded-5"
                                                data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i>
                                                Cerrar</button>
                                            <button type="submit" class="btn btn-success fs-8 rounded-5"><i
                                                    class="fa-solid fa-check"></i> Confirmar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-----Modal subir archivo comprobante de valera----->
                        <div class="offcanvas offcanvas-bottom" tabindex="-1"
                            id="offcanvasBottom{{ $datos->id }}" aria-labelledby="offcanvasBottomLabel"
                            style="height:70vh;">
                            <div class="offcanvas-body contenedor-light">
                                <div>
                                    <button type="button" class="btn btn-outline-secondary rounded-5 fs-8"
                                        data-bs-dismiss="offcanvas" style="width: 40px;height:40px;"
                                        aria-label="Close"><i class="fa-solid fa-chevron-down"></i></button>
                                </div>

                                <!-----Formulario entrega----->
                                <form action="/subirCompEstadoCuenta/{{ $datos->id }}/{{ $datos->id_empleado }}"
                                    method="POST" enctype="multipart/form-data" class="g-3 form needs-validation modern-form"
                                    novalidate>
                                    @csrf
                                    <div class="row p-1">
                                        <div class="row mb-2">
                                            @if ($datos->tipo_credito == 'nomina')
                                                <div class="col">
                                                    <label for="file1" class="drop-container2 mt-2">
                                                        <span class="drop-title1">Subir Estado de Cuenta</span>
                                                        Archivo importado PDF
                                                        <input type="file" name="estadoCuenta"class="validaPDF"
                                                            required>
                                                        <div class="valid-feedback">
                                                            ¡Se ve bien!
                                                        </div>
                                                        <div class="invalid-feedback">
                                                            Por favor, completa la información requerida.
                                                        </div>
                                                    </label>

                                                    <div class="text-start">
                                                        <div class="form-check">
                                                            <label class="form-check-label text-dark"
                                                                style="font-size: 13px!important;"
                                                                for="status_entrega_estado_cuenta">
                                                                ¿Ya lo tengo archivado? <b>Si</b> <i
                                                                    class="text-primary fa-solid fa-circle-question"></i>
                                                            </label>
                                                            <input class="form-check-input mt-2" type="checkbox"
                                                                style="font-size: 13px!important;"
                                                                name="status_entrega_estado_cuenta" value="entregado"
                                                                id="status_entrega_estado_cuenta">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <label for="file1" class="drop-container2 mt-2">
                                                        <span class="drop-title1">Subir Pagaré</span>
                                                        Archivo importado PDF
                                                        <input type="file" name="pagare"class="validaPDF"
                                                            required>
                                                        <div class="valid-feedback">
                                                            ¡Se ve bien!
                                                        </div>
                                                        <div class="invalid-feedback">
                                                            Por favor, completa la información requerida.
                                                        </div>
                                                    </label>

                                                    <div class="text-start">
                                                        <div class="form-check">
                                                            <label class="form-check-label text-dark"
                                                                style="font-size: 13px!important;"
                                                                for="status_entrega_pagare">
                                                                ¿Ya lo tengo archivado? <b>Si</b> <i
                                                                    class="text-primary fa-solid fa-circle-question"></i>
                                                            </label>
                                                            <input class="form-check-input mt-2" type="checkbox"
                                                                name="status_entrega_pagare"
                                                                style="font-size: 13px!important;" value="entregado"
                                                                id="status_entrega_pagare">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif


                                            @if ($datos->tipo_credito == 'automovil')
                                                <div class="col-md-4">
                                                    <label for="file1" class="drop-container2 mt-2">
                                                        <span class="drop-title1">Subir Estado de Cuenta</span>
                                                        Archivo importado PDF
                                                        <input type="file" name="estadoCuenta"class="validaPDF"
                                                            required>
                                                        <div class="valid-feedback">
                                                            ¡Se ve bien!
                                                        </div>
                                                        <div class="invalid-feedback">
                                                            Por favor, completa la información requerida.
                                                        </div>
                                                    </label>

                                                    <div class="text-start">
                                                        <div class="form-check">
                                                            <label class="form-check-label text-dark"
                                                                style="font-size: 13px!important;"
                                                                for="status_entrega_estado_cuenta">
                                                                ¿Ya lo tengo archivado? <b>Si</b> <i
                                                                    class="text-primary fa-solid fa-circle-question"></i>
                                                            </label>
                                                            <input class="form-check-input mt-2"
                                                                style="font-size: 13px!important;" type="checkbox"
                                                                name="status_entrega_estado_cuenta" value="entregado"
                                                                id="status_entrega_estado_cuenta">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="file1" class="drop-container2 mt-2">
                                                        <span class="drop-title1">Subir Pagaré</span>
                                                        Archivo importado PDF
                                                        <input type="file" name="pagare"class="validaPDF"
                                                            required>
                                                        <div class="valid-feedback">
                                                            ¡Se ve bien!
                                                        </div>
                                                        <div class="invalid-feedback">
                                                            Por favor, completa la información requerida.
                                                        </div>
                                                    </label>

                                                    <div class="text-start">
                                                        <div class="form-check">
                                                            <label class="form-check-label text-dark"
                                                                style="font-size: 13px!important;"
                                                                for="status_entrega_pagare">
                                                                ¿Ya lo tengo archivado? <b>Si</b> <i
                                                                    class="text-primary fa-solid fa-circle-question"></i>
                                                            </label>
                                                            <input class="form-check-input mt-2"
                                                                style="font-size: 13px!important;" type="checkbox"
                                                                name="status_entrega_pagare" value="entregado"
                                                                id="status_entrega_pagare">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="file1" class="drop-container2 mt-2">
                                                        <span class="drop-title1">Subir Factura</span>
                                                        Archivo importado PDF
                                                        <input type="file" name="factura"class="validaPDF"
                                                            required>
                                                        <div class="valid-feedback">
                                                            ¡Se ve bien!
                                                        </div>
                                                        <div class="invalid-feedback">
                                                            Por favor, completa la información requerida.
                                                        </div>
                                                    </label>

                                                    <div class="text-start">
                                                        <div class="form-check">
                                                            <label class="form-check-label text-dark"
                                                                style="font-size: 13px!important;"
                                                                for="status_entrega_factura">
                                                                ¿Ya lo tengo archivado? <b>Si</b> <i
                                                                    class="text-primary fa-solid fa-circle-question"></i>
                                                            </label>
                                                            <input class="form-check-input mt-2"
                                                                style="font-size: 13px!important;" type="checkbox"
                                                                name="status_entrega_factura" value="entregado"
                                                                id="status_entrega_factura">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            <input type="text" name="tipo_credito"
                                                value="{{ $datos->tipo_credito }}" hidden required>
                                        </div>

                                        <div class="text-center">
                                            <br>
                                            <button type="submit"
                                                class="btn btn-primary col-md-2 col fw-light rounded-5 fs-8"><i
                                                    class="fas fa-arrow-up-from-bracket"></i>&nbsp;&nbsp;
                                                Subir</button></br></br>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-----Modal ver archivo comprobante de valera----->
                        <div class="offcanvas offcanvas-bottom" tabindex="-1"
                            id="offcanvasArchivos{{ $datos->id }}" aria-labelledby="offcanvasBottomLabel"
                            style="height:50vh;">
                            <div class="offcanvas-body contenedor-light">
                                <div>
                                    <button type="button" class="btn btn-outline-secondary rounded-5 fs-8"
                                        data-bs-dismiss="offcanvas" style="width: 40px;height:40px;"
                                        aria-label="Close"><i class="fa-solid fa-chevron-down"></i></button>
                                </div>
                                <form action="/PrestamoEmpleados/Estado/{{ $datos->id }}" method="POST" class="modern-form">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12 text-start">
                                            <h5 class="mb-3 fw-bold text-dark">Archivos de crédito
                                                &nbsp;&nbsp;&nbsp;
                                                @if (
                                                    $datos->status_entrega_estado_cuenta == 'entregado' &&
                                                        $datos->status_entrega_pagare == 'entregado' &&
                                                        $datos->status_entrega_factura == 'entregado')
                                                @else
                                                    <button class="btn btn-primary rounded-5  fs-10" type="submit"><i
                                                            class="fa-solid fa-check"></i>
                                                        Guardar Estado</button>
                                                @endif
                                            </h5>
                                        </div>
                                    </div>

                                    <div class="row p-3 text-start text-dark">
                                        {{ $datos->id }}
                                        <div class="card push shadow-sm mr-1 col" style="height: 100px;">
                                            <div class="row mt-2 p-2">
                                                <div class="col-2 fs-4 text-purple"><i style="font-size:60px;"
                                                        class="text-purple fa-solid fa-file-lines"></i></div>
                                                <div class="col form-outline text-start">
                                                    <a target="_blank" class="btn border-0" style="font-size:13px;"
                                                        href="{{ asset('Expedientes/prestamosEmpleados/EMP_' . $datos->id_empleado . '/PRES_' . $datos->id . '/' . $datos->estado_cuenta) }}">
                                                        <h6 class="text-dark">Estado de Cuenta <i
                                                                class="text-primary fa-solid fa-eye"></i></h6>
                                                        <div class="text-start">
                                                            @if ($datos->status_entrega_estado_cuenta == 'entregado')
                                                                <p><i
                                                                        class="text-success fa-solid fa-circle-check"></i>
                                                                    Archivado</p>
                                                            @else
                                                                <div class="form-check">
                                                                    <label class="form-check-label text-secondary"
                                                                        style="font-size: 10px!important;"
                                                                        for="status_entrega_estado_cuenta">
                                                                        ¿Ya lo tengo archivado? <b>Si</b> <i
                                                                            class="text-primary fa-solid fa-circle-question"></i>
                                                                    </label>
                                                                    <input class="form-check-input mt-2"
                                                                        style="font-size: 13px!important;"
                                                                        type="checkbox"
                                                                        name="status_entrega_estado_cuenta"
                                                                        value="entregado"
                                                                        id="status_entrega_estado_cuenta">
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card push shadow-sm mr-1 col">
                                            <div class="row mt-2 p-2">
                                                <div class="col-3 fs-4 text-success"><i style="font-size:60px;"
                                                        class="fa-solid fa-money-check-dollar"></i></div>
                                                <div class="col form-outline text-start">
                                                    <a target="_blank" class="btn border-0" style="font-size:13px;"
                                                        href="{{ asset('Expedientes/prestamosEmpleados/EMP_' . $datos->id_empleado . '/PRES_' . $datos->id . '/' . $datos->pagare) }}">
                                                        <h6 class="text-dark  text-start">Pagaré
                                                            &nbsp;&nbsp;&nbsp;<i
                                                                class="text-primary fa-solid fa-eye"></i></h6>
                                                        <div class="text-start">
                                                            @if ($datos->status_entrega_pagare == 'entregado')
                                                                <p><i
                                                                        class="text-success fa-solid fa-circle-check"></i>
                                                                    Archivado</p>
                                                            @else
                                                                <div class="form-check">
                                                                    <label class="form-check-label text-secondary"
                                                                        style="font-size: 10px!important;"
                                                                        for="status_entrega_pagare">
                                                                        ¿Ya lo tengo archivado? <b>Si</b> <i
                                                                            class="text-primary fa-solid fa-circle-question"></i>
                                                                    </label>
                                                                    <input class="form-check-input mt-2"
                                                                        style="font-size: 13px!important;"
                                                                        type="checkbox" name="status_entrega_pagare"
                                                                        value="entregado" id="status_entrega_pagare">
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        @if ($datos->tipo_credito == 'automovil')
                                            <div class="card push shadow-sm mr-1 col">
                                                <div class="row mt-2 p-2">
                                                    <div class="col-2 fs-4 text-primary"><i style="font-size:60px;"
                                                            class="fa-solid fa-file-invoice-dollar"></i></div>
                                                    <div class="col form-outline text-start">
                                                        <a target="_blank" class="btn border-0"
                                                            style="font-size:13px;"
                                                            href="{{ asset('Expedientes/prestamosEmpleados/EMP_' . $datos->id_empleado . '/PRES_' . $datos->id . '/' . $datos->factura) }}">
                                                            <h6 class="text-dark">Factura &nbsp;&nbsp;&nbsp;<i
                                                                    class="text-primary fa-solid fa-eye"></i>
                                                            </h6>
                                                            <div class="text-start">
                                                                @if ($datos->status_entrega_factura == 'entregado')
                                                                    <p><i
                                                                            class="text-success fa-solid fa-circle-check"></i>
                                                                        Archivado</p>
                                                                @else
                                                                    <div class="form-check">
                                                                        <label class="form-check-label text-secondary"
                                                                            style="font-size: 10px!important;"
                                                                            for="status_entrega_factura">
                                                                            ¿Ya lo tengo archivado? <b>Si</b> <i
                                                                                class="text-primary fa-solid fa-circle-question"></i>
                                                                        </label>
                                                                        <input class="form-check-input mt-2"
                                                                            style="font-size: 13px!important;"
                                                                            type="checkbox"
                                                                            name="status_entrega_factura"
                                                                            value="entregado"
                                                                            id="status_entrega_factura">
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="5"></th>
                        <th>Subtotal</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="5"></th>
                        <th>Total</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>


    {{-- Modal de Solicitudes de Cancelaciones --}}
    <div class="offcanvas offcanvas-top" tabindex="-1" id="ModalCancelaciones" aria-labelledby="offcanvasTopLabel"
        style="height: 70vh;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasTopLabel">Solicitudes de Cancelaciones</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body" style="margin-top:-20px!important;">
            <div class="bg-body rounded border-0">
                <div class="table-responsive pad-table" id="mydatatable-container">

                    <table class="table table-striped table-hover" id="tableSolicitudesCancelaciones">
                        <thead>
                            <tr class=" text-secondary">
                                <th class="text-center">Herramienta</th>
                                <th class="text-center">Crédito</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Fecha</th>
                                <th class="text-center">Nombre</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-center">Descripción</th>
                                <th class="text-center">Solicitado por</th>
                            </tr>
                        </thead>


                        <tbody>
                            @foreach ($varCancelSoli as $cancel)
                                <tr class="boder-sec">
                                    <td class=" text-secondary">
                                        <button type="button"
                                            class="btn btn-danger rounded-5 text-truncate  fs-10 m-0"
                                            data-bs-toggle="modal"
                                            data-bs-target="#ModalCancelar{{ $cancel->id_credito }}"><i
                                                class="fa-solid fa-trash"></i> Cancelar</button>
                                    </td>
                                    <td class=" text-secondary">{{ $cancel->id_credito }}</td>
                                    <td class=" text-secondary">
                                        <button class="btn btn-baseColor rounded-5  fs-10">Pendiente</button>
                                    </td>
                                    <td class=" text-secondary">{{ $cancel->fecha }}</td>
                                    <td class=" text-secondary">{{ $cancel->NombreEmp }}</td>
                                    <td class=" text-secondary">
                                        @if ($cancel->tipo_credito == 'nomina')
                                            <button class="rounded-5 btn btn-success  fs-10 pointer_none">
                                                Nomina</button>
                                        @endif
                                        @if ($cancel->tipo_credito == 'automovil')
                                            <button class="rounded-5 btn btn-primary  fs-10 pointer_none">
                                                Automovil</button>
                                        @endif
                                    </td>
                                    <td class=" text-secondary">{{ $cancel->descripcion }}</td>
                                    <td class=" text-secondary">{{ $cancel->created_by }}</td>
                                </tr>
                            @endforeach
                        </tbody>


                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/tableX.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
<script>
    $(document).ready(function() {
        $("input[type=submit]").click(function() {
            var accion = $(this).attr('dir');
            $('.formulario1').attr('action', accion);
            $('.formulario1').submit();
        });
    });
</script>
<script>
    $(document).ready(function() {
        $("input[type=submit]").click(function() {
            var accion = $(this).attr('dir');
            $('.formulario2').attr('action', accion);
            $('.formulario2').submit();
        });
    });
</script>
@endsection
