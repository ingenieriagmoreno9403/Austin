@extends('layouts.app')
@section('content')
    <style>
        .bg-purple {
            background-color: #6f42c1 !important; /* Púrpura de Bootstrap */
        }
        
        .btn-outline-purple {
            color: #6f42c1;
            border-color: #6f42c1;
        }
        
        .btn-outline-purple:hover, 
        .btn-outline-purple.active {
            color: #fff;
            background-color: #6f42c1;
            border-color: #6f42c1;
        }
    </style>

    @if ($mensaje = Session::get('success'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'Swal.fire({
                        title: "¡Operación exitosa!",
                        text: "Proceso realizado de forma correcta",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });';
                echo '
            </script>';
        @endphp
    @elseif($mensaje = Session::get('Errorpermisos'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'Swal.fire({
                        title: "¡No se encontro el permiso para efectuar la accion!",
                        text: "Comunicate al area de sistemas para validar permisos",
                        icon: "warning",
                        timer: 5000,
                        showConfirmButton: false
                    });';
                echo '
            </script>';
        @endphp
    @elseif($mensaje = Session::get('errorFechas'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'Swal.fire({
                        title: "¡Estas fechas no son validas!",
                        text: "La fecha ya se encuentra en sistema",
                        icon: "warning",
                        timer: 5000,
                        showConfirmButton: false
                    });';
                echo '
            </script>';
        @endphp
    @elseif($mensaje = Session::get('warningBD'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'Swal.fire({
                        title: "¡Fallo la acción!",
                        text: "Contacte a un superior para ver el error",
                        icon: "warning",
                        timer: 4000,
                        showConfirmButton: false
                    });';
                echo '
            </script>';
        @endphp
    @endif

    <div class="container-fluid format_page">
        <div class="row">
            <div class="col-lg-3 col-6 start-center">
                <h3 class="mt-1 animate_animated animate_backInLeft">Mis Licitaciones</h3>
                <span class="p-0 m-0 d-none d-md-block fs-8">Gestiona tus licitaciones activas y enviadas.</span>
            </div>

            <div class="col-lg-9 col-6 text-end">
                <!-- Podríamos añadir un botón o función aquí si es necesario -->
            </div>
        </div>

        <!-- Tarjetas resumen -->
        <div class="row mt-3">
            <div class="col mb-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Licitaciones Pendientes</h6>
                                <h2 class="mb-0 mt-2">
                                    @php
                                        $pendientes = collect($licitaciones_prov)->where('estado_provext', '!=', 'Enviada')->count();
                                        echo $pendientes;
                                    @endphp
                                </h2>
                            </div>
                            <i class="fa-solid fa-clipboard-list fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <span class="small text-white">Requieren tu atención</span>
                    </div>
                </div>
            </div>
            
            <div class="col mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Licitaciones Enviadas</h6>
                                <h2 class="mb-0 mt-2">
                                    @php
                                        $enviadas = collect($licitaciones_prov)->where('estado_provext', '=', 'Enviada')->count();
                                        echo $enviadas;
                                    @endphp
                                </h2>
                            </div>
                            <i class="fa-solid fa-paper-plane fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <span class="small text-white">Esperando respuesta</span>
                    </div>
                </div>
            </div>
            
            <div class="col mb-3">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Próximas a Vencer</h6>
                                <h2 class="mb-0 mt-2">
                                    @php
                                        $today = \Carbon\Carbon::now();
                                        $proximasVencer = collect($licitaciones_prov)
                                            ->where('estado_provext', '!=', 'Enviada')
                                            ->filter(function($item) use ($today) {
                                                $fechaLimite = \Carbon\Carbon::parse($item->fecha_limite);
                                                $diff = $today->diffInDays($fechaLimite, false);
                                                return $diff >= 0 && $diff <= 3;
                                            })
                                            ->count();
                                        echo $proximasVencer;
                                    @endphp
                                </h2>
                            </div>
                            <i class="fa-solid fa-hourglass-half fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <span class="small text-dark">Menos de 3 días</span>
                    </div>
                </div>
            </div>
            
            <div class="col mb-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Adjudicadas</h6>
                                <h2 class="mb-0 mt-2">
                                    @php
                                        $adjudicadas = collect($licitaciones_prov)
                                            ->where('proveedor_adjudicado_id', '=', $licitaciones_prov[0]->proveedor_id ?? 0)
                                            ->count();
                                        echo $adjudicadas;
                                    @endphp
                                </h2>
                            </div>
                            <i class="fa-solid fa-trophy fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <span class="small text-white">Ganadas a tu favor</span>
                    </div>
                </div>
            </div>
            
            <!-- Licitaciones con OC enviada -->
            <div class="col mb-3">
                <div class="card bg-purple text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Con Orden de Compra</h6>
                                <h2 class="mb-0 mt-2">
                                    @php
                                        $conOC = 0;
                                        $proveedorId = $licitaciones_prov[0]->proveedor_id ?? 0;
                                        
                                        foreach($licitaciones_prov as $item) {
                                            // Solo contar las adjudicadas a este proveedor
                                            if ($item->proveedor_adjudicado_id == $proveedorId) {
                                                // Verificar si tiene OC enviada
                                                $tieneOC = DB::table('tblordencompra_enc')
                                                    ->where('referencia_licitacion_id', $item->licitacion_id)
                                                    ->whereIn('estado', ['enviada', 'confirmada', 'en_proceso', 'completada', 'cerrada'])
                                                    ->exists();
                                                
                                                if ($tieneOC) {
                                                    $conOC++;
                                                }
                                            }
                                        }
                                        echo $conOC;
                                    @endphp
                                </h2>
                            </div>
                            <i class="fa-solid fa-file-invoice-dollar fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <span class="small text-white">Con pedido en proceso</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-3">
            <div class="card-header bg-light">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">Lista de Licitaciones</h5>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm filter-btn active" data-filter="all">Todas</button>
                            <button type="button" class="btn btn-outline-primary btn-sm filter-btn" data-filter="pendientes">Pendientes</button>
                            <button type="button" class="btn btn-outline-primary btn-sm filter-btn" data-filter="enviadas">Enviadas</button>
                            <button type="button" class="btn btn-outline-primary btn-sm filter-btn" data-filter="adjudicadas">Adjudicadas</button>
                            <button type="button" class="btn btn-outline-purple btn-sm filter-btn rounded-0" data-filter="con_oc">Con OC</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de licitaciones -->
        <div class="row mt-3">
            <div class="table-responsive">
                <table class="table table-striped table-hover display" id="table">
                    <thead>
                        <tr>
                            <th class="text-center">No.</th>
                            <th class="text-center">Nombre</th>
                            <th class="text-center">Fecha Inicio</th>
                            <th class="text-center">Fecha Límite</th>
                            <th class="text-center">Descripción</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Progreso</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($licitaciones_prov as $licitacion)
                            @php
                                // Calcular días restantes
                                $fechaLimite = \Carbon\Carbon::parse($licitacion->fecha_limite);
                                $today = \Carbon\Carbon::now();
                                $diasRestantes = $today->diffInDays($fechaLimite, false)+1;
                                
                                // Determinar clase para el estado
                                $estadoClass = '';
                                $filterClass = '';
                                
                                // Verificar si tiene orden de compra asociada en estado "enviada" o posterior
                                $tieneOrdenCompra = DB::table('tblordencompra_enc')
                                    ->where('referencia_licitacion_id', $licitacion->licitacion_id)
                                    ->whereIn('estado', ['enviada', 'confirmada', 'en_proceso', 'completada', 'cerrada'])
                                    ->exists();
                                
                                // Verificar si está adjudicada a este proveedor
                                $esAdjudicada = ($licitacion->proveedor_adjudicado_id == $licitacion->proveedor_id);
                                
                                if ($esAdjudicada) {
                                    if ($tieneOrdenCompra) {
                                        $estadoClass = 'bg-purple text-white';
                                        $estado = 'Con OC Enviada';
                                        $filterClass = 'con_oc adjudicadas';
                                    } else {
                                        $estadoClass = 'bg-info text-white';
                                        $estado = 'Adjudicada';
                                        $filterClass = 'adjudicadas';
                                    }
                                } elseif ($licitacion->estado_provext == 'Enviada') {
                                    $estadoClass = 'bg-success text-white';
                                    $estado = 'Enviada';
                                    $filterClass = 'enviadas';
                                } elseif ($diasRestantes < 0) {
                                    $estadoClass = 'bg-danger text-white';
                                    $estado = 'Vencida';
                                    $filterClass = 'pendientes';
                                } elseif ($diasRestantes <= 3) {
                                    $estadoClass = 'bg-warning';
                                    $estado = 'Por Vencer';
                                    $filterClass = 'pendientes';
                                } else {
                                    $estadoClass = 'bg-primary text-white';
                                    $estado = 'Pendiente';
                                    $filterClass = 'pendientes';
                                }
                                
                                // Calcular el progreso
                                $totalProductos = DB::table('tbllicitacion_det')
                                    ->where('licitacion_id', $licitacion->licitacion_id)
                                    ->count();
                                
                                $productosConPrecio = DB::table('tbllicitacionproducto_proveedor')
                                    ->where('licitacion_id', $licitacion->licitacion_id)
                                    ->where('proveedor_id', $licitacion->proveedor_id)
                                    ->where('costo', '>', 0)
                                    ->count();
                                
                                $porcentajeProgreso = $totalProductos > 0 ? ($productosConPrecio / $totalProductos) * 100 : 0;
                                
                                // Determinar clase para el progreso
                                $progresoClass = '';
                                if ($porcentajeProgreso == 100) {
                                    $progresoClass = 'bg-success';
                                } elseif ($porcentajeProgreso > 50) {
                                    $progresoClass = 'bg-primary';
                                } elseif ($porcentajeProgreso > 0) {
                                    $progresoClass = 'bg-warning';
                                } else {
                                    $progresoClass = 'bg-danger';
                                }
                            @endphp
                            
                            <tr class="filter-row {{ $filterClass }} {{ $esAdjudicada ? 'adjudicadas' : '' }}">
                                <td class="text-center fw-bold">{{ $licitacion->id }}</td>
                                <td>{{ $licitacion->nombre }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($licitacion->fecha_creacion)->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    {{ \Carbon\Carbon::parse($licitacion->fecha_limite)->format('d/m/Y') }}
                                    @if ($diasRestantes > 0 && $diasRestantes <= 3 && $licitacion->estado_provext != 'Enviada')
                                        <span class="badge bg-warning text-dark">{{ $diasRestantes }} días</span>
                                    @elseif ($diasRestantes <= 0 && $licitacion->estado_provext != 'Enviada')
                                        <span class="badge bg-danger">Vencida</span>
                                    @endif
                                </td>
                                <td>
                                    @if (strlen($licitacion->descripcion_detalle) > 50)
                                        {{ substr($licitacion->descripcion_detalle, 0, 50) }}...
                                        <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $licitacion->descripcion_detalle }}">
                                            <i class="fa-solid fa-info-circle"></i>
                                        </a>
                                    @else
                                        {{ $licitacion->descripcion_detalle }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $estadoClass }}">
                                        {{ $estado }}
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar {{ $progresoClass }}" role="progressbar" 
                                            style="width: {{ $porcentajeProgreso }}%;" 
                                            aria-valuenow="{{ $porcentajeProgreso }}" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            {{ round($porcentajeProgreso) }}%
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ $productosConPrecio }}/{{ $totalProductos }} productos</small>
                                </td>
                                <td class="text-center text-truncate">
                                    <div class="btn-group">
                                        <a href="/showlicitacion/{{$licitacion->licitacion_id}}"
                                            class="btn btn-primary btn-sm {{ $licitacion->estado_provext == 'Enviada' ? 'disabled' : '' }}"
                                            title="{{ $licitacion->estado_provext == 'Enviada' ? 'Licitación ya enviada' : 'Editar precios' }}">
                                            <i class="fa-solid fa-pencil"></i> Editar precios
                                        </a>
                                        
                                        @if ($licitacion->estado_provext != 'Enviada')
                                            <button type="button" 
                                                   class="btn btn-success btn-sm ms-1 btn-enviar-licitacion" 
                                                   data-id="{{ $licitacion->licitacion_id }}"
                                                   data-progreso="{{ $porcentajeProgreso }}"
                                                   {{ $porcentajeProgreso < 100 ? 'disabled' : '' }}
                                                   title="{{ $porcentajeProgreso < 100 ? 'Completa todos los productos primero' : 'Enviar licitación' }}">
                                                <i class="fa-solid fa-paper-plane"></i> Enviar
                                            </button>
                                        @endif
                                        
                                        @if ($esAdjudicada)
                                            <button type="button" class="btn btn-info btn-sm ms-1" data-bs-toggle="modal" data-bs-target="#xmlModal{{ $licitacion->id }}">
                                                <i class="fa-solid fa-file-invoice"></i> Factura
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Modal para subir XML -->
                            @if ($esAdjudicada)
                            <div class="modal fade" id="xmlModal{{ $licitacion->id }}" tabindex="-1" aria-labelledby="xmlModalLabel{{ $licitacion->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="xmlModalLabel{{ $licitacion->id }}">Subir factura XML</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('factura.procesar') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="licitacion_id" value="{{ $licitacion->licitacion_id }}">
                                            <div class="modal-body">
                                                <p>Sube el archivo XML de la factura para la licitación <strong>{{ $licitacion->nombre }}</strong>.</p>
                                                <div class="mb-3">
                                                    <label for="xmlFile{{ $licitacion->id }}" class="form-label">Archivo XML</label>
                                                    <input type="file" class="form-control" id="xmlFile{{ $licitacion->id }}" name="xml" accept=".xml" required>
                                                </div>
                                                <div class="alert alert-info">
                                                    <i class="fa-solid fa-info-circle"></i> Asegúrate de que el archivo XML cumpla con los requisitos fiscales y contenga toda la información necesaria.
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-primary">Procesar XML</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formulario oculto para enviar licitaciones -->
    <form id="form-enviar-licitacion" method="POST" style="display: none;">
        @csrf
    </form>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Filtrado de licitaciones
            const filterButtons = document.querySelectorAll('.filter-btn');
            const filterRows = document.querySelectorAll('.filter-row');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Quitar la clase active de todos los botones
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Añadir la clase active al botón actual
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    
                    // Mostrar u ocultar filas según el filtro
                    filterRows.forEach(row => {
                        if (filter === 'all') {
                            row.style.display = '';
                        } else if (row.classList.contains(filter)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
            
            // Configurar botones de enviar licitación
            const botonesEnviar = document.querySelectorAll('.btn-enviar-licitacion');
            const formEnviar = document.getElementById('form-enviar-licitacion');
            
            botonesEnviar.forEach(boton => {
                boton.addEventListener('click', function() {
                    const licitacionId = this.getAttribute('data-id');
                    const progreso = parseFloat(this.getAttribute('data-progreso'));
                    
                    if (progreso < 100) {
                        Swal.fire({
                            title: "Licitación incompleta",
                            text: "Debes completar el precio de todos los productos antes de enviar.",
                            icon: "warning"
                        });
                        return;
                    }
                    
                    Swal.fire({
                        title: "¿Estás seguro?",
                        text: "Una vez enviada la licitación no podrás modificar los precios.",
                        icon: "warning",
                        showCancelButton: true,
                        cancelButtonText: "Cancelar",
                        confirmButtonText: "Sí, enviar",
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Configurar y enviar el formulario
                            formEnviar.action = `/ext_licitaciones/enviar/${licitacionId}`;
                            formEnviar.submit();
                        }
                    });
                });
            });
        });
    </script>
@endsection
