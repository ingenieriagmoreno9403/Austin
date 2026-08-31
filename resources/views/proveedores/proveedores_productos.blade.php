@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid format_page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div>
                            <div class="mb-1">
                                <a href="/proveedores" class="text-muted text-decoration-none fs-8">
                                    <i class="fa-solid fa-chevron-left me-1"></i>Proveedores
                                </a>
                            </div>
                            <h2 class="mb-0 text-marino fw-bold">
                                Proveedores <i class="fa-solid fa-chevron-right fs-6"></i> Productos
                            </h2>
                            <p class="text-muted mb-0">Catálogo de productos del proveedor</p>
                        </div>
                    </div>
                    <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalInsert">
                            <i class="fa-solid fa-plus"></i> Añadir Producto
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="table-responsive">
                <table class="table table-stripped table-hover display" id="table">
                    <thead>
                        <tr class="table-light text-secondary">
                            <th class="text-center text-truncate fw-bold">No.</th>
                            <th class="text-center text-truncate fw-bold">Nombre</th>
                            <th class="text-center text-truncate fw-bold">Categoria</th>
                            <th class="text-center text-truncate fw-bold">Unidad de Medida</th>
                            <th class="text-center text-truncate fw-bold">Costo</th>
                            <th class="text-center text-truncate fw-bold">Descripcion</th>
                            <th class="text-center text-truncate fw-bold">Comentarios</th>
                            <th class="text-center text-truncate fw-bold">Moneda</th>
                            <th class="text-center text-truncate fw-bold">Herramientas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($productosproveedor as $ltsproductosproveedor)
                            <tr>
                                <td class="fw-bold">{{ $ltsproductosproveedor->id }}</td>
                                <td>{{ $ltsproductosproveedor->nombre }}</td>
                                <td>{{ $ltsproductosproveedor->categoria}}</td>
                                <td>{{ $ltsproductosproveedor->unidad_medida }}</td>
                                <td>$ {{ number_format($ltsproductosproveedor->costo, 2) }}</td>
                                

                                <td class="text-truncate">
                                    <p class="btn border-0 text-truncate m-0 fs-8" style="max-width: 150px;" data-bs-toggle="modal" data-bs-target="#Modaldescripcion{{$ltsproductosproveedor->id}}">
                                      {{$ltsproductosproveedor->descripcion}}
                                    </p>
                                </td>

                                <td class="text-truncate">
                                    <p class="btn border-0 text-truncate m-0 fs-8" style="max-width: 150px;" data-bs-toggle="modal" data-bs-target="#Modalcomentario{{$ltsproductosproveedor->id}}">
                                      {{$ltsproductosproveedor->comentarios}}
                                    </p>
                                </td>

                                <td>{{ $ltsproductosproveedor->moneda }}</td>
                                <td>
                                    <a href="/EditarProductoProveedor/{{$ltsproductosproveedor->id}}"
                                        class="btn btn-primary"><i class="fa-solid fa-pen fs-8"></i>
                                    </a>
                               
                                    <a href="/EliminarProveedor/{{$ltsproductosproveedor->id}}"
                                        class="btn btn-warning"><i class="fa-solid fa-trash fs-8"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach ($productosproveedor as $ltsproductosproveedor)
        <!-- Modal comentarios -->
        <div class="modal fade" id="Modalcomentario{{$ltsproductosproveedor->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row justify-content-center">
                        <div class="col-11">
                            <h6 class="text-dark mb-4"> Comentarios</h6>

                            <p class="text-dark text-wrap fw-normal fs-8" style="text-align: justify!important">
                            {{$ltsproductosproveedor->comentarios}}
                            </p>

                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal descripcion -->
        <div class="modal fade" id="Modaldescripcion{{$ltsproductosproveedor->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row justify-content-center">
                        <div class="col-11">
                            <h6 class="text-dark mb-4"> Descripción</h6>

                            <p class="text-dark text-wrap fw-normal fs-8" style="text-align: justify!important">
                            {{$ltsproductosproveedor->descripcion}}
                            </p>

                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach



    <!-- Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title text-dark fs-5" id="exampleModalLabel">Eliminar nomina</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 class="text-dark">¿Está seguro que desea eliminar esta nomina?</h6>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger fs-8" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> Cerrar
                    </button>
                    
                </div>
            </div>
        </div>
    </div> 


    <!-- Insertar Modal-->
    <div class="modal fade" id="modalInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h4>Nuevo Proveedor</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('InsertProductoProveedor', 1)}}" method="POST" class="form needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="p-4 pt-0 pb-0">
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Nombre</label>
                                    
                                        <select class="form-select" name="nombre" id="nombre" required>
                                            <option value="">...</option>
                                            @foreach ($productos as $ltsproductos )
                                                <option value="{{ $ltsproductos->id }}" selected>{{ $ltsproductos->nombre." Descripcion: ".$ltsproductos->descripcion." Unidad de medida: ".$ltsproductos->id_unidad_medida}}</option>
                                            @endforeach
                                        </select>                                        
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6 mb-2">
                                    <div class="form-outline">
                                        <label class="form-label">Costo Por Unidad</label>
                                        <input type="decimal" step="0.01" id="costo" name="costo"
                                        class="form-control" required />
                                        <div class="valid-feedback">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-6 mb-2">
                                    <div class="form-outline">
                                        <label class="form-label">Tipo de cambio</label>
                                        <input type="text" id="moneda" name="moneda"
                                            class="form-control" required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mb-2">
                                    <div class="form-outline">
                                        <label class="form-label">Comentarios</label>
                                        <textarea  type="text" id="comentario" name="comentario" maxlength="250"
                                            class="form-control" required ></textarea>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                     <!-- Guardar -->
                     <div class="row justify-content-center mt-4 mb-5">
                        <button class="btn btn-baseColor fs-8 col-8" type="submit"><i
                                class="fa-solid fa-check"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar detalles del producto seleccionado
            const selectProducto = document.getElementById('nombre');
            const descripcionEl = document.getElementById('producto-descripcion');
            const unidadEl = document.getElementById('producto-unidad');
            
            selectProducto.addEventListener('change', function() {
                const optionSelected = this.options[this.selectedIndex];
                
                if (this.value) {
                    const fullDetails = optionSelected.getAttribute('title');
                    const parts = fullDetails.split(' - ');
                    
                    descripcionEl.textContent = parts[0]; // Nombre completo
                    
                    if (parts.length > 1) {
                        const descripcion = parts[1].replace('Descripción: ', '');
                        unidadEl.textContent = `${descripcion} ${parts.length > 2 ? ' - ' + parts[2] : ''}`;
                    } else {
                        unidadEl.textContent = '';
                    }
                } else {
                    descripcionEl.textContent = 'Seleccione un producto para ver sus detalles';
                    unidadEl.textContent = '';
                }
            });
        });
    </script>
@endsection
