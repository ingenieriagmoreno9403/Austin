@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Editar permisos de usuario</h2>
                        <p class="text-muted mb-0">
                            {{ $varusuario->Nombre ?? 'Usuario seleccionado' }}
                            @if (!empty($varusuario->name))
                                <span class="text-muted">({{ $varusuario->name }})</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-baseColor-light fs-7 mb-2" href="/Sistemas/Usuarios">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="usuario-permisos-info-card">
                <span class="usuario-permisos-info-card__icon">
                    <i class="fa-solid fa-id-card"></i>
                </span>
                <div>
                    <p class="usuario-permisos-info-card__label">Usuario</p>
                    <p class="usuario-permisos-info-card__value">{{ $varusuario->Nombre ?? 'Sin nombre' }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="usuario-permisos-info-card">
                <span class="usuario-permisos-info-card__icon usuario-permisos-info-card__icon--green">
                    <i class="fa-solid fa-id-badge"></i>
                </span>
                <div>
                    <p class="usuario-permisos-info-card__label">Perfiles asignados</p>
                    <p class="usuario-permisos-info-card__value">{{ $varperfilesUser->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="usuario-permisos-info-card">
                <span class="usuario-permisos-info-card__icon usuario-permisos-info-card__icon--orange">
                    <i class="fa-solid fa-list-check"></i>
                </span>
                <div>
                    <p class="usuario-permisos-info-card__label">Acciones directas</p>
                    <p class="usuario-permisos-info-card__value">{{ $varlistuseracc->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
        <div class="card-header text-start bg-body border-0 pb-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-id-badge me-2"></i>Perfiles asignados
            </h5>
            <p class="text-muted fs-8 mb-0">Quita los perfiles que ya no debe tener este usuario.</p>
        </div>
        <div class="card-body">
            @if ($varperfilesUser->isNotEmpty())
                <div class="row g-3">
                    @foreach ($varperfilesUser as $perfil)
                        <div class="col-xl-4 col-lg-6">
                            <div class="usuario-perfil-card">
                                <div class="usuario-perfil-card__body">
                                    <span class="usuario-perfil-card__icon">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </span>
                                    <div class="flex-grow-1 min-w-0">
                                        <h6 class="usuario-perfil-card__title">{{ $perfil->nombre }}</h6>
                                        <p class="usuario-perfil-card__desc">
                                            {{ $perfil->descripcion ?: 'Sin descripción' }}
                                        </p>
                                        <span class="usuario-perfil-card__meta">
                                            {{ $perfil->total_acciones }} {{ $perfil->total_acciones == 1 ? 'acción' : 'acciones' }}
                                        </span>
                                    </div>
                                </div>
                                <button class="btn btn-danger fs-8 w-100 mt-3" type="button"
                                    data-bs-toggle="modal" data-bs-target="#perfilModal{{ $perfil->id }}">
                                    <i class="fa-solid fa-trash"></i> Quitar perfil
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info border-0 rounded-4 mb-0">
                    <i class="fa-solid fa-circle-info me-2"></i>Este usuario no tiene perfiles asignados.
                </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow p-3 mt-2 bg-body rounded-5">
        <div class="card-header text-start bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-list-check me-2"></i>Acciones directas
            </h5>
            <p class="text-muted fs-8 mb-0">Acciones asignadas directamente al usuario, independientes de perfiles.</p>
        </div>
        <div class="card-body">
    <div class="row">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                    <tr>
                        <th class="text-center text-truncate fw-bold">Nombre Acción</th>
                        <th class="text-center text-truncate fw-bold">Nombre Vista</th>
                        <th class="text-center text-truncate fw-bold">Nombre Departamento</th>
                        <th class="text-center text-truncate fw-bold">Fecha creación</th>
                        <th class="text-center text-truncate fw-bold">Usuario</th>
                        <th class="text-center text-truncate fw-bold">Herramienta</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($varlistuseracc as $datos)
                        <tr>
                            <td>{{ $datos->descripcion_accion }}</td>
                            <td>{{ $datos->nombre_vista }}</td>
                            <td>{{ $datos->nombre_departamento }}</td>
                            <td>
                                {{ $datos->created_at && $datos->created_at !== '0000-00-00 00:00:00' 
                                    ? \Carbon\Carbon::parse($datos->created_at)->format('d/m/Y') 
                                    : '' }}
                            </td>                                    
                            <td>{{ $datos->created_by }}</td>
                            <td>
                                <button class="btn btn-danger" type="button" data-bs-toggle="modal"
                                    data-bs-target="#cancelModal{{ $datos->id }}">
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
</div>

@foreach ($varlistuseracc as $datos)
    <!-- Modal -->
    <div class="modal fade" id="cancelModal{{ $datos->id }}" tabindex="-1"
        aria-labelledby="exampleModalLabel{{ $datos->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title text-dark fs-5" id="exampleModalLabel{{ $datos->id }}">Eliminar acciones</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body text-center">
                    <h5 class="text-dark">¿Está seguro que desea eliminar esta acción?</h5>
                </div>

                <div class="container">
                    <div class="row p-3">
                        <button type="button" class="col btn btn-secondary fs-8 rounded-1 m-2 mt-0"  data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i>  Cerrar</button>
                        <a  href="/Sistemas/eliminar_acciones_user/{{ $datos->id }}" class="col-9 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Aceptar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

@foreach ($varperfilesUser as $perfil)
    <div class="modal fade" id="perfilModal{{ $perfil->id }}" tabindex="-1"
        aria-labelledby="perfilModalLabel{{ $perfil->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title text-dark fs-5" id="perfilModalLabel{{ $perfil->id }}">
                        Quitar perfil
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body text-center">
                    <h5 class="text-dark">¿Está seguro que desea quitar el perfil?</h5>
                    <p class="text-muted mb-0">{{ $perfil->nombre }}</p>
                </div>

                <div class="container">
                    <div class="row p-3">
                        <button type="button" class="col btn btn-secondary fs-8 rounded-1 m-2 mt-0"
                            data-bs-dismiss="modal">
                            <i class="fa-solid fa-xmark"></i> Cerrar
                        </button>
                        <a href="/Sistemas/eliminar_perfil_user/{{ $perfil->id }}"
                            class="col-9 btn btn-baseColor fs-8 rounded-1 m-2 mt-0">
                            <i class="fa-solid fa-check"></i> Aceptar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach


<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection
