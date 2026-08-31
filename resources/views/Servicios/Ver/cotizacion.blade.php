@extends('layouts.app')
@section('content')


<div class="container-fluid format_page">
    <div class="g-3 form">
    
        {{-- ENCABEZADO --}}
        <div class="row">
            <div class="col-lg-6 col-12 start-center">
                <h2 class="mt-1 animate_animated animate_backInLeft">Cotización de {{$tipo}} <span class="badge bg-orange fs-6"> Folio: {{ $servicio->folio }}</span></h2>
                <a href="/Servicios" class="btn btn-primary fs-8"><i class="fa-solid fa-house"></i> Inicio</a> 
            </div>

            <div class="col-lg-6 col-12 start-center">
                <div class="row">
                    <div class="col-md-6 col-6 form-outline inputform">
                        <h6 class="fs-8">Nombre</h6>
                        <h6>{{$servicio_encxid->nombre}}</h6>
                    </div>

                
                    <div class="col-md-6 col-6 form-outline inputform">
                        <h6 class="fs-8">Fecha Inicio / Limite</h6>
                        <h6>
                            {{ \Carbon\Carbon::parse($servicio_encxid->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($servicio_encxid->fecha_limite)->format('d/m/Y') }} 
                        </h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- MENU --}}
            <div class="col-md-3 p-3 start-center">
                @if($HisotrialServicio->isNotEmpty())
                    @foreach($HisotrialServicio as $item)
                        {{-- CREACION --}}
                        <div class="text-start">
                            <div class="text-start">
                                <span class="badge bg-success fs-9">Creación</span><br>
                            </div>

                            <span class="text-secondary fs-8">
                                <b class="text-primary">
                                    <i class="fa-solid fa-calendar-days"></i> Fecha
                                </b><br> 

                                {{ \Carbon\Carbon::parse($item->fecha_inicio)->format('d/m/Y') }} 
                                @php
                                    $fechaLimite = \Carbon\Carbon::parse($item->fecha_limite);
                                    $fechaCreacion = \Carbon\Carbon::parse($item->fecha_inicio);
                                    $hoy = \Carbon\Carbon::now();
                                    $diasRestantes = $hoy->diffInDays($fechaLimite, false);
                                @endphp

                                @if ($diasRestantes >= 0 && $diasRestantes <= 3 && $item->estado != "FINALIZADO")
                                    <span class="badge bg-warning text-dark">{{ $diasRestantes }} días</span>
                                @elseif ($diasRestantes < 0 && $item->estado != "FINALIZADO")
                                    <span class="badge bg-danger">Vencida</span>
                                @endif
                            </span><br>
                        </div>
                        
                        {{-- LICITACION --}}
                        @if(!is_null($item->licitacion))
                            <div class="border-start margin-line">
                                <br><br><br>
                            </div>

                            <div class="text-start">
                                <span class="badge bg-orange fs-9">Comparativa</span> 
                                <a class="fs-8" href="/licitaciones/edit/{{$item->licitacion}}">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a><br>
                            </div>

                            <div>
                                <div class="fs-8">
                                    <b>Folio</b> {{$item->folio_licitacion}} <br>
                                    <b>Nombre</b> {{$item->nombre_licitacion}} <br>
                                    <b>Descripción</b> {{$item->descripcion_detalle_licitacion}} <br>
                                    <b>Estado</b> <span class="badge badge-secondary-light fs-10 fw-normal"> {{ ucwords($item->estado_licitacion) }}</span><br>
                                </div>

                                <span class="text-secondary fs-8">
                                    <b class="text-primary">
                                        <i class="fa-solid fa-calendar-days"></i> Fechas
                                    </b><br> 

                                    {{ \Carbon\Carbon::parse($item->fechaini_licitacion)->format('d/m/Y') }} -
                                    {{ \Carbon\Carbon::parse($item->fechafin_licitacion)->format('d/m/Y') }}
                                </span><br>
                            </div>
                        @endif
                        
                        @if(!is_null($item->order_compra))
                            <div class="border-start margin-line">
                                <br><br><br>
                            </div>

                            <div class="text-start">
                                    <span class="badge bg-info fs-9">Ónden(es) de Compra</span>
                                    <a class="fs-8" href="/OrdenCompras">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a><br>
                            </div>

                            <div>
                                <div class="fs-8">
                                    <b>Folio</b> {{$item->folio_oc}} <br>
                                    <b>Nombre</b> {{$item->nombre_oc}} <br>
                                    <b>Descripción</b> {{$item->descripcion_detalle_oc}} <br>
                                    <b>Estado</b> <span class="badge badge-secondary-light fs-10 fw-normal"> {{ ucwords($item->estado_oc) }}</span><br>
                                </div>

                                <span class="text-secondary fs-8">
                                    <b class="text-primary">
                                        <i class="fa-solid fa-calendar-days"></i> Fechas
                                    </b><br> 

                                    {{ \Carbon\Carbon::parse($item->fechaini_oc)->format('d/m/Y') }} -
                                    {{ \Carbon\Carbon::parse($item->fechafin_oc)->format('d/m/Y') }}
                                </span><br>
                            </div>
                        @endif

                        @if(!is_null($item->recepcion) && $item->recepcion > 0)
                            <div class="border-start margin-line">
                                <br><br><br>
                            </div>

                            <div class="text-start">
                                <span class="badge bg-success fs-9">Recepción</span><br>
                            </div>

                            <div>
                                <div class="fs-8">
                                    <b>Estado</b>
                                    @if($item->estado_oc == "cerrada")
                                        <span class="badge badge-success fs-10 fw-normal">Entregada</span>
                                    @else
                                        <span class="badge badge-danger-dark fs-10 fw-normal">Pendiente</span>
                                    @endif
                                </div>
                                <br>

                                <div class="p-2 border rounded-2 bg-body">
                                    <small class="text-secondary fs-9">
                                        <i class="fa-solid fa-circle-question"></i> El estado actual e información más detallada sobre la <b>entrega</b> puede verla directamente en el modulo de 
                                        <a href="/entradas">Recepción de Mercancía <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                    </small>
                                </div>
                                <br>
                            </div>
                        @endif

                        <div class="text-start">
                            <div class="border-start margin-line">
                                <br><br><br>
                            </div>

                            <div class="text-start">
                                <span class="badge bg-danger fs-9">Finalización</span><br>
                            </div>

                            <span class="text-secondary fs-8">
                                <b class="text-primary">
                                    <i class="fa-solid fa-calendar-days"></i> Fecha
                                </b><br>

                                {{ \Carbon\Carbon::parse($item->fecha_limite)->format('d/m/Y') }} 

                                @if ($diasRestantes >= 0 && $diasRestantes <= 3 && $item->estado != "FINALIZADO")
                                    <span class="badge bg-warning text-dark">{{ $diasRestantes }} días</span>
                                @elseif ($diasRestantes < 0 && $item->estado != "FINALIZADO")
                                    <span class="badge bg-danger">Vencida</span>
                                @endif
                            </span><br>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- CUERPO --}}
            <div class="col-md-9 p-5 pb-3 pt-0">

                {{-- ENCABEZADO --}}
                <div class="row m-0">
                    <div class="m-3 mt-0 bg-body border p-3">

                        <table class="fs-8 table-striped table-hover">
                            <tr class="text-center">
                                <th class="badge-orange-dark fw-bold">ATENCIÓN</th>
                                <th class="badge-orange fw-bold">VENDEDOR</th>
                            </tr>

                            <tr>
                                <th>Nombre</th>
                                <th>Nombre</th>
                            </tr>

                            <tr>
                                <td>{{$datos_servicio->nombre_atencion}}</td>
                                <td>{{$datos_servicio->nombre_vendedor}}</td>
                            </tr>

                            {{-- <tr>
                                <th>Puesto</th>
                                <th>Puesto</th>
                            </tr> --}}

                            {{-- <tr>
                                <td>-</td>
                                <td>{{$datos_servicio->puesto_vendedor}}</td>
                            </tr> --}}

                            <tr>
                                <th class="2"><br></th>
                            </tr>

                            <tr class="text-center">
                                <th class="badge-orange-dark fw-bold">EMPRESA</th>

                                <th class="badge-orange fw-bold">EMPRESA</th>
                            </tr>

                            <tr>
                                <th>Razón Social</th>
                                <th>Razón Social</th>
                            </tr>

                            <tr>
                                <td>{{$datos_servicio->nombre_empresa_cliente}}</td>
                                <td>{{$datos_servicio->nombre_empresa_vendedor}}</td>
                            </tr>

                            <tr>
                                <th>RFC</th>
                                <th>RFC</th>
                            </tr>

                            <tr>
                                <td>{{$datos_servicio->rfc_cliente}}</td>
                                <td>{{$datos_servicio->rfc_empresa_vendedor}}</td>
                            </tr>

                            <tr>
                                <th>Dirección</th>
                                <th>Dirección</th>
                            </tr>

                            <tr>
                                <td>{{$datos_servicio->direccion_cliente}}</td>
                                <td>{{$datos_servicio->direccion_empresa_vendedor}}</td>
                            </tr>

                            <tr>
                                <th>Télefono</th>
                                <th>Télefono</th>
                            </tr>

                            <tr>
                                <td>{{$datos_servicio->clientes_telefono}}</td>
                                <td>{{$datos_servicio->telefono_vendedor}}</td>
                            </tr>

                            <tr>
                                <th>Correo Electrónico</th>
                                <th>Correo Electrónico</th>
                            </tr>

                            <tr>
                                <td>{{$datos_servicio->clientes_correo}}</td>
                                <td>{{$datos_servicio->correo_vendedor}}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- COTIZACIONES REALIZADAS --}}
                @if($cotizacion->isNotEmpty())
                <div class="row m-1 mt-3 mb-3">
                    <div class="col-12 mb-3">
                        <h3 class="fw-bold">
                             Cotizaciones Realizadas
                        </h3>
                    </div>
                    
                    @php
                        $i = 1;
                    @endphp

                    @foreach($cotizacion as $item)
                        @php
                            $badgeClass = 'bg-secondary';
                            if($item->estado == 'REALIZADA') $badgeClass = 'bg-info';
                            elseif($item->estado == 'AUTORIZADA') $badgeClass = 'bg-warning';
                            elseif($item->estado == 'CERRADA') $badgeClass = 'bg-success';
                            elseif($item->estado == 'BORRADOR') $badgeClass = 'bg-secondary';
                        @endphp
                        
                        <div class="col-md-4 mb-3">
                            <div class="card shadow-sm h-100 border-start border-3 {{ $badgeClass == 'bg-success' ? 'border-success' : ($badgeClass == 'bg-warning' ? 'border-warning' : ($badgeClass == 'bg-info' ? 'border-info' : 'border-secondary')) }}">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="fa-solid fa-calendar-days me-2"></i>
                                        Cotización #{{$i++}}
                                    </h6>
                                    <span class="badge {{$badgeClass}}">{{$item->estado}}</span>
                                </div>

                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-muted">
                                                <i class="fa-solid fa-calendar me-1"></i> Fecha:
                                            </small>
                                            <strong>{{date('d/m/Y', strtotime($item->fecha))}}</strong>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-muted">
                                                <i class="fa-solid fa-dollar-sign me-1"></i> Subtotal:
                                            </small>
                                            <strong>$ {{number_format($item->subtotal, 2)}}</strong>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-muted">
                                                <i class="fa-solid fa-percent me-1"></i> IVA:
                                            </small>
                                            <strong>$ {{number_format($item->iva, 2)}}</strong>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between mb-2 border-top pt-2">
                                            <small class="text-muted fw-bold">
                                                <i class="fa-solid fa-calculator me-1"></i> Total:
                                            </small>
                                            <strong class="text-primary fs-5">$ {{number_format($item->total, 2)}}</strong>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">
                                            <i class="fa-solid fa-toggle-on me-1"></i> Cambiar Estado:
                                        </label>
                                        <select 
                                            class="form-select form-select-sm cambiar-estado-cotizacion" 
                                            data-cotizacion-id="{{$item->id}}"
                                            data-servicio-id="{{$servicio->id}}"
                                            data-estado-actual="{{$item->estado}}"
                                        >
                                            <option value="BORRADOR" {{$item->estado == 'BORRADOR' ? 'selected' : ''}}>BORRADOR</option>
                                            <option value="REALIZADA" {{$item->estado == 'REALIZADA' ? 'selected' : ''}}>REALIZADA</option>
                                            <option value="AUTORIZADA" {{$item->estado == 'AUTORIZADA' ? 'selected' : ''}}>AUTORIZADA</option>
                                            <option value="CERRADA" {{$item->estado == 'CERRADA' ? 'selected' : ''}}>CERRADA</option>
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">
                                            <i class="fa-solid fa-user me-1"></i> Creado por: <strong>{{$item->created_by}}</strong>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fa-solid fa-clock me-1"></i> {{date('d/m/Y H:i', strtotime($item->created_at))}}
                                        </small>
                                    </div>

                                    <div class="d-grid gap-2 mt-3">
                                        <a href="/Servicios/Cotizacion/Exportar/{{$item->id}}" target="_blank" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-download"></i> Descargar PDF
                                        </a>
                                        @if($item->estado == 'BORRADOR')
                                        <button 
                                            type="button" 
                                            class="btn btn-danger btn-sm btn-eliminar-cotizacion" 
                                            data-cotizacion-id="{{$item->id}}"
                                            data-servicio-id="{{$servicio->id}}"
                                        >
                                            <i class="fa-solid fa-trash"></i> Eliminar Borrador
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif

      
                {{-- FILTRO DE PRODUCTOS PARA SUMINISTRO --}}
                @if(strtolower($tipo) == 'suministro' && $productos_suministro->isNotEmpty())
                <div class="row p-3" id="seccion-productos">
                    <h3 class="fw-bold"> Crear Cotización</h3>
                    <h6 class="mb-3 fw-bold text-secondary"><i class="fa-solid fa-circle-check text-orange"></i> Selección de Propuesta</h6>

                    @php
                        $productos_agrupados = $productos_suministro->groupBy('producto_id');
                    @endphp
                    
                    <div class="accordion" id="accordionProductos">
                        @foreach($productos_agrupados as $producto_id => $propuestas)
                            @php
                                $primer_producto = $propuestas->first();
                                $propuestas_por_proveedor = $propuestas->groupBy('proveedor_id');
                                $accordion_id = 'producto_' . $producto_id;
                            @endphp
                            
                            <div class="accordion-item mb-2">
                                <h2 class="accordion-header" id="heading{{ $producto_id }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $accordion_id }}" aria-expanded="false" aria-controls="{{ $accordion_id }}">
                                        <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                            <div>
                                                <i class="fa-solid fa-box me-2 text-primary"></i>
                                                <strong>{{ Str::limit($primer_producto->nombre_producto, 50) }}</strong>
                                                <span class="badge bg-info ms-2">{{ $propuestas->count() }} propuesta(s)</span>
                                            </div>
                                            {{-- <small class="text-muted me-3">{{ $primer_producto->descripcion_producto }}</small> --}}
                                        </div>
                                    </button>
                                </h2>
                                <div id="{{ $accordion_id }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $producto_id }}" data-bs-parent="#accordionProductos">
                                    <div class="accordion-body p-3">
                                        @foreach($propuestas_por_proveedor as $proveedor_id => $propuestas_proveedor)
                                            @php
                                                $primer_propuesta = $propuestas_proveedor->first();
                                            @endphp
                                            <div class="card mb-3 border-start border-secondary border-3 tarjeta-proveedor" data-producto-id="{{ $producto_id }}" data-proveedor-id="{{ $proveedor_id }}">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8">
                                                            <h6 class="mb-2">
                                                                <i class="fa-solid fa-truck me-2 text-success"></i>
                                                                <strong>{{ $primer_propuesta->proveedor }}</strong>
                                                                <span class="badge bg-success ms-2 d-none badge-seleccionado">
                                                                    <i class="fa-solid fa-check"></i> Seleccionado
                                                                </span>
                                                            </h6>
                                                            <div class="row g-2 small text-muted">
                                                                <div class="col-md-3">
                                                                    <i class="fa-solid fa-hashtag"></i> <strong>Cantidad:</strong> {{ $primer_propuesta->cantidad_solicitada }} 
                                                                    {{ $primer_propuesta->unidad_medida }}
                                                                </div>
                                                                {{-- <div class="col-md-3">
                                                                    <i class="fa-solid fa-tag"></i> <strong>Marca:</strong> {{ $primer_propuesta->marca ?? 'N/A' }}
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <i class="fa-solid fa-clock"></i> <strong>T. Entrega:</strong> {{ $primer_propuesta->t_entrega ?? 'N/A' }}
                                                                </div> --}}
                                                                <div class="col-md-6">
                                                                    <i class="fa-solid fa-dollar-sign"></i> <strong>P. Unitario:</strong> $ {{ number_format($primer_propuesta->subtotal, 2) }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <div class="mb-2">
                                                                <h5 class="text-primary mb-0">$ {{ number_format($primer_propuesta->total, 2) }}</h5>
                                                                <small class="text-muted">Total</small>
                                                            </div>
                                                            <button 
                                                                type="button"
                                                                class="btn btn-success btn-sm btn-agregar-propuesta w-100" 
                                                                data-producto-id="{{ $producto_id }}"
                                                                data-proveedor-id="{{ $proveedor_id }}"
                                                                data-producto-nombre="{{ $primer_producto->nombre_producto }}"
                                                                data-producto-descripcion="{{ $primer_producto->descripcion_producto }}"
                                                                data-cantidad="{{ $primer_propuesta->cantidad_solicitada }}"
                                                                data-unidad="{{ $primer_propuesta->unidad_medida }}"
                                                                data-proveedor="{{ $primer_propuesta->proveedor }}"
                                                                data-marca="{{ $primer_propuesta->marca ?? '' }}"
                                                                data-t-entrega="{{ $primer_propuesta->t_entrega ?? '' }}"
                                                                data-p-unitario="{{ $primer_propuesta->subtotal }}"
                                                                data-total="{{ $primer_propuesta->total }}"
                                                                id="btn_propuesta_{{ $producto_id }}_{{ $proveedor_id }}"
                                                            >
                                                                <i class="fa-solid fa-plus"></i> Agregar Propuesta
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

              
  
                {{--TABLA PRINCIPAL--}}
                <div class="row m-0">
                    <h6 class="mb-3 fw-bold text-secondary"><i class="fa-solid fa-circle-check text-orange"></i> Previsualización de partidas</h6>
                    <div class="m-2 mt-0">
                        <table class="table table-hover table-striped" id="tabla-preview">
                            <thead>
                                <tr>
                                    <th class="badge-orange text-dark text-center" colspan="8">
                                        <h6>SERVICIO <b>{{strtoupper($tipo)}}</b></h6>
                                    </th>
                                </tr>
                                <tr>
                                    <th class="bg-orange" colspan="8"></th>
                                </tr>
                                <tr>
                                    <th class="table-secondary text-center">ACCIONES</th>
                                    <th class="table-secondary">PDA</th>
                                    <th class="table-secondary">CANTIDAD</th>
                                    <th class="table-secondary">DESCRIPCIÓN</th>
                                    <th class="table-secondary">MARCA</th>
                                    <th class="table-secondary">T.ENTREGA</th>
                                    <th class="table-secondary">P. UNITARIO</th>
                                    <th class="table-secondary">TOTAL</th>
                                </tr>
                                <tr>
                                    <th class="bg-orange" colspan="8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($i = 1)
                                @php($suma_total = 0)
                                {{-- Los productos se cargarán dinámicamente desde JavaScript --}}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="text-end">SUBTOTAL</td>
                                    <td id="subtotal-productos">$ {{number_format(0,2)}}</td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="text-end">IVA <b class="text-secondary fs-8 fw-normal">(16 %)</b></td>
                                    <td id="iva-productos">$ {{number_format(0,2)}}</td>
                                </tr>
                                <tr>
                                    <td colspan="7" class="text-end table-warning fw-bold">TOTAL</td>
                                    <td id="total-productos" class="table-warning fw-bold">$ {{number_format(0,2)}}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>


                {{-- PIE DE PAGINA --}}
                <div class="row mt-4 mb-4 m-2">
                    <div class="col-md-8 mb-3 mb-md-0">
                        <div class="card border-start border-danger border-4 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <div class="bg-danger bg-opacity-10 rounded-circle p-3 d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fa-solid fa-triangle-exclamation text-danger fs-5"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <label for="observaciones" class="form-label fw-bold mb-2">
                                            <i class="fa-solid fa-comment-dots me-2"></i>Observaciones
                                        </label>
                                        <textarea 
                                            name="observaciones" 
                                            id="observaciones" 
                                            class="form-control" 
                                            rows="4" 
                                            placeholder="Ingrese las observaciones o notas adicionales para esta cotización..." 
                                            required
                                        >{{$servicio->otrosconceptos3}}</textarea>
                                        <small class="text-muted">
                                            <i class="fa-solid fa-info-circle me-1"></i>
                                            Esta información será incluida en la cotización.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <form action="/Servicios/CrearCotizacion/{{$servicio->id}}" method="GET" class="needs-validation h-100" id="form1" novalidate>
                            @csrf
                            
                            <div class="card shadow-sm h-100">
                                <div class="card-body p-3 d-flex flex-column">
                                    <label for="reviso" class="form-label fw-bold mb-2 text-center">
                                        <i class="fa-solid fa-user-check me-2"></i>Revisó
                                    </label>
                                    
                                    <select name="reviso" id="reviso" class="form-select select2 mb-2" required>
                                        <option value="">Seleccione un empleado...</option>
                                        @foreach($EmpleadosActivos as $item)
                                            @if($datos_servicio->reviso == $item->id && !is_null($datos_servicio->reviso))
                                                <option value="{{$item->id}}" selected>{{$item->Nombre}}</option>
                                            @else
                                                <option value="{{$item->id}}">{{$item->Nombre}}</option>
                                            @endif
                                        @endforeach
                                    </select>

                                    <small class="text-muted text-center mt-auto">
                                        <i class="fa-solid fa-info-circle me-1"></i>
                                        Seleccione la persona responsable de revisar esta cotización.
                                    </small>

                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, seleccione un revisor.</div>
                                </div>
                            </div>
                            
                            <!-- Campo oculto para las observaciones -->
                            <input type="hidden" name="observaciones" id="observaciones_hidden">
                        </form>
                    </div>
                </div>

                {{--ACCIONES--}}
                <div class="row mt-5 mb-5 justify-content-center">
                    {{-- <div class="col-md-3 p-2">
                        <button class="btn btn-primary btn-mode fs-8 push" style="width: 100%"><i class="fa-solid fa-broom"></i> Limpiar</button>
                    </div> --}}
                    <div class="col-md-3 p-2">
                        @if(strtolower($tipo) == 'integracion' || strtolower($tipo) == 'integración')
                            <button class="btn btn-baseColor btn-mode fs-8 push" onclick="Guardar()" id="btnGuardar" style="width: 100%" disabled><i class="fa-solid fa-check"></i> Guardar</button>
                        @else
                        <button class="btn btn-baseColor btn-mode fs-8 push" onclick="Guardar()" style="width: 100%"><i class="fa-solid fa-check"></i> Guardar</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>


<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>

@if(strtolower($tipo) == 'suministro' && $productos_suministro->isNotEmpty())
<script>
    // Almacenar las propuestas seleccionadas
    let propuestasSeleccionadas = {};
    let contadorPDA = 1;

    // Función para formatear números
    function formatearNumero(numero) {
        return new Intl.NumberFormat('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(numero);
    }

    // Función para actualizar los totales
    function actualizarTotales() {
        let subtotal = 0;
        
        // Calcular subtotal sumando todos los totales de las filas
        document.querySelectorAll('#tabla-preview tbody tr').forEach(row => {
            const totalCell = row.querySelector('td:last-child');
            if (totalCell) {
                const totalTexto = totalCell.textContent.replace('$', '').replace(/,/g, '').trim();
                subtotal += parseFloat(totalTexto) || 0;
            }
        });

        const iva = subtotal * 0.16;
        const total = subtotal + iva;

        // Actualizar los totales en el footer
        document.getElementById('subtotal-productos').textContent = '$ ' + formatearNumero(subtotal);
        document.getElementById('iva-productos').textContent = '$ ' + formatearNumero(iva);
        document.getElementById('total-productos').textContent = '$ ' + formatearNumero(total);
    }

    // Función para agregar fila a la tabla de previsualización
    function agregarFilaAPreview(datos) {
        const tbody = document.querySelector('#tabla-preview tbody');
        
        // Verificar si ya existe una fila para este producto
        const filaExistente = tbody.querySelector(`tr[data-producto-id="${datos.productoId}"]`);
        if (filaExistente) {
            // Obtener el PDA actual de la fila existente
            const pdaActual = filaExistente.querySelector('td:nth-child(2)').textContent;
            // Obtener los valores actuales de marca y t_entrega si existen
            const inputMarcaActual = filaExistente.querySelector('input.input-marca');
            const inputTEntregaActual = filaExistente.querySelector('input.input-t-entrega');
            const marcaActual = inputMarcaActual ? inputMarcaActual.value : (datos.marca || '');
            const tEntregaActual = inputTEntregaActual ? inputTEntregaActual.value : (datos.tEntrega || '');
            
            // Actualizar la fila existente manteniendo el PDA
            filaExistente.innerHTML = `
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-propuesta" data-producto-id="${datos.productoId}">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
                <td class="fw-bold">${pdaActual}</td>
                <td>${datos.cantidad}</td>
                <td>${datos.descripcion}</td>
                <td>
                    <input type="text" class="form-control form-control-sm input-marca" value="${marcaActual}" placeholder="Marca" data-producto-id="${datos.productoId}">
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm input-t-entrega" value="${tEntregaActual}" placeholder="T. Entrega" data-producto-id="${datos.productoId}">
                </td>
                <td>$ ${formatearNumero(datos.pUnitario)}</td>
                <td>$ ${formatearNumero(datos.total)}</td>
            `;
            
            // Agregar event listener al botón de eliminar
            const btnEliminar = filaExistente.querySelector('.btn-eliminar-propuesta');
            if (btnEliminar) {
                btnEliminar.addEventListener('click', function() {
                    eliminarFilaDePreview(datos.productoId);
                    actualizarTotales();
                    actualizarEstadoBotones();
                });
            }
            return;
        }

        // Crear nueva fila
        const tr = document.createElement('tr');
        tr.setAttribute('data-producto-id', datos.productoId);
        tr.innerHTML = `
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm btn-eliminar-propuesta" data-producto-id="${datos.productoId}">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
            <td class="fw-bold">${contadorPDA++}</td>
            <td>${datos.cantidad}</td>
            <td>${datos.descripcion}</td>
            <td>
                <input type="text" class="form-control form-control-sm input-marca" value="${datos.marca || ''}" placeholder="Marca" data-producto-id="${datos.productoId}">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm input-t-entrega" value="${datos.tEntrega || ''}" placeholder="T. Entrega" data-producto-id="${datos.productoId}">
            </td>
            <td>$ ${formatearNumero(datos.pUnitario)}</td>
            <td>$ ${formatearNumero(datos.total)}</td>
        `;
        tbody.appendChild(tr);
        
        // Agregar event listener al botón de eliminar
        const btnEliminar = tr.querySelector('.btn-eliminar-propuesta');
        if (btnEliminar) {
            btnEliminar.addEventListener('click', function() {
                eliminarFilaDePreview(datos.productoId);
                actualizarTotales();
                actualizarEstadoBotones();
            });
        }
    }

    // Función para eliminar fila de la tabla de previsualización
    function eliminarFilaDePreview(productoId) {
        const tbody = document.querySelector('#tabla-preview tbody');
        const fila = tbody.querySelector(`tr[data-producto-id="${productoId}"]`);
        if (fila) {
            fila.remove();
            // Eliminar la propuesta seleccionada
            delete propuestasSeleccionadas[productoId];
            // Recalcular PDAs
            contadorPDA = 1;
            tbody.querySelectorAll('tr').forEach(row => {
                const pdaCell = row.querySelector('td:nth-child(2)');
                if (pdaCell) {
                    pdaCell.textContent = contadorPDA++;
                }
            });
        }
    }

    // Función para actualizar el estado de los botones
    function actualizarEstadoBotones() {
        document.querySelectorAll('.btn-agregar-propuesta').forEach(btn => {
            const productoId = btn.getAttribute('data-producto-id');
            const proveedorId = btn.getAttribute('data-proveedor-id');
            const filaExistente = document.querySelector(`#tabla-preview tbody tr[data-producto-id="${productoId}"]`);
            const propuestaSeleccionada = propuestasSeleccionadas[productoId];
            
            // Obtener la tarjeta del proveedor
            const tarjetaProveedor = document.querySelector(`.tarjeta-proveedor[data-producto-id="${productoId}"][data-proveedor-id="${proveedorId}"]`);
            const badgeSeleccionado = tarjetaProveedor ? tarjetaProveedor.querySelector('.badge-seleccionado') : null;
            
            if (filaExistente && propuestaSeleccionada && String(propuestaSeleccionada.proveedorId) === String(proveedorId)) {
                // Este es el proveedor seleccionado
                btn.disabled = true;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-secondary');
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Agregado';
                
                // Marcar la tarjeta como seleccionada
                if (tarjetaProveedor) {
                    tarjetaProveedor.classList.add('border-success');
                    tarjetaProveedor.classList.remove('border-primary');
                    if (badgeSeleccionado) {
                        badgeSeleccionado.classList.remove('d-none');
                    }
                }
            } else {
                // Este NO es el proveedor seleccionado
                btn.disabled = false;
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-success');
                btn.innerHTML = '<i class="fa-solid fa-plus"></i> Agregar Propuesta';
                
                // Quitar marca de seleccionado de la tarjeta
                if (tarjetaProveedor) {
                    tarjetaProveedor.classList.remove('border-success');
                    tarjetaProveedor.classList.add('border-secondary');
                    if (badgeSeleccionado) {
                        badgeSeleccionado.classList.add('d-none');
                    }
                }
            }
        });
    }

    // Event listener para los botones
    document.addEventListener('DOMContentLoaded', function() {
        const botones = document.querySelectorAll('.btn-agregar-propuesta');
        
        botones.forEach(boton => {
            boton.addEventListener('click', function() {
                const productoId = this.getAttribute('data-producto-id');
                const proveedorId = this.getAttribute('data-proveedor-id');
                
                // Guardar la propuesta seleccionada
                propuestasSeleccionadas[productoId] = {
                    productoId: productoId,
                    proveedorId: proveedorId,
                    productoNombre: this.getAttribute('data-producto-nombre'),
                    descripcion: this.getAttribute('data-producto-descripcion'),
                    cantidad: this.getAttribute('data-cantidad'),
                    unidad: this.getAttribute('data-unidad'),
                    proveedor: this.getAttribute('data-proveedor'),
                    marca: this.getAttribute('data-marca'),
                    tEntrega: this.getAttribute('data-t-entrega'),
                    pUnitario: parseFloat(this.getAttribute('data-p-unitario')),
                    total: parseFloat(this.getAttribute('data-total'))
                };

                // Agregar o actualizar la fila en la tabla de previsualización
                agregarFilaAPreview(propuestasSeleccionadas[productoId]);
                
                // Actualizar totales
                actualizarTotales();
                
                // Actualizar el estado de los botones
                actualizarEstadoBotones();
            });
        });

        // Inicializar contador de PDA basado en filas existentes
        const tbody = document.querySelector('#tabla-preview tbody');
        if (tbody) {
            contadorPDA = tbody.querySelectorAll('tr').length + 1;
        }
        
        // Inicializar el estado de los botones
        actualizarEstadoBotones();
    });

    // Función para copiar observaciones al campo oculto antes de enviar
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form1');
        const textareaObservaciones = document.getElementById('observaciones');
        const hiddenObservaciones = document.getElementById('observaciones_hidden');
        
        if (form && textareaObservaciones && hiddenObservaciones) {
            form.addEventListener('submit', function(e) {
                // Copiar el valor del textarea al campo oculto antes de enviar
                hiddenObservaciones.value = textareaObservaciones.value;
            });
        }
    });

    // Función para guardar la cotización
    function Guardar() {
        // Validar que haya productos en la tabla
        const filas = document.querySelectorAll('#tabla-preview tbody tr');
        if (filas.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin productos',
                text: 'Debe agregar al menos un producto a la cotización.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Validar el formulario
        const form = document.getElementById('form1');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Recopilar todos los datos de la tabla
        const productos = [];
        filas.forEach((fila, index) => {
            const productoId = fila.getAttribute('data-producto-id');
            const pda = fila.querySelector('td:nth-child(2)').textContent.trim();
            const cantidad = fila.querySelector('td:nth-child(3)').textContent.trim();
            const descripcion = fila.querySelector('td:nth-child(4)').textContent.trim();
            const inputMarca = fila.querySelector('input.input-marca');
            const inputTEntrega = fila.querySelector('input.input-t-entrega');
            const marca = inputMarca ? inputMarca.value : '';
            const tEntrega = inputTEntrega ? inputTEntrega.value : '';
            const pUnitario = fila.querySelector('td:nth-child(7)').textContent.replace('$', '').replace(/,/g, '').trim();
            const total = fila.querySelector('td:nth-child(8)').textContent.replace('$', '').replace(/,/g, '').trim();

            // Buscar el producto_id desde propuestasSeleccionadas
            const propuesta = propuestasSeleccionadas[productoId];
            const producto_id = propuesta ? propuesta.productoId : null;

            productos.push({
                pda: parseInt(pda),
                cantidad: parseInt(cantidad),
                producto_id: producto_id,
                marca: marca,
                t_entrega: tEntrega,
                p_unitario: parseFloat(pUnitario),
                total: parseFloat(total)
            });
        });

        // Calcular subtotal, IVA y total
        const subtotal = productos.reduce((sum, p) => sum + p.total, 0);
        const iva = subtotal * 0.16;
        const total = subtotal + iva;

        // Obtener observaciones y revisor
        const observaciones = document.getElementById('observaciones').value;
        const reviso = document.getElementById('reviso').value;

        if (!reviso) {
            Swal.fire({
                icon: 'warning',
                title: 'Revisor requerido',
                text: 'Debe seleccionar un revisor.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Mostrar loading
        Swal.fire({
            title: 'Guardando cotización...',
            text: 'Por favor espere.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Crear formulario para enviar los datos
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('productos', JSON.stringify(productos));
        formData.append('subtotal', subtotal);
        formData.append('iva', iva);
        formData.append('total', total);
        formData.append('observaciones', observaciones);
        formData.append('reviso', reviso);
        formData.append('tipo', '{{ $tipo }}');

        // Enviar datos al servidor
        fetch('/Servicios/CrearCotizacion/{{ $servicio->id }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message || 'Cotización guardada correctamente.',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    // Recargar la página para ver los cambios
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al guardar la cotización.',
                    confirmButtonColor: '#3085d6'
                });
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al guardar la cotización. Por favor, intente nuevamente.',
                confirmButtonColor: '#3085d6'
            });
        });
    }

    // Función para cambiar el estado de la cotización
    document.addEventListener('DOMContentLoaded', function() {
        const selectsEstado = document.querySelectorAll('.cambiar-estado-cotizacion');
        
        selectsEstado.forEach(select => {
            select.addEventListener('change', function() {
                const cotizacionId = this.getAttribute('data-cotizacion-id');
                const servicioId = this.getAttribute('data-servicio-id');
                const estadoActual = this.getAttribute('data-estado-actual');
                const nuevoEstado = this.value;

                // Si el estado no cambió, no hacer nada
                if (estadoActual === nuevoEstado) {
                    return;
                }

                // Confirmar cambio
                Swal.fire({
                    title: '¿Cambiar estado?',
                    html: `¿Está seguro de cambiar el estado de <b>${estadoActual}</b> a <b>${nuevoEstado}</b>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, cambiar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mostrar loading
                        Swal.fire({
                            title: 'Cambiando estado...',
                            text: 'Por favor espere.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Enviar petición
                        fetch(`/Servicios/Cotizacion/CambiarEstado/${cotizacionId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                nuevo_estado: nuevoEstado,
                                servicio_id: servicioId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Éxito!',
                                    text: data.message || 'Estado cambiado correctamente.',
                                    confirmButtonColor: '#3085d6'
                                }).then(() => {
                                    // Recargar la página para ver los cambios
                                    window.location.reload();
                                });
                            } else {
                                // Revertir el select al estado anterior
                                this.value = estadoActual;
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Error al cambiar el estado.',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.close();
                            // Revertir el select al estado anterior
                            this.value = estadoActual;
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al cambiar el estado. Por favor, intente nuevamente.',
                                confirmButtonColor: '#3085d6'
                            });
                        });
                    } else {
                        // Revertir el select al estado anterior si canceló
                        this.value = estadoActual;
                    }
                });
            });
        });

        // Función para eliminar borradores
        const botonesEliminar = document.querySelectorAll('.btn-eliminar-cotizacion');
        
        botonesEliminar.forEach(boton => {
            boton.addEventListener('click', function() {
                const cotizacionId = this.getAttribute('data-cotizacion-id');
                const servicioId = this.getAttribute('data-servicio-id');

                // Confirmar eliminación
                Swal.fire({
                    title: '¿Eliminar borrador?',
                    html: '¿Está seguro de eliminar esta cotización en estado BORRADOR?<br><b>Esta acción no se puede deshacer.</b>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mostrar loading
                        Swal.fire({
                            title: 'Eliminando...',
                            text: 'Por favor espere.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        // Enviar petición
                        fetch(`/Servicios/Cotizacion/Eliminar/${cotizacionId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                servicio_id: servicioId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            Swal.close();
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Eliminado!',
                                    text: data.message || 'Borrador eliminado correctamente.',
                                    confirmButtonColor: '#3085d6'
                                }).then(() => {
                                    // Recargar la página para ver los cambios
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Error al eliminar el borrador.',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.close();
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error al eliminar el borrador. Por favor, intente nuevamente.',
                                confirmButtonColor: '#3085d6'
                            });
                        });
                    }
                });
            });
        });
    });
</script>
@endif

@endsection
                                
