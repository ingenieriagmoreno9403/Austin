<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
     
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

    <title>FINIQUITO</title>
    <style>
       
        body{
            Font-family: Candara, Calibri, Segoe, Segoe UI, Optima, Arial, sans-serif;font-size:10px;
        }
        .p1{
            font-size:14px;
        }

        .texR{text-align:right;}
        
        .td1{width: 20px}
        .td2{text-align: right;width: 30px;}
        .td3{width: 50px}
        .td22{border-top:0.3px solid #000000;text-align: right;width: 30px;}

        .tdd1{width: 100px}
        .tdd11{width: 100px;text-align: right;}
        .tdd2{text-align: right;width: 150px;}
        .tdd22{border-top:0.3px solid #000000;text-align: right;width: 30px;}
        .logoV{width: 75px!important;height:60px!important;margin: 5px;}
        .fw-bold{font-weight: bold!important;}
        .fw-light{font-weight: lighter!important;}
        .text-center{text-align: center!important;}


    </style>
</head>
<body class="container">
    <center style="margin-bottom: 5px;border:1px solid #2c2c2c;width:100%;padding:10px;">
        {{-- <table>
          <tr>
            <td><img class="logoV"  src="{{$icono}}" alt="empresa"></td> 
            
          </tr>
        </table> --}}

        <h2 style="font-size:15;color:#000000;text-align: center!important;" class="fw-bold">{{$empresa}}</h2>
        <br>
        <div class="fw-bold" style="font-size: 10px;color:#000000;margin-top:-10px;">
            @if($tipo_baja == "finiquito")
                CALCULO DE FINIQUITO
            @else
                BAJA DE EMPLEADO
            @endif
        </div>   
    </center>

    <div class="row texR mt-1 mb-4">
        <p id="fecha">Torreón Coah, A {{ucwords($datenow)}}</p>
    </div>

    <div class="row mb-3">
       <p><b>EMPLEADO: </b>{{$nombreemplado}} <br>
       <b>PUESTO: </b>{{$puesto}}</p>
    </div>


    <div class="row mb-4">
        <div style="border:1px solid #2c2c2c;width:100%;padding:10px;">
            <table>
                <tr>
                    <td class="tdd1">FECHA INGRESO:</td>
                    <td class="tdd2"> {{date("d/m/Y", strtotime($fecha_ingreso))}}</td>
                    <td class="tdd11">S.D.</td>
                    <td class="tdd2"> $ {{number_format($salario, 2)}}</td> 
                </tr>
                <tr>
                    <td class="tdd1">FECHA BAJA:</td>
                    <td class="tdd2"> {{date("d/m/Y", strtotime($fecha_baja))}}</td>
                    <td class="tdd11">S.D.I</td>
                    <td class="tdd2 fw-bold"> - </td>
                </tr>
                <tr>
                    <td class="tdd1"></td>
                    <td class="tdd22">{{$diasDiferencia}} DIAS</td>
                    <td class="tdd11" ></td>
                    <td class="tdd2 fw-bold">PAGO QUINCENAL</td>
                </tr>
            </table>
        </div>
    </div>
     
    <div class="row mb-4">
            <div class="fw-bold" style="width:80%;border:1px solid #2c2c2c;padding:5px!important;margin-bottom:5px;background-color:#000000;color:#ffffff;">
               <b> FINIQUITO</b>
            </div>

            <table>
                {{-- <tr>
                    <td class="td1">NO. EMPLEADO</td>
                    <td class="td2">{{$idempleado}}</td>
                    <td class="td3"></td>
                </tr> --}}
                @if($rptvardiasgratificacion > 0)
                <tr>
                    <td class="td1">GRATIFICACIÓN</td>
                    <td class="td3">{{$dias_gratificacion}} días</td>
                    <td class="td2"> $ {{number_format($rptvardiasgratificacion, 2)}}</td>
                    <td class="td3"></td>
                </tr>
                @endif
                <tr>
                    <td class="td1">SUELDO</td>
                    <td class="td3"></td>
                    <td class="td2"> $ {{number_format($rptvarsueldoporporcional, 2)}}</td>
                    <td class="td3"></td>
    
                </tr>
                <tr>
                    <td class="td1">AGUINALDO</td>
                    @if($dias_aguinaldo == 0)
                        <td class="td3">0 días</td>
                    @elseif($dias_aguinaldo < 365)
                        @php($dias_aguinaldo = (15/365)*$dias_aguinaldo)
                        <td class="td3">{{number_format($dias_aguinaldo, 2)}} días</td>
                    @else
                        <td class="td3">{{$dias_aguinaldo}} días</td>
                    @endif
                   
                    <td class="td2"> $ {{number_format($rtpvaraguinaldoporporcional, 2)}}</td>
                    <td class="td3"></td>
    
                </tr>
                <tr>
                    <td class="td1">PRIMA VACACIONAL</td>
                    <td class="td3">25 % (por {{$dias_vacaciones}} días)</td>
                    <td class="td2"> $ {{number_format($rptvarvacacionesporporcionales, 2)}}</td>
                    <td class="td3"></td>
    
                </tr>
                <tr>
                    <td class="td1">VACACIONES NO TOMADAS</td>
                    <td class="td3">{{$dias_vacaciones_no_tomadas}} días</td>
                    <td class="td2"> $ {{number_format($total_vacaciones_no_tomadas, 2)}}</td>
                    <td class="td3"></td>
    
                </tr>
                <tr>
                    <td class="td1"><b>TOTAL PERCEPCIONES</b></td>
                    <td class="td3"></td>
                    <td class="td22"> $ {{number_format($totalper, 2)}}</td>
                    <td class="td3"></td>
    
                </tr>
            </table>
    </div>
                        
    <div class="row mb-4">
        <div class="fw-bold" style="width:80%;border:1px solid #2c2c2c;padding:5px!important;margin-bottom:5px;background-color:#000000;color:#ffffff;"> 
            DEDUCCIONES
        </div>
        <table>
            <tr>
                <td class="td1">IMSS</td>
                <td class="td3"></td>
                <td class="td2"> $ {{number_format($rptvardeudaimms, 2)}}</td>
                <td class="td3"></td>
            </tr>
            <tr>
                <td class="td1">INFONAVIT</td>
                <td class="td3"></td>
                <td class="td2"> $ {{number_format($rptvardeduedainfonavit, 2)}}</td>
                <td class="td3"></td>
            </tr>
            <tr>
                <td class="td1">PRESTAMOS</td>
                <td class="td3"></td>
                <td class="td2"> $ {{number_format($rptvardeudaprestamo, 2)}}</td>
                <td class="td3"></td>

            </tr>
            <tr>
                <td class="td1">OTROS</td>
                <td class="td3"></td>
                <td class="td2"> $ {{number_format($rptvarotrasdeudas, 2)}}</td>
                <td class="td3"></td>

            </tr>
            <tr>
                <td class="td1"><b>TOTAL DEDUCCIONES</b></td>
                <td class="td3"></td>
                <td class="td22"> $ {{number_format($rptotaldeducciones, 2)}}</td>
                <td class="td3"></td>

            </tr>
        </table>
     
    </div>
                                    

    <div class="row" style="margin-bottom:200px;">
        <div style="width:80%;border:1px solid #2c2c2c;padding:5px!important;margin-bottom:5px;font-size:12px;background-color:#000000;color:#ffffff;">
            TOTAL A PAGAR &nbsp;&nbsp;&nbsp;&nbsp;<b> $ {{number_format($rpttotalentregar, 2)}}</b>
        </div>
    </div>
 


    <div class="row">
        <table class="default">
            <tr>
                <th style="border: 0.3px solid #000000;border-radius:20px;width:50px;height:90px;"></td>
                <th style="border-bottom: 0.3px solid #000000;width:100px;"></td>
                <th style="border: 0.3px solid #000000;border-radius:80px;width:50px;height:90px;"></td>
            </tr>

            <tr>
                <td  style="width:25px;"></td>
                <td style="width:150px;" class="text-center">
                    <p class="fw-bold p-1">{{$nombreemplado}}</p>
                    <p>FIRMA DE CONFORMIDAD</p>
                </td>
                <td style="width:25px;"></td>
            </tr>

        </table>
    </div>

    <!--Marca de agua-->
    <div style="position: fixed;z-index:-1;bottom:0px;right:0px;margin-bottom:-50px;margin-right:-30px;">
        <img style="width : 300px;height : 300px; opacity: .2;" src="{{$marca_agua}}" alt="fincrelaguna">
    </div>
    </body>
</html>