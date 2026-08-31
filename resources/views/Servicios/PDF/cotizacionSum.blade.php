<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cotizacion de Suministro</title>
    
    <style>
        @page {
            margin: 0;
            size: A4;
            margin-left: 0px!important;
            margin-right: 0px!important;
            margin-top: 280px!important;
            margin-bottom: 200px!important;
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            /* font-family: 'Inter', Arial, sans-serif!important; */
            Font-family: 'Inter', Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif!important;

            font-size: 12px;
            line-height: 1.4;
            color: #1A2B40;
            background-color: #ffffff;
        }
        div{
             Font-family: 'Inter', Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif!important;

        }

        h1,h2,h3,h4,h5,h6{Font-family: 'Inter', Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif!important;}


       .fw-bold{font-weight: bold!important;}
       .fw-light{font-weight: lighter!important;}
       .text-center{text-align: center!important;}
       .text-start{text-align: start!important;}
       .text-end{text-align: end!important;}
        
        .page {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            position: relative;
            background-color: #ffffff;
            padding: 20px;
        }
        
        /* Header Design - Fixed on all pages */
        .header {
            position: fixed;
            top: -280px;
            left: 0;
            right: 0;
            height: 250px;
            z-index: 1000;
        }
        
        .header-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('{{ public_path("Images/encabezado.png") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .logo-section {
            position: absolute;
            top: 20px;
            right: 40px;
            text-align: center;
            color: white;
            z-index: 10;
        }
        
        .logo-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .logo-subtitle {
            font-size: 10px;
            font-weight: 300;
            opacity: 0.9;
            line-height: 1.2;
        }
        
        .quotation-title {
            position: absolute;
            top: 180px;
            left: 30%;
            transform: translateX(80%);
            color: #1A2B40;
            font-size: 23px;
            font-weight: bold!important;
            /* font-weight: 700!important; */
            text-transform: uppercase;
            Font-family: 'Inter','Arial Black', Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif!important;
        }
        
        .quotation-date {
            position: absolute;
            top: 220px;
            left: 46%;
            transform: translateX(100%);
            color: #1A2B40;
            font-size: 14px;
            ont-weight: bold!important;
            font-weight: 700;
            Font-family: 'Inter', Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif!important;
        
        }
        
        /* Main content with header offset */
        .main-content {
            margin-top: 40px;
            margin-bottom: 120px;
            padding: 0 20px;
        }

        .company-section{
            margin-top: -50px;
        }
        
        /* Company Information - Exact match to image */
        .company-info {
            display: flex;
            justify-content: space-between;
            margin: 30px 0 20px 0;
            gap: 40px;
        }
        
        .company-block {
            flex: 1;
            width: 50%!important;
            vertical-align: top!important;
            padding: 15px;
           
        }

        .company-block  div{
            font-size: 14px;
            font-weight: 700;
            color: #1A2B40;
            margin-bottom: 8px;
            text-transform: uppercase;
            line-height: 1.3;
        }
    
        
        /* Service Table - Exact match to image */
        .service-section {
            margin: 20px 0;
            margin-top: 0px;
        }
        
        .service-header {
            background-color: #1A2B40;
            color: white;
            padding: 12px;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }
        
        .table th {
            color: #1A2B40;
            padding: 15px 8px;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            border: 2.5px solid #1A2B40;
        }
        
        .table tbody td {
            padding: 10px;
            font-size: 12px;
            border: 2.5px solid #1A2B40;
            text-align: center;
            vertical-align: middle;
            color: #1A2B40;
            font-weight: 700;
            width: 100%;
            
        }

        .table tfoot td {
            padding: 15px;
            font-size: 12px;
            border: 2.5px solid #1A2B40;
            text-align: right !important;
            vertical-align: middle;
            color: #1A2B40;
            font-weight: bold;
        }
        
        .table tfoot tr:last-child td {
            font-weight: bold;
            font-size: 14px;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .description-cell {
            text-align: center;
            max-width: 200px;
            word-wrap: break-word;
            font-size: 9px!important;
        }

        .description-cell {
            text-align: center;
            max-width: 200px;
            word-wrap: break-word;
            font-size: 10px!important;
        }
        
        /* Summary Section - Exact match to image */
        .summary-section {
            margin: 20px 0;
            text-align: right;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
            padding: 2px 0;
        }
        
        .summary-total {
            font-weight: 700;
            font-size: 14px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            margin-top: 5px;
        }
        
        /* Footer - Fixed on all pages */
        .footer {
            position: fixed;
            bottom: -200px;
            left: 0;
            right: 0;
            height: 250px;
            display: flex;
            z-index: 1000;
            background-color: #fFff;
            background-image: url('{{ public_path("Images/piepagina.png") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .footer-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('{{ public_path("Images/piepagina.png") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .footer-left {
            flex: 1;
            position: relative;
            padding: 15px 20px;
            color: white;
            z-index: 10;
        }
        
        .footer-right {
            flex: 1;
            padding: 15px 20px;
            text-align: center;
            position: relative;
            z-index: 10;
        }
        
        .footer-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .footer-contact {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: normal;
        }
        
        .icon-circle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background-color: #1A2B40;
            border-radius: 50%;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Iconos CSS para DomPDF */
        .icon-phone::before {
            content: "📞";
            font-size: 14px;
        }
        
        .icon-email::before {
            content: "✉";
            font-size: 14px;
        }
        
        .icon-location::before {
            content: "📍";
            font-size: 14px;
        }
        
        .icon-website::before {
            content: "🌐";
            font-size: 14px;
        }
        
        /* Iconos con CSS puro (alternativa) */
        .icon-phone-css {
            position: relative;
            width: 24px;
            height: 24px;
            background-color: #1A2B40;
            border-radius: 50%;
        }
        
        .icon-phone-css::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            background-color: white;
            border-radius: 50%;
        }
        
        .icon-phone-css::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 12px;
            height: 12px;
            border: 2px solid white;
            border-radius: 50%;
        }
        
        .reviso-section {
            position: relative;
            margin-bottom: 20px;
        }
        
        .reviso-text {
            font-size: 15px;
            font-weight: light;
            color: #000218;
            margin-bottom: 10px;
        }
        
        .watermark {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1;
            width: 100px;
            height: 100px;
            background-image: url('{{ public_path("Images/marcaM.png") }}');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
        
        .website {
            font-size: 16px;
            color: #1A2B40;
            /* display: flex; */
            align-items: end;
            justify-content: end;
            text-align: end;
            gap: 5px;
            font-weight: bold;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin: 0 auto;
            margin-top: 10px;
        }
        
        /* Page numbering */
        .page-number {
            position: fixed;
            bottom: 10px;
            right: 20px;
            font-size: 8px;
            color: #cccccc;
            z-index: 1001;
        }
        
        /* Utility classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: 600; }
        .fw-normal { font-weight: 400; }
        .text-truncate { overflow: hidden;text-overflow: ellipsis;white-space: nowrap;max-width: 100px;}
        
    </style>
</head>

<body>
    <!-- Header - Fixed on all pages -->
    <div class="header">
        <div class="header-bg"></div>
        <div class="logo-section">
            {{-- <div class="logo-title">UMM MINING</div>
            <div class="logo-subtitle">MATERIAL ELECTRICO, AUTOMATIZACION Y CONTROL</div> --}}
        </div>
        <div class="quotation-title"><b>COTIZACIÓN {{$datos_servicio->folio}}</b></div>
        <div class="quotation-date"><b>{{$fecha}}</b></div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Company Information - Exact match to image -->
        <table class="company-section">
            <tbody>
                <tr>
                    <td class="company-block">
                        <div>{{$datos_servicio->nombre_atencion}}</div>
                        <div>{{$datos_servicio->rfc_cliente}}</div>
                        <div>{{$datos_servicio->direccion_cliente}}7</div>
                        <div>{{$datos_servicio->telefono_atencion}}</div>
                        <div>{{$datos_servicio->correo_atencion}}</div>
                    </td>
                    
                    <td class="company-block">
                        <div>{{$datos_servicio->nombre_empresa_vendedor}}</div>
                        <div>{{$datos_servicio->rfc_empresa_vendedor}}</div>
                        <div>{{$datos_servicio->direccion_empresa_vendedor}}</div>
                        <div>{{$datos_servicio->telefono_vendedor}}</div>
                        <div>{{$datos_servicio->correo_vendedor}}</div>
                    </td>
                </tr>
                </tbody>
        </table>

        <!-- Service Table - Exact match to image -->
        @if($servicio->id_tiposervicio == 1)
            <div class="service-section">
                <div class="service-header">SERVICIO SUMINISTRO</div>
                <table class="table">
                    @php($colspan = 6)
                    <thead>
                        <tr>
                            <th>PDA</th>
                            <th>CANTIDAD</th>
                            <th>DESCRIPCIÓN</th>
                            <th>MARCA</th>
                            <th>T.ENTREGA</th>
                            <th>P. UNITARIO</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($cotizacion_det as $item)
                        <tr>
                            <td class="description-cell2">{{$item->pda}}</td>
                            <td class="description-cell2">{{$item->cantidad}}</td>
                            <td class="description-cell">{{$item->nombre_producto}}</td>
                            <td class="description-cell">{{$item->marca}}</td>
                            <td class="description-cell">{{$item->t_entrega}}</td>
                            <td class="text-truncate description-cell2">$ {{number_format($item->p_unitario,2)}}</td>
                            <td class="text-truncate description-cell2">$ {{number_format($item->total,2)}}</td>
                        </tr>
                        @endforeach
                    </tbody>
            
                    <!-- Summary - Exact match to image -->
                    <tfoot>
                        <tr>
                            <td colspan="7">
                                SUBTOTAL&nbsp;&nbsp;&nbsp;$ {{number_format($cotizacion->subtotal,2)}}
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7">
                                IVA (16%)&nbsp;&nbsp;&nbsp;$ {{number_format($cotizacion->iva,2)}}
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7">
                            TOTAL&nbsp;&nbsp;&nbsp;$ {{number_format($cotizacion->total,2)}}
                            </td>
                        </tr>
                        
                    </tfoot>
                </table>

                <div>
                        <p class="fw-bold"
                            style="text-align: justify!important;
                            word-wrap: break-word!important;
                            font-size:10px!important;
                            padding: 20px!important;">
                            {!! nl2br(e(trim($cotizacion->nota))) !!}
                        </p>
                </div>
            </div>
        @endif
    </div>

    <!-- Footer - Fixed on all pages -->
    <div class="footer">
        {{-- <div class="footer-bg"></div> --}}
        <table>
            <tbody>
                <tr>
                    <td style="width: 50%">
                        <div class="footer-left" style="width: 70%">
                            <div class="footer-title">VENDEDOR</div>
                            <div class="footer-contact">
                                <span>
                                   <b> TÉLEFONO</b> <br>
                                    {{$datos_servicio->telefono_vendedor}}</span>
                            </div>
                            <div class="footer-contact">
                                <span>
                                    <b> CORREO ELECTRÓNICO</b> <br>
                                    {{$datos_servicio->correo_vendedor}}</span>
                            </div>
                            <div class="footer-contact">
                                <span>
                                    <b> DIRECCIÓN</b> <br>
                                    {{$datos_servicio->direccion_empresa_vendedor}}</span>
                            </div>
                        </div>
                    </td>
                    
                    <td style="width: 50%;vertical-align: end;">
                        <div class="footer-right">
                            <div class="reviso-section">
                                <div class="watermark"></div>
                            </div>
                            <br><br><br>
                            <div class="signature-line"></div>
                             <div class="reviso-text">REVISÓ
                                <br>
                                {{$cotizacion->nombre_reviso }}
                             </div>

                            <br>
                            <div class="website text-end footer-page" style="text-align: end!important;">
                                <span>www.ummining.com.mx</span>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>


    <script type="text/php">
        if ( isset($pdf) ) {
            $pdf->page_script('
                $font = $fontMetrics->get_font("Inter, Arial, sans-serif", "normal");
                $pdf->text(520, 800, "Pág $PAGE_NUM de $PAGE_COUNT", $font, 8);
            ');
        }
    </script>
</body>
</html> 