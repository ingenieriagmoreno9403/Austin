@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
.rpt-page .kpi-card,.rpt-page .filtros-card,.rpt-page .tabla-card,.rpt-page .grafica-card{border:1px solid #e8edf3;border-radius:1rem;background:#fff}
.rpt-page .kpi-card{padding:1rem 1.1rem;height:100%}
.rpt-page .kpi-valor{font-size:1.45rem;font-weight:700;color:#7c3aed;line-height:1.1}
.rpt-page .grafica-card{padding:1rem 1.1rem;height:100%}
.rpt-page .grafica-wrap{position:relative;height:260px}
.rpt-page .grafica-card h6{color:#7c3aed;font-weight:700;font-size:.85rem;margin-bottom:.75rem}
</style>

<div class="container-fluid rpt-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3"><i class="fa-solid fa-user-gear"></i></div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.reportes') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Reportes de producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Producción por empleado / máquina</h2>
                        <p class="text-muted mb-0">Metros, piezas y kg agrupados por operador y máquina asignada en la OP.</p>
                    </div>
                </div>
                <a class="btn btn-blue-light fs-7" href="{{ route('produccion.reportes.empleado_maquina.exportar', request()->query()) }}">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Pares emp/máq</span><div class="kpi-valor">{{ $resumen['pares'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Empleados</span><div class="kpi-valor">{{ $resumen['empleados'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Máquinas</span><div class="kpi-valor">{{ $resumen['maquinas'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Metros</span><div class="kpi-valor">{{ number_format($resumen['metros'], 2) }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Piezas</span><div class="kpi-valor">{{ $resumen['piezas'] }}</div></div></div>
        <div class="col-6 col-md"><div class="kpi-card"><span class="text-muted fs-8">Kg real / merma</span><div class="kpi-valor" style="font-size:1.15rem">{{ number_format($resumen['kg_real'], 1) }} / {{ number_format($resumen['kg_merma'], 1) }}</div></div></div>
    </div>

    <div class="filtros-card p-3 mb-3">
        <form method="GET" action="{{ route('produccion.reportes.empleado_maquina') }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Fecha inicio</label>
                <input type="date" class="form-control form-control-sm" name="fecha_inicio" value="{{ $fechaInicio }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Fecha fin</label>
                <input type="date" class="form-control form-control-sm" name="fecha_fin" value="{{ $fechaFin }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Proceso</label>
                <select class="form-select form-select-sm" name="tipo_proceso">
                    <option value="">Todos</option>
                    <option value="TUBO" {{ $tipoProceso === 'TUBO' ? 'selected' : '' }}>Tubo</option>
                    <option value="FLANGE" {{ $tipoProceso === 'FLANGE' ? 'selected' : '' }}>Flange</option>
                    <option value="CONEXION" {{ $tipoProceso === 'CONEXION' ? 'selected' : '' }}>Conexión</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Máquina</label>
                <select class="form-select form-select-sm" name="maquina_id">
                    <option value="">Todas</option>
                    @foreach ($maquinas as $m)
                        <option value="{{ $m->id }}" {{ (int) $maquinaId === (int) $m->id ? 'selected' : '' }}>
                            {{ $m->nombre }}{{ $m->codigo ? ' ('.$m->codigo.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Empleado</label>
                <select class="form-select form-select-sm" name="operador_id">
                    <option value="">Todos</option>
                    @foreach ($operadores as $e)
                        <option value="{{ $e->id }}" {{ (int) $operadorId === (int) $e->id ? 'selected' : '' }}>
                            {{ trim($e->primer_nombre.' '.$e->apellido_paterno) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Buscar</label>
                <input type="text" class="form-control form-control-sm" name="q" value="{{ $q }}" placeholder="Nombre, máquina, OP…">
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-blue btn-sm" type="submit"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="{{ route('produccion.reportes.empleado_maquina') }}" class="btn btn-blue-light btn-sm">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="grafica-card">
                <h6><i class="fa-solid fa-users me-1"></i> Top empleados</h6>
                <div class="grafica-wrap"><canvas id="chartEmpleados"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="grafica-card">
                <h6><i class="fa-solid fa-gears me-1"></i> Top máquinas</h6>
                <div class="grafica-wrap"><canvas id="chartMaquinas"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="grafica-card">
                <h6><i class="fa-solid fa-link me-1"></i> Top pares empleado·máquina</h6>
                <div class="grafica-wrap"><canvas id="chartPares"></canvas></div>
            </div>
        </div>
    </div>

    <div class="tabla-card p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-8">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Máquina</th>
                        <th class="text-end">Salidas</th>
                        <th class="text-end">OPs</th>
                        <th class="text-end">Metros</th>
                        <th class="text-end">Piezas</th>
                        <th class="text-end">Kg teór.</th>
                        <th class="text-end">Kg real</th>
                        <th class="text-end">Kg merma</th>
                        <th class="text-end">% merma</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filas as $fila)
                        <tr>
                            <td class="fw-semibold">{{ $fila['empleado'] }}</td>
                            <td>
                                <div>{{ $fila['maquina'] }}</div>
                                @if ($fila['maquina_codigo'])
                                    <div class="text-muted">{{ $fila['maquina_codigo'] }}</div>
                                @endif
                            </td>
                            <td class="text-end">{{ $fila['salidas'] }}</td>
                            <td class="text-end">{{ $fila['ops'] }}</td>
                            <td class="text-end">{{ number_format($fila['metros'], 2) }}</td>
                            <td class="text-end">{{ $fila['piezas'] }}</td>
                            <td class="text-end">{{ number_format($fila['kg_teorico'], 2) }}</td>
                            <td class="text-end">{{ number_format($fila['kg_real'], 2) }}</td>
                            <td class="text-end">{{ number_format($fila['kg_merma'], 2) }}</td>
                            <td class="text-end">{{ number_format($fila['porcentaje_merma'], 2) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Sin producción con operador/máquina en el periodo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($filas->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end">Totales</td>
                            <td class="text-end">{{ $resumen['salidas'] }}</td>
                            <td></td>
                            <td class="text-end">{{ number_format($resumen['metros'], 2) }}</td>
                            <td class="text-end">{{ $resumen['piezas'] }}</td>
                            <td></td>
                            <td class="text-end">{{ number_format($resumen['kg_real'], 2) }}</td>
                            <td class="text-end">{{ number_format($resumen['kg_merma'], 2) }}</td>
                            <td></td>
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
    const empty = (id, labels) => {
        if (labels && labels.length) return false;
        const el = document.getElementById(id);
        if (el) el.parentElement.innerHTML = '<div class="text-muted text-center py-5 fs-8">Sin datos para graficar.</div>';
        return true;
    };
    const optsH = { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { x: { beginAtZero: true } } };

    if (!empty('chartEmpleados', g.empleados?.labels)) {
        new Chart(document.getElementById('chartEmpleados'), {
            type: 'bar',
            data: {
                labels: g.empleados.labels,
                datasets: [
                    { label: 'Metros', data: g.empleados.metros, backgroundColor: '#7c3aed', borderRadius: 4 },
                    { label: 'Piezas', data: g.empleados.piezas, backgroundColor: '#1e3a5f', borderRadius: 4 },
                ],
            },
            options: optsH,
        });
    }
    if (!empty('chartMaquinas', g.maquinas?.labels)) {
        new Chart(document.getElementById('chartMaquinas'), {
            type: 'bar',
            data: {
                labels: g.maquinas.labels,
                datasets: [
                    { label: 'Metros', data: g.maquinas.metros, backgroundColor: '#0f766e', borderRadius: 4 },
                    { label: 'Piezas', data: g.maquinas.piezas, backgroundColor: '#1e3a5f', borderRadius: 4 },
                ],
            },
            options: optsH,
        });
    }
    if (!empty('chartPares', g.pares?.labels)) {
        new Chart(document.getElementById('chartPares'), {
            type: 'bar',
            data: {
                labels: g.pares.labels,
                datasets: [
                    { label: 'Metros', data: g.pares.metros, backgroundColor: '#7c3aed', borderRadius: 4 },
                    { label: 'Kg real', data: g.pares.kg_real, backgroundColor: '#b45309', borderRadius: 4 },
                ],
            },
            options: optsH,
        });
    }
})();
</script>
@endsection
