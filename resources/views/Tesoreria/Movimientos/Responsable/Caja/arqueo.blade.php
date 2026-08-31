@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@if($mensaje = Session::get('warningSaldo'))
@php
        echo '<script language="JavaScript">';
        echo 'swal("¡Saldo Insuficiente!","No se a efectuado la acción, necesita un mayor saldo para hacer el cargo, revise los saldos para continuar","warning", {buttons: false,timer: 3500});';
        echo '</script>';  
@endphp
 
@elseif($mensaje = Session::get('warningCuenta'))
@php
        echo '<script language="JavaScript">';
        echo 'swal("¡No se selecciono bien la cuenta!","Asegurese de llenar todos los campos para una transferencia exitosa","warning", {buttons: false,timer: 4000});';
        echo '</script>';  
@endphp
@endif


<div class="social-bar">
    <a href="/Tesoreria/Movimientos/Responsable/{{$tipo}}/{{$empresaid}}/{{$id}}" class="iconn icon-user-tie btn border-0">&nbsp;&nbsp;<b><i class="fa-solid fa-chevron-left"></i></b>&nbsp;&nbsp;&nbsp; </a>
</div>


<!-----Principal Area----->
<div class="container-fluid format_page bg-body">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Tesoreria/Movimientos/Responsable/{{$tipo}}/{{$empresaid}}/{{$id}}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Caja
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Arqueo | {{ $nombre }}</h2>
                        <p class="text-muted mb-0">Gestión y control del arqueo de caja</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

  <div class="shadow p-2 start-center" style="background-color: rgba(241, 241, 241, 0.571)!important;padding-left: 20px!important;">
    <div class="row">
      <div class="col-md-6 col-12">
          <h6 class="text-muted">Herramientas <i class="fa-solid fa-screwdriver-wrench"></i>&nbsp;&nbsp;&nbsp;
            @if($estado == "En Espera")
              <button type="button" class="btn btn-primary m-1 rounded-3 fs-9 push border-0 text-truncate" disabled>
                <i class="fa-solid fa-signs-post"></i> Solicitar Acción
              </button>
            @else
              <button type="button" class="btn btn-primary m-1 rounded-3 fs-9 push border-0 text-truncate" data-bs-toggle="modal" data-bs-target="#modalComentar">
                <i class="fa-solid fa-signs-post"></i> Solicitar Acción
              </button>
            @endif
              
              <a href="/Tesoreria/Movimientos/Exportar/ArqueoCaja/{{$tipo}}/{{$empresaid}}/{{$id}}/{{$fecha}}" class="btn btn-success m-1 rounded-3 fs-9 push text-truncate">
                <i class="fa-solid fa-download"></i> Descargar Arqueo
              </a> 

              @if($estado == "Editando")
              <button type="button" class="btn btn-outline-primary m-1 rounded-3 fs-9 push" data-bs-toggle="modal" data-bs-target="#modalArqueo">
                <i class="fa-solid fa-rotate-right"></i> Actualizar Efectivo
              </button>
              @endif
            </h6>
      </div>

      <div class="col-md-2 col-4" style="border-right: 1px solid rgb(170, 170, 170);">
        <div class="row text-center mt-1">
          <h6 class="text-secondary">{{$estado}}</h6>
          <h6 class="fs-9">Estado</h6>
        </div>
      </div>

      <div class="col-md-2 col-4" style="border-right: 1px solid rgb(170, 170, 170);">
        <div class="row text-center mt-1">
            <h6 class="text-secondary">{{$saldo_inicial}}</h6>
            <h6 class="fs-9">Saldo Inicial</h6>
        </div>
      </div>

      <div class="col-md-2 col-4 mt-1">
        <div class="row text-center">
            <h6 class="text-success">{{$saldo_actual}}</h6>
            <h6 class="fs-9">Saldo Actual</h6>
        </div>
      </div>
    </div>
  </div>

  <div class="row bd-body">
      <div class="col-md-3 col p-1 btn push3 border-0 btningresos" style="background-color: rgba(238, 238, 238, 0.986)!important;padding-left: 20px!important;">
        <div class="row text-center">
            <h4 class="text-success">{{$total_ingresos}}</h4>
            <h6 class="fs-8">Total Ingresos</h6>
        </div>
      </div>

      <div class="col-md-3 col p-1 btn shadow push3 border-0 btnegresos" style="background-color: rgb(247, 247, 247)!important;padding-left: 20px!important;">
        <div class="row text-center">
            <h4 class="text-danger">{{$total_egresos}}</h4>
            <h6 class="fs-8">Total Egresos</h6>
        </div>
      </div>

      <div class="col-md-2 col p-1 btn push3 border-0 btnrelaciones" style="background-color: rgba(238, 238, 238, 0.986)!important;padding-left: 20px!important;">
        <div class="row text-center">
            <h4> {{$total_calculado}} </h4>
            <h6 class="fs-8">Saldo en Caja</h6>
        </div>
      </div>

      <div class="col-md-2 col p-1 btn shadow push3 border-0 btnrelaciones" style="background-color: rgb(247, 247, 247)!important;">
        <div class="row text-center">
            <h4 class="text-secondary"> {{$total_ingresado}}</h4>
            <h6 class="fs-8">Relación de Efectivo</h6>
        </div>
      </div>

      <div class="col-md-2 col p-1 border-0" style="background-color: rgba(238, 238, 238, 0.986)!important;padding-left: 20px!important;">
        <div class="row text-center">
          @if($diferencia  == 0)
            <h4 class="text-success"> {{$diferencia}} </h4>
          @else
          <h4 class="text-danger"> {{$diferencia}} </h4>
          @endif
          <h6 class="fs-8">Diferencia</h6>
        </div>
      </div>
  </div>

  <div class="container">

    <ul class="nav nav-tabs mt-3 mb-4">
      <li class="nav-item">
        <a class="nav-link btnrelaciones fs-6_5" id="totales" aria-current="page" href="#">Saldos</a>
      </li>
      <li class="nav-item">
        <a class="nav-link btningresos fs-6_5" id="totalingresos" href="#">Total Ingresos</a>
      </li>
      <li class="nav-item">
        <a class="nav-link btnegresos fs-6_5" id="totalegresos" href="#">Total Egresos</a>
      </li>
    </ul>

    {{-- saldo en caja y relacion de efectivo --}}
    <div class="row mt-2" id="relaciones">
      {{-- saldo en caja --}}
      <div class="card border-0 bg-light shadow-lg m-2 mb-5 col-md-5 col-12">
        <div class="row bg-light">
          <h6 class="fs-8 mt-1 text-center">SALDO EN CAJA</h6>
        </div>

        <div class="card-body scrollpsi">
          <div class="row">
            <div class="col-md-8 fw-bold">Saldo Inicial</div>
            <div class="col text-end">$ {{$saldo_inicial}}</div>
          </div>

          {{-- total ingresos --}}
          <div class="row mt-3">
            <table class="table table-striped table-hover p-2">
              <tbody>
                  <tr>
                    <th>1. &nbsp;&nbsp;RECEPCION DE EFECTIVO</th>
                    <td>$ {{$traspasos}}</td>
                  </tr>
                  <tr>
                    <th>2. &nbsp;&nbsp;COBRANZA DIARIA</th>
                    <td>$ {{$cobranza}}</td>
                  </tr>
              </tbody>
            </table>
          </div>

          <div class="row">
            <div class="col-md-8 fw-bold text-success">Total Ingresos</div>
            <div class="col text-end text-success">$ {{$total_ingresos}}</div>
          </div>

          {{-- total egresos --}}
          <hr>
          <div class="row mt-4">
            <table class="table table-striped table-hover p-2">
              <tbody>
                  <tr>
                    <th>3. &nbsp;&nbsp;DESEMBOLSOS DIARIOS</th>
                    <td>$ {{$desembolsos}}</td>
                  </tr>
                  <tr>
                    <th>4. &nbsp;&nbsp;RELACION DE GASTOS POR COMPROBAR</th>
                    <td>$ {{$gastos}}</td>
                  </tr>
                  <tr>
                    <th>5. &nbsp;&nbsp;ENTREGA DE EFECTIVO</th>
                    <td>$ {{$entregas}}</td>
                  </tr>
                  <tr>
                    <th>6. &nbsp;&nbsp;CANCELACION DE PAGOS</th>
                    <td>$ {{$cancelaciones}}</td>
                  </tr>
              </tbody>
            </table>
          </div>

          <div class="row">
            <div class="col-md-8 fw-bold text-danger">Total Egresos</div>
            <div class="col text-end text-danger">$ {{$total_egresos}}</div>
          </div>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-md-8 fw-bold">Total</div>
            <div class="col text-end">$ {{$total_calculado}}</div>
          </div>
        </div>
      </div>

      {{-- relacion de efectivo --}}
      <div class="card border-0 bg-light shadow-lg m-2 mb-5 col-md-5  col-12">
        <div class="row bg-light">
          <h6 class="fs-8 mt-1 text-center">RELACION DE EFECTIVO</h6>
        </div>

        <div class="card-body scrollpsi">
          <div class="row">
            <div class="col">
              {{-- Billetes --}}
              <table class="table table-striped table-hover p-2">
                <thead>
                  <tr>
                    <th>Denominación</th>
                    <th>Cantidad</th>
                    <th>Monto</th>
                  </tr>
                </thead>

                <tbody>
                    <tr>
                      <td class="fw-bold">1,000.00</td>
                      <td>{{$catidad_mil}}@php($totalmil = 1000*$catidad_mil)</td>
                      <td class="text-truncate">{{$totalmil}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">500.00</td>
                      <td>{{$catidad_quinientos}}@php($totalquinientos = 500*$catidad_quinientos)</td>
                      <td class="text-truncate">{{$totalquinientos}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">200.00</td>
                      <td>{{$catidad_doscientos}}@php($totaldoscientos = 200*$catidad_doscientos)</td>
                      <td class="text-truncate">{{$totaldoscientos}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">100.00</td>
                      <td>{{$catidad_cien}}@php($totalcien = 100*$catidad_cien)</td>
                      <td class="text-truncate">{{$totalcien}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">50.00</td>
                      <td>{{$catidad_cincuenta}}@php($totalcincuenta = 50*$catidad_cincuenta)</td>
                      <td class="text-truncate">{{$totalcincuenta}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">20.00</td>
                      <td>{{$catidad_veinte}}@php($totalveinte = 20*$catidad_veinte)</td>
                      <td class="text-truncate">{{$totalveinte}}</td>
                    </tr>
                    <tr>
                      @php($totalbillete = $totalmil+$totalquinientos+$totaldoscientos+$totalcien+$totalcincuenta+$totalveinte)
                      <th colspan="2">TOTAL BILLETES</th>
                      <td class="text-truncate">$ {{$totalbillete}}</td>
                    </tr>
                </tbody>
              </table>
            </div>

            <div class="col">
              {{-- Monedas --}}
              <table class="table table-striped table-hover p-2">
                <thead>
                  <tr>
                    <th>Denominación</th>
                    <th>Cantidad</th>
                    <th>Monto</th>
                  </tr>
                </thead>

                <tbody>
                    <tr>
                      <td class="fw-bold">10.00</td>
                      <td>{{$catidad_diez}}@php($totaldiez = 10*$catidad_diez)</td>
                      <td class="text-truncate">{{$totaldiez}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">5.00</td>
                      <td>{{$catidad_cinco}}@php($totalcinco = 5*$catidad_cinco)</td>
                      <td class="text-truncate">{{$totalcinco}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">2.00</td>
                      <td>{{$catidad_dos}}@php($totaldos = 2*$catidad_dos)</td>
                      <td class="text-truncate">{{$totaldos}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">1.00</td>
                      <td>{{$catidad_uno}}@php($totaluno = 1*$catidad_uno)</td>
                      <td class="text-truncate">{{$totaluno}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">0.50</td>
                      <td>{{$catidad_cincuentacentavos}}@php($totalcincuentacentavos = 0.5*$catidad_cincuentacentavos)</td>
                      <td class="text-truncate">{{$totalcincuentacentavos}}</td>
                    </tr>
                    <tr>
                      @php($totalmoneda = $totaldiez+$totalcinco+$totaldos+$totaluno+$totalcincuentacentavos)
                      <th colspan="2">TOTAL MONEDAS</th>
                      <td class="text-truncate">$ {{$totalmoneda}}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-md-8 fw-bold">Total</div>
            <div class="col text-end">$ {{$total_ingresado}}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- ingresos --}}
    <div class="row mt-2" id="ingresos">
      {{-- traspasos --}}
      <div class="card border-0 bg-light shadow-lg m-2 mb-5 col-md-5  col-12">
        <div class="row bg-light">
          <h6 class="fs-8 mt-1">1. &nbsp;&nbsp;RECEPCION DE EFECTIVO</h6>
        </div>

        <div class="card-body scrollpsi">
          <table class="table table-striped table-hover p-2">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Referencia</th>
                <th>Concepto</th>
                <th>Importe</th>
              </tr>
            </thead>

            <tbody>
              @foreach($vartraspasos as $tras)
                <tr>
                  <td class="text-truncate">{{$tras->fecha}}</td>
                  <td># {{$tras->numero_referencia}} {{$tras->nombre_referencia}}</td>
                  <td>{{$tras->concepto}}</td>
                  <td class="text-truncate">$ {{$tras->ingreso}}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-md-8 fw-bold">Total</div>
            <div class="col text-end">$ {{$traspasos}}</div>
          </div>
        </div>
      </div>

      {{-- cobranza --}}
      <div class="card border-0 bg-light shadow-lg m-2 mb-5 col-md-5  col-12">
        <div class="row bg-light">
          <h6 class="fs-8 mt-1">2. &nbsp;&nbsp;COBRANZA DIARIA</h6>
        </div>

        <div class="card-body scrollpsi">
          <table class="table table-striped table-hover p-2">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Referencia</th>
                <th>Concepto</th>
                <th>Importe</th>
              </tr>
            </thead>

            <tbody>
              @foreach($varcobranza as $cobra)
              <tr>
                <td class="text-truncate">{{$cobra->fecha}}</td>
                <td>#{{$cobra->numero_referencia}} {{$cobra->nombre_referencia}}</td>
                <td>{{$cobra->concepto}}</td>
                <td class="text-truncate">$ {{$cobra->ingreso}}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-md-8 fw-bold">Total</div>
            <div class="col text-end">$ {{$cobranza}}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- egresos --}}
    <div class="row mt-2" id="egresos">
      <div class="row">
        {{-- DESEMBOLSOS --}}
        <div class="card border-0 bg-light shadow-lg m-2 col-md-5  col-12">
          <div class="row bg-light">
            <h6 class="fs-8 mt-1">3. &nbsp;&nbsp;DESEMBOLSOS DIARIOS</h6>
          </div>

          <div class="card-body scrollpsi">
            <table class="table table-striped table-hover p-2">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Referencia</th>
                  <th>Concepto</th>
                  <th>Importe</th>
                </tr>
              </thead>

              <tbody>
                @foreach($vardesembolsos as $desm)
                <tr>
                  <td class="text-truncate">{{$desm->fecha}}</td>
                  <td>#{{$desm->numero_referencia}} {{$desm->nombre_referencia}}</td>
                  <td>{{$desm->concepto}}</td>
                  <td class="text-truncate">$ {{$desm->egreso}}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="card-footer">
            <div class="row">
              <div class="col-md-8 fw-bold">Total</div>
              <div class="col text-end">$ {{$desembolsos}}</div>
            </div>
          </div>
        </div>

        {{-- GASTO --}}
        <div class="card border-0 bg-light shadow-lg m-2 col-md-5  col-12">
          <div class="row bg-light">
            <h6 class="fs-8 mt-1">4. &nbsp;&nbsp;RELACION DE GASTOS POR COMPROBAR</h6>
          </div>

          <div class="card-body scrollpsi">
            <table class="table table-striped table-hover p-2">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Referencia</th>
                  <th>Concepto</th>
                  <th>Importe</th>
                </tr>
              </thead>

              <tbody>
                @foreach($vargastos as $gast)
                <tr>
                  <td class="text-truncate">{{$gast->fecha}}</td>
                  <td>{{$gast->concepto}}</td>
                  <td>{{$gast->descripcion}}</td>
                  <td>{{$gast->egreso}}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="card-footer">
            <div class="row">
              <div class="col-md-8 fw-bold">Total</div>
              <div class="col text-end">$ {{$gastos}}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-5">
        {{-- ENTREGA DE EFECTIVO --}}
        <div class="card border-0 bg-light shadow-lg m-2 col-md-5  col-12">
          <div class="row bg-light">
            <h6 class="fs-8 mt-1">5. &nbsp;&nbsp;ENTREGA DE EFECTIVO</h6>
          </div>

          <div class="card-body scrollpsi">
            <table class="table table-striped table-hover p-2">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Referencia</th>
                  <th>Concepto</th>
                  <th>Importe</th>
                </tr>
              </thead>

              <tbody>
                @foreach($varentregas as $entr)
                <tr>
                  <td class="text-truncate">{{$entr->fecha}}</td>
                  <td>#{{$entr->numero_referencia}} {{$entr->nombre_referencia}}</td>
                  <td>{{$entr->concepto}}</td>
                  <td>{{$entr->egreso}}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="card-footer">
            <div class="row">
              <div class="col-md-8 fw-bold">Total</div>
              <div class="col text-end">$ {{$entregas}}</div>
            </div>
          </div>
        </div>

        <div class="card border-0 bg-light shadow-lg m-2 col-md-5  col-12">
          <div class="row bg-light">
            <h6 class="fs-8 mt-1">6. &nbsp;&nbsp;CANCELACION DE PAGOS</h6>
          </div>

          <div class="card-body scrollpsi">
            <table class="table table-striped table-hover p-2">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Referencia</th>
                  <th>Concepto</th>
                  <th>Importe</th>
                </tr>
              </thead>

              <tbody>
                @foreach($varcancelaciones as $canc)
                <tr>
                  <td class="text-truncate">{{$canc->fecha}}</td>
                  <td>PAGO #{{$canc->numero_referencia}} DE {{$canc->nombre_referencia}}</td>
                  <td>{{$canc->concepto}}</td>
                  <td>{{$canc->egreso}}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="card-footer">
            <div class="row">
              <div class="col-md-8 fw-bold">Total</div>
              <div class="col text-end">$ {{$cancelaciones}}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

 {{--------------------------- Modal comentar arqueo ----------------------}}
<div class="modal fade" id="modalComentar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-dark" id="exampleModalLabel">Solicitar Acción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/Tesoreria/Movimientos/ArqueoCaja/comentarArqueo/{{$id_arqueo}}" class="g-3 needs-validation mt-1 text-start" novalidate>
        @csrf
        <div class="modal-body">
          <div>
            <h6> ¿Qué dese realizar?&nbsp; &nbsp; &nbsp; 
            
            @if($estado == "Editando")
              <input type="radio" class="btn-check" name="accion" id="s-accion" autocomplete="off" value="CANCELAR" required>
              <label class="btn btn-outline-danger fs-9" for="s-accion"><i class="fa-solid fa-ban"></i> Solicitar Cancelación</label>

              <input type="radio" class="btn-check" name="accion" id="d-accion" autocomplete="off" value="EFECTIVO" disabled>
              <label class="btn btn-outline-primary fs-9" for="d-accion"><i class="fa-solid fa-rotate-right"></i> Actualizar Efectivo</label>
            @else
              <input type="radio" class="btn-check" name="accion" id="s-accion" autocomplete="off" value="CANCELAR" required>
              <label class="btn btn-outline-danger fs-9" for="s-accion"><i class="fa-solid fa-ban"></i> Solicitar Cancelación</label>

              <input type="radio" class="btn-check" name="accion" id="d-accion" autocomplete="off" value="EFECTIVO" required>
              <label class="btn btn-outline-primary fs-9" for="d-accion"><i class="fa-solid fa-rotate-right"></i> Actualizar Efectivo</label>
            @endif
          </h6>
          </div>

          <hr> 
          <h6 class="text-center mb-3">Comentario</h6>
          <div class="row mt-4 mb-2">
            <center class="col">
              <div class="form-floating">
                <textarea class="form-control fs-8 text" placeholder="Leave a comment here" name="comentario" id="floatingTextarea2" maxlength="250" style="height: 100px;width: 80%" required></textarea>
                  <div class="valid-feedback">
                  ¡Se ve bien!
                  </div>
                  <div class="invalid-feedback">
                  Por favor, completa la información requerida.
                  </div>
              </div>
            </center>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-danger fs-8 rounded-5" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
          <button type="submit" class="btn btn-success fs-8 rounded-5"><i class="fa-solid fa-check"></i> Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{--------------------------- Modal arqueo ----------------------}}
<div class="modal fade" id="modalArqueo" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-dark" id="exampleModalLabel">Relación de Efectivo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/Tesoreria/Movimientos/ArqueoCaja/EditarEfectivo/{{$id}}/{{$id_arqueo}}/{{$idarqueo_efect}}" class="g-3 needs-validation mt-1 text-start" novalidate>
        @csrf
        <div class="modal-body">
          <div class="container">
              <div class="row text-center">
                <h6 class="text-center fs-8 fw-bold">TOTAL BILLETES</h6>
                    <div class="row">
                      <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                        <img src="{{ asset('Images/billetes/mil.jpg') }}" width="100px" alt="billete">
                        <br>1,000.00 <br><br><input type="number" class="form-control fs-8" name="mil" id="mil" required value="0">
                      </div>

                      <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                        <img src="{{ asset('Images/billetes/quinientos.jpg') }}" width="100px" alt="billete">
                        <br>500.00 <br><br><input type="number" class="form-control fs-8" name="quinientos" id="quinientos" required value="0">
                      </div>

                      <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                        <img src="{{ asset('Images/billetes/doscientos.jpg') }}" width="100px" alt="billete">
                        <br>200.00 <br><br><input type="number" class="form-control fs-8" name="doscientos" id="doscientos" required value="0">
                      </div>

                      <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                        <img src="{{ asset('Images/billetes/cien.jpg') }}" width="100px" alt="billete">
                        <br>100.00 <br><br><input type="number" class="form-control fs-8" name="cien" id="cien" required value="0">
                      </div>

                      <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                        <img src="{{ asset('Images/billetes/cincuenta.jpeg') }}" width="100px" alt="billete">
                        <br>50.00 <br><br><input type="number" class="form-control fs-8" name="cincuenta" id="cincuenta" required value="0">
                      </div>

                      <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                        <img src="{{ asset('Images/billetes/veinte.jpg') }}" width="100px" alt="billete">
                        <br>20.00 <br><br><input type="number" class="form-control fs-8" name="veinte" id="veinte" required value="0">
                      </div>
                    </div>
              </div>
              <br>
              <hr>

              <div class="row text-center">
                <h6 class="text-center fs-8 fw-bold">TOTAL MONEDAS</h6>

                <div class="row">
                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                    <img src="{{ asset('Images/monedas/diez.jpg') }}" width="50px" alt="moneda">
                        <br>10.00 <br><br><input type="number" class="form-control fs-8" name="diez" id="diez" required value="0">
                  </div>

                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                    <img src="{{ asset('Images/monedas/cinco.jpg') }}" width="40px" alt="moneda">
                        <br>5.00 <br><br><input type="number" class="form-control mt-2 fs-8" name="cinco" id="cinco" required value="0">
                  </div>

                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                    <img src="{{ asset('Images/monedas/dos.jpg') }}" width="30px" alt="moneda">
                        <br>2.00 <br><br><input type="number" class="form-control mt-3 fs-8" name="dos" id="dos" required value="0">
                  </div>

                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                    <img src="{{ asset('Images/monedas/uno.jpg') }}" width="30px" alt="moneda">
                        <br>1.00 <br><br><input type="number" class="form-control mt-3 fs-8" name="uno" id="uno" required value="0">
                  </div>

                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                    <img src="{{ asset('Images/monedas/cincuentacentavos.jpg') }}" width="25px" alt="moneda">
                        <br>0.50 <br><br><input type="number" class="form-control mt-3 fs-8" name="cincuentacentavos" id="cincuentacentavos" required value="0">
                  </div>
                </div>
              </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-danger fs-8 rounded-5" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
          <button type="submit" class="btn btn-success fs-8 rounded-5"><i class="fa-solid fa-check"></i> Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>
  

<script src="{{ asset('js/validation.js') }}"></script>
<script>
  $(document).ready(function(){

    $('#relaciones').show();
    $('#ingresos').hide();
    $('#egresos').hide();
    $('#totales').addClass('active fw-bold');

    $(".btnrelaciones").on("click", function() {	
      $('#relaciones').show();
      $('#ingresos').hide();
      $('#egresos').hide();

      $('#totales').addClass('active fw-bold');
      $('#totalingresos').removeClass('active fw-bold');
      $('#totalegresos').removeClass('active fw-bold');
    });

    $(".btningresos").on("click", function() {	
      $('#relaciones').hide();
      $('#ingresos').show();
      $('#egresos').hide();

      $('#totales').removeClass('active fw-bold');
      $('#totalingresos').addClass('active fw-bold');
      $('#totalegresos').removeClass('active fw-bold');
    });

    $(".btnegresos").on("click", function() {	
      $('#relaciones').hide();
      $('#ingresos').hide();
      $('#egresos').show();

      $('#totales').removeClass('active fw-bold');
      $('#totalingresos').removeClass('active fw-bold');
      $('#totalegresos').addClass('active fw-bold');
    });
	});
</script>
@endsection