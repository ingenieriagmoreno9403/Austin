@extends('layouts.app')
@section('content')
@include('Gestion_alumnos.partials.sweet_alerts')

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .factura-page {
        --factura-primary: #1e40af;
        --factura-soft: #eff6ff;
        --factura-border: rgba(30, 64, 175, 0.12);
        --factura-muted: #64748b;
    }

    .factura-card {
        background: #fff;
        border: 1px solid var(--factura-border);
        border-radius: 24px;
        box-shadow: 0 16px 38px rgba(15, 23, 42, 0.07);
        height: 100%;
    }

    .factura-card > .card-body {
        padding: 1.5rem;
    }

    .factura-section-title {
        color: #334155;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .factura-stat {
        border: 1px solid var(--factura-border);
        border-radius: 18px;
        padding: 1rem;
        background: var(--factura-soft);
        height: 100%;
    }

    .factura-stat small {
        color: var(--factura-muted);
        display: block;
        margin-bottom: 0.25rem;
    }

    .factura-stat strong {
        color: #0f172a;
        font-size: 1.05rem;
    }

    .factura-data-row {
        border: 1px solid #eef2ff;
        border-radius: 14px;
        padding: 0.7rem 0.85rem;
        background: #f8fbff;
        margin-bottom: 0.65rem;
    }

    .factura-data-row strong {
        color: #475569;
        display: block;
        font-size: 0.78rem;
        margin-bottom: 0.15rem;
    }

    #tablaFacturaConceptos {
        border-collapse: separate;
        border-spacing: 0;
        min-width: 1080px;
    }

    #tablaFacturaConceptos thead th {
        background: #f8fafc;
        color: #334155;
        font-weight: 700;
        border-color: #e2e8f0;
    }

    #tablaFacturaConceptos tfoot td {
        background: #f8fafc;
        border-top: 2px solid #dbeafe;
    }

    .factura-total-row td {
        background: #fff7ed !important;
        color: #9a3412;
    }

    .factura-timbrar-card {
        border: 1px solid rgba(30, 64, 175, 0.14);
        border-radius: 24px;
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 16px 38px rgba(15, 23, 42, 0.07);
    }

    @media (max-width: 767.98px) {
        .factura-card > .card-body {
            padding: 1.15rem;
        }
    }
</style>

<div class="container-fluid acciones-config-page factura-page">

    @php
        $apiConveniosAsignaciones = url('/Gestion_alumnos/api/convenios-asignaciones');
        $apiAsistencias = url('/Gestion_alumnos/api/asistencias');
        $apiDatosFiscales = url('/Sistemas/api/datos-fiscales');
        $apiEmpresas = url('/Gestion_alumnos/api/empresas');
        $apiFacturasBeca = url('/api/facturas-beca');
        $urlVerFactura = url('/verfacturabecas');
        $empresaIdUsuario = $empresaIdUsuario ?? null;
    @endphp

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Factura Empresa</h2>
                        <p class="text-muted mb-0" id="subtituloFactura">Generación de factura por servicio de alumnos duales.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="#" class="btn btn-baseColor-light fs-7 mb-2" id="btnRegresarPagos">
                        <i class="fa-solid fa-arrow-left"></i> Cálculo de pagos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="factura-stat">
                <small><i class="fa-solid fa-building me-1"></i>Empresa receptora</small>
                <strong id="statEmpresaFactura">Pendiente de carga</strong>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="factura-stat">
                <small><i class="fa-solid fa-calendar-days me-1"></i>Periodo</small>
                <strong id="statPeriodoFactura">Mes actual</strong>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="factura-stat">
                <small><i class="fa-solid fa-sack-dollar me-1"></i>Total estimado</small>
                <strong id="statTotalFactura">$0.00</strong>
            </div>
        </div>
    </div>

    <!-- Información Principal -->
    <div class="row">
        <!-- Datos del Emisor -->
        <div class="col-md-6 mb-4">
            <div class="factura-card">
                <div class="card-body">
                    <h5 class="factura-section-title"><i class="fa-solid fa-building text-primary me-2"></i>Emisor</h5>
                    <div class="mb-3">
                        <label class="form-label small mb-1"><strong>Seleccionar empresa emisora:</strong></label>
                        <select class="form-select form-select-sm" id="selectEmisor">
                            <option value="">Cargando empresas...</option>
                        </select>
                    </div>
                    <div class="factura-data-row">
                        <strong>Nombre:</strong> <span id="emisorNombre">—</span>
                    </div>
                    <div class="factura-data-row">
                        <strong>RFC:</strong> <span id="emisorRfc">—</span>
                    </div>
                    <div class="factura-data-row">
                        <strong>Dirección:</strong> <span id="emisorDireccion">—</span>
                    </div>
                    <div class="factura-data-row">
                        <strong>Lugar de Expedición (C.P.):</strong> <span id="emisorLugarExpedicion">—</span>
                    </div>
                    <div class="factura-data-row">
                        <strong>Régimen Fiscal:</strong> <span id="emisorRegimenFiscal">—</span>
                    </div>
                    <div class="factura-data-row mb-0">
                        <strong>Teléfono:</strong> <span id="emisorTelefono">—</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Datos del Receptor (Empresa) -->
        <div class="col-md-6 mb-4">
            <div class="factura-card">
                <div class="card-body">
                    <h5 class="factura-section-title"><i class="fa-solid fa-industry text-primary me-2"></i>Receptor (Empresa)</h5>
                    <div class="factura-data-row">
                        <strong>Nombre:</strong> <span id="receptorNombre" class="fw-bold text-primary">—</span>
                    </div>
                    <div class="factura-data-row">
                        <strong>Razón Social:</strong> <span id="receptorRazonSocial">—</span>
                    </div>
                    <div class="factura-data-row">
                        <strong>RFC:</strong>
                        <input type="text" class="form-control form-control-sm mt-1" id="receptorRfc" placeholder="RFC de la empresa">
                    </div>
                    <div class="factura-data-row">
                        <strong>Dirección fiscal:</strong> <span id="receptorDireccion">—</span>
                    </div>
                    <div class="factura-data-row">
                        <strong>Código Postal:</strong>
                        <input type="text" class="form-control form-control-sm mt-1" id="receptorCodigoPostal" placeholder="C.P.">
                    </div>
                    <div class="factura-data-row">
                        <strong>Correo fiscal:</strong> <span id="receptorCorreoFiscal">—</span>
                    </div>
                    <div class="factura-data-row">
                        <strong>Uso del CFDI:</strong>
                        <select class="form-select form-select-sm mt-1" id="receptorUsoCfdi">
                            <option value="">Seleccionar uso CFDI</option>
                            <option value="G01">G01 - Adquisición de mercancías</option>
                            <option value="G02">G02 - Devoluciones, descuentos o bonificaciones</option>
                            <option value="G03">G03 - Gastos en general</option>
                            <option value="I01">I01 - Construcciones</option>
                            <option value="I02">I02 - Mobiliario y equipo de oficina</option>
                            <option value="I04">I04 - Equipo de cómputo y accesorios</option>
                            <option value="I08">I08 - Otra maquinaria y equipo</option>
                            <option value="P01">P01 - Por definir</option>
                            <option value="S01">S01 - Sin efectos fiscales</option>
                        </select>
                    </div>
                    <div class="factura-data-row mb-0">
                        <strong>Régimen Fiscal:</strong>
                        <select class="form-select form-select-sm mt-1" id="receptorRegimenFiscal">
                            <option value="">Seleccionar régimen fiscal</option>
                            <option value="601">601 - General de Ley Personas Morales</option>
                            <option value="603">603 - Personas Morales con Fines no Lucrativos</option>
                            <option value="605">605 - Sueldos y Salarios</option>
                            <option value="606">606 - Arrendamiento</option>
                            <option value="607">607 - Régimen de Enajenación</option>
                            <option value="608">608 - Demás ingresos</option>
                            <option value="610">610 - Residentes en el Extranjero</option>
                            <option value="612">612 - Personas Físicas con Actividades Empresariales</option>
                            <option value="614">614 - Ingresos por intereses</option>
                            <option value="616">616 - Sin obligaciones fiscales</option>
                            <option value="620">620 - Sociedades Cooperativas de Producción</option>
                            <option value="621">621 - Incorporación Fiscal</option>
                            <option value="625">625 - Régimen de las Actividades Empresariales (RESICO)</option>
                            <option value="626">626 - Régimen Simplificado de Confianza</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Conceptos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="factura-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="factura-section-title mb-1"><i class="fa-solid fa-list text-primary me-2"></i>Conceptos</h5>
                            <p class="text-muted fs-8 mb-0">Captura los servicios que se incluirán en la factura.</p>
                        </div>
                        <button type="button" class="btn btn-baseColor btn-sm" id="btnAgregarConcepto">
                            <i class="fas fa-plus"></i> Agregar Concepto
                        </button>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="checkSinIva">
                        <label class="form-check-label" for="checkSinIva">
                            <strong>Sin IVA</strong> <span class="text-muted small">— No calcular ni sumar IVA al total</span>
                        </label>
                    </div>
                    <div class="table-responsive rounded-4 border">
                        <table class="table table-bordered mb-0" id="tablaFacturaConceptos">
                            <thead>
                                <tr>
                                    <th style="min-width:200px;">Producto / Clave SAT</th>
                                    <th style="width:80px;">Cantidad</th>
                                    <th style="width:140px;">Unidad</th>
                                    <th>Descripción</th>
                                    <th style="width:120px;">Precio U.</th>
                                    <th style="width:120px;">Importe</th>
                                    <th style="width:60px;">Acc.</th>
                                </tr>
                            </thead>
                            <tbody id="tablaConceptos"></tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                    <td colspan="2">$<span id="subtotal">0.00</span></td>
                                </tr>
                                <tr id="filaIva">
                                    <td colspan="5" class="text-end"><strong>IVA 16%:</strong></td>
                                    <td colspan="2">$<span id="iva">0.00</span></td>
                                </tr>
                                <tr class="factura-total-row">
                                    <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                    <td colspan="2"><strong>$<span id="total">0.00</span></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Observaciones y Método de Pago -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="factura-card">
                <div class="card-body">
                    <h6 class="factura-section-title"><i class="fa-solid fa-credit-card text-primary me-2"></i>Método de Pago</h6>
                    <select class="form-select form-select-sm" id="metodoPago">
                        <option value="PUE">PUE - Pago en una sola exhibición</option>
                        <option value="PPD">PPD - Pago en parcialidades o diferido</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="factura-card">
                <div class="card-body">
                    <h6 class="factura-section-title"><i class="fa-solid fa-money-bill text-primary me-2"></i>Forma de Pago</h6>
                    <select class="form-select form-select-sm" id="formaPago">
                        <option value="01">01 - Efectivo</option>
                        <option value="02">02 - Cheque nominativo</option>
                        <option value="03" selected>03 - Transferencia electrónica</option>
                        <option value="04">04 - Tarjeta de crédito</option>
                        <option value="28">28 - Tarjeta de débito</option>
                        <option value="99">99 - Por definir</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="factura-card">
                <div class="card-body">
                    <h6 class="factura-section-title"><i class="fa-solid fa-coins text-primary me-2"></i>Moneda</h6>
                    <select class="form-select form-select-sm" id="moneda">
                        <option value="MXN" selected>MXN - Peso Mexicano</option>
                        <option value="USD">USD - Dólar Americano</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="factura-card">
                <div class="card-body">
                    <h6 class="factura-section-title"><i class="fa-solid fa-comment text-primary me-2"></i>Observaciones</h6>
                    <textarea class="form-control form-control-sm" id="observaciones" rows="3" placeholder="Observaciones o notas adicionales para la factura..."></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón de Timbrar -->
    <div class="row mb-4">
        <div class="col-12 text-center">
            <button type="button" class="btn btn-baseColor btn-lg" id="btnTimbrar" disabled>
                <i class="fa-solid fa-stamp"></i> Timbrar Factura
            </button>
        </div>
    </div>

    <!-- Historial de Facturas Timbradas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title m-0"><i class="fa-solid fa-clock-rotate-left"></i> Facturas Timbradas</h5>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefrescarFacturas">
                            <i class="fa-solid fa-arrows-rotate"></i> Refrescar
                        </button>
                    </div>
                    <div id="cargandoFacturas" class="text-center text-muted py-3">
                        <i class="fa-solid fa-spinner fa-spin"></i> Cargando facturas...
                    </div>
                    <div id="sinFacturas" class="text-center text-muted py-3 d-none">
                        <i class="fa-solid fa-file-circle-xmark"></i> No hay facturas timbradas para esta empresa.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm d-none" id="tablaFacturas">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Receptor</th>
                                    <th>RFC</th>
                                    <th class="text-end">Subtotal</th>
                                    <th class="text-end">IVA</th>
                                    <th class="text-end">Total</th>
                                    <th>UUID</th>
                                    <th>Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyFacturas"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const apiBase = @json($apiConveniosAsignaciones);
        const apiAsistencias = @json($apiAsistencias);
        const apiDatosFiscales = @json($apiDatosFiscales);
        const apiEmpresas = @json($apiEmpresas);
        const apiFacturasBeca = @json($apiFacturasBeca);
        const urlVerFactura = @json($urlVerFactura);
        const empresaIdUsuario = @json($empresaIdUsuario);
        const params = new URLSearchParams(window.location.search);
        const empresaId = params.get('empresa_id') || empresaIdUsuario;
        const mesParam = params.get('mes');
        const anioParam = params.get('anio');

        const MESES = [
            'Enero','Febrero','Marzo','Abril','Mayo','Junio',
            'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
        ];
        const hoy = new Date();
        var mesActual = mesParam ? parseInt(mesParam) : (hoy.getMonth() + 1);
        var anioActual = anioParam ? parseInt(anioParam) : hoy.getFullYear();

        var btnRegresar = document.getElementById('btnRegresarPagos');
        if (btnRegresar && empresaId) {
            btnRegresar.href = '{{ url("/Gestion_alumnos/calculo-pagos") }}' +
                '?empresa_id=' + encodeURIComponent(empresaId) +
                '&mes=' + encodeURIComponent(mesActual) +
                '&anio=' + encodeURIComponent(anioActual);
        }

        var subtituloEl = document.getElementById('subtituloFactura');
        var receptorNombreEl = document.getElementById('receptorNombre');
        var tablaConceptos = document.getElementById('tablaConceptos');
        var statEmpresa = document.getElementById('statEmpresaFactura');
        var statPeriodo = document.getElementById('statPeriodoFactura');
        var statTotal = document.getElementById('statTotalFactura');
        statPeriodo.textContent = MESES[mesActual - 1] + ' ' + anioActual;

        function escapeHtml(txt) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(txt || ''));
            return d.innerHTML;
        }

        function formatMoney(n) {
            return n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        var checkSinIva = document.getElementById('checkSinIva');
        var filaIva = document.getElementById('filaIva');

        function actualizarTotales() {
            var subtotal = 0;
            var filas = tablaConceptos.querySelectorAll('tr');
            filas.forEach(function (tr) {
                var importeInput = tr.querySelector('.concepto-importe');
                if (importeInput) subtotal += parseFloat(importeInput.value) || 0;
            });

            var sinIva = checkSinIva && checkSinIva.checked;
            var iva = sinIva ? 0 : Math.round(subtotal * 0.16 * 100) / 100;
            var total = Math.round((subtotal + iva) * 100) / 100;

            document.getElementById('subtotal').textContent = formatMoney(subtotal);
            document.getElementById('iva').textContent = formatMoney(iva);
            document.getElementById('total').textContent = formatMoney(total);
            statTotal.textContent = '$' + formatMoney(total);

            if (filaIva) {
                filaIva.style.display = sinIva ? 'none' : '';
            }
        }

        if (checkSinIva) {
            checkSinIva.addEventListener('change', function () {
                actualizarTotales();
            });
        }

        function calcularImporteFila(tr) {
            var cant = parseFloat(tr.querySelector('.concepto-cantidad').value) || 0;
            var precio = parseFloat(tr.querySelector('.concepto-precio').value) || 0;
            var importe = Math.round(cant * precio * 100) / 100;
            tr.querySelector('.concepto-importe').value = importe.toFixed(2);
            actualizarTotales();
        }

        function agregarFilaConcepto(descripcion, cantidad, precio) {
            descripcion = descripcion || '';
            cantidad = cantidad || 1;
            precio = precio || 0;
            var importe = Math.round(cantidad * precio * 100) / 100;

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><input type="text" class="form-control form-control-sm concepto-clave" placeholder="Clave producto SAT" value="80141600"></td>' +
                '<td><input type="number" class="form-control form-control-sm concepto-cantidad" value="' + cantidad + '" min="1" step="1"></td>' +
                '<td><select class="form-select form-select-sm concepto-unidad">' +
                    '<option value="E48" selected>E48 - Unidad de servicio</option>' +
                    '<option value="H87">H87 - Pieza</option>' +
                    '<option value="EA">EA - Elemento</option>' +
                    '<option value="ACT">ACT - Actividad</option>' +
                '</select></td>' +
                '<td><input type="text" class="form-control form-control-sm concepto-descripcion" value="' + escapeHtml(descripcion) + '"></td>' +
                '<td><input type="number" class="form-control form-control-sm concepto-precio" value="' + precio.toFixed(2) + '" step="0.01" min="0"></td>' +
                '<td><input type="text" class="form-control form-control-sm concepto-importe" value="' + importe.toFixed(2) + '" readonly></td>' +
                '<td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-eliminar-concepto"><i class="fas fa-trash"></i></button></td>';
            tablaConceptos.appendChild(tr);

            tr.querySelector('.concepto-cantidad').addEventListener('change', function () { calcularImporteFila(tr); });
            tr.querySelector('.concepto-precio').addEventListener('change', function () { calcularImporteFila(tr); });

            actualizarTotales();
            return tr;
        }

        document.getElementById('btnAgregarConcepto').addEventListener('click', function () {
            agregarFilaConcepto('', 1, 0);
        });

        tablaConceptos.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-eliminar-concepto');
            if (btn) {
                btn.closest('tr').remove();
                actualizarTotales();
            }
        });

        // --- Cargar emisores desde datos fiscales ---
        var selectEmisor = document.getElementById('selectEmisor');
        var emisoresData = [];

        async function cargarEmisores() {
            try {
                var r = await fetch(apiDatosFiscales, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                var j = await r.json();
                emisoresData = (j.data || []).filter(function (e) { return e.estatus === 'ACTIVO'; });

                selectEmisor.innerHTML = '<option value="">Seleccionar empresa emisora...</option>';
                emisoresData.forEach(function (e) {
                    var opt = document.createElement('option');
                    opt.value = e.id;
                    opt.textContent = e.razon_social + ' (' + e.rfc + ')';
                    selectEmisor.appendChild(opt);
                });

                if (emisoresData.length === 1) {
                    selectEmisor.value = emisoresData[0].id;
                    mostrarDatosEmisor(emisoresData[0]);
                }
            } catch (err) {
                console.error('Error cargando emisores:', err);
                selectEmisor.innerHTML = '<option value="">Error al cargar emisores</option>';
            }
        }

        function mostrarDatosEmisor(e) {
            document.getElementById('emisorNombre').textContent = e.razon_social || '—';
            document.getElementById('emisorRfc').textContent = e.rfc || '—';

            var partes = [e.calle, e.numero_exterior ? ('No. ' + e.numero_exterior) : null,
                e.numero_interior ? ('Int. ' + e.numero_interior) : null,
                e.colonia, e.municipio, e.estado, e.codigo_postal ? ('C.P. ' + e.codigo_postal) : null,
                e.pais].filter(Boolean);
            document.getElementById('emisorDireccion').textContent = partes.join(', ') || '—';

            document.getElementById('emisorLugarExpedicion').textContent = e.codigo_postal || '—';

            var regimenTxt = e.regimen_fiscal || '';
            if (e.regimen_fiscal_descripcion) regimenTxt += ' - ' + e.regimen_fiscal_descripcion;
            document.getElementById('emisorRegimenFiscal').textContent = regimenTxt || '—';

            document.getElementById('emisorTelefono').textContent = e.telefono || '—';
        }

        selectEmisor.addEventListener('change', function () {
            var id = selectEmisor.value;
            if (!id) {
                document.getElementById('emisorNombre').textContent = '—';
                document.getElementById('emisorRfc').textContent = '—';
                document.getElementById('emisorDireccion').textContent = '—';
                document.getElementById('emisorLugarExpedicion').textContent = '—';
                document.getElementById('emisorRegimenFiscal').textContent = '—';
                document.getElementById('emisorTelefono').textContent = '—';
                return;
            }
            var emisor = emisoresData.find(function (e) { return String(e.id) === String(id); });
            if (emisor) mostrarDatosEmisor(emisor);
        });

        var receptorFiscalData = {};

        async function cargarDatosFiscalesReceptor() {
            if (!empresaId) return;
            try {
                var r = await fetch(apiEmpresas + '/' + encodeURIComponent(empresaId), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                var j = await r.json();
                var e = j.data || {};
                if (e.nombre && (!receptorNombreEl.textContent || receptorNombreEl.textContent === '—' || receptorNombreEl.textContent === 'Empresa #' + empresaId)) {
                    receptorNombreEl.textContent = e.nombre;
                    statEmpresa.textContent = e.nombre;
                }

                receptorFiscalData = {
                    calle: e.calle || '',
                    numero_exterior: e.numero_exterior || '',
                    numero_interior: e.numero_interior || '',
                    colonia: e.colonia || '',
                    municipio: e.municipio || '',
                    estado: e.estado || ''
                };

                document.getElementById('receptorRazonSocial').textContent = e.razon_social || '—';

                if (e.rfc) document.getElementById('receptorRfc').value = e.rfc;
                if (e.codigo_postal) document.getElementById('receptorCodigoPostal').value = e.codigo_postal;
                if (e.correo_fiscal) {
                    document.getElementById('receptorCorreoFiscal').textContent = e.correo_fiscal;
                }

                var partes = [e.calle, e.numero_exterior ? ('No. ' + e.numero_exterior) : null,
                    e.numero_interior ? ('Int. ' + e.numero_interior) : null,
                    e.colonia, e.municipio, e.estado,
                    e.codigo_postal ? ('C.P. ' + e.codigo_postal) : null].filter(Boolean);
                document.getElementById('receptorDireccion').textContent = partes.join(', ') || e.direccion || '—';

                if (e.uso_cfdi) {
                    var selectCfdi = document.getElementById('receptorUsoCfdi');
                    for (var i = 0; i < selectCfdi.options.length; i++) {
                        if (selectCfdi.options[i].value === e.uso_cfdi) {
                            selectCfdi.value = e.uso_cfdi;
                            break;
                        }
                    }
                }
                if (e.regimen_fiscal) {
                    var selectRegimen = document.getElementById('receptorRegimenFiscal');
                    for (var i = 0; i < selectRegimen.options.length; i++) {
                        if (selectRegimen.options[i].value === e.regimen_fiscal) {
                            selectRegimen.value = e.regimen_fiscal;
                            break;
                        }
                    }
                }
            } catch (err) {
                console.error('Error cargando datos fiscales del receptor:', err);
            }
        }

        // Cargar datos de empresa y generar concepto automático
        async function inicializar() {
            if (!empresaId) return;

            try {
                var r = await fetch(apiBase + '?estado=activo', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                var j = await r.json();
                var todos = j.data || [];
                var alumnosEmpresa = todos.filter(function (a) { return String(a.empresa_id) === String(empresaId); });

                var empresaNombre = alumnosEmpresa.length > 0 ? (alumnosEmpresa[0].empresa_nombre || 'Empresa') : 'Empresa #' + empresaId;
                receptorNombreEl.textContent = empresaNombre;
                subtituloEl.textContent = 'Factura para ' + empresaNombre + ' — ' + MESES[mesActual - 1] + ' ' + anioActual;
                statEmpresa.textContent = empresaNombre;

                await cargarDatosFiscalesReceptor();

                // Cargar asistencias para calcular total
                var url = apiAsistencias + '?empresa_id=' + encodeURIComponent(empresaId) +
                    '&mes=' + encodeURIComponent(mesActual) + '&anio=' + encodeURIComponent(anioActual);
                var rAsis = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                var jAsis = await rAsis.json();
                var asistenciaData = jAsis.data || {};

                var P = 5350;
                var Dm = 30.4;
                var totalPago = 0;
                var totalAlumnos = alumnosEmpresa.length;

                alumnosEmpresa.forEach(function (alumno) {
                    var aId = String(alumno.alumno_id);
                    var datos = asistenciaData[aId] || {};
                    var contF = 0;
                    Object.values(datos).forEach(function (v) { if (v === 'F') contF++; });
                    var di = Dm - contF;
                    if (di < 0) di = 0;
                    var pago = contF === 0 ? P : ((P / Dm) * di);
                    totalPago += Math.round(pago * 100) / 100;
                });

                var descripcionConcepto = 'CUOTA EXTRAORDINARIA, PROGRAMA DE CAPACITACION DEL MES DE ' + MESES[mesActual - 1].toUpperCase();
                agregarFilaConcepto(descripcionConcepto, 1, Math.round(totalPago * 100) / 100);

                document.getElementById('btnTimbrar').disabled = false;

            } catch (err) {
                console.error('Error inicializando factura:', err);
            }
        }

        document.getElementById('btnTimbrar').addEventListener('click', async function () {
            if (!selectEmisor.value) {
                alert('Selecciona una empresa emisora antes de timbrar.');
                return;
            }

            var emisor = emisoresData.find(function (e) { return String(e.id) === String(selectEmisor.value); });
            if (!emisor) {
                alert('No se encontraron datos del emisor seleccionado.');
                return;
            }

            var rfc = document.getElementById('receptorRfc').value.trim();
            if (!rfc) {
                alert('El RFC del receptor es obligatorio.');
                return;
            }

            var conceptos = [];
            tablaConceptos.querySelectorAll('tr').forEach(function (tr) {
                conceptos.push({
                    producto: (tr.querySelector('.concepto-clave') || {}).value || '',
                    cantidad: (tr.querySelector('.concepto-cantidad') || {}).value || '0',
                    unidad: (tr.querySelector('.concepto-unidad') || {}).value || '',
                    concepto: (tr.querySelector('.concepto-descripcion') || {}).value || '',
                    precio: (tr.querySelector('.concepto-precio') || {}).value || '0',
                    importe: (tr.querySelector('.concepto-importe') || {}).value || '0'
                });
            });

            if (conceptos.length === 0) {
                alert('Agrega al menos un concepto para timbrar.');
                return;
            }

            var regimenEmisor = emisor.regimen_fiscal || '';
            if (emisor.regimen_fiscal_descripcion) regimenEmisor += ' - ' + emisor.regimen_fiscal_descripcion;

            var receptorDirEl = document.getElementById('receptorDireccion');
            var receptorRazonEl = document.getElementById('receptorRazonSocial');

            var payload = {
                _token: '{{ csrf_token() }}',
                emisor_nombre: emisor.razon_social || '',
                emisor_rfc: emisor.rfc || '',
                emisor_lugar_expedicion: emisor.codigo_postal || '',
                emisor_regimen_fiscal: regimenEmisor,
                receptor_nombre: document.getElementById('receptorNombre').textContent.trim(),
                receptor_razon_social: receptorRazonEl ? receptorRazonEl.textContent.trim() : '',
                receptor_rfc: rfc,
                receptor_codigo_postal: document.getElementById('receptorCodigoPostal').value.trim(),
                receptor_uso_cfdi: document.getElementById('receptorUsoCfdi').value,
                receptor_regimen_fiscal: document.getElementById('receptorRegimenFiscal').value,
                receptor_direccion: receptorDirEl ? receptorDirEl.textContent.trim() : '',
                receptor_calle: receptorFiscalData.calle || '',
                receptor_numero_exterior: receptorFiscalData.numero_exterior || '',
                receptor_numero_interior: receptorFiscalData.numero_interior || '',
                receptor_colonia: receptorFiscalData.colonia || '',
                receptor_municipio: receptorFiscalData.municipio || '',
                receptor_estado: receptorFiscalData.estado || '',
                forma_pago: document.getElementById('formaPago').value,
                metodo_pago: document.getElementById('metodoPago').value,
                sin_iva: checkSinIva ? checkSinIva.checked : false,
                empresa_id: empresaId,
                mes: mesActual,
                anio: anioActual,
                conceptos: JSON.stringify(conceptos)
            };

            var btnTimbrar = this;
            btnTimbrar.disabled = true;
            btnTimbrar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Timbrando...';

            try {
                var formData = new FormData();
                Object.keys(payload).forEach(function (key) {
                    formData.append(key, payload[key]);
                });

                var resp = await fetch('{{ route("procesar.factura.beca") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                var responseText = await resp.text();
                console.log('Response status:', resp.status, 'Body:', responseText);

                if (!resp.ok) {
                    alert('Error HTTP ' + resp.status + ': ' + responseText.substring(0, 300));
                    return;
                }

                var result = JSON.parse(responseText);

                if (result.success) {
                    alert('Factura timbrada exitosamente. Folio: ' + (result.folio || ''));
                    cargarHistorialFacturas();
                    if (result.redirect_url) {
                        window.open(result.redirect_url, '_blank');
                    }
                } else {
                    alert('Error: ' + (result.message || 'Error desconocido al timbrar.'));
                }
            } catch (err) {
                console.error('Error al timbrar factura beca:', err);
                alert('Error de conexión al timbrar la factura: ' + err.message);
            } finally {
                btnTimbrar.disabled = false;
                btnTimbrar.innerHTML = '<i class="fa-solid fa-stamp"></i> Timbrar Factura';
            }
        });

        // --- Historial de facturas timbradas ---
        var tbodyFacturas = document.getElementById('tbodyFacturas');
        var tablaFacturas = document.getElementById('tablaFacturas');
        var cargandoFacturas = document.getElementById('cargandoFacturas');
        var sinFacturas = document.getElementById('sinFacturas');

        function formatMoneyHist(n) {
            var num = parseFloat(n) || 0;
            return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatFecha(str) {
            if (!str) return '—';
            var d = new Date(str);
            if (isNaN(d)) return str;
            var dd = String(d.getDate()).padStart(2, '0');
            var mm = String(d.getMonth() + 1).padStart(2, '0');
            var yy = d.getFullYear();
            var hh = String(d.getHours()).padStart(2, '0');
            var mi = String(d.getMinutes()).padStart(2, '0');
            return dd + '/' + mm + '/' + yy + ' ' + hh + ':' + mi;
        }

        async function cargarHistorialFacturas() {
            cargandoFacturas.classList.remove('d-none');
            tablaFacturas.classList.add('d-none');
            sinFacturas.classList.add('d-none');
            tbodyFacturas.innerHTML = '';

            try {
                var url = apiFacturasBeca + '?empresa_id=' + encodeURIComponent(empresaId) +
                    '&mes=' + encodeURIComponent(mesActual) + '&anio=' + encodeURIComponent(anioActual);
                var r = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                var j = await r.json();
                var facturas = j.data || [];

                cargandoFacturas.classList.add('d-none');

                if (facturas.length === 0) {
                    sinFacturas.classList.remove('d-none');
                    return;
                }

                tablaFacturas.classList.remove('d-none');

                facturas.forEach(function (f) {
                    var uuidCorto = f.uuid ? f.uuid.substring(0, 8) + '...' : '—';
                    var estadoBadge = f.estado === 'Activo'
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-secondary">' + escapeHtml(f.estado || '—') + '</span>';

                    var btnPdf = '';
                    if (f.facturama_id) {
                        btnPdf = '<a href="' + urlVerFactura + '/' + encodeURIComponent(f.facturama_id) + '" target="_blank" class="btn btn-danger btn-sm" title="Ver PDF">' +
                            '<i class="fa-solid fa-file-pdf"></i> PDF</a>';
                    }

                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td><strong>' + escapeHtml(f.folio || '—') + '</strong></td>' +
                        '<td class="text-nowrap">' + formatFecha(f.fecha_timbrado || f.created_at) + '</td>' +
                        '<td>' + escapeHtml(f.receptor_nombre || '—') + '</td>' +
                        '<td>' + escapeHtml(f.receptor_rfc || '—') + '</td>' +
                        '<td class="text-end">$' + formatMoneyHist(f.subtotal) + '</td>' +
                        '<td class="text-end">$' + formatMoneyHist(f.iva) + '</td>' +
                        '<td class="text-end"><strong>$' + formatMoneyHist(f.total) + '</strong></td>' +
                        '<td title="' + escapeHtml(f.uuid || '') + '">' + uuidCorto + '</td>' +
                        '<td class="text-center">' + estadoBadge + '</td>' +
                        '<td class="text-center">' + btnPdf + '</td>';
                    tbodyFacturas.appendChild(tr);
                });
            } catch (err) {
                console.error('Error cargando historial facturas:', err);
                cargandoFacturas.classList.add('d-none');
                sinFacturas.textContent = 'Error al cargar facturas.';
                sinFacturas.classList.remove('d-none');
            }
        }

        document.getElementById('btnRefrescarFacturas').addEventListener('click', function () {
            cargarHistorialFacturas();
        });

        cargarEmisores();
        inicializar();
        cargarHistorialFacturas();
    });
</script>
@endsection
