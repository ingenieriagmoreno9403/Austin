@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('successcalcular'))
@php
        echo '<script language="JavaScript">';
        echo 'swal("¡Nómina calculada exitosamente!","Proceso realizado de forma correcta","success", {buttons: false,timer: 2000});';
        echo '</script>';  
@endphp
  @elseif($mensaje = Session::get('Errorpermisos'))
  @php
          echo '<script language="JavaScript">';
          echo 'swal("¡No se encontro el permiso para efectuar la accion!","Comunicate al area de sistemas para validar permisos","warning", {buttons: false,timer: 5000});';
          echo '</script>';  
  @endphp
@endif

<div class="social-bar">
  <a href="/reporteGlobal"  class="iconn icon-user-tie btn border-0">&nbsp;&nbsp;<b><i class="fa-solid fa-chevron-left"></i></b>&nbsp;&nbsp;&nbsp; </a>
</div>

@php($porsalr = 0)
@php($pormora = 0)
@if($saldor  != 0)
  @php($porsalr = $saldor /$saldoactualxdistri*100)
  @else
  @php($porsalr=0)
@endif
@if($mora != 0)
  @php($pormora=$mora /$saldoactualxdistri*100)
  @else
  @php($pormora=0)
@endif

<div class="container-fluid format_page Global">
  <div class="marginLeft" style="position: fixed;top:0;width:100%;height:7vh;">
    <div class="row">
        <div class="col mb-1">
          <div class="row p-2">
            @foreach($detalledis as $listadet)
              <h6 class="col border-end m-2 fs-8 text-truncate text-secondary">  #{{$listadet->id}} <br> <b class="fs-9 text-secondary">Distribuidor</b></h6>
              <h6 class="col border-end m-2 fs-8 text-truncate text-primary">  $ {{ number_format($listadet->capital, 2)}} <br> <b class="fs-9 text-secondary">Capital disponible</b></h6>
              <h6 class="col border-end m-2 fs-8 text-truncate text-orange">  $ {{ number_format($listadet->capital_autorizado, 2)}}<br> <b class="fs-9 text-secondary">Capital Autorizado</b> </h6>
              <h6 class="col border-end m-2 fs-8 text-truncate text-success">  $ {{ number_format($saldoactualxdistri, 2)}} <br> <b class="fs-9 text-secondary">Saldo actual</b></h6>
              <h6 class="col border-end m-2 fs-8 text-truncate text-danger">  $ {{ number_format($saldor, 2)}}<br> <b class="fs-9 text-secondary">Saldo en Riesgo</b></h6>
              <h6 class="col border-end m-2 fs-8 text-truncate text-secondary"> % {{number_format($porsalr, 2)}} <br> <b class="fs-9 text-secondary">% Saldo en Riesgo</b></h6>
              <h6 class="col border-end m-2 fs-8 text-truncate text-orange">  $ {{ number_format($mora, 2)}}<br> <b class="fs-9 text-secondary"> Mora</b></h6>
              <h6 class="col border-end m-2 fs-8 text-truncate text-secondary"> % {{number_format($pormora, 2)}} <br> <b class="fs-9 text-secondary">% Mora</b> </h6>
            @endforeach
          </div>
        </div>
    </div>
  </div> 

  <center class="border-0" style="position: fixed;top:10vh;height:85vh;width:100%;">
    <div class="table-responsive float-search " id="mydatatable-container">  
      @if($permiso1 == "exportar_globalDetalleCli")
        <table id="table2" class="display nowrap table-hover" style="width: 100%;">
      @else
        <table id="table3" class="display nowrap table-hover" style="width: 100%;">
      @endif
        <thead>
          <tr class="text-tr">
            <th class="text-center text-truncate fw-bold"># PRESTAMO</th>
            <th class="text-center text-truncate fw-bold">#CLIENTE</th>
            <th class="text-center text-truncate fw-bold">NOMBRE ClIENTE</th>
            <th class="text-center text-truncate fw-bold">INICIO DE PRESTAMO</th>
            <th class="text-center text-truncate fw-bold">TERMINO DE PRESTAMO</th>
            <th class="text-center text-truncate fw-bold">TOTAL DE PRESTAMO</th>
            <th class="text-center text-truncate fw-bold">DIRECCION</th>
            <th class="text-center text-truncate fw-bold">TELEFONO</th>
            <th class="text-center text-truncate fw-bold">CAPITAL</th>
            <th class="text-center text-truncate fw-bold">VALIDO CREDITO</th>
            <th class="text-center text-truncate fw-bold">CUENTA DESEMBOLSO</th>
            
            <th class="text-center text-truncate fw-bold">ABONADO</th>
            <th class="text-center text-truncate fw-bold">SALDO ACTUAL</th>
            <th class="text-center text-truncate fw-bold">PLAZOS PAGADOS</th>
            <th class="text-center text-truncate fw-bold">PAGO POR PLAZO</th>
            <th class="text-center text-truncate fw-bold">TOTAL COBERTURA</th>
            <th class="text-center text-truncate fw-bold">INTERESES</th>
            <th class="text-center text-truncate fw-bold">ULTIMO PAGO</th>
      
          </tr>
        </thead>

        <tbody>
          @foreach($globalcli as $gcli1)
            <tr class="boder-sec">
                <td class = "text-dark text-truncate">{{$gcli1->id_prestamo}}</td>
                <td class = "text-dark text-truncate">{{$gcli1->id_cliente}}</td>
                <td class = "text-dark text-truncate">{{$gcli1->nombre_cliente}}</td>
                <td class = "text-dark text-truncate">{{$gcli1->fecha_inicioprestamo}}</td>
                <td class = "text-dark text-truncate">{{$gcli1->termino_prestamo}}</td>
                <td class = "text-dark text-truncate"> {{ number_format($gcli1->total_prestamo, 2)}}</td>
                <td class = "text-dark text-truncate">{{$gcli1->direccion}}</td>
                <td class = "text-dark text-truncate">{{$gcli1->telefono}}</td>
                <td class = "text-dark text-truncate"> {{ number_format($gcli1->capital, 2)}}</td>
                <td class = "text-dark text-truncate">{{$gcli1->created_by}}</td>
                <td class = "text-dark text-truncate">{{$gcli1->nombre}}</td>


                @php($abonado = 0)
                @php($saldo_actual = 0)
                @php($plazos_pagados = 0)
                @php($pagoxplazo = 0)
                @php($total_cobertura = 0)
                @php($intereses = 0)
                @php($total_plazos = 0)
                @php($fpago = '00/00/0000')
                @foreach($obtenersaldoclientes as $saldoss)
                    @if($gcli1->id_prestamo==$saldoss->idprestamo)
                      @php($abonado = $saldoss->abonado)
                      @php($saldo_actual = $saldoss->total_prestamo -  $saldoss->abonado)
                      @php($plazos_pagados = $saldoss->plazos_pagados)
                      @php($pagoxplazo = $saldoss->pagoxplazo)
                      @php($total_cobertura = $saldoss->total_cobertura)
                      @php($intereses = $saldoss->intereses)
                      @php($total_plazos = $saldoss->total_plazos)
                      @php($fpago = $saldoss->fecha_pago)
                    @endif
                @endforeach
                <td class = "text-dark text-truncate"> {{ number_format($abonado, 2)}}</td>
                <td class = "text-dark text-truncate"> {{ number_format($saldo_actual, 2)}}</td>
                <td class = "text-dark text-truncate"> {{ $plazos_pagados}}/{{ $total_plazos}}</td>
                <td class = "text-dark text-truncate"> {{ number_format($pagoxplazo, 2)}}</td>
                <td class = "text-dark text-truncate"> {{ number_format($total_cobertura, 2)}}</td>
                <td class = "text-dark text-truncate"> {{ number_format($intereses, 2)}}</td>
                <td class = "text-dark text-truncate">
                  @if(is_null($fpago))
                   No hay pagos
                  @else
                    {{$fpago}}
                  @endif
                </td>
                
            </tr> 
          @endforeach
        </tbody>
      </table>
      </div>  
  </center> 
</div>
<script src="{{ asset('js/btnBack1.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/tableX.js') }}"></script>
@endsection