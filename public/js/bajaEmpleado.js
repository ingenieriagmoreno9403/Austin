$(document).ready(function() {
    $('#nss').hide();
    $('#excedente').hide();
    $('#Efectivo').hide();
    $('#sueldo_fiscal').hide();
    $('#tipo_descuento_infonavit').hide();
    $('#factor_sua').hide();
    $('#descuento_quincenal').hide();
    $('#numero_credito_infonavit').hide();
    $('#vacaciones_notomadas').hide();



    $("table tbody tr").click(function() {

      //obtenemos el id del empleado
      var id = $(this).find("td:eq(1)").text();

      //obtenemos el nombre completo mediante la tabla
      var nombre = $(this).find("td:eq(3)").text();
      var nombre2 = $(this).find("td:eq(4)").text();
      var Apellido_p = $(this).find("td:eq(5)").text();
      var Apellido_m = $(this).find("td:eq(6)").text();
      var pago_imms =  $(this).find("td:eq(42)").text();
      var tipo_descuentoinfo = $(this).find("td:eq(31)").text();
      var factorsua = $(this).find("td:eq(33)").text();
      var empresa = $(this).find("td:eq(25)").text();
      var puesto_e = $(this).find("td:eq(23)").text();

      //obtenemos el salario del empleado
      var salario = $(this).find("td:eq(28)").text();
      var salarioMensual = $(this).find("td:eq(27)").text();
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

      document.getElementById("emple").value = val(id);
    
  });

    var table = $('#tblempleados').DataTable( {
          "dom": 'B<"float-left"l><"float-right"f>t<"float-left"i><"float-right"p><"clearfix">',
          responsive: true,
          scrollY: 500,
          scrollX: true,
          "language": {
              "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
          },
          
      } );
      new $.fn.dataTable.FixedHeader( table );
  });



  $("#cmbempresas").change(function(){
    var id = $(this).val(); 
    var empresa = $(this).find('option:selected').text(); 
    if(empresa == "Sin imss")
    {
        // $('#nss').hide();
        $('#excedente').hide();
        $('#Efectivo').show();
        $('#sueldo_fiscal').hide();
    
    }
    else
    {
        // $('#nss').hide();
        $('#excedente').show();
        $('#Efectivo').hide();
        $('#sueldo_fiscal').show();
    }
      });


function terminos_cambio(checkbox){
    //Si está marcada ejecuta la condición verdadera.
    if(checkbox.checked){
      $('#vacaciones_notomadas').show();
    }

    else{
      $('#vacaciones_notomadas').hide();
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
 
    var diff = 0;
    var Totaldiastrabajadosvacaciones = 0;
    var totaldiastrabajadostotales=0;

    //obtenemos las fechas con las que haremos los calculos
    var fehcha_baja = document.getElementById("fecha_baja").value;
    var fechainicio = document.getElementById("fecha_ingreso_empelado").value;


    var fechabaj = new Date(fehcha_baja);
    const añoactual = fechabaj.getFullYear();
    const mesactual = fechabaj.getMonth()+1;
    const diaactual = fechabaj.getDate();
    var fechaingreso = new Date(fechainicio);
    const añoingreso = fechaingreso.getFullYear();
    const mesingreso = fechaingreso.getMonth()+1;
    const diaingreso = fechaingreso.getDate();

    

    //esto para sacar prima vacacional
    if(añoactual == añoingreso){
      //se cuentan los dias en el mismo año
       diff = fechabajo - fechaIni;
       Totaldiastrabajadosvacaciones = Math.floor(diff / (1000 * 60 * 60 * 24));
    }
    else{
      año=añoactual-1;
      fechaIni = new Date(año+'-'+mesingreso+'-'+diaingreso);
      fechadebaja = new Date(fehcha_baja);
      diff = fechadebaja - fechaIni;
      Totaldiastrabajadosvacaciones = Math.floor(diff / (1000 * 60 * 60 * 24));
  
    }
     
    var fechaIni = new Date(fechainicio);
    var fechabajo = new Date(fehcha_baja);
    var diffe = fechabajo - fechaIni;
    var diasvacaciones = 0;
    Totaldiastrabajados = Math.floor(diffe / (1000 * 60 * 60 * 24));
    if(Totaldiastrabajados <= 729)
    {  
      diasdevacaciones = 12;
    }
    if(Totaldiastrabajados  >=730 &&  Totaldiastrabajados <= 1094)
    {  
      diasdevacaciones = 14;
    }

    if(Totaldiastrabajados  >=1095 &&  Totaldiastrabajados <= 1459)
    {  
      diasdevacaciones = 16;
    }

    if(Totaldiastrabajados  >=1460 &&  Totaldiastrabajados <= 1825)
    {  
      diasdevacaciones = 18;
    }

    
    if(Totaldiastrabajados  >=1826 &&  Totaldiastrabajados <= 2189)
    {  
      diasdevacaciones = 20;
    }

    if(Totaldiastrabajados  >=2190 &&  Totaldiastrabajados <=2554 )
    {  
      diasdevacaciones = 22;
    }

    if(Totaldiastrabajados  >=2555 &&  Totaldiastrabajados <=2919 )
    {  
      diasdevacaciones = 24;
    }

    if(Totaldiastrabajados  >=2920 &&  Totaldiastrabajados <=3284 )
    {  
      diasdevacaciones = 26;
    }

    if(Totaldiastrabajados  >=3284 &&  Totaldiastrabajados <=3285 )
    {  
      diasdevacaciones = 28;
    }

    if(Totaldiastrabajados  >=3285 &&  Totaldiastrabajados <=3649 )
    {  
      diasdevacaciones = 30;
    }

    if(Totaldiastrabajados  >=3650 &&  Totaldiastrabajados <=5000 )
    {  
      diasdevacaciones = 32;
    }
  
    var salarioc =$("#salario").val();
    var salarioM= $("#salarioMensual").val();
    var diagratificacion = document.getElementById("diasgratificacion").value;
    var diastrabajados = $("#dias_trabajados").val();
    var diastrabajadosdeber = $("#dias_trabajadosa_deber").val();
    var vacacionesnotomadas = $("#vacaciones_notomadas").val();
    var deduccionimms =  $("#imms").val();
    var deduccioninfonavit =  $("#infonavit").val();
    var deducciontransporte =  document.getElementById("transporte").value;
    var otrasper =  document.getElementById("otras").value;
    // alert(otrasper);
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
    if(tipopago == 'N/A')
    {
        saldoadeberinfonavit=0;
    }
  
 
    var aguinaldo = 15/365*diastrabajados*salarioc;
    var gratificacion = diagratificacion*salarioc;
    var sueldopropo = salarioc*diastrabajadosdeber;
    var diasvacaciones = (((diasdevacaciones/365)*Totaldiastrabajadosvacaciones)*salarioc);
    var primavacacional = ((diasvacaciones)*.25);
    var totaldiasnotomados = parseFloat(vacacionesnotomadas*salarioc).toFixed();
    var totalpercepciones = aguinaldo+gratificacion+sueldopropo+primavacacional;
    var totalprecepcionesfinal = (otrasper+totaldiasnotomados-1+totalpercepciones+1);
    //falta agregar las demas deducciones

    
    var total = (((((((otrasper+totaldiasnotomados-1+aguinaldo+gratificacion+sueldopropo+primavacacional+1)-parseFloat(deduccionimms).toFixed(2))-parseFloat(saldoadeberinfonavit).toFixed(2)-parseFloat(deducciontransporte).toFixed(2))-parseFloat(deduccionorestamo).toFixed(2))-parseFloat(deduccionotroa).toFixed(2))));
    let totaldeduccion =total-(aguinaldo+gratificacion+sueldopropo+primavacacional);
  

  // calculamos el aguinaldo del empleado a deber segun la fecha de ingreso
    $("#Aguinaldo_poporcional").val(parseFloat(aguinaldo).toFixed(2));
    $("#total_p").val(parseFloat(totalprecepcionesfinal).toFixed(2));
    $("#total_d").val(parseFloat(totaldeduccion).toFixed(2).substr(1,15));
    $("#gratificacion").val(parseFloat(gratificacion).toFixed(2));
    $("#sueldo_poporcional").val(parseFloat(sueldopropo).toFixed(2));
    $("#vacaciones_poporcionales").val(parseFloat(primavacacional).toFixed(2));
    $("#infonavit").val(parseFloat(saldoadeberinfonavit).toFixed(2));
    $("#total_entregar").val(parseFloat(total).toFixed(2));
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