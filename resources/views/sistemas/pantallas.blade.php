@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}?v={{ @filemtime(public_path('css/vistas.css')) ?: time() }}" rel="stylesheet">

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-desktop"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Permisos por pantallas</h2>
                        <p class="text-muted mb-0">Asignar permisos a un usuario por pantallas.</p>
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
            <h5 class="text-secondary">Asignación de permisos</h5>
        </div>
        <div class="card-body">
        <form action="/Sistemas/guardar_permisos">
            @csrf
            <div class="row mb-3">
                <div class="form-outline">
                    <label for="">Usuario</label>
                    <select class="form-select select2 fs-6" name="idusuario" id="idusuario" required>
                        <option value="">Seleccionar empleado... </option>
                            @foreach ($varlistausers as $usuario)
                            <option value="{{ $usuario->id }}">{{ $usuario->name }}- {{ $usuario->Nombre }}
                                ({{ $usuario->puesto }})
                            </option>
                            @endforeach
                    </select>
                    <div class="valid-feedback">¡Se ve bien!</div>
                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                </div>
            </div>

            <div class="p-2 mb-3">
                <div class="row">
                    {{-- Tarjeta de departemento --}}
                    <div class="col">
                        @php($vardepartamento = 'null')
                        @php($varvista = 'null')
                        @foreach ($varpermiso as $datos)
                            @if ($vardepartamento == 'null' || $vardepartamento != $datos->nombre_departamento)
                                <div class="col pantalla-depto-block" data-departamento="{{ $datos->nombre_departamento }}">
                                    <input type="checkbox" class="btn-check d-none"
                                        id="departamento{{ $datos->nombre_departamento }}" autocomplete="off"
                                        name='vistas[{{ $datos->iddepartamento }}]' value="{{ $datos->iddepartamento }}">
                                    <label class="btn btn-outline-primary fs-8"
                                        for="departamento{{ $datos->nombre_departamento }}"><i
                                            class="fa-brands fa-slack"></i> {{ $datos->nombre_departamento }}</label>
                                </div>
                            @endif
                            @if ($vardepartamento == $datos->nombre_departamento && $varvista != $datos->nombre_vista)
                            @endif
                            <div class="accordion pantalla-vista-block" id="{{ $datos->iddepartamento }}" data-idvista="{{ $datos->idvista }}">
                                <div class="accordion-item border-0">
                                    @if ($varvista == 'null' || $varvista != $datos->nombre_vista)
                                        <h2 class="accordion-header" id="vistaHeading{{ $datos->idvista }}">
                                            <button class="accordion-button accordion_bg" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#vista{{ $datos->idvista }}"
                                                aria-expanded="true" aria-controls="collapse{{ $datos->idvista }}">
                                                <input type="checkbox" class="btn-check d-nome" id="vistas1{{ $datos->idvista }}"
                                                    autocomplete="off" name='vistas[{{ $datos->idvista }}]'
                                                    value="{{ $datos->idvista }}">
                                                <label class="btn btn-outline-primary fs-8"
                                                    for="vistas1{{ $datos->idvista }}">{{ $datos->nombre_vista }}</label>
                                            </button>
                                        </h2>
                                    @endif
                                    @php($vardepartamento = $datos->nombre_departamento)
                                    @php($varvista = $datos->nombre_vista)
                                    <div id="vista{{ $datos->idvista }}" class="accordion-collapse collapse"
                                        aria-labelledby="vistaHeading{{ $datos->idvista }}"
                                        data-bs-parent="#Permisos{{ $datos->iddepartamento }}">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <div class="col col-lg-2">
                                                    <input type="checkbox" class="btn-check d-none"
                                                        id="acciones{{ $datos->idacciones }}" autocomplete="off"
                                                        name='caja[{{ $datos->nombre_accion }}]'
                                                        value="{{ $datos->idacciones }}">
                                                    <label class="btn btn-outline-primary rounded-5 fs-8"
                                                        for="acciones{{ $datos->idacciones }}">{{ $datos->descripcion_accion }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="row mt-2 mb-3 justify-content-center">
                <button type="submit" class="col-md-3 btn btn-baseColor">
                    <i class="fa-solid fa-check"></i> Guardar permisos
                </button>
            </div>
        </form>
        </div>
    </div>
</div>

<script>
    function aplicarFiltroVistasCatalogo(data) {
        const catalogoActivo = !!(data && data.catalogo_activo);
        const permitidas = (data && Array.isArray(data.vistas_permitidas))
            ? data.vistas_permitidas.map(String)
            : null;

        document.querySelectorAll('.pantalla-vista-block').forEach(function (item) {
            const id = String(item.getAttribute('data-idvista') || '');
            const visible = !catalogoActivo || permitidas === null || permitidas.includes(id);
            item.classList.toggle('is-hidden-catalogo', !visible);
            item.querySelectorAll('input').forEach(function (input) {
                if (!visible) input.checked = false;
            });
        });
    }

    async function cargarCatalogoUsuario(idUsuario) {
        document.querySelectorAll('.pantalla-vista-block').forEach(function (item) {
            item.classList.remove('is-hidden-catalogo');
        });
        if (!idUsuario) return;

        try {
            const response = await fetch('/Sistemas/usuario_asignaciones/' + encodeURIComponent(idUsuario), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('No se pudo cargar el catálogo');
            aplicarFiltroVistasCatalogo(await response.json());
        } catch (error) {
            console.error(error);
        }
    }

    $('#idusuario').on('change', function () {
        cargarCatalogoUsuario(this.value);
    });
</script>
@endsection
