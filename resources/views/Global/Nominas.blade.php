@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
    .global-chart-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
        padding: 18px;
        height: 100%;
    }
    .global-chart-title {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .global-chart-subtitle {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 12px;
    }
    .global-chart-box {
        height: 260px;
    }
    @media (max-width: 768px) {
        .global-chart-box { height: 220px; }
    }
</style>

<div class="container-fluid format_page">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Global Préstamos Nómina</h2>
                        <p class="text-muted mb-0">Resumen general de saldos y préstamos vigentes</p>
                    </div>
                </div>
                <div class="header-actions"></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($saldosgenericos as $listadet)
            <div class="col-lg col-md-4 col-6">
                <div class="p-3 bg-white rounded-3 shadow-sm border">
                    <div class="text-muted fs-8">Capital</div>
                    <div class="fs-5 fw-bold text-success">${{ number_format($listadet->capital, 2) }}</div>
                </div>
            </div>

            <div class="col-lg col-md-4 col-6">
                <div class="p-3 bg-white rounded-3 shadow-sm border">
                    <div class="text-muted fs-8">Abonado</div>
                    <div class="fs-5 fw-bold text-dark">
                        @foreach ($abonado as $Abonado)
                            $ {{ number_format($Abonado->abonado, 2) }}
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg col-md-4 col-6">
                <div class="p-3 bg-white rounded-3 shadow-sm border">
                    <div class="text-muted fs-8">Saldo actual</div>
                    <div class="fs-5 fw-bold text-primary">${{ number_format($saldo_actual, 2) }}</div>
                </div>
            </div>

            <div class="col-lg col-md-4 col-6">
                <div class="p-3 bg-white rounded-3 shadow-sm border">
                    <div class="text-muted fs-8">Saldo vencido</div>
                    <div class="fs-5 fw-bold text-danger">
                        @foreach ($sadovencidoprenom as $saldov)
                            $ {{ number_format($saldov->saldo_vencido, 2) }}
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg col-md-4 col-6">
                <div class="p-3 bg-white rounded-3 shadow-sm border">
                    <div class="text-muted fs-8">Préstamos vigentes</div>
                    <div class="fs-5 fw-bold text-orange">
                        @foreach ($clientevprenom as $cliv)
                            {{ $cliv->cvigentes }}
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
  
    <div class="border-0 mt-3">
        <div class="table-responsive float-search" id="mydatatable-container">
            @if ($permiso1 == 'exportar_globalPresNom')
            <table id="tableNominasExcel" class="table table-stripped table-hover display" >
            @else
            <table id="tableNominas" class="table table-stripped table-hover display" >
            @endif
                <thead>
                    <tr class="text-tr">
                        <th class="text-center fw-bold">Empleado</th>
                        <th class="text-center text-truncate fw-bold">Sucursal</th>
                        <th class="text-center text-truncate fw-bold">Fecha Inicio</th>
                        <th class="text-center text-truncate fw-bold">Plazos</th>
                        <th class="text-center text-truncate fw-bold">Monto Credito</th>
                        <th class="text-center text-truncate fw-bold">Total Prestamo</th>
                        <th class="text-center text-truncate fw-bold">Abonado</th>
                        <th class="text-center text-truncate fw-bold">Saldo Actual</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($globlaprestamosenc as $globalprestamoenc)
                        @php($colortabla = 'text-dark') {{-- Valor por defecto --}}
                        <tr class="">
                            @foreach ($empleadosatraso as $atraso)
                                @if ($atraso->id_empleado == $globalprestamoenc->idempleado)
                                    @php($colortabla = 'text-danger')
                                @break;
                                @else
                                    @php($colortabla = 'text-dark')
                                @endif
                            @endforeach
                        <td class = "{{ $colortabla }}  text-truncate text-start fw-bold">
                            {{ $globalprestamoenc->Nombre_empleado }}</td>
                        <td class = "{{ $colortabla }} text-truncate">{{ $globalprestamoenc->nombres }}</td>
                        <td class = "{{ $colortabla }} text-truncate">{{ \Carbon\Carbon::parse($globalprestamoenc->fecha_inicio)->format('d/m/y') }}
                        </td>
                        <td class = "{{ $colortabla }} text-truncate">{{ $globalprestamoenc->plazos }}</td>
                        <td class = "{{ $colortabla }} text-truncate">$
                            {{ number_format($globalprestamoenc->monto_credito, 2) }}</td>
                        <td class = "{{ $colortabla }} text-truncate">$
                            {{ number_format($globalprestamoenc->total_prestamo, 2) }}</td>

                        @php($abonado = 0)
                        @php($saldo_actual = 0)
                        @php($plazos_pagados = 0)
                        @foreach ($globlaprestamossaldos as $saldos)
                            @if ($globalprestamoenc->idprestamo == $saldos->idprestamo)
                                @php($abonado = $saldos->abonado)
                                @php($saldo_actual = $saldos->saldo_actual)
                                @php($plazos_pagados = $saldos->plazos_pagados)
                            @endif
                        @endforeach

                        @if ($saldo_actual == 0)
                            @php($saldo_actual = $globalprestamoenc->total_prestamo)
                        @else
                        @endif
                        <td class = "{{ $colortabla }} text-truncate">$ {{ number_format($abonado) }}</td>
                        <td class = "{{ $colortabla }} text-truncate">$ {{ number_format($saldo_actual) }}
                        </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="3"></th>
                        <th>Subtotal</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="3"></th>
                        <th>Total</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    
    
    <div class="row g-3 mb-4">
        <div class="col-lg-6 col-12">
            <div class="global-chart-card">
                <div class="global-chart-title">Tipos de préstamos</div>
                <div class="global-chart-subtitle">Nómina vs autos</div>
                <div id="chartTiposPrestamo" class="global-chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="global-chart-card">
                <div class="global-chart-title">Saldo actual</div>
                <div class="global-chart-subtitle">Distribución de saldo</div>
                <div id="chartSaldoActual" class="global-chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="global-chart-card">
                <div class="global-chart-title">Saldo en riesgo</div>
                <div class="global-chart-subtitle">Vigente vs vencido</div>
                <div id="chartRiesgo" class="global-chart-box"></div>
            </div>
        </div>
        <div class="col-lg-6 col-12">
            <div class="global-chart-card">
                <div class="global-chart-title">Atrasos</div>
                <div class="global-chart-subtitle">Pagos pendientes por mes</div>
                <div id="chartAtrasos" class="global-chart-box"></div>
            </div>
        </div>
    </div>
</div>


<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/tableX.js') }}"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script>
    Highcharts.chart('chartTiposPrestamo', {
        chart: { type: 'pie' },
        title: { text: null },
        tooltip: { pointFormat: '<b>{point.y}</b> %' },
        colors: ['#2563eb', '#10b981'],
        series: [{
            name: 'Tipos',
            colorByPoint: true,
            data: [
                { name: 'Nómina', y: 68 },
                { name: 'Autos', y: 32 }
            ]
        }]
    });

    Highcharts.chart('chartSaldoActual', {
        chart: { type: 'column' },
        title: { text: null },
        xAxis: { categories: ['Bajo', 'Medio', 'Alto'] },
        yAxis: { title: { text: 'Monto ($)' } },
        colors: ['#8b5cf6'],
        series: [{ name: 'Saldo', data: [210000, 420000, 650000] }]
    });

    Highcharts.chart('chartRiesgo', {
        chart: { type: 'doughnut' },
        title: { text: null },
        plotOptions: {
            pie: { innerSize: '60%', dataLabels: { enabled: true } }
        },
        colors: ['#10b981', '#f97316'],
        series: [{
            name: 'Riesgo',
            data: [
                { name: 'Vigente', y: 78 },
                { name: 'Vencido', y: 22 }
            ]
        }]
    });

    Highcharts.chart('chartAtrasos', {
        chart: { type: 'line' },
        title: { text: null },
        xAxis: { categories: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'] },
        yAxis: { title: { text: 'Atrasos' } },
        colors: ['#ef4444'],
        series: [{ name: 'Atrasos', data: [6, 8, 5, 9, 7, 4] }]
    });
</script>
@endsection
