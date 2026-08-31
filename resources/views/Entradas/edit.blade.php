@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('Entradas.index') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Recepciones
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Editar Recepción de Inventario</h2>
                        <p class="text-muted mb-0">Modificación de la recepción de productos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @livewire('entradas-inventario', ['entradaId' => $entradaId])
</div>
@endsection
