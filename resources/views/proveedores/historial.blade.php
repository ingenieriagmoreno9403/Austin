@extends('layouts.app')
@section('content')

    @if ($mensaje = Session::get('successcalcular'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'swal("¡Nómina calculada exitosamente!","Proceso realizado de forma correcta","success", {buttons: false,timer: 2000});';
                echo '
            </script>';
        @endphp
    @elseif($mensaje = Session::get('Errorpermisos'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'swal("¡No se encontro el permiso para efectuar la accion!","Comunicate al area de sistemas para validar permisos","warning", {buttons: false,timer: 5000});';
                echo '
            </script>';
        @endphp
    @elseif($mensaje = Session::get('errorFechas'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'swal("¡Estas fechas no son validas!","La fecha ya se encuentra en sistema","warning", {buttons: false,timer: 5000});';
                echo '
            </script>';
        @endphp
    @elseif($mensaje = Session::get('warningBD'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'swal("¡Fallo la acción!","Contacte a un superior para ver el error","warning", {buttons: false,timer: 4000});';
                echo '
            </script>';
        @endphp
    @endif

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid format_page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Historial de Proveedores</h2>
                            <p class="text-muted mb-0">Consulta del historial de proveedores</p>
                        </div>
                    </div>
                    <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalInsert">
                            <i class="fa-solid fa-plus"></i> Nuevo Proveedor
                        </button>
                    </div>
                </div>
            </div>
        </div>


        
 
    </div>

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

                <div class="modal-body">
                    <div class="p-4 pt-0 pb-0">
                        <form action="" method="POST" class="form g-3 needs-validation" novalidate>
                            @csrf

                            <div class="row mb-3">
                                <div class="row">
                                    <div class="col mb-2">
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Nombre</label>
                                            <input type="text" class="form-control text" name="nombre"
                                                id="nombre" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col mb-2">
                                        <div class="form-outline">
                                            <label class="form-label">Telefono</label>
                                            <input type="text" id="telefono" name="telefono"
                                                class="form-control" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col mb-2">
                                        <div class="form-outline">
                                            <label class="form-label">Direccion</label>
                                            <input type="text" id="direccion" name="direccion"
                                                class="form-control" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                  
                                <div class="row">
                                    <div class="col mb-2">
                                        <div class="form-outline">
                                                <label class="form-label">nit</label>
                                                <input type="text" id="nit" name="nit"
                                                class="form-control" required />
                                            <div class="valid-feedback">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col mb-2">
                                        <div class="form-outline">
                                            <label class="form-label">Giro</label>
                                            <input type="text" id="giro" name="giro"
                                                class="form-control" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>
            
                                    <div class="col mb-2">
                                        <div class="form-outline">
                                            <label class="form-label">RFC</label>
                                            <input type="text" id="rfc" name="rfc"
                                                class="form-control" required />
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
                            
                            <!-- Guardar empleado -->
                            <div class="row justify-content-center mt-4 mb-3">
                                <button class="btn btn-baseColor fs-8 col-8" type="submit"><i
                                        class="fa-solid fa-check"></i>&nbsp;&nbsp;Crear Proveedor
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
@endsection
