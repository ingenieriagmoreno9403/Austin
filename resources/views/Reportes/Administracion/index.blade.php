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
            <h3 class="mt-1 animate__animated animate__backInLeft">Reportes Administración</h3>
            <span>Filtrado de reportes dedicado al área de administración.</span>
        </div>
        </div>
    </div> 

    

    <div class="container">
        <div class="row mt-1">
            {{-- REPORTE CIRRES --}}
            @if($permiso1== "ver_reporteCierres")
                @php($color ="rgba(0, 0, 0, 0.337)")
                @php($class ="cursor")
            @else
                @php($color ="rgba(89, 89, 89, 0.661)")
                @php($class ="pointer_none")
            @endif
            <div class="col-md-4 mb-3 mr-2 push">
                <a class="animate___animated animate___flipInY card bg-dark text-white border-0 {{$class}}" href="/ReportesAdministracion/Cierres">
                    <img src="{{asset('Images/24.png')}}" class="card-img border-0" alt="Inventario_valeras">
                    <div class="card-img-overlay text-start start-center" style="background-color:{{$color}}"><br><br>
                        <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5 text-start start-center"><h1 class="text-light">Cierres</h1></div>
                        <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start d-none d-md-block" style="margin-top: -30px;" type="submit">Cierres delimitados por fechas para el calculo de totales.</div>
                    </div>
                </a>
            </div>

            {{-- REPORTE COORDINADORES --}}
            @if($permiso2== "ver_reporteCoord")
                @php($color ="rgba(0, 0, 0, 0.337)")
                @php($class ="cursor")
            @else
                @php($color ="rgba(89, 89, 89, 0.661)")
                @php($class ="pointer_none")
            @endif
            <div class="col-md-4 mb-3 mr-2 push">
              <a class="animate___animated animate___flipInY card bg-dark text-white border-0 {{$class}}" href="/ReportesAdministracion/GestionCoordinadores">
                  <img src="{{asset('Images/25.png')}}" class="card-img border-0" alt="Inventario_valeras">
                  <div class="card-img-overlay text-start start-center" style="background-color: {{$color}}"><br><br>
                      <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5 text-start start-center"><h1 class="text-light">Gestión Coordinadores</h1></div>
                      <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start d-none d-md-block" style="margin-top: -30px;" type="submit">Filtrado de datos por coordinador.</div>
                  </div>
              </a>
          </div>

          {{-- REPORTE SUCURSALES --}}
           @if($permiso3== "ver_reporteSuc")
                @php($color ="rgba(0, 0, 0, 0.337)")
                @php($class ="cursor")
            @else
                @php($color ="rgba(89, 89, 89, 0.661)")
                @php($class ="pointer_none")
            @endif
          <div class="col-md-4 mb-3 mr-2 push">
            <a class="animate___animated animate___flipInY card bg-dark text-white border-0 {{$class}}" href="/ReportesAdministracion/GestionSucursales">
                <img src="{{asset('Images/26.png')}}" class="card-img border-0" alt="Inventario_valeras">
                <div class="card-img-overlay text-start start-center" style="background-color: {{$color}}"><br><br>
                    <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5 text-start start-center"><h1 class="text-light">Gestión Sucursales</h1></div>
                    <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start d-none d-md-block" style="margin-top: -30px;" type="submit">Filtrado de datos por sucursales.</div>
                  </div>
            </a>
          </div>

          {{-- REPORTE CANJES --}}
            @if($permiso4== "ver_reporteCanjes")
                @php($color ="rgba(0, 0, 0, 0.337)")
                @php($class ="cursor")
            @else
                @php($color ="rgba(89, 89, 89, 0.661)")
                @php($class ="pointer_none")
            @endif
          <div class="col-md-4 mb-3 mr-2 push" >
            <a class="animate___animated animate___flipInY card bg-dark text-white border-0 {{$class}}" href="/ReportesAdministracion/HistorialCanjes" >
                <img src="{{asset('Images/27.png')}}" class="card-img border-0" alt="Inventario_valeras">
                <div class="card-img-overlay text-start start-center" style="background-color: {{$color}}"><br><br>
                    <div class="animate__animated animate__backInDown btn border-0 card-title fw-bold text-light mt-5 text-start start-center"><h1 class="text-light">Historial de  Canjes</h1></div>
                    <div class="animate__animated animate__backInDown btn border-0 card-text fs-9 text-light text-start d-none d-md-block" style="margin-top: -30px;" type="submit">Filtrado de detalle de canjes por fecha.</div>
                </div>
            </a>
          </div>

            {{-- REPORTE CANJES --}}
            @if($permiso4== "ver_reporteCanjes")
                @php($color ="rgba(0, 0, 0, 0.337)")
                @php($class ="cursor")
            @else
                @php($color ="rgba(89, 89, 89, 0.661)")
                @php($class ="pointer_none")
            @endif

            {{--
            <div class="col-md-4 mb-3 mr-2 push" >
                <a class="animate___animated animate___flipInY card bg-dark text-white border-0 {{$class}}" href="/ReportesAdministracion/CuentasDistribuidores">
                    <img src="{{asset('Images/28.png')}}" class="card-img border-0" alt="Inventario_valeras">
                    <div class="card-img-overlay text-start start-center" style="background-color: {{$color}}"><br><br>
                        <div class="animate__animated animate__backInDown btn border-0 card-title fw-bold text-light mt-5 text-start start-center"><h1 class="text-light">Referencias por Distribuidor</h1></div>
                        <div class="animate__animated animate__backInDown btn border-0 card-text fs-9 text-light text-start d-none d-md-block" style="margin-top: -30px;" type="submit">Filtrado de detalle de referencia por distribuidor.</div>
                    </div>
                </a>
            </div>
            --}}
      </div>
    </div>
</div>
@endsection