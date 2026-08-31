@extends('layouts.app')
@section('content')

<style>
    .estado-cuenta {
        min-height: 100vh;
    }

    .estado-cuenta .report-header {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 55%, #a78bfa 100%);
        color: #fff;
        border-radius: 18px;
        padding: 1.5rem;
        box-shadow: 0 12px 28px rgba(99, 102, 241, 0.2);
    }

    .estado-cuenta .report-card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
    }

    .estado-cuenta .kpi-card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        background: #fff;
    }

    .estado-cuenta .kpi-card .icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .estado-cuenta .tag {
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

    .estado-cuenta .report-table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        border-bottom: 1px solid rgba(148, 163, 184, 0.35);
        background: #f8fafc;
    }

    .estado-cuenta .report-table td {
        font-size: 0.9rem;
        color: #0f172a;
        padding: 0.7rem 0.5rem;
    }

    .estado-cuenta .report-table tbody tr:nth-child(even) td {
        background: rgba(148, 163, 184, 0.08);
    }

    .estado-cuenta .report-table tbody tr:hover td {
        background: rgba(99, 102, 241, 0.08);
    }

    .estado-cuenta .chart-card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
    }

    .estado-cuenta .chart-wrapper {
        height: 250px;
    }
</style>
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class=" estado-cuenta">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Estado de Cuenta</h2>
                        <p class="text-muted mb-0">Detalle de movimientos y saldos por cuenta</p>
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
                <div class="h4 fw-bold mb-0">350,000</div>
                <div class="text-muted small">Cuenta principal</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="text-muted small">Ingresos</div>
                <div class="h4 fw-bold mb-0">210,450</div>
                <div class="text-success small"><i class="fas fa-arrow-up me-1"></i>+12%</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="text-muted small">Egresos</div>
                <div class="h4 fw-bold mb-0">128,600</div>
                <div class="text-danger small"><i class="fas fa-arrow-down me-1"></i>-6%</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="text-muted small">Saldo final</div>
                <div class="h4 fw-bold mb-0">431,850</div>
                <div class="text-muted small">Periodo actual</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card chart-card h-100">
                <div class="card-header bg-white border-0">
                    <div class="fw-bold">Flujo mensual</div>
                    <div class="text-muted small">Ingresos vs egresos</div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="flujoCuentaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card chart-card h-100">
                <div class="card-header bg-white border-0">
                    <div class="fw-bold">Distribución</div>
                    <div class="text-muted small">Ingresos por tipo</div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="distribucionCuentaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card report-card">
        <div class="card-header bg-white border-0">
            <div class="fw-bold">Movimientos recientes</div>
            <div class="text-muted small">Últimos registros del periodo</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-borderless report-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th class="text-end">Ingreso</th>
                            <th class="text-end">Egreso</th>
                            <th class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>2026-02-01</td>
                            <td>Cuota anual</td>
                            <td class="text-end">120,000</td>
                            <td class="text-end">-</td>
                            <td class="text-end">470,000</td>
                        </tr>
                        <tr>
                            <td>2026-02-05</td>
                            <td>Pago de servicios</td>
                            <td class="text-end">-</td>
                            <td class="text-end">35,600</td>
                            <td class="text-end">434,400</td>
                        </tr>
                        <tr>
                            <td>2026-02-12</td>
                            <td>Ingresos de capacitación</td>
                            <td class="text-end">45,350</td>
                            <td class="text-end">-</td>
                            <td class="text-end">479,750</td>
                        </tr>
                        <tr>
                            <td>2026-02-18</td>
                            <td>Gastos administrativos</td>
                            <td class="text-end">-</td>
                            <td class="text-end">28,900</td>
                            <td class="text-end">450,850</td>
                        </tr>
                        <tr>
                            <td>2026-02-26</td>
                            <td>Entrega de fondo</td>
                            <td class="text-end">-</td>
                            <td class="text-end">19,000</td>
                            <td class="text-end">431,850</td>
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
        const flujoCtx = document.getElementById('flujoCuentaChart');
        if (flujoCtx) {
            new Chart(flujoCtx, {
                type: 'line',
                data: {
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                    datasets: [
                        {
                            label: 'Ingresos',
                            data: [180000, 210450, 195000, 230000, 220000, 240000],
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.15)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Egresos',
                            data: [120000, 128600, 110000, 135000, 125000, 140000],
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.12)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
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

        const distribucionCtx = document.getElementById('distribucionCuentaChart');
        if (distribucionCtx) {
            new Chart(distribucionCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Cuotas', 'Capacitaciones', 'Otros'],
                    datasets: [{
                        data: [150000, 45350, 15100],
                        backgroundColor: ['#6366f1', '#22c55e', '#f97316'],
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
