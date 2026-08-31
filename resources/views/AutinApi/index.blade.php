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
            <strong>Global:</strong> <code>/cuentas</code> · <code>/centros-costo</code>
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
                <label class="form-label fw-semibold">Empresa</label>
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
            <div class="col-md-2">
                <label class="form-label fw-semibold">Filtro (código)</label>
                <input type="text" id="sapFilterCode" class="form-control" placeholder="Código">
            </div>
            <div class="col-md-2">
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
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary" id="sapBtnLoad">
                    <i class="fas fa-search me-1"></i> Consultar
                </button>
                <button type="button" class="btn btn-outline-secondary" id="sapBtnHealth">
                    <i class="fas fa-heartbeat me-1"></i> Probar health
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

    function fillResources() {
        const scope = document.getElementById('sapScope').value;
        const select = document.getElementById('sapResource');
        const map = scope === 'global' ? resourcesGlobal : resourcesEmpresa;
        select.innerHTML = Object.keys(map).map(function (key) {
            return '<option value="' + key + '">' + map[key] + '</option>';
        }).join('');
        document.getElementById('sapDatabaseWrap').style.display = scope === 'empresa' ? '' : 'none';
        document.getElementById('sapEmpresaFilterWrap').style.display = scope === 'global' ? '' : 'none';
    }

    document.getElementById('sapScope').addEventListener('change', fillResources);
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
            if (empresaFiltro) params.set('Empresa', empresaFiltro);
            if (resource === 'centros-costo') {
                if (code) params.set('PrcCode', code);
                if (name) params.set('PrcName', name);
            } else if (resource === 'cuentas') {
                if (code) params.set('FormatCode', code);
                if (name) params.set('AcctName', name);
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

    function renderTable(payload) {
        const thead = document.getElementById('sapThead');
        const tbody = document.getElementById('sapTbody');
        const data = Array.isArray(payload.data) ? payload.data : [];
        const meta = payload.meta || {};

        document.getElementById('sapTitle').textContent = payload.label || 'Resultados';
        document.getElementById('sapMeta').textContent = payload.empresa
            ? (payload.empresa + ' · página ' + (meta.current_page || 1) + ' de ' + (meta.last_page || 1) + ' · ' + (meta.total || data.length) + ' registros')
            : ('página ' + (meta.current_page || 1) + ' de ' + (meta.last_page || 1) + ' · ' + (meta.total || data.length) + ' registros');

        currentPage = meta.current_page || 1;
        document.getElementById('sapPrev').disabled = !meta.current_page || meta.current_page <= 1;
        document.getElementById('sapNext').disabled = !meta.last_page || meta.current_page >= meta.last_page;

        if (!data.length) {
            thead.innerHTML = '';
            tbody.innerHTML = '<tr><td class="text-muted py-4">Sin resultados.</td></tr>';
            return;
        }

        const keys = Object.keys(data[0]);
        thead.innerHTML = keys.map(function (k) { return '<th>' + k + '</th>'; }).join('');
        tbody.innerHTML = data.map(function (row) {
            return '<tr>' + keys.map(function (k) {
                const v = row[k];
                const text = v === null || v === undefined ? '' : String(v);
                return '<td>' + text.replace(/</g, '&lt;') + '</td>';
            }).join('') + '</tr>';
        }).join('');
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
            document.getElementById('sapTbody').innerHTML =
                '<tr><td class="text-danger py-4">' + (err.message || 'Error al consultar') + '</td></tr>';
            document.getElementById('sapThead').innerHTML = '';
            document.getElementById('sapMeta').textContent = 'Error';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search me-1"></i> Consultar';
        }
    }

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
})();
</script>
@endsection
