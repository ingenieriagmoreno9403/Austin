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
@elseif($mensaje = Session::get('saldoInsuficiente'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡Saldo insuficiente!", text: "Seleccione una cuenta diferente para poder aplicar"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('required'))
@php
        echo '<script language="JavaScript">';
        echo 'const Toast = Swal.mixin({';
        echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
        echo 'didOpen: (toast) => {';
        echo '  toast.onmouseenter = Swal.stopTimer;';
        echo '  toast.onmouseleave = Swal.resumeTimer;}});';
        echo 'Toast.fire({ icon: "warning",title: "¡Prestamo no aplicado!", text: "Faltaron campos por llenar"});';
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
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/prestamosnominas" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Préstamos nómina
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Nuevo Crédito</h2>
                        <p class="text-muted mb-0">Administración de préstamos a empleados</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-baseColor fs-7 mb-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                        <i class="fa-solid fa-plus"></i> Nuevo Crédito
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="collapse" id="collapseExample">
        <form action="#" method="POST" id="prestamoForm" enctype="multipart/form-data"
            class="g-3 form needs-validation formulario1 p-2 pt-0 p-0 modern-form" novalidate>
            @csrf
            <div class="card p-3 rounded-2">
                <div class="row p-3 pb-0">
                    <div class="col-md-4">
                        <div class="form-outline">
                            <label class="form-label"for="form8Example4">Empleado</label>
                            <select name="empleado" id="empleado" class=" form-select text" required>
                                <option value="" selected>Selecciona ...</option>
                                @foreach ($varempleados as $empleados)
                                    <option value="{{ $empleados->id }}"><b>{{ $empleados->id }} - </b>
                                        {{ $empleados->Nombre }}</option>
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

                    <div class="col">
                        <div class="form-outline">
                            <label class="form-label" for="form8Example4">Tipo de credito</label>
                            <select name="tipocredito" id="" class=" form-select text"
                                onchange="showHideFactura(this);" required>
                                <option value="" selected> Selecciona ...</option>
                                <option value="nomina">Nomina</option>
                                <option value="automovil">Automovil</option>
                            </select>
                            <div class="valid-feedback">
                                ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                            </div>
                        </div>
                    </div>

                    <div class="col">
                        <div class="form-outline">
                            <label class="form-label" for="form8Example4">Fecha Inicio</label>
                            <input type="date" class="form-control" name="fechai" id="fechai" maxlength="10"
                                required />
                            <div class="valid-feedback">
                                ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-outline">
                            <label class="form-label" for="form8Example4">Autoriza</label>
                            <select name="coordinador" id="" class=" form-select text" required>
                                <option value="" selected> Selecciona ...</option>
                                @foreach ($varcoordinadores as $cor)
                                    <option value="{{ $cor->id }}">{{ $cor->Nombre }}</option>
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

                <div class="row p-3">
                    <div class="col">
                        <div class="form-outline">
                            <label class="form-label" for="form8Example4">Tasa</label>
                            <select name="tasa" id="" class=" form-select text" required>
                                <option value="" selected> Selecciona ...</option>
                                @foreach ($listatas as $tasa)
                                    <option value="{{ $tasa->id }}">{{ $tasa->nombre }}</option>
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

                    <div class="col-md-3">
                        <div class="form-outline">
                            <label class="form-label" for="form8Example4">Monto</label>
                            <input type="text" class="form-control text" name="monto" id="monto" maxlength="10"
                                required />
                            <div class="valid-feedback">
                                ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-outline">
                            <label class="form-label" for="form8Example4">Tipo de Plazo</label>
                            <select name="tipop" id="" class=" form-select text" required>
                                <option value="quincenal">Quincenal</option>
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

                <div class="row p-3 pt-0">
                    <div class="col">
                        <div class="row">
                            @if ($permisos2 == 'insertarplazomanuales')
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Plazos</label>
                                    <input type="text" class="form-control text" name="numplazo" id="plazosm"
                                        maxlength="10" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            @else
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Numero de plazos</label>
                                    <select name="numplazo" id="" class=" form-select text" required>
                                        <option value="" selected>Selecciona ...</option>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>
                                        <option value="7">7</option>
                                        <option value="8">8</option>
                                        <option value="9">9</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                        <option value="14">14</option>
                                        <option value="15">15</option>
                                    </select>
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="row">
                            <label class="form-label" for="form8Example4">Tipo de Desembolso</label>
                            <div class="row p-4 pt-0 pb-0">
                                <div class="col p-3 pt-1 pb-1 form-check">
                                    <input class="form-check-input cuenta" type="radio" name="tipo" value="CUENTA"
                                        onclick="cuenta()" id="flexRadioDefault1" required>
                                    <label class="form-check-label" for="flexRadioDefault1">
                                        Cuenta
                                    </label>
                                </div>

                                <div class="col p-3 pt-1 pb-1 form-check">
                                    <input class="form-check-input caja" type="radio" name="tipo" value="CAJA"
                                        onclick="caja()" id="flexRadioDefault1" required>
                                    <label class="form-check-label" for="flexRadioDefault1">
                                        Caja
                                    </label>
                                </div>
                            </div>


                            <div class="form-outline" id="cuentas">
                                <label class="form-label" for="form8Example4">Cuenta de Desembolso</label>
                                <select name="cuenta" class="form-select cuentaselect">
                                    <option value="">Selecciona ...</option>
                                    @foreach ($cuentas as $cuenta)
                                        @foreach ($varManejoCuentas as $permiso1)
                                            @if ($permiso1->id == $cuenta->id)
                                                <option value="{{ $cuenta->id }}">{{ $cuenta->descripcion }}
                                                    ({{ $cuenta->saldo_actual }})
                                                </option>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="form-outline" id="cajas">
                                <label class="form-label" for="form8Example4">Caja de Desembolso</label>
                                <select name="caja" class="form-select cajaselect">
                                    <option value="">Selecciona ...</option>
                                    @foreach ($cajas as $caja)
                                        @if ($caja->arqueo == 'Autorizado')
                                            <option value="{{ $caja->id }}">{{ $caja->nombre }}</option>
                                        @else
                                            <option value="{{ $caja->id }}" disabled>{{ $caja->nombre }} (Arqueo
                                                Pendiente)</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-12">
                        <label for=""></label>
                        <div style="padding: 10px 0px 30px 0px;">
                            <label for="file1" class="drop-container2">
                                <span class="drop-title2">Subir Estado de Cuenta</span>
                                <small>Archivo importado PDF</small>
                                <label class="btn" for="estadoCuenta">Seleccionar
                                    archivo</label>
                                <input type="file" id="estadoCuenta" class="form-control d-none"
                                    name="estadoCuenta"
                                    onchange="this.nextElementSibling.textContent = this.files[0]?.name || 'No se ha seleccionado ningún archivo';"
                                    required>
                                <small class="text-muted mt-2">No se ha seleccionado ningún
                                    archivo</small>
                                <div class="valid-feedback"> ¡Se ve bien!</div>
                                <div class="invalid-feedback"> Por favor, completa la información
                                    requerida.</div>
                            </label>
                        </div>
                    </div>

                    <div class="col-lg-3 col-12">
                        <label for=""></label>
                        <div style="padding: 10px 0px 30px 0px;">
                            <label for="file1" class="drop-container2">
                                <span class="drop-title2">Subir Pagaré</span>
                                <small>Archivo importado PDF</small>
                                <label class="btn" for="pagare">Seleccionar
                                    archivo</label>
                                <input type="file" id="pagare" class="form-control d-none" name="pagare"
                                    onchange="this.nextElementSibling.textContent = this.files[0]?.name || 'No se ha seleccionado ningún archivo';"
                                    required>
                                <small class="text-muted mt-2">No se ha seleccionado ningún
                                    archivo</small>
                                <div class="valid-feedback"> ¡Se ve bien!</div>
                                <div class="invalid-feedback"> Por favor, completa la información
                                    requerida.</div>
                            </label>
                        </div>
                    </div>

                    <div class="col-lg-3 col-12" id="divFactura">
                        <label for="">Factura</label>
                        <div style="padding: 10px 0px 30px 0px;">
                            <label for="file1" class="drop-container2">
                                <span class="drop-title1">Subir Factura</span>
                                Archivo importado PDF
                                <label class="btn btn-primary mb-2" for="factura">Seleccionar
                                    archivo</label>
                                <input type="file" id="factura" class="form-control d-none" name="factura"
                                    onchange="this.nextElementSibling.textContent = this.files[0]?.name || 'No se ha seleccionado ningún archivo';">
                                <small class="text-muted mt-2">No se ha seleccionado ningún
                                    archivo</small>
                                <div class="valid-feedback"> ¡Se ve bien!</div>
                                <div class="invalid-feedback"> Por favor, completa la información
                                    requerida.</div>
                            </label>
                        </div>
                    </div>

                    <div class="col-lg-3 col-12">
                        <div class="form-floating" style="padding-top: 30px">
                            <textarea class="form-control" placeholder="Escribe un comentario..." name="comentario" id="floatingTextarea2"
                                style="height: 200px"></textarea>
                            <label for="floatingTextarea2"
                                style="margin-left:10px; margin-top:20px">Comentario</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="mt-3" style="text-align: start-center">
                        @if ($permisos3 == 'calcular_prestamo_emp')
                            <button type="submit" class="col btn bg-orange text-light rounded-2"
                                onclick="prepararCalculo('/guardarprestamonomina/calcular')">
                                Calcular
                            </button>
                        @else
                            <button class="btn bg-primary text-light rounded-2" disabled>Calcular</button>
                        @endif

                        @if ($permisos4 == 'aut_pres_emp')
                            <button type="submit" class="btn bg-success text-light rounded-2"
                                onclick="prepararAutorizar('/guardarprestamonomina/autorizar')">
                                Autorizar
                            </button>
                        @else
                            <button class="btn bg-success text-light rounded-2" disabled>Autorizar</button>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="tableCapturaPrestamos">
                <thead>
                    <tr>
                        <th class="text-truncate">Empleado</th>
                        <th class="text-truncate">Estado</th>
                        <th class="text-truncate">Edo. Cuenta</th>
                        <th class="text-truncate">Tipo Credito</th>
                        <th class="text-truncate">Plazos</th>
                        <th class="text-truncate">Fecha Inicio</th>
                        <th class="text-truncate">Tasa</th>
                        <th class="text-truncate">Porcentaje</th>
                        <th class="text-truncate">Monto</th>
                        <th class="text-truncate">Interés</th>
                        <th class="text-truncate">Iva Interés</th>
                        <th class="text-truncate">Total</th>
                        <th class="text-truncate">Interés R.</th>
                        <th class="text-truncate">Total R.</th>
                        <th class="text-truncate">Tipo Plazo</th>
                        <th class="text-truncate">Opciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($varPresEnc as $datos)
                        <tr>
                            <td class="table-secondary text-start p-0">
                                {{ $datos->Nombre }}
                            </td>
                            {{-- 
                            <a type="button" class="btn fs-8 rounded-5 border-0 m-0"
                                    href="/prestamosnominas/detalle/pendientes/{{ $datos->id }}/{{ $datos->idcoordinador }}">
                                    <i class="text-primary fa-solid fa-eye"></i>&nbsp;
                                    &nbsp;<b>{{ $datos->id_empleado }} - </b>{{ $datos->Nombre }}
                                </a>
                                    --}}


                            <td class="table-secondary text-secondary text-truncate">
                                @if ($datos->estado == 'P')
                                    <button
                                        class="btn btn-baseColor rounded-5 pointer_none fs-8 m-0">Pendiente</button>
                                @endif
                                @if ($datos->estado == 'A')
                                    <button class="btn btn-success rounded-5 pointer_none fs-8 m-0">Activo</button>
                                @endif
                                @if ($datos->estado == 'C')
                                    <button
                                        class="btn btn-danger rounded-5 pointer_none fs-8 m-0">Cancelado</button>
                                @endif
                                @if ($datos->estado == 'S')
                                    <button
                                        class="btn btn-secondary rounded-5 pointer_none fs-8 m-0">Saldado</button>
                                @endif
                            </td>

                            <td class="table-light text-secondary text-truncate p-0">
                                @if ($permisos7 == 'descargar_estado_cuenta')
                                    <form class="modern-form"
                                        action="/downloadEstadoCuenta/pendientes/{{ $datos->id }}/{{ $datos->id_empleado }}/{{ $datos->idcoordinador }}">
                                        <button class="btn text-primary rounded-5 fs-8 border-0 m-0"
                                            type="submit"><i class="fa-solid fa-download"></i></button>
                                    </form>
                                @else
                                    <button class="btn text-primary rounded-5 fs-8 border-0 m-0" type="submit"
                                        disabled><i class="fa-solid fa-download"></i></button>
                                @endif
                            </td>

                            <td class="table-light text-secondary text-truncate p-0">
                                @if ($datos->tipo_credito == 'nomina')
                                    <button class="rounded-5 btn btn-success fs-8 pointer_none m-0">
                                        Nomina</button>
                                @endif
                                @if ($datos->tipo_credito == 'automovil')
                                    <button class="rounded-5 btn btn-baseColor fs-8 pointer_none m-0">
                                        Automovil</button>
                                @endif
                            </td>

                            <td class="table-secondary text-truncate">{{ $datos->plazos }}</td>
                            </td>
                            <td class="table-secondary text-truncate">{{ $datos->fecha_inicio }}
                            </td>
                            <td class="table-light text-truncate">{{ $datos->tasa }}</td>
                            <td class="table-secondary text-truncate">% {{ $datos->porcentaje }}</td>
                            <td class="table-success text-success text-truncate">$
                                {{ number_format($datos->monto, 2) }}</td>
                            <td class="table-secondary text-truncate">{{ $datos->interes }}</td>
                            <td class="table-secondarytext-truncate">{{ $datos->ivainteres }}
                            </td>
                            <td class="table-secondary text-success text-truncate">$
                                {{ number_format($datos->total, 2) }}</td>
                            <td class="table-success text-success text-truncate">{{ $datos->interesredondeado }}
                            </td>
                            <td class="table-success text-success text-truncate">$
                                {{ number_format($datos->totalredondeado, 2) }}
                            </td>

                            <td class="table-secondary text-truncate">{{ ucfirst($datos->tipo_plazo) }} </td>

                            <td>
                                @if ($permisos6 == 'editar_captura_cred_emp')
                                    <button type="button" class="btn text-primary fs-8 rounded-5"
                                        data-bs-toggle="modal" data-bs-target="#Modal{{ $datos->id }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn text-primary fs-8 rounded-5" disabled>
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                @endif

                                @if ($permisos5 == 'eliminar_captura_cred_emp')
                                    <a type="button" class="btn text-danger fs-8 rounded-5"
                                        href="/eliminarprestamonomina/{{ $datos->id }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                @else
                                    <button type="button" class="btn text-danger fs-8 rounded-5" disabled>
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                @endif

                                @if ($datos->status_comentario == '1')
                                    <a type="button" class="btn border-0 text-truncate text-primary fs-8 m-0"
                                        style="max-width: 80px;" data-bs-toggle="modal"
                                        data-bs-target="#Modalcomentario{{ $datos->id }}">
                                        <i class="fa-solid fa-comment"></i></a>
                                @else
                                    <button class="btn text-primary rounded-5 border-0 fs-8 m-0"
                                        style="max-width: 80px;" data-bs-toggle="modal"
                                        data-bs-target="#Modalcomentario{{ $datos->id }}"><i
                                            class="fa-solid fa-comment"></i></button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="7"></th>
                        <th>Subtotal</th>
                        <th></th>
                        <th colspan="2"></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th colspan="2"></th>
                    </tr>
                    <tr>
                        <th colspan="7"></th>
                        <th>Total</th>
                        <th></th>
                        <th colspan="2"></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @foreach ($varPresEnc as $datos)
        <!-- Modal Editar-->
        <div class="modal fade" id="Modal{{ $datos->id }}" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h6 class="modal-title text-dark" id="exampleModalLabel">Editar crédito
                            calculado - {{ $datos->Nombre }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <form action="#" method="POST" id="prestamoFormEdit" enctype="multipart/form-data"
                        class="g-3 needs-validation offcanvas-body form formulario2 modern-form" novalidate>
                        @csrf
                        <div class="modal-body">
                            <div class="row p-3 pt-1">
                                <input type="text" name="id_credito" value="{{ $datos->id }}" hidden required>
                                <div class="col text-start">
                                    <div class="form-outline">
                                        <label class="form-label"for="form8Example4">Cliente</label>
                                        <select name="empleado" id="empleado" class=" form-select text" required>
                                            <option value="{{ $datos->id_empleado }}">
                                                {{ $datos->Nombre }}</option>
                                        </select>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 text-start">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Tipo de
                                            credito</label>
                                        <select name="tipocredito" id="" class=" form-select text" required>
                                            <option value="{{ $datos->tipo_credito }}">
                                                {{ $datos->tipo_credito }}</option>
                                        </select>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col text-start">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Fecha
                                            Inicio</label>
                                        <input type="date" class="form-control text" name="fechai"
                                            id="fechai"
                                            value="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $datos->fecha_inicio)->format('Y-m-d') }}"
                                            maxlength="10" required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col text-start">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Coordinador</label>
                                        <select name="coordinador" id="" class=" form-select text" required>
                                            @foreach ($varcoordinadores as $cord)
                                                @if ($cord->id == $datos->idcoordinador)
                                                    <option selected value="{{ $cord->id }}">
                                                        {{ $cord->Nombre }}</option>
                                                @else
                                                    <option value="{{ $cord->id }}">
                                                        {{ $cord->Nombre }}</option>
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
                            </div>

                            <div class="row p-3 pt-1">
                                <div class="col text-start">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Tasa</label>
                                        <select name="tasa" id="" class=" form-select text" required>
                                            @foreach ($listatas as $tasa)
                                                @if ($tasa->id == $datos->idtasa)
                                                    <option selected value="{{ $tasa->id }}">
                                                        {{ $tasa->nombre }}</option>
                                                @else
                                                    <option value="{{ $tasa->id }}">
                                                        {{ $tasa->nombre }}</option>
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

                                <div class="col text-start">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Monto</label>
                                        <input type="text" class="form-control text" name="monto"
                                            id="monto" value="{{ $datos->monto }}" maxlength="10" required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col text-start">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Tipo de
                                            Plazo</label>
                                        <select name="tipop" id="" class=" form-select text" required>
                                            <option value="quincenal">Quincenal</option>
                                        </select>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col text-start">
                                    @if ($permisos2 == 'insertarplazomanuales')
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Plazos
                                                Manuales</label>
                                            <input type="text" class="form-control text" name="numplazo"
                                                id="plazosm" value="{{ $datos->plazos }}" maxlength="10"
                                                required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    @else
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Numero
                                                de plazos</label>
                                            <select name="numplazo" id="" class=" form-select text"
                                                required>
                                                <option value="{{ $datos->plazos }}" selected>
                                                    {{ $datos->plazos }}</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                                <option value="6">6</option>
                                                <option value="7">7</option>
                                                <option value="8">8</option>
                                                <option value="9">9</option>
                                                <option value="10">10</option>
                                                <option value="11">11</option>
                                                <option value="12">12</option>
                                                <option value="13">13</option>
                                                <option value="14">14</option>
                                                <option value="15">15</option>
                                            </select>
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="row p-4 pt-1 pb-1">
                                <div class="col-md-5 card rounded-3 p-3 text-start">
                                    <label class="form-label" for="form8Example4">Tipo de
                                        Desembolso</label>
                                    <div class="row p-4 pt-0 pb-2 text-start">

                                        @if ($datos->tipo_cuenta == 'CAJA')
                                            <div class="col p-2 pt-0 form-check">
                                                <label class="form-check-label text-dark"
                                                    for="flexRadioDefault1">Cuenta</label>
                                                <input class="form-check-input cuentaCheck2" type="radio"
                                                    name="tipo2" value="CUENTA" id="flexRadioDefaul" required>
                                            </div>

                                            <div class="col p-2 pt-0 form-check">
                                                <label class="form-check-label text-dark"
                                                    for="flexRadioDefault1">Caja</label>
                                                <input class="form-check-input cajaCheck2" type="radio"
                                                    name="tipo2" value="CAJA" id="flexRadioDefaul" required
                                                    checked>
                                            </div>
                                        @else
                                            <div class="col p-2 pt-0 form-check">
                                                <label class="form-check-label text-dark"
                                                    for="flexRadioDefault1">Cuenta</label>
                                                <input class="form-check-input cuentaCheck2" type="radio"
                                                    name="tipo2" value="CUENTA" id="flexRadioDefaul" required
                                                    checked>
                                            </div>

                                            <div class="col p-2 pt-0 form-check">
                                                <label class="form-check-label text-dark" for="flexRadioDefault1">Caja
                                                </label>
                                                <input class="form-check-input cajaCheck2" type="radio"
                                                    name="tipo2" value="CAJA" id="flexRadioDefaul" required>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="row text-start">
                                        <div class="form-outline" id="cuentas2">
                                            <label class="form-label" for="form8Example4">Cuenta
                                                de Desembolso</label>
                                            <select name="cuenta" class="form-select cuentaselect2">
                                                <option value="">Selecciona ...</option>
                                                @foreach ($cuentas as $cuenta)
                                                    @foreach ($varManejoCuentas as $permiso1)
                                                        @if ($permiso1->id == $cuenta->id)
                                                            @if ($datos->id_cuenta == $cuenta->id && $datos->tipo_cuenta == 'CUENTA')
                                                                <option value="{{ $cuenta->id }}" selected>
                                                                    {{ $cuenta->descripcion }}
                                                                </option>
                                                            @else
                                                                <option value="{{ $cuenta->id }}">
                                                                    {{ $cuenta->descripcion }}
                                                                </option>
                                                            @endif
                                                        @endif
                                                    @endforeach
                                                @endforeach
                                            </select>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la
                                                información requerida.</div>
                                        </div>

                                        <div class="form-outline" id="cajas2">
                                            <label class="form-label" for="form8Example4">Caja de
                                                Desembolso</label>
                                            <select name="caja" class="form-select cajaselect2">
                                                <option value="">Selecciona ...</option>
                                                @foreach ($cajas as $caja)
                                                    @if ($caja->arqueo == 'Autorizado')
                                                        @if ($datos->id_cuenta == $caja->id && $datos->tipo_cuenta == 'CAJA')
                                                            <option value="{{ $caja->id }}" selected>
                                                                {{ $caja->nombre }}
                                                            </option>
                                                        @else
                                                            <option value="{{ $caja->id }}">
                                                                {{ $caja->nombre }}</option>
                                                        @endif
                                                    @else
                                                        <option value="{{ $caja->id }}" disabled>
                                                            {{ $caja->nombre }} (Arqueo
                                                            Pendiente)</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la
                                                información requerida.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <textarea class="form-control" placeholder="Escribe un comentario..." name="comentario" id="floatingTextarea2"
                                            style="height: 50px">{{ $datos->comentario }}</textarea>
                                        <label for="floatingTextarea2"
                                            style="margin-top:-10px;margin-left:10px;">Comentario</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row p-4 pt-1 pb-1">
                                <div class="col-lg-4 col-12">
                                    <label for="">Estado de cuenta</label>
                                    <div style="padding: 10px 0px 30px 0px;">
                                        <label for="file1" class="drop-container2">
                                            <span class="drop-title1">Subir Estado de Cuenta</span>
                                            Archivo importado PDF
                                            <label class="btn btn-primary mb-2" for="estadoCuentaEdit">Seleccionar
                                                archivo</label>
                                            <input type="file" id="estadoCuentaEdit" class="form-control d-none"
                                                name="estadoCuentaEdit"
                                                onchange="this.nextElementSibling.textContent = this.files[0]?.name || 'No se ha seleccionado ningún archivo';"
                                                required>
                                            <small class="text-muted mt-2">No se ha seleccionado
                                                ningún
                                                archivo</small>
                                            <div class="valid-feedback"> ¡Se ve bien!</div>
                                            <div class="invalid-feedback"> Por favor, completa la
                                                información
                                                requerida.</div>
                                        </label>
                                    </div>
                                    @if ($datos->estado_cuenta != null)
                                        <label class="text-success">Estado de cuenta subido</label>
                                    @endif
                                </div>

                                <div class="col-lg-4 col-12">
                                    <label for="">Pagaré</label>
                                    <div style="padding: 10px 0px 30px 0px;">
                                        <label for="file1" class="drop-container2">
                                            <span class="drop-title1">Subir Pagaré</span>
                                            Archivo importado PDF
                                            <label class="btn btn-primary mb-2" for="pagareEdit">Seleccionar
                                                archivo</label>
                                            <input type="file" id="pagareEdit" class="form-control d-none"
                                                name="pagareEdit"
                                                onchange="this.nextElementSibling.textContent = this.files[0]?.name || 'No se ha seleccionado ningún archivo';"
                                                required>
                                            <small class="text-muted mt-2">No se ha seleccionado
                                                ningún
                                                archivo</small>
                                            <div class="valid-feedback"> ¡Se ve bien!</div>
                                            <div class="invalid-feedback"> Por favor, completa la
                                                información
                                                requerida.</div>
                                        </label>
                                    </div>
                                    @if ($datos->pagare != null)
                                        <label class="text-success">Pagaré subido</label>
                                    @endif
                                </div>

                                @if ($datos->tipo_credito == 'automovil')
                                    <div class="col-lg-4 col-12">
                                        <label for="">Factura</label>
                                        <div style="padding: 10px 0px 30px 0px;">
                                            <label for="file1" class="drop-container2">
                                                <span class="drop-title1">Subir Factura</span>
                                                Archivo importado PDF
                                                <label class="btn btn-primary mb-2" for="facturaEdit">Seleccionar
                                                    archivo</label>
                                                <input type="file" id="facturaEdit" class="form-control d-none"
                                                    name="facturaEdit"
                                                    onchange="this.nextElementSibling.textContent = this.files[0]?.name || 'No se ha seleccionado ningún archivo';">
                                                <small class="text-muted mt-2">No se ha seleccionado
                                                    ningún
                                                    archivo</small>
                                                <div class="valid-feedback"> ¡Se ve bien!</div>
                                                <div class="invalid-feedback"> Por favor, completa la
                                                    información
                                                    requerida.</div>
                                            </label>
                                        </div>
                                        @if ($datos->factura != null)
                                            <label class="text-success">Factura subido</label>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>


                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-danger rounded-5"
                                data-bs-dismiss="modal">Cerrar</button>
                            @if ($permisos3 == 'calcular_prestamo_emp')
                                <button class="btn bg-orange text-light rounded-4 push needs-validation"
                                    type="submit" name="calcular" id="calcular"
                                    onclick="prepararCalculoEdit('#Modal{{ $datos->id }}', '/editarprestamonomina/calcular')">Calcular
                                </button>
                            @else
                                <input class="btn bg-orange text-light rounded-4" type="submit" name="calcular"
                                    id="calcular" value="Calcular" disabled />
                            @endif

                            @if ($permisos4 == 'aut_pres_emp')
                                <button class="btn bg-success text-light rounded-4 push needs-validation"
                                    type="submit" name="autorizar" id="autorizar"
                                    onclick="prepararAutorizarEdit('#Modal{{ $datos->id }}', '/editarprestamonomina/autorizar')">
                                    Autorizar
                                </button>
                            @else
                                <input class="btn bg-success text-light rounded-4" type="submit" name="autorizar"
                                    id="autorizar" value="Autorizar" disabled />
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="Modalcomentario{{ $datos->id }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-center" id="exampleModalLabel">
                            Comentario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <form action="/PrestamoEmpleados/Comentario/{{ $datos->id }}/pendiente" method="POST" class="modern-form">
                        @csrf
                        <div class="modal-body">
                            @if ($datos->status_comentario == '1')
                                <div class="badge  text-wrap" style="width: 90%;">
                                    <p class="text-dark text-break"
                                        style="text-align: justify!important;font-size:12px!important;font-weight: normal!important;">
                                        <i class="text-primary fa-regular fa-comment"></i>
                                        {{ $datos->comentario }}
                                    </p>
                                </div>
                                <hr>
                                <h6 class="fs-8 mb-2">Remplazar Comentario</h6>
                            @endif
                            <div class="row mt-1">
                                <div class="form-floating">
                                    <textarea class="form-control fs-8" placeholder="Escribe un comentario..." name="comentario" id="floatingTextarea2"
                                        style="height: 50px"></textarea>
                                    <label for="floatingTextarea2" style="margin-top:-10px;margin-left:10px;">Nuevo
                                        Comentario</label>
                                </div>
                            </div>
                        </div>

                        <div class="container">
                            <div class="row p-3">
                                <button type="button" class="col btn btn-secondary fs-8 rounded-1 m-2 mt-0"  data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
                                <button type="submit" class="col-9 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/tableX.js') }}"></script>

<script>
    $(document).ready(function() {
        $("input[type=submit]").click(function() {
            var accion = $(this).attr('dir');
            $('.formulario1').attr('action', accion);
            $('.formulario1').submit();
        });
        $('#divFactura').hide();
    });


    function showHideFactura(option) {
        console.log('showHideFactura', option.value);
        const facturaDiv = $('#divFactura');
        if (option.value === 'automovil') {
            facturaDiv.show();
        } else {
            facturaDiv.hide();
        }
    }

    function prepararCalculo(ruta) {
        const form = document.getElementById('prestamoForm');
        const pagareInput = document.getElementById('pagare');
        const estadoCuentaInput = document.getElementById('estadoCuenta');
        pagareInput.removeAttribute('required'); // Quita el requerido
        estadoCuentaInput.removeAttribute('required'); // Quita el requerido
        form.action = ruta; // Cambia la acción del formulario
    }

    function prepararAutorizar(ruta) {
        const form = document.getElementById('prestamoForm');
        const pagareInput = document.getElementById('pagare');
        const estadoCuentaInput = document.getElementById('estadoCuenta');
        pagareInput.setAttribute('required', 'required'); // Asegura que siga siendo requerido
        estadoCuentaInput.setAttribute('required', 'required'); // Asegura que siga siendo requerido
        form.action = ruta; // Cambia la acción del formulario
    }

    function prepararCalculoEdit(modalTarget, ruta) {
        // Selecciona el modal con el ID especificado
        const modal = document.querySelector(modalTarget);
        if (!modal) {
            console.error('No se encontró el modal:', modalTarget);
            return;
        }

        // Selecciona el formulario dentro del modal
        const form = modal.querySelector('#prestamoFormEdit');
        if (!form) {
            console.error('No se encontró el formulario dentro del modal:', modalTarget);
            return;
        }

        // Quitar el atributo "required" de los campos específicos
        const camposNoRequeridos = ['pagareEdit', 'facturaEdit', 'estadoCuentaEdit'];
        camposNoRequeridos.forEach((campoId) => {
            const input = form.querySelector(`#${campoId}`);
            if (input) {
                input.removeAttribute('required');
            } else {
                console.warn(`No se encontró el campo con ID: ${campoId}`);
            }
        });

        // Actualiza la acción del formulario
        form.action = ruta;
    }


    function prepararAutorizarEdit(modalTarget, ruta) {
        const modal = document.querySelector(modalTarget);
        if (!modal) {
            console.error('No se encontró el modal:', modalTarget);
            return;
        }

        // Selecciona el formulario dentro del modal
        const form = modal.querySelector('#prestamoFormEdit');
        if (!form) {
            console.error('No se encontró el formulario dentro del modal:', modalTarget);
            return;
        }

        // Revisa el estado de cuenta
        const estadoCuentaLabel = Array.from(modal.querySelectorAll('label.text-success')).find(label =>
            label.textContent.includes("Estado de cuenta subido")
        );
        const estadoCuentaInput = modal.querySelector('#estadoCuentaEdit');
        if (!estadoCuentaLabel && (!estadoCuentaInput || !estadoCuentaInput.value)) {
            console.log('El estado de cuenta es requerido.');
            estadoCuentaInput.setAttribute('required', 'required');
        } else {
            estadoCuentaInput.removeAttribute('required');
        }

        // Revisa el pagaré
        const pagareLabel = Array.from(modal.querySelectorAll('label.text-success')).find(label =>
            label.textContent.includes("Pagaré subido")
        );
        const pagareInput = modal.querySelector('#pagareEdit');
        if (!pagareLabel && (!pagareInput || !pagareInput.value)) {
            console.log('El pagaré es requerido.');
            pagareInput.setAttribute('required', 'required');
        } else {
            pagareInput.removeAttribute('required');
        }

        // Revisa la factura
        const tipoCreditoSelect = modal.querySelector('select[name="tipocredito"]');
        const facturaInput = modal.querySelector('#facturaEdit');
        if (tipoCreditoSelect && tipoCreditoSelect.value === "automovil") {
            if (!facturaInput || !facturaInput.value) {
                console.log('El campo factura es requerido para tipo de crédito: automovil.');
                facturaInput.setAttribute('required', 'required');
            }
        } else {
            if (facturaInput) {
                facturaInput.removeAttribute('required');
            }
        }

        // Cambia la acción del formulario
        form.action = ruta;
    }
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

<script>
    $(document).ready(function() {
        $('#cajas').hide();
        $('#cuentas').hide();

        document.querySelector('.cuentaselect').required = true;
        document.querySelector('.cajaselect').required = true;

        $(".cuenta").on("click", function() {
            $('#cajas').hide();
            $('#cuentas').show();
            document.querySelector('.cuentaselect').required = true;
            document.querySelector('.cajaselect').required = false;
        });

        $(".caja").on("click", function() {
            $('#cajas').show();
            $('#cuentas').hide();
            document.querySelector('.cuentaselect').required = false;
            document.querySelector('.cajaselect').required = true;
        });
    });
</script>

<script>
    $(document).ready(function() {
        if ($(".cajaCheck2").is(":checked")) {
            $('.cajas2').show();
            $('.cuentas2').hide();
            document.querySelector('.cuentaselect2').required = false;
            document.querySelector('.cajaselect2').required = true;
        } else {
            $('.cajas2').hide();
            $('.cuentas2').show();
            document.querySelector('.cuentaselect2').required = true;
            document.querySelector('.cajaselect2').required = false;
        }

        $(".cajaCheck2").on("click", function() {
            $('.cajas2').show();
            $('.cuentas2').hide();
            document.querySelector('.cuentaselect2').required = false;
            document.querySelector('.cajaselect2').required = true;
        });

        $(".cuentaCheck2").on("click", function() {
            $('.cajas2').hide();
            $('.cuentas2').show();
            document.querySelector('.cuentaselect2').required = true;
            document.querySelector('.cajaselect2').required = false;
        });
    });
</script>
@endsection
