@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Usuarios y permisos</h2>
                        <p class="text-muted mb-0">Consulta usuarios, perfiles asignados y accesos de edición.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-baseColor-light fs-7 mb-2" href="/Panel">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mt-2 bg-body rounded-5">
        <div class="card-header text-start bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-users-gear me-2"></i>Listado de usuarios
            </h5>
            <p class="text-muted fs-8 mb-0">Entra a editar para revisar acciones directas y quitar perfiles asignados.</p>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="table-responsive">
                    <table class="table table-stripped table-hover display align-middle" id="table">
                        <thead>
                            <tr class="table-light text-secondary">
                                <th class="text-center text-truncate fw-bold">No.</th>
                                <th class="text-truncate fw-bold">Usuario</th>
                                <th class="text-center text-truncate fw-bold">Estado</th>
                                <th class="text-truncate fw-bold">Puesto</th>
                                <th class="text-truncate fw-bold">Sucursal</th>
                                <th class="text-truncate fw-bold">Perfiles</th>
                                <th class="text-center text-truncate fw-bold">Editar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($varlistausers as $user)
                                @php($perfilesUsuario = $perfilesPorUsuario->get($user->id, collect()))
                                <tr>
                                    <td class="fw-bold text-center">{{ $user->id }}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $user->Nombre }}</div>
                                        <small class="text-muted">{{ $user->name }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($user->estado_user == "A")
                                            <span class="badge badge-success-dark fs-9">Activa</span>
                                        @endif
                                        @if($user->estado_user == "I")
                                            <span class="badge badge-danger-dark fs-9">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->puesto }}</td>
                                    <td>{{ $user->sucursal }}</td>
                                    <td>
                                        @forelse ($perfilesUsuario as $perfil)
                                            <span class="usuario-perfil-chip">
                                                <i class="fa-solid fa-id-badge"></i>{{ $perfil->nombre }}
                                            </span>
                                        @empty
                                            <span class="text-muted fs-8">Sin perfiles asignados</span>
                                        @endforelse
                                    </td>
                                    <td class="text-center">
                                        <a href="/Sistemas/UsuarioPermisos/{{ $user->id }}"
                                            class="btn btn-baseColor fs-8">
                                            <i class="fa-solid fa-pen"></i> Editar
                                        </a>
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

<script src="{{ asset('js/table.js') }}"></script>
@endsection
