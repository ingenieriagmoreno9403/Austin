@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Catálogos</h2>
                        <p class="text-muted mb-0">Gestión de catálogos generales del sistema</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row mb-4 rounded-3 p-3">
            <h4>General</h4><hr>
                @if($permisos1 == "gestion_empresas")
                <div class="col-md-3 mb-3 mr-2 push">
                    <a href="/CatalogoGeneral/Empresas" class="animate___animated animate___flipInY card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/21.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(89, 89, 89, 0.158)">
                @else
                    <div class="col-md-3 mb-3 mr-2">
                    <a href="/CatalogoGeneral/Empresas" class="animate___animated animate___flipInY pointer_none card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/21.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(89, 89, 89, 0.661)">
                @endif
                            <div class="animate___animated animate___backInDown btn border-0  fw-bold text-light mt-5" type="submit"><h2 class="text-light">Empresas</h2></div>
                            <div class="animate___animated animate___backInDown btn border-0  fs-9 text-light text-start" style="margin-top: -10px;" type="submit">Control de empresas con respecto a razón social, gestionando diversos movimientos.</div>
                        </div>
                    </a>
                </div>


                @if($permisos2 == "gestion_sucursales")
                <div class="col-md-3 mb-3 mr-2 push">
                    <a href="/CatalogoGeneral/Sucursales" class="animate___animated animate___flipInY card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/22.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(89, 89, 89, 0.158)">
                @else
                <div class="col-md-3 mb-3 mr-2">
                    <a href="/CatalogoGeneral/Sucursales" class="animate___animated animate___flipInY pointer_none card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/22.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(89, 89, 89, 0.661)">
                @endif
                            <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5" type="submit"><h2 class="text-light">Sucursales</h2></div>
                            <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -30px;" type="submit">Control de apertura de sucursales y gestionando diversos movimientos.</div>
                        </div>
                    </a>
                </div>
        </div> 


        <div class="row mb-4 rounded-3 p-3">
            <h4>Tesorería</h4><hr>

                @if($permisos5 == "gestion_permisosCuentas")
                <div class="col-md-3 mb-3 mr-2 push">
                    <a href="/CatalogoGeneral/PermisosCuentas" class="animate___animated animate___flipInY card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/23.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(89, 89, 89, 0.158)">
                @else
                <div class="col-md-3 mb-3 mr-2">
                    <a href="/CatalogoGeneral/PermisosCuentas" class="animate___animated animate___flipInY pointer_none card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/23.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(89, 89, 89, 0.661)">
                @endif
                            <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5" type="submit"><h4 class="text-light">Permisos Cuentas</h4></div>
                            <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -30px;" type="submit">Vista de cuentas por usuario en los respectivos modulos.</div>
                        </div>
                    </a>
                </div>
            
                @if($permisos3 == "gestion_gastos")
                <div class="col-md-3 mb-3 mr-2 push">
                    <a href="/CatalogoGeneral/Gastos" class="animate___animated animate___flipInY card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/15.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(89, 89, 89, 0.158)">
                @else
                <div class="col-md-3 mb-3 mr-2">
                    <a href="/CatalogoGeneral/Gastos" class="animate___animated animate___flipInY pointer_none card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/15.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(89, 89, 89, 0.661)">
                @endif
                            <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5" type="submit"><h2 class="text-light">Gastos</h2></div>
                            <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -30px;" type="submit">Alta de gastos con respecto a gastos y gestionar diversos movimientos.</div>
                        </div>
                    </a>
                </div>


                @if($permisos4 == "gestion_cajas")
                <div class="col-md-3 mb-3 mr-2 push">
                    <a href="/CatalogoGeneral/Cajas" class="animate___animated animate___flipInY card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/17.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(0, 0, 0, 0.337)">
                @else
                <div class="col-md-3 mb-3 mr-2">
                    <a href="/CatalogoGeneral/Cajas" class="animate___animated animate___flipInY pointer_none card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/17.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(89, 89, 89, 0.661)">
                @endif
                            <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5" type="submit"><h2 class="text-light">Cajas</h2></div>
                            <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -30px;" type="submit">Control y gestión de cajas, aplicando la asignación de las mismas.</div>
                        </div>
                    </a>
                </div>
                @if($permisos5 == "gestion_permisosCuentas")
                <div class="col-md-3 mb-3 mr-2 push">
                    <a href="/Tesoreria/Cuentas" class="animate___animated animate___flipInY card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/16.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(0, 0, 0, 0.337)">
                @else
                <div class="col-md-3 mb-3 mr-2">
                    <a href="/Tesoreria/Cuentas" class="animate___animated animate___flipInY pointer_none card bg-dark text-white border-0" style="height: 300px;">
                        <img src="{{asset('Images/16.png')}}" class="card-img border-0" alt="Inventario_valeras" style="height: 300px;object-fit:cover;">
                        <div class="card-img-overlay text-start" style="background-color: rgba(89, 89, 89, 0.661)">
                @endif
                            <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5" type="submit"><h2 class="text-light">Cuentas</h2></div>
                            <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -30px;" type="submit">Control y gestión de cuentas, aplicando la asignación de las mismas.</div>
                        </div>
                    </a>
                </div>
        </div>
         
    </div>
@endsection