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
        sapMensaje: ''
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
                if (!r.ok) throw new Error(json.message || ('HTTP ' + r.status));
                return json;
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

    function cuentasVisibles() {
        var q = ((document.getElementById('grp-cta-q') || {}).value || '').toLowerCase().trim();
        var mask = String(state.mask || '');
        var source = (mask && state.cuentas.length) ? state.cuentas : (state.cuentasAll.length ? state.cuentasAll : state.cuentas);
        return source.filter(function (c) {
            return ((c.codigo || '') + ' ' + (c.nombre || '') + ' ' + (c.grupo || '') + ' ' + (c.grupo_id || '')).toLowerCase().indexOf(q) !== -1;
        });
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
        if (!rows.length) {
            box.innerHTML = '<div class="cc-empty">Aún no hay grupos. Crea el primero.</div>';
            return;
        }
        box.innerHTML = rows.map(function (g) {
            var on = Number(state.editingId) === Number(g.id);
            var n = (g.cuentas || []).length;
            return '<button type="button" class="cc-cc-item' + (on ? ' is-on' : '') + '" data-grupo="' + g.id + '" role="option" aria-selected="' + (on ? 'true' : 'false') + '">' +
                '<span><span class="cc-cc-code">' + escapeHtml(g.clave) + '</span>' +
                '<span class="cc-cc-name">' + escapeHtml(g.nombre) + '</span></span>' +
                '<span class="cc-badge ' + (n ? 'cc-badge-ink' : 'cc-badge-solo_revision') + '">' + n + (n === 1 ? ' cuenta' : ' cuentas') + '</span>' +
                '</button>';
        }).join('');
        box.querySelectorAll('[data-grupo]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                editGrupo(Number(btn.getAttribute('data-grupo')));
            });
        });
    }

    function renderCuentas() {
        var box = document.getElementById('grp-cta-list');
        var meta = document.getElementById('grp-cta-meta');
        var todas = document.getElementById('grp-cta-todas');
        if (!box) return;
        var rows = cuentasVisibles();
        var nSel = selectedList().length;
        if (meta) meta.textContent = nSel + ' seleccionadas · ' + rows.length + ' visibles';
        if (!rows.length) {
            box.innerHTML = '<div class="cc-empty">' + ((state.cuentasAll.length || state.cuentas.length) ? (state.mask ? 'Sin cuentas para este GroupMask' : 'Sin coincidencias') : (state.sapMensaje || 'Sin cuentas en este catálogo')) + '</div>';
            if (todas) todas.checked = false;
            renderKpis();
            return;
        }
        var groups = {};
        rows.forEach(function (c) {
            var g = maskLabel(c.grupo_id, c.grupo);
            if (!groups[g]) groups[g] = [];
            groups[g].push(c);
        });
        box.innerHTML = Object.keys(groups).map(function (g) {
            return '<div class="cc-cta-group"><div class="cc-cta-group-h">GroupMask ' + escapeHtml(g) + '</div>' +
                groups[g].map(function (c) {
                    var on = !!state.selected[String(c.codigo)];
                    return '<label class="cc-cta-item"><input type="checkbox" data-grp-cta="1" value="' + escapeHtml(c.codigo) + '"' +
                        ' data-nombre="' + escapeHtml(c.nombre || '') + '"' + (on ? ' checked' : '') + '>' +
                        '<span class="cc-cta-code">' + escapeHtml(c.codigo) + '</span>' +
                        '<span class="cc-cta-name">' + escapeHtml(c.nombre || '') + '</span></label>';
                }).join('') + '</div>';
        }).join('');
        if (todas) {
            var nOn = 0;
            box.querySelectorAll('[data-grp-cta]').forEach(function (i) { if (i.checked) nOn += 1; });
            todas.checked = rows.length > 0 && nOn === rows.length;
        }
        renderKpis();
    }

    function syncSelectedFromDom() {
        document.querySelectorAll('#grp-cta-list [data-grp-cta]').forEach(function (i) {
            if (i.checked) {
                state.selected[i.value] = {
                    codigo: i.value,
                    nombre: i.getAttribute('data-nombre') || ''
                };
            } else {
                delete state.selected[i.value];
            }
        });
        renderKpis();
        var meta = document.getElementById('grp-cta-meta');
        if (meta) {
            var visibles = document.querySelectorAll('#grp-cta-list [data-grp-cta]').length;
            meta.textContent = selectedList().length + ' seleccionadas · ' + visibles + ' visibles';
        }
    }

    function resetEditor() {
        state.editingId = 0;
        state.clave = '';
        state.nombre = '';
        state.selected = {};
        var q = document.getElementById('grp-cta-q');
        if (q) q.value = '';
        setText('grp-editor-title', 'Cuentas');
        var del = document.getElementById('grp-borrar');
        if (del) del.hidden = true;
        renderGrupos();
        renderCuentas();
    }

    function editGrupo(id) {
        var g = state.grupos.filter(function (x) { return Number(x.id) === Number(id); })[0];
        if (!g) return;
        state.editingId = g.id;
        state.clave = g.clave || '';
        state.nombre = g.nombre || '';
        state.selected = {};
        (g.cuentas || []).forEach(function (c) {
            state.selected[String(c.codigo)] = { codigo: c.codigo, nombre: c.nombre || '' };
        });
        setText('grp-editor-title', 'Cuentas · ' + (g.clave || ''));
        var del = document.getElementById('grp-borrar');
        if (del) del.hidden = false;
        renderGrupos();
        renderCuentas();
    }

    function setEmpresa(codigo) {
        var next = String(codigo || '').toLowerCase();
        if (!next || next === state.empresa) return;
        state.empresa = next;
        state.grupos = [];
        state.cuentas = [];
        state.cuentasAll = [];
        state.agrupaciones = [];
        state.mask = '';
        state.sapOk = false;
        state.sapMensaje = '';
        fillMaskFilter();
        resetEditor();
        var ws = document.getElementById('grp-workspace');
        if (ws) ws.hidden = false;
        var btnNuevo = document.getElementById('grp-nuevo');
        if (btnNuevo) btnNuevo.disabled = false;
        renderEmpresas();
        renderKpis();
        var list = document.getElementById('grp-cta-list');
        if (list) list.innerHTML = '<div class="cc-empty">Cargando cuentas SAP…</div>';
        var gList = document.getElementById('grp-list');
        if (gList) gList.innerHTML = '<div class="cc-empty">Cargando grupos…</div>';
        setFlag('Cargando…', false);
        Promise.all([
            getJSON('/CentrosCostos/api/cuentas?todas=1&empresa=' + encodeURIComponent(next)),
            getJSON('/CentrosCostos/api/grupos?empresa=' + encodeURIComponent(next))
        ]).then(function (res) {
            state.cuentas = res[0].cuentas || [];
            state.cuentasAll = state.cuentas.slice();
            state.agrupaciones = res[0].agrupaciones || [];
            state.sapOk = !!res[0].ok;
            state.sapMensaje = res[0].mensaje || '';
            state.grupos = res[1].grupos || [];
            setFlag(state.sapOk ? (state.cuentasAll.length + ' cuentas') : (state.sapMensaje || 'Sin catálogo'), state.sapOk);
            fillMaskFilter();
            renderGrupos();
            renderCuentas();
            renderKpis();
        }).catch(function (err) {
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
        return {
            empresa: state.empresa,
            clave: String(state.clave || '').trim(),
            nombre: String(state.nombre || '').trim(),
            cuentas: selectedList()
        };
    }

    function pedirIdentidad(defaults) {
        defaults = defaults || {};
        return new Promise(function (resolve, reject) {
            if (window.Swal) {
                Swal.fire({
                    title: 'Identificar grupo',
                    html: '<input id="swal-grp-clave" class="swal2-input" maxlength="40" placeholder="Clave / ID" value="' + escapeHtml(defaults.clave || '') + '">' +
                        '<input id="swal-grp-nombre" class="swal2-input" maxlength="180" placeholder="Nombre" value="' + escapeHtml(defaults.nombre || '') + '">',
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Continuar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: function () {
                        var clave = ((document.getElementById('swal-grp-clave') || {}).value || '').trim();
                        var nombre = ((document.getElementById('swal-grp-nombre') || {}).value || '').trim();
                        if (!clave || !nombre) {
                            Swal.showValidationMessage('Escribe clave y nombre.');
                            return false;
                        }
                        return { clave: clave, nombre: nombre };
                    }
                }).then(function (r) {
                    if (r.isConfirmed && r.value) resolve(r.value);
                    else reject(new Error('cancelado'));
                });
                return;
            }
            var clave = window.prompt('Clave / ID', defaults.clave || '');
            if (clave == null) { reject(new Error('cancelado')); return; }
            var nombre = window.prompt('Nombre', defaults.nombre || '');
            if (nombre == null) { reject(new Error('cancelado')); return; }
            clave = String(clave).trim();
            nombre = String(nombre).trim();
            if (!clave || !nombre) { reject(new Error('Faltan datos')); return; }
            resolve({ clave: clave, nombre: nombre });
        });
    }

    function guardar() {
        if (!state.empresa) {
            toast('warning', 'Elige una empresa', '');
            return;
        }
        if (!selectedList().length) {
            toast('warning', 'Sin cuentas', 'Selecciona al menos una cuenta.');
            return;
        }
        var seguir = function () {
            var data = payloadGrupo();
            if (!data.clave || !data.nombre) {
                toast('warning', 'Faltan datos', 'El grupo necesita clave y nombre.');
                return;
            }
            enviarGrupo(data);
        };
        if (state.editingId && state.clave && state.nombre) {
            seguir();
            return;
        }
        pedirIdentidad({ clave: state.clave, nombre: state.nombre }).then(function (id) {
            state.clave = id.clave;
            state.nombre = id.nombre;
            seguir();
        }).catch(function (err) {
            if (err && err.message && err.message !== 'cancelado') {
                toast('warning', 'Faltan datos', err.message);
            }
        });
    }

    function enviarGrupo(data) {
        var btn = document.getElementById('grp-guardar');
        if (btn) btn.disabled = true;
        var req = state.editingId
            ? sendJSON('/CentrosCostos/api/grupos/' + state.editingId, 'PUT', data)
            : sendJSON('/CentrosCostos/api/grupos', 'POST', data);
        req.then(function (json) {
            var saved = json.grupo;
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
            toast('success', state.editingId ? 'Grupo actualizado' : 'Grupo creado', saved.clave);
            editGrupo(saved.id);
            renderEmpresas();
        }).catch(function (err) {
            toast('error', 'No se guardó', err && err.message ? err.message : 'Error de red');
        }).then(function () {
            if (btn) btn.disabled = false;
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
                toast('success', 'Grupo eliminado', g ? g.clave : '');
                resetEditor();
                renderEmpresas();
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
        var ctaQ = document.getElementById('grp-cta-q');
        if (ctaQ) {
            ctaQ.addEventListener('input', function () {
                syncSelectedFromDom();
                renderCuentas();
            });
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
        var nuevo = document.getElementById('grp-nuevo');
        if (nuevo) nuevo.addEventListener('click', resetEditor);
        var cancelar = document.getElementById('grp-cancelar');
        if (cancelar) cancelar.addEventListener('click', resetEditor);
        var guardarBtn = document.getElementById('grp-guardar');
        if (guardarBtn) guardarBtn.addEventListener('click', guardar);
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
        }
    };

    window.CCGrupos = CCGrupos;
})(window, document);
