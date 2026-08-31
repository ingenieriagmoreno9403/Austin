@extends('layouts.app')
@section('content')
    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Especialidades</h2>
                            <p class="text-muted mb-0">Alta, edición y consulta de carreras / especialidades registradas.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-baseColor fs-7 mb-2" type="button" data-bs-toggle="modal" data-bs-target="#modalAltaEspecialidad">
                            <i class="fa-solid fa-plus"></i> Nueva 
                        </button>
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="javascript:history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiEspecialidadesBase = url('/Gestion_alumnos/api/especialidades');
        @endphp

        <div class="modal fade" id="modalAltaEspecialidad" tabindex="-1" aria-labelledby="modalAltaEspecialidadLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h5 class="modal-title text-secondary" id="modalAltaEspecialidadLabel">
                                <i class="fa-solid fa-circle-plus me-2"></i>Alta de nueva especialidad
                            </h5>
                            <p class="text-muted fs-8 mb-0">Registra el número interno, nombre y descripción de la especialidad.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="formEspecialidad" class="g-3 form needs-validation" novalidate>
                        <input type="hidden" id="especialidad_edit_id" value="">
                        <div class="modal-body pt-0">
                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Número interno especialidad <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control text" name="numero_interno_especialidad" min="1" step="1" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Indica el número interno.</div>
                                </div>
                            </div>

                            <div class="col-md-8 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombre especialidad <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" name="nombre_especialidad" maxlength="100" required>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Escribe el nombre de la especialidad.</div>
                                </div>
                            </div>

                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Descripción <span class="text-danger">*</span></label>
                                    <textarea class="form-control text" name="descripcion" rows="3" required></textarea>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">La descripción es obligatoria.</div>
                                </div>
                            </div>
                        </div>

                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-baseColor fs-8 rounded-1" id="btnSubmitEspecialidadForm">
                                    <i class="fa-solid fa-check"></i> Guardar especialidad
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-moderno fs-8 rounded-1" id="btnLimpiarEspecialidadForm">
                                    <i class="fa-solid fa-eraser"></i> Limpiar
                                </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Listado de especialidades
                </h5>
                <p class="text-muted fs-8 mb-0">Consulta, edita o inactiva especialidades registradas.</p>
            </div>
            <div class="card-body">
            <!-- <div class="row mb-3">
                <div class="col-md-5 col-12">
                    <div class="form-outline">
                        <label class="form-label">Buscar</label>
                        <input type="text" id="buscadorEspecialidades" class="form-control text" placeholder="Núm. interno, nombre, descripción…">
                    </div>
                </div>
            </div> -->

            <div class="table-responsive">
                <table class="table table-stripped table-hover display mb-0" id="tablaEspecialidades">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-truncate">Núm. int.</th>
                            <th class="text-truncate">Nombre</th>
                            <th class="text-truncate">Descripción</th>
                            <th class="text-truncate">Estado</th>
                            <th class="text-truncate">Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyEspecialidadesListado">
                        <tr>
                            <td class="text-muted" colspan="5">Cargando…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        <div class="modal fade" id="modalEditarEspecialidad" tabindex="-1" aria-labelledby="modalEditarEspecialidadLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h5 class="modal-title text-secondary" id="modalEditarEspecialidadLabel">
                                <i class="fa-solid fa-pen me-2"></i>Editar especialidad
                            </h5>
                            <p class="text-muted fs-8 mb-0">Actualiza el número interno, nombre y descripción de la especialidad.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="formEditarEspecialidad" class="g-3 form needs-validation" novalidate>
                        <input type="hidden" id="especialidad_modal_edit_id" value="">
                        <div class="modal-body pt-0">
                            <div class="row">
                                <div class="col-md-4 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Número interno especialidad <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control text" name="numero_interno_especialidad" min="1" step="1" required>
                                        <div class="invalid-feedback">Indica el número interno.</div>
                                    </div>
                                </div>
                                <div class="col-md-8 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Nombre especialidad <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text" name="nombre_especialidad" maxlength="100" required>
                                        <div class="invalid-feedback">Escribe el nombre de la especialidad.</div>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Descripción <span class="text-danger">*</span></label>
                                        <textarea class="form-control text" name="descripcion" rows="3" required></textarea>
                                        <div class="invalid-feedback">La descripción es obligatoria.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
            const apiEspecialidades = @json($apiEspecialidadesBase);
            const formEspecialidad = document.getElementById('formEspecialidad');
            const especialidadEditId = document.getElementById('especialidad_edit_id');
            const tituloFormularioEspecialidad = document.getElementById('tituloFormularioEspecialidad');
            const btnSubmitEspecialidadForm = document.getElementById('btnSubmitEspecialidadForm');
            const btnLimpiarEspecialidadForm = document.getElementById('btnLimpiarEspecialidadForm');
            const tbodyEspecialidadesListado = document.getElementById('tbodyEspecialidadesListado');
            const buscadorEspecialidades = document.getElementById('buscadorEspecialidades');
            const modalAltaEspecialidad = document.getElementById('modalAltaEspecialidad');
            const modalEditarEspecialidad = document.getElementById('modalEditarEspecialidad');
            const formEditarEspecialidad = document.getElementById('formEditarEspecialidad');
            const especialidadModalEditId = document.getElementById('especialidad_modal_edit_id');

            let cacheListaCompleta = [];

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
                const el = formEspecialidad.querySelector('[name="' + name + '"]');
                return el ? String(el.value || '').trim() : '';
            };

            const setFieldVal = function (name, value) {
                const el = formEspecialidad.querySelector('[name="' + name + '"]');
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

            const limpiarFormularioEspecialidad = function () {
                formEspecialidad.reset();
                formEspecialidad.classList.remove('was-validated');
                if (especialidadEditId) {
                    especialidadEditId.value = '';
                }
                if (tituloFormularioEspecialidad) {
                    tituloFormularioEspecialidad.innerHTML = '<i class="fa-solid fa-circle-plus me-2"></i>Alta de nueva especialidad';
                }
                if (btnSubmitEspecialidadForm) {
                    btnSubmitEspecialidadForm.innerHTML = '<i class="fa-solid fa-check"></i> Guardar';
                }
            };

            const especialidadActiva = function (e) {
                if (e.activo === undefined || e.activo === null) {
                    return true;
                }
                return e.activo !== false && e.activo !== 0;
            };

            const textoEstado = function (e) {
                if (e.activo === undefined || e.activo === null) {
                    return 'Activo';
                }
                return especialidadActiva(e) ? 'Activo' : 'Inactivo';
            };

            const listaFiltrada = function () {
                const q = buscadorEspecialidades ? (buscadorEspecialidades.value || '').trim().toLowerCase() : '';
                if (!q) {
                    return cacheListaCompleta.slice();
                }
                return cacheListaCompleta.filter(function (e) {
                    const bloque = [
                        e.numero_interno_especialidad,
                        e.nombre_especialidad,
                        e.descripcion,
                    ]
                        .filter(function (v) { return v !== undefined && v !== null && String(v).trim() !== ''; })
                        .join(' ')
                        .toLowerCase();
                    return bloque.indexOf(q) !== -1;
                });
            };

            const renderTablaEspecialidades = function (lista) {
                if (!tbodyEspecialidadesListado) {
                    return;
                }

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.destroy('#tablaEspecialidades');
                }

                tbodyEspecialidadesListado.innerHTML = '';

                if (!lista.length) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td class="text-muted" colspan="5">Sin registros por el momento.</td>';
                    tbodyEspecialidadesListado.appendChild(tr);
                    return;
                }

                lista.forEach(function (e) {
                    const activa = especialidadActiva(e);
                    const badgeClass = activa ? 'badge-success-dark' : 'badge-danger-dark';
                    const btnInactivar = activa
                        ? '<button type="button" class="btn btn-danger m-1 btn-inactivar-especialidad" data-id="' + escHtml(e.id) + '" title="Inactivar"><i class="fa-solid fa-ban fs-8"></i></button>'
                        : '';

                    const tr = document.createElement('tr');
                    tr.className = 'boder-sec';
                    tr.innerHTML =
                        '<td class="text-truncate">' + escHtml(e.numero_interno_especialidad != null ? e.numero_interno_especialidad : '') + '</td>' +
                        '<td class="text-truncate text-start">' + escHtml(e.nombre_especialidad || '—') + '</td>' +
                        '<td class="text-truncate" style="max-width:14rem;" title="' + escHtml(e.descripcion || '') + '">' + escHtml(e.descripcion || '—') + '</td>' +
                        '<td class="text-truncate"><span class="badge ' + badgeClass + ' fs-9">' + escHtml(textoEstado(e)) + '</span></td>' +
                        '<td class="text-truncate">' +
                        '  <button type="button" class="btn btn-primary border-0 m-1 btn-editar-especialidad" data-id="' + escHtml(e.id) + '"><i class="fa-solid fa-pen fs-8"></i></button>' +
                        btnInactivar +
                        '</td>';
                    tbodyEspecialidadesListado.appendChild(tr);
                });

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.init('#tablaEspecialidades');
                }
            };

            const aplicarFiltroYRender = function () {
                renderTablaEspecialidades(listaFiltrada());
            };

            const cargarEspecialidades = async function () {
                try {
                    const r = await fetch(apiEspecialidades, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        void mostrarAlerta((j && j.message) ? j.message : 'No se pudo cargar el listado de especialidades.', 'error');
                        cacheListaCompleta = [];
                        aplicarFiltroYRender();
                        return;
                    }
                    cacheListaCompleta = j.data || [];
                    aplicarFiltroYRender();
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al cargar especialidades.', 'error');
                    cacheListaCompleta = [];
                    aplicarFiltroYRender();
                }
            };

            const abrirEdicionEspecialidad = async function (id) {
                try {
                    const r = await fetch(apiEspecialidades + '/' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        void mostrarAlerta((j && j.message) ? j.message : 'No se pudo cargar la especialidad.', 'error');
                        return;
                    }
                    const e = j.data;
                    if (especialidadModalEditId) {
                        especialidadModalEditId.value = String(e.id);
                    }
                    setFieldValFrom(formEditarEspecialidad, 'numero_interno_especialidad', e.numero_interno_especialidad != null ? e.numero_interno_especialidad : '');
                    setFieldValFrom(formEditarEspecialidad, 'nombre_especialidad', e.nombre_especialidad || '');
                    setFieldValFrom(formEditarEspecialidad, 'descripcion', e.descripcion || '');
                    if (formEditarEspecialidad) {
                        formEditarEspecialidad.classList.remove('was-validated');
                    }
                    if (window.bootstrap && modalEditarEspecialidad) {
                        bootstrap.Modal.getOrCreateInstance(modalEditarEspecialidad).show();
                    }
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al cargar la especialidad.', 'error');
                }
            };

            const inactivarEspecialidad = async function (id) {
                if (!await confirmarAccion('Dejará de aparecer en selecciones de alta de alumno.', '¿Inactivar esta especialidad?')) {
                    return;
                }
                try {
                    const r = await fetch(apiEspecialidades + '/' + encodeURIComponent(id) + '/inactivar', {
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
                    await cargarEspecialidades();
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al inactivar.', 'error');
                }
            };

            if (formEspecialidad) {
                formEspecialidad.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formEspecialidad.checkValidity()) {
                        formEspecialidad.classList.add('was-validated');
                        return;
                    }

                    const editId = especialidadEditId && especialidadEditId.value ? String(especialidadEditId.value).trim() : '';
                    const payload = {
                        numero_interno_especialidad: parseInt(fieldVal('numero_interno_especialidad'), 10),
                        nombre_especialidad: fieldVal('nombre_especialidad'),
                        descripcion: fieldVal('descripcion'),
                    };

                    try {
                        const url = editId ? (apiEspecialidades + '/' + encodeURIComponent(editId)) : apiEspecialidades;
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
                        if (window.bootstrap && modalAltaEspecialidad) {
                            bootstrap.Modal.getOrCreateInstance(modalAltaEspecialidad).hide();
                        }
                        limpiarFormularioEspecialidad();
                        await cargarEspecialidades();
                    } catch (err) {
                        console.error(err);
                        void mostrarAlerta('Error de red al guardar.', 'error');
                    }
                });
            }

            if (modalAltaEspecialidad) {
                modalAltaEspecialidad.addEventListener('show.bs.modal', limpiarFormularioEspecialidad);
            }

            if (formEditarEspecialidad) {
                formEditarEspecialidad.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formEditarEspecialidad.checkValidity()) {
                        formEditarEspecialidad.classList.add('was-validated');
                        return;
                    }

                    const editId = especialidadModalEditId && especialidadModalEditId.value ? String(especialidadModalEditId.value).trim() : '';
                    if (!editId) {
                        return;
                    }

                    const payload = {
                        numero_interno_especialidad: parseInt(fieldValFrom(formEditarEspecialidad, 'numero_interno_especialidad'), 10),
                        nombre_especialidad: fieldValFrom(formEditarEspecialidad, 'nombre_especialidad'),
                        descripcion: fieldValFrom(formEditarEspecialidad, 'descripcion'),
                    };

                    try {
                        const r = await fetch(apiEspecialidades + '/' + encodeURIComponent(editId), {
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

                        if (window.bootstrap && modalEditarEspecialidad) {
                            bootstrap.Modal.getOrCreateInstance(modalEditarEspecialidad).hide();
                        }
                        formEditarEspecialidad.classList.remove('was-validated');
                        await cargarEspecialidades();
                        void mostrarAlerta(j.message || 'Especialidad actualizada correctamente.', 'success');
                    } catch (err) {
                        console.error(err);
                        void mostrarAlerta('Error de red al actualizar.', 'error');
                    }
                });
            }

            if (tbodyEspecialidadesListado) {
                tbodyEspecialidadesListado.addEventListener('click', function (event) {
                    const btnEdit = event.target.closest('.btn-editar-especialidad');
                    if (btnEdit) {
                        const id = btnEdit.getAttribute('data-id');
                        if (id) {
                            void abrirEdicionEspecialidad(id);
                        }
                        return;
                    }
                    const btnBan = event.target.closest('.btn-inactivar-especialidad');
                    if (btnBan) {
                        const id = btnBan.getAttribute('data-id');
                        if (id) {
                            void inactivarEspecialidad(id);
                        }
                    }
                });
            }

            if (btnLimpiarEspecialidadForm) {
                btnLimpiarEspecialidadForm.addEventListener('click', limpiarFormularioEspecialidad);
            }

            if (buscadorEspecialidades) {
                buscadorEspecialidades.addEventListener('input', aplicarFiltroYRender);
            }

            void cargarEspecialidades();
        });
    </script>
@endsection
