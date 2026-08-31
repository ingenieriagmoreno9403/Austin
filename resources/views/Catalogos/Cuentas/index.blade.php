@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('warningSaldo'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡Saldo Insuficiente!", text: "No se a efectuado la acción, necesita un mayor saldo para hacer la transferencia"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('warningFecha'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "info",title: "¡No existen coincidencias!", text: "Pruebe como otro rango de fechas"});';
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
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Cuentas</h2>
                        <p class="text-muted mb-0">Gestión y control de cuentas, con visualización de detalle</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if ($permisos1 == 'nueva_cuenta')
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            <i class="fa-solid fa-plus"></i> Nueva Cuenta
                        </button>
                    @else
                        <button class="btn btn-baseColor" disabled type="button">
                            <i class="fa-solid fa-plus"></i> Nueva Cuenta
                        </button>
                    @endif

                    @if ($permisos6 == 'exportar_cuenta')
                        <a class="btn btn-baseColor-light" href="/CatalogoGeneral/Cuentas/Exportar">
                            <i class="fa-solid fa-file-excel"></i> Exportar
                        </a>
                    @else
                        <button class="btn btn-baseColor-light" disabled type="button">
                            <i class="fa-solid fa-file-excel"></i> Exportar
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
                  <th class="text-center fw-normal text-truncate">Fecha Alta</th>
                  <th class="text-center fw-normal text-truncate">Tipo</th>
                  <th class="text-center fw-normal text-truncate">Saldo Inicial</th>
                  <th class="text-center fw-normal text-truncate">Saldo Actual</th>
                  <th class="text-center fw-normal text-truncate">Razón Social</th>
                  <th class="text-center fw-normal text-truncate">No. de Cuenta</th>
                  <th class="text-center fw-normal text-truncate">Herramientas</th>
                  <th class="text-center fw-normal text-truncate">Creación</th>
                  <th class="text-center fw-normal text-truncate">Actualización</th>
                  
              </tr>
            </thead>
  
            <tbody>
              @foreach($obtenerCuentas as $datos) 
                <tr>
                  <td class="text-truncate">{{$datos->descripcion}}</td>
                  <td class="text-truncate">
                    @if($datos->status == "A")
                      <span class="badge badge-success-dark fs-9">Activa</span>
                    @endif
                    @if($datos->status == "C")
                      <span class="badge badge-danger-dark fs-9">Cancelada</span>
                    @endif
                  </td>
                  <td class="text-truncate">{{$datos->fecha_alta}}</td>
                  <td class="text-truncate">{{$datos->tipo}}</td>
                  <td class="text-truncate">{{$datos->saldo_inicial}}</td>
                  <td class="text-truncate">{{$datos->saldo_actual}}</td>
                  <td class="text-truncate">{{$datos->nombre_empresa}}</td>
                  <td class="text-truncate">
                    @if($datos->id_cuenta == 0)
                    @else
                      {{$datos->id_cuenta}}
                    @endif
                  </td>

                  <td class="text-truncate">
                    {{-- editar --}}
                    @if($permisos4 == "editar_cuenta")
                      @if($datos->status != "C")
                        <button class="btn btn-primary border-0 m-0" type="button" data-bs-toggle="modal" data-bs-target="#modalEditar{{$datos->id}}">
                          <i class="fa-solid fa-pen fs-8"></i>
                        </button>
                      @else
                        <button class="btn btn-primary border-0 m-0" type="button" disabled>
                          <i class="fa-solid fa-pen fs-8"></i>
                        </button>
                      @endif
                    @else
                        <button class="btn btn-primary border-0 m-0" type="button" disabled>
                          <i class="fa-solid fa-pen fs-8"></i>
                        </button>
                    @endif
               
                  {{-- cancelar --}}
                    @if($permisos2 == "cancelar_cuenta")
                      @if($datos->status != "C")
                        <button class="btn btn-danger border-0 m-0" type="button" data-bs-toggle="modal" data-bs-target="#ModalCancel{{$datos->id}}">
                          <i class="fa-solid fa-ban fs-8"></i> 
                        </button>
                      @else
                        <button class="btn btn-danger border-0 m-0" type="button" disabled>
                          <i class="fa-solid fa-ban fs-8"></i>
                        </button>
                      @endif
                    @else
                      <button class="btn btn-danger border-0 m-0" type="button" disabled>
                        <i class="fa-solid fa-ban fs-8"></i>
                      </button>
                    @endif
                  </td>

                  <td class="text-truncate">{{$datos->created_by}} - {{$datos->created_at}}</td>
                  <td class="text-truncate">{{$datos->updated_by}} - {{$datos->updated_at}}</td>
                </tr>
              @endforeach
            </tbody>
        </table>
      </div>  
    </div>

    <!-- Modal Nueva Cuenta -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title" id="exampleModalLabel">Alta de Cuenta</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <form action="/CatalogoGeneral/Cuentas/NuevaCuenta" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
            @csrf
            <div class="modal-body">
              <div class="container">

                <div class="row mb-4">
                  <div class="col">
                      <div class="form-outline">
                      <label class="form-label" for="form8Example4">Nombre de Cuenta</label>
                          <input type="text"  class="form-control text" name="nombre" id="nombre" maxlength="50" required />
                          <div class="valid-feedback">
                          ¡Se ve bien!
                          </div>
                          <div class="invalid-feedback">
                          Por favor, completa la información requerida.
                          </div>
                      </div>
                  </div>

                  <div class="col">
                    <div class="form-outline">
                    <label class="form-label" for="form8Example4">Numero de Cuenta</label>
                        <input type="text"  class="form-control text" name="no_cuenta" id="no_cuenta" maxlength="18"/>
                        <div class="valid-feedback">
                        ¡Se ve bien!
                        </div>
                        <div class="invalid-feedback">
                        Por favor, completa la información requerida.
                        </div>
                    </div>
                  </div>
                
                  <div class="col">
                    <div class="form-outline">
                    <label class="form-label" for="form8Example4">Saldo Inicial</label>
                        <input type="number"  class="form-control text" name="saldo_inicial" id="saldo_inicial"  required />
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
                    <div class="col-md-4 col-12 mb-2">
                      <div class="form-outline">
                          <label class="form-label" for="form8Example4">Fecha de Alta</label>
                          <input type="date"  class="form-control" name="fecha_alta" id="fecha_alta" required />
                          <div class="valid-feedback">
                          ¡Se ve bien!
                          </div>
                          <div class="invalid-feedback">
                          Por favor, completa la información requerida.
                          </div>
                      </div>
                    </div>

                    <div class="col-md-4 col-12 mb-2">
                      <div class="form-outline">
                        <label class="form-label" for="form8Example4">Razón Social</label>
                          <select class="form-select" name="empresa" id="empresa" required>
                              <option value="">Seleccionar...</option>
                              @foreach($obtenerempresas as $razonSocial)
                                <option value="{{$razonSocial->id}}">{{$razonSocial->nombre_empresa}}</option>
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

                    <div class="col-md-4 col-12 mb-2">
                      <div class="form-outline">
                            <label class="form-label" for="form8Example4">Tipo de Cuenta</label>
                            <select class="form-select" name="tipo" id="tipo" required>
                                <option value="BANCO">BANCO</option>
                                <option value="EFECTIVO">EFECTIVO</option>
                                <option value="HIBRIDA">HIBRIDA</option>
                                <option value="PUENTE">PUENTE</option>
                            </select>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
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

    @foreach($obtenerCuentas as $datos) 
       <!-- Modal Editar Cuenta -->
       <div class="modal fade" id="modalEditar{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-dark" id="exampleModalLabel">Editar Cuenta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="/CatalogoGeneral/Cuentas/EditarCuenta/{{$datos->id}}" method="POST" enctype="multipart/form-data" class="form g-3 needs-validation mt-1 text-start" novalidate>
              @csrf
                <div class="modal-body p-4 pt-0">
                  <div class="row mb-2">
                      <div class="col">
                          <div class="form-outline">
                          <label class="form-label" for="form8Example4">Nombre de Cuenta</label>
                              <input type="text"  class="form-control text" name="nombre" id="nombre" maxlength="50" value="{{$datos->descripcion}}" required />
                              <div class="valid-feedback">
                              ¡Se ve bien!
                              </div>
                              <div class="invalid-feedback">
                              Por favor, completa la información requerida.
                              </div>
                          </div>
                      </div>
    
                      <div class="col">
                        <div class="form-outline">
                          <label class="form-label" for="form8Example4">Numero de Cuenta</label>
                            <input type="text"  class="form-control text" name="no_cuenta" id="no_cuenta" maxlength="18" value="{{$datos->id_cuenta}}"/>
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
                      <div class="col">
                        <div class="form-outline">
                        <label class="form-label" for="form8Example4">Fecha de Alta</label>
                            <input type="date"  class="form-control" name="fecha_alta" id="fecha_alta" value="{{$datos->fecha_alta}}" required />
                            <div class="valid-feedback">
                            ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                            Por favor, completa la información requerida.
                            </div>
                        </div>
                      </div>
                      
  
                      <div class="col">
                        <div class="form-outline">
                        <label class="form-label" for="form8Example4">Razón Social</label>
                            <select class="form-select" name="empresa" id="empresa" required>
                                @foreach($obtenerempresas as $razonSocial)
                                  @if($datos->id_empresa == $razonSocial->id)
                                  <option value="{{$razonSocial->id}}" selected>{{$razonSocial->nombre_empresa}}</option>
                                  @else
                                    <option value="{{$razonSocial->id}}">{{$razonSocial->nombre_empresa}}</option>
                                  @endif
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
  
                      <div class="col">
                        <div class="form-outline">
                        <label class="form-label" for="form8Example4">Tipo de Cuenta</label>
                            <select class="form-select" name="tipo" id="tipo" required>
                                <option value="{{$datos->tipo}}" selected>{{$datos->tipo}}</option>
                                <option value="BANCO">BANCO</option>
                                <option value="EFECTIVO">EFECTIVO</option>
                                <option value="HIBRIDA">HIBRIDA</option>
                                <option value="PUENTE">PUENTE</option>
                            </select>
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

       <!-- Modal Cancelar Cuenta -->
       <div class="modal fade" id="ModalCancel{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header border-0">
              <h5 class="modal-title" id="exampleModalLabel">Cancelación de Cuenta</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="/CatalogoGeneral/Cuentas/CancelarCuenta/{{$datos->id}}" method="POST" class="form g-3 needs-validation text-start" novalidate>
              @csrf
              <div class="modal-body">
                <h6 class="text-break">¿Esta seguro de "<i class="text-danger fa-solid fa-ban"></i> Cancelar" la cuenta {{$datos->descripcion}}?</h6>
                <span class="text-break">Cuenta de {{$datos->nombre_empresa}} con saldo inicial de $ {{$datos->saldo_inicial}} y un saldo actual de $ {{$datos->saldo_actual}}</span>

                <div class="row mt-4 mb-2">
                  <div class="col">
                    <div class="form-floating">
                      <textarea class="form-control fs-8 text" placeholder="Leave a comment here" name="descripcion" id="floatingTextarea2" style="height: 100px" maxlength="250" required></textarea>
                        <div class="valid-feedback">
                        ¡Se ve bien!
                        </div>
                        <div class="invalid-feedback">
                        Por favor, completa la información requerida.
                        </div>
                      <label for="floatingTextarea2">Descripción</label>
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
    @endforeach
</div>

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection