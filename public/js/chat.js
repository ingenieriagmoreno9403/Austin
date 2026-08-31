const mensajechat = document.querySelector('#chat');

mensajechat.innerHTML = `
<div class="chatButtonPosition">
    <button class="btn border-0 p-2 chatButton" onclick="chat()"><h3><i class="fa-solid fa-comment"></i></h3></button>
</div>


<div id="chatElement">
    <div class="card border-0 bg-chat">
 
        <div class="card-header border-0 bg-headerChat">
            <div class="row text-start">
                <div class="col-md-2 col-2">
                    <img class="pChat zoom" src="../../Images/6.png" />
                </div>
                <div class="col-md-4 col-4 fw-light mt-1">mgarcia<br/> suc Torreón</div>
                <div class="col-md-4 col-4 text-end fw-bold mt-1">credito #000</div>
                <div class="col-md-2 col-2 text-end mt-1"><div class="cursor" onclick="chat()" ><i class="fa-solid fa-xmark"></i></div></div>
            </div>
        </div>


        <div class="card-body">
            <div class="scrollChat">
                <div class="mb-2">
                    <div class="m1">
                       Lorem ipsum, dolor sit amet consectetur adipisicing elit. Consequatur reprehenderit ab perspiciatis necessitatibus, laboriosam quas culpa, architecto explicabo facere nisi placeat.
                    </div> 
                    <div class="infoM1">
                       <p class="text-secondary"><i class="fa-solid fa-check"></i> Enviado 00:00:00 pm  10/10/2000</p> 
                    </div>
                </div>

                <div class="mb-2">
                    <div class="m2">
                        Lorem ipsum, dolor sit amet consectetur adipisicing elit. Consequatur reprehenderit ab perspiciatis necessitatibus.
                    </div>
                    <div class="infoM2">
                        <p class="text-secondary"><i class="fa-solid fa-check"></i> Enviado 00:00:00 pm  10/10/2000</p> 
                    </div>
                </div>
            </div>

            <div class="responseArea">
                <div class="input-group mb-2 ">
                    <input type="text" class="form-control responseInput border-0">
                    <button class="input-group-text btn-blak responseBtn"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </div>


    </div>
</div>

`

$(document).ready(function() { 

    $('#chatElement').hide();
});


var close = 1;


    function chat(){ 
        if(close==1){
            document.getElementById("chatElement").style.height = "0px"; 
            $('#chatElement').show();  
            close = close + 1;
        
        } 
        else{
            document.getElementById("chatElement").style.height = "100%";
            $('#chatElement').hide(); 
            close = 1;
        }   
    }