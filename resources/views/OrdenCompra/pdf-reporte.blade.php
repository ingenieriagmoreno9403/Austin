<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra {{ $ordenCompra->folio }}</title>
    <style>
        @page {
            margin-left: 30px !important;
            margin-right: 30px !important;
            margin-top: 30px !important;
            margin-bottom: 100px !important;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.25;
        }
        .marco {
            border: 0.5px solid #c4c2c2;
            padding: 12px 14px 10px;
        }
        .azul { color: #000080; }
        .email { color: #0000ee; text-decoration: underline; font-size: 8px; }

        .header-emp { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .header-emp td { vertical-align: middle; }
        .logo-iohisa { height: 100px; }
        .logo-pp { height: 34px; }
        .empresa-nombre {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            line-height: 1;
        }
        .empresa-dir {
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            margin-top: 2px;
        }
        .empresa-mails {
            text-align: center;
            margin-top: 3px;
        }

        .titulo-fila { width: 100%; border-collapse: collapse; margin: 8px 0 6px; }
        .titulo-doc {
            font-size: 22px;
            font-weight: bold;
            color: #000080;
        }
        .estatus-doc {
            font-size: 18px;
            font-weight: bold;
            color: #8a8a8a;
            text-align: right;
            text-transform: uppercase;
        }

        .bloque { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .bloque td { vertical-align: top; }
        .caja {
            border: 1px solid #000080;
            margin-bottom: 5px;
        }
        .caja-tit {
            background: #000080;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            padding: 4px 6px;
            text-align: center;
        }
        .caja-tit-izq { text-align: left; }
        .caja-body {
            padding: 6px 6px;
            min-height: 20px;
            font-size: 10px;
        }
        .caja-body strong { font-size: 11px; }
        .party-info {
            width: 100%;
            border-collapse: collapse;
        }
        .party-info td {
            font-size: 9px;
            padding: 2px 0;
            vertical-align: top;
        }
        .party-info .lab {
            width: 28%;
            font-weight: bold;
            color: #000080;
            padding-right: 4px;
            white-space: nowrap;
        }
        .party-info .val {
            width: 72%;
        }
        .party-note {
            font-size: 8px;
            font-style: italic;
            color: #555;
            margin: 4px 0 2px;
        }

        .detalle { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .detalle th {
            background: #000080;
            color: #fff;
            font-size: 10px;
            font-weight: bold;
            padding: 5px 4px;
            text-align: center;
            border: 1px solid #000080;
        }
        .detalle td {
            font-size: 10px;
            padding: 5px 4px;
            border: 1px solid #cfcfcf;
            border-top: none;
            vertical-align: top;
        }
        .detalle .num { text-align: right; white-space: nowrap; }
        .detalle .cen { text-align: center; }
        .detalle .obs-linea {
            font-size: 8px;
            font-style: italic;
            color: #444;
            margin-top: 2px;
        }

        .pie-tabla { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .pie-tabla td { vertical-align: top; }
        .obs {
            font-size: 10px;
            padding-right: 10px;
        }
        .total-en-letras {
            font-size: 10px;
            font-style: italic;
            margin-bottom: 8px;
        }
        .meta-grid {
            width: 72%;
            max-width: 280px;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .meta-grid > tbody > tr > td {
            vertical-align: top;
        }
        .meta-grid .caja-body {
            font-size: 10px;
            text-align: center;
            min-height: 24px;
        }
        .meta-grid .caja-tit {
            font-size: 10px;
        }

        .totales {
            width: 100%;
            border-collapse: collapse;
        }
        .totales td {
            padding: 2px 4px;
            font-size: 10px;
        }
        .totales .lab { text-align: left; padding-right: 8px; }
        .totales .amt { text-align: right; font-weight: bold; width: 90px; white-space: nowrap; }
        .totales .total-row td {
            font-size: 12px;
            font-weight: bold;
            padding-top: 5px;
            border-top: 0.5px solid #888;
        }

        .firma {
            margin-top: 18px;
            text-align: center;
            font-size: 10px;
        }
        .firma .atentos { margin-top: 70px; }
        .firma .nombre { font-weight: bold; text-transform: uppercase; }
        .firma .cargo { margin-top: 2px; font-size: 9px; color: #444; }

        .billing {
            font-size: 9px;
            margin-bottom: 4px;
        }
        .billing-tit {
            font-weight: bold;
            margin-bottom: 3px;
            color: #000080;
        }
        .page-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -85px;
            height: 78px;
            border-top: 0.5px solid #aaa;
            padding: 6px 4px 0;
            background: #fff;
        }
        .footer-gen {
            font-size: 8px;
            color: #666;
            border-top: 0.5px solid #ddd;
            padding-top: 4px;
            width: 100%;
            border-collapse: collapse;
        }
        .footer-gen td {
            vertical-align: middle;
            font-size: 8px;
            color: #666;
        }
        .footer-gen .pagina {
            text-align: right;
            white-space: nowrap;
        }
    </style>
</head>
<body>
@php
    $logoIohisa = public_path('Images/IOHISA.png');
    $logoPp = public_path('Images/performance-pipe.png');
    $tieneLogoPp = is_file($logoPp);

    $estatusVisual = \App\Models\OrdenCompra::$estados[$ordenCompra->estado] ?? ucfirst((string) $ordenCompra->estado);
    $monedaCodigo = strtoupper((string) ($ordenCompra->tipo_moneda ?? 'MXN'));

    $nombreComprador = '—';
    if ($ordenCompra->comprador) {
        $nombreComprador = trim(collect([
            $ordenCompra->comprador->primer_nombre,
            $ordenCompra->comprador->segundo_nombre,
            $ordenCompra->comprador->apellido_paterno,
            $ordenCompra->comprador->apellido_materno,
        ])->filter()->implode(' '));
        $nombreComprador = $nombreComprador !== '' ? strtoupper($nombreComprador) : '—';
    }

    $nombreAtencion = '—';
    $puestoAtencion = '—';
    if ($ordenCompra->personaAtencion) {
        $nombreAtencion = trim(collect([
            $ordenCompra->personaAtencion->primer_nombre,
            $ordenCompra->personaAtencion->segundo_nombre,
            $ordenCompra->personaAtencion->apellido_paterno,
            $ordenCompra->personaAtencion->apellido_materno,
        ])->filter()->implode(' '));
        $nombreAtencion = $nombreAtencion !== '' ? strtoupper($nombreAtencion) : '—';
        $puestoAtencion = strtoupper((string) ($ordenCompra->personaAtencion->puesto ?? '—')) ?: '—';
    }

    $puestoComprador = strtoupper((string) ($puestoComprador ?? 'COMPRAS')) ?: 'COMPRAS';
    $empresaComprador = $empresaComprador ?? null;
    $razonSocialComprador = strtoupper(trim((string) ($empresaComprador?->nombre_empresa ?? ''))) ?: '—';
    $rfcEmpresaComprador = trim((string) ($empresaComprador?->rfc ?? '')) ?: '—';
    $direccionEmpresaComprador = trim((string) ($empresaComprador?->direccion_fiscal ?? '')) ?: '—';
    $telefonoEmpresaComprador = trim((string) (
        data_get($empresaComprador, 'telefono')
        ?: data_get($empresaComprador, 'telefono_empresa')
        ?: ($ordenCompra->comprador->telefono ?? '')
    )) ?: '—';
    $correoEmpresaComprador = trim((string) (
        data_get($empresaComprador, 'correo')
        ?: data_get($empresaComprador, 'email')
        ?: ($ordenCompra->comprador->correo ?? '')
    )) ?: '—';

    $condicionesPago = trim(collect([
        $monedaCodigo,
        isset($ordenCompra->proveedor->modo_pago) ? strtoupper((string) $ordenCompra->proveedor->modo_pago) : null,
    ])->filter()->implode(' · '));
    if ($condicionesPago === '') {
        $condicionesPago = '—';
    }

    $fechaCorta = $fechaCorta ?? (\Carbon\Carbon::parse($ordenCompra->fecha_creacion)->locale('es')->translatedFormat('d/M./Y'));
    $vigenciaCorta = $vigenciaCorta ?? (\Carbon\Carbon::parse($ordenCompra->fecha_limite)->locale('es')->translatedFormat('d/M./Y'));
@endphp

<div class="marco">
    {{-- Encabezado empresa --}}
    <table class="header-emp">
        <tr>
            <td style="width:18%;text-align:center;">
                @if (is_file($logoIohisa))
                    <img src="{{ $logoIohisa }}" class="logo-iohisa" alt="IOHISA">
                @endif
            </td>
            <td style="width:64%; text-align:center;">
                <div class="empresa-nombre">Ingeniería y Obras<br>Hidráulicas, S.A. de C.V.</div>
                <div class="empresa-dir">Roble 2137 Ote. Col. Brittingham Gómez Palacio Dgo.</div>
                <div class="empresa-dir">Tels. 714-84-27, 714-84-40, 714-84-60</div>
                <div class="empresa-mails">
                    <span class="email">ventas@iohisa.com</span>
                    &nbsp;<span class="email">compras@iohisa.com</span>
                    &nbsp;<span class="email">ventas1@iohisa.com</span>
                </div>
            </td>
            <td style="width:18%; text-align:right;">
                <!-- @if ($tieneLogoPp)
                    <img src="{{ $logoPp }}" class="logo-pp" alt="Performance Pipe">
                @else
                    <div style="font-size:9px; font-weight:bold; line-height:1.1; text-align:right;">
                        PERFORMANCE<br><span style="color:#c00;">PIPE</span>
                    </div>
                @endif -->
            </td>
        </tr>
    </table>

    <table class="titulo-fila">
        <tr>
            <td class="titulo-doc">Orden de Compra</td>
            <td class="estatus-doc">{{ $estatusVisual }}</td>
        </tr>
    </table>

    <table class="bloque">
        <tr>
            <td style="width:25%; padding-right:3px;">
                <div class="caja">
                    <div class="caja-tit">Fecha</div>
                    <div class="caja-body" style="text-align:center;">{{ $fechaCorta }}</div>
                </div>
            </td>
            <td style="width:25%; padding-right:3px; padding-left:3px;">
                <div class="caja">
                    <div class="caja-tit">Folio</div>
                    <div class="caja-body" style="text-align:center;">{{ $ordenCompra->folio }}</div>
                </div>
            </td>
            <td style="width:25%; padding-right:3px; padding-left:3px;">
                <div class="caja">
                    <div class="caja-tit">Condiciones</div>
                    <div class="caja-body" style="text-align:center;">{{ $condicionesPago }}</div>
                </div>
            </td>
            <td style="width:25%; padding-left:3px;">
                <div class="caja">
                    <div class="caja-tit">Vigencia</div>
                    <div class="caja-body" style="text-align:center;">{{ $vigenciaCorta }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Comprador (izq) / Proveedor (der) --}}
    <table class="bloque">
        <tr>
            <td style="width:50%; padding-right:4px;">
                <div class="caja">
                    <div class="caja-tit">Comprador</div>
                    <div class="caja-body">
                        <table class="party-info">
                            <tr>
                                <td class="lab">Nombre:</td>
                                <td class="val">{{ $nombreComprador }}</td>
                            </tr>
                            <tr>
                                <td class="lab">Puesto:</td>
                                <td class="val">{{ $puestoComprador }}</td>
                            </tr>
                        </table>
                        <div class="party-note">(Datos de la empresa)</div>
                        <table class="party-info">
                            <tr>
                                <td class="lab">Razón social:</td>
                                <td class="val">{{ $razonSocialComprador }}</td>
                            </tr>
                            <tr>
                                <td class="lab">RFC:</td>
                                <td class="val">{{ $rfcEmpresaComprador ?: '—' }}</td>
                            </tr>
                            <tr>
                                <td class="lab">Dirección:</td>
                                <td class="val">{{ $direccionEmpresaComprador ?: '—' }}</td>
                            </tr>
                            <tr>
                                <td class="lab">Teléfono:</td>
                                <td class="val">{{ $telefonoEmpresaComprador ?: '—' }}</td>
                            </tr>
                            <tr>
                                <td class="lab">Correo:</td>
                                <td class="val">{{ $correoEmpresaComprador ?: '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
            <td style="width:50%; padding-left:4px;">
                <div class="caja">
                    <div class="caja-tit">Proveedor</div>
                    <div class="caja-body">
                        <table class="party-info">
                            <tr>
                                <td class="lab">Nombre:</td>
                                <td class="val">{{ $nombreAtencion }}</td>
                            </tr>
                            <tr>
                                <td class="lab">Puesto:</td>
                                <td class="val">{{ $puestoAtencion }}</td>
                            </tr>
                        </table>
                        <div class="party-note">(Datos de la empresa)</div>
                        <table class="party-info">
                            <tr>
                                <td class="lab">Razón social:</td>
                                <td class="val">{{ strtoupper($ordenCompra->proveedor->nombre ?? '—') }}</td>
                            </tr>
                            <tr>
                                <td class="lab">RFC:</td>
                                <td class="val">{{ $ordenCompra->proveedor->rfc ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="lab">Dirección:</td>
                                <td class="val">{{ $ordenCompra->proveedor->direccion ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="lab">Teléfono:</td>
                                <td class="val">{{ $ordenCompra->proveedor->telefono ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="lab">Correo:</td>
                                <td class="val">{{ $ordenCompra->proveedor->otrosconceptos1 ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @if (!empty($ordenCompra->nombre))
        <div style="margin: 4px 0 2px; font-size: 10px;">
            <strong>Concepto:</strong> {{ $ordenCompra->nombre }}
        </div>
    @endif

    {{-- Detalle --}}
    <table class="detalle">
        <thead>
            <tr>
                <th style="width:5%;">No.</th>
                <th style="width:12%;">Artículo</th>
                <th style="width:39%;">Nombre</th>
                <th style="width:8%;">U.med.</th>
                <th style="width:10%;">Unidades</th>
                <th style="width:13%;">Precio</th>
                <th style="width:13%;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detalles as $index => $detalle)
                <tr>
                    <td class="cen">{{ $index + 1 }}</td>
                    <td>{{ $detalle['sku'] ?? '—' }}</td>
                    <td>
                        {{ $detalle['producto'] }}
                        @if (!empty($detalle['observaciones']))
                            <div class="obs-linea">Obs.: {{ $detalle['observaciones'] }}</div>
                        @endif
                    </td>
                    <td class="cen">{{ $detalle['unidad'] }}</td>
                    <td class="num">{{ number_format((float) $detalle['cantidad'], 2) }}</td>
                    <td class="num">{{ number_format((float) $detalle['costo_unitario'], 2) }}</td>
                    <td class="num">{{ number_format((float) $detalle['subtotal'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="cen" style="padding:12px;">Sin partidas</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="pie-tabla">
        <tr>
            <td style="width:58%;">
                <div class="obs">
                    @if (!empty($totalEnLetras))
                        <div class="total-en-letras">({{ $totalEnLetras }})</div>
                    @endif
                    @if (!empty($ordenCompra->observaciones))
                        <div style="margin-bottom:6px;"><strong>Observaciones:</strong> {{ $ordenCompra->observaciones }}</div>
                    @endif
                </div>
                <table class="meta-grid">
                    <tr>
                        <td>
                            <div class="caja">
                                <div class="caja-tit">Condiciones de entrega</div>
                                <div class="caja-body">
                                    {{ $ordenCompra->condiciones_entrega ?: '—' }}
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="caja">
                                <div class="caja-tit">Fecha tentativa de pago</div>
                                <div class="caja-body">
                                    @if (!empty($ordenCompra->fecha_tentativa_pago))
                                        {{ \Carbon\Carbon::parse($ordenCompra->fecha_tentativa_pago)->locale('es')->translatedFormat('d/M./Y') }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @if (!empty($ordenCompra->referencia_licitacion_id))
                    <tr>
                        <td>
                            <div class="caja">
                                <div class="caja-tit">Referencia comparativa</div>
                                <div class="caja-body">
                                    #{{ $ordenCompra->referencia_licitacion_id }}
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
            <td style="width:42%;">
                <table class="totales">
                    <tr>
                        <td class="lab">Subtotal</td>
                        <td class="amt">{{ number_format((float) $subtotal, 2) }}</td>
                    </tr>
                    @if ($ordenCompra->iva_aplicado == 1)
                    <tr>
                        <td class="lab">IVA 16%</td>
                        <td class="amt">{{ number_format((float) $iva, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="lab">Total ({{ $monedaCodigo }})</td>
                        <td class="amt">{{ number_format((float) $total, 2) }}</td>
                    </tr>
                </table>

                <div class="firma">
                    <div class="atentos">Atentamente</div>
                    <div class="nombre">{{ $nombreComprador !== '—' ? $nombreComprador : 'ING. UZIEL PEREZ DE LA FUENTE' }}</div>
                    <div class="cargo">Autoriza</div>
                </div>
            </td>
        </tr>
    </table>
</div>

<footer class="page-footer">
    <div class="billing">
        <div class="billing-tit">* Área para comentarios / Facturación</div>
        @if (is_null($ordenCompra->descripcion_detalle) || trim((string) $ordenCompra->descripcion_detalle) === '')
            <div>
                <strong>FACTURA A:</strong>
                INGENIERÍA Y OBRAS HIDRÁULICAS, S.A. DE C.V.
                — Roble 2137 Ote. Col. Brittingham, Gómez Palacio, Dgo.
                — Correo compras: compras@iohisa.com
            </div>
        @else
            <div style="text-align: justify;">
                {!! nl2br(e($ordenCompra->descripcion_detalle)) !!}
            </div>
        @endif
    </div>

    <table class="footer-gen">
        <tr>
            <td>
                Generado por: {{ $usuario ?? 'Sistema' }}
                &nbsp;·&nbsp;
                Fecha de generación: {{ $fechaGeneracion ?? now()->format('d/m/Y H:i:s') }}
                &nbsp;·&nbsp;
                Documento generado automáticamente
            </td>
            <td class="pagina" style="width:90px;"></td>
        </tr>
    </table>
</footer>

<script type="text/php">
    if (isset($pdf)) {
        $pdf->page_script('
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $pdf->text(520, 755, "Pág $PAGE_NUM de $PAGE_COUNT", $font, 8);
        ');
    }
</script>
</body>
</html>
