@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('errorServ'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡Problema al insertar servicio!", text: "Asegurese de capturar toda la información requerida si capturo un cliente desde el incio es probable que se insertará, puede cambiar la opción a cliente existente."});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('errorCli'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "error",title: "¡Problema al insertar cliente!", text: "Asegurese de capturar toda la información requerida"});';
            echo '</script>'; 
    @endphp
@endif

<div class="container-fluid format_page">
    <!-- Encabezado -->
     @foreach($servicio_encxid as $key)
        <form action="/Servicios/Editar/InsertarDatos/{{$tipo}}/{{$key->id}}" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
        @csrf
        
            <div class="row">
                <div class="col-lg-6 col-12 start-center">
                    <h2 class="mt-1 animate_animated animate_backInLeft">Creación de {{$tipo}}</h2>
                    <a href="/Servicios" class="btn btn-primary fs-8"><i class="fa-solid fa-house"></i> Inicio</a> 
                </div>

                <div class="col-lg-6 col-12 start-center">
                    <div class="row">
                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" value="{{$key->nombre}}" name="nombre" placeholder="nombre" maxlength="20" required>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>

                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Folio <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" value="{{$key->folio}}" name="folio" placeholder="83901" maxlength="10" required>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    
                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Fecha Recepción <span class="text-danger">*</span></label>
                            <input class="form-control" type="date" value="{{$key->fecha_inicio}}" name="fecha_ini" required>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>

                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Fecha Entrega <span class="text-danger">*</span></label>
                            <input class="form-control" type="date" value="{{$key->fecha_limite}}" name="fecha_fin" id="fecha_fin" required>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- MENU --}}
                <div class="col-md-3  p-3 start-center">
                    
                    <a href="/Servicios/Editar/Datos/{{$tipo}}/{{$id}}" class="row pointer">
                        <div class="col-md-2 col-2  d-none d-md-block">
                            <div class="circleBlue"> 1</div>
                        </div>

                        <div class="col-md-10 col-12 p-3 pb-0">
                            <h6 class="text-dark">Datos Informativos</h6>
                        </div>
                    </a>

                    <div class="lineGrey d-none d-md-block"></div>

                    <a href="/Servicios/Editar/Materiales/{{$tipo}}/{{$id}}" class="row pointer">
                        <div class="col-md-2 col-2 d-none d-md-block">
                            <div class="circleGrey"> 2</div>
                        </div>

                        <div class="col-md-10 col-10 d-none d-md-block p-3 pt-1 pb-0">
                            <h6 class="m-0 p-0 text-dark">Materiales y Suministro</h6>
                            <span class="fs-8 text-secondary m-0 p-0">Plantilla de Cotiozaciones</span>
                        </div>
                    </a>

                    @if($tipo == "Integración")
                    <div class="lineGrey d-none d-md-block"></div>

                    <a href="/Servicios/Editar/Costos/{{$tipo}}/{{$id}}" class="row pointer">
                        <div class="col-md-2 col-2 d-none d-md-block">
                            <div class="circleGrey"> 3</div>
                        </div>

                        <div class="col-md-10 col-10 d-none d-md-block  p-3 pb-0">
                            <h6 class="text-dark">Costos Calculados</h6>
                        </div>
                    </a>
                    @endif
                </div>

                {{-- CUERPO --}}
                <div class="col-md-9">
                    <div class="row p-3">
                        <div class="border bg-light p-3 rounded-1">
                            {{-- Vendedor --}}
                            <div>
                                <h6><b class="text-orange fs-5">1.</b> Vendedor</h6>
                                <div class="row">
                                    @livewire('buscador-empleados-editar', ['empleadoId' => $key->id_vendedor])
                                </div>
                            </div>

                              {{-- LIMITE PAGO --}}

                            <div class="row mt-2 mb-2">
                                <div class="col-md-4 col-12 form-outline inputform me-3">
                                    <label class="form-label">Fecha Tentativa de Pago</label>
                                    <div class="input-group">
                                        <input class="form-control" type="date" name="fecha_tentativa_pago" id="fecha_tentativa_pago" 
                                            value="{{ $key->fecha_tentativa_pago ? \Carbon\Carbon::parse($key->fecha_tentativa_pago)->format('Y-m-d') : '' }}" />
                                        <button type="button" class="btn btn-outline-secondary" id="btnCalcularFechaPago" 
                                            title="Calcular fecha basada en días de crédito del cliente">
                                            <i class="fa-solid fa-calculator"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fa-solid fa-info-circle me-1"></i>
                                        Calcula desde fecha entrega + días de crédito del cliente
                                    </small>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            {{-- Atención --}}
                            <div>
                                <h6><b class="text-orange fs-5">2.</b> Atención</h6>

                                <div class="row p-2">
                                    <div class="col-md-3 col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name ="tipo_cliente" value="existente" id="defaultCheck1" onclick="existenteCliente()" checked>
                                            <label class="form-check-label" for="defaultCheck1">
                                                Existente 
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-6">
                                    <div class="form-check">
                                            <input class="form-check-input" type="radio" name ="tipo_cliente" value="nuevo" id="defaultCheck1" onclick="nuevoCliente()">
                                            <label class="form-check-label" for="defaultCheck1">
                                                Nuevo
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">

                                    <!-- Componente Livewire para Cliente y Personas de Atención -->
                                    @livewire('personas-atencion-editar', ['clienteId' => $key->id_cliente, 'personaId' => $key->id_atencion, 'servicioId' => $key->id])

                                    <div id="nuevoCliente" class="p-3">
                                        <div class="row mb-3">
                                            <div class="row">
                                                <div class="form-outline col-md-5 col-12">
                                                    <label class="form-label" for="form8Example4">Nombre Atención <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control text" name="nombreatencion" id="nombreatencion" />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>
                                            
                                                <div class="form-outline col-md-4 col-6">
                                                    <label class="form-label">Alias <span class="text-danger">*</span></label>
                                                    <input type="text" id="alias" name="alias" class="form-control" maxlength="50" />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>

                                                <div class="form-outline col-md-3 col-6">
                                                    <label class="form-label">Tipo <span class="text-danger">*</span></label>
                                                    <select id="tipo" name="tipo" class="select-form">
                                                        <option value="" selected>...</option>
                                                        <option value="EXCELENTE">EXCELENTE</option>
                                                        <option value="BUENO">BUENO</option>
                                                        <option value="INTERMEDIO">INTERMEDIO</option>
                                                        <option value="MALO">MALO</option>
                                                        <option value="PESIMO">PESIMO</option>
                                                    </select>
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="form-outline col-md-6 col-12">
                                                    <label class="form-label">Razon Social <span class="text-danger">*</span></label>
                                                    <input type="text" id="razon_social" name="razon_social" maxlength="1000" class="form-control text" />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>

                                                <div class="form-outline col-md-6 col-12">
                                                    <label class="form-label">RFC <span class="text-danger">*</span></label>
                                                    <input type="text" id="rfc" name="rfc" minlength="13" maxlength="13" class="form-control text" />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>
                                            </div>
                                                
                                            <div class="row">
                                                <div class="form-outline col-md-6 col-12">
                                                    <label class="form-label">Telefono <span class="text-danger">*</span></label>
                                                    <input type="text" id="telefono" name="telefono" class="form-control" maxlength="10" />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>

                                                <div class="form-outline col-md-6 col-12">
                                                    <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                                    <input type="text" id="correo_electronico" name="correo_electronico" class="form-control" />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="form-outline col-md-4 col-12">
                                                    <label class="form-label">Ciudad <span class="text-danger">*</span></label>
                                                    <select id="id_ciudad" name="id_ciudad" class="select-form">
                                                        <option value="" selected>...</option>
                                                        @foreach($Ciudades as $key)
                                                        <option value="{{$key->id}}">{{$key->nombre}}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>
                        
                                                <div class="form-outline col-md-4 col-6">
                                                    <label class="form-label">Colonia <span class="text-danger">*</span></label>
                                                    <input type="text" id="colonia" name="colonia" maxlength="50" class="form-control" />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>

                                                <div class="form-outline col-md-4 col-6">
                                                    <label class="form-label">Calle <span class="text-danger">*</span></label>
                                                    <input type="text" id="calle" name="calle" maxlength="50" class="form-control" />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="form-outline col-md-4 col-6">
                                                    <label class="form-label">Numero Externo <span class="text-danger">*</span></label>
                                                    <input type="text" id="numero_ext" name="numero_ext" maxlength="10" class="form-control" />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>

                                                <div class="form-outline col-md-4 col-6">
                                                    <label class="form-label">Numero Interno</label>
                                                    <input type="text" id="numero_int" name="numero_int" maxlength="10" class="form-control" />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>

                                                <div class="form-outline col-md-4 col-12">
                                                    <label class="form-label">C. P. <span class="text-danger">*</span></label>
                                                    <input type="text" id="cp" name="cp" maxlength="10" class="form-control"  />
                                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                          
                        </div>
                    </div>

            
                    <div class="row justify-content-end p-3">
                        <button class="col-md-2 col-4 btn btn-baseColor fs-7" type="submit"> 
                        <i class="fa-solid fa-circle-arrow-right"></i>&nbsp; Continuar  
                        </button>
                    </div>
                </div>
            </div>
            
        </form>
    @endforeach
</div>


<script>
    $("#nuevoCliente").hide();
    $("#existenteCliente").show();

    function nuevoCliente(){
        $("#nuevoCliente").show();
        $("#existenteCliente").hide();

        document.getElementById("nombreatencion").required = true;
        document.getElementById("alias").required = true;
        document.getElementById("tipo").required = true;
        document.getElementById("razon_social").required = true;
        document.getElementById("rfc").required = true;
        document.getElementById("telefono").required = true;
        document.getElementById("correo_electronico").required = true;
        document.getElementById("id_ciudad").required = true;
        document.getElementById("colonia").required = true;
        document.getElementById("calle").required = true;
        document.getElementById("numero_ext").required = true;
        document.getElementById("cp").required = true;

        document.getElementById("cliente").required = false;
    }

    function existenteCliente(){
        $("#nuevoCliente").hide();
        $("#existenteCliente").show();
        document.getElementById("cliente").required = true;

        document.getElementById("nombreatencion").required = false;
        document.getElementById("alias").required = false;
        document.getElementById("tipo").required = false;
        document.getElementById("razon_social").required = false;
        document.getElementById("rfc").required = false;
        document.getElementById("telefono").required = false;
        document.getElementById("correo_electronico").required = false;
        document.getElementById("id_ciudad").required = false;
        document.getElementById("colonia").required = false;
        document.getElementById("calle").required = false;
        document.getElementById("numero_ext").required = false;
        document.getElementById("cp").required = false;
    }

    // Función para calcular fecha tentativa de pago
    function calcularFechaTentativaPago() {
        console.log('Calculando fecha tentativa de pago...');
        
        // Obtener el cliente_id del campo hidden del componente Livewire
        // Intentar múltiples formas de obtener el cliente_id
        let clienteInput = document.querySelector('input[name="cliente"]');
        if (!clienteInput) {
            // Buscar en todos los inputs hidden dentro del componente Livewire
            clienteInput = document.querySelector('[wire\\:id] input[name="cliente"]');
        }
        
        const clienteId = clienteInput ? clienteInput.value : null;
        console.log('Cliente ID encontrado:', clienteId);
        
        // Obtener fecha límite - intentar por ID o por name
        let fechaLimiteInput = document.getElementById('fecha_fin');
        if (!fechaLimiteInput) {
            fechaLimiteInput = document.querySelector('input[name="fecha_fin"]');
        }
        const fechaLimite = fechaLimiteInput ? fechaLimiteInput.value : null;
        console.log('Fecha límite encontrada:', fechaLimite);
        
        const fechaTentativaPago = document.getElementById('fecha_tentativa_pago');
        
        if (!clienteId || clienteId === '' || clienteId === '0') {
            Swal.fire({
                icon: 'warning',
                title: 'Cliente no seleccionado',
                text: 'Debes seleccionar un cliente primero para calcular la fecha de pago.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        if (!fechaLimite || fechaLimite === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Fecha de entrega requerida',
                text: 'Debes ingresar la fecha de entrega primero.',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        // Mostrar loading
        Swal.fire({
            title: 'Calculando...',
            text: 'Obteniendo información del cliente',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Hacer petición AJAX para obtener días de crédito
        fetch(`/api/cliente/${clienteId}/dias-credito`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            Swal.close();
            
            if (data.success && data.modo_pago === 'credito' && data.dias_credito) {
                // Calcular fecha sumando días de crédito a fecha límite
                // Usar formato YYYY-MM-DD directamente para evitar problemas de zona horaria
                const fechaParts = fechaLimite.split('-');
                const year = parseInt(fechaParts[0]);
                const month = parseInt(fechaParts[1]) - 1; // Los meses en JS son 0-indexed
                const day = parseInt(fechaParts[2]);
                const diasCredito = parseInt(data.dias_credito);
                
                // Crear objeto Date con la fecha límite
                const fechaLimiteObj = new Date(year, month, day);
                
                // Sumar los días de crédito
                fechaLimiteObj.setDate(fechaLimiteObj.getDate() + diasCredito);
                
                // Formatear fecha en formato YYYY-MM-DD
                const yearCalculado = fechaLimiteObj.getFullYear();
                const monthCalculado = String(fechaLimiteObj.getMonth() + 1).padStart(2, '0');
                const dayCalculado = String(fechaLimiteObj.getDate()).padStart(2, '0');
                const fechaCalculada = `${yearCalculado}-${monthCalculado}-${dayCalculado}`;
                
                console.log('Fecha límite original:', fechaLimite);
                console.log('Fecha límite parseada:', `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`);
                console.log('Días de crédito:', diasCredito);
                console.log('Fecha después de sumar días:', fechaLimiteObj);
                console.log('Fecha calculada final:', fechaCalculada);
                
                if (fechaTentativaPago) {
                    fechaTentativaPago.value = fechaCalculada;
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Fecha calculada',
                        text: `Fecha tentativa de pago calculada: ${day}/${month}/${year} (${data.dias_credito} días de crédito)`,
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    console.error('No se encontró el campo fecha_tentativa_pago');
                }
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin días de crédito',
                    text: data.modo_pago !== 'credito' 
                        ? 'El cliente tiene modo de pago a contado. Puedes ingresar la fecha manualmente.'
                        : 'El cliente no tiene días de crédito configurados. Puedes ingresar la fecha manualmente.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Entendido'
                });
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error al calcular fecha',
                text: 'No se pudo obtener la información del cliente. Error: ' + error.message,
                confirmButtonColor: '#d33'
            });
        });
    }
    
    // Asignar evento al botón de calcular cuando se carga el DOM
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado, buscando botón calcular...');
        const btnCalcular = document.getElementById('btnCalcularFechaPago');
        console.log('Botón encontrado:', btnCalcular);
        if (btnCalcular) {
            btnCalcular.addEventListener('click', calcularFechaTentativaPago);
            console.log('Evento click asignado al botón');
        } else {
            console.error('No se encontró el botón btnCalcularFechaPago');
        }
    });
    
    // También intentar después de que Livewire cargue
    document.addEventListener('livewire:load', function() {
        const btnCalcular = document.getElementById('btnCalcularFechaPago');
        if (btnCalcular) {
            btnCalcular.addEventListener('click', calcularFechaTentativaPago);
        }
    });
</script>



<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
@endsection
