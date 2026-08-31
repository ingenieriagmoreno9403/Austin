$(document).ready(function() { 
    $('#messageContent').hide();
});

var clic = 1;

    function message(){ 
        if(clic==1){
            
            document.getElementById("messageContent").style.height = "0px"; 
            $('#messageContent').show(); 
            clic = clic + 1;
        
        } 
        else{
            document.getElementById("messageContent").style.height = "100%";
            $('#messageContent').hide(); 
              
            clic = 1;
        }   
    }