(function (window, document) {
    'use strict';

    var csrf = document.querySelector('meta[name="csrf-token"]');
    csrf = csrf ? csrf.getAttribute('content') : '';

    function getJSON(url) {
        return fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            });
    }

    var SIN_CENTRO_CODIGO = 'SIN_CC';
    var SIN_CENTRO_NOMBRE = 'Sin cliente';

    function isSinCentro(codigo) {
        return String(codigo || '').replace(/\s+/g, '').toUpperCase() === SIN_CENTRO_CODIGO;
    }

    function ctaPretty(codigo) {
        var s = String(codigo == null ? '' : codigo).trim();
        if (!s) return '';
        if (!/sys/i.test(s)) return s;
        var out = s.replace(/_?SYS/gi, '').replace(/^0+/, '').replace(/^[\s\-_]+/, '');
        return out || s.replace(/\D+/g, '').replace(/^0+/, '') || s;
    }

    function ctaNorm(codigo) {
        return ctaPretty(codigo).replace(/\s+/g, '').toLowerCase();
    }

    function etiquetaCentro(codigo, nombre) {
        if (isSinCentro(codigo)) return SIN_CENTRO_NOMBRE;
        var code = String(codigo || '').trim();
        var nom = String(nombre || '').trim();
        if (code && nom && nom !== code) return code + ' — ' + nom;
        return nom || code || '—';
    }

    function foldText(s) {
        return String(s || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function matchQuery(haystack, q) {
        var query = foldText(q);
        if (!query) return true;
        var hay = foldText(haystack);
        return query.split(' ').every(function (tok) {
            return tok === '' || hay.indexOf(tok) !== -1;
        });
    }

    function permNombre(clave) {
        var list = catalogoPermisos();
        for (var i = 0; i < list.length; i++) {
            if (String(list[i].clave) === String(clave)) return list[i].nombre || clave;
        }
        return clave;
    }

    function permBadges(perms) {
        return (perms || []).map(function (p) {
            return '<span class="cc-badge cc-badge-abierto">' + escapeHtml(permNombre(p)) + '</span>';
        }).join(' ') || '<span class="text-muted">—</span>';
    }

    function maskLabel(id, nombre) {
        var code = String(id || '').trim();
        var nom = String(nombre || '').trim();
        if (code && nom && nom !== code) return code + ' · ' + nom;
        return nom || code || 'Sin GroupMask';
    }

    function defaultMasks() {
        return [
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
        ];
    }

    function masksFromCatalogo(agrupaciones, rows) {
        var map = {};
        defaultMasks().forEach(function (m) { map[m.id] = { id: m.id, nombre: m.nombre }; });
        (agrupaciones || []).forEach(function (g) {
            var id = String(g.id || '').trim();
            if (!id) return;
            var nom = String(g.nombre || '').trim();
            map[id] = { id: id, nombre: (nom && nom !== id) ? nom : (map[id] ? map[id].nombre : id) };
        });
        (rows || []).forEach(function (c) {
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

    function fillMaskUi(selId, agrupaciones, cuentas, current, onPick) {
        var sel = document.getElementById(selId);
        if (!sel) return;
        var masks = masksFromCatalogo(agrupaciones, cuentas);
        current = String(current || '');
        sel.innerHTML = '<option value="">Todos los GroupMask</option>' + masks.map(function (m) {
            return '<option value="' + escapeHtml(m.id) + '"' + (current === String(m.id) ? ' selected' : '') + '>' +
                escapeHtml(maskLabel(m.id, m.nombre)) + '</option>';
        }).join('');
        sel.value = current;
        sel.onchange = function () { onPick(sel.value || ''); };
    }

    function fillGrupoUi(selId, grupos, current, onPick) {
        var sel = document.getElementById(selId);
        if (!sel) return;
        current = String(current || '');
        var rows = grupos || [];
        var opts = '<option value="">Todos los grupos</option>';
        if (!rows.length) {
            opts += '<option value="_none" disabled>Sin grupos personalizados</option>';
        } else {
            opts += rows.map(function (g) {
                var id = String(g.id);
                var label = String(g.nombre || g.clave || ('Grupo ' + id)).trim();
                var n = (g.cuentas || []).length;
                if (n) label += ' · ' + n;
                return '<option value="' + escapeHtml(id) + '"' + (current === id ? ' selected' : '') + '>' +
                    escapeHtml(label) + '</option>';
            }).join('');
        }
        sel.innerHTML = opts;
        sel.value = current;
        sel.onchange = function () { onPick(sel.value || ''); };
    }

    function grupoCuentaMap(grupos, id) {
        if (!id) return null;
        var g = (grupos || []).filter(function (x) { return String(x.id) === String(id); })[0];
        if (!g) return null;
        var map = {};
        (g.cuentas || []).forEach(function (c) {
            var code = ctaNorm(c.codigo || c.cuenta_codigo);
            if (!code) return;
            map[code] = c;
        });
        return map;
    }

    function filterRowsByGrupo(rows, map) {
        if (!map) return rows || [];
        return (rows || []).filter(function (c) {
            return !!map[ctaNorm(c.codigo)];
        });
    }

    function mergeGrupoCuentas(selected, map, catalog, keyFn) {
        if (!selected || !map) return;
        var byCode = {};
        (catalog || []).forEach(function (c) {
            var k = ctaNorm(c.codigo);
            if (k) byCode[k] = c;
        });
        Object.keys(map).forEach(function (k) {
            var fromG = map[k];
            var fromCat = byCode[k];
            var codigo = (fromCat && fromCat.codigo) || (fromG && (fromG.codigo || fromG.cuenta_codigo)) || '';
            if (!codigo) return;
            var storeKey = keyFn ? keyFn(codigo) : String(codigo);
            selected[storeKey] = {
                codigo: codigo,
                nombre: (fromCat && fromCat.nombre) || (fromG && (fromG.nombre || fromG.cuenta_nombre)) || '',
                agrupacion: (fromCat && (fromCat.grupo || fromCat.agrupacion)) || (fromG && fromG.agrupacion) || ''
            };
        });
    }

    function groupByMask(rows) {
        var groups = {};
        (rows || []).forEach(function (c) {
            var id = String(c.grupo_id || '').trim() || '_';
            if (!groups[id]) {
                groups[id] = { id: id === '_' ? '' : id, label: maskLabel(c.grupo_id, c.grupo), rows: [] };
            }
            groups[id].rows.push(c);
        });
        return groups;
    }

    var CCAsig = {};

    var PERMS_FALLBACK = [
        { clave: 'capturar', nombre: 'Capturar', descripcion: 'Puede capturar proyección mientras el ciclo esté Abierto' },
        { clave: 'editar', nombre: 'Editar', descripcion: 'Puede modificar montos cuando el ciclo está En revisión o Cerrado' },
        { clave: 'revisar', nombre: 'Revisar', descripcion: 'Puede consultar y revisar sin editar' }
    ];

    var EMPRESAS_FALLBACK = [
        { codigo: 'austin' }, { codigo: 'imsa' }, { codigo: 'pitic' }, { codigo: 'sydney' }
    ];

    CCAsig.initCiclo = function (cicloOrCfg) {
        var cfg = (cicloOrCfg && typeof cicloOrCfg === 'object') ? cicloOrCfg : { ciclo: cicloOrCfg };
        CCAsig.cfg = Object.assign({}, CCAsig.cfg || {}, cfg);
        if (cfg.csrf) csrf = cfg.csrf;
        CCAsig.ciclo = cfg.ciclo || CCAsig.ciclo || '';
        bindFiltrosTabla();
        bindModalesAsig();
        bindImportarUsuarios();
        loadEmpresasKpi();
        reloadTabla();
    };

    function loadEmpresasKpi() {
        getJSON('/ProyeccionesVentas/api/empresas?ciclo=' + encodeURIComponent(CCAsig.ciclo || '')).then(function (json) {
            CCAsig._empresas = json.empresas && json.empresas.length ? json.empresas : EMPRESAS_FALLBACK;
            renderAsigKpis();
        }).catch(function () {
            CCAsig._empresas = EMPRESAS_FALLBACK;
            renderAsigKpis();
        });
    }

    function reloadTabla() {
        var ciclo = CCAsig.ciclo;
        getJSON('/Ventas/Asignaciones/' + encodeURIComponent(ciclo) + '/asignaciones').then(function (json) {
            CCAsig._rows = json.asignaciones || [];
            renderTablaFiltrada();
            renderAsigKpis();
        }).catch(function () {
            CCAsig._rows = [];
            renderTablaFiltrada();
            renderAsigKpis();
        });
    }

    function filtroVal(id) {
        var el = document.getElementById(id);
        return el ? String(el.value || '').toLowerCase().trim() : '';
    }

    function bindFiltrosTabla() {
        if (CCAsig._filtrosBound) return;
        CCAsig._filtrosBound = true;
        ['asig-q-empresa', 'asig-q-usuario', 'asig-q-centro'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', renderTablaFiltrada);
        });
    }

    function matchFiltro(haystack, q) {
        return matchQuery(haystack, q);
    }

    function isPrincipal(r) {
        return !r || r.es_principal !== false;
    }

    function mismoCentro(a, b) {
        return String(a.empresa || '').toLowerCase() === String(b.empresa || '').toLowerCase()
            && String(a.centro_codigo || '') === String(b.centro_codigo || '');
    }

    function delCentro(row) {
        return (CCAsig._rows || []).filter(function (r) { return mismoCentro(r, row); });
    }

    function principalDe(row) {
        var list = delCentro(row);
        return list.filter(isPrincipal)[0] || list[0] || row;
    }

    function extrasDe(row) {
        var p = principalDe(row);
        return delCentro(row).filter(function (r) { return Number(r.id) !== Number(p.id); });
    }

    function catalogoPermisos() {
        var list = (CCAsig.cfg && CCAsig.cfg.permisos) || [];
        list = list.length ? list : PERMS_FALLBACK;
        return list.filter(function (p) { return String(p.clave) !== 'importar'; });
    }

    function catalogoUsuarios() {
        return (CCAsig.cfg && CCAsig.cfg.usuarios) || [];
    }

    function setKpi(id, html) {
        var el = document.getElementById(id);
        if (el) el.innerHTML = html;
    }

    function setKpiText(id, text) {
        var el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    function ratioHtml(done, total) {
        return done + '<span class="cc-kpi-den">/' + total + '</span>';
    }

    function hintDe(total, done, varios) {
        if (!total) return 'Sin catálogo todavía';
        if (!done) return 'De ' + total + ' ' + varios + ', ninguna tiene asignación todavía';
        if (done === 1) return 'De ' + total + ' ' + varios + ', 1 ya tiene asignación';
        if (done >= total) return 'Las ' + total + ' ' + varios + ' ya tienen asignación';
        return 'De ' + total + ' ' + varios + ', ' + done + ' ya tienen asignación';
    }

    function hintUsuarios(total, done) {
        if (!total) return 'Sin usuarios en el catálogo';
        if (!done) return 'De ' + total + ' usuarios, ninguno tiene clientes todavía';
        if (done === 1) return 'De ' + total + ' usuarios, 1 ya tiene clientes';
        if (done >= total) return 'Los ' + total + ' usuarios ya tienen clientes';
        return 'De ' + total + ' usuarios, ' + done + ' ya tienen clientes';
    }

    function renderAsigKpis() {
        if (!document.getElementById('asig-kpis')) return;
        var rows = CCAsig._rows || [];
        var empSet = {};
        var ccSet = {};
        var userSet = {};
        rows.forEach(function (r) {
            var emp = String(r.empresa || '').toLowerCase();
            if (emp) empSet[emp] = true;
            if (emp && r.centro_codigo) ccSet[emp + '|' + String(r.centro_codigo)] = true;
            if (isPrincipal(r) && r.user_id) userSet[String(r.user_id)] = true;
        });
        var empDone = Object.keys(empSet).length;
        var empTot = (CCAsig._empresas && CCAsig._empresas.length) ? CCAsig._empresas.length : 4;
        var ccDone = Object.keys(ccSet).length;
        var userDone = Object.keys(userSet).length;
        var userTot = catalogoUsuarios().length || userDone;

        setKpi('kpi-asig-emp', ratioHtml(empDone, empTot));
        setKpiText('kpi-asig-emp-hint', hintDe(empTot, empDone, 'empresas'));
        setKpi('kpi-asig-cc', String(ccDone));
        setKpiText('kpi-asig-cc-hint', ccDone === 1
            ? '1 cliente ya tiene asignación'
            : ccDone + ' clientes ya tienen asignación');
        setKpi('kpi-asig-user', ratioHtml(userDone, userTot));
        setKpiText('kpi-asig-user-hint', hintUsuarios(userTot, userDone));
    }

    function filasCaptura() {
        return (CCAsig._rows || []).filter(isPrincipal).slice().sort(function (a, b) {
            var ua = String(a.usuario || '').toLowerCase();
            var ub = String(b.usuario || '').toLowerCase();
            if (ua !== ub) return ua < ub ? -1 : 1;
            var ea = String(a.empresa || '').toLowerCase();
            var eb = String(b.empresa || '').toLowerCase();
            if (ea !== eb) return ea < eb ? -1 : 1;
            return String(a.centro_codigo || '').localeCompare(String(b.centro_codigo || ''));
        });
    }

    function renderTablaFiltrada() {
        var all = filasCaptura();
        var qEmp = filtroVal('asig-q-empresa');
        var qUser = filtroVal('asig-q-usuario');
        var qCc = filtroVal('asig-q-centro');
        var rows = all.filter(function (r) {
            var extras = extrasDe(r).map(function (x) { return (x.usuario || '') + ' ' + (x.email || ''); }).join(' ');
            return matchFiltro(r.empresa, qEmp)
                && matchFiltro((r.usuario || '') + ' ' + (r.email || '') + ' ' + extras, qUser)
                && matchFiltro((r.centro_codigo || '') + ' ' + (r.centro_nombre || '') + ' ' + etiquetaCentro(r.centro_codigo, r.centro_nombre), qCc);
        });
        renderTabla(rows, CCAsig.ciclo, all.length, qEmp || qUser || qCc);
    }

    function renderTabla(rows, ciclo, total, filtrando) {
        var tb = document.getElementById('asig-tbody');
        var meta = document.getElementById('asig-tabla-meta');
        if (meta) {
            if (!total) meta.textContent = '';
            else if (filtrando) meta.textContent = rows.length + ' de ' + total;
            else meta.textContent = total + (total === 1 ? ' asignación de captura' : ' asignaciones de captura');
        }
        if (!tb) return;
        if (!rows.length) {
            var empty = filtrando
                ? 'No hay asignaciones que coincidan con la búsqueda.'
                : 'Aún no hay asignaciones. Usa Nueva asignación para crear la primera.';
            tb.innerHTML = '<tr><td colspan="6"><div class="cc-empty">' + empty + '</div></td></tr>';
            return;
        }
        var html = [];
        var lastUser = null;
        rows.forEach(function (r) {
            var uid = String(r.user_id || r.usuario || '');
            if (uid !== lastUser) {
                lastUser = uid;
                var n = 0;
                rows.forEach(function (x) {
                    if (String(x.user_id || x.usuario || '') === uid) n += 1;
                });
                html.push('<tr class="cc-asig-group"><td colspan="6"><div class="cc-asig-group-in">' +
                    '<span class="cc-avatar">' + initials(r.usuario) + '</span>' +
                    '<span><span class="fw-semibold">' + escapeHtml(r.usuario) + '</span>' +
                    (r.email ? '<span class="text-muted" style="font-size:.75rem;margin-left:.4rem">' + escapeHtml(r.email) + '</span>' : '') +
                    '</span>' +
                    '<span class="cc-asig-group-n">' + n + (n === 1 ? ' cliente' : ' clientes') + '</span>' +
                    '</div></td></tr>');
            }
            var nRev = extrasDe(r).length;
            var revHint = nRev
                ? '<div class="text-muted" style="font-size:.72rem;margin-top:.25rem">' + nRev + (nRev === 1 ? ' revisor en Permisos' : ' revisores en Permisos') + '</div>'
                : '';
            html.push('<tr data-id="' + r.id + '">' +
                '<td><div class="fw-semibold">' + escapeHtml(r.usuario) + '</div>' +
                '<div class="text-muted" style="font-size:.75rem">Captura</div></td>' +
                '<td>' + String(r.empresa || '').toUpperCase() + '</td>' +
                '<td>' + escapeHtml(etiquetaCentro(r.centro_codigo, r.centro_nombre)) + '</td>' +
                '<td>' + (r.cuentas || []).length + '</td>' +
                '<td>' + permBadges(r.permisos) + revHint + '</td>' +
                '<td><div class="cc-row-actions">' +
                    '<button type="button" class="cc-icon-btn" data-act="ver" title="Ver"><i class="fa-solid fa-eye"></i></button>' +
                    '<button type="button" class="cc-icon-btn" data-act="editar" title="Editar"><i class="fa-solid fa-pen"></i></button>' +
                    '<button type="button" class="cc-icon-btn" data-act="permisos" title="Permisos"><i class="fa-solid fa-user-lock"></i></button>' +
                    '<button type="button" class="cc-icon-btn is-danger" data-act="del" title="Quitar"><i class="fa-solid fa-trash"></i></button>' +
                '</div></td>' +
                '</tr>');
        });
        tb.innerHTML = html.join('');
        tb.querySelectorAll('[data-act]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tr = btn.closest('tr');
                var row = rowById(tr && tr.getAttribute('data-id'));
                if (!row) return;
                var act = btn.getAttribute('data-act');
                if (act === 'ver') openVer(row);
                if (act === 'editar') openEditar(row);
                if (act === 'permisos') openPermisos(row);
                if (act === 'del') eliminarAsignacion(row);
            });
        });
    }

    function rowById(id) {
        return (CCAsig._rows || []).filter(function (r) { return String(r.id) === String(id); })[0] || null;
    }

    function initials(name) {
        var p = String(name || '').trim().split(/\s+/);
        var a = (p[0] || '?').charAt(0);
        var b = p.length > 1 ? p[p.length - 1].charAt(0) : '';
        return (a + b).toUpperCase();
    }

    function toast(icon, title, text) {
        if (window.Swal) {
            Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false,
                timer: 2600, timerProgressBar: true
            }).fire({ icon: icon, title: title, text: text || '' });
        }
    }

    function showModal(id) {
        if (window.CC && typeof CC.showModal === 'function') {
            CC.showModal(id);
            return;
        }
        var el = document.getElementById(id);
        if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show();
    }

    function hideModal(id) {
        if (window.CC && typeof CC.hideModal === 'function') {
            CC.hideModal(id);
            return;
        }
        var el = document.getElementById(id);
        if (el && window.bootstrap) {
            var inst = bootstrap.Modal.getInstance(el);
            if (inst) inst.hide();
        }
    }

    function hideThen(id, fn) {
        var el = document.getElementById(id);
        var open = el && el.classList.contains('show');
        hideModal(id);
        if (open) setTimeout(fn, 220);
        else fn();
    }

    function sendJSON(url, method, body) {
        return fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (r) {
            return r.json().then(function (j) {
                if (!r.ok) throw new Error((j && j.message) || ('HTTP ' + r.status));
                return j;
            });
        });
    }

    function asigUrl(id) {
        return '/Ventas/Asignaciones/' + encodeURIComponent(CCAsig.ciclo) + '/asignaciones/' + id;
    }

    function permChecksHtml(prefix, selected) {
        var set = {};
        (selected || []).forEach(function (p) { set[String(p)] = true; });
        return catalogoPermisos().map(function (p) {
            return '<label class="cc-perm-card">' +
                '<input type="checkbox" name="' + prefix + '" value="' + escapeHtml(p.clave) + '"' + (set[p.clave] ? ' checked' : '') + '>' +
                '<strong>' + escapeHtml(p.nombre) + '</strong>' +
                '<span>' + escapeHtml(p.descripcion || '') + '</span></label>';
        }).join('');
    }

    function selectedPerms(rootId, name) {
        var out = [];
        document.querySelectorAll('#' + rootId + ' input[name="' + name + '"]:checked').forEach(function (i) {
            out.push(i.value);
        });
        return out;
    }

    function openVer(row) {
        CCAsig._activo = row;
        var p = principalDe(row);
        var extras = extrasDe(row);
        var title = document.getElementById('asig-ver-title');
        if (title) title.textContent = 'Ver · ' + etiquetaCentro(row.centro_codigo, row.centro_nombre);
        var cuentas = (p.cuentas || row.cuentas || []).map(function (c) {
            return '<li><span class="cc-cta-name">' + escapeHtml(c.nombre || '') + '</span> <span class="cc-cta-code">' + escapeHtml(ctaPretty(c.codigo)) + '</span></li>';
        }).join('') || '<li class="text-muted">Sin productos</li>';
        var extrasHtml = extras.length
            ? extras.map(function (x) {
                return '<div class="cc-user" style="margin-bottom:.35rem">' +
                    '<span class="cc-avatar">' + initials(x.usuario) + '</span>' +
                    '<span>' + escapeHtml(x.usuario) + ' · ' + permBadges(x.permisos) + '</span></div>';
            }).join('')
            : '<span class="text-muted">Nadie más tiene acceso.</span>';
        document.getElementById('asig-ver-body').innerHTML =
            '<div class="cc-context">' +
            '<div class="cc-context-item"><small>Usuario a cargo</small><strong>' + escapeHtml(p.usuario || '—') + '</strong></div>' +
            '<div class="cc-context-item"><small>Empresa</small><strong>' + String(row.empresa || '').toUpperCase() + '</strong></div>' +
            '<div class="cc-context-item"><small>Cliente</small><strong>' + escapeHtml(etiquetaCentro(row.centro_codigo, row.centro_nombre)) + '</strong></div>' +
            '<div class="cc-context-item"><small>Permisos del responsable</small><span>' + permBadges(p.permisos) + '</span></div>' +
            '</div>' +
            '<div class="cc-form-kicker">Productos</div>' +
            '<ul class="cc-ver-cuentas">' + cuentas + '</ul>' +
            '<div class="cc-form-kicker">Otros usuarios</div>' + extrasHtml;
        showModal('modalAsigVer');
    }

    function openEditar(row) {
        hideThen('modalAsigVer', function () { openEditarNow(row); });
    }

    function openEditarNow(row) {
        CCAsig._activo = row;
        var p = principalDe(row);
        var title = document.getElementById('asig-edit-title');
        var sub = document.getElementById('asig-edit-sub');
        if (title) title.textContent = 'Editar productos · ' + etiquetaCentro(row.centro_codigo, row.centro_nombre);
        if (sub) sub.textContent = String(row.empresa || '').toUpperCase() + ' · ' + (p.usuario || row.usuario) + ' a cargo. Los productos se replican a quienes tengan acceso.';
        CCAsig._editSelected = {};
        (p.cuentas || row.cuentas || []).forEach(function (c) {
            CCAsig._editSelected[String(c.codigo)] = {
                codigo: c.codigo,
                nombre: c.nombre || '',
                agrupacion: c.agrupacion || ''
            };
        });
        var qEl = document.getElementById('asig-edit-q');
        if (qEl) qEl.value = '';
        var list = document.getElementById('asig-edit-list');
        if (list) list.innerHTML = '<div class="cc-empty">Cargando productos SAP…</div>';
        CCAsig._editMask = '';
        CCAsig._editGrupo = '';
        var maskSel = document.getElementById('asig-edit-mask');
        if (maskSel) maskSel.value = '';
        var grupoSel = document.getElementById('asig-edit-grupo');
        if (grupoSel) grupoSel.value = '';
        showModal('modalAsigEditar');
        loadGruposEditar(row.empresa);
        loadCuentasEditar(row.empresa, qEl ? qEl.value : '', row.centro_codigo);
    }

    function loadCuentasEditar(empresa, q, cliente) {
        var year = Number((window.CC && CC.state && (CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia))) || new Date().getFullYear());
        var card = String(cliente || (CCAsig._activo && CCAsig._activo.centro_codigo) || '').trim();
        var key = String(empresa || '').toLowerCase() + '|' + year + '|' + card.toUpperCase();
        var apply = function (rows, groups) {
            CCAsig._editCuentasAll = rows || [];
            CCAsig._editCuentas = CCAsig._editCuentasAll;
            if (groups && groups.length) CCAsig._editAgrupaciones = groups;
            fillMaskUi('asig-edit-mask', CCAsig._editAgrupaciones || [], CCAsig._editCuentasAll, CCAsig._editMask || '', setEditMask);
            fillGrupoUi('asig-edit-grupo', CCAsig._editGrupos || [], CCAsig._editGrupo || '', setEditGrupo);
            if (CCAsig._editMask) {
                setEditMask(CCAsig._editMask);
                return;
            }
            renderCuentasEditar(q);
        };
        CCAsig._cuentasCache = CCAsig._cuentasCache || {};
        if (CCAsig._cuentasCache[key]) {
            apply(CCAsig._cuentasCache[key], CCAsig._editAgrupaciones || []);
            return;
        }
        getJSON('/ProyeccionesVentas/api/cuentas?empresa=' + encodeURIComponent(String(empresa || '').toLowerCase()) +
            '&year=' + encodeURIComponent(year) +
            '&cliente=' + encodeURIComponent(card) +
            '&cc=' + encodeURIComponent(card)).then(function (json) {
            CCAsig._cuentasCache[key] = json.cuentas || [];
            apply(CCAsig._cuentasCache[key], json.agrupaciones || []);
        }).catch(function () {
            apply((principalDe(CCAsig._activo).cuentas) || [], []);
        });
    }

    function loadGruposEditar(empresa) {
        CCAsig._editGrupos = [];
        fillGrupoUi('asig-edit-grupo', [], CCAsig._editGrupo || '', setEditGrupo);
    }

    function setEditGrupo(id) {
        CCAsig._editGrupo = String(id || '');
        fillGrupoUi('asig-edit-grupo', CCAsig._editGrupos || [], CCAsig._editGrupo, setEditGrupo);
        var map = grupoCuentaMap(CCAsig._editGrupos || [], CCAsig._editGrupo);
        if (map) {
            CCAsig._editSelected = CCAsig._editSelected || {};
            mergeGrupoCuentas(CCAsig._editSelected, map, CCAsig._editCuentasAll || CCAsig._editCuentas || []);
        }
        var qEl = document.getElementById('asig-edit-q');
        renderCuentasEditar(qEl ? qEl.value : '');
    }

    function setEditMask(mask) {
        CCAsig._editMask = String(mask || '');
        fillMaskUi('asig-edit-mask', CCAsig._editAgrupaciones || [], CCAsig._editCuentasAll || [], CCAsig._editMask, setEditMask);
        var qEl = document.getElementById('asig-edit-q');
        var q = qEl ? qEl.value : '';
        if (!CCAsig._editMask) {
            CCAsig._editCuentas = CCAsig._editCuentasAll || [];
            renderCuentasEditar(q);
            return;
        }
        var empresa = String((CCAsig._activo && CCAsig._activo.empresa) || '').toLowerCase();
        var key = empresa + '|' + CCAsig._editMask;
        CCAsig._cuentasCache = CCAsig._cuentasCache || {};
        if (CCAsig._cuentasCache[key]) {
            CCAsig._editCuentas = CCAsig._cuentasCache[key];
            renderCuentasEditar(q);
            return;
        }
        var box = document.getElementById('asig-edit-list');
        if (box) box.innerHTML = '<div class="cc-empty">Cargando GroupMask ' + escapeHtml(CCAsig._editMask) + '…</div>';
        getJSON('/ProyeccionesVentas/api/cuentas?todas=1&empresa=' + encodeURIComponent(empresa) +
            '&group_mask=' + encodeURIComponent(CCAsig._editMask)).then(function (json) {
            if (String(CCAsig._editMask) !== String(mask || '')) return;
            CCAsig._cuentasCache[key] = json.cuentas || [];
            if (json.agrupaciones && json.agrupaciones.length) {
                CCAsig._editAgrupaciones = json.agrupaciones;
            }
            CCAsig._editCuentas = CCAsig._cuentasCache[key];
            fillMaskUi('asig-edit-mask', CCAsig._editAgrupaciones || [], CCAsig._editCuentasAll || [], CCAsig._editMask, setEditMask);
            renderCuentasEditar(qEl ? qEl.value : '');
        }).catch(function () {
            renderCuentasEditar(q);
        });
    }

    function renderCuentasEditar(q) {
        q = (q || '').toLowerCase();
        var box = document.getElementById('asig-edit-list');
        if (!box) return;
        var mask = String(CCAsig._editMask || '');
        var grupoMap = grupoCuentaMap(CCAsig._editGrupos || [], CCAsig._editGrupo || '');
        var rows = filterRowsByGrupo(CCAsig._editCuentas || [], grupoMap).filter(function (c) {
            return matchQuery((c.codigo || '') + ' ' + ctaPretty(c.codigo) + ' ' + (c.nombre || ''), q);
        });
        if (!rows.length) {
            box.innerHTML = '<div class="cc-empty">Sin productos para este catálogo</div>';
            return;
        }
        var sel = CCAsig._editSelected || {};
        box.innerHTML = '<div class="cc-cta-group">' + rows.map(function (c) {
                    var on = !!sel[String(c.codigo)];
                    return '<label class="cc-cta-item"><input type="checkbox" data-edit-cta="1" value="' + escapeHtml(c.codigo) + '"' +
                        ' data-nombre="' + escapeHtml(c.nombre || '') + '" data-grupo="' + escapeHtml(c.grupo || '') + '"' +
                        ' data-mask="' + escapeHtml(c.grupo_id || '') + '"' + (on ? ' checked' : '') + '>' +
                        '<span class="cc-cta-name">' + escapeHtml(c.nombre || c.codigo) + '</span>' +
                        '<span class="cc-cta-code">' + escapeHtml(ctaPretty(c.codigo)) + '</span></label>';
                }).join('') + '</div>';
        var todas = document.getElementById('asig-edit-todas');
        if (todas) {
            var nOn = 0;
            box.querySelectorAll('[data-edit-cta]').forEach(function (i) { if (i.checked) nOn += 1; });
            todas.checked = rows.length > 0 && nOn === rows.length;
        }
    }

    function syncEditSelectedFromDom() {
        var sel = CCAsig._editSelected || {};
        document.querySelectorAll('#asig-edit-list [data-edit-cta]').forEach(function (i) {
            if (i.checked) {
                sel[i.value] = {
                    codigo: i.value,
                    nombre: i.getAttribute('data-nombre') || '',
                    agrupacion: i.getAttribute('data-grupo') || ''
                };
            } else {
                delete sel[i.value];
            }
        });
        CCAsig._editSelected = sel;
    }

    function openPermisos(row) {
        hideThen('modalAsigVer', function () { openPermisosNow(row); });
    }

    function openPermisosNow(row) {
        CCAsig._activo = row;
        var p = principalDe(row);
        CCAsig._permPrincipal = p;
        CCAsig._accesos = extrasDe(row).map(function (x) {
            return {
                id: x.id,
                user_id: x.user_id,
                usuario: x.usuario,
                email: x.email,
                permisos: (x.permisos || []).slice()
            };
        });
        CCAsig._accPick = null;
        var title = document.getElementById('asig-perm-title');
        if (title) title.textContent = 'Permisos · ' + etiquetaCentro(row.centro_codigo, row.centro_nombre);
        var av = document.getElementById('asig-perm-av');
        var nom = document.getElementById('asig-perm-nombre');
        var mail = document.getElementById('asig-perm-email');
        if (av) av.textContent = initials(p.usuario);
        if (nom) nom.textContent = p.usuario || '—';
        if (mail) mail.textContent = p.email || '';
        document.getElementById('asig-perm-principal').innerHTML = permChecksHtml('perm-principal', p.permisos);
        var q = document.getElementById('asig-acc-q');
        if (q) q.value = '';
        var addBox = document.getElementById('asig-acc-add-box');
        if (addBox) addBox.hidden = true;
        fillAccUserPick('');
        renderAccesos();
        showModal('modalAsigPermisos');
    }

    function fillAccUserPick(q) {
        var list = document.getElementById('asig-acc-pick');
        if (!list) return;
        q = (q || '').toLowerCase().trim();
        var p = CCAsig._permPrincipal || {};
        var taken = {};
        taken[String(p.user_id)] = true;
        (CCAsig._accesos || []).forEach(function (a) { taken[String(a.user_id)] = true; });
        var rows = catalogoUsuarios().filter(function (u) {
            if (taken[String(u.id)]) return false;
            if (!q) return true;
            return (u.nombre + ' ' + (u.email || '')).toLowerCase().indexOf(q) !== -1;
        }).slice(0, 40);
        if (!q) {
            list.innerHTML = '<div class="cc-empty">Escribe para buscar a quién dar acceso.</div>';
            return;
        }
        if (!rows.length) {
            list.innerHTML = '<div class="cc-empty">Sin coincidencias</div>';
            return;
        }
        list.innerHTML = rows.map(function (u) {
            return '<button type="button" class="cc-cc-item" data-id="' + escapeHtml(u.id) + '">' +
                '<span><span class="cc-cc-code">' + escapeHtml(u.nombre) + '</span>' +
                (u.email ? '<span class="cc-cc-name">' + escapeHtml(u.email) + '</span>' : '') +
                '</span></button>';
        }).join('');
        list.querySelectorAll('.cc-cc-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                pickAccUser(btn.getAttribute('data-id'));
            });
        });
    }

    function pickAccUser(id) {
        var u = catalogoUsuarios().filter(function (x) { return String(x.id) === String(id); })[0];
        if (!u) return;
        CCAsig._accPick = u;
        var box = document.getElementById('asig-acc-add-box');
        if (box) box.hidden = false;
        var name = document.getElementById('asig-acc-add-name');
        var mail = document.getElementById('asig-acc-add-email');
        if (name) name.textContent = u.nombre;
        if (mail) mail.textContent = u.email || '';
        document.getElementById('asig-acc-add-perms').innerHTML = permChecksHtml('perm-acc-new', ['revisar']);
        var q = document.getElementById('asig-acc-q');
        if (q) q.value = u.nombre;
        var list = document.getElementById('asig-acc-pick');
        if (list) list.innerHTML = '';
    }

    function renderAccesos() {
        var tb = document.getElementById('asig-acc-tbody');
        if (!tb) return;
        var rows = CCAsig._accesos || [];
        if (!rows.length) {
            tb.innerHTML = '<tr><td colspan="3"><div class="cc-empty">Nadie más tiene acceso a este centro.</div></td></tr>';
            return;
        }
        tb.innerHTML = rows.map(function (a, i) {
            return '<tr>' +
                '<td><div class="fw-semibold">' + escapeHtml(a.usuario) + '</div>' +
                '<div class="text-muted" style="font-size:.75rem">' + escapeHtml(a.email || '') + '</div></td>' +
                '<td>' + permBadges(a.permisos) + '</td>' +
                '<td><button type="button" class="cc-icon-btn is-danger" data-acc-del="' + i + '" title="Quitar acceso"><i class="fa-solid fa-xmark"></i></button></td>' +
                '</tr>';
        }).join('');
        tb.querySelectorAll('[data-acc-del]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                CCAsig._accesos.splice(Number(btn.getAttribute('data-acc-del')), 1);
                renderAccesos();
                fillAccUserPick(filtroVal('asig-acc-q'));
            });
        });
    }

    function eliminarAsignacion(row) {
        var extras = extrasDe(row);
        var esP = isPrincipal(row);
        var text = esP && extras.length
            ? 'También se quitará el acceso de ' + extras.length + (extras.length === 1 ? ' usuario' : ' usuarios') + ' a este centro.'
            : 'Se quitará a ' + (row.usuario || 'este usuario') + ' de ' + (etiquetaCentro(row.centro_codigo, row.centro_nombre) || 'este centro') + '.';
        var go = function () {
            sendJSON(asigUrl(row.id), 'DELETE').then(function () {
                toast('success', 'Asignación eliminada');
                reloadTabla();
            }).catch(function (err) {
                toast('error', 'No se pudo quitar', err.message);
            });
        };
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: esP ? 'Quitar centro asignado' : 'Quitar acceso',
                text: text,
                showCancelButton: true,
                confirmButtonText: 'Quitar',
                cancelButtonText: 'Cancelar'
            }).then(function (res) { if (res.isConfirmed) go(); });
        } else {
            go();
        }
    }

    function bindImportarUsuarios() {
        if (CCAsig._importarBound) return;
        CCAsig._importarBound = true;
        var btn = document.getElementById('btn-importar-usuarios');
        if (btn) btn.addEventListener('click', openImportarUsuarios);
        var q = document.getElementById('imp-user-q');
        if (q) q.addEventListener('input', function () { fillImportarPick(q.value); });
        var form = document.getElementById('form-importar-usuarios');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var ids = (CCAsig._importarUsers || []).map(function (u) { return u.id; });
                sendJSON('/Ventas/Asignaciones/' + encodeURIComponent(CCAsig.ciclo) + '/importar-usuarios', 'PUT', { user_ids: ids })
                    .then(function (json) {
                        CCAsig._importarUsers = json.usuarios || [];
                        renderImportarUsers();
                        hideModal('modalImportarUsuarios');
                        toast('success', 'Permiso guardado', 'Importar masivo quedó por usuario, para todo el ciclo.');
                    })
                    .catch(function (err) {
                        toast('error', 'No se guardó', err && err.message ? err.message : 'Error de red');
                    });
            });
        }
    }

    function importarUrl() {
        return '/Ventas/Asignaciones/' + encodeURIComponent(CCAsig.ciclo) + '/importar-usuarios';
    }

    function openImportarUsuarios() {
        CCAsig._importarUsers = [];
        var q = document.getElementById('imp-user-q');
        if (q) q.value = '';
        fillImportarPick('');
        renderImportarUsers();
        showModal('modalImportarUsuarios');
        getJSON(importarUrl()).then(function (json) {
            CCAsig._importarUsers = json.usuarios || [];
            renderImportarUsers();
        }).catch(function () {
            CCAsig._importarUsers = [];
            renderImportarUsers();
        });
    }

    function fillImportarPick(q) {
        var list = document.getElementById('imp-user-pick');
        if (!list) return;
        q = (q || '').toLowerCase().trim();
        var taken = {};
        (CCAsig._importarUsers || []).forEach(function (u) { taken[String(u.id)] = true; });
        if (!q) {
            list.innerHTML = '';
            list.hidden = true;
            return;
        }
        var rows = catalogoUsuarios().filter(function (u) {
            if (taken[String(u.id)]) return false;
            return (u.nombre + ' ' + (u.email || '')).toLowerCase().indexOf(q) !== -1;
        }).slice(0, 40);
        list.hidden = false;
        if (!rows.length) {
            list.innerHTML = '<div class="cc-empty cc-empty-compact">Sin coincidencias</div>';
            return;
        }
        list.innerHTML = rows.map(function (u) {
            return '<button type="button" class="cc-cc-item" data-id="' + escapeHtml(u.id) + '">' +
                '<span><span class="cc-cc-code">' + escapeHtml(u.nombre) + '</span>' +
                (u.email ? '<span class="cc-cc-name">' + escapeHtml(u.email) + '</span>' : '') +
                '</span></button>';
        }).join('');
        list.querySelectorAll('.cc-cc-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                addImportarUser(btn.getAttribute('data-id'));
            });
        });
    }

    function addImportarUser(id) {
        var u = catalogoUsuarios().filter(function (x) { return String(x.id) === String(id); })[0];
        if (!u) return;
        CCAsig._importarUsers = CCAsig._importarUsers || [];
        if (CCAsig._importarUsers.some(function (x) { return Number(x.id) === Number(u.id); })) return;
        var nAsig = (CCAsig._rows || []).filter(function (r) { return Number(r.user_id) === Number(u.id); }).length;
        CCAsig._importarUsers.push({
            id: u.id,
            nombre: u.nombre,
            email: u.email,
            asignaciones: nAsig
        });
        var q = document.getElementById('imp-user-q');
        if (q) q.value = '';
        fillImportarPick('');
        renderImportarUsers();
    }

    function renderImportarUsers() {
        var tb = document.getElementById('imp-user-tbody');
        if (!tb) return;
        var rows = CCAsig._importarUsers || [];
        if (!rows.length) {
            tb.innerHTML = '<tr><td colspan="3"><div class="cc-empty">Nadie puede importar masivo en este ciclo.</div></td></tr>';
            return;
        }
        tb.innerHTML = rows.map(function (u) {
            var n = Number(u.asignaciones || 0);
            var hint = n ? (n + (n === 1 ? ' centro' : ' centros')) : 'Sin centros asignados';
            return '<tr>' +
                '<td><strong>' + escapeHtml(u.nombre || '') + '</strong>' +
                (u.email ? '<div class="text-muted" style="font-size:.75rem">' + escapeHtml(u.email) + '</div>' : '') + '</td>' +
                '<td>' + escapeHtml(hint) + '</td>' +
                '<td><button type="button" class="cc-icon-btn" data-del="' + escapeHtml(u.id) + '" title="Quitar"><i class="fa-solid fa-trash"></i></button></td>' +
                '</tr>';
        }).join('');
        tb.querySelectorAll('[data-del]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-del');
                CCAsig._importarUsers = (CCAsig._importarUsers || []).filter(function (u) {
                    return String(u.id) !== String(id);
                });
                renderImportarUsers();
                var q = document.getElementById('imp-user-q');
                fillImportarPick(q ? q.value : '');
            });
        });
    }

    function bindModalesAsig() {
        if (CCAsig._modalesBound) return;
        CCAsig._modalesBound = true;

        var verEdit = document.getElementById('asig-ver-editar');
        if (verEdit) verEdit.addEventListener('click', function () {
            if (CCAsig._activo) openEditar(CCAsig._activo);
        });
        var verPerm = document.getElementById('asig-ver-permisos');
        if (verPerm) verPerm.addEventListener('click', function () {
            if (CCAsig._activo) openPermisos(CCAsig._activo);
        });

        var editQ = document.getElementById('asig-edit-q');
        if (editQ) editQ.addEventListener('input', function () {
            syncEditSelectedFromDom();
            renderCuentasEditar(editQ.value);
        });
        var todas = document.getElementById('asig-edit-todas');
        if (todas) todas.addEventListener('change', function () {
            var on = this.checked;
            document.querySelectorAll('#asig-edit-list [data-edit-cta]').forEach(function (i) { i.checked = on; });
            syncEditSelectedFromDom();
        });
        var editList = document.getElementById('asig-edit-list');
        if (editList) editList.addEventListener('change', function (ev) {
            var t = ev.target;
            if (t && t.getAttribute('data-edit-mask') != null) {
                var on = t.checked;
                editList.querySelectorAll('[data-edit-cta]').forEach(function (i) {
                    if (String(i.getAttribute('data-mask') || '') === String(t.getAttribute('data-edit-mask') || '')) {
                        i.checked = on;
                    }
                });
            }
            syncEditSelectedFromDom();
            var qEl = document.getElementById('asig-edit-q');
            renderCuentasEditar(qEl ? qEl.value : '');
        });

        var formEdit = document.getElementById('form-asig-editar');
        if (formEdit) formEdit.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!CCAsig._activo) return;
            syncEditSelectedFromDom();
            var cuentas = Object.keys(CCAsig._editSelected || {}).map(function (k) { return CCAsig._editSelected[k]; });
            if (!cuentas.length) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Sin productos', text: 'Selecciona al menos un producto.' });
                return;
            }
            var btn = document.getElementById('asig-edit-guardar');
            if (btn) btn.disabled = true;
            var target = isPrincipal(CCAsig._activo) ? CCAsig._activo : principalDe(CCAsig._activo);
            sendJSON(asigUrl(target.id), 'PUT', { cuentas: cuentas }).then(function () {
                hideModal('modalAsigEditar');
                toast('success', 'Productos actualizados');
                reloadTabla();
            }).catch(function (err) {
                toast('error', 'No se guardó', err.message);
            }).then(function () {
                if (btn) btn.disabled = false;
            });
        });

        var accQ = document.getElementById('asig-acc-q');
        if (accQ) accQ.addEventListener('input', function () { fillAccUserPick(accQ.value); });
        var accAdd = document.getElementById('asig-acc-add');
        if (accAdd) accAdd.addEventListener('click', function () {
            var u = CCAsig._accPick;
            if (!u) return;
            var perms = selectedPerms('asig-acc-add-perms', 'perm-acc-new');
            if (!perms.length) perms = ['revisar'];
            CCAsig._accesos = CCAsig._accesos || [];
            CCAsig._accesos.push({
                user_id: u.id,
                usuario: u.nombre,
                email: u.email,
                permisos: perms
            });
            CCAsig._accPick = null;
            var box = document.getElementById('asig-acc-add-box');
            if (box) box.hidden = true;
            if (accQ) accQ.value = '';
            renderAccesos();
            fillAccUserPick('');
        });

        var formPerm = document.getElementById('form-asig-permisos');
        if (formPerm) formPerm.addEventListener('submit', function (e) {
            e.preventDefault();
            var p = CCAsig._permPrincipal;
            if (!p) return;
            var permisos = selectedPerms('asig-perm-principal', 'perm-principal');
            if (!permisos.length) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Falta un permiso', text: 'El usuario a cargo necesita al menos un permiso.' });
                return;
            }
            var accesos = (CCAsig._accesos || []).map(function (a) {
                return { user_id: a.user_id, permisos: a.permisos && a.permisos.length ? a.permisos : ['revisar'] };
            });
            var btn = document.getElementById('asig-perm-guardar');
            if (btn) btn.disabled = true;
            sendJSON(asigUrl(p.id), 'PUT', { permisos: permisos, accesos: accesos }).then(function () {
                hideModal('modalAsigPermisos');
                toast('success', 'Permisos guardados');
                reloadTabla();
            }).catch(function (err) {
                toast('error', 'No se guardó', err.message);
            }).then(function () {
                if (btn) btn.disabled = false;
            });
        });
    }

    CCAsig.initWizard = function (cfg) {
        CCAsig.cfg = cfg;
        csrf = cfg.csrf || csrf;
        var userSel = document.getElementById('asig-user');
        var userQ = document.getElementById('asig-user-q');
        var centroSel = document.getElementById('asig-centro');
        var ccQ = document.getElementById('asig-cc-q');
        var ctaQ = document.getElementById('asig-cta-q');
        var empBox = document.getElementById('asig-empresas');
        var detBox = document.getElementById('asig-detalle');
        var btnSave = document.getElementById('asig-guardar');
        var empresas = [];
        var centros = [];
        var cuentas = [];
        var agrupaciones = [];
        var grupos = [];
        var saved = [];
        var cacheCentros = {};
        var cacheCuentas = {};
        var cacheGrupos = {};
        var ctaSelected = {};
        var ctaMask = '';
        var ctaGrupo = '';
        var lastFlashKey = '';
        var currentUserId = 0;

        function normCode(s) {
            return String(s || '').replace(/\s+/g, '').toLowerCase();
        }

        function isTempId(id) {
            return id == null || id === '' || String(id).indexOf('tmp-') === 0;
        }

        function rowKey(emp, codigo) {
            return String(emp || '').toLowerCase() + '|' + String(codigo || '');
        }

        function findSavedIndex(emp, codigo) {
            var e = String(emp || '').toLowerCase();
            var c = normCode(codigo);
            for (var i = 0; i < saved.length; i++) {
                if (String(saved[i].empresa || '').toLowerCase() === e && normCode(saved[i].centro_codigo) === c) {
                    return i;
                }
            }
            return -1;
        }

        function upsertSaved(row) {
            var i = findSavedIndex(row.empresa, row.centro_codigo);
            if (i >= 0) {
                var prevId = saved[i].id;
                var merged = Object.assign({}, saved[i], row);
                if (isTempId(row.id) && !isTempId(prevId)) merged.id = prevId;
                saved.splice(i, 1);
                saved.unshift(merged);
            } else {
                saved.unshift(row);
            }
            lastFlashKey = rowKey(row.empresa, row.centro_codigo);
        }

        function userId() {
            if (currentUserId) return currentUserId;
            return Number(userSel && userSel.value) || 0;
        }

        function userName() {
            var id = String(userId() || '');
            if (!id) return '';
            var u = catalogoUsuarios().filter(function (x) { return String(x.id) === id; })[0];
            if (u && u.nombre) return u.nombre;
            if (!userSel || !userSel.value) return '';
            var opt = userSel.options[userSel.selectedIndex];
            if (!opt) return '';
            return (opt.getAttribute('data-nombre') || String(opt.text || '').split('·')[0]).trim();
        }

        function catalogoUsuarios() {
            if (cfg.usuarios && cfg.usuarios.length) {
                return cfg.usuarios.map(function (u) {
                    return {
                        id: String(u.id),
                        nombre: String(u.nombre || u.name || ''),
                        email: String(u.email || '')
                    };
                });
            }
            var rows = [];
            if (!userSel) return rows;
            Array.prototype.forEach.call(userSel.options, function (opt) {
                if (!opt.value) return;
                var nombre = (opt.getAttribute('data-nombre') || '').trim();
                var email = (opt.getAttribute('data-email') || '').trim();
                var text = String(opt.text || '');
                if (!nombre) nombre = text.split('·')[0].trim();
                if (!email && text.indexOf('·') !== -1) email = text.split('·').slice(1).join('·').trim();
                rows.push({ id: String(opt.value), nombre: nombre, email: email });
            });
            return rows;
        }

        function fillUsers(q) {
            var list = document.getElementById('asig-user-list');
            if (!list) return;
            q = (q || '').toLowerCase().trim();
            var current = String((userSel && userSel.value) || '');
            var all = catalogoUsuarios();
            var rows = all.filter(function (u) {
                if (!q) return true;
                return (u.nombre + ' ' + u.email).toLowerCase().indexOf(q) !== -1;
            });
            var selectedRow = null;
            all.forEach(function (u) {
                if (current !== '' && String(u.id) === current) selectedRow = u;
            });
            if (selectedRow && !rows.some(function (r) { return String(r.id) === String(selectedRow.id); })) {
                rows.unshift(selectedRow);
            }
            if (!rows.length) {
                list.innerHTML = '<div class="cc-empty">' + (q ? 'Sin coincidencias para “' + escapeHtml(q) + '”' : 'No hay usuarios') + '</div>';
                return;
            }
            list.innerHTML = rows.map(function (u) {
                var on = current !== '' && String(u.id) === current;
                return '<button type="button" class="cc-cc-item' + (on ? ' is-on' : '') + '" role="option" data-id="' + escapeHtml(u.id) + '" aria-selected="' + (on ? 'true' : 'false') + '">' +
                    '<span><span class="cc-cc-code">' + escapeHtml(u.nombre) + '</span>' +
                    (u.email ? '<span class="cc-cc-name">' + escapeHtml(u.email) + '</span>' : '') +
                    '</span>' +
                    (on ? '<span class="cc-badge cc-badge-ink">Seleccionado</span>' : '') +
                    '</button>';
            }).join('');
            list.querySelectorAll('.cc-cc-item').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    pickUser(btn.getAttribute('data-id'));
                });
            });
        }

        function userEmail() {
            if (!userSel || !userSel.value) return '';
            var id = String(userSel.value);
            var fromCat = catalogoUsuarios().filter(function (u) { return String(u.id) === id; })[0];
            if (fromCat && fromCat.email) return fromCat.email;
            var opt = userSel.options[userSel.selectedIndex];
            return opt ? (opt.getAttribute('data-email') || '').trim() : '';
        }

        function initials(name) {
            var p = String(name || '').trim().split(/\s+/);
            var a = (p[0] || '?').charAt(0);
            var b = p.length > 1 ? p[p.length - 1].charAt(0) : '';
            return (a + b).toUpperCase();
        }

        function syncUserUi() {
            var pick = document.getElementById('asig-user-pick');
            var chosen = document.getElementById('asig-user-chosen');
            var nameEl = document.getElementById('asig-user-chosen-name');
            var mailEl = document.getElementById('asig-user-chosen-email');
            var avEl = document.getElementById('asig-user-chosen-av');
            var picked = !!userId();
            if (pick) pick.hidden = picked;
            if (chosen) chosen.hidden = !picked;
            if (picked) {
                var nom = userName();
                if (nameEl) nameEl.textContent = nom;
                if (mailEl) mailEl.textContent = userEmail();
                if (avEl) avEl.textContent = initials(nom);
            } else if (userQ) {
                userQ.value = '';
                fillUsers('');
            }
        }

        function pickUser(id) {
            if (!userSel && !id) return;
            var prev = String(userId() || '');
            currentUserId = Number(id) || 0;
            if (userSel) userSel.value = id || '';
            syncUserUi();
            if (prev !== String(currentUserId || '')) {
                if (userSel) userSel.dispatchEvent(new Event('change'));
                else onUserChanged();
            }
        }

        function savedDeEmpresa(emp) {
            var e = String(emp || '').toLowerCase();
            return saved.filter(function (a) { return String(a.empresa || '').toLowerCase() === e; });
        }

        function asignacionDeCentro(emp, codigo) {
            var n = normCode(codigo);
            return savedDeEmpresa(emp).filter(function (a) {
                return normCode(a.centro_codigo) === n;
            })[0] || null;
        }

        function centroYaAsignado(emp, codigo) {
            return !!asignacionDeCentro(emp, codigo);
        }

        function markEmpresaCard(codigo) {
            var grid = document.getElementById('empresa-grid');
            if (!grid) return;
            var needle = String(codigo || '').toLowerCase();
            grid.querySelectorAll('.cc-ciclo-card[data-emp]').forEach(function (card) {
                var emp = String(card.getAttribute('data-emp') || '').toLowerCase();
                var on = emp === needle && needle !== '';
                card.classList.toggle('is-on', on);
                var n = savedDeEmpresa(emp).length;
                var badge = card.querySelector('[data-asig-n]');
                card.classList.toggle('has-asig', n > 0);
                if (badge) {
                    badge.textContent = n ? (n + (n === 1 ? ' centro' : ' centros')) : 'Sin asignar';
                    badge.className = 'cc-badge ' + (n ? 'cc-badge-ink' : 'cc-badge-solo_revision');
                }
                var label = card.querySelector('.cc-card-pick');
                if (label) {
                    label.textContent = on ? 'Seleccionada' : (n ? 'Ya asignada' : 'Seleccionar');
                    label.classList.remove('cc-btn-ink', 'cc-btn-ok');
                    if (on) label.classList.add('cc-btn-ink');
                    else if (n) label.classList.add('cc-btn-ink');
                }
            });
        }

        function renderEmpresaCards() {
            var grid = document.getElementById('empresa-grid');
            if (!grid) return;
            if (!empresas.length) {
                grid.innerHTML = '<div class="cc-empty" style="grid-column:1/-1">No se pudieron leer empresas de AutinApi</div>';
                return;
            }
            var selected = String(cfg.empresa || '').toLowerCase();
            grid.innerHTML = empresas.map(function (e) {
                var code = String(e.codigo || '').toLowerCase();
                var on = selected === code && selected !== '';
                var n = savedDeEmpresa(code).length;
                var badgeCls = n ? 'cc-badge-ink' : 'cc-badge-solo_revision';
                var pickCls = (on || n) ? 'cc-btn-ink' : '';
                var pickLabel = on ? 'Seleccionada' : (n ? 'Ya asignada' : 'Seleccionar');
                return '<article class="cc-ciclo-card is-pickable' + (on ? ' is-on' : '') + (n ? ' has-asig' : '') + '" data-emp="' + escapeHtml(e.codigo) + '" data-nombre="' + escapeHtml(e.nombre) + '" role="button" tabindex="0">' +
                    '<div class="top"><div><div class="code">SAP</div><h3>' + escapeHtml(e.nombre) + '</h3></div>' +
                    '<span class="cc-badge ' + badgeCls + '" data-asig-n>' + (n ? (n + (n === 1 ? ' centro' : ' centros')) : 'Sin asignar') + '</span></div>' +
                    '<div class="cc-ciclo-obs">Clientes y productos de ' + escapeHtml(e.nombre) + ' vía AutinApi.</div>' +
                    '<div class="actions"><span class="text-muted" style="font-size:.75rem">' + (n ? 'Ya tiene centros en resultados' : 'Clic en cualquier parte') + '</span>' +
                    '<span class="cc-btn ' + pickCls + ' cc-card-pick">' + pickLabel + '</span></div></article>';
            }).join('');
            grid.querySelectorAll('.cc-ciclo-card[data-emp]').forEach(function (card) {
                var pick = function () { setEmpresa(card.getAttribute('data-emp'), card.getAttribute('data-nombre')); };
                card.addEventListener('click', pick);
                card.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); pick(); }
                });
            });
        }

        function empresaNombre(emp) {
            var code = String(emp || '').toLowerCase();
            var found = empresas.filter(function (e) { return String(e.codigo || '').toLowerCase() === code; })[0];
            return found ? found.nombre : String(emp || '').toUpperCase();
        }

        function renderResumen() {
            var box = document.getElementById('asig-resultados');
            var tb = document.getElementById('asig-resumen');
            var meta = document.getElementById('asig-resumen-meta');
            var title = document.querySelector('#asig-resultados .cc-panel-head h3');
            if (!box || !tb) return;
            if (title) {
                title.innerHTML = '<i class="fa-solid fa-table"></i> Resultados de asignación' +
                    (userName() ? ' · ' + escapeHtml(userName()) : '');
            }
            if (!userId()) {
                box.hidden = true;
                tb.innerHTML = '';
                if (meta) meta.textContent = '';
                return;
            }
            box.hidden = false;
            if (!saved.length) {
                tb.innerHTML = '<tr><td colspan="5"><div class="cc-empty">Aún no hay clientes en la tabla. Elige empresa, cliente y productos y pulsa Agregar a resultados.</div></td></tr>';
                if (meta) meta.textContent = '';
                return;
            }
            var empSet = {};
            saved.forEach(function (a) { empSet[String(a.empresa || '').toLowerCase()] = true; });
            if (meta) meta.textContent = Object.keys(empSet).length + ' empresas · ' + saved.length + ' centros';
            var rows = saved.slice();
            tb.innerHTML = rows.map(function (a) {
                var key = rowKey(a.empresa, a.centro_codigo);
                var nCtas = (a.cuentas || []).length;
                var cls = [];
                if (a.pending) cls.push('is-pending');
                else cls.push('is-done');
                if (key === lastFlashKey) cls.push('is-new');
                return '<tr class="' + cls.join(' ') + '" data-emp="' + escapeHtml(a.empresa) + '" data-key="' + escapeHtml(key) + '">' +
                    '<td><strong>' + escapeHtml(empresaNombre(a.empresa)) + '</strong></td>' +
                    '<td><div class="fw-semibold">' + escapeHtml(isSinCentro(a.centro_codigo) ? SIN_CENTRO_NOMBRE : a.centro_codigo) + '</div>' +
                    '<div class="text-muted" style="font-size:.75rem">' + escapeHtml(isSinCentro(a.centro_codigo) ? 'Productos sin cliente' : (a.centro_nombre || '')) + '</div></td>' +
                    '<td>' + nCtas + (nCtas === 1 ? ' producto' : ' productos') + '</td>' +
                    '<td>' + permBadges(a.permisos) + '</td>' +
                    '<td><button type="button" class="cc-icon-btn" data-del="' + escapeHtml(a.id) + '" data-emp="' + escapeHtml(a.empresa) + '" data-cc="' + escapeHtml(a.centro_codigo) + '" title="Quitar"><i class="fa-solid fa-trash"></i></button></td>' +
                    '</tr>';
            }).join('');
            tb.querySelectorAll('[data-del]').forEach(function (btn) {
                btn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    quitarFila(btn.getAttribute('data-del'), btn.getAttribute('data-emp'), btn.getAttribute('data-cc'));
                });
            });
        }

        function quitarFila(id, emp, codigo) {
            var i = findSavedIndex(emp, codigo);
            if (i < 0) return;
            var row = saved[i];
            saved.splice(i, 1);
            renderResumen();
            renderEmpresaCards();
            markEmpresaCard(cfg.empresa);
            fillCentros(ccQ ? ccQ.value : '');
            if (isTempId(id) || isTempId(row.id)) return;
            fetch('/Ventas/Asignaciones/' + encodeURIComponent(cfg.ciclo) + '/asignaciones/' + row.id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).catch(function () { /* la fila ya salió de la tabla */ });
        }

        function loadSaved() {
            if (!userId()) {
                saved = [];
                return Promise.resolve([]);
            }
            var url = (cfg.listUrl || ('/Ventas/Asignaciones/' + encodeURIComponent(cfg.ciclo) + '/asignaciones'))
                + '?user_id=' + encodeURIComponent(userId());
            return getJSON(url)
                .then(function (json) {
                    saved = json.asignaciones || [];
                    return saved;
                }).catch(function () {
                    saved = [];
                    return saved;
                });
        }

        function setEmpresa(codigo, nombre) {
            if (!userId()) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Elige el usuario', text: 'Primero selecciona quién va a presupuestar.' });
                return;
            }
            var same = String(cfg.empresa || '').toLowerCase() === String(codigo || '').toLowerCase();
            cfg.empresa = String(codigo || '').toLowerCase();
            var nom = nombre || String(codigo || '').toUpperCase();
            var kicker = document.getElementById('asig-kicker');
            var title = document.getElementById('asig-cc-title');
            if (kicker) kicker.textContent = cfg.ciclo + ' · ' + userName() + ' · ' + nom;
            if (title) title.innerHTML = '<i class="fa-solid fa-sitemap"></i> 3. Cliente (' + nom + ')';
            if (detBox) detBox.hidden = false;
            markEmpresaCard(cfg.empresa);
            renderResumen();
            setStep(3);
            if (same && centros.length) {
                fillCentros(ccQ ? ccQ.value : '');
                loadGrupos();
                return;
            }
            centros = [];
            cuentas = [];
            agrupaciones = [];
            grupos = [];
            ctaSelected = {};
            ctaMask = '';
            ctaGrupo = '';
            if (centroSel) {
                centroSel.innerHTML = '<option value="">Cargando clientes…</option>';
                centroSel.disabled = false;
            }
            var ccList = document.getElementById('asig-cc-list');
            if (ccList) ccList.innerHTML = '<div class="cc-empty">Cargando centros…</div>';
            if (ccQ) { ccQ.disabled = false; ccQ.value = ''; }
            if (ctaQ) { ctaQ.disabled = true; ctaQ.value = ''; }
            var list = document.getElementById('asig-cta-list');
            if (list) list.innerHTML = '<div class="cc-empty">Elige un cliente para ver los productos</div>';
            fillMaskUi('asig-cta-mask', [], [], '', setCtaMask);
            fillGrupoUi('asig-cta-grupo', [], '', setCtaGrupo);
            loadCentros();
            loadGrupos();
        }

        function unlockEmpresas() {
            if (empBox) empBox.classList.toggle('is-locked', !userId());
            var hint = document.getElementById('asig-emp-hint');
            if (hint) {
                hint.textContent = userId()
                    ? 'Elige la empresa. Luego el cliente. Puedes cambiar de empresa cuando quieras; lo guardado se acumula abajo.'
                    : 'Elige un usuario para habilitar las empresas.';
            }
        }

        function loadEmpresas() {
            getJSON('/ProyeccionesVentas/api/empresas?ciclo=' + encodeURIComponent(cfg.ciclo)).then(function (json) {
                empresas = json.empresas || [];
                renderEmpresaCards();
                var flag = document.getElementById('sap-flag');
                if (flag && empresas.length) {
                    flag.textContent = 'Catálogo SAP';
                    flag.className = 'cc-sap-flag';
                }
                markEmpresaCard(cfg.empresa);
            }).catch(function () {
                empresas = [];
                renderEmpresaCards();
            });
        }

        function anioRef() {
            return Number((window.CC && CC.state && (CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia))) || new Date().getFullYear());
        }

        function loadCentros() {
            var meta = document.getElementById('asig-cc-meta');
            if (meta) meta.textContent = 'Cargando clientes SAP…';
            var year = anioRef();
            var key = String(cfg.empresa || '').toLowerCase() + '|' + year;
            var apply = function (rows, mensaje) {
                centros = rows || [];
                syncCentroSelect(centros);
                fillCentros(ccQ ? ccQ.value : '');
                if (meta) {
                    if (centros.length) {
                        var base = centros.length + ' clientes en ' + String(cfg.empresa).toUpperCase()
                            + (savedDeEmpresa(cfg.empresa).length ? ' · ' + savedDeEmpresa(cfg.empresa).length + ' ya asignados' : '');
                        meta.textContent = mensaje ? (base + ' · ' + mensaje) : base;
                    } else {
                        meta.textContent = mensaje || 'Sin clientes en AutinApi';
                    }
                }
            };
            if (cacheCentros[key]) {
                apply(cacheCentros[key], null);
                return;
            }
            getJSON('/ProyeccionesVentas/api/centros?empresa=' + encodeURIComponent(cfg.empresa) + '&year=' + encodeURIComponent(year)).then(function (json) {
                var rows = json.centros || [];
                if (json.ok && rows.length) cacheCentros[key] = rows;
                apply(rows, json.mensaje);
            }).catch(function () {
                apply([], 'No se pudieron cargar los clientes');
            });
        }

        function syncCentroSelect(rows) {
            if (!centroSel) return;
            var keep = centroSel.value;
            centroSel.innerHTML = '<option value="">Elige un cliente…</option>' + (rows || []).map(function (c) {
                return '<option value="' + escapeHtml(c.codigo) + '" data-nombre="' + escapeHtml(c.nombre || '') + '">' +
                    escapeHtml((c.nombre || c.codigo) + ' · ' + c.codigo) + '</option>';
            }).join('');
            if (keep) centroSel.value = keep;
        }

        function fillCentros(q) {
            if (!centroSel) return;
            var list = document.getElementById('asig-cc-list');
            var meta = document.getElementById('asig-cc-meta');
            q = String(q || '');
            var current = String(centroSel.value || '');
            var rows = centros.filter(function (c) {
                if (isSinCentro(c.codigo)) return false;
                return matchQuery((c.codigo || '') + ' ' + (c.nombre || ''), q);
            });
            if (current) {
                var selected = centros.filter(function (c) { return String(c.codigo) === current; })[0];
                if (selected && !rows.some(function (r) { return String(r.codigo) === current; })) {
                    rows.unshift(selected);
                }
            }
            if (!list) return;
            if (!centros.length) {
                list.innerHTML = '<div class="cc-empty">Elige una empresa para ver clientes</div>';
                return;
            }
            if (!rows.length) {
                list.innerHTML = '<div class="cc-empty">Sin coincidencias para “' + escapeHtml(q.trim()) + '”</div>';
                if (meta && q.trim()) {
                    meta.textContent = '0 de ' + centros.length + ' clientes';
                }
                return;
            }
            if (meta) {
                meta.textContent = q.trim()
                    ? (rows.length + ' de ' + centros.length + ' clientes')
                    : (centros.length + ' clientes en ' + String(cfg.empresa || '').toUpperCase() + (savedDeEmpresa(cfg.empresa).length ? ' · ' + savedDeEmpresa(cfg.empresa).length + ' ya asignados' : ''));
            }
            list.innerHTML = rows.map(function (c) {
                var done = centroYaAsignado(cfg.empresa, c.codigo);
                var on = current !== '' && String(c.codigo) === current;
                var cls = 'cc-cc-item' + (done ? ' is-done' : '') + (on ? ' is-on' : '');
                return '<button type="button" class="' + cls + '" role="option" data-codigo="' + escapeHtml(c.codigo) + '" data-nombre="' + escapeHtml(c.nombre || '') + '" aria-selected="' + (on ? 'true' : 'false') + '">' +
                    '<span><span class="cc-cc-code">' + escapeHtml(c.nombre || c.codigo) + '</span>' +
                    '<span class="cc-cc-name">' + escapeHtml(c.codigo) + '</span></span>' +
                    (done ? '<span class="cc-badge cc-badge-done">Asignado</span>' : '') +
                    '</button>';
            }).join('');
            list.querySelectorAll('.cc-cc-item').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    pickCentro(btn.getAttribute('data-codigo'), btn.getAttribute('data-nombre'));
                });
            });
        }

        function pickCentro(codigo, nombre) {
            if (!centroSel) return;
            var exists = false;
            Array.prototype.forEach.call(centroSel.options, function (opt) {
                if (opt.value === codigo) exists = true;
            });
            if (!exists && codigo) {
                var o = document.createElement('option');
                o.value = codigo;
                o.setAttribute('data-nombre', nombre || '');
                o.textContent = codigo;
                centroSel.appendChild(o);
            }
            centroSel.value = codigo || '';
            var list = document.getElementById('asig-cc-list');
            if (list) {
                list.querySelectorAll('.cc-cc-item').forEach(function (btn) {
                    var on = btn.getAttribute('data-codigo') === String(codigo);
                    btn.classList.toggle('is-on', on);
                    btn.setAttribute('aria-selected', on ? 'true' : 'false');
                });
            }
            if (codigo) {
                setStep(4);
                if (ctaQ) ctaQ.disabled = false;
                seedCtaSelected();
                loadCuentas();
            }
        }

        function seedCtaSelected() {
            ctaSelected = {};
            var current = centroSel ? centroSel.value : '';
            var prev = asignacionDeCentro(cfg.empresa, current);
            ((prev && prev.cuentas) || []).forEach(function (c) {
                var codigo = c.codigo || c.cuenta_codigo || '';
                if (!codigo) return;
                ctaSelected[normCode(codigo)] = {
                    codigo: codigo,
                    nombre: c.nombre || c.cuenta_nombre || '',
                    agrupacion: c.agrupacion || c.grupo || ''
                };
            });
            var map = grupoCuentaMap(grupos, ctaGrupo);
            if (map) {
                mergeGrupoCuentas(ctaSelected, map, cacheCuentas[cfg.empresa] || cuentas, normCode);
            }
        }

        function setCtaGrupo(id) {
            ctaGrupo = String(id || '');
            fillGrupoUi('asig-cta-grupo', grupos, ctaGrupo, setCtaGrupo);
            var map = grupoCuentaMap(grupos, ctaGrupo);
            if (map) {
                mergeGrupoCuentas(ctaSelected, map, cacheCuentas[cfg.empresa] || cuentas, normCode);
            }
            renderCuentas(ctaQ ? ctaQ.value : '');
        }

        function loadGrupos() {
            grupos = [];
            fillGrupoUi('asig-cta-grupo', [], '', setCtaGrupo);
        }

        function setCtaMask(mask) {
            ctaMask = String(mask || '');
            fillMaskUi('asig-cta-mask', agrupaciones, cuentas, ctaMask, setCtaMask);
            if (!ctaMask) {
                var all = cacheCuentas[cfg.empresa];
                if (all) cuentas = all;
                renderCuentas(ctaQ ? ctaQ.value : '');
                return;
            }
            var key = String(cfg.empresa || '') + '|' + ctaMask;
            if (cacheCuentas[key]) {
                cuentas = cacheCuentas[key];
                renderCuentas(ctaQ ? ctaQ.value : '');
                return;
            }
            var box = document.getElementById('asig-cta-list');
            if (box) box.innerHTML = '<div class="cc-empty">Cargando GroupMask ' + escapeHtml(ctaMask) + '…</div>';
            getJSON('/ProyeccionesVentas/api/cuentas?todas=1&empresa=' + encodeURIComponent(cfg.empresa) +
                '&group_mask=' + encodeURIComponent(ctaMask)).then(function (json) {
                if (String(ctaMask) !== String(mask || '')) return;
                cacheCuentas[key] = json.cuentas || [];
                if (json.agrupaciones && json.agrupaciones.length) {
                    agrupaciones = json.agrupaciones;
                }
                cuentas = cacheCuentas[key];
                fillMaskUi('asig-cta-mask', agrupaciones, cuentas, ctaMask, setCtaMask);
                renderCuentas(ctaQ ? ctaQ.value : '');
            }).catch(function () {
                renderCuentas(ctaQ ? ctaQ.value : '');
            });
        }

        function syncCtaSelectedFromDom() {
            document.querySelectorAll('#asig-cta-list [data-cta]').forEach(function (i) {
                var key = normCode(i.value);
                if (i.checked) {
                    ctaSelected[key] = {
                        codigo: i.value,
                        nombre: i.getAttribute('data-nombre') || '',
                        agrupacion: i.getAttribute('data-grupo') || '',
                        costo: Number(i.getAttribute('data-costo') || 0) || 0,
                        precio: Number(i.getAttribute('data-costo') || 0) || 0,
                        moneda: String(i.getAttribute('data-moneda') || 'MXN').toUpperCase()
                    };
                } else {
                    delete ctaSelected[key];
                }
            });
            updateCtaSelCount();
        }

        function updateCtaSelCount() {
            var el = document.getElementById('asig-cta-sel');
            if (!el) return;
            var n = Object.keys(ctaSelected).length;
            el.textContent = n + (n === 1 ? ' seleccionada' : ' seleccionadas');
        }

        function loadCuentas() {
            var box = document.getElementById('asig-cta-list');
            var cliente = centroSel ? String(centroSel.value || '').trim() : '';
            if (!cliente) {
                box.innerHTML = '<div class="cc-empty">Elige un cliente para ver sus productos (ItemName)</div>';
                return;
            }
            var year = anioRef();
            box.innerHTML = '<div class="cc-empty">Cargando productos de OINV + ORIN…</div>';
            var key = String(cfg.empresa || '').toLowerCase() + '|' + year + '|' + cliente.toUpperCase();
            var apply = function (rows, groups) {
                cuentas = rows || [];
                if (groups && groups.length) agrupaciones = groups;
                fillMaskUi('asig-cta-mask', agrupaciones, cuentas, ctaMask, setCtaMask);
                fillGrupoUi('asig-cta-grupo', grupos, ctaGrupo, setCtaGrupo);
                if (ctaMask) {
                    setCtaMask(ctaMask);
                    return;
                }
                renderCuentas(ctaQ ? ctaQ.value : '');
            };
            if (cacheCuentas[key]) {
                apply(cacheCuentas[key], agrupaciones);
                return;
            }
            getJSON('/ProyeccionesVentas/api/cuentas?empresa=' + encodeURIComponent(cfg.empresa) +
                '&year=' + encodeURIComponent(year) +
                '&cliente=' + encodeURIComponent(cliente) +
                '&cc=' + encodeURIComponent(cliente)).then(function (json) {
                var rows = json.cuentas || [];
                if (json.ok && rows.length) cacheCuentas[key] = rows;
                if (!rows.length && json.mensaje) {
                    box.innerHTML = '<div class="cc-empty">' + String(json.mensaje) + '</div>';
                    cuentas = [];
                    return;
                }
                apply(rows, json.agrupaciones || []);
            }).catch(function () {
                apply([], []);
            });
        }

        function renderCuentas(q) {
            q = (q || '').toLowerCase();
            var box = document.getElementById('asig-cta-list');
            var mask = String(ctaMask || '');
            var grupoMap = grupoCuentaMap(grupos, ctaGrupo);
            var rows = filterRowsByGrupo(cuentas, grupoMap).filter(function (c) {
                return matchQuery((c.codigo || '') + ' ' + ctaPretty(c.codigo) + ' ' + (c.nombre || ''), q);
            });
            if (!rows.length) {
                box.innerHTML = '<div class="cc-empty">Sin productos para este catálogo</div>';
                updateCtaSelCount();
                return;
            }
            var current = centroSel ? centroSel.value : '';
            var prev = asignacionDeCentro(cfg.empresa, current);
            box.innerHTML = '<div class="cc-cta-group">' + rows.map(function (c) {
                        var checked = ctaSelected[normCode(c.codigo)] ? ' checked' : '';
                        var costo = Number(c.costo || c.precio || 0) || 0;
                        var moneda = String(c.costo_moneda || c.moneda || 'MXN').toUpperCase();
                        return '<label class="cc-cta-item"><input type="checkbox" data-cta="1" value="' + escapeHtml(c.codigo) + '"' +
                            ' data-nombre="' + escapeHtml(c.nombre) + '" data-grupo="' + escapeHtml(c.grupo || '') + '"' +
                            ' data-mask="' + escapeHtml(c.grupo_id || '') + '"' +
                            ' data-costo="' + escapeHtml(String(costo)) + '"' +
                            ' data-moneda="' + escapeHtml(moneda) + '"' + checked + '>' +
                            '<span class="cc-cta-name">' + escapeHtml(c.nombre || c.codigo) + '</span>' +
                            '<span class="cc-cta-code">' + escapeHtml(ctaPretty(c.codigo)) + '</span></label>';
                    }).join('') + '</div>';
            syncPermisos(prev ? prev.permisos : null);
            var boxes = box.querySelectorAll('[data-cta]');
            var nOn = 0;
            boxes.forEach(function (i) { if (i.checked) nOn += 1; });
            var todas = document.getElementById('asig-cta-todas');
            if (todas) todas.checked = boxes.length > 0 && nOn === boxes.length;
            updateCtaSelCount();
        }

        function syncPermisos(perms) {
            var set = {};
            var list = (perms && perms.length) ? perms : ['capturar'];
            list.forEach(function (p) { set[String(p)] = true; });
            document.querySelectorAll('#asig-permisos input[name="permiso"]').forEach(function (i) {
                i.checked = !!set[i.value];
            });
        }

        function etiquetaGrupo(g) {
            var s = String(g || '').trim();
            var map = {
                '1': 'Activo',
                '2': 'Pasivo',
                '3': 'Capital',
                '4': 'Ingresos',
                '5': 'Costo de ventas',
                '6': 'Gastos',
                '7': 'Otros ingresos y gastos',
                '8': 'Otros'
            };
            if (map[s]) return map[s];
            if (!s || /^\d+$/.test(s) || s.toLowerCase() === 'sin agrupación') return '';
            return s;
        }

        function resetDetalleParcial() {
            if (centroSel) centroSel.selectedIndex = 0;
            if (ctaQ) { ctaQ.disabled = true; ctaQ.value = ''; }
            ctaSelected = {};
            ctaMask = '';
            ctaGrupo = '';
            var list = document.getElementById('asig-cta-list');
            if (list) list.innerHTML = '<div class="cc-empty">Elige un cliente para ver los productos</div>';
            var todas = document.getElementById('asig-cta-todas');
            if (todas) todas.checked = false;
            fillMaskUi('asig-cta-mask', agrupaciones, cuentas, '', setCtaMask);
            fillGrupoUi('asig-cta-grupo', grupos, '', setCtaGrupo);
            updateCtaSelCount();
            setStep(cfg.empresa ? 3 : (userId() ? 2 : 1));
        }

        function onUserChanged() {
            var kicker = document.getElementById('asig-kicker');
            if (!userId()) {
                unlockEmpresas();
                if (kicker) kicker.textContent = cfg.ciclo;
                if (detBox) detBox.hidden = true;
                saved = [];
                renderEmpresaCards();
                renderResumen();
                setStep(1);
                return;
            }
            if (kicker) kicker.textContent = cfg.empresa
                ? (cfg.ciclo + ' · ' + userName() + ' · ' + String(cfg.empresa).toUpperCase())
                : (cfg.ciclo + ' · ' + userName());
            setStep(cfg.empresa ? 3 : 2);
            loadSaved().then(function () {
                unlockEmpresas();
                renderEmpresaCards();
                markEmpresaCard(cfg.empresa);
                renderResumen();
                if (cfg.empresa) {
                    fillCentros(ccQ ? ccQ.value : '');
                    if (centroSel && centroSel.value) renderCuentas(ctaQ ? ctaQ.value : '');
                }
            });
        }

        if (userSel) userSel.addEventListener('change', onUserChanged);

        if (ccQ) {
            var onCcSearch = function () { fillCentros(ccQ.value); };
            ccQ.addEventListener('input', onCcSearch);
            ccQ.addEventListener('search', onCcSearch);
            ccQ.addEventListener('keyup', onCcSearch);
        }

        if (centroSel) centroSel.addEventListener('change', function () {
            if (!centroSel.value) return;
            setStep(4);
            if (ctaQ) ctaQ.disabled = false;
            loadCuentas();
        });

        if (ctaQ) ctaQ.addEventListener('input', function () {
            syncCtaSelectedFromDom();
            renderCuentas(ctaQ.value);
        });
        var todasEl = document.getElementById('asig-cta-todas');
        if (todasEl) todasEl.addEventListener('change', function () {
            var on = this.checked;
            document.querySelectorAll('#asig-cta-list [data-cta]').forEach(function (i) { i.checked = on; });
            syncCtaSelectedFromDom();
        });
        var listEl = document.getElementById('asig-cta-list');
        if (listEl) listEl.addEventListener('change', function (ev) {
            var t = ev.target;
            if (t && t.getAttribute('data-cta-mask') != null) {
                var on = t.checked;
                listEl.querySelectorAll('[data-cta]').forEach(function (i) {
                    if (String(i.getAttribute('data-mask') || '') === String(t.getAttribute('data-cta-mask') || '')) {
                        i.checked = on;
                    }
                });
            }
            setStep(4);
            syncCtaSelectedFromDom();
            renderCuentas(ctaQ ? ctaQ.value : '');
        });

        function recogerPayload() {
            if (!userId()) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Elige el usuario', text: 'Primero selecciona quién va a presupuestar.' });
                return null;
            }
            if (!cfg.empresa) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Elige una empresa', text: 'Selecciona la tarjeta de empresa.' });
                return null;
            }
            var opt = centroSel && centroSel.options[centroSel.selectedIndex];
            syncCtaSelectedFromDom();
            var ctas = Object.keys(ctaSelected).map(function (k) { return ctaSelected[k]; });
            var perms = [];
            document.querySelectorAll('#asig-permisos input[name="permiso"]:checked').forEach(function (i) {
                perms.push(i.value);
            });
            var payload = {
                empresa: cfg.empresa,
                user_id: userId(),
                centro_codigo: centroSel ? centroSel.value : '',
                centro_nombre: opt ? (opt.getAttribute('data-nombre') || '') : '',
                cuentas: ctas,
                permisos: perms
            };
            if (!payload.centro_codigo) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Falta el cliente', text: 'Elige un cliente o la opción Sin cliente.' });
                return null;
            }
            if (isSinCentro(payload.centro_codigo)) {
                payload.centro_codigo = SIN_CENTRO_CODIGO;
                payload.centro_nombre = SIN_CENTRO_NOMBRE;
            }
            if (!ctas.length) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Sin productos', text: 'Selecciona al menos un producto.' });
                return null;
            }
            return payload;
        }

        function aplicarAResultados() {
            var payload = recogerPayload();
            if (!payload) return;
            var existente = findSavedIndex(payload.empresa, payload.centro_codigo);
            var local = {
                id: existente >= 0 && !isTempId(saved[existente].id) ? saved[existente].id : ('tmp-' + Date.now()),
                ciclo: cfg.ciclo,
                empresa: payload.empresa,
                user_id: payload.user_id,
                usuario: userName(),
                centro_codigo: payload.centro_codigo,
                centro_nombre: payload.centro_nombre,
                cuentas: payload.cuentas.slice(),
                permisos: payload.permisos.slice(),
                pending: true
            };
            upsertSaved(local);
            renderResumen();
            renderEmpresaCards();
            markEmpresaCard(cfg.empresa);
            resetDetalleParcial();
            fillCentros(ccQ ? ccQ.value : '');
            var box = document.getElementById('asig-resultados');
            if (box) box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

            fetch(cfg.storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
                .then(function (res) {
                    if (!res.ok) {
                        if (window.Swal) Swal.fire({ icon: 'error', title: 'No se guardó en servidor', text: (res.json && res.json.message) || 'La fila ya está en resultados; vuelve a agregar si hace falta.' });
                        return;
                    }
                    var savedRow = (res.json && res.json.asignacion) ? res.json.asignacion : local;
                    savedRow.pending = false;
                    upsertSaved(savedRow);
                    renderResumen();
                    renderEmpresaCards();
                    markEmpresaCard(cfg.empresa);
                    fillCentros(ccQ ? ccQ.value : '');
                    var nMaestro = Number(res.json && res.json.maestro_creados) || 0;
                    if (nMaestro > 0 && window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Asignación guardada',
                            text: nMaestro + (nMaestro === 1
                                ? ' producto nuevo en el maestro de precios.'
                                : ' productos nuevos en el maestro de precios.'),
                            timer: 1600,
                            showConfirmButton: false
                        });
                    }
                }).catch(function () {
                    if (window.Swal) Swal.fire({ icon: 'error', title: 'No se guardó en servidor', text: 'La fila ya está en la tabla de resultados.' });
                });
        }

        if (btnSave) btnSave.addEventListener('click', function (ev) {
            ev.preventDefault();
            aplicarAResultados();
        });

        var btnFinGuardar = document.getElementById('asig-fin-guardar');
        if (btnFinGuardar) btnFinGuardar.addEventListener('click', function (ev) {
            ev.preventDefault();
            if (!saved.length) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Nada que guardar', text: 'Asigna al menos un centro a la tabla de resultados.' });
                return;
            }
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Asignaciones guardadas',
                    text: saved.length + (saved.length === 1 ? ' centro asignado.' : ' centros asignados.'),
                    timer: 1400,
                    showConfirmButton: false
                });
            }
        });

        var btnFinSeguir = document.getElementById('asig-fin-seguir');
        if (btnFinSeguir) btnFinSeguir.addEventListener('click', function (ev) {
            ev.preventDefault();
            if (!saved.length) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Nada que guardar', text: 'Asigna al menos un centro a la tabla de resultados.' });
                return;
            }
            var goCiclo = function () {
                window.location.href = cfg.cicloUrl || ('/Ventas/Asignaciones/' + encodeURIComponent(cfg.ciclo));
            };
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Asignaciones guardadas',
                    text: saved.length + (saved.length === 1 ? ' centro asignado.' : ' centros asignados.'),
                    timer: 1400,
                    showConfirmButton: false
                }).then(goCiclo);
            } else {
                goCiclo();
            }
        });

        if (userQ) userQ.addEventListener('input', function () { fillUsers(userQ.value); });
        var userClear = document.getElementById('asig-user-clear');
        if (userClear) userClear.addEventListener('click', function (ev) {
            ev.preventDefault();
            pickUser('');
        });

        unlockEmpresas();
        loadEmpresas();
        fillUsers('');
        setStep(1);
    };

    function setStep(n) {
        document.querySelectorAll('.cc-step').forEach(function (el) {
            el.classList.toggle('is-on', Number(el.getAttribute('data-step')) <= n);
        });
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    window.CCAsig = CCAsig;
})(window, document);
