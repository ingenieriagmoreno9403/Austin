@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}?v={{ (int) @filemtime(public_path('css/centros-costos.css')) }}" rel="stylesheet">
<style>
    .exp-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: .85rem;
        margin-top: 1rem;
    }
    .exp-actions .cc-btn {
        min-width: 200px;
        justify-content: center;
        text-align: center;
    }
    .exp-progress-wrap {
        width: min(420px, 100%);
        display: none;
    }
    .exp-progress-wrap.is-on {
        display: block;
    }
    .exp-progress-top {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: .75rem;
        margin-bottom: .4rem;
        font-size: .8rem;
        color: #64748b;
    }
    .exp-progress-top strong {
        color: #0f172a;
        font-variant-numeric: tabular-nums;
    }
    .exp-progress-wrap .cc-progress {
        height: 10px;
    }
</style>
@endsection

@section('content')
<div class="cc-page" id="cc-app">
    <div class="cc-header">
        <div>
            <div class="cc-kicker">Ventas · exportación</div>
            <h1 class="cc-title">Exportar información del budget</h1>
            <p class="cc-sub">Elige el ciclo (y opcionalmente una empresa) para descargar en Excel las cantidades capturadas del presupuesto.</p>
        </div>
        <div class="cc-header-actions">
            <a class="cc-btn" href="{{ route('pv.control') }}">
                <i class="fa-solid fa-pen-to-square"></i> Ir a captura
            </a>
        </div>
    </div>

    @include('ProyeccionesVentas.partials.nav')

    <div class="cc-panel">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-file-excel"></i> Descargar budget</h3>
            <span class="text-muted" style="font-size:.8rem" id="exp-hint">Selecciona un ciclo para exportar.</span>
        </div>

        <div class="cc-filters" style="grid-template-columns: 220px 200px auto; align-items: end;">
            <label class="cc-filter-field" for="exp-ciclo">Ciclo
                <select id="exp-ciclo" class="cc-select" aria-label="Ciclo">
                    <option value="">Seleccionar ciclo…</option>
                </select>
            </label>
            <label class="cc-filter-field" for="exp-empresa">Empresa
                <select id="exp-empresa" class="cc-select" aria-label="Empresa">
                    <option value="">Todas</option>
                </select>
            </label>
            <div class="exp-actions">
                <button type="button" class="cc-btn cc-btn-ink" id="exp-descargar" disabled>
                    <i class="fa-solid fa-file-arrow-down"></i> Exportar Excel
                </button>
            </div>
        </div>
        <div class="exp-progress-wrap" id="exp-progress-wrap" aria-live="polite" style="margin-top:.85rem">
            <div class="exp-progress-top">
                <span id="exp-progress-label">Preparando archivo…</span>
                <strong id="exp-progress-pct">0%</strong>
            </div>
            <div class="cc-progress" id="exp-progress-bar"><span style="width:0%"></span></div>
        </div>

        <div class="cc-empty" id="exp-empty" style="padding:1.6rem 1rem;margin-top:.5rem">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                El Excel sale en formato largo (1 fila por mes):
                <strong>COMPANY</strong>, <strong>ITEMCODE</strong>/<strong>ITEMNAME</strong> (producto),
                <strong>CARDCODE</strong>/<strong>CARDNAME</strong> (cliente),
                <strong>MONTH</strong>, <strong>YEAR</strong>, <strong>QTY</strong>, <strong>QTY PREVIOUS</strong> (venta del año pasado),
                <strong>ORIGEN</strong>, <strong>IMPORTEUSD</strong>, <strong>IMPORTEMXN</strong>, <strong>TC</strong>, <strong>PRICE</strong>.
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    var ciclosUrl = @json(route('pv.api.ciclos'));
    var empresasUrl = @json(route('pv.api.empresas'));
    var downloadUrl = @json(route('pv.exportar.excel'));
    var cicloSel = document.getElementById('exp-ciclo');
    var empresaSel = document.getElementById('exp-empresa');
    var btn = document.getElementById('exp-descargar');
    var hint = document.getElementById('exp-hint');
    var progressWrap = document.getElementById('exp-progress-wrap');
    var progressLabel = document.getElementById('exp-progress-label');
    var progressPct = document.getElementById('exp-progress-pct');
    var progressBar = document.getElementById('exp-progress-bar');
    var exporting = false;
    var tickTimer = null;

    function csrf() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function setProgress(pct, label) {
        pct = Math.max(0, Math.min(100, Math.round(pct)));
        if (progressPct) progressPct.textContent = pct + '%';
        if (progressBar) {
            var span = progressBar.querySelector('span');
            if (span) span.style.width = pct + '%';
            progressBar.classList.toggle('good', pct >= 100);
            progressBar.classList.toggle('warn', pct > 0 && pct < 100);
        }
        if (label && progressLabel) progressLabel.textContent = label;
    }

    function showProgress(on) {
        if (!progressWrap) return;
        progressWrap.classList.toggle('is-on', !!on);
        if (!on) {
            if (tickTimer) {
                clearInterval(tickTimer);
                tickTimer = null;
            }
            setProgress(0, 'Preparando archivo…');
        }
    }

    function startFakeProgress() {
        var pct = 4;
        setProgress(pct, 'Generando Excel…');
        if (tickTimer) clearInterval(tickTimer);
        tickTimer = setInterval(function () {
            if (pct >= 88) return;
            pct += Math.max(1, Math.round((90 - pct) * 0.08));
            setProgress(pct, 'Generando Excel…');
        }, 350);
    }

    function syncBtn() {
        var ok = !!(cicloSel && cicloSel.value) && !exporting;
        if (btn) btn.disabled = !ok;
        if (hint && !exporting) {
            hint.textContent = (cicloSel && cicloSel.value)
                ? 'Listo para descargar el budget del ciclo ' + cicloSel.value + '.'
                : 'Selecciona un ciclo para exportar.';
        }
    }

    function filenameFromDisposition(header, fallback) {
        if (!header) return fallback;
        var m = /filename\*=UTF-8''([^;]+)|filename="?([^";]+)"?/i.exec(header);
        if (!m) return fallback;
        try {
            return decodeURIComponent((m[1] || m[2] || fallback).trim());
        } catch (e) {
            return (m[1] || m[2] || fallback).trim();
        }
    }

    function loadCiclos() {
        return fetch(ciclosUrl, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (json) {
            var ciclos = (json && json.ciclos) ? json.ciclos : [];
            if (!cicloSel) return;
            cicloSel.innerHTML = '<option value="">Seleccionar ciclo…</option>';
            ciclos.forEach(function (c) {
                var codigo = String(c.codigo || c.Codigo || '').toUpperCase();
                if (!codigo) return;
                var nombre = String(c.nombre || c.Nombre || codigo);
                var estado = String(c.estado || '');
                var opt = document.createElement('option');
                opt.value = codigo;
                opt.textContent = codigo + ' · ' + nombre + (estado ? ' (' + estado + ')' : '');
                cicloSel.appendChild(opt);
            });
            syncBtn();
        }).catch(function () {
            if (hint) hint.textContent = 'No se pudieron cargar los ciclos.';
        });
    }

    function loadEmpresas() {
        var ciclo = cicloSel ? cicloSel.value : '';
        var url = empresasUrl + (ciclo ? ('?ciclo=' + encodeURIComponent(ciclo)) : '');
        return fetch(url, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (json) {
            var list = [];
            if (Array.isArray(json)) list = json;
            else if (json && Array.isArray(json.empresas)) list = json.empresas;
            else if (json && Array.isArray(json.data)) list = json.data;
            if (!empresaSel) return;
            var prev = empresaSel.value;
            empresaSel.innerHTML = '<option value="">Todas</option>';
            list.forEach(function (e) {
                var code = String((typeof e === 'string') ? e : (e.codigo || e.code || e.id || e.nombre || '')).toUpperCase();
                var name = String((typeof e === 'string') ? e : (e.nombre || e.name || code));
                if (!code) return;
                var opt = document.createElement('option');
                opt.value = code;
                opt.textContent = name;
                empresaSel.appendChild(opt);
            });
            if (prev) empresaSel.value = prev;
        }).catch(function () { /* opcional */ });
    }

    function exportExcel() {
        var ciclo = cicloSel ? cicloSel.value : '';
        if (!ciclo || exporting) return;
        var empresa = empresaSel ? empresaSel.value : '';
        var url = downloadUrl + '?ciclo=' + encodeURIComponent(ciclo);
        if (empresa) url += '&empresa=' + encodeURIComponent(empresa);

        exporting = true;
        syncBtn();
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Exportando…';
        }
        showProgress(true);
        startFakeProgress();
        if (hint) hint.textContent = 'Generando archivo del ciclo ' + ciclo + '…';

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream,*/*' }
        }).then(function (res) {
            if (!res.ok) {
                return res.text().then(function () {
                    throw new Error('No se pudo generar el Excel (' + res.status + ').');
                });
            }
            var name = filenameFromDisposition(
                res.headers.get('Content-Disposition'),
                'budget_ventas_' + ciclo + '.xlsx'
            );
            setProgress(92, 'Descargando…');
            return res.blob().then(function (blob) {
                return { blob: blob, name: name };
            });
        }).then(function (pack) {
            if (tickTimer) {
                clearInterval(tickTimer);
                tickTimer = null;
            }
            setProgress(100, 'Listo');
            var objectUrl = URL.createObjectURL(pack.blob);
            var a = document.createElement('a');
            a.href = objectUrl;
            a.download = pack.name;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 1500);
            if (hint) hint.textContent = 'Descarga completada.';
            setTimeout(function () {
                showProgress(false);
                exporting = false;
                if (btn) btn.innerHTML = '<i class="fa-solid fa-file-arrow-down"></i> Exportar Excel';
                syncBtn();
            }, 900);
        }).catch(function (err) {
            if (tickTimer) {
                clearInterval(tickTimer);
                tickTimer = null;
            }
            setProgress(0, 'Error al exportar');
            if (hint) hint.textContent = (err && err.message) ? err.message : 'Error al exportar.';
            exporting = false;
            if (btn) btn.innerHTML = '<i class="fa-solid fa-file-arrow-down"></i> Exportar Excel';
            syncBtn();
            setTimeout(function () { showProgress(false); }, 1800);
        });
    }

    if (cicloSel) {
        cicloSel.addEventListener('change', function () {
            syncBtn();
            loadEmpresas();
        });
    }
    if (btn) btn.addEventListener('click', exportExcel);

    loadCiclos().then(loadEmpresas);
})();
</script>
@endsection
