@php
    $meses = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.', 'dic.'];
    $fechaFormateada = $fecha;
    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $fecha, $partes)) {
        $fechaFormateada = str_pad($partes[1], 2, '0', STR_PAD_LEFT) . '/' . $meses[(int) $partes[2] - 1] . '/' . $partes[3];
    } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $fecha, $partes)) {
        $fechaFormateada = str_pad($partes[3], 2, '0', STR_PAD_LEFT) . '/' . $meses[(int) $partes[2] - 1] . '/' . $partes[1];
    }

    $noPolizaDisplay = preg_replace('/^(CU|CJ)00/i', '', (string) $no_poliza);
    $tipoEtiqueta = $tipo_etiqueta ?? strtoupper($tipoMovimiento ?? 'DIARIO');
    $asientos = $asientos ?? [];
    $totalDebe = 0;
    $totalHaber = 0;

    foreach ($asientos as $asiento) {
        $totalDebe += (float) ($asiento['debe'] ?? 0);
        $totalHaber += (float) ($asiento['haber'] ?? 0);
    }

    $numAsientos = count($asientos);

    $asientos = array_map(function ($asiento) {
        if (!array_key_exists('nombre', $asiento) && !empty($asiento['cuenta'])) {
            $asiento['nombre'] = $asiento['cuenta'];
            $asiento['cuenta'] = '';
        }

        return $asiento;
    }, $asientos);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Póliza {{ $no_poliza }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: Tahoma, Arial, Helvetica, sans-serif;
            font-size: 8pt;
            color: #000000;
            margin: 0;
            padding: 8px 8px 100px;
            background: #ffffff;
        }

        @page {
            margin: 18px;
            size: letter portrait;
        }

        .page-border {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 1px solid #000000;
        }

        .page-content {
            padding: 0;
        }

        footer {
            position: fixed;
            bottom: 8px;
            left: 8px;
            right: 8px;
            font-size: 10px !important;
            padding: 0 8px;
        }

        .encabezado {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .encabezado td {
            vertical-align: top;
            padding: 0;
            border: none;
        }

        .rfc {
            text-align: center;
            font-size: 8pt;
            padding-bottom: 2px;
        }

        .empresa {
            text-align: center;
            font-size: 12pt;
            font-weight: normal;
            text-transform: uppercase;
            padding-bottom: 8px;
        }

        .fecha-box {
            width: 185px;
            border-collapse: collapse;
            margin-left: auto;
        }

        .fecha-box th {
            border: 1px solid #000000;
            padding: 3px 8px;
            text-align: center;
            font-size: 11pt;
            background: #1d1d1d;
            color: #ffffff;
        }

        .fecha-box td {
            border: 1px solid #000000;
            padding: 3px 8px;
            text-align: center;
            font-size: 11pt;
            background: #ffffff;
            letter-spacing: 0.8px;
        }

        .fecha-box th {
            font-weight: bold;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .fecha-box td {
            font-weight: normal;
            height: 18px;
        }

        .contenido {
            width: 100%;
            border-collapse: collapse;
        }

        .contenido > tbody > tr > td {
            vertical-align: top;
            padding: 0;
            border: none;
        }

        .tipo-izq {
            padding-top: 8px;
            padding-right: 8px;
        }

        .poliza-titulo {
            font-size: 25pt;
            font-weight: bold;
            margin: 0;
        }

        .diario-titulo {
            font-size: 18pt;
            font-weight: bold;
            margin: 0;
        }

        .poliza-table {
            width: 100%;
            border-collapse: collapse;
        }

        .poliza-table th{
            border: 1px solid #000000;
            padding: 2px 4px;
            font-size: 8pt;
            vertical-align: middle;
            background: #000000;
            color: #ffffff;
        }

        .poliza-table td {
            border: 1px solid #ffffff;
            padding: 2px 4px;
            font-size: 8pt;
            vertical-align: middle;
            background: #ffffff;
            color: #000000;
        }

        .poliza-table th {
            font-weight: bold;
            text-align: center;
            height: 13px;
        }

        .col-cuenta { width: 18%; }
        .col-nombre { width: 41%; }
        .col-referencia { width: 15%; text-align: center; }
        .col-debe { width: 10%; text-align: right; }
        .col-haber { width: 16%; text-align: right; }

        .poliza-table td.col-referencia {
            text-align: left;
        }

        .totales-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        .totales-wrap td {
            border: none;
            padding: 2px 4px;
            font-size: 8pt;
            vertical-align: top;
        }

        .totales-asientos {
            width: 59%;
        }

        .totales-label {
            width: 15%;
            text-align: left;
            padding-left: 0;
        }

        .totales-debe,
        .totales-haber {
            width: 15%;
            text-align: right;
            border-top: 1px solid #000000;
            font-weight: normal;
        }

        .totales-haber {
            width: 16%;
        }

        .pasivo-compra {
            font-size: 8pt;
            padding-left: 2px;
            margin: 0 0 8px;
        }

        .footer-hr {
            border: none;
            border-top: 1px solid #000000;
            margin: 0 0 6px;
        }

        .firmas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }


        .firmas td {
            border: 1px solid #000000;
            height: 1px;
            padding: 0;
            text-align: center;
            font-size: 8pt;
            padding: 0 6px 4px;        
        }


        .nota  th{
            border: 1px solid #000000;
            padding: 2px 4px;
            font-size: 8pt;
            vertical-align: middle;
            background: #000000;
            color: #ffffff;
            
        }

        .encabezado td {
            border: none;
        }
    </style>
</head>
<body>
    <div class="page-border"></div>

    <div class="page-content">
        <table class="encabezado">
            <tr>
                <td class="empresa" colspan="2">{{ $nombre_empresa }}</td>
            </tr>
            <tr>
                <td class="rfc" colspan="2">{{ $rfc ?? '' }}</td>
            </tr>
            <tr>
                <td class="tipo-izq">
                    <div class="poliza-titulo">Póliza Diario</div>
                </td>

                <td style="text-align: right; padding-top: 2px;">
                    <table class="fecha-box">
                        <tr>
                            <th style="width: 52%;">Fecha</th>
                            <th style="width: 48%;">Número</th>
                        </tr>
                        <tr>
                            <td>{{ $fechaFormateada }}</td>
                            <td>{{ $noPolizaDisplay }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

    

        <table class="contenido">
            <tr>
                
                <td>
                    <table class="poliza-table">
                        <thead>
                            <tr>
                                <th class="col-cuenta">Cuenta</th>
                                <th class="col-nombre">Nombre</th>
                                <th class="col-referencia">Referencia</th>
                                <th class="col-debe">Debe</th>
                                <th class="col-haber">Haber</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($asientos as $asiento)
                                <tr>
                                    <td class="col-cuenta">{{ $asiento['cuenta'] ?? '' }}</td>
                                    <td class="col-nombre">{{ $asiento['nombre'] ?? '' }}</td>
                                    <td class="col-referencia">{{ $asiento['referencia'] ?? '' }}</td>
                                    <td class="col-debe">
                                        @if (!empty($asiento['debe']))
                                            {{ number_format((float) $asiento['debe'], 2) }}
                                        @endif
                                    </td>
                                    <td class="col-haber">
                                        @if (!empty($asiento['haber']))
                                            {{ number_format((float) $asiento['haber'], 2) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <table class="totales-wrap">
                        <tr>
                            <td class="totales-asientos">{{ $numAsientos }} {{ $numAsientos === 1 ? 'asiento' : 'asientos' }}</td>
                            <td class="totales-label">Sumas iguales</td>
                            <td class="totales-debe"><hr>{{ number_format($totalDebe, 2) }}</td>
                            <td class="totales-haber"><hr>{{ number_format($totalHaber, 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="nota" style="width: 40%; border-collapse: collapse; margin-top: 8px;">
            <tr>
                <th>Nota</th>   
            </tr>
            <tr>
                <td>{{ $nota ?? '' }}</td>
            </tr>
        </table>
    </div>

    <footer>
        <hr class="footer-hr">
        
        <div class="pasivo-compra">{{ $tipoEtiqueta }}</div>
        
        <table class="firmas">
            <tr>
                <td style="width: 25%;">Hecho por</td>
                <td style="width: 25%;">Revisado</td>
                <td style="width: 25%;">Autorizado</td>
                <td style="width: 25%;">Auxiliares</td>
            </tr>
            <tr>
                <td>{{ $hecho_por ?? $usuario }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </footer>
</body>
</html>
