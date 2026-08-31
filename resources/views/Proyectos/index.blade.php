@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/buscador-cliente.css') }}">
<link rel="stylesheet" href="{{ asset('css/modal-nuevo-proyecto.css') }}">
@endpush

@section('content')

<div class="container-fluid format_page">
    <!-- Encabezado -->
    <div class="row">
        <div class="col-lg-3 col-12 start-center">
            <h3 class="mt-1 animate_animated animate_backInLeft">Proyectos</h3>
            <span class="p-0 m-0 d-none d-md-block fs-8">Gestión y seguimiento de proyectos activos.</span>
        </div>

        <div class="col-lg-9 col-12 center-end">
            <button class="btn btn-baseColor fs-7 mb-2" type="button" data-bs-toggle="modal" data-bs-target="#modalCrear">
                <i class="fa-solid fa-plus"></i> Nuevo Proyecto
            </button>
            
            <a class="btn btn-baseColor-light fs-7 mb-2" href="{{ route('proyectos.reportes.rfq') }}">
                <i class="fa-solid fa-chart-pie"></i> Reportes por RFQ
            </a>
        </div>
    </div>

    <!-- Tarjetas resumen -->
    <div class="row mt-3 mb-3">
        <!-- Total de Borradores -->
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg_secondary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Total Proyectos</h6>
                            <h1 class=" p-3">
                                @php
                                    $totalProyectos = count($proyectos);
                                @endphp
                                {{ $totalProyectos }}
                            </h1>
                        </div>
                        <h1 class="fs-max-6 p-3 opacity-50">
                            <i class="fa-solid fa-project-diagram"></i>
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Valor Total</h6>
                            <h2 class="p-3 text-truncate">
                                @php
                                    $valorTotal = 0;
                                    foreach($proyectos as $proyecto) {
                                        $valorTotal += ($proyecto->monto_proyectado ?? 0);
                                    }
                                @endphp
                                {{ number_format($valorTotal, 2) }}
                            </h2>
                        </div>
                        <h1 class="fs-max-6 p-3 opacity-50">
                            <i class="fa-solid fa-dollar-sign"></i>
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Proyectos Urgentes</h6>
                            <h1 class="p-3">
                                @php
                                    $urgentes = 0;
                                    foreach($proyectos as $proyecto) {
                                        $fechaLimite = \Carbon\Carbon::parse($proyecto->fecha_limite);
                                        $hoy = \Carbon\Carbon::now();
                                        $diasRestantes = $hoy->diffInDays($fechaLimite, false);
                                        if ($diasRestantes <= 5 && $diasRestantes > 0) {
                                            $urgentes++;
                                        }
                                    }
                                @endphp
                                {{ $urgentes }}
                            </h1>
                        </div>
                        <h1 class="fs-max-6 p-3 opacity-50">
                            <i class="fa-solid fa-exclamation-triangle"></i>
                        </h1>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Proyectos Vencidos</h6>
                            <h1 class="p-3">
                                @php
                                    $vencidos = 0;
                                    foreach($proyectos as $proyecto) {
                                        $fechaLimite = \Carbon\Carbon::parse($proyecto->fecha_limite);
                                        $hoy = \Carbon\Carbon::now();
                                        $diasRestantes = $hoy->diffInDays($fechaLimite, false);
                                        if ($diasRestantes <= 0) {
                                            $vencidos++;
                                        }
                                    }
                                @endphp
                                {{ $vencidos }}
                            </h1>
                        </div>
                        <h1 class="fs-max-6 p-3 opacity-50">
                            <i class="fa-solid fa-calendar-times"></i>
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Proyectos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-list"></i> Lista de Proyectos
                    </h5>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            <strong>División:</strong> 
                            <span class="badge bg-success me-2">División Creada</span> = Ya tiene divisiones configuradas, 
                            <span class="badge bg-warning me-2">Pendiente</span> = Sin divisiones configuradas
                        </small>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaProyectos">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th># Contrato</th>
                                    <th>Nombre</th>
                                    <th>Cliente</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Límite</th>
                                    <th>Total Proyecto</th>
                                    <th>Monto Utilidad</th>
                                    <th>RFQ</th>
                                    <th>División</th>
                                    <th>Avance</th>
                                    <th>Estado</th>
                                    <th>Estado Proyecto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($proyectos as $proyecto)
                                    @php
                                        $fechaLimite = \Carbon\Carbon::parse($proyecto->fecha_limite);
                                        $fechaInicio = \Carbon\Carbon::parse($proyecto->fecha_inicio);
                                        $hoy = \Carbon\Carbon::now();
                                        $diasRestantes = $hoy->diffInDays($fechaLimite, false);
                                        
                                        if ($diasRestantes <= 0) {
                                            $estado = 'Vencido';
                                            $estadoClass = 'danger';
                                        } elseif ($diasRestantes <= 5) {
                                            $estado = 'Urgente';
                                            $estadoClass = 'warning';
                                        } elseif ($diasRestantes <= 15) {
                                            $estado = 'Próximo';
                                            $estadoClass = 'info';
                                        } else {
                                            $estado = 'En Tiempo';
                                            $estadoClass = 'success';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">{{ $proyecto->id_servicio }}</span>
                                        </td>
                                       
                                        <td>
                                            <span class="badge bg-secondary">{{ $proyecto->folio }}</span>
                                        </td>

                                        <td>
                                            {{ $proyecto->nombre }}
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex align-items-center justify-content-center me-2">
                                                    <i class="text-primary fa-solid fa-user"></i>
                                                </div>
                                                <span class="fw-medium">{{ $proyecto->cliente }}</span>
                                            </div>
                                        </td>

                                        <td class="text-truncate">
                                            <i class="fa-solid fa-calendar-day text-muted me-1"></i>
                                            {{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d/m/Y') }}
                                        </td>
                                        <td class="text-truncate">
                                            <i class="fa-solid fa-calendar-check text-muted me-1"></i>
                                            {{ \Carbon\Carbon::parse($proyecto->fecha_limite)->format('d/m/Y') }}
                                        </td>

                                        <td>
                                            <span class="fw-bold text-success">
                                                ${{ number_format(($proyecto->monto_proyectado ?? 0), 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-primary">
                                                ${{ number_format(($proyecto->monto_utilidad ?? 0), 2) }}
                                            </span>
                                        </td>
                                        
                                        <td class="text-truncate" style="max-width:160px;" title="{{ $proyecto->rfq }}">
                                            {{ $proyecto->rfq }}
                                        </td>
                                        <td>
                                            @if($proyecto->tiene_divisiones)
                                                <span class="badge bg-success">
                                                    <i class="fa-solid fa-check me-1"></i>División Creada
                                                </span>
                                            @else
                                                <span class="badge bg-warning">
                                                    <i class="fa-solid fa-clock me-1"></i>Pendiente
                                                </span>
                                            @endif
                                        </td>
                                        <td style="min-width:190px;">
                                            @php
                                                $costeado = (float) ($proyecto->total_costeado ?? 0);
                                                $gastado = (float) ($proyecto->total_gastado ?? 0);
                                                $pctGasto = $costeado > 0 ? round(min(100, ($gastado / $costeado) * 100)) : 0;
                                                $barClass = $pctGasto >= 100 ? 'bg-danger' : ($pctGasto >= 75 ? 'bg-warning' : 'bg-success');
                                            @endphp
                                            @if($costeado > 0)
                                                <div class="d-flex flex-column">
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar {{ $barClass }}" role="progressbar" style="width: {{ $pctGasto }}%;" aria-valuenow="{{ $pctGasto }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <small class="text-muted mt-1">${{ number_format($gastado, 2) }} de ${{ number_format($costeado, 2) }} ({{ $pctGasto }}%)</small>
                                                </div>
                                            @else
                                                <small class="text-muted">Sin costo dividido</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $estadoClass }}">
                                                {{ $estado }}
                                                @if($diasRestantes > 0)
                                                    ({{ $diasRestantes }} días)
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $estadoProyecto = $proyecto->estado_servicio ?? '';
                                                $estadoProyectoClass = 'secondary';
                                                if ($estadoProyecto === 'EN PROCESO') { $estadoProyectoClass = 'primary'; }
                                                elseif ($estadoProyecto === 'EN ESPERA') { $estadoProyectoClass = 'warning'; }
                                                elseif ($estadoProyecto === 'BORRADOR') { $estadoProyectoClass = 'secondary'; }
                                                elseif ($estadoProyecto === 'CIERRE') { $estadoProyectoClass = 'secondary'; }
                                            @endphp
                                            <span class="badge bg-{{ $estadoProyectoClass }}">{{ $estadoProyecto }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if($proyecto->tiene_divisiones)
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                            disabled
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="División ya creada - Ver detalles para gestionar partidas">
                                                        <i class="fa-solid fa-sitemap"></i>
                                                    </button>
                                                @else
                                                    @php $permitirDivision = in_array(($proyecto->estado_servicio ?? ''), ['EN PROCESO','BORRADOR']); @endphp
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#modalDivisionProyecto"
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="División de Proyecto"
                                                            onclick="{{ $permitirDivision ? "abrirModalDivision($proyecto->monto_proyectado, $proyecto->id_servicio)" : 'Swal.fire({icon:\'warning\',title:\'No permitido\',text:\'El proyecto debe estar EN PROCESO o BORRADOR para poder dividirse.\'})' }}"
                                                            {{ $permitirDivision ? '' : 'disabled' }}>
                                                        <i class="fa-solid fa-sitemap"></i>
                                                    </button>
                                                @endif
                                                @php $estadoProyectoAccion = $proyecto->estado_servicio ?? ''; @endphp
                                                @if($proyecto->tiene_divisiones)
                                                <button type="button" class="btn btn-sm btn-outline-success d-none" 
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="Indicadores de Proyecto"
                                                            onclick="verIndicadoresProyecto({{ $proyecto->id_servicio }})"
                                                            {{ $estadoProyectoAccion === 'BORRADOR' ? 'disabled' : '' }}>
                                                        <i class="fa-solid fa-chart-line"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="Montos máximos por tipo"
                                                            onclick="openModalMaximos({{ $proyecto->id_servicio }}, '{{ $proyecto->folio }}', '{{ addslashes($proyecto->nombre) }}')">
                                                        <i class="fa-solid fa-sliders"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="Ver Detalles"
                                                            onclick="verDetallesProyecto({{ $proyecto->id_servicio }})"
                                                            {{ $estadoProyectoAccion === 'BORRADOR' ? 'disabled' : '' }}>
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                    <a href="{{ route('proyectos.cotizar', ['idServicio' => $proyecto->id_servicio]) }}" 
                                                    target="_blank"
                                                    class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="tooltip" data-bs-placement="top" 
                                                    title="Cotizar (PDF)">
                                                        <i class="fa-solid fa-file-pdf"></i>
                                                    </a>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-success d-none" disabled 
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="Debe crear divisiones para usar indicadores">
                                                        <i class="fa-solid fa-chart-line"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="Montos máximos por tipo"
                                                            onclick="openModalMaximos({{ $proyecto->id_servicio }}, '{{ $proyecto->folio }}', '{{ addslashes($proyecto->nombre) }}')">
                                                        <i class="fa-solid fa-sliders"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info" disabled 
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="Debe crear divisiones para ver detalles">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" disabled 
                                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                                            title="Debe crear divisiones para cotizar">
                                                        <i class="fa-solid fa-file-pdf"></i>
                                                    </button>
                                                @endif
                                                <div class="btn-group dropstart">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Cambiar estado">
                                                        <i class="fa-solid fa-toggle-on"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado({{ $proyecto->id_servicio }}, 'EN PROCESO')">En Proceso</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado({{ $proyecto->id_servicio }}, 'EN ESPERA')">En Espera</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado({{ $proyecto->id_servicio }}, 'BORRADOR')">Borrador</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="cambiarEstado({{ $proyecto->id_servicio }}, 'CIERRE')">Cierre</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    {{-- <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fa-solid fa-inbox fa-3x mb-3"></i>
                                                <h5>No hay proyectos disponibles</h5>
                                                <p>No se encontraron proyectos con el nivel de progreso especificado.</p>
                                            </div>
                                        </td>
                                    </tr> --}}
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Montos Máximos por Tipo -->
<div class="modal fade" id="modalMaximos" tabindex="-1" aria-labelledby="modalMaximosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalMaximosLabel"><i class="fa-solid fa-sliders"></i> Montos máximos por tipo de ingreso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <input type="hidden" id="inputProyectoId">
                    <div class="col-md-5">
                        <label class="form-label">Proyecto</label>
                        <input type="text" id="inputProyectoInfo" class="form-control" placeholder="-" disabled>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Tipo de ingreso</label>
                        <select id="selectTipoIngreso" class="form-select"></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Monto máximo</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" min="0" step="0.01" class="form-control" id="inputMontoMaximo" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-10">
                        <label class="form-label">Nota (opcional)</label>
                        <input type="text" class="form-control" id="inputNotaTipo" placeholder="Comentario u observación">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-success w-100" id="btnAgregarTipo"><i class="fa-solid fa-plus"></i> Agregar</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tablaMaximosTipos">
                        <thead class="table-dark">
                            <tr>
                                <th>Tipo</th>
                                <th class="text-end">Monto Máximo</th>
                                <th>Nota</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-muted">Costeo del proyecto: $<span id="lblCosteoProyecto">0.00</span></small>
                    <small class="text-muted">Suma configurada: $<span id="lblSumaConfigurada">0.00</span></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarMaximos"><i class="fa-solid fa-save"></i> Guardar</button>
            </div>
        </div>
    </div>
    </div>

<!-- Modal para nuevo proyecto -->
<div class="modal fade" id="modalCrear" tabindex="-1" aria-labelledby="modalCrearLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
         <!-- Header del modal sobrio -->
             <div class="modal-header border-bottom bg-light">
                 <div>
                     <h4 class="modal-title fw-semibold text-dark mb-1" id="modalCrearLabel">
                         <i class="fa-solid fa-plus-circle me-2 text-orange"></i>
                         Nuevo Proyecto
                     </h4>
                     <p class="text-muted mb-0 small">Complete la información para crear un nuevo proyecto</p>
                 </div>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
             </div>

            <div class="modal-body p-4">
                <form id="formNuevoProyecto" method="POST" action="{{ route('servicios.mcaptura', ['tipo' => 'Proyecto']) }}">
                    @csrf
                    <input type="hidden" name="tipo_cliente" value="existente">
                    <input type="hidden" name="tipo_servicio" value="1">
                    
                    <!-- Sección 1: Información Básica del Proyecto -->
                     <div class="form-section mb-4">
                         <div class="row g-3">
                             <div class="col-md-6">
                                 <div class="form-group">
                                     <label for="folio" class="form-label fw-semibold text-dark mb-2">
                                         <i class="fa-solid fa-hashtag me-2 text-primary"></i>Número de Contrato
                                     </label>
                                     <input type="text" class="form-control" id="folio" name="folio" 
                                            placeholder="Ej: T000" minlength="1" maxlength="20" required>
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="form-group">
                                     <label for="nombre" class="form-label fw-semibold text-dark mb-2">
                                         <i class="fa-solid fa-project-diagram me-2 text-primary"></i>Nombre del Proyecto
                                     </label>
                                     <input type="text" class="form-control" id="nombre" name="nombre" 
                                            placeholder="Ej: Proyecto XYZ" required>
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="form-group">
                                     <label for="rfq" class="form-label fw-semibold text-dark mb-2">
                                         <i class="fa-solid fa-file-invoice me-2 text-primary"></i>RFQ
                                     </label>
                                     <input type="text" class="form-control" id="rfq" name="rfq" placeholder="Referencia RFQ (opcional)">
                                 </div>
                             </div>
                         </div>
                     </div>

                     <!-- Sección 2: Fechas del Proyecto -->
                     <div class="form-section mb-4">
                         <div class="row g-3">
                             <div class="col-md-6">
                                 <div class="form-group">
                                     <label for="fecha_inicio" class="form-label fw-semibold text-dark mb-2">
                                         <i class="fa-solid fa-play me-2 text-success"></i>Fecha de Inicio
                                     </label>
                                     <input type="date" class="form-control" id="fecha_inicio" name="fecha_ini" 
                                            value="{{ date('Y-m-d') }}" required>
                                 </div>
                             </div>
                             <div class="col-md-6">
                                 <div class="form-group">
                                     <label for="fecha_limite" class="form-label fw-semibold text-dark mb-2">
                                         <i class="fa-solid fa-flag-checkered me-2 text-success"></i>Fecha Límite
                                     </label>
                                     <input type="date" class="form-control" id="fecha_limite" name="fecha_fin" required>
                                 </div>
                             </div>
                         </div>
                     </div>

                     <!-- Sección 3: Responsables y Cliente -->
                     <div class="form-section mb-4">
                         <div class="row g-3">
                                                           <div class="col-md-6">
                                                                     <div class="form-group mb-4">
                                       <label for="vendedor" class="form-label fw-semibold text-dark mb-2">
                                           <i class="fa-solid fa-user-tie me-2 text-info"></i>Vendedor/Responsable
                                       </label>
                                       @livewire('buscador-empleado')
                                       <!-- Campo oculto para el ID del empleado seleccionado -->
                                       <input type="hidden" name="vendedor" id="vendedor_id" value="">
                                   </div>

                                 <div class="form-group">
                                    <label for="descripcion" class="form-label fw-semibold text-dark mb-2">
                                        <i class="fa-solid fa-edit me-2 text-warning"></i>Descripción del Proyecto
                                    </label>
                                    <textarea class="form-control" id="descripcion" name="descripcion"  rows="4" placeholder="Describa los detalles del proyecto..."></textarea>
                                </div>
                             </div>

                             <div class="col-md-6">
                                    <div class="form-group">
                                      
                                      @livewire('buscador-cliente')
                                  </div>
                             </div>
                         </div>
                     </div>

                    
                    <!-- Campos ocultos para cliente e id_atencion -->
                    <input type="hidden" name="cliente" id="cliente_id" value="">
                    <input type="hidden" name="id_atencion" id="id_atencion" value="">
                </form>
            </div>

            <!-- Footer del modal con botones modernos -->
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-baseColor shadow-sm" id="btnCrearProyecto">
                    <i class="fa-solid fa-rocket me-2"></i>Crear Proyecto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para División de Proyecto -->
<div class="modal fade" id="modalDivisionProyecto" tabindex="-1" aria-labelledby="modalDivisionProyectoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDivisionProyectoLabel">
                    <i class="fa-solid fa-sitemap"></i> División de Proyecto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formDivisionProyecto">
                    <input type="hidden" name="id_servicio" value="">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="costo_total" class="form-label">
                                <i class="fa-solid fa-dollar-sign"></i> Costo Total del Proyecto
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="costo_total" name="costo_total" 
                                       step="0.01" min="0" required placeholder="0.00" value="0">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="utilidad_porcentaje" class="form-label">
                                <i class="fa-solid fa-percent"></i> Utilidad (%)
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="utilidad_porcentaje" name="utilidad_porcentaje"
                                       step="0.01" min="0" max="100" placeholder="Ej: 10" value="0">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="num_partidas" class="form-label">
                                <i class="fa-solid fa-list-ol"></i> Número de Partidas
                            </label>
                            <input type="number" class="form-control" id="num_partidas" name="num_partidas" 
                                   min="1" max="50" required placeholder="Ej: 6">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="total_con_utilidad" class="form-label">
                                <i class="fa-solid fa-calculator"></i> Total con Utilidad
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control" id="total_con_utilidad" name="total_con_utilidad" readonly placeholder="0.00" value="0.00">
                            </div>
                            <small class="text-muted">Costo + (Costo × Utilidad%)</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <button type="button" class="btn btn-primary" id="btnCalcularDivision">
                                <i class="fa-solid fa-calculator"></i> Calcular División
                            </button>
                            <button type="button" class="btn btn-secondary" id="btnLimpiarDivision">
                                <i class="fa-solid fa-eraser"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Tabla Dinámica de División -->
                <div class="row mt-4" id="seccionTablaDivision" style="display: none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fa-solid fa-table"></i> División del Proyecto por Partidas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="tablaDivisionProyecto">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Partida</th>
                                                <th>Monto</th>
                                                <th>Porcentaje</th>
                                                <th>Nombre</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyDivisionProyecto">
                                            <!-- Los datos se generarán dinámicamente con JavaScript -->
                                        </tbody>
                                        <tfoot class="table-info">
                                            <tr>
                                                <td colspan="2" class="text-end fw-bold">Total:</td>
                                                <td class="fw-bold" id="totalCalculado">$0.00</td>
                                                <td class="fw-bold">100%</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGuardarDivision">
                    <i class="fa-solid fa-save"></i> Guardar División
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/division-proyecto.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
<script>
        // Modal Montos máximos por tipo
        let tiposDisponibles = [];
        let itemsMaximos = [];

        window.openModalMaximos = function(idProyecto, folio, nombre){
            $('#inputProyectoId').val(idProyecto);
            $('#inputProyectoInfo').val(`#${folio} - ${nombre}`);
            $('#modalMaximos').modal('show');
            $.get(`{{ url('/proyectos') }}/${idProyecto}/tipos-ingreso-maximos`, function(resp){
                if (resp && resp.success) {
                    tiposDisponibles = resp.tipos || [];
                    const sel = $('#selectTipoIngreso');
                    sel.empty();
                    sel.append('<option value="">Seleccionar tipo...</option>');
                    tiposDisponibles.forEach(function(t){ sel.append(`<option value="${t.id_tipo}">${t.nombre}</option>`); });
                    itemsMaximos = (resp.tipos || []).filter(t=> parseFloat(t.monto_maximo) > 0).map(t=>({ id_tipo_ingreso: t.id_tipo, nombre: t.nombre, monto_agregado: parseFloat(t.monto_maximo)||0, nota: t.nota||'' }));
                    renderTablaMaximos();
                    try {
                        const costeo = parseFloat(resp.costeo||0);
                        $('#lblCosteoProyecto').text(costeo.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                        actualizarSumaConfigurada();
                    } catch(e) {}
                }
            });
        };

        $('#btnAgregarTipo').on('click', function(){
            const idTipo = parseInt($('#selectTipoIngreso').val()||'');
            const monto = parseFloat($('#inputMontoMaximo').val()||'0');
            const nota = ($('#inputNotaTipo').val()||'').trim();
            if (!idTipo || isNaN(monto)) return;
            const tipo = (tiposDisponibles || []).find(t=> parseInt(t.id_tipo) === idTipo);
            if (!tipo) return;
            // Reemplazar si existe
            itemsMaximos = itemsMaximos.filter(x=> parseInt(x.id_tipo_ingreso)!==idTipo);
            itemsMaximos.push({ id_tipo_ingreso: idTipo, nombre: tipo.nombre, monto_agregado: monto, nota });
            $('#inputMontoMaximo').val('');
            $('#inputNotaTipo').val('');
            renderTablaMaximos();
        });

        function renderTablaMaximos(){
            const tbody = $('#tablaMaximosTipos tbody');
            tbody.empty();
            if (!itemsMaximos.length) {
                tbody.append('<tr><td colspan="4" class="text-center text-muted">Sin elementos</td></tr>');
                return;
            }
            itemsMaximos.forEach(function(it){
                tbody.append(`
                    <tr>
                        <td>${it.nombre}</td>
                        <td class="text-end">$${(it.monto_agregado||0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td>${it.nota||''}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-id="${it.id_tipo_ingreso}" onclick="eliminarItemMaximo(${it.id_tipo_ingreso})"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                `);
            });
            actualizarSumaConfigurada();
        }

        window.eliminarItemMaximo = function(idTipo){
            itemsMaximos = itemsMaximos.filter(x=> parseInt(x.id_tipo_ingreso)!==parseInt(idTipo));
            renderTablaMaximos();
        };

        function actualizarSumaConfigurada(){
            const suma = itemsMaximos.reduce((acc, it)=> acc + (parseFloat(it.monto_agregado)||0), 0);
            $('#lblSumaConfigurada').text(suma.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }

        $('#btnGuardarMaximos').on('click', function(){
            const idProyecto = parseInt($('#inputProyectoId').val()||'');
            if (!idProyecto) { Swal.fire({icon:'warning',title:'Proyecto inválido'}); return; }
            // Validación: suma de montos == costeo del proyecto
            const costeoTxt = ($('#lblCosteoProyecto').text()||'0').replace(/[^0-9.,-]/g,'');
            const costeo = parseFloat(costeoTxt.replace(/,/g,''))||0;
            const suma = itemsMaximos.reduce((acc, it)=> acc + (parseFloat(it.monto_agregado)||0), 0);
            if (Math.abs(suma - costeo) > 0.01) {
                Swal.fire({icon:'warning',title:'Montos no coinciden',html:`La suma de tipos ($${suma.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}) debe ser igual al costeo del proyecto ($${costeo.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}).`});
                return;
            }
            const payload = { _token: '{{ csrf_token() }}', items: itemsMaximos };
            $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Guardando...');
            $.ajax({
                url: `{{ url('/proyectos') }}/${idProyecto}/tipos-ingreso-maximos`,
                method: 'POST',
                data: payload,
                success: function(resp){
                    if (resp && resp.success) {
                        Swal.fire({icon:'success',title:'Guardado',timer:1200,showConfirmButton:false});
                        $('#modalMaximos').modal('hide');
                    } else {
                        Swal.fire({icon:'error',title:'Error',text:(resp && resp.message) ? resp.message : 'No se pudo guardar'});
                    }
                },
                error: function(xhr){
                    let msg = 'Error al guardar';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire({icon:'error',title:'Error',text:msg});
                },
                complete: ()=>{ $('#btnGuardarMaximos').prop('disabled', false).html('<i class="fa-solid fa-save"></i> Guardar'); }
            });
        });

    $(document).ready(function() {
        // Inicializar tooltips (proteger si bootstrap no est1 cargado)
        try {
            if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function') {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        } catch (e) {
            console.warn('Bootstrap no disponible para tooltips:', e);
        }

        // Cambiar estado del proyecto
        window.cambiarEstado = function(idServicio, estado) {
            $.ajax({
                url: '{{ route('proyectos.cambiar-estado') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_servicio: idServicio,
                    estado: estado
                },
                success: function(resp){
                    Swal.fire({ icon: 'success', title: 'Estado actualizado', timer: 1200, showConfirmButton: false });
                    // Refrescar para ver el cambio
                    setTimeout(() => window.location.reload(), 800);
                },
                error: function(xhr){
                    let msg = 'No se pudo actualizar el estado';
                    if (xhr.responseJSON && xhr.responseJSON.message) { msg = xhr.responseJSON.message; }
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                }
            });
        }

        // Event listener para el botón calcular
        $('#btnCalcularDivision').on('click', function() {
            calcularDivisionProyecto();
        });

        // Limpiar componente Livewire cuando se cierre el modal
        $('#modalCrear').on('hidden.bs.modal', function () {
            // Emitir evento para limpiar la selección del componente Livewire (Livewire v2)
            if (typeof Livewire !== 'undefined') {
                // En Livewire v2, usamos dispatchEvent
                window.dispatchEvent(new CustomEvent('limpiarSeleccion'));
            }
        });

        // Event listeners para el componente Livewire
        if (typeof window.clienteSeleccionadoListener === 'undefined') {
            window.clienteSeleccionadoListener = true;
            window.addEventListener('clienteSeleccionado', function (event) {
                console.log('Cliente seleccionado:', event.detail);
                // Actualizar el campo cliente del formulario
                $('#cliente_id').val(event.detail.cliente_id);
            });
        }

        if (typeof window.personaSeleccionadaListener === 'undefined') {
            window.personaSeleccionadaListener = true;
            window.addEventListener('personaSeleccionada', function (event) {
                console.log('Persona seleccionada:', event.detail);
                // Actualizar el campo id_atencion del formulario
                $('input[name="id_atencion"]').val(event.detail.persona_id);
            });
        }

        // Event listener para empleado seleccionado
        if (typeof window.empleadoSeleccionadoListener === 'undefined') {
            window.empleadoSeleccionadoListener = true;
            window.addEventListener('empleadoSeleccionado', function (event) {
                console.log('Empleado seleccionado:', event.detail);
                // Actualizar el campo vendedor del formulario
                $('input[name="vendedor"]').val(event.detail.empleado_id);
            });
        }

        // Event listener para el botón limpiar
        $('#btnLimpiarDivision').on('click', function() {
            $('#formDivisionProyecto')[0].reset();
            $('#seccionTablaDivision').hide();
            window.divisionData = [];
        });

        // Event listener para el botón guardar
        $('#btnGuardarDivision').on('click', function() {
            if (!window.divisionData || window.divisionData.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin Datos',
                    text: 'Primero debe calcular la división del proyecto.'
                });
                return;
            }

            // Validar suma == costo_total antes de confirmar
            // Validación con base en total con utilidad
            let totalRaw = ($('#total_con_utilidad').val() || '').toString();
            let totalSan = totalRaw.replace(/[^0-9.,-]/g, '');
            if (totalSan.indexOf(',') >= 0 && totalSan.indexOf('.') === -1) { totalSan = totalSan.replace(/,/g, '.'); } else { totalSan = totalSan.replace(/,/g, ''); }
            const costoTotal = parseFloat(totalSan) || 0;
            let suma = 0;
            if (window.divisionData && Array.isArray(window.divisionData.divisiones)) {
                window.divisionData.divisiones.forEach(function(div, idx) {
                    const input = document.getElementById('division_monto_' + (idx + 1));
                    const monto = input ? parseFloat(input.value) || 0 : (div.monto || 0);
                    suma += monto;
                });
            }
            if (Math.abs(suma - costoTotal) > 0.01) {
                Swal.fire({
                    icon: 'error',
                    title: 'Montos no coinciden',
                    html: `La suma de partidas ($${suma.toFixed(2)}) no coincide con el costo total del proyecto ($${costoTotal.toFixed(2)}). Ajuste los montos.`
                });
                return;
            }

            Swal.fire({
                title: '¿Guardar División?',
                text: '¿Está seguro de que desea guardar la división del proyecto?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, Guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tomar los nombres ingresados en cada fila y colocarlos en divisionData
                    if (window.divisionData && Array.isArray(window.divisionData.divisiones)) {
                        window.divisionData.divisiones = window.divisionData.divisiones.map(function(div, idx) {
                            const input = document.getElementById('division_nombre_' + (idx + 1));
                            return {
                                ...div,
                                nombre: input ? input.value : ''
                            };
                        });
                    }
                    // Enviar datos al servidor
                    $.ajax({
                        url: '{{ route("proyectos.guardar-division") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            ...window.divisionData
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Guardado!',
                                    text: response.message
                                }).then(() => {
                                    $('#modalDivisionProyecto').modal('hide');
                                    $('#formDivisionProyecto')[0].reset();
                                    $('#seccionTablaDivision').hide();
                                    window.divisionData = [];
                                    
                                    // Redirigir a la vista de divisiones
                                    if (response.id_servicio) {
                                        window.location.href = '{{ route("proyectos.divisiones") }}/' + response.id_servicio;
                                    } else {
                                        window.location.href = '{{ route("proyectos.divisiones") }}';
                                    }
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
                            let errorMessage = 'Error al guardar la división del proyecto';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage
                            });
                        }
                    });
                }
            });
        });

        // Event listener para botón Crear Proyecto: envía el formulario al backend
        $('#btnCrearProyecto').on('click', function() {
            const form = $('#formNuevoProyecto');
            
            // Obtener valores de los campos
            const cliente = $('#cliente_id').val();
            const vendedor = $('input[name="vendedor"]').val();
            const nombre = $('#nombre').val();
            const folio = $('#folio').val();
            const fechaInicio = $('#fecha_inicio').val();
            const fechaLimite = $('#fecha_limite').val();
            const idAtencion = $('input[name="id_atencion"]').val();
            
            // Validación de campos requeridos
            if (!cliente || !vendedor || !nombre || !folio || !fechaInicio || !fechaLimite) {
                let camposFaltantes = [];
                if (!cliente) camposFaltantes.push('Cliente');
                if (!vendedor) camposFaltantes.push('Vendedor/Responsable');
                if (!nombre) camposFaltantes.push('Nombre del Proyecto');
                if (!folio) camposFaltantes.push('Número de Contrato');
                if (!fechaInicio) camposFaltantes.push('Fecha de Inicio');
                if (!fechaLimite) camposFaltantes.push('Fecha Límite');
                
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Campos requeridos', 
                    html: `Complete los siguientes campos obligatorios:<br><br><strong>${camposFaltantes.join(', ')}</strong>` 
                });
                return;
            }
            
            // Validar que se haya seleccionado una persona de atención
            if (!idAtencion) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Persona de Atención Requerida', 
                    text: 'Debe seleccionar una persona de atención para el cliente.' 
                });
                return;
            }
            
            // Si todo está bien, enviar el formulario
            form.trigger('submit');
        });

        // Event listener para validar costo total
        $('#costo_total').on('input', function() {
            const valor = parseFloat($(this).val());
            if (valor < 0) {
                $(this).val(0);
            }
            actualizarTotalConUtilidad();
        });

        // Event listener para validar número de partidas
        $('#num_partidas').on('input', function() {
            const valor = parseInt($(this).val());
            const max = parseInt($(this).attr('max'));
            if (valor > max) {
                $(this).val(max);
            }
            if (valor < 1) {
                $(this).val(1);
            }
        });

        // Recalcular total con utilidad al cambiar utilidad
        $('#utilidad_porcentaje').on('input', function() {
            let v = parseFloat($(this).val());
            if (isNaN(v) || v < 0) v = 0;
            if (v > 100) v = 100;
            $(this).val(v);
            actualizarTotalConUtilidad();
        });

        // Función para abrir modal de división con el valor del proyecto
        window.abrirModalDivision = function(valorProyecto, idServicio) {
            // Llenar el campo costo_total con el valor del proyecto
            $('#costo_total').val(valorProyecto);
            actualizarTotalConUtilidad();
            
            // Limpiar otros campos del formulario
            $('#num_partidas').val('');
            
            // Ocultar la tabla de división si está visible
            $('#seccionTablaDivision').hide();
            window.divisionData = [];

            // Establecer el ID del servicio en el formulario
            $('input[name="id_servicio"]').val(idServicio);
        };

        // Función para redirigir a la vista de divisiones del proyecto específico
        window.verDetallesProyecto = function(idServicio) {
            window.location.href = '{{ route("proyectos.divisiones") }}/' + idServicio;
        };

        // Función para ver indicadores del proyecto
        window.verIndicadoresProyecto = function(idServicio) {
            window.location.href = `/proyectos/indicadores/${idServicio}`;
        };
    });

    // Calcula y muestra el total con utilidad = costo_total * (1 + utilidad/100)
    function actualizarTotalConUtilidad() {
        let costo = parseFloat($('#costo_total').val());
        if (isNaN(costo) || costo < 0) costo = 0;
        let util = parseFloat($('#utilidad_porcentaje').val());
        if (isNaN(util) || util < 0) util = 0;
        if (util > 100) util = 100;
        const total = costo * (1 + (util / 100));
        $('#total_con_utilidad').val(total.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    }
</script>
@endpush 