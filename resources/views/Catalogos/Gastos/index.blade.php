@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Gastos</h2>
                        <p class="text-muted mb-0">Control de empresas con respecto a gastos</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if ($permisos1 == 'nuevo_gasto')
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#ModalNuevoGasto">
                            <i class="fa-solid fa-plus"></i> Nuevo Gasto
                        </button>
                    @else
                        <button class="btn btn-baseColor" disabled type="button">
                            <i class="fa-solid fa-plus"></i> Nuevo Gasto
                        </button>
                    @endif

                    @if ($permisos4 == 'exportar_gasto')
                        <button class="btn btn-baseColor-light" type="button" data-bs-toggle="modal" data-bs-target="#ModalExportarGastos">
                            <i class="fa-solid fa-file-excel"></i> Exportar
                        </button>
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
                        <th class="text-center fw-bold text-truncate">Nombre</th>
                        <th class="text-center fw-bold text-truncate">Estado</th>
                        <th class="text-center fw-bold text-truncate">% IVA</th>
                        <th class="text-center fw-bold text-truncate">% RET IVA</th>
                        <th class="text-center fw-bold text-truncate">% RET ISR</th>
                        <th class="text-center fw-bold text-truncate">% RET ISR RESICO</th>
                        <th class="text-center fw-bold text-truncate">Tipo</th>
                        <th class="text-center fw-bold text-truncate">Modalidad</th>
                        <th class="text-center fw-bold text-truncate">Dirigido</th>
                        <th class="text-center fw-bold text-truncate">Opciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($varGastos as $gastos)
                        <tr>
                            <td class=" text-start fw-normal">{{ $gastos->nombre }}</td>

                            <td>
                                @if ($gastos->estado == 'A')
                                    <span class="badge badge-success-dark fs-9">Activa</span>
                                @else
                                    <span class="badge badge-danger-dark fs-9">Cancelada</span>
                                @endif
                            </td>

                            <td class=" text-center fw-normal">{{ $gastos->iva }}</td>
                            <td class=" text-center fw-normal">{{ $gastos->ret_iva }}</td>
                            <td class=" text-center fw-normal">{{ $gastos->ret_isr }}</td>
                            <td class=" text-center fw-normal">{{ $gastos->ret_isr_resico }}</td>
                            <td class="">{{ $gastos->rubro }}</td>

                            <td class="text-truncate">
                                @if ($gastos->tipo == 'E')
                                    <span class="badge badge-success fs-9">Efectivo</span>
                                @else
                                    <span class="badge badge-success fs-9">Banco</span>
                                @endif
                            </td>

                            <td>{{ $gastos->nombre_empresa }}</td>
                            <td class=" text-truncate">
                                @if ($permisos2 == 'editar_gasto')
                                    <button class="btn btn-primary border-0 m-0" type="button" data-bs-toggle="modal"  data-bs-target="#ModalEditar{{ $gastos->id }}">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                @else
                                    <button class="btn btn-primary border-0 m-0" type="button" disabled>
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                @endif

                                @if ($permisos3 == 'eliminar_gasto')
                                    <button class="btn btn-danger m-0" type="button" data-bs-toggle="modal" data-bs-target="#eliminarGasto{{ $gastos->id }}">
                                        <i class="fa-solid fa-ban fs-8"></i>
                                    </button>
                                @else
                                    <button class="btn btn-secondary m-0" type="button" disabled>
                                        <i class="fa-solid fa-ban fs-8"></i>
                                    </button>
                                @endif
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
       
    @foreach ($varGastos as $gastos)
        <!-- Modal Editar Gasto -->
        <div class="modal fade" id="ModalEditar{{ $gastos->id }}" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-dark" id="exampleModalLabel">Editar Gasto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="/CatalogoGeneral/Gastos/EditarGasto/{{ $gastos->id }}" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
                        @csrf
                        <div class="modal-body">
                            <div class="container">
                                <div class="row mb-2">
                                    <div class="form-outline text-start">
                                        <label class="form-label text-dark fs-8" for="form8Example4">Nombre</label>
                                        <input type="text" class="form-control text" name="nombre"
                                            value="{{ $gastos->nombre }}" id="nombre" maxlength="50" required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>


                                <div class="p-2"
                                    style="border: 1.5px solid rgb(222, 222, 222);border-radius:5px;">
                                    <div class="row">
                                        <div class="col form-outline text-start mb-3">
                                            <label class="form-label text-dark fs-8" for="form8Example4">% IVA</label>
                                            <input type="text" class="form-control text" name="iva"
                                                id="iva" value="{{ $gastos->iva }}" maxlength="12" />
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>

                                        <div class="col form-outline text-start mb-3">
                                            <label class="form-label text-dark fs-8" for="form8Example4">% RET
                                                IVA</label>
                                            <input type="text" class="form-control text" name="ret_iva"
                                                id="ret_iva" value="{{ $gastos->ret_iva }}" maxlength="12"/>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>

                                        <div class="col form-outline text-start mb-3">
                                            <label class="form-label text-dark fs-8" for="form8Example4">% RET
                                                ISR</label>
                                            <input type="text" class="form-control text" name="ret_isr"
                                                id="ret_isr" value="{{ $gastos->ret_isr }}" maxlength="12"/>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>

                                        <div class="col form-outline text-start mb-3">
                                            <label class="form-label text-dark fs-8" for="form8Example4">% RET ISR
                                                RESICO</label>
                                            <input type="text" class="form-control text" name="ret_isr_resico"
                                                id="ret_isr_resico" value="{{ $gastos->ret_isr_resico }}"
                                                maxlength="12"/>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col">
                                            <div class="row  mb-2 text-start">
                                                <div class="form-outline">
                                                    <label class="form-label text-dark"
                                                        for="form8Example4">Dirigido</label>
                                                    <select name="empresa" id="empresa" class=" form-select text"
                                                        required>
                                                        @foreach ($varempresa as $empresa)
                                                            @if ($gastos->id_empresa == $empresa->id)
                                                                @if ($empresa->efectivo == 1)
                                                                    <option value="{{ $empresa->id }}" selected>
                                                                        <b>{{ $empresa->id }} -
                                                                        </b>{{ $empresa->nombre_empresa }} (Efectivo)
                                                                    </option>
                                                                @else
                                                                    <option value="{{ $empresa->id }}" selected>
                                                                        <b>{{ $empresa->id }} -
                                                                        </b>{{ $empresa->nombre_empresa }}
                                                                    </option>
                                                                @endif
                                                            @else
                                                                @if ($empresa->efectivo == 1)
                                                                    <option value="{{ $empresa->id }}">
                                                                        <b>{{ $empresa->id }} -
                                                                        </b>{{ $empresa->nombre_empresa }} (Efectivo)
                                                                    </option>
                                                                @else
                                                                    <option value="{{ $empresa->id }}">
                                                                        <b>{{ $empresa->id }} -
                                                                        </b>{{ $empresa->nombre_empresa }}
                                                                    </option>
                                                                @endif
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

                                            <div class="row mb-2 text-start">
                                                <label class="form-label text-dark fs-8"
                                                    for="form8Example4">Tipo</label>
                                                <div class="form-check" style="margin-left: 20px;">
                                                    @if ($gastos->rubro == 'ADMINISTRATIVO')
                                                        <input class="form-check-input" type="radio" name="rubro"
                                                            name="rubro" value="ADMINISTRATIVO"
                                                            id="flexRadioDefault1" required checked>
                                                    @else
                                                        <input class="form-check-input" type="radio" name="rubro"
                                                            name="rubro" value="ADMINISTRATIVO"
                                                            id="flexRadioDefault1" required>
                                                    @endif
                                                    <label class="form-check-label" for="flexRadioDefault1">
                                                        Administrativo
                                                    </label>
                                                </div>

                                                <div class="form-check" style="margin-left: 20px;">
                                                    @if ($gastos->rubro == 'OPERATIVO')
                                                        <input class="form-check-input" type="radio" name="rubro"
                                                            name="rubro" value="OPERATIVO" id="flexRadioDefault1"
                                                            required checked>
                                                    @else
                                                        <input class="form-check-input" type="radio" name="rubro"
                                                            name="rubro" value="OPERATIVO" id="flexRadioDefault1"
                                                            required>
                                                    @endif
                                                    <label class="form-check-label" for="flexRadioDefault1">
                                                        Operativo
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="row mb-2">
                                                <div class="form-outline text-start">
                                                    <label class="form-label text-dark fs-8"
                                                        for="form8Example4">Efectivo </label>
                                                    <div class="form-check form-switch">
                                                        @if ($gastos->tipo == 'E')
                                                            <input class="form-check-input"
                                                                style="width: 45px!important;height: 18px!important;"
                                                                type="checkbox" value="1" role="switch"
                                                                name="tipo" id="tipo" checked>
                                                        @else
                                                            <input class="form-check-input"
                                                                style="width: 45px!important;height: 18px!important;"
                                                                type="checkbox" value="1" role="switch"
                                                                name="tipo" id="tipo">
                                                        @endif
                                                    </div>
                                                    <div class="valid-feedback">
                                                        ¡Se ve bien!
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        Por favor, completa la información requerida.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="form-outline text-start">
                                                    <label class="form-label text-dark fs-8"
                                                        for="form8Example4">Estado</label>
                                                    <div class="form-check form-switch">
                                                        @if ($gastos->estado == 'A')
                                                            <input class="form-check-input"
                                                                style="width: 45px!important;height: 18px!important;"
                                                                type="checkbox" value="1" role="switch"
                                                                name="estado" id="estado" checked>
                                                        @else
                                                            <input class="form-check-input"
                                                                style="width: 45px!important;height: 18px!important;"
                                                                type="checkbox" value="1" role="switch"
                                                                name="estado" id="estado">
                                                        @endif
                                                    </div>
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

        <!-- Modal Eliminar Gasto -->
        <div class="modal fade" id="eliminarGasto{{$gastos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-dark" id="exampleModalLabel">¿Qué desea hacer?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <form action="/CatalogoGeneral/Gastos/EliminarGasto/{{$gastos->id}}" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
                        @csrf
                        <div class="modal-body">
                            <div class="container">
                                <div class="row mb-2">
                                    <div class="text-start">
                                        <div class="form-check">
                                        <input class="form-check-input" style="margin-top:5px;" type="radio" value="borrar" name="eliminaraccion" id="eliminaraccion" required>
                                        <label class="form-check-label text-dark" style="font-size: 15px!important;" for="eliminaraccion">
                                            Borrar Definitivamente
                                        </label>
                                            <div class="valid-feedback">
                                            ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                            </div>
                                        </div>

                                        <div class="form-check">
                                        @if ($gastos->estado == 'A')
                                        <input class="form-check-input" type="radio" style="margin-top:5px;" value="inactivar" name="eliminaraccion" id="eliminaraccion" required>
                                        @else
                                        <input class="form-check-input" type="radio" style="margin-top:5px;" value="inactivar" name="eliminaraccion" id="eliminaraccion" disabled>
                                        @endif
                                        <label class="form-check-label text-dark" style="font-size: 15px!important;" for="eliminaraccion">
                                            Inactivar Completamente
                                        </label>
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
    @endforeach

    <!-- Modal Nuevo Gasto -->
    <div class="modal fade" id="ModalNuevoGasto" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-dark" id="exampleModalLabel">Nuevo Gasto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/CatalogoGeneral/Gastos/NuevoGasto" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="container">
                            <div class="row mb-4">
                                <div class="form-outline text-start">
                                    <label class="form-label text-dark fs-8" for="form8Example4">Nombre</label>
                                    <input type="text" class="form-control text" name="nombre" id="nombre"
                                        maxlength="50" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="p-2" style="border: 1.5px solid rgb(231, 231, 231);border-radius:5px;">
                                <div class="row">
                                    <div class="col-lg-2 col-12 form-outline text-start">
                                        <label class="form-label text-dark fs-8" for="form8Example4">% IVA</label>
                                        <input type="text" class="form-control text" name="iva"
                                            id="iva" maxlength="12" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-12 form-outline text-start">
                                        <label class="form-label text-dark fs-8" for="form8Example4">% RET IVA</label>
                                        <input type="text" class="form-control text" name="ret_iva"
                                            id="ret_iva" maxlength="12" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-12 form-outline text-start">
                                        <label class="form-label text-dark fs-8" for="form8Example4">% RET ISR</label>
                                        <input type="text" class="form-control text" name="ret_isr"
                                            id="ret_isr" maxlength="12" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-12 form-outline text-start">
                                        <label class="form-label text-dark fs-8" for="form8Example4">% RET ISR
                                            RESICO</label>
                                        <input type="text" class="form-control text" name="ret_isr_resico"
                                            id="ret_isr_resico" maxlength="12" />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-4 col-12 text-start">
                                        <div class="form-outline">
                                            <label class="form-label text-dark" for="form8Example4">Dirigido</label>
                                            <select name="empresa" id="empresa" class=" form-select text" required>
                                                <option value="" selected>Selecciona ...</option>
                                                @foreach ($varempresa as $empresa)
                                                    @if ($empresa->efectivo == 1)
                                                        <option value="{{ $empresa->id }}"><b>{{ $empresa->id }} -
                                                            </b>{{ $empresa->nombre_empresa }} (Efectivo)</option>
                                                    @else
                                                        <option value="{{ $empresa->id }}"><b>{{ $empresa->id }} -
                                                            </b>{{ $empresa->nombre_empresa }}</option>
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

                                    <div class="col-lg-4 col-12 text-start">
                                        <label class="form-label text-dark fs-8" for="form8Example4">Tipo</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="rubro"
                                                name="rubro" value="ADMINISTRATIVO" id="flexRadioDefault1"
                                                required>
                                            <label class="form-check-label" for="flexRadioDefault1">
                                                Administrativo
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="rubro"
                                                name="rubro" value="OPERATIVO" id="flexRadioDefault1" required>
                                            <label class="form-check-label" for="flexRadioDefault1">
                                                Operativo
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-12">
                                        <div class="form-outline text-start">
                                            <label class="form-label text-dark fs-8" for="form8Example4">Efectivo
                                            </label>
                                            <div class="form-check form-switch" style="margin-left: 5px;">
                                                <input class="form-check-input"
                                                    style="width: 45px!important;height: 18px!important;"
                                                    type="checkbox" value="1" role="switch" name="tipo"
                                                    id="tipo">
                                            </div>
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

    <!-- Modal Exportar Gasto -->
    <div class="modal fade" id="ModalExportarGastos" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-dark" id="exampleModalLabel">Exportar Catálogo de Gastos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/CatalogoGeneral/Gastos/ExportarGasto" method="POST" enctype="multipart/form-data"
                    class="g-3 form needs-validation mt-1" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="container">
                            <div class="row mb-4">
                                <div class="form-outline">
                                    <label class="form-label text-dark" for="form8Example4">Por Empresa</label>
                                    <select name="empresa" id="empresa" class=" form-select text" required>
                                        <option value="0" selected>Todas</option>
                                        @foreach ($varempresa as $empresa)
                                            @if ($empresa->efectivo == 1)
                                                <option value="{{ $empresa->id }}"><b>{{ $empresa->id }} -
                                                    </b>{{ $empresa->nombre_empresa }} (Efectivo)</option>
                                            @else
                                                <option value="{{ $empresa->id }}"><b>{{ $empresa->id }} -
                                                    </b>{{ $empresa->nombre_empresa }}</option>
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
</div>

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection
