<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de la nómina</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .empresa {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .titulo {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 6px 0 10px;
        }
        .periodo {
            font-size: 11px;
            margin-bottom: 8px;
        }
        .modo {
            font-size: 9px;
            color: #444;
            margin-bottom: 10px;
        }
        table.listado {
            width: 100%;
            border-collapse: collapse;
        }
        table.listado thead th {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 4px 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .th-emp { text-align: left; width: 34%; }
        .th-num { text-align: right; }
        .emp-row td {
            padding-top: 8px;
            padding-bottom: 2px;
            font-size: 10px;
            vertical-align: top;
        }
        .emp-nombre {
            font-weight: bold;
            text-transform: uppercase;
        }
        .num { text-align: right; white-space: nowrap; }
        .conceptos {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 4px 8px;
        }
        .conceptos th {
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            padding: 1px 2px;
            border: none;
        }
        .conceptos td {
            font-size: 9px;
            padding: 1px 2px;
            border: none;
            vertical-align: top;
        }
        .conceptos .c-concepto { width: 48%; }
        .conceptos .c-monto { width: 22%; text-align: right; }
        .conceptos .c-unidad { width: 30%; text-align: right; }
        .totales-row td {
            border-top: 1.5px solid #000;
            padding-top: 6px;
            font-weight: bold;
            font-size: 10px;
        }
        .page-break { page-break-after: always; }
        @page {
            margin: 28px 32px;
        }
    </style>
</head>
<body>
@php
    $meses = [1=>'ene.',2=>'feb.',3=>'mar.',4=>'abr.',5=>'may.',6=>'jun.',7=>'jul.',8=>'ago.',9=>'sep.',10=>'oct.',11=>'nov.',12=>'dic.'];
    $fechaRef = $listado['fecha_inicio'] ?? $listado['fecha_fin'] ?? null;
    $periodoTxt = '-';
    if ($fechaRef) {
        $dt = \Carbon\Carbon::parse($fechaRef);
        $periodoTxt = $dt->format('d') . '/' . $meses[(int)$dt->format('n')] . '/' . $dt->format('Y');
    }
    $modosLabel = [
        'completo' => 'Conceptos: fiscal + excedente + excepciones',
        'fiscal' => 'Conceptos: solo fiscal',
        'excedente' => 'Conceptos: excedente y excepciones',
    ];
@endphp

<div class="empresa">{{ $razon_social }}</div>
<div class="titulo">Listado de la nómina</div>
<div class="periodo">Nómina: {{ $listado['tipo_nomina'] }} del {{ $periodoTxt }}</div>
<div class="modo">{{ $modosLabel[$listado['modo']] ?? '' }} · Orden: {{ $listado['orden'] === 'id' ? 'No. empleado' : 'Apellido' }}</div>

<table class="listado">
    <thead>
        <tr>
            <th class="th-emp">Empleado</th>
            <th class="th-num">Sueldo diario</th>
            <th class="th-num">Percepciones</th>
            <th class="th-num">Retenciones</th>
            <th class="th-num">Pago en especie</th>
            <th class="th-num">Pago en efectivo</th>
        </tr>
    </thead>
    <tbody>
        @foreach($listado['empleados'] as $empleado)
            <tr class="emp-row">
                <td class="emp-nombre">{{ $empleado->nombre_listado }}</td>
                <td class="num">{{ number_format($empleado->sueldo_diario, 2) }}</td>
                <td class="num">{{ number_format($empleado->percepciones, 2) }}</td>
                <td class="num">{{ number_format($empleado->retenciones, 2) }}</td>
                <td class="num">{{ number_format($empleado->pago_especie, 2) }}</td>
                <td class="num">{{ number_format($empleado->pago_efectivo, 2) }}</td>
            </tr>
            <tr>
                <td colspan="6" style="padding: 0 0 6px 0;">
                    <table class="conceptos">
                        <thead>
                            <tr>
                                <th class="c-concepto">Concepto</th>
                                <th class="c-monto">Monto</th>
                                <th class="c-unidad"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($empleado->conceptos as $concepto)
                                <tr>
                                    <td class="c-concepto">{{ $concepto['codigo'] }} {{ $concepto['descripcion'] }}</td>
                                    <td class="c-monto">{{ number_format($concepto['monto'], 2) }}</td>
                                    <td class="c-unidad">{{ $concepto['unidad'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        @endforeach
        <tr class="totales-row">
            <td>TOTALES</td>
            <td class="num"></td>
            <td class="num">{{ number_format($listado['totales']['percepciones'], 2) }}</td>
            <td class="num">{{ number_format($listado['totales']['retenciones'], 2) }}</td>
            <td class="num">{{ number_format($listado['totales']['pago_especie'], 2) }}</td>
            <td class="num">{{ number_format($listado['totales']['pago_efectivo'], 2) }}</td>
        </tr>
    </tbody>
</table>
</body>
</html>
