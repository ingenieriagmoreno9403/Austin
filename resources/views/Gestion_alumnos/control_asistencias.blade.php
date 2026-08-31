@extends('layouts.app')
@section('content')
    @include('Gestion_alumnos.partials.sweet_alerts')

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <style>
        .asistencia-page {
            --asistencia-primary: #1e40af;
            --asistencia-soft: #eff6ff;
            --asistencia-border: rgba(30, 64, 175, 0.12);
            --asistencia-muted: #64748b;
        }

        .asistencia-card {
            background: #fff;
            border: 1px solid var(--asistencia-border);
            border-radius: 24px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.07);
        }

        .asistencia-toolbar {
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
            border: 1px solid var(--asistencia-border);
            border-radius: 22px;
            padding: 1.25rem;
        }

        .asistencia-stat {
            border: 1px solid var(--asistencia-border);
            border-radius: 18px;
            padding: 1rem;
            background: var(--asistencia-soft);
            height: 100%;
        }

        .asistencia-stat small {
            color: var(--asistencia-muted);
            display: block;
            margin-bottom: 0.25rem;
        }

        .asistencia-stat strong {
            color: #0f172a;
            font-size: 1.05rem;
        }

        .asistencia-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            justify-content: flex-end;
        }

        .asistencia-legend .badge {
            border-radius: 999px;
            padding: 0.45rem 0.7rem;
            font-weight: 600;
        }

        #tablaAsistencias {
            border-collapse: separate;
            border-spacing: 0;
            min-width: 980px;
        }

        #tablaAsistencias thead th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            border-color: #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        #tablaAsistencias tbody td:first-child,
        #tablaAsistencias thead th:first-child {
            position: sticky;
            left: 0;
            z-index: 3;
            background: #fff;
            box-shadow: 8px 0 14px rgba(15, 23, 42, 0.04);
        }

        #tablaAsistencias tbody tr:hover td {
            background-color: #f8fbff;
        }

        .select-asistencia {
            cursor: pointer;
            border-radius: 0 !important;
        }

        .asistencia-upload {
            border: 1px dashed rgba(30, 64, 175, 0.28);
            border-radius: 18px;
            background: #f8fbff;
        }
    </style>

        @php
            $apiConveniosAsignaciones = url('/Gestion_alumnos/api/convenios-asignaciones');
            $apiAsistencias = url('/Gestion_alumnos/api/asistencias');
            $empresaIdUsuario = $empresaIdUsuario ?? null;
        @endphp

    <div class="container-fluid acciones-config-page asistencia-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-clipboard-user"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Control de Asistencias</h2>
                            <p class="text-muted mb-0" id="subtituloEmpresa">Registra y consulta la asistencia de los alumnos con convenio.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        @if(auth()->user()->tipo != 'empresa')
                        <a href="#" class="btn btn-baseColor fs-7 mb-2" id="btnCalcularPagos">
                            <i class="fa-solid fa-calculator"></i> Calcular pagos
                        </a>
                        @endif
                        
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="javascript:history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="asistencia-stat">
                    <small><i class="fa-solid fa-building me-1"></i>Empresa</small>
                    <strong id="statEmpresa">Pendiente de carga</strong>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="asistencia-stat">
                    <small><i class="fa-solid fa-calendar-days me-1"></i>Periodo</small>
                    <strong id="statPeriodo">Mes actual</strong>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="asistencia-stat">
                    <small><i class="fa-solid fa-user-graduate me-1"></i>Alumnos activos</small>
                    <strong id="statAlumnos">0 alumnos</strong>
                </div>
            </div>
        </div>

        <div class="asistencia-card p-3 p-lg-4 mt-2 mb-2">
            <div class="asistencia-toolbar mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-4 col-6">
                        <label class="form-label small mb-1" for="selectMes">Mes</label>
                        <select class="form-select form-select-sm" id="selectMes"></select>
                    </div>
                    <div class="col-lg-3 col-md-4 col-6">
                        <label class="form-label small mb-1" for="selectAnio">Año</label>
                        <select class="form-select form-select-sm" id="selectAnio"></select>
                    </div>
                    <div class="col-lg-6 col-md-4 col-12 d-flex flex-wrap gap-2 justify-content-lg-end">
                        <button type="button" class="btn btn-baseColor btn-sm" id="btnCargarAsistencia">
                            <i class="fa-solid fa-magnifying-glass"></i> Consultar
                        </button>
                        <button type="button" class="btn btn-baseColor-light btn-sm" id="btnGuardarAsistencia" disabled>
                            <i class="fa-solid fa-floppy-disk"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" id="btnDescargarPlantilla" disabled>
                            <i class="fa-solid fa-file-arrow-down"></i> Plantilla
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnMostrarSubir" disabled>
                            <i class="fa-solid fa-file-arrow-up"></i> Subir
                        </button>
                    </div>
                </div>
            </div>

            <div id="seccionSubirPlantilla" class="asistencia-upload p-3 mb-4 d-none">
                <h6 class="mb-2"><i class="fa-solid fa-file-csv text-primary me-1"></i>Carga masiva de asistencias</h6>
                <p class="small text-muted mb-3">Sube el archivo CSV generado con la plantilla. Debe mantener el mismo formato.</p>
                <div class="d-flex flex-column flex-md-row align-items-md-end gap-2">
                    <div class="flex-grow-1">
                        <input type="file" class="form-control form-control-sm" id="inputPlantillaCsv" accept=".csv">
                    </div>
                    <button type="button" class="btn btn-baseColor btn-sm text-nowrap" id="btnProcesarPlantilla">
                        <i class="fa-solid fa-upload"></i> Procesar y cargar
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" id="btnCerrarSubir">
                        Cancelar
                    </button>
                </div>
                <div class="mt-2 small" id="statusSubirPlantilla"></div>
            </div>

            <div id="alertNoEmpresa" class="alert alert-warning d-none">
                <i class="fa-solid fa-triangle-exclamation"></i> No se recibió el parámetro de empresa. Regresa a la pantalla de convenios y abre el modal de una empresa.
            </div>

            <div id="contenedorTablaAsistencias" class="d-none">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                    <div>
                        <h5 class="text-secondary mb-1">
                            <i class="fa-solid fa-list-check me-2"></i><span id="tituloTablaAsistencia">Asistencia</span>
                        </h5>
                        <p class="text-muted fs-8 mb-0">Captura la asistencia diaria y guarda los cambios del periodo seleccionado.</p>
                    </div>
                    <div class="asistencia-legend">
                        <span class="badge bg-success me-1">A = Asistencia</span>
                        <span class="badge bg-danger me-1">F = Falta</span>
                        <span class="badge bg-warning text-dark me-1">R = Retardo</span>
                        <span class="badge bg-secondary">J = Justificada</span>
                    </div>
                </div>
                <div class="table-responsive rounded-4 border">
                    <table class="table table-sm table-bordered table-hover mb-0 text-center" id="tablaAsistencias">
                        <thead id="theadAsistencias"></thead>
                        <tbody id="tbodyAsistencias"></tbody>
                    </table>
                </div>
                <div class="mt-3 small text-muted" id="resumenAsistencia"></div>
            </div>

            <div id="spinnerCargando" class="text-center py-4 d-none">
                <div class="spinner-border text-orange" role="status"><span class="visually-hidden">Cargando...</span></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiBase = @json($apiConveniosAsignaciones);
            const apiAsistencias = @json($apiAsistencias);
            const empresaIdUsuario = @json($empresaIdUsuario);
            const params = new URLSearchParams(window.location.search);
            const empresaId = params.get('empresa_id') || empresaIdUsuario;

            if (!empresaId) {
                document.getElementById('alertNoEmpresa').classList.remove('d-none');
                return;
            }

            const MESES = [
                'Enero','Febrero','Marzo','Abril','Mayo','Junio',
                'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
            ];
            const ESTADOS_ASISTENCIA = ['A', 'F', 'R', 'J'];
            const ESTADO_CLASES = { A: 'bg-success text-white', F: 'bg-danger text-white', R: 'bg-warning text-dark', J: 'bg-secondary text-white' };

            const selectMes = document.getElementById('selectMes');
            const selectAnio = document.getElementById('selectAnio');
            const btnCargar = document.getElementById('btnCargarAsistencia');
            const btnGuardar = document.getElementById('btnGuardarAsistencia');
            const theadEl = document.getElementById('theadAsistencias');
            const tbodyEl = document.getElementById('tbodyAsistencias');
            const contenedor = document.getElementById('contenedorTablaAsistencias');
            const spinner = document.getElementById('spinnerCargando');
            const subtitulo = document.getElementById('subtituloEmpresa');
            const tituloTabla = document.getElementById('tituloTablaAsistencia');
            const resumenDiv = document.getElementById('resumenAsistencia');
            const statEmpresa = document.getElementById('statEmpresa');
            const statPeriodo = document.getElementById('statPeriodo');
            const statAlumnos = document.getElementById('statAlumnos');

            let alumnosEmpresa = [];
            let asistenciaData = {};
            let diasDelMes = 0;
            let empresaNombre = '';

            const hoy = new Date();
            MESES.forEach(function (m, i) {
                var opt = document.createElement('option');
                opt.value = i + 1;
                opt.textContent = m;
                if (i === hoy.getMonth()) opt.selected = true;
                selectMes.appendChild(opt);
            });
            for (var y = hoy.getFullYear() - 2; y <= 2050; y++) {
                var opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                if (y === hoy.getFullYear()) opt.selected = true;
                selectAnio.appendChild(opt);
            }
            statPeriodo.textContent = MESES[parseInt(selectMes.value) - 1] + ' ' + selectAnio.value;

            function getDiasEnMes(mes, anio) {
                return new Date(anio, mes, 0).getDate();
            }

            function esDiaHabil(anio, mes, dia) {
                var d = new Date(anio, mes - 1, dia);
                var dow = d.getDay();
                return dow !== 0 && dow !== 6;
            }

            function getCsrfToken() {
                var m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            }

            async function cargarAsistenciasDesdeApi(mes, anio) {
                try {
                    var url = apiAsistencias + '?empresa_id=' + encodeURIComponent(empresaId) +
                        '&mes=' + encodeURIComponent(mes) + '&anio=' + encodeURIComponent(anio);
                    var r = await fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    var j = await r.json();
                    return j.data || {};
                } catch (err) {
                    console.error('Error cargando asistencias:', err);
                    return {};
                }
            }

            async function guardarAsistenciasEnApi() {
                var mes = parseInt(selectMes.value);
                var anio = parseInt(selectAnio.value);

                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

                try {
                    var r = await fetch(apiAsistencias, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            empresa_id: parseInt(empresaId),
                            mes: mes,
                            anio: anio,
                            asistencias: asistenciaData,
                        }),
                    });
                    var j = await r.json();
                    if (!r.ok) {
                        var msg = j.message || 'Error al guardar.';
                        if (j.errors) msg = Object.values(j.errors).flat().join('\n');
                        alert('Error: ' + msg);
                        return;
                    }
                    alert('Asistencias guardadas correctamente para ' + MESES[mes - 1] + ' ' + anio +
                        '.\nInsertados: ' + (j.insertados || 0) + ' | Actualizados: ' + (j.actualizados || 0));
                } catch (err) {
                    console.error('Error guardando asistencias:', err);
                    alert('Error de red al guardar las asistencias.');
                } finally {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar';
                }
            }

            async function cargarAlumnosEmpresa() {
                spinner.classList.remove('d-none');
                try {
                    var r = await fetch(apiBase + '?estado=activo', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    var j = await r.json();
                    var todos = j.data || [];
                    alumnosEmpresa = todos.filter(function (a) { return String(a.empresa_id) === String(empresaId); });
                    if (alumnosEmpresa.length > 0) {
                        empresaNombre = alumnosEmpresa[0].empresa_nombre || 'Empresa';
                        subtitulo.textContent = 'Empresa: ' + empresaNombre;
                        statEmpresa.textContent = empresaNombre;
                    } else {
                        empresaNombre = 'Empresa #' + empresaId;
                        statEmpresa.textContent = empresaNombre;
                    }
                    statAlumnos.textContent = alumnosEmpresa.length + (alumnosEmpresa.length === 1 ? ' alumno' : ' alumnos');
                } catch (err) {
                    console.error('Error cargando alumnos:', err);
                } finally {
                    spinner.classList.add('d-none');
                }
            }

            async function renderTabla() {
                var mes = parseInt(selectMes.value);
                var anio = parseInt(selectAnio.value);
                diasDelMes = getDiasEnMes(mes, anio);
                statPeriodo.textContent = MESES[mes - 1] + ' ' + anio;

                tituloTabla.textContent = 'Asistencia — ' + MESES[mes - 1] + ' ' + anio + ' — ' + empresaNombre;

                spinner.classList.remove('d-none');
                asistenciaData = await cargarAsistenciasDesdeApi(mes, anio);
                spinner.classList.add('d-none');

                var DIAS_SEMANA = ['D','L','M','Mi','J','V','S'];
                var trDiaSemana = '<tr><th class="text-start" style="min-width:180px;"></th>';
                var trHead = '<tr><th class="text-start" style="min-width:180px;">Alumno</th>';
                for (var d = 1; d <= diasDelMes; d++) {
                    var habil = esDiaHabil(anio, mes, d);
                    var dow = new Date(anio, mes - 1, d).getDay();
                    var claseDia = habil ? '' : 'bg-light text-muted';
                    trDiaSemana += '<th style="min-width:36px;font-size:0.65rem;" class="' + claseDia + '">' + DIAS_SEMANA[dow] + '</th>';
                    trHead += '<th style="min-width:36px;" class="' + claseDia + '">' + d + '</th>';
                }
                trDiaSemana += '<th></th><th></th><th></th><th></th></tr>';
                trHead += '<th style="min-width:50px;">A</th><th style="min-width:50px;">F</th><th style="min-width:50px;">R</th><th style="min-width:50px;">J</th></tr>';
                theadEl.innerHTML = trDiaSemana + trHead;

                tbodyEl.innerHTML = '';
                if (!alumnosEmpresa.length) {
                    tbodyEl.innerHTML = '<tr><td colspan="' + (diasDelMes + 5) + '" class="text-muted">No hay alumnos con convenio activo en esta empresa.</td></tr>';
                    contenedor.classList.remove('d-none');
                    return;
                }

                alumnosEmpresa.forEach(function (alumno) {
                    var aId = String(alumno.alumno_id);
                    if (!asistenciaData[aId]) asistenciaData[aId] = {};

                    var tr = document.createElement('tr');
                    var celdas = '<td class="text-start text-nowrap small">' + escapeHtml(alumno.alumno_nombre) + '</td>';

                    for (var d = 1; d <= diasDelMes; d++) {
                        var habil = esDiaHabil(anio, mes, d);
                        var val = asistenciaData[aId][d] || '';
                        var claseEstado = val ? (ESTADO_CLASES[val] || '') : '';
                        var claseFondo = !habil && !val ? 'bg-light' : '';

                        celdas += '<td class="p-0 ' + claseFondo + '">';
                        if (habil) {
                            celdas += '<select class="form-select form-select-sm border-0 text-center p-0 select-asistencia ' + claseEstado + '" ' +
                                'data-alumno="' + aId + '" data-dia="' + d + '" style="font-size:0.7rem;height:28px;width:36px;">';
                            celdas += '<option value="">-</option>';
                            ESTADOS_ASISTENCIA.forEach(function (e) {
                                celdas += '<option value="' + e + '"' + (val === e ? ' selected' : '') + '>' + e + '</option>';
                            });
                            celdas += '</select>';
                        } else {
                            celdas += '<span class="text-muted" style="font-size:0.65rem;">—</span>';
                        }
                        celdas += '</td>';
                    }

                    var conteos = contarAsistencias(aId);
                    celdas += '<td class="small fw-bold text-success">' + conteos.A + '</td>';
                    celdas += '<td class="small fw-bold text-danger">' + conteos.F + '</td>';
                    celdas += '<td class="small fw-bold text-warning">' + conteos.R + '</td>';
                    celdas += '<td class="small fw-bold text-secondary">' + conteos.J + '</td>';

                    tr.innerHTML = celdas;
                    tbodyEl.appendChild(tr);
                });

                contenedor.classList.remove('d-none');
                btnGuardar.disabled = false;
                actualizarResumen();
            }

            function contarAsistencias(alumnoId) {
                var data = asistenciaData[alumnoId] || {};
                var c = { A: 0, F: 0, R: 0, J: 0 };
                Object.values(data).forEach(function (v) {
                    if (c.hasOwnProperty(v)) c[v]++;
                });
                return c;
            }

            function actualizarResumen() {
                var totalA = 0, totalF = 0, totalR = 0, totalJ = 0;
                alumnosEmpresa.forEach(function (al) {
                    var c = contarAsistencias(String(al.alumno_id));
                    totalA += c.A; totalF += c.F; totalR += c.R; totalJ += c.J;
                });
                resumenDiv.innerHTML = '<strong>Resumen general:</strong> ' +
                    '<span class="text-success">Asistencias: ' + totalA + '</span> | ' +
                    '<span class="text-danger">Faltas: ' + totalF + '</span> | ' +
                    '<span class="text-warning">Retardos: ' + totalR + '</span> | ' +
                    '<span class="text-secondary">Justificadas: ' + totalJ + '</span>';
            }

            function escapeHtml(txt) {
                var d = document.createElement('div');
                d.appendChild(document.createTextNode(txt || ''));
                return d.innerHTML;
            }

            tbodyEl.addEventListener('change', function (e) {
                var sel = e.target.closest('.select-asistencia');
                if (!sel) return;
                var aId = sel.getAttribute('data-alumno');
                var dia = parseInt(sel.getAttribute('data-dia'));
                var val = sel.value;

                if (!asistenciaData[aId]) asistenciaData[aId] = {};
                if (val) {
                    asistenciaData[aId][dia] = val;
                } else {
                    delete asistenciaData[aId][dia];
                }

                sel.className = 'form-select form-select-sm border-0 text-center p-0 select-asistencia ' + (val ? (ESTADO_CLASES[val] || '') : '');

                var fila = sel.closest('tr');
                if (fila) {
                    var celdas = fila.querySelectorAll('td');
                    var c = contarAsistencias(aId);
                    var offset = celdas.length;
                    celdas[offset - 4].textContent = c.A;
                    celdas[offset - 3].textContent = c.F;
                    celdas[offset - 2].textContent = c.R;
                    celdas[offset - 1].textContent = c.J;
                }

                actualizarResumen();
            });

            btnCargar.addEventListener('click', function () {
                renderTabla();
            });

            btnGuardar.addEventListener('click', function () {
                guardarAsistenciasEnApi();
            });

            var btnPagos = document.getElementById('btnCalcularPagos');
            if (btnPagos) {
                btnPagos.addEventListener('click', function (e) {
                    e.preventDefault();
                    var mes = selectMes.value;
                    var anio = selectAnio.value;
                    window.location.href = '{{ url("/Gestion_alumnos/calculo-pagos") }}' +
                        '?empresa_id=' + encodeURIComponent(empresaId) +
                        '&mes=' + encodeURIComponent(mes) +
                        '&anio=' + encodeURIComponent(anio);
                });
            }

            // --- Descargar plantilla CSV ---
            var btnDescargar = document.getElementById('btnDescargarPlantilla');
            btnDescargar.addEventListener('click', function () {
                var mes = parseInt(selectMes.value);
                var anio = parseInt(selectAnio.value);
                var numDias = getDiasEnMes(mes, anio);

                var encabezado = ['alumno_id', 'matricula', 'alumno_nombre'];
                for (var d = 1; d <= numDias; d++) {
                    var dow = new Date(anio, mes - 1, d).getDay();
                    var habil = (dow !== 0 && dow !== 6);
                    encabezado.push('dia_' + d + (habil ? '' : '_FIN'));
                }
                var filas = [encabezado.join(',')];

                alumnosEmpresa.forEach(function (alumno) {
                    var aId = String(alumno.alumno_id);
                    var fila = [
                        aId,
                        alumno.numero_matricula != null ? alumno.numero_matricula : '',
                        '"' + (alumno.alumno_nombre || '').replace(/"/g, '""') + '"'
                    ];
                    for (var d = 1; d <= numDias; d++) {
                        var habil = esDiaHabil(anio, mes, d);
                        var val = (asistenciaData[aId] && asistenciaData[aId][d]) ? asistenciaData[aId][d] : '';
                        fila.push(habil ? val : 'INHABIL');
                    }
                    filas.push(fila.join(','));
                });

                var csv = '\uFEFF' + filas.join('\n');
                var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'asistencias_' + empresaId + '_' + anio + '_' + String(mes).padStart(2, '0') + '.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });

            // --- Subir plantilla CSV ---
            var btnMostrarSubir = document.getElementById('btnMostrarSubir');
            var seccionSubir = document.getElementById('seccionSubirPlantilla');
            var btnCerrarSubir = document.getElementById('btnCerrarSubir');
            var btnProcesar = document.getElementById('btnProcesarPlantilla');
            var inputCsv = document.getElementById('inputPlantillaCsv');
            var statusSubir = document.getElementById('statusSubirPlantilla');

            btnMostrarSubir.addEventListener('click', function () {
                seccionSubir.classList.toggle('d-none');
                inputCsv.value = '';
                statusSubir.innerHTML = '';
            });

            btnCerrarSubir.addEventListener('click', function () {
                seccionSubir.classList.add('d-none');
            });

            btnProcesar.addEventListener('click', function () {
                var file = inputCsv.files && inputCsv.files[0];
                if (!file) {
                    alert('Selecciona un archivo CSV.');
                    return;
                }

                statusSubir.innerHTML = '<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Procesando...</span>';

                var reader = new FileReader();
                reader.onload = function (e) {
                    try {
                        var text = e.target.result;
                        var lines = text.split(/\r?\n/).filter(function (l) { return l.trim() !== ''; });
                        if (lines.length < 2) {
                            statusSubir.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> El archivo está vacío o no tiene datos.</span>';
                            return;
                        }

                        var header = lines[0].split(',');
                        var diaColumnas = [];
                        header.forEach(function (col, idx) {
                            var match = col.trim().match(/^dia_(\d+)$/);
                            if (match) {
                                diaColumnas.push({ idx: idx, dia: parseInt(match[1]) });
                            }
                        });

                        if (!diaColumnas.length) {
                            statusSubir.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> No se encontraron columnas de días (dia_1, dia_2, ...) en el encabezado.</span>';
                            return;
                        }

                        var nuevaData = {};
                        var registros = 0;

                        for (var i = 1; i < lines.length; i++) {
                            var campos = parseCsvLine(lines[i]);
                            var alumnoId = String(campos[0] || '').trim();
                            if (!alumnoId || alumnoId === '') continue;

                            nuevaData[alumnoId] = {};
                            diaColumnas.forEach(function (dc) {
                                var val = String(campos[dc.idx] || '').trim().toUpperCase();
                                if (['A', 'F', 'R', 'J'].indexOf(val) !== -1) {
                                    nuevaData[alumnoId][dc.dia] = val;
                                    registros++;
                                }
                            });
                        }

                        // Merge con datos actuales
                        Object.keys(nuevaData).forEach(function (aId) {
                            if (!asistenciaData[aId]) asistenciaData[aId] = {};
                            Object.keys(nuevaData[aId]).forEach(function (dia) {
                                asistenciaData[aId][dia] = nuevaData[aId][dia];
                            });
                        });

                        renderTablaConDatos();
                        statusSubir.innerHTML = '<span class="text-success"><i class="fa-solid fa-circle-check"></i> Plantilla procesada: ' + registros + ' registros cargados. Presiona <strong>Guardar</strong> para enviar a la base de datos.</span>';

                    } catch (err) {
                        console.error(err);
                        statusSubir.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-xmark"></i> Error al procesar el archivo: ' + escapeHtml(err.message) + '</span>';
                    }
                };
                reader.readAsText(file, 'UTF-8');
            });

            function parseCsvLine(line) {
                var result = [];
                var current = '';
                var inQuotes = false;
                for (var i = 0; i < line.length; i++) {
                    var ch = line[i];
                    if (inQuotes) {
                        if (ch === '"' && i + 1 < line.length && line[i + 1] === '"') {
                            current += '"';
                            i++;
                        } else if (ch === '"') {
                            inQuotes = false;
                        } else {
                            current += ch;
                        }
                    } else {
                        if (ch === '"') {
                            inQuotes = true;
                        } else if (ch === ',') {
                            result.push(current);
                            current = '';
                        } else {
                            current += ch;
                        }
                    }
                }
                result.push(current);
                return result;
            }

            function renderTablaConDatos() {
                var mes = parseInt(selectMes.value);
                var anio = parseInt(selectAnio.value);
                diasDelMes = getDiasEnMes(mes, anio);
                statPeriodo.textContent = MESES[mes - 1] + ' ' + anio;

                tituloTabla.textContent = 'Asistencia — ' + MESES[mes - 1] + ' ' + anio + ' — ' + empresaNombre;

                var DIAS_SEMANA = ['D','L','M','Mi','J','V','S'];
                var trDiaSemana = '<tr><th class="text-start" style="min-width:180px;"></th>';
                var trHead = '<tr><th class="text-start" style="min-width:180px;">Alumno</th>';
                for (var d = 1; d <= diasDelMes; d++) {
                    var habil = esDiaHabil(anio, mes, d);
                    var dow = new Date(anio, mes - 1, d).getDay();
                    var claseDia = habil ? '' : 'bg-light text-muted';
                    trDiaSemana += '<th style="min-width:36px;font-size:0.65rem;" class="' + claseDia + '">' + DIAS_SEMANA[dow] + '</th>';
                    trHead += '<th style="min-width:36px;" class="' + claseDia + '">' + d + '</th>';
                }
                trDiaSemana += '<th></th><th></th><th></th><th></th></tr>';
                trHead += '<th style="min-width:50px;">A</th><th style="min-width:50px;">F</th><th style="min-width:50px;">R</th><th style="min-width:50px;">J</th></tr>';
                theadEl.innerHTML = trDiaSemana + trHead;

                tbodyEl.innerHTML = '';
                alumnosEmpresa.forEach(function (alumno) {
                    var aId = String(alumno.alumno_id);
                    if (!asistenciaData[aId]) asistenciaData[aId] = {};

                    var tr = document.createElement('tr');
                    var celdas = '<td class="text-start text-nowrap small">' + escapeHtml(alumno.alumno_nombre) + '</td>';

                    for (var d = 1; d <= diasDelMes; d++) {
                        var habil = esDiaHabil(anio, mes, d);
                        var val = asistenciaData[aId][d] || '';
                        var claseEstado = val ? (ESTADO_CLASES[val] || '') : '';
                        var claseFondo = !habil && !val ? 'bg-light' : '';

                        celdas += '<td class="p-0 ' + claseFondo + '">';
                        if (habil) {
                            celdas += '<select class="form-select form-select-sm border-0 text-center p-0 select-asistencia ' + claseEstado + '" ' +
                                'data-alumno="' + aId + '" data-dia="' + d + '" style="font-size:0.7rem;height:28px;width:36px;">';
                            celdas += '<option value="">-</option>';
                            ESTADOS_ASISTENCIA.forEach(function (e) {
                                celdas += '<option value="' + e + '"' + (val === e ? ' selected' : '') + '>' + e + '</option>';
                            });
                            celdas += '</select>';
                        } else {
                            celdas += '<span class="text-muted" style="font-size:0.65rem;">—</span>';
                        }
                        celdas += '</td>';
                    }

                    var conteos = contarAsistencias(aId);
                    celdas += '<td class="small fw-bold text-success">' + conteos.A + '</td>';
                    celdas += '<td class="small fw-bold text-danger">' + conteos.F + '</td>';
                    celdas += '<td class="small fw-bold text-warning">' + conteos.R + '</td>';
                    celdas += '<td class="small fw-bold text-secondary">' + conteos.J + '</td>';

                    tr.innerHTML = celdas;
                    tbodyEl.appendChild(tr);
                });

                contenedor.classList.remove('d-none');
                btnGuardar.disabled = false;
                actualizarResumen();
            }

            cargarAlumnosEmpresa().then(function () {
                renderTabla().then(function () {
                    btnDescargar.disabled = alumnosEmpresa.length === 0;
                    btnMostrarSubir.disabled = alumnosEmpresa.length === 0;
                });
            });
        });
    </script>
@endsection
