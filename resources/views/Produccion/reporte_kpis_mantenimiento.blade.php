@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
.rpt-page .kpi-card,.rpt-page .filtros-card,.rpt-page .tabla-card,.rpt-page .grafica-card,.rpt-page .def-card{border:1px solid #e8edf3;border-radius:1rem;background:#fff}
.rpt-page .kpi-card{padding:1rem 1.1rem;height:100%}
.rpt-page .kpi-valor{font-size:1.45rem;font-weight:700;color:#9f1239;line-height:1.1}
.rpt-page .grafica-card{padding:1rem 1.1rem;height:100%}
.rpt-page .grafica-wrap{position:relative;height:260px}
.rpt-page .grafica-card h6,.rpt-page .def-card h6{color:#9f1239;font-weight:700;font-size:.85rem;margin-bottom:.75rem}
.rpt-page .def-card{padding:1rem 1.1rem}
.rpt-page .def-card table{font-size:.82rem}
</style>

<div class="container-fluid rpt-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.reportes') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Reportes de producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Indicadores de mantenimiento (KPIs)</h2>
                        <p class="text-muted mb-0">
                            Se calculan desde órdenes correctivas. Con historial se podrá diseñar el preventivo.
                        </p>
                    </div>
                </div>
                <a class="btn btn-blue-light fs-7" href="{{ route('produccion.reportes.kpis_mantenimiento.exportar', request()->query()) }}">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Máquinas</span><div class="kpi-valor">{{ $resumen['maquinas'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Nº fallas</span><div class="kpi-valor">{{ $resumen['fallas'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Horas paro</span><div class="kpi-valor">{{ number_format($resumen['horas_paro'], 2) }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">MTTR (h)</span><div class="kpi-valor">{{ number_format($resumen['mttr'], 2) }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Costo total ($)</span><div class="kpi-valor" style="font-size:1.2rem">${{ number_format($resumen['costo_total'], 2) }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Causa más frecuente</span><div class="kpi-valor" style="font-size:.95rem">{{ $resumen['causa_frecuente'] }}</div></div></div>
    </div>

    <div class="filtros-card p-3 mb-3">
        <form method="GET" action="{{ route('produccion.reportes.kpis_mantenimiento') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Fecha inicio</label>
                <input type="date" class="form-control form-control-sm" name="fecha_inicio" value="{{ $fechaInicio }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Fecha fin</label>
                <input type="date" class="form-control form-control-sm" name="fecha_fin" value="{{ $fechaFin }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-8 mb-1 text-muted">Máquina</label>
                <select class="form-select form-select-sm" name="maquina_id">
                    <option value="">Todas</option>
                    @foreach ($maquinas as $m)
                        <option value="{{ $m->id }}" {{ (int) $maquinaId === (int) $m->id ? 'selected' : '' }}>
                            {{ $m->codigo ? $m->codigo.' — ' : '' }}{{ $m->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Horas programadas / día</label>
                <input type="number" step="0.5" min="0.5" class="form-control form-control-sm" name="horas_programadas_dia"
                    value="{{ $horasDia }}" title="Para MTBF y disponibilidad OEE">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-blue btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="{{ route('produccion.reportes.kpis_mantenimiento') }}" class="btn btn-blue-light btn-sm">Limpiar</a>
            </div>
        </form>
        <div class="form-text fs-9 mt-2">
            MTBF y disponibilidad usan {{ number_format($horasDia, 1) }} h/día × {{ $resumen['dias'] }} día(s) del periodo como tiempo programado.
        </div>
    </div>

    <div class="def-card mb-3">
        <h6><i class="fa-solid fa-book me-1"></i> Definición de KPIs</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>KPI</th>
                        <th>Fórmula / definición</th>
                        <th>Cómo se calcula</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($definiciones as $def)
                        <tr>
                            <td class="fw-semibold">{{ $def['kpi'] }}</td>
                            <td>{{ $def['formula'] }}</td>
                            <td class="text-muted">{{ $def['como'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-3">
            <div class="grafica-card">
                <h6><i class="fa-solid fa-triangle-exclamation me-1"></i> Fallas por máquina</h6>
                <div class="grafica-wrap"><canvas id="chartFallas"></canvas></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="grafica-card">
                <h6><i class="fa-solid fa-clock me-1"></i> Horas de paro</h6>
                <div class="grafica-wrap"><canvas id="chartParo"></canvas></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="grafica-card">
                <h6><i class="fa-solid fa-dollar-sign me-1"></i> Costo por máquina</h6>
                <div class="grafica-wrap"><canvas id="chartCosto"></canvas></div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="grafica-card">
                <h6><i class="fa-solid fa-list-ol me-1"></i> Causas (CF-xx)</h6>
                <div class="grafica-wrap"><canvas id="chartCausas"></canvas></div>
            </div>
        </div>
    </div>

    <div class="tabla-card p-3">
        <h6 class="text-marino fw-bold mb-2">Resumen por máquina (en vivo)</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-8">
                <thead>
                    <tr>
                        <th>Máquina</th>
                        <th class="text-end">Nº fallas</th>
                        <th class="text-end">Horas paro</th>
                        <th class="text-end">Costo total ($)</th>
                        <th class="text-end">MTTR (h)</th>
                        <th class="text-end">MTBF (h)</th>
                        <th class="text-end">Disponibilidad %</th>
                        <th>Causa más frecuente</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $fila)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $fila['codigo'] }}</div>
                                <div class="text-muted">{{ $fila['maquina'] }}</div>
                            </td>
                            <td class="text-end">{{ $fila['num_fallas'] }}</td>
                            <td class="text-end">{{ number_format($fila['horas_paro'], 2) }}</td>
                            <td class="text-end">${{ number_format($fila['costo_total'], 2) }}</td>
                            <td class="text-end">{{ number_format($fila['mttr'], 2) }}</td>
                            <td class="text-end">
                                {{ $fila['mtbf'] !== null ? number_format($fila['mtbf'], 2) : '—' }}
                            </td>
                            <td class="text-end">{{ number_format($fila['disponibilidad_pct'], 2) }}%</td>
                            <td>{{ $fila['causa_frecuente'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No hay máquinas activas para mostrar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($filas->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold">
                            <td class="text-end">Totales / promedio</td>
                            <td class="text-end">{{ $resumen['fallas'] }}</td>
                            <td class="text-end">{{ number_format($resumen['horas_paro'], 2) }}</td>
                            <td class="text-end">${{ number_format($resumen['costo_total'], 2) }}</td>
                            <td class="text-end">{{ number_format($resumen['mttr'], 2) }}</td>
                            <td></td>
                            <td></td>
                            <td>{{ $resumen['causa_frecuente'] }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const g = @json($graficas ?? []);
    const empty = (id, labels, valores) => {
        const has = (labels || []).length && (valores || []).some(v => Number(v) > 0);
        if (has) return false;
        const el = document.getElementById(id);
        if (el) el.parentElement.innerHTML = '<div class="text-muted text-center py-5 fs-8">Sin datos para graficar.</div>';
        return true;
    };
    const optsH = { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } };
    const optsPie = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };
    const colors = ['#9f1239','#be123c','#e11d48','#f43f5e','#fb7185','#1e3a5f','#0f766e','#b45309'];

    if (!empty('chartFallas', g.fallas?.labels, g.fallas?.valores)) {
        new Chart(document.getElementById('chartFallas'), {
            type: 'bar',
            data: { labels: g.fallas.labels, datasets: [{ data: g.fallas.valores, backgroundColor: '#9f1239', borderRadius: 4 }] },
            options: optsH,
        });
    }
    if (!empty('chartParo', g.paro?.labels, g.paro?.valores)) {
        new Chart(document.getElementById('chartParo'), {
            type: 'bar',
            data: { labels: g.paro.labels, datasets: [{ data: g.paro.valores, backgroundColor: '#1e3a5f', borderRadius: 4 }] },
            options: optsH,
        });
    }
    if (!empty('chartCosto', g.costo?.labels, g.costo?.valores)) {
        new Chart(document.getElementById('chartCosto'), {
            type: 'bar',
            data: { labels: g.costo.labels, datasets: [{ data: g.costo.valores, backgroundColor: '#0f766e', borderRadius: 4 }] },
            options: optsH,
        });
    }
    if (!empty('chartCausas', g.causas?.labels, g.causas?.valores)) {
        new Chart(document.getElementById('chartCausas'), {
            type: 'doughnut',
            data: { labels: g.causas.labels, datasets: [{ data: g.causas.valores, backgroundColor: colors }] },
            options: optsPie,
        });
    }
})();
</script>
@endsection
