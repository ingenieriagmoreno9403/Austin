@extends('layouts.app')
@section('content')
    @include('Gestion_alumnos.partials.sweet_alerts')

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
    <style>
        .alumnos-modal-scroll .modal-dialog {
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
            max-height: calc(100vh - 1rem);
        }

        .alumnos-modal-scroll .modal-content {
            max-height: calc(100vh - 1rem);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .alumnos-modal-scroll .modal-header,
        .alumnos-modal-scroll .modal-footer {
            flex-shrink: 0;
        }

        .alumnos-modal-scroll .modal-body {
            min-height: 0;
            overflow-y: auto;
        }

        .alumno-option-btn--docs {
            background-color: #0099ff !important;
        }

        .alumno-option-btn--cursos {
            background-color: #ff9800 !important;
        }

        .alumno-option-btn--tutores {
            background-color: #00b84a !important;
        }

        .alumno-option-btn--editar {
            background-color: #5b2cff !important;
        }

        .alumno-option-btn--bloqueado {
            background-color: #e60023 !important;
        }
    </style>

    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Gestión de Alumnos</h2>
                            <p class="text-muted mb-0">Alta, consulta y administración de registros de alumnos.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-baseColor fs-7 mb-2" type="button" data-bs-toggle="modal" data-bs-target="#modalAltaAlumno">
                            <i class="fa-solid fa-user-plus"></i> Nuevo alumno
                        </button>
                        
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-baseColor-light fs-7 mb-2 dropdown-toggle" type="button" id="dropdownAtajosAlumnos"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-bolt"></i> Atajos
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownAtajosAlumnos">
                                @if ((int) optional(auth()->user())->id === 524)
                                    <li>
                                        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#modalCargaMasivaAlumnos">
                                            <i class="fa-solid fa-file-excel me-2"></i>Carga masiva
                                        </button>
                                    </li>
                                @endif
                                <li>
                                    <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevaEscuela">
                                        <i class="fa-solid fa-school me-2"></i>Nueva escuela
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevaEspecialidad">
                                        <i class="fa-solid fa-graduation-cap me-2"></i>Nueva especialidad
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevoTipoDocumento">
                                        <i class="fa-solid fa-file-circle-plus me-2"></i>Nuevo documento
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#modalRequisitosDocumentos">
                                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Requisitos docs
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="javascript:history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade alumnos-modal-scroll" id="modalAltaAlumno" tabindex="-1" aria-labelledby="modalAltaAlumnoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h5 class="modal-title text-secondary" id="modalAltaAlumnoLabel">
                                <i class="fa-solid fa-user-plus me-2"></i>Alta de alumno
                            </h5>
                            <p class="text-muted fs-8 mb-0">Captura los datos generales, escolares y de contacto del alumno.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                <form id="formAltaAlumno" action="#" method="POST" class="g-3 form needs-validation" novalidate>
                    @csrf
                    <div class="modal-body pt-0">
                    <div class="row">
                    <div class="col-md-4 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Número de matrícula <span class="text-danger">*</span></label>
                            <input type="number" class="form-control text" name="numero_matricula" min="1" step="1" required>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Indica el número de matrícula.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Semestre <span class="text-danger">*</span></label>
                            <input type="number" class="form-control text" name="semestre" min="1" max="20" step="1" required>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Indica el semestre.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Fecha de nacimiento</label>
                            <input type="date" class="form-control text" name="fecha_nacimiento">
                        </div>
                    </div>
                </div>

                    <div class="row">
                    <div class="col-md-4 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Nombres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text" name="nombres" rows="2" required>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Escribe el o los nombres.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Apellido paterno <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text" name="apellido_paterno" maxlength="100" required>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">El apellido paterno es obligatorio.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Apellido materno</label>
                            <input type="text" class="form-control text" name="apellido_materno" maxlength="100">
                        </div>
                    </div>
                </div>

                    <div class="row">
                    <div class="col-md-6 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Escuela</label>
                            <select class="form-select" name="id_escuela" id="selectEscuela">
                                <option value="" selected>Selecciona una escuela</option>
                            </select>
                            <small class="text-muted d-block mt-1" id="numeroSepSeleccionado">Numero SEP: N/D</small>
                        </div>
                    </div>

                    <div class="col-md-6 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Especialidad <span class="text-danger">*</span></label>
                            <select class="form-select" name="id_especialidad" id="selectEspecialidad" required>
                                <option value="" selected>Selecciona una especialidad</option>
                            </select>
                            <small class="text-muted d-block mt-1" id="numeroInternoEspecialidadSeleccionado">Núm. interno especialidad: N/D</small>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, selecciona una especialidad.</div>
                        </div>
                    </div>
                </div>

                    <div class="row">
                    <div class="col-md-4 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control text" name="telefono" maxlength="20" inputmode="tel" autocomplete="tel">
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Correo <span class="text-danger">*</span></label>
                            <input type="email" class="form-control text" name="correo" maxlength="150" required autocomplete="email">
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Escribe un correo electrónico válido.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Beca</label>
                            <select class="form-select" name="id_beca">
                                <option value="" selected>Sin beca</option>
                                <option value="1">Beca demo 1</option>
                                <option value="2">Beca demo 2</option>
                                <option value="3">Beca demo 3</option>
                            </select>
                        </div>
                    </div>
                </div>

                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-baseColor fs-8 rounded-1">
                            <i class="fa-solid fa-check"></i> Guardar alumno
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Listado de alumnos disponibles
                </h5>
                <p class="text-muted fs-8 mb-0">Consulta, administra documentos, tutores, cursos o edita alumnos registrados.</p>
            </div>
            <div class="card-body">
            <!-- <div class="row mb-3">
                <div class="col-md-5 col-12">
                    <div class="form-outline">
                        <label class="form-label">Buscar por coincidencias</label>
                        <input type="text" id="buscadorAlumnos" class="form-control text" placeholder="Matrícula, nombres, correo, teléfono, estado...">
                    </div>
                </div>
            </div> -->

            <div class="table-responsive">
                <table class="table table-stripped table-hover display mb-0" id="tableAlumnos">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-truncate">Opciones</th>
                            <th class="text-truncate">Matrícula</th>
                            <th class="text-truncate">Nombre Completo</th>
                            <th class="text-truncate">Estado</th>
                            <th class="text-truncate">Especialidad</th>
                            <th class="text-truncate">Empresa</th>
                            <th class="text-truncate">Escuela</th>
                            <th class="text-truncate">Correo</th>
                            <th class="text-truncate">Nac.</th>
                            <th class="text-truncate">Beca</th>
                            <th class="text-truncate">Teléfono</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAlumnosListado">
                        <tr>
                            <td class="text-muted" colspan="11">Cargando…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>

    @if ((int) optional(auth()->user())->id === 524)
        <div class="modal fade alumnos-modal-scroll" id="modalCargaMasivaAlumnos" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h5 class="modal-title text-secondary">
                                <i class="fa-solid fa-file-excel me-2"></i>Carga masiva de alumnos
                            </h5>
                            <p class="text-muted fs-8 mb-0">Sube el Excel y se crearán/actualizarán alumnos por matrícula.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="formCargaMasivaAlumnos" class="g-3 form needs-validation" novalidate>
                        <div class="modal-body pt-0">
                            <div class="col-12 mt-2">
                                <label class="form-label">Archivo Excel <span class="text-danger">*</span></label>
                                <input type="file" class="form-control text" id="archivoMasivoAlumnos" name="archivo" accept=".xlsx,.xls,.csv" required>
                                <small class="text-muted d-block mt-1">Sugerido: `ACTUALIZACION ALUMNOS DUAL FEBRERO 2026 (1).xlsx`</small>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-baseColor fs-8 rounded-1">
                                <i class="fa-solid fa-file-import"></i> Importar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade alumnos-modal-scroll" id="modalRequisitosDocumentos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content d-flex flex-column overflow-hidden">
                <form id="formRequisitosDocumentos" class="g-3 form needs-validation d-flex flex-column flex-grow-1 min-h-0" style="min-height: 0;" novalidate>
                    <div class="modal-header border-0 flex-shrink-0">
                        <div>
                            <h5 class="modal-title text-secondary">
                                <i class="fa-solid fa-clipboard-check me-2"></i>Requisitos mínimos de documentos
                            </h5>
                            <p class="text-muted fs-8 mb-0">Configura por separado qué debe subir el alumno y qué debe subir cada tutor.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body pt-0 flex-grow-1 overflow-auto min-h-0">
                        <div class="accordion acciones-admin-accordion mt-2" id="accordionRequisitosDocumentosAlumno">
                            <div class="accordion-item acciones-collapse-card acciones-collapse-card--vista rounded-4 overflow-hidden mb-3">
                                <h2 class="accordion-header" id="headingRequisitosAlumnoModal">
                                    <button class="accordion-button collapsed acciones-collapse-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseRequisitosAlumnoModal" aria-expanded="false" aria-controls="collapseRequisitosAlumnoModal">
                                        <span class="acciones-collapse-title">
                                            <span class="acciones-collapse-icon acciones-collapse-icon--vista">
                                                <i class="fa-solid fa-user-graduate"></i>
                                            </span>
                                            <span>
                                                <span class="acciones-collapse-name">Documentos obligatorios del alumno</span>
                                                <span class="acciones-collapse-help">Marca los documentos requeridos para cada alumno.</span>
                                            </span>
                                        </span>
                                    </button>
                                </h2>
                                <div id="collapseRequisitosAlumnoModal" class="accordion-collapse collapse" aria-labelledby="headingRequisitosAlumnoModal"
                                    data-bs-parent="#accordionRequisitosDocumentosAlumno">
                                    <div class="accordion-body acciones-collapse-body">
                                        <p class="text-muted small mb-2">Estos archivos se revisan en los documentos cargados como persona <strong>alumno</strong>.</p>
                                        <div class="table-responsive">
                                            <table class="table table-stripped table-hover align-middle mb-0">
                                                <thead>
                                                    <tr class="text-tr">
                                                        <th class="text-truncate">Documento</th>
                                                        <th class="text-truncate text-center" style="width: 8rem;">Obligatorio</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyRequisitosAlumno"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item acciones-collapse-card acciones-collapse-card--accion rounded-4 overflow-hidden">
                                <h2 class="accordion-header" id="headingRequisitosTutorModal">
                                    <button class="accordion-button collapsed acciones-collapse-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseRequisitosTutorModal" aria-expanded="false" aria-controls="collapseRequisitosTutorModal">
                                        <span class="acciones-collapse-title">
                                            <span class="acciones-collapse-icon acciones-collapse-icon--accion">
                                                <i class="fa-solid fa-chalkboard-user"></i>
                                            </span>
                                            <span>
                                                <span class="acciones-collapse-name">Documentos obligatorios del tutor</span>
                                                <span class="acciones-collapse-help">Marca los documentos requeridos por cada tutor.</span>
                                            </span>
                                        </span>
                                    </button>
                                </h2>
                                <div id="collapseRequisitosTutorModal" class="accordion-collapse collapse" aria-labelledby="headingRequisitosTutorModal"
                                    data-bs-parent="#accordionRequisitosDocumentosAlumno">
                                    <div class="accordion-body acciones-collapse-body">
                                        <p class="text-muted small mb-2">Estos archivos se revisan <strong>por cada tutor</strong> registrado del alumno (persona <strong>tutor</strong>).</p>
                                        <div class="table-responsive">
                                            <table class="table table-stripped table-hover align-middle mb-0">
                                                <thead>
                                                    <tr class="text-tr">
                                                        <th class="text-truncate">Documento</th>
                                                        <th class="text-truncate text-center" style="width: 8rem;">Obligatorio</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyRequisitosTutor"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small mt-2 mb-0" id="requisitosDocumentosVacio" style="display:none;">
                            No hay tipos de documento registrados.
                        </p>
                    </div>
                    <div class="modal-footer border-0 flex-shrink-0">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-baseColor fs-8 rounded-1">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade alumnos-modal-scroll" id="modalEditarAlumno" tabindex="-1" aria-labelledby="modalEditarAlumnoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalEditarAlumnoLabel">
                            <i class="fa-solid fa-pen me-2"></i>Editar alumno
                        </h5>
                        <p class="text-muted fs-8 mb-0">Actualiza los datos generales, escolares y de contacto del alumno.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEditarAlumno" class="g-3 form needs-validation" novalidate>
                    <input type="hidden" id="alumno_edit_id" value="">
                    <div class="modal-body pt-0">
                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Número de matrícula <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control text" id="edit_numero_matricula" name="numero_matricula" min="1" step="1" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Indica el número de matrícula.</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Semestre <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control text" id="edit_semestre" name="semestre" min="1" max="20" step="1" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Indica el semestre.</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Fecha de nacimiento</label>
                                    <input type="date" class="form-control text" id="edit_fecha_nacimiento" name="fecha_nacimiento">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombres <span class="text-danger">*</span></label>
                                    <textarea class="form-control text" id="edit_nombres" name="nombres" rows="2" required></textarea>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Escribe el o los nombres.</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Apellido paterno <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" id="edit_apellido_paterno" name="apellido_paterno" maxlength="100" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">El apellido paterno es obligatorio.</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Apellido materno</label>
                                    <input type="text" class="form-control text" id="edit_apellido_materno" name="apellido_materno" maxlength="100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Escuela</label>
                                    <select class="form-select" name="id_escuela" id="selectEscuelaEdit">
                                        <option value="" selected>Selecciona una escuela</option>
                                    </select>
                                    <small class="text-muted d-block mt-1" id="numeroSepSeleccionadoEdit">Numero SEP: N/D</small>
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Especialidad <span class="text-danger">*</span></label>
                                    <select class="form-select" name="id_especialidad" id="selectEspecialidadEdit" required>
                                        <option value="" selected>Selecciona una especialidad</option>
                                    </select>
                                    <small class="text-muted d-block mt-1" id="numeroInternoEspecialidadSeleccionadoEdit">Núm. interno especialidad: N/D</small>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, selecciona una especialidad.</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control text" id="edit_telefono" name="telefono" maxlength="20" inputmode="tel" autocomplete="tel">
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Correo <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control text" id="edit_correo" name="correo" maxlength="150" required autocomplete="email">
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Escribe un correo electrónico válido.</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Beca</label>
                                    <select class="form-select" name="id_beca" id="edit_id_beca">
                                        <option value="" selected>Sin beca</option>
                                        <option value="1">Beca demo 1</option>
                                        <option value="2">Beca demo 2</option>
                                        <option value="3">Beca demo 3</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Estado</label>
                                    <input type="text" class="form-control text" id="edit_estado" name="estado" maxlength="50" placeholder="Ej. iniciado" autocomplete="off">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-baseColor fs-8 rounded-1">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade alumnos-modal-scroll" id="modalTutoresAlumno" tabindex="-1" aria-labelledby="modalTutoresAlumnoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalTutoresAlumnoLabel">
                            <i class="fa-solid fa-users me-2"></i>Tutores del alumno
                        </h5>
                        <p class="text-muted fs-8 mb-0"><span id="tituloAlumnoTutores">—</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formTutorAlumno" class="g-3 form needs-validation" novalidate>
                    <input type="hidden" name="alumno_id" id="tutor_alumno_id" value="">
                    <div class="modal-body pt-0">
                        <h6 class="text-secondary mb-3">
                            <i class="fa-solid fa-user-plus me-2"></i>Registrar tutor
                        </h6>
                        <div class="row">
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombres <span class="text-danger">*</span></label>
                                    <textarea class="form-control text" name="nombres" id="tutor_nombres" rows="2" maxlength="500" required></textarea>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Escribe el o los nombres del tutor.</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Apellido paterno <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" name="apellido_paterno" id="tutor_apellido_paterno" maxlength="100" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Campo obligatorio.</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Apellido materno</label>
                                    <input type="text" class="form-control text" name="apellido_materno" id="tutor_apellido_materno" maxlength="100">
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Dirección</label>
                                    <textarea class="form-control text" name="direccion" id="tutor_direccion" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control text" name="telefono" id="tutor_telefono" maxlength="20" inputmode="tel">
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Correo <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control text" name="correo" id="tutor_correo" maxlength="150" required autocomplete="email">
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Correo obligatorio y válido.</div>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Parentesco</label>
                                    <input type="text" class="form-control text" name="parentesco" id="tutor_parentesco" maxlength="225" placeholder="Ej. Madre, padre, tutor legal…">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-secondary m-0">
                                <i class="fa-solid fa-list-check me-2"></i>Tutores registrados para este alumno
                            </h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-stripped table-hover mb-0" id="tablaTutoresAlumno">
                                <thead>
                                    <tr class="text-tr">
                                        <th class="text-truncate">Nombres</th>
                                        <th class="text-truncate">Apellidos</th>
                                        <th class="text-truncate">Parentesco</th>
                                        <th class="text-truncate">Dirección</th>
                                        <th class="text-truncate">Teléfono</th>
                                        <th class="text-truncate">Correo</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyTutoresAlumno">
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted small mt-2 mb-0" id="tutoresAlumnoVacio" style="display: none;">No hay tutores registrados para este alumno.</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-baseColor fs-8 rounded-1">
                            <i class="fa-solid fa-check"></i> Guardar tutor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade alumnos-modal-scroll" id="modalCursosAlumno" tabindex="-1" aria-labelledby="modalCursosAlumnoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalCursosAlumnoLabel">
                            <i class="fa-solid fa-book-open me-2"></i>Cursos del alumno
                        </h5>
                        <p class="text-muted fs-8 mb-0"><span id="tituloAlumnoCursos">—</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <input type="hidden" id="cursos_modal_alumno_id" value="">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-8 col-12">
                            <label class="form-label mb-1">Asignar curso</label>
                            <select class="form-select" id="selectCursoAsignar">
                                <option value="">Selecciona un curso…</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-12">
                            <button type="button" class="btn btn-baseColor fs-8 rounded-1 w-100" id="btnAsignarCursoAlumno">
                                <i class="fa-solid fa-plus"></i> Asignar
                            </button>
                        </div>
                    </div>
                    <p class="text-muted small mb-3 mb-md-2">No se puede asignar el mismo curso dos veces. Puedes editar la <strong>calificación</strong> directamente en esta tabla y guardarla por cada curso.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-stripped table-hover mb-0" id="tablaCursosAlumnoModal">
                            <thead>
                                <tr class="text-tr">
                                    <th class="text-truncate">Curso</th>
                                    <th class="text-truncate">Tipo</th>
                                    <th class="text-truncate">Instructor</th>
                                    <th class="text-truncate">Calificación</th>
                                    <th class="text-truncate text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyCursosAlumnoModal">
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small mt-2 mb-0" id="cursosAlumnoModalVacio" style="display: none;">No hay cursos asignados a este alumno.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade alumnos-modal-scroll" id="modalDocumentosAlumno" tabindex="-1" aria-labelledby="modalDocumentosAlumnoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalDocumentosAlumnoLabel">
                            <i class="fa-solid fa-file-lines me-2"></i>Documentos
                        </h5>
                        <p class="text-muted fs-8 mb-0"><span id="tituloAlumnoDocumentos">—</span></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formDocumentoPersona" class="g-3 form needs-validation" enctype="multipart/form-data" novalidate>
                    <input type="hidden" id="documentos_alumno_id" value="">
                    <input type="hidden" id="documentos_id_persona_alumno" value="">
                    <input type="hidden" id="persona_documento_edit_id" value="">
                    <div class="modal-body pt-0">
                        <h6 class="text-secondary mb-3" id="tituloFormDocumentoPersona">
                            <i class="fa-solid fa-file-circle-plus me-2"></i>Registrar documento
                        </h6>
                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Tipo de documento (id_documento) <span class="text-danger">*</span></label>
                                    <select class="form-select" name="id_documento" id="doc_id_documento" required>
                                        <option value="" selected disabled>Selecciona</option>
                                    </select>
                                    <div class="invalid-feedback">Selecciona el tipo de documento.</div>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Persona <span class="text-danger">*</span></label>
                                    <select class="form-select" name="id_persona" id="doc_id_persona_destino" required>
                                        <option value="">—</option>
                                    </select>
                                    <div class="invalid-feedback">Selecciona alumno o tutor.</div>
                                </div>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Fecha alta <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control text" name="fecha_alta" id="doc_fecha_alta" required>
                                    <div class="invalid-feedback">Indica la fecha.</div>
                                </div>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Estado (2 car.)</label>
                                    <input type="text" class="form-control text" name="estado_documento" id="doc_estado_documento" maxlength="2" value="a" placeholder="a">
                                </div>
                            </div>
                            <div class="col-md-8 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Archivo <span class="text-danger" id="doc_archivo_required_mark">*</span></label>
                                    <input type="file" class="form-control text" name="archivo" id="doc_archivo" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                    <small class="text-muted">Al editar puedes dejar el mismo archivo o subir uno nuevo.</small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="text-secondary mb-2">
                            <i class="fa-solid fa-user-graduate me-2"></i>Documentos del alumno
                        </h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-stripped table-hover mb-0">
                                <thead>
                                    <tr class="text-tr">
                                        <th class="text-truncate">id_documento</th>
                                        <th class="text-truncate">Persona</th>
                                        <th class="text-truncate">fecha_alta</th>
                                        <th class="text-truncate">estado</th>
                                        <th class="text-truncate">Tipo</th>
                                        <th class="text-truncate">Archivo</th>
                                        <th class="text-truncate text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyDocumentosAlumno">
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted small mb-3" id="documentosAlumnoVacio" style="display: none;">Sin documentos registrados para el alumno.</p>

                        <h6 class="text-secondary mb-2">
                            <i class="fa-solid fa-users me-2"></i>Documentos de tutores
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-stripped table-hover mb-0">
                                <thead>
                                    <tr class="text-tr">
                                        <th class="text-truncate">id_documento</th>
                                        <th class="text-truncate">Persona</th>
                                        <th class="text-truncate">fecha_alta</th>
                                        <th class="text-truncate">estado</th>
                                        <th class="text-truncate">Tipo</th>
                                        <th class="text-truncate">Archivo</th>
                                        <th class="text-truncate text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyDocumentosTutores">
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted small mt-2 mb-0" id="documentosTutoresVacio" style="display: none;">Sin documentos de tutores.</p>
                    </div>
                    <div class="modal-footer border-0 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-outline-secondary fs-8 rounded-1" id="btnCancelarEdicionDocumento" style="display: none;">
                                <i class="fa-solid fa-eraser"></i> Cancelar edición
                            </button>
                        </div>
                        <button type="submit" class="btn btn-baseColor fs-8 rounded-1" id="btnSubmitDocumentoPersona">
                            <i class="fa-solid fa-check"></i> Guardar documento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade alumnos-modal-scroll" id="modalNuevoTipoDocumento" tabindex="-1" aria-labelledby="modalTituloTipoDocumento" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalTituloTipoDocumento">
                            <i class="fa-solid fa-file-circle-plus me-2"></i>Alta de nuevo tipo de documento
                        </h5>
                        <p class="text-muted fs-8 mb-0">Registra y administra los tipos de documento disponibles.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevoTipoDocumento" class="g-3 form needs-validation" novalidate>
                    <input type="hidden" id="documentoEditId" value="">
                    <div class="modal-body pt-0">
                        <div class="row">
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombre del documento <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" name="nombre_documento" id="nombre_documento_tipo" maxlength="200" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">El nombre es obligatorio (máx. 200 caracteres).</div>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Descripción del documento</label>
                                    <textarea class="form-control text" name="descripcion_documento" id="descripcion_documento_tipo" rows="3" maxlength="300"></textarea>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                            <h6 class="text-secondary m-0">
                                <i class="fa-solid fa-list-check me-2"></i>Tipos de documento registrados
                            </h6>
                            <div class="form-outline" style="min-width: 12rem; max-width: 22rem;">
                                <label class="form-label small mb-0" for="buscadorTiposDocumentoModal">Buscar</label>
                                <input type="text" class="form-control form-control-sm text" id="buscadorTiposDocumentoModal" placeholder="id, nombre, descripción…" autocomplete="off">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-stripped table-hover mb-0" id="tablaTiposDocumento">
                                <thead>
                                    <tr class="text-tr">
                                        <th class="text-truncate">id</th>
                                        <th class="text-truncate">nombre_documento</th>
                                        <th class="text-truncate">descripcion_documento</th>
                                        <th class="text-truncate">Estado</th>
                                        <th class="text-truncate text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyTiposDocumento">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer border-0 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <div class="d-flex flex-wrap gap-2 ms-md-auto">
                            <button type="button" class="btn btn-outline-secondary fs-8 rounded-1" id="btnLimpiarEdicionTipoDocumento" style="display: none;" title="Volver a alta de tipo de documento">
                                <i class="fa-solid fa-eraser"></i> Limpiar
                            </button>
                            <button type="submit" class="btn btn-baseColor fs-8 rounded-1" id="btnSubmitTipoDocumento">
                                <i class="fa-solid fa-check"></i> Agregar tipo de documento
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade alumnos-modal-scroll" id="modalNuevaEscuela" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalTituloEscuela">
                            <i class="fa-solid fa-school me-2"></i>Alta de nueva escuela
                        </h5>
                        <p class="text-muted fs-8 mb-0">Registra y administra escuelas disponibles para los alumnos.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevaEscuela" class="g-3 form needs-validation" novalidate>
                    <input type="hidden" id="escuelaEditId" value="">
                    <div class="modal-body pt-0">
                        <div class="row">
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombre escuela <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" id="nombreEscuela" name="nombre" maxlength="255" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, escribe el nombre de la escuela.</div>
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Direccion</label>
                                    <textarea class="form-control text" id="direccionEscuela" name="direccion" rows="2"></textarea>
                                </div>
                            </div>

                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Numero SEP <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" id="numeroSepEscuela" name="numero_sep" maxlength="50" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, escribe el numero SEP.</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Telefono</label>
                                    <input type="text" class="form-control text" id="telefonoEscuela" name="telefono" maxlength="30">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                            <h6 class="text-secondary m-0">
                                <i class="fa-solid fa-list-check me-2"></i>Escuelas registradas
                            </h6>
                            <div class="form-outline" style="min-width: 12rem; max-width: 22rem;">
                                <label class="form-label small mb-0" for="buscadorEscuelasModal">Buscar</label>
                                <input type="text" class="form-control form-control-sm text" id="buscadorEscuelasModal" placeholder="Nombre, dirección, SEP, teléfono…" autocomplete="off">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-stripped table-hover mb-0" id="tablaEscuelasModal">
                                <thead>
                                    <tr class="text-tr">
                                        <th class="text-truncate">Nombre</th>
                                        <th class="text-truncate">Dirección</th>
                                        <th class="text-truncate">Núm. SEP</th>
                                        <th class="text-truncate">Teléfono</th>
                                        <th class="text-truncate">Estado</th>
                                        <th class="text-truncate text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyEscuelasModal">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer border-0 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <div class="d-flex flex-wrap gap-2 ms-md-auto">
                            <button type="button" class="btn btn-outline-secondary fs-8 rounded-1" id="btnLimpiarEdicionEscuela" style="display: none;" title="Volver a alta de escuela">
                                <i class="fa-solid fa-eraser"></i> Limpiar
                            </button>
                            <button type="submit" class="btn btn-baseColor fs-8 rounded-1" id="btnSubmitEscuela">
                                <i class="fa-solid fa-check"></i> Agregar escuela
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade alumnos-modal-scroll" id="modalNuevaEspecialidad" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalTituloEspecialidad">
                            <i class="fa-solid fa-graduation-cap me-2"></i>Alta de nueva especialidad
                        </h5>
                        <p class="text-muted fs-8 mb-0">Registra y administra carreras o especialidades disponibles.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevaEspecialidad" class="g-3 form needs-validation" novalidate>
                    <input type="hidden" id="especialidadEditId" value="">
                    <div class="modal-body pt-0">
                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Número interno especialidad <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control text" id="numeroInternoEspecialidad" name="numero_interno_especialidad" min="1" step="1" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Indica el número interno.</div>
                                </div>
                            </div>

                            <div class="col-md-8 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombre especialidad <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" id="nombreEspecialidad" name="nombre_especialidad" maxlength="100" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Escribe el nombre de la especialidad.</div>
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Descripción <span class="text-danger">*</span></label>
                                    <textarea class="form-control text" id="descripcionEspecialidad" name="descripcion" rows="3" required></textarea>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">La descripción es obligatoria.</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                            <h6 class="text-secondary m-0">
                                <i class="fa-solid fa-list-check me-2"></i>Carreras / especialidades registradas
                            </h6>
                            <div class="form-outline" style="min-width: 12rem; max-width: 22rem;">
                                <label class="form-label small mb-0" for="buscadorEspecialidadesModal">Buscar</label>
                                <input type="text" class="form-control form-control-sm text" id="buscadorEspecialidadesModal" placeholder="Núm. interno, nombre, descripción…" autocomplete="off">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-stripped table-hover mb-0" id="tablaEspecialidadesModal">
                                <thead>
                                    <tr class="text-tr">
                                        <th class="text-truncate">Núm. int.</th>
                                        <th class="text-truncate">Nombre</th>
                                        <th class="text-truncate">Descripción</th>
                                        <th class="text-truncate">Estado</th>
                                        <th class="text-truncate text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyEspecialidadesModal">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer border-0 d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <div class="d-flex flex-wrap gap-2 ms-md-auto">
                            <button type="button" class="btn btn-outline-secondary fs-8 rounded-1" id="btnLimpiarEdicionEspecialidad" style="display: none;" title="Volver a alta de especialidad">
                                <i class="fa-solid fa-eraser"></i> Limpiar
                            </button>
                            <button type="submit" class="btn btn-baseColor fs-8 rounded-1" id="btnSubmitEspecialidad">
                                <i class="fa-solid fa-check"></i> Agregar especialidad
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @php
        $apiEscuelasBase = url('/Gestion_alumnos/api/escuelas');
        $apiEspecialidadesBase = url('/Gestion_alumnos/api/especialidades');
        $apiDocumentosBase = url('/Gestion_alumnos/api/documentos');
        $apiPersonasDocumentosBase = url('/Gestion_alumnos/api/personas-documentos');
        $apiAlumnosBase = url('/Gestion_alumnos/api/alumnos');
        $apiCursosBase = url('/Gestion_alumnos/api/cursos');
    @endphp
    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/gestionAlumnosMayusculas.js') }}?v=4"></script>
    <script src="{{ asset('js/gestionAlumnosDataTables.js') }}?v=1"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buscador = document.getElementById('buscadorAlumnos');
            const formAltaAlumno = document.getElementById('formAltaAlumno');
            const modalAltaAlumno = document.getElementById('modalAltaAlumno');
            const tbodyAlumnosListado = document.getElementById('tbodyAlumnosListado');
            const formEditarAlumno = document.getElementById('formEditarAlumno');
            const modalEditarAlumno = document.getElementById('modalEditarAlumno');
            const alumnoEditId = document.getElementById('alumno_edit_id');
            const selectEscuelaEdit = document.getElementById('selectEscuelaEdit');
            const selectEspecialidadEdit = document.getElementById('selectEspecialidadEdit');
            const numeroSepSeleccionadoEdit = document.getElementById('numeroSepSeleccionadoEdit');
            const numeroInternoEspecialidadSeleccionadoEdit = document.getElementById('numeroInternoEspecialidadSeleccionadoEdit');
            const formNuevaEscuela = document.getElementById('formNuevaEscuela');
            const nombreEscuela = document.getElementById('nombreEscuela');
            const numeroSepEscuela = document.getElementById('numeroSepEscuela');
            const selectEscuela = document.getElementById('selectEscuela');
            const numeroSepSeleccionado = document.getElementById('numeroSepSeleccionado');
            const modalNuevaEscuela = document.getElementById('modalNuevaEscuela');
            const escuelaEditId = document.getElementById('escuelaEditId');
            const modalTituloEscuela = document.getElementById('modalTituloEscuela');
            const btnSubmitEscuela = document.getElementById('btnSubmitEscuela');
            const btnLimpiarEdicionEscuela = document.getElementById('btnLimpiarEdicionEscuela');
            const apiEscuelas = @json($apiEscuelasBase);
            const apiEspecialidades = @json($apiEspecialidadesBase);
            const apiDocumentos = @json($apiDocumentosBase);
            const apiPersonasDocumentos = @json($apiPersonasDocumentosBase);
            const apiAlumnos = @json($apiAlumnosBase);
            const apiCursos = @json($apiCursosBase);
            const especialidadEditId = document.getElementById('especialidadEditId');
            const modalTituloEspecialidad = document.getElementById('modalTituloEspecialidad');
            const btnSubmitEspecialidad = document.getElementById('btnSubmitEspecialidad');
            const btnLimpiarEdicionEspecialidad = document.getElementById('btnLimpiarEdicionEspecialidad');
            const buscadorEspecialidadesModalEl = document.getElementById('buscadorEspecialidadesModal');
            const selectEspecialidad = document.getElementById('selectEspecialidad');
            const numeroInternoEspecialidadSeleccionado = document.getElementById('numeroInternoEspecialidadSeleccionado');
            const direccionEscuela = document.getElementById('direccionEscuela');
            const telefonoEscuela = document.getElementById('telefonoEscuela');
            const tbodyEscuelasModal = document.getElementById('tbodyEscuelasModal');
            const formNuevaEspecialidad = document.getElementById('formNuevaEspecialidad');
            const numeroInternoEspecialidad = document.getElementById('numeroInternoEspecialidad');
            const nombreEspecialidad = document.getElementById('nombreEspecialidad');
            const descripcionEspecialidad = document.getElementById('descripcionEspecialidad');
            const tbodyEspecialidadesModal = document.getElementById('tbodyEspecialidadesModal');
            const modalNuevaEspecialidad = document.getElementById('modalNuevaEspecialidad');
            const formCargaMasivaAlumnos = document.getElementById('formCargaMasivaAlumnos');
            const archivoMasivoAlumnos = document.getElementById('archivoMasivoAlumnos');
            const modalCargaMasivaAlumnos = document.getElementById('modalCargaMasivaAlumnos');
            const modalRequisitosDocumentos = document.getElementById('modalRequisitosDocumentos');
            const formRequisitosDocumentos = document.getElementById('formRequisitosDocumentos');
            const tbodyRequisitosAlumno = document.getElementById('tbodyRequisitosAlumno');
            const tbodyRequisitosTutor = document.getElementById('tbodyRequisitosTutor');
            const requisitosDocumentosVacio = document.getElementById('requisitosDocumentosVacio');
            const modalCursosAlumno = document.getElementById('modalCursosAlumno');
            const tituloAlumnoCursos = document.getElementById('tituloAlumnoCursos');
            const cursosModalAlumnoId = document.getElementById('cursos_modal_alumno_id');
            const selectCursoAsignar = document.getElementById('selectCursoAsignar');
            const btnAsignarCursoAlumno = document.getElementById('btnAsignarCursoAlumno');
            const tbodyCursosAlumnoModal = document.getElementById('tbodyCursosAlumnoModal');
            const cursosAlumnoModalVacio = document.getElementById('cursosAlumnoModalVacio');

            const modalTutoresAlumno = document.getElementById('modalTutoresAlumno');
            const tituloAlumnoTutores = document.getElementById('tituloAlumnoTutores');
            const tutorAlumnoId = document.getElementById('tutor_alumno_id');
            const formTutorAlumno = document.getElementById('formTutorAlumno');
            const tbodyTutoresAlumno = document.getElementById('tbodyTutoresAlumno');
            const tutoresAlumnoVacio = document.getElementById('tutoresAlumnoVacio');

            const modalDocumentosAlumno = document.getElementById('modalDocumentosAlumno');
            const tituloAlumnoDocumentos = document.getElementById('tituloAlumnoDocumentos');
            const documentosAlumnoId = document.getElementById('documentos_alumno_id');
            const documentosIdPersonaAlumno = document.getElementById('documentos_id_persona_alumno');
            const formDocumentoPersona = document.getElementById('formDocumentoPersona');
            const personaDocumentoEditId = document.getElementById('persona_documento_edit_id');
            const tituloFormDocumentoPersona = document.getElementById('tituloFormDocumentoPersona');
            const docArchivoInput = document.getElementById('doc_archivo');
            const docArchivoRequiredMark = document.getElementById('doc_archivo_required_mark');
            const btnCancelarEdicionDocumento = document.getElementById('btnCancelarEdicionDocumento');
            const btnSubmitDocumentoPersona = document.getElementById('btnSubmitDocumentoPersona');
            const docIdPersonaDestino = document.getElementById('doc_id_persona_destino');
            /** Tutores del alumno actual cargados desde la API (tbltutores); se usa para Persona y para id_personas en documentos. */
            let listaTutoresDocumentosActual = [];
            const tbodyDocumentosAlumno = document.getElementById('tbodyDocumentosAlumno');
            const tbodyDocumentosTutores = document.getElementById('tbodyDocumentosTutores');
            const documentosAlumnoVacio = document.getElementById('documentosAlumnoVacio');
            const documentosTutoresVacio = document.getElementById('documentosTutoresVacio');
            const tbodyTiposDocumento = document.getElementById('tbodyTiposDocumento');
            const formNuevoTipoDocumento = document.getElementById('formNuevoTipoDocumento');
            const modalNuevoTipoDocumento = document.getElementById('modalNuevoTipoDocumento');
            const documentoEditId = document.getElementById('documentoEditId');
            const modalTituloTipoDocumento = document.getElementById('modalTituloTipoDocumento');
            const btnSubmitTipoDocumento = document.getElementById('btnSubmitTipoDocumento');
            const btnLimpiarEdicionTipoDocumento = document.getElementById('btnLimpiarEdicionTipoDocumento');
            const buscadorTiposDocumentoModalEl = document.getElementById('buscadorTiposDocumentoModal');

            const escapeHtmlTutor = function (text) {
                if (text === null || text === undefined) {
                    return '';
                }
                const d = document.createElement('div');
                d.textContent = String(text);
                return d.innerHTML;
            };

            const cargarRequisitosDocumentos = async function () {
                if (!tbodyRequisitosAlumno || !tbodyRequisitosTutor || !requisitosDocumentosVacio) {
                    return;
                }
                const r = await fetch(apiAlumnos + '/requisitos-documentos', {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const j = await r.json();
                if (!r.ok) {
                    throw new Error((j && j.message) ? j.message : 'No se pudieron cargar los requisitos.');
                }
                const data = (j && j.data) ? j.data : {};
                const docs = Array.isArray(data.documentos) ? data.documentos : [];
                tbodyRequisitosAlumno.innerHTML = '';
                tbodyRequisitosTutor.innerHTML = '';

                if (!docs.length) {
                    requisitosDocumentosVacio.style.display = 'block';
                    return;
                }
                requisitosDocumentosVacio.style.display = 'none';

                docs.forEach(function (doc) {
                    const nombre = (doc && doc.nombre_documento) ? String(doc.nombre_documento).trim() : '—';
                    const idDoc = String(doc.id || '');

                    const trA = document.createElement('tr');
                    trA.className = 'boder-sec';
                    const tdNomA = document.createElement('td');
                    tdNomA.className = 'text-truncate text-start';
                    tdNomA.textContent = nombre;
                    trA.appendChild(tdNomA);
                    const tdChkA = document.createElement('td');
                    tdChkA.className = 'text-center';
                    tdChkA.innerHTML = '<input type="checkbox" class="form-check-input req-doc-check-alumno" value="' +
                        idDoc + '"' + (doc.obligatorio_alumno ? ' checked' : '') + '>';
                    trA.appendChild(tdChkA);
                    tbodyRequisitosAlumno.appendChild(trA);

                    const trT = document.createElement('tr');
                    trT.className = 'boder-sec';
                    const tdNomT = document.createElement('td');
                    tdNomT.className = 'text-truncate text-start';
                    tdNomT.textContent = nombre;
                    trT.appendChild(tdNomT);
                    const tdChkT = document.createElement('td');
                    tdChkT.className = 'text-center';
                    tdChkT.innerHTML = '<input type="checkbox" class="form-check-input req-doc-check-tutor" value="' +
                        idDoc + '"' + (doc.obligatorio_tutor ? ' checked' : '') + '>';
                    trT.appendChild(tdChkT);
                    tbodyRequisitosTutor.appendChild(trT);
                });
            };

            const cargarTablaTutoresAlumno = async function (alumnoId) {
                if (!tbodyTutoresAlumno || !tutoresAlumnoVacio) {
                    return;
                }

                const aid = String(alumnoId || '').trim();
                tbodyTutoresAlumno.innerHTML = '';

                if (!aid) {
                    tutoresAlumnoVacio.style.display = 'block';
                    return;
                }

                try {
                    const r = await fetch(apiAlumnos + '/' + encodeURIComponent(aid) + '/tutores', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        tutoresAlumnoVacio.style.display = 'block';
                        return;
                    }

                    const lista = j.data || [];
                    lista.forEach(function (t) {
                        const tr = document.createElement('tr');
                        tr.className = 'boder-sec';
                        const apParts = [t.apellido_paterno, t.apellido_materno].filter(function (x) {
                            return x && String(x).trim();
                        });
                        const apStr = apParts.length ? apParts.join(' ') : '—';
                        const parentTxt = (t.paretensco != null && String(t.paretensco).trim()) ? String(t.paretensco).trim() : '—';

                        tr.innerHTML = '<td class="text-truncate text-start">' + escapeHtmlTutor(t.nombres) + '</td>' +
                            '<td class="text-truncate">' + escapeHtmlTutor(apStr) + '</td>' +
                            '<td class="text-truncate">' + escapeHtmlTutor(parentTxt) + '</td>' +
                            '<td class="text-truncate" style="max-width: 10rem;">' + escapeHtmlTutor(t.direccion || '—') + '</td>' +
                            '<td class="text-truncate">' + escapeHtmlTutor(t.telefono || '—') + '</td>' +
                            '<td class="text-truncate">' + escapeHtmlTutor(t.correo || '') + '</td>';
                        tbodyTutoresAlumno.appendChild(tr);
                    });

                    tutoresAlumnoVacio.style.display = lista.length ? 'none' : 'block';
                } catch (err) {
                    console.error(err);
                    tutoresAlumnoVacio.style.display = 'block';
                }
            };

            if (modalTutoresAlumno && tutorAlumnoId) {
                modalTutoresAlumno.addEventListener('show.bs.modal', function (event) {
                    const btn = event.relatedTarget;
                    if (!btn) {
                        return;
                    }

                    const id = btn.getAttribute('data-alumno-id') || '';
                    const nombre = btn.getAttribute('data-alumno-nombre') || 'Alumno';

                    if (tituloAlumnoTutores) {
                        tituloAlumnoTutores.textContent = nombre;
                    }

                    tutorAlumnoId.value = id;
                    void cargarTablaTutoresAlumno(id);

                    if (formTutorAlumno) {
                        formTutorAlumno.classList.remove('was-validated');
                    }
                });
            }

            const etiquetaTutor = function (t) {
                const ap = [t.apellido_paterno, t.apellido_materno].filter(function (x) {
                    return x && String(x).trim();
                }).join(' ');
                return (t.nombres || '') + (ap ? ' ' + ap : '');
            };

            const slugCarpetaTutor = function (txt) {
                let s = String(txt || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                s = s.replace(/[^a-zA-Z0-9]+/g, '_').replace(/^_|_$/g, '');
                return (s || 'tutor').substring(0, 100);
            };

            const resetFormDocumentoPersonaAlta = function () {
                if (personaDocumentoEditId) {
                    personaDocumentoEditId.value = '';
                }
                if (tituloFormDocumentoPersona) {
                    tituloFormDocumentoPersona.innerHTML = '<i class="fa-solid fa-file-circle-plus me-2"></i>Registrar documento';
                }
                if (btnCancelarEdicionDocumento) {
                    btnCancelarEdicionDocumento.style.display = 'none';
                }
                if (docArchivoInput) {
                    docArchivoInput.setAttribute('required', 'required');
                }
                if (docArchivoRequiredMark) {
                    docArchivoRequiredMark.classList.remove('d-none');
                }
                if (btnSubmitDocumentoPersona) {
                    btnSubmitDocumentoPersona.innerHTML = '<i class="fa-solid fa-check"></i> Guardar documento';
                }
                const selPerReset = document.getElementById('doc_id_persona_destino');
                if (selPerReset) {
                    selPerReset.removeAttribute('disabled');
                }
                const elEstReset = document.getElementById('doc_estado_documento');
                if (elEstReset) {
                    elEstReset.value = 'a';
                }
            };

            const poblarSelectPersonaDocumento = async function (alumnoId, idPersonaAlumno, nombreAlumno) {
                if (!docIdPersonaDestino) {
                    return;
                }

                docIdPersonaDestino.innerHTML = '';
                listaTutoresDocumentosActual = [];

                const optAl = document.createElement('option');
                optAl.value = String(idPersonaAlumno);
                optAl.textContent = 'Alumno — ' + (nombreAlumno || 'Alumno');
                optAl.dataset.carpeta = 'alumno';
                docIdPersonaDestino.appendChild(optAl);

                const aid = String(alumnoId || '').trim();
                if (!aid) {
                    return;
                }

                try {
                    const r = await fetch(apiAlumnos + '/' + encodeURIComponent(aid) + '/tutores', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        return;
                    }

                    (j.data || []).forEach(function (t) {
                        const pid = t.id;
                        if (pid === undefined || pid === null) {
                            return;
                        }
                        listaTutoresDocumentosActual.push(t);
                        const o = document.createElement('option');
                        o.value = String(pid);
                        o.textContent = 'Tutor — ' + etiquetaTutor(t).trim();
                        o.dataset.carpeta = slugCarpetaTutor(etiquetaTutor(t));
                        docIdPersonaDestino.appendChild(o);
                    });
                } catch (err) {
                    console.error(err);
                }
            };

            const construirIdPersonasParaDocumentos = function (idPersonaAlumno) {
                const ids = [];
                if (idPersonaAlumno !== undefined && idPersonaAlumno !== null && String(idPersonaAlumno).trim() !== '') {
                    ids.push(String(idPersonaAlumno));
                }
                listaTutoresDocumentosActual.forEach(function (t) {
                    if (t.id !== undefined && t.id !== null) {
                        ids.push(String(t.id));
                    }
                });
                return ids.filter(function (v, i, a) {
                    return a.indexOf(v) === i;
                });
            };

            const nombreArchivoDocumento = function (d) {
                if (d.nombre_archivo) {
                    return String(d.nombre_archivo);
                }
                if (d.ruta_documento) {
                    const p = String(d.ruta_documento);
                    const i = Math.max(p.lastIndexOf('/'), p.lastIndexOf('\\'));
                    return i >= 0 ? p.slice(i + 1) : p;
                }
                return '';
            };

            const renderTablasDocumentos = function (lista, idPersonaAlumno) {
                if (!tbodyDocumentosAlumno || !tbodyDocumentosTutores) {
                    return;
                }

                const idAlStr = String(idPersonaAlumno);
                const docsAlumno = (lista || []).filter(function (d) {
                    return String(d.id_persona) === idAlStr;
                });
                const docsTutores = (lista || []).filter(function (d) {
                    return String(d.id_persona) !== idAlStr;
                });

                const llenar = function (tbody, docs) {
                    tbody.innerHTML = '';
                    docs.forEach(function (d) {
                        const tr = document.createElement('tr');
                        tr.className = 'boder-sec';
                        const fa = d.fecha_alta ? String(d.fecha_alta).replace('T', ' ').slice(0, 10) : '—';
                        const nomArch = nombreArchivoDocumento(d);

                        const tdDoc = document.createElement('td');
                        tdDoc.className = 'text-truncate';
                        tdDoc.textContent = d.id_documento != null ? String(d.id_documento) : '';
                        tr.appendChild(tdDoc);

                        const tdPer = document.createElement('td');
                        tdPer.className = 'text-truncate';
                        const nomPersona = (d.tipos_persona && String(d.tipos_persona).trim()) ? String(d.tipos_persona).trim() : '—';
                        tdPer.textContent = nomPersona;
                        tr.appendChild(tdPer);

                        const tdFa = document.createElement('td');
                        tdFa.className = 'text-truncate';
                        tdFa.textContent = fa;
                        tr.appendChild(tdFa);

                        const tdEst = document.createElement('td');
                        tdEst.className = 'text-truncate';
                        const estTxt = (d.estado_documento && String(d.estado_documento).trim()) ? String(d.estado_documento).trim() : 'a';
                        tdEst.textContent = estTxt;
                        tr.appendChild(tdEst);

                        const tdTipo = document.createElement('td');
                        tdTipo.className = 'text-truncate';
                        tdTipo.textContent = d.doc_label || '—';
                        tr.appendChild(tdTipo);

                        const tdArch = document.createElement('td');
                        tdArch.className = 'text-truncate';
                        const urlVer = d.url_visualizar || d.url_descarga;
                        const urlDown = d.url_descargar;
                        if (nomArch && urlVer) {
                            const aVer = document.createElement('a');
                            aVer.href = urlVer;
                            aVer.target = '_blank';
                            aVer.rel = 'noopener noreferrer';
                            aVer.className = 'me-2';
                            aVer.textContent = 'Ver';
                            tdArch.appendChild(aVer);
                            const spanNom = document.createElement('span');
                            spanNom.className = 'text-muted small';
                            spanNom.textContent = nomArch;
                            tdArch.appendChild(spanNom);
                            if (urlDown) {
                                const aDown = document.createElement('a');
                                aDown.href = urlDown;
                                aDown.className = 'd-block small mt-1';
                                aDown.textContent = 'Descargar';
                                tdArch.appendChild(aDown);
                            }
                        } else {
                            tdArch.textContent = '—';
                        }
                        tr.appendChild(tdArch);

                        const tdAcc = document.createElement('td');
                        tdAcc.className = 'text-end text-nowrap';
                        const btnEd = document.createElement('button');
                        btnEd.type = 'button';
                        btnEd.className = 'btn btn-primary btn-sm m-1 btn-editar-documento-persona';
                        btnEd.dataset.id = String(d.id);
                        btnEd.innerHTML = '<i class="fa-solid fa-pen fs-8"></i> Editar';
                        tdAcc.appendChild(btnEd);

                        const btnDel = document.createElement('button');
                        btnDel.type = 'button';
                        btnDel.className = 'btn btn-danger btn-sm m-1 btn-eliminar-documento-persona';
                        btnDel.dataset.id = String(d.id);
                        btnDel.title = 'Eliminar documento y archivo';
                        btnDel.innerHTML = '<i class="fa-solid fa-trash fs-8"></i>';
                        tdAcc.appendChild(btnDel);

                        tr.appendChild(tdAcc);

                        tbody.appendChild(tr);
                    });
                };

                llenar(tbodyDocumentosAlumno, docsAlumno);
                llenar(tbodyDocumentosTutores, docsTutores);

                if (documentosAlumnoVacio) {
                    documentosAlumnoVacio.style.display = docsAlumno.length ? 'none' : 'block';
                }
                if (documentosTutoresVacio) {
                    documentosTutoresVacio.style.display = docsTutores.length ? 'none' : 'block';
                }
            };

            const cargarTablasDocumentosDesdeApi = async function (alumnoId, idPersonaAlumno) {
                const ids = construirIdPersonasParaDocumentos(idPersonaAlumno);
                if (!ids.length) {
                    renderTablasDocumentos([], idPersonaAlumno);
                    return;
                }

                try {
                    const r = await fetch(apiPersonasDocumentos + '?id_personas=' + encodeURIComponent(ids.join(',')), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudieron cargar los documentos.');
                        renderTablasDocumentos([], idPersonaAlumno);
                        return;
                    }

                    renderTablasDocumentos(j.data || [], idPersonaAlumno);
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar documentos.');
                    renderTablasDocumentos([], idPersonaAlumno);
                }
            };

            async function abrirEdicionDocumentoPersona(id) {
                try {
                    const r = await fetch(apiPersonasDocumentos + '/' + encodeURIComponent(id), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar el documento.');
                        return;
                    }

                    const d = j.data;
                    if (personaDocumentoEditId) {
                        personaDocumentoEditId.value = String(d.id);
                    }

                    const selDoc = document.getElementById('doc_id_documento');
                    const selPer = document.getElementById('doc_id_persona_destino');
                    if (selDoc) {
                        selDoc.value = String(d.id_documento);
                    }
                    if (selPer) {
                        selPer.value = String(d.id_persona);
                        selPer.setAttribute('disabled', 'disabled');
                    }

                    const elFecha = document.getElementById('doc_fecha_alta');
                    if (elFecha) {
                        elFecha.value = d.fecha_alta ? String(d.fecha_alta).slice(0, 10) : '';
                    }

                    const elEst = document.getElementById('doc_estado_documento');
                    if (elEst) {
                        const ev = (d.estado_documento || '').trim();
                        elEst.value = ev || 'a';
                    }

                    if (docArchivoInput) {
                        docArchivoInput.value = '';
                        docArchivoInput.removeAttribute('required');
                    }
                    if (docArchivoRequiredMark) {
                        docArchivoRequiredMark.classList.add('d-none');
                    }
                    if (tituloFormDocumentoPersona) {
                        tituloFormDocumentoPersona.innerHTML = '<i class="fa-solid fa-pen me-2"></i>Editar documento';
                    }
                    if (btnCancelarEdicionDocumento) {
                        btnCancelarEdicionDocumento.style.display = '';
                    }
                    if (btnSubmitDocumentoPersona) {
                        btnSubmitDocumentoPersona.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Actualizar documento';
                    }
                    if (formDocumentoPersona) {
                        formDocumentoPersona.classList.remove('was-validated');
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar el documento.');
                }
            }

            async function eliminarDocumentoPersona(idDoc) {
                if (!window.confirm('¿Eliminar este documento? Se borrará el registro y el archivo en disco.')) {
                    return;
                }

                try {
                    const r = await fetch(apiPersonasDocumentos + '/' + encodeURIComponent(idDoc), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        }
                    });
                    let j = {};
                    try {
                        j = await r.json();
                    } catch (e) {
                        j = {};
                    }

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo eliminar el documento.');
                        return;
                    }

                    if (personaDocumentoEditId && String(personaDocumentoEditId.value) === String(idDoc)) {
                        const alumnoId = documentosAlumnoId ? documentosAlumnoId.value : '';
                        const idPerAl = documentosIdPersonaAlumno ? documentosIdPersonaAlumno.value : '';
                        const nombre = tituloAlumnoDocumentos ? tituloAlumnoDocumentos.textContent : 'Alumno';
                        resetFormDocumentoPersonaAlta();
                        if (formDocumentoPersona) {
                            formDocumentoPersona.reset();
                        }
                        if (documentosAlumnoId) {
                            documentosAlumnoId.value = alumnoId;
                        }
                        if (documentosIdPersonaAlumno) {
                            documentosIdPersonaAlumno.value = idPerAl;
                        }
                        await poblarSelectPersonaDocumento(alumnoId, idPerAl, nombre);
                        void cargarSelectTiposDocumentoActivos();
                        if (formDocumentoPersona) {
                            formDocumentoPersona.classList.remove('was-validated');
                        }
                    }

                    const aid = documentosAlumnoId ? documentosAlumnoId.value : '';
                    const pid = documentosIdPersonaAlumno ? documentosIdPersonaAlumno.value : '';
                    await cargarTablasDocumentosDesdeApi(aid, pid);
                    window.alert((j && j.message) ? j.message : 'Documento eliminado correctamente.');
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al eliminar el documento.');
                }
            }

            const aplicarFooterModoTipoDocumento = function (esEdicion) {
                if (btnSubmitTipoDocumento) {
                    if (esEdicion) {
                        btnSubmitTipoDocumento.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Actualizar';
                    } else {
                        btnSubmitTipoDocumento.innerHTML = '<i class="fa-solid fa-check"></i> Agregar tipo de documento';
                    }
                }
                if (btnLimpiarEdicionTipoDocumento) {
                    btnLimpiarEdicionTipoDocumento.style.display = esEdicion ? '' : 'none';
                }
            };

            const resetFormTipoDocumentoAlta = function () {
                if (documentoEditId) {
                    documentoEditId.value = '';
                }
                if (modalTituloTipoDocumento) {
                    modalTituloTipoDocumento.innerHTML = '<i class="fa-solid fa-file-circle-plus me-2"></i>Alta de nuevo tipo de documento';
                }
                aplicarFooterModoTipoDocumento(false);
                if (formNuevoTipoDocumento) {
                    formNuevoTipoDocumento.reset();
                    formNuevoTipoDocumento.classList.remove('was-validated');
                }
            };

            const aplicarFiltroTiposDocumentoModal = function () {
                const input = document.getElementById('buscadorTiposDocumentoModal');
                if (!input || !tbodyTiposDocumento) {
                    return;
                }

                const termino = input.value.toLowerCase().trim();

                tbodyTiposDocumento.querySelectorAll('tr').forEach(function (fila) {
                    const texto = fila.textContent.toLowerCase();
                    fila.style.display = (!termino || texto.includes(termino)) ? '' : 'none';
                });
            };

            async function cargarSelectTiposDocumentoActivos(valorSeleccionado) {
                const sel = document.getElementById('doc_id_documento');
                if (!sel) {
                    return;
                }

                try {
                    const r = await fetch(apiDocumentos + '?solo_activas=1', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudieron cargar los tipos de documento.');
                        return;
                    }

                    const valGuardado = valorSeleccionado !== undefined && valorSeleccionado !== null ? String(valorSeleccionado) : sel.value;

                    sel.innerHTML = '<option value="" selected disabled>Selecciona</option>';

                    (j.data || []).forEach(function (d) {
                        const o = document.createElement('option');
                        o.value = String(d.id);
                        o.textContent = d.id + ' — ' + (d.nombre_documento || '');
                        sel.appendChild(o);
                    });

                    if (valGuardado) {
                        const existe = Array.prototype.some.call(sel.options, function (opt) {
                            return opt.value === valGuardado;
                        });
                        if (existe) {
                            sel.value = valGuardado;
                        }
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar tipos de documento.');
                }
            }

            async function renderTablaTiposDocumentoModal() {
                if (!tbodyTiposDocumento) {
                    return;
                }

                try {
                    const r = await fetch(apiDocumentos, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar el listado de tipos de documento.');
                        return;
                    }

                    tbodyTiposDocumento.innerHTML = '';

                    (j.data || []).forEach(function (d) {
                        const tr = document.createElement('tr');
                        tr.className = 'boder-sec';
                        tr.dataset.documentoId = String(d.id);

                        const tdId = document.createElement('td');
                        tdId.className = 'text-truncate';
                        tdId.textContent = d.id != null ? String(d.id) : '';
                        tr.appendChild(tdId);

                        const tdNom = document.createElement('td');
                        tdNom.className = 'text-truncate text-start';
                        tdNom.textContent = d.nombre_documento || '';
                        tr.appendChild(tdNom);

                        const tdDesc = document.createElement('td');
                        tdDesc.className = 'text-truncate';
                        tdDesc.style.maxWidth = '14rem';
                        tdDesc.textContent = d.descripcion_documento || '';
                        tr.appendChild(tdDesc);

                        const tdEst = document.createElement('td');
                        const docActivo = d.activo !== false && d.activo !== 0;
                        tdEst.className = 'text-truncate';
                        tdEst.innerHTML = '<span class="badge ' + (docActivo ? 'badge-success-dark' : 'badge-danger-dark') + ' fs-9">' +
                            (docActivo ? 'Activo' : 'Inactivo') + '</span>';
                        tr.appendChild(tdEst);

                        const tdAcc = document.createElement('td');
                        tdAcc.className = 'text-end text-nowrap';

                        const btnEdit = document.createElement('button');
                        btnEdit.type = 'button';
                        btnEdit.className = 'btn btn-primary border-0 m-1 btn-editar-tipo-documento';
                        btnEdit.title = 'Editar';
                        btnEdit.innerHTML = '<i class="fa-solid fa-pen fs-8"></i>';
                        tdAcc.appendChild(btnEdit);

                        if (docActivo) {
                            const btnBan = document.createElement('button');
                            btnBan.type = 'button';
                            btnBan.className = 'btn btn-danger m-1 btn-inactivar-tipo-documento';
                            btnBan.title = 'Inactivar';
                            btnBan.dataset.documentoId = String(d.id);
                            btnBan.innerHTML = '<i class="fa-solid fa-ban fs-8"></i>';
                            tdAcc.appendChild(btnBan);
                        }

                        tr.appendChild(tdAcc);
                        tbodyTiposDocumento.appendChild(tr);
                    });

                    aplicarFiltroTiposDocumentoModal();
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar el listado de tipos de documento.');
                }
            }

            async function abrirEdicionTipoDocumento(id) {
                try {
                    const r = await fetch(apiDocumentos + '/' + id, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo obtener el tipo de documento.');
                        return;
                    }

                    const d = j.data;
                    if (documentoEditId) {
                        documentoEditId.value = String(d.id);
                    }
                    const elNom = document.getElementById('nombre_documento_tipo');
                    const elDesc = document.getElementById('descripcion_documento_tipo');
                    if (elNom) {
                        elNom.value = d.nombre_documento || '';
                    }
                    if (elDesc) {
                        elDesc.value = d.descripcion_documento || '';
                    }
                    if (modalTituloTipoDocumento) {
                        modalTituloTipoDocumento.innerHTML = '<i class="fa-solid fa-pen me-2"></i>Editar tipo de documento';
                    }
                    aplicarFooterModoTipoDocumento(true);

                    if (window.bootstrap && modalNuevoTipoDocumento) {
                        window._tipoDocumentoModalModoAlta = false;
                        bootstrap.Modal.getOrCreateInstance(modalNuevoTipoDocumento).show();
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar el tipo de documento.');
                }
            }

            async function inactivarTipoDocumento(id) {
                try {
                    const r = await fetch(apiDocumentos + '/' + id + '/inactivar', {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo inactivar.');
                        return;
                    }

                    await cargarSelectTiposDocumentoActivos(document.getElementById('doc_id_documento') ? document.getElementById('doc_id_documento').value : '');
                    await renderTablaTiposDocumentoModal();
                    window.alert(j.message || 'Tipo de documento inactivado.');
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al inactivar el tipo de documento.');
                }
            }

            if (modalDocumentosAlumno && documentosAlumnoId && documentosIdPersonaAlumno) {
                modalDocumentosAlumno.addEventListener('show.bs.modal', async function (event) {
                    const btn = event.relatedTarget;
                    if (!btn) {
                        return;
                    }

                    const id = btn.getAttribute('data-alumno-id') || '';
                    const nombre = btn.getAttribute('data-alumno-nombre') || 'Alumno';
                    const idPerAl = btn.getAttribute('data-id-persona-alumno') || '';

                    if (tituloAlumnoDocumentos) {
                        tituloAlumnoDocumentos.textContent = nombre;
                    }

                    documentosAlumnoId.value = id;
                    documentosIdPersonaAlumno.value = idPerAl;

                    if (formDocumentoPersona) {
                        formDocumentoPersona.reset();
                        formDocumentoPersona.classList.remove('was-validated');
                        documentosAlumnoId.value = id;
                        documentosIdPersonaAlumno.value = idPerAl;
                    }

                    await poblarSelectPersonaDocumento(id, idPerAl, nombre);
                    resetFormDocumentoPersonaAlta();
                    await cargarTablasDocumentosDesdeApi(id, idPerAl);
                    void cargarSelectTiposDocumentoActivos();
                });
            }

            if (modalDocumentosAlumno) {
                modalDocumentosAlumno.addEventListener('click', function (ev) {
                    const btnEd = ev.target.closest('.btn-editar-documento-persona');
                    if (btnEd && modalDocumentosAlumno.contains(btnEd)) {
                        const docRowId = btnEd.getAttribute('data-id');
                        if (docRowId) {
                            void abrirEdicionDocumentoPersona(docRowId);
                        }
                        return;
                    }
                    const btnDel = ev.target.closest('.btn-eliminar-documento-persona');
                    if (btnDel && modalDocumentosAlumno.contains(btnDel)) {
                        const docRowIdDel = btnDel.getAttribute('data-id');
                        if (docRowIdDel) {
                            void eliminarDocumentoPersona(docRowIdDel);
                        }
                    }
                });
            }

            if (btnCancelarEdicionDocumento && formDocumentoPersona && documentosAlumnoId && documentosIdPersonaAlumno) {
                btnCancelarEdicionDocumento.addEventListener('click', async function () {
                    const alumnoId = documentosAlumnoId.value;
                    const idPerAl = documentosIdPersonaAlumno.value;
                    const nombre = tituloAlumnoDocumentos ? tituloAlumnoDocumentos.textContent : 'Alumno';
                    resetFormDocumentoPersonaAlta();
                    formDocumentoPersona.reset();
                    documentosAlumnoId.value = alumnoId;
                    documentosIdPersonaAlumno.value = idPerAl;
                    await poblarSelectPersonaDocumento(alumnoId, idPerAl, nombre);
                    void cargarSelectTiposDocumentoActivos();
                    formDocumentoPersona.classList.remove('was-validated');
                });
            }

            if (formDocumentoPersona && documentosAlumnoId && documentosIdPersonaAlumno) {
                formDocumentoPersona.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    if (!formDocumentoPersona.checkValidity()) {
                        formDocumentoPersona.classList.add('was-validated');
                        return;
                    }

                    const alumnoId = documentosAlumnoId.value;
                    const idPerAl = documentosIdPersonaAlumno.value;
                    const nombreAl = tituloAlumnoDocumentos ? tituloAlumnoDocumentos.textContent : 'Alumno';
                    const selPersona = document.getElementById('doc_id_persona_destino');
                    const optPersona = selPersona ? selPersona.options[selPersona.selectedIndex] : null;
                    let tiposPersona = '';
                    if (optPersona && optPersona.textContent) {
                        tiposPersona = optPersona.textContent.trim().substring(0, 200);
                    }
                    if (!tiposPersona) {
                        tiposPersona = 'Persona';
                    }

                    const carpetaData = optPersona && optPersona.dataset.carpeta ? String(optPersona.dataset.carpeta) : '';
                    const carpetaTutor = (carpetaData && carpetaData !== 'alumno') ? carpetaData : '';

                    const estadoDocRaw = (document.getElementById('doc_estado_documento').value || '').trim();
                    const estadoDoc = estadoDocRaw || 'a';
                    const editId = personaDocumentoEditId && personaDocumentoEditId.value ? String(personaDocumentoEditId.value).trim() : '';

                    try {
                        let r;
                        let j;

                        if (editId) {
                            const fdPatch = new FormData();
                            fdPatch.append('id_documento', document.getElementById('doc_id_documento').value);
                            fdPatch.append('tipos_persona', tiposPersona);
                            fdPatch.append('fecha_alta', document.getElementById('doc_fecha_alta').value);
                            fdPatch.append('estado_documento', estadoDoc);
                            if (docArchivoInput && docArchivoInput.files && docArchivoInput.files[0]) {
                                fdPatch.append('archivo', docArchivoInput.files[0]);
                            }

                            r = await fetch(apiPersonasDocumentos + '/' + encodeURIComponent(editId), {
                                method: 'PATCH',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': getCsrfToken()
                                },
                                body: fdPatch
                            });
                            j = await r.json();
                        } else {
                            const fd = new FormData();
                            fd.append('id_alumno', alumnoId);
                            fd.append('id_persona_alumno', idPerAl);
                            if (carpetaTutor) {
                                fd.append('carpeta_tutor', carpetaTutor);
                            }
                            fd.append('id_documento', document.getElementById('doc_id_documento').value);
                            fd.append('id_persona', document.getElementById('doc_id_persona_destino').value);
                            fd.append('tipos_persona', tiposPersona);
                            fd.append('fecha_alta', document.getElementById('doc_fecha_alta').value);
                            fd.append('estado_documento', estadoDoc);
                            if (docArchivoInput && docArchivoInput.files && docArchivoInput.files[0]) {
                                fd.append('archivo', docArchivoInput.files[0]);
                            }

                            r = await fetch(apiPersonasDocumentos, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': getCsrfToken()
                                },
                                body: fd
                            });
                            j = await r.json();
                        }

                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar el documento.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }

                        resetFormDocumentoPersonaAlta();
                        formDocumentoPersona.reset();
                        documentosAlumnoId.value = alumnoId;
                        documentosIdPersonaAlumno.value = idPerAl;
                        await poblarSelectPersonaDocumento(alumnoId, idPerAl, nombreAl);
                        void cargarSelectTiposDocumentoActivos(document.getElementById('doc_id_documento') ? document.getElementById('doc_id_documento').value : '');
                        await cargarTablasDocumentosDesdeApi(alumnoId, idPerAl);
                        window.alert(j.message || (editId ? 'Documento actualizado correctamente.' : 'Documento guardado correctamente.'));
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar el documento.');
                    }
                });
            }

            if (formTutorAlumno && tutorAlumnoId) {
                formTutorAlumno.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    if (!formTutorAlumno.checkValidity()) {
                        formTutorAlumno.classList.add('was-validated');
                        return;
                    }

                    const id = tutorAlumnoId.value;
                    const payload = {
                        nombres: document.getElementById('tutor_nombres').value.trim(),
                        apellido_paterno: document.getElementById('tutor_apellido_paterno').value.trim(),
                        apellido_materno: document.getElementById('tutor_apellido_materno').value.trim(),
                        direccion: document.getElementById('tutor_direccion').value.trim(),
                        telefono: document.getElementById('tutor_telefono').value.trim(),
                        correo: document.getElementById('tutor_correo').value.trim(),
                        parentesco: document.getElementById('tutor_parentesco').value.trim()
                    };

                    try {
                        const r = await fetch(apiAlumnos + '/' + encodeURIComponent(id) + '/tutores', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            body: JSON.stringify(payload)
                        });
                        const j = await r.json();

                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar el tutor.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }

                        formTutorAlumno.reset();
                        formTutorAlumno.classList.remove('was-validated');
                        tutorAlumnoId.value = id;
                        await cargarTablaTutoresAlumno(id);
                        window.alert(j.message || 'Tutor registrado correctamente.');
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar el tutor.');
                    }
                });
            }

            const actualizarNumeroSepSeleccionado = function () {
                if (!selectEscuela || !numeroSepSeleccionado) {
                    return;
                }

                const opcionSeleccionada = selectEscuela.options[selectEscuela.selectedIndex];
                const numeroSep = opcionSeleccionada ? opcionSeleccionada.getAttribute('data-numero-sep') : '';
                numeroSepSeleccionado.textContent = 'Numero SEP: ' + (numeroSep && numeroSep.trim() ? numeroSep : 'N/D');
            };

            const actualizarNumeroInternoEspecialidad = function () {
                if (!selectEspecialidad || !numeroInternoEspecialidadSeleccionado) {
                    return;
                }

                const opcion = selectEspecialidad.options[selectEspecialidad.selectedIndex];
                const numInt = opcion ? opcion.getAttribute('data-num-interno') : '';
                numeroInternoEspecialidadSeleccionado.textContent = 'Núm. interno especialidad: ' + (numInt && String(numInt).trim() ? numInt : 'N/D');
            };

            const getCsrfToken = function () {
                const m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            };

            const parseJsonRespCursos = async function (r) {
                const t = await r.text();
                if (!t) {
                    return {};
                }
                try {
                    return JSON.parse(t);
                } catch (e) {
                    return {};
                }
            };

            const renderTablaCursosAlumnoModal = function (lista) {
                if (!tbodyCursosAlumnoModal || !cursosAlumnoModalVacio) {
                    return;
                }
                tbodyCursosAlumnoModal.innerHTML = '';
                if (!lista || !lista.length) {
                    cursosAlumnoModalVacio.style.display = 'block';
                    return;
                }
                cursosAlumnoModalVacio.style.display = 'none';
                lista.forEach(function (c) {
                    const pivot = c.pivot || {};
                    const calRaw = pivot.calificacion;
                    const cal = (calRaw != null && String(calRaw).trim() !== '')
                        ? String(calRaw).trim()
                        : '—';
                    const tr = document.createElement('tr');
                    tr.className = 'boder-sec';
                    tr.innerHTML = '<td class="text-truncate text-start">' + escapeHtmlTutor(c.nombre || '—') + '</td>' +
                        '<td class="text-truncate">' + escapeHtmlTutor(c.tipo_curso || '—') + '</td>' +
                        '<td class="text-truncate">' + escapeHtmlTutor(c.instructor || '—') + '</td>' +
                        '<td class="text-truncate"><input type="text" class="form-control form-control-sm input-calificacion-curso" data-curso-id="' + escapeHtmlTutor(String(c.id)) + '" value="' + (cal === '—' ? '' : escapeHtmlTutor(cal)) + '" maxlength="50" placeholder="Sin calificación"></td>' +
                        '<td class="text-end text-nowrap">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary m-1 btn-guardar-calificacion-curso" data-curso-id="' + escapeHtmlTutor(String(c.id)) + '" title="Guardar calificación"><i class="fa-solid fa-floppy-disk"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger m-1 btn-quitar-curso-alumno" data-curso-id="' + escapeHtmlTutor(String(c.id)) + '" title="Quitar asignación">' +
                        '<i class="fa-solid fa-times"></i></button></td>';
                    tbodyCursosAlumnoModal.appendChild(tr);
                });
            };

            const refrescarSelectCursosAsignar = async function (alumnoId, idsAsignadosSet) {
                if (!selectCursoAsignar) {
                    return;
                }
                selectCursoAsignar.innerHTML = '<option value="">Selecciona un curso…</option>';
                try {
                    const r = await fetch(apiCursos, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonRespCursos(r);
                    if (!r.ok) {
                        return;
                    }
                    const todos = j.data || [];
                    todos.forEach(function (cur) {
                        if (idsAsignadosSet.has(String(cur.id))) {
                            return;
                        }
                        const opt = document.createElement('option');
                        opt.value = String(cur.id);
                        opt.textContent = (cur.nombre || ('Curso #' + cur.id));
                        selectCursoAsignar.appendChild(opt);
                    });
                } catch (e) {
                    console.error(e);
                }
            };

            const cargarModalCursosAlumno = async function (alumnoId) {
                const aid = String(alumnoId || '').trim();
                if (!tbodyCursosAlumnoModal) {
                    return;
                }
                tbodyCursosAlumnoModal.innerHTML = '';
                if (cursosAlumnoModalVacio) {
                    cursosAlumnoModalVacio.style.display = 'block';
                }
                if (!aid) {
                    if (selectCursoAsignar) {
                        selectCursoAsignar.innerHTML = '<option value="">Selecciona un curso…</option>';
                    }
                    return;
                }
                try {
                    const r = await fetch(apiAlumnos + '/' + encodeURIComponent(aid) + '/cursos', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await parseJsonRespCursos(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudieron cargar los cursos del alumno.');
                        renderTablaCursosAlumnoModal([]);
                        await refrescarSelectCursosAsignar(aid, new Set());
                        return;
                    }
                    const lista = j.data || [];
                    const idsSet = new Set(lista.map(function (x) {
                        return String(x.id);
                    }));
                    renderTablaCursosAlumnoModal(lista);
                    await refrescarSelectCursosAsignar(aid, idsSet);
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar cursos del alumno.');
                }
            };

            if (modalCursosAlumno && cursosModalAlumnoId) {
                modalCursosAlumno.addEventListener('show.bs.modal', function (event) {
                    const btn = event.relatedTarget;
                    if (!btn) {
                        return;
                    }
                    const id = btn.getAttribute('data-alumno-id') || '';
                    const nombre = btn.getAttribute('data-alumno-nombre') || 'Alumno';
                    if (tituloAlumnoCursos) {
                        tituloAlumnoCursos.textContent = nombre;
                    }
                    cursosModalAlumnoId.value = id;
                    if (selectCursoAsignar) {
                        selectCursoAsignar.value = '';
                    }
                    void cargarModalCursosAlumno(id);
                });
            }

            if (btnAsignarCursoAlumno) {
                btnAsignarCursoAlumno.addEventListener('click', async function () {
                    const aid = cursosModalAlumnoId ? cursosModalAlumnoId.value.trim() : '';
                    const cid = selectCursoAsignar ? selectCursoAsignar.value.trim() : '';
                    if (!aid || !cid) {
                        window.alert('Selecciona un curso para asignar.');
                        return;
                    }
                    try {
                        const r = await fetch(apiAlumnos + '/' + encodeURIComponent(aid) + '/cursos', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ curso_id: parseInt(cid, 10) }),
                        });
                        const j = await parseJsonRespCursos(r);
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo asignar el curso.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        if (j.message) {
                            window.alert(j.message);
                        }
                        if (selectCursoAsignar) {
                            selectCursoAsignar.value = '';
                        }
                        await cargarModalCursosAlumno(aid);
                    } catch (e) {
                        console.error(e);
                        window.alert('Error de red al asignar el curso.');
                    }
                });
            }

            if (tbodyCursosAlumnoModal) {
                tbodyCursosAlumnoModal.addEventListener('click', async function (ev) {
                    const bg = ev.target.closest('.btn-guardar-calificacion-curso');
                    if (bg) {
                        const cidG = bg.getAttribute('data-curso-id');
                        const aidG = cursosModalAlumnoId ? cursosModalAlumnoId.value.trim() : '';
                        if (!cidG || !aidG) {
                            return;
                        }
                        const inputCal = tbodyCursosAlumnoModal.querySelector('.input-calificacion-curso[data-curso-id="' + String(cidG).replace(/"/g, '') + '"]');
                        const calVal = inputCal ? String(inputCal.value || '').trim() : '';
                        try {
                            const r = await fetch(apiAlumnos + '/' + encodeURIComponent(aidG) + '/cursos/' + encodeURIComponent(cidG), {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': getCsrfToken(),
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({ calificacion: calVal !== '' ? calVal : null }),
                            });
                            const j = await parseJsonRespCursos(r);
                            if (!r.ok) {
                                let msg = (j && j.message) ? j.message : 'No se pudo actualizar la calificación.';
                                if (j && j.errors) {
                                    msg = Object.values(j.errors).flat().join('\n');
                                }
                                window.alert(msg);
                                return;
                            }
                            if (j.message) {
                                window.alert(j.message);
                            }
                            await cargarModalCursosAlumno(aidG);
                        } catch (e) {
                            console.error(e);
                            window.alert('Error de red al actualizar la calificación.');
                        }
                        return;
                    }

                    const bq = ev.target.closest('.btn-quitar-curso-alumno');
                    if (!bq) {
                        return;
                    }
                    const cid = bq.getAttribute('data-curso-id');
                    const aid = cursosModalAlumnoId ? cursosModalAlumnoId.value.trim() : '';
                    if (!cid || !aid) {
                        return;
                    }
                    if (!window.confirm('¿Quitar este curso del alumno?')) {
                        return;
                    }
                    try {
                        const r = await fetch(apiAlumnos + '/' + encodeURIComponent(aid) + '/cursos/' + encodeURIComponent(cid), {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                            },
                        });
                        const j = await parseJsonRespCursos(r);
                        if (!r.ok) {
                            window.alert((j && j.message) ? j.message : 'No se pudo quitar el curso.');
                            return;
                        }
                        await cargarModalCursosAlumno(aid);
                    } catch (e) {
                        console.error(e);
                        window.alert('Error de red.');
                    }
                });
            }

            const renderizarTablaAlumnos = function (lista) {
                if (!tbodyAlumnosListado) {
                    return;
                }

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.destroy('#tableAlumnos');
                }

                tbodyAlumnosListado.innerHTML = '';
                const esApellidoVacio = function (txt) {
                    const t = (txt == null ? '' : String(txt)).trim();
                    return t === '' || t === '.' || t === '-' || t === '—';
                };

                (lista || []).forEach(function (a) {
                    const apParts = [a.apellido_paterno, a.apellido_materno].filter(function (x) {
                        return !esApellidoVacio(x);
                    });
                    const apStr = apParts.length ? apParts.join(' ') : '—';

                    let nombreCompleto = (a.nombres || '').trim();
                    if (apStr && apStr !== '—') {
                        nombreCompleto = (nombreCompleto + ' ' + apStr).trim();
                    }

                    const fechaNac = a.fecha_nacimiento
                        ? String(a.fecha_nacimiento).replace('T', ' ').slice(0, 10)
                        : '—';
                    const becaTxt = (a.id_beca !== null && a.id_beca !== undefined && a.id_beca !== '') ? String(a.id_beca) : '—';
                    const telTxt = (a.telefono && String(a.telefono).trim()) ? String(a.telefono).trim() : '—';

                    const tr = document.createElement('tr');
                    tr.className = 'boder-sec';
                    tr.dataset.alumnoId = String(a.id);
                    tr.dataset.idPersonaAlumno = String(a.id);

                    const tdMat = document.createElement('td');
                    tdMat.className = 'text-truncate';
                    tdMat.textContent = a.numero_matricula != null
                        ? String(a.numero_matricula).padStart(10, '0')
                        : '';
                    tr.appendChild(tdMat);

                    const tdNom = document.createElement('td');
                    tdNom.className = 'text-truncate text-start';
                    tdNom.textContent = (a.nombres || '').trim();
                    tr.appendChild(tdNom);

                    const tdEsp = document.createElement('td');
                    tdEsp.className = 'text-truncate';
                    tdEsp.textContent = (a.especialidad && a.especialidad.nombre_especialidad)
                        ? String(a.especialidad.nombre_especialidad).trim()
                        : '—';
                    tr.appendChild(tdEsp);

                    const tdEmp = document.createElement('td');
                    tdEmp.className = 'text-truncate';
                    tdEmp.textContent = (a.empresa && a.empresa.nombre)
                        ? String(a.empresa.nombre).trim()
                        : '—';
                    tr.appendChild(tdEmp);

                    const tdEsc = document.createElement('td');
                    tdEsc.className = 'text-truncate';
                    tdEsc.textContent = (a.escuela && a.escuela.nombre)
                        ? String(a.escuela.nombre).trim()
                        : '—';
                    tr.appendChild(tdEsc);

                    const tdCor = document.createElement('td');
                    tdCor.className = 'text-truncate';
                    tdCor.textContent = (a.correo || '').trim();
                    tr.appendChild(tdCor);

                    const tdFn = document.createElement('td');
                    tdFn.className = 'text-truncate';
                    tdFn.textContent = fechaNac;
                    tr.appendChild(tdFn);

                    const tdBeca = document.createElement('td');
                    tdBeca.className = 'text-truncate';
                    tdBeca.textContent = becaTxt;
                    tr.appendChild(tdBeca);

                    const tdEstado = document.createElement('td');
                    tdEstado.className = 'text-truncate';
                    const estadoTxt = (a.estado !== null && a.estado !== undefined && String(a.estado).trim() !== '')
                        ? String(a.estado).trim()
                        : '—';
                    const docsPendientes = Boolean(a.docs_pendientes);
                    const faltanA = Number(a.docs_faltantes_alumno || 0);
                    const faltanT = Number(a.docs_faltantes_tutor || 0);
                    tdEstado.innerHTML = '<span class="badge badge-secondary fs-9">' + escapeHtmlTutor(estadoTxt) + '</span>';
                    if (docsPendientes) {
                        const docsPendientesLabel = document.createElement('small');
                        docsPendientesLabel.className = 'text-muted d-block mt-1';
                        docsPendientesLabel.title = 'Documentos obligatorios faltantes: ' +
                            (faltanA > 0 ? ('alumno ' + String(faltanA)) : 'alumno 0') +
                            ', ' +
                            (faltanT > 0 ? ('tutor(es) ' + String(faltanT)) : 'tutor 0');

                        const pendientes = [];
                        if (faltanA > 0) {
                            pendientes.push('Alumno ' + String(faltanA));
                        }
                        if (faltanT > 0) {
                            pendientes.push('Tutor ' + String(faltanT));
                        }

                        docsPendientesLabel.innerHTML = '<i class="fa-solid fa-file-lines me-1"></i>Documentos pendientes - ' +
                            escapeHtmlTutor(pendientes.length ? pendientes.join(' - ') : 'Sin detalle');
                        tdEstado.appendChild(docsPendientesLabel);
                    }
                    tr.appendChild(tdEstado);

                    const tdTel = document.createElement('td');
                    tdTel.className = 'text-truncate';
                    tdTel.textContent = telTxt;
                    tr.appendChild(tdTel);

                    const tdOp = document.createElement('td');
                    tdOp.className = 'text-truncate text-nowrap';

                    const btnDoc = document.createElement('button');
                    btnDoc.type = 'button';
                    btnDoc.className = 'btn btn-info text-light border-0 m-1 alumno-option-btn--docs btn-documentos-alumno';
                    btnDoc.setAttribute('data-bs-toggle', 'modal');
                    btnDoc.setAttribute('data-bs-target', '#modalDocumentosAlumno');
                    btnDoc.setAttribute('data-alumno-id', String(a.id));
                    btnDoc.setAttribute('data-alumno-nombre', nombreCompleto);
                    btnDoc.setAttribute('data-id-persona-alumno', String(a.id));
                    btnDoc.title = 'Documentos';
                    btnDoc.innerHTML = '<i class="fa-solid fa-file-lines fs-8"></i>';

                    const btnCursosAl = document.createElement('button');
                    btnCursosAl.type = 'button';
                    btnCursosAl.className = 'btn btn-warning text-light border-0 m-1 alumno-option-btn--cursos btn-cursos-alumno';
                    btnCursosAl.setAttribute('data-bs-toggle', 'modal');
                    btnCursosAl.setAttribute('data-bs-target', '#modalCursosAlumno');
                    btnCursosAl.setAttribute('data-alumno-id', String(a.id));
                    btnCursosAl.setAttribute('data-alumno-nombre', nombreCompleto);
                    btnCursosAl.title = 'Cursos';
                    btnCursosAl.innerHTML = '<i class="fa-solid fa-book-open fs-8"></i>';

                    const btnTut = document.createElement('button');
                    btnTut.type = 'button';
                    btnTut.className = 'btn btn-success border-0 m-1 alumno-option-btn--tutores btn-tutores-alumno';
                    btnTut.setAttribute('data-bs-toggle', 'modal');
                    btnTut.setAttribute('data-bs-target', '#modalTutoresAlumno');
                    btnTut.setAttribute('data-alumno-id', String(a.id));
                    btnTut.setAttribute('data-alumno-nombre', nombreCompleto);
                    btnTut.title = 'Tutores';
                    btnTut.innerHTML = '<i class="fa-solid fa-users fs-8"></i>';

                    const btnEd = document.createElement('button');
                    btnEd.type = 'button';
                    btnEd.className = 'btn btn-primary border-0 m-1 alumno-option-btn--editar btn-editar-alumno';
                    btnEd.title = 'Editar';
                    btnEd.dataset.alumnoId = String(a.id);
                    btnEd.innerHTML = '<i class="fa-solid fa-pen fs-8"></i>';

                    const btnBan = document.createElement('button');
                    btnBan.type = 'button';
                    btnBan.className = 'btn btn-danger m-1 alumno-option-btn--bloqueado btn-inactivar-alumno';
                    btnBan.title = 'Inactivar alumno';
                    btnBan.dataset.alumnoId = String(a.id);
                    btnBan.dataset.alumnoNombre = nombreCompleto;
                    btnBan.disabled = String(estadoTxt || '').trim().toLowerCase() === 'inactivo';
                    btnBan.innerHTML = '<i class="fa-solid fa-ban fs-8"></i>';

                    tdOp.appendChild(btnDoc);
                    tdOp.appendChild(btnCursosAl);
                    tdOp.appendChild(btnTut);
                    tdOp.appendChild(btnEd);
                    tdOp.appendChild(btnBan);
                    tr.appendChild(tdOp);
                    tr.insertBefore(tdOp, tr.firstChild);
                    tr.insertBefore(tdEstado, tdEsp);

                    tbodyAlumnosListado.appendChild(tr);
                });

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.init('#tableAlumnos');
                }
            };

            const aplicarFiltroTablaAlumnos = function () {
                if (!buscador || !tbodyAlumnosListado) {
                    return;
                }

                const termino = buscador.value.toLowerCase().trim();

                tbodyAlumnosListado.querySelectorAll('tr').forEach(function (fila) {
                    const texto = fila.textContent.toLowerCase();
                    fila.style.display = (!termino || texto.includes(termino)) ? '' : 'none';
                });
            };

            const cargarTablaAlumnosDesdeApi = async function () {
                if (!tbodyAlumnosListado) {
                    return;
                }

                try {
                    const r = await fetch(apiAlumnos, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar el listado de alumnos.');
                        return;
                    }

                    renderizarTablaAlumnos(j.data || []);
                    aplicarFiltroTablaAlumnos();
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar alumnos.');
                }
            };

            const aplicarFooterModoEscuela = function (esEdicion) {
                if (btnSubmitEscuela) {
                    if (esEdicion) {
                        btnSubmitEscuela.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Actualizar';
                    } else {
                        btnSubmitEscuela.innerHTML = '<i class="fa-solid fa-check"></i> Agregar escuela';
                    }
                }
                if (btnLimpiarEdicionEscuela) {
                    btnLimpiarEdicionEscuela.style.display = esEdicion ? '' : 'none';
                }
            };

            const resetFormEscuelaAlta = function () {
                if (escuelaEditId) {
                    escuelaEditId.value = '';
                }
                if (modalTituloEscuela) {
                    modalTituloEscuela.innerHTML = '<i class="fa-solid fa-school me-2"></i>Alta de nueva escuela';
                }
                aplicarFooterModoEscuela(false);
                if (formNuevaEscuela) {
                    formNuevaEscuela.reset();
                    formNuevaEscuela.classList.remove('was-validated');
                }
            };

            const cargarSelectEscuelasActivas = async function (valorSeleccionado, opts) {
                const selectTarget = (opts && opts.select) ? opts.select : selectEscuela;
                const sepTarget = (opts && Object.prototype.hasOwnProperty.call(opts || {}, 'sepSpan')) ? opts.sepSpan : numeroSepSeleccionado;

                if (!selectTarget) {
                    return;
                }

                try {
                    const r = await fetch(apiEscuelas + '?solo_activas=1', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudieron cargar las escuelas.');
                        return;
                    }

                    selectTarget.innerHTML = '<option value="" selected>Selecciona una escuela</option>';

                    (j.data || []).forEach(function (e) {
                        const o = document.createElement('option');
                        o.value = String(e.id);
                        o.textContent = e.nombre;
                        o.setAttribute('data-numero-sep', e.numero_sep ? String(e.numero_sep) : '');
                        selectTarget.appendChild(o);
                    });

                    if (valorSeleccionado !== undefined && valorSeleccionado !== null && valorSeleccionado !== '') {
                        const existe = Array.prototype.some.call(selectTarget.options, function (opt) {
                            return opt.value === String(valorSeleccionado);
                        });
                        if (existe) {
                            selectTarget.value = String(valorSeleccionado);
                        }
                    }

                    if (sepTarget) {
                        const opcionSeleccionada = selectTarget.options[selectTarget.selectedIndex];
                        const numeroSep = opcionSeleccionada ? opcionSeleccionada.getAttribute('data-numero-sep') : '';
                        sepTarget.textContent = 'Numero SEP: ' + (numeroSep && numeroSep.trim() ? numeroSep : 'N/D');
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar escuelas.');
                }
            };

            const aplicarFiltroEscuelasModal = function () {
                const input = document.getElementById('buscadorEscuelasModal');
                if (!input || !tbodyEscuelasModal) {
                    return;
                }

                const termino = input.value.toLowerCase().trim();

                tbodyEscuelasModal.querySelectorAll('tr').forEach(function (fila) {
                    const texto = fila.textContent.toLowerCase();
                    fila.style.display = (!termino || texto.includes(termino)) ? '' : 'none';
                });
            };

            const buscadorEscuelasModalEl = document.getElementById('buscadorEscuelasModal');
            if (buscadorEscuelasModalEl) {
                buscadorEscuelasModalEl.addEventListener('input', aplicarFiltroEscuelasModal);
            }

            if (btnLimpiarEdicionEscuela) {
                btnLimpiarEdicionEscuela.addEventListener('click', function () {
                    resetFormEscuelaAlta();
                });
            }

            const renderTablaEscuelasModal = async function () {
                if (!tbodyEscuelasModal) {
                    return;
                }

                try {
                    const r = await fetch(apiEscuelas, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar el listado de escuelas.');
                        return;
                    }

                    tbodyEscuelasModal.innerHTML = '';

                    (j.data || []).forEach(function (e) {
                        const tr = document.createElement('tr');
                        tr.className = 'boder-sec';
                        tr.dataset.escuelaId = String(e.id);

                        const tdNom = document.createElement('td');
                        tdNom.className = 'text-truncate text-start';
                        tdNom.textContent = e.nombre || '';
                        tr.appendChild(tdNom);

                        const tdDir = document.createElement('td');
                        tdDir.className = 'text-truncate';
                        tdDir.style.maxWidth = '8rem';
                        tdDir.textContent = e.Direccion || '';
                        tr.appendChild(tdDir);

                        const tdSep = document.createElement('td');
                        tdSep.className = 'text-truncate';
                        tdSep.textContent = e.numero_sep || '';
                        tr.appendChild(tdSep);

                        const tdTel = document.createElement('td');
                        tdTel.className = 'text-truncate';
                        tdTel.textContent = e.telefono || '';
                        tr.appendChild(tdTel);

                        const tdEst = document.createElement('td');
                        const escuelaActiva = e.activo !== false && e.activo !== 0;
                        tdEst.className = 'text-truncate';
                        tdEst.innerHTML = '<span class="badge ' + (escuelaActiva ? 'badge-success-dark' : 'badge-danger-dark') + ' fs-9">' +
                            (escuelaActiva ? 'Activo' : 'Inactivo') + '</span>';
                        tr.appendChild(tdEst);

                        const tdAcc = document.createElement('td');
                        tdAcc.className = 'text-end text-nowrap';

                        const btnEdit = document.createElement('button');
                        btnEdit.type = 'button';
                        btnEdit.className = 'btn btn-primary border-0 m-1 btn-editar-escuela';
                        btnEdit.title = 'Editar';
                        btnEdit.innerHTML = '<i class="fa-solid fa-pen fs-8"></i>';
                        tdAcc.appendChild(btnEdit);

                        if (escuelaActiva) {
                            const btnBan = document.createElement('button');
                            btnBan.type = 'button';
                            btnBan.className = 'btn btn-danger m-1 btn-inactivar-escuela';
                            btnBan.title = 'Inactivar';
                            btnBan.dataset.escuelaId = String(e.id);
                            btnBan.innerHTML = '<i class="fa-solid fa-ban fs-8"></i>';
                            tdAcc.appendChild(btnBan);
                        }

                        tr.appendChild(tdAcc);
                        tbodyEscuelasModal.appendChild(tr);
                    });

                    aplicarFiltroEscuelasModal();
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar el listado de escuelas.');
                }
            };

            const abrirEdicionEscuela = async function (id) {
                try {
                    const r = await fetch(apiEscuelas + '/' + id, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo obtener la escuela.');
                        return;
                    }

                    const e = j.data;
                    escuelaEditId.value = String(e.id);
                    nombreEscuela.value = e.nombre || '';
                    direccionEscuela.value = e.Direccion || '';
                    numeroSepEscuela.value = e.numero_sep || '';
                    telefonoEscuela.value = e.telefono || '';
                    modalTituloEscuela.innerHTML = '<i class="fa-solid fa-pen me-2"></i>Editar escuela';
                    aplicarFooterModoEscuela(true);

                    if (window.bootstrap && modalNuevaEscuela) {
                        window._escuelaModalModoAlta = false;
                        bootstrap.Modal.getOrCreateInstance(modalNuevaEscuela).show();
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar la escuela.');
                }
            };

            const inactivarEscuela = async function (id) {
                try {
                    const r = await fetch(apiEscuelas + '/' + id + '/inactivar', {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo inactivar.');
                        return;
                    }

                    await cargarSelectEscuelasActivas(selectEscuela ? selectEscuela.value : '');
                    await renderTablaEscuelasModal();
                    window.alert(j.message || 'Escuela inactivada.');
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al inactivar la escuela.');
                }
            };

            const aplicarFooterModoEspecialidad = function (esEdicion) {
                if (btnSubmitEspecialidad) {
                    if (esEdicion) {
                        btnSubmitEspecialidad.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Actualizar';
                    } else {
                        btnSubmitEspecialidad.innerHTML = '<i class="fa-solid fa-check"></i> Agregar especialidad';
                    }
                }
                if (btnLimpiarEdicionEspecialidad) {
                    btnLimpiarEdicionEspecialidad.style.display = esEdicion ? '' : 'none';
                }
            };

            const resetFormEspecialidadAlta = function () {
                if (especialidadEditId) {
                    especialidadEditId.value = '';
                }
                if (modalTituloEspecialidad) {
                    modalTituloEspecialidad.innerHTML = '<i class="fa-solid fa-graduation-cap me-2"></i>Alta de nueva especialidad';
                }
                aplicarFooterModoEspecialidad(false);
                if (formNuevaEspecialidad) {
                    formNuevaEspecialidad.reset();
                    formNuevaEspecialidad.classList.remove('was-validated');
                }
            };

            const aplicarFiltroEspecialidadesModal = function () {
                const input = document.getElementById('buscadorEspecialidadesModal');
                if (!input || !tbodyEspecialidadesModal) {
                    return;
                }

                const termino = input.value.toLowerCase().trim();

                tbodyEspecialidadesModal.querySelectorAll('tr').forEach(function (fila) {
                    const texto = fila.textContent.toLowerCase();
                    fila.style.display = (!termino || texto.includes(termino)) ? '' : 'none';
                });
            };

            if (buscadorEspecialidadesModalEl) {
                buscadorEspecialidadesModalEl.addEventListener('input', aplicarFiltroEspecialidadesModal);
            }

            if (btnLimpiarEdicionEspecialidad) {
                btnLimpiarEdicionEspecialidad.addEventListener('click', function () {
                    resetFormEspecialidadAlta();
                });
            }

            if (buscadorTiposDocumentoModalEl) {
                buscadorTiposDocumentoModalEl.addEventListener('input', aplicarFiltroTiposDocumentoModal);
            }

            if (btnLimpiarEdicionTipoDocumento) {
                btnLimpiarEdicionTipoDocumento.addEventListener('click', function () {
                    resetFormTipoDocumentoAlta();
                });
            }

            const cargarSelectEspecialidadesActivas = async function (valorSeleccionado, opts) {
                const selectTarget = (opts && opts.select) ? opts.select : selectEspecialidad;
                const internoTarget = (opts && Object.prototype.hasOwnProperty.call(opts || {}, 'internoSpan')) ? opts.internoSpan : numeroInternoEspecialidadSeleccionado;

                if (!selectTarget) {
                    return;
                }

                try {
                    const r = await fetch(apiEspecialidades + '?solo_activas=1', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudieron cargar las especialidades.');
                        return;
                    }

                    selectTarget.innerHTML = '<option value="" selected>Selecciona una especialidad</option>';

                    (j.data || []).forEach(function (e) {
                        const o = document.createElement('option');
                        o.value = String(e.id);
                        o.textContent = e.nombre_especialidad || '';
                        const numInt = e.numero_interno_especialidad != null ? String(e.numero_interno_especialidad) : '';
                        o.setAttribute('data-num-interno', numInt);
                        selectTarget.appendChild(o);
                    });

                    if (valorSeleccionado !== undefined && valorSeleccionado !== null && valorSeleccionado !== '') {
                        const existe = Array.prototype.some.call(selectTarget.options, function (opt) {
                            return opt.value === String(valorSeleccionado);
                        });
                        if (existe) {
                            selectTarget.value = String(valorSeleccionado);
                        }
                    }

                    if (internoTarget) {
                        const opcion = selectTarget.options[selectTarget.selectedIndex];
                        const numInt = opcion ? opcion.getAttribute('data-num-interno') : '';
                        internoTarget.textContent = 'Núm. interno especialidad: ' + (numInt && String(numInt).trim() ? numInt : 'N/D');
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar especialidades.');
                }
            };

            const renderTablaEspecialidadesModal = async function () {
                if (!tbodyEspecialidadesModal) {
                    return;
                }

                try {
                    const r = await fetch(apiEspecialidades, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar el listado de especialidades.');
                        return;
                    }

                    tbodyEspecialidadesModal.innerHTML = '';

                    (j.data || []).forEach(function (e) {
                        const tr = document.createElement('tr');
                        tr.className = 'boder-sec';
                        tr.dataset.especialidadId = String(e.id);

                        const tdNum = document.createElement('td');
                        tdNum.className = 'text-truncate';
                        tdNum.textContent = e.numero_interno_especialidad != null ? String(e.numero_interno_especialidad) : '';
                        tr.appendChild(tdNum);

                        const tdNom = document.createElement('td');
                        tdNom.className = 'text-truncate text-start';
                        tdNom.textContent = e.nombre_especialidad || '';
                        tr.appendChild(tdNom);

                        const tdDesc = document.createElement('td');
                        tdDesc.className = 'text-truncate';
                        tdDesc.style.maxWidth = '12rem';
                        tdDesc.textContent = e.descripcion || '';
                        tr.appendChild(tdDesc);

                        const tdEst = document.createElement('td');
                        const espActiva = e.activo !== false && e.activo !== 0;
                        tdEst.className = 'text-truncate';
                        tdEst.innerHTML = '<span class="badge ' + (espActiva ? 'badge-success-dark' : 'badge-danger-dark') + ' fs-9">' +
                            (espActiva ? 'Activo' : 'Inactivo') + '</span>';
                        tr.appendChild(tdEst);

                        const tdAcc = document.createElement('td');
                        tdAcc.className = 'text-end text-nowrap';

                        const btnEdit = document.createElement('button');
                        btnEdit.type = 'button';
                        btnEdit.className = 'btn btn-primary border-0 m-1 btn-editar-especialidad';
                        btnEdit.title = 'Editar';
                        btnEdit.innerHTML = '<i class="fa-solid fa-pen fs-8"></i>';
                        tdAcc.appendChild(btnEdit);

                        if (espActiva) {
                            const btnBan = document.createElement('button');
                            btnBan.type = 'button';
                            btnBan.className = 'btn btn-danger m-1 btn-inactivar-especialidad';
                            btnBan.title = 'Inactivar';
                            btnBan.dataset.especialidadId = String(e.id);
                            btnBan.innerHTML = '<i class="fa-solid fa-ban fs-8"></i>';
                            tdAcc.appendChild(btnBan);
                        }

                        tr.appendChild(tdAcc);
                        tbodyEspecialidadesModal.appendChild(tr);
                    });

                    aplicarFiltroEspecialidadesModal();
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar el listado de especialidades.');
                }
            };

            const abrirEdicionEspecialidad = async function (id) {
                try {
                    const r = await fetch(apiEspecialidades + '/' + id, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo obtener la especialidad.');
                        return;
                    }

                    const e = j.data;
                    especialidadEditId.value = String(e.id);
                    numeroInternoEspecialidad.value = e.numero_interno_especialidad != null ? e.numero_interno_especialidad : '';
                    nombreEspecialidad.value = e.nombre_especialidad || '';
                    descripcionEspecialidad.value = e.descripcion || '';
                    modalTituloEspecialidad.innerHTML = '<i class="fa-solid fa-pen me-2"></i>Editar especialidad';
                    aplicarFooterModoEspecialidad(true);

                    if (window.bootstrap && modalNuevaEspecialidad) {
                        window._especialidadModalModoAlta = false;
                        bootstrap.Modal.getOrCreateInstance(modalNuevaEspecialidad).show();
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar la especialidad.');
                }
            };

            const inactivarEspecialidad = async function (id) {
                try {
                    const r = await fetch(apiEspecialidades + '/' + id + '/inactivar', {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo inactivar.');
                        return;
                    }

                    await cargarSelectEspecialidadesActivas(selectEspecialidad ? selectEspecialidad.value : '');
                    await renderTablaEspecialidadesModal();
                    window.alert(j.message || 'Especialidad inactivada.');
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al inactivar la especialidad.');
                }
            };

            const abrirModalEditarAlumno = async function (id) {
                if (!formEditarAlumno || !alumnoEditId) {
                    return;
                }

                try {
                    const r = await fetch(apiAlumnos + '/' + id, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar el alumno.');
                        return;
                    }

                    const a = j.data;
                    alumnoEditId.value = String(a.id);

                    const elMat = document.getElementById('edit_numero_matricula');
                    const elSem = document.getElementById('edit_semestre');
                    const elFn = document.getElementById('edit_fecha_nacimiento');
                    const elNom = document.getElementById('edit_nombres');
                    const elAp = document.getElementById('edit_apellido_paterno');
                    const elAm = document.getElementById('edit_apellido_materno');
                    const elTel = document.getElementById('edit_telefono');
                    const elCor = document.getElementById('edit_correo');
                    const elBeca = document.getElementById('edit_id_beca');
                    const elEstado = document.getElementById('edit_estado');

                    if (elMat) {
                        elMat.value = a.numero_matricula != null ? String(a.numero_matricula) : '';
                    }
                    if (elSem) {
                        elSem.value = a.semestre != null ? String(a.semestre) : '';
                    }
                    if (elFn) {
                        elFn.value = a.fecha_nacimiento ? String(a.fecha_nacimiento).slice(0, 10) : '';
                    }
                    if (elNom) {
                        elNom.value = (a.nombres || '').trim();
                    }
                    if (elAp) {
                        elAp.value = (a.apellido_paterno || '').trim();
                    }
                    if (elAm) {
                        elAm.value = (a.apellido_materno || '').trim();
                    }
                    if (elTel) {
                        elTel.value = (a.telefono || '').trim();
                    }
                    if (elCor) {
                        elCor.value = (a.correo || '').trim();
                    }
                    if (elBeca) {
                        elBeca.value = (a.id_beca !== null && a.id_beca !== undefined && a.id_beca !== '') ? String(a.id_beca) : '';
                    }
                    if (elEstado) {
                        elEstado.value = (a.estado !== null && a.estado !== undefined) ? String(a.estado).trim() : '';
                    }

                    const idEscVal = a.id_escuela !== null && a.id_escuela !== undefined && a.id_escuela !== '' ? String(a.id_escuela) : '';
                    await cargarSelectEscuelasActivas(idEscVal, {
                        select: selectEscuelaEdit,
                        sepSpan: numeroSepSeleccionadoEdit
                    });

                    const idEspVal = a.id_especialidad != null ? a.id_especialidad : '';
                    await cargarSelectEspecialidadesActivas(idEspVal, {
                        select: selectEspecialidadEdit,
                        internoSpan: numeroInternoEspecialidadSeleccionadoEdit
                    });

                    formEditarAlumno.classList.remove('was-validated');

                    if (window.bootstrap && modalEditarAlumno) {
                        bootstrap.Modal.getOrCreateInstance(modalEditarAlumno).show();
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar el alumno.');
                }
            };

            const inactivarAlumno = async function (id, nombre) {
                if (!id) {
                    return;
                }

                const etiqueta = nombre ? (' a ' + nombre) : '';
                let confirmado = false;
                if (window.Swal) {
                    const result = await window.Swal.fire({
                        icon: 'warning',
                        title: 'Inactivar alumno',
                        text: '¿Inactivar' + etiqueta + '? El estado cambiará a "inactivo".',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, inactivar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#e60023',
                        cancelButtonColor: '#6c757d',
                    });
                    confirmado = result.isConfirmed;
                } else {
                    confirmado = window.confirm('¿Inactivar' + etiqueta + '? El estado cambiará a "inactivo".');
                }

                if (!confirmado) {
                    return;
                }

                try {
                    const r = await fetch(apiAlumnos + '/' + encodeURIComponent(id) + '/inactivar', {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken()
                        }
                    });
                    const j = await r.json();

                    if (!r.ok) {
                        if (window.Swal) {
                            await window.Swal.fire({
                                icon: 'error',
                                title: 'No se pudo inactivar',
                                text: (j && j.message) ? j.message : 'No se pudo inactivar al alumno.',
                            });
                        } else {
                            window.alert((j && j.message) ? j.message : 'No se pudo inactivar al alumno.');
                        }
                        return;
                    }

                    await cargarTablaAlumnosDesdeApi();
                    if (window.Swal) {
                        await window.Swal.fire({
                            icon: 'success',
                            title: 'Alumno inactivado',
                            text: j.message || 'Alumno inactivado correctamente.',
                        });
                    } else {
                        window.alert(j.message || 'Alumno inactivado correctamente.');
                    }
                } catch (err) {
                    console.error(err);
                    if (window.Swal) {
                        await window.Swal.fire({
                            icon: 'error',
                            title: 'Error de red',
                            text: 'Error de red al inactivar al alumno.',
                        });
                    } else {
                        window.alert('Error de red al inactivar al alumno.');
                    }
                }
            };

            document.addEventListener('click', function (ev) {
                if (ev.target.closest('.btn-editar-alumno')) {
                    ev.preventDefault();
                    const btn = ev.target.closest('.btn-editar-alumno');
                    const id = btn && btn.dataset.alumnoId;
                    if (id) {
                        void abrirModalEditarAlumno(id);
                    }
                    return;
                }
                if (ev.target.closest('.btn-inactivar-alumno')) {
                    ev.preventDefault();
                    const btn = ev.target.closest('.btn-inactivar-alumno');
                    const id = btn && btn.dataset.alumnoId;
                    const nombre = btn && btn.dataset.alumnoNombre;
                    if (id) {
                        void inactivarAlumno(id, nombre || '');
                    }
                    return;
                }
                if (ev.target.closest('.btn-editar-escuela')) {
                    ev.preventDefault();
                    const tr = ev.target.closest('tr');
                    const id = tr && tr.dataset.escuelaId;
                    if (id) {
                        abrirEdicionEscuela(id);
                    }
                    return;
                }
                if (ev.target.closest('.btn-inactivar-escuela')) {
                    ev.preventDefault();
                    const btn = ev.target.closest('.btn-inactivar-escuela');
                    const id = btn && btn.dataset.escuelaId;
                    if (!id) {
                        return;
                    }
                    if (!window.confirm('¿Inactivar esta escuela? Dejará de mostrarse en el alta de alumnos.')) {
                        return;
                    }
                    inactivarEscuela(id);
                    return;
                }
                if (ev.target.closest('.btn-editar-especialidad')) {
                    ev.preventDefault();
                    const tr = ev.target.closest('tr');
                    const id = tr && tr.dataset.especialidadId;
                    if (id) {
                        abrirEdicionEspecialidad(id);
                    }
                    return;
                }
                if (ev.target.closest('.btn-inactivar-especialidad')) {
                    ev.preventDefault();
                    const btn = ev.target.closest('.btn-inactivar-especialidad');
                    const id = btn && btn.dataset.especialidadId;
                    if (!id) {
                        return;
                    }
                    if (!window.confirm('¿Inactivar esta especialidad? Dejará de mostrarse en el alta de alumnos.')) {
                        return;
                    }
                    inactivarEspecialidad(id);
                    return;
                }
                if (ev.target.closest('.btn-editar-tipo-documento')) {
                    ev.preventDefault();
                    const tr = ev.target.closest('tr');
                    const id = tr && tr.dataset.documentoId;
                    if (id) {
                        abrirEdicionTipoDocumento(id);
                    }
                    return;
                }
                if (ev.target.closest('.btn-inactivar-tipo-documento')) {
                    ev.preventDefault();
                    const btn = ev.target.closest('.btn-inactivar-tipo-documento');
                    const id = btn && btn.dataset.documentoId;
                    if (!id) {
                        return;
                    }
                    if (!window.confirm('¿Inactivar este tipo de documento? Dejará de mostrarse en el alta de documentos del alumno.')) {
                        return;
                    }
                    inactivarTipoDocumento(id);
                    return;
                }
            });

            if (buscador) {
                buscador.addEventListener('input', aplicarFiltroTablaAlumnos);
            }

            if (formNuevaEscuela && nombreEscuela && numeroSepEscuela && selectEscuela) {
                formNuevaEscuela.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    if (!formNuevaEscuela.checkValidity()) {
                        formNuevaEscuela.classList.add('was-validated');
                        return;
                    }

                    const payload = {
                        nombre: nombreEscuela.value.trim(),
                        direccion: direccionEscuela ? direccionEscuela.value.trim() : '',
                        numero_sep: numeroSepEscuela.value.trim(),
                        telefono: telefonoEscuela ? telefonoEscuela.value.trim() : ''
                    };

                    const idEdit = escuelaEditId && escuelaEditId.value ? escuelaEditId.value : '';
                    const url = idEdit ? (apiEscuelas + '/' + idEdit) : apiEscuelas;
                    const method = idEdit ? 'PUT' : 'POST';

                    try {
                        const r = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            body: JSON.stringify(payload)
                        });
                        const j = await r.json();

                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }

                        const nuevoId = j.data && j.data.id ? j.data.id : null;
                        await cargarSelectEscuelasActivas(nuevoId || (selectEscuela ? selectEscuela.value : ''));
                        await renderTablaEscuelasModal();
                        resetFormEscuelaAlta();

                        if (window.bootstrap && modalNuevaEscuela) {
                            const instanciaModal = bootstrap.Modal.getOrCreateInstance(modalNuevaEscuela);
                            instanciaModal.hide();
                        }

                        window.alert(j.message || 'Guardado correctamente.');
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar la escuela.');
                    }
                });
            }

            const btnAbrirModalEscuela = document.querySelector('[data-bs-target="#modalNuevaEscuela"]');
            if (btnAbrirModalEscuela) {
                btnAbrirModalEscuela.addEventListener('click', function () {
                    window._escuelaModalModoAlta = true;
                });
            }

            if (modalNuevaEscuela) {
                modalNuevaEscuela.addEventListener('show.bs.modal', function () {
                    if (window._escuelaModalModoAlta) {
                        resetFormEscuelaAlta();
                        if (buscadorEscuelasModalEl) {
                            buscadorEscuelasModalEl.value = '';
                        }
                    }
                    window._escuelaModalModoAlta = false;
                    renderTablaEscuelasModal();
                });
            }

            if (selectEscuela) {
                selectEscuela.addEventListener('change', actualizarNumeroSepSeleccionado);
                actualizarNumeroSepSeleccionado();
            }

            cargarSelectEscuelasActivas();

            if (selectEspecialidad) {
                selectEspecialidad.addEventListener('change', actualizarNumeroInternoEspecialidad);
                actualizarNumeroInternoEspecialidad();
            }

            if (selectEscuelaEdit) {
                selectEscuelaEdit.addEventListener('change', function () {
                    if (!numeroSepSeleccionadoEdit) {
                        return;
                    }
                    const opcionSeleccionada = selectEscuelaEdit.options[selectEscuelaEdit.selectedIndex];
                    const numeroSep = opcionSeleccionada ? opcionSeleccionada.getAttribute('data-numero-sep') : '';
                    numeroSepSeleccionadoEdit.textContent = 'Numero SEP: ' + (numeroSep && numeroSep.trim() ? numeroSep : 'N/D');
                });
            }

            if (selectEspecialidadEdit) {
                selectEspecialidadEdit.addEventListener('change', function () {
                    if (!numeroInternoEspecialidadSeleccionadoEdit) {
                        return;
                    }
                    const opcion = selectEspecialidadEdit.options[selectEspecialidadEdit.selectedIndex];
                    const numInt = opcion ? opcion.getAttribute('data-num-interno') : '';
                    numeroInternoEspecialidadSeleccionadoEdit.textContent = 'Núm. interno especialidad: ' + (numInt && String(numInt).trim() ? numInt : 'N/D');
                });
            }

            cargarSelectEspecialidadesActivas();

            if (formNuevaEspecialidad && numeroInternoEspecialidad && nombreEspecialidad && descripcionEspecialidad) {
                formNuevaEspecialidad.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    if (!formNuevaEspecialidad.checkValidity()) {
                        formNuevaEspecialidad.classList.add('was-validated');
                        return;
                    }

                    const payload = {
                        numero_interno_especialidad: parseInt(numeroInternoEspecialidad.value, 10),
                        nombre_especialidad: nombreEspecialidad.value.trim(),
                        descripcion: descripcionEspecialidad.value.trim()
                    };

                    const idEdit = especialidadEditId && especialidadEditId.value ? especialidadEditId.value : '';
                    const url = idEdit ? (apiEspecialidades + '/' + idEdit) : apiEspecialidades;
                    const method = idEdit ? 'PUT' : 'POST';

                    try {
                        const r = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            body: JSON.stringify(payload)
                        });
                        const j = await r.json();

                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }

                        const nuevoId = j.data && j.data.id ? j.data.id : null;
                        await cargarSelectEspecialidadesActivas(nuevoId || (selectEspecialidad ? selectEspecialidad.value : ''));
                        await renderTablaEspecialidadesModal();
                        resetFormEspecialidadAlta();

                        if (window.bootstrap && modalNuevaEspecialidad) {
                            const instanciaModalEsp = bootstrap.Modal.getOrCreateInstance(modalNuevaEspecialidad);
                            instanciaModalEsp.hide();
                        }

                        window.alert(j.message || 'Guardado correctamente.');
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar la especialidad.');
                    }
                });
            }

            const btnAbrirModalEspecialidad = document.querySelector('[data-bs-target="#modalNuevaEspecialidad"]');
            if (btnAbrirModalEspecialidad) {
                btnAbrirModalEspecialidad.addEventListener('click', function () {
                    window._especialidadModalModoAlta = true;
                });
            }

            if (modalNuevaEspecialidad) {
                modalNuevaEspecialidad.addEventListener('show.bs.modal', function () {
                    if (window._especialidadModalModoAlta) {
                        resetFormEspecialidadAlta();
                        if (buscadorEspecialidadesModalEl) {
                            buscadorEspecialidadesModalEl.value = '';
                        }
                    }
                    window._especialidadModalModoAlta = false;
                    renderTablaEspecialidadesModal();
                });
            }

            const btnAbrirModalTipoDocumento = document.querySelector('[data-bs-target="#modalNuevoTipoDocumento"]');
            if (btnAbrirModalTipoDocumento) {
                btnAbrirModalTipoDocumento.addEventListener('click', function () {
                    window._tipoDocumentoModalModoAlta = true;
                });
            }

            if (modalNuevoTipoDocumento) {
                modalNuevoTipoDocumento.addEventListener('show.bs.modal', function () {
                    if (window._tipoDocumentoModalModoAlta) {
                        resetFormTipoDocumentoAlta();
                        if (buscadorTiposDocumentoModalEl) {
                            buscadorTiposDocumentoModalEl.value = '';
                        }
                    }
                    window._tipoDocumentoModalModoAlta = false;
                    void renderTablaTiposDocumentoModal();
                });
            }

            if (formNuevoTipoDocumento) {
                formNuevoTipoDocumento.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    if (!formNuevoTipoDocumento.checkValidity()) {
                        formNuevoTipoDocumento.classList.add('was-validated');
                        return;
                    }

                    const nombre = document.getElementById('nombre_documento_tipo').value.trim();
                    const descripcion = document.getElementById('descripcion_documento_tipo').value.trim();

                    const payload = {
                        nombre_documento: nombre,
                        descripcion_documento: descripcion || null
                    };

                    const idEdit = documentoEditId && documentoEditId.value ? documentoEditId.value : '';
                    const url = idEdit ? (apiDocumentos + '/' + idEdit) : apiDocumentos;
                    const method = idEdit ? 'PUT' : 'POST';

                    try {
                        const r = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            body: JSON.stringify(payload)
                        });
                        const j = await r.json();

                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }

                        const nuevoId = j.data && j.data.id ? j.data.id : null;
                        await cargarSelectTiposDocumentoActivos(nuevoId || (document.getElementById('doc_id_documento') ? document.getElementById('doc_id_documento').value : ''));
                        await renderTablaTiposDocumentoModal();
                        resetFormTipoDocumentoAlta();

                        if (window.bootstrap && modalNuevoTipoDocumento) {
                            const instModalTipoDoc = bootstrap.Modal.getOrCreateInstance(modalNuevoTipoDocumento);
                            instModalTipoDoc.hide();
                        }

                        window.alert(j.message || 'Guardado correctamente.');
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar el tipo de documento.');
                    }
                });
            }

            void cargarSelectTiposDocumentoActivos();
            void renderTablaTiposDocumentoModal();

            if (formAltaAlumno) {
                formAltaAlumno.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    if (!formAltaAlumno.checkValidity()) {
                        formAltaAlumno.classList.add('was-validated');
                        return;
                    }

                    const idEsc = selectEscuela ? selectEscuela.value : '';
                    const idEsp = selectEspecialidad ? selectEspecialidad.value : '';
                    const becaEl = formAltaAlumno.querySelector('[name="id_beca"]');
                    const rawBeca = becaEl ? becaEl.value : '';

                    const payload = {
                        numero_matricula: parseInt(formAltaAlumno.querySelector('[name="numero_matricula"]').value, 10),
                        id_especialidad: parseInt(idEsp, 10),
                        semestre: parseInt(formAltaAlumno.querySelector('[name="semestre"]').value, 10),
                        nombres: formAltaAlumno.querySelector('[name="nombres"]').value.trim(),
                        apellido_paterno: formAltaAlumno.querySelector('[name="apellido_paterno"]').value.trim(),
                        apellido_materno: (formAltaAlumno.querySelector('[name="apellido_materno"]').value || '').trim() || null,
                        id_escuela: idEsc ? idEsc : null,
                        fecha_nacimiento: (formAltaAlumno.querySelector('[name="fecha_nacimiento"]').value || '') || null,
                        telefono: (formAltaAlumno.querySelector('[name="telefono"]').value || '').trim() || null,
                        correo: formAltaAlumno.querySelector('[name="correo"]').value.trim(),
                        id_beca: rawBeca ? parseInt(rawBeca, 10) : null
                    };

                    try {
                        const r = await fetch(apiAlumnos, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            body: JSON.stringify(payload)
                        });
                        const j = await r.json();

                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar al alumno.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }

                        await window.alert(j.message || 'Alumno registrado correctamente.');
                        formAltaAlumno.reset();
                        formAltaAlumno.classList.remove('was-validated');
                        if (window.bootstrap && modalAltaAlumno) {
                            bootstrap.Modal.getOrCreateInstance(modalAltaAlumno).hide();
                        }
                        await cargarSelectEscuelasActivas();
                        await cargarSelectEspecialidadesActivas();
                        await cargarTablaAlumnosDesdeApi();
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar al alumno.');
                    }
                });
            }

            if (formEditarAlumno) {
                formEditarAlumno.addEventListener('submit', async function (event) {
                    event.preventDefault();

                    if (!formEditarAlumno.checkValidity()) {
                        formEditarAlumno.classList.add('was-validated');
                        return;
                    }

                    const idEdit = alumnoEditId && alumnoEditId.value ? alumnoEditId.value : '';
                    if (!idEdit) {
                        return;
                    }

                    const idEsc = selectEscuelaEdit ? selectEscuelaEdit.value : '';
                    const idEsp = selectEspecialidadEdit ? selectEspecialidadEdit.value : '';
                    const becaEl = document.getElementById('edit_id_beca');
                    const rawBeca = becaEl ? becaEl.value : '';

                    const payload = {
                        numero_matricula: parseInt(document.getElementById('edit_numero_matricula').value, 10),
                        id_especialidad: parseInt(idEsp, 10),
                        semestre: parseInt(document.getElementById('edit_semestre').value, 10),
                        nombres: document.getElementById('edit_nombres').value.trim(),
                        apellido_paterno: document.getElementById('edit_apellido_paterno').value.trim(),
                        apellido_materno: (document.getElementById('edit_apellido_materno').value || '').trim() || null,
                        id_escuela: idEsc ? idEsc : null,
                        fecha_nacimiento: (document.getElementById('edit_fecha_nacimiento').value || '') || null,
                        telefono: (document.getElementById('edit_telefono').value || '').trim() || null,
                        correo: document.getElementById('edit_correo').value.trim(),
                        id_beca: rawBeca ? parseInt(rawBeca, 10) : null,
                        estado: (function () {
                            const el = document.getElementById('edit_estado');
                            const t = el ? (el.value || '').trim() : '';
                            return t !== '' ? t : null;
                        })()
                    };

                    try {
                        const r = await fetch(apiAlumnos + '/' + idEdit, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            body: JSON.stringify(payload)
                        });
                        const j = await r.json();

                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo actualizar al alumno.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }

                        formEditarAlumno.classList.remove('was-validated');
                        await cargarTablaAlumnosDesdeApi();

                        if (window.bootstrap && modalEditarAlumno) {
                            bootstrap.Modal.getOrCreateInstance(modalEditarAlumno).hide();
                        }

                        window.alert(j.message || 'Alumno actualizado correctamente.');
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al actualizar al alumno.');
                    }
                });
            }

            if (formCargaMasivaAlumnos) {
                formCargaMasivaAlumnos.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formCargaMasivaAlumnos.checkValidity()) {
                        formCargaMasivaAlumnos.classList.add('was-validated');
                        return;
                    }
                    if (!archivoMasivoAlumnos || !archivoMasivoAlumnos.files || !archivoMasivoAlumnos.files[0]) {
                        window.alert('Selecciona un archivo.');
                        return;
                    }
                    try {
                        const fd = new FormData();
                        fd.append('archivo', archivoMasivoAlumnos.files[0]);
                        const r = await fetch(apiAlumnos + '/importar-masivo', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                            },
                            body: fd,
                        });
                        const j = await r.json();
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo importar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }

                        const data = (j && j.data) ? j.data : {};
                        const resumen = [
                            'Importación terminada.',
                            'Creados: ' + String(data.creados || 0),
                            'Actualizados: ' + String(data.actualizados || 0),
                            'Errores: ' + String((data.errores || []).length),
                        ];
                        if (Array.isArray(data.errores) && data.errores.length) {
                            resumen.push('', 'Detalle de errores:');
                            resumen.push.apply(resumen, data.errores.slice(0, 20));
                        }
                        window.alert(resumen.join('\n'));

                        formCargaMasivaAlumnos.reset();
                        formCargaMasivaAlumnos.classList.remove('was-validated');
                        if (window.bootstrap && modalCargaMasivaAlumnos) {
                            bootstrap.Modal.getOrCreateInstance(modalCargaMasivaAlumnos).hide();
                        }
                        await cargarTablaAlumnosDesdeApi();
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al importar alumnos.');
                    }
                });
            }

            if (modalRequisitosDocumentos) {
                modalRequisitosDocumentos.addEventListener('show.bs.modal', async function () {
                    try {
                        await cargarRequisitosDocumentos();
                        if (formRequisitosDocumentos) {
                            formRequisitosDocumentos.classList.remove('was-validated');
                        }
                    } catch (err) {
                        console.error(err);
                        window.alert((err && err.message) ? err.message : 'No se pudieron cargar los requisitos.');
                    }
                });
            }

            if (formRequisitosDocumentos) {
                formRequisitosDocumentos.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formRequisitosDocumentos.checkValidity()) {
                        formRequisitosDocumentos.classList.add('was-validated');
                        return;
                    }
                    try {
                        const alumnoIds = [];
                        const tutorIds = [];
                        formRequisitosDocumentos.querySelectorAll('.req-doc-check-alumno:checked').forEach(function (chk) {
                            const idDoc = Number(chk.value || 0);
                            if (idDoc) {
                                alumnoIds.push(idDoc);
                            }
                        });
                        formRequisitosDocumentos.querySelectorAll('.req-doc-check-tutor:checked').forEach(function (chk) {
                            const idDoc = Number(chk.value || 0);
                            if (idDoc) {
                                tutorIds.push(idDoc);
                            }
                        });
                        const payload = {
                            alumno: alumnoIds,
                            tutor: tutorIds,
                        };
                        const r = await fetch(apiAlumnos + '/requisitos-documentos', {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                            },
                            body: JSON.stringify(payload),
                        });
                        const j = await r.json();
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudieron guardar los requisitos.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        formRequisitosDocumentos.classList.remove('was-validated');
                        if (window.bootstrap && modalRequisitosDocumentos) {
                            bootstrap.Modal.getOrCreateInstance(modalRequisitosDocumentos).hide();
                        }
                        await cargarTablaAlumnosDesdeApi();
                        window.alert(j.message || 'Requisitos de documentos actualizados.');
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar requisitos.');
                    }
                });
            }

            void cargarTablaAlumnosDesdeApi();
        });
    </script>
@endsection
