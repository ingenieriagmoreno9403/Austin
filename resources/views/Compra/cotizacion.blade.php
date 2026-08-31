@extends('layouts.app')
@section('content')

    @if ($mensaje = Session::get('successcalcular'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'swal("¡Cotización calculada exitosamente!","Proceso realizado de forma correcta","success", {buttons: false,timer: 2000});';
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

    <!-- pantalla principal -->
   
    <div class="container-fluid format_page">
        <!-- encabezado -->
        <div class="row">
            <div class="col-lg-3 col-6 start-center">
                <h3 class="mt-1 animate__animated animate__backInLeft">Cotizaciones</h3>
                <span class="p-0 m-0 d-none d-md-block fs-8">Administración de compras de la Empresa.</span>
            </div>
    
            <div class="col-lg-9 col-6 text-end">
                <button class="btn btn-baseColor fs-7" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasBottom" aria-controls="offcanvasBottom">
                    <i class="fa-solid fa-plus"></i> Nueva Cotizacion
                </button> 
            </div>
        </div>

        {{-- tabla de cotizaciones principal  --}}
        <div class="row mt-3">
            <div class="table-responsive">
                <table class="table table-stripped table-hover display" id="tableEmpleados">
                    <thead>
                        <tr>
                            <th></th>
                            <th class="text-truncate">Nombre</th>
                            <th class="text-truncate">Fecha Creacion</th>
                            <th class="text-truncate">Fecha Limite</th>
                            <th class="text-truncate">Descripcion</th>
                        </tr>
                    </thead>
    
                    <tbody>
                        @foreach ($varlista as $vis)
                            <tr>
                                <td></td>
                                <td>
                                    {{ $vis->nombre }}
                                </td>
                                <td>{{ $vis->fecha_creacion }}</td>
                                <td>{{ $vis->fecha_limite }}</td>
                                <td>{{ $vis->descripcion_detalle }}</td>
                                
                                
                                <td class="text-truncate"> <!-- botones de acciones  -->
                                    
                                    <a class="btn btn-baseColor m-0" type="button" data-bs-toggle="modal"  data-bs-target="#datosClientes{{ $vis->id }}" title="Ver Datos Cotizacion">
                                        <i class="fa-solid fa-eye fs-8"></i>
                                    </a>
                                    <!-- editar cotizacion -->
                                    <a class="btn btn-primary m-0" title="Editar Cotizacion" href="{{ route('compras.editar',$vis->id) }}"> 
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </a>
    
                                    <!-- eliminar cotizacion -->
                                    {{-- <form action="{{ route('compras.destroy',$vis->id) }}" method="POST" class="d-inline" id="formEliminar{{ $vis->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-danger m-0" data-bs-toggle="modal" data-bs-target="#cancelModal" title="Baja Cotizacion" >
                                            <i class="fa-solid fa-trash fs-8"></i>
                                        </button>
                                    </form> --}}

                                    <button type="button"
                                        class="btn btn-danger m-0"
                                        data-bs-toggle="modal"
                                        data-bs-target="#cancelModal"
                                        data-id="{{ $vis->id }}"
                                        title="Baja Cotizacion">
                                        <i class="fa-solid fa-trash fs-8"></i>
                                    </button>

                                    {{-- @if ($vis->estado == 'A')
                                        <a class="btn btn-primary m-0" title="Editar Empleado" href="{{ route('compras.edit',$vis->id) }}/edit"> 
                                            <i class="fa-solid fa-pen fs-8"></i>
                                        </a>
                                    @else
                                        <a class="btn btn-primary m-0" title="Reactivar Empleado" href="/ReactivarEmpleado/{{ $vis->idempleado }}"> 
                                            <i  class="fa-solid fa-user-check fs-8"></i>
                                        </a>
                                    @endif
    
                                    @if ($vis->estado == 'A')
                                        @if ($permisos4 == 'eliminar_empleados')
                                            <a class="btn btn-danger m-0" href="/Empleados/Baja/{{ $vis->idempleado }}" title="Baja Empleado">
                                                <i class="fa-solid fa-trash fs-8"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-danger m-0" title="Baja Empleado" disabled> 
                                                <i class="fa-solid fa-trash fs-8"></i>
                                            </button>
                                        @endif
                                    @else
                                        @if ($vis->archivo_baja == '1')
                                            <a class="btn btn-baseColor m-0" target="_blank" title="Ver Docuemnto Baja" href="DetallesEmpleados/bajas/baja_{{ $vis->idempleado }}.pdf"> 
                                                <i class="fa-solid fa-file fs-8"></i>
                                            </a>
                                        @else
                                            <button class="btn btn-baseColor m-0"title="Subir Baja Firmada" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvaBottomUpload{{ $vis->idempleado }}" aria-controls="offcanvasBottom" id="{{ $vis->id }}"> 
                                                <i class="fa-solid fa-arrow-up-from-bracke fs-8t"></i>
                                            </button>
                                        @endif
                                    @endif --}}
                                </td> 
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal preguntar eliminado-->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="modalDeleteForm">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title text-dark fs-5" id="exampleModalLabel">Eliminar Cotizacion</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <h6 class="text-dark">¿Está seguro que desea eliminar esta Cotizacion?</h6>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary fs-8" data-bs-dismiss="modal"> <i class="fa-solid fa-xmark"></i> Cancelar</button>
                        <button type="submit" class="btn btn-danger fs-8">Eliminar</button>
                    </div>
                </div>
            </form>
        </div>
    </div> 


    <!-- Insertar Modal-->
    <div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasBottom" aria-labelledby="offcanvasBottomLabel"
        style="height:70vh">
        <div class="offcanvas-header">
            <nav id="navbar-example2" class="navbar navbar-light px-3">
                <a class="navbar-brand">Nueva Cotizacion</a>
            </nav>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body small">
            <div class="p-4 pt-0 pb-0">
                <div data-bs-spy="scroll" data-bs-target="#navbar-example" data-bs-offset="0" class="scrollspy-example"
                    tabindex="0">
                    <form action="{{ route('compras.store')}}" method="POST" class="form g-3 needs-validation" novalidate>
                        @csrf

                        <div class="row mb-3">
                            <h4 id="paso1">Paso 1. General</h4>
                            <div class="row">
                                <div class="col">
                                    <!-- nombre -->
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Nombre de Cotización</label>
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
                                    <!-- fecha creacion-->
                                    <div class="form-outline">
                                        <label class="form-label">Fecha de Inicio</label>
                                        <input type="date" id="fecha_creacion" name="fecha_creacion"
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
                                    <!-- fecha limite-->
                                    <div class="form-outline">
                                        <label class="form-label">Fecha Fin</label>
                                        <input type="date" id="fecha_limite" name="fecha_limite"
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
                                    <!-- descripcion detalle -->
                                    <div class="form-outline">
                                        <label class="form-label">Descripcion</label>
                                        <textarea class="form-control" id="descripcion_detalle" name="descripcion_detalle"
                                            rows="2" required></textarea>
                                        {{-- <input type="text" id="descripciondetalle" name="descripcion_detalle"
                                            class="form-control" required /> --}}
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- <div class="col">
                                    <!-- Email input -->
                                    <div class="form-outline">
                                        <label class="form-label">Tipo de Cotización</label>
                                        <select class="form-select" name="tipo_Cotizacion" required>
                                            <option value="">Seleccionar...</option>
                                        </select>
                                    </div>
                                </div> --}}
                            </div>
                        </div>
                        
                        <!-- Guardar empleado -->
                        <div class="offcanvas-footer text-end" style="padding:10px;">
                            <button class="btn btn-baseColor fs-8" style="border-radius: 10px;" type="submit"><i
                                    class="fa-solid fa-check"></i>&nbsp;&nbsp;Crear Cotización
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('cancelModal');
            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const form = document.getElementById('modalDeleteForm');
                const route = "{{ route('compras.destroy', ':id') }}".replace(':id', id);
                form.setAttribute('action', route);
            });
        });
    </script>    
@endsection
