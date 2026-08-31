@extends('layouts.app')
@section('content')

<div class="container-fluid format_page Global">
  <div class="marginLeft" style="">
    <div class="row">
        <div class="col-md-2">
          <div class="row start-center">
            <h5 class="mt-2 animate__animated animate__backInLeft text-truncate">Reporte Global</h5>
           
          {{--<div class="col-md-5 col-5">
              <div class="center p-2">
                <button class="btn push2 fs-9 btn-success rounded-3" style="font-size: 9px!important;"> <i class="fa-solid fa-file-excel"></i> Exportar </button>
              </div>
            </div> --}}
          </div>
        </div>

        <div class="col mb-1">
          <div class="row p-2">
              <h6 class="col border-end m-2 fs-6 text-truncate text-success"> $ {{ number_format($saldoact, 2)}} <br> <b class="fs-9 text-dark">Saldo Actual</b></h6>

              <h6 class="col border-end m-2 fs-6 text-truncate text-danger"> $ {{ number_format($saldor, 2)}} <br><b class="fs-9 text-dark">Saldo En Riesgo</b></h6>
    
              <h6 class="col border-end m-2 fs-6 text-truncate text-primary"> % {{ number_format($porsaldoenriesgo * 100, 2)}}<br> <b class="fs-9 text-dark">% SR</b></h6>

              <h6 class="col border-end m-2 fs-6 text-truncate text-orange"> $ {{ number_format($saldoatr, 2)}} <br><b class="fs-9 text-dark">Mora</b></h6>

              <h6 class="col m-2 fs-6 text-truncate"> % {{number_format($pormora * 100, 2)}} <br> <b class="fs-9 text-dark">% Mora</b> </h6>
          </div>
        </div>
    </div>
  </div> 

  
  <center class="border-0" style="margin-top:-30px;">
    <div class="table-responsive float-search " id="mydatatable-container">  
      @if($permiso1 == "exportar_globalVales")
          <table id="table" class="display  table-hover" style="width: 100%;">
        @else
          <table id="table" class="display  table-hover" style="width: 100%;">
      @endif
        <thead>
            <tr class="text-tr">
            <th class="text-center fw-bold">Detalle</th>
            <th class="text-center fw-bold">Reasignación</th>
            <th class="text-center fw-bold">Sucursal</th>
            <th class="text-center fw-bold">ID</th>
            <th class="text-center fw-bold">Distribuidor</th>
            <th class="text-center fw-bold">ID</th>
            <th class="text-center fw-bold">Coordinador</th>
            <th class="text-center fw-bold">Estado</th>
            <th class="text-center fw-bold">Capital Activo</th>
            <th class="text-center fw-bold">Total Prestamos</th> 
            <th class="text-center fw-bold">Total Intereses</th>
            <th class="text-center fw-bold">Iva Intereses</th>
            <th class="text-center fw-bold">Total Cobertura</th>
            <th class="text-center fw-bold">Categoría</th>
            <th class="text-center fw-bold">Clientes Activos</th>
            <th class="text-center fw-bold">Fecha Activacion</th>
            <th class="text-center fw-bold">Capital Disponible</th>
            <th class="text-center fw-bold">Linea de Credito</th>
            <th class="text-center fw-bold">Abonado</th>
            <th class="text-center fw-bold">Saldo Actual</th>
            <th class="text-center fw-bold">Comision</th>
            <th class="text-center fw-bold">Pago Requerido al Corte</th>
            <th class="text-center fw-bold">Fecha Ultimo Pago</th>
            <th class="text-center fw-bold">Importe Vencido Atrasado</th>
            <th class="text-center fw-bold">Dias de Atraso</th>
            <th class="text-center fw-bold">Coordinador Anterior</th>
            <th class="text-center fw-bold">Sucursal Anterior</th>
            <th class="text-center fw-bold">Usuario Autorizo</th>
            <th class="text-center fw-bold">ID</th>
            <th class="text-center fw-bold">Nombre Gestor</th>
            </tr>
        </thead>
        <tbody>
          @foreach($global1 as $glob1)
          @foreach($varobtienesuc as $sucpermitida)
          @if($sucpermitida->nombre == $glob1->sucursal)
            <tr class="boder-sec">
                @php($status = $glob1->status_distribuidor)
                @php($diasdeatraso = 0)
                @php($colortabla = "text-dark")
                @foreach($global3 as $diaatr)
                      @if($glob1->iddis == $diaatr->iddistribuidor)
                            @if($diaatr->dias_atraso < 0)
                              @php($diasdeatraso = 0)
                            @else
                              @php($diasdeatraso = $diasdeatraso + $diaatr->dias_atraso)
                            @endif
                      @endif
                @endforeach

                @if($diasdeatraso >= 7)
                    @php($colortabla = "text-danger")
                    @php($status = "SUSPENDIDO")
                  @elseif($diasdeatraso >= 1)
                    @php($colortabla = "text-danger")
                    @php($status = "MORA")
                @endif
     
              <td>
                  <form action="/Reporte_Globalcli/{{$glob1->iddis}}" >
                  <button class="btn border-0 btn-primary fs-9 rounded-3 m-0 text-truncate"><i class="fa-solid fa-eye"></i> Detalle</button>
                  </form>
              </td>
              
              <td>
                  <!-- Button trigger modal -->
                  @if($permiso == "reasignar")
                    <button type="button" class="btn btn-secondary fs-9 rounded-3  m-0 text-truncate" data-bs-toggle="modal" data-bs-target="#exampleModal{{$glob1->iddis}}">
                      <i class="fa-solid fa-shuffle"></i> Aplicar
                    </button>
                  @else
                    <button type="button" class="btn btn-secondary fs-9 rounded-3  m-0 text-truncate" disabled>
                      <i class="fa-solid fa-shuffle"></i> Aplicar
                    </button>
                  @endif

                  <!-- Modal -->
                  <div class="modal fade" id="exampleModal{{$glob1->iddis}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h1 class="modal-title text-dark fs-5" id="exampleModalLabel">Reasignar Coordinador</h1>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="/reporteGlobal/Reasignacion/{{$glob1->iddis}}" class="g-3 form bg-body needs-validation mt-1" novalidate>
                            <div class="modal-body">
                              <div class="container p-3 text-center">
                                  <div class="row">
                                    <div class="col-md-5">
                                      <p class="text-dark fs-6">
                                        <b>Reasignación de Distribuidor</b><br>
                                       {{$glob1->iddis}} {{$glob1->nombre_distribuidor}}<br>
                                        <b class="fs-9 text-primary fw-normal">({{$glob1->nombre_coordinador}})</b>
                                      </p>
                                    </div>

                                    <div class="col"> 
                                      <h2 class="text-primary"><i class="fa-solid fa-shuffle"></i></h2>
                                    </div>

                                    <input type="text" name="id_anterior" value="{{$glob1->idcoordinador}}" required hidden>

                                    <div class="col-md-5 text-start">
                                        <label class="form-label fs-8 text-dark fw-bold" for="">Coordinador Nuevo</label>
                                        <select class="form-select" name="id_responsable" id="" required>
                                          <option value="" selected>Selecciona...</option>
                                          @foreach($varpromotores as $key)
                                            <option value="{{$key->id}}" selected> {{$key->Nombre}} ({{$key->sucursal}})</option>
                                          @endforeach
                                        </select>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>
                                  </div>
                              </div>
                            </div>

                            <div class="modal-footer">
                              <button type="button" class="btn btn-outline-danger fs-8 rounded-4" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
                              <button type="sumbit" class="btn btn-primary fs-8 rounded-4"> <i class="fa-solid fa-check"></i> Guardar</button>
                            </div>
                        </form>
                      </div>
                    </div>
                  </div>
              </td>


              <td class = "{{$colortabla}} text-start">{{$glob1->sucursal}}</td>
              <td class = "{{$colortabla}}">{{$glob1->iddis}}</td>
              <td class = "{{$colortabla}} text-start">{{$glob1->nombre_distribuidor}}</td>
              <td class = "{{$colortabla}}">{{$glob1->idcoordinador}}</td>
              <td class = "{{$colortabla}} text-start">{{$glob1->nombre_coordinador}}</td>
              <td class = "{{$colortabla}}">
                  @if($status == "activo")
                    <button class="btn bg_success text-success rounded-5 fs-10 text-truncate" for="btn-dia"> ACTIVO </button>
                  @elseif($status == "MORA")
                    <button class="btn bg_orange text-orange rounded-5 fs-10 text-truncate" for="btn-dia"> MORA </button>
                  @elseif($status == "SUSPENDIDO")
                    <button class="btn bg_danger text-danger rounded-5 fs-10 text-truncate" for="btn-dia"> SUSPENDIDO </button>
                  @elseif($status == "inactivo")
                    <button class="btn bg_secondary text-secondary rounded-5 fs-10 text-truncate" for="btn-dia"> INACTIVO </button>
                  @elseif($status == "finado")
                    <button class="btn bg_secondary text-secondary rounded-5 fs-10 text-truncate" for="btn-dia"> FINADO </button>
                  @elseif($status == "cancelado")
                    <button class="btn bg_danger text-danger rounded-5 fs-10 text-truncate" for="btn-dia"> CANCELADO </button>
                  @endif
              </td>
              <td class = "{{$colortabla}}">{{ number_format($glob1->total_canjes, 2)}}</td>
              <td class = "{{$colortabla}}">{{ number_format($glob1->total_prestamos, 2)}}</td>
              <td class = "{{$colortabla}}">{{ number_format($glob1->total_intereses, 2)}}</td>
              <td class = "{{$colortabla}}">{{ number_format($glob1->ivainteres, 2)}}</td>
              <td class = "{{$colortabla}}">{{ number_format($glob1->total_cobertura, 2)}}</td>
              <td class = "{{$colortabla}}">
                @if($glob1->categoria =='BRONCE')
                    <button class="btn bg_coffe text-coffe rounded-5 fs-10 text-truncate" for="btn-dia"><i class="fa-solid fa-award"></i> Bronce</button>
                @elseif($glob1->categoria =='ORO')
                    <button class="btn bg_orange text-orange rounded-5 fs-10 text-truncate" for="btn-oro"><i class="fa-solid fa-trophy"></i> Oro</button> 
                @elseif($glob1->categoria =='PLATA')
                    <button class="btn bg_secondary text-secondary rounded-5 fs-10 text-truncate" for="btn-plat"><i class="fa-solid fa-award"></i> Plata</button>
                @elseif($glob1->categoria =='PLATINO')
                    <button class="btn bg_secondary text-secondary rounded-5 fs-10 text-truncate" for="btn-plat"><i class="fa-solid fa-trophy"></i> Platino</button>
                @elseif($glob1->categoria =='DIAMANTE')
                    <button class="btn bg_primary text-primary rounded-5 fs-10 text-truncate" for="btn-plat"><i class="fa-solid fa-gem"></i> Diamante</button>
                @endif
              </td>
              <td class = "{{$colortabla}}">{{$glob1->clientes_activos}}</td>
              <td class = "{{$colortabla}}">{{$glob1->fecha_inicio}}</td>
              <td class = "{{$colortabla}}">{{ number_format($glob1->capital_disponible, 2)}}</td>
              <td class = "{{$colortabla}}">{{ number_format($glob1->capital_autorizado, 2)}}</td>
      
                @php($abonado = 0)
                @php($comision = 0)
                @php($fechaupago = "No existen pagos")
                @foreach($global2 as $saldos)
                    @if($glob1->iddis == $saldos->id_distribuidor)
                      @php($abonado = $saldos->Abonado)
                      @php($comision = $saldos->comision)
                      @php($fechaupago = $saldos->fecha_ultimopago)
                    @endif
                @endforeach
              <td class = "{{$colortabla}}">{{ number_format($abonado, 2)}}</td>
              <td class = "{{$colortabla}}">{{ number_format($glob1->total_prestamos - $abonado, 2)}}</td>
              <td class = "{{$colortabla}}">{{ number_format($comision, 2)}}</td>


              @php($pagorelacionadoalcorte = 0)
              @foreach($pagoalcorte as $corte)
                  @if($glob1->iddis == $corte->id_distribuidor)
                    @php($pagorelacionadoalcorte = $corte->pagorelacionadoalcorte)
                  @endif
              @endforeach

              <td class = "{{$colortabla}}">{{number_format($pagorelacionadoalcorte,2)}}</td>
              <td class = "{{$colortabla}}">{{$fechaupago}}</td>
                @php($saldoatra = 0)
                @php($quincenasatras = 0)
                @php($diasatra = 0)
                @php($pagos_incomp = 0)
                
                @foreach($global3 as $saldoss)
                    @if($glob1->iddis == $saldoss->iddistribuidor && $saldoss->dias_atraso >= 1)
                      @php($saldoatra = $saldoatra + $saldoss->saldoatrasado)
                      @php($quincenasatras = $quincenasatras + $saldoss->quincenasatras)
                      {{-- @php($diasatra = $diasatra + $saldoss->dias_atraso) --}}
                    @endif
                @endforeach
                @php($saldoatra = $saldoatra + $quincenasatras)
              <td class = "{{$colortabla}}">{{ number_format($saldoatra, 2)}}</td>
              <td class = "{{$colortabla}}">{{$diasdeatraso}}</td>
              <td class = "{{$colortabla}} text-start">{{$glob1->name_coord_ant}}</td>
              <td class = "{{$colortabla}}"></td>
              <td class = "{{$colortabla}}">{{$glob1->uservalidocdt}}</td>
              <td class = "{{$colortabla}}"></td>
              <td class = "{{$colortabla}}"></td>
             
            </tr>


            @else
            @endif
            @endforeach
          @endforeach
      </table>
    </div>
  </center>

  <!--<center class="border-0"  style="position: fixed;bottom:0;width:100%;height:8vh;background:#f6f6f6;">-->
  <!--  {{-- TOTALES --}}-->
  <!--  <div class="mb-3">-->
  <!--    <div class="row text-center">-->
  <!--        <h6 class="col m-2 text-truncate text-success border-end"> 00 <br><b class="fs-9 text-secondary">Total</b></h6>-->

  <!--        <h6 class="col m-2 text-truncate text-danger border-end"> 00 <br><b class="fs-9 text-secondary">Total</b></h6>-->

  <!--        <h6 class="col m-2 text-truncate text-primary border-end"> 00 <br><b class="fs-9 text-secondary">Total</b></h6>-->

  <!--        <h6 class="col m-2 text-truncate text-orange border-end"> 00 <br><b class="fs-9 text-secondary">Total</b </h6>-->

  <!--        <h6 class="col m-2 text-truncate"> 00 <br><b class="fs-9 text-secondary">Total</b></h6>-->
  <!--    </div>-->
  <!--  </div>-->
  <!--</center>-->

  <script>
    $(document).ready(function () {
        new DataTable('#table', {
          responsive: true,
          columnDefs: [
            { responsivePriority: 1, targets: 1 },
            { responsivePriority: 2, targets: 0 }
          ],
          "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
          },
          layout: {
            topStart: {
              buttons: ['excel']
            }
          }
        });
      });
    </script>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection