<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
     
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

    <style>
       body{Font-family: Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif;font-size:10px;}
       p{font-size: 12px; font-weight: lighter;}
       .title{font-size: 13px}
       .small{font-size: 7px!important;}
       .text-start{text-align: start!important;}
       .text-end{text-align: end!important;}
       .table{width:100%!important;}
       .table td{border: 1px solid #000000 !important;font-size: 9.8px!important;}
       .table th{border: 1px solid #000000 !important;font-size: 9.8px!important; }
       .border{border: 1px solid #000000 !important;border-radius: 10px!important;font-size: 11px; font-weight: lighter;}
       .text-justify{text-align:justify;}
       .text-dark{color: #000000}
       .text-center{text-align:center;}
       .shadow{background-color: #ababab; color: rgb(0, 0, 0); font-weight: bold;}
       .shadow_light{background-color: #cacaca; color: rgb(0, 0, 0); font-weight: bold;}
       .shadow_swift{background-color: #dfdfdf; color: rgb(0, 0, 0);}
       a{color: #000000!important;}
       .logo_banco{width:140px;height: 60px;}
       .fw-bold{font-weight: bold!important;}
       .fw-light{font-weight: lighter!important;}
       .text-center{text-align: center!important;}
       .bg-light{background-color: rgb(255, 255, 255)!important;}
       .text_s{color: #262626}
       .logoV{width: 100px!important;height:45px!important;margin: 5px;}
       .page-break {page-break-after: always;}
       @page {
		margin-left: 20px!important;margin-right: 20px!important;margin-top: 20px!important;margin-bottom: 20px!important;
	   }

       
       .row::after {content: ""; clear: both; display: table;}
        [class*="col-"] {  float: left; padding: 5px;}   /* border: 1px solid red;  */ 
        .col-1 {width: 10%;}
        .col-2 {width: 25%;}
        .col-3 {width: 30%;}
        .col-4 {width: 40%;}
        .col-5 {width: 45%;}
        .col-6 {width: 60%;}
        .col-7 {width: 71%;}
        .col-8 {width: 75%;}
        .col-9 {width: 75%;}
        .col-10 {width: 83.33%;}
        .col-11 {width: 91.66%;}
        .col-12 {width: 100%;}

        * {
        box-sizing: border-box;
        }

    </style>
</head>
<body>
    <!--Pagina 1-->
    <div>
        <center>
            <table>
              <tr>
                <td><img class="logoV"  src="{{ public_path('/Images/ummining_500.png')}}" alt="empresa"></td> 
                <td style="font-size:14;color:#393939;" class="fw-bold">
                  <br>&nbsp;&nbsp;&nbsp; ARQUEO {{$nombre}}<br></td>
              </tr>
            </table>
            <hr style="border:1px solid#f59300;margin-top:0px;">
            <p class="fw-bold" style="font-size: 10px;color:#f59300;margin-top:-10px;">{{$nombre_empresa}}</p>
        </center>


        <div class="row">
            <div>
                <div class="col-2">
                    {{-- fechas --}}
                    <table class="table">
                        <tbody>
                            <tr>
                                <th class="shadow_light">FECHA</th>
                                <td>{{$fecha}}</td>
                            </tr>
                            <tr>
                                <th class="shadow">SUCURSAL</th>
                                <td>{{$nombre_sucursal}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="col-7">
                    {{-- responsabilidad --}}
                    <table class="table">
                        <tbody>
                            <tr>
                                <th class="shadow_light">ELABORO</th>
                                <td>{{$NombreRealizado}}</td>
                            </tr>
                            <tr>
                                <th class="shadow">REVISO</th>
                                <td> <b>{{$estado}}</b> {{$NombreAutoriza}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div>
            <div class="col-2">
                {{-- informacion --}}
                <table class="table">
                    <thead>
                        <tr>
                            <th colspan="2">INFORMACION CAJA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th class="shadow_light">NOMBRE</th>
                            <td>{{$nombre}}</td>
                        </tr>
                        <tr>
                            <th class="shadow_light">SALDO ACTUAL</th>
                            <td>$ {{number_format($saldo_actual, 2)}}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- ingresos --}}
                <table class="table">
                    <tbody>
                        <tr>
                            <th class="shadow">SALDO INICIAL</th>
                            <td class="text-truncate">$ {{number_format($saldo_inicial, 2)}}</td>
                        </tr>
                        <tr>
                            <th>RECEPCION DE EFECTIVO</th>
                            <td class="text-truncate">$ {{number_format($traspasos, 2)}}</td>
                        </tr>
                        <tr>
                            <th>COBRANZA DIARIA</th>
                            <td class="text-truncate">$ {{number_format($cobranza, 2)}}</td>
                        </tr>
                        <tr>
                            <th class="shadow">TOTAL INGRESOS</th>
                            <td class="shadow text-truncate">$ {{number_format($total_ingresos, 2)}}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- egresos --}}
                <table class="table">
                    <tbody>
                        <tr>
                            <th>DESEMBOLSOS DIARIOS</th>
                            <td class="text-truncate">$ {{number_format($desembolsos, 2)}}</td>
                        </tr>
                        <tr>
                            <th>GASTOS DADOS DE BAJA</th>
                            <td class="text-truncate">$ {{number_format($gastos, 2)}}</td>
                        </tr>
                        <tr>
                            <th>ENTREGA DE EFECTIVO</th>
                            <td class="text-truncate">$ {{number_format($entregas, 2)}}</td>
                        </tr>
                        <tr>
                            <th>CANCELACION DE PAGOS</th>
                            <td class="text-truncate">$ {{number_format($cancelaciones, 2)}}</td>
                        </tr>
                        <tr>
                            <th class="shadow">TOTAL EGRESOS</th>
                            <td class="shadow text-truncate">$ {{number_format($total_egresos, 2)}}</td>
                        </tr>
                    </tbody>
                </table>
          
                {{-- total calculado --}}
                <table class="table">
                    <tbody>
                        <tr>
                            <th class="shadow">SALDO EN CAJA</th>
                            <td class="shadow text-truncate">$ {{number_format($total_calculado, 2)}}</td>
                        </tr>
                    </tbody>
                </table>

                <br><br> <br><br><br><br><br><br>

                {{-- efectivo ingresado billetes--}}
                <table class="table">
                    <thead>
                        <tr>
                            <th class="shadow" colspan="3">RELACION DE EFECTIVO</th>
                        </tr>
                        <tr>
                            <th class="shadow_light">DENOMINACION</th>
                            <th class="shadow_light">CANTIDAD</th>
                            <th class="shadow_light">MONTO</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                          <td class="fw-bold">1,000.00</td>
                          <td class="shadow_swift">{{$catidad_mil}}@php($totalmil = 1000*$catidad_mil)</td>
                          <td class="text-truncate">$ {{number_format($totalmil, 2)}}</td>
                        </tr>
                        <tr>
                          <td class="fw-bold">500.00</td>
                          <td class="shadow_swift">{{$catidad_quinientos}}@php($totalquinientos = 500*$catidad_quinientos)</td>
                          <td class="text-truncate">$ {{number_format($totalquinientos, 2)}}</td>
                        </tr>
                        <tr>
                          <td class="fw-bold">200.00</td>
                          <td class="shadow_swift">{{$catidad_doscientos}}@php($totaldoscientos = 200*$catidad_doscientos)</td>
                          <td class="text-truncate">$ {{number_format($totaldoscientos, 2)}}</td>
                        </tr>
                        <tr>
                          <td class="fw-bold">100.00</td>
                          <td class="shadow_swift">{{$catidad_cien}}@php($totalcien = 100*$catidad_cien)</td>
                          <td class="text-truncate">$ {{number_format($totalcien, 2)}}</td>
                        </tr>
                        <tr>
                          <td class="fw-bold">50.00</td>
                          <td class="shadow_swift">{{$catidad_cincuenta}}@php($totalcincuenta = 50*$catidad_cincuenta)</td>
                          <td class="text-truncate">$ {{number_format($totalcincuenta, 2)}}</td>
                        </tr>
                        <tr>
                          <td class="fw-bold">20.00</td>
                          <td class="shadow_swift">{{$catidad_veinte}}@php($totalveinte = 20*$catidad_veinte)</td>
                          <td class="text-truncate">$ {{number_format($totalveinte, 2)}}</td>
                        </tr>
                        <tr>
                            @php($totalbillete = $totalmil+$totalquinientos+$totaldoscientos+$totalcien+$totalcincuenta+$totalveinte)
                            <th colspan="2" class="shadow">TOTAL BILLETES</th>
                            <td class="shadow text-truncate">$ {{number_format($totalbillete, 2)}}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- efectivo ingresado monedas --}}
                <table class="table">
                    <thead>
                        <tr>
                            <th class="shadow_light">DENOMINACION</th>
                            <th class="shadow_light">CANTIDAD</th>
                            <th class="shadow_light">MONTO</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                        <td class="fw-bold">10.00</td>
                        <td class="shadow_swift">{{$catidad_diez}}@php($totaldiez = 10*$catidad_diez)</td>
                        <td class="text-truncate">$ {{number_format($totaldiez, 2)}}</td>
                        </tr>
                        <tr>
                        <td class="fw-bold">5.00</td>
                        <td class="shadow_swift">{{$catidad_cinco}}@php($totalcinco = 5*$catidad_cinco)</td>
                        <td class="text-truncate">$ {{number_format($totalcinco, 2)}}</td>
                        </tr>
                        <tr>
                        <td class="fw-bold">2.00</td>
                        <td class="shadow_swift">{{$catidad_dos}}@php($totaldos = 2*$catidad_dos)</td>
                        <td class="text-truncate">$ {{number_format($totaldos, 2)}}</td>
                        </tr>
                        <tr>
                        <td class="fw-bold">1.00</td>
                        <td class="shadow_swift">{{$catidad_uno}}@php($totaluno = 1*$catidad_uno)</td>
                        <td class="text-truncate">$ {{number_format($totaluno, 2)}}</td>
                        </tr>
                        <tr>
                        <td class="fw-bold">0.50</td>
                        <td class="shadow_swift">{{$catidad_cincuentacentavos}}@php($totalcincuentacentavos = 0.5*$catidad_cincuentacentavos)</td>
                        <td class="text-truncate">$ {{number_format($totalcincuentacentavos, 2)}}</td>
                        </tr>
                        <tr>
                            @php($totalmoneda = $totaldiez+$totalcinco+$totaldos+$totaluno+$totalcincuentacentavos)
                            <th colspan="2" class="shadow">TOTAL MONEDAS</th>
                            <td class="shadow text-truncate">$ {{number_format($totalmoneda, 2)}}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- total ingresado --}}
                <table class="table">
                    <tbody>
                        <tr>
                            <th class="shadow">TOTAL EFECTIVO</th>
                            <td class="shadow text-truncate">$ {{number_format($total_ingresado, 2)}}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- diferencia --}}
                <table class="table">
                    <tbody>
                        <tr>
                            <th class="shadow">DIFERENCIA</th>
                            <td class="shadow text-truncate">$ {{number_format($diferencia, 2)}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-4">
                {{-- traspasos --}}
                <table class="table">
                    <thead>
                        <tr>
                            <th colspan="4" class="shadow">1.  &nbsp;&nbsp;RECEPCION DE EFECTIVO</th>
                        </tr>
                        <tr>
                            <th class="shadow_light">FECHA</th>
                            <th class="shadow_light">REFERENCIA</th>
                            <th class="shadow_light">CONCEPTO</th>
                            <th class="shadow_light">IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vartraspasos as $tras)
                            <tr>
                            <td class="text-truncate">{{$tras->fecha}}</td>
                            <td># {{$tras->numero_referencia}} {{$tras->nombre_referencia}}</td>
                            <td>{{$tras->concepto}}</td>
                            <td class="text-truncate">$ {{$tras->ingreso}}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="shadow">TOTAL</td>
                            <td class="text-truncate">$ {{number_format($traspasos, 2)}}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- desembolsos --}}
                <table class="table">
                    <thead>
                        <tr>
                            <th colspan="4" class="shadow">3.  &nbsp;&nbsp;DESEMBOLSOS DIARIOS</th>
                        </tr>
                        <tr>
                            <th class="shadow_light">FECHA</th>
                            <th class="shadow_light">REFERENCIA</th>
                            <th class="shadow_light">CONCEPTO</th>
                            <th class="shadow_light">IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vardesembolsos as $desm)
                        <tr>
                            <td class="text-truncate">{{$desm->fecha}}</td>
                            <td>#{{$desm->numero_referencia}} {{$desm->nombre_referencia}}</td>
                            <td>{{$desm->concepto}}</td>
                            <td class="text-truncate">$ {{number_format($desm->egreso, 2)}}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="shadow">TOTAL</td>
                            <td class="text-truncate">$ {{number_format($desembolsos, 2)}}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- gastos --}}
                <table class="table">
                    <thead>
                        <tr>
                            <th colspan="4" class="shadow">4.  &nbsp;&nbsp;RELACION DE GASTOS POR COMPROBAR</th>
                        </tr>
                        <tr>
                            <th class="shadow_light">FECHA</th>
                            <th class="shadow_light">REFERENCIA</th>
                            <th class="shadow_light">CONCEPTO</th>
                            <th class="shadow_light">IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vargastos as $gast)
                        <tr>
                        <td class="text-truncate">{{$gast->fecha}}</td>
                        <td>{{$gast->concepto}}</td>
                        <td>{{$gast->descripcion}}</td>
                        <td class="text-truncate">$ {{number_format($gast->egreso, 2)}}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="shadow">TOTAL</td>
                            <td class="text-truncate">$ {{number_format($gastos, 2)}}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- entregas --}}
                <table class="table">
                    <thead>
                        <tr>
                            <th colspan="4" class="shadow">5.  &nbsp;&nbsp;ENTREGA DE EFECTIVO</th>
                        </tr>
                        <tr>
                            <th class="shadow_light">FECHA</th>
                            <th class="shadow_light">REFERENCIA</th>
                            <th class="shadow_light">CONCEPTO</th>
                            <th class="shadow_light">IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($varentregas as $entr)
                        <tr>
                            <td class="text-truncate">{{$entr->fecha}}</td>
                            <td>#{{$entr->numero_referencia}} {{$entr->nombre_referencia}}</td>
                            <td>{{$entr->concepto}}</td>
                            <td class="text-truncate">$ {{number_format($entr->egreso, 2)}}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="shadow">TOTAL</td>
                            <td class="text-truncate">$ {{number_format($entregas, 2)}}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- cancelaciones --}}
                <table class="table">
                    <thead>
                        <tr>
                            <th colspan="4" class="shadow">6.  &nbsp;&nbsp;CANCELACION DE PAGOS</th>
                        </tr>
                        <tr>
                            <th class="shadow_light">FECHA</th>
                            <th class="shadow_light">REFERENCIA</th>
                            <th class="shadow_light">CONCEPTO</th>
                            <th class="shadow_light">IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($varcancelaciones as $canc)
                        <tr>
                            <td class="text-truncate">{{$canc->fecha}}</td>
                            <td>PAGO #{{$canc->numero_referencia}} DE {{$canc->nombre_referencia}}</td>
                            <td>{{$canc->concepto}}</td>
                            <td class="text-truncate">$ {{number_format($canc->egreso, 2)}}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="shadow">TOTAL</td>
                            <td class="text-truncate">$ {{number_format($cancelaciones, 2)}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="col-3">
                {{-- cobranza --}}
                <table class="table">
                    <thead>
                        <tr>
                            <th colspan="3" class="shadow">2.  &nbsp;&nbsp;COBRANZA DIARIA</th>
                        </tr>
                        <tr>
                            <th class="shadow_light">FECHA</th>
                            <th class="shadow_light">REFERENCIA</th>
                            <th class="shadow_light">IMPORTE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($varcobranza as $cobra)
                        <tr>
                          <td class="text-truncate">{{$cobra->fecha}}</td>
                          <td>#{{$cobra->numero_referencia}} {{$cobra->nombre_referencia}}</td>
                          <td class="text-truncate">$ {{number_format($cobra->ingreso, 2)}}</td>
                        </tr>
                        @endforeach
                        <tr>
                            <td colspan="2" class="shadow">TOTAL</td>
                            <td class="text-truncate">$ {{number_format($cobranza, 2)}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        


       
          <!--Marca de agua-->
        {{-- <div style="position: fixed;z-index:-1;bottom:0px;right:0px;margin-bottom:-50px;margin-right:-30px;">
            <img style="width : 300px;height : 300px; opacity: .2;" src="{{$marca_agua}}">
        </div> --}}
    </div>
    
</body>
</html>