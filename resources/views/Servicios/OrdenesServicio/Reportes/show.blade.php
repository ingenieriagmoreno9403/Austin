@extends('layouts.app')
@section('css')
<style>
    .ReporteOrdenServicio .card {
        border-radius: 14px;
    }
    .ReporteOrdenServicio .table thead th {
        white-space: nowrap;
    }
</style>
@endsection

@section('content')
<div class="container-fluid format_page ReporteOrdenServicio">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
            <div>
                <h3 class="m-0">{{ $titulo }}</h3>
                <div class="text-secondary">{{ $subtitulo }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('servicios.ordenesservicio') }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Volver
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @if($reporte === 'servicios-dia-estados')
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0"><b>Filtros por dias</b></div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('servicios.ordenes.reporte', ['reporte' => $reporte]) }}" class="row g-2 align-items-end">
                            <div class="col-12 col-md-4 col-lg-3">
                                <label class="form-label fw-semibold">Desde</label>
                                <input type="date" name="desde" class="form-control" value="{{ $filtros['desde'] ?? '' }}">
                            </div>
                            <div class="col-12 col-md-4 col-lg-3">
                                <label class="form-label fw-semibold">Hasta</label>
                                <input type="date" name="hasta" class="form-control" value="{{ $filtros['hasta'] ?? '' }}">
                            </div>
                            <div class="col-12 col-md-4 col-lg-6 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-filter me-2"></i>Aplicar filtros
                                </button>
                                <a href="{{ route('servicios.ordenes.reporte', ['reporte' => $reporte]) }}" class="btn btn-outline-secondary">
                                    Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if($tableType === 'simple')
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-light border-0"><b>Grafica</b></div>
                    <div class="card-body">
                        <canvas id="chartReporteOrdenServicio" height="130"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-light border-0"><b>Tabla de datos</b></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Concepto</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($filas as $fila)
                                        <tr>
                                            <td>{{ $fila['concepto'] }}</td>
                                            <td class="text-end fw-semibold">{{ $fila['total'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="text-center text-secondary">Sin informacion</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0"><b>Grafica</b></div>
                    <div class="card-body">
                        <canvas id="chartReporteOrdenServicio" height="130"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0"><b>Tabla de datos</b></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-primary">
                                    @if($tableType === 'estadoDia')
                                        <tr>
                                            <th>Fecha</th>
                                            <th class="text-end">Pendiente</th>
                                            <th class="text-end">En proceso</th>
                                            <th class="text-end">Finalizado</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    @elseif($tableType === 'productividadEmpleado')
                                        <tr>
                                            <th>Empleado</th>
                                            <th class="text-end">En tiempo</th>
                                            <th class="text-end">Fuera de tiempo</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-end">% Cumplimiento</th>
                                        </tr>
                                    @else
                                        <tr>
                                            <th>Servicio</th>
                                            <th class="text-end">Prom. Tab</th>
                                            <th class="text-end">Prom. Real</th>
                                            <th class="text-end">Registros</th>
                                        </tr>
                                    @endif
                                </thead>
                                <tbody>
                                    @forelse($filas as $fila)
                                        @if($tableType === 'estadoDia')
                                            <tr>
                                                <td>{{ $fila['concepto'] }}</td>
                                                <td class="text-end">{{ $fila['pendiente'] }}</td>
                                                <td class="text-end">{{ $fila['en_proceso'] }}</td>
                                                <td class="text-end">{{ $fila['finalizado'] }}</td>
                                                <td class="text-end fw-semibold">{{ $fila['total'] }}</td>
                                            </tr>
                                        @elseif($tableType === 'productividadEmpleado')
                                            <tr>
                                                <td>{{ $fila['concepto'] }}</td>
                                                <td class="text-end">{{ $fila['en_tiempo'] }}</td>
                                                <td class="text-end">{{ $fila['fuera_tiempo'] }}</td>
                                                <td class="text-end fw-semibold">{{ $fila['total'] }}</td>
                                                <td class="text-end">{{ $fila['cumplimiento'] }}%</td>
                                            </tr>
                                        @else
                                            <tr>
                                                <td>{{ $fila['concepto'] }}</td>
                                                <td class="text-end">{{ $fila['promedio_tab'] }}</td>
                                                <td class="text-end">{{ $fila['promedio_real'] }}</td>
                                                <td class="text-end fw-semibold">{{ $fila['total'] }}</td>
                                            </tr>
                                        @endif
                                    @empty
                                        @if($tableType === 'estadoDia' || $tableType === 'productividadEmpleado')
                                            <tr><td colspan="5" class="text-center text-secondary">Sin informacion</td></tr>
                                        @else
                                            <tr><td colspan="4" class="text-center text-secondary">Sin informacion</td></tr>
                                        @endif
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const reporte = @json($reporte);
    const labels = @json($labels ?? []);
    const series = @json($series ?? []);
    const series2 = @json($series2 ?? []);
    const series3 = @json($series3 ?? []);
    const filas = @json($filas ?? []);
    const tipoGrafica = @json($tipoGrafica ?? 'bar');
    const chartMode = @json($chartMode ?? 'single');
    const canvas = document.getElementById('chartReporteOrdenServicio');
    if (!canvas) return;

    if (chartMode === 'single') {
        new Chart(canvas, {
            type: tipoGrafica,
            data: {
                labels,
                datasets: [{
                    label: 'Total',
                    data: series,
                    backgroundColor: [
                        '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6',
                        '#14b8a6', '#06b6d4', '#84cc16', '#f97316', '#a855f7'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
        return;
    }

    if (chartMode === 'stacked') {
        const labelsDia = filas.map((f) => f.concepto);
        const isProductividad = filas.length && Object.prototype.hasOwnProperty.call(filas[0], 'en_tiempo');
        const serieA = isProductividad
            ? filas.map((f) => Number(f.en_tiempo || 0))
            : filas.map((f) => Number(f.pendiente || 0));
        const serieB = isProductividad
            ? filas.map((f) => Number(f.fuera_tiempo || 0))
            : filas.map((f) => Number(f.en_proceso || 0));
        const serieC = isProductividad ? [] : filas.map((f) => Number(f.finalizado || 0));

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labelsDia,
                datasets: [
                    { label: isProductividad ? 'En tiempo' : 'Pendiente', data: serieA, backgroundColor: isProductividad ? '#22c55e' : '#f59e0b' },
                    { label: isProductividad ? 'Fuera de tiempo' : 'En proceso', data: serieB, backgroundColor: isProductividad ? '#ef4444' : '#3b82f6' },
                    ...(isProductividad ? [] : [{ label: 'Finalizado', data: serieC, backgroundColor: '#22c55e' }])
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
            }
        });
        return;
    }

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Promedio Tab', data: series, backgroundColor: '#3b82f6' },
                { label: 'Promedio Real', data: series2, backgroundColor: '#22c55e' }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>
@endsection

