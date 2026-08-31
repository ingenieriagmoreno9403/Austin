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
    
@foreach($varlistanomina as $nomina)

   @if($nomina->total_nomina_fiscal > 0)
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
                     RFC: {{$nomina->rfc}}<br>
                     IMSS: {{$nomina->nss}}
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
                     {{$nomina->fecha_fin}}
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
                     <p> Nombre: {{$nomina->NOMBRE}}<br>
                        Puesto: {{$nomina->puesto}}<br>
                        {{-- Depto.:<br> --}}
                        RFC: {{$nomina->rfc}}<br>
                        CURP: {{$nomina->curp}}<br>
                        Domicilio fiscal: {{$nomina->domicilio_fiscal}}<br>
                        Regimen fiscal:  605/Sueldos y Salarios e Ingresos Asimilados a Salarios
                     </p>
                  </div>
               </td>

               <td>
                  <div>
                    <div class="th text-center">Seguridad Social</div>
                    <p> Rgistro: {{$nomina->nss}}<br>
                     Tipo de Salario: Fijo<br>
                     Salario Diario: {{$nomina->salario_diario}}<br>
                     Jornada: 8.00 horas<br>
                     Fecha de ingreso: {{$nomina->fecha_ingreso_imss}}
                     <br>
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
                 <p> Sueldo Normal <br>
                   Subsidio para el empleo<br><br><br></p>
               </td>
               <td>
                  <p>{{$nomina->total_sueldo}}<br>
                     0.00<br><br><br></p>
               </td>
               <td>
                  @php($dias_lab = intval($nomina->dias_laborados))
                  <p>{{$dias_lab}} días<br><br><br><br></p>
               </td>
               @php($prestamo = 0)
               <td>
                  <p>
                     ISR<br>
                     Seguro Social<br>
                     INFONAVIT<br>
                     Deudores Fiscal<br>
                    @if($nomina->deudores_no_fiscal > 0 && $nomina->total_apagar_excedente <= 0) Deudores No Fiscal<br> @endif
                  </p>
               </td>
               <td>
                  <p>{{$nomina->pago_isr}}<br>
                     {{$nomina->pago_imss}}<br>
                     {{$nomina->pago_infonavit}}<br>
                     {{$nomina->deudores_fiscal}}<br>
                     @if($nomina->deudores_no_fiscal > 0 && $nomina->total_apagar_excedente <= 0) {{$nomina->deudores_no_fiscal}}<br> @php($prestamo = $nomina->deudores_no_fiscal)@endif
                  </p>
               </td>
            </tr>
         </tbody>
      </table>

      <br>

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
               <td>{{$nomina->pago_subsidio}}</td>
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
                  <p>
                     ___________________________________________________________________<br>
                     Firma del empleado
                  </p>
               </td>

               <td>
                  <p>
                     @php($total_percepciones = $nomina->sueldo_fiscal+$nomina->pago_subsidio)
                  <b>Total de percepciones:</b> {{$total_percepciones}}<br>
                     @php($total_retenciones = $nomina->pago_isr + $nomina->pago_imss + $nomina->pago_infonavit + $nomina->deudores_fiscal + $prestamo)
                  <b>Total de retenciones:</b> {{$total_retenciones}}<br>
                     {{-- @php($total = $total_percepciones - $total_retenciones) --}}
                  <b>Pago:</b>{{$nomina->total_nomina_fiscal}}</p>
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

    @if($nomina->total_apagar_excedente > 0) 
    <!--Pagina Excedente-->
    <div class="border" style="padding: 20px;">
      <div>
         <div style="width: 30%">
            <div class="th border-rad">PERIODO</div>
            <div class="rounded_stwift"> DEL {{$nomina->fecha_inicio}} AL {{$nomina->fecha_fin}}</div>
         </div>
      </div>

      <br>

      <table>
         <thead>
            <tr>
               <th class="border-rad">NOMBRE DE EMPLEADO</th>
               <th class="bg-light">&nbsp;&nbsp;&nbsp;</th>
               <th class="border-rad">SUCURSAL</th>
            </tr>
        </thead>
        <tbody>
         <tr>
            <td>
               <div class="rounded_stwift"><b>{{$nomina->id_empleado}} - </b>{{$nomina->NOMBRE}}</div>
            </td>
            <td>&nbsp;&nbsp;&nbsp;</td>
            <td>
               <div class="rounded_stwift">{{$nomina->sucursal}}</div>
            </td>
         </tr>
        </tbody>
      </table>

      <br>

      <table>
         <thead>
            <tr>
               <th class="fw-bold border-rad" colspan="2">PERCEPCIONES</th>
               <th class="bg-light">&nbsp;&nbsp;&nbsp;</th>
               <th class="fw-bold border-rad" colspan="2">DEDUCCIONES</th>
            </tr>
         </thead>
         <tbody>
            <tr>
               <td class="">
                     SUELDO<br>
                     P.VACACIONAL<br>
                     BONO<br>
                     TRANSPORTE<br>
                     DIAS PENDIENTES <br>
                     OTROS<br> 
                     <b>TOTAL</b>
               </td>
               <td class="shadowTd border-rad2">
                  {{$nomina->sueldo_excedente}}<br>
                  {{-- {{$nomina->pago_prima_vacacional+ $nomina->pago_prima_vacacional_exce + $nomina->pago_prima_vacacional_efec}}<br> --}}
                  {{$nomina->bono}}<br>
                  {{$nomina->transporte}}<br>
                  {{$nomina->dias_pendiente}}<br>
                  {{$nomina->otros}}<br> 
                  @php($total_percepcionesExcedente = $nomina->sueldo_excedente+
                  // $nomina->pago_prima_vacacional+ $nomina->pago_prima_vacacional_exce + $nomina->pago_prima_vacacional_efec +$nomina->bono+$nomina->transporte
                  
                  +$nomina->dias_pendiente+$nomina->otros)
                  <b>{{$total_percepcionesExcedente}}</b>
               </td>
               <td>&nbsp;&nbsp;&nbsp;</td>
               <td class="">
                  PRESTAMO<br>
                  <br> 
                  <br>
                  <br>
                  <br> 
                  <br><br>
                  <b>TOTAL</b>
               </td>
               <td class="shadowTd border-rad2">
                  {{$nomina->deudores_no_fiscal}}<br>
                  <br> 
                  <br>
                  <br>
                  <br> 
                  <br> @php($total_deduccionesExcedente = $nomina->deudores_no_fiscal)
                  <b>{{$total_deduccionesExcedente}}</b>
               </td>
            </tr>
         </tbody>
      </table>

    
      <br>
      <div class="text-end">
         <p class="fw-bold">TOTAL A PAGAR {{$nomina->total_apagar_excedente}}</p>
      </div>
    
      <br>
      <br>
      <br>
      <br>

      <div class="text-center">
         ____________________________________________________________<br>
         <b>FIRMA DE CONFORMIDAD</b>
      </div>
    </div>

    <br><br>
      <div class="punteado"></div>
    <br><br>

    <div class="border" style="padding: 20px;">
      <div>
         <div style="width: 30%">
            <div class="th border-rad">PERIODO</div>
            <div class="rounded_stwift"> DEL {{$nomina->fecha_inicio}} AL {{$nomina->fecha_fin}}</div>
         </div>
      </div>

      <br>

      <table>
         <thead>
            <tr>
               <th class="border-rad">NOMBRE DE EMPLEADO</th>
               <th class="bg-light">&nbsp;&nbsp;&nbsp;</th>
               <th class="border-rad">SUCURSAL</th>
            </tr>
        </thead>
        <tbody>
         <tr>
            <td>
               <div class="rounded_stwift"><b>{{$nomina->id_empleado}} - </b>{{$nomina->NOMBRE}}</div>
            </td>
            <td>&nbsp;&nbsp;&nbsp;</td>
            <td>
               <div class="rounded_stwift">{{$nomina->sucursal}}</div>
            </td>
         </tr>
        </tbody>
      </table>

      <br>

      <table>
         <thead>
            <tr>
               <th class="fw-bold border-rad" colspan="2">PERCEPCIONES</th>
               <th class="bg-light">&nbsp;&nbsp;&nbsp;</th>
               <th class="fw-bold border-rad" colspan="2">DEDUCCIONES</th>
            </tr>
         </thead>
         <tbody>
            <tr>
               <td class="">
                     SUELDO<br>
                     P.VACACIONAL<br>
                     BONO<br>
                     TRANSPORTE<br>
                     DIAS PENDIENTES <br>
                     OTROS<br> 
                     <b>TOTAL</b>
               </td>
               <td class="shadowTd border-rad2">
                  {{$nomina->sueldo_excedente}}<br>
                  {{$nomina->pago_prima_vacacional}}<br>
                  {{$nomina->bono}}<br>
                  {{$nomina->transporte}}<br>
                  {{$nomina->dias_pendiente}}<br>
                  {{$nomina->otros}}<br> 
                  @php($total_percepcionesExcedente = $nomina->sueldo_excedente+
                  $nomina->pago_prima_vacacional+$nomina->bono+
                  $nomina->transporte+$nomina->dias_pendiente+$nomina->otros)
                  <b>{{$total_percepcionesExcedente}}</b>
               </td>
               <td>&nbsp;&nbsp;&nbsp;</td>
               <td class="">
                  PRESTAMO<br>
                  <br> 
                  <br>
                  <br>
                  <br> 
                  <br> <br>
                  <b>TOTAL</b>
               </td>
               <td class="shadowTd border-rad2">
                  {{$nomina->deudores_no_fiscal}}<br>
                  <br> 
                  <br>
                  <br>
                  <br> 
                  <br> @php($total_deduccionesExcedente = $nomina->deudores_no_fiscal)
                  <b>{{$total_deduccionesExcedente}}</b>
               </td>
            </tr>
         </tbody>
      </table>

    
      <br>
      <div class="text-end">
         <p class="fw-bold">TOTAL A PAGAR {{$nomina->total_apagar_excedente}}</p>
      </div>
    
      <br>
      <br>
      <br>
      <br>

      <div class="text-center">
         ____________________________________________________________<br>
         <b>FIRMA DE CONFORMIDAD</b>
      </div>
    </div>

    <div class="page-break"></div>
    @endif

    @if($nomina->total_efectivo > 0) 
    <!--Pagina Excedente-->
    <div class="border" style="padding: 20px;">
      <div>
         <div style="width: 30%">
            <div class="th border-rad">PERIODO</div>
            <div class="rounded_stwift"> DEL {{$nomina->fecha_inicio}} AL {{$nomina->fecha_fin}}</div>
         </div>
      </div>

      <br>

      <table>
         <thead>
            <tr>
               <th class="border-rad">NOMBRE DE EMPLEADO</th>
               <th class="bg-light">&nbsp;&nbsp;&nbsp;</th>
               <th class="border-rad">SUCURSAL</th>
            </tr>
        </thead>
        <tbody>
         <tr>
            <td>
               <div class="rounded_stwift"><b>{{$nomina->id_empleado}} - </b>{{$nomina->NOMBRE}}</div>
            </td>
            <td>&nbsp;&nbsp;&nbsp;</td>
            <td>
               <div class="rounded_stwift">{{$nomina->sucursal}}</div>
            </td>
         </tr>
        </tbody>
      </table>

      <br>

      <table>
         <thead>
            <tr>
               <th class="fw-bold border-rad" colspan="2">PERCEPCIONES</th>
               <th class="bg-light">&nbsp;&nbsp;&nbsp;</th>
               <th class="fw-bold border-rad" colspan="2">DEDUCCIONES</th>
            </tr>
         </thead>
         <tbody>
            <tr>
               <td class="">
                     SUELDO<br>
                     P.VACACIONAL<br>
                     BONO<br>
                     TRANSPORTE<br>
                     DIAS PENDIENTES <br>
                     OTROS <br>
                     <b>TOTAL</b>
               </td>
               <td class="shadowTd border-rad2">
                  {{$nomina->sueldo_efectivo}}<br>
                  {{$nomina->pago_prima_vacacional}}<br>
                  {{$nomina->bono}}<br>
                  {{$nomina->transporte}}<br>
                  {{$nomina->dias_pendiente}}<br>
                  {{$nomina->otros}}<br>
                  @php($total_percepcionesExcedente = $nomina->sueldo_efectivo+
                  $nomina->pago_prima_vacacional+$nomina->bono+
                  $nomina->transporte+$nomina->dias_pendiente)
                  <b>{{$total_percepcionesExcedente}}</b>
               </td>
               <td>&nbsp;&nbsp;&nbsp;</td>
               <td class="">
                  PRESTAMO <br>
                  INFONAVIT<br> 
                  <br>
                  <br>
                  <br> 
                  <br><br> 
                  <b>TOTAL</b>
               </td>
               <td class="shadowTd border-rad2">
                  {{$nomina->deudores_no_fiscal}}<br>
                  {{$nomina->pago_infonavit}}<br> 
                  <br>
                  <br>
                  <br> 
                  <br> @php($total_deduccionesExcedente = $nomina->deudores_no_fiscal + $nomina->pago_infonavit)
                  <b>{{$total_deduccionesExcedente}}</b>
               </td>
            </tr>
         </tbody>
      </table>

    
      <br>
      <div class="text-end">
         <p class="fw-bold">TOTAL A PAGAR {{$nomina->total_efectivo}}</p>
      </div>
    
      <br>
      <br>
      <br>
      <br>

      <div class="text-center">
         ____________________________________________________________<br>
         <b>FIRMA DE CONFORMIDAD</b>
      </div>
    </div>

    <br><br>
      <div class="punteado"></div>
    <br><br>

    <div class="border" style="padding: 20px;">
      <div>
         <div style="width: 30%">
            <div class="th border-rad">PERIODO</div>
            <div class="rounded_stwift"> DEL {{$nomina->fecha_inicio}} AL {{$nomina->fecha_fin}}</div>
         </div>
      </div>

      <br>

      <table>
         <thead>
            <tr>
               <th class="border-rad">NOMBRE DE EMPLEADO</th>
               <th class="bg-light">&nbsp;&nbsp;&nbsp;</th>
               <th class="border-rad">SUCURSAL</th>
            </tr>
        </thead>
        <tbody>
         <tr>
            <td>
               <div class="rounded_stwift"><b>{{$nomina->id_empleado}} - </b>{{$nomina->NOMBRE}}</div>
            </td>
            <td>&nbsp;&nbsp;&nbsp;</td>
            <td>
               <div class="rounded_stwift">{{$nomina->sucursal}}</div>
            </td>
         </tr>
        </tbody>
      </table>

      <br>

      <table>
         <thead>
            <tr>
               <th class="fw-bold border-rad" colspan="2">PERCEPCIONES</th>
               <th class="bg-light">&nbsp;&nbsp;&nbsp;</th>
               <th class="fw-bold border-rad" colspan="2">DEDUCCIONES</th>
            </tr>
         </thead>
         <tbody>
            <tr>
               <td class="">
                     SUELDO<br>
                     P.VACACIONAL<br>
                     BONO<br>
                     TRANSPORTE<br>
                     DIAS PENDIENTES <br>
                     OTROS <br>
                     <b>TOTAL</b>
               </td>
               <td class="shadowTd border-rad2">
                  {{$nomina->sueldo_efectivo}}<br>
                  {{$nomina->pago_prima_vacacional}}<br>
                  {{$nomina->bono}}<br>
                  {{$nomina->transporte}}<br>
                  {{$nomina->dias_pendiente}}<br>
                  {{$nomina->otros}}<br>
                  @php($total_percepcionesExcedente = $nomina->sueldo_efectivo+
                  $nomina->pago_prima_vacacional+$nomina->bono+
                  $nomina->transporte+$nomina->dias_pendiente)
                  <b>{{$total_percepcionesExcedente}}</b>
               </td>
               <td>&nbsp;&nbsp;&nbsp;</td>
               <td class="">
                  PRESTAMO <br>
                  INFONAVIT<br> 
                  <br>
                  <br>
                  <br> 
                  <br><br> 
                  <b>TOTAL</b>
               </td>
               <td class="shadowTd border-rad2">
                  {{$nomina->deudores_no_fiscal}}<br>
                  {{$nomina->pago_infonavit}}<br> 
                  <br>
                  <br>
                  <br> 
                  <br> @php($total_deduccionesExcedente = $nomina->deudores_no_fiscal + $nomina->pago_infonavit)
                  <b>{{$total_deduccionesExcedente}}</b>
               </td>
            </tr>
         </tbody>
      </table>

    
      <br>
      <div class="text-end">
         <p class="fw-bold">TOTAL A PAGAR {{$nomina->total_efectivo}}</p>
      </div>
    
      <br>
      <br>
      <br>
      <br>

      <div class="text-center">
         ____________________________________________________________<br>
         <b>FIRMA DE CONFORMIDAD</b>
      </div>
    </div>
    

    <div class="page-break"></div>
    @endif

@endforeach
</body>
</html>