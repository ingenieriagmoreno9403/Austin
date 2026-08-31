<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cheques de nómina</title>
    <style>
        @page {
            size: {{ $anchoCm }}cm {{ $altoCm }}cm;
            margin: 0.5cm 0.85cm 0.5cm 0.5cm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5px;
            color: #1f2937;
            margin: 0;
            padding: 0.1cm 0.25cm 0.1cm 0.1cm;
            line-height: 1.2;
        }

        .cheque-page {
            padding: 0.08cm 0.2cm 0.08cm 0.08cm;
            page-break-inside: avoid;
        }

        .cheque {
            width: 96%;
            max-width: 96%;
            border: 1px solid #111;
            padding: 0.35cm 0.5cm 0.4cm 0.38cm;
        }

        .color-label {
            color: #7b96b7;
            font-weight: bold;
        }

        .color-titulo {
            color: #5a7ea8;
            font-weight: bold;
        }

        .tabla-layout {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabla-layout td {
            vertical-align: top;
            padding: 0;
            border: none;
        }

        .cheque-titulo {
            font-size: 18px;
            font-weight: bold;
            color: #5a7ea8;
            margin: 0;
            padding: 0;
            line-height: 1;
        }

        .fecha-wrap {
            width: 1.95cm;
            display: inline-block;
        }

        .fecha-head {
            background: #5a7ea8;
            color: #fff;
            text-align: center;
            font-size: 7.5px;
            font-weight: bold;
            padding: 0.06cm 0.1cm;
            border: 1px solid #4a6f97;
        }

        .fecha-valor {
            border: 1px solid #9ca3af;
            border-top: none;
            text-align: center;
            font-size: 7.5px;
            padding: 0.08cm 0.05cm;
            background: #fff;
        }

        .campo-fila {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.1cm;
        }

        .campo-fila td {
            vertical-align: middle;
            padding: 0.02cm 0;
            border: none;
        }

        .campo-etiqueta {
            width: 2.1cm;
            color: #7b96b7;
            font-weight: bold;
            font-size: 8px;
            white-space: nowrap;
            padding-right: 0.08cm;
        }

        .campo-caja {
            border: 1px solid #9ca3af;
            background: #fff;
            padding: 0.06cm 0.1cm;
            font-size: 8px;
            min-height: 0.42cm;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .campo-caja-id {
            width: 1.1cm;
            text-align: center;
            font-weight: bold;
        }

        .campo-caja-nombre {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .campo-caja-vacio {
            min-height: 0.42cm;
            border-bottom: 1px solid #6b7280;
            border-top: none;
            border-left: none;
            border-right: none;
            padding: 0.06cm 0;
        }

        .col-izq {
            width: 58%;
            padding-right: 0.12cm;
        }

        .col-der {
            width: 42%;
            vertical-align: bottom !important;
            padding-top: 0.55cm;
            padding-right: 0.08cm;
        }

        .monto-fila {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.12cm;
        }

        .monto-fila td {
            vertical-align: bottom;
            padding: 0;
            border: none;
        }

        .monto-etiqueta {
            width: 48%;
            color: #7b96b7;
            font-weight: bold;
            font-size: 7.5px;
            text-align: right;
            padding-right: 0.08cm;
            white-space: nowrap;
        }

        .monto-valor {
            width: 52%;
            text-align: right;
            font-size: 8.5px;
            font-weight: bold;
            border-bottom: 1px solid #374151;
            padding: 0 0.1cm 0.04cm 0;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@foreach($varlistanomina as $nomina)
    @if($nomina->total_apagar > 0)
        @php
            $percepciones = $nomina->total_sueldo
                + $nomina->total_horas_extras
                + $nomina->despensa
                + $nomina->otros
                + $nomina->percepcion_extraordinaria
                + $nomina->pago_dias_descanso
                + $nomina->pago_prima_dominical
                + $nomina->pago_dias_vacaciones
                + $nomina->pago_prima_vacacional;

            $retenciones = $nomina->pago_isr
                + $nomina->pago_imss
                + $nomina->pago_infonavit
                + $nomina->fonacot
                + $nomina->deudores_fiscal;

            $formaPago = !empty($nomina->banco) ? 'Transferencia' : 'Cheque nominativo';
            $cuentaBancaria = trim($nomina->numero_cuenta ?? '');

            $meses = ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.', 'dic.'];
            $fechaTs = strtotime($nomina->fecha_fin);
            $fechaTexto = date('d', $fechaTs) . '/' . $meses[(int) date('n', $fechaTs) - 1] . '/' . date('Y', $fechaTs);
            $nombreEmpleado = mb_strtoupper($nomina->NOMBRE, 'UTF-8');
        @endphp

        <div class="cheque-page">
        <div class="cheque">
            <table class="tabla-layout" style="margin-bottom: 0.18cm;">
                <tr>
                    <td style="width: 68%;">
                        <p class="cheque-titulo">Pago de nómina</p>
                    </td>
                    <td style="width: 32%; text-align: right; padding-right: 0.05cm;">
                        <div class="fecha-wrap">
                            <div class="fecha-head">Fecha</div>
                            <div class="fecha-valor">{{ $fechaTexto }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="tabla-layout">
                <tr>
                    <td class="col-izq">
                        <table class="campo-fila" style="margin-bottom: 0.14cm;">
                            <tr>
                                <td class="campo-etiqueta">Empleado:</td>
                                <td style="width: 1.15cm;">
                                    <div class="campo-caja campo-caja-id">{{ $nomina->id_empleado }}</div>
                                </td>
                                <td>
                                    <div class="campo-caja campo-caja-nombre">{{ $nombreEmpleado }}</div>
                                </td>
                            </tr>
                        </table>

                        <table class="campo-fila">
                            <tr>
                                <td class="campo-etiqueta">Puesto:</td>
                                <td><div class="campo-caja">{{ $nomina->puesto }}</div></td>
                            </tr>
                        </table>

                        <table class="campo-fila">
                            <tr>
                                <td class="campo-etiqueta">Departamento:</td>
                                <td><div class="campo-caja">{{ $nomina->sucursal }}</div></td>
                            </tr>
                        </table>

                        <table class="campo-fila">
                            <tr>
                                <td class="campo-etiqueta">Forma de pago:</td>
                                <td><div class="campo-caja">{{ $formaPago }}</div></td>
                            </tr>
                        </table>

                        <table class="campo-fila">
                            <tr>
                                <td class="campo-etiqueta">Cuenta bancaria:</td>
                                <td>
                                    @if($cuentaBancaria !== '')
                                        <div class="campo-caja">{{ $cuentaBancaria }}</div>
                                    @else
                                        <div class="campo-caja-vacio">&nbsp;</div>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>

                    <td class="col-der">
                        <table class="monto-fila">
                            <tr>
                                <td class="monto-etiqueta">Percepciones:</td>
                                <td class="monto-valor">{{ number_format($percepciones, 2) }}</td>
                            </tr>
                        </table>
                        <table class="monto-fila">
                            <tr>
                                <td class="monto-etiqueta">Retenciones:</td>
                                <td class="monto-valor">{{ number_format($retenciones, 2) }}</td>
                            </tr>
                        </table>
                        <table class="monto-fila">
                            <tr>
                                <td class="monto-etiqueta">Pago:</td>
                                <td class="monto-valor">{{ number_format($nomina->total_apagar, 2) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
        </div>

        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
