@extends('layouts.app')
@section('content')

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    #historialMov tr.table-row-cancelado td {
        background-color: rgba(220, 53, 69, 0.08) !important;
    }

    .historial-stat-cards {
        width: 100%;
    }

    .historial-stat-card {
        background: #fff;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 10px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        min-width: 148px;
        flex: 1 1 148px;
        max-width: 190px;
    }

    .historial-stat-card__inner {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.45rem 0.6rem;
        min-height: 48px;
    }

    .historial-stat-card__icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.75rem;
    }

    .historial-stat-card__content {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
        line-height: 1.15;
    }

    .historial-stat-card__value {
        font-size: 0.82rem;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .historial-stat-card__label {
        font-size: 0.68rem;
        color: #6c757d;
        white-space: nowrap;
    }

    .historial-stat-card__btn {
        padding: 0.15rem 0.45rem;
        font-size: 0.72rem;
        line-height: 1.2;
        border-radius: 6px;
    }

    @media (max-width: 767.98px) {
        .historial-stat-cards {
            justify-content: stretch !important;
        }

        .historial-stat-card {
            max-width: none;
            flex: 1 1 calc(33.333% - 0.5rem);
            min-width: 0;
        }

        .historial-stat-card__inner {
            padding: 0.35rem 0.45rem;
            min-height: 44px;
        }

        .historial-stat-card__icon {
            width: 24px;
            height: 24px;
            font-size: 0.68rem;
        }

        .historial-stat-card__value {
            font-size: 0.72rem;
        }

        .historial-stat-card__label {
            font-size: 0.62rem;
        }
    }
</style>

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

@if(!$obtenerHistorial->isEmpty()) 
  @foreach($obtenerHistorial as $data)
    @php($nombre = $data->cuenta)
    @php($empresa = $data->empresa)
  @endforeach
@else
  @php($nombre = "")
  @php($empresa = "")
@endif

@foreach($obtenerInfo as $info)
  @php($saldo_inicial = $info->saldo_inicial)
@endforeach

<div class="container-fluid format_page">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6 col-12 mb-3">
            <div class="d-flex align-items-center">
                <div class="header-icon me-3">
                    <i class="fas fa-history"></i>
                </div>

                <div>
                    <h2 class="mb-0 text-marino fw-bold">Historial {{$nombre}}</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a class="nav-link pointer text-green fw-bold text-truncate fs-8" href="/Tesoreria/Movimientos"><i class="fa-solid fa-house"></i>  Inicio</a></li>
                            <li class="breadcrumb-item"><a class="nav-link pointer text-green fs-8 text-truncate" href="/Tesoreria/Movimientos/Manejo/{{$tipo}}">{{$tipo}}</a></li>
                            <li class="breadcrumb-item active fs-8 text-truncate" aria-current="page">Historial de {{$nombre}}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-12 header-actions">
            <div class="d-flex flex-wrap justify-content-md-end gap-2 historial-stat-cards">
                @foreach($obtenerInfo as $info)
                    <div class="historial-stat-card">
                        <form action="/Tesoreria/Movimientos/ExportarMovimientos/{{$tipo}}/{{$id}}" class="historial-stat-card__inner mb-0">
                            <input type="text" name="nombre" value="{{$nombre}}" hidden required />
                            <input type="text" name="fecha_inicio" id="exportFechaInicio" value="{{$fecha_inicio}}" hidden required />
                            <input type="text" name="fecha_fin" id="exportFechaFin" value="{{$fecha_fin}}" hidden required />
                            <input type="text" name="empresa" value="{{$empresa}}" hidden required />
                            <input type="text" name="tipo_fecha" id="exportTipoFecha" value="{{$tipo_fecha}}" hidden required />
                            <input type="text" name="tipo_movimiento" id="exportTipoMovimiento" value="{{$tipo_movimiento}}" hidden />

                            <span class="historial-stat-card__icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-file-excel"></i>
                            </span>
                            <div class="historial-stat-card__content">
                                @if($permisos1 == "movimientos_exportar" && !$obtenerHistorial->isEmpty())
                                    <button class="btn btn-success btn-sm historial-stat-card__btn" type="submit">Exportar</button>
                                @else
                                    <button class="btn btn-success btn-sm historial-stat-card__btn" disabled type="button">Exportar</button>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="historial-stat-card">
                        <div class="historial-stat-card__inner">
                            <span class="historial-stat-card__icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-dollar-sign"></i>
                            </span>
                            <div class="historial-stat-card__content">
                                <span class="historial-stat-card__value text-success">${{number_format($info->saldo_inicial,2)}}</span>
                                <span class="historial-stat-card__label">Saldo Inicial</span>
                            </div>
                        </div>
                    </div>

                    <div class="historial-stat-card">
                        <div class="historial-stat-card__inner">
                            <span class="historial-stat-card__icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-wallet"></i>
                            </span>
                            <div class="historial-stat-card__content">
                                <span class="historial-stat-card__value text-primary">${{number_format($info->saldo_actual,2)}}</span>
                                <span class="historial-stat-card__label">Saldo Actual</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>


    <!-- Filtros Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-body p-3">
                    <form action="/Tesoreria/Movimientos/ConsultarMovimientos/{{$tipo}}/{{$id}}" method="" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <input type="hidden" name="fecha_inicio" id="hiddenFechaInicio" value="{{ $fecha_inicio }}">
                        <input type="hidden" name="fecha_fin" id="hiddenFechaFin" value="{{ $fecha_fin }}">
                        <input type="hidden" name="tipo_fecha" id="hiddenTipoFecha" value="{{ $tipo_fecha }}">
                        <div class="row align-items-end">
                            <div class="col-md-2 col-6 mb-2">
                                <label class="form-label fw-semibold fs-9 mb-1">
                                    <i class="fas fa-filter me-1 text-green"></i>Tipo de fecha
                                </label>
                                <select class="form-select form-select-sm modern-input" id="tipoFechaSelector">
                                    <option value="aplicacion" {{ $tipo_fecha === 'aplicacion' ? 'selected' : '' }}>Fecha de aplicación</option>
                                    <option value="movimiento" {{ $tipo_fecha === 'movimiento' ? 'selected' : '' }}>Fecha de movimiento</option>
                                </select>
                            </div>

                            <div class="col-md-2 col-6 mb-2 fecha-aplicacion-group">
                                <label class="form-label fw-semibold fs-9 mb-1">
                                    <i class="fas fa-calendar me-1 text-green"></i>Aplicación inicio
                                </label>
                                <input type="date" class="form-control form-control-sm modern-input"
                                       name="fecha_aplicacion_inicio" id="fechaAplicacionInicio"
                                       value="{{ $fecha_aplicacion_inicio }}" required>
                                <div class="valid-feedback">
                                    <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                                </div>
                            </div>

                            <div class="col-md-2 col-6 mb-2 fecha-aplicacion-group">
                                <label class="form-label fw-semibold fs-9 mb-1">
                                    <i class="fas fa-calendar me-1 text-green"></i>Aplicación fin
                                </label>
                                <input type="date" class="form-control form-control-sm modern-input"
                                       name="fecha_aplicacion_fin" id="fechaAplicacionFin"
                                       value="{{ $fecha_aplicacion_fin }}" required>
                                <div class="valid-feedback">
                                    <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                                </div>
                            </div>

                            <div class="col-md-2 col-6 mb-2 fecha-movimiento-group">
                                <label class="form-label fw-semibold fs-9 mb-1">
                                    <i class="fas fa-calendar me-1 text-green"></i>Movimiento inicio
                                </label>
                                <input type="date" class="form-control form-control-sm modern-input"
                                       name="fecha_movimiento_inicio" id="fechaMovimientoInicio"
                                       value="{{ $fecha_movimiento_inicio }}">
                                <div class="valid-feedback">
                                    <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                                </div>
                            </div>

                            <div class="col-md-2 col-6 mb-2 fecha-movimiento-group">
                                <label class="form-label fw-semibold fs-9 mb-1">
                                    <i class="fas fa-calendar me-1 text-green"></i>Movimiento fin
                                </label>
                                <input type="date" class="form-control form-control-sm modern-input"
                                       name="fecha_movimiento_fin" id="fechaMovimientoFin"
                                       value="{{ $fecha_movimiento_fin }}">
                                <div class="valid-feedback">
                                    <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                                </div>
                            </div>

                            <div class="col-md-2 col-6 mb-2">
                                <label class="form-label fw-semibold fs-9 mb-1">
                                    <i class="fas fa-tag me-1 text-green"></i>Tipo de movimiento
                                </label>
                                <select class="form-select form-select-sm modern-input" name="tipo_movimiento" id="tipoMovimientoSelector">
                                    <option value="TODOS" {{ $tipo_movimiento === 'TODOS' ? 'selected' : '' }}>Todos</option>
                                    @foreach($tipos_movimiento as $movimiento)
                                        <option value="{{ $movimiento }}" {{ $tipo_movimiento === $movimiento ? 'selected' : '' }}>
                                            {{ $movimiento }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2 col-4 mb-2">
                                <button class="btn btn-baseColor btn-sm w-100" type="submit">
                                    <i class="fas fa-search me-1"></i>Buscar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="historialMov">
                <thead>
                    <tr>
                        <th class="text-truncate">Aplicación</th>
                        <th class="text-truncate">Movimiento</th>
                        <th class="text-truncate">Tipo</th>
                        <th class="text-truncate">Ingreso</th>
                        <th class="text-truncate">Egreso</th>
                        <th class="text-truncate">Saldo</th>
                        <th class="text-truncate">Información</th>
                        <th class="text-truncate">Titular</th>
                        <th class="text-truncate">No. Póliza</th>
                        <th class="text-truncate">IVA y Retenciones</th>
                        <th class="text-truncate">Herramientas</th>
                        <th class="text-truncate">Estado</th>
                        <th class="text-truncate">No. Referencia</th>
                        <th class="text-truncate">Usuario</th>
                        <th class="text-truncate">Pertenece</th>
                    </tr>
                </thead>

                <tbody>
                    @php($colortd = "")
                    @foreach($obtenerHistorial as $index => $datos)
                        @if($datos->estado == "C")
                            @php($colortd = "fst-italic text-secondary table-secondary")
                        @else
                            @php($colortd = "table-light")
                        @endif

                        <tr class="boder-sec @if($datos->estado == 'C') table-row-cancelado @endif">
                            <td class="text-truncate" data-order="{{ $datos->fecha_orden ?? $datos->fecha }}">
                                {{ $datos->fecha_aplicacion_formato ?? date('d/m/Y', strtotime($datos->fecha)) }} <br>
                                @if(!empty($datos->hora_aplicacion_formato))
                                    <small class="text-secondary"><i class="fa-solid fa-clock"></i> {{ $datos->hora_movimiento_formato }}</small>
                                @endif
                            </td>

                            <td class="text-truncate" data-order="{{ $datos->created_at_orden ?? $datos->created_at }}">
                                {{ $datos->fecha_movimiento_formato ?? date('d/m/Y', strtotime($datos->created_at)) }} <br>
                                <small class="text-secondary"><i class="fa-solid fa-clock"></i> {{ $datos->hora_movimiento_formato ?? date('H:i:s', strtotime($datos->created_at)) }}</small>
                            </td>

                            <td class="text-truncate {{$colortd}}">
                                            @if($datos->tipo_movimiento == "APERTURA")
                                                <span class="badge bg-success fs-9 fw-bold text-light">APERTURA</span>
                                            @elseif($datos->tipo_movimiento == "CANCELACION")
                                                <span class="badge bg-danger fs-9 fw-bold text-light">{{$datos->tipo_movimiento}}</span>
                                            @elseif($datos->tipo_movimiento == "TRANSFERENCIA")
                                                <span class="badge bg-orange fs-9 fw-bold text-light">{{$datos->tipo_movimiento}}</span>
                                            @elseif($datos->tipo_movimiento == "ENTREGA")
                                                <span class="badge bg-orange fs-9 fw-bold text-light">{{$datos->tipo_movimiento}}</span>
                                            @elseif($datos->tipo_movimiento == "INGRESO" || $datos->tipo_movimiento == "DEVOLUCION")
                                                <span class="badge bg-success fs-9 fw-bold text-light">{{$datos->tipo_movimiento}}</span>
                                            @elseif($datos->tipo_movimiento == "PAGO")
                                                <span class="badge bg-success fs-9 fw-bold text-light">{{$datos->tipo_movimiento}}</span>
                                            @elseif($datos->tipo_movimiento == "CARGO" || $datos->tipo_movimiento == "GASTO")
                                                <span class="badge bg-danger fs-9 fw-bold text-light">{{$datos->tipo_movimiento}}</span>
                                            @elseif($datos->tipo_movimiento == "DESEMBOLSO")
                                                <span class="badge bg-danger fs-9 fw-bold text-light">{{$datos->tipo_movimiento}}</span>
                                            @elseif($datos->tipo_movimiento == "RETIRO")
                                                <span class="badge bg-danger fs-9 fw-bold text-light">{{$datos->tipo_movimiento}}</span>
                                            @else
                                                <span class="badge bg-success fs-9 fw-bold text-light">{{$datos->tipo_movimiento}}</span>
                                            @endif
                                        </td>

                                        <td class="text-truncate">
                                            @if($datos->ingreso == 0)
                                                <span class="text-muted">-</span>
                                            @else
                                                <span class="fw-bold text-success">
                                                    ${{number_format($datos->ingreso, 2)}}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="text-truncate">
                                            @if($datos->egreso == 0)
                                                <span class="text-muted">-</span>
                                            @else
                                                <span class="fw-bold text-danger">
                                                    ${{number_format($datos->egreso, 2)}}
                                                </span>
                                            @endif
                                        </td>
                                    
                                        <td class="text-truncate fw-bold">
                                            ${{number_format($datos->saldo, 2)}}
                                        </td>

                                        <td class="text-truncate">
                                            <button class="btn btn-light text-secondary border-secondary text-start shadow-0" data-bs-toggle="modal" data-bs-target="#Modalcomentario{{$datos->id}}">
                                               + <i class="fa-solid fa-message"></i> {{Str::limit($datos->concepto, 15)}}
                                            </button>
                                        </td>

                                        <td class="text-truncate fs-9">
                                            <small class="pointer" title="{{$datos->nombreEmp}}">{{Str::limit($datos->nombreEmp, 20)}}</small>
                                        </td>

                                        <td class="text-truncate">
                                            @if($datos->numero_poliza != 0)
                                                <form action="/Tesoreria/Movimientos/Poliza/{{$datos->tipo_movimiento}}/{{$datos->numero_poliza}}" target="_blank">
                                                    <input type="text" name="idmov" hidden value="{{$datos->id}}">
                                                    <input type="text" name="id1" hidden value="{{$datos->id_tipo}}">
                                                    <input type="text" name="nombre1" hidden value="{{$datos->cuenta}}">
                                                    <input type="text" name="id2" hidden value="{{$datos->numero_referencia}}">
                                                    <input type="text" name="tipo1" hidden value="{{$tipo}}">
                                                    <input type="text" name="tipo2" hidden value="{{$datos->tipo_referencia}}">
                                                    @if($datos->ingreso != 0)
                                                        <input type="text" name="saldo" hidden value="{{$datos->ingreso}}">
                                                    @else
                                                        <input type="text" name="saldo" hidden value="{{$datos->egreso}}">
                                                    @endif
                                                    <input type="text" name="saldo_inicial" hidden value="{{$saldo_inicial}}">
                                                    <input type="text" name="saldo_actual" hidden value="{{$datos->saldo}}">
                                                    <input type="text" name="concepto" hidden value="{{$datos->concepto}}">
                                                    <input type="text" name="descripcion" hidden value="{{$datos->descripcion}}">
                                                    <input type="text" name="usuario" hidden value="{{$datos->created_by}}">
                                                    <input type="text" name="id_empleado" hidden value="{{$datos->id_empleado ?? ''}}">
                                                    <input type="text" name="empresa" hidden value="{{$datos->idempresa}}">
                                                    <input type="text" name="pertenece" hidden value="{{$datos->pertenencia}}">
                                                    <button type="submit" class="btn btn-primary btn-sm rounded-1">
                                                        <i class="fas fa-download me-1"></i>{{$datos->numero_poliza}}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <td class="text-truncate">
                                            <button class="btn btn-outline-secondary btn-sm text-start shadow-0" data-bs-toggle="modal" data-bs-target="#Modaliva{{$datos->id}}">
                                                IVA y Retenciones
                                            </button>
                                        </td>

                                        <td class="text-truncate">
                                            <div class="action-buttons text-truncate">
                                                @if($datos->tipo_movimiento == "GASTO" && $datos->estado != "C")
                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#Modalcancel{{$datos->id}}">
                                                        <i class="fas fa-ban me-1"></i>Cancelar
                                                    </button>
                                                @else
                                                    <button disabled class="btn btn-secondary btn-sm">
                                                        <i class="fas fa-ban me-1"></i>Cancelar
                                                    </button>
                                                @endif
                                                @if($datos->tipo_movimiento == "GASTO")
                                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalVer{{$datos->id}}">
                                                        <i class="fas fa-eye me-1"></i>Ver
                                                    </button>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="text-truncate">
                                            @if($datos->estado == "A")
                                                <span class="badge badge-success-dark text-light">
                                                    AUTORIZADO
                                                </span>
                                            @elseif($datos->estado == "D")
                                                <span class="badge badge-danger-dark text-light">
                                                    DECLINADO
                                                </span>
                                            @elseif($datos->estado == "E")
                                                <span class="badge badge-orange-dark text-light">
                                                    EN ESPERA
                                                </span>
                                            @elseif($datos->estado == "C")
                                                <span class="badge badge-danger-dark text-light">
                                                   CANCELADO
                                                </span>
                                            @else
                                                <span class="badge badge-primary-dark text-light">
                                                    INCONCLUSO
                                                </span>
                                            @endif
                                        </td>

                                        <td class="text-truncate">
                                            {{$datos->numero_referencia}}
                                        </td>
                                        
                                        <td class="text-truncate">
                                            {{$datos->created_by}}
                                        </td>
                                        <td class="text-truncate">
                                            {{$datos->pertenencia}}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3"></th>
                        <th>Subtotal</th>
                        <th></th>
                        <th></th>
                        <th colspan="9"></th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Totales:</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th colspan="9"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>


@foreach($obtenerHistorial as $datos)
  <!-- Modal iva -->
  <div class="modal fade text-start" id="Modaliva{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row justify-content-center">
                  <div class="col-md-11">
                    <h5 class="text-dark  mb-4 text-start">
                      <i class="fa-solid fs-6 fa-chart-line text-green"></i> IVA Y Retenciones
                    </h5>

                    <table class="table">
                        @if($datos->ingreso > 0)                            
                            @php($importe = $datos->ingreso)
                        @else
                            @php($total_neto = $datos->egreso)
                            @php($suma = $datos->total_iva)
                            @php($resta = $datos->total_ret_iva + $datos->total_ret_isr + $datos->total_ret_isr_resico)
                            @php($importe = ($total_neto + $resta) - $suma)
                        @endif
                      
                      <thead>
                        <tr>
                            <th colspan="2" class="fw-bold text-primary">IMPORTE</th>
                            <th colspan="2" class="fw-bold"> ${{number_format($importe, 2)}}</th>
                        </tr>
                        <tr>
                          <td class="fw-bold">IVA</td>
                          <td class="fw-bold">RET IVA</td>
                          <td class="fw-bold">RET ISR</td>
                          <td class="fw-bold">RET ISR RESICO</td>
                        </tr>
                      </thead>

                      <tbody>
                        <tr>
                          <td>${{number_format($datos->total_iva, 2)}}</td>
                          <td>${{number_format($datos->total_ret_iva, 2)}}</td>
                          <td>${{number_format($datos->total_ret_isr, 2)}}</td>
                          <td>${{number_format($datos->total_ret_isr_resico, 2)}}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Modal concepto -->
  <div class="modal fade" id="Modalcomentario{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="row justify-content-center">
                  <div class="col-md-11">
                      <h6 class="text-dark mb-4 text-start"><i class="text-green fa-solid fa-info fs-4"></i>&nbsp; Información de Movimiento</h6>

                      <table class="table border">
                        <tbody>
                          <tr>
                            <td class="fw-bold">CONCEPTO</td>
                            <td class="text-justify">{{$datos->concepto}}</td>
                          </tr>

                          <tr>
                            <td class="fw-bold">DESCRIPCIÓN</td>
                            <td class="text-justify">{{$datos->descripcion}}</td>
                          </tr>
                        </tbody>
                      </table>
                  </div>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Modal dictamen -->
  <div class="modal fade" id="modalVer{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title" id="exampleModalLabel">Dictamen de Cargo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
        
          <div class="modal-body">
            <div class="row mb-2 p-2 pt-0">
                <div class="mb-2 text-start">
                  
                  <p class="fs-8 text-green text-wrap fw-normal" style="width: 100%;">
                    <b>GASTO </b><br>{{$datos->concepto}} <br>
                    <b>DESCRIPCIÓN </b> <br> {{$datos->descripcion}}
                  </p>
                </div>

                <div class="text-start">
                      <h6>
                        <a target="_blank" class="btn btn-primary btn-mode fs-8 border-0 text-light 0 m-0 rounded-1" href="{{asset('Tesoreria/Gastos/'.$tipo.'/'.$datos->ruta_evidencia)}}">
                          Comprobante &nbsp; &nbsp;<i class="fa-solid fa-eye"></i>
                        </a>
                      </h6>
                </div>
            </div> 
          </div>

          <div class="row justify-content-center p-3">
              @if(!is_null($datos->ruta_evidencia) && $datos->estado == "E" || $datos->estado == "D")
                <a class="btn btn-danger btn-mode col-4 m-1 rounded-1" href="/Tesoreria/Movimientos/Dictamen/{{$tipo}}/Declinar/{{$datos->id}}/{{$datos->id_tipo}}/{{$datos->egreso}}"><i class="fa-solid fa-xmark"></i> Declinar</a>
                <a class="btn btn-success btn-mode col-4 m-1 rounded-1" href="/Tesoreria/Movimientos/Dictamen/{{$tipo}}/Autorizar/{{$datos->id}}/{{$datos->id_tipo}}/{{$datos->egreso}}"><i class="fa-solid fa-check"></i> Autorizar</a>
              @endif
          </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="Modalcancel{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title text-dark">Cancelar Gasto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <form action="/Tesoreria/Movimientos/cancelarGasto/{{$tipo}}/{{$id}}" method="POST" class="form" novalidate>
            @csrf
              <div class="modal-body text-start p-4 pt-2">
                <h4 class="text-dark mb-4">¿Está seguro de cancelar este "Gasto"?</h4>
                <input type="number" value="{{$datos->id}}" name="id_mov" required hidden>
                <input type="number" value="{{$datos->egreso}}" name="monto" required hidden>
                <input type="text" value="{{$datos->concepto}}" name="concepto" required hidden>
                <input type="date" value="{{$datos->fecha}}" name="fecha" required hidden>

                <p class="p-3 card rounded-3 text-wrap fw-normal"  style="width: 100%;">
                  <b>CONCEPTO: </b> {{$datos->concepto}}<br><br>
                  <b>DESCRIPCION:</b> {{$datos->descripcion}}
                </p>

                <label for="" class="text-dark">Motivo de cancelación:</label>
                <div class="form-floating">
                  <textarea class="form-control text" placeholder="Leave a comment here" id="floatingTextarea2" name="descripcion" style="height: 100px" required></textarea>
                  <label for="floatingTextarea2">Comentario</label>
                </div>
              </div>

              <div class="modal-footer justify-content-center border-0">
                <button type="submit" class="col-4 btn btn-success fs-8 rounded-1"><i class="fa-solid fa-check"></i> Aplicar</button>
              </div>
          </form>
      </div>
    </div>
  </div>
@endforeach

<style>
    .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    table .btn-group .btn {
        margin-right: 0.25rem;
        text-align: center;
    }

    .btn-group .btn:last-child {
        margin-right: 0;
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }

    .form-control {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: #00634e;
        box-shadow: 0 0 0 0.2rem rgba(0, 108, 96, 0.25);
    }

    @media (max-width: 768px) {
        table .btn-group {
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        
        table .btn-group .btn {
            margin-right: 0;
            margin-bottom: 0.25rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoFechaSelector = document.getElementById('tipoFechaSelector');
        const hiddenFechaInicio = document.getElementById('hiddenFechaInicio');
        const hiddenFechaFin = document.getElementById('hiddenFechaFin');
        const hiddenTipoFecha = document.getElementById('hiddenTipoFecha');
        const exportFechaInicio = document.getElementById('exportFechaInicio');
        const exportFechaFin = document.getElementById('exportFechaFin');
        const exportTipoFecha = document.getElementById('exportTipoFecha');
        const tipoMovimientoSelector = document.getElementById('tipoMovimientoSelector');
        const exportTipoMovimiento = document.getElementById('exportTipoMovimiento');
        const fechaAplicacionInicio = document.getElementById('fechaAplicacionInicio');
        const fechaAplicacionFin = document.getElementById('fechaAplicacionFin');
        const fechaMovimientoInicio = document.getElementById('fechaMovimientoInicio');
        const fechaMovimientoFin = document.getElementById('fechaMovimientoFin');

        const aplicarTipoFecha = (tipo) => {
            const isAplicacion = tipo === 'aplicacion';
            document.querySelectorAll('.fecha-aplicacion-group').forEach(el => el.style.display = isAplicacion ? '' : 'none');
            document.querySelectorAll('.fecha-movimiento-group').forEach(el => el.style.display = isAplicacion ? 'none' : '');

            fechaAplicacionInicio.required = isAplicacion;
            fechaAplicacionFin.required = isAplicacion;
            fechaMovimientoInicio.required = !isAplicacion;
            fechaMovimientoFin.required = !isAplicacion;

            hiddenTipoFecha.value = tipo;
            if (exportTipoFecha) {
                exportTipoFecha.value = tipo;
            }
        };

        const sincronizarFechas = (tipo) => {
            if (tipo === 'movimiento') {
                hiddenFechaInicio.value = fechaMovimientoInicio.value || '';
                hiddenFechaFin.value = fechaMovimientoFin.value || '';
                if (exportFechaInicio) exportFechaInicio.value = hiddenFechaInicio.value;
                if (exportFechaFin) exportFechaFin.value = hiddenFechaFin.value;
            } else {
                hiddenFechaInicio.value = fechaAplicacionInicio.value || '';
                hiddenFechaFin.value = fechaAplicacionFin.value || '';
                if (exportFechaInicio) exportFechaInicio.value = hiddenFechaInicio.value;
                if (exportFechaFin) exportFechaFin.value = hiddenFechaFin.value;
            }
        };

        if (tipoFechaSelector) {
            aplicarTipoFecha(tipoFechaSelector.value);
            sincronizarFechas(tipoFechaSelector.value);

            tipoFechaSelector.addEventListener('change', function() {
                aplicarTipoFecha(this.value);
                sincronizarFechas(this.value);
            });

            [fechaAplicacionInicio, fechaAplicacionFin, fechaMovimientoInicio, fechaMovimientoFin].forEach((input) => {
                if (!input) return;
                input.addEventListener('change', () => sincronizarFechas(tipoFechaSelector.value));
            });
        }

        if (tipoMovimientoSelector && exportTipoMovimiento) {
            exportTipoMovimiento.value = tipoMovimientoSelector.value;
            tipoMovimientoSelector.addEventListener('change', function() {
                exportTipoMovimiento.value = this.value;
            });
        }
    });
</script>

<script src="{{ asset('js/tableX.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection
