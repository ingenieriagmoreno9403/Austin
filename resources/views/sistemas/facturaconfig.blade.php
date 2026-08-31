@extends('layouts.app')
@section('content')
<div class="container-fluid format_page">
    <div class="row">
       <div class="col start-center">
           <h3 class="mt-1 animate__animated animate__backInLeft">Configuracion Facturacion</h3>
           <span class="p-0 m-0 d-none d-md-block fs-8">Añadir los datos correspondientes del pack de facturacion</span>
       </div>
   </div>
   @if($datosfacturify->isEmpty())
    <div class="card p-3 mt-3 bg-body rounded">
        <div class="card-header text-start bg-body border-0">
            <h6 class="text-orange">Añadir Configuracion</h6>
        </div>

        <div class="card-body">
            <form method="POST" action="/Sistemas/guardar_config" method="POST"
                class="g-3 needs-validation form" enctype="multipart/form-data" id="frm" novalidate>
                @csrf
                <div class="row mb-3">
                    <div class="col-lg-3 col-12">
                        <div class="form-outline text-truncate">
                            <label class="form-label">Url Api</label>
                            <input type="text" id="url_api" name="url_api" class="form-control"
                                maxlength="45" placeholder="Url Api" required />
                            <div class="valid-feedback">
                                ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-12 text-truncatel">
                        <div class="form-outline">
                            <label class="form-label">Web Admin</label>
                            <input type="text" id="web_admin" name="web_admin" class="form-control" maxlength="50"
                                placeholder="Web Admin" required />
                            <div class="valid-feedback">
                                ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-12 text-truncatel">
                        <div class="form-outline">
                            <label class="form-label">Api Secret</label>
                            <input type="text" id="api_secret" name="api_secret" class="form-control" maxlength="50"
                                placeholder="Api Secret" required />
                            <div class="valid-feedback">
                                ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-12 text-truncatel">
                        <div class="form-outline">
                            <label class="form-label">Api Key</label>
                            <input type="text" id="api_key" name="api_key" class="form-control" maxlength="50"
                                placeholder="Api Key" required />
                            <div class="valid-feedback">
                                ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-12 text-truncate">
                        <div class="form-outline">
                            <label class="form-label">Fecha</label>
                            <input type="text" id="fecha" name="fecha" class="form-control" maxlength="50" value="{{date("Y-m-d");}}" placeholder ="Api Key" required  />
                            <div class="valid-feedback">
                                ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-12 text-truncate">
                        <br>
                        <button type="submit" class="fs-6 btn btn-baseColor mt-2 text-truncate">
                            <i class="fas fa-check"></i> Registrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
@endif
        @if($datosfacturify->isNotEmpty())
        <div class="card p-3 mt-3 bg-body rounded">
            <div class="card-header text-start bg-body border-0">
                <h6 class="text-orange">Configuracion Guardada</h6>
            </div>
        <div class="row mt-3">
            <div class="table-responsive">
                <table class="table table-stripped table-hover display" id="table">
                    <thead>
                        <tr class="table-light text-secondary">
                            <th class="text-center text-truncate fw-bold">No.</th>
                            <th class="text-center text-truncate fw-bold">Web Admin</th>
                            <th class="text-center text-truncate fw-bold">Api Secret</th>
                            <th class="text-center text-truncate fw-bold">Api Key</th>
                            <th class="text-center text-truncate fw-bold">Fecha de Registro</th>
                            <th class="text-center text-truncate fw-bold">Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datosfacturify as $ltsfacturify)
                            <tr>
                                <td class="fw-bold">{{ $ltsfacturify->id }}</td>
                                <td>{{ $ltsfacturify->web_admin }}</td>
                                <td>{{ ocultarConAsteriscos($ltsfacturify->api_secret ,0,10)}}</td>
                                <td>{{ ocultarConAsteriscos($ltsfacturify->api_key,0,10) }}</td>
                                <td>{{ $ltsfacturify->fecha_registro }}</td>
                                <td>
                                    <a href="/Sistemas/detallefacturacion/{{ $ltsfacturify->id }}"
                                        class="btn btn-primary"><i class="fa-solid fa-pen fs-8"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
@endif
    </div>



</div>

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection
