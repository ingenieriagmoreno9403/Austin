@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
.rpt-page .kpi-card,.rpt-page .filtros-card,.rpt-page .tabla-card,.rpt-page .grafica-card{border:1px solid #e8edf3;border-radius:1rem;background:#fff}
.rpt-page .kpi-card{padding:1rem 1.1rem;height:100%}
.rpt-page .kpi-valor{font-size:1.45rem;font-weight:700;color:#0369a1;line-height:1.1}
.rpt-page .grafica-card{padding:1rem 1.1rem}
.rpt-page .grafica-wrap{position:relative;height:260px}
.rpt-page .grafica-card h6{color:#0369a1;font-weight:700;font-size:.85rem;margin-bottom:.75rem}
</style>

<div class="container-fluid rpt-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3"><i class="fa-solid fa-gauge-high"></i></div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.reportes') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Reportes de producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">OEE / Registro de producción</h2>
                        <p class="text-muted mb-0">Tubo (metros) y flange/conexiones (piezas). Calidad de piezas = buenas ÷ total.</p>
                    </div>
                </div>
                <a class="btn btn-blue-light fs-7" href="{{ route('produccion.reportes.oee.exportar', request()->query()) }}">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Órdenes</span><div class="kpi-valor">{{ $resumen['ordenes'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">OEE promedio</span><div class="kpi-valor">{{ $resumen['oee_prom'] !== null ? number_format($resumen['oee_prom'], 1).'%' : '—' }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Disponibilidad prom.</span><div class="kpi-valor">{{ $resumen['disp_prom'] !== null ? number_format($resumen['disp_prom'], 1).'%' : '—' }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Metros</span><div class="kpi-valor">{{ number_format($resumen['metros'], 1) }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Piezas B / M</span><div class="kpi-valor" style="font-size:1.15rem">{{ number_format($resumen['piezas_buenas']) }} / {{ number_format($resumen['piezas_malas']) }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Costo material</span><div class="kpi-valor" style="font-size:1.15rem">${{ number_format($resumen['costo_material'], 2) }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Horas paro</span><div class="kpi-valor">{{ number_format($resumen['horas_paro'], 2) }}</div></div></div>
    </div>

    <div class="filtros-card p-3 mb-3">
        <form method="GET" action="{{ route('produccion.reportes.oee') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Fecha inicio</label>
                <input type="date" class="form-control form-control-sm" name="fecha_inicio" value="{{ $fechaInicio }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Fecha fin</label>
                <input type="date" class="form-control form-control-sm" name="fecha_fin" value="{{ $fechaFin }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Tipo</label>
                <select class="form-select form-select-sm" name="tipo_proceso">
                    <option value="">Todos</option>
                    <option value="TUBO" {{ ($tipoProceso ?? '') === 'TUBO' ? 'selected' : '' }}>Tubo</option>
                    <option value="FLANGE" {{ ($tipoProceso ?? '') === 'FLANGE' ? 'selected' : '' }}>Flange</option>
                    <option value="CONEXION" {{ ($tipoProceso ?? '') === 'CONEXION' ? 'selected' : '' }}>Conexión</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-8 mb-1 text-muted">Máquina</label>
                <select class="form-select form-select-sm" name="maquina_id">
                    <option value="">Todas</option>
                    @foreach ($maquinas as $m)
                        <option value="{{ $m->id }}" {{ (int)$maquinaId === (int)$m->id ? 'selected' : '' }}>
                            {{ $m->codigo ? $m->codigo.' — ' : '' }}{{ $m->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-blue btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="{{ route('produccion.reportes.oee') }}" class="btn btn-blue-light btn-sm">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="grafica-card">
                <h6><i class="fa-solid fa-chart-bar me-1"></i> Top OEE por orden</h6>
                <div class="grafica-wrap"><canvas id="chartOee"></canvas></div>
            </div>
        </div>
    </div>

    <div class="tabla-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-8">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Máquina</th>
                        <th>Turno</th>
                        <th class="text-end">T.Prog</th>
                        <th class="text-end">T.Paros</th>
                        <th class="text-end">T.Op</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Bueno kg</th>
                        <th class="text-end">Disp %</th>
                        <th class="text-end">Rend OEE %</th>
                        <th class="text-end">Calidad %</th>
                        <th class="text-end">OEE %</th>
                        <th class="text-end">Costo mat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $f)
                        @php
                            $esPieza = in_array($f['tipo_proceso'] ?? '', ['FLANGE', 'CONEXION'], true);
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $f['folio'] }}</td>
                            <td>
                                @if (($f['tipo_proceso'] ?? '') === 'CONEXION')
                                    <span class="badge bg-info text-dark">CONEXIÓN</span>
                                @elseif (($f['tipo_proceso'] ?? '') === 'FLANGE')
                                    <span class="badge bg-warning text-dark">FLANGE</span>
                                @else
                                    <span class="badge bg-secondary">TUBO</span>
                                @endif
                            </td>
                            <td>{{ $f['fecha'] }}</td>
                            <td>{{ $f['maquina'] }}</td>
                            <td>{{ $f['turno'] }}</td>
                            <td class="text-end">{{ $f['t_programado_h'] !== null ? number_format($f['t_programado_h'], 2) : '—' }}</td>
                            <td class="text-end">{{ $f['t_paros_h'] !== null ? number_format($f['t_paros_h'], 2) : '—' }}</td>
                            <td class="text-end">{{ $f['t_operando_h'] !== null ? number_format($f['t_operando_h'], 2) : '—' }}</td>
                            <td class="text-end">
                                @if ($esPieza)
                                    {{ (int) ($f['piezas_buenas'] ?? 0) }} B / {{ (int) ($f['piezas_malas'] ?? 0) }} M
                                @else
                                    {{ $f['metros'] !== null ? number_format($f['metros'], 2).' m' : '—' }}
                                @endif
                            </td>
                            <td class="text-end">{{ $f['prod_bueno_kg'] !== null ? number_format($f['prod_bueno_kg'], 2) : '—' }}</td>
                            <td class="text-end">{{ $f['disponibilidad_pct'] !== null ? number_format($f['disponibilidad_pct'], 1) : '—' }}</td>
                            <td class="text-end">{{ $f['rendimiento_oee_pct'] !== null ? number_format($f['rendimiento_oee_pct'], 1) : '—' }}</td>
                            <td class="text-end">{{ $f['calidad_pct'] !== null ? number_format($f['calidad_pct'], 1) : '—' }}</td>
                            <td class="text-end fw-bold">{{ $f['oee_pct'] !== null ? number_format($f['oee_pct'], 1) : '—' }}</td>
                            <td class="text-end">
                                {{ $f['costo_material'] !== null ? '$'.number_format($f['costo_material'], 2) : '—' }}
                                @if ($esPieza && $f['costo_unit_pieza'] !== null)
                                    <div class="text-muted">${{ number_format($f['costo_unit_pieza'], 4) }}/pza</div>
                                @elseif (!$esPieza && $f['costo_unit_m'] !== null)
                                    <div class="text-muted">${{ number_format($f['costo_unit_m'], 4) }}/m</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if (!empty($f['orden_id']))
                                    <a href="{{ route('produccion.ordenes.detalle', $f['orden_id']) }}" class="btn btn-sm btn-blue-light"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="text-center text-muted py-4">
                                Sin registros OEE en el periodo. Capture el registro en el detalle de cada OP.
                            </td>
                        </tr>
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
    const labels = g.oee?.labels || [];
    const valores = g.oee?.valores || [];
    if (!labels.length || !valores.some(v => Number(v) > 0)) {
        const el = document.getElementById('chartOee');
        if (el) el.parentElement.innerHTML = '<div class="text-muted text-center py-5 fs-8">Sin datos para graficar.</div>';
        return;
    }
    new Chart(document.getElementById('chartOee'), {
        type: 'bar',
        data: { labels, datasets: [{ label: 'OEE %', data: valores, backgroundColor: '#0369a1', borderRadius: 4 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, max: 100 } } },
    });
})();
</script>
@endsection
