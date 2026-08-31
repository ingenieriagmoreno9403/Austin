$('input[type="file"]').on('change', function(){
    var ext = $( this ).val().split('.').pop();
    if ($( this ).val() != '') {
        if(ext == "xlsx"){
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
        swal("¡Archivo no valido!","Solo se permiten archivos Excel con extensión .XLSX","warning", {buttons: false,timer: 3000});
        }
    }
    });