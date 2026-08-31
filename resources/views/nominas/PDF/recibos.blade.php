<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Recibos de nómina</title>
     
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script> -->

    <style>
       body{Font-family: Arial,Candara, Calibri, Segoe, Segoe UI, Optima, sans-serif;font-size:10px;line-height: 1.1!important;}
       p{font-size: 11px; font-weight: lighter; margin: 0; padding: 0;}
       h1{Font-family: Arial,sans-serif;}
       .title{font-size: 13px}
       .small{font-size: 7px!important;}
       .text-end{text-align: end;}
       .border{border: 1px solid #000000 !important;}
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
       @page {margin-left: 80px!important;margin-right: 80px!important;margin-top: 40px!important;margin-bottom: 40px!important;}
       .page-break {page-break-after: always;}
       .container{padding-left: 40px!important;padding-right: 40px!important;padding-top: 60px!important;padding-bottom: 60px!important;}
       .fw-bold{font-weight: bold;}
       .fw-light{font-weight: light;}
       table{width:100%!important;}
       .table{border-collapse: collapse !important;border-spacing: 0 !important;width: 100%;}
       .table th{font-size:11px;font-weight: bold;background-color: rgb(26, 26, 26);color: #ffffff; padding: 4px;
         border: 1px solid #131313 !important;line-height: 1.1!important;margin: 0;}
       .table td{font-size:10px; padding: 4px; border: 1px solid #131313 !important;line-height: 1.1!important;margin: 0;vertical-align: top;}
       
       .table2 th{font-size:11px;font-weight: bold;background-color: rgb(26, 26, 26);color: #ffffff; padding: 4px;
         border: 1px solid #131313 !important;line-height: 1.1!important;margin: 0;}
       .table2 td{font-size:10px; padding: 4px; background-color: rgb(255, 255, 255);line-height: 1.1!important;margin: 0;vertical-align: top;}

       .th{font-size:11px;font-weight: bold;background-color: rgb(26, 26, 26);color: #ffffff; padding: 4px;
       border: 1px solid #131313 !important;}
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
    
@php
   $recibosNomina = collect($varlistanomina)->filter(function ($n) {
      return $n->total_apagar > 0;
   })->values();
@endphp

@foreach($recibosNomina as $nomina)
    <!--Pagina 1-->
    <div class="border" style="padding: 10px 0px 100px 10px;">
      <div style="font-size:12px;" class="fw-bold text-center">{{$razon_social}}</div> <br>


      <table cellspacing="0" cellpadding="0" style="width: 100%;">
         <tbody>
            <tr>
               <td style="border: none; vertical-align: top;width: 42%!important;"><p style="font-size:30px;" class="fw-bold">Recibo de nómina</p></td>
               <td style="border: none; vertical-align: top;">
                  <p style="font-size: 10px;padding: 10px">
                     RFC: {{$nomina->rfc}}<br>
                     IMSS: {{$nomina->nss}}
                  </p>
               </td>
              
               <td style="padding: 0; width: 18%;">
                  <table class="table" cellspacing="0" cellpadding="0" style="width: 100%;">
                     <tr>
                        <th class="text-center">Frecuencia de Pago</th>
                     </tr>
                     <tr>
                        <td class="text-center">{{ strtoupper($nomina->frecuencia_pago ?? '-') }}</td>
                     </tr>
                  </table>
               </td>

               <td style="padding: 0; width: 15%;">
                  <table class="table" cellspacing="0" cellpadding="0" style="width: 100%;">
                     <tr>
                        <th class="text-center">Fecha</th>
                     </tr>
                     <tr>
                        <td class="text-center">{{date("d/m/Y", strtotime($nomina->fecha_fin))}}</td>
                     </tr>
                  </table>
               </td>
            </tr>
         </tbody>
      </table>
      <br>
      
      <table class="table" cellspacing="0" cellpadding="0" style="margin-top: 0 !important;">
         <thead>
            <tr>
               <th>Empleado</th>
               <th>Seguridad Social</th>
            </tr>
         </thead>

         <tbody>
            <tr>
               <td>
                     Nombre: {{$nomina->NOMBRE}}<br>
                     Puesto: {{$nomina->puesto}}<br>
                     {{-- Depto.:<br> --}}
                     RFC: {{$nomina->rfc}}<br>
                     CURP: {{$nomina->curp}}<br>
                     Domicilio fiscal: {{$nomina->domicilio_fiscal}}<br>
                     Regimen fiscal:  605/Sueldos y Salarios e Ingresos Asimilados a Salarios
                  
               </td>

               <td>
                     Rgistro: {{$nomina->nss}}<br>
                     Tipo de Salario: Fijo<br>
                     S.D. : $ {{number_format($nomina->salario_diario, 2)}}<br>
                     S.D.I. : $ {{number_format($nomina->salario_diario_integrado, 2)}}<br>
                     Jornada: 8 horas<br>
                     Fecha de ingreso: 
                     @if($nomina->fecha_ingreso_imss)
                        {{date("d/m/Y", strtotime($nomina->fecha_ingreso_imss))}}
                     @else
                     -
                     @endif
                     <br>
               </td>
            </tr>
         </tbody>
      </table>
      <br>

      <table class="table2" cellspacing="0" cellpadding="0">
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
                  @if($nomina->total_sueldo != 0) Sueldo Ordinario<br> @endif
                  @if($nomina->total_horas_extras != 0) Horas Extras<br> @endif
                  @if($nomina->despensa != 0) Despensa<br> @endif
                  @if($nomina->otros != 0) Otros<br> @endif
                  @if(($nomina->percepcion_extraordinaria + $nomina->pago_dias_descanso + $nomina->pago_prima_dominical) != 0) Percepción Exenta<br> @endif
                  @if($nomina->pago_dias_vacaciones != 0) Días de Vacaciones<br> @endif
                  @if($nomina->pago_prima_vacacional != 0) Prima Vacacional<br> @endif
               </td>
               <td>
                  @if($nomina->total_sueldo != 0) $ {{number_format($nomina->total_sueldo, 2)}}<br> @endif
                  @if($nomina->total_horas_extras != 0) $ {{number_format($nomina->total_horas_extras, 2)}}<br> @endif
                  @if($nomina->despensa != 0) $ {{number_format($nomina->despensa, 2)}}<br> @endif
                  @if($nomina->otros != 0) $ {{number_format($nomina->otros, 2)}}<br> @endif
                  @if(($nomina->percepcion_extraordinaria + $nomina->pago_dias_descanso + $nomina->pago_prima_dominical) != 0) $ {{number_format($nomina->percepcion_extraordinaria + $nomina->pago_dias_descanso + $nomina->pago_prima_dominical, 2)}}<br> @endif
                  @if($nomina->pago_dias_vacaciones != 0) $ {{number_format($nomina->pago_dias_vacaciones, 2)}}<br> @endif
                  @if($nomina->pago_prima_vacacional != 0) $ {{number_format($nomina->pago_prima_vacacional, 2)}}<br> @endif
               </td>
               <td>
                  @if($nomina->total_sueldo != 0) {{$nomina->dias_laborados}} días<br> @endif
                  @if($nomina->total_horas_extras != 0) {{$nomina->horas_extras}} horas<br> @endif
                  @if($nomina->pago_dias_vacaciones != 0) {{$nomina->dias_vaciones}} días<br> @endif
               </td>
               <td>
                  @if($nomina->pago_isr != 0) ISR<br> @endif
                  @if($nomina->pago_imss != 0) IMSS<br> @endif
                  @if($nomina->pago_infonavit != 0) INFONAVIT<br> @endif
                  @if($nomina->fonacot != 0) FONACOT<br> @endif
                  @if($nomina->deudores_fiscal != 0) Prestamo de Empresa<br> @endif
               </td>
               <td>
                  @if($nomina->pago_isr != 0) $ {{number_format($nomina->pago_isr, 2)}}<br> @endif
                  @if($nomina->pago_imss != 0) $ {{number_format($nomina->pago_imss, 2)}}<br> @endif
                  @if($nomina->pago_infonavit != 0) $ {{number_format($nomina->pago_infonavit, 2)}}<br> @endif
                  @if($nomina->fonacot != 0) $ {{number_format($nomina->fonacot, 2)}}<br> @endif
                  @if($nomina->deudores_fiscal != 0) $ {{number_format($nomina->deudores_fiscal, 2)}}<br> @endif
               </td>
            </tr>
         </tbody>
      </table>
      <br>

      @php
         $total_percepciones = $nomina->total_sueldo + $nomina->total_horas_extras + $nomina->despensa + $nomina->otros + $nomina->percepcion_extraordinaria + $nomina->pago_dias_descanso + $nomina->pago_prima_dominical + $nomina->pago_dias_vacaciones + $nomina->pago_prima_vacacional;
         $total_retenciones = $nomina->pago_isr + $nomina->pago_imss + $nomina->pago_infonavit + $nomina->fonacot + $nomina->deudores_fiscal;
      @endphp

      @if($nomina->pago_subsidio != 0)
      <table class="table2" cellspacing="0" cellpadding="0" style="width: 60%!important;">
         <thead>
            <tr>
               <th>Subsidio al Empleo causado</th>
               <th>Monto</th>
            </tr>
         </thead>
         <tbody>
            <tr>
               <td> Subsidio Causado</td>
               <td>$ {{number_format($nomina->pago_subsidio, 2)}}</td>
            </tr>
         </tbody>
      </table>
      @endif

      <br>
      <br>


      <table cellspacing="0" cellpadding="0">
         <tbody>
            <tr>
               <td class="text-center" style="border: none;">
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

               <td style="border: none;">
                  <p>
                     <b>Total de percepciones:</b> $ {{number_format($total_percepciones, 2)}}<br>
                     <b>Total de retenciones:</b> $ {{number_format($total_retenciones, 2)}}<br>
                     <b>Pago:</b>$ {{number_format($nomina->total_apagar, 2)}}
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

    @if(!$loop->last)
    <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>