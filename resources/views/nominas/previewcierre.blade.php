@extends('layouts.app')

@section('content')
@php($total_fiscal = 0)
@php($total_excedente = 0)
@php($total_pagar = 0)

@foreach($varnominas as $noms)
  @php($idpagonomina = $noms->idpagonomina)
  @php($tiponom = $noms->idtiponomina)
  @php($fecha_fin = $noms->fecha_fin)
  @php($nombre_nomina = $noms->nombre_nomina)
@break
@endforeach

@foreach($varnominas as $pago)
  @php($total_fiscal = $total_fiscal + $pago->total_nomina_fiscal)
  @php($total_excedente = $total_excedente + $pago->total_apagar_excedente)
  @php($total_pagar = $total_pagar + ($visorFiscalActivo ? $pago->total_nomina_fiscal : $pago->total_apagar))
@endforeach

@php($saldos = $total_fiscal + $total_excedente)
@php($saldosVisor = $visorFiscalActivo ? $total_fiscal : $saldos)
@php($empleadosSinBanco = 0)

@foreach($varnominas as $nomina)
  @if(empty(trim($nomina->banco ?? '')) || empty(trim($nomina->numero_cuenta ?? '')))
    @php($empleadosSinBanco++)
  @endif
@endforeach

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<link href="{{ asset('css/tables.css') }}" rel="stylesheet">

<style>
  .payroll-cierre-page {
    color: #1f2937;
  }

  .payroll-cierre-hero {
    background: linear-gradient(135deg, #ffffff 0%, #faf7ff 48%, #f8fafc 100%);
    border: 1px solid rgba(100, 116, 139, 0.16);
    border-radius: 22px;
    box-shadow: 0 14px 34px rgba(51, 65, 85, 0.10);
    padding: 1.25rem;
  }

  .payroll-cierre-hero .header-icon {
    background-image: linear-gradient(135deg, #1f2937, #475569) !important;
    box-shadow: 0 10px 22px rgba(51, 65, 85, 0.20) !important;
  }

  .payroll-cierre-summary-card {
    height: 100%;
    padding: 1rem;
    background: #fff;
    border: 1px solid rgba(100, 116, 139, 0.14);
    border-radius: 18px;
    box-shadow: 0 8px 22px rgba(51, 65, 85, 0.08);
  }

  .payroll-cierre-summary-card.is-total {
    background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
    border-color: #a7f3d0;
  }

  .payroll-cierre-summary-label {
    margin-bottom: 0.35rem;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .payroll-cierre-summary-value {
    margin: 0;
    color: #1f2937;
    font-size: 1.15rem;
    font-weight: 800;
  }

  .payroll-cierre-summary-card.is-total .payroll-cierre-summary-value {
    color: #047857;
  }

  .payroll-cierre-note {
    padding: 0.85rem 1rem;
    border-radius: 14px;
    background: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
    font-size: 0.84rem;
  }

  .payroll-cierre-table-section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--dark-color);
  }

  .payroll-cierre-table-card {
    border: 1px solid rgba(17, 24, 39, 0.05);
    border-radius: 18px;
    box-shadow: 0 6px 18px rgba(17, 24, 39, 0.04);
    background: #fff;
    overflow: hidden;
  }

  .payroll-cierre-table-card .table-wrapper {
    padding: 0.75rem 1rem 0;
  }

  .payroll-cierre-table-card tbody tr.row-sin-banco {
    background: #fff7ed;
  }

  .payroll-cierre-table-card .col-total {
    font-weight: 700;
    color: #047857;
    white-space: nowrap;
  }

  .payroll-cierre-table-card tfoot tr.nomina-table-totals th {
    background: #f9fafb !important;
    border-top: 2px solid rgba(17, 24, 39, 0.12) !important;
    font-weight: 800 !important;
  }

  .payroll-cierre-table-card tfoot tr.nomina-table-totals th.col-total {
    color: #047857;
  }

  .payroll-cierre-table-card th.col-accion-recibo,
  .payroll-cierre-table-card td.col-accion-recibo,
  .payroll-cierre-table-card th.col-accion-entrega,
  .payroll-cierre-table-card td.col-accion-entrega {
    width: 1%;
    white-space: nowrap;
    padding-left: 0.35rem !important;
    padding-right: 0.35rem !important;
  }

  .payroll-cierre-table-card .col-accion-recibo .btn {
    padding: 0.2rem 0.4rem;
  }

</style>

<div class="container-fluid format_page payroll-cierre-page">
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center header payroll-cierre-hero flex-wrap gap-3">
        <div class="d-flex align-items-center">
          <div class="header-icon me-3">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <div>
            <div class="mb-1">
              <a href="{{ route('Nominaseditar', ['id' => $idpagonomina, 'idtiponomina' => $idtiponomina, 'fecha_ini' => $fecha_inicio, 'fecha_fin' => $fecha_fin]) }}" class="text-muted text-decoration-none fs-8">
                <i class="fa-solid fa-chevron-left me-1"></i>Edición de nómina
              </a>
            </div>
            <h2 class="mb-0 text-marino fw-bold">Previsualización de cierre</h2>
            <p class="text-muted mb-0">
              <b>{{ $nombre_nomina }}</b> &middot; {{ date('d/m/Y', strtotime($fecha_fin)) }}
              &middot;
              @if($tiponom == 1)
                Semanal
              @elseif($tiponom == 2)
                Quincenal
              @elseif($tiponom == 3)
                Mensual
              @endif
            </p>
          </div>
        </div>
        <div class="header-actions">
          <button type="button" class="btn btn-baseColor-light fs-7 mb-2" onclick="abrirListadoNominaPdf({{ (int) $idpagonomina }})" title="Listado de la nómina">
            <i class="fa-solid fa-list me-1"></i> Listado
          </button>
          <a class="btn btn-baseColor-light fs-7 mb-2" target="_blank" href="{{ route('Nominas.exportarComprobantes', $idpagonomina) }}" title="Ver todos los recibos">
            <i class="fa-solid fa-file-invoice me-1"></i> Recibos
          </a>
          @if($saldosVisor > 0)
          <button type="button" class="btn btn-danger fs-7 mb-2" onclick="confirmarSeleccionCuentasCierre()">
            <i class="fas fa-times-circle me-1"></i> Cerrar y desembolsar
          </button>
          @else
          <button type="button" class="btn btn-danger fs-7 mb-2" disabled>
            <i class="fas fa-times-circle me-1"></i> Cerrar y desembolsar
          </button>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    @if($total_fiscal > 0)
    <div class="col-lg-3 col-md-6">
      <div class="payroll-cierre-summary-card">
        <div class="payroll-cierre-summary-label">Total fiscal</div>
        <p class="payroll-cierre-summary-value">$ {{ number_format($total_fiscal, 2) }}</p>
      </div>
    </div>
    @endif
    @if(!$visorFiscalActivo && $total_excedente > 0)
    <div class="col-lg-3 col-md-6">
      <div class="payroll-cierre-summary-card">
        <div class="payroll-cierre-summary-label">Total excedente</div>
        <p class="payroll-cierre-summary-value">$ {{ number_format($total_excedente, 2) }}</p>
      </div>
    </div>
    @endif
    <div class="col-lg-3 col-md-6">
      <div class="payroll-cierre-summary-card is-total">
        <div class="payroll-cierre-summary-label">{{ $visorFiscalActivo ? 'Total fiscal a dispersar' : 'Total a dispersar' }}</div>
        <p class="payroll-cierre-summary-value">$ {{ number_format($saldosVisor, 2) }}</p>
      </div>
    </div>
    <div class="col-lg-3 col-md-6">
      <div class="payroll-cierre-summary-card">
        <div class="payroll-cierre-summary-label">Empleados</div>
        <p class="payroll-cierre-summary-value">{{ count($varnominas) }}</p>
      </div>
    </div>
  </div>

  @if($empleadosSinBanco > 0)
    <div class="row mb-3">
      <div class="col-12">
        <div class="payroll-cierre-note">
          <i class="fa-solid fa-triangle-exclamation me-1"></i>
          {{ $empleadosSinBanco }} empleado(s) tienen datos bancarios incompletos. Verifique antes de continuar.
        </div>
      </div>
    </div>
  @endif

  <div class="row">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h6 class="payroll-cierre-table-section-title mb-0">
          <i class="fa-solid fa-table-list me-2 text-muted"></i>
          Detalle de dispersión por empleado
        </h6>
      </div>
      <div class="payroll-cierre-table-card">
        <div class="table-wrapper">
          <div class="table-responsive">
            <table
              id="tablePreviewCierre"
              class="table table-striped table-hover display modern-table w-100"
              data-export-title="Previsualización cierre - {{ $nombre_nomina }}"
            >
            <thead>
              <tr>
                <th class="text-truncate text-center">Recibo</th>
                <th class="text-truncate text-center">Entrega</th>
                <th class="text-truncate">#</th>
                <th class="text-truncate">Empleado</th>
                <th class="text-truncate">Banco</th>
                <th class="text-truncate">ID Banca</th>
                <th class="text-truncate">No. Cuenta</th>
                @if($total_fiscal > 0)
                <th class="text-truncate text-end">Total fiscal</th>
                @endif
                @if(!$visorFiscalActivo && $total_excedente > 0)
                <th class="text-truncate text-end">Total excedente</th>
                @endif
                <th class="text-truncate text-end">{{ $visorFiscalActivo ? 'Total fiscal a pagar' : 'Total a pagar' }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($varnominas as $nomina)
                @php($nombreEmpleado = trim($nomina->primer_nombre.' '.$nomina->segundo_nombre.' '.$nomina->apellido_paterno.' '.$nomina->apellido_materno))
                @php($sinBanco = empty(trim($nomina->banco ?? '')) || empty(trim($nomina->numero_cuenta ?? '')))
                @php($totalPagarEmpleado = $visorFiscalActivo ? $nomina->total_nomina_fiscal : $nomina->total_apagar)
                <tr @if($sinBanco) class="row-sin-banco" @endif>
                  <td class="text-center">
                    <a class="btn btn-secondary btn-sm m-0" target="_blank" href="{{ route('Nominas.exportarComprobante', $nomina->id) }}" title="Imprimir recibo">
                      <i class="fa-solid fa-print"></i>
                    </a>
                  </td>
                  <td class="text-center" data-order="{{ (int)($nomina->entrega ?? 0) }}">
                    <input
                      type="checkbox"
                      class="form-check-input check-entrega-nomina"
                      data-id="{{ $nomina->id }}"
                      title="Marcar entrega de pago"
                      {{ (int)($nomina->entrega ?? 0) === 1 ? 'checked' : '' }}
                    >
                  </td>
                  <td data-order="{{ $nomina->idempleado }}">{{ $nomina->idempleado }}</td>
                  <td class="text-truncate">{{ $nombreEmpleado }}</td>
                  <td class="text-truncate">{{ $nomina->banco ?: '—' }}</td>
                  <td class="text-truncate">{{ $nomina->idbanca ?: '—' }}</td>
                  <td class="text-truncate">{{ $nomina->numero_cuenta ?: '—' }}</td>
                  @if($total_fiscal > 0)
                  <td class="text-end" data-order="{{ $nomina->total_nomina_fiscal }}">$ {{ number_format($nomina->total_nomina_fiscal, 2) }}</td>
                  @endif
                  @if(!$visorFiscalActivo && $total_excedente > 0)
                  <td class="text-end" data-order="{{ $nomina->total_apagar_excedente }}">$ {{ number_format($nomina->total_apagar_excedente, 2) }}</td>
                  @endif
                  <td class="text-end col-total" data-order="{{ $totalPagarEmpleado }}">$ {{ number_format($totalPagarEmpleado, 2) }}</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr class="nomina-table-totals">
                <th></th>
                <th></th>
                <th></th>
                <th colspan="4" class="text-end nomina-total-label">Totales</th>
                @if($total_fiscal > 0)
                <th class="text-end">$ {{ number_format($total_fiscal, 2) }}</th>
                @endif
                @if(!$visorFiscalActivo && $total_excedente > 0)
                <th class="text-end">$ {{ number_format($total_excedente, 2) }}</th>
                @endif
                <th class="text-end col-total">$ {{ number_format($total_pagar, 2) }}</th>
              </tr>
            </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<form id="formCerrarNomina" action="/Nominas/cerrarnomina/{{ $idpagonomina }}/{{ $tiponom }}/{{ $fecha_inicio }}/{{ $fecha_fin }}" method="POST" style="display:none;">
  @csrf
  @if($total_fiscal > 0)
    <input type="hidden" name="total_fiscal" value="{{ $total_fiscal }}">
    <input type="hidden" name="cuentaFiscal" id="hf_cuenta_fiscal">
  @endif
  @if($total_excedente > 0 && !$visorFiscalActivo)
    <input type="hidden" name="total_excedente" value="{{ $total_excedente }}">
    <input type="hidden" name="cuentaExcedente" id="hf_cuenta_excedente">
  @endif
</form>
@endsection

@section('js')
<script src="{{ asset('js/tablePreviewCierre.js') }}"></script>
<script>
$(document).on('change', '.check-entrega-nomina', function () {
  const checkbox = this;
  const idpagodet = checkbox.dataset.id;
  const entrega = checkbox.checked ? 1 : 0;
  const estadoAnterior = !checkbox.checked;

  checkbox.disabled = true;

  $.ajax({
    url: `/Nominas/entrega/${idpagodet}`,
    method: 'POST',
    data: {
      _token: $('meta[name="csrf-token"]').attr('content'),
      entrega: entrega
    },
    success: function (res) {
      checkbox.disabled = false;
      if (!res.ok) {
        checkbox.checked = estadoAnterior;
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: res.message || 'No se pudo actualizar', showConfirmButton: false, timer: 2500 });
        return;
      }
      const $cell = $(checkbox).closest('td');
      if ($cell.length && $.fn.DataTable.isDataTable('#tablePreviewCierre')) {
        $('#tablePreviewCierre').DataTable().cell($cell).invalidate().draw(false);
      }
      Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: res.message, showConfirmButton: false, timer: 1800 });
    },
    error: function (xhr) {
      checkbox.checked = estadoAnterior;
      checkbox.disabled = false;
      const message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo actualizar la entrega.';
      Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: message, showConfirmButton: false, timer: 2500 });
    }
  });
});

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

const cuentasCerrarNomina = @json($varcuentas->map(fn($c) => ['id' => $c->id, 'descripcion' => $c->descripcion])->values());
const configCerrarNomina = {
  tieneFiscal: {{ $total_fiscal > 0 ? 'true' : 'false' }},
  tieneExcedente: {{ (!$visorFiscalActivo && $total_excedente > 0) ? 'true' : 'false' }},
  totalFiscal: '{{ number_format($total_fiscal, 2) }}',
  totalExcedente: '{{ number_format($total_excedente, 2) }}',
  saldos: '{{ number_format($saldosVisor, 2) }}'
};

function buildCuentasSelect(id, label) {
  let options = '<option value="">Seleccionar...</option>';
  cuentasCerrarNomina.forEach((cuenta) => {
    options += `<option value="${cuenta.id}">${cuenta.descripcion}</option>`;
  });
  return `
    <div class="text-start mb-3">
      <label class="form-label fw-semibold">${label}</label>
      <select class="form-select" id="${id}">${options}</select>
    </div>
  `;
}

function confirmarSeleccionCuentasCierre() {
  let html = '<div class="text-start">';
  if (configCerrarNomina.tieneFiscal) {
    html += `<p class="mb-2"><strong>Fiscal:</strong> $ ${configCerrarNomina.totalFiscal}</p>`;
    html += buildCuentasSelect('swal_cuenta_fiscal', 'Cuenta fiscal');
  }
  if (configCerrarNomina.tieneExcedente) {
    html += `<p class="mb-2"><strong>Excedente:</strong> $ ${configCerrarNomina.totalExcedente}</p>`;
    html += buildCuentasSelect('swal_cuenta_excedente', 'Cuenta excedente');
  }
  html += `<p class="mt-3 mb-0"><strong>Total:</strong> $ ${configCerrarNomina.saldos}</p></div>`;

  Swal.fire({
    title: 'Confirmar cierre de nómina',
    html: html,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#475569',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Cerrar y desembolsar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    focusConfirm: false,
    preConfirm: () => {
      const data = {};
      if (configCerrarNomina.tieneFiscal) {
        const cuentaFiscal = document.getElementById('swal_cuenta_fiscal')?.value;
        if (!cuentaFiscal) {
          Swal.showValidationMessage('Seleccione la cuenta fiscal.');
          return false;
        }
        data.cuentaFiscal = cuentaFiscal;
      }
      if (configCerrarNomina.tieneExcedente) {
        const cuentaExcedente = document.getElementById('swal_cuenta_excedente')?.value;
        if (!cuentaExcedente) {
          Swal.showValidationMessage('Seleccione la cuenta excedente.');
          return false;
        }
        data.cuentaExcedente = cuentaExcedente;
      }
      return data;
    }
  }).then((result) => {
    if (!result.isConfirmed) return;
    const form = document.getElementById('formCerrarNomina');
    if (configCerrarNomina.tieneFiscal) {
      document.getElementById('hf_cuenta_fiscal').value = result.value.cuentaFiscal;
    }
    if (configCerrarNomina.tieneExcedente) {
      document.getElementById('hf_cuenta_excedente').value = result.value.cuentaExcedente;
    }
    form.submit();
  });
}
</script>
@endsection
