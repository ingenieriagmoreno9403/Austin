@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .cp-step {
        border: 0;
        border-radius: 1.5rem;
        background: var(--bs-body-bg, #fff);
        box-shadow: 0 .5rem 1rem rgba(17, 24, 39, .1);
        margin-bottom: 1.5rem;
        overflow: hidden;
        padding: 1rem;
    }
    .cp-step-header {
        background: transparent;
        padding: .25rem .5rem .75rem;
        font-weight: 600;
        color: var(--secondary-color, #374151);
        display: flex;
        align-items: center;
        gap: .75rem;
        font-size: 1.1rem;
    }
    .cp-step-num {
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
    .cp-step-body { padding: .5rem .5rem 0; }
    .factura-row { cursor: pointer; transition: background .15s; }
    .factura-row:hover { background: var(--primary-soft-bg, #f3f4f6); }
    .factura-row.selected { background: rgba(17, 24, 39, .08); }
    .cp-flow-arrow {
        text-align: center;
        color: var(--text-muted, #6b7280);
        font-size: 1.25rem;
        margin: .25rem 0;
    }
    .saldo-bar {
        height: 8px;
        border-radius: 4px;
        background: #e5e7eb;
        overflow: hidden;
    }
    .saldo-bar-fill {
        height: 100%;
        background: var(--primary-color, #111827);
        transition: width .3s;
    }
    .cp-alerta {
        border-radius: 1rem;
        transition: opacity 0.4s ease;
    }
    .cp-alerta.ocultando { opacity: 0; }
    .text-teal { color: var(--secondary-color, #374151) !important; }
    .cp-side-stack {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .cp-side-stack .cp-step:last-child { margin-bottom: 0; }
    .cp-top-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        margin-bottom: 0;
    }
    .cp-top-card .cp-step-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .cp-factura-table {
        max-height: 260px;
        overflow-y: auto;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-money-check-dollar"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Panel" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Panel
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Complementos de Pago (REP)</h2>
                        <p class="text-muted mb-0">Registro de abonos sobre facturas PPD. Complemento de pago ≠ Nota de crédito.</p>
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

    <div id="alertaComplemento" class="cp-alerta alert alert-danger border-0 shadow-sm rounded-4 d-none" role="alert">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i id="alertaComplementoIcono" class="fa-solid fa-circle-xmark"></i>
                    <strong id="alertaComplementoTitulo">Error</strong>
                </div>
                <div id="alertaComplementoMensaje" class="small mb-0"></div>
            </div>
            <button type="button" class="btn-close" onclick="ocultarAlertaComplemento()" aria-label="Cerrar"></button>
        </div>
    </div>

    <div class="alert alert-info border-0 shadow-sm rounded-4">
        <i class="fa-solid fa-scale-balanced me-2"></i>
        <strong>Regla de oro:</strong> Use <strong>Complemento de Pago</strong> cuando el cliente abone dinero.
        Use <strong>Nota de Crédito</strong> solo para descuentos, bonificaciones o devoluciones de mercancía.
    </div>

    {{-- Izquierda: pasos 1 y 2 | Derecha: guía --}}
    <div class="row g-4 align-items-stretch mb-4">
        <div class="col-lg-6">
            <div class="cp-side-stack">
                {{-- PASO 1 --}}
                <div class="cp-step">
                    <div class="cp-step-header">
                        <span class="cp-step-num">1</span>
                        Venta inicial — CFDI Ingreso (PPD)
                    </div>
                    <div class="cp-step-body">
                        <p class="text-muted small mb-3">
                            Seleccione la factura timbrada con método <span class="badge bg-secondary">PPD</span>
                            y forma de pago <span class="badge bg-secondary">99</span> (por definir).
                        </p>
                        <div class="table-responsive cp-factura-table">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="sticky-top">
                                    <tr>
                                        <th></th>
                                        <th>Folio</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Método</th>
                                        <th>Forma</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($facturasPpd as $f)
                                        <tr class="factura-row" data-id="{{ $f->id }}" onclick="seleccionarFactura({{ $f->id }})">
                                            <td><input type="radio" name="factura_sel" value="{{ $f->id }}" class="form-check-input"></td>
                                            <td><strong>{{ $f->folio }}</strong></td>
                                            <td>{{ $f->date ? \Carbon\Carbon::parse($f->date)->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                <div class="text-truncate" style="max-width:120px" title="{{ $f->reciver_nombre }}">{{ $f->reciver_nombre }}</div>
                                                <small class="text-muted">{{ $f->reciver_rfc }}</small>
                                            </td>
                                            <td><span class="badge bg-secondary">{{ $f->metodo_pago }}</span></td>
                                            <td><span class="badge bg-secondary">{{ $f->forma_pago ?? '99' }}</span></td>
                                            <td class="text-end">${{ number_format($f->total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                No hay facturas timbradas PPD disponibles.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="cp-flow-arrow"><i class="fa-solid fa-arrow-down"></i></div>

                {{-- PASO 2 --}}
                <div class="cp-step" id="pasoAbono" style="opacity:.5;pointer-events:none">
                    <div class="cp-step-header">
                        <span class="cp-step-num">2</span>
                        Abono del cliente
                    </div>
                    <div class="cp-step-body">
                        <div id="resumenFactura" class="alert alert-light border d-none mb-3"></div>

                        <div class="mb-3" id="barraSaldo" style="display:none">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Pagado</span>
                                <span id="lblSaldoInsoluto">Saldo: $0.00</span>
                            </div>
                            <div class="saldo-bar"><div class="saldo-bar-fill" id="barraSaldoFill" style="width:0%"></div></div>
                        </div>

                        <div class="modern-form">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="fechaPago">Fecha del pago</label>
                                    <input type="date" id="fechaPago" class="form-control" value="{{ date('Y-m-d') }}" onchange="actualizarResumen()">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="formaPagoAbono">Forma de pago del abono</label>
                                    <select id="formaPagoAbono" class="form-select" onchange="actualizarResumen()">
                                        @foreach($formasPago as $codigo => $label)
                                            <option value="{{ $codigo }}" {{ $codigo === '03' ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="montoAbono">Monto del abono</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" id="montoAbono" class="form-control" step="0.01" min="0.01" oninput="actualizarResumen()">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label" for="numParcialidad">Nº parcialidad</label>
                                    <input type="text" id="numParcialidad" class="form-control" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="saldoAnterior">Saldo anterior</label>
                                    <input type="text" id="saldoAnterior" class="form-control" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="saldoInsoluto">Saldo insoluto</label>
                                    <input type="text" id="saldoInsoluto" class="form-control fw-semibold text-teal" readonly>
                                </div>
                            </div>
                        </div>

                        <div id="historialParcialidades" class="mt-3 d-none">
                            <div class="small fw-semibold mb-2">Parcialidades anteriores</div>
                            <div id="listaParcialidades" class="small text-muted"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="cp-step cp-top-card">
                <div class="cp-step-header">
                    <i class="fa-solid fa-circle-info"></i>
                    Guía rápida PPD
                </div>
                <div class="cp-step-body small">
                    <ol class="ps-3 mb-3">
                        <li class="mb-2">Factura inicial: CFDI <strong>I</strong> con método <strong>PPD</strong> y forma <strong>99</strong></li>
                        <li class="mb-2">Cliente realiza un abono parcial o total</li>
                        <li class="mb-2">Se emite <strong>Complemento de Pago</strong> (CFDI <strong>P</strong>)</li>
                        <li class="mb-2">Se vincula al UUID de la factura original</li>
                        <li>Se registran parcialidad, saldo anterior e insoluto</li>
                    </ol>

                    <div class="border rounded p-3 bg-light">
                        <div class="fw-semibold mb-2">¿Cuándo NO usar complemento?</div>
                        <ul class="ps-3 mb-0 text-muted">
                            <li>Descuentos posteriores → Nota de Crédito</li>
                            <li>Devolución de mercancía → Nota de Crédito</li>
                            <li>Cobro simple sobre factura PPD → <strong>Complemento de Pago</strong></li>
                        </ul>
                    </div>

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

    <div class="row g-4">
        <div class="col-12">
            <div class="cp-flow-arrow" id="flechaEmitir" style="display:none"><i class="fa-solid fa-arrow-down"></i></div>

            {{-- PASO 3 --}}
            <div class="cp-step" id="pasoEmitir" style="display:none">
                <div class="cp-step-header">
                    <span class="cp-step-num">3</span>
                    Emitir REP — CFDI Tipo Pago (P)
                </div>
                <div class="cp-step-body">
                    <div id="resumenEmision" class="alert alert-success mb-3 d-none"></div>
                    <div class="alert alert-warning small">
                        <i class="fa-solid fa-calendar-days me-1"></i>
                        Emitir a más tardar el <strong>5º día natural</strong> del mes siguiente a la fecha del pago.
                        <span id="fechaLimite" class="ms-1"></span>
                    </div>
                    @if($permisoGestion === 'gestion_complementos_pago')
                        <button type="button" class="btn btn-baseColor btn-lg" id="btnEmitir" onclick="emitirComplemento()">
                            <i class="fa-solid fa-stamp me-2"></i> Timbrar Complemento de Pago
                        </button>
                    @else
                        <div class="alert alert-warning mb-0">Sin permisos para emitir complementos de pago.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Historial --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="cp-step">
                <div class="cp-step-header">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Complementos de pago emitidos
                </div>
                <div class="cp-step-body">
                    <div class="table-responsive">
                        <table class="table table-stripped table-hover display" id="table">
                            <thead>
                                <tr>
                                    <th class="text-center fw-bold text-truncate">Acciones</th>
                                    <th class="text-center fw-bold text-truncate">Folio REP</th>
                                    <th class="text-center fw-bold text-truncate">Factura</th>
                                    <th class="text-center fw-bold text-truncate">UUID REP</th>
                                    <th class="text-center fw-bold text-truncate">Parc.</th>
                                    <th class="text-center fw-bold text-truncate">Fecha pago</th>
                                    <th class="text-center fw-bold text-truncate">Forma</th>
                                    <th class="text-center fw-bold text-truncate">Abono</th>
                                    <th class="text-center fw-bold text-truncate">Saldo insoluto</th>
                                    <th class="text-center fw-bold text-truncate">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($complementos as $cp)
                                    @php
                                        $facturaOrig = $facturasRelacionadas[$cp->id_factura_original] ?? null;
                                    @endphp
                                    <tr>
                                        <td class="text-center text-nowrap">
                                            @if($cp->estado === 'Timbrado' && $cp->facturama_id)
                                                <a href="{{ route('complementosPago.ver', $cp->id) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   title="Ver complemento de pago (REP)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="{{ route('complementosPago.pdf', $cp->id) }}"
                                                   class="btn btn-sm btn-outline-danger"
                                                   title="Descargar PDF complemento (REP)">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                </a>
                                            @endif
                                            @if($facturaOrig && !empty($facturaOrig->facturama_id))
                                                <a href="{{ route('facturas.ver', $facturaOrig->id) }}"
                                                   class="btn btn-sm btn-outline-secondary"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   title="Ver factura original ({{ $cp->folio_factura }})">
                                                    <i class="fa-solid fa-file-invoice"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-center fw-normal">{{ $cp->folio_complemento ?? '-' }}</td>
                                        <td class="text-center fw-normal">{{ $cp->folio_factura }}</td>
                                        <td class="text-center fw-normal"><small>{{ $cp->uuid ?? '-' }}</small></td>
                                        <td class="text-center fw-normal">{{ $cp->numero_parcialidad }}</td>
                                        <td class="text-center fw-normal">{{ $cp->fecha_pago ? $cp->fecha_pago->format('d/m/Y') : '-' }}</td>
                                        <td class="text-center fw-normal">{{ $formasPago[$cp->forma_pago] ?? $cp->forma_pago }}</td>
                                        <td class="text-end text-success fw-semibold">${{ number_format($cp->monto_pagado, 2) }}</td>
                                        <td class="text-end fw-normal">${{ number_format($cp->saldo_insoluto, 2) }}</td>
                                        <td class="text-center">
                                            @if($cp->estado === 'Timbrado')
                                                <span class="badge bg-success">Timbrado</span>
                                            @elseif($cp->estado === 'Error')
                                                <span class="badge bg-danger">Error</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            @endif
                                        </td>
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
    let facturaSeleccionada = null;
    let datosFactura = null;
    let alertaComplementoTimeout = null;

    function mostrarAlertaComplemento(tipo, titulo, mensaje, autoOcultar = true) {
        const box = document.getElementById('alertaComplemento');
        const icono = document.getElementById('alertaComplementoIcono');
        const clases = {
            error: 'alert-danger',
            warning: 'alert-warning',
            info: 'alert-info',
            success: 'alert-success'
        };
        const iconos = {
            error: 'fa-circle-xmark',
            warning: 'fa-triangle-exclamation',
            info: 'fa-circle-info',
            success: 'fa-circle-check'
        };

        box.className = 'cp-alerta alert border-0 shadow-sm rounded-4 ' + (clases[tipo] || clases.error);
        box.classList.remove('d-none', 'ocultando');
        icono.className = 'fa-solid ' + (iconos[tipo] || iconos.error);
        document.getElementById('alertaComplementoTitulo').textContent = titulo;
        document.getElementById('alertaComplementoMensaje').innerHTML = (mensaje || 'Error desconocido').replace(/\n/g, '<br>');

        box.scrollIntoView({ behavior: 'smooth', block: 'center' });

        if (alertaComplementoTimeout) {
            clearTimeout(alertaComplementoTimeout);
        }

        if (autoOcultar) {
            alertaComplementoTimeout = setTimeout(ocultarAlertaComplemento, 10000);
        }
    }

    function ocultarAlertaComplemento() {
        const box = document.getElementById('alertaComplemento');
        box.classList.add('ocultando');

        if (alertaComplementoTimeout) {
            clearTimeout(alertaComplementoTimeout);
            alertaComplementoTimeout = null;
        }

        setTimeout(() => {
            box.classList.add('d-none');
            box.classList.remove('ocultando');
            document.getElementById('alertaComplementoMensaje').innerHTML = '';
        }, 400);
    }

    function mostrarErrorFacturama(mensaje) {
        mostrarAlertaComplemento('error', 'Error Facturama', mensaje);
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

    async function seleccionarFactura(id) {
        document.querySelectorAll('.factura-row').forEach(r => r.classList.remove('selected'));
        const row = document.querySelector(`.factura-row[data-id="${id}"]`);
        if (row) {
            row.classList.add('selected');
            row.querySelector('input[type=radio]').checked = true;
        }

        facturaSeleccionada = id;

        const paso = document.getElementById('pasoAbono');
        paso.style.opacity = '1';
        paso.style.pointerEvents = 'auto';

        try {
            const res = await fetch(`/Tesoreria/ComplementosPago/factura/${id}`);
            const data = await res.json();
            if (!data.success) {
                mostrarAlertaComplemento('warning', 'Atención', data.message);
                return;
            }

            datosFactura = data;
            const f = data.factura;
            mostrarReceptorFiscal(data.receptor || null);

            if (data.saldo_insoluto <= 0) {
                mostrarAlertaComplemento('info', 'Factura pagada', 'Esta factura ya no tiene saldo pendiente.');
            }

            document.getElementById('resumenFactura').classList.remove('d-none');
            document.getElementById('resumenFactura').innerHTML = `
                <strong>Factura ${f.folio}</strong> — UUID: <code>${f.uuid}</code><br>
                Cliente: ${f.receptor_nombre} (${f.receptor_rfc})<br>
                Total factura: <strong>$${fmt(f.total)}</strong> |
                Pagado: $${fmt(data.total_pagado)} |
                Saldo insoluto: <strong class="text-teal">$${fmt(data.saldo_insoluto)}</strong>
            `;

            document.getElementById('numParcialidad').value = data.siguiente_parcialidad;
            document.getElementById('saldoAnterior').value = '$' + fmt(data.saldo_insoluto);
            document.getElementById('montoAbono').value = data.saldo_insoluto > 0 ? data.saldo_insoluto.toFixed(2) : '';
            document.getElementById('montoAbono').max = data.saldo_insoluto;

            const pct = f.total > 0 ? ((data.total_pagado / f.total) * 100) : 0;
            document.getElementById('barraSaldo').style.display = 'block';
            document.getElementById('barraSaldoFill').style.width = pct + '%';
            document.getElementById('lblSaldoInsoluto').textContent = 'Saldo: $' + fmt(data.saldo_insoluto);

            if (data.complementos_previos.length > 0) {
                document.getElementById('historialParcialidades').classList.remove('d-none');
                document.getElementById('listaParcialidades').innerHTML = data.complementos_previos.map(c =>
                    `Parc. ${c.parcialidad}: $${fmt(c.monto)} — ${c.fecha} (insoluto: $${fmt(c.saldo_insoluto)})`
                ).join('<br>');
            } else {
                document.getElementById('historialParcialidades').classList.add('d-none');
            }

            document.getElementById('pasoEmitir').style.display = 'block';
            document.getElementById('flechaEmitir').style.display = 'block';
            actualizarResumen();
        } catch (e) {
            mostrarErrorFacturama('No se pudo cargar la factura. Verifique su conexión.');
        }
    }

    function actualizarResumen() {
        if (!datosFactura) return;

        const monto = parseFloat(document.getElementById('montoAbono').value) || 0;
        const saldoAnt = datosFactura.saldo_insoluto;
        const insoluto = Math.max(0, saldoAnt - monto);

        document.getElementById('saldoInsoluto').value = '$' + fmt(insoluto);

        const resumen = document.getElementById('resumenEmision');
        const fecha = document.getElementById('fechaPago').value;
        const forma = document.getElementById('formaPagoAbono');

        if (monto <= 0) {
            resumen.classList.add('d-none');
            return;
        }

        const limite = calcularLimite(fecha);
        document.getElementById('fechaLimite').textContent = limite ? `(límite: ${limite})` : '';

        resumen.classList.remove('d-none');
        resumen.innerHTML = `
            <strong>REP — CFDI Tipo P</strong><br>
            UUID relacionado: <code>${datosFactura.factura.uuid}</code><br>
            Parcialidad: <strong>${datosFactura.siguiente_parcialidad}</strong> |
            Abono: <strong>$${monto.toFixed(2)}</strong> |
            Forma: ${forma.options[forma.selectedIndex].text}<br>
            Saldo anterior: $${fmt(saldoAnt)} → Insoluto: <strong>$${fmt(insoluto)}</strong>
        `;
    }

    function calcularLimite(fechaStr) {
        if (!fechaStr) return '';
        const d = new Date(fechaStr + 'T12:00:00');
        d.setMonth(d.getMonth() + 1);
        d.setDate(5);
        return d.toLocaleDateString('es-MX');
    }

    async function emitirComplemento() {
        if (!facturaSeleccionada || !datosFactura) {
            mostrarAlertaComplemento('warning', 'Datos incompletos', 'Seleccione una factura PPD.');
            return;
        }

        const monto = parseFloat(document.getElementById('montoAbono').value);
        const fecha = document.getElementById('fechaPago').value;
        const forma = document.getElementById('formaPagoAbono').value;

        if (!monto || monto <= 0) {
            mostrarAlertaComplemento('warning', 'Monto inválido', 'Ingrese el monto del abono.');
            return;
        }
        if (monto > datosFactura.saldo_insoluto + 0.01) {
            mostrarAlertaComplemento('warning', 'Monto excedido', 'El abono supera el saldo insoluto.');
            return;
        }

        const confirm = await Swal.fire({
            title: '¿Timbrar complemento de pago?',
            html: `Abono de <strong>$${monto.toFixed(2)}</strong> — Parcialidad ${datosFactura.siguiente_parcialidad}`,
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
            const res = await fetch('{{ route("complementosPago.emitir") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    factura_id: facturaSeleccionada,
                    fecha_pago: fecha,
                    forma_pago: forma,
                    monto_pagado: monto
                })
            });
            const data = await res.json();
            if (data.success) {
                let msg = data.message;
                if (data.limite_emision) msg += `<br><small>Límite de emisión: ${data.limite_emision}</small>`;
                await Swal.fire('¡Éxito!', msg, 'success');
                window.location.reload();
            } else {
                mostrarErrorFacturama(data.message || 'No se pudo timbrar');
            }
        } catch (e) {
            mostrarErrorFacturama('Error de comunicación con el servidor.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-stamp me-2"></i> Timbrar Complemento de Pago';
        }
    }

    function fmt(n) {
        return Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
</script>
<script src="{{ asset('js/table.js') }}"></script>
@endsection
