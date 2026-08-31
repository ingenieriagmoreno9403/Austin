@extends('layouts.app')
@section('content')
    @include('Gestion_alumnos.partials.sweet_alerts')

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <style>
        .calendar-grid-eventos {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
        }
        .calendar-day-eventos {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            min-height: 84px;
            padding: 8px;
            background: #fff;
            cursor: pointer;
            transition: all .15s ease;
        }
        .calendar-day-eventos:hover {
            border-color: #fd7e14;
            box-shadow: 0 2px 10px rgba(0,0,0,.08);
        }
        .calendar-day-eventos.is-out {
            opacity: .45;
            cursor: default;
        }
        .calendar-day-eventos .num {
            font-weight: 600;
            font-size: 13px;
        }
        .calendar-day-eventos .badge-count {
            font-size: 11px;
        }
    </style>
    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Eventos de socios</h2>
                            <p class="text-muted mb-0">Alta de eventos con múltiples encargados y monto por encargado.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button type="button" class="btn btn-baseColor fs-8 mb-2" data-bs-toggle="modal" data-bs-target="#modalEventoSocio">
                            <i class="fa-solid fa-plus"></i> Nuevo evento
                        </button>
                        <a href="{{ route('gestion_alumnos.socios') }}" class="btn btn-baseColor-light fs-8 mb-2">
                            <i class="fa-solid fa-arrow-left"></i> Volver a socios
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiEventos = url('/Gestion_alumnos/api/socios/eventos');
            $apiCatalogoEncargados = url('/Gestion_alumnos/api/socios/eventos/catalogo-encargados');
        @endphp

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="text-secondary mb-1">
                            <i class="fa-solid fa-calendar-days me-2"></i>Calendario de eventos
                        </h5>
                        <p class="text-muted fs-8 mb-0">Da clic en un día para dar de alta evento con fecha precargada.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-baseColor-light" id="btnMesAnteriorEventos">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <span class="fw-bold" id="tituloMesEventos">-</span>
                        <button type="button" class="btn btn-sm btn-baseColor-light" id="btnMesSiguienteEventos">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="calendar-grid-eventos mb-2 text-muted small">
                    <div>Lu</div><div>Ma</div><div>Mi</div><div>Ju</div><div>Vi</div><div>Sa</div><div>Do</div>
                </div>
                <div class="calendar-grid-eventos" id="calendarGridEventos"></div>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Listado de eventos
                </h5>
                <p class="text-muted fs-8 mb-0">Consulta eventos, costos estimados y responsables asignados.</p>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-5 col-12">
                        <label class="form-label">Buscar evento</label>
                        <input type="text" id="buscadorEventosSocio" class="form-control text" placeholder="Nombre, tipo, descripción...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-stripped table-hover mb-0">
                        <thead>
                            <tr class="text-tr">
                                <th>Evento</th>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Lugar</th>
                                <th>Horario</th>
                                <th>Estimado</th>
                                <th>Comprometido</th>
                                <th>Encargados</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyEventosSocio">
                            <tr><td class="text-muted" colspan="9">Sin eventos registrados.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEventoSocio" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title text-secondary">
                            <i class="fa-solid fa-calendar-plus me-2"></i>Nuevo evento
                        </h5>
                        <p class="text-muted fs-8 mb-0">Asigna uno o varios encargados con su monto de aportación.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body pt-0">
                    <form id="formEventoSocio" class="g-3 form needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-5 col-12 mt-2">
                                <label class="form-label">Nombre del evento <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text" name="nombre_evento" maxlength="200" required>
                            </div>
                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Fecha</label>
                                <input type="date" class="form-control text" name="fecha_evento">
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <label class="form-label">Tipo de evento</label>
                                <input type="text" class="form-control text" name="tipo_evento" maxlength="120" placeholder="Ej. Almuerzo, torneo de golf">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <label class="form-label">Lugar del evento <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text" name="lugar_evento" maxlength="200" required placeholder="Ej. Salón principal">
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <label class="form-label">Hora inicio <span class="text-danger">*</span></label>
                                <input type="time" class="form-control text" name="hora_inicio" required>
                            </div>
                            <div class="col-md-2 col-12 mt-2">
                                <label class="form-label">Hora fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-control text" name="hora_fin" required>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control text" name="descripcion" rows="2"></textarea>
                            </div>
                            <div class="col-md-4 col-12 mt-2">
                                <label class="form-label">Monto estimado</label>
                                <input type="number" class="form-control text" name="monto_estimado" min="0" step="0.01" value="0">
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Encargados y aportaciones</h6>
                            <button type="button" class="btn btn-sm btn-baseColor-light" id="btnAgregarEncargadoEvento">
                                <i class="fa-solid fa-user-plus"></i> Agregar encargado
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Socio</th>
                                        <th>Rol</th>
                                        <th>Monto aportación</th>
                                        <th style="width:70px;">Quitar</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyEncargadosEvento"></tbody>
                            </table>
                        </div>
                    </form>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                        <h6 class="mb-0">Eventos del día seleccionado</h6>
                        <span class="fw-bold" id="lblFechaSeleccionadaEventos">Sin selección</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-stripped table-hover mb-0">
                            <thead>
                                <tr class="text-tr">
                                    <th>Evento</th>
                                    <th>Tipo</th>
                                    <th>Lugar</th>
                                    <th>Horario</th>
                                    <th>Estimado</th>
                                    <th>Comprometido</th>
                                    <th>Encargados</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyEventosDiaSeleccionado">
                                <tr><td class="text-muted" colspan="8">Selecciona un día para ver eventos.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-moderno" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formEventoSocio" class="btn btn-baseColor fs-8 rounded-1">
                        <i class="fa-solid fa-check"></i> Guardar evento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const apiEventos = @json($apiEventos);
            const apiCatalogoEncargados = @json($apiCatalogoEncargados);

            const buscador = document.getElementById('buscadorEventosSocio');
            const tbodyEventos = document.getElementById('tbodyEventosSocio');
            const formEvento = document.getElementById('formEventoSocio');
            const tbodyEncargados = document.getElementById('tbodyEncargadosEvento');
            const btnAgregarEncargado = document.getElementById('btnAgregarEncargadoEvento');
            const modalEventoEl = document.getElementById('modalEventoSocio');
            const calendarGridEventos = document.getElementById('calendarGridEventos');
            const tituloMesEventos = document.getElementById('tituloMesEventos');
            const btnMesAnteriorEventos = document.getElementById('btnMesAnteriorEventos');
            const btnMesSiguienteEventos = document.getElementById('btnMesSiguienteEventos');
            const tbodyEventosDiaSeleccionado = document.getElementById('tbodyEventosDiaSeleccionado');
            const lblFechaSeleccionadaEventos = document.getElementById('lblFechaSeleccionadaEventos');

            let catalogoSocios = [];
            let eventosCache = [];
            let fechaPreseleccionada = '';
            let fechaSeleccionadaCalendario = '';
            let calendarioMesActual = new Date();

            const parseJsonResponse = async function (r) {
                const text = await r.text();
                if (!text) return {};
                try { return JSON.parse(text); } catch (e) { return { _parseError: true, _raw: text }; }
            };
            const getCsrfToken = function () {
                const m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            };
            const esc = function (txt) {
                if (txt == null) return '';
                return String(txt).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            };
            const money = function (n) {
                return '$' + (Number(n || 0)).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };
            const toDateKey = function (d) {
                const y = d.getFullYear();
                const m = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return y + '-' + m + '-' + day;
            };
            const nombreSocio = function (s) {
                return [s.numero_socio || '', s.numero_dependiente ? ('-' + s.numero_dependiente) : '', ' ', s.nombre || '', ' ', s.ap_paterno || '', ' ', s.ap_materno || '']
                    .join('').replace(/\s+/g, ' ').trim();
            };
            const abrirModalNuevoEvento = function (fecha) {
                fechaPreseleccionada = fecha || '';
                const modal = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getOrCreateInstance(modalEventoEl) : null;
                if (modal) modal.show();
            };
            const renderEventosDelDiaSeleccionado = function () {
                if (!tbodyEventosDiaSeleccionado || !lblFechaSeleccionadaEventos) return;
                if (!fechaSeleccionadaCalendario) {
                    lblFechaSeleccionadaEventos.textContent = 'Sin selección';
                    tbodyEventosDiaSeleccionado.innerHTML = '<tr><td class="text-muted" colspan="8">Selecciona un día para ver eventos.</td></tr>';
                    return;
                }
                lblFechaSeleccionadaEventos.textContent = fechaSeleccionadaCalendario;
                const rows = eventosCache.filter(function (ev) {
                    return String(ev.fecha_evento || '').slice(0, 10) === fechaSeleccionadaCalendario;
                });
                tbodyEventosDiaSeleccionado.innerHTML = '';
                if (!rows.length) {
                    tbodyEventosDiaSeleccionado.innerHTML = '<tr><td class="text-muted" colspan="8">No hay eventos en este día.</td></tr>';
                    return;
                }
                rows.forEach(function (ev) {
                    const encargadosCount = Array.isArray(ev.encargados) ? ev.encargados.length : 0;
                    const horario = ((ev.hora_inicio || '') && (ev.hora_fin || '')) ? (String(ev.hora_inicio).slice(0, 5) + ' - ' + String(ev.hora_fin).slice(0, 5)) : '—';
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + esc(ev.nombre_evento || '—') + '</td>' +
                        '<td>' + esc(ev.tipo_evento || '—') + '</td>' +
                        '<td>' + esc(ev.lugar_evento || '—') + '</td>' +
                        '<td>' + esc(horario) + '</td>' +
                        '<td>' + esc(money(ev.monto_estimado || 0)) + '</td>' +
                        '<td>' + esc(money(ev.monto_comprometido || 0)) + '</td>' +
                        '<td>' + esc(encargadosCount) + '</td>' +
                        '<td><a class="btn btn-sm btn-baseColor-light" href="/Gestion_alumnos/socios/eventos/' + encodeURIComponent(String(ev.id)) + '/detalle">Detalle</a></td>';
                    tbodyEventosDiaSeleccionado.appendChild(tr);
                });
            };
            const renderCalendario = function () {
                if (!calendarGridEventos || !tituloMesEventos) return;

                const anio = calendarioMesActual.getFullYear();
                const mes = calendarioMesActual.getMonth();
                tituloMesEventos.textContent = calendarioMesActual.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });

                const primerDia = new Date(anio, mes, 1);
                const inicioSemana = (primerDia.getDay() + 6) % 7; // lunes=0
                const inicioGrid = new Date(anio, mes, 1 - inicioSemana);

                const conteoPorFecha = {};
                eventosCache.forEach(function (ev) {
                    const f = String(ev.fecha_evento || '').slice(0, 10);
                    if (!f) return;
                    conteoPorFecha[f] = (conteoPorFecha[f] || 0) + 1;
                });

                calendarGridEventos.innerHTML = '';
                for (let i = 0; i < 42; i += 1) {
                    const dia = new Date(inicioGrid);
                    dia.setDate(inicioGrid.getDate() + i);
                    const key = toDateKey(dia);
                    const esMesActual = dia.getMonth() === mes;
                    const total = conteoPorFecha[key] || 0;

                    const cell = document.createElement('div');
                    cell.className = 'calendar-day-eventos' + (esMesActual ? '' : ' is-out');
                    cell.innerHTML =
                        '<div class="d-flex justify-content-between align-items-start">' +
                        '  <span class="num">' + dia.getDate() + '</span>' +
                        (total ? ('<span class="badge rounded-pill text-bg-primary badge-count">' + total + '</span>') : '') +
                        '</div>' +
                        (total ? ('<small class="text-muted d-block mt-2">' + total + ' evento(s)</small>') : '<small class="text-muted d-block mt-2">Sin eventos</small>');

                    if (esMesActual) {
                        cell.addEventListener('click', function () {
                            fechaSeleccionadaCalendario = key;
                            renderEventosDelDiaSeleccionado();
                            abrirModalNuevoEvento(key);
                        });
                    }
                    calendarGridEventos.appendChild(cell);
                }
            };

            const rowEncargado = function () {
                const tr = document.createElement('tr');
                const options = ['<option value="">Selecciona socio...</option>']
                    .concat(catalogoSocios.map(function (s) {
                        return '<option value="' + esc(s.id) + '">' + esc(nombreSocio(s)) + '</option>';
                    }));
                tr.innerHTML =
                    '<td><select class="form-select encargado-socio" required>' + options.join('') + '</select></td>' +
                    '<td><input type="text" class="form-control text encargado-rol" maxlength="120" placeholder="Ej. Coordinador"></td>' +
                    '<td><input type="number" class="form-control text encargado-monto" min="0" step="0.01" value="0" required></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-encargado"><i class="fa-solid fa-trash"></i></button></td>';
                return tr;
            };

            const cargarCatalogoSocios = async function () {
                const r = await fetch(apiCatalogoEncargados, { headers: { 'Accept': 'application/json' } });
                const j = await parseJsonResponse(r);
                catalogoSocios = (r.ok && j && Array.isArray(j.data)) ? j.data : [];
            };

            const coincideBusquedaEvento = function (ev, q) {
                if (!q) return true;
                const text = [
                    ev.nombre_evento || '',
                    ev.tipo_evento || '',
                    ev.descripcion || '',
                    ev.fecha_evento || ''
                ].join(' ').toLowerCase();
                return text.includes(q);
            };

            const renderTablaEventos = function (data) {
                tbodyEventos.innerHTML = '';
                if (!data.length) {
                    tbodyEventos.innerHTML = '<tr><td class="text-muted" colspan="9">Sin eventos registrados.</td></tr>';
                    return;
                }
                data.forEach(function (ev) {
                    const encargados = Array.isArray(ev.encargados) ? ev.encargados : [];
                    const detalle = encargados.map(function (e) {
                        const s = e.socio || {};
                        const name = [s.nombre || '', s.ap_paterno || '', s.ap_materno || ''].join(' ').replace(/\s+/g, ' ').trim();
                        return name + ' (' + money(e.monto_aportacion || 0) + ')';
                    }).join(', ');
                    const tr = document.createElement('tr');
                    const horario = ((ev.hora_inicio || '') && (ev.hora_fin || '')) ? (String(ev.hora_inicio).slice(0, 5) + ' - ' + String(ev.hora_fin).slice(0, 5)) : '—';
                    tr.innerHTML =
                        '<td>' + esc(ev.nombre_evento || '—') + '</td>' +
                        '<td>' + esc(ev.fecha_evento || '—') + '</td>' +
                        '<td>' + esc(ev.tipo_evento || '—') + '</td>' +
                        '<td>' + esc(ev.lugar_evento || '—') + '</td>' +
                        '<td>' + esc(horario) + '</td>' +
                        '<td>' + esc(money(ev.monto_estimado || 0)) + '</td>' +
                        '<td>' + esc(money(ev.monto_comprometido || 0)) + '</td>' +
                        '<td>' + esc(encargados.length) + '</td>' +
                    '<td><small>' + esc(detalle || 'Sin detalle') + '</small><br><a class="btn btn-sm btn-baseColor-light mt-1" href="/Gestion_alumnos/socios/eventos/' + encodeURIComponent(String(ev.id)) + '/detalle">Detalle</a></td>';
                    tbodyEventos.appendChild(tr);
                });
            };

            const cargarEventos = async function () {
                try {
                    const r = await fetch(apiEventos, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    const fullData = (r.ok && j && Array.isArray(j.data)) ? j.data : [];
                    eventosCache = fullData;
                    renderCalendario();
                    renderEventosDelDiaSeleccionado();
                    const q = buscador ? String(buscador.value || '').trim().toLowerCase() : '';
                    const filtrados = fullData.filter(function (ev) { return coincideBusquedaEvento(ev, q); });
                    renderTablaEventos(filtrados);
                } catch (err) {
                    console.error(err);
                    tbodyEventos.innerHTML = '<tr><td class="text-muted" colspan="7">No se pudo cargar eventos.</td></tr>';
                }
            };

            const resetFormEvento = function (fecha) {
                if (formEvento) formEvento.reset();
                if (tbodyEncargados) {
                    tbodyEncargados.innerHTML = '';
                    tbodyEncargados.appendChild(rowEncargado());
                }
                if (formEvento && fecha) {
                    formEvento.elements['fecha_evento'].value = fecha;
                }
            };

            if (btnAgregarEncargado) {
                btnAgregarEncargado.addEventListener('click', function () {
                    if (!tbodyEncargados) return;
                    tbodyEncargados.appendChild(rowEncargado());
                });
            }
            if (tbodyEncargados) {
                tbodyEncargados.addEventListener('click', function (event) {
                    const btn = event.target.closest('.btn-remove-encargado');
                    if (!btn) return;
                    const rows = tbodyEncargados.querySelectorAll('tr');
                    if (rows.length <= 1) return;
                    const tr = btn.closest('tr');
                    if (tr) tr.remove();
                });
            }
            if (buscador) {
                buscador.addEventListener('input', function () { void cargarEventos(); });
            }
            if (modalEventoEl) {
                modalEventoEl.addEventListener('show.bs.modal', function () {
                    resetFormEvento(fechaPreseleccionada);
                });
                modalEventoEl.addEventListener('hidden.bs.modal', function () {
                    fechaPreseleccionada = '';
                });
            }
            if (btnMesAnteriorEventos) {
                btnMesAnteriorEventos.addEventListener('click', function () {
                    calendarioMesActual = new Date(calendarioMesActual.getFullYear(), calendarioMesActual.getMonth() - 1, 1);
                    renderCalendario();
                });
            }
            if (btnMesSiguienteEventos) {
                btnMesSiguienteEventos.addEventListener('click', function () {
                    calendarioMesActual = new Date(calendarioMesActual.getFullYear(), calendarioMesActual.getMonth() + 1, 1);
                    renderCalendario();
                });
            }

            if (formEvento) {
                formEvento.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    formEvento.classList.add('was-validated');
                    if (!formEvento.checkValidity()) return;

                    const rows = Array.from(tbodyEncargados ? tbodyEncargados.querySelectorAll('tr') : []);
                    const encargados = rows.map(function (tr) {
                        const socio = tr.querySelector('.encargado-socio');
                        const rol = tr.querySelector('.encargado-rol');
                        const monto = tr.querySelector('.encargado-monto');
                        return {
                            id_socio: Number(socio ? socio.value : 0),
                            rol_encargado: rol ? String(rol.value || '').trim() : '',
                            monto_aportacion: Number(monto ? monto.value : 0),
                        };
                    }).filter(function (x) { return x.id_socio > 0; });

                    if (!encargados.length) {
                        window.alert('Agrega al menos un encargado válido.');
                        return;
                    }

                    try {
                        const payload = {
                            nombre_evento: String(formEvento.elements['nombre_evento'].value || '').trim(),
                            descripcion: String(formEvento.elements['descripcion'].value || '').trim(),
                            fecha_evento: String(formEvento.elements['fecha_evento'].value || '').trim(),
                            tipo_evento: String(formEvento.elements['tipo_evento'].value || '').trim(),
                            lugar_evento: String(formEvento.elements['lugar_evento'].value || '').trim(),
                            hora_inicio: String(formEvento.elements['hora_inicio'].value || '').trim(),
                            hora_fin: String(formEvento.elements['hora_fin'].value || '').trim(),
                            monto_estimado: Number(formEvento.elements['monto_estimado'].value || 0),
                            encargados: encargados,
                        };
                        const r = await fetch(apiEventos, {
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
                        if (!r.ok) {
                            window.alert((j && j.message) ? j.message : 'No se pudo guardar el evento.');
                            return;
                        }
                        window.alert((j && j.message) ? j.message : 'Evento guardado.');
                        const instance = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getInstance(modalEventoEl) : null;
                        if (instance) instance.hide();
                        await cargarEventos();
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar evento.');
                    }
                });
            }

            await cargarCatalogoSocios();
            resetFormEvento();
            calendarioMesActual = new Date();
            renderCalendario();
            renderEventosDelDiaSeleccionado();
            await cargarEventos();
        });
    </script>
@endsection
