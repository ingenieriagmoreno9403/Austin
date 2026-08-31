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
                            <i class="fa-solid fa-chart-column"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Reportes diarios de socios</h2>
                            <p class="text-muted mb-0">Nuevos socios, montos del día, entradas y corte de caja esperado.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('gestion_alumnos.socios.checkout') }}" class="btn btn-baseColor-light fs-8 mb-2">
                            <i class="fa-solid fa-arrow-left"></i> Volver a check out
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiResumen = url('/Gestion_alumnos/api/socios/reportes-diarios/resumen');
            $apiGuardarCorte = url('/Gestion_alumnos/api/socios/reportes-diarios/corte');
            $apiHistorial = url('/Gestion_alumnos/api/socios/reportes-diarios/historial');
            $apiTrafico = url('/Gestion_alumnos/api/socios/reportes-diarios/trafico');
            $apiPagosGrafica = url('/Gestion_alumnos/api/socios/reportes-diarios/pagos-grafica');
        @endphp

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-calendar-day me-2"></i>Filtros del reporte
                </h5>
                <p class="text-muted fs-8 mb-0">Selecciona la fecha para actualizar resumen, gráficas y corte.</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-12">
                        <label class="form-label">Fecha de reporte</label>
                        <input type="date" id="fechaReporteDiario" class="form-control text">
                    </div>
                    <div class="col-md-3 col-12 mt-md-0 mt-2 d-flex align-items-end">
                        <button type="button" class="btn btn-baseColor-light w-100" id="btnCargarResumen">
                            <i class="fa-solid fa-rotate"></i> Cargar resumen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card border-0 shadow-sm p-3 h-100 bg-body rounded-5">
                    <div class="text-muted fs-8">Nuevos socios</div>
                    <div class="fs-4 fw-bold" id="valNuevosSocios">0</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card border-0 shadow-sm p-3 h-100 bg-body rounded-5">
                    <div class="text-muted fs-8">Socios que entraron</div>
                    <div class="fs-4 fw-bold" id="valSociosEntradas">0</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card border-0 shadow-sm p-3 h-100 bg-body rounded-5">
                    <div class="text-muted fs-8">Monto cobrado del día</div>
                    <div class="fs-4 fw-bold text-success" id="valMontoCobrado">$0.00</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-12">
                <div class="card border-0 shadow-sm p-3 h-100 bg-body rounded-5">
                    <div class="text-muted fs-8">Cancelaciones del día</div>
                    <div class="fs-4 fw-bold text-danger" id="valMontoCancelado">$0.00</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6 col-12">
                <div class="card border-0 shadow p-3 h-100 bg-body rounded-5">
                    <div class="card-header text-start bg-body border-0">
                        <h5 class="text-secondary mb-1">
                            <i class="fa-solid fa-chart-line me-2"></i>Tráfico de socios por día
                        </h5>
                    </div>
                    <canvas id="chartTraficoPorDia" height="120"></canvas>
                </div>
            </div>
            <div class="col-lg-6 col-12">
                <div class="card border-0 shadow p-3 h-100 bg-body rounded-5">
                    <div class="card-header text-start bg-body border-0">
                        <h5 class="text-secondary mb-1">
                            <i class="fa-solid fa-clock me-2"></i>Horas de entrada del día
                        </h5>
                    </div>
                    <canvas id="chartTraficoPorHora" height="120"></canvas>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow p-3 bg-body rounded-5">
                    <div class="card-header text-start bg-body border-0">
                        <h5 class="text-secondary mb-1">
                            <i class="fa-solid fa-money-bill-trend-up me-2"></i>Pagos por día (cobrado vs cancelado)
                        </h5>
                    </div>
                    <canvas id="chartPagosPorDia" height="90"></canvas>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-cash-register me-2"></i>Corte de caja
                </h5>
                <p class="text-muted fs-8 mb-0">Registra caja física y valida diferencia contra el corte esperado.</p>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 col-12">
                        <label class="form-label">Corte esperado</label>
                        <input type="text" class="form-control text" id="valCorteEsperado" readonly value="$0.00">
                    </div>
                    <div class="col-md-4 col-12 mt-md-0 mt-2">
                        <label class="form-label">Caja física reportada</label>
                        <input type="number" step="0.01" min="0" class="form-control text" id="inpCajaFisicaReportada" placeholder="0.00">
                    </div>
                    <div class="col-md-4 col-12 mt-md-0 mt-2">
                        <label class="form-label">Diferencia</label>
                        <input type="text" class="form-control text" id="valDiferenciaCorte" readonly value="$0.00">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8 col-12">
                        <label class="form-label">Observaciones</label>
                        <input type="text" class="form-control text" id="inpObservacionesCorte" maxlength="255" placeholder="Opcional">
                    </div>
                    <div class="col-md-4 col-12 mt-md-0 mt-2 d-flex align-items-end">
                        <button type="button" class="btn btn-baseColor w-100" id="btnGuardarCorte">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar corte diario
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-list-check me-2"></i>Historial de cortes guardados
                </h5>
                <p class="text-muted fs-8 mb-0">Revisa los cortes diarios registrados previamente.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-stripped table-hover mb-0">
                        <thead>
                            <tr class="text-tr">
                                <th>Fecha</th>
                                <th>Nuevos socios</th>
                                <th>Entradas</th>
                                <th>Cobrado</th>
                                <th>Cancelado</th>
                                <th>Esperado</th>
                                <th>Caja física</th>
                                <th>Diferencia</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyHistorialCortes">
                            <tr><td class="text-muted" colspan="8">Sin cortes guardados.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const apiResumen = @json($apiResumen);
            const apiGuardarCorte = @json($apiGuardarCorte);
            const apiHistorial = @json($apiHistorial);
            const apiTrafico = @json($apiTrafico);
            const apiPagosGrafica = @json($apiPagosGrafica);

            const fechaReporte = document.getElementById('fechaReporteDiario');
            const btnCargarResumen = document.getElementById('btnCargarResumen');
            const btnGuardarCorte = document.getElementById('btnGuardarCorte');
            const inpCajaFisicaReportada = document.getElementById('inpCajaFisicaReportada');
            const inpObservacionesCorte = document.getElementById('inpObservacionesCorte');
            const tbodyHistorialCortes = document.getElementById('tbodyHistorialCortes');

            const valNuevosSocios = document.getElementById('valNuevosSocios');
            const valSociosEntradas = document.getElementById('valSociosEntradas');
            const valMontoCobrado = document.getElementById('valMontoCobrado');
            const valMontoCancelado = document.getElementById('valMontoCancelado');
            const valCorteEsperado = document.getElementById('valCorteEsperado');
            const valDiferenciaCorte = document.getElementById('valDiferenciaCorte');
            const chartTraficoPorDiaEl = document.getElementById('chartTraficoPorDia');
            const chartTraficoPorHoraEl = document.getElementById('chartTraficoPorHora');
            const chartPagosPorDiaEl = document.getElementById('chartPagosPorDia');

            let corteEsperadoNumero = 0;
            let chartTraficoDia = null;
            let chartTraficoHora = null;
            let chartPagos = null;

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
                const x = Number(n || 0);
                return '$' + x.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };
            const destroyChart = function (chartRef) {
                if (chartRef && typeof chartRef.destroy === 'function') {
                    chartRef.destroy();
                }
            };

            const actualizarDiferencia = function () {
                const caja = Number(inpCajaFisicaReportada ? inpCajaFisicaReportada.value : 0) || 0;
                const diferencia = caja - corteEsperadoNumero;
                if (valDiferenciaCorte) {
                    valDiferenciaCorte.value = money(diferencia);
                    valDiferenciaCorte.classList.remove('text-success', 'text-danger');
                    if (diferencia > 0) valDiferenciaCorte.classList.add('text-success');
                    if (diferencia < 0) valDiferenciaCorte.classList.add('text-danger');
                }
            };

            const cargarResumen = async function () {
                try {
                    const f = fechaReporte && fechaReporte.value ? String(fechaReporte.value) : '';
                    const url = f ? (apiResumen + '?fecha=' + encodeURIComponent(f)) : apiResumen;
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok || !j || !j.data) {
                        window.alert('No se pudo cargar el resumen diario.');
                        return;
                    }
                    const d = j.data;
                    if (valNuevosSocios) valNuevosSocios.textContent = String(d.nuevos_socios || 0);
                    if (valSociosEntradas) valSociosEntradas.textContent = String(d.socios_entradas || 0);
                    if (valMontoCobrado) valMontoCobrado.textContent = money(d.monto_cobrado_dia || 0);
                    if (valMontoCancelado) valMontoCancelado.textContent = money(d.monto_cancelado_dia || 0);
                    corteEsperadoNumero = Number(d.corte_caja_esperado || 0);
                    if (valCorteEsperado) valCorteEsperado.value = money(corteEsperadoNumero);

                    if (inpCajaFisicaReportada && d.caja_fisica_reportada != null) {
                        inpCajaFisicaReportada.value = Number(d.caja_fisica_reportada).toFixed(2);
                    }
                    if (inpObservacionesCorte && d.observaciones != null) {
                        inpObservacionesCorte.value = d.observaciones;
                    }
                    actualizarDiferencia();
                } catch (err) {
                    console.error(err);
                    window.alert('Error al consultar resumen diario.');
                }
            };

            const cargarHistorialCortes = async function () {
                try {
                    const r = await fetch(apiHistorial, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    const lista = (r.ok && j && Array.isArray(j.data)) ? j.data : [];
                    tbodyHistorialCortes.innerHTML = '';
                    if (!lista.length) {
                        tbodyHistorialCortes.innerHTML = '<tr><td class="text-muted" colspan="8">Sin cortes guardados.</td></tr>';
                        return;
                    }
                    lista.forEach(function (row) {
                        const tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td>' + esc(row.fecha_reporte || '—') + '</td>' +
                            '<td>' + esc(row.nuevos_socios || 0) + '</td>' +
                            '<td>' + esc(row.socios_entradas || 0) + '</td>' +
                            '<td>' + esc(money(row.monto_cobrado_dia || 0)) + '</td>' +
                            '<td>' + esc(money(row.monto_cancelado_dia || 0)) + '</td>' +
                            '<td>' + esc(money(row.corte_caja_esperado || 0)) + '</td>' +
                            '<td>' + esc(money(row.caja_fisica_reportada || 0)) + '</td>' +
                            '<td>' + esc(money(row.diferencia_corte || 0)) + '</td>';
                        tbodyHistorialCortes.appendChild(tr);
                    });
                } catch (err) {
                    console.error(err);
                    tbodyHistorialCortes.innerHTML = '<tr><td class="text-muted" colspan="8">No se pudo cargar historial.</td></tr>';
                }
            };

            const cargarGraficas = async function () {
                try {
                    const f = fechaReporte && fechaReporte.value ? String(fechaReporte.value) : '';
                    const qs = f ? ('?fecha=' + encodeURIComponent(f) + '&dias=14') : '?dias=14';
                    const [rt, rp] = await Promise.all([
                        fetch(apiTrafico + qs, { headers: { 'Accept': 'application/json' } }),
                        fetch(apiPagosGrafica + qs, { headers: { 'Accept': 'application/json' } }),
                    ]);
                    const jt = await parseJsonResponse(rt);
                    const jp = await parseJsonResponse(rp);

                    const traficoDia = (rt.ok && jt && jt.data && jt.data.entradas_por_dia) ? jt.data.entradas_por_dia : { labels: [], values: [] };
                    const traficoHora = (rt.ok && jt && jt.data && jt.data.entradas_por_hora) ? jt.data.entradas_por_hora : { labels: [], values: [] };
                    const pagos = (rp.ok && jp && jp.data) ? jp.data : { labels: [], pagados: [], cancelados: [] };

                    if (window.Chart && chartTraficoPorDiaEl) {
                        destroyChart(chartTraficoDia);
                        chartTraficoDia = new window.Chart(chartTraficoPorDiaEl, {
                            type: 'line',
                            data: {
                                labels: traficoDia.labels || [],
                                datasets: [{
                                    label: 'Entradas',
                                    data: traficoDia.values || [],
                                    borderColor: '#0d6efd',
                                    backgroundColor: 'rgba(13,110,253,.20)',
                                    tension: 0.3,
                                    fill: true,
                                }],
                            },
                            options: { responsive: true, plugins: { legend: { display: false } } },
                        });
                    }

                    if (window.Chart && chartTraficoPorHoraEl) {
                        destroyChart(chartTraficoHora);
                        chartTraficoHora = new window.Chart(chartTraficoPorHoraEl, {
                            type: 'bar',
                            data: {
                                labels: traficoHora.labels || [],
                                datasets: [{
                                    label: 'Entradas por hora',
                                    data: traficoHora.values || [],
                                    backgroundColor: 'rgba(255,159,64,.7)',
                                    borderColor: '#ff9f40',
                                }],
                            },
                            options: { responsive: true, plugins: { legend: { display: false } } },
                        });
                    }

                    if (window.Chart && chartPagosPorDiaEl) {
                        destroyChart(chartPagos);
                        chartPagos = new window.Chart(chartPagosPorDiaEl, {
                            type: 'bar',
                            data: {
                                labels: pagos.labels || [],
                                datasets: [
                                    {
                                        label: 'Cobrado',
                                        data: pagos.pagados || [],
                                        backgroundColor: 'rgba(25,135,84,.75)',
                                    },
                                    {
                                        label: 'Cancelado',
                                        data: pagos.cancelados || [],
                                        backgroundColor: 'rgba(220,53,69,.70)',
                                    }
                                ],
                            },
                            options: { responsive: true, scales: { y: { beginAtZero: true } } },
                        });
                    }
                } catch (err) {
                    console.error(err);
                }
            };

            if (inpCajaFisicaReportada) {
                inpCajaFisicaReportada.addEventListener('input', actualizarDiferencia);
            }
            if (btnCargarResumen) {
                btnCargarResumen.addEventListener('click', function () { void cargarResumen(); void cargarGraficas(); });
            }
            if (fechaReporte) {
                const hoy = new Date();
                const y = hoy.getFullYear();
                const m = String(hoy.getMonth() + 1).padStart(2, '0');
                const d = String(hoy.getDate()).padStart(2, '0');
                fechaReporte.value = y + '-' + m + '-' + d;
                fechaReporte.addEventListener('change', function () { void cargarResumen(); void cargarGraficas(); });
            }
            if (btnGuardarCorte) {
                btnGuardarCorte.addEventListener('click', async function () {
                    try {
                        const fecha = fechaReporte ? String(fechaReporte.value || '').trim() : '';
                        const caja = Number(inpCajaFisicaReportada ? inpCajaFisicaReportada.value : 0);
                        if (!fecha) {
                            window.alert('Selecciona la fecha del reporte.');
                            return;
                        }
                        if (!(caja >= 0)) {
                            window.alert('Captura la caja física reportada.');
                            return;
                        }
                        const r = await fetch(apiGuardarCorte, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                fecha_reporte: fecha,
                                caja_fisica_reportada: caja,
                                observaciones: inpObservacionesCorte ? String(inpObservacionesCorte.value || '').trim() : '',
                            }),
                        });
                        const j = await parseJsonResponse(r);
                        if (!r.ok) {
                            window.alert((j && j.message) ? j.message : 'No se pudo guardar el corte.');
                            return;
                        }
                        window.alert((j && j.message) ? j.message : 'Corte guardado.');
                        await cargarResumen();
                        await cargarHistorialCortes();
                    } catch (err) {
                        console.error(err);
                        window.alert('Error de red al guardar corte.');
                    }
                });
            }

            await cargarResumen();
            await cargarHistorialCortes();
            await cargarGraficas();
        });
    </script>
@endsection
