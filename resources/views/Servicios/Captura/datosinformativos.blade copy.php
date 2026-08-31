@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('PDFwarning'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡Seleccione el archivo correcto!", text: "El formato permitido de archivos admitido es .pdf"});';
            echo '</script>'; 
    @endphp
@endif

<div class="container-fluid format_page">
    <!-- Encabezado -->
    <form action="#" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
      @csrf
        <div class="row">
            <div class="col-lg-6 col-12 start-center">
                <h2 class="mt-1 animate_animated animate_backInLeft">Creación de {{ucfirst(strtolower($tipo))}}</h2>
            </div>

            <div class="col-lg-6 col-12 start-center">
                <div class="row">
                    <div class="col-md-3 col-6 form-outline">
                        <label class="form-label">Nombre</label>
                        <input class="form-control" type="text" name="" id="" placeholder="nombre" maxlength="20" required>
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>

                    <div class="col-md-3 col-6 form-outline">
                        <label class="form-label">Folio</label>
                        <input class="form-control" type="text" name="" id="" placeholder="83901" maxlength="10" required>
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                
                    <div class="col-md-3 col-6 form-outline">
                        <label class="form-label">Fecha Inicio</label>
                        <input class="form-control" type="date" name="" id="" required>
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>

                    <div class="col-md-3 col-6 form-outline">
                        <label class="form-label">Fecha Limite</label>
                        <input class="form-control" type="date" name="" id="" required>
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3  p-3 start-center">
                @if($tipo == "SUMINISTRO")
                @elseif($tipo == "INTEGRACION")
                {{-- @elseif($tipo == "PROYECTOS") --}}
                @endif

                <div class="row">
                    <div class="col-md-2 col-2  d-none d-md-block">
                        <div class="circleBlue"> 1</div>
                    </div>

                    <div class="col-md-10 col-12 p-3 pb-0">
                        <h6>Datos Informativos</h6>
                    </div>
                </div>

                <div class="lineGrey d-none d-md-block"></div>

                <div class="row">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleGrey"> 2</div>
                    </div>

                    <div class="col-md-10 col-10 d-none d-md-block p-3 pt-1 pb-0">
                        <h6 class="m-0 p-0">Materiales y Suministro</h6>
                        <span class="fs-8 text-secondary m-0 p-0">Plantilla de Cotiozaciones</span>
                    </div>
                </div>

                <div class="lineGrey d-none d-md-block"></div>

                <div class="row">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleGrey"> 3</div>
                    </div>

                    <div class="col-md-10 col-10 d-none d-md-block  p-3 pb-0">
                        <h6>Costos Calculados</h6>
                    </div>
                </div>


            </div>

            <div class="col-md-9">
                <div class="row p-3">
                    <div class="border bg-light p-3 rounded-1">
                        <h6><b class="text-violet fs-5">1.</b> Atención</h6>

                        <div class="row">
                            <div class="col-md-3 col-6 form-outline">
                                <label class="form-label">Primer Nombre</label>
                                <input class="form-control" type="text" name="" id="" placeholder="" maxlength="10" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-3 col-6 form-outline">
                                <label class="form-label">Segundo Nombre</label>
                                <input class="form-control" type="text" name="" id="" placeholder="" maxlength="10">
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-3 col-6 form-outline">
                                <label class="form-label">Apellido Paterno</label>
                                <input class="form-control" type="text" name="" id="" placeholder="" maxlength="10" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-3 col-6 form-outline">
                                <label class="form-label">Apellido Materno</label>
                                <input class="form-control" type="text" name="" id="" placeholder="" maxlength="10" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-6 form-outline">
                                <label class="form-label">Puesto</label>
                                <input class="form-control" type="text" name="" id="" placeholder="" maxlength="20" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-4 col-6 form-outline">
                                <label class="form-label">Telefono</label>
                                <input class="form-control" type="number" name="" id="" placeholder="" maxlength="10" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-4 col-12 form-outline">
                                <label class="form-label">Correo Eléctronico</label>
                                <input class="form-control" type="text" name="" id="" placeholder="" maxlength="50" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>

                        <h6 class="text-secondary mt-2">Empresa</h6>
                        <div class="row">
                            <div class="col-md-4 col-6 form-outline">
                                <label class="form-label">Razón Social</label>
                                <input class="form-control" type="text" name="" id="" placeholder="" maxlength="50" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-4 col-6 form-outline">
                                <label class="form-label">RFC</label>
                                <input class="form-control" type="text" name="" id="" placeholder="" maxlength="13" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-4 col-12 form-outline">
                                <label class="form-label">Dirección</label>
                                <input class="form-control" type="text" name="" id="" placeholder="" maxlength="50" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row p-3">
                    <div class="border bg-light p-3 rounded-1">
                        <h6><b class="text-violet fs-5">2.</b> Vendedor</h6>
                         <div class="row">
                            <div class="col-md-4 col-6 form-outline">
                                <label class="form-label">Empleado</label>
                                <select name="" id="" class="form-select" required>
                                    <option value="" selected>...</option>
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-3 col-6 form-outline">
                                <label class="form-label">Telefono</label>
                                <input class="form-control" type="number" name="" id="" placeholder="" maxlength="10" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-5 col-6 form-outline">
                                <label class="form-label">Correo Eléctronico</label>
                                <input class="form-control" type="text" name="" id="" placeholder="" maxlength="50" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-end p-3">
                    <a class="col-md-2 col-4 btn btn-violet fs-7" href="/Servicios/Captura/Materiales/{{$tipo}}"> 
                       <i class="fa-solid fa-circle-arrow-right"></i>&nbsp; Continuar  
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>



<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
@endsection
