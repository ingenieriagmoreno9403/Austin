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
    .exp-error {
        display: none;
        margin-top: 1rem;
        padding: .9rem 1rem;
        border: 1px solid #fecaca;
        background: #fef2f2;
        border-radius: 8px;
        color: #7f1d1d;
        max-width: 560px;
    }
    .exp-error.is-on {
        display: block;
    }
    .exp-error-title {
        display: flex;
        align-items: center;
        gap: .45rem;
        font-weight: 700;
        font-size: .92rem;
        margin-bottom: .35rem;
    }
    .exp-error-motivo {
        margin: 0 0 .75rem;
        font-size: .84rem;
        line-height: 1.4;
        color: #991b1b;
        word-break: break-word;
    }
    .exp-error-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .exp-error-actions .cc-btn {
        min-width: 0;
    }
</style>
@endsection

@section('content')
<div class="cc-page" id="cc-app">
    <div class="cc-header">
        <div>
            <div class="cc-kicker">Gastos · exportación</div>
            <h1 class="cc-title">Exportar información del budget</h1>
            <p class="cc-sub">Elige el ciclo (y opcionalmente una empresa) para descargar en Excel los importes de centros de costo y cuentas asignadas. Incluye cuentas sin captura con importe 0.</p>
        </div>
        <div class="cc-header-actions">
            <a class="cc-btn" href="{{ route('centros.control') }}">
                <i class="fa-solid fa-pen-to-square"></i> Ir a captura
            </a>
        </div>
    </div>


    <div class="cc-panel">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-file-excel"></i> Descargar budget de gastos</h3>
            <span class="text-muted" style="font-size:.8rem" id="exp-hint">Selecciona un ciclo para exportar. Si tarda o falla, filtra por empresa.</span>
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

        <div class="exp-error" id="exp-error" role="alert">
            <div class="exp-error-title">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>Error al descargar</span>
            </div>
            <p class="exp-error-motivo" id="exp-error-motivo"></p>
            <div class="exp-error-actions">
                <button type="button" class="cc-btn cc-btn-ink" id="exp-reintentar">
                    <i class="fa-solid fa-rotate-right"></i> Volver a descargar
                </button>
                <button type="button" class="cc-btn" id="exp-error-cerrar">Cerrar</button>
            </div>
        </div>

        <div class="cc-empty" id="exp-empty" style="padding:1.6rem 1rem;margin-top:.5rem">
            <i class="fa-solid fa-circle-info"></i>
            <div>
                Se descarga un <strong>Excel (.xlsx)</strong> en formato largo (1 fila por mes, solo cuentas asignadas; importe 0 si aún no hay captura):
                <strong>COMPANY</strong>, <strong>CC</strong>/<strong>NAME CC</strong> (centro de costo),
                <strong>CUENTA</strong>/<strong>NAME CUENTA</strong>,
                <strong>YEAR</strong>, <strong>MONTH</strong>, <strong>ORIGIN</strong> (BUDGET),
                <strong>IMPORTEUSD</strong>, <strong>IMPORTEMXN</strong>, <strong>TC</strong>.
                La captura se guarda en MXN; <strong>IMPORTEUSD</strong> = MXN ÷ TC (sin redondear cada fila a centavos, para que la suma coincida con el total USD del sistema).
                Si tienes cuentas asignadas se usan esas; si no, se exportan todas las del ciclo (puedes filtrar por empresa).
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(function () {
    var ciclosUrl = @json(route('centros.api.ciclos'));
    var empresasUrl = @json(route('centros.api.empresas'));
    var startUrl = @json(route('centros.exportar.excel.start'));
    var statusUrlBase = @json(url('/ExportarInformacionGastos/excel/status'));
    var fileUrlBase = @json(url('/ExportarInformacionGastos/excel/file'));
    var cicloSel = document.getElementById('exp-ciclo');
    var empresaSel = document.getElementById('exp-empresa');
    var btn = document.getElementById('exp-descargar');
    var hint = document.getElementById('exp-hint');
    var progressWrap = document.getElementById('exp-progress-wrap');
    var progressLabel = document.getElementById('exp-progress-label');
    var progressPct = document.getElementById('exp-progress-pct');
    var progressBar = document.getElementById('exp-progress-bar');
    var errorBox = document.getElementById('exp-error');
    var errorMotivo = document.getElementById('exp-error-motivo');
    var btnRetry = document.getElementById('exp-reintentar');
    var btnErrorClose = document.getElementById('exp-error-cerrar');
    var exporting = false;
    var tickTimer = null;
    var pollTimer = null;
    /** Tiempo máximo esperando el archivo en segundo plano. */
    var CLIENT_TIMEOUT_MS = 12 * 60 * 1000;

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

    function hideError() {
        if (errorBox) errorBox.classList.remove('is-on');
        if (errorMotivo) errorMotivo.textContent = '';
    }

    function showError(motivo) {
        var text = String(motivo || 'Error desconocido al generar el Excel.').trim();
        if (errorMotivo) errorMotivo.textContent = text;
        if (errorBox) errorBox.classList.add('is-on');
        if (hint) hint.textContent = 'Error al descargar.';
        showProgress(false);
    }

    function startFakeProgress() {
        var pct = 4;
        setProgress(pct, 'Generando Excel…');
        if (tickTimer) clearInterval(tickTimer);
        tickTimer = setInterval(function () {
            if (pct >= 88) return;
            pct += Math.max(1, Math.round((90 - pct) * 0.08));
            setProgress(pct, 'Generando Excel… esto puede tardar varios minutos');
        }, 350);
    }

    function syncBtn() {
        var ok = !!(cicloSel && cicloSel.value) && !exporting;
        if (btn) btn.disabled = !ok;
        if (btnRetry) btnRetry.disabled = !ok && !cicloSel.value;
        if (hint && !exporting && !(errorBox && errorBox.classList.contains('is-on'))) {
            hint.textContent = (cicloSel && cicloSel.value)
                ? 'Listo para descargar el budget del ciclo ' + cicloSel.value + ' (solo cuentas asignadas).'
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

    function looksLikeHtml(text) {
        var t = String(text || '').trim().slice(0, 200).toLowerCase();
        return t.indexOf('<!doctype') === 0 || t.indexOf('<html') === 0 || t.indexOf('<body') >= 0;
    }

    function extractMotivoFromHtml(html) {
        var text = String(html || '');
        var m = /Maximum execution time of \d+ seconds exceeded/i.exec(text);
        if (m) return 'Se agotó el tiempo de ejecución al generar el Excel. Vuelve a intentar; si persiste, exporta por empresa.';
        m = /Allowed memory size of \d+ bytes exhausted/i.exec(text);
        if (m) return 'Se agotó la memoria del servidor al generar el Excel. Intenta exportar filtrando por una empresa.';
        m = /<title[^>]*>([^<]+)<\/title>/i.exec(text);
        if (m && m[1]) {
            var title = m[1].replace(/\s+/g, ' ').trim();
            if (title && title.toLowerCase().indexOf('laravel') === -1) return title;
        }
        return '';
    }

    function motivoDesdeRespuesta(res, bodyText) {
        var status = res && res.status ? res.status : 0;
        var text = String(bodyText || '').trim();

        if (text) {
            try {
                var json = JSON.parse(text);
                if (json && (json.motivo || json.message || json.error)) {
                    return String(json.motivo || json.message || json.error);
                }
            } catch (e) { /* no JSON */ }

            if (looksLikeHtml(text)) {
                var fromHtml = extractMotivoFromHtml(text);
                if (fromHtml) return fromHtml;
            } else if (text.length < 400 && text.indexOf('<') === -1) {
                return text;
            }
        }

        if (status === 408 || status === 504 || status === 524) {
            return 'El proxy cortó la espera (HTTP ' + status + ' Gateway Timeout). Vuelve a intentar: ahora el Excel se genera en segundo plano. Si persiste, exporta por empresa.';
        }
        if (status === 502 || status === 503) {
            return 'El servidor no pudo completar la descarga (HTTP ' + status + '). Vuelve a intentar; si tarda mucho, exporta por empresa.';
        }
        if (status === 419) {
            return 'La sesión expiró (CSRF). Recarga la página e intenta de nuevo.';
        }
        if (status === 403) {
            return 'No tienes permiso para exportar este budget (HTTP 403).';
        }
        if (status === 422) {
            return 'Datos inválidos para exportar. Revisa el ciclo seleccionado.';
        }
        if (status >= 500) {
            return 'Error interno del servidor al generar el Excel (HTTP ' + status + ').';
        }
        if (status) {
            return 'No se pudo generar el Excel (HTTP ' + status + ').';
        }
        return 'No se pudo generar el Excel.';
    }

    function motivoDesdeCatch(err) {
        if (!err) return 'Error desconocido al descargar.';
        if (err.name === 'AbortError') {
            return 'Se canceló o se agotó el tiempo de espera en el navegador al descargar. Vuelve a intentar; si tarda mucho, exporta por empresa.';
        }
        var msg = String(err.message || err);
        var lower = msg.toLowerCase();
        if (lower.indexOf('body stream already read') >= 0) {
            return 'Error al interpretar la respuesta del servidor. Vuelve a intentar.';
        }
        if (lower.indexOf('failed to fetch') >= 0 || lower.indexOf('networkerror') >= 0) {
            return 'Error de red o el servidor cortó la conexión (a veces por tiempo de ejecución). Vuelve a intentar.';
        }
        return msg || 'Error desconocido al descargar.';
    }

    /** Lee el body una sola vez (evita "body stream already read"). */
    function readJsonOnce(res) {
        return res.text().then(function (bodyText) {
            var json = null;
            if (bodyText) {
                try {
                    json = JSON.parse(bodyText);
                } catch (e) {
                    json = null;
                }
            }
            return { res: res, json: json, bodyText: bodyText || '' };
        });
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
        if (!empresaSel) return Promise.resolve();
        var url = empresasUrl + (ciclo ? ('?ciclo=' + encodeURIComponent(ciclo)) : '');
        return fetch(url, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (json) {
            var list = [];
            if (Array.isArray(json)) list = json;
            else if (json && Array.isArray(json.empresas)) list = json.empresas;
            else if (json && Array.isArray(json.data)) list = json.data;
            var prev = empresaSel.value;
            empresaSel.innerHTML = '<option value="">Todas</option>';
            list.forEach(function (e) {
                var code = String((typeof e === 'string') ? e : (e.codigo || e.code || e.id || e.nombre || '')).toUpperCase();
                var name = String((typeof e === 'string') ? e : (e.nombre || e.name || code));
                if (!code) return;
                if (code === 'ABSA') code = 'AUSTIN';
                var opt = document.createElement('option');
                opt.value = code;
                opt.textContent = name.toUpperCase();
                empresaSel.appendChild(opt);
            });
            if (prev) empresaSel.value = prev;
        }).catch(function () { /* opcional */ });
    }

    function clearPoll() {
        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }
    }

    function resetExportUi() {
        exporting = false;
        clearPoll();
        if (tickTimer) {
            clearInterval(tickTimer);
            tickTimer = null;
        }
        if (btn) btn.innerHTML = '<i class="fa-solid fa-file-arrow-down"></i> Exportar Excel';
        syncBtn();
    }

    function downloadReadyFile(token, fallbackName) {
        var url = fileUrlBase + '/' + encodeURIComponent(token);
        setProgress(95, 'Descargando archivo…');
        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/octet-stream,application/json,*/*'
            }
        }).then(function (res) {
            var ct = String(res.headers.get('Content-Type') || '').toLowerCase();
            if (!res.ok || ct.indexOf('json') >= 0 || ct.indexOf('text/html') >= 0) {
                return res.text().then(function (body) {
                    throw new Error(motivoDesdeRespuesta(res, body));
                });
            }
            var name = filenameFromDisposition(res.headers.get('Content-Disposition'), fallbackName || 'budget_gastos.xlsx');
            return res.blob().then(function (blob) {
                if (!blob || blob.size < 32) {
                    throw new Error('El archivo recibido está vacío o incompleto. Vuelve a intentar.');
                }
                return { blob: blob, name: name };
            });
        }).then(function (pack) {
            var objectUrl = URL.createObjectURL(pack.blob);
            var a = document.createElement('a');
            a.href = objectUrl;
            a.download = pack.name;
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 1500);
            setProgress(100, 'Listo');
            if (hint) hint.textContent = 'Descarga completada.';
            setTimeout(function () {
                showProgress(false);
                resetExportUi();
            }, 900);
        });
    }

    function pollExportStatus(token, startedAt, fallbackName) {
        clearPoll();
        if (Date.now() - startedAt > CLIENT_TIMEOUT_MS) {
            showError('Se agotó el tiempo de espera generando el Excel. Vuelve a intentar o exporta por empresa.');
            resetExportUi();
            return;
        }

        fetch(statusUrlBase + '/' + encodeURIComponent(token), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function (res) {
            return readJsonOnce(res);
        }).then(function (pack) {
            var json = pack.json || {};
            if (!pack.res.ok) {
                throw new Error(json.motivo || json.message || motivoDesdeRespuesta(pack.res, pack.bodyText));
            }
            var status = String(json.status || '');
            var pct = Number(json.pct);
            if (!isFinite(pct)) pct = status === 'ready' ? 100 : 20;
            if (status === 'ready') {
                if (tickTimer) {
                    clearInterval(tickTimer);
                    tickTimer = null;
                }
                return downloadReadyFile(token, json.filename || fallbackName);
            }
            if (status === 'error') {
                throw new Error(json.motivo || 'Error al generar el Excel.');
            }
            setProgress(Math.max(8, Math.min(90, pct)), status === 'running'
                ? 'Generando Excel en segundo plano…'
                : 'En cola…');
            pollTimer = setTimeout(function () {
                pollExportStatus(token, startedAt, fallbackName);
            }, 1500);
        }).catch(function (err) {
            showError(motivoDesdeCatch(err));
            resetExportUi();
        });
    }

    function exportExcel() {
        var ciclo = cicloSel ? cicloSel.value : '';
        if (!ciclo || exporting) return;
        var empresa = empresaSel ? empresaSel.value : '';
        var fallbackName = 'budget_gastos_' + ciclo + (empresa ? ('_' + empresa) : '') + '.xlsx';

        hideError();
        exporting = true;
        syncBtn();
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Exportando…';
        }
        showProgress(true);
        startFakeProgress();
        if (hint) hint.textContent = 'Generando archivo del ciclo ' + ciclo + ' en segundo plano…';

        var body = new FormData();
        body.append('ciclo', ciclo);
        if (empresa) body.append('empresa', empresa);

        fetch(startUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: body
        }).then(function (res) {
            return readJsonOnce(res);
        }).then(function (pack) {
            if (!pack.res.ok || !pack.json || !pack.json.token) {
                var motivo = (pack.json && (pack.json.motivo || pack.json.message))
                    || motivoDesdeRespuesta(pack.res, pack.bodyText);
                if (pack.res.ok && (!pack.json || !pack.json.token)) {
                    motivo = 'El servidor respondió sin token de exportación (posible agotamiento de memoria). Filtra por una empresa e intenta de nuevo.';
                }
                throw new Error(motivo);
            }
            setProgress(12, 'Generando Excel en segundo plano…');
            if (hint) hint.textContent = 'El archivo se está armando; no cierres esta pestaña…';
            pollExportStatus(pack.json.token, Date.now(), fallbackName);
        }).catch(function (err) {
            setProgress(0, 'Error al descargar');
            showError(motivoDesdeCatch(err));
            resetExportUi();
        });
    }

    if (cicloSel) {
        cicloSel.addEventListener('change', function () {
            hideError();
            syncBtn();
            loadEmpresas();
        });
    }
    if (empresaSel) {
        empresaSel.addEventListener('change', function () {
            hideError();
        });
    }
    if (btn) btn.addEventListener('click', exportExcel);
    if (btnRetry) btnRetry.addEventListener('click', exportExcel);
    if (btnErrorClose) {
        btnErrorClose.addEventListener('click', function () {
            hideError();
            syncBtn();
        });
    }

    loadCiclos().then(loadEmpresas);
})();
</script>
@endsection
