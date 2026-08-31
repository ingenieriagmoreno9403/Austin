@extends('layouts.app')
@section('content')
    @include('Gestion_alumnos.partials.sweet_alerts')

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <style>
        .convenios-asignar-page {
            --conv-primary: #1e40af;
            --conv-soft: #eff6ff;
            --conv-border: rgba(30, 64, 175, 0.12);
            --conv-muted: #64748b;
        }

        .convenio-card {
            background: #fff;
            border: 1px solid var(--conv-border);
            border-radius: 24px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.07);
        }

        .convenio-card > .card-body {
            padding: 1.5rem;
        }

        .convenio-stat {
            border: 1px solid var(--conv-border);
            border-radius: 18px;
            padding: 1rem;
            background: var(--conv-soft);
            height: 100%;
        }

        .convenio-stat small {
            color: var(--conv-muted);
            display: block;
            margin-bottom: 0.25rem;
        }

        .convenio-stat strong {
            color: #0f172a;
            font-size: 1.05rem;
        }

        .convenio-info-box {
            border: 1px solid rgba(30, 64, 175, 0.14);
            border-radius: 18px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
            padding: 1rem;
        }

        #tablaAlumnosConvenio {
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1080px;
        }

        #tablaAlumnosConvenio thead th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            border-color: #e2e8f0;
        }

        #tablaAlumnosConvenio tbody tr:hover td {
            background-color: #f8fbff;
        }

        .modal-convenio .modal-content {
            border: 0;
            border-radius: 22px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.2);
        }

        .modal-convenio .modal-header {
            border: 0;
            padding: 1.25rem 1.5rem 0.75rem;
        }

        .modal-convenio .modal-body {
            padding: 0 1.5rem 1rem;
        }

        .modal-convenio .modal-footer {
            border: 0;
            padding: 0.75rem 1.5rem 1.5rem;
        }
    </style>

    <div class="container-fluid acciones-config-page convenios-asignar-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-handshake"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Asignación de convenios</h2>
                            <p class="text-muted mb-0">Elige empresa FED aceptada y asigna alumnos que cumplan requisitos.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('gestion_alumnos.convenios') }}" class="btn btn-baseColor-light fs-7 mb-2">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiEmpresasBase = url('/Gestion_alumnos/api/empresas');
            $apiEmpresasFedAceptadasBase = url('/Gestion_alumnos/api/empresas/fed-aceptadas');
            $apiAlumnosBase = url('/Gestion_alumnos/api/alumnos');
            $apiPersonasDocumentosBase = url('/Gestion_alumnos/api/personas-documentos');
            $apiSubirConvenioFirmadoBase = url('/Gestion_alumnos/api/personas-documentos/subir-convenio-firmado');
            $apiTestsAulaMixtaBase = url('/Gestion_alumnos/api/tests-personalidad-aula-mixta');
            $apiConveniosAsignacionesBase = url('/Gestion_alumnos/api/convenios-asignaciones');
            $urlTestAulaMixtaBase = url('/Gestion_alumnos/documentos_alumno/test_personalidad_aula_mixta_simple');
        @endphp

        <div class="row g-3 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="convenio-stat">
                    <small><i class="fa-solid fa-building-circle-check me-1"></i>Empresas FED</small>
                    <strong id="statEmpresasFed">Validando...</strong>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="convenio-stat">
                    <small><i class="fa-solid fa-user-graduate me-1"></i>Alumnos registrados</small>
                    <strong id="statAlumnosConvenio">Validando...</strong>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="convenio-stat">
                    <small><i class="fa-solid fa-list-check me-1"></i>Validación</small>
                    <strong>Cursos, documentos y test</strong>
                </div>
            </div>
        </div>

        <div class="convenio-card mt-2">
            <div class="card-body">
            <h5 class="mb-2 text-secondary"><i class="fa-solid fa-building-circle-check text-primary me-2"></i>Convenio (Empresas FED)</h5>
            <p class="text-muted small mb-3">
                Se muestran empresas con resultado FED <strong>Aceptado</strong>. El botón <strong>Asignar</strong> solo se habilita cuando el alumno cumple <em>al mismo tiempo</em> los requisitos de la lista siguiente (se valida en sistema con cursos, documentos y test).
            </p>

            <div class="row mb-3">
                <div class="col-md-6 col-12">
                    <label class="form-label">Empresa con FED aceptado</label>
                    <select class="form-select" id="selectEmpresaConvenio">
                        <option value="">Selecciona empresa...</option>
                    </select>
                    <p class="text-muted small mt-2 mb-0 d-none" id="msgEmpresasFedVacio">
                        No aparecen empresas porque ninguna tiene una visita de prospección con resultado FED <strong>Aceptado</strong>.
                        Registra o edita visitas en <strong>Calendario de visitas</strong> y en el campo correspondiente elige <strong>Aceptado</strong>.
                    </p>
                </div>
            </div>

            <h6 class="mb-2">Alumnos elegibles para asignar</h6>
            <div class="convenio-info-box small mb-3" role="status">
                <strong>¿En qué nos basamos para dejar activo «Asignar»?</strong> Debe cumplirse <strong>todo</strong> esto (si falta uno, el botón queda deshabilitado; igual verás al alumno en la tabla con en rojo lo que no cumple):
                <ul class="mb-0 mt-2 ps-3">
                    <li><strong>Cursos:</strong> tener al menos un curso asignado y que <strong>todos</strong> sus cursos tengan calificación numérica exactamente <strong>10</strong> (no 9, no 9.5).</li>
                    <li><strong>Convenio de aprendizaje:</strong> documento cargado en el expediente del alumno (tipo/nombre que contenga «convenio de aprendizaje») y estado activo.</li>
                    <li><strong>Convenio de colaboración:</strong> igual, nombre que contenga «convenio de colaboracion».</li>
                    <li><strong>Test aula mixta:</strong> debe existir aplicación del test en el sistema con resultado clasificado como <strong>bajo</strong> o <strong>medio</strong> riesgo socioemocional (si el resultado es <strong>alto</strong>, no se puede asignar). El documento en expediente solo complementa si también hay registro evaluable en sistema.</li>
                </ul>
            </div>
            <div id="wrapProgressConvenioAlumnos" class="mb-3 d-none" aria-live="polite">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted" id="txtProgressConvenioAlumnos">Validando…</small>
                    <small class="text-muted fw-semibold" id="pctProgressConvenioAlumnos">0%</small>
                </div>
                <div class="progress" style="height: 12px;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="progressConvenioAlumnos">
                    <div id="barProgressConvenioAlumnos" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 0%;"></div>
                </div>
            </div>
            <div class="table-responsive rounded-4 border mb-4">
                <table class="table table-sm table-stripped table-hover mb-0" id="tablaAlumnosConvenio">
                    <thead>
                        <tr class="text-tr">
                            <th>Matrícula</th>
                            <th>Alumno</th>
                            <th>Cursos (10)</th>
                            <th>Conv. aprendizaje</th>
                            <th>Conv. colaboración</th>
                            <th>Test aula mixta</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyAlumnosConvenio">
                        <tr>
                            <td class="text-muted" colspan="8">Selecciona una empresa para consultar alumnos.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-convenio" id="modalRegistrarConvenio" tabindex="-1" aria-labelledby="modalRegistrarConvenioTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRegistrarConvenioTitulo">Registrar convenio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Aquí defines los datos que se guardan al dar de alta el convenio entre el alumno y la empresa.</p>
                    <dl class="row small border rounded p-2 bg-light mb-3">
                        <dt class="col-sm-4 mb-1">Empresa</dt>
                        <dd class="col-sm-8 mb-1" id="modalConvenioEmpresaNombre">—</dd>
                        <dt class="col-sm-4 mb-0">Alumno</dt>
                        <dd class="col-sm-8 mb-0" id="modalConvenioAlumnoNombre">—</dd>
                    </dl>
                    <div class="mb-3">
                        <label class="form-label" for="modalConvenioFechaInicio">Fecha de inicio del convenio</label>
                        <input type="date" class="form-control" id="modalConvenioFechaInicio" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="modalConvenioObservaciones">Observaciones <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea class="form-control" id="modalConvenioObservaciones" rows="3" maxlength="2000" placeholder="Por ejemplo: referencia al acuerdo, contacto en empresa, notas internas…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-baseColor" id="btnConfirmarRegistrarConvenio"><i class="fa-solid fa-floppy-disk"></i> Guardar convenio</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiEmpresas = @json($apiEmpresasBase);
            const apiEmpresasFedAceptadas = @json($apiEmpresasFedAceptadasBase);
            const apiAlumnos = @json($apiAlumnosBase);
            const apiPersonasDocumentos = @json($apiPersonasDocumentosBase);
            const apiSubirConvenioFirmado = @json($apiSubirConvenioFirmadoBase);
            const apiTestsAulaMixta = @json($apiTestsAulaMixtaBase);
            const apiConveniosAsignaciones = @json($apiConveniosAsignacionesBase);
            const urlTestAulaMixta = @json($urlTestAulaMixtaBase);
            const selectEmpresaConvenio = document.getElementById('selectEmpresaConvenio');
            const msgEmpresasFedVacio = document.getElementById('msgEmpresasFedVacio');
            const tbodyAlumnosConvenio = document.getElementById('tbodyAlumnosConvenio');
            const wrapProgressConvenioAlumnos = document.getElementById('wrapProgressConvenioAlumnos');
            const progressConvenioAlumnos = document.getElementById('progressConvenioAlumnos');
            const barProgressConvenioAlumnos = document.getElementById('barProgressConvenioAlumnos');
            const txtProgressConvenioAlumnos = document.getElementById('txtProgressConvenioAlumnos');
            const pctProgressConvenioAlumnos = document.getElementById('pctProgressConvenioAlumnos');
            const statEmpresasFed = document.getElementById('statEmpresasFed');
            const statAlumnosConvenio = document.getElementById('statAlumnosConvenio');
            let cacheEmpresas = [];
            let cacheEmpresasFedAceptado = [];
            let cacheAlumnos = [];
            let cacheValidacionAlumnos = {};
            let asignacionesConvenio = [];

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

            const endpointCursosAlumno = function (alumnoId) {
                return apiAlumnos + '/' + encodeURIComponent(String(alumnoId || '').trim()) + '/cursos';
            };

            const normalizarTexto = function (txt) {
                if (txt === null || txt === undefined) {
                    return '';
                }
                return String(txt).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
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

            const modalRegistrarConvenioEl = document.getElementById('modalRegistrarConvenio');
            const btnConfirmarRegistrarConvenio = document.getElementById('btnConfirmarRegistrarConvenio');

            const cargarAsignacionesDesdeApi = async function () {
                try {
                    const r = await fetch(apiConveniosAsignaciones + '?estado=activo', { headers: { 'Accept': 'application/json' } });
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

            const renderEmpresasConvenio = function () {
                if (!selectEmpresaConvenio) {
                    return;
                }
                selectEmpresaConvenio.innerHTML = '<option value="">Selecciona empresa...</option>';
                cacheEmpresasFedAceptado.forEach(function (e) {
                    const o = document.createElement('option');
                    o.value = String(e.id);
                    o.textContent = (e.nombre || 'Empresa') + ' (ID ' + e.id + ')';
                    selectEmpresaConvenio.appendChild(o);
                });
                if (msgEmpresasFedVacio) {
                    msgEmpresasFedVacio.classList.toggle('d-none', cacheEmpresasFedAceptado.length > 0);
                }
                if (statEmpresasFed) {
                    statEmpresasFed.textContent = cacheEmpresasFedAceptado.length + (cacheEmpresasFedAceptado.length === 1 ? ' empresa' : ' empresas');
                }
            };

            const pintarBadgeEstado = function (ok) {
                return ok ? '<span class="badge bg-success">Cumple</span>' : '<span class="badge bg-danger">No cumple</span>';
            };

            const validarCursosDiez = function (cursos) {
                if (!Array.isArray(cursos) || cursos.length === 0) {
                    return false;
                }
                return cursos.every(function (c) {
                    const raw = (c && c.pivot && c.pivot.calificacion !== undefined) ? c.pivot.calificacion : null;
                    const n = raw !== null ? parseFloat(String(raw).replace(',', '.')) : NaN;
                    return Number.isFinite(n) && n === 10;
                });
            };

            /** Devuelve 'bajo' | 'medio' | 'alto' | null según interpretación o puntaje (0–26 / 27–53 / 54–80). */
            const nivelRiesgoDesdeDatosTest = function (data) {
                if (!data || typeof data !== 'object') {
                    return null;
                }
                const interp = String(data.interpretacion || '').trim();
                if (/riesgo\s+alto/i.test(interp)) {
                    return 'alto';
                }
                if (/riesgo\s+medio/i.test(interp)) {
                    return 'medio';
                }
                if (/bajo\s+riesgo/i.test(interp)) {
                    return 'bajo';
                }
                const p = data.puntaje_total;
                if (p !== null && p !== undefined && String(p).trim() !== '') {
                    const n = parseFloat(String(p).replace(',', '.'));
                    if (Number.isFinite(n)) {
                        if (n >= 54) {
                            return 'alto';
                        }
                        if (n >= 27) {
                            return 'medio';
                        }
                        return 'bajo';
                    }
                }
                return null;
            };

            const detalleCursos = function (cursos) {
                if (!Array.isArray(cursos) || !cursos.length) {
                    return 'Sin cursos asignados';
                }
                return cursos.map(function (c) {
                    const nombre = c && c.nombre ? String(c.nombre) : 'Curso';
                    const cal = (c && c.pivot && c.pivot.calificacion !== undefined && c.pivot.calificacion !== null && String(c.pivot.calificacion).trim() !== '')
                        ? String(c.pivot.calificacion)
                        : 'Sin calificación';
                    return nombre + ': ' + cal;
                }).join(' | ');
            };

            const validarDocumentosConvenio = function (documentos) {
                const docs = Array.isArray(documentos) ? documentos : [];
                const obtenerDocumento = function (nombreBuscado) {
                    return docs.find(function (d) {
                        const estado = normalizarTexto(d.estado_documento || '');
                        const activo = (estado === '' || estado === 'a');
                        if (!activo) {
                            return null;
                        }
                        const label = normalizarTexto(d.doc_label || '');
                        return label.includes(nombreBuscado);
                    });
                };
                const docAprendizaje = obtenerDocumento('convenio de aprendizaje');
                const docColaboracion = obtenerDocumento('convenio de colaboracion');
                return {
                    aprendizaje: !!docAprendizaje,
                    colaboracion: !!docColaboracion,
                    testAulaMixta: !!obtenerDocumento('test de personalidad aula mixta'),
                    docAprendizaje: docAprendizaje || null,
                    docColaboracion: docColaboracion || null,
                };
            };

            const cargarAlumnos = async function () {
                try {
                    const r = await fetch(apiAlumnos, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudieron cargar alumnos.');
                        cacheAlumnos = [];
                        if (statAlumnosConvenio) {
                            statAlumnosConvenio.textContent = '0 alumnos';
                        }
                        return;
                    }
                    cacheAlumnos = Array.isArray(j.data) ? j.data : [];
                    if (statAlumnosConvenio) {
                        statAlumnosConvenio.textContent = cacheAlumnos.length + (cacheAlumnos.length === 1 ? ' alumno' : ' alumnos');
                    }
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar alumnos.');
                    cacheAlumnos = [];
                    if (statAlumnosConvenio) {
                        statAlumnosConvenio.textContent = '0 alumnos';
                    }
                }
            };

            const validarAlumnoConvenio = async function (alumno) {
                const alumnoId = alumno && alumno.id ? String(alumno.id) : '';
                if (!alumnoId) {
                    return null;
                }
                if (cacheValidacionAlumnos[alumnoId] && cacheValidacionAlumnos[alumnoId]._validacionVersion === 2) {
                    return cacheValidacionAlumnos[alumnoId];
                }

                let cursos = [];
                let documentos = [];
                let testDataApi = null;
                try {
                    const rCursos = await fetch(endpointCursosAlumno(alumnoId), { headers: { 'Accept': 'application/json' } });
                    const jCursos = await parseJsonResponse(rCursos);
                    cursos = rCursos.ok && Array.isArray(jCursos.data) ? jCursos.data : [];
                } catch (err) {
                    console.error(err);
                }

                try {
                    const rDocs = await fetch(apiPersonasDocumentos + '?id_personas=' + encodeURIComponent(alumnoId), { headers: { 'Accept': 'application/json' } });
                    const jDocs = await parseJsonResponse(rDocs);
                    documentos = rDocs.ok && Array.isArray(jDocs.data) ? jDocs.data : [];
                } catch (err) {
                    console.error(err);
                }

                try {
                    const rTest = await fetch(apiTestsAulaMixta + '/alumno/' + encodeURIComponent(alumnoId), { headers: { 'Accept': 'application/json' } });
                    const jTest = await parseJsonResponse(rTest);
                    if (rTest.ok && jTest.data !== null && jTest.data !== undefined) {
                        testDataApi = jTest.data;
                    }
                } catch (err) {
                    console.error(err);
                }

                const cursosOk = validarCursosDiez(cursos);
                const docsOk = validarDocumentosConvenio(documentos);
                const tieneTestEnSistema = !!testDataApi;
                const testRegistradoOk = docsOk.testAulaMixta || tieneTestEnSistema;
                const nivelRiesgo = nivelRiesgoDesdeDatosTest(testDataApi);
                const testNivelOk = nivelRiesgo === 'bajo' || nivelRiesgo === 'medio';
                const testAulaMixtaOk = testRegistradoOk && testNivelOk;
                const interpretacionTest = testDataApi && testDataApi.interpretacion != null ? String(testDataApi.interpretacion).trim() : '';
                const puntajeTest = testDataApi && testDataApi.puntaje_total !== undefined && testDataApi.puntaje_total !== null
                    ? testDataApi.puntaje_total
                    : null;

                const motivosNoCumple = [];
                if (!cursosOk) {
                    motivosNoCumple.push('Cursos: ' + detalleCursos(cursos));
                }
                if (!docsOk.aprendizaje) {
                    motivosNoCumple.push('Falta convenio de aprendizaje activo');
                }
                if (!docsOk.colaboracion) {
                    motivosNoCumple.push('Falta convenio de colaboración activo');
                }
                if (!testRegistradoOk) {
                    motivosNoCumple.push('Falta test aula mixta (aplicación en sistema o documento en expediente)');
                } else if (!testNivelOk) {
                    if (nivelRiesgo === 'alto') {
                        motivosNoCumple.push('Test: riesgo alto (solo bajo o medio permiten asignar)');
                    } else if (!tieneTestEnSistema && docsOk.testAulaMixta) {
                        motivosNoCumple.push('Test: hace falta aplicación en sistema con resultado bajo o medio');
                    } else {
                        motivosNoCumple.push('Test: resultado no clasificado como bajo o medio');
                    }
                }

                const out = {
                    cursosOk: cursosOk,
                    cursosDetalle: detalleCursos(cursos),
                    aprendizajeOk: docsOk.aprendizaje,
                    colaboracionOk: docsOk.colaboracion,
                    testAulaMixtaOk: testAulaMixtaOk,
                    testRegistradoOk: testRegistradoOk,
                    testNivelOk: testNivelOk,
                    interpretacionTest: interpretacionTest,
                    puntajeTest: puntajeTest,
                    nivelRiesgo: nivelRiesgo,
                    tieneTestEnSistema: tieneTestEnSistema,
                    motivosNoCumple: motivosNoCumple.join(' · '),
                    docAprendizaje: docsOk.docAprendizaje,
                    docColaboracion: docsOk.docColaboracion,
                    cumple: cursosOk && docsOk.aprendizaje && docsOk.colaboracion && testAulaMixtaOk,
                    _validacionVersion: 2,
                };
                cacheValidacionAlumnos[alumnoId] = out;
                return out;
            };

            const setProgressConvenioAlumnos = function (visible, texto, pct) {
                if (!wrapProgressConvenioAlumnos || !barProgressConvenioAlumnos) {
                    return;
                }
                wrapProgressConvenioAlumnos.classList.toggle('d-none', !visible);
                if (!visible) {
                    barProgressConvenioAlumnos.style.width = '0%';
                    if (progressConvenioAlumnos) {
                        progressConvenioAlumnos.setAttribute('aria-valuenow', '0');
                    }
                    if (pctProgressConvenioAlumnos) {
                        pctProgressConvenioAlumnos.textContent = '0%';
                    }
                    if (txtProgressConvenioAlumnos != null) {
                        txtProgressConvenioAlumnos.textContent = 'Validando…';
                    }
                    return;
                }
                if (txtProgressConvenioAlumnos != null && texto != null) {
                    txtProgressConvenioAlumnos.textContent = texto;
                }
                if (pct != null && !Number.isNaN(pct)) {
                    const p = Math.min(100, Math.max(0, pct));
                    barProgressConvenioAlumnos.style.width = p + '%';
                    if (progressConvenioAlumnos) {
                        progressConvenioAlumnos.setAttribute('aria-valuenow', String(Math.round(p)));
                    }
                    if (pctProgressConvenioAlumnos) {
                        pctProgressConvenioAlumnos.textContent = Math.round(p) + '%';
                    }
                }
            };

            const renderAlumnosConvenio = async function () {
                if (!tbodyAlumnosConvenio || !selectEmpresaConvenio) {
                    return;
                }
                const empresaId = String(selectEmpresaConvenio.value || '').trim();
                tbodyAlumnosConvenio.innerHTML = '';
                setProgressConvenioAlumnos(false, '', 0);
                if (!empresaId) {
                    tbodyAlumnosConvenio.innerHTML = '<tr><td class="text-muted" colspan="8">Selecciona una empresa para consultar alumnos.</td></tr>';
                    return;
                }
                if (!cacheAlumnos.length) {
                    tbodyAlumnosConvenio.innerHTML = '<tr><td class="text-muted" colspan="8">No hay alumnos registrados.</td></tr>';
                    return;
                }

                const totalAl = cacheAlumnos.length;
                setProgressConvenioAlumnos(true, 'Validando requisitos de convenio (no cierres esta pestaña)…', 0);
                tbodyAlumnosConvenio.innerHTML = '<tr class="tr-convenio-espera-primer-lote"><td colspan="8" class="text-muted">Validando primer grupo de alumnos…</td></tr>';
                /** Muchos alumnos en paralelo saturan el navegador/servidor (115×3 peticiones). Se procesa por lotes. */
                const TAM_LOTE = 6;
                const columnaConvenioHtml = function (tipo, ok, alumnoId, doc) {
                    const dataKey = String(alumnoId) + '-' + tipo;
                    const bloqueSubida =
                        '<div class="mt-1 d-flex gap-1 align-items-center">' +
                        '  <input type="file" class="form-control form-control-sm input-subir-convenio" data-key="' + escapeHtml(dataKey) + '" accept=".pdf,.jpg,.jpeg,.png">' +
                        '  <button type="button" class="btn btn-sm btn-outline-primary btn-subir-convenio" data-tipo="' + escapeHtml(tipo) + '" data-alumno-id="' + escapeHtml(alumnoId) + '" data-key="' + escapeHtml(dataKey) + '">Subir</button>' +
                        '</div>';
                    const link = (doc && doc.url_descargar)
                        ? '<div class="small mt-1"><a href="' + escapeHtml(doc.url_descargar) + '" target="_blank" rel="noopener">Descargar firmado</a></div>'
                        : '';
                    return pintarBadgeEstado(ok) + link + bloqueSubida;
                };
                const filaHtmlAlumno = async function (a) {
                    let validacion = null;
                    try {
                        validacion = await validarAlumnoConvenio(a);
                    } catch (err) {
                        console.error(err);
                        validacion = {
                            cursosOk: false,
                            aprendizajeOk: false,
                            colaboracionOk: false,
                            testAulaMixtaOk: false,
                            testRegistradoOk: false,
                            testNivelOk: false,
                            interpretacionTest: '',
                            puntajeTest: null,
                            nivelRiesgo: null,
                            tieneTestEnSistema: false,
                            motivosNoCumple: 'Error al validar (revisa consola o red).',
                            cumple: false,
                            cursosDetalle: 'Error al validar (revisa consola o red).',
                            docAprendizaje: null,
                            docColaboracion: null,
                        };
                    }
                    const asignacionActiva = asignacionesConvenio.find(function (x) {
                        return String(x.alumno_id) === String(a.id) && String(x.estado || 'activo') === 'activo';
                    });
                    const yaAsignadoAqui = asignacionActiva && String(asignacionActiva.empresa_id) === empresaId;
                    const otraEmpresaBloquea = asignacionActiva && String(asignacionActiva.empresa_id) !== empresaId;
                    let textoBotonAsignar = 'Asignar';
                    if (yaAsignadoAqui) {
                        textoBotonAsignar = 'Asignado';
                    } else if (otraEmpresaBloquea) {
                        textoBotonAsignar = 'Otra empresa';
                    }
                    const puedeAsignar = validacion && validacion.cumple && !yaAsignadoAqui && !otraEmpresaBloquea;
                    const nombre = [a.nombres || '', a.apellido_paterno || '', a.apellido_materno || ''].join(' ').replace(/\s+/g, ' ').trim();
                    const v = validacion;
                    let htmlResultadoTest = '';
                    if (v && v.interpretacionTest) {
                        htmlResultadoTest += '<div class="small fw-medium">' + escapeHtml(v.interpretacionTest) + '</div>';
                    }
                    if (v && v.puntajeTest !== null && v.puntajeTest !== undefined && String(v.puntajeTest).trim() !== '') {
                        htmlResultadoTest += '<div class="small text-muted">Puntaje: ' + escapeHtml(String(v.puntajeTest)) + '</div>';
                    }
                    if (v && !v.interpretacionTest && (v.puntajeTest === null || v.puntajeTest === undefined || String(v.puntajeTest).trim() === '')) {
                        if (v.testRegistradoOk && !v.tieneTestEnSistema) {
                            htmlResultadoTest += '<div class="small text-warning">Sin aplicación en sistema (solo documento)</div>';
                        } else if (!v.testRegistradoOk) {
                            htmlResultadoTest += '<div class="small text-muted">Sin test registrado</div>';
                        }
                    }
                    let badgeNivel = '';
                    const nv = v && v.nivelRiesgo;
                    if (nv === 'alto') {
                        badgeNivel = '<span class="badge bg-danger mt-1">Riesgo alto · no asignable</span>';
                    } else if (nv === 'medio') {
                        badgeNivel = '<span class="badge bg-warning text-dark mt-1">Riesgo medio · asignable</span>';
                    } else if (nv === 'bajo') {
                        badgeNivel = '<span class="badge bg-success mt-1">Bajo riesgo · asignable</span>';
                    } else if (v && v.testRegistradoOk) {
                        badgeNivel = '<span class="badge bg-secondary mt-1">Sin clasificar</span>';
                    }
                    const celdaTest = pintarBadgeEstado(v && v.testAulaMixtaOk) +
                        htmlResultadoTest +
                        (badgeNivel ? '<div>' + badgeNivel + '</div>' : '') +
                        '<div class="mt-1 d-flex flex-wrap gap-1 align-items-center">' +
                        '<a href="' + escapeHtml(urlTestAulaMixta) + '?alumno_id=' + encodeURIComponent(a.id) + '&ver_resultados=1" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" title="Ver último test guardado"><i class="fa-solid fa-chart-simple"></i> Resultados</a>' +
                        '<a href="' + escapeHtml(urlTestAulaMixta) + '?alumno_id=' + encodeURIComponent(a.id) + '" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" title="Abrir formulario del test"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ir al test</a>' +
                        '</div>';
                    const textoNoCumple = (v && v.motivosNoCumple) ? v.motivosNoCumple : (v ? v.cursosDetalle : '');
                    return '<tr>' +
                        '<td>' + escapeHtml(a.numero_matricula || '—') + '</td>' +
                        '<td>' + escapeHtml(nombre || '—') + '</td>' +
                        '<td>' + pintarBadgeEstado(validacion && validacion.cursosOk) + '</td>' +
                        '<td>' + columnaConvenioHtml('aprendizaje', (validacion && validacion.aprendizajeOk), a.id, validacion ? validacion.docAprendizaje : null) + '</td>' +
                        '<td>' + columnaConvenioHtml('colaboracion', (validacion && validacion.colaboracionOk), a.id, validacion ? validacion.docColaboracion : null) + '</td>' +
                        '<td>' + celdaTest +
                        '</td>' +
                        '<td>' +
                        pintarBadgeEstado(validacion && validacion.cumple) +
                        ((validacion && !validacion.cumple)
                            ? '<div class="small text-muted mt-1" title="' + escapeHtml(textoNoCumple || '') + '">' + escapeHtml(textoNoCumple || '') + '</div>'
                            : '') +
                        '</td>' +
                        '<td><button type="button" class="btn btn-sm btn-baseColor btn-asignar-convenio" data-empresa-id="' + escapeHtml(empresaId) + '" data-alumno-id="' + escapeHtml(a.id) + '" ' +
                        (puedeAsignar ? '' : 'disabled ') +
                        'title="' + (otraEmpresaBloquea ? escapeHtml('Este alumno ya tiene un convenio activo con otra empresa.') : '') + '"' +
                        '>' + escapeHtml(textoBotonAsignar) + '</button></td>' +
                        '</tr>';
                };
                for (let i = 0; i < totalAl; i += TAM_LOTE) {
                    const slice = cacheAlumnos.slice(i, i + TAM_LOTE);
                    const desde = i + 1;
                    const hasta = Math.min(i + TAM_LOTE, totalAl);
                    const pctDurante = totalAl ? (i / totalAl) * 100 : 0;
                    setProgressConvenioAlumnos(true, 'Validando alumnos ' + desde + '–' + hasta + ' de ' + totalAl + '…', pctDurante);
                    const filasLote = await Promise.all(slice.map(function (a) {
                        return filaHtmlAlumno(a);
                    }));
                    const rowEspera = tbodyAlumnosConvenio.querySelector('.tr-convenio-espera-primer-lote');
                    if (rowEspera) {
                        rowEspera.remove();
                    }
                    tbodyAlumnosConvenio.insertAdjacentHTML('beforeend', filasLote.join(''));
                    const pctHecho = totalAl ? (hasta / totalAl) * 100 : 100;
                    setProgressConvenioAlumnos(true, 'Cargados ' + hasta + ' de ' + totalAl + ' alumnos…', pctHecho);
                    await new Promise(function (resolve) {
                        window.requestAnimationFrame(resolve);
                    });
                }
                setProgressConvenioAlumnos(false, '', 100);
            };

            const cargarEmpresas = async function () {
                try {
                    const urlFed = apiEmpresasFedAceptadas;
                    const r = await fetch(urlFed, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        window.alert((j && j.message) ? j.message : 'No se pudieron cargar empresas con FED aceptado.');
                        cacheEmpresas = [];
                        cacheEmpresasFedAceptado = [];
                        renderEmpresasConvenio();
                        return;
                    }
                    cacheEmpresasFedAceptado = Array.isArray(j.data) ? j.data : [];
                    cacheEmpresas = cacheEmpresasFedAceptado.slice();
                    renderEmpresasConvenio();
                } catch (err) {
                    console.error(err);
                    window.alert('Error de red al cargar empresas.');
                    cacheEmpresas = [];
                    cacheEmpresasFedAceptado = [];
                    renderEmpresasConvenio();
                }
            };

            if (selectEmpresaConvenio) {
                selectEmpresaConvenio.addEventListener('change', function () {
                    void renderAlumnosConvenio();
                });
            }

            if (tbodyAlumnosConvenio) {
                tbodyAlumnosConvenio.addEventListener('click', function (event) {
                    const btnSubir = event.target.closest('.btn-subir-convenio');
                    if (btnSubir) {
                        const alumnoId = String(btnSubir.getAttribute('data-alumno-id') || '').trim();
                        const tipo = String(btnSubir.getAttribute('data-tipo') || '').trim();
                        const key = String(btnSubir.getAttribute('data-key') || '').trim();
                        const input = tbodyAlumnosConvenio.querySelector('.input-subir-convenio[data-key="' + key + '"]');
                        const file = input && input.files && input.files.length ? input.files[0] : null;
                        if (!alumnoId || !tipo || !file) {
                            window.alert('Selecciona un archivo para subir.');
                            return;
                        }
                        const fd = new FormData();
                        fd.append('alumno_id', alumnoId);
                        fd.append('tipo_convenio', tipo);
                        fd.append('archivo', file);
                        fetch(apiSubirConvenioFirmado, {
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
                                let msg = (j && j.message) ? j.message : 'No se pudo subir el convenio.';
                                if (j && j.errors) {
                                    msg = Object.values(j.errors).flat().join('\n');
                                }
                                window.alert(msg);
                                return;
                            }
                            delete cacheValidacionAlumnos[alumnoId];
                            await renderAlumnosConvenio();
                        }).catch(function (err) {
                            console.error(err);
                            window.alert('Error de red al subir convenio.');
                        });
                        return;
                    }

                    const btn = event.target.closest('.btn-asignar-convenio');
                    if (!btn) {
                        return;
                    }
                    const empresaId = String(btn.getAttribute('data-empresa-id') || '').trim();
                    const alumnoId = String(btn.getAttribute('data-alumno-id') || '').trim();
                    if (!empresaId || !alumnoId) {
                        return;
                    }
                    const empresa = cacheEmpresasFedAceptado.find(function (e) { return String(e.id) === empresaId; });
                    const alumno = cacheAlumnos.find(function (a) { return String(a.id) === alumnoId; });
                    const validacion = cacheValidacionAlumnos[alumnoId];
                    if (!empresa || !alumno || !validacion || !validacion.cumple) {
                        window.alert('El alumno no cumple requisitos para convenio.');
                        return;
                    }
                    const nombreAlumnoModal = [alumno.nombres || '', alumno.apellido_paterno || '', alumno.apellido_materno || '']
                        .join(' ')
                        .replace(/\s+/g, ' ')
                        .trim();
                    const elEmp = document.getElementById('modalConvenioEmpresaNombre');
                    const elAl = document.getElementById('modalConvenioAlumnoNombre');
                    const inpFecha = document.getElementById('modalConvenioFechaInicio');
                    const txtObs = document.getElementById('modalConvenioObservaciones');
                    if (elEmp) {
                        elEmp.textContent = empresa.nombre || ('Empresa ' + empresaId);
                    }
                    if (elAl) {
                        elAl.textContent = nombreAlumnoModal || 'Alumno';
                    }
                    if (inpFecha) {
                        inpFecha.value = hoyIsoLocal();
                    }
                    if (txtObs) {
                        txtObs.value = '';
                    }
                    if (modalRegistrarConvenioEl) {
                        modalRegistrarConvenioEl.dataset.empresaId = empresaId;
                        modalRegistrarConvenioEl.dataset.alumnoId = alumnoId;
                    }
                    if (modalRegistrarConvenioEl && window.bootstrap && window.bootstrap.Modal) {
                        window.bootstrap.Modal.getOrCreateInstance(modalRegistrarConvenioEl).show();
                    }
                });
            }

            if (btnConfirmarRegistrarConvenio && modalRegistrarConvenioEl) {
                btnConfirmarRegistrarConvenio.addEventListener('click', function () {
                    const empresaId = String(modalRegistrarConvenioEl.dataset.empresaId || '').trim();
                    const alumnoId = String(modalRegistrarConvenioEl.dataset.alumnoId || '').trim();
                    const inpFecha = document.getElementById('modalConvenioFechaInicio');
                    const txtObs = document.getElementById('modalConvenioObservaciones');
                    const fechaInicio = inpFecha ? String(inpFecha.value || '').trim() : '';
                    const observaciones = txtObs ? String(txtObs.value || '').trim() : '';
                    if (!empresaId || !alumnoId) {
                        window.alert('Datos incompletos. Cierra el cuadro e inténtalo de nuevo.');
                        return;
                    }
                    if (!fechaInicio) {
                        window.alert('Indica la fecha de inicio del convenio.');
                        return;
                    }
                    const payload = {
                        alumno_id: parseInt(alumnoId, 10),
                        empresa_id: parseInt(empresaId, 10),
                        fecha_inicio: fechaInicio,
                    };
                    if (observaciones) {
                        payload.observaciones = observaciones;
                    }
                    fetch(apiConveniosAsignaciones, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    }).then(async function (r) {
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            let msg = (j && j.message) ? j.message : 'No se pudo registrar la asignación.';
                            window.alert(msg);
                            return;
                        }
                        if (modalRegistrarConvenioEl && window.bootstrap && window.bootstrap.Modal) {
                            window.bootstrap.Modal.getInstance(modalRegistrarConvenioEl).hide();
                        }
                        await cargarAsignacionesDesdeApi();
                        void renderAlumnosConvenio();
                    }).catch(function (err) {
                        console.error(err);
                        window.alert('Error de red al asignar.');
                    });
                });
            }

            (async function init() {
                await cargarAlumnos();
                await cargarEmpresas();
                await cargarAsignacionesDesdeApi();
                await renderAlumnosConvenio();
            })();
        });
    </script>
@endsection
