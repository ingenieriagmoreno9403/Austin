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
            echo 'Toast.fire({ icon: "warning",title: "¡Opss...!", text: "Recuerde subir todo los formatos, solo se admiten archivos .pdf"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('Errorpermisos'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "info",title: "¡No se encontro el permiso para efectuar la accion!", text: "Comunicate al area de sistemas para validar permisos"});';
            echo '</script>'; 
    @endphp
@endif

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <!--Icon Area-->
    <div class="pos__ico d-none d-md-block">
        <img style="width:100px;margin-left:-40px;margin-top:-10px!important;" src="{{ asset('Images/fincreVertical.png') }}"
            alt="fincreLaguna" class="mt-2">
    </div>

    <div class="marginLeft">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-hand-holding-dollar"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Créditos de Empleados</h2>
                            <p class="text-muted mb-0">Control de créditos a empleados</p>
                        </div>
                    </div>
                    <div class="header-actions"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container container-fluid format_page">
        <div class="row mt-5">
            <div class="col-md-4 mb-3 mr-2 push">
                @if ($captura_credito_emp == 'A')
                    <a href="/prestamosnominas/capturaPrestamos"
                        class="animate___animated animate___flipInY card bg-dark text-white border-0" style="height: 300px">
                        <img src="{{ asset('Images/15.png') }}" class="card-img border-0" alt="captura_credito"
                            style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(0, 0, 0, 0.387)">
                        @else
                            <a href="#"
                                class="animate___animated animate___flipInY card bg-dark text-white pointer_none border-0"
                                style="height: 300px">
                                <img src="{{ asset('Images/15.png') }}" class="card-img border-0" alt="captura_credito"
                                    style="height: 300px;object-fit:cover;">
                                <div class="card-img-overlay text-start"
                                    style="background-color:  rgba(173, 173, 173, 0.731)">
                @endif
                <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-1"
                    type="submit">
                    <h4 class="text-light">Captura de Prestamos</h4>
                </div>
                <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start"
                    style="margin-top: -30px;" type="submit">Calcula y autoriza prestamos a empleados.</div>
            </div>
            </a>
        </div>

        <div class="col-md-4 mb-3 mr-2 push">
            @if ($captura_credito_emp == 'A')
                <a href="/prestamosnominas/creditosEmpleadosCatalogo"
                    class="animate___animated animate___flipInY card bg-dark text-white border-0" style="height: 300px">
                    <img src="{{ asset('Images/16.png') }}" class="card-img border-0" alt="catalogo_credito"
                        style="height: 300px;object-fit:cover;">
                    <div class="card-img-overlay text-start" style="background-color: rgba(0, 0, 0, 0.387)">
                    @else
                        <a href="#"
                            class="animate___animated animate___flipInY card bg-dark text-white pointer_none border-0"
                            style="height: 300px">
                            <img src="{{ asset('Images/16.png') }}" class="card-img border-0" alt="catalogo_credito"
                                style="height: 300px;object-fit:cover;">
                            <div class="card-img-overlay text-start" style="background-color:  rgba(173, 173, 173, 0.731)">
            @endif
            <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-1"
                type="submit">
                <h4 class="text-light">Catalogo de Prestamos</h4>
            </div>
            <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start"
                style="margin-top: -30px;" type="submit">Visualizar prestamos de empleados y gestionarlos.</div>
        </div>
        </a>
    </div>


    <div class="col-md-4 mb-3 mr-2 push">
        @if ($permisos3 == 'global_empleados')
            <a href="/Global/Nominas" class="animate___animated animate___flipInY card bg-dark text-white border-0"
                style="height: 300px">
                <img src="{{ asset('Images/14.png') }}" class="card-img border-0" alt="catalogo_credito"
                    style="height: 300px;object-fit:cover;">
                <div class="card-img-overlay text-start" style="background-color: rgba(0, 0, 0, 0.387)">
                @else
                    <a href="#"
                        class="animate___animated animate___flipInY card bg-dark text-white pointer_none border-0"
                        style="height: 300px">
                        <img src="{{ asset('Images/14.png') }}" class="card-img border-0" alt="catalogo_credito"
                            style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color:  rgba(173, 173, 173, 0.731)">
        @endif
        <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-1" type="submit">
            <h4 class="text-light">Global Prestamos</h4>
        </div>
        <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start"
            style="margin-top: -30px;" type="submit">Visualizar global de prestamos en nomina.</div>
    </div>
    </a>
    </div>
    </div>
    </div>
@endsection
