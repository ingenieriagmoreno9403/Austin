@extends('layouts.app')

@section('css')
<link href="{{ asset('css/inputfile.css') }}" rel="stylesheet">
@endsection

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
@elseif($mensaje = Session::get('warningCantidadEgreso'))
  @php
          echo '<script language="JavaScript">';
          echo 'const Toast = Swal.mixin({';
          echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
          echo 'didOpen: (toast) => {';
          echo '  toast.onmouseenter = Swal.stopTimer;';
          echo '  toast.onmouseleave = Swal.resumeTimer;}});';
          echo 'Toast.fire({ icon: "info",title: "¡No es posible retirar esa cantidad!", text: "Pruebe como otra cantidad que este dentro del rango de saldo en la cuenta"});';
          echo '</script>'; 
  @endphp
@elseif($mensaje = Session::get('warningCuenta'))
  @php
          echo '<script language="JavaScript">';
          echo 'const Toast = Swal.mixin({';
          echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
          echo 'didOpen: (toast) => {';
          echo '  toast.onmouseenter = Swal.stopTimer;';
          echo '  toast.onmouseleave = Swal.resumeTimer;}});';
          echo 'Toast.fire({ icon: "info",title: "¡No se selecciono bien la cuenta!", text: "Asegurese de llenar todos los campos para una transferencia exitosa"});';
          echo '</script>'; 
  @endphp
@elseif($mensaje = Session::get('arqueoNoAut'))
  @php
          echo '<script language="JavaScript">';
          echo 'const Toast = Swal.mixin({';
          echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
          echo 'didOpen: (toast) => {';
          echo '  toast.onmouseenter = Swal.stopTimer;';
          echo '  toast.onmouseleave = Swal.resumeTimer;}});';
          echo 'Toast.fire({ icon: "info",title: "¡Arqueo pendiente de autorizar!", text: "Esta acción no es posibles realizar hasta autorizar arqueo pendiente"});';
          echo '</script>'; 
  @endphp
@elseif($mensaje = Session::get('warningPermiso'))
@php
        echo '<script language="JavaScript">';
        echo 'const Toast = Swal.mixin({';
        echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
        echo 'didOpen: (toast) => {';
        echo '  toast.onmouseenter = Swal.stopTimer;';
        echo '  toast.onmouseleave = Swal.resumeTimer;}});';
        echo 'Toast.fire({ icon: "info",title: "¡Ya tiene el permiso!", text: "El usuario ya cuenta con ese permiso"});';
        echo '</script>'; 
@endphp
@endif

@php($meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"))
@php($mesNombre = $meses[$mes-1])

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Tesoreria/Movimientos" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Movimientos
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Listado de {{ $tipo }}</h2>
                        <p class="text-muted mb-0">Gestión y control de {{ strtolower($tipo) }}</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                  @if($tipo == "Cajas")
                      @if (!empty($notificaciones) && count($notificaciones) > 0)
                          <button type="button" class="btn btn-baseColor-light position-relative" id="liveToastBtn">
                              <i class="fa-solid fa-bell"></i>
                              <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
                                  <span class="visually-hidden">{{$totalnotis}}</span>
                              </span>
                          </button>
                      @else
                          <button type="button" class="btn btn-baseColor-light" id="liveToastBtn">
                              <i class="fa-regular fa-bell"></i>
                          </button>
                      @endif
                  @endif
                </div>
            </div>
        </div>
    </div>
      
    <div class="row">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                  <tr class="table-light text-secondary">
                    <th class="text-center fw-bold text-truncate border-0">
                        <i class="fas fa-tag me-1 text-secondary"></i>Nombre
                    </th>
                    <th class="text-center fw-bold border-0">
                        <i class="fas fa-circle me-1 text-secondary"></i>Estado
                    </th>
                    <th class="text-center fw-bold border-0">
                        <i class="fas fa-dollar-sign me-1 text-secondary"></i>Saldo Inicial
                    </th>
                    <th class="text-center fw-bold border-0">
                        <i class="fas fa-wallet me-1 text-secondary"></i>Saldo Actual
                    </th>
                    <th class="text-center fw-bold border-0">
                        <i class="fas fa-building me-1 text-secondary"></i>Pertenece
                    </th>
                    <th class="text-center fw-bold border-0">
                        <i class="fas fa-tools me-1 text-secondary"></i>Herramientas
                    </th>
                  </tr>
                </thead>

                <tbody>
                    @php($arqueo = "") 
                    @php($accion = "") 
                    @php($table = "table-light")
                    @php($accion = "") 


                    @foreach($varManejo as $datos)
                      @if($datos->tipo == "caja")
                          @php($arqueo = $datos->arqueo) 
                          @if($arqueo == "Creado")
                            @php($table = "table-primary")
                            @php($accion = "disabled") 
                          @else
                            @php($table = "table-light")
                            @php($accion = "") 
                          @endif
                      @endif

                        <tr class="boder-sec">
                        
                          <td class="{{$table}} align-middle">
                            <div class="d-flex align-items-center">
                              <span class="fw-bold">{{$datos->nombre}}</span>
                            </div>
                          </td>

                          <td class="{{$table}} text-truncate align-middle text-center">
                            @if($datos->status == "A")
                              <span class="badge badge-success text-light fw-bold">
                                <i class="fas fa-check-circle me-1"></i>Activa
                              </span>
                            @endif
                            @if($datos->status == "C")
                              <span class="badge badge-danger fw-bold">
                                <i class="fas fa-times-circle me-1"></i>Cancelada
                              </span>
                            @endif
                          </td>
                    

                          <td class="{{$table}} text-truncate align-middle text-center">
                            <span class="fw-bold text-success">
                              <i class="fas fa-dollar-sign me-1"></i>
                              {{number_format($datos->saldo_inicial, 2)}}
                            </span>
                          </td>

                          <td class="{{$table}} text-truncate align-middle text-center">
                            <span class="fw-bold text-primary">
                             $ {{number_format($datos->saldo_actual, 2)}}
                            </span>
                          </td>

                          <td class="{{$table}} text-truncate align-middle text-center text-muted">
                            {{Str::limit($datos->pertenencia, 20)}}
                          </td>

                          @if ($tipo == "Cajas")
                            <td class="{{$table}} td-actions align-middle text-center">
                              <div class="btn-group btn-group-sm" role="group">
                                @if($permisos4 == "movimientos_ingresar")
                                      @if($datos->status != "C")
                                        <button class="btn btn-success btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#ModalIngresar{{$datos->id}}" {{$accion}}>
                                          <i class="fa-solid fa-plus me-1"></i>Ingreso
                                        </button>
                                      @else
                                        <button class="btn btn-secondary btn-sm" type="button" disabled>
                                          <i class="fa-solid fa-plus me-1"></i>Ingreso
                                        </button>
                                      @endif
                                    @else
                                      <button class="btn btn-secondary btn-sm" type="button" disabled>
                                        <i class="fa-solid fa-plus me-1"></i>Ingreso
                                      </button>
                                    @endif

                                  @if($permisos1 == "movimientos_transferir")
                                  @if($datos->status != "C")
                                    <button class="btn btn-warning btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#ModalTransferir{{$datos->id}}" {{$accion}}>
                                      <i class="fa-solid fa-right-left me-1"></i>Transferencia
                                    </button>
                                  @else
                                    <button class="btn btn-secondary btn-sm" type="button" disabled>
                                      <i class="fa-solid fa-right-left me-1"></i>Transferencia
                                    </button>
                                  @endif
                                @else
                                  <button class="btn btn-secondary btn-sm" type="button" disabled>
                                    <i class="fa-solid fa-right-left me-1"></i>Transferencia
                                  </button>
                                @endif

                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalGasto{{$datos->id}}" {{$accion}}>
                                  <i class="fa-solid fa-minus me-1"></i>Gasto
                                </button>

                                @if($permisos2 == "movimientos_consultar")
                                  <a class="btn btn-primary btn-sm" href="/Tesoreria/Historial/ConsultarMovimientos/{{$tipo}}/{{$datos->id}}">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i>Asientos Contables
                                  </a>
                                @else
                                  <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fa-solid fa-magnifying-glass me-1"></i>Asientos Contables
                                  </button>
                                @endif

                                @if($tipo == "Cajas")
                                  @if($arqueo == "Creado")
                                      <a href="/Tesoreria/Historial/ControlArqueos/{{$tipo}}/{{$datos->idempresa}}/{{$datos->id}}" class="btn btn-success btn-sm position-relative" title="Arqueo pendiente por autorizar">
                                        <i class="fa-solid fa-chart-line me-1"></i>Arqueo
                                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                          <span class="visually-hidden">New alerts</span>
                                        </span>
                                      </a>
                                  @else
                                    <a href="/Tesoreria/Historial/ControlArqueos/{{$tipo}}/{{$datos->idempresa}}/{{$datos->id}}" class="btn btn-success btn-sm">
                                      <i class="fa-solid fa-chart-line me-1"></i>Arqueo
                                    </a>
                                  @endif
                                @endif
                              </div>
                            </td> 
                          @else
                            <td class="{{$table}} td-actions align-middle text-center">
                              <div class="btn-group btn-group-sm" role="group">
                                @if($permisos4 == "movimientos_ingresar")
                                      @if($datos->status != "C")
                                        <button class="btn btn-success btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#ModalIngresar{{$datos->id}}" {{$accion}}>
                                          <i class="fa-solid fa-plus me-1"></i>Ingresar
                                        </button>
                                      @else
                                        <button class="btn btn-secondary btn-sm" type="button" disabled>
                                          <i class="fa-solid fa-plus me-1"></i>Ingresar
                                        </button>
                                      @endif
                                    @else
                                      <button class="btn btn-secondary btn-sm" type="button" disabled>
                                        <i class="fa-solid fa-plus me-1"></i>Ingresar
                                      </button>
                                    @endif

                                  @if($permisos1 == "movimientos_transferir")
                                  @if($datos->status != "C")
                                    <button class="btn btn-warning btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#ModalTransferir{{$datos->id}}" {{$accion}}>
                                      <i class="fa-solid fa-right-left me-1"></i>Transferencia
                                    </button>
                                  @else
                                    <button class="btn btn-secondary btn-sm" type="button" disabled>
                                      <i class="fa-solid fa-right-left me-1"></i>Transferencia
                                    </button>
                                  @endif
                                @else
                                  <button class="btn btn-secondary btn-sm" type="button" disabled>
                                    <i class="fa-solid fa-right-left me-1"></i>Transferencia
                                  </button>
                                @endif

                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalGasto{{$datos->id}}" {{$accion}}>
                                  <i class="fa-solid fa-minus me-1"></i>Gasto
                                </button>

                                @if($permisos2 == "movimientos_consultar")
                                  <a class="btn btn-primary btn-sm" href="/Tesoreria/Movimientos/ConsultarMovimientos/{{$tipo}}/{{$datos->id}}">
                                    <i class="fa-solid fa-magnifying-glass me-1"></i>Consultar
                                  </a>
                                @else
                                  <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fa-solid fa-magnifying-glass me-1"></i>Consultar
                                  </button>
                                @endif

                                @if($tipo == "Cajas")
                                  @if($arqueo == "Creado")
                                      <a href="/Tesoreria/Movimientos/ControlArqueos/{{$tipo}}/{{$datos->idempresa}}/{{$datos->id}}" class="btn btn-success btn-sm position-relative" title="Arqueo pendiente por autorizar">
                                        <i class="fa-solid fa-chart-line me-1"></i>Arqueo
                                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                          <span class="visually-hidden">New alerts</span>
                                        </span>
                                      </a>
                                  @else
                                    <a href="/Tesoreria/Movimientos/ControlArqueos/{{$tipo}}/{{$datos->idempresa}}/{{$datos->id}}" class="btn btn-success btn-sm">
                                      <i class="fa-solid fa-chart-line me-1"></i>Arqueo
                                    </a>
                                  @endif
                                @endif
                              </div>
                            </td> 
                          @endif

                          

                          
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>  
    </div>
</div>

@foreach($varManejo as $datos)
  <!-- Modal Ingresar Cuenta -->
  <div class="modal fade" id="ModalIngresar{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-success text-white border-0">
          <h5 class="modal-title fw-bold" id="exampleModalLabel">
            <i class="fas fa-plus-circle me-2"></i>Ingresar Saldo
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="/Tesoreria/Movimientos/Ingresar/{{$tipo}}/{{$datos->id}}" method="POST" class="needs-validation" novalidate>
          @csrf
          <div class="modal-body p-4">
            <div class="row mb-4">
              <div class="col-12">
                <div class="card bg-success bg-opacity-10 border-success border-opacity-25">
                  <div class="card-body text-center">
                    <i class="fas fa-plus-circle fa-3x text-success mb-3"></i>
                    <h6 class="fw-bold text-success">A la cuenta "{{$datos->nombre}}" se le ingresará</h6>
                    <p class="text-muted mb-0">Saldo actual: <span class="fw-bold text-success">${{number_format($datos->saldo_actual, 2)}}</span></p>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-12">
                <label class="form-label fw-bold">
                  <i class="fas fa-dollar-sign me-1 text-success"></i>Cantidad a Ingresar
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-success text-white">
                    <i class="fas fa-plus"></i>
                  </span>
                  <input type="number" min="1" class="form-control border-success" placeholder="0.00" name="saldo_ingresar" id="saldo_ingresar" maxlength="10" required />
                </div>
                <div class="valid-feedback">
                  <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                </div>
                <div class="invalid-feedback">
                  <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <label class="form-label fw-bold">
                  <i class="fas fa-comment me-1 text-primary"></i>Información de Movimiento
                </label>
                <textarea class="form-control border-primary" placeholder="Describe el motivo del ingreso..." name="descripcion" id="floatingTextarea2" maxlength="250" rows="4" required></textarea>
                <div class="valid-feedback">
                  <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                </div>
                <div class="invalid-feedback">
                  <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fas fa-times me-1"></i>Cancelar
            </button>
            <button type="submit" class="btn btn-success">
              <i class="fas fa-check me-1"></i>Confirmar Ingreso
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Transferir Cuenta -->
  <div class="modal fade" id="ModalTransferir{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-warning text-white border-0">
          <h5 class="modal-title fw-bold" id="exampleModalLabel">
            <i class="fas fa-exchange-alt me-2"></i>Traspaso de Saldo
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form action="/Tesoreria/Movimientos/Traspasos/{{$tipo}}/{{$datos->id}}" method="POST" class="needs-validation" novalidate>
          @csrf
          <div class="modal-body p-4">
            <div class="row mb-4">
              <div class="col-12">
                <div class="card bg-warning bg-opacity-10 border-warning border-opacity-25">
                  <div class="card-body text-center">
                    <i class="fas fa-exchange-alt fa-3x text-warning mb-3"></i>
                    <h6 class="fw-bold text-warning">Cuenta "{{$datos->nombre}}" transfiere a</h6>
                    <p class="text-muted mb-0">Saldo disponible: <span class="fw-bold text-warning">${{number_format($datos->saldo_actual, 2)}}</span></p>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-md-6">
                <label class="form-label fw-bold">
                  <i class="fas fa-dollar-sign me-1 text-warning"></i>Cantidad a Transferir
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-warning text-white">
                    <i class="fas fa-exchange-alt"></i>
                  </span>
                  <input type="number" min="1" class="form-control border-warning" placeholder="0.00" name="saldo_trasferir" id="saldo_trasferir" maxlength="10" required />
                </div>
                <div class="valid-feedback">
                  <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                </div>
                <div class="invalid-feedback">
                  <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold">
                  <i class="fas fa-layer-group me-1 text-primary"></i>Tipo de Destino
                </label>
                <div class="d-flex flex-column gap-2">
                  <div class="form-check">
                    <input class="form-check-input cuenta" type="radio" name="tipo_transferencia" id="cuenta" value="cuenta" onchange="ShowSelected();" required>
                    <label class="form-check-label fw-bold" for="cuenta">
                      <i class="fas fa-building-columns me-1 text-primary"></i>Cuenta
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input caja" type="radio" name="tipo_transferencia" id="caja" value="caja" onchange="ShowSelected();" required>
                    <label class="form-check-label fw-bold" for="caja">
                      <i class="fas fa-cash-register me-1 text-success"></i>Caja
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input caja_chica" type="radio" name="tipo_transferencia" id="caja_chica" value="caja_chica" onchange="ShowSelected();" required>
                    <label class="form-check-label fw-bold" for="caja_chica">
                      <i class="fas fa-circle-dollar-to-slot me-1 text-warning"></i>Caja Chica
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-12">
                <div class="cuentas" style="display: none;">
                  <label class="form-label fw-bold">
                    <i class="fas fa-building-columns me-1 text-primary"></i>Seleccionar Cuenta
                  </label>
                  <select class="form-select border-primary" name="transferenciaCuenta" id="cuentaselect">
                    <option value="">Selecciona una cuenta...</option>
                    @foreach($obtenerCuentas as $cuen)
                      @if($cuen->id != $datos->id)
                        <option value="{{$cuen->id}}">{{$cuen->descripcion}}</option>
                      @endif
                    @endforeach
                  </select>
                </div>

                <div class="cajas" style="display: none;">
                  <label class="form-label fw-bold">
                    <i class="fas fa-cash-register me-1 text-success"></i>Seleccionar Caja
                  </label>
                  <select class="form-select border-success" name="transferenciaCaja" id="cajaselect">
                    <option value="">Selecciona una caja...</option>
                    @foreach($obtenerCajas as $caj)
                      @if($caj->tipo == "caja")
                        @if($caj->id != $datos->id)
                          @if($caj->arqueo == "Creado")
                            <option value="{{$caj->id}}" disabled>{{$caj->nombre}} (Arqueo Pendiente)</option>
                          @else
                            <option value="{{$caj->id}}">{{$caj->nombre}}</option>
                          @endif
                        @endif
                      @endif
                    @endforeach
                  </select>
                </div>

                <div class="cajas_chicas" style="display: none;">
                  <label class="form-label fw-bold">
                    <i class="fas fa-circle-dollar-to-slot me-1 text-warning"></i>Seleccionar Caja Chica
                  </label>
                  <select class="form-select border-warning" name="transferenciaCajaChica" id="chicaselect">
                    <option value="">Selecciona una caja chica...</option>
                    @foreach($obtenerCajas as $caj1)
                      @if($caj1->tipo == "caja_chica")
                        @if($caj1->id != $datos->id)
                          <option value="{{$caj1->id}}">{{$caj1->nombre}}</option>
                        @endif
                      @endif
                    @endforeach
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-12">
                <label class="form-label fw-bold">
                  <i class="fas fa-comment me-1 text-primary"></i>Información de Movimiento
                </label>
                <textarea class="form-control border-primary" placeholder="Describe el motivo de la transferencia..." name="descripcion" id="floatingTextarea2" maxlength="250" rows="4" required></textarea>
                <div class="valid-feedback">
                  <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                </div>
                <div class="invalid-feedback">
                  <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="fas fa-times me-1"></i>Cancelar
            </button>
            <button type="submit" class="btn btn-warning">
              <i class="fas fa-check me-1"></i>Confirmar Transferencia
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Gastos -->
  <div class="modal fade" id="modalGasto{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg">
        <div class="modal-header bg-danger text-white border-0">
          <h5 class="modal-title fw-bold" id="exampleModalLabel">
            <i class="fas fa-minus-circle me-2"></i>Alta de Gasto
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <form action="/Tesoreria/Movimientos/Responsable/AplicarGasto/{{$datos->id}}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            @csrf
            <input type="text" value="{{$datos->pertenencia}}" name="sucursal" hidden>
            <input type="text" value="{{$datos->nombre}}" name="nombre" hidden>
            <input type="text" value="{{$mesNombre}}" name="mes" hidden>
            <input type="text" value="{{$tipo}}" name="tipo" hidden>

            <div class="modal-body p-4">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-danger bg-opacity-10 border-danger border-opacity-25">
                            <div class="card-body text-center">
                                <i class="fas fa-minus-circle fa-3x text-danger mb-3"></i>
                                <h6 class="fw-bold text-danger">Registrar Gasto en "{{$datos->nombre}}"</h6>
                                <p class="text-muted mb-0">Saldo disponible: <span class="fw-bold text-danger">${{number_format($datos->saldo_actual, 2)}}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="fas fa-list me-1 text-primary"></i>Tipo de Gasto
                        </label>
                        <select name="gasto" id="gasto" class="form-select border-primary" required>
                            <option value="" selected>Selecciona un gasto...</option>
                            @foreach($varGastos as $gastos)
                                @if($datos->idempresa == $gastos->id_empresa || $gastos->id_empresa == 0)
                                    <option value="{{$gastos->id}}">{{$gastos->nombre}}</option>
                                @endif
                            @endforeach 
                        </select>
                        <div class="valid-feedback">
                            <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                        </div>
                        <div class="invalid-feedback">
                            <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                        </div>
                    </div>
                
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="fas fa-dollar-sign me-1 text-danger"></i>Importe
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-danger text-white">
                                <i class="fas fa-minus"></i>
                            </span>
                            <input type="number" min="1" class="form-control border-danger" placeholder="0.00" name="saldo" id="saldo" maxlength="10" required />
                        </div>
                        <div class="valid-feedback">
                            <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                        </div>
                        <div class="invalid-feedback">
                            <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label fw-bold">
                            <i class="fas fa-comment me-1 text-primary"></i>Descripción
                        </label>
                        <textarea class="form-control border-primary" placeholder="Describe el motivo del gasto..." name="descripcion" id="floatingTextarea2" rows="3" required></textarea>
                        <div class="valid-feedback">
                            <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                        </div>
                        <div class="invalid-feedback">
                            <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                        </div>
                    </div>
                </div>
            
                <div class="row">
                    <div class="col-12">
                        <label class="form-label fw-bold">
                            <i class="fas fa-file-pdf me-1 text-danger"></i>Evidencia (PDF)
                        </label>
                        <div class="modern-file-input mt-2" data-input-id="evidencia-gasto-{{ $datos->id }}">
                            <div class="file-input-wrapper" id="wrapper-evidencia-gasto-{{ $datos->id }}">
                                <div class="file-input-content">
                                    <div class="file-input-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <p class="file-input-text">Arrastra tu archivo aquí</p>
                                    <p class="file-input-subtext">o haz clic para seleccionar</p>
                                    <small class="file-input-subtext d-block mt-2">Solo se admite formato .pdf</small>
                                </div>
                                <input type="file" name="evidencia" id="evidencia-gasto-{{ $datos->id }}"
                                    class="hidden-file-input validaPDF" required accept=".pdf"/>
                            </div>
                            <div class="file-preview" id="preview-evidencia-gasto-{{ $datos->id }}">
                                <div class="file-preview-item">
                                    <div class="file-preview-info">
                                        <div class="file-preview-icon">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <div class="file-preview-details">
                                            <h6 id="filename-evidencia-gasto-{{ $datos->id }}"></h6>
                                            <small id="filesize-evidencia-gasto-{{ $datos->id }}"></small>
                                        </div>
                                    </div>
                                    <button type="button" class="file-preview-remove"
                                        onclick="removeFile('evidencia-gasto-{{ $datos->id }}')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" id="progress-evidencia-gasto-{{ $datos->id }}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-check me-1"></i>Confirmar Gasto
                </button>
            </div>
        </form>
      </div>
    </div>
  </div>
@endforeach

<!-- Contenedor de notificaciones -->
@if($tipo == "Cajas" && !empty($notificaciones) && count($notificaciones) > 0)
  <div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast animate___animated animate___backInRight" role="alert" aria-live="assertive" aria-atomic="true" style="width: 70vh!important">
      <div class="toast-header text-start">
        <img src="{{ asset('images/erp/valeMilMarca.png') }}" width="20px" class="rounded me-2" alt="valemil">
        <strong class="me-auto">Valemil</strong>
        <small><i class="fa-solid fa-triangle-exclamation"></i> Solicitudes</small>
        <button type="button" class="btn-close push border-0" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body text-start">
          @if (!empty($notificaciones) && count($notificaciones) > 0)
              @foreach ($notificaciones as $notificacion)
              <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <div class="d-flex flex-column p-4 pt-1">
                    <span><strong>Nombre:</strong> {{ $notificacion->nombre }}</span>
                    <span><strong>Razon:</strong> {{ $notificacion->razon }}</span>
                    <span><strong>Descripción:</strong> {{ $notificacion->descripcion }}</span>
                    <span><strong>Caja:</strong> {{ $notificacion->nombre_caja }}</span>
                </div>

                <div class="d-flex flex-column ml-auto p-4 pt-1">
                    <form action="/Tesoreria/Historial/ArqueoCaja/Aceptarpermiso/{{$notificacion->id_empleado}}/{{$notificacion->id}}" method="POST" class="mb-2">
                        @csrf
                        <button type="submit" class="accept-notification btn btn-success w-100 text-left">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    </form>
                    <form action="/Tesoreria/Historial/ArqueoCaja/Editarnotificaciones/{{$notificacion->id}}" method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="delete-notification btn btn-danger w-100 text-left">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </div>
              </div>
              @endforeach
          @else
              <p class="text-center text-muted">No hay notificaciones.</p>
          @endif
      </div>
    </div>
  </div>
@endif

<!-- Estilos CSS para tabla y animaciones -->
<style>
/* Estilos para la tabla */
.table {
    border-radius: 0;
    overflow: visible;
}

.table thead th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    color: #495057;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(220, 53, 69, 0.05);
    transform: scale(1.01);
}

/* Animaciones personalizadas */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.table tbody tr {
    animation: fadeInUp 0.6s ease-out;
}

.table tbody tr:nth-child(1) { animation-delay: 0.1s; }
.table tbody tr:nth-child(2) { animation-delay: 0.2s; }
.table tbody tr:nth-child(3) { animation-delay: 0.3s; }
.table tbody tr:nth-child(4) { animation-delay: 0.4s; }
.table tbody tr:nth-child(5) { animation-delay: 0.5s; }
.table tbody tr:nth-child(6) { animation-delay: 0.6s; }

</style>

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/files.js') }}"></script>
<script src="{{ asset('js/notification.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="modalGasto"]').forEach(function(el) {
        el.addEventListener('shown.bs.modal', function() {
            if (typeof bindModernFileInputs === 'function') {
                bindModernFileInputs(el);
            }
        });
    });
});
</script>
<script>
    $(document).ready(function () {
        const notificationButton = $('#notificationButton');
        const notificationBox = $('#notificationBox');

        // Mostrar el cuadro de notificaciones cuando el cursor esté sobre el botón
        notificationButton.on('mouseover', function () {
            const rect = notificationButton[0].getBoundingClientRect();
            notificationBox.css({
                display: 'block',
                top: rect.bottom + window.scrollY + 'px',
                left: rect.left + 'px'
            });
        });

        notificationBox.on('mouseover', function () {
            $(this).show();
        });

        notificationBox.on('mouseleave', function () {
            $(this).hide();
        });
        notificationButton.on('mouseleave', function () {
            setTimeout(function () {
                if (!notificationBox.is(':hover')) {
                    notificationBox.hide();
                }
            }, 200);
        });
    });
</script>

<script>
$(document).ready(function(){
    $('.cajas').hide();
    $('.cuentas').hide();
    $('.cajas_chicas').hide();
    document.querySelector('#cuentaselect').required = true;
    document.querySelector('#cajaselect').required = true;
    document.querySelector('#chicaselect').required = true;

    $(".cuenta").on("click", function() {	
        $('.cajas').hide();
        $('.cuentas').show();
        $('.cajas_chicas').hide();

        $("#cuentaselect").attr('required', '');
        $("#cajaselect").removeAttr('required'); 
        $("#chicaselect").removeAttr('required'); 
    });

    $(".caja").on("click", function() {	
        $('.cajas').show();
        $('.cuentas').hide();
        $('.cajas_chicas').hide();
        document.querySelector('#cuentaselect').required = false;
        document.querySelector('#cajaselect').required = true;
        document.querySelector('#chicaselect').required = false;
    });

    $(".caja_chica").on("click", function() {	
        $('.cajas').hide();
        $('.cuentas').hide();
        $('.cajas_chicas').show();
        document.querySelector('#cuentaselect').required = false;
        document.querySelector('#cajaselect').required = false;
        document.querySelector('#chicaselect').required = true;
    });
});
</script>
@endsection