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
                <a href="/Servicios" class="btn btn-primary fs-8"><i class="fa-solid fa-house"></i> Inicio</a> 
            </div>

            <div class="col-lg-6 col-12 d-none d-md-block start-center">
               @foreach($servicio_encxid as $key)
                    <div class="row">
                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" type="text" value="{{$key->nombre}}" placeholder="nombre" maxlength="20" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>

                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Folio</label>
                            <input class="form-control" type="text" value="{{$key->folio}}" placeholder="83901" maxlength="10" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    
                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Fecha Inicio</label>
                            <input class="form-control" type="date" value="{{$key->fecha_inicio}}" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>

                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Fecha Limite</label>
                            <input class="form-control" type="date" value="{{$key->fecha_limite}}" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 p-3 start-center">
                <a href="/Servicios/Editar/Datos/{{$tipo}}/{{$id}}" class="row pointer">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleBlue"> 1</div>
                    </div>

                    <div class="col-md-10 col-6 p-3 pb-0 d-none d-md-block">
                        <h6 class="text-dark">Datos Informativos</h6>
                    </div>
                </a>

                <div class="lineBlue d-none d-md-block"></div>

                <a href="/Servicios/Editar/Materiales/{{$tipo}}/{{$id}}" class="row pointer">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleBlue"> 2</div>
                    </div>

                    <div class="col-md-10 col-12 p-3 pt-1 pb-0  d-none d-md-block">
                        <h6 class="m-0 p-0 text-dark">Materiales y Suministro</h6>
                        <span class="fs-8 text-secondary m-0 p-0">Plantilla de Cotiozaciones</span>
                    </div>
                </a>

                 @if($tipo == "Integración")
                <div class="lineBlue d-none d-md-block"></div>

                <a href="/Servicios/Editar/Costos/{{$tipo}}/{{$id}}" class="row pointer">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleBlue">3</div>
                    </div>

                    <div class="col-md-10 col-12 d p-3 pb-0">
                        <h6 class="text-dark">Costos Calculados</h6>
                    </div>
                </a>
                @endif
            </div>

            <div class="col-md-9">
                {{-- CONCEPTOS --}}
                 <div class="row p-3">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <td class="fw-bold">Concepto</td>
                                <td class="fw-bold">Monto</td>
                                <td class="fw-bold">% Participación</td>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>MANO DE OBRA</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>
                            <tr>
                                <td>MATERIALES Y SUMINISTRO</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>
                            <tr>
                                <td>ALIMENTACIÓN</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>
                            <tr>
                                <td>HOSPEDAJE</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>
                            <tr>
                                <td>HERRAMIENTAS Y EQUIPO</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>
                            <tr>
                                <td>ARTICULOS DEL HOGAR</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>
                            <tr>
                                <td>TRANSPORTE Y LOGISTICA</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>
                            <tr>
                                <td>SERVICIOS PUBLICOS</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>
                            <tr>
                                <td>ALQUILER DE OFICINAS Y ALMACENES</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>

                            <tr>
                                <td>SEGUROS</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>

                            <tr>
                                <td>PERMISOS Y LICENCIAS</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>

                            <tr>
                                <td>CAPACITACIONES Y CERTIFICACIONES</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>

                            <tr>
                                <td>GASTOS MEDICOS, OFICINA Y OTROS</td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                                <td>
                                    <input type="number" step="any" class="form-control" name="" id=""  value="0" required>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- PORCENTAJE UTLIDAD --}}
                <div class="row p-3">
                    <div class="row justify-content-start mb-2">
                        <div class="col-3">
                            <input class="form-control" type="text" name="" id="porcentaje" placeholder="Porcentaje" required>
                        </div>
                        <button class="col-1 btn btn-primary fs-7 p-1" style="margin-top: -5px; padding:1px;" type="button" onclick="calcular()">Calcular</button>
                    </div>

                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <td class="fw-bold">Porcentaje de Utilidad</td>
                                <td class="fw-bold text-primary" id="porcentajetext">0%</td>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <th>Total</td>
                                <td>$ {{number_format(0,2)}}</td>
                            </tr>
                            <tr>
                                 <th>Total Facturación</td>
                                <td>$ {{number_format(0,2)}}</td>
                            </tr>
                            <tr>
                                <td class="text-success fw-bold">Total Utilidad</td>
                                <td class="text-success fw-bold">$ {{number_format(0,2)}}</td>
                            </tr>
                            <tr>
                                 <td colspan="2"></td>
                            </tr>
                            <tr>
                                <th class="fst-italic">Iva Integrado</td>
                                <td class="fst-italic">$ {{number_format(0,2)}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- PUESTOS --}}
                <div class="row p-3">
                    <div class="border bg-light p-3 rounded-1">
                        <div class="row">
                            <div class="col-md-8 col-6 form-outline">
                                <label class="form-label">Puesto</label>
                                <input class="form-control" type="text" name="" id="" placeholder="Buscar..." required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-4 col-6 form-outline">
                                <label class="form-label">Cantidad</label>
                                <input class="form-control" type="number" name="" id="" placeholder="0" maxlength="10" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="row">
                                <div>
                                    <button class="btn btn-baseColor fs-8"><i class="fa-solid fa-plus"></i> Agregar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row p-3">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <td class="fw-bold">Puesto</td>
                                <td class="fw-bold">Cantidad</td>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>Lider de Cuadrilla</td>
                                <td>1</td>
                            </tr>
                            <tr>
                                <td>Cargador</td>
                                <td>3</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end p-3">
                    <a class="col-md-2 col-4 btn btn-baseColor fs-7" href="/Servicios"> 
                       <i class="fa-solid fa-circle-arrow-right"></i>&nbsp; Continuar  
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>



<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
<script>
    function calcular(){
        porcetaje = $("#porcentaje").val();
        document.getElementById('porcentajetext').textContent = porcetaje+" %";
    }
</script>
@endsection
