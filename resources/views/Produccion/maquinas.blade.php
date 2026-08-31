@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-gears"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="javascript:history.back()" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Catálogo de Máquinas</h2>
                        <p class="text-muted mb-0">Alta, edición e inactivación de máquinas de producción.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-blue fs-7 mb-2" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevaMaquina">
                        <i class="fa-solid fa-plus"></i> Nueva máquina
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-xmark me-2"></i>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
        <div class="card-header text-start bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-list-check me-2"></i>Listado de máquinas
            </h5>
            <p class="text-muted fs-8 mb-0">Seleccione una máquina y use las acciones superiores. Edite o inactiva desde cada fila.</p>
        </div>

        <div class="d-flex flex-wrap gap-2 px-3 pb-3 border-bottom">
            <button type="button" class="btn btn-blue-light fs-8 m-0 btn-accion-maquina"
                data-accion="mantenimiento-preventivo" title="Mantenimiento preventivo">
                <i class="fa-solid fa-calendar-check me-1"></i>Mantenimiento preventivo
            </button>
            <button type="button" class="btn btn-blue-light fs-8 m-0 btn-accion-maquina"
                data-accion="inspecciones-semanales" title="Inspecciones semanales">
                <i class="fa-solid fa-clipboard-check me-1"></i>Inspecciones semanales
            </button>
            <button type="button" class="btn btn-blue-light fs-8 m-0 btn-accion-maquina"
                data-accion="incidencias" title="Incidencias">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>Incidencias
            </button>
            <button type="button" class="btn btn-blue-light fs-8 m-0 btn-accion-maquina"
                data-accion="mantenimientos" title="Mantenimientos">
                <i class="fa-solid fa-screwdriver-wrench me-1"></i>Mantenimientos
            </button>
            <button type="button" class="btn btn-blue-light fs-8 m-0 btn-accion-maquina"
                data-accion="refacciones" title="Refacciones">
                <i class="fa-solid fa-boxes-stacked me-1"></i>Refacciones
            </button>
            <button type="button" class="btn btn-success fs-8 m-0 btn-estado-maquina" data-estado="activar" title="Activar máquina">
                <i class="fa-solid fa-circle-check me-1"></i>Activar
            </button>
            <button type="button" class="btn btn-danger fs-8 m-0 btn-estado-maquina" data-estado="inactivar" title="Inactivar máquina">
                <i class="fa-solid fa-ban me-1"></i>Inactivar
            </button>
        </div>

        <form id="formEstadoMaquina" method="POST" class="d-none">
            @csrf
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover display modern-table w-100 mb-0" id="table">
                <thead id="navbar">
                    <tr class="text-tr">
                        <th class="text-truncate text-center" style="width: 3rem;">Sel.</th>
                        <th class="text-truncate">Código</th>
                        <th class="text-truncate">Nombre</th>
                        <th class="text-truncate">Ubicación planta</th>
                        <th class="text-truncate">Almacén refacciones</th>
                        <th class="text-truncate">Ubicación refacciones</th>
                        <th class="text-truncate">Capacidad</th>
                        <th class="text-truncate">Manual</th>
                        <th class="text-truncate">Reproceso</th>
                        <th class="text-truncate">Estatus</th>
                        <th class="text-truncate">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($maquinas as $maquina)
                        <tr>
                            <td class="text-center align-middle">
                                <input type="checkbox" class="form-check-input maquina-seleccionada" name="maquina_seleccionada"
                                    value="{{ $maquina->id }}" aria-label="Seleccionar {{ $maquina->codigo }}">
                            </td>
                            <td class="text-truncate">{{ $maquina->codigo }}</td>
                            <td class="text-truncate text-start">{{ $maquina->nombre }}</td>
                            <td class="text-truncate">{{ $maquina->ubicacion ?: '—' }}</td>
                            <td class="text-truncate">{{ $maquina->almacen->folio_interno ?? '—' }}</td>
                            <td class="text-truncate">
                                @if ($maquina->ubicacionAlmacen)
                                    {{ $maquina->ubicacionAlmacen->folio_interno }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-truncate">{{ $maquina->capacidad_pr_hora ?? '—' }}</td>
                            <td class="text-truncate">
                                @if ($maquina->ruta_manual)
                                    <a href="{{ asset($maquina->ruta_manual) }}" target="_blank" rel="noopener" class="text-decoration-none">
                                        <i class="fa-solid fa-file-pdf me-1"></i>Ver manual
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-truncate">
                                @if ($maquina->para_reproceso)
                                    <span class="badge bg-warning text-dark fs-9">Triturado/Peletizado</span>
                                @else
                                    <span class="text-muted fs-9">—</span>
                                @endif
                            </td>
                            <td class="text-truncate">
                                @if ($maquina->estatus === 'A')
                                    <span class="badge badge-success-dark fs-9">Activa</span>
                                @elseif ($maquina->estatus === 'I')
                                    <span class="badge badge-danger-dark fs-9">Inactiva</span>
                                @else
                                    <span class="badge badge-secondary fs-9">Mantenimiento</span>
                                @endif
                            </td>
                            <td class="text-truncate">
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    @if ($maquina->estatus === 'A')
                                        <button class="btn btn-primary border-0 m-0" type="button"
                                            data-bs-toggle="modal" data-bs-target="#modalEditarMaquina{{ $maquina->id }}" title="Editar">
                                            <i class="fa-solid fa-pen fs-8"></i>
                                        </button>
                                        <button class="btn btn-danger m-0" type="button"
                                            data-bs-toggle="modal" data-bs-target="#modalInactivarMaquina{{ $maquina->id }}" title="Inactivar">
                                            <i class="fa-solid fa-ban fs-8"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-primary border-0 m-0" type="button" disabled title="Editar">
                                            <i class="fa-solid fa-pen fs-8"></i>
                                        </button>
                                        <button class="btn btn-success m-0" type="button"
                                            data-bs-toggle="modal" data-bs-target="#modalActivarMaquina{{ $maquina->id }}" title="Activar">
                                            <i class="fa-solid fa-circle-check fs-8"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mt-3 mb-2 bg-body rounded-5" id="lista-revision">
        <div class="card-header text-start bg-body border-0 d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Lista de revisión
                </h5>
                <p class="text-muted fs-8 mb-0">
                    Parámetros del checklist de inspecciones semanales. Los activos se usan al registrar una inspección.
                </p>
            </div>
            <button class="btn btn-blue fs-7" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevoParametroRevision">
                <i class="fa-solid fa-plus"></i> Agregar parámetro
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover display modern-table w-100 mb-0" id="table22">
                <thead>
                    <tr class="text-tr">
                        <th style="width: 5rem;">Orden</th>
                        <th>Parámetro / concepto</th>
                        <th style="width: 8rem;">Estatus</th>
                        <th style="width: 10rem;">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($parametrosRevision ?? [] as $parametro)
                        <tr class="{{ $parametro->estatus !== 'A' ? 'table-light text-muted' : '' }}">
                            <td>{{ $parametro->orden }}</td>
                            <td class="text-start">{{ $parametro->nombre }}</td>
                            <td>
                                @if ($parametro->estatus === 'A')
                                    <span class="badge badge-success-dark fs-9">Activo</span>
                                @else
                                    <span class="badge badge-danger-dark fs-9">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <button class="btn btn-primary border-0 m-0 btn-sm" type="button"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarParametro{{ $parametro->id }}"
                                        title="Editar">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>
                                    @if ($parametro->estatus === 'A')
                                        <form method="POST" action="{{ route('produccion.maquinas.lista_revision.inactivar', $parametro->id) }}" class="m-0">
                                            @csrf
                                            <button class="btn btn-danger m-0 btn-sm" type="submit" title="Inactivar"
                                                onclick="return confirm('¿Inactivar este parámetro?');">
                                                <i class="fa-solid fa-ban fs-8"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('produccion.maquinas.lista_revision.activar', $parametro->id) }}" class="m-0">
                                            @csrf
                                            <button class="btn btn-success m-0 btn-sm" type="submit" title="Activar">
                                                <i class="fa-solid fa-circle-check fs-8"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal nuevo parámetro lista de revisión --}}
<div class="modal fade" id="modalNuevoParametroRevision" tabindex="-1" aria-labelledby="modalNuevoParametroRevisionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('produccion.maquinas.lista_revision.store') }}" class="needs-validation" novalidate>
                @csrf
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalNuevoParametroRevisionLabel">
                            <i class="fa-solid fa-circle-plus me-2"></i>Agregar parámetro
                        </h5>
                        <p class="text-muted fs-8 mb-0">Se incluirá en la lista de revisión de inspecciones semanales.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="nombreParametroNuevo">Nombre del parámetro <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombreParametroNuevo" name="nombre"
                            value="{{ old('nombre') }}" maxlength="200" required
                            placeholder="Ej. Nivel de aceite, Estado de rodillos…">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="ordenParametroNuevo">Orden (opcional)</label>
                        <input type="number" class="form-control" id="ordenParametroNuevo" name="orden"
                            value="{{ old('orden') }}" min="0" max="9999" placeholder="Al final si se omite">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-blue-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-blue">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach ($parametrosRevision ?? [] as $parametro)
<div class="modal fade" id="modalEditarParametro{{ $parametro->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('produccion.maquinas.lista_revision.update', $parametro->id) }}" class="needs-validation" novalidate>
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title text-secondary">
                        <i class="fa-solid fa-pen me-2"></i>Editar parámetro
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" value="{{ $parametro->nombre }}" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Orden</label>
                        <input type="number" class="form-control" name="orden" value="{{ $parametro->orden }}" min="0" max="9999">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Estatus</label>
                        <select class="form-select" name="estatus" required>
                            <option value="A" {{ $parametro->estatus === 'A' ? 'selected' : '' }}>Activo</option>
                            <option value="I" {{ $parametro->estatus === 'I' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-blue-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-blue">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- Modal nueva máquina --}}
<div class="modal fade" id="modalNuevaMaquina" tabindex="-1" aria-labelledby="modalNuevaMaquinaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <div>
                    <h5 class="modal-title text-secondary" id="modalNuevaMaquinaLabel">
                        <i class="fa-solid fa-circle-plus me-2"></i>Alta de nueva máquina
                    </h5>
                    <p class="text-muted fs-8 mb-0">Captura los datos generales de la máquina.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="{{ route('produccion.maquinas.store') }}" method="POST" enctype="multipart/form-data"
                class="g-3 modern-form needs-validation" novalidate>
                @csrf
                <div class="modal-body pt-0">
                    <div class="row">
                        <div class="col-md-4 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Código <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text" name="codigo" maxlength="50"
                                    value="{{ old('codigo') }}" required>
                                <div class="invalid-feedback">El código es obligatorio.</div>
                            </div>
                        </div>
                        <div class="col-md-8 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text" name="nombre" maxlength="150"
                                    value="{{ old('nombre') }}" required>
                                <div class="invalid-feedback">El nombre es obligatorio.</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Ubicación en planta</label>
                                <input type="text" class="form-control text" name="ubicacion" maxlength="150"
                                    value="{{ old('ubicacion') }}">
                            </div>
                        </div>
                        <div class="col-md-6 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Capacidad teór. (kg/h)</label>
                                <input type="number" class="form-control text" name="capacidad_pr_hora" min="0" step="0.001"
                                    value="{{ old('capacidad_pr_hora') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-12 mt-2">
                            <label class="form-label">Potencia (kW)</label>
                            <input type="number" class="form-control text" name="potencia_kw" min="0" step="0.001" value="{{ old('potencia_kw') }}">
                        </div>
                        <div class="col-md-3 col-12 mt-2">
                            <label class="form-label">Horas / turno</label>
                            <input type="number" class="form-control text" name="horas_turno" min="0" step="0.5" value="{{ old('horas_turno') }}">
                        </div>
                        <div class="col-md-3 col-12 mt-2">
                            <label class="form-label">Turnos / día</label>
                            <input type="number" class="form-control text" name="turnos_dia" min="1" max="4" step="1" value="{{ old('turnos_dia') }}">
                        </div>
                        <div class="col-md-3 col-12 mt-2">
                            <label class="form-label">Operador responsable</label>
                            <select class="form-select" name="operador_responsable_id">
                                <option value="">— Opcional —</option>
                                @foreach ($empleados ?? [] as $empleado)
                                    <option value="{{ $empleado->id }}" @selected((int) old('operador_responsable_id') === (int) $empleado->id)>
                                        {{ $empleado->primer_nombre }} {{ $empleado->apellido_paterno }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @include('Produccion.partials.campos_almacen_ubicacion')

                    <div class="row">
                        <div class="col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control text" name="descripcion" rows="2">{{ old('descripcion') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="para_reproceso" value="1"
                                    id="paraReprocesoNueva" @checked(old('para_reproceso'))>
                                <label class="form-check-label" for="paraReprocesoNueva">
                                    Usar en <strong>reproceso</strong> (trituradora / peletizadora)
                                </label>
                            </div>
                            <div class="form-text">Solo estas máquinas aparecen al iniciar un lote de reproceso.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label" for="manualNuevaMaquina">Manual de la máquina</label>
                                <input type="file" class="form-control" id="manualNuevaMaquina" name="manual"
                                    accept=".pdf,.doc,.docx">
                                <div class="form-text">Formatos permitidos: PDF, DOC, DOCX. Máximo 20 MB.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-blue">
                        <i class="fa-solid fa-check"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach ($maquinas as $maquina)
    {{-- Modal editar --}}
    <div class="modal fade" id="modalEditarMaquina{{ $maquina->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary">
                            <i class="fa-solid fa-pen me-2"></i>Editar máquina
                        </h5>
                        <p class="text-muted fs-8 mb-0">{{ $maquina->nombre }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form action="{{ route('produccion.maquinas.update', $maquina->id) }}" method="POST"
                    enctype="multipart/form-data" class="g-3 modern-form needs-validation" novalidate>
                    @csrf
                    <div class="modal-body pt-0">
                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Código <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" name="codigo" maxlength="50"
                                        value="{{ $maquina->codigo }}" required>
                                    <div class="invalid-feedback">El código es obligatorio.</div>
                                </div>
                            </div>
                            <div class="col-md-8 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" name="nombre" maxlength="150"
                                        value="{{ $maquina->nombre }}" required>
                                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Ubicación en planta</label>
                                    <input type="text" class="form-control text" name="ubicacion" maxlength="150"
                                        value="{{ $maquina->ubicacion }}">
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Capacidad teór. (kg/h)</label>
                                    <input type="number" class="form-control text" name="capacidad_pr_hora" min="0" step="0.001"
                                        value="{{ $maquina->capacidad_pr_hora }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Potencia (kW)</label>
                                <input type="number" class="form-control text" name="potencia_kw" min="0" step="0.001" value="{{ $maquina->potencia_kw }}">
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Horas / turno</label>
                                <input type="number" class="form-control text" name="horas_turno" min="0" step="0.5" value="{{ $maquina->horas_turno }}">
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Turnos / día</label>
                                <input type="number" class="form-control text" name="turnos_dia" min="1" max="4" step="1" value="{{ $maquina->turnos_dia }}">
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Operador responsable</label>
                                <select class="form-select" name="operador_responsable_id">
                                    <option value="">— Opcional —</option>
                                    @foreach ($empleados ?? [] as $empleado)
                                        <option value="{{ $empleado->id }}" @selected((int) $maquina->operador_responsable_id === (int) $empleado->id)>
                                            {{ $empleado->primer_nombre }} {{ $empleado->apellido_paterno }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @include('Produccion.partials.campos_almacen_ubicacion', [
                            'suffix' => '_' . $maquina->id,
                            'idAlmacen' => $maquina->id_almacen,
                            'idUbicacion' => $maquina->id_ubicacion,
                        ])

                        <div class="row">
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control text" name="descripcion" rows="2">{{ $maquina->descripcion }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="para_reproceso" value="1"
                                        id="paraReprocesoEdit{{ $maquina->id }}" @checked($maquina->para_reproceso)>
                                    <label class="form-check-label" for="paraReprocesoEdit{{ $maquina->id }}">
                                        Usar en <strong>reproceso</strong> (trituradora / peletizadora)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Manual de la máquina</label>
                                    @if ($maquina->ruta_manual)
                                        <div class="form-text mb-2">
                                            Archivo actual:
                                            <a href="{{ asset($maquina->ruta_manual) }}" target="_blank" rel="noopener">
                                                {{ basename($maquina->ruta_manual) }}
                                            </a>
                                        </div>
                                    @else
                                        <div class="form-text mb-2 text-muted">Sin manual cargado.</div>
                                    @endif
                                    <input type="file" class="form-control" name="manual" accept=".pdf,.doc,.docx">
                                    <div class="form-text">Deja vacío para conservar el archivo actual.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-blue">
                            <i class="fa-solid fa-floppy-disk"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal inactivar --}}
    @if ($maquina->estatus === 'A')
        <div class="modal fade" id="modalInactivarMaquina{{ $maquina->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-secondary">Inactivar máquina</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form action="{{ route('produccion.maquinas.inactivar', $maquina->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="mb-0">¿Deseas inactivar la máquina <strong>{{ $maquina->nombre }}</strong>?</p>
                            <p class="text-muted fs-8 mb-0">Úselo cuando la máquina esté en mantenimiento o descompuesta.</p>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-ban"></i> Inactivar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal activar --}}
    @if ($maquina->estatus !== 'A')
        <div class="modal fade" id="modalActivarMaquina{{ $maquina->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title text-secondary">Activar máquina</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form action="{{ route('produccion.maquinas.activar', $maquina->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p class="mb-0">¿Deseas activar la máquina <strong>{{ $maquina->nombre }}</strong>?</p>
                            <p class="text-muted fs-8 mb-0">La máquina volverá a estar operativa en el catálogo.</p>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fa-solid fa-circle-check"></i> Activar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.campos-almacen-ubicacion').forEach(function (bloque) {
            const selectAlmacen = bloque.querySelector('.select-almacen-maquina');
            const selectUbicacion = bloque.querySelector('.select-ubicacion-maquina');

            if (!selectAlmacen || !selectUbicacion) {
                return;
            }

            const filtrarUbicaciones = function () {
                const almacenId = selectAlmacen.value;
                let tieneSeleccionValida = false;

                Array.from(selectUbicacion.options).forEach(function (opcion, indice) {
                    if (indice === 0) {
                        opcion.hidden = false;
                        return;
                    }

                    const coincide = !almacenId || opcion.dataset.almacen === almacenId;
                    opcion.hidden = !coincide;

                    if (coincide && opcion.selected) {
                        tieneSeleccionValida = true;
                    }
                });

                if (!tieneSeleccionValida) {
                    selectUbicacion.value = '';
                }
            };

            selectAlmacen.addEventListener('change', filtrarUbicaciones);
            filtrarUbicaciones();
        });
    });

    document.querySelectorAll('.maquina-seleccionada').forEach(function (check) {
        check.addEventListener('change', function () {
            if (!this.checked) {
                return;
            }

            document.querySelectorAll('.maquina-seleccionada').forEach(function (otro) {
                if (otro !== check) {
                    otro.checked = false;
                }
            });
        });
    });

    document.querySelectorAll('.btn-accion-maquina').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const seleccion = document.querySelector('.maquina-seleccionada:checked');

            if (!seleccion) {
                alert('Seleccione una máquina de la tabla.');
                return;
            }

            const base = @json(url('/maquinas'));
            window.location.href = base + '/' + seleccion.value + '/' + boton.dataset.accion;
        });
    });

    document.querySelectorAll('.btn-estado-maquina').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const seleccion = document.querySelector('.maquina-seleccionada:checked');

            if (!seleccion) {
                alert('Seleccione una máquina de la tabla.');
                return;
            }

            const accion = boton.dataset.estado;
            const mensaje = accion === 'activar'
                ? '¿Activar la máquina seleccionada?'
                : '¿Inactivar la máquina seleccionada? Use esto cuando esté en mantenimiento o descompuesta.';

            if (!confirm(mensaje)) {
                return;
            }

            const base = @json(url('/maquinas'));
            const form = document.getElementById('formEstadoMaquina');
            form.action = base + '/' + accion + '/' + seleccion.value;
            form.submit();
        });
    });
</script>
@endsection
