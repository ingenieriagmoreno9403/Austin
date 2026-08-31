@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
.rpt-page .kpi-card,.rpt-page .filtros-card,.rpt-page .tabla-card,.rpt-page .grafica-card{border:1px solid #e8edf3;border-radius:1rem;background:#fff}
.rpt-page .kpi-card{padding:1rem 1.1rem;height:100%}
.rpt-page .kpi-valor{font-size:1.5rem;font-weight:700;color:#0f766e;line-height:1.1}
.rpt-page .ayuda{font-size:.8rem;color:#64748b}
.rpt-page .grafica-card{padding:1rem 1.1rem;height:100%}
.rpt-page .grafica-wrap{position:relative;height:260px}
.rpt-page .grafica-card h6{color:#0f766e;font-weight:700;font-size:.85rem;margin-bottom:.75rem}
</style>

<div class="container-fluid rpt-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3"><i class="fa-solid fa-industry"></i></div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.reportes') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Reportes de producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Producción diaria</h2>
                        <p class="text-muted mb-0">Metros / piezas registrados por salida, máquina y turno.</p>
                    </div>
                </div>
                <a class="btn btn-blue-light fs-7" href="{{ route('produccion.reportes.produccion_diaria.exportar', request()->query()) }}">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">Salidas</span><div class="kpi-valor">{{ $resumen['salidas'] }}</div></div></div>
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">OPs</span><div class="kpi-valor">{{ $resumen['ops'] }}</div></div></div>
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">Metros</span><div class="kpi-valor">{{ number_format($resumen['metros'], 2) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">Piezas</span><div class="kpi-valor">{{ $resumen['piezas'] }}</div></div></div>
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">Kg real</span><div class="kpi-valor">{{ number_format($resumen['kg_real'], 2) }}</div></div></div>
        <div class="col-6 col-md-2"><div class="kpi-card"><span class="text-muted fs-8">Kg teórico</span><div class="kpi-valor">{{ number_format($resumen['kg_teorico'], 2) }}</div></div></div>
    </div>

    <div class="filtros-card p-3 mb-3">
        <form method="GET" action="{{ route('produccion.reportes.produccion_diaria') }}" class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label fs-8 mb-1 text-muted">Fecha inicio</label><input type="date" class="form-control form-control-sm" name="fecha_inicio" value="{{ $fechaInicio }}" required></div>
            <div class="col-md-2"><label class="form-label fs-8 mb-1 text-muted">Fecha fin</label><input type="date" class="form-control form-control-sm" name="fecha_fin" value="{{ $fechaFin }}" required></div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Proceso</label>
                <select class="form-select form-select-sm" name="tipo_proceso">
                    <option value="">Todos</option>
                    <option value="TUBO" {{ $tipoProceso === 'TUBO' ? 'selected' : '' }}>Tubo</option>
                    <option value="FLANGE" {{ $tipoProceso === 'FLANGE' ? 'selected' : '' }}>Flange</option>
                    <option value="CONEXION" {{ $tipoProceso === 'CONEXION' ? 'selected' : '' }}>Conexión</option>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label fs-8 mb-1 text-muted">Buscar</label><input type="text" class="form-control form-control-sm" name="q" value="{{ $q }}" placeholder="OP, máquina, turno, producto…"></div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-blue btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="{{ route('produccion.reportes.produccion_diaria') }}" class="btn btn-blue-light btn-sm">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8"><div class="grafica-card"><h6><i class="fa-solid fa-chart-column me-1"></i> Producción por día</h6><div class="grafica-wrap"><canvas id="chartDia"></canvas></div></div></div>
        <div class="col-lg-4"><div class="grafica-card"><h6><i class="fa-solid fa-clock me-1"></i> Por turno</h6><div class="grafica-wrap"><canvas id="chartTurno"></canvas></div></div></div>
        <div class="col-12"><div class="grafica-card"><h6><i class="fa-solid fa-gears me-1"></i> Por máquina</h6><div class="grafica-wrap"><canvas id="chartMaquina"></canvas></div></div></div>
    </div>

    <div class="tabla-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-8">
                <thead>
                    <tr>
                        <th>Fecha</th><th>OP</th><th>Producto</th><th>Máquina</th><th>Turno</th>
                        <th class="text-end">Metros</th><th class="text-end">Piezas</th>
                        <th class="text-end">Kg teór.</th><th class="text-end">Kg real</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $fila)
                        <tr>
                            <td>{{ $fila['fecha'] }}</td>
                            <td>
                                <div class="fw-semibold">{{ $fila['folio_op'] }}</div>
                                <div class="text-muted">{{ $fila['tipo_proceso'] }} · {{ $fila['estatus_op'] }}</div>
                            </td>
                            <td><div>{{ $fila['producto'] }}</div><div class="text-muted">{{ $fila['sku'] }}</div></td>
                            <td>{{ $fila['maquina'] }}</td>
                            <td>{{ $fila['turno'] }}</td>
                            <td class="text-end">{{ number_format($fila['metros'], 2) }}</td>
                            <td class="text-end">{{ $fila['piezas'] }}</td>
                            <td class="text-end">{{ number_format($fila['kg_teorico'], 2) }}</td>
                            <td class="text-end">{{ number_format($fila['kg_real'], 2) }}</td>
                            <td class="text-end">
                                @if ($fila['orden_id'])
                                    <a href="{{ route('produccion.ordenes.detalle', $fila['orden_id']) }}" class="btn btn-sm btn-blue-light"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">Sin salidas en el periodo.</td></tr>
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
            type: 'bar',
            data: {
                labels: g.dias.labels,
                datasets: [
                    { label: 'Metros', data: g.dias.metros, backgroundColor: '#0f766e', borderRadius: 4 },
                    { label: 'Piezas', data: g.dias.piezas, backgroundColor: '#1e3a5f', borderRadius: 4 },
                    { label: 'Kg real', data: g.dias.kg_real, backgroundColor: '#0d9488', borderRadius: 4 },
                ],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
        });
    }
    if (!empty('chartTurno', g.turnos?.labels)) {
        new Chart(document.getElementById('chartTurno'), {
            type: 'doughnut',
            data: { labels: g.turnos.labels, datasets: [{ data: g.turnos.metros.map((m,i)=>m + (g.turnos.piezas[i]||0)), backgroundColor: ['#0f766e','#1e3a5f','#b45309','#15803d','#64748b'] }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
    }
    if (!empty('chartMaquina', g.maquinas?.labels)) {
        new Chart(document.getElementById('chartMaquina'), {
            type: 'bar',
            data: {
                labels: g.maquinas.labels,
                datasets: [
                    { label: 'Metros', data: g.maquinas.metros, backgroundColor: '#0f766e', borderRadius: 4 },
                    { label: 'Piezas', data: g.maquinas.piezas, backgroundColor: '#1e3a5f', borderRadius: 4 },
                ],
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { x: { beginAtZero: true } } },
        });
    }
})();
</script>
@endsection
