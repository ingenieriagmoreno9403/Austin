@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/inventario" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Inventarios
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Ubicaciones de {{ ucwords(strtolower($nombre_almacen)) }}</h2>
                        <p class="text-muted mb-0">Catálogo de ubicaciones del almacén</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if($permisos1 == "crear_ubicacion")
                        <a href="/pinsertaubi/{{$id}}/{{$nombre_almacen}}" class="btn btn-baseColor">
                            <i class="fa-solid fa-plus"></i> Nueva Ubicación
                        </a>
                    @else
                        <button disabled class="btn btn-baseColor">
                            <i class="fa-solid fa-plus"></i> Nueva Ubicación
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
                        <th class="text-center fw-bold text-truncate">Opciones</th>
                        <th class="text-center fw-bold text-truncate">No.</th>
                        <th class="text-center fw-bold text-truncate">Folio Interno</th>
                        <th class="text-center fw-bold text-truncate">Descripcion</th>
                        <th class="text-center fw-bold text-truncate">Espacio</th>
                        <th class="text-center fw-bold text-truncate">Nivel</th>
                        <th class="text-center fw-bold text-truncate">Ubicación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ubicaciones as $ubi)
                        <tr>
                            <td class="text-truncate">
                                @if($permisos3 == "ver_productosxubi")
                                    <a href="/detalleubi/{{$id}}/{{$ubi->id_ubicacion}}/{{$nombre_almacen}}" class="btn btn-success">
                                        <i class="fa-solid fa-eye fs-8"></i>
                                    </a>
                                @else
                                    <button disabled class="btn btn-success">
                                        <i class="fa-solid fa-eye fs-8"></i>
                                    </button>
                                @endif

                                @if($permisos2 == "edit_ubicacion")
                                    <a href="/pactualizaubi/{{$id}}/{{$ubi->id_ubicacion}}/{{$nombre_almacen}}" class="btn btn-primary border-0 m-0">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </a>
                                @else
                                    <button disabled class="btn btn-primary border-0 m-0">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                @endif
                            </td>
                            <td class="text-center">{{$ubi->id_ubicacion}}</td>
                            <td class="text-center">{{$ubi->folio_interno}}</td>
                            <td class="text-center">
                                @if (strlen($ubi->descripcion) > 30)
                                    <div class="divtooltip">
                                        {{ substr($ubi->descripcion,0,30) }} ... <i class="text-secondary fa-solid fa-info-circle"></i>
                                        <span class="tooltiptext">{{ $ubi->descripcion }}</span>
                                    </div>
                                @else
                                    {{ $ubi->descripcion }}
                                @endif
                            </td>
                            <td class="text-center">{{$ubi->espacio}}</td>
                            <td class="text-center">{{$ubi->nivel}}</td>
                            <td class="text-center">{{$ubi->ubicacion}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($ubicaciones as $ubi)
    <div class="modal fade" id="Modalcomentario{{$ubi->id_ubicacion}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row justify-content-center">
                        <div class="col-11">
                            <h6 class="text-dark mb-4">Descripción</h6>
                            <p class="text-dark text-wrap fw-normal fs-8" style="text-align: justify!important">
                                {{$ubi->descripcion}}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<script src="{{ asset('js/table.js') }}"></script>
@endsection
