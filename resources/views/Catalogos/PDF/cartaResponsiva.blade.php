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
       p{font-size: 13px; font-weight: normal;line-height:normal;}
       .title{font-size: 13px}
       .small{font-size: 7px!important;}
       .text-end{text-align: end;}
       .table td{border: 1px solid #000000 !important;font-size: 11px; font-weight: lighter;}
       .table th{border: 1px solid #000000 !important;font-size: 11px; font-weight: lighter;}
       .border{border: 1px solid #000000 !important;font-size: 11px; font-weight: lighter;}
       .text-justify{text-align:justify;}
       .text-dark{color: #000000}
       .text-center{text-align:center;}
       .firma1{margin-top: 210px;}
       .firma2{margin-top: 545px;}
       .firma3{margin-top: 440px;}

       .mt_small{margin-top: 30px;}
       .mt_medium{margin-top: 80px;}
       .mt_large{margin-top: 140px;}

       .mb_small{margin-bottom: 40px;}
       .mb_medium{margin-bottom: 130px;}
       .mb_large{margin-bottom: 135px;}

       .page-break {page-break-after: always;}
       @page {
		margin-left: 120px!important;margin-right: 110px!important;margin-top: 50px!important;margin-bottom: 110px!important;
	   }

       .logo_banco{width:140px;height: 60px;}
       .fw-bold{font-weight: bold!important;}
       .fw-light{font-weight: lighter!important;}
       .text-center{text-align: center!important;}
       .bg-light{background-color: rgb(255, 255, 255)!important;}
       .text_s{color: #262626}
       /* .logoV{width: 85px!important;height:75px!important;margin: 5px;} */
       .logoV{width: 150px!important;height:75px!important;margin: 5px;}
    </style>
</head>
<body>
    
    @php($icono = "Images/ummining_500.png")
    @php($marca_agua = "Images/ummining_ico.png")

    <!--Pagina 1-->
    <div>
        <center>
            <table>
              <tr>
                <td><img class="logoV"  src="{{ public_path($icono) }}" alt="fincreLaguna"></td>
                <td style="font-size:14;color:#f29900;" class="fw-bold">
                  <br>&nbsp;&nbsp;&nbsp; CARTA RESPONSIVA<br></td>
              </tr>
            </table>
            <hr style="border:1px solid#f29900;margin-top:0px;">
            <p class="fw-bold" style="font-size: 10px;color:#f29900;margin-top:-10px;">{{$nombreEmpresa}}</p>
        </center>

        <br/><br/>
        @if($tipo == "caja")
            <div class="row">
                <p class="text-end">
                    {{$fechaEntrega}}
                </p>
                <p class="text-justify">
                    Por medio de la presente se hace entrega de la <b>{{$nombreCaja}}</b> con el numero identificador 
                    <a class="text-decoration-underline text-dark">{{$id}}</a> al "Empleado" 
                    <a class="text-decoration-underline text-dark">{{$nombreResponsable}}</a> con numero 
                    identificador <a class="text-decoration-underline text-dark">{{$idEmpleado}}</a>, quien asume 
                    la responsabilidad del control de la <b>caja asiganda</b>, comprometiendose a manejar la caja 
                    debidamente en conformidad a las necesidades que se otorguen por parte de la empresa.
                </p>
            </div>

            <br/><br/>

            <div class="row">
                <p class="text-justify">
                    El "Empleado" <a class="text-decoration-underline text-dark">{{$nombreResponsable}}</a> tiene como obligacion reportar las salidas de dinero que se apliquen, obteniendo total responsabilidad, de no ser asi debera asumir la responsabilidad del pago de los cargo no justificados o el mal manejo de la misma.           
                </p>
            </div>

        @elseif($tipo == "caja_chica")
            @foreach($info_caja as $data)
                <div class="row text-justify">
                    <p>
                        Por medio de la presente, se hace constar con fecha {{$fechaEntrega}}, 
                        se Asigna a {{$nombreResponsable}} quien tiene Relación Laboral 
                        con {{$nombreEmpresa}} el Fondo de Caja Chica de la 
                        Sucursal {{$data->sucursal}}.
                        Con la única Finalidad de Adquirir Artículos de Limpieza, Papelería, Servicios de Luz, 
                        Agua e Internet y Recursos Necesarios para la Óptima Operación de la Sucursal {{$data->sucursal}}, Con 
                        el compromiso del Buen Uso del manejo de los Recursos, a través de lo siguiente:
                    </p>
                </div>
                <br>

                <div class="row text-justify">
                    <p class="fw-bold" style="padding-left: 50px!important;width:90%;">
                            • Realizar las Compras únicamente de los Artículos y Servicios para lo que fue creado el Fondo de Caja Chica, amparados con sus respectivos comprobantes.
                        <br>• En caso de Necesitar Adquirir Productos o Servicios distintos a los señalados en el punto Anterior, será previa autorización por escrito de Administradora Corporativa. 
                        <br>• Realizar la Captura de cada uno de los Gastos efectuados en el ERP.
                        <br>• Conservar Integro el Importe Total del Fondo, Respaldado por el Efectivo y Comprobantes de las Compras realizadas.
                        <br>• No Mezclar Recursos Personales con el Fondo de la Sucursal.
                        <br>• No Realizar Préstamos Personales Propios, ni a los Compañeros. 
                        <br>• No serán reembolsado los Gastos que no cumplan con las normativas antes mencionadas.
                        <br>• Se pondrá a Disposición, sin que exista previo aviso, la documentación y el efectivo al ser requerido para la práctica del Arqueo correspondiente.
                    </p>
                </div>
                <br>

                <div class="row text-justify">
                    <p>
                        En caso de detectarse algún faltante o documento que no cumpla con las Normativas,
                        se Autoriza a {{$nombreEmpresa}} realizar la Retención de mi Sueldo por dicho Faltante
                    </p>
                </div>
            @endforeach
        @endif
        {{-- FIRMA --}}
        <div class="row">
            <table class="firma1">
                <tbody class="text-center">
                    <tr>
                        <td>
                            <p>_____________________________________</p>
                            <b>{{$nombreResponsable}}</b>
                        </td>
                        <td>
                            <p>_____________________________________</p>
                            <b>FIRMA DE QUIEN ENTREGA</b>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>  
        
          <!--Marca de agua-->
        <div style="position: fixed;z-index:-1;bottom:0px;right:0px;margin-bottom:-50px;margin-right:-30px;">
            <img style="width : 300px;height : 300px; opacity: .08;" src="{{public_path($marca_agua)}}" alt="valemil">
        </div>
    </div>
    
</body>
</html>