@extends('layouts.app')
@section('content')
    @include('Gestion_alumnos.partials.sweet_alerts')

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <style>
        .pagos-page {
            --pagos-primary: #1e40af;
            --pagos-soft: #eff6ff;
            --pagos-border: rgba(30, 64, 175, 0.12);
            --pagos-muted: #64748b;
        }

        .pagos-card {
            background: #fff;
            border: 1px solid var(--pagos-border);
            border-radius: 24px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.07);
        }

        .pagos-toolbar {
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
            border: 1px solid var(--pagos-border);
            border-radius: 22px;
            padding: 1.25rem;
        }

        .pagos-stat {
            border: 1px solid var(--pagos-border);
            border-radius: 18px;
            padding: 1rem;
            background: var(--pagos-soft);
            height: 100%;
        }

        .pagos-stat small {
            color: var(--pagos-muted);
            display: block;
            margin-bottom: 0.25rem;
        }

        .pagos-stat strong {
            color: #0f172a;
            font-size: 1.05rem;
        }

        .pago-summary-card {
            border: 1px solid var(--pagos-border);
            border-radius: 20px;
            padding: 1.15rem;
            background: #fff;
            height: 100%;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
        }

        .pago-summary-card .summary-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--pagos-soft);
            color: var(--pagos-primary);
            margin-bottom: 0.75rem;
        }

        .formula-box {
            border: 1px solid rgba(14, 165, 233, 0.24);
            border-radius: 18px;
            background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
            color: #334155;
        }

        #tablaPagos {
            border-collapse: separate;
            border-spacing: 0;
            min-width: 980px;
        }

        #tablaPagos thead th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            border-color: #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        #tablaPagos tbody td:first-child,
        #tablaPagos thead th:first-child {
            position: sticky;
            left: 0;
            z-index: 3;
            background: #fff;
            box-shadow: 8px 0 14px rgba(15, 23, 42, 0.04);
        }

        #tablaPagos tbody tr:hover td {
            background-color: #f8fbff;
        }

        #tablaPagos tfoot td {
            background: #f8fafc;
            border-top: 2px solid #dbeafe;
        }
    </style>

    @php
        $apiConveniosAsignaciones = url('/Gestion_alumnos/api/convenios-asignaciones');
        $apiAsistencias = url('/Gestion_alumnos/api/asistencias');
        $empresaIdUsuario = $empresaIdUsuario ?? null;
    @endphp

    <div class="container-fluid acciones-config-page pagos-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Cálculo de Pagos</h2>
                            <p class="text-muted mb-0" id="subtituloEmpresa">Cálculo de pagos por alumno basado en asistencia.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="#" class="btn btn-baseColor fs-7 mb-2" id="btnCrearFactura">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Crear factura
                        </a>
                        <a href="#" class="btn btn-baseColor-light fs-7 mb-2" id="btnRegresarAsistencias">
                            <i class="fa-solid fa-arrow-left"></i> Asistencias
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="pagos-stat">
                    <small><i class="fa-solid fa-building me-1"></i>Empresa</small>
                    <strong id="statEmpresaPago">Pendiente de carga</strong>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="pagos-stat">
                    <small><i class="fa-solid fa-calendar-days me-1"></i>Periodo</small>
                    <strong id="statPeriodoPago">Mes actual</strong>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="pagos-stat">
                    <small><i class="fa-solid fa-user-graduate me-1"></i>Alumnos activos</small>
                    <strong id="statAlumnosPago">0 alumnos</strong>
                </div>
            </div>
        </div>

        <div class="pagos-card p-3 p-lg-4 mt-2 mb-2">
            <div class="pagos-toolbar mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-3">
                    <label class="form-label small mb-1" for="selectMesPago">Mes</label>
                    <select class="form-select form-select-sm" id="selectMesPago" disabled></select>
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label small mb-1" for="selectAnioPago">Año</label>
                    <select class="form-select form-select-sm" id="selectAnioPago" disabled></select>
                </div>
                <div class="col-lg-3 col-md-3">
                    <label class="form-label small mb-1" for="inputPrecioMensual">Precio mensual por alumno ($)</label>
                    <input type="number" class="form-control form-control-sm" id="inputPrecioMensual" value="5350" min="0" step="0.01">
                </div>
                <div class="col-lg-2 col-md-3">
                    <label class="form-label small mb-1" for="inputDiasPromedio">Días promedio mes (Dm)</label>
                    <input type="number" class="form-control form-control-sm" id="inputDiasPromedio" value="30.4" min="1" step="0.1">
                </div>
                <div class="col-lg-3 col-md-12 d-flex flex-wrap gap-2 justify-content-lg-end">
                    <button type="button" class="btn btn-baseColor btn-sm" id="btnRecalcular">
                        <i class="fa-solid fa-calculator"></i> Recalcular
                    </button>
                    <button type="button" class="btn btn-baseColor-light btn-sm" id="btnGuardarPagos" disabled>
                        <i class="fa-solid fa-floppy-disk"></i> Guardar pagos
                    </button>
                </div>
            </div>
            </div>

            <div id="alertNoEmpresa" class="alert alert-warning d-none">
                <i class="fa-solid fa-triangle-exclamation"></i> No se recibió el parámetro de empresa.
            </div>

            <div id="spinnerCargando" class="text-center py-4 d-none">
                <div class="spinner-border text-orange" role="status"><span class="visually-hidden">Cargando...</span></div>
            </div>

            <div id="contenedorPagos" class="d-none">
                {{-- Resumen general --}}
                <div class="row g-3 mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="pago-summary-card">
                            <span class="summary-icon"><i class="fa-solid fa-user-graduate"></i></span>
                            <div class="text-muted small">Total alumnos</div>
                            <h3 class="mb-0 fw-bold" id="cardTotalAlumnos">0</h3>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="pago-summary-card">
                            <span class="summary-icon"><i class="fa-solid fa-sack-dollar"></i></span>
                            <div class="text-muted small">Pago total</div>
                            <h3 class="mb-0 fw-bold text-success" id="cardPagoTotal">$0.00</h3>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="pago-summary-card">
                            <span class="summary-icon"><i class="fa-solid fa-circle-check"></i></span>
                            <div class="text-muted small">Pago sin faltas</div>
                            <h3 class="mb-0 fw-bold text-primary" id="cardPagoSinFaltas">$0.00</h3>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="pago-summary-card">
                            <span class="summary-icon"><i class="fa-solid fa-circle-minus"></i></span>
                            <div class="text-muted small">Descuento por faltas</div>
                            <h3 class="mb-0 fw-bold text-danger" id="cardDescuento">$0.00</h3>
                        </div>
                    </div>
                </div>

                {{-- Fórmula aplicada --}}
                <div class="formula-box p-3 small mb-4">
                    <i class="fa-solid fa-circle-info"></i>
                    <strong>Fórmula:</strong> Pago por alumno = (P / Dm) × d<sub>i</sub>
                    &nbsp;|&nbsp; <strong>P</strong> = precio mensual &nbsp;|&nbsp;
                    <strong>Dm</strong> = días promedio del mes &nbsp;|&nbsp;
                    <strong>d<sub>i</sub></strong> = días asistidos (Dm − faltas).
                    Sin faltas se cobra P completo.
                </div>

                {{-- Tabla de detalle --}}
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                    <div>
                        <h5 class="text-secondary mb-1">
                            <i class="fa-solid fa-list-check me-2"></i>Detalle de pagos
                        </h5>
                        <p class="text-muted fs-8 mb-0">Revisa los días cobrados y el monto calculado por alumno.</p>
                    </div>
                </div>
                <div class="table-responsive rounded-4 border">
                    <table class="table table-sm table-bordered table-hover mb-0" id="tablaPagos">
                        <thead>
                            <tr class="text-tr">
                                <th>#</th>
                                <th>Matrícula</th>
                                <th class="text-start">Alumno</th>
                                <th>Asistencias</th>
                                <th>Faltas</th>
                                <th>Retardos</th>
                                <th>Justificadas</th>
                                <th>d<sub>i</sub> (días cobrados)</th>
                                <th>Pago ($)</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyPagos"></tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">TOTAL</td>
                                <td id="tfootA" class="text-center">0</td>
                                <td id="tfootF" class="text-center">0</td>
                                <td id="tfootR" class="text-center">0</td>
                                <td id="tfootJ" class="text-center">0</td>
                                <td id="tfootDi" class="text-center">0</td>
                                <td id="tfootPago" class="text-center text-success">$0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiBase = @json($apiConveniosAsignaciones);
            const apiAsistencias = @json($apiAsistencias);
            const apiGuardarPagos = apiAsistencias + '/guardar-pagos';
            const empresaIdUsuario = @json($empresaIdUsuario);
            const params = new URLSearchParams(window.location.search);
            const empresaId = params.get('empresa_id') || empresaIdUsuario;
            const mesParam = params.get('mes');
            const anioParam = params.get('anio');

            if (!empresaId) {
                document.getElementById('alertNoEmpresa').classList.remove('d-none');
                return;
            }

            const MESES = [
                'Enero','Febrero','Marzo','Abril','Mayo','Junio',
                'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
            ];

            const selectMes = document.getElementById('selectMesPago');
            const selectAnio = document.getElementById('selectAnioPago');
            const inputPrecio = document.getElementById('inputPrecioMensual');
            const inputDm = document.getElementById('inputDiasPromedio');
            const btnRecalcular = document.getElementById('btnRecalcular');
            const btnGuardarPagos = document.getElementById('btnGuardarPagos');
            const spinner = document.getElementById('spinnerCargando');
            const contenedor = document.getElementById('contenedorPagos');
            const tbodyPagos = document.getElementById('tbodyPagos');
            const subtitulo = document.getElementById('subtituloEmpresa');
            const btnRegresar = document.getElementById('btnRegresarAsistencias');
            const statEmpresa = document.getElementById('statEmpresaPago');
            const statPeriodo = document.getElementById('statPeriodoPago');
            const statAlumnos = document.getElementById('statAlumnosPago');

            let alumnosEmpresa = [];
            let asistenciaData = {};
            let empresaNombre = '';
            let pagosCalculados = [];

            function getCsrfToken() {
                var m = document.querySelector('meta[name="csrf-token"]');
                return m ? m.getAttribute('content') : '';
            }

            const hoy = new Date();
            var mesActual = mesParam ? parseInt(mesParam) : (hoy.getMonth() + 1);
            var anioActual = anioParam ? parseInt(anioParam) : hoy.getFullYear();

            MESES.forEach(function (m, i) {
                var opt = document.createElement('option');
                opt.value = i + 1;
                opt.textContent = m;
                if ((i + 1) === mesActual) opt.selected = true;
                selectMes.appendChild(opt);
            });
            for (var y = hoy.getFullYear() - 2; y <= 2050; y++) {
                var opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                if (y === anioActual) opt.selected = true;
                selectAnio.appendChild(opt);
            }
            statPeriodo.textContent = MESES[mesActual - 1] + ' ' + anioActual;

            if (btnRegresar) {
                btnRegresar.href = '{{ url("/Gestion_alumnos/control-asistencias") }}?empresa_id=' + encodeURIComponent(empresaId);
            }

            var btnFactura = document.getElementById('btnCrearFactura');
            if (btnFactura) {
                btnFactura.href = '{{ url("/Gestion_alumnos/facturacion-empresa") }}' +
                    '?empresa_id=' + encodeURIComponent(empresaId) +
                    '&mes=' + encodeURIComponent(mesActual) +
                    '&anio=' + encodeURIComponent(anioActual);
            }

            function escapeHtml(txt) {
                var d = document.createElement('div');
                d.appendChild(document.createTextNode(txt || ''));
                return d.innerHTML;
            }

            function formatMoney(n) {
                return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function esDiaHabil(anio, mes, dia) {
                var d = new Date(anio, mes - 1, dia);
                var dow = d.getDay();
                return dow !== 0 && dow !== 6;
            }

            function getDiasHabiles(mes, anio) {
                var total = new Date(anio, mes, 0).getDate();
                var habiles = 0;
                for (var d = 1; d <= total; d++) {
                    if (esDiaHabil(anio, mes, d)) habiles++;
                }
                return habiles;
            }

            async function cargarAlumnosEmpresa() {
                var r = await fetch(apiBase + '?estado=activo', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                var j = await r.json();
                var todos = j.data || [];
                alumnosEmpresa = todos.filter(function (a) { return String(a.empresa_id) === String(empresaId); });
                if (alumnosEmpresa.length > 0) {
                    empresaNombre = alumnosEmpresa[0].empresa_nombre || 'Empresa';
                    subtitulo.textContent = 'Empresa: ' + empresaNombre + ' — ' + MESES[mesActual - 1] + ' ' + anioActual;
                    statEmpresa.textContent = empresaNombre;
                } else {
                    empresaNombre = 'Empresa #' + empresaId;
                    statEmpresa.textContent = empresaNombre;
                }
                statAlumnos.textContent = alumnosEmpresa.length + (alumnosEmpresa.length === 1 ? ' alumno' : ' alumnos');
            }

            async function cargarAsistencias(mes, anio) {
                var url = apiAsistencias + '?empresa_id=' + encodeURIComponent(empresaId) +
                    '&mes=' + encodeURIComponent(mes) + '&anio=' + encodeURIComponent(anio);
                var r = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                var j = await r.json();
                return j.data || {};
            }

            function calcularYRenderizar() {
                var mes = parseInt(selectMes.value);
                var anio = parseInt(selectAnio.value);
                var P = parseFloat(inputPrecio.value) || 0;
                var Dm = parseFloat(inputDm.value) || 30.4;
                var costoDiario = P / Dm;
                statPeriodo.textContent = MESES[mes - 1] + ' ' + anio;

                tbodyPagos.innerHTML = '';
                pagosCalculados = [];

                var totalA = 0, totalF = 0, totalR = 0, totalJ = 0, totalDi = 0, totalPago = 0;
                var pagoSinFaltas = 0;

                alumnosEmpresa.forEach(function (alumno, idx) {
                    var aId = String(alumno.alumno_id);
                    var datos = asistenciaData[aId] || {};

                    var contA = 0, contF = 0, contR = 0, contJ = 0;
                    Object.values(datos).forEach(function (v) {
                        if (v === 'A') contA++;
                        else if (v === 'F') contF++;
                        else if (v === 'R') contR++;
                        else if (v === 'J') contJ++;
                    });

                    var di = Dm - contF;
                    if (di < 0) di = 0;

                    var pagoAlumno = contF === 0 ? P : (costoDiario * di);
                    pagoAlumno = Math.round(pagoAlumno * 100) / 100;

                    pagosCalculados.push({
                        alumno_id: parseInt(aId),
                        total_asistencias: contA,
                        total_faltas: contF,
                        total_retardos: contR,
                        total_justificadas: contJ,
                        dias_cobrados: parseFloat(di.toFixed(1)),
                        monto_calculado: pagoAlumno,
                    });

                    totalA += contA;
                    totalF += contF;
                    totalR += contR;
                    totalJ += contJ;
                    totalDi += di;
                    totalPago += pagoAlumno;
                    pagoSinFaltas += P;

                    var clasePago = contF > 0 ? 'text-warning' : 'text-success';
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + (idx + 1) + '</td>' +
                        '<td>' + escapeHtml(alumno.numero_matricula != null ? String(alumno.numero_matricula) : '—') + '</td>' +
                        '<td class="text-start">' + escapeHtml(alumno.alumno_nombre) + '</td>' +
                        '<td>' + contA + '</td>' +
                        '<td class="' + (contF > 0 ? 'text-danger fw-bold' : '') + '">' + contF + '</td>' +
                        '<td>' + contR + '</td>' +
                        '<td>' + contJ + '</td>' +
                        '<td>' + di.toFixed(1) + '</td>' +
                        '<td class="fw-bold ' + clasePago + '">' + formatMoney(pagoAlumno) + '</td>';
                    tbodyPagos.appendChild(tr);
                });

                document.getElementById('tfootA').textContent = totalA;
                document.getElementById('tfootF').textContent = totalF;
                document.getElementById('tfootR').textContent = totalR;
                document.getElementById('tfootJ').textContent = totalJ;
                document.getElementById('tfootDi').textContent = totalDi.toFixed(1);
                document.getElementById('tfootPago').textContent = formatMoney(totalPago);

                document.getElementById('cardTotalAlumnos').textContent = alumnosEmpresa.length;
                document.getElementById('cardPagoTotal').textContent = formatMoney(totalPago);
                document.getElementById('cardPagoSinFaltas').textContent = formatMoney(pagoSinFaltas);
                document.getElementById('cardDescuento').textContent = formatMoney(pagoSinFaltas - totalPago);

                contenedor.classList.remove('d-none');
                btnGuardarPagos.disabled = pagosCalculados.length === 0;
            }

            btnRecalcular.addEventListener('click', function () {
                calcularYRenderizar();
            });

            btnGuardarPagos.addEventListener('click', async function () {
                var mes = parseInt(selectMes.value);
                var anio = parseInt(selectAnio.value);
                var P = parseFloat(inputPrecio.value) || 0;
                var Dm = parseFloat(inputDm.value) || 30.4;

                if (!pagosCalculados.length) {
                    alert('No hay pagos que guardar. Calcula primero.');
                    return;
                }

                btnGuardarPagos.disabled = true;
                btnGuardarPagos.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

                try {
                    var r = await fetch(apiGuardarPagos, {
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
                            precio_mensual_alumno: P,
                            dias_promedio_mes: Dm,
                            pagos: pagosCalculados,
                        }),
                    });
                    var j = await r.json();
                    if (!r.ok) {
                        var msg = j.message || 'Error al guardar.';
                        if (j.errors) msg = Object.values(j.errors).flat().join('\n');
                        alert('Error: ' + msg);
                        return;
                    }
                    alert('Pagos guardados correctamente.\nRegistros actualizados: ' + (j.actualizados || 0));
                } catch (err) {
                    console.error('Error guardando pagos:', err);
                    alert('Error de red al guardar los pagos.');
                } finally {
                    btnGuardarPagos.disabled = false;
                    btnGuardarPagos.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar pagos';
                }
            });

            spinner.classList.remove('d-none');
            Promise.all([
                cargarAlumnosEmpresa(),
                cargarAsistencias(mesActual, anioActual)
            ]).then(function (results) {
                asistenciaData = results[1];
                spinner.classList.add('d-none');
                calcularYRenderizar();
            }).catch(function (err) {
                console.error(err);
                spinner.classList.add('d-none');
            });
        });
    </script>
@endsection
