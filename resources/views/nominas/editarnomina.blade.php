@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.0/css/fixedColumns.dataTables.min.css">
@endsection

@section('content')
@if($mensaje = Session::get('successExcel'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "success",title: "¡Acción exitosa!", text: "Archivo importado correctamente, recuerde recalcular en base a lo importado"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('warningExcel'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡No se efectuó la acción!", text: "Revise que el archivo importado cumpla con el formato requerido"});';
            echo '</script>'; 
    @endphp
  @elseif($mensaje = Session::get('success'))
  @php
         echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "success",title: "¡Acción exitosa!", text: "Movimiento Realizado con Exito"});';
            echo '</script>'; 
  @endphp
@elseif($mensaje = Session::get('warningCuenta'))
@php
        echo '<script language="JavaScript">';
        echo 'const Toast = Swal.mixin({';
        echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 5000,timerProgressBar: true,';
        echo 'didOpen: (toast) => {';
        echo '  toast.onmouseenter = Swal.stopTimer;';
        echo '  toast.onmouseleave = Swal.resumeTimer;}});';
        echo 'Toast.fire({ icon: "error",title: "¡No se efectuó la acción!", text: "Revise que las cuentas esten bien seleccionadas y que el saldo sea suficiente para el desembolso"});';
        echo '</script>'; 
@endphp
@endif

@php($total_fiscal = 0)
@php($total_excedente = 0)
@php($total_pagar = 0)
@php($tot_dias_laborados = 0)
@php($tot_total_sueldo = 0)
@php($tot_dias_incapacidad = 0)
@php($tot_faltas_reta_aus = 0)
@php($tot_total_faltas_reta_aus = 0)
@php($tot_horas_extras = 0)
@php($tot_total_horas_extras = 0)
@php($tot_dias_descanso = 0)
@php($tot_pago_dias_descanso = 0)
@php($tot_dias_prima_dominical = 0)
@php($tot_pago_prima_dominical = 0)
@php($tot_pago_infonavit = 0)
@php($tot_pago_imss = 0)
@php($tot_pago_subsidio = 0)
@php($tot_pago_isr = 0)
@php($tot_fonacot = 0)
@php($tot_deudores_fiscal = 0)
@php($tot_despensa = 0)
@php($tot_otros = 0)
@php($tot_bono = 0)
@php($tot_viaticos = 0)
@php($tot_dias_vacaciones = 0)
@php($tot_pago_dias_vacaciones = 0)
@php($tot_dias_prima_vacacional = 0)
@php($tot_pago_prima_vacacional = 0)
@php($tot_percepcion_extraordinaria = 0)
@php($tot_salario_fijo = 0)
@php($tot_salario_diario_integrado = 0)
@php($tot_excedente = 0)
@php($saldos = 0)

@foreach($varnominas as $noms)
  @php($idpagonomina = $noms->idpagonomina)
  @php($tiponom = $noms->idtiponomina)
  @php($pagoenc_id = $noms->pagoenc_id)
  @php($fecha_fin = $noms->fecha_fin)
  @php($nombre_nomina = $noms->nombre_nomina)
  @php($estado_nomina = $noms->estado_nomina)
@break
@endforeach

@foreach($varnominas as $pago)
  @php($total_fiscal = $total_fiscal + $pago->total_nomina_fiscal)
  @php($total_excedente = $total_excedente + $pago->total_apagar_excedente)
  @php($total_pagar = $total_pagar + ($visorFiscalActivo ? $pago->total_nomina_fiscal : $pago->total_apagar))
  @php($tot_dias_laborados += $pago->dias_laborados)
  @php($tot_total_sueldo += $pago->total_sueldo)
  @php($tot_dias_incapacidad += $pago->dias_incapacidad)
  @php($tot_faltas_reta_aus += $pago->faltas_reta_aus)
  @php($tot_total_faltas_reta_aus += $pago->total_faltas_reta_aus)
  @php($tot_horas_extras += $pago->horas_extras)
  @php($tot_total_horas_extras += $pago->total_horas_extras)
  @php($tot_dias_descanso += $pago->dias_descanso)
  @php($tot_pago_dias_descanso += $pago->pago_dias_descanso)
  @php($tot_dias_prima_dominical += $pago->dias_prima_dominical)
  @php($tot_pago_prima_dominical += $pago->pago_prima_dominical)
  @php($tot_pago_infonavit += $pago->pago_infonavit)
  @php($tot_pago_imss += $pago->pago_imss)
  @php($tot_pago_subsidio += $pago->pago_subsidio)
  @php($tot_pago_isr += $pago->pago_isr)
  @php($tot_fonacot += $pago->fonacot)
  @php($tot_deudores_fiscal += $pago->deudores_fiscal)
  @php($tot_despensa += $pago->despensa)
  @php($tot_otros += $pago->otros)
  @php($tot_bono += $pago->bono)
  @php($tot_viaticos += $pago->viaticos ?? 0)
  @php($tot_dias_vacaciones += $pago->dias_vaciones)
  @php($tot_pago_dias_vacaciones += $pago->pago_dias_vacaciones)
  @php($tot_dias_prima_vacacional += $pago->dias_prima_vacacional)
  @php($tot_pago_prima_vacacional += $pago->pago_prima_vacacional)
  @php($tot_percepcion_extraordinaria += $pago->percepcion_extraordinaria)
  @php($tot_salario_fijo += $pago->salario_fijo)
  @php($tot_salario_diario_integrado += $pago->salario_diario_integrado)
  @php($tot_excedente += $pago->excedente)
@endforeach

@php($saldos = $total_excedente + $total_fiscal)
@php($saldosVisor = $visorFiscalActivo ? $total_fiscal : $saldos)

@if($errors->has('calculos'))
    <div class="alert alert-danger">
        {{ $errors->first('calculos') }}
        
        @if(session('errores_detallados'))
            <ul>
            @foreach(session('errores_detallados') as $error)
                <li>Empleado: {{ $error['empleado'] }} - 
                    Saldo negativo: ${{ $error['saldo_negativo'] }} | 
                    Sueldo total: ${{ $error['sueldo_total'] }} | 
                    Deducciones: ${{ $error['total_deducciones'] }}</li>
            @endforeach
            </ul>
        @endif
    </div>
@endif

@if($errors->has('comparacion_ids'))
    <div class="alert alert-{{ session('comparacion_detalle.faltantes') > 0 ? 'warning' : 'success' }}">
        <h4>Error</h4>
        <p>{{ $errors->first('comparacion_ids') }}</p>
        
        {{-- @if(session('comparacion_detalle.faltantes') > 0)
            <div class="mt-3">
                <h5>Empleados Sin Timbrar ({{ session('comparacion_detalle.faltantes') }}):</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empleado</th>
                            <th>Puesto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(session('comparacion_detalle.detalles_faltantes') as $faltante)
                            <tr>
                                <td>{{ $faltante['id'] }}</td>
                                <td>{{ $faltante['empleado'] }}</td>
                                <td>{{ $faltante['puesto'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-success mt-3">
                ¡Todos los registros coinciden correctamente!
            </div>
        @endif --}}
    </div>
@endif


<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<link href="{{ asset('css/inputfile.css') }}" rel="stylesheet">

<style>
  .payroll-edit-page {
    color: #1f2937;
  }

  .payroll-hero,
  .payroll-action-panel {
    background: linear-gradient(135deg, #ffffff 0%, #faf7ff 48%, #f8fafc 100%);
    border: 1px solid rgba(100, 116, 139, 0.16);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(51, 65, 85, 0.10);
  }

  .payroll-hero {
    padding: 1.25rem;
  }

  .payroll-hero .header-icon {
    background-image: linear-gradient(135deg, #1f2937, #475569) !important;
    box-shadow: 0 10px 22px rgba(51, 65, 85, 0.20) !important;
  }

  .payroll-title {
    color: #1f2937 !important;
    font-size: clamp(1.1rem, 2vw, 1.65rem);
    letter-spacing: -0.02em;
  }

  .payroll-subtitle {
    color: #64748b;
  }

  .payroll-status-badge {
    border-radius: 999px;
    padding: 0.3rem 0.75rem;
    font-size: 0.75rem !important;
    font-weight: 700 !important;
  }

  .payroll-summary-card {
    height: 100%;
    padding: 1rem;
    background: #fff;
    border: 1px solid rgba(100, 116, 139, 0.14);
    border-radius: 18px;
    box-shadow: 0 8px 22px rgba(51, 65, 85, 0.08);
  }

  .payroll-summary-label {
    margin-bottom: 0.35rem;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .payroll-summary-value {
    margin: 0;
    color: #1f2937;
    font-size: 1.15rem;
    font-weight: 800;
  }

  .payroll-tools-panel {
    padding: 0.75rem 1rem;
    width: 100%;
    background: #fff;
    border: 1px solid rgba(100, 116, 139, 0.14);
    border-radius: 16px;
    box-shadow: 0 4px 14px rgba(51, 65, 85, 0.06);
  }

  .payroll-tools-bar {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem 0;
  }

  .payroll-tools-section {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.15rem 0.5rem;
  }

  .payroll-tools-divider {
    width: 1px;
    height: 28px;
    background: #e2e8f0;
    margin: 0 0.35rem;
    flex-shrink: 0;
  }

  .payroll-tools-label {
    color: #94a3b8;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    white-space: nowrap;
    padding-right: 0.15rem;
  }

  .payroll-tools-buttons {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.35rem;
  }

  .payroll-tool-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    height: 32px;
    padding: 0 0.7rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
    color: #334155;
    font-size: 0.75rem;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    transition: all 0.15s ease;
    text-decoration: none;
  }

  .payroll-tool-chip i {
    font-size: 0.78rem;
    opacity: 0.85;
  }

  .payroll-tool-chip:hover:not(:disabled) {
    background: #1f2937;
    border-color: #1f2937;
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(31, 41, 55, 0.18);
  }

  .payroll-tool-chip:disabled {
    opacity: 0.45;
    cursor: not-allowed;
  }

  .payroll-tool-chip.is-success:hover:not(:disabled) {
    background: #047857;
    border-color: #047857;
    color: #fff;
  }

  .payroll-tool-chip.is-danger:hover:not(:disabled) {
    background: #dc2626;
    border-color: #dc2626;
    color: #fff;
  }

  .payroll-tool-chip.is-primary:hover:not(:disabled) {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
  }

  .payroll-tools-status {
    margin-left: auto;
    padding-left: 0.5rem;
  }

  @media (max-width: 768px) {
    .payroll-tools-bar {
      flex-direction: column;
      align-items: stretch;
    }

    .payroll-tools-divider {
      width: 100%;
      height: 1px;
      margin: 0.25rem 0;
    }

    .payroll-tools-status {
      margin-left: 0;
      padding-top: 0.35rem;
      border-top: 1px solid #e2e8f0;
    }

    .payroll-tools-section {
      flex-wrap: wrap;
    }
  }

  .payroll-import-modal .modal-body {
    padding: 1.5rem;
  }

  .payroll-import-note {
    padding: 0.9rem 1rem;
    margin-bottom: 1rem;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-size: 0.82rem;
  }

  .payroll-warning {
    border: 1px solid #fcd9d9 !important;
    border-left: 3px solid #e57373 !important;
    border-radius: 10px !important;
    background: #fff8f8 !important;
    color: #6b4f4f !important;
    box-shadow: none;
    padding: 0.65rem 0.9rem;
    margin-bottom: 1rem !important;
    font-size: 0.84rem;
  }

  .payroll-warning h6,
  .payroll-warning p {
    color: #6b4f4f !important;
    font-size: 0.84rem;
    font-weight: 500;
  }

  .payroll-warning h6 {
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #9a6b6b !important;
  }

  .payroll-warning .payroll-warning-icon {
    color: #d97777;
    font-size: 0.95rem;
    margin-top: 0.1rem;
  }

  .payroll-warning .btn-close {
    opacity: 0.45;
    padding: 0.65rem;
    filter: none;
  }

  .payroll-fiscal-filter-btn {
    border-color: #e8b4b4 !important;
    color: #9a4f4f !important;
    background: #fff !important;
    font-size: 0.74rem;
    font-weight: 600;
    border-radius: 999px;
    padding: 0.25rem 0.7rem;
  }

  .payroll-fiscal-filter-btn:hover,
  .payroll-fiscal-filter-btn.active {
    background: #fef2f2 !important;
    border-color: #d97777 !important;
    color: #9a4f4f !important;
  }

  .nomina-edit-table-card {
    border: 1px solid rgba(17, 24, 39, 0.05) !important;
    box-shadow: 0 6px 18px rgba(17, 24, 39, 0.04) !important;
  }

  .nomina-edit-table-card .td-actions {
    white-space: nowrap !important;
    min-width: 0 !important;
    width: 1% !important;
    padding-left: 0.35rem !important;
    padding-right: 0.35rem !important;
  }

  .nomina-edit-table-card .td-actions .btn {
    display: inline-flex !important;
    margin: 0 !important;
  }

  .nomina-edit-table-card table.dataTable > tbody > tr.selected > *,
  .nomina-edit-table-card table.dataTable > tbody > tr.selected:hover > * {
    background-color: #e5e7eb !important;
    color: #111827 !important;
  }

  .nomina-edit-table-card tfoot tr.nomina-table-totals th {
    background: #f9fafb !important;
    border-top: 2px solid rgba(17, 24, 39, 0.12) !important;
    font-weight: 800 !important;
  }

  .nomina-edit-table-card tfoot tr.nomina-table-totals th.nomina-total-amount {
    color: #047857;
  }

  .nomina-edit-table-section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark-color);
  }

  .payroll-edit-page .modal-content {
    border: 1px solid rgba(100, 116, 139, 0.16);
    border-radius: 22px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.22);
    overflow: hidden;
  }

  .payroll-edit-page .modal-header {
    background: linear-gradient(135deg, #ffffff 0%, #faf7ff 100%);
    border-bottom: 1px solid #e2e8f0 !important;
  }

  .payroll-edit-page .modal-title {
    color: #1f2937 !important;
    font-weight: 800;
  }

  .payroll-edit-page .modern-form .modal-body .row {
    margin-bottom: 0.75rem;
    border: 1px solid #e2e8f0 !important;
    border-radius: 18px;
    background: #fff;
  }

  .payroll-edit-page .form-label {
    color: #334155 !important;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    text-transform: uppercase;
  }

  .payroll-edit-page .form-control,
  .payroll-edit-page .form-select {
    border-color: #dbe4f0;
    border-radius: 12px;
    color: #1f2937;
    font-weight: 600;
  }

  .payroll-edit-page .form-control:focus,
  .payroll-edit-page .form-select:focus {
    border-color: #64748b;
    box-shadow: 0 0 0 0.2rem rgba(100, 116, 139, 0.16);
  }

  .payroll-edit-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 1rem;
    padding: 0.35rem;
    background: #f1f5f9;
    border-radius: 14px;
  }

  .payroll-edit-tabs .nav-link {
    border: 1px solid transparent;
    border-radius: 10px;
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.45rem 0.8rem;
  }

  .payroll-edit-tabs .nav-link:hover {
    color: #1f2937;
    background: #fff;
  }

  .payroll-edit-tabs .nav-link.active {
    background: #1f2937;
    border-color: #1f2937;
    color: #fff;
  }

  .payroll-edit-tabs .nav-link.is-excepciones.active {
    background: #0f766e;
    border-color: #0f766e;
  }

  .payroll-excepciones-panel {
    border: 1px solid #99f6e4 !important;
    border-radius: 18px;
    background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 55%);
    padding: 1rem 1rem 0.35rem;
  }

  .payroll-excepciones-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
    color: #0f766e;
    font-size: 0.9rem;
    font-weight: 800;
  }

  .payroll-excepciones-note {
    margin-bottom: 0.9rem;
    color: #64748b;
    font-size: 0.78rem;
  }

  .payroll-excepciones-panel .form-control {
    border-color: #99f6e4;
    background: #fff;
  }

  .payroll-excepciones-panel .form-control:focus {
    border-color: #0f766e;
    box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.14);
  }

  .payroll-edit-section-title {
    margin: 0 0 0.75rem;
    color: #475569;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
  }

  @media (max-width: 768px) {
    .payroll-hero {
      align-items: flex-start !important;
    }

  }
</style>

<div class="container-fluid format_page payroll-edit-page">
  @if($estado_nomina == "Edicion" && $validaNominasenCero > 0)
    <div class="alert alert-light alert-dismissible fade show payroll-warning" role="alert">
      <div class="d-flex align-items-start gap-2">
        <i class="fa-solid fa-circle-info payroll-warning-icon mt-1"></i>
        <div>
          <h6 class="mb-1">Aviso</h6>
          <p class="mb-0">
            El sueldo fiscal debe ser mayor a 0 para cerrar la nómina. Si necesita más información, contacte a un superior.
          </p>
        </div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <!-- Header Section -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center header payroll-hero flex-wrap gap-3">
        <div class="d-flex align-items-center">
          <div class="header-icon me-3">
            <i class="fas fa-pen-to-square"></i>
          </div>
          <div>
            <div class="mb-1">
              <a href="/Nominas" class="text-muted text-decoration-none fs-8">
                <i class="fa-solid fa-chevron-left me-1"></i>Nómina
              </a>
            </div>
            <h2 class="mb-0 text-marino fw-bold payroll-title">
              Edicion de Nomina
              <span class="fw-normal">
                @if($tiponom == 1)
                  Semanal
                @elseif($tiponom == 2)
                  Quincenal
                @elseif($tiponom== 3)
                  Mensual
                @endif
              </span>
            </h2>
            <p class="payroll-subtitle mb-0">
              <b>{{$nombre_nomina}}</b> &middot; {{date("d/m/Y", strtotime($fecha_fin))}}
              @if ($estado_nomina == 'Edicion')
                <span class="badge badge-primary payroll-status-badge">Edicion</span>
              @elseif($estado_nomina == 'Cerrada')
                <span class="badge badge-danger payroll-status-badge">{{ $estado_nomina }}</span>
              @endif
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
      <div class="payroll-summary-card">
        <div class="payroll-summary-label">Total fiscal</div>
        <p class="payroll-summary-value">$ {{ number_format($total_fiscal, 2) }}</p>
      </div>
    </div>
    @unless($visorFiscalActivo)
    <div class="col-lg-3 col-md-6">
      <div class="payroll-summary-card">
        <div class="payroll-summary-label">Total excedente</div>
        <p class="payroll-summary-value">$ {{ number_format($total_excedente, 2) }}</p>
      </div>
    </div>
    @endunless
    <div class="col-lg-3 col-md-6">
      <div class="payroll-summary-card">
        <div class="payroll-summary-label">{{ $visorFiscalActivo ? 'Total fiscal a dispersar' : 'Total a dispersar' }}</div>
        <p class="payroll-summary-value">$ {{ number_format($saldosVisor, 2) }}</p>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="payroll-summary-card">
        <div class="payroll-summary-label">Empleados</div>
        <p class="payroll-summary-value">{{ count($varnominas) }}</p>
      </div>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-12 p-0">
      <div class="payroll-tools-panel">
        <div class="payroll-tools-bar">
          <div class="payroll-tools-section">
            <span class="payroll-tools-label">Importar</span>
            <div class="payroll-tools-buttons">
              @if($permisos1 == "importar_bonos_nominas" && $estado_nomina == "Edicion")
                <button type="button" class="payroll-tool-chip" data-bs-toggle="modal" data-bs-target="#modalImportExtras" title="Bonos, horas extra, percepción exenta, préstamos">
                  <i class="fas fa-arrow-up-from-bracket"></i> Excepciones
                </button>
                <a href="/Nominas/asistencias/{{ $idpagonomina }}" class="payroll-tool-chip" title="Ver e importar asistencias">
                  <i class="fa-solid fa-clock"></i> Asistencias
                </a>
              @else
                <button type="button" class="payroll-tool-chip" disabled><i class="fas fa-arrow-up-from-bracket"></i> Excepciones</button>
                <a href="/Nominas/asistencias/{{ $idpagonomina }}" class="payroll-tool-chip" title="Ver asistencias">
                  <i class="fa-solid fa-clock"></i> Asistencias
                </a>
              @endif
            </div>
          </div>

          <div class="payroll-tools-divider"></div>

          <div class="payroll-tools-section">
            <span class="payroll-tools-label">Cálculo</span>
            <div class="payroll-tools-buttons">
              @if($permisos2 == "calcular_nominas" && $estado_nomina == "Edicion")
                <button type="button" class="payroll-tool-chip is-success" onclick="confirmarRecalcularNomina()">
                  <i class="fas fa-calculator"></i> Recalcular
                </button>
              @else
                <button type="button" class="payroll-tool-chip is-success" disabled><i class="fas fa-calculator"></i> Recalcular</button>
              @endif
            </div>
          </div>

          <div class="payroll-tools-divider"></div>

          <div class="payroll-tools-section">
            <span class="payroll-tools-label">Exportar</span>
            <div class="payroll-tools-buttons">
              @if($permisos3 == "exportar_nominas")
                <button type="button" class="payroll-tool-chip is-primary" onclick="abrirListadoNominaPdf({{ (int) $pagoenc_id }})" title="Listado de la nómina">
                  <i class="fa-solid fa-list"></i> Listado
                </button>
                <div class="dropdown">
                  <button class="payroll-tool-chip is-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-download"></i> Exportar
                  </button>
                  <ul class="dropdown-menu shadow-sm">
                    <li><a class="dropdown-item fs-8" href="#" onclick="abrirListadoNominaPdf({{ (int) $pagoenc_id }}); return false;">Listado de Nómina (PDF)</a></li>
                    <li><a class="dropdown-item fs-8" href="/Nominas/exportar_excel/{{$pagoenc_id}}">Excel Nómina</a></li>
                    <li><a class="dropdown-item fs-8" href="/Nominas/exportar_retenciones/{{$pagoenc_id}}">Excel Retenciones</a></li>
                    @if($estado_nomina == "Edicion")
                      <!-- <li><a class="dropdown-item fs-8" href="/ExpotArchivoDispersion/{{ $pagoenc_id }}">Excel Dispersión</a></li>-->
                      <!-- <li><a class="dropdown-item fs-8" href="/Exportarlayoutnomina/{{ $pagoenc_id}}">Layout Dispersión</a></li>-->
                      <li><a class="dropdown-item fs-8" href="/ExportarlayoutBanorte/{{ $pagoenc_id}}">Layout BANORTE</a></li>
                      <li><a class="dropdown-item fs-8" target="_blank" href="/Nominas/exportarComprobantes/{{ $pagoenc_id}}">Recibos de Pago</a></li>
                      <li><a class="dropdown-item fs-8" target="_blank" href="/Nominas/exportarCheques/{{ $pagoenc_id}}">Cheques de Pago</a></li>
                    @else
                      <!--<li><span class="dropdown-item text-muted fs-8">Dispersión (al cerrar)</span></li>
                      <li><span class="dropdown-item text-muted fs-8">Layout (al cerrar)</span></li>-->
                      <li><span class="dropdown-item text-muted fs-8">Layout BANORTE (al cerrar)</span></li>
                      <li><span class="dropdown-item text-muted fs-8">Recibos (al cerrar)</span></li>
                      <li><span class="dropdown-item text-muted fs-8">Cheques (al cerrar)</span></li>
                    @endif
                  </ul>
                </div>
              @else
                <button type="button" class="payroll-tool-chip is-primary" disabled><i class="fa-solid fa-download"></i> Exportar</button>
              @endif
            </div>
          </div>

          <div class="payroll-tools-divider"></div>

          <div class="payroll-tools-section">
            <span class="payroll-tools-label">Cierre</span>
            <div class="payroll-tools-buttons">
              @if($estado_nomina == "Edicion")
                @if($validaNominasenCero == 0)
                  @if($saldosVisor > 0)
                    <a href="{{ route('Nominas.previewCierre', ['id' => $idpagonomina, 'idtiponomina' => $tiponom, 'fecha_ini' => $fecha_inicio, 'fecha_fin' => $fecha_fin]) }}" class="payroll-tool-chip is-danger">
                      <i class="fas fa-times-circle"></i> Cerrar
                    </a>
                  @else
                    <button type="button" class="payroll-tool-chip is-danger" onclick="Swal.fire({ icon: 'warning', title: 'No se puede cerrar', text: 'El total a dispersar debe ser mayor a 0.' })">
                      <i class="fas fa-times-circle"></i> Cerrar
                    </button>
                  @endif
                @else
                  <button type="button" class="payroll-tool-chip is-danger" disabled><i class="fas fa-times-circle"></i> Cerrar</button>
                @endif
                <button type="button" class="payroll-tool-chip is-success" disabled><i class="fa-regular fa-bell"></i> Timbrar</button>
              @else
                @if($validaTimbrado > 0)
                  <a class="payroll-tool-chip is-success" href="/vernominatimbrada/{{$pagoenc_id}}">
                    <i class="fa-regular fa-bell"></i> Ver timbrada
                  </a>
                @else
                  <a class="payroll-tool-chip is-success" href="#" onclick="mostrarCarga(); return false;" id="btnTimbrar">
                    <i class="fa-regular fa-bell"></i> Timbrar
                  </a>
                @endif
                <button type="button" class="payroll-tool-chip is-danger" disabled><i class="fas fa-lock"></i> Cerrada</button>
              @endif
            </div>
          </div>

          <div class="payroll-tools-status">
            <span class="badge {{ $estado_nomina == 'Edicion' ? 'bg-primary' : 'bg-danger' }} payroll-status-badge">
              {{ $estado_nomina }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  
 
  @if($estado_nomina != 'Cerrada' && $validaNominasenCero > 0)
    <div class="row">
  @else
    <div class="row mt-3">
  @endif
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
              <h6 class="nomina-edit-table-section-title mb-0">
                <i class="fa-solid fa-table-list me-2 text-muted"></i>
                Detalle de empleados en nómina
              </h6>
              @if($estado_nomina == "Edicion" && $validaNominasenCero > 0)
                <button
                  type="button"
                  class="btn btn-sm payroll-fiscal-filter-btn payroll-fiscal-filter-btn-table"
                  data-fiscal-filter-toggle
                  data-label-filtrar="Ver fiscal en $0 ({{ $validaNominasenCero }})"
                  data-label-todos="Mostrar todos"
                >
                  <i class="fa-solid fa-filter"></i>
                  Ver fiscal en $0 ({{ $validaNominasenCero }})
                </button>
              @endif
            </div>
            <div class="table-wrapper nomina-edit-table-card">
                <div class="table-responsive">
                    <table class="table table-striped table-hover display modern-table w-100" id="tableNomina">
              <thead>
                <tr>
                    <th class="text-truncate text-center">Editar</th>
                    <th class="text-truncate">No. Empleado</th>
                    <th class="text-truncate">Nombre Empleado</th>
                    <th class="text-truncate">Puesto</th>
                    <th class="text-truncate">Sucursal</th>
                    <th class="text-truncate">Banco</th>
                    <th class="text-truncate">ID Banca</th>
                    <th class="text-truncate">Numero de Cuenta</th>

                    <th class="text-truncate">Salario Diario</th>
                    <th class="text-truncate">Salario Diario Integrado</th>
                    @unless($visorFiscalActivo)
                    <th class="text-truncate">Salario Diario Excedente</th>
                    @endunless

                    <th class="text-truncate">Dias Laborados</th>
                    <th class="text-truncate">Salario por Días Laborados</th>

                    <th class="text-truncate">Días de Incapacidad</th>
                    <th class="text-truncate">Faltas/ret/aus</th>
                    <th class="text-truncate">Descuento por Ausencias</th>

                    <th class="text-truncate">Horas Extras</th>
                    <th class="text-truncate">Pago por Extras</th>

                    <th class="text-truncate">Días de Descanso</th>
                    <th class="text-truncate">Pago Días de Descanso</th>
                    <th class="text-truncate">Dias de Prima Dominical</th>
                    <th class="text-truncate">Pago Prima Dominical</th>
                    
                    <th class="text-truncate">INFONAVIT</th>
                    <th class="text-truncate">IMSS</th>
                    <th class="text-truncate">Subsidio</th>
                    <th class="text-truncate">ISR</th>
                    <th class="text-truncate">FONACOT</th>

                    <th class="text-truncate">Prestamo de Empresa</th>
                    <th class="text-truncate">Despensa</th>
                    <th class="text-truncate">Otros</th>
                    <th class="text-truncate">Bono</th>
                    <th class="text-truncate">Viáticos</th>

                    <th class="text-truncate">Días de Vacaciones</th>
                    <th class="text-truncate">Pago de Días de Vacaciones</th>
                    <th class="text-truncate">Días de Prima Vacacional</th>
                    <th class="text-truncate">Pago de Prima Vacacional</th>
                    
                    <th class="text-truncate">Percepcion Exenta</th>
                    <th class="text-truncate">Total Nomina Fiscal</th>
                    @unless($visorFiscalActivo)
                    <th class="text-truncate">Total Nomina Excedente</th>
                    @endunless
                    <th class="text-truncate">{{ $visorFiscalActivo ? 'Total fiscal a pagar' : 'Total a pagar' }}</th>
                    
                  </tr>
              </thead>
            
              <tbody>
                @foreach($varnominas as $nomina)
                  <tr @if($nomina->total_nomina_fiscal <= 0) data-fiscal-cero="1" @endif>
                    <td class="td-actions text-center">
                      @if($estado_nomina != 'Cerrada')
                        <button class="btn btn-primary m-0" data-bs-toggle="modal" data-bs-target="#editarEmpleado{{$nomina->id}}" title="Editar excepciones y conceptos"><i class="fa-solid fa-pen fs-8"></i></button>
                      @else
                        <button class="btn btn-primary m-0" disabled title="Editar"><i class="fa-solid fa-pen fs-8"></i></button>
                      @endif
                    </td>
                    <td class="text-truncate">{{$nomina->idempleado}}</td>
                    <td class="text-truncate">{{$nomina->primer_nombre.' '.$nomina->segundo_nombre.' '.$nomina->apellido_paterno.' '.$nomina->apellido_materno}}</td>
                    <td class="text-truncate">{{$nomina->puesto}}</td>
                    <td class="text-truncate">{{$nomina->sucursal}}</td>
                    <td class="text-truncate">{{$nomina->banco}}</td>
                    <td class="text-truncate">{{$nomina->idbanca}}</td>
                    <td class="text-truncate">{{$nomina->numero_cuenta}}</td> 
                    
                    <td class="text-truncate">$ {{ number_format($nomina->salario_fijo, 2)}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->salario_diario_integrado, 2)}}</td>
                    @unless($visorFiscalActivo)
                    <td class="text-truncate">$ {{ number_format($nomina->excedente, 2)}}</td>
                    @endunless

                    <td class="text-truncate">{{$nomina->dias_laborados}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->total_sueldo, 2)}}</td>
                    
                    <td class="text-truncate">{{$nomina->dias_incapacidad}}</td>
                    <td class="text-truncate">{{$nomina->faltas_reta_aus}}</td>
                    <td class="text-truncate">$ {{number_format($nomina->total_faltas_reta_aus, 2)}}</td>

                    <td class="text-truncate">{{$nomina->horas_extras}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->total_horas_extras, 2)}}</td>

                    <td class="text-truncate">  {{ $nomina->dias_descanso}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->pago_dias_descanso, 2)}}</td>
                    <td class="text-truncate">  {{ $nomina->dias_prima_dominical}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->pago_prima_dominical, 2)}}</td>

                    <td class="text-truncate">$ {{ number_format($nomina->pago_infonavit, 2)}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->pago_imss, 2)}}</td>  
                    <td class="text-truncate">$ {{ number_format($nomina->pago_subsidio, 2)}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->pago_isr, 2)}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->fonacot, 2)}}</td>

                    <td class="text-truncate">$ {{ number_format($nomina->deudores_fiscal, 2)}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->despensa, 2)}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->otros, 2)}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->bono, 2)}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->viaticos ?? 0, 2)}}</td>

                    <td class="text-truncate">{{$nomina->dias_vaciones}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->pago_dias_vacaciones, 2)}}</td>
                    <td class="text-truncate">{{$nomina->dias_prima_vacacional}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->pago_prima_vacacional, 2)}}</td>

                    <td class="text-truncate">$ {{ number_format($nomina->percepcion_extraordinaria, 2)}}</td>
                    <td class="text-truncate">$ {{ number_format($nomina->total_nomina_fiscal, 2)}}</td>
                    @unless($visorFiscalActivo)
                    <td class="text-truncate">$ {{ number_format($nomina->total_apagar_excedente, 2)}}</td>
                    @endunless
                    <td class="text-truncate text-success fw-bold">$ {{ number_format($visorFiscalActivo ? $nomina->total_nomina_fiscal : $nomina->total_apagar, 2)}}</td>
                    
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr class="nomina-table-totals">
                  <th class="text-truncate"></th>
                  <th class="text-truncate"></th>
                  <th class="nomina-total-label fw-bold text-truncate">Totales</th>
                  <th class="text-truncate"></th>
                  <th class="text-truncate"></th>
                  <th class="text-truncate"></th>
                  <th class="text-truncate"></th>
                  <th class="text-truncate"></th>
                  <th class="text-truncate">$ {{ number_format($tot_salario_fijo, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_salario_diario_integrado, 2) }}</th>
                  @unless($visorFiscalActivo)
                  <th class="text-truncate">$ {{ number_format($tot_excedente, 2) }}</th>
                  @endunless
                  <th class="text-truncate">{{ number_format($tot_dias_laborados, 0) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_total_sueldo, 2) }}</th>
                  <th class="text-truncate">{{ number_format($tot_dias_incapacidad, 0) }}</th>
                  <th class="text-truncate">{{ number_format($tot_faltas_reta_aus, 0) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_total_faltas_reta_aus, 2) }}</th>
                  <th class="text-truncate">{{ number_format($tot_horas_extras, 0) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_total_horas_extras, 2) }}</th>
                  <th class="text-truncate">{{ number_format($tot_dias_descanso, 0) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_pago_dias_descanso, 2) }}</th>
                  <th class="text-truncate">{{ number_format($tot_dias_prima_dominical, 0) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_pago_prima_dominical, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_pago_infonavit, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_pago_imss, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_pago_subsidio, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_pago_isr, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_fonacot, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_deudores_fiscal, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_despensa, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_otros, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_bono, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_viaticos, 2) }}</th>
                  <th class="text-truncate">{{ number_format($tot_dias_vacaciones, 0) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_pago_dias_vacaciones, 2) }}</th>
                  <th class="text-truncate">{{ number_format($tot_dias_prima_vacacional, 0) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_pago_prima_vacacional, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($tot_percepcion_extraordinaria, 2) }}</th>
                  <th class="text-truncate">$ {{ number_format($total_fiscal, 2) }}</th>
                  @unless($visorFiscalActivo)
                  <th class="text-truncate">$ {{ number_format($total_excedente, 2) }}</th>
                  @endunless
                  <th class="nomina-total-amount text-truncate">$ {{ number_format($total_pagar, 2) }}</th>
                </tr>
              </tfoot>
            </table>
                </div>
            </div>
        </div>
  </div>
</div>

<!-- ................................................................................................................................................-->

<!-- Editar Modal-->
@foreach($varnominas as $nomina)
  <div class="modal fade" id="editarEmpleado{{$nomina->id}}" tabindex="-1" aria-labelledby="editarEmpleadoLabel{{$nomina->id}}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">

        <div class="modal-header border-0">
            <div>
              <h1 class="modal-title fs-5 text-dark mb-1" id="editarEmpleadoLabel{{$nomina->id}}">
                Editar empleado
              </h1>
              <p class="mb-0 text-muted fs-8">
                #{{ $nomina->idempleado }} &middot;
                {{ $nomina->primer_nombre }} {{ $nomina->segundo_nombre }} {{ $nomina->apellido_paterno }} {{ $nomina->apellido_materno }}
              </p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form action="/Nominas/EditarEmpleado/{{$nomina->id}}"  method="POST" enctype="multipart/form-data" class="g-3 needs-validation  modern-form" novalidate>
          @csrf
          <div class="modal-body">
              <div class="container-fluid px-1">
                <ul class="nav payroll-edit-tabs" id="editarTabs{{ $nomina->id }}" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link is-excepciones active" id="tab-exc-{{$nomina->id}}" data-bs-toggle="tab" data-bs-target="#pane-exc-{{$nomina->id}}" type="button" role="tab" aria-controls="pane-exc-{{$nomina->id}}" aria-selected="true">
                      <i class="fas fa-sliders me-1"></i> Excepciones
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-sueldo-{{$nomina->id}}" data-bs-toggle="tab" data-bs-target="#pane-sueldo-{{$nomina->id}}" type="button" role="tab" aria-controls="pane-sueldo-{{$nomina->id}}" aria-selected="false">
                      Sueldo
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-ret-{{$nomina->id}}" data-bs-toggle="tab" data-bs-target="#pane-ret-{{$nomina->id}}" type="button" role="tab" aria-controls="pane-ret-{{$nomina->id}}" aria-selected="false">
                      Retenciones
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-tot-{{$nomina->id}}" data-bs-toggle="tab" data-bs-target="#pane-tot-{{$nomina->id}}" type="button" role="tab" aria-controls="pane-tot-{{$nomina->id}}" aria-selected="false">
                      Otros / Totales
                    </button>
                  </li>
                </ul>

                <div class="tab-content">
                  {{-- EXCEPCIONES: mismos campos del import Excel --}}
                  <div class="tab-pane fade show active" id="pane-exc-{{$nomina->id}}" role="tabpanel" aria-labelledby="tab-exc-{{$nomina->id}}">
                    <div class="payroll-excepciones-panel">
                      <div class="payroll-excepciones-title">
                        <i class="fas fa-sliders"></i>
                        Excepciones del periodo
                      </div>
                      <p class="payroll-excepciones-note mb-3">
                        Mismos conceptos que el Excel de excepciones. Tras guardar, use <b>Recalcular</b> para actualizar pagos derivados.
                      </p>

                      <div class="row">
                        <div class="mb-3 form-outline col-md-3 col-6 text-start">
                          <label class="form-label">Bono</label>
                          <input required type="number" step="any" min="0" class="form-control" name="bono" value="{{$nomina->bono}}">
                        </div>

                        <div class="mb-3 form-outline col-md-3 col-6 text-start">
                          <label class="form-label">Viáticos</label>
                          <input required type="number" step="any" min="0" class="form-control" name="viaticos" value="{{$nomina->viaticos ?? 0}}">
                        </div>

                        <div class="mb-3 form-outline col-md-3 col-6 text-start">
                          <label class="form-label">Horas extras</label>
                          <input required type="number" step="any" min="0" class="form-control" name="horas_extras" value="{{$nomina->horas_extras}}">
                        </div>

                        <div class="mb-3 form-outline col-md-3 col-6 text-start">
                          <label class="form-label">Días de descanso</label>
                          <input required type="number" step="any" min="0" class="form-control" name="dias_descanso" value="{{$nomina->dias_descanso}}">
                        </div>

                        <div class="mb-3 form-outline col-md-3 col-6 text-start">
                          <label class="form-label">Días prima dominical</label>
                          <input required type="number" step="any" min="0" class="form-control" name="dias_prima_dominical" value="{{$nomina->dias_prima_dominical}}">
                        </div>

                        <div class="mb-3 form-outline col-md-3 col-6 text-start">
                          <label class="form-label">Percepción exenta</label>
                          <input required type="number" step="any" min="0" class="form-control" name="percepcion_extraordinaria" value="{{$nomina->percepcion_extraordinaria}}">
                        </div>

                        <div class="mb-3 form-outline col-md-3 col-6 text-start">
                          <label class="form-label">Préstamo empresarial</label>
                          <input required type="number" step="any" min="0" class="form-control" name="deudores_fiscal" value="{{$nomina->deudores_fiscal}}">
                        </div>

                        <div class="mb-3 form-outline col-md-3 col-6 text-start">
                          <label class="form-label">FONACOT</label>
                          <input required type="number" step="any" min="0" class="form-control" name="fonacot" value="{{$nomina->fonacot}}">
                        </div>

                        <div class="mb-3 form-outline col-md-3 col-6 text-start">
                          <label class="form-label">Días prima vacacional</label>
                          <input required type="number" step="any" min="0" class="form-control" name="dias_prima_vacacional" value="{{$nomina->dias_prima_vacacional}}">
                        </div>
                      </div>

                      <input type="hidden" name="horas_extras_pago_f" value="{{ $nomina->horas_extras_pago_f }}">
                      <input type="hidden" name="horas_extras_pago_e" value="{{ $nomina->horas_extras_pago_e }}">
                      <input type="hidden" name="total_horas_extras" value="{{ $nomina->total_horas_extras }}">
                      <input type="hidden" name="pago_dias_descanso" value="{{ $nomina->pago_dias_descanso }}">
                      <input type="hidden" name="pago_prima_dominical" value="{{ $nomina->pago_prima_dominical }}">
                    </div>
                  </div>

                  {{-- SUELDO --}}
                  <div class="tab-pane fade" id="pane-sueldo-{{$nomina->id}}" role="tabpanel" aria-labelledby="tab-sueldo-{{$nomina->id}}">
                    <div class="row border pb-2 pt-2">
                      <div class="mb-3 form-outline col-md-3 col-6 text-start">
                        <label class="form-label">Sueldo fiscal</label>
                        <input required type="number" step="any" min="0" class="form-control" name="sueldo_fiscal" value="{{$nomina->sueldo_fiscal}}">
                      </div>

                      @unless($visorFiscalActivo)
                      <div class="mb-3 form-outline col-md-3 col-6 text-start">
                        <label class="form-label">Sueldo excedente</label>
                        <input required type="number" step="any" min="0" class="form-control" name="sueldo_excedente" value="{{$nomina->sueldo_excedente}}">
                      </div>
                      @else
                      <input type="hidden" name="sueldo_excedente" value="{{ $nomina->sueldo_excedente }}">
                      @endunless

                      <div class="mb-3 form-outline col-md-3 col-6 text-start">
                        <label class="form-label">Días laborados</label>
                        <input required type="number" step="any" min="0" class="form-control" name="dias_laborados" value="{{$nomina->dias_laborados}}">
                      </div>

                      <div class="mb-3 form-outline col-md-3 col-6 text-start">
                        <label class="form-label text-success">Sueldo por días laborados</label>
                        <input required type="number" step="any" min="0" class="form-control" name="total_sueldo" value="{{$nomina->total_sueldo}}">
                      </div>

                      <div class="mb-3 form-outline col-md-3 col-6 text-start">
                        <label class="form-label">Faltas / ret / aus</label>
                        <input type="number" step="any" class="form-control bg-light" name="faltas_reta_aus" value="{{$nomina->faltas_reta_aus}}" readonly>
                        <small class="text-muted fs-9">Incapacidad + vacaciones del periodo</small>
                      </div>

                      <div class="mb-3 form-outline col-md-9 col-6 text-start">
                        <label class="form-label text-danger">Total faltas / ret / aus</label>
                        <input type="number" step="any" class="form-control bg-light" name="total_faltas_reta_aus" value="{{$nomina->total_faltas_reta_aus}}" readonly>
                      </div>
                    </div>
                  </div>

                  {{-- RETENCIONES --}}
                  <div class="tab-pane fade" id="pane-ret-{{$nomina->id}}" role="tabpanel" aria-labelledby="tab-ret-{{$nomina->id}}">
                    <div class="row border pb-2 pt-2">
                      <div class="mb-3 form-outline col-md-6 col-6 text-start">
                        <label class="form-label">Pago INFONAVIT</label>
                        <input required type="number" step="any" min="0" class="form-control" name="pago_infonavit" value="{{$nomina->pago_infonavit}}">
                      </div>

                      <div class="mb-3 form-outline col-md-6 col-6 text-start">
                        <label class="form-label">Pago IMSS</label>
                        <input required type="number" step="any" min="0" class="form-control" name="pago_imss" value="{{$nomina->pago_imss}}">
                      </div>

                      <div class="mb-3 form-outline col-md-6 col-6 text-start">
                        <label class="form-label">Pago subsidio</label>
                        <input required type="number" step="any" min="0" class="form-control" name="pago_subsidio" value="{{$nomina->pago_subsidio}}">
                      </div>

                      <div class="mb-3 form-outline col-md-6 col-6 text-start">
                        <label class="form-label">Pago ISR</label>
                        <input required type="number" step="any" min="0" class="form-control" name="pago_isr" value="{{$nomina->pago_isr}}">
                      </div>
                    </div>
                  </div>

                  {{-- OTROS / TOTALES --}}
                  <div class="tab-pane fade" id="pane-tot-{{$nomina->id}}" role="tabpanel" aria-labelledby="tab-tot-{{$nomina->id}}">
                    <div class="row border pb-2 pt-2 mb-2">
                      <div class="mb-3 form-outline col-md-4 col-6 text-start">
                        <label class="form-label">Despensa</label>
                        <input required type="number" step="any" min="0" class="form-control" name="despensa" value="{{$nomina->despensa}}">
                      </div>

                      <div class="mb-3 form-outline col-md-4 col-6 text-start">
                        <label class="form-label">Otros</label>
                        <input required type="number" step="any" min="0" class="form-control" name="otros" value="{{$nomina->otros}}">
                      </div>
                    </div>

                    <div class="row border pb-2 pt-2">
                      <div class="mb-3 form-outline col-md-6 text-start">
                        <label class="form-label text-primary">Total fiscal</label>
                        <input required type="number" step="any" class="form-control" name="total_nomina_fiscal" value="{{$nomina->total_nomina_fiscal}}">
                      </div>

                      @unless($visorFiscalActivo)
                      <div class="mb-3 form-outline col-md-6 text-start">
                        <label class="form-label text-primary">Total excedente</label>
                        <input required type="number" step="any" class="form-control" name="total_apagar_excedente" value="{{$nomina->total_apagar_excedente}}">
                      </div>
                      @else
                      <input type="hidden" name="total_apagar_excedente" value="{{ $nomina->total_apagar_excedente }}">
                      @endunless

                      <div class="mb-3 form-outline col-md-12 text-start">
                        <label class="form-label text-success fw-bold">Total a pagar</label>
                        <input required type="number" step="any" class="form-control" name="total_apagar" value="{{$nomina->total_apagar}}">
                      </div>
                    </div>
                  </div>
                </div>
            </div>
          </div>

          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-baseColor">
              <i class="fa-solid fa-check me-1"></i> Guardar cambios
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endforeach

<!-- Modal Importar Excepciones -->
<div class="modal fade payroll-import-modal" id="modalImportExtras" tabindex="-1" aria-labelledby="modalImportExtrasLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalImportExtrasLabel">
          <i class="fas fa-arrow-up-from-bracket me-2"></i>Importar excepciones
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/Nominas/importBoTrans/{{$idpagonomina}}" method="POST" enctype="multipart/form-data" class="msform needs-validation" novalidate>
        @csrf
        <div class="modal-body">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <p class="payroll-import-note mb-0 flex-grow-1">
              Carga el archivo con bonos, viáticos, horas extras, percepción exenta, préstamos, fonacot y demás conceptos.
              Solo se admite formato <b>.xlsx</b>.
            </p>
            <a class="btn btn-success btn-sm" href="/Nominas/exportar_formato_nomina/{{$idpagonomina}}">
              <i class="fa-sharp fa-solid fa-file-arrow-down me-1"></i>Descargar formato
            </a>
          </div>

          <div class="modern-file-input" data-input-id="import-extras">
            <div class="file-input-wrapper" id="wrapper-import-extras">
              <div class="file-input-content">
                <div class="file-input-icon">
                  <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <p class="file-input-text">Arrastra tu archivo aquí</p>
                <p class="file-input-subtext">o haz clic para seleccionar</p>
              </div>
              <input type="file" name="urlxlsx" id="import-extras" class="hidden-file-input" required accept=".xlsx"/>
            </div>
            <div class="file-preview" id="preview-import-extras">
              <div class="file-preview-item">
                <div class="file-preview-info">
                  <div class="file-preview-icon">
                    <i class="fas fa-file-excel"></i>
                  </div>
                  <div class="file-preview-details">
                    <h6 id="filename-import-extras"></h6>
                    <small id="filesize-import-extras"></small>
                  </div>
                </div>
                <button type="button" class="file-preview-remove" onclick="removeFile('import-extras')">
                  <i class="fas fa-times"></i>
                </button>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" id="progress-import-extras"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-baseColor">
            <i class="fas fa-arrow-up-from-bracket me-2"></i>Subir excepciones
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function confirmarRecalcularNomina() {
  Swal.fire({
    title: '¿Recalcular nómina?',
    text: 'Confirme que desea recalcular esta nómina.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#047857',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, recalcular',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = '/Nominas/Recalcular/{{ $idpagonomina }}/{{ $fecha_inicio }}/{{ $fecha_fin }}';
    }
  });
}

function mostrarCarga() {
  Swal.fire({
    title: 'Procesando nómina...',
    text: 'Por favor espere, esto puede tardar unos minutos.',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });
  setTimeout(function() {
    window.location.href = '/timbrarnomina/{{ $pagoenc_id }}';
  }, 100);
}
</script>

<script src="{{ asset('js/files.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  ['modalImportExtras'].forEach(function(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.addEventListener('shown.bs.modal', function() {
      if (typeof bindModernFileInputs === 'function') {
        bindModernFileInputs(modal);
      }
    });
  });
});
</script>

<script src="{{ asset('js/validation.js') }}"></script>

@endsection

@section('js')
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js"></script>
<script src="{{ asset('js/tableNominaEdit.js') }}"></script>
<script>
function abrirListadoNominaPdf(idNomina) {
  Swal.fire({
    title: 'Listado de la nómina',
    html: `
      <div class="text-start">
        <label class="form-label fw-semibold">Conceptos a mostrar</label>
        <select id="swal_listado_modo" class="form-select mb-3">
          <option value="completo">Completo (fiscal + excedente + excepciones)</option>
          <option value="fiscal">Solo fiscal</option>
          <option value="excedente">Solo excedente y excepciones</option>
        </select>
        <label class="form-label fw-semibold">Orden de empleados</label>
        <select id="swal_listado_orden" class="form-select">
          <option value="apellido">Por apellido (A-Z)</option>
          <option value="id">Por número de empleado</option>
        </select>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Generar PDF',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#475569',
    reverseButtons: true,
    preConfirm: () => {
      return {
        modo: document.getElementById('swal_listado_modo').value,
        orden: document.getElementById('swal_listado_orden').value
      };
    }
  }).then((result) => {
    if (!result.isConfirmed) return;
    const url = `/Nominas/exportarListado/${idNomina}?modo=${encodeURIComponent(result.value.modo)}&orden=${encodeURIComponent(result.value.orden)}`;
    window.open(url, '_blank');
  });
}
</script>
@endsection