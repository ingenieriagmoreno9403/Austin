@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-car"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Tipos de Vehículos</h2>
                        <p class="text-muted mb-0">Gestión y control del catálogo de tipos de vehículos</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevoTipoVehiculo">
                        <i class="fa-solid fa-plus"></i> Nuevo Tipo de Vehículo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                    <tr class="table-light">
                        <th class="text-center fw-normal text-truncate">Tipo de Vehiculo</th>
                        <th class="text-center fw-normal text-truncate">Estado</th>
                        <th class="text-center fw-normal text-truncate">Herramientas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tiposVehiculos as $item)
                        <tr>
                            <td class="text-truncate">{{ $item->tipo_vehiculo }}</td>
                            <td class="text-truncate">
                                @if($item->estado == "A")
                                    <span class="badge badge-success-dark fs-9">Activo</span>
                                @else
                                    <span class="badge badge-danger-dark fs-9">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                @if ($item->estado != 'I')
                                    <button class="btn btn-primary border-0 m-0" type="button" data-bs-toggle="modal" data-bs-target="#modalEditarTipoVehiculo{{ $item->id }}">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                    <button class="btn btn-danger m-0" type="button" data-bs-toggle="modal" data-bs-target="#modalEliminarTipoVehiculo{{ $item->id }}">
                                        <i class="fa-solid fa-trash fs-8"></i>
                                    </button>
                                @else
                                    <button class="btn btn-primary border-0 m-0" type="button" disabled>
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                    <button class="btn btn-danger m-0" type="button" disabled>
                                        <i class="fa-solid fa-trash fs-8"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @foreach($tiposVehiculos as $item)
        <div class="modal fade" id="modalEditarTipoVehiculo{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-dark">Editar Tipo de Vehiculo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form action="/CatalogoGeneral/TiposVehiculos/EditarTipoVehiculo/{{ $item->id }}" method="POST" class="form g-3 needs-validation mt-1 text-start" novalidate>
                        @csrf
                        <div class="modal-body p-4 pt-0">
                            <div class="row mb-2">
                                <div class="form-outline mb-2">
                                    <label class="form-label">Tipo de Vehiculo</label>
                                    <input type="text" class="form-control text" name="tipo_vehiculo" maxlength="80" value="{{ $item->tipo_vehiculo }}" required />
                                </div>

                                <div class="form-outline">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" name="estado" required>
                                        <option value="A" {{ $item->estado == 'A' ? 'selected' : '' }}>Activo</option>
                                        <option value="I" {{ $item->estado == 'I' ? 'selected' : '' }}>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="container">
                            <div class="row justify-content-center p-3">
                                <button type="submit" class="col-6 btn btn-orange fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalEliminarTipoVehiculo{{ $item->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title">Eliminar Tipo de Vehiculo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form action="/CatalogoGeneral/TiposVehiculos/EliminarTipoVehiculo/{{ $item->id }}" method="POST" class="form g-3 needs-validation text-start" novalidate>
                        @csrf
                        <div class="modal-body mb-3">
                            <h6 class="text-break">¿Que desea realizar con "{{ $item->tipo_vehiculo }}"?</h6>
                            <div class="for-outline">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="eliminar" value="desactivar" checked>
                                    <label class="form-check-label">Desactivar</label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="eliminar" value="eliminar">
                                    <label class="form-check-label">Eliminar definitivamente</label>
                                </div>
                            </div>
                        </div>

                        <div class="container">
                            <div class="row justify-content-center p-3">
                                <button type="submit" class="col-6 btn btn-orange fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="modalNuevoTipoVehiculo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Alta de Tipo de Vehiculo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="/CatalogoGeneral/TiposVehiculos/NuevoTipoVehiculo" method="POST" class="g-3 form needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="container">
                            <div class="row mb-4">
                                <div class="form-outline">
                                    <label class="form-label">Tipo de Vehiculo</label>
                                    <input type="text" class="form-control text" name="tipo_vehiculo" maxlength="80" required />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container">
                        <div class="row justify-content-center p-3">
                            <button type="submit" class="col-6 btn btn-orange fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
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

