@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="social-bar">
  <a href="/Tesoreria/Movimientos/Manejo/{{$tipo}}" class="iconn icon-user-tie btn border-0">&nbsp;&nbsp;<b><i class="fa-solid fa-chevron-left"></i></b>&nbsp;&nbsp;&nbsp; </a>
</div>


<div class="container-fluid format_page bg-body">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Arqueos de {{ $tipo }}</h2>
                        <p class="text-muted mb-0">Gestión y control de arqueos, con visualización de detalle</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-primary rounded-5 fs-9" type="button">Creado</button>
                <button class="btn btn-outline-warning rounded-5 fs-9" type="button">En Espera</button>
                <button class="btn btn-outline-success rounded-5 fs-9" type="button">Autorizado</button>
                <button class="btn btn-outline-danger rounded-5 fs-9" type="button">Cancelado</button>
            </div>
        </div>
    </div>

    <div class="row mb-3">
          <div class="col-md-4 col-12">
            <div class="rounded-3 m-1 p-3 pt-1 card-tools row">
                <div class="fs-6 fw-normal text-center text-tools d-none d-md-block"><i
                        class="fa-solid fa-filter"></i> Filtros</div>
  
                <div class="row">
                    <form action="/Tesoreria/Movimientos/ControlArqueos/{{$tipo}}/{{$empresaid}}/{{$id}}" method="" enctype="multipart/form-data"
                        class="text-start form needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-4 col-6">
                                <div class="form-outline">
                                    <label for="">Fecha Inicio</label>
                                    <input type="date" class="form-control fs-mini" name="fecha_inicio"
                                        value="{{ $fecha_inicio }}" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>
  
                            <div class="col-md-4 col-6">
                                <div class="form-outline">
                                    <label for="">Fecha Final</label>
                                    <input type="date" class="form-control fs-mini" name="fecha_fin"
                                        value="{{ $fecha_fin }}" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>
                           
  
                            <div class="col-md-4 col-12 col-lg-4">
                                <div class="center mt-4">
                                    <div class="btn-group">
                                        <button class="col btn btn-secondary fs-8 push2"><i
                                                class="fa-solid fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
  
                {{-- LEYENDA DE FILTROS
                <div class="fs-8 mt-1 fw-normal text-end text-tools pt-0 p-2" style="z-index: 9999">
                    @php($fecha_inicio = new DateTime($fecha_inicio))
                    @php($fecha_fin = new DateTime($fecha_fin))
                    @if (!$reporte_gastos->isEmpty())
                        @php($list = 0)
                        @foreach ($reporte_gastos as $i)
                            @php($list = $list + 1)
                        @endforeach
                    @endif
  
                    {{ date_format($fecha_inicio, 'd/m/Y') }} a {{ date_format($fecha_fin, 'd/m/Y') }}
                    ({{ $list }} Coincidencias)
                    &nbsp; &nbsp; &nbsp;
                </div>
                --}}
            </div>
        </div>
          <div class="col-md-2 col-12 ">
            <div class="rounded-3 m-1 p-3 pt-1 card-tools">
                <div class="align-items-center">
                    <legend class="text-secondary fs-5">Opciones</legend>
                    <fieldset>
                        <ul style="padding: 0px!important;">
                            <li>
                                <span class="text-center fs-8">
                                    <i class="fs-8 text-primary fa-solid fa-circle-info"></i> Totales
                                </span>
                            </li>
                            <li>
                              <span class="text-center fs-8">
                                  <i class="fa-solid fa-eye fs-8 text-primary"></i> Detalle
                              </span>
                          </li>
                            <li>
                                <span class="text-center fs-8">
                                    <i class="fa-solid fa-download fs-8 text-primary"></i> Descargar
                                </span>
                            </li>
                            <li>
                              <span class="text-center fs-8">
                                  <i class="fa-solid fa-check fs-8 text-success"></i> Autorizar
                              </span>
                          </li>
                          <li>
                            <span class="text-center fs-8">
                                <i class="fa-solid fa-ban fs-8 text-danger"></i> Cancelar
                            </span>
                        </li>
                        <li>
                          <span class="text-center fs-8">
                              <i class="fa-solid fa-rotate-right fs-8 text-primary"></i> Actualizar
                          </span>
                      </li>
                        </ul>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>

    <br>
    
    {{--------------------------- Cuerpo de la tabla----------------------}}
    <center class="bg-body border-0">
        <div class="border-0">
          
        <div class="border-0 bg-body mt-4">
          <div class="table-responsive marginTable float-search" id="mydatatable-container">
            <table class="table table-light table-stripped display" id="tableArqueosCajas"
                style="width:100%!important;">
                  <thead>
                    <tr class="table-light text-secondary">
                        <th class="text-center fw-bold ">Estado</th> 

                        <th class="text-center fw-bold ">Información detallada arqueo</th>
                        <th class="text-center fw-bold ">Información general arqueo</th>
                        <th class="text-center fw-bold ">Descargar</th>
                        <th class="text-center fw-bold ">Fecha</th>
                        <th class="text-center fw-bold ">Comentario</th>
                        <th class="text-center fw-bold ">Saldo Inicial</th>
                        <th class="text-center fw-bold ">Saldo Actual</th>
                        {{-- <th class="text-center fw-bold ">Traspasos</th>-
                        <th class="text-center fw-bold ">Cobranza</th>-
                        <th class="text-center fw-bold ">Ingresos</th>-
                        <th class="text-center fw-bold ">Desembolsos</th>-
                        <th class="text-center fw-bold ">Gastos</th>-
                        <th class="text-center fw-bold ">Entregas</th>-
                        <th class="text-center fw-bold ">Egresos</th>-
                        <th class="text-center fw-bold ">Saldo en Caja</th>
                        <th class="text-center fw-bold ">Relación de Efectivo</th>
                        <th class="text-center fw-bold ">Diferencia</th> --}}
                        <th class="text-center fw-bold ">Creación</th>
                        <th class="text-center fw-bold ">Actualización</th>
                        <th class="text-center fw-bold ">Herramientas</th>
                        
                    </tr>
                  </thead>
        
                  <tbody>
                      @foreach($varaqueos as $datos) 
                          <tr>
                                                       
                           <td class="table-light text-secondary">
                            <h6>
                              @if($datos->estado == "Creado")
                                <button class="btn bg_primary text-primary rounded-5 fs-10 pointer_none text-truncate"><i class="fa-solid fa-check"></i> Creado </button>
                              @elseif($datos->estado == "Autorizado")
                                <button class="btn bg_success text-success rounded-5 fs-10 pointer_none text-truncate"><i class="fa-solid fa-check"></i> Autorizado </button>
                              @elseif($datos->estado == "En Espera") 
                                <button class="btn bg_orange text-orange rounded-5 fs-10 pointer_none text-truncate"><i class="fa-solid fa-pause"></i> En Espera </button>
                              @elseif($datos->estado == "Cancelado") 
                                <button class="btn bg_danger text-danger rounded-5 fs-10 pointer_none text-truncate"><i class="fa-solid fa-ban"></i> Cancelado </button>
                              @elseif($datos->estado == "Editando")
                                <button class="btn bg_primary text-primary rounded-5 fs-10 pointer_none text-truncate"><i class="fa-solid fa-pen"></i> Editando </button>
                              @endif
                            </h6>
                          </td>
                           {{-- <td class="bg-0 text-secondary text-start">
                              <h6>
                                @if($datos->estado == "Autorizado")
                                  <button class="btn btn-success text-light rounded-3 fs-10" type="button" disabled><i class="fa-solid fa-check"></i>  Autorizar</button>
                               
                                  @elseif($datos->estado == "Cancelado")
                                    <a href="/Tesoreria/Movimientos/ControlArqueos/autorizarArqueo/{{$datos->id}}/{{$datos->id_caja}}/{{$datos->total_ingresado}}" class="btn btn-success text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-check"></i>  Autorizar</a>
                                  @elseif($datos->estado == "Editando")
                                    <a href="/Tesoreria/Movimientos/ControlArqueos/autorizarArqueo/{{$datos->id}}/{{$datos->id_caja}}/{{$datos->total_ingresado}}" class="btn btn-success text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-check"></i>  Autorizar</a>
                                  @else
                                      <a href="/Tesoreria/Movimientos/ControlArqueos/autorizarArqueo/{{$datos->id}}/{{$datos->id_caja}}/{{$datos->total_ingresado}}" class="btn btn-success text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-check"></i>  Autorizar</a>
                                @endif
                              </h6>
                           </td>
                           
                            <td class="bg-0 text-secondary text-start">
                                <h6>
                                    @if($datos->estado == "Autorizado")
                                        <button class="btn btn-danger text-light rounded-3 fs-10" type="button" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                            <i class="fa-solid fa-ban"></i> Cancelar
                                        </button>

                                    @elseif($datos->estado == "Cancelado")
                                        <button class="btn btn-danger text-light rounded-3 fs-10" type="button" disabled>
                                            <i class="fa-solid fa-ban"></i> Cancelar
                                        </button>
                                    @elseif($datos->estado == "Editando")
                                        <button class="btn btn-danger text-light rounded-3 fs-10" type="button" data-bs-toggle="modal"  data-bs-target="#cancelModal">
                                            <i class="fa-solid fa-ban"></i> Cancelar
                                        </button>
                                    @else
                                        <button class="btn btn-danger text-light rounded-3 fs-10" type="button" data-bs-toggle="modal"  data-bs-target="#cancelModal">
                                            <i class="fa-solid fa-ban"></i> Cancelar
                                        </button>
                                    @endif
                                </h6>
                            </td>
                              
                             <td class="bg-0 text-secondary text-start">
                              <h6>
                                @if($datos->estado == "Autorizado")
                                  <a href="/Tesoreria/Movimientos/ControlArqueos/editarArqueo/{{$datos->id}}" class="btn btn-primary text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-rotate-right"></i>  Actualizar</a>
                                  @elseif($datos->estado == "Cancelado")
                                    <a href="/Tesoreria/Movimientos/ControlArqueos/editarArqueo/{{$datos->id}}" class="btn btn-primary text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-rotate-right"></i>  Actualizar</a>
                                  @elseif($datos->estado == "Editando")
                                    <button disabled class="btn btn-primary text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-rotate-right"></i>  Actualizar Efectivo</button>
                                  @else
                                      <a href="/Tesoreria/Movimientos/ControlArqueos/editarArqueo/{{$datos->id}}" class="btn btn-primary text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-rotate-right"></i>  Actualizar</a>
                                @endif
                              </h6>
                           </td>
 --}}

                           <td class="bg-0 text-secondary">
                            <h6><a href="/Tesoreria/Movimientos/DetalleArqueo/{{$tipo}}/{{$empresaid}}/{{$datos->fecha}}/{{$id}}" class="btn text-primary rounded-3 fs-9 border-0 text-truncate" type="button">
                                <i class="fa-solid fa-eye"></i>  Detalle</a>
                            </h6> 
                           </td>
                           <td class="table-light text-secondary text-center">
                            <h6>
                              <button class="btn text-primary rounded-3 fs-9 border-0 text-truncate" type="button" data-bs-toggle="modal" data-bs-target="#detalleArqueo{{ $datos->id }}">
                                  <i class="fa-solid fa-circle-info"></i> Totales
                              </button>
                            </h6>
                            </td>

                           <td class="bg-0 text-secondary">
                            <h6><a href="/Tesoreria/Movimientos/Exportar/ArqueoCaja/{{$tipo}}/{{$empresaid}}/{{$id}}/{{$datos->fecha}}" class="btn text-primary rounded-3 fs-9 border-0 text-truncate" type="button"><i class="fa-solid fa-download"></i>  Descargar</a></h6> 
                           </td>

                           <td class="table-light text-secondary text-center text-truncate fw-bold">{{date('d-m-Y', strtotime($datos->fecha))}}</td>
                         
                           <td class="table-light text-start text-secondary">
                            <p class="btn border-0 text-truncate fs-9 m-0" style="max-width: 150px;" data-bs-toggle="modal" data-bs-target="#Modalcomentario{{$datos->id}}">
                              @if($datos->concepto == "EFECTIVO")
                              <button class="btn btn-primary fs-10 pointer_none rounded-5 p-1 m-0">Actualizar Efectivo</button>
                              @endif 
                              @if($datos->concepto == "CANCELAR")
                                <button class="btn btn-danger fs-10 pointer_none rounded-5 p-1 m-0">Solicitar Cancelación</button>
                              @endif
                              &nbsp;
                              {{$datos->comentario}}</p>
      
                            <!-- Modal -->
                            <div class="modal fade" id="Modalcomentario{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <div class="badge">
                                          <h6 class="text-dark mb-2"><i class="text-primary fa-regular fa-comment"></i> Información de Movimiento</h6>
                                          <p class="text-dark text-wrap fw-normal" style="width: 100%;text-align: justify!important;font-size:12px!important;">
                                            <b class="text-secondary">CONCEPTO: </b> 
                                            @if($datos->concepto == "EFECTIVO")
                                            SE SOLICITA ACTUALIZAR EL EFECTIVO QUE CONTIENE LA CAJA
                                            @elseif($datos->concepto == "CANCELAR")
                                            SE SOLICITA LA CANCELACIÓN DEL ARQUEO
                                            @endif
                                            <hr/>
                                            <b class="text-secondary">COMENTARIO: </b> {{$datos->comentario}}
                                          </p>
                                      </div>
                                    </div>
                                </div>
                                </div>
                            </div>
                           </td>
                            
                            <td class="table-light text-secondary fw-bold">${{number_format($datos->saldo_inicial, 2)}}</td>
                            <td class="table-light text-secondary text-center">${{number_format($datos->saldo_actual, 2)}}</td>
                            
                            {{-- <td class="bg-0 text-secondary text-center">${{number_format($datos->total_traspasos, 2)}}</td>
                            <td class="bg-0 text-secondary text-center">${{number_format($datos->total_cobranza, 2)}}</td>
                            <td class="table-light text-success text-center fw-bold">${{number_format($datos->total_ingresos, 2)}}</td>
                            <td class="bg-0 text-secondary text-center">${{number_format($datos->total_desembolsos, 2)}}</td>
                            <td class="bg-0 text-secondary text-center">${{number_format($datos->total_gastos, 2)}}</td>
                            <td class="bg-0 text-secondary text-center">${{number_format($datos->total_entregas, 2)}}</td>
                            <td class="table-light text-danger text-center fw-bold">${{number_format($datos->total_egresos, 2)}}</td>
                            <td class="table-light text-secondary text-center fw-bold">${{number_format($datos->total_calculado, 2)}}</td>
                            <td class="table-light text-success text-center fw-bold">${{number_format($datos->total_ingresado, 2)}}</td>
                            @if($datos->diferencia == 0)
                              <td class="table-success text-secondary fw-bold">${{number_format($datos->diferencia, 2)}}</td>
                            @else
                              <td class="table-danger text-secondary fw-bold">${{number_format($datos->diferencia, 2)}}</td>
                            @endif --}}
                           
                            <td class="table-light text-secondary text-center">{{$datos->created_by}} - {{ date('d-m-Y H:i:s', strtotime($datos->created_at)) }}</td>
                            <td class="table-light text-secondary text-center">{{$datos->updated_by}} - {{ date('d-m-Y H:i:s', strtotime($datos->updated_at)) }}</td>
                            <td class="table-light text-secondary text-center">
                                @if($datos->estado == "Autorizado")
                                <button class="btn btn-success text-light rounded-3 fs-10" type="button" disabled><i class="fa-solid fa-check "></i></button>
                                @elseif($datos->estado == "Cancelado")
                                  <a href="/Tesoreria/Movimientos/ControlArqueos/autorizarArqueo/{{$datos->id}}/{{$datos->id_caja}}/{{$datos->total_ingresado}}" class="btn btn-success  text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-check "></i></a>
                                @elseif($datos->estado == "Editando")
                                  <a href="/Tesoreria/Movimientos/ControlArqueos/autorizarArqueo/{{$datos->id}}/{{$datos->id_caja}}/{{$datos->total_ingresado}}" class="btn btn-success  text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-check"></i></a>
                                @else
                                  <a href="/Tesoreria/Movimientos/ControlArqueos/autorizarArqueo/{{$datos->id}}/{{$datos->id_caja}}/{{$datos->total_ingresado}}" class="btn btn-success  text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-check"></i></a>
                                @endif
                                @if($datos->estado == "Autorizado")
                                          <button class="btn btn-danger  text-light rounded-3 fs-10" type="button" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                              <i class="fa-solid fa-ban"></i>
                                          </button>
  
                                      @elseif($datos->estado == "Cancelado")
                                          <button class="btn btn-danger text-light rounded-3 fs-10" type="button" disabled>
                                              <i class="fa-solid fa-ban"></i>
                                          </button>
                                      @elseif($datos->estado == "Editando")
                                          <button class="btn btn-danger text-light rounded-3 fs-10" type="button" data-bs-toggle="modal"  data-bs-target="#cancelModal">
                                              <i class="fa-solid fa-ban"></i>
                                          </button>
                                      @else
                                          <button class="btn btn-danger  text-light rounded-3 fs-10" type="button" data-bs-toggle="modal"  data-bs-target="#cancelModal">
                                              <i class="fa-solid fa-ban"></i>
                                          </button>
                                      @endif
                                      @if($datos->estado == "Autorizado")
                                      <a href="/Tesoreria/Movimientos/ControlArqueos/editarArqueo/{{$datos->id}}" class="btn btn-primary text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-rotate-right"></i></a>
                                      @elseif($datos->estado == "Cancelado")
                                        <a href="/Tesoreria/Movimientos/ControlArqueos/editarArqueo/{{$datos->id}}" class="btn btn-primary text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-rotate-right"></i></a>
                                      @elseif($datos->estado == "Editando")
                                        <button disabled class="btn btn-primary text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-rotate-right "></i></button>
                                      @else
                                          <a href="/Tesoreria/Movimientos/ControlArqueos/editarArqueo/{{$datos->id}}" class="btn btn-primary text-light rounded-3 fs-10" type="button"><i class="fa-solid fa-rotate-right "></i></a>
                                    @endif
                              </td>
                            
                          </tr>
                          <!-- Modal -->
                          <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title text-dark fs-5" id="exampleModalLabel">Cancelar arqueo</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <h6 class="text-dark">¿Está seguro que desea cancelar este arqueo?</h6>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger fs-8" data-bs-dismiss="modal">
                                            <i class="fa-solid fa-xmark"></i> Cerrar
                                        </button>
                                        <a href="/Tesoreria/Movimientos/ControlArqueos/cancelarArqueo/{{$datos->id}}" class="btn btn-success fs-8">
                                            <i class="fa-solid fa-check"></i> Aplicar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>    
                        <!-- Modal detalle de arqueo -->
                        <div class="modal fade" id="detalleArqueo{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                          <div class="modal-dialog modal-lg modal-center">
                              <div class="modal-content">
                                  <div class="modal-header">
                                      <h1 class="modal-title text-dark fs-5" id="exampleModalLabel{{$datos->id}}">Detalles Arqueo</h1>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                  </div>
                                  <div class="modal-body text-center">
                                    <div class="shadow p-2 start-center">
                                      <div class="row">
                                          <div class="col-md-4 col-6" style="border-right: 1px solid rgb(170, 170, 170);">
                                              <div class="row text-center mt-1">
                                                  <h6 class="text-danger fs-5">${{number_format($datos->total_gastos, 2)}}</h6>
                                                  <h6 class="fs-6">Gastos</h6>
                                              </div>
                                          </div>
                                          <div class="col-md-4 col-6" style="border-right: 1px solid rgb(170, 170, 170);">
                                              <div class="row text-center mt-1">
                                                  <h6 class="text-primary fs-5">${{number_format($datos->total_cobranza, 2)}}</h6>
                                                  <h6 class="fs-6">Cobranza</h6>
                                              </div>
                                          </div>
                                          <div class="col-md-4 col-6">
                                              <div class="row text-center mt-1">
                                                  <h6 class="text-primary fs-5">${{number_format($datos->total_ingresado, 2)}}</h6>
                                                  <h6 class="fs-6">Relacion de Efectivo</h6>
                                              </div>
                                          </div>
                                          <div class="col-md-4 col-6" style="border-right: 1px solid rgb(170, 170, 170);">
                                              <div class="row text-center mt-1">
                                                  <h6 class="text-success fs-5">@if($datos->diferencia == 0)
                                                    ${{number_format($datos->diferencia, 2)}}
                                                @else
                                                    ${{number_format($datos->diferencia, 2)}}
                                                @endif</h6>
                                                  <h6 class="fs-6">Diferencia</h6>
                                              </div>
                                          </div>
                                          <div class="col-md-4 col-6" style="border-right: 1px solid rgb(170, 170, 170);">
                                              <div class="row text-center mt-1">
                                                  <h6 class="text-primary fs-5">${{number_format($datos->total_calculado, 2)}}</h6>
                                                  <h6 class="fs-6">Saldo en caja</h6>
                                              </div>
                                          </div>
                                          <div class="col-md-4 col-6">
                                            <div class="row text-center mt-1">
                                                <h6 class="text-primary fs-5">${{number_format($datos->total_entregas, 2)}}</h6>
                                                <h6 class="fs-6">Entregas</h6>
                                            </div>
                                        </div>
                                      </div>
                                  </div>
                              
                                  <div class="row bd-body">
                                        <div class="col-md-3 col-12 col-lg-3  p-1  border-0" style="background-color: rgba(238, 238, 238, 0.986)!important;padding-left: 20px!important;">
                                          <div class="row text-center">
                                              <h4 class="text-success">${{number_format($datos->total_ingresos, 2)}}</h4>
                                              <h6 class="fs-7">Ingresos</h6>
                                          </div>
                                      </div>
                                      <div class="col-md-3 col-12 col-lg-3 p-1 border-0 s" style="background-color: rgba(238, 238, 238, 0.986)!important;padding-left: 20px!important;">
                                          <div class="row text-center">
                                              <h4 class="text-danger">${{number_format($datos->total_egresos, 2)}}</h4>
                                              <h6 class="fs-7">Egresos</h6>
                                          </div>
                                      </div>
                                      <div class="col-md-3 col-12 col-lg-3  p-1  border-0" style="background-color: rgba(238, 238, 238, 0.986)!important;padding-left: 20px!important;">
                                          <div class="row text-center">
                                              <h4 class="text-danger">${{number_format($datos->total_desembolsos, 2)}}</h4>
                                              <h6 class="fs-7">Desembolsos</h6>
                                          </div>
                                      </div>
                              
                                      <div class="col-md-3 col-12 col-lg-3 col p-1  border-0 " style="background-color: rgba(238, 238, 238, 0.986)!important;">
                                          <div class="row text-center">
                                              <h4 class="text-danger">${{number_format($datos->total_traspasos, 2)}}</h4>
                                              <h6 class="fs-7">Traspasos</h6>
                                          </div>
                                      </div>
                                  </div>
                                  </div>
                                  <div class="modal-footer">
                                      <button type="button" class="btn btn-danger fs-8" data-bs-dismiss="modal">
                                          <i class="fa-solid fa-xmark"></i> Cerrar
                                      </button>
                                  </div>
                              </div>
                          </div>
                      </div>                       
                        

            
                        @endforeach
                  </tbody>
                  <tfoot>
                    <tr>
                        <th colspan="5"></th>
                        <th>Subtotal</th>
                        <th></th>
                        <th></th>
                        <th colspan="5"></th>
                    </tr>
                    <tr>
                        <th colspan="5"></th>
                        <th>Total</th>
                        <th></th>
                        <th></th>
                        <th colspan="5"></th>
                    </tr>
                </tfoot>
              </table>
            </div>  
        </div>
        </div>
    </center>  

</div>



<script src="{{ asset('js/tableX.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection