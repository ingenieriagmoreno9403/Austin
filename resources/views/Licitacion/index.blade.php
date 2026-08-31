@extends('layouts.app')
@section('content')
    @if ($mensaje = Session::get('successcalcular'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'Swal.fire({
                        title: "¡Comparativa calculada exitosamente!",
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

   


    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <style>
        .licitacion-filters {
            background: var(--table-soft, #f9fafb);
            border: 1px solid var(--table-border, #e5e7eb);
            margin-bottom: 0.85rem;
            padding: 0.55rem 0.75rem !important;
            border-radius: 14px !important;
            box-shadow: none;
        }
        .licitacion-filters form {
            gap: 0.35rem 0.65rem;
        }
        .licitacion-filters .filter-field {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex: 0 1 auto;
            max-width: none;
        }
        .licitacion-filters .form-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--table-muted, #6b7280);
            margin-bottom: 0;
            white-space: nowrap;
        }
        .licitacion-filters .form-select-sm {
            min-height: 28px;
            height: 28px;
            font-size: 0.78rem;
            padding: 0.15rem 0.4rem;
            width: auto;
            min-width: 120px;
            max-width: 200px;
            background: #ffffff !important;
            border: 1px solid var(--table-border, #e5e7eb) !important;
            border-radius: 10px !important;
            color: var(--table-text, #111827) !important;
        }
        .licitacion-filters .btn-sm {
            min-height: 28px;
            padding: 0.15rem 0.55rem;
            font-size: 0.78rem;
        }
        @media (max-width: 768px) {
            .licitacion-filters .filter-field {
                flex: 1 1 calc(50% - 0.5rem);
            }
            .licitacion-filters .form-select-sm {
                flex: 1;
                max-width: none;
            }
        }

        #modalNuevaComparativa .modal-content {
            border: 1px solid var(--table-border, #e5e7eb);
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
            overflow: hidden;
        }
        #modalNuevaComparativa .modal-header {
            border-bottom: 1px solid #eef2f7;
            padding: 1rem 1.25rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }
        #modalNuevaComparativa .modal-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.05rem;
            font-weight: 750;
            color: #0f172a;
        }
        #modalNuevaComparativa .modal-title-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #0284c7;
            font-size: 0.95rem;
        }
        #modalNuevaComparativa .modal-subtitle {
            margin: 0.15rem 0 0;
            font-size: 0.78rem;
            font-weight: 500;
            color: #64748b;
        }
        #modalNuevaComparativa .modal-body {
            padding: 1.15rem 1.25rem;
        }
        #modalNuevaComparativa .modal-footer {
            border-top: 1px solid #eef2f7;
            padding: 0.85rem 1.25rem;
            background: #f8fafc;
        }
        #modalNuevaComparativa .form-label {
            font-size: 0.78rem;
            font-weight: 650;
            color: #64748b;
            margin-bottom: 0.35rem;
        }
        #modalNuevaComparativa .form-control,
        #modalNuevaComparativa .form-select {
            border: 1px solid var(--table-border, #e5e7eb);
            border-radius: 10px;
            min-height: 38px;
            font-size: 0.9rem;
        }
        #modalNuevaComparativa .form-text {
            font-size: 0.72rem;
            color: #94a3b8;
        }
        #modalNuevaComparativa textarea.form-control {
            min-height: 72px;
        }

        .licitacion-acciones {
            white-space: nowrap;
        }
        .licitacion-acciones-row {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: nowrap;
        }
        .licitacion-acciones-row .btn {
            width: 2.15rem;
            height: 2.15rem;
            padding: 0;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.55rem;
            font-size: 0.85rem;
        }
    </style>

    <!-- pantalla principal -->
    <div class="container-fluid format_page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-scale-balanced"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Comparativa de Precios</h2>
                            <p class="text-muted mb-0">Administración de comparativa de la empresa</p>
                        </div>
                    </div>
                    <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevaComparativa">
                            <i class="fa-solid fa-plus"></i> Nueva Comparativa
                        </button>

                        {{-- <button class="btn btn-baseColor-light position-relative" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom2"
                            aria-controls="offcanvasBottom">
                            <i class="fa-solid fa-plus"></i> Crear desde Solicitud
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                {{ count($solicitudes_pendientes) }}+
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        </button> --}}
                    </div>
                </div>
            </div>
        </div>

        @php
            $totalComparativas = count($varlista);
            $activas = 0;
            $porVencer = 0;
            $adjudicadas = 0;
            $conOC = 0;
            $hoyResumen = \Carbon\Carbon::now();

            foreach ($varlista as $item) {
                $fechaLimite = \Carbon\Carbon::parse($item->fecha_limite);
                $diasRestantes = $hoyResumen->diffInDays($fechaLimite, false);
                $estadoItem = isset($item->estado) ? trim(strtolower($item->estado)) : '';
                $estaAdjudicadaItem = in_array($estadoItem, ['adjudicada', 'cerrada'], true);

                $tieneOC = DB::table('tblordencompra_enc')
                    ->where('referencia_licitacion_id', $item->id)
                    ->whereIn('estado', ['enviada', 'confirmada', 'en_proceso', 'completada', 'cerrada'])
                    ->exists();

                if ($tieneOC) {
                    $conOC++;
                }

                if ($estaAdjudicadaItem) {
                    $adjudicadas++;
                } elseif ($diasRestantes >= 0 && $diasRestantes <= 3) {
                    $porVencer++;
                } elseif ($diasRestantes >= 0) {
                    $activas++;
                }
            }
        @endphp

        <!-- Tarjetas resumen -->
        <div class="row g-3 compras-kpi-row">
            <div class="col-6 col-md">
                <div class="compras-kpi compras-kpi--secondary">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Total</span>
                        <span class="compras-kpi__value">{{ $totalComparativas }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="compras-kpi compras-kpi--success">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Activas</span>
                        <span class="compras-kpi__value">{{ $activas }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="compras-kpi compras-kpi--warning">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Por vencer</span>
                        <span class="compras-kpi__value">{{ $porVencer }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="compras-kpi compras-kpi--info">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Adjudicadas</span>
                        <span class="compras-kpi__value">{{ $adjudicadas }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="compras-kpi compras-kpi--secondary">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Con OC</span>
                        <span class="compras-kpi__value">{{ $conOC }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- tabla de licitaciones principal  --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-orange">Listado de Comparativas</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <div class="filters licitacion-filters">
                        <form id="filtroLicitaciones" class="d-flex flex-wrap align-items-center">
                            <div class="filter-field">
                                <label class="form-label" for="filtroEstado">Estado</label>
                                <select class="form-select form-select-sm" id="filtroEstado" aria-label="Filtrar por estado">
                                    <option value="all">Todas</option>
                                    <option value="activas">Activas</option>
                                    <option value="vencer">Por vencer</option>
                                    <option value="adjudicadas">Adjudicadas</option>
                                    <option value="con_oc">Con OC</option>
                                    <option value="vencidas">Vencidas</option>
                                </select>
                            </div>
                            <button type="button" class="btn btn-baseColor-light btn-sm flex-shrink-0" id="btnAplicarFiltros">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" id="btnLimpiarFiltros">
                                Limpiar
                            </button>
                        </form>
                    </div>

                    <table class="table table-striped table-hover display modern-table w-100" id="tableLicitaciones">
                        <thead>
                            <tr>
                                <th class="text-center">Acciones</th>
                                <th class="text-center">ID</th>
                                <th class="text-center">Folio</th>
                                <th class="text-center">Nombre</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Fecha Creación</th>
                                <th class="text-center">Fecha Límite</th>
                                <th class="text-center">Descripción</th>
                                <th class="text-center">Proveedores</th>
                            </tr>
                        </thead>

                    <tbody>
                        @foreach ($varlista as $vis)
                            @php
                                // Calcular estado y clase CSS
                                $fechaLimite = \Carbon\Carbon::parse($vis->fecha_limite);
                                $fechaCreacion = \Carbon\Carbon::parse($vis->fecha_creacion);
                                $hoy = \Carbon\Carbon::now();
                                $diasRestantes = $hoy->diffInDays($fechaLimite, false);
                                
                                $estadoClase = '';
                                $estadoTexto = '';
                                $filterClass = '';
                                
                                // Debug - Para ver el valor exacto de estado
                                $estadoActual = isset($vis->estado) ? $vis->estado : 'no definido';
                                $estaAdjudicada = false;
                                
                                // Comprobar específicamente si está adjudicada o cerrada (comparando string)
                                if (isset($vis->estado) && (trim(strtolower($vis->estado)) === 'adjudicada' || trim(strtolower($vis->estado)) === 'cerrada')) {
                                    $estaAdjudicada = true;
                                }
                                
                                // Verificar si tiene orden de compra asociada en estado "enviada" o posterior
                                $tieneOrdenCompra = DB::table('tblordencompra_enc')
                                    ->where('referencia_licitacion_id', $vis->id)
                                    ->whereIn('estado', ['enviada', 'confirmada', 'en_proceso', 'completada', 'cerrada'])
                                    ->exists();
                                
                                // Lógica de estados - Con prioridad para adjudicada o cerrada
                                if ($estaAdjudicada) {
                                    $estadoTextoBase = isset($vis->estado) && trim(strtolower($vis->estado)) === 'cerrada' ? 'Cerrada' : 'Adjudicada';
                                    
                                    if ($tieneOrdenCompra) {
                                        $estadoClase = 'bg-purple text-white';
                                        $estadoTexto = $estadoTextoBase . ' (Con OC)';
                                        $filterClass = 'con_oc';
                                    } else {
                                        $estadoClase = 'bg-info text-white';
                                        $estadoTexto = $estadoTextoBase;
                                        $filterClass = 'adjudicadas';
                                    }
                                } 
                                // Solo aplicar lógica de fecha si no está adjudicada
                                elseif ($diasRestantes < 0) {
                                    $estadoClase = 'bg-danger text-white';
                                    $estadoTexto = 'Vencida';
                                    $filterClass = 'vencidas';
                                } elseif ($diasRestantes <= 3) {
                                    $estadoClase = 'bg-warning';
                                    $estadoTexto = 'Por Vencer';
                                    $filterClass = 'vencer';
                                } else {
                                    $estadoClase = 'bg-success text-white';
                                    $estadoTexto = 'Activa';
                                    $filterClass = 'activas';
                                }
                                
                                // Contar proveedores participantes
                                $proveedores = DB::table('tbllicitacion_proveedor')
                                    ->where('licitacion_id', $vis->id)
                                    ->count();
                            @endphp
                            
                            <tr class="filter-row {{ $filterClass }}">
                                <td class="text-center licitacion-acciones">
                                    <div class="licitacion-acciones-row">
                                        {{-- <a class="btn btn-baseColor btn-sm" href="{{ route('licitaciones.show', $vis->id) }}" title="Ver Datos de Comparativa">
                                            <i class="fa-solid fa-eye"></i>
                                        </a> --}}
                                        <a class="btn btn-primary btn-sm" title="Editar Comparativa"
                                            href="{{ route('licitaciones.editar', $vis->id) }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a class="btn btn-baseColor btn-sm" title="Comparar Precios"
                                            href="{{ route('licitaciones.comparar', $vis->id) }}">
                                            <i class="fa-solid fa-scale-balanced"></i>
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#modalCostos{{ $vis->id }}" title="Ver Costos por Proveedor">
                                            <i class="fa-solid fa-money-bill-wave"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#cancelModal" data-id="{{ $vis->id }}" title="Eliminar Comparativa">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-center">{{ $vis->id }}</td>
                                <td class="text-center text-truncate">{{ $vis->folio }}</td>
                                <td>{{ $vis->nombre }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $estadoClase }}">{{ $estadoTexto }}</span>
                                </td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($vis->fecha_creacion)->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    {{ \Carbon\Carbon::parse($vis->fecha_limite)->format('d/m/Y') }}
                                    @if ($diasRestantes >= 0 && $diasRestantes <= 3 && !$estaAdjudicada)
                                        <span class="badge bg-warning text-dark">{{ $diasRestantes }} días</span>
                                    @elseif ($diasRestantes < 0 && !$estaAdjudicada)
                                        <span class="badge bg-danger">Vencida</span>
                                    @endif
                                </td>
                                <td>
                                    @if (strlen($vis->descripcion_detalle) > 50)
                                        {{ substr($vis->descripcion_detalle, 0, 50) }}...
                                        <a href="#" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $vis->descripcion_detalle }}">
                                            <i class="fa-solid fa-info-circle"></i>
                                        </a>
                                    @else
                                        {{ $vis->descripcion_detalle }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $proveedores }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Costos de esta Comparativa -->
    @foreach ($varlista as $vis)
        <div class="modal fade" id="modalCostos{{ $vis->id }}" tabindex="-1"
            aria-labelledby="modalCostosLabel{{ $vis->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h6 class="modal-title" id="modalCostosLabel{{ $vis->id }}">
                            Costos Capturados - Comparativa #{{ $vis->id }}
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body" style="margin-top: -20px">
                        @php
                            $costos = DB::table('tbllicitacionproducto_proveedor as lpp')
                                ->join('tblprovedores as p', 'p.id', '=', 'lpp.proveedor_id')
                                ->join('tblproductos as pr', 'pr.id', '=', 'lpp.producto_id')
                                ->select('p.nombre as proveedor', 'pr.nombre as producto', 'lpp.costo', 'lpp.marca')
                                ->where('lpp.licitacion_id', $vis->id)
                                ->orderBy('proveedor')
                                ->orderBy('producto')
                                ->get();
                                
                            // Agrupar por proveedor
                            $costosPorProveedor = $costos->groupBy('proveedor');
                            
                            // Calcular total por proveedor
                            $totalesPorProveedor = [];
                            foreach ($costosPorProveedor as $proveedor => $items) {
                                $totalesPorProveedor[$proveedor] = $items->sum('costo');
                            }
                        @endphp

                        @if ($costos->isEmpty())
                            <div class="alert alert-warning">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                No hay costos registrados para esta Comparativa.
                            </div>
                        @else
                            <!-- Resumen de proveedores -->
                            <div class="mb-4">
                                <h6 class="mb-4"><i class="text-orange fa-solid fa-chart-pie me-2"></i>Resumen por Proveedor</h6>
                                <div class="row">
                                    @foreach ($costosPorProveedor as $proveedor => $items)
                                    <div class="col-md-4 mb-2">
                                        <div class="card">
                                            <div class="card-body py-2">
                                                <h6 class="card-title mb-0">{{ $proveedor }}</h6>
                                                <p class="mb-0 text-muted">{{ $items->count() }} productos</p>
                                                <h5 class="text-primary">${{ number_format($totalesPorProveedor[$proveedor], 2) }}</h5>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Tabla detallada -->
                            <h6><i class="fa-solid fa-list me-2 text-orange"></i>Detalle de Productos</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Proveedor</th>
                                            <th>Producto</th>
                                            <th class="text-end">Costo</th>
                                            <th>Marca</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($costos as $costo)
                                            <tr>
                                                <td>{{ $costo->proveedor }}</td>
                                                <td>{{ $costo->producto }}</td>
                                                <td class="text-end">${{ number_format($costo->costo, 2) }}</td>
                                                <td>{{ $costo->marca }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-baseColor-light" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
                        @if (!$costos->isEmpty())
                        <a href="{{ route('licitaciones.exportarCostos', $vis->id) }}" class="btn btn-baseColor">
                            <i class="fa-solid fa-file-excel me-1"></i> Exportar a Excel
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach


    <!-- Modal preguntar eliminado-->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="modalDeleteForm">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar Comparativa
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center p-4">
                        <i class="fa-solid fa-trash fa-3x text-danger mb-3"></i>
                        <h5 class="text-dark">¿Está seguro que desea eliminar esta Comparativa?</h5>
                        <p class="text-muted">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark me-1"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-trash me-1"></i> Eliminar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal Nueva Comparativa -->
    <div class="modal fade" id="modalNuevaComparativa" tabindex="-1" aria-labelledby="modalNuevaComparativaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('licitaciones.store') }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-header">
                        <div class="modal-title" id="modalNuevaComparativaLabel">
                            <span class="modal-title-icon" aria-hidden="true">
                                <i class="fa-solid fa-scale-balanced"></i>
                            </span>
                            <div>
                                <div>Nueva Comparativa</div>
                                <p class="modal-subtitle">Captura la información general para iniciar la comparativa.</p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="folio">Folio <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="folio" id="folio" minlength="3"
                                    maxlength="10" placeholder="Ej: COT-192" required>
                                <div class="form-text">Folio único de la comparativa</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="nombre">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nombre" id="nombre"
                                    placeholder="Ej: Compra de equipos informáticos" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="descripcion_detalle">Descripción</label>
                                <textarea class="form-control" id="descripcion_detalle" name="descripcion_detalle"
                                    rows="3" placeholder="Detalles de la comparativa"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="fecha_creacion">Fecha de inicio <span class="text-danger">*</span></label>
                                <input type="date" id="fecha_creacion" name="fecha_creacion"
                                    class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="fecha_limite">Fecha límite <span class="text-danger">*</span></label>
                                <input type="date" id="fecha_limite" name="fecha_limite" class="form-control" required>
                                <div class="form-text">Fecha límite para recepción de ofertas</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button class="btn btn-baseColor" type="submit">
                            <i class="fa-solid fa-check me-1"></i>Crear Comparativa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Insertar Modal Solicitudes-->
    <div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasBottom2" aria-labelledby="offcanvasBottomLabel"
        style="height:80vh">
        <div class="offcanvas-header bg-light">
            <h5 class="offcanvas-title" id="offcanvasBottomLabel">
                <i class="fa-solid fa-plus me-2"></i>Nueva Comparativa desde Solicitud
            </h5>
            
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body">
            <div class="container-fluid">
                <form action="{{ route('licitaciones.storeSolicitud') }}" method="POST" class="form g-3 needs-validation"
                    novalidate>
                    @csrf
                    
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fa-solid fa-clipboard-list me-2"></i>Información General</h5>
                        </div>
                        <div class="card-body">

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <!-- solicitud -->
                                    <div class="form-outline">
                                        <label class="form-label" for="nombre">Seleccione Solicitud</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-file-import"></i></span>
                                            <select name="id_servicio" id="id_servicio" class="form-select" required>
                                                <option value="" selected>Selccione ...</option>
                                                @foreach($solicitudes_pendientes as $key)
                                                    <option value="{{$key->id}}">{{$key->folio}} &nbsp;&nbsp;{{$key->nombre}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-text">Seleccione la solicitud que desea aplicar</div>
                                    </div>
                                </div>

                                {{-- <div class="col-md-6">
                                    <!-- folio -->
                                    <div class="form-outline">
                                        <label class="form-label" for="nombre">Folio</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-hashtag"></i></span>
                                            <input type="text" class="form-control" name="folio" id="folio" minlength="3"
                                            maxlength="10" placeholder="Ej: COT-192" required />
                                        </div>
                                        <div class="form-text">Ingrese un folio único</div>
                                    </div>
                                </div> --}}
                            </div>
                      
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <!-- nombre -->
                                    <div class="form-outline">
                                        <label class="form-label" for="nombre">Nombre de Comparativa</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-tag"></i></span>
                                            <input type="text" class="form-control" name="nombre" id="nombre"
                                                placeholder="Ej: Compra de equipos informáticos" required />
                                        </div>
                                        <div class="form-text">Ingrese un nombre descriptivo para la comparativa</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- descripcion detalle -->
                                    <div class="form-outline">
                                        <label class="form-label" for="descripcion_detalle">Descripción</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-align-left"></i></span>
                                            <textarea class="form-control" id="descripcion_detalle" name="descripcion_detalle" 
                                                rows="1" placeholder="Detalles de la Comparativa"></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- fecha creacion-->
                                    <div class="form-outline">
                                        <label class="form-label" for="fecha_creacion">Fecha de Inicio</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-calendar"></i></span>
                                            <input type="date" id="fecha_creacion" name="fecha_creacion"
                                                class="form-control" value="{{ date('Y-m-d') }}" required />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- fecha limite-->
                                    <div class="form-outline">
                                        <label class="form-label" for="fecha_limite">Fecha Límite</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fa-solid fa-calendar-check"></i></span>
                                            <input type="date" id="fecha_limite" name="fecha_limite" 
                                                class="form-control" required />
                                        </div>
                                        <div class="form-text">Fecha límite para recepción de ofertas</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Guardar Comparativa -->
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">
                            <i class="fa-solid fa-xmark me-1"></i>Cancelar
                        </button>
                        <button class="btn btn-primary" type="submit">
                            <i class="fa-solid fa-check me-1"></i>Crear Comparativa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Modal de eliminación
            const modal = document.getElementById('cancelModal');
            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const form = document.getElementById('modalDeleteForm');
                const route = "{{ route('licitaciones.destroy', ':id') }}".replace(':id', id);
                form.setAttribute('action', route);
            });

            // Filtrado de licitaciones
            const filterRows = document.querySelectorAll('.filter-row');
            const filtroEstado = document.getElementById('filtroEstado');
            const formFiltros = document.getElementById('filtroLicitaciones');

            function aplicarFiltroLicitaciones() {
                const filter = filtroEstado ? filtroEstado.value : 'all';
                filterRows.forEach(row => {
                    if (filter === 'all' || row.classList.contains(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            if (formFiltros) {
                formFiltros.addEventListener('submit', function(e) {
                    e.preventDefault();
                    aplicarFiltroLicitaciones();
                });
            }

            document.getElementById('btnAplicarFiltros')?.addEventListener('click', aplicarFiltroLicitaciones);

            document.getElementById('btnLimpiarFiltros')?.addEventListener('click', function() {
                if (filtroEstado) {
                    filtroEstado.value = 'all';
                }
                aplicarFiltroLicitaciones();
            });

            // Validación para fecha límite posterior a fecha inicio
            const fechaInicio = document.getElementById('fecha_creacion');
            const fechaLimite = document.getElementById('fecha_limite');
            
            if (fechaInicio && fechaLimite) {
                fechaInicio.addEventListener('change', function() {
                    fechaLimite.min = fechaInicio.value;
                    
                    if (fechaLimite.value && fechaLimite.value < fechaInicio.value) {
                        fechaLimite.value = fechaInicio.value;
                    }
                });
                
                // Establecer la fecha mínima al cargar la página
                fechaLimite.min = fechaInicio.value || new Date().toISOString().split('T')[0];
            }
        });
    </script>
@endsection
