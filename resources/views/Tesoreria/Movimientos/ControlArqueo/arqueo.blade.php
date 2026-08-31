@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@if($mensaje = Session::get('warningSaldo'))
@php
        echo '<script language="JavaScript">';
        echo 'swal("¡Saldo Insuficiente!","No se a efectuado la acción, necesita un mayor saldo para hacer el cargo, revise el saldo o consulte con tesoreria","warning", {buttons: false,timer: 3500});';
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
    <a href="/Tesoreria/Movimientos/ControlArqueos/{{$tipo}}/{{$empresaid}}/{{$id}}" class="iconn icon-user-tie btn border-0">&nbsp;&nbsp;<b><i class="fa-solid fa-chevron-left"></i></b>&nbsp;&nbsp;&nbsp; </a>
</div>


<!-- Modal detalle arqueo -->
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
                            <a href="/Tesoreria/Movimientos/ControlArqueos/{{$tipo}}/{{$empresaid}}/{{$id}}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Control de arqueos
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Arqueo | {{ $nombre }}</h2>
                        <p class="text-muted mb-0">Detalle y gestión del arqueo</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

  <div class="shadow p-2 start-center" style="background-color: rgba(241, 241, 241, 0.571)!important;padding-left: 20px!important;">
    <div class="row">
      <div class="col d-none d-md-block"></div>

      <div class="col-md-2 col-12" style="border-right: 1px solid rgb(170, 170, 170);">
        <div class="row text-center mt-1">
          <a href="/Tesoreria/Movimientos/Exportar/ArqueoCaja/{{$tipo}}/{{$empresaid}}/{{$id}}/{{$fecha}}" class="btn text-success border-0 m-1 rounded-3 fs-8 push">
            <i class="fa-solid fa-download"></i> Descargar Arqueo
          </a> 
        </div>
      </div>

      <div class="col-md-2 col-4" style="border-right: 1px solid rgb(170, 170, 170);">
        <div class="row text-center mt-1">
          <h6 class="text-secondary">{{$estado}}</h6>
          <h6 class="fs-9">Estado</h6>
        </div>
      </div>

      <div class="col-md-2 col-4" style="border-right: 1px solid rgb(170, 170, 170);">
        <div class="row text-center mt-1">
            <h6 class="text-secondary">${{number_format($saldo_inicial, 2)}}</h6>
            <h6 class="fs-9">Saldo Inicial al Arqueo</h6>
        </div>
      </div>

      <div class="col-md-2 col-4 mt-1">
        <div class="row text-center">
            <h6 class="text-success">${{number_format($saldo_actual, 2)}}</h6>
            <h6 class="fs-9">Saldo Actual al Arqueo</h6>
        </div>
      </div>

      <div class="col d-none d-md-block"></div>
    </div>
  </div>

  <div class="row bd-body">
      <div class="col-md-3 col p-1 btn push3 border-0 btningresos" style="background-color: rgba(238, 238, 238, 0.986)!important;padding-left: 20px!important;">
        <div class="row text-center">
            <h4 class="text-success">${{number_format($total_ingresos, 2)}}</h4>
            <h6 class="fs-8">Total Ingresos</h6>
        </div>
      </div>

      <div class="col-md-3 col p-1 btn shadow push3 border-0 btnegresos" style="background-color: rgb(247, 247, 247)!important;padding-left: 20px!important;">
        <div class="row text-center">
            <h4 class="text-danger">${{number_format($total_egresos, 2)}}</h4>
            <h6 class="fs-8">Total Egresos</h6>
        </div>
      </div>

      <div class="col-md-2 col p-1 btn push3 border-0 btnrelaciones" style="background-color: rgba(238, 238, 238, 0.986)!important;padding-left: 20px!important;">
        <div class="row text-center">
            <h4>${{number_format($total_calculado, 2)}}</h4>
            <h6 class="fs-8">Saldo en Caja</h6>
        </div>
      </div>

      <div class="col-md-2 col p-1 btn shadow push3 border-0 btnrelaciones" style="background-color: rgb(247, 247, 247)!important;">
        <div class="row text-center">
            <h4 class="text-secondary">${{number_format($total_ingresado, 2)}}</h4>
            <h6 class="fs-8">Relación de Efectivo</h6>
        </div>
      </div>

      <div class="col-md-2 col p-1 border-0" style="background-color: rgba(238, 238, 238, 0.986)!important;padding-left: 20px!important;">
        <div class="row text-center">
          @if($diferencia  == 0)
            <h4 class="text-success">${{number_format($diferencia, 2)}} </h4>
          @else
          <h4 class="text-danger">${{number_format($diferencia, 2)}}</h4>
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
            <div class="col text-end">$ {{number_format($saldo_inicial, 2)}}</div>
          </div>

          {{-- total ingresos --}}
          <div class="row mt-3">
            <table class="table table-striped table-hover p-2">
              <tbody>
                  <tr>
                    <th>1. &nbsp;&nbsp;RECEPCION DE EFECTIVO</th>
                    <td>$ {{number_format($traspasos, 2)}}</td>
                  </tr>
                  <tr>
                    <th>2. &nbsp;&nbsp;COBRANZA DIARIA</th>
                    <td>$ {{number_format($cobranza, 2)}}</td>
                  </tr>
              </tbody>
            </table>
          </div>

          <div class="row">
            <div class="col-md-8 fw-bold text-success">Total Ingresos</div>
            <div class="col text-end text-success">$ {{number_format($total_ingresos, 2)}}</div>
          </div>

          {{-- total egresos --}}
          <hr>
          <div class="row mt-4">
            <table class="table table-striped table-hover p-2">
              <tbody>
                  <tr>
                    <th>3. &nbsp;&nbsp;DESEMBOLSOS DIARIOS</th>
                    <td>$ {{number_format($desembolsos, 2)}}</td>
                  </tr>
                  <tr>
                    <th>4. &nbsp;&nbsp;RELACION DE GASTOS POR COMPROBAR</th>
                    <td>$ {{number_format($gastos, 2)}}</td>
                  </tr>
                  <tr>
                    <th>5. &nbsp;&nbsp;ENTREGA DE EFECTIVO</th>
                    <td>$ {{number_format($entregas, 2)}}</td>
                  </tr>
                  <tr>
                    <th>6. &nbsp;&nbsp;CANCELACION DE PAGOS</th>
                    <td>$ {{number_format($cancelaciones, 2)}}</td>
                  </tr>
              </tbody>
            </table>
          </div>

          <div class="row">
            <div class="col-md-8 fw-bold text-danger">Total Egresos</div>
            <div class="col text-end text-danger">$ {{number_format($total_egresos, 2)}}</div>
          </div>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-md-8 fw-bold">Total</div>
            <div class="col text-end">$ {{number_format($total_calculado, 2)}}</div>
          </div>
        </div>
      </div>

      {{-- relacion de efectivo --}}
      <div class="card border-0 bg-light shadow-lg m-2 mb-5 col-md-5 col-12">
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
                      <td class="text-truncate">{{number_format($totalmil, 2)}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">500.00</td>
                      <td>{{$catidad_quinientos}}@php($totalquinientos = 500*$catidad_quinientos)</td>
                      <td class="text-truncate">{{number_format($totalquinientos, 2)}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">200.00</td>
                      <td>{{$catidad_doscientos}}@php($totaldoscientos = 200*$catidad_doscientos)</td>
                      <td class="text-truncate">{{number_format($totaldoscientos, 2)}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">100.00</td>
                      <td>{{$catidad_cien}}@php($totalcien = 100*$catidad_cien)</td>
                      <td class="text-truncate">{{number_format($totalcien, 2)}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">50.00</td>
                      <td>{{$catidad_cincuenta}}@php($totalcincuenta = 50*$catidad_cincuenta)</td>
                      <td class="text-truncate">{{number_format($totalcincuenta, 2)}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">20.00</td>
                      <td>{{$catidad_veinte}}@php($totalveinte = 20*$catidad_veinte)</td>
                      <td class="text-truncate">{{number_format($totalveinte, 2)}}</td>
                    </tr>
                    <tr>
                      @php($totalbillete = $totalmil+$totalquinientos+$totaldoscientos+$totalcien+$totalcincuenta+$totalveinte)
                      <th colspan="2">TOTAL BILLETES</th>
                      <td class="text-truncate">$ {{number_format($totalbillete, 2)}}</td>
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
                      <td class="text-truncate">{{number_format($totaldiez, 2)}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">5.00</td>
                      <td>{{$catidad_cinco}}@php($totalcinco = 5*$catidad_cinco)</td>
                      <td class="text-truncate">{{number_format($totalcinco, 2)}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">2.00</td>
                      <td>{{$catidad_dos}}@php($totaldos = 2*$catidad_dos)</td>
                      <td class="text-truncate">{{number_format($totaldos, 2)}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">1.00</td>
                      <td>{{$catidad_uno}}@php($totaluno = 1*$catidad_uno)</td>
                      <td class="text-truncate">{{number_format($totaluno, 2)}}</td>
                    </tr>
                    <tr>
                      <td class="fw-bold">0.50</td>
                      <td>{{$catidad_cincuentacentavos}}@php($totalcincuentacentavos = 0.5*$catidad_cincuentacentavos)</td>
                      <td class="text-truncate">{{number_format($totalcincuentacentavos, 2)}}</td>
                    </tr>
                    <tr>
                      @php($totalmoneda = $totaldiez+$totalcinco+$totaldos+$totaluno+$totalcincuentacentavos)
                      <th colspan="2">TOTAL MONEDAS</th>
                      <td class="text-truncate">$ {{number_format($totalmoneda, 2)}}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-md-8 fw-bold">Total</div>
            <div class="col text-end">$ {{number_format($total_ingresado, 2)}}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- ingresos --}}
    <div class="row mt-2" id="ingresos">
      {{-- traspasos --}}
      <div class="card border-0 bg-light shadow-lg m-2 mb-5 col-md-5 col-12">
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
                  <td class="text-truncate">$ {{number_format($tras->ingreso, 2)}}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-md-8 fw-bold">Total</div>
            <div class="col text-end">$ {{number_format($traspasos, 2)}}</div>
          </div>
        </div>
      </div>

      {{-- cobranza --}}
      <div class="card border-0 bg-light shadow-lg m-2 mb-5 col-md-5 col-12">
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
                <td class="text-truncate">$ {{number_format($cobra->ingreso, 2)}}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-md-8 fw-bold">Total</div>
            <div class="col text-end">$ {{number_format($cobranza, 2)}}</div>
          </div>
        </div>
      </div>
    </div>

    {{-- egresos --}}
    <div class="row mt-2" id="egresos">
      <div class="row">
        {{-- DESEMBOLSOS --}}
        <div class="card border-0 bg-light shadow-lg m-2 col-md-5 col-12">
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
                  <td class="text-truncate">$ {{number_format($desm->egreso, 2)}}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="card-footer">
            <div class="row">
              <div class="col-md-8 fw-bold">Total</div>
              <div class="col text-end">$ {{number_format($desembolsos, 2)}}</div>
            </div>
          </div>
        </div>

        {{-- GASTO --}}
        <div class="card border-0 bg-light shadow-lg m-2 col-md-5 col-12">
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
                  <td>{{number_format($gast->egreso, 2)}}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="card-footer">
            <div class="row">
              <div class="col-md-8 fw-bold">Total</div>
              <div class="col text-end">$ {{number_format($gastos, 2)}}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-5">
        {{-- ENTREGA DE EFECTIVO --}}
        <div class="card border-0 bg-light shadow-lg m-2 col-md-5 col-12">
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
                  <td>{{number_format($entr->egreso, 2)}}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="card-footer">
            <div class="row">
              <div class="col-md-8 fw-bold">Total</div>
              <div class="col text-end">$ {{number_format($entregas, 2)}}</div>
            </div>
          </div>
        </div>

        <div class="card border-0 bg-light shadow-lg m-2 col-md-5 col-12">
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
                  <td>{{number_format($canc->egreso, 2)}}</td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="card-footer">
            <div class="row">
              <div class="col-md-8 fw-bold">Total</div>
              <div class="col text-end">$ {{number_format($cancelaciones, 2)}}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

 <!-- Modal Comentar Arqueo -->
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-dark" id="exampleModalLabel">¿Esta Seguro Solicitar Cancelación?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/Tesoreria/Movimientos/ArqueoCaja/comentarArqueo/{{$id_arqueo}}" class="form g-3 needs-validation mt-1 text-start" novalidate>
        @csrf
        <div class="modal-body">
          <h6 class="text-center mb-3 fs-8">Comentario</h6>
          <hr> 
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