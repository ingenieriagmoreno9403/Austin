@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Acciones registradas</h2>
                        <p class="text-muted mb-0">Añadir acción y listado de estas.</p>
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

    

    <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
        <div class="card-header bg-body border-0 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h5 class="text-secondary mb-1">
                        <i class="fa-solid fa-layer-group me-2"></i>Administrar altas
                    </h5>
                    <p class="text-muted fs-8 mb-0">Muestra u oculta el formulario que necesitas capturar.</p>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="accordion acciones-admin-accordion" id="accionesPerfilAccordion">
                <div class="accordion-item acciones-collapse-card acciones-collapse-card--departamento rounded-4 mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingDepartamento">
                        <button class="accordion-button acciones-collapse-button" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseDepartamento" aria-expanded="true"
                            aria-controls="collapseDepartamento">
                            <span class="acciones-collapse-title">
                                <span class="acciones-collapse-icon acciones-collapse-icon--departamento">
                                    <i class="fa-solid fa-building"></i>
                                </span>
                                <span>
                                    <span class="acciones-collapse-name">Departamento</span>
                                    <span class="acciones-collapse-help">Alta de departamentos para organizar vistas y permisos.</span>
                                </span>
                            </span>
                        </button>
                    </h2>
                    <div id="collapseDepartamento" class="accordion-collapse collapse"
                        aria-labelledby="headingDepartamento" data-bs-parent="#accionesPerfilAccordion">
                        <div class="accordion-body acciones-collapse-body">
                            <form action="/Sistemas/guardar_departamento" method="POST"
                                class="g-3 needs-validation form modern-form" novalidate>
                                @csrf
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-3 col-12">
                                        <label class="form-label" for="departamentoNombre">Nombre del departamento</label>
                                        <div class="form-outline">
                                            <input type="text" class="form-control" placeholder="Ej. Sistemas"
                                                name="nombre" id="departamentoNombre" maxlength="50" required />
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-12">
                                        <label class="form-label" for="departamentoDescripcion">Descripción</label>
                                        <div class="form-outline">
                                            <input type="text" class="form-control" placeholder="Descripción del departamento"
                                                name="descripcion" id="departamentoDescripcion" maxlength="100" required />
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-12">
                                        <label class="form-label" for="departamentoOrden">Orden de aparición</label>
                                        <input type="number" step="any" class="form-control" placeholder="Ej. 1"
                                            name="orden" id="departamentoOrden" min="1" step="1" required />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="col-lg-2 col-12">
                                        <label class="form-label" for="departamentoEmpresa">Empresa</label>
                                        <select name="id_empresa" id="departamentoEmpresa" class="form-select" required>
                                            <option value="">Seleccionar empresa...</option>
                                            @foreach ($varlistaempresas as $empresa)
                                                <option value="{{ $empresa->id }}">{{ $empresa->nombre_empresa }}</option>
                                            @endforeach
                                        </select>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="col-lg-2 col-12">
                                        <label class="form-label" for="departamentoIcon">Icono</label>
                                        <input type="text" class="form-control" placeholder="fa-building"
                                            name="icon" id="departamentoIcon" maxlength="50" />
                                    </div>

                                    <div class="col-12 text-end">
                                        <button class="btn btn-baseColor fs-6" type="submit">
                                            <i class="fa-solid fa-check"></i> Añadir departamento
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="accordion-item acciones-collapse-card acciones-collapse-card--vista rounded-4 mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingVista">
                        <button class="accordion-button collapsed acciones-collapse-button" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseVista" aria-expanded="false"
                            aria-controls="collapseVista">
                            <span class="acciones-collapse-title">
                                <span class="acciones-collapse-icon acciones-collapse-icon--vista">
                                    <i class="fa-solid fa-desktop"></i>
                                </span>
                                <span>
                                    <span class="acciones-collapse-name">Vista</span>
                                    <span class="acciones-collapse-help">Alta de pantallas dentro de un departamento.</span>
                                </span>
                            </span>
                        </button>
                    </h2>
                    <div id="collapseVista" class="accordion-collapse collapse"
                        aria-labelledby="headingVista" data-bs-parent="#accionesPerfilAccordion">
                        <div class="accordion-body acciones-collapse-body">
                            <form action="/Sistemas/guardar_vista" method="POST"
                                class="g-3 needs-validation form modern-form" novalidate>
                                @csrf
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-3 col-12">
                                        <label class="form-label" for="vistaNombre">Nombre de la vista</label>
                                        <input type="text" class="form-control" placeholder="Ej. Acciones registradas"
                                            name="nombre" id="vistaNombre" maxlength="50" required />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="col-lg-3 col-12">
                                        <label class="form-label" for="vistaDescripcion">Nombre Ruta</label>
                                        <input type="text" class="form-control" placeholder="Nombre de la ruta"
                                            name="descripcion" id="vistaDescripcion" maxlength="100" required />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="col-lg-3 col-12">
                                        <label class="form-label" for="vistaOrden">Orden de aparición</label>
                                        <input type="number" step="any" class="form-control" placeholder="Ej. 1"
                                            name="orden" id="vistaOrden" min="1" step="1" required />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="col-lg-3 col-12">
                                        <label class="form-label" for="vistaDepartamento">Departamento</label>
                                        <select name="iddepartamento" id="vistaDepartamento" class="form-select select2" required>
                                            <option value="">Seleccionar departamento...</option>
                                            @foreach ($varlistadepas as $depa)
                                                <option value="{{ $depa->id }}">{{ $depa->nombre }}</option>
                                            @endforeach
                                        </select>
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="col-12 text-end">
                                        <button class="btn btn-baseColor fs-6" type="submit">
                                            <i class="fa-solid fa-check"></i> Añadir vista
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="accordion-item acciones-collapse-card acciones-collapse-card--accion rounded-4 mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="headingAccion">
                        <button class="accordion-button collapsed acciones-collapse-button" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseAccion" aria-expanded="false"
                            aria-controls="collapseAccion">
                            <span class="acciones-collapse-title">
                                <span class="acciones-collapse-icon acciones-collapse-icon--accion">
                                    <i class="fa-solid fa-bolt"></i>
                                </span>
                                <span>
                                    <span class="acciones-collapse-name">Acción</span>
                                    <span class="acciones-collapse-help">Alta de permisos o movimientos disponibles por vista.</span>
                                </span>
                            </span>
                        </button>
                    </h2>
                    <div id="collapseAccion" class="accordion-collapse collapse"
                        aria-labelledby="headingAccion" data-bs-parent="#accionesPerfilAccordion">
                        <div class="accordion-body acciones-collapse-body">
                            <form method="POST" action="/Sistemas/guardar_acciones"
                                class="g-3 needs-validation form modern-form" enctype="multipart/form-data" id="frm" novalidate>
                                @csrf
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-3 col-12">
                                        <div class="form-outline text-truncate">
                                            <label class="form-label" for="accionDescripcion">Nombre</label>
                                            <input type="text" id="accionDescripcion" name="descripcion" class="form-control"
                                                maxlength="45" placeholder="Nombre" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-12 text-truncate">
                                        <div class="form-outline">
                                            <label class="form-label" for="accionNombre">Nombre Identificador</label>
                                            <input type="text" id="accionNombre" name="nombre" class="form-control" maxlength="50"
                                                placeholder="Nombre Identificador" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-12">
                                        <div class="form-outline">
                                            <label class="form-label" for="accionVista">Vista</label>
                                            <select name="idvista" id="accionVista" class="form-select select2" required>
                                                <option value="">Seleccionar vista... </option>
                                                @foreach ($varlistavistas as $vis)
                                                    <option value="{{ $vis->id }}">{{ $vis->nombre }}
                                                        ({{ $vis->departamento }})
                                                    </option>
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

                                    <div class="col-lg-2 col-12 text-truncate">
                                        <button type="submit" class="fs-6 btn btn-baseColor w-100 text-truncate btn-moderno">
                                            <i class="fas fa-check"></i> Aplicar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="accordion-item acciones-collapse-card acciones-collapse-card--perfil rounded-4 overflow-hidden">
                    <h2 class="accordion-header" id="headingPerfil">
                        <button class="accordion-button collapsed acciones-collapse-button" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapsePerfil" aria-expanded="false"
                            aria-controls="collapsePerfil">
                            <span class="acciones-collapse-title">
                                <span class="acciones-collapse-icon acciones-collapse-icon--perfil">
                                    <i class="fa-solid fa-id-badge"></i>
                                </span>
                                <span>
                                    <span class="acciones-collapse-name">Perfil</span>
                                    <span class="acciones-collapse-help">Creación de perfiles para agrupar acciones y permisos.</span>
                                </span>
                            </span>
                        </button>
                    </h2>
                    <div id="collapsePerfil" class="accordion-collapse collapse"
                        aria-labelledby="headingPerfil" data-bs-parent="#accionesPerfilAccordion">
                        <div class="accordion-body acciones-collapse-body">
                            <form action="/Sistemas/nuevo_perfil" method="POST" class="g-3 needs-validation form modern-form" novalidate>
                                @csrf
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-5 col-12">
                                        <label class="form-label" for="perfilNombre">Nombre del perfil</label>
                                        <div class="form-outline">
                                            <input type="text" class="form-control" placeholder="Ej. Administrador"
                                                name="nombre" id="perfilNombre" maxlength="20" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-5 col-12">
                                        <label class="form-label" for="perfilDescripcion">Descripción</label>
                                        <div class="form-outline">
                                            <input type="text" class="form-control" placeholder="Breve descripción del perfil"
                                                name="descripcion" id="perfilDescripcion" maxlength="50" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-2 col-12">
                                        <button class="btn btn-baseColor fs-6 w-100" type="submit">
                                            <i class="fa-solid fa-check"></i> Añadir
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    </div>

    <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
        <div class="card-header text-start bg-body border-0">
            <h5 class="text-secondary">Registro de Elementos</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="table-responsive">
                    <table class="table table-stripped table-hover display" id="table">
                        <thead>
                            <tr class="table-light text-secondary">
                                <th class="text-center text-truncate fw-bold">No.</th>
                                <th class="text-center text-truncate fw-bold">Acción</th>
                                <th class="text-center text-truncate fw-bold">Identificador</th>
                                <th class="text-center text-truncate fw-bold">Vista</th>
                                <th class="text-center text-truncate fw-bold">Departamento</th>
                                <th class="text-center text-truncate fw-bold">Creación</th>
                                <th class="text-center text-truncate fw-bold">Herramienta</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            @foreach ($varacciones as $datos)
                                <tr>
                                    <td class="table-light fw-bold">{{ $datos->id }}</td>
                                    <td>{{ $datos->descripcion_accion }}</td>
                                    <td>{{ $datos->nombre_accion }}</td>
                                    <td>{{ $datos->vista }}</td>
                                    <td>{{ $datos->nombre_departamento }}</td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($datos->created_at)->format('d/m/Y') }}</br>
                                        {{ $datos->created_by }}
                                    </td>
                                    <td>
                                        <a href="/Sistemas/eliminar_acciones/{{ $datos->id }}"
                                            class="btn btn-danger border-0 m-0"><i class="fa-solid fa-trash fs-8"></i>
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
<script src="{{ asset('js/validaPDF.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
@endsection
