<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización {{ $cotizacion->folio }}</title>
    <style>
        @page { margin: 18px 20px; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.25;
        }
        .marco {
            border: 1.2px solid #000;
            padding: 14px 16px 12px;
            min-height: 980px;
        }
        .azul { color: #000080; }
        .azul-bg { background: #000080; color: #fff; }
        .gris { color: #6b6b6b; }
        .email { color: #0000ee; text-decoration: underline; font-size: 8px; }

        .header-emp { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .header-emp td { vertical-align: middle; }
        .logo-iohisa { height: 58px; }
        .logo-pp { height: 34px; }
        .empresa-nombre {
            font-family: DejaVu Serif, Times New Roman, serif;
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.2px;
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
            font-size: 20px;
            font-weight: bold;
            color: #8a8a8a;
            text-align: right;
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
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .meta-grid > tbody > tr > td {
            vertical-align: top;
        }
        .meta-grid .caja-body {
            font-size: 11px;
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
        .totales .lab { text-align: right; padding-right: 8px; }
        .totales .amt { text-align: right; font-weight: bold; width: 78px; white-space: nowrap; }
        .totales .total-row td {
            font-size: 12px;
            font-weight: bold;
            padding-top: 5px;
            border-top: 1.5px solid #000;
        }

        .firma {
            margin-top: 28px;
            text-align: center;
            font-size: 10px;
        }
        .firma .atentos { margin-bottom: 28px; }
        .firma .nombre { font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>
@php
    $monedaCodigo = $cotizacion->moneda->abreviacion ?? ($cotizacion->moneda->codigo ?? 'MXN');
    $estatusVisual = match ($cotizacion->estatus) {
        'CONVERTIDA', 'ACEPTADA' => 'CERRADA',
        'ENVIADA' => 'ENVIADA',
        'BORRADOR' => 'BORRADOR',
        'RECHAZADA' => 'RECHAZADA',
        'VENCIDA' => 'VENCIDA',
        default => $cotizacion->estatus,
    };
    $logoIohisa = public_path('Images/IOHISA.png');
    $logoPp = public_path('Images/performance-pipe.png');
    $tieneLogoPp = is_file($logoPp);
@endphp

<div class="marco">
    {{-- Encabezado empresa --}}
    <table class="header-emp">
        <tr>
            <td style="width:18%;">
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
                @if ($tieneLogoPp)
                    <img src="{{ $logoPp }}" class="logo-pp" alt="Performance Pipe">
                @else
                    <div style="font-size:9px; font-weight:bold; line-height:1.1; text-align:right;">
                        PERFORMANCE<br><span style="color:#c00;">PIPE</span>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <table class="titulo-fila">
        <tr>
            <td class="titulo-doc">Cotizacion</td>
            <td class="estatus-doc">{{ $estatusVisual }}</td>
        </tr>
    </table>

    {{-- Cliente / Atención / Fecha Folio --}}
    <table class="bloque">
        <tr>
            <td style="width:58%; padding-right:6px;">
                <div class="caja" style="min-height:92px;">
                    <div class="caja-tit caja-tit-izq">Cliente</div>
                    <div class="caja-body">
                        <strong>{{ strtoupper($cliente->razon_social ?: ($cliente->nombre ?? '—')) }}</strong><br>
                        @if (!empty($direccionCliente))
                            {{ $direccionCliente }}<br>
                        @endif
                        @if (!empty($cliente->rfc))
                            RFC: {{ $cliente->rfc }}
                        @endif
                        @if (!empty($cliente->telefono))
                            <div style="margin-top:4px;">Teléfono : {{ $cliente->telefono }}</div>
                        @endif
                    </div>
                </div>
            </td>
            <td style="width:42%;">
                <div class="caja">
                    <div class="caja-tit">Atención</div>
                    <div class="caja-body" style="min-height:22px;">
                        {{ $atencionNombre ?? '—' }}
                    </div>
                </div>
                <table class="bloque" style="width:100%;">
                    <tr>
                        <td style="width:50%; padding-right:3px;">
                            <div class="caja">
                                <div class="caja-tit">Fecha</div>
                                <div class="caja-body" style="text-align:center;">{{ $fechaCorta }}</div>
                            </div>
                        </td>
                        <td style="width:50%; padding-left:3px;">
                            <div class="caja">
                                <div class="caja-tit">Folio</div>
                                <div class="caja-body" style="text-align:center;">{{ $cotizacion->folio }}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="bloque">
        <tr>
            <td style="width:29%; padding-right:3px;">
                <div class="caja">
                    <div class="caja-tit">Vigencia</div>
                    <div class="caja-body" style="text-align:center;">
                        {{ $vigenciaCorta ?? '—' }}
                    </div>
                </div>
            </td>
            <td style="width:29%; padding-right:3px; padding-left:3px;">
                <div class="caja">
                    <div class="caja-tit">Condiciones</div>
                    <div class="caja-body" style="text-align:center;">
                        {{ $condiciones ?? '—' }}
                    </div>
                </div>
            </td>
            <td style="width:42%; padding-left:3px;">
                <div class="caja">
                    <div class="caja-tit">Vendedor</div>
                    <div class="caja-body" style="text-align:center;">
                        {{ strtoupper($vendedor->name ?? '—') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Detalle --}}
    <table class="detalle">
        <thead>
            <tr>
                <th style="width:12%;">Artículo</th>
                <th style="width:38%;">Nombre</th>
                <th style="width:8%;">U.med.</th>
                <th style="width:10%;">Unidades</th>
                <th style="width:10%;">Precio</th>
                <th style="width:9%;">Descto.</th>
                <th style="width:13%;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detalles as $detalle)
                @php
                    $producto = $detalle->producto;
                    $sku = $producto->sku ?? '';
                    $nombre = $detalle->descripcion ?: ($producto->nombre ?? 'Producto');
                    $unidad = $producto->unidadMedida->nombre
                        ?? $producto->unidadMedida->abreviacion
                        ?? 'pieza';
                @endphp
                <tr>
                    <td>{{ $sku ?: '—' }}</td>
                    <td>{{ $nombre }}</td>
                    <td class="cen">{{ $unidad }}</td>
                    <td class="num">{{ number_format((float) $detalle->cantidad, 2) }}</td>
                    <td class="num">{{ number_format((float) $detalle->precio_unitario, 2) }}</td>
                    <td class="num">
                        @if ((float) $detalle->descuento > 0)
                            {{ number_format((float) $detalle->descuento, 2) }}
                        @endif
                    </td>
                    <td class="num">{{ number_format((float) $detalle->importe, 2) }}</td>
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
                    @php
                        $obsPdf = trim((string) ($cotizacion->observaciones ?? ''));
                        if (!empty($fleteEnPrecios) && $obsPdf !== '') {
                            // No mostrar notas de flete disperso en el PDF.
                            $obsPdf = preg_replace('/^\s*EL\s+FLETE\s+VA\s+ENTRE\s+LOS\s+PRODUCTOS\s*$/imu', '', $obsPdf);
                            $obsPdf = preg_replace('/\(?\s*incluido\s+en\s+precios\s*\)?/iu', '', $obsPdf);
                            $obsPdf = trim(preg_replace("/\n{2,}/", "\n", $obsPdf));
                        }
                    @endphp
                    @if ($obsPdf !== '')
                        <div style="margin-bottom:6px;">{{ $obsPdf }}</div>
                    @endif
                </div>
                <table class="meta-grid">
                    <tr>
                        @if (empty($fleteEnPrecios))
                        <td style="width:34%; padding-right:3px;">
                            <div class="caja">
                                <div class="caja-tit">Flete</div>
                                <div class="caja-body">
                                    {{ $cotizacion->tipoFlete->nombre ?? '—' }}
                                    @if (!empty($mostrarConceptoFlete))
                                        <div style="margin-top:2px;">{{ number_format((float) $importeFlete, 2) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        @endif
                        <td style="width:{{ empty($fleteEnPrecios) ? '33%' : '50%' }}; padding-right:3px; {{ empty($fleteEnPrecios) ? 'padding-left:3px;' : '' }}">
                            <div class="caja">
                                <div class="caja-tit">Tiempo de Entrega</div>
                                <div class="caja-body">
                                    {{ $cotizacion->tiempo_entrega ?: '—' }}
                                </div>
                            </div>
                        </td>
                        <td style="width:{{ empty($fleteEnPrecios) ? '33%' : '50%' }}; padding-left:3px;">
                            <div class="caja">
                                <div class="caja-tit">No. Solicitud</div>
                                <div class="caja-body">
                                    {{ $cotizacion->no_solicitud ?? '—' }}
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width:42%;">
                <table class="totales">
                    <tr>
                        <td class="lab">{{ !empty($ivaEnPrecios) ? 'Subtotal (c/IVA)' : 'Subtotal' }}</td>
                        <td class="amt">{{ number_format((float) ($subtotalPdf ?? $cotizacion->subtotal), 2) }}</td>
                    </tr>
                    @if ((float) ($descuentoPdf ?? $cotizacion->descuento) > 0)
                        <tr>
                            <td class="lab">Descuento</td>
                            <td class="amt">{{ number_format((float) ($descuentoPdf ?? $cotizacion->descuento), 2) }}</td>
                        </tr>
                    @endif
                    @if (!empty($mostrarConceptoIva) || (!isset($mostrarConceptoIva) && empty($ivaEnPrecios)))
                    <tr>
                        <td class="lab">IVA {{ rtrim(rtrim(number_format((float) ($porcentajeIva ?? $cotizacion->porcentaje_iva ?? 16), 2, '.', ''), '0'), '.') }}%</td>
                        <td class="amt">{{ number_format((float) $cotizacion->iva, 2) }}</td>
                    </tr>
                    @endif
                    @if (!empty($mostrarConceptoFlete))
                    <tr>
                        <td class="lab">Flete</td>
                        <td class="amt">{{ number_format((float) $importeFlete, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="lab">Total ({{ $monedaCodigo }})</td>
                        <td class="amt">{{ number_format((float) $cotizacion->total, 2) }}</td>
                    </tr>
                </table>

                <div class="firma">
                    <div class="atentos">Atentamente</div>
                    <div class="nombre">{{ strtoupper($vendedor->name ?? '') }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
