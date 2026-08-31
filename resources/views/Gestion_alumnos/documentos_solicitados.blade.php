@extends('layouts.app')
@section('content')
    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-file-circle-check"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Documentos Solicitados</h2>
                            <p class="text-muted mb-0">Alta de tipos de documento y configuración de requisitos para alumno y tutor.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-baseColor fs-7 mb-2" type="button" data-bs-toggle="modal" data-bs-target="#modalAltaDocumento">
                            <i class="fa-solid fa-plus"></i> Nuevo Documento
                        </button>
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="javascript:history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiDocumentosBase = url('/Gestion_alumnos/api/documentos');
            $apiAlumnosBase = url('/Gestion_alumnos/api/alumnos');
        @endphp

        <div class="modal fade" id="modalAltaDocumento" tabindex="-1" aria-labelledby="modalAltaDocumentoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h5 class="modal-title text-secondary" id="modalAltaDocumentoLabel">
                                <i class="fa-solid fa-file-circle-plus me-2"></i>Alta de nuevo tipo de documento
                            </h5>
                            <p class="text-muted fs-8 mb-0">Registra los documentos que podrán solicitarse al alumno o tutor.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
            <form id="formTipoDocumento" class="g-3 form needs-validation" novalidate>
                <input type="hidden" id="documento_edit_id" value="">
                <div class="modal-body pt-0">
                <div class="row">
                    <div class="col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Nombre del documento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text" name="nombre_documento" maxlength="200" required>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">El nombre es obligatorio (máx. 200 caracteres).</div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="form-outline">
                            <label class="form-label">Descripción del documento</label>
                            <textarea class="form-control text" name="descripcion_documento" rows="3" maxlength="300"></textarea>
                        </div>
                    </div>
                </div>

                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-baseColor fs-8 rounded-1" id="btnSubmitDocumentoForm">
                            <i class="fa-solid fa-check"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-moderno fs-8 rounded-1" id="btnLimpiarDocumentoForm">
                            <i class="fa-solid fa-eraser"></i> Limpiar
                        </button>
                </div>
            </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
            <div class="card-body">
            <form id="formRequisitosDocumentos" class="g-3 form needs-validation" novalidate>
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-2 mb-3">
                    <div>
                        <h5 class="text-secondary mb-1">
                            <i class="fa-solid fa-clipboard-check me-2"></i>Requisitos mínimos de documentos
                        </h5>
                        <p class="text-muted fs-8 mb-1">Configura por separado qué debe subir el alumno y qué debe subir cada tutor.</p>
                        <p class="text-primary fs-8 mb-0">
                            <i class="fa-solid fa-circle-info me-1"></i>Deberás guardar los cambios para verlos efectuados.
                        </p>
                    </div>
                </div>

                <div class="accordion acciones-admin-accordion mt-3" id="accordionRequisitosDocumentos">
                    <div class="accordion-item acciones-collapse-card acciones-collapse-card--vista rounded-4 overflow-hidden mb-3">
                        <h2 class="accordion-header" id="headingRequisitosAlumno">
                            <button class="accordion-button collapsed acciones-collapse-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseRequisitosAlumno" aria-expanded="false" aria-controls="collapseRequisitosAlumno">
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
                        <div id="collapseRequisitosAlumno" class="accordion-collapse collapse" aria-labelledby="headingRequisitosAlumno"
                            data-bs-parent="#accordionRequisitosDocumentos">
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
                                        <tbody id="tbodyRequisitosAlumno">
                                            <tr>
                                                <td class="text-muted" colspan="2">Cargando…</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item acciones-collapse-card acciones-collapse-card--accion rounded-4 overflow-hidden">
                        <h2 class="accordion-header" id="headingRequisitosTutor">
                            <button class="accordion-button collapsed acciones-collapse-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseRequisitosTutor" aria-expanded="false" aria-controls="collapseRequisitosTutor">
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
                        <div id="collapseRequisitosTutor" class="accordion-collapse collapse" aria-labelledby="headingRequisitosTutor"
                            data-bs-parent="#accordionRequisitosDocumentos">
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
                                        <tbody id="tbodyRequisitosTutor">
                                            <tr>
                                                <td class="text-muted" colspan="2">Cargando…</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0" id="requisitosDocumentosVacio" style="display:none;">
                    No hay tipos de documento registrados.
                </p>
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-baseColor fs-8 rounded-1">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar requisitos
                    </button>
                </div>
            </form>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Listado de documentos solicitados
                </h5>
                <p class="text-muted fs-8 mb-0">Consulta, edita o inactiva los tipos de documento registrados.</p>
            </div>
            <div class="card-body">
            <!-- <div class="row mb-3">
                <div class="col-md-5 col-12">
                    <div class="form-outline">
                        <label class="form-label">Buscar</label>
                        <input type="text" id="buscadorDocumentos" class="form-control text" placeholder="id, nombre, descripción…">
                    </div>
                </div>
            </div> -->

            <div class="table-responsive">
                <table class="table table-stripped table-hover display mb-0" id="tablaDocumentos">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-truncate">id</th>
                            <th class="text-truncate">nombre_documento</th>
                            <th class="text-truncate">descripcion_documento</th>
                            <th class="text-truncate">Estado</th>
                            <th class="text-truncate">Req. alumno</th>
                            <th class="text-truncate">Req. tutor</th>
                            <th class="text-truncate">Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDocumentosListado">
                        <tr>
                            <td class="text-muted" colspan="7">Cargando…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        <div class="modal fade" id="modalEditarDocumento" tabindex="-1" aria-labelledby="modalEditarDocumentoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h5 class="modal-title text-secondary" id="modalEditarDocumentoLabel">
                                <i class="fa-solid fa-pen me-2"></i>Editar tipo de documento
                            </h5>
                            <p class="text-muted fs-8 mb-0">Actualiza el nombre y la descripción del documento seleccionado.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="formEditarDocumento" class="g-3 form needs-validation" novalidate>
                        <input type="hidden" id="documento_modal_edit_id" value="">
                        <div class="modal-body pt-0">
                            <div class="row">
                                <div class="col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Nombre del documento <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text" name="nombre_documento" maxlength="200" required>
                                        <div class="invalid-feedback">El nombre es obligatorio (máx. 200 caracteres).</div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Descripción del documento</label>
                                        <textarea class="form-control text" name="descripcion_documento" rows="3" maxlength="300"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-baseColor">
                                <i class="fa-solid fa-floppy-disk"></i> Actualizar 
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
            const apiDocumentos = @json($apiDocumentosBase);
            const apiAlumnos = @json($apiAlumnosBase);
            const formTipoDocumento = document.getElementById('formTipoDocumento');
            const documentoEditId = document.getElementById('documento_edit_id');
            const tituloFormularioDocumento = document.getElementById('tituloFormularioDocumento');
            const btnSubmitDocumentoForm = document.getElementById('btnSubmitDocumentoForm');
            const btnLimpiarDocumentoForm = document.getElementById('btnLimpiarDocumentoForm');
            const formRequisitosDocumentos = document.getElementById('formRequisitosDocumentos');
            const tbodyRequisitosAlumno = document.getElementById('tbodyRequisitosAlumno');
            const tbodyRequisitosTutor = document.getElementById('tbodyRequisitosTutor');
            const requisitosDocumentosVacio = document.getElementById('requisitosDocumentosVacio');
            const tbodyDocumentosListado = document.getElementById('tbodyDocumentosListado');
            const buscadorDocumentos = document.getElementById('buscadorDocumentos');
            const modalAltaDocumento = document.getElementById('modalAltaDocumento');
            const modalEditarDocumento = document.getElementById('modalEditarDocumento');
            const formEditarDocumento = document.getElementById('formEditarDocumento');
            const documentoModalEditId = document.getElementById('documento_modal_edit_id');

            let cacheDocumentos = [];

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
                const el = formTipoDocumento.querySelector('[name="' + name + '"]');
                return el ? String(el.value || '').trim() : '';
            };

            const setFieldVal = function (name, value) {
                const el = formTipoDocumento.querySelector('[name="' + name + '"]');
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

            const limpiarFormularioDocumento = function () {
                formTipoDocumento.reset();
                formTipoDocumento.classList.remove('was-validated');
                if (documentoEditId) {
                    documentoEditId.value = '';
                }
                if (tituloFormularioDocumento) {
                    tituloFormularioDocumento.innerHTML = '<i class="fa-solid fa-file-circle-plus me-2"></i>Alta de nuevo tipo de documento';
                }
                if (btnSubmitDocumentoForm) {
                    btnSubmitDocumentoForm.innerHTML = '<i class="fa-solid fa-check"></i> Guardar';
                }
            };

            const documentoActivo = function (d) {
                if (d.activo === undefined || d.activo === null) {
                    return true;
                }
                return d.activo !== false && d.activo !== 0;
            };

            const textoEstado = function (d) {
                if (d.activo === undefined || d.activo === null) {
                    return 'Activo';
                }
                return documentoActivo(d) ? 'Activo' : 'Inactivo';
            };

            const listaFiltrada = function () {
                const q = buscadorDocumentos ? (buscadorDocumentos.value || '').trim().toLowerCase() : '';
                if (!q) {
                    return cacheDocumentos.slice();
                }
                return cacheDocumentos.filter(function (d) {
                    const bloque = [
                        d.id,
                        d.nombre_documento,
                        d.descripcion_documento,
                    ]
                        .filter(function (v) { return v !== undefined && v !== null && String(v).trim() !== ''; })
                        .join(' ')
                        .toLowerCase();
                    return bloque.indexOf(q) !== -1;
                });
            };

            const renderTablaDocumentos = function (lista) {
                if (!tbodyDocumentosListado) {
                    return;
                }

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.destroy('#tablaDocumentos');
                }

                tbodyDocumentosListado.innerHTML = '';

                if (!lista.length) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td class="text-muted" colspan="7">Sin registros por el momento.</td>';
                    tbodyDocumentosListado.appendChild(tr);
                    return;
                }

                lista.forEach(function (d) {
                    const activo = documentoActivo(d);
                    const badgeClass = activo ? 'badge-success-dark' : 'badge-danger-dark';
                    const btnInactivar = activo
                        ? '<button type="button" class="btn btn-danger m-1 btn-inactivar-documento" data-id="' + escHtml(d.id) + '" title="Inactivar"><i class="fa-solid fa-ban fs-8"></i></button>'
                        : '';
                    const reqAlumno = d.obligatorio_alumno ? 'Sí' : 'No';
                    const reqTutor = d.obligatorio_tutor ? 'Sí' : 'No';

                    const tr = document.createElement('tr');
                    tr.className = 'boder-sec';
                    tr.innerHTML =
                        '<td class="text-truncate">' + escHtml(d.id != null ? d.id : '') + '</td>' +
                        '<td class="text-truncate text-start">' + escHtml(d.nombre_documento || '—') + '</td>' +
                        '<td class="text-truncate" style="max-width:14rem;" title="' + escHtml(d.descripcion_documento || '') + '">' + escHtml(d.descripcion_documento || '—') + '</td>' +
                        '<td class="text-truncate"><span class="badge ' + badgeClass + ' fs-9">' + escHtml(textoEstado(d)) + '</span></td>' +
                        '<td class="text-truncate">' + escHtml(reqAlumno) + '</td>' +
                        '<td class="text-truncate">' + escHtml(reqTutor) + '</td>' +
                        '<td class="text-truncate">' +
                        '  <button type="button" class="btn btn-primary border-0 m-1 btn-editar-documento" data-id="' + escHtml(d.id) + '"><i class="fa-solid fa-pen fs-8"></i></button>' +
                        btnInactivar +
                        '</td>';
                    tbodyDocumentosListado.appendChild(tr);
                });

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.init('#tablaDocumentos');
                }
            };

            const renderRequisitos = function (documentos) {
                if (!tbodyRequisitosAlumno || !tbodyRequisitosTutor || !requisitosDocumentosVacio) {
                    return;
                }

                tbodyRequisitosAlumno.innerHTML = '';
                tbodyRequisitosTutor.innerHTML = '';

                if (!documentos.length) {
                    requisitosDocumentosVacio.style.display = 'block';
                    tbodyRequisitosAlumno.innerHTML = '<tr><td class="text-muted" colspan="2">Sin documentos registrados.</td></tr>';
                    tbodyRequisitosTutor.innerHTML = '<tr><td class="text-muted" colspan="2">Sin documentos registrados.</td></tr>';
                    return;
                }

                requisitosDocumentosVacio.style.display = 'none';

                documentos.forEach(function (doc) {
                    const idDoc = escHtml(doc.id || '');
                    const nombre = escHtml(doc.nombre_documento || '—');

                    const trAlumno = document.createElement('tr');
                    trAlumno.className = 'boder-sec';
                    trAlumno.innerHTML =
                        '<td class="text-truncate text-start">' + nombre + '</td>' +
                        '<td class="text-truncate text-center"><input type="checkbox" class="form-check-input req-doc-check-alumno" value="' + idDoc + '"' + (doc.obligatorio_alumno ? ' checked' : '') + '></td>';
                    tbodyRequisitosAlumno.appendChild(trAlumno);

                    const trTutor = document.createElement('tr');
                    trTutor.className = 'boder-sec';
                    trTutor.innerHTML =
                        '<td class="text-truncate text-start">' + nombre + '</td>' +
                        '<td class="text-truncate text-center"><input type="checkbox" class="form-check-input req-doc-check-tutor" value="' + idDoc + '"' + (doc.obligatorio_tutor ? ' checked' : '') + '></td>';
                    tbodyRequisitosTutor.appendChild(trTutor);
                });

            };

            const aplicarFiltroYRender = function () {
                renderTablaDocumentos(listaFiltrada());
            };

            const cargarDocumentosYRequisitos = async function () {
                try {
                    const documentosReq = fetch(apiDocumentos, { headers: { 'Accept': 'application/json' } });
                    const requisitosReq = fetch(apiAlumnos + '/requisitos-documentos', { headers: { 'Accept': 'application/json' } });
                    const respuestas = await Promise.all([documentosReq, requisitosReq]);
                    const docsJson = await parseJsonResponse(respuestas[0]);
                    const reqJson = await parseJsonResponse(respuestas[1]);

                    if (!respuestas[0].ok) {
                        void mostrarAlerta((docsJson && docsJson.message) ? docsJson.message : 'No se pudo cargar el listado de documentos.', 'error');
                        cacheDocumentos = [];
                        renderRequisitos([]);
                        aplicarFiltroYRender();
                        return;
                    }
                    if (!respuestas[1].ok) {
                        void mostrarAlerta((reqJson && reqJson.message) ? reqJson.message : 'No se pudieron cargar los requisitos.', 'error');
                    }

                    const requisitosPorDocumento = {};
                    const reqData = reqJson && reqJson.data ? reqJson.data : {};
                    (reqData.documentos || []).forEach(function (docReq) {
                        requisitosPorDocumento[String(docReq.id)] = {
                            obligatorio_alumno: !!docReq.obligatorio_alumno,
                            obligatorio_tutor: !!docReq.obligatorio_tutor,
                        };
                    });

                    cacheDocumentos = (docsJson.data || []).map(function (d) {
                        const flags = requisitosPorDocumento[String(d.id)] || {};
                        return Object.assign({}, d, {
                            obligatorio_alumno: !!flags.obligatorio_alumno,
                            obligatorio_tutor: !!flags.obligatorio_tutor,
                        });
                    });

                    renderRequisitos(cacheDocumentos);
                    aplicarFiltroYRender();
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al cargar documentos.', 'error');
                    cacheDocumentos = [];
                    renderRequisitos([]);
                    aplicarFiltroYRender();
                }
            };

            const abrirEdicionDocumento = async function (id) {
                try {
                    const r = await fetch(apiDocumentos + '/' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        void mostrarAlerta((j && j.message) ? j.message : 'No se pudo cargar el tipo de documento.', 'error');
                        return;
                    }
                    const d = j.data;
                    if (documentoModalEditId) {
                        documentoModalEditId.value = String(d.id);
                    }
                    setFieldValFrom(formEditarDocumento, 'nombre_documento', d.nombre_documento || '');
                    setFieldValFrom(formEditarDocumento, 'descripcion_documento', d.descripcion_documento || '');
                    if (formEditarDocumento) {
                        formEditarDocumento.classList.remove('was-validated');
                    }
                    if (window.bootstrap && modalEditarDocumento) {
                        bootstrap.Modal.getOrCreateInstance(modalEditarDocumento).show();
                    }
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al cargar el tipo de documento.', 'error');
                }
            };

            const inactivarDocumento = async function (id) {
                if (!await confirmarAccion('Dejará de aparecer en nuevas selecciones.', '¿Inactivar este tipo de documento?')) {
                    return;
                }
                try {
                    const r = await fetch(apiDocumentos + '/' + encodeURIComponent(id) + '/inactivar', {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        void mostrarAlerta((j && j.message) ? j.message : 'No se pudo inactivar.', 'error');
                        return;
                    }
                    if (j.message) {
                        void mostrarAlerta(j.message, 'success');
                    }
                    await cargarDocumentosYRequisitos();
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al inactivar.', 'error');
                }
            };

            if (formTipoDocumento) {
                formTipoDocumento.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formTipoDocumento.checkValidity()) {
                        formTipoDocumento.classList.add('was-validated');
                        return;
                    }

                    const editId = documentoEditId && documentoEditId.value ? String(documentoEditId.value).trim() : '';
                    const payload = {
                        nombre_documento: fieldVal('nombre_documento'),
                        descripcion_documento: fieldVal('descripcion_documento') || null,
                    };

                    try {
                        const url = editId ? (apiDocumentos + '/' + encodeURIComponent(editId)) : apiDocumentos;
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
                            void mostrarAlerta('El servidor devolvió un error al guardar. Revisa la consola o los logs.', 'error');
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
                        if (window.bootstrap && modalAltaDocumento) {
                            bootstrap.Modal.getOrCreateInstance(modalAltaDocumento).hide();
                        }
                        limpiarFormularioDocumento();
                        await cargarDocumentosYRequisitos();
                    } catch (err) {
                        console.error(err);
                        void mostrarAlerta('Error de red al guardar.', 'error');
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

                    try {
                        const r = await fetch(apiAlumnos + '/requisitos-documentos', {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                alumno: alumnoIds,
                                tutor: tutorIds,
                            }),
                        });
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudieron guardar los requisitos.';
                            if (j && j.errors) {
                                msg = Object.values(j.errors).flat().join('\n');
                            }
                            void mostrarAlerta(msg, 'error');
                            return;
                        }
                        formRequisitosDocumentos.classList.remove('was-validated');
                        if (j.message) {
                            void mostrarAlerta(j.message, 'success');
                        }
                        await cargarDocumentosYRequisitos();
                    } catch (err) {
                        console.error(err);
                        void mostrarAlerta('Error de red al guardar requisitos.', 'error');
                    }
                });
            }

            if (modalAltaDocumento) {
                modalAltaDocumento.addEventListener('show.bs.modal', limpiarFormularioDocumento);
            }

            if (formEditarDocumento) {
                formEditarDocumento.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formEditarDocumento.checkValidity()) {
                        formEditarDocumento.classList.add('was-validated');
                        return;
                    }

                    const editId = documentoModalEditId && documentoModalEditId.value ? String(documentoModalEditId.value).trim() : '';
                    if (!editId) {
                        return;
                    }

                    const payload = {
                        nombre_documento: fieldValFrom(formEditarDocumento, 'nombre_documento'),
                        descripcion_documento: fieldValFrom(formEditarDocumento, 'descripcion_documento') || null,
                    };

                    try {
                        const r = await fetch(apiDocumentos + '/' + encodeURIComponent(editId), {
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

                        if (window.bootstrap && modalEditarDocumento) {
                            bootstrap.Modal.getOrCreateInstance(modalEditarDocumento).hide();
                        }
                        formEditarDocumento.classList.remove('was-validated');
                        await cargarDocumentosYRequisitos();
                        void mostrarAlerta(j.message || 'Tipo de documento actualizado correctamente.', 'success');
                    } catch (err) {
                        console.error(err);
                        void mostrarAlerta('Error de red al actualizar.', 'error');
                    }
                });
            }

            if (tbodyDocumentosListado) {
                tbodyDocumentosListado.addEventListener('click', function (event) {
                    const btnEdit = event.target.closest('.btn-editar-documento');
                    if (btnEdit) {
                        const id = btnEdit.getAttribute('data-id');
                        if (id) {
                            void abrirEdicionDocumento(id);
                        }
                        return;
                    }
                    const btnBan = event.target.closest('.btn-inactivar-documento');
                    if (btnBan) {
                        const id = btnBan.getAttribute('data-id');
                        if (id) {
                            void inactivarDocumento(id);
                        }
                    }
                });
            }

            if (btnLimpiarDocumentoForm) {
                btnLimpiarDocumentoForm.addEventListener('click', limpiarFormularioDocumento);
            }

            if (buscadorDocumentos) {
                buscadorDocumentos.addEventListener('input', aplicarFiltroYRender);
            }

            void cargarDocumentosYRequisitos();
        });
    </script>
@endsection
