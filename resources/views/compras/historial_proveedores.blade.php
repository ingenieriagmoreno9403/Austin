@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .hp-filters {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 0.7rem 0.85rem;
        margin-bottom: 0.9rem;
    }
    .hp-filters .form-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 0.15rem;
    }
    .hp-kpi {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 0.75rem 0.9rem;
    }
    .hp-kpi small {
        display: block;
        color: #6b7280;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .hp-kpi strong {
        font-size: 1.15rem;
    }
    .hp-calidad {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 1px solid #e5e7eb;
        background: #fff;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.2rem 0.55rem;
    }
    .hp-dot {
        width: 0.7rem;
        height: 0.7rem;
        border-radius: 50%;
        display: inline-block;
        box-shadow: inset 0 0 0 1px rgba(0,0,0,.08);
    }
    .hp-calidad[data-calidad="EXCELENTE"] .hp-dot { background: #16a34a; }
    .hp-calidad[data-calidad="BUENO"] .hp-dot { background: #65a30d; }
    .hp-calidad[data-calidad="REGULAR"] .hp-dot { background: #eab308; }
    .hp-calidad[data-calidad="MALO"] .hp-dot { background: #dc2626; }
    .hp-calidad[data-calidad="SIN CALIFICAR"] .hp-dot { background: #9ca3af; }
    .hp-chart-box {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 1rem;
        margin-top: 1rem;
    }
    .hp-chart-box canvas {
        max-height: 280px;
    }
    #hp-chart-top {
        max-height: 420px;
    }
</style>

<div class="container-fluid format_page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Historial de proveedores</h2>
                        <p class="text-muted mb-0">Productos comprados en órdenes de compra, por proveedor.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('compras.historial_proveedores') }}" class="hp-filters">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="proveedor_id">Proveedor</label>
                <select class="form-select form-select-sm" id="proveedor_id" name="proveedor_id">
                    <option value="">Todos</option>
                    @foreach ($proveedores as $prov)
                        <option value="{{ $prov->id }}" @selected($proveedorId === (int) $prov->id)>{{ $prov->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="producto">Producto</label>
                <input class="form-control form-control-sm" id="producto" name="producto" value="{{ $producto }}" placeholder="Nombre del producto">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="desde">Desde</label>
                <input class="form-control form-control-sm" type="date" id="desde" name="desde" value="{{ $desde }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="hasta">Hasta</label>
                <input class="form-control form-control-sm" type="date" id="hasta" name="hasta" value="{{ $hasta }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="estado">Estado de la orden</label>
                <select class="form-select form-select-sm" id="estado" name="estado">
                    <option value="">Todos</option>
                    @foreach ($estados as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected($estado === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-baseColor btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Consultar</button>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('compras.historial_proveedores') }}">Limpiar</a>
            </div>
        </div>
    </form>

    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="hp-kpi">
                <small>Órdenes</small>
                <strong>{{ number_format((int) ($resumen->ordenes ?? 0)) }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="hp-kpi">
                <small>Productos distintos</small>
                <strong>{{ number_format((int) ($resumen->productos ?? 0)) }}</strong>
            </div>
        </div>
        <div class="col-md-4">
            <div class="hp-kpi">
                <small>Importe</small>
                <strong>${{ number_format((float) ($resumen->importe ?? 0), 2) }}</strong>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Orden</th>
                    <th>Proveedor</th>
                    <th>Producto</th>
                    <th>Marca</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Costo</th>
                    <th class="text-end">Importe</th>
                    <th class="text-end">Recibido</th>
                    <th>Estado</th>
                    <th>Calidad</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lineas as $linea)
                    <tr>
                        <td>{{ $linea->fecha_creacion ? \Carbon\Carbon::parse($linea->fecha_creacion)->format('d/m/Y') : '—' }}</td>
                        <td>
                            <a href="{{ route('ordcompras.editar', $linea->orden_id) }}">{{ $linea->folio }}</a>
                            @if ($linea->orden_nombre)
                                <div class="text-muted" style="font-size:.75rem">{{ $linea->orden_nombre }}</div>
                            @endif
                        </td>
                        <td>{{ $linea->proveedor ?: '—' }}</td>
                        <td>{{ $linea->producto ?: 'Producto #'.$linea->producto_id }}</td>
                        <td>{{ $linea->marca ?: '—' }}</td>
                        <td class="text-end">{{ number_format((float) $linea->cantidad, 2) }} {{ $linea->unidad_medida }}</td>
                        <td class="text-end">{{ $linea->tipo_moneda }} {{ number_format((float) $linea->costo, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $linea->importe, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $linea->cantidad_recibida, 2) }}</td>
                        <td>{{ $estados[$linea->estado] ?? $linea->estado }}</td>
                        <td>
                            @php
                                $calidad = strtoupper(trim((string) ($linea->calidad_proveedor ?? '')));
                                if (! in_array($calidad, ['EXCELENTE', 'BUENO', 'REGULAR', 'MALO'], true)) {
                                    $calidad = 'SIN CALIFICAR';
                                }
                                $etiquetasCalidad = [
                                    'EXCELENTE' => 'Excelente',
                                    'BUENO' => 'Bueno',
                                    'REGULAR' => 'Regular',
                                    'MALO' => 'Malo',
                                    'SIN CALIFICAR' => 'Sin calificar',
                                ];
                            @endphp
                            @if ($linea->detalle_id)
                                <div class="dropdown">
                                    <button type="button" class="hp-calidad dropdown-toggle" data-bs-toggle="dropdown" data-detalle="{{ $linea->detalle_id }}" data-calidad="{{ $calidad }}" aria-label="Cambiar calidad de esta línea">
                                        <span class="hp-dot"></span>
                                        <span class="hp-calidad-txt">{{ $etiquetasCalidad[$calidad] }}</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        @foreach (['EXCELENTE' => 'Excelente', 'BUENO' => 'Bueno', 'REGULAR' => 'Regular', 'MALO' => 'Malo'] as $claveCal => $nombreCal)
                                            <li>
                                                <button type="button" class="dropdown-item js-set-calidad" data-detalle="{{ $linea->detalle_id }}" data-calidad="{{ $claveCal }}">{{ $nombreCal }}</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">No hay compras con esos filtros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $lineas->links() }}

    <div class="hp-chart-box">
        <h3 class="h6 mb-3">Calidad por línea</h3>
        <canvas id="hp-chart-calidad"></canvas>
    </div>

    <div class="hp-chart-box">
        <h3 class="h6 mb-1">Proveedores a los que más se compra</h3>
        <p class="text-muted small mb-3">Top 10 por importe. En rojo si concentra 40% o más, o si ya rebasó el valor de su contrato.</p>
        <canvas id="hp-chart-top"></canvas>
        <div class="table-responsive mt-3">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th class="text-end">Importe</th>
                        <th class="text-end">% del total</th>
                        <th class="text-end">Tope del contrato</th>
                        <th>Consumo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topProveedores as $fila)
                        <tr>
                            <td>{{ $fila['proveedor'] }}</td>
                            <td class="text-end">{{ number_format($fila['importe'], 2) }}</td>
                            <td class="text-end {{ $fila['concentrado'] ? 'text-danger fw-bold' : '' }}">{{ number_format($fila['porcentaje'], 1) }}%</td>
                            <td class="text-end">{{ $fila['contrato_valor'] !== null ? number_format($fila['contrato_valor'], 2) : 'Sin tope' }}</td>
                            <td class="{{ $fila['excede_tope'] ? 'text-danger fw-bold' : '' }}">
                                @if ($fila['excede_tope'])
                                    Rebasa el tope
                                @elseif ($fila['contrato_valor'] !== null)
                                    Dentro del tope
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">No hay compras con esos filtros.</td>
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
    var etiquetas = {
        'EXCELENTE': 'Excelente',
        'BUENO': 'Bueno',
        'REGULAR': 'Regular',
        'MALO': 'Malo',
        'SIN CALIFICAR': 'Sin calificar'
    };
    var colores = {
        'EXCELENTE': '#16a34a',
        'BUENO': '#65a30d',
        'REGULAR': '#eab308',
        'MALO': '#dc2626',
        'SIN CALIFICAR': '#9ca3af'
    };
    var orden = ['EXCELENTE', 'BUENO', 'REGULAR', 'MALO', 'SIN CALIFICAR'];
    var totales = @json($grafica);
    var chart = new Chart(document.getElementById('hp-chart-calidad'), {
        type: 'bar',
        data: {
            labels: orden.map(function (k) { return etiquetas[k]; }),
            datasets: [{
                label: 'Líneas',
                data: orden.map(function (k) { return Number(totales[k] || 0); }),
                backgroundColor: orden.map(function (k) { return colores[k]; }),
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    var topProveedores = @json($topProveedores);
    new Chart(document.getElementById('hp-chart-top'), {
        type: 'bar',
        data: {
            labels: topProveedores.map(function (fila) { return fila.proveedor; }),
            datasets: [{
                label: 'Importe',
                data: topProveedores.map(function (fila) { return Number(fila.importe || 0); }),
                backgroundColor: topProveedores.map(function (fila) {
                    return (fila.concentrado || fila.excede_tope) ? '#dc2626' : '#2563eb';
                }),
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            var fila = topProveedores[ctx.dataIndex] || {};
                            var texto = 'Importe: ' + Number(ctx.parsed.x || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            texto += ' (' + Number(fila.porcentaje || 0).toLocaleString('es-MX') + '%)';
                            if (fila.excede_tope) texto += ' · rebasa el tope';
                            else if (fila.concentrado) texto += ' · alta concentración';
                            return texto;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (valor) {
                            return Number(valor).toLocaleString('es-MX');
                        }
                    }
                }
            }
        }
    });

    function pintar(boton, calidad) {
        boton.setAttribute('data-calidad', calidad);
        var txt = boton.querySelector('.hp-calidad-txt');
        if (txt) txt.textContent = etiquetas[calidad] || calidad;
    }

    document.querySelectorAll('.js-set-calidad').forEach(function (item) {
        item.addEventListener('click', function () {
            var detalle = item.getAttribute('data-detalle');
            var calidad = item.getAttribute('data-calidad');
            var menu = item.closest('.dropdown');
            var boton = menu ? menu.querySelector('.hp-calidad') : null;
            if (!boton) return;
            var anterior = boton.getAttribute('data-calidad');
            var token = document.querySelector('meta[name="csrf-token"]');
            fetch(@json(route('compras.historial_proveedores.calidad')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ detalle_id: detalle, calidad: calidad })
            }).then(function (res) {
                if (!res.ok) throw new Error('calidad');
                return res.json();
            }).then(function () {
                pintar(boton, calidad);
                if (anterior && anterior !== calidad && Object.prototype.hasOwnProperty.call(totales, anterior)) {
                    totales[anterior] = Math.max(0, Number(totales[anterior] || 0) - 1);
                    totales[calidad] = Number(totales[calidad] || 0) + 1;
                    chart.data.datasets[0].data = orden.map(function (k) { return Number(totales[k] || 0); });
                    chart.update();
                }
            }).catch(function () {
                window.alert('No se pudo cambiar la calidad de esta línea.');
            });
        });
    });
})();
</script>
@endsection
