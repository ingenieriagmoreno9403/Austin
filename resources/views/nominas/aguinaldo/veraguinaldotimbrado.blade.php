@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
@if($mensaje = Session::get('successExcel'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "success",title: "¡Acción exitosa!", text: "Archivo importado correctamente, recuerde recalcular en base a lo importado"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('warningExcel'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡No se efectuo la acción!", text: "Revise que el archivo importado cumpla con el formato requerido"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('warningCuenta'))
@php
        echo '<script language="JavaScript">';
        echo 'const Toast = Swal.mixin({';
        echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
        echo 'didOpen: (toast) => {';
        echo '  toast.onmouseenter = Swal.stopTimer;';
        echo '  toast.onmouseleave = Swal.resumeTimer;}});';
        echo 'Toast.fire({ icon: "info",title: "¡No se efectuo la acción!", text: "Revise que las cuentas esten bien seleccionadas y que el saldo sea suficiente para el desembolso"});';
        echo '</script>'; 
@endphp
@elseif($mensaje = Session::get('Errofac'))
@php
        echo '<script language="JavaScript">';
        echo 'const Toast = Swal.mixin({';
        echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
        echo 'didOpen: (toast) => {';
        echo '  toast.onmouseenter = Swal.stopTimer;';
        echo '  toast.onmouseleave = Swal.resumeTimer;}});';
        echo 'Toast.fire({ icon: "info",title: "¡No se efectuo la acción!", text: "Existe un error de facturacion"});';
        echo '</script>'; 
@endphp
@endif

@php($total_fiscal = 0)
@php($total_excedente = 0)
@php($total_pagar = 0)

@foreach($aguinaldotimbrado as $pago)
  @php($total_fiscal = $total_fiscal + $pago->total_pagar_f)
  @php($total_excedente = $total_excedente + $pago->total_pagar_e)
  @php($total_pagar = $total_pagar + $pago->total_pagar)
@endforeach

@if($errors->has('comparacion_ids'))
    <div class="alert alert-{{ session('comparacion_detalle.faltantes') > 0 ? 'warning' : 'success' }}">
        <h4>Resultado de comparación</h4>
        <p>{{ $errors->first('comparacion_ids') }}</p>
        
        @if(session('comparacion_detalle.faltantes') > 0)
            <div class="mt-3">
                <h5>Detalles de registros faltantes ({{ session('comparacion_detalle.faltantes') }}):</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empleado</th>
                            <th>Puesto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(session('comparacion_detalle.detalles_faltantes') as $faltante)
                            <tr>
                                <td>{{ $faltante['id'] }}</td>
                                <td>{{ $faltante['empleado'] }}</td>
                                <td>{{ $faltante['puesto'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-success mt-3">
                ¡Todos los registros coinciden correctamente!
            </div>
        @endif
    </div>
@endif

  {{--------------------------- Encabezado de la pagina----------------------}}
  <div class="container-fluid format_page">
    <!-- Header Section -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center header">
          <div class="d-flex align-items-center">
            <div class="header-icon me-3">
              <i class="fas fa-receipt"></i>
            </div>
            <div>
              <div class="mb-1">
                <a href="/Nominas/Aguinaldos" class="text-muted text-decoration-none fs-8">
                  <i class="fa-solid fa-chevron-left me-1"></i>Aguinaldo
                </a>
              </div>
              <h2 class="mb-0 text-marino fw-bold">Aguinaldos Timbrados</h2>
              <p class="text-muted mb-0">Detalle y descarga de comprobantes</p>
            </div>
          </div>
          <div class="header-actions">
            @if($validaTimbradofallido > 0)
              <button type="button" class="btn btn-primary fs-8 position-relative mb-2" data-bs-toggle="modal" data-bs-target="#modalFacturar" aria-controls="exampleModa">
                <i class="fas fa-file"></i> Facturas Pendientes
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                  + {{$validaTimbradofallido}}
                  <span class="visually-hidden">unread messages</span>
                </span>
              </button>
            @else
              <button type="button" class="btn btn-primary fs-8 position-relative mb-2" disabled>
                <i class="fas fa-file"></i> Facturas Pendientes 
              </button>
            @endif
          </div>
        </div>
      </div>
    </div>
    
  
      <div class="row mt-3">
          <div class="table-responsive">
              <table class="table table-stripped table-hover display" id="tableaguinaldo">
                <thead>
                  <tr>
                      <th></th>
                      <th class="text-truncate">Ver</th>
                      <th class="text-truncate">ID</th>
                      <th class="text-truncate">Nombre Empleado</th>
                      <th class="text-truncate">Días Aguinaldo</th>
                      <th class="text-truncate">Aguinaldo Fiscal</th>
                      <th class="text-truncate">Aguinaldo Excedente</th>
                      <th class="text-truncate">Aguinaldo Gravado</th>
                      <th class="text-truncate">Aguinaldo Exento</th>
                      <th class="text-truncate">Aguinaldo Total</th>
                      <th class="text-truncate">ISR Calculado</th>
                      <th class="text-truncate">Total Pagar Fiscal</th>
                      <th class="text-truncate">Total Pagar Excedente</th>
                      <th class="text-truncate">Total Pagar</th>
                    </tr>
                </thead>
              @php($bol ='no')
                <tbody>
                  @foreach($aguinaldotimbrado as $aguinaldo)
                    <tr>
                      <td></td>
                      <td class="table-light fs-8 text-truncate">
                          <a class="btn btn-primary rounded-3 fs-9 text-truncate" href="/verfacturaaguinaldo/{{$aguinaldo->id_recibo}}" target="_blank">
                            <i class="fa-solid fa-file fs-8"></i> 
                          </a>
                      </td>
                      <td class="table-light fs-8">{{$aguinaldo->idempleado}}</td>
                      <td class="table-light text-start fs-8">{{$aguinaldo->primer_nombre}}  {{$aguinaldo->segundo_nombre}}  {{$aguinaldo->apellido_paterno}}  {{$aguinaldo->apellido_materno}}</td>
                      <td class="bg-0 fs-8">{{$aguinaldo->dias_aguinaldo_pagados}}</td>
                      <td class="bg-0 fs-8">$ {{ number_format($aguinaldo->aguinaldo_f, 2)}}</td>
                      <td class="table-light text-truncate fs-8">$ {{ number_format($aguinaldo->aguinaldo_e, 2)}}</td>
                      <td class="table-light text-truncate fs-8">$ {{ number_format($aguinaldo->aguinaldo_gravado, 2)}}</td>
                      <td class="table-light text-truncate fs-8">$ {{ number_format($aguinaldo->aguinaldo_exento, 2)}}</td>
                      <td class="table-light text-truncate fs-8">$ {{ number_format($aguinaldo->aguinaldo_total, 2)}}</td>
                      <td class="bg-0 text-truncate fs-8">$ {{ number_format($aguinaldo->isr_calculado, 2)}}</td> 
                      <td class="bg-0 text-truncate fs-8">$ {{ number_format($aguinaldo->total_pagar_f, 2)}}</td> 
                      <td class="bg-0 text-truncate fs-8">$ {{ number_format($aguinaldo->total_pagar_e, 2)}}</td> 
                      <td class="bg-0 text-success text-truncate fw-bold  fs-8">$ {{ number_format($aguinaldo->total_pagar, 2)}}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
          </div>  
    </div>
  </div>

  <!-- Modal Facturas -->
  <div class="modal fade" id="modalFacturar" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5>Aguinaldos no facturados</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form action="#" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation mt-1 modern-form" novalidate>
            @csrf
            <div class="modal-body">
              

              <table class="table table-striped table-hover">
                <thead>
                  <tr>
                    <th scope="col">No.</th>
                    <th scope="col">Empleado</th>
                    <th scope="col">Mensaje</th>
                    <th scope="col">Timbrar</th>
                  </tr>
                </thead>
                <tbody>
                  @php($count= 1)
                  @foreach($obtnertimbradosFallidos as $item)
                   @php($count = $count + 1)
                  <tr>
                    <td scope="col">{{$count}}</td>
                    <td>{{$item->primer_nombre}}  {{$item->segundo_nombre}}  {{$item->apellido_paterno}}  {{$item->apellido_materno}}</td>
                    <td>{{$item->mensaje_error}}</td>
                    <td>
                      <a class="btn btn-success rounded-3 fs-9 text-truncate" onclick="mostrarCarga('{{$item->id_aguinaldo_det}}', '{{$item->idaguinaldo_enc}}')">
                        <i class="fa-solid fa-file-invoice-dollar fs-8"></i> Timbrar
                      </a>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            
            </div>


            <div class="row p-4 pt-2">
                <div class="animate__animated animate__swing bg-body border border-warning rounded-3 p-3">
                  <h6 class="text-orange"><i class="fa-solid fa-triangle-exclamation"></i> Recuerde</h6>
                  <p class="fs-9">Al volver a intentar facturar, se realizará la petición y el proceso se repetira, así que debe asegurarse que se corrigieran los erres anter de volver a timbrar.</p>
                </div>
            </div>

            <div class="row p-3 justify-content-center">
              <button type="submit" class="col-4 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Volver a Intentar</button>
            </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal de Carga -->
  <div class="modal fade" id="modalCarga" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalCargaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body text-center p-4">
          <div class="spinner-border text-orange mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <h5 class="text-dark">Procesando timbrado...</h5>
          <p class="text-muted">Por favor espere, esto puede tardar unos minutos.</p>
          <div class="progress mt-3">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-orange" role="progressbar" style="width: 100%"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validaXLSX.js') }}"></script>

<script>
function mostrarCarga(idDetalle, idEncabezado) {
    // Mostrar el modal de carga
    var modalCarga = new bootstrap.Modal(document.getElementById('modalCarga'));
    modalCarga.show();
    
    // Redirigir a la página de timbrado
    setTimeout(function() {
        window.location.href = "/Aguinaldos/Timbrar/" + idEncabezado;
    }, 100);
}

// Agregar estilos CSS personalizados
document.head.insertAdjacentHTML('beforeend', `
    <style>
        .bg-orange {
            background-color: #ff6b00 !important;
        }
        .text-orange {
            color: #ff6b00 !important;
        }
        .progress {
            height: 0.5rem;
        }
    </style>
`);
</script>

@endsection

