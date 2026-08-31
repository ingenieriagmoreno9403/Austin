@extends('layouts.app')
@section('content')

<div class="social-bar">
  <a href="/ReportesAdministracion/Cierres"  class="iconn icon-user-tie btn border-0">&nbsp;&nbsp;<b><i class="fa-solid fa-chevron-left"></i></b>&nbsp;&nbsp;&nbsp; </a>
</div>

<div class="container-fluid format_page Global bg-body posAll">
  <div class="marginLeft" style="position: fixed;top:0;width:100%;height:7vh;">
    <div class="row">
      <div class="center col-md-2 col-12">
        <h5 class="mt-1 animate__animated animate__backInLeft">Historial  de Cierres</h5>
      </div>
      <div class="col d-none d-md-block"></div>
    </div>
  </div> 

  <center class="border-0 mt-3" style="position: fixed;top:8vh;height:85vh;width:100%;">
    <div class="table-responsive float-search " id="mydatatable-container">  
      <table id="table2" class="display  table-striped table-hover" style="width: 100%;">
          <thead>
              <tr class="text-tr">
                      <th class="text-center fw-bold">ID SUCURSAL</th>
                      <th class="text-center fw-bold">SUCURSAL</th>
                      <th class="text-center fw-bold">ID COORDINADOR</th>
                      <th class="text-center fw-bold">COORDINADOR</th>
                      <th class="text-center fw-bold">NO. CONTRATO</th>
                      <th class="text-center fw-bold">ID DISTRIBUIDOR</th>
                      <th class="text-center fw-bold">DISTRIBUIDOR</th>
                      <th class="text-center fw-bold">FECHA ACTIVACIÓN</th>
                      <th class="text-center fw-bold">ESTADO ACTUAL</th> 
                      <th class="text-center fw-bold">CAPTAL AUTORIZADO</th>
                      <th class="text-center fw-bold">CAPITAL ACTIVO</th>{{-- capital actual --}}
                      <th class="text-center fw-bold">INTERESES</th>
                      <th class="text-center fw-bold">IVA INTERESES</th>
                      {{-- <th class="text-center fw-bold">TOTAL</th> --}}
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
                      <th class="text-center fw-bold">FECHA CORTE</th>
                      <th class="text-center fw-bold">PAGO AL CORTE</th>
                      <th class="text-center fw-bold">ABONADO</th>
                      <th class="text-center fw-bold">MAXIMO DÍA DE ATRASO</th>
                      {{-- <th class="text-center fw-bold">FECHA ULTIMO PAGO</th> --}}
                      <th class="text-center fw-bold">COORDINADOR ANTERIOR</th>
                      <th class="text-center fw-bold">FECHA DE REASIGNACION</th>
              </tr>
          </thead>
          
          <tbody>
              @foreach ($datoscierre as $item)
              <tr class="text-tr">
                <td class="text-dark">{{$item->numero}}</td>
                <td class="text-dark text-start">{{$item->sucursal}}</td>
                <td class="text-dark">{{$item->numero_cor}}</td>
                <td class="text-dark text-start">{{$item->nombre_cor}}</td>
                <td class="text-dark">{{$item->numero_contrato}}</td>
                <td class="text-dark">{{$item->iddistribuidor}}</td>
                <td class="text-dark text-start">{{$item->distribuidor}}</td>
                <td class="text-dark">{{$item->fecha_activacion}}</td>
                <td class="text-dark">
                  @if($item->estado == "activo")
                    <button class="btn bg-success text-light  fs-11 rounded-5 pointer_none"><i class="fa-solid fa-check"></i> Activo</button>
                  @elseif($item->estado == "inactivo" || $item->estado == "valera_inac")
                    <button class="btn bg-primary text-light  fs-11 rounded-5 pointer_none"><i class="fa-solid fa-xmark"></i> Inactivo</button>
                  @elseif($item->estado == "cancelado")
                    <button class="btn text-danger bg-light  fs-11 rounded-5 pointer_none"><i class="fa-solid fa-ban"></i> Cancelado</button>
                  @elseif($item->estado == "finado")
                    <button class="btn bg-secondary bg-light  fs-11 rounded-5 pointer_none"><i class="fa-solid fa-xmark"></i> Finado</button>
                  @elseif($item->estado == "suspendido")
                    <button class="btn bg-primary text-light  fs-11 rounded-5 pointer_none"><i class="fa-solid fa-xmark"></i> Suspendido</button>
                  @else
                    <button class="btn bg-secondary text-light  fs-11 rounded-5 pointer_none"><i class="fa-solid fa-xmark"></i> {{$item->nombre}}</button>
                  @endif
                </td> 
                <td class="text-dark">{{ number_format($item->capitalautorizado, 2)}}</td>
                <td class="text-dark">{{ number_format($item->capital, 2)}}</td>
                <td class="text-dark">{{ number_format($item->intereses, 2)}}</td>
                <td class="text-dark">{{ number_format($item->ivaintereses, 2)}}</td>
                <td class="text-dark">{{ number_format($item->cobertura, 2)}}</td>
                <td class="text-dark">{{ number_format($item->ivacobertura, 2)}}</td>
                <td class="text-dark">{{ number_format($item->otros, 2)}}</td>
                <td class="text-dark">{{$item->diasatraso}}</td>
                <td class="text-dark">{{ number_format($item->saldo_riesgo, 2)}}</td>
                <td class="text-dark">{{ number_format($item->saldo_atrasado, 2)}}</td>
                <td class="text-dark">{{ number_format($item->saldo, 2)}}</td>
                <td class="text-dark">{{ number_format($item->capitalpagado, 2)}}</td>
                <td class="text-dark">{{ number_format($item->clientes_vigentes, 2)}}</td>
                <td class="text-dark">{{ number_format($item->capital_desembolsado, 2)}}</td>
                <td class="text-dark">{{$item->fecha_corte}}</td>
                <td class="text-dark">{{ number_format($item->pago_alcorte, 2)}}</td>
                <td class="text-dark">{{ number_format($item->abonado, 2)}}</td>
                <td class="text-dark">{{ number_format($item->maximosdiasatraso, 2)}}</td>
                <td class="text-dark"></td>
                <td class="text-dark"></td>
                {{-- <td class="text-dark">{{$item->fechaultimopago}}</td> --}}
                {{-- <td class="text-dark">{{$item->ultimopago}}</td> --}}
              </tr>
              @endforeach
          </tbody>
      </table>
    </div>
  </center>
  </div>


  
<script src="{{ asset('js/tableX.js') }}"></script>
@endsection