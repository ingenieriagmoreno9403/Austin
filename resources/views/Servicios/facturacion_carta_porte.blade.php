@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@if($mensaje = Session::get('success'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: @json($mensaje), showConfirmButton: false, timer: 6000 });
});
</script>
@endif

<div class="container-fluid format_page acciones-config-page">
    @if($mensaje = Session::get('facturaerror'))
        <div class="alert alert-danger border-0 shadow-sm mb-3">
            <h5 class="alert-heading"><i class="fas fa-times-circle me-2"></i>Error al timbrar con Carta Porte</h5>
            <div>{!! $mensaje !!}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-3">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $facturaExistente = ($facturasTimbradas ?? collect())->first();
        $cp = $datos['carta_porte'] ?? [];
        $origen = $cp['origen'] ?? [];
        $destino = $cp['destino'] ?? [];
        $auto = $cp['autotransporte'] ?? [];
        $figura = $cp['figura'] ?? [];
        $estadosSat = [
            'AGU' => 'Aguascalientes', 'BCN' => 'Baja California', 'BCS' => 'Baja California Sur',
            'CAM' => 'Campeche', 'CHP' => 'Chiapas', 'CHH' => 'Chihuahua', 'CMX' => 'Ciudad de México',
            'COA' => 'Coahuila', 'COL' => 'Colima', 'DUR' => 'Durango', 'GUA' => 'Guanajuato',
            'GRO' => 'Guerrero', 'HID' => 'Hidalgo', 'JAL' => 'Jalisco', 'MEX' => 'Estado de México',
            'MIC' => 'Michoacán', 'MOR' => 'Morelos', 'NAY' => 'Nayarit', 'NLE' => 'Nuevo León',
            'OAX' => 'Oaxaca', 'PUE' => 'Puebla', 'QUE' => 'Querétaro', 'ROO' => 'Quintana Roo',
            'SLP' => 'San Luis Potosí', 'SIN' => 'Sinaloa', 'SON' => 'Sonora', 'TAB' => 'Tabasco',
            'TAM' => 'Tamaulipas', 'TLA' => 'Tlaxcala', 'VER' => 'Veracruz', 'YUC' => 'Yucatán', 'ZAC' => 'Zacatecas',
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h3 class="text-marino mb-1">
                <i class="fa-solid fa-truck-fast me-2"></i>Factura con Carta Porte 3.1
            </h3>
            <p class="text-muted mb-0 fs-8">
                @if(!empty($rutaLogistica))
                    Ruta <strong>{{ $rutaLogistica->folio }}</strong>
                    · {{ ($pedidosRuta ?? collect())->count() }} pedido(s)
                    · Total CFDI = costo de transporte
                    ${{ number_format((float) ($costoTransporte ?? 0), 2) }}
                @else
                    Pedido <strong>{{ $datos['servicio']['folio'] ?? $id }}</strong>
                @endif
                · CFDI Ingreso con complemento
                <a href="https://apisandbox.facturama.mx/guias/complementos/complemento-carta-porte-31" target="_blank" rel="noopener">CartaPorte31</a>
                (NameId 36)
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(!empty($rutaLogistica))
                <a href="{{ route('ventas.pedidos.logistica', ['ruta_id' => $rutaLogistica->id]) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver a Logística
                </a>
            @else
                <a href="{{ route('ventas.pedidos', ['vendedor_id' => auth()->id(), 'abrir_pedido' => 1, 'pedido_id' => $id]) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver al pedido
                </a>
                <a href="{{ route('facturacion', ['id' => $id, 'servicio' => 'pedido']) }}" class="btn btn-outline-primary btn-sm">
                    Facturar sin carta porte
                </a>
            @endif
        </div>
    </div>

    @if(!empty($rutaLogistica) && ($pedidosRuta ?? collect())->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-marino mb-0">
                        <i class="fa-solid fa-boxes-stacked me-1"></i>
                        Pedidos de la ruta {{ $rutaLogistica->folio }}
                    </h6>
                    <span class="badge bg-dark">
                        Costo transporte (total CFDI): ${{ number_format((float) ($costoTransporte ?? 0), 2) }}
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Productos</th>
                                <th class="text-end">Total pedido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pedidosRuta as $pRuta)
                                <tr>
                                    <td><strong>{{ $pRuta->folio ?? ('#' . $pRuta->id) }}</strong></td>
                                    <td>{{ $pRuta->cliente->nombre ?? '—' }}</td>
                                    <td class="fs-8">
                                        @forelse($pRuta->detalles as $det)
                                            {{ optional($det->producto)->nombre ?? ($det->descripcion ?: 'Producto') }}
                                            ({{ number_format((float) $det->cantidad, 2) }})@if(!$loop->last), @endif
                                        @empty
                                            —
                                        @endforelse
                                    </td>
                                    <td class="text-end">${{ number_format((float) ($pRuta->total ?? 0), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="form-text fs-8 mt-2 mb-0">
                    Los importes de conceptos abajo se ajustan para que el <strong>total del CFDI</strong> coincida con el costo de transporte.
                </div>
            </div>
        </div>
    @endif

    @if($facturaExistente)
        <div class="alert alert-info border-0 shadow-sm mb-3">
            Ya existe factura timbrada:
            <strong>{{ $facturaExistente->folio }}</strong> · UUID {{ $facturaExistente->Uuid }}
        </div>
    @endif

    <form method="POST" action="{{ route('procesar.factura.carta_porte') }}" id="formCartaPorte">
        @csrf
        <input type="hidden" name="id_servicio_enc" value="{{ $id }}">
        <input type="hidden" name="tipo_servicio" value="pedido_venta">
        <input type="hidden" name="folio" value="{{ $datos['servicio']['folio'] ?? '' }}">
        <input type="hidden" name="emisor_nombre" value="{{ $datos['emisor']['nombre'] ?? '' }}">
        <input type="hidden" name="emisor_rfc" value="{{ $datos['emisor']['rfc'] ?? '' }}">
        <input type="hidden" name="emisor_lugar_expedicion" value="{{ $datos['emisor']['lugar_expedicion'] ?? '' }}">
        <input type="hidden" name="emisor_regimen_fiscal" value="{{ $datos['emisor']['regimen_fiscal'] ?? '' }}">
        <input type="hidden" name="receptor_nombre" value="{{ $datos['receptor']['razon_social'] ?? $datos['receptor']['nombre'] ?? '' }}">
        <input type="hidden" name="receptor_rfc" value="{{ $datos['receptor']['rfc'] ?? '' }}">
        <input type="hidden" name="receptor_codigo_postal" value="{{ $datos['receptor']['codigo_postal'] ?? '' }}">
        <input type="hidden" name="receptor_calle" value="{{ $datos['receptor']['calle'] ?? '' }}">
        <input type="hidden" name="receptor_num_ext" value="{{ $datos['receptor']['numero_ext'] ?? '' }}">
        <input type="hidden" name="receptor_num_int" value="{{ $datos['receptor']['numero_int'] ?? '' }}">
        <input type="hidden" name="receptor_colonia" value="{{ $datos['receptor']['colonia'] ?? '' }}">
        <input type="hidden" name="receptor_municipio" value="{{ $datos['receptor']['municipio'] ?? '' }}">
        <input type="hidden" name="receptor_estado" value="{{ $datos['receptor']['estado'] ?? '' }}">
        <input type="hidden" name="forma_pago" value="{{ old('forma_pago', $datos['pago']['forma_pago'] ?? '03') }}">
        <input type="hidden" name="metodo_pago" value="{{ old('metodo_pago', $datos['pago']['metodo_pago'] ?? 'PUE') }}">
        <input type="hidden" name="conceptos" id="conceptos_json" value="">

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-marino">Emisor</h6>
                        <div class="fs-8"><strong>{{ $datos['emisor']['nombre'] ?? '—' }}</strong></div>
                        <div class="fs-8">RFC: {{ $datos['emisor']['rfc'] ?? '—' }}</div>
                        <div class="fs-8">CP expedición: {{ $datos['emisor']['lugar_expedicion'] ?? '—' }}</div>
                        <div class="fs-8">{{ $datos['emisor']['regimen_fiscal'] ?? '' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-marino mb-3">Receptor</h6>
                        <div class="fs-8 mb-1"><strong>{{ $datos['receptor']['razon_social'] ?? $datos['receptor']['nombre'] ?? '—' }}</strong></div>
                        <div class="fs-8 mb-1">RFC: {{ $datos['receptor']['rfc'] ?? '—' }}</div>
                        <div class="fs-8 mb-1">CP: {{ $datos['receptor']['codigo_postal'] ?? '—' }}</div>
                        <div class="fs-8 mb-3">{{ $datos['receptor']['direccion'] ?? '' }}</div>

                        <div class="mb-2">
                            <strong class="fs-8">Uso del CFDI:</strong>
                            <select class="form-select form-select-sm mt-1" id="uso_cfdi" name="receptor_uso_cfdi" required
                                data-valor-inicial="{{ old('receptor_uso_cfdi', $datos['receptor']['uso_cfdi'] ?? 'G03 - Gastos en general') }}">
                                <option value="">Seleccionar uso CFDI</option>
                                <option value="G01 - Adquisición de mercancías">G01 - Adquisición de mercancías</option>
                                <option value="G03 - Gastos en general" selected>G03 - Gastos en general</option>
                                <option value="I01 - Construcciones">I01 - Construcciones</option>
                                <option value="I08 - Otra maquinaria y equipo">I08 - Otra maquinaria y equipo</option>
                                <option value="S01 - Sin efectos fiscales">S01 - Sin efectos fiscales</option>
                                <option value="CP01 - Pagos">CP01 - Pagos</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <strong class="fs-8">Régimen Fiscal:</strong>
                            <select class="form-select form-select-sm mt-1" id="regimen_fiscal_receptor" name="receptor_regimen_fiscal" required
                                data-valor-inicial="{{ old('receptor_regimen_fiscal', $datos['receptor']['regimen_fiscal'] ?? '601 - General de Ley Personas Morales') }}">
                                <option value="">Seleccionar régimen fiscal</option>
                                <option value="601 - General de Ley Personas Morales" selected>601 - General de Ley Personas Morales</option>
                                <option value="603 - Personas Morales con Fines no Lucrativos">603 - Personas Morales con Fines no Lucrativos</option>
                                <option value="605 - Sueldos y Salarios e Ingresos Asimilados a Salarios">605 - Sueldos y Salarios e Ingresos Asimilados a Salarios</option>
                                <option value="606 - Arrendamiento">606 - Arrendamiento</option>
                                <option value="608 - Demás ingresos">608 - Demás ingresos</option>
                                <option value="612 - Personas Físicas con Actividades Empresariales y Profesionales">612 - Personas Físicas con Actividades Empresariales y Profesionales</option>
                                <option value="616 - Sin obligaciones fiscales">616 - Sin obligaciones fiscales</option>
                                <option value="621 - Incorporación Fiscal">621 - Incorporación Fiscal</option>
                                <option value="625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas">625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas</option>
                                <option value="626 - Régimen Simplificado de Confianza">626 - Régimen Simplificado de Confianza</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-marino mb-0">Conceptos / Mercancías</h6>
                    <div class="fs-8 text-muted">
                        Subtotal ${{ $datos['totales']['subtotal'] }} · IVA ${{ $datos['totales']['iva'] }} ·
                        <strong>Total ${{ $datos['totales']['total'] }}</strong>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" id="tabla-conceptos-cp">
                        <thead class="table-light">
                            <tr>
                                <th>Clave SAT</th>
                                <th>Descripción</th>
                                <th style="min-width:150px">Unidad SAT</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end">Precio</th>
                                <th class="text-end">Importe</th>
                                <th style="width:110px">Peso kg</th>
                                <th style="width:120px">Mat. peligroso</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $unidadesSatCatalogoCp = [
                                    'H87' => 'H87 — Pieza',
                                    'X4H' => 'X4H — Piezas',
                                    'E48' => 'E48 — Unidad de servicio',
                                    'EA' => 'EA — Elemento',
                                    'KGM' => 'KGM — Kilogramo',
                                    'TNE' => 'TNE — Tonelada',
                                    'MTR' => 'MTR — Metro',
                                    'X9G' => 'X9G — (catálogo local)',
                                ];
                            @endphp
                            @foreach($datos['conceptos'] as $i => $c)
                                @php
                                    $unidadSel = trim((string) ($c['unidad'] ?? 'H87'));
                                    $unidadesUpper = array_change_key_case($unidadesSatCatalogoCp, CASE_UPPER);
                                @endphp
                                <tr class="js-concepto-cp"
                                    data-producto="{{ $c['producto'] }}"
                                    data-sku="{{ $c['sku'] ?? '' }}"
                                    data-concepto="{{ $c['concepto'] }}"
                                    data-unidad="{{ $unidadSel }}"
                                    data-cantidad="{{ $c['cantidad'] }}"
                                    data-precio="{{ $c['precio'] }}"
                                    data-importe="{{ $c['importe'] }}">
                                    <td class="fs-8">{{ $c['producto'] }}</td>
                                    <td class="fs-8">
                                        <strong>{{ $c['nombre'] ?? $c['concepto'] }}</strong>
                                        @if(($c['concepto'] ?? '') !== ($c['nombre'] ?? ''))
                                            <div class="text-muted">{{ $c['concepto'] }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm js-unidad-sat" title="Clave unidad SAT (editable)">
                                            @foreach($unidadesSatCatalogoCp as $codigoUnidad => $etiquetaUnidad)
                                                <option value="{{ $codigoUnidad }}" {{ strtoupper($unidadSel) === strtoupper($codigoUnidad) ? 'selected' : '' }}>
                                                    {{ $etiquetaUnidad }}
                                                </option>
                                            @endforeach
                                            @if($unidadSel !== '' && !array_key_exists(strtoupper($unidadSel), $unidadesUpper))
                                                <option value="{{ $unidadSel }}" selected>{{ $unidadSel }}</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td class="text-end fs-8">{{ $c['cantidad'] }}</td>
                                    <td class="text-end fs-8">${{ number_format((float)$c['precio'], 2) }}</td>
                                    <td class="text-end fs-8">${{ number_format((float)$c['importe'], 2) }}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm js-peso-kg" min="0.01" step="0.01"
                                            value="{{ old('peso_'.$i, $c['peso_kg'] !== '' ? $c['peso_kg'] : max(1, (float)$c['cantidad'])) }}" required>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm js-mat-peligroso">
                                            <option value="No" selected>No</option>
                                            <option value="Sí">Sí</option>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="form-text fs-8 text-muted mb-2">
                    La unidad SAT se puede corregir por línea (ej. pieza = <strong>H87</strong>). Se usa en el CFDI y en mercancías de Carta Porte.
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-md-3">
                        <label class="form-label fs-8">Unidad de peso</label>
                        <select class="form-select form-select-sm" name="cp_unidad_peso">
                            <option value="KGM" selected>KGM — Kilogramo</option>
                            <option value="TNE">TNE — Tonelada</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fs-8">Transporte internacional</label>
                        <select class="form-select form-select-sm" name="cp_transp_internac">
                            <option value="No" @selected(old('cp_transp_internac', $cp['transp_internac'] ?? 'No') === 'No')>No</option>
                            <option value="Sí" @selected(old('cp_transp_internac') === 'Sí')>Sí</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fs-8">Forma de pago</label>
                        <select class="form-select form-select-sm" id="forma_pago_visible">
                            <option value="03 - Transferencia electrónica de fondos">03 — Transferencia</option>
                            <option value="01 - Efectivo">01 — Efectivo</option>
                            <option value="99 - Por definir">99 — Por definir</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fs-8">Método de pago</label>
                        <select class="form-select form-select-sm" id="metodo_pago_visible">
                            <option value="PUE" @selected(($datos['pago']['metodo_pago'] ?? 'PUE') === 'PUE')>PUE</option>
                            <option value="PPD" @selected(($datos['pago']['metodo_pago'] ?? '') === 'PPD')>PPD</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-marino mb-3"><i class="fa-solid fa-location-dot me-1"></i> Ubicación origen</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label fs-8">RFC remitente *</label>
                                <input class="form-control form-control-sm" name="cp_origen_rfc" value="{{ old('cp_origen_rfc', $origen['rfc'] ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8">Nombre</label>
                                <input class="form-control form-control-sm" name="cp_origen_nombre" value="{{ old('cp_origen_nombre', $origen['nombre'] ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8">Fecha/hora salida *</label>
                                <input type="datetime-local" class="form-control form-control-sm" name="cp_origen_fecha"
                                    value="{{ old('cp_origen_fecha', isset($origen['fecha']) ? str_replace(' ', 'T', substr($origen['fecha'], 0, 16)) : '') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8">CP *</label>
                                <div class="input-group input-group-sm">
                                    <input class="form-control form-control-sm js-cp-sat" id="cp_origen_cp" name="cp_origen_cp"
                                        value="{{ old('cp_origen_cp', $origen['cp'] ?? '') }}"
                                        data-prefix="origen" maxlength="5" inputmode="numeric" required>
                                    <button type="button" class="btn btn-outline-secondary js-btn-buscar-cp" data-prefix="origen" title="Buscar claves SAT por CP">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>
                                <div class="form-text fs-9 text-muted js-cp-status" data-prefix="origen"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8">Estado SAT *</label>
                                <select class="form-select form-select-sm" id="cp_origen_estado" name="cp_origen_estado" required>
                                    @foreach($estadosSat as $clave => $nombre)
                                        <option value="{{ $clave }}" {{ old('cp_origen_estado', $origen['estado'] ?? '') === $clave ? 'selected' : '' }}>{{ $clave }} — {{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Municipio (clave SAT)</label>
                                <input class="form-control form-control-sm" id="cp_origen_municipio" name="cp_origen_municipio"
                                    value="{{ old('cp_origen_municipio', $origen['municipio'] ?? '') }}" placeholder="Ej. 035" maxlength="3">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Localidad (clave SAT)</label>
                                <input class="form-control form-control-sm" id="cp_origen_localidad" name="cp_origen_localidad"
                                    value="{{ old('cp_origen_localidad', $origen['localidad'] ?? '') }}" placeholder="Ej. 11" maxlength="2">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Colonia</label>
                                <input class="form-control form-control-sm" name="cp_origen_colonia" value="{{ old('cp_origen_colonia', $origen['colonia'] ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8">Calle</label>
                                <input class="form-control form-control-sm" name="cp_origen_calle" value="{{ old('cp_origen_calle', $origen['calle'] ?? '') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8">No. ext.</label>
                                <input class="form-control form-control-sm" name="cp_origen_num_ext" value="{{ old('cp_origen_num_ext', $origen['num_ext'] ?? '') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8">No. int.</label>
                                <input class="form-control form-control-sm" name="cp_origen_num_int" value="{{ old('cp_origen_num_int', $origen['num_int'] ?? '') }}">
                            </div>
                            <input type="hidden" name="cp_origen_pais" value="MEX">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-marino mb-3"><i class="fa-solid fa-flag-checkered me-1"></i> Ubicación destino</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label fs-8">RFC destinatario *</label>
                                <input class="form-control form-control-sm" name="cp_destino_rfc" value="{{ old('cp_destino_rfc', $destino['rfc'] ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8">Nombre</label>
                                <input class="form-control form-control-sm" name="cp_destino_nombre" value="{{ old('cp_destino_nombre', $destino['nombre'] ?? '') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8">Fecha/hora llegada *</label>
                                <input type="datetime-local" class="form-control form-control-sm" name="cp_destino_fecha"
                                    value="{{ old('cp_destino_fecha', isset($destino['fecha']) ? str_replace(' ', 'T', substr($destino['fecha'], 0, 16)) : '') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8">Distancia km *</label>
                                <input type="number" min="0.01" step="0.01" class="form-control form-control-sm" name="cp_destino_distancia"
                                    value="{{ old('cp_destino_distancia', $destino['distancia'] ?? '1') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8">CP *</label>
                                <div class="input-group input-group-sm">
                                    <input class="form-control form-control-sm js-cp-sat" id="cp_destino_cp" name="cp_destino_cp"
                                        value="{{ old('cp_destino_cp', $destino['cp'] ?? '') }}"
                                        data-prefix="destino" maxlength="5" inputmode="numeric" required>
                                    <button type="button" class="btn btn-outline-secondary js-btn-buscar-cp" data-prefix="destino" title="Buscar claves SAT por CP">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>
                                <div class="form-text fs-9 text-muted js-cp-status" data-prefix="destino"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Estado SAT *</label>
                                <select class="form-select form-select-sm" id="cp_destino_estado" name="cp_destino_estado" required>
                                    @foreach($estadosSat as $clave => $nombre)
                                        <option value="{{ $clave }}" {{ old('cp_destino_estado', $destino['estado'] ?? '') === $clave ? 'selected' : '' }}>{{ $clave }} — {{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Municipio (clave SAT)</label>
                                <input class="form-control form-control-sm" id="cp_destino_municipio" name="cp_destino_municipio"
                                    value="{{ old('cp_destino_municipio', $destino['municipio'] ?? '') }}" placeholder="Ej. 035" maxlength="3">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Localidad (clave SAT)</label>
                                <input class="form-control form-control-sm" id="cp_destino_localidad" name="cp_destino_localidad"
                                    value="{{ old('cp_destino_localidad', $destino['localidad'] ?? '') }}" placeholder="Ej. 11" maxlength="2">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Colonia</label>
                                <input class="form-control form-control-sm" name="cp_destino_colonia" value="{{ old('cp_destino_colonia', $destino['colonia'] ?? '') }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fs-8">Calle</label>
                                <input class="form-control form-control-sm" name="cp_destino_calle" value="{{ old('cp_destino_calle', $destino['calle'] ?? '') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8">No. ext.</label>
                                <input class="form-control form-control-sm" name="cp_destino_num_ext" value="{{ old('cp_destino_num_ext', $destino['num_ext'] ?? '') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8">No. int.</label>
                                <input class="form-control form-control-sm" name="cp_destino_num_int" value="{{ old('cp_destino_num_int', $destino['num_int'] ?? '') }}">
                            </div>
                            <input type="hidden" name="cp_destino_pais" value="MEX">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $camionSeleccionado = old('cp_camion_id', $cp['camion_id'] ?? null);
            $choferSeleccionado = old('cp_chofer_id', $cp['chofer_id'] ?? null);
            $camionesLista = $camiones ?? collect();
            $choferesLista = $choferes ?? collect();
        @endphp

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-marino mb-3"><i class="fa-solid fa-truck me-1"></i> Autotransporte</h6>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label fs-8">Camión / unidad</label>
                                <select class="form-select form-select-sm" id="cp_camion_id" name="cp_camion_id">
                                    <option value="">— Seleccionar camión —</option>
                                    @foreach($camionesLista as $c)
                                        <option value="{{ $c->id }}"
                                            {{ (string) $camionSeleccionado === (string) $c->id ? 'selected' : '' }}
                                            data-placas="{{ $c->placas }}"
                                            data-perm-sct="{{ $c->perm_sct }}"
                                            data-num-permiso-sct="{{ $c->num_permiso_sct }}"
                                            data-config-vehicular="{{ $c->config_vehicular }}"
                                            data-anio-modelo="{{ $c->anio_modelo }}"
                                            data-capacidad-ton="{{ $c->capacidad_ton }}"
                                            data-asegura-resp-civil="{{ $c->asegura_resp_civil }}"
                                            data-poliza-resp-civil="{{ $c->poliza_resp_civil }}">
                                            {{ $c->etiqueta_con_capacidad }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text fs-8">Al elegir un camión se rellenan permiso, placa, seguro y peso.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">PermSCT *</label>
                                <input class="form-control form-control-sm" id="cp_perm_sct" name="cp_perm_sct" value="{{ old('cp_perm_sct', $auto['perm_sct'] ?? 'TPAF01') }}" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fs-8">No. permiso SCT *</label>
                                <input class="form-control form-control-sm" id="cp_num_permiso_sct" name="cp_num_permiso_sct" value="{{ old('cp_num_permiso_sct', $auto['num_permiso_sct'] ?? '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Config. vehicular *</label>
                                <input class="form-control form-control-sm" id="cp_config_vehicular" name="cp_config_vehicular" value="{{ old('cp_config_vehicular', $auto['config_vehicular'] ?? 'VL') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Placa *</label>
                                <input class="form-control form-control-sm" id="cp_placa_vm" name="cp_placa_vm" value="{{ old('cp_placa_vm', $auto['placa_vm'] ?? '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Año modelo *</label>
                                <input class="form-control form-control-sm" id="cp_anio_modelo_vm" name="cp_anio_modelo_vm" value="{{ old('cp_anio_modelo_vm', $auto['anio_modelo_vm'] ?? date('Y')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Peso bruto veh. *</label>
                                <input type="number" min="0.01" step="0.01" class="form-control form-control-sm" id="cp_peso_bruto_vehicular" name="cp_peso_bruto_vehicular"
                                    value="{{ old('cp_peso_bruto_vehicular', $auto['peso_bruto_vehicular'] ?? '1') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Aseguradora RC *</label>
                                <input class="form-control form-control-sm" id="cp_asegura_resp_civil" name="cp_asegura_resp_civil" value="{{ old('cp_asegura_resp_civil', $auto['asegura_resp_civil'] ?? '') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Póliza RC *</label>
                                <input class="form-control form-control-sm" id="cp_poliza_resp_civil" name="cp_poliza_resp_civil" value="{{ old('cp_poliza_resp_civil', $auto['poliza_resp_civil'] ?? '') }}" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="text-marino mb-3"><i class="fa-solid fa-id-card me-1"></i> Figura de transporte</h6>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label fs-8">Operador / chofer</label>
                                <select class="form-select form-select-sm" id="cp_chofer_id" name="cp_chofer_id">
                                    <option value="">— Seleccionar operador —</option>
                                    @foreach($choferesLista as $ch)
                                        <option value="{{ $ch->id }}"
                                            {{ (string) $choferSeleccionado === (string) $ch->id ? 'selected' : '' }}
                                            data-nombre="{{ $ch->nombre }}"
                                            data-rfc="{{ $ch->rfc }}"
                                            data-licencia="{{ $ch->licencia }}">
                                            {{ $ch->etiqueta }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text fs-8">Al elegir un operador se rellenan nombre, RFC y licencia.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-8">Tipo figura *</label>
                                <select class="form-select form-select-sm" name="cp_figura_tipo" required>
                                    <option value="01" {{ old('cp_figura_tipo', $figura['tipo_figura'] ?? '01') === '01' ? 'selected' : '' }}>01 — Operador</option>
                                    <option value="02" {{ old('cp_figura_tipo') === '02' ? 'selected' : '' }}>02 — Propietario</option>
                                    <option value="03" {{ old('cp_figura_tipo') === '03' ? 'selected' : '' }}>03 — Arrendatario</option>
                                    <option value="04" {{ old('cp_figura_tipo') === '04' ? 'selected' : '' }}>04 — Notificado</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fs-8">Nombre *</label>
                                <input class="form-control form-control-sm" id="cp_figura_nombre" name="cp_figura_nombre" value="{{ old('cp_figura_nombre', $figura['nombre_figura'] ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8">RFC figura *</label>
                                <input class="form-control form-control-sm" id="cp_figura_rfc" name="cp_figura_rfc" value="{{ old('cp_figura_rfc', $figura['rfc_figura'] ?? '') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-8">No. licencia *</label>
                                <input class="form-control form-control-sm" id="cp_figura_licencia" name="cp_figura_licencia" value="{{ old('cp_figura_licencia', $figura['num_licencia'] ?? '') }}" required>
                            </div>
                        </div>
                        <p class="fs-8 text-muted mt-3 mb-0">
                            Completa municipio/localidad con claves del catálogo SAT si Facturama lo exige.
                            Guía: <a href="https://apisandbox.facturama.mx/guias/complementos/complemento-carta-porte-31" target="_blank" rel="noopener">Carta Porte 3.1</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('ventas.pedidos', ['vendedor_id' => auth()->id(), 'abrir_pedido' => 1, 'pedido_id' => $id]) }}"
               class="btn btn-secondary">Cancelar</a>
            <button type="button" class="btn btn-outline-primary" id="btn-vista-previa-cp">
                <i class="fa-solid fa-eye me-1"></i> Vista previa
            </button>
            <button type="submit" class="btn btn-success" id="btn-timbrar-carta-porte">
                <i class="fa-solid fa-stamp me-1"></i> Timbrar con Carta Porte
            </button>
        </div>
    </form>
</div>

{{-- Vista previa representación Carta Porte 3.1 --}}
<div class="modal fade" id="modalVistaPreviaCp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title text-marino mb-0">
                        <i class="fa-solid fa-file-invoice me-2"></i>Vista previa — CFDI Ingreso + Carta Porte 3.1
                    </h5>
                    <div class="fs-8 text-muted">Representación según estructura Facturama / SAT (NameId 36). No es el XML definitivo.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="d-flex justify-content-end gap-2 mb-2 d-print-none">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-imprimir-previa-cp">
                        <i class="fa-solid fa-print me-1"></i> Imprimir
                    </button>
                </div>
                <div id="cp-preview-sheet" class="cp-preview-sheet">
                    <div class="cp-preview-top">
                        <div>
                            <div class="cp-preview-badge">CFDI 4.0 · Tipo I · NameId 36</div>
                            <h4 class="cp-preview-title mb-1">Carta Porte versión 3.1</h4>
                            <div class="fs-8 text-muted" id="pv-pedido-ref"></div>
                        </div>
                        <div class="text-end fs-8">
                            <div><strong>Forma pago:</strong> <span id="pv-forma-pago">—</span></div>
                            <div><strong>Método pago:</strong> <span id="pv-metodo-pago">—</span></div>
                            <div><strong>Transp. internac.:</strong> <span id="pv-transp-internac">—</span></div>
                            <div><strong>Lugar expedición:</strong> <span id="pv-lugar-exp">—</span></div>
                        </div>
                    </div>

                    <div class="cp-preview-grid2">
                        <section>
                            <h6>Emisor</h6>
                            <div id="pv-emisor"></div>
                        </section>
                        <section>
                            <h6>Receptor</h6>
                            <div id="pv-receptor"></div>
                        </section>
                    </div>

                    <section class="mt-3">
                        <h6>Conceptos del CFDI</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 cp-preview-table">
                                <thead>
                                    <tr>
                                        <th>Clave</th>
                                        <th>Descripción</th>
                                        <th>Unidad</th>
                                        <th class="text-end">Cant.</th>
                                        <th class="text-end">P. unit.</th>
                                        <th class="text-end">Importe</th>
                                    </tr>
                                </thead>
                                <tbody id="pv-conceptos-body"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>Subtotal</strong></td>
                                        <td class="text-end" id="pv-subtotal">$0.00</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>IVA 16%</strong></td>
                                        <td class="text-end" id="pv-iva">$0.00</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>Total</strong></td>
                                        <td class="text-end" id="pv-total"><strong>$0.00</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </section>

                    <section class="mt-3">
                        <h6>Complemento CartaPorte31 · Ubicaciones</h6>
                        <div class="cp-preview-grid2">
                            <div class="cp-preview-box">
                                <div class="cp-preview-box-title">Origen · OR000001</div>
                                <div id="pv-origen"></div>
                            </div>
                            <div class="cp-preview-box">
                                <div class="cp-preview-box-title">Destino · DE000001</div>
                                <div id="pv-destino"></div>
                            </div>
                        </div>
                    </section>

                    <section class="mt-3">
                        <h6>Mercancías <span class="fw-normal text-muted fs-8" id="pv-unidad-peso"></span></h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 cp-preview-table">
                                <thead>
                                    <tr>
                                        <th>BienesTransp</th>
                                        <th>Descripción</th>
                                        <th>Cant.</th>
                                        <th>ClaveUnidad</th>
                                        <th>PesoEnKg</th>
                                        <th>Mat. peligroso</th>
                                    </tr>
                                </thead>
                                <tbody id="pv-mercancias-body"></tbody>
                            </table>
                        </div>
                    </section>

                    <div class="cp-preview-grid2 mt-3">
                        <section class="cp-preview-box">
                            <div class="cp-preview-box-title">Autotransporte</div>
                            <div id="pv-autotransporte"></div>
                        </section>
                        <section class="cp-preview-box">
                            <div class="cp-preview-box-title">Figura de transporte</div>
                            <div id="pv-figura"></div>
                        </section>
                    </div>

                    <details class="mt-3">
                        <summary class="fs-8 text-muted" style="cursor:pointer">Ver JSON CartaPorte31 (payload aproximado)</summary>
                        <pre id="pv-json" class="cp-preview-json mt-2 mb-0"></pre>
                    </details>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btn-timbrar-desde-previa">
                    <i class="fa-solid fa-stamp me-1"></i> Continuar a timbrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.cp-preview-sheet {
    background: #fff;
    border: 1px solid #cfd6de;
    border-radius: 8px;
    padding: 18px 20px;
    color: #1a1a1a;
    font-size: 12px;
    line-height: 1.4;
}
.cp-preview-top {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 2px solid #1e3a5f;
    padding-bottom: 12px;
    margin-bottom: 14px;
}
.cp-preview-badge {
    display: inline-block;
    background: #e8eef5;
    color: #1e3a5f;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .03em;
    padding: 3px 8px;
    border-radius: 4px;
    margin-bottom: 6px;
}
.cp-preview-title {
    color: #1e3a5f;
    font-size: 1.15rem;
}
.cp-preview-sheet h6 {
    color: #1e3a5f;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 8px;
}
.cp-preview-grid2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.cp-preview-box {
    border: 1px solid #d9dee5;
    border-radius: 6px;
    padding: 10px 12px;
    background: #fafbfc;
}
.cp-preview-box-title {
    font-weight: 700;
    color: #1e3a5f;
    margin-bottom: 6px;
    font-size: 11px;
    text-transform: uppercase;
}
.cp-preview-table th {
    background: #f0f3f7;
    font-size: 10px;
    text-transform: uppercase;
}
.cp-preview-json {
    background: #1e2430;
    color: #d6e2ff;
    border-radius: 6px;
    padding: 12px;
    font-size: 11px;
    max-height: 280px;
    overflow: auto;
}
.cp-preview-kv { margin-bottom: 2px; }
.cp-preview-kv strong { color: #333; }
@media (max-width: 768px) {
    .cp-preview-grid2 { grid-template-columns: 1fr; }
    .cp-preview-top { flex-direction: column; }
}
@media print {
    body * { visibility: hidden !important; }
    #cp-preview-sheet, #cp-preview-sheet * { visibility: visible !important; }
    #cp-preview-sheet {
        position: absolute;
        left: 0; top: 0; width: 100%;
        border: none; box-shadow: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('formCartaPorte');
    var hiddenConceptos = document.getElementById('conceptos_json');
    var formaVisible = document.getElementById('forma_pago_visible');
    var metodoVisible = document.getElementById('metodo_pago_visible');

    function seleccionarValorInicial(selectEl) {
        if (!selectEl) return;
        var inicial = (selectEl.getAttribute('data-valor-inicial') || '').trim();
        if (!inicial) return;
        var codigo = inicial.split(' - ')[0].trim();
        var encontrado = false;
        Array.prototype.forEach.call(selectEl.options, function (opt) {
            if (opt.value === inicial || opt.value.indexOf(codigo + ' - ') === 0 || opt.value === codigo) {
                opt.selected = true;
                encontrado = true;
            }
        });
        if (!encontrado && inicial) {
            var opt = document.createElement('option');
            opt.value = inicial;
            opt.textContent = inicial;
            opt.selected = true;
            selectEl.appendChild(opt);
        }
    }

    seleccionarValorInicial(document.getElementById('uso_cfdi'));
    seleccionarValorInicial(document.getElementById('regimen_fiscal_receptor'));

    var urlBuscarCp = @json(route('buscar.codigo.postal'));
    var timersCp = {};

    function setCpStatus(prefix, text, isError) {
        var el = document.querySelector('.js-cp-status[data-prefix="' + prefix + '"]');
        if (!el) return;
        el.textContent = text || '';
        el.classList.toggle('text-danger', !!isError);
        el.classList.toggle('text-success', !isError && !!text);
        el.classList.toggle('text-muted', !text);
    }

    function aplicarCodigoPostalSat(prefix, item) {
        if (!item) return;
        var estadoEl = document.getElementById('cp_' + prefix + '_estado');
        var municipioEl = document.getElementById('cp_' + prefix + '_municipio');
        var localidadEl = document.getElementById('cp_' + prefix + '_localidad');
        var cpEl = document.getElementById('cp_' + prefix + '_cp');

        if (cpEl && item.cp) {
            cpEl.value = item.cp;
        }
        if (estadoEl && item.estado) {
            estadoEl.value = item.estado;
            if (estadoEl.value !== item.estado) {
                var opt = document.createElement('option');
                opt.value = item.estado;
                opt.textContent = item.estado;
                opt.selected = true;
                estadoEl.appendChild(opt);
            }
        }
        if (municipioEl) {
            municipioEl.value = item.municipio || '';
        }
        if (localidadEl) {
            localidadEl.value = item.localidad || '';
        }
        setCpStatus(
            prefix,
            'SAT: ' + (item.estado || '—') + ' / Mun ' + (item.municipio || '—') + ' / Loc ' + (item.localidad || '—'),
            false
        );
    }

    function buscarCodigoPostalSat(prefix, forzar) {
        var cpEl = document.getElementById('cp_' + prefix + '_cp');
        if (!cpEl) return;
        var cp = String(cpEl.value || '').replace(/\D+/g, '');
        cpEl.value = cp;

        if (cp.length < 5) {
            if (forzar) {
                setCpStatus(prefix, 'Capture un CP de 5 dígitos.', true);
            }
            return;
        }

        setCpStatus(prefix, 'Consultando Facturama…', false);

        fetch(urlBuscarCp + '?keyword=' + encodeURIComponent(cp), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
                if (!res.ok || !res.data || res.data.ok === false) {
                    setCpStatus(prefix, (res.data && res.data.error) ? res.data.error : 'No se encontró el CP.', true);
                    return;
                }
                var sugerido = res.data.sugerido || ((res.data.data || [])[0] || null);
                if (!sugerido) {
                    setCpStatus(prefix, 'CP sin coincidencias en catálogo.', true);
                    return;
                }
                aplicarCodigoPostalSat(prefix, sugerido);
            })
            .catch(function (err) {
                setCpStatus(prefix, 'Error al consultar CP: ' + (err.message || 'red'), true);
            });
    }

    document.querySelectorAll('.js-cp-sat').forEach(function (input) {
        var prefix = input.getAttribute('data-prefix');
        input.addEventListener('blur', function () {
            buscarCodigoPostalSat(prefix, false);
        });
        input.addEventListener('input', function () {
            clearTimeout(timersCp[prefix]);
            timersCp[prefix] = setTimeout(function () {
                if (String(input.value || '').replace(/\D+/g, '').length === 5) {
                    buscarCodigoPostalSat(prefix, false);
                }
            }, 450);
        });
    });

    document.querySelectorAll('.js-btn-buscar-cp').forEach(function (btn) {
        btn.addEventListener('click', function () {
            buscarCodigoPostalSat(btn.getAttribute('data-prefix'), true);
        });
    });

    // Autocompletar al cargar si ya hay CP
    ['origen', 'destino'].forEach(function (prefix) {
        var cpEl = document.getElementById('cp_' + prefix + '_cp');
        if (cpEl && String(cpEl.value || '').replace(/\D+/g, '').length === 5) {
            buscarCodigoPostalSat(prefix, false);
        }
    });

    function setInputValue(id, value, fallback) {
        var el = document.getElementById(id);
        if (!el) return;
        var v = (value == null ? '' : String(value)).trim();
        if (v === '' && fallback !== undefined) {
            v = String(fallback);
        }
        el.value = v;
    }

    function aplicarCamionSeleccionado() {
        var sel = document.getElementById('cp_camion_id');
        if (!sel || !sel.value) return;
        var opt = sel.options[sel.selectedIndex];
        if (!opt) return;
        setInputValue('cp_perm_sct', opt.getAttribute('data-perm-sct'), 'TPAF01');
        setInputValue('cp_num_permiso_sct', opt.getAttribute('data-num-permiso-sct'), '');
        setInputValue('cp_config_vehicular', opt.getAttribute('data-config-vehicular'), 'VL');
        setInputValue('cp_placa_vm', opt.getAttribute('data-placas'), '');
        setInputValue('cp_anio_modelo_vm', opt.getAttribute('data-anio-modelo'), String(new Date().getFullYear()));
        setInputValue('cp_peso_bruto_vehicular', opt.getAttribute('data-capacidad-ton'), '1');
        setInputValue('cp_asegura_resp_civil', opt.getAttribute('data-asegura-resp-civil'), '');
        setInputValue('cp_poliza_resp_civil', opt.getAttribute('data-poliza-resp-civil'), '');
    }

    function aplicarChoferSeleccionado() {
        var sel = document.getElementById('cp_chofer_id');
        if (!sel || !sel.value) return;
        var opt = sel.options[sel.selectedIndex];
        if (!opt) return;
        setInputValue('cp_figura_nombre', opt.getAttribute('data-nombre'), '');
        setInputValue('cp_figura_rfc', opt.getAttribute('data-rfc'), '');
        setInputValue('cp_figura_licencia', opt.getAttribute('data-licencia'), '');
    }

    var selCamion = document.getElementById('cp_camion_id');
    if (selCamion) {
        selCamion.addEventListener('change', aplicarCamionSeleccionado);
    }
    var selChofer = document.getElementById('cp_chofer_id');
    if (selChofer) {
        selChofer.addEventListener('change', aplicarChoferSeleccionado);
    }

    function sincronizarConceptos() {
        var filas = document.querySelectorAll('.js-concepto-cp');
        var conceptos = [];
        filas.forEach(function (fila) {
            var unidadSelect = fila.querySelector('.js-unidad-sat');
            var unidad = unidadSelect ? String(unidadSelect.value || '').trim() : (fila.getAttribute('data-unidad') || 'H87');
            if (unidadSelect) {
                fila.setAttribute('data-unidad', unidad);
            }
            conceptos.push({
                producto: fila.getAttribute('data-producto'),
                sku: fila.getAttribute('data-sku') || '',
                concepto: fila.getAttribute('data-concepto'),
                unidad: unidad,
                cantidad: fila.getAttribute('data-cantidad'),
                precio: fila.getAttribute('data-precio'),
                importe: fila.getAttribute('data-importe'),
                peso_kg: (fila.querySelector('.js-peso-kg') || {}).value || '1',
                material_peligroso: (fila.querySelector('.js-mat-peligroso') || {}).value || 'No'
            });
        });
        hiddenConceptos.value = JSON.stringify(conceptos);
    }

    document.querySelectorAll('.js-unidad-sat').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var fila = sel.closest('.js-concepto-cp');
            if (fila) {
                fila.setAttribute('data-unidad', sel.value);
            }
            sincronizarConceptos();
        });
    });

    if (form) {
        form.addEventListener('submit', function () {
            sincronizarConceptos();
            if (formaVisible) {
                form.querySelector('input[name="forma_pago"]').value = formaVisible.value;
            }
            if (metodoVisible) {
                form.querySelector('input[name="metodo_pago"]').value = metodoVisible.value;
            }
            var btn = document.getElementById('btn-timbrar-carta-porte');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Timbrando...';
            }
        });
        sincronizarConceptos();
    }

    function val(name) {
        var el = form ? form.querySelector('[name="' + name + '"]') : null;
        return el ? String(el.value || '').trim() : '';
    }

    function valId(id) {
        var el = document.getElementById(id);
        return el ? String(el.value || '').trim() : '';
    }

    function money(n) {
        var num = Number(n) || 0;
        return '$' + num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function codigoDe(valor) {
        var v = String(valor || '').trim();
        if (!v) return '';
        return v.indexOf(' - ') >= 0 ? v.split(' - ')[0].trim() : v;
    }

    function kv(label, value) {
        var v = (value == null || String(value).trim() === '') ? '—' : String(value);
        return '<div class="cp-preview-kv"><strong>' + label + ':</strong> ' + v + '</div>';
    }

    function domicilioHtml(prefix) {
        var partes = [
            val(prefix + '_calle'),
            val(prefix + '_num_ext') ? ('Ext. ' + val(prefix + '_num_ext')) : '',
            val(prefix + '_num_int') ? ('Int. ' + val(prefix + '_num_int')) : '',
            val(prefix + '_colonia') ? ('Col. ' + val(prefix + '_colonia')) : '',
            val(prefix + '_municipio'),
            val(prefix + '_localidad') ? ('Loc. ' + val(prefix + '_localidad')) : '',
            val(prefix + '_estado'),
            val(prefix + '_cp') ? ('CP ' + val(prefix + '_cp')) : '',
            val(prefix + '_pais') || 'MEX'
        ].filter(Boolean);
        return partes.join(', ') || '—';
    }

    function construirPayloadPrevia() {
        sincronizarConceptos();
        var conceptos = [];
        try {
            conceptos = JSON.parse(hiddenConceptos.value || '[]');
        } catch (e) {
            conceptos = [];
        }

        var items = [];
        var mercancias = [];
        var subtotal = 0;
        var iva = 0;

        conceptos.forEach(function (c) {
            var cantidad = Number(c.cantidad) || 0;
            var precio = Number(c.precio) || 0;
            var importe = Number(c.importe);
            if (!importe) importe = cantidad * precio;
            var ivaItem = Math.round(importe * 0.16 * 100) / 100;
            subtotal += importe;
            iva += ivaItem;

            var productCode = codigoDe(c.producto);
            var unitCode = codigoDe(c.unidad);
            var peso = Number(c.peso_kg) || Math.max(1, cantidad);
            var esPeligroso = (c.material_peligroso === 'Sí' || c.material_peligroso === 'Si');

            items.push({
                ProductCode: productCode,
                Description: c.concepto || '',
                UnitCode: unitCode,
                Quantity: cantidad,
                UnitPrice: precio,
                Subtotal: importe,
                Total: Math.round((importe + ivaItem) * 100) / 100
            });

            var mercancia = {
                BienesTransp: productCode,
                Descripcion: c.concepto || '',
                Cantidad: String(cantidad),
                ClaveUnidad: unitCode,
                PesoEnKg: String(peso),
                CantidadTransporta: [{
                    Cantidad: String(cantidad),
                    IDOrigen: 'OR000001',
                    IDDestino: 'DE000001'
                }]
            };
            // No enviar MaterialPeligroso cuando es "No" (catálogo SAT = 0).
            if (esPeligroso) {
                mercancia.MaterialPeligroso = 'Sí';
            }
            mercancias.push(mercancia);
        });

        var cartaPorte = {
            TranspInternac: (val('cp_transp_internac') === 'Sí' || val('cp_transp_internac') === 'Si') ? 'Sí' : 'No',
            Ubicaciones: [
                {
                    TipoUbicacion: 'Origen',
                    IDUbicacion: 'OR000001',
                    RFCRemitenteDestinatario: val('cp_origen_rfc').toUpperCase(),
                    NombreRemitenteDestinatario: val('cp_origen_nombre'),
                    FechaHoraSalidaLlegada: val('cp_origen_fecha').replace('T', ' '),
                    Domicilio: {
                        Calle: val('cp_origen_calle'),
                        NumeroExterior: val('cp_origen_num_ext'),
                        NumeroInterior: val('cp_origen_num_int'),
                        Colonia: val('cp_origen_colonia'),
                        Localidad: val('cp_origen_localidad'),
                        Municipio: val('cp_origen_municipio'),
                        Estado: val('cp_origen_estado').toUpperCase(),
                        Pais: (val('cp_origen_pais') || 'MEX').toUpperCase(),
                        CodigoPostal: val('cp_origen_cp')
                    }
                },
                {
                    TipoUbicacion: 'Destino',
                    IDUbicacion: 'DE000001',
                    RFCRemitenteDestinatario: val('cp_destino_rfc').toUpperCase(),
                    NombreRemitenteDestinatario: val('cp_destino_nombre'),
                    FechaHoraSalidaLlegada: val('cp_destino_fecha').replace('T', ' '),
                    DistanciaRecorrida: val('cp_destino_distancia'),
                    Domicilio: {
                        Calle: val('cp_destino_calle'),
                        NumeroExterior: val('cp_destino_num_ext'),
                        NumeroInterior: val('cp_destino_num_int'),
                        Colonia: val('cp_destino_colonia'),
                        Localidad: val('cp_destino_localidad'),
                        Municipio: val('cp_destino_municipio'),
                        Estado: val('cp_destino_estado').toUpperCase(),
                        Pais: (val('cp_destino_pais') || 'MEX').toUpperCase(),
                        CodigoPostal: val('cp_destino_cp')
                    }
                }
            ],
            Mercancias: {
                UnidadPeso: val('cp_unidad_peso') || 'KGM',
                Mercancia: mercancias,
                Autotransporte: {
                    PermSCT: val('cp_perm_sct'),
                    NumPermisoSCT: val('cp_num_permiso_sct'),
                    IdentificacionVehicular: {
                        ConfigVehicular: val('cp_config_vehicular'),
                        PesoBrutoVehicular: val('cp_peso_bruto_vehicular'),
                        PlacaVM: val('cp_placa_vm').toUpperCase(),
                        AnioModeloVM: val('cp_anio_modelo_vm')
                    },
                    Seguros: {
                        AseguraRespCivil: val('cp_asegura_resp_civil'),
                        PolizaRespCivil: val('cp_poliza_resp_civil')
                    }
                }
            },
            FiguraTransporte: [{
                TipoFigura: val('cp_figura_tipo') || '01',
                RFCFigura: val('cp_figura_rfc').toUpperCase(),
                NumLicencia: val('cp_figura_licencia'),
                NombreFigura: val('cp_figura_nombre')
            }]
        };

        return {
            CfdiType: 'I',
            NameId: '36',
            PaymentForm: codigoDe(formaVisible ? formaVisible.value : val('forma_pago')),
            PaymentMethod: metodoVisible ? metodoVisible.value : val('metodo_pago'),
            ExpeditionPlace: val('emisor_lugar_expedicion'),
            Folio: val('folio'),
            Issuer: {
                Name: val('emisor_nombre'),
                Rfc: val('emisor_rfc'),
                FiscalRegime: codigoDe(val('emisor_regimen_fiscal'))
            },
            Receiver: {
                Name: val('receptor_nombre'),
                Rfc: val('receptor_rfc'),
                CfdiUse: codigoDe(valId('uso_cfdi') || val('receptor_uso_cfdi')),
                FiscalRegime: codigoDe(valId('regimen_fiscal_receptor') || val('receptor_regimen_fiscal')),
                TaxZipCode: val('receptor_codigo_postal')
            },
            Items: items,
            Complemento: { CartaPorte31: cartaPorte },
            _totales: {
                subtotal: Math.round(subtotal * 100) / 100,
                iva: Math.round(iva * 100) / 100,
                total: Math.round((subtotal + iva) * 100) / 100
            }
        };
    }

    function renderVistaPrevia() {
        var payload = construirPayloadPrevia();
        var cp = payload.Complemento.CartaPorte31;
        var origen = cp.Ubicaciones[0] || {};
        var destino = cp.Ubicaciones[1] || {};
        var auto = cp.Mercancias.Autotransporte || {};
        var idVeh = auto.IdentificacionVehicular || {};
        var seguros = auto.Seguros || {};
        var figura = (cp.FiguraTransporte || [])[0] || {};

        document.getElementById('pv-pedido-ref').textContent =
            'Pedido ' + (payload.Folio || '{{ $id }}') + ' · Vista previa local (sin timbrar)';
        document.getElementById('pv-forma-pago').textContent = payload.PaymentForm || '—';
        document.getElementById('pv-metodo-pago').textContent = payload.PaymentMethod || '—';
        document.getElementById('pv-transp-internac').textContent = cp.TranspInternac || '—';
        document.getElementById('pv-lugar-exp').textContent = payload.ExpeditionPlace || '—';

        document.getElementById('pv-emisor').innerHTML =
            kv('Nombre', payload.Issuer.Name) +
            kv('RFC', payload.Issuer.Rfc) +
            kv('Régimen', payload.Issuer.FiscalRegime);

        document.getElementById('pv-receptor').innerHTML =
            kv('Nombre', payload.Receiver.Name) +
            kv('RFC', payload.Receiver.Rfc) +
            kv('Uso CFDI', payload.Receiver.CfdiUse) +
            kv('Régimen', payload.Receiver.FiscalRegime) +
            kv('CP', payload.Receiver.TaxZipCode);

        var conceptosHtml = '';
        (payload.Items || []).forEach(function (it) {
            conceptosHtml += '<tr>' +
                '<td>' + (it.ProductCode || '') + '</td>' +
                '<td>' + (it.Description || '') + '</td>' +
                '<td>' + (it.UnitCode || '') + '</td>' +
                '<td class="text-end">' + it.Quantity + '</td>' +
                '<td class="text-end">' + money(it.UnitPrice) + '</td>' +
                '<td class="text-end">' + money(it.Subtotal) + '</td>' +
                '</tr>';
        });
        document.getElementById('pv-conceptos-body').innerHTML = conceptosHtml || '<tr><td colspan="6" class="text-muted">Sin conceptos</td></tr>';
        document.getElementById('pv-subtotal').textContent = money(payload._totales.subtotal);
        document.getElementById('pv-iva').textContent = money(payload._totales.iva);
        document.getElementById('pv-total').innerHTML = '<strong>' + money(payload._totales.total) + '</strong>';

        document.getElementById('pv-origen').innerHTML =
            kv('RFC', origen.RFCRemitenteDestinatario) +
            kv('Nombre', origen.NombreRemitenteDestinatario) +
            kv('Fecha/hora salida', origen.FechaHoraSalidaLlegada) +
            kv('Domicilio', domicilioHtml('cp_origen'));

        document.getElementById('pv-destino').innerHTML =
            kv('RFC', destino.RFCRemitenteDestinatario) +
            kv('Nombre', destino.NombreRemitenteDestinatario) +
            kv('Fecha/hora llegada', destino.FechaHoraSalidaLlegada) +
            kv('Distancia (km)', destino.DistanciaRecorrida) +
            kv('Domicilio', domicilioHtml('cp_destino'));

        document.getElementById('pv-unidad-peso').textContent = '(UnidadPeso: ' + (cp.Mercancias.UnidadPeso || 'KGM') + ')';
        var mercHtml = '';
        (cp.Mercancias.Mercancia || []).forEach(function (m) {
            mercHtml += '<tr>' +
                '<td>' + (m.BienesTransp || '') + '</td>' +
                '<td>' + (m.Descripcion || '') + '</td>' +
                '<td>' + (m.Cantidad || '') + '</td>' +
                '<td>' + (m.ClaveUnidad || '') + '</td>' +
                '<td>' + (m.PesoEnKg || '') + '</td>' +
                '<td>' + (m.MaterialPeligroso || 'No aplica (omitido)') + '</td>' +
                '</tr>';
        });
        document.getElementById('pv-mercancias-body').innerHTML = mercHtml || '<tr><td colspan="6" class="text-muted">Sin mercancías</td></tr>';

        document.getElementById('pv-autotransporte').innerHTML =
            kv('PermSCT', auto.PermSCT) +
            kv('NumPermisoSCT', auto.NumPermisoSCT) +
            kv('ConfigVehicular', idVeh.ConfigVehicular) +
            kv('PlacaVM', idVeh.PlacaVM) +
            kv('AnioModeloVM', idVeh.AnioModeloVM) +
            kv('PesoBrutoVehicular', idVeh.PesoBrutoVehicular) +
            kv('AseguraRespCivil', seguros.AseguraRespCivil) +
            kv('PolizaRespCivil', seguros.PolizaRespCivil);

        var tipoFiguraTxt = {
            '01': '01 — Operador',
            '02': '02 — Propietario',
            '03': '03 — Arrendatario',
            '04': '04 — Notificado'
        };
        document.getElementById('pv-figura').innerHTML =
            kv('TipoFigura', tipoFiguraTxt[figura.TipoFigura] || figura.TipoFigura) +
            kv('NombreFigura', figura.NombreFigura) +
            kv('RFCFigura', figura.RFCFigura) +
            kv('NumLicencia', figura.NumLicencia);

        var jsonLimpio = {
            CfdiType: payload.CfdiType,
            NameId: payload.NameId,
            PaymentForm: payload.PaymentForm,
            PaymentMethod: payload.PaymentMethod,
            ExpeditionPlace: payload.ExpeditionPlace,
            Folio: payload.Folio,
            Issuer: payload.Issuer,
            Receiver: payload.Receiver,
            Items: payload.Items,
            Complemento: payload.Complemento
        };
        document.getElementById('pv-json').textContent = JSON.stringify(jsonLimpio, null, 2);
    }

    var btnPrevia = document.getElementById('btn-vista-previa-cp');
    if (btnPrevia) {
        btnPrevia.addEventListener('click', function () {
            renderVistaPrevia();
            var modalEl = document.getElementById('modalVistaPreviaCp');
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else if (window.jQuery) {
                jQuery(modalEl).modal('show');
            } else {
                modalEl.style.display = 'block';
                modalEl.classList.add('show');
            }
        });
    }

    var btnPrint = document.getElementById('btn-imprimir-previa-cp');
    if (btnPrint) {
        btnPrint.addEventListener('click', function () {
            window.print();
        });
    }

    var btnTimbrarPrevia = document.getElementById('btn-timbrar-desde-previa');
    if (btnTimbrarPrevia && form) {
        btnTimbrarPrevia.addEventListener('click', function () {
            var modalEl = document.getElementById('modalVistaPreviaCp');
            if (window.bootstrap && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });
    }
});
</script>
@endsection
