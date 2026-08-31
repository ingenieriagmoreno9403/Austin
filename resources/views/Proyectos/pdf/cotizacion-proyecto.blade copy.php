<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

    <style>
       body{ Font-family: Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif; font-size:10px; }
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
       @page { margin-left: 40px!important;margin-right: 40px!important;margin-top: 40px!important;margin-bottom: 40px!important; }
       .logoV{width: 150px!important;height:60px!important;margin: 5px;}
       .fw-bold{font-weight: bold!important;}
       .fw-light{font-weight: lighter!important;}
       .bg-light{background-color: rgb(255, 255, 255)!important;}
       .text_s{color: #262626}
       .badge-orange-dark{background-color: #ff9d14!important;color: #ffffff!important;font-weight: normal!important;}
       .badge-orange{background-color: #ffd9a0d4!important;color: #ec8a00!important;font-weight: normal!important;}
       .bg-orange {background-color: #FFA602!important;}
       footer { position: fixed; bottom: -20px; left: 0px; right: 0px; height: 50px; font-size: 10px!important }
       .wrap-text { white-space: pre-line !important; word-break: break-word !important; max-width: 350px; font-size: 11px; }
    </style>
    
</head>
<body>
    @php($icono = "Images/ummining_500.png")
    @php($marca_agua = "Images/ummining_ico.png")

    <div>
        <center>
            <table class="tab">
              <tr>
                <td style="width:10%;"><img class="logoV"  src="{{ public_path($icono) }}" alt="ummining"></td>
                <td style="font-size:28;color:#f26d00;font-weight:bold" class="fw-bold text-start">
                  &nbsp;&nbsp;&nbsp; <b>COTIZACION PROYECTO</b><br></td>
                <td class="text-end">
                    <br>
                    {{ $fechaEmision }}
                </td>
              </tr>
            </table>
            <hr style="border:1px solid#f29900;margin-top:0px;">
        </center>

        <div class="pb-3 pt-0">
            <div class="row m-0">
                <div class="mb-2 bg-body">
                    <table class="fs-8 table table-striped table-hover">
                        <tr class="text-center">
                            <th class="badge-orange-dark fw-bold">CLIENTE</th>
                            <th class="badge-orange fw-bold">PROYECTO</th>
                        </tr>
                        <tr>
                            <th>Nombre</th>
                            <th>Folio</th>
                        </tr>
                        <tr>
                            <td>{{ $servicio->cliente }}</td>
                            <td>{{ $servicio->folio }}</td>
                        </tr>
                        <tr>
                            <th>Periodo</th>
                            <th>Emitido</th>
                        </tr>
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($servicio->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($servicio->fecha_limite)->format('d/m/Y') }}</td>
                            <td>{{ $fechaEmision }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row m-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr class="text-start">
                            <th class="badge-orange text-dark text-center" colspan="4">
                                <h6><b>DIVISIONES DEL PROYECTO</b></h6>
                            </th>
                        </tr>
                        <tr>
                            <th class="bg-orange" colspan="4"></th>
                        </tr>
                        <tr class="bg-secondary">
                            <th class="table-secondary">PARTIDA</th>
                            <th class="table-secondary">TITULO</th>
                            <th class="table-secondary">RANGO</th>
                            <th class="table-secondary">MONTO</th>
                        </tr>
                        <tr>
                            <th class="bg-orange" colspan="4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($i = 1)
                        @foreach($divisiones as $d)
                        <tr>
                            <td class="fw-bold">{{ $i++ }}</td>
                            <td class="wrap-text">{{ $d->titulo ?: ('Plazo '.$d->plazo) }}</td>
                            <td>{{ \Carbon\Carbon::parse($d->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($d->fecha_fin)->format('d/m/Y') }}</td>
                            <td class="text-end">$ {{ number_format($d->monto,2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end table-warning fw-bold">TOTAL</td>
                            <td class="text-end">$ {{ number_format($montoTotal,2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div style="position: fixed;z-index:-1;bottom:0px;right:0px;margin-bottom:-50px;margin-right:-30px;">
                <img style="width : 300px;height : 300px; opacity: .08;" src="{{public_path($marca_agua)}}" alt="marca">
            </div>
        </div>
    </div>

    <footer>
        Proyecto: {{ $servicio->folio }} | Cliente: {{ $servicio->cliente }}
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


