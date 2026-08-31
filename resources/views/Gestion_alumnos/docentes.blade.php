@extends('layouts.app')
@section('content')
    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-chalkboard-user"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Docentes</h2>
                            <p class="text-muted mb-0">Alta, edición y consulta de docentes registrados.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-baseColor fs-7 mb-2" type="button" data-bs-toggle="modal" data-bs-target="#modalAltaDocente">
                            <i class="fa-solid fa-plus"></i> Nuevo docente
                        </button>
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="javascript:history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiDocentesBase = url('/Gestion_alumnos/api/docentes');
        @endphp

        <div class="modal fade" id="modalAltaDocente" tabindex="-1" aria-labelledby="modalAltaDocenteLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h5 class="modal-title text-secondary" id="modalAltaDocenteLabel">
                                <i class="fa-solid fa-user-plus me-2"></i>Nuevo docente
                            </h5>
                            <p class="text-muted fs-8 mb-0">Captura los datos generales del docente.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="formDocente" class="g-3 form needs-validation" novalidate>
                        <input type="hidden" id="docente_edit_id" value="">
                        <div class="modal-body pt-0">
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

                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-baseColor fs-8 rounded-1" id="btnSubmitDocenteForm">
                                    <i class="fa-solid fa-check"></i> Guardar docente
                                </button>
                                <button type="button" class="btn btn-outline-secondary fs-8 rounded-1" id="btnLimpiarDocenteForm">
                                    <i class="fa-solid fa-eraser"></i> Limpiar
                                </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <h5 class="text-secondary mb-1">
                            <i class="fa-solid fa-list-check me-2"></i>Listado de docentes
                        </h5>
                        <p class="text-muted fs-8 mb-0">Consulta, edita o elimina docentes registrados.</p>
                    </div>
                </div>
            </div>
            <div class="card-body">
            <!-- <div class="row mb-3">
                <div class="col-md-5 col-12">
                    <div class="form-outline">
                        <label class="form-label">Buscar</label>
                        <input type="text" id="buscadorDocentes" class="form-control text" placeholder="Nombre, apellido, correo, especialidad…">
                    </div>
                </div>
            </div> -->

            <div class="table-responsive">
                <table class="table table-stripped table-hover display mb-0" id="tablaDocentes">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-truncate">Nombre completo</th>
                            <th class="text-truncate">Correo</th>
                            <th class="text-truncate">Teléfono</th>
                            <th class="text-truncate">Especialidad</th>
                            <th class="text-truncate">Grado</th>
                            <th class="text-truncate">Estatus</th>
                            <th class="text-truncate">Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDocentesListado">
                        <tr>
                            <td class="text-muted" colspan="7">Cargando…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        <div class="modal fade" id="modalEditarDocente" tabindex="-1" aria-labelledby="modalEditarDocenteLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h5 class="modal-title text-secondary" id="modalEditarDocenteLabel">
                                <i class="fa-solid fa-pen me-2"></i>Editar docente
                            </h5>
                            <p class="text-muted fs-8 mb-0">Actualiza los datos generales del docente seleccionado.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="formEditarDocente" class="g-3 form needs-validation" novalidate>
                        <input type="hidden" id="docente_modal_edit_id" value="">
                        <div class="modal-body pt-0">
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
                                        <input type="text" class="form-control text" name="especialidad" maxlength="200">
                                    </div>
                                </div>
                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Grado académico</label>
                                        <input type="text" class="form-control text" name="grado_academico" maxlength="150">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-baseColor">
                                <i class="fa-solid fa-floppy-disk"></i> Actualizar docente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/gestionAlumnosMayusculas.js') }}?v=3"></script>
    <script src="{{ asset('js/gestionAlumnosDataTables.js') }}?v=1"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiDocentes = @json($apiDocentesBase);
            const formDocente = document.getElementById('formDocente');
            const docenteEditId = document.getElementById('docente_edit_id');
            const tituloFormularioDocente = document.getElementById('tituloFormularioDocente');
            const btnSubmitDocenteForm = document.getElementById('btnSubmitDocenteForm');
            const btnLimpiarDocenteForm = document.getElementById('btnLimpiarDocenteForm');
            const tbodyDocentesListado = document.getElementById('tbodyDocentesListado');
            const buscadorDocentes = document.getElementById('buscadorDocentes');
            const modalAltaDocente = document.getElementById('modalAltaDocente');
            const modalEditarDocente = document.getElementById('modalEditarDocente');
            const formEditarDocente = document.getElementById('formEditarDocente');
            const docenteModalEditId = document.getElementById('docente_modal_edit_id');

            const getCsrfToken = function () {
                const m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
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

            const mostrarAlerta = function (mensaje, icono = 'info', titulo = null) {
                if (typeof Swal === 'undefined') {
                    console[icono === 'error' ? 'error' : 'log'](mensaje);
                    return Promise.resolve();
                }

                return Swal.fire({
                    icon: icono,
                    title: titulo || (icono === 'success' ? '¡Listo!' : icono === 'error' ? 'Error' : 'Aviso'),
                    text: mensaje,
                    confirmButtonColor: '#0d6efd',
                });
            };

            const confirmarAccion = async function (mensaje, titulo = '¿Estás seguro?') {
                if (typeof Swal === 'undefined') {
                    return false;
                }

                const result = await Swal.fire({
                    title: titulo,
                    text: mensaje,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                });

                return result.isConfirmed;
            };

            const fieldVal = function (name) {
                const el = formDocente.querySelector('[name="' + name + '"]');
                return el ? String(el.value || '').trim() : '';
            };

            const setFieldVal = function (name, value) {
                const el = formDocente.querySelector('[name="' + name + '"]');
                if (el) {
                    el.value = value != null ? String(value) : '';
                }
            };

            const fieldValFrom = function (form, name) {
                const el = form ? form.querySelector('[name="' + name + '"]') : null;
                return el ? String(el.value || '').trim() : '';
            };

            const setFieldValFrom = function (form, name, value) {
                const el = form ? form.querySelector('[name="' + name + '"]') : null;
                if (el) {
                    el.value = value != null ? String(value) : '';
                }
            };

            const limpiarFormularioDocente = function () {
                formDocente.reset();
                formDocente.classList.remove('was-validated');
                if (docenteEditId) {
                    docenteEditId.value = '';
                }
                if (tituloFormularioDocente) {
                    tituloFormularioDocente.innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Nuevo docente';
                }
                if (btnSubmitDocenteForm) {
                    btnSubmitDocenteForm.innerHTML = '<i class="fa-solid fa-check"></i> Guardar docente';
                }
            };

            const nombreCompletoDocente = function (d) {
                return [
                    d.primer_nombre,
                    d.segundo_nombre,
                    d.apellido_paterno,
                    d.apellido_materno,
                ].filter(function (v) {
                    return v && String(v).trim();
                }).join(' ');
            };

            const renderTablaDocentes = function (lista) {
                if (!tbodyDocentesListado) {
                    return;
                }

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.destroy('#tablaDocentes');
                }

                tbodyDocentesListado.innerHTML = '';

                if (!lista.length) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td class="text-muted" colspan="7">Sin docentes registrados.</td>';
                    tbodyDocentesListado.appendChild(tr);
                    return;
                }

                lista.forEach(function (d) {
                    const estatus = d.estatus || 'ACTIVO';
                    const badgeClass = estatus === 'ACTIVO' ? 'badge-success-dark' : 'badge-danger-dark';
                    const tr = document.createElement('tr');
                    tr.className = 'boder-sec';
                    tr.innerHTML =
                        '<td class="text-truncate text-start">' + escHtml(nombreCompletoDocente(d) || '—') + '</td>' +
                        '<td class="text-truncate">' + escHtml(d.email || '—') + '</td>' +
                        '<td class="text-truncate">' + escHtml(d.telefono || '—') + '</td>' +
                        '<td class="text-truncate">' + escHtml(d.especialidad || '—') + '</td>' +
                        '<td class="text-truncate">' + escHtml(d.grado_academico || '—') + '</td>' +
                        '<td class="text-truncate"><span class="badge ' + badgeClass + ' fs-9">' + escHtml(estatus) + '</span></td>' +
                        '<td class="text-truncate">' +
                        '  <button type="button" class="btn btn-primary border-0 m-0 btn-editar-docente" data-id="' + escHtml(d.id) + '"><i class="fa-solid fa-pen fs-8"></i></button>' +
                        '  <button type="button" class="btn btn-danger m-0 btn-eliminar-docente" data-id="' + escHtml(d.id) + '"><i class="fa-solid fa-trash fs-8"></i></button>' +
                        '</td>';
                    tbodyDocentesListado.appendChild(tr);
                });

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.init('#tablaDocentes');
                }
            };

            const cargarTablaDocentes = async function () {
                try {
                    const q = buscadorDocentes ? (buscadorDocentes.value || '').trim() : '';
                    const url = q ? (apiDocentes + '?q=' + encodeURIComponent(q)) : apiDocentes;
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        void mostrarAlerta((j && j.message) ? j.message : 'No se pudo cargar el listado de docentes.', 'error');
                        renderTablaDocentes([]);
                        return;
                    }
                    renderTablaDocentes(j.data || []);
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al cargar docentes.', 'error');
                    renderTablaDocentes([]);
                }
            };

            const abrirEdicionDocente = async function (id) {
                try {
                    const r = await fetch(apiDocentes + '/' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        void mostrarAlerta((j && j.message) ? j.message : 'No se pudo cargar el docente.', 'error');
                        return;
                    }

                    const d = j.data;
                    if (docenteModalEditId) {
                        docenteModalEditId.value = String(d.id);
                    }
                    setFieldValFrom(formEditarDocente, 'primer_nombre', d.primer_nombre);
                    setFieldValFrom(formEditarDocente, 'segundo_nombre', d.segundo_nombre);
                    setFieldValFrom(formEditarDocente, 'apellido_paterno', d.apellido_paterno);
                    setFieldValFrom(formEditarDocente, 'apellido_materno', d.apellido_materno);
                    setFieldValFrom(formEditarDocente, 'email', d.email);
                    setFieldValFrom(formEditarDocente, 'telefono', d.telefono);
                    setFieldValFrom(formEditarDocente, 'especialidad', d.especialidad);
                    setFieldValFrom(formEditarDocente, 'grado_academico', d.grado_academico);
                    if (formEditarDocente) {
                        formEditarDocente.classList.remove('was-validated');
                    }
                    if (window.bootstrap && modalEditarDocente) {
                        bootstrap.Modal.getOrCreateInstance(modalEditarDocente).show();
                    }
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al cargar el docente.', 'error');
                }
            };

            const eliminarDocente = async function (id) {
                if (!await confirmarAccion('Esta acción eliminará el registro del docente.', '¿Eliminar este docente?')) {
                    return;
                }
                try {
                    const r = await fetch(apiDocentes + '/' + encodeURIComponent(id), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        void mostrarAlerta((j && j.message) ? j.message : 'No se pudo eliminar.', 'error');
                        return;
                    }
                    if (j.message) {
                        void mostrarAlerta(j.message, 'success');
                    }
                    limpiarFormularioDocente();
                    await cargarTablaDocentes();
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al eliminar.', 'error');
                }
            };

            if (formDocente) {
                formDocente.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formDocente.checkValidity()) {
                        formDocente.classList.add('was-validated');
                        return;
                    }

                    const editId = docenteEditId && docenteEditId.value ? String(docenteEditId.value).trim() : '';
                    const payload = {
                        primer_nombre: fieldVal('primer_nombre'),
                        segundo_nombre: fieldVal('segundo_nombre') || null,
                        apellido_paterno: fieldVal('apellido_paterno'),
                        apellido_materno: fieldVal('apellido_materno') || null,
                        email: fieldVal('email') || null,
                        telefono: fieldVal('telefono') || null,
                        especialidad: fieldVal('especialidad') || null,
                        grado_academico: fieldVal('grado_academico') || null,
                        estatus: 'ACTIVO',
                    };

                    try {
                        const url = editId ? (apiDocentes + '/' + encodeURIComponent(editId)) : apiDocentes;
                        const r = await fetch(url, {
                            method: editId ? 'PUT' : 'POST',
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
                            void mostrarAlerta('El servidor devolvió un error. Revisa la consola.', 'error');
                            console.error(j._raw);
                            return;
                        }
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo guardar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            void mostrarAlerta(msg, 'error');
                            return;
                        }
                        if (j.message) {
                            void mostrarAlerta(j.message, 'success');
                        }
                        if (window.bootstrap && modalAltaDocente) {
                            bootstrap.Modal.getOrCreateInstance(modalAltaDocente).hide();
                        }
                        limpiarFormularioDocente();
                        await cargarTablaDocentes();
                    } catch (err) {
                        console.error(err);
                        void mostrarAlerta('Error de red al guardar.', 'error');
                    }
                });
            }

            if (formEditarDocente) {
                formEditarDocente.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formEditarDocente.checkValidity()) {
                        formEditarDocente.classList.add('was-validated');
                        return;
                    }

                    const editId = docenteModalEditId && docenteModalEditId.value ? String(docenteModalEditId.value).trim() : '';
                    if (!editId) {
                        return;
                    }

                    const payload = {
                        primer_nombre: fieldValFrom(formEditarDocente, 'primer_nombre'),
                        segundo_nombre: fieldValFrom(formEditarDocente, 'segundo_nombre') || null,
                        apellido_paterno: fieldValFrom(formEditarDocente, 'apellido_paterno'),
                        apellido_materno: fieldValFrom(formEditarDocente, 'apellido_materno') || null,
                        email: fieldValFrom(formEditarDocente, 'email') || null,
                        telefono: fieldValFrom(formEditarDocente, 'telefono') || null,
                        especialidad: fieldValFrom(formEditarDocente, 'especialidad') || null,
                        grado_academico: fieldValFrom(formEditarDocente, 'grado_academico') || null,
                        estatus: 'ACTIVO',
                    };

                    try {
                        const r = await fetch(apiDocentes + '/' + encodeURIComponent(editId), {
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
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo actualizar.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            void mostrarAlerta(msg, 'error');
                            return;
                        }

                        if (window.bootstrap && modalEditarDocente) {
                            bootstrap.Modal.getOrCreateInstance(modalEditarDocente).hide();
                        }
                        formEditarDocente.classList.remove('was-validated');
                        await cargarTablaDocentes();
                        void mostrarAlerta(j.message || 'Docente actualizado correctamente.', 'success');
                    } catch (err) {
                        console.error(err);
                        void mostrarAlerta('Error de red al actualizar.', 'error');
                    }
                });
            }

            if (tbodyDocentesListado) {
                tbodyDocentesListado.addEventListener('click', function (event) {
                    const btnEdit = event.target.closest('.btn-editar-docente');
                    if (btnEdit) {
                        const id = btnEdit.getAttribute('data-id');
                        if (id) {
                            void abrirEdicionDocente(id);
                        }
                        return;
                    }

                    const btnDel = event.target.closest('.btn-eliminar-docente');
                    if (btnDel) {
                        const id = btnDel.getAttribute('data-id');
                        if (id) {
                            void eliminarDocente(id);
                        }
                    }
                });
            }

            if (buscadorDocentes) {
                buscadorDocentes.addEventListener('input', function () {
                    void cargarTablaDocentes();
                });
            }

            if (btnLimpiarDocenteForm) {
                btnLimpiarDocenteForm.addEventListener('click', limpiarFormularioDocente);
            }

            if (modalAltaDocente) {
                modalAltaDocente.addEventListener('show.bs.modal', limpiarFormularioDocente);
            }

            void cargarTablaDocentes();
        });
    </script>
@endsection
