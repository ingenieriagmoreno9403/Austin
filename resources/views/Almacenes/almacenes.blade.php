@extends('layouts.app')
@section('content')
<div class="container-fluid content_page">
    <div class="row">
        <div class="col-lg-6 col-6 start-center">
            <h3 class="mt-1 animate__animated animate__backInLeft">Almacenes</h3>
            <span class="p-0 m-0 d-none d-md-block fs-8">Administración y Alta de Almacenes.</span>
        </div>

        <div class="col-lg-6 col-6 text-end">
            <a href="/pinsertaalm" class="btn btn-baseColor">
                <i class="fa-solid fa-plus"></i> Nuevo
            </a>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                    <tr>
                        <th></th>
                        <th class="text-truncate">Herramientas</th>
                        <th class="text-truncate">No.</th>
                        <th class="text-truncate">Folio Interno</th>
                        <th class="text-truncate">Tipo Almacen</th>
                        <th class="text-truncate">Ciudad</th>
                        <th class="text-truncate">Encargado</th>
                        <th class="text-truncate">Estado</th>
                        <th class="text-truncate">Dirección</th>
                        <th class="text-truncate">Codigo postal</th>
                        <th class="text-truncate">Telefono</th>
                        <th class="text-truncate">Correo electronico</th>
                        <th class="text-truncate">Contacto</th>
                        <th class="text-truncate">Capacidad</th>
                        <th class="text-truncate">Comentarios</th>
                        
                    </tr>
                </thead>

                <tbody>
                @foreach($Listadoalmacenes as $listado)
                        <tr>
                            <td></td>
                            <td class="text-truncate">     
                                <a href="/pactualizaalmacen/{{$listado->id}}" class="btn btn-primary">
                                    <i class="fa-solid fa-pen"></i> 
                                </a>

                                <a href="/ubicaciondet/{{$listado->id}}" class="btn btn-success">
                                    <i class="fa-solid fa-location-dot fs-8"></i>
                                </a>
                            </td>
                            <td>{{$listado->id}}</td>
                            <td>{{$listado->folio_interno}}</td>
                            <td>{{$listado->tipo_almacen}}</td>
                            <td>{{$listado->ciudad}}</td>
                            <td class="text-truncate">{{$listado->nombre_encargado}}</td>
                            <td>
                                @if ($listado->estado == 'A')
                                    <span class="badge badge-success fw-normal fs-9"> Activo</span>
                                @elseif($listado->estado == 'C')
                                    <span class="badge badge-secondary fw-normal fs-9"> Cancelado</span>
                                @elseif($listado->estado == 'I')
                                    <span class="badge badge-danger fw-normal fs-9"> Inactivo</span>
                                @endif
                            </td>
                            <td>{{$listado->direccion}}</td>
                            <td>{{$listado->codigo_postal}}</td>
                            <td>{{$listado->telefono}}</td>
                            <td>{{$listado->correo_electronico}}</td>
                            <td>{{$listado->contacto}}</td>
                            <td>{{$listado->capacidad}}</td>
                            <td>{{$listado->comentarios}}</td>
                            
                        </tr>		
                    @endforeach		
                </tbody>
            </table>
        </div>
    </div>
   
</div>

<script src="{{ asset('js/table.js') }}"></script>
@endsection
