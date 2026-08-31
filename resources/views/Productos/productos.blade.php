@extends('layouts.app')
@section('content')
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
                        <h2 class="mb-0 text-marino fw-bold">Catálogo de Productos</h2>
                        <p class="text-muted mb-0">Administración de productos</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if($permisos1 == "crear_product")
                        <a class="btn btn-baseColor" href="/pinsertaroductos">
                            <i class="fa-solid fa-plus"></i> Nuevo Producto
                        </a>
                    @else
                        <button class="btn btn-baseColor" disabled>
                            <i class="fa-solid fa-plus"></i> Nuevo Producto
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                    <tr>
                        {{-- <th>No.</th> --}}
                        <th class="text-truncate">Herramientas</th>
                        <th class="text-truncate">Imagen</th>
                        <th class="text-truncate">SKU</th>
                        <th class="text-truncate">Código de Barras</th>
                        <th class="text-truncate">Nombre</th>
                        <th class="text-truncate">Descripción</th>
                        <th class="text-truncate">Nombre Categoria</th>
                        <th class="text-truncate">Unidad de Medida</th>
                        <th class="text-truncate">Proveedor</th>
                        <th class="text-truncate">Costo Unitario</th>
                        <th class="text-truncate">Costo Compra</th>
                        <th class="text-truncate">Costo Publico</th>
                        <th class="text-truncate">Existencia Minima</th>
                        <th class="text-truncate">Existencia Maxima</th>
                        <th class="text-truncate">Fecha Ultima Entrada</th>
                        <th class="text-truncate">Fecha Ultima Salida</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($Listadoproductos as $pro)
                        <tr>
                            {{-- <td class="text-center">{{$pro->idproducto}}</td> --}}
                            <td>
                                @if($permisos2 == "edit_product")
                                    <a class="btn btn-primary" href="/pactualizaprod/{{$pro->idproducto}}">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </a>
                                @else
                                    <button class="btn btn-primary" disabled>
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($pro->ruta_img1 == '')
                                    <center><img src="{{ asset('Images/Productos/producto.jpg') }}" class="rounded-3 shadow"style="height: 50px;width: 50px;object-fit: cover;" alt="foto_perfil"></center>
                                @else
                                    <center><img src="{{ asset('Images/Productos/'.$pro->sku.'/'.$pro->ruta_img1) }}" class="rounded-3 shadow"style="height: 50px;width: 50px;object-fit: cover;" alt="foto_perfil"></center>
                                @endif
                            </td>

                            
                            <td class="text-truncate">{{$pro->sku}}</td>
                            <td>{{$pro->codigo_barras}}</td> 
                            <td>
                                <p class="btn border-0 text-truncate m-0 fs-8" style="max-width: 150px;" data-bs-toggle="modal" data-bs-target="#Modalnombre{{$pro->idproducto}}">
                                    <b class="text-orange"><i class="fa-solid fa-circle-info"></i></b> {{$pro->nombre}}
                                </p>
                            </td>

                            <td class="text-truncate">
                                <p class="btn border-0 text-truncate m-0 fs-8" style="max-width: 150px;" data-bs-toggle="modal" data-bs-target="#Modalcomentario{{$pro->idproducto}}">
                                    <b class="text-orange"><i class="fa-solid fa-circle-info"></i></b> {{$pro->descripcion}}
                                </p>
                            </td>
                            
                            <td><span class="badge badge-secondary fw-normal fs-9"> {{$pro->nombrecate}}</span></td>
                            <td>{{$pro->unidadmedida}}</td>
                            <td>{{$pro->provedor}}</td>
                            <td>$ {{ number_format($pro->precio_unitario, 2) }}</td>
                            <td>$ {{ number_format($pro->costo_compra, 2) }}</td>
                            <td>$ {{ number_format($pro->costo_venta, 2) }}</td>
                            <td>{{$pro->minima_existencia}}</td>
                            <td>{{$pro->maxima_existencia}}</td>
                            <td>{{date("d/m/Y", strtotime($pro->fecha_ultima_entrada))}}</td>
                            <td>{{date("d/m/Y", strtotime($pro->fecha_ultima_salida))}}</td>
                            
                        </tr>		
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach ($Listadoproductos as $pro)
    <!-- Modal nombre -->
    <div class="modal fade" id="Modalnombre{{$pro->idproducto}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row justify-content-center">
                    <div class="col-11">
                        <h6 class="text-dark mb-4"> Nombre Completo</h6>

                        <p class="text-dark text-wrap fw-normal fs-8" style="text-align: justify!important">
                        {{$pro->nombre}}
                        </p>

                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal descripcion -->
    <div class="modal fade" id="Modalcomentario{{$pro->idproducto}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row justify-content-center">
                    <div class="col-11">
                        <h6 class="text-dark mb-4"> Descripción</h6>

                        <p class="text-dark text-wrap fw-normal fs-8" style="text-align: justify!important">
                        {{$pro->descripcion}}
                        </p>

                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach


<script src="{{ asset('js/table.js') }}"></script>
<script>
    function editar() {
        var ele = document.getElementsByName('idproducto');

        for (i = 0; i < ele.length; i++) {
            if (ele[i].checked){
                $('#edit_id').val(ele[i].value);
                let formulario = document.getElementById('form_edit');
                formulario.submit();
            }else{
                $('#edit_id').val("");
            }
        }
    }

    function baja() {
        var ele = document.getElementsByName('idproducto');

        for (i = 0; i < ele.length; i++) {
            if (ele[i].checked){
                $('#baja_id').val(ele[i].value);
                let formulario = document.getElementById('form_baja');
                formulario.submit();
            }else{
                $('#baja_id').val("");
            }
        }
    }
</script>
@endsection






