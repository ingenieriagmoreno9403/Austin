@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('warningGuardar'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡Saldo Insuficiente!", text: "Este perfil ya tiene estas cualidades, no es posible repetir"});';
            echo '</script>'; 
    @endphp
@endif

<link href="{{ asset('css/vistas.css') }}?v={{ @filemtime(public_path('css/vistas.css')) ?: time() }}" rel="stylesheet">

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-id-badge"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Gestión de perfiles</h2>
                        <p class="text-muted mb-0">Asignar perfiles a usuarios.</p>
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

    @php
        $accionesPorPerfil = collect($varaccionesdePerfiles)->groupBy('id');
    @endphp

    <div class="row">
        <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
            <div class="card-header bg-body border-0 pb-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-user-check me-2"></i>Asignar perfil a usuario
                </h5>
                <p class="text-muted fs-8 mb-0">Selecciona el usuario; se marcarán sus perfiles ya asignados. Al desmarcar un perfil y guardar, se quita ese acceso.</p>
            </div>

            <form action="/Sistemas/guardar_perfil" method="POST" class="needs-validation form modern-form" novalidate id="formAsignarPerfil">
                @csrf
                <div class="card-body">
                    <div class="mb-4">
                        <label class="perfil-section-label" for="idusuario">
                            <i class="fa-solid fa-user"></i> Usuario
                        </label>
                        <select class="form-select select2" name="idusuario" id="idusuario" required>
                            <option value="">Seleccionar usuario...</option>
                            @foreach ($varlistausers as $usuario)
                                <option value="{{ $usuario->id }}"
                                    data-empresa="{{ $usuario->id_empresa ?? '' }}"
                                    data-empresa-nombre="{{ $usuario->empresa ?? '' }}">
                                    {{ $usuario->name }} — {{ $usuario->Nombre }}
                                    ({{ $usuario->puesto }}) · {{ $usuario->sucursal }}
                                </option>
                            @endforeach
                        </select>
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Selecciona un usuario.</div>
                    </div>

                    <div class="row g-3 align-items-stretch perfil-asignar-layout">
                        <div class="col-12">
                            <label class="perfil-section-label d-block">
                                <i class="fa-solid fa-id-badge"></i> Perfiles
                            </label>
                            <p class="perfil-catalogo-hint" id="perfilCatalogoHint"></p>
                            <div class="perfil-picker" id="perfilPicker">
                                @foreach ($varperfiles as $perfiles)
                                    @php
                                        $accionesDelPerfil = $accionesPorPerfil->get($perfiles->id, collect());
                                        $totalAcciones = $accionesDelPerfil->count();
                                    @endphp
                                    <div class="perfil-picker__item" data-perfil-id="{{ $perfiles->id }}">
                                        <label class="perfil-picker__card" for="perfil{{ $perfiles->id }}">
                                            <input class="perfil-picker__input" type="checkbox" name="perfil[]"
                                                id="perfil{{ $perfiles->id }}" value="{{ $perfiles->id }}">
                                            <div class="perfil-picker__head">
                                                <span class="perfil-picker__icon">
                                                    <i class="fa-solid fa-shield-halved"></i>
                                                </span>
                                                <div class="flex-grow-1 min-w-0">
                                                    <p class="perfil-picker__title text-truncate">{{ $perfiles->nombre }}</p>
                                                    <p class="perfil-picker__meta">
                                                        {{ $totalAcciones }} {{ $totalAcciones === 1 ? 'acción' : 'acciones' }}
                                                    </p>
                                                </div>
                                                <span class="perfil-picker__check">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </span>
                                            </div>
                                        </label>
                                        @if ($totalAcciones > 0)
                                            <button type="button" class="perfil-picker__toggle"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#perfilAcciones{{ $perfiles->id }}"
                                                aria-expanded="false">
                                                <i class="fa-solid fa-chevron-down"></i>
                                                Ver permisos incluidos
                                            </button>
                                            <div class="collapse" id="perfilAcciones{{ $perfiles->id }}">
                                                <div class="perfil-picker__acciones">
                                                    <ul class="list-unstyled mb-0">
                                                        @foreach ($accionesDelPerfil as $perAcc)
                                                            <li>
                                                                {{ $perAcc->descripcion_accion }}
                                                                <span class="dept">({{ $perAcc->nombreDepartamento }})</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        @else
                                            <p class="perfil-picker__meta mb-0 ps-1">Sin acciones asignadas aún.</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-body border-0 pt-0">
                    <div class="text-end">
                        <button class="btn btn-baseColor fs-6" type="submit">
                            <i class="fa-solid fa-check"></i> Aplicar perfil
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
<script>
    function syncPerfilCards() {
        document.querySelectorAll('.perfil-picker__card').forEach(function(card) {
            const input = card.querySelector('.perfil-picker__input');
            card.classList.toggle('is-selected', input && input.checked);
        });
    }

    function aplicarFiltroCatalogo(data) {
        const hint = document.getElementById('perfilCatalogoHint');
        const catalogoActivo = !!(data && data.catalogo_activo);
        const permitidos = (data && Array.isArray(data.perfiles_permitidos))
            ? data.perfiles_permitidos.map(String)
            : null;
        const asignados = (data && data.perfiles) ? data.perfiles.map(String) : [];
        const empresaNombre = (data && data.empresa) ? data.empresa : '';

        document.querySelectorAll('.perfil-picker__item').forEach(function(item) {
            const id = String(item.getAttribute('data-perfil-id') || '');
            const visible = !catalogoActivo || permitidos === null
                || permitidos.includes(id)
                || asignados.includes(id);
            item.classList.toggle('is-hidden-catalogo', !visible);
        });

        if (hint) {
            if (catalogoActivo) {
                hint.classList.add('is-visible');
                hint.innerHTML = empresaNombre
                    ? '<i class="fa-solid fa-filter me-1"></i>Solo se listan los perfiles vendidos a <strong>' + empresaNombre + '</strong>.'
                    : '<i class="fa-solid fa-filter me-1"></i>Solo se listan los perfiles habilitados para la empresa de este usuario.';
            } else {
                hint.classList.remove('is-visible');
                hint.textContent = '';
            }
        }
    }

    function limpiarAsignaciones() {
        document.querySelectorAll('.perfil-picker__input').forEach(function(input) {
            input.checked = false;
        });
        document.querySelectorAll('.perfil-picker__item').forEach(function(item) {
            item.classList.remove('is-hidden-catalogo');
        });
        const hint = document.getElementById('perfilCatalogoHint');
        if (hint) {
            hint.classList.remove('is-visible');
            hint.textContent = '';
        }
        syncPerfilCards();
    }

    function aplicarAsignaciones(data) {
        const perfiles = (data && data.perfiles) ? data.perfiles.map(String) : [];

        document.querySelectorAll('.perfil-picker__input').forEach(function(input) {
            input.checked = perfiles.includes(String(input.value));
        });

        aplicarFiltroCatalogo(data);
        syncPerfilCards();
    }

    async function cargarAsignacionesUsuario(idUsuario) {
        if (!idUsuario) {
            limpiarAsignaciones();
            return;
        }

        try {
            const response = await fetch('/Sistemas/usuario_asignaciones/' + encodeURIComponent(idUsuario), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('No se pudieron cargar las asignaciones');
            }

            const data = await response.json();
            aplicarAsignaciones(data);
        } catch (error) {
            console.error(error);
            limpiarAsignaciones();
        }
    }

    document.querySelectorAll('.perfil-picker__input').forEach(function(input) {
        input.addEventListener('change', syncPerfilCards);
    });

    syncPerfilCards();

    $('#idusuario').on('change', function() {
        cargarAsignacionesUsuario(this.value);
    });

</script>
@endsection
