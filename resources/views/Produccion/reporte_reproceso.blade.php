@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
@php
    $estatuses = \App\Models\Reproceso::$estatuses;
@endphp
<style>
.rpt-page .kpi-card,.rpt-page .filtros-card,.rpt-page .tabla-card,.rpt-page .grafica-card{border:1px solid #e8edf3;border-radius:1rem;background:#fff}
.rpt-page .kpi-card{padding:1rem 1.1rem;height:100%}
.rpt-page .kpi-valor{font-size:1.5rem;font-weight:700;color:#15803d;line-height:1.1}
.rpt-page .grafica-card{padding:1rem 1.1rem;height:100%}
.rpt-page .grafica-wrap{position:relative;height:260px}
.rpt-page .grafica-card h6{color:#15803d;font-weight:700;font-size:.85rem;margin-bottom:.75rem}
</style>

<div class="container-fluid rpt-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3"><i class="fa-solid fa-recycle"></i></div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.reportes') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Reportes de producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Reproceso y resina</h2>
                        <p class="text-muted mb-0">Triturado, peletizado y recuperación de resina desde rechazos / merma.</p>
                    </div>
                </div>
                <a class="btn btn-blue-light fs-7" href="{{ route('produccion.reportes.reproceso.exportar', request()->query()) }}">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">Lotes</span><div class="kpi-valor">{{ $resumen['total'] }}</div></div></div>
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">Kg origen</span><div class="kpi-valor">{{ number_format($resumen['kg_origen'], 2) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">Kg triturado</span><div class="kpi-valor">{{ number_format($resumen['kg_triturado'], 2) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">Kg resina</span><div class="kpi-valor">{{ number_format($resumen['kg_resina'], 2) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">Rendimiento</span><div class="kpi-valor">{{ number_format($resumen['rendimiento_pct'], 1) }}%</div></div></div>
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">En proceso / cerrados</span><div class="kpi-valor" style="font-size:1.2rem">{{ $resumen['en_proceso'] }} / {{ $resumen['cerrados'] }}</div></div></div>
    </div>

    <div class="filtros-card p-3 mb-3">
        <form method="GET" action="{{ route('produccion.reportes.reproceso') }}" class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label fs-8 mb-1 text-muted">Fecha inicio</label><input type="date" class="form-control form-control-sm" name="fecha_inicio" value="{{ $fechaInicio }}" required></div>
            <div class="col-md-2"><label class="form-label fs-8 mb-1 text-muted">Fecha fin</label><input type="date" class="form-control form-control-sm" name="fecha_fin" value="{{ $fechaFin }}" required></div>
            <div class="col-md-3">
                <label class="form-label fs-8 mb-1 text-muted">Estatus</label>
                <select class="form-select form-select-sm" name="estatus">
                    <option value="">Todos</option>
                    @foreach ($estatuses as $code => $label)
                        @if ($code !== 'CANCELADA')
                            <option value="{{ $code }}" {{ $estatus === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label fs-8 mb-1 text-muted">Buscar</label><input type="text" class="form-control form-control-sm" name="q" value="{{ $q }}" placeholder="Folio, OP, resina…"></div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-blue btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="{{ route('produccion.reportes.reproceso') }}" class="btn btn-blue-light btn-sm">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5"><div class="grafica-card"><h6><i class="fa-solid fa-chart-line me-1"></i> Origen vs resina por día</h6><div class="grafica-wrap"><canvas id="chartDia"></canvas></div></div></div>
        <div class="col-lg-3"><div class="grafica-card"><h6><i class="fa-solid fa-scale-balanced me-1"></i> Balance kg</h6><div class="grafica-wrap"><canvas id="chartBalance"></canvas></div></div></div>
        <div class="col-lg-4"><div class="grafica-card"><h6><i class="fa-solid fa-list-check me-1"></i> Por estatus</h6><div class="grafica-wrap"><canvas id="chartEstatus"></canvas></div></div></div>
    </div>

    <div class="tabla-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-8">
                <thead>
                    <tr>
                        <th>Fecha</th><th>Reproceso</th><th>OP</th><th>Estatus</th><th>Producto → Resina</th>
                        <th class="text-end">Kg origen</th><th class="text-end">Kg trit.</th><th class="text-end">Kg resina</th>
                        <th class="text-end">Rend. %</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $fila)
                        <tr>
                            <td>{{ $fila['fecha'] }}</td>
                            <td>
                                <div class="fw-semibold">{{ $fila['folio_reproceso'] }}</div>
                                <div class="text-muted">{{ $fila['origen'] }}</div>
                            </td>
                            <td>{{ $fila['folio_op'] ?: '—' }}</td>
                            <td>{{ $fila['estatus'] }}</td>
                            <td>
                                <div>{{ $fila['producto_origen'] ?: '—' }}</div>
                                <div class="text-muted">→ {{ $fila['producto_resina'] ?: '—' }}</div>
                            </td>
                            <td class="text-end">{{ number_format($fila['kg_origen'], 2) }}</td>
                            <td class="text-end">{{ number_format($fila['kg_triturado'], 2) }}</td>
                            <td class="text-end">{{ number_format($fila['kg_resina'], 2) }}</td>
                            <td class="text-end">{{ $fila['rendimiento_pct'] !== null ? number_format($fila['rendimiento_pct'], 1) . '%' : '—' }}</td>
                            <td class="text-end">
                                @if ($fila['reproceso_id'])
                                    <a href="{{ route('produccion.reproceso.detalle', $fila['reproceso_id']) }}" class="btn btn-sm btn-blue-light"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Sin lotes de reproceso en el periodo.</td></tr>
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
    const empty = (id, labels) => {
        if (labels && labels.length) return false;
        const el = document.getElementById(id);
        if (el) el.parentElement.innerHTML = '<div class="text-muted text-center py-5 fs-8">Sin datos para graficar.</div>';
        return true;
    };
    if (!empty('chartDia', g.dias?.labels)) {
        new Chart(document.getElementById('chartDia'), {
            type: 'line',
            data: {
                labels: g.dias.labels,
                datasets: [
                    { label: 'Kg origen', data: g.dias.origen, borderColor: '#b45309', backgroundColor: 'rgba(180,83,9,.15)', fill: true, tension: .3 },
                    { label: 'Kg resina', data: g.dias.resina, borderColor: '#15803d', backgroundColor: 'rgba(21,128,61,.15)', fill: true, tension: .3 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
        });
    }
    new Chart(document.getElementById('chartBalance'), {
        type: 'doughnut',
        data: { labels: g.balance.labels, datasets: [{ data: g.balance.valores, backgroundColor: ['#b45309','#1e3a5f','#15803d'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
    if (!empty('chartEstatus', g.estatus?.labels)) {
        new Chart(document.getElementById('chartEstatus'), {
            type: 'bar',
            data: { labels: g.estatus.labels, datasets: [{ label: 'Lotes', data: g.estatus.valores, backgroundColor: '#15803d', borderRadius: 4 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } },
        });
    }
})();
</script>
@endsection
