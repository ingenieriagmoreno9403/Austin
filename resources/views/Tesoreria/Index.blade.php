@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .tesoreria-page .header h2,
    .tesoreria-page .text-marino {
        color: var(--secondary-color, #1e3a5f) !important;
    }

    .tesoreria-page .catalog-module-card {
        height: 300px;
        background-color: #212529 !important;
        color: #ffffff !important;
        border: 0 !important;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .tesoreria-page .catalog-module-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.24);
    }

    .tesoreria-page .catalog-module-card .card-img {
        height: 300px;
        object-fit: cover;
    }

    .tesoreria-page .catalog-module-card .card-img-overlay {
        background-color: rgba(0, 0, 0, 0.387) !important;
    }

    .tesoreria-page .catalog-module-card.is-disabled {
        pointer-events: none;
        filter: grayscale(0.35);
    }

    .tesoreria-page .catalog-module-card.is-disabled .card-img-overlay {
        background-color: rgba(173, 173, 173, 0.731) !important;
    }

    .tesoreria-page .catalog-module-card h2,
    .tesoreria-page .catalog-module-card .text-light {
        color: #ffffff !important;
    }
</style>

<div class="pos__ico">
    <img class="ico__image" src="{{ asset('ico/valeMil.png') }}" alt="valeMil">
</div>

<div class="container-fluid tesoreria-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Catálogo de Tesorería</h2>
                        <p class="text-muted mb-0">Módulos con respecto a tesorería y su manejo</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        @if($permisos1 == "gestion_cuentas")
            <div class="col-md-3 mb-3 mr-2 push">
                <a href="/Tesoreria/Cuentas" class="animate___animated animate___flipInY card catalog-module-card text-white border-0">
                    <img src="{{ asset('Images/14.png') }}" class="card-img border-0" alt="Cuentas">
                    <div class="card-img-overlay text-start">
                        <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5" type="submit"><h2 class="text-light">Cuentas</h2></div>
                        <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -50px;" type="submit">Control de cuentas, gestionando diversos movimientos.</div>
                    </div>
                </a>
            </div>
        @else
            <div class="col-md-3 mb-3 mr-2">
                <div class="animate___animated animate___flipInY card catalog-module-card is-disabled text-white border-0">
                    <img src="{{ asset('Images/14.png') }}" class="card-img border-0" alt="Cuentas">
                    <div class="card-img-overlay text-start">
                        <div class="animate___animated animate___backInDown btn border-0 card-title fw-bold text-light mt-5" type="submit"><h2 class="text-light">Cuentas</h2></div>
                        <div class="animate___animated animate___backInDown btn border-0 card-text fs-9 text-light text-start" style="margin-top: -50px;" type="submit">Control de cuentas, gestionando diversos movimientos.</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
