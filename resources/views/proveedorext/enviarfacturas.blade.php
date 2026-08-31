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
         @elseif($mensaje = Session::get('exitoeliminar'))
         @php
             echo '<script language="JavaScript">
                 ';
                 echo
                    'swal("¡Proveedor eliminado exitosamente!","Proceso realizado de forma correcta","success", {buttons: false,timer: 2000});';
                 echo '
             </script>';
         @endphp
    @endif

    <div class="container-fluid format_page">
        <div class="row">
            <div class="col-lg-3 col-6 start-center">
                <h3 class="mt-1 animate_animated animate_backInLeft">Proveedores</h3>
                <span class="p-0 m-0 d-none d-md-block fs-8">Administración de Proveedores.</span>
            </div>
    
            <div class="col-lg-9 col-6 text-end">
                <button class="btn btn-baseColor fs-7" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasBottom" aria-controls="offcanvasBottom">
                    <i class="fa-solid fa-plus"></i> Nuevo Proveedor
                </button> 
            </div>
        </div>

        <div class="row mt-3">
            <div class="table-responsive">
                <table class="table table-stripped table-hover display" id="table">
                    <thead>
                        <tr class="table-light text-secondary">
                            {{-- <th class="text-center text-truncate fw-bold">No.</th> --}}
                            <th class="text-center text-truncate fw-bold">Nombre</th>
                            <th class="text-center text-truncate fw-bold">Telefono</th>
                            <th class="text-center text-truncate fw-bold">Direccion</th>
                            <th class="text-center text-truncate fw-bold">Estado</th>
                            <th class="text-center text-truncate fw-bold">Nit</th>
                            <th class="text-center text-truncate fw-bold">Giro</th>
                            <th class="text-center text-truncate fw-bold">rfc</th>
                            <th class="text-center text-truncate fw-bold">Herramientas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proveedores as $ltsproveedores)
                            <tr>
                                {{-- <td class="fw-bold">{{ $ltsproveedores->id }}</td> --}}
                                <td>{{ $ltsproveedores->nombre }}</td>
                                <td>{{ $ltsproveedores->telefono}}</td>
                                <td>{{ $ltsproveedores->direccion }}</td>
                                <td>
                                    @if ($ltsproveedores->estado == 'A')
                                        <span class="badge badge-success fw-normal fs-9"> Activa</span>
                                    @elseif($ltsproveedores->estado == 'P')
                                        <span class="badge badge-orange fw-normal fs-9"> Pendiente</span>
                                    @elseif($ltsproveedores->estado == 'I')
                                        <span class="badge badge-danger fw-normal fs-9"> Inactiva</span>
                                    @elseif($ltsproveedores->estado == 'C')
                                        <span class="badge badge-secondary fw-normal fs-9"> Cancelda</span>
                                    @endif
                                </td>

                                <td>{{ $ltsproveedores->nit }}</td>
                                <td>{{ $ltsproveedores->giro }}</td>
                                <td>{{ $ltsproveedores->rfc }}</td>

                                <td>
                                    <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalFactura{{$ltsproveedores->id}}">
                                        <i class="fa-solid fa-file-import fs-7"></i>
                                    </button>

                                    <a href="/editar_proveedor/{{$ltsproveedores->id}}"
                                        class="btn btn-primary"><i class="fa-solid fa-pen fs-8"></i>
                                    </a>
                             
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#Eliminar{{$ltsproveedores->id}}"
                                        class="btn btn-danger"><i class="fa-solid fa-trash fs-8"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    @foreach ($proveedores as $ltsproveedores)
     <!-- Factura Subir Modal-->
     <div class="modal fade" id="modalFactura{{$ltsproveedores->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h4>XML Factura</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('factura.procesar') }}" method="POST" enctype="multipart/form-data" class="form g-3 needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="p-4 pt-0 pb-0">
                            <input type="file" class="form-control" name="xml" accept=".xml" required>
                        </div>
                    </div>

                     <!-- Guardar -->
                     <div class="row justify-content-center mt-3 mb-4">
                        <button class="btn btn-baseColor fs-8 col-4" type="submit"><i
                                class="fa-solid fa-check"></i> Procesar
                        </button>
                    </div>
                </form>
            </div>
        </div>
     </div>

      <!-- Modal confirmacion -->
      <div class="modal fade" id="Eliminar{{$ltsproveedores->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="p-4 pt-0 pb-0 text-center">
                     <h5>¿Estás seguro de eliminar el proveedor?</h5>
                    </div>
                </div>

                <div class="row justify-content-center mt-4 mb-3">
                    <a class="btn btn-baseColor fs-8 col-8" href="/EliminarProveedor/{{$ltsproveedores->id}}" >
                        <i class="fa-solid fa-check"></i> Aceptar
                    </a>
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
    <div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasBottom" aria-labelledby="offcanvasBottomLabel"
        style="height:70vh">
        <div class="offcanvas-header">
            <nav id="navbar-example2" class="navbar navbar-light px-3">
                <a class="navbar-brand">Nuevo Proveedor</a>
            </nav>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body small">
            <div class="p-4 pt-0 pb-0">
                <div data-bs-spy="scroll" data-bs-target="#navbar-example" data-bs-offset="0" class="scrollspy-example"
                    tabindex="0">
                    <form action="insertarproveedor" method="POST" class="form g-3 needs-validation" novalidate>
                        @csrf

                        <div class="row mb-3">
                            <h4 id="paso1">Paso 1. General</h4>
                            <div class="row">
                                <div class="col">
                                    <!-- Name input -->
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
                                <div class="col">
                                    <!-- Email input -->
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

                                <div class="col">
                                    <!-- Email input -->
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
                                
                                <div class="col">
                                    <!-- Email input -->
                                    <div class="form-outline">
                                        <label class="form-label">nit</label>
                                        <input type="text" id="nit" name="nit"
                                        class="form-control" required />
                                    <div class="valid-feedback">
                                    </div>
                                </div>
                            </div>

                            <div class="col">
                                <!-- Email input -->
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
    
                            <div class="col">
                                <!-- Email input -->
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
                        
                        <!-- Guardar empleado -->
                        <div class="offcanvas-footer text-end" style="padding:10px;">
                            <button class="btn btn-baseColor fs-8" style="border-radius: 10px;" type="submit"><i
                                    class="fa-solid fa-check"></i>&nbsp;&nbsp;Crear Proveedor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@isset($datos)
    <div class="mt-5">
        <h2>Datos de la Factura</h2>
        <table class="table table-bordered">
            <tbody>
                <tr><th>Emisor</th><td>{{ $datos['emisor'] }}</td></tr>
                <tr><th>RFC Emisor</th><td>{{ $datos['rfc_emisor'] }}</td></tr>
                <tr><th>Receptor</th><td>{{ $datos['receptor'] }}</td></tr>
                <tr><th>Total</th><td>${{ number_format($datos['total'], 2) }}</td></tr>
                <tr><th>Fecha</th><td>{{ $datos['fecha'] }}</td></tr>
                <tr><th>UUID (Folio Fiscal)</th><td>{{ $datos['uuid'] }}</td></tr>
            </tbody>
        </table>
    </div>
@endisset


    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
    
@endsection
