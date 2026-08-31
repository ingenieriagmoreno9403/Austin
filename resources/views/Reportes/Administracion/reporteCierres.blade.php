@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('ya_existe'))
@php
        echo '<script language="JavaScript">';
        echo 'swal("¡Ya existe un cierre generado!","Verfique la fecha del cierre antes de continuar","warning", {buttons: false,timer: 4500});';
        echo '</script>';  
@endphp
@elseif($mensaje = Session::get('no_es_dia'))
@php
        echo '<script language="JavaScript">';
        echo 'swal("¡No es día para aplicar cierre!","Verfique los días para plicar cierre antes de continuar","warning", {buttons: false,timer: 4500});';
        echo '</script>';  
@endphp
@endif

{{--
@if($permiso1 == "exportar_reporteCierres")
@php($table ="table2")
@else
@php($table ="table3")
@endif
--}}

{{--
@if($vista == "no")

  <div class="outlineCub">
    <div class="row text-center" style="margin-top:200px;">
        <div class="col"></div>
        <div class="col-md-8 col-8 alert text-light border-0" style="background-color: rgba(0, 0, 0, 0.673)">
            <div class="card-body">
                <h1>Vista Restringida hasta los días de cierre</h1>
                <h6>(días 15, 16, 30, 31 y 01)</h6>
            </div>
            <div class="card-footer text-center mt-4">
                <a class="btn btn-outline-secondary rounded-4 fs-8 push" href="/ReportesAdministracion"><i class="fa-solid fa-arrow-left"></i>&nbsp;&nbsp; Volver</a>
            </div>
        </div>
        <div class="col"></div>
    </div>
    
</div>
@endif
--}}

<div class="container-fluid format_page Global bg-body posAll">
  <div>
    <div class="row">
      <div class="center col-md-3 col-12">
        <h3 class="mt-1 animate__animated animate__backInLeft">Reporte de Cierres</h3>
      </div>
  
      <div class="col-md-3 col-12">
        <div class="rounded-3 m-1 p-2 card-tools">
            <p class="fs-6_5 fw-normal m-1 text-center text-tools"><i class="fa-solid fa-screwdriver-wrench"></i> Herramientas</p>
            <div class="row text-center p-2">
                <button class="col btn push2 fs-8 btn-primary rounded-3  m-2 mt-0"  type="button" data-bs-toggle="modal"  data-bs-target="#ModalGeneraCierre">
                  <b class="fs-6"><i class="fa-solid fa-gears"></i></b><br>
                  Generar Cierre
                </button> 

                <button class="col btn push2 fs-8 btn-primary rounded-3  m-2 mt-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasHistoriaCierre" aria-controls="offcanvasTop">
                  <b class="fs-6"><i class="fa-regular fa-clock"></i></b><br>
                  Historial Cierres 
                </button> 
            </div>
        </div>
      </div>

      <div class="col d-none d-md-block"></div>
  
    </div>
  </div> 

 
  {{-- FORMULARIO CIERRE --}} 
  <form action="/datoscierre/insert"  class="g-3 form bg-body needs-validation mt-1" novalidate>
    @csrf

    {{-- MODAL FECHA --}} 
    <div class="modal fade" id="ModalGeneraCierre" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Generar Cierre</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">

            <div class="form-outline">
              <label class="form-label" for="form8Example4">Fecha Corte</label>
              <input type="date"  class="form-control" name="fechacorte" id="fechacorte" maxlength="10" required />
              <div class="valid-feedback">¡Se ve bien!</div>
              <div class="invalid-feedback">Por favor, completa la información requerida.</div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-danger rounded-4 fs-8" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
            <button type="submit" class="btn btn-primary rounded-4 fs-8"><i class="fa-solid fa-check"></i> Generar</button>
          </div>
        </div>
      </div>
    </div>
    
    {{-- TABLA --}} 
    <div class="border-0">
        <div class="table-responsive float-search " id="mydatatable-container">  
            <table {{-- id="{{$table}}" --}} id="tableAdministracionCierres" class="display  table-striped table-hover" style="width: 100%;">
                <thead>
                    <tr class="text-tr">
                      <th class="text-center fw-bold">ID SUCURSAL</th>
                      <th class="text-center fw-bold">SUCURSAL</th>
                      <th class="text-center fw-bold">ID COORDINADOR</th>
                      <th class="text-center fw-bold">COORDINADOR</th>
                      <th class="text-center fw-bold">NO. CONTRATO</th>
                      <th class="text-center fw-bold">ID DISTRIBUIDOR</th>
                      <th class="text-center fw-bold">DISTRIBUIDOR</th>
                      <th class="text-center fw-bold">CAPTAL AUTORIZADO</th>
                      <th class="text-center fw-bold">CAPITAL ACTIVO</th>{{-- capital actual --}}
                      <th class="text-center fw-bold">INTERESES</th>
                      <th class="text-center fw-bold">IVA INTERESES</th>
                      <th class="text-center fw-bold">COBERTURA</th>
                      <th class="text-center fw-bold">IVA COBERTUA</th>
                      <th class="text-center fw-bold">OTROS</th>
                      <th class="text-center fw-bold">DIAS DE ATRASO</th>
                      <th class="text-center fw-bold">SALDO EN RIESGO</th>
                      <th class="text-center fw-bold">SALDO ATRASADO</th>
                      <th class="text-center fw-bold">SALDO</th>
                      <th class="text-center fw-bold">CAPITAL PAGADO</th>{{-- en pago por concepto de capital --}}
                      <th class="text-center fw-bold">CLIENTES VIGENTES</th>
                      <th class="text-center fw-bold">CAPITAL DESEMBOLSADO</th>{{-- monto vale desembolsado --}}
                      <th class="text-center fw-bold">PAGO AL CORTE</th>
                      <th class="text-center fw-bold">ABONADO</th>
                      <th class="text-center fw-bold">MAXIMO DÍA DE ATRASO</th>
                      {{-- <th class="text-center fw-bold">FECHA ULTIMO PAGO</th> --}}
                      {{-- <th class="text-center fw-bold">TOTAL</th> --}}
                      <th class="text-center fw-bold">COORDINADOR ANTERIOR</th>
                      <th class="text-center fw-bold">FECHA DE REASIGNACION</th>
                      <th class="text-center fw-bold">FECHA ACTIVACIÓN</th>
                      <th class="text-center fw-bold">ESTADO ACTUAL</th> 
                    </tr>
                </thead>


                <tbody>
                    @php($saldo = 0)
                    @foreach ($consulta_1 as $item)
                      <tr class="text-tr">
                        @php($saldo_riesgo = 0)
                        @foreach ($consulta_2 as $item2)
                          @if($item->id_distribuidor ==  $item2->iddistribuidor)
                            @php($saldo_riesgo = $item2->saldo_riesgo)
                          @endif
                        @endforeach

                        @php($abonado = 0)
                        @foreach ($consulta_3 as $item3)
                          @if($item->id_distribuidor ==  $item3->id)
                          @php($abonado = $item3->abonado)
                          @endif
                        @endforeach

                        @php($fechaultimopago = "0000/00/00")
                        @php($saldoatrasado = 0)
                        @php($dias_atraso = 0)
                        @foreach ($consulta_4 as $item4)
                          @if($item->id_distribuidor ==  $item4->iddistribuidor)
                              @php($fechaultimopago =$item4->fecha_ultimopago)
                              @php($saldoatrasado = $item4->saldoatrasado)
                              @php($dias_atraso = $item4->dias_atraso)
                          @endif
                        @endforeach

                        @php($saldo = 0)
                        @foreach ($consulta_5 as $item5)
                          @if($item->id_distribuidor ==  $item5->iddis)
                          @php($saldo = $saldo + $item5->saldo)
                          @break
                          @endif
                        @endforeach

                        @php($capital_pagado = 0)
                        @foreach ($consulta_6 as $item6)
                          @if($item->id_distribuidor ==  $item6->dis)
                            @php($capital_pagado = $capital_pagado + $item6->capital_pagado)
                            @break
                          @endif
                        @endforeach

                        @php($pagorelacionadoalcorte = 0)
                        @foreach ($consulta_7 as $item7)
                          @if($item->id_distribuidor ==  $item7->id_distribuidor)
                          @php($pagorelacionadoalcorte = $pagorelacionadoalcorte + $item7->pagorelacionadoalcorte)
                          @break
                          @endif
                        @endforeach

                        @php($maximosdiasatraso = 0)
                        @foreach ($diasatrasomaximo as $dato)
                          @if($item->id_distribuidor ==  $dato->id_distribuidor)
                          @php($maximosdiasatraso = $maximosdiasatraso + $dato->maximosdiasatraso)
                          @break
                          @endif
                        @endforeach

                        @php($capitaldesembolsado = 0)
                        @foreach ($capitaldesembolsadoalcorte as $data)
                          @if($item->id_distribuidor ==  $data->iddistribuidor)
                          @php($capitaldesembolsado = $capitaldesembolsado + $data->capitaldesembolsado)
                          @break
                          @endif
                        @endforeach

                        @php($saldoatra = 0)
                        @php($diasdeatraso = 0)
                        @php($quincenasatras = 0)
                        @foreach($global3 as $diaatr)
                          @if($item->id_distribuidor == $diaatr->iddistribuidor)
                            @if($diaatr->dias_atraso < 0)
                                @php($diasdeatraso = 0)
                            @else
                                @php($diasdeatraso = $diasdeatraso + $diaatr->dias_atraso)
                            @endif
                            @php($saldoatra = $saldoatra + $diaatr->saldoatrasado)
                            @php($quincenasatras = $quincenasatras + $diaatr->quincenasatras)
                          @endif
                        @endforeach
                        @php($saldoatra = $saldoatra + $quincenasatras)

                        @if($diasdeatraso >= 7)
                          @php($colortabla = "text-danger")
                          @php($status = "SUSPENDIDO")
                        @elseif($diasdeatraso >= 1)
                          @php($colortabla = "text-danger")
                          @php($status = "MORA")
                        @endif

                        <td class="text-dark"><input type="text" hidden name="numero_suc[]" value="{{$item->id}}">{{$item->id}}</td>
                        <td class="text-dark text-start"><input type="text" hidden name="sucursal[]" value="{{$item->sucursal}}">{{$item->sucursal}}</td>
                        <td class="text-dark"><input type="text" hidden name="numero_cor[]" value="{{$item->id_coordinador}}">{{$item->id_coordinador}}</td>
                        <td class="text-dark text-start"><input type="text" hidden name="nombre_cor[]" value="{{$item->coordinador}}">{{$item->coordinador}}</td>
                        <td class="text-dark"><input type="text" hidden name="numero_contrato[]" value="{{$item->n_contrato}}">{{$item->n_contrato}}</td>
                        <td class="text-dark"><input type="text" hidden name="id_dis[]" value="{{$item->id_distribuidor}}">{{$item->id_distribuidor}}</td>
                        <td class="text-dark text-start"><input type="text" hidden name="distribuidor[]" value="{{$item->distribuidor}}">{{$item->distribuidor}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="capital_autorizado[]" value="{{$item->capital_autorizado}}">$ {{ number_format($item->capital_autorizado, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="capital_activo[]" value="{{$item->capital_activo}}">$ {{ number_format($item->capital_activo, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="number" hidden name="intereses[]" value="{{$item->intereses}}">$ {{ number_format($item->intereses, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="iva_intereses[]" value="{{$item->iva_intereses}}">$ {{ number_format($item->iva_intereses, 2)}}</td>
                        {{-- <td class="text-dark text-truncate"><input type="text" hidden name="total[]" value="{{$item->total}}">{{ number_format($item->total, 2)}}</td> --}}
                        <td class="text-dark text-truncate"><input type="text" hidden name="cobertura[]" value="{{$item->cobertura}}">$ {{ number_format($item->cobertura, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="iva_cobertura[]" value="{{$item->iva_cobertura}}">$ {{ number_format($item->iva_cobertura, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="otros[]" value="{{$item->otros}}">$ {{ number_format($item->otros, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="diasatraso[]" value="{{$diasdeatraso}}">{{$diasdeatraso}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="saldo_riesgo[]" value="{{$saldo_riesgo}}">$ {{ number_format($saldo_riesgo, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="saldoatrasado[]" value="{{$saldoatra}}">$ {{ number_format($saldoatra, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="saldo[]" value="{{$saldo}}">$ {{ number_format($saldo, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="capitalpagado[]" value="{{$capital_pagado}}">$ {{ number_format($capital_pagado, 2)}}</td> 
                        <td class="text-dark text-truncate"><input type="text" hidden name="clientes_vigentes[]" value="{{$item->clientes_vigentes}}">  {{$item->clientes_vigentes}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="capital_desembolsado[]" value="{{$capitaldesembolsado}}">$ {{ number_format($capitaldesembolsado, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="pago_alcorte[]" value="{{$pagorelacionadoalcorte}}">$ {{number_format($pagorelacionadoalcorte, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="abonado[]" value="{{$abonado}}">$ {{ number_format($abonado, 2)}}</td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="maximosdiasatraso[]" value="{{$maximosdiasatraso}}">{{$maximosdiasatraso}}</td>
                        {{-- <td class="text-dark text-truncate"><input type="text" hidden name="fechaultimopago[]" value="{{$fechaultimopago}}">{{$fechaultimopago}}</td> --}}
                        <td class="text-dark text-truncate"><input type="text" hidden name="coord_anterior[]" value=""></td>
                        <td class="text-dark text-truncate"><input type="text" hidden name="fecha_reasignacion[]" value=""></td>
                        <td class="text-dark"><input type="text" hidden name="fecha_activacion[]" value="{{$item->fecha_activacion}}">    {{ \Carbon\Carbon::parse($item->fecha_activacion)->format('d/m/Y') }}
                        </td>
                        <td class="text-dark"><input type="text" hidden name="estado[]" value="{{$item->nombre}}">
                          @if($item->nombre == "activo")
                            <button class="btn bg-success text-light  fs-8 rounded-5 pointer_none"><i class="fa-solid fa-check"></i> Activo</button>
                          @elseif($item->nombre == "inactivo" || $item->nombre == "valera_inac")
                            <button class="btn bg-primary text-light  fs-8 rounded-5 pointer_none"><i class="fa-solid fa-xmark"></i> Inactivo</button>
                          @elseif($item->nombre == "cancelado")
                            <button class="btn text-danger bg-light  fs-8 rounded-5 pointer_none"><i class="fa-solid fa-ban"></i> Cancelado</button>
                          @elseif($item->nombre == "finado")
                            <button class="btn bg-secondary bg-light  fs-8 rounded-5 pointer_none"><i class="fa-solid fa-xmark"></i> Finado</button>
                          @elseif($item->nombre == "suspendido")
                            <button class="btn bg-primary text-light  fs-8 rounded-5 pointer_none"><i class="fa-solid fa-xmark"></i> Suspendido</button>
                          @else
                            <button class="btn bg-secondary text-light  fs-8 rounded-5 pointer_none"><i class="fa-solid fa-xmark"></i> {{$item->nombre}}</button>
                          @endif
                        </td>
                        
                      </tr>
                    @endforeach
                </tbody>
                <tfoot>
                  <tr>
                    <th></th>
                    <th>Subtotal</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                  </tr>
                  <tr>
                    <th></th>
                    <th>Total</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
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
  </form>

  {{-- MODAL HISTORIAL CIERRES --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasHistoriaCierre" aria-labelledby="offcanvasTopLabel" style="width: 50vh!important;">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasTopLabel">Historial Cierres</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body" style="margin-top: -20px;">
        <div class="table-responsive float-search" id="mydatatable-container">  
          <table id="minitable" class="table table-striped table-hover">
            <thead>
              <tr>
                <th class="text-center">Ver</th>
                <th class="text-center">Fecha</th>
                <th class="text-center">Descarga</th>
              </tr>
            </thead>


            <tbody>
              @foreach ($cierreshis as $lsitcierre)
              <tr>
                  <td class="text-dark text-center">
                      <a href="/vercierreant/{{$lsitcierre->fecha_corte}}" class="btn text-primary border-0 fs-9">
                        <i class="fa-solid fa-eye"></i> Ver Cierre
                      </a>
                  </td>
                  <td class="text-dark text-center">{{$lsitcierre->fecha_corte}}</td>
                  <td class="text-dark text-center">
                    <a href="/ExportarCierres/{{$lsitcierre->fecha_corte}}" class="btn text-primary border-0 fs-9">
                      <i class="fa-solid fa-download"></i> Exportar
                    </a>
                  </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
    </div>
  </div>
</div>


<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/tableX.js') }}"></script>
<script>
  var table = $('#dic').DataTable( {
      "dom": 'B<"float-left"l><"float-right"f>t<"float-left"i><"float-right"p><"clearfix">',
      responsive: true,
      scrollY: 280,
      scrollX: false,
      "language": {
          "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
      },
      
  } );
</script>
@endsection