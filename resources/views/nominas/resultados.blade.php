@extends('layouts.app')
@section('content')

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
    .nomina-charts-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        padding: 18px;
        height: 100%;
    }
    .nomina-chart-title {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .nomina-chart-subtitle {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 12px;
    }
    .nomina-chart-box {
        height: 280px;
    }
    @media (max-width: 768px) {
        .nomina-chart-box { height: 220px; }
    }
</style>

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-chart-column"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('vernominas') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Nómina
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Resultados de Nómina</h2>
                        <p class="text-muted mb-0">
                            {{ $nominaEnc->nombre_nomina }}
                            &middot;
                            {{ \Carbon\Carbon::parse($nominaEnc->fecha_inicio)->format('d/m/Y') }}
                            -
                            {{ \Carbon\Carbon::parse($nominaEnc->fecha_fin)->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6 col-12">
            <div class="nomina-charts-card">
                <div class="nomina-chart-title">Retenciones por periodo</div>
                <div class="nomina-chart-subtitle">ISR, IMSS e INFONAVIT</div>
                <div id="chartRetenciones" class="nomina-chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="nomina-charts-card">
                <div class="nomina-chart-title">Bonos y horas extras</div>
                <div class="nomina-chart-subtitle">Totales de la nómina</div>
                <div id="chartBonosHoras" class="nomina-chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="nomina-charts-card">
                <div class="nomina-chart-title">Incapacidades y faltas</div>
                <div class="nomina-chart-subtitle">Eventos registrados</div>
                <div id="chartIncapacidades" class="nomina-chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="nomina-charts-card">
                <div class="nomina-chart-title">Vacaciones y días no laborados</div>
                <div class="nomina-chart-subtitle">Totales del periodo</div>
                <div id="chartVacaciones" class="nomina-chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="nomina-charts-card">
                <div class="nomina-chart-title">ISR retenido</div>
                <div class="nomina-chart-subtitle">Monto total retenido</div>
                <div id="chartIsr" class="nomina-chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="nomina-charts-card">
                <div class="nomina-chart-title">Impuestos y deducciones</div>
                <div class="nomina-chart-subtitle">Distribución de cargas</div>
                <div id="chartImpuestos" class="nomina-chart-box"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script>
    const chartData = @json($chartData);
    const periodoLabel = @json($nominaEnc->nombre_nomina);

    Highcharts.chart('chartRetenciones', {
        chart: { type: 'column' },
        title: { text: null },
        xAxis: { categories: [periodoLabel] },
        yAxis: { title: { text: 'Monto ($)' } },
        colors: ['#2563eb', '#10b981', '#8b5cf6'],
        tooltip: { shared: true, valuePrefix: '$' },
        series: [
            { name: 'ISR', data: [chartData.isr] },
            { name: 'IMSS', data: [chartData.imss] },
            { name: 'INFONAVIT', data: [chartData.infonavit] }
        ]
    });

    Highcharts.chart('chartBonosHoras', {
        chart: { type: 'column' },
        title: { text: null },
        xAxis: { categories: ['Bonos', 'Horas extra'] },
        yAxis: { title: { text: 'Monto ($)' } },
        colors: ['#10b981', '#2563eb'],
        tooltip: { valuePrefix: '$' },
        series: [{
            name: 'Monto',
            data: [chartData.bonos, chartData.horasExtras],
            showInLegend: false
        }]
    });

    Highcharts.chart('chartIncapacidades', {
        chart: { type: 'bar' },
        title: { text: null },
        xAxis: { categories: ['Incapacidades', 'Faltas'] },
        yAxis: { title: { text: 'Días / eventos' }, allowDecimals: false },
        colors: ['#8b5cf6'],
        series: [{
            name: 'Eventos',
            data: [chartData.incapacidades, chartData.faltas],
            showInLegend: false
        }]
    });

    Highcharts.chart('chartVacaciones', {
        chart: { type: 'column' },
        title: { text: null },
        xAxis: { categories: ['Vacaciones', 'Días no laborados'] },
        yAxis: { title: { text: 'Días' }, allowDecimals: false },
        colors: ['#38bdf8', '#0ea5e9'],
        series: [{
            name: 'Días',
            data: [chartData.vacaciones, chartData.diasNoLaborados],
            showInLegend: false
        }]
    });

    Highcharts.chart('chartIsr', {
        chart: { type: 'column' },
        title: { text: null },
        xAxis: { categories: [periodoLabel] },
        yAxis: { title: { text: 'Monto ($)' } },
        colors: ['#1e40af'],
        tooltip: { valuePrefix: '$' },
        series: [{
            name: 'ISR retenido',
            data: [chartData.isr],
            showInLegend: false
        }]
    });

    const impuestosPie = [
        { name: 'ISR', y: chartData.isr },
        { name: 'IMSS', y: chartData.imss },
        { name: 'INFONAVIT', y: chartData.infonavit },
        { name: 'FONACOT', y: chartData.fonacot },
        { name: 'Deudores', y: chartData.deudores }
    ].filter(item => item.y > 0);

    Highcharts.chart('chartImpuestos', {
        chart: { type: 'pie' },
        title: { text: null },
        tooltip: { pointFormat: '<b>${point.y:,.2f}</b> ({point.percentage:.1f}%)' },
        colors: ['#2563eb', '#10b981', '#8b5cf6', '#38bdf8', '#f59e0b'],
        series: [{
            name: 'Monto',
            colorByPoint: true,
            data: impuestosPie.length ? impuestosPie : [{ name: 'Sin deducciones', y: 1 }]
        }]
    });
</script>
@endsection
