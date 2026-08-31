@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .sust-step-num {
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
        margin-right: .5rem;
    }
    .sust-flow-arrow {
        text-align: center;
        color: var(--text-muted, #6b7280);
        font-size: 1.25rem;
        margin: .25rem 0;
    }
    .motivo-card {
        border: 1px solid var(--card-border, rgba(17, 24, 39, 0.09));
        border-radius: 10px;
        padding: .75rem;
        height: 100%;
        background: #f8f9fa;
    }
    .motivo-card.active {
        border-color: var(--primary-color, #111827);
        background: var(--primary-soft-bg, #f3f4f6);
        box-shadow: 0 0 0 1px rgba(17, 24, 39, 0.12);
    }
    .motivo-card .clave {
        font-weight: 700;
        color: var(--secondary-color, #374151);
    }
    .proceso-step {
        display: flex;
        gap: .75rem;
        padding: .65rem 0;
        border-bottom: 1px dashed rgba(17, 24, 39, 0.1);
    }
    .proceso-step:last-child { border-bottom: none; }
    .proceso-step .n {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--primary-color, #111827);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .75rem;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .check-sat {
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        padding: .45rem 0;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-right-left"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Sustitución de CFDI</h2>
                        <p class="text-muted mb-0">Motivo 01: comprobante con errores con relación. Primero timbrar el sustituto (04), luego cancelar el original (01).</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-baseColor-light fs-7 mb-2" href="{{ route('facturas.cancelacion', $facturaOriginal->id) }}">
                        <i class="fa-solid fa-arrow-left"></i> Cambiar motivo
                    </a>
                    <a class="btn btn-baseColor-light fs-7 mb-2" href="{{ route('facturas.index') }}">
                        <i class="fa-solid fa-list"></i> Facturas
                    </a>
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
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 alert-dismissible fade show">
            <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!($infoSat['dentro_facilidad'] ?? true))
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            <strong>Fuera de plazo:</strong> la factura es del {{ $infoSat['anio_emision'] ?? '—' }}.
            El límite legal fue {{ $infoSat['limite_legal'] ?? '—' }} y la facilidad administrativa
            (marzo del año siguiente) venció el {{ $infoSat['limite_facilidad'] ?? '—' }}.
            Cancelar fuera de plazo puede generar multas del 5% al 10%.
        </div>
    @elseif(!($infoSat['dentro_plazo_legal'] ?? true))
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-calendar-days me-1"></i>
            Estás en la <strong>extensión de facilidad administrativa</strong> (hasta {{ $infoSat['limite_facilidad'] ?? '—' }}).
            El plazo legal del año fiscal {{ $infoSat['anio_emision'] ?? '' }} ya venció ({{ $infoSat['limite_legal'] ?? '—' }}).
        </div>
    @endif

    @if(!empty($resultadoSustitucion))
        <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
            <div class="card-header bg-body border-0 pb-0">
                <h5 class="text-success mb-1">
                    <i class="fa-solid fa-circle-check me-2"></i>
                    Resultado del proceso SAT (01 + 04)
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100 bg-light">
                            <div class="fw-semibold mb-2">
                                <span class="badge bg-dark me-1">Paso 1</span>
                                CFDI sustituto (relación 04)
                            </div>
                            <div>Folio: <strong>{{ $resultadoSustitucion['folio_sustituto'] ?? '—' }}</strong></div>
                            <div class="small text-muted mt-1">UUID: <code>{{ $resultadoSustitucion['uuid_sustituto'] ?? '—' }}</code></div>
                            <span class="badge bg-success mt-2">Timbrado</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100 bg-light">
                            <div class="fw-semibold mb-2">
                                <span class="badge bg-danger me-1">Paso 2</span>
                                CFDI original (motivo 01)
                            </div>
                            <div>Folio: <strong>{{ $resultadoSustitucion['folio_original'] ?? '—' }}</strong></div>
                            <div class="small text-muted mt-1">UUID: <code>{{ $resultadoSustitucion['uuid_original'] ?? '—' }}</code></div>
                            @if($resultadoSustitucion['cancelacion_ok'] ?? false)
                                <span class="badge bg-danger mt-2">Cancelado / solicitud enviada (motivo 01)</span>
                                <div class="small text-muted mt-2">
                                    El cliente puede tener 3 días hábiles para aceptar o rechazar en Buzón Tributario
                                    (salvo excepciones: ≤$1,000, dentro de 24 h, etc.).
                                </div>
                            @else
                                <span class="badge bg-warning text-dark mt-2">Cancelación pendiente</span>
                                @if(!empty($resultadoSustitucion['mensaje_cancelacion']))
                                    <div class="small text-danger mt-2">{{ $resultadoSustitucion['mensaje_cancelacion'] }}</div>
                                @endif
                                <div class="small text-muted mt-2">
                                    El sustituto ya está timbrado. Puede reintentar la cancelación del original con motivo 01
                                    indicando el UUID del sustituto.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('facturas.index') }}" class="btn btn-baseColor">
                        <i class="fa-solid fa-list me-1"></i> Ir a facturas emitidas
                    </a>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
                <div class="card-header bg-body border-0 pb-0">
                    <h5 class="text-secondary mb-1">
                        <span class="sust-step-num">1</span>
                        CFDI original a sustituir
                    </h5>
                    <p class="text-muted fs-8 mb-0">Comprobante que se cancelará con motivo 01 tras timbrar el sustituto.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3"><strong>Folio:</strong> {{ $facturaOriginal->folio ?? '—' }}</div>
                        <div class="col-md-3"><strong>Fecha:</strong> {{ $facturaOriginal->date ? \Carbon\Carbon::parse($facturaOriginal->date)->format('d/m/Y') : '—' }}</div>
                        <div class="col-md-3"><strong>Total:</strong> ${{ number_format($facturaOriginal->total ?? 0, 2) }}</div>
                        <div class="col-md-3"><strong>Método:</strong> {{ $facturaOriginal->metodo_pago ?? '—' }}</div>
                        <div class="col-md-6"><strong>Cliente:</strong> {{ $facturaOriginal->reciver_nombre ?? '—' }}</div>
                        <div class="col-md-6"><strong>RFC:</strong> {{ $facturaOriginal->reciver_rfc ?? '—' }}</div>
                        <div class="col-md-6"><strong>Uso CFDI actual:</strong> {{ $datos['receptor']['uso_cfdi'] ?? '—' }}</div>
                        <div class="col-12"><strong>UUID:</strong> <code>{{ $facturaOriginal->Uuid ?? '—' }}</code></div>
                    </div>
                    <div class="alert alert-info border-0 shadow-sm rounded-4 small mt-3 mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Este CFDI se cancelará con motivo <strong>01</strong> <em>después</em> de timbrar el sustituto,
                        vinculándolo al UUID de la nueva factura.
                    </div>
                </div>
            </div>

            <div class="sust-flow-arrow"><i class="fa-solid fa-arrow-down"></i></div>

            @if(empty($resultadoSustitucion) || !($resultadoSustitucion['timbrado_ok'] ?? false))
            <form id="formSustitucion" method="POST" action="{{ route('facturas.sustitucion.procesar', $facturaOriginal->id) }}">
                @csrf
                <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
                    <div class="card-header bg-body border-0 pb-0">
                        <h5 class="text-secondary mb-1">
                            <span class="sust-step-num">2</span>
                            Timbrar CFDI sustituto (relación SAT 04)
                        </h5>
                        <p class="text-muted fs-8 mb-0">Corrija los datos fiscales del receptor y confirme conceptos.</p>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="emisor_nombre" value="{{ $datos['emisor']['nombre'] ?? '' }}">
                        <input type="hidden" name="emisor_rfc" value="{{ $datos['emisor']['rfc'] ?? '' }}">
                        <input type="hidden" name="emisor_lugar_expedicion" value="{{ $datos['emisor']['lugar_expedicion'] ?? '' }}">
                        <input type="hidden" name="emisor_regimen_fiscal" value="{{ $datos['emisor']['regimen_fiscal'] ?? '601' }}">
                        <input type="hidden" name="conceptos" id="inputConceptos">

                        <div class="border rounded-4 p-3 mb-4 bg-light">
                            <div class="fw-semibold mb-3">
                                <i class="fa-solid fa-user-check me-1"></i>
                                Datos fiscales del receptor (corrija errores aquí)
                            </div>
                            <div class="alert alert-warning border-0 shadow-sm rounded-4 small">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                Si el error es el <strong>RFC de otro cliente</strong>, <strong>no use sustitución (01)</strong>.
                                En ese caso cancele con motivo <strong>02</strong> (sin relación) y emita una factura nueva independiente.
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre / Razón social *</label>
                                    <input type="text" name="receptor_nombre" class="form-control"
                                           value="{{ $datos['receptor']['nombre'] ?? $facturaOriginal->reciver_nombre }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">RFC *</label>
                                    <input type="text" name="receptor_rfc" id="receptor_rfc" class="form-control text-uppercase"
                                           value="{{ $datos['receptor']['rfc'] ?? $facturaOriginal->reciver_rfc }}"
                                           maxlength="13" required>
                                    <small class="text-muted">RFC original: {{ $facturaOriginal->reciver_rfc ?? '—' }}</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">C.P. fiscal *</label>
                                    <input type="text" name="receptor_codigo_postal" class="form-control"
                                           value="{{ $datos['receptor']['codigo_postal'] ?? '' }}" maxlength="5" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" name="receptor_direccion" class="form-control"
                                           value="{{ $datos['receptor']['direccion'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Régimen fiscal receptor</label>
                                    <input type="text" name="receptor_regimen_fiscal" class="form-control"
                                           value="{{ $datos['receptor']['regimen_fiscal'] ?? '601 - General de Ley Personas Morales' }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="uso_cfdi" class="form-label">Uso de CFDI *</label>
                                    <select name="receptor_uso_cfdi" id="uso_cfdi" class="form-select" required>
                                        <option value="">Seleccionar uso de CFDI</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Folio sustituto</label>
                                <input type="text" name="folio" class="form-control" value="{{ $datos['folio_sustituto'] ?? '' }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Método de pago</label>
                                <select name="metodo_pago" class="form-select">
                                    <option value="PUE" @selected(($datos['pago']['metodo_pago'] ?? '') === 'PUE')>PUE</option>
                                    <option value="PPD" @selected(($datos['pago']['metodo_pago'] ?? '') === 'PPD')>PPD</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Forma de pago</label>
                                <select name="forma_pago" class="form-select">
                                    @foreach($formasPago as $codigo => $label)
                                        <option value="{{ $codigo }}" @selected(str_starts_with($datos['pago']['forma_pago'] ?? '', $codigo))>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo relación SAT</label>
                                <input type="text" class="form-control" value="04 - Sustitución de CFDI previos" readonly>
                            </div>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle" id="tablaConceptos">
                                <thead>
                                    <tr>
                                        <th>Clave SAT</th>
                                        <th>Descripción</th>
                                        <th style="width:90px">Cant.</th>
                                        <th style="width:90px">Unidad</th>
                                        <th style="width:110px">Precio</th>
                                        <th style="width:110px">Importe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($datos['conceptos'] as $i => $c)
                                        <tr>
                                            <td><input type="text" class="form-control form-control-sm c-producto" value="{{ $c['producto'] ?? '' }}"></td>
                                            <td><input type="text" class="form-control form-control-sm c-concepto" value="{{ $c['concepto'] ?? '' }}"></td>
                                            <td><input type="number" class="form-control form-control-sm c-cantidad" step="0.01" min="0.01" value="{{ $c['cantidad'] ?? 1 }}"></td>
                                            <td><input type="text" class="form-control form-control-sm c-unidad" value="{{ $c['unidad'] ?? 'H87' }}"></td>
                                            <td><input type="number" class="form-control form-control-sm c-precio" step="0.01" min="0.01" value="{{ str_replace(',', '', $c['precio'] ?? '0') }}"></td>
                                            <td class="c-importe text-end fw-semibold">${{ number_format((float) str_replace(',', '', $c['importe'] ?? 0), 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="5" class="text-end fw-semibold">Subtotal</td>
                                        <td class="text-end fw-semibold" id="lblSubtotal">${{ $datos['totales']['subtotal'] ?? '0.00' }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end">IVA (16%)</td>
                                        <td class="text-end" id="lblIva">${{ $datos['totales']['iva'] ?? '0.00' }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">Total</td>
                                        <td class="text-end fw-bold text-success" id="lblTotal">${{ $datos['totales']['total'] ?? '0.00' }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="sust-flow-arrow"><i class="fa-solid fa-arrow-down"></i></div>

                <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
                    <div class="card-header bg-body border-0 pb-0">
                        <h5 class="text-secondary mb-1">
                            <span class="sust-step-num">3</span>
                            Confirmar y ejecutar (orden oficial SAT)
                        </h5>
                        <p class="text-muted fs-8 mb-0">Marque las confirmaciones antes de procesar.</p>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="check-sat">
                                <input type="checkbox" class="form-check-input mt-1" id="chkOrden" required>
                                <label for="chkOrden" class="small mb-0">
                                    Entiendo el orden correcto: <strong>1)</strong> timbrar el CFDI nuevo con relación
                                    <strong>04</strong> al UUID original; <strong>2)</strong> cancelar el original con motivo
                                    <strong>01</strong> vinculándolo al UUID del sustituto.
                                </label>
                            </div>
                            <div class="check-sat">
                                <input type="checkbox" class="form-check-input mt-1" id="chkMotivo" required>
                                <label for="chkMotivo" class="small mb-0">
                                    Confirmo que el error <strong>no cambia el cliente</strong> (mismo RFC / misma operación).
                                    Si fuera otro RFC, usaría motivo <strong>02</strong> sin relación.
                                </label>
                            </div>
                            <div class="check-sat">
                                <input type="checkbox" class="form-check-input mt-1" id="chkAceptacion" required>
                                <label for="chkAceptacion" class="small mb-0">
                                    Sé que, salvo excepciones (≤ $1,000, dentro de 24 h, nómina/egreso, público en general, etc.),
                                    el receptor puede tener <strong>3 días hábiles</strong> para aceptar o rechazar la cancelación
                                    en su Buzón Tributario.
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            @if($infoSat['cancelacion_directa_24h'] ?? false)
                                <span class="badge bg-success">Dentro de 24 h → posible cancelación directa</span>
                            @endif
                            @if($infoSat['monto_bajo_umbral'] ?? false)
                                <span class="badge bg-success">Monto ≤ $1,000 → sin autorización del cliente</span>
                            @else
                                <span class="badge bg-secondary">Monto &gt; $1,000 → puede requerir aceptación</span>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-baseColor btn-lg" id="btnProcesar">
                            <i class="fa-solid fa-bolt me-2"></i>
                            1. Timbrar sustituto (04) → 2. Cancelar original (01)
                        </button>
                    </div>
                </div>
            </form>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5 sticky-top" style="top:1rem">
                <div class="card-header bg-body border-0 pb-0">
                    <h5 class="text-secondary mb-1">
                        <i class="fa-solid fa-scale-balanced me-2"></i>
                        Guía SAT — Cancelación / Sustitución
                    </h5>
                </div>
                <div class="card-body small">
                    <div class="fw-semibold mb-2">Proceso correcto (motivo 01)</div>
                    <div class="proceso-step">
                        <span class="n">1</span>
                        <div>Generar la factura <strong>correcta</strong> y relacionarla con la anterior (tipo <strong>04</strong>).</div>
                    </div>
                    <div class="proceso-step">
                        <span class="n">2</span>
                        <div>Cancelar la factura vieja con clave <strong>01</strong>, vinculándola al UUID/folio de la nueva.</div>
                    </div>

                    <hr>

                    <div class="fw-semibold mb-2">Los 4 motivos oficiales</div>
                    <div class="row g-2 mb-3">
                        <div class="col-12">
                            <div class="motivo-card active">
                                <div class="clave">01 — Con relación</div>
                                <div class="text-muted">Error en la factura; la operación sí se realiza. Requiere sustituto previo.</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="motivo-card">
                                <div class="clave">02 — Sin relación</div>
                                <div class="text-muted">Error sin sustitución inmediata, o RFC de otro cliente.</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="motivo-card">
                                <div class="clave">03 — Operación no realizada</div>
                                <div class="text-muted">Cliente canceló / no se prestó el servicio. Usar con cuidado (riesgo SAT).</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="motivo-card">
                                <div class="clave">04 — Factura global</div>
                                <div class="text-muted">Venta al público general que luego pide factura individual.</div>
                            </div>
                        </div>
                    </div>

                    <div class="fw-semibold mb-1">Plazos</div>
                    <ul class="ps-3 mb-3 text-muted">
                        <li>Límite legal: último día del año fiscal de emisión ({{ $infoSat['limite_legal'] ?? '—' }}).</li>
                        <li>Facilidad RMF: hasta declaración anual (marzo PM / abril PF) → {{ $infoSat['limite_facilidad'] ?? '—' }} (PM).</li>
                    </ul>

                    <div class="fw-semibold mb-1">Aceptación del cliente</div>
                    <ul class="ps-3 mb-3 text-muted">
                        <li>Regla general: 3 días hábiles en Buzón Tributario.</li>
                        <li>Sin permiso: ≤$1,000; nómina/egreso/traslado; Mis Cuentas; público en general / extranjero; dentro de 24 h.</li>
                    </ul>

                    <div class="alert alert-danger border-0 shadow-sm rounded-4 small mb-0">
                        <strong>Riesgos:</strong> multas 5%–10%, no deducibilidad para el cliente, o restricción del CSD en casos graves.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const usoCfdiActual = @json($datos['receptor']['uso_cfdi'] ?? 'G03 - Gastos en general');
    const rfcOriginal = @json(strtoupper(trim($facturaOriginal->reciver_rfc ?? '')));

    const usosCfdiPorDefecto = [
        { Value: 'G01', Name: 'Adquisición de mercancías' },
        { Value: 'G02', Name: 'Devoluciones, descuentos o bonificaciones' },
        { Value: 'G03', Name: 'Gastos en general' },
        { Value: 'I01', Name: 'Construcciones' },
        { Value: 'I02', Name: 'Mobilario y equipo de oficina por inversiones' },
        { Value: 'I03', Name: 'Equipo de transporte' },
        { Value: 'I04', Name: 'Equipo de computo y accesorios' },
        { Value: 'I08', Name: 'Otra maquinaria y equipo' },
        { Value: 'D01', Name: 'Honorarios médicos, dentales y gastos hospitalarios' },
        { Value: 'D02', Name: 'Gastos médicos por incapacidad o discapacidad' },
        { Value: 'D03', Name: 'Gastos funerales' },
        { Value: 'D04', Name: 'Donativos' },
        { Value: 'D10', Name: 'Por definir' },
        { Value: 'S01', Name: 'Sin efectos fiscales' },
        { Value: 'CP01', Name: 'Pagos' },
        { Value: 'CN01', Name: 'Nómina' },
    ];

    function llenarSelectUsoCfdi(items) {
        const select = document.getElementById('uso_cfdi');
        if (!select) return;
        const valorPrevio = select.value || usoCfdiActual;
        select.innerHTML = '<option value="">Seleccionar uso de CFDI</option>';

        items.forEach(function (uso) {
            const codigo = uso.Value || uso.value || '';
            const nombre = uso.Name || uso.name || '';
            if (!codigo) return;

            const option = document.createElement('option');
            option.value = `${codigo} - ${nombre}`;
            option.textContent = `${codigo} - ${nombre}`;

            if (option.value === valorPrevio || codigo === valorPrevio.split(' - ')[0]) {
                option.selected = true;
            }

            select.appendChild(option);
        });
    }

    function cargarUsosCfdi(keyword = 'G') {
        const select = document.getElementById('uso_cfdi');
        if (!select) return;
        select.innerHTML = '<option value="">Cargando usos de CFDI...</option>';

        fetch(`{{ route('buscar.uso.cfdi') }}?keyword=${encodeURIComponent(keyword)}`)
            .then(function (response) {
                if (!response.ok) throw new Error('Error al consultar catálogo');
                return response.json();
            })
            .then(function (data) {
                if (Array.isArray(data) && data.length > 0) {
                    llenarSelectUsoCfdi(data);
                } else {
                    llenarSelectUsoCfdi(usosCfdiPorDefecto);
                }
            })
            .catch(function () {
                llenarSelectUsoCfdi(usosCfdiPorDefecto);
            });
    }

    cargarUsosCfdi('G');

    function recalcularTotales() {
        let subtotal = 0;
        document.querySelectorAll('#tablaConceptos tbody tr').forEach(function (row) {
            const cant = parseFloat(row.querySelector('.c-cantidad')?.value) || 0;
            const precio = parseFloat(row.querySelector('.c-precio')?.value) || 0;
            const importe = cant * precio;
            subtotal += importe;
            const cell = row.querySelector('.c-importe');
            if (cell) cell.textContent = '$' + importe.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        });
        const iva = subtotal * 0.16;
        const total = subtotal + iva;
        document.getElementById('lblSubtotal').textContent = '$' + subtotal.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('lblIva').textContent = '$' + iva.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('lblTotal').textContent = '$' + total.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    document.querySelectorAll('#tablaConceptos .c-cantidad, #tablaConceptos .c-precio').forEach(function (el) {
        el.addEventListener('input', recalcularTotales);
    });

    document.getElementById('formSustitucion')?.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (!document.getElementById('chkOrden')?.checked
            || !document.getElementById('chkMotivo')?.checked
            || !document.getElementById('chkAceptacion')?.checked) {
            Swal.fire('Confirmaciones pendientes', 'Marque las 3 casillas del proceso SAT antes de continuar.', 'warning');
            return;
        }

        const rfcNuevo = (document.getElementById('receptor_rfc')?.value || '').trim().toUpperCase();
        if (rfcOriginal && rfcNuevo && rfcNuevo !== rfcOriginal) {
            const avisoRfc = await Swal.fire({
                title: 'RFC distinto al original',
                html: `RFC original: <code>${rfcOriginal}</code><br>RFC nuevo: <code>${rfcNuevo}</code><br><br>
                       Si es <strong>otro cliente</strong>, el motivo correcto es <strong>02 (sin relación)</strong>, no sustitución 01.<br>
                       ¿Desea continuar de todas formas?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, es corrección del mismo cliente',
                cancelButtonText: 'Cancelar',
            });
            if (!avisoRfc.isConfirmed) return;
        }

        const conceptos = [];
        document.querySelectorAll('#tablaConceptos tbody tr').forEach(function (row) {
            conceptos.push({
                producto: row.querySelector('.c-producto')?.value || '',
                concepto: row.querySelector('.c-concepto')?.value || '',
                cantidad: row.querySelector('.c-cantidad')?.value || 0,
                unidad: row.querySelector('.c-unidad')?.value || 'H87',
                precio: row.querySelector('.c-precio')?.value || 0,
            });
        });

        if (!conceptos.length) {
            Swal.fire('Sin conceptos', 'Agregue al menos un concepto válido.', 'warning');
            return;
        }

        const usoCfdi = document.getElementById('uso_cfdi')?.value;
        if (!usoCfdi) {
            Swal.fire('Uso de CFDI requerido', 'Seleccione el uso de CFDI correcto para el CFDI sustituto.', 'warning');
            return;
        }

        document.getElementById('inputConceptos').value = JSON.stringify(conceptos);

        const confirm = await Swal.fire({
            title: '¿Ejecutar proceso SAT 01?',
            html: `
                <ol class="text-start small">
                    <li class="mb-2"><strong>Timbrar</strong> CFDI sustituto con relación <strong>04</strong> al UUID original.</li>
                    <li><strong>Cancelar</strong> el original con motivo <strong>01</strong> ligado al UUID del sustituto.</li>
                </ol>
                <div class="small text-muted">Uso CFDI: <strong>${usoCfdi.split(' - ')[0]}</strong></div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
        });

        if (!confirm.isConfirmed) return;

        const btn = document.getElementById('btnProcesar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>1. Timbrando… luego cancelando…';
        this.submit();
    });
</script>
@endsection
