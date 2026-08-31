@extends('layouts.app')
@section('content')

<style>
    .pos-financiera {
        min-height: 100vh;
    }

    .pos-financiera .report-header {
        background: linear-gradient(135deg, #3b82f6 0%, #6d28d9 55%, #60a5fa 100%);
        color: #fff;
        border-radius: 18px;
        padding: 1.5rem;
        box-shadow: 0 12px 28px rgba(59, 130, 246, 0.2);
    }

    .pos-financiera .summary-card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.3);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    .pos-financiera .summary-card .icon-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }

    .pos-financiera .tag {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(59, 130, 246, 0.1);
        color: #ffffff;
        padding: 0.3rem 0.8rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .pos-financiera .report-card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    }

    .pos-financiera .report-title {
        font-weight: 700;
        color: #0f172a;
    }

    .pos-financiera .report-table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        border-bottom: 1px solid rgba(148, 163, 184, 0.35);
        background: #f8fafc;
    }

    .pos-financiera .report-table td {
        font-size: 0.9rem;
        color: #0f172a;
        padding: 0.7rem 0.5rem;
    }

    .pos-financiera .report-table .section-row td {
        font-weight: 700;
        color: #1e3a8a;
        padding-top: 1rem;
    }

    .pos-financiera .report-table .total-row td {
        font-weight: 700;
        border-top: 1px solid rgba(148, 163, 184, 0.5);
        background: rgba(59, 130, 246, 0.08);
    }

    .pos-financiera .report-table .muted {
        color: #64748b;
        font-size: 0.8rem;
    }

    .pos-financiera .report-table tbody tr {
        border-radius: 10px;
    }

    .pos-financiera .report-table tbody tr:hover td {
        background: rgba(59, 130, 246, 0.08);
    }

    .pos-financiera .report-table tbody tr:nth-child(even) td {
        background: rgba(148, 163, 184, 0.08);
    }

    .pos-financiera .report-table tbody tr.section-row td,
    .pos-financiera .report-table tbody tr.total-row td {
        background: transparent;
    }

    .pos-financiera .kpi-card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
        background: #ffffff;
    }

    .pos-financiera .kpi-card .icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .pos-financiera .chart-card {
        border-radius: 16px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
    }

    .pos-financiera .chart-wrapper {
        height: 260px;
    }

    .pos-financiera .pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.25rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .pos-financiera .export-btn {
        border-radius: 999px;
        padding: 0.35rem 1rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border: 1px solid transparent;
        box-shadow: 0 10px 18px rgba(15, 23, 42, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .pos-financiera .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 22px rgba(15, 23, 42, 0.12);
    }

    .pos-financiera .export-btn.pdf {
        background: rgba(239, 68, 68, 0.12);
        color: #b91c1c;
    }

    .pos-financiera .export-btn.excel {
        background: rgba(16, 185, 129, 0.12);
        color: #047857;
    }

    .pos-financiera .export-btn.csv {
        background: rgba(59, 130, 246, 0.12);
        color: #1d4ed8;
    }
</style>
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class=" pos-financiera">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-scale-balanced"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Reporte de Posición Financiera</h2>
                        <p class="text-muted mb-0">Resumen general de activos, pasivos y capital</p>
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

    <div class="card report-card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label text-muted small">Fecha inicial</label>
                    <input type="date" class="form-control" value="{{ now()->format('Y-m-01') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted small">Fecha final</label>
                    <input type="date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Comparar fechas
                    </button>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="export-btn pdf">
                        <i class="fas fa-file-pdf"></i>Exportar PDF
                    </button>
                    <button class="export-btn excel">
                        <i class="fas fa-file-excel"></i>Exportar Excel
                    </button>
                    <button class="export-btn csv">
                        <i class="fas fa-file-csv"></i>Exportar CSV
                    </button>
                </div>
                <div class="col-12 text-muted small">
                    Selecciona un rango para comparar fecha contra fecha y ver el impacto financiero.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Ingresos </div>
                        <div class="h4 fw-bold mb-0">1,064,449</div>
                        <div class="pill mt-2" style="background: rgba(59, 130, 246, 0.12); color: #1d4ed8;">
                            <i class="fas fa-arrow-up"></i> 100%
                        </div>
                    </div>
                    <div class="icon" style="background: rgba(59, 130, 246, 0.15); color: #1d4ed8;">
                        <i class="fas fa-circle-dollar-to-slot"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Gastos generales</div>
                        <div class="h4 fw-bold mb-0">629,146</div>
                        <div class="pill mt-2" style="background: rgba(248, 113, 113, 0.15); color: #b91c1c;">
                            <i class="fas fa-arrow-down"></i> 59%
                        </div>
                    </div>
                    <div class="icon" style="background: rgba(248, 113, 113, 0.15); color: #b91c1c;">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Remanente neto</div>
                        <div class="h4 fw-bold mb-0">434,159</div>
                        <div class="pill mt-2" style="background: rgba(16, 185, 129, 0.15); color: #047857;">
                            <i class="fas fa-chart-line"></i> 41%
                        </div>
                    </div>
                    <div class="icon" style="background: rgba(16, 185, 129, 0.15); color: #047857;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Suma activo</div>
                        <div class="h4 fw-bold mb-0">6,506,985</div>
                        <div class="pill mt-2" style="background: rgba(109, 40, 217, 0.12); color: #6d28d9;">
                            <i class="fas fa-coins"></i> feb-26
                        </div>
                    </div>
                    <div class="icon" style="background: rgba(109, 40, 217, 0.15); color: #6d28d9;">
                        <i class="fas fa-scale-balanced"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card chart-card h-100">
                <div class="card-header bg-white border-0">
                    <div class="report-title">Ingresos vs Gastos</div>
                    <div class="text-muted small">Comparativo feb-26 vs feb-25</div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="ingresosGastosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card chart-card h-100">
                <div class="card-header bg-white border-0">
                    <div class="report-title">Activo vs Pasivo</div>
                    <div class="text-muted small">Participación feb-26</div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="activoPasivoChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card chart-card h-100">
                <div class="card-header bg-white border-0">
                    <div class="report-title">Flujo mensual</div>
                    <div class="text-muted small">Entradas vs salidas por mes</div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="flujoMensualChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card report-card mb-4">
        <div class="card-header bg-white border-0">
            <div class="report-title">Estado de Posición Financiera</div>
            <div class="text-muted small">COPARMEX LAGUNA, SP · feb-26 vs feb-25</div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless report-table">
                            <thead>
                                <tr>
                                    <th>Activo</th>
                                    <th class="text-end">feb-26</th>
                                    <th class="text-end">feb-25</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="section-row">
                                    <td>Circulante</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Caja chica</td>
                                    <td class="text-end">8,082</td>
                                    <td class="text-end">922</td>
                                </tr>
                                <tr>
                                    <td>Bancos</td>
                                    <td class="text-end">134,031</td>
                                    <td class="text-end">123,882</td>
                                </tr>
                                <tr>
                                    <td>Clientes</td>
                                    <td class="text-end">795,507</td>
                                    <td class="text-end">233,024</td>
                                </tr>
                                <tr>
                                    <td>Deudores</td>
                                    <td class="text-end">5,199</td>
                                    <td class="text-end">5,199</td>
                                </tr>
                                <tr>
                                    <td>IVA por acreditar</td>
                                    <td class="text-end">108,322</td>
                                    <td class="text-end">63,440</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Suma activo circulante</td>
                                    <td class="text-end">1,051,141</td>
                                    <td class="text-end">426,467</td>
                                </tr>
                                <tr class="section-row">
                                    <td>Fijo</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Terreno</td>
                                    <td class="text-end">190,000</td>
                                    <td class="text-end">190,000</td>
                                </tr>
                                <tr>
                                    <td>Actualización terreno</td>
                                    <td class="text-end">870,182</td>
                                    <td class="text-end">870,182</td>
                                </tr>
                                <tr>
                                    <td>Edificio</td>
                                    <td class="text-end">2,615,651</td>
                                    <td class="text-end">2,615,651</td>
                                </tr>
                                <tr>
                                    <td>Actualización Edificio</td>
                                    <td class="text-end">2,849,334</td>
                                    <td class="text-end">2,849,334</td>
                                </tr>
                                <tr>
                                    <td>Eq. de Transporte</td>
                                    <td class="text-end">102,504</td>
                                    <td class="text-end">102,504</td>
                                </tr>
                                <tr>
                                    <td>Actualización Eq. de transporte</td>
                                    <td class="text-end">93,079</td>
                                    <td class="text-end">93,079</td>
                                </tr>
                                <tr>
                                    <td>Mobiliario y eq. de oficina</td>
                                    <td class="text-end">670,726</td>
                                    <td class="text-end">670,726</td>
                                </tr>
                                <tr>
                                    <td>Actualización mobiliario y eq.</td>
                                    <td class="text-end">355,037</td>
                                    <td class="text-end">355,037</td>
                                </tr>
                                <tr>
                                    <td>Eq. de computo</td>
                                    <td class="text-end">847,891</td>
                                    <td class="text-end">712,129</td>
                                </tr>
                                <tr>
                                    <td>Actualización eq. de computo</td>
                                    <td class="text-end">233,739</td>
                                    <td class="text-end">233,739</td>
                                </tr>
                                <tr>
                                    <td>Dep. acum. Activo fijo</td>
                                    <td class="text-end">-2,166,516</td>
                                    <td class="text-end">-2,166,516</td>
                                </tr>
                                <tr>
                                    <td>Actualización Dep. acum. Activo fijo</td>
                                    <td class="text-end">-1,205,783</td>
                                    <td class="text-end">-1,205,783</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Suma activo fijo</td>
                                    <td class="text-end">5,455,844</td>
                                    <td class="text-end">5,320,082</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Suma activo</td>
                                    <td class="text-end">6,506,985</td>
                                    <td class="text-end">5,746,549</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless report-table">
                            <thead>
                                <tr>
                                    <th>Pasivo y Capital</th>
                                    <th class="text-end">feb-26</th>
                                    <th class="text-end">feb-25</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="section-row">
                                    <td>Pasivo</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Proveedores</td>
                                    <td class="text-end">524,741</td>
                                    <td class="text-end">425,919</td>
                                </tr>
                                <tr>
                                    <td>Acreedores</td>
                                    <td class="text-end">196,362</td>
                                    <td class="text-end">825,937</td>
                                </tr>
                                <tr>
                                    <td>Impuestos por pagar</td>
                                    <td class="text-end">16,455</td>
                                    <td class="text-end">36,476</td>
                                </tr>
                                <tr>
                                    <td>IVA por trasladar</td>
                                    <td class="text-end">13,445</td>
                                    <td class="text-end">11,058</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Suma Pasivo</td>
                                    <td class="text-end">751,003</td>
                                    <td class="text-end">1,299,390</td>
                                </tr>
                                <tr class="section-row">
                                    <td>Capital</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td>Patrimonio</td>
                                    <td class="text-end">3,915,617</td>
                                    <td class="text-end">3,915,617</td>
                                </tr>
                                <tr>
                                    <td>Exceso de Capital</td>
                                    <td class="text-end">1,883,989</td>
                                    <td class="text-end">1,883,989</td>
                                </tr>
                                <tr>
                                    <td>Act. de exceso de capital</td>
                                    <td class="text-end">287,576</td>
                                    <td class="text-end">287,576</td>
                                </tr>
                                <tr>
                                    <td>Actualización Patrimonio</td>
                                    <td class="text-end">-1,856,314</td>
                                    <td class="text-end">-1,856,314</td>
                                </tr>
                                <tr>
                                    <td>Actualización de activos</td>
                                    <td class="text-end">3,195,588</td>
                                    <td class="text-end">3,195,588</td>
                                </tr>
                                <tr>
                                    <td>Resultado de Ej. Ant.</td>
                                    <td class="text-end">-2,104,633</td>
                                    <td class="text-end">-3,118,005</td>
                                </tr>
                                <tr>
                                    <td>Resultado del ejercicio</td>
                                    <td class="text-end">434,159</td>
                                    <td class="text-end">138,708</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Suma capital</td>
                                    <td class="text-end">5,755,982</td>
                                    <td class="text-end">4,447,159</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Suma Pasivo mas Capital</td>
                                    <td class="text-end">6,506,985</td>
                                    <td class="text-end">5,746,549</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="muted mt-2">Datos cargados segun las capturas proporcionadas.</div>
        </div>
    </div>

    <div class="card report-card mb-4">
        <div class="card-header bg-white border-0">
            <div class="report-title">Estado de resultados</div>
            <div class="text-muted small">Comparativo feb-26 vs feb-25</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-borderless report-table">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th class="text-end">feb-26</th>
                            <th class="text-end">%</th>
                            <th class="text-end">feb-25</th>
                            <th class="text-end">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="section-row">
                            <td>Ingresos</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Cuotas trimestrales</td>
                            <td class="text-end">0</td>
                            <td class="text-end">0%</td>
                            <td class="text-end">4,500</td>
                            <td class="text-end">1%</td>
                        </tr>
                        <tr>
                            <td>Cuotas semestrales</td>
                            <td class="text-end">5,350</td>
                            <td class="text-end">1%</td>
                            <td class="text-end">12,750</td>
                            <td class="text-end">3%</td>
                        </tr>
                        <tr>
                            <td>Cuotas anuales</td>
                            <td class="text-end">246,100</td>
                            <td class="text-end">23%</td>
                            <td class="text-end">105,455</td>
                            <td class="text-end">22%</td>
                        </tr>
                        <tr>
                            <td>Cuotas extraordinarias</td>
                            <td class="text-end">12,000</td>
                            <td class="text-end">1%</td>
                            <td class="text-end">0</td>
                            <td class="text-end">0%</td>
                        </tr>
                        <tr>
                            <td>Cursos y capacitación</td>
                            <td class="text-end">19,908</td>
                            <td class="text-end">2%</td>
                            <td class="text-end">0</td>
                            <td class="text-end">0%</td>
                        </tr>
                        <tr>
                            <td>Administración de practicantes</td>
                            <td class="text-end">781,091</td>
                            <td class="text-end">73%</td>
                            <td class="text-end">365,138</td>
                            <td class="text-end">75%</td>
                        </tr>
                        <tr class="total-row">
                            <td>Ingresos</td>
                            <td class="text-end">1,064,449</td>
                            <td class="text-end">100%</td>
                            <td class="text-end">487,843</td>
                            <td class="text-end">100%</td>
                        </tr>
                        <tr class="section-row">
                            <td>Gastos generales</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>Sueldos y gastos de seguridad social</td>
                            <td class="text-end">97,226</td>
                            <td class="text-end">9%</td>
                            <td class="text-end">112,040</td>
                            <td class="text-end">23%</td>
                        </tr>
                        <tr>
                            <td>Gratificación practicantes</td>
                            <td class="text-end">324,298</td>
                            <td class="text-end">30%</td>
                            <td class="text-end">160,308</td>
                            <td class="text-end">33%</td>
                        </tr>
                        <tr>
                            <td>Gastos de administración</td>
                            <td class="text-end">89,519</td>
                            <td class="text-end">8%</td>
                            <td class="text-end">33,828</td>
                            <td class="text-end">7%</td>
                        </tr>
                        <tr>
                            <td>Gastos de mantenimiento</td>
                            <td class="text-end">19,475</td>
                            <td class="text-end">2%</td>
                            <td class="text-end">19,026</td>
                            <td class="text-end">4%</td>
                        </tr>
                        <tr>
                            <td>Eventos y conferencias</td>
                            <td class="text-end">70,785</td>
                            <td class="text-end">7%</td>
                            <td class="text-end">5,671</td>
                            <td class="text-end">1%</td>
                        </tr>
                        <tr>
                            <td>IVA Acreditable al gasto</td>
                            <td class="text-end">27,843</td>
                            <td class="text-end">3%</td>
                            <td class="text-end">17,732</td>
                            <td class="text-end">4%</td>
                        </tr>
                        <tr class="total-row">
                            <td>Gastos generales</td>
                            <td class="text-end">629,146</td>
                            <td class="text-end">59%</td>
                            <td class="text-end">348,605</td>
                            <td class="text-end">71%</td>
                        </tr>
                        <tr class="total-row">
                            <td>Remanente de operación</td>
                            <td class="text-end">435,303</td>
                            <td class="text-end">41%</td>
                            <td class="text-end">139,238</td>
                            <td class="text-end">29%</td>
                        </tr>
                        <tr>
                            <td>Gastos financieros</td>
                            <td class="text-end">1,144</td>
                            <td class="text-end">0%</td>
                            <td class="text-end">530</td>
                            <td class="text-end">0%</td>
                        </tr>
                        <tr class="total-row">
                            <td>Remanente neto</td>
                            <td class="text-end">434,159</td>
                            <td class="text-end">41%</td>
                            <td class="text-end">138,708</td>
                            <td class="text-end">28%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card chart-card h-100">
                <div class="card-header bg-white border-0">
                    <div class="report-title">Detalle de ingresos</div>
                    <div class="text-muted small">Composición feb-26</div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="detalleIngresosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card chart-card h-100">
                <div class="card-header bg-white border-0">
                    <div class="report-title">Remanente vs gastos</div>
                    <div class="text-muted small">Relación feb-26</div>
                </div>
                <div class="card-body">
                    <div class="chart-wrapper">
                        <canvas id="remanenteChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card report-card">
        <div class="card-header bg-white border-0">
            <div class="report-title">Indicadores clave</div>
            <div class="text-muted small">Impacto financiero del periodo</div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="text-muted small">Remanente operativo</div>
                        <div class="h4 fw-bold mb-0">435,303</div>
                        <div class="pill mt-2" style="background: rgba(59, 130, 246, 0.12); color: #1d4ed8;">
                            <i class="fas fa-arrow-up"></i> 41%
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="text-muted small">Gastos financieros</div>
                        <div class="h4 fw-bold mb-0">1,144</div>
                        <div class="pill mt-2" style="background: rgba(248, 113, 113, 0.15); color: #b91c1c;">
                            <i class="fas fa-arrow-down"></i> 0%
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card p-3 h-100">
                        <div class="text-muted small">Remanente neto</div>
                        <div class="h4 fw-bold mb-0">434,159</div>
                        <div class="pill mt-2" style="background: rgba(16, 185, 129, 0.15); color: #047857;">
                            <i class="fas fa-chart-line"></i> 41%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ingresosGastosCtx = document.getElementById('ingresosGastosChart');
        if (ingresosGastosCtx) {
            new Chart(ingresosGastosCtx, {
                type: 'bar',
                data: {
                    labels: ['Ingresos', 'Gastos generales', 'Remanente neto'],
                    datasets: [
                        {
                            label: 'feb-26',
                            data: [1064449, 629146, 434159],
                            backgroundColor: ['#3b82f6', '#f97316', '#10b981']
                        },
                        {
                            label: 'feb-25',
                            data: [487843, 348605, 138708],
                            backgroundColor: ['#93c5fd', '#fdba74', '#6ee7b7']
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        const activoPasivoCtx = document.getElementById('activoPasivoChart');
        if (activoPasivoCtx) {
            new Chart(activoPasivoCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Activo', 'Pasivo', 'Capital'],
                    datasets: [{
                        data: [6506985, 751003, 5755982],
                        backgroundColor: ['#3b82f6', '#f97316', '#10b981'],
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

        const detalleIngresosCtx = document.getElementById('detalleIngresosChart');
        if (detalleIngresosCtx) {
            new Chart(detalleIngresosCtx, {
                type: 'bar',
                data: {
                    labels: [
                        'Cuotas semestrales',
                        'Cuotas anuales',
                        'Cuotas extraordinarias',
                        'Cursos y capacitación',
                        'Adm. practicantes'
                    ],
                    datasets: [{
                        label: 'feb-26',
                        data: [5350, 246100, 12000, 19908, 781091],
                        backgroundColor: ['#60a5fa', '#3b82f6', '#a78bfa', '#38bdf8', '#10b981']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        const remanenteCtx = document.getElementById('remanenteChart');
        if (remanenteCtx) {
            new Chart(remanenteCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Remanente neto', 'Gastos generales'],
                    datasets: [{
                        data: [434159, 629146],
                        backgroundColor: ['#10b981', '#f97316'],
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

        const flujoMensualCtx = document.getElementById('flujoMensualChart');
        if (flujoMensualCtx) {
            new Chart(flujoMensualCtx, {
                type: 'line',
                data: {
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                    datasets: [
                        {
                            label: 'Entradas',
                            data: [320000, 410000, 380000, 450000, 520000, 480000, 510000, 470000, 495000, 530000, 560000, 590000],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.15)',
                            tension: 0.35,
                            fill: true
                        },
                        {
                            label: 'Salidas',
                            data: [280000, 360000, 340000, 390000, 430000, 410000, 420000, 405000, 430000, 470000, 500000, 520000],
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.12)',
                            tension: 0.35,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>

@endsection
