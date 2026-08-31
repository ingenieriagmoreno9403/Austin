<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
     
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
{{--  --}}

    <style>
        /* @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap");
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap'); */
  
       body{
        Font-family: Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif;
        font-size:10px;
        }
        h1,h2,h3,h4,h5,h6{Font-family: Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif!important;}
       p{font-size: 13px; font-weight: normal;line-height:normal;}
       .title{font-size: 13px}
       .small{font-size: 7px!important;}
       .text-end{text-align: end!important;}
       .tab{width:100%}
       .table{width:100%;}
       .table td{border: 1px solid #b5b5b5 !important;font-size: 11px; font-weight: lighter; padding: 5px;}
       .table th{border: 1px solid #b5b5b5 !important;font-size: 11px; font-weight: lighter; padding: 5px;}
       .border{border: 1px solid #b5b5b5 !important;font-size: 11px; font-weight: lighter;}
       .bg-secondary{background-color: #e6e6e6}
       .table-secondary{background-color: #d0d0d0}
       .table-warning{background-color: #fff6a3}
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
       .logoV{width: 150px!important;height:60px!important;margin: 5px;}

       .badge-orange-dark{background-color: #ff9d14!important;color: #ffffff!important;font-weight: normal!important;}
        .badge-orange{background-color: #ffd9a0d4!important;color: #ec8a00!important;font-weight: normal!important;}
        .bg-orange {background-color: #FFA602!important;}
            @page {
            margin-left: 40px!important;margin-right: 40px!important;margin-top: 40px!important;margin-bottom: 40px!important;
        }

         footer {
            position: fixed;
            bottom: -20px;
            left: 0px;
            right: 0px;
            height: 50px;
            font-size: 10px!important

            /** Extra personal styles **/
            /* background-color: #03a9f4;
            color: white;
            text-align: center;
            line-height: 35px; */
        }

        .wrap-text {
            white-space: pre-line !important;
            word-break: break-word !important;
            max-width: 350px;
            font-size: 11px;
        }

    </style>
    
</head>
<body>
    
    @php($icono = "Images/ummining_500.png")
    @php($marca_agua = "Images/ummining_ico.png")

    <!--Pagina 1-->
    <div>
        <center>
            <table class="tab">
              <tr>
                <td style="width:10%;"><img class="logoV"  src="{{ public_path($icono) }}" alt="fincreLaguna"></td>
                <td style="font-size:28;color:#f26d00;font-weight:bold" class="fw-bold text-start">
                  &nbsp;&nbsp;&nbsp; <b>COTIZACION</b><br></td>
                <td class="text-end">
                    <br>
                    {{$fecha}}
                </td>
              </tr>
            </table>
            <hr style="border:1px solid#f29900;margin-top:0px;">
        </center>

        <div class="pb-3 pt-0">

            {{-- ENCABEZADO --}}
            @foreach($datos_servicio as $item)
            <div class="row m-0">
                <div class="mb-2 bg-body">

                    <table class="fs-8 table table-striped table-hover">
                        <tr class="text-center">
                            <th class="badge-orange-dark fw-bold">ATENCIÓN</th>
                            <th class="badge-orange fw-bold">VENDEDOR</th>
                        </tr>

                        <tr>
                            <th>Nombre</th>
                            <th>Nombre</th>
                        </tr>

                        <tr>
                            <td>{{$item->nombre_cliente}}</td>
                            <td>{{$item->nombre_vendedor}}</td>
                        </tr>

                        <tr>
                            <th>Puesto</th>
                            <th>Puesto</th>
                        </tr>

                        <tr>
                            <td>-</td>
                            <td class="border">{{$item->puesto_vendedor}}</td>
                        </tr>

                        <tr>
                            <th class="2"><br></th>
                        </tr>

                        <tr class="text-center">
                            <th class="badge-orange-dark fw-bold">EMPRESA</th>

                            <th class="badge-orange fw-bold">EMPRESA</th>
                        </tr>

                        <tr>
                            <th>Razón Social</th>
                            <th>Razón Social</th>
                        </tr>

                        <tr>
                            <td>{{$item->nombre_empresa_cliente}}</td>
                            <td>{{$item->nombre_empresa_vendedor}}</td>
                        </tr>

                        <tr>
                            <th>RFC</th>
                            <th>RFC</th>
                        </tr>

                        <tr>
                            <td>{{$item->rfc_cliente}}</td>
                            <td>{{$item->rfc_empresa_vendedor}}</td>
                        </tr>

                        <tr>
                            <th>Dirección</th>
                            <th>Dirección</th>
                        </tr>

                        <tr>
                            <td>{{$item->direccion_cliente}}</td>
                            <td>{{$item->direccion_empresa_vendedor}}</td>
                        </tr>

                        <tr>
                            <th>Télefono</th>
                            <th>Télefono</th>
                        </tr>

                        <tr>
                            <td>{{$item->clientes_telefono}}</td>
                            <td>{{$item->telefono_vendedor}}</td>
                        </tr>

                        <tr>
                            <th>Correo Electrónico</th>
                            <th>Correo Electrónico</th>
                        </tr>

                        <tr>
                            <td>{{$item->clientes_correo}}</td>
                            <td>{{$item->correo_vendedor}}</td>
                        </tr>
                    </table>
                </div>
            </div>
            @php($reviso = $item->reviso)
            @endforeach
        
            {{--TABLA PRINCIPAL--}}
            <div class="row m-0">
                @if(strtolower($tipo) == 'suministro')
                <table class="table table-hover table-striped">
                    <thead>
                        <tr class="text-start">
                            <th class="badge-orange text-dark text-center" colspan="7">
                                <h6><b>SERVICIO {{strtoupper($tipo)}}</b></h6>
                            </th>
                        </tr>
                        <tr>
                            <th class="bg-orange" colspan="7"></th>
                        </tr>

                        <tr class="bg-secondary">
                            <th class="table-secondary">PARTIDA</th>
                            <th class="table-secondary">CANTIDAD</th>
                            <th class="table-secondary">DESCRIPCIÓN</th>
                            <th class="table-secondary">UNIDAD</th>
                            <th class="table-secondary">PROVEEDOR</th>
                            <th class="table-secondary">OBSERVACIÓN</th>
                            <th class="table-secondary">TOTAL</th>
                        </tr>

                        <tr>
                            <th class="bg-orange" colspan="7"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @php($i = 1)
                        @php($suma_total = 0)
                        @foreach($productos_suministro as $item)
                        <tr>
                            <td class="fw-bold">{{$i++}}</td>
                            <td class="text-truncate">{{$item->cantidad}}</td>
                            <td class="wrap-text">
                                @if(strlen($item->descripcion_producto) > 30)
                                    {{ substr($item->descripcion_producto,0,30) }}...
                                @else
                                    {{ $item->descripcion_producto }}
                                @endif
                            </td>
                            <td class="text-truncate">{{$item->unidad_medida}}</td>
                            <td class="text-truncate">{{$item->proveedor}}</td>
                            <td class="wrap-text">{{$item->otrosconceptos1}}</td>
                            <td class="text-truncate">$ {{number_format($item->total,2)}}</td>
                        </tr>
                        @php($suma_total = $suma_total + $item->total)
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-end">SUBTOTAL</td>
                            <td>$ {{number_format($suma_total,2)}}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end">IVA <b class="text-secondary fs-8 fw-normal">(16 %)</b></td>
                            @php($iva = .16 * $suma_total)
                            <td>$ {{number_format($iva,2)}}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end table-warning fw-bold">TOTAL</td>
                            <td>$ {{number_format($suma_total + $iva,2)}}</td>
                        </tr>
                    </tfoot>
                </table>
                @elseif(strtolower($tipo) == 'integracion' || strtolower($tipo) == 'integración')
                <table class="table table-hover table-striped">
                    <thead>
                        <tr class="text-start">
                            <th class="badge-orange text-dark text-center" colspan="7">
                                <h6><b>SERVICIO {{strtoupper($tipo)}}</b></h6>
                            </th>
                        </tr>
                        <tr>
                            <th class="bg-orange" colspan="7"></th>
                        </tr>

                        <tr class="bg-secondary">
                            <th class="table-secondary">PARTIDA</th>
                            <th class="table-secondary">CANTIDAD</th>
                            <th class="table-secondary">DESCRIPCIÓN</th>
                            <th class="table-secondary">UNIDAD</th>
                            <th class="table-secondary">PRECIO U.</th>
                            <th class="table-secondary">OBSERVACIÓN</th>
                            <th class="table-secondary">TOTAL</th>
                        </tr>

                        <tr>
                            <th class="bg-orange" colspan="7"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @php($i = 1)
                        @php($suma_total = 0)
                        @foreach($productos_suministro as $item)
                        <tr>
                            <td class="fw-bold">{{$item->partida}}</td>
                            <td class="text-truncate">{{$item->cantidad}}</td>
                            <td class="wrap-text">{{$item->descripcion}}</td>
                            <td class="text-truncate">{{$item->unidad}}</td>
                            <td class="text-truncate">$ {{number_format($item->{'Precio u.'},2)}}</td>
                            <td class="wrap-text">{{$item->observacion}}</td>
                            <td class="text-truncate">$ {{number_format($item->total,2)}}</td>
                        </tr>
                        @php($suma_total = $suma_total + $item->total)
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-end">SUBTOTAL</td>
                            <td>$ {{number_format($suma_total,2)}}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end">IVA <b class="text-secondary fs-8 fw-normal">(16 %)</b></td>
                            @php($iva = .16 * $suma_total)
                            <td>$ {{number_format($iva,2)}}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end table-warning fw-bold">TOTAL</td>
                            <td>$ {{number_format($suma_total + $iva,2)}}</td>
                        </tr>
                    </tfoot>
                </table>
                @else
                <table class="table table-hover table-striped">
                    <thead>
                        <tr class="text-start">
                            <th class="badge-orange text-dark text-center" colspan="8">
                                <h6><b>SERVICIO {{strtoupper($tipo)}}</b></h6>
                            </th>
                        </tr>
                        <tr>
                            <th class="bg-orange" colspan="8"></th>
                        </tr>

                        <tr class="bg-secondary">
                            <th class="table-secondary">PARTIDA</th>
                            <th class="table-secondary">CANTIDAD</th>
                            <th class="table-secondary" colspan="2">DESCRIPCIÓN</th>
                            <th class="table-secondary">UNIDAD</th>
                            <th class="table-secondary">PRECIO U.</th>
                            <th class="table-secondary">OBSERVACIÓN</th>
                            <th class="table-secondary">TOTAL</th>
                        </tr>

                        <tr>
                            <th class="bg-orange" colspan="8"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @php($i = 1)
                        @php($suma_total = 0)
                        @foreach($obtnerproductosxservicio as $item)
                        <tr>
                            <td class="fw-bold">{{$i++}}</td>
                            <td class="text-truncate">{{$item->cantidad_total}}</td>
                            <td class="wrap-text" colspan="2">{{$item->nombre}}</td>
                            <td class="text-truncate">{{$item->unidad_medida}}</td>
                            <td class="text-truncate">$ {{number_format($item->precio_unitario,2)}}</td>
                            <td class="wrap-text">{{$item->otrosconceptos1}}</td>
                            @php($total = $item->precio_unitario*$item->cantidad_total)
                            <td class="text-truncate"> $ {{number_format($total,2)}}</td>
                        </tr>
                        @php($suma_total = $suma_total + $total)
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="6" class=""><br></td>
                            <td class="bg-secondary">SUBTOTAL</td>
                            <td>$ {{number_format($suma_total,2)}}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class=""><br></td>
                            <td class="table-secondary">IVA <b class="text-secondary fs-8 fw-normal">(16 %)</b></td>
                            @php($iva = .16 * $suma_total)
                            <td>$ {{number_format($iva,2)}}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class=""><br></td>
                            <td class="table-warning fw-bold">TOTAL</td>
                            <td>$ {{number_format($suma_total + $iva,2)}}</td>
                        </tr>
                    </tfoot>
                </table>
                @endif
            </div>

            @if(isset($revisor_condiciones))
            <div class="row">
                <div class="col-12 text-center" style="margin-top: 20px;">
                    <b>Revisó:</b> {{ $revisor_condiciones->revisor }}
                </div>
            </div>
            @endif

            {{-- PIE DE PAGINA --}}
            <div class="row">
                <table class="firma1 tab">
                    <tbody class="text-center">
                        <tr>
                            <td style="padding: 10px">
                                <p>_____________________________________</p>
                                <b class="mt-2" style="font-size: 16px">Revisó</b><br>
                                <b style="font-size: 12px" class="fw-normal">
                                    @foreach($EmpleadosActivos as $item)
                                        @if($reviso == $item->id && !is_null($reviso))
                                            {{$item->Nombre}}
                                        @endif
                                    @endforeach
                                </b><br>
                                
                            </td>
                        </tr>

                        <tr>
                            <td style="padding: 10px">
                                <div class="border" style="border-radius: 10px">
                                    <div class="row p-2 border-4 border-start border-danger bg-body" style="border-radius: 10px">
                                            <p class="text-wrap text-justify" style="font-size: 10px; color:#7b7b7b;">
                                                {{ $revisor_condiciones->condiciones }}
                                            </p>
                                    </div>
                                </div>
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

    <footer>
        Usuario Imprime: {{$nom_imprime}} <br>
        Fecha Impresion: {{$date}}
    </footer>

    <script type="text/php">
        if ( isset($pdf) ) {
            $pdf->page_script('
                $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
                $pdf->text(270, 760, "Pág $PAGE_NUM de $PAGE_COUNT", $font, 10);
            ');
        }
	</script>


    
</body>
</html>