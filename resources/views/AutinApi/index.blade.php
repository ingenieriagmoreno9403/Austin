@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .sap-wrap { max-width: 1280px; margin: 0 auto; }
    .sap-status {
        display: inline-flex; align-items: center; gap: .5rem;
        padding: .4rem .85rem; border-radius: 999px; font-size: .85rem; font-weight: 600;
    }
    .sap-status.ok { background: #ecfdf5; color: #047857; }
    .sap-status.err { background: #fef2f2; color: #b91c1c; }
    .sap-panel {
        background: #fff;
        border: 1px solid rgba(17, 24, 39, .09);
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(17, 24, 39, .06);
        padding: 1.25rem;
    }
    .sap-method {
        display: inline-block; min-width: 3.2rem; text-align: center;
        font-size: .7rem; font-weight: 800; letter-spacing: .04em;
        padding: .2rem .45rem; border-radius: 6px; background: #ecfdf5; color: #047857;
    }
    .sap-badge {
        display: inline-block; font-size: .68rem; font-weight: 700; letter-spacing: .03em;
        padding: .15rem .5rem; border-radius: 999px; background: #eff6ff; color: #1d4ed8;
        text-transform: uppercase;
    }
    .sap-badge.global { background: #fdf4ff; color: #a21caf; }
    .sap-path { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .82rem; word-break: break-all; }
    .sap-table-wrap { overflow-x: auto; max-height: 62vh; }
    .sap-table thead th {
        position: sticky; top: 0; background: #f8fafc; z-index: 1;
        font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; color: #64748b;
    }
    .sap-meta { font-size: .85rem; color: #64748b; }
    .sap-ep-row:hover { background: #f8fafc; }
    .sap-copy {
        border: 0; background: #f1f5f9; color: #334155; border-radius: 8px;
        padding: .2rem .55rem; font-size: .75rem; cursor: pointer;
    }
    .sap-copy:hover { background: #e2e8f0; }
    .sap-search { position: relative; flex: 1 1 280px; min-width: 240px; max-width: 440px; }
    .sap-search i {
        position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
        color: #94a3b8; pointer-events: none;
    }
    .sap-search input { padding-left: 2.15rem; }
    mark.sap-hit { background: #fef08a; color: inherit; padding: 0 .12rem; border-radius: 3px; }
    .sap-totals {
        margin-top: .9rem; padding: .9rem 1rem; border-radius: 12px;
        background: #f8fafc; border: 1px solid rgba(17, 24, 39, .08);
    }
    .sap-totals-grid { display: flex; flex-wrap: wrap; gap: .55rem; }
    .sap-total-chip {
        background: #fff; border: 1px solid rgba(17, 24, 39, .08);
        border-radius: 10px; padding: .4rem .7rem; min-width: 9rem;
    }
    .sap-total-chip .k {
        display: block; font-size: .68rem; font-weight: 700; letter-spacing: .03em;
        text-transform: uppercase; color: #64748b;
    }
    .sap-total-chip .v {
        display: block; font-weight: 700; color: #0f172a;
        font-variant-numeric: tabular-nums;
    }
</style>

<div class="container-fluid sap-wrap">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-plug"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">AutinApi / Endpoints SAP</h2>
                        <p class="text-muted mb-0">Consulta catálogos del servidor real · proxy interno en Sistemas</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="/Panel" class="btn btn-baseColor-light fs-7 mb-0">
                        <i class="fa-solid fa-arrow-left me-1"></i> Sistemas
                    </a>
                    @if(!empty($health['ok']))
                        <span class="sap-status ok"><i class="fas fa-check-circle"></i> API conectada</span>
                    @else
                        <span class="sap-status err"><i class="fas fa-exclamation-triangle"></i> {{ $health['message'] ?? 'Sin conexión a AutinApi' }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="sap-panel mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="sap-meta mb-1">Servidor remoto (AutinApi)</div>
                <div class="sap-path">{{ $apiBaseUrl }}</div>
            </div>
            <div class="col-md-6">
                <div class="sap-meta mb-1">Proxy local (este ERP)</div>
                <div class="sap-path">{{ $proxyBaseUrl }}</div>
            </div>
            <div class="col-12">
                <div class="sap-meta">
                    Empresas: {{ strtoupper(implode(', ', $databases)) }} ·
                    Auth: Basic (credenciales en servidor, no en el navegador)
                </div>
            </div>
        </div>
    </div>

    <div class="sap-panel mb-4">
        <h5 class="mb-3"><i class="fas fa-list me-2"></i>Endpoints disponibles</h5>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:90px">Grupo</th>
                        <th style="width:70px">Método</th>
                        <th>Ruta</th>
                        <th>Descripción</th>
                        <th style="width:100px">Probar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($endpoints as $ep)
                        <tr class="sap-ep-row">
                            <td>
                                <span class="sap-badge {{ str_contains($ep['grupo'], 'Global') ? 'global' : '' }}">
                                    {{ str_contains($ep['grupo'], 'Global') ? 'Global' : (str_contains($ep['grupo'], 'Por') ? 'Empresa' : 'General') }}
                                </span>
                            </td>
                            <td><span class="sap-method">{{ $ep['method'] }}</span></td>
                            <td>
                                <div class="sap-path mb-1">{{ $ep['path'] }}</div>
                                <div class="sap-meta">Remoto: {{ $ep['remoto'] }}</div>
                                <div class="sap-meta">Proxy: {{ $ep['proxy'] }}</div>
                            </td>
                            <td class="sap-meta">{{ $ep['descripcion'] }}</td>
                            <td>
                                <button type="button" class="sap-copy me-1" data-url="{{ $ep['proxy'] }}" title="Copiar proxy">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <a href="{{ $ep['proxy'] }}" target="_blank" class="sap-copy text-decoration-none" title="Abrir proxy">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="sap-meta mt-3 mb-0">
            <strong>Global:</strong> <code>/cuentas</code> · <code>/centros-costo</code> · <code>/gasto-real</code> · <code>/ventas</code>
            &nbsp;|&nbsp;
            <strong>Por empresa:</strong> <code>{database}</code> = austin | imsa | pitic | sydney ·
            <code>{resource}</code> = centros-costo | cuentas | agrupaciones-cuentas | transacciones
        </p>
    </div>

    <div class="sap-panel mb-4">
        <h5 class="mb-3"><i class="fas fa-search me-2"></i>Explorar catálogo</h5>
        <form id="sapFilterForm" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Alcance</label>
                <select id="sapScope" class="form-select">
                    <option value="global">Global (todas las empresas)</option>
                    <option value="empresa">Por empresa</option>
                </select>
            </div>
            <div class="col-md-3" id="sapDatabaseWrap" style="display:none;">
                <label class="form-label fw-semibold">Empresa (ruta)</label>
                <select id="sapDatabase" class="form-select" name="database">
                    @foreach($databases as $db)
                        <option value="{{ $db }}" {{ $db === $defaultDb ? 'selected' : '' }}>{{ strtoupper($db) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Catálogo</label>
                <select id="sapResource" class="form-select" name="resource"></select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold">Por página</label>
                <select id="sapPerPage" class="form-select" name="per_page">
                    <option value="25">25</option>
                    <option value="50" selected>50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
            </div>

            <div class="col-md-2" id="sapGenericCodeWrap">
                <label class="form-label fw-semibold">Filtro (código)</label>
                <input type="text" id="sapFilterCode" class="form-control" placeholder="Código">
            </div>
            <div class="col-md-2" id="sapGenericNameWrap">
                <label class="form-label fw-semibold">Filtro (nombre)</label>
                <input type="text" id="sapFilterName" class="form-control" placeholder="Nombre">
            </div>
            <div class="col-md-2" id="sapEmpresaFilterWrap">
                <label class="form-label fw-semibold">Filtro Empresa</label>
                <select id="sapFilterEmpresa" class="form-select">
                    <option value="">Todas</option>
                    @foreach($databases as $db)
                        <option value="{{ strtoupper($db) }}">{{ strtoupper($db) }}</option>
                    @endforeach
                </select>
            </div>

            <div id="sapGastoRealFilters" class="col-12" style="display:none;">
                <div class="border rounded-3 p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <strong class="text-marino"><i class="fas fa-coins me-1"></i> Filtros gasto-real</strong>
                        <span class="sap-meta mb-0">Opcionales · proxy <code>/Sistemas/AutinApi/gasto-real</code></span>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Empresa</label>
                            <select id="gastoEmpresa" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($databases as $db)
                                    <option value="{{ strtoupper($db) }}">{{ strtoupper($db) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">CC</label>
                            <input type="text" id="gastoCC" class="form-control form-control-sm" placeholder="04">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Cuenta</label>
                            <input type="text" id="gastoCuenta" class="form-control form-control-sm" placeholder="6000">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">DescCuenta</label>
                            <input type="text" id="gastoDescCuenta" class="form-control form-control-sm" placeholder="ARRENDAMIENTO">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small mb-1">year</label>
                            <input type="number" id="gastoYear" class="form-control form-control-sm" placeholder="2026" min="2000" max="2100">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">GroupMask</label>
                            <input type="text" id="gastoGroupMask" class="form-control form-control-sm" placeholder="6" value="6">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">DEPTO</label>
                            <input type="text" id="gastoDepto" class="form-control form-control-sm" placeholder="AdminExp">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">fecha_desde</label>
                            <input type="date" id="gastoFechaDesde" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">fecha_hasta</label>
                            <input type="date" id="gastoFechaHasta" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="sap-meta">
                                Ejemplos:
                                <code>?Empresa=IMSA&amp;per_page=50</code> ·
                                <code>?Empresa=AUSTIN&amp;CC=04&amp;year=2026</code> ·
                                <code>?DEPTO=AdminExp</code>
                                <br>Campos: CC, DescripcionCC, <strong>DEPTO</strong> (ej. MfgOverhead, AdminExp)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sapVentasFilters" class="col-12" style="display:none;">
                <div class="border rounded-3 p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <strong class="text-marino"><i class="fas fa-file-invoice-dollar me-1"></i> Filtros ventas</strong>
                        <span class="sap-meta mb-0">Opcionales · proxy <code>/Sistemas/AutinApi/ventas</code></span>
                    </div>
                    <p class="sap-meta mb-2">
                        Facturas (<code>VENTA</code>) y notas de crédito (<code>NC</code>).
                        En IMSA, clientes con CardCode que inicia en <code>P</code> aparecen como empresa <code>BACHIMBA</code>.
                    </p>
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Empresa</label>
                            <select id="ventasEmpresa" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($databases as $db)
                                    <option value="{{ strtoupper($db) }}">{{ strtoupper($db) }}</option>
                                @endforeach
                                <option value="BACHIMBA">BACHIMBA</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Tipo_Doc</label>
                            <select id="ventasTipoDoc" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="VENTA">VENTA</option>
                                <option value="NC">NC</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small mb-1">year</label>
                            <input type="number" id="ventasYear" class="form-control form-control-sm" placeholder="2026" min="2000" max="2100">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">ItemCode</label>
                            <input type="text" id="ventasItemCode" class="form-control form-control-sm" placeholder="AUSCA">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">CardCode</label>
                            <input type="text" id="ventasCardCode" class="form-control form-control-sm" placeholder="A005">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">CardName</label>
                            <input type="text" id="ventasCardName" class="form-control form-control-sm" placeholder="MINERA">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">U_LINEA_QV</label>
                            <input type="text" id="ventasLinea" class="form-control form-control-sm" placeholder="ANFO">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">fecha_desde</label>
                            <input type="date" id="ventasFechaDesde" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">fecha_hasta</label>
                            <input type="date" id="ventasFechaHasta" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="sap-meta">
                                Ejemplos:
                                <code>?Empresa=IMSA&amp;per_page=50</code> ·
                                <code>?Empresa=AUSTIN&amp;Tipo_Doc=NC</code> ·
                                <code>?Empresa=BACHIMBA</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sapListasPreciosFilters" class="col-12" style="display:none;">
                <div class="border rounded-3 p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <strong class="text-marino"><i class="fas fa-tags me-1"></i> Filtros listas de precios</strong>
                        <span class="sap-meta mb-0">Proxy <code>/Sistemas/AutinApi/listas-precios</code></span>
                    </div>
                    <p class="sap-meta mb-2">
                        Consulta listas de precios SAP por empresa y cliente.
                        Ejemplo: <code>?Empresa=IMSA&amp;CodigoCliente=D136&amp;per_page=100</code>
                    </p>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Empresa</label>
                            <select id="lpEmpresa" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($databases as $db)
                                    <option value="{{ strtoupper($db) }}">{{ strtoupper($db) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">CodigoCliente</label>
                            <input type="text" id="lpCodigoCliente" class="form-control form-control-sm" placeholder="D136">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="sap-meta">
                                Usa el <em>por página</em> de arriba. Ejemplo local:
                                <code>/Sistemas/AutinApi/listas-precios?Empresa=IMSA&amp;CodigoCliente=D136&amp;per_page=100</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary" id="sapBtnLoad">
                    <i class="fas fa-search me-1"></i> Consultar
                </button>
                <button type="button" class="btn btn-outline-secondary" id="sapBtnHealth">
                    <i class="fas fa-heartbeat me-1"></i> Probar health
                </button>
                <button type="button" class="btn btn-outline-primary" id="sapBtnGastoQuick">
                    <i class="fas fa-bolt me-1"></i> Abrir gasto-real
                </button>
                <button type="button" class="btn btn-outline-primary" id="sapBtnVentasQuick">
                    <i class="fas fa-file-invoice-dollar me-1"></i> Abrir ventas
                </button>
                <button type="button" class="btn btn-outline-primary" id="sapBtnListasPreciosQuick">
                    <i class="fas fa-tags me-1"></i> Abrir listas-precios
                </button>
            </div>
        </form>
    </div>

    <div class="sap-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 class="mb-0" id="sapTitle">Resultados</h5>
                <div class="sap-meta" id="sapMeta">Selecciona alcance y catálogo, luego consulta.</div>
            </div>
            <div class="sap-search">
                <i class="fas fa-search"></i>
                <input type="search" id="sapSearch" class="form-control form-control-sm" placeholder="Buscar coincidencias en los resultados" autocomplete="off" disabled>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="sapPrev" disabled>Anterior</button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="sapNext" disabled>Siguiente</button>
            </div>
        </div>
        <div class="sap-table-wrap">
            <table class="table table-sm table-hover sap-table mb-0" id="sapTable">
                <thead><tr id="sapThead"></tr></thead>
                <tbody id="sapTbody">
                    <tr><td class="text-muted py-4">Sin datos cargados.</td></tr>
                </tbody>
            </table>
        </div>
        <div id="sapTotals" class="sap-totals" hidden></div>
    </div>
</div>

<script>
(function () {
    const routes = {
        health: @json(route('autin-api.health')),
        base: @json(url('/Sistemas/AutinApi'))
    };

    const resourcesEmpresa = @json($resources);
    const resourcesGlobal = @json($globalResources);

    let currentPage = 1;
    let loadedRows = [];
    let loadedKeys = [];

    const SUM_NAME = /(importe|monto|amount|debit|credit|qty|quantity|cantidad|precio|price|costo|cost|saldo|total|tax|iva|descuento|discount|peso)/i;
    const SKIP_SUM = /(code|codigo|cuenta|fecha|date|year|anio|mask|empresa|nombre|name|desc|estatus|status|tipo|depto|prc|format|card|item|docnum|docentry|linenum|linea|^id$|_id$)/i;

    function isGastoReal() {
        return document.getElementById('sapScope').value === 'global'
            && document.getElementById('sapResource').value === 'gasto-real';
    }

    function isVentas() {
        return document.getElementById('sapScope').value === 'global'
            && document.getElementById('sapResource').value === 'ventas';
    }

    function isListasPrecios() {
        return document.getElementById('sapScope').value === 'global'
            && document.getElementById('sapResource').value === 'listas-precios';
    }

    function toggleFilters() {
        const gasto = isGastoReal();
        const ventas = isVentas();
        const listas = isListasPrecios();
        const especial = gasto || ventas || listas;

        document.getElementById('sapGastoRealFilters').style.display = gasto ? '' : 'none';
        document.getElementById('sapVentasFilters').style.display = ventas ? '' : 'none';
        document.getElementById('sapListasPreciosFilters').style.display = listas ? '' : 'none';
        document.getElementById('sapGenericCodeWrap').style.display = especial ? 'none' : '';
        document.getElementById('sapGenericNameWrap').style.display = especial ? 'none' : '';

        const scope = document.getElementById('sapScope').value;
        document.getElementById('sapEmpresaFilterWrap').style.display = (scope === 'global' && !especial) ? '' : 'none';
        document.getElementById('sapDatabaseWrap').style.display = scope === 'empresa' ? '' : 'none';
    }

    function fillResources() {
        const scope = document.getElementById('sapScope').value;
        const select = document.getElementById('sapResource');
        const map = scope === 'global' ? resourcesGlobal : resourcesEmpresa;
        select.innerHTML = Object.keys(map).map(function (key) {
            return '<option value="' + key + '">' + map[key] + '</option>';
        }).join('');
        toggleFilters();
    }

    document.getElementById('sapScope').addEventListener('change', fillResources);
    document.getElementById('sapResource').addEventListener('change', toggleFilters);
    fillResources();

    document.querySelectorAll('.sap-copy[data-url]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const url = btn.getAttribute('data-url');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    setTimeout(function () { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 1200);
                });
            } else {
                prompt('Copia la URL:', url);
            }
        });
    });

    function setParam(params, key, value) {
        if (value !== null && value !== undefined && String(value).trim() !== '') {
            params.set(key, String(value).trim());
        }
    }

    /** Convierte YYYY-MM-DD (input date) a YYYY/MM/DD (API ventas). */
    function toSlashDate(value) {
        if (!value) return '';
        return String(value).replace(/-/g, '/');
    }

    function buildRequest() {
        const scope = document.getElementById('sapScope').value;
        const resource = document.getElementById('sapResource').value;
        const perPage = document.getElementById('sapPerPage').value;
        const code = document.getElementById('sapFilterCode').value.trim();
        const name = document.getElementById('sapFilterName').value.trim();
        const empresaFiltro = document.getElementById('sapFilterEmpresa').value;

        const params = new URLSearchParams();
        params.set('per_page', perPage);
        params.set('page', String(currentPage));

        let url;
        if (scope === 'global') {
            url = routes.base + '/' + resource;

            if (resource === 'gasto-real') {
                setParam(params, 'Empresa', document.getElementById('gastoEmpresa').value);
                setParam(params, 'CC', document.getElementById('gastoCC').value);
                setParam(params, 'Cuenta', document.getElementById('gastoCuenta').value);
                setParam(params, 'DescCuenta', document.getElementById('gastoDescCuenta').value);
                setParam(params, 'year', document.getElementById('gastoYear').value);
                setParam(params, 'GroupMask', document.getElementById('gastoGroupMask').value);
                setParam(params, 'DEPTO', document.getElementById('gastoDepto').value);
                setParam(params, 'fecha_desde', document.getElementById('gastoFechaDesde').value);
                setParam(params, 'fecha_hasta', document.getElementById('gastoFechaHasta').value);
            } else if (resource === 'ventas') {
                setParam(params, 'Empresa', document.getElementById('ventasEmpresa').value);
                setParam(params, 'Tipo_Doc', document.getElementById('ventasTipoDoc').value);
                setParam(params, 'year', document.getElementById('ventasYear').value);
                setParam(params, 'ItemCode', document.getElementById('ventasItemCode').value);
                setParam(params, 'CardCode', document.getElementById('ventasCardCode').value);
                setParam(params, 'CardName', document.getElementById('ventasCardName').value);
                setParam(params, 'U_LINEA_QV', document.getElementById('ventasLinea').value);
                setParam(params, 'fecha_desde', toSlashDate(document.getElementById('ventasFechaDesde').value));
                setParam(params, 'fecha_hasta', toSlashDate(document.getElementById('ventasFechaHasta').value));
            } else if (resource === 'listas-precios') {
                setParam(params, 'Empresa', document.getElementById('lpEmpresa').value);
                setParam(params, 'CodigoCliente', document.getElementById('lpCodigoCliente').value);
            } else {
                if (empresaFiltro) params.set('Empresa', empresaFiltro);
                if (resource === 'centros-costo') {
                    if (code) params.set('PrcCode', code);
                    if (name) params.set('PrcName', name);
                } else if (resource === 'cuentas') {
                    if (code) params.set('FormatCode', code);
                    if (name) params.set('AcctName', name);
                }
            }
        } else {
            const db = document.getElementById('sapDatabase').value;
            url = routes.base + '/' + db + '/' + resource;
            if (resource === 'centros-costo') {
                if (code) params.set('CC', code);
                if (name) params.set('NOMBRE', name);
            } else if (resource === 'cuentas') {
                if (code) params.set('CUENTA', code);
                if (name) params.set('NOMBRE', name);
            } else if (resource === 'agrupaciones-cuentas') {
                if (code) params.set('GroupMask', code);
            } else if (resource === 'transacciones') {
                if (code) params.set('CC', code);
                if (name) params.set('Cuenta', name);
            }
        }

        return { url: url + '?' + params.toString() };
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fold(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function highlight(value, query) {
        const raw = value === null || value === undefined ? '' : String(value);
        const safe = escapeHtml(raw);
        const q = String(query || '').trim();
        if (!q) return safe;
        const pattern = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return safe.replace(new RegExp('(' + pattern + ')', 'ig'), '<mark class="sap-hit">$1</mark>');
    }

    function parseAmount(value) {
        if (typeof value === 'number' && isFinite(value)) return value;
        if (value === null || value === undefined) return null;
        const raw = String(value).trim().replace(/[$\s]/g, '');
        if (raw === '') return null;
        const normalized = raw.replace(/,/g, '');
        if (!/^-?\d+(\.\d+)?$/.test(normalized)) return null;
        return parseFloat(normalized);
    }

    function formatAmount(value) {
        return value.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function columnIsSummable(key, rows) {
        if (SKIP_SUM.test(key) && !SUM_NAME.test(key)) return false;
        if (/(code|codigo|^id$|_id$|fecha|date|year)/i.test(key)) return false;

        let filled = 0;
        let numeric = 0;
        let withDecimal = 0;
        rows.forEach(function (row) {
            const value = row[key];
            if (value === null || value === undefined || String(value).trim() === '') return;
            filled += 1;
            const amount = parseAmount(value);
            if (amount === null) return;
            numeric += 1;
            if (String(value).indexOf('.') !== -1) withDecimal += 1;
        });
        if (!filled || numeric !== filled) return false;
        if (SUM_NAME.test(key)) return true;
        return withDecimal > 0;
    }

    function visibleRows() {
        const query = fold(document.getElementById('sapSearch').value.trim());
        if (!query) return loadedRows.slice();
        return loadedRows.filter(function (row) {
            return loadedKeys.some(function (key) {
                return fold(row[key]).indexOf(query) !== -1;
            });
        });
    }

    function paintRows(rows, query) {
        const tbody = document.getElementById('sapTbody');
        if (!loadedRows.length) {
            tbody.innerHTML = '<tr><td class="text-muted py-4">Sin resultados.</td></tr>';
            return;
        }
        if (!rows.length) {
            tbody.innerHTML = '<tr><td class="text-muted py-4" colspan="' + loadedKeys.length + '">Sin coincidencias para «' + escapeHtml(query) + '».</td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(function (row) {
            return '<tr>' + loadedKeys.map(function (key) {
                return '<td>' + highlight(row[key], query) + '</td>';
            }).join('') + '</tr>';
        }).join('');
    }

    function paintTotals(rows) {
        const box = document.getElementById('sapTotals');
        const search = document.getElementById('sapSearch');
        if (!loadedRows.length || !loadedKeys.length) {
            box.hidden = true;
            box.innerHTML = '';
            search.disabled = true;
            return;
        }

        search.disabled = false;
        const summable = loadedKeys.filter(function (key) {
            return columnIsSummable(key, loadedRows);
        });
        const query = search.value.trim();
        const label = query
            ? (rows.length + ' coincidencias de ' + loadedRows.length + ' en esta página')
            : (loadedRows.length + ' registros en esta página');

        if (!summable.length) {
            box.hidden = false;
            box.innerHTML = '<div class="sap-meta mb-0">' + label + ' · esta consulta no trae columnas numéricas para totalizar.</div>';
            return;
        }

        const chips = summable.map(function (key) {
            const total = rows.reduce(function (sum, row) {
                const amount = parseAmount(row[key]);
                return sum + (amount === null ? 0 : amount);
            }, 0);
            return '<div class="sap-total-chip"><span class="k">' + escapeHtml(key) + '</span><span class="v">' + formatAmount(total) + '</span></div>';
        }).join('');

        box.hidden = false;
        box.innerHTML = '<div class="fw-semibold mb-2">Suma de totales · ' + label + '</div><div class="sap-totals-grid">' + chips + '</div>';
    }

    function applyResultView() {
        const query = document.getElementById('sapSearch').value.trim();
        const rows = visibleRows();
        paintRows(rows, query);
        paintTotals(rows);
    }

    function renderTable(payload) {
        const thead = document.getElementById('sapThead');
        const data = Array.isArray(payload.data) ? payload.data : [];
        const meta = payload.meta || {};
        const filters = payload.filters_applied || null;

        document.getElementById('sapTitle').textContent = payload.label || payload.resource || 'Resultados';

        let metaText = '';
        if (payload.empresa) metaText += payload.empresa + ' · ';
        metaText += 'página ' + (meta.current_page || 1) + ' de ' + (meta.last_page || 1)
            + ' · ' + (meta.total || data.length) + ' registros';
        if (filters) {
            const parts = Object.keys(filters).filter(function (k) {
                return filters[k] !== null && filters[k] !== '';
            }).map(function (k) {
                return k + '=' + filters[k];
            });
            if (parts.length) metaText += ' · filtros: ' + parts.join(', ');
        }
        document.getElementById('sapMeta').textContent = metaText;

        currentPage = meta.current_page || 1;
        document.getElementById('sapPrev').disabled = !meta.current_page || meta.current_page <= 1;
        document.getElementById('sapNext').disabled = !meta.last_page || meta.current_page >= meta.last_page;

        const search = document.getElementById('sapSearch');
        search.value = '';
        loadedRows = data;
        loadedKeys = data.length ? Object.keys(data[0]) : [];

        if (!data.length) {
            thead.innerHTML = '';
            applyResultView();
            return;
        }

        thead.innerHTML = loadedKeys.map(function (k) { return '<th>' + escapeHtml(k) + '</th>'; }).join('');
        applyResultView();
    }

    async function loadData() {
        const built = buildRequest();
        const btn = document.getElementById('sapBtnLoad');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cargando…';

        try {
            const res = await fetch(built.url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            if (!res.ok) {
                throw new Error(json.message || json.error || ('Error HTTP ' + res.status));
            }
            renderTable(json);
        } catch (err) {
            loadedRows = [];
            loadedKeys = [];
            document.getElementById('sapSearch').value = '';
            document.getElementById('sapSearch').disabled = true;
            document.getElementById('sapTotals').hidden = true;
            document.getElementById('sapTotals').innerHTML = '';
            document.getElementById('sapTbody').innerHTML =
                '<tr><td class="text-danger py-4">' + escapeHtml(err.message || 'Error al consultar') + '</td></tr>';
            document.getElementById('sapThead').innerHTML = '';
            document.getElementById('sapMeta').textContent = 'Error';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-1"></i> Consultar';
        }
    }

    document.getElementById('sapSearch').addEventListener('input', applyResultView);

    document.getElementById('sapFilterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        currentPage = 1;
        loadData();
    });

    document.getElementById('sapPrev').addEventListener('click', function () {
        if (currentPage > 1) {
            currentPage -= 1;
            loadData();
        }
    });

    document.getElementById('sapNext').addEventListener('click', function () {
        currentPage += 1;
        loadData();
    });

    document.getElementById('sapBtnHealth').addEventListener('click', async function () {
        try {
            const res = await fetch(routes.health, { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            alert(res.ok ? ('API OK:\n' + JSON.stringify(json, null, 2)) : ('Error:\n' + JSON.stringify(json, null, 2)));
        } catch (err) {
            alert('No se pudo contactar AutinApi: ' + err.message);
        }
    });

    document.getElementById('sapBtnGastoQuick').addEventListener('click', function () {
        document.getElementById('sapScope').value = 'global';
        fillResources();
        document.getElementById('sapResource').value = 'gasto-real';
        toggleFilters();
        if (!document.getElementById('gastoYear').value) {
            document.getElementById('gastoYear').value = String(new Date().getFullYear());
        }
        currentPage = 1;
        loadData();
    });

    document.getElementById('sapBtnVentasQuick').addEventListener('click', function () {
        document.getElementById('sapScope').value = 'global';
        fillResources();
        document.getElementById('sapResource').value = 'ventas';
        toggleFilters();
        if (!document.getElementById('ventasYear').value) {
            document.getElementById('ventasYear').value = String(new Date().getFullYear());
        }
        currentPage = 1;
        loadData();
    });

    document.getElementById('sapBtnListasPreciosQuick').addEventListener('click', function () {
        document.getElementById('sapScope').value = 'global';
        fillResources();
        document.getElementById('sapResource').value = 'listas-precios';
        toggleFilters();
        if (!document.getElementById('lpEmpresa').value) {
            document.getElementById('lpEmpresa').value = 'IMSA';
        }
        if (!document.getElementById('lpCodigoCliente').value) {
            document.getElementById('lpCodigoCliente').value = 'D136';
        }
        document.getElementById('sapPerPage').value = '100';
        currentPage = 1;
        loadData();
    });
})();
</script>
@endsection
