@extends('layouts.app')
@section('content')

<div class="container-fluid format_page">
    <!-- Encabezado -->
    <div class="row">
        <div class="col-lg-3 col-12 start-center">
            <h3 class="mt-1 animate_animated animate_backInLeft">Divisiones del Proyecto</h3>
            <span class="p-0 m-0 d-none d-md-block fs-8">Detalles y seguimiento de divisiones del proyecto.</span>
        </div>

        <div class="col-lg-9 col-12 center-end">
            <a href="{{ route('proyectos.index') }}" class="btn btn-secondary fs-7 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Volver a Proyectos
            </a>
            <button id="btnAgregarDivisionSiguiente" class="btn btn-primary fs-7 mb-2" type="button" {{ (isset($servicio->estado_servicio) && $servicio->estado_servicio !== 'EN PROCESO') ? 'disabled' : '' }}>
                <i class="fa-solid fa-calendar-plus"></i> Agregar nueva división (después de la última)
            </button>
            <button id="btnCrearIngresoProyecto" class="btn btn-success fs-7 mb-2" type="button" onclick="abrirModalIngresoProyecto({{ isset($servicio) ? (int)$servicio->id : 0 }})">
                <i class="fa-solid fa-plus"></i> Crear Ingreso de Proyecto
            </button>
            <a class="btn btn-outline-dark fs-7 mb-2" href="{{ isset($servicio) ? route('proyectos.gastos', ['idServicio' => $servicio->id]) : '#' }}">
                <i class="fa-solid fa-chart-area"></i> Ver Gastos del Proyecto
            </a>
        </div>
    </div>

<!-- Modal para Ingreso de Proyecto (colocado fuera de otros modales) -->
<div class="modal fade" id="modalIngresoProyecto" tabindex="-1" aria-labelledby="modalIngresoProyectoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalIngresoProyectoLabel">
                    <i class="fa-solid fa-coins"></i> Ingreso de Proyecto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formIngresoProyecto" enctype="multipart/form-data">
                    <input type="hidden" name="id_proyecto" id="id_proyecto_ingreso" value="{{ isset($servicio) ? (int)$servicio->id : '' }}">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="id_proyecto_ingreso_visible" class="form-label">
                                <i class="fa-solid fa-hashtag"></i> ID Proyecto
                            </label>
                            <input type="text" id="id_proyecto_ingreso_visible" class="form-control" value="{{ isset($servicio) ? (int)$servicio->id : '' }}" disabled>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo_ingreso_proy" class="form-label">
                                <i class="fa-solid fa-money-bill"></i> Tipo de Ingreso
                            </label>
                            <select class="form-select" id="tipo_ingreso_proy" name="tipo_ingreso" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="EST. MED. OFICINA Y OTROS">EST. MED. OFICINA Y OTROS</option>
                                <option value="ART. DEL HOGAR">ART. DEL HOGAR</option>
                                <option value="MANO DE OBRA">MANO DE OBRA</option>
                                <option value="SUMINISTRO">SUMINISTRO</option>
                                <option value="SERV. PÚBLICOS">SERV. PÚBLICOS</option>
                                <option value="HOSPEDAJE">HOSPEDAJE</option>
                                <option value="ALIMENTOS">ALIMENTOS</option>
                                <option value="TRANSPORTE Y LOGISTICA">TRANSPORTE Y LOGISTICA</option>
                                <option value="HERRAMIENTAS Y EQUIPO">HERRAMIENTAS Y EQUIPO</option>
                                <option value="SEGUROS">SEGUROS</option>
                                <option value="PERMISOS Y LICENCIAS">PERMISOS Y LICENCIAS</option>
                                <option value="CAP. Y CERTIF">CAP. Y CERTIF</option>
                                <option value="OTROS">OTROS</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="monto_ingreso_proy" class="form-label">
                                <i class="fa-solid fa-dollar-sign"></i> Monto del Ingreso
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="monto_ingreso_proy" name="monto_ingreso" step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="fecha_ingreso_proy" class="form-label">
                                <i class="fa-solid fa-calendar-day"></i> Fecha
                            </label>
                            <input type="date" class="form-control" id="fecha_ingreso_proy" name="fecha" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="comentario_ingreso_proy" class="form-label">
                                <i class="fa-solid fa-edit"></i> Comentario
                            </label>
                            <input type="text" class="form-control" id="comentario_ingreso_proy" name="comentario" placeholder="Comentario del ingreso (opcional)">
                        </div>
                    </div>

                    <div class="row" id="contenedorManoObraProy" style="display:none;">
                        <div class="col-md-12 mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="switchPorPuestoProy">
                                <label class="form-check-label" for="switchPorPuestoProy">Capturar por puesto (manual) en lugar de por empleado</label>
                            </div>

                            <div id="wrapEmpleadoProy">
                                <label class="form-label"><i class="fa-solid fa-user-gear"></i> Empleado</label>
                                <select class="form-select" id="empleadoLocalProy" name="empleado_local">
                                    <option value="">Seleccionar...</option>
                                    @isset($EmpleadosActivos)
                                        @foreach($EmpleadosActivos as $empleado)
                                            <option value="{{ $empleado->id }}">{{ $empleado->Nombre }}</option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>

                            <div id="wrapPuestoProy" style="display:none;">
                                <label class="form-label"><i class="fa-solid fa-briefcase"></i> Puesto</label>
                                <select class="form-select" id="puestoProy">
                                    <option value="">Seleccionar puesto...</option>
                                    @isset($PuestosActivos)
                                        @foreach($PuestosActivos as $puesto)
                                            <option value="{{ $puesto->id }}">{{ $puesto->nombre }}</option>
                                        @endforeach
                                    @endisset
                                </select>
                                <div class="mt-2">
                                    <label class="form-label">Cantidad de trabajadores</label>
                                    <input type="number" min="1" value="1" class="form-control" id="cantidadTrabPuestoProy">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="contenedorSuministroProy" style="display:none;">
                        <div class="col-md-12 mb-3">
                            <label for="producto_suministro_proy" class="form-label">
                                <i class="fa-solid fa-box"></i> Producto (con existencia)
                            </label>
                            <select class="form-select" id="producto_suministro_proy" name="producto_suministro">
                                <option value="">Seleccionar producto...</option>
                            </select>
                            <div class="mt-2">
                                <label class="form-label">Cantidad a usar</label>
                                <input type="number" class="form-control" id="cantidad_suministro_proy" name="cantidad_suministro" min="1" value="1">
                                <small class="text-muted" id="info_existencia_proy"></small>
                            </div>
                            <div class="mt-2" id="detalle_existencias_container_proy" style="display:none;">
                                <div class="card"><div class="card-body p-2"><div class="table-responsive">
                                    <table class="table table-sm mb-0"><thead><tr><th>Almacén</th><th>Ubicación</th><th class="text-end">Disponible</th></tr></thead>
                                    <tbody id="tbody_detalle_existencias_proy"></tbody></table>
                                </div></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tipo_operacion_proy" class="form-label">
                                <i class="fa-solid fa-exchange-alt"></i> Tipo de Operación
                            </label>
                            <select class="form-select" id="tipo_operacion_proy" name="tipo_operacion" required>
                                <option value="">Seleccionar operación...</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="cheque">Cheque</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="deposito">Depósito</option>
                                <option value="ingreso">Ingreso</option>
                                <option value="egreso">Egreso</option>
                                <option value="ajuste">Ajuste</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ruta_documento_proy" class="form-label">
                                <i class="fa-solid fa-file-upload"></i> Documento
                            </label>
                            <input type="file" class="form-control" id="ruta_documento_proy" name="ruta_documento" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGuardarIngresoProyecto">
                    <i class="fa-solid fa-save"></i> Guardar Ingreso
                </button>
            </div>
        </div>
    </div>
    </div>
    <!-- Información del Proyecto -->
    @if(isset($servicio))
    <div class="row mt-3 mb-3">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="mb-1">Cliente</h6>
                            <h5 class="mb-0">{{ $servicio->cliente }}</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Folio</h6>
                            <h5 class="mb-0">{{ $servicio->folio }}</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Fecha Inicio</h6>
                            <h5 class="mb-0">{{ \Carbon\Carbon::parse($servicio->fecha_inicio)->format('d/m/Y') }}</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Fecha Límite</h6>
                            <h5 class="mb-0">{{ \Carbon\Carbon::parse($servicio->fecha_limite)->format('d/m/Y') }}</h5>
                        </div>
                        <div class="col-12 mt-2">
                            @php $estadoSvc = $servicio->estado_servicio ?? ''; @endphp
                            @if($estadoSvc === 'BORRADOR')
                                <span class="badge bg-warning text-dark">Estado: BORRADOR. Solo se permite División de Proyecto y Cotizar.</span>
                            @elseif($estadoSvc === 'CIERRE')
                                <span class="badge bg-secondary">Estado: CIERRE. Ediciones bloqueadas.</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Tarjetas resumen -->
    <div class="row mt-3 mb-3">
        <div class="col-lg-2 col-md-3 col-6 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate small">Total Divisiones</h6>
                            <div class="p-2 fs-4 fw-semibold mb-0">
                                @php
                                    $totalDivisiones = count($divisiones);
                                @endphp
                                {{ $totalDivisiones }}
                        </div>
                        </div>
                        <div class="fs-3 p-2 opacity-50">
                            <i class="fa-solid fa-list-ol"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-3 col-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate small">Monto Total</h6>
                            <div class="p-2 fs-4 fw-semibold mb-0">
                                @php
                                    $montoTotal = 0;
                                    foreach($divisiones as $division) {
                                        $montoTotal += $division->monto;
                                    }
                                @endphp
                                ${{ number_format($montoTotal, 2) }}
                        </div>
                        </div>
                        <div class="fs-3 p-2 opacity-50">
                            <i class="fa-solid fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        

        
        @isset($montoExtras)
        <div class="col-lg-2 col-md-3 col-6 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate small">Costo Divisiones Extras</h6>
                            <div class="p-2 fs-4 fw-semibold mb-0">${{ number_format($montoExtras, 2) }}</div>
                        </div>
                        <div class="fs-3 p-2 opacity-50">
                            <i class="fa-solid fa-circle-plus"></i>
                    </div>
                </div>
            </div>
        </div>
        </div>
        @endisset
        @isset($montoBase)
        <div class="col-lg-2 col-md-3 col-6 mb-3">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate small">Monto Base (antes de extras)</h6>
                            <div class="p-2 fs-4 fw-semibold mb-0">${{ number_format($montoBase, 2) }}</div>
                        </div>
                        <div class="fs-3 p-2 opacity-50">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endisset
        <div class="col-lg-2 col-md-3 col-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate small">Ingresado en Partidas</h6>
                            <div class="p-2 fs-4 fw-semibold mb-0">${{ number_format(($montoTotalIngresos ?? 0), 2) }}</div>
                        </div>
                        <div class="fs-3 p-2 opacity-50">
                            <i class="fa-solid fa-coins"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-3 col-6 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate small">Disponible según costeo</h6>
                            <div class="p-2 fs-4 fw-semibold mb-0">${{ number_format(($montoDisponible ?? 0), 2) }}</div>
                        </div>
                        <div class="fs-3 p-2 opacity-50">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Divisiones -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-table"></i> Divisiones del Proyecto
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaDivisiones">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Partida / Título</th>
                                    <th>Monto</th>
                                    
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($divisiones as $division)
                                    @php
                                        $esExtra = isset($minCreatedAt) && $division->created_at > $minCreatedAt;
                                    @endphp
                                    <tr data-division-id="{{ $division->id }}" @if(!empty($esExtra) && $esExtra) class="table-danger" title="División fuera de costeo original" @endif>
                                        <td>{{ $division->id }}</td>
                                        <td data-order="{{ intval($division->plazo) }}">
                                            <div class="d-flex flex-column">
                                                @if(!empty($division->titulo_division))
                                                    <span class="fw-semibold text-dark">{{ $division->titulo_division }}</span>
                                                @else
                                                    <span class="text-muted">Sin título</span>
                                                @endif
                                                <div class="mt-1">
                                                    <span class="badge bg-primary me-1" style="font-size: 0.65rem;">Partida {{ $division->plazo }}</span>
                                                    @if(!empty($esExtra) && $esExtra)
                                                        <span class="badge bg-danger" style="font-size: 0.65rem;"><i class="fa-solid fa-exclamation-triangle me-1"></i>División fuera de costeo original</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">
                                                ${{ number_format($division->monto, 2) }}
                                            </span>
                                        </td>
                                      
                                        <td>
                                            <div class="btn-group" role="group">
                                                {{-- Botones ocultos: Asignar Partida y Detalle de Partidas --}}
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="tooltip" data-bs-placement="top" 
                                                        title="{{ (isset($division->partidas_count) && intval($division->partidas_count) > 0) ? 'No se puede eliminar: la división tiene partidas' : 'Eliminar (requiere autorización)' }}" 
                                                        onclick="{{ (isset($division->partidas_count) && intval($division->partidas_count) > 0) ? '' : 'abrirModalEliminarAutorizado('.$division->id.')' }}"
                                                        {{ (isset($division->partidas_count) && intval($division->partidas_count) > 0) || (isset($servicio->estado_servicio) && $servicio->estado_servicio === 'CIERRE') ? 'disabled' : '' }}>
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        data-bs-toggle="tooltip" data-bs-placement="top" 
                                                        title="{{ (isset($division->partidas_count) && intval($division->partidas_count) > 0) ? 'No se puede editar el monto: la división tiene partidas' : 'Editar monto' }}" 
                                                        onclick="{{ (isset($division->partidas_count) && intval($division->partidas_count) > 0) ? '' : 'editarMontoDivision('.$division->id.', '.number_format($division->monto, 2, '.', '').')' }}"
                                                        {{ (isset($division->partidas_count) && intval($division->partidas_count) > 0) || (isset($servicio->estado_servicio) && in_array($servicio->estado_servicio, ['BORRADOR','CIERRE'])) ? 'disabled' : '' }}>
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <form action="{{ route('facturacion.proyecto', ['id' => isset($servicio) ? $servicio->id : 0, 'servicio' => 'Proyecto', 'id_division' => $division->id]) }}" method="GET" style="display: inline;">
                                                    <button type="submit" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="Facturar División">
                                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fa-solid fa-inbox fa-3x mb-3"></i>
                                                <h5>No hay divisiones disponibles</h5>
                                                <p>Este proyecto no tiene divisiones creadas aún.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Asignar Partida -->
<div class="modal fade" id="modalAsignarPartida" tabindex="-1" aria-labelledby="modalAsignarPartidaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalAsignarPartidaLabel">
                    <i class="fa-solid fa-plus"></i> Asignar Partida
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAsignarPartida" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <div class="alert alert-secondary mb-0">
                                <i class="fa-solid fa-tag"></i>
                                <strong>Título de la división:</strong>
                                <span id="tituloDivisionSeleccionada">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="id_partida_proyecto" class="form-label">
                                <i class="fa-solid fa-list"></i> ID de la Partida
                            </label>
                            <input type="number" class="form-control" id="id_partida_proyecto" name="id_partida_proyecto" 
                                   min="1" required placeholder="Ej: 1, 2, 3..." readonly>
                            <div class="form-text">
                                Este valor se asigna automáticamente según la división seleccionada
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tipo_ingreso" class="form-label">
                                <i class="fa-solid fa-money-bill"></i> Tipo de Ingreso
                            </label>
                            <select class="form-select" id="tipo_ingreso" name="tipo_ingreso" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="EST. MED. OFICINA Y OTROS">EST. MED. OFICINA Y OTROS</option>
                                <option value="ART. DEL HOGAR">ART. DEL HOGAR</option>
                                <option value="MANO DE OBRA">MANO DE OBRA</option>
                                <option value="SUMINISTRO">SUMINISTRO</option>
                                <option value="SERV. PÚBLICOS">SERV. PÚBLICOS</option>
                                <option value="HOSPEDAJE">HOSPEDAJE</option>
                                <option value="ALIMENTOS">ALIMENTOS</option>
                                <option value="TRANSPORTE Y LOGISTICA">TRANSPORTE Y LOGISTICA</option>
                                <option value="HERRAMIENTAS Y EQUIPO">HERRAMIENTAS Y EQUIPO</option>
                                <option value="SEGUROS">SEGUROS</option>
                                <option value="PERMISOS Y LICENCIAS">PERMISOS Y LICENCIAS</option>
                                <option value="CAP. Y CERTIF">CAP. Y CERTIF</option>
                                <option value="OTROS">OTROS</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="contenedorManoObra" style="display:none;">
                            <label class="form-label"><i class="fa-solid fa-user-gear"></i> Mano de Obra</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="switchLocalExterno">
                                <label class="form-check-label" for="switchLocalExterno">Local (activado) / Foráneo (desactivado)</label>
                            </div>
                            <div class="mt-2" id="seccionLocal" style="display:none;">
                                <label class="form-label">Seleccionar empleado</label>
                                <select class="form-select" id="empleadoLocal" name="empleado_local">
                                    <option value="">Seleccionar...</option>
                                    @isset($EmpleadosActivos)
                                        @foreach($EmpleadosActivos as $empleado)
                                            <option value="{{ $empleado->id }}">{{ $empleado->Nombre }}</option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>
                            <div class="mt-2" id="seccionForaneo" style="display:none;">
                                <label class="form-label">Descripción Foráneo</label>
                                <input type="text" class="form-control" id="descripcionForaneo" name="descripcion_foraneo" placeholder="Descripción del proveedor externo">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="monto_ingreso" class="form-label">
                                <i class="fa-solid fa-dollar-sign"></i> Monto del Ingreso
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="monto_ingreso" name="monto_ingreso" 
                                       step="0.01" min="0" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3" id="contenedorSuministro" style="display:none;">
                            <label for="producto_suministro" class="form-label">
                                <i class="fa-solid fa-box"></i> Producto (con existencia)
                            </label>
                            <select class="form-select" id="producto_suministro" name="producto_suministro">
                                <option value="">Seleccionar producto...</option>
                            </select>
                            <div class="mt-2">
                                <label class="form-label">Cantidad a usar</label>
                                <input type="number" class="form-control" id="cantidad_suministro" name="cantidad_suministro" min="1" value="1">
                                <small class="text-muted" id="info_existencia"></small>
                                <div class="mt-2" id="detalle_existencias_container" style="display:none;">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <div class="table-responsive">
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Almacén</th>
                                                            <th>Ubicación</th>
                                                            <th class="text-end">Disponible</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbody_detalle_existencias"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tipo_operacion" class="form-label">
                                <i class="fa-solid fa-exchange-alt"></i> Tipo de Operación
                            </label>
                            <select class="form-select" id="tipo_operacion" name="tipo_operacion" required>
                                <option value="">Seleccionar operación...</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="cheque">Cheque</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="deposito">Depósito</option>
                                <option value="ingreso">Ingreso</option>
                                <option value="egreso">Egreso</option>
                                <option value="ajuste">Ajuste</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="ruta_documento" class="form-label">
                                <i class="fa-solid fa-file-upload"></i> Documento
                            </label>
                            <input type="file" class="form-control" id="ruta_documento" name="ruta_documento" 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <div class="form-text">
                                Formatos permitidos: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarPartida">
                    <i class="fa-solid fa-save"></i> Guardar Partida
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalle de Partidas -->
<div class="modal fade" id="modalDetallePartidas" tabindex="-1" aria-labelledby="modalDetallePartidasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalDetallePartidasLabel">
                    <i class="fa-solid fa-list"></i> Detalle de Partidas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Encabezado con información de la división -->
                <div class="row mb-4" id="encabezadoDivision" style="display: none;">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <h6 class="mb-1 text-muted">División #</h6>
                                        <h5 class="mb-0" id="numeroDivision">-</h5>
                                    </div>
                                    <div class="col-md-3">
                                        <h6 class="mb-1 text-muted">Monto División</h6>
                                        <h5 class="mb-0 text-success" id="montoDivision">-</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjetas de totales -->
                <div class="row mb-4" id="tarjetasTotales" style="display: none;">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-truncate">Total Partidas</h6>
                                        <h3 class="mb-0" id="totalPartidas">0</h3>
                                    </div>
                                    <div class="fs-1 opacity-50">
                                        <i class="fa-solid fa-list"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-truncate">Monto Total</h6>
                                        <h3 class="mb-0" id="montoTotalPartidas">$0.00</h3>
                                    </div>
                                    <div class="fs-1 opacity-50">
                                        <i class="fa-solid fa-dollar-sign"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-truncate">Promedio</h6>
                                        <h3 class="mb-0" id="promedioPartidas">$0.00</h3>
                                    </div>
                                    <div class="fs-1 opacity-50">
                                        <i class="fa-solid fa-chart-line"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de partidas -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Tipo de Ingreso</th>
                                <th>Monto</th>
                                <th>Tipo de Operación</th>
                                <th>Fecha</th>
                                <th>Documento</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDetallePartidas">
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Monto de Nueva División -->
<div class="modal fade" id="modalMontoNuevaDivision" tabindex="-1" aria-labelledby="modalMontoNuevaDivisionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalMontoNuevaDivisionLabel">
                    <i class="fa-solid fa-dollar-sign"></i> Costo de la nueva división
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Monto de la división</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" min="0" step="0.01" class="form-control" id="montoNuevaDivision" placeholder="0.00">
                    </div>
                    <div class="form-text">Deja vacío para usar el mismo monto que la última división.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnConfirmarNuevaDivision">
                    <i class="fa-solid fa-save"></i> Agregar
                </button>
            </div>
        </div>
    </div>
    
</div>

<!-- Modal Editar Monto División -->
<div class="modal fade" id="modalEditarMontoDivision" tabindex="-1" aria-labelledby="modalEditarMontoDivisionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalEditarMontoDivisionLabel">
                    <i class="fa-solid fa-pen-to-square"></i> Editar monto de la división
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editarMontoDivisionId" value="">
                <div class="mb-3">
                    <label class="form-label">Nuevo monto</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" min="0" step="0.01" class="form-control" id="editarMontoDivisionValor" placeholder="0.00">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btnGuardarEditarMontoDivision">
                    <i class="fa-solid fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Eliminar con Autorización -->
<div class="modal fade" id="modalEliminarAutorizado" tabindex="-1" aria-labelledby="modalEliminarAutorizadoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalEliminarAutorizadoLabel">
                    <i class="fa-solid fa-user-shield"></i> Autorización para eliminar división
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="eliminarDivisionId" value="">
                <div class="mb-3">
                    <label class="form-label">Gerente autorizador</label>
                    <select class="form-select" id="eliminarEmpleadoId">
                        <option value="">Seleccionar...</option>
                        @isset($EmpleadosActivos)
                            @foreach($EmpleadosActivos as $empleado)
                                <option value="{{ $empleado->id }}">{{ $empleado->Nombre }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Código de verificación</label>
                    <input type="password" class="form-control" id="eliminarCodigoVerificacion" placeholder="Código de autorización">
                </div>
                <div class="alert alert-warning mb-0">
                    Esta acción es irreversible. Solo se permite si la división no tiene partidas.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminarDivision">
                    <i class="fa-solid fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Inicializar DataTable
        $('#tablaDivisiones').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            responsive: true,
            order: [[1, 'asc']], // Ordenar por plazo
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
        });

        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Función para abrir modal de asignar partida
        window.abrirModalAsignarPartida = function(idDivision, idServicio) {
            // Limpiar formulario
            $('#formAsignarPartida')[0].reset();
            
            // Asignar automáticamente el ID de la división como ID de la partida
            $('#id_partida_proyecto').val(idDivision);
            
            // Limpiar campo de archivo
            $('#ruta_documento').val('');

            // Cargar título de la división seleccionada desde la fila
            const fila = $(`tr[data-division-id="${idDivision}"]`);
            const titulo = fila.find('td:nth-child(2) span.fw-semibold').text().trim() || 'Sin título';
            $('#tituloDivisionSeleccionada').text(titulo);

            // Reiniciar secciones de mano de obra
            $('#contenedorManoObra').hide();
            $('#seccionLocal').hide();
            $('#seccionForaneo').hide();

            // Cargar productos con existencia para el selector (por si el usuario elige SUMINISTRO)
            try { cargarProductosExistencia(''); } catch(e) {}
        };

        // Mostrar controles adicionales si Tipo de Ingreso = MANO DE OBRA
        $('#tipo_ingreso').on('change', function() {
            if ($(this).val() === 'MANO DE OBRA') {
                $('#contenedorManoObra').show();
                $('#contenedorSuministro').hide();
                $('#monto_ingreso').prop('readonly', false);
            } else {
                $('#contenedorManoObra').hide();
                $('#seccionLocal').hide();
                $('#seccionForaneo').hide();
                if ($(this).val() === 'SUMINISTRO') {
                    $('#contenedorSuministro').show();
                    cargarProductosExistencia('');
                    $('#monto_ingreso').prop('readonly', true);
                    // Forzar cálculo inicial si ya hay selección y cantidad
                    setTimeout(function(){ $('#producto_suministro').trigger('change'); }, 100);
                } else {
                    $('#contenedorSuministro').hide();
                    $('#monto_ingreso').prop('readonly', false);
                }
            }
        });
        $('#modalAsignarPartida').on('shown.bs.modal', function(){
            if ($('#tipo_ingreso').val() === 'SUMINISTRO') {
                cargarProductosExistencia('');
            }
        });

        function updateMontoSuministro(){
            const opt = $('#producto_suministro').find('option:selected');
            const precio = parseFloat(opt && opt.data('precio')) || 0;
            // Mostrar el precio unitario del producto como monto del ingreso
            $('#monto_ingreso').val(precio.toFixed(2));
        }

        // Cargar productos con existencia (búsqueda simple)
        function cargarProductosExistencia(q) {
            $.ajax({
                url: '{{ route('proyectos.productos-existencias') }}',
                method: 'GET',
                data: { q: q || '' },
                success: function(list){
                    const sel = $('#producto_suministro');
                    sel.empty();
                    sel.append('<option value="">Seleccionar producto...</option>');
                    if (Array.isArray(list) && list.length) {
                        list.forEach(function(p){
                            const disp = (p.disponible ?? p.disponible_total ?? 0);
                            sel.append(`<option data-precio="${p.precio_unitario}" data-existencia="${disp}" value="${p.id}">${p.nombre} - ${p.descripcion} (Disp: ${disp})</option>`);
                        });
                        sel.prop('selectedIndex', 1).trigger('change');
                        // Calcular monto inmediato basado en el primer producto y cantidad actual
                        updateMontoSuministro();
                    } else {
                        $('#info_existencia').text('');
                        sel.append('<option value="" disabled>No hay productos con existencia</option>');
                        $('#monto_ingreso').val('0.00');
                    }
                },
                error: function(){
                    const sel = $('#producto_suministro');
                    sel.empty();
                    sel.append('<option value="" disabled>Error cargando productos</option>');
                }
            });
        }

        // Al cambiar producto, actualizar info, detalle almacén/ubicación y monto sugerido
        $('#producto_suministro').on('change', function(){
            const opt = $(this).find('option:selected');
            const existencia = parseFloat(opt.data('existencia')) || 0;
            const precio = parseFloat(opt.data('precio')) || 0;
            $('#info_existencia').text(existencia ? `Existencia disponible: ${existencia}` : '');
            let cantidad = parseInt($('#cantidad_suministro').val() || '1');
            if (existencia && cantidad > existencia) {
                cantidad = existencia;
                $('#cantidad_suministro').val(cantidad);
            }
            updateMontoSuministro();

            // Cargar detalle por almacén/ubicación
            const productoId = parseInt(opt.val());
            if (productoId) {
                $.ajax({
                    url: '{{ route("proyectos.productos-existencias-detalle", ["productoId" => "__ID__"]) }}'.replace('__ID__', productoId),
                    method: 'GET',
                    success: function(resp){
                        const cont = $('#detalle_existencias_container');
                        const tbody = $('#tbody_detalle_existencias');
                        tbody.empty();
                        if (resp && resp.success && Array.isArray(resp.existencias) && resp.existencias.length) {
                            resp.existencias.forEach(function(row){
                                tbody.append(`
                                    <tr>
                                        <td>${row.almacen}</td>
                                        <td>${row.ubicacion}</td>
                                        <td class="text-end">${parseFloat(row.disponible).toLocaleString('es-MX')}</td>
                                    </tr>
                                `);
                            });
                            cont.show();
                        } else {
                            cont.hide();
                        }
                    },
                    error: function(){
                        $('#detalle_existencias_container').hide();
                    }
                });
            } else {
                $('#detalle_existencias_container').hide();
            }
        });

        // Al cambiar cantidad, recalcular monto si hay producto seleccionado
        $('#cantidad_suministro').on('input', function(){
            const opt = $('#producto_suministro').find('option:selected');
            const precio = parseFloat(opt.data('precio')) || 0;
            const existencia = parseFloat(opt.data('existencia')) || 0;
            let cantidad = parseInt($(this).val() || '1');
            if (existencia && cantidad > existencia) {
                cantidad = existencia;
                $(this).val(cantidad);
            }
            updateMontoSuministro();
        });

        // Switch Local/Foráneo
        $('#switchLocalExterno').on('change', function() {
            if ($(this).is(':checked')) {
                // Local
                $('#seccionLocal').show();
                $('#seccionForaneo').hide();
            } else {
                // Foráneo
                $('#seccionLocal').hide();
                $('#seccionForaneo').show();
            }
        });

        // Función para ver detalle de partidas
        window.verDetallePartidas = function(idDivision) {
            // Obtener información de la división desde la tabla
            const fila = $(`tr[data-division-id="${idDivision}"]`);
            const montoDivision = fila.find('td:nth-child(3)').text().trim();
            
            // Llenar encabezado de la división
            $('#numeroDivision').text(idDivision);
            $('#montoDivision').text(montoDivision);
            $('#encabezadoDivision').show();
            
            // Cargar datos de partidas para esta división
            $.ajax({
                url: `/proyectos/detalle-partidas/${idDivision}`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Llenar totales
                        $('#totalPartidas').text(response.total_partidas);
                        $('#montoTotalPartidas').text(formatCurrency(response.monto_total));
                        
                        // Calcular promedio
                        const promedio = response.total_partidas > 0 ? response.monto_total / response.total_partidas : 0;
                        $('#promedioPartidas').text(formatCurrency(promedio));
                        
                        // Mostrar tarjetas de totales
                        $('#tarjetasTotales').show();
                        
                        // Llenar la tabla con los datos
                        llenarTablaPartidas(response.partidas);
                        // Mostrar el modal
                        $('#modalDetallePartidas').modal('show');
                    } else {
                        // Ocultar tarjetas si no hay datos
                        $('#tarjetasTotales').hide();
                        $('#encabezadoDivision').show();
                        
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sin datos',
                            text: 'No hay partidas registradas para esta división'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al cargar los detalles de partidas'
                    });
                }
            });
        };

        // Función para llenar la tabla de partidas
        function llenarTablaPartidas(partidas) {
            const tbody = $('#tbodyDetallePartidas');
            tbody.empty();
            
            if (partidas.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fa-solid fa-inbox fa-2x mb-2"></i>
                                <p>No hay partidas registradas para esta división</p>
                            </div>
                        </td>
                    </tr>
                `);
                return;
            }
            
            partidas.forEach(function(partida) {
                const fecha = new Date(partida.created_at).toLocaleDateString('es-ES');
                const monto = parseFloat(partida.monto_ingreso).toLocaleString('es-MX', {
                    style: 'currency',
                    currency: 'MXN'
                });
                
                const urlDoc = `{{ route('proyectos.documento', ['id' => '__ID__']) }}`.replace('__ID__', partida.id);
                tbody.append(`
                    <tr>
                        <td>${partida.id}</td>
                        <td>
                            <span class="badge bg-primary">${partida.tipo_ingreso}</span>
                        </td>
                        <td>
                            <span class="fw-bold text-success">${monto}</span>
                        </td>
                        <td>
                            <span class="badge bg-info">${partida.tipo_operacion}</span>
                        </td>
                        <td>${fecha}</td>
                        <td>
                            ${partida.ruta_documento ? `<a href="${urlDoc}" target="_blank" class="btn btn-sm btn-outline-primary"><i class=\"fa-solid fa-download\"></i> Ver</a>` : ''}
                        </td>
                    </tr>
                `);
            });
        }

        // Función para formatear moneda
        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(amount);
        }

        // Event listener para el botón guardar partida
        $('#btnGuardarPartida').on('click', function() {
            // Validar formulario
            if (!$('#formAsignarPartida')[0].checkValidity()) {
                $('#formAsignarPartida')[0].reportValidity();
                return;
            }

            // Crear FormData para envío con archivo
            var formData = new FormData($('#formAsignarPartida')[0]);
            formData.append('_token', '{{ csrf_token() }}');

            // Mostrar loading
            $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');

            // Enviar datos al servidor
            $.ajax({
                url: '{{ route("proyectos.guardar-partida") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Guardado!',
                            text: response.message
                        }).then(() => {
                            const idDivision = $('#id_partida_proyecto').val();
                            const url = '{{ route("proyectos.division.partidas", ["idDivision" => "__ID__"]) }}'.replace('__ID__', idDivision);
                            window.location.href = url;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Error al guardar la partida';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                },
                complete: function() {
                    // Restaurar botón
                    $('#btnGuardarPartida').prop('disabled', false).html('<i class="fa-solid fa-save"></i> Guardar Partida');
                }
            });
        });

        // Agregar división siguiente
        const idServicioProyecto = {{ isset($servicio) ? (int)$servicio->id : 'null' }};
        $('#btnAgregarDivisionSiguiente').on('click', function(){
            if (!idServicioProyecto) {
                Swal.fire({ icon: 'warning', title: 'Proyecto no identificado', text: 'No se encontró el ID del proyecto.' });
                return;
            }
            $('#montoNuevaDivision').val('');
            $('#modalMontoNuevaDivision').modal('show');
        });

        // Editar monto división
        window.editarMontoDivision = function(idDivision, montoActual){
            $('#editarMontoDivisionId').val(idDivision);
            $('#editarMontoDivisionValor').val(parseFloat(montoActual).toFixed(2));
            $('#modalEditarMontoDivision').modal('show');
        };

        $('#btnGuardarEditarMontoDivision').on('click', function(){
            const idDivision = $('#editarMontoDivisionId').val();
            const monto = $('#editarMontoDivisionValor').val();
            if (monto === '' || isNaN(parseFloat(monto)) || parseFloat(monto) < 0) {
                Swal.fire({ icon: 'warning', title: 'Monto inválido', text: 'Ingresa un monto válido mayor o igual a 0.' });
                return;
            }
            $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');
            $.ajax({
                url: '{{ route("proyectos.division.actualizar-monto", ["idDivision" => "__ID__"]) }}'.replace('__ID__', idDivision),
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', monto: parseFloat(monto) },
                success: function(resp){
                    if (resp && resp.success) {
                        $('#modalEditarMontoDivision').modal('hide');
                        Swal.fire({ icon: 'success', title: 'Listo', text: resp.message }).then(()=>{ window.location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: (resp && resp.message) ? resp.message : 'No se pudo actualizar el monto.' });
                    }
                },
                error: function(xhr){
                    let msg = 'Error al actualizar el monto';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                },
                complete: () => {
                    $('#btnGuardarEditarMontoDivision').prop('disabled', false).html('<i class="fa-solid fa-save"></i> Guardar');
                }
            });
        });

        // Eliminar con autorización
        window.abrirModalEliminarAutorizado = function(idDivision){
            $('#eliminarDivisionId').val(idDivision);
            $('#eliminarEmpleadoId').val('');
            $('#eliminarCodigoVerificacion').val('');
            $('#modalEliminarAutorizado').modal('show');
        };

        $('#btnConfirmarEliminarDivision').on('click', function(){
            const idDivision = $('#eliminarDivisionId').val();
            const empleadoId = $('#eliminarEmpleadoId').val();
            const codigo = $('#eliminarCodigoVerificacion').val();
            if (!empleadoId || !codigo) {
                Swal.fire({ icon: 'warning', title: 'Datos requeridos', text: 'Selecciona el gerente y escribe el código de verificación.' });
                return;
            }
            $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Eliminando...');
            $.ajax({
                url: '{{ route("proyectos.division.eliminar-autorizado", ["idDivision" => "__ID__"]) }}'.replace('__ID__', idDivision),
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', empleado_id: empleadoId, codigo },
                success: function(resp){
                    if (resp && resp.success) {
                        $('#modalEliminarAutorizado').modal('hide');
                        Swal.fire({ icon: 'success', title: 'Eliminada', text: resp.message }).then(()=>{ window.location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'No autorizado', text: (resp && resp.message) ? resp.message : 'No se pudo eliminar.' });
                    }
                },
                error: function(xhr){
                    let msg = 'Error al eliminar';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                },
                complete: () => {
                    $('#btnConfirmarEliminarDivision').prop('disabled', false).html('<i class="fa-solid fa-trash"></i> Eliminar');
                }
            });
        });

        $('#btnConfirmarNuevaDivision').on('click', function(){
            const monto = $('#montoNuevaDivision').val();
            const payload = { _token: '{{ csrf_token() }}' };
            if (monto !== '' && !isNaN(parseFloat(monto))) {
                payload.monto = parseFloat(monto);
            }
            $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Agregando...');
            $.ajax({
                url: '{{ route("proyectos.agregar-division-siguiente", ["idServicio" => "__ID__"]) }}'.replace('__ID__', idServicioProyecto),
                method: 'POST',
                data: payload,
                success: function(resp){
                    if (resp && resp.success) {
                        $('#modalMontoNuevaDivision').modal('hide');
                        Swal.fire({ icon: 'success', title: 'Listo', text: resp.message }).then(()=>{ window.location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: (resp && resp.message) ? resp.message : 'No se pudo agregar la división.' });
                    }
                },
                error: function(xhr){
                    let msg = 'Error al agregar la división';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                },
                complete: () => {
                    $('#btnConfirmarNuevaDivision').prop('disabled', false).html('<i class="fa-solid fa-save"></i> Agregar');
                }
            });
        });

        // Validación de archivo
        $('#ruta_documento').on('change', function() {
            const file = this.files[0];
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            if (file && file.size > maxSize) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Archivo muy grande',
                    text: 'El archivo no debe superar los 10MB'
                });
                this.value = '';
            }
        });

        // Ingreso de Proyecto
        window.abrirModalIngresoProyecto = function(idProyecto){
            $('#id_proyecto_ingreso').val(idProyecto || '');
            $('#tipo_ingreso_proy').val('');
            $('#monto_ingreso_proy').val('');
            $('#contenedorSuministroProy').hide();
            $('#detalle_existencias_container_proy').hide();
            $('#modalIngresoProyecto').modal('show');
        };

        $('#tipo_ingreso_proy').on('change', function(){
            if ($(this).val() === 'SUMINISTRO') {
                $('#contenedorSuministroProy').show();
                cargarProductosExistenciaProyecto('');
                $('#monto_ingreso_proy').prop('readonly', true);
                setTimeout(function(){ $('#producto_suministro_proy').trigger('change'); }, 100);
                $('#contenedorManoObraProy').hide();
            } else {
                $('#contenedorSuministroProy').hide();
                $('#monto_ingreso_proy').prop('readonly', false);
                if ($(this).val() === 'MANO DE OBRA') {
                    $('#contenedorManoObraProy').show();
                } else {
                    $('#contenedorManoObraProy').hide();
                }
            }
        });
        // Switch por puesto (modal ingreso proyecto)
        $('#switchPorPuestoProy').on('change', function(){
            if ($(this).is(':checked')) {
                $('#wrapEmpleadoProy').hide();
                $('#wrapPuestoProy').show();
            } else {
                $('#wrapPuestoProy').hide();
                $('#wrapEmpleadoProy').show();
            }
        });

        // Mano de obra: popular empleados (sin autollenar salario)
        const empleadosActivos = @json(isset($EmpleadosActivos) ? $EmpleadosActivos : []);
        // Reutilizar secciones de mano de obra existentes: al seleccionar MANO DE OBRA para proyecto, mostrar contenedorManoObra y permitir elegir empleado
        $('#tipo_ingreso_proy').on('change', function(){
            if ($(this).val() === 'MANO DE OBRA') {
                $('#contenedorManoObraProy').show();
                const sel = $('#empleadoLocalProy');
                if (sel.children('option').length <= 1 && Array.isArray(empleadosActivos)) {
                    empleadosActivos.forEach(function(emp){
                        if (!sel.find(`option[value="${emp.id}"]`).length) {
                            sel.append(`<option value="${emp.id}">${emp.Nombre || (emp.primer_nombre || '')}</option>`);
                        }
                    });
                }
            } else {
                // Ocultar mano de obra si no aplica en este modal
                $('#contenedorManoObraProy').hide();
            }
        });

        function cargarProductosExistenciaProyecto(q){
            $.ajax({
                url: '{{ route('proyectos.productos-existencias') }}',
                method: 'GET',
                data: { q: q || '' },
                success: function(list){
                    const sel = $('#producto_suministro_proy');
                    sel.empty();
                    sel.append('<option value="">Seleccionar producto...</option>');
                    if (Array.isArray(list) && list.length) {
                        list.forEach(function(p){
                            const disp = (p.disponible ?? p.disponible_total ?? 0);
                            sel.append(`<option data-precio="${p.precio_unitario}" data-existencia="${disp}" value="${p.id}">${p.nombre} - ${p.descripcion} (Disp: ${disp})</option>`);
                        });
                        sel.prop('selectedIndex', 1).trigger('change');
                        actualizarMontoIngresoProyecto();
                    } else {
                        $('#info_existencia_proy').text('');
                        sel.append('<option value="" disabled>No hay productos con existencia</option>');
                        $('#monto_ingreso_proy').val('0.00');
                    }
                },
                error: function(){
                    const sel = $('#producto_suministro_proy');
                    sel.empty();
                    sel.append('<option value="" disabled>Error cargando productos</option>');
                }
            });
        }

        function actualizarMontoIngresoProyecto(){
            const opt = $('#producto_suministro_proy').find('option:selected');
            const precio = parseFloat(opt && opt.data('precio')) || 0;
            // Mostrar el precio unitario del producto como monto del ingreso (proyecto)
            $('#monto_ingreso_proy').val(precio.toFixed(2));
        }

        $('#producto_suministro_proy').on('change', function(){
            const opt = $(this).find('option:selected');
            const existencia = parseFloat(opt.data('existencia')) || 0;
            $('#info_existencia_proy').text(existencia ? `Existencia disponible: ${existencia}` : '');
            let cantidad = parseInt($('#cantidad_suministro_proy').val() || '1');
            if (existencia && cantidad > existencia) {
                cantidad = existencia;
                $('#cantidad_suministro_proy').val(cantidad);
            }
            actualizarMontoIngresoProyecto();

            // detalle almacenes
            const productoId = parseInt(opt.val());
            if (productoId) {
                $.ajax({
                    url: '{{ route("proyectos.productos-existencias-detalle", ["productoId" => "__ID__"]) }}'.replace('__ID__', productoId),
                    method: 'GET',
                    success: function(resp){
                        const cont = $('#detalle_existencias_container_proy');
                        const tbody = $('#tbody_detalle_existencias_proy');
                        tbody.empty();
                        if (resp && resp.success && Array.isArray(resp.existencias) && resp.existencias.length) {
                            resp.existencias.forEach(function(row){
                                tbody.append(`<tr><td>${row.almacen}</td><td>${row.ubicacion}</td><td class="text-end">${parseFloat(row.disponible).toLocaleString('es-MX')}</td></tr>`);
                            });
                            cont.show();
                        } else { cont.hide(); }
                    },
                    error: function(){ $('#detalle_existencias_container_proy').hide(); }
                });
            } else { $('#detalle_existencias_container_proy').hide(); }
        });

        $('#cantidad_suministro_proy').on('input', function(){
            const opt = $('#producto_suministro_proy').find('option:selected');
            const existencia = parseFloat(opt.data('existencia')) || 0;
            let cantidad = parseInt($(this).val() || '1');
            if (existencia && cantidad > existencia) { cantidad = existencia; $(this).val(cantidad); }
            actualizarMontoIngresoProyecto();
        });

        $('#btnGuardarIngresoProyecto').on('click', function(){
            if (!$('#formIngresoProyecto')[0].checkValidity()) {
                $('#formIngresoProyecto')[0].reportValidity();
                return;
            }
            var formData = new FormData($('#formIngresoProyecto')[0]);
            // Si es mano de obra por puesto, anexar campos custom
            const tipoSel = ($('#tipo_ingreso_proy').val() || '').toUpperCase();
            const porPuesto = $('#switchPorPuestoProy').is(':checked');
            if (tipoSel === 'MANO DE OBRA' && porPuesto) {
                formData.append('por_puesto', '1');
                formData.append('puesto_id', $('#puestoProy').val() || '');
                formData.append('cantidad_trab_puesto', $('#cantidadTrabPuestoProy').val() || '');
            }
            formData.append('_token', '{{ csrf_token() }}');
            $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');
            $.ajax({
                url: '{{ route("proyectos.guardar-ingreso-proyecto") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response){
                    if (response.success) {
                        Swal.fire({ icon:'success', title:'¡Guardado!', text: response.message }).then(()=>{ window.location.reload(); });
                    } else {
                        Swal.fire({ icon:'error', title:'Error', text: response.message || 'No se pudo guardar el ingreso' });
                    }
                },
                error: function(xhr){
                    let msg = 'Error al guardar el ingreso';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({ icon:'error', title:'Error', text: msg });
                },
                complete: ()=>{ $('#btnGuardarIngresoProyecto').prop('disabled', false).html('<i class="fa-solid fa-save"></i> Guardar Ingreso'); }
            });
        });
    });
</script>
@endpush 