@extends('layouts.app')
@section('content')

<div class="container-fluid format_page">
    <div class="row">
        <div class="col-lg-6 col-12 start-center">
            <h3 class="mt-1">Gastos del Proyecto</h3>
            <span class="fs-8 text-muted">Detalle y análisis de ingresos/gastos</span>
        </div>
        <div class="col-lg-6 col-12 center-end">
            <a href="{{ route('proyectos.divisiones', ['idServicio' => $servicio->id]) }}" class="btn btn-secondary fs-7 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Volver a Divisiones
            </a>
            <button class="btn btn-baseColor fs-7 mb-2" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Imprimir
            </button>
        </div>
    </div>

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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-table"></i> Listado de Gastos</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaGastos">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Monto</th>
                                    <th>Empleado</th>
                                    <th>Producto</th>
                                    <th>Documento</th>
                                    <th>Comentario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($gastos as $g)
                                    <tr>
                                        <td>{{ $g->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($g->fecha)->format('d/m/Y') }}</td>
                                        <td><span class="badge bg-info">{{ $g->nombre_tipo }}</span></td>
                                        <td><span class="fw-bold text-success">${{ number_format((float) data_get($g, 'monto', 0), 2) }}</span></td>
                                        <td>
                                            @php $isManoObra = strtoupper($g->nombre_tipo ?? '') === 'MANO DE OBRA'; @endphp
                                            @if($isManoObra)
                                                @php $esPorPuesto = intval($g->es_por_puesto ?? 0) === 1; @endphp
                                                {{ $esPorPuesto ? ($g->puesto ?: '-') : ($g->empleado ?: '-') }}
                                            @else
                                                {{ $g->empleado ?: '-' }}
                                            @endif
                                        </td>
                                        <td>{{ $g->producto ?: '-' }}</td>
                                        <td>
                                            @if(!empty($g->ruta_documento))
                                                <a href="{{ asset($g->ruta_documento) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-solid fa-download"></i> Ver
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-truncate" style="max-width:240px;" title="{{ $g->comentario }}">{{ $g->comentario }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-layer-group"></i> Resumen por Tipo</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaResumenTipos">
                            <thead class="table-dark">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Cantidad de Registros</th>
                                    <th>Monto Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($porTipo ?? []) as $tipo => $total)
                                    <tr>
                                        <td><span class="badge bg-info">{{ $tipo }}</span></td>
                                        <td>{{ $conteoPorTipo[$tipo] ?? 0 }}</td>
                                        <td><span class="fw-bold text-success">${{ number_format($total, 2) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($comparativaTipos) && count($comparativaTipos))
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-scale-balanced"></i> Comparativa por Tipo (Ingresado vs Máximo)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaComparativaTipos">
                            <thead class="table-dark">
                                <tr>
                                    <th>Tipo</th>
                                    <th class="text-end">Máximo</th>
                                    <th class="text-end">Ingresado (partidas)</th>
                                    <th class="text-end">Faltante</th>
                                    <th class="text-end">Desfasado</th>
                                    <th class="text-center">% Avance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comparativaTipos as $row)
                                <tr>
                                    <td><span class="badge bg-info">{{ $row['tipo'] }}</span></td>
                                    <td class="text-end">${{ number_format($row['maximo'], 2) }}</td>
                                    <td class="text-end">${{ number_format($row['ingresado'], 2) }}</td>
                                    <td class="text-end">${{ number_format($row['faltante'], 2) }}</td>
                                    <td class="text-end">${{ number_format($row['desfasado'], 2) }}</td>
                                    <td class="text-center">
                                        <div class="progress" style="height:8px;">
                                            <div class="progress-bar {{ $row['porcentaje']>=100 ? 'bg-danger' : ($row['porcentaje']>=75 ? 'bg-warning' : 'bg-success') }}" role="progressbar" style="width: {{ $row['porcentaje'] }}%;"></div>
                                        </div>
                                        <small class="text-muted">{{ $row['porcentaje'] }}%</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

    
    @if(isset($comparativaTipos) && count($comparativaTipos))
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-chart-column"></i> Gráfica Comparativa por Tipo</h6>
                </div>
                <div class="card-body"><canvas id="chartComparativaTipos"></canvas></div>
            </div>
        </div>
    </div>
    @endif

    <div class="row mt-3">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fa-solid fa-chart-pie"></i> Gastos por Tipo</h6>
                </div>
				<div class="card-body">
					<div style="height: 280px;">
						<canvas id="chartTipos"></canvas>
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
    const porTipo = @json($porTipo);

    // Tipos
    (function(){
        const labels = Object.keys(porTipo);
        const data = labels.map(k => parseFloat(porTipo[k]));
        new Chart(document.getElementById('chartTipos'), {
            type: 'doughnut',
            data: { labels, datasets: [{ data, backgroundColor: ['#17a2b8','#f39c12','#28a745','#dc3545','#6f42c1','#20c997','#fd7e14','#0dcaf0'] }] },
            options: { responsive: true }
        });
    })();

    // (Gráfica por mes eliminada)

    // Comparativa por tipo (apilada: Ingresado vs Faltante) usando los valores de la tabla de comparativa
    (function(){
        const ctx = document.getElementById('chartComparativaTipos');
        if (!ctx) return;
        const parseCurrency = (s) => {
            if (!s) return 0;
            const n = (s+"").replace(/[^0-9,.-]/g, '').replace(/,/g,'');
            const v = parseFloat(n);
            return isNaN(v) ? 0 : v;
        };
        const rows = Array.from(document.querySelectorAll('#tablaComparativaTipos tbody tr'));
        if (!rows.length) return;
        const labels = [];
        const ingresado = [];
        const faltante = [];
        const desfasado = [];
        rows.forEach(tr => {
            const tds = tr.querySelectorAll('td');
            if (tds.length >= 5) {
                const tipo = tds[0].innerText.trim();
                const ingresadoVal = parseCurrency(tds[2].innerText.trim());
                const faltanteVal = parseCurrency(tds[3].innerText.trim());
                const desfasadoVal = parseCurrency(tds[4].innerText.trim());
                labels.push(tipo);
                ingresado.push(ingresadoVal);
                faltante.push(faltanteVal);
                desfasado.push(desfasadoVal);
            }
        });
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Ingresado', data: ingresado, backgroundColor: '#4e73df' },
                    { label: 'Faltante', data: faltante, backgroundColor: '#20c997' },
                    { label: 'Desfasado', data: desfasado, backgroundColor: '#dc3545' }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });
    })();

    $('#tablaGastos').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[1,'desc']],
        pageLength: 10
    });

    $('#tablaResumenTipos').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
        order: [[2,'desc']],
        paging: false,
        searching: false,
        info: false
    });
});
</script>
@endpush


