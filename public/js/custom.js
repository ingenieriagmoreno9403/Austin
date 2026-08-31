
$('.barra').hide();
$('.guardarfull').addClass('btn-primary');

function disableButton(button){
 
  $('.barra').show();
  button.style.pointerEvents = "none";
  $('.guardarfull').addClass('btn-secondary');
  let urld = window.location.href;
  var url = $("#inputs").val();

      // Simulación de progreso
      var progress = 0;
      let isFetching = false;
      var interval = setInterval(function() {
          progress += 1;
          $('.progress-bar').css('width', progress + '%').attr('aria-valuenow', progress);
          
        //   fetch(url).then(response => {
        //     if (response.ok) {
        //       progress = 150;
        //       clearInterval(interval);
        //       $('.progress-bar').css('width', '100%').attr('aria-valuenow', 100);
        //       $('.progress-bar').css('background-color', 'green');
        //       swal("Exito en la operación","Acción completada","success", {buttons: false,timer: 5000});
        //       location.reload();
        //     }
        //   });
          
          fetch(url)
            
            .then(response => { 
                console.log(response)
            })
          
      }, 1000);
}
