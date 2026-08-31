@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .nd-step {
        border: 0;
        border-radius: 1.5rem;
        background: var(--bs-body-bg, #fff);
        box-shadow: 0 .5rem 1rem rgba(17, 24, 39, .1);
        margin-bottom: 1.5rem;
        overflow: hidden;
        padding: 1rem;
    }
    .nd-step-header {
        background: transparent;
        padding: .25rem .5rem .75rem;
        font-weight: 600;
        color: var(--secondary-color, #374151);
        display: flex;
        align-items: center;
        gap: .75rem;
        font-size: 1.1rem;
    }
    .nd-step-num {
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
    .nd-step-body { padding: .5rem .5rem 0; }
    .factura-row { cursor: pointer; transition: background .15s; }
    .factura-row:hover { background: var(--primary-soft-bg, #f3f4f6); }
    .factura-row.selected { background: rgba(17, 24, 39, .08); }
    .nd-flow-arrow {
        text-align: center;
        color: var(--text-muted, #6b7280);
        font-size: 1.25rem;
        margin: .25rem 0;
    }
    .nd-side-stack {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .nd-side-stack .nd-step:last-child {
        margin-bottom: 0;
    }
    .nd-top-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        margin-bottom: 0;
    }
    .nd-top-card .nd-step-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    .nd-factura-table {
        max-height: 260px;
        overflow-y: auto;
    }
    .tipo-card {
        cursor: pointer;
        transition: border-color .15s, box-shadow .15s;
    }
    .tipo-card:hover, .tipo-card.active {
        border-color: var(--primary-color, #111827) !important;
        box-shadow: 0 0 0 2px rgba(17, 24, 39, .12);
    }
    .trilogia-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .6rem 0;
        border-bottom: 1px dashed rgba(17, 24, 39, .1);
    }
    .trilogia-item:last-child { border-bottom: none; }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-file-circle-plus"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Panel" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Panel
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Notas de Débito</h2>
                        <p class="text-muted mb-0">Ajustes comerciales y fiscales que aumentan el saldo de la deuda del cliente.</p>
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

    {{-- Izquierda: pasos 1 y 2 apilados | Derecha: trilogía --}}
    <div class="row g-4 align-items-stretch mb-4">
        <div class="col-lg-6">
            <div class="nd-side-stack">
                {{-- PASO 1 --}}
                <div class="nd-step">
                    <div class="nd-step-header">
                        <span class="nd-step-num">1</span>
                        Emisión de Factura de Ingreso (Venta Original)
                    </div>
                    <div class="nd-step-body">
                        <p class="text-muted small mb-3">
                            Seleccione la factura timbrada <span class="badge bg-dark">PUE</span> sobre la cual aplicará el cargo adicional.
                        </p>
                        <div class="table-responsive nd-factura-table">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="sticky-top">
                                    <tr>
                                        <th></th>
                                        <th>Folio</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Método</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($facturasTimbradas as $f)
                                        <tr class="factura-row" data-id="{{ $f->id }}" onclick="seleccionarFactura({{ $f->id }})">
                                            <td><input type="radio" name="factura_sel" value="{{ $f->id }}" class="form-check-input"></td>
                                            <td><strong>{{ $f->folio }}</strong></td>
                                            <td>{{ $f->date ? \Carbon\Carbon::parse($f->date)->format('d/m/Y') : '-' }}</td>
                                            <td>
                                                <div class="text-truncate" style="max-width:130px" title="{{ $f->reciver_nombre }}">{{ $f->reciver_nombre }}</div>
                                                <small class="text-muted">{{ $f->reciver_rfc }}</small>
                                            </td>
                                            <td><span class="badge bg-dark">{{ $f->metodo_pago }}</span></td>
                                            <td class="text-end">${{ number_format($f->total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No hay facturas timbradas PUE disponibles.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="nd-flow-arrow"><i class="fa-solid fa-arrow-down"></i></div>

                {{-- PASO 2 --}}
                <div class="nd-step" id="pasoDeuda" style="opacity:.5;pointer-events:none">
                    <div class="nd-step-header">
                        <span class="nd-step-num">2</span>
                        ¿Cambio en la deuda?
                    </div>
                    <div class="nd-step-body">
                        <div id="resumenFactura" class="alert alert-light border d-none mb-3"></div>
                        <div class="d-flex gap-3 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary" onclick="sinCambioDeuda()">
                                <i class="fa-solid fa-ban me-1"></i> No hay cambio
                            </button>
                            <button type="button" class="btn btn-baseColor" onclick="hayCambioDeuda()">
                                <i class="fa-solid fa-arrow-trend-up me-1"></i> Sí, aumenta la deuda
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel lateral --}}
        <div class="col-lg-6">
            <div class="nd-step nd-top-card">
                <div class="nd-step-header">
                    <i class="fa-solid fa-scale-balanced"></i>
                    Trilogía de control de saldos
                </div>
                <div class="nd-step-body">
                    <div class="trilogia-item">
                        <span class="badge bg-dark">I</span>
                        <div><strong>Factura Original</strong><br><small class="text-muted">CFDI Ingreso — crea la deuda (+)</small></div>
                    </div>
                    <div class="trilogia-item">
                        <span class="badge bg-secondary">I</span>
                        <div><strong>Nota de Débito</strong><br><small class="text-muted">CFDI Ingreso — aumenta saldo (+)</small></div>
                    </div>
                    <div class="trilogia-item">
                        <span class="badge bg-success">E</span>
                        <div><strong>Nota de Crédito</strong><br><small class="text-muted">CFDI Egreso — disminuye saldo (−)</small></div>
                    </div>

                    <hr>
                    <div class="small">
                        <div class="fw-semibold mb-2">Cuándo usar nota de débito</div>
                        <ul class="ps-3 mb-0">
                            <li>Error de facturación a favor del vendedor</li>
                            <li>Intereses o recargos por mora</li>
                            <li>Gastos de envío o logística extras</li>
                        </ul>
                    </div>

                    <div class="border rounded p-3 bg-light mt-auto small">
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
            <div class="nd-flow-arrow" id="flechaTipo" style="display:none"><i class="fa-solid fa-arrow-down"></i></div>

            {{-- PASO 3: Tipo documento --}}
            <div class="nd-step" id="pasoTipo" style="display:none">
                <div class="nd-step-header">
                    <span class="nd-step-num">3</span>
                    Tipo de nota de débito
                </div>
                <div class="nd-step-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="w-100 border rounded p-3 tipo-card" id="cardComercial">
                                <input type="radio" name="tipo_documento" value="comercial" class="form-check-input me-2" onchange="seleccionarTipo()">
                                <strong>Débito Comercial</strong>
                                <div class="small text-muted mt-1">Documento interno. Sin validez fiscal ante el SAT. Agiliza negociaciones.</div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="w-100 border rounded p-3 tipo-card" id="cardFiscal">
                                <input type="radio" name="tipo_documento" value="fiscal" class="form-check-input me-2" onchange="seleccionarTipo()">
                                <strong>Débito Fiscal (CFDI Ingreso)</strong>
                                <div class="small text-muted mt-1">Requiere timbrado. Tipo <strong>I</strong> — suma saldo para ISR/IVA.</div>
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-danger small mt-3 mb-0">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        <strong>¡Ojo con el SAT!</strong> Si modifica flujo de dinero o IVA, debe timbrar un CFDI fiscal.
                    </div>
                </div>
            </div>

            <div class="nd-flow-arrow" id="flechaMotivo" style="display:none"><i class="fa-solid fa-arrow-down"></i></div>

            {{-- PASO 4: Motivo --}}
            <div class="nd-step" id="pasoMotivo" style="display:none">
                <div class="nd-step-header">
                    <span class="nd-step-num">4</span>
                    Motivo del cargo adicional
                </div>
                <div class="nd-step-body">
                    <div class="row g-2">
                        @foreach($motivos as $key => $motivo)
                            <div class="col-md-6">
                                <label class="w-100 border rounded p-3 d-flex gap-2 align-items-start" style="cursor:pointer">
                                    <input type="radio" name="motivo" value="{{ $key }}" class="form-check-input mt-1" onchange="seleccionarMotivo()">
                                    <div>
                                        <strong>{{ $motivo['label'] }}</strong>
                                        <div class="small text-muted">{{ $motivo['descripcion'] }}</div>
                                        @if($motivo['tipo_relacion'])
                                            <span class="badge bg-secondary mt-1">Relación SAT fiscal: {{ $motivo['tipo_relacion'] }}</span>
                                        @endif
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="nd-flow-arrow" id="flechaMonto" style="display:none"><i class="fa-solid fa-arrow-down"></i></div>

            {{-- PASO 5: Monto --}}
            <div class="nd-step" id="pasoMonto" style="display:none">
                <div class="nd-step-header">
                    <span class="nd-step-num">5</span>
                    Monto del cargo adicional
                </div>
                <div class="nd-step-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Monto adicional (con IVA)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" id="montoNota" class="form-control" step="0.01" min="0.01" oninput="actualizarResumen()">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Descripción adicional (opcional)</label>
                            <textarea id="descripcionAdicional" class="form-control" rows="2" maxlength="500" oninput="actualizarResumen()"></textarea>
                        </div>
                    </div>
                    <div id="resumenEmision" class="alert alert-warning mt-3 mb-0 d-none"></div>
                </div>
            </div>

            <div class="nd-flow-arrow" id="flechaEmitir" style="display:none"><i class="fa-solid fa-arrow-down"></i></div>

            {{-- PASO 6: Emitir --}}
            <div class="nd-step" id="pasoEmitir" style="display:none">
                <div class="nd-step-header">
                    <span class="nd-step-num">6</span>
                    Emisión y registro
                </div>
                <div class="nd-step-body">
                    @if($permisoGestion === 'gestion_notas_debito')
                        <button type="button" class="btn btn-baseColor btn-lg" id="btnEmitir" onclick="emitirNotaDebito()">
                            <i class="fa-solid fa-file-circle-plus me-2"></i>
                            <span id="btnEmitirTexto">Registrar Nota de Débito</span>
                        </button>
                    @else
                        <div class="alert alert-warning mb-0">No tiene permisos para emitir notas de débito.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Historial --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="nd-step">
                <div class="nd-step-header">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Notas de débito registradas
                </div>
                <div class="nd-step-body">
                    <div class="table-responsive">
                        <table class="table table-stripped table-hover display" id="table">
                            <thead>
                                <tr>
                                    <th class="text-center fw-bold text-truncate">Acciones</th>
                                    <th class="text-center fw-bold text-truncate">Folio</th>
                                    <th class="text-center fw-bold text-truncate">Tipo</th>
                                    <th class="text-center fw-bold text-truncate">Factura origen</th>
                                    <th class="text-center fw-bold text-truncate">Motivo</th>
                                    <th class="text-center fw-bold text-truncate">Cliente</th>
                                    <th class="text-center fw-bold text-truncate">Total</th>
                                    <th class="text-center fw-bold text-truncate">Estado</th>
                                    <th class="text-center fw-bold text-truncate">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notasDebito as $nd)
                                    <tr>
                                        <td class="text-center text-nowrap">
                                            @if($nd->estado === 'Timbrada' && $nd->facturama_id)
                                                <a href="{{ route('notasDebito.ver', $nd->id) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   title="Ver nota de débito (CFDI I)">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="{{ route('notasDebito.pdf', $nd->id) }}"
                                                   class="btn btn-sm btn-outline-danger"
                                                   title="Descargar PDF nota de débito">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                </a>
                                            @endif
                                            @if(!empty($nd->id_factura_original))
                                                <a href="{{ route('facturas.ver', $nd->id_factura_original) }}"
                                                   class="btn btn-sm btn-outline-secondary"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   title="Ver factura original ({{ $nd->folio_factura }})">
                                                    <i class="fa-solid fa-file-invoice"></i>
                                                </a>
                                            @endif
                                        </td>
                                        <td class="text-center fw-normal">{{ $nd->folio_nota ?? '-' }}</td>
                                        <td class="text-center">
                                            @if($nd->tipo_documento === 'fiscal')
                                                <span class="badge bg-warning text-dark">Fiscal</span>
                                            @else
                                                <span class="badge bg-secondary">Comercial</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-normal">{{ $nd->folio_factura }}</td>
                                        <td class="text-center fw-normal">{{ $motivos[$nd->motivo]['label'] ?? $nd->motivo }}</td>
                                        <td class="text-start fw-normal">{{ $nd->receptor_nombre }}</td>
                                        <td class="text-end text-success fw-semibold">+${{ number_format($nd->total, 2) }}</td>
                                        <td class="text-center">
                                            @if($nd->estado === 'Timbrada')
                                                <span class="badge bg-success">Timbrada</span>
                                            @elseif($nd->estado === 'Registrada')
                                                <span class="badge bg-info text-dark">Comercial</span>
                                            @elseif($nd->estado === 'Error')
                                                <span class="badge bg-danger">Error</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pendiente</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-normal">{{ $nd->created_at ? $nd->created_at->format('d/m/Y H:i') : '-' }}</td>
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

        const paso = document.getElementById('pasoDeuda');
        paso.style.opacity = '1';
        paso.style.pointerEvents = 'auto';

        try {
            const res = await fetch(`/Tesoreria/NotasDebito/factura/${id}`);
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
                Total original: <strong>$${fmt(f.total)}</strong> |
                Débitos: <span class="text-success">+$${fmt(data.notas_debito_aplicadas)}</span> |
                Créditos: <span class="text-danger">-$${fmt(data.notas_credito_aplicadas)}</span><br>
                Saldo actual estimado: <strong class="text-warning">$${fmt(data.saldo_actual)}</strong>
            `;
        } catch (e) {
            Swal.fire('Error', 'No se pudo cargar la factura', 'error');
        }
    }

    function resetFlujo() {
        ['pasoTipo','pasoMotivo','pasoMonto','pasoEmitir'].forEach(id => document.getElementById(id).style.display = 'none');
        ['flechaTipo','flechaMotivo','flechaMonto','flechaEmitir'].forEach(id => document.getElementById(id).style.display = 'none');
        document.querySelectorAll('input[name=motivo],input[name=tipo_documento]').forEach(r => r.checked = false);
        document.getElementById('cardComercial')?.classList.remove('active');
        document.getElementById('cardFiscal')?.classList.remove('active');
        document.getElementById('resumenEmision')?.classList.add('d-none');
        ocultarReceptorFiscal();
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

    function sinCambioDeuda() {
        Swal.fire({ icon: 'info', title: 'Sin cambio', text: 'La deuda se mantiene sin modificación.', timer: 3000, showConfirmButton: false });
        resetFlujo();
    }

    function hayCambioDeuda() {
        document.getElementById('pasoTipo').style.display = 'block';
        document.getElementById('flechaTipo').style.display = 'block';
    }

    function seleccionarTipo() {
        document.getElementById('cardComercial').classList.toggle('active', document.querySelector('input[name=tipo_documento][value=comercial]')?.checked);
        document.getElementById('cardFiscal').classList.toggle('active', document.querySelector('input[name=tipo_documento][value=fiscal]')?.checked);
        document.getElementById('pasoMotivo').style.display = 'block';
        document.getElementById('flechaMotivo').style.display = 'block';
        actualizarBotonEmitir();
    }

    function seleccionarMotivo() {
        document.getElementById('pasoMonto').style.display = 'block';
        document.getElementById('flechaMonto').style.display = 'block';
        document.getElementById('pasoEmitir').style.display = 'block';
        document.getElementById('flechaEmitir').style.display = 'block';
        actualizarResumen();
    }

    function actualizarBotonEmitir() {
        const tipo = document.querySelector('input[name=tipo_documento]:checked')?.value;
        const txt = document.getElementById('btnEmitirTexto');
        if (!txt) return;
        txt.textContent = tipo === 'fiscal' ? 'Timbrar Nota de Débito Fiscal' : 'Registrar Nota de Débito Comercial';
    }

    function actualizarResumen() {
        const motivoInput = document.querySelector('input[name=motivo]:checked');
        const tipoInput = document.querySelector('input[name=tipo_documento]:checked');
        const monto = parseFloat(document.getElementById('montoNota').value) || 0;
        const resumen = document.getElementById('resumenEmision');

        if (!motivoInput || !tipoInput || monto <= 0) {
            resumen.classList.add('d-none');
            return;
        }

        const motivo = MOTIVOS[motivoInput.value];
        const subtotal = (monto / 1.16).toFixed(2);
        const iva = (monto - subtotal).toFixed(2);
        const esFiscal = tipoInput.value === 'fiscal';

        resumen.classList.remove('d-none');
        resumen.innerHTML = `
            <strong>Resumen</strong> — ${esFiscal ? 'CFDI Ingreso (I) timbrado' : 'Documento comercial interno'}<br>
            Motivo: ${motivo.label}<br>
            ${esFiscal ? `Relación SAT: ${motivo.tipo_relacion} | UUID: <code>${datosFactura?.factura?.uuid ?? ''}</code><br>` : ''}
            Efecto: <strong class="text-success">+$${monto.toFixed(2)}</strong> al saldo del cliente<br>
            Subtotal: $${subtotal} | IVA: $${iva}
        `;
        actualizarBotonEmitir();
    }

    async function emitirNotaDebito() {
        const motivoInput = document.querySelector('input[name=motivo]:checked');
        const tipoInput = document.querySelector('input[name=tipo_documento]:checked');
        const monto = parseFloat(document.getElementById('montoNota').value);

        if (!facturaSeleccionada || !motivoInput || !tipoInput) {
            Swal.fire('Datos incompletos', 'Complete todos los pasos del flujo.', 'warning');
            return;
        }
        if (!monto || monto <= 0) {
            Swal.fire('Monto inválido', 'Ingrese un monto mayor a cero.', 'warning');
            return;
        }

        const esFiscal = tipoInput.value === 'fiscal';
        const confirm = await Swal.fire({
            title: esFiscal ? '¿Timbrar nota de débito fiscal?' : '¿Registrar nota de débito comercial?',
            html: `Se aplicará un cargo de <strong>+$${monto.toFixed(2)}</strong> al saldo del cliente.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirm.isConfirmed) return;

        const btn = document.getElementById('btnEmitir');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

        try {
            const res = await fetch('{{ route("notasDebito.emitir") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    factura_id: facturaSeleccionada,
                    motivo: motivoInput.value,
                    tipo_documento: tipoInput.value,
                    monto: monto,
                    descripcion_adicional: document.getElementById('descripcionAdicional').value
                })
            });
            const data = await res.json();
            if (data.success) {
                await Swal.fire('¡Éxito!', data.message, 'success');
                window.location.reload();
            } else {
                mostrarErrorFacturama(data.message || 'No se pudo procesar la nota de débito');
            }
        } catch (e) {
            Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-file-circle-plus me-2"></i><span id="btnEmitirTexto">' + (document.querySelector('input[name=tipo_documento]:checked')?.value === 'fiscal' ? 'Timbrar Nota de Débito Fiscal' : 'Registrar Nota de Débito Comercial') + '</span>';
        }
    }

    function fmt(n) {
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
</script>
<script src="{{ asset('js/table.js') }}"></script>
@endsection
