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
                            <h2 class="mb-0 text-marino fw-bold">Editar Producto</h2>
                            <p class="text-muted mb-0">Modificación de producto del proveedor</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($producto as $ltsproducto)
            <div data-bs-spy="scroll" data-bs-target="#navbar-example" data-bs-offset="0" class="scrollspy-example"
                tabindex="0">

                <form action="{{ route('updateprod_prove', $ltsproducto->id) }}" method="POST"
                    enctype="multipart/form-data" class="g-3 needs-validation form" novalidate>
                    @csrf
                    <!-- Datos generales-->
                    <div class="bg-body rounded-2 p-4 mb-4">
                        <div class="row">
                            <h4 class="col-md-2" id="paso1"><b class="fs-3 text-orange">1. </b> General</h4>
                        </div>

                        <div class="row">
                            <div class="col-lg-9 col-12 order-lg-first order-last mt-2">
                                <div class="row">
                                    <div class="col-md-6 col-12 mt-2">
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Nombre</label>
                                            <input type="text" hidden class="form-control text" name="nombre"
                                                value="{{ $ltsproducto->nombre }}"required />
                                            <input type="text" class="form-control text" name="primer_nombre"
                                                id="primer_nombre" value="{{ $ltsproducto->nombre }}" minlength="3"
                                                maxlength="20" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-12 mt-2">
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Categoria</label>
                                                <select class="form-select" name="categoria" id="categoria" required>
                                                    <option value="{{ $ltsproducto->idcat }}" selected>
                                                        {{ $ltsproducto->categoria }}</option>
                                                        @foreach ($categoriasproductos as $tblcategoriasproductos)
                                                        <option value="{{ $tblcategoriasproductos->id }}">
                                                            {{ $tblcategoriasproductos->nombre }}</option>
                                                        @endforeach
                                                </select>
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-4 col-12 mt-2">
                                        <!-- Name input -->
                                        <div class="form-outline">
                                            <label class="form-label">Costo</label>
                                            <input type="number" step="0.01" name="costo" id="costo"
                                                class="form-control text" value="{{ $ltsproducto->costo}}"
                                                minlength="3" maxlength="100" required />
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                </div>

                                <div class="row">
                        

                                    <div class="col-md-2 col-12 mt-2">
                                        <div class="form-outline">
                                            <label class="form-label">Unidad de medida</label>
                                                <select class="form-select" name="medida" id="medida" required>
                                                    <option value="{{ $ltsproducto->umid }}" selected>
                                                        {{ $ltsproducto->unidad_medida }}</option>
                                                        @foreach ($medidas as $tblmedidas)
                                                        <option value="{{ $tblmedidas->id }}">
                                                            {{ $tblmedidas->nombre }}</option>
                                                        @endforeach
                                                </select>
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

                   

                            <div class="col-md-4 col-12 mt-2">
                                <!-- Email input -->
                        
                        </div>

                        <div class="row">
                            <div class="col-md-3 col-12 mt-2">
                                <!-- Email input -->
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Moneda</label>
                                        <select class="form-select" name="moneda" id="moneda" required>
                                            <option value="{{ $ltsproducto->idm }}" selected>
                                                {{ $ltsproducto->moneda." (".$ltsproducto->abrevia.")" }}</option>
                                            @foreach ($monedas as $ltsmonedas )
                                            <option value="{{ $ltsmonedas->id }}">
                                                {{ $ltsmonedas->nombre." (".$ltsmonedas->abreviacion.")" }}</option>
                                                @endforeach
                                        </select>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <label class="form-label">Descripcion</label>
                                <textarea  type="text" id="descripcion" name="descripcion"
                                class="form-control"  required >{{ $ltsproducto->descripcion }}</textarea>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                        </div>
                    </div>

                    </div>


                   


                    <!-- Guardar-->
                    <div class="mb-5 text-center" style="padding:10px;">
                        <div>
                            <button class="btn btn-primary rounded-5 fs-6_5 push" type="submit">&nbsp;<i
                                    class="fa-solid fa-check"></i>&nbsp;Guardar cambios&nbsp;</button>
                            <br><br>
                        </div>
                    </div>
                </form>


            </div>
        @endforeach
    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/validaPDF.js') }}"></script>
@endsection
