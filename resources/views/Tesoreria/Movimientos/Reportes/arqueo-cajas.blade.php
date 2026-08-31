@extends('layouts.app')
@section('content')

<style>
    .arqueo-cajas {
        min-height: 100vh;
    }

    .arqueo-cajas .report-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 55%, #34d399 100%);
        color: #fff;
        border-radius: 18px;
        padding: 1.5rem;
        box-shadow: 0 12px 28px rgba(16, 185, 129, 0.2);
    }

    .arqueo-cajas .report-card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
    }

    .arqueo-cajas .kpi-card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        background: #fff;
    }

    .arqueo-cajas .kpi-card .icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .arqueo-cajas .tag {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        padding: 0.3rem 0.8rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .arqueo-cajas .report-table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        border-bottom: 1px solid rgba(148, 163, 184, 0.35);
        background: #f8fafc;
    }

    .arqueo-cajas .report-table td {
        font-size: 0.9rem;
        color: #0f172a;
        padding: 0.7rem 0.5rem;
    }

    .arqueo-cajas .report-table tbody tr:nth-child(even) td {
        background: rgba(148, 163, 184, 0.08);
    }

    .arqueo-cajas .report-table tbody tr:hover td {
        background: rgba(16, 185, 129, 0.08);
    }

    .arqueo-cajas .chart-card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
    }

    .arqueo-cajas .chart-wrapper {
        height: 250px;
    }
</style>
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class=" arqueo-cajas">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Arqueo de Cajas</h2>
                        <p class="text-muted mb-0">Conciliación y control diario de caja</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-light text-dark border">
                        <i class="fas fa-calendar-day me-1"></i>{{ now()->format('d/m/Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="text-muted small">Saldo inicial</div>
                <div class="h4 fw-bold mb-0">125,000</div>
                <div class="text-muted small">Caja principal</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="text-muted small">Ingresos</div>
                <div class="h4 fw-bold mb-0">48,500</div>
                <div class="text-success small"><i class="fas fa-arrow-up me-1"></i>+6%</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="text-muted small">Egresos</div>
                <div class="h4 fw-bold mb-0">36,200</div>
                <div class="text-danger small"><i class="fas fa-arrow-down me-1"></i>-3%</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="text-muted small">Saldo calculado</div>
                <div class="h4 fw-bold mb-0">137,300</div>
                <div class="text-muted small">Conciliado</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card chart-card h-100">
                <div class="card-header bg-white border-0">
                    <div class="fw-bold">Resumen diario</div>
                    <div class="text-muted small">Ingresos vs egresos</div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="arqueoFlujoChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card chart-card h-100">
                <div class="card-header bg-white border-0">
                    <div class="fw-bold">Composición de efectivo</div>
                    <div class="text-muted small">Billetes vs monedas</div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="arqueoEfectivoChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card report-card">
        <div class="card-header bg-white border-0">
            <div class="fw-bold">Detalle de arqueo</div>
            <div class="text-muted small">Resumen de movimientos y diferencias</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-borderless report-table">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th class="text-end">Monto</th>
                            <th class="text-end">Tipo</th>
                            <th class="text-end">Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Saldo inicial</td>
                            <td class="text-end">125,000</td>
                            <td class="text-end">Entrada</td>
                            <td class="text-end">Caja principal</td>
                        </tr>
                        <tr>
                            <td>Cobranza del día</td>
                            <td class="text-end">48,500</td>
                            <td class="text-end">Entrada</td>
                            <td class="text-end">Ingresos diarios</td>
                        </tr>
                        <tr>
                            <td>Gastos operativos</td>
                            <td class="text-end">-26,200</td>
                            <td class="text-end">Salida</td>
                            <td class="text-end">Pagos menores</td>
                        </tr>
                        <tr>
                            <td>Traspasos a caja chica</td>
                            <td class="text-end">-10,000</td>
                            <td class="text-end">Salida</td>
                            <td class="text-end">Fondo menor</td>
                        </tr>
                        <tr>
                            <td>Diferencia</td>
                            <td class="text-end">-0</td>
                            <td class="text-end">Conciliado</td>
                            <td class="text-end">Sin variaciones</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const flujoCtx = document.getElementById('arqueoFlujoChart');
        if (flujoCtx) {
            new Chart(flujoCtx, {
                type: 'bar',
                data: {
                    labels: ['Ingresos', 'Egresos', 'Saldo final'],
                    datasets: [{
                        label: 'Caja principal',
                        data: [48500, 36200, 137300],
                        backgroundColor: ['#22c55e', '#f97316', '#10b981']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        const efectivoCtx = document.getElementById('arqueoEfectivoChart');
        if (efectivoCtx) {
            new Chart(efectivoCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Billetes', 'Monedas'],
                    datasets: [{
                        data: [110000, 27300],
                        backgroundColor: ['#10b981', '#a7f3d0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    });
</script>

@endsection
