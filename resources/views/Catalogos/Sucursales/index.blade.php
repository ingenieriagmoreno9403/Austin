@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Sucursales</h2>
                        <p class="text-muted mb-0">Control y gestión de sucursales</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if ($permisos1 == 'nueva_sucursal')
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#ModalNuevaSucursal">
                            <i class="fa-solid fa-plus"></i> Nueva Sucursal
                        </button>
                    @else
                        <button class="btn btn-baseColor" disabled type="button">
                            <i class="fa-solid fa-plus"></i> Nueva Sucursal
                        </button>
                    @endif

                    @if ($permisos4 == 'exportar_sucursal')
                        <a class="btn btn-baseColor-light" href="/CatalogoGeneral/Gastos/ExportarSucursales">
                            <i class="fa-solid fa-file-excel"></i> Exportar
                        </a>
                    @else
                        <button class="btn btn-baseColor-light" disabled type="button">
                            <i class="fa-solid fa-file-excel"></i> Exportar
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                    <tr>
                        <th class="text-truncate">Nombre</th>
                        <th class="text-truncate">Estado</th>
                        <th class="text-truncate">Telefono</th>
                        <th class="text-truncate">Estado</th>
                        <th class="text-truncate">Ciudad</th>
                        <th class="text-truncate">Empresa</th>
                        <th class="text-truncate">Opciones</th>
                        <th class="text-truncate">Dirección</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($varsucursales as $sucursal)
                        <tr class="boder-sec">
                            <td class="text-truncate">{{ $sucursal->nombre }}</td>
                            <td>
                                @if ($sucursal->status == 'A')
                                    <span class="badge badge-success-dark fs-9">Activa</span>
                                @elseif($sucursal->status == 'I')
                                    <span class="badge badge-secondary-dark fs-9">Inactivo</span>
                                @endif
                            </td>

                            <td>{{ $sucursal->telefono }}</td>
                            <td>{{ $sucursal->estado }}</td>
                            <td>{{ $sucursal->ciudad }}</td>
                           
                            <td class="text-truncate">
                                @if ($sucursal->efectivo == 1)
                                    <i class=" fs-9 text-orange fa-solid fa-dollar-sign"></i>&nbsp; &nbsp;
                                    {{ $sucursal->nombre_empresa }}
                                @else
                                    <i class=" fs-9 text-orange fa-solid fa-building-columns"></i>&nbsp; &nbsp;
                                    {{ $sucursal->nombre_empresa }}
                                @endif
                            </td>
                            
                            <td class="text-truncate">
                                @if ($permisos2 == 'editar_sucursal')
                                    <button class="btn btn-primary border-0 m-0" type="button" data-bs-toggle="modal"  data-bs-target="#ModalEditar{{ $sucursal->id }}">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                @else
                                    <button class="btn btn-primary border-0 m-0" type="button" disabled>
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                @endif

                                @if ($sucursal->status == 'A')
                                    <button class="btn btn-danger m-0" type="button" data-bs-toggle="modal" data-bs-target="#ModalBorrar{{ $sucursal->id }}">
                                        <i class="fa-solid fa-ban fs-8"></i>
                                    </button>
                                @else
                                    <button class="btn btn-secondary m-0" type="button" disabled>
                                        <i class="fa-solid fa-ban fs-8"></i>
                                    </button>
                                @endif
                            </td>

                            <td class="text-truncate">Col. {{ $sucursal->colonia }}, calle
                                {{ $sucursal->calle }} No. Int {{ $sucursal->numero_interior }} - No. Ext
                                {{ $sucursal->numero_exterior }} C.P. {{ $sucursal->codigo_postal }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach ($varsucursales as $sucursal)
    <!-- Modal Editar Sucursal -->
    <div class="modal fade" id="ModalEditar{{ $sucursal->id }}" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="exampleModalLabel">Editar Sucursal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="/CatalogoGeneral/Sucursales/EditarSucursal/{{ $sucursal->id }}"  method="POST" enctype="multipart/form-data"  class="g-3 form needs-validation" novalidate>
                    @csrf

                    <div class="modal-body">
                        <div class="container">
                            <div class="row mb-2">
                                <div class="col-md-6 col-12">
                                    <div class="form-outline">
                                        <label class="form-label text-dark"
                                            for="form8Example4">Nombre de
                                            Sucursal</label>
                                        <input type="text" class="form-control text"
                                            name="nombre" id="nombre"
                                            value="{{ $sucursal->nombre }}" maxlength="60"
                                            required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-outline">
                                        <label class="form-label text-dark"
                                            for="form8Example4">Telefono</label>
                                        <input type="text" class="form-control text"
                                            name="telefono" id="telefono"
                                            value="{{ $sucursal->telefono }}" maxlength="10"
                                            required />
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
                                <div class="col-md-6 col-12">
                                    <div class="form-outline">
                                        <label class="form-label text-dark"
                                            for="form8Example4">Ciudad</label>
                                        <select class="form-select" name="ciudad"
                                            id="ciudad" required>
                                            @foreach ($obtenerCiudades as $ciudades)
                                                @if ($ciudades->id == $sucursal->idciudad)
                                                    <option value="{{ $sucursal->idciudad }}"
                                                        selected>
                                                        {{ $sucursal->ciudad }}
                                                    </option>
                                                @else
                                                    <option value="{{ $ciudades->id }}">
                                                        {{ $ciudades->nombre }}</option>
                                                @endif
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

                                <div class="col-md-6 col-12">
                                    <div class="form-outline">
                                        <label class="form-label text-dark"
                                            for="form8Example4">Empresa</label>
                                        <select class="form-select" name="empresa"
                                            id="empresa" required>
                                            @foreach ($obtenerempresas as $empresa)
                                                @if ($empresa->id == $sucursal->idempresa)
                                                    <option value="{{ $empresa->id }}"
                                                        selected>
                                                        {{ $empresa->nombre_empresa }}
                                                    </option>
                                                @else
                                                    <option value="{{ $empresa->id }}">
                                                        {{ $empresa->nombre_empresa }}</option>
                                                @endif
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

                            <div class="row mb-2">
                                <div class="col-12">
                                    <div class="form-outline">
                                        <label class="form-label text-dark fs-8"
                                            for="form8Example4">Colonia</label>
                                        <input type="text" class="form-control text"
                                            name="colonia" id="colonia"
                                            value="{{ $sucursal->colonia }}" maxlength="60"
                                            required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-outline">
                                        <label class="form-label text-dark fs-8"
                                            for="form8Example4">Calle</label>
                                        <input type="text" class="form-control text"
                                            name="calle"
                                            id="calle"value="{{ $sucursal->calle }}"
                                            maxlength="60" required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-6 col-12">
                                    <div class="form-outline">
                                        <label class="form-label text-dark fs-8"
                                            for="form8Example4">Numero
                                            Interior</label>
                                        <input type="text" class="form-control text"
                                            name="noInt"
                                            id="noInt"value="{{ $sucursal->numero_interior }}"
                                            maxlength="10" />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-outline">
                                        <label class="form-label text-dark fs-8"
                                            for="form8Example4">Numero
                                            Exterior</label>
                                        <input type="text" class="form-control text"
                                            name="noExt" id="noExt"
                                            value="{{ $sucursal->numero_exterior }}"
                                            maxlength="10" required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <div class="form-outline">
                                        <label class="form-label text-dark fs-8"
                                            for="form8Example4">Codigo
                                            Postal</label>
                                        <input type="text" class="form-control text"
                                            name="codigo_postal" id="codigo_postal"
                                            value="{{ $sucursal->codigo_postal }}"
                                            maxlength="6" required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-12 pt-4">
                                    <input type="hidden" name="activo" value="0">
                                    <div class="col-md-3 col-5">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                role="switch" id="flexSwitchCheckChecked"
                                                name="activo" value="1"
                                                @if ($sucursal->status == 'A') checked @endif>
                                            <label class="form-check-label"
                                                for="flexSwitchCheckChecked">Activar</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container">
                        <div class="row justify-content-center p-3">
                            <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Sucursal -->
    <div class="modal fade" id="ModalBorrar{{ $sucursal->id }}" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="exampleModalLabel">Desactivar Sucursal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="/CatalogoGeneral/Sucursales/EliminarSucursal/{{ $sucursal->id }}" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="container">
                            <div class="row mb-2">
                                <div class="col-md-12 col-12">
                                    <h5 class="text-dark">
                                        ¿Estás seguro de desactivar la sucursal <b class="fs-6 text-secondary">{{ $sucursal->nombre }}</b>?
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container">
                        <div class="row p-3 pt-0">
                            <button type="button" class="col btn btn-secondary fs-8 rounded-1 m-2 mt-0"  data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
                            <button type="submit" class="col-9 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Aplicar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<!-- Modal Nueva Sucursal -->
<div class="modal fade" id="ModalNuevaSucursal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="exampleModalLabel">Alta de Sucursal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/CatalogoGeneral/Sucursales/NuevaSucursal" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="container">
                        <div class="row mb-2">
                            <div class="col-md-6 col-12">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Nombre de Sucursal</label>
                                    <input type="text" class="form-control text" name="nombre"
                                        id="nombre" maxlength="60" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-12">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Telefono</label>
                                    <input type="text" class="form-control text" name="telefono"
                                        id="telefono" maxlength="10" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-md-6 col-12">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Ciudad</label>
                                    <select class="form-select fs-8" name="ciudad" id="ciudad" required>
                                        <option value="">Selecciona...</option>
                                        @foreach ($obtenerCiudades as $ciudades)
                                            <option value="{{ $ciudades->id }}">{{ $ciudades->nombre }}</option>
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

                            <div class="col-md-6 col-12">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Empresa</label>
                                    <select class="form-select" name="empresa" id="empresa" required>
                                        <option value="">Seleccionar...</option>
                                        @foreach ($obtenerempresas as $razonSocial)
                                            @if ($razonSocial->efectivo == 1)
                                                <option value="{{ $razonSocial->id }}">Efectivo -
                                                    {{ $razonSocial->nombre_empresa }}</option>
                                            @else
                                                <option value="{{ $razonSocial->id }}">
                                                    {{ $razonSocial->nombre_empresa }}</option>
                                            @endif
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

                        <div class="row mb-2">
                            <div class="col-md-6 col-12">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Colonia</label>
                                    <input type="text" class="form-control text" name="colonia"
                                        id="colonia" maxlength="60" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-12">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Calle</label>
                                    <input type="text" class="form-control text" name="calle"
                                        id="calle" maxlength="60" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-md-4 col-12">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Numero Interior</label>
                                    <input type="text" class="form-control text" name="noInt"
                                        id="noInt" maxlength="10" />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Numero Exterior</label>
                                    <input type="text" class="form-control text" name="noExt"
                                        id="noExt" maxlength="10" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Codigo Postal</label>
                                    <input type="text" class="form-control text" name="codigo_postal"
                                        id="codigo_postal" maxlength="6" required />
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

                <div class="container">
                    <div class="row justify-content-center p-3">
                        <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection
