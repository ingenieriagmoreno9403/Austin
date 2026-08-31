@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
.rpt-page .kpi-card,.rpt-page .filtros-card,.rpt-page .tabla-card,.rpt-page .grafica-card{border:1px solid #e8edf3;border-radius:1rem;background:#fff}
.rpt-page .kpi-card{padding:1rem 1.1rem;height:100%}
.rpt-page .kpi-valor{font-size:1.5rem;font-weight:700;color:#b45309;line-height:1.1}
.rpt-page .grafica-card{padding:1rem 1.1rem;height:100%}
.rpt-page .grafica-wrap{position:relative;height:260px}
.rpt-page .grafica-card h6{color:#b45309;font-weight:700;font-size:.85rem;margin-bottom:.75rem}
.rpt-page .ok{color:#15803d;font-weight:600}
.rpt-page .bad{color:#b91c1c;font-weight:600}
</style>

<div class="container-fluid rpt-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3"><i class="fa-solid fa-clipboard-check"></i></div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.reportes') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Reportes de producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Calidad y rechazos</h2>
                        <p class="text-muted mb-0">Inspecciones, espesores fuera de rango y lotes enviados a reproceso.</p>
                    </div>
                </div>
                <a class="btn btn-blue-light fs-7" href="{{ route('produccion.reportes.calidad.exportar', request()->query()) }}">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Inspecciones</span><div class="kpi-valor">{{ $resumen['total'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Aceptadas</span><div class="kpi-valor ok">{{ $resumen['aceptadas'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Rechazadas</span><div class="kpi-valor bad">{{ $resumen['rechazadas'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Fuera de rango</span><div class="kpi-valor">{{ $resumen['fuera_rango'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Con reproceso</span><div class="kpi-valor">{{ $resumen['con_reproceso'] }}</div><div class="fs-8 text-muted">{{ number_format($resumen['pct_rechazo'], 1) }}% rechazo</div></div></div>
    </div>

    <div class="filtros-card p-3 mb-3">
        <form method="GET" action="{{ route('produccion.reportes.calidad') }}" class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label fs-8 mb-1 text-muted">Fecha inicio</label><input type="date" class="form-control form-control-sm" name="fecha_inicio" value="{{ $fechaInicio }}" required></div>
            <div class="col-md-2"><label class="form-label fs-8 mb-1 text-muted">Fecha fin</label><input type="date" class="form-control form-control-sm" name="fecha_fin" value="{{ $fechaFin }}" required></div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Resultado</label>
                <select class="form-select form-select-sm" name="resultado">
                    <option value="">Todos</option>
                    <option value="ACEPTADO" {{ $resultado === 'ACEPTADO' ? 'selected' : '' }}>Aceptado</option>
                    <option value="RECHAZADO" {{ $resultado === 'RECHAZADO' ? 'selected' : '' }}>Rechazado</option>
                    <option value="PENDIENTE" {{ $resultado === 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label fs-8 mb-1 text-muted">Buscar</label><input type="text" class="form-control form-control-sm" name="q" value="{{ $q }}" placeholder="OP, producto, reproceso…"></div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-blue btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="{{ route('produccion.reportes.calidad') }}" class="btn btn-blue-light btn-sm">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4"><div class="grafica-card"><h6><i class="fa-solid fa-chart-pie me-1"></i> Resultado</h6><div class="grafica-wrap"><canvas id="chartResultado"></canvas></div></div></div>
        <div class="col-lg-4"><div class="grafica-card"><h6><i class="fa-solid fa-ruler-combined me-1"></i> Espesores</h6><div class="grafica-wrap"><canvas id="chartRango"></canvas></div></div></div>
        <div class="col-lg-4"><div class="grafica-card"><h6><i class="fa-solid fa-chart-column me-1"></i> Por día</h6><div class="grafica-wrap"><canvas id="chartDia"></canvas></div></div></div>
    </div>

    <div class="tabla-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-8">
                <thead>
                    <tr>
                        <th>Fecha</th><th>OP</th><th>Tipo</th><th>Producto</th><th>Resultado</th><th>Rango</th>
                        <th class="text-end">Cantidad</th><th class="text-end">Kg merma</th><th>Reproceso</th><th>Auditor</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $fila)
                        @php
                            $esPieza = in_array($fila['tipo_proceso'] ?? '', ['FLANGE', 'CONEXION'], true);
                        @endphp
                        <tr>
                            <td>{{ $fila['fecha'] }}</td>
                            <td class="fw-semibold">{{ $fila['folio_op'] }}</td>
                            <td>
                                @if (($fila['tipo_proceso'] ?? '') === 'CONEXION')
                                    <span class="badge bg-info text-dark">CONEXIÓN</span>
                                @elseif (($fila['tipo_proceso'] ?? '') === 'FLANGE')
                                    <span class="badge bg-warning text-dark">FLANGE</span>
                                @else
                                    <span class="badge bg-secondary">TUBO</span>
                                @endif
                            </td>
                            <td><div>{{ $fila['producto'] }}</div><div class="text-muted">{{ $fila['sku'] }}</div></td>
                            <td>
                                <span class="{{ $fila['resultado_codigo'] === 'RECHAZADO' ? 'bad' : ($fila['resultado_codigo'] === 'ACEPTADO' ? 'ok' : '') }}">
                                    {{ $fila['resultado_codigo'] }}
                                </span>
                                <div class="text-muted">{{ $fila['estatus'] }}</div>
                            </td>
                            <td class="{{ $esPieza ? '' : ($fila['espesores_en_rango'] ? 'ok' : 'bad') }}">
                                {{ $esPieza ? '—' : $fila['en_rango_texto'] }}
                            </td>
                            <td class="text-end">
                                @if ($esPieza)
                                    {{ (int) ($fila['piezas_buenas'] ?? 0) }} B / {{ (int) ($fila['piezas_malas'] ?? 0) }} M
                                @else
                                    {{ number_format($fila['metros'], 2) }} m
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($fila['kg_merma'], 2) }}</td>
                            <td>
                                @if ($fila['folio_reproceso'])
                                    <a href="{{ route('produccion.reproceso.detalle', $fila['reproceso_id']) }}">{{ $fila['folio_reproceso'] }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $fila['auditor'] ?: '—' }}</td>
                            <td class="text-end">
                                @if ($fila['orden_id'])
                                    <a href="{{ route('produccion.ordenes.detalle', $fila['orden_id']) }}" class="btn btn-sm btn-blue-light"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Sin inspecciones en el periodo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const g = @json($graficas ?? []);
    new Chart(document.getElementById('chartResultado'), {
        type: 'doughnut',
        data: { labels: g.resultados.labels, datasets: [{ data: g.resultados.valores, backgroundColor: ['#15803d','#b91c1c','#94a3b8'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
    new Chart(document.getElementById('chartRango'), {
        type: 'doughnut',
        data: { labels: g.rango.labels, datasets: [{ data: g.rango.valores, backgroundColor: ['#15803d','#b45309'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
    if (g.dias?.labels?.length) {
        new Chart(document.getElementById('chartDia'), {
            type: 'bar',
            data: {
                labels: g.dias.labels,
                datasets: [
                    { label: 'Aceptadas', data: g.dias.aceptadas, backgroundColor: '#15803d', borderRadius: 4 },
                    { label: 'Rechazadas', data: g.dias.rechazadas, backgroundColor: '#b91c1c', borderRadius: 4 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } },
        });
    } else {
        document.getElementById('chartDia').parentElement.innerHTML = '<div class="text-muted text-center py-5 fs-8">Sin datos para graficar.</div>';
    }
})();
</script>
@endsection
