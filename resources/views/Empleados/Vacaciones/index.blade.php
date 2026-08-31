@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-umbrella-beach"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('verempleados') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Empleados
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Vacaciones</h2>
                        <p class="text-muted mb-0">Alta e historial de vacaciones aplicadas</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @include('Empleados.partials.contratos-por-vencer-campana')
                    <a class="btn btn-baseColor fs-7" href="{{ route('vacaciones_alta') }}">
                        <i class="fa-solid fa-plus"></i> Nueva
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-wrapper">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle display modern-table w-100" id="table">
                    <thead>
                        <tr>
                            <th class="text-truncate">Nombre</th>
                            <th class="text-truncate">Descripción</th>
                            <th class="text-truncate">Fecha solicitud</th>
                            <th class="text-truncate">Fecha inicio</th>
                            <th class="text-truncate">Fecha fin</th>
                            <th class="text-truncate text-center">Días</th>
                            <th class="text-truncate">Autorizó</th>
                            <th class="text-truncate">Opciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($varvacaciones as $item)
                            <tr>
                                <td>{{ $item->NombreEmpleado }}</td>

                                <td class="text-start text-truncate">
                                    <p class="btn border-0 text-truncate m-0 fs-8" style="max-width: 150px;" data-bs-toggle="modal" data-bs-target="#descripcionModal{{ $item->id }}">
                                        <i class="fa-solid fa-circle-info text-primary"></i>&nbsp;
                                        {{ $item->descripcion }}
                                    </p>
                                </td>

                                <td>{{ date("d/m/Y", strtotime($item->fecha_solicitante)) }}</td>
                                <td>{{ date("d/m/Y", strtotime($item->fecha_inicio)) }}</td>
                                <td>{{ date("d/m/Y", strtotime($item->fecha_fin)) }}</td>
                                <td class="text-center">
                                    <span class="badge badge-primary-dark fs-9">{{ $item->dias_solicitados ?? 0 }}</span>
                                </td>
                                <td>{{ $item->NombreAutorizo }}</td>

                                <td class="text-truncate text-nowrap">
                                    @if ($item->ruta_evidencia)
                                        <a class="btn btn-primary m-0" title="Comprobante" href="/DetallesEmpleados/vacaciones/{{ $item->id_empleado }}/{{ $item->ruta_evidencia }}" target="_blank">
                                            <i class="fa-solid fa-file fs-8"></i>
                                        </a>
                                    @endif

                                    <button class="btn btn-danger m-0" type="button" title="Eliminar" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $item->id }}">
                                        <i class="fa-solid fa-trash fs-8"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@foreach ($varvacaciones as $item)
    <div class="modal fade" id="cancelModal{{ $item->id }}" tabindex="-1" aria-labelledby="exampleModalLabel{{ $item->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body text-center">
                    <h4 class="text-dark">¿Está seguro que desea eliminar este registro de vacaciones?</h4>
                </div>

                <form class="row p-3 pt-0 justify-content-center" action="{{ route('vacaciones_eliminar', $item->id) }}">
                    <input name="id_empleado" value="{{ $item->id_empleado }}" hidden required>
                    <input type="text" name="ruta_evidencia" value="{{ $item->ruta_evidencia }}" hidden>

                    <button class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0" type="submit" title="Eliminar vacación">
                        <i class="fa-solid fa-check"></i> Aceptar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="descripcionModal{{ $item->id }}" tabindex="-1" aria-labelledby="exampleModalLabel{{ $item->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h6>Descripción</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body text-center">
                    <p class="text-dark" style="text-align: justify">{{ $item->descripcion }}</p>
                </div>
            </div>
        </div>
    </div>
@endforeach

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>

@include('Empleados.partials.contratos-por-vencer-offcanvas')
@endsection
