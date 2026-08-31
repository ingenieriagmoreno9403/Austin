const btnBack2 = document.querySelector('#btnBack');

btnBack2.innerHTML = `
<div class="pos__btnBack1">
    <div class="wrapper"> 
      <h5 class="btnBack1" onClick="history.go(-1);"><i class="fas fa-solid fa-arrow-left"></i></h5>
    </div>
</div>

`