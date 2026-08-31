@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<link href="{{ asset('css/tables.css') }}" rel="stylesheet">

<style>
    .aportaciones-table-wrapper {
        overflow-x: auto;
    }
    .aportaciones-table-wrapper table th,
    .aportaciones-table-wrapper table td {
        white-space: nowrap;
        font-size: 12px;
    }
    .aportaciones-resumen-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        padding: 18px;
    }
    .aportaciones-resumen-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    .aportaciones-resumen-item:last-child {
        border-bottom: 0;
        font-weight: 700;
        color: #1e40af;
    }
</style>

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-building-columns"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('index_aportaciones_patronales') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Aportaciones
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">{{ $aportacionEnc->nombre }}</h2>
                        <p class="text-muted mb-0">
                            Periodo del {{ date('d/m/Y', strtotime($aportacionEnc->fecha_inicio)) }}
                            al {{ date('d/m/Y', strtotime($aportacionEnc->fecha_fin)) }}
                            ({{ $aportacionEnc->dias_periodo }} días)
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap gap-2">
                    <a class="btn btn-info fs-7 mb-2" href="{{ route('recalcular_aportaciones_patronales', $aportacionEnc->id) }}" title="Recalcular con nóminas cerradas del periodo">
                        <i class="fa-solid fa-rotate"></i> Recalcular
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4 col-12">
            <div class="aportaciones-resumen-card h-100">
                <div class="fw-bold mb-3">Total patronal del periodo</div>
                <div class="aportaciones-resumen-item"><span>Riesgos de trabajo</span><span>$ {{ number_format($totales['riesgo_trabajo'], 2) }}</span></div>
                <div class="aportaciones-resumen-item"><span>EM - Cuota fija</span><span>$ {{ number_format($totales['cuota_fija'], 2) }}</span></div>
                <div class="aportaciones-resumen-item"><span>EM - Excedente 3 UMA</span><span>$ {{ number_format($totales['enf_mat_excedente'], 2) }}</span></div>
                <div class="aportaciones-resumen-item"><span>EM - Prestaciones en dinero</span><span>$ {{ number_format($totales['enf_mat_dinero'], 2) }}</span></div>
                <div class="aportaciones-resumen-item"><span>EM - Gastos médicos pensionados</span><span>$ {{ number_format($totales['enf_mat_gastos_pensionados'], 2) }}</span></div>
                <div class="aportaciones-resumen-item"><span>Invalidez y vida</span><span>$ {{ number_format($totales['invalidez_vida'], 2) }}</span></div>
                <div class="aportaciones-resumen-item"><span>Guarderías y prestaciones</span><span>$ {{ number_format($totales['guarderia'], 2) }}</span></div>
                <div class="aportaciones-resumen-item"><span>Retiro</span><span>$ {{ number_format($totales['retiro'], 2) }}</span></div>
                <div class="aportaciones-resumen-item"><span>Cesantía y vejez patronal</span><span>$ {{ number_format($totales['cesantia_vejez'], 2) }}</span></div>
                <div class="aportaciones-resumen-item"><span>INFONAVIT</span><span>$ {{ number_format($totales['infonavit'], 2) }}</span></div>
                <div class="aportaciones-resumen-item"><span>TOTAL PATRONAL</span><span>$ {{ number_format($totales['total'], 2) }}</span></div>
            </div>
        </div>
        <div class="col-lg-8 col-12">
            <div class="alert alert-light border mb-0 h-100">
                <div class="fw-semibold mb-2"><i class="fa-solid fa-circle-info me-1"></i> Base del cálculo</div>
                <ul class="mb-0 ps-3 small">
                    <li><strong>SBC</strong>: salario diario integrado promedio ponderado por días, tomado de nóminas cerradas del periodo.</li>
                    <li><strong>D</strong>: suma de días laborados (días cotizados) por empleado en el periodo.</li>
                    <li><strong>Prima de riesgo</strong>, cuotas EM, retiro, guarderías e INFONAVIT: parámetros de Factores Generales.</li>
                    <li><strong>Cesantía y vejez patronal</strong>: porcentaje según rango de SBC en el catálogo de Cesantía y Vejez.</li>
                    <li><strong>Excedente 3 UMA</strong>: si SBC &gt; (UMA × 3), aplica (SBC − 3 UMA) × D × tasa; de lo contrario es 0.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 aportaciones-table-wrapper">
            <table class="table table-stripped table-hover display" id="tablenomina">
                <thead>
                    <tr>
                        <th>No. Empleado</th>
                        <th>Empleado</th>
                        <th>SBC</th>
                        <th>Días cotizados</th>
                        <th>RT</th>
                        <th>EM Cuota fija</th>
                        <th>EM Excedente</th>
                        <th>EM Prest. dinero</th>
                        <th>EM Gastos méd.</th>
                        <th>Inv. y vida</th>
                        <th>Guarderías</th>
                        <th>Retiro</th>
                        <th>C y V patronal</th>
                        <th>INFONAVIT</th>
                        <th>Total patronal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($aportacionDet as $row)
                        <tr>
                            <td>{{ $row->numero_empleado ?? '-' }}</td>
                            <td>{{ $row->nombre_empleado }}</td>
                            <td>$ {{ number_format($row->sbc, 2) }}</td>
                            <td>{{ number_format($row->dias_cotizados, 2) }}</td>
                            <td>$ {{ number_format($row->riesgo_trabajo, 2) }}</td>
                            <td>$ {{ number_format($row->cuota_fija, 2) }}</td>
                            <td>$ {{ number_format($row->enf_mat_excedente, 2) }}</td>
                            <td>$ {{ number_format($row->enf_mat_dinero, 2) }}</td>
                            <td>$ {{ number_format($row->enf_mat_gastos_pensionados, 2) }}</td>
                            <td>$ {{ number_format($row->invalidez_vida, 2) }}</td>
                            <td>$ {{ number_format($row->guarderia, 2) }}</td>
                            <td>$ {{ number_format($row->retiro, 2) }}</td>
                            <td>$ {{ number_format($row->cesantia_vejez, 2) }}</td>
                            <td>$ {{ number_format($row->infonavit, 2) }}</td>
                            <td class="fw-semibold">$ {{ number_format($row->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center text-muted py-4">
                                No hay empleados calculados. Verifique que existan nóminas cerradas dentro del periodo y use Recalcular.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($aportacionDet->count())
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="2">Totales</td>
                            <td>$ {{ number_format($totales['sbc'], 2) }}</td>
                            <td>{{ number_format($totales['dias_cotizados'], 2) }}</td>
                            <td>$ {{ number_format($totales['riesgo_trabajo'], 2) }}</td>
                            <td>$ {{ number_format($totales['cuota_fija'], 2) }}</td>
                            <td>$ {{ number_format($totales['enf_mat_excedente'], 2) }}</td>
                            <td>$ {{ number_format($totales['enf_mat_dinero'], 2) }}</td>
                            <td>$ {{ number_format($totales['enf_mat_gastos_pensionados'], 2) }}</td>
                            <td>$ {{ number_format($totales['invalidez_vida'], 2) }}</td>
                            <td>$ {{ number_format($totales['guarderia'], 2) }}</td>
                            <td>$ {{ number_format($totales['retiro'], 2) }}</td>
                            <td>$ {{ number_format($totales['cesantia_vejez'], 2) }}</td>
                            <td>$ {{ number_format($totales['infonavit'], 2) }}</td>
                            <td>$ {{ number_format($totales['total'], 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
@endsection
