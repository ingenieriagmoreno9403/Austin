@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('warningEliminar'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡El puesto que desea eliminar esta siendo utilizado!", text: "Algún empleado depende de este puesto, pruebe cambiando de puesto antes"});';
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
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Puestos</h2>
                        <p class="text-muted mb-0">Gestión y control de puestos, con visualización de detalle</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if ($permisos1 == 'nuevo_puesto')
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            <i class="fa-solid fa-plus"></i> Nuevo Puesto
                        </button>
                    @else
                        <button class="btn btn-baseColor" disabled type="button">
                            <i class="fa-solid fa-plus"></i> Nuevo Puesto
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
              <tr class="table-light"> 
                  <th class="text-center fw-normal text-truncate">Nombre</th>
                  <th class="text-center fw-normal text-truncate">Estado</th>
                  <th class="text-center fw-normal text-truncate">Descripción</th>
                  <th class="text-center fw-normal text-truncate">Herramientas</th>
              </tr>
            </thead>
  
            <tbody>
              @foreach($obtenerpuestosAll as $datos) 
                <tr>
                  <td class="text-truncate">{{$datos->nombre}}</td>
                  <td class="text-truncate">
                    @if($datos->estado == "A")
                      <span class="badge badge-success-dark fs-9">Activa</span>
                    @endif
                    @if($datos->estado == "I")
                      <span class="badge badge-danger-dark fs-9">Inactiva</span>
                    @endif
                  </td>
                  <td class="text-truncate">{{$datos->descripcion}}</td>
                  <td>
                    @if ($permisos2 == 'editar_puesto')
                        @if ($datos->estado != 'I')
                            <button class="btn btn-primary border-0 m-0" type="button" data-bs-toggle="modal"  data-bs-target="#modalEditar{{ $datos->id }}">
                                <i class="fa-solid fa-pen fs-8"></i>
                            </button>
                        @else
                            <button class="btn btn-primary border-0 m-0" type="button"  disabled>
                                <i class="fa-solid fa-pen fs-8"></i>
                            </button>
                        @endif
                    @else
                        <button class="btn btn-primary border-0 m-0" type="button" disabled>
                            <i class="fa-solid fa-pen fs-8"></i>
                        </button>
                    @endif

                    @if ($permisos3 == 'eliminar_puesto')
                        @if ($datos->estado != 'I')
                            <button class="btn btn-danger m-0" type="button" data-bs-toggle="modal" data-bs-target="#ModalCancel{{ $datos->id }}">
                                <i class="fa-solid fa-trash fs-8"></i>
                            </button>
                        @else
                            <button class="btn btn-danger m-0" type="button" disabled>
                                <i class="fa-solid fa-trash fs-8"></i>
                            </button>
                        @endif
                    @else
                        <button class="btn btn-danger m-0" type="button" disabled>
                            <i class="fa-solid fa-trash fs-8"></i>
                        </button>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
        </table>
      </div>  
    </div>

    @foreach($obtenerpuestosAll as $datos) 
     <!-- Modal Editar Puesto -->
     <div class="modal fade" id="modalEditar{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header border-0">
              <h5 class="modal-title text-dark" id="exampleModalLabel">Editar Puesto</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <form action="/CatalogoGeneral/Puestos/EditarPuesto/{{$datos->id}}" method="POST" enctype="multipart/form-data" class="form g-3 needs-validation mt-1 text-start" novalidate>
            @csrf
              <div class="modal-body p-4 pt-0">
                  <div class="row mb-2">
                    <div class="form-outline">
                        <label class="form-label" for="form8Example4">Nombre de Cuenta</label>
                        <input type="text"  class="form-control text" name="nombre" id="nombre" maxlength="45" value="{{$datos->nombre}}" required />
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>

                    <div class="form-outline">
                      <label class="form-label" for="form8Example4">Descripción de Cuenta</label>
                      <input type="text"  class="form-control text" name="descripcion" id="descripcion" maxlength="45" value="{{$datos->descripcion}}" required />
                      <div class="valid-feedback">¡Se ve bien!</div>
                      <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                  </div>
              </div>

              <div class="container">
                <div class="row justify-content-center p-3">
                    <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                </div>
              </div>
          </form>
        </div>
      </div>
     </div>

      <!-- Modal Eliminar Puesto -->
      <div class="modal fade" id="ModalCancel{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header border-0">
              <h5 class="modal-title" id="exampleModalLabel">Eliminar Puesto</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="/CatalogoGeneral/Puestos/EliminarPuesto/{{$datos->id}}" method="POST" class="form g-3 needs-validation text-start" novalidate>
              @csrf
              <div class="modal-body mb-3">
                  <h6 class="text-break">¿Qué desea realizar con el puesto "{{$datos->nombre}}"?</h6>

                  <div class="for-outline">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="eliminar" value="desactivar" id="flexRadioDefault1" checked>
                      <label class="form-check-label" for="flexRadioDefault1">
                        Desactivar
                      </label>
                    </div>
                    
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="eliminar" value="eliminar" id="flexRadioDefault2">
                      <label class="form-check-label" for="flexRadioDefault2">
                        Eliminar definitivamente
                      </label>
                    </div>

                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                  </div>
              </div>

              <div class="container">
                <div class="row justify-content-center p-3">
                        <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endforeach

    <!-- Modal Nueva Cuenta -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title" id="exampleModalLabel">Alta de Puesto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <form action="/CatalogoGeneral/Puestos/NuevoPuesto" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
            @csrf
            <div class="modal-body">
              <div class="container">
                <div class="row mb-4">
                    <div class="form-outline">
                        <label class="form-label" for="form8Example4">Nombre de Puesto</label>
                        <input type="text"  class="form-control text" name="nombre" id="nombre" maxlength="45" required />
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>

                    <div class="form-outline">
                      <label class="form-label" for="form8Example4">Descripción de Puesto</label>
                      <input type="text"  class="form-control text" name="descripcion" id="descripcion" maxlength="45" required />
                      <div class="valid-feedback">¡Se ve bien!</div>
                      <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                   </div>
                </div>
              </div>
            </div>

            <div class="container">
              <div class="row justify-content-center p-3">
                  <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>

@endsection