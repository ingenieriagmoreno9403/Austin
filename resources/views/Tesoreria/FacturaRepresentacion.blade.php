<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura {{ $folio }} — Representación impresa</title>
    <style>
        :root {
            --ink: #1a1a1a;
            --muted: #555;
            --line: #b8b8b8;
            --soft: #f3f3f3;
            --accent: #1e3a5f;
            --accent-2: #2c5282;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 16px;
            color: var(--ink);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            line-height: 1.35;
            background: #e8e8e8;
        }
        .toolbar {
            max-width: 920px;
            margin: 0 auto 12px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .toolbar a, .toolbar button {
            border: 1px solid #ccc;
            background: #fff;
            color: #222;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 12px;
        }
        .toolbar button.primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        .sheet {
            max-width: 920px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #ccc;
            padding: 18px 20px 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
        }
        .top {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 16px;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .brand {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .brand img {
            width: 72px;
            height: auto;
            object-fit: contain;
        }
        .brand h1 {
            margin: 0 0 4px;
            font-size: 14px;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: .2px;
        }
        .brand .rfc {
            font-weight: 700;
            margin-top: 4px;
        }
        .meta-box {
            border: 1px solid var(--line);
            border-radius: 4px;
            overflow: hidden;
        }
        .meta-box .title {
            background: var(--accent);
            color: #fff;
            font-weight: 700;
            text-align: center;
            padding: 6px;
            font-size: 15px;
            letter-spacing: 1px;
        }
        .meta-box .row {
            display: grid;
            grid-template-columns: 110px 1fr;
            border-top: 1px solid var(--line);
        }
        .meta-box .row span {
            padding: 5px 8px;
        }
        .meta-box .row span:first-child {
            background: var(--soft);
            font-weight: 600;
            border-right: 1px solid var(--line);
        }
        .cliente {
            display: grid;
            grid-template-columns: 1.7fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
            align-items: start;
        }
        .box {
            border: 1px solid var(--line);
            border-radius: 4px;
            padding: 8px 10px;
        }
        .box .label {
            font-size: 10px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 2px;
        }
        .box .name {
            font-size: 13px;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 4px;
        }
        .kv { margin: 2px 0; }
        .kv strong { font-weight: 600; }
        .side-fields {
            border: 1px solid var(--line);
            border-radius: 4px;
            overflow: hidden;
        }
        .side-fields .field {
            display: grid;
            grid-template-columns: 118px 1fr;
            border-top: 1px solid var(--line);
            margin: 0;
            border-radius: 0;
            border: none;
        }
        .side-fields .field:first-child {
            border-top: none;
        }
        .side-fields .field .k {
            background: var(--soft);
            padding: 4px 8px;
            font-weight: 600;
            border-right: 1px solid var(--line);
            border-bottom: none;
            font-size: 10px;
            display: flex;
            align-items: center;
        }
        .side-fields .field .v {
            padding: 4px 8px;
            min-height: 0;
            display: flex;
            align-items: center;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.items th {
            background: var(--accent);
            color: #fff;
            font-weight: 600;
            padding: 6px 5px;
            text-align: left;
            font-size: 10px;
        }
        table.items td {
            border: 1px solid var(--line);
            padding: 6px 5px;
            vertical-align: top;
        }
        table.items .num { text-align: right; white-space: nowrap; }
        table.items .center { text-align: center; }
        .concepto-desc { color: var(--muted); font-size: 10px; margin-top: 2px; }
        .concepto-extra { margin-top: 3px; font-size: 10.5px; }
        .relacionados, .credito-box {
            border: 1px solid var(--line);
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 10px;
            background: #fafafa;
        }
        .relacionados h3, .credito-box h3 {
            margin: 0 0 6px;
            font-size: 11px;
            color: var(--accent);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .credito-box table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .credito-box th {
            text-align: left;
            border-bottom: 1px solid var(--line);
            padding: 4px 6px;
            font-weight: 700;
        }
        .credito-box td {
            padding: 5px 6px;
            border-bottom: 1px solid #e5e5e5;
        }
        .totales-wrap {
            display: grid;
            grid-template-columns: 1.4fr .9fr;
            gap: 12px;
            margin-bottom: 10px;
        }
        .pago-info .line { margin: 3px 0; }
        .totales table {
            width: 100%;
            border-collapse: collapse;
        }
        .totales td {
            padding: 4px 6px;
            border: 1px solid var(--line);
        }
        .totales td:first-child {
            background: var(--soft);
            font-weight: 600;
            width: 55%;
        }
        .totales td:last-child { text-align: right; }
        .totales .total td {
            background: var(--accent);
            color: #fff;
            font-weight: 700;
            font-size: 12px;
        }
        .letras {
            margin-top: 6px;
            font-style: italic;
            color: var(--muted);
            font-size: 10px;
        }
        .cert {
            border: 1px solid var(--line);
            padding: 8px;
            margin-bottom: 8px;
            font-size: 10px;
            background: #fafafa;
        }
        .seal-block {
            margin-bottom: 8px;
        }
        .seal-block h4 {
            margin: 0 0 3px;
            font-size: 10px;
            color: var(--accent);
            text-transform: uppercase;
        }
        .seal-block pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-all;
            font-family: Consolas, "Courier New", monospace;
            font-size: 8.5px;
            line-height: 1.3;
            border: 1px solid var(--line);
            padding: 6px;
            background: #fcfcfc;
            max-height: 72px;
            overflow: hidden;
        }
        .pagare {
            border: 1px solid var(--ink);
            padding: 10px;
            margin-top: 10px;
        }
        .pagare h3 {
            margin: 0 0 6px;
            text-align: center;
            letter-spacing: 2px;
            font-size: 13px;
        }
        .pagare p {
            margin: 4px 0;
            text-align: justify;
            font-size: 10px;
        }
        .pagare .firma {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 18px;
        }
        .pagare .firma .slot {
            text-align: center;
            border-top: 1px solid var(--ink);
            padding-top: 4px;
            margin-top: 36px;
            font-size: 10px;
        }
        .footer-note {
            margin-top: 8px;
            text-align: center;
            color: var(--muted);
            font-size: 9px;
        }
        .cancelada-banner {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 8px;
            text-align: center;
            font-weight: 700;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none !important; }
            .sheet {
                box-shadow: none;
                border: none;
                max-width: none;
                padding: 0;
            }
            .seal-block pre { max-height: none; }
        }
        @page { size: letter; margin: 10mm; }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('facturas.index') }}">← Volver a facturas</a>
        <button type="button" class="primary" onclick="window.print()">Imprimir / PDF</button>
    </div>

    <div class="sheet">
        @if(($factura->cancelada ?? 'A') === 'C')
            <div class="cancelada-banner">CFDI CANCELADO</div>
        @endif

        <div class="top">
            <div class="brand">
                @if(!empty($logoUrl))
                    <img src="{{ $logoUrl }}" alt="Logo">
                @endif
                <div>
                    <h1>{{ $emisor['nombre'] ?? '—' }}</h1>
                    <div>{{ $emisor['calle'] ?? '' }}</div>
                    <div>{{ $emisor['colonia'] ?? '' }}</div>
                    <div>
                        {{ $emisor['municipio'] ?? '' }}{{ !empty($emisor['estado']) ? ', ' . $emisor['estado'] : '' }}
                        @if(!empty($emisor['codigo_postal'])) CP: {{ $emisor['codigo_postal'] }} @endif
                    </div>
                    @if(!empty($emisor['correo']))
                        <div>{{ $emisor['correo'] }}</div>
                    @endif
                    @if(!empty($emisor['telefono']))
                        <div>Tel: {{ $emisor['telefono'] }}</div>
                    @endif
                    <div class="rfc">RFC: {{ $emisor['rfc'] ?? '—' }}</div>
                </div>
            </div>

            <div class="meta-box">
                <div class="title">FACTURA</div>
                <div class="row"><span>Folio</span><span><strong>{{ $folio }}</strong></span></div>
                <div class="row"><span>Fecha</span><span>{{ $fechaEmision }}</span></div>
                <div class="row"><span>Lugar y fecha de expedición</span><span>{{ $lugarExpedicion }}</span></div>
            </div>
        </div>

        <div class="cliente">
            <div class="box">
                <div class="label">Receptor</div>
                <div class="name">{{ $receptor['nombre'] ?? '—' }}</div>
                <div>{{ $receptor['direccion_linea1'] ?? $receptor['direccion'] ?? '' }}</div>
                @if(!empty($receptor['direccion_linea2']))
                    <div>{{ $receptor['direccion_linea2'] }}</div>
                @endif
                @if(!empty($receptor['ciudad_estado']))
                    <div>{{ $receptor['ciudad_estado'] }}</div>
                @endif
                <div class="kv"><strong>RFC:</strong> {{ $receptor['rfc'] ?? '—' }}</div>
                <div class="kv"><strong>Domicilio fiscal:</strong> {{ $receptor['codigo_postal'] ?? '—' }}</div>
                <div class="kv"><strong>Régimen fiscal:</strong> {{ $receptor['regimen_fiscal_texto'] ?? '—' }}</div>
            </div>
            <div class="side-fields">
                <div class="field">
                    <div class="k">Orden de compra</div>
                    <div class="v">{{ $ordenCompra ?: '—' }}</div>
                </div>
                <div class="field">
                    <div class="k">Tipo de pago</div>
                    <div class="v">{{ $tipoPago ?? '—' }}</div>
                </div>
                <div class="field">
                    <div class="k">Condiciones de pago</div>
                    <div class="v">{{ $condiciones ?: '—' }}</div>
                </div>
                <div class="field">
                    <div class="k">Fecha</div>
                    <div class="v">{{ $fechaEmisionCorta }}</div>
                </div>
            </div>
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width:12%">Clave</th>
                    <th style="width:34%">Nombre</th>
                    <th style="width:14%">U.med. / SAT</th>
                    <th style="width:10%" class="center">Unidades</th>
                    <th style="width:10%" class="num">Precio</th>
                    <th style="width:10%" class="num">Descto.</th>
                    <th style="width:10%" class="num">Importe</th>
                </tr>
            </thead>
            <tbody>
                @forelse($conceptos as $c)
                    <tr>
                        <td>{{ $c['clave'] ?? '—' }}</td>
                        <td>
                            <strong>{{ $c['nombre'] }}</strong>
                            @if(!empty($c['clave_prod_sat']))
                                <div class="concepto-desc">{{ $c['clave_prod_sat'] }}{{ !empty($c['clave_prod_sat_desc']) ? '/' . $c['clave_prod_sat_desc'] : '' }}</div>
                            @endif
                            @if(!empty($c['descripcion_extra']))
                                <div class="concepto-extra">{{ $c['descripcion_extra'] }}</div>
                            @endif
                        </td>
                        <td>
                            <div>{{ $c['unidad'] ?? '—' }}</div>
                            @if(!empty($c['unidad_sat']) || !empty($c['unidad_desc']))
                                <div class="concepto-desc">
                                    {{ $c['unidad_sat'] ?? '' }}{{ !empty($c['unidad_desc']) ? '/' . $c['unidad_desc'] : '' }}
                                </div>
                            @endif
                        </td>
                        <td class="center">{{ rtrim(rtrim(number_format((float) $c['cantidad'], 2, '.', ''), '0'), '.') }}</td>
                        <td class="num">{{ number_format((float) $c['precio'], 2) }}</td>
                        <td class="num">{{ ((float) ($c['descuento'] ?? 0)) > 0 ? number_format((float) $c['descuento'], 2) : '' }}</td>
                        <td class="num">{{ number_format((float) $c['importe'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="center">Sin conceptos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(!empty($esCredito))
            <div class="credito-box">
                <h3>
                    <span>Condiciones de crédito</span>
                    <span>Tipo de pago: {{ $tipoPago ?? 'Crédito' }}</span>
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th>Tipo de crédito</th>
                            <th>Condiciones de pago</th>
                            <th>Días</th>
                            <th>Método</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $tipoPago ?? 'Crédito' }}</td>
                            <td>{{ $condiciones ?: 'CRÉDITO' }}</td>
                            <td>{{ !empty($diasCredito) ? $diasCredito : '—' }}</td>
                            <td>{{ $metodoPagoLabel }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        @if(!empty($relacionados))
            <div class="relacionados">
                <h3>
                    <span>Documentos relacionados</span>
                    <span>Tipo de relación: {{ $tipoRelacionLabel ?? '—' }}</span>
                </h3>
                @foreach($relacionados as $rel)
                    <div>
                        Folio SAT de los documentos relacionados:
                        <code>{{ $rel['uuid'] ?? '—' }}</code>
                        @if(!empty($rel['folio']))
                            (Folio {{ $rel['folio'] }})
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="totales-wrap">
            <div class="pago-info">
                <div class="line"><strong>Método de pago:</strong> {{ $metodoPagoLabel }}</div>
                <div class="line"><strong>Forma de pago:</strong> {{ $formaPagoLabel }}</div>
                <div class="line"><strong>CFDI 4.0:</strong> {{ $tipoComprobanteLabel }}</div>
                <div class="line"><strong>Uso del CFDI:</strong> {{ $usoCfdiLabel }}</div>
                <div class="letras">({{ $totalEnLetras }})</div>
            </div>
            <div class="totales">
                <table>
                    <tr>
                        <td>Subtotal</td>
                        <td>{{ number_format((float) $subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td>002|IVA 16%</td>
                        <td>{{ number_format((float) $iva, 2) }}</td>
                    </tr>
                    <tr class="total">
                        <td>Total (MXN)</td>
                        <td>{{ number_format((float) $total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="cert">
            Este documento es una representación impresa de un CFDI.
            Folio del SAT: <strong>{{ $uuid }}</strong><br>
            Fecha de certificación: {{ $fechaCertificacion }}
            &nbsp; Certificado del emisor: {{ $certificadoEmisor ?: '—' }}
            &nbsp; Certificado del SAT: {{ $certificadoSat ?: '—' }}<br>
            Régimen fiscal del emisor: {{ $emisor['regimen_fiscal_texto'] ?? '—' }}
            &nbsp; Lugar de expedición: {{ $emisor['codigo_postal'] ?? $lugarExpedicionCp ?? '—' }}
        </div>

        <div class="seal-block">
            <h4>Cadena original del complemento de certificación digital del SAT:</h4>
            <pre>{{ $cadenaOriginal ?: '—' }}</pre>
        </div>
        <div class="seal-block">
            <h4>Sello digital del CFDI:</h4>
            <pre>{{ $selloCfdi ?: '—' }}</pre>
        </div>
        <div class="seal-block">
            <h4>Sello digital del SAT:</h4>
            <pre>{{ $selloSat ?: '—' }}</pre>
        </div>

        <div class="pagare">
            <h3>PAGARÉ</h3>
            <p>
                POR ESTE PAGARÉ DEBO Y PAGARÉ INCONDICIONALMENTE A LA ORDEN DE
                <strong>{{ strtoupper($emisor['nombre'] ?? '') }}</strong>
                EN LA CD DE {{ strtoupper($emisor['municipio'] ?? 'GÓMEZ PALACIO') }}
                {{ strtoupper($emisor['estado'] ?? 'DGO.') }}, SIN NECESIDAD DE PROTESTO,
                LA CANTIDAD DE $ {{ number_format((float) $total, 2) }}.
            </p>
            <p>
                SI ESTA CANTIDAD NO FUESE PAGADA A SU VENCIMIENTO, CAUSARÁ INTERESES MORATORIOS
                DESDE LA FECHA DE VENCIMIENTO HASTA LA FECHA DE SU PAGO TOTAL.
            </p>
            <div class="firma">
                <div>
                    <div><strong>NOMBRE:</strong> {{ $receptor['nombre'] ?? '—' }}</div>
                    <div><strong>DIRECCIÓN:</strong> {{ $receptor['direccion'] ?? '—' }}</div>
                    <div class="slot">ACEPTAMO(AMOS) / DEUDOR</div>
                </div>
                <div>
                    <div><strong>Fecha:</strong> {{ $fechaEmisionCorta }}</div>
                    <div class="slot">FIRMA</div>
                </div>
            </div>
        </div>

        <div class="footer-note">
            Representación impresa personalizada IOHISA — generada desde facturas timbradas
        </div>
    </div>
</body>
</html>
