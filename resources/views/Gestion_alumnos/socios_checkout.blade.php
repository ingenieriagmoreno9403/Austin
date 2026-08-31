@extends('layouts.app')
@section('content')
    @include('Gestion_alumnos.partials.sweet_alerts')

    <link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
    <style>
        .checkout-search-card {
            background: linear-gradient(135deg, #3b82f6 0%, #6d28d9 55%, #60a5fa 100%);
            box-shadow: 0 12px 34px rgba(59, 130, 246, .24);
            color: #fff;
            overflow: visible;
            position: relative;
            z-index: 30;
        }

        .checkout-search-card::before {
            background: radial-gradient(circle, rgba(255, 255, 255, .16) 0%, transparent 68%);
            content: '';
            height: 180px;
            pointer-events: none;
            position: absolute;
            right: 1rem;
            top: -80px;
            width: 180px;
        }

        .checkout-search-card .card-header,
        .checkout-search-card .card-body {
            background: transparent !important;
            position: relative;
            z-index: 1;
        }

        .checkout-search-card .card-header .text-secondary,
        .checkout-search-card .card-header .text-muted {
            color: #fff !important;
        }

        .checkout-search-card .card-header .text-muted {
            opacity: .86;
        }

        .checkout-search-card .card-body {
            padding-top: .75rem;
        }

        .socio-search-box {
            margin-bottom: 2rem;
            max-width: 620px;
            position: relative;
        }

        .socio-search-input-wrap {
            position: relative;
        }

        .socio-search-icon {
            color: #6c757d;
            left: 1rem;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 2;
        }

        .socio-search-input {
            background: rgba(255, 255, 255, .96);
            border: 1px solid rgba(255, 255, 255, .75);
            border-radius: 999px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .16);
            height: 46px;
            padding-left: 2.65rem;
            padding-right: 2.75rem;
        }

        .socio-search-input:focus {
            border-color: #0d6efd;
            box-shadow: 0 10px 24px rgba(13, 110, 253, .12);
        }

        .socio-search-loader {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .socio-search-help {
            color: rgba(255, 255, 255, .88);
        }

        .socio-search-results {
            background: #fff;
            border: 1px solid #edf0f4;
            border-radius: 1rem;
            display: flex;
            flex-direction: column;
            gap: .75rem;
            box-shadow: 0 16px 38px rgba(15, 23, 42, .12);
            margin-bottom: 1rem;
            margin-top: .65rem;
            max-height: 320px;
            overflow-y: auto;
            padding: .5rem;
            position: relative;
            z-index: 50;
        }

        .socio-search-empty {
            background: #f8fafc;
            border: 1px dashed #d9e0ea;
            border-radius: .85rem;
            color: #6c757d;
            margin-top: .65rem;
            max-width: 620px;
            padding: .65rem .85rem;
        }

        .socio-result-item {
            border: 0;
            border-radius: .85rem;
            padding: .7rem .8rem;
            transition: background-color .15s ease, transform .15s ease;
            width: 100%;
        }

        .socio-result-item:hover {
            background: #f5f8ff;
            transform: translateY(-1px);
        }

        .checkout-history-card {
            position: relative;
            z-index: 1;
        }

        .checkout-history-filters {
            background: #f8fafc;
            border: 1px solid #edf0f4;
            border-radius: 1rem;
            margin-bottom: 1.25rem;
            padding: .9rem;
        }

        .checkout-history-table {
            border-collapse: separate;
            border-spacing: 0 .55rem;
        }

        .checkout-history-table thead th {
            border: 0;
            color: #6c757d;
            font-size: .78rem;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .checkout-history-table tbody tr {
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
        }

        .checkout-history-table tbody td {
            background: #fff;
            border: 0;
            padding: .85rem .75rem;
            vertical-align: middle;
        }

        .checkout-history-table tbody td:first-child {
            border-bottom-left-radius: .9rem;
            border-top-left-radius: .9rem;
        }

        .checkout-history-table tbody td:last-child {
            border-bottom-right-radius: .9rem;
            border-top-right-radius: .9rem;
        }

        .history-time-pill,
        .history-member-pill {
            background: #eef5ff;
            border-radius: 999px;
            color: #0d6efd;
            display: inline-block;
            font-weight: 600;
            padding: .25rem .6rem;
        }
    </style>

    <div class="container-fluid acciones-config-page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fa-solid fa-right-to-bracket"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Check out de Socios</h2>
                            <p class="text-muted mb-0">Búsqueda y registro de entradas con validación de pagos al corriente.</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('gestion_alumnos.socios.reportes_diarios') }}" class="btn btn-baseColor-light fs-8 mb-2">
                            <i class="fa-solid fa-chart-column"></i> Reportes diarios
                        </a>
                        <a class="btn btn-baseColor-light fs-8 mb-2" href="javascript:history.back()">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiHistorial = url('/Gestion_alumnos/api/checkout-socios/historial-hoy');
        @endphp

        <div class="card border-0 shadow p-3 mt-2 mb-2 rounded-5 checkout-search-card">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Buscar socio
                </h5>
            </div>
            <div class="card-body">
                @livewire('socios-checkout-buscador')
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5 checkout-history-card">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de entradas
                </h5>
                <p class="text-muted fs-8 mb-0">Consulta entradas registradas por fecha o por socio.</p>
            </div>
            <div class="card-body">
                <div class="checkout-history-filters mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3 col-12">
                            <label class="form-label">Fecha</label>
                            <input type="date" id="filtroFechaHistorial" class="form-control text">
                        </div>
                        <div class="col-md-5 col-12">
                            <label class="form-label">Buscar socio</label>
                            <input type="text" id="filtroSocioHistorial" class="form-control text" placeholder="No. socio o nombre...">
                        </div>
                        <div class="col-md-4 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-baseColor-light flex-fill" id="btnFiltrarHistorial">
                                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
                            </button>
                            <button type="button" class="btn btn-secondary btn-moderno flex-fill" id="btnLimpiarHistorial">
                                <i class="fa-solid fa-rotate-left"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm checkout-history-table mb-0">
                        <thead>
                            <tr class="text-tr">
                                <th>Hora</th>
                                <th>No. socio</th>
                                <th>Dependiente</th>
                                <th>Nombre</th>
                                <th>Resultado</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyCheckoutHistorialHoy">
                            <tr><td class="text-muted" colspan="6">Sin entradas registradas.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const apiHistorial = @json($apiHistorial);
            const tbodyHistorial = document.getElementById('tbodyCheckoutHistorialHoy');
            const filtroFechaHistorial = document.getElementById('filtroFechaHistorial');
            const filtroSocioHistorial = document.getElementById('filtroSocioHistorial');
            const btnFiltrarHistorial = document.getElementById('btnFiltrarHistorial');
            const btnLimpiarHistorial = document.getElementById('btnLimpiarHistorial');

            const parseJsonResponse = async function (r) {
                const text = await r.text();
                if (!text) return {};
                try { return JSON.parse(text); } catch (e) { return { _parseError: true, _raw: text }; }
            };
            const esc = function (txt) {
                if (txt == null) return '';
                return String(txt).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            };

            const renderHistorial = function (lista) {
                tbodyHistorial.innerHTML = '';
                if (!lista || !lista.length) {
                    tbodyHistorial.innerHTML = '<tr><td class="text-muted" colspan="6">Sin entradas registradas.</td></tr>';
                    return;
                }
                lista.forEach(function (r) {
                    const socio = r.socio || {};
                    const nombre = [socio.nombre || '', socio.ap_paterno || '', socio.ap_materno || ''].join(' ').replace(/\s+/g, ' ').trim();
                    const badge = String(r.resultado || '').toLowerCase() === 'permitido'
                        ? '<span class="badge rounded-pill bg-success px-3 py-2">Permitido</span>'
                        : '<span class="badge rounded-pill bg-danger px-3 py-2">Denegado</span>';
                    const hora = r.fecha_hora_entrada ? String(r.fecha_hora_entrada).replace('T', ' ').slice(11, 19) : '-';
                    const numeroSocio = socio.numero_socio || '-';
                    const numeroDependiente = socio.numero_dependiente || '-';
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td><span class="history-time-pill"><i class="fa-regular fa-clock me-1"></i>' + esc(hora) + '</span></td>' +
                        '<td><span class="history-member-pill">#' + esc(numeroSocio) + '</span></td>' +
                        '<td>' + esc(numeroDependiente) + '</td>' +
                        '<td><strong class="text-marino">' + esc(nombre || '-') + '</strong></td>' +
                        '<td>' + badge + '</td>' +
                        '<td><span class="text-muted">' + esc(r.motivo || '-') + '</span></td>';
                    tbodyHistorial.appendChild(tr);
                });
            };

            const cargarHistorial = async function () {
                try {
                    const qs = new URLSearchParams();
                    const fecha = filtroFechaHistorial ? String(filtroFechaHistorial.value || '').trim() : '';
                    const q = filtroSocioHistorial ? String(filtroSocioHistorial.value || '').trim() : '';
                    if (fecha) qs.set('fecha', fecha);
                    if (q) qs.set('q', q);
                    const url = qs.toString() ? (apiHistorial + '?' + qs.toString()) : apiHistorial;
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const j = await parseJsonResponse(r);
                    if (!r.ok) {
                        renderHistorial([]);
                        return;
                    }
                    renderHistorial(j.data || []);
                } catch (err) {
                    console.error(err);
                    renderHistorial([]);
                }
            };

            if (btnFiltrarHistorial) {
                btnFiltrarHistorial.addEventListener('click', function () { void cargarHistorial(); });
            }
            if (filtroSocioHistorial) {
                filtroSocioHistorial.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        void cargarHistorial();
                    }
                });
            }
            if (filtroFechaHistorial) {
                filtroFechaHistorial.addEventListener('change', function () { void cargarHistorial(); });
            }
            if (btnLimpiarHistorial) {
                btnLimpiarHistorial.addEventListener('click', function () {
                    if (filtroSocioHistorial) filtroSocioHistorial.value = '';
                    if (filtroFechaHistorial) filtroFechaHistorial.value = '';
                    void cargarHistorial();
                });
            }

            window.addEventListener('checkout-socio-alert', function (event) {
                const detail = event.detail || {};
                const message = detail.message || '';
                if (window.Swal) {
                    window.Swal.fire({
                        icon: detail.icon || 'info',
                        title: detail.title || 'Aviso',
                        text: message,
                    }).then(function () {
                        if (detail.reloadPage) {
                            window.location.reload();
                        }
                    });
                } else {
                    window.alert(message);
                    if (detail.reloadPage) {
                        window.location.reload();
                    }
                }
                if (detail.refreshHistorial) {
                    void cargarHistorial();
                }
            });

            await cargarHistorial();
        });
    </script>
@endsection
