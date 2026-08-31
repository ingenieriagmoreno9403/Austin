@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('errorServ'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡Problema al insertar servicio!", text: "Asegurese de capturar toda la información requerida si capturo un cliente desde el incio es probable que se insertará, puede cambiar la opción a cliente existente."});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('errorCli'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡Problema al insertar cliente!", text: "Asegurese de capturar toda la información requerida"});';
            echo '</script>'; 
    @endphp
@endif


<div class="container-fluid format_page">
    <div class="g-3 form">
    
        {{-- ENCABEZADO --}}
        <div class="row">
            <div class="col-lg-6 col-12 start-center">
                <h2 class="mt-1 animate_animated animate_backInLeft">Creación de {{$tipo}} <span class="badge bg-orange fs-6"> Folio: {{ $servicio->folio }}</span></h2>
                <a href="/Servicios" class="btn btn-primary fs-8"><i class="fa-solid fa-house"></i> Inicio</a> 
            </div>

            @foreach($servicio_encxid as $key)
            <div class="col-lg-6 col-12 start-center">
                <div class="row">
                    <div class="col-md-6 col-6 form-outline inputform">
                        <h6 class="fs-8">Nombre</h6>
                        <h6>{{$key->nombre}}</h6>
                    </div>

                
                    <div class="col-md-6 col-6 form-outline inputform">
                        <h6 class="fs-8">Fecha Inicio / Limite</h6>
                        <h6>
                            {{ \Carbon\Carbon::parse($key->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($key->fecha_limite)->format('d/m/Y') }} 
                        </h6>
                    </div>
                </div>
            </div>
            @endforeach
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
                @foreach($datos_servicio as $item)
                <div class="row m-0">
                    <div class="mb-2 bg-body border p-3">

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
                                <td>{{$item->nombre_atencion}}</td>
                                <td>{{$item->nombre_vendedor}}</td>
                            </tr>

                            {{-- <tr>
                                <th>Puesto</th>
                                <th>Puesto</th>
                            </tr> --}}

                            {{-- <tr>
                                <td>-</td>
                                <td>{{$item->puesto_vendedor}}</td>
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
                                <td>{{$item->nombre_empresa_cliente}}</td>
                                <td>{{$item->nombre_empresa_vendedor}}</td>
                            </tr>

                            <tr>
                                <th>RFC</th>
                                <th>RFC</th>
                            </tr>

                            <tr>
                                <td>{{$item->rfc_cliente}}</td>
                                <td>{{$item->rfc_empresa_vendedor}}</td>
                            </tr>

                            <tr>
                                <th>Dirección</th>
                                <th>Dirección</th>
                            </tr>

                            <tr>
                                <td>{{$item->direccion_cliente}}</td>
                                <td>{{$item->direccion_empresa_vendedor}}</td>
                            </tr>

                            <tr>
                                <th>Télefono</th>
                                <th>Télefono</th>
                            </tr>

                            <tr>
                                <td>{{$item->clientes_telefono}}</td>
                                <td>{{$item->telefono_vendedor}}</td>
                            </tr>

                            <tr>
                                <th>Correo Electrónico</th>
                                <th>Correo Electrónico</th>
                            </tr>

                            <tr>
                                <td>{{$item->clientes_correo}}</td>
                                <td>{{$item->correo_vendedor}}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                @php($reviso = $item->reviso)
                @endforeach

                {{-- DIv INFORMÁTICO PARA PROYECTOS --}}
          
                @if(strtolower($tipo) == 'suministro' && !empty($cotizacion_borrador_suministro_json))
                <div class="mt-4 mb-2">
                    <h5 class="mb-3 text-primary"><i class="fa-solid fa-file-invoice"></i> Cotización Guardada (Borrador)</h5>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th class="text-truncate">PDA</th>
                                <th class="text-truncate">CANTIDAD</th>
                                <th class="text-truncate">DESCRIPCIÓN</th>
                                <th class="text-truncate">PROVEEDOR</th>
                                <th class="text-truncate">MARCA</th>
                                <th class="text-truncate">T.ENTREGA</th>
                                <th class="text-truncate">P. UNITARIO</th>
                                <th class="text-truncate">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cotizacion_borrador_suministro_json as $i => $item)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $item['cantidad'] ?? '' }}</td>
                                    <td>{{ $item['descripcion'] ?? '' }}</td>
                                    <td>{{ $item['proveedor'] ?? '' }}</td>
                                    <td>{{ $item['marca'] ?? '' }}</td>
                                    <td>{{ $item['observacion'] ?? '' }}</td>
                                    <td>{{ $item['preciou'] ?? '' }}</td>
                                    <td>$ {{ number_format($item['precio'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mb-4">
                        <button class="btn btn-success fs-8 fw-normal" onclick="reimprimirCotizacionGuardadaSuministro()">
                            <i class="fa-solid fa-print"></i> Imprimir Cotización
                        </button>
                    </div>
                    <script>
                    function reimprimirCotizacionGuardadaSuministro() {
                        const cotizacion = @json($cotizacion_borrador_suministro_json);
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '/Servicios/export_cot_pdf/{{$id}}/suministro';
                        form.target = '_blank'; // 👈 esto abre en nueva pestaña

                        // CSRF
                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = '{{ csrf_token() }}';
                        form.appendChild(csrfToken);
                        // JSON
                        const productosInput = document.createElement('input');
                        productosInput.type = 'hidden';
                        productosInput.name = 'productos_data';
                        productosInput.value = JSON.stringify(cotizacion);
                        form.appendChild(productosInput);
                        document.body.appendChild(form);
                        form.submit();
                        document.body.removeChild(form);
                    }
                    </script>
                </div>
                @endif
                {{-- FILTRO DE PRODUCTOS PARA SUMINISTRO --}}
                @if(strtolower($tipo) == 'suministro')
                <div class="row p-3" id="seccion-productos">
                    <h5 class="mb-3">Gestión de Productos</h5>
                    <div class="border bg-light p-3 rounded-1">
                        <div class="row">
                            <div class="col-md-8 col-6 form-outline">
                                <label class="form-label">Buscar Producto</label>
                                <input class="form-control" type="text" name="buscar_producto" id="buscar_producto" placeholder="Buscar por nombre..." onkeyup="filtrarProductos()">
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-4 col-6 form-outline">
                                <label class="form-label">Precio</label>
                                <input class="form-control" type="number" name="precio_producto" id="precio_producto" placeholder="0" maxlength="10" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <button class="btn btn-primary fs-8" type="button" onclick="mostrarModalProductos()">
                                        <i class="fa-solid fa-search"></i> Ver Productos Disponibles
                                    </button>
                                    <button class="btn btn-baseColor fs-8" type="button" onclick="agregarProducto()">
                                        <i class="fa-solid fa-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal de Productos -->
                <div class="modal fade" id="modalProductos" tabindex="-1" aria-labelledby="modalProductosLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalProductosLabel">Productos Disponibles</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="tabla-productos">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Descripción</th>
                                                <th>Proveedor</th>
                                                <th>Precio</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($productos_suministro as $producto)
                                            <tr class="producto-row">
                                                <td>{{ $producto->producto_id }}</td>
                                                <td>{{ $producto->descripcion_producto }}</td>
                                                <td>{{ $producto->proveedor }}</td>
                                                <td>$ {{number_format($producto->total,2)}}</td>
                                                <td>
                                                    <button class="btn btn-success btn-sm" onclick="seleccionarProducto('{{ $producto->producto_id }}', '{{ $producto->descripcion_producto }}', '{{ $producto->proveedor }}', {{ $producto->total }})">
                                                        <i class="fa-solid fa-check"></i> Seleccionar
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row p-3" id="tabla-productos-container">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="fw-bold">ID PRODUCTO</th>
                                <th class="fw-bold">DESCRIPCIÓN</th>
                                <th class="fw-bold">PROVEEDOR</th>
                                <th class="fw-bold">CANTIDAD</th>
                                <th class="fw-bold">UNIDAD</th>
                                <th class="fw-bold">PRECIO</th>
                                <th class="fw-bold">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-productos-lista">
                            <!-- Los productos se agregarán dinámicamente aquí -->
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- CONCEPTOS DE PROYECTO --}}
                @if(strtolower($tipo) == 'proyecto')
                <div class="row m-0 mb-3">
                    <div class="mb-2 bg-body border p-3">
                        <h5 class="mb-3 text-orange"><i class="fa-solid fa-list me-2"></i>Conceptos del Proyecto</h5>
                        <table class="table table-striped table-hover fs-8" id="tabla-conceptos-proyecto">
                            <thead>
                                <tr class="text-center">
                                    <th class="table-secondary">PARTIDA</th>
                                    <th class="table-secondary">NOMBRE</th>
                                    <th class="table-secondary">DESCRIPCIÓN</th>
                                    <th class="table-secondary">CANTIDAD</th>
                                    <th class="table-secondary">PRECIO UNITARIO</th>
                                    <th class="table-secondary">COSTO</th>
                                    <th class="table-secondary">ESTADO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($i = 1)
                                @forelse($conceptos_proyecto ?? [] as $concepto)
                                    <tr>
                                        <td class="fw-bold text-center">{{$i++}}</td>
                                        <td>{{$concepto->nombre_concepto}}</td>
                                        <td>{{$concepto->descripcion_concepto}}</td>
                                        <td class="text-center">{{$concepto->otrosconceptos1 ?? '-'}}</td>
                                        <td class="text-end">$ {{number_format($concepto->otrosconceptos2 ?? 0,2)}}</td>
                                        <td class="text-end">$ {{number_format($concepto->costo_concepto,2)}}</td>
                                        <td class="text-center">
                                            @if($concepto->otrsoconceptos3 == '1')
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary">Inactivo</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No hay conceptos registrados para este proyecto.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                @if(strtolower($tipo) == 'proyecto')
                <div class="row m-0 mb-3">
                    <div class="mb-2 bg-body border p-3">
                        @foreach($totalespro as $item)
                        <h5 class="mb-3 text-orange"><i class="fa-solid fa-list me-2"></i>Proyecion de Costos</h5>
                        <h5 class="mb-3 text-orange"><i class="fa-solid fa-list me-2"></i>Subtotal: ${{number_format($item->monto_real,2)}}</h5>
                        <h5 class="mb-3 text-orange"><i class="fa-solid fa-list me-2"></i>Total: ${{number_format($item->monto_proyectado,2)}}</h5>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- CONCEPTOS DE COSTOS PARA INTEGRACIÓN --}}
                @if(strtolower($tipo) == 'integracion' || strtolower($tipo) == 'integración')
                <div class="row m-0 mb-3">
                    <div class="mb-2 bg-body border p-3">
                        <h5 class="mb-3 text-orange"><i class="fa-solid fa-list me-2"></i>Proyecion de Costos</h5>
                        <table class="table table-striped table-hover fs-8" id="tabla-conceptos-costos">
                            <thead>
                                <tr class="text-center">
                                    <th class="table-secondary">PARTIDA</th>
                                    <th class="table-secondary">NOMBRE</th>
                                    <th class="table-secondary">DESCRIPCIÓN</th>
                                    <th class="table-secondary">MONTO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($i = 1)
                                @forelse($conceptos_servicio as $concepto)
                                    <tr>
                                        <td class="fw-bold text-center">{{$i++}}</td>
                                        <td>{{$concepto->nombre}}</td>
                                        <td>{{$concepto->descripcion}}</td>
                                        <td>$ {{number_format($concepto->monto,2)}}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No hay conceptos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <script>
                function recalcularSubtotalIntegracionDesdeConceptos() {
                    let subtotal = 0;
                    document.querySelectorAll('#tabla-conceptos-costos tbody tr').forEach(tr => {
                        // Busca input en la celda de monto (4ta columna)
                        let monto = 0;
                        const montoInput = tr.querySelector('td:nth-child(4) input');
                        const montoCell = tr.querySelector('td:nth-child(4)');
                        if (montoInput) {
                            monto = parseFloat(montoInput.value) || 0;
                        } else if (montoCell) {
                            monto = parseFloat(montoCell.textContent.replace('$', '').replace(',', '').trim()) || 0;
                        }
                        subtotal += monto;
                    });
                    const iva = subtotal * 0.16;
                    const total = subtotal + iva;
                    if(document.getElementById('subtotal-integracion'))
                        document.getElementById('subtotal-integracion').textContent = '$ ' + subtotal.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    if(document.getElementById('iva-integracion'))
                        document.getElementById('iva-integracion').textContent = '$ ' + iva.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    if(document.getElementById('total-integracion'))
                        document.getElementById('total-integracion').textContent = '$ ' + total.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                // Llama la función al cargar la página
                document.addEventListener('DOMContentLoaded', function() {
                    if (document.getElementById('tabla-conceptos-costos')) {
                        recalcularSubtotalIntegracionDesdeConceptos();
                        // Listeners para inputs de porcentaje y enviar_a_partida si quieres recalcular al editar
                        document.querySelectorAll('#tabla-conceptos-costos input').forEach(input => {
                            input.addEventListener('input', recalcularSubtotalIntegracionDesdeConceptos);
                        });
                    }
                });
                </script>
                @endif

                {{--TABLA PRINCIPAL--}}
                <div class="row m-0">
                    @if(strtolower($tipo) == 'suministro')
                    <h5 class="mb-3">Previsualización de partidas</h5>
                    <table class="table table-hover table-striped">
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
                                <th class="table-secondary text-center">SELECCIONA</th>
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

                    @elseif(strtolower($tipo) == 'integracion' || strtolower($tipo) == 'integración')
                    <div class="mb-3">
                        <div class="mb-2">
                            <label for="inputMontoProyectado" class="form-label fw-bold" style="font-size: 20px !important; color: #0e0e0e; text-shadow: 2px 2px 8px #fff3cd;">
                                <i class="fa-solid fa-coins"></i> Monto Proyectado del Proyecto
                            </label>
                            <div class="input-group mb-3">
                                <span class="input-group-text bg-warning" style="font-size: 1.5rem; color: #e4980a;">
                                    <i class="fa-solid fa-coins"></i>
                                </span>
                                <input
                                    type="text"
                                    id="inputMontoProyectado"
                                    class="form-control text-center fw-bold"
                                    value="$ {{ number_format($monto_proyectado ?? 0, 2) }}"
                                    readonly
                                    style="
                                        background: #fffbe6;
                                        color: #d35400;
                                        font-size: 2rem;
                                        border: 2.5px solid #FFA602;
                                        box-shadow: 0 2px 8px rgba(255, 166, 2, 0.10);
                                        border-radius: 0 1rem 1rem 0;
                                        letter-spacing: 1px;
                                    "
                                >
                            </div>
                        </div>
                        <button class="btn btn-baseColor btn-sm mb-2" type="button" onclick="agregarFilaIntegracion()">
                            <i class="fa-solid fa-plus"></i> Agregar Concepto
                        </button>
                        <button class="btn btn-info btn-sm mb-2 ms-2" type="button" onclick="distribuirMontoProyectado()">
                            <i class="fa-solid fa-calculator"></i> Distribuir Monto
                        </button>
                    </div>
                    <table class="table table-hover table-striped">
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
                                <th class="table-secondary">PARTIDA</th>
                                <th class="table-secondary">CANTIDAD</th>
                                <th class="table-secondary" colspan="2">DESCRIPCIÓN</th>
                                <th class="table-secondary">UNIDAD</th>
                                <th class="table-secondary">PRECIO U.</th>
                                <th class="table-secondary">T.ENTREGA</th>
                                <th class="table-secondary">TOTAL</th>
                                <th class="table-secondary">ACCIONES</th>
                                <th class="table-secondary">PONDERACIÓN</th>
                            </tr>
                            <tr>
                                <th class="bg-orange" colspan="10"></th>
                            </tr>
                        </thead>
                        <tbody id="tabla-integracion-lista">
                            {{-- Las filas se agregarán dinámicamente con JS --}}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8" class="text-end">SUBTOTAL</td>
                                <td id="subtotal-integracion">$ {{ number_format($monto_proyectado ?? 0, 2) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="8" class="text-end">IVA <b class="text-secondary fs-8 fw-normal">(16 %)</b></td>
                                <td id="iva-integracion">$ {{ number_format(($monto_proyectado ?? 0) * 0.16, 2) }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="8" class="text-end table-warning fw-bold">TOTAL</td>
                                <td id="total-integracion" class="table-warning fw-bold">$ {{ number_format(($monto_proyectado ?? 0) * 1.16, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <script>
                        let partidaIntegracion = 1;
                        
                        function agregarFilaIntegracion() {
                            console.log('Agregando fila de integración...');
                            const tbody = document.getElementById('tabla-integracion-lista');
                            if (!tbody) {
                                console.error('No se encontró el tbody de integración');
                                return;
                            }
                            
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td class="fw-bold text-center">${partidaIntegracion}</td>
                                <td><input type="number" class="form-control form-control-sm" min="1" value="1" onchange="recalcularTotalIntegracion(this)" oninput="recalcularTotalIntegracion(this)" onblur="recalcularTotalIntegracion(this)" onfocus="this.select()"></td>
                                <td colspan="2"><input type="text" class="form-control form-control-sm" placeholder="Descripción del concepto"></td>
                                <td><input type="text" class="form-control form-control-sm" value="PZA"></td>
                                <td><input type="number" class="form-control form-control-sm" min="0" step="0.01" value="" placeholder="0.00" onchange="recalcularTotalIntegracion(this)" oninput="recalcularTotalIntegracion(this)" onblur="recalcularTotalIntegracion(this)" onfocus="this.select()"></td>
                                <td><input type="text" class="form-control form-control-sm" placeholder="Observación"></td>
                                <td class="text-truncate total-fila">$ 0.00</td>
                                <td><button class="btn btn-danger btn-sm" type="button" onclick="eliminarFilaIntegracion(this)"><i class="fa-solid fa-trash"></i></button></td>
                                <td><input type="number" class="form-control form-control-sm" min="0" step="0.01" value="0" placeholder="0.00" onchange="actualizarDistribucionPorPonderacion(this)" oninput="actualizarDistribucionPorPonderacion(this)"></td>
                            `;
                            tbody.appendChild(tr);
                            partidaIntegracion++;
                            console.log('Fila agregada, recalculando totales...');
                            
                            // Enfocar en el campo de precio para que el usuario pueda ingresar un valor inmediatamente
                            const precioInput = tr.querySelector('td:nth-child(6) input');
                            if (precioInput) {
                                setTimeout(() => {
                                    precioInput.focus();
                                }, 100);
                            }
                            
                            recalcularTotalesIntegracion();
                        }
                        
                        function eliminarFilaIntegracion(btn) {
                            console.log('Eliminando fila de integración...');
                            const tr = btn.closest('tr');
                            tr.remove();
                            recalcularTotalesIntegracion();
                        }
                        
                        function recalcularTotalIntegracion(input) {
                            console.log('=== RECALCULANDO TOTAL DE LÍNEA ===');
                            console.log('Input que disparó el evento:', input);
                            console.log('Valor del input:', input.value);
                            console.log('Tipo del input:', input.type);
                            
                            const tr = input.closest('tr');
                            console.log('Fila completa:', tr);
                            console.log('Todas las celdas de la fila:', tr.querySelectorAll('td'));
                            
                            const cantidadInput = tr.querySelector('td:nth-child(2) input');
                            
                            // Determinar cuál es el input de precio basado en la posición del input que disparó el evento
                            let precioInput = null;
                            const allInputs = tr.querySelectorAll('input[type="number"]');
                            console.log('Todos los inputs number en la fila:', allInputs);
                            
                            if (input.type === 'number') {
                                // Si el input que disparó el evento es de tipo number, determinar si es cantidad o precio
                                const inputIndex = Array.from(allInputs).indexOf(input);
                                console.log('Índice del input que disparó el evento:', inputIndex);
                                
                                if (inputIndex === 0) {
                                    // Es el input de cantidad, buscar el precio (segundo input number)
                                    precioInput = allInputs[1];
                                    console.log('Input disparado es cantidad, usando segundo input como precio');
                                } else if (inputIndex === 1) {
                                    // Es el input de precio, usarlo directamente
                                precioInput = input;
                                    console.log('Input disparado es precio, usándolo directamente');
                                }
                            }
                            
                            const totalCell = tr.querySelector('.total-fila');
                            
                            console.log('Elementos encontrados:');
                            console.log('- Cantidad input:', cantidadInput);
                            console.log('- Precio input:', precioInput);
                            console.log('- Total cell:', totalCell);
                            
                            if (!cantidadInput || !precioInput || !totalCell) {
                                console.error('No se encontraron los elementos necesarios para el cálculo');
                                return;
                            }
                            
                            const cantidad = parseFloat(cantidadInput.value) || 0;
                            const precio = parseFloat(precioInput.value) || 0;
                            
                            console.log('Valores extraídos:');
                            console.log('- Cantidad:', cantidad);
                            console.log('- Precio:', precio);
                            
                            // Solo calcular si el precio es mayor a 0
                            if (precio > 0) {
                                const total = cantidad * precio;
                                console.log(`✅ Cálculo válido: ${cantidad} × ${precio} = ${total}`);
                                totalCell.textContent = '$ ' + total.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            } else {
                                console.log(`❌ Precio inválido: ${precio} (debe ser mayor a 0)`);
                                totalCell.textContent = '$ 0.00';
                            }
                            
                            console.log('=== FIN RECÁLCULO ===');
                            recalcularTotalesIntegracion();
                        }
                        
                        // Función para distribuir el monto proyectado según ponderaciones
                        function distribuirMontoProyectado() {
                            console.log('=== DISTRIBUYENDO MONTO PROYECTADO ===');
                            
                            const inputMontoProyectado = document.getElementById('inputMontoProyectado');
                            const tbody = document.getElementById('tabla-integracion-lista');
                            
                            if (!inputMontoProyectado || !tbody) {
                                console.error('No se encontraron los elementos necesarios');
                                return;
                            }
                            
                            const montoProyectado = parseFloat(inputMontoProyectado.value.replace('$', '').replace(/,/g, '').trim()) || 0;
                            console.log('Monto proyectado:', montoProyectado);
                            
                            if (montoProyectado <= 0) {
                                console.log('Monto proyectado inválido');
                                return;
                            }
                            
                            const filas = tbody.querySelectorAll('tr');
                            console.log(`Procesando ${filas.length} filas`);
                            
                            let sumaPonderaciones = 0;
                            const ponderaciones = [];
                            
                            // Primera pasada: calcular suma total de ponderaciones
                            filas.forEach((tr, index) => {
                                console.log(`Analizando fila ${index + 1}:`, tr.innerHTML);
                                
                                // Buscar el input de ponderación de diferentes maneras
                                let ponderacionInput = tr.querySelector('td:nth-child(10) input');
                                if (!ponderacionInput) {
                                    // Intentar con el último input de la fila
                                    const inputs = tr.querySelectorAll('input[type="number"]');
                                    console.log(`Fila ${index + 1} - Inputs encontrados:`, inputs.length);
                                    if (inputs.length > 0) {
                                        ponderacionInput = inputs[inputs.length - 1]; // Último input number
                                        console.log(`Fila ${index + 1} - Usando último input:`, ponderacionInput);
                                    }
                                }
                                
                                if (ponderacionInput) {
                                    const ponderacion = parseFloat(ponderacionInput.value) || 0;
                                    ponderaciones.push(ponderacion);
                                    sumaPonderaciones += ponderacion;
                                    console.log(`Fila ${index + 1} - Ponderación: ${ponderacion}`);
                                } else {
                                    console.log(`Fila ${index + 1} - No se encontró input de ponderación`);
                                    ponderaciones.push(0);
                                }
                            });
                            
                            console.log('Suma total de ponderaciones:', sumaPonderaciones);
                            
                            if (sumaPonderaciones <= 0) {
                                console.log('No hay ponderaciones válidas');
                                return;
                            }
                            
                            // Segunda pasada: distribuir el monto según ponderaciones
                            filas.forEach((tr, index) => {
                                let ponderacionInput = tr.querySelector('td:nth-child(10) input');
                                if (!ponderacionInput) {
                                    const inputs = tr.querySelectorAll('input[type="number"]');
                                    if (inputs.length > 0) {
                                        ponderacionInput = inputs[inputs.length - 1];
                                    }
                                }
                                
                                const totalCell = tr.querySelector('.total-fila');
                                
                                // Buscar los inputs correctos según la estructura real de la tabla
                                const allInputs = tr.querySelectorAll('input');
                                console.log(`Fila ${index + 1} - Todos los inputs:`, allInputs.length);
                                
                                // Encontrar los inputs por su posición en la fila
                                let cantidadInput = null;
                                let precioInput = null;
                                let observacionInput = null;
                                
                                if (allInputs.length >= 4) {
                                    // Estructura esperada: cantidad, descripcion, unidad, precio, observacion, ponderacion
                                    cantidadInput = allInputs[0]; // Primer input (cantidad)
                                    precioInput = allInputs[3]; // Cuarto input (precio)
                                    observacionInput = allInputs[4]; // Quinto input (observacion)
                                }
                                
                                console.log(`Fila ${index + 1} - Inputs encontrados:`, {
                                    cantidad: !!cantidadInput,
                                    precio: !!precioInput,
                                    observacion: !!observacionInput,
                                    ponderacion: !!ponderacionInput
                                });
                                
                                if (ponderacionInput && totalCell && cantidadInput && precioInput) {
                                    const ponderacion = ponderaciones[index] || 0;
                                    const porcentaje = ponderacion / sumaPonderaciones;
                                    const montoDistribuido = montoProyectado * porcentaje;
                                    
                                    console.log(`Fila ${index + 1}:`);
                                    console.log(`- Ponderación: ${ponderacion}`);
                                    console.log(`- Porcentaje: ${(porcentaje * 100).toFixed(2)}%`);
                                    console.log(`- Monto distribuido: $${montoDistribuido.toFixed(2)}`);
                                    
                                    // Obtener la cantidad actual
                                    const cantidad = parseFloat(cantidadInput.value) || 1;
                                    
                                    // Calcular el precio unitario basado en el monto distribuido
                                    const precioUnitario = cantidad > 0 ? montoDistribuido / cantidad : 0;
                                    
                                    // Actualizar el precio unitario (NO la observación)
                                    precioInput.value = precioUnitario.toFixed(2);
                                    console.log(`- Cantidad: ${cantidad}`);
                                    console.log(`- Precio unitario calculado: $${precioUnitario.toFixed(2)}`);
                                    
                                    // Calcular y actualizar el total de la línea
                                    const totalLinea = cantidad * precioUnitario;
                                    totalCell.textContent = '$ ' + totalLinea.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    console.log(`- Total de línea: $${totalLinea.toFixed(2)}`);
                                }
                            });
                            
                            console.log('=== DISTRIBUCIÓN COMPLETADA ===');
                            recalcularTotalesIntegracion();
                        }
                        
                        // Función para actualizar distribución cuando cambia una ponderación
                        function actualizarDistribucionPorPonderacion(input) {
                            console.log('=== ACTUALIZANDO DISTRIBUCIÓN POR PONDERACIÓN ===');
                            console.log('Input de ponderación cambiado:', input.value);
                            
                            // Esperar un momento para que se actualice el valor
                            setTimeout(() => {
                                distribuirMontoProyectado();
                            }, 100);
                        }
                        
                        function recalcularTotalesIntegracion() {
                            console.log('Recalculando totales de integración...');
                            const tbody = document.getElementById('tabla-integracion-lista');
                            const subtotalElement = document.getElementById('subtotal-integracion');
                            const ivaElement = document.getElementById('iva-integracion');
                            const totalElement = document.getElementById('total-integracion');
                            
                            if (!tbody || !subtotalElement || !ivaElement || !totalElement) {
                                console.error('No se encontraron los elementos de totales');
                                return;
                            }
                            
                            let subtotal = 0;
                            const filas = tbody.querySelectorAll('tr');
                            console.log(`Procesando ${filas.length} filas...`);
                            
                            filas.forEach((tr, index) => {
                                const cantidadInput = tr.querySelector('td:nth-child(2) input');
                                // Usar la misma lógica que en recalcularTotalIntegracion
                                const allInputs = tr.querySelectorAll('input[type="number"]');
                                const precioInput = allInputs.length > 1 ? allInputs[1] : null; // El segundo input es el precio
                                
                                console.log(`Fila ${index + 1}:`);
                                console.log('- Cantidad input:', cantidadInput);
                                console.log('- Precio input:', precioInput);
                                console.log('- Todos los inputs number:', allInputs);
                                
                                if (cantidadInput && precioInput) {
                                    const cantidad = parseFloat(cantidadInput.value) || 0;
                                    const precio = parseFloat(precioInput.value) || 0;
                                    
                                    console.log(`- Valores: cantidad=${cantidad}, precio=${precio}`);
                                    
                                    // Solo considerar filas con precio válido mayor a 0
                                    if (precio > 0) {
                                        const totalLinea = cantidad * precio;
                                        subtotal += totalLinea;
                                        console.log(`✅ Fila ${index + 1}: ${cantidad} × ${precio} = ${totalLinea}`);
                                    } else {
                                        console.log(`❌ Fila ${index + 1}: ${cantidad} × ${precio} = 0 (precio inválido)`);
                                    }
                                } else {
                                    console.log(`❌ Fila ${index + 1}: No se encontraron los inputs necesarios`);
                                    console.log(`- Cantidad input encontrado:`, !!cantidadInput);
                                    console.log(`- Precio input encontrado:`, !!precioInput);
                                }
                            });
                            
                            const iva = subtotal * 0.16;
                            const total = subtotal + iva;
                            
                            console.log(`Subtotal: ${subtotal}, IVA: ${iva}, Total: ${total}`);
                            
                            subtotalElement.textContent = '$ ' + subtotal.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            ivaElement.textContent = '$ ' + iva.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            totalElement.textContent = '$ ' + total.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            
                            // Validar montos después de actualizar totales
                            validarMontoProyectado();
                        }
                        
                        // Función para recalcular desde el monto proyectado (si es necesario)
                        function recalcularTotalesIntegracionDesdeMontoProyectado() {
                            console.log('Recalculando desde monto proyectado...');
                            const input = document.getElementById('inputMontoProyectado');
                            let subtotal = 0;
                            if (input) {
                                subtotal = parseFloat(input.value.replace('$', '').replace(/,/g, '').trim()) || 0;
                            }
                            const iva = subtotal * 0.16;
                            const total = subtotal + iva;
                            
                            const subtotalElement = document.getElementById('subtotal-integracion');
                            const ivaElement = document.getElementById('iva-integracion');
                            const totalElement = document.getElementById('total-integracion');
                            
                            if (subtotalElement && ivaElement && totalElement) {
                                subtotalElement.textContent = '$ ' + subtotal.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                ivaElement.textContent = '$ ' + iva.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                totalElement.textContent = '$ ' + total.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                
                                // Validar montos después de actualizar totales
                                validarMontoProyectado();
                            }
                        }
                        
                        // Función para validar si el monto proyectado es igual al subtotal
                        function validarMontoProyectado() {
                            const btnGuardar = document.getElementById('btnGuardar');
                            if (!btnGuardar) return; // Solo para servicios de integración
                            
                            const inputMontoProyectado = document.getElementById('inputMontoProyectado');
                            const subtotalElement = document.getElementById('subtotal-integracion');
                            
                            if (!inputMontoProyectado || !subtotalElement) {
                                console.log('No se encontraron los elementos necesarios para la validación');
                                return;
                            }
                            
                            // Extraer valores numéricos
                            const montoProyectado = parseFloat(inputMontoProyectado.value.replace('$', '').replace(/,/g, '').trim()) || 0;
                            const subtotalActual = parseFloat(subtotalElement.textContent.replace('$', '').replace(/,/g, '').trim()) || 0;
                            
                            console.log('Validando montos:');
                            console.log('- Monto Proyectado:', montoProyectado);
                            console.log('- Subtotal Actual:', subtotalActual);
                            
                            // Comparar con tolerancia de 0.01 para evitar problemas de precisión decimal
                            const diferencia = Math.abs(montoProyectado - subtotalActual);
                            const sonIguales = diferencia < 0.01;
                            
                            console.log('- Diferencia:', diferencia);
                            console.log('- Son iguales:', sonIguales);
                            
                            if (sonIguales) {
                                btnGuardar.disabled = false;
                                btnGuardar.classList.remove('btn-secondary');
                                btnGuardar.classList.add('btn-baseColor');
                                console.log('✅ Botón habilitado - Monto Proyectado = Subtotal');
                            } else {
                                btnGuardar.disabled = true;
                                btnGuardar.classList.remove('btn-baseColor');
                                btnGuardar.classList.add('btn-secondary');
                                console.log('❌ Botón deshabilitado - Monto Proyectado ≠ Subtotal');
                            }
                        }
                        
                        // Inicializar cuando se carga la página
                        document.addEventListener('DOMContentLoaded', function() {
                            console.log('DOM cargado, inicializando tabla de integración...');
                            if (document.getElementById('tabla-integracion-lista')) {
                                console.log('Tabla de integración encontrada');
                                // Si no hay filas dinámicas, usar el monto proyectado
                                const tbody = document.getElementById('tabla-integracion-lista');
                                if (tbody.children.length === 0) {
                                    console.log('No hay filas dinámicas, usando monto proyectado');
                                    recalcularTotalesIntegracionDesdeMontoProyectado();
                                } else {
                                    console.log('Hay filas dinámicas, recalculando totales');
                                    recalcularTotalesIntegracion();
                                }
                                
                                // Validar montos después de calcular
                                setTimeout(validarMontoProyectado, 100);
                            } else {
                                console.log('Tabla de integración no encontrada');
                            }
                        });
                    </script>
                    @if(strtolower($tipo) == 'integracion' || strtolower($tipo) == 'integración')
                        @if(!empty($cotizacion_integracion))
                            <div class="alert alert-info mb-3">
                                <b>Ya existe una cotización guardada para este servicio. Puedes consultarla aquí:</b>
                            </div>
                            <table class="table table-hover table-striped mb-2">
                                <thead>
                                    <tr>
                                        <th>PARTIDA</th>
                                        <th>CANTIDAD</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>UNIDAD</th>
                                        <th>PRECIO U.</th>
                                        <th>T.ENTREGA</th>
                                        <th>TOTAL</th>
                                        <th>PONDERACIÓN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cotizacion_integracion as $item)
                                        <tr>
                                            <td>{{ $item['partida'] ?? '' }}</td>
                                            <td>{{ $item['cantidad'] ?? '' }}</td>
                                            <td>{{ $item['descripcion'] ?? '' }}</td>
                                            <td>{{ $item['unidad'] ?? '' }}</td>
                                            <td>$ {{ number_format($item['Precio u.'] ?? 0, 2) }}</td>
                                            <td>{{ $item['observacion'] ?? '' }}</td>
                                            <td>$ {{ number_format($item['total'] ?? 0, 2) }}</td>
                                            <td>{{ $item['ponderacion'] ?? '' }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mb-4">
                                <button class="btn btn-success fw-normal fs-8" onclick="reimprimirCotizacionGuardada()">
                                    <i class="fa-solid fa-print"></i> Imprimir cotización
                                </button>
                            </div>
                            <script>
                            function reimprimirCotizacionGuardada() {
                                // El JSON de la cotización guardada está disponible en Blade como PHP
                                const cotizacion = @json($cotizacion_integracion);
                                // Crear un formulario temporal para enviar los datos
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = '/Servicios/export_cot_pdf/{{$id}}/integracion';
                                form.target = '_blank'; // 👈 esto abre en nueva pestaña

                                // Agregar el token CSRF
                                const csrfToken = document.createElement('input');
                                csrfToken.type = 'hidden';
                                csrfToken.name = '_token';
                                csrfToken.value = '{{ csrf_token() }}';
                                form.appendChild(csrfToken);
                                // Agregar los datos de productos como JSON
                                const productosInput = document.createElement('input');
                                productosInput.type = 'hidden';
                                productosInput.name = 'productos_data';
                                productosInput.value = JSON.stringify(cotizacion);
                                form.appendChild(productosInput);
                                // Agregar el formulario al DOM y enviarlo
                                document.body.appendChild(form);
                                form.submit();
                                document.body.removeChild(form);
                            }
                            </script>
                        @endif
                    @endif
                    @else
                                  {{-- CONCEPTOS DE COSTOS PARA INTEGRACIÓN --}}
                @if(strtolower($tipo) == 'integracion' || strtolower($tipo) == 'integración')
                <div class="row m-0 mb-3">
                    <div class="mb-2 bg-body border p-3">
                        <h5 class="mb-3 text-orange"><i class="fa-solid fa-list me-2"></i>Conceptos de Costos</h5>
                        <table class="table table-striped table-hover fs-8">
                            <thead>
                                <tr class="text-center">
                                    <th class="table-secondary">PARTIDA</th>
                                    <th class="table-secondary">NOMBRE</th>
                                    <th class="table-secondary">DESCRIPCIÓN</th>
                                    <th class="table-secondary">MONTO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($i = 1)
                                @forelse($conceptos_servicio as $concepto)
                                    <tr>
                                        <td class="fw-bold text-center">{{$i++}}</td>
                                        <td>{{$concepto->nombre}}</td>
                                        <td>{{$concepto->descripcion}}</td>
                                        <td>$ {{number_format($concepto->monto,2)}}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No hay conceptos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                    <table class="table table-hover table-striped">
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
                                <th class="table-secondary">PARTIDA</th>
                                <th class="table-secondary">CANTIDAD</th>
                                <th class="table-secondary" colspan="2">DESCRIPCIÓN</th>
                                <th class="table-secondary">UNIDAD</th>
                                <th class="table-secondary">PRECIO U.</th>
                                <th class="table-secondary">T.ENTREGA</th>
                                <th class="table-secondary">TOTAL</th>
                            </tr>

                            <tr>
                                <th class="bg-orange" colspan="8"></th>
                            </tr>
                        </thead>

                        <tbody>
                            @php($i = 1)
                            @php($suma_total = 0)
                            @foreach($obtnerproductosxservicio as $item)
                            <tr>
                                <td class="fw-bold">{{$i++}}</td>
                                <td class="text-truncate">{{$item->cantidad_total}}</td>
                                <td class="text-" colspan="2">{{substr($item->nombre,0,20)}}...</td>
                                <td class="text-truncate">{{$item->unidad_medida}}</td>
                                <td class="text-truncate">$ {{number_format($item->precio_unitario,2)}}</td>
                                <td class="text-truncate">
                                    <form action="/Servicios/GuardarObservacion/{{$item->id}}" method="GET" class="needs-validation" id="form1" novalidate>
                                        @csrf
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" minlength="2" maxlength="200" placeholder="Recipient's Observation" name="observacion" value="{{$item->otrosconceptos1}}" required>
                                            <button class="btn btn-success rounded-end input-group-text fs-9" id="basic-addon2" type="submit"><i class="fa-solid fa-check"></i></button>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                    </form>
                                </td>
                                @php($total = $item->precio_unitario*$item->cantidad_total)
                                <td class="text-truncate"> $ {{number_format($total,2)}}</td>
                            </tr>
                            @php($suma_total = $suma_total + $total)
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <td colspan="6" class=""><br></td>
                                <td>SUBTOTAL</td>
                                <td>$ {{number_format($suma_total,2)}}</td>
                            </tr>
                            <tr>
                                <td colspan="6" class=""><br></td>
                                <td class="table-secondary">IVA <b class="text-secondary fs-8 fw-normal">(16 %)</b></td>
                                @php($iva = .16 * $suma_total)
                                <td>$ {{number_format($iva,2)}}</td>
                            </tr>
                            <tr>
                                <td colspan="6" class=""><br></td>
                                <td class="table-warning fw-bold">TOTAL</td>
                                <td>$ {{number_format($suma_total + $iva,2)}}</td>
                            </tr>
                        </tfoot>
                    </table>
                    @endif
                </div>

                {{-- TABLA DE CONCEPTOS DE INTEGRACIÓN 
                @if(strtolower($tipo) == 'integración' || strtolower($tipo) == 'integracion')
                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="mb-2 text-orange">Conceptos de Integración</h5>
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>PARTIDA</th>
                                    <th>CANTIDAD</th>
                                    <th>DESCRIPCIÓN</th>
                                    <th>UNIDAD</th>
                                    <th>PRECIO U.</th>
                                    <th>OBSERVACIÓN</th>
                                    <th>TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Fila editable solicitada 
                                <tr>
                                    <td>1</td>
                                    <td><input type="number" class="form-control" value="1" min="1" readonly></td>
                                    <td><textarea class="form-control" name="descripcion_general" rows="2" placeholder="Descripción general del servicio..."></textarea></td>
                                    <td><input type="text" class="form-control" value="SERVICIO" readonly></td>
                                    <td><input type="text" class="form-control" value="$ {{ number_format($servicio->monto_proyectado ?? 0, 2) }}" readonly></td>
                                    <td><input type="text" class="form-control" name="observacion_general" value=""></td>
                                    <td><input type="text" class="form-control" value="$ {{ number_format($servicio->monto_proyectado ?? 0, 2) }}" readonly></td>
                                </tr>
                                {{-- Fin fila editable 
                                @php($i = 1)
                                @php($suma_total = 0)
                                @if(isset($conceptos_servicio) && count($conceptos_servicio) > 0)
                                    @foreach($conceptos_servicio as $concepto)
                                    <tr>
                                        <td>{{$i++}}</td>
                                        <td>1</td>
                                        <td>{{ $concepto->nombre }}</td>
                                        <td>PZA</td>
                                        <td>$ {{ number_format($concepto->monto, 2) }}</td>
                                        <td>{{ $concepto->descripcion }}</td>
                                        <td>$ {{ number_format($concepto->monto, 2) }}</td>
                                    </tr>
                                    @php($suma_total += $concepto->monto)
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No hay conceptos registrados para este servicio.</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">TOTAL</td>
                                    <td class="fw-bold">$ {{ number_format($suma_total + ($servicio->monto_proyectado ?? 0), 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif
--}}
  

                {{-- PIE DE PAGINA --}}
                <div class="row p-4  rounded-2">
                    <div class="col-md-8" style="padding: 0px 50px 0px 10px;">
                        <div class="row p-2 border-4 border-start border-danger rounded-2 bg-body">
                            <div class="col-2 p-2">
                                <h1 class="text-danger m-3"><i class="fa-solid fa-triangle-exclamation"></i></h1>
                            </div>

                            <div class="col-10">
                                <textarea name="observaciones" id="observaciones" class="form-control" rows="3" placeholder="Observaciones" required>{{$servicio->otrosconceptos3}}</textarea>
                            </div>
                        </div>
                    </div>

                    <form action="/Servicios/GuardarCotizacion/{{$servicio->id}}" method="GET" class="needs-validation col-md-4" id="form1" novalidate>
                        @csrf
                        
                        <div class="form-outline col-md-12 p-2 pt-3 text-center">
                            <select name="reviso" id="reviso" class="select-form select2" required>
                                    <option value="">...</option>
                                    @foreach($EmpleadosActivos as $item)
                                        @if($reviso == $item->id && !is_null($reviso))
                                            <option value="{{$item->id}}" selected>{{$item->Nombre}}</option>
                                        @else
                                            <option value="{{$item->id}}">{{$item->Nombre}}</option>
                                        @endif
                                    @endforeach
                            </select>

                            <h5 class="mt-2">Revisó</h5>
                            <small class="text-wrap text-secondary">
                                (Seleccione el nombre de la persona responsable de 
                                revizar la información presentada actualmente.)
                            </small>

                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                        
                        <!-- Campo oculto para las observaciones -->
                        <input type="hidden" name="observaciones" id="observaciones_hidden">
                    </form>
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
                    {{-- <div class="col-md-3 p-2">
                        @if(strtolower($tipo) == 'suministro')
                        <button class="btn btn-success btn-mode fs-8 push" onclick="DescargarSuministro()" style="width: 100%"><i class="fa-solid fa-file-excel"></i> Descargar</button>
                        @elseif(strtolower($tipo) == 'integracion' || strtolower($tipo) == 'integración')
                        <button class="btn btn-success btn-mode fs-8 push" onclick="DescargarIntegracion()" style="width: 100%"><i class="fa-solid fa-file-excel"></i> Descargar</button>
                        @else
                        <a class="btn btn-success btn-mode fs-8 push" href="/Servicios/export_cot_pdf/{{$id}}/{{$tipo}}" style="width: 100%"><i class="fa-solid fa-file-excel"></i> Descargar</a>
                        @endif
                    </div> --}}
                </div>
            </div>
        </div>
        
    </div>
</div>


<script>
    function Guardar(){
         // Capturar el contenido del textarea de observaciones
         const textareaObservaciones = document.getElementById('observaciones');
         const campoOculto = document.getElementById('observaciones_hidden');
         
         if (textareaObservaciones && campoOculto) {
             campoOculto.value = textareaObservaciones.value;
         }
         
         let productosData = [];
         
         // Obtener datos según el tipo de servicio
         if ('{{strtolower($tipo)}}' === 'suministro') {
             // Obtener los datos de la tabla de productos para suministro
         const tablaProductos = document.querySelector('#tabla-productos-lista');
             if (tablaProductos) {
                 const filas = tablaProductos.querySelectorAll('tr');
                 filas.forEach((fila, index) => {
                     const celdas = fila.querySelectorAll('td');
                     if (celdas.length >= 4) {
                         const producto = {
                             id: celdas[0].textContent.trim(),
                             descripcion: celdas[1].textContent.trim(),
                             proveedor: celdas[2].textContent.trim(),
                             precio: parseFloat(celdas[3].textContent.replace('$', '').replace(',', '').trim()),
                             unidad: celdas[4] ? celdas[4].textContent.trim() : '', // UNIDAD
                            //  observacion: celdas[5] ? celdas[5].textContent.trim() : '', // OBSERVACIÓN
                             precio: celdas[5] ? parseFloat(celdas[5].textContent.replace('$', '').replace(',', '').trim()) : 0 // TOTAL
                         };
                         productosData.push(producto);
                     }
                 });
             }
         } else if ('{{strtolower($tipo)}}' === 'integracion' || '{{strtolower($tipo)}}' === 'integración') {
             // Obtener los datos de la tabla de integración
             const tablaIntegracion = document.querySelector('#tabla-integracion-lista');
             if (tablaIntegracion) {
                 const filas = tablaIntegracion.querySelectorAll('tr');
                 filas.forEach((fila, index) => {
                     const celdas = fila.querySelectorAll('td');
                     console.log(`Procesando fila ${index + 1}, celdas encontradas: ${celdas.length}`);
                     
                     if (celdas.length >= 8) { // La tabla de integración tiene 9 columnas (incluyendo acciones)
                         console.log(`Guardar - Estructura HTML de la fila ${index + 1}:`, fila.innerHTML);
                         
                         const cantidadInput = fila.querySelector('td:nth-child(2) input');
                         const descripcionInput = fila.querySelector('td:nth-child(3) input');
                         const unidadInput = fila.querySelector('td:nth-child(4) input'); // Corregido: después del colspan
                         const precioInput = fila.querySelector('td:nth-child(5) input'); // Corregido: después del colspan
                         const observacionInput = fila.querySelector('td:nth-child(6) input'); // Corregido: después del colspan
                         const totalCell = fila.querySelector('td:nth-child(7)'); // Corregido: después del colspan
                         
                         console.log(`Fila ${index + 1} - Inputs encontrados:`, {
                             cantidad: !!cantidadInput,
                             descripcion: !!descripcionInput,
                             unidad: !!unidadInput,
                             precio: !!precioInput,
                             observacion: !!observacionInput,
                             total: !!totalCell
                         });
                         
                         if (cantidadInput && descripcionInput && precioInput) {
                             const cantidad = parseFloat(cantidadInput.value) || 0;
                             const precio = parseFloat(precioInput.value) || 0;
                             const total = cantidad * precio;
                             
                             console.log(`Fila ${index + 1} - Cantidad: ${cantidad}, Precio: ${precio}, Total: ${total}`);
                             console.log(`Guardar - Valores de inputs:`, {
                                 cantidadInput: cantidadInput.value,
                                 precioInput: precioInput.value,
                                 descripcionInput: descripcionInput.value,
                                 observacionInput: observacionInput ? observacionInput.value : 'N/A'
                             });
                             console.log(`Guardar - Inputs encontrados:`, {
                                 cantidadInput: !!cantidadInput,
                                 precioInput: !!precioInput,
                                 descripcionInput: !!descripcionInput,
                                 observacionInput: !!observacionInput
                             });
                             
                             // Buscar el input de ponderación
                             const allInputs = fila.querySelectorAll('input');
                             let ponderacionInput = null;
                             if (allInputs.length >= 6) {
                                 ponderacionInput = allInputs[5]; // Sexto input (ponderación)
                             }
                             
                             const ponderacion = ponderacionInput ? parseFloat(ponderacionInput.value) || 0 : 0;
                             
                             const producto = {
                                 partida: celdas[0].textContent.trim(),
                                 cantidad: cantidad,
                                 descripcion: descripcionInput.value.trim(),
                                 unidad: unidadInput ? unidadInput.value.trim() : 'PZA',
                                 unidad_medida: "UNIDAD",
                                 "Precio u.": precio,
                                 observacion: observacionInput ? observacionInput.value.trim() : '',
                                 total: total,
                                 ponderacion: ponderacion
                             };
                             
                             console.log(`Guardar - Estructura JSON para fila ${index + 1}:`, producto);
                             productosData.push(producto);
                         } else {
                             console.log(`Guardar - Fila ${index + 1} - No se encontraron todos los inputs necesarios:`, {
                                 cantidadInput: !!cantidadInput,
                                 descripcionInput: !!descripcionInput,
                                 precioInput: !!precioInput
                             });
                         }
                     }
                 });
             }
         } else if ('{{strtolower($tipo)}}' === 'proyecto') {
             // Para proyecto, usar la misma lógica que suministro por ahora
             const tablaProductos = document.querySelector('#tabla-productos-lista');
         if (tablaProductos) {
             const filas = tablaProductos.querySelectorAll('tr');
             filas.forEach((fila, index) => {
                 const celdas = fila.querySelectorAll('td');
                 if (celdas.length >= 4) {
                     const producto = {
                         id: celdas[0].textContent.trim(),
                         descripcion: celdas[1].textContent.trim(),
                         proveedor: celdas[2].textContent.trim(),
                         precio: parseFloat(celdas[3].textContent.replace('$', '').replace(',', '').trim())
                     };
                     productosData.push(producto);
                 }
             });
         }
         }
         
         // Crear o actualizar el input oculto para el JSON
         let inputJson = document.getElementById('productos_data');
         if (!inputJson) {
             inputJson = document.createElement('input');
             inputJson.type = 'hidden';
             inputJson.name = 'productos_data';
             inputJson.id = 'productos_data';
             document.getElementById('form1').appendChild(inputJson);
         }
         inputJson.value = JSON.stringify(productosData);
         
         // Agregar el tipo de servicio al formulario
         let inputTipo = document.getElementById('tipo_servicio');
         if (!inputTipo) {
             inputTipo = document.createElement('input');
             inputTipo.type = 'hidden';
             inputTipo.name = 'tipo';
             inputTipo.id = 'tipo_servicio';
             document.getElementById('form1').appendChild(inputTipo);
         }
         inputTipo.value = '{{$tipo}}'; // Usar el tipo de servicio de la variable Blade
         
         console.log('Datos a enviar:', {
             tipo: '{{$tipo}}',
             productos: productosData,
             total_productos: productosData.length
         });
         console.log('JSON completo a enviar:', JSON.stringify(productosData, null, 2));
         
         const miFormulario = document.getElementById('form1');
         miFormulario.submit(); 
    }

    function DescargarSuministro() {
        // Obtener todos los datos de la tabla de productos
        const tablaProductos = document.querySelector('#tabla-productos-lista');
        const productosData = [];
        
        if (tablaProductos) {
            const filas = tablaProductos.querySelectorAll('tr');
            
            filas.forEach((fila, index) => {
                const celdas = fila.querySelectorAll('td');
                if (celdas.length >= 4) {
                    const producto = {
                        id: celdas[0].textContent.trim(),
                        descripcion: celdas[1].textContent.trim(),
                        proveedor: celdas[2].textContent.trim(),
                        precio: parseFloat(celdas[3].textContent.replace('$', '').replace(',', '').trim())
                    };
                    productosData.push(producto);
                }
            });
        }
        
        console.log('Datos de productos a enviar:', productosData);
        
        // Si no hay productos, mostrar alerta
        if (productosData.length === 0) {
            alert('Por favor, agrega al menos un producto para exportar.');
            return;
        }
        
        // Crear un formulario temporal para enviar los datos
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/Servicios/export_cot_pdf/{{$id}}/{{$tipo}}';
        
        // Agregar el token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Agregar los datos de productos como JSON
        const productosInput = document.createElement('input');
        productosInput.type = 'hidden';
        productosInput.name = 'productos_data';
        productosInput.value = JSON.stringify(productosData);
        form.appendChild(productosInput);
        
        // Agregar el formulario al DOM y enviarlo
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function DescargarIntegracion() {
        // Obtener todos los datos de la tabla de integración
        const tablaIntegracion = document.querySelector('#tabla-integracion-lista');
        const productosData = [];
        
        if (tablaIntegracion) {
            const filas = tablaIntegracion.querySelectorAll('tr');
            
            filas.forEach((fila, index) => {
                const celdas = fila.querySelectorAll('td');
                console.log(`Descargar - Procesando fila ${index + 1}, celdas encontradas: ${celdas.length}`);
                
                if (celdas.length >= 8) { // La tabla de integración tiene 9 columnas (incluyendo acciones)
                    console.log(`Descargar - Estructura HTML de la fila ${index + 1}:`, fila.innerHTML);
                    
                    const cantidadInput = fila.querySelector('td:nth-child(2) input');
                    const descripcionInput = fila.querySelector('td:nth-child(3) input');
                    const unidadInput = fila.querySelector('td:nth-child(4) input'); // Corregido: después del colspan
                    const precioInput = fila.querySelector('td:nth-child(5) input'); // Corregido: después del colspan
                    const observacionInput = fila.querySelector('td:nth-child(6) input'); // Corregido: después del colspan
                    const totalCell = fila.querySelector('td:nth-child(7)'); // Corregido: después del colspan
                    
                    console.log(`Descargar - Fila ${index + 1} - Inputs encontrados:`, {
                        cantidad: !!cantidadInput,
                        descripcion: !!descripcionInput,
                        unidad: !!unidadInput,
                        precio: !!precioInput,
                        observacion: !!observacionInput,
                        total: !!totalCell
                    });
                    
                    if (cantidadInput && descripcionInput && precioInput) {
                        const cantidad = parseFloat(cantidadInput.value) || 0;
                        const precio = parseFloat(precioInput.value) || 0;
                        const total = cantidad * precio;
                        
                        console.log(`Descargar - Fila ${index + 1} - Cantidad: ${cantidad}, Precio: ${precio}, Total: ${total}`);
                        console.log(`Descargar - Valores de inputs:`, {
                            cantidadInput: cantidadInput.value,
                            precioInput: precioInput.value,
                            descripcionInput: descripcionInput.value,
                            observacionInput: observacionInput ? observacionInput.value : 'N/A'
                        });
                        console.log(`Descargar - Inputs encontrados:`, {
                            cantidadInput: !!cantidadInput,
                            precioInput: !!precioInput,
                            descripcionInput: !!descripcionInput,
                            observacionInput: !!observacionInput
                        });
                        
                        // Buscar el input de ponderación
                        const allInputs = fila.querySelectorAll('input');
                        let ponderacionInput = null;
                        if (allInputs.length >= 6) {
                            ponderacionInput = allInputs[5]; // Sexto input (ponderación)
                        }
                        
                        const ponderacion = ponderacionInput ? parseFloat(ponderacionInput.value) || 0 : 0;
                        
                        const producto = {
                            partida: celdas[0].textContent.trim(),
                            cantidad: cantidad,
                            descripcion: descripcionInput.value.trim(),
                            unidad: unidadInput ? unidadInput.value.trim() : 'PZA',
                            unidad_medida: "UNIDAD",
                            "Precio u.": precio,
                            observacion: observacionInput ? observacionInput.value.trim() : '',
                            total: total,
                            ponderacion: ponderacion
                        };
                        
                        console.log(`Descargar - Estructura JSON para fila ${index + 1}:`, producto);
                        productosData.push(producto);
                    } else {
                        console.log(`Descargar - Fila ${index + 1} - No se encontraron todos los inputs necesarios:`, {
                            cantidadInput: !!cantidadInput,
                            descripcionInput: !!descripcionInput,
                            precioInput: !!precioInput
                        });
                    }
                }
            });
        }
        
        console.log('Datos de integración a enviar:', productosData);
        console.log('JSON completo a enviar:', JSON.stringify(productosData, null, 2));
        
        // Si no hay productos, mostrar alerta
        if (productosData.length === 0) {
            alert('Por favor, agrega al menos un concepto para exportar.');
            return;
        }
        
        // Crear un formulario temporal para enviar los datos
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/Servicios/export_cot_pdf/{{$id}}/{{$tipo}}';
        
        // Agregar el token CSRF
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        // Agregar los datos de productos como JSON
        const productosInput = document.createElement('input');
        productosInput.type = 'hidden';
        productosInput.name = 'productos_data';
        productosInput.value = JSON.stringify(productosData);
        form.appendChild(productosInput);
        
        // Agregar el formulario al DOM y enviarlo
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function ProbarCheckboxes() {
        // Obtener todos los checkboxes (marcados y no marcados)
        const todosCheckboxes = document.querySelectorAll('input[name="proveedor_seleccionado[]"]');
        const checkboxesMarcados = document.querySelectorAll('input[name="proveedor_seleccionado[]"]:checked');
        
        console.log('=== PRUEBA DE CHECKBOXES ===');
        console.log('Total checkboxes:', todosCheckboxes.length);
        console.log('Checkboxes marcados:', checkboxesMarcados.length);
        
        // Mostrar valores de los checkboxes marcados
        const valoresMarcados = Array.from(checkboxesMarcados).map(cb => cb.value);
        console.log('Valores de checkboxes marcados:', valoresMarcados);
        
        // Mostrar información de cada checkbox
        todosCheckboxes.forEach((cb, index) => {
            console.log(`Checkbox ${index + 1}:`, {
                checked: cb.checked,
                value: cb.value,
                name: cb.name
            });
        });
    }

    // Funciones para filtrado y búsqueda de productos
    function filtrarTabla() {
        const filtro = document.getElementById('filtroProductos').value.toLowerCase();
        const filas = document.querySelectorAll('tbody tr');
        
        filas.forEach(fila => {
            const descripcion = fila.querySelector('td:nth-child(4)').textContent.toLowerCase();
            if (filtro === '' || descripcion.includes(filtro)) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }

    function buscarProductos() {
        const busqueda = document.getElementById('buscadorProductos').value.toLowerCase();
        const filas = document.querySelectorAll('tbody tr');
        
        filas.forEach(fila => {
            const descripcion = fila.querySelector('td:nth-child(4)').textContent.toLowerCase();
            const proveedor = fila.querySelector('td:nth-child(6)').textContent.toLowerCase();
            const unidad = fila.querySelector('td:nth-child(5)').textContent.toLowerCase();
            
            if (descripcion.includes(busqueda) || proveedor.includes(busqueda) || unidad.includes(busqueda)) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }

    // ===== FUNCIONES PARA GESTIÓN DE PRODUCTOS =====
    
    // Array para almacenar los productos
    let productos = [];
    let productoSeleccionado = null;
    
    function mostrarModalProductos() {
        const modal = new bootstrap.Modal(document.getElementById('modalProductos'));
        modal.show();
    }
    
    function filtrarProductos() {
        const filtro = document.getElementById('buscar_producto').value.toLowerCase();
        const filas = document.querySelectorAll('.producto-row');
        
        filas.forEach(fila => {
            const descripcion = fila.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const proveedor = fila.querySelector('td:nth-child(3)').textContent.toLowerCase();
            if (descripcion.includes(filtro) || proveedor.includes(filtro)) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }
    
    function seleccionarProducto(id, descripcion, proveedor, precio) {
        productoSeleccionado = {
            id: id,
            descripcion: descripcion,
            proveedor: proveedor,
            precio: precio
        };
        
        document.getElementById('buscar_producto').value = descripcion;
        document.getElementById('precio_producto').value = precio;
        
        // Cerrar el modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalProductos'));
        modal.hide();
        
        // Enfocar en el campo de precio
        document.getElementById('precio_producto').focus();
    }
    
    function actualizarTablaProductos() {
        console.log('=== ACTUALIZANDO TABLA PRODUCTOS ===');
        console.log('Productos en array:', productos);
        
        const tbody = document.getElementById('tabla-productos-lista');
        // Buscar la tabla de suministro específicamente
        const tbodyServicio = encontrarTablaSuministro();
        
        console.log('tbody gestión:', tbody);
        console.log('tbody servicio:', tbodyServicio);
        
        if (!tbody || !tbodyServicio) {
            console.error('No se encontraron los elementos tbody necesarios');
            console.error('tbody gestión:', tbody);
            console.error('tbody servicio:', tbodyServicio);
            return;
        }
        
        // Limpiar tablas
        console.log('Limpiando tablas...');
        tbody.innerHTML = '';
        tbodyServicio.innerHTML = '';
        console.log('Tablas limpiadas. Filas en tbody servicio:', tbodyServicio.children.length);
        
        let contador = 1;
        let sumaTotal = 0;
        
        productos.forEach((producto, index) => {
            console.log(`Procesando producto ${index + 1}:`, producto);
            
            // Agregar a la tabla de gestión de productos
            const rowGestion = document.createElement('tr');
            rowGestion.innerHTML = `
                <td>${producto.id}</td>
                <td>${producto.descripcion}</td>
                <td>${producto.proveedor}</td>
                <td>${producto.cantidad ? producto.cantidad : 1}</td>
                <td>${producto.unidad ? producto.unidad : 'PZA'}</td>
                <td>$ ${producto.precio.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="eliminarProducto('${producto.id}')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(rowGestion);
            console.log('Fila agregada a tabla gestión');
            
            // Agregar a la tabla principal de SERVICIO
            const rowServicio = document.createElement('tr');
            rowServicio.innerHTML = `
                <td class="text-center">
                    <input type="checkbox" name="proveedor_seleccionado[]" value="${producto.id}" class="form-check-input">
                </td>
                <td class="fw-bold">${contador++}</td>
                <td class="text-truncate">1</td>
                <td class="text-truncate">
                    <span title="${producto.descripcion}">${producto.descripcion.length > 30 ? producto.descripcion.substring(0,30) + '...' : producto.descripcion}</span>
                </td>
                <td class="text-truncate">PZA</td>
                <td class="text-truncate">${producto.proveedor}</td>
                <td class="text-truncate">
                    <input type="text" class="form-control" placeholder="Observación" value="">
                </td>
                <td class="text-truncate">$ ${producto.precio.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            `;
            tbodyServicio.appendChild(rowServicio);
            console.log('Fila agregada a tabla servicio. Total filas:', tbodyServicio.children.length);
            
            sumaTotal += producto.precio;
        });
        
        console.log('Suma total:', sumaTotal);
        console.log('Filas agregadas a tabla servicio:', tbodyServicio.children.length);
        
        // Verificar que las filas estén realmente en el DOM
        setTimeout(() => {
            const filasVisibles = tbodyServicio.querySelectorAll('tr');
            console.log('Filas visibles en tbody servicio:', filasVisibles.length);
            console.log('Contenido del tbody servicio:', tbodyServicio.innerHTML);
        }, 100);
        
        // Actualizar totales en la tabla principal
        actualizarTotalesProductos(sumaTotal);
        
        console.log('=== TABLA ACTUALIZADA ===');
    }
    
    function encontrarTablaSuministro() {
        // Buscar todas las tablas
        const tablas = document.querySelectorAll('table');
        
        for (let tabla of tablas) {
            // Buscar la tabla que contenga "SERVICIO SUMINISTRO" en el encabezado
            const encabezado = tabla.querySelector('thead tr th');
            if (encabezado && encabezado.textContent.includes('SERVICIO SUMINISTRO')) {
                console.log('Tabla de suministro encontrada:', tabla);
                return tabla.querySelector('tbody');
            }
        }
        
        // Si no encuentra por encabezado, buscar por clases específicas
        const tablaSuministro = document.querySelector('table.table-hover.table-striped');
        if (tablaSuministro) {
            console.log('Tabla de suministro encontrada por clases:', tablaSuministro);
            return tablaSuministro.querySelector('tbody');
        }
        
        console.error('No se encontró la tabla de suministro');
        return null;
    }
    
    function agregarProducto() {
        console.log('=== AGREGANDO PRODUCTO ===');
        const precio = document.getElementById('precio_producto').value.trim();
        
        console.log('Producto seleccionado:', productoSeleccionado);
        console.log('Precio ingresado:', precio);
        
        // Validar que se haya seleccionado un producto
        if (!productoSeleccionado) {
            alert('Por favor, seleccione un producto primero');
            return;
        }
        
        // Validar que el precio esté lleno
        if (!precio) {
            alert('Por favor, complete el precio');
            return;
        }
        
        // Validar que el precio sea un número válido
        if (isNaN(precio) || precio <= 0) {
            alert('Por favor, ingrese un precio válido mayor a 0');
            return;
        }
        
        // Verificar si el producto ya existe
        const productoExistente = productos.find(p => p.id === productoSeleccionado.id);
        if (productoExistente) {
            alert('Este producto ya ha sido agregado');
            return;
        }
        
        // Agregar el producto al array local
        const nuevoProducto = {
            id: productoSeleccionado.id,
            descripcion: productoSeleccionado.descripcion,
            proveedor: productoSeleccionado.proveedor,
            precio: parseFloat(precio)
        };
        
        productos.push(nuevoProducto);
        console.log('Producto agregado al array:', nuevoProducto);
        console.log('Array completo:', productos);
        
        // Actualizar la tabla
        actualizarTablaProductos();
        
        // Limpiar los campos
        document.getElementById('buscar_producto').value = '';
        document.getElementById('precio_producto').value = '';
        productoSeleccionado = null;
        document.getElementById('buscar_producto').focus();
        
        // Mostrar mensaje de éxito
        alert('Producto agregado correctamente');
    }
    
    function eliminarProducto(id) {
        console.log('=== ELIMINANDO PRODUCTO ===');
        console.log('ID a eliminar:', id);
        console.log('Productos antes de eliminar:', productos);
        
        // Encontrar y eliminar el producto del array
        productos = productos.filter(p => p.id !== id);
        
        console.log('Productos después de eliminar:', productos);
        
        // Actualizar la tabla
        actualizarTablaProductos();
        
        // Mostrar mensaje de éxito
        alert('Producto eliminado correctamente');
    }
    
    function actualizarTotalesProductos(subtotal) {
        const iva = subtotal * 0.16;
        const total = subtotal + iva;
        
        document.getElementById('subtotal-productos').textContent = '$ ' + subtotal.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('iva-productos').textContent = '$ ' + iva.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('total-productos').textContent = '$ ' + total.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    
    // Función de inicialización
    function inicializarGestionProductos() {
        console.log('=== INICIALIZANDO GESTIÓN DE PRODUCTOS ===');
        console.log('Tipo de servicio:', '{{$tipo}}');
        console.log('Productos disponibles:', {{count($productos_suministro ?? [])}});
        
        // Verificar tablas disponibles
        verificarTablasDisponibles();
        
        // Inicializar arrays
        productos = [];
        productoSeleccionado = null;
        
        // Actualizar tablas
        actualizarTablaProductos();
        
        console.log('=== INICIALIZACIÓN COMPLETADA ===');
    }
    
    function verificarTablasDisponibles() {
        console.log('=== VERIFICANDO TABLAS DISPONIBLES ===');
        
        const todasLasTablas = document.querySelectorAll('table');
        console.log('Total de tablas encontradas:', todasLasTablas.length);
        
        todasLasTablas.forEach((tabla, index) => {
            console.log(`Tabla ${index + 1}:`, {
                clases: tabla.className,
                id: tabla.id,
                tbody: tabla.querySelector('tbody'),
                filas: tabla.querySelectorAll('tr').length
            });
        });
        
        // Buscar específicamente la tabla de SERVICIO
        const tablaServicio = document.querySelector('table.table-hover');
        if (tablaServicio) {
            console.log('Tabla SERVICIO encontrada:', tablaServicio);
            console.log('Tbody de tabla SERVICIO:', tablaServicio.querySelector('tbody'));
        } else {
            console.error('No se encontró la tabla SERVICIO');
        }
    }
    
    // Inicializar cuando se carga la página
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== PÁGINA CARGADA ===');
        inicializarGestionProductos();
    });
</script>


<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>



@endsection
                                
