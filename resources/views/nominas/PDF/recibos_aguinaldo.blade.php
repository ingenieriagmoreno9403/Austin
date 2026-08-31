<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
     
 
    <style>
       body{Font-family: Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif;font-size:10px;}
       p{font-size: 10px; font-weight: lighter;}
       h1{Font-family: Arial,sans-serif;}
       .title{font-size: 13px}
       .small{font-size: 7px!important;}
       .text-end{text-align: end;}
       .table td{border: 1px solid #000000 !important;font-size: 11px; font-weight: lighter;}
       .table th{border: 1px solid #000000 !important;font-size: 11px; font-weight: lighter;}
       .border{border: 1px solid #000000 !important;border-radius: 8px;}
       .text-justify{text-align:justify;}
       .text-dark{color: #000000}
       .text-center{text-align:center;}
       .firma1{margin-top: 210px;}
       .firma2{margin-top: 520px;}
       .firma3{margin-top: 80px;}
       .table1{width: 100%!important;}
       .table1 td{background-color: rgba(218, 218, 218, 0.56);font-size: 10px; font-weight: lighter;border:none!important;}
       .table1 th{background-color: rgba(154, 154, 154, 0.619);font-size: 10px; font-weight: bold!important;border:none!important;}
       .table2{width: 100%!important;}
       .table2 td{background-color: rgba(236, 236, 229, 0.56);font-size: 8.5px; font-weight: lighter;border:none!important;}
       .table2 th{background-color: rgba(236, 236, 229, 0.619);font-size: 10px; font-weight: bold!important;border:none!important;}
       .thead_shadow th{background-color: #cccccc7b!important;font-size: 10px!important; font-weight: light!important;}
       .thead_shadow2{background-color: #ffffff8a!important;font-size: 22px!important; font-weight: bold!important; color: #656565!important;}
       .t_light{background-color: #fafafa9a!important;font-size: 12px!important; font-weight: light!important; color: #484646!important;}
       .shadowTh{background-color: #d7d7d79a!important;font-size: 12px!important; font-weight: bold;}
       .shadowTd{background-color: #b0b0b098!important;font-size: 11px!important;color: #000000}
       .tdEspecial{background-color: rgb(255, 255, 255)!important;font-size: 18px!important; font-weight: bold!important;color: #3b3b3b}
       .table_little{font-size: 12px!important; font-weight: lighter;width: 100%!important;}
       .table_little td {background-color: rgb(236, 236, 229);font-size: 12px;}
       .text-dark{color: #000000}
       .text-center{text-align:center;}
       @page {margin-left: 80px!important;margin-right: 80px!important;margin-top: 80px!important;margin-bottom: 80px!important;}
       .page-break {page-break-after: always;}
       .container{padding-left: 40px!important;padding-right: 40px!important;padding-top: 60px!important;padding-bottom: 60px!important;}
       .fw-bold{font-weight: bold;}
       .fw-light{font-weight: light;}
       table{width:100%!important;}
       th{font-size:11px;font-weight: bold;background-color: rgb(26, 26, 26);color: #ffffff;border: 1px solid #fcfcfc !important;}
       td{font-size:10px;}
       .th{font-size:10px;font-weight: bold;background-color: rgb(26, 26, 26);color: #ffffff;}
       .punteado{border-style: dashed;border-top: 2px;border-color: #4f4f50;width: 100%;}
       .text-end{text-align: end;}
       .text-start{text-align: start;}
       .bg-light{background-color: #ffffff;color:#0000;}
       .rounded_stwift{border: 1px solid #000000 !important;border-bottom-left-radius:5px!important;border-bottom-right-radius:5px!important;margin-top: -5px;padding: 3px;}
       .border-rad{border-top-left-radius:5px!important;border-top-right-radius:5px!important;padding: 3px;}
       .border-rad2{border-bottom-right-radius:5px!important;}
    </style>
</head>
<body>
    
@foreach($varlistanomina as $item)
@php($total_percepciones = 0)
@php($total_retenciones = 0)

   @if($item->total_pagar> 0)
    <!--Pagina 1-->
    <div>
      <p style="font-size:12px;" class="fw-bold text-center">{{$razon_social}}</p>
      <hr>

      <table>
         <tbody>
            <tr>
               <td><p style="font-size:16px;" class="fw-bold">Recibo de nómina</p></td>
               <td>
                  <p style="font-size: 8px;">
                     RFC: {{$item->rfc}}<br>
                     IMSS: {{$item->nss}}
                  </p>
               </td>
              
               <td>
                  <div class="text-center">
                     <div class="th">Frecuencia de Pago</div>
                     QUINCENAL
                  </div>
               </td>

               <td>
                  <div class="text-center">
                     <div class="th">Fecha</div>
                     {{date("d/m/Y", strtotime($item->fecha_pago))}}
                  </div>
               </td>
            </tr>
         </tbody>
      </table>
      
      <br>

      <table>
         <tbody>
            <tr>
               <td>
                  <div>
                     <div class="th text-center">Empleado</div>
                     <p> Nombre: {{$item->nombre_empleado}}<br>
                        Puesto: {{$item->puesto}}<br>
                        {{-- Depto.:<br> --}}
                        RFC: {{$item->rfc}}<br>
                        CURP: {{$item->curp}}<br>
                        Domicilio fiscal: {{$item->domicilio_fiscal}}<br>
                        Regimen fiscal:  NULL
                     </p>
                  </div>
               </td>

               <td>
                  <div>
                    <div class="th text-center">Seguridad Social</div>
                    <p> Rgistro: {{$item->nss}}<br>
                     Tipo de Salario: Fijo<br>
                     S.D. : $ {{number_format($item->salario_diario + $item->salario_excedente, 2)}}<br>
                     Jornada: 8 horas<br>
                     Fecha de ingreso: {{date("d/m/Y", strtotime($item->fecha_ingreso_imss))}}<br>
                     Días Trabajados del Año : {{number_format($item->dias_trabajados, 2)}} días

                     <br>
                    </p>
                  </div>
               </td>
            </tr>
         </tbody>
      </table>

      <br>

      <table>
         <thead>
            <tr>
               <th>Percepción</th>
               <th>Monto</th>
               <th>Unidades</th>
               <th>Retencion</th>
               <th>Monto</th>
               <th>Saldo</th>
            </tr>
         </thead>

         <tbody>
            <tr>
               <td>
                  Aguinaldo <br>
               </td>
               <td>
                     $ {{number_format($item->aguinaldo_f+$item->aguinaldo_e, 2)}}<br>
               </td>
               <td>
                    {{$item->dias_aguinaldo_correspondientes}} días / {{$item->dias_aguinaldo_pagados}} días<br>
               </td>
               <td>
                     ISR<br>
               </td>
               <td>
                     $ {{number_format($item->isr_calculado, 2)}}<br>
                     @php($total_retenciones = $item->isr_calculado)
               </td>
            </tr>
         </tbody>
      </table>

      <br>
       @php($total_percepciones = $item->aguinaldo_f+ $item->aguinaldo_e )

      <table style="width: 60%!important;">
         <thead>
            <tr>
               <th>Subsidio al Empleo causado</th>
               <th>Monto</th>
            </tr>
         </thead>
         <tbody>
            <tr>
               <td> Subsidio Causado</td>
               <td>
                  @if($item->isr_calculado > 0)
                  $ {{number_format($item->aguinaldo_exento, 2)}}
                  @else
                  $ 0.00   
                  @endif
               </td>
            </tr>
         </tbody>
      </table>

      <br>
      <br>


      <table>
         <tbody>
            <tr>
               <td class="text-center">
                  <p style="font-size:8px;"  class="text-justify">
                     Recibí de esta empresa la cantidad que señala este recibo de pago, <br>
                     estando conforme con las percepciones y las retenciones descritas, <br>
                     por lo que certifico que no se adeuda cantidad alguna por ningún   <br> 
                     concepto.
                  </p>
                  <br>
                  <br>
                  <br>
                  <br>
                  <br>
                  <p>
                     ___________________________________________________________________<br>
                     Firma del empleado
                  </p>
               </td>

               <td>
                  <p>
                     <b>Total de percepciones:</b> $ {{number_format($total_percepciones, 2)}}<br>
                     <b>Total de retenciones:</b> $ {{number_format($total_retenciones, 2)}}<br>
                     <b>Pago:</b>$ {{number_format($item->total_pagar, 2)}}
                  </p>
                  <br>
                  <br>
                  <br>
                  <br>
                  <br>
                  <br>
               </td>
            </tr>
         </tbody>
      </table>
        
    </div>

    <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>