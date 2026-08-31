@extends('layouts.app')
@section('content')

<div class="pos__ico">
    <img class="ico__image" src="{{ asset('ico/valeMil.png') }}" alt="valeMil">
</div>

    <div class="container">
        <div class="row mt-1 mb-4 bg-p">
            <div class="center">
                <h2 class="mb-1 fw-light">Catálogo de Tesorería</h2> 
                <p class="text-secondary text-truncate" style="margin-top:-5px;" title="Título fuente">Módulos con  respecto a Tesorería y su manejo.</p>
            </div>
        </div>

        <div class="row mt-4">
            @if($permisos1 == "gestion_cuentas")
                <div class="col-md-3 mb-3 mr-2 push">
                    <a href="/Tesoreria/Cuentas" class="animate___animated animate___flipInY card bg-dark text-white border-0">
                        <img src="{{asset('Images/14.png')}}" class="card-img border-0" alt="Inventario_valeras">
                        <div class="card-img-overlay text-start" style="background-color: rgba(0, 0, 0, 0.387)">
                            <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5" type="submit"><h2 class="text-light">Cuentas</h2></div>
                            <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -50px;" type="submit">Control de cuentas, gestionando diversos movimientos.</div>
                        </div>
                    </a>
                </div>
            @else
                <div class="col-md-3 mb-3 mr-2">
                    <div class="animate___animated animate___flipInY card bg-dark text-white border-0">
                        <img src="{{asset('Images/14.png')}}" class="card-img border-0" alt="Inventario_valeras">
                        <div class="card-img-overlay text-start" style="background-color: rgba(173, 173, 173, 0.731)">
                            <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5" type="submit"><h2 class="text-light">Cuentas</h2></div>
                            <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -50px;" type="submit">Control de cuentas, gestionando diversos movimientos.</div>
                        </div>
                    </div>
                </div>
            @endif
            
            
        </div>  
    </div>
@endsection