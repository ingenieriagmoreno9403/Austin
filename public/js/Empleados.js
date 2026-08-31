$(document).ready(function() {
    $('#nss').hide();
    $('#excedente').hide();
    $('#Efectivo').hide();
    $('#salario_fijo').hide();
    $('#tipo_descuento_infonavit').hide();
    $('#factor_sua').hide();
    $('#descuento_quincenal').hide();
    $('#numero_credito_infonavit').hide();
    $('#vacaciones_notomadas').hide();



    $("table tbody tr").click(function() {

      //obtenemos el id del empleado
      var id = $(this).find("td:eq(2)").text();

      //obtenemos el nombre completo mediante la tabla
      var nombre = $(this).find("td:eq(3)").text();
      var nombre2 = $(this).find("td:eq(5)").text();
      var Apellido_p = $(this).find("td:eq(6)").text();
      var Apellido_m = $(this).find("td:eq(7)").text();
      var pago_imms =  $(this).find("td:eq(38)").text();
      var tipo_descuentoinfo = $(this).find("td:eq(31)").text();
      var factorsua = $(this).find("td:eq(33)").text();
      var empresa = $(this).find("td:eq(26)").text();
      var puesto_e = $(this).find("td:eq(23)").text();

      //obtenemos el salario del empleado
      var salario = $(this).find("td:eq(29)").text();
      var salarioMensual = $(this).find("td:eq(28)").text();
      //obtenemos la fecha de alta del empleado
      var fecha_ingreso_empelado = $(this).find("td:eq(24)").text();
    //obtenemos el imput que llenaremos mediante javascript


    var nombrecompleto=document.getElementById('nombre');
    var nombrecompleto=document.getElementById('empresa');
    var salariosoperaciones=document.getElementById('salario');
    var fecha_ingreso=document.getElementById('fecha_ingreso_empelado');
    var tipoinfo=document.getElementById('t_infonavit');
    var puesto_empleado=document.getElementById('t_puesto');


    //llenamos los imput mediante javascript





  $("#imms").val(parseFloat(pago_imms).toFixed(2));
  $("#infonavit").val(parseFloat(factorsua).toFixed(2));
  $("#nombre").val(nombre+' '+nombre2+' '+Apellido_p+' '+Apellido_m);
  $("#empresa").val(empresa);
  $("#salario").val(salario);
  $("#salarioMensual").val(salarioMensual);
  $("#fecha_ingreso_empelado").val(fecha_ingreso_empelado);
  $("#t_infonavit").val(tipo_descuentoinfo);
  $("#t_puesto").val(puesto_e);




  var fehcha_baja = document.getElementById("fecha_baja").value;

    //obtenemos los datos para calcular dias de aguinaldo del empleado
      var fechaIni = new Date(fecha_ingreso.innerText);
      var fechaFin = new Date();
      const añoinicio = fechaIni.getFullYear();
      const mesinicio = fechaIni.getMonth()+1;
      const diainicio = fechaIni.getDate();
      const añoactual = fechaFin.getFullYear();
      const diahoy = fechaFin.getDate();
      const mes = fechaFin.getMonth()+1;
      var diff = fechaFin - fechaIni;

      if (añoinicio == añoactual){

        fechaIni = new Date(añoinicio+'-'+mesinicio+'-'+diainicio);
      }
      else{
        fechaIni = new Date(añoactual+'-01-01');
      }
    diff = fechaFin - fechaIni;
    diferenciaDias = Math.floor(diff / (1000 * 60 * 60 * 24)+1);
      $("#dias_trabajados").val(diferenciaDias);



      if(diahoy >= 16 &&  diahoy <= 31){
        var fecha_ini_sueldopropo = new Date(añoactual+'-'+mes+'-16');
      }
      if(diahoy >= 1 && diahoy <= 15)
      {
        var fecha_ini_sueldopropo = new Date(añoactual+'-'+mes+'-01');
      }
    
      var diff = fechaFin - fecha_ini_sueldopropo;
      diferenciaDiass = Math.floor(diff / (1000 * 60 * 60 * 24));

    if(diferenciaDiass >diferenciaDias ){
      
      $("#dias_trabajadosa_deber").val(diferenciaDiass-1);
    }
    else{
      
      $("#dias_trabajadosa_deber").val(diferenciaDiass+1);
    }
    
      $("#ids").val(id);
      $("#idd").val(id);
      $("#emp").val(id);

    
  });

    
     
  });

    


