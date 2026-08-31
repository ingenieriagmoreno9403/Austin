@extends('layouts.app')
@section('content')
    @include('Gestion_alumnos.partials.sweet_alerts')

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Gestión de Cursos</h2>
                            <p class="text-muted mb-0">Alta, edición y consulta de cursos y docentes.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button type="button" class="btn btn-baseColor fs-7 mb-2 text-nowrap" data-bs-toggle="modal" data-bs-target="#modalAltaCurso">
                            <i class="fa-solid fa-circle-plus"></i> Nuevo curso
                        </button>
                        <button type="button" class="btn btn-baseColor fs-7 mb-2 text-nowrap" data-bs-toggle="modal" data-bs-target="#modalDocentes">
                            <i class="fa-solid fa-chalkboard-user"></i> Docentes
                        </button>
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="javascript:history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiCursosBase = url('/Gestion_alumnos/api/cursos');
            $apiDocentesBase = url('/Gestion_alumnos/api/docentes');
        @endphp

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Listado de cursos disponibles
                </h5>
                <p class="text-muted fs-8 mb-0">Consulta, edita o elimina cursos registrados.</p>
            </div>
            <div class="card-body">
                {{-- Buscador adicional comentado: DataTables ya agrega su búsqueda principal.
                <div class="row mb-3">
                    <div class="col-md-5 col-12">
                        <div class="form-outline">
                            <label class="form-label">Buscar</label>
                            <input type="text" id="buscadorCursos" class="form-control text" placeholder="Nombre, descripción, tipo, instructor...">
                        </div>
                    </div>
                </div>
                --}}

                <div class="table-responsive">
                    <table class="table table-stripped table-hover display mb-0" id="tableCursos">
                        <thead>
                            <tr class="text-tr">
                                <th class="text-truncate">Nombre</th>
                                <th class="text-truncate">Tipo</th>
                                <th class="text-truncate">Duración</th>
                                <th class="text-truncate">Instructor</th>
                                <th class="text-truncate">Lugar</th>
                                <th class="text-truncate">Descripción</th>
                                <th class="text-truncate">Enlace</th>
                                <th class="text-truncate">Opciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyCursosListado">
                            <tr>
                                <td class="text-muted" colspan="8">Sin registros por el momento.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAltaCurso" tabindex="-1" aria-labelledby="modalAltaCursoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalAltaCursoLabel">
                            <i class="fa-solid fa-circle-plus me-2"></i>Alta de curso
                        </h5>
                        <p class="text-muted fs-8 mb-0">Captura la información principal del curso antes de publicarlo en el listado.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formCurso" class="g-3 form needs-validation" novalidate>
                    <div class="modal-body pt-0">
                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" name="nombre" maxlength="200" required>
                                    <div class="invalid-feedback">El nombre del curso es obligatorio.</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Tipo de curso</label>
                                    <input type="text" class="form-control text" name="tipo_curso" maxlength="100" placeholder="Ej. Taller, Diplomado">
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Duración</label>
                                    <input type="text" class="form-control text" name="duracion" maxlength="50" placeholder="Ej. 40 hrs">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Instructor</label>
                                    <input type="text" class="form-control text" name="instructor" maxlength="150">
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Aula o lugar</label>
                                    <input type="text" class="form-control text" name="aula_lugar" maxlength="200">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control text" name="descripcion" rows="3" placeholder="Contenido, objetivos, notas..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Enlace (URL)</label>
                                    <input type="text" class="form-control text" name="link" maxlength="300" placeholder="https://...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-baseColor fs-8 rounded-1">
                            <i class="fa-solid fa-check"></i> Guardar curso
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarCurso" tabindex="-1" aria-labelledby="modalEditarCursoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary" id="modalEditarCursoLabel">
                            <i class="fa-solid fa-pen me-2"></i>Editar curso
                        </h5>
                        <p class="text-muted fs-8 mb-0">Actualiza la información del curso seleccionado.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formEditarCurso" class="g-3 form needs-validation" novalidate>
                    <input type="hidden" id="curso_edit_id" value="">
                    <div class="modal-body pt-0">
                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" name="nombre" maxlength="200" required>
                                    <div class="invalid-feedback">El nombre del curso es obligatorio.</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Tipo de curso</label>
                                    <input type="text" class="form-control text" name="tipo_curso" maxlength="100" placeholder="Ej. Taller, Diplomado">
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Duración</label>
                                    <input type="text" class="form-control text" name="duracion" maxlength="50" placeholder="Ej. 40 hrs">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Instructor</label>
                                    <input type="text" class="form-control text" name="instructor" maxlength="150">
                                </div>
                            </div>
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Aula o lugar</label>
                                    <input type="text" class="form-control text" name="aula_lugar" maxlength="200">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control text" name="descripcion" rows="3" placeholder="Contenido, objetivos, notas..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Enlace (URL)</label>
                                    <input type="text" class="form-control text" name="link" maxlength="300" placeholder="https://...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-baseColor fs-8 rounded-1">
                            <i class="fa-solid fa-floppy-disk"></i> Actualizar curso
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Administrar Docentes --}}
    <div class="modal fade" id="modalDocentes" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary">
                            <i class="fa-solid fa-chalkboard-user me-2"></i>Administrar docentes
                        </h5>
                        <p class="text-muted fs-8 mb-0">Alta, edición y consulta de docentes disponibles.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    {{-- Sección colapsable: Formulario de docente --}}
                    <div class="mb-3">
                        <button class="btn btn-baseColor fs-7 w-100 d-flex align-items-center justify-content-between"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseFormDocente"
                                aria-expanded="false" aria-controls="collapseFormDocente" id="btnToggleFormDocente">
                            <span id="modalTituloDocente"><i class="fa-solid fa-plus"></i> Nuevo docente</span>
                            <i class="fa-solid fa-chevron-down" id="iconCollapseDocente"></i>
                        </button>
                        <div class="collapse" id="collapseFormDocente">
                            <div class="border rounded-bottom p-3 bg-body">
                                <form id="formDocente" class="g-3 form needs-validation" novalidate>
                                    <input type="hidden" id="docenteEditId" value="">
                                    <div class="row">
                                        <div class="col-md-3 col-12 mt-2">
                                            <div class="form-outline">
                                                <label class="form-label">Primer nombre <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control text" name="primer_nombre" maxlength="100" required>
                                                <div class="invalid-feedback">El primer nombre es obligatorio.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-12 mt-2">
                                            <div class="form-outline">
                                                <label class="form-label">Segundo nombre</label>
                                                <input type="text" class="form-control text" name="segundo_nombre" maxlength="100">
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-12 mt-2">
                                            <div class="form-outline">
                                                <label class="form-label">Apellido paterno <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control text" name="apellido_paterno" maxlength="100" required>
                                                <div class="invalid-feedback">El apellido paterno es obligatorio.</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-12 mt-2">
                                            <div class="form-outline">
                                                <label class="form-label">Apellido materno</label>
                                                <input type="text" class="form-control text" name="apellido_materno" maxlength="100">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 col-12 mt-2">
                                            <div class="form-outline">
                                                <label class="form-label">Correo electrónico</label>
                                                <input type="email" class="form-control text" name="email" maxlength="200">
                                            </div>
                                        </div>
                                        <div class="col-md-2 col-12 mt-2">
                                            <div class="form-outline">
                                                <label class="form-label">Teléfono</label>
                                                <input type="text" class="form-control text" name="telefono" maxlength="20" inputmode="tel">
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-12 mt-2">
                                            <div class="form-outline">
                                                <label class="form-label">Especialidad</label>
                                                <input type="text" class="form-control text" name="especialidad" maxlength="200" placeholder="Ej. Matemáticas, Derecho">
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-12 mt-2">
                                            <div class="form-outline">
                                                <label class="form-label">Grado académico</label>
                                                <input type="text" class="form-control text" name="grado_academico" maxlength="150" placeholder="Ej. Maestría, Doctorado">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row justify-content-end mt-3">
                                        <div class="col-auto d-flex flex-wrap gap-2">
                                            <button type="button" class="btn btn-outline-secondary" id="btnLimpiarDocente" style="display: none;">
                                                <i class="fa-solid fa-eraser"></i> Limpiar
                                            </button>
                                            <button type="submit" class="btn btn-baseColor" id="btnSubmitDocente">
                                                <i class="fa-solid fa-check"></i> Guardar docente
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Sección: Tabla de docentes registrados (siempre visible) --}}
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                        <h6 class="m-0">Docentes registrados</h6>
                        <div class="form-outline" style="min-width: 12rem; max-width: 22rem;">
                            <label class="form-label small mb-0" for="buscadorDocentesModal">Buscar</label>
                            <input type="text" class="form-control form-control-sm text" id="buscadorDocentesModal" placeholder="Nombre, apellido, correo, especialidad…" autocomplete="off">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-stripped table-hover mb-0">
                            <thead>
                                <tr class="text-tr">
                                    <th>Nombre completo</th>
                                    <th>Correo</th>
                                    <th>Teléfono</th>
                                    <th>Especialidad</th>
                                    <th>Grado</th>
                                    <th>Estatus</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyDocentesModal">
                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/gestionAlumnosDataTables.js') }}?v=1"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiCursos = @json($apiCursosBase);
            const apiDocentes = @json($apiDocentesBase);
            const formCurso = document.getElementById('formCurso');
            const formEditarCurso = document.getElementById('formEditarCurso');
            const cursoEditId = document.getElementById('curso_edit_id');
            const modalAltaCurso = document.getElementById('modalAltaCurso');
            const modalEditarCurso = document.getElementById('modalEditarCurso');
            const tbodyCursosListado = document.getElementById('tbodyCursosListado');
            const buscadorCursos = document.getElementById('buscadorCursos');

            const getCsrfToken = function () {
                const m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            };

            const fieldVal = function (form, name) {
                const el = form ? form.querySelector('[name="' + name + '"]') : null;
                return el ? String(el.value || '').trim() : '';
            };

            const setFieldVal = function (form, name, value) {
                const el = form ? form.querySelector('[name="' + name + '"]') : null;
                if (el) {
                    el.value = value != null ? String(value) : '';
                }
            };

            const formPayload = function (form) {
                return {
                    nombre: fieldVal(form, 'nombre'),
                    descripcion: fieldVal(form, 'descripcion'),
                    tipo_curso: fieldVal(form, 'tipo_curso'),
                    duracion: fieldVal(form, 'duracion'),
                    instructor: fieldVal(form, 'instructor'),
                    link: fieldVal(form, 'link'),
                    aula_lugar: fieldVal(form, 'aula_lugar'),
                };
            };

            const parseJsonResponse = async function (r) {
                const text = await r.text();
                if (!text) {
                    return {};
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    return { _parseError: true, _raw: text };
                }
            };

            const limpiarFormularioCurso = function () {
                formCurso.reset();
                formCurso.classList.remove('was-validated');
            };

            const limpiarFormularioEditarCurso = function () {
                if (formEditarCurso) {
                    formEditarCurso.reset();
                    formEditarCurso.classList.remove('was-validated');
                }
                if (cursoEditId) {
                    cursoEditId.value = '';
                }
            };

            const truncar = function (s, n) {
                if (!s) {
                    return '—';
                }
                const t = String(s).trim();
                if (!t) {
                    return '—';
                }
                return t.length > n ? t.slice(0, n) + '…' : t;
            };

            const escHtml = function (s) {
                if (s == null) {
                    return '';
                }
                return String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            };

            const renderTablaCursos = function (lista) {
                if (!tbodyCursosListado) {
                    return;
                }

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.destroy('#tableCursos');
                }

                tbodyCursosListado.innerHTML = '';

                if (!lista.length) {
                    if (window.GestionAlumnosDataTables) {
                        window.GestionAlumnosDataTables.init('#tableCursos');
                    }
                    return;
                }

                lista.forEach(function (c) {
                    const desc = truncar(c.descripcion, 80);
                    const linkRaw = (c.link && String(c.link).trim()) ? String(c.link).trim() : '';
                    const linkCell = linkRaw
                        ? '<a href="' + escHtml(linkRaw) + '" target="_blank" rel="noopener noreferrer" class="small">Abrir</a>'
                        : '—';

                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escHtml(c.nombre || '—') + '</td>' +
                        '<td>' + escHtml(c.tipo_curso || '—') + '</td>' +
                        '<td>' + escHtml(c.duracion || '—') + '</td>' +
                        '<td>' + escHtml(c.instructor || '—') + '</td>' +
                        '<td>' + escHtml(c.aula_lugar || '—') + '</td>' +
                        '<td class="text-truncate" style="max-width:14rem;" title="' + escHtml(c.descripcion || '') + '">' + escHtml(desc) + '</td>' +
                        '<td class="text-nowrap">' + linkCell + '</td>' +
                        '<td class="text-end text-nowrap">' +
                        '  <button type="button" class="btn btn-primary border-0 m-0 btn-editar-curso" data-id="' + escHtml(c.id) + '"><i class="fa-solid fa-pen fs-8"></i></button>' +
                        '  <button type="button" class="btn btn-danger m-0 btn-eliminar-curso" data-id="' + escHtml(c.id) + '"><i class="fa-solid fa-trash fs-8"></i></button>' +
                        '</td>';
                    tbodyCursosListado.appendChild(tr);
                });

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.init('#tableCursos');
                }
            };

            const cargarTablaCursos = async function () {
                try {
                    const q = buscadorCursos ? (buscadorCursos.value || '').trim() : '';
                    const url = q ? (apiCursos + '?q=' + encodeURIComponent(q)) : apiCursos;
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar cursos.');
                        renderTablaCursos([]);
                        return;
                    }
                    renderTablaCursos(j.data || []);
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar cursos.');
                    renderTablaCursos([]);
                }
            };

            const abrirEdicionCurso = async function (id) {
                try {
                    const r = await fetch(apiCursos + '/' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar el curso.');
                        return;
                    }
                    const c = j.data;
                    if (cursoEditId) {
                        cursoEditId.value = String(c.id);
                    }
                    setFieldVal(formEditarCurso, 'nombre', c.nombre || '');
                    setFieldVal(formEditarCurso, 'descripcion', c.descripcion || '');
                    setFieldVal(formEditarCurso, 'tipo_curso', c.tipo_curso || '');
                    setFieldVal(formEditarCurso, 'duracion', c.duracion || '');
                    setFieldVal(formEditarCurso, 'instructor', c.instructor || '');
                    setFieldVal(formEditarCurso, 'link', c.link || '');
                    setFieldVal(formEditarCurso, 'aula_lugar', c.aula_lugar || '');
                    if (formEditarCurso) {
                        formEditarCurso.classList.remove('was-validated');
                    }
                    if (window.bootstrap && modalEditarCurso) {
                        window.bootstrap.Modal.getOrCreateInstance(modalEditarCurso).show();
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar el curso.');
                }
            };

            const eliminarCurso = async function (id) {
                if (!window.confirm('¿Eliminar este curso?')) {
                    return;
                }
                try {
                    const r = await fetch(apiCursos + '/' + encodeURIComponent(id), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                        },
                    });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo eliminar.');
                        return;
                    }
                    await cargarTablaCursos();
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al eliminar.');
                }
            };

            if (formCurso) {
                formCurso.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formCurso.checkValidity()) {
                        formCurso.classList.add('was-validated');
                        return;
                    }

                    const payload = formPayload(formCurso);

                    try {
                        const r = await fetch(apiCursos, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                        });
                        const j = await parseJsonResponse(r);
                        if (j._parseError) {
                            window.alert('El servidor devolvió un error al guardar. Revisa la consola o los logs.');
                            console.error(j._raw);
                            return;
                        }
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        if (j.message) {
                            window.alert(j.message);
                        }
                        limpiarFormularioCurso();
                        await cargarTablaCursos();
                        if (window.bootstrap && modalAltaCurso) {
                            window.bootstrap.Modal.getOrCreateInstance(modalAltaCurso).hide();
                        }
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar.');
                    }
                });
            }

            if (formEditarCurso) {
                formEditarCurso.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formEditarCurso.checkValidity()) {
                        formEditarCurso.classList.add('was-validated');
                        return;
                    }

                    const editId = cursoEditId && cursoEditId.value ? String(cursoEditId.value).trim() : '';
                    if (!editId) {
                        window.alert('No se encontró el curso a editar.');
                        return;
                    }

                    const payload = formPayload(formEditarCurso);

                    try {
                        const r = await fetch(apiCursos + '/' + encodeURIComponent(editId), {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                        });
                        const j = await parseJsonResponse(r);
                        if (j._parseError) {
                            window.alert('El servidor devolvió un error al guardar. Revisa la consola o los logs.');
                            console.error(j._raw);
                            return;
                        }
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        if (j.message) {
                            window.alert(j.message);
                        }
                        limpiarFormularioEditarCurso();
                        await cargarTablaCursos();
                        if (window.bootstrap && modalEditarCurso) {
                            window.bootstrap.Modal.getOrCreateInstance(modalEditarCurso).hide();
                        }
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar.');
                    }
                });
            }

            if (modalAltaCurso) {
                modalAltaCurso.addEventListener('hidden.bs.modal', function () {
                    limpiarFormularioCurso();
                });
            }

            if (modalEditarCurso) {
                modalEditarCurso.addEventListener('hidden.bs.modal', function () {
                    limpiarFormularioEditarCurso();
                });
            }

            if (tbodyCursosListado) {
                tbodyCursosListado.addEventListener('click', function (event) {
                    const btnEdit = event.target.closest('.btn-editar-curso');
                    if (btnEdit) {
                        const id = btnEdit.getAttribute('data-id');
                        if (id) {
                            void abrirEdicionCurso(id);
                        }
                        return;
                    }
                    const btnDel = event.target.closest('.btn-eliminar-curso');
                    if (btnDel) {
                        const id = btnDel.getAttribute('data-id');
                        if (id) {
                            void eliminarCurso(id);
                        }
                    }
                });
            }

            if (buscadorCursos) {
                buscadorCursos.addEventListener('input', function () {
                    void cargarTablaCursos();
                });
            }

            void cargarTablaCursos();

            // =====================================================
            // Lógica del modal de docentes
            // =====================================================
            const formDocente = document.getElementById('formDocente');
            const docenteEditId = document.getElementById('docenteEditId');
            const tbodyDocentesModal = document.getElementById('tbodyDocentesModal');
            const buscadorDocentesModal = document.getElementById('buscadorDocentesModal');
            const modalTituloDocente = document.getElementById('modalTituloDocente');
            const btnLimpiarDocente = document.getElementById('btnLimpiarDocente');
            const btnSubmitDocente = document.getElementById('btnSubmitDocente');
            const modalDocentesEl = document.getElementById('modalDocentes');
            const collapseFormDocente = document.getElementById('collapseFormDocente');
            const iconCollapseDocente = document.getElementById('iconCollapseDocente');

            const collapseInstance = collapseFormDocente
                ? new bootstrap.Collapse(collapseFormDocente, { toggle: false })
                : null;

            if (collapseFormDocente) {
                collapseFormDocente.addEventListener('shown.bs.collapse', function () {
                    if (iconCollapseDocente) iconCollapseDocente.style.transform = 'rotate(180deg)';
                });
                collapseFormDocente.addEventListener('hidden.bs.collapse', function () {
                    if (iconCollapseDocente) iconCollapseDocente.style.transform = 'rotate(0deg)';
                });
            }
            if (iconCollapseDocente) {
                iconCollapseDocente.style.transition = 'transform 0.3s ease';
            }

            const docenteFieldVal = function (name) {
                const el = formDocente.querySelector('[name="' + name + '"]');
                return el ? String(el.value || '').trim() : '';
            };

            const setDocenteFieldVal = function (name, value) {
                const el = formDocente.querySelector('[name="' + name + '"]');
                if (el) el.value = value != null ? String(value) : '';
            };

            const resetFormDocente = function () {
                formDocente.reset();
                formDocente.classList.remove('was-validated');
                docenteEditId.value = '';
                modalTituloDocente.innerHTML = '<i class="fa-solid fa-plus"></i> Nuevo docente';
                btnSubmitDocente.innerHTML = '<i class="fa-solid fa-check"></i> Guardar docente';
                btnLimpiarDocente.style.display = 'none';
            };

            const renderTablaDocentes = function (lista) {
                if (!tbodyDocentesModal) return;
                tbodyDocentesModal.innerHTML = '';

                if (!lista.length) {
                    var tr = document.createElement('tr');
                    tr.innerHTML = '<td class="text-muted" colspan="7">Sin docentes registrados.</td>';
                    tbodyDocentesModal.appendChild(tr);
                    return;
                }

                lista.forEach(function (d) {
                    var nombre = [d.primer_nombre, d.segundo_nombre, d.apellido_paterno, d.apellido_materno]
                        .filter(Boolean).join(' ');
                    var badgeClass = d.estatus === 'ACTIVO' ? 'bg-success' : 'bg-secondary';

                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escHtml(nombre) + '</td>' +
                        '<td>' + escHtml(d.email || '—') + '</td>' +
                        '<td>' + escHtml(d.telefono || '—') + '</td>' +
                        '<td>' + escHtml(d.especialidad || '—') + '</td>' +
                        '<td>' + escHtml(d.grado_academico || '—') + '</td>' +
                        '<td><span class="badge ' + badgeClass + '">' + escHtml(d.estatus) + '</span></td>' +
                        '<td class="text-end text-nowrap">' +
                        '  <button type="button" class="btn btn-primary border-0 m-0 btn-editar-docente" data-id="' + escHtml(d.id) + '"><i class="fa-solid fa-pen fs-8"></i></button>' +
                        '  <button type="button" class="btn btn-danger m-0 btn-eliminar-docente" data-id="' + escHtml(d.id) + '"><i class="fa-solid fa-trash fs-8"></i></button>' +
                        '</td>';
                    tbodyDocentesModal.appendChild(tr);
                });
            };

            const cargarTablaDocentes = async function () {
                try {
                    var q = buscadorDocentesModal ? (buscadorDocentesModal.value || '').trim() : '';
                    var url = q ? (apiDocentes + '?q=' + encodeURIComponent(q)) : apiDocentes;
                    var r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    var j = await parseJsonResponse(r);
                    if (!r.ok) {
                        renderTablaDocentes([]);
                        return;
                    }
                    renderTablaDocentes(j.data || []);
                } catch (err) {
                    console.error(err);
                    renderTablaDocentes([]);
                }
            };

            const abrirEdicionDocente = async function (id) {
                try {
                    var r = await fetch(apiDocentes + '/' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } });
                    var j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo cargar el docente.');
                        return;
                    }
                    var d = j.data;
                    docenteEditId.value = String(d.id);
                    setDocenteFieldVal('primer_nombre', d.primer_nombre);
                    setDocenteFieldVal('segundo_nombre', d.segundo_nombre);
                    setDocenteFieldVal('apellido_paterno', d.apellido_paterno);
                    setDocenteFieldVal('apellido_materno', d.apellido_materno);
                    setDocenteFieldVal('email', d.email);
                    setDocenteFieldVal('telefono', d.telefono);
                    setDocenteFieldVal('especialidad', d.especialidad);
                    setDocenteFieldVal('grado_academico', d.grado_academico);
                    formDocente.classList.remove('was-validated');
                    modalTituloDocente.innerHTML = '<i class="fa-solid fa-pen"></i> Editar docente';
                    btnSubmitDocente.innerHTML = '<i class="fa-solid fa-save"></i> Actualizar docente';
                    btnLimpiarDocente.style.display = '';
                    if (collapseInstance) collapseInstance.show();
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar el docente.');
                }
            };

            const eliminarDocente = async function (id) {
                if (!window.confirm('¿Eliminar este docente?')) return;
                try {
                    var r = await fetch(apiDocentes + '/' + encodeURIComponent(id), {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                    });
                    var j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudo eliminar.');
                        return;
                    }
                    resetFormDocente();
                    await cargarTablaDocentes();
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al eliminar.');
                }
            };

            if (formDocente) {
                formDocente.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formDocente.checkValidity()) {
                        formDocente.classList.add('was-validated');
                        return;
                    }

                    var editId = docenteEditId.value ? String(docenteEditId.value).trim() : '';
                    var payload = {
                        primer_nombre: docenteFieldVal('primer_nombre'),
                        segundo_nombre: docenteFieldVal('segundo_nombre'),
                        apellido_paterno: docenteFieldVal('apellido_paterno'),
                        apellido_materno: docenteFieldVal('apellido_materno'),
                        email: docenteFieldVal('email'),
                        telefono: docenteFieldVal('telefono'),
                        especialidad: docenteFieldVal('especialidad'),
                        grado_academico: docenteFieldVal('grado_academico'),
                        estatus: 'ACTIVO',
                    };

                    try {
                        var url = editId ? (apiDocentes + '/' + encodeURIComponent(editId)) : apiDocentes;
                        var r = await fetch(url, {
                            method: editId ? 'PUT' : 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify(payload),
                        });
                        var j = await parseJsonResponse(r);
                        if (j._parseError) {
                            window.alert('El servidor devolvió un error. Revisa la consola.');
                            console.error(j._raw);
                            return;
                        }
                        if (!r.ok) {
                            var msg = (j && j.message) ? j.message : 'No se pudo guardar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            window.alert(msg);
                            return;
                        }
                        if (j.message) window.alert(j.message);
                        resetFormDocente();
                        if (collapseInstance) collapseInstance.hide();
                        await cargarTablaDocentes();
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar.');
                    }
                });
            }

            if (tbodyDocentesModal) {
                tbodyDocentesModal.addEventListener('click', function (event) {
                    var btnEdit = event.target.closest('.btn-editar-docente');
                    if (btnEdit) {
                        void abrirEdicionDocente(btnEdit.getAttribute('data-id'));
                        return;
                    }
                    var btnDel = event.target.closest('.btn-eliminar-docente');
                    if (btnDel) {
                        void eliminarDocente(btnDel.getAttribute('data-id'));
                    }
                });
            }

            if (buscadorDocentesModal) {
                buscadorDocentesModal.addEventListener('input', function () {
                    void cargarTablaDocentes();
                });
            }

            if (btnLimpiarDocente) {
                btnLimpiarDocente.addEventListener('click', function () {
                    resetFormDocente();
                });
            }

            if (modalDocentesEl) {
                modalDocentesEl.addEventListener('show.bs.modal', function () {
                    resetFormDocente();
                    if (collapseInstance) collapseInstance.hide();
                    void cargarTablaDocentes();
                });
            }
        });
    </script>
@endsection
