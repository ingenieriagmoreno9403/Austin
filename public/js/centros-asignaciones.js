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
    var SIN_CENTRO_NOMBRE = 'Sin centro de costos';
    var EMPRESA_CODIGO = 'EMPRESA';
    var EMPRESA_NOMBRE = 'Toda la empresa';

    function isSinCentro(codigo) {
        return String(codigo || '').replace(/\s+/g, '').toUpperCase() === SIN_CENTRO_CODIGO;
    }

    function isEmpresaCompleta(codigo) {
        return String(codigo || '').replace(/\s+/g, '').toUpperCase() === EMPRESA_CODIGO;
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
        if (isEmpresaCompleta(codigo)) return EMPRESA_NOMBRE;
        if (isSinCentro(codigo)) return SIN_CENTRO_NOMBRE;
        var code = String(codigo || '').trim();
        var nom = String(nombre || '').trim();
        if (code && nom && nom !== code) return code + ' — ' + nom;
        return nom || code || '—';
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

    function ctaNameKey(nombre) {
        return String(nombre || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function indexCatalogo(catalog) {
        var byCode = {};
        var byName = {};
        (catalog || []).forEach(function (c) {
            var k = ctaNorm(c.codigo);
            if (k && !byCode[k]) byCode[k] = c;
            var n = ctaNameKey(c.nombre);
            if (!n) return;
            if (!byName[n]) byName[n] = [];
            byName[n].push(c);
        });
        return { byCode: byCode, byName: byName };
    }

    function cuentaDeGrupo(fromG, index) {
        var code = ctaNorm((fromG && (fromG.codigo || fromG.cuenta_codigo)) || '');
        if (code && index.byCode[code]) return index.byCode[code];
        var n = ctaNameKey(fromG && (fromG.nombre || fromG.cuenta_nombre));
        var hits = (n && index.byName[n]) || [];
        if (hits.length === 1) return hits[0];
        var conCodigo = hits.filter(function (c) { return !!ctaNorm(c.codigo); });
        if (conCodigo.length === 1) return conCodigo[0];
        return null;
    }

    function filterRowsByGrupo(rows, map) {
        if (!map) return rows || [];
        var index = indexCatalogo(rows);
        var out = [];
        var seen = {};
        Object.keys(map).forEach(function (k) {
            var fromG = map[k];
            var hit = cuentaDeGrupo(fromG, index);
            var row = hit || {
                codigo: (fromG && (fromG.codigo || fromG.cuenta_codigo)) || '',
                nombre: (fromG && (fromG.nombre || fromG.cuenta_nombre)) || '',
                grupo: (fromG && (fromG.agrupacion || fromG.grupo)) || '',
                grupo_id: (fromG && fromG.grupo_id) || ''
            };
            var key = ctaNorm(row.codigo) || ('n:' + ctaNameKey(row.nombre));
            if (!row.codigo || seen[key]) return;
            seen[key] = true;
            out.push(row);
        });
        return out;
    }

    function mergeGrupoCuentas(selected, map, catalog, keyFn) {
        if (!selected || !map) return;
        var index = indexCatalogo(catalog);
        Object.keys(map).forEach(function (k) {
            var fromG = map[k];
            var fromCat = cuentaDeGrupo(fromG, index);
            var codigo = (fromCat && fromCat.codigo) || (fromG && (fromG.codigo || fromG.cuenta_codigo)) || '';
            if (!codigo) return;
            var storeKey = keyFn ? keyFn(codigo) : String(codigo);
            var raw = (fromG && (fromG.codigo || fromG.cuenta_codigo)) || '';
            var rawKey = raw ? (keyFn ? keyFn(raw) : String(raw)) : '';
            if (rawKey && rawKey !== storeKey) delete selected[rawKey];
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
        { clave: 'capturar', nombre: 'Capturar', descripcion: 'Puede capturar presupuesto mientras el budget esté Abierto' },
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
        bindActualizarSapCiclo();
        loadEmpresasKpi();
        reloadTabla();
    };

    function bindActualizarSapCiclo() {
        var btn = document.getElementById('ciclo-sap');
        if (!btn || btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', abrirModalSap);
        var go = document.getElementById('ciclo-sap-go');
        var cancel = document.getElementById('ciclo-sap-cancelar');
        var reintento = document.getElementById('ciclo-sap-reintentar');
        var sel = document.getElementById('ciclo-sap-empresa');
        if (go) go.addEventListener('click', empezarActualizacionSap);
        if (cancel) cancel.addEventListener('click', function () { CCAsig._sapParar = true; });
        if (reintento) reintento.addEventListener('click', reintentarFallosSap);
        if (sel) sel.addEventListener('change', pintarMetaSap);
    }

    function gruposSapDeAsignaciones(rows) {
        var grupos = {};
        (rows || []).forEach(function (row) {
            var emp = String(row.empresa || '').trim();
            var cc = String(row.centro_codigo || '').trim().toUpperCase();
            if (!emp || !cc || cc === 'SIN_CC' || cc === 'EMPRESA') return;
            var key = emp.toUpperCase();
            if (!grupos[key]) grupos[key] = { empresa: emp, centros: [] };
            if (!grupos[key].centros.some(function (c) { return c.cc === cc; })) {
                grupos[key].centros.push({ empresa: emp, cc: cc });
            }
        });
        return Object.keys(grupos).sort().map(function (k) { return grupos[k]; });
    }

    function pintarProgresoSap(hecho, total, texto) {
        var label = document.getElementById('ciclo-sap-label');
        var bar = document.getElementById('ciclo-sap-bar');
        var pct = total ? Math.round((hecho / total) * 100) : 0;
        if (label) label.textContent = texto || '';
        if (bar) bar.style.width = pct + '%';
    }

    function gastoListo(estado) {
        return estado === 'registrado' || estado === 'actualizado' || estado === 'sin_cambio';
    }

    function statsSap() {
        if (!CCAsig._sapStats) CCAsig._sapStats = { registrados: 0, actualizados: 0, sinCambio: 0, fallidos: 0 };
        return CCAsig._sapStats;
    }

    function pintarContadoresSap() {
        var s = statsSap();
        var reg = document.getElementById('ciclo-sap-reg');
        var act = document.getElementById('ciclo-sap-act');
        var igual = document.getElementById('ciclo-sap-igual');
        var fail = document.getElementById('ciclo-sap-fail');
        if (reg) reg.textContent = String(s.registrados);
        if (act) act.textContent = String(s.actualizados);
        if (igual) igual.textContent = String(s.sinCambio || 0);
        if (fail) fail.textContent = String(s.fallidos);
    }

    function grupoSapElegido() {
        var sel = document.getElementById('ciclo-sap-empresa');
        var grupos = CCAsig._sapGrupos || [];
        var i = sel ? parseInt(sel.value, 10) : -1;
        return grupos[i] || null;
    }

    function pintarMetaSap() {
        var meta = document.getElementById('ciclo-sap-meta');
        var go = document.getElementById('ciclo-sap-go');
        var g = grupoSapElegido();
        if (meta) {
            meta.textContent = g
                ? (g.centros.length + ' centros asignados en ' + g.empresa + '.')
                : 'Todavía no hay una empresa elegida.';
        }
        if (go && !CCAsig._sapCorriendo) go.disabled = !g;
    }

    function abrirModalSap() {
        var modal = document.getElementById('modalCicloSap');
        if (CCAsig._sapCorriendo) {
            if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).show();
            return;
        }
        var rows = filasSapEnPantalla();
        if (rows.length) {
            prepararModalSap(rows);
            return;
        }
        var ciclo = cicloCodigoPagina();
        getJSON('/AdminCentros/' + encodeURIComponent(ciclo) + '/asignaciones').then(function (json) {
            CCAsig._rows = (json && json.asignaciones) || [];
            prepararModalSap(CCAsig._rows);
        }).catch(function () {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Sin asignaciones', text: 'No se pudieron leer los centros de este ciclo.' });
        });
    }

    function prepararModalSap(rows) {
        CCAsig._sapGrupos = gruposSapDeAsignaciones(rows);
        CCAsig._sapStats = { registrados: 0, actualizados: 0, sinCambio: 0, fallidos: 0 };
        pintarContadoresSap();
        var sel = document.getElementById('ciclo-sap-empresa');
        var fallos = document.getElementById('ciclo-sap-fallos');
        if (fallos) fallos.innerHTML = '';
        habilitarReintentosSap(false);
        pintarProgresoSap(0, 1, '');
        if (sel) {
            var html = '<option value="">Seleccionar empresa</option>';
            CCAsig._sapGrupos.forEach(function (g, i) {
                html += '<option value="' + i + '">' + escapeHtml(String(g.empresa || '').toUpperCase()) + ' · ' + g.centros.length + ' centros</option>';
            });
            sel.innerHTML = html;
            sel.disabled = !CCAsig._sapGrupos.length;
        }
        pintarMetaSap();
        var modal = document.getElementById('modalCicloSap');
        if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).show();
        if (!CCAsig._sapGrupos.length && window.Swal) {
            Swal.fire({ icon: 'warning', title: 'Sin centros de SAP', text: 'Las asignaciones de este ciclo no tienen un centro de costo de SAP.' });
        }
    }

    function marcarControlesSap(corriendo) {
        var sel = document.getElementById('ciclo-sap-empresa');
        var go = document.getElementById('ciclo-sap-go');
        var cancel = document.getElementById('ciclo-sap-cancelar');
        var x = document.getElementById('ciclo-sap-x');
        if (sel) sel.disabled = corriendo || !(CCAsig._sapGrupos || []).length;
        if (go) go.disabled = corriendo || !grupoSapElegido();
        if (cancel) cancel.disabled = !corriendo;
        if (x) x.disabled = corriendo;
    }

    function anotarFalloSap(c, texto) {
        statsSap().fallidos += 1;
        pintarContadoresSap();
        var list = document.getElementById('ciclo-sap-fallos');
        if (!list) return;
        var li = document.createElement('li');
        li.setAttribute('data-empresa', c.empresa || '');
        li.setAttribute('data-cc', c.cc || '');
        var span = document.createElement('span');
        var btn = document.createElement('button');
        span.textContent = c.cc + (texto ? ' — ' + texto : '');
        btn.type = 'button';
        btn.className = 'cc-btn cc-sap-reintento';
        btn.textContent = 'Reintentar';
        btn.disabled = true;
        btn.addEventListener('click', function () {
            reintentarCentroSap({ empresa: c.empresa, cc: c.cc }, li, span, btn);
        });
        li.appendChild(span);
        li.appendChild(btn);
        list.appendChild(li);
    }

    function habilitarReintentosSap(on) {
        document.querySelectorAll('#ciclo-sap-fallos .cc-sap-reintento').forEach(function (btn) {
            btn.disabled = !on;
        });
        var todos = document.getElementById('ciclo-sap-reintentar');
        var n = document.querySelectorAll('#ciclo-sap-fallos li').length;
        if (todos) {
            todos.hidden = n < 1;
            todos.disabled = !on || n < 1;
        }
    }

    function cerrarIntentoSap(c, li, span, pack) {
        var estado = pack && pack.json && pack.json.estado;
        if (pack && pack.ok && gastoListo(estado)) {
            if (statsSap().fallidos > 0) statsSap().fallidos -= 1;
            if (estado === 'registrado') statsSap().registrados += 1;
            else if (estado === 'actualizado') statsSap().actualizados += 1;
            else statsSap().sinCambio += 1;
            pintarContadoresSap();
            if (li && li.parentNode) li.remove();
            return true;
        }
        if (span) span.textContent = c.cc + ' — ' + ((pack && pack.json && (pack.json.mensaje || pack.json.message)) || 'No se pudo guardar');
        return false;
    }

    function avisarSiNoQuedanFallos() {
        if (document.querySelector('#ciclo-sap-fallos li')) return false;
        pintarProgresoSap(1, 1, 'Listo.');
        habilitarReintentosSap(false);
        var aviso = window.Swal
            ? Swal.fire({
                icon: 'success',
                title: 'Gasto actualizado',
                text: 'Los centros que faltaban ya quedaron guardados.'
            })
            : Promise.resolve();
        aviso.then(function () { window.location.reload(); });
        return true;
    }

    function correrCentroSap(c, year, corrida, onTexto) {
        function postCentro(desdeJson) {
            return fetch('/CentrosCostos/api/gasto-real/sincronizar-centro', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    year: year,
                    empresa: c.empresa,
                    cc: c.cc,
                    corrida: corrida,
                    desde_json: desdeJson ? 1 : 0
                })
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (json) {
                    return { ok: res.ok, json: json || {} };
                });
            });
        }
        function esperarArchivo(intentos, sinCorrida, listoVeces) {
            if (CCAsig._sapParar) return Promise.reject(new Error('pausa'));
            var url = '/CentrosCostos/api/gasto-real/sincronizar-centro/estado'
                + '?empresa=' + encodeURIComponent(c.empresa)
                + '&cc=' + encodeURIComponent(c.cc)
                + '&year=' + encodeURIComponent(year)
                + '&corrida=' + encodeURIComponent(corrida);
            return getJSON(url).catch(function () { return {}; }).then(function (json) {
                var misma = json && json.corrida === corrida;
                if (misma && json.insertado && gastoListo(json.estado)) {
                    return { ok: true, json: json };
                }
                if (misma && json.estado === 'fallo' && !json.listo) {
                    return { ok: false, json: json };
                }
                if (misma && json.listo && !json.insertado) {
                    sinCorrida = 0;
                    listoVeces += 1;
                    if (listoVeces >= 6) return postCentro(true);
                } else {
                    listoVeces = 0;
                    if (!misma) sinCorrida += 1;
                }
                if (intentos <= 0 || sinCorrida >= 8) throw new Error('sin archivo');
                if (onTexto) onTexto('La conexión de ' + c.cc + ' sigue en el archivo…');
                return new Promise(function (resolve, reject) {
                    setTimeout(function () {
                        esperarArchivo(intentos - 1, misma ? 0 : sinCorrida, listoVeces).then(resolve, reject);
                    }, 5000);
                });
            });
        }
        return postCentro(false).then(function (pack) {
            var estado = pack.json && pack.json.estado;
            if (pack.ok && gastoListo(estado)) return pack;
            if (estado === 'fallo') return pack;
            return esperarArchivo(60, 0, 0);
        }).catch(function (err) {
            if (err && err.message === 'pausa') throw err;
            return esperarArchivo(60, 0, 0);
        });
    }

    function reintentarCentroSap(c, li, span, btn) {
        if (CCAsig._sapCorriendo) return;
        var year = anioGastoPagina();
        if (!year) return;
        CCAsig._sapCorriendo = true;
        CCAsig._sapParar = false;
        marcarControlesSap(true);
        habilitarReintentosSap(false);
        btn.disabled = true;
        span.textContent = c.cc + ' — actualizando…';
        pintarProgresoSap(0, 1, 'Actualizando ' + c.cc + '…');
        correrCentroSap(c, year, 'r' + Date.now().toString(36), function (texto) {
            pintarProgresoSap(0, 1, texto);
            span.textContent = c.cc + ' — ' + texto;
        }).then(function (pack) {
            CCAsig._sapCorriendo = false;
            CCAsig._sapParar = false;
            marcarControlesSap(false);
            cerrarIntentoSap(c, li, span, pack);
            if (avisarSiNoQuedanFallos()) return;
            habilitarReintentosSap(true);
            if (pack && pack.ok && gastoListo(pack.json && pack.json.estado)) {
                pintarProgresoSap(1, 1, c.cc + ' quedó guardado.');
            }
        }).catch(function (err) {
            CCAsig._sapCorriendo = false;
            CCAsig._sapParar = false;
            marcarControlesSap(false);
            habilitarReintentosSap(true);
            span.textContent = c.cc + ' — ' + ((err && err.message === 'pausa') ? 'pausado' : 'Se cortó antes de guardar el archivo');
        });
    }

    function reintentarFallosSap() {
        if (CCAsig._sapCorriendo) return;
        var year = anioGastoPagina();
        var items = Array.prototype.slice.call(document.querySelectorAll('#ciclo-sap-fallos li'));
        if (!year || !items.length) return;
        CCAsig._sapCorriendo = true;
        CCAsig._sapParar = false;
        marcarControlesSap(true);
        habilitarReintentosSap(false);
        var i = 0;
        function paso() {
            var pausado = !!CCAsig._sapParar;
            if (pausado || i >= items.length) {
                CCAsig._sapCorriendo = false;
                CCAsig._sapParar = false;
                marcarControlesSap(false);
                if (avisarSiNoQuedanFallos()) return;
                habilitarReintentosSap(true);
                pintarProgresoSap(i, items.length, pausado ? 'Reintento pausado.' : 'Listo.');
                return;
            }
            var li = items[i];
            if (!li.parentNode) {
                i += 1;
                paso();
                return;
            }
            var c = {
                empresa: li.getAttribute('data-empresa') || '',
                cc: li.getAttribute('data-cc') || ''
            };
            var span = li.querySelector('span');
            if (span) span.textContent = c.cc + ' — actualizando…';
            pintarProgresoSap(i, items.length, 'Reintentando ' + c.cc + ' (' + (i + 1) + ' de ' + items.length + ')…');
            correrCentroSap(c, year, 'f' + Date.now().toString(36) + i, function (texto) {
                pintarProgresoSap(i, items.length, texto + ' (' + (i + 1) + ' de ' + items.length + ')');
                if (span) span.textContent = c.cc + ' — ' + texto;
            }).then(function (pack) {
                cerrarIntentoSap(c, li, span, pack);
                i += 1;
                paso();
            }).catch(function (err) {
                if (err && err.message === 'pausa') {
                    if (span) span.textContent = c.cc + ' — pausado';
                    CCAsig._sapCorriendo = false;
                    CCAsig._sapParar = false;
                    marcarControlesSap(false);
                    habilitarReintentosSap(true);
                    pintarProgresoSap(i, items.length, 'Reintento pausado.');
                    return;
                }
                if (span) span.textContent = c.cc + ' — Se cortó antes de guardar el archivo';
                i += 1;
                paso();
            });
        }
        paso();
    }

    function cicloCodigoPagina() {
        if (CCAsig.ciclo) return CCAsig.ciclo;
        if (window.CC && CC.state && CC.state.cicloCodigo) return CC.state.cicloCodigo;
        var el = document.getElementById('period-codigo');
        return el ? String(el.textContent || '').trim() : '';
    }

    function anioGastoPagina() {
        var year = (window.CC && CC.state && (CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia))) || '';
        if (!year) {
            var ref = document.getElementById('period-anio-ref');
            year = ref ? parseInt(ref.textContent, 10) : 0;
        }
        return year;
    }

    function filasSapEnPantalla() {
        var rows = (CCAsig._rows || []).slice();
        if (rows.length) return rows;
        var out = [];
        document.querySelectorAll('#asig-tbody tr[data-cc]').forEach(function (tr) {
            out.push({
                empresa: tr.getAttribute('data-empresa') || '',
                centro_codigo: tr.getAttribute('data-cc') || ''
            });
        });
        return out;
    }

    function empezarActualizacionSap() {
        if (CCAsig._sapCorriendo) return;
        var g = grupoSapElegido();
        var year = anioGastoPagina();
        if (!g || !g.centros.length) {
            if (window.Swal) Swal.fire({ icon: 'warning', title: 'Elige la empresa', text: 'Selecciona la empresa que quieres actualizar.' });
            return;
        }
        if (!year) {
            if (window.Swal) Swal.fire({ icon: 'warning', title: 'Falta el año', text: 'Este ciclo no tiene año de gasto.' });
            return;
        }
        CCAsig._sapCorriendo = true;
        CCAsig._sapParar = false;
        CCAsig._sapStats = { registrados: 0, actualizados: 0, sinCambio: 0, fallidos: 0 };
        pintarContadoresSap();
        var fallos = document.getElementById('ciclo-sap-fallos');
        if (fallos) fallos.innerHTML = '';
        habilitarReintentosSap(false);
        marcarControlesSap(true);
        var corrida = 'c' + Date.now().toString(36);
        var i = 0;
        var total = g.centros.length;
        function aplicarResultado(c, pack) {
            var estado = pack.json && pack.json.estado;
            if (pack.ok && estado === 'registrado') statsSap().registrados += 1;
            else if (pack.ok && estado === 'actualizado') statsSap().actualizados += 1;
            else if (pack.ok && estado === 'sin_cambio') statsSap().sinCambio += 1;
            else anotarFalloSap(c, (pack.json && (pack.json.mensaje || pack.json.message)) || 'No se pudo guardar');
            pintarContadoresSap();
        }
        function terminar(pausado) {
            CCAsig._sapCorriendo = false;
            CCAsig._sapParar = false;
            marcarControlesSap(false);
            var s = statsSap();
            pintarProgresoSap(pausado ? i : total, total, pausado ? 'Actualización pausada.' : 'Listo.');
            if (s.fallidos) habilitarReintentosSap(true);
            var aviso = window.Swal
                ? Swal.fire({
                    icon: s.fallidos ? 'warning' : 'success',
                    title: pausado ? 'Actualización pausada' : 'Gasto actualizado',
                    html: '<p style="margin:0">' + s.registrados + ' registrados<br>' + s.actualizados + ' actualizados<br>' + (s.sinCambio || 0) + ' sin cambio<br>' + s.fallidos + ' fallaron'
                        + (s.fallidos ? '<br><br>Cada centro de la lista tiene un botón para intentarlo de nuevo.' : '') + '</p>'
                })
                : Promise.resolve();
            if (!s.fallidos) {
                aviso.then(function () {
                    window.location.reload();
                });
            }
        }
        function paso() {
            if (CCAsig._sapParar) {
                terminar(true);
                return;
            }
            if (i >= total) {
                terminar(false);
                return;
            }
            var c = g.centros[i];
            pintarProgresoSap(i, total, 'Actualizando ' + c.cc + ' (' + (i + 1) + ' de ' + total + ')…');
            correrCentroSap(c, year, corrida, function (texto) {
                pintarProgresoSap(i, total, texto + ' (' + (i + 1) + ' de ' + total + ')');
            }).then(function (pack) {
                aplicarResultado(c, pack || { ok: false, json: {} });
                i += 1;
                paso();
            }).catch(function (err) {
                if (err && err.message === 'pausa') {
                    terminar(true);
                    return;
                }
                anotarFalloSap(c, 'Se cortó antes de guardar el archivo');
                i += 1;
                paso();
            });
        }
        paso();
    }

    function loadEmpresasKpi() {
        getJSON('/CentrosCostos/api/empresas?ciclo=' + encodeURIComponent(CCAsig.ciclo || '')).then(function (json) {
            CCAsig._empresas = json.empresas && json.empresas.length ? json.empresas : EMPRESAS_FALLBACK;
            renderAsigKpis();
        }).catch(function () {
            CCAsig._empresas = EMPRESAS_FALLBACK;
            renderAsigKpis();
        });
    }

    function reloadTabla() {
        var ciclo = CCAsig.ciclo;
        getJSON('/AdminCentros/' + encodeURIComponent(ciclo) + '/asignaciones').then(function (json) {
            CCAsig._rows = json.asignaciones || [];
            fillFiltrosTabla();
            renderTablaFiltrada();
            renderAsigKpis();
        }).catch(function () {
            CCAsig._rows = [];
            fillFiltrosTabla();
            renderTablaFiltrada();
            renderAsigKpis();
        });
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

    function filtroVal(id) {
        var el = document.getElementById(id);
        return el ? String(el.value || '').trim() : '';
    }

    function bindFiltrosTabla() {
        if (CCAsig._filtrosBound) return;
        CCAsig._filtrosBound = true;
        var qEl = document.getElementById('asig-q');
        if (qEl) qEl.addEventListener('input', renderTablaFiltrada);
        ['asig-q-empresa', 'asig-q-usuario', 'asig-q-centro', 'asig-q-permiso'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('change', function () {
                fillFiltrosTabla();
                renderTablaFiltrada();
            });
        });
    }

    function userOptionKey(p) {
        if (p && p.user_id != null && String(p.user_id) !== '') return 'id:' + String(p.user_id);
        var nom = String((p && p.usuario) || '').trim().toLowerCase();
        if (nom) return 'nom:' + nom;
        var mail = String((p && p.email) || '').trim().toLowerCase();
        return mail ? 'mail:' + mail : '';
    }

    function userOptionLabel(p) {
        return String((p && (p.usuario || p.email)) || '—');
    }

    function centroFiltroKey(r) {
        return String(r.empresa || '').toLowerCase() + '|' + String(r.centro_codigo || '');
    }

    function filaTieneUsuario(r, key) {
        if (!key) return true;
        return userOptionKey(r) === key;
    }

    function filaTienePermiso(r, clave) {
        if (!clave) return true;
        return (r.permisos || []).some(function (p) { return String(p) === String(clave); });
    }

    function opcionSigue(items, value) {
        if (!value) return '';
        for (var i = 0; i < items.length; i++) {
            if (items[i].value === value) return value;
        }
        return '';
    }

    function fillSelectFiltro(el, items, extra, value) {
        if (!el) return;
        var html = extra || '';
        items.forEach(function (it) {
            html += '<option value="' + escapeHtml(it.value) + '">' + escapeHtml(it.label) + '</option>';
        });
        el.innerHTML = html;
        el.value = value || '';
    }

    function fillFiltrosTabla() {
        var empEl = document.getElementById('asig-q-empresa');
        var userEl = document.getElementById('asig-q-usuario');
        var ccEl = document.getElementById('asig-q-centro');
        var permEl = document.getElementById('asig-q-permiso');
        if (!empEl && !userEl && !ccEl && !permEl) return;

        var all = filasCaptura();
        var emp = empEl ? empEl.value : '';
        var user = userEl ? userEl.value : '';
        var cc = ccEl ? ccEl.value : '';

        var emps = [];
        var seenEmp = {};
        all.forEach(function (r) {
            var raw = String(r.empresa || '').trim();
            var key = raw.toLowerCase();
            if (!key || seenEmp[key]) return;
            seenEmp[key] = true;
            emps.push({ value: key, label: raw.toUpperCase() });
        });
        emps.sort(function (a, b) { return a.label.localeCompare(b.label, 'es'); });
        emp = opcionSigue(emps, emp);

        var scoped = all.filter(function (r) {
            return !emp || String(r.empresa || '').toLowerCase() === emp;
        });
        if (cc && !scoped.some(function (r) { return centroFiltroKey(r) === cc; })) cc = '';

        var users = [];
        var seenUser = {};
        scoped.filter(function (r) { return !cc || centroFiltroKey(r) === cc; }).forEach(function (r) {
            var key = userOptionKey(r);
            if (!key || seenUser[key]) return;
            seenUser[key] = true;
            users.push({ value: key, label: userOptionLabel(r) });
        });
        users.sort(function (a, b) { return a.label.localeCompare(b.label, 'es'); });
        user = opcionSigue(users, user);

        var centros = [];
        var seenCc = {};
        scoped.filter(function (r) { return filaTieneUsuario(r, user); }).forEach(function (r) {
            var key = centroFiltroKey(r);
            if (!r.centro_codigo || seenCc[key]) return;
            seenCc[key] = true;
            var label = etiquetaCentro(r.centro_codigo, r.centro_nombre);
            if (!emp && r.empresa) label = String(r.empresa).toUpperCase() + ' · ' + label;
            centros.push({ value: key, label: label });
        });
        centros.sort(function (a, b) { return a.label.localeCompare(b.label, 'es'); });
        cc = opcionSigue(centros, cc);

        var perm = permEl ? permEl.value : '';
        var permisos = catalogoPermisos().map(function (p) {
            return { value: String(p.clave), label: p.nombre || p.clave };
        }).filter(function (p) { return p.value; });
        perm = opcionSigue(permisos, perm);

        fillSelectFiltro(empEl, emps, '<option value="">Todas las empresas</option>', emp);
        fillSelectFiltro(userEl, users, '<option value="">Todos los usuarios</option>', user);
        fillSelectFiltro(ccEl, centros, '<option value="">Todos los centros</option>', cc);
        fillSelectFiltro(permEl, permisos, '<option value="">Todos los permisos</option>', perm);
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

    function esCaptura(r) {
        return (r.permisos || []).indexOf('capturar') !== -1;
    }

    function etiquetaTipoAsignacion(r) {
        return esCaptura(r) ? 'Captura' : 'Revisión';
    }

    function textoCuentasAsignadas(r) {
        var n = (r.cuentas || []).length;
        if (n) return String(n);
        return 'Todas';
    }

    function esAccesoDeUsuario(r) {
        if (!r || esCaptura(r)) return false;
        var pid = Number(r.parent_id || 0);
        if (!pid) return false;
        if (!(r.es_principal === false || r.es_principal === 0 || r.es_principal === '0')) return false;
        if (isEmpresaCompleta(r.centro_codigo)) return false;
        if (!(r.cuentas || []).length) return false;
        return true;
    }

    function nombreCentroDe(r) {
        var code = String((r && r.centro_codigo) || '').trim();
        var nom = String((r && r.centro_nombre) || '').trim();
        if (nom && nom !== code) return nom;
        var emp = String((r && r.empresa) || '').toLowerCase();
        var found = '';
        (CCAsig._rows || []).some(function (x) {
            if (String(x.empresa || '').toLowerCase() !== emp) return false;
            if (String(x.centro_codigo || '').trim() !== code) return false;
            var n = String(x.centro_nombre || '').trim();
            if (!n || n === code) return false;
            found = n;
            return true;
        });
        return found || nom;
    }

    function esRevisionDeTodoElCentro(r) {
        if (!r || esCaptura(r) || esAccesoDeUsuario(r)) return false;
        if (isEmpresaCompleta(r.centro_codigo)) return false;
        var perms = r.permisos || [];
        if (perms.indexOf('revisar') === -1 && perms.indexOf('editar') === -1) return false;
        return !(r.cuentas || []).length;
    }

    function personaDe(x) {
        var nom = String((x && x.usuario) || '').trim();
        var mail = String((x && x.email) || '').trim();
        if (!nom || !mail) {
            var u = catalogoUsuarios().filter(function (c) {
                return String(c.id) === String(x && x.user_id);
            })[0];
            if (u) {
                if (!nom) nom = String(u.nombre || '').trim();
                if (!mail) mail = String(u.email || '').trim();
            }
        }
        return { nombre: nom, email: mail };
    }

    function revisoresTodoElCentro(row) {
        if (!row || !esCaptura(row)) return [];
        var emp = String(row.empresa || '').toLowerCase();
        var code = String(row.centro_codigo || '').trim();
        var uid = Number(row.user_id || 0);
        return (CCAsig._rows || []).filter(function (x) {
            if (!esRevisionDeTodoElCentro(x)) return false;
            if (Number(x.user_id) === uid) return false;
            if (String(x.empresa || '').toLowerCase() !== emp) return false;
            return String(x.centro_codigo || '').trim() === code;
        });
    }

    function leyendaAccesos(row) {
        var revisores = [];
        var editores = [];
        var vistos = {};
        function sumar(x) {
            var id = String(x.user_id || x.usuario || '');
            if (!id || vistos[id] || Number(x.user_id) === Number(row.user_id)) return;
            vistos[id] = true;
            var perms = x.permisos || [];
            var nom = personaDe(x).nombre;
            if (perms.indexOf('revisar') !== -1) revisores.push(nom);
            if (perms.indexOf('editar') !== -1) editores.push(nom);
        }
        extrasDe(row).forEach(function (x) {
            if (esAccesoDeUsuario(x)) sumar(x);
        });
        if (esCaptura(row)) {
            var emp = String(row.empresa || '').toLowerCase();
            var code = String(row.centro_codigo || '').trim();
            (CCAsig._rows || []).forEach(function (x) {
                if (!esRevisionDeTodoElCentro(x)) return;
                if (String(x.empresa || '').toLowerCase() !== emp) return;
                if (String(x.centro_codigo || '').trim() !== code) return;
                sumar(x);
            });
        }
        function linea(n, uno, varios, nombres) {
            if (!n) return '';
            var txt = n + ' ' + (n === 1 ? uno : varios);
            return '<span title="' + escapeHtml(nombres.filter(Boolean).join(', ')) + '">' + escapeHtml(txt) + '</span>';
        }
        var html = linea(revisores.length, 'revisor', 'revisores', revisores) +
            linea(editores.length, 'editor', 'editores', editores);
        if (!html) return '';
        return '<div class="cc-asig-leyenda">' + html + '</div>';
    }

    function asignacionDueno(row) {
        if (!row) return row;
        if (isPrincipal(row) || esCaptura(row)) return row;
        var pid = Number(row.parent_id || 0);
        if (pid) {
            var padre = (CCAsig._rows || []).filter(function (r) { return Number(r.id) === pid; })[0];
            if (padre) return padre;
        }
        return principalDe(row);
    }

    function extrasDe(row) {
        var p = asignacionDueno(row);
        var pid = Number(p && p.id);
        if (!pid) return [];
        return (CCAsig._rows || []).filter(function (r) {
            if (Number(r.id) === pid) return false;
            if (isPrincipal(r) || esCaptura(r)) return false;
            return Number(r.parent_id || 0) === pid;
        });
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
        if (!done) return 'De ' + total + ' usuarios, ninguno tiene centros de costos todavía';
        if (done === 1) return 'De ' + total + ' usuarios, 1 ya tiene centros de costos';
        if (done >= total) return 'Los ' + total + ' usuarios ya tienen centros de costos';
        return 'De ' + total + ' usuarios, ' + done + ' ya tienen centros de costos';
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
            if (r.user_id) userSet[String(r.user_id)] = true;
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
            ? '1 centro de costo ya tiene asignación'
            : ccDone + ' centros de costo ya tienen asignación');
        setKpi('kpi-asig-user', ratioHtml(userDone, userTot));
        setKpiText('kpi-asig-user-hint', hintUsuarios(userTot, userDone));
    }

    function filasCaptura() {
        return (CCAsig._rows || []).filter(function (r) {
            return !esAccesoDeUsuario(r);
        }).slice().sort(function (a, b) {
            var ua = String(a.usuario || '').toLowerCase();
            var ub = String(b.usuario || '').toLowerCase();
            if (ua !== ub) return ua < ub ? -1 : 1;
            var ea = String(a.empresa || '').toLowerCase();
            var eb = String(b.empresa || '').toLowerCase();
            if (ea !== eb) return ea < eb ? -1 : 1;
            return String(a.centro_codigo || '').localeCompare(String(b.centro_codigo || ''));
        });
    }

    function textoFila(r) {
        var perms = (r.permisos || []).map(permNombre).join(' ');
        var personas = (r.usuario || '') + ' ' + (r.email || '') + ' ' + perms;
        var cuentas = (r.cuentas || []).map(function (c) {
            if (!c) return '';
            if (typeof c === 'string') return c;
            return (c.codigo || '') + ' ' + ctaPretty(c.codigo) + ' ' + (c.nombre || '');
        }).join(' ');
        return [
            r.empresa,
            r.centro_codigo,
            r.centro_nombre,
            etiquetaCentro(r.centro_codigo, r.centro_nombre),
            personas,
            cuentas
        ].join(' ');
    }

    function renderTablaFiltrada() {
        var all = filasCaptura();
        var q = filtroVal('asig-q');
        var qEmp = filtroVal('asig-q-empresa');
        var qUser = filtroVal('asig-q-usuario');
        var qCc = filtroVal('asig-q-centro');
        var qPerm = filtroVal('asig-q-permiso');
        var rows = all.filter(function (r) {
            if (q && !matchQuery(textoFila(r), q)) return false;
            if (qEmp && String(r.empresa || '').toLowerCase() !== qEmp.toLowerCase()) return false;
            if (qUser && !filaTieneUsuario(r, qUser)) return false;
            if (qCc && centroFiltroKey(r) !== qCc) return false;
            if (qPerm && !filaTienePermiso(r, qPerm)) return false;
            return true;
        });
        renderTabla(rows, CCAsig.ciclo, all.length, q || qEmp || qUser || qCc || qPerm);
    }

    function renderTabla(rows, ciclo, total, filtrando) {
        var tb = document.getElementById('asig-tbody');
        var meta = document.getElementById('asig-tabla-meta');
        if (meta) {
            if (!total) meta.textContent = '';
            else if (filtrando) meta.textContent = rows.length + ' de ' + total;
            else meta.textContent = total + (total === 1 ? ' asignación' : ' asignaciones');
        }
        if (!tb) return;
        if (!rows.length) {
            var empty = filtrando
                ? 'No hay asignaciones que coincidan con el filtro.'
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
                    '<span class="cc-asig-group-n">' + n + (n === 1 ? ' centro' : ' centros') + '</span>' +
                    '</div></td></tr>');
            }
            var revision = !esCaptura(r);
            html.push('<tr data-id="' + r.id + '" data-empresa="' + escapeHtml(r.empresa || '') + '" data-cc="' + escapeHtml(r.centro_codigo || '') + '">' +
                '<td><div class="fw-semibold">' + escapeHtml(r.usuario) + '</div>' +
                '<div class="text-muted" style="font-size:.75rem">' + etiquetaTipoAsignacion(r) + '</div></td>' +
                '<td>' + String(r.empresa || '').toUpperCase() + '</td>' +
                '<td>' + escapeHtml(etiquetaCentro(r.centro_codigo, nombreCentroDe(r))) + '</td>' +
                '<td>' + textoCuentasAsignadas(r) + '</td>' +
                '<td><div class="cc-asig-perm">' + permBadges(r.permisos) + leyendaAccesos(r) + '</div></td>' +
                '<td><div class="cc-row-actions">' +
                    '<button type="button" class="cc-icon-btn" data-act="ver" title="Ver"><i class="fa-solid fa-eye"></i></button>' +
                    '<button type="button" class="cc-icon-btn" data-act="editar" title="' + (revision ? 'No aplica en revisión' : 'Editar') + '"' + (revision ? ' disabled' : '') + '><i class="fa-solid fa-pen"></i></button>' +
                    '<button type="button" class="cc-icon-btn" data-act="permisos" title="' + (revision ? 'No aplica en revisión' : 'Permisos') + '"' + (revision ? ' disabled' : '') + '><i class="fa-solid fa-user-lock"></i></button>' +
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
                if (act === 'editar' && esCaptura(row)) openEditar(row);
                if (act === 'permisos' && esCaptura(row)) openPermisos(row);
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
        return '/AdminCentros/' + encodeURIComponent(CCAsig.ciclo) + '/asignaciones/' + id;
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
        var p = asignacionDueno(row);
        var extras = extrasDe(row).concat(revisoresTodoElCentro(row));
        var title = document.getElementById('asig-ver-title');
        if (title) title.textContent = 'Ver · ' + etiquetaCentro(row.centro_codigo, row.centro_nombre);
        var cuentas = (p.cuentas || row.cuentas || []).map(function (c) {
            return '<li><span class="cc-cta-code">' + escapeHtml(ctaPretty(c.codigo)) + '</span> ' + escapeHtml(c.nombre || '') + '</li>';
        }).join('') || '<li class="text-muted">Sin cuentas</li>';
        var extrasHtml = extras.length
            ? extras.map(function (x) {
                var persona = personaDe(x);
                var nota = esRevisionDeTodoElCentro(x) ? ' · Todo el centro' : '';
                return '<div class="cc-user" style="margin-bottom:.35rem">' +
                    '<span class="cc-avatar">' + initials(persona.nombre) + '</span>' +
                    '<span>' + escapeHtml(persona.nombre || '—') + nota + ' · ' + permBadges(x.permisos) + '</span></div>';
            }).join('')
            : '<span class="text-muted">Nadie más tiene acceso.</span>';
        document.getElementById('asig-ver-body').innerHTML =
            '<div class="cc-context">' +
            '<div class="cc-context-item"><small>Usuario a cargo</small><strong>' + escapeHtml(p.usuario || '—') + '</strong></div>' +
            '<div class="cc-context-item"><small>Empresa</small><strong>' + String(row.empresa || '').toUpperCase() + '</strong></div>' +
            '<div class="cc-context-item"><small>Centro</small><strong>' + escapeHtml(etiquetaCentro(row.centro_codigo, row.centro_nombre)) + '</strong></div>' +
            '<div class="cc-context-item"><small>Permisos del responsable</small><span>' + permBadges(p.permisos) + '</span></div>' +
            '</div>' +
            '<div class="cc-form-kicker">Cuentas</div>' +
            '<ul class="cc-ver-cuentas">' + cuentas + '</ul>' +
            '<div class="cc-form-kicker">Otros usuarios</div>' + extrasHtml;
        var revision = !esCaptura(row);
        var verEdit = document.getElementById('asig-ver-editar');
        var verPerm = document.getElementById('asig-ver-permisos');
        if (verEdit) verEdit.disabled = revision;
        if (verPerm) verPerm.disabled = revision;
        showModal('modalAsigVer');
    }

    function openEditar(row) {
        hideThen('modalAsigVer', function () { openEditarNow(row); });
    }

    function openEditarNow(row) {
        CCAsig._activo = row;
        var p = asignacionDueno(row);
        var title = document.getElementById('asig-edit-title');
        var sub = document.getElementById('asig-edit-sub');
        if (title) title.textContent = 'Editar cuentas · ' + etiquetaCentro(row.centro_codigo, row.centro_nombre);
        if (sub) sub.textContent = String(row.empresa || '').toUpperCase() + ' · ' + (p.usuario || row.usuario) + ' a cargo. Las cuentas se replican a quienes tengan acceso.';
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
        if (list) list.innerHTML = '<div class="cc-empty">Cargando cuentas SAP…</div>';
        CCAsig._editMask = '';
        CCAsig._editGrupo = '';
        var maskSel = document.getElementById('asig-edit-mask');
        if (maskSel) maskSel.value = '';
        var grupoSel = document.getElementById('asig-edit-grupo');
        if (grupoSel) grupoSel.value = '';
        showModal('modalAsigEditar');
        loadGruposEditar(row.empresa);
        loadCuentasEditar(row.empresa, qEl ? qEl.value : '');
    }

    function loadCuentasEditar(empresa, q) {
        var key = String(empresa || '').toLowerCase();
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
        getJSON('/CentrosCostos/api/cuentas?todas=1&empresa=' + encodeURIComponent(key)).then(function (json) {
            CCAsig._cuentasCache[key] = json.cuentas || [];
            apply(CCAsig._cuentasCache[key], json.agrupaciones || []);
        }).catch(function () {
            apply((asignacionDueno(CCAsig._activo).cuentas) || [], []);
        });
    }

    function loadGruposEditar(empresa) {
        var key = String(empresa || '').toLowerCase();
        var apply = function (rows) {
            CCAsig._editGrupos = rows || [];
            fillGrupoUi('asig-edit-grupo', CCAsig._editGrupos, CCAsig._editGrupo || '', setEditGrupo);
        };
        CCAsig._gruposCache = CCAsig._gruposCache || {};
        if (CCAsig._gruposCache[key]) {
            apply(CCAsig._gruposCache[key]);
            return;
        }
        getJSON('/CentrosCostos/api/grupos?empresa=' + encodeURIComponent(key)).then(function (json) {
            CCAsig._gruposCache[key] = json.grupos || [];
            apply(CCAsig._gruposCache[key]);
        }).catch(function () {
            apply([]);
        });
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
        getJSON('/CentrosCostos/api/cuentas?todas=1&empresa=' + encodeURIComponent(empresa) +
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
        var base = grupoMap ? (CCAsig._editCuentasAll || CCAsig._editCuentas || []) : (CCAsig._editCuentas || []);
        var rows = filterRowsByGrupo(base, grupoMap).filter(function (c) {
            if (grupoMap && mask && String(c.grupo_id || '') && String(c.grupo_id) !== mask) return false;
            return ((c.codigo || '') + ' ' + ctaPretty(c.codigo) + ' ' + (c.nombre || '') + ' ' + (c.grupo || '') + ' ' + (c.grupo_id || '')).toLowerCase().indexOf(q) !== -1;
        });
        if (!rows.length) {
            box.innerHTML = '<div class="cc-empty">' + (CCAsig._editGrupo ? 'Sin cuentas en este grupo' : (mask ? 'Sin cuentas para este GroupMask' : 'Sin cuentas para este catálogo')) + '</div>';
            return;
        }
        var sel = CCAsig._editSelected || {};
        var groups = groupByMask(rows);
        box.innerHTML = Object.keys(groups).map(function (key) {
            var pack = groups[key];
            var nOn = 0;
            pack.rows.forEach(function (c) { if (sel[String(c.codigo)]) nOn += 1; });
            var allOn = pack.rows.length > 0 && nOn === pack.rows.length;
            return '<div class="cc-cta-group">' +
                '<label class="cc-cta-group-h cc-cta-mask-h"><input type="checkbox" data-edit-mask="' + escapeHtml(pack.id) + '"' + (allOn ? ' checked' : '') + '>' +
                'GroupMask ' + escapeHtml(pack.label) + ' · ' + pack.rows.length + '</label>' +
                pack.rows.map(function (c) {
                    var on = !!sel[String(c.codigo)];
                    return '<label class="cc-cta-item"><input type="checkbox" data-edit-cta="1" value="' + escapeHtml(c.codigo) + '"' +
                        ' data-nombre="' + escapeHtml(c.nombre || '') + '" data-grupo="' + escapeHtml(c.grupo || '') + '"' +
                        ' data-mask="' + escapeHtml(c.grupo_id || '') + '"' + (on ? ' checked' : '') + '>' +
                        '<span class="cc-cta-code">' + escapeHtml(ctaPretty(c.codigo)) + '</span>' +
                        '<span class="cc-cta-name">' + escapeHtml(c.nombre || '') + '</span></label>';
                }).join('') + '</div>';
        }).join('');
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
        var p = asignacionDueno(row);
        CCAsig._permPrincipal = p;
        CCAsig._accesos = extrasDe(row).filter(function (x) {
            return esAccesoDeUsuario(x);
        }).map(function (x) {
            var persona = personaDe(x);
            return {
                id: x.id,
                user_id: x.user_id,
                usuario: persona.nombre,
                email: persona.email,
                permisos: (x.permisos || []).slice()
            };
        });
        CCAsig._revisoresCentro = revisoresTodoElCentro(p).map(function (x) {
            var persona = personaDe(x);
            return {
                id: x.id,
                user_id: x.user_id,
                usuario: persona.nombre,
                email: persona.email,
                permisos: (x.permisos || []).slice(),
                todoCentro: true
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
        var permGrid = document.getElementById('asig-perm-principal');
        if (permGrid) permGrid.innerHTML = permChecksHtml('perm-principal', p.permisos);
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
        (CCAsig._revisoresCentro || []).forEach(function (a) { taken[String(a.user_id)] = true; });
        var rows = catalogoUsuarios().filter(function (u) {
            if (taken[String(u.id)]) return false;
            if (!q) return true;
            return (u.nombre + ' ' + (u.email || '')).toLowerCase().indexOf(q) !== -1;
        }).slice(0, 40);
        if (!q) {
            list.hidden = true;
            list.innerHTML = '';
            return;
        }
        list.hidden = false;
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
        if (list) {
            list.innerHTML = '';
            list.hidden = true;
        }
    }

    function renderAccesos() {
        var tb = document.getElementById('asig-acc-tbody');
        if (!tb) return;
        var propios = CCAsig._accesos || [];
        var delCentroRev = CCAsig._revisoresCentro || [];
        var rows = propios.concat(delCentroRev);
        if (!rows.length) {
            tb.innerHTML = '<tr><td colspan="3"><div class="cc-empty">Nadie más tiene acceso a este usuario.</div></td></tr>';
            return;
        }
        tb.innerHTML = rows.map(function (a, i) {
            var quitar = a.todoCentro
                ? ''
                : '<button type="button" class="cc-icon-btn is-danger" data-acc-del="' + i + '" title="Quitar acceso"><i class="fa-solid fa-xmark"></i></button>';
            var nota = a.todoCentro ? '<div class="text-muted" style="font-size:.75rem">Revisa todo el centro</div>' : '';
            return '<tr>' +
                '<td><div class="fw-semibold">' + escapeHtml(a.usuario || '—') + '</div>' +
                '<div class="text-muted" style="font-size:.75rem">' + escapeHtml(a.email || '') + '</div>' + nota + '</td>' +
                '<td>' + permBadges(a.permisos) + '</td>' +
                '<td>' + quitar + '</td>' +
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
        var text = esRevisionDeTodoElCentro(row)
            ? 'Se quita la revisión de este centro para todos los usuarios que lo tienen.'
            : (esP && extras.length
                ? 'También se quitará el acceso de ' + extras.length + (extras.length === 1 ? ' usuario' : ' usuarios') + ' a este usuario.'
                : 'Se quitará a ' + (row.usuario || 'este usuario') + ' de ' + (etiquetaCentro(row.centro_codigo, row.centro_nombre) || 'este centro') + '.');
        var go = function () {
            sendJSON(asigUrl(row.id), 'DELETE').then(function () {
                window.location.reload();
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
                sendJSON('/AdminCentros/' + encodeURIComponent(CCAsig.ciclo) + '/importar-usuarios', 'PUT', { user_ids: ids })
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
        return '/AdminCentros/' + encodeURIComponent(CCAsig.ciclo) + '/importar-usuarios';
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
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Sin cuentas', text: 'Selecciona al menos una cuenta.' });
                return;
            }
            var btn = document.getElementById('asig-edit-guardar');
            if (btn) btn.disabled = true;
            var target = asignacionDueno(CCAsig._activo);
            sendJSON(asigUrl(target.id), 'PUT', { cuentas: cuentas }).then(function () {
                hideModal('modalAsigEditar');
                toast('success', 'Cuentas actualizadas', 'El gasto nuevo se guarda en la copia local.');
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
            if (!permisos.length) permisos = (p.permisos && p.permisos.length) ? p.permisos.slice() : ['capturar'];
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
        cfg = cfg || {};
        if (!cfg.ciclo) {
            var app = document.getElementById('cc-app');
            cfg.ciclo = (app && app.getAttribute('data-ciclo'))
                || (window.CC && CC.state && CC.state.cicloCodigo)
                || '';
        }
        CCAsig.cfg = cfg;
        CCAsig.ciclo = cfg.ciclo || CCAsig.ciclo || '';
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
        var ctaLoaded = false;
        var lastFlashKey = '';
        var currentUserId = 0;
        var savesEnCurso = 0;
        var copiaBusy = false;

        function showCtaLoading() {
            ctaLoaded = false;
            var list = document.getElementById('asig-cta-list');
            if (list) list.innerHTML = '<div class="cc-empty">Cargando cuentas…</div>';
        }

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
                var toda = savedDeEmpresa(emp).some(function (a) { return isEmpresaCompleta(a.centro_codigo); });
                var badge = card.querySelector('[data-asig-n]');
                card.classList.toggle('has-asig', n > 0);
                if (badge) {
                    badge.textContent = toda ? 'Toda la empresa' : (n ? (n + (n === 1 ? ' centro' : ' centros')) : 'Sin asignar');
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
                var rowsEmp = savedDeEmpresa(code);
                var n = rowsEmp.length;
                var toda = rowsEmp.some(function (a) { return isEmpresaCompleta(a.centro_codigo); });
                var badgeCls = n ? 'cc-badge-ink' : 'cc-badge-solo_revision';
                var pickCls = (on || n) ? 'cc-btn-ink' : '';
                var pickLabel = on ? 'Seleccionada' : (n ? 'Ya asignada' : 'Seleccionar');
                return '<article class="cc-ciclo-card is-pickable' + (on ? ' is-on' : '') + (n ? ' has-asig' : '') + '" data-emp="' + escapeHtml(e.codigo) + '" data-nombre="' + escapeHtml(e.nombre) + '" role="button" tabindex="0">' +
                    '<div class="top"><div><div class="code">SAP</div><h3>' + escapeHtml(e.nombre) + '</h3></div>' +
                    '<span class="cc-badge ' + badgeCls + '" data-asig-n>' + (toda ? 'Toda la empresa' : (n ? (n + (n === 1 ? ' centro' : ' centros')) : 'Sin asignar')) + '</span></div>' +
                    '<div class="cc-ciclo-obs">Centros de costo y cuentas de ' + escapeHtml(e.nombre) + ' vía AutinApi.</div>' +
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
                tb.innerHTML = '<tr><td colspan="5"><div class="cc-empty">Aún no hay centros en la tabla. Elige empresa, centro y cuentas y pulsa Agregar a resultados.</div></td></tr>';
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
                var cliTitulo = isEmpresaCompleta(a.centro_codigo)
                    ? EMPRESA_NOMBRE
                    : (isSinCentro(a.centro_codigo) ? SIN_CENTRO_NOMBRE : a.centro_codigo);
                var cliSub = isEmpresaCompleta(a.centro_codigo)
                    ? 'Revisión de la empresa'
                    : (isSinCentro(a.centro_codigo) ? 'Cuentas sin afiliar a un centro' : (a.centro_nombre || ''));
                var revisionFila = (a.permisos || []).indexOf('revisar') !== -1 && (a.permisos || []).indexOf('capturar') === -1;
                var prodTxt = nCtas
                    ? (nCtas + (nCtas === 1 ? ' cuenta' : ' cuentas'))
                    : ((isEmpresaCompleta(a.centro_codigo) || revisionFila) ? 'Cuentas de los usuarios' : 'Sin cuentas');
                return '<tr class="' + cls.join(' ') + '" data-emp="' + escapeHtml(a.empresa) + '" data-key="' + escapeHtml(key) + '">' +
                    '<td><strong>' + escapeHtml(empresaNombre(a.empresa)) + '</strong></td>' +
                    '<td><div class="fw-semibold">' + escapeHtml(cliTitulo) + '</div>' +
                    '<div class="text-muted" style="font-size:.75rem">' + escapeHtml(cliSub) + '</div></td>' +
                    '<td>' + escapeHtml(prodTxt) + '</td>' +
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

        function pintarAsignaciones() {
            renderResumen();
            renderEmpresaCards();
            markEmpresaCard(cfg.empresa);
            fillCentros(ccQ ? ccQ.value : '');
        }

        function quitarFila(id, emp, codigo) {
            var i = findSavedIndex(emp, codigo);
            if (i < 0 && id) {
                for (var j = 0; j < saved.length; j++) {
                    if (String(saved[j].id) === String(id)) { i = j; break; }
                }
            }
            if (i < 0) return;
            var row = saved[i];
            saved.splice(i, 1);
            pintarAsignaciones();
            if (isTempId(id) || isTempId(row.id)) return;
            var ciclo = cfg.ciclo || CCAsig.ciclo || '';
            fetch('/AdminCentros/' + encodeURIComponent(ciclo) + '/asignaciones/' + encodeURIComponent(row.id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) {
                if (r.ok) return null;
                return r.json().catch(function () { return {}; }).then(function (j) {
                    throw new Error((j && j.message) || 'No se pudo quitar la asignación.');
                });
            }).catch(function (err) {
                saved.splice(Math.min(i, saved.length), 0, row);
                pintarAsignaciones();
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'No se pudo quitar',
                        text: (err && err.message) || 'No se pudo eliminar la asignación.'
                    });
                }
            });
        }

        function loadSaved() {
            if (!userId()) {
                saved = [];
                return Promise.resolve([]);
            }
            var url = (cfg.listUrl || ('/AdminCentros/' + encodeURIComponent(cfg.ciclo) + '/asignaciones'))
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
            if (title) title.innerHTML = '<i class="fa-solid fa-sitemap"></i> ' + (modoRevision() ? '4. Centro de costos (opcional)' : '4. Centro de costos') + ' (' + nom + ')';
            if (detBox) detBox.hidden = false;
            markEmpresaCard(cfg.empresa);
            renderResumen();
            refrescarModo();
            if (same && centros.length) {
                fillCentros(ccQ ? ccQ.value : '');
                loadGrupos();
                loadCuentas();
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
                centroSel.innerHTML = '<option value="">Cargando centros…</option>';
                centroSel.disabled = false;
            }
            var ccList = document.getElementById('asig-cc-list');
            if (ccList) ccList.innerHTML = '<div class="cc-empty">Cargando centros…</div>';
            if (ccQ) { ccQ.disabled = false; ccQ.value = ''; }
            if (ctaQ) { ctaQ.disabled = true; ctaQ.value = ''; }
            showCtaLoading();
            fillMaskUi('asig-cta-mask', [], [], '', setCtaMask);
            fillGrupoUi('asig-cta-grupo', [], '', setCtaGrupo);
            loadCentros();
            loadGrupos();
            loadCuentas();
        }

        function unlockEmpresas() {
            if (empBox) empBox.classList.toggle('is-locked', !userId());
            refrescarModo();
        }

        function permisosMarcados() {
            var perms = [];
            document.querySelectorAll('#asig-permisos input[name="permiso"]:checked').forEach(function (i) {
                perms.push(i.value);
            });
            return perms;
        }

        function modoRevision() {
            var perms = permisosMarcados();
            return perms.indexOf('revisar') !== -1 && perms.indexOf('capturar') === -1;
        }

        function pasoWizard() {
            if (!userId()) return 1;
            if (!permisosMarcados().length) return 2;
            if (!cfg.empresa) return 3;
            if (modoRevision()) return 4;
            if (centroSel && String(centroSel.value || '').trim()) return 4;
            return 3;
        }

        function refrescarModo() {
            var rev = modoRevision();
            var permPanel = document.getElementById('asig-perm-panel');
            if (permPanel) permPanel.classList.toggle('is-locked', !userId());
            var step4 = document.getElementById('asig-step-4');
            if (step4) step4.innerHTML = '<span>4</span> ' + (rev ? 'Centro (opcional)' : 'Centro y cuentas');
            var hint = document.getElementById('asig-emp-hint');
            if (hint) {
                hint.textContent = !userId()
                    ? 'Elige un usuario para habilitar las empresas.'
                    : (rev
                        ? 'Elige la empresa. En revisión puedes asignar toda la empresa, un centro o las cuentas que marques.'
                        : 'Elige la empresa. Luego el centro y las cuentas. Puedes cambiar de empresa cuando quieras; lo guardado se acumula abajo.');
            }
            var banner = document.getElementById('asig-rev-banner');
            if (banner) banner.hidden = !rev;
            var apply = document.getElementById('asig-apply-hint');
            if (apply) {
                apply.textContent = rev
                    ? 'Revisión: asigna toda la empresa, un centro o las cuentas marcadas. Lo que no elijas son las cuentas de los usuarios.'
                    : 'Suma este centro a la tabla de abajo. El usuario y la empresa se quedan seleccionados.';
            }
            var ctaTitle = document.getElementById('asig-cta-title');
            if (ctaTitle) {
                ctaTitle.innerHTML = '<i class="fa-solid fa-list"></i> ' + (rev ? 'Cuentas (opcionales)' : 'Cuentas con acceso');
            }
            var ccTitle = document.getElementById('asig-cc-title');
            if (ccTitle && cfg.empresa) {
                var nom = String(cfg.empresa).toUpperCase();
                ccTitle.innerHTML = '<i class="fa-solid fa-sitemap"></i> 4. Centro de costos'
                    + (rev ? ' (opcional)' : '')
                    + ' (' + nom + ')';
            }
            if (rev && ctaQ && cfg.empresa) ctaQ.disabled = false;
            setStep(pasoWizard());
        }

        function loadEmpresas() {
            getJSON('/CentrosCostos/api/empresas?ciclo=' + encodeURIComponent(cfg.ciclo)).then(function (json) {
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

        function loadCentros() {
            var meta = document.getElementById('asig-cc-meta');
            if (meta) meta.textContent = 'Cargando centros SAP…';
            var key = cfg.empresa;
            var apply = function (rows, mensaje) {
                centros = rows || [];
                fillCentros(ccQ ? ccQ.value : '');
                if (meta) {
                    meta.textContent = centros.length
                        ? (centros.length + ' centros en ' + String(cfg.empresa).toUpperCase() + (savedDeEmpresa(cfg.empresa).length ? ' · ' + savedDeEmpresa(cfg.empresa).length + ' ya asignados' : ''))
                        : (mensaje || 'Sin centros en AutinApi');
                }
            };
            if (cacheCentros[key]) {
                apply(cacheCentros[key], null);
                return;
            }
            getJSON('/CentrosCostos/api/centros?empresa=' + encodeURIComponent(cfg.empresa) + '&per_page=200').then(function (json) {
                cacheCentros[key] = json.centros || [];
                apply(cacheCentros[key], json.mensaje);
            });
        }

        function fillCentros(q) {
            if (!centroSel) return;
            var list = document.getElementById('asig-cc-list');
            var keep = centroSel.value;
            q = (q || '').toLowerCase().trim();
            var none = { codigo: SIN_CENTRO_CODIGO, nombre: SIN_CENTRO_NOMBRE };
            var noneHay = (none.codigo + ' ' + none.nombre + ' sin centro de costos').toLowerCase();
            var showNone = !q || noneHay.indexOf(q) !== -1;
            var rows = centros.filter(function (c) {
                if (isSinCentro(c.codigo)) return false;
                return ((c.codigo || '') + ' ' + (c.nombre || '')).toLowerCase().indexOf(q) !== -1;
            });
            var opts = '';
            if (showNone) {
                opts += '<option value="' + escapeHtml(none.codigo) + '" data-nombre="' + escapeHtml(none.nombre) + '">' +
                    escapeHtml(none.nombre) + '</option>';
            }
            opts += rows.map(function (c) {
                return '<option value="' + escapeHtml(c.codigo) + '" data-nombre="' + escapeHtml(c.nombre) + '">' +
                    escapeHtml(c.codigo) + '</option>';
            }).join('');
            centroSel.innerHTML = '<option value="">Elige un centro…</option>' + opts;
            if (keep && ((showNone && String(keep) === String(none.codigo)) || rows.some(function (c) { return String(c.codigo) === String(keep); }))) {
                centroSel.value = keep;
            }
            if (!list) return;
            if (!showNone && !rows.length) {
                list.innerHTML = '<div class="cc-empty">' + (centros.length ? 'Sin coincidencias' : 'Elige una empresa para ver centros') + '</div>';
                return;
            }
            var current = centroSel.value;
            var html = '';
            if (showNone) {
                var noneDone = centroYaAsignado(cfg.empresa, none.codigo);
                var noneOn = current !== '' && String(current) === String(none.codigo);
                html += '<button type="button" class="cc-cc-item is-none' + (noneDone ? ' is-done' : '') + (noneOn ? ' is-on' : '') + '" role="option" data-codigo="' + escapeHtml(none.codigo) + '" data-nombre="' + escapeHtml(none.nombre) + '" aria-selected="' + (noneOn ? 'true' : 'false') + '">' +
                    '<span><span class="cc-cc-code">SIN CC</span>' +
                    '<span class="cc-cc-name">' + escapeHtml(none.nombre) + '</span></span>' +
                    (noneDone ? '<span class="cc-badge cc-badge-done">Asignado</span>' : '') +
                    '</button>';
            }
            html += rows.map(function (c) {
                var done = centroYaAsignado(cfg.empresa, c.codigo);
                var on = current !== '' && String(c.codigo) === String(current);
                var cls = 'cc-cc-item' + (done ? ' is-done' : '') + (on ? ' is-on' : '');
                return '<button type="button" class="' + cls + '" role="option" data-codigo="' + escapeHtml(c.codigo) + '" data-nombre="' + escapeHtml(c.nombre) + '" aria-selected="' + (on ? 'true' : 'false') + '">' +
                    '<span><span class="cc-cc-code">' + escapeHtml(c.codigo) + '</span>' +
                    '<span class="cc-cc-name">' + escapeHtml(c.nombre) + '</span></span>' +
                    (done ? '<span class="cc-badge cc-badge-done">Asignado</span>' : '') +
                    '</button>';
            }).join('');
            list.innerHTML = html;
            list.querySelectorAll('.cc-cc-item').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    pickCentro(btn.getAttribute('data-codigo'), btn.getAttribute('data-nombre'));
                });
            });
        }

        function pickCentro(codigo, nombre) {
            if (!centroSel) return;
            if (modoRevision() && codigo && String(centroSel.value || '') === String(codigo)) {
                centroSel.value = '';
                var listOff = document.getElementById('asig-cc-list');
                if (listOff) {
                    listOff.querySelectorAll('.cc-cc-item').forEach(function (btn) {
                        btn.classList.remove('is-on');
                        btn.setAttribute('aria-selected', 'false');
                    });
                }
                refrescarModo();
                return;
            }
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
            var key = String(cfg.empresa || '').toLowerCase();
            var apply = function (rows) {
                grupos = rows || [];
                fillGrupoUi('asig-cta-grupo', grupos, ctaGrupo, setCtaGrupo);
            };
            if (!key) {
                apply([]);
                return;
            }
            if (cacheGrupos[key]) {
                apply(cacheGrupos[key]);
                return;
            }
            getJSON('/CentrosCostos/api/grupos?empresa=' + encodeURIComponent(key)).then(function (json) {
                cacheGrupos[key] = json.grupos || [];
                apply(cacheGrupos[key]);
            }).catch(function () {
                apply([]);
            });
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
            ctaLoaded = false;
            if (box) box.innerHTML = '<div class="cc-empty">Cargando cuentas…</div>';
            getJSON('/CentrosCostos/api/cuentas?todas=1&empresa=' + encodeURIComponent(cfg.empresa) +
                '&group_mask=' + encodeURIComponent(ctaMask)).then(function (json) {
                if (String(ctaMask) !== String(mask || '')) return;
                cacheCuentas[key] = json.cuentas || [];
                if (json.agrupaciones && json.agrupaciones.length) {
                    agrupaciones = json.agrupaciones;
                }
                cuentas = cacheCuentas[key];
                ctaLoaded = true;
                fillMaskUi('asig-cta-mask', agrupaciones, cuentas, ctaMask, setCtaMask);
                renderCuentas(ctaQ ? ctaQ.value : '');
            }).catch(function () {
                ctaLoaded = true;
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
                        agrupacion: i.getAttribute('data-grupo') || ''
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
            if (box) box.innerHTML = '<div class="cc-empty">Cargando cuentas…</div>';
            ctaLoaded = false;
            var key = cfg.empresa;
            var apply = function (rows, groups) {
                cuentas = rows || [];
                ctaLoaded = true;
                if (groups && groups.length) agrupaciones = groups;
                fillMaskUi('asig-cta-mask', agrupaciones, cuentas, ctaMask, setCtaMask);
                fillGrupoUi('asig-cta-grupo', grupos, ctaGrupo, setCtaGrupo);
                if (ctaGrupo) {
                    var mapGrupo = grupoCuentaMap(grupos, ctaGrupo);
                    if (mapGrupo) mergeGrupoCuentas(ctaSelected, mapGrupo, cacheCuentas[cfg.empresa] || cuentas, normCode);
                }
                if (ctaMask) {
                    setCtaMask(ctaMask);
                    return;
                }
                renderCuentas(ctaQ ? ctaQ.value : '');
            };
            if (!key) {
                apply([], []);
                return;
            }
            if (cacheCuentas[key]) {
                apply(cacheCuentas[key], agrupaciones);
                return;
            }
            getJSON('/CentrosCostos/api/cuentas?todas=1&empresa=' + encodeURIComponent(cfg.empresa)).then(function (json) {
                cacheCuentas[key] = json.cuentas || [];
                apply(cacheCuentas[key], json.agrupaciones || []);
            }).catch(function () {
                cuentas = [];
                ctaLoaded = true;
                renderCuentas(ctaQ ? ctaQ.value : '');
            });
        }

        function renderCuentas(q) {
            q = (q || '').toLowerCase();
            var box = document.getElementById('asig-cta-list');
            if (!box) return;
            if (!ctaLoaded) {
                box.innerHTML = '<div class="cc-empty">Cargando cuentas…</div>';
                updateCtaSelCount();
                return;
            }
            var mask = String(ctaMask || '');
            var grupoMap = grupoCuentaMap(grupos, ctaGrupo);
            var base = grupoMap ? (cacheCuentas[cfg.empresa] || cuentas) : cuentas;
            var rows = filterRowsByGrupo(base, grupoMap).filter(function (c) {
                if (grupoMap && mask && String(c.grupo_id || '') && String(c.grupo_id) !== mask) return false;
                return ((c.codigo || '') + ' ' + ctaPretty(c.codigo) + ' ' + (c.nombre || '') + ' ' + (c.grupo || '') + ' ' + (c.grupo_id || '')).toLowerCase().indexOf(q) !== -1;
            });
            if (!rows.length) {
                box.innerHTML = '<div class="cc-empty">' + (ctaGrupo ? 'Sin cuentas en este grupo' : (mask ? 'Sin cuentas para este GroupMask' : 'Sin cuentas para este catálogo')) + '</div>';
                updateCtaSelCount();
                return;
            }
            var groups = groupByMask(rows);
            box.innerHTML = Object.keys(groups).map(function (key) {
                var pack = groups[key];
                var nOn = 0;
                pack.rows.forEach(function (c) { if (ctaSelected[normCode(c.codigo)]) nOn += 1; });
                var allOn = pack.rows.length > 0 && nOn === pack.rows.length;
                return '<div class="cc-cta-group">' +
                    '<label class="cc-cta-group-h cc-cta-mask-h"><input type="checkbox" data-cta-mask="' + escapeHtml(pack.id) + '"' + (allOn ? ' checked' : '') + '>' +
                    'GroupMask ' + escapeHtml(pack.label) + ' · ' + pack.rows.length + '</label>' +
                    pack.rows.map(function (c) {
                        var checked = ctaSelected[normCode(c.codigo)] ? ' checked' : '';
                        return '<label class="cc-cta-item"><input type="checkbox" data-cta="1" value="' + escapeHtml(c.codigo) + '"' +
                            ' data-nombre="' + escapeHtml(c.nombre) + '" data-grupo="' + escapeHtml(c.grupo || '') + '"' +
                            ' data-mask="' + escapeHtml(c.grupo_id || '') + '"' + checked + '>' +
                            '<span class="cc-cta-code">' + escapeHtml(ctaPretty(c.codigo)) + '</span>' +
                            '<span class="cc-cta-name">' + escapeHtml(c.nombre) + '</span></label>';
                    }).join('') + '</div>';
            }).join('');
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
            if (ctaLoaded) {
                renderCuentas(ctaQ ? ctaQ.value : '');
            } else {
                showCtaLoading();
            }
            var todas = document.getElementById('asig-cta-todas');
            if (todas) todas.checked = false;
            fillMaskUi('asig-cta-mask', agrupaciones, cuentas, '', setCtaMask);
            fillGrupoUi('asig-cta-grupo', grupos, '', setCtaGrupo);
            updateCtaSelCount();
            refrescarModo();
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
                refrescarModo();
                return;
            }
            if (kicker) kicker.textContent = cfg.empresa
                ? (cfg.ciclo + ' · ' + userName() + ' · ' + String(cfg.empresa).toUpperCase())
                : (cfg.ciclo + ' · ' + userName());
            refrescarModo();
            loadSaved().then(function () {
                unlockEmpresas();
                renderEmpresaCards();
                markEmpresaCard(cfg.empresa);
                renderResumen();
                if (cfg.empresa) {
                    fillCentros(ccQ ? ccQ.value : '');
                    loadCuentas();
                }
            });
        }

        if (userSel) userSel.addEventListener('change', onUserChanged);

        if (ccQ) ccQ.addEventListener('input', function () { fillCentros(ccQ.value); });

        if (centroSel) centroSel.addEventListener('change', function () {
            if (!centroSel.value) {
                refrescarModo();
                return;
            }
            if (ctaQ) ctaQ.disabled = false;
            loadCuentas();
            refrescarModo();
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
            var perms = permisosMarcados();
            var revision = modoRevision();
            if (!perms.length) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Falta un permiso', text: 'Marca Capturar o Revisar.' });
                return null;
            }
            var payload = {
                empresa: cfg.empresa,
                user_id: userId(),
                centro_codigo: centroSel ? centroSel.value : '',
                centro_nombre: opt ? (opt.getAttribute('data-nombre') || '') : '',
                cuentas: ctas,
                permisos: perms
            };
            if (!payload.centro_codigo) {
                if (!revision) {
                    if (window.Swal) Swal.fire({ icon: 'warning', title: 'Falta el centro', text: 'Elige un centro de costos o la opción Sin centro de costos.' });
                    return null;
                }
                payload.centro_codigo = EMPRESA_CODIGO;
                payload.centro_nombre = EMPRESA_NOMBRE;
            }
            if (isSinCentro(payload.centro_codigo)) {
                payload.centro_codigo = SIN_CENTRO_CODIGO;
                payload.centro_nombre = SIN_CENTRO_NOMBRE;
            }
            if (isEmpresaCompleta(payload.centro_codigo)) {
                payload.centro_nombre = EMPRESA_NOMBRE;
            }
            if (!revision && !ctas.length) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Sin cuentas', text: 'Selecciona al menos una cuenta.' });
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

            savesEnCurso += 1;
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
                    savedRow.needsSnapshot = !!(res.json && res.json.snapshot);
                    upsertSaved(savedRow);
                    renderResumen();
                    renderEmpresaCards();
                    markEmpresaCard(cfg.empresa);
                    fillCentros(ccQ ? ccQ.value : '');
                }).catch(function () {
                    if (window.Swal) Swal.fire({ icon: 'error', title: 'No se guardó en servidor', text: 'La fila ya está en la tabla de resultados.' });
                }).then(function () {
                    savesEnCurso = Math.max(0, savesEnCurso - 1);
                });
        }

        function esCapturaRow(row) {
            return (row.permisos || []).indexOf('capturar') !== -1;
        }

        function centrosParaCopia() {
            var vistos = {};
            var items = [];
            saved.forEach(function (row) {
                if (!row.needsSnapshot || !esCapturaRow(row)) return;
                var cc = String(row.centro_codigo || '').toUpperCase();
                if (!cc || cc === 'SIN_CC' || cc === 'EMPRESA') return;
                var ctas = row.cuentas || [];
                if (!ctas.length) return;
                var key = String(row.empresa || '').toUpperCase() + '|' + cc;
                if (vistos[key]) return;
                vistos[key] = true;
                items.push({
                    empresa: row.empresa,
                    centro_codigo: row.centro_codigo,
                    cuentas: ctas,
                    permisos: row.permisos || ['capturar']
                });
            });
            return items;
        }

        function marcarCopiaHecha() {
            saved.forEach(function (row) { row.needsSnapshot = false; });
        }

        var btnFinGuardar = document.getElementById('asig-fin-guardar');
        var btnFinSeguir = document.getElementById('asig-fin-seguir');
        var htmlFinGuardar = btnFinGuardar ? btnFinGuardar.innerHTML : '';
        var htmlFinSeguir = btnFinSeguir ? btnFinSeguir.innerHTML : '';

        function setGuardando(on, btn) {
            [btnFinGuardar, btnFinSeguir].forEach(function (el) {
                if (el) el.disabled = !!on;
            });
            if (btnFinGuardar) {
                btnFinGuardar.innerHTML = (on && btn === btnFinGuardar)
                    ? '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…'
                    : htmlFinGuardar;
            }
            if (btnFinSeguir) {
                btnFinSeguir.innerHTML = (on && btn === btnFinSeguir)
                    ? '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…'
                    : htmlFinSeguir;
            }
        }

        function cuandoTermineAsignar(fn) {
            if (!savesEnCurso) {
                fn();
                return;
            }
            var n = 0;
            var t = setInterval(function () {
                n += 1;
                if (!savesEnCurso || n > 80) {
                    clearInterval(t);
                    fn();
                }
            }, 250);
        }

        function guardarCopiaLocal(btn, luego) {
            if (copiaBusy) return;
            if (!saved.length) {
                if (window.Swal) Swal.fire({ icon: 'warning', title: 'Nada que guardar', text: 'Asigna al menos un centro a la tabla de resultados.' });
                return;
            }
            copiaBusy = true;
            setGuardando(true, btn);
            cuandoTermineAsignar(function () {
                var items = centrosParaCopia();
                var listo = function () {
                    if (luego) luego();
                };
                if (!items.length || !cfg.snapshotUrl) {
                    listo();
                    return;
                }
                fetch(cfg.snapshotUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ items: items })
                }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
                    .then(function (res) {
                        if (!res.ok || !res.json || res.json.ok === false) {
                            copiaBusy = false;
                            setGuardando(false, btn);
                            if (window.Swal) Swal.fire({ icon: 'error', title: 'No se guardó la copia local', text: (res.json && res.json.message) || 'Vuelve a pulsar Guardar.' });
                            return;
                        }
                        marcarCopiaHecha();
                        listo();
                    }).catch(function () {
                        copiaBusy = false;
                        setGuardando(false, btn);
                        if (window.Swal) Swal.fire({ icon: 'error', title: 'No se guardó la copia local', text: 'Vuelve a pulsar Guardar.' });
                    });
            });
        }

        if (btnSave) btnSave.addEventListener('click', function (ev) {
            ev.preventDefault();
            aplicarAResultados();
        });

        if (btnFinGuardar) btnFinGuardar.addEventListener('click', function (ev) {
            ev.preventDefault();
            guardarCopiaLocal(btnFinGuardar, function () {
                window.location.reload();
            });
        });

        if (btnFinSeguir) btnFinSeguir.addEventListener('click', function (ev) {
            ev.preventDefault();
            guardarCopiaLocal(btnFinSeguir, function () {
                window.location.href = cfg.cicloUrl || ('/AdminCentros/' + encodeURIComponent(cfg.ciclo));
            });
        });

        if (userQ) userQ.addEventListener('input', function () { fillUsers(userQ.value); });
        var userClear = document.getElementById('asig-user-clear');
        if (userClear) userClear.addEventListener('click', function (ev) {
            ev.preventDefault();
            pickUser('');
        });

        document.querySelectorAll('#asig-permisos input[name="permiso"]').forEach(function (input) {
            input.addEventListener('change', function () {
                if (input.checked && (input.value === 'capturar' || input.value === 'revisar')) {
                    var otro = input.value === 'capturar' ? 'revisar' : 'capturar';
                    document.querySelectorAll('#asig-permisos input[name="permiso"]').forEach(function (i) {
                        if (i.value === otro) i.checked = false;
                    });
                }
                refrescarModo();
            });
        });

        unlockEmpresas();
        loadEmpresas();
        fillUsers('');
        refrescarModo();
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
