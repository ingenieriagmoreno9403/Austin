@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php($total_fiscal = 0)
@php($total_excedente = 0)
@php($saldos = 0)


@foreach($aguinaldos_det as $pago)
  @php($total_fiscal = $total_fiscal + $pago->total_pagar_f)
  @php($total_excedente = $total_excedente + $pago->total_pagar_e)
@endforeach

@php($saldos = $total_excedente + $total_fiscal)

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



{{--------------------------- Encabezado de la pagina----------------------}}
<div class="container-fluid format_page">
  <!-- Header Section -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center header">
        <div class="d-flex align-items-center">
          <div class="header-icon me-3">
            <i class="fas fa-gift"></i>
          </div>
          <div>
            <div class="mb-1">
              <a href="/Nominas/Aguinaldos" class="text-muted text-decoration-none fs-8">
                <i class="fa-solid fa-chevron-left me-1"></i>Aguinaldo
              </a>
            </div>
            <h2 class="mb-0 text-marino fw-bold">
              Edición de Aguinaldos
              <span class="fw-normal">{{$aguinaldos_enc->nombre}}</span>
            </h2>
            <p class="text-muted mb-0">
              @if ($aguinaldos_enc->estado == 'EDICION')
                <span class="badge badge-primary fw-normal fs-9">Edición</span>
              @elseif($aguinaldos_enc->estado == 'CERRADO')
                <span class="badge badge-danger fw-normal fs-9">Cerrado</span>
              @endif
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8 col-12 p-4 pt-0 pb-1">
      <div class="row text-center justify-content-end">

          <div class="animate___animated animate___backInLeft col-md-2 col-6 p-2">
                @if($aguinaldos_enc->estado== "EDICION")
                    <button type="button" onclick="confirmarRecalcular()" class="btn btn-success rounded-1 fs-8 text-truncate" style="width: 100%">
                      <i class="fas fa-calculator "></i>
                      Recalcular 
                    </button>
                @else
                  <button class="btn btn-success rounded-1 fs-8 text-truncate" style="width: 100%" disabled >
                    <i class="fas fa-calculator "></i>
                    Recalcular 
                  </button> 
                @endif
          </div>
          
          <div class="dropdown animate___animated animate___backInLeft col-md-2 col-6 p-2">
              <a class="btn btn-primary dropdown-toggle fs-8" style="width: 100%" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="fa-solid fa-download "></i> Exportar 
              </a>

              <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                  <li><a class="dropdown-item fs-8" href="/Nominas/Aguinaldos/exportar_excel/{{$aguinaldos_enc->id}}">Excel Aguinaldos</a></li>
                    @if($aguinaldos_enc->estado == "CERRADO")
                      <li><a class="dropdown-item fs-8" href="/ExportarlayoutAguinaldo/{{ $aguinaldos_enc->id}}">Layout Dispersión</a></li>
                      <li><a class="dropdown-item fs-8" href="/Nominas/Aguinaldos/exportarComprobantes/{{ $aguinaldos_enc->id}}">Recibos de Pago</a></li>
                    @else
                      <li><a class="dropdown-item disabled fs-8" href="#">Layout Dispersión</a></li>
                      <li><a class="dropdown-item disabled fs-8" href="#">Recibos de Pago</a></li>
                    @endif
              </ul>
          </div>
      
          
          <div class="animate___animated animate___backInLeft col-md-2 col-6 p-2">
              @if($validaAguinaldoCero == 0)
                @if($aguinaldos_enc->estado == "EDICION")
                    <button class="btn btn-danger rounded-1 fs-8 text-truncate" style="width: 100%" type="button" onclick="abrirCerrarAguinaldo()">
                      <i class="fas fa-times-circle "></i>
                      Cerrar  
                    </button>
                 @else
                    <button class="btn btn-danger rounded-1 fs-8 text-truncate" style="width: 100%" type="button" disabled>
                      <i class="fas fa-times-circle "></i>
                      Cerrar  
                    </button>
                @endif
              @else
                <button class="btn btn-danger rounded-1 fs-8 text-truncate" style="width: 100%" type="button" disabled>
                  <i class="fas fa-times-circle "></i>
                  Cerrar  
                </button>
              @endif
          </div>

          <div class="animate___animated animate___backInLeft col-md-3 col-6 p-2">
            @if($aguinaldos_enc->estado== "CERRADO")
                @if($validaTimbrado > 0)
                    <a class="btn btn-info rounded-1 fs-8 text-truncate" style="width: 100%" href="{{ route('veraguinaldotimbrado', $aguinaldos_enc->id) }}">
                      <i class="fa-solid fa-eye fs-6"></i>
                      Ver Aguinaldo Timbrado
                    </a>
                @else
                     <a class="btn btn-success rounded-1 fs-8 text-truncate" style="width: 100%" href="{{ route('timbrar_aguinaldo', $aguinaldos_enc->id) }}" onclick="mostrarCarga(); return false;">
                        <i class="fa-regular fa-bell fs-6"></i>
                          Timbrar Aguinaldo
                      </a> 
                @endif     
            @else
                <button class="btn btn-success rounded-1 fs-8 text-truncate" style="width: 100%" disabled>
                  <i class="fa-regular fa-bell fs-6"></i>
                    Timbrar Aguinaldo
                 </button>  
            @endif
          </div>
      </div>

       @if($aguinaldos_enc->estado== "EDICION")
        @if( $validaAguinaldoCero > 0)
          <div class="row justify-content-end p-2">
              <div class="animate__animated animate__pulse border border-danger bg-body rounded-3 p-3 pt-2 pb-2 col-md-11">
                  <h6 class="text-danger fs-7"><i class="fa-solid fa-triangle-exclamation"></i> Recuerde</h6>
                  <p class="fs-8 text-danger">El pago del aguinaldo fiscal debe <b>ser mayor a 0 para cerrar </b>, si necesita más información contacte a un superior.</p>
                </div>
          </div>
        @endif
      @endif
    </div>
  </div>
  
 
  @if($aguinaldos_enc->estado != 'CERRADO' && $validaAguinaldoCero > 0)
    <div class="row" style="margin-top: -15px">
  @else
   <div class="row mt-3">
  @endif
        <div class="m-1">
            <table class="table table-stripped table-hover display" id="tableaguinaldo">
              <thead>
                <tr>
                  <th class="text-truncate"></th>
                  <th class="text-truncate">Editar</th>
                  <th class="text-truncate">No. Empleado</th>
                  <th class="text-truncate">Nombre Empleado</th>
                  <th class="text-truncate">Puesto</th>
                  <th class="text-truncate">Banco</th>
                  <th class="text-truncate">ID Banca</th>
                  <th class="text-truncate">Numero de Cuenta</th>
                  <th class="text-truncate">Fecha Ingreso</th>
                  <th class="text-truncate">Días Trabajados</th>
                  <th class="text-truncate">Salario F.</th>
                  <th class="text-truncate">Salario E.</th>
                  <th class="text-truncate">Salario Diario</th>
                  <th class="text-truncate">Sueldo Mensual</th>
                  <th class="text-truncate">Días Aguinaldo Pagados</th>
                  <th class="text-truncate">Aguinaldo Fiscal</th>
                  <th class="text-truncate">Aguinaldo Excedente</th>
                  <th class="text-truncate">Aguinaldo Gravado</th>
                  <th class="text-truncate">Aguinaldo Exento</th>
                  <th class="text-truncate">Aguinaldo Total</th>
                  <th class="text-truncate">ISR Calculado</th>
                  <th class="text-truncate">Total Pagar Fiscal</th>
                  <th class="text-truncate">Total Pagar Excedente</th>
                  <th class="text-truncate">Total Pagar</th>
                </tr>
              </thead>
            
              <tbody>
                @foreach($aguinaldos_det as $item)
                  <tr>
                    <td class="table-light fs-8"></td>
                    <td class="table-light fs-8 text-truncate">
                      @if($aguinaldos_enc->estado != 'CERRADO')
                        <button class="btn btn-primary fs-9" type="button" onclick="editarDiasAguinaldo(this)"
                          data-idempleado="{{ $item->idempleado }}"
                          data-idnomina="{{ $item->idnomina }}"
                          data-fecha-ingreso="{{ $item->fecha_ingreso }}"
                          data-salario-diario="{{ $item->salario_fijo }}"
                          data-salario-excedente="{{ $item->excedente }}"
                          data-sueldo-mensual="{{ $item->salario_bruto }}"
                          data-dias="{{ $item->dias_aguinaldo_pagados }}"><i class="fs-8 fa-solid fa-pen"></i></button>
                      @else
                        <button class="btn btn-primary fs-9" disabled><i class="fs-8 fa-solid fa-pen"></i></button>
                      @endif
                    </td>
                    <td class="table-light fs-8">{{$item->idempleado}}</td>
                    <td class="table-light text-start fs-8">{{$item->nombre_empleado}}</td>
                    <td class="table-light text-start text-truncate fs-8">{{$item->puesto}}</td>
                    <td class="table-light text-truncate text-truncate fs-8">{{$item->banco}}</td>
                    <td class="table-light text-truncate text-truncate fs-8">{{$item->idbanca}}</td>
                    <td class="table-light text-truncate text-truncate fs-8">{{$item->numero_cuenta}}</td> 
                    <td class="table-light text-truncate text-truncate fs-8">{{date('d/m/Y', strtotime($item->fecha_ingreso))}}</td>
                    <td class="table-light text-truncate text-truncate fs-8">{{$item->dias_trabajados}}</td>

                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->salario_fijo, 2)}}</td>
                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->sueldo_diario - $item->salario_fijo, 2)}}</td>
                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->sueldo_diario, 2)}}</td> 
                    <td class="bg-0 fs-8">{{$item->sueldo_mensual}}</td>
                    <td class="bg-0 fs-8">{{$item->dias_aguinaldo_pagados}}</td>

                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->aguinaldo_f, 2)}}</td>
                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->aguinaldo_e, 2)}}</td>
                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->aguinaldo_gravado, 2)}}</td>
                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->aguinaldo_exento, 2)}}</td>
                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->aguinaldo_total, 2)}}</td>
                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->isr_calculado, 2)}}</td>
                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->total_pagar_f, 2)}}</td>
                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->total_pagar_e, 2)}}</td>
                    <td class="table-light text-truncate fs-8">$ {{ number_format($item->total_pagar, 2)}}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
        </div>  
  </div>
</div>


<form id="formCerrarAguinaldo" action="/Nominas/Aguinaldos/Cerrar/{{ $aguinaldos_enc->id }}" method="POST" style="display:none;">
  @csrf
  @if($total_fiscal > 0)
    <input type="hidden" name="total_fiscal" value="{{ $total_fiscal }}">
    <input type="hidden" name="cuentaFiscal" id="hf_aguinaldo_cuenta_fiscal">
  @endif
  @if($total_excedente > 0)
    <input type="hidden" name="total_excedente" value="{{ $total_excedente }}">
    <input type="hidden" name="cuentaExcedente" id="hf_aguinaldo_cuenta_excedente">
  @endif
</form>

<script>
const cuentasCerrarAguinaldo = @json($varcuentas->map(fn($c) => ['id' => $c->id, 'descripcion' => $c->descripcion])->values());
const configCerrarAguinaldo = {
  tieneFiscal: {{ $total_fiscal > 0 ? 'true' : 'false' }},
  tieneExcedente: {{ $total_excedente > 0 ? 'true' : 'false' }},
  totalFiscal: '{{ number_format($total_fiscal, 2) }}',
  totalExcedente: '{{ number_format($total_excedente, 2) }}',
  saldos: '{{ number_format($saldos, 2) }}',
  puedeCerrar: {{ $saldos > 0 ? 'true' : 'false' }}
};
const urlEditarDiasAguinaldo = '/Nominas/Aguinaldos/EditarDias/{{ $aguinaldos_enc->id }}';
const csrfTokenAguinaldo = '{{ csrf_token() }}';

function buildCuentasSelectAguinaldo(id, label) {
  let options = '<option value="">Seleccionar...</option>';
  cuentasCerrarAguinaldo.forEach((cuenta) => {
    options += `<option value="${cuenta.id}">${cuenta.descripcion}</option>`;
  });
  return `
    <div class="text-start mb-3">
      <label class="form-label fw-semibold">${label}</label>
      <select class="form-select" id="${id}">${options}</select>
    </div>
  `;
}

function confirmarRecalcular() {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Los cambios editados en "Días Aguinaldo Pagados" se recalcularán. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, recalcular',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '/Nominas/Aguinaldos/Calcular/{{ $aguinaldos_enc->id }}';
        }
    });
}

function abrirCerrarAguinaldo() {
  if (!configCerrarAguinaldo.puedeCerrar) {
    Swal.fire({
      icon: 'warning',
      title: 'No se puede cerrar',
      text: 'El total a dispersar debe ser mayor a 0.'
    });
    return;
  }

  let html = '<div class="text-start">';
  if (configCerrarAguinaldo.tieneFiscal) {
    html += `<p class="mb-2"><strong>Fiscal:</strong> $ ${configCerrarAguinaldo.totalFiscal}</p>`;
    html += buildCuentasSelectAguinaldo('swal_aguinaldo_cuenta_fiscal', 'Cuenta fiscal');
  }
  if (configCerrarAguinaldo.tieneExcedente) {
    html += `<p class="mb-2"><strong>Excedente:</strong> $ ${configCerrarAguinaldo.totalExcedente}</p>`;
    html += buildCuentasSelectAguinaldo('swal_aguinaldo_cuenta_excedente', 'Cuenta excedente');
  }
  html += `<p class="mt-3 mb-0"><strong>Total:</strong> $ ${configCerrarAguinaldo.saldos}</p></div>`;

  Swal.fire({
    title: 'Cerrar aguinaldo',
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
      if (configCerrarAguinaldo.tieneFiscal) {
        const cuentaFiscal = document.getElementById('swal_aguinaldo_cuenta_fiscal')?.value;
        if (!cuentaFiscal) {
          Swal.showValidationMessage('Seleccione la cuenta fiscal.');
          return false;
        }
        data.cuentaFiscal = cuentaFiscal;
      }
      if (configCerrarAguinaldo.tieneExcedente) {
        const cuentaExcedente = document.getElementById('swal_aguinaldo_cuenta_excedente')?.value;
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
    const form = document.getElementById('formCerrarAguinaldo');
    if (configCerrarAguinaldo.tieneFiscal) {
      document.getElementById('hf_aguinaldo_cuenta_fiscal').value = result.value.cuentaFiscal;
    }
    if (configCerrarAguinaldo.tieneExcedente) {
      document.getElementById('hf_aguinaldo_cuenta_excedente').value = result.value.cuentaExcedente;
    }
    form.submit();
  });
}

function editarDiasAguinaldo(btn) {
  const diasActuales = btn.getAttribute('data-dias');
  Swal.fire({
    title: 'Editar días de aguinaldo',
    html: `
      <div class="text-start">
        <label class="form-label fw-semibold" for="swal_dias_aguinaldo">Días aguinaldo pagados</label>
        <input type="number" step="any" min="0" class="form-control" id="swal_dias_aguinaldo" value="${diasActuales}">
      </div>
    `,
    icon: 'info',
    showCancelButton: true,
    confirmButtonColor: '#475569',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Guardar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
    focusConfirm: false,
    preConfirm: () => {
      const dias = document.getElementById('swal_dias_aguinaldo')?.value;
      if (dias === '' || Number(dias) < 0) {
        Swal.showValidationMessage('Ingrese un número válido de días.');
        return false;
      }
      return dias;
    }
  }).then((result) => {
    if (!result.isConfirmed) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = urlEditarDiasAguinaldo;
    form.innerHTML = `
      <input type="hidden" name="_token" value="${csrfTokenAguinaldo}">
      <input type="hidden" name="dias_aguinaldo_pagados" value="${result.value}">
      <input type="hidden" name="idempleado" value="${btn.getAttribute('data-idempleado')}">
      <input type="hidden" name="idnomina" value="${btn.getAttribute('data-idnomina')}">
      <input type="hidden" name="fecha_ingreso" value="${btn.getAttribute('data-fecha-ingreso')}">
      <input type="hidden" name="salario_diario" value="${btn.getAttribute('data-salario-diario')}">
      <input type="hidden" name="salario_excedente" value="${btn.getAttribute('data-salario-excedente')}">
      <input type="hidden" name="sueldo_mensual" value="${btn.getAttribute('data-sueldo-mensual')}">
    `;
    document.body.appendChild(form);
    form.submit();
  });
}

function mostrarCarga() {
    Swal.fire({
        title: 'Procesando aguinaldo...',
        text: 'Por favor espere, esto puede tardar unos minutos.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    setTimeout(function() {
        window.location.href = "{{ route('timbrar_aguinaldo', $aguinaldos_enc->id) }}";
    }, 100);
}
</script>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validaXLSX.js') }}"></script>

@endsection