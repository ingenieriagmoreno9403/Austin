@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid format_page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-file-lines"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Detalle de Licitación</h2>
                            <p class="text-muted mb-0">Consulta de información de la comparativa</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div data-bs-spy="scroll" data-bs-target="#navbar-example" data-bs-offset="0" class="scrollspy-example"
            tabindex="0">

            <form enctype="multipart/form-data" class="g-3 needs-validation form" novalidate>
                @csrf

                <!-- Datos generales-->
                <div class="bg-body rounded-2 p-4 mb-4">
                    <div class="row mb-3">
                        <h4 class="col-md-8" id="paso1">
                            <b class="fs-3 text-orange">1. </b> Información General
                            <span class="badge bg-{{ $licitacion->estado == 'adjudicada' ? 'success' : 'primary' }} ms-2">
                                {{ ucfirst($licitacion->estado ?? 'Pendiente') }}
                            </span>
                        </h4>
                        
                        <div class="col-md-4 text-end">
                            <span class="text-muted">ID: {{ $licitacion->id }}</span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-9 col-12 order-lg-first order-last mt-2">
                            <div class="row">
                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label fw-bold" for="form8Example4">Nombre:</label>
                                        <input type="text" class="form-control-plaintext border-bottom" name="nombre"
                                            id="nombre" value="{{ $licitacion->nombre }}" readonly />
                                    </div>
                                </div>

                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label fw-bold">Fecha Creación:</label>
                                        <input type="text" id="fecha_creacion" name="fecha_creacion"
                                            class="form-control-plaintext border-bottom" value="{{ $licitacion->fecha_creacion }}" readonly/>
                                    </div>
                                </div>

                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label fw-bold">Fecha Límite:</label>
                                        <input type="text" name="fecha_limite" id="fecha_limite"
                                            class="form-control-plaintext border-bottom" value="{{ $licitacion->fecha_limite }}"
                                            readonly />
                                    </div>
                                </div>

                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label fw-bold">Descripción:</label>
                                        <textarea name="descripcion_detalle" id="descripcion_detalle"
                                            class="form-control-plaintext border-bottom" readonly>{{ $licitacion->descripcion_detalle }}</textarea>
                                    </div>
                                </div>
                                
                            </div>

                        </div>

                    </div>
                    
                    <div class="mt-4">
                        <h5 class="mb-3"><i class="fa-solid fa-users me-2"></i>Proveedores Invitados</h5>
                        @livewire('proveedor-selector', ['licitacionId' => $licitacion->id,'sololectura' => true])
                    </div>
                    
                    @if($licitacion->estado != 'adjudicada')
                        {{-- <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title"><i class="fa-solid fa-envelope me-2"></i>Notificaciones</h6>
                                        <p class="card-text">Enviar correos electrónicos a todos los proveedores invitados.</p>
                                        <a href="{{ route('licitaciones.enviarcorreo', ['id' => $licitacion->id]) }}" 
                                            class="btn btn-primary">
                                            <i class="fa-solid fa-paper-plane me-2"></i>Enviar correos
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
                    @else
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fa-solid fa-check-circle me-2"></i>Licitación Adjudicada
                                        </h6>
                                        <p class="card-text">
                                            Esta licitación fue adjudicada al proveedor: 
                                            <strong>{{ $licitacion->proveedor_adjudicado->nombre ?? 'No especificado' }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="bg-body rounded-2 p-4 mb-4">
                    {{-- <h4 class="mb-3"><b class="fs-3 text-orange">2. </b> Productos</h4> --}}
                    @livewire('licitacion-detalle', ['detalle' => old('detalle', $detalle ?? []),'sololectura' => true])
                </div>
                
                <!-- Botones de acción -->
                <div class="mb-5 text-center" style="padding:10px;">
                    <div>
                        <a href="{{ route('licitaciones.editar', $licitacion->id) }}" class="btn btn-primary rounded-5 fs-6_5">
                            <i class="fa-solid fa-edit me-1"></i>Editar
                        </a>
                        
                        <button type="button" class="btn btn-secondary rounded-5 fs-6_5"
                                onclick="window.location='{{ route('licitaciones.index') }}'">
                            <i class="fa-solid fa-arrow-left me-1"></i>Volver
                        </button>
                        
                        @if($licitacion->estado != 'adjudicada' && count($detalle) > 0)
                            <a href="{{ route('licitaciones.comparar', $licitacion->id) }}" class="btn btn-success rounded-5 fs-6_5">
                                <i class="fa-solid fa-file-contract me-1"></i>Comparar Cotizaciones
                            </a>
                        @endif
                    </div>
                </div>
            </form>

        </div>

    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/validaPDF.js') }}"></script>

 @endsection
