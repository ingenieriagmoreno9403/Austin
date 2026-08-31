@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .chart-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        padding: 20px;
        height: 100%;
    }
    .chart-title {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 6px;
    }
    .chart-subtitle {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 16px;
    }
    .chart-box {
        height: 320px;
    }
    @media screen and (max-width: 768px) {
        .chart-box { height: 260px; }
    }
</style>

<div class="container-fluid format_page">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Empleados" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Empleados
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Gráficas de Empleados</h2>
                        <p class="text-muted mb-0">Indicadores de plantilla, rotación y contrataciones</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6 col-12">
            <div class="chart-card">
                <div class="chart-title">Distribución por puesto</div>
                <div class="chart-subtitle">Cantidad de empleados por puesto</div>
                <div id="chartPuestos" class="chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="chart-card">
                <div class="chart-title">Distribución por sucursal</div>
                <div class="chart-subtitle">Concentración de personal por sede</div>
                <div id="chartSucursales" class="chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="chart-card">
                <div class="chart-title">Rotación de personal</div>
                <div class="chart-subtitle">Activos vs inactivos</div>
                <div id="chartEstados" class="chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="chart-card">
                <div class="chart-title">Contrataciones recientes</div>
                <div class="chart-subtitle">Altas de los últimos 12 meses</div>
                <div id="chartContrataciones" class="chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="chart-card">
                <div class="chart-title">Rangos salariales</div>
                <div class="chart-subtitle">Distribución por salario mensual</div>
                <div id="chartSalarios" class="chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="chart-card">
                <div class="chart-title">Promedio salarial por puesto</div>
                <div class="chart-subtitle">Comparativo de sueldo promedio</div>
                <div id="chartSalarioPuesto" class="chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="chart-card">
                <div class="chart-title">Rendimiento (antigüedad)</div>
                <div class="chart-subtitle">Tiempo en la empresa</div>
                <div id="chartAntiguedad" class="chart-box"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script>
    const graficaPuestos = @json($graficaPuestos ?? []);
    const graficaSucursales = @json($graficaSucursales ?? []);
    const graficaEstados = @json($graficaEstados ?? []);
    const graficaContrataciones = @json($graficaContrataciones ?? []);
    const graficaSalarios = @json($graficaSalarios ?? []);
    const graficaSalarioPuesto = @json($graficaSalarioPuesto ?? ['categorias' => [], 'series' => []]);
    const graficaAntiguedad = @json($graficaAntiguedad ?? []);

    const normalizePieData = (data) => {
        const total = data.reduce((sum, item) => sum + (item.y || 0), 0);
        if (!data.length || total === 0) {
            return [{ name: 'Sin datos', y: 1, color: '#e5e7eb' }];
        }
        return data;
    };

    Highcharts.setOptions({
        lang: { thousandsSep: ',' }
    });

    Highcharts.chart('chartPuestos', {
        chart: { type: 'pie' },
        title: { text: null },
        tooltip: { pointFormat: '<b>{point.y}</b> empleados' },
        plotOptions: {
            pie: { allowPointSelect: true, cursor: 'pointer', dataLabels: { enabled: true } }
        },
        series: [{ name: 'Empleados', colorByPoint: true, data: normalizePieData(graficaPuestos) }]
    });

    Highcharts.chart('chartSucursales', {
        chart: { type: 'pie' },
        title: { text: null },
        tooltip: { pointFormat: '<b>{point.y}</b> empleados' },
        plotOptions: {
            pie: { allowPointSelect: true, cursor: 'pointer', dataLabels: { enabled: true } }
        },
        series: [{ name: 'Empleados', colorByPoint: true, data: normalizePieData(graficaSucursales) }]
    });

    Highcharts.chart('chartEstados', {
        chart: { type: 'pie' },
        title: { text: null },
        tooltip: { pointFormat: '<b>{point.y}</b> empleados' },
        plotOptions: {
            pie: {
                innerSize: '55%',
                dataLabels: { enabled: true, format: '{point.name}: {point.y}' }
            }
        },
        series: [{ name: 'Estatus', colorByPoint: true, data: normalizePieData(graficaEstados) }]
    });

    Highcharts.chart('chartContrataciones', {
        chart: { type: 'column' },
        title: { text: null },
        xAxis: { categories: graficaContrataciones.map(item => item.name) },
        yAxis: { title: { text: 'Altas' } },
        tooltip: { pointFormat: '<b>{point.y}</b> altas' },
        series: [{ name: 'Contrataciones', data: graficaContrataciones.map(item => item.y) }]
    });

    Highcharts.chart('chartSalarios', {
        chart: { type: 'pie' },
        title: { text: null },
        tooltip: { pointFormat: '<b>{point.y}</b> empleados' },
        plotOptions: {
            pie: { allowPointSelect: true, cursor: 'pointer', dataLabels: { enabled: true } }
        },
        series: [{ name: 'Salarios', colorByPoint: true, data: normalizePieData(graficaSalarios) }]
    });

    Highcharts.chart('chartSalarioPuesto', {
        chart: { type: 'bar' },
        title: { text: null },
        xAxis: { categories: graficaSalarioPuesto.categorias },
        yAxis: { title: { text: 'Salario promedio' } },
        tooltip: { pointFormat: '<b>${point.y}</b>' },
        series: [{ name: 'Salario promedio', data: graficaSalarioPuesto.series }]
    });

    Highcharts.chart('chartAntiguedad', {
        chart: { type: 'column' },
        title: { text: null },
        xAxis: { categories: graficaAntiguedad.map(item => item.name) },
        yAxis: { title: { text: 'Empleados' } },
        tooltip: { pointFormat: '<b>{point.y}</b> empleados' },
        series: [{ name: 'Antigüedad', data: graficaAntiguedad.map(item => item.y) }]
    });
</script>
@endsection
