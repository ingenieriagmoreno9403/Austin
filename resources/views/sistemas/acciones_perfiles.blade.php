@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}?v={{ @filemtime(public_path('css/vistas.css')) ?: time() }}" rel="stylesheet">

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-link"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Acciones a perfiles</h2>
                        <p class="text-muted mb-0">Asignar a cada perfil las acciones de los módulos disponibles.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-baseColor-light fs-7 mb-2" href="/Panel">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    @livewire('acciones-perfiles-selector')
</div>

@endsection
