const notificacionesF1 = document.querySelector('#notificaciones');

notificacionesF1.innerHTML = `
<div class="pos__btnNotification">
    <div class="wrapper"> 
        <button type="button" class="btn border-0" data-bs-toggle="offcanvas" data-bs-target="#offcanvasRight" aria-controls="offcanvasRight">
            <h5 class="btnNoti"><i class="fa-solid fa-bell"></i>
                <span class="fs-9 position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    99+
                </span>
            </h5>
          </button>
    </div>
        
</div>


<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header">
      <div class="text-center">
          <h5 class="offcanvas-title fw-light" id="offcanvasRightLabel">Notificaciones</h5>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div class="shadow-sm p-3 mb-5 rounded-4 bg-lightsecond">
          <div class="fw-bold text-secondary">
              Nuevo mensaje
          </div>
          <div class="fw-light mt-4">Contenido de mensaje</div>
          <div class="fw-bold fs-9 mt-2">Credito #000</div>
          <div class="text-end mt-4">
              <a href="#" class="fw-bold text-primary link_none">ver</a>
          </div>
      </div>
    </div>
</div>


`
