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
                            <i class="fa-solid fa-file-signature"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Convenios</h2>
                            <p class="text-muted mb-0">Convenios activos entre alumnos y empresas (FED).</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('gestion_alumnos.convenios_asignar') }}" class="btn btn-baseColor fs-7 mb-2">
                            <i class="fa-solid fa-plus"></i> Nuevo convenio
                        </a>
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="javascript:history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiConveniosAsignacionesBase = url('/Gestion_alumnos/api/convenios-asignaciones');
        @endphp

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Convenios registrados
                </h5>
                <p class="text-muted fs-8 mb-0">
                    Para dar de alta uno nuevo (elegir empresa, validar alumnos y asignar), usa <strong>Nuevo convenio</strong>.
                    Un alumno solo puede tener un convenio activo a la vez; una empresa puede tener varios alumnos.
                </p>
            </div>
            <div class="card-body">

            <div class="card border-0 shadow-sm mb-3 bg-light rounded-4">
                <div class="card-body py-3">
                    <div class="row g-2 align-items-end">
                        {{-- Buscador adicional comentado: DataTables ya agrega su búsqueda principal.
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label small mb-1" for="filtroConveniosBusqueda">Buscar</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                                <input type="search" class="form-control" id="filtroConveniosBusqueda" placeholder="Empresa, alumno o matrícula…" autocomplete="off">
                            </div>
                        </div>
                        --}}
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label small mb-1" for="filtroConveniosFechaDesde">Inicio desde</label>
                            <input type="date" class="form-control form-control-sm" id="filtroConveniosFechaDesde">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label small mb-1" for="filtroConveniosFechaHasta">Inicio hasta</label>
                            <input type="date" class="form-control form-control-sm" id="filtroConveniosFechaHasta">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label small mb-1" for="filtroConveniosVistaApi">Registros</label>
                            <select class="form-select form-select-sm" id="filtroConveniosVistaApi">
                                <option value="activo">Solo activos</option>
                                <option value="todos">Activos y terminados</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label small mb-1" for="filtroConveniosEstadoTabla">Estado convenio</label>
                            <select class="form-select form-select-sm" id="filtroConveniosEstadoTabla">
                                <option value="todos">Todos</option>
                                <option value="activo">Activo</option>
                                <option value="terminado">Terminado</option>
                            </select>
                        </div>
                        <div class="col-lg-auto col-md-6 ms-lg-auto d-flex gap-2 align-items-center flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLimpiarFiltrosConvenios" title="Quitar filtros">
                                <i class="fa-solid fa-eraser"></i> Limpiar
                            </button>
                            <span class="small text-muted" id="filtroConveniosConteo"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-stripped table-hover display mb-0" id="tableConvenios">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-truncate">Empresa</th>
                            <th class="text-truncate">Alumnos</th>
                            <th class="text-truncate">Inicio</th>
                            <th class="text-truncate">Fin</th>
                            <th class="text-truncate">Estado convenio</th>
                            <th class="text-truncate">Estado validación</th>
                            <th class="text-truncate">Convenios COPARMEX</th>
                            <th class="text-truncate">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAsignacionesConvenio">
                        <tr>
                            <td class="text-muted" colspan="8">Cargando…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTerminarConvenio" tabindex="-1" aria-labelledby="modalTerminarConvenioTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-secondary" id="modalTerminarConvenioTitulo">
                        <i class="fa-solid fa-stop me-2"></i>Terminar convenio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Indica la fecha en que finaliza el convenio. El alumno podrá asignarse a otra empresa después.</p>
                    <dl class="row small mb-3 border rounded p-2 bg-light">
                        <dt class="col-sm-4 mb-1">Empresa</dt>
                        <dd class="col-sm-8 mb-1" id="modalTerminarEmpresaNombre">—</dd>
                        <dt class="col-sm-4 mb-0">Alumno</dt>
                        <dd class="col-sm-8 mb-0" id="modalTerminarAlumnoNombre">—</dd>
                    </dl>
                    <div class="mb-3">
                        <label class="form-label" for="modalTerminarFechaFin">Fecha de fin del convenio</label>
                        <input type="date" class="form-control" id="modalTerminarFechaFin" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="modalTerminarObservaciones">Observaciones al cierre <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea class="form-control" id="modalTerminarObservaciones" rows="2" maxlength="2000" placeholder="Motivo de cierre o notas…"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarTerminarConvenio"><i class="fa-solid fa-stop"></i> Terminar convenio</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Alumnos con convenio de una empresa --}}
    <div class="modal fade" id="modalAlumnosEmpresa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-secondary" id="modalAlumnosEmpresaTitulo">
                        <i class="fa-solid fa-users me-2"></i>Alumnos con convenio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-light">
                        <h6 class="text-secondary mb-2"><i class="fa-solid fa-file-arrow-up me-2"></i>Subir plantilla de asistencia</h6>
                        <div class="d-flex flex-column flex-md-row align-items-md-end gap-2">
                            <div class="flex-grow-1">
                                <label class="form-label small mb-1" for="inputPlantillaAsistencia">Archivo (.xlsx, .xls, .csv, .pdf)</label>
                                <input type="file" class="form-control form-control-sm" id="inputPlantillaAsistencia" accept=".xlsx,.xls,.csv,.pdf">
                            </div>
                            <button type="button" class="btn btn-baseColor btn-sm text-nowrap" id="btnSubirPlantillaAsistencia">
                                <i class="fa-solid fa-upload"></i> Subir
                            </button>
                        </div>
                        <div class="mt-2 small" id="statusPlantillaAsistencia"></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-stripped table-hover mb-0">
                            <thead>
                                <tr class="text-tr">
                                    <th>Matrícula</th>
                                    <th>Alumno</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyAlumnosEmpresaModal">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-between">
                    <a href="#" class="btn btn-baseColor btn-sm" id="btnControlAsistencias">
                        <i class="fa-solid fa-clipboard-check"></i> Control de Asistencias
                    </a>
                    <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/gestionAlumnosDataTables.js') }}?v=1"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiConveniosAsignaciones = @json($apiConveniosAsignacionesBase);
            const tbodyAsignacionesConvenio = document.getElementById('tbodyAsignacionesConvenio');
            const modalTerminarConvenioEl = document.getElementById('modalTerminarConvenio');
            const btnConfirmarTerminarConvenio = document.getElementById('btnConfirmarTerminarConvenio');
            const filtroConveniosBusqueda = document.getElementById('filtroConveniosBusqueda');
            const filtroConveniosFechaDesde = document.getElementById('filtroConveniosFechaDesde');
            const filtroConveniosFechaHasta = document.getElementById('filtroConveniosFechaHasta');
            const filtroConveniosVistaApi = document.getElementById('filtroConveniosVistaApi');
            const filtroConveniosEstadoTabla = document.getElementById('filtroConveniosEstadoTabla');
            const btnLimpiarFiltrosConvenios = document.getElementById('btnLimpiarFiltrosConvenios');
            const filtroConveniosConteo = document.getElementById('filtroConveniosConteo');
            let asignacionesConvenio = [];
            let filtroBusquedaTimer = null;

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

            const escapeHtml = function (txt) {
                if (txt === null || txt === undefined) {
                    return '';
                }
                const d = document.createElement('div');
                d.textContent = String(txt);
                return d.innerHTML;
            };

            const fmtFechaConvenio = function (iso) {
                if (!iso) {
                    return '—';
                }
                try {
                    const s = String(iso).trim();
                    const d = new Date(s.indexOf('T') >= 0 ? s : s + 'T12:00:00');
                    if (Number.isNaN(d.getTime())) {
                        return escapeHtml(s);
                    }
                    return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
                } catch (e) {
                    return escapeHtml(String(iso));
                }
            };

            const badgeEstadoConvenio = function (estado) {
                const e = String(estado || '').toLowerCase();
                if (e === 'activo') {
                    return '<span class="badge bg-success">Activo</span>';
                }
                if (e === 'terminado') {
                    return '<span class="badge bg-secondary">Terminado</span>';
                }
                return '<span class="badge bg-light text-dark">' + escapeHtml(estado || '—') + '</span>';
            };

            const pintarBadgeEstado = function (ok) {
                return ok ? '<span class="badge bg-success">Cumple</span>' : '<span class="badge bg-danger">No cumple</span>';
            };

            const getCsrfToken = function () {
                const m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            };

            const hoyIsoLocal = function () {
                const d = new Date();
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + day;
            };

            const cargarAsignacionesDesdeApi = async function () {
                try {
                    const vista = filtroConveniosVistaApi && filtroConveniosVistaApi.value === 'todos' ? 'todos' : 'activo';
                    const r = await fetch(apiConveniosAsignaciones + '?estado=' + encodeURIComponent(vista), { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        asignacionesConvenio = [];
                        return;
                    }
                    asignacionesConvenio = Array.isArray(j.data) ? j.data : [];
                } catch (err) {
                    console.error(err);
                    asignacionesConvenio = [];
                }
            };

            const normalizarBusquedaConvenio = function (s) {
                return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
            };

            const filtrarAsignacionesConvenio = function () {
                const q = filtroConveniosBusqueda ? normalizarBusquedaConvenio(filtroConveniosBusqueda.value) : '';
                const fd = filtroConveniosFechaDesde && filtroConveniosFechaDesde.value ? String(filtroConveniosFechaDesde.value).trim() : '';
                const fh = filtroConveniosFechaHasta && filtroConveniosFechaHasta.value ? String(filtroConveniosFechaHasta.value).trim() : '';
                const estF = filtroConveniosEstadoTabla ? String(filtroConveniosEstadoTabla.value || 'todos') : 'todos';
                return asignacionesConvenio.filter(function (a) {
                    if (q) {
                        const mat = a.numero_matricula != null ? String(a.numero_matricula) : '';
                        const blob = normalizarBusquedaConvenio([
                            a.empresa_nombre,
                            a.alumno_nombre,
                            mat,
                            String(a.empresa_id || ''),
                            String(a.alumno_id || ''),
                            a.observaciones || '',
                        ].join(' '));
                        if (!blob.includes(q)) {
                            return false;
                        }
                    }
                    const fi = a.fecha_inicio ? String(a.fecha_inicio).slice(0, 10) : '';
                    if (fd && fi && fi < fd) {
                        return false;
                    }
                    if (fh && fi && fi > fh) {
                        return false;
                    }
                    if (estF !== 'todos') {
                        const e = String(a.estado || '').toLowerCase();
                        if (estF === 'activo' && e !== 'activo') {
                            return false;
                        }
                        if (estF === 'terminado' && e !== 'terminado') {
                            return false;
                        }
                    }
                    return true;
                });
            };

            const renderAsignacionesConvenio = function () {
                if (!tbodyAsignacionesConvenio) {
                    return;
                }

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.destroy('#tableConvenios');
                }

                tbodyAsignacionesConvenio.innerHTML = '';
                const total = asignacionesConvenio.length;
                const lista = filtrarAsignacionesConvenio();
                if (filtroConveniosConteo) {
                    if (!total) {
                        filtroConveniosConteo.textContent = '0 registros';
                    } else {
                        filtroConveniosConteo.textContent = lista.length === total
                            ? (total + (total === 1 ? ' registro' : ' registros'))
                            : ('Mostrando ' + lista.length + ' de ' + total);
                    }
                }
                if (!total) {
                    tbodyAsignacionesConvenio.innerHTML = '<tr><td class="text-muted" colspan="8">Sin convenios. Usa <strong>Nuevo convenio</strong> para registrar uno.</td></tr>';
                    return;
                }
                if (!lista.length) {
                    tbodyAsignacionesConvenio.innerHTML = '<tr><td class="text-muted" colspan="8">Ningún registro coincide con los filtros. <button type="button" class="btn btn-link btn-sm p-0 align-baseline" id="btnQuitarFiltrosInline">Quitar filtros</button></td></tr>';
                    const btnInline = document.getElementById('btnQuitarFiltrosInline');
                    if (btnInline) {
                        btnInline.addEventListener('click', function () {
                            if (btnLimpiarFiltrosConvenios) {
                                btnLimpiarFiltrosConvenios.click();
                            }
                        });
                    }
                    return;
                }
                lista.forEach(function (a) {
                    const tr = document.createElement('tr');
                    const idRow = a.id != null ? String(a.id) : '';
                    const esActivo = String(a.estado || '').toLowerCase() === 'activo';
                    const celdaAccion = esActivo
                        ? '<button type="button" class="btn btn-sm btn-danger btn-quitar-asignacion" title="Terminar convenio" data-asignacion-id="' + escapeHtml(idRow) + '" data-empresa-id="' + escapeHtml(a.empresa_id) + '" data-alumno-id="' + escapeHtml(a.alumno_id) + '" data-empresa-nombre="' + escapeHtml(a.empresa_nombre || '') + '" data-alumno-nombre="' + escapeHtml(a.alumno_nombre || '') + '"><i class="fa-solid fa-stop"></i></button>'
                        : '<span class="text-muted small">Finalizado</span>';
                    tr.innerHTML =
                        '<td>' + escapeHtml(a.empresa_nombre || '—') + '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-outline-primary btn-ver-alumnos-empresa" data-empresa-id="' + escapeHtml(a.empresa_id) + '" data-empresa-nombre="' + escapeHtml(a.empresa_nombre || '') + '"><i class="fa-solid fa-users"></i> Ver Alumnos</button></td>' +
                        '<td class="text-nowrap small">' + fmtFechaConvenio(a.fecha_inicio) + '</td>' +
                        '<td class="text-nowrap small">' + fmtFechaConvenio(a.fecha_fin) + '</td>' +
                        '<td>' + badgeEstadoConvenio(a.estado) + '</td>' +
                        '<td>' + pintarBadgeEstado(a.cumple) + '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-outline-primary btn-placeholder-convenios" data-alumno-id="' + escapeHtml(a.alumno_id) + '"><i class="fa-solid fa-file-signature"></i> Convenios COPARMEX</button></td>' +
                        '<td>' + celdaAccion + '</td>';
                    tbodyAsignacionesConvenio.appendChild(tr);
                });

                if (window.GestionAlumnosDataTables) {
                    window.GestionAlumnosDataTables.init('#tableConvenios');
                }
            };

            const aplicarFiltrosYRender = function () {
                renderAsignacionesConvenio();
            };

            const limpiarFiltrosConvenios = function () {
                if (filtroConveniosBusqueda) {
                    filtroConveniosBusqueda.value = '';
                }
                if (filtroConveniosFechaDesde) {
                    filtroConveniosFechaDesde.value = '';
                }
                if (filtroConveniosFechaHasta) {
                    filtroConveniosFechaHasta.value = '';
                }
                if (filtroConveniosEstadoTabla) {
                    filtroConveniosEstadoTabla.value = 'todos';
                }
                aplicarFiltrosYRender();
            };

            if (btnLimpiarFiltrosConvenios) {
                btnLimpiarFiltrosConvenios.addEventListener('click', function () {
                    limpiarFiltrosConvenios();
                });
            }

            if (filtroConveniosBusqueda) {
                filtroConveniosBusqueda.addEventListener('input', function () {
                    if (filtroBusquedaTimer) {
                        window.clearTimeout(filtroBusquedaTimer);
                    }
                    filtroBusquedaTimer = window.setTimeout(function () {
                        aplicarFiltrosYRender();
                    }, 200);
                });
            }
            [filtroConveniosFechaDesde, filtroConveniosFechaHasta, filtroConveniosEstadoTabla].forEach(function (el) {
                if (el) {
                    el.addEventListener('change', function () {
                        aplicarFiltrosYRender();
                    });
                }
            });

            if (filtroConveniosVistaApi) {
                filtroConveniosVistaApi.addEventListener('change', async function () {
                    await cargarAsignacionesDesdeApi();
                    aplicarFiltrosYRender();
                });
            }

            if (btnConfirmarTerminarConvenio && modalTerminarConvenioEl) {
                btnConfirmarTerminarConvenio.addEventListener('click', function () {
                    const asignacionId = String(modalTerminarConvenioEl.dataset.asignacionId || '').trim();
                    const inpFin = document.getElementById('modalTerminarFechaFin');
                    const txtObs = document.getElementById('modalTerminarObservaciones');
                    const fechaFin = inpFin ? String(inpFin.value || '').trim() : '';
                    const observaciones = txtObs ? String(txtObs.value || '').trim() : '';
                    if (!asignacionId) {
                        window.alert('No se encontró el registro de convenio.');
                        return;
                    }
                    if (!fechaFin) {
                        window.alert('Indica la fecha de fin del convenio.');
                        return;
                    }
                    const body = { fecha_fin: fechaFin };
                    if (observaciones) {
                        body.observaciones = observaciones;
                    }
                    fetch(apiConveniosAsignaciones + '/' + encodeURIComponent(asignacionId) + '/terminar', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(body),
                    }).then(async function (r) {
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            window.alert((j && j.message) ? j.message : 'No se pudo actualizar el convenio.');
                            return;
                        }
                        if (modalTerminarConvenioEl && window.bootstrap && window.bootstrap.Modal) {
                            const inst = window.bootstrap.Modal.getInstance(modalTerminarConvenioEl);
                            if (inst) {
                                inst.hide();
                            }
                        }
                        await cargarAsignacionesDesdeApi();
                        renderAsignacionesConvenio();
                    }).catch(function (err) {
                        console.error(err);
                        window.alert('Error de red.');
                    });
                });
            }

            if (tbodyAsignacionesConvenio) {
                tbodyAsignacionesConvenio.addEventListener('click', function (event) {
                    const btnQuitar = event.target.closest('.btn-quitar-asignacion');
                    if (btnQuitar) {
                        const asignacionId = String(btnQuitar.getAttribute('data-asignacion-id') || '').trim();
                        if (!asignacionId) {
                            window.alert('No se encontró el registro de convenio.');
                            return;
                        }
                        const nombreEmp = String(btnQuitar.getAttribute('data-empresa-nombre') || '').trim();
                        const nombreAl = String(btnQuitar.getAttribute('data-alumno-nombre') || '').trim();
                        const elE = document.getElementById('modalTerminarEmpresaNombre');
                        const elA = document.getElementById('modalTerminarAlumnoNombre');
                        const inpFin = document.getElementById('modalTerminarFechaFin');
                        const txtObsT = document.getElementById('modalTerminarObservaciones');
                        if (elE) {
                            elE.textContent = nombreEmp || '—';
                        }
                        if (elA) {
                            elA.textContent = nombreAl || '—';
                        }
                        if (inpFin) {
                            inpFin.value = hoyIsoLocal();
                        }
                        if (txtObsT) {
                            txtObsT.value = '';
                        }
                        if (modalTerminarConvenioEl) {
                            modalTerminarConvenioEl.dataset.asignacionId = asignacionId;
                        }
                        if (modalTerminarConvenioEl && window.bootstrap && window.bootstrap.Modal) {
                            window.bootstrap.Modal.getOrCreateInstance(modalTerminarConvenioEl).show();
                        }
                        return;
                    }

                    const btnVerAlumnos = event.target.closest('.btn-ver-alumnos-empresa');
                    if (btnVerAlumnos) {
                        const empresaId = String(btnVerAlumnos.getAttribute('data-empresa-id') || '').trim();
                        const empresaNombre = String(btnVerAlumnos.getAttribute('data-empresa-nombre') || '').trim();
                        const tituloModal = document.getElementById('modalAlumnosEmpresaTitulo');
                        const tbodyModal = document.getElementById('tbodyAlumnosEmpresaModal');
                        if (tituloModal) tituloModal.textContent = 'Alumnos con convenio — ' + (empresaNombre || 'Empresa');
                        if (tbodyModal) {
                            tbodyModal.innerHTML = '';
                            var alumnosEmpresa = asignacionesConvenio.filter(function (x) {
                                return String(x.empresa_id) === empresaId;
                            });
                            if (!alumnosEmpresa.length) {
                                tbodyModal.innerHTML = '<tr><td class="text-muted" colspan="5">No hay alumnos asignados a esta empresa.</td></tr>';
                            } else {
                                alumnosEmpresa.forEach(function (a) {
                                    var tr = document.createElement('tr');
                                    tr.innerHTML =
                                        '<td>' + escapeHtml(a.numero_matricula != null ? a.numero_matricula : '—') + '</td>' +
                                        '<td>' + escapeHtml(a.alumno_nombre || '—') + '</td>' +
                                        '<td class="text-nowrap small">' + fmtFechaConvenio(a.fecha_inicio) + '</td>' +
                                        '<td class="text-nowrap small">' + fmtFechaConvenio(a.fecha_fin) + '</td>' +
                                        '<td>' + badgeEstadoConvenio(a.estado) + '</td>';
                                    tbodyModal.appendChild(tr);
                                });
                            }
                        }
                        var modalAlumnosEl = document.getElementById('modalAlumnosEmpresa');
                        if (modalAlumnosEl) {
                            modalAlumnosEl.dataset.empresaId = empresaId;
                            var inputFile = document.getElementById('inputPlantillaAsistencia');
                            var statusDiv = document.getElementById('statusPlantillaAsistencia');
                            if (inputFile) inputFile.value = '';
                            if (statusDiv) statusDiv.innerHTML = '';
                            var btnAsistencias = document.getElementById('btnControlAsistencias');
                            if (btnAsistencias) {
                                btnAsistencias.href = '{{ url("/Gestion_alumnos/control-asistencias") }}?empresa_id=' + encodeURIComponent(empresaId);
                            }
                        }
                        if (modalAlumnosEl && window.bootstrap && window.bootstrap.Modal) {
                            window.bootstrap.Modal.getOrCreateInstance(modalAlumnosEl).show();
                        }
                        return;
                    }

                    const btnPlaceholder = event.target.closest('.btn-placeholder-convenios');
                    if (btnPlaceholder) {
                        window.alert('Botón listo para conectar: convenio de aprendizaje y convenio de colaboración COPARMEX.');
                    }
                });
            }

            const btnSubirPlantilla = document.getElementById('btnSubirPlantillaAsistencia');
            if (btnSubirPlantilla) {
                btnSubirPlantilla.addEventListener('click', function () {
                    const inputFile = document.getElementById('inputPlantillaAsistencia');
                    const statusDiv = document.getElementById('statusPlantillaAsistencia');
                    const modalEl = document.getElementById('modalAlumnosEmpresa');
                    const empresaId = modalEl ? String(modalEl.dataset.empresaId || '').trim() : '';
                    const file = inputFile && inputFile.files && inputFile.files.length ? inputFile.files[0] : null;

                    if (!file) {
                        window.alert('Selecciona un archivo para subir.');
                        return;
                    }
                    if (!empresaId) {
                        window.alert('No se identificó la empresa.');
                        return;
                    }

                    const fd = new FormData();
                    fd.append('empresa_id', empresaId);
                    fd.append('archivo', file);

                    if (statusDiv) statusDiv.innerHTML = '<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Subiendo...</span>';

                    fetch(apiConveniosAsignaciones + '/plantilla-asistencia', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: fd,
                    }).then(async function (r) {
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            var msg = (j && j.message) ? j.message : 'No se pudo subir el archivo.';
                            if (j && j.errors) msg = Object.values(j.errors).flat().join('\n');
                            if (statusDiv) statusDiv.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> ' + escapeHtml(msg) + '</span>';
                            return;
                        }
                        if (statusDiv) statusDiv.innerHTML = '<span class="text-success"><i class="fa-solid fa-circle-check"></i> ' + escapeHtml(j.message || 'Plantilla subida correctamente.') + '</span>';
                        if (inputFile) inputFile.value = '';
                    }).catch(function (err) {
                        console.error(err);
                        if (statusDiv) statusDiv.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> Error de red al subir.</span>';
                    });
                });
            }

            void (async function init() {
                await cargarAsignacionesDesdeApi();
                renderAsignacionesConvenio();
            })();
        });
    </script>
@endsection
