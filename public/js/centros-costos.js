(function (window, document) {
    'use strict';

    var MONTHS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    var STATUS = {
        abierto: { label: 'Abierto', cls: 'cc-badge-abierto' },
        en_proceso: { label: 'En proceso', cls: 'cc-badge-en_proceso' },
        terminado: { label: 'Terminado', cls: 'cc-badge-terminado' },
        en_revision: { label: 'En revisión', cls: 'cc-badge-en_revision' }
    };
    var CICLO_STATUS = {
        abierto: { label: 'Abierto', cls: 'cc-badge-abierto' },
        en_revision: { label: 'En revisión', cls: 'cc-badge-en_revision' },
        cerrado: { label: 'Cerrado', cls: 'cc-badge-cerrado' }
    };
    var SK = {
        admin: 'austin.cc.admin.v1',
        budget: 'austin.cc.budget.v1',
        period: 'austin.cc.period.v1',
        ciclos: 'austin.cc.ciclos.v1',
        currency: 'austin.cc.currency.v1'
    };

    function currentCurrency() {
        var el = document.getElementById('ctl-moneda');
        var raw = el && el.value ? el.value : (CC.state.currency || 'MXN');
        return String(raw).toUpperCase() === 'USD' ? 'USD' : 'MXN';
    }

    function fxRate() {
        var p = CC.state.period || {};
        var n = Number(p.tipoCambio != null && p.tipoCambio !== '' ? p.tipoCambio : p.tipo_cambio);
        return n > 0 ? n : 20;
    }

    function captureIsUsd() {
        return currentCurrency() === 'USD';
    }

    function syncCurrencyState() {
        CC.state.currency = currentCurrency();
        return CC.state.currency;
    }

    function decimalPlaces(n) {
        var v = Math.abs(Number(n) || 0);
        if (!isFinite(v)) return 0;
        var s = v.toFixed(8).replace(/0+$/, '').replace(/\.$/, '');
        var dot = s.indexOf('.');
        return dot < 0 ? 0 : (s.length - dot - 1);
    }

    function round2(n) {
        var v = Number(n);
        if (!isFinite(v)) return 0;
        if (decimalPlaces(v) > 3) {
            var sign = v < 0 ? -1 : 1;
            return sign * Math.floor(Math.abs(v) * 100 + 1e-8) / 100;
        }
        return Math.round(v * 100) / 100;
    }

    function mxnToCapture(n) {
        var v = Number(n) || 0;
        if (captureIsUsd()) v = v / fxRate();
        return round2(v);
    }

    function captureToMxn(n) {
        if (n === null || n === undefined || n === '') return null;
        var v = Number(n);
        if (!isFinite(v)) v = 0;
        if (captureIsUsd()) v = v * fxRate();
        return round2(v);
    }

    function inputAmount(n) {
        if (!mesLleno(n)) return '';
        return mxnToCapture(n).toFixed(2);
    }

    function parseCaptureInput(raw) {
        syncCurrencyState();
        var s = String(raw || '').trim().replace(/,/g, '');
        if (s === '') return null;
        return captureToMxn(s);
    }

    function money(n, currency) {
        var cur = currency || currentCurrency();
        var val = Number(n) || 0;
        if (cur === 'USD') val = val / fxRate();
        val = round2(val);
        return (cur === 'USD' ? 'US$' : '$') + val.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function moneyDec(n) {
        return (Number(n) || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function mesLleno(v) {
        return v !== null && v !== undefined && v !== '';
    }

    function mesesTodosLlenos(months) {
        return Array.isArray(months) && months.length === 12 && months.every(mesLleno);
    }

    function sum(arr) {
        return (arr || []).reduce(function (a, b) { return a + (Number(b) || 0); }, 0);
    }

    function initials(name) {
        return String(name || '?').split(/[.\s]+/).filter(Boolean).slice(0, 2).map(function (p) {
            return p.charAt(0).toUpperCase();
        }).join('');
    }

    function uid() {
        return 'cc-' + Math.random().toString(36).slice(2, 9);
    }

    function season(total) {
        var w = [0.07, 0.07, 0.08, 0.08, 0.09, 0.08, 0.08, 0.08, 0.09, 0.1, 0.09, 0.09];
        return w.map(function (p) { return Math.round(total * p); });
    }

    function pct(a, b) {
        if (!b) return a ? 100 : 0;
        return Math.round((a / b) * 1000) / 10;
    }

    function deltaPct(now, prev) {
        if (!prev) return now ? 100 : 0;
        return Math.round(((now - prev) / prev) * 1000) / 10;
    }

    function loadJSON(key, fallback) {
        try {
            var raw = localStorage.getItem(key);
            return raw ? JSON.parse(raw) : fallback;
        } catch (e) {
            return fallback;
        }
    }

    function saveJSON(key, val) {
        localStorage.setItem(key, JSON.stringify(val));
    }

    function toast(icon, title, text) {
        if (window.Swal) {
            Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false,
                timer: 2600, timerProgressBar: true
            }).fire({ icon: icon, title: title, text: text || '' });
        }
    }

    function demoCuentas() {
        return [
            { codigo: '5110-010', nombre: 'AGUA', grupo: 'Mantenimiento', empresa: 'ABSA', gasto: season(18400) },
            { codigo: '5110-021', nombre: 'ARRENDAMIENTO EQUIPO OFICINA', grupo: 'Equipo', empresa: 'ABSA', gasto: season(96000) },
            { codigo: '5110-022', nombre: 'ARRENDAMIENTO DE LOCALES', grupo: 'Equipo', empresa: 'ABSA', gasto: season(42000) },
            { codigo: '5110-023', nombre: 'ARRENDAMIENTO E LOCALES PERSONA MORAL', grupo: 'Equipo', empresa: 'ABSA', gasto: [3500,3500,3500,3500,3500,3500,3500,3500,3500,3500,3500,3500] },
            { codigo: '5110-040', nombre: 'COMUNICACIÓN CELULAR', grupo: 'Equipo', empresa: 'ABSA', gasto: season(28600) },
            { codigo: '5110-050', nombre: 'ENERGIA ELECTRICA', grupo: 'Mantenimiento', empresa: 'ABSA', gasto: season(67400) },
            { codigo: '5110-061', nombre: 'FLETES', grupo: 'Transporte', empresa: 'ABSA', gasto: season(189200) },
            { codigo: '5110-070', nombre: 'GASTOS DE VIAJE', grupo: 'Transporte', empresa: 'ABSA', gasto: season(54200) },
            { codigo: '5110-080', nombre: 'IMPRESIONES Y REPRODUCCIONES', grupo: 'Equipo', empresa: 'ABSA', gasto: season(8200) },
            { codigo: '5110-090', nombre: 'IMPUESTOS Y DERECHOS ESTATALES/MUNICIPAL', grupo: 'Mantenimiento', empresa: 'ABSA', gasto: season(21400) },
            { codigo: '5110-100', nombre: 'MENSAJERIAS', grupo: 'Transporte', empresa: 'ABSA', gasto: season(9600) },
            { codigo: '5120-010', nombre: 'LICENCIAS SOFTWARE', grupo: 'Equipo', empresa: 'ABSA', gasto: season(118000) },
            { codigo: '5120-020', nombre: 'EQUIPO DE CÓMPUTO', grupo: 'Equipo', empresa: 'ABSA', gasto: season(86000) },
            { codigo: '5130-010', nombre: 'COMBUSTIBLE', grupo: 'Transporte', empresa: 'ABSA', gasto: season(41200) },
            { codigo: '5140-010', nombre: 'MANTENIMIENTO EQUIPO', grupo: 'Mantenimiento', empresa: 'ABSA', gasto: season(33800) }
        ];
    }

    function demoCentros() {
        return [
            { codigo: '385', nombre: 'TECNOLOGÍAS DE LA INFORMACIÓN', empresa: 'ABSA', departamento: 'AdminExp', usuario: 'Rene', estado: 'en_proceso', modo: 'captura', gasto2026: 428400, ppto2027: 312000, fecha: '04/11/25' },
            { codigo: '181', nombre: 'CAPACITACION', empresa: 'ABSA', departamento: 'AdminExp', usuario: 'Jesus.Ramirez', estado: 'abierto', modo: 'captura', gasto2026: 96200, ppto2027: 0, fecha: '04/11/25' },
            { codigo: '203', nombre: 'ABRAHAM MORALES RAMIREZ', empresa: 'ABSA', departamento: 'FieldSellExp', usuario: 'Marycarmen.Najera', estado: 'en_revision', modo: 'solo_revision', gasto2026: 563710, ppto2027: 302788, fecha: '04/11/25' },
            { codigo: '319', nombre: 'ALMACEN', empresa: 'ABSA', departamento: 'DirectLabor', usuario: 'Jesus.Ramirez', estado: 'en_proceso', modo: 'captura', gasto2026: 214500, ppto2027: 180000, fecha: '04/11/25' },
            { codigo: '110', nombre: 'PRODUCCIÓN PLANTA', empresa: 'ABSA', departamento: 'MfgOverhead', usuario: 'Ana.Lopez', estado: 'terminado', modo: 'solo_revision', gasto2026: 890400, ppto2027: 910000, fecha: '04/08/25' },
            { codigo: '220', nombre: 'VENTAS NORTE', empresa: 'IMSA', departamento: 'FieldSellExp', usuario: 'Carlos.Mendez', estado: 'abierto', modo: 'captura', gasto2026: 334000, ppto2027: 40000, fecha: '03/22/25' },
            { codigo: '441', nombre: 'MANTENIMIENTO FLOTILLA', empresa: 'PITIC', departamento: 'DirectLabor', usuario: 'Lucia.Perez', estado: 'en_proceso', modo: 'captura', gasto2026: 156800, ppto2027: 210000, fecha: '04/02/25' },
            { codigo: '512', nombre: 'ADMINISTRACIÓN GENERAL', empresa: 'SYDNEY', departamento: 'AdminExp', usuario: 'Rene', estado: 'terminado', modo: 'solo_revision', gasto2026: 278900, ppto2027: 265000, fecha: '03/30/25' }
        ];
    }

    var CC = {
        MONTHS: MONTHS,
        STATUS: STATUS,
        money: money,
        sum: sum,
        pct: pct,
        deltaPct: deltaPct,
        toast: toast,
        state: {
            currency: 'MXN',
            page: 'admin',
            sapOk: false,
            centros: [],
            cuentas: [],
            agrupaciones: [],
            usuarios: [],
            period: {},
            overlays: {},
            budgets: {},
            completados: {},
            charts: {}
        }
    };

    function centroKey(c) {
        return (c.empresa || '') + '|' + (c.codigo || '');
    }

    function budgetKey(empresa, cc, cuenta) {
        return [empresa, cc, cuenta].join('|');
    }

    function cuentaMarcada(empresa, cc, codigo) {
        var done = CC.state.completados || {};
        var k = budgetKey(empresa, cc, codigo);
        if (done[k]) return true;
        var needle = String(k).toUpperCase();
        return Object.keys(done).some(function (key) {
            return done[key] && String(key).toUpperCase() === needle;
        });
    }

    function ctaPendiente(cta) {
        return !(cta && cta.listo);
    }

    function overlayOf(c) {
        return CC.state.overlays[centroKey(c)] || {};
    }

    function mergedCentro(c) {
        var o = overlayOf(c);
        return Object.assign({}, c, o, {
            estado: o.estado || c.estado || 'abierto',
            usuario: o.usuario || c.usuario || '',
            modo: o.modo || c.modo || 'captura',
            cuentasAsignadas: o.cuentasAsignadas || c.cuentasAsignadas || null,
            permisos: o.permisos || c.permisos || []
        });
    }

    function cuentasDeCentro(c) {
        var asig = asigDe(c);
        if (asig && asig.cuentas && asig.cuentas.length) {
            var gmap = gastoLookupDeCentro(c);
            return asig.cuentas.map(function (x) {
                var sap = (CC.state.cuentas || []).filter(function (cta) {
                    return String(cta.codigo) === String(x.codigo);
                })[0];
                var nombre = x.nombre || (sap && sap.nombre) || x.codigo;
                return {
                    codigo: x.codigo,
                    nombre: nombre,
                    grupo: x.agrupacion || (sap && sap.grupo) || 'Asignadas',
                    gasto: gastoMensualDe(x.codigo, nombre, gmap),
                    empresa: c.empresa
                };
            });
        }
        if (CC.state.page === 'control' || CC.state.page === 'detalle' || CC.state.page === 'analisis') return [];
        var all = CC.state.cuentas.filter(function (cta) {
            return !cta.empresa || !c.empresa || String(cta.empresa).toUpperCase() === String(c.empresa).toUpperCase();
        });
        var o = overlayOf(c);
        if (o.cuentasAsignadas && o.cuentasAsignadas.length) {
            return all.filter(function (cta) { return o.cuentasAsignadas.indexOf(cta.codigo) !== -1; });
        }
        return all;
    }

    function asigsDe(c) {
        var ciclo = (document.getElementById('ctl-ciclo') && val('ctl-ciclo'))
            || (document.getElementById('an-ciclo') && val('an-ciclo'))
            || CC.state.cicloCodigo || '';
        return (CC.state.misAsignaciones || []).filter(function (a) {
            var sameCc = String(a.centro_codigo) === String(c.codigo);
            var sameEmp = String(a.empresa || '').toLowerCase() === String(c.empresa || a.empresa || '').toLowerCase();
            var sameCiclo = !ciclo || String(a.ciclo || '') === String(ciclo);
            return sameCc && sameEmp && sameCiclo;
        });
    }

    function asigDe(c) {
        var list = asigsDe(c);
        if (!list.length) return null;
        var prin = list.filter(function (a) { return a.es_principal; })[0] || list[0];
        var base = Object.assign({}, prin);
        var seen = {};
        var cuentas = [];
        list.forEach(function (a) {
            (a.cuentas || []).forEach(function (x) {
                var k = String(x.codigo || '');
                if (!k || seen[k]) return;
                seen[k] = true;
                cuentas.push(x);
            });
        });
        base.cuentas = cuentas;
        list.forEach(function (a) {
            base.capturar = !!(base.capturar || a.capturar);
            base.editar = !!(base.editar || a.editar);
            base.revisar = !!(base.revisar || a.revisar);
        });
        return base;
    }

    function codigoCuentaVisible(codigo) {
        var raw = String(codigo || '').trim();
        if (!raw) return '';
        if (!/sys/i.test(raw)) return raw;
        var out = raw.replace(/_?SYS/gi, '').replace(/^0+/, '').replace(/^[\s\-_]+/, '');
        return out || raw.replace(/\D+/g, '').replace(/^0+/, '') || raw;
    }

    function codigoCuentaKey(codigo) {
        var vis = codigoCuentaVisible(codigo);
        if (!vis) return String(codigo || '').trim();
        return vis.replace(/\D+/g, '') || vis;
    }

    function nombreCuentaKey(nombre) {
        var s = String(nombre || '');
        try { s = s.normalize('NFD').replace(/[\u0300-\u036f]/g, ''); } catch (e) {}
        return s.toUpperCase().replace(/Ñ/g, 'N').replace(/[^A-Z0-9]+/g, ' ').trim().replace(/\s+/g, ' ');
    }

    function nombresGastoCompatibles(a, b) {
        if (!a || !b) return false;
        if (a === b) return true;
        var ac = a.replace(/\s+/g, '');
        var bc = b.replace(/\s+/g, '');
        if (ac === bc) return true;
        var as = stripPrefijoCuenta(a).replace(/\s+/g, '');
        var bs = stripPrefijoCuenta(b).replace(/\s+/g, '');
        if (as && bs && as === bs) return true;
        if (as && (as === bc || as === ac)) return true;
        if (bs && (bs === ac || bs === as)) return true;
        var minLen = Math.min(ac.length, bc.length);
        if (minLen >= 8 && (ac.indexOf(bc) === 0 || bc.indexOf(ac) === 0)) return true;
        return as.length >= 8 && bs.length >= 8 && (as.indexOf(bs) === 0 || bs.indexOf(as) === 0);
    }

    function stripPrefijoCuenta(s) {
        var parts = String(s || '').trim().split(/\s+/);
        if (parts.length >= 2 && parts[0].length === 2) {
            return parts.slice(1).join(' ');
        }
        return String(s || '');
    }

    function zeros12() {
        return [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    }

    function moneyGasto(n) {
        n = Number(n) || 0;
        return n ? money(n) : '—';
    }

    function nombresDesdeMapa(map) {
        var list = [];
        Object.keys(map || {}).forEach(function (k) {
            if (k.indexOf('n:') !== 0) return;
            var gasto = map[k];
            if (gasto && gasto.length === 12) list.push({ key: k.slice(2), gasto: gasto });
        });
        return list;
    }

    function gastoMensualDe(codigo, nombre, mapOpt) {
        var map = mapOpt || control._gastoMap || {};
        var nk = nombreCuentaKey(nombre);
        if (!nk) return zeros12();
        var compact = nk.replace(/\s+/g, '');
        var hit = map['n:' + nk] || map['nc:' + compact] || map[nk] || map[compact];
        if (!hit || hit.length !== 12) {
            var list = control._gastoNombres && (!mapOpt || mapOpt === control._gastoMap)
                ? control._gastoNombres
                : nombresDesdeMapa(map);
            for (var i = 0; i < list.length; i++) {
                if (nombresGastoCompatibles(nk, list[i].key)) {
                    hit = list[i].gasto;
                    break;
                }
            }
        }
        if (hit && hit.length === 12) return hit.slice();
        return zeros12();
    }

    function asMeses12(gasto) {
        if (Array.isArray(gasto) && gasto.length === 12) return gasto;
        if (gasto && typeof gasto === 'object') {
            var a = [];
            for (var i = 0; i < 12; i++) a.push(Number(gasto[i] != null ? gasto[i] : gasto[String(i)]) || 0);
            return a;
        }
        return null;
    }

    function mapFromPorCuenta(porCuenta) {
        var map = {};
        var nombres = [];
        Object.keys(porCuenta || {}).forEach(function (key) {
            var row = porCuenta[key] || {};
            var gasto = asMeses12(row.gasto || row);
            if (!gasto) return;
            var nk = nombreCuentaKey(row.nombre || key || '');
            if (!nk) return;
            var compact = nk.replace(/\s+/g, '');
            map['n:' + nk] = gasto;
            map['nc:' + compact] = gasto;
            map[nk] = gasto;
            map[compact] = gasto;
            nombres.push({ key: nk, gasto: gasto });
        });
        return { map: map, nombres: nombres };
    }

    function gastoCacheKey(c) {
        var year = CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || 2026;
        return String(c.empresa || '').toUpperCase() + '|' + year;
    }

    function gastoLookupDeCentro(c) {
        if (!c) return control._gastoMap || {};
        var key = gastoCacheKey(c);
        if (control._gastoReq === key && control._gastoMap) return control._gastoMap;
        var packed = CC.state.gastoLookup && CC.state.gastoLookup[key];
        if (packed) return packed;
        var por = CC.state.gastoCache && CC.state.gastoCache[key];
        if (!por) return {};
        var built = mapFromPorCuenta(por);
        CC.state.gastoLookup = CC.state.gastoLookup || {};
        CC.state.gastoLookup[key] = built.map;
        return built.map;
    }

    function applyGastoMap(porCuenta) {
        var built = mapFromPorCuenta(porCuenta);
        control._gastoMap = built.map;
        control._gastoNombres = built.nombres;
        if (control._gastoReq) {
            CC.state.gastoLookup = CC.state.gastoLookup || {};
            CC.state.gastoLookup[control._gastoReq] = built.map;
        }
        if (!control.centro) return;
        control._allCtas = cuentasEnriquecidas(control.centro);
        updateControlProgress();
        renderNavCuentas();
        renderCtaChips();
        renderCapturaForm();
        renderControlTable();
        renderControlCharts();
        renderDetalleTable();
        renderDetalleChart();
        renderVisorTable();
        paintDetalleHeader();
    }

    function nombreGastoCargado(nombre) {
        var nk = nombreCuentaKey(nombre);
        if (!nk) return false;
        var map = control._gastoMap || {};
        var hit = map['n:' + nk] || map[nk] || map['nc:' + nk.replace(/\s+/g, '')];
        return !!(hit && hit.length === 12);
    }

    function labelGastoMes(cta, i) {
        var n = Number((cta && cta.gasto && cta.gasto[i]) || 0);
        if (n) return 'Gastó ' + money(n);
        if (!nombreGastoCargado(cta && cta.nombre)) return 'Cargando…';
        return 'Sin gasto';
    }

    function nombresAsignadosCentro(c) {
        var asig = asigDe(c);
        var out = [];
        var seen = {};
        ((asig && asig.cuentas) || []).forEach(function (x) {
            var n = String((x && x.nombre) || '').trim();
            var k = nombreCuentaKey(n);
            if (!k || seen[k]) return;
            seen[k] = true;
            out.push(n);
        });
        return out;
    }

    function mergePorCuenta(por) {
        var key = control._gastoReq;
        CC.state.gastoCache = CC.state.gastoCache || {};
        CC.state.gastoCache[key] = Object.assign({}, CC.state.gastoCache[key] || {}, por || {});
        applyGastoMap(CC.state.gastoCache[key]);
    }

    function fetchGastoNombres(c, nombres, done) {
        if (!nombres.length) {
            if (done) done();
            return;
        }
        var year = CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || 2026;
        var params = new URLSearchParams();
        params.set('empresa', c.empresa || '');
        params.set('cc', c.codigo || '');
        params.set('year', String(year));
        nombres.forEach(function (n) { params.append('nombres[]', n); });
        fetch(CC.state.gastoUrl + '?' + params.toString(), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (json) {
            if (control._gastoReq !== gastoCacheKey(c)) return;
            mergePorCuenta((json && json.por_cuenta) || {});
            if (done) done();
        }).catch(function () {
            if (control._gastoReq !== gastoCacheKey(c)) return;
            var dummy = {};
            nombres.forEach(function (n) {
                dummy[nombreCuentaKey(n)] = { nombre: n, gasto: zeros12() };
            });
            mergePorCuenta(dummy);
            if (done) done();
        });
    }

    function loadGastoRealCentro(c) {
        if (!c || !CC.state.gastoUrl) return;
        var key = gastoCacheKey(c);
        control._gastoReq = key;
        var all = nombresAsignadosCentro(c);
        var pend = all.filter(function (n) { return !nombreGastoCargado(n); });
        if (!pend.length) {
            if (CC.state.gastoCache && CC.state.gastoCache[key]) applyGastoMap(CC.state.gastoCache[key]);
            return;
        }
        var actual = currentCta();
        var first = [];
        if (actual && actual.nombre && !nombreGastoCargado(actual.nombre)) first = [actual.nombre];
        if (!first.length) first = pend.slice(0, 1);
        var rest = pend.filter(function (n) {
            return nombreCuentaKey(n) !== nombreCuentaKey(first[0] || '');
        });
        fetchGastoNombres(c, first, function () {
            fetchGastoNombres(c, rest);
        });
    }

    function pptoDe(empresa, cc, cuenta, gasto) {
        var key = budgetKey(empresa, cc, cuenta.codigo);
        if (CC.state.budgets[key]) return CC.state.budgets[key].slice();
        if (cuenta.ppto) return cuenta.ppto.slice();
        return (gasto || cuenta.gasto || [0,0,0,0,0,0,0,0,0,0,0,0]).map(function () { return null; });
    }

    function persistBudget(empresa, cc, cuenta, months, opts) {
        opts = opts || {};
        syncCurrencyState();
        months = (months || []).map(function (v) { return mesLleno(v) ? round2(v) : v; });
        CC.state.budgets[budgetKey(empresa, cc, cuenta)] = months.slice();
        var ciclo = cicloActualCodigo();
        if (!ciclo) return;
        var key = budgetKey(empresa, cc, cuenta);
        CC.state.completados = CC.state.completados || {};
        var allFilled = mesesTodosLlenos(months);
        var done = Object.prototype.hasOwnProperty.call(opts, 'completado')
            ? !!opts.completado
            : allFilled;
        if (done && !allFilled) {
            months = months.map(function (v) { return mesLleno(v) ? v : 0; });
            CC.state.budgets[budgetKey(empresa, cc, cuenta)] = months.slice();
        }
        if (done) CC.state.completados[key] = true;
        else delete CC.state.completados[key];
        if (CC._budgetSaveTimers && CC._budgetSaveTimers[key]) clearTimeout(CC._budgetSaveTimers[key]);
        CC._budgetSaveTimers = CC._budgetSaveTimers || {};
        CC._budgetSaveTimers[key] = setTimeout(function () {
            var nombre = '';
            var list = (control && control._allCtas) || [];
            for (var i = 0; i < list.length; i++) {
                if (String(list[i].codigo) === String(cuenta)) {
                    nombre = list[i].nombre || '';
                    break;
                }
            }
            fetch('/CentrosCostos/api/captura/presupuesto', {
                method: 'PUT',
                headers: apiJsonHeaders(),
                body: JSON.stringify({
                    ciclo: ciclo,
                    empresa: empresa,
                    centro: cc,
                    cuenta: cuenta,
                    cuenta_nombre: nombre,
                    meses: months.slice(),
                    completado: done
                })
            }).then(function (res) {
                return res.json().then(function (json) {
                    if (!res.ok) throw new Error(json.message || 'No se pudo guardar el presupuesto');
                    CC.state.completados = CC.state.completados || {};
                    if (json.completado) CC.state.completados[key] = true;
                    else delete CC.state.completados[key];
                });
            }).catch(function (err) {
                toast('error', 'No se guardó el presupuesto', err && err.message ? err.message : 'Error de red');
            });
        }, 350);
    }

    function persistOverlay(c, patch) {
        var k = centroKey(c);
        CC.state.overlays[k] = Object.assign({}, CC.state.overlays[k] || {}, patch, { fecha: new Date().toLocaleDateString('es-MX') });
        if (!patch || patch.estado == null || !c) return;
        if (CC.state.page !== 'control' && CC.state.page !== 'detalle') return;
        var ciclo = cicloActualCodigo();
        if (!ciclo) return;
        fetch('/CentrosCostos/api/captura/centro', {
            method: 'PUT',
            headers: apiJsonHeaders(),
            body: JSON.stringify({
                ciclo: ciclo,
                empresa: c.empresa,
                centro: c.codigo,
                estado: CC.state.overlays[k].estado
            })
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok) throw new Error(json.message || 'No se pudo guardar el estado');
                if (json.fecha) CC.state.overlays[k].fecha = json.fecha;
            });
        }).catch(function (err) {
            toast('error', 'No se guardó el estado', err && err.message ? err.message : 'Error de red');
        });
    }

    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function apiJsonHeaders() {
        return {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken()
        };
    }

    function cicloActualCodigo() {
        return String((CC.state.period && CC.state.period.codigo) || CC.state.cicloCodigo || '');
    }

    function persistPeriod() {
        upsertCiclo(CC.state.period);
        saveJSON(SK.period, CC.state.period);
    }

    function saveCicloApi(data) {
        return fetch('/CentrosCostos/api/ciclos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify(data)
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok) {
                    var msg = json.message || 'No se pudo guardar el ciclo';
                    if (json.errors) {
                        var k = Object.keys(json.errors)[0];
                        if (k && json.errors[k] && json.errors[k][0]) msg = json.errors[k][0];
                    }
                    throw new Error(msg);
                }
                return json.ciclo || data;
            });
        });
    }

    function normalizeLoadedMonths(months, done) {
        months = Array.isArray(months) ? months.slice() : [];
        while (months.length < 12) months.push(null);
        months = months.slice(0, 12);
        if (done) {
            return months.map(function (v) { return mesLleno(v) ? Number(v) : 0; });
        }
        return months.map(function (v) {
            if (!mesLleno(v)) return null;
            return Number(v);
        });
    }

    function overlayStoreKey() {
        return SK.admin + ':' + String((CC.state.period && CC.state.period.codigo) || 'default');
    }

    function budgetStoreKey() {
        return SK.budget + ':' + String((CC.state.period && CC.state.period.codigo) || 'default');
    }

    function loadOverlaysForCycle() {
        var ciclo = cicloActualCodigo();
        CC._capturaReady = false;
        CC.state.overlays = {};
        CC.state.budgets = {};
        CC.state.completados = {};
        if (!ciclo) {
            CC._capturaReady = true;
            return Promise.resolve();
        }
        return fetch('/CentrosCostos/api/captura?ciclo=' + encodeURIComponent(ciclo), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (json) {
            CC.state.overlays = json.overlays || {};
            CC.state.completados = json.completados || {};
            CC.state.budgets = {};
            var rawBudgets = json.budgets || {};
            Object.keys(rawBudgets).forEach(function (k) {
                CC.state.budgets[k] = normalizeLoadedMonths(rawBudgets[k], !!CC.state.completados[k]);
            });
            CC._capturaReady = true;
        }).catch(function () {
            CC.state.overlays = {};
            CC.state.budgets = {};
            CC.state.completados = {};
            CC._capturaReady = true;
        });
    }

    function loadCiclos(periodoDefault, fromBoot) {
        if (Array.isArray(fromBoot)) return fromBoot.slice();
        var saved = loadJSON(SK.ciclos, null);
        if (Array.isArray(saved) && saved.length) return saved;
        var old = loadJSON(SK.period, null);
        if (old && old.codigo) return [Object.assign({}, periodoDefault || {}, old)];
        return [];
    }

    function findCiclo(codigo) {
        var needle = String(codigo || '').toUpperCase();
        return (CC.state.ciclos || []).filter(function (c) {
            return String(c.codigo || '').toUpperCase() === needle;
        })[0] || null;
    }

    function upsertCiclo(data) {
        var list = CC.state.ciclos || [];
        var i = -1;
        list.forEach(function (c, idx) {
            if (String(c.codigo).toUpperCase() === String(data.codigo).toUpperCase()) i = idx;
        });
        if (i >= 0) list[i] = Object.assign({}, list[i], data);
        else list.unshift(data);
        CC.state.ciclos = list;
        saveJSON(SK.ciclos, list);
    }

    function cicloUrl(codigo) {
        return '/AdminCentros/' + encodeURIComponent(codigo);
    }

    function leerFormCiclo() {
        return {
            codigo: val('p-codigo').trim(),
            nombre: val('p-nombre').trim(),
            anioReferencia: Number(val('p-anio-ref')) || 2026,
            anio: Number(val('p-anio')) || 2027,
            inicio: val('p-inicio'),
            fin: val('p-fin'),
            capturaHasta: val('p-captura'),
            revisionDesde: val('p-revision'),
            estado: val('p-estado'),
            inflacion: Number(val('p-inflacion')) || 4,
            tipoCambio: Number(val('p-tc')) || 20,
            observaciones: val('p-obs')
        };
    }

    function attachGasto(cuentas) {
        return cuentas.map(function (cta, i) {
            if (cta.gasto && cta.gasto.length === 12) return cta;
            var base = 8000 + ((i * 13751) % 120000);
            return Object.assign({}, cta, { gasto: season(base), grupo: cta.grupo || grupoDesdeNombre(cta.nombre) });
        });
    }

    function grupoDesdeNombre(nombre) {
        var n = String(nombre || '').toUpperCase();
        if (/FLETE|VIAJE|COMBUST|TRANSP|MENSAJ|CELULAR/.test(n)) return 'Transporte';
        if (/MANTEN|AGUA|ENERG|IMPUEST|SERVICIO/.test(n)) return 'Mantenimiento';
        return 'Equipo';
    }

    function mergeSap(boot, opts) {
        opts = opts || {};
        var lockCentros = !!opts.lockCentros;
        var sapCentros = boot.centros || [];
        var sapCuentas = boot.cuentas || [];
        var demoC = demoCentros();
        var demoA = demoCuentas();
        if (sapCentros.length) CC.state.sapCentros = sapCentros;

        if (!lockCentros) {
            if (sapCentros.length) {
                CC.state.centros = sapCentros.map(function (c, i) {
                    var match = demoC.filter(function (d) { return d.codigo === c.codigo; })[0] || demoC[i % demoC.length];
                    return {
                        codigo: c.codigo,
                        nombre: c.nombre || match.nombre,
                        empresa: c.empresa || match.empresa,
                        departamento: c.departamento || match.departamento || 'AdminExp',
                        usuario: match.usuario || '',
                        estado: match.estado || 'abierto',
                        modo: match.modo || 'captura',
                        gasto2026: match.gasto2026 || 0,
                        ppto2027: match.ppto2027 || 0,
                        fecha: match.fecha || '',
                        sap: true
                    };
                });
                CC.state.sapOk = true;
            } else {
                CC.state.centros = demoC;
                CC.state.sapOk = !!boot.sapOk;
            }
        } else if (sapCentros.length || boot.sapOk) {
            CC.state.sapOk = true;
        }

        if (sapCuentas.length) {
            CC.state.cuentas = attachGasto(sapCuentas.map(function (c) {
                var match = demoA.filter(function (d) { return d.nombre === c.nombre; })[0];
                return {
                    codigo: c.codigo,
                    nombre: c.nombre,
                    empresa: c.empresa,
                    grupo: c.grupo || (match && match.grupo) || grupoDesdeNombre(c.nombre),
                    gasto: match ? match.gasto : null
                };
            }));
        } else if (!lockCentros) {
            CC.state.cuentas = demoA;
        }

        CC.state.agrupaciones = (boot.agrupaciones && boot.agrupaciones.length)
            ? boot.agrupaciones
            : (CC.state.agrupaciones && CC.state.agrupaciones.length
                ? CC.state.agrupaciones
                : [{ id: 'eq', nombre: 'Equipo' }, { id: 'tr', nombre: 'Transporte' }, { id: 'mt', nombre: 'Mantenimiento' }]);
    }

    function kpisAdmin() {
        var list = CC.state.centros.map(mergedCentro);
        var by = function (st) { return list.filter(function (c) { return c.estado === st; }).length; };
        return {
            centros: list.length,
            abiertos: by('abierto') + by('en_proceso'),
            revision: by('en_revision'),
            aceptados: by('aceptado'),
            usuarios: list.map(function (c) { return c.usuario; }).filter(Boolean).filter(function (v, i, a) { return a.indexOf(v) === i; }).length
        };
    }

    function normalizeEstado(estado) {
        if (estado === 'aceptado' || estado === 'rechazado') return 'terminado';
        return STATUS[estado] ? estado : 'abierto';
    }

    function normalizeCicloEstado(estado) {
        if (estado === 'cerrado' || estado === 'terminado' || estado === 'aceptado' || estado === 'rechazado') return 'cerrado';
        if (estado === 'en_revision') return 'en_revision';
        return 'abierto';
    }

    function cicloEstadoDe(codigo) {
        var needle = String(codigo || '').toUpperCase();
        var cyc = findCiclo(codigo);
        if (!cyc && CC.state.period && String(CC.state.period.codigo || '').toUpperCase() === needle) {
            cyc = CC.state.period;
        }
        return normalizeCicloEstado(cyc && cyc.estado);
    }

    function puedeEscribirAsig(asig, cicloCodigo) {
        var a = asig || {};
        var st = cicloEstadoDe(cicloCodigo || a.ciclo || CC.state.cicloCodigo || (CC.state.period && CC.state.period.codigo) || '');
        if (st === 'abierto') return !!(a.capturar || a.editar);
        return !!a.editar;
    }

    function renderBadge(estado) {
        var s = STATUS[normalizeEstado(estado)] || STATUS.abierto;
        return '<span class="cc-badge ' + s.cls + '"><span class="cc-dot"></span>' + s.label + '</span>';
    }

    function fillSelect(el, items, valueKey, labelKey, extra) {
        if (!el) return;
        var html = extra || '';
        items.forEach(function (it) {
            if (typeof it === 'string') html += '<option value="' + it + '">' + it + '</option>';
            else html += '<option value="' + (it[valueKey] || it.codigo || it.nombre) + '">' + (it[labelKey] || it.nombre || it.codigo) + '</option>';
        });
        el.innerHTML = html;
    }

    function empresas() {
        var set = {};
        CC.state.centros.forEach(function (c) { if (c.empresa) set[c.empresa] = true; });
        CC.state.cuentas.forEach(function (c) { if (c.empresa) set[c.empresa] = true; });
        return Object.keys(set).sort();
    }

    function departamentos() {
        var set = {};
        CC.state.centros.forEach(function (c) { if (c.departamento) set[c.departamento] = true; });
        return Object.keys(set).sort();
    }

    /* ---------- Ciclos ---------- */
    CC.initCiclos = function () {
        bindPeriodoForm();
        bindEstadoPickers();
        var chips = document.getElementById('ciclo-estado-chips');
        if (chips && !CC._cicloChipsBound) {
            chips.innerHTML =
                '<button type="button" class="cc-chip is-active" data-val="">Todos</button>' +
                '<button type="button" class="cc-chip" data-val="abierto">Abierto</button>' +
                '<button type="button" class="cc-chip" data-val="en_revision">En revisión</button>' +
                '<button type="button" class="cc-chip" data-val="cerrado">Cerrado</button>';
            chips.querySelectorAll('.cc-chip').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    chips.setAttribute('data-value', btn.getAttribute('data-val') || '');
                    chips.querySelectorAll('.cc-chip').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                    renderCiclos();
                });
            });
            var q = document.getElementById('ciclo-q');
            if (q) q.addEventListener('input', renderCiclos);
            CC._cicloChipsBound = true;
        }
        var gridEl = document.getElementById('ciclo-grid');
        if (gridEl && !CC._cicloDelBound) {
            gridEl.addEventListener('click', function (e) {
                var btn = e.target.closest ? e.target.closest('[data-del-ciclo]') : null;
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    CC.eliminarCiclo(btn.getAttribute('data-del-ciclo'));
                    return;
                }
                if (e.target.closest && e.target.closest('a, button, select, .cc-estado-pick')) return;
                if (CC._skipCardOpen && (Date.now() - CC._skipCardOpen) < 500) return;
                var card = e.target.closest ? e.target.closest('.cc-ciclo-card[data-ciclo]') : null;
                if (!card) return;
                window.location = cicloUrl(card.getAttribute('data-ciclo'));
            });
            gridEl.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                if (e.target.closest && e.target.closest('a, button, select, .cc-estado-pick')) return;
                var card = e.target.closest ? e.target.closest('.cc-ciclo-card[data-ciclo]') : null;
                if (!card) return;
                e.preventDefault();
                window.location = cicloUrl(card.getAttribute('data-ciclo'));
            });
            CC._cicloDelBound = true;
        }
        renderCiclos();
    };

    function renderCiclos() {
        var list = CC.state.ciclos || [];
        var abiertos = list.filter(function (c) { return normalizeCicloEstado(c.estado) === 'abierto'; }).length;
        var rev = list.filter(function (c) { return normalizeCicloEstado(c.estado) === 'en_revision'; }).length;
        var cerrados = list.filter(function (c) { return normalizeCicloEstado(c.estado) === 'cerrado'; }).length;
        setText('kpi-ciclos', list.length);
        setText('kpi-ciclos-abiertos', abiertos);
        setText('kpi-ciclos-revision', rev);
        setText('kpi-ciclos-cerrados', cerrados);

        var q = (val('ciclo-q') || '').toLowerCase();
        var st = (document.getElementById('ciclo-estado-chips') || {}).getAttribute
            ? document.getElementById('ciclo-estado-chips').getAttribute('data-value')
            : '';
        var rows = list.filter(function (c) {
            if (st && normalizeCicloEstado(c.estado) !== st) return false;
            var blob = (c.codigo + ' ' + c.nombre + ' ' + (c.observaciones || '')).toLowerCase();
            return !q || blob.indexOf(q) !== -1;
        });
        var grid = document.getElementById('ciclo-grid');
        if (!grid) return;
        if (!rows.length) {
            grid.innerHTML = '<div class="cc-empty" style="grid-column:1/-1"><i class="fa-solid fa-layer-group"></i>No hay ciclos con esos filtros. Abre uno nuevo para empezar.</div>';
            return;
        }
        grid.innerHTML = rows.map(function (c) {
            return '<article class="cc-ciclo-card is-pickable" data-ciclo="' + escapeHtml(c.codigo) + '" role="link" tabindex="0">' +
                '<div class="top"><div><div class="code">' + escapeHtml(c.codigo) + '</div><h3>' + escapeHtml(c.nombre) + '</h3></div>' +
                htmlEstadoSelect(c.codigo, c.estado) + '</div>' +
                '<div class="cc-ciclo-meta">' +
                    '<div><small>Año real</small><strong>' + (c.anioReferencia || '—') + '</strong></div>' +
                    '<div><small>Año presupuesto</small><strong>' + (c.anio || '—') + '</strong></div>' +
                    '<div><small>Ventana</small><strong>' + formatDate(c.inicio) + ' — ' + formatDate(c.fin) + '</strong></div>' +
                    '<div><small>Indicadores</small><strong>' + (c.inflacion || 0) + '% · USD $' + Number(c.tipoCambio || 0).toFixed(2) + '</strong></div>' +
                '</div>' +
                '<div class="cc-ciclo-obs">' + escapeHtml(c.observaciones || 'Sin observaciones') + '</div>' +
                '<div class="actions">' +
                    '<span class="cc-ciclo-load">' + resumenCargaCiclo(c) + '</span>' +
                    '<span style="display:flex;gap:.4rem">' +
                    '<button type="button" class="cc-btn cc-btn-danger" data-del-ciclo="' + escapeHtml(c.codigo) + '">Eliminar</button>' +
                    '<a class="cc-btn cc-btn-ink" href="' + cicloUrl(c.codigo) + '">Abrir</a>' +
                    '</span></div></article>';
        }).join('');
    }

    function htmlEstadoSelect(codigo, current) {
        var cur = normalizeCicloEstado(current);
        var s = CICLO_STATUS[cur];
        return '<div class="cc-estado-pick" data-estado="' + cur + '" data-estado-ciclo="' + escapeHtml(codigo) + '">' +
            '<button type="button" class="cc-estado-btn ' + s.cls + '" aria-haspopup="listbox" aria-expanded="false" title="Cambiar estado">' +
            '<span class="cc-estado-dot"></span><span class="cc-estado-label">' + s.label + '</span>' +
            '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i></button>' +
            '<div class="cc-estado-menu" role="listbox" hidden>' + estadoMenuHtml(cur) + '</div></div>';
    }

    function estadoMenuHtml(current) {
        var cur = normalizeCicloEstado(current);
        return Object.keys(CICLO_STATUS).map(function (id) {
            var st = CICLO_STATUS[id];
            return '<button type="button" class="cc-estado-opt' + (id === cur ? ' is-on' : '') + '" role="option" data-val="' + id + '" aria-selected="' + (id === cur ? 'true' : 'false') + '">' +
                '<span class="cc-estado-dot ' + st.cls + '"></span>' + st.label +
                (id === cur ? '<i class="fa-solid fa-check"></i>' : '') +
                '</button>';
        }).join('');
    }

    function paintEstadoPick(wrap, estado, codigo) {
        if (!wrap) return;
        var cur = normalizeCicloEstado(estado);
        var s = CICLO_STATUS[cur];
        wrap.setAttribute('data-estado', cur);
        if (codigo) wrap.setAttribute('data-estado-ciclo', codigo);
        var btn = wrap.querySelector('.cc-estado-btn');
        var label = wrap.querySelector('.cc-estado-label');
        var menu = wrap.querySelector('.cc-estado-menu');
        if (btn) {
            btn.className = 'cc-estado-btn ' + s.cls;
            btn.setAttribute('aria-expanded', wrap.classList.contains('is-open') ? 'true' : 'false');
        }
        if (label) label.textContent = s.label;
        if (menu) menu.innerHTML = estadoMenuHtml(cur);
    }

    function closeEstadoMenus(except) {
        document.querySelectorAll('.cc-estado-pick.is-open').forEach(function (el) {
            if (el === except) return;
            el.classList.remove('is-open');
            var btn = el.querySelector('.cc-estado-btn');
            var menu = el.querySelector('.cc-estado-menu');
            if (btn) btn.setAttribute('aria-expanded', 'false');
            if (menu) menu.hidden = true;
        });
    }

    function bindEstadoPickers() {
        if (CC._estadoPickBound) return;
        CC._estadoPickBound = true;
        document.addEventListener('click', function (e) {
            var opt = e.target.closest ? e.target.closest('.cc-estado-opt') : null;
            if (opt) {
                e.preventDefault();
                e.stopPropagation();
                var wrap = opt.closest('.cc-estado-pick');
                var val = opt.getAttribute('data-val');
                var code = (wrap && wrap.getAttribute('data-estado-ciclo')) || (CC.state.period && CC.state.period.codigo);
                closeEstadoMenus();
                CC._skipCardOpen = Date.now();
                if (code && val) CC.cambiarEstadoCiclo(code, val);
                return;
            }
            var btn = e.target.closest ? e.target.closest('.cc-estado-btn') : null;
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                var wrap = btn.closest('.cc-estado-pick');
                var open = wrap.classList.contains('is-open');
                closeEstadoMenus();
                if (!open) {
                    wrap.classList.add('is-open');
                    btn.setAttribute('aria-expanded', 'true');
                    var menu = wrap.querySelector('.cc-estado-menu');
                    if (menu) menu.hidden = false;
                }
                return;
            }
            closeEstadoMenus();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeEstadoMenus();
        });
    }

    function applyCicloEstado(codigo, estado, fromApi) {
        var found = findCiclo(codigo);
        if (found) {
            found.estado = estado;
            if (fromApi) Object.assign(found, fromApi);
            upsertCiclo(found);
        }
        if (CC.state.period && String(CC.state.period.codigo || '').toUpperCase() === String(codigo || '').toUpperCase()) {
            CC.state.period = Object.assign({}, CC.state.period, fromApi || {}, { estado: estado });
            persistPeriod();
            renderPeriodBanner();
        }
        if (CC.state.page === 'admin') renderCiclos();
    }

    CC.cambiarEstadoCiclo = function (codigo, estado) {
        var c = findCiclo(codigo) || (CC.state.period && String(CC.state.period.codigo || '').toUpperCase() === String(codigo || '').toUpperCase() ? CC.state.period : null);
        if (!c || !CICLO_STATUS[estado]) return;
        if (normalizeCicloEstado(c.estado) === estado) return;
        var prev = c.estado;
        applyCicloEstado(codigo, estado);
        fetch('/CentrosCostos/api/ciclos/' + encodeURIComponent(codigo) + '/estado', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify({ estado: estado })
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok) throw new Error(json.message || 'No se pudo cambiar el estado');
                return json;
            });
        }).then(function (json) {
            applyCicloEstado(codigo, estado, json.ciclo || null);
            var label = (CICLO_STATUS[estado] && CICLO_STATUS[estado].label) || estado;
            toast('success', 'Estado actualizado', (c.codigo || codigo) + ' · ' + label);
        }).catch(function (err) {
            applyCicloEstado(codigo, prev);
            toast('error', 'No se cambió el estado', err && err.message ? err.message : 'Error de red');
        });
    };

    function resumenCargaCiclo(c) {
        var a = Number(c.asignaciones || 0);
        var ctas = Number(c.cuentas || 0);
        var us = Number(c.usuarios || 0);
        var cc = Number(c.centros || 0);
        if (!a && !ctas) return 'Sin asignaciones todavía';
        var bits = [];
        if (a) bits.push(a + (a === 1 ? ' asignación' : ' asignaciones'));
        if (cc) bits.push(cc + (cc === 1 ? ' centro' : ' centros'));
        if (ctas) bits.push(ctas + (ctas === 1 ? ' cuenta' : ' cuentas'));
        if (us) bits.push(us + (us === 1 ? ' usuario' : ' usuarios'));
        return bits.join(' · ');
    }

    function htmlEliminarCiclo(c) {
        var a = Number(c.asignaciones || 0);
        var ctas = Number(c.cuentas || 0);
        var us = Number(c.usuarios || 0);
        var cc = Number(c.centros || 0);
        var head = 'Se eliminará el budget <strong>' + escapeHtml(c.codigo) + '</strong> (' + escapeHtml(c.nombre || '') + ').';
        if (!a && !ctas) {
            return head + '<p style="margin:.75rem 0 0">No tiene asignaciones cargadas. Solo se borra el ciclo.</p>';
        }
        return head + '<p style="margin:.75rem 0 .35rem">También se borra lo que ya tiene cargado:</p><ul style="text-align:left;margin:.2rem 0 0;padding-left:1.2rem">' +
            (a ? '<li>' + a + (a === 1 ? ' asignación' : ' asignaciones') + '</li>' : '') +
            (cc ? '<li>' + cc + (cc === 1 ? ' centro de costos' : ' centros de costo') + '</li>' : '') +
            (ctas ? '<li>' + ctas + (ctas === 1 ? ' cuenta' : ' cuentas') + '</li>' : '') +
            (us ? '<li>' + us + (us === 1 ? ' usuario asignado' : ' usuarios asignados') + '</li>' : '') +
            '</ul>';
    }

    function purgeCicloLocal(codigo) {
        CC.state.ciclos = (CC.state.ciclos || []).filter(function (c) {
            return String(c.codigo || '').toUpperCase() !== String(codigo || '').toUpperCase();
        });
        saveJSON(SK.ciclos, CC.state.ciclos);
        try {
            localStorage.removeItem(SK.admin + ':' + codigo);
            localStorage.removeItem(SK.budget + ':' + codigo);
        } catch (err) {}
    }

    CC.eliminarCiclo = function (codigo) {
        var c = findCiclo(codigo) || { codigo: codigo, nombre: codigo, asignaciones: 0, cuentas: 0, usuarios: 0, centros: 0 };
        var go = function () {
            return fetch('/CentrosCostos/api/ciclos/' + encodeURIComponent(c.codigo), {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken()
                }
            }).then(function (res) {
                return res.json().then(function (json) {
                    if (!res.ok) throw new Error(json.message || 'No se pudo eliminar');
                    return json;
                });
            }).then(function () {
                purgeCicloLocal(c.codigo);
                toast('success', 'Budget eliminado', c.codigo);
                if (CC.state.page === 'admin-ciclo' || CC.state.page === 'asignacion') {
                    window.location = '/AdminCentros';
                    return;
                }
                renderCiclos();
            }).catch(function (err) {
                toast('error', 'No se eliminó', err && err.message ? err.message : 'Error de red');
            });
        };
        if (window.Swal) {
            Swal.fire({
                icon: 'warning',
                title: 'Eliminar budget',
                html: htmlEliminarCiclo(c),
                showCancelButton: true,
                confirmButtonColor: '#b91c1c',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Eliminar'
            }).then(function (r) { if (r.isConfirmed) go(); });
            return;
        }
        if (window.confirm('¿Eliminar el budget ' + c.codigo + '?')) go();
    };

    /* ---------- Admin ---------- */
    CC.initAdmin = function () {
        var k = kpisAdmin();
        setText('kpi-centros', k.centros);
        setText('kpi-abiertos', k.abiertos);
        setText('kpi-revision', k.revision);
        setText('kpi-aceptados', k.aceptados);
        setText('kpi-usuarios', k.usuarios);
        renderPeriodBanner();
        renderEmpresaChips('admin-empresa-chips', renderAdminTable);
        fillSelect(document.getElementById('f-depto'), departamentos(), null, null, '<option value="">Todos los departamentos</option>');
        fillSelect(document.getElementById('f-estado'), Object.keys(STATUS).map(function (id) {
            return { codigo: id, nombre: STATUS[id].label };
        }), 'codigo', 'nombre', '<option value="">Todos los estados</option>');
        fillSelect(document.getElementById('f-usuario'), usuariosUnicos().map(function (n) {
            return { codigo: n, nombre: n };
        }), 'codigo', 'nombre', '<option value="">Todos los usuarios</option>');
        if (!CC._adminBound) {
            ['f-q', 'f-depto', 'f-estado', 'f-usuario'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.addEventListener('input', renderAdminTable);
                if (el && el.tagName === 'SELECT') el.addEventListener('change', renderAdminTable);
            });
            bindAdminModals();
            CC._adminBound = true;
        }
        fillSelect(document.getElementById('n-empresa'), empresas().map(function (e) { return { codigo: e, nombre: e }; }), 'codigo', 'nombre');
        fillSelect(document.getElementById('n-depto'), departamentos().concat(['AdminExp', 'FieldSellExp', 'DirectLabor', 'MfgOverhead']).filter(function (v, i, a) { return a.indexOf(v) === i; }).map(function (d) { return { codigo: d, nombre: d }; }), 'codigo', 'nombre');
        fillSelect(document.getElementById('n-usuario'), usuariosUnicos().map(function (n) { return { codigo: n, nombre: n }; }), 'codigo', 'nombre', '<option value="">Sin asignar</option>');
        renderAdminTable();
    };

    function usuariosUnicos() {
        var fromCentros = CC.state.centros.map(function (c) { return mergedCentro(c).usuario; });
        var fromUsers = (CC.state.usuarios || []).map(function (u) { return u.nombre; });
        return fromCentros.concat(fromUsers).filter(Boolean).filter(function (v, i, a) { return a.indexOf(v) === i; });
    }

    function renderEmpresaChips(id, onChange) {
        var box = document.getElementById(id);
        if (!box) return;
        var current = box.getAttribute('data-value') || '';
        var html = '<button type="button" class="cc-chip' + (current === '' ? ' is-active' : '') + '" data-val="">Todas</button>';
        empresas().forEach(function (e) {
            html += '<button type="button" class="cc-chip' + (current === e ? ' is-active' : '') + '" data-val="' + e + '">' + e + '</button>';
        });
        box.innerHTML = html;
        box.querySelectorAll('.cc-chip').forEach(function (btn) {
            btn.addEventListener('click', function () {
                box.setAttribute('data-value', btn.getAttribute('data-val') || '');
                renderEmpresaChips(id, onChange);
                onChange();
            });
        });
    }

    function renderPeriodBanner() {
        var p = CC.state.period || {};
        setText('period-nombre', p.nombre || ('Presupuesto ' + (p.anio || 2027)));
        setText('period-codigo', p.codigo || '—');
        setText('period-anio-ref', p.anioReferencia || 2026);
        setText('period-anio', p.anio || 2027);
        setText('period-rango', formatDate(p.inicio) + ' — ' + formatDate(p.fin));
        var wrap = document.getElementById('period-estado-pick');
        if (wrap) {
            paintEstadoPick(wrap, p.estado, p.codigo || '');
            bindEstadoPickers();
        } else {
            var estadoEl = document.getElementById('period-estado');
            if (estadoEl) {
                var s = CICLO_STATUS[normalizeCicloEstado(p.estado)] || CICLO_STATUS.abierto;
                estadoEl.textContent = s.label;
                estadoEl.className = 'cc-badge ' + s.cls;
            }
        }
        setText('period-fx', (p.inflacion || 0) + '% infl. · USD $' + Number(p.tipoCambio || 0).toFixed(2));
        setText('th-gasto', 'Gasto ' + (p.anioReferencia || 2026));
        setText('th-ppto', 'Ppto ' + (p.anio || 2027));
        setText('ind-inflacion', (p.inflacion || 0) + ' %');
        setText('ind-tc', '$ ' + Number(p.tipoCambio || 0).toFixed(2));
        setText('ind-anio-ref', p.anioReferencia || 2026);
        setText('ind-anio', p.anio || 2027);
        var kicker = document.querySelector('.cc-kicker');
        if (kicker && CC.state.page === 'admin-ciclo') {
            kicker.innerHTML = 'Ciclo ' + escapeHtml(p.codigo || '') + ' · centros y permisos';
        }
    }

    function formatDate(iso) {
        if (!iso) return '—';
        var p = String(iso).split('-');
        if (p.length !== 3) return iso;
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    function renderAdminTable() {
        var q = (val('f-q') || '').toLowerCase();
        var emp = (document.getElementById('admin-empresa-chips') || {}).getAttribute ? document.getElementById('admin-empresa-chips').getAttribute('data-value') : '';
        var depto = val('f-depto');
        var st = val('f-estado');
        var user = val('f-usuario');
        var rows = CC.state.centros.map(mergedCentro).filter(function (c) {
            if (emp && c.empresa !== emp) return false;
            if (depto && c.departamento !== depto) return false;
            if (st && c.estado !== st) return false;
            if (user && c.usuario !== user) return false;
            var blob = (c.codigo + ' ' + c.nombre + ' ' + c.usuario + ' ' + c.empresa).toLowerCase();
            return !q || blob.indexOf(q) !== -1;
        });
        var tb = document.getElementById('admin-tbody');
        if (!tb) return;
        if (!rows.length) {
            tb.innerHTML = '<tr><td colspan="9"><div class="cc-empty"><i class="fa-solid fa-inbox"></i>Sin centros con esos filtros</div></td></tr>';
            return;
        }
        tb.innerHTML = rows.map(function (c) {
            var ctas = cuentasDeCentro(c);
            var ppto = totPptoCentro(c, ctas);
            var gasto = totGastoCentro(c, ctas) || c.gasto2026 || 0;
            return '<tr data-key="' + centroKey(c) + '">' +
                '<td><div class="fw-semibold">' + c.codigo + ' — ' + escapeHtml(c.nombre) + '</div><div class="text-muted" style="font-size:.75rem">' + ctas.length + ' cuentas · ' + (c.sap ? 'SAP' : 'catálogo') + '</div></td>' +
                '<td>' + escapeHtml(c.empresa) + '</td>' +
                '<td>' + escapeHtml(c.departamento || '—') + '</td>' +
                '<td>' + userCell(c.usuario) + '</td>' +
                '<td>' + renderBadge(c.estado) + '</td>' +
                '<td>' + (c.modo === 'solo_revision' ? '<span class="cc-badge cc-badge-solo_revision">Solo revisar</span>' : '<span class="cc-badge cc-badge-captura">Captura</span>') + '</td>' +
                '<td class="num">' + money(gasto) + '</td>' +
                '<td class="num">' + money(ppto) + '</td>' +
                '<td><div class="cc-row-actions">' +
                    '<button class="cc-icon-btn" data-act="detalle" title="Detalle"><i class="fa-solid fa-eye"></i></button>' +
                    '<button class="cc-icon-btn" data-act="cuentas" title="Cuentas"><i class="fa-solid fa-list"></i></button>' +
                    '<button class="cc-icon-btn" data-act="permisos" title="Permisos"><i class="fa-solid fa-user-lock"></i></button>' +
                    '<a class="cc-icon-btn" href="/ControlCentros?empresa=' + encodeURIComponent(c.empresa) + '&cc=' + encodeURIComponent(c.codigo) + '&ciclo=' + encodeURIComponent(CC.state.period.codigo || '') + '" title="Presupuestar"><i class="fa-solid fa-pen-to-square"></i></a>' +
                '</div></td></tr>';
        }).join('');
        tb.querySelectorAll('[data-act]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tr = btn.closest('tr');
                var centro = findCentro(tr.getAttribute('data-key'));
                if (!centro) return;
                var act = btn.getAttribute('data-act');
                if (act === 'detalle') openDetalle(centro);
                if (act === 'cuentas') openCuentas(centro);
                if (act === 'permisos') openPermisos(centro);
            });
        });
    }

    function totGastoCentro(c, ctas) {
        return (ctas || cuentasDeCentro(c)).reduce(function (a, cta) { return a + sum(cta.gasto); }, 0);
    }

    function totPptoCentro(c, ctas) {
        return (ctas || cuentasDeCentro(c)).reduce(function (a, cta) {
            return a + sum(pptoDe(c.empresa, c.codigo, cta, cta.gasto));
        }, 0);
    }

    function findCentro(key) {
        return CC.state.centros.map(mergedCentro).filter(function (c) { return centroKey(c) === key; })[0];
    }

    function userCell(name) {
        if (!name) return '<span class="text-muted">Sin asignar</span>';
        return '<span class="cc-user"><span class="cc-avatar">' + initials(name) + '</span>' + escapeHtml(name) + '</span>';
    }

    function bindPeriodoForm() {
        var formPeriod = document.getElementById('form-periodo');
        if (!formPeriod || CC._periodoBound) return;
        formPeriod.addEventListener('submit', function (e) {
            e.preventDefault();
            var data = leerFormCiclo();
            if (!data.codigo || !data.nombre) return;
            var btn = document.getElementById('btn-guardar-ciclo');
            if (btn) btn.disabled = true;
            saveCicloApi(data).then(function (ciclo) {
                CC.state.period = Object.assign({}, CC.state.period, data, ciclo || {});
                persistPeriod();
                hideModal('modalPeriodo');
                if (CC.state.page === 'admin') {
                    toast('success', 'Ciclo abierto', data.codigo + ' · ' + data.nombre);
                    window.location = cicloUrl(data.codigo);
                    return;
                }
                loadOverlaysForCycle();
                renderPeriodBanner();
                toast('success', 'Ciclo actualizado', data.codigo + ' · ' + data.nombre);
            }).catch(function (err) {
                toast('error', 'No se guardó el ciclo', err && err.message ? err.message : 'Error de red');
            }).then(function () {
                if (btn) btn.disabled = false;
            });
        });
        CC._periodoBound = true;
    }

    function bindAdminModals() {
        bindPeriodoForm();
        var formNuevo = document.getElementById('form-nuevo-centro');
        if (formNuevo) {
            fillSelect(document.getElementById('n-empresa'), empresas().map(function (e) { return { codigo: e, nombre: e }; }), 'codigo', 'nombre');
            fillSelect(document.getElementById('n-depto'), departamentos().concat(['AdminExp', 'FieldSellExp', 'DirectLabor', 'MfgOverhead']).filter(function (v, i, a) { return a.indexOf(v) === i; }).map(function (d) { return { codigo: d, nombre: d }; }), 'codigo', 'nombre');
            fillSelect(document.getElementById('n-usuario'), usuariosUnicos().map(function (n) { return { codigo: n, nombre: n }; }), 'codigo', 'nombre', '<option value="">Sin asignar</option>');
            formNuevo.addEventListener('submit', function (e) {
                e.preventDefault();
                var c = {
                    codigo: val('n-codigo'),
                    nombre: val('n-nombre'),
                    empresa: val('n-empresa'),
                    departamento: val('n-depto'),
                    usuario: val('n-usuario'),
                    estado: 'abierto',
                    modo: val('n-modo') || 'captura',
                    gasto2026: 0,
                    ppto2027: 0,
                    fecha: new Date().toLocaleDateString('es-MX')
                };
                CC.state.centros.unshift(c);
                persistOverlay(c, { estado: c.estado, usuario: c.usuario, modo: c.modo });
                renderAdminTable();
                CC.initAdmin();
                hideModal('modalNuevo');
                toast('success', 'Centro creado', c.codigo + ' — ' + c.nombre);
            });
        }
    }

    function openDetalle(c) {
        var ctas = cuentasDeCentro(c);
        var ppto = totPptoCentro(c, ctas);
        var gasto = totGastoCentro(c, ctas);
        var av = pct(ppto, gasto);
        document.getElementById('det-title').textContent = c.codigo + ' — ' + c.nombre;
        document.getElementById('det-body').innerHTML =
            '<div class="cc-context">' +
            item('Empresa', c.empresa) + item('Departamento', c.departamento) +
            item('Responsable', c.usuario || 'Sin asignar') + item('Estado', STATUS[normalizeEstado(c.estado)].label) +
            item('Modo', c.modo === 'solo_revision' ? 'Solo revisar' : 'Puede capturar') +
            item('Cuentas', ctas.length) +
            item('Gasto 2026', money(gasto)) + item('Ppto 2027', money(ppto)) +
            '</div>' +
            '<div class="mb-2 d-flex justify-content-between"><span class="text-muted">Avance vs gasto 2026</span><strong>' + av + '%</strong></div>' +
            '<div class="cc-progress ' + (av < 40 ? 'warn' : 'good') + '"><span style="width:' + Math.min(av, 100) + '%"></span></div>' +
            '<div class="mt-3 d-flex gap-2 flex-wrap">' +
            '<a class="cc-btn cc-btn-ink" href="/ControlCentros?empresa=' + encodeURIComponent(c.empresa) + '&cc=' + encodeURIComponent(c.codigo) + '&ciclo=' + encodeURIComponent((CC.state.period && CC.state.period.codigo) || '') + '">Ir a captura</a>' +
            '<a class="cc-btn" href="/AnalisisProgreso">Ver análisis</a></div>';
        showOffcanvas('offDetalle');
    }

    function item(k, v) {
        return '<div class="cc-context-item"><small>' + k + '</small><strong>' + escapeHtml(String(v)) + '</strong></div>';
    }

    function openCuentas(c) {
        CC._centroActivo = c;
        var asignadas = (overlayOf(c).cuentasAsignadas) || cuentasDeCentro(c).map(function (x) { return x.codigo; });
        var qInput = document.getElementById('cta-q');
        function draw() {
            var q = (qInput.value || '').toLowerCase();
            var list = CC.state.cuentas.filter(function (cta) {
                return (cta.codigo + ' ' + cta.nombre + ' ' + (cta.grupo || '')).toLowerCase().indexOf(q) !== -1;
            });
            document.getElementById('cta-list').innerHTML = list.map(function (cta) {
                var on = asignadas.indexOf(cta.codigo) !== -1;
                return '<label><input type="checkbox" value="' + cta.codigo + '"' + (on ? ' checked' : '') + '>' +
                    '<span><strong>' + codigoCuentaVisible(cta.codigo) + '</strong> ' + escapeHtml(cta.nombre) +
                    '<small>' + (cta.grupo || 'Sin agrupación') + (cta.empresa ? ' · ' + cta.empresa : '') + '</small></span></label>';
            }).join('') || '<div class="cc-empty">Sin cuentas</div>';
        }
        qInput.oninput = draw;
        draw();
        document.getElementById('cta-title').textContent = 'Cuentas · ' + c.codigo + ' ' + c.nombre;
        document.getElementById('form-cuentas').onsubmit = function (e) {
            e.preventDefault();
            var selected = Array.prototype.map.call(document.querySelectorAll('#cta-list input:checked'), function (i) { return i.value; });
            persistOverlay(c, { cuentasAsignadas: selected });
            hideModal('modalCuentas');
            renderAdminTable();
            toast('success', 'Cuentas asignadas', selected.length + ' cuentas en el catálogo del centro');
        };
        showModal('modalCuentas');
    }

    function openPermisos(c) {
        CC._centroActivo = c;
        document.getElementById('perm-title').textContent = 'Permisos · ' + c.codigo;
        fillSelect(document.getElementById('perm-usuario'), usuariosUnicos().map(function (n) { return { codigo: n, nombre: n }; }), 'codigo', 'nombre');
        document.getElementById('perm-usuario').value = c.usuario || '';
        document.getElementById('perm-estado').value = c.estado || 'abierto';
        document.getElementById('perm-modo').value = c.modo || 'captura';
        var perms = overlayOf(c).permisos || [{ usuario: c.usuario, capturar: true, editar: true, revisar: false }];
        function drawPerms() {
            document.getElementById('perm-list').innerHTML = perms.map(function (p, i) {
                return '<tr><td>' + userCell(p.usuario) + '</td>' +
                    '<td>' + chk(p.capturar) + ' Capturar</td>' +
                    '<td>' + chk(p.editar) + ' Editar</td>' +
                    '<td>' + chk(p.revisar) + ' Revisar</td>' +
                    '<td><button type="button" class="cc-icon-btn" data-i="' + i + '"><i class="fa-solid fa-xmark"></i></button></td></tr>';
            }).join('');
            document.querySelectorAll('#perm-list [data-i]').forEach(function (b) {
                b.onclick = function () { perms.splice(Number(b.getAttribute('data-i')), 1); drawPerms(); };
            });
        }
        function chk(v) { return v ? '<i class="fa-solid fa-check" style="color:#047857"></i>' : '<i class="fa-solid fa-minus" style="color:#a1a1aa"></i>'; }
        drawPerms();
        document.getElementById('perm-add').onclick = function () {
            var u = val('perm-add-user') || val('perm-usuario');
            if (!u) return;
            perms.push({
                usuario: u,
                capturar: document.getElementById('perm-cap').checked,
                editar: document.getElementById('perm-edit').checked,
                revisar: document.getElementById('perm-rev').checked
            });
            drawPerms();
        };
        fillSelect(document.getElementById('perm-add-user'), usuariosUnicos().map(function (n) { return { codigo: n, nombre: n }; }), 'codigo', 'nombre', '<option value="">Usuario</option>');
        document.getElementById('form-permisos').onsubmit = function (e) {
            e.preventDefault();
            persistOverlay(c, {
                usuario: val('perm-usuario'),
                estado: val('perm-estado'),
                modo: val('perm-modo'),
                permisos: perms
            });
            hideModal('modalPermisos');
            renderAdminTable();
            toast('success', 'Permisos guardados', 'El centro quedó asignado a ' + val('perm-usuario'));
        };
        showModal('modalPermisos');
    }

    /* ---------- Control ---------- */
    var control = { centro: null, cuenta: null, soloPendientes: false, grupoCerrado: {}, grupoCerradoDet: {}, vista: 'captura', scopeTabla: 'cuenta', chartVerTodas: false, _gastoLoading: false };

    function asignacionesDelCiclo(ciclo) {
        return (CC.state.misAsignaciones || []).filter(function (a) {
            return !ciclo || String(a.ciclo || '') === String(ciclo);
        });
    }

    function centrosDesdeAsignaciones(list) {
        return centrosAgrupadosDesdeAsignaciones(list);
    }

    function centrosAgrupadosDesdeAsignaciones(list) {
        var map = {};
        (list || []).forEach(function (a) {
            var emp = String(a.empresa || '').toUpperCase();
            var k = emp + '|' + String(a.centro_codigo || '');
            if (!map[k]) {
                map[k] = {
                    codigo: a.centro_codigo,
                    nombre: a.centro_nombre || a.centro_codigo,
                    empresa: emp,
                    usuario: a.usuario || '',
                    usuarios: [],
                    estado: 'abierto',
                    modo: (a.capturar || a.editar) ? 'captura' : 'solo_revision',
                    departamento: '',
                    ciclo: a.ciclo,
                    capturar: false,
                    editar: false,
                    revisar: false,
                    permisos: [],
                    cuentasN: 0,
                    sap: true
                };
            }
            var row = map[k];
            if (a.es_principal || !row.usuario) row.usuario = a.usuario || row.usuario;
            if (a.usuario && row.usuarios.indexOf(a.usuario) === -1) row.usuarios.push(a.usuario);
            if (a.centro_nombre) row.nombre = a.centro_nombre;
            row.capturar = row.capturar || !!a.capturar;
            row.editar = row.editar || !!a.editar;
            row.revisar = row.revisar || !!a.revisar;
            (a.permisos || []).forEach(function (p) {
                if (row.permisos.indexOf(p) === -1) row.permisos.push(p);
            });
            row.cuentasN += (a.cuentas || []).length;
            if (row.capturar || row.editar) row.modo = 'captura';
        });
        return Object.keys(map).map(function (k) {
            var row = map[k];
            row.modo = puedeEscribirAsig(row, row.ciclo) ? 'captura' : 'solo_revision';
            return row;
        });
    }

    function ciclosDeMisAsignaciones() {
        var seen = {};
        var out = [];
        (CC.state.misAsignaciones || []).forEach(function (a) {
            var code = String(a.ciclo || '');
            if (!code || seen[code]) return;
            seen[code] = true;
            var cyc = findCiclo(code) || {};
            out.push({
                codigo: code,
                nombre: cyc.nombre || code,
                estado: cyc.estado || 'abierto',
                inicio: cyc.inicio || '',
                fin: cyc.fin || '',
                capturaHasta: cyc.capturaHasta || '',
                anio: cyc.anio || '',
                anioReferencia: cyc.anioReferencia || ''
            });
        });
        return out;
    }

    function todayISO() {
        var d = new Date();
        var m = d.getMonth() + 1;
        var day = d.getDate();
        return d.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (day < 10 ? '0' : '') + day;
    }

    function scoreTemporadaCaptura(codigo) {
        var row = findCiclo(codigo) || {};
        var st = row.estado || '';
        var capturing = normalizeCicloEstado(st) === 'abierto';
        if (!capturing) return 0;
        var today = todayISO();
        var from = row.inicio || '';
        var to = row.capturaHasta || row.fin || '';
        if (from && to && today >= from && today <= to) return 3;
        if ((!from && !to) || (from && today < from) || (to && today <= to)) return 2;
        return 1;
    }

    function cicloEnTemporadaCaptura(codigo) {
        return scoreTemporadaCaptura(codigo) >= 2;
    }

    function cicloControlPreferido() {
        var mine = ciclosDeMisAsignaciones();
        var ini = CC.state.cicloInicial || '';
        if (ini && mine.filter(function (c) { return c.codigo === ini; }).length) return ini;
        var ranked = mine.slice().sort(function (a, b) {
            return scoreTemporadaCaptura(b.codigo) - scoreTemporadaCaptura(a.codigo);
        });
        return (ranked[0] && ranked[0].codigo) || '';
    }

    function setCapturaEnabled(on) {
        ['ctl-guardar', 'ctl-guardar-seguir', 'ctl-copy-year', 'ctl-clear-year', 'ctl-apply-infl', 'ctl-btn-dispersar', 'ctl-completar'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.disabled = !on;
        });
    }

    function cuentasEnriquecidas(c) {
        return cuentasDeCentro(c).map(function (cta) {
            var ppto = pptoDe(c.empresa, c.codigo, cta, cta.gasto);
            var totP = sum(ppto);
            return Object.assign({}, cta, {
                ppto: ppto,
                totG: sum(cta.gasto),
                totP: totP,
                listo: mesesTodosLlenos(ppto) || cuentaMarcada(c.empresa, c.codigo, cta.codigo)
            });
        });
    }

    function statsDeCentro(c) {
        var ctas = cuentasEnriquecidas(c);
        var pend = ctas.filter(ctaPendiente).length;
        return {
            total: ctas.length,
            capturadas: ctas.length - pend,
            pendientes: pend,
            totG: ctas.reduce(function (a, x) { return a + x.totG; }, 0),
            totP: ctas.reduce(function (a, x) { return a + x.totP; }, 0),
            avance: pct(ctas.length - pend, ctas.length || 1)
        };
    }

    function currentCta() {
        return (control._allCtas || []).filter(function (x) {
            return String(x.codigo) === String(control.cuenta || '');
        })[0] || null;
    }

    function scopedCtas(scopePref, list) {
        var ctas = list || control._allCtas || [];
        if (scopePref === 'cuenta' && control.cuenta) {
            return ctas.filter(function (x) {
                return String(x.codigo) === String(control.cuenta);
            });
        }
        return ctas;
    }

    function chartScopePref() {
        if (control.scopeTabla === 'centro' && control.chartVerTodas) return 'centro';
        if (control.cuenta) return 'cuenta';
        return 'centro';
    }

    function paintScopeToggle(id, current) {
        var box = document.getElementById(id);
        if (!box) return;
        box.querySelectorAll('[data-scope-val]').forEach(function (btn) {
            btn.classList.toggle('is-on', btn.getAttribute('data-scope-val') === current);
        });
    }

    function paintScopeHints() {
        var cta = currentCta();
        var hint = document.getElementById('ctl-detalle-hint');
        if (hint) {
            if (control.scopeTabla === 'cuenta' && cta) {
                hint.textContent = 'Solo la cuenta ' + labelNombreCodigo(cta.nombre, cta.codigo) + '. Cambia a Todo el centro para ver todas las cuentas.';
            } else if (control.scopeTabla === 'cuenta' && !control.cuenta) {
                hint.textContent = 'Elige una cuenta para filtrar el detalle, o cambia a Todo el centro para verlas todas.';
            } else if (control.chartVerTodas) {
                hint.textContent = 'Todas las cuentas del centro. La gráfica muestra el centro completo.';
            } else if (cta) {
                hint.textContent = 'Todas las cuentas del centro. La gráfica muestra ' + (cta.nombre || cta.codigo) + '. Marca Ver todas para ver el centro completo.';
            } else {
                hint.textContent = 'Todas las cuentas del centro. Elige una fila para verla en la gráfica, o marca Ver todas.';
            }
        }
        var wrap = document.getElementById('ctl-chart-todas-wrap');
        if (wrap) wrap.hidden = control.scopeTabla !== 'centro';
        var chk = document.getElementById('ctl-chart-todas');
        if (chk) chk.checked = !!control.chartVerTodas;
        ['ctl-chart-hint', 'det-chart-hint'].forEach(function (id) {
            var chartHint = document.getElementById(id);
            if (!chartHint) return;
            var base = 'Gasto ' + CC.state.anioGasto + ' vs presupuesto ' + CC.state.anioPresupuesto;
            if (chartScopePref() === 'cuenta' && cta) {
                chartHint.textContent = base + ' · ' + (cta.nombre || cta.codigo);
            } else {
                chartHint.textContent = base + ' · Todo el centro';
            }
        });
        paintScopeToggle('ctl-scope-tabla', control.scopeTabla);
    }

    function refreshAfterScope() {
        paintScopeHints();
        if (document.getElementById('ctl-tbody')) {
            renderControlTable();
            renderControlCharts();
        }
        if (document.getElementById('det-tbody')) {
            renderDetalleTable();
            renderDetalleChart();
        }
    }

    function bindScopeToggles() {
        if (CC._scopeBound) return;
        var box = document.getElementById('ctl-scope-tabla');
        if (box) {
            box.querySelectorAll('[data-scope-val]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    control.scopeTabla = btn.getAttribute('data-scope-val');
                    refreshAfterScope();
                });
            });
        }
        var chk = document.getElementById('ctl-chart-todas');
        if (chk) {
            chk.addEventListener('change', function () {
                control.chartVerTodas = this.checked;
                paintScopeHints();
                if (document.getElementById('chart-control')) renderControlCharts();
                if (document.getElementById('chart-detalle')) renderDetalleChart();
            });
        }
        CC._scopeBound = true;
    }

    function paintVisibleTableTotals(ctas, prefix) {
        prefix = prefix || 'ctl';
        if (!document.getElementById(prefix + '-tot-gasto')) return;
        if (!ctas) {
            setText(prefix + '-tot-gasto', '—');
            setText(prefix + '-tot-ppto', '—');
            setText(prefix + '-pend', '—');
            return;
        }
        var totG = ctas.reduce(function (a, x) { return a + (x.totG || 0); }, 0);
        var totP = ctas.reduce(function (a, x) { return a + (x.totP || 0); }, 0);
        var pend = ctas.filter(ctaPendiente).length;
        setText(prefix + '-tot-gasto', money(totG));
        setText(prefix + '-tot-ppto', money(totP));
        setText(prefix + '-pend', pend);
    }

    function renderMisCentros() {
        renderControlNav();
    }

    function renderControlNav() {
        renderNavEmpresas();
        renderNavCentros();
        renderNavCuentas();
    }

    function asigsPorEmpresa(ciclo) {
        var asigs = asignacionesDelCiclo(ciclo);
        var emps = {};
        asigs.forEach(function (a) {
            var e = String(a.empresa || '').toUpperCase();
            (emps[e] = emps[e] || []).push(a);
        });
        return emps;
    }

    function empresasAsignadas() {
        return Object.keys(asigsPorEmpresa(val('ctl-ciclo'))).sort();
    }

    function markPendLabel(n) {
        return n > 0 ? ('⚠  ' + n + (n === 1 ? ' pend' : ' pend')) : '✓  listo';
    }

    function paintNavStatus(id, n, selectEl) {
        var el = document.getElementById(id);
        var has = n !== null && n !== undefined && n !== '';
        if (el) {
            el.hidden = !has;
            if (has) {
                el.textContent = n > 0 ? (n + (n === 1 ? ' pendiente' : ' pendientes')) : 'Listo';
                el.className = 'cc-nav-status ' + (n > 0 ? 'is-warn' : 'is-ok');
            }
        }
        if (selectEl) {
            selectEl.classList.toggle('is-warn', has && n > 0);
            selectEl.classList.toggle('is-ok', has && n === 0);
        }
    }

    function pendientesDeEmpresa(emp) {
        var list = asigsPorEmpresa(val('ctl-ciclo'))[emp] || [];
        return list.reduce(function (n, a) {
            return n + statsDeCentro({
                codigo: a.centro_codigo,
                nombre: a.centro_nombre || a.centro_codigo,
                empresa: emp
            }).pendientes;
        }, 0);
    }

    function renderNavEmpresas() {
        var el = document.getElementById('ctl-empresa');
        if (!el) return;
        var keys = empresasAsignadas();
        var cur = String(el.value || '').toUpperCase();
        var html = '<option value="">Elige empresa…</option>';
        keys.forEach(function (e) {
            var label = e;
            if (CC._capturaReady) label += '   ·   ' + markPendLabel(pendientesDeEmpresa(e));
            html += '<option value="' + escapeHtml(e) + '">' + escapeHtml(label) + '</option>';
        });
        el.innerHTML = html;
        if (cur && keys.indexOf(cur) !== -1) el.value = cur;
        else if (CC.state.empresaInicial && keys.indexOf(String(CC.state.empresaInicial).toUpperCase()) !== -1) {
            el.value = String(CC.state.empresaInicial).toUpperCase();
        } else if (keys.length === 1) el.value = keys[0];
        else el.value = '';
        var sel = String(el.value || '').toUpperCase();
        paintNavStatus('ctl-emp-status', (CC._capturaReady && sel) ? pendientesDeEmpresa(sel) : null, el);
    }

    function renderNavCentros() {
        var el = document.getElementById('ctl-centro');
        if (!el) return;
        var emp = String(val('ctl-empresa') || '').toUpperCase();
        if (!emp) {
            el.innerHTML = '<option value="">Elige una empresa primero…</option>';
            el.disabled = true;
            el.classList.remove('is-warn', 'is-ok');
            paintNavStatus('ctl-cc-status', null, el);
            return;
        }
        var list = asigsPorEmpresa(val('ctl-ciclo'))[emp] || [];
        var cur = val('ctl-centro');
        var html = '<option value="">Elige centro…</option>';
        list.forEach(function (a) {
            var label = labelNombreCodigo(a.centro_nombre, a.centro_codigo);
            if (CC._capturaReady) {
                var st = statsDeCentro({
                    codigo: a.centro_codigo,
                    nombre: a.centro_nombre || a.centro_codigo,
                    empresa: emp
                });
                label += '   ·   ' + markPendLabel(st.pendientes);
            }
            html += '<option value="' + escapeHtml(a.centro_codigo) + '">' + escapeHtml(label) + '</option>';
        });
        el.innerHTML = html;
        el.disabled = !list.length;
        var exists = list.filter(function (a) { return String(a.centro_codigo) === String(cur); }).length;
        if (exists) el.value = cur;
        else if (CC.state.centroInicial && list.filter(function (a) { return String(a.centro_codigo) === String(CC.state.centroInicial); }).length) {
            el.value = CC.state.centroInicial;
        } else if (list.length === 1) el.value = list[0].centro_codigo;
        else el.value = '';
        var chosen = list.filter(function (a) { return String(a.centro_codigo) === String(el.value); })[0];
        paintNavStatus('ctl-cc-status', (CC._capturaReady && chosen) ? statsDeCentro({
            codigo: chosen.centro_codigo,
            nombre: chosen.centro_nombre,
            empresa: emp
        }).pendientes : null, el);
    }

    function renderNavCuentas() {
        var el = document.getElementById('ctl-cuenta');
        if (!el) return;
        var emp = val('ctl-empresa');
        var cc = val('ctl-centro');
        if (!emp || !cc) {
            el.innerHTML = '<option value="">Elige un centro primero…</option>';
            el.disabled = true;
            el.classList.remove('is-warn', 'is-ok');
            paintNavStatus('ctl-cta-status', null, el);
            return;
        }
        var centro = control.centro;
        var ctas = (control._allCtas && control._allCtas.length)
            ? control._allCtas
            : (centro ? cuentasEnriquecidas(centro) : []);
        if (control.soloPendientes) ctas = ctas.filter(ctaPendiente);
        var html = '<option value="">Elige cuenta…</option>';
        ctas.forEach(function (cta) {
            var pend = ctaPendiente(cta) ? 1 : 0;
            html += '<option value="' + escapeHtml(cta.codigo) + '">' +
                escapeHtml(labelNombreCodigo(cta.nombre, cta.codigo) + '   ·   ' + markPendLabel(pend)) + '</option>';
        });
        el.innerHTML = html;
        el.disabled = !ctas.length;
        if (control.cuenta && ctas.filter(function (x) { return String(x.codigo) === String(control.cuenta); }).length) {
            el.value = control.cuenta;
        } else {
            el.value = '';
        }
        var cta = ctas.filter(function (x) { return String(x.codigo) === String(el.value); })[0];
        paintNavStatus('ctl-cta-status', cta ? (ctaPendiente(cta) ? 1 : 0) : null, el);
    }

    function renderCicloPicks() {
        var el = document.getElementById('ctl-ciclo');
        if (!el) return;
        var ciclos = ciclosDeMisAsignaciones();
        var selected = el.value || cicloControlPreferido();
        if (!CC._cicloUserPicked) selected = cicloControlPreferido() || selected;
        el.innerHTML = ciclos.map(function (c) {
            var season = cicloEnTemporadaCaptura(c.codigo);
            var label = (c.codigo ? (c.codigo + ' — ') : '') + (c.nombre || c.codigo);
            if (season) label += ' · temporada';
            return '<option value="' + escapeHtml(c.codigo) + '">' + escapeHtml(label) + '</option>';
        }).join('') || '<option value="">Sin presupuestos</option>';
        if (selected) el.value = selected;
    }

    function applyControlPath(ciclo, emp, cc) {
        var cicloSel = document.getElementById('ctl-ciclo');
        var empEl = document.getElementById('ctl-empresa');
        var ccEl = document.getElementById('ctl-centro');
        var prevCiclo = val('ctl-ciclo');
        var prevEmp = val('ctl-empresa');
        var prevCc = val('ctl-centro');
        var cicloChanged = ciclo && String(ciclo) !== String(prevCiclo);
        if (cicloSel && ciclo) cicloSel.value = ciclo;
        function finishPath() {
            if (empEl && emp) empEl.value = emp;
            renderNavCentros();
            if (ccEl && cc) {
                ccEl.value = cc;
            } else if (ccEl && (cicloChanged || (emp && emp !== prevEmp))) {
                ccEl.value = '';
                control.cuenta = null;
            }
            var centroChanged = cicloChanged || val('ctl-empresa') !== prevEmp || val('ctl-centro') !== prevCc;
            if (centroChanged) control.cuenta = null;
            renderCicloPicks();
            renderControlNav();
            syncImportTools();
            loadControlCentro();
        }
        if (cicloChanged) {
            CC._cicloUserPicked = true;
            CC.state.cicloCodigo = ciclo;
            var period = findCiclo(ciclo);
            if (period) {
                CC.state.period = Object.assign({}, CC.state.period, period);
                if (period.anioReferencia) CC.state.anioGasto = period.anioReferencia;
                if (period.anio) CC.state.anioPresupuesto = period.anio;
            }
            CC.state.centros = centrosDesdeAsignaciones(asignacionesDelCiclo(ciclo));
            if (empEl && !emp) empEl.value = '';
            fillVisorFilters();
            loadOverlaysForCycle().then(finishPath);
            return;
        }
        finishPath();
    }

    CC.initControl = function () {
        if (!CC._asigLoaded) {
            fetch('/CentrosCostos/api/mis-asignaciones', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); }).then(function (json) {
                CC.state.misAsignaciones = json.asignaciones || [];
                CC._asigLoaded = true;
                CC.initControl();
            }).catch(function () {
                CC._asigLoaded = true;
                CC.state.misAsignaciones = CC.state.misAsignaciones || [];
                CC.initControl();
            });
            return;
        }

        var empty = document.getElementById('ctl-empty');
        var work = document.getElementById('ctl-work');
        var mineAll = CC.state.misAsignaciones || [];
        if (!mineAll.length) {
            if (empty) empty.hidden = false;
            if (work) work.hidden = true;
            setCapturaEnabled(false);
            return;
        }
        if (empty) empty.hidden = true;
        if (work) work.hidden = false;

        var cicloSel = document.getElementById('ctl-ciclo');
        renderCicloPicks();
        var ciclo = val('ctl-ciclo') || cicloControlPreferido();
        if (cicloSel && ciclo) cicloSel.value = ciclo;
        CC.state.cicloCodigo = ciclo || '';
        var period = findCiclo(ciclo);
        if (period) {
            CC.state.period = Object.assign({}, CC.state.period, period);
            if (period.anioReferencia) CC.state.anioGasto = period.anioReferencia;
            if (period.anio) CC.state.anioPresupuesto = period.anio;
        }
        CC.state.centros = centrosDesdeAsignaciones(asignacionesDelCiclo(ciclo));

        if (!CC._controlBound) {
            if (cicloSel) cicloSel.addEventListener('change', function () {
                CC._cicloUserPicked = true;
                control.cuenta = null;
                applyControlPath(this.value, '', '');
            });
            document.getElementById('ctl-empresa').addEventListener('change', function () {
                control.cuenta = null;
                applyControlPath(val('ctl-ciclo'), this.value, '');
            });
            document.getElementById('ctl-centro').addEventListener('change', function () {
                control.cuenta = null;
                applyControlPath(val('ctl-ciclo'), val('ctl-empresa'), this.value);
            });
            var ctaSel = document.getElementById('ctl-cuenta');
            if (ctaSel) ctaSel.addEventListener('change', function () {
                selectCuenta(this.value || null);
            });
            var qEl = document.getElementById('ctl-q');
            if (qEl) qEl.addEventListener('input', function () {
                renderCtaChips();
                renderControlTable();
            });
            document.getElementById('ctl-moneda').addEventListener('change', function () {
                applyCurrencyView();
            });
            var pend = document.getElementById('ctl-pendientes');
            if (pend) pend.addEventListener('change', function () {
                control.soloPendientes = this.checked;
                renderNavCuentas();
                renderCtaChips();
                renderControlTable();
            });
            bindControlTools();
            bindImportMasivo();
            bindCapturaQuick();
            bindVistas();
            bindVisor();
            bindScopeToggles();
            CC._controlBound = true;
        }
        var mon = document.getElementById('ctl-moneda');
        if (mon) mon.value = String(CC.state.currency || 'MXN').toUpperCase() === 'USD' ? 'USD' : 'MXN';
        paintCurrencyHint();
        if (CC.state.centroInicial) {
            var found = CC.state.centros.filter(function (c) { return String(c.codigo) === String(CC.state.centroInicial); })[0];
            if (found) {
                var empElIni = document.getElementById('ctl-empresa');
                var ccElIni = document.getElementById('ctl-centro');
                if (empElIni) empElIni.value = found.empresa;
                renderNavCentros();
                if (ccElIni) ccElIni.value = found.codigo;
            }
        }
        renderCicloPicks();
        renderMisCentros();
        syncImportTools();
        loadOverlaysForCycle().then(function () {
            renderControlNav();
            loadControlCentro();
            fillVisorFilters();
            renderVisorTable();
            setVista(control.vista === 'visor' || CC.state.vistaInicial === 'visor' ? 'visor' : 'captura');
        });
    };

    CC.initDetalle = function () {
        if (!CC._asigLoaded) {
            fetch('/CentrosCostos/api/mis-asignaciones', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); }).then(function (json) {
                CC.state.misAsignaciones = json.asignaciones || [];
                CC._asigLoaded = true;
                CC.initDetalle();
            }).catch(function () {
                CC._asigLoaded = true;
                CC.state.misAsignaciones = CC.state.misAsignaciones || [];
                CC.initDetalle();
            });
            return;
        }
        CC._cicloUserPicked = true;
        bindDetalleCurrency();
        var ciclo = CC.state.cicloInicial || cicloControlPreferido();
        renderCicloPicks();
        if (ciclo) {
            setVal('ctl-ciclo', ciclo);
            CC.state.cicloCodigo = ciclo;
            var period = findCiclo(ciclo);
            if (period) {
                CC.state.period = Object.assign({}, CC.state.period, period);
                if (period.anioReferencia) CC.state.anioGasto = period.anioReferencia;
                if (period.anio) CC.state.anioPresupuesto = period.anio;
            }
        }
        renderPeriodBanner();
        CC.state.centros = centrosDesdeAsignaciones(asignacionesDelCiclo(ciclo));
        setVal('ctl-empresa', CC.state.empresaInicial);
        setVal('ctl-centro', CC.state.centroInicial);
        control.scopeTabla = 'centro';
        control.chartVerTodas = false;
        bindScopeToggles();
        loadOverlaysForCycle().then(function () {
            loadControlCentro();
            paintDetalleHeader();
            renderDetalleTable();
            renderDetalleChart();
            paintScopeHints();
        });
    };

    function bindDetalleCurrency() {
        var mon = document.getElementById('ctl-moneda');
        if (mon) mon.value = CC.state.currency || 'MXN';
        if (CC._detalleBound) return;
        if (mon) {
            mon.addEventListener('change', function () {
                applyCurrencyView();
            });
        }
        CC._detalleBound = true;
    }

    function fillCentrosControl() {
        renderNavCentros();
    }

    function loadControlCentro() {
        paintCurrencyHint();
        var emp = val('ctl-empresa');
        var codigo = val('ctl-centro');
        if (!emp || !codigo) {
            control.centro = null;
            control.cuenta = null;
            control._allCtas = [];
            control._ctas = [];
            setCapturaEnabled(false);
            updateControlProgress();
            renderNavCuentas();
            renderCtaChips();
            renderCapturaForm();
            renderControlTable();
            renderControlCharts();
            renderVisorTable();
            return;
        }
        control.centro = CC.state.centros.map(mergedCentro).filter(function (c) {
            return c.codigo === codigo && c.empresa === emp;
        })[0] || null;
        if (!control.centro) {
            var tb = document.getElementById('ctl-tbody');
            if (tb) tb.innerHTML = '<tr><td colspan="28"><div class="cc-empty">No tienes centros de costo asignados en este ciclo. Pide a contabilidad una asignación.</div></td></tr>';
            setCapturaEnabled(false);
            control._allCtas = [];
            control._ctas = [];
            updateControlProgress();
            renderNavCuentas();
            renderCtaChips();
            renderCapturaForm();
            renderVisorTable();
            return;
        }
        var c = control.centro;
        var asig = asigDe(c) || c;
        var cicloCode = c.ciclo || val('ctl-ciclo') || CC.state.cicloCodigo || '';
        var locked = !puedeEscribirAsig(asig, cicloCode);
        control.locked = locked;
        setCapturaEnabled(!locked);
        var permBox = document.getElementById('ctl-perms');
        if (permBox) {
            var perms = (asig && asig.permisos) || c.permisos || [];
            permBox.innerHTML = perms.length
                ? perms.map(function (p) { return '<span class="cc-badge cc-badge-abierto">' + escapeHtml(p) + '</span> '; }).join('')
                : '<span class="text-muted">Sin permiso</span>';
        }
        loadGastoRealCentro(c);
        control._allCtas = cuentasEnriquecidas(c);
        if (control.cuenta && !control._allCtas.filter(function (x) { return String(x.codigo) === String(control.cuenta); }).length) {
            control.cuenta = null;
        }
        updateControlProgress();
        renderNavEmpresas();
        renderNavCentros();
        renderNavCuentas();
        renderCtaChips();
        renderCapturaForm();
        renderControlTable();
        renderControlCharts();
        renderVisorTable();
    }

    function updateControlProgress() {
        var c = control.centro;
        var box = document.getElementById('ctl-nav-pend');
        if (!c) {
            setText('ctl-progress-kicker', 'Centro de costo');
            setText('ctl-progress-title', 'Selecciona un centro');
            setText('kpi-ctl-avance', '—');
            setText('ctl-progress-meta', 'Elige un centro para ver el avance');
            setText('ctl-pend-label', '— pendientes');
            setText('ctl-nav-pend-n', '—');
            setText('ctl-nav-pend-sub', 'Elige un centro para ver cuántas cuentas faltan por presupuestar');
            if (box) box.className = 'cc-nav-pend';
            var bar0 = document.getElementById('ctl-avance-bar');
            var wrap0 = document.getElementById('ctl-avance-wrap');
            if (bar0) bar0.style.width = '0%';
            if (wrap0) wrap0.className = 'cc-progress warn';
            paintVisibleTableTotals(null);
            paintScopeHints();
            return;
        }
        var st = statsDeCentro(c);
        setText('ctl-progress-kicker', (c.ciclo || val('ctl-ciclo') || 'Presupuesto') + ' · ' + c.empresa);
        setText('ctl-progress-title', c.codigo + ' — ' + c.nombre);
        setText('kpi-ctl-avance', st.avance + '%');
        setText('ctl-progress-meta', st.capturadas + ' de ' + st.total + ' cuentas capturadas');
        setText('ctl-pend-label', st.pendientes + (st.pendientes === 1 ? ' pendiente' : ' pendientes'));
        setText('kpi-ctl-gasto', moneyGasto(st.totG));
        setText('kpi-ctl-ppto', money(st.totP));
        setText('kpi-ctl-pend', st.pendientes);
        var bar = document.getElementById('ctl-avance-bar');
        var wrap = document.getElementById('ctl-avance-wrap');
        if (bar) bar.style.width = Math.min(st.avance, 100) + '%';
        if (wrap) wrap.className = 'cc-progress ' + (st.pendientes ? 'warn' : 'good');
        setText('ctl-nav-pend-n', st.pendientes);
        setText('ctl-nav-pend-sub', st.total
            ? (st.pendientes
                ? ('De ' + st.total + ' cuentas asignadas a este centro')
                : ('Las ' + st.total + ' cuentas de este centro ya tienen presupuesto'))
            : 'Este centro no tiene cuentas asignadas');
        if (box) box.className = 'cc-nav-pend' + (st.pendientes ? '' : ' is-ok');
        paintScopeHints();
    }

    function renderCtaChips() {
        var box = document.getElementById('ctl-cta-chips');
        if (!box) return;
        var c = control.centro;
        if (!c) {
            box.innerHTML = '';
            return;
        }
        var q = (val('ctl-q') || '').toLowerCase();
        var ctas = (control._allCtas || []).filter(function (cta) {
            var blob = (cta.codigo + ' ' + cta.nombre + ' ' + (cta.grupo || '')).toLowerCase();
            return !q || blob.indexOf(q) !== -1;
        });
        if (control.soloPendientes) ctas = ctas.filter(ctaPendiente);
        box.innerHTML = ctas.map(function (cta) {
            var on = String(cta.codigo) === String(control.cuenta || '');
            var ok = !ctaPendiente(cta);
            return '<button type="button" class="cc-cta-chip' + (on ? ' is-on' : '') + (ok ? ' is-ok' : '') + '" data-cta="' + escapeHtml(cta.codigo) + '" title="' + escapeHtml(labelNombreCodigo(cta.nombre, cta.codigo)) + '">' +
                '<span class="dot"></span><span class="cc-cta-chip-name">' + escapeHtml(cta.nombre || cta.codigo) + '</span></button>';
        }).join('') || '<div class="text-muted" style="font-size:.8rem">Sin cuentas para mostrar</div>';
        box.querySelectorAll('[data-cta]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                selectCuenta(btn.getAttribute('data-cta'));
            });
        });
    }

    function selectCuenta(codigo) {
        control.cuenta = codigo || null;
        var el = document.getElementById('ctl-cuenta');
        if (el && String(el.value || '') !== String(control.cuenta || '')) {
            el.value = control.cuenta || '';
        }
        renderCtaChips();
        renderCapturaForm();
        renderControlTable();
        renderControlCharts();
        renderDetalleTable();
        renderDetalleChart();
        paintScopeHints();
        var cta = currentCta();
        if (control.centro && cta && cta.nombre && !nombreGastoCargado(cta.nombre)) {
            fetchGastoNombres(control.centro, [cta.nombre]);
        }
    }

    function renderCapturaForm() {
        var empty = document.getElementById('ctl-form-empty');
        var body = document.getElementById('ctl-form-body');
        var cta = currentCta();
        if (!control.centro || !cta) {
            if (empty) empty.hidden = false;
            if (body) body.hidden = true;
            return;
        }
        if (empty) empty.hidden = true;
        if (body) body.hidden = false;
        setText('ctl-form-grupo', cta.grupo || 'Cuenta');
        var cc = control.centro;
        paintNombreCodigo('ctl-form-cc', cc ? cc.nombre : '', cc ? cc.codigo : '');
        paintNombreCodigo('ctl-form-cta', cta.nombre, cta.codigo);
        paintFormPresupuestoLabel();
        paintFormEstado(cta);
        paintCompletarBtn(cta);
        updateFormTotals(cta);
        drawMonthGrid(cta);
        paintCurrencyHint();
    }

    function nombrePresupuestoActual() {
        var p = CC.state.period || {};
        return p.nombre || p.codigo || ('Presupuesto ' + (p.anio || CC.state.anioPresupuesto || ''));
    }

    function paintFormPresupuestoLabel() {
        setText('ctl-form-ppto-label', nombrePresupuestoActual());
    }

    function paintFormEstado(cta) {
        var st = document.getElementById('ctl-form-estado');
        if (!st) return;
        var listo = !ctaPendiente(cta);
        st.textContent = listo ? (cta.totP > 0 ? 'Capturada' : 'Completada') : 'Pendiente';
        st.className = 'cc-badge ' + (listo ? 'cc-badge-aceptado' : 'cc-badge-en_proceso');
    }

    function paintCompletarBtn(cta) {
        var btn = document.getElementById('ctl-completar');
        if (!btn) return;
        if (!cta || control.locked) {
            btn.hidden = true;
            return;
        }
        btn.hidden = false;
        var on = !ctaPendiente(cta);
        btn.setAttribute('data-on', on ? '1' : '0');
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Completado';
    }

    function mesesCapturados(cta) {
        return (cta.ppto || []).reduce(function (n, v) { return n + (mesLleno(v) ? 1 : 0); }, 0);
    }

    function paintCtaFill(cta) {
        var filled = mesesCapturados(cta);
        var av = Math.round((filled / 12) * 100);
        setText('ctl-form-avance', av + '%');
        setText('ctl-form-avance-meta', filled + ' de 12 meses capturados');
        var bar = document.getElementById('ctl-form-avance-bar');
        var wrap = document.getElementById('ctl-form-avance-wrap');
        if (bar) bar.style.width = av + '%';
        if (wrap) wrap.className = 'cc-progress ' + (filled === 12 ? 'good' : 'warn');
    }

    function updateFormTotals(cta) {
        setText('ctl-form-gasto', nombreGastoCargado(cta.nombre) ? moneyGasto(cta.totG) : 'Cargando…');
        setText('ctl-form-ppto', money(cta.totP));
        var d = deltaPct(cta.totP, cta.totG);
        var el = document.getElementById('ctl-form-delta');
        if (el) el.textContent = !ctaPendiente(cta)
            ? (cta.totP ? ((d > 0 ? '+' : '') + d + '% vs ' + CC.state.anioGasto) : 'Completada en 0')
            : 'Aún sin capturar';
        paintCtaFill(cta);
    }

    function drawMonthGrid(cta) {
        var grid = document.getElementById('ctl-month-grid');
        if (!grid) return;
        grid.innerHTML = MONTHS.map(function (m, i) {
            var cls = monthCellClass(cta, i);
            var shown = inputAmount(cta.ppto[i]);
            var cur = currentCurrency();
            return '<div class="' + cls + '"><div class="m">' + m + ' <em>' + cur + '</em></div>' +
                '<div class="prev' + (Number(cta.gasto[i] || 0) || nombreGastoCargado(cta.nombre) ? '' : ' is-loading') + '">' + labelGastoMes(cta, i) + '</div>' +
                '<input type="number" step="0.01" min="0" data-cta="' + escapeHtml(cta.codigo) + '" data-m="' + i + '" value="' + shown + '" ' + (control.locked ? 'disabled' : '') + ' placeholder="0.00"></div>';
        }).join('');
        grid.querySelectorAll('input').forEach(function (inp) {
            inp.addEventListener('input', function () {
                var cell = inp.closest('.cc-month-cell');
                if (!cell) return;
                var fake = { gasto: cta.gasto, ppto: (cta.ppto || []).slice() };
                fake.ppto[Number(inp.getAttribute('data-m'))] = parseCaptureInput(inp.value);
                cell.className = monthCellClass(fake, Number(inp.getAttribute('data-m')));
            });
            inp.addEventListener('change', onMonthChange);
            inp.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter') { ev.preventDefault(); inp.blur(); }
            });
        });
    }

    function monthCellClass(cta, i) {
        var cls = 'cc-month-cell';
        var raw = cta.ppto && cta.ppto[i];
        if (!mesLleno(raw)) {
            cls += ' is-empty';
            return cls;
        }
        var val = Number(raw);
        if (cta.gasto[i] && val > cta.gasto[i] * 1.2) cls += ' is-over';
        else cls += ' is-ok';
        return cls;
    }

    function monthsFromGrid(cta, fillEmpty) {
        var c = control.centro;
        var months = (c && cta) ? pptoDe(c.empresa, c.codigo, cta, cta.gasto).slice() : [null, null, null, null, null, null, null, null, null, null, null, null];
        var grid = document.getElementById('ctl-month-grid');
        if (!grid) return months;
        grid.querySelectorAll('input[data-m]').forEach(function (inp) {
            var m = Number(inp.getAttribute('data-m'));
            var parsed = parseCaptureInput(inp.value);
            months[m] = parsed === null ? (fillEmpty ? 0 : null) : parsed;
        });
        return months;
    }

    function cuentaMesesVacios(months) {
        var faltan = [];
        (months || []).forEach(function (v, i) {
            if (!mesLleno(v)) faltan.push(MONTHS[i]);
        });
        return faltan;
    }

    function irSiguientePendiente(actualCodigo) {
        var pend = (control._allCtas || []).filter(function (x) {
            return ctaPendiente(x) && String(x.codigo) !== String(actualCodigo || control.cuenta || '');
        })[0];
        if (pend) {
            selectCuenta(pend.codigo);
            return true;
        }
        celebrarCentroCompleto();
        return false;
    }

    function celebrarCentroCompleto() {
        var c = control.centro || {};
        var n = (control._allCtas || []).length;
        var nombre = c.nombre || c.codigo || 'este centro';
        var cuentaTxt = n === 1 ? 'la única cuenta' : ('las ' + n + ' cuentas');
        var frases = [
            {
                title: '¡Lo lograste!',
                html: 'Cerraste ' + cuentaTxt + ' de <b>' + escapeHtml(nombre) + '</b>. Ese centro ya puede dormir tranquilo.'
            },
            {
                title: '¡Centro listo, crack!',
                html: '<b>' + escapeHtml(nombre) + '</b> quedó redondo: ' + cuentaTxt + ' presupuestadas. Contabilidad te lo va a agradecer.'
            },
            {
                title: '¡Misión cumplida!',
                html: 'Ni una cuenta pendiente en <b>' + escapeHtml(nombre) + '</b>. Hoy sí se siente el avance.'
            }
        ];
        var pick = frases[Math.floor(Math.random() * frases.length)];
        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: pick.title,
                html: pick.html,
                position: 'center',
                toast: false,
                width: '32rem',
                confirmButtonColor: '#0a0a0a',
                confirmButtonText: '¡Qué orgullo!',
                allowOutsideClick: true
            });
        } else {
            toast('success', pick.title, 'Ya no hay cuentas pendientes de presupuestar');
        }
    }

    function guardarCapturaActual() {
        var c = control.centro;
        var cta = currentCta();
        if (c && cta && !control.locked) {
            var months = monthsFromGrid(cta);
            var faltan = cuentaMesesVacios(months);
            if (faltan.length) {
                toast('warning', 'Faltan meses', 'Llena los 12 meses (el 0 sí cuenta) o pulsa Completado.');
                drawMonthGrid(Object.assign({}, cta, { ppto: months, totP: sum(months) }));
                return false;
            }
            persistBudget(c.empresa, c.codigo, cta.codigo, months, { completado: true });
        }
        if (c) persistOverlay(c, { estado: c.estado === 'abierto' ? 'en_proceso' : c.estado });
        renderVisorTable();
        return true;
    }

    function refreshControlAfterEdit(codigo, opts) {
        opts = opts || {};
        var c = control.centro;
        if (!c) return;
        control._allCtas = cuentasEnriquecidas(c);
        updateControlProgress();
        renderControlNav();
        renderCtaChips();
        renderControlTable();
        renderControlCharts();
        var cta = currentCta() || control._allCtas.filter(function (x) { return String(x.codigo) === String(codigo); })[0];
        if (cta) {
            paintFormEstado(cta);
            paintCompletarBtn(cta);
            updateFormTotals(cta);
            if (!opts.skipFormGrid) drawMonthGrid(cta);
        }
        renderVisorTable();
    }

    function renderControlTable() {
        fillMonthTable('ctl-thead', 'ctl-tbody', { readonly: false, groupKey: 'grupoCerrado', scope: control.scopeTabla });
    }

    function renderDetalleTable() {
        fillMonthTable('det-thead', 'det-tbody', { readonly: true, groupKey: 'grupoCerradoDet', scope: control.scopeTabla, selectable: true });
    }

    function fillMonthTable(theadId, tbodyId, opts) {
        opts = opts || {};
        var c = control.centro;
        var thead = document.getElementById(theadId);
        var tbody = document.getElementById(tbodyId);
        if (!thead || !tbody) return;
        if (!c) {
            tbody.innerHTML = '<tr><td colspan="28"><div class="cc-empty">Elige un centro para ver el detalle</div></td></tr>';
            if (tbodyId === 'ctl-tbody') paintVisibleTableTotals(null, 'ctl');
            if (tbodyId === 'det-tbody') paintVisibleTableTotals(null, 'det');
            return;
        }
        var q = opts.readonly ? '' : (val('ctl-q') || '').toLowerCase();
        var ctas = (control._allCtas || cuentasEnriquecidas(c)).filter(function (cta) {
            var blob = (cta.codigo + ' ' + cta.nombre + ' ' + (cta.grupo || '')).toLowerCase();
            return !q || blob.indexOf(q) !== -1;
        });
        if (opts.scope) ctas = scopedCtas(opts.scope, ctas);
        if (!opts.readonly && control.soloPendientes && !(opts.scope === 'cuenta' && control.cuenta)) {
            ctas = ctas.filter(ctaPendiente);
        }
        if (!opts.readonly) control._ctas = ctas;
        if (tbodyId === 'ctl-tbody') paintVisibleTableTotals(ctas, 'ctl');
        if (tbodyId === 'det-tbody') paintVisibleTableTotals(ctas, 'det');

        var groups = {};
        ctas.forEach(function (cta) {
            var g = cta.grupo || 'Sin agrupación';
            (groups[g] = groups[g] || []).push(cta);
        });
        var closedMap = control[opts.groupKey] || (control[opts.groupKey] = {});

        var head = '<tr><th class="sticky-col">Cuenta</th><th class="num">Gasto ' + CC.state.anioGasto + '</th><th class="num">Ppto ' + CC.state.anioPresupuesto + '</th><th class="num">Δ%</th>';
        MONTHS.forEach(function (m) { head += '<th class="num">' + m + ' ' + String(CC.state.anioGasto).slice(2) + '</th><th class="num">' + m + ' ' + String(CC.state.anioPresupuesto).slice(2) + '</th>'; });
        head += '</tr>';
        thead.innerHTML = head;

        var html = '';
        var hideGroups = opts.scope === 'cuenta' && control.cuenta && ctas.length <= 1;
        Object.keys(groups).forEach(function (g) {
            var gTotG = groups[g].reduce(function (a, x) { return a + x.totG; }, 0);
            var gTotP = groups[g].reduce(function (a, x) { return a + x.totP; }, 0);
            var closed = !!closedMap[g];
            if (!hideGroups) {
                html += '<tr class="cc-group-row" data-group="' + escapeHtml(g) + '"><td class="sticky-col" colspan="4"><i class="fa-solid fa-chevron-' + (closed ? 'right' : 'down') + ' me-1"></i>' + escapeHtml(g) + ' · ' + groups[g].length + ' cuentas</td>';
                html += '<td class="num" colspan="2">' + money(gTotG) + ' → ' + money(gTotP) + '</td><td colspan="22"></td></tr>';
            }
            if (!closed || hideGroups) {
                groups[g].forEach(function (cta) {
                    var d = deltaPct(cta.totP, cta.totG);
                    var dCls = d > 15 ? 'text-danger' : (d < 0 ? 'text-success' : 'text-muted');
                    var selected = String(cta.codigo) === String(control.cuenta || '');
                    var editing = (!opts.readonly || opts.selectable) && selected;
                    html += '<tr class="cc-result-row' + (editing ? ' is-editing' : '') + (!ctaPendiente(cta) ? ' is-done' : '') + '" data-cta="' + escapeHtml(cta.codigo) + '"><td class="sticky-col">' + htmlNombreCodigo(cta.nombre, cta.codigo) + '</td>';
                    html += '<td class="num">' + moneyGasto(cta.totG) + '</td><td class="num fw-semibold">' + money(cta.totP) + '</td>';
                    html += '<td class="num ' + dCls + '">' + (cta.totP ? ((d > 0 ? '+' : '') + d + '%') : '—') + '</td>';
                    for (var i = 0; i < 12; i++) {
                        var lleno = mesLleno(cta.ppto[i]);
                        var pCls = lleno
                            ? (cta.gasto[i] && cta.ppto[i] > cta.gasto[i] * 1.2 ? 'is-over' : 'is-ok')
                            : 'is-empty';
                        html += '<td class="num text-muted" style="font-size:.75rem">' + moneyGasto(cta.gasto[i] || 0) + '</td>';
                        html += '<td class="num fw-semibold ' + pCls + '" style="font-size:.78rem">' +
                            (lleno ? money(cta.ppto[i] || 0) : '—') + '</td>';
                    }
                    html += '</tr>';
                });
            }
        });
        var emptyMsg = opts.scope === 'cuenta' && control.cuenta
            ? 'No hay filas para esta cuenta con los filtros actuales'
            : 'Sin cuentas asignadas a este centro';
        tbody.innerHTML = html || '<tr><td colspan="28"><div class="cc-empty">' + emptyMsg + '</div></td></tr>';

        tbody.querySelectorAll('.cc-group-row').forEach(function (row) {
            row.addEventListener('click', function () {
                var g = row.getAttribute('data-group');
                closedMap[g] = !closedMap[g];
                fillMonthTable(theadId, tbodyId, opts);
            });
        });
        if (!opts.readonly || opts.selectable) {
            tbody.querySelectorAll('.cc-result-row').forEach(function (row) {
                row.addEventListener('click', function () {
                    selectCuenta(row.getAttribute('data-cta'));
                    if (!opts.readonly) {
                        var form = document.getElementById('ctl-form-body');
                        if (form && form.scrollIntoView) form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            });
        }
    }

    function onMonthChange(ev) {
        var inp = ev.target;
        var c = control.centro;
        var codigo = inp.getAttribute('data-cta');
        var m = Number(inp.getAttribute('data-m'));
        var cta = (control._allCtas || []).filter(function (x) { return String(x.codigo) === String(codigo); })[0];
        if (!c || !cta) return;
        var valN = parseCaptureInput(inp.value);
        var months = pptoDe(c.empresa, c.codigo, cta, cta.gasto);
        var wasEmpty = !mesesTodosLlenos(months) && months.every(function (x) { return !mesLleno(x); });
        months[m] = valN;
        persistBudget(c.empresa, c.codigo, codigo, months);

        var cell = inp.closest('.cc-month-cell');
        if (cell) {
            var fake = { gasto: cta.gasto, ppto: months };
            cell.className = monthCellClass(fake, m);
        }

        if (wasEmpty && valN > 0 && m === 0 && !control.locked) {
            if (window.Swal) {
                Swal.fire({
                    title: '¿Gasto fijo mensual?',
                    html: 'Capturaste <b>' + money(valN) + '</b> en enero para <b>' + escapeHtml(cta.nombre) + '</b>. ¿Lo replicamos en los 12 meses?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0a0a0a',
                    confirmButtonText: 'Sí, mantener mensual',
                    cancelButtonText: 'Solo enero'
                }).then(function (res) {
                    if (res.isConfirmed) {
                        persistBudget(c.empresa, c.codigo, codigo, months.map(function () { return valN; }));
                        toast('success', 'Gasto fijo aplicado', 'Los 12 meses quedaron en ' + money(valN));
                    }
                    refreshControlAfterEdit(codigo);
                });
                return;
            }
        }
        refreshControlAfterEdit(codigo);
    }

    function bindCapturaQuick() {
        var copy = document.getElementById('ctl-copy-year');
        if (copy) copy.addEventListener('click', function () {
            var c = control.centro;
            var cta = currentCta();
            if (!c || !cta || control.locked) return;
            persistBudget(c.empresa, c.codigo, cta.codigo, (cta.gasto || []).slice());
            refreshControlAfterEdit(cta.codigo);
            toast('success', 'Copiado', captureIsUsd()
                ? ('Se copió el gasto ' + CC.state.anioGasto + ' en USD (se guarda en pesos)')
                : ('Se copió el gasto ' + CC.state.anioGasto + ' a los 12 meses'));
        });
        var clear = document.getElementById('ctl-clear-year');
        if (clear) clear.addEventListener('click', function () {
            var c = control.centro;
            var cta = currentCta();
            if (!c || !cta || control.locked) return;
            persistBudget(c.empresa, c.codigo, cta.codigo, [null, null, null, null, null, null, null, null, null, null, null, null], { completado: false });
            refreshControlAfterEdit(cta.codigo);
            toast('success', 'Limpio', 'Se quitó el presupuesto de ' + cta.nombre);
        });
        var infl = document.getElementById('ctl-apply-infl');
        if (infl) infl.addEventListener('click', function () {
            var c = control.centro;
            var cta = currentCta();
            if (!c || !cta || control.locked) return;
            var inflPct = Number(CC.state.period.inflacion) || 4;
            var rate = 1 + (inflPct / 100);
            var months = (cta.gasto || []).map(function (v) { return round2((Number(v) || 0) * rate); });
            persistBudget(c.empresa, c.codigo, cta.codigo, months);
            refreshControlAfterEdit(cta.codigo);
            toast('success', 'Inflación aplicada', captureIsUsd()
                ? ('Gasto ' + CC.state.anioGasto + ' en USD × ' + inflPct + '% (se guarda en pesos)')
                : ('Gasto ' + CC.state.anioGasto + ' × ' + inflPct + '%'));
        });
        var completar = document.getElementById('ctl-completar');
        if (completar) completar.addEventListener('click', function () {
            var c = control.centro;
            var cta = currentCta();
            if (!c || !cta || control.locked) return;
            var months = monthsFromGrid(cta, true);
            persistBudget(c.empresa, c.codigo, cta.codigo, months, { completado: true });
            refreshControlAfterEdit(cta.codigo);
            toast('success', 'Completado', 'Los meses vacíos quedaron en 0');
        });
    }

    function deptoDeCentro(c) {
        if (c && c.departamento) return c.departamento;
        var emp = String((c && c.empresa) || '').toUpperCase();
        var code = String((c && c.codigo) || '');
        var sap = (CC.state.sapCentros || []).filter(function (s) {
            return String(s.codigo) === code && (!s.empresa || String(s.empresa).toUpperCase() === emp);
        })[0];
        if (sap && sap.departamento) return sap.departamento;
        var demo = demoCentros().filter(function (d) {
            return String(d.codigo) === code && (!emp || d.empresa === emp);
        })[0];
        return (demo && demo.departamento) || '—';
    }

    function rememberPageHeader() {
        if (control._pageHeader) return;
        control._pageHeader = {
            kicker: (document.getElementById('cc-page-kicker') || {}).textContent || '',
            title: (document.getElementById('cc-page-title') || {}).textContent || '',
            sub: (document.getElementById('cc-page-sub') || {}).textContent || ''
        };
    }

    function restorePageHeader() {
        var h = control._pageHeader;
        if (!h) return;
        setText('cc-page-kicker', h.kicker);
        setText('cc-page-title', h.title);
        setText('cc-page-sub', h.sub);
        var facts = document.getElementById('cc-detalle-facts');
        var sub = document.getElementById('cc-page-sub');
        if (facts) facts.hidden = true;
        if (sub) sub.hidden = false;
    }

    function factHtml(label, value, extra) {
        return '<div class="cc-detalle-fact"><small>' + label + '</small><strong>' + value + '</strong>' + (extra || '') + '</div>';
    }

    function paintDetalleHeader(centro) {
        var c = centro || control.centro;
        var facts = document.getElementById('cc-detalle-facts');
        var sub = document.getElementById('cc-page-sub');
        if (!c) {
            setText('cc-page-kicker', 'Detalle del centro');
            setText('cc-page-title', 'Centro de costos');
            if (sub) { sub.hidden = false; setText('cc-page-sub', 'Elige un centro desde el visor.'); }
            if (facts) facts.hidden = true;
            return;
        }
        var stt = statsDeCentro(c);
        var dep = deptoDeCentro(c);
        var barCls = stt.pendientes ? 'warn' : 'good';
        setText('cc-page-kicker', 'Detalle · ' + (c.empresa || '—') + (dep && dep !== '—' ? ' · ' + dep : '') + (c.codigo ? ' · ' + c.codigo : ''));
        var title = document.getElementById('cc-page-title');
        if (title) title.innerHTML = escapeHtml(c.nombre || c.codigo || 'Centro de costos');
        if (sub) sub.hidden = true;
        if (facts) {
            facts.hidden = false;
            facts.innerHTML =
                factHtml('Empresa', escapeHtml(c.empresa || '—')) +
                factHtml('Tot Gasto', money(stt.totG)) +
                factHtml('Tot Pres', money(stt.totP)) +
                factHtml('Usuario', escapeHtml(c.usuario || 'Sin asignar')) +
                factHtml('Fecha modif', escapeHtml(c.fecha || '—')) +
                factHtml('Progreso', stt.avance + '%',
                    '<div class="cc-visor-avance"><div class="meta"><span>' + stt.capturadas + '/' + stt.total + '</span></div>' +
                    '<div class="cc-progress ' + barCls + '"><span style="width:' + Math.min(stt.avance, 100) + '%"></span></div></div>') +
                '<div class="cc-detalle-fact"><small>Estado</small><div style="margin-top:.2rem">' + renderBadge(c.estado) + '</div></div>';
        }
    }

    function setVista(name) {
        if (name !== 'captura' && name !== 'visor') name = 'captura';
        control.vista = name;
        var cap = document.getElementById('vista-captura');
        var vis = document.getElementById('vista-visor');
        if (cap) cap.hidden = name !== 'captura';
        if (vis) vis.hidden = name !== 'visor';
        document.querySelectorAll('#ctl-vistas [data-vista]').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-vista') === name);
        });
        if (name === 'visor') renderVisorTable();
        if (name === 'captura') {
            renderControlTable();
            renderControlCharts();
        }
    }

    function bindVistas() {
        var nav = document.getElementById('ctl-vistas');
        if (!nav) return;
        nav.querySelectorAll('[data-vista]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setVista(btn.getAttribute('data-vista'));
            });
        });
    }

    function bindVisor() {
        var filtroBtn = document.getElementById('visor-filtro-btn');
        var filters = document.getElementById('visor-filters');
        if (filtroBtn && filters) {
            filtroBtn.addEventListener('click', function () {
                filters.hidden = !filters.hidden;
                filtroBtn.classList.toggle('is-on', !filters.hidden);
            });
        }
        ['visor-q', 'visor-empresa', 'visor-depto', 'visor-estado'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener(id === 'visor-q' ? 'input' : 'change', renderVisorTable);
        });
    }

    function fillVisorFilters() {
        var rows = (CC.state.centros || []).map(mergedCentro);
        var emps = [];
        var deptos = [];
        rows.forEach(function (c) {
            if (c.empresa && emps.indexOf(c.empresa) === -1) emps.push(c.empresa);
            var d = deptoDeCentro(c);
            if (d && d !== '—' && deptos.indexOf(d) === -1) deptos.push(d);
        });
        emps.sort();
        deptos.sort();
        fillSelect(document.getElementById('visor-empresa'), emps, null, null, '<option value="">Todas las empresas</option>');
        fillSelect(document.getElementById('visor-depto'), deptos, null, null, '<option value="">Todos los departamentos</option>');
        fillSelect(document.getElementById('visor-estado'), Object.keys(STATUS).map(function (id) {
            return { codigo: id, nombre: STATUS[id].label };
        }), 'codigo', 'nombre', '<option value="">Todos los estados</option>');
    }

    function visorRowsFiltradas() {
        var q = (val('visor-q') || '').toLowerCase();
        var emp = val('visor-empresa');
        var depto = val('visor-depto');
        var st = val('visor-estado');
        return (CC.state.centros || []).map(mergedCentro).filter(function (c) {
            var dep = deptoDeCentro(c);
            if (emp && c.empresa !== emp) return false;
            if (depto && dep !== depto) return false;
            if (st && c.estado !== st) return false;
            var blob = (c.codigo + ' ' + c.nombre + ' ' + (c.empresa || '') + ' ' + (c.usuario || '') + ' ' + dep).toLowerCase();
            return !q || blob.indexOf(q) !== -1;
        });
    }

    function visorTotales(rows) {
        var totG = 0, totP = 0, capturadas = 0, totalCtas = 0, listos = 0;
        (rows || []).forEach(function (c) {
            var s = statsDeCentro(c);
            totG += s.totG;
            totP += s.totP;
            capturadas += s.capturadas;
            totalCtas += s.total;
            if (!s.pendientes) listos++;
        });
        var n = (rows || []).length;
        return {
            n: n,
            totG: totG,
            totP: totP,
            capturadas: capturadas,
            totalCtas: totalCtas,
            listos: listos,
            pendientes: n - listos,
            avance: pct(capturadas, totalCtas || 1)
        };
    }

    function paintVisorResumen(tot) {
        setText('visor-avance', tot.n ? (tot.avance + '%') : '—');
        setText('visor-avance-meta', tot.n
            ? (tot.listos + ' de ' + tot.n + ' centros listos · ' + tot.capturadas + ' de ' + tot.totalCtas + ' cuentas capturadas')
            : 'No hay centros para sumar');
        var bar = document.getElementById('visor-avance-bar');
        var wrap = document.getElementById('visor-avance-wrap');
        if (bar) bar.style.width = (tot.n ? Math.min(tot.avance, 100) : 0) + '%';
        if (wrap) wrap.className = 'cc-progress ' + (tot.n && !tot.pendientes ? 'good' : 'warn');
        setText('visor-kpi-n', tot.n || '0');
        setText('visor-kpi-gasto', money(tot.totG));
        setText('visor-kpi-ppto', money(tot.totP));
        setText('visor-kpi-pend', tot.pendientes);
        var tf = document.getElementById('visor-tfoot');
        if (!tf) return;
        if (!tot.n) {
            tf.innerHTML = '';
            return;
        }
        tf.innerHTML = '<tr>' +
            '<td colspan="4">Totales · ' + tot.n + (tot.n === 1 ? ' centro' : ' centros') + '</td>' +
            '<td class="num">' + money(tot.totG) + '</td>' +
            '<td class="num">' + money(tot.totP) + '</td>' +
            '<td colspan="2"></td>' +
            '<td>' + tot.capturadas + '/' + tot.totalCtas + ' · ' + tot.avance + '%</td>' +
            '<td>' + tot.listos + ' listos</td>' +
            '</tr>';
    }

    function renderVisorTable() {
        var tb = document.getElementById('visor-tbody');
        if (!tb) return;
        var rows = visorRowsFiltradas();
        var tot = visorTotales(rows);
        paintVisorResumen(tot);
        if (!rows.length) {
            tb.innerHTML = '<tr><td colspan="10"><div class="cc-empty"><i class="fa-solid fa-inbox"></i>No hay centros con esos filtros</div></td></tr>';
            return;
        }
        tb.innerHTML = rows.map(function (c) {
            var stt = statsDeCentro(c);
            var dep = deptoDeCentro(c);
            var barCls = stt.pendientes ? 'warn' : 'good';
            var fecha = c.fecha || '—';
            return '<tr data-key="' + escapeHtml(centroKey(c)) + '">' +
                '<td><a class="cc-btn cc-btn-detalle" href="' + detalleHref(c) + '"><i class="fa-solid fa-eye"></i> Detalle</a></td>' +
                '<td>' + escapeHtml(c.empresa || '—') + '</td>' +
                '<td>' + htmlNombreCodigo(c.nombre, c.codigo) + '</td>' +
                '<td>' + escapeHtml(dep) + '</td>' +
                '<td class="num">' + money(stt.totG) + '</td>' +
                '<td class="num">' + money(stt.totP) + '</td>' +
                '<td>' + userCell(c.usuario) + '</td>' +
                '<td>' + escapeHtml(fecha) + '</td>' +
                '<td><div class="cc-visor-avance"><div class="meta"><span>' + stt.capturadas + '/' + stt.total + '</span><strong>' + stt.avance + '%</strong></div>' +
                    '<div class="cc-progress ' + barCls + '"><span style="width:' + Math.min(stt.avance, 100) + '%"></span></div></div></td>' +
                '<td>' + renderBadge(c.estado) + '</td></tr>';
        }).join('');
    }

    function detalleHref(c) {
        var base = CC.state.detalleUrl || '/ControlCentros/detalle';
        var ciclo = val('ctl-ciclo') || CC.state.cicloCodigo || '';
        return base + '?empresa=' + encodeURIComponent(c.empresa || '') +
            '&cc=' + encodeURIComponent(c.codigo || '') +
            '&ciclo=' + encodeURIComponent(ciclo);
    }

    function paintCurrencyHint() {
        var el = document.getElementById('ctl-fx-hint');
        if (!el) return;
        var tc = fxRate().toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
        if (captureIsUsd()) {
            el.textContent = 'Captura en USD · TC $' + tc + ' · se guarda en MXN';
        } else {
            el.textContent = 'Captura en MXN · TC USD $' + tc;
        }
    }

    function applyCurrencyView() {
        syncCurrencyState();
        saveJSON(SK.currency, CC.state.currency);
        paintCurrencyHint();
        paintDispMontoLabel();
        var cta = currentCta();
        if (cta) {
            updateFormTotals(cta);
            drawMonthGrid(cta);
        }
        updateControlProgress();
        renderControlTable();
        renderControlCharts();
        renderVisorTable();
        if (CC.state.page === 'detalle') {
            paintDetalleHeader();
            renderDetalleTable();
            renderDetalleChart();
        }
    }

    function paintDispMontoLabel() {
        var mismo = val('d-modo') === 'mismo';
        var label = document.getElementById('d-monto-label');
        var hint = document.getElementById('d-modo-hint');
        if (label) label.textContent = (mismo ? 'Monto mensual' : 'Monto total') + (captureIsUsd() ? ' (USD)' : ' (MXN)');
        if (hint) {
            hint.textContent = mismo
                ? 'Gasto fijo: se copia el mismo monto a cada mes del rango Desde–Hasta.'
                : 'El total se reparte entre los meses del rango.';
        }
    }

    function bindControlTools() {
        var formDisp = document.getElementById('form-dispersar');
        if (formDisp) {
            paintDispMontoLabel();
            var modoEl = document.getElementById('d-modo');
            if (modoEl) modoEl.addEventListener('change', paintDispMontoLabel);
            formDisp.addEventListener('submit', function (e) {
                e.preventDefault();
                var c = control.centro;
                var cta = currentCta();
                var codigo = cta ? cta.codigo : '';
                var monto = captureToMxn(val('d-monto')) || 0;
                var from = Number(val('d-desde')) || 0;
                var to = Number(val('d-hasta')) || 11;
                var modo = val('d-modo');
                if (!cta || to < from) return;
                var months = pptoDe(c.empresa, c.codigo, cta, cta.gasto);
                var n = to - from + 1;
                if (modo === 'igual') {
                    var each = round2(monto / n);
                    for (var i = from; i <= to; i++) months[i] = each;
                } else if (modo === 'mismo') {
                    for (var k = from; k <= to; k++) months[k] = round2(monto);
                } else {
                    var weights = cta.gasto.slice(from, to + 1);
                    var tw = sum(weights) || n;
                    for (var j = from; j <= to; j++) months[j] = round2(monto * ((cta.gasto[j] || 0) / tw));
                }
                persistBudget(c.empresa, c.codigo, codigo, months);
                hideModal('modalDispersar');
                control.cuenta = codigo;
                refreshControlAfterEdit(codigo);
                var rango = MONTHS[from] + '–' + MONTHS[to];
                toast('success', modo === 'mismo' ? 'Gasto fijo' : 'Costo dispersado', modo === 'mismo'
                    ? money(monto) + ' en cada mes (' + rango + ')'
                    : money(monto) + ' en ' + cta.nombre);
            });
        }
        var save = document.getElementById('ctl-guardar');
        if (save) save.addEventListener('click', function () {
            var codigo = control.cuenta;
            if (!guardarCapturaActual()) return;
            refreshControlAfterEdit(codigo);
            if (!(control._allCtas || []).some(ctaPendiente)) {
                celebrarCentroCompleto();
                return;
            }
            toast('success', 'Captura guardada', 'El presupuesto quedó en el servidor');
        });
        var saveNext = document.getElementById('ctl-guardar-seguir');
        if (saveNext) saveNext.addEventListener('click', function () {
            var codigo = control.cuenta;
            if (!guardarCapturaActual()) return;
            refreshControlAfterEdit(codigo);
            if (!irSiguientePendiente(codigo)) return;
            toast('success', 'Captura guardada', 'El presupuesto quedó en el servidor');
        });
    }

    function puedeImportarCiclo() {
        return asignacionesDelCiclo(cicloActualCodigo()).some(function (a) {
            return !!a.importar || (a.permisos || []).indexOf('importar') !== -1;
        });
    }

    function tieneEditarCiclo() {
        return asignacionesDelCiclo(cicloActualCodigo()).some(function (a) {
            return !!a.editar || (a.permisos || []).indexOf('editar') !== -1;
        });
    }

    function cicloPermitePlantilla() {
        return cicloEstadoDe(cicloActualCodigo()) === 'abierto' || tieneEditarCiclo();
    }

    function avisarPlantillaBloqueada() {
        var st = cicloEstadoDe(cicloActualCodigo());
        var label = (CICLO_STATUS[st] && CICLO_STATUS[st].label) || 'otro estado';
        if (window.Swal) {
            Swal.fire({
                icon: 'info',
                title: 'Plantillas bloqueadas',
                html: 'El budget está en <b>' + escapeHtml(label) + '</b>. Para bajar o subir plantilla en este estado necesitas permiso de <b>Editar</b> en algún centro.',
                position: 'center',
                width: '32rem',
                confirmButtonColor: '#0a0a0a',
                confirmButtonText: 'Entendido'
            });
        } else {
            toast('info', 'Plantillas bloqueadas', 'En este estado se necesita permiso de Editar');
        }
    }

    function syncImportTools() {
        var box = document.getElementById('ctl-import-tools');
        var puede = puedeImportarCiclo();
        if (box) box.hidden = !puede;
        var on = puede && cicloPermitePlantilla();
        ['ctl-plantilla', 'ctl-importar'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.disabled = !on;
            el.title = on ? '' : 'El budget no está en captura. Se desbloquea si tienes permiso de Editar en algún centro.';
        });
    }

    function bindImportMasivo() {
        var btnPlantilla = document.getElementById('ctl-plantilla');
        var btnImportar = document.getElementById('ctl-importar');
        var fileEl = document.getElementById('ctl-import-file');
        if (btnPlantilla) {
            btnPlantilla.addEventListener('click', function () {
                var ciclo = cicloActualCodigo();
                if (!ciclo || !puedeImportarCiclo()) return;
                if (!cicloPermitePlantilla()) {
                    avisarPlantillaBloqueada();
                    return;
                }
                window.location.href = '/CentrosCostos/api/captura/plantilla?ciclo=' + encodeURIComponent(ciclo);
            });
        }
        if (btnImportar && fileEl) {
            btnImportar.addEventListener('click', function () {
                if (!puedeImportarCiclo()) return;
                if (!cicloPermitePlantilla()) {
                    avisarPlantillaBloqueada();
                    return;
                }
                fileEl.value = '';
                fileEl.click();
            });
            fileEl.addEventListener('change', function () {
                var file = fileEl.files && fileEl.files[0];
                if (!file) return;
                if (!cicloPermitePlantilla()) {
                    avisarPlantillaBloqueada();
                    return;
                }
                var ciclo = cicloActualCodigo();
                var fd = new FormData();
                fd.append('ciclo', ciclo);
                fd.append('archivo', file);
                btnImportar.disabled = true;
                if (btnPlantilla) btnPlantilla.disabled = true;
                if (window.Swal) {
                    Swal.fire({
                        title: 'Subiendo plantilla…',
                        html: '<p style="margin:0 0 .55rem">Estamos leyendo <b>' + escapeHtml(file.name) + '</b></p>' +
                            '<p style="margin:0;color:#71717a">Guardando tus presupuestos. No cierres esta ventana.</p>',
                        position: 'center',
                        width: '32rem',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: function () { Swal.showLoading(); }
                    });
                }
                fetch('/CentrosCostos/api/captura/importar', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken()
                    },
                    body: fd
                }).then(function (r) {
                    return r.json().then(function (json) {
                        return { ok: r.ok, json: json };
                    });
                }).then(function (res) {
                    btnImportar.disabled = false;
                    syncImportTools();
                    var j = res.json || {};
                    if (!res.ok) {
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'No se importó',
                                html: escapeHtml((j.message) ? j.message : 'Error al leer el archivo'),
                                position: 'center',
                                width: '32rem',
                                confirmButtonColor: '#0a0a0a',
                                confirmButtonText: 'Entendido'
                            });
                        } else {
                            toast('error', 'No se importó', j.message || 'Error al leer el archivo');
                        }
                        return;
                    }
                    var n = Number(j.guardadas || 0);
                    var omit = Number(j.omitidas || 0);
                    var errN = Number(j.errores_total || 0);
                    var html = '<p style="margin:0 0 .65rem">Se actualizaron <b>' + n + '</b> ' + (n === 1 ? 'cuenta' : 'cuentas') + '.</p>';
                    if (omit) html += '<p style="margin:0 0 .65rem;color:#71717a">' + omit + ' filas se omitieron porque iban vacías.</p>';
                    if (j.errores && j.errores.length) {
                        html += j.errores.slice(0, 12).map(function (e) {
                            return '<div style="text-align:left;font-size:.9rem">Fila ' + e.fila + ': ' + escapeHtml(e.mensaje || '') + '</div>';
                        }).join('');
                        if (errN > j.errores.length) {
                            html += '<div style="text-align:left">… y ' + (errN - j.errores.length) + ' más</div>';
                        }
                    }
                    if (window.Swal) {
                        Swal.fire({
                            icon: errN && n ? 'warning' : (n ? 'success' : 'error'),
                            title: errN && n ? 'Importado con observaciones' : (n ? '¡Plantilla cargada!' : 'Nada se importó'),
                            html: html,
                            position: 'center',
                            width: '32rem',
                            confirmButtonColor: '#0a0a0a',
                            confirmButtonText: n ? '¡Listo!' : 'Entendido'
                        });
                    } else {
                        toast(n ? 'success' : 'error', n ? 'Importación lista' : 'Nada se importó', n + ' cuentas actualizadas');
                    }
                    loadOverlaysForCycle().then(function () {
                        loadControlCentro();
                        renderVisorTable();
                    });
                }).catch(function (err) {
                    btnImportar.disabled = false;
                    syncImportTools();
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se importó',
                            html: escapeHtml(err && err.message ? err.message : 'Error de red'),
                            position: 'center',
                            width: '32rem',
                            confirmButtonColor: '#0a0a0a',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        toast('error', 'No se importó', err && err.message ? err.message : 'Error de red');
                    }
                });
            });
        }
    }

    function renderControlCharts() {
        paintScopeHints();
        renderMonthChart('chart-control', 'chart-control', chartScopePref());
    }

    function renderDetalleChart() {
        paintScopeHints();
        renderMonthChart('chart-detalle', 'chart-detalle', chartScopePref());
    }

    function renderMonthChart(canvasId, chartKey, scopePref) {
        var canvas = document.getElementById(canvasId);
        if (!canvas || !window.Chart) return;
        var ctas = scopedCtas(scopePref, control._allCtas || []);
        var g = MONTHS.map(function (_, i) { return ctas.reduce(function (a, x) { return a + ((x.gasto && x.gasto[i]) || 0); }, 0); });
        var p = MONTHS.map(function (_, i) { return ctas.reduce(function (a, x) { return a + ((x.ppto && x.ppto[i]) || 0); }, 0); });
        var d = g.map(function (gv, i) { return deltaPct(p[i], gv); });
        if (CC.state.charts[chartKey]) CC.state.charts[chartKey].destroy();
        CC.state.charts[chartKey] = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: MONTHS,
                datasets: [
                    { type: 'bar', label: 'Gasto ' + CC.state.anioGasto, data: g, backgroundColor: '#0a0a0a', borderRadius: 4, yAxisID: 'y' },
                    { type: 'bar', label: 'Ppto ' + CC.state.anioPresupuesto, data: p, backgroundColor: '#a1a1aa', borderRadius: 4, yAxisID: 'y' },
                    { type: 'line', label: 'Contraste %', data: d, borderColor: '#b45309', backgroundColor: 'transparent', tension: 0.3, yAxisID: 'y1', pointRadius: 3, borderWidth: 2 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                if (ctx.dataset.yAxisID === 'y1') return ctx.dataset.label + ': ' + ctx.parsed.y + '%';
                                return ctx.dataset.label + ': ' + money(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { ticks: { callback: function (v) { return money(v); } }, grid: { color: '#f1f1f3' } },
                    y1: { position: 'right', grid: { display: false }, ticks: { callback: function (v) { return v + '%'; } } }
                }
            }
        });
    }

    /* ---------- Análisis ---------- */
    function cicloAnalisisPreferido() {
        var ciclos = CC.state.ciclos || [];
        var open = ciclos.filter(function (c) { return normalizeCicloEstado(c.estado) === 'abierto'; })[0];
        return (CC.state.cicloInicial || CC.state.cicloCodigo || (open && open.codigo) || (ciclos[0] && ciclos[0].codigo) || '');
    }

    function renderAnalisisCiclos() {
        var el = document.getElementById('an-ciclo');
        if (!el) return;
        var ciclos = CC.state.ciclos || [];
        var selected = el.value || cicloAnalisisPreferido();
        el.innerHTML = ciclos.map(function (c) {
            var label = (c.codigo ? (c.codigo + ' — ') : '') + (c.nombre || c.codigo);
            return '<option value="' + escapeHtml(c.codigo) + '">' + escapeHtml(label) + '</option>';
        }).join('') || '<option value="">Sin ciclos</option>';
        if (selected) el.value = selected;
    }

    function paintAnalisisYears() {
        var g = CC.state.anioGasto || 2026;
        var p = CC.state.anioPresupuesto || 2027;
        setText('an-kicker', 'Supervisión · presupuesto ' + p);
        setText('an-sub', 'Cuánto se lleva capturado por empresa, centro, cuenta o usuario, y dónde se pasó el límite vs el gasto ' + g + '.');
        setText('an-kpi-over-hint', 'Ppto ' + p + ' > 110% del gasto ' + g);
        setText('an-kpi-yoy-hint', 'Promedio de aumento vs ' + g);
        var title = document.getElementById('an-chart-emp-title');
        if (title) title.innerHTML = '<i class="fa-solid fa-chart-column"></i> Gasto ' + g + ' vs presupuesto ' + p;
        setText('an-th-gasto', 'Gasto ' + g);
        setText('an-th-ppto', 'Ppto ' + p);
        var months = document.getElementById('an-heat-months');
        if (months) months.innerHTML = MONTHS.map(function (m) { return '<span>' + m + '</span>'; }).join('');
    }

    function fillAnalisisFilters() {
        var rows = (CC.state.centros || []).map(mergedCentro);
        var emps = [];
        var users = [];
        rows.forEach(function (c) {
            if (c.empresa && emps.indexOf(c.empresa) === -1) emps.push(c.empresa);
            var names = (c.usuarios && c.usuarios.length) ? c.usuarios : (c.usuario ? [c.usuario] : []);
            names.forEach(function (n) {
                if (n && users.indexOf(n) === -1) users.push(n);
            });
        });
        emps.sort();
        users.sort();
        fillSelectKeep(document.getElementById('an-empresa'), emps, null, null, '<option value="">Todas las empresas</option>');
        fillSelectKeep(document.getElementById('an-user'), users.map(function (n) {
            return { codigo: n, nombre: n };
        }), 'codigo', 'nombre', '<option value="">Todos los usuarios</option>');
    }

    function fillSelectKeep(el, items, valueKey, labelKey, extra) {
        if (!el) return;
        var prev = el.value;
        fillSelect(el, items, valueKey, labelKey, extra);
        if (prev) el.value = prev;
    }

    function applyAnalisisCiclo(ciclo) {
        CC.state.cicloCodigo = ciclo || '';
        var period = findCiclo(ciclo);
        if (period) {
            CC.state.period = Object.assign({}, CC.state.period, period);
            if (period.anioReferencia) CC.state.anioGasto = period.anioReferencia;
            if (period.anio) CC.state.anioPresupuesto = period.anio;
        }
        paintAnalisisYears();
        CC._anCapturaCiclo = null;
        CC._anAsigLoaded = false;
        CC._anAsigLoading = false;
        CC.initAnalisis();
    }

    CC.initAnalisis = function () {
        renderAnalisisCiclos();
        paintAnalisisYears();
        if (!CC._analisisBound) {
            ['an-q', 'an-empresa', 'an-user'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.addEventListener('input', renderAnalisis);
                if (el && el.tagName === 'SELECT') el.addEventListener('change', renderAnalisis);
            });
            var cicloEl = document.getElementById('an-ciclo');
            if (cicloEl) cicloEl.addEventListener('change', function () {
                applyAnalisisCiclo(this.value);
            });
            var empBox = document.getElementById('an-empresas');
            if (empBox) {
                empBox.addEventListener('click', function (e) {
                    var hit = e.target.closest('[data-emp]');
                    if (!hit) return;
                    var sel = document.getElementById('an-empresa');
                    if (!sel) return;
                    var next = hit.getAttribute('data-emp') || '';
                    sel.value = sel.value === next ? '' : next;
                    renderAnalisis();
                });
            }
            CC._analisisBound = true;
        }

        var ciclo = val('an-ciclo') || cicloAnalisisPreferido();
        var cicloEl = document.getElementById('an-ciclo');
        if (cicloEl && ciclo) cicloEl.value = ciclo;
        CC.state.cicloCodigo = ciclo || CC.state.cicloCodigo || '';
        if (CC._anCapturaCiclo !== String(CC.state.cicloCodigo || '')) {
            CC._anCapturaCiclo = String(CC.state.cicloCodigo || '');
            loadOverlaysForCycle().then(function () { CC.initAnalisis(); });
            return;
        }

        if (!CC._anAsigLoaded) {
            if (CC._anAsigLoading) return;
            if (!ciclo) {
                CC.state.misAsignaciones = CC.state.misAsignaciones || [];
                CC.state.centros = [];
                CC._anAsigLoaded = true;
                fillAnalisisFilters();
                renderAnalisis();
                return;
            }
            CC._anAsigLoading = true;
            fetch('/AdminCentros/' + encodeURIComponent(ciclo) + '/asignaciones', {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); }).then(function (json) {
                CC.state.misAsignaciones = json.asignaciones || [];
                CC.state.centros = centrosAgrupadosDesdeAsignaciones(asignacionesDelCiclo(ciclo));
                CC._anAsigLoaded = true;
                CC._anAsigLoading = false;
                fillAnalisisFilters();
                renderAnalisis();
                queueAnalisisGastos();
            }).catch(function () {
                CC.state.misAsignaciones = CC.state.misAsignaciones || [];
                CC.state.centros = centrosAgrupadosDesdeAsignaciones(asignacionesDelCiclo(ciclo));
                CC._anAsigLoaded = true;
                CC._anAsigLoading = false;
                fillAnalisisFilters();
                renderAnalisis();
            });
            return;
        }

        CC.state.centros = centrosAgrupadosDesdeAsignaciones(asignacionesDelCiclo(ciclo));
        fillAnalisisFilters();
        renderAnalisis();
        queueAnalisisGastos();
    };

    function queueAnalisisGastos() {
        if (!CC.state.gastoUrl || CC.state.page !== 'analisis') return;
        var year = CC.state.anioGasto || 2026;
        var seenEmp = {};
        var pending = (CC.state.centros || []).filter(function (c) {
            var key = gastoCacheKey(c);
            if (seenEmp[key]) return false;
            seenEmp[key] = true;
            return !(CC.state.gastoCache && CC.state.gastoCache[key]);
        });
        if (!pending.length) return;
        var inflight = 0;
        var max = 3;
        var dirty = false;
        function kick() {
            while (inflight < max && pending.length) {
                var c = pending.shift();
                var key = gastoCacheKey(c);
                inflight += 1;
                (function (centro, cacheKey) {
                    fetch(CC.state.gastoUrl + '?empresa=' + encodeURIComponent(centro.empresa || '') +
                        '&cc=' + encodeURIComponent(centro.codigo || '') +
                        '&year=' + encodeURIComponent(year), {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function (res) { return res.json(); }).then(function (json) {
                        CC.state.gastoCache = CC.state.gastoCache || {};
                        CC.state.gastoCache[cacheKey] = (json && json.por_cuenta) || {};
                        CC.state.gastoLookup = CC.state.gastoLookup || {};
                        CC.state.gastoLookup[cacheKey] = mapFromPorCuenta(CC.state.gastoCache[cacheKey]).map;
                        dirty = true;
                    }).catch(function () {
                        CC.state.gastoCache = CC.state.gastoCache || {};
                        CC.state.gastoCache[cacheKey] = {};
                    }).then(function () {
                        inflight -= 1;
                        if (!pending.length && !inflight && dirty) renderAnalisis();
                        else kick();
                    });
                })(c, key);
            }
        }
        kick();
    }

    function snapshotCentros() {
        return CC.state.centros.map(mergedCentro).map(function (c) {
            var ctas = cuentasDeCentro(c);
            var gasto = totGastoCentro(c, ctas);
            var ppto = totPptoCentro(c, ctas);
            var filled = ctas.filter(function (cta) {
                return mesesTodosLlenos(pptoDe(c.empresa, c.codigo, cta, cta.gasto)) || cuentaMarcada(c.empresa, c.codigo, cta.codigo);
            }).length;
            var users = (c.usuarios && c.usuarios.length) ? c.usuarios : (c.usuario ? [c.usuario] : []);
            return Object.assign({}, c, {
                gasto: gasto,
                ppto: ppto,
                cuentas: ctas.length,
                capturadas: filled,
                avance: pct(filled, ctas.length || 1),
                yoY: deltaPct(ppto, gasto),
                over: gasto > 0 && ppto > gasto * 1.1,
                departamento: deptoDeCentro(c),
                usuarios: users,
                monthGasto: MONTHS.map(function (_, i) {
                    return ctas.reduce(function (a, x) { return a + ((x.gasto || [])[i] || 0); }, 0);
                })
            });
        });
    }

    function renderAnalisis() {
        var q = (val('an-q') || '').toLowerCase();
        var emp = val('an-empresa');
        var user = val('an-user');
        var gYear = CC.state.anioGasto || 2026;
        var pYear = CC.state.anioPresupuesto || 2027;
        var empty = document.getElementById('an-empty');
        var work = document.getElementById('an-work');
        var all = snapshotCentros();
        if (empty) empty.hidden = !!all.length;
        if (work) work.hidden = !all.length;

        var rows = all.filter(function (c) {
            if (emp && c.empresa !== emp) return false;
            if (user && (c.usuarios || []).indexOf(user) === -1 && c.usuario !== user) return false;
            return true;
        });
        var tableRows = rows.filter(function (c) {
            var blob = (c.codigo + ' ' + c.nombre + ' ' + (c.usuarios || []).join(' ') + ' ' + c.usuario + ' ' + c.empresa + ' ' + (c.departamento || '')).toLowerCase();
            return !q || blob.indexOf(q) !== -1;
        });

        if (!rows.length) {
            setText('an-kpi-avance', all.length ? '—' : '—');
            setText('an-kpi-poco', all.length ? '0' : '—');
            setText('an-kpi-over', all.length ? '0' : '—');
            setText('an-kpi-yoy', '—');
        } else {
            var tot = rows.length;
            var avgAv = Math.round(rows.reduce(function (a, c) { return a + c.avance; }, 0) / tot);
            var poco = rows.filter(function (c) { return c.avance < 40; }).length;
            var over = rows.filter(function (c) { return c.over; }).length;
            var withGasto = rows.filter(function (c) { return c.gasto > 0; });
            var avgYoy = withGasto.length
                ? Math.round(withGasto.reduce(function (a, c) { return a + c.yoY; }, 0) / withGasto.length * 10) / 10
                : 0;
            setText('an-kpi-avance', avgAv + '%');
            setText('an-kpi-poco', poco);
            setText('an-kpi-over', over);
            setText('an-kpi-yoy', withGasto.length ? ((avgYoy > 0 ? '+' : '') + avgYoy + '%') : '—');
        }

        var byEmp = {};
        rows.forEach(function (c) {
            byEmp[c.empresa] = byEmp[c.empresa] || { n: 0, av: 0, gasto: 0, ppto: 0 };
            byEmp[c.empresa].n += 1;
            byEmp[c.empresa].av += c.avance;
            byEmp[c.empresa].gasto += c.gasto;
            byEmp[c.empresa].ppto += c.ppto;
        });
        var empBox = document.getElementById('an-empresas');
        if (empBox) {
            empBox.innerHTML = Object.keys(byEmp).map(function (e) {
                var av = Math.round(byEmp[e].av / byEmp[e].n);
                var cls = av < 40 ? 'warn' : (av > 85 ? 'good' : '');
                var on = emp === e ? ' is-on' : '';
                return '<div class="mb-3 cc-an-emp' + on + '" data-emp="' + escapeHtml(e) + '"><div class="d-flex justify-content-between"><strong>' + escapeHtml(e) + '</strong><span>' + av + '% capturado · ' + byEmp[e].n + ' centros</span></div>' +
                    '<div class="cc-progress ' + cls + ' mt-1"><span style="width:' + av + '%"></span></div>' +
                    '<div class="text-muted mt-1" style="font-size:.75rem">Gasto ' + gYear + ' ' + money(byEmp[e].gasto) + ' · Ppto ' + pYear + ' ' + money(byEmp[e].ppto) + '</div></div>';
            }).join('') || '<div class="cc-empty">Sin empresas</div>';
        }

        var tb = document.getElementById('an-tbody');
        if (tb) {
            tb.innerHTML = tableRows.map(function (c) {
                var cls = c.avance < 40 ? 'warn' : (c.over ? 'bad' : 'good');
                var userLabel = (c.usuarios && c.usuarios.length > 1)
                    ? c.usuario + ' +' + (c.usuarios.length - 1)
                    : c.usuario;
                return '<tr><td>' + escapeHtml(c.empresa || '—') + '</td>' +
                    '<td><div class="fw-semibold">' + escapeHtml(c.codigo) + ' — ' + escapeHtml(c.nombre) + '</div><div class="text-muted" style="font-size:.75rem">' + escapeHtml(c.departamento || '') + '</div></td>' +
                    '<td>' + userCell(userLabel) + '</td>' +
                    '<td>' + renderBadge(c.estado) + '</td>' +
                    '<td><div class="d-flex justify-content-between"><span>' + c.capturadas + '/' + c.cuentas + '</span><span>' + c.avance + '%</span></div><div class="cc-progress ' + cls + ' mt-1"><span style="width:' + Math.min(c.avance, 100) + '%"></span></div></td>' +
                    '<td class="num">' + money(c.gasto) + '</td>' +
                    '<td class="num">' + money(c.ppto) + '</td>' +
                    '<td class="num ' + (c.yoY > 10 ? 'text-danger' : '') + '">' + (c.gasto ? ((c.yoY > 0 ? '+' : '') + c.yoY + '%') : '—') + '</td>' +
                    '<td>' + (c.over ? '<span class="cc-badge cc-badge-rechazado">Sobre límite</span>' : '<span class="cc-badge cc-badge-aceptado">Dentro</span>') + '</td>' +
                    '<td><a class="cc-btn" href="' + detalleHref(c) + '">Ver</a></td></tr>';
            }).join('') || '<tr><td colspan="10"><div class="cc-empty">Sin coincidencias</div></td></tr>';
        }

        var empKeys = Object.keys(byEmp);
        drawBar('chart-empresas', empKeys, [
            { label: 'Gasto ' + gYear, data: empKeys.map(function (e) { return byEmp[e].gasto; }), backgroundColor: '#0a0a0a' },
            { label: 'Ppto ' + pYear, data: empKeys.map(function (e) { return byEmp[e].ppto; }), backgroundColor: '#a1a1aa' }
        ]);
        drawDoughnut('chart-estados',
            ['Abierto', 'En proceso', 'En revisión', 'Terminado'],
            [
                rows.filter(function (c) { return normalizeEstado(c.estado) === 'abierto'; }).length,
                rows.filter(function (c) { return normalizeEstado(c.estado) === 'en_proceso'; }).length,
                rows.filter(function (c) { return normalizeEstado(c.estado) === 'en_revision'; }).length,
                rows.filter(function (c) { return normalizeEstado(c.estado) === 'terminado'; }).length
            ]
        );

        var heat = document.getElementById('an-heat');
        if (heat) {
            var focus = rows.slice().sort(function (a, b) { return b.gasto - a.gasto; })[0];
            if (focus) {
                var monthG = focus.monthGasto || MONTHS.map(function () { return 0; });
                var max = Math.max.apply(null, monthG.concat([1]));
                heat.innerHTML = monthG.map(function (v, i) {
                    var t = v / max;
                    var bg = 'rgba(10,10,10,' + (0.12 + t * 0.88) + ')';
                    return '<span title="' + MONTHS[i] + ': ' + money(v) + '" style="background:' + bg + '"></span>';
                }).join('');
                setText('an-heat-label', 'Gasto ' + gYear + ' · ' + focus.codigo + ' ' + focus.nombre);
            } else {
                heat.innerHTML = MONTHS.map(function () {
                    return '<span style="background:rgba(10,10,10,.08)"></span>';
                }).join('');
                setText('an-heat-label', 'Gasto ' + gYear);
            }
        }
    }

    function drawBar(id, labels, datasets) {
        var canvas = document.getElementById(id);
        if (!canvas || !window.Chart) return;
        if (CC.state.charts[id]) CC.state.charts[id].destroy();
        CC.state.charts[id] = new Chart(canvas, {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
                scales: {
                    x: { grid: { display: false } },
                    y: { ticks: { callback: function (v) { return money(v); } }, grid: { color: '#f1f1f3' } }
                }
            }
        });
    }

    function drawDoughnut(id, labels, data) {
        var canvas = document.getElementById(id);
        if (!canvas || !window.Chart) return;
        if (CC.state.charts[id]) CC.state.charts[id].destroy();
        CC.state.charts[id] = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: data, backgroundColor: ['#1d4ed8', '#d97706', '#6d28d9', '#047857', '#b91c1c', '#475569'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, cutout: '62%' }
        });
    }

    /* ---------- helpers DOM ---------- */
    function val(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }
    function setText(id, t) {
        var el = document.getElementById(id);
        if (el) el.textContent = t;
    }
    function isSinCentro(codigo) {
        return String(codigo || '').replace(/\s+/g, '').toUpperCase() === 'SIN_CC';
    }
    function labelNombreCodigo(nombre, codigo) {
        if (isSinCentro(codigo)) return String(nombre || '').trim() || 'Sin centro de costos';
        var name = String(nombre || '').trim();
        var id = codigoCuentaVisible(codigo);
        if (name && id && name !== id) return name + ' — ' + id;
        return name || id || '—';
    }
    function htmlNombreCodigo(nombre, codigo) {
        if (isSinCentro(codigo)) {
            var none = escapeHtml(String(nombre || '').trim() || 'Sin centro de costos');
            return '<div class="cc-name-id"><div class="cc-name-id-name">' + none + '</div></div>';
        }
        var name = String(nombre || '').trim();
        var id = codigoCuentaVisible(codigo);
        var main = escapeHtml(name || id || '—');
        var sub = id && id !== name ? '<div class="cc-name-id-code">' + escapeHtml(id) + '</div>' : '';
        return '<div class="cc-name-id"><div class="cc-name-id-name">' + main + '</div>' + sub + '</div>';
    }
    function paintNombreCodigo(id, nombre, codigo) {
        var el = document.getElementById(id);
        if (!el) return;
        if (isSinCentro(codigo)) {
            el.innerHTML = '<span class="cc-name-id-name">' + escapeHtml(String(nombre || '').trim() || 'Sin centro de costos') + '</span>';
            return;
        }
        var name = String(nombre || '').trim();
        var code = codigoCuentaVisible(codigo);
        el.innerHTML = '<span class="cc-name-id-name">' + escapeHtml(name || code || '—') + '</span>' +
            (code && code !== name ? '<span class="cc-name-id-code">' + escapeHtml(code) + '</span>' : '');
    }
    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }
    function showModal(id) {
        var el = document.getElementById(id);
        if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show();
        if (id === 'modalIndicadores') renderPeriodBanner();
        if (id === 'modalDispersar') paintDispMontoLabel();
        if (id === 'modalPeriodo') {
            var editing = CC.state.page === 'admin-ciclo';
            var title = document.getElementById('modal-ciclo-title');
            var btn = document.getElementById('btn-guardar-ciclo');
            var code = document.getElementById('p-codigo');
            if (title) title.textContent = editing ? 'Editar ciclo' : 'Abrir nuevo ciclo';
            if (btn) btn.textContent = editing ? 'Guardar ciclo' : 'Abrir ciclo';
            if (code) code.readOnly = editing;
            var p = editing
                ? (CC.state.period || {})
                : {
                    codigo: '',
                    nombre: '',
                    anioReferencia: 2026,
                    anio: 2027,
                    inicio: '2026-10-01',
                    fin: '2026-10-31',
                    capturaHasta: '2026-10-31',
                    revisionDesde: '2026-11-01',
                    estado: 'abierto',
                    inflacion: 4,
                    tipoCambio: 20,
                    observaciones: ''
                };
            setVal('p-codigo', p.codigo);
            setVal('p-nombre', p.nombre);
            setVal('p-anio-ref', p.anioReferencia || 2026);
            setVal('p-anio', p.anio || 2027);
            setVal('p-inicio', p.inicio); setVal('p-fin', p.fin);
            setVal('p-captura', p.capturaHasta); setVal('p-revision', p.revisionDesde);
            setVal('p-estado', normalizeCicloEstado(p.estado)); setVal('p-inflacion', p.inflacion); setVal('p-tc', p.tipoCambio);
            setVal('p-obs', p.observaciones || '');
        }
    }
    function hideModal(id) {
        var el = document.getElementById(id);
        if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).hide();
    }
    function showOffcanvas(id) {
        var el = document.getElementById(id);
        if (el && window.bootstrap) bootstrap.Offcanvas.getOrCreateInstance(el).show();
    }
    function setVal(id, v) {
        var el = document.getElementById(id);
        if (el) el.value = v;
    }

    CC.showModal = showModal;

    CC.boot = function (boot) {
        CC.state.page = boot.page;
        CC.state.anioGasto = boot.anioGasto || 2026;
        CC.state.anioPresupuesto = boot.anioPresupuesto || 2027;
        CC.state.usuarios = boot.usuarios || [];
        CC.state.centroInicial = boot.centroInicial || '';
        CC.state.empresaInicial = boot.empresaInicial || '';
        CC.state.cicloInicial = boot.cicloInicial || '';
        CC.state.vistaInicial = boot.vistaInicial || '';
        CC.state.detalleUrl = boot.detalleUrl || '/ControlCentros/detalle';
        CC.state.gastoUrl = boot.gastoUrl || '/CentrosCostos/api/gasto-real';
        CC.state.gastoCache = {};
        CC.state.cicloCodigo = boot.cicloCodigo || boot.cicloInicial || '';
        CC.state.usuarioActual = boot.usuarioActual || '';
        CC.state.ciclos = loadCiclos(boot.periodoDefault, boot.ciclos);
        if (Array.isArray(boot.misAsignaciones)) {
            CC.state.misAsignaciones = boot.misAsignaciones;
            CC._asigLoaded = true;
        }
        var actual = findCiclo(CC.state.cicloCodigo)
            || findCiclo((loadJSON(SK.period, {}) || {}).codigo)
            || CC.state.ciclos.filter(function (c) { return normalizeCicloEstado(c.estado) === 'abierto'; })[0]
            || CC.state.ciclos[0]
            || boot.periodoDefault
            || {};
        CC.state.period = Object.assign({}, boot.periodoDefault || {}, actual);
        if (CC.state.period.anioReferencia) CC.state.anioGasto = CC.state.period.anioReferencia;
        if (CC.state.period.anio) CC.state.anioPresupuesto = CC.state.period.anio;
        CC.state.overlays = {};
        CC.state.budgets = {};
        CC.state.completados = {};
        CC.state.currency = String(loadJSON(SK.currency, 'MXN') || 'MXN').toUpperCase() === 'USD' ? 'USD' : 'MXN';
        CC.state.sapMensaje = boot.sapMensaje;
        mergeSap(boot, { lockCentros: CC.state.page === 'control' || CC.state.page === 'detalle' || CC.state.page === 'analisis' });
        var flag = document.getElementById('sap-flag');
        if (flag) {
            flag.textContent = CC.state.sapOk && (boot.centros || []).length ? 'Catálogo SAP' : (boot.sapMensaje ? 'Catálogo local' : 'Catálogo de trabajo');
            flag.className = 'cc-sap-flag' + (CC.state.sapOk && (boot.centros || []).length ? '' : ' off');
        }
        if (CC.state.page === 'admin') CC.initCiclos();
        if (CC.state.page === 'admin-ciclo' || CC.state.page === 'asignacion') {
            renderPeriodBanner();
            bindPeriodoForm();
            var delBtn = document.getElementById('btn-eliminar-ciclo');
            if (delBtn && !CC._cicloDelHeaderBound) {
                delBtn.addEventListener('click', function () {
                    CC.eliminarCiclo(CC.state.cicloCodigo || (CC.state.period && CC.state.period.codigo));
                });
                CC._cicloDelHeaderBound = true;
            }
        }
        if (CC.state.page === 'control') CC.initControl();
        if (CC.state.page === 'detalle') CC.initDetalle();
        if (CC.state.page === 'analisis') CC.initAnalisis();
        if (CC.state.page === 'control' || CC.state.page === 'detalle' || CC.state.page === 'analisis') refreshSap(boot.catalogoUrl);
    };

    function refreshSap(url) {
        if (!url) return;
        fetch(url + '?per_page=120', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (data) {
            if (!data) return;
            var lock = CC.state.page === 'control' || CC.state.page === 'detalle' || CC.state.page === 'analisis';
            if (!lock && !(data.centros || []).length) return;
            mergeSap(Object.assign({}, data, { sapOk: !!data.sapOk }), { lockCentros: lock });
            var flag = document.getElementById('sap-flag');
            if (flag && (data.sapOk || (data.centros || []).length || (data.cuentas || []).length)) {
                flag.textContent = 'Catálogo SAP';
                flag.className = 'cc-sap-flag';
            }
            if (CC.state.page === 'control') CC.initControl();
            if (CC.state.page === 'detalle') CC.initDetalle();
            if (CC.state.page === 'analisis') CC.initAnalisis();
        }).catch(function () { /* se queda el catálogo local */ });
    }

    window.CC = CC;
})(window, document);
