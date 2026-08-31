<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous">
    </script>

    <style>
        body {
            Font-family: Arial, Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif;
            font-size: 10px;
        }

        p {
            font-size: 12px;
            font-weight: lighter;
        }

        h6 {
            font-size: 12px !important;
            font-weight: bold !important;
        }

        .h1 {
            font-size: 20px !important;
            color: #232323 !important;
        }

        .title {
            font-size: 14px
        }

        .small {
            font-size: 9px !important;
        }

        .text-end {
            text-align: end;
        }

        .table td {
            border: 1px solid #000000 !important;
            font-size: 12px;
            font-weight: lighter;
        }

        .table th {
            border: 1px solid #000000 !important;
            font-size: 13px;
            font-weight: lighter;
        }

        .border {
            border: .5px solid #8d8d8d !important;
        }

        .table2 {
            width: 100% !important;
        }

        .table2 td {
            background-color: rgba(236, 236, 229, 0.56);
            font-size: 11px !important;
            font-weight: lighter;
            border: none !important;
        }

        .table2 th {
            background-color: rgba(236, 236, 229, 0.619);
            font-size: 11px !important;
            font-weight: bold !important;
            border: none !important;
        }

        .thead_shadow th {
            background-color: #cccccc7b !important;
            font-size: 12px !important;
            font-weight: light !important;
        }

        .thead_shadow2 {
            background-color: #ffffff8a !important;
            font-size: 22px !important;
            font-weight: bold !important;
            color: #656565 !important;
        }

        .t_light {
            background-color: #000000 !important;
            font-size: 12px !important;
            font-weight: light !important;
            color: #000000 !important;
        }

        .shadowTh {
            background-color: #d7d7d79a !important;
            font-size: 11px !important;
            font-weight: bold;
        }

        .shadowTd {
            background-color: #b0b0b098 !important;
            font-size: 11px !important;
            font-weight: bold;
        }

        .table_little {
            font-size: 12px !important;
            font-weight: lighter;
            width: 100% !important;
        }

        .table_little td {
            background-color: rgb(236, 236, 229);
            font-size: 12px;
        }

        .text-justify {
            text-align: justify;
        }

        .text-dark {
            color: #000000
        }

        .text-center {
            text-align: center;
        }

        .firma1 {
            margin-top: 210px;
        }

        .firma2 {
            margin-top: 545px;
        }

        .firma3 {
            margin-top: 440px;
        }

        .mt_small {
            margin-top: 30px;
        }

        .mt_medium {
            margin-top: 80px;
        }

        .mt_large {
            margin-top: 140px;
        }

        .mb_small {
            margin-bottom: 40px;
        }

        .mb_medium {
            margin-bottom: 130px;
        }

        .mb_large {
            margin-bottom: 135px;
        }

        .page-break {
            page-break-after: always;
        }

        .container {
            padding: 20px !important;
        }

        .logo_banco {
            width: 140px;
            height: 60px;
        }

        .fw-bold {
            font-weight: bold !important;
        }

        .fw-light {
            font-weight: lighter !important;
        }

        .text-center {
            text-align: center !important;
        }

        .bg-light {
            background-color: rgb(255, 255, 255) !important;
        }

        .text_s {
            color: #262626
        }

        .logoV {
            width: 85px !important;
            height: 75px !important;
            margin: 5px;
        }
    </style>
</head>

<body>
    @php
        $pagosTotal = 0;
        $atrasosTotales = 0;
        $saldoAtrasado = 0;
        $first = false;
        $diasAtrasados = 0;

        function getRowColor($estado, $fecha_pago)
        {
            $fechaFormateada = DateTime::createFromFormat('d/m/Y', $fecha_pago);
            $timestampFecha = $fechaFormateada ? $fechaFormateada->getTimestamp() : null;
            $now = time();

            if ($estado == 'S') {
                return '#28a745'; // Verde
            } elseif ($timestampFecha && $timestampFecha < $now) {
                return '#dc3545'; // Rojo
            } else {
                return '#ec8e0f'; // Amarillo
            }
        }
    @endphp
    @foreach ($varPresDet as $pres)
        @if ($pres->estado == 'S')
            @php $pagosTotal += $pres->pago_quincenal; @endphp
        @endif
        @php
            $fecha_pago = DateTime::createFromFormat('d/m/Y', $pres->fecha_pago);
            $fechaFormateada = $fecha_pago->getTimestamp(); // Obtener timestamp de la fecha convertida
            $now = time();
            $datediff = $now - $fechaFormateada;
            $dias = round($datediff / (60 * 60 * 24));
            if ($now > $fechaFormateada && $pres->estado == 'A') {
                $saldoAtrasado += $pres->pago_quincenal;
                $atrasosTotales += 1;
                if ($first == false) {
                    $first = true;
                    $diasAtrasados = $dias;
                }
            }
            $ciudadEmp = $pres->ciudadEmp;
            $direccionEmp = $pres->direccionEmp;
            $empleadoTel = $pres->empleadoTel;
            $id_credito = $pres->id_credito;
            $plazos = $pres->plazos;
            $monto = $pres->monto;
            $interes = $pres->interes;
            $ivainteres = $pres->ivainteres;
            $otros = $pres->otros;
            $total = $pres->total;
            $fecha_inicio = $pres->fecha_inicio;
            $pago_quincenal = $pres->pago_quincenal;
            $fecha_deposito = $pres->fecha_pago;
            $montoxplazo = $pres->otrosconceptos1;
            $interesxplazo = $pres->otrosconceptos2;
            $ivainteresxplazo = $pres->otrosconceptos3;
        @endphp
    @endforeach

    <!--Página 1-->
    <div class="container">
        <div style="margin-top:-40px;">
            <table>
                <tr>
                    <td><img class="logoV" src="{{ $icono }}" alt="fincreLaguna"></td>
                    <td style="font-size:14;color:#1492b5;" class="fw-bold">
                        <br>&nbsp;&nbsp;&nbsp; ESTADO DE CUENTA Y PLAN DE PAGOS<br>
                    </td>
                </tr>
            </table>
            <hr style="border:1px solid#1492b5;margin-top:0px;">
            <p class="fw-bold" style="font-size: 10px;color:#137d9a;margin-top:-10px;">{{ $razon_social }}</p>
        </div>

        <div class="row text-center">
            <table class="table2">
                <tbody>
                    <tr class="t_light">
                        <td class="bg-light" colspan="2">
                            @if ($varPresDet[1]->estadoPres == 'P')
                                <b style="color:#ff9f29;"> PENDIENTE </b>
                            @endif
                            @if ($varPresDet[1]->estadoPres == 'A')
                                <b style="color:#00bd45;"> AUTORIZADO </b>
                            @endif
                        </td>
                    </tr>
                    <tr class="t_light">
                        <td class="bg-light"><b>CLIENTE</b> {{ $varPresDet[1]->Nombre }}</td>
                        <td class="bg-light"><b>TELEFONO</b> {{ $varPresDet[1]->empleadoTel }}</td>
                    </tr>
                    <tr class="t_light">
                        <td class="bg-light"><b>AUTORIZADO POR</b> {{ $varPresDet[1]->coordinador }}<br><br><br></td>
                        <td class="bg-light"><b>TELEFONO</b> {{ $varPresDet[1]->coordinadorTel }}<br><br><br></td>
                    </tr>
                </tbody>
            </table>

            <table class="table2">
                <tbody>
                    <tr class="t_light">
                        <td class="bg-light text-center fw-bold">FOLIO</td>
                        <td class="bg-light text-center">{{ $id_credito }}</td>
                        <td class="bg-light text-center fw-bold">PLAZOS</td>
                        <td class="bg-light text-center">{{ $plazos }}</td>
                        <td class="bg-light text-center fw-bold">TOTAL</td>
                        <td class="bg-light text-center">$ {{ number_format($total, 2) }}</td>
                        <td class="bg-light text-center fw-bold">ELAB</td>
                        <td class="bg-light text-center">{{ $fecha_inicio }}</td>
                    </tr>
                    <tr class="t_light">
                        <td class="bg-light" colspan="3"><b>CIUDAD</b> {{ $ciudadEmp }}</td>
                        <td class="bg-light"></td>
                        <td class="bg-light" colspan="4"><b>DIRECCION</b> {{ $direccionEmp }}</td>
                    </tr>
                </tbody>
            </table>

            <br><br>

            <table class="table2">
                <thead>
                    <tr class="t_light">
                        <th class="bg-light text-center fw-bold">CANT</th>
                        <th class="bg-light text-center fw-bold">CONCEPTO</th>
                        <th class="bg-light text-center fw-bold">PLAZOS</th>
                        <th class="bg-light text-center fw-bold">IMPORTE</th>
                        <th class="bg-light text-center fw-bold">PAGO POR PLAZO</th>
                        <th class="bg-light text-center fw-bold">OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-light text-center">1</td>
                        <td class="shadowTh fw-bold text-center">CAPITAL</td>
                        <td class="fw-light text-center">{{ $plazos }}</td>
                        <td class="shadowTh text-center">$ {{ number_format($monto, 2) }}</td>
                        <td class="fw-bold text-center">$ {{ number_format($montoxplazo, 2) }}</td>
                        <td class="fw-light text-center"></td>
                    </tr>
                    <tr>
                        <td class="fw-light text-center">1</td>
                        <td class="shadowTh fw-bold text-center">INTERESES</td>
                        <td class="fw-light text-center">{{ $plazos }}</td>
                        <td class="shadowTh text-center">$ {{ number_format($interes, 2) }}</td>
                        <td class="fw-bold text-center">$ {{ number_format($interesxplazo, 2) }}</td>
                        <td class="fw-light text-center"></td>
                    </tr>
                    <tr>
                        <td class="fw-light text-center">1</td>
                        <td class="shadowTh fw-bold text-center">IVA INTERES</td>
                        <td class="fw-light text-center">{{ $plazos }}</td>
                        <td class="shadowTh text-center">$ {{ number_format($ivainteres, 2) }}</td>
                        <td class="fw-bold text-center">$ {{ number_format($ivainteresxplazo, 2) }}</td>
                        <td class="fw-light text-center"></td>
                    </tr>
                    <tr>
                        <td class="fw-light text-center">1</td>
                        <td class="shadowTh fw-bold text-center">OTROS</td>
                        <td class="fw-light text-center">{{ $plazos }}</td>
                        <td class="shadowTh text-center">$ {{ number_format($otros, 2) }}</td>
                        @php($otrosxplazo = $otros / $plazos) @php($otroxplazo = round($otrosxplazo * 100) / 100) $ {{ $otroxplazo }}
                        <td class="fw-bold text-center">$ {{ number_format($otroxplazo, 2) }}</td>
                        <td class="fw-light text-center"></td>
                    </tr>
                    <tr>
                        <td class="bg-light" colspan="3"></td>
                        <td class="shadowTh fw-bold text-center">$ {{ number_format($total, 2) }}</td>
                        <td class="bg-light" colspan="2"></td>
                    </tr>
                </tbody>
            </table>

            <br><br>
        </div>


        <div class="row mb-4">
            <table class="table2">
                <tbody>
                    <tr class="t_light">
                        <td class="fw-bold bg-light text-center">TIPO DE PLAZO</td>
                        <td class="fw-bold bg-light text-center">CONDICION</td>
                       {{-- <td class="fw-bold bg-light text-center">FORMA DE PAGO</td> --}}
                        <td class="fw-bold bg-light text-center">TOTAL</td>
                        <td class="fw-bold bg-light text-center">PAGOS</td>
                    </tr>
                    <tr>
                        <td class="shadowTh fw-light text-center">QUINCENAL</td>
                        <td class="shadowTd fw-light text-center">Prestamo con intereses</td>
                        {{-- <td></td> --}}
                        <td class="shadowTh fw-bold text-center">$ {{ number_format($total, 2) }}</td>
                        <td class="shadowTd fw-bold text-center">$ {{ number_format($pagosTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            <br><br>

            <table class="table2">
                <tbody>
                    <tr class="t_light">
                        <td class="fw-bold bg-light text-center">ATRASOS</td>
                        <td class="fw-bold bg-light text-center">SALDO ATRASADO</td>
                        <td class="fw-bold bg-light text-center">DIAS DE ATRASO</td>
                        {{-- <td class="fw-bold bg-light text-center">CH. ENTREGADOS</td>
                        <td class="fw-bold bg-light text-center">CH. PENDIENTES</td> --}}
                    </tr>
                    <tr>
                        <td class="shadowTh fw-light text-center">{{ $atrasosTotales }}</td>
                        <td class="shadowTd fw-bold text-center">$ {{ number_format($saldoAtrasado, 2) }}</td>
                        <td class="fw-light text-center">{{ $diasAtrasados }}</td>
                        {{-- <td class="shadowTh fw-light text-center">0</td>
                        <td class="shadowTd fw-light text-center">0</td> --}}
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="row mb-4">
            <div>
                <div>
                    <h6 style="color:#137d9a;margin-top:10px;"> PLAN DE PAGOS</h6>
                </div>
                <table class="table2">
                    <thead>
                        <tr class="t_light">
                            <th class="bg-light fw-bold text-center">PLAZO <br></th>
                            <th class="bg-light fw-bold text-center">FECHA <br></th>
                            {{-- <th class="bg-light fw-bold text-center">CHEQUE <br></th> --}}
                            <th class="bg-light fw-bold text-center">IMPORTE <br></th>
                            <th class="bg-light fw-bold text-center">SALDO <br></th>
                            <th class="bg-light fw-bold text-center">FECHA DE PAGO <br></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($varPresDet as $dato2)
                            <tr style="color: {{getRowColor($dato2->estado, $dato2->fecha_pago)}};">
                                <td class="shadowTd text-center">{{ $dato2->plazo }}</td>
                                <td class="shadowTh text-center">{{ $dato2->fecha_pago }}</td>
                                {{-- <td class="text-center"></td> --}}
                                <td class="shadowTh text-center">${{ number_format($dato2->pago_quincenal, 2) }}</td>
                                <td class="shadowTd text-center">${{ number_format($dato2->saldo_nuevo, 2) }}</td>
                                <td class="text-center">{{ $dato2->fecha_saldado }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div>
                <table style="margin-top: 100px;width:100%;">
                    <tbody class="text-center">
                        <tr>
                            <td class="text-center">
                                <p class="fw-light text-center">___________________________________</p>
                                <b class="small fw-light">{{ $razon_social }}</b>
                                <p class="fw-bold" style="font-size:8px;">Firma</p>
                            </td>
                            <td class="text-center">
                                <p class="fw-light text-center">___________________________________</p>
                                <b class="small fw-light">EL ACREDITADO</b>
                                <p class="fw-bold" style="font-size:8px;">Nombre Completo y Firma</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!--Marca de agua-->
        <div style="position: fixed;z-index:-1;bottom:0px;right:0px;margin-bottom:-50px;margin-right:-30px;">
            <img style="width : 300px;height : 300px; opacity: .2;" src="{{ $marca_agua }}" alt="fincrelaguna">
        </div>
    </div>



</body>

</html>
