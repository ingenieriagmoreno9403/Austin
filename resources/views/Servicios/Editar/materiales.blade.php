@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('warningProducto'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡No se agrego el producto correctamente!", text: "Vuelva a intentarlo, si el problema persiste contacte a un superior."});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('errorcero'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡Vuelva a intentarlo!", text: "La cantidad del producto ingresado debe ser mayor a 0."});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('error_pasar'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡Vuelva a intentarlo!", text: "No es posible pasar de página, intente de nuevo o más tarde."});';
            echo '</script>'; 
    @endphp
@endif

<div class="container-fluid format_page">
    <!-- Encabezado -->
      @csrf
        <div class="row">
            <div class="col-lg-6 col-12 start-center">
                <h2 class="mt-1 animate_animated animate_backInLeft"> 
                    Creación de {{$tipo}}
                </h2>

                 <a href="/Servicios" class="btn btn-primary fs-8"><i class="fa-solid fa-house"></i> Inicio</a> 
            </div>

            <div class="col-lg-6 col-12 d-none d-md-block start-center">
                @foreach($servicio_encxid as $key)
                    <div class="row">
                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" type="text" value="{{$key->nombre}}" placeholder="nombre" maxlength="20" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>

                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Folio</label>
                            <input class="form-control" type="text" value="{{$key->folio}}" placeholder="83901" maxlength="10" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    
                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Fecha Inicio</label>
                            <input class="form-control" type="date" value="{{$key->fecha_inicio}}" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>

                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Fecha Limite</label>
                            <input class="form-control" type="date" value="{{$key->fecha_limite}}" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="row">
            {{-- MENU --}}
            <div class="col-md-3 p-3 start-center">
                

                <a href="/Servicios/Editar/Datos/{{$tipo}}/{{$id}}" class="row pointer">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleBlue"> 1</div>
                    </div>

                    <div class="col-md-10 col-6 p-3 pb-0 d-none d-md-block">
                        <h6 class="text-dark">Datos Informativos</h6>
                    </div>
                </a>

                <div class="lineBlue d-none d-md-block"></div>

                <a href="/Servicios/Editar/Materiales/{{$tipo}}/{{$id}}" class="row pointer">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleBlue"> 2</div>
                    </div>

                    <div class="col-md-10 col-12 p-3 pt-1 pb-0">
                        <h6 class="m-0 p-0 text-dark">Materiales y Suministro</h6>
                        <span class="fs-8 text-secondary m-0 p-0">Plantilla de Cotizaciones</span>
                    </div>
                </a>

                @if($tipo == "Integración")
                <div class="lineGrey d-none d-md-block"></div>

                <a href="/Servicios/Editar/Costos/{{$tipo}}/{{$id}}" class="row pointer">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleGrey"> 3</div>
                    </div>

                    <div class="col-md-10 col-10 d-none d-md-block  p-3 pb-0">
                        <h6 class="text-dark">Costos Calculados</h6>
                    </div>
                </a>
                @endif
            </div>

            {{-- CUERPO --}}
            <div class="col-md-9">
                {{-- AÑADIR PRODUCTOS --}}
                <div class="row p-3">
                    <div class="border bg-light p-3 rounded-1">
                        <form action="/Servicios/Captura/AgregarProducto/{{$tipo}}/{{$id}}" method="GET" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
                            @csrf
                            <div class="row">
                                <div class="col-md-4 col-12 form-outline">
                                    <label class="form-label">Productos</label>
                                    <select class="select-form select2" name="producto" id="producto" required>
                                        <option value="">Buscar ...</option>
                                        @foreach($Listadoproductos as $key)
                                            <option value="{{$key->idproducto}}">({{$key->sku}}) {{$key->nombre}} </option>
                                        @endforeach
                                    </select>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>

                                <div class="col-md-4 col-12 form-outline">
                                    <label class="form-label">Cantidad</label>
                                    <input class="form-control border" type="number" name="cantidad" id="cantidad" style="margin-top: -4px" placeholder="0" maxlength="10" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            
                                <div class="col-md-4 col-6 mt-4 pt-2">
                                    <button class="btn btn-baseColor fs-8" type="submit"><i class="fa-solid fa-plus"></i> Agregar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                
           
                <div class="row p-3">
                    <div class="row p-3 pt-0 pb-0 justify-content-start">
                        <a href="/Servicios/Ver/Inicio/{{$id}}/{{$tipo}}" class="col-md-3 m-1 btn btn-success fs-8"><i class="fa-solid fa-file-excel"></i>&nbsp; Plantilla de Cotizaciones</a>
                    </div>

                    <table class="table table-stripped table-hover display" id="table">
                        <thead>
                            <tr>
                                <th></th>
                                <th class="text-truncate">Eliminar</th>
                                <th class="text-truncate">SKU</th>
                                <th class="text-truncate">Disponibilidad</th>
                                <th class="text-truncate">Nombre</th>
                                <th class="text-truncate">U. Medida</th>
                                <th class="text-truncate">Cantidad</th>
                                <th class="text-truncate">Disponible</th>
                                <th class="text-truncate">Faltante</th>
                                <th class="text-truncate">Costo U.</th>
                                <th class="text-truncate">Total</th>
                                <th class="text-truncate">Descripción</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($obtnerproductosxservicio as $item)
                            @if($item->cantidad_disponible > 0)
                                @php($color1 = "table-success text-success")
                            @else
                                @php($color1 = "table-light")
                            @endif
                            
                            @if($item->cantidad_faltante > 0)
                                @php($color2 = "table-danger text-danger")
                            @else
                                @php($color2 = "table-light")
                            @endif
                            
                            <tr>
                                <td></td>
                                <td>
                                    <a class="btn btn-danger" href="/Servicios/Captura/EliminarProducto/{{$tipo}}/{{$id}}/{{$item->id}}/{{$item->id_producto}}/{{$item->cantidad_disponible}}"> 
                                        <i class="fa-solid fa-trash text-light"></i> 
                                    </a>
                                </td>

                                <td class="text-truncate">{{$item->sku}}</td>
                                <td>
                                    @if($item->estado == "Sin Stock")
                                        <span class="badge badge-danger fs-9"> {{$item->estado}} </span>
                                    @elseif($item->estado == "Con Stock")
                                        <span class="badge badge-success fs-9"> {{$item->estado}} </span>
                                    @else
                                        <span class="badge badge-orange fs-9"> {{$item->estado}} </span>
                                    @endif
                                </td>
                                <td>
                                     @if (strlen($item->nombre) > 30)
                                        <div class="divtooltip">
                                            {{ substr($item->nombre,0,30) }} ... <i class="text-secondary fa-solid fa-info-circle"></i>
                                            <span class="tooltiptext">{{ $item->nombre }}</span>
                                        </div>
                                    @else
                                        {{ $item->nombre }}
                                    @endif
                                </td>
                               
                                <td class="text-truncate">{{$item->unidad_medida}}</td>

                                <td class="table-light text-truncate fw-bold">
                                    {{$item->cantidad_total}}
                                </td>

                                <td class="{{$color1}}  text-truncate fw-bold">
                                    {{$item->cantidad_disponible}}
                                </td>

                                <td class="{{$color2}} text-truncate fw-bold">
                                    {{$item->cantidad_faltante}}
                                </td>

                                

                                <td class="text-truncate">
                                    $ {{number_format($item->precio_unitario,2)}}
                                </td>

                                <td class="table-light fw-bold text-truncate">
                                    $ {{number_format($item->precio_unitario*$item->cantidad_total,2)}}
                                </td>

                                 <td class="text-truncate">
                                    {{ $item->descripcion }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach($servicio_encxid as $key)
                <div class="row justify-content-end p-3 form">
                    <div class="col-md-10 col-8">
                        <div class="form-check">
                            @if(!is_null($key->solicita_licitacion))
                                <input class="form-check-input" name="licitaciones" type="checkbox" value="si" id="defaultCheck1" disabled checked>
                            @else
                                <input class="form-check-input" name="licitaciones" type="checkbox" value="si" id="defaultCheck1">
                            @endif

                            <label class="form-check-label" for="defaultCheck1">
                                Enviar a Comparativa
                            </label>
                        </div>
                    </div>

                      
                    @if(!is_null($key->solicita_licitacion))
                       {{-- para que me valida si hay nuevos productos que eniviar a licitaciones --}}
                        <a class="col-md-2 col-4 btn btn-baseColor fs-7" href="/Servicios/Editar/InsertarMateriales/{{$tipo}}/{{$id}}"> 
                            <i class="fa-solid fa-check"></i>&nbsp; Guardar
                        </a>
                    @else
                        @if($tipo == "Suministro")
                            <a class="col-md-2 col-4 btn btn-baseColor fs-7" href="/Servicios"> 
                                <i class="fa-solid fa-circle-arrow-right"></i>&nbsp; Salir 
                            </a>
                        @else
                            <a class="col-md-2 col-4 btn btn-baseColor fs-7" href="/Servicios/Editar/Costos/{{$tipo}}/{{$id}}"> 
                                <i class="fa-solid fa-circle-arrow-right"></i>&nbsp; Continuar  
                            </a>
                        @endif
                    @endif
                     
                </div>
                @endforeach
            </div>
        </div>
</div>



<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
@endsection
