@extends('layouts.app')
@section('content')

    <div class="container-fluid format_page">
        <div class="row">
            <div class="center">
                <h3 class="mt-1 animate__animated animate__backInLeft">Editar Licitacion</h3>
            </div>
        </div>

        <div data-bs-spy="scroll" data-bs-target="#navbar-example" data-bs-offset="0" class="scrollspy-example"
            tabindex="0">

            <form action="{{ route('licitaciones.update', $licitacion->id) }}" method="POST"
                enctype="multipart/form-data" class="g-3 needs-validation form" novalidate>
                @csrf
                @method('PUT')

                <!-- Datos generales-->
                <div class="bg-body rounded-2 p-4 mb-4">
                    <div class="row">
                        <h4 class="col-md-2" id="paso1"><b class="fs-3 text-orange">1. </b> General</h4>
                    </div>

                    <div class="row">
                        <div class="col-lg-9 col-12 order-lg-first order-last mt-2">
                            <div class="row">
                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Nombre</label>
                                        {{-- <input type="text" hidden class="form-control text" name="idnomina"
                                            value="{{ $licitacion->idnom }}"required /> --}}
                                        <input type="text" class="form-control text" name="nombre"
                                            id="nombre" value="{{ $licitacion->nombre }}" minlength="3"
                                            maxlength="20" required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Fecha Creacion</label>
                                        <input type="date" id="fecha_creacion" name="fecha_creacion"
                                            class="form-control text" value="{{ $licitacion->fecha_creacion }}" required/>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Fecha Limite</label>
                                        <input type="text" name="fecha_limite" id="fecha_limite"
                                            class="form-control text" value="{{ $licitacion->fecha_limite }}"
                                            required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Descripción</label>
                                        <textarea name="descripcion_detalle" id="descripcion_detalle"
                                            class="form-control text" required>{{ $licitacion->descripcion_detalle }}</textarea>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>
                                
                            </div>

                        </div>

                    </div>
                    @livewire('proveedor-selector', ['licitacionId' => $licitacion->id,'sololectura' => false])

                </div>

                @livewire('licitacion-detalle', ['detalle' => old('detalle', $detalle ?? []),'sololectura' => false])
                
                <!-- Guardar-->
                <div class="mb-5 text-center" style="padding:10px;">
                    <div>
                        <button onclick="Livewire.emit('guardarTodo')"class="btn btn-primary rounded-5 fs-6_5 push" type="submit" >&nbsp;<i
                                class="fa-solid fa-check"></i>&nbsp;Guardar cambios&nbsp;</button>
                        <button type="button" class="btn btn-secondary rounded-5 fs-6_5"
                                onclick="window.location='{{ route('licitaciones.index') }}'">
                            &nbsp;<i class="fa-solid fa-arrow-left"></i>&nbsp;Volver&nbsp;
                        </button>
                        
                        <br>
                        <br>
                    </div>
                </div>
            </form>

        </div>

    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/validaPDF.js') }}"></script>

 @endsection
