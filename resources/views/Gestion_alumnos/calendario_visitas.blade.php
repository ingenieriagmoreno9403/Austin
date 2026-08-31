@extends('layouts.app')
@section('content')
    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <style>
        .calendario-page {
            --cal-primary: #1e40af;
            --cal-soft: #eff6ff;
            --cal-border: rgba(30, 64, 175, 0.12);
            --cal-muted: #64748b;
        }

        .calendario-card {
            background: #fff;
            border: 1px solid var(--cal-border);
            border-radius: 24px;
            box-shadow: 0 16px 38px rgba(15, 23, 42, 0.07);
            height: 100%;
        }

        .calendario-card > .card-body {
            padding: 1.5rem;
        }

        .calendario-section-title {
            color: #334155;
            font-weight: 700;
        }

        .calendario-stat {
            border: 1px solid var(--cal-border);
            border-radius: 18px;
            padding: 1rem;
            background: var(--cal-soft);
            height: 100%;
        }

        .calendario-stat small {
            color: var(--cal-muted);
            display: block;
            margin-bottom: 0.25rem;
        }

        .calendario-stat strong {
            color: #0f172a;
            font-size: 1.05rem;
        }

        #tablaDetalleVisitas {
            border-collapse: separate;
            border-spacing: 0;
        }

        #tablaDetalleVisitas thead th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            border-color: #e2e8f0;
        }

        #tablaDetalleVisitas tbody tr:hover td {
            background-color: #f8fbff;
        }
    </style>

    <div class="container-fluid acciones-config-page calendario-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Calendario de visitas a empresas</h2>
                            <p class="text-muted mb-0">Consulta visitas del mes, empresas por día y gráfica por estado.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a class="btn btn-baseColor-light fs-7 mb-2" href="javascript:history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="calendario-stat">
                    <small><i class="fa-solid fa-calendar-check me-1"></i>Visitas registradas</small>
                    <strong>{{ count($visitasCalendario ?? []) }} visitas</strong>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="calendario-stat">
                    <small><i class="fa-solid fa-building me-1"></i>Empresas con visita</small>
                    <strong>{{ collect($visitasCalendario ?? [])->pluck('empresa')->filter()->unique()->count() }} empresas</strong>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="calendario-stat">
                    <small><i class="fa-solid fa-chart-column me-1"></i>Seguimiento</small>
                    <strong>Resumen por estado</strong>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="calendario-card">
                    <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="calendario-section-title mb-0"><i class="fa-solid fa-calendar me-2 text-primary"></i>Calendario de visitas</h5>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnMesAnterior">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <span class="fw-bold" id="tituloMesCalendario">-</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnMesSiguiente">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="calendar-labels mb-2">
                        <span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sa</span><span>Do</span>
                    </div>
                    <div class="calendar-grid" id="calendarGridVisitasDetalle"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="calendario-card">
                    <div class="card-body">
                    <h5 class="calendario-section-title mb-2"><i class="fa-solid fa-chart-column me-2 text-primary"></i>Resumen por estado</h5>
                    <small class="text-muted d-block mb-3" id="tituloGraficaEstado">Mes actual</small>
                    <div style="height: 260px;">
                        <canvas id="chartVisitasEstados"></canvas>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="calendario-card">
            <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="calendario-section-title mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i>Detalle del día seleccionado</h5>
                <span id="detalleFechaSeleccionada" class="fw-bold">-</span>
            </div>
            <div class="table-responsive rounded-4 border">
                <table class="table table-sm table-stripped table-hover mb-0" id="tablaDetalleVisitas">
                    <thead>
                        <tr class="text-tr">
                            <th>Empresa</th>
                            <th>Estado</th>
                            <th>Estatus visita</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDetalleVisitasDia">
                        <tr>
                            <td class="text-muted" colspan="3">Selecciona un dia para ver visitas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const visitas = @json($visitasCalendario ?? []);
            const calendarGrid = document.getElementById('calendarGridVisitasDetalle');
            const tbodyDia = document.getElementById('tbodyDetalleVisitasDia');
            const detalleFechaSeleccionada = document.getElementById('detalleFechaSeleccionada');
            const tituloGraficaEstado = document.getElementById('tituloGraficaEstado');
            const chartCtx = document.getElementById('chartVisitasEstados');
            const tituloMesCalendario = document.getElementById('tituloMesCalendario');
            const btnMesAnterior = document.getElementById('btnMesAnterior');
            const btnMesSiguiente = document.getElementById('btnMesSiguiente');

            const pad2 = (n) => String(n).padStart(2, '0');
            const now = new Date();
            let viewingYear = now.getFullYear();
            let viewingMonth = now.getMonth();

            const porFecha = visitas.reduce((acc, it) => {
                const key = String(it.fecha_visita || '');
                if (!key) return acc;
                if (!acc[key]) acc[key] = [];
                acc[key].push(it);
                return acc;
            }, {});

            const escHtml = (value) => String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const filtrarVisitasMes = function (year, month) {
                const prefijo = year + '-' + pad2(month + 1) + '-';
                return visitas.filter((v) => String(v.fecha_visita || '').startsWith(prefijo));
            };

            const actualizarTituloMes = function () {
                if (!tituloMesCalendario) {
                    return;
                }
                const dt = new Date(viewingYear, viewingMonth, 1);
                tituloMesCalendario.textContent = dt.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
            };

            let chart = null;
            const pintarGrafica = function (lista, titulo) {
                const conteo = {};
                lista.forEach((v) => {
                    const estado = (v.estado && String(v.estado).trim()) ? String(v.estado).trim() : 'Sin estado';
                    conteo[estado] = (conteo[estado] || 0) + 1;
                });
                const labels = Object.keys(conteo);
                const data = Object.values(conteo);
                if (tituloGraficaEstado) tituloGraficaEstado.textContent = titulo;

                if (chart) {
                    chart.destroy();
                }
                chart = new Chart(chartCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels.length ? labels : ['Sin visitas'],
                        datasets: [{
                            label: 'Visitas',
                            data: data.length ? data : [0],
                            backgroundColor: '#2563eb',
                            borderRadius: 6,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    },
                });
            };

            const renderDetalleDia = function (iso) {
                const fecha = new Date(iso + 'T00:00:00');
                detalleFechaSeleccionada.textContent = fecha.toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' });
                const lista = porFecha[iso] || [];
                tbodyDia.innerHTML = '';
                if (!lista.length) {
                    tbodyDia.innerHTML = '<tr><td class="text-muted" colspan="3">Sin visitas registradas en este dia.</td></tr>';
                    pintarGrafica([], 'Sin visitas en el dia');
                    return;
                }

                lista.forEach((v) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + escHtml(v.empresa || 'Empresa sin nombre') + '</td>' +
                        '<td>' + escHtml(v.estado || 'Sin estado') + '</td>' +
                        '<td>' + escHtml(v.estatus || 'Pendiente') + '</td>';
                    tbodyDia.appendChild(tr);
                });
                pintarGrafica(lista, 'Visitas por estado del dia seleccionado');
            };

            const renderCalendar = function () {
                calendarGrid.innerHTML = '';
                const firstDay = new Date(viewingYear, viewingMonth, 1);
                const firstWeekIndex = (firstDay.getDay() + 6) % 7;
                const totalDays = new Date(viewingYear, viewingMonth + 1, 0).getDate();
                actualizarTituloMes();

                for (let i = 0; i < firstWeekIndex; i++) {
                    const empty = document.createElement('div');
                    empty.className = 'calendar-day calendar-day-empty';
                    empty.textContent = '.';
                    calendarGrid.appendChild(empty);
                }

                for (let day = 1; day <= totalDays; day++) {
                    const iso = viewingYear + '-' + pad2(viewingMonth + 1) + '-' + pad2(day);
                    const count = (porFecha[iso] || []).length;
                    const dayEl = document.createElement('div');
                    dayEl.className = 'calendar-day' + (count > 0 ? ' event' : '');
                    dayEl.dataset.dateIso = iso;
                    dayEl.innerHTML = String(day) + (count > 0 ? ' <span class="event-dot"></span>' : '');
                    dayEl.addEventListener('click', function () {
                        calendarGrid.querySelectorAll('.calendar-day.selected').forEach((el) => el.classList.remove('selected'));
                        dayEl.classList.add('selected');
                        renderDetalleDia(iso);
                    });
                    calendarGrid.appendChild(dayEl);
                }
            };

            renderCalendar();
            pintarGrafica(filtrarVisitasMes(viewingYear, viewingMonth), 'Visitas por estado del mes mostrado');

            const seleccionarDiaInicial = function () {
                const todayIso = now.getFullYear() + '-' + pad2(now.getMonth() + 1) + '-' + pad2(now.getDate());
                let selectedIso = viewingYear + '-' + pad2(viewingMonth + 1) + '-01';
                if (viewingYear === now.getFullYear() && viewingMonth === now.getMonth()) {
                    selectedIso = todayIso;
                } else {
                    const primerConVisita = filtrarVisitasMes(viewingYear, viewingMonth)
                        .map((v) => String(v.fecha_visita || ''))
                        .find((f) => f !== '');
                    if (primerConVisita) {
                        selectedIso = primerConVisita;
                    }
                }
                const cell = calendarGrid.querySelector('[data-date-iso="' + selectedIso + '"]');
                if (cell) {
                    cell.classList.add('selected');
                    renderDetalleDia(selectedIso);
                }
            };
            seleccionarDiaInicial();

            if (btnMesAnterior) {
                btnMesAnterior.addEventListener('click', function () {
                    viewingMonth -= 1;
                    if (viewingMonth < 0) {
                        viewingMonth = 11;
                        viewingYear -= 1;
                    }
                    renderCalendar();
                    pintarGrafica(filtrarVisitasMes(viewingYear, viewingMonth), 'Visitas por estado del mes mostrado');
                    seleccionarDiaInicial();
                });
            }

            if (btnMesSiguiente) {
                btnMesSiguiente.addEventListener('click', function () {
                    viewingMonth += 1;
                    if (viewingMonth > 11) {
                        viewingMonth = 0;
                        viewingYear += 1;
                    }
                    renderCalendar();
                    pintarGrafica(filtrarVisitasMes(viewingYear, viewingMonth), 'Visitas por estado del mes mostrado');
                    seleccionarDiaInicial();
                });
            }
        });
    </script>

    <style>
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .calendar-labels {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 1px;
            text-align: center;
        }
        .calendar-day {
            background: #f8fafc;
            border-radius: 10px;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            color: #1e40af;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .calendar-day:hover {
            background: #e0e7ff;
            transform: translateY(-2px);
        }
        .calendar-day.event {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.15), rgba(59, 130, 246, 0.25));
            border: 1px solid rgba(37, 99, 235, 0.2);
        }
        .calendar-day.selected {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
        }
        .calendar-day .event-dot {
            width: 6px;
            height: 6px;
            background: #f59e0b;
            border-radius: 999px;
            display: inline-block;
            margin-top: 6px;
        }
        .calendar-day-empty {
            visibility: hidden;
            pointer-events: none;
        }
    </style>
@endsection
