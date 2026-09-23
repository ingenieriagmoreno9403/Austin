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
        admin: 'austin.pv.admin.v1',
        budget: 'austin.pv.budget.v1',
        period: 'austin.pv.period.v2',
        ciclos: 'austin.pv.ciclos.v2',
        currency: 'austin.pv.currency.v1',
        qtyRaw: 'austin.pv.qtyRaw.v1'
    };

    function money(n, currency, monthIdx) {
        var cur = currency || CC.state.currency || 'MXN';
        var val = toDisplayAmount(n, monthIdx);
        return (cur === 'USD' ? 'US$' : '$') + val.toLocaleString('es-MX', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function moneyDec(n, monthIdx) {
        return toDisplayAmount(n, monthIdx).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fxBaseRate() {
        var p = CC.state.period || {};
        var tc = Number(p.tipoCambio);
        return tc > 0 ? tc : 20;
    }

    /** TC base del ciclo, o el de un mes (0–11) si hay override. */
    function fxRate(monthIdx) {
        ensureFxMeses();
        if (monthIdx === null || monthIdx === undefined || monthIdx === '') {
            return fxBaseRate();
        }
        var i = Number(monthIdx);
        if (!isFinite(i) || i < 0 || i > 11) return fxBaseRate();
        var v = Number((CC.state.fxMeses || [])[i]);
        return v > 0 ? v : fxBaseRate();
    }

    function ensureFxMeses() {
        var base = fxBaseRate();
        var src = (CC.state.period && CC.state.period.tipoCambioMeses) || CC.state.fxMeses || [];
        if (!CC.state.fxMeses || CC.state.fxMeses.length !== 12) {
            var out = [];
            for (var i = 0; i < 12; i++) {
                var v = Number(src[i]);
                out.push(v > 0 ? v : base);
            }
            CC.state.fxMeses = out;
            if (CC.state.period) CC.state.period.tipoCambioMeses = out.slice();
        }
        return CC.state.fxMeses;
    }

    function syncFxMesesFromPeriod(period) {
        var p = period || CC.state.period || {};
        var base = Number(p.tipoCambio) > 0 ? Number(p.tipoCambio) : 20;
        var src = Array.isArray(p.tipoCambioMeses) ? p.tipoCambioMeses : [];
        var out = [];
        for (var i = 0; i < 12; i++) {
            var v = Number(src[i]);
            out.push(v > 0 ? v : base);
        }
        CC.state.fxMeses = out;
        if (CC.state.period) {
            CC.state.period.tipoCambio = base;
            CC.state.period.tipoCambioMeses = out.slice();
        }
        return out;
    }

    function fxMesesVarian() {
        var base = fxBaseRate();
        var meses = ensureFxMeses();
        for (var i = 0; i < 12; i++) {
            if (Math.abs(Number(meses[i]) - base) > 0.0001) return true;
        }
        return false;
    }

    function isUsdView() {
        return String(CC.state.currency || 'MXN').toUpperCase() === 'USD';
    }

    /** Valor guardado (base MXN) → lo que se muestra según moneda. Solo importes. */
    function toDisplayAmount(n, monthIdx) {
        var val = Number(n);
        if (!isFinite(val)) val = 0;
        if (isUsdView()) val = val / fxRate(monthIdx);
        return Math.round(val * 100) / 100;
    }

    /** Valor capturado en pantalla → lo que se guarda (siempre en MXN base). Solo importes. */
    function toStoreAmount(n, monthIdx) {
        var val = Number(n);
        if (!isFinite(val)) val = 0;
        if (isUsdView()) val = val * fxRate(monthIdx);
        return Math.round(val * 100) / 100;
    }

    /**
     * Cantidades (unidades): el tipo de cambio NO las afecta.
     * Se muestran y guardan tal cual.
     */
    function toStoreQty(n) {
        var val = Number(n);
        if (!isFinite(val)) val = 0;
        return Math.round(val * 100) / 100;
    }

    function formatInputQty(n) {
        if (!mesLleno(n)) return '';
        var v = Number(n);
        if (!isFinite(v)) return '';
        if (Math.abs(v - Math.round(v)) < 0.00001) return String(Math.round(v));
        return String(Math.round(v * 100) / 100);
    }

    /** @deprecated Usar formatInputQty para meses (unidades). */
    function formatInputAmount(n) {
        return formatInputQty(n);
    }

    function paintFxBadge() {
        var tc = fxBaseRate();
        var cur = isUsdView() ? 'USD' : 'MXN';
        var varian = fxMesesVarian();
        var label = cur === 'USD'
            ? ('Vista USD · TC ' + tc.toFixed(2) + ' MXN/USD' + (varian ? ' · por mes' : ''))
            : ('Vista MXN · TC ' + tc.toFixed(2) + ' MXN/USD' + (varian ? ' · por mes' : ''));
        var shortLabel = 'TC ' + tc.toFixed(2) + (varian ? '*' : '');
        ['ctl-fx-badge', 'ctl-fx-nav'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.hidden = false;
            el.textContent = id === 'ctl-fx-nav' ? shortLabel : label;
            el.setAttribute('title', 'Clic para editar TC base y por mes. Base: 1 USD = ' + tc.toFixed(2) + ' MXN');
            el.classList.toggle('is-usd', cur === 'USD');
            el.classList.add('is-clickable');
            el.setAttribute('role', 'button');
            el.tabIndex = 0;
        });
        var mon = document.getElementById('ctl-moneda');
        if (mon) mon.setAttribute('title', '1 USD = ' + tc.toFixed(2) + ' MXN (base)' + (varian ? '; hay TC distinto por mes' : ''));
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
            preciosMeses: {},
            charts: {}
        }
    };

    function centroKey(c) {
        return String((c && c.empresa) || '').toUpperCase().trim() + '|' + String((c && c.codigo) || '').trim();
    }

    function budgetKey(empresa, cc, cuenta) {
        return [
            String(empresa || '').toUpperCase().trim(),
            String(cc || '').trim(),
            String(cuenta || '').trim()
        ].join('|');
    }

    /** Resuelve la clave real en budgets/completados (tolerante a mayúsculas). */
    function resolveStateKey(map, empresa, cc, cuenta) {
        map = map || {};
        var primary = budgetKey(empresa, cc, cuenta);
        if (Object.prototype.hasOwnProperty.call(map, primary)) return primary;
        var needle = primary.toUpperCase();
        var keys = Object.keys(map);
        for (var i = 0; i < keys.length; i++) {
            if (String(keys[i]).toUpperCase() === needle) return keys[i];
        }
        return primary;
    }

    function budgetMonthsOf(empresa, cc, cuenta) {
        var key = resolveStateKey(CC.state.budgets, empresa, cc, cuenta);
        var row = CC.state.budgets && CC.state.budgets[key];
        return Array.isArray(row) ? row.slice() : null;
    }

    function cuentaMarcada(empresa, cc, codigo) {
        var done = CC.state.completados || {};
        var k = resolveStateKey(done, empresa, cc, codigo);
        if (done[k]) return true;
        var needle = budgetKey(empresa, cc, codigo).toUpperCase();
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
            var packed = packedLookupDeCentro(c);
            return asig.cuentas.map(function (x) {
                var sap = (CC.state.cuentas || []).filter(function (cta) {
                    return String(cta.codigo) === String(x.codigo);
                })[0];
                var nombre = x.nombre || (sap && sap.nombre) || x.codigo;
                var extra = extraMensualDe(x.codigo, nombre, packed.extras, packed.nombres);
                return {
                    codigo: x.codigo,
                    nombre: nombre,
                    grupo: x.agrupacion || (sap && sap.grupo) || 'Asignadas',
                    gasto: gastoMensualDe(x.codigo, nombre, packed.map, packed.nombres),
                    importe: extra.importe,
                    importeUsd: extra.importe_usd,
                    precio: extra.precio,
                    unidad: extra.unidad || '',
                    unidadNombre: extra.unidad_nombre || '',
                    costoVenta: Number(extra.costo) || 0,
                    costoVentaMoneda: String(extra.costo_moneda || 'MXN').toUpperCase(),
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
        return ac.length >= 16 && bc.length >= 16 && (ac.indexOf(bc) === 0 || bc.indexOf(ac) === 0);
    }

    function zeros12() {
        return [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    }

    function moneyGasto(n) {
        n = Number(n) || 0;
        if (!n) return '—';
        // n ya viene en la moneda de vista (LineTotal / LineTotalUSD de SAP).
        return (isUsdView() ? 'US$' : '$') + n.toLocaleString('es-MX', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function usdSeriesOf(cta) {
        if (!cta) return zeros12();
        if (cta.importeUsd && cta.importeUsd.length === 12) return cta.importeUsd;
        if (cta.importe_usd && cta.importe_usd.length === 12) return cta.importe_usd;
        return zeros12();
    }

    /** Venta real del mes en la moneda de vista: USD → LineTotalUSD; MXN → LineTotal. */
    function importeMesVentaVista(cta, i) {
        if (!cta) return 0;
        if (isUsdView()) {
            var usd = Number((usdSeriesOf(cta)[i]) || 0);
            if (usd) return usd;
            return toDisplayAmount(importeMesVentaMxn(cta, i), i);
        }
        var mxn = Number((cta.importe && cta.importe[i]) || 0);
        if (mxn) return mxn;
        var usdOnly = Number((usdSeriesOf(cta)[i]) || 0);
        if (usdOnly) return usdOnly * fxRate(i);
        return importeMesVentaMxn(cta, i);
    }

    /** Total anual de venta real en la moneda de vista (suma mensual SAP). */
    function importeVentaVista(cta) {
        if (!cta) return 0;
        var total = 0;
        for (var i = 0; i < 12; i++) total += importeMesVentaVista(cta, i);
        return total;
    }

    function gastoMensualDe(codigo, nombre, mapOpt, nombresOpt) {
        var map = mapOpt || control._gastoMap || {};
        var hit = map[String(codigo)] || map[codigoCuentaKey(codigo)];
        var nk = nombreCuentaKey(nombre);
        if ((!hit || hit.length !== 12) && nk) {
            hit = map['n:' + nk] || map['nc:' + nk.replace(/\s+/g, '')];
        }
        if ((!hit || hit.length !== 12) && nk) {
            var list = nombresOpt || control._gastoNombres || [];
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

    function extraFromRow(row) {
        return {
            importe: (row && row.importe && row.importe.length === 12) ? row.importe : zeros12(),
            importe_usd: (row && row.importe_usd && row.importe_usd.length === 12) ? row.importe_usd : zeros12(),
            precio: (row && row.precio && row.precio.length === 12) ? row.precio : zeros12(),
            unidad: (row && row.unidad) ? String(row.unidad) : '',
            unidad_nombre: (row && row.unidad_nombre) ? String(row.unidad_nombre) : '',
            costo: Number(row && row.costo) || 0,
            costo_moneda: String((row && row.costo_moneda) || 'MXN').toUpperCase()
        };
    }

    function mapFromPorCuenta(porCuenta) {
        var map = {};
        var extras = {};
        var nombres = [];
        function put(k, gasto, extra) {
            if (!k) return;
            map[k] = gasto;
            extras[k] = extra;
        }
        Object.keys(porCuenta || {}).forEach(function (key) {
            var row = porCuenta[key] || {};
            var gasto = row.gasto || row;
            if (!gasto || gasto.length !== 12) return;
            var extra = extraFromRow(row);
            put(key, gasto, extra);
            if (row.codigo) put(String(row.codigo), gasto, extra);
            put(codigoCuentaKey(key), gasto, extra);
            if (row.codigo) put(codigoCuentaKey(row.codigo), gasto, extra);
            var nk = nombreCuentaKey(row.nombre || '');
            if (nk) {
                put('n:' + nk, gasto, extra);
                put('nc:' + nk.replace(/\s+/g, ''), gasto, extra);
                nombres.push({ key: nk, gasto: gasto, extra: extra });
            }
        });
        return { map: map, extras: extras, nombres: nombres };
    }

    function gastoCacheKey(c) {
        var year = CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || 2026;
        return String(c.empresa || '').toUpperCase() + '|' + String(c.codigo || '') + '|' + year;
    }

    function packedLookupDeCentro(c) {
        var empty = { map: {}, extras: {}, nombres: [] };
        if (!c) {
            return {
                map: control._gastoMap || {},
                extras: control._gastoExtras || {},
                nombres: control._gastoNombres || []
            };
        }
        var key = gastoCacheKey(c);
        if (control._gastoReq === key && control._gastoMap) {
            return {
                map: control._gastoMap,
                extras: control._gastoExtras || {},
                nombres: control._gastoNombres || []
            };
        }
        var packed = CC.state.gastoLookup && CC.state.gastoLookup[key];
        if (packed) {
            if (packed.map) return packed;
            return { map: packed, extras: {}, nombres: [] };
        }
        var por = CC.state.gastoCache && CC.state.gastoCache[key];
        if (!por) return empty;
        var built = mapFromPorCuenta(por);
        CC.state.gastoLookup = CC.state.gastoLookup || {};
        CC.state.gastoLookup[key] = built;
        return built;
    }

    function gastoLookupDeCentro(c) {
        return packedLookupDeCentro(c).map || {};
    }

    function extrasLookupDeCentro(c) {
        return packedLookupDeCentro(c).extras || {};
    }

    function extraMensualDe(codigo, nombre, extrasOpt, nombresOpt) {
        var extras = extrasOpt || control._gastoExtras || {};
        var hit = extras[String(codigo)] || extras[codigoCuentaKey(codigo)];
        var nk = nombreCuentaKey(nombre);
        if (!hit && nk) {
            hit = extras['n:' + nk] || extras['nc:' + nk.replace(/\s+/g, '')];
        }
        if (!hit && nk) {
            var list = nombresOpt || control._gastoNombres || [];
            for (var i = 0; i < list.length; i++) {
                if (nombresGastoCompatibles(nk, list[i].key)) {
                    hit = list[i].extra;
                    break;
                }
            }
        }
        return extraFromRow(hit);
    }

    function applyGastoMap(porCuenta) {
        var built = mapFromPorCuenta(porCuenta);
        control._gastoMap = built.map;
        control._gastoExtras = built.extras;
        control._gastoNombres = built.nombres;
        control._gastoLoadedFor = control._gastoReq || '';
        if (control._gastoReq) {
            CC.state.gastoLookup = CC.state.gastoLookup || {};
            CC.state.gastoLookup[control._gastoReq] = built;
        }
        if (!control.centro) return;
        control._allCtas = cuentasEnriquecidas(control.centro);
        if (CC._capturaReady) seedPresupuestoDesdeVentaReal(control.centro);
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

    var apiWaitDepth = 0;

    function ensureApiWait() {
        var el = document.getElementById('cc-api-wait');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'cc-api-wait';
        el.className = 'cc-api-wait';
        el.hidden = true;
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        el.innerHTML =
            '<div class="cc-api-wait-card">' +
                '<div class="cc-api-wait-spinner" aria-hidden="true"></div>' +
                '<div class="cc-api-wait-title">Por favor espera</div>' +
                '<div class="cc-api-wait-msg" id="cc-api-wait-msg">Cargando datos…</div>' +
                '<div class="cc-api-wait-bar" aria-hidden="true"><span></span></div>' +
            '</div>';
        document.body.appendChild(el);
        return el;
    }

    function showApiWait(msg) {
        apiWaitDepth += 1;
        var el = ensureApiWait();
        var m = document.getElementById('cc-api-wait-msg');
        if (m) m.textContent = msg || 'Cargando datos de la API…';
        el.hidden = false;
        document.body.classList.add('cc-api-waiting');
    }

    function hideApiWait() {
        if (apiWaitDepth > 0) apiWaitDepth -= 1;
        if (apiWaitDepth > 0) return;
        var el = document.getElementById('cc-api-wait');
        if (el) el.hidden = true;
        document.body.classList.remove('cc-api-waiting');
    }

    function loadingBlockHtml(msg) {
        return '<div class="cc-loading-inline">' +
            '<div class="cc-api-wait-spinner is-sm" aria-hidden="true"></div>' +
            '<div class="cc-loading-inline-copy">' +
                '<strong>Por favor espera</strong>' +
                '<span>' + escapeHtml(msg || 'Cargando…') + '</span>' +
            '</div>' +
            '</div>';
    }

    function loadGastoRealCentro(c) {
        if (!c || !CC.state.gastoUrl) return;
        var year = CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || 2026;
        var key = String(c.empresa || '').toUpperCase() + '|' + String(c.codigo || '') + '|' + year;
        control._gastoReq = key;
        if (CC.state.gastoCache && CC.state.gastoCache[key]) {
            applyGastoMap(CC.state.gastoCache[key]);
            return;
        }
        control._gastoMap = {};
        showApiWait('Consultando ventas reales en SAP…');
        fetch(CC.state.gastoUrl + '?empresa=' + encodeURIComponent(c.empresa || '') +
            '&cc=' + encodeURIComponent(c.codigo || '') +
            '&year=' + encodeURIComponent(year), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (json) {
            if (control._gastoReq !== key) return;
            var por = (json && json.por_cuenta) || {};
            CC.state.gastoCache = CC.state.gastoCache || {};
            CC.state.gastoCache[key] = por;
            applyGastoMap(por);
        }).catch(function () {
            if (control._gastoReq !== key) return;
            applyGastoMap({});
        }).then(function () {
            hideApiWait();
        });
    }

    function preciosCacheKey(c) {
        return String(c.empresa || '').toUpperCase() + '|' + String(c.codigo || '');
    }

    function lookupPrecioLista(codigo) {
        var map = control._preciosMap || {};
        if (!codigo) return null;
        return map[String(codigo)]
            || map[String(codigo).toUpperCase()]
            || map[codigoCuentaKey(codigo)]
            || null;
    }

    function applyPreciosMap(porArticulo) {
        var map = {};
        Object.keys(porArticulo || {}).forEach(function (key) {
            var row = porArticulo[key];
            if (!row || typeof row !== 'object') return;
            var codigo = String(row.codigo || key || '').trim();
            if (!codigo) return;
            var entry = {
                codigo: codigo,
                nombre: row.nombre || '',
                precio: Number(row.precio) || 0,
                moneda: String(row.moneda || 'MXN').toUpperCase(),
                unidad: String(row.unidad || '').trim(),
                unidad_nombre: String(row.unidad_nombre || '').trim(),
                lista: row.lista || '',
                no_lista: row.no_lista || ''
            };
            map[codigo] = entry;
            map[codigo.toUpperCase()] = entry;
            map[codigoCuentaKey(codigo)] = entry;
        });
        control._preciosMap = map;
        if (control._preciosReq) {
            CC.state.preciosCache = CC.state.preciosCache || {};
            CC.state.preciosCache[control._preciosReq] = map;
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

    function loadListasPreciosCentro(c) {
        if (!c || !CC.state.listasPreciosUrl) return;
        var key = preciosCacheKey(c);
        control._preciosReq = key;
        if (CC.state.preciosCache && CC.state.preciosCache[key]) {
            control._preciosMap = CC.state.preciosCache[key];
            if (control.centro) {
                control._allCtas = cuentasEnriquecidas(control.centro);
                renderControlTable();
            }
            return;
        }
        control._preciosMap = {};
        showApiWait('Consultando listas de precios…');
        fetch(CC.state.listasPreciosUrl + '?empresa=' + encodeURIComponent(c.empresa || '') +
            '&cliente=' + encodeURIComponent(c.codigo || '') +
            '&year=' + encodeURIComponent(CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || new Date().getFullYear()), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (json) {
            if (control._preciosReq !== key) return;
            applyPreciosMap((json && json.por_articulo) || {});
        }).catch(function () {
            if (control._preciosReq !== key) return;
            applyPreciosMap({});
        }).then(function () {
            hideApiWait();
        });
    }

    function syncVentasPasadasBtn() {
        var btn = document.getElementById('ctl-btn-ventas-pasadas');
        if (!btn) return;
        btn.disabled = !(val('ctl-empresa') && val('ctl-centro'));
    }

    function fillVentasPasadasYears() {
        var sel = document.getElementById('vp-anio');
        if (!sel) return;
        var current = Number(sel.value) || 0;
        var ref = Number(CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || new Date().getFullYear()) || new Date().getFullYear();
        var minY = ref - 3;
        var years = [];
        for (var y = ref; y >= minY; y--) years.push(y);
        var preferred = current || ref;
        if (preferred > ref || preferred < minY) preferred = ref;
        sel.innerHTML = years.map(function (y) {
            return '<option value="' + y + '"' + (y === preferred ? ' selected' : '') + '>' + y + '</option>';
        }).join('');
        if (!sel.value) sel.value = String(preferred);
    }

    function openVentasPasadas() {
        var emp = val('ctl-empresa');
        var cc = val('ctl-centro');
        if (!emp || !cc) {
            toast('warning', 'Elige empresa y cliente', '');
            return;
        }
        var c = control.centro;
        var sub = document.getElementById('vp-sub');
        if (sub) {
            sub.textContent = String(emp).toUpperCase() + ' · ' +
                labelNombreCodigo((c && c.nombre) || cc, (c && c.codigo) || cc);
        }
        fillVentasPasadasYears();
        control._vpMetric = val('vp-metric') || 'qty';
        control._vpQuery = '';
        var q = document.getElementById('vp-q');
        if (q) q.value = '';
        showModal('modalVentasPasadas');
        loadVentasPasadas(true);
    }

    function fetchVentasPasadas(empresa, cc, year) {
        var key = String(empresa || '').toUpperCase() + '|' + String(cc || '') + '|' + year;
        CC.state.gastoCache = CC.state.gastoCache || {};
        if (CC.state.gastoCache[key]) {
            return Promise.resolve({ ok: true, year: year, por_cuenta: CC.state.gastoCache[key], cached: true });
        }
        return fetch(CC.state.gastoUrl + '?empresa=' + encodeURIComponent(empresa || '') +
            '&cc=' + encodeURIComponent(cc || '') +
            '&year=' + encodeURIComponent(year), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (json) {
            var por = (json && json.por_cuenta) || {};
            if (json && json.ok) CC.state.gastoCache[key] = por;
            return json || { ok: false, por_cuenta: {}, mensaje: 'Sin respuesta' };
        });
    }

    function rowsFromPorCuenta(por) {
        return Object.keys(por || {}).map(function (k) {
            var row = por[k] || {};
            var gasto = Array.isArray(row.gasto) ? row.gasto : zeros12();
            var importe = Array.isArray(row.importe) ? row.importe : zeros12();
            var usd = Array.isArray(row.importe_usd) ? row.importe_usd : zeros12();
            return {
                codigo: row.codigo || k,
                nombre: row.nombre || row.codigo || k,
                gasto: gasto,
                importe: importe,
                importe_usd: usd,
                totQty: sum(gasto),
                totMxn: sum(importe),
                totUsd: sum(usd)
            };
        }).sort(function (a, b) {
            return (b.totMxn || b.totQty) - (a.totMxn || a.totQty);
        });
    }

    function formatVpCell(v, metric) {
        var n = Number(v) || 0;
        if (!n) return '—';
        var txt = n.toLocaleString('es-MX', {
            minimumFractionDigits: 0,
            maximumFractionDigits: metric === 'qty' ? 2 : 2
        });
        if (metric === 'mxn') return '$' + txt;
        if (metric === 'usd') return 'US$' + txt;
        return txt;
    }

    function renderVentasPasadasTable(rows, year, metric) {
        var thead = document.getElementById('vp-thead');
        var tbody = document.getElementById('vp-tbody');
        var meta = document.getElementById('vp-meta');
        if (!thead || !tbody) return;
        metric = metric || control._vpMetric || 'qty';
        var q = normSearch(control._vpQuery || ((document.getElementById('vp-q') || {}).value || ''));
        var list = (rows || []).filter(function (r) {
            if (!q) return true;
            return normSearch((r.codigo || '') + ' ' + (r.nombre || '')).indexOf(q) !== -1;
        });
        var totLabel = metric === 'mxn' ? 'Tot. MXN' : (metric === 'usd' ? 'Tot. USD' : 'Tot. uds');
        var head = '<tr><th class="sticky-col">Producto</th><th class="num">' + totLabel + '</th>';
        MONTHS.forEach(function (m) { head += '<th class="num">' + m + '</th>'; });
        head += '</tr>';
        thead.innerHTML = head;

        if (!list.length) {
            tbody.innerHTML = '<tr><td colspan="14"><div class="cc-empty">Sin ventas en ' + year +
                (q ? ' para ese filtro' : '') + '</div></td></tr>';
            if (meta) meta.textContent = 'Año ' + year + ' · 0 productos';
            return;
        }

        var seriesKey = metric === 'mxn' ? 'importe' : (metric === 'usd' ? 'importe_usd' : 'gasto');
        var totKey = metric === 'mxn' ? 'totMxn' : (metric === 'usd' ? 'totUsd' : 'totQty');
        var grand = list.reduce(function (a, r) { return a + (Number(r[totKey]) || 0); }, 0);
        tbody.innerHTML = list.map(function (r) {
            var series = r[seriesKey] || zeros12();
            var cells = '';
            for (var i = 0; i < 12; i++) {
                cells += '<td class="num' + ((Number(series[i]) || 0) ? '' : ' text-muted') + '">' +
                    formatVpCell(series[i], metric) + '</td>';
            }
            return '<tr><td class="sticky-col">' + htmlNombreCodigo(r.nombre, r.codigo) + '</td>' +
                '<td class="num fw-semibold">' + formatVpCell(r[totKey], metric) + '</td>' +
                cells + '</tr>';
        }).join('') +
            '<tr class="cc-vp-total"><td class="sticky-col"><strong>Total</strong></td>' +
            '<td class="num fw-semibold">' + formatVpCell(grand, metric) + '</td>' +
            MONTHS.map(function (_, i) {
                var t = list.reduce(function (a, r) {
                    return a + (Number((r[seriesKey] || [])[i]) || 0);
                }, 0);
                return '<td class="num fw-semibold">' + formatVpCell(t, metric) + '</td>';
            }).join('') + '</tr>';

        if (meta) {
            meta.textContent = 'Año ' + year + ' · ' + list.length +
                (list.length === 1 ? ' producto' : ' productos') +
                ' · Total ' + formatVpCell(grand, metric);
        }
    }

    function loadVentasPasadas(force) {
        var emp = val('ctl-empresa');
        var cc = val('ctl-centro');
        var ref = Number(CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || new Date().getFullYear()) || new Date().getFullYear();
        var year = Number(val('vp-anio')) || ref;
        if (year > ref) year = ref;
        if (year < ref - 3) year = ref - 3;
        var anioSel = document.getElementById('vp-anio');
        if (anioSel && Number(anioSel.value) !== year) anioSel.value = String(year);
        var metric = val('vp-metric') || 'qty';
        control._vpMetric = metric;
        var tbody = document.getElementById('vp-tbody');
        var meta = document.getElementById('vp-meta');
        var btn = document.getElementById('vp-cargar');
        if (!emp || !cc) {
            if (tbody) tbody.innerHTML = '<tr><td colspan="14"><div class="cc-empty">Elige empresa y cliente</div></td></tr>';
            return;
        }
        if (!force && control._vpRows && Number(control._vpYear) === year) {
            renderVentasPasadasTable(control._vpRows, year, metric);
            return;
        }
        if (tbody) tbody.innerHTML = '<tr><td colspan="14">' + loadingBlockHtml('Cargando ventas ' + year + ' desde SAP…') + '</td></tr>';
        if (meta) meta.textContent = 'Consultando SAP…';
        if (btn) btn.disabled = true;
        control._vpReq = String(emp).toUpperCase() + '|' + cc + '|' + year;
        showApiWait('Cargando ventas pasadas de ' + year + '…');
        fetchVentasPasadas(emp, cc, year).then(function (json) {
            if (control._vpReq !== String(emp).toUpperCase() + '|' + cc + '|' + year) return;
            if (btn) btn.disabled = false;
            if (!json || !json.ok) {
                control._vpRows = [];
                control._vpYear = year;
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="14"><div class="cc-empty">' +
                        escapeHtml((json && json.mensaje) || 'No se pudieron cargar las ventas') +
                        '</div></td></tr>';
                }
                if (meta) meta.textContent = 'Año ' + year + ' · sin datos';
                return;
            }
            control._vpRows = rowsFromPorCuenta(json.por_cuenta || {});
            control._vpYear = year;
            renderVentasPasadasTable(control._vpRows, year, metric);
        }).catch(function (err) {
            if (btn) btn.disabled = false;
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="14"><div class="cc-empty">' +
                    escapeHtml((err && err.message) || 'Error de red') + '</div></td></tr>';
            }
        }).then(function () {
            hideApiWait();
        });
    }

    function bindVentasPasadas() {
        var openBtn = document.getElementById('ctl-btn-ventas-pasadas');
        if (openBtn) openBtn.addEventListener('click', openVentasPasadas);
        var cargar = document.getElementById('vp-cargar');
        if (cargar) cargar.addEventListener('click', function () { loadVentasPasadas(true); });
        var anio = document.getElementById('vp-anio');
        if (anio) anio.addEventListener('change', function () { loadVentasPasadas(true); });
        var metric = document.getElementById('vp-metric');
        if (metric) metric.addEventListener('change', function () {
            control._vpMetric = this.value || 'qty';
            if (control._vpRows) renderVentasPasadasTable(control._vpRows, control._vpYear, control._vpMetric);
            else loadVentasPasadas(false);
        });
        var q = document.getElementById('vp-q');
        if (q) q.addEventListener('input', function () {
            control._vpQuery = this.value || '';
            if (control._vpRows) renderVentasPasadasTable(control._vpRows, control._vpYear, control._vpMetric);
        });
    }

    function preciosMesesDe(empresa, cc, codigo) {
        var map = CC.state.preciosMeses || {};
        var k = budgetKey(empresa, cc, codigo);
        var arr = map[k];
        if (!arr) {
            var needle = String(k).toUpperCase();
            Object.keys(map).some(function (key) {
                if (String(key).toUpperCase() === needle) {
                    arr = map[key];
                    return true;
                }
                return false;
            });
        }
        var out = [];
        for (var i = 0; i < 12; i++) {
            var v = arr && arr[i];
            out.push((v === null || v === undefined || v === '' || !(Number(v) > 0)) ? null : Math.round(Number(v) * 10000) / 10000);
        }
        return out;
    }

    function setPrecioMesesLocal(empresa, cc, codigo, meses) {
        CC.state.preciosMeses = CC.state.preciosMeses || {};
        CC.state.preciosMeses[budgetKey(empresa, cc, codigo)] = (meses || []).slice(0, 12);
    }

    /** Precio unitario efectivo del mes: override o precio de lista global. */
    function precioUnitarioMes(cta, monthIdx) {
        var base = Number(cta && cta.precioLista) || 0;
        var arr = (cta && cta.precioMeses) || [];
        var v = Number(arr[monthIdx]);
        if (isFinite(v) && v > 0) return v;
        if (base > 0) return base;
        return 0;
    }

    function precioMesTieneOverride(cta, monthIdx) {
        var v = Number((cta && cta.precioMeses && cta.precioMeses[monthIdx]));
        return isFinite(v) && v > 0;
    }

    function precioMesesVarian(cta) {
        for (var i = 0; i < 12; i++) {
            if (precioMesTieneOverride(cta, i)) return true;
        }
        return false;
    }

    function pptoDe(empresa, cc, cuenta, gasto) {
        var code = cuenta && typeof cuenta === 'object' ? cuenta.codigo : cuenta;
        var found = budgetMonthsOf(empresa, cc, code);
        if (found) return found;
        if (cuenta && cuenta.ppto) return cuenta.ppto.slice();
        return (gasto || (cuenta && cuenta.gasto) || [0,0,0,0,0,0,0,0,0,0,0,0]).map(function () { return null; });
    }

    function protectLockedMonths(empresa, cc, cuenta, months, opts) {
        opts = opts || {};
        var out = (months || []).slice();
        while (out.length < 12) out.push(null);
        out = out.slice(0, 12);
        if (opts.allowLocked) return out;
        var lock = budgetLockCount();
        if (!lock) return out;
        var prev = budgetMonthsOf(empresa, cc, cuenta);
        if (!prev) return out;
        for (var i = 0; i < lock; i++) {
            out[i] = prev[i];
        }
        return out;
    }

    function persistBudget(empresa, cc, cuenta, months, opts) {
        opts = opts || {};
        months = protectLockedMonths(empresa, cc, cuenta, months, opts);
        var ciclo = cicloActualCodigo();
        if (!ciclo) return;
        var key = budgetKey(empresa, cc, cuenta);
        var timerKey = String(ciclo).toUpperCase() + '|' + key;
        CC.state.budgets = CC.state.budgets || {};
        CC.state.budgets[key] = months.slice();
        CC.state.completados = CC.state.completados || {};
        var allFilled = mesesTodosLlenos(months);
        var done = Object.prototype.hasOwnProperty.call(opts, 'completado')
            ? !!opts.completado
            : allFilled;
        if (done && !allFilled) {
            months = months.map(function (v) { return mesLleno(v) ? v : 0; });
            CC.state.budgets[key] = months.slice();
        }
        if (done) CC.state.completados[key] = true;
        else delete CC.state.completados[key];

        var monthsToSave = months.slice();
        var nombre = '';
        var list = (control && control._allCtas) || [];
        for (var i = 0; i < list.length; i++) {
            if (String(list[i].codigo) === String(cuenta)) {
                nombre = list[i].nombre || '';
                break;
            }
        }
        var costo = 0;
        for (var j = 0; j < list.length; j++) {
            if (String(list[j].codigo) === String(cuenta)) {
                costo = Number(list[j].costo) || 0;
                break;
            }
        }
        var precioMeses = preciosMesesDe(empresa, cc, cuenta);
        var ajuste = Number((document.getElementById('ctl-ajuste-pct') || {}).value) || 0;

        function doSave() {
            return fetch('/ProyeccionesVentas/api/captura/presupuesto', {
                method: 'PUT',
                headers: apiJsonHeaders(),
                body: JSON.stringify({
                    ciclo: ciclo,
                    empresa: empresa,
                    centro: cc,
                    cuenta: cuenta,
                    cuenta_nombre: nombre,
                    meses: monthsToSave,
                    completado: done,
                    ajuste_pct: ajuste,
                    precio_meses: precioMeses,
                    costo_unitario: costo
                })
            }).then(function (res) {
                return res.json().then(function (json) {
                    if (!res.ok) throw new Error(json.message || 'No se pudo guardar la proyección');
                    // Solo sincroniza estado local si seguimos en el mismo ciclo.
                    if (String(cicloActualCodigo() || '').toUpperCase() !== String(ciclo).toUpperCase()) return json;
                    CC.state.completados = CC.state.completados || {};
                    if (json.completado) CC.state.completados[key] = true;
                    else delete CC.state.completados[key];
                    if (json.precio_meses) setPrecioMesesLocal(empresa, cc, cuenta, json.precio_meses);
                    return json;
                });
            }).catch(function (err) {
                toast('error', 'No se guardó la proyección', err && err.message ? err.message : 'Error de red');
            });
        }

        if (CC._budgetSaveTimers && CC._budgetSaveTimers[timerKey]) clearTimeout(CC._budgetSaveTimers[timerKey]);
        CC._budgetSaveTimers = CC._budgetSaveTimers || {};
        CC._budgetSavePending = CC._budgetSavePending || {};
        CC._budgetSavePending[timerKey] = doSave;
        CC._budgetSaveTimers[timerKey] = setTimeout(function () {
            delete CC._budgetSaveTimers[timerKey];
            var fn = CC._budgetSavePending[timerKey];
            delete CC._budgetSavePending[timerKey];
            if (fn) fn();
        }, 350);
    }

    /** Antes de cambiar de ciclo: manda a guardar lo pendiente al ciclo correcto. */
    function flushPendingBudgetSaves() {
        var pending = CC._budgetSavePending || {};
        var timers = CC._budgetSaveTimers || {};
        var keys = Object.keys(pending);
        var jobs = keys.map(function (k) {
            if (timers[k]) clearTimeout(timers[k]);
            delete timers[k];
            var fn = pending[k];
            delete pending[k];
            try {
                return fn ? Promise.resolve(fn()) : Promise.resolve();
            } catch (e) {
                return Promise.resolve();
            }
        });
        return Promise.all(jobs);
    }

    function cancelPendingBudgetSaves() {
        var timers = CC._budgetSaveTimers || {};
        Object.keys(timers).forEach(function (k) {
            clearTimeout(timers[k]);
            delete timers[k];
        });
        CC._budgetSavePending = {};
    }

    function persistPrecioMesesProducto(empresa, cc, cuenta, precioMeses) {
        setPrecioMesesLocal(empresa, cc, cuenta, precioMeses);
        var months = (CC.state.budgets && CC.state.budgets[budgetKey(empresa, cc, cuenta)])
            ? CC.state.budgets[budgetKey(empresa, cc, cuenta)].slice()
            : null;
        if (!months) {
            var cta = ((control && control._allCtas) || []).filter(function (x) {
                return String(x.codigo) === String(cuenta);
            })[0];
            months = cta && cta.ppto ? cta.ppto.slice() : [null, null, null, null, null, null, null, null, null, null, null, null];
        }
        persistBudget(empresa, cc, cuenta, months);
    }

    function persistOverlay(c, patch) {
        var k = centroKey(c);
        CC.state.overlays[k] = Object.assign({}, CC.state.overlays[k] || {}, patch, { fecha: new Date().toLocaleDateString('es-MX') });
        if (!patch || patch.estado == null || !c) return;
        if (CC.state.page !== 'control' && CC.state.page !== 'detalle') return;
        var ciclo = cicloActualCodigo();
        if (!ciclo) return;
        fetch('/ProyeccionesVentas/api/captura/centro', {
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
        var fromCtl = document.getElementById('ctl-ciclo') ? val('ctl-ciclo') : '';
        if (fromCtl) return String(fromCtl);
        var fromAn = document.getElementById('an-ciclo') ? val('an-ciclo') : '';
        if (fromAn) return String(fromAn);
        return String(CC.state.cicloCodigo || (CC.state.period && CC.state.period.codigo) || '');
    }

    function persistPeriod() {
        upsertCiclo(CC.state.period);
        saveJSON(SK.period, CC.state.period);
    }

    function saveCicloApi(data) {
        return fetch('/ProyeccionesVentas/api/ciclos', {
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

    /**
     * Una sola vez: si se capturó en vista USD, los meses se guardaron × TC.
     * Los dejamos en unidades reales para que MXN/USD ya no los mueva.
     */
    function unscaleQtyBudgetsIfNeeded() {
        if (loadJSON(SK.qtyRaw, false)) return;
        var cur = String(CC.state.currency || 'MXN').toUpperCase();
        if (cur !== 'USD') {
            saveJSON(SK.qtyRaw, true);
            return;
        }
        var tc = fxBaseRate();
        if (!(tc > 0)) {
            saveJSON(SK.qtyRaw, true);
            return;
        }
        Object.keys(CC.state.budgets || {}).forEach(function (k) {
            CC.state.budgets[k] = (CC.state.budgets[k] || []).map(function (v) {
                if (!mesLleno(v)) return v;
                return Math.round((Number(v) / tc) * 100) / 100;
            });
        });
        saveJSON(SK.qtyRaw, true);
    }

    function overlayStoreKey() {
        return SK.admin + ':' + String((CC.state.period && CC.state.period.codigo) || 'default');
    }

    function budgetStoreKey() {
        return SK.budget + ':' + String((CC.state.period && CC.state.period.codigo) || 'default');
    }

    function loadOverlaysForCycle() {
        return flushPendingBudgetSaves().catch(function () {
            return null;
        }).then(function () {
            // Capturar ciclo DESPUÉS del flush (el combo ya debe estar estable).
            var ciclo = String(cicloActualCodigo() || '').trim();
            CC._capturaLoadGen = (CC._capturaLoadGen || 0) + 1;
            var gen = CC._capturaLoadGen;
            CC._seedDoneFor = {};
            CC._capturaReady = false;
            CC.state.overlays = {};
            CC.state.budgets = {};
            CC.state.budgetsBase = {};
            CC.state.budgetKeysFromServer = {};
            CC.state.completados = {};
            CC.state.preciosMeses = {};
            CC.state.cicloCodigo = ciclo || CC.state.cicloCodigo || '';
            if (!ciclo) {
                CC._capturaReady = true;
                return;
            }
            return fetch('/ProyeccionesVentas/api/captura?ciclo=' + encodeURIComponent(ciclo), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) {
                if (!r.ok) throw new Error('captura ' + r.status);
                return r.json();
            }).then(function (json) {
                // Solo invalidar por generación (cambio de ciclo real). No comparar el select
                // otra vez: renderCicloPicks puede tocar el valor sin ser un cambio de ciclo.
                if (gen !== CC._capturaLoadGen) return { applied: false };
                CC.state.overlays = json.overlays || {};
                CC.state.completados = json.completados || {};
                CC.state.ajustes = json.ajustes || {};
                CC.state.costos = json.costos || {};
                CC.state.costosMaster = json.costosMaster || {};
                CC.state.costosMeses = json.costosMeses || {};
                CC.state.preciosMeses = json.preciosMeses || {};
                CC.state.budgets = {};
                CC.state.budgetKeysFromServer = {};
                var rawBudgets = json.budgets || {};
                Object.keys(rawBudgets).forEach(function (k) {
                    CC.state.budgets[k] = normalizeLoadedMonths(rawBudgets[k], !!CC.state.completados[k]);
                    CC.state.budgetKeysFromServer[String(k).toUpperCase()] = true;
                });
                Object.keys(CC.state.completados || {}).forEach(function (k) {
                    if (CC.state.completados[k]) CC.state.budgetKeysFromServer[String(k).toUpperCase()] = true;
                });
                unscaleQtyBudgetsIfNeeded();
                return loadBudgetsBaseForSeed(gen, ciclo).then(function () {
                    return { applied: true, ciclo: ciclo };
                });
            }).then(function (result) {
                if (gen !== CC._capturaLoadGen) return;
                CC._capturaReady = true;
                if (result && result.applied) {
                    refreshCapturaAfterBudgetsLoaded();
                }
            }).catch(function (err) {
                if (gen !== CC._capturaLoadGen) return;
                console.warn('[PV] No se pudo cargar captura del ciclo', ciclo, err);
                // No vaciar budgets si ya había datos de una carga buena previa del mismo gen.
                CC._capturaReady = true;
            });
        });
    }

    function refreshCapturaAfterBudgetsLoaded() {
        if (!(val('ctl-empresa') && val('ctl-centro'))) return;
        if (!control.centro) return;
        control._allCtas = cuentasEnriquecidas(control.centro);
        seedPresupuestoDesdeVentaReal(control.centro);
        updateControlProgress();
        renderNavCuentas();
        renderCtaChips();
        renderCapturaForm();
        renderControlTable();
        renderControlCharts();
        renderVisorTable();
    }

    /** Ciclo base para heredar cantidades (Forecast/SIOP). */
    function cicloBaseProyeccion() {
        var current = cicloActualCodigo();
        var cur = findCiclo(current) || CC.state.period || {};
        var tipo = cur.tipoBudget || budgetTipo();
        if (!esForecastTipo(tipo) && !esSiopTipo(tipo)) return '';
        var anio = Number(cur.anio || CC.state.anioPresupuesto) || 0;
        var curId = Number(cur.id) || 0;
        var ciclos = CC.state.ciclos || [];
        var sameYear = ciclos.filter(function (c) {
            return String(c.codigo) !== String(current) && (!anio || Number(c.anio) === anio);
        });

        // SIOP: tomar el ciclo anterior del mismo año (el de mayor id < actual),
        // incluyendo Forecast 3+9 / 6+6 / 9+3. Si no hay, el más reciente del año previo.
        if (esSiopTipo(tipo)) {
            var ranked = sameYear.slice().sort(function (a, b) {
                var idA = Number(a.id) || 0;
                var idB = Number(b.id) || 0;
                if (curId) {
                    var beforeA = idA > 0 && idA < curId ? 1 : 0;
                    var beforeB = idB > 0 && idB < curId ? 1 : 0;
                    if (beforeA !== beforeB) return beforeB - beforeA;
                }
                return idB - idA;
            });
            if (ranked.length) return ranked[0].codigo;
            var prevYear = ciclos.filter(function (c) {
                return String(c.codigo) !== String(current) && Number(c.anio) === (anio - 1);
            }).sort(function (a, b) {
                return (Number(b.id) || 0) - (Number(a.id) || 0);
            });
            return (prevYear[0] && prevYear[0].codigo) || '';
        }

        // Forecast: preferir SIOP / Budget del mismo año (no otro Forecast).
        var siop = sameYear.filter(function (c) { return String(c.tipoBudget || '') === 'SIOP'; });
        if (siop.length) return siop[0].codigo;
        var plain = sameYear.filter(function (c) {
            var t = String(c.tipoBudget || '');
            return !t || (!esForecastTipo(t) && t !== 'SIOP');
        });
        if (plain.length) return plain[0].codigo;
        var other = sameYear.filter(function (c) { return !esForecastTipo(c.tipoBudget); });
        return (other[0] && other[0].codigo) || '';
    }

    function loadBudgetsBaseForSeed(gen, cicloEsperado) {
        var base = cicloBaseProyeccion();
        CC.state.budgetsBase = {};
        CC.state.cicloBaseCodigo = base || '';
        if (!base) return Promise.resolve();
        return fetch('/ProyeccionesVentas/api/captura?ciclo=' + encodeURIComponent(base), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            if (!r.ok) throw new Error('base ' + r.status);
            return r.json();
        }).then(function (json) {
            if (gen != null && gen !== CC._capturaLoadGen) return;
            var raw = json.budgets || {};
            Object.keys(raw).forEach(function (k) {
                CC.state.budgetsBase[k] = normalizeLoadedMonths(raw[k], false);
            });
        }).catch(function () {
            if (gen != null && gen !== CC._capturaLoadGen) return;
            CC.state.budgetsBase = {};
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

    /** Normaliza tipo de budget del ciclo ('' = sin bloqueo Forecast). */
    function normalizeTipoBudgetClient(t) {
        t = String(t || '').trim();
        if (t === '3+9' || t === '6+6' || t === '9+3' || t === 'SIOP') return t;
        return '';
    }

    /**
     * Aplica el periodo del ciclo elegido. Sustituye el period completo para no
     * heredar tipoBudget / años del ciclo anterior (bug: quedaban 6 meses bloqueados).
     */
    function applyPeriodFromCiclo(period, codigoFallback) {
        var next = period ? Object.assign({}, period) : {};
        if (!next.codigo && codigoFallback) next.codigo = codigoFallback;
        next.tipoBudget = normalizeTipoBudgetClient(next.tipoBudget);
        CC.state.period = next;
        syncFxMesesFromPeriod(CC.state.period);
        if (next.anioReferencia) CC.state.anioGasto = next.anioReferencia;
        if (next.anio) CC.state.anioPresupuesto = next.anio;
        if (next.codigo) CC.state.cicloCodigo = next.codigo;
        persistPeriod();
        return next;
    }

    function upsertCiclo(data) {
        var list = CC.state.ciclos || [];
        var i = -1;
        list.forEach(function (c, idx) {
            if (String(c.codigo).toUpperCase() === String(data.codigo).toUpperCase()) i = idx;
        });
        var row = Object.assign({}, i >= 0 ? list[i] : {}, data);
        row.tipoBudget = normalizeTipoBudgetClient(row.tipoBudget);
        if (i >= 0) list[i] = row;
        else list.unshift(row);
        CC.state.ciclos = list;
        saveJSON(SK.ciclos, list);
    }

    function cicloUrl(codigo) {
        return '/Ventas/Asignaciones/' + encodeURIComponent(codigo);
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
            tipoBudget: val('p-tipo-budget') || '3+9',
            observaciones: val('p-obs')
        };
    }

    function budgetTipo() {
        return normalizeTipoBudgetClient(CC.state.period && CC.state.period.tipoBudget);
    }

    function esForecastTipo(t) {
        t = String(t || budgetTipo() || '');
        return t === '3+9' || t === '6+6' || t === '9+3';
    }

    function esSiopTipo(t) {
        return String(t || budgetTipo() || '') === 'SIOP';
    }

    /** Meses a precargar desde venta real del año en curso (índices 0..n-1). SIOP = 0. */
    function budgetSeedCount() {
        var t = budgetTipo();
        if (t === '3+9') return 3;
        if (t === '6+6') return 6;
        if (t === '9+3') return 9;
        return 0;
    }

    /** Meses bloqueados (venta real fija). En SIOP ninguno. */
    function budgetLockCount() {
        var t = budgetTipo();
        if (t === '3+9') return 3;
        if (t === '6+6') return 6;
        if (t === '9+3') return 9;
        return 0;
    }

    function mesBloqueadoBudget(monthIdx) {
        var i = Number(monthIdx);
        if (!isFinite(i) || i < 0) return false;
        return i < budgetLockCount();
    }

    function labelTipoBudget(t) {
        t = String(t || budgetTipo() || '');
        if (t === '3+9') return 'Forecast 3+9';
        if (t === '6+6') return 'Forecast 6+6';
        if (t === '9+3') return 'Forecast 9+3';
        if (t === 'SIOP') return 'SIOP';
        return '—';
    }

    function descTipoBudget(t) {
        t = String(t || budgetTipo() || '');
        if (t === '3+9') {
            return '<strong>Forecast 3+9:</strong> los 3 primeros meses (ene–mar) se cargan con la venta real del año en curso y quedan fijos. Los 9 restantes se cargan del Budget y se pueden modificar.';
        }
        if (t === '6+6') {
            return '<strong>Forecast 6+6:</strong> los 6 primeros meses (ene–jun) se cargan con la venta real del año en curso y quedan fijos. Los 6 restantes se cargan del Budget y se pueden modificar.';
        }
        if (t === '9+3') {
            return '<strong>Forecast 9+3:</strong> los 9 primeros meses (ene–sep) se cargan con la venta real del año en curso y quedan fijos. Los 3 restantes se cargan del Budget y se pueden modificar.';
        }
        if (t === 'SIOP') {
            return '<strong>SIOP:</strong> se precargan las cantidades del ciclo anterior (si no hay, la venta real). Mes sin cantidad → 0. Todos los meses son editables.';
        }
        return 'Selecciona el tipo de forecast o SIOP.';
    }

    function syncTipoBudgetModalUi() {
        var tipo = val('p-tipo-budget') || '3+9';
        var desc = document.getElementById('p-tipo-budget-desc');
        if (desc) desc.innerHTML = descTipoBudget(tipo);
        var hint = document.getElementById('p-tipo-budget-hint');
        if (hint && !document.getElementById('p-tipo-budget').disabled) {
            hint.textContent = esSiopTipo(tipo)
                ? 'SIOP: precarga ciclo anterior (o venta real) · meses vacíos en 0 · todos editables.'
                : 'Forecast: venta real fija en los primeros meses · el resto viene del Budget (editable).';
        }
    }

    /**
     * Al abrir Captura de un cliente: solo precarga si el producto NO tiene fila
     * guardada en este ciclo. Nunca pisa capturas ya persistidas al cambiar de ciclo.
     * SIOP: 12 meses desde ciclo anterior; si el mes no tiene cantidad → 0.
     * Si el producto no existe en el ciclo anterior → venta real del año de referencia (o 0).
     */
    function budgetTieneFilaEnServidor(empresa, cc, cuenta) {
        var map = CC.state.budgetKeysFromServer || {};
        var primary = budgetKey(empresa, cc, cuenta).toUpperCase();
        if (map[primary]) return true;
        return Object.keys(map).some(function (k) {
            return String(k).toUpperCase() === primary;
        });
    }

    function budgetNecesitaSeed(empresa, cc, cuenta, months) {
        if (budgetTieneFilaEnServidor(empresa, cc, cuenta)) return false;
        if (months && months.some(mesLleno)) return false;
        return true;
    }

    function baseBudgetMonthsOf(empresa, cc, cuenta) {
        var map = CC.state.budgetsBase || {};
        var key = resolveStateKey(map, empresa, cc, cuenta);
        var row = map[key];
        return Array.isArray(row) ? row.slice() : null;
    }

    function gastoListoParaSeed() {
        return !!(control._gastoReq && control._gastoLoadedFor &&
            String(control._gastoLoadedFor) === String(control._gastoReq));
    }

    function seedPresupuestoDesdeVentaReal(c) {
        if (!c || control.locked) return;
        if (!CC._capturaReady) return;
        var cicloSeed = cicloActualCodigo();
        if (!cicloSeed) return;
        var tipo = budgetTipo();
        if (!tipo) {
            if ((control._allCtas || []).length) {
                console.warn('[PV] Ciclo sin tipoBudget; no se precarga Forecast/SIOP');
            }
            return;
        }
        var n = budgetSeedCount(); // 0 en SIOP (solo Budget); 3/6/9 en Forecast
        var seedKey = String(cicloSeed).toUpperCase() + '|' + budgetKey(c.empresa, c.codigo, '*');
        CC._seedDoneFor = CC._seedDoneFor || {};
        if (CC._seedDoneFor[seedKey]) return;

        var ctas = control._allCtas || [];
        if (!ctas.length) return;
        var seeded = 0;
        var fromBase = 0;
        var fromVenta = 0;
        var waitingGasto = false;
        var loadGen = CC._capturaLoadGen || 0;
        ctas.forEach(function (cta) {
            var existing = budgetMonthsOf(c.empresa, c.codigo, cta.codigo);
            if (!budgetNecesitaSeed(c.empresa, c.codigo, cta.codigo, existing)) return;
            var baseRow = baseBudgetMonthsOf(c.empresa, c.codigo, cta.codigo);
            var hasBaseQty = !!(baseRow && baseRow.some(mesLleno));
            var gasto = cta.gasto || [];
            // SIOP sin fila en ciclo anterior: esperar venta real para no grabar ceros prematuros.
            if (esSiopTipo(tipo) && !hasBaseQty && !gastoListoParaSeed()) {
                waitingGasto = true;
                return;
            }
            var months = [];
            var usedBase = false;
            var usedVenta = false;
            for (var i = 0; i < 12; i++) {
                if (esSiopTipo(tipo)) {
                    if (hasBaseQty) {
                        // Ciclo anterior: cantidad si existe, si no 0.
                        months.push(mesLleno(baseRow[i]) ? toStoreQty(Number(baseRow[i]) || 0) : 0);
                        usedBase = true;
                    } else {
                        // Sin proyección previa del producto → venta real (o 0).
                        months.push(toStoreQty(Number(gasto[i]) || 0));
                        usedVenta = true;
                    }
                } else if (i < n) {
                    // Forecast: primeros N meses = venta real del año en curso (fijos).
                    months.push(toStoreQty(Number(gasto[i]) || 0));
                    usedVenta = true;
                } else if (baseRow && mesLleno(baseRow[i])) {
                    // Forecast: resto = Budget (editable).
                    months.push(toStoreQty(Number(baseRow[i]) || 0));
                    usedBase = true;
                } else {
                    months.push(null);
                }
            }
            // SIOP siempre persiste los 12 meses (cantidad o 0). Forecast: solo si hay algo.
            if (!esSiopTipo(tipo) && !months.some(mesLleno)) return;
            var key = budgetKey(c.empresa, c.codigo, cta.codigo);
            CC.state.budgets = CC.state.budgets || {};
            CC.state.budgets[key] = months.slice();
            CC.state.budgetKeysFromServer = CC.state.budgetKeysFromServer || {};
            CC.state.budgetKeysFromServer[key.toUpperCase()] = true;
            persistBudget(c.empresa, c.codigo, cta.codigo, months, { allowLocked: true });
            seeded += 1;
            if (usedBase) fromBase += 1;
            if (usedVenta) fromVenta += 1;
        });
        // Si aún falta la venta SAP para completar SIOP, reintentar cuando llegue applyGastoMap.
        if (!waitingGasto) CC._seedDoneFor[seedKey] = true;
        if (seeded && loadGen === (CC._capturaLoadGen || 0)
            && String(cicloActualCodigo() || '').toUpperCase() === String(cicloSeed).toUpperCase()) {
            control._allCtas = cuentasEnriquecidas(c);
            var parts = [];
            if (esSiopTipo(tipo)) {
                if (fromBase) {
                    parts.push('ciclo anterior' + (CC.state.cicloBaseCodigo ? (' · ' + CC.state.cicloBaseCodigo) : '') +
                        ' en ' + fromBase + (fromBase === 1 ? ' producto' : ' productos'));
                }
                if (fromVenta) {
                    parts.push('venta ' + (CC.state.anioGasto || '') + ' en ' + fromVenta +
                        (fromVenta === 1 ? ' producto' : ' productos'));
                }
                parts.push('meses sin cantidad → 0');
                parts.push('todos los meses editables');
            } else {
                if (fromVenta) {
                    parts.push(n + ' mes' + (n === 1 ? '' : 'es') + ' de venta ' + (CC.state.anioGasto || '') + ' (fijos)');
                }
                if (fromBase) {
                    parts.push('resto desde Budget' + (CC.state.cicloBaseCodigo ? (' · ' + CC.state.cicloBaseCodigo) : ''));
                }
                if (budgetLockCount()) parts.push('meses iniciales bloqueados');
            }
            toast('success', labelTipoBudget(tipo), 'Se precargaron ' + seeded +
                (seeded === 1 ? ' producto' : ' productos') +
                (parts.length ? (' · ' + parts.join(' · ')) : ''));
            renderCapturaForm();
            updateControlProgress();
        }
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
                    '<div><small>Año proyección</small><strong>' + (c.anio || '—') + '</strong></div>' +
                    '<div><small>Budget</small><strong>' + escapeHtml(labelTipoBudget(c.tipoBudget)) + '</strong></div>' +
                    '<div><small>Tipo de cambio</small><strong>USD $' + Number(c.tipoCambio || 0).toFixed(2) + '</strong></div>' +
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
        fetch('/ProyeccionesVentas/api/ciclos/' + encodeURIComponent(codigo) + '/estado', {
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
            return fetch('/ProyeccionesVentas/api/ciclos/' + encodeURIComponent(c.codigo), {
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
                    window.location = '/Ventas/Asignaciones';
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
        setText('period-fx', 'USD $' + Number(p.tipoCambio || 0).toFixed(2) +
            (p.tipoBudget ? (' · Budget ' + labelTipoBudget(p.tipoBudget)) : ''));
        setText('th-gasto', 'Venta ' + (p.anioReferencia || 2026));
        setText('th-ppto', 'Proy. ' + (p.anio || 2027));
        setText('ind-inflacion', (p.inflacion || 0) + ' %');
        syncFxMesesFromPeriod(p);
        var baseEl = document.getElementById('ind-tc-base');
        if (baseEl) baseEl.value = String(fxBaseRate());
        setText('ind-anio-ref', p.anioReferencia || 2026);
        setText('ind-anio', p.anio || 2027);
        paintFxBadge();
        var kicker = document.querySelector('.cc-kicker');
        if (kicker && CC.state.page === 'admin-ciclo') {
            kicker.innerHTML = 'Ciclo ' + escapeHtml(p.codigo || '') + ' · centros y permisos';
        }
    }

    function renderTcMesesEditor() {
        syncFxMesesFromPeriod(CC.state.period);
        var box = document.getElementById('ind-tc-months');
        var baseEl = document.getElementById('ind-tc-base');
        if (baseEl) baseEl.value = fxBaseRate().toFixed(2);
        if (!box) return;
        var meses = ensureFxMeses();
        box.innerHTML = MONTHS.map(function (m, i) {
            var v = Number(meses[i]) || fxBaseRate();
            var custom = Math.abs(v - fxBaseRate()) > 0.0001;
            return '<div class="cc-tc-month' + (custom ? ' is-custom' : '') + '">' +
                '<label for="ind-tc-m-' + i + '">' + m + '</label>' +
                '<input id="ind-tc-m-' + i + '" type="number" step="0.01" min="0.01" value="' + v.toFixed(2) + '">' +
                '</div>';
        }).join('');
    }

    function readTcMesesFromEditor() {
        var base = Number(val('ind-tc-base'));
        if (!(base > 0)) base = fxBaseRate();
        var out = [];
        for (var i = 0; i < 12; i++) {
            var el = document.getElementById('ind-tc-m-' + i);
            var v = el ? Number(el.value) : base;
            out.push(v > 0 ? Math.round(v * 10000) / 10000 : base);
        }
        return { base: base, meses: out };
    }

    function applyTcBaseToMonthsEditor() {
        var base = Number(val('ind-tc-base'));
        if (!(base > 0)) base = fxBaseRate();
        for (var i = 0; i < 12; i++) {
            var el = document.getElementById('ind-tc-m-' + i);
            if (el) el.value = base.toFixed(2);
        }
        renderTcMesesEditorHighlight();
    }

    function renderTcMesesEditorHighlight() {
        var base = Number(val('ind-tc-base')) || fxBaseRate();
        MONTHS.forEach(function (_, i) {
            var wrap = document.querySelector('#ind-tc-months .cc-tc-month:nth-child(' + (i + 1) + ')');
            var el = document.getElementById('ind-tc-m-' + i);
            if (!wrap || !el) return;
            var v = Number(el.value) || base;
            wrap.classList.toggle('is-custom', Math.abs(v - base) > 0.0001);
        });
    }

    function saveTcMeses() {
        var pack = readTcMesesFromEditor();
        var codigo = cicloActualCodigo();
        if (!codigo) {
            toast('warning', 'Sin ciclo', 'Elige un ciclo antes de guardar el tipo de cambio.');
            return Promise.resolve(false);
        }
        return fetch('/ProyeccionesVentas/api/ciclos/' + encodeURIComponent(codigo) + '/tipo-cambio-meses', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken()
            },
            body: JSON.stringify({
                tipoCambio: pack.base,
                tipoCambioMeses: pack.meses
            })
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok) throw new Error((json && json.message) || 'No se pudo guardar el TC');
                var ciclo = json.ciclo || {};
                CC.state.period = Object.assign({}, CC.state.period || {}, ciclo);
                syncFxMesesFromPeriod(CC.state.period);
                upsertCiclo(CC.state.period);
                persistPeriod();
                paintFxBadge();
                renderPeriodBanner();
                if (CC.state.page === 'control') {
                    if (control.centro) {
                        control._allCtas = cuentasEnriquecidas(control.centro);
                        updateControlProgress();
                        renderCapturaForm();
                        renderControlTable();
                    }
                }
                toast('success', 'TC guardado', 'Base ' + pack.base.toFixed(2) + (fxMesesVarian() ? ' · con variaciones mensuales' : ' · 12 meses iguales'));
                return true;
            });
        }).catch(function (err) {
            toast('danger', 'Error', err.message || 'No se pudo guardar');
            return false;
        });
    }

    function openTcMesesModal() {
        renderTcMesesEditor();
        showModal('modalIndicadores');
    }

    function bindTcMesesUi() {
        if (CC._tcMesesBound) return;
        CC._tcMesesBound = true;
        ['ctl-fx-badge', 'ctl-fx-nav'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('click', function () { openTcMesesModal(); });
            el.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    openTcMesesModal();
                }
            });
        });
        var applyAll = document.getElementById('ind-tc-apply-all');
        if (applyAll) applyAll.addEventListener('click', function () {
            var base = Number(val('ind-tc-base'));
            if (!(base > 0)) {
                toast('warning', 'TC base', 'Captura un tipo de cambio base válido.');
                return;
            }
            for (var i = 0; i < 12; i++) {
                var el = document.getElementById('ind-tc-m-' + i);
                if (el) el.value = base.toFixed(2);
            }
            renderTcMesesEditorHighlight();
        });
        var reset = document.getElementById('ind-tc-reset');
        if (reset) reset.addEventListener('click', function () {
            var base = fxBaseRate();
            var baseEl = document.getElementById('ind-tc-base');
            if (baseEl) baseEl.value = base.toFixed(2);
            for (var i = 0; i < 12; i++) {
                var el = document.getElementById('ind-tc-m-' + i);
                if (el) el.value = base.toFixed(2);
            }
            renderTcMesesEditorHighlight();
        });
        var save = document.getElementById('ind-tc-save');
        if (save) save.addEventListener('click', function () {
            save.disabled = true;
            saveTcMeses().then(function (ok) {
                save.disabled = false;
                if (ok) {
                    var modal = document.getElementById('modalIndicadores');
                    if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).hide();
                }
            });
        });
        var monthsBox = document.getElementById('ind-tc-months');
        if (monthsBox) {
            monthsBox.addEventListener('input', function (ev) {
                if (ev.target && ev.target.id && ev.target.id.indexOf('ind-tc-m-') === 0) {
                    renderTcMesesEditorHighlight();
                }
            });
        }
    }

    function openPrecioMesesModal(codigo) {
        var c = control.centro;
        if (!c || !codigo) return;
        var cta = (control._allCtas || []).filter(function (x) { return String(x.codigo) === String(codigo); })[0];
        if (!cta) {
            toast('warning', 'Producto', 'No se encontró el producto.');
            return;
        }
        control._precioEditCodigo = String(codigo);
        selectCuenta(codigo);
        var sub = document.getElementById('pm-sub');
        if (sub) sub.textContent = labelNombreCodigo(cta.nombre, cta.codigo);
        var base = Number(cta.precioLista) || 0;
        var mon = String(cta.precioMoneda || 'MXN').toUpperCase();
        var baseEl = document.getElementById('pm-base');
        if (baseEl) baseEl.value = base > 0 ? base.toFixed(4) : '';
        var baseHint = document.getElementById('pm-base-hint');
        if (baseHint) {
            baseHint.textContent = base > 0
                ? ('Global: ' + moneyLista(base, mon) + (cta.unidad ? ' / ' + cta.unidad : '') + ' · deja vacío un mes para usar este valor')
                : 'Sin precio de lista; captura un valor base o por mes.';
        }
        var box = document.getElementById('pm-months');
        if (box) {
            var meses = cta.precioMeses || preciosMesesDe(c.empresa, c.codigo, codigo);
            var maestro = lookupMaestroMeses(c.empresa, c.codigo, codigo);
            var refMeses = (maestro && maestro.meses) || [];
            var refMons = (maestro && maestro.monedas) || [];
            box.innerHTML = MONTHS.map(function (m, i) {
                var ov = Number(meses[i]);
                var custom = isFinite(ov) && ov > 0;
                var shown = custom ? ov : base;
                var ref = Number(refMeses[i]);
                var refMon = String(refMons[i] || mon || 'MXN').toUpperCase();
                var refHtml = (isFinite(ref) && ref > 0)
                    ? ('<div class="cc-tc-month-ref" title="Precio en Precios de productos (mes ' + (i + 1) + ')">' +
                        escapeHtml(moneyLista(ref, refMon)) + '</div>')
                    : '<div class="cc-tc-month-ref is-empty" title="Sin precio en Precios de productos para este mes">—</div>';
                return '<div class="cc-tc-month' + (custom ? ' is-custom' : '') + '">' +
                    '<label for="pm-m-' + i + '">' + m + '</label>' +
                    refHtml +
                    '<input id="pm-m-' + i + '" type="number" step="0.0001" min="0" placeholder="' +
                    (base > 0 ? base.toFixed(2) : '0') + '" value="' + (custom ? shown.toFixed(4) : '') + '">' +
                    '</div>';
            }).join('');
        }
        highlightPrecioMesesEditor();
        showModal('modalPrecioMeses');
    }

    function highlightPrecioMesesEditor() {
        var base = Number(val('pm-base')) || 0;
        MONTHS.forEach(function (_, i) {
            var wrap = document.querySelector('#pm-months .cc-tc-month:nth-child(' + (i + 1) + ')');
            var el = document.getElementById('pm-m-' + i);
            if (!wrap || !el) return;
            var raw = String(el.value || '').trim();
            var v = Number(raw);
            var custom = raw !== '' && isFinite(v) && v > 0 && Math.abs(v - base) > 0.0001;
            wrap.classList.toggle('is-custom', custom || (raw !== '' && isFinite(v) && v > 0));
        });
    }

    function readPrecioMesesFromEditor() {
        var out = [];
        for (var i = 0; i < 12; i++) {
            var el = document.getElementById('pm-m-' + i);
            var raw = el ? String(el.value || '').trim() : '';
            var v = Number(raw);
            out.push(raw !== '' && isFinite(v) && v > 0 ? Math.round(v * 10000) / 10000 : null);
        }
        return out;
    }

    function bindPrecioMesesUi() {
        if (CC._precioMesesBound) return;
        CC._precioMesesBound = true;
        var applyAll = document.getElementById('pm-apply-all');
        if (applyAll) applyAll.addEventListener('click', function () {
            var base = Number(val('pm-base'));
            if (!(base > 0)) {
                toast('warning', 'Precio base', 'Captura un precio global válido.');
                return;
            }
            for (var i = 0; i < 12; i++) {
                var el = document.getElementById('pm-m-' + i);
                if (el) el.value = '';
            }
            highlightPrecioMesesEditor();
        });
        var reset = document.getElementById('pm-reset');
        if (reset) reset.addEventListener('click', function () {
            for (var i = 0; i < 12; i++) {
                var el = document.getElementById('pm-m-' + i);
                if (el) el.value = '';
            }
            highlightPrecioMesesEditor();
        });
        var save = document.getElementById('pm-save');
        if (save) save.addEventListener('click', function () {
            var c = control.centro;
            var codigo = control._precioEditCodigo || control.cuenta;
            if (!c || !codigo || control.locked) {
                toast('warning', 'Sin permiso', 'No se puede editar el precio en este momento.');
                return;
            }
            var meses = readPrecioMesesFromEditor();
            persistPrecioMesesProducto(c.empresa, c.codigo, codigo, meses);
            if (control._allCtas) {
                control._allCtas = cuentasEnriquecidas(c);
            }
            updateMatrixRow(codigo);
            renderControlTable();
            renderControlCharts();
            var modal = document.getElementById('modalPrecioMeses');
            if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).hide();
            toast('success', 'Precio guardado', precioMesesVarian({ precioMeses: meses, precioLista: Number(val('pm-base')) || 0 })
                ? 'Hay meses con precio distinto al global'
                : 'Los 12 meses usan el precio global');
        });
        var monthsBox = document.getElementById('pm-months');
        if (monthsBox) {
            monthsBox.addEventListener('input', function (ev) {
                if (ev.target && ev.target.id && ev.target.id.indexOf('pm-m-') === 0) {
                    highlightPrecioMesesEditor();
                }
            });
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
            tb.innerHTML = '<tr><td colspan="9"><div class="cc-empty"><i class="fa-solid fa-inbox"></i>Sin clientes con esos filtros</div></td></tr>';
            return;
        }
        tb.innerHTML = rows.map(function (c) {
            var ctas = cuentasDeCentro(c);
            var ppto = totPptoCentro(c, ctas);
            var gasto = totGastoCentro(c, ctas) || c.gasto2026 || 0;
            return '<tr data-key="' + centroKey(c) + '">' +
                '<td><div class="fw-semibold">' + c.codigo + ' — ' + escapeHtml(c.nombre) + '</div><div class="text-muted" style="font-size:.75rem">' + ctas.length + ' productos · ' + (c.sap ? 'SAP' : 'catálogo') + '</div></td>' +
                '<td>' + escapeHtml(c.empresa) + '</td>' +
                '<td>' + escapeHtml(c.departamento || '—') + '</td>' +
                '<td>' + userCell(c.usuario) + '</td>' +
                '<td>' + renderBadge(c.estado) + '</td>' +
                '<td>' + (c.modo === 'solo_revision' ? '<span class="cc-badge cc-badge-solo_revision">Solo revisar</span>' : '<span class="cc-badge cc-badge-captura">Captura</span>') + '</td>' +
                '<td class="num">' + money(gasto) + '</td>' +
                '<td class="num">' + money(ppto) + '</td>' +
                '<td><div class="cc-row-actions">' +
                    '<button class="cc-icon-btn" data-act="detalle" title="Detalle"><i class="fa-solid fa-eye"></i></button>' +
                    '<button class="cc-icon-btn" data-act="cuentas" title="Productos"><i class="fa-solid fa-list"></i></button>' +
                    '<button class="cc-icon-btn" data-act="permisos" title="Permisos"><i class="fa-solid fa-user-lock"></i></button>' +
                    '<a class="cc-icon-btn" href="/Ventas/Captura?empresa=' + encodeURIComponent(c.empresa) + '&cc=' + encodeURIComponent(c.codigo) + '&ciclo=' + encodeURIComponent(CC.state.period.codigo || '') + '" title="Proyectar"><i class="fa-solid fa-pen-to-square"></i></a>' +
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

    function totPptoCentro(c, ctas) {
        return (ctas || cuentasEnriquecidas(c)).reduce(function (a, cta) {
            if (cta.importeProy != null) return a + Number(cta.importeProy || 0);
            var full = Object.assign({}, cta, {
                ppto: cta.ppto || pptoDe(c.empresa, c.codigo, cta, cta.gasto),
                precioLista: cta.precioLista != null ? cta.precioLista : Number((lookupPrecioLista(cta.codigo) || {}).precio) || 0,
                precioMoneda: cta.precioMoneda || String((lookupPrecioLista(cta.codigo) || {}).moneda || 'MXN').toUpperCase()
            });
            return a + importeProyeccionMxn(full);
        }, 0);
    }

    function totGastoCentro(c, ctas) {
        return (ctas || cuentasEnriquecidas(c)).reduce(function (a, cta) {
            if (cta.importeVenta != null) return a + Number(cta.importeVenta || 0);
            return a + importeVentaMxn(cta);
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
                applyPeriodFromCiclo(Object.assign({}, data, ciclo || {}), data.codigo);
                hideModal('modalPeriodo');
                if (CC.state.page === 'admin') {
                    toast('success', 'Ciclo abierto', data.codigo + ' · ' + labelTipoBudget(data.tipoBudget) + ' · ' + data.nombre);
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
        var tipoEl = document.getElementById('p-tipo-budget');
        if (tipoEl) tipoEl.addEventListener('change', syncTipoBudgetModalUi);
        var anioEl = document.getElementById('p-anio');
        if (anioEl) anioEl.addEventListener('change', syncTipoBudgetModalUi);
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
            item('Productos', ctas.length) +
            item('Venta 2026', money(gasto)) + item('Proy. 2027', money(ppto)) +
            '</div>' +
            '<div class="mb-2 d-flex justify-content-between"><span class="text-muted">Avance vs venta 2026</span><strong>' + av + '%</strong></div>' +
            '<div class="cc-progress ' + (av < 40 ? 'warn' : 'good') + '"><span style="width:' + Math.min(av, 100) + '%"></span></div>' +
            '<div class="mt-3 d-flex gap-2 flex-wrap">' +
            '<a class="cc-btn cc-btn-ink" href="/Ventas/Captura?empresa=' + encodeURIComponent(c.empresa) + '&cc=' + encodeURIComponent(c.codigo) + '&ciclo=' + encodeURIComponent((CC.state.period && CC.state.period.codigo) || '') + '">Ir a captura</a>' +
            '<a class="cc-btn" href="/Ventas/Analisis">Ver análisis</a></div>';
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
                    '<small>' + (cta.grupo || '') + (cta.empresa ? ' · ' + cta.empresa : '') + '</small></span></label>';
            }).join('') || '<div class="cc-empty">Sin productos</div>';
        }
        qInput.oninput = draw;
        draw();
        document.getElementById('cta-title').textContent = 'Productos · ' + c.codigo + ' ' + c.nombre;
        document.getElementById('form-cuentas').onsubmit = function (e) {
            e.preventDefault();
            var selected = Array.prototype.map.call(document.querySelectorAll('#cta-list input:checked'), function (i) { return i.value; });
            persistOverlay(c, { cuentasAsignadas: selected });
            hideModal('modalCuentas');
            renderAdminTable();
            toast('success', 'Productos asignados', selected.length + ' productos en el catálogo del cliente');
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
    var control = { centro: null, cuenta: null, soloPendientes: false, grupoCerrado: {}, grupoCerradoDet: {}, grupoCerradoMatrix: {}, vista: 'captura', scopeTabla: 'centro', chartVerTodas: false, prodQuery: '' };

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
            var emp = String(a.empresa || '').toUpperCase().trim();
            var code = String(a.centro_codigo || '').trim();
            var k = emp + '|' + code;
            if (!emp || !code) return;
            if (!map[k]) {
                map[k] = {
                    codigo: code,
                    nombre: a.centro_nombre || code,
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
            var d = scoreTemporadaCaptura(b.codigo) - scoreTemporadaCaptura(a.codigo);
            if (d) return d;
            var tb = findCiclo(b.codigo) || {};
            var ta = findCiclo(a.codigo) || {};
            var hasB = tb.tipoBudget ? 1 : 0;
            var hasA = ta.tipoBudget ? 1 : 0;
            if (hasB !== hasA) return hasB - hasA;
            return (Number(tb.id) || 0) - (Number(ta.id) || 0);
        });
        return (ranked[0] && ranked[0].codigo) || '';
    }

    function setCapturaEnabled(on) {
        ['ctl-guardar', 'ctl-guardar-seguir', 'ctl-copy-year', 'ctl-clear-year', 'ctl-apply-infl', 'ctl-btn-dispersar', 'ctl-completar', 'ctl-ajuste-pct', 'ctl-prod-q'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.disabled = !on;
        });
    }

    function lookupMaestroLocal(empresa, cliente, codigo) {
        var map = CC.state.costosMaster || {};
        if (!codigo) return null;
        var emp = String(empresa || '').toUpperCase();
        var card = String(cliente || '').trim();
        var cod = String(codigo || '').trim();
        var hit = null;
        if (emp && card && cod) {
            hit = map[emp + '|' + card + '|' + cod];
        }
        if (!hit && emp && cod) {
            hit = map[emp + '|' + cod];
        }
        if (!hit) {
            hit = map[cod] || map[cod.toUpperCase()] || null;
        }
        return hit || null;
    }

    /** Precios mensuales del maestro Precios de productos (Empresa+CardCode+ItemCode). */
    function lookupMaestroMeses(empresa, cliente, codigo) {
        var map = CC.state.costosMeses || {};
        if (!codigo) return null;
        var emp = String(empresa || '').toUpperCase();
        var card = String(cliente || '').trim();
        var cod = String(codigo || '').trim();
        var hit = null;
        if (emp && card && cod) hit = map[emp + '|' + card + '|' + cod];
        if (!hit && emp && cod) hit = map[emp + '|' + cod];
        if (!hit) hit = map[cod] || map[cod.toUpperCase()] || null;
        if (!hit || !hit.meses) return null;
        return hit;
    }

    function cuentasEnriquecidas(c) {
        return cuentasDeCentro(c).map(function (cta) {
            var ppto = pptoDe(c.empresa, c.codigo, cta, cta.gasto);
            var totP = sum(ppto);
            var lista = lookupPrecioLista(cta.codigo) || {};
            var master = lookupMaestroLocal(c.empresa, c.codigo, cta.codigo);
            var snap = Number((CC.state.costos || {})[budgetKey(c.empresa, c.codigo, cta.codigo)]) || 0;
            var masterPrecio = (master && Number(master.costo)) || 0;
            var masterMes = (master && Number(master.mes)) || 0;
            // Preferir precio local (último mes) sobre lista SAP.
            var precioLista = masterPrecio > 0 ? masterPrecio : (Number(lista.precio) || 0);
            var precioMoneda = '';
            if (masterPrecio > 0 && master && master.moneda) {
                precioMoneda = String(master.moneda).toUpperCase();
            } else {
                precioMoneda = String(lista.moneda || '').toUpperCase() || '';
            }
            var enriched = Object.assign({}, cta, {
                ppto: ppto,
                totG: sum(cta.gasto),
                totP: totP,
                listo: mesesTodosLlenos(ppto) || cuentaMarcada(c.empresa, c.codigo, cta.codigo),
                costo: Number(cta.costo) || Number((CC.state.costos || {})[budgetKey(c.empresa, c.codigo, cta.codigo)]) || 0,
                precioLista: precioLista,
                precioMoneda: precioMoneda,
                precioMeses: preciosMesesDe(c.empresa, c.codigo, cta.codigo),
                precioLocalMes: masterMes > 0 ? masterMes : null,
                unidad: String(lista.unidad || cta.unidad || '').trim(),
                unidadNombre: String(lista.unidad_nombre || cta.unidadNombre || '').trim(),
                listaPrecioNombre: masterPrecio > 0
                    ? ('Maestro local' + (masterMes > 0 ? (' · mes ' + masterMes) : ''))
                    : (lista.lista || '')
            });
            enriched.unidadesAnio = unidadesAnuales(enriched.ppto);
            if (!enriched.unidadNombre && enriched.unidad) {
                enriched.unidadNombre = nombreUnidadMedida(enriched.unidad);
            }
            var costoLocal = Number(cta.costo) || snap || masterPrecio || 0;
            enriched.costo = costoLocal;
            enriched.costoMoneda = (master && master.moneda) || 'MXN';
            enriched.importeProy = importeProyeccionMxn(enriched);
            enriched.importeProyVista = importeProyeccionVista(enriched);
            enriched.importeVenta = importeVentaMxn(enriched);
            enriched.importeVentaVista = importeVentaVista(enriched);
            return enriched;
        });
    }

    function statsDeCentro(c) {
        var ctas = cuentasEnriquecidas(c);
        var pend = ctas.filter(ctaPendiente).length;
        return {
            total: ctas.length,
            capturadas: ctas.length - pend,
            pendientes: pend,
            totG: ctas.reduce(function (a, x) { return a + (x.importeVenta || 0); }, 0),
            totGVista: ctas.reduce(function (a, x) {
                return a + (x.importeVentaVista != null ? x.importeVentaVista : importeVentaVista(x));
            }, 0),
            totP: ctas.reduce(function (a, x) { return a + (x.importeProy || 0); }, 0),
            totPVista: ctas.reduce(function (a, x) {
                return a + (x.importeProyVista != null ? x.importeProyVista : importeProyeccionVista(x));
            }, 0),
            totUnitsG: ctas.reduce(function (a, x) { return a + (x.totG || 0); }, 0),
            totUnitsP: ctas.reduce(function (a, x) { return a + (x.unidadesAnio != null ? x.unidadesAnio : unidadesAnuales(x.ppto)); }, 0),
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
                hint.textContent = 'Solo el producto ' + labelNombreCodigo(cta.nombre, cta.codigo) + '. Cambia a Todo el cliente para ver todos los productos.';
            } else if (control.scopeTabla === 'cuenta' && !control.cuenta) {
                hint.textContent = 'Elige un producto para filtrar el detalle, o cambia a Todo el cliente para verlos todos.';
            } else if (control.chartVerTodas) {
                hint.textContent = 'Todos los productos del cliente. La gráfica muestra el cliente completo.';
            } else if (cta) {
                hint.textContent = 'Todos los productos del cliente. La gráfica muestra ' + (cta.nombre || cta.codigo) + '. Marca Ver todas para ver el cliente completo.';
            } else {
                hint.textContent = 'Todos los productos del cliente. Elige una fila para verla en la gráfica, o marca Ver todas.';
            }
        }
        var wrap = document.getElementById('ctl-chart-todas-wrap');
        if (wrap) wrap.hidden = control.scopeTabla !== 'centro';
        var chk = document.getElementById('ctl-chart-todas');
        if (chk) chk.checked = !!control.chartVerTodas;
        ['ctl-chart-hint', 'det-chart-hint'].forEach(function (id) {
            var chartHint = document.getElementById(id);
            if (!chartHint) return;
            var base = 'Venta ' + CC.state.anioGasto + ' vs proyección ' + CC.state.anioPresupuesto;
            if (chartScopePref() === 'cuenta' && cta) {
                chartHint.textContent = base + ' · ' + (cta.nombre || cta.codigo);
            } else {
                chartHint.textContent = base + ' · Todo el cliente';
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
        var mesesEl = document.getElementById(prefix + '-tot-meses');
        if (!ctas) {
            setText(prefix + '-tot-gasto', '—');
            setText(prefix + '-tot-ppto', '—');
            setText(prefix + '-pend', '—');
            if (mesesEl) mesesEl.innerHTML = '';
            return;
        }
        var totG = ctas.reduce(function (a, x) {
            return a + (x.importeVentaVista != null ? x.importeVentaVista : importeVentaVista(x));
        }, 0);
        var totP = ctas.reduce(function (a, x) { return a + importeProyeccionVista(x); }, 0);
        var pend = ctas.filter(ctaPendiente).length;
        setText(prefix + '-tot-gasto', moneyGasto(totG));
        setText(prefix + '-tot-ppto', moneyGasto(totP));
        setText(prefix + '-pend', pend);

        if (mesesEl) {
            var mesG = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            var mesP = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            ctas.forEach(function (cta) {
                for (var i = 0; i < 12; i++) {
                    mesG[i] += Number(importeMesVentaVista(cta, i)) || 0;
                    var mp = importeMesProyVista(cta, i);
                    if (mp != null) mesP[i] += Number(mp) || 0;
                }
            });
            var yyG = String(CC.state.anioGasto).slice(2);
            var yyP = String(CC.state.anioPresupuesto).slice(2);
            mesesEl.innerHTML = MONTHS.map(function (m, i) {
                return '<div class="cc-sticky-mes">' +
                    '<span class="cc-sticky-mes-label">' + m + '</span>' +
                    '<span class="cc-sticky-mes-venta" title="Venta ’' + yyG + '">' + (mesG[i] ? moneyGasto(mesG[i]) : '—') + '</span>' +
                    '<span class="cc-sticky-mes-proy" title="Proy. ’' + yyP + '">' + (mesP[i] ? moneyGasto(mesP[i]) : '—') + '</span>' +
                    '</div>';
            }).join('');
        }
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
            var e = String(a.empresa || '').toUpperCase().trim();
            if (!e) return;
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
                el.className = 'cc-nav-status ' + (n > 0 ? 'is-danger' : 'is-ok');
            }
        }
        if (selectEl) {
            selectEl.classList.toggle('is-danger', has && n > 0);
            selectEl.classList.toggle('is-warn', false);
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
            var pend = CC._capturaReady ? pendientesDeEmpresa(e) : null;
            var label = e;
            if (pend !== null) label += '   ·   ' + markPendLabel(pend);
            var optClass = pend === null ? '' : (pend > 0 ? 'is-danger' : 'is-ok');
            html += '<option' + (optClass ? ' class="' + optClass + '"' : '') + ' value="' + escapeHtml(e) + '">' + escapeHtml(label) + '</option>';
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
        var emp = String(val('ctl-empresa') || '').toUpperCase().trim();
        if (!emp) {
            el.innerHTML = '<option value="">Elige una empresa primero…</option>';
            el.disabled = true;
            el.classList.remove('is-warn', 'is-ok', 'is-danger');
            paintNavStatus('ctl-cc-status', null, el);
            return;
        }
        var raw = asigsPorEmpresa(val('ctl-ciclo'))[emp] || [];
        var seenCc = {};
        var list = [];
        raw.forEach(function (a) {
            var code = String(a.centro_codigo || '').trim();
            if (!code || seenCc[code]) return;
            seenCc[code] = true;
            list.push(a);
        });
        var cur = String(val('ctl-centro') || '').trim();
        var html = '<option value="">Elige cliente…</option>';
        list.forEach(function (a) {
            var label = labelNombreCodigo(a.centro_nombre, a.centro_codigo);
            var pend = null;
            if (CC._capturaReady) {
                pend = statsDeCentro({
                    codigo: a.centro_codigo,
                    nombre: a.centro_nombre || a.centro_codigo,
                    empresa: emp
                }).pendientes;
                label += '   ·   ' + markPendLabel(pend);
            }
            var optClass = pend === null ? '' : (pend > 0 ? 'is-danger' : 'is-ok');
            html += '<option' + (optClass ? ' class="' + optClass + '"' : '') + ' value="' + escapeHtml(String(a.centro_codigo || '').trim()) + '">' + escapeHtml(label) + '</option>';
        });
        el.innerHTML = html;
        el.disabled = !list.length;
        var exists = list.filter(function (a) { return String(a.centro_codigo || '').trim() === cur; }).length;
        if (exists) el.value = cur;
        else if (CC.state.centroInicial && list.filter(function (a) { return String(a.centro_codigo || '').trim() === String(CC.state.centroInicial).trim(); }).length) {
            el.value = String(CC.state.centroInicial).trim();
        } else if (list.length === 1) el.value = String(list[0].centro_codigo || '').trim();
        else el.value = '';
        var chosen = list.filter(function (a) { return String(a.centro_codigo || '').trim() === String(el.value || '').trim(); })[0];
        paintNavStatus('ctl-cc-status', (CC._capturaReady && chosen) ? statsDeCentro({
            codigo: chosen.centro_codigo,
            nombre: chosen.centro_nombre,
            empresa: emp
        }).pendientes : null, el);
    }

    function renderNavCuentas() {
        var el = document.getElementById('ctl-cuenta');
        var qEl = document.getElementById('ctl-prod-q');
        var emp = val('ctl-empresa');
        var cc = val('ctl-centro');
        if (!emp || !cc) {
            if (el) {
                el.innerHTML = '<option value="">Elige un cliente primero…</option>';
                el.value = '';
            }
            if (qEl) {
                qEl.value = '';
                qEl.disabled = true;
            }
            paintNavStatus('ctl-cta-status', null, qEl || el);
            return;
        }
        var centro = control.centro;
        var ctas = (control._allCtas && control._allCtas.length)
            ? control._allCtas
            : (centro ? cuentasEnriquecidas(centro) : []);
        if (control.soloPendientes) ctas = ctas.filter(ctaPendiente);
        if (qEl) qEl.disabled = !ctas.length;
        if (el) {
            var html = '<option value="">Todos los productos</option>';
            ctas.forEach(function (cta) {
                var pend = ctaPendiente(cta) ? 1 : 0;
                html += '<option value="' + escapeHtml(cta.codigo) + '">' +
                    escapeHtml(labelNombreCodigo(cta.nombre, cta.codigo) + '   ·   ' + markPendLabel(pend)) + '</option>';
            });
            el.innerHTML = html;
            if (control.cuenta && ctas.filter(function (x) { return String(x.codigo) === String(control.cuenta); }).length) {
                el.value = control.cuenta;
            } else {
                el.value = '';
            }
        }
        var st = statsDeCentro({
            codigo: cc,
            nombre: '',
            empresa: emp
        });
        paintNavStatus('ctl-cta-status', st.pendientes, qEl || el);
    }

    function renderCicloPicks() {
        var el = document.getElementById('ctl-ciclo');
        if (!el) return;
        var ciclos = ciclosDeMisAsignaciones();
        var cur = String(el.value || '').trim();
        var selected = cur;
        if (!selected) {
            selected = cicloControlPreferido() || '';
        } else if (!CC._cicloUserPicked) {
            // Mantener el valor ya puesto en el combo; no forzar otro ciclo al re-render.
            selected = cur;
        }
        el.innerHTML = ciclos.map(function (c) {
            var season = cicloEnTemporadaCaptura(c.codigo);
            var label = (c.codigo ? (c.codigo + ' — ') : '') + (c.nombre || c.codigo);
            if (season) label += ' · temporada';
            return '<option value="' + escapeHtml(c.codigo) + '">' + escapeHtml(label) + '</option>';
        }).join('') || '<option value="">Sin ciclos</option>';
        if (selected) el.value = selected;
        if (el.value) CC.state.cicloCodigo = el.value;
    }

    function applyControlPath(ciclo, emp, cc) {
        var cicloSel = document.getElementById('ctl-ciclo');
        var empEl = document.getElementById('ctl-empresa');
        var ccEl = document.getElementById('ctl-centro');
        // Comparar contra el ciclo en estado (no el select): en el evento change
        // el <select> ya tiene el valor nuevo y cicloChanged quedaba siempre false.
        var prevCiclo = String(CC.state.cicloCodigo || '').trim();
        var prevEmp = empEl ? String(empEl.value || '').trim() : '';
        var prevCc = ccEl ? String(ccEl.value || '').trim() : '';
        var cicloChanged = !!(ciclo && String(ciclo) !== prevCiclo);
        if (cicloSel && ciclo) cicloSel.value = ciclo;
        function finishPath() {
            if (empEl && emp) empEl.value = emp;
            renderNavCentros();
            if (ccEl && cc) {
                ccEl.value = String(cc).trim();
            } else if (ccEl && (cicloChanged || (emp && emp !== prevEmp))) {
                // No borrar si renderNavCentros ya eligió el único cliente.
                if (!ccEl.value) control.cuenta = null;
            }
            // Si quedó vacío pero hay exactamente un cliente, tomarlo.
            if (ccEl && !String(ccEl.value || '').trim()) {
                var opts = Array.prototype.slice.call(ccEl.options || []).filter(function (o) { return o.value; });
                if (opts.length === 1) ccEl.value = opts[0].value;
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
            applyPeriodFromCiclo(findCiclo(ciclo), ciclo);
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
            fetch('/ProyeccionesVentas/api/mis-asignaciones', {
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
        syncVentasPasadasBtn();
        var ciclo = val('ctl-ciclo') || cicloControlPreferido();
        if (cicloSel && ciclo) cicloSel.value = ciclo;
        CC.state.cicloCodigo = ciclo || '';
        // Evita que re-renders posteriores cambien el ciclo sin recargar presupuestos.
        if (ciclo) CC._cicloUserPicked = true;
        applyPeriodFromCiclo(findCiclo(ciclo), ciclo);
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
            var prodQ = document.getElementById('ctl-prod-q');
            if (prodQ) prodQ.addEventListener('input', function () {
                control.prodQuery = String(this.value || '');
                renderCapturaForm();
                renderControlTable();
            });
            var qEl = document.getElementById('ctl-q');
            if (qEl) qEl.addEventListener('input', function () {
                renderCtaChips();
                renderControlTable();
            });
            document.getElementById('ctl-moneda').addEventListener('change', function () {
                CC.state.currency = this.value;
                saveJSON(SK.currency, CC.state.currency);
                paintFxBadge();
                renderCapturaForm();
                renderControlTable();
                renderControlCharts();
                updateControlProgress();
                renderVisorTable();
            });
            var pend = document.getElementById('ctl-pendientes');
            if (pend) pend.addEventListener('change', function () {
                control.soloPendientes = this.checked;
                renderNavCuentas();
                renderCtaChips();
                renderCapturaForm();
                renderControlTable();
            });
            bindControlTools();
            bindImportMasivo();
            bindCapturaQuick();
            bindVentasPasadas();
            bindVistas();
            bindVisor();
            bindScopeToggles();
            CC._controlBound = true;
        }
        var mon = document.getElementById('ctl-moneda');
        if (mon) mon.value = CC.state.currency;
        paintFxBadge();
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
            fetch('/ProyeccionesVentas/api/mis-asignaciones', {
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
            applyPeriodFromCiclo(findCiclo(ciclo), ciclo);
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
                CC.state.currency = this.value || 'MXN';
                saveJSON(SK.currency, CC.state.currency);
                paintFxBadge();
                paintDetalleHeader();
                renderDetalleTable();
                renderDetalleChart();
            });
        }
        CC._detalleBound = true;
    }

    function fillCentrosControl() {
        renderNavCentros();
    }

    function centroDesdeAsignacion(emp, codigo, ciclo) {
        var e = String(emp || '').toUpperCase().trim();
        var code = String(codigo || '').trim();
        if (!e || !code) return null;
        var fromState = (CC.state.centros || []).map(mergedCentro).filter(function (c) {
            return String(c.codigo || '').trim() === code
                && String(c.empresa || '').toUpperCase().trim() === e;
        })[0];
        if (fromState) return fromState;
        var list = asigsPorEmpresa(ciclo || val('ctl-ciclo') || CC.state.cicloCodigo)[e] || [];
        var a = list.filter(function (x) {
            return String(x.centro_codigo || '').trim() === code;
        })[0];
        if (!a) {
            // Último recurso: buscar en todas las asignaciones del ciclo (por si empresa viene con otro casing).
            a = asignacionesDelCiclo(ciclo || val('ctl-ciclo') || CC.state.cicloCodigo).filter(function (x) {
                return String(x.centro_codigo || '').trim() === code
                    && String(x.empresa || '').toUpperCase().trim() === e;
            })[0] || null;
        }
        if (!a) return null;
        return mergedCentro({
            codigo: String(a.centro_codigo || '').trim(),
            nombre: a.centro_nombre || a.centro_codigo,
            empresa: e,
            usuario: a.usuario || '',
            estado: 'abierto',
            modo: (a.capturar || a.editar) ? 'captura' : 'solo_revision',
            departamento: '',
            ciclo: a.ciclo || ciclo || CC.state.cicloCodigo || '',
            capturar: !!a.capturar,
            editar: !!a.editar,
            revisar: !!a.revisar,
            permisos: a.permisos || [],
            sap: true
        });
    }

    function loadControlCentro() {
        var emp = String(val('ctl-empresa') || '').trim();
        var codigo = String(val('ctl-centro') || '').trim();
        if (!emp || !codigo) {
            control.centro = null;
            control.cuenta = null;
            control._allCtas = [];
            control._ctas = [];
            control._preciosMap = {};
            control._preciosReq = null;
            setCapturaEnabled(false);
            updateControlProgress();
            renderNavCuentas();
            syncVentasPasadasBtn();
            renderCtaChips();
            renderCapturaForm();
            renderControlTable();
            renderControlCharts();
            renderVisorTable();
            return;
        }
        // Mantener centros alineados al ciclo actual (evita combo con valor y tabla vacía).
        var cicloNow = val('ctl-ciclo') || CC.state.cicloCodigo || '';
        var periodCodigo = String((CC.state.period && CC.state.period.codigo) || '').trim();
        if (cicloNow && (String(CC.state.cicloCodigo || '') !== String(cicloNow) || periodCodigo.toUpperCase() !== String(cicloNow).toUpperCase())) {
            applyPeriodFromCiclo(findCiclo(cicloNow), cicloNow);
        }
        CC.state.centros = centrosDesdeAsignaciones(asignacionesDelCiclo(cicloNow));
        control.centro = centroDesdeAsignacion(emp, codigo, cicloNow);
        if (!control.centro) {
            var tb = document.getElementById('ctl-tbody');
            if (tb) tb.innerHTML = '<tr><td colspan="28"><div class="cc-empty">No tienes este cliente asignado en el ciclo seleccionado.</div></td></tr>';
            setCapturaEnabled(false);
            control._allCtas = [];
            control._ctas = [];
            updateControlProgress();
            renderNavCuentas();
            syncVentasPasadasBtn();
            renderCtaChips();
            renderCapturaForm();
            renderVisorTable();
            return;
        }
        var c = control.centro;
        var asig = asigDe(c) || c;
        var cicloCode = c.ciclo || cicloNow;
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
        loadListasPreciosCentro(c);
        control._allCtas = cuentasEnriquecidas(c);
        if (control.cuenta && !control._allCtas.filter(function (x) { return String(x.codigo) === String(control.cuenta); }).length) {
            control.cuenta = null;
        }
        updateControlProgress();
        renderNavEmpresas();
        renderNavCentros();
        renderNavCuentas();
        syncVentasPasadasBtn();
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
            setText('ctl-progress-kicker', 'Cliente');
            setText('ctl-progress-title', 'Selecciona un cliente');
            setText('kpi-ctl-avance', '—');
            setText('ctl-progress-meta', 'Elige un cliente para ver el avance');
            setText('ctl-pend-label', '— pendientes');
            setText('ctl-nav-pend-n', '—');
            setText('ctl-nav-pend-sub', 'Elige un cliente para ver cuántos productos faltan por proyectar');
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
        setText('ctl-progress-kicker', (c.ciclo || val('ctl-ciclo') || 'Proyección') + ' · ' + c.empresa);
        setText('ctl-progress-title', c.codigo + ' — ' + c.nombre);
        setText('kpi-ctl-avance', st.avance + '%');
        setText('ctl-progress-meta', st.capturadas + ' de ' + st.total + ' productos capturados');
        setText('ctl-pend-label', st.pendientes + (st.pendientes === 1 ? ' pendiente' : ' pendientes'));
        setText('kpi-ctl-gasto', moneyGasto(st.totGVista != null ? st.totGVista : st.totG));
        setText('kpi-ctl-ppto', money(st.totP));
        setText('kpi-ctl-pend', st.pendientes);
        var bar = document.getElementById('ctl-avance-bar');
        var wrap = document.getElementById('ctl-avance-wrap');
        if (bar) bar.style.width = Math.min(st.avance, 100) + '%';
        if (wrap) wrap.className = 'cc-progress ' + (st.pendientes ? 'warn' : 'good');
        setText('ctl-nav-pend-n', st.pendientes);
        setText('ctl-nav-pend-sub', st.total
            ? (st.pendientes
                ? ('De ' + st.total + ' productos asignados a este cliente')
                : ('Los ' + st.total + ' productos de este cliente ya tienen proyección'))
            : 'Este cliente no tiene productos asignados');
        if (box) box.className = 'cc-nav-pend' + (st.pendientes ? ' is-danger' : ' is-ok');
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
        }).join('') || '<div class="text-muted" style="font-size:.8rem">Sin productos para mostrar</div>';
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
        // Si el producto está en un grupo colapsado de la matriz, abrirlo.
        if (codigo) {
            var ctaSel = (control._allCtas || []).filter(function (x) {
                return String(x.codigo) === String(codigo);
            })[0];
            var gName = ctaSel && (ctaSel.grupo || 'Sin agrupación');
            if (gName && control.grupoCerradoMatrix && control.grupoCerradoMatrix[gName]) {
                control.grupoCerradoMatrix[gName] = false;
                drawMatrixTable();
            }
        }
        renderCtaChips();
        paintMatrixSelection();
        paintMatrixHint();
        paintCompletarBtn(currentCta());
        renderControlTable();
        renderControlCharts();
        renderDetalleTable();
        renderDetalleChart();
        paintScopeHints();
    }

    function matrixCtas() {
        var q = normSearch(control.prodQuery || ((document.getElementById('ctl-prod-q') || {}).value || ''));
        var ctas = (control._allCtas || []).slice();
        if (control.soloPendientes) ctas = ctas.filter(ctaPendiente);
        if (q) {
            ctas = ctas.filter(function (cta) {
                return normSearch(
                    (cta.codigo || '') + ' ' + (cta.nombre || '') + ' ' + (cta.grupo || '')
                ).indexOf(q) !== -1;
            });
        }
        return ctas;
    }

    function normSearch(s) {
        var t = String(s == null ? '' : s).toLowerCase();
        if (t.normalize) t = t.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        return t.replace(/\s+/g, ' ').trim();
    }

    function targetCtasForTools() {
        var selected = currentCta();
        if (selected) return [selected];
        return matrixCtas();
    }

    function renderCapturaForm() {
        var empty = document.getElementById('ctl-form-empty');
        var body = document.getElementById('ctl-form-body');
        if (!control.centro) {
            if (empty) {
                empty.hidden = false;
                empty.innerHTML = '<i class="fa-solid fa-table"></i>' +
                    '<div>Elige empresa y cliente a la izquierda para capturar todos los productos en una sola tabla</div>';
            }
            if (body) body.hidden = true;
            return;
        }
        var cc = control.centro;
        var ctas = matrixCtas();
        var st = statsDeCentro(cc);
        if (!st.total) {
            if (empty) {
                empty.hidden = false;
                empty.innerHTML = '<i class="fa-solid fa-boxes-stacked"></i>' +
                    '<div><strong>Este cliente no tiene productos asignados</strong></div>' +
                    '<div class="text-muted" style="font-size:.85rem;margin-top:.35rem">Ve a <b>Ventas → Asignaciones</b> del ciclo <b>' +
                    escapeHtml(val('ctl-ciclo') || CC.state.cicloCodigo || '') +
                    '</b>, elige el cliente y asígnale productos. Luego regresa a Captura: el budget <b>' +
                    escapeHtml(labelTipoBudget() || '3+9') +
                    '</b> precargará los meses del año de referencia.</div>';
            }
            if (body) body.hidden = true;
            return;
        }
        if (empty) empty.hidden = true;
        if (body) body.hidden = false;
        paintNombreCodigo('ctl-form-cc', cc.nombre || '', cc.codigo || '');
        setText('ctl-form-cta', st.total + (st.total === 1 ? ' producto' : ' productos'));
        setText('ctl-form-grupo', ctas.length === st.total
            ? 'Edita Ene–Dic por fila'
            : ('Mostrando ' + ctas.length + ' de ' + st.total));
        paintFormPresupuestoLabel();
        paintFormEstadoCliente(st);
        paintCompletarBtn(currentCta() || ctas[0] || null);
        updateFormTotalsCliente(st);
        paintFxBadge();
        drawMatrixTable(ctas);
        paintMatrixHint();
    }

    function paintFormEstadoCliente(st) {
        var el = document.getElementById('ctl-form-estado');
        if (!el) return;
        var listo = st && st.total > 0 && !st.pendientes;
        el.textContent = listo ? 'Capturada' : (st && st.pendientes ? (st.pendientes + ' pend.') : 'Pendiente');
        el.className = 'cc-badge ' + (listo ? 'cc-badge-aceptado' : 'cc-badge-en_proceso');
    }

    function updateFormTotalsCliente(st) {
        st = st || (control.centro ? statsDeCentro(control.centro) : { totG: 0, totP: 0, totGVista: 0, totPVista: 0, capturadas: 0, total: 0, pendientes: 0, avance: 0 });
        var ventaVista = st.totGVista != null ? st.totGVista : st.totG;
        var proyVista = st.totPVista != null ? st.totPVista : st.totP;
        setText('ctl-form-gasto', moneyGasto(ventaVista));
        setText('ctl-form-ppto', moneyGasto(proyVista));
        var d = deltaPct(proyVista, ventaVista);
        var el = document.getElementById('ctl-form-delta');
        if (el) el.textContent = st.pendientes
            ? (st.pendientes + (st.pendientes === 1 ? ' producto pendiente' : ' productos pendientes'))
            : (st.totP ? ((d > 0 ? '+' : '') + d + '% vs ' + CC.state.anioGasto) : 'Completada');
        setText('ctl-form-avance', st.avance + '%');
        setText('ctl-form-avance-meta', st.capturadas + ' de ' + st.total + ' productos capturados');
        var bar = document.getElementById('ctl-form-avance-bar');
        var wrap = document.getElementById('ctl-form-avance-wrap');
        if (bar) bar.style.width = Math.min(st.avance, 100) + '%';
        if (wrap) wrap.className = 'cc-progress ' + (st.pendientes ? 'warn' : 'good');
    }

    function paintMatrixHint() {
        var hint = document.getElementById('ctl-matrix-hint');
        if (!hint) return;
        var cta = currentCta();
        var anio = CC.state.anioGasto || '';
        if (cta) {
            hint.textContent = 'Fila activa: ' + labelNombreCodigo(cta.nombre, cta.codigo) +
                '. Arriba = venta ' + anio + '; abajo = proyección. Copiar / Limpiar / % / Completado aplican a esta fila.';
            return;
        }
        hint.textContent = 'Arriba de cada mes: venta ' + anio + '. Abajo: proyección. Clic en un producto para herramientas puntuales.';
    }

    function nombrePresupuestoActual() {
        var p = CC.state.period || {};
        return p.nombre || p.codigo || ('Presupuesto ' + (p.anio || CC.state.anioPresupuesto || ''));
    }

    function paintFormPresupuestoLabel() {
        setText('ctl-form-ppto-label', nombrePresupuestoActual());
    }

    function paintFormEstado(cta) {
        if (!cta) {
            if (control.centro) paintFormEstadoCliente(statsDeCentro(control.centro));
            return;
        }
        var st = document.getElementById('ctl-form-estado');
        if (!st) return;
        var listo = !ctaPendiente(cta);
        st.textContent = listo ? (cta.totP > 0 ? 'Capturada' : 'Completada') : 'Pendiente';
        st.className = 'cc-badge ' + (listo ? 'cc-badge-aceptado' : 'cc-badge-en_proceso');
    }

    function paintCompletarBtn(cta) {
        var btn = document.getElementById('ctl-completar');
        if (!btn) return;
        if (control.locked || !control.centro) {
            btn.hidden = true;
            return;
        }
        btn.hidden = false;
        var on = cta ? !ctaPendiente(cta) : !(statsDeCentro(control.centro).pendientes);
        btn.setAttribute('data-on', on ? '1' : '0');
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Completado';
    }

    function mesesCapturados(cta) {
        return (cta.ppto || []).reduce(function (n, v) { return n + (mesLleno(v) ? 1 : 0); }, 0);
    }

    function paintCtaFill(cta) {
        if (!cta) {
            updateFormTotalsCliente();
            return;
        }
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
        if (!cta) {
            updateFormTotalsCliente();
            return;
        }
        var ventaVista = cta.importeVentaVista != null ? cta.importeVentaVista : importeVentaVista(cta);
        var proyVista = importeProyeccionVista(cta);
        setText('ctl-form-gasto', moneyGasto(ventaVista));
        setText('ctl-form-ppto', moneyGasto(proyVista));
        var d = deltaPct(proyVista, ventaVista);
        var el = document.getElementById('ctl-form-delta');
        if (el) el.textContent = !ctaPendiente(cta)
            ? ((cta.importeProy || cta.totP) ? ((d > 0 ? '+' : '') + d + '% vs ' + CC.state.anioGasto) : 'Completada en 0')
            : 'Aún sin capturar';
        paintCtaFill(cta);
    }

    function qtyLabel(n) {
        var val = Number(n) || 0;
        return val.toLocaleString('es-MX', { maximumFractionDigits: 2 });
    }

    /**
     * Semestre pasado respecto a la proyección, en el año de venta real (anioGasto).
     * Si hoy es Jul–Dic → Ene–Jun; si hoy es Ene–Jun → Jul–Dic del año anterior de referencia
     * (en la práctica usamos Jul–Dic del mismo anioGasto cuando aún estamos en H1).
     * Solo unidades (no importe).
     */
    function indicesSemestreAnterior() {
        var m = new Date().getMonth();
        if (m >= 6) {
            return { from: 0, to: 5, label: 'Ene–Jun' };
        }
        return { from: 6, to: 11, label: 'Jul–Dic' };
    }

    function unidadesSemestreAnterior(cta) {
        var idx = indicesSemestreAnterior();
        var gasto = (cta && cta.gasto) || [];
        var total = 0;
        for (var i = idx.from; i <= idx.to; i++) {
            total += Number(gasto[i]) || 0;
        }
        return total;
    }

    /** ¿Hay alguna venta real cargada en el año (cualquier mes)? */
    function tieneVentaRealAnio(cta) {
        var gasto = (cta && cta.gasto) || [];
        for (var i = 0; i < 12; i++) {
            if (Number(gasto[i]) || 0) return true;
        }
        return false;
    }

    function labelSemestreAnterior() {
        var idx = indicesSemestreAnterior();
        var yy = String(CC.state.anioGasto || '');
        return idx.label + (yy ? ' ' + yy : '');
    }

    /** Precio de lista (moneda nativa) → monto en la moneda de vista. Usa TC del mes si se indica. */
    function precioListaEnVista(precio, monedaNativa, monthIdx) {
        var amount = Number(precio) || 0;
        var src = String(monedaNativa || 'MXN').toUpperCase();
        var view = isUsdView() ? 'USD' : 'MXN';
        if (src === view) return amount;
        var tc = fxRate(monthIdx);
        if (!(tc > 0)) tc = fxBaseRate();
        if (src === 'USD' && view === 'MXN') return amount * tc;
        if (src === 'MXN' && view === 'USD') return tc > 0 ? amount / tc : amount;
        return amount;
    }

    /** Unidades capturadas (Ene–Dic). Sin tipo de cambio. */
    function unidadesAnuales(ppto) {
        var total = 0;
        (ppto || []).forEach(function (v) {
            if (mesLleno(v)) total += Number(v) || 0;
        });
        return total;
    }

    /**
     * Proyección monetaria en base MXN: suma mensual de unidades × precio con TC del mes.
     */
    function importeProyeccionMxn(cta) {
        if (!cta) return 0;
        var total = 0;
        for (var i = 0; i < 12; i++) {
            var m = importeMesProyMxn(cta, i);
            if (m != null) total += Number(m) || 0;
        }
        return total;
    }

    /** Venta real en MXN (suma de LineTotal SAP) o, si no hay, qty × precio lista. */
    function importeVentaMxn(cta) {
        if (!cta) return 0;
        var fromSap = sum(cta.importe || []);
        if (fromSap) return fromSap;
        var usd = sum(usdSeriesOf(cta));
        if (usd) {
            var total = 0;
            for (var j = 0; j < 12; j++) {
                var u = Number(usdSeriesOf(cta)[j]) || 0;
                if (u) total += u * fxRate(j);
            }
            return total;
        }
        var total2 = 0;
        for (var i = 0; i < 12; i++) {
            total2 += importeMesVentaMxn(cta, i);
        }
        return total2;
    }

    /** Importe MXN de un mes de venta real. */
    function importeMesVentaMxn(cta, i) {
        if (!cta) return 0;
        var fromSap = Number((cta.importe && cta.importe[i]) || 0);
        if (fromSap) return fromSap;
        var usd = Number(usdSeriesOf(cta)[i]) || 0;
        if (usd) return usd * fxRate(i);
        var qty = Number((cta.gasto && cta.gasto[i]) || 0);
        var precio = Number(cta.precioLista) || 0;
        if (!qty || !precio) return 0;
        var mon = String(cta.precioMoneda || 'MXN').toUpperCase();
        return qty * (mon === 'USD' ? precio * fxRate(i) : precio);
    }

    /** Importe proyectado del mes ya en moneda de vista (USD/MXN). */
    function importeMesProyVista(cta, i) {
        var m = importeMesProyMxn(cta, i);
        if (m == null) return null;
        return toDisplayAmount(m, i);
    }

    /** Total proyección en moneda de vista (misma base que VENTA en pantalla). */
    function importeProyeccionVista(cta) {
        if (!cta) return 0;
        var total = 0;
        for (var i = 0; i < 12; i++) {
            var m = importeMesProyVista(cta, i);
            if (m != null) total += Number(m) || 0;
        }
        return total;
    }

    /** Importe MXN de un mes proyectado: unidades × precio del mes (override o lista) × TC.
     *  Si no hay precio de lista, usa el precio implícito de la venta real de ese mes. */
    function importeMesProyMxn(cta, i) {
        if (!cta || !mesLleno(cta.ppto && cta.ppto[i])) return null;
        var uds = Number(cta.ppto[i]) || 0;
        if (!uds) return 0;
        var precio = precioUnitarioMes(cta, i);
        if (precio > 0) {
            var mon = String(cta.precioMoneda || 'MXN').toUpperCase();
            var precioMxn = mon === 'USD' ? precio * fxRate(i) : precio;
            return uds * precioMxn;
        }
        var qtyVenta = Number((cta.gasto && cta.gasto[i]) || 0);
        var impVenta = Number((cta.importe && cta.importe[i]) || 0);
        if (qtyVenta > 0 && impVenta > 0) {
            return uds * (impVenta / qtyVenta);
        }
        var precioImp = precioImplicitoVenta(cta);
        if (precioImp > 0) return uds * precioImp;
        return null;
    }

    /** Precio MXN/unidad promedio de la venta real (importe ÷ unidades). */
    function precioImplicitoVenta(cta) {
        if (!cta) return 0;
        var qty = 0;
        var imp = 0;
        for (var i = 0; i < 12; i++) {
            var q = Number((cta.gasto && cta.gasto[i]) || 0);
            var m = Number((cta.importe && cta.importe[i]) || 0);
            if (q > 0 && m > 0) {
                qty += q;
                imp += m;
            }
        }
        return qty > 0 ? (imp / qty) : 0;
    }

    function moneyLista(precio, monedaNativa, monthIdx, decimals) {
        var view = isUsdView() ? 'USD' : 'MXN';
        var val = precioListaEnVista(precio, monedaNativa, monthIdx);
        var dec = (decimals == null || decimals === '') ? 2 : Number(decimals);
        if (!isFinite(dec) || dec < 0) dec = 2;
        return (view === 'USD' ? 'US$' : '$') + val.toLocaleString('es-MX', {
            minimumFractionDigits: dec,
            maximumFractionDigits: dec
        });
    }

    /** Precio local del último mes (tbl_pv_productos_costo). Si no hay, promedio de venta SAP. */
    function precioVentaAnioRef(cta) {
        var unidad = String((cta && cta.unidad) || '').trim();
        var unidadNombre = String((cta && cta.unidadNombre) || '').trim();
        var local = Number(cta && cta.precioLista) || 0;
        var mon = String((cta && cta.precioMoneda) || 'MXN').toUpperCase() || 'MXN';
        var mes = Number(cta && cta.precioLocalMes) || 0;
        var MESES = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        if (local > 0 && (cta.listaPrecioNombre || '').indexOf('Maestro local') === 0) {
            return {
                precio: local,
                moneda: mon,
                unidad: unidad,
                unidadNombre: unidadNombre,
                decimals: 2,
                title: 'Precio local (último mes' + (mes >= 1 && mes <= 12 ? ': ' + MESES[mes] : '') + ') · tbl_pv_productos_costo'
            };
        }
        // Si precioLista vino de lista SAP pero hay maestro, preferir maestro via lookup otra vez.
        var c = control.centro;
        if (c) {
            var master = lookupMaestroLocal(c.empresa, c.codigo, cta && cta.codigo);
            if (master && Number(master.costo) > 0) {
                var mMes = Number(master.mes) || 0;
                return {
                    precio: Number(master.costo),
                    moneda: String(master.moneda || 'MXN').toUpperCase(),
                    unidad: unidad,
                    unidadNombre: unidadNombre,
                    decimals: 2,
                    title: 'Precio local (último mes' + (mMes >= 1 && mMes <= 12 ? ': ' + MESES[mMes] : '') + ') · tbl_pv_productos_costo'
                };
            }
        }
        var info = precioVentaPasadaInfo(cta);
        if (!info.title) {
            info.title = 'Precio unitario promedio de la venta ' + (CC.state.anioGasto || '') + ' (importe SAP ÷ uds).';
        }
        return info;
    }

    /** @deprecated Preferir precioVentaAnioRef en Captura (vista de ventas). */
    function costoPiezaVentaAnio(cta) {
        return precioVentaAnioRef(cta);
    }

    /** Precio unitario promedio de la venta del año de referencia (ponderado por unidades). */
    function precioVentaPasadaInfo(cta) {
        var unidad = String((cta && cta.unidad) || '').trim();
        var unidadNombre = String((cta && cta.unidadNombre) || '').trim();
        if (!cta) return { precio: 0, moneda: 'MXN', unidad: unidad, unidadNombre: unidadNombre };
        var qtyTot = 0;
        var mxnTot = 0;
        var usdTot = 0;
        var wPrice = 0;
        var wQty = 0;
        for (var i = 0; i < 12; i++) {
            var q = Number((cta.gasto && cta.gasto[i]) || 0);
            qtyTot += q;
            mxnTot += Number((cta.importe && cta.importe[i]) || 0);
            usdTot += Number((cta.importeUsd && cta.importeUsd[i]) || (cta.importe_usd && cta.importe_usd[i]) || 0);
            var p = Number((cta.precio && cta.precio[i]) || 0);
            if (q && p) {
                wPrice += Math.abs(q) * p;
                wQty += Math.abs(q);
            }
        }
        if (qtyTot && usdTot) {
            return {
                precio: Math.abs(usdTot / qtyTot),
                moneda: 'USD',
                unidad: unidad,
                unidadNombre: unidadNombre,
                decimals: 2,
                title: 'Promedio ponderado (LineTotalUSD ÷ uds). VENTA es la suma SAP; uds × precio a 2 decimales puede diferir unos dólares.'
            };
        }
        if (qtyTot && mxnTot) {
            return {
                precio: Math.abs(mxnTot / qtyTot),
                moneda: 'MXN',
                unidad: unidad,
                unidadNombre: unidadNombre,
                decimals: 2,
                title: 'Promedio ponderado (LineTotal ÷ uds). VENTA es la suma SAP; el redondeo del precio puede diferir unos pesos.'
            };
        }
        if (wQty) {
            return { precio: wPrice / wQty, moneda: 'MXN', unidad: unidad, unidadNombre: unidadNombre };
        }
        return { precio: 0, moneda: 'MXN', unidad: unidad, unidadNombre: unidadNombre };
    }

    /** Precio de lista / proyección: prioriza maestro local (último mes). */
    function precioProyeccionInfo(cta) {
        var mes = Number(cta && cta.precioLocalMes) || 0;
        var MESES = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        var fromLocal = (cta && cta.listaPrecioNombre && String(cta.listaPrecioNombre).indexOf('Maestro local') === 0);
        return {
            precio: Number(cta && cta.precioLista) || 0,
            moneda: String((cta && cta.precioMoneda) || 'MXN').toUpperCase() || 'MXN',
            unidad: String((cta && cta.unidad) || '').trim(),
            unidadNombre: String((cta && cta.unidadNombre) || '').trim(),
            porMes: precioMesesVarian(cta),
            title: fromLocal
                ? ('Precio local (último mes' + (mes >= 1 && mes <= 12 ? ': ' + MESES[mes] : '') + ')')
                : 'Precio de lista / proyección'
        };
    }

    function nombreUnidadMedida(codigo) {
        var code = String(codigo || '').trim();
        if (!code) return '';
        var map = CC.state.unidadesMedida || {};
        return String(map[code.toUpperCase()] || map[code] || '').trim();
    }

    /** Etiqueta visible: "PIEZAS (PZA)" o solo el código si no hay nombre. */
    function formatUnidadLabel(codigo, nombre) {
        var code = String(codigo || '').trim();
        var name = String(nombre || '').trim() || nombreUnidadMedida(code);
        if (!code && !name) return '';
        if (name && code && name.toUpperCase() !== code.toUpperCase()) {
            return name + ' (' + code + ')';
        }
        return name || code;
    }

    function unidadDe(cta) {
        if (!cta) return '';
        return formatUnidadLabel(cta.unidad, cta.unidadNombre);
    }

    function precioInfoHtml(info) {
        info = info || {};
        var precio = Number(info.precio) || 0;
        var mon = String(info.moneda || 'MXN').toUpperCase();
        var unidad = formatUnidadLabel(info.unidad, info.unidadNombre);
        var dec = info.decimals != null ? info.decimals : 2;
        if (!precio && !unidad) {
            return '<span class="cc-price-empty">—</span>';
        }
        var html = '';
        if (precio) {
            html += '<div class="cc-price-amt">' + escapeHtml(moneyLista(precio, mon, null, dec)) + '</div>';
            if (mon && mon !== (isUsdView() ? 'USD' : 'MXN')) {
                html += '<div class="cc-price-src">' + escapeHtml(mon) + '</div>';
            }
        } else {
            html += '<div class="cc-price-amt text-muted">Sin precio</div>';
        }
        if (unidad) {
            html += '<div class="cc-price-uom" title="' + escapeHtml(String(info.unidad || '')) + '">' + escapeHtml(unidad) + '</div>';
        }
        if (info.porMes) {
            html += '<div class="cc-price-src is-custom">por mes</div>';
        }
        return html;
    }

    function precioProyeccionCellHtml(cta) {
        var info = precioProyeccionInfo(cta);
        return '<button type="button" class="cc-price-edit' + (info.porMes ? ' is-custom' : '') + '" data-edit-precio="' +
            escapeHtml(cta.codigo) + '" title="Clic para ajustar el precio por mes. El precio global aplica hasta que cambies un mes.">' +
            precioInfoHtml(info) +
            '<span class="cc-price-hint">Clic · variar por mes</span>' +
            '</button>';
    }

    function precioUnitarioHtml(cta) {
        return precioInfoHtml(precioProyeccionInfo(cta));
    }

    function monthRealHtml(cta, i) {
        var qty = Number((cta.gasto && cta.gasto[i]) || 0);
        var tot = Number((cta.importe && cta.importe[i]) || 0);
        var usd = Number((cta.importeUsd && cta.importeUsd[i]) || 0);
        var price = Number((cta.precio && cta.precio[i]) || 0);
        if (!qty && !tot && !usd) {
            return '<div class="cc-month-real is-empty">Sin venta</div>';
        }
        if (!price && qty) price = tot / qty;
        return '<div class="cc-month-real">' +
            '<div class="cc-month-qty">' + qtyLabel(qty) + ' <em>uds</em></div>' +
            '<div class="cc-month-money">' +
                '<span>MXN ' + money(tot) + '</span>' +
                (usd ? '<span>USD ' + money(usd) + '</span>' : '') +
            '</div>' +
            (price ? '<div class="cc-month-price">P. ' + moneyDec(price) + '</div>' : '') +
            '</div>';
    }

    function drawMonthGrid(cta) {
        drawMatrixTable(matrixCtas());
    }

    function matrixProductRowHtml(cta, anioPast) {
        var d = deltaPct(cta.totP, cta.totG);
        var dCls = d > 15 ? 'text-danger' : (d < 0 ? 'text-success' : 'text-muted');
        var selected = String(cta.codigo) === String(control.cuenta || '');
        var done = !ctaPendiente(cta);
        var udsVentaAnio = sum((cta.gasto || []).slice(0, 12));
        var udsSem = unidadesSemestreAnterior(cta);
        var udsAnio = cta.unidadesAnio != null ? cta.unidadesAnio : unidadesAnuales(cta.ppto);
        var precioVenta = precioVentaAnioRef(cta);
        var uomLabel = unidadDe(cta);
        var cells = '';
        for (var i = 0; i < 12; i++) {
            var shown = formatInputQty(cta.ppto[i]);
            var pastQty = Number((cta.gasto && cta.gasto[i]) || 0);
            var lockedMes = mesBloqueadoBudget(i);
            var pCls = monthCellClass(cta, i).replace('cc-month-cell', 'cc-matrix-cell') + (lockedMes ? ' is-locked' : '');
            var pMes = precioUnitarioMes(cta, i);
            var pCustom = precioMesTieneOverride(cta, i);
            var mon = String(cta.precioMoneda || 'MXN').toUpperCase();
            cells += '<td class="' + pCls + '">' +
                '<div class="cc-matrix-month">' +
                    '<div class="cc-month-past' + (pastQty ? '' : ' is-zero') + '" title="' +
                        escapeHtml('Venta ' + anioPast + ' · ' + MONTHS[i] + (uomLabel ? ' · ' + uomLabel : '') +
                            (pastQty ? ' · ' + qtyLabel(pastQty) + ' uds' : '')) + '">' +
                        (pastQty ? escapeHtml(qtyLabel(pastQty)) : '—') +
                    '</div>' +
                    '<input type="number" step="0.01" data-cta="' + escapeHtml(cta.codigo) + '" data-m="' + i + '" value="' + shown + '" ' +
                    ((control.locked || lockedMes) ? 'disabled' : '') + ' placeholder="0" class="cc-month-input' +
                    ((Number(shown) || 0) < 0 ? ' is-neg' : '') + (lockedMes ? ' is-locked' : '') + '" title="' +
                    escapeHtml(MONTHS[i] + (lockedMes ? ' · bloqueado (budget ' + labelTipoBudget() + ')' : '') +
                        ' · proyección (unidades) · venta ' + anioPast + ': ' + (pastQty ? qtyLabel(pastQty) : '0') +
                        (uomLabel ? ' ' + uomLabel : '')) + '">' +
                    (pCustom
                        ? ('<div class="cc-month-price-tag is-custom" title="' +
                            escapeHtml('Precio del mes: ' + moneyLista(pMes, mon, i) + ' · TC ' + fxRate(i).toFixed(2)) + '">' +
                            escapeHtml(moneyLista(pMes, mon, i)) + '</div>')
                        : '') +
                '</div>' +
                '</td>';
        }
        return '<tr class="cc-matrix-row' + (selected ? ' is-on' : '') + (done ? ' is-done' : '') + '" data-cta="' + escapeHtml(cta.codigo) + '" data-group="' + escapeHtml(cta.grupo || 'Sin agrupación') + '">' +
            '<td class="sticky-col">' +
                '<button type="button" class="cc-matrix-prod" data-pick-cta="' + escapeHtml(cta.codigo) + '">' +
                    htmlNombreCodigo(cta.nombre, cta.codigo) +
                '</button>' +
                '<span class="cc-matrix-status">' + (done ? 'Capturado' : (mesesCapturados(cta) + '/12')) + '</span>' +
            '</td>' +
            '<td class="num cc-price-cell" data-precio-venta title="' +
                escapeHtml(precioVenta.title || ('Precio unitario promedio de la venta ' + anioPast)) + '">' +
                (precioVenta.precio
                    ? ('<div class="cc-price-amt">' + escapeHtml(moneyLista(precioVenta.precio, precioVenta.moneda, null, 2)) + '</div>' +
                        (uomLabel ? '<div class="cc-price-uom" title="' + escapeHtml(cta.unidad || '') + '">' + escapeHtml(uomLabel) + '</div>' : ''))
                    : '<span class="cc-price-empty">—</span>') +
            '</td>' +
            '<td class="num cc-sem-cell" data-uds-venta title="' +
                escapeHtml('Total unidades vendidas en ' + anioPast +
                    ' (misma fuente que Ventas pasadas · TOT. UDS)' +
                    (uomLabel ? ' · ' + uomLabel : '') +
                    (udsSem ? '' : (udsVentaAnio ? ' · Ene–Jun: 0; venta en jul–Dic' : ''))) + '">' +
                (udsVentaAnio
                    ? ('<div class="cc-sem-qty">' + escapeHtml(qtyLabel(udsVentaAnio)) + '</div>' +
                        (uomLabel ? '<div class="cc-sem-uom">' + escapeHtml(uomLabel) + '</div>' : ''))
                    : '<span class="cc-price-empty">—</span>') +
            '</td>' +
            '<td class="num cc-sem-cell" data-uds-total title="' +
                escapeHtml('Suma de unidades proyectadas Ene–Dic' + (uomLabel ? ' · ' + uomLabel : '')) + '">' +
                (udsAnio
                    ? ('<div class="cc-sem-qty">' + escapeHtml(qtyLabel(udsAnio)) + '</div>' +
                        (uomLabel ? '<div class="cc-sem-uom">' + escapeHtml(uomLabel) + '</div>' : ''))
                    : '<span class="cc-price-empty">—</span>') +
            '</td>' +
            '<td class="num cc-price-cell" data-precio-unit>' + precioProyeccionCellHtml(cta) + '</td>' +
            '<td class="num ' + dCls + '" data-delta>' + (cta.totP || done ? ((d > 0 ? '+' : '') + d + '%') : '—') + '</td>' +
            cells +
            '</tr>';
    }

    function bindMatrixTableEvents(tbody) {
        tbody.querySelectorAll('.cc-matrix-group-row').forEach(function (row) {
            row.addEventListener('click', function () {
                var g = row.getAttribute('data-group');
                var map = control.grupoCerradoMatrix || (control.grupoCerradoMatrix = {});
                map[g] = !map[g];
                drawMatrixTable();
            });
        });
        tbody.querySelectorAll('[data-pick-cta]').forEach(function (btn) {
            btn.addEventListener('click', function (ev) {
                ev.preventDefault();
                selectCuenta(btn.getAttribute('data-pick-cta'));
            });
        });
        tbody.querySelectorAll('[data-edit-precio]').forEach(function (btn) {
            btn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                openPrecioMesesModal(btn.getAttribute('data-edit-precio'));
            });
        });
        tbody.querySelectorAll('input[data-m]').forEach(function (inp) {
            inp.addEventListener('focus', function () {
                if (String(control.cuenta || '') !== String(inp.getAttribute('data-cta') || '')) {
                    control.cuenta = inp.getAttribute('data-cta');
                    paintMatrixSelection();
                    paintMatrixHint();
                    paintCompletarBtn(currentCta());
                }
            });
            inp.addEventListener('input', function () {
                var td = inp.closest('td');
                var codigo = inp.getAttribute('data-cta');
                var cta = (control._allCtas || []).filter(function (x) { return String(x.codigo) === String(codigo); })[0];
                if (!td || !cta) return;
                var raw = String(inp.value || '').trim().replace(/,/g, '');
                var fake = { gasto: cta.gasto, ppto: (cta.ppto || []).slice() };
                fake.ppto[Number(inp.getAttribute('data-m'))] = raw === '' ? null : toStoreQty(Number(raw) || 0);
                td.className = monthCellClass(fake, Number(inp.getAttribute('data-m'))).replace('cc-month-cell', 'cc-matrix-cell');
                inp.classList.toggle('is-neg', (Number(raw) || 0) < 0);
                var rowLive = inp.closest('tr');
                var totalEl = rowLive && rowLive.querySelector('[data-uds-total]');
                if (totalEl) {
                    var udsLive = unidadesAnuales(fake.ppto);
                    totalEl.innerHTML = udsLive
                        ? ('<div class="cc-sem-qty">' + escapeHtml(qtyLabel(udsLive)) + '</div>' +
                            (unidadDe(cta) ? '<div class="cc-sem-uom">' + escapeHtml(unidadDe(cta)) + '</div>' : ''))
                        : '<span class="cc-price-empty">—</span>';
                }
            });
            inp.addEventListener('change', onMonthChange);
            inp.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter') { ev.preventDefault(); inp.blur(); }
            });
        });
    }

    function drawMatrixTable(ctas) {
        var thead = document.getElementById('ctl-matrix-thead');
        var tbody = document.getElementById('ctl-matrix-tbody');
        if (!thead || !tbody) return;
        ctas = ctas || matrixCtas();
        var anioPast = CC.state.anioGasto || '';
        var cur = isUsdView() ? 'USD' : 'MXN';
        var head = '<tr><th class="sticky-col">Producto</th>' +
            '<th class="num">Precio<span class="cc-th-past">local · último mes</span></th>' +
            '<th class="num">Uds. venta<span class="cc-th-past">' + escapeHtml(String(anioPast || '')) + '</span></th>' +
            '<th class="num">Total uds<span class="cc-th-past">proy. 12 meses</span></th>' +
            '<th class="num">Precio<span class="cc-th-past">proy. local</span></th>' +
            '<th class="num">Δ%</th>';
        MONTHS.forEach(function (m, mi) {
            var tcM = fxRate(mi);
            var base = fxBaseRate();
            var diff = Math.abs(tcM - base) > 0.0001;
            var lockedMes = mesBloqueadoBudget(mi);
            head += '<th class="num">' + m +
                '<span class="cc-th-past">vs ' + String(anioPast).slice(2) + '</span>' +
                '<span class="cc-th-tc' + (diff ? ' is-custom' : '') + '" title="Tipo de cambio del mes">TC ' +
                tcM.toFixed(2) + '</span>' +
                (lockedMes ? '<span class="cc-th-budget" title="Mes bloqueado por tipo de budget">fijo</span>' : '') +
                '</th>';
        });
        head += '</tr>';
        thead.innerHTML = head;
        if (!ctas.length) {
            tbody.innerHTML = '<tr><td colspan="18"><div class="cc-empty">' +
                (control.soloPendientes || control.prodQuery
                    ? 'Sin productos con ese filtro'
                    : 'Este cliente no tiene productos asignados') +
                '</div></td></tr>';
            return;
        }

        var groups = {};
        var groupOrder = [];
        ctas.forEach(function (cta) {
            var g = cta.grupo || 'Sin agrupación';
            if (!groups[g]) {
                groups[g] = [];
                groupOrder.push(g);
            }
            groups[g].push(cta);
        });
        var closedMap = control.grupoCerradoMatrix || (control.grupoCerradoMatrix = {});
        var html = '';
        groupOrder.forEach(function (g) {
            var list = groups[g];
            var closed = !!closedMap[g];
            var capturados = list.filter(function (x) { return !ctaPendiente(x); }).length;
            var udsGrupo = list.reduce(function (a, x) {
                return a + (x.unidadesAnio != null ? x.unidadesAnio : unidadesAnuales(x.ppto));
            }, 0);
            html += '<tr class="cc-group-row cc-matrix-group-row" data-group="' + escapeHtml(g) + '">' +
                '<td class="sticky-col" colspan="6">' +
                    '<i class="fa-solid fa-chevron-' + (closed ? 'right' : 'down') + ' me-1"></i>' +
                    escapeHtml(g) + ' · ' + list.length + (list.length === 1 ? ' producto' : ' productos') +
                    '<span class="cc-matrix-group-meta">' + capturados + '/' + list.length + ' capturados' +
                    (udsGrupo ? (' · ' + qtyLabel(udsGrupo) + ' uds proy.') : '') + '</span>' +
                '</td>' +
                '<td colspan="12" class="cc-matrix-group-spacer"></td>' +
                '</tr>';
            if (!closed) {
                list.forEach(function (cta) {
                    html += matrixProductRowHtml(cta, anioPast);
                });
            }
        });
        tbody.innerHTML = html;
        bindMatrixTableEvents(tbody);
    }

    function paintMatrixSelection() {
        var tbody = document.getElementById('ctl-matrix-tbody');
        if (!tbody) return;
        tbody.querySelectorAll('.cc-matrix-row').forEach(function (row) {
            row.classList.toggle('is-on', String(row.getAttribute('data-cta') || '') === String(control.cuenta || ''));
        });
    }

    function updateMatrixRow(codigo) {
        var c = control.centro;
        if (!c || !codigo) return;
        var cta = (control._allCtas || []).filter(function (x) { return String(x.codigo) === String(codigo); })[0];
        var row = document.querySelector('#ctl-matrix-tbody tr.cc-matrix-row[data-cta="' + cssEscape(codigo) + '"]');
        if (!cta || !row) {
            // Puede estar en un grupo cerrado: reabrir y redibujar.
            if (cta) {
                var gName = cta.grupo || 'Sin agrupación';
                if (control.grupoCerradoMatrix && control.grupoCerradoMatrix[gName]) {
                    control.grupoCerradoMatrix[gName] = false;
                }
            }
            drawMatrixTable();
            return;
        }
        var d = deltaPct(cta.totP, cta.totG);
        var dCls = d > 15 ? 'text-danger' : (d < 0 ? 'text-success' : 'text-muted');
        var done = !ctaPendiente(cta);
        row.classList.toggle('is-done', done);
        row.classList.toggle('is-on', String(control.cuenta || '') === String(codigo));
        var udsTotal = row.querySelector('[data-uds-total]');
        var precioCell = row.querySelector('[data-precio-unit]');
        var delta = row.querySelector('[data-delta]');
        var status = row.querySelector('.cc-matrix-status');
        if (udsTotal) {
            var udsAnio = cta.unidadesAnio != null ? cta.unidadesAnio : unidadesAnuales(cta.ppto);
            udsTotal.innerHTML = udsAnio
                ? ('<div class="cc-sem-qty">' + escapeHtml(qtyLabel(udsAnio)) + '</div>' +
                    (unidadDe(cta) ? '<div class="cc-sem-uom">' + escapeHtml(unidadDe(cta)) + '</div>' : ''))
                : '<span class="cc-price-empty">—</span>';
        }
        if (precioCell) precioCell.innerHTML = precioProyeccionCellHtml(cta);
        if (precioCell) {
            var editBtn = precioCell.querySelector('[data-edit-precio]');
            if (editBtn) {
                editBtn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    openPrecioMesesModal(editBtn.getAttribute('data-edit-precio'));
                });
            }
        }
        if (delta) {
            delta.className = 'num ' + dCls;
            delta.textContent = (cta.totP || done) ? ((d > 0 ? '+' : '') + d + '%') : '—';
        }
        if (status) status.textContent = done ? 'Capturado' : (mesesCapturados(cta) + '/12');
        for (var i = 0; i < 12; i++) {
            var inp = row.querySelector('input[data-m="' + i + '"]');
            if (!inp) continue;
            if (document.activeElement === inp) continue;
            var shown = formatInputQty(cta.ppto[i]);
            if (String(inp.value) !== shown) inp.value = shown;
            inp.classList.toggle('is-neg', (Number(shown) || 0) < 0);
            var td = inp.closest('td');
            if (td) {
                td.className = monthCellClass(cta, i).replace('cc-month-cell', 'cc-matrix-cell');
                var wrap = td.querySelector('.cc-matrix-month');
                if (wrap) {
                    var tag = wrap.querySelector('.cc-month-price-tag');
                    var pMes = precioUnitarioMes(cta, i);
                    var pCustom = precioMesTieneOverride(cta, i);
                    var mon = String(cta.precioMoneda || 'MXN').toUpperCase();
                    if (pCustom && pMes) {
                        if (!tag) {
                            tag = document.createElement('div');
                            wrap.appendChild(tag);
                        }
                        tag.className = 'cc-month-price-tag is-custom';
                        tag.textContent = moneyLista(pMes, mon, i);
                        tag.title = 'Precio del mes: ' + moneyLista(pMes, mon, i) + ' · TC ' + fxRate(i).toFixed(2);
                    } else if (tag) {
                        tag.remove();
                    }
                }
            }
        }
        updateFormTotalsCliente(statsDeCentro(c));
        paintFormEstadoCliente(statsDeCentro(c));
        paintMatrixHint();
    }

    function cssEscape(value) {
        return String(value == null ? '' : value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    function monthCellClass(cta, i) {
        var cls = 'cc-month-cell';
        var raw = cta.ppto && cta.ppto[i];
        // Amarillo: mes pendiente de capturar.
        if (!mesLleno(raw)) {
            cls += ' is-empty';
            return cls;
        }
        var val = Number(raw) || 0;
        var past = Number((cta.gasto && cta.gasto[i]) || 0);
        // Verde: unidades proyectadas mayores (o iguales) a la venta pasada.
        // Rojo: unidades proyectadas menores a la venta pasada.
        if (past > 0 && val < past) cls += ' is-over';
        else cls += ' is-ok';
        return cls;
    }

    function monthsFromGrid(cta, fillEmpty) {
        var c = control.centro;
        var months = (c && cta) ? pptoDe(c.empresa, c.codigo, cta, cta.gasto).slice() : [null, null, null, null, null, null, null, null, null, null, null, null];
        if (!cta) return months;
        var selector = '#ctl-matrix-tbody input[data-cta="' + cssEscape(cta.codigo) + '"][data-m], #ctl-month-grid input[data-cta="' + cssEscape(cta.codigo) + '"][data-m]';
        document.querySelectorAll(selector).forEach(function (inp) {
            var m = Number(inp.getAttribute('data-m'));
            var raw = String(inp.value || '').trim().replace(/,/g, '');
            months[m] = raw === '' ? (fillEmpty ? 0 : null) : toStoreQty(Number(raw) || 0);
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
            var row = document.querySelector('#ctl-matrix-tbody tr[data-cta="' + cssEscape(pend.codigo) + '"]');
            if (row && row.scrollIntoView) row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            var firstEmpty = row && row.querySelector('td.is-empty input, .cc-matrix-cell.is-empty input');
            if (firstEmpty) firstEmpty.focus();
            return true;
        }
        celebrarCentroCompleto();
        return false;
    }

    function celebrarCentroCompleto() {
        var c = control.centro || {};
        var n = (control._allCtas || []).length;
        var nombre = c.nombre || c.codigo || 'este cliente';
        var cuentaTxt = n === 1 ? 'el único producto' : ('los ' + n + ' productos');
        var frases = [
            {
                title: '¡Lo lograste!',
                html: 'Cerraste ' + cuentaTxt + ' de <b>' + escapeHtml(nombre) + '</b>.'
            },
            {
                title: '¡Cliente listo!',
                html: '<b>' + escapeHtml(nombre) + '</b> quedó redondo: ' + cuentaTxt + ' proyectados.'
            },
            {
                title: '¡Misión cumplida!',
                html: 'Ni un producto pendiente en <b>' + escapeHtml(nombre) + '</b>.'
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
            toast('success', pick.title, 'Ya no hay productos pendientes de proyectar');
        }
    }

    function guardarCapturaActual() {
        var c = control.centro;
        if (!c || control.locked) return true;
        var ctas = control._allCtas || [];
        var incompletos = [];
        ctas.forEach(function (cta) {
            var months = monthsFromGrid(cta);
            var faltan = cuentaMesesVacios(months);
            if (faltan.length) {
                incompletos.push(labelNombreCodigo(cta.nombre, cta.codigo));
                return;
            }
            persistBudget(c.empresa, c.codigo, cta.codigo, months, { completado: true });
        });
        if (incompletos.length) {
            toast('warning', 'Faltan meses', incompletos.length === 1
                ? ('Completa los 12 meses de ' + incompletos[0] + ' (el 0 sí cuenta) o pulsa Completado.')
                : ('Hay ' + incompletos.length + ' productos incompletos. Llena los 12 meses o usa Completado.'));
            drawMatrixTable();
            return false;
        }
        persistOverlay(c, { estado: c.estado === 'abierto' ? 'en_proceso' : c.estado });
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
        if (opts.skipFormGrid) {
            updateMatrixRow(codigo);
        } else {
            renderCapturaForm();
        }
        paintCompletarBtn(currentCta());
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
            tbody.innerHTML = '<tr><td colspan="30"><div class="cc-empty">Elige un cliente para ver el detalle</div></td></tr>';
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

        var yyG = String(CC.state.anioGasto).slice(2);
        var yyP = String(CC.state.anioPresupuesto).slice(2);
        var head = '<tr><th class="sticky-col">Producto</th>' +
            '<th class="num">Precio<span class="cc-th-past">venta ’' + yyG + '</span></th>' +
            '<th class="num">Venta ' + CC.state.anioGasto + '</th>' +
            '<th class="num">Precio<span class="cc-th-past">proy. ’' + yyP + '</span></th>' +
            '<th class="num">Proy. ' + CC.state.anioPresupuesto + '</th>' +
            '<th class="num">Δ%</th>';
        MONTHS.forEach(function (m) { head += '<th class="num">' + m + ' ' + yyG + '</th><th class="num">' + m + ' ' + yyP + '</th>'; });
        head += '</tr>';
        thead.innerHTML = head;

        var html = '';
        var hideGroups = opts.scope === 'cuenta' && control.cuenta && ctas.length <= 1;
        Object.keys(groups).forEach(function (g) {
            var gTotG = groups[g].reduce(function (a, x) {
                return a + (x.importeVentaVista != null ? x.importeVentaVista : importeVentaVista(x));
            }, 0);
            var gTotP = groups[g].reduce(function (a, x) { return a + importeProyeccionVista(x); }, 0);
            var closed = !!closedMap[g];
            if (!hideGroups) {
                html += '<tr class="cc-group-row" data-group="' + escapeHtml(g) + '"><td class="sticky-col" colspan="6"><i class="fa-solid fa-chevron-' + (closed ? 'right' : 'down') + ' me-1"></i>' + escapeHtml(g) + ' · ' + groups[g].length + ' productos</td>';
                html += '<td class="num cc-group-totals" colspan="2">' +
                    '<span class="cc-group-tot"><em>Venta ' + CC.state.anioGasto + '</em> ' + moneyGasto(gTotG) + '</span>' +
                    '<span class="cc-group-tot-sep">→</span>' +
                    '<span class="cc-group-tot is-proy"><em>Proy. ' + CC.state.anioPresupuesto + '</em> ' + moneyGasto(gTotP) + '</span>' +
                    '</td><td colspan="22"></td></tr>';
            }
            if (!closed || hideGroups) {
                groups[g].forEach(function (cta) {
                    var impG = cta.importeVentaVista != null ? cta.importeVentaVista : importeVentaVista(cta);
                    var impP = importeProyeccionVista(cta);
                    // Δ% en la misma moneda que se muestra (evita mezclar LineTotal MXN vs proy×TC).
                    var d = deltaPct(impP, impG);
                    var dCls = d > 15 ? 'text-danger' : (d < 0 ? 'text-success' : 'text-muted');
                    var selected = String(cta.codigo) === String(control.cuenta || '');
                    var editing = (!opts.readonly || opts.selectable) && selected;
                    html += '<tr class="cc-result-row' + (editing ? ' is-editing' : '') + (!ctaPendiente(cta) ? ' is-done' : '') + '" data-cta="' + escapeHtml(cta.codigo) + '"><td class="sticky-col">' + htmlNombreCodigo(cta.nombre, cta.codigo) + '</td>';
                    var pPast = precioVentaPasadaInfo(cta);
                    html += '<td class="num cc-price-cell" title="' +
                        escapeHtml(pPast.title || ('Precio unitario promedio de la venta ' + CC.state.anioGasto)) + '">' +
                        precioInfoHtml(pPast) + '</td>';
                    html += '<td class="num" title="Suma de LineTotalUSD (SAP). No es uds × precio redondeado.">' + moneyGasto(impG) + '</td>';
                    html += '<td class="num cc-price-cell" title="Precio de lista actual (proyección ' + CC.state.anioPresupuesto + ')">' + precioInfoHtml(precioProyeccionInfo(cta)) + '</td>';
                    html += '<td class="num fw-semibold">' + moneyGasto(impP) + '</td>';
                    html += '<td class="num ' + dCls + '" title="Variación de importe en la moneda de vista (VENTA vs PROY)">' +
                        (impP || !ctaPendiente(cta) ? ((d > 0 ? '+' : '') + d + '%') : '—') + '</td>';
                    for (var i = 0; i < 12; i++) {
                        var lleno = mesLleno(cta.ppto[i]);
                        var udsProy = lleno ? (Number(cta.ppto[i]) || 0) : 0;
                        var udsPast = Number((cta.gasto && cta.gasto[i]) || 0);
                        var pCls = lleno
                            ? (udsPast > 0 && udsProy < udsPast ? 'is-over' : 'is-ok')
                            : 'is-empty';
                        var impMesG = importeMesVentaVista(cta, i);
                        var impMesP = importeMesProyVista(cta, i);
                        html += '<td class="num text-muted" style="font-size:.75rem" title="' +
                            escapeHtml('Venta ' + CC.state.anioGasto + (udsPast ? ' · ' + qtyLabel(udsPast) + ' uds' : '') + (isUsdView() ? ' · LineTotalUSD' : ' · LineTotal')) + '">' +
                            (impMesG ? moneyGasto(impMesG) : (udsPast ? qtyLabel(udsPast) : '—')) + '</td>';
                        html += '<td class="num fw-semibold ' + pCls + '" style="font-size:.78rem" title="' +
                            escapeHtml('Proy. ' + CC.state.anioPresupuesto + (lleno ? ' · ' + qtyLabel(udsProy) + ' uds' : '') + ' · TC ' + fxRate(i).toFixed(2)) + '">' +
                            (lleno
                                ? (impMesP != null ? moneyGasto(impMesP) : qtyLabel(udsProy))
                                : '—') + '</td>';
                    }
                    html += '</tr>';
                });
            }
        });
        var emptyMsg = opts.scope === 'cuenta' && control.cuenta
            ? 'No hay filas para este producto con los filtros actuales'
            : 'Sin productos asignados a este cliente';
        if (!html) {
            tbody.innerHTML = '<tr><td colspan="30"><div class="cc-empty">' + emptyMsg + '</div></td></tr>';
        } else {
            // Fila de totales por mes (todas las cuentas filtradas, aunque el grupo esté cerrado).
            var sumG = 0;
            var sumP = 0;
            var mesG = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            var mesP = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            ctas.forEach(function (cta) {
                sumG += cta.importeVentaVista != null ? cta.importeVentaVista : importeVentaVista(cta);
                sumP += importeProyeccionVista(cta);
                for (var mi = 0; mi < 12; mi++) {
                    mesG[mi] += Number(importeMesVentaVista(cta, mi)) || 0;
                    var mp = importeMesProyVista(cta, mi);
                    if (mp != null) mesP[mi] += Number(mp) || 0;
                }
            });
            var dTot = deltaPct(sumP, sumG);
            var dTotCls = dTot > 15 ? 'text-danger' : (dTot < 0 ? 'text-success' : 'text-muted');
            html += '<tr class="cc-month-totals-row">' +
                '<td class="sticky-col"><strong>Totales</strong>' +
                '<span class="cc-month-totals-sub">' + ctas.length + (ctas.length === 1 ? ' producto' : ' productos') + '</span></td>' +
                '<td class="num">—</td>' +
                '<td class="num fw-semibold">' + moneyGasto(sumG) + '</td>' +
                '<td class="num">—</td>' +
                '<td class="num fw-semibold">' + moneyGasto(sumP) + '</td>' +
                '<td class="num ' + dTotCls + '">' + ((dTot > 0 ? '+' : '') + dTot + '%') + '</td>';
            for (var ti = 0; ti < 12; ti++) {
                html += '<td class="num" title="' + escapeHtml('Total venta ' + MONTHS[ti] + ' ' + CC.state.anioGasto) + '">' +
                    (mesG[ti] ? moneyGasto(mesG[ti]) : '—') + '</td>';
                html += '<td class="num fw-semibold" title="' + escapeHtml('Total proy. ' + MONTHS[ti] + ' ' + CC.state.anioPresupuesto) + '">' +
                    (mesP[ti] ? moneyGasto(mesP[ti]) : '—') + '</td>';
            }
            html += '</tr>';
            tbody.innerHTML = html;
        }

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
        if (mesBloqueadoBudget(m)) {
            toast('warning', 'Mes bloqueado', 'Este mes viene de la venta real del año en curso (' + labelTipoBudget() + ') y no se puede editar.');
            refreshControlAfterEdit(codigo, { skipFormGrid: true });
            return;
        }
        var raw = String(inp.value || '').trim().replace(/,/g, '');
        var valN = raw === '' ? null : toStoreQty(Number(raw) || 0);
        var months = pptoDe(c.empresa, c.codigo, cta, cta.gasto);
        var wasEmpty = !mesesTodosLlenos(months) && months.every(function (x) { return !mesLleno(x); });
        months[m] = valN;
        persistBudget(c.empresa, c.codigo, codigo, months);

        var cell = inp.closest('.cc-month-cell, .cc-matrix-cell, td');
        if (cell) {
            var fake = { gasto: cta.gasto, ppto: months };
            var cls = monthCellClass(fake, m);
            if (cell.classList.contains('cc-matrix-cell') || cell.tagName === 'TD') {
                cell.className = cls.replace('cc-month-cell', 'cc-matrix-cell') + (mesBloqueadoBudget(m) ? ' is-locked' : '');
            } else {
                cell.className = cls;
            }
        }

        if (wasEmpty && valN > 0 && m === 0 && !control.locked && !budgetLockCount()) {
            if (window.Swal) {
                Swal.fire({
                    title: '¿Cantidad fija mensual?',
                    html: 'Capturaste <b>' + escapeHtml(qtyLabel(valN)) + '</b> unidades en enero para <b>' + escapeHtml(cta.nombre) + '</b>. ¿Lo replicamos en los 12 meses?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0a0a0a',
                    confirmButtonText: 'Sí, mantener mensual',
                    cancelButtonText: 'Solo enero'
                }).then(function (res) {
                    if (res.isConfirmed) {
                        var filled = months.map(function (v, idx) {
                            return mesBloqueadoBudget(idx) ? v : valN;
                        });
                        persistBudget(c.empresa, c.codigo, codigo, filled);
                        toast('success', 'Cantidad fija aplicada', 'Los meses editables quedaron en ' + qtyLabel(valN));
                    }
                    refreshControlAfterEdit(codigo);
                });
                return;
            }
        }
        refreshControlAfterEdit(codigo, { skipFormGrid: true });
    }

    function bindCapturaQuick() {
        var copy = document.getElementById('ctl-copy-year');
        if (copy) copy.addEventListener('click', function () {
            var c = control.centro;
            var list = targetCtasForTools();
            if (!c || !list.length || control.locked) return;
            var run = function () {
                list.forEach(function (cta) {
                    persistBudget(c.empresa, c.codigo, cta.codigo, (cta.gasto || []).slice());
                });
                refreshControlAfterEdit((list[0] && list[0].codigo) || control.cuenta);
                toast('success', 'Copiado', list.length === 1
                    ? ('Se copió la venta de ' + CC.state.anioGasto + ' a ' + labelNombreCodigo(list[0].nombre, list[0].codigo))
                    : ('Se copió ' + CC.state.anioGasto + ' en ' + list.length + ' productos'));
            };
            if (list.length > 1 && window.Swal) {
                Swal.fire({
                    icon: 'question',
                    title: 'Copiar a ' + list.length + ' productos',
                    text: 'Se reemplazará la proyección visible con la venta real de ' + CC.state.anioGasto + '.',
                    showCancelButton: true,
                    confirmButtonText: 'Copiar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0a0a0a'
                }).then(function (r) { if (r.isConfirmed) run(); });
                return;
            }
            run();
        });
        var clear = document.getElementById('ctl-clear-year');
        if (clear) clear.addEventListener('click', function () {
            var c = control.centro;
            var list = targetCtasForTools();
            if (!c || !list.length || control.locked) return;
            var run = function () {
                list.forEach(function (cta) {
                    persistBudget(c.empresa, c.codigo, cta.codigo, [null, null, null, null, null, null, null, null, null, null, null, null], { completado: false });
                });
                refreshControlAfterEdit((list[0] && list[0].codigo) || control.cuenta);
                toast('success', 'Limpio', list.length === 1
                    ? ('Se quitó la proyección de ' + labelNombreCodigo(list[0].nombre, list[0].codigo))
                    : ('Se limpiaron ' + list.length + ' productos'));
            };
            if (list.length > 1 && window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Limpiar ' + list.length + ' productos',
                    text: 'Se borrarán las cantidades proyectadas visibles.',
                    showCancelButton: true,
                    confirmButtonText: 'Limpiar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#b91c1c'
                }).then(function (r) { if (r.isConfirmed) run(); });
                return;
            }
            run();
        });
        var infl = document.getElementById('ctl-apply-infl');
        if (infl) infl.addEventListener('click', function () {
            var c = control.centro;
            var list = targetCtasForTools();
            if (!c || !list.length || control.locked) return;
            var pctIn = document.getElementById('ctl-ajuste-pct');
            var pctVal = pctIn ? Number(pctIn.value) : 0;
            if (!isFinite(pctVal)) pctVal = 0;
            var rate = 1 + (pctVal / 100);
            var run = function () {
                list.forEach(function (cta) {
                    var months = (cta.gasto || []).map(function (v) { return (Number(v) || 0) * rate; });
                    persistBudget(c.empresa, c.codigo, cta.codigo, months);
                });
                refreshControlAfterEdit((list[0] && list[0].codigo) || control.cuenta);
                toast('success', 'Ajuste aplicado', list.length === 1
                    ? ('Cantidad ' + CC.state.anioGasto + ' × (1 ' + (pctVal >= 0 ? '+ ' : '− ') + Math.abs(pctVal) + '%)')
                    : (list.length + ' productos × (1 ' + (pctVal >= 0 ? '+ ' : '− ') + Math.abs(pctVal) + '%)'));
            };
            if (list.length > 1 && window.Swal) {
                Swal.fire({
                    icon: 'question',
                    title: 'Aplicar % a ' + list.length + ' productos',
                    text: 'Se recalculará la proyección visible con el porcentaje indicado.',
                    showCancelButton: true,
                    confirmButtonText: 'Aplicar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0a0a0a'
                }).then(function (r) { if (r.isConfirmed) run(); });
                return;
            }
            run();
        });
        var completar = document.getElementById('ctl-completar');
        if (completar) completar.addEventListener('click', function () {
            var c = control.centro;
            var list = targetCtasForTools();
            if (!c || !list.length || control.locked) return;
            list.forEach(function (cta) {
                var months = monthsFromGrid(cta, true);
                persistBudget(c.empresa, c.codigo, cta.codigo, months, { completado: true });
            });
            refreshControlAfterEdit((list[0] && list[0].codigo) || control.cuenta);
            toast('success', 'Completado', list.length === 1
                ? 'Los meses vacíos quedaron en 0'
                : ('Se completaron ' + list.length + ' productos (vacíos en 0)'));
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
            setText('cc-page-kicker', 'Detalle del cliente');
            setText('cc-page-title', 'Cliente');
            if (sub) { sub.hidden = false; setText('cc-page-sub', 'Elige un cliente desde el visor.'); }
            if (facts) facts.hidden = true;
            return;
        }
        var stt = statsDeCentro(c);
        var dep = deptoDeCentro(c);
        var barCls = stt.pendientes ? 'warn' : 'good';
        setText('cc-page-kicker', 'Detalle · ' + (c.empresa || '—') + (dep && dep !== '—' ? ' · ' + dep : '') + (c.codigo ? ' · ' + c.codigo : ''));
        var title = document.getElementById('cc-page-title');
        if (title) title.innerHTML = escapeHtml(c.nombre || c.codigo || 'Cliente');
        if (sub) sub.hidden = true;
        if (facts) {
            facts.hidden = false;
            facts.innerHTML =
                factHtml('Empresa', escapeHtml(c.empresa || '—')) +
                factHtml('Tot Venta', money(stt.totG)) +
                factHtml('Tot Proy.', money(stt.totP)) +
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
            ? (tot.listos + ' de ' + tot.n + ' clientes listos · ' + tot.capturadas + ' de ' + tot.totalCtas + ' productos capturados')
            : 'No hay clientes para sumar');
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
            '<td colspan="4">Totales · ' + tot.n + (tot.n === 1 ? ' cliente' : ' clientes') + '</td>' +
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
            tb.innerHTML = '<tr><td colspan="10"><div class="cc-empty"><i class="fa-solid fa-inbox"></i>No hay clientes con esos filtros</div></td></tr>';
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
        var base = CC.state.detalleUrl || '/Ventas/Captura/detalle';
        var ciclo = val('ctl-ciclo') || CC.state.cicloCodigo || '';
        return base + '?empresa=' + encodeURIComponent(c.empresa || '') +
            '&cc=' + encodeURIComponent(c.codigo || '') +
            '&ciclo=' + encodeURIComponent(ciclo);
    }

    function paintDispMontoLabel() {
        var mismo = val('d-modo') === 'mismo';
        var label = document.getElementById('d-monto-label');
        var hint = document.getElementById('d-modo-hint');
        if (label) label.textContent = mismo ? 'Unidades mensuales' : 'Unidades totales';
        if (hint) {
            hint.textContent = mismo
                ? 'Cantidad fija: se copia el mismo número de unidades a cada mes del rango Desde–Hasta.'
                : 'El total de unidades se reparte entre los meses del rango (el tipo de cambio no aplica).';
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
                if (!cta) {
                    toast('warning', 'Elige un producto', 'Haz clic en una fila de la tabla antes de dispersar.');
                    return;
                }
                var codigo = cta.codigo;
                var monto = toStoreQty(Number(val('d-monto')) || 0);
                var from = Number(val('d-desde')) || 0;
                var to = Number(val('d-hasta')) || 11;
                var modo = val('d-modo');
                if (!cta || to < from) return;
                var months = pptoDe(c.empresa, c.codigo, cta, cta.gasto);
                var n = to - from + 1;
                if (modo === 'igual') {
                    var each = Math.round((monto / n) * 100) / 100;
                    for (var i = from; i <= to; i++) months[i] = each;
                } else if (modo === 'mismo') {
                    for (var k = from; k <= to; k++) months[k] = monto;
                } else {
                    var weights = cta.gasto.slice(from, to + 1);
                    var tw = sum(weights) || n;
                    for (var j = from; j <= to; j++) months[j] = Math.round(monto * ((cta.gasto[j] || 0) / tw) * 100) / 100;
                }
                persistBudget(c.empresa, c.codigo, codigo, months);
                hideModal('modalDispersar');
                control.cuenta = codigo;
                refreshControlAfterEdit(codigo);
                var rango = MONTHS[from] + '–' + MONTHS[to];
                toast('success', modo === 'mismo' ? 'Cantidad fija' : 'Unidades dispersadas', modo === 'mismo'
                    ? qtyLabel(monto) + ' uds en cada mes (' + rango + ')'
                    : qtyLabel(monto) + ' uds en ' + cta.nombre);
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
            toast('success', 'Captura guardada', 'La proyección quedó en el servidor');
        });
        var saveNext = document.getElementById('ctl-guardar-seguir');
        if (saveNext) saveNext.addEventListener('click', function () {
            var codigo = control.cuenta;
            if (!guardarCapturaActual()) return;
            refreshControlAfterEdit(codigo);
            if (!irSiguientePendiente(codigo)) return;
            toast('success', 'Captura guardada', 'La proyección quedó en el servidor');
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
                window.location.href = '/ProyeccionesVentas/api/captura/plantilla?ciclo=' + encodeURIComponent(ciclo);
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
                            '<p style="margin:0;color:#71717a">Guardando tus proyecciones. No cierres esta ventana.</p>',
                        position: 'center',
                        width: '32rem',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: function () { Swal.showLoading(); }
                    });
                }
                fetch('/ProyeccionesVentas/api/captura/importar', {
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
                        toast(n ? 'success' : 'error', n ? 'Importación lista' : 'Nada se importó', n + ' productos actualizados');
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
        setText('an-kicker', 'Supervisión · proyección ' + p);
        setText('an-sub', 'Cuánto se lleva capturado por empresa, cliente, producto o usuario, y la diferencia vs la venta real (cantidad × costo).');
        setText('an-kpi-over-hint', 'Proy. ' + p + ' > 110% de la venta ' + g);
        setText('an-kpi-yoy-hint', 'Promedio de aumento vs ' + g);
        var title = document.getElementById('an-chart-emp-title');
        if (title) title.innerHTML = '<i class="fa-solid fa-chart-column"></i> Venta ' + g + ' vs proyección ' + p;
        setText('an-th-gasto', 'Venta ' + g);
        setText('an-th-ppto', 'Proy. ' + p);
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
        applyPeriodFromCiclo(findCiclo(ciclo), ciclo);
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
            fetch('/Ventas/Asignaciones/' + encodeURIComponent(ciclo) + '/asignaciones', {
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
        var pending = (CC.state.centros || []).filter(function (c) {
            var key = gastoCacheKey(c);
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
        if (id === 'modalIndicadores') {
            renderPeriodBanner();
            renderTcMesesEditor();
        }
        if (id === 'modalDispersar') paintDispMontoLabel();
        if (id === 'modalVentasPasadas') fillVentasPasadasYears();
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
                    anioReferencia: new Date().getFullYear(),
                    anio: new Date().getFullYear() + 1,
                    inicio: '2026-10-01',
                    fin: '2026-10-31',
                    capturaHasta: '2026-10-31',
                    revisionDesde: '2026-11-01',
                    estado: 'abierto',
                    inflacion: 4,
                    tipoCambio: 20,
                    tipoBudget: '3+9',
                    observaciones: ''
                };
            setVal('p-codigo', p.codigo);
            setVal('p-nombre', p.nombre);
            setVal('p-anio-ref', p.anioReferencia || 2026);
            setVal('p-anio', p.anio || 2027);
            setVal('p-inicio', p.inicio); setVal('p-fin', p.fin);
            setVal('p-captura', p.capturaHasta); setVal('p-revision', p.revisionDesde);
            setVal('p-estado', normalizeCicloEstado(p.estado)); setVal('p-inflacion', p.inflacion); setVal('p-tc', p.tipoCambio);
            setVal('p-tipo-budget', p.tipoBudget || '3+9');
            setVal('p-obs', p.observaciones || '');
            var tipoBudgetEl = document.getElementById('p-tipo-budget');
            var tipoFijo = editing && !!normalizeTipoBudgetClient(p.tipoBudget);
            if (tipoBudgetEl) {
                tipoBudgetEl.disabled = tipoFijo;
                tipoBudgetEl.title = tipoFijo
                    ? 'El tipo queda fijo al crear el ciclo'
                    : (editing
                        ? 'Este ciclo aún no tenía tipo: elige Forecast o SIOP y guarda'
                        : 'Forecast: venta real + Budget · SIOP: Budget editable');
            }
            syncTipoBudgetModalUi();
            if (editing) {
                var tipoHint = document.getElementById('p-tipo-budget-hint');
                if (tipoHint) {
                    tipoHint.textContent = tipoFijo
                        ? ('Fijo: ' + labelTipoBudget(p.tipoBudget) + ' (no se puede cambiar)')
                        : 'Aún sin tipo: elige 3+9 / 6+6 / 9+3 / SIOP y guarda para fijarlo.';
                }
            }
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

    function initCostos() {
        var url = CC.state.costosUrl || '/ProyeccionesVentas/api/costos';
        var items = [];
        var page = 1;
        var lastPage = 1;
        var total = 0;
        var from = 0;
        var to = 0;
        var searchTimer = null;
        var empSel = document.getElementById('pv-costos-empresa');
        var clienteEl = document.getElementById('pv-costos-cliente');
        var itemEl = document.getElementById('pv-costos-itemcode');
        var perPageEl = document.getElementById('pv-costos-per-page');
        var tbody = document.getElementById('pv-costos-tbody');
        var hint = document.getElementById('pv-costos-hint');
        var pageInfo = document.getElementById('pv-costos-page-info');
        var pageLabel = document.getElementById('pv-costos-page-label');
        var prevBtn = document.getElementById('pv-costos-prev');
        var nextBtn = document.getElementById('pv-costos-next');
        if (!tbody) return;

        function fillEmpresas() {
            if (!empSel) return;
            var cur = empSel.value || '';
            var set = {};
            (CC.state.empresasLocales || []).forEach(function (e) {
                var code = String(e.codigo || e.id || e.nombre || '').toUpperCase();
                if (code) set[code] = e.nombre || code;
            });
            ['AUSTIN', 'IMSA', 'PITIC', 'SYDNEY'].forEach(function (k) {
                if (!set[k]) set[k] = k;
            });
            items.forEach(function (it) {
                if (it.empresa) set[String(it.empresa).toUpperCase()] = it.empresa;
            });
            var opts = '<option value="">Todas las empresas</option>';
            Object.keys(set).sort().forEach(function (k) {
                opts += '<option value="' + escapeHtml(k) + '"' + (k === cur ? ' selected' : '') + '>' +
                    escapeHtml(set[k]) + '</option>';
            });
            empSel.innerHTML = opts;
            if (cur) empSel.value = cur;
        }

        function updatePager() {
            if (hint) {
                hint.textContent = total + (total === 1 ? ' registro' : ' registros');
            }
            if (pageInfo) {
                pageInfo.textContent = total
                    ? ('Mostrando ' + from + '–' + to + ' de ' + total)
                    : 'Sin resultados';
            }
            if (pageLabel) pageLabel.textContent = page + ' / ' + lastPage;
            if (prevBtn) prevBtn.disabled = page <= 1;
            if (nextBtn) nextBtn.disabled = page >= lastPage;
        }

        function render() {
            var MESES = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            updatePager();
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="11"><div class="cc-empty">Sin productos para mostrar</div></td></tr>';
                return;
            }
            tbody.innerHTML = items.map(function (it, idx) {
                var mon = String(it.moneda || 'MXN').toUpperCase();
                var costoTxt = (mon === 'USD' ? 'US$' : '$') +
                    (Number(it.costo_unitario) || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                var codigo = String(it.producto_codigo || '').trim();
                var nombre = String(it.producto_nombre || '').trim();
                if (nombre && nombre.toUpperCase() === codigo.toUpperCase()) nombre = '';
                var mesesLabel = it.meses_label || '—';
                return '<tr data-idx="' + idx + '">' +
                    '<td>' + escapeHtml(it.empresa || '—') + '</td>' +
                    '<td><code>' + escapeHtml(it.card_code || '—') + '</code></td>' +
                    '<td>' + escapeHtml(it.card_name || '—') + '</td>' +
                    '<td><code>' + escapeHtml(codigo || '—') + '</code></td>' +
                    '<td>' + escapeHtml(nombre || '—') + '</td>' +
                    '<td>' + escapeHtml(mesesLabel) + '</td>' +
                    '<td class="num fw-semibold">' + escapeHtml(costoTxt) + '</td>' +
                    '<td>' + escapeHtml(mon) + '</td>' +
                    '<td>' + (it.tiene_maestro
                        ? '<span class="cc-badge cc-badge-aceptado">Maestro</span>'
                        : '<span class="cc-badge cc-badge-en_proceso">Sin maestro</span>') + '</td>' +
                    '<td>' + escapeHtml(it.updated_at || '—') + '</td>' +
                    '<td class="num"><div class="cc-actions-row">' +
                    '<button type="button" class="cc-btn cc-btn-sm" data-hist-costo' +
                    ' data-emp="' + escapeHtml(it.empresa) + '"' +
                    ' data-cod="' + escapeHtml(codigo) + '"' +
                    ' data-nom="' + escapeHtml(nombre || codigo) + '"' +
                    ' data-card="' + escapeHtml(it.card_code || '') + '"' +
                    ' data-card-name="' + escapeHtml(it.card_name || '') + '"' +
                    ' data-mes="0"' +
                    ' title="Ver historial de cambios">' +
                    '<i class="fa-solid fa-clock-rotate-left"></i></button>' +
                    '<button type="button" class="cc-btn cc-btn-sm" data-sync-api-costo' +
                    ' data-emp="' + escapeHtml(it.empresa) + '"' +
                    ' data-cod="' + escapeHtml(codigo) + '"' +
                    ' data-nom="' + escapeHtml(nombre || codigo) + '"' +
                    ' data-card="' + escapeHtml(it.card_code || '') + '"' +
                    ' data-mes="' + escapeHtml(String(it.mes || lastMesConPrecio(it) || 0)) + '"' +
                    ' title="Actualizar desde API el mes de referencia">' +
                    '<i class="fa-solid fa-cloud-arrow-down"></i></button>' +
                    '<button type="button" class="cc-btn cc-btn-sm" data-edit-meses-costo data-idx="' + idx + '"' +
                    ' title="Editar precios por mes">' +
                    '<i class="fa-solid fa-calendar-days"></i></button>' +
                    '</div></td>' +
                    '</tr>';
            }).join('');
            tbody.querySelectorAll('[data-edit-meses-costo]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var i = Number(btn.getAttribute('data-idx'));
                    openCostoMesesModal(items[i]);
                });
            });
            tbody.querySelectorAll('[data-hist-costo]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openHistorialPrecio(
                        btn.getAttribute('data-emp') || '',
                        btn.getAttribute('data-cod') || '',
                        btn.getAttribute('data-nom') || '',
                        btn.getAttribute('data-card') || '',
                        btn.getAttribute('data-card-name') || '',
                        btn.getAttribute('data-mes') || '0'
                    );
                });
            });
            tbody.querySelectorAll('[data-sync-api-costo]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    actualizarPrecioDesdeApi(btn);
                });
            });
        }

        function lastMesConPrecio(it) {
            var arr = (it && it.precio_meses) || [];
            for (var i = 11; i >= 0; i--) {
                if (Number(arr[i]) > 0) return i + 1;
            }
            return 0;
        }

        function openCostoMesesModal(it) {
            if (!it) return;
            var MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            var codigo = String(it.producto_codigo || '').trim();
            var nombre = String(it.producto_nombre || '').trim();
            if (nombre && nombre.toUpperCase() === codigo.toUpperCase()) nombre = '';
            var base = Number(it.costo_unitario) || 0;
            var mon = String(it.moneda || 'MXN').toUpperCase();
            var meses = Array.isArray(it.precio_meses) ? it.precio_meses.slice(0, 12) : [];
            while (meses.length < 12) meses.push(null);

            setVal('pcm-empresa', it.empresa || '');
            setVal('pcm-codigo', codigo);
            setVal('pcm-nombre', nombre || codigo);
            setVal('pcm-card', it.card_code || '');
            setVal('pcm-card-name', it.card_name || '');
            setVal('pcm-base', base > 0 ? String(base) : '');
            setVal('pcm-moneda', mon);
            setText('pcm-sub', (nombre || codigo) + ' · ' + codigo +
                (it.card_code ? (' · ' + it.card_code) : '') +
                (it.empresa ? (' · ' + it.empresa) : ''));
            var hint = document.getElementById('pcm-base-hint');
            if (hint) {
                hint.textContent = base > 0
                    ? ('Global: ' + moneyTxt(base, mon) + ' · deja vacío un mes para no tocarlo al guardar')
                    : 'Captura un global o valores por mes.';
            }
            var box = document.getElementById('pcm-months');
            if (box) {
                box.innerHTML = MESES.map(function (m, i) {
                    var ov = Number(meses[i]);
                    var custom = isFinite(ov) && ov > 0 && Math.abs(ov - base) > 0.0001;
                    var has = isFinite(ov) && ov > 0;
                    return '<div class="cc-tc-month' + (custom ? ' is-custom' : '') + '">' +
                        '<label for="pcm-m-' + i + '">' + m + '</label>' +
                        '<input id="pcm-m-' + i + '" type="number" step="0.0001" min="0" placeholder="' +
                        (base > 0 ? base.toFixed(2) : '0') + '" value="' + (has ? ov.toFixed(4) : '') + '">' +
                        '</div>';
                }).join('');
            }
            highlightCostoMesesEditor();
            showModal('modalPvCostoMeses');
        }

        function highlightCostoMesesEditor() {
            var base = Number(val('pcm-base')) || 0;
            for (var i = 0; i < 12; i++) {
                var wrap = document.querySelector('#pcm-months .cc-tc-month:nth-child(' + (i + 1) + ')');
                var el = document.getElementById('pcm-m-' + i);
                if (!wrap || !el) continue;
                var raw = String(el.value || '').trim();
                var v = Number(raw);
                var custom = raw !== '' && isFinite(v) && v > 0 && Math.abs(v - base) > 0.0001;
                wrap.classList.toggle('is-custom', custom || (raw !== '' && isFinite(v) && v > 0));
            }
        }

        function readCostoMesesFromEditor() {
            var out = [];
            for (var i = 0; i < 12; i++) {
                var el = document.getElementById('pcm-m-' + i);
                var raw = el ? String(el.value || '').trim() : '';
                var v = Number(raw);
                out.push(raw !== '' && isFinite(v) && v > 0 ? Math.round(v * 10000) / 10000 : null);
            }
            return out;
        }

        function saveCostoMesesModal() {
            var emp = val('pcm-empresa');
            var cod = val('pcm-codigo');
            var nom = val('pcm-nombre');
            var card = val('pcm-card');
            var cardName = val('pcm-card-name');
            var mon = val('pcm-moneda') || 'MXN';
            var base = Number(val('pcm-base')) || 0;
            var meses = readCostoMesesFromEditor();
            if (!emp || !cod) {
                toast('warning', 'Faltan datos', 'Empresa e ItemCode son obligatorios.');
                return;
            }
            var jobs = [];
            for (var i = 0; i < 12; i++) {
                var p = meses[i];
                if (p === null || !(p > 0)) continue;
                jobs.push({
                    empresa: emp,
                    producto_codigo: cod,
                    producto_nombre: nom || cod,
                    card_code: card,
                    card_name: cardName,
                    mes: i + 1,
                    costo_unitario: p,
                    moneda: mon
                });
            }
            // Si no hay ningún mes capturado pero sí global, guarda los 12 con el global.
            if (!jobs.length && base > 0) {
                for (var j = 0; j < 12; j++) {
                    jobs.push({
                        empresa: emp,
                        producto_codigo: cod,
                        producto_nombre: nom || cod,
                        card_code: card,
                        card_name: cardName,
                        mes: j + 1,
                        costo_unitario: base,
                        moneda: mon
                    });
                }
            }
            if (!jobs.length) {
                toast('warning', 'Sin precios', 'Captura al menos un mes o un precio global.');
                return;
            }
            var saveBtn = document.getElementById('pcm-save');
            if (saveBtn) saveBtn.disabled = true;
            var chain = Promise.resolve();
            var okCount = 0;
            jobs.forEach(function (payload) {
                chain = chain.then(function () {
                    return fetch(url, {
                        method: 'PUT',
                        headers: apiJsonHeaders(),
                        body: JSON.stringify(payload)
                    }).then(function (r) {
                        return r.json().then(function (json) {
                            if (!r.ok) throw new Error((json && json.message) || 'Error al guardar mes ' + payload.mes);
                            okCount++;
                            return json;
                        });
                    });
                });
            });
            chain.then(function () {
                toast('success', 'Precios guardados', okCount + ' mes' + (okCount === 1 ? '' : 'es') + ' actualizado(s).');
                hideModal('modalPvCostoMeses');
                load(page);
            }).catch(function (err) {
                toast('error', 'No se guardó', err && err.message ? err.message : 'Error');
            }).then(function () {
                if (saveBtn) saveBtn.disabled = false;
            });
        }

        function bindCostoMesesUi() {
            if (CC._costoMesesBound) return;
            CC._costoMesesBound = true;
            var applyAll = document.getElementById('pcm-apply-all');
            if (applyAll) applyAll.addEventListener('click', function () {
                var base = Number(val('pcm-base'));
                if (!(base > 0)) {
                    toast('warning', 'Precio global', 'Captura un precio global válido.');
                    return;
                }
                for (var i = 0; i < 12; i++) {
                    var el = document.getElementById('pcm-m-' + i);
                    if (el) el.value = base.toFixed(4);
                }
                highlightCostoMesesEditor();
            });
            var reset = document.getElementById('pcm-reset');
            if (reset) reset.addEventListener('click', function () {
                for (var i = 0; i < 12; i++) {
                    var el = document.getElementById('pcm-m-' + i);
                    if (el) el.value = '';
                }
                highlightCostoMesesEditor();
            });
            var save = document.getElementById('pcm-save');
            if (save) save.addEventListener('click', saveCostoMesesModal);
            var monthsBox = document.getElementById('pcm-months');
            if (monthsBox) {
                monthsBox.addEventListener('input', function (ev) {
                    if (ev.target && ev.target.id && ev.target.id.indexOf('pcm-m-') === 0) {
                        highlightCostoMesesEditor();
                    }
                });
            }
            var baseEl = document.getElementById('pcm-base');
            if (baseEl) baseEl.addEventListener('input', highlightCostoMesesEditor);
        }

        bindCostoMesesUi();

        function actualizarPrecioDesdeApi(btn) {
            var emp = btn.getAttribute('data-emp') || '';
            var cod = btn.getAttribute('data-cod') || '';
            var card = btn.getAttribute('data-card') || '';
            var mes = Number(btn.getAttribute('data-mes') || 0) || 0;
            var nom = btn.getAttribute('data-nom') || cod;
            if (!emp || !cod) {
                toast('error', 'Datos incompletos', 'Falta empresa o ItemCode.');
                return;
            }
            btn.disabled = true;
            var payloadBase = {
                empresa: emp,
                card_code: card,
                producto_codigo: cod,
                mes: mes,
                anio: CC.state.anioGasto || new Date().getFullYear()
            };
            fetch(url + '/actualizar-desde-api', {
                method: 'POST',
                headers: apiJsonHeaders(),
                body: JSON.stringify(Object.assign({}, payloadBase, { confirmar: false }))
            }).then(function (r) {
                return r.json().then(function (json) {
                    if (!r.ok) throw new Error((json && json.message) || 'No se pudo consultar la API');
                    return json;
                });
            }).then(function (json) {
                if (json.igual) {
                    toast('info', 'Sin cambios', 'El precio local ya coincide con la API (' +
                        moneyTxt(json.precio_api, json.moneda_api) + ').');
                    return;
                }
                var msg = 'Producto: ' + (nom || cod) + '\n' +
                    'ItemCode: ' + cod + (card ? (' · CardCode: ' + card) : '') +
                    (mes ? (' · Mes: ' + mes) : '') + '\n\n' +
                    'Local: ' + moneyTxt(json.precio_local, json.moneda_local) + '\n' +
                    'API:   ' + moneyTxt(json.precio_api, json.moneda_api) + '\n\n' +
                    '¿Actualizar solo el precio con el valor de la API?';
                if (!window.confirm(msg)) return;

                return fetch(url + '/actualizar-desde-api', {
                    method: 'POST',
                    headers: apiJsonHeaders(),
                    body: JSON.stringify(Object.assign({}, payloadBase, { confirmar: true }))
                }).then(function (r) {
                    return r.json().then(function (j2) {
                        if (!r.ok) throw new Error((j2 && j2.message) || 'No se pudo actualizar');
                        return j2;
                    });
                }).then(function (j2) {
                    if (j2.actualizado) {
                        toast('success', 'Precio actualizado',
                            moneyTxt(j2.precio_anterior, j2.moneda_anterior) + ' → ' +
                            moneyTxt(j2.precio_nuevo, j2.moneda_nueva));
                        load(page);
                    } else {
                        toast('info', 'Sin cambios', j2.message || 'El precio ya era el mismo.');
                    }
                });
            }).catch(function (err) {
                toast('error', 'No se actualizó', err && err.message ? err.message : 'Error');
            }).then(function () {
                btn.disabled = false;
            });
        }

        function buildQuery(goPage) {
            var params = [];
            var emp = empSel ? String(empSel.value || '') : '';
            var cliente = clienteEl ? String(clienteEl.value || '').trim() : '';
            var itemcode = itemEl ? String(itemEl.value || '').trim() : '';
            var perPage = perPageEl ? (Number(perPageEl.value) || 25) : 25;
            if (emp) params.push('empresa=' + encodeURIComponent(emp));
            if (cliente) params.push('cliente=' + encodeURIComponent(cliente));
            if (itemcode) params.push('itemcode=' + encodeURIComponent(itemcode));
            params.push('page=' + encodeURIComponent(String(goPage || page || 1)));
            params.push('per_page=' + encodeURIComponent(String(perPage)));
            return params.length ? ('?' + params.join('&')) : '';
        }

        function load(goPage) {
            if (goPage) page = goPage;
            if (hint) hint.textContent = 'Cargando…';
            fetch(url + buildQuery(page), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    items = (json && json.items) || [];
                    total = Number(json && json.total) || items.length;
                    page = Number(json && json.page) || page || 1;
                    lastPage = Number(json && json.last_page) || 1;
                    from = Number(json && json.from) || 0;
                    to = Number(json && json.to) || 0;
                    fillEmpresas();
                    render();
                })
                .catch(function () {
                    items = [];
                    total = 0;
                    from = 0;
                    to = 0;
                    lastPage = 1;
                    tbody.innerHTML = '<tr><td colspan="11"><div class="cc-empty">No se pudieron cargar los precios</div></td></tr>';
                    if (hint) hint.textContent = 'Error al cargar';
                    updatePager();
                });
        }

        function scheduleSearch() {
            if (searchTimer) clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { load(1); }, 320);
        }

        function moneyTxt(valor, moneda) {
            var mon = String(moneda || 'MXN').toUpperCase();
            if (valor === null || valor === undefined || valor === '') return '—';
            return (mon === 'USD' ? 'US$' : '$') +
                Number(valor).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
        }

        function openHistorialPrecio(emp, cod, nom, card, cardName, mes) {
            var MESES = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            var histTbody = document.getElementById('pv-hist-tbody');
            var histHint = document.getElementById('pv-hist-hint');
            var mesN = Number(mes) || 0;
            var mesTxt = mesN >= 1 && mesN <= 12 ? (MESES[mesN] + ' (' + mesN + ')') : '—';
            var clienteTxt = (card || '') + (cardName ? (' — ' + cardName) : '');
            setText('pv-hist-prod-label', (emp || '—') + (clienteTxt ? (' · ' + clienteTxt) : ''));
            setText('pv-hist-nombre', nom || cod || '—');
            setText('pv-hist-itemcode', cod || '—');
            setText('pv-hist-mes', mesTxt);
            setText('pv-hist-cliente', clienteTxt || '—');
            if (histTbody) {
                histTbody.innerHTML = '<tr><td colspan="9"><div class="cc-empty">Cargando historial…</div></td></tr>';
            }
            if (histHint) histHint.textContent = 'Consultando movimientos del precio unitario…';
            showModal('modalPvHistorialPrecio');
            var histUrl = CC.state.costosHistorialUrl || (url + '/historial');
            var qs = '?empresa=' + encodeURIComponent(emp) +
                '&producto_codigo=' + encodeURIComponent(cod);
            if (card) qs += '&card_code=' + encodeURIComponent(card);
            if (mesN > 0) qs += '&mes=' + encodeURIComponent(String(mesN));
            fetch(histUrl + qs, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) {
                    return r.json().then(function (json) {
                        if (!r.ok) throw new Error((json && json.message) || 'No se pudo cargar el historial');
                        return json;
                    });
                })
                .then(function (json) {
                    var rows = (json && json.items) || [];
                    var actual = json.precio_actual != null
                        ? moneyTxt(json.precio_actual, json.moneda_actual)
                        : '—';
                    if (json.producto_nombre) setText('pv-hist-nombre', json.producto_nombre);
                    if (json.producto_codigo) setText('pv-hist-itemcode', json.producto_codigo);
                    if (json.mes_label) setText('pv-hist-mes', json.mes_label);
                    var cli = (json.card_code || card || '');
                    if (json.card_name || cardName) {
                        cli += (cli ? ' — ' : '') + (json.card_name || cardName);
                    }
                    if (cli) setText('pv-hist-cliente', cli);
                    setText('pv-hist-prod-label',
                        (json.empresa || emp || '—') +
                        (cli ? (' · ' + cli) : '')
                    );
                    if (histHint) {
                        histHint.textContent = rows.length
                            ? (rows.length + (rows.length === 1 ? ' movimiento' : ' movimientos') + ' · precio actual ' + actual)
                            : ('Aún no hay cambios. El precio actual es ' + actual + '.');
                    }
                    if (!histTbody) return;
                    if (!rows.length) {
                        histTbody.innerHTML = '<tr><td colspan="9"><div class="cc-empty">Este producto todavía no tiene movimientos de precio.</div></td></tr>';
                        return;
                    }
                    histTbody.innerHTML = rows.map(function (it) {
                        var mon = String(it.moneda_nueva || 'MXN').toUpperCase();
                        var delta = it.variacion;
                        var cls = 'cc-hist-flat';
                        var deltaTxt = 'Alta';
                        if (delta !== null && delta !== undefined) {
                            if (delta > 0) {
                                cls = 'cc-hist-up';
                                deltaTxt = '+' + moneyTxt(delta, mon);
                            } else if (delta < 0) {
                                cls = 'cc-hist-down';
                                deltaTxt = moneyTxt(delta, mon);
                            } else {
                                deltaTxt = 'Sin cambio';
                            }
                            if (it.variacion_pct !== null && it.variacion_pct !== undefined && delta !== 0) {
                                deltaTxt += ' (' + (it.variacion_pct > 0 ? '+' : '') +
                                    Number(it.variacion_pct).toLocaleString('es-MX', { maximumFractionDigits: 2 }) + '%)';
                            }
                        }
                        var mesRow = it.mes_label || '—';
                        var codRow = it.producto_codigo || cod || '—';
                        var nomRow = String(it.producto_nombre || '').trim();
                        if (!nomRow || nomRow.toUpperCase() === String(codRow).toUpperCase()) {
                            nomRow = json.producto_nombre || nom || codRow;
                        }
                        return '<tr>' +
                            '<td>' + escapeHtml(it.fecha || '—') + '</td>' +
                            '<td>' + escapeHtml(mesRow) + '</td>' +
                            '<td><code>' + escapeHtml(codRow) + '</code></td>' +
                            '<td>' + escapeHtml(nomRow) + '</td>' +
                            '<td class="num">' + escapeHtml(moneyTxt(it.precio_anterior, it.moneda_anterior || mon)) + '</td>' +
                            '<td class="num fw-semibold">' + escapeHtml(moneyTxt(it.precio_nuevo, mon)) + '</td>' +
                            '<td class="num"><span class="' + cls + '">' + escapeHtml(deltaTxt) + '</span></td>' +
                            '<td>' + escapeHtml(it.origen_label || it.origen || '—') + '</td>' +
                            '<td>' + escapeHtml(it.usuario || 'Sistema') + '</td>' +
                            '</tr>';
                    }).join('');
                })
                .catch(function (err) {
                    if (histHint) histHint.textContent = 'No se pudo cargar el historial.';
                    if (histTbody) {
                        histTbody.innerHTML = '<tr><td colspan="9"><div class="cc-empty">' +
                            escapeHtml(err && err.message ? err.message : 'Error al cargar') +
                            '</div></td></tr>';
                    }
                });
        }

        var saveBtn = document.getElementById('pv-costo-guardar');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var payload = {
                    empresa: val('pv-costo-empresa'),
                    producto_codigo: val('pv-costo-codigo'),
                    producto_nombre: val('pv-costo-nombre'),
                    card_code: val('pv-costo-card'),
                    card_name: val('pv-costo-card-name'),
                    mes: Number(val('pv-costo-mes')) || 0,
                    costo_unitario: Number(val('pv-costo-valor')) || 0,
                    moneda: val('pv-costo-moneda') || 'MXN'
                };
                if (!payload.empresa || !payload.producto_codigo) {
                    toast('warning', 'Faltan datos', 'Empresa y producto son obligatorios.');
                    return;
                }
                saveBtn.disabled = true;
                fetch(url, {
                    method: 'PUT',
                    headers: apiJsonHeaders(),
                    body: JSON.stringify(payload)
                }).then(function (r) {
                    return r.json().then(function (json) {
                        if (!r.ok) throw new Error((json && json.message) || 'No se pudo guardar');
                        return json;
                    });
                }).then(function (json) {
                    toast('success', 'Precio guardado', json.message || 'Actualizado en maestro local.');
                    var modal = document.getElementById('modalPvCosto');
                    if (modal && window.bootstrap && bootstrap.Modal) {
                        var inst = bootstrap.Modal.getInstance(modal);
                        if (inst) inst.hide();
                    }
                    load();
                }).catch(function (err) {
                    toast('error', 'No se guardó', err && err.message ? err.message : 'Error');
                }).then(function () {
                    saveBtn.disabled = false;
                });
            });
        }

        if (empSel) empSel.addEventListener('change', function () { load(1); });
        if (clienteEl) clienteEl.addEventListener('input', scheduleSearch);
        if (itemEl) itemEl.addEventListener('input', scheduleSearch);
        if (perPageEl) perPageEl.addEventListener('change', function () { load(1); });
        if (prevBtn) prevBtn.addEventListener('click', function () { if (page > 1) load(page - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { if (page < lastPage) load(page + 1); });
        var reload = document.getElementById('pv-costos-reload');
        if (reload) reload.addEventListener('click', function () { load(page); });
        var importBtn = document.getElementById('pv-costos-import-api');
        if (importBtn) {
            importBtn.addEventListener('click', function () {
                if (!window.confirm('¿Cargar precios mensuales desde la API (todas las empresas)?\nSe importan CardCode, ItemCode, Mes y Precio desde /precios-mensuales y se actualizan también los que ya tienen valor.')) {
                    return;
                }
                importBtn.disabled = true;
                if (hint) hint.textContent = 'Importando precios desde API…';
                fetch(url + '/importar-api', {
                    method: 'POST',
                    headers: apiJsonHeaders(),
                    body: JSON.stringify({
                        empresa: '',
                        todas_empresas: true,
                        anio: CC.state.anioGasto || 2026,
                        solo_vacios: false
                    })
                }).then(function (r) {
                    return r.json().then(function (json) {
                        if (!r.ok) throw new Error((json && json.message) || 'No se pudo importar');
                        return json;
                    });
                }).then(function (json) {
                    toast('success', 'Importación lista', json.message || 'Precios cargados.');
                    load();
                }).catch(function (err) {
                    toast('error', 'No se importó', err && err.message ? err.message : 'Error');
                    if (hint) hint.textContent = 'Error al importar';
                }).then(function () {
                    importBtn.disabled = false;
                });
            });
        }

        var excelBtn = document.getElementById('pv-costos-excel');
        if (excelBtn) {
            excelBtn.addEventListener('click', function () {
                showModal('modalPvPreciosExcel');
            });
        }
        var dlBtn = document.getElementById('pv-precios-descargar');
        if (dlBtn) {
            dlBtn.addEventListener('click', function () {
                var plantillaUrl = CC.state.costosPlantillaUrl || (url + '/plantilla');
                var emp = empSel ? String(empSel.value || '') : '';
                var qs = emp ? ('?empresa=' + encodeURIComponent(emp)) : '';
                window.location.href = plantillaUrl + qs;
            });
        }
        var upBtn = document.getElementById('pv-precios-subir');
        var fileEl = document.getElementById('pv-precios-archivo');
        var excelHint = document.getElementById('pv-precios-excel-hint');
        if (upBtn && fileEl) {
            upBtn.addEventListener('click', function () {
                var file = fileEl.files && fileEl.files[0];
                if (!file) {
                    toast('warning', 'Falta archivo', 'Selecciona el Excel editado.');
                    return;
                }
                var importUrl = CC.state.costosImportExcelUrl || (url + '/importar-excel');
                var fd = new FormData();
                fd.append('archivo', file);
                upBtn.disabled = true;
                if (excelHint) excelHint.textContent = 'Subiendo y actualizando…';
                fetch(importUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken()
                    },
                    body: fd
                }).then(function (r) {
                    return r.json().then(function (json) {
                        if (!r.ok) throw new Error((json && json.message) || 'No se pudo importar');
                        return json;
                    });
                }).then(function (json) {
                    var extra = '';
                    if (json.errores && json.errores.length) {
                        extra = ' · ' + json.errores.length + ' fila(s) con aviso';
                    }
                    toast('success', 'Excel aplicado', (json.message || 'Precios actualizados.') + extra);
                    fileEl.value = '';
                    if (excelHint) {
                        excelHint.textContent = 'Se actualiza por Empresa + ItemCode y se propaga a ciclos abiertos.';
                    }
                    var modal = document.getElementById('modalPvPreciosExcel');
                    if (modal && window.bootstrap && bootstrap.Modal) {
                        var inst = bootstrap.Modal.getInstance(modal);
                        if (inst) inst.hide();
                    }
                    load();
                }).catch(function (err) {
                    toast('error', 'No se importó', err && err.message ? err.message : 'Error');
                    if (excelHint) excelHint.textContent = 'Error al importar. Revisa columnas Empresa, ItemCode y Precio.';
                }).then(function () {
                    upBtn.disabled = false;
                });
            });
        }

        load();
    }

    CC.boot = function (boot) {
        CC.state.page = boot.page;
        CC.state.anioGasto = boot.anioGasto || 2026;
        CC.state.anioPresupuesto = boot.anioPresupuesto || 2027;
        CC.state.usuarios = boot.usuarios || [];
        CC.state.centroInicial = boot.centroInicial || '';
        CC.state.empresaInicial = boot.empresaInicial || '';
        CC.state.cicloInicial = boot.cicloInicial || '';
        CC.state.vistaInicial = boot.vistaInicial || '';
        CC.state.detalleUrl = boot.detalleUrl || '/Ventas/Captura/detalle';
        CC.state.gastoUrl = boot.gastoUrl || '/ProyeccionesVentas/api/gasto-real';
        CC.state.listasPreciosUrl = boot.listasPreciosUrl || '/ProyeccionesVentas/api/listas-precios';
        CC.state.costosUrl = boot.costosUrl || '/ProyeccionesVentas/api/costos';
        CC.state.costosPlantillaUrl = boot.costosPlantillaUrl || '/ProyeccionesVentas/api/costos/plantilla';
        CC.state.costosImportExcelUrl = boot.costosImportExcelUrl || '/ProyeccionesVentas/api/costos/importar-excel';
        CC.state.costosHistorialUrl = boot.costosHistorialUrl || '/ProyeccionesVentas/api/costos/historial';
        CC.state.empresasLocales = boot.empresasLocales || [];
        CC.state.unidadesMedida = boot.unidadesMedida || {};
        CC.state.gastoCache = {};
        CC.state.preciosCache = {};
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
        applyPeriodFromCiclo(actual, CC.state.cicloCodigo || actual.codigo);
        CC.state.overlays = {};
        CC.state.budgets = {};
        CC.state.completados = {};
        CC.state.currency = loadJSON(SK.currency, 'MXN');
        CC.state.sapMensaje = boot.sapMensaje;
        mergeSap(boot, { lockCentros: CC.state.page === 'control' || CC.state.page === 'detalle' || CC.state.page === 'analisis' });
        var flag = document.getElementById('sap-flag');
        if (flag) {
            flag.textContent = CC.state.sapOk && (boot.centros || []).length ? 'Catálogo SAP' : (boot.sapMensaje ? 'Catálogo local' : 'Catálogo de trabajo');
            flag.className = 'cc-sap-flag' + (CC.state.sapOk && (boot.centros || []).length ? '' : ' off');
        }
        bindTcMesesUi();
        bindPrecioMesesUi();
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
        if (CC.state.page === 'costos') initCostos();
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
