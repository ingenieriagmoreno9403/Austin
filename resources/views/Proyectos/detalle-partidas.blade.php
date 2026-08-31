@extends('layouts.app')
@section('content')

<div class="container-fluid format_page">
    <div class="row">
        <div class="col-lg-6 col-12 start-center">
            <h3 class="mt-1">Partidas de la División</h3>
            <span class="fs-8 text-muted">Estadísticas y gráficas por tipo de ingreso</span>
        </div>
        <div class="col-lg-6 col-12 center-end">
            <a href="{{ route('proyectos.divisiones', ['idServicio' => $division->id_servicio]) }}" class="btn btn-secondary mb-2">
                <i class="fa-solid fa-arrow-left"></i> Regresar a Divisiones
            </a>
        </div>
    </div>

    <!-- Encabezado de división -->
    <div class="row mt-3 mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="mb-1 text-muted">División</h6>
                            <h5 class="mb-0">Plazo {{ $division->plazo }}</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1 text-muted">Título</h6>
                            <h5 class="mb-0">{{ $division->titulo ?: 'Sin título' }}</h5>
                        </div>
                        @php
                            $isDateFi = is_string($division->fecha_inicio ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}/', $division->fecha_inicio);
                            $isDateFf = is_string($division->fecha_fin ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}/', $division->fecha_fin);
                        @endphp
                        @if($isDateFi || $isDateFf)
                        <div class="col-md-3">
                            <h6 class="mb-1 text-muted">Rango</h6>
                            @php
                                $fi = $isDateFi ? \Carbon\Carbon::parse($division->fecha_inicio)->format('d/m/Y') : '-';
                                $ff = $isDateFf ? \Carbon\Carbon::parse($division->fecha_fin)->format('d/m/Y') : '-';
                            @endphp
                            <h5 class="mb-0">{{ $fi }} - {{ $ff }}</h5>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <h6 class="mb-1 text-muted">Monto</h6>
                            <h5 class="mb-0 text-success">${{ number_format($division->monto,2) }}</h5>
                        </div>
                    </div>
                    @php
                        $excedente = max(0, ($montoTotal ?? 0) - (float)($division->monto ?? 0));
                    @endphp
                    @if($excedente > 0)
                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="alert alert-danger mb-0" role="alert">
                                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                    Se superó el monto del costeo inicial en <strong>${{ number_format($excedente, 2) }}</strong>.
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h6>Total de Partidas</h6>
                    <h2 class="mb-0">{{ $totalPartidas }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h6>Monto Total</h6>
                    <h2 class="mb-0">${{ number_format($montoTotal,2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h6>Promedio</h6>
                    <h2 class="mb-0">${{ number_format($totalPartidas > 0 ? $montoTotal / $totalPartidas : 0,2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficas -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-chart-column"></i> Monto por Tipo de Ingreso</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartMontos"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-chart-pie"></i> Proporción por Tipo</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartProporcion"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-12 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-sitemap"></i> Suministros por Producto</h6>
                </div>
                <div class="card-body">
                    <canvas id="chartSuministrosProducto"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de partidas existente -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-table"></i> Partidas registradas</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaPartidas">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Tipo de Ingreso</th>
                                    <th>Monto</th>
                                    <th>Tipo de Operación</th>
                                    <th>Fecha</th>
                                <th>Documento</th>
                                <th>Almacén</th>
                                <th>Ubicación</th>
                                <th>Producto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($partidas as $p)
                                    <tr>
                                        <td>{{ $p->id }}</td>
                                        <td><span class="badge bg-primary">{{ $p->tipo_ingreso }}</span></td>
                                        <td><span class="fw-bold text-success">${{ number_format($p->monto_ingreso,2) }}</span></td>
                                        <td><span class="badge bg-info">{{ $p->tipo_operacion }}</span></td>
                                        <td>{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y') }}</td>
                                        <td>
                                            @if(!empty($p->ruta_documento))
                                                <a href="{{ asset($p->ruta_documento) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-solid fa-download"></i> Ver
                                                </a>
                                            @endif
                                        </td>
                                        <td>{{ $p->tipo_ingreso === 'SUMINISTRO' ? ($p->almacen ?: '-') : '-' }}</td>
                                        <td>{{ $p->tipo_ingreso === 'SUMINISTRO' ? ($p->ubicacion ?: '-') : '-' }}</td>
                                        <td>{{ $p->tipo_ingreso === 'SUMINISTRO' ? ($p->producto ?: '-') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Sin partidas registradas</td>
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
document.addEventListener('DOMContentLoaded', function(){
    const dataPorTipo = @json($porTipo);
    const labels = Object.keys(dataPorTipo);
    const montos = labels.map(k => parseFloat(dataPorTipo[k].total));
    const cantidades = labels.map(k => parseInt(dataPorTipo[k].cantidad));

    const colores = ['#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc949','#af7aa1','#ff9da7','#9c755f','#bab0ab'];

    const ctx1 = document.getElementById('chartMontos');
    new Chart(ctx1, {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Monto', data: montos, backgroundColor: colores }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    const ctx2 = document.getElementById('chartProporcion');
    new Chart(ctx2, {
        type: 'pie',
        data: { labels, datasets: [{ data: montos, backgroundColor: colores }] },
        options: { responsive: true }
    });

    // DataTable
    $('#tablaPartidas').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[0,'desc']]
    });

    // Suministros por producto
    const sumaProd = @json($suministrosPorProducto ?? []);
    try {
        const prodLabels = sumaProd.map(x => x.producto);
        const prodData = sumaProd.map(x => parseFloat(x.total));
        const ctx3 = document.getElementById('chartSuministrosProducto');
        if (ctx3) {
            new Chart(ctx3, {
                type: 'bar',
                data: { labels: prodLabels, datasets: [{ label: 'Suministros ($)', data: prodData, backgroundColor: '#17a2b8' }] },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } } } }
            });
        }
    } catch (e) { console.warn('Grafica suministros producto', e); }
});
</script>
@endpush


