(function (window, document) {
    'use strict';

    var csrf = document.querySelector('meta[name="csrf-token"]');
    csrf = csrf ? csrf.getAttribute('content') : '';

    var EMPRESAS_FALLBACK = [
        { codigo: 'austin', nombre: 'AUSTIN', grupos: 0 },
        { codigo: 'imsa', nombre: 'IMSA', grupos: 0 },
        { codigo: 'pitic', nombre: 'PITIC', grupos: 0 },
        { codigo: 'sydney', nombre: 'SYDNEY', grupos: 0 }
    ];

    var state = {
        empresa: '',
        empresas: [],
        grupos: [],
        cuentas: [],
        cuentasAll: [],
        agrupaciones: [],
        mask: '',
        clave: '',
        nombre: '',
        selected: {},
        editingId: 0,
        sapOk: false,
        sapMensaje: '',
        gruposCache: {},
        loadSeq: 0,
        creating: false,
        named: false
    };

    function getJSON(url) {
        return fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                return r.json().then(function (json) {
                    if (!r.ok) throw new Error(json.message || ('HTTP ' + r.status));
                    return json;
                });
            });
    }

    function sendJSON(url, method, body) {
        return fetch(url, {
            method: method,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (r) {
            return r.json().then(function (json) {
                if (!r.ok) {
                    var msg = json.message || ('HTTP ' + r.status);
                    if (json.errors) {
                        var first = Object.keys(json.errors)[0];
                        if (first && json.errors[first] && json.errors[first][0]) {
                            msg = json.errors[first][0];
                        }
                    }
                    throw new Error(msg);
                }
                return json;
            }, function () {
                throw new Error(r.ok ? 'Respuesta inválida' : ('HTTP ' + r.status));
            });
        });
    }

    function toast(icon, title, text) {
        if (window.Swal) {
            Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false,
                timer: 2800, timerProgressBar: true
            }).fire({ icon: icon, title: title, text: text || '' });
            return;
        }
        window.alert((title || '') + (text ? '\n' + text : ''));
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function selectedList() {
        return Object.keys(state.selected).map(function (k) { return state.selected[k]; });
    }

    function ctaPretty(codigo) {
        var s = String(codigo == null ? '' : codigo).trim();
        if (!s) return '';
        if (!/sys/i.test(s)) return s;
        var out = s.replace(/_?SYS/gi, '').replace(/^0+/, '').replace(/^[\s\-_]+/, '');
        return out || s.replace(/\D+/g, '').replace(/^0+/, '') || s;
    }

    function ctaKey(codigo) {
        return ctaPretty(codigo);
    }

    function applySeleccion(cuentas) {
        state.selected = {};
        (cuentas || []).forEach(function (c) {
            var k = ctaKey(c.codigo || c.cuenta_codigo);
            if (!k) return;
            state.selected[k] = {
                codigo: k,
                nombre: c.nombre || c.cuenta_nombre || ''
            };
        });
    }

    function cuentaRowHtml(c, on) {
        var k = ctaKey(c.codigo);
        return '<label class="cc-cta-item"><input type="checkbox" data-grp-cta="1" value="' + escapeHtml(k) + '"' +
            ' data-nombre="' + escapeHtml(c.nombre || '') + '"' + (on ? ' checked' : '') + '>' +
            '<span class="cc-cta-code">' + escapeHtml(ctaPretty(k)) + '</span>' +
            '<span class="cc-cta-name">' + escapeHtml(c.nombre || '') + '</span></label>';
    }

    function empresaNombre(codigo) {
        var found = state.empresas.filter(function (e) {
            return String(e.codigo || '').toLowerCase() === String(codigo || '').toLowerCase();
        })[0];
        return found ? found.nombre : String(codigo || '').toUpperCase();
    }

    function renderKpis() {
        setText('kpi-grp-emp', state.empresa ? empresaNombre(state.empresa) : '—');
        setText('kpi-grp-ctas', state.empresa ? String((state.cuentasAll.length || state.cuentas.length)) : '—');
        setText('kpi-grp-n', state.empresa ? String(state.grupos.length) : '—');
        setText('kpi-grp-sel', state.empresa ? String(selectedList().length) : '—');
    }

    function maskLabel(id, nombre) {
        var code = String(id || '').trim();
        var nom = String(nombre || '').trim();
        if (code && nom && nom !== code) return code + ' · ' + nom;
        return nom || code || 'Sin GroupMask';
    }

    function masksDisponibles() {
        var map = {};
        [
            { id: '1', nombre: 'Activo' },
            { id: '2', nombre: 'Pasivo' },
            { id: '3', nombre: 'Capital' },
            { id: '4', nombre: 'Ingresos' },
            { id: '5', nombre: 'Costo de ventas' },
            { id: '6', nombre: 'Gastos' },
            { id: '7', nombre: 'Otros ingresos y gastos' },
            { id: '8', nombre: 'Otros' },
            { id: '9', nombre: 'GroupMask 9' },
            { id: '10', nombre: 'GroupMask 10' }
        ].forEach(function (m) { map[m.id] = m; });
        (state.agrupaciones || []).forEach(function (g) {
            var id = String(g.id || '').trim();
            if (!id) return;
            var nom = String(g.nombre || '').trim();
            map[id] = { id: id, nombre: (nom && nom !== id) ? nom : (map[id] ? map[id].nombre : id) };
        });
        (state.cuentasAll.length ? state.cuentasAll : state.cuentas).forEach(function (c) {
            var id = String(c.grupo_id || '').trim();
            if (!id) return;
            if (!map[id] || map[id].nombre === id) {
                map[id] = { id: id, nombre: c.grupo || (map[id] && map[id].nombre) || id };
            }
        });
        return Object.keys(map).sort(function (a, b) {
            var na = Number(a);
            var nb = Number(b);
            if (!isNaN(na) && !isNaN(nb)) return na - nb;
            return a.localeCompare(b);
        }).map(function (k) { return map[k]; });
    }

    function fillMaskFilter() {
        var sel = document.getElementById('grp-mask');
        var masks = masksDisponibles();
        var current = String(state.mask || '');
        if (!sel) return;
        sel.innerHTML = '<option value="">Todos los GroupMask</option>' + masks.map(function (m) {
            return '<option value="' + escapeHtml(m.id) + '"' + (current === String(m.id) ? ' selected' : '') + '>' +
                escapeHtml(maskLabel(m.id, m.nombre)) + '</option>';
        }).join('');
        sel.value = current;
    }

    function setMask(mask) {
        state.mask = String(mask || '');
        var sel = document.getElementById('grp-mask');
        if (sel && sel.value !== state.mask) sel.value = state.mask;
        if (!state.mask && state.cuentasAll.length) {
            state.cuentas = state.cuentasAll.slice();
        }
        fillMaskFilter();
        renderCuentas();
        if (!state.empresa || !state.mask) return;
        getJSON('/CentrosCostos/api/cuentas?todas=1&empresa=' + encodeURIComponent(state.empresa) +
            '&group_mask=' + encodeURIComponent(state.mask)).then(function (json) {
            if (String(state.mask) !== String(mask || '')) return;
            var rows = json.cuentas || [];
            state.cuentas = rows;
            if (json.agrupaciones && json.agrupaciones.length) {
                state.agrupaciones = json.agrupaciones;
            }
            fillMaskFilter();
            renderCuentas();
        }).catch(function () {});
    }

    function normSearch(s) {
        var t = String(s == null ? '' : s).toLowerCase();
        if (t.normalize) t = t.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        return t.replace(/\s+/g, ' ').trim();
    }

    function searchQuery() {
        return normSearch(((document.getElementById('grp-cta-q') || {}).value || ''));
    }

    function cuentaMatch(c, q) {
        if (!q) return true;
        return normSearch(
            ctaPretty(c.codigo) + ' ' + (c.codigo || '') + ' ' + (c.nombre || '') + ' ' + (c.grupo || '') + ' ' + (c.grupo_id || '')
        ).indexOf(q) !== -1;
    }

    function cuentasVisibles() {
        var q = searchQuery();
        var mask = String(state.mask || '');
        var source = (mask && state.cuentas.length) ? state.cuentas : (state.cuentasAll.length ? state.cuentasAll : state.cuentas);
        return source.filter(function (c) { return cuentaMatch(c, q); });
    }

    function renderEmpresas() {
        var grid = document.getElementById('grp-empresa-grid');
        if (!grid) return;
        if (!state.empresas.length) {
            grid.innerHTML = '<div class="cc-empty" style="grid-column:1/-1">No se pudieron leer empresas de AutinApi</div>';
            return;
        }
        var selected = String(state.empresa || '').toLowerCase();
        grid.innerHTML = state.empresas.map(function (e) {
            var code = String(e.codigo || '').toLowerCase();
            var on = selected === code && selected !== '';
            var n = Number(e.grupos || 0);
            var badgeCls = n ? 'cc-badge-ink' : 'cc-badge-solo_revision';
            var pickCls = on ? 'cc-btn-ink' : (n ? 'cc-btn-ink' : '');
            var pickLabel = on ? 'Seleccionada' : 'Seleccionar';
            return '<article class="cc-ciclo-card is-pickable' + (on ? ' is-on' : '') + (n ? ' has-asig' : '') + '" data-emp="' + escapeHtml(e.codigo) + '" role="button" tabindex="0">' +
                '<div class="top"><div><div class="code">SAP</div><h3>' + escapeHtml(e.nombre || String(e.codigo).toUpperCase()) + '</h3></div>' +
                '<span class="cc-badge ' + badgeCls + '">' + (n ? (n + (n === 1 ? ' grupo' : ' grupos')) : 'Sin grupos') + '</span></div>' +
                '<div class="cc-ciclo-obs">Cuentas de ' + escapeHtml(e.nombre || e.codigo) + ' para armar agrupaciones propias.</div>' +
                '<div class="actions"><span class="text-muted" style="font-size:.75rem">' + (on ? 'Catálogo activo' : 'Clic para cargar cuentas') + '</span>' +
                '<span class="cc-btn ' + pickCls + ' cc-card-pick">' + pickLabel + '</span></div></article>';
        }).join('');
        grid.querySelectorAll('.cc-ciclo-card[data-emp]').forEach(function (card) {
            var pick = function () { setEmpresa(card.getAttribute('data-emp')); };
            card.addEventListener('click', pick);
            card.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); pick(); }
            });
        });
    }

    function renderGrupos() {
        var box = document.getElementById('grp-list');
        var meta = document.getElementById('grp-list-meta');
        if (!box) return;
        var rows = state.grupos.slice();
        if (meta) meta.textContent = state.grupos.length ? (state.grupos.length + (state.grupos.length === 1 ? ' grupo' : ' grupos')) : 'Sin grupos';
        var prev = nombreEl();
        var hadFocus = !!(prev && document.activeElement === prev);
        var selStart = hadFocus ? prev.selectionStart : null;
        var selEnd = hadFocus ? prev.selectionEnd : null;
        var naming = !!state.creating && !state.editingId && !state.named;
        var draftOn = !!state.creating && !state.editingId && !!state.named;
        var html;
        if (naming) {
            html = '<div class="cc-cc-item is-on is-editing cc-grupos-nombre-row" data-nuevo="1" role="option" aria-selected="true">' +
                '<input id="grp-nombre" class="cc-input" type="text" maxlength="180" placeholder="Nombre de la agrupación…" autocomplete="off">' +
                '<button type="button" class="cc-grupos-confirm" data-confirmar-nombre="1" title="Confirmar nombre" aria-label="Confirmar nombre">' +
                '<i class="fa-solid fa-plus"></i></button></div>';
        } else {
            html = '<div class="cc-cc-item" data-nuevo="1" role="option" aria-selected="false">' +
                '<span><span class="cc-cc-name">Crear una nueva</span>' +
                '<span class="cc-cc-code">Ponle un nombre y elige cuentas a la derecha</span></span>' +
                '<button type="button" class="cc-grupos-confirm" data-nuevo="1" title="Crear una nueva" aria-label="Crear una nueva">' +
                '<i class="fa-solid fa-plus"></i></button></div>';
        }
        if (draftOn) {
            var nDraft = selectedList().length;
            html += '<button type="button" class="cc-cc-item is-on" data-draft="1" role="option" aria-selected="true">' +
                '<span><span class="cc-cc-name">' + escapeHtml(state.nombre || 'Sin nombre') + '</span>' +
                '<span class="cc-cc-code">' + nDraft + (nDraft === 1 ? ' cuenta' : ' cuentas') + '</span></span>' +
                '<span class="cc-badge cc-badge-ink">Nueva</span></button>';
        }
        if (rows.length) {
            html += rows.map(function (g) {
                var on = Number(state.editingId) === Number(g.id);
                var n = (g.cuentas || []).length;
                return '<button type="button" class="cc-cc-item' + (on ? ' is-on' : '') + '" data-grupo="' + g.id + '" role="option" aria-selected="' + (on ? 'true' : 'false') + '" title="Clic para editar">' +
                    '<span><span class="cc-cc-name">' + escapeHtml(g.nombre) + '</span>' +
                    '<span class="cc-cc-code">' + n + (n === 1 ? ' cuenta' : ' cuentas') + '</span></span>' +
                    '<span class="cc-badge ' + (n ? 'cc-badge-ink' : 'cc-badge-solo_revision') + '">' + (on ? 'Editando' : n) + '</span>' +
                    '</button>';
            }).join('');
        }
        box.innerHTML = html;
        writeNombre(state.nombre);
        bindNombre();
        var el = nombreEl();
        if (el && hadFocus) {
            el.focus();
            try { if (selStart != null) el.setSelectionRange(selStart, selEnd); } catch (e) {}
        }
    }

    function canPickCuentas() {
        if (state.editingId) return true;
        return !!(state.creating && state.named && String(state.nombre || '').trim());
    }

    function syncEditorLock() {
        var locked = !canPickCuentas();
        var editor = document.getElementById('grp-editor');
        if (editor) editor.classList.toggle('is-locked', locked);
        ['grp-mask', 'grp-cta-q', 'grp-cta-todas', 'grp-guardar', 'grp-guardar-salir'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.disabled = locked;
        });
    }

    function renderCuentas() {
        var box = document.getElementById('grp-cta-list');
        var meta = document.getElementById('grp-cta-meta');
        var todas = document.getElementById('grp-cta-todas');
        if (!box) return;
        syncEditorLock();
        if (!canPickCuentas()) {
            var hint = state.creating
                ? 'Escribe el nombre a la izquierda y pulsa + para confirmar. Después puedes marcar cuentas aquí.'
                : 'Elige una agrupación o crea una nueva a la izquierda.';
            box.innerHTML = '<div class="cc-empty">' + hint + '</div>';
            if (meta) meta.textContent = state.creating ? 'Confirma el nombre' : '—';
            if (todas) todas.checked = false;
            renderKpis();
            return;
        }
        var catalog = cuentasVisibles();
        catalog.forEach(function (c) {
            var k = ctaKey(c.codigo);
            if (state.selected[k] && c.nombre) state.selected[k].nombre = c.nombre;
        });
        var q = searchQuery();
        var selectedRows = selectedList().filter(function (c) { return cuentaMatch(c, q); });
        var seen = {};
        selectedRows.forEach(function (c) { seen[ctaKey(c.codigo)] = true; });
        var rest = catalog.filter(function (c) { return !seen[ctaKey(c.codigo)]; });
        var nSel = selectedList().length;
        if (meta) meta.textContent = nSel + ' en la agrupación · ' + catalog.length + ' visibles';
        if (!selectedRows.length && !rest.length) {
            box.innerHTML = '<div class="cc-empty">' + escapeHtml(
                q ? 'Sin coincidencias para “' + ((document.getElementById('grp-cta-q') || {}).value || '') + '”' :
                ((state.cuentasAll.length || state.cuentas.length)
                    ? (state.mask ? 'Sin cuentas para este GroupMask' : 'Sin coincidencias')
                    : (state.sapMensaje || 'Cargando cuentas SAP…'))
            ) + '</div>';
            if (todas) todas.checked = false;
            renderKpis();
            return;
        }
        var html = '';
        if (selectedRows.length) {
            html += '<div class="cc-cta-group"><div class="cc-cta-group-h">En esta agrupación · ' + selectedRows.length + '</div>' +
                selectedRows.map(function (c) { return cuentaRowHtml(c, true); }).join('') + '</div>';
        }
        if (rest.length) {
            var groups = {};
            rest.forEach(function (c) {
                var g = maskLabel(c.grupo_id, c.grupo);
                if (!groups[g]) groups[g] = [];
                groups[g].push(c);
            });
            html += Object.keys(groups).map(function (g) {
                return '<div class="cc-cta-group"><div class="cc-cta-group-h">GroupMask ' + escapeHtml(g) + '</div>' +
                    groups[g].map(function (c) { return cuentaRowHtml(c, !!state.selected[ctaKey(c.codigo)]); }).join('') + '</div>';
            }).join('');
        }
        if (!html) {
            box.innerHTML = '<div class="cc-empty">' + (state.sapMensaje || 'Sin cuentas en este catálogo') + '</div>';
            if (todas) todas.checked = false;
            renderKpis();
            return;
        }
        box.innerHTML = html;
        if (todas) {
            var boxes = box.querySelectorAll('[data-grp-cta]');
            var nOn = 0;
            boxes.forEach(function (i) { if (i.checked) nOn += 1; });
            todas.checked = boxes.length > 0 && nOn === boxes.length;
        }
        renderKpis();
    }

    function syncSelectedFromDom() {
        document.querySelectorAll('#grp-cta-list [data-grp-cta]').forEach(function (i) {
            var k = ctaKey(i.value);
            if (!k) return;
            if (i.checked) {
                state.selected[k] = {
                    codigo: k,
                    nombre: i.getAttribute('data-nombre') || ''
                };
            } else {
                delete state.selected[k];
            }
        });
        renderKpis();
        var meta = document.getElementById('grp-cta-meta');
        if (meta) {
            meta.textContent = selectedList().length + ' en la agrupación · ' +
                document.querySelectorAll('#grp-cta-list [data-grp-cta]').length + ' visibles';
        }
        var draftCode = document.querySelector('#grp-list [data-draft] .cc-cc-code');
        if (draftCode) {
            var n = selectedList().length;
            draftCode.textContent = n + (n === 1 ? ' cuenta' : ' cuentas');
        }
    }

    function nombreEl() {
        return document.getElementById('grp-nombre');
    }

    function readNombre() {
        var el = nombreEl();
        state.nombre = el ? String(el.value || '').trim() : String(state.nombre || '').trim();
        return state.nombre;
    }

    function writeNombre(value) {
        state.nombre = String(value || '');
        var el = nombreEl();
        if (el) el.value = state.nombre;
    }

    function focusNombre(select) {
        var el = nombreEl();
        if (!el) return;
        el.focus();
        if (select && typeof el.select === 'function') el.select();
    }

    function setEditorTitle() {
        var nom = String(state.nombre || '').trim();
        if (nom) {
            setText('grp-editor-title', 'Seleccionar cuentas · ' + nom);
            return;
        }
        setText('grp-editor-title', 'Seleccionar cuentas');
    }

    function bindNombre() {
        var nom = nombreEl();
        if (!nom || nom.getAttribute('data-bound') === '1') return;
        nom.setAttribute('data-bound', '1');
        nom.addEventListener('input', function () {
            state.nombre = String(nom.value || '').trim();
            setEditorTitle();
        });
        nom.addEventListener('keydown', function (ev) {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                confirmNombre();
            }
        });
        nom.addEventListener('click', function (ev) { ev.stopPropagation(); });
    }

    function resetEditor() {
        state.editingId = 0;
        state.creating = false;
        state.named = false;
        state.clave = '';
        state.selected = {};
        writeNombre('');
        var q = document.getElementById('grp-cta-q');
        if (q) q.value = '';
        setEditorTitle();
        var del = document.getElementById('grp-borrar');
        if (del) del.hidden = true;
        renderGrupos();
        renderCuentas();
    }

    function confirmNombre() {
        var nom = readNombre();
        if (!nom) {
            toast('warning', 'Ponle un nombre', 'Escríbelo y pulsa + para confirmar.');
            focusNombre(true);
            return;
        }
        state.creating = true;
        state.named = true;
        state.editingId = 0;
        setEditorTitle();
        renderGrupos();
        renderCuentas();
    }

    function startCreate() {
        if (state.creating && !state.editingId && !state.named) {
            focusNombre(false);
            return;
        }
        state.editingId = 0;
        state.creating = true;
        state.named = false;
        state.clave = '';
        state.selected = {};
        writeNombre('');
        var q = document.getElementById('grp-cta-q');
        if (q) q.value = '';
        setEditorTitle();
        var del = document.getElementById('grp-borrar');
        if (del) del.hidden = true;
        renderGrupos();
        renderCuentas();
        focusNombre(true);
    }

    function editGrupo(id) {
        var g = state.grupos.filter(function (x) { return Number(x.id) === Number(id); })[0];
        if (!g) return;
        state.creating = false;
        state.named = true;
        state.editingId = g.id;
        state.clave = g.clave || '';
        writeNombre(g.nombre || '');
        applySeleccion(g.cuentas || []);
        var q = document.getElementById('grp-cta-q');
        if (q) q.value = '';
        state.mask = '';
        fillMaskFilter();
        setEditorTitle();
        var del = document.getElementById('grp-borrar');
        if (del) del.hidden = false;
        renderGrupos();
        renderCuentas();
    }

    function rememberGrupos(empresa, grupos) {
        var emp = String(empresa || '').toLowerCase();
        state.gruposCache[emp] = grupos || [];
        if (String(state.empresa || '').toLowerCase() === emp) {
            state.grupos = state.gruposCache[emp];
            renderGrupos();
            if (state.editingId) {
                var g = state.grupos.filter(function (x) { return Number(x.id) === Number(state.editingId); })[0];
                if (g && !selectedList().length && (g.cuentas || []).length) {
                    applySeleccion(g.cuentas);
                    renderCuentas();
                }
            }
        }
        state.empresas = state.empresas.map(function (e) {
            if (String(e.codigo || '').toLowerCase() === emp) {
                e.grupos = state.gruposCache[emp].length;
            }
            return e;
        });
        renderEmpresas();
    }

    function ingestGruposAll(rows) {
        state.gruposCache = {};
        (rows || []).forEach(function (g) {
            var emp = String(g.empresa || '').toLowerCase();
            if (!emp) return;
            if (!state.gruposCache[emp]) state.gruposCache[emp] = [];
            state.gruposCache[emp].push(g);
        });
        if (state.empresa && state.gruposCache[state.empresa]) {
            state.grupos = state.gruposCache[state.empresa];
            renderGrupos();
            if (state.editingId) {
                var g = state.grupos.filter(function (x) { return Number(x.id) === Number(state.editingId); })[0];
                if (g && !selectedList().length && (g.cuentas || []).length) {
                    applySeleccion(g.cuentas);
                    renderCuentas();
                }
            }
        }
        if (state.empresas.length) {
            state.empresas = state.empresas.map(function (e) {
                var emp = String(e.codigo || '').toLowerCase();
                if (state.gruposCache[emp]) e.grupos = state.gruposCache[emp].length;
                return e;
            });
            renderEmpresas();
        }
    }

    function setEmpresa(codigo) {
        var next = String(codigo || '').toLowerCase();
        if (!next || next === state.empresa) return;
        var seq = ++state.loadSeq;
        state.empresa = next;
        state.cuentas = [];
        state.cuentasAll = [];
        state.agrupaciones = [];
        state.mask = '';
        state.sapOk = false;
        state.sapMensaje = '';
        state.grupos = state.gruposCache[next] || [];
        fillMaskFilter();
        resetEditor();
        if (!state.gruposCache[next]) {
            var gList = document.getElementById('grp-list');
            if (gList) gList.innerHTML = '<div class="cc-empty">Cargando grupos…</div>';
        }
        var ws = document.getElementById('grp-workspace');
        if (ws) ws.hidden = false;
        renderEmpresas();
        renderKpis();
        var list = document.getElementById('grp-cta-list');
        if (list) list.innerHTML = '<div class="cc-empty">Cargando cuentas SAP…</div>';
        setFlag('Cargando…', false);

        getJSON('/CentrosCostos/api/grupos?empresa=' + encodeURIComponent(next)).then(function (json) {
            if (seq !== state.loadSeq) return;
            rememberGrupos(next, json.grupos || []);
        }).catch(function () {
            if (seq !== state.loadSeq) return;
            if (!state.grupos.length) {
                var gList = document.getElementById('grp-list');
                if (gList) gList.innerHTML = '<div class="cc-empty">No se pudieron cargar las agrupaciones</div>';
            }
        });

        getJSON('/CentrosCostos/api/cuentas?todas=1&empresa=' + encodeURIComponent(next)).then(function (json) {
            if (seq !== state.loadSeq) return;
            state.cuentas = json.cuentas || [];
            state.cuentasAll = state.cuentas.slice();
            state.agrupaciones = json.agrupaciones || [];
            state.sapOk = !!json.ok;
            state.sapMensaje = json.mensaje || '';
            setFlag(state.sapOk ? (state.cuentasAll.length + ' cuentas') : (state.sapMensaje || 'Sin catálogo'), state.sapOk);
            fillMaskFilter();
            renderCuentas();
            renderKpis();
        }).catch(function (err) {
            if (seq !== state.loadSeq) return;
            setFlag(err && err.message ? err.message : 'Error de red', false);
            if (list) list.innerHTML = '<div class="cc-empty">No se pudieron cargar las cuentas</div>';
        });
    }

    function setFlag(text, ok) {
        var flag = document.getElementById('grp-sap-flag');
        if (!flag) return;
        flag.textContent = text;
        flag.className = 'cc-sap-flag' + (ok ? '' : ' off');
    }

    function payloadGrupo() {
        var data = {
            empresa: state.empresa,
            nombre: readNombre(),
            cuentas: selectedList()
        };
        if (state.editingId && state.clave) {
            data.clave = String(state.clave).trim();
        }
        return data;
    }

    function guardar() {
        if (!state.empresa) {
            toast('warning', 'Elige una empresa', '');
            return;
        }
        if (state.creating && !state.named) {
            toast('warning', 'Confirma el nombre', 'Pulsa + para dejar la agrupación como un ítem.');
            focusNombre(true);
            return;
        }
        syncSelectedFromDom();
        var data = payloadGrupo();
        if (!data.nombre) {
            toast('warning', 'Ponle un nombre', 'Escribe el nombre de la agrupación.');
            var el = nombreEl();
            if (el) el.focus();
            return;
        }
        if (!data.cuentas.length) {
            toast('warning', 'Sin cuentas', 'Selecciona al menos una cuenta.');
            return;
        }
        enviarGrupo(data, !!salir);
    }

    function enviarGrupo(data, salir) {
        var btns = [document.getElementById('grp-guardar'), document.getElementById('grp-guardar-salir')];
        btns.forEach(function (btn) { if (btn) btn.disabled = true; });
        var req = state.editingId
            ? sendJSON('/CentrosCostos/api/grupos/' + state.editingId, 'PUT', data)
            : sendJSON('/CentrosCostos/api/grupos', 'POST', data);
        req.then(function (json) {
            var saved = json.grupo;
            if (!saved || !saved.id) throw new Error(json.message || 'No se recibió el grupo guardado.');
            if (state.editingId) {
                state.grupos = state.grupos.map(function (g) {
                    return Number(g.id) === Number(saved.id) ? saved : g;
                });
            } else {
                state.grupos = [saved].concat(state.grupos);
            }
            state.empresas = state.empresas.map(function (e) {
                if (String(e.codigo).toLowerCase() === state.empresa) {
                    e.grupos = state.grupos.length;
                }
                return e;
            });
            toast('success', state.editingId ? 'Agrupación actualizada' : 'Agrupación guardada', saved.nombre || saved.clave);
            rememberGrupos(state.empresa, state.grupos);
            if (salir) {
                resetEditor();
            } else {
                editGrupo(saved.id);
            }
        }).catch(function (err) {
            toast('error', 'No se guardó', err && err.message ? err.message : 'Error de red');
        }).then(function () {
            btns.forEach(function (btn) { if (btn) btn.disabled = false; });
            syncEditorLock();
        });
    }

    function borrar() {
        if (!state.editingId) return;
        var g = state.grupos.filter(function (x) { return Number(x.id) === Number(state.editingId); })[0];
        var go = function () {
            return sendJSON('/CentrosCostos/api/grupos/' + state.editingId, 'DELETE').then(function () {
                state.grupos = state.grupos.filter(function (x) { return Number(x.id) !== Number(state.editingId); });
                state.empresas = state.empresas.map(function (e) {
                    if (String(e.codigo).toLowerCase() === state.empresa) {
                        e.grupos = state.grupos.length;
                    }
                    return e;
                });
                toast('success', 'Grupo eliminado', g ? g.nombre || g.clave : '');
                rememberGrupos(state.empresa, state.grupos);
                resetEditor();
            }).catch(function (err) {
                toast('error', 'No se eliminó', err && err.message ? err.message : 'Error de red');
            });
        };
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Eliminar ' + (g ? g.clave : 'grupo'),
                text: 'Se quita la agrupación. Las cuentas SAP no se modifican.',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#b91c1c'
            }).then(function (r) { if (r.isConfirmed) go(); });
            return;
        }
        if (window.confirm('¿Eliminar este grupo?')) go();
    }

    function bind() {
        var gList = document.getElementById('grp-list');
        if (gList) {
            gList.addEventListener('click', function (ev) {
                if (ev.target.closest('input, textarea, select')) return;
                var ok = ev.target.closest('[data-confirmar-nombre]');
                if (ok && gList.contains(ok)) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    confirmNombre();
                    return;
                }
                var nuevo = ev.target.closest('[data-nuevo]');
                if (nuevo && gList.contains(nuevo)) {
                    ev.preventDefault();
                    startCreate();
                    return;
                }
                if (ev.target.closest('[data-draft]')) {
                    ev.preventDefault();
                    return;
                }
                var btn = ev.target.closest('[data-grupo]');
                if (!btn || !gList.contains(btn)) return;
                ev.preventDefault();
                var id = Number(btn.getAttribute('data-grupo'));
                if (Number(state.editingId) === id) return;
                editGrupo(id);
            });
        }
        var ctaQ = document.getElementById('grp-cta-q');
        if (ctaQ) {
            var runSearch = function () { renderCuentas(); };
            ctaQ.addEventListener('input', runSearch);
            ctaQ.addEventListener('search', runSearch);
            ctaQ.addEventListener('keyup', runSearch);
        }
        var maskSel = document.getElementById('grp-mask');
        if (maskSel) {
            maskSel.addEventListener('change', function () {
                syncSelectedFromDom();
                setMask(maskSel.value || '');
            });
        }
        var list = document.getElementById('grp-cta-list');
        if (list) {
            list.addEventListener('change', function (ev) {
                if (ev.target && ev.target.getAttribute('data-grp-cta')) {
                    syncSelectedFromDom();
                    var todas = document.getElementById('grp-cta-todas');
                    if (todas) {
                        var boxes = list.querySelectorAll('[data-grp-cta]');
                        var nOn = 0;
                        boxes.forEach(function (i) { if (i.checked) nOn += 1; });
                        todas.checked = boxes.length > 0 && nOn === boxes.length;
                    }
                }
            });
        }
        var todas = document.getElementById('grp-cta-todas');
        if (todas) {
            todas.addEventListener('change', function () {
                document.querySelectorAll('#grp-cta-list [data-grp-cta]').forEach(function (i) {
                    i.checked = todas.checked;
                });
                syncSelectedFromDom();
            });
        }
        bindNombre();
        var nuevo = document.getElementById('grp-nuevo');
        if (nuevo) nuevo.addEventListener('click', startCreate);
        var cancelar = document.getElementById('grp-cancelar');
        if (cancelar) cancelar.addEventListener('click', resetEditor);
        var guardarBtn = document.getElementById('grp-guardar');
        if (guardarBtn) guardarBtn.addEventListener('click', function () { guardar(false); });
        var guardarSalirBtn = document.getElementById('grp-guardar-salir');
        if (guardarSalirBtn) guardarSalirBtn.addEventListener('click', function () { guardar(true); });
        var borrarBtn = document.getElementById('grp-borrar');
        if (borrarBtn) borrarBtn.addEventListener('click', borrar);
    }

    var CCGrupos = {
        boot: function (cfg) {
            if (cfg && cfg.csrf) csrf = cfg.csrf;
            bind();
            getJSON('/CentrosCostos/api/empresas').then(function (json) {
                state.empresas = json.empresas && json.empresas.length ? json.empresas : EMPRESAS_FALLBACK;
                renderEmpresas();
            }).catch(function () {
                state.empresas = EMPRESAS_FALLBACK;
                renderEmpresas();
            });
            getJSON('/CentrosCostos/api/grupos').then(function (json) {
                ingestGruposAll(json.grupos || []);
            }).catch(function () {});
        }
    };

    window.CCGrupos = CCGrupos;
})(window, document);
