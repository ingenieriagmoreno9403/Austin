$('.validaPDF').on('change', function(){
    var ext = $( this ).val().split('.').pop();
    if ($( this ).val() != '') {
        if(ext == "pdf"){
        // alert("La extensión es: " + ext);
        // if($(this)[0].files[0].size > 1048576){
        //     swal("¡Archivo no valido!","El archivo no debe ser mayor a 1MB","warning", {buttons: false,timer: 3000});
        //     $('#modal-title').text('¡Precaución!');
        //     $('#modal-msg').html("Se solicita un archivo no mayor a 1MB. Por favor verifica.");
        //     $("#modal-gral").modal();           
        //     $(this).val('');
        // }else{
        //     $("#modal-gral").hide();
        // }
        }
        else
        {
        $( this ).val('');
        swal("¡Archivo no valido!","Solo se permiten archivos PDF","warning", {buttons: false,timer: 3000});
        }
    }
    });

$('.validaJPG').on('change', function(){
    var ext = $( this ).val().split('.').pop();
    if ($( this ).val() != '') {
        if(ext == "jpg"){
        // alert("La extensión es: " + ext);
        if($(this)[0].files[0].size > 1048576){
            swal("¡Archivo no valido!","El archivo no debe ser mayor a 1MB","warning", {buttons: false,timer: 3000});
            $('#modal-title').text('¡Precaución!');
            $('#modal-msg').html("Se solicita un archivo no mayor a 1MB. Por favor verifica.");
            $("#modal-gral").modal();           
            $(this).val('');
        }else{
            $("#modal-gral").hide();
        }
        }
        else
        {
        $( this ).val('');
        swal("¡Archivo no valido!","Solo se permiten archivos JPG","warning", {buttons: false,timer: 3000});
        }
    }
    });

$('.validaPNG').on('change', function(){
    var ext = $( this ).val().split('.').pop();
    if ($( this ).val() != '') {
        if(ext == "png"){
        // alert("La extensión es: " + ext);
        if($(this)[0].files[0].size > 1048576){
            swal("¡Archivo no valido!","El archivo no debe ser mayor a 1MB","warning", {buttons: false,timer: 3000});
            $('#modal-title').text('¡Precaución!');
            $('#modal-msg').html("Se solicita un archivo no mayor a 1MB. Por favor verifica.");
            $("#modal-gral").modal();           
            $(this).val('');
        }else{
            $("#modal-gral").hide();
        }
        }
        else
        {
        $( this ).val('');
        swal("¡Archivo no valido!","Solo se permiten archivos PNG","warning", {buttons: false,timer: 3000});
        }
    }
    });

    $('.validaFOTO').on('change', function(){
    var ext = $( this ).val().split('.').pop();
    if ($( this ).val() != '') {
        if(ext == "png" || ext == "jpg" || ext == "jpeg"){
        // alert("La extensión es: " + ext);
        if($(this)[0].files[0].size > 1048576){
            swal("¡Archivo no valido!","El archivo no debe ser mayor a 1MB","warning", {buttons: false,timer: 3000});
            $('#modal-title').text('¡Precaución!');
            $('#modal-msg').html("Se solicita un archivo no mayor a 1MB. Por favor verifica.");
            $("#modal-gral").modal();           
            $(this).val('');
        }else{
            $("#modal-gral").hide();
        }
        }
        else
        {
        $( this ).val('');
        swal("¡Archivo no valido!","Solo se permiten archivos PNG","warning", {buttons: false,timer: 3000});
        }
    }
    });