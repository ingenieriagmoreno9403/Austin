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
            echo 'Toast.fire({ icon: "warning",title: "?Opss...!", text: "Recuerde subir todo los formatos, solo se admiten archivos .pdf"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('successpagoAplicado'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "success",title: "?Pago Aplicado!", text: "Acci realizada correctamente"});';
            echo '</script>'; 
    @endphp
@endif


@php
    $countPendiente = 0;
    foreach ($varPresDet as $datos) {
        if ($datos->estado != 'S') {
            $countPendiente += $datos->pago_quincenal;
        }
    }
    function formatFecha($fecha)
    {
        return DateTime::createFromFormat('d/m/Y', $fecha)->format('Y-m-d');
    }
@endphp
@foreach ($varPresDet as $datos)
    @if ($datos->estado != 'S')
        @php
            $countPendiente += $datos->pago_quincenal;
        @endphp
    @endif
@endforeach

    <!-----Principal Area----->
    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
    <div class="container-fluid format_page bg-body">
        <div class="mb-2">
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
                                    <a href="/prestamosnominas/creditosEmpleadosCatalogo" class="text-muted text-decoration-none fs-8">
                                        <i class="fa-solid fa-chevron-left me-1"></i>Créditos
                                    </a>
                                </div>
                                <h2 class="mb-0 text-marino fw-bold">Detalle de Pr?stamo</h2>
                                <p class="text-muted mb-0">Registro de pagos por plazo</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col text-dark">
                    <div class="row text-start p-4 pt-3">
                        <div class="col-md-3 col-12 p-1">
                            <b>Empleado</b>
                            <b>{{ $id_empleado }}</b> {{ $nombre }}
                        </div>

                        <div class="col-md-2 col-12 p-1">
                            <b>No. Prestamo</b>
                            {{ $idpres }}
                        </div>

                        <div class="col-md-3 col-12 p-1 mb-2">
                            <b>Autorizo</b>
                            {{ $coordinador }}
                        </div>

                        <div class="col-md-2 col-6 center p-1 pt-0">
                            <a class="btn btn-primary fs-8 rounded-3 text-truncate" data-bs-toggle="collapse"
                                href="#collapseExample" role="button" aria-expanded="false"
                                aria-controls="collapseExample">
                                <i class="fa-solid fa-info-circle"></i> M?s Informaci
                            </a>
                        </div>

                        <div class="col-md-2 col-6 center p-1 pt-0">
                            <button type="button" class="btn btn-success fs-8 rounded-3 text-truncate"
                                data-bs-toggle="modal" data-bs-target="#pagoTodoModal"
                                @if ($countPendiente == 0) disabled @endif>
                                <i class="fa-solid fa-dollar-sign"></i> Pagar Todo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ------------------------- Informaci-------------------- --}}
        <div class="row">
            <div class="collapse" id="collapseExample">
                <div class="row p-4 pt-0">
                    <div class="col-md-3 col-6 border border-1 bg-light p-3 push">
                        <h5 class="text-orange">$ {{ $formattedNum = number_format($pago_total, 2) }}</h5>
                        <p class="fw-bold">Prestamo Total</p>
                    </div>

                    <div class="col-md-3 col-6 border border-1 bg-light p-3 push">
                        <h5 class="text-success">$ {{ $formattedNum = number_format($pagos, 2) }}</h5>
                        <p class="fw-bold">Saldo Actual</p>
                    </div>

                    @for ($i = 0; $i < 1; $i++)
                        <div class="col-md-3 col-6 border border-1 bg-light p-3 push">
                            <h5>{{ ucfirst($varPresDet[$i]->tasa) }}</h5>
                            <p class="fw-bold">Tasa</p>
                        </div>

                        <div class="col-md-3 col-6 border border-1 bg-light p-3 push">
                            <h5>% {{ $varPresDet[$i]->porcentaje }}</h5>
                            <p class="fw-bold">Porcentaje</p>
                        </div>

                        <div class="col-md-3 col-6 border border-1 bg-light p-3 push">
                            <h5 class="text-primary">$ {{ $formattedNum = number_format($monto, 2) }}</h5>
                            <p class="fw-bold">Capital</p>
                        </div>

                        <div class="col-md-3 col-6 border border-1 bg-light p-3 push">
                            <h5 class="text-danger">$ {{ number_format(ucfirst($varPresDet[$i]->interesredondeado), 2) }}
                            </h5>
                            <p class="fw-bold">Inter?s</p>
                        </div>

                        <div class="col-md-3 col-6 border border-1 bg-light p-3 push">
                            <h5 class="text-secondary">$ {{ number_format($varPresDet[$i]->ivainteres, 2) }}</h5>
                            <p class="fw-bold">IVA Inter?s</p>
                        </div>

                        <div class="col-md-3 col-6 border border-1 bg-light p-3 push">
                            <h5>$ {{ number_format($varPresDet[$i]->totalredondeado, 2) }}</h5>
                            <p class="fw-bold">Total</p>
                        </div>
                    @endfor

                </div>
            </div>
        </div>

        <div class="row">
            <table class="table table-striped" id="tablePrestamoNominaDetalle">
                <thead>
                    <tr>
                        <th scope="col" class="fs-6"># Plazo</th>
                        <th scope="col" class="fs-6">Estado</th>
                        <th scope="col" class="fs-6">Pago Quincenal</th>
                        <th scope="col" class="fs-6">Saldo Nuevo</th>
                        <th scope="col" class="fs-6">Fecha Pago</th>
                        @if ($tipo != 'pendientes' && $permisos1 == 'aplicar_pago_prestamosEmp')
                            <th scope="col" class="fs-6">Aplicar Pago</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($varPresDet as $datos)
                        <tr>
                            <td class="fs-8">{{ $datos->plazo }}</td>
                            <td>
                                @if ($datos->estado == 'P')
                                    <div class="btn bg_secondary rounded-2 p-1 pointer_none fs-8 text-secondary">
                                        Pendiente</div>
                                @endif
                                @if ($datos->estado == 'A')
                                    <div class="btn bg_secondary rounded-2 p-1 pointer_none fs-8 text-secondary">
                                        Pendiente</div>
                                @endif
                                @if ($datos->estado == 'C')
                                    <div class="btn bg_danger rounded-2 p-1 pointer_none fs-8 text-danger">
                                        Cancelado</div>
                                @endif
                                @if ($datos->estado == 'S')
                                    <div class="btn bg_success rounded-2 p-1 pointer_none fs-8 text-success">
                                        Pagado</div>
                                @endif
                            </td>
                            <td class="fs-8">$
                                {{ $formattedNum = number_format($datos->pago_quincenal, 2) }}</td>
                            <td class="fs-8">$
                                {{ $formattedNum = number_format($datos->saldo_nuevo, 2) }}</td>
                            <td class="fs-8">{{ $datos->fecha_pago }}</td>
                            @if ($tipo != 'pendientes' && $permisos1 == 'aplicar_pago_prestamosEmp')
                                <td>
                                    @if ($datos->estado == 'S')
                                        <button type="button" class="btn btn-secondary fs-8 rounded-3 text-truncate"
                                            disabled>
                                            <i class="fa-solid fa-dollar-sign"></i> Pagar
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-success fs-8 rounded-3 text-truncate"
                                            data-bs-toggle="modal" data-bs-target="#pagoModal{{ $datos->plazo }}">
                                            <i class="fa-solid fa-dollar-sign"></i> Pagar
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

    @foreach ($varPresDet as $datos)
        <!-- Modal -->
        <form action="/PrestamoEmpleados/AplicarPago/{{ $idpres }}" method="POST"
            class="g-3 bg-body needs-validation form modern-form" novalidate>
            @csrf
            <div class="modal fade" id="pagoModal{{ $datos->plazo }}" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <p class="text-success">APLICAR PAGO | <b
                                    class="fs-8 fw-normal text-dark">{{ $nombre }}</b>
                            </p>
                            <input type="text" name="iddistribuidor" hidden value="{{ $id_empleado }}">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body text-start row">
                            <div class="col-md-5 card rounded-4 p-3 m-3">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-outline">
                                            <h3>Plazo {{ $datos->plazo }}
                                            </h3>
                                            <p>{{ $datos->fecha_pago }}
                                            </p>

                                        </div>
                                    </div>

                                    <div class="col text-center">
                                        <h3 class="text-success"><i class="fa-solid fa-right-left"></i>
                                        </h3>
                                    </div>

                                    <div class="col-md-12">
                                        <h3>$
                                            {{ $formattedNum = number_format($datos->pago_quincenal, 2) }}
                                        </h3>
                                    </div>
                                </div>
                            </div>

                            <div class="col card rounded-4 p-3 m-3">
                                <div class="form-outline mb-2">
                                    <label class="form-label" for="form8Example4">Fecha de
                                        pago</label>
                                    <input type="date" class="form-control fs-8" name="fecha_pago" id="fecha_pago"
                                        maxlength="10" required />
                                    <div class="text-success valid-feedback">
                                        ?Se ve bien!</div>
                                    <div class="text-danger invalid-feedback">
                                        ?Por favor, completa la informaci
                                        requerida!</div>
                                </div>

                                <div class="form-outline">
                                    <label class="form-label fs-8" for="form8Example4">Cuenta que
                                        desea
                                        afectar</label>
                                    <!--
                                            <select name="cuenta" class="form-select">
                                                <option value="" selected>
                                                    Seleccionar...</option>
                                                @foreach ($cuentas as $cuenta)
    @foreach ($varManejoCuentas as $permiso1)
    @if ($permiso1->id == $cuenta->id)
    <option value="{{ $cuenta->id }}">
                                                                {{ $cuenta->descripcion }}
                                                            </option>
    @endif
    @endforeach
    @endforeach
                                            </select>
                                            -->

                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col mx-3 p-3 pt-1 pb-1 form-check">
                                                <input class="form-check-input cuenta" type="radio" name="tipo"
                                                    value="CUENTA" onclick="cuenta()" id="flexRadioDefault1" required>
                                                <label class="form-check-label" for="flexRadioDefault1">
                                                    Cuenta
                                                </label>
                                            </div>

                                            <div class="col p-3 pt-1 pb-1 form-check">
                                                <input class="form-check-input caja" type="radio" name="tipo"
                                                    value="CAJA" onclick="caja()" id="flexRadioDefault1" required>
                                                <label class="form-check-label" for="flexRadioDefault1">
                                                    Caja
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-outline" id="cuentas">
                                            <label class="form-label" for="form8Example4">Cuenta:</label>
                                            <select name="cuenta" class="form-select cuentaselect">
                                                <option value="">Selecciona ...</option>
                                                @foreach ($cuentas as $cuenta)
                                                    @foreach ($varManejoCuentas as $permiso1)
                                                        @if ($permiso1->id == $cuenta->id)
                                                            <option value="{{ $cuenta->id }}">
                                                                {{ $cuenta->descripcion }}
                                                                ({{ $cuenta->saldo_actual }})
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                @endforeach
                                            </select>
                                            <div class="valid-feedback">?Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la informaci requerida.
                                            </div>
                                        </div>

                                        <div class="form-outline" id="cajas">
                                            <label class="form-label" for="form8Example4">Caja:</label>
                                            <select name="caja" class="form-select cajaselect">
                                                <option value="">Selecciona ...</option>
                                                @foreach ($cajas as $caja)
                                                    @if ($caja->arqueo == 'Autorizado')
                                                        <option value="{{ $caja->id }}">{{ $caja->nombre }}</option>
                                                    @else
                                                        <option value="{{ $caja->id }}" disabled>{{ $caja->nombre }}
                                                            (Arqueo
                                                            Pendiente)
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <div class="valid-feedback">?Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la informaci requerida.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Escribe un comentario..." name="descripcion" id="floatingTextarea2"
                                        style="height: 50px" required></textarea>
                                    <label for="floatingTextarea2"
                                        style="margin-top:-10px;margin-left:10px;">Descripcion</label>
                                </div>
                            </div>

                            <div>
                                <input type="date" class="form-control fs-8" name="fecha_quincenal"
                                    id="fecha_quincenal" value="{{ formatFecha($datos->fecha_pago) }}" hidden required />
                                <div class="text-success valid-feedback">
                                    ?Se ve bien!</div>
                                <div class="text-danger invalid-feedback">
                                    ?Por favor, completa la informaci
                                    requerida!</div>

                                <input type="number" class="form-control text fs-8" name="pago_quincenal"
                                    id="pago_quincenal" value="{{ $datos->pago_quincenal }}" hidden required />
                                <div class="text-success valid-feedback">
                                    ?Se ve bien!</div>
                                <div class="text-danger invalid-feedback">
                                    ?Por favor, completa la informaci
                                    requerida!</div>

                                <input type="number" class="form-control text fs-8" name="plazo" id="plazo"
                                    value="{{ $datos->plazo }}" hidden required />
                                <div class="text-success valid-feedback">
                                    ?Se ve bien!</div>
                                <div class="text-danger invalid-feedback">
                                    ?Por favor, completa la informaci
                                    requerida!</div>

                                <input type="number" class="form-control text fs-8" name="idempleado" id="idempleado"
                                    value="{{ $id_empleado }}" hidden required />
                                <div class="text-success valid-feedback">
                                    ?Se ve bien!</div>
                                <div class="text-danger invalid-feedback">
                                    ?Por favor, completa la informaci
                                    requerida!</div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-danger rounded-5 fs-8"
                                data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i>
                                Cerrar</button>
                            <button type="submit" class="btn btn-success rounded-5 fs-8"><i
                                    class="fa-solid fa-dollar"></i>
                                Aplicar</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    @endforeach

    <!-- Modal Pagar todo -->
    <form action="/PrestamoEmpleados/AplicarPagoTodos/{{ $idpres }}" method="POST"
        class="g-3 bg-body needs-validation form modern-form" novalidate>
        @csrf
        <div class="modal fade" id="pagoTodoModal" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <p class="text-success">APLICAR PAGO | <b
                                class="fs-8 fw-normal text-dark">{{ $nombre }}</b>
                        </p>
                        <input type="text" name="idempleado" hidden value="{{ $id_empleado }}">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body text-start row">
                        <div class="col-md-5 card rounded-4 p-3 m-3">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-outline">
                                        <h3>Saldo Actual
                                        </h3>
                                    </div>
                                </div>

                                <div class="col text-center">
                                    <h3 class="text-success"><i class="fa-solid fa-right-left"></i>
                                    </h3>
                                </div>

                                <div class="col-md-12">
                                    <h3>$
                                        {{ number_format($countPendiente, 2) }}
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <div class="col card rounded-4 p-3 m-3">
                            <div class="form-outline mb-2">
                                <label class="form-label" for="form8Example4">Fecha de
                                    pago</label>
                                <input type="date" class="form-control fs-8" name="fecha_pago" id="fecha_pago"
                                    maxlength="10" required />
                                <div class="text-success valid-feedback">
                                    ?Se ve bien!</div>
                                <div class="text-danger invalid-feedback">
                                    ?Por favor, completa la informaci
                                    requerida!</div>
                            </div>

                            <div class="form-outline">
                                <label class="form-label fs-8" for="form8Example4">Cuenta que
                                    desea
                                    afectar</label>
                                <!--
                                        <select name="cuenta" class="form-select" required>
                                            <option value="" selected>
                                                Seleccionar...</option>
                                            @foreach ($cuentas as $cuenta)
    @foreach ($varManejoCuentas as $permiso1)
    @if ($permiso1->id == $cuenta->id)
    <option value="{{ $cuenta->id }}">
                                                            {{ $cuenta->descripcion }}
                                                        </option>
    @endif
    @endforeach
    @endforeach
                                        </select>
                                        -->

                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col mx-3 p-3 pt-1 pb-1 form-check">
                                            <input class="form-check-input cuenta" type="radio" name="tipo"
                                                value="CUENTA" onclick="cuenta()" id="flexRadioDefault1" required>
                                            <label class="form-check-label" for="flexRadioDefault1">
                                                Cuenta
                                            </label>
                                        </div>

                                        <div class="col p-3 pt-1 pb-1 form-check">
                                            <input class="form-check-input caja" type="radio" name="tipo"
                                                value="CAJA" onclick="caja()" id="flexRadioDefault1" required>
                                            <label class="form-check-label" for="flexRadioDefault1">
                                                Caja
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-outline" id="cuentas">
                                        <label class="form-label" for="form8Example4">Cuenta:</label>
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
                                        <div class="valid-feedback">?Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la informaci requerida.</div>
                                    </div>

                                    <div class="form-outline" id="cajas">
                                        <label class="form-label" for="form8Example4">Caja:</label>
                                        <select name="caja" class="form-select cajaselect">
                                            <option value="">Selecciona ...</option>
                                            @foreach ($cajas as $caja)
                                                @if ($caja->arqueo == 'Autorizado')
                                                    <option value="{{ $caja->id }}">{{ $caja->nombre }}</option>
                                                @else
                                                    <option value="{{ $caja->id }}" disabled>{{ $caja->nombre }}
                                                        (Arqueo
                                                        Pendiente)
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <div class="valid-feedback">?Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la informaci requerida.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-floating">
                                <textarea class="form-control" placeholder="Escribe un comentario..." name="descripcion" id="floatingTextarea2"
                                    style="height: 50px" required></textarea>
                                <label for="floatingTextarea2"
                                    style="margin-top:-10px;margin-left:10px;">Descripcion</label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger rounded-5 fs-8" data-bs-dismiss="modal"><i
                                class="fa-solid fa-xmark"></i>
                            Cerrar</button>
                        <button type="submit" class="btn btn-success rounded-5 fs-8"><i class="fa-solid fa-dollar"></i>
                            Aplicar</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/tableX.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Manejo del evento cuando se abre un modal
            $(document).on("shown.bs.modal", function(e) {
                const modal = $(e.target); // El modal actual que se abri?

                // Ocultar ambos contenedores al inicio
                modal.find('#cajas').hide();
                modal.find('#cuentas').hide();

                // Configurar required en false por defecto
                modal.find('.cuentaselect').prop('required', false);
                modal.find('.cajaselect').prop('required', false);

                // Evento para el radio "cuenta"
                modal.find(".cuenta").on("click", function() {
                    modal.find('#cajas').hide();
                    modal.find('#cuentas').show();
                    modal.find('.cuentaselect').prop('required', true);
                    modal.find('.cajaselect').prop('required', false);
                });

                // Evento para el radio "caja"
                modal.find(".caja").on("click", function() {
                    modal.find('#cajas').show();
                    modal.find('#cuentas').hide();
                    modal.find('.cuentaselect').prop('required', false);
                    modal.find('.cajaselect').prop('required', true);
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            // Manejo del evento cuando se abre un modal
            $(document).on("shown.bs.modal", function(e) {
                const modal = $(e.target); // El modal actual que se abri?

                // Ocultar ambos contenedores al inicio
                modal.find('#cajas2').hide();
                modal.find('#cuentas2').hide();

                // Configurar required en false por defecto
                modal.find('.cuentaselect2').prop('required', false);
                modal.find('.cajaselect2').prop('required', false);

                // Evento para el radio "cuenta"
                modal.find(".cuenta2").on("click", function() {
                    modal.find('#cajas2').hide();
                    modal.find('#cuentas2').show();
                    modal.find('.cuentaselect2').prop('required', true);
                    modal.find('.cajaselect2').prop('required', false);
                });

                // Evento para el radio "caja"
                modal.find(".caja2").on("click", function() {
                    modal.find('#cajas2').show();
                    modal.find('#cuentas2').hide();
                    modal.find('.cuentaselect2').prop('required', false);
                    modal.find('.cajaselect2').prop('required', true);
                });
            });
        });
    </script>
@endsection
