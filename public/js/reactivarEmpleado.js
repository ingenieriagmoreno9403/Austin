$(document).ready(function() {
    $('#excedente').hide();
    $('#Efectivo').hide();
    $('#salario_fijo').hide();
    $('#tipo_descuento_infonavit').hide();
    $('#factor_sua').hide();
    $('#descuento_quincenal').hide();
    $('#numero_credito_infonavit').hide();
    $('#vacaciones_notomadas').hide();

  });



  $("#cmbempresas").change(function(){
      var id = $(this).val();     
      var empresa = $(this).find('option:selected').text(); 
      var tipo = empresa.substring(0,8);
      $('#salario_fijo').show();
      
      if(tipo == "Efectivo")
      {
          $('#Efectivo').show();
          $('#excedente').hide();
          $('#salario_fijo').hide();
      }
      else
      {
          $('#Efectivo').hide();
          $('#excedente').show();
          $('#salario_fijo').show();
      }
    });


function terminos_cambio(checkbox){
//Si está marcada ejecuta la condición verdadera.
if(checkbox.checked){
  $('#vacaciones_poporcionales').show();
  // $('#dias_vacaciones').show();
  $('#vacaciones_notomadas').show();


}
//Si se ha desmarcado se ejecuta el siguiente mensaje.
else{
  $('#vacaciones_poporcionales').hide();
  // $('#dias_vacaciones').hide();
  $("#vacaciones_poporcionales").val(0);
  // $("#dias_vacaciones").val(0);
  $('#vacaciones_notomadas').hide(0);
}
}

function chekinfonavit(checkbox){
//Si está marcada ejecuta la condición verdadera.
if(checkbox.checked){
  $('#tipo_descuento_infonavit').show();
  $('#factor_sua').show();
  $('#descuento_quincenal').show();
  $('#numero_credito_infonavit').show();

}
//Si se ha desmarcado se ejecuta el siguiente mensaje.
else{
  $('#tipo_descuento_infonavit').hide();
  $('#factor_sua').hide();
  $('#descuento_quincenal').hide();
  $('#numero_credito_infonavit').hide();
  $("#descuento_quincenal").val(0);
  $("#factor_sua").val(0);

}
}


function operaciones()
{

var salarioc =$("#salario").val();
var salarioM= $("#salarioMensual").val();
var diagratificacion = document.getElementById("diasgratificacion").value;
var diastrabajados = $("#dias_trabajados").val();
var diastrabajadosdeber = $("#dias_trabajadosa_deber").val();
// var diasvacaciones = $("#dias_vacaciones").val();
var vacacionesnotomadas = $("#vacaciones_notomadas").val();
var deduccionimms =  $("#imms").val();
var deduccioninfonavit =  $("#infonavit").val();
var deducciontransporte =  document.getElementById("transporte").value;
var deduccionorestamo =  $("#prestamo").val();
var deduccionotroa =  $("#otras").val();

//en este apartado vamos a calcular lo que debe de infonavit segun su credito

var tipopago =  $("#t_infonavit").val();
var saldoadeberinfonavit=0;

pagosuainfonavit =  $("#infonavit").val();


if(pagosuainfonavit==parseFloat(0).toFixed(2));
{
  saldoadeberinfonavit=0;
}
if(tipopago == 'CF' && pagosuainfonavit  != parseFloat(0).toFixed(2))
{
  pagosuainfonavit =  $("#infonavit").val();
  saldoadeberinfonavit = (pagosuainfonavit*2)/61*(diastrabajadosdeber)+15;
}
if(tipopago == 'VSM' && pagosuainfonavit  != parseFloat(0).toFixed(2))
{
  pagosuainfonavit =  $("#infonavit").val();
  saldoadeberinfonavit = (pagosuainfonavit*103.74*2)/61*(diastrabajadosdeber)+15;
}




//sumamos el total de dinero a otorgar al trabajador

}

function limpiar()
{
$("#Aguinaldo_poporcional").val(parseFloat(0).toFixed(2));
$("#total_p").val(parseFloat(0).toFixed(2));
$("#total_d").val(parseFloat(0).toFixed(2));
$("#gratificacion").val(parseFloat(0).toFixed(2));
$("#sueldo_poporcional").val(parseFloat(0).toFixed(2));
$("#vacaciones_poporcionales").val(parseFloat(0).toFixed(2));

}