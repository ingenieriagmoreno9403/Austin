@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('PDFwarning'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡Seleccione el archivo correcto!", text: "El formato permitido de archivos admitido es .pdf"});';
            echo '</script>'; 
    @endphp
@endif
@php
    $notificaciones = 0
@endphp

@foreach($listaservicio_enc as $item)
    @php
        $fechaLimite = \Carbon\Carbon::parse($item->fecha_limite);
        $fechaCreacion = \Carbon\Carbon::parse($item->fecha_inicio);
        $hoy = \Carbon\Carbon::now();
        $diasRestantes = $hoy->diffInDays($fechaLimite, false);    

        if ($diasRestantes <= 5 && $diasRestantes > 0 && $item->estado != "FINALIZADO"){
            $notificaciones = $notificaciones + 1;
        }elseif ($diasRestantes <= 0 && $item->estado != "FINALIZADO"){
            $notificaciones = $notificaciones + 1;
        }
    @endphp
@endforeach

<div class="container-fluid format_page">
    <!-- Encabezado -->
    <div class="row">
        <div class="col-lg-3 col-12 start-center">
            <h3 class="mt-1 animate_animated animate_backInLeft">Servicios</h3>
            <span class="p-0 m-0 d-none d-md-block fs-8">Suministros, Integraciones y Proyectos.</span>
        </div>

        <div class="col-lg-9 col-12 center-end">
            <div class="dropdown">
                <button class="btn btn-baseColor fs-7 mb-2 dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-plus"></i> Crear Nuevo
                </button>
                
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                    <li><a class="dropdown-item" href="/Servicios/Captura/Datos/Suministro">Suministro</a></li>
                    <li><a class="dropdown-item" href="/Servicios/Captura/Datos/Integración">Integración</a></li>
                </ul>
            </div>

            <a class="btn btn-baseColor-light fs-7 mb-2" href="">
                <i class="fa-solid fa-chart-simple"></i> Análisis de Riesgo
            </a>

            <button type="button" class="btn btn-baseColor position-relative mb-2" id="liveToastBtn">
                @if($notificaciones > 0)
                    <i class="fa-solid fa-bell"></i>
                @else
                    <i class="fa-regular fa-bell"></i>
                @endif
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                    {{$notificaciones}} +
                    <span class="visually-hidden">unread messages</span>
                </span>
            </button>
        </div>
    </div>

    <!-- Tarjetas resumen -->
    <div class="row mt-3 mb-3">
        @php
            $borradores = 0;
            $enespera = 0;
            $enproceso = 0;
            $finalizados = 0;
        

            if(!$totales_estados->isEmpty()){
                foreach($totales_estados as $item){
                    if($item->estado == "BORRADOR"){
                        $borradores = $borradores + $item->totales;
                    }elseif($item->estado == "EN ESPERA"){
                        $enespera = $enespera + $item->totales;
                    }elseif($item->estado == "EN PROCESO"){
                        $enproceso = $enproceso + $item->totales;
                    }elseif($item->estado == "FINALIZADO"){
                        $finalizados = $finalizados + $item->totales;
                    }
                }
            }
        @endphp 

        <!-- Total de Borradores -->
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-secondary text-white">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="mb-0 text-truncate d-block">Borradores</small>
                            <h4 class="mb-0">{{ $borradores }}</h4>
                        </div>
                        <i class="fa-solid fa-eraser fs-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total de Espera -->
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="mb-0 text-truncate d-block">En Espera</small>
                            <h4 class="mb-0">{{ $enespera }}</h4>
                        </div>
                        <i class="fa-solid fa-pause fs-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total de proceso -->
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="mb-0 text-truncate d-block">En Proceso</small>
                            <h4 class="mb-0">{{ $enproceso }}</h4>
                        </div>
                        <i class="fa-solid fa-bars-progress fs-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total de Finalizados -->
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-danger text-white">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="mb-0 text-truncate d-block">Finalizados</small>
                            <h4 class="mb-0">{{ $finalizados }}</h4>
                        </div>
                        <i class="fa-solid fa-hourglass-end fs-4 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="row mt-3 mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fa-solid fa-filter"></i> Filtros de Búsqueda</h6>
                </div>
                <div class="card-body">
                    <form id="filtrosForm" class="row g-3">
                        <div class="col-md-3">
                            <label for="filtroFechaInicio" class="form-label fs-8">Fecha Inicio Desde</label>
                            <input type="date" class="form-control form-control-sm" id="filtroFechaInicio" name="fecha_inicio_desde">
                        </div>
                        <div class="col-md-3">
                            <label for="filtroFechaInicioHasta" class="form-label fs-8">Fecha Inicio Hasta</label>
                            <input type="date" class="form-control form-control-sm" id="filtroFechaInicioHasta" name="fecha_inicio_hasta">
                        </div>
                        <div class="col-md-3">
                            <label for="filtroFechaLimiteDesde" class="form-label fs-8">Fecha Límite Desde</label>
                            <input type="date" class="form-control form-control-sm" id="filtroFechaLimiteDesde" name="fecha_limite_desde">
                        </div>
                        <div class="col-md-3">
                            <label for="filtroFechaLimiteHasta" class="form-label fs-8">Fecha Límite Hasta</label>
                            <input type="date" class="form-control form-control-sm" id="filtroFechaLimiteHasta" name="fecha_limite_hasta">
                        </div>
                        <div class="col-md-3">
                            <label for="filtroEstado" class="form-label fs-8">Estado</label>
                            <select class="form-select form-select-sm" id="filtroEstado" name="estado">
                                <option value="">Todos</option>
                                <option value="BORRADOR">Borrador</option>
                                <option value="EN ESPERA">En Espera</option>
                                <option value="EN PROCESO">En Proceso</option>
                                <option value="FINALIZADO">Finalizado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtroServicio" class="form-label fs-8">Servicio</label>
                            <select class="form-select form-select-sm" id="filtroServicio" name="servicio">
                                <option value="">Todos</option>
                                <option value="Suministro">Suministro</option>
                                <option value="Integración">Integración</option>
                                <option value="Proyecto">Proyecto</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtroVendedor" class="form-label fs-8">Vendedor</label>
                            <select class="form-select form-select-sm" id="filtroVendedor" name="vendedor">
                                <option value="">Todos</option>
                                @php
                                    $vendedoresUnicos = collect($listaservicio_enc)->pluck('nombre_vendedor')->unique()->sort()->values();
                                @endphp
                                @foreach($vendedoresUnicos as $vendedor)
                                    <option value="{{ $vendedor }}">{{ $vendedor }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-sm me-2" id="btnAplicarFiltros">
                                <i class="fa-solid fa-filter"></i> Aplicar Filtros
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnLimpiarFiltros">
                                <i class="fa-solid fa-eraser"></i> Limpiar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla principal  --}}
    <div class="row mt-3">
        <div class="row p-4 pt-0 pb-0">
            <div class="col-md-6 col-12">
                <div class="row">
                    <div class="col">
                        <h6 class="fs-8 text-truncate"><button class="btn btn-warning pointer-blocked rounded-5"></button> Comparativa Precios</h6>
                    </div>

                    <div class="col">
                        <h6 class="fs-8 text-truncate"><button class="btn btn-info pointer-blocked rounded-5"></button> Orden de Compra</h6>
                    </div>

                    <div class="col">
                        <h6 class="fs-8 text-truncate"><button class="btn btn-primary pointer-blocked rounded-5"></button> Recepción  Mercancía</h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Folio</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Servicio</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th class="text-truncate">Fecha Inicio</th>
                        <th class="text-truncate">Fecha Limite</th>
                        <th class="text-truncate">Proceso</th>
                      
                        <th class="text-truncate">Herrmienta</th>
                    </tr>
                </thead>

                <tbody>
   
                    @foreach($listaservicio_enc as $item)
                        @if($item->servicio != "Proyecto")
                            <tr>
                                <td class="text-truncate">{{$item->id}}</td>
                                <td class="text-truncate">{{$item->folio}}</td>
                                <td style="font-size:11px!important;">
                                    {{ $item->nombre }}
                                </td>
                                <td>
                                    @if($item->estado == "BORRADOR")
                                        <span class="badge badge-secondary fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                    @elseif($item->estado == "EN ESPERA")
                                        <span class="badge badge-primary fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                    @elseif($item->estado == "EN PROCESO")
                                        <span class="badge badge-success fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                    @elseif($item->estado == "FINALIZADO")
                                        <span class="badge badge-danger fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                    @endif
                                </td>

                                <td class="fs-10">
                                    @if($item->servicio == "Suministro")
                                        <span class="badge badge-orange-dark fs-10"> {{$item->servicio}} </span>
                                    @elseif($item->servicio == "Integración")
                                        <span class="badge badge-primary-dark fs-10"> {{$item->servicio}} </span>
                                    @elseif($item->servicio == "Proyecto")
                                        <span class="badge badge-success-dark fs-10"> {{$item->servicio}} </span>
                                    @endif
                                </td>

                                <td style="font-size:11px!important;">
                                {{$item->nombre_atencion}}  <br> <b>{{ $item->razon_social}}</b>
                                </td>

                                <td style="font-size:11px!important;">
                                    {{$item->nombre_vendedor}}
                                </td>

                                <td style="font-size:11px!important;">
                                    {{ \Carbon\Carbon::parse($item->fecha_inicio)->format('d/m/Y') }}
                                    
                                </td>

                                <td style="font-size:11px!important;">
                                    @php
                                        $fechaLimite = \Carbon\Carbon::parse($item->fecha_limite);
                                        $fechaCreacion = \Carbon\Carbon::parse($item->fecha_inicio);
                                        $hoy = \Carbon\Carbon::now();
                                        $diasRestantes = $hoy->diffInDays($fechaLimite, false);
                                    @endphp

                                    {{ \Carbon\Carbon::parse($item->fecha_limite)->format('d/m/Y') }}
                                    @if ($diasRestantes <= 5 && $diasRestantes > 0 && $item->estado != "FINALIZADO")
                                        <span class="badge bg-warning text-dark">{{ $diasRestantes }} días</span>
                                    @elseif ($diasRestantes <= 0 && $item->estado != "FINALIZADO")
                                        <span class="badge bg-danger">Vencida</span>
                                    @endif
                                </td>

                                <td class="text-truncate">
                                    @if($item->estado == "EN ESPERA")
                                    <div class="progress text-center">
                                        @if(!is_null($item->licitacion))
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: 33.33%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                                        @endif
                                        @if(!is_null($item->order_compra))
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 33.33%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        @endif

                                        @if( $item->recepcion > 0)
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: 33.33%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        @endif

                                        @if(is_null($item->licitacion) && is_null($item->order_compra))
                                        <div class="text-secondary text-center" style="padding-left: 10px"> 0%</div>
                                        @endif
                                    </div>
                                    @else
                                    <span class="fs-8 text-secondary">(No aplica)</span>
                                    @endif
                                </td>

                                <td class="text-truncate">
                                
                                    @if($item->nivel_progreso >= 3)
                                        {{-- EDITAR --}}
                                        <a class="btn btn-primary m-0" href="/Servicios/Editar/Datos/{{$item->servicio}}/{{$item->id}}" title="Ver cotizacion">
                                            <i class="fa-solid fa-pen fs-7"></i> 
                                        </a> 
                                    @else
                                        {{-- COMPLETAR CAPTURA --}}
                                        @if($item->servicio == "Proyecto")
                                            <a class="btn btn-info m-0" href="/Servicios/Captura/CostosProyecto/{{$item->servicio}}/{{$item->id}}" title="Ver Progresos">
                                                <i class="fa-solid fa-play fs-7"></i>
                                            </a>
                                        @else
                                            <a class="btn btn-info m-0" href="/Servicios/ValidaProgreso/{{$item->servicio}}/{{$item->nivel_progreso}}/{{$item->id}}" title="Ver Progresos">
                                                <i class="fa-solid fa-play fs-7"></i>
                                            </a>
                                        @endif
                                    @endif

                                    @if($item->servicio == "Suministro")
                                        <a class="btn btn-warning m-0" href="/Servicios/Cotizacion/{{$item->id}}/{{$item->servicio}}" title="Ver">
                                            <i class="fa-solid fa-clipboard-check fs-7"></i>
                                        </a>
                                    @else
                                        <a class="btn btn-warning m-0" href="/Servicios/Ver/Inicio/{{$item->id}}/{{$item->servicio}}" title="Ver">
                                            <i class="fa-solid fa-clipboard-check fs-7"></i>
                                        </a>
                                    @endif

                                    <a class="btn btn-success m-0" href="{{ route('facturacion', ['id' => $item->id, 'servicio' => $item->servicio]) }}" title="Facturación">
                                        <i class="fa-solid fa-file-invoice-dollar fs-7"></i>
                                    </a>

                                    @if(!is_null($item->licitacion) && !is_null($item->order_compra) && $item->recepcion > 0)
                                    <button class="btn btn-danger m-0" onclick="finalizarServicio({{ $item->id }}, '{{ $item->folio }}', '{{ $item->nombre }}')" title="Marcar como Entregado">
                                        <i class="fa-regular fa-circle-check fs-7"></i>
                                    </button> 
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>


    {{-- Avisos --}}
    @if($notificaciones > 0)
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 5"  id="liveToast">
            <div class="text-end">
                <div class="btn btn-danger shadow mb-2 fs-9" id="cerrar" onclick="closetoast()">
                    <i class="fa-solid fa-xmark"></i> Cerrar
                </div>
            </div>

            @foreach($listaservicio_enc as $item)
                @php
                    $fechaLimite = \Carbon\Carbon::parse($item->fecha_limite);
                    $fechaCreacion = \Carbon\Carbon::parse($item->fecha_inicio);
                    $hoy = \Carbon\Carbon::now();
                    $diasRestantes = $hoy->diffInDays($fechaLimite, false);
                    $notificaciones = 0
                @endphp
        

                @if($diasRestantes <= 5 && $diasRestantes >= 0 && $item->estado != "FINALIZADO")
                    <div class="toast mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header text-orange">
                            <i class="fa-solid fa-circle-exclamation me-2"></i>
                            <strong class="me-auto">{{$item->folio}}</strong>
                            <small class="text-muted">just now</small>
                        </div>

                        <div class="toast-body bg-body">
                            <b>Nombre</b> {{$item->nombre}} <br>
                            <b>Servicio</b> {{$item->servicio}} <br>
                            <b>Estado</b> 
                                @if($item->estado == "BORRADOR")
                                    <span class="badge badge-secondary fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                @elseif($item->estado == "EN ESPERA")
                                    <span class="badge badge-primary fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                @elseif($item->estado == "EN PROCESO")
                                    <span class="badge badge-success fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                @elseif($item->estado == "FINALIZADO")
                                    <span class="badge badge-danger fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                @endif
                            <br>
                            <b>Fecha Limite</b> {{ \Carbon\Carbon::parse($item->fecha_limite)->format('d/m/Y') }} <span class="badge bg-warning text-dark">{{ $diasRestantes }} días</span>
                        </div>
                    </div>
                @elseif ($diasRestantes < 0 && $item->estado != "FINALIZADO")
                    <div class="toast mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header text-danger">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            <strong class="me-auto">{{$item->folio}}</strong>
                            <small class="text-muted">just now</small>
                        </div>

                        <div class="toast-body bg-body">
                            <b>Nombre</b> {{$item->nombre}} <br>
                            <b>Servicio</b> {{$item->servicio}} <br>
                            <b>Estado</b> 
                                @if($item->estado == "BORRADOR")
                                    <span class="badge badge-secondary fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                @elseif($item->estado == "EN ESPERA")
                                    <span class="badge badge-primary fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                @elseif($item->estado == "EN PROCESO")
                                    <span class="badge badge-success fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                @elseif($item->estado == "FINALIZADO")
                                    <span class="badge badge-danger fs-10"> {{ucwords(strtolower($item->estado))}} </span>
                                @endif
                            <br>
                            <b>Fecha Limite</b> {{ \Carbon\Carbon::parse($item->fecha_limite)->format('d/m/Y') }}
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>


<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
<script>
  // Variable global para la tabla DataTables y el filtro
  var table;
  var filtroPersonalizado = null;
  
  $(document).ready(function() {
    // Esperar a que table.js inicialice la tabla
    setTimeout(function() {
      table = $('#table').DataTable();
      
      // Función para extraer fecha del formato dd/mm/yyyy
      function extraerFecha(fechaTexto) {
        // Remover badges y otros elementos HTML
        var textoLimpio = $('<div>').html(fechaTexto).text().trim();
        
        // Buscar patrón de fecha dd/mm/yyyy
        var match = textoLimpio.match(/(\d{2})\/(\d{2})\/(\d{4})/);
        if (match) {
          // Convertir a formato yyyy-mm-dd para comparación
          return match[3] + '-' + match[2] + '-' + match[1];
        }
        return '';
      }
      
      // Función para aplicar filtros
      function aplicarFiltros() {
        // Remover filtro anterior si existe
        if (filtroPersonalizado !== null) {
          $.fn.dataTable.ext.search.pop();
          filtroPersonalizado = null;
        }
        
        var fechaInicioDesde = $('#filtroFechaInicio').val();
        var fechaInicioHasta = $('#filtroFechaInicioHasta').val();
        var fechaLimiteDesde = $('#filtroFechaLimiteDesde').val();
        var fechaLimiteHasta = $('#filtroFechaLimiteHasta').val();
        var estado = $('#filtroEstado').val();
        var servicio = $('#filtroServicio').val();
        var vendedor = $('#filtroVendedor').val();
        
        // Solo agregar filtro si hay algún valor seleccionado
        if (fechaInicioDesde || fechaInicioHasta || fechaLimiteDesde || fechaLimiteHasta || 
            estado || servicio || vendedor) {
          
          // Función de filtrado personalizado
          filtroPersonalizado = function(settings, data, dataIndex) {
            // Solo aplicar a nuestra tabla
            if (settings.nTable.id !== 'table') {
              return true;
            }
            
            var row = table.row(dataIndex).node();
            
            // Filtro por Estado (columna 3, índice 3)
            if (estado && estado !== '') {
              var estadoCell = $(row).find('td:eq(3)').text().trim();
              if (!estadoCell.toLowerCase().includes(estado.toLowerCase())) {
                return false;
              }
            }
            
            // Filtro por Servicio (columna 4, índice 4)
            if (servicio && servicio !== '') {
              var servicioCell = $(row).find('td:eq(4)').text().trim();
              if (!servicioCell.toLowerCase().includes(servicio.toLowerCase())) {
                return false;
              }
            }
            
            // Filtro por Vendedor (columna 6, índice 6)
            if (vendedor && vendedor !== '') {
              var vendedorCell = $(row).find('td:eq(6)').text().trim();
              if (!vendedorCell.toLowerCase().includes(vendedor.toLowerCase())) {
                return false;
              }
            }
            
            // Filtro por Fecha Inicio (columna 7, índice 7)
            if (fechaInicioDesde || fechaInicioHasta) {
              var fechaInicioCell = $(row).find('td:eq(7)').html();
              var fechaInicio = extraerFecha(fechaInicioCell);
              
              if (fechaInicio === '') return false; // Si no se puede extraer la fecha, excluir
              
              if (fechaInicioDesde && fechaInicio < fechaInicioDesde) {
                return false;
              }
              if (fechaInicioHasta && fechaInicio > fechaInicioHasta) {
                return false;
              }
            }
            
            // Filtro por Fecha Límite (columna 8, índice 8)
            if (fechaLimiteDesde || fechaLimiteHasta) {
              var fechaLimiteCell = $(row).find('td:eq(8)').html();
              var fechaLimite = extraerFecha(fechaLimiteCell);
              
              if (fechaLimite === '') return false; // Si no se puede extraer la fecha, excluir
              
              if (fechaLimiteDesde && fechaLimite < fechaLimiteDesde) {
                return false;
              }
              if (fechaLimiteHasta && fechaLimite > fechaLimiteHasta) {
                return false;
              }
            }
            
            return true;
          };
          
          $.fn.dataTable.ext.search.push(filtroPersonalizado);
        }
        
        table.draw();
      }
      
      // Función para limpiar filtros
      function limpiarFiltros() {
        $('#filtroFechaInicio').val('');
        $('#filtroFechaInicioHasta').val('');
        $('#filtroFechaLimiteDesde').val('');
        $('#filtroFechaLimiteHasta').val('');
        $('#filtroEstado').val('');
        $('#filtroServicio').val('');
        $('#filtroVendedor').val('');
        
        // Remover filtro personalizado
        if (filtroPersonalizado !== null) {
          $.fn.dataTable.ext.search.pop();
          filtroPersonalizado = null;
        }
        
        table.draw();
      }
      
      // Event listeners
      $('#btnAplicarFiltros').on('click', function() {
        aplicarFiltros();
      });
      
      $('#btnLimpiarFiltros').on('click', function() {
        limpiarFiltros();
      });
      
      // Aplicar filtros al presionar Enter en los campos de fecha
      $('#filtroFechaInicio, #filtroFechaInicioHasta, #filtroFechaLimiteDesde, #filtroFechaLimiteHasta').on('change', function() {
        aplicarFiltros();
      });
    }, 500);
  });

  $(".toast").hide()
  $("#cerrar").hide()
  
  var toastTrigger = document.getElementById('liveToastBtn')
  var toastLiveExample = document.getElementById('liveToast')
  if (toastTrigger) {
    toastTrigger.addEventListener('click', function () {
     // var toast = new bootstrap.Toast(toastLiveExample)
    $(".toast").show()
    $("#cerrar").show()

    })
  }

  function closetoast(){
    $(".toast").hide()
    $("#cerrar").hide()
  }

  // Función para marcar servicio como entregado con confirmación SweetAlert
  function finalizarServicio(id, folio, nombre) {
    Swal.fire({
      title: 'Marcar como Entregado',
      html: `
        <p>¿Deseas marcar como entregado el siguiente servicio?</p>
        <p><strong>Folio:</strong> ${folio}</p>
        <p><strong>Nombre:</strong> ${nombre}</p>
        <div class="mt-3">
          <label for="fechaEntrega" class="form-label"><strong>Fecha de Entrega Realizada:</strong></label>
          <input type="date" 
                 id="fechaEntrega" 
                 class="form-control" 
                 value="${new Date().toISOString().split('T')[0]}" 
                 required>
        </div>
        <p class="text-danger mt-2"><small>Esta acción cambiará el estado del servicio a "FINALIZADO" y no se podrá revertir.</small></p>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, marcar como entregado',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      didOpen: () => {
        // Asegurar que el input de fecha esté visible
        const fechaInput = document.getElementById('fechaEntrega');
        if (fechaInput) {
          fechaInput.focus();
        }
      },
      preConfirm: () => {
        const fechaEntrega = document.getElementById('fechaEntrega').value;
        if (!fechaEntrega) {
          Swal.showValidationMessage('Por favor, ingresa la fecha de entrega realizada.');
          return false;
        }
        return fechaEntrega;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const fechaEntrega = result.value;
        
        // Mostrar loading
        Swal.fire({
          title: 'Marcando como entregado...',
          text: 'Por favor espera',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        // Crear formulario para enviar la petición POST
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/Servicios/Finalizar/' + id;
        
        // Agregar token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        form.appendChild(csrfToken);
        
        // Agregar método PUT (Laravel usa POST con _method)
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'POST';
        form.appendChild(methodField);
        
        // Agregar fecha de entrega
        const fechaField = document.createElement('input');
        fechaField.type = 'hidden';
        fechaField.name = 'fecha_entrega_realizada';
        fechaField.value = fechaEntrega;
        form.appendChild(fechaField);
        
        // Enviar formulario
        document.body.appendChild(form);
        form.submit();
      }
    });
  }
</script>
@endsection
