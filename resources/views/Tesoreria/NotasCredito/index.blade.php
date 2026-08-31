@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .nc-step {
        border: 0;
        border-radius: 1.5rem;
        background: var(--bs-body-bg, #fff);
        box-shadow: 0 .5rem 1rem rgba(17, 24, 39, .1);
        margin-bottom: 1.5rem;
        overflow: hidden;
        padding: 1rem;
    }
    .nc-step-header {
        background: transparent;
        padding: .25rem .5rem .75rem;
        font-weight: 600;
        color: var(--secondary-color, #374151);
        display: flex;
        align-items: center;
        gap: .75rem;
        font-size: 1.1rem;
    }
    .nc-step-num {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--primary-color, #111827);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        flex-shrink: 0;
    }
    .nc-step-body { padding: .5rem .5rem 0; }
    .nc-check-item {
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        padding: .5rem 0;
        border-bottom: 1px dashed rgba(17, 24, 39, .1);
    }
    .nc-check-item:last-child { border-bottom: none; }
    .factura-row { cursor: pointer; transition: background .15s; }
    .factura-row:hover { background: var(--primary-soft-bg, #f3f4f6); }
    .factura-row.selected { background: rgba(17, 24, 39, .08); }
    .nc-flow-arrow {
        text-align: center;
        color: var(--text-muted, #6b7280);
        font-size: 1.25rem;
        margin: .25rem 0;
    }
    .nc-top-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        margin-bottom: 0;
    }
    .nc-top-card .nc-step-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .nc-top-card .table-responsive {
        flex: 1;
        max-height: none !important;
        min-height: 280px;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Panel" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Panel
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Notas de Crédito</h2>
                        <p class="text-muted mb-0">Ciclo de vida CFDI de egreso según SAT 4.0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 alert-dismissible fade show">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4 alert-dismissible fade show">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Cards iniciales del mismo tamaño --}}
    <div class="row g-4 align-items-stretch mb-4">
        <div class="col-lg-6">
            {{-- PASO 1: Factura original timbrada --}}
            <div class="nc-step nc-top-card">
                <div class="nc-step-header">
                    <span class="nc-step-num">1</span>
                    Venta y Facturación Original (CFDI Ingreso)
                </div>
                <div class="nc-step-body">
                    <p class="text-muted small mb-3">
                        Seleccione una factura timbrada con método de pago <span class="badge bg-dark">PUE</span>
                        (Pago en una sola exhibición) para evaluar si requiere ajuste.
                    </p>
                    <div class="table-responsive" style="overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="sticky-top">
                                <tr>
                                    <th></th>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Método</th>
                                    <th>UUID</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody id="tablaFacturas">
                                @forelse($facturasTimbradas as $f)
                                    <tr class="factura-row" data-id="{{ $f->id }}" onclick="seleccionarFactura({{ $f->id }})">
                                        <td><input type="radio" name="factura_sel" value="{{ $f->id }}" class="form-check-input"></td>
                                        <td><strong>{{ $f->folio }}</strong></td>
                                        <td>{{ $f->date ? \Carbon\Carbon::parse($f->date)->format('d/m/Y') : '-' }}</td>
                                        <td>
                                            <div class="text-truncate" style="max-width:140px" title="{{ $f->reciver_nombre }}">{{ $f->reciver_nombre }}</div>
                                            <small class="text-muted">{{ $f->reciver_rfc }}</small>
                                        </td>
                                        <td><span class="badge bg-dark">{{ $f->metodo_pago }}</span></td>
                                        <td><small class="text-muted text-truncate d-inline-block" style="max-width:100px" title="{{ $f->Uuid }}">{{ $f->Uuid }}</small></td>
                                        <td class="text-end">${{ number_format($f->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No hay facturas timbradas con método de pago PUE disponibles.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel lateral: diagrama resumido --}}
        <div class="col-lg-6">
            <div class="nc-step nc-top-card">
                <div class="nc-step-header">
                    <i class="fa-solid fa-diagram-project"></i>
                    Flujo del proceso
                </div>
                <div class="nc-step-body small">
                    <ol class="mb-3 ps-3">
                        <li class="mb-2">Factura de ingreso timbrada</li>
                        <li class="mb-2">Evaluar si se requiere ajuste</li>
                        <li class="mb-2">Seleccionar motivo (devolución, descuento, corrección o cancelación parcial)</li>
                        <li class="mb-2">Validar requisitos SAT y monto</li>
                        <li class="mb-2">Timbrar CFDI tipo <strong>E</strong> en Facturama</li>
                        <li class="mb-2">Entregar nota al cliente</li>
                        <li>Registrar ajuste contable/fiscal</li>
                    </ol>
                    <div class="border rounded p-3 bg-light mt-auto">
                        <div class="fw-semibold mb-2">Emisor configurado</div>
                        <div>{{ $emisor['nombre'] ?? '—' }}</div>
                        <div class="text-muted">RFC: {{ $emisor['rfc'] ?? '—' }}</div>
                        <div class="text-muted">Régimen fiscal: {{ $emisor['regimen_fiscal_texto'] ?? $emisor['regimen_fiscal'] ?? '—' }}</div>
                        <div class="text-muted">Lugar expedición: {{ $emisor['lugar_expedicion'] ?? '—' }}</div>
                        @if (!empty($emisor['direccion']))
                            <div class="text-muted small mt-1">{{ $emisor['direccion'] }}</div>
                        @endif
                    </div>
                    <div id="panelReceptorFiscal" class="border rounded p-3 bg-light mt-3 d-none">
                        <div class="fw-semibold mb-2">
                            <i class="fa-solid fa-user-tie me-1"></i> Receptor seleccionado
                        </div>
                        <div id="receptorNombre">—</div>
                        <div class="text-muted" id="receptorRazonSocialWrap" style="display:none;">
                            Razón social: <span id="receptorRazonSocial">—</span>
                        </div>
                        <div class="text-muted">RFC: <span id="receptorRfc">—</span></div>
                        <div class="text-muted">Régimen fiscal: <span id="receptorRegimen">—</span></div>
                        <div class="text-muted">C.P.: <span id="receptorCp">—</span></div>
                        <div class="text-muted" id="receptorUsoCfdiWrap" style="display:none;">
                            Uso CFDI: <span id="receptorUsoCfdi">—</span>
                        </div>
                        <div class="text-muted small mt-1" id="receptorDireccionWrap" style="display:none;">
                            <span id="receptorDireccion"></span>
                        </div>
                        <div class="text-muted small" id="receptorContactoWrap" style="display:none;">
                            <span id="receptorContacto"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="nc-flow-arrow"><i class="fa-solid fa-arrow-down"></i></div>

    <div class="row g-4">
        <div class="col-12">
            {{-- PASO 2: ¿Se requiere ajuste? --}}
            <div class="nc-step" id="pasoAjuste" style="opacity:.5;pointer-events:none">
                <div class="nc-step-header">
                    <span class="nc-step-num">2</span>
                    ¿Se requiere ajuste?
                </div>
                <div class="nc-step-body">
                    <div id="resumenFactura" class="alert alert-light border d-none mb-3"></div>
                    <div class="d-flex gap-3 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary" onclick="sinAjuste()">
                            <i class="fa-solid fa-ban me-1"></i> No — Sin cambios
                        </button>
                        <button type="button" class="btn btn-baseColor" onclick="requiereAjuste()">
                            <i class="fa-solid fa-check me-1"></i> Sí — Verificar motivo
                        </button>
                    </div>
                </div>
            </div>

            <div class="nc-flow-arrow" id="flechaMotivo" style="display:none"><i class="fa-solid fa-arrow-down"></i></div>

            {{-- PASO 3: Motivo --}}
            <div class="nc-step" id="pasoMotivo" style="display:none">
                <div class="nc-step-header">
                    <span class="nc-step-num">3</span>
                    Verificar motivo del ajuste
                </div>
                <div class="nc-step-body">
                    <div class="row g-2">
                        @foreach($motivos as $key => $motivo)
                            <div class="col-md-6">
                                <label class="w-100 border rounded p-3 d-flex gap-2 align-items-start motivo-card" style="cursor:pointer">
                                    <input type="radio" name="motivo" value="{{ $key }}" class="form-check-input mt-1" onchange="seleccionarMotivo()">
                                    <div>
                                        <strong>{{ $motivo['label'] }}</strong>
                                        <div class="small text-muted">{{ $motivo['descripcion'] }}</div>
                                        <span class="badge bg-secondary mt-1">Relación SAT: {{ $motivo['tipo_relacion'] }}</span>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="nc-flow-arrow" id="flechaRequisitos" style="display:none"><i class="fa-solid fa-arrow-down"></i></div>

            {{-- PASO 4: Requisitos SAT + Monto --}}
            <div class="nc-step" id="pasoRequisitos" style="display:none">
                <div class="nc-step-header">
                    <span class="nc-step-num">4</span>
                    Requisitos de emisión (SAT CFDI 4.0)
                </div>
                <div class="nc-step-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="nc-check-item"><i class="fa-solid fa-circle-check text-success"></i><span><strong>Tipo Egresos ("E")</strong> — CFDI de egreso</span></div>
                            <div class="nc-check-item"><i class="fa-solid fa-circle-check text-success"></i><span><strong>Relacionar UUID</strong> de factura original</span></div>
                            <div class="nc-check-item"><i class="fa-solid fa-circle-check text-success"></i><span><strong>Método de pago "PUE"</strong></span></div>
                            <div class="nc-check-item"><i class="fa-solid fa-circle-check text-success"></i><span><strong>Motivo en descripción</strong> del concepto</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo de ajuste</label>
                                <select id="tipoAjuste" class="form-select" onchange="actualizarMonto()">
                                    <option value="total">Monto total disponible</option>
                                    <option value="parcial">Monto parcial</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Monto de la nota de crédito (con IVA)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" id="montoNota" class="form-control" step="0.01" min="0.01" readonly>
                                </div>
                                <small class="text-muted">Saldo disponible: <strong id="saldoDisponible">$0.00</strong></small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción adicional (opcional)</label>
                                <textarea id="descripcionAdicional" class="form-control" rows="2" maxlength="500" placeholder="Detalle del ajuste..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="nc-flow-arrow" id="flechaEmitir" style="display:none"><i class="fa-solid fa-arrow-down"></i></div>

            {{-- PASO 5: Emitir --}}
            <div class="nc-step" id="pasoEmitir" style="display:none">
                <div class="nc-step-header">
                    <span class="nc-step-num">5</span>
                    Emitir CFDI de Egreso (Nota de Crédito)
                </div>
                <div class="nc-step-body">
                    <div id="resumenEmision" class="alert alert-info mb-3"></div>
                    @if($permisoGestion === 'gestion_notas_credito')
                        <button type="button" class="btn btn-baseColor btn-lg" id="btnEmitir" onclick="emitirNotaCredito()">
                            <i class="fa-solid fa-stamp me-2"></i> Timbrar Nota de Crédito
                        </button>
                    @else
                        <div class="alert alert-warning mb-0">No tiene permisos para emitir notas de crédito.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Historial de notas emitidas --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="nc-step">
                <div class="nc-step-header">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Notas de crédito emitidas
                </div>
                <div class="nc-step-body">
                    <div class="table-responsive">
                        <table class="table table-stripped table-hover display" id="table">
                            <thead>
                                <tr>
                                    <th class="text-center fw-bold text-truncate">Acciones</th>
                                    <th class="text-center fw-bold text-truncate">Folio NC</th>
                                    <th class="text-center fw-bold text-truncate">Factura origen</th>
                                    <th class="text-center fw-bold text-truncate">UUID NC</th>
                                    <th class="text-center fw-bold text-truncate">Motivo</th>
                                    <th class="text-center fw-bold text-truncate">Cliente</th>
                                    <th class="text-center fw-bold text-truncate">Total</th>
                                    <th class="text-center fw-bold text-truncate">Estado</th>
                                    <th class="text-center fw-bold text-truncate">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notasCredito as $nc)
                                    <tr>
                                        <td class="text-center text-nowrap">
                                            @if($nc->estado === 'Timbrada' && $nc->facturama_id)
                                                <a href="{{ route('notasCredito.ver', $nc->id) }}"
                                                    class="btn btn-sm btn-outline-primary"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    title="Ver nota de crédito (CFDI E)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="{{ route('notasCredito.pdf', $nc->id) }}"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Descargar PDF nota de crédito">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                </a>
                                            @endif
                                            @if(!empty($nc->id_factura_original))
                                                <a href="{{ route('facturas.ver', $nc->id_factura_original) }}"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    title="Ver factura original ({{ $nc->folio_factura }})">
                                                    <i class="fa-solid fa-file-invoice"></i>
                                                </a>
                                            @endif
                                            @if($permisoGestion === 'gestion_notas_credito')
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-secondary btn-eliminar-nc"
                                                    title="Eliminar registro"
                                                    data-id="{{ $nc->id }}"
                                                    data-folio="{{ $nc->folio_nota ?? $nc->folio_factura }}"
                                                    data-estado="{{ $nc->estado }}">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                        <td class="text-center fw-normal">{{ $nc->folio_nota ?? '-' }}</td>
                                        <td class="text-center fw-normal">{{ $nc->folio_factura }}</td>
                                        <td class="text-center fw-normal"><small>{{ $nc->uuid ?? '-' }}</small></td>
                                        <td class="text-center fw-normal">{{ $motivos[$nc->motivo]['label'] ?? $nc->motivo }}</td>
                                        <td class="text-start fw-normal">{{ $nc->receptor_nombre }}</td>
                                        <td class="text-end fw-normal">${{ number_format($nc->total, 2) }}</td>
                                        <td class="text-center">
                                            @if($nc->estado === 'Timbrada')
                                                <span class="badge bg-success">Timbrada</span>
                                            @elseif($nc->estado === 'Error')
                                                <span class="badge bg-danger">Error</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-normal">{{ $nc->created_at ? $nc->created_at->format('d/m/Y H:i') : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const MOTIVOS = @json($motivos);
    let facturaSeleccionada = null;
    let datosFactura = null;

    async function seleccionarFactura(id) {
        document.querySelectorAll('.factura-row').forEach(r => r.classList.remove('selected'));
        const row = document.querySelector(`.factura-row[data-id="${id}"]`);
        if (row) {
            row.classList.add('selected');
            row.querySelector('input[type=radio]').checked = true;
        }

        facturaSeleccionada = id;
        resetFlujo();

        const pasoAjuste = document.getElementById('pasoAjuste');
        pasoAjuste.style.opacity = '1';
        pasoAjuste.style.pointerEvents = 'auto';

        try {
            const res = await fetch(`/Tesoreria/NotasCredito/factura/${id}`);
            const data = await res.json();
            if (!data.success) {
                Swal.fire('Atención', data.message, 'warning');
                return;
            }
            datosFactura = data;
            const f = data.factura;
            mostrarReceptorFiscal(data.receptor || null);
            document.getElementById('resumenFactura').classList.remove('d-none');
            document.getElementById('resumenFactura').innerHTML = `
                <strong>Factura ${f.folio}</strong> — UUID: <code>${f.uuid}</code><br>
                Cliente: ${f.receptor_nombre} (${f.receptor_rfc})<br>
                Total factura: <strong>$${formatMoney(f.total)}</strong> |
                Notas previas: $${formatMoney(data.notas_credito_aplicadas)} |
                Saldo disponible: <strong class="text-success">$${formatMoney(data.saldo_disponible)}</strong>
            `;
            document.getElementById('saldoDisponible').textContent = '$' + formatMoney(data.saldo_disponible);
            document.getElementById('montoNota').value = data.saldo_disponible > 0 ? data.saldo_disponible.toFixed(2) : '';
        } catch (e) {
            Swal.fire('Error', 'No se pudo cargar la factura', 'error');
        }
    }

    function resetFlujo() {
        ['pasoMotivo','pasoRequisitos','pasoEmitir'].forEach(id => document.getElementById(id).style.display = 'none');
        ['flechaMotivo','flechaRequisitos','flechaEmitir'].forEach(id => document.getElementById(id).style.display = 'none');
        document.querySelectorAll('input[name=motivo]').forEach(r => r.checked = false);
    }

    function mostrarReceptorFiscal(receptor) {
        const panel = document.getElementById('panelReceptorFiscal');
        if (!panel || !receptor) {
            ocultarReceptorFiscal();
            return;
        }

        document.getElementById('receptorNombre').textContent = receptor.nombre || '—';

        const razonWrap = document.getElementById('receptorRazonSocialWrap');
        const razon = receptor.razon_social || '';
        if (razon && razon !== receptor.nombre) {
            document.getElementById('receptorRazonSocial').textContent = razon;
            razonWrap.style.display = 'block';
        } else {
            razonWrap.style.display = 'none';
        }

        document.getElementById('receptorRfc').textContent = receptor.rfc || '—';
        document.getElementById('receptorRegimen').textContent = receptor.regimen_fiscal_texto || receptor.regimen_fiscal || '—';
        document.getElementById('receptorCp').textContent = receptor.codigo_postal || '—';

        const usoWrap = document.getElementById('receptorUsoCfdiWrap');
        if (receptor.uso_cfdi) {
            document.getElementById('receptorUsoCfdi').textContent = receptor.uso_cfdi;
            usoWrap.style.display = 'block';
        } else {
            usoWrap.style.display = 'none';
        }

        const dirWrap = document.getElementById('receptorDireccionWrap');
        if (receptor.direccion) {
            document.getElementById('receptorDireccion').textContent = receptor.direccion;
            dirWrap.style.display = 'block';
        } else {
            dirWrap.style.display = 'none';
        }

        const contactoWrap = document.getElementById('receptorContactoWrap');
        const contactoPartes = [];
        if (receptor.telefono) contactoPartes.push('Tel: ' + receptor.telefono);
        if (receptor.correo) contactoPartes.push(receptor.correo);
        if (contactoPartes.length) {
            document.getElementById('receptorContacto').textContent = contactoPartes.join(' | ');
            contactoWrap.style.display = 'block';
        } else {
            contactoWrap.style.display = 'none';
        }

        panel.classList.remove('d-none');
    }

    function ocultarReceptorFiscal() {
        const panel = document.getElementById('panelReceptorFiscal');
        if (panel) panel.classList.add('d-none');
    }

    function sinAjuste() {
        Swal.fire({
            icon: 'info',
            title: 'Sin ajuste',
            text: 'La factura se mantiene sin cambios. No se generará nota de crédito.',
            timer: 3000,
            showConfirmButton: false
        });
        resetFlujo();
    }

    function requiereAjuste() {
        if (!datosFactura || datosFactura.saldo_disponible <= 0) {
            Swal.fire('Sin saldo', 'Esta factura no tiene saldo disponible para nota de crédito.', 'warning');
            return;
        }
        document.getElementById('pasoMotivo').style.display = 'block';
        document.getElementById('flechaMotivo').style.display = 'block';
    }

    function seleccionarMotivo() {
        document.getElementById('pasoRequisitos').style.display = 'block';
        document.getElementById('flechaRequisitos').style.display = 'block';
        document.getElementById('pasoEmitir').style.display = 'block';
        document.getElementById('flechaEmitir').style.display = 'block';
        actualizarResumenEmision();
    }

    function actualizarMonto() {
        const tipo = document.getElementById('tipoAjuste').value;
        const input = document.getElementById('montoNota');
        if (!datosFactura) return;

        if (tipo === 'total') {
            input.value = datosFactura.saldo_disponible.toFixed(2);
            input.readOnly = true;
        } else {
            input.readOnly = false;
            if (!input.value) input.value = datosFactura.saldo_disponible.toFixed(2);
        }
        actualizarResumenEmision();
    }

    function actualizarResumenEmision() {
        const motivoInput = document.querySelector('input[name=motivo]:checked');
        if (!motivoInput || !datosFactura) return;

        const motivo = MOTIVOS[motivoInput.value];
        const monto = parseFloat(document.getElementById('montoNota').value) || 0;
        const subtotal = (monto / 1.16).toFixed(2);
        const iva = (monto - subtotal).toFixed(2);

        document.getElementById('resumenEmision').innerHTML = `
            <strong>Resumen de emisión</strong><br>
            Motivo: ${motivo.label} (Relación ${motivo.tipo_relacion})<br>
            UUID relacionado: <code>${datosFactura.factura.uuid}</code><br>
            CFDI Tipo: <strong>E</strong> | Método: <strong>PUE</strong><br>
            Subtotal: $${subtotal} | IVA: $${iva} | Total: <strong>$${monto.toFixed(2)}</strong>
        `;
    }

    document.getElementById('montoNota')?.addEventListener('input', actualizarResumenEmision);

    async function emitirNotaCredito() {
        const motivoInput = document.querySelector('input[name=motivo]:checked');
        if (!facturaSeleccionada || !motivoInput) {
            Swal.fire('Datos incompletos', 'Seleccione factura y motivo.', 'warning');
            return;
        }

        const monto = parseFloat(document.getElementById('montoNota').value);
        if (!monto || monto <= 0) {
            Swal.fire('Monto inválido', 'Ingrese un monto válido.', 'warning');
            return;
        }

        const confirm = await Swal.fire({
            title: '¿Timbrar nota de crédito?',
            html: `Se emitirá un CFDI de Egreso por <strong>$${monto.toFixed(2)}</strong> relacionado a la factura.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, timbrar',
            cancelButtonText: 'Cancelar'
        });

        if (!confirm.isConfirmed) return;

        const btn = document.getElementById('btnEmitir');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Timbrando...';

        try {
            const res = await fetch('{{ route("notasCredito.emitir") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    factura_id: facturaSeleccionada,
                    motivo: motivoInput.value,
                    tipo_ajuste: document.getElementById('tipoAjuste').value,
                    monto: monto,
                    descripcion_adicional: document.getElementById('descripcionAdicional').value
                })
            });

            const data = await res.json();

            if (data.success) {
                await Swal.fire('¡Éxito!', data.message, 'success');
                window.location.reload();
            } else {
                mostrarErrorFacturama(data.message || 'No se pudo timbrar la nota de crédito');
            }
        } catch (e) {
            Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-stamp me-2"></i> Timbrar Nota de Crédito';
        }
    }

    function formatMoney(n) {
        return Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function mostrarErrorFacturama(mensaje) {
        Swal.fire({
            icon: 'error',
            title: 'Error Facturama',
            html: (mensaje || 'Error desconocido').replace(/\n/g, '<br>'),
            width: 620
        });
    }

    document.querySelectorAll('.btn-eliminar-nc').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const id = this.getAttribute('data-id');
            const folio = this.getAttribute('data-folio') || ('#' + id);
            const estado = this.getAttribute('data-estado') || '';
            const esTimbrada = estado === 'Timbrada';

            const advertenciaTimbrada = esTimbrada
                ? '<br><br><span class="text-danger"><strong>Atención:</strong> esta nota está timbrada. Eliminar el registro solo lo quita del sistema; <strong>no cancela el CFDI ante el SAT</strong>.</span>'
                : '';

            const confirmacion = await Swal.fire({
                title: '¿Estás SUPER seguro?',
                html: `Vas a eliminar el registro de la nota de crédito <strong>${folio}</strong>.<br><br>Esta acción <strong>no se puede deshacer</strong>.${advertenciaTimbrada}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, estoy SUPER seguro',
                cancelButtonText: 'No, cancelar',
                focusCancel: true,
            });

            if (!confirmacion.isConfirmed) return;

            const confirmacionFinal = await Swal.fire({
                title: 'Confirmación final',
                text: '¿Eliminar definitivamente este registro?',
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                focusCancel: true,
            });

            if (!confirmacionFinal.isConfirmed) return;

            try {
                const res = await fetch(`{{ url('/Tesoreria/NotasCredito') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                });

                const data = await res.json();

                if (data.success) {
                    await Swal.fire('Eliminado', data.message, 'success');
                    window.location.reload();
                } else {
                    Swal.fire('Error', data.message || 'No se pudo eliminar el registro', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
            }
        });
    });
</script>
<script src="{{ asset('js/table.js') }}"></script>
@endsection
