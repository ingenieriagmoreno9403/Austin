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
                            <i class="fa-solid fa-clipboard-list"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Detalle de evento</h2>
                            <p class="text-muted mb-0">{{ $evento->nombre_evento }} - {{ $evento->fecha_evento ?: 'Sin fecha' }}</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('gestion_alumnos.socios.eventos') }}" class="btn btn-baseColor-light fs-8 mb-2">
                            <i class="fa-solid fa-arrow-left"></i> Volver a eventos
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @php
            $apiEvento = url('/Gestion_alumnos/api/socios/eventos/' . $evento->id);
            $apiDetalles = url('/Gestion_alumnos/api/socios/eventos/' . $evento->id . '/detalles');
        @endphp

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <h5 class="text-secondary mb-1">
                    <i class="fa-solid fa-plus-circle me-2"></i>Agregar concepto al evento
                </h5>
                <p class="text-muted fs-8 mb-0">Captura recursos, proveedores, costos y horarios de entrega.</p>
            </div>
            <div class="card-body">
                <form id="formDetalleEvento" class="g-3 form needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Concepto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text" name="concepto" maxlength="200" required placeholder="Ej. Sillas, mesas, sonido">
                        </div>
                        <div class="col-md-2 col-12 mt-2">
                            <label class="form-label">Categoría</label>
                            <input type="text" class="form-control text" name="categoria" maxlength="120" placeholder="Mobiliario">
                        </div>
                        <div class="col-md-2 col-12 mt-2">
                            <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" class="form-control text" name="cantidad" min="0.01" step="0.01" value="1" required>
                        </div>
                        <div class="col-md-2 col-12 mt-2">
                            <label class="form-label">Costo unitario <span class="text-danger">*</span></label>
                            <input type="number" class="form-control text" name="costo_unitario" min="0" step="0.01" value="0" required>
                        </div>
                        <div class="col-md-2 col-12 mt-2">
                            <label class="form-label">Costo total</label>
                            <input type="text" class="form-control text" id="previewCostoTotalDetalle" readonly value="$0.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Empresa / proveedor</label>
                            <input type="text" class="form-control text" name="empresa_proveedor" maxlength="200" placeholder="Nombre empresa">
                        </div>
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Lugar de entrega <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text" name="lugar_entrega" maxlength="200" required placeholder="Ej. Salón principal">
                        </div>
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Fecha de recibido <span class="text-danger">*</span></label>
                            <input type="date" class="form-control text" name="fecha_recibido" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 col-12 mt-2">
                            <label class="form-label">Hora recibido <span class="text-danger">*</span></label>
                            <input type="time" class="form-control text" name="hora_recibido" required>
                        </div>
                        <div class="col-md-4 col-12 mt-2">
                            <label class="form-label">Fecha de recogida <span class="text-danger">*</span></label>
                            <input type="date" class="form-control text" name="fecha_recogida" required>
                        </div>
                        <div class="col-md-2 col-12 mt-2">
                            <label class="form-label">Hora recogida <span class="text-danger">*</span></label>
                            <input type="time" class="form-control text" name="hora_recogida" required>
                        </div>
                        <div class="col-md-2 col-12 mt-2">
                            <label class="form-label">Observaciones</label>
                            <input type="text" class="form-control text" name="observaciones" maxlength="255">
                        </div>
                        <div class="col-md-2 col-12 mt-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-baseColor w-100">
                                <i class="fa-solid fa-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mt-2">
                            <small class="text-muted">Tip: puedes usar observaciones para notas de montaje/entrega.</small>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
            <div class="card-header text-start bg-body border-0">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <h5 class="text-secondary mb-0">
                        <i class="fa-solid fa-list-check me-2"></i>Conceptos del evento
                    </h5>
                    <span class="fw-bold">Total: <span id="lblTotalEventoDetalle">$0.00</span></span>
                </div>
                <p class="text-muted fs-8 mb-0">Listado de conceptos agregados al evento.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-stripped table-hover mb-0">
                        <thead>
                            <tr class="text-tr">
                                <th>Concepto</th>
                                <th>Categoría</th>
                                <th>Cantidad</th>
                                <th>Costo unitario</th>
                                <th>Costo total</th>
                                <th>Proveedor</th>
                                <th>Lugar entrega</th>
                                <th>Recibido</th>
                                <th>Recogida</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyEventoDetalles">
                            <tr><td class="text-muted" colspan="10">Sin conceptos registrados.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', async function () {
            const apiEvento = @json($apiEvento);
            const apiDetalles = @json($apiDetalles);
            const form = document.getElementById('formDetalleEvento');
            const tbody = document.getElementById('tbodyEventoDetalles');
            const lblTotal = document.getElementById('lblTotalEventoDetalle');
            const previewTotal = document.getElementById('previewCostoTotalDetalle');

            const parseJsonResponse = async function (r) {
                const text = await r.text();
                if (!text) return {};
                try { return JSON.parse(text); } catch (e) { return {}; }
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
            const updatePreview = function () {
                if (!form || !previewTotal) return;
                const cantidad = Number(form.elements['cantidad'].value || 0);
                const costo = Number(form.elements['costo_unitario'].value || 0);
                previewTotal.value = money(cantidad * costo);
            };

            const cargarDetalles = async function () {
                const r = await fetch(apiDetalles, { headers: { 'Accept': 'application/json' } });
                const j = await parseJsonResponse(r);
                const data = (r.ok && j && Array.isArray(j.data)) ? j.data : [];
                tbody.innerHTML = '';
                if (!data.length) {
                    tbody.innerHTML = '<tr><td class="text-muted" colspan="10">Sin conceptos registrados.</td></tr>';
                    if (lblTotal) lblTotal.textContent = money(0);
                    return;
                }
                let total = 0;
                data.forEach(function (row) {
                    total += Number(row.costo_total || 0);
                    const recibido = (row.fecha_recibido ? String(row.fecha_recibido) : '—') + (row.hora_recibido ? (' ' + String(row.hora_recibido).slice(0, 5)) : '');
                    const recogida = (row.fecha_recogida ? String(row.fecha_recogida) : '—') + (row.hora_recogida ? (' ' + String(row.hora_recogida).slice(0, 5)) : '');
                    const tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + esc(row.concepto || '—') + '</td>' +
                        '<td>' + esc(row.categoria || '—') + '</td>' +
                        '<td>' + esc(row.cantidad || '0') + '</td>' +
                        '<td>' + esc(money(row.costo_unitario || 0)) + '</td>' +
                        '<td>' + esc(money(row.costo_total || 0)) + '</td>' +
                        '<td>' + esc(row.empresa_proveedor || '—') + '</td>' +
                        '<td>' + esc(row.lugar_entrega || '—') + '</td>' +
                        '<td>' + esc(recibido) + '</td>' +
                        '<td>' + esc(recogida) + '</td>' +
                        '<td>' + esc(row.observaciones || '—') + '</td>';
                    tbody.appendChild(tr);
                });
                if (lblTotal) lblTotal.textContent = money(total);
            };

            if (form) {
                form.elements['cantidad'].addEventListener('input', updatePreview);
                form.elements['costo_unitario'].addEventListener('input', updatePreview);
                form.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.classList.add('was-validated');
                    if (!form.checkValidity()) return;

                    const payload = {
                        concepto: String(form.elements['concepto'].value || '').trim(),
                        categoria: String(form.elements['categoria'].value || '').trim(),
                        cantidad: Number(form.elements['cantidad'].value || 0),
                        costo_unitario: Number(form.elements['costo_unitario'].value || 0),
                        empresa_proveedor: String(form.elements['empresa_proveedor'].value || '').trim(),
                        lugar_entrega: String(form.elements['lugar_entrega'].value || '').trim(),
                        fecha_recibido: String(form.elements['fecha_recibido'].value || '').trim(),
                        hora_recibido: String(form.elements['hora_recibido'].value || '').trim(),
                        fecha_recogida: String(form.elements['fecha_recogida'].value || '').trim(),
                        hora_recogida: String(form.elements['hora_recogida'].value || '').trim(),
                        observaciones: String(form.elements['observaciones'].value || '').trim(),
                    };
                    const r = await fetch(apiDetalles, {
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
                        window.alert((j && j.message) ? j.message : 'No se pudo guardar detalle.');
                        return;
                    }
                    form.reset();
                    form.classList.remove('was-validated');
                    updatePreview();
                    await fetch(apiEvento, { headers: { 'Accept': 'application/json' } });
                    await cargarDetalles();
                });
            }

            updatePreview();
            await cargarDetalles();
        });
    </script>
@endsection
