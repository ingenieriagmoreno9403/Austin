@extends('layouts.app')
@section('content')
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
@elseif($mensaje = Session::get('errorduplicidad'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡No se efectuo la acción!", text: "Intento de duplicidad de productos"});';
            echo '</script>';
    @endphp
@elseif($mensaje = Session::get('errorexistencia'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡No se efectuo la acción!", text: "La cantidad a transferir es mayor a la existente"});';
            echo '</script>';
    @endphp
@elseif($mensaje = Session::get('succeshistmov'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "success",title: "¡Movimiento generado con exito!", text: "Se transfirio correctamente"});';
            echo '</script>';
    @endphp
@elseif($mensaje = Session::get('errorhistmov'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡No se efectuo la acción!", text: "Ocurrio algun error al generar el histial del movimiento favor revisarlo en tabla de log"});';
            echo '</script>';
    @endphp
@elseif($mensaje = Session::get('erroralreg'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡No se efectuo la acción!", text: "Ocurrio un error al registrar la tranferencia"});';
            echo '</script>';
    @endphp
@elseif($mensaje = Session::get('errorallog'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡No se efectuo la acción!", text: "error al registrar el log"});';
            echo '</script>';
    @endphp
@endif

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/ubicaciondet/{{$idalm}}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Ubicaciones
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Productos de Ubicación</h2>
                        <p class="text-muted mb-0">{{ ucwords(strtolower($nombre_ubicacion)) }} — inventario por ubicación</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if($permisos1 == "ingresa_productosxubi")
                        <button class="btn btn-baseColor" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#offcanvasmanualnuevo" aria-controls="offcanvasRight">
                            <i class="fa-solid fa-plus"></i> Ingresar Producto
                        </button>
                    @else
                        <button class="btn btn-baseColor" type="button" disabled>
                            <i class="fa-solid fa-plus"></i> Ingresar Producto
                        </button>
                    @endif

                    <button class="btn btn-baseColor-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                        <i class="fa-solid fa-quote-left"></i> Simbología
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-12 text-end">
            <div id="collapseExample" class="collapse fs-9 mt-2 text-end p-3 pt-1 pb-0">
                <p class="text-secondary text-truncate fs-8">
                     Recepcionar <i class="fa-solid fa-dolly text-success fs-7"></i><br>
                     Transferir <i class="fa-solid fa-repeat text-orange fs-7"></i><br>
                     Ingresar <i class="fa-solid fa-right-to-bracket text-primary fs-7"></i><br>
                </p>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                    <tr>
                        <th class="text-center fw-bold text-truncate">Opciones</th>
                        <th class="text-center fw-bold text-truncate">Imagen</th>
                        <th class="text-center fw-bold text-truncate">#</th>
                        <th class="text-center fw-bold text-truncate">Nombre Producto</th>
                        <th class="text-center fw-bold text-truncate">Estado</th>
                        <th class="text-center fw-bold text-truncate">Nombre Categoría</th>
                        <th class="text-center fw-bold text-truncate">Unidad de Medida</th>
                        <th class="text-center fw-bold text-truncate">Cantidad Existente</th>
                        <th class="text-center fw-bold text-truncate">Productos a recepcionar</th>
                        <th class="text-center fw-bold text-truncate">Cantidad Reservada</th>
                        <th class="text-center fw-bold text-truncate">SKU</th>
                        <th class="text-center fw-bold text-truncate">Código de Barras</th>
                        <th class="text-center fw-bold text-truncate">Proveedor</th>
                        <th class="text-center fw-bold text-truncate">Precio Unitario</th>
                        <th class="text-center fw-bold text-truncate">Costo Compra</th>
                        <th class="text-center fw-bold text-truncate">Costo Publico</th>
                        <th class="text-center fw-bold text-truncate">Existencia Minima</th>
                        <th class="text-center fw-bold text-truncate">Existencia Maxima</th>
                        <th class="text-center fw-bold text-truncate">Descripción</th>
                        <th class="text-center fw-bold text-truncate">Fecha Ultima Entrada</th>
                        <th class="text-center fw-bold text-truncate">Fecha Ultima Salida</th>
                    </tr>
                </thead>
                <tbody>
                    @php($table = "table-light")
                    @foreach($Listadoproductosxubicacion as $pro)
                    @if($pro->nombre_estado == 'Enviado')
                        @php($table = "table-primary")
                    @else
                        @php($table = "table-light")
                    @endif
                        <tr>
                            <td class="{{$table}}">
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    @if($pro->nombre_estado == 'Enviado')
                                        @if($permisos2 == "recep_productosxubi")
                                            <button type="button" class="btn btn-success m-0" data-bs-toggle="modal" data-bs-target="#modalRecibir{{$pro->id_producto}}" title="Recepcionar">
                                                <i class="fa-solid fa-dolly fs-8"></i>
                                            </button>
                                        @else
                                            <button disabled class="btn btn-success m-0" title="Recepcionar">
                                                <i class="fa-solid fa-dolly fs-8"></i>
                                            </button>
                                        @endif
                                    @else
                                        <button disabled class="btn btn-success m-0" title="Recepcionar">
                                            <i class="fa-solid fa-dolly fs-8"></i>
                                        </button>
                                    @endif

                                    @if($pro->cantidad_existente > 0 ||  $pro->nombre_estado == 'Recibido')
                                        @if($permisos3 == "trans_productosxubi")
                                            <button class="btn btn-baseColor border-0 m-0" type="button" data-bs-toggle="modal" data-bs-target="#modalTransefrir{{$pro->id_producto}}" title="Transferir">
                                                <i class="fa-solid fa-repeat fs-8"></i>
                                            </button>
                                        @else
                                            <button class="btn btn-baseColor border-0 m-0" type="button" disabled title="Transferir">
                                                <i class="fa-solid fa-repeat fs-8"></i>
                                            </button>
                                        @endif
                                    @else
                                        <button class="btn btn-baseColor border-0 m-0" type="button" disabled title="Transferir">
                                            <i class="fa-solid fa-repeat fs-8"></i>
                                        </button>
                                    @endif

                                    @if($permisos1 == "ingresa_productosxubi")
                                        <button class="btn btn-primary border-0 m-0" type="button" data-bs-toggle="modal" data-bs-target="#modalngreso{{$pro->id_producto}}" title="Ingresar">
                                            <i class="fa-solid fa-right-to-bracket fs-8"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-primary border-0 m-0" type="button" disabled title="Ingresar">
                                            <i class="fa-solid fa-right-to-bracket fs-8"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <td class="{{$table}}">
                                @if($pro->ruta_img1 == '')
                                    <img src="{{ asset('Images/Productos/producto.jpg') }}" class="rounded-3 shadow" style="height: 64px;width: 64px;object-fit: cover;" alt="producto">
                                @else
                                    <img src="{{ asset('Images/Productos/'.$pro->sku.'/'.$pro->ruta_img1) }}" class="rounded-3 shadow" style="height: 64px;width: 64px;object-fit: cover;" alt="producto">
                                @endif
                            </td>

                            <td class="{{$table}}">{{$pro->id_producto}}</td>
                            <td class="text-start text-truncate {{$table}}">{{$pro->nombrepro}}</td>

                            <td class="{{$table}}">
                                @if($pro->nombre_estado == "Enviado")
                                    <span class="badge badge-primary fw-normal fs-9 position-relative">
                                        {{$pro->nombre_estado}}
                                        <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
                                            <span class="visually-hidden">New alerts</span>
                                        </span>
                                    </span>
                                @elseif($pro->nombre_estado == "Recibido")
                                    <span class="badge badge-orange fw-normal fs-9">{{$pro->nombre_estado}}</span>
                                @endif
                            </td>

                            <td class="{{$table}}">
                                <span class="badge badge-secondary fw-normal fs-9">{{$pro->subcategoria}}</span>
                            </td>
                            <td class="{{$table}}">{{$pro->unidad}}</td>
                            <td class="{{$table}}">
                                @if($pro->cantidad_existente < 0)
                                @else
                                    {{intval($pro->cantidad_existente)}}
                                @endif
                            </td>
                            <td class="{{$table}}">
                                @if($pro->nombre_estado == 'Enviado' and $pro->productos_arecibir > 0)
                                    {{intval($pro->productos_arecibir)}}
                                @else
                                    0
                                @endif
                            </td>
                            <td class="{{$table}}">{{intval($pro->cantidad_reservada)}}</td>
                            <td class="text-truncate {{$table}}">{{$pro->sku}}</td>
                            <td class="text-truncate {{$table}}">{{$pro->codigo_barras}}</td>
                            <td class="text-truncate {{$table}}">
                                @if($pro->provedor == '')
                                    No asignado
                                @else
                                    {{$pro->provedor}}
                                @endif
                            </td>
                            <td class="{{$table}}">$ {{number_format($pro->precio_unitario, 2)}}</td>
                            <td class="{{$table}}">$ {{number_format($pro->costo_compra, 2)}}</td>
                            <td class="{{$table}}">$ {{number_format($pro->costo_venta, 2)}}</td>
                            <td class="{{$table}}">{{$pro->minima_existencia}}</td>
                            <td class="{{$table}}">{{$pro->maxima_existencia}}</td>
                            <td class="text-truncate {{$table}}">
                                <button type="button" class="btn border-0 text-truncate m-0 fs-8 p-0" style="max-width: 150px;" data-bs-toggle="modal" data-bs-target="#Modalcomentario{{$pro->id_producto}}">
                                    <i class="fa-solid fa-circle-info text-orange me-1"></i>{{$pro->descripcion}}
                                </button>
                            </td>
                            <td class="{{$table}}">{{date("d/m/Y", strtotime($pro->fecha_ultima_entrada))}}</td>
                            <td class="{{$table}}">{{date("d/m/Y", strtotime($pro->fecha_ultima_salida))}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @foreach($Listadoproductosxubicacion as $pro)
        <div class="modal fade" id="modalRecibir{{$pro->id_producto}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-body">
                        <div class="text-center mt-3 mb-3">
                            <h5 class="text-marino">Existe producto pendiente a recepcionar.<br>¿Desea darle entrada?</h5>
                        </div>
                        <div class="row justify-content-center p-3">
                            <a href="/mrecepciontranferencia/{{$pro->idex}}/{{$pro->cantidad_existente}}/{{$pro->productos_arecibir}}" class="col-5 btn btn-baseColor fs-8">
                                <i class="fa-solid fa-check"></i> Aceptar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalTransefrir{{$pro->id_producto}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-dark">Transferencia de productos</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form action="/transferenciaproductos" method="GET" enctype="multipart/form-data" class="modern-form needs-validation mt-1 text-start" novalidate>
                        @csrf
                        <div class="modal-body p-4 pt-0">
                            <div class="row mb-3">
                                <div class="col-md-3 col-12">
                                    <div class="fs-8 text-dark bg-light border rounded-4 p-3">
                                        <p>
                                            <b class="text-orange">Existencia actual</b><br>
                                            {{$pro->cantidad_existente}} {{$pro->unidad}}
                                        </p>
                                        <p>
                                            <b class="text-orange">Almacén</b><br>
                                            {{$pro->nombreal}}
                                        </p>
                                        <p class="mb-0">
                                            <b class="text-orange">Ubicación</b><br>
                                            {{$pro->ubi}}
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-9 col-12">
                                    <input type="text" value="{{$pro->idex}}" hidden name="idex">
                                    <input type="text" value="{{$pro->alma}}" hidden name="idalmaactual">
                                    <input type="text" value="{{$pro->idubi}}" hidden name="idubiactual">
                                    <input type="text" value="{{$pro->cantidad_existente}}" hidden name="cantidadexistente">

                                    <div class="row">
                                        <div class="col-md-8 col-12 mt-2">
                                            <label class="form-label">Nombre de producto</label>
                                            <input type="text" class="form-control text" name="id_producto" hidden maxlength="18" value="{{$pro->id_producto}}"/>
                                            <input type="text" class="form-control text" name="nombrep" disabled maxlength="18" value="{{$pro->nombre}}"/>
                                        </div>
                                        <div class="col-md-4 col-12 mt-2">
                                            <label class="form-label">Cantidad a transferir</label>
                                            <input type="text" class="form-control text" name="cantidad" maxlength="18" value="{{$pro->cantidad_existente}}"/>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                    </div>

                                    <livewire:ubicacionesxalmacen/>
                                </div>
                            </div>

                            <div class="row justify-content-end">
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-baseColor fs-8">
                                        <i class="fa-solid fa-check"></i> Transferir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalngreso{{$pro->id_producto}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-dark">Ingreso de productos manual</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form action="/entradamanualexistente/{{$pro->idubi}}/{{$pro->id_producto}}" method="post" enctype="multipart/form-data" class="modern-form needs-validation mt-1 text-start" novalidate>
                        @csrf
                        <div class="modal-body p-4 pt-0">
                            <div class="row mb-2">
                                <div class="col-md-3 col-12">
                                    <div class="fs-8 text-dark bg-light border rounded-4 p-3">
                                        <p>
                                            <b class="text-orange">Existencia actual</b><br>
                                            {{$pro->cantidad_existente}} {{$pro->unidad}}
                                        </p>
                                        <p>
                                            <b class="text-orange">Almacén</b><br>
                                            {{$pro->nombreal}}
                                        </p>
                                        <p class="mb-0">
                                            <b class="text-orange">Ubicación</b><br>
                                            {{$pro->ubi}}
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-9 col-12">
                                    <input type="text" value="{{$pro->idex}}" hidden name="idex">
                                    <input type="text" value="{{$pro->idubi}}" hidden name="idubiactual">

                                    <div class="row">
                                        <div class="col-md-8 col-12 mt-2">
                                            <label class="form-label">Nombre de producto</label>
                                            <input type="text" class="form-control text" name="id_producto" hidden maxlength="18" value="{{$pro->id_producto}}"/>
                                            <input type="text" class="form-control text" name="nombrep" disabled maxlength="18" value="{{$pro->nombre}}"/>
                                        </div>
                                        <div class="col-md-4 col-12 mt-2">
                                            <label class="form-label">Cantidad a ingresar <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control text" name="cantidad" maxlength="10" required/>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                    </div>

                                    <div class="row mt-3 justify-content-center">
                                        <label for="file1" class="drop-container2">
                                            <span class="drop-title2">Subir documento de entrada</span>
                                            <small class="fs-8 text-secondary">Solo se admite formato ".pdf"</small>
                                            <input class="validaPDF" type="file" name="entrada{{$pro->id_producto}}" required>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row justify-content-end">
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-baseColor fs-8">
                                        <i class="fa-solid fa-check"></i> Ingresar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="Modalnombre{{$pro->id_producto}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-body">
                        <h6 class="text-marino mb-3">Nombre completo</h6>
                        <p class="text-dark text-wrap fw-normal fs-8 mb-0" style="text-align: justify!important">
                            {{$pro->nombrepro}}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="Modalcomentario{{$pro->id_producto}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-dark">Descripción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="text-dark text-wrap fw-normal fs-8 mb-0" style="text-align: justify!important">
                            {{$pro->descripcion}}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasmanualnuevo" aria-labelledby="offcanvasRightLabel" style="width:65vh!important">
        <div class="offcanvas-header border-0">
            <h5 class="text-marino" id="offcanvasRightLabel">Ingreso de productos manual</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body">
            <form action="/entradamanualnuevopro" method="post" enctype="multipart/form-data" class="modern-form needs-validation text-start" novalidate>
                @csrf
                <input type="text" value="{{$idalm}}" hidden name="idalmaactual">
                <input type="text" value="{{$idubi}}" hidden name="idubiactual">

                <div class="mb-3">
                    <label class="form-label">Producto nuevo para ubicación <span class="text-danger">*</span></label>
                    <input type="text" class="form-control mb-2" id="buscadorProductoNuevo" placeholder="Buscar por nombre, SKU o código de barras...">
                    <select name="id_producto" id="selectProductoNuevo" class="form-select" required>
                        <option value="">Escribe para buscar...</option>
                    </select>
                    <div class="form-text" id="ayudaBusquedaProducto"></div>
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Cantidad a ingresar <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text" name="cantidad" maxlength="10" required/>
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                </div>

                <div class="mb-4">
                    <label for="file1" class="drop-container2">
                        <span class="drop-title2">Subir documento de entrada</span>
                        <small class="fs-8 text-secondary">Solo se admite formato ".pdf"</small>
                        <input class="validaPDF" type="file" name="entrada" required>
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </label>
                </div>

                <button type="submit" class="btn btn-baseColor fs-8 w-100">
                    <i class="fa-solid fa-check"></i> Ingresar
                </button>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
<script>
    (function(){
        const input = document.getElementById('buscadorProductoNuevo');
        const select = document.getElementById('selectProductoNuevo');
        const ayuda = document.getElementById('ayudaBusquedaProducto');
        const idubi = {{ (int)($idubi ?? 0) }};

        if (!input || !select || !idubi) return;

        let abortCtrl = null;
        let debounceTimer = null;

        function setLoading(loading) {
            ayuda.textContent = loading ? 'Buscando…' : '';
        }

        async function buscar(q) {
            if (abortCtrl) abortCtrl.abort();
            abortCtrl = new AbortController();
            setLoading(true);
            try {
                const params = new URLSearchParams({ idubi: String(idubi), q: q || '' });
                const resp = await fetch('{{ route('inventario.buscar-productos-nuevos') }}' + '?' + params.toString(), { signal: abortCtrl.signal });
                const data = await resp.json();
                select.innerHTML = '';
                if (Array.isArray(data) && data.length) {
                    select.appendChild(new Option('Selecciona un producto…', ''));
                    data.forEach(item => {
                        const etiqueta = `${item.nombre} ${item.sku ? '('+item.sku+')' : ''}`.trim();
                        select.appendChild(new Option(etiqueta, item.id));
                    });
                    ayuda.textContent = `${data.length} resultados`;
                } else {
                    select.appendChild(new Option('Sin resultados', ''));
                    ayuda.textContent = 'Sin resultados';
                }
            } catch (e) {
                select.innerHTML = '';
                select.appendChild(new Option('Error al buscar', ''));
                ayuda.textContent = 'Error al buscar';
            } finally {
                setLoading(false);
            }
        }

        input.addEventListener('input', function(){
            const q = this.value.trim();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => buscar(q), 250);
        });

        buscar('');
    })();
</script>
@endsection
