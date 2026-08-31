@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-map-pin"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/almacenes" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Almacenes
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Ubicaciones</h2>
                        <p class="text-muted mb-0">Gestión de ubicaciones del almacén</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <a href="/pinsertaubi/{{$id}}" class="btn btn-baseColor">
                        <i class="fa-solid fa-plus"></i> Nueva Ubicación
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                    <tr>
                        <th class="text-center fw-bold text-truncate">No.</th>
                        <th class="text-center fw-bold text-truncate">Folio Interno</th>
                        <th class="text-center fw-bold text-truncate">Descripcion</th>
                        <th class="text-center fw-bold text-truncate">Espacio</th>
                        <th class="text-center fw-bold text-truncate">Nivel</th>
                        <th class="text-center fw-bold text-truncate">Ubicación</th>
                        <th class="text-center fw-bold text-truncate">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ubicaciones as $ubi)
                        <tr>
                            <td class="text-center">{{$ubi->id_ubicacion}}</td>
                            <td class="text-center">{{$ubi->folio_interno}}</td>
                            <td class="text-center">{{$ubi->descripcion}}</td>
                            <td class="text-center">{{$ubi->espacio}}</td>
                            <td class="text-center">{{$ubi->nivel}}</td>
                            <td class="text-center">{{$ubi->ubicacion}}</td>
                            <td class="text-truncate">
                                <a href="/pactualizaubi/{{$id}}/{{$ubi->id_ubicacion}}" class="btn btn-primary border-0 m-0">
                                    <i class="fa-solid fa-pen fs-8"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
@endsection
