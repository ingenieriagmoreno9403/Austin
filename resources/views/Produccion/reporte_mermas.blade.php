@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $seccion = $seccion ?? 'tubo';
    $filasActivas = match ($seccion) {
        'resina' => $filasResina,
        'materiales' => $filasMateriales,
        default => $filasTubo,
    };
@endphp

<style>
.mermas-page .kpi-card {
    border: 1px solid #e8edf3;
    border-radius: 1rem;
    padding: 1rem 1.1rem;
    background: #fff;
    height: 100%;
}
.mermas-page .kpi-card .kpi-valor {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.1;
    color: #1e3a5f;
}
.mermas-page .filtros-card,
.mermas-page .tabla-card {
    border: 1px solid #e8edf3;
    border-radius: 1rem;
    background: #fff;
}
.mermas-page .nav-pills .nav-link {
    border-radius: .75rem;
    color: #1e3a5f;
    font-weight: 600;
}
.mermas-page .nav-pills .nav-link.active {
    background: #1e3a5f;
}
.mermas-page .merma-alta { color: #b91c1c; font-weight: 600; }
.mermas-page .merma-media { color: #b45309; font-weight: 600; }
.mermas-page .ayuda-calculo {
    font-size: .8rem;
    color: #64748b;
}
.mermas-page .grafica-card {
    border: 1px solid #e8edf3;
    border-radius: 1rem;
    background: #fff;
    padding: 1rem 1.1rem;
    height: 100%;
}
.mermas-page .grafica-card h6 {
    color: #1e3a5f;
    font-weight: 700;
    font-size: .85rem;
    margin-bottom: .75rem;
}
.mermas-page .grafica-wrap {
    position: relative;
    height: 260px;
}
</style>

<div class="container-fluid mermas-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.reportes') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Reportes de producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Reporte de mermas</h2>
                        <p class="text-muted mb-0">Tubo (kg teórico vs real), resina recuperada y materiales según receta y lo producido.</p>
                    </div>
                </div>
                <a class="btn btn-blue-light fs-7"
                    href="{{ route('produccion.reportes.mermas.exportar', request()->query()) }}">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <span class="text-muted fs-8">Merma tubo (kg)</span>
                <div class="kpi-valor">{{ number_format($resumen['tubo_kg_merma'], 2) }}</div>
                <div class="ayuda-calculo">{{ number_format($pctTubo, 2) }}% sobre teórico</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <span class="text-muted fs-8">Metros faltantes</span>
                <div class="kpi-valor">{{ number_format($resumen['tubo_metros_faltantes'], 2) }}</div>
                <div class="ayuda-calculo">Pedido / longitud vs salidas</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <span class="text-muted fs-8">Resina recuperada (kg)</span>
                <div class="kpi-valor">{{ number_format($resumen['resina_kg_recuperada'], 2) }}</div>
                <div class="ayuda-calculo">Origen: {{ number_format($resumen['resina_kg_pesado'], 2) }} kg</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="kpi-card">
                <span class="text-muted fs-8">Merma materiales (est.)</span>
                <div class="kpi-valor">{{ number_format($resumen['materiales_merma_kg'], 2) }}</div>
                <div class="ayuda-calculo">Tomado: {{ number_format($resumen['materiales_tomados'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="filtros-card p-3 mb-3">
        <form method="GET" action="{{ route('produccion.reportes.mermas') }}" class="row g-2 align-items-end">
            <input type="hidden" name="seccion" value="{{ $seccion }}">
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
            <div class="col-md-3">
                <label class="form-label fs-8 mb-1 text-muted">Buscar</label>
                <input type="text" class="form-control form-control-sm" name="q" value="{{ $q }}"
                    placeholder="OP, pedido, producto, SKU…">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-blue btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="{{ route('produccion.reportes.mermas', ['seccion' => $seccion]) }}" class="btn btn-blue-light btn-sm">Limpiar</a>
            </div>
        </form>
    </div>

    <ul class="nav nav-pills gap-2 mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $seccion === 'tubo' ? 'active' : '' }}"
                href="{{ route('produccion.reportes.mermas', array_merge(request()->except('seccion'), ['seccion' => 'tubo'])) }}">
                <i class="fa-solid fa-ruler-horizontal me-1"></i> Tubo / Flange
                <span class="badge bg-light text-dark ms-1">{{ $filasTubo->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $seccion === 'resina' ? 'active' : '' }}"
                href="{{ route('produccion.reportes.mermas', array_merge(request()->except('seccion'), ['seccion' => 'resina'])) }}">
                <i class="fa-solid fa-recycle me-1"></i> Resina
                <span class="badge bg-light text-dark ms-1">{{ $filasResina->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $seccion === 'materiales' ? 'active' : '' }}"
                href="{{ route('produccion.reportes.mermas', array_merge(request()->except('seccion'), ['seccion' => 'materiales'])) }}">
                <i class="fa-solid fa-flask me-1"></i> Materiales
                <span class="badge bg-light text-dark ms-1">{{ $filasMateriales->count() }}</span>
            </a>
        </li>
    </ul>

    <div class="row g-3 mb-3">
        @if ($seccion === 'tubo')
            <div class="col-lg-8">
                <div class="grafica-card">
                    <h6><i class="fa-solid fa-chart-column me-1"></i> Merma kg por día</h6>
                    <div class="grafica-wrap">
                        <canvas id="chartTuboDia"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="grafica-card">
                    <h6><i class="fa-solid fa-scale-balanced me-1"></i> Teórico vs real vs merma</h6>
                    <div class="grafica-wrap">
                        <canvas id="chartTuboVs"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="grafica-card">
                    <h6><i class="fa-solid fa-ranking-star me-1"></i> Top productos por kg merma</h6>
                    <div class="grafica-wrap">
                        <canvas id="chartTuboProductos"></canvas>
                    </div>
                </div>
            </div>
        @elseif ($seccion === 'resina')
            <div class="col-lg-8">
                <div class="grafica-card">
                    <h6><i class="fa-solid fa-chart-line me-1"></i> Origen vs resina recuperada por día</h6>
                    <div class="grafica-wrap">
                        <canvas id="chartResinaDia"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="grafica-card">
                    <h6><i class="fa-solid fa-recycle me-1"></i> Balance de resina</h6>
                    <div class="grafica-wrap">
                        <canvas id="chartResinaVs"></canvas>
                    </div>
                </div>
            </div>
        @else
            <div class="col-lg-8">
                <div class="grafica-card">
                    <h6><i class="fa-solid fa-flask me-1"></i> Top materias primas (tomado vs merma)</h6>
                    <div class="grafica-wrap">
                        <canvas id="chartMaterialesMp"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="grafica-card">
                    <h6><i class="fa-solid fa-chart-pie me-1"></i> Tomado vs merma estimada</h6>
                    <div class="grafica-wrap">
                        <canvas id="chartMaterialesVs"></canvas>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="tabla-card p-3">
        @if ($seccion === 'tubo')
            <p class="ayuda-calculo mb-3">
                <strong>Cálculo:</strong> kg teórico = metros × kg/m (spec). Merma kg = max(teórico − kg real pesado, 0).
                Metros faltantes = longitud objetivo / pedido − metros de la salida.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-8">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>OP</th>
                            <th>Producto</th>
                            <th>Ø / RD</th>
                            <th class="text-end">Esp.</th>
                            <th class="text-end">Real</th>
                            <th class="text-end">Falta/malas</th>
                            <th class="text-end">Kg teór.</th>
                            <th class="text-end">Kg real</th>
                            <th class="text-end">Kg merma</th>
                            <th class="text-end">%</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($filasTubo as $fila)
                            @php
                                $cls = $fila['porcentaje_merma'] >= 5 ? 'merma-alta' : ($fila['porcentaje_merma'] >= 2 ? 'merma-media' : '');
                            @endphp
                            <tr>
                                <td>{{ $fila['fecha'] }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $fila['folio_op'] }}</div>
                                    <div class="text-muted">{{ $fila['tipo_proceso'] }} · {{ $fila['maquina'] }}</div>
                                </td>
                                <td>
                                    <div>{{ $fila['producto'] }}</div>
                                    <div class="text-muted">{{ $fila['sku'] }}</div>
                                </td>
                                <td>{{ $fila['diametro'] }} / {{ $fila['rd'] }}</td>
                                @if (in_array($fila['tipo_proceso'], ['FLANGE', 'CONEXION'], true))
                                    <td class="text-end">{{ number_format($fila['piezas_pedido'], 0) }} p</td>
                                    <td class="text-end">{{ $fila['piezas_buenas'] }}</td>
                                    <td class="text-end {{ $fila['piezas_malas'] > 0 ? 'merma-media' : '' }}">{{ $fila['piezas_malas'] }}</td>
                                @else
                                    <td class="text-end">{{ number_format($fila['metros_esperados'], 2) }}</td>
                                    <td class="text-end">{{ number_format($fila['metros_reales'], 2) }}</td>
                                    <td class="text-end {{ $fila['metros_faltantes'] > 0 ? 'merma-media' : '' }}">
                                        {{ number_format($fila['metros_faltantes'], 2) }}
                                    </td>
                                @endif
                                <td class="text-end">{{ number_format($fila['kg_teorico'], 2) }}</td>
                                <td class="text-end">{{ number_format($fila['kg_real'], 2) }}</td>
                                <td class="text-end {{ $cls }}">{{ number_format($fila['kg_merma'], 2) }}</td>
                                <td class="text-end {{ $cls }}">{{ number_format($fila['porcentaje_merma'], 2) }}%</td>
                                <td class="text-end">
                                    @if ($fila['orden_id'])
                                        <a href="{{ route('produccion.ordenes.detalle', $fila['orden_id']) }}" class="btn btn-sm btn-blue-light">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">Sin salidas de producción en el periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($filasTubo->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="7" class="text-end">Totales</td>
                                <td class="text-end">{{ number_format($resumen['tubo_kg_teorico'], 2) }}</td>
                                <td></td>
                                <td class="text-end">{{ number_format($resumen['tubo_kg_merma'], 2) }}</td>
                                <td class="text-end">{{ number_format($pctTubo, 2) }}%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @elseif ($seccion === 'resina')
            <p class="ayuda-calculo mb-3">
                <strong>Cálculo:</strong> kg origen (pesado / reportado) vs kg resina recuperada (saca / sistema).
                Pérdida de proceso = origen − recuperada. Rendimiento = recuperada / origen.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-8">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Reproceso</th>
                            <th>OP</th>
                            <th>Origen / estatus</th>
                            <th>Producto → Resina</th>
                            <th class="text-end">Kg origen</th>
                            <th class="text-end">Kg recup.</th>
                            <th class="text-end">Pérdida</th>
                            <th class="text-end">Rend. %</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($filasResina as $fila)
                            <tr>
                                <td>{{ $fila['fecha'] }}</td>
                                <td class="fw-semibold">{{ $fila['folio_reproceso'] }}</td>
                                <td>{{ $fila['folio_op'] }}</td>
                                <td>
                                    <div>{{ $fila['origen'] }}</div>
                                    <div class="text-muted">{{ $fila['estatus'] }}</div>
                                </td>
                                <td>
                                    <div>{{ $fila['producto_origen'] }}</div>
                                    <div class="text-muted">→ {{ $fila['producto_resina'] ?: '—' }}</div>
                                </td>
                                <td class="text-end">{{ number_format($fila['kg_pesado'], 2) }}</td>
                                <td class="text-end">{{ number_format($fila['kg_recuperada'], 2) }}</td>
                                <td class="text-end {{ ($fila['kg_perdida_proceso'] ?? 0) > 0 ? 'merma-media' : '' }}">
                                    {{ $fila['kg_perdida_proceso'] !== null ? number_format($fila['kg_perdida_proceso'], 2) : '—' }}
                                </td>
                                <td class="text-end">
                                    {{ $fila['rendimiento_pct'] !== null ? number_format($fila['rendimiento_pct'], 1) . '%' : '—' }}
                                </td>
                                <td class="text-end">
                                    @if ($fila['reproceso_id'])
                                        <a href="{{ route('produccion.reproceso.detalle', $fila['reproceso_id']) }}" class="btn btn-sm btn-blue-light">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">Sin reprocesos / resina en el periodo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="ayuda-calculo mb-3">
                <strong>Cálculo:</strong> Material tomado = receta × cantidad pedida.
                Teórico x producido = receta × metros/piezas realmente producidos.
                Merma estimada = exceso tomado vs producido, o prorrateo de kg merma del tubo sobre la receta.
            </p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 fs-8">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>OP</th>
                            <th>Producto PT</th>
                            <th>Materia prima</th>
                            <th class="text-end">Pedida</th>
                            <th class="text-end">Producida</th>
                            <th class="text-end">Tomado</th>
                            <th class="text-end">Teór. prod.</th>
                            <th class="text-end">Exceso</th>
                            <th class="text-end">Merma est.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($filasMateriales as $fila)
                            <tr>
                                <td>{{ $fila['fecha'] }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $fila['folio_op'] }}</div>
                                    <div class="text-muted">{{ $fila['tipo_proceso'] }}</div>
                                </td>
                                <td>
                                    <div>{{ $fila['producto_pt'] }}</div>
                                    <div class="text-muted">{{ $fila['sku_pt'] }}</div>
                                </td>
                                <td>
                                    <div>{{ $fila['materia_prima'] }}</div>
                                    <div class="text-muted">{{ $fila['sku_mp'] }} {{ $fila['unidad'] ? '· ' . $fila['unidad'] : '' }}</div>
                                </td>
                                <td class="text-end">{{ number_format($fila['cantidad_pedida'], 2) }} {{ $fila['unidad_pt'] }}</td>
                                <td class="text-end">{{ number_format($fila['cantidad_producida'], 2) }} {{ $fila['unidad_pt'] }}</td>
                                <td class="text-end">{{ number_format($fila['tomado'], 3) }}</td>
                                <td class="text-end">{{ number_format($fila['teorico_producido'], 3) }}</td>
                                <td class="text-end {{ $fila['exceso_vs_producido'] > 0 ? 'merma-media' : '' }}">
                                    {{ number_format($fila['exceso_vs_producido'], 3) }}
                                </td>
                                <td class="text-end {{ $fila['merma_estimada'] > 0 ? 'merma-alta' : '' }}">
                                    {{ number_format($fila['merma_estimada'], 3) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Sin órdenes con materiales tomados y receta en el periodo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($filasMateriales->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="6" class="text-end">Totales</td>
                                <td class="text-end">{{ number_format($resumen['materiales_tomados'], 3) }}</td>
                                <td></td>
                                <td></td>
                                <td class="text-end">{{ number_format($resumen['materiales_merma_kg'], 3) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const g = @json($graficas ?? []);
    const seccion = @json($seccion);
    const colores = {
        merma: '#b91c1c',
        teorico: '#1e3a5f',
        real: '#0d9488',
        origen: '#b45309',
        recup: '#15803d',
        tomado: '#2563eb',
        grid: '#e8edf3',
    };

    function sinDatos(labels) {
        return !labels || labels.length === 0;
    }

    function baseOpts(tituloEje) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { mode: 'index', intersect: false },
            },
            scales: {
                x: { grid: { color: colores.grid } },
                y: {
                    beginAtZero: true,
                    title: { display: !!tituloEje, text: tituloEje || '', font: { size: 11 } },
                    grid: { color: colores.grid },
                },
            },
        };
    }

    function msgVacio(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const wrap = canvas.parentElement;
        wrap.innerHTML = '<div class="text-muted text-center py-5 fs-8">Sin datos para graficar en el periodo.</div>';
    }

    if (seccion === 'tubo') {
        if (sinDatos(g.tubo_dias?.labels)) {
            msgVacio('chartTuboDia');
        } else {
            new Chart(document.getElementById('chartTuboDia'), {
                type: 'bar',
                data: {
                    labels: g.tubo_dias.labels,
                    datasets: [
                        { label: 'Kg merma', data: g.tubo_dias.merma, backgroundColor: colores.merma, borderRadius: 4 },
                        { label: 'Kg teórico', data: g.tubo_dias.teorico, backgroundColor: colores.teorico, borderRadius: 4 },
                        { label: 'Kg real', data: g.tubo_dias.real, backgroundColor: colores.real, borderRadius: 4 },
                    ],
                },
                options: baseOpts('Kg'),
            });
        }

        new Chart(document.getElementById('chartTuboVs'), {
            type: 'doughnut',
            data: {
                labels: g.tubo_vs.labels,
                datasets: [{
                    data: g.tubo_vs.valores,
                    backgroundColor: [colores.teorico, colores.real, colores.merma],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            },
        });

        if (sinDatos(g.tubo_productos?.labels)) {
            msgVacio('chartTuboProductos');
        } else {
            new Chart(document.getElementById('chartTuboProductos'), {
                type: 'bar',
                data: {
                    labels: g.tubo_productos.labels,
                    datasets: [{
                        label: 'Kg merma',
                        data: g.tubo_productos.merma,
                        backgroundColor: colores.merma,
                        borderRadius: 4,
                    }],
                },
                options: {
                    ...baseOpts('Kg'),
                    indexAxis: 'y',
                },
            });
        }
    }

    if (seccion === 'resina') {
        if (sinDatos(g.resina_dias?.labels)) {
            msgVacio('chartResinaDia');
        } else {
            new Chart(document.getElementById('chartResinaDia'), {
                type: 'line',
                data: {
                    labels: g.resina_dias.labels,
                    datasets: [
                        {
                            label: 'Kg origen',
                            data: g.resina_dias.origen,
                            borderColor: colores.origen,
                            backgroundColor: 'rgba(180,83,9,.15)',
                            fill: true,
                            tension: .3,
                        },
                        {
                            label: 'Kg recuperada',
                            data: g.resina_dias.recuperada,
                            borderColor: colores.recup,
                            backgroundColor: 'rgba(21,128,61,.15)',
                            fill: true,
                            tension: .3,
                        },
                    ],
                },
                options: baseOpts('Kg'),
            });
        }

        new Chart(document.getElementById('chartResinaVs'), {
            type: 'doughnut',
            data: {
                labels: g.resina_vs.labels,
                datasets: [{
                    data: g.resina_vs.valores,
                    backgroundColor: [colores.origen, colores.recup, colores.merma],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            },
        });
    }

    if (seccion === 'materiales') {
        if (sinDatos(g.materiales_mp?.labels)) {
            msgVacio('chartMaterialesMp');
        } else {
            new Chart(document.getElementById('chartMaterialesMp'), {
                type: 'bar',
                data: {
                    labels: g.materiales_mp.labels,
                    datasets: [
                        { label: 'Tomado', data: g.materiales_mp.tomado, backgroundColor: colores.tomado, borderRadius: 4 },
                        { label: 'Merma est.', data: g.materiales_mp.merma, backgroundColor: colores.merma, borderRadius: 4 },
                    ],
                },
                options: {
                    ...baseOpts('Cantidad'),
                    indexAxis: 'y',
                },
            });
        }

        new Chart(document.getElementById('chartMaterialesVs'), {
            type: 'doughnut',
            data: {
                labels: g.materiales_vs.labels,
                datasets: [{
                    data: g.materiales_vs.valores,
                    backgroundColor: [colores.tomado, colores.merma],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            },
        });
    }
})();
</script>
@endsection
