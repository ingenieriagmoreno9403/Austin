@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('warningFecha'))
@php
        echo '<script language="JavaScript">';
        echo 'swal("¡No existen Movimientos Relacionados!","Puede probar con otro rango de fechas para encontrar conincidencias","warning", {buttons: false,timer: 4500});';
        echo '</script>';  
@endphp
@endif



<div class="container-fluid format_page">
    <div class="marginLeft mb-5">
        <div class="row">
        <div class="center">
            <h3 class="mt-1 animate__animated animate__backInLeft">Reportes Tesoreria</h3>
            <span>Filtrado de reportes dedicado al área de tesorería.</span>
        </div>
        </div>
    </div> 

    <div class="container">
        <div class="row mt-2">
            <div class="col-md-4 mb-3 mr-2 push">
                <a class="animate___animated animate___flipInY card bg-dark text-white border-0 cursor" href="/ReportesTesoreria/Desembolsos">
                    <img src="{{asset('Images/7.png')}}" class="card-img border-0" alt="Inventario_valeras">
                    <div class="card-img-overlay text-start" style="background-color: rgba(0, 0, 0, 0.337)"><br><br>
                        <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5"><h4 class="text-light">Reporte Desmbolsos</h4></div>
                        <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -30px;" type="submit">Reporte de desmbolsos de créditos delimitado por fechas.</div>
                    </div>
                </a>
            </div>

            <div class="col-md-4 mb-3 mr-2 push">
                <a class="animate___animated animate___flipInY card bg-dark text-white border-0 cursor" href="/ReportesTesoreria/Gastos">
                    <img src="{{asset('Images/8.png')}}" class="card-img border-0" alt="Inventario_valeras">
                    <div class="card-img-overlay text-start" style="background-color: rgba(0, 0, 0, 0.337)"><br><br>
                        <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5"><h4 class="text-light">Reporte Gastos</h4></div>
                        <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -30px;" type="submit">Reporte de gastos de créditos delimitado por fechas.</div>
                    </div>
                </a>
            </div>


            <div class="col-md-4 mb-3 mr-2 push">
              <a class="animate___animated animate___flipInY card bg-dark text-white border-0 cursor" href="/ReportesTesoreria/Ingresos">
                  <img src="{{asset('Images/9.png')}}" class="card-img border-0" alt="Inventario_valeras">
                  <div class="card-img-overlay text-start" style="background-color: rgba(0, 0, 0, 0.337)"><br><br>
                      <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5"><h4 class="text-light">Reporte Ingresos</h4></div>
                      <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -30px;" type="submit">Reporte de gastos de créditos delimitado por fechas.</div>
                  </div>
              </a>
          </div>
        </div>

       
         
    </div>
</div>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection