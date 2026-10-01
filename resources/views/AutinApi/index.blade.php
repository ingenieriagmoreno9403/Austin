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
                <button type="button" class="btn btn-sm btn-outline-success" id="sapExcel" disabled title="Descarga en Excel todos los registros de esta consulta">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </button>
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
        sumas: @json(route('autin-api.sumas')),
        base: @json(url('/Sistemas/AutinApi'))
    };

    const resourcesEmpresa = @json($resources);
    const resourcesGlobal = @json($globalResources);

    let currentPage = 1;
    let loadedRows = [];
    let loadedKeys = [];
    let tableJob = 0;
    let excelJob = 0;
    let totalsJob = 0;
    let totalsAbort = null;
    let grand = null;

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

    function buildRequest(pageOverride, perPageOverride) {
        const scope = document.getElementById('sapScope').value;
        const resource = document.getElementById('sapResource').value;
        const perPage = perPageOverride != null ? String(perPageOverride) : document.getElementById('sapPerPage').value;
        const code = document.getElementById('sapFilterCode').value.trim();
        const name = document.getElementById('sapFilterName').value.trim();
        const empresaFiltro = document.getElementById('sapFilterEmpresa').value;

        const params = new URLSearchParams();
        params.set('per_page', perPage);
        params.set('page', String(pageOverride != null ? pageOverride : currentPage));

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

    function formatCount(value) {
        return Number(value || 0).toLocaleString('es-MX');
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

    function totalsChips(totals) {
        return Object.keys(totals).map(function (key) {
            return '<div class="sap-total-chip"><span class="k">' + escapeHtml(key) + '</span><span class="v">' + formatAmount(Number(totals[key]) || 0) + '</span></div>';
        }).join('');
    }

    function renderTotalsBox() {
        const box = document.getElementById('sapTotals');
        if (!grand) {
            box.hidden = true;
            box.innerHTML = '';
            return;
        }
        if (grand.loading) {
            box.hidden = false;
            box.innerHTML = '<div class="sap-meta mb-0">Calculando la suma de todos los registros…</div>';
            return;
        }
        const totals = grand.totals && typeof grand.totals === 'object' && !Array.isArray(grand.totals) ? grand.totals : null;
        if (!totals) {
            box.hidden = false;
            box.innerHTML = '<div class="text-danger mb-0">' + escapeHtml(grand.error || 'No se pudo calcular la suma') + '</div>';
            return;
        }

        const countLabel = formatCount(grand.total) + ' registros · todas las páginas';
        const keys = Object.keys(totals);
        if (!keys.length) {
            box.hidden = false;
            box.innerHTML = '<div class="sap-meta mb-0">' + countLabel + ' · esta consulta no trae columnas numéricas para totalizar.</div>';
            return;
        }

        const warn = grand.error
            ? '<div class="sap-meta mt-2 mb-0">' + escapeHtml(grand.error) + '</div>'
            : '';
        box.hidden = false;
        box.innerHTML = '<div class="fw-semibold mb-2">Suma de totales · ' + countLabel + '</div><div class="sap-totals-grid">' + totalsChips(totals) + '</div>' + warn;
    }

    function applyLocalTotals(rows, total) {
        const totals = {};
        loadedKeys.forEach(function (key) {
            if (!columnIsSummable(key, rows)) return;
            totals[key] = rows.reduce(function (sum, row) {
                const amount = parseAmount(row[key]);
                return sum + (amount === null ? 0 : amount);
            }, 0);
        });
        grand = { loading: false, total: total, totals: totals, error: null };
        renderTotalsBox();
    }

    function buildSumasUrl() {
        const built = buildRequest();
        const u = new URL(built.url, window.location.origin);
        const basePath = new URL(routes.base, window.location.origin).pathname.replace(/\/$/, '');
        let catalogo = decodeURIComponent(u.pathname);
        if (catalogo.indexOf(basePath + '/') === 0) {
            catalogo = catalogo.slice(basePath.length + 1);
        }
        u.searchParams.delete('page');
        u.searchParams.delete('per_page');
        u.searchParams.set('catalogo', catalogo);
        const sumasPath = new URL(routes.sumas, window.location.origin).pathname;
        return sumasPath + '?' + u.searchParams.toString();
    }

    function scheduleTotals(payload, sumasUrl) {
        const data = Array.isArray(payload.data) ? payload.data : [];
        const meta = payload.meta || {};
        const last = meta.last_page || 1;
        if (last <= 1) {
            applyLocalTotals(data, meta.total != null ? meta.total : data.length);
            return;
        }
        startGrandTotals(sumasUrl);
    }

    async function startGrandTotals(sumasUrl) {
        const token = ++totalsJob;
        if (totalsAbort) totalsAbort.abort();
        totalsAbort = new AbortController();
        grand = { loading: true, total: null, totals: null, error: null };
        renderTotalsBox();

        try {
            const json = await fetchJson(sumasUrl, totalsAbort.signal);
            if (token !== totalsJob) return;
            const totals = json.totals && typeof json.totals === 'object' && !Array.isArray(json.totals)
                ? json.totals
                : {};
            grand = {
                loading: false,
                total: json.total,
                totals: totals,
                error: json.complete === false
                    ? 'No se pudieron leer todas las páginas; la suma puede estar incompleta.'
                    : null
            };
            renderTotalsBox();
        } catch (err) {
            if (err.name === 'AbortError' || token !== totalsJob) return;
            grand = {
                loading: false,
                total: null,
                totals: null,
                error: err.message || 'No se pudo calcular la suma de todos los registros'
            };
            renderTotalsBox();
        }
    }

    function applyResultView() {
        const query = document.getElementById('sapSearch').value.trim();
        paintRows(visibleRows(), query);
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
        search.disabled = data.length === 0;
        document.getElementById('sapExcel').disabled = data.length === 0;
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

    async function fetchJson(url, signal) {
        const res = await fetch(url, {
            signal: signal,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        let json = {};
        try {
            json = await res.json();
        } catch (parseErr) {
            json = {};
        }
        if (!res.ok) {
            throw new Error(json.message || json.error || ('Error HTTP ' + res.status));
        }
        return json;
    }

    async function loadData(options) {
        const refreshTotals = !options || options.refreshTotals !== false;
        const job = ++tableJob;
        excelJob += 1;
        const excelBtn = document.getElementById('sapExcel');
        excelBtn.disabled = true;
        excelBtn.innerHTML = '<i class="fas fa-file-excel me-1"></i> Excel';
        const built = buildRequest();
        const sumasUrl = refreshTotals ? buildSumasUrl() : '';
        const btn = document.getElementById('sapBtnLoad');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cargando…';

        if (refreshTotals) {
            totalsJob += 1;
            if (totalsAbort) totalsAbort.abort();
            grand = { loading: true, total: null, totals: null, error: null };
            renderTotalsBox();
        }

        try {
            const json = await fetchJson(built.url);
            if (job !== tableJob) return;
            renderTable(json);
            if (refreshTotals) scheduleTotals(json, sumasUrl);
        } catch (err) {
            if (job !== tableJob) return;
            loadedRows = [];
            loadedKeys = [];
            document.getElementById('sapSearch').value = '';
            document.getElementById('sapSearch').disabled = true;
            document.getElementById('sapExcel').disabled = true;
            if (refreshTotals) {
                if (totalsAbort) totalsAbort.abort();
                grand = null;
                document.getElementById('sapTotals').hidden = true;
                document.getElementById('sapTotals').innerHTML = '';
            }
            document.getElementById('sapTbody').innerHTML =
                '<tr><td class="text-danger py-4">' + escapeHtml(err.message || 'Error al consultar') + '</td></tr>';
            document.getElementById('sapThead').innerHTML = '';
            document.getElementById('sapMeta').textContent = 'Error';
        } finally {
            if (job === tableJob) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-search me-1"></i> Consultar';
            }
        }
    }

    function xmlEscape(value) {
        return String(value)
            .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function sheetName() {
        const resource = document.getElementById('sapResource').value || 'Resultados';
        return resource.replace(/[\\/?*[\]:]/g, ' ').slice(0, 31) || 'Resultados';
    }

    function colName(index) {
        let n = index + 1;
        let name = '';
        while (n > 0) {
            const rem = (n - 1) % 26;
            name = String.fromCharCode(65 + rem) + name;
            n = Math.floor((n - 1) / 26);
        }
        return name;
    }

    function cellXml(value, ref, header) {
        const style = header ? ' s="1"' : '';
        if (!header && value !== null && value !== undefined && value !== '' && typeof value !== 'object') {
            const compact = String(value).trim().replace(/,/g, '');
            if (/^-?(?:0|[1-9]\d*)(?:\.\d+)?$/.test(compact)) {
                return '<c r="' + ref + '"' + style + '><v>' + compact + '</v></c>';
            }
        }
        let text = '';
        if (value !== null && value !== undefined && value !== '') {
            text = typeof value === 'object' ? JSON.stringify(value) : String(value);
        }
        const preserve = /^\s|\s$/.test(text) ? ' xml:space="preserve"' : '';
        return '<c r="' + ref + '"' + style + ' t="inlineStr"><is><t' + preserve + '>' + xmlEscape(text) + '</t></is></c>';
    }

    function worksheetXml(keys, rows) {
        const lines = [];
        lines.push('<row r="1">' + keys.map(function (key, index) {
            return cellXml(key, colName(index) + '1', true);
        }).join('') + '</row>');
        rows.forEach(function (row, rowIndex) {
            const excelRow = rowIndex + 2;
            lines.push('<row r="' + excelRow + '">' + keys.map(function (key, index) {
                return cellXml(row[key], colName(index) + excelRow, false);
            }).join('') + '</row>');
        });
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            + '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            + lines.join('')
            + '</sheetData></worksheet>';
    }

    const CRC_TABLE = (function () {
        const table = new Uint32Array(256);
        for (let n = 0; n < 256; n++) {
            let c = n;
            for (let k = 0; k < 8; k++) {
                c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
            }
            table[n] = c >>> 0;
        }
        return table;
    })();

    function crc32(bytes) {
        let crc = 0xFFFFFFFF;
        for (let i = 0; i < bytes.length; i++) {
            crc = CRC_TABLE[(crc ^ bytes[i]) & 0xFF] ^ (crc >>> 8);
        }
        return (crc ^ 0xFFFFFFFF) >>> 0;
    }

    function u16(n) {
        return new Uint8Array([n & 255, (n >> 8) & 255]);
    }

    function u32(n) {
        n = n >>> 0;
        return new Uint8Array([n & 255, (n >>> 8) & 255, (n >>> 16) & 255, (n >>> 24) & 255]);
    }

    function concatBytes(parts) {
        let length = 0;
        parts.forEach(function (part) { length += part.length; });
        const out = new Uint8Array(length);
        let offset = 0;
        parts.forEach(function (part) {
            out.set(part, offset);
            offset += part.length;
        });
        return out;
    }

    function zipStore(files) {
        const now = new Date();
        const dosTime = (now.getHours() << 11) | (now.getMinutes() << 5) | Math.floor(now.getSeconds() / 2);
        const dosDate = ((now.getFullYear() - 1980) << 9) | ((now.getMonth() + 1) << 5) | now.getDate();
        const encoder = new TextEncoder();
        const locals = [];
        const centrals = [];
        let offset = 0;

        files.forEach(function (file) {
            const name = encoder.encode(file.name);
            const data = encoder.encode(file.data);
            const crc = crc32(data);
            const local = concatBytes([
                u32(0x04034b50), u16(20), u16(0), u16(0),
                u16(dosTime), u16(dosDate), u32(crc),
                u32(data.length), u32(data.length),
                u16(name.length), u16(0), name, data
            ]);
            locals.push(local);
            centrals.push(concatBytes([
                u32(0x02014b50), u16(20), u16(20), u16(0), u16(0),
                u16(dosTime), u16(dosDate), u32(crc),
                u32(data.length), u32(data.length),
                u16(name.length), u16(0), u16(0), u16(0), u16(0),
                u32(0), u32(offset), name
            ]));
            offset += local.length;
        });

        const central = concatBytes(centrals);
        const end = concatBytes([
            u32(0x06054b50), u16(0), u16(0),
            u16(files.length), u16(files.length),
            u32(central.length), u32(offset), u16(0)
        ]);
        return concatBytes(locals.concat([central, end]));
    }

    function buildWorkbook(keys, rows) {
        const name = xmlEscape(sheetName());
        const files = [
            {
                name: '[Content_Types].xml',
                data: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    + '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                    + '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                    + '<Default Extension="xml" ContentType="application/xml"/>'
                    + '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                    + '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                    + '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                    + '</Types>'
            },
            {
                name: '_rels/.rels',
                data: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                    + '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                    + '</Relationships>'
            },
            {
                name: 'xl/workbook.xml',
                data: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    + '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                    + '<sheets><sheet name="' + name + '" sheetId="1" r:id="rId1"/></sheets></workbook>'
            },
            {
                name: 'xl/_rels/workbook.xml.rels',
                data: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                    + '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                    + '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                    + '</Relationships>'
            },
            {
                name: 'xl/styles.xml',
                data: '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                    + '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                    + '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
                    + '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
                    + '<fill><patternFill patternType="solid"><fgColor rgb="FF111827"/></patternFill></fill></fills>'
                    + '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                    + '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                    + '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                    + '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
                    + '</styleSheet>'
            },
            { name: 'xl/worksheets/sheet1.xml', data: worksheetXml(keys, rows) }
        ];
        const bytes = zipStore(files);
        return new Blob([bytes], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    }

    function downloadBlob(filename, blob) {
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 1500);
    }

    function excelFilename() {
        const scope = document.getElementById('sapScope').value || 'catalogo';
        const resource = document.getElementById('sapResource').value || 'resultados';
        const now = new Date();
        const pad = function (n) { return String(n).padStart(2, '0'); };
        const stamp = now.getFullYear() + pad(now.getMonth() + 1) + pad(now.getDate());
        return 'autin-' + scope + '-' + resource + '-' + stamp + '.xlsx';
    }

    function rowKeys(rows) {
        const keys = [];
        const seen = {};
        rows.forEach(function (row) {
            if (!row || typeof row !== 'object') return;
            Object.keys(row).forEach(function (key) {
                if (!seen[key]) {
                    seen[key] = true;
                    keys.push(key);
                }
            });
        });
        return keys;
    }

    async function fetchPage(page, perPage) {
        let lastError = null;
        for (let attempt = 0; attempt < 2; attempt++) {
            try {
                return await fetchJson(buildRequest(page, perPage));
            } catch (err) {
                lastError = err;
            }
        }
        throw lastError || new Error('No se pudo leer la página ' + page);
    }

    async function mapPool(items, limit, worker) {
        const results = new Array(items.length);
        let cursor = 0;
        async function run() {
            while (cursor < items.length) {
                const index = cursor;
                cursor += 1;
                results[index] = await worker(items[index], index);
            }
        }
        const workers = [];
        const size = Math.min(limit, items.length);
        for (let i = 0; i < size; i++) workers.push(run());
        await Promise.all(workers);
        return results;
    }

    async function fetchAllRows(onProgress) {
        const maxRows = 65000;
        let perPage = 200;
        let first;
        try {
            first = await fetchPage(1, perPage);
        } catch (err) {
            perPage = 80;
            first = await fetchPage(1, perPage);
        }

        const firstRows = Array.isArray(first.data) ? first.data : [];
        const meta = first.meta || {};
        let last = parseInt(meta.last_page || 1, 10);
        if (!last || last < 1) last = 1;
        const total = meta.total != null ? meta.total : firstRows.length;
        const maxPages = Math.max(1, Math.ceil(maxRows / perPage));
        const capped = last > maxPages;
        if (capped) last = maxPages;

        const pages = [];
        for (let page = 2; page <= last; page++) pages.push(page);
        let done = 1;
        onProgress(done, last);

        const chunks = await mapPool(pages, 3, async function (page) {
            const json = await fetchPage(page, perPage);
            done += 1;
            onProgress(done, last);
            return Array.isArray(json.data) ? json.data : [];
        });

        const rows = firstRows.slice();
        chunks.forEach(function (chunk) {
            chunk.forEach(function (row) {
                if (row && typeof row === 'object') rows.push(row);
            });
        });

        return {
            rows: rows.slice(0, maxRows),
            total: total,
            truncated: capped || (total > rows.length && last >= maxPages)
        };
    }

    async function downloadExcel() {
        const btn = document.getElementById('sapExcel');
        if (btn.disabled) return;
        const job = ++excelJob;
        const label = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Preparando…';

        try {
            const packed = await fetchAllRows(function (done, last) {
                if (job !== excelJob) return;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> ' + done + '/' + last;
            });
            if (job !== excelJob) return;
            if (!packed.rows.length) {
                alert('No hay registros para descargar.');
                return;
            }
            const keys = rowKeys(packed.rows);
            downloadBlob(excelFilename(), buildWorkbook(keys, packed.rows));
            if (packed.truncated) {
                alert('El archivo incluye ' + formatCount(packed.rows.length) + ' de ' + formatCount(packed.total) + ' registros. Acota los filtros para descargar el resto.');
            }
        } catch (err) {
            if (job !== excelJob) return;
            alert(err.message || 'No se pudo generar el Excel');
        } finally {
            if (job === excelJob) {
                btn.disabled = loadedRows.length === 0;
                btn.innerHTML = label;
            }
        }
    }

    document.getElementById('sapExcel').addEventListener('click', downloadExcel);

    document.getElementById('sapSearch').addEventListener('input', applyResultView);

    document.getElementById('sapFilterForm').addEventListener('submit', function (e) {
        e.preventDefault();
        currentPage = 1;
        loadData();
    });

    document.getElementById('sapPrev').addEventListener('click', function () {
        if (currentPage > 1) {
            currentPage -= 1;
            loadData({ refreshTotals: false });
        }
    });

    document.getElementById('sapNext').addEventListener('click', function () {
        currentPage += 1;
        loadData({ refreshTotals: false });
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
