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
            echo 'Toast.fire({ icon: "warning",title: "¡Saldo Insuficiente!", text: "No se a efectuado la acción, necesita un mayor saldo para hacer el cargo revise el saldo o consulte con tesoreria"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('warningCuenta'))
  @php
          echo '<script language="JavaScript">';
          echo 'const Toast = Swal.mixin({';
          echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
          echo 'didOpen: (toast) => {';
          echo '  toast.onmouseenter = Swal.stopTimer;';
          echo '  toast.onmouseleave = Swal.resumeTimer;}});';
          echo 'Toast.fire({ icon: "warning",title: "¡No se selecciono bien la cuenta!", text: "Asegurese de llenar todos los campos para una transferencia exitosa"});';
          echo '</script>'; 
  @endphp
@elseif($mensaje = Session::get('arqueroExistente'))
  @php
          echo '<script language="JavaScript">';
          echo 'const Toast = Swal.mixin({';
          echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
          echo 'didOpen: (toast) => {';
          echo '  toast.onmouseenter = Swal.stopTimer;';
          echo '  toast.onmouseleave = Swal.resumeTimer;}});';
          echo 'Toast.fire({ icon: "warning",title: "¡Ya se realizo un arqueo previamente!", text: "Verifique la información o intente despues"});';
          echo '</script>'; 
  @endphp
@endif

@foreach($obtenerInfo as $info)
  @php($id = $info->id)
  @php($status = $info->status)
  @php($saldo_inicial = $info->saldo_inicial)
  @php($saldo_actual = $info->saldo_actual)
  @php($nombre = $info->nombre)
  @php($empresa = $info->nombre_empresa)
  @php($pertenencia = $info->nombre_sucursal)
@endforeach

@if(!$validaAutorizar->isEmpty())
    @foreach($validaAutorizar as $item)
      @if($item->estado != "Autorizado")
        @php($validaAut = "no")
      @else
        @php($validaAut = "si")
      @endif
    @endforeach
@else
    @php($validaAut = "si")
@endif

<div style="z-index:4;position: fixed;top:10px;right:15px;">
  @if($validaAut == "si")
    <button type="button" class="btn border-0 push2 text-secondary fs-4" id="liveToastBtn"><i class="fa-regular fa-bell"></i></button>
  @else
    <button type="button" class="btn border-0 push2 text-secondary fs-4 position-relative p-0 m-0" id="liveToastBtn">
        <i class="fa-solid fa-bell"></i>
        <span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle">
          <span class="visually-hidden">New alerts</span>
        </span>
    </button>
  @endif
</div>

<div class="container-fluid format_page">
   <livewire:historial 
    :tipo="$tipo" 
    :recordId="$id" 
    :empresaId="$empresaid"

/>
</div>


{{--------------------------- Modal solicitud ----------------------}}
<div class="modal fade" id="modalSolicitud" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-dark" id="exampleModalLabel">Solicitud de Arqueo con otra Fecha</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/Tesoreria/Movimientos/SolicitudArqueo/{{$id}}" method="POST" class="form g-3 needs-validation mt-1 text-start" novalidate>
        @csrf
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12">
              <div class="row mb-3">
                <div class="form-outline">
                  <label class="form-label" for="descripcion">Descripción de la solicitud</label>
                  <textarea class="form-control fs-8 text" name="descripcion" id="descripcion" rows="4" required></textarea>
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
       <div class="modal-footer">
              <button type="button" class="btn btn-outline-danger  rounded-3 fs-9" data-bs-dismiss="modal">Cerrar</button>
              <button type="submit" class="btn btn-primary rounded-3 fs-9"><i class="fa-solid fa-check"></i> Aplicar</button>
            </div>
      </form>
    </div>
  </div>
</div>

{{--------------------------- Notificación  ----------------------}}
@if($validaAut != "si")
  <div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast animate___animated animate___backInRight" role="alert" aria-live="assertive" aria-atomic="true" style="width: 60vh!important">
      <div class="toast-header text-start">
        <img src="{{ asset('Images/ummining_ico.png') }}" width="20px" class="rounded me-2" alt="valemil">
        <strong class="me-auto">Valemil</strong>
        <small><i class="fa-solid fa-triangle-exclamation"></i> Arqueo Pendiente</small>
        <button type="button" class="btn-close push border-0" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body text-start">
        El Arqueo aún <b class="text-danger">no se a autorizado</b>, espere a que sea aprobado por "Tesorería" para realizar cualquier movimiento.
      </div>
    </div>
  </div>
@endif

{{--------------------------- Modal gastos ----------------------}}
<div class="text-start modal fade" id="modalGasto" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      
      <form action="/Tesoreria/Movimientos/Responsable/AplicarGasto/{{$id}}" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation formulario1" novalidate>
          @csrf
            <div class="modal-header">
                <h5>Alta de Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
              <div class="row">
                <input type="text" value="{{$pertenencia}}" name="sucursal" hidden>
                <input type="text" value="{{$nombre}}" name="nombre" hidden>
                <input type="text" value="{{$mesNombre}}" name="mes" hidden>
                <input type="text" value="Cajas" name="tipo" hidden>
                <div class="col">
                  <div class="row">
                      <div class="col">
                          <div class="form-outline">
                              <label class="form-label"for="form8Example4">Gastos</label>
                              <select name="gasto" id="gasto" class=" form-select text" required>
                              <option value="" selected>Selecciona ...</option>
                              @foreach($varGastos as $gastos)
                              <option value="{{$gastos->id}}"><b>{{$gastos->id}} - </b> {{$gastos->nombre}}</option>
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

                      <div class="col-md-4">
                          <div class="form-outline">
                              <label class="form-label" for="form8Example4">Importe</label>
                              <input type="decimal"  class="form-control" name="saldo" id="saldo"   maxlength="10" required />
                              <div class="valid-feedback">
                              ¡Se ve bien!
                              </div>
                              <div class="invalid-feedback">
                              Por favor, completa la información requerida.
                              </div>
                          </div>
                      </div>
                  
                  </div>

                  <div class="row mt-3">
                      <div class="col">
                        <div class="form-floating">
                            <textarea class="form-control text" placeholder="Escribe un comentario..." name="descripcion" id="floatingTextarea2" style="height: 50px" required></textarea>
                            <label for="floatingTextarea2" style="margin-top:-10px;margin-left:10px;">Descripcion</label>
                        </div>
                    </div>
                  </div>
                </div>

                <div class="col text-center">
                  <label for="file1" class="drop-container22 mt-2 p-3 text-center">
                    <h6>Subir Evidencia <b class="fs-10">(.pdf)</b></h6>
                    <input type="file" name="evidencia"  class="validaPDF" required>
                    <div class="valid-feedback">
                        ¡Se ve bien!
                        </div>
                        <div class="invalid-feedback">
                        Por favor, completa la información requerida.
                        </div>
                  </label>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-outline-danger  rounded-3 fs-9" data-bs-dismiss="modal">Cerrar</button>
              <button type="submit" class="btn btn-primary rounded-3 fs-9"><i class="fa-solid fa-check"></i> Aplicar</button>
            </div>
      </form>
      
    </div>
  </div>
</div>

{{--------------------------- Modal entrega ----------------------}}
<div class="modal fade" id="modalEntrega" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-dark" id="exampleModalLabel">Entrega de Saldo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/Tesoreria/Movimientos/EntregasEfectivo/{{$id}}" method="POST" class="form g-3 needs-validation mt-1 text-start" novalidate>
        @csrf
        <div class="modal-body">
          <div class="row">
            <div class="col-md-7 text-end">
              <h6 class="text-dark">Cuenta "{{$nombre}}" entrega &nbsp;&nbsp;<i class="mt-1 fs-5 text-orange fa-solid fa-arrow-right"></i></h6>
              <span class="text-break fs-8">Saldo inicial de <b class="text-dark">$ {{$saldo_inicial}}</b> y un saldo actual de <b class="text-success fs-8">$ {{$saldo_actual}}</b></span>
              <h6 class="text-dark">a</h6>
            </div>

            <div class="col-md-4">
                <div class="row mb-3">
                  <div class="form-outline">
                      <input type="number"  class="form-control fs-8" placeholder="Cantidad a Transferir" name="saldo_entregar" id="saldo_entregar"  maxlength="10" required />
                      <div class="valid-feedback">
                      ¡Se ve bien!
                      </div>
                      <div class="invalid-feedback">
                      Por favor, completa la información requerida.
                      </div>
                  </div>
                </div>

                <div class="row cuentas">
                  <div class="form-outline">
                    <select class="form-select fs-8" name="cuenta">
                      <option value="">Selecciona Cuenta...</option>
                      @foreach($obtenerCuentas as $cuen)
                        <option value="{{$cuen->id}}">{{$cuen->id}} {{$cuen->descripcion}}</option>
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
            </div>
          </div>

          <hr> 

          <div class="row mt-4 mb-2">
            <h6 class="text-center mb-3 fs-8">Información de Movimiento</h6>

            <center class="col">
              <div class="form-floating">
                <textarea class="form-control fs-8 text" placeholder="Leave a comment here" name="descripcion" id="floatingTextarea2" maxlength="250" style="height: 100px;width: 80%" required></textarea>
                  <div class="valid-feedback">
                  ¡Se ve bien!
                  </div>
                  <div class="invalid-feedback">
                  Por favor, completa la información requerida.
                  </div>
              </div>
            </center>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-danger fs-8 rounded-5" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
          <button type="submit" class="btn btn-success fs-8 rounded-5"><i class="fa-solid fa-check"></i> Confirmar</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{--------------------------- Modal arqueos realizados ----------------------}}
<div class="offcanvas offcanvas-top" tabindex="-1" id="ModalArqueos" aria-labelledby="offcanvasTopLabel" style="height: 80vh;">
  <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasTopLabel">Arqueos Realizados</h5>
      <button type="button" class="btn-close push border-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  
  <div class="offcanvas-body" style="margin-top:-50px!important;">
      <div class="rounded border-0">
          <div class="table-responsive pad-table" id="mydatatable-container"> 
          
            <table class="table table-hover table-striped" id="dic">
              <thead>
                <tr class="table-light text-secondary"> 
                    <th class="text-center fw-bold ">Estado</th>
                    <th class="text-center fw-bold ">Descargar Arqueo</th>
                    <th class="text-center fw-bold ">Fecha</th>
                    <th class="text-center fw-bold ">Saldo Inicial</th>
                    <th class="text-center fw-bold ">Saldo Actual</th>
                    <th class="text-center fw-bold ">Traspasos</th>
                    <th class="text-center fw-bold ">Cobranza</th>
                    <th class="text-center fw-bold ">Ingresos</th>
                    <th class="text-center fw-bold ">Desembolsos</th>
                    <th class="text-center fw-bold ">Gastos</th>
                    <th class="text-center fw-bold ">Entregas</th>
                    <th class="text-center fw-bold ">Egresos</th>
                    <th class="text-center fw-bold ">Saldo en Caja</th>
                    <th class="text-center fw-bold ">Relación de Efectivo</th>
                    <th class="text-center fw-bold ">Diferencia</th>
                    <th class="text-center fw-bold ">Comentario</th>
                    <th class="text-center fw-bold ">Creación</th>
                    <th class="text-center fw-bold ">Actualización</th>
                    
                </tr>
              </thead>
    
              <tbody>
                  @foreach($varaqueos as $datos) 
                      <tr>
                        <td class="table-light text-secondary text-truncate"><h6>
                          @if($datos->estado == "Creado")
                            <button class="btn btn-primary rounded-5 fs-9 pointer_none"> Creado </button>
                          @elseif($datos->estado == "Autorizado")
                            <button class="btn btn-success rounded-5 fs-9 pointer_none"> Autorizado </button>
                          @elseif($datos->estado == "En Espera")
                            <button class="btn btn-baseColor rounded-5 fs-9 pointer_none"> En Espera </button>
                          @elseif($datos->estado == "Cancelado")
                            <button class="btn btn-danger rounded-5 fs-9 pointer_none"> Cancelado </button>
                          @endif</h6>
                        </td>

                       <td class="table-light text-secondary text-truncate">
                        <h6><a href="/Tesoreria/Movimientos/Exportar/ArqueoCaja/{{$tipo}}/{{$empresaid}}/{{$id}}/{{$datos->fecha}}" class="btn text-primary  border-0 m-0 p-0 rounded-3 fs-9" type="button"><i class="fa-solid fa-download"></i>  Descargar</a></h6> 
                      </td>

                        <td class="table-secondary text-secondary text-center text-truncate">{{$datos->fecha}}</td>
                        <td class="table-light text-secondary fw-bold text-truncate">{{$datos->saldo_inicial}}</td>
                        <td class="table-light text-secondary text-center text-truncate">{{$datos->saldo_actual}}</td>
                        <td class="bg-0 text-secondary text-center text-truncate">{{$datos->total_traspasos}}</td>
                        <td class="bg-0 text-secondary text-start text-truncate">{{$datos->total_cobranza}}</td>
                        <td class="table-light text-success text-start fw-bold text-truncate">{{$datos->total_ingresos}}</td>
                        <td class="bg-0 text-secondary text-start text-truncate">{{$datos->total_desembolsos}}</td>
                        <td class="bg-0 text-secondary text-start text-truncate">{{$datos->total_gastos}}</td>
                        <td class="bg-0 text-secondary text-start text-truncate">{{$datos->total_entregas}}</td>
                        <td class="table-light text-danger text-start fw-bold text-truncate">{{$datos->total_egresos}}</td>
                        <td class="table-light text-secondary text-start fw-bold text-truncate">{{$datos->total_calculado}}</td>
                        <td class="table-light text-success text-start fw-bold text-truncate">{{$datos->total_ingresado}}</td>
                        
                        <td class="table-light text-secondary fw-bold text-truncate">
                            @if($datos->diferencia == 0)
                                <b class="text-success fw-normal">{{$datos->diferencia}}</b>
                            @else
                                <b class="text-danger fw-normal">{{$datos->diferencia}}</b>
                            @endif
                        </td>

                        <td class="table-light text-start text-secondary text-truncate">
                          <p class="btn border-0 text-truncate fs-9 m-0" style="max-width: 150px;" data-bs-toggle="modal" data-bs-target="#Modalcomentario{{$datos->id}}">{{$datos->comentario}}</p>
    
                          <!-- Modal -->
                          <div class="modal fade" id="Modalcomentario{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                              <div class="modal-dialog">
                              <div class="modal-content">
                                  <div class="modal-body">
                                      <div class="badge">
                                        <h6 class="text-dark mb-2"><i class="text-primary fa-regular fa-comment"></i> Información de Movimiento</h6>
                                        <p class="text-dark text-wrap fw-normal" style="width: 100%;text-align: justify!important;font-size:12px!important;">
                                          <b class="text-secondary">COMENTARIO: </b> {{$datos->comentario}}
                                        </p>
                                    </div>
                                  </div>
                              </div>
                              </div>
                          </div>
                        </td>
                        <td class="table-light text-secondary text-start">{{$datos->created_by}} - {{$datos->created_at}}</td>
                        <td class="table-light text-secondary text-start">{{$datos->updated_by}} - {{$datos->updated_at}}</td>
                      </tr>
                  @endforeach
              </tbody>
           </table>
          </div>  
      </div>
  </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
<script src="{{ asset('js/notification.js') }}"></script>

<script>
  var table = $('#dic').DataTable( {
     "dom": 'B<"float-left"l><"float-right"f>t<"float-left"i><"float-right"p><"clearfix">',
     responsive: true,
     scrollY: 280,
     scrollX: true,
     "language": {
         "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
     },
     
     
 } );
    
    
    
</script>


@endsection