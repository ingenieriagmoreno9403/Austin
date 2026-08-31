@extends('layouts.app')
@section('content')

<div class="container-fluid format_page">
    <!-- Encabezado -->
    <div class="row">
        <div class="col-lg-3 col-12 start-center">
            <h3 class="mt-1 animate_animated animate_backInLeft">Indicadores del Proyecto</h3>
            <span class="p-0 m-0 d-none d-md-block fs-8">Reporte detallado de ingresos por partidas.</span>
        </div>

        <div class="col-lg-9 col-12 center-end">
            <a href="{{ route('proyectos.index') }}" class="btn btn-secondary fs-7 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Volver a Proyectos
            </a>
            <button class="btn btn-baseColor fs-7 mb-2" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    <!-- Información del Proyecto -->
    <div class="row mt-3 mb-3">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="mb-1">Cliente</h6>
                            <h5 class="mb-0">{{ $proyecto->cliente }}</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Folio</h6>
                            <h5 class="mb-0">{{ $proyecto->folio }}</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Fecha Inicio</h6>
                            <h5 class="mb-0">{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d/m/Y') }}</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Fecha Límite</h6>
                            <h5 class="mb-0">{{ \Carbon\Carbon::parse($proyecto->fecha_limite)->format('d/m/Y') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de Resumen -->
    <div class="row mt-3 mb-3">
        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Total Divisiones</h6>
                            <h3 class="mb-0">{{ $totalDivisiones }}</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fa-solid fa-list-ol"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Total Ingresos</h6>
                            <h3 class="mb-0">{{ $totalIngresos }}</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fa-solid fa-money-bill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Monto Total Divisiones</h6>
                            <h3 class="mb-0">${{ number_format($montoTotalDivisiones, 2) }}</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-truncate">Monto Total Ingresos</h6>
                            <h3 class="mb-0">${{ number_format($montoTotalIngresos, 2) }}</h3>
                        </div>
                        <div class="fs-1 opacity-50">
                            <i class="fa-solid fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Progreso -->
    <div class="row mt-3 mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-percentage"></i> Progreso del Proyecto
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: {{ $porcentajeAvance }}%" 
                                     aria-valuenow="{{ $porcentajeAvance }}" 
                                     aria-valuemin="0" aria-valuemax="100">
                                    {{ number_format($porcentajeAvance, 1) }}%
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Avance: {{ number_format($porcentajeAvance, 1) }}%</h6>
                            <small class="text-muted">
                                ${{ number_format($montoTotalIngresos, 2) }} de ${{ number_format($montoTotalDivisiones, 2) }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="row mt-3 mb-3">
        <!-- Gráfica de Divisiones -->
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-chart-bar"></i> Montos por División
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="graficaDivisiones" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfica de Ingresos por Tipo -->
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-chart-pie"></i> Ingresos por Tipo
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="graficaIngresos" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    

    <!-- Tabla de Ingresos Detallados -->
    <div class="row mt-3 mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-table"></i> Detalle de Ingresos por Partidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaIngresos">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>División</th>
                                    <th>Tipo de Ingreso</th>
                                    <th>Monto</th>
                                    <th>Tipo de Operación</th>
                                    <th>Fecha</th>
                                    <th>Documento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ingresos as $ingreso)
                                    <tr>
                                        <td>{{ $ingreso->id }}</td>
                                        <td>
                                            <span class="badge bg-primary">Plazo {{ $ingreso->plazo }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $ingreso->tipo_ingreso }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">
                                                ${{ number_format($ingreso->monto_ingreso, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning">{{ $ingreso->tipo_operacion }}</span>
                                        </td>
                                        <td>
                                            <i class="fa-solid fa-calendar-day text-muted me-1"></i>
                                            {{ \Carbon\Carbon::parse($ingreso->created_at)->format('d/m/Y') }}
                                        </td>
                                        <td>
                                         
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fa-solid fa-inbox fa-3x mb-3"></i>
                                                <h5>No hay ingresos registrados</h5>
                                                <p>Este proyecto no tiene ingresos por partidas registrados.</p>
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

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Inicializar DataTable
        $('#tablaIngresos').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            responsive: true,
            order: [[5, 'desc']], // Ordenar por fecha
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
        });

        // Datos para las gráficas
        const datosDivisiones = @json($datosGraficaDivisiones);
        const datosIngresos = @json($datosGraficaIngresos);
        // const datosOperaciones = @json($datosGraficaOperaciones);

        // Gráfica de Divisiones (Barras comparativas)
        const ctxDivisiones = document.getElementById('graficaDivisiones').getContext('2d');
        new Chart(ctxDivisiones, {
            type: 'bar',
            data: {
                labels: datosDivisiones.map(item => item.plazo),
                datasets: [
                    {
                        label: 'Monto por División',
                        data: datosDivisiones.map(item => item.monto),
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Ingresado por Partidas',
                        data: datosDivisiones.map(item => item.ingresado),
                        backgroundColor: 'rgba(75, 192, 192, 0.8)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString('es-MX');
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                return label + ': $' + context.parsed.y.toLocaleString('es-MX');
                            }
                        }
                    }
                }
            }
        });

        // Gráfica de Ingresos por Tipo (Barras por tipo de partida)
        const ctxIngresos = document.getElementById('graficaIngresos').getContext('2d');
        (function(){
            // Construir resumen por tipo a partir de los ingresos renderizados en la tabla (misma fuente de datos)
            const ingresosTabla = @json($ingresos);
            const mapa = {};
            (ingresosTabla || []).forEach(item => {
                const tipo = (item.tipo_ingreso || '').toString().trim();
                const monto = parseFloat(item.monto_ingreso) || 0;
                if (!mapa[tipo]) mapa[tipo] = 0;
                mapa[tipo] += monto;
            });

            const labelsTipos = Object.keys(mapa);
            const valores = labelsTipos.map(k => mapa[k]);

            // Colores por tipo específico (resaltar MANO DE OBRA y SUMINISTRO)
            const colorPorTipo = {
                'MANO DE OBRA': 'rgba(255, 159, 64, 0.85)',
                'SUMINISTRO': 'rgba(54, 162, 235, 0.85)',
                'SERV. PUBLICOS': 'rgba(75, 192, 192, 0.85)',
                'EST. MED. OFICINA Y OTROS': 'rgba(153, 102, 255, 0.85)',
                'ALIMENTOS': 'rgba(255, 205, 86, 0.85)',
                'TRANSPORTE Y LOGISTICA': 'rgba(201, 203, 207, 0.85)',
                'HERRAMIENTAS Y EQUIPO': 'rgba(255, 99, 132, 0.85)',
                'OTROS': 'rgba(99, 255, 132, 0.85)'
            };
            const backgroundColors = labelsTipos.map(t => colorPorTipo[t] || 'rgba(99, 132, 255, 0.65)');

            new Chart(ctxIngresos, {
                type: 'bar',
                data: {
                    labels: labelsTipos,
                    datasets: [{
                        label: 'Monto por tipo',
                        data: valores,
                        backgroundColor: backgroundColors,
                        borderColor: backgroundColors.map(c => c.replace('0.85','1').replace('0.65','1')),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) { return '$' + value.toLocaleString('es-MX'); }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': $' + context.parsed.x.toLocaleString('es-MX');
                                }
                            }
                        },
                        legend: { display: false }
                    }
                }
            });
        })();

        // Gráfica de Operaciones eliminada según solicitud
    });
</script>
@endpush 