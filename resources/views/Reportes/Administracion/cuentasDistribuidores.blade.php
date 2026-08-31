@extends('layouts.app')
@section('content')



<div class="social-bar">
  <a href="/ReportesAdministracion"  class="iconn icon-user-tie btn border-0">&nbsp;&nbsp;<b><i class="fa-solid fa-chevron-left"></i></b>&nbsp;&nbsp;&nbsp; </a>
</div>


<div class="container-fluid format_page Global bg-body posAll">
  <div class="marginLeft">
    <div class="row">
      <div class="center">
        <h5 class="mt-1 animate__animated animate__backInLeft">Referencias por Distribuidor</h5>
      </div>
    </div>
  </div> 

  <center class="border-0 mt-5">
    <div class="table-responsive float-search " id="mydatatable-container">  
      @if($permiso1 == "exportar_reporteDesem")
        <table id="table2" class="table-striped table-hover" style="width: 100%;">
      @else
        <table id="table3" class="table-striped table-hover" style="width: 100%;">
      @endif
        <thead>
            <tr class="text-tr">
              <th class="text-center fw-bold">ID DISTRIBUIDOR</th>
              <th class="text-center fw-bold">DISTRIBUIDOR</th>
              <th class="text-center fw-bold">SUCURSAL</th>
              <th class="text-center fw-bold">REFERENCIA BANCO AZTECA</th>
              <th class="text-center fw-bold">REFERENCIA BBVA</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cuentas_distribuidores as $item)
            <tr class="text-tr">
              <td class="text-dark fs-8">{{$item->id_distribuidor}}</td>
              <td class="text-dark fs-8 text-start">{{$item->distribuidor}}</td>
              <td class="text-dark fs-8">{{$item->sucursal}}</td>
              <td class="text-dark fs-8">{{$item->referencia}}</td>
              <td class="text-dark fs-8">{{$item->referencia2}}</td>
            </tr>
            @endforeach
      </table>
    </div>
  </center>



  
<script src="{{ asset('js/tableX.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>

@endsection