@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('warningEliminarE'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡El estado que desea eliminar esta siendo utilizado!", text: "Este estado depende de otro registo, verifique dependencias, corriga y vuelva a intentar"});';
            echo '</script>'; 
    @endphp
@esleif($mensaje = Session::get('warningEliminarC'))
@php
        echo '<script language="JavaScript">';
        echo 'const Toast = Swal.mixin({';
        echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
        echo 'didOpen: (toast) => {';
        echo '  toast.onmouseenter = Swal.stopTimer;';
        echo '  toast.onmouseleave = Swal.resumeTimer;}});';
        echo 'Toast.fire({ icon: "error",title: "¡La ciudad que desea eliminar esta siendo utilizado!", text: "Esta ciudad depende de otro registo, verifique dependencias, corriga y vuelva a intentar"});';
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
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">
                            Lugares <i class="fa-solid fa-chevron-right fs-6"></i>
                            @if($filtro == "Estado")
                                Estados
                            @else
                                Ciudades
                            @endif
                        </h2>
                        <p class="text-muted mb-0">Catálogo de estados y ciudades</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if($filtro == "Estado")
                        @if ($permisos1 == 'nuevo_lugar')
                            <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                <i class="fa-solid fa-plus"></i> Nuevo Estado
                            </button>
                        @else
                            <button class="btn btn-baseColor" disabled type="button">
                                <i class="fa-solid fa-plus"></i> Nuevo Estado
                            </button>
                        @endif
                    @else
                        @if ($permisos1 == 'nuevo_lugar')
                            <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                <i class="fa-solid fa-plus"></i> Nueva Ciudad
                            </button>
                        @else
                            <button class="btn btn-baseColor" disabled type="button">
                                <i class="fa-solid fa-plus"></i> Nueva Ciudad
                            </button>
                        @endif
                    @endif

                    <form action="/CatalogoGeneral/Lugares" id="filtro-form" class="mb-0">
                        <select class="form-select form-select-sm w-auto" name="filtro" onchange="getComboA(this)" required aria-label="Filtrar por tipo">
                            @if($filtro == "Estado")
                                <option value="Estado" selected>Estados</option>
                                <option value="Ciudad">Ciudades</option>
                            @else
                                <option value="Estado">Estados</option>
                                <option value="Ciudad" selected>Ciudades</option>
                            @endif
                        </select>
                    </form>
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
                  @if($filtro == "Ciudad")
                      <th class="text-center fw-normal text-truncate">Estado</th>
                  @endif
                  <th class="text-center fw-normal text-truncate">Creación</th>
                  <th class="text-center fw-normal text-truncate">Actualización</th>
                  <th class="text-center fw-normal text-truncate">Herramientas</th>
              </tr>
            </thead>
  
            <tbody>
              @foreach($obtenerlugar as $datos) 
                <tr>
                  <td class="text-truncate">{{$datos->nombre}}</td>

                  @if($filtro == "Ciudad")
                      <td class="text-truncate">{{$datos->estado}}</td>
                  @endif

                  <td class="text-truncate"> {{$datos->created_by}} - {{$datos->created_at}}</td>
                  <td class="text-truncate"> {{$datos->updated_by}} - {{$datos->updated_at}}</td>

                  <td>
                    @if ($permisos2 == 'editar_lugar')
                        <button class="btn btn-primary border-0 m-0" type="button" data-bs-toggle="modal"  data-bs-target="#modalEditar{{ $datos->id }}">
                            <i class="fa-solid fa-pen fs-8"></i>
                        </button>
                    @else
                        <button class="btn btn-primary border-0 m-0" type="button" disabled>
                            <i class="fa-solid fa-pen fs-8"></i>
                        </button>
                    @endif

                    @if ($permisos3 == 'eliminar_lugar')
                        <button class="btn btn-danger m-0" type="button" data-bs-toggle="modal" data-bs-target="#ModalCancel{{ $datos->id }}">
                            <i class="fa-solid fa-trash fs-8"></i>
                        </button>
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
   
    @foreach($obtenerlugar as $datos) 
     <!-- Modal Editar Estado -->
     <div class="modal fade" id="modalEditar{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header border-0">
              <h5 class="modal-title text-dark" id="exampleModalLabel">Editar {{$filtro}}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          @if($filtro == "Estado")
            <form action="/CatalogoGeneral/Estado/EditarEstado/{{$datos->id}}" method="POST" enctype="multipart/form-data" class="form g-3 needs-validation mt-1 text-start" novalidate>
          @else
            <form action="/CatalogoGeneral/Ciudad/EditarCiudad/{{$datos->id}}" method="POST" enctype="multipart/form-data" class="form g-3 needs-validation mt-1 text-start" novalidate>
          @endif

            @csrf
              <div class="modal-body p-4 pt-0">
                  <div class="row mb-2">
                    <div class="form-outline">
                        <label class="form-label" for="form8Example4">Nombre de {{$filtro}}</label>
                        <input type="text"  class="form-control text" name="nombre" id="nombre" maxlength="20" value="{{$datos->nombre}}" required />
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>

                    @if($filtro == "Ciudad")
                      <div class="form-outline mt-2">
                          <label class="form-label" for="form8Example4">Estados</label>
                          <select class="form-select" name="idestado" required>
                              @foreach ($listEstados as $item)
                                @if($datos->id_estado == $item->id)
                                  <option value="{{$item->id}}" selected>{{$item->nombre}}</option>
                                @else
                                  <option value="{{$item->id}}">{{$item->nombre}}</option>
                                @endif
                              @endforeach
                          </select>
                          
                          <div class="valid-feedback">¡Se ve bien!</div>
                          <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                      </div>
                    @endif
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

      <!-- Modal Eliminar Estado -->
      <div class="modal fade" id="ModalCancel{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header border-0">
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

              @if($filtro == "Estado")
                <form action="/CatalogoGeneral/Estado/EliminarEstado/{{$datos->id}}" method="POST" enctype="multipart/form-data" class="form g-3 needs-validation mt-1 text-start" novalidate>
              @else
                <form action="/CatalogoGeneral/Ciudad/EliminarCiudad/{{$datos->id}}" method="POST" enctype="multipart/form-data" class="form g-3 needs-validation mt-1 text-start" novalidate>
              @endif

              @csrf
              <div class="modal-body text-center mb-3">
                  <h4 class="text-break">¿Esta seguro que eliminar el {{$filtro}} "{{$datos->nombre}}"?</h4>
                  <span class="fs-8 text-secondary">(Concidere que si la ciudad depende de otro dato, no se podrá eliminar, a menos que remueva la dependencia)</span>
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
            <h5 class="modal-title" id="exampleModalLabel">Alta de Estado</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          @if($filtro == "Estado")
              <form action="/CatalogoGeneral/Estado/NuevoEstado" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
          @else
              <form action="/CatalogoGeneral/Ciudad/NuevaCiudad" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
          @endif
            @csrf
            <div class="modal-body">
              <div class="container">
                <div class="row mb-4">
                    <div class="form-outline">
                        <label class="form-label" for="form8Example4">Nombre de {{$filtro}}</label>
                        <input type="text"  class="form-control text" name="nombre" id="nombre" maxlength="20" required />
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>

                    @if($filtro == "Ciudad")
                      <div class="form-outline mt-2">
                          <label class="form-label" for="form8Example4">Estados</label>
                          <select class="form-select" name="idestado" required>
                              <option value="" selected>Selecionar...</option>
                              @foreach ($listEstados as $item)
                                  <option value="{{$item->id}}">{{$item->nombre}}</option>
                              @endforeach
                          </select>
                          <div class="valid-feedback">¡Se ve bien!</div>
                          <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                      </div>
                    @endif
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

<script>
  function getComboA(selectObject) {
      var value = selectObject.value;  
   

      let formulario = document.getElementById('filtro-form');
      formulario.submit();
  }
</script>
<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>

@endsection