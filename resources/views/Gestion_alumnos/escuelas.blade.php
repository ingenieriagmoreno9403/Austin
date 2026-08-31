@extends('layouts.app')
@section('content')
    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-school"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Escuelas</h2>
                            <p class="text-muted mb-0">Alta, edición y consulta de escuelas registradas.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-baseColor fs-7 mb-2" type="button" data-bs-toggle="modal" data-bs-target="#modalAltaEscuela">
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
            $apiEscuelasBase = url('/Gestion_alumnos/api/escuelas');
        @endphp

        <div class="modal fade" id="modalAltaEscuela" tabindex="-1" aria-labelledby="modalAltaEscuelaLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h5 class="modal-title text-secondary" id="modalAltaEscuelaLabel">
                                <i class="fa-solid fa-circle-plus me-2"></i>Alta de nueva escuela
                            </h5>
                            <p class="text-muted fs-8 mb-0">Captura los datos generales y de contacto de la escuela.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="formEscuela" class="g-3 modern-form needs-validation" novalidate>
                        <input type="hidden" id="escuela_edit_id" value="">
                        <div class="modal-body pt-0">
                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" name="nombre" maxlength="255" required>
                                    <div class="invalid-feedback">El nombre de la escuela es obligatorio.</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Número SEP <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text" name="numero_sep" maxlength="50" required>
                                    <div class="invalid-feedback">El número SEP es obligatorio.</div>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control text" name="telefono" maxlength="30">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Dirección</label>
                                    <textarea class="form-control text" name="direccion" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-baseColor" id="btnSubmitEscuelaForm">
                                    <i class="fa-solid fa-check"></i> Guardar
                                </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Listado de escuelas
                </h5>
                <p class="text-muted fs-8 mb-0">Consulta, edita o inactiva escuelas registradas.</p>
            </div>
            <!-- <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-5 col-12">
                    <div class="form-outline">
                        <label class="form-label">Buscar</label>
                        <input type="text" id="buscadorEscuelas" class="form-control text" placeholder="Nombre, dirección, SEP, teléfono…">
                    </div>
                </div>
            </div> -->

            <div class="table-responsive">
                <table class="table table-stripped table-hover display mb-0" id="tableEscuelas">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-truncate">Nombre</th>
                            <th class="text-truncate">Dirección</th>
                            <th class="text-truncate">Núm. SEP</th>
                            <th class="text-truncate">Teléfono</th>
                            <th class="text-truncate">Estado</th>
                            <th class="text-truncate">Opciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyEscuelasListado">
                        <tr>
                            <td class="text-muted" colspan="6">Cargando…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        <div class="modal fade" id="modalEditarEscuela" tabindex="-1" aria-labelledby="modalEditarEscuelaLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <div>
                            <h5 class="modal-title text-secondary" id="modalEditarEscuelaLabel">
                                <i class="fa-solid fa-pen me-2"></i>Editar escuela
                            </h5>
                            <p class="text-muted fs-8 mb-0">Actualiza los datos generales y de contacto de la escuela.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form id="formEditarEscuela" class="g-3 modern-form needs-validation" novalidate>
                        <input type="hidden" id="escuela_modal_edit_id" value="">
                        <div class="modal-body pt-0">
                            <div class="row">
                                <div class="col-md-6 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text" name="nombre" maxlength="255" required>
                                        <div class="invalid-feedback">El nombre de la escuela es obligatorio.</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Número SEP <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text" name="numero_sep" maxlength="50" required>
                                        <div class="invalid-feedback">El número SEP es obligatorio.</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Teléfono</label>
                                        <input type="text" class="form-control text" name="telefono" maxlength="30">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Dirección</label>
                                        <textarea class="form-control text" name="direccion" rows="2"></textarea>
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
            const apiEscuelas = @json($apiEscuelasBase);
            const formEscuela = document.getElementById('formEscuela');
            const escuelaEditId = document.getElementById('escuela_edit_id');
            const tituloFormularioEscuela = document.getElementById('tituloFormularioEscuela');
            const tbodyEscuelasListado = document.getElementById('tbodyEscuelasListado');
            const buscadorEscuelas = document.getElementById('buscadorEscuelas');
            const btnSubmitEscuelaForm = document.getElementById('btnSubmitEscuelaForm');
            const modalAltaEscuela = document.getElementById('modalAltaEscuela');
            const modalEditarEscuela = document.getElementById('modalEditarEscuela');
            const formEditarEscuela = document.getElementById('formEditarEscuela');
            const escuelaModalEditId = document.getElementById('escuela_modal_edit_id');

            let cacheListaCompleta = [];

            const convertirCampoEscuelaAMayusculas = function (elemento) {
                if (!elemento || !['INPUT', 'TEXTAREA'].includes(elemento.tagName)) {
                    return;
                }
                const inicio = elemento.selectionStart;
                const fin = elemento.selectionEnd;
                const valorMayusculas = String(elemento.value || '').toLocaleUpperCase('es-MX');
                if (elemento.value === valorMayusculas) {
                    return;
                }
                elemento.value = valorMayusculas;
                if (typeof inicio === 'number' && typeof fin === 'number') {
                    elemento.setSelectionRange(inicio, fin);
                }
            };

            if (formEscuela) {
                formEscuela.addEventListener('input', function (event) {
                    convertirCampoEscuelaAMayusculas(event.target);
                });
            }

            const getCsrfToken = function () {
                const m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            };

            const fieldVal = function (name) {
                const el = formEscuela.querySelector('[name="' + name + '"]');
                return el ? String(el.value || '').trim() : '';
            };

            const setFieldVal = function (name, value) {
                const el = formEscuela.querySelector('[name="' + name + '"]');
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

            const formPayload = function () {
                return {
                    nombre: fieldVal('nombre'),
                    direccion: fieldVal('direccion') || null,
                    numero_sep: fieldVal('numero_sep'),
                    telefono: fieldVal('telefono') || null,
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

            const limpiarFormularioEscuela = function () {
                formEscuela.reset();
                formEscuela.classList.remove('was-validated');
                if (escuelaEditId) {
                    escuelaEditId.value = '';
                }
                if (tituloFormularioEscuela) {
                    tituloFormularioEscuela.innerHTML = '<i class="fa-solid fa-circle-plus me-2"></i>Alta de nueva escuela';
                }
                if (btnSubmitEscuelaForm) {
                    btnSubmitEscuelaForm.innerHTML = '<i class="fa-solid fa-check"></i> Guardar escuela';
                }
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

            const escuelaActiva = function (e) {
                if (e.activo === undefined || e.activo === null) {
                    return true;
                }
                return e.activo !== false && e.activo !== 0;
            };

            const textoEstado = function (e) {
                if (e.activo === undefined || e.activo === null) {
                    return 'Activo';
                }
                return escuelaActiva(e) ? 'Activo' : 'Inactivo';
            };

            const listaFiltrada = function () {
                const q = buscadorEscuelas ? (buscadorEscuelas.value || '').trim().toLowerCase() : '';
                if (!q) {
                    return cacheListaCompleta.slice();
                }
                return cacheListaCompleta.filter(function (e) {
                    const bloque = [
                        e.nombre,
                        e.Direccion,
                        e.numero_sep,
                        e.telefono,
                    ]
                        .filter(Boolean)
                        .join(' ')
                        .toLowerCase();
                    return bloque.indexOf(q) !== -1;
                });
            };

            const renderTablaEscuelas = function (lista) {
                if (!tbodyEscuelasListado) {
                    return;
                }

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.destroy('#tableEscuelas');
                }

                tbodyEscuelasListado.innerHTML = '';

                if (!lista.length) {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td class="text-muted" colspan="6">Sin registros por el momento.</td>';
                    tbodyEscuelasListado.appendChild(tr);
                    return;
                }

                lista.forEach(function (e) {
                    const activa = escuelaActiva(e);
                    const badgeClass = activa ? 'badge-success-dark' : 'badge-danger-dark';
                    const btnInactivar = activa
                        ? '<button type="button" class="btn btn-danger m-1 btn-inactivar-escuela" data-id="' + escHtml(e.id) + '" title="Inactivar"><i class="fa-solid fa-ban fs-8"></i></button>'
                        : '';

                    const tr = document.createElement('tr');
                    tr.className = 'boder-sec';
                    tr.innerHTML =
                        '<td class="text-truncate text-start">' + escHtml(e.nombre || '—') + '</td>' +
                        '<td class="text-truncate" style="max-width:12rem;" title="' + escHtml(e.Direccion || '') + '">' + escHtml(e.Direccion || '—') + '</td>' +
                        '<td class="text-truncate">' + escHtml(e.numero_sep || '—') + '</td>' +
                        '<td class="text-truncate">' + escHtml(e.telefono || '—') + '</td>' +
                        '<td class="text-truncate"><span class="badge ' + badgeClass + ' fs-9">' + escHtml(textoEstado(e)) + '</span></td>' +
                        '<td class="text-truncate">' +
                        '  <button type="button" class="btn btn-primary border-0 m-1 btn-editar-escuela" data-id="' + escHtml(e.id) + '"><i class="fa-solid fa-pen fs-8"></i></button>' +
                        btnInactivar +
                        '</td>';
                    tbodyEscuelasListado.appendChild(tr);
                });

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.init('#tableEscuelas');
                }
            };

            const aplicarFiltroYRender = function () {
                renderTablaEscuelas(listaFiltrada());
            };

            const cargarEscuelas = async function () {
                try {
                    const r = await fetch(apiEscuelas, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        void mostrarAlerta((j && j.message) ? j.message : 'No se pudo cargar el listado de escuelas.', 'error');
                        cacheListaCompleta = [];
                        aplicarFiltroYRender();
                        return;
                    }
                    cacheListaCompleta = j.data || [];
                    aplicarFiltroYRender();
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al cargar escuelas.', 'error');
                    cacheListaCompleta = [];
                    aplicarFiltroYRender();
                }
            };

            const abrirEdicionEscuela = async function (id) {
                try {
                    const r = await fetch(apiEscuelas + '/' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        void mostrarAlerta((j && j.message) ? j.message : 'No se pudo cargar la escuela.', 'error');
                        return;
                    }
                    const e = j.data;
                    if (escuelaModalEditId) {
                        escuelaModalEditId.value = String(e.id);
                    }
                    setFieldValFrom(formEditarEscuela, 'nombre', e.nombre || '');
                    setFieldValFrom(formEditarEscuela, 'direccion', e.Direccion || '');
                    setFieldValFrom(formEditarEscuela, 'numero_sep', e.numero_sep || '');
                    setFieldValFrom(formEditarEscuela, 'telefono', e.telefono || '');
                    if (formEditarEscuela) {
                        formEditarEscuela.classList.remove('was-validated');
                    }
                    if (window.bootstrap && modalEditarEscuela) {
                        bootstrap.Modal.getOrCreateInstance(modalEditarEscuela).show();
                    }
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al cargar la escuela.', 'error');
                }
            };

            const inactivarEscuela = async function (id) {
                if (!await confirmarAccion('Dejará de aparecer en selecciones de alta de alumno.', '¿Inactivar esta escuela?')) {
                    return;
                }
                try {
                    const r = await fetch(apiEscuelas + '/' + encodeURIComponent(id) + '/inactivar', {
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
                    await cargarEscuelas();
                } catch (err) {
                    console.error(err);
                    void mostrarAlerta('Error de red al inactivar.', 'error');
                }
            };

            if (formEscuela) {
                formEscuela.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formEscuela.checkValidity()) {
                        formEscuela.classList.add('was-validated');
                        return;
                    }

                    const editId = escuelaEditId && escuelaEditId.value ? String(escuelaEditId.value).trim() : '';
                    const payload = formPayload();

                    try {
                        const url = editId ? (apiEscuelas + '/' + encodeURIComponent(editId)) : apiEscuelas;
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
                        if (window.bootstrap && modalAltaEscuela) {
                            bootstrap.Modal.getOrCreateInstance(modalAltaEscuela).hide();
                        }
                        limpiarFormularioEscuela();
                        await cargarEscuelas();
                    } catch (err) {
                        console.error(err);
                        void mostrarAlerta('Error de red al guardar.', 'error');
                    }
                });
            }

            if (modalAltaEscuela) {
                modalAltaEscuela.addEventListener('show.bs.modal', limpiarFormularioEscuela);
            }

            if (formEditarEscuela) {
                formEditarEscuela.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!formEditarEscuela.checkValidity()) {
                        formEditarEscuela.classList.add('was-validated');
                        return;
                    }

                    const editId = escuelaModalEditId && escuelaModalEditId.value ? String(escuelaModalEditId.value).trim() : '';
                    if (!editId) {
                        return;
                    }

                    const payload = {
                        nombre: fieldValFrom(formEditarEscuela, 'nombre'),
                        direccion: fieldValFrom(formEditarEscuela, 'direccion') || null,
                        numero_sep: fieldValFrom(formEditarEscuela, 'numero_sep'),
                        telefono: fieldValFrom(formEditarEscuela, 'telefono') || null,
                    };

                    try {
                        const r = await fetch(apiEscuelas + '/' + encodeURIComponent(editId), {
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

                        if (window.bootstrap && modalEditarEscuela) {
                            bootstrap.Modal.getOrCreateInstance(modalEditarEscuela).hide();
                        }
                        formEditarEscuela.classList.remove('was-validated');
                        await cargarEscuelas();
                        void mostrarAlerta(j.message || 'Escuela actualizada correctamente.', 'success');
                    } catch (err) {
                        console.error(err);
                        void mostrarAlerta('Error de red al actualizar.', 'error');
                    }
                });
            }

            if (tbodyEscuelasListado) {
                tbodyEscuelasListado.addEventListener('click', function (event) {
                    const btnEdit = event.target.closest('.btn-editar-escuela');
                    if (btnEdit) {
                        const id = btnEdit.getAttribute('data-id');
                        if (id) {
                            void abrirEdicionEscuela(id);
                        }
                        return;
                    }
                    const btnBan = event.target.closest('.btn-inactivar-escuela');
                    if (btnBan) {
                        const id = btnBan.getAttribute('data-id');
                        if (id) {
                            void inactivarEscuela(id);
                        }
                    }
                });
            }

            if (buscadorEscuelas) {
                buscadorEscuelas.addEventListener('input', aplicarFiltroYRender);
            }

            void cargarEscuelas();
        });
    </script>
@endsection
