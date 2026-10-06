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

    /** Importe que ya está en la moneda de vista (LineTotal o LineTotalUSD). No aplica TC. */
    function moneyVista(n) {
        var v = Number(n);
        if (!isFinite(v)) v = 0;
        var sym = isUsdView() ? 'US$' : '$';
        return sym + v.toLocaleString('es-MX', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    /** Importe SAP ya en la moneda indicada. No aplica tipo de cambio. */
    function moneyVistaFijo(n, currency) {
        var v = Number(n);
        if (!isFinite(v) || !v) return '—';
        var sym = String(currency || 'MXN').toUpperCase() === 'USD' ? 'US$' : '$';
        return sym + v.toLocaleString('es-MX', {
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

    /**
     * SAP B1 manda la moneda local como "$" o "##" (pesos), no como USD.
     * Solo un código explícito de dólar se trata como USD.
     */
    function canonMoneda(raw) {
        var m = String(raw || '').toUpperCase().replace(/[\s.]/g, '');
        if (m === 'USD' || m === 'US$' || m === 'U$S' || m === 'U$D' || m === 'US'
            || m === 'DLLS' || m === 'DLL' || m === 'DOLAR' || m === 'DOLARES'
            || m === 'DOLLAR' || m === 'DOLLARS') {
            return 'USD';
        }
        return 'MXN';
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

    function productosVentaRealDeCentro(c) {
        var key = gastoCacheKey(c);
        var por = (CC.state.gastoCache && CC.state.gastoCache[key]) || {};
        var out = [];
        var seen = {};
        Object.keys(por).forEach(function (k) {
            var row = por[k];
            if (!row || typeof row !== 'object' || !row.gasto || row.gasto.length !== 12) return;
            var codigo = String(row.codigo || k || '').trim();
            if (!codigo || seen[codigo]) return;
            seen[codigo] = true;
            var extra = extraFromRow(row);
            out.push({
                codigo: codigo,
                nombre: row.nombre || codigo,
                grupo: 'Venta real',
                gasto: row.gasto.slice(),
                importe: extra.importe,
                importeUsd: extra.importe_usd,
                precio: extra.precio,
                unidad: extra.unidad || '',
                unidadNombre: extra.unidad_nombre || '',
                costoVenta: Number(extra.costo) || 0,
                costoVentaMoneda: String(extra.costo_moneda || 'MXN').toUpperCase(),
                empresa: c.empresa
            });
        });
        return out;
    }

    function productosDesdeBudgetsDeCentro(c) {
        var emp = String((c && c.empresa) || '').toUpperCase().trim();
        var cc = String((c && c.codigo) || '').trim();
        if (!emp || !cc) return [];
        var prefix = (emp + '|' + cc + '|').toUpperCase();
        var seen = {};
        var out = [];
        function addKey(k) {
            var key = String(k || '');
            if (key.toUpperCase().indexOf(prefix) !== 0) return;
            var parts = key.split('|');
            if (parts.length < 3) return;
            var codigo = String(parts.slice(2).join('|') || '').trim();
            if (!codigo || seen[codigo] || seen[codigo.toUpperCase()]) return;
            seen[codigo] = true;
            seen[codigo.toUpperCase()] = true;
            out.push({
                codigo: codigo,
                nombre: codigo,
                grupo: 'Proyección',
                gasto: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                empresa: c.empresa
            });
        }
        Object.keys(CC.state.budgets || {}).forEach(addKey);
        Object.keys(CC.state.budgetKeysFromServer || {}).forEach(addKey);
        Object.keys(CC.state.completados || {}).forEach(addKey);
        return out;
    }

    function mergeCuentasAnalisis(c, base) {
        var seen = {};
        (base || []).forEach(function (cta) {
            seen[String(cta.codigo)] = true;
            seen[codigoCuentaKey(cta.codigo)] = true;
        });
        var extra = productosVentaRealDeCentro(c).filter(function (cta) {
            return !seen[String(cta.codigo)] && !seen[codigoCuentaKey(cta.codigo)];
        });
        extra.forEach(function (cta) {
            seen[String(cta.codigo)] = true;
            seen[codigoCuentaKey(cta.codigo)] = true;
        });
        var fromBudget = productosDesdeBudgetsDeCentro(c).filter(function (cta) {
            return !seen[String(cta.codigo)] && !seen[codigoCuentaKey(cta.codigo)];
        });
        return (base || []).concat(extra).concat(fromBudget);
    }

    function cuentasDeCentro(c) {
        var asig = asigDe(c);
        if (asig && asig.cuentas && asig.cuentas.length) {
            var packed = packedLookupDeCentro(c);
            var mapped = asig.cuentas.map(function (x) {
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
            return mapped;
        }
        var revisionSinProductos = asig && !asig.capturar && (asig.revisar || ((asig.permisos || []).indexOf('revisar') !== -1));
        if (revisionSinProductos && (CC.state.page === 'control' || CC.state.page === 'detalle')) {
            return mergeCuentasAnalisis(c, []);
        }
        // Análisis: solo productos asignados a captura. Sin lista, no se rellena con la venta real.
        if (CC.state.page === 'analisis') return [];
        if (CC.state.page === 'control' || CC.state.page === 'detalle') return [];
        var all = CC.state.cuentas.filter(function (cta) {
            return !cta.empresa || !c.empresa || String(cta.empresa).toUpperCase() === String(c.empresa).toUpperCase();
        });
        var o = overlayOf(c);
        if (o.cuentasAsignadas && o.cuentasAsignadas.length) {
            return all.filter(function (cta) { return o.cuentasAsignadas.indexOf(cta.codigo) !== -1; });
        }
        return all;
    }

    function codigoClienteAsig(a) {
        return String((a && (a.centro_codigo || a.cliente_codigo)) || '').trim();
    }

    function nombreClienteAsig(a) {
        return String((a && (a.centro_nombre || a.cliente_nombre)) || '').trim();
    }

    function asigsDe(c) {
        var ciclo = (document.getElementById('ctl-ciclo') && val('ctl-ciclo'))
            || (document.getElementById('an-ciclo') && val('an-ciclo'))
            || CC.state.cicloCodigo || '';
        var cicloNeedle = String(ciclo || '').toUpperCase();
        var code = String(c.codigo || '').trim();
        var fuente = CC.state.page === 'control' ? asignacionesCaptura(ciclo) : (CC.state.misAsignaciones || []);
        var pool = fuente.filter(function (a) {
            var sameEmp = String(a.empresa || '').toLowerCase() === String(c.empresa || a.empresa || '').toLowerCase();
            var sameCiclo = !cicloNeedle || String(a.ciclo || '').toUpperCase() === cicloNeedle;
            return sameEmp && sameCiclo;
        });
        var exact = pool.filter(function (a) { return codigoClienteAsig(a) === code; });
        if (exact.length) return exact;
        if (!code) return [];
        return pool.filter(function (a) { return isEmpresaCompleta(codigoClienteAsig(a)); });
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
        if (c && isEmpresaCompleta(base.centro_codigo) && !isEmpresaCompleta(c.codigo)) {
            base.centro_codigo = c.codigo;
            base.centro_nombre = c.nombre || c.codigo;
            base.revisar = true;
        }
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

    /** ItemCode SAP (tiene letras): no usar alias solo-dígitos (REPE-3PE MT → "3"). */
    function esItemCodeSap(codigo) {
        return /[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/.test(String(codigo || ''));
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

    function padSerie12(src) {
        var out = Array.isArray(src) ? src.slice(0, 12) : [];
        while (out.length < 12) out.push(0);
        return out;
    }

    /** Misma clave de ItemCode aunque cambien mayúsculas, espacios o el guion. */
    function claveProductoVenta(codigo) {
        return String(codigo || '').replace(/\u00a0/g, '').replace(/[–—]/g, '-').replace(/\s+/g, '').toUpperCase();
    }

    /**
     * Une la fila de /ventas con la de los 3 meses agrupados (oct–dic).
     * Un mes vacío se rellena; un mes repetido con el mismo importe no se suma otra vez.
     */
    function collapsePorCuenta(por) {
        var out = {};
        Object.keys(por || {}).forEach(function (k) {
            if (k === '_meta') return;
            var row = por[k];
            if (!row || typeof row !== 'object' || Array.isArray(row)) return;
            var raw = String((row.codigo != null && String(row.codigo) !== '') ? row.codigo : k).trim();
            var clave = claveProductoVenta(raw);
            if (!clave) return;
            if (!out[clave]) {
                var copy = Object.assign({}, row);
                copy.codigo = raw;
                copy.gasto = padSerie12(row.gasto);
                copy.importe = padSerie12(row.importe);
                copy.importe_usd = padSerie12(row.importe_usd);
                out[clave] = copy;
                return;
            }
            var base = out[clave];
            if (!base.nombre && row.nombre) base.nombre = row.nombre;
            ['gasto', 'importe', 'importe_usd'].forEach(function (field) {
                var a = padSerie12(base[field]);
                var b = padSerie12(row[field]);
                for (var i = 0; i < 12; i++) {
                    var va = Number(a[i]) || 0;
                    var vb = Number(b[i]) || 0;
                    if (!va) a[i] = vb;
                    else if (vb && Math.abs(va - vb) >= 0.0001 && Math.abs(vb) > Math.abs(va)) a[i] = vb;
                }
                base[field] = a;
            });
        });
        return out;
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

    /** Misma regla que el control de centros de costos: más de 3 decimales se recortan, no se suben. */
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

    function moneyDosDecimales(n) {
        var v = round2(n);
        if (!v) return '—';
        return (isUsdView() ? 'US$' : '$') + v.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function usdSeriesOf(cta) {
        if (!cta) return zeros12();
        if (cta.importeUsd && cta.importeUsd.length === 12) return cta.importeUsd;
        if (cta.importe_usd && cta.importe_usd.length === 12) return cta.importe_usd;
        return zeros12();
    }

    /** Venta real del mes en la moneda de vista.
     *  USD: LineTotalUSD de SAP, o LineTotal MXN ÷ TC si SAP no trae dólares.
     *  Budget, oct–dic: si gasto-real no trae el mes, usa ventas-budget (importe o uds × precio). */
    function importeMesVentaVista(cta, i) {
        if (!cta) return 0;
        var mxnSap = Number((cta.importe && cta.importe[i]) || 0);
        var usdSap = Number(usdSeriesOf(cta)[i]) || 0;
        if (isUsdView()) {
            if (usdSap) return usdSap;
            if (mxnSap) return toDisplayAmount(mxnSap, i);
        } else if (mxnSap) {
            return mxnSap;
        }
        var idx = Number(i);
        if (!esBudgetTipo() || idx < 9 || idx > 11) return 0;
        var hit = hitVentasBudget(cta);
        if (hit) {
            if (isUsdView()) {
                var usd = Number(hit.importe_usd && hit.importe_usd[idx]);
                if (usd) return usd;
                var mxnUsd = Number(hit.importe && hit.importe[idx]);
                if (mxnUsd) return toDisplayAmount(mxnUsd, idx);
            } else {
                var mxn = Number(hit.importe && hit.importe[idx]);
                if (mxn) return mxn;
            }
        }
        var qty = Number(pastQtyMes(cta, idx)) || 0;
        var precio = Number(cta.precioLista) || 0;
        if (!qty || !precio) return 0;
        var mon = canonMoneda(cta.precioMoneda || 'MXN');
        var mxnAmt = qty * (mon === 'USD' ? precio * fxRate(idx) : precio);
        return toDisplayAmount(mxnAmt, idx);
    }

    function hitVentasBudget(cta) {
        if (!cta) return null;
        var map = control._ventasBudgetRefMap || {};
        var hit = map[cta.codigo]
            || map[String(cta.codigo || '').toUpperCase()]
            || map[codigoCuentaKey(cta.codigo)]
            || null;
        if (hit) return hit;
        var emp = String(cta._empresa || (control.centro && control.centro.empresa) || '').toUpperCase();
        var cc = String(cta._cliente || (control.centro && control.centro.codigo) || '').trim();
        if (!emp || !cc) return null;
        var bag = (CC._anVentasBudget || {})[emp + '|' + cc];
        if (!bag || typeof bag !== 'object') return null;
        return bag[cta.codigo]
            || bag[String(cta.codigo || '').toUpperCase()]
            || bag[codigoCuentaKey(cta.codigo)]
            || null;
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
        var cod = String(codigo || '').trim();
        var hit = map[cod] || map[cod.toUpperCase()];
        // Alias numérico solo para cuentas contables, no ItemCode (evita REPE-3PE MT → "3").
        if ((!hit || hit.length !== 12) && cod && !esItemCodeSap(cod)) {
            hit = map[codigoCuentaKey(cod)];
        }
        var nk = nombreCuentaKey(nombre);
        if ((!hit || hit.length !== 12) && nk) {
            hit = map['n:' + nk] || map['nc:' + nk.replace(/\s+/g, '')];
        }
        // Match difuso por nombre: solo cuentas; en artículos exige nombre exacto (arriba).
        if ((!hit || hit.length !== 12) && nk && !esItemCodeSap(cod)) {
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

    function extraMensualDe(codigo, nombre, extrasOpt, nombresOpt) {
        var extras = extrasOpt || control._gastoExtras || {};
        var cod = String(codigo || '').trim();
        var hit = extras[cod] || extras[cod.toUpperCase()];
        if (!hit && cod && !esItemCodeSap(cod)) {
            hit = extras[codigoCuentaKey(cod)];
        }
        var nk = nombreCuentaKey(nombre);
        if (!hit && nk) {
            hit = extras['n:' + nk] || extras['nc:' + nk.replace(/\s+/g, '')];
        }
        if (!hit && nk && !esItemCodeSap(cod)) {
            var list = nombresOpt || control._gastoNombres || [];
            for (var i = 0; i < list.length; i++) {
                if (nombresGastoCompatibles(nk, list[i].key) && list[i].extra) {
                    hit = list[i].extra;
                    break;
                }
            }
        }
        return hit || extraFromRow(null);
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
            var codRef = String(row.codigo || key || '').trim();
            put(key, gasto, extra);
            if (row.codigo) put(String(row.codigo), gasto, extra);
            if (codRef) put(codRef.toUpperCase(), gasto, extra);
            // Alias solo-dígitos únicamente para cuentas (no ItemCode con letras).
            if (codRef && !esItemCodeSap(codRef)) {
                put(codigoCuentaKey(key), gasto, extra);
                if (row.codigo) put(codigoCuentaKey(row.codigo), gasto, extra);
            }
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
        return String(c.empresa || '').toUpperCase() + '|' +
            String(c.codigo || '').trim().toUpperCase() + '|' + year;
    }

    /** ¿Se está consultando venta SAP para el cliente abierto? (igual que precios lista). */
    function gastoVentaCargando() {
        if (!control.centro || !control._gastoLoading) return false;
        return control._gastoLoading === gastoCacheKey(control.centro);
    }

    /** ¿Hay entrada usable en caché de sesión? No confiar en {} sin meta (envenena el snapshot). */
    function gastoCacheEntry(key) {
        if (!key) return null;
        var por = CC.state.gastoCache && CC.state.gastoCache[key];
        var meta = CC.state.gastoMeta && CC.state.gastoMeta[key];
        if (por == null && !meta) return null;
        if (por && typeof por === 'object') {
            var collapsed = collapsePorCuenta(por);
            var before = Object.keys(por).length;
            var after = Object.keys(collapsed).length;
            if (after !== before) {
                CC.state.gastoCache[key] = collapsed;
                if (CC.state.gastoLookup) delete CC.state.gastoLookup[key];
                por = collapsed;
            }
        }
        var n = por && typeof por === 'object' ? Object.keys(por).length : 0;
        if (meta && meta.fuente) {
            return { por: por || {}, meta: meta };
        }
        // Datos sin meta (p.ej. Análisis / Ventas pasadas): usarlos pero marcar caché.
        if (n > 0) {
            return {
                por: por,
                meta: {
                    fuente: 'cache',
                    synced_at: null,
                    year: (key.split('|')[2] || CC.state.anioGasto || ''),
                    mensaje: null
                }
            };
        }
        return null;
    }

    function rememberGastoCache(key, por, meta) {
        if (!key) return;
        CC.state.gastoCache = CC.state.gastoCache || {};
        CC.state.gastoMeta = CC.state.gastoMeta || {};
        var n = por && typeof por === 'object' ? Object.keys(por).length : 0;
        var fuente = meta && meta.fuente ? String(meta.fuente) : '';
        // No guardar vacíos/errores: permiten reconsultar el snapshot en BD al cambiar de cliente.
        if (n === 0 && (!fuente || fuente === 'error')) {
            delete CC.state.gastoCache[key];
            delete CC.state.gastoMeta[key];
            if (CC.state.gastoLookup) delete CC.state.gastoLookup[key];
            return;
        }
        CC.state.gastoCache[key] = collapsePorCuenta(por || {});
        if (meta) CC.state.gastoMeta[key] = meta;
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
        if (control._gastoReq === key && control._gastoLoadedFor === key && control._gastoMap) {
            return {
                map: control._gastoMap,
                extras: control._gastoExtras || {},
                nombres: control._gastoNombres || []
            };
        }
        var packed = CC.state.gastoLookup && CC.state.gastoLookup[key];
        if (packed) {
            if (packed.map && packed.extras) return packed;
            var porCached = CC.state.gastoCache && CC.state.gastoCache[key];
            if (porCached && Object.keys(porCached).length) {
                var rebuilt = mapFromPorCuenta(porCached);
                CC.state.gastoLookup[key] = rebuilt;
                return rebuilt;
            }
            if (packed.map) return packed;
            return { map: packed, extras: {}, nombres: [] };
        }
        var hit = gastoCacheEntry(key);
        if (!hit) return empty;
        var built = mapFromPorCuenta(hit.por);
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

    function applyGastoMap(porCuenta, meta) {
        var built = mapFromPorCuenta(porCuenta);
        control._gastoMap = built.map;
        control._gastoExtras = built.extras;
        control._gastoNombres = built.nombres;
        control._gastoLoadedFor = control._gastoReq || '';
        control._gastoMeta = meta || {};
        if (control._gastoReq) {
            CC.state.gastoLookup = CC.state.gastoLookup || {};
            CC.state.gastoLookup[control._gastoReq] = built;
        }
        paintVentaSnapshotHint(meta);
        if (!control.centro) return;
        stripSiopSeedLocalOnce(control.centro);
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

    function paintVentaSnapshotHint(meta) {
        meta = meta || control._gastoMeta || {};
        var year = meta.year || CC.state.anioGasto || '';
        var when = meta.synced_at || '';
        var fuente = String(meta.fuente || '');
        var label = 'Sin snapshot';
        var shortLabel = 'Venta local: —';
        var navLabel = 'Snapshot: —';
        var state = 'is-empty';
        var title = 'Aún no hay venta local para este cliente. Se consultará SAP al cargar.';

        if (fuente === 'snapshot' || fuente === 'snapshot_fallback') {
            label = when ? ('Snapshot ' + when) : ('Snapshot venta ' + year);
            shortLabel = when ? ('Local · ' + when) : ('Local · venta ' + year);
            navLabel = when ? ('Snapshot: ' + when) : 'Snapshot local';
            state = fuente === 'snapshot_fallback' ? 'is-warn' : 'is-local';
            title = 'Venta ' + year + ' desde BD local' + (when ? (' (sync ' + when + ')') : '') +
                (fuente === 'snapshot_fallback' ? '. SAP no respondió; se usó el último snapshot.' : '.');
        } else if (fuente === 'api') {
            label = when ? ('SAP ahora · ' + when) : ('SAP · venta ' + year);
            shortLabel = when ? ('SAP · ' + when) : 'SAP · recién sync';
            navLabel = when ? ('SAP: ' + when) : 'SAP recién sync';
            state = 'is-api';
            title = 'Venta ' + year + ' consultada en SAP y guardada en snapshot' +
                (when ? (' (' + when + ')') : '') + '.';
        } else if (fuente === 'cache') {
            label = 'Caché de sesión';
            shortLabel = 'Caché sesión';
            navLabel = 'Snapshot: caché';
            state = 'is-cache';
            title = 'Venta ' + year + ' en caché de esta sesión.';
        } else if (fuente === 'error') {
            label = 'Error al cargar';
            shortLabel = 'Error venta';
            navLabel = 'Snapshot: error';
            state = 'is-error';
            title = (meta.mensaje || 'No se pudo cargar la venta real.');
        }

        var badge = document.getElementById('ctl-venta-snap-badge');
        var badgeTxt = document.getElementById('ctl-venta-snap-text');
        if (badge) {
            badge.className = 'cc-snap-badge ' + state;
            badge.title = title;
        }
        if (badgeTxt) badgeTxt.textContent = label;

        var pill = document.getElementById('ctl-venta-snap-pill');
        var pillTxt = document.getElementById('ctl-venta-snap-pill-text');
        if (pill) {
            pill.className = 'cc-snap-badge ' + state;
            pill.title = title + ' Clic en «Actualizar venta SAP» para refrescar.';
        }
        if (pillTxt) pillTxt.textContent = shortLabel;

        var nav = document.getElementById('ctl-venta-snap-nav');
        if (nav) {
            nav.textContent = navLabel;
            nav.title = title;
            nav.className = 'cc-snap-nav ' + state;
        }
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

    function loadGastoRealCentro(c, opts) {
        opts = opts || {};
        if (!c || !CC.state.gastoUrl) return;
        var year = CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || 2026;
        var key = gastoCacheKey(c);
        // Por defecto fuerza SAP (Captura). Detalle pasa { force: false } → snapshot.
        var force = opts.force !== false;
        var auto = !!opts.auto;
        var reqId = (control._gastoReqId = (control._gastoReqId || 0) + 1);
        control._gastoReq = key;
        control._gastoLoading = key;
        if (!force) {
            var hit = gastoCacheEntry(key);
            if (hit) {
                control._gastoLoading = '';
                applyGastoMap(hit.por, hit.meta);
                return;
            }
        }
        if (CC.state.gastoCache) delete CC.state.gastoCache[key];
        if (CC.state.gastoMeta) delete CC.state.gastoMeta[key];
        if (CC.state.gastoLookup) delete CC.state.gastoLookup[key];
        var badgeTxt = document.getElementById('ctl-venta-snap-text');
        var pillTxt = document.getElementById('ctl-venta-snap-pill-text');
        var nav = document.getElementById('ctl-venta-snap-nav');
        var waitMsg = force
            ? ('Consultando unidades de venta ' + year + ' en SAP…')
            : ('Cargando venta ' + year + ' desde snapshot…');
        if (badgeTxt) badgeTxt.textContent = force ? 'Consultando SAP…' : 'Snapshot…';
        if (pillTxt) pillTxt.textContent = force ? 'Consultando SAP…' : 'Snapshot…';
        if (nav) nav.textContent = force ? 'Venta: SAP…' : 'Venta: local…';
        ['ctl-venta-snap-badge', 'ctl-venta-snap-pill'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.className = 'cc-snap-badge is-cache';
        });
        // Solo overlay en refresh manual. Auto/snapshot no bloquean la captura.
        var showedWait = !auto && force;
        if (showedWait) showApiWait(waitMsg);
        if (CC._capturaReady) {
            try { renderControlTable(); } catch (e1) { /* ignore */ }
        }
        var qs = '?empresa=' + encodeURIComponent(c.empresa || '') +
            '&cc=' + encodeURIComponent(String(c.codigo || '').trim()) +
            '&year=' + encodeURIComponent(year) +
            '&ref=' + encodeURIComponent(year) +
            '&force=' + (force ? '1' : '0');
        var reqKey = key;
        var ctrl = (typeof AbortController !== 'undefined') ? new AbortController() : null;
        var abortTimer = setTimeout(function () {
            try { if (ctrl) ctrl.abort(); } catch (eAbort) { /* ignore */ }
        }, force ? 45000 : 20000);
        fetch(CC.state.gastoUrl + qs, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: ctrl ? ctrl.signal : undefined
        }).then(function (res) { return res.json(); }).then(function (json) {
            if (reqId !== control._gastoReqId) return;
            if (!control.centro || gastoCacheKey(control.centro) !== reqKey) return;
            control._gastoLoading = '';
            var por = (json && json.por_cuenta) || {};
            var meta = {
                fuente: (json && json.fuente) || 'api',
                synced_at: (json && json.synced_at) || null,
                year: (json && json.year) || year,
                mensaje: (json && json.mensaje) || null
            };
            rememberGastoCache(reqKey, por, meta);
            applyGastoMap(por, meta);
            if (!auto) {
                toast('success', 'Venta actualizada',
                    'SAP ' + year + (meta.synced_at ? (' · ' + meta.synced_at) : '') +
                    (meta.fuente ? (' · ' + meta.fuente) : ''));
            }
        }).catch(function () {
            if (reqId !== control._gastoReqId) return;
            if (!control.centro || gastoCacheKey(control.centro) !== reqKey) return;
            control._gastoLoading = '';
            // Si ya hay snapshot/caché, no borrar la tabla al fallar SAP.
            var prev = gastoCacheEntry(reqKey);
            if (prev && prev.por && Object.keys(prev.por).length) {
                applyGastoMap(prev.por, prev.meta || { fuente: 'snapshot', year: year });
            } else {
                applyGastoMap({}, { fuente: 'error', year: year });
            }
            if (!auto) {
                toast('error', 'No se actualizó', 'No se pudo consultar las unidades de venta en SAP.');
            }
        }).then(function () {
            clearTimeout(abortTimer);
            if (reqId === control._gastoReqId && control._gastoLoading === reqKey) {
                control._gastoLoading = '';
            }
            // Siempre cerrar si esta petición abrió el overlay (evita modal colgado por race).
            if (showedWait) hideApiWait();
        });
    }
    function preciosCacheKey(c) {
        return String(c.empresa || '').toUpperCase() + '|' + String(c.codigo || '');
    }

    function preciosListaCargando() {
        if (!control.centro || !control._preciosLoading) return false;
        return control._preciosLoading === preciosCacheKey(control.centro);
    }

    function lookupPrecioLista(codigo) {
        var map = control._preciosMap || {};
        if (!codigo) return null;
        return precioListaEnMapa(map, codigo);
    }

    function precioListaEnMapa(map, codigo) {
        if (!map || !codigo) return null;
        var keys = [String(codigo), String(codigo).toUpperCase()];
        if (!esItemCodeSap(codigo)) keys.push(codigoCuentaKey(codigo));
        for (var i = 0; i < keys.length; i++) {
            var hit = map[keys[i]];
            if (hit && Number(hit.precio) > 0) return hit;
        }
        return null;
    }

    function mapaPreciosArticulo(porArticulo) {
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
                moneda: canonMoneda(row.moneda || 'MXN'),
                unidad: String(row.unidad || '').trim(),
                unidad_nombre: String(row.unidad_nombre || '').trim(),
                lista: row.lista || '',
                no_lista: row.no_lista || ''
            };
            map[codigo] = entry;
            map[codigo.toUpperCase()] = entry;
            if (!esItemCodeSap(codigo)) map[codigoCuentaKey(codigo)] = entry;
        });
        return map;
    }

    function applyPreciosMap(porArticulo) {
        var map = mapaPreciosArticulo(porArticulo);
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

    /** Precio lista desde costosMaster (tbl_pv_productos_costo.precio_lista), no SAP. */
    function preciosMapDesdeMaestroLocal(c) {
        var out = {};
        if (!c) return out;
        var emp = String(c.empresa || '').toUpperCase();
        var card = String(c.codigo || '').trim();
        var map = CC.state.costosMaster || {};
        var prefix = emp && card ? (emp + '|' + card + '|') : '';
        Object.keys(map).forEach(function (key) {
            var entry = map[key];
            if (!entry || typeof entry !== 'object') return;
            var cod = '';
            var entryCard = String(entry.card_code || '').trim();
            if (prefix && key.indexOf(prefix) === 0) {
                cod = key.slice(prefix.length);
            } else if (card && entryCard && entryCard.toUpperCase() === card.toUpperCase()) {
                var parts = String(key).split('|');
                cod = parts[parts.length - 1] || '';
            } else {
                return;
            }
            cod = String(cod || '').trim();
            if (!cod) return;
            var pl = Number(entry.precio_lista != null ? entry.precio_lista : entry.precioLista) || 0;
            if (!(pl > 0)) return;
            out[cod] = {
                codigo: cod,
                precio: pl,
                moneda: canonMoneda(entry.moneda || 'MXN'),
                lista: 'Maestro local · Precio lista',
                fuente: 'maestro_local_precio_lista'
            };
        });
        return out;
    }

    function loadListasPreciosCentro(c) {
        if (!c) return;
        // Captura / Detalle: Precio lista = maestro local (no SAP).
        if (CC.state.page === 'control' || CC.state.page === 'detalle') {
            var localKey = preciosCacheKey(c);
            control._preciosReq = localKey;
            control._preciosLoading = '';
            applyPreciosMap(preciosMapDesdeMaestroLocal(c));
            return;
        }
        if (!CC.state.listasPreciosUrl) return;
        var key = preciosCacheKey(c);
        control._preciosReq = key;
        control._preciosLoading = key;
        // No reutilizar caché de sesión incompleta: siempre pedir los productos del cliente.
        if (CC.state.preciosCache) delete CC.state.preciosCache[key];
        var hasMaster = CC.state.costosMaster && Object.keys(CC.state.costosMaster).length > 0;
        var showedWait = !hasMaster;
        control._preciosMap = {};
        if (showedWait) {
            showApiWait('Consultando listas de precios…');
        }
        var items = [];
        try {
            (cuentasDeCentro(c) || []).forEach(function (cta) {
                var cod = String((cta && cta.codigo) || '').trim();
                if (cod && items.indexOf(cod) === -1) items.push(cod);
            });
        } catch (e) { /* ignore */ }
        var qs = '?empresa=' + encodeURIComponent(c.empresa || '') +
            '&cliente=' + encodeURIComponent(c.codigo || '') +
            '&year=' + encodeURIComponent(CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || new Date().getFullYear());
        if (items.length) {
            qs += '&items=' + encodeURIComponent(items.join(','));
        }
        fetch(CC.state.listasPreciosUrl + qs, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (json) {
            if (control._preciosReq !== key) return;
            control._preciosLoading = '';
            applyPreciosMap((json && json.por_articulo) || {});
        }).catch(function () {
            if (control._preciosReq !== key) return;
            control._preciosLoading = '';
            applyPreciosMap({});
        }).then(function () {
            if (showedWait) hideApiWait();
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

    function fetchVentasPasadas(empresa, cc, year, forceSap) {
        var key = String(empresa || '').toUpperCase() + '|' + String(cc || '') + '|' + year;
        // El modal debe refrescar SAP (force): el snapshot pudo mezclar D* + DD*.
        if (!forceSap) {
            var hit = gastoCacheEntry(key);
            if (hit) {
                return Promise.resolve({
                    ok: true,
                    year: year,
                    por_cuenta: hit.por,
                    fuente: (hit.meta && hit.meta.fuente) || 'cache',
                    synced_at: hit.meta && hit.meta.synced_at,
                    cached: true
                });
            }
        } else {
            if (CC.state.gastoCache) delete CC.state.gastoCache[key];
            if (CC.state.gastoMeta) delete CC.state.gastoMeta[key];
            if (CC.state.gastoLookup) delete CC.state.gastoLookup[key];
        }
        return fetch(CC.state.gastoUrl + '?empresa=' + encodeURIComponent(empresa || '') +
            '&cc=' + encodeURIComponent(cc || '') +
            '&year=' + encodeURIComponent(year) +
            '&ref=' + encodeURIComponent(year) +
            '&force=' + (forceSap ? '1' : '0'), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (json) {
            var por = (json && json.por_cuenta) || {};
            rememberGastoCache(key, por, {
                fuente: (json && json.fuente) || (json && json.ok ? 'api' : 'error'),
                synced_at: (json && json.synced_at) || null,
                year: (json && json.year) || year,
                budget_completo: !!(json && json.budget_completo),
                mensaje: (json && json.mensaje) || null
            });
            return json || { ok: false, por_cuenta: {}, mensaje: 'Sin respuesta' };
        });
    }

    function rowsFromPorCuenta(por) {
        por = collapsePorCuenta(por);
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
        var money = metric === 'mxn' || metric === 'usd';
        var txt = n.toLocaleString('es-MX', {
            minimumFractionDigits: money ? 2 : 0,
            maximumFractionDigits: 2
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
        // force=true (abrir modal / Cargar): SAP + CardCode exacto, reescribe snapshot.
        fetchVentasPasadas(emp, cc, year, !!force).then(function (json) {
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

    /** Precio unitario efectivo del mes: override proyección → maestro Costos del mes → global. */
    function precioUnitarioMes(cta, monthIdx) {
        var arr = (cta && cta.precioMeses) || [];
        var v = Number(arr[monthIdx]);
        if (isFinite(v) && v > 0) return v;
        var maestroMes = Number(cta && cta.precioMaestroMeses && cta.precioMaestroMeses[monthIdx]);
        if (isFinite(maestroMes) && maestroMes > 0) return maestroMes;
        var base = Number(cta && cta.precioLista) || 0;
        if (base > 0) return base;
        return 0;
    }

    function precioMesTieneOverride(cta, monthIdx) {
        var v = Number((cta && cta.precioMeses && cta.precioMeses[monthIdx]));
        return isFinite(v) && v > 0;
    }

    /** True si el mes difiere del global (override o precio del maestro distinto). */
    function precioMesEsDistinto(cta, monthIdx) {
        var base = Number(cta && cta.precioLista) || 0;
        var p = precioUnitarioMes(cta, monthIdx);
        if (!(p > 0)) return false;
        if (precioMesTieneOverride(cta, monthIdx)) {
            return !(base > 0) || Math.abs(p - base) > 0.0001;
        }
        var maestroMes = Number(cta && cta.precioMaestroMeses && cta.precioMaestroMeses[monthIdx]);
        if (isFinite(maestroMes) && maestroMes > 0 && base > 0) {
            return Math.abs(maestroMes - base) > 0.0001;
        }
        return false;
    }

    function precioMesesVarian(cta) {
        for (var i = 0; i < 12; i++) {
            if (precioMesEsDistinto(cta, i)) return true;
        }
        return false;
    }

    /** Moda de precios mensuales > 0 (para sincronizar “global” del maestro). */
    function precioModaDeMeses(meses) {
        var freq = {};
        var arr = meses || [];
        for (var i = 0; i < arr.length; i++) {
            var p = Number(arr[i]);
            if (!(p > 0)) continue;
            var k = (Math.round(p * 10000) / 10000).toFixed(4);
            freq[k] = (freq[k] || 0) + 1;
        }
        var best = null;
        var bestN = 0;
        Object.keys(freq).forEach(function (k) {
            if (freq[k] > bestN) {
                bestN = freq[k];
                best = Number(k);
            }
        });
        return best && best > 0 ? best : 0;
    }

    function mesesVacios12() {
        return [null, null, null, null, null, null, null, null, null, null, null, null];
    }

    function qtyMesIgual(a, b) {
        var av = mesLleno(a) ? (Number(a) || 0) : 0;
        var bv = mesLleno(b) ? (Number(b) || 0) : 0;
        return av === bv;
    }

    function monthsIgualReferencia(months, ref) {
        if (!Array.isArray(months)) return false;
        ref = Array.isArray(ref) ? ref : [];
        for (var i = 0; i < 12; i++) {
            if (!qtyMesIgual(months[i], ref[i])) return false;
        }
        return true;
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

    function persistPrecioMesesProducto(empresa, cc, cuenta, precioMeses, opts) {
        opts = opts || {};
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
        // Sincroniza también Ventas/Costos (crear/actualizar por mes).
        return syncPrecioMesesACostos(empresa, cc, cuenta, precioMeses, opts);
    }

    function syncPrecioMesesACostos(empresa, cc, cuenta, precioMeses, opts) {
        opts = opts || {};
        var url = (CC.state.costosUrl || '/ProyeccionesVentas/api/costos') + '/meses';
        var cta = ((control && control._allCtas) || []).filter(function (x) {
            return String(x.codigo) === String(cuenta);
        })[0];
        var moneda = canonMoneda(opts.moneda || (cta && cta.precioMoneda) || 'MXN');
        var nombre = opts.nombre || (cta && cta.nombre) || cuenta;
        var cardName = opts.card_name || (control.centro && control.centro.nombre) || '';
        return fetch(url, {
            method: 'PUT',
            headers: apiJsonHeaders(),
            body: JSON.stringify({
                empresa: empresa,
                card_code: cc || '',
                card_name: cardName,
                producto_codigo: cuenta,
                producto_nombre: nombre,
                moneda: moneda,
                anio: CC.state.anioPresupuesto || 2027,
                meses: precioMeses
            })
        }).then(function (r) {
            return r.json().then(function (json) {
                if (!r.ok) throw new Error((json && json.message) || 'No se pudo sincronizar Ventas/Costos');
                // Actualiza mapa local de precios mensuales del maestro.
                CC.state.costosMeses = CC.state.costosMeses || {};
                var keyCard = String(empresa || '').toUpperCase() + '|' + String(cc || '').trim() + '|' + String(cuenta || '').trim();
                var entry = {
                    meses: (json.precio_meses || precioMeses || []).slice(0, 12),
                    monedas: []
                };
                for (var i = 0; i < 12; i++) {
                    entry.monedas[i] = entry.meses[i] != null ? moneda : null;
                }
                CC.state.costosMeses[keyCard] = entry;
                // Global del maestro = moda (no el último mes). Respeta 0 explícito.
                var moda = precioModaDeMeses(entry.meses);
                if (json.precio_global != null && json.precio_global !== '') {
                    moda = Number(json.precio_global) || 0;
                }
                CC.state.costosMaster = CC.state.costosMaster || {};
                CC.state.costosMaster[keyCard] = {
                    costo: moda,
                    moneda: moneda,
                    mes: null,
                    card_code: cc || '',
                    origen: 'maestro_local',
                    explicito: true
                };
                return json;
            });
        });
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
            CC._siopStrippedFor = '';
            CC._siopSeedStamp = '';
            CC._budgetsBaseLoaded = false;
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
                CC._budgetsBaseLoaded = true;
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
                if (CC.state.page === 'analisis') {
                    CC._budgetsBaseLoaded = true;
                    return { applied: true, ciclo: ciclo };
                }
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
        stripSiopSeedLocalOnce(control.centro);
        // Reaplicar precio lista local cuando ya llegó costosMaster del ciclo.
        if (CC.state.page === 'control' || CC.state.page === 'detalle') {
            var pKey = preciosCacheKey(control.centro);
            control._preciosReq = pKey;
            control._preciosLoading = '';
            applyPreciosMap(preciosMapDesdeMaestroLocal(control.centro));
        }
        control._allCtas = cuentasEnriquecidas(control.centro);
        seedPresupuestoDesdeVentaReal(control.centro);
        if (esBudgetTipo()) scrubProyeccionOctDicSiCopiaBudgetRef();
        control._allCtas = cuentasEnriquecidas(control.centro);
        updateControlProgress();
        renderNavCuentas();
        renderCtaChips();
        renderCapturaForm();
        renderControlTable();
        renderControlCharts();
        renderVisorTable();
    }

    function cicloSortKey(c) {
        c = c || {};
        var id = Number(c.id) || 0;
        if (id) return id;
        return Date.parse(String(c.inicio || c.fin || c.capturaHasta || '')) || 0;
    }

    /** Ciclo previo del mismo año de presupuesto (el más reciente entre los más antiguos). */
    function cicloProyectoAnterior() {
        var current = cicloActualCodigo();
        var cur = findCiclo(current) || CC.state.period || {};
        var anio = Number(cur.anio || CC.state.anioPresupuesto) || 0;
        var curKey = cicloSortKey(cur);
        var sameYear = (CC.state.ciclos || []).filter(function (c) {
            return String(c.codigo || '').toUpperCase() !== String(current || '').toUpperCase()
                && (!anio || Number(c.anio) === anio);
        });
        var earlier = sameYear.filter(function (c) {
            return curKey > 0 && cicloSortKey(c) < curKey;
        }).sort(function (a, b) {
            return cicloSortKey(b) - cicloSortKey(a);
        });
        if (earlier[0] && earlier[0].codigo) return earlier[0].codigo;
        // Si el SIOP se dio de alta primero, usar el único otro ciclo del mismo año.
        if (sameYear.length === 1 && sameYear[0].codigo) return sameYear[0].codigo;
        return '';
    }

    /** Ciclo base para heredar cantidades (Forecast/SIOP). */
    function cicloBaseProyeccion() {
        var current = cicloActualCodigo();
        var cur = findCiclo(current) || CC.state.period || {};
        var tipo = cur.tipoBudget || budgetTipo();
        var anio = Number(cur.anio || CC.state.anioPresupuesto) || 0;
        var curId = Number(cur.id) || 0;
        var ciclos = CC.state.ciclos || [];
        var sameYear = ciclos.filter(function (c) {
            return String(c.codigo || '').toUpperCase() !== String(current || '').toUpperCase()
                && (!anio || Number(c.anio) === anio);
        });

        // SIOP: preferir Budget del mismo año (proyección base 2027), luego Forecast, luego el ciclo anterior.
        if (esSiopTipo(tipo)) {
            var budget = sameYear.filter(function (c) {
                return String(c.tipoBudget || '') === 'BUDGET';
            }).sort(function (a, b) {
                return (Number(b.id) || 0) - (Number(a.id) || 0);
            });
            if (budget[0] && budget[0].codigo) return budget[0].codigo;

            var forecast = sameYear.filter(function (c) {
                return esForecastTipo(c.tipoBudget);
            }).sort(function (a, b) {
                var idA = Number(a.id) || 0;
                var idB = Number(b.id) || 0;
                if (curId) {
                    var beforeA = idA > 0 && idA < curId ? 1 : 0;
                    var beforeB = idB > 0 && idB < curId ? 1 : 0;
                    if (beforeA !== beforeB) return beforeB - beforeA;
                }
                return idB - idA;
            });
            if (forecast[0] && forecast[0].codigo) return forecast[0].codigo;

            var prev = cicloProyectoAnterior();
            if (prev) return prev;

            var prevYear = ciclos.filter(function (c) {
                return String(c.codigo) !== String(current) && Number(c.anio) === (anio - 1);
            }).sort(function (a, b) {
                return (Number(b.id) || 0) - (Number(a.id) || 0);
            });
            return (prevYear[0] && prevYear[0].codigo) || '';
        }

        if (!esForecastTipo(tipo)) return '';

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

    /** Cantidad de referencia (fila de arriba). Budget: ene–sep = venta real; oct–dic = ventas-budget. */
    function pastQtyMes(cta, monthIdx) {
        if (esSiopTipo()) {
            var c = control.centro;
            if (c && cta) {
                var row = baseBudgetMonthsOf(c.empresa, c.codigo, cta.codigo);
                if (row && mesLleno(row[monthIdx])) return Number(row[monthIdx]) || 0;
            }
            return Number((cta && cta.gasto && cta.gasto[monthIdx]) || 0);
        }
        if (esBudgetTipo()) {
            var i = Number(monthIdx);
            if (i >= 9 && i <= 11) {
                var qRef = lookupVentasBudgetRefQty(cta, i);
                if (qRef) return qRef;
                var qAn = lookupAnalisisVentasBudgetRefQty(cta, i);
                if (qAn) return qAn;
                return Number((cta && cta.gasto && cta.gasto[i]) || 0);
            }
            return Number((cta && cta.gasto && cta.gasto[monthIdx]) || 0);
        }
        return Number((cta && cta.gasto && cta.gasto[monthIdx]) || 0);
    }

    function pastQtyCaption() {
        if (esSiopTipo()) {
            var code = CC.state.cicloBaseCodigo || '';
            if (code && siopBaseTieneDatosCliente()) {
                return 'proy. ' + code;
            }
            if (code) {
                return 'proy. ' + code + ' / venta ' + (CC.state.anioGasto || '');
            }
            return 'venta ' + (CC.state.anioGasto || '');
        }
        if (esBudgetTipo()) {
            return 'venta ' + (CC.state.anioGasto || '') + ' · Oct–Dic budget';
        }
        return 'venta ' + (CC.state.anioGasto || '');
    }

    function ventasBudgetRefKey(c) {
        return String((c && c.empresa) || '').toUpperCase() + '|' + String((c && c.codigo) || '');
    }

    function lookupVentasBudgetRefQty(cta, monthIdx) {
        var map = control._ventasBudgetRefMap || {};
        if (!cta) return 0;
        var hit = map[cta.codigo]
            || map[String(cta.codigo || '').toUpperCase()]
            || map[codigoCuentaKey(cta.codigo)]
            || null;
        if (!hit || !Array.isArray(hit.meses)) return 0;
        var n = Number(hit.meses[monthIdx]);
        return isFinite(n) && n > 0 ? n : 0;
    }

    /** Oct–Dic en Análisis: catálogo ventas-budget por empresa|cliente (sin depender de Captura). */
    function lookupAnalisisVentasBudgetRefQty(cta, monthIdx) {
        if (!cta) return 0;
        var emp = String(cta._empresa || (control.centro && control.centro.empresa) || '').toUpperCase();
        var cc = String(cta._cliente || (control.centro && control.centro.codigo) || '').trim();
        if (!emp || !cc) return 0;
        var bag = (CC._anVentasBudget || {})[emp + '|' + cc];
        if (!bag || typeof bag !== 'object') return 0;
        var hit = bag[cta.codigo]
            || bag[String(cta.codigo || '').toUpperCase()]
            || bag[codigoCuentaKey(cta.codigo)]
            || null;
        if (!hit || !Array.isArray(hit.meses)) return 0;
        var n = Number(hit.meses[monthIdx]);
        return isFinite(n) && n > 0 ? n : 0;
    }

    function analisisVentasBudgetKey(c) {
        return String((c && c.empresa) || '').toUpperCase() + '|' + String((c && c.codigo) || '').trim();
    }

    function applyVentasBudgetRefMap(porArticulo) {
        control._ventasBudgetRefMap = porArticulo && typeof porArticulo === 'object' ? porArticulo : {};
        if (!control.centro || !esBudgetTipo()) return;
        // La ref Oct–Dic es solo informativa: limpia inputs que se habían
        // precargado con el mismo valor (p. ej. botón Budget anterior).
        scrubProyeccionOctDicSiCopiaBudgetRef();
        control._allCtas = cuentasEnriquecidas(control.centro);
        updateControlProgress();
        renderControlTable();
        renderDetalleTable();
        renderCapturaForm();
        paintMatrixHint();
    }

    /**
     * En tipo Budget, Oct–Dic del input no deben copiar ventas-budget.
     * Si la proyección guardada coincide con la ref de arriba, se vacía (queda 0/vacío).
     */
    /**
     * En tipo Budget, Oct–Dic del input son captura (arriba = solo ref).
     * Si la proyección guardada copia ventas-budget, la vacía para dejar capturar.
     */
    function scrubProyeccionOctDicSiCopiaBudgetRef() {
        var c = control.centro;
        if (!c || !esBudgetTipo()) return;
        var map = control._ventasBudgetRefMap || {};
        if (!Object.keys(map).length) return;

        var ctas = control._allCtas || cuentasEnriquecidas(c);
        var any = false;
        ctas.forEach(function (cta) {
            var hit = map[cta.codigo]
                || map[String(cta.codigo || '').toUpperCase()]
                || map[codigoCuentaKey(cta.codigo)]
                || null;
            if (!hit || !Array.isArray(hit.meses)) return;
            var cur = budgetMonthsOf(c.empresa, c.codigo, cta.codigo);
            if (!cur) return;
            var next = cur.slice();
            var changed = false;
            for (var i = 9; i <= 11; i++) {
                if (!mesLleno(next[i])) continue;
                var ref = Number(hit.meses[i]);
                var v = Number(next[i]) || 0;
                if (isFinite(ref) && ref > 0 && Math.abs(v - ref) < 0.0001) {
                    next[i] = null;
                    changed = true;
                }
            }
            if (changed) {
                persistBudget(c.empresa, c.codigo, cta.codigo, next, { completado: false });
                any = true;
            }
        });
        return any;
    }

    /** Solo referencia arriba (Oct–Dic). No escribe la proyección. */
    function loadVentasBudgetRefCentro(c) {
        if (!c || !esBudgetTipo()) {
            control._ventasBudgetRefMap = {};
            return;
        }
        var url = CC.state.ventasBudgetUrl || '/ProyeccionesVentas/api/captura/ventas-budget';
        var key = ventasBudgetRefKey(c);
        control._ventasBudgetRefReq = key;
        var qs = '?empresa=' + encodeURIComponent(c.empresa || '') +
            '&cliente=' + encodeURIComponent(c.codigo || '') +
            '&year=' + encodeURIComponent(CC.state.anioGasto || '') +
            '&meses=10,11,12';
        fetch(url + qs, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.json().then(function (json) {
                if (!r.ok || (json && json.ok === false)) {
                    throw new Error((json && (json.message || json.mensaje)) || 'ventas-budget');
                }
                return json;
            });
        }).then(function (json) {
            if (control._ventasBudgetRefReq !== key) return;
            applyVentasBudgetRefMap((json && json.por_articulo) || {});
        }).catch(function () {
            if (control._ventasBudgetRefReq !== key) return;
            applyVentasBudgetRefMap({});
        });
    }

    /** ¿El Budget/base trae al menos una fila del cliente abierto? */
    function siopBaseTieneDatosCliente() {
        var c = control.centro;
        var map = CC.state.budgetsBase || {};
        if (!c || !Object.keys(map).length) return false;
        var emp = String(c.empresa || '').toUpperCase();
        var cc = String(c.codigo || '').trim();
        return Object.keys(map).some(function (k) {
            var parts = String(k).split('|');
            if (parts.length < 2) return false;
            return String(parts[0]).toUpperCase() === emp && String(parts[1]).trim() === cc;
        });
    }

    function loadBudgetsBaseForSeed(gen, cicloEsperado) {
        var base = cicloBaseProyeccion();
        CC.state.budgetsBase = {};
        CC.state.cicloBaseCodigo = base || '';
        CC._budgetsBaseLoaded = false;
        if (!base) {
            CC._budgetsBaseLoaded = true;
            return Promise.resolve();
        }
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
            CC._budgetsBaseLoaded = true;
        }).catch(function () {
            if (gen != null && gen !== CC._capturaLoadGen) return;
            CC.state.budgetsBase = {};
            CC._budgetsBaseLoaded = true;
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
        if (/^budget$/i.test(t)) return 'BUDGET';
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
        var anioLbl = document.getElementById('pv-costos-anio-label');
        if (anioLbl && CC.state.anioPresupuesto) anioLbl.textContent = String(CC.state.anioPresupuesto);
        persistPeriod();
        syncBudgetTools();
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
            tipoBudget: val('p-tipo-budget') || 'BUDGET',
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

    /** Budget: captura normal, meses en blanco y editables. */
    function esBudgetTipo(t) {
        return String(t || budgetTipo() || '') === 'BUDGET';
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
        if (t === 'BUDGET') return 'Budget';
        if (t === '3+9') return 'Forecast 3+9';
        if (t === '6+6') return 'Forecast 6+6';
        if (t === '9+3') return 'Forecast 9+3';
        if (t === 'SIOP') return 'SIOP';
        return '—';
    }

    function descTipoBudget(t) {
        t = String(t || budgetTipo() || '');
        if (t === 'BUDGET') {
            return '<strong>Budget:</strong> los 12 meses quedan editables para capturar la proyección. '
                + 'Arriba de cada mes se muestra la venta real (ene–sep) y ventas-budget (oct–dic) solo como referencia.';
        }
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
            return '<strong>SIOP:</strong> se precarga el Budget del mismo año (proyección ' + (CC.state.anioPresupuesto || '') + '). '
                + 'Si un mes no tiene Budget, se usa la venta real. Sin dato = 0. Todos editables.';
        }
        return 'Selecciona el tipo de forecast o SIOP.';
    }

    function syncTipoBudgetModalUi() {
        var tipo = val('p-tipo-budget') || 'BUDGET';
        var desc = document.getElementById('p-tipo-budget-desc');
        if (desc) desc.innerHTML = descTipoBudget(tipo);
        var hint = document.getElementById('p-tipo-budget-hint');
        if (hint && !document.getElementById('p-tipo-budget').disabled) {
            if (esBudgetTipo(tipo)) {
                hint.textContent = 'Budget: proyección editable; arriba ene–sep = venta real, oct–dic = ventas-budget.';
            } else if (esSiopTipo(tipo)) {
                hint.textContent = 'SIOP: Budget ' + (CC.state.anioPresupuesto || '') + ' + venta real si falta · sin dato = 0 · todos editables.';
            } else {
                hint.textContent = 'Forecast: venta real fija en los primeros meses · el resto viene del Budget (editable).';
            }
        }
    }

    /**
     * Al abrir Captura de un cliente: solo precarga si el producto NO tiene fila
     * guardada en este ciclo. Nunca pisa capturas ya persistidas al cambiar de ciclo.
     * Forecast: primeros N meses = venta real (fijos); el resto = Budget.
     * SIOP: precarga proyección del mismo año (dato o 0); todos editables.
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

    /** Antes vaciaba el input SIOP si coincidía con el ciclo anterior; eso impedía ver la precarga. */
    function stripSiopSeedLocalOnce() {
        // no-op: SIOP precarga la proyección del año (base) en el input y la deja editable.
    }

    function monthsPositiveCount(months) {
        var n = 0;
        var arr = months || [];
        for (var i = 0; i < 12; i++) {
            if (Number(arr[i]) > 0) n += 1;
        }
        return n;
    }

    /**
     * Fuente de precarga SIOP por mes: Budget/base si tiene dato; si no, venta real.
     * Así no quedan en 0 meses que sí tienen referencia visible arriba.
     */
    function siopFuenteMes(cta, baseRow, monthIdx) {
        if (baseRow && mesLleno(baseRow[monthIdx])) {
            return Number(baseRow[monthIdx]) || 0;
        }
        var g = Number((cta && cta.gasto && cta.gasto[monthIdx]) || 0);
        return g > 0 ? g : 0;
    }

    function siopFuenteMonths(cta, baseRow) {
        var out = [];
        for (var i = 0; i < 12; i++) {
            out.push(siopFuenteMes(cta, baseRow, i));
        }
        return out;
    }

    /** SIOP: re-precargar si faltan meses que sí trae la base o la venta real. */
    function siopDebeReseedDesdeBase(existing, baseRow, cta) {
        var fuente = siopFuenteMonths(cta, baseRow);
        var fuenteN = monthsPositiveCount(fuente);
        if (fuenteN <= 0) return false;
        if (!existing) return true;
        if (monthsPositiveCount(existing) <= 0) return true;
        for (var i = 0; i < 12; i++) {
            var f = Number(fuente[i]) || 0;
            if (f <= 0) continue;
            var e = existing[i];
            if (!mesLleno(e) || Number(e) === 0) return true;
        }
        return false;
    }

    /**
     * SIOP: precarga Budget del mismo año; si un mes no tiene dato en Budget, usa venta real.
     * Meses ya capturados (>0) no se pisan. Ceros se rellenan si la fuente tiene dato.
     */
    function seedSiopDesdeProyeccionBase(c, cicloSeed, loadGen) {
        var ctas = control._allCtas || [];
        if (!ctas.length) return 0;
        var seeded = 0;
        ctas.forEach(function (cta) {
            var existing = budgetMonthsOf(c.empresa, c.codigo, cta.codigo);
            var baseRow = baseBudgetMonthsOf(c.empresa, c.codigo, cta.codigo);
            var needs = siopDebeReseedDesdeBase(existing, baseRow, cta)
                || budgetNecesitaSeed(c.empresa, c.codigo, cta.codigo, existing);
            if (!needs) return;

            var months = [];
            var changed = false;
            for (var i = 0; i < 12; i++) {
                var fuente = siopFuenteMes(cta, baseRow, i);
                var cur = existing && mesLleno(existing[i]) ? Number(existing[i]) || 0 : null;
                if (cur === null) {
                    months.push(toStoreQty(fuente));
                    changed = true;
                } else if (cur === 0 && fuente > 0) {
                    months.push(toStoreQty(fuente));
                    changed = true;
                } else {
                    months.push(toStoreQty(cur));
                }
            }
            if (!changed) return;
            if (monthsPositiveCount(months) <= 0 && existing && monthsPositiveCount(existing) > 0) {
                return;
            }
            var key = budgetKey(c.empresa, c.codigo, cta.codigo);
            CC.state.budgets = CC.state.budgets || {};
            CC.state.budgets[key] = months.slice();
            CC.state.budgetKeysFromServer = CC.state.budgetKeysFromServer || {};
            CC.state.budgetKeysFromServer[key.toUpperCase()] = true;
            persistBudget(c.empresa, c.codigo, cta.codigo, months, { allowLocked: true });
            seeded += 1;
        });
        if (seeded && loadGen === (CC._capturaLoadGen || 0)
            && String(cicloActualCodigo() || '').toUpperCase() === String(cicloSeed).toUpperCase()) {
            control._allCtas = cuentasEnriquecidas(c);
            var desde = CC.state.cicloBaseCodigo || ('proyección ' + (CC.state.anioPresupuesto || ''));
            if (!siopBaseTieneDatosCliente()) {
                desde += ' + venta ' + (CC.state.anioGasto || '');
            }
            toast('success', 'SIOP', 'Se precargaron ' + seeded +
                ' producto' + (seeded === 1 ? '' : 's') +
                ' desde ' + desde +
                ' (sin dato = 0). Todos los meses editables.');
            drawMatrixTable();
            updateControlProgress();
        }
        return seeded;
    }

    /**
     * SIOP: si ya hay fila guardada con meses vacíos (null), completa con 0 en memoria.
     */
    function fillSiopNullMonthsWithZero(c) {
        if (!c || !esSiopTipo()) return;
        var list = control._allCtas || cuentasDeCentro(c);
        var changed = false;
        list.forEach(function (cta) {
            var found = budgetMonthsOf(c.empresa, c.codigo, cta.codigo);
            if (!found) return;
            var months = found.slice();
            var dirty = false;
            for (var i = 0; i < 12; i++) {
                if (!mesLleno(months[i])) {
                    months[i] = 0;
                    dirty = true;
                }
            }
            if (!dirty) return;
            var key = resolveStateKey(CC.state.budgets, c.empresa, c.codigo, cta.codigo)
                || budgetKey(c.empresa, c.codigo, cta.codigo);
            CC.state.budgets[key] = months;
            changed = true;
        });
        if (changed) {
            control._allCtas = cuentasEnriquecidas(c);
        }
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
        if (esBudgetTipo(tipo)) return;

        var seedKey = String(cicloSeed).toUpperCase() + '|' + budgetKey(c.empresa, c.codigo, '*');
        CC._seedDoneFor = CC._seedDoneFor || {};
        var loadGen = CC._capturaLoadGen || 0;

        if (esSiopTipo(tipo)) {
            if (!CC._budgetsBaseLoaded) return;
            // Permitir re-seed si la base tiene datos y SIOP está en ceros (aunque ya se haya intentado).
            var baseStamp = String(CC.state.cicloBaseCodigo || '') + '|' + Object.keys(CC.state.budgetsBase || {}).length;
            var siopStamp = seedKey + '|' + baseStamp;
            fillSiopNullMonthsWithZero(c);
            if (CC._siopSeedStamp === siopStamp && CC._seedDoneFor[seedKey]) {
                // Ya precargamos con esta misma base; igual intenta rellenar ceros si la base mejoró.
                seedSiopDesdeProyeccionBase(c, cicloSeed, loadGen);
                return;
            }
            seedSiopDesdeProyeccionBase(c, cicloSeed, loadGen);
            CC._seedDoneFor[seedKey] = true;
            CC._siopSeedStamp = siopStamp;
            return;
        }

        var n = budgetSeedCount(); // 3/6/9 en Forecast
        if (CC._seedDoneFor[seedKey]) return;

        var ctas = control._allCtas || [];
        if (!ctas.length) return;
        var seeded = 0;
        var fromBase = 0;
        var fromVenta = 0;
        ctas.forEach(function (cta) {
            var existing = budgetMonthsOf(c.empresa, c.codigo, cta.codigo);
            if (!budgetNecesitaSeed(c.empresa, c.codigo, cta.codigo, existing)) return;
            var baseRow = baseBudgetMonthsOf(c.empresa, c.codigo, cta.codigo);
            var gasto = cta.gasto || [];
            var months = [];
            var usedBase = false;
            var usedVenta = false;
            for (var i = 0; i < 12; i++) {
                if (i < n) {
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
            if (!months.some(mesLleno)) return;
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
        CC._seedDoneFor[seedKey] = true;
        if (seeded && loadGen === (CC._capturaLoadGen || 0)
            && String(cicloActualCodigo() || '').toUpperCase() === String(cicloSeed).toUpperCase()) {
            control._allCtas = cuentasEnriquecidas(c);
            var parts = [];
            if (fromVenta) {
                parts.push(n + ' mes' + (n === 1 ? '' : 'es') + ' de venta ' + (CC.state.anioGasto || '') + ' (fijos)');
            }
            if (fromBase) {
                parts.push('resto desde Budget' + (CC.state.cicloBaseCodigo ? (' · ' + CC.state.cicloBaseCodigo) : ''));
            }
            if (budgetLockCount()) parts.push('meses iniciales bloqueados');
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
        if (cc) bits.push(cc + (cc === 1 ? ' cliente' : ' clientes'));
        if (ctas) bits.push(ctas + (ctas === 1 ? ' producto' : ' productos'));
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
            (cc ? '<li>' + cc + (cc === 1 ? ' cliente' : ' clientes') + '</li>' : '') +
            (ctas ? '<li>' + ctas + (ctas === 1 ? ' producto' : ' productos') + '</li>' : '') +
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
            kicker.innerHTML = 'Ciclo ' + escapeHtml(p.codigo || '') + ' · clientes y permisos';
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
                if (CC.state.page === 'analisis') renderAnalisis();
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
        if (!CC.state.puedeEditarPreciosCaptura) {
            toast('warning', 'Sin permiso', 'No tienes permiso para editar precios por mes en Captura.');
            return;
        }
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
        var mon = canonMoneda(cta.precioMoneda || 'MXN');
        var baseEl = document.getElementById('pm-base');
        if (baseEl) baseEl.value = base > 0 ? base.toFixed(2) : '';
        var baseHint = document.getElementById('pm-base-hint');
        if (baseHint) {
            baseHint.textContent = base > 0
                ? ('Global: ' + moneyLista(base, mon) + (cta.unidad ? ' / ' + cta.unidad : '') +
                    ' · cada mes se precarga con Precios de productos o, si falta, con este global')
                : 'Sin precio de lista; captura un valor base o por mes.';
        }
        var box = document.getElementById('pm-months');
        if (box) {
            var maestro = lookupMaestroMeses(c.empresa, c.codigo, codigo);
            var refMeses = (maestro && maestro.meses) || [];
            var refMons = (maestro && maestro.monedas) || [];
            box.innerHTML = MONTHS.map(function (m, i) {
                var ref = Number(refMeses[i]);
                // Precarga: precio del mes en Precios de productos; si no hay, el global.
                var seed = (isFinite(ref) && ref > 0) ? ref : (base > 0 ? base : 0);
                var custom = seed > 0 && base > 0 && Math.abs(seed - base) > 0.0001;
                var refMon = String(refMons[i] || mon || 'MXN').toUpperCase();
                var refHtml = (isFinite(ref) && ref > 0)
                    ? ('<div class="cc-tc-month-ref" title="Precio en Precios de productos (mes ' + (i + 1) + ')">' +
                        escapeHtml(moneyLista(ref, refMon)) + '</div>')
                    : '<div class="cc-tc-month-ref is-empty" title="Sin precio en Precios de productos · se usa el global">—</div>';
                return '<div class="cc-tc-month' + (custom ? ' is-custom' : '') + '">' +
                    '<label for="pm-m-' + i + '">' + m + '</label>' +
                    refHtml +
                    '<input id="pm-m-' + i + '" type="text" inputmode="decimal" placeholder="' +
                    (base > 0 ? base.toFixed(2) : '0.00') + '" value="' + (seed > 0 ? seed.toFixed(2) : '') + '">' +
                    '</div>';
            }).join('');
        }
        highlightPrecioMesesEditor();
        showModal('modalPrecioMeses');
    }

    function parsePrecioInput(raw) {
        var s = String(raw || '').trim().replace(',', '.');
        if (s === '') return NaN;
        var v = Number(s);
        return isFinite(v) ? v : NaN;
    }

    function highlightPrecioMesesEditor() {
        var base = parsePrecioInput(val('pm-base')) || 0;
        MONTHS.forEach(function (_, i) {
            var wrap = document.querySelector('#pm-months .cc-tc-month:nth-child(' + (i + 1) + ')');
            var el = document.getElementById('pm-m-' + i);
            if (!wrap || !el) return;
            var raw = String(el.value || '').trim();
            var v = parsePrecioInput(raw);
            var custom = raw !== '' && isFinite(v) && v > 0 && Math.abs(v - base) > 0.0001;
            wrap.classList.toggle('is-custom', custom || (raw !== '' && isFinite(v) && v > 0));
        });
    }

    function readPrecioMesesFromEditor() {
        var out = [];
        for (var i = 0; i < 12; i++) {
            var el = document.getElementById('pm-m-' + i);
            var raw = el ? String(el.value || '').trim() : '';
            var v = parsePrecioInput(raw);
            out.push(raw !== '' && isFinite(v) && v > 0 ? Math.round(v * 100) / 100 : null);
        }
        return out;
    }

    function seedPrecioMesesDesdeMaestro(base) {
        var c = control.centro;
        var codigo = control._precioEditCodigo || control.cuenta;
        var maestro = (c && codigo) ? lookupMaestroMeses(c.empresa, c.codigo, codigo) : null;
        var refMeses = (maestro && maestro.meses) || [];
        base = Number(base);
        if (!(base > 0)) base = parsePrecioInput(val('pm-base')) || 0;
        for (var i = 0; i < 12; i++) {
            var el = document.getElementById('pm-m-' + i);
            if (!el) continue;
            var ref = Number(refMeses[i]);
            var seed = (isFinite(ref) && ref > 0) ? ref : base;
            el.value = seed > 0 ? seed.toFixed(2) : '';
        }
        highlightPrecioMesesEditor();
    }

    function bindPrecioMesesUi() {
        if (CC._precioMesesBound) return;
        CC._precioMesesBound = true;
        var applyAll = document.getElementById('pm-apply-all');
        if (applyAll) applyAll.addEventListener('click', function () {
            var base = parsePrecioInput(val('pm-base'));
            if (!(base > 0)) {
                toast('warning', 'Precio base', 'Captura un precio global válido.');
                return;
            }
            for (var i = 0; i < 12; i++) {
                var el = document.getElementById('pm-m-' + i);
                if (el) el.value = base.toFixed(2);
            }
            highlightPrecioMesesEditor();
        });
        var reset = document.getElementById('pm-reset');
        if (reset) {
            reset.addEventListener('click', function () {
                seedPrecioMesesDesdeMaestro(parsePrecioInput(val('pm-base')) || 0);
                toast('info', 'Precargado', 'Meses con precio de productos; si falta, el global.');
            });
        }
        var save = document.getElementById('pm-save');
        if (save) save.addEventListener('click', function () {
            var c = control.centro;
            var codigo = control._precioEditCodigo || control.cuenta;
            if (!c || !codigo || control.locked) {
                toast('warning', 'Sin permiso', 'No se puede editar el precio en este momento.');
                return;
            }
            if (!CC.state.puedeEditarPreciosCaptura) {
                toast('warning', 'Sin permiso', 'No tienes permiso para editar precios por mes en Captura.');
                return;
            }
            var meses = readPrecioMesesFromEditor();
            // Si quedó algún mes vacío, completa con global para dejar los 12 asignados.
            var base = parsePrecioInput(val('pm-base')) || 0;
            var filled = 0;
            for (var i = 0; i < 12; i++) {
                if (meses[i] == null && base > 0) meses[i] = Math.round(base * 100) / 100;
                if (meses[i] != null && meses[i] > 0) filled++;
            }
            if (!filled) {
                toast('warning', 'Sin precios', 'Captura al menos un mes o un precio global.');
                return;
            }
            var cta = (control._allCtas || []).filter(function (x) {
                return String(x.codigo) === String(codigo);
            })[0];
            save.disabled = true;
            Promise.resolve(persistPrecioMesesProducto(c.empresa, c.codigo, codigo, meses, {
                moneda: canonMoneda((cta && cta.precioMoneda) || 'MXN'),
                nombre: (cta && cta.nombre) || codigo,
                card_name: (c && c.nombre) || ''
            })).then(function (json) {
                if (control._allCtas) {
                    control._allCtas = cuentasEnriquecidas(c);
                }
                updateMatrixRow(codigo);
                renderControlTable();
                renderControlCharts();
                var modal = document.getElementById('modalPrecioMeses');
                if (modal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).hide();
                var syncMsg = (json && json.message) ? (' · ' + json.message) : '';
                toast('success', 'Precio guardado',
                    filled + ' mes' + (filled === 1 ? '' : 'es') + ' en proyección' + syncMsg);
            }).catch(function (err) {
                toast('error', 'Parcial', (err && err.message)
                    ? ('Proyección ok, pero Costos: ' + err.message)
                    : 'Se guardó la proyección; falló sincronizar Costos.');
                if (control._allCtas) control._allCtas = cuentasEnriquecidas(c);
                updateMatrixRow(codigo);
                renderControlTable();
            }).then(function () {
                save.disabled = false;
            });
        });
        var monthsBox = document.getElementById('pm-months');
        if (monthsBox) {
            monthsBox.addEventListener('input', function (ev) {
                if (ev.target && ev.target.id && ev.target.id.indexOf('pm-m-') === 0) {
                    highlightPrecioMesesEditor();
                }
            });
            monthsBox.addEventListener('blur', function (ev) {
                if (!(ev.target && ev.target.id && ev.target.id.indexOf('pm-m-') === 0)) return;
                var v = parsePrecioInput(ev.target.value);
                if (isFinite(v) && v > 0) ev.target.value = v.toFixed(2);
                else if (String(ev.target.value || '').trim() === '') ev.target.value = '';
                highlightPrecioMesesEditor();
            }, true);
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
            return '<tr data-key="' + centroKey(c) + '">' +
                '<td><div class="fw-semibold">' + c.codigo + ' — ' + escapeHtml(c.nombre) + '</div><div class="text-muted" style="font-size:.75rem">' + ctas.length + ' productos · ' + (c.sap ? 'SAP' : 'catálogo') + '</div></td>' +
                '<td>' + escapeHtml(c.empresa) + '</td>' +
                '<td>' + escapeHtml(c.departamento || '—') + '</td>' +
                '<td>' + userCell(c.usuario) + '</td>' +
                '<td>' + renderBadge(c.estado) + '</td>' +
                '<td>' + (c.modo === 'solo_revision' ? '<span class="cc-badge cc-badge-solo_revision">Solo revisar</span>' : '<span class="cc-badge cc-badge-captura">Captura</span>') + '</td>' +
                '<td class="num">' + moneyVista(totGastoCentroVista(c, ctas)) + '</td>' +
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

    /** Venta real ya en la moneda de vista (LineTotal o LineTotalUSD). */
    function totGastoCentroVista(c, ctas) {
        return (ctas || cuentasEnriquecidas(c)).reduce(function (a, cta) {
            return a + importeVentaVista(cta);
        }, 0);
    }

    function findCentro(key) {
        return CC.state.centros.map(mergedCentro).filter(function (c) { return centroKey(c) === key; })[0];
    }

    function userCell(name) {
        if (!name) return '<span class="text-muted">Sin asignar</span>';
        return '<span class="cc-user"><span class="cc-avatar">' + initials(name) + '</span>' + escapeHtml(name) + '</span>';
    }

    function listaUsuarios(names) {
        var uniq = [];
        (names || []).forEach(function (n) {
            n = String(n || '').replace(/\s+/g, ' ').trim();
            if (n && uniq.indexOf(n) === -1) uniq.push(n);
        });
        return uniq;
    }

    function usersFromAttr(raw) {
        raw = String(raw || '');
        if (!raw) return [];
        if (raw.charAt(0) === '[') {
            try {
                var parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) return listaUsuarios(parsed);
            } catch (e) {}
        }
        try { raw = decodeURIComponent(raw); } catch (e2) {}
        return listaUsuarios(raw.split('\n'));
    }

    function htmlPilaUsuarios(names, withName) {
        var uniq = listaUsuarios(names);
        if (!uniq.length) return '<span class="text-muted">Sin asignar</span>';
        var extra = uniq.length - 1;
        var mostrarNombre = !extra && !!withName;
        var more = extra ? '<span class="cc-avatar cc-avatar-more">+' + extra + '</span>' : '';
        var nombre = mostrarNombre ? '<span class="cc-user-name">' + escapeHtml(uniq[0]) + '</span>' : '';
        var tip = extra || !mostrarNombre;
        var cls = 'cc-user' + (extra ? ' cc-user-stack' : '') + (tip ? ' cc-user-more' : '');
        var attrs = tip
            ? ' tabindex="0" data-users="' + encodeURIComponent(uniq.join('\n')) + '" aria-label="' + escapeHtml(uniq.join(', ')) + '"'
            : '';
        return '<span class="' + cls + '"' + attrs + '><span class="cc-avatar">' + initials(uniq[0]) + '</span>' + more + nombre + '</span>';
    }

    function userTipEl() {
        var tip = document.getElementById('cc-user-tip');
        if (tip) return tip;
        tip = document.createElement('div');
        tip.id = 'cc-user-tip';
        tip.className = 'cc-user-tip';
        tip.hidden = true;
        document.body.appendChild(tip);
        return tip;
    }

    function hideUserTip() {
        var tip = document.getElementById('cc-user-tip');
        if (tip) tip.hidden = true;
    }

    function tipUsuariosDe(el) {
        var nodo = el;
        while (nodo && nodo !== document) {
            if (nodo.getAttribute && nodo.getAttribute('data-users')) return nodo;
            nodo = nodo.parentNode;
        }
        return null;
    }

    function showUserTip(el) {
        var names = usersFromAttr(el.getAttribute('data-users') || '');
        if (!names.length) return;
        var tip = userTipEl();
        tip.innerHTML = names.map(function (n) { return '<div>' + escapeHtml(n) + '</div>'; }).join('');
        tip.hidden = false;
        var r = el.getBoundingClientRect();
        var left = Math.max(8, Math.min(r.left, window.innerWidth - tip.offsetWidth - 8));
        var top = r.bottom + 6;
        if (top + tip.offsetHeight > window.innerHeight - 8) top = Math.max(8, r.top - tip.offsetHeight - 6);
        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
    }

    function bindUserTip() {
        if (CC._userTipBound) return;
        CC._userTipBound = true;
        document.addEventListener('mouseover', function (e) {
            var hit = e.target && e.target.closest ? e.target.closest('.cc-user-more, .cc-avatar-more') : null;
            var el = hit ? tipUsuariosDe(hit) : null;
            if (!el) return;
            showUserTip(el);
        });
        document.addEventListener('mouseout', function (e) {
            var hit = e.target && e.target.closest ? e.target.closest('.cc-user-more, .cc-avatar-more') : null;
            var el = hit ? tipUsuariosDe(hit) : null;
            if (!el) return;
            if (e.relatedTarget && el.contains(e.relatedTarget)) return;
            hideUserTip();
        });
        document.addEventListener('focusin', function (e) {
            var hit = e.target && e.target.closest ? e.target.closest('.cc-user-more, .cc-avatar-more') : null;
            var el = hit ? tipUsuariosDe(hit) : null;
            if (el) showUserTip(el);
        });
        document.addEventListener('focusout', function (e) {
            var hit = e.target && e.target.closest ? e.target.closest('.cc-user-more, .cc-avatar-more') : null;
            var el = hit ? tipUsuariosDe(hit) : null;
            if (el) hideUserTip();
        });
        window.addEventListener('scroll', hideUserTip, true);
    }

    function listaCapturadores() {
        return CC._detalleCapturadores || [];
    }

    function ordenarPrincipal(names, principal) {
        if (principal && names.indexOf(principal) > 0) {
            return [principal].concat(names.filter(function (n) { return n !== principal; }));
        }
        return names;
    }

    function productoAsignadoA(codigo, cuentas) {
        var lista = cuentas || [];
        if (!lista.length) return true;
        var needle = String(codigo || '').trim().toUpperCase();
        if (!needle) return false;
        return lista.some(function (x) {
            var code = String((x && x.codigo) || x || '').trim().toUpperCase();
            return code && code === needle;
        });
    }

    /** Usuarios con Capturar o Editar en el cliente (no el de la sesión). */
    function usuariosCapturaDetalle() {
        var names = [];
        var principal = '';
        listaCapturadores().forEach(function (a) {
            var nombre = String(a.usuario || '').replace(/\s+/g, ' ').trim();
            if (!nombre) return;
            if (names.indexOf(nombre) === -1) names.push(nombre);
            if (a.es_principal && !principal) principal = nombre;
        });
        return ordenarPrincipal(names, principal);
    }

    function usuariosDeProducto(codigo) {
        var names = [];
        var principal = '';
        listaCapturadores().forEach(function (a) {
            var nombre = String(a.usuario || '').replace(/\s+/g, ' ').trim();
            if (!nombre) return;
            if (!productoAsignadoA(codigo, a.cuentas)) return;
            if (names.indexOf(nombre) === -1) names.push(nombre);
            if (a.es_principal && !principal) principal = nombre;
        });
        return ordenarPrincipal(names, principal);
    }

    function nombreDistintoDeCodigo(nombre, codigo) {
        nombre = String(nombre || '').trim();
        codigo = String(codigo || '').trim();
        if (!nombre || (codigo && nombre.toUpperCase() === codigo.toUpperCase())) return '';
        return nombre;
    }

    function nombreClienteDetalle(c) {
        var code = String((c && c.codigo) || '').trim();
        var desdeBoot = nombreDistintoDeCodigo(CC._detalleClienteNombre, code);
        if (desdeBoot) return desdeBoot;
        var propio = nombreDistintoDeCodigo(c && c.nombre, code);
        if (propio) return propio;
        return nombreDistintoDeCodigo(nombreCatalogoCliente(c && c.empresa, code), code);
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
        var gastoMxn = totGastoCentro(c, ctas);
        var gasto = totGastoCentroVista(c, ctas);
        var av = pct(ppto, gastoMxn);
        document.getElementById('det-title').textContent = c.codigo + ' — ' + c.nombre;
        document.getElementById('det-body').innerHTML =
            '<div class="cc-context">' +
            item('Empresa', c.empresa) + item('Departamento', c.departamento) +
            item('Responsable', c.usuario || 'Sin asignar') + item('Estado', STATUS[normalizeEstado(c.estado)].label) +
            item('Modo', c.modo === 'solo_revision' ? 'Solo revisar' : 'Puede capturar') +
            item('Productos', ctas.length) +
            item('Venta ' + (CC.state.anioGasto || ''), moneyVista(gasto)) + item('Proy. ' + (CC.state.anioPresupuesto || ''), money(ppto)) +
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
        var needle = String(ciclo || '').toUpperCase();
        return (CC.state.misAsignaciones || []).filter(function (a) {
            return !needle || String(a.ciclo || '').toUpperCase() === needle;
        });
    }

    function clavesPermiso(a) {
        var p = (a && a.permisos) || [];
        return {
            capturar: !!(a && (a.capturar || p.indexOf('capturar') !== -1)),
            editar: !!(a && (a.editar || p.indexOf('editar') !== -1)),
            revisar: !!(a && (a.revisar || p.indexOf('revisar') !== -1))
        };
    }

    /** Revisar no entra a captura, aunque la fila también tenga Editar. */
    function esAsignacionRevision(a) {
        var k = clavesPermiso(a);
        return k.revisar && !k.capturar;
    }

    function permisoCapturaOEditar(a) {
        if (!a || esAsignacionRevision(a)) return false;
        var k = clavesPermiso(a);
        return k.capturar || k.editar;
    }

    function esAsignacionPropia(a) {
        var uid = Number(CC.state.usuarioActualId || 0);
        if (!uid) return true;
        var owner = Number(a && a.user_id);
        return !owner || owner === uid;
    }

    /**
     * Captura lista empresas y clientes propios con Capturar o Editar.
     * Sigue visible con el ciclo cerrado, para ver cuáles quedaron asignados.
     * Las de revisión no entran.
     */
    function asignacionesCaptura(ciclo) {
        return asignacionesDelCiclo(ciclo).filter(function (a) {
            var code = codigoClienteAsig(a);
            if (!code || isEmpresaCompleta(code)) return false;
            return esAsignacionPropia(a) && permisoCapturaOEditar(a);
        });
    }

    function fuenteAsignacionesControl(ciclo) {
        return CC.state.page === 'control' ? asignacionesCaptura(ciclo) : asignacionesDelCiclo(ciclo);
    }

    function centrosDesdeAsignaciones(list) {
        return centrosAgrupadosDesdeAsignaciones(list);
    }

    function centrosAgrupadosDesdeAsignaciones(list) {
        var map = {};
        (list || []).forEach(function (a) {
            var emp = String(a.empresa || '').toUpperCase().trim();
            var code = codigoClienteAsig(a);
            var k = emp + '|' + code;
            if (!emp || !code || isEmpresaCompleta(code)) return;
            if (!map[k]) {
                map[k] = {
                    codigo: code,
                    nombre: nombreClienteAsig(a) || code,
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
            if (nombreClienteAsig(a)) row.nombre = nombreClienteAsig(a);
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

    function ciclosAbiertosDeMisAsignaciones() {
        return ciclosDeMisAsignaciones().filter(function (c) {
            return normalizeCicloEstado(c.estado) === 'abierto';
        });
    }

    function ciclosVisiblesEnCaptura() {
        return ciclosDeMisAsignaciones().filter(function (c) {
            return asignacionesCaptura(c.codigo).length;
        });
    }

    function cicloControlPreferido() {
        var mine = CC.state.page === 'control' ? ciclosVisiblesEnCaptura() : ciclosAbiertosDeMisAsignaciones();
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
        ['ctl-guardar', 'ctl-guardar-seguir', 'ctl-copy-year', 'ctl-refresh-venta', 'ctl-clear-year', 'ctl-apply-infl', 'ctl-btn-dispersar', 'ctl-completar', 'ctl-ajuste-pct', 'ctl-prod-q', 'ctl-add-btn'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.disabled = !on;
        });
    }

    function syncBudgetTools() {
        // El botón Budget Oct–Nov–Dic se eliminó: la ref Oct–Dic se carga sola arriba del input.
    }

    function lookupMaestroLocal(empresa, cliente, codigo) {
        var map = CC.state.costosMaster || {};
        if (!codigo) return null;
        var emp = String(empresa || '').toUpperCase();
        var card = String(cliente || '').trim();
        var cod = String(codigo || '').trim();
        // Con CardCode: solo la clave exacta. No heredar precio de otro cliente (EMP|Item).
        if (emp && card && cod) {
            return map[emp + '|' + card + '|' + cod] || null;
        }
        var hit = null;
        if (emp && cod) {
            hit = map[emp + '|' + cod];
        }
        if (!hit) {
            hit = map[cod] || map[cod.toUpperCase()] || null;
        }
        return hit || null;
    }

    /** Precios mensuales del maestro Precios de productos (Empresa+CardCode+ItemCode).
     *  Con cliente: solo clave exacta EMP|Card|Item. Sin fallback a EMP|Item / Item
     *  (evita mezclar meses de otro cliente con el mismo producto). */
    function lookupMaestroMeses(empresa, cliente, codigo) {
        var map = CC.state.costosMeses || {};
        if (!codigo) return null;
        var emp = String(empresa || '').toUpperCase();
        var card = String(cliente || '').trim();
        var cod = String(codigo || '').trim();
        var hit = null;
        if (emp && card && cod) {
            hit = map[emp + '|' + card + '|' + cod] || null;
        } else if (emp && cod) {
            hit = map[emp + '|' + cod] || null;
            if (!hit) hit = map[cod] || map[cod.toUpperCase()] || null;
        } else if (cod) {
            hit = map[cod] || map[cod.toUpperCase()] || null;
        }
        if (!hit || !hit.meses) return null;
        return hit;
    }

    function listaPrecioDeCentro(c, codigo) {
        if (!c || !codigo) return null;
        var own = CC.state.preciosCache && CC.state.preciosCache[preciosCacheKey(c)];
        if (own) {
            return precioListaEnMapa(own, codigo);
        }
        if (control.centro
            && String(control.centro.codigo) === String(c.codigo)
            && String(control.centro.empresa || '').toUpperCase() === String(c.empresa || '').toUpperCase()
            && control._preciosReq === preciosCacheKey(c)) {
            return lookupPrecioLista(codigo);
        }
        return null;
    }

    function cuentasEnriquecidas(c) {
        return cuentasDeCentro(c).map(function (cta) {
            var ppto = pptoDe(c.empresa, c.codigo, cta, cta.gasto);
            var totP = sum(ppto);
            var lista = listaPrecioDeCentro(c, cta.codigo) || lookupPrecioLista(cta.codigo) || {};
            var master = lookupMaestroLocal(c.empresa, c.codigo, cta.codigo);
            var maestroMesesHit = lookupMaestroMeses(c.empresa, c.codigo, cta.codigo);
            var snap = Number((CC.state.costos || {})[budgetKey(c.empresa, c.codigo, cta.codigo)]) || 0;
            var hasMasterRow = !!master;
            var masterPrecio = hasMasterRow ? (Number(master.costo) || 0) : 0;
            var masterMes = (master && Number(master.mes)) || 0;
            // PRECIO proy. local = solo precio global del maestro (Ventas/Costos).
            // PRECIO DE LISTA = columna precio_lista del maestro local (no SAP).
            var masterPrecioLista = hasMasterRow
                ? (Number(master.precio_lista != null ? master.precio_lista : master.precioLista) || 0)
                : 0;
            if (!(masterPrecioLista > 0) && lista && Number(lista.precio) > 0
                && String(lista.lista || lista.fuente || '').indexOf('Maestro local') >= 0) {
                masterPrecioLista = Number(lista.precio) || 0;
            }
            var precioListaSap = masterPrecioLista > 0 ? masterPrecioLista : 0;
            var precioLista = hasMasterRow ? masterPrecio : 0;
            var precioMoneda = '';
            if (hasMasterRow && master && master.moneda) {
                precioMoneda = canonMoneda(master.moneda);
            } else {
                precioMoneda = 'MXN';
            }
            var precioMaestroMeses = (maestroMesesHit && maestroMesesHit.meses)
                ? maestroMesesHit.meses.slice(0, 12)
                : mesesVacios12();
            var enriched = Object.assign({}, cta, {
                _empresa: c.empresa || '',
                _cliente: c.codigo || '',
                ppto: ppto,
                totG: sum(cta.gasto),
                totP: totP,
                listo: mesesTodosLlenos(ppto) || cuentaMarcada(c.empresa, c.codigo, cta.codigo),
                costo: Number(cta.costo) || Number((CC.state.costos || {})[budgetKey(c.empresa, c.codigo, cta.codigo)]) || 0,
                precioLista: precioLista,
                precioListaSap: precioListaSap,
                precioListaSapMoneda: precioMoneda || 'MXN',
                precioMoneda: precioMoneda || 'MXN',
                precioMeses: preciosMesesDe(c.empresa, c.codigo, cta.codigo),
                precioMaestroMeses: precioMaestroMeses,
                precioLocalMes: masterMes > 0 ? masterMes : null,
                tienePrecioMaestro: hasMasterRow,
                unidad: String((lista && lista.unidad) || cta.unidad || '').trim(),
                unidadNombre: String((lista && lista.unidad_nombre) || cta.unidadNombre || '').trim(),
                listaPrecioNombre: hasMasterRow
                    ? 'Maestro local · global'
                    : '',
                listaPrecioNombreSap: precioListaSap > 0
                    ? 'Maestro local · Precio lista'
                    : String((lista && lista.lista) || '').trim(),
                listaPrecioNoSap: String((lista && lista.no_lista) || '').trim()
            });
            enriched.unidadesAnio = unidadesAnuales(enriched.ppto);
            if (!enriched.unidadNombre && enriched.unidad) {
                enriched.unidadNombre = nombreUnidadMedida(enriched.unidad);
            }
            var costoLocal = Number(cta.costo) || snap || (hasMasterRow ? masterPrecio : 0) || 0;
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
            return a + importeVentaVista(x);
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
        var asigs = fuenteAsignacionesControl(ciclo);
        var emps = {};
        asigs.forEach(function (a) {
            if (CC.state.page === 'control' && isEmpresaCompleta(codigoClienteAsig(a))) return;
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
            if (isEmpresaCompleta(a.centro_codigo)) return n;
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

    function cacheKeyClientesEmpresa(emp) {
        var year = Number(CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || new Date().getFullYear());
        return String(emp || '').toUpperCase() + '|' + year;
    }

    function clientesEmpresaDe(emp) {
        CC._cliEmp = CC._cliEmp || {};
        return CC._cliEmp[cacheKeyClientesEmpresa(emp)] || null;
    }

    function nombreCatalogoCliente(emp, codigo) {
        var rows = clientesEmpresaDe(emp) || [];
        var code = String(codigo || '').trim();
        var hit = rows.filter(function (c) { return String(c.codigo || '').trim() === code; })[0];
        return hit ? String(hit.nombre || '').trim() : '';
    }

    function ensureClientesEmpresa(emp) {
        var key = cacheKeyClientesEmpresa(emp);
        CC._cliEmp = CC._cliEmp || {};
        CC._cliEmpLoad = CC._cliEmpLoad || {};
        if (CC._cliEmp[key] || CC._cliEmpLoad[key]) return;
        CC._cliEmpLoad[key] = true;
        var year = key.split('|')[1];
        fetch('/ProyeccionesVentas/api/centros?empresa=' + encodeURIComponent(String(emp || '').toLowerCase()) + '&year=' + encodeURIComponent(year), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (json) {
            CC._cliEmp[key] = json.centros || [];
            CC._cliEmpLoad[key] = false;
            if (CC.state.page === 'detalle' && control.centro) {
                var nombreCli = nombreClienteDetalle(control.centro);
                if (nombreCli) control.centro.nombre = nombreCli;
                paintDetalleHeader();
            }
            if (String(val('ctl-empresa') || '').toUpperCase() !== String(emp || '').toUpperCase()) return;
            var before = String(val('ctl-centro') || '');
            renderNavCentros();
            var now = String(val('ctl-centro') || '');
            if (now && now !== before) loadControlCentro();
        }).catch(function () {
            CC._cliEmp[key] = [];
            CC._cliEmpLoad[key] = false;
        });
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
        var wide = raw.some(function (a) { return isEmpresaCompleta(a.centro_codigo); });
        var seenCc = {};
        var list = [];
        raw.forEach(function (a) {
            var code = String(a.centro_codigo || '').trim();
            if (!code || isEmpresaCompleta(code) || seenCc[code]) return;
            seenCc[code] = true;
            list.push(a);
        });
        if (wide && CC.state.page !== 'control') {
            var catalogo = clientesEmpresaDe(emp);
            if (!catalogo) {
                ensureClientesEmpresa(emp);
                if (!list.length) {
                    el.innerHTML = '<option value="">Cargando clientes de la empresa…</option>';
                    el.disabled = true;
                    el.classList.remove('is-warn', 'is-ok', 'is-danger');
                    paintNavStatus('ctl-cc-status', null, el);
                    return;
                }
            } else {
                catalogo.forEach(function (c) {
                    var code = String(c.codigo || '').trim();
                    if (!code || seenCc[code]) return;
                    seenCc[code] = true;
                    list.push({
                        centro_codigo: code,
                        centro_nombre: c.nombre || code,
                        empresa: emp,
                        revisar: true,
                        capturar: false,
                        permisos: ['revisar']
                    });
                });
            }
        }
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
        var ciclos = CC.state.page === 'control' ? ciclosVisiblesEnCaptura() : ciclosAbiertosDeMisAsignaciones();
        var cur = String(el.value || '').trim();
        var selected = cur;
        var curOpen = ciclos.some(function (c) { return String(c.codigo) === cur; });
        if (!selected || !curOpen) {
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
        }).join('') || '<option value="">Sin ciclos abiertos</option>';
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
            syncBudgetTools();
            loadControlCentro();
        }
        if (cicloChanged) {
            CC._cicloUserPicked = true;
            applyPeriodFromCiclo(findCiclo(ciclo), ciclo);
            CC.state.centros = centrosDesdeAsignaciones(fuenteAsignacionesControl(ciclo));
            // Si la empresa ya no aplica en el ciclo nuevo, limpiarla (y el cliente).
            var empKeep = String(emp || (empEl && empEl.value) || '').toUpperCase().trim();
            var empsOk = Object.keys(asigsPorEmpresa(ciclo) || {}).map(function (k) {
                return String(k).toUpperCase();
            });
            if (empKeep && empsOk.indexOf(empKeep) === -1) {
                emp = '';
                cc = '';
                if (empEl) empEl.value = '';
                if (ccEl) ccEl.value = '';
            } else if (empKeep) {
                emp = empKeep;
                var ccsOk = (asigsPorEmpresa(ciclo)[empKeep] || []).map(function (a) {
                    return String(a.centro_codigo || '').trim();
                });
                var ccKeep = String(cc || (ccEl && ccEl.value) || '').trim();
                if (ccKeep && ccsOk.indexOf(ccKeep) === -1) {
                    cc = '';
                    if (ccEl) ccEl.value = '';
                } else if (ccKeep) {
                    cc = ccKeep;
                }
            }
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
        if (!asignacionesCaptura('').length) {
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
        if (ciclo && !asignacionesCaptura(ciclo).length) ciclo = cicloControlPreferido();
        if (cicloSel && ciclo) cicloSel.value = ciclo;
        CC.state.cicloCodigo = ciclo || '';
        // Evita que re-renders posteriores cambien el ciclo sin recargar presupuestos.
        if (ciclo) CC._cicloUserPicked = true;
        applyPeriodFromCiclo(findCiclo(ciclo), ciclo);
        CC.state.centros = centrosDesdeAsignaciones(asignacionesCaptura(ciclo));

        if (!CC._controlBound) {
            if (cicloSel) cicloSel.addEventListener('change', function () {
                CC._cicloUserPicked = true;
                control.cuenta = null;
                // Conservar empresa/cliente si siguen en el ciclo nuevo (para reutilizar snapshot).
                applyControlPath(this.value, val('ctl-empresa'), val('ctl-centro'));
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
                if (control.centro) control._allCtas = cuentasEnriquecidas(control.centro);
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
            bindAgregarProducto();
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
        syncBudgetTools();
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
        loadDetalleLocalBootstrap().then(function () {
            loadControlCentro();
            if (control.centro) {
                var nombreCli = nombreClienteDetalle(control.centro);
                if (nombreCli) control.centro.nombre = nombreCli;
                else ensureClientesEmpresa(control.centro.empresa);
            }
            paintDetalleHeader();
            renderDetalleTable();
            renderDetalleChart();
            paintScopeHints();
        });
    };

    /** Detalle: un solo JSON local (captura + snapshot + precios). Sin SAP. */
    function loadDetalleLocalBootstrap() {
        var ciclo = String(cicloActualCodigo() || CC.state.cicloInicial || '').trim();
        var empresa = String(val('ctl-empresa') || CC.state.empresaInicial || '').trim();
        var cc = String(val('ctl-centro') || CC.state.centroInicial || '').trim();
        var year = CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || new Date().getFullYear();
        CC._capturaLoadGen = (CC._capturaLoadGen || 0) + 1;
        var gen = CC._capturaLoadGen;
        CC._seedDoneFor = {};
        CC._siopStrippedFor = '';
        CC._siopSeedStamp = '';
        CC._budgetsBaseLoaded = true;
        CC._capturaReady = false;
        CC._detalleCapturadores = [];
        CC._detalleClienteNombre = '';
        CC.state.overlays = {};
        CC.state.budgets = {};
        CC.state.budgetsBase = {};
        CC.state.budgetKeysFromServer = {};
        CC.state.completados = {};
        CC.state.preciosMeses = {};
        CC.state.cicloCodigo = ciclo || CC.state.cicloCodigo || '';

        if (!ciclo || !empresa || !cc) {
            CC._capturaReady = true;
            return Promise.resolve();
        }

        var url = (CC.state.capturaBootstrapUrl || '/ProyeccionesVentas/api/captura/bootstrap') +
            '?ciclo=' + encodeURIComponent(ciclo) +
            '&empresa=' + encodeURIComponent(empresa) +
            '&cc=' + encodeURIComponent(cc) +
            '&year=' + encodeURIComponent(year);

        return fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            if (!r.ok) throw new Error('bootstrap ' + r.status);
            return r.json();
        }).then(function (json) {
            if (gen !== CC._capturaLoadGen) return;
            CC._detalleCapturadores = Array.isArray(json.capturadores) ? json.capturadores : [];
            CC._detalleClienteNombre = String(json.cliente_nombre || '').trim();
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

            var cRef = { empresa: empresa, codigo: cc };
            var gKey = gastoCacheKey(cRef);
            var venta = json.venta_real;
            if (venta && venta.ok && venta.por_cuenta) {
                rememberGastoCache(gKey, venta.por_cuenta, {
                    fuente: venta.fuente || 'snapshot',
                    synced_at: venta.synced_at || null,
                    year: venta.year || year,
                    budget_completo: !!venta.budget_completo,
                    mensaje: null
                });
            }

            var budgetRef = json.ventas_budget_ref || {};
            if (budgetRef && typeof budgetRef === 'object' && Object.keys(budgetRef).length) {
                control._ventasBudgetRefMap = budgetRef;
            } else {
                control._ventasBudgetRefMap = {};
            }

            var precios = (json.precios && json.precios.por_articulo) || {};
            var pKey = preciosCacheKey(cRef);
            control._preciosReq = pKey;
            control._preciosLoading = '';
            applyPreciosMap(precios);

            CC._capturaReady = true;
            CC._detalleLocalBoot = true;
        }).catch(function (err) {
            if (gen !== CC._capturaLoadGen) return;
            console.warn('[PV] Bootstrap local de detalle falló', err);
            CC._capturaReady = true;
            // Fallback: overlays del ciclo (BD) sin SAP de catálogo.
            return loadOverlaysForCycle();
        });
    }

    function bindDetalleCurrency() {
        var mon = document.getElementById('ctl-moneda');
        if (mon) mon.value = CC.state.currency || 'MXN';
        bindUserTip();
        if (CC._detalleBound) return;
        if (mon) {
            mon.addEventListener('change', function () {
                CC.state.currency = this.value || 'MXN';
                saveJSON(SK.currency, CC.state.currency);
                if (control.centro) control._allCtas = cuentasEnriquecidas(control.centro);
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
            // Último recurso: buscar en las asignaciones del ciclo (por si empresa viene con otro casing).
            a = fuenteAsignacionesControl(ciclo || val('ctl-ciclo') || CC.state.cicloCodigo).filter(function (x) {
                return String(x.centro_codigo || '').trim() === code
                    && String(x.empresa || '').toUpperCase().trim() === e;
            })[0] || null;
        }
        if (!a && CC.state.page !== 'control') {
            a = asignacionesDelCiclo(ciclo || val('ctl-ciclo') || CC.state.cicloCodigo).filter(function (x) {
                return isEmpresaCompleta(x.centro_codigo)
                    && String(x.empresa || '').toUpperCase().trim() === e;
            })[0] || null;
        }
        if (!a) return null;
        var wide = isEmpresaCompleta(a.centro_codigo);
        return mergedCentro({
            codigo: wide ? code : String(a.centro_codigo || '').trim(),
            nombre: wide ? (nombreCatalogoCliente(e, code) || code) : (a.centro_nombre || a.centro_codigo),
            empresa: e,
            usuario: a.usuario || '',
            estado: 'abierto',
            modo: (a.capturar || a.editar) ? 'captura' : 'solo_revision',
            departamento: '',
            ciclo: a.ciclo || ciclo || CC.state.cicloCodigo || '',
            capturar: !!a.capturar,
            editar: !!a.editar,
            revisar: !!(a.revisar || wide),
            permisos: (a.permisos && a.permisos.length) ? a.permisos : (wide ? ['revisar'] : []),
            sap: true
        });
    }

    function loadControlCentro() {
        var emp = String(val('ctl-empresa') || '').trim();
        var codigo = String(val('ctl-centro') || '').trim();
        if (control._addClienteKey !== (emp + '|' + codigo)) {
            control._addClienteKey = emp + '|' + codigo;
            var addQ = document.getElementById('ctl-add-q');
            if (addQ) addQ.value = '';
        }
        if (!emp || !codigo) {
            control.centro = null;
            control.cuenta = null;
            control._allCtas = [];
            control._ctas = [];
            control._preciosMap = {};
            control._preciosReq = null;
            control._gastoMeta = {};
            paintVentaSnapshotHint({});
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
        CC.state.centros = centrosDesdeAsignaciones(fuenteAsignacionesControl(cicloNow));
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
        if (CC.state.page === 'detalle') {
            // Detalle: solo local (bootstrap/snapshot). Sin listas-precios ni ventas-budget SAP.
            var gKey = gastoCacheKey(c);
            control._gastoReq = gKey;
            var gHit = gastoCacheEntry(gKey);
            if (gHit) {
                applyGastoMap(gHit.por, gHit.meta);
            } else {
                loadGastoRealCentro(c, { force: false, auto: true });
            }
            var pKey = preciosCacheKey(c);
            if (CC.state.preciosCache && CC.state.preciosCache[pKey]) {
                control._preciosReq = pKey;
                control._preciosLoading = '';
                control._preciosMap = CC.state.preciosCache[pKey];
            }
        } else {
            // Captura: venta desde snapshot (SAP solo con «Actualizar venta SAP»).
            // Precio lista = maestro local (no SAP).
            loadGastoRealCentro(c, { force: false, auto: true });
            loadVentasBudgetRefCentro(c);
            applyPreciosMap(preciosMapDesdeMaestroLocal(c));
        }
        stripSiopSeedLocalOnce(c);
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

    /** Rojo < 40%, amarillo 40–79%, verde desde 80%. */
    function semaforoAvance(pct) {
        var n = Number(pct) || 0;
        if (n >= 80) return 'good';
        if (n >= 40) return 'warn';
        return 'bad';
    }

    function paintBarraProgreso(wrapId, barId, pct, labelId) {
        var n = Math.max(0, Math.min(Number(pct) || 0, 100));
        var cls = semaforoAvance(n);
        var bar = document.getElementById(barId);
        var wrap = document.getElementById(wrapId);
        if (bar) bar.style.width = n + '%';
        if (wrap) wrap.className = 'cc-progress ' + cls;
        if (labelId) {
            var label = document.getElementById(labelId);
            if (label) label.className = 'cc-semaforo-' + cls;
        }
        return cls;
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
            var barEmpty = document.getElementById('ctl-avance-bar');
            var wrapEmpty = document.getElementById('ctl-avance-wrap');
            var labelEmpty = document.getElementById('kpi-ctl-avance');
            if (barEmpty) barEmpty.style.width = '0%';
            if (wrapEmpty) wrapEmpty.className = 'cc-progress';
            if (labelEmpty) labelEmpty.className = '';
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
        setText('kpi-ctl-ppto', moneyGasto(st.totPVista != null ? st.totPVista : st.totP));
        setText('kpi-ctl-pend', st.pendientes);
        paintBarraProgreso('ctl-avance-wrap', 'ctl-avance-bar', st.avance, 'kpi-ctl-avance');
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
        if (empty) empty.hidden = true;
        if (body) body.hidden = false;
        paintNombreCodigo('ctl-form-cc', cc.nombre || '', cc.codigo || '');
        setText('ctl-form-cta', st.total + (st.total === 1 ? ' producto' : ' productos'));
        setText('ctl-form-grupo', !st.total
            ? 'Agrega un producto para capturarlo aquí'
            : (ctas.length === st.total
                ? 'Edita Ene–Dic por fila'
                : ('Mostrando ' + ctas.length + ' de ' + st.total)));
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
        paintBarraProgreso('ctl-form-avance-wrap', 'ctl-form-avance-bar', st.avance, 'ctl-form-avance');
    }

    function paintMatrixLegend() {
        var legend = document.getElementById('ctl-matrix-legend');
        var ok = document.getElementById('ctl-legend-ok');
        var over = document.getElementById('ctl-legend-over');
        if (legend) legend.hidden = false;
        if (esSiopTipo()) {
            var src = pastQtyCaption();
            if (ok) ok.textContent = 'Mayor o igual a ' + src;
            if (over) over.textContent = 'Menor a ' + src;
            return;
        }
        var anio = CC.state.anioGasto || '';
        if (ok) ok.textContent = 'Mayor o igual a venta real';
        if (over) over.textContent = 'Menor a venta' + (anio ? (' ' + anio) : ' pasada');
    }

    function paintMatrixHint() {
        var hint = document.getElementById('ctl-matrix-hint');
        paintMatrixLegend();
        if (!hint) return;
        var cta = currentCta();
        var arriba = pastQtyCaption();
        if (esBudgetTipo()) {
            hint.textContent = cta
                ? ('Fila activa: ' + labelNombreCodigo(cta.nombre, cta.codigo) +
                    '. Arriba = venta ' + (CC.state.anioGasto || '') + ' (ene–sep) y budget (oct–dic); abajo = proyección editable.')
                : ('Budget: arriba = venta ' + (CC.state.anioGasto || '') + ' ene–sep + budget oct–dic. Abajo: captura la proyección.');
            return;
        }
        if (cta) {
            hint.textContent = 'Fila activa: ' + labelNombreCodigo(cta.nombre, cta.codigo) +
                '. Arriba = ' + arriba + '; abajo = proyección. Copiar / Limpiar / % / Completado aplican a esta fila.';
            return;
        }
        hint.textContent = 'Arriba de cada mes: ' + arriba + '. Abajo: proyección. Clic en un producto para herramientas puntuales.';
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
        paintBarraProgreso('ctl-form-avance-wrap', 'ctl-form-avance-bar', av, 'ctl-form-avance');
    }

    function updateFormTotals(cta) {
        if (!cta) {
            updateFormTotalsCliente();
            return;
        }
        var ventaVista = importeVentaVista(cta);
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
        var src = canonMoneda(monedaNativa);
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

    /** Importe MXN de un mes de venta real (o ref Budget Oct–Dic vía pastQtyMes). */
    function importeMesVentaMxn(cta, i) {
        if (!cta) return 0;
        var fromSap = Number((cta.importe && cta.importe[i]) || 0);
        if (fromSap) return fromSap;
        var usd = Number(usdSeriesOf(cta)[i]) || 0;
        if (usd) return usd * fxRate(i);
        var qty = Number((cta.gasto && cta.gasto[i]) || 0);
        if (!qty) {
            // Budget: Oct–Dic pueden venir de ventas-budget (ref informativa).
            qty = Number(pastQtyMes(cta, i)) || 0;
        }
        var precio = Number(cta.precioLista) || 0;
        if (!qty || !precio) return 0;
        var mon = canonMoneda(cta.precioMoneda || 'MXN');
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
            var mon = canonMoneda(cta.precioMoneda || 'MXN');
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

    /**
     * Precio lista desde maestro local (tbl_pv_productos_costo.precio_lista).
     * Ya no consulta SAP en Captura.
     */
    function precioListaSapInfo(cta) {
        var unidad = String((cta && cta.unidad) || '').trim();
        var unidadNombre = String((cta && cta.unidadNombre) || '').trim();
        var precio = Number(cta && cta.precioListaSap) || 0;
        var mon = canonMoneda((cta && cta.precioListaSapMoneda) || (cta && cta.precioMoneda) || 'MXN');

        if (!(precio > 0) && cta && cta.codigo) {
            var c = control.centro;
            if (c) {
                var master = lookupMaestroLocal(c.empresa, c.codigo, cta.codigo);
                if (master) {
                    precio = Number(master.precio_lista != null ? master.precio_lista : master.precioLista) || 0;
                    if (master.moneda) mon = canonMoneda(master.moneda);
                }
            }
        }

        return {
            precio: precio,
            moneda: mon,
            unidad: unidad,
            unidadNombre: unidadNombre,
            decimals: 2,
            title: precio > 0
                ? 'Precio lista · Maestro local (Ventas/Costos)'
                : 'Sin Precio lista en el maestro local. Usa «Obtener precio lista» en /Ventas/Costos.'
        };
    }

    /** Precio de referencia legado: maestro local o promedio de venta pasada. */
    function precioVentaAnioRef(cta) {
        var unidad = String((cta && cta.unidad) || '').trim();
        var unidadNombre = String((cta && cta.unidadNombre) || '').trim();
        var mon = canonMoneda((cta && cta.precioMoneda) || 'MXN');
        var anio = CC.state.anioGasto || '';

        var local = Number(cta && cta.precioLista) || 0;
        if (local > 0 && (cta.listaPrecioNombre || '').indexOf('Maestro local') === 0) {
            return {
                precio: local,
                moneda: mon,
                unidad: unidad,
                unidadNombre: unidadNombre,
                decimals: 2,
                title: 'Precio global · Precios de productos' + (anio ? (' · proy. ' + anio) : '')
            };
        }

        var c = control.centro;
        if (c && cta && cta.codigo) {
            var master = lookupMaestroLocal(c.empresa, c.codigo, cta.codigo);
            if (master && Number(master.costo) > 0) {
                return {
                    precio: Number(master.costo),
                    moneda: String(master.moneda || mon || 'MXN').toUpperCase(),
                    unidad: unidad,
                    unidadNombre: unidadNombre,
                    decimals: 2,
                    title: 'Precio global · Precios de productos' + (anio ? (' · proy. ' + anio) : '')
                };
            }
            var maestro = lookupMaestroMeses(c.empresa, c.codigo, cta.codigo);
            var moda = precioModaDeMeses((maestro && maestro.meses) || []);
            if (moda > 0) {
                return {
                    precio: moda,
                    moneda: mon,
                    unidad: unidad,
                    unidadNombre: unidadNombre,
                    decimals: 2,
                    title: 'Precio global (moda) · Precios de productos' + (anio ? (' · proy. ' + anio) : '')
                };
            }
        }

        var info = precioVentaPasadaInfo(cta);
        if (!info.title) {
            info.title = 'Precio unitario promedio de la venta ' + (anio || '') + ' (importe SAP ÷ uds).';
        }
        return info;
    }

    /** @deprecated Preferir precioListaSapInfo en Captura (columna Precio de lista). */
    function costoPiezaVentaAnio(cta) {
        return precioListaSapInfo(cta);
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

    /** Precio proy. local: solo precio global del maestro (Ventas/Costos). Si es 0, muestra 0. */
    function precioProyeccionInfo(cta) {
        var fromLocal = !!(cta && cta.tienePrecioMaestro);
        var precio = fromLocal ? (Number(cta.precioLista) || 0) : 0;
        return {
            precio: precio,
            moneda: canonMoneda((cta && cta.precioMoneda) || 'MXN'),
            unidad: String((cta && cta.unidad) || '').trim(),
            unidadNombre: String((cta && cta.unidadNombre) || '').trim(),
            porMes: fromLocal ? precioMesesVarian(cta) : false,
            showZero: fromLocal,
            title: fromLocal
                ? 'Precio global del maestro (Ventas/Costos · mes=0)'
                : 'Sin precio global en el maestro para este Empresa+CardCode+Item'
        };
    }

    function nombreUnidadMedida(codigo) {
        var code = String(codigo || '').trim();
        if (!code) return '';
        var aliases = {
            XBX: 'CAJA',
            RK: 'ROLLOS',
            XSA: 'SACOS',
            H87: 'PIEZAS',
            MTS: 'METROS',
            E48: 'SERVICIOS',
            KGM: 'KILOS',
            RL: 'ROLLOS',
            RO: 'ROLLOS',
            BO: 'Botella',
            PR: 'Par',
            SET: 'Juego',
            PZA: 'PIEZAS',
            PZ: 'PIEZAS',
            ACT: 'Actividad'
        };
        var up = code.toUpperCase();
        if (aliases[up]) return aliases[up];
        var map = CC.state.unidadesMedida || {};
        return String(map[up] || map[code] || '').trim();
    }

    /** Etiqueta visible: solo el nombre (Bobina, PIEZAS…). Sin metros ni código SAP. */
    function formatUnidadLabel(codigo, nombre) {
        var code = String(codigo || '').trim();
        var name = String(nombre || '').trim() || nombreUnidadMedida(code);
        if (!code && !name) return '';
        return name || code;
    }

    function unidadDe(cta) {
        if (!cta) return '';
        return formatUnidadLabel(cta.unidad, cta.unidadNombre);
    }

    function precioInfoHtml(info) {
        info = info || {};
        var precio = Number(info.precio);
        if (!isFinite(precio)) precio = 0;
        var mon = canonMoneda(info.moneda || 'MXN');
        var unidad = formatUnidadLabel(info.unidad, info.unidadNombre);
        var dec = info.decimals != null ? info.decimals : 2;
        var showZero = !!info.showZero;
        var hasPrice = showZero || precio > 0;
        var view = isUsdView() ? 'USD' : 'MXN';
        if (!hasPrice && !unidad) {
            return '<span class="cc-price-empty">—</span>';
        }
        var html = '';
        if (hasPrice) {
            html += '<div class="cc-price-amt">' + escapeHtml(moneyLista(precio, mon, null, dec)) + '</div>';
            // Si la vista difiere de la moneda nativa, aclarar origen (no confundir con la moneda de vista).
            if (mon && mon !== view) {
                var nativoTxt = (mon === 'USD' ? 'US$' : '$') +
                    Number(precio || 0).toLocaleString('es-MX', {
                        minimumFractionDigits: dec,
                        maximumFractionDigits: dec
                    });
                html += '<div class="cc-price-src" title="Moneda original del precio (SAP/maestro). El monto de arriba ya está en ' +
                    escapeHtml(view) + ' con el TC de la vista.">' +
                    escapeHtml('origen ' + mon + ' · nativo ' + nativoTxt) + '</div>';
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
        if (!CC.state.puedeEditarPreciosCaptura) {
            return '<div class="cc-price-readonly" title="Sin permiso para editar precios por mes">' +
                precioInfoHtml(info) + '</div>';
        }
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
                '<span>MXN ' + moneyVistaFijo(tot, 'MXN') + '</span>' +
                (usd ? '<span>USD ' + moneyVistaFijo(usd, 'USD') + '</span>' : '') +
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
        var precioLista = precioListaSapInfo(cta);
        var uomLabel = unidadDe(cta);
        var cells = '';
        for (var i = 0; i < 12; i++) {
            var shown = formatInputQty(cta.ppto[i]);
            var pastQty = pastQtyMes(cta, i);
            var lockedMes = mesBloqueadoBudget(i);
            var pCls = monthCellClass(cta, i).replace('cc-month-cell', 'cc-matrix-cell') + (lockedMes ? ' is-locked' : '');
            var pMes = precioUnitarioMes(cta, i);
            var pCustom = precioMesEsDistinto(cta, i);
            var mon = canonMoneda(cta.precioMoneda || 'MXN');
            var pastCap = pastQtyCaption();
            cells += '<td class="' + pCls + '">' +
                '<div class="cc-matrix-month">' +
                    '<div class="cc-month-past' + (pastQty ? '' : ' is-zero') + '" title="' +
                        escapeHtml(pastCap + ' · ' + MONTHS[i] + (uomLabel ? ' · ' + uomLabel : '') +
                            (pastQty ? ' · ' + qtyLabel(pastQty) + ' uds' : '')) + '">' +
                        (pastQty ? escapeHtml(qtyLabel(pastQty)) : '—') +
                    '</div>' +
                    '<input type="number" step="0.01" data-cta="' + escapeHtml(cta.codigo) + '" data-m="' + i + '" value="' + shown + '" ' +
                    ((control.locked || lockedMes) ? 'disabled' : '') + ' placeholder="0" class="cc-month-input' +
                    ((Number(shown) || 0) < 0 ? ' is-neg' : '') + (lockedMes ? ' is-locked' : '') + '" title="' +
                    escapeHtml(MONTHS[i] + (lockedMes ? ' · bloqueado (budget ' + labelTipoBudget() + ')' : '') +
                        ' · este cuadro = proyección (unidades): ' + (shown !== '' && shown != null ? qtyLabel(shown) : '0') +
                        (uomLabel ? ' ' + uomLabel : '') +
                        ' · ' + pastCap + ' (arriba): ' + (pastQty ? qtyLabel(pastQty) : '0') +
                        (uomLabel ? ' ' + uomLabel : '')) + '">' +
                    (pMes > 0
                        ? ('<div class="cc-month-price-tag' + (pCustom ? ' is-custom' : '') + '" title="' +
                            escapeHtml('Precio del mes: ' + moneyLista(pMes, mon, i) +
                                (pCustom ? ' · distinto del global' : ' · global / maestro') +
                                ' · TC ' + fxRate(i).toFixed(2)) + '">' +
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
                escapeHtml(precioLista.precio
                    ? (precioLista.title || 'Precio lista · Maestro local')
                    : (preciosListaCargando() ? 'Cargando precio lista del maestro…' : (precioLista.title || 'Sin precio de lista'))) + '">' +
                (precioLista.precio
                    ? precioInfoHtml(precioLista)
                    : (preciosListaCargando()
                        ? '<span class="cc-price-empty">Cargando...</span>'
                        : '<span class="cc-price-empty">—</span>')) +
            '</td>' +
            '<td class="num cc-sem-cell" data-uds-venta title="' +
                escapeHtml(gastoVentaCargando()
                    ? ('Consultando unidades de venta ' + anioPast + ' en SAP…')
                    : ('Total unidades vendidas en ' + anioPast +
                        ' (SAP · misma fuente que Ventas pasadas · TOT. UDS)' +
                        (uomLabel ? ' · ' + uomLabel : '') +
                        (udsSem ? '' : (udsVentaAnio ? ' · Ene–Jun: 0; venta en jul–Dic' : '')))) + '">' +
                (udsVentaAnio
                    ? ('<div class="cc-sem-qty">' + escapeHtml(qtyLabel(udsVentaAnio)) + '</div>' +
                        (uomLabel ? '<div class="cc-sem-uom">' + escapeHtml(uomLabel) + '</div>' : ''))
                    : (gastoVentaCargando()
                        ? '<span class="cc-price-empty">Cargando...</span>'
                        : '<span class="cc-price-empty">—</span>')) +
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
                var fake = {
                    gasto: cta.gasto,
                    ppto: (cta.ppto || []).slice(),
                    codigo: cta.codigo
                };
                fake.ppto[Number(inp.getAttribute('data-m'))] = raw === '' ? null : toStoreQty(Number(raw) || 0);
                var lockedMes = mesBloqueadoBudget(Number(inp.getAttribute('data-m')));
                td.className = monthCellClass(fake, Number(inp.getAttribute('data-m'))).replace('cc-month-cell', 'cc-matrix-cell') +
                    (lockedMes ? ' is-locked' : '');
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
            '<th class="num">Precio de lista<span class="cc-th-past">Maestro local</span></th>' +
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
                    : 'Usa Agregar producto, a la izquierda, para asignarlo y capturarlo aquí') +
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
                var lockedMesUpd = mesBloqueadoBudget(i);
                td.className = monthCellClass(cta, i).replace('cc-month-cell', 'cc-matrix-cell') +
                    (lockedMesUpd ? ' is-locked' : '');
                var wrap = td.querySelector('.cc-matrix-month');
                if (wrap) {
                    var tag = wrap.querySelector('.cc-month-price-tag');
                    var pMes = precioUnitarioMes(cta, i);
                    var pCustom = precioMesEsDistinto(cta, i);
                    var mon = canonMoneda(cta.precioMoneda || 'MXN');
                    if (pMes > 0) {
                        if (!tag) {
                            tag = document.createElement('div');
                            wrap.appendChild(tag);
                        }
                        tag.className = 'cc-month-price-tag' + (pCustom ? ' is-custom' : '');
                        tag.textContent = moneyLista(pMes, mon, i);
                        tag.title = 'Precio del mes: ' + moneyLista(pMes, mon, i) +
                            (pCustom ? ' · distinto del global' : ' · global / maestro') +
                            ' · TC ' + fxRate(i).toFixed(2);
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
        var past = pastQtyMes(cta, i);
        // Verde: proyección >= venta / proy. anterior.
        // Rojo: proyección < venta / proy. anterior.
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
        var conUsuarios = tbodyId === 'det-tbody';
        var colCount = conUsuarios ? 31 : 30;
        if (!c) {
            tbody.innerHTML = '<tr><td colspan="' + colCount + '"><div class="cc-empty">Elige un cliente para ver el detalle</div></td></tr>';
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
            (conUsuarios ? '<th>Usuarios</th>' : '') +
            '<th class="num">Precio<span class="cc-th-past">lista SAP</span></th>' +
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
                return a + importeVentaVista(x);
            }, 0);
            var gTotP = groups[g].reduce(function (a, x) { return a + importeProyeccionVista(x); }, 0);
            var closed = !!closedMap[g];
            if (!hideGroups) {
                html += '<tr class="cc-group-row" data-group="' + escapeHtml(g) + '"><td class="sticky-col" colspan="' + (conUsuarios ? 7 : 6) + '"><i class="fa-solid fa-chevron-' + (closed ? 'right' : 'down') + ' me-1"></i>' + escapeHtml(g) + ' · ' + groups[g].length + ' productos</td>';
                html += '<td class="num cc-group-totals" colspan="2">' +
                    '<span class="cc-group-tot"><em>Venta ' + CC.state.anioGasto + '</em> ' + moneyGasto(gTotG) + '</span>' +
                    '<span class="cc-group-tot-sep">→</span>' +
                    '<span class="cc-group-tot is-proy"><em>Proy. ' + CC.state.anioPresupuesto + '</em> ' + moneyGasto(gTotP) + '</span>' +
                    '</td><td colspan="22"></td></tr>';
            }
            if (!closed || hideGroups) {
                groups[g].forEach(function (cta) {
                    var impG = importeVentaVista(cta);
                    var impP = importeProyeccionVista(cta);
                    // Δ% en la misma moneda que se muestra (evita mezclar LineTotal MXN vs proy×TC).
                    var d = deltaPct(impP, impG);
                    var dCls = d > 15 ? 'text-danger' : (d < 0 ? 'text-success' : 'text-muted');
                    var selected = String(cta.codigo) === String(control.cuenta || '');
                    var editing = (!opts.readonly || opts.selectable) && selected;
                    html += '<tr class="cc-result-row' + (editing ? ' is-editing' : '') + (!ctaPendiente(cta) ? ' is-done' : '') + '" data-cta="' + escapeHtml(cta.codigo) + '"><td class="sticky-col">' + htmlNombreCodigo(cta.nombre, cta.codigo) + '</td>';
                    if (conUsuarios) html += '<td class="cc-col-users">' + htmlPilaUsuarios(usuariosDeProducto(cta.codigo), false) + '</td>';
                    // Mismo precio que la matriz (Precio lista · Maestro local).
                    var pLista = precioListaSapInfo(cta);
                    html += '<td class="num cc-price-cell" title="' +
                        escapeHtml(pLista.title || ('Precio lista · Maestro local (misma fuente que la matriz)')) + '">' +
                        precioInfoHtml(pLista) + '</td>';
                    html += '<td class="num" title="' + (isUsdView()
                        ? 'Suma de LineTotalUSD (SAP). No se convierte con el tipo de cambio.'
                        : 'Suma de LineTotal (SAP).') + '">' + moneyGasto(impG) + '</td>';
                    html += '<td class="num cc-price-cell" title="Precio de lista actual (proyección ' + CC.state.anioPresupuesto + ')">' + precioInfoHtml(precioProyeccionInfo(cta)) + '</td>';
                    html += '<td class="num fw-semibold">' + moneyGasto(impP) + '</td>';
                    html += '<td class="num ' + dCls + '" title="Variación de importe en la moneda de vista (VENTA vs PROY)">' +
                        (impP || !ctaPendiente(cta) ? ((d > 0 ? '+' : '') + d + '%') : '—') + '</td>';
                    for (var i = 0; i < 12; i++) {
                        var lleno = mesLleno(cta.ppto[i]);
                        var udsProy = lleno ? (Number(cta.ppto[i]) || 0) : 0;
                        var udsPast = pastQtyMes(cta, i);
                        var pCls = lleno
                            ? (udsPast > 0 && udsProy < udsPast ? 'is-over' : 'is-ok')
                            : 'is-empty';
                        var impMesG = importeMesVentaVista(cta, i);
                        var impMesP = importeMesProyVista(cta, i);
                        html += '<td class="num text-muted" style="font-size:.75rem" title="' +
                            escapeHtml((esBudgetTipo() && i >= 9
                                ? ('Ref. budget ' + MONTHS[i])
                                : ('Venta ' + CC.state.anioGasto)) +
                                (udsPast ? ' · ' + qtyLabel(udsPast) + ' uds' : '') +
                                (isUsdView() ? ' · USD' : ' · MXN')) + '">' +
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
            tbody.innerHTML = '<tr><td colspan="' + colCount + '"><div class="cc-empty">' + emptyMsg + '</div></td></tr>';
        } else {
            // Fila de totales por mes (todas las cuentas filtradas, aunque el grupo esté cerrado).
            var sumG = 0;
            var sumP = 0;
            var mesG = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            var mesP = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            ctas.forEach(function (cta) {
                sumG += importeVentaVista(cta);
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
                (conUsuarios ? '<td class="cc-col-users"></td>' : '') +
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
            if (conUsuarios) hideUserTip();
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
        var refreshVenta = document.getElementById('ctl-refresh-venta');
        if (refreshVenta) refreshVenta.addEventListener('click', function () {
            var c = control.centro;
            if (!c) {
                toast('warning', 'Cliente', 'Elige un cliente primero.');
                return;
            }
            var go = function () {
                loadGastoRealCentro(c, { force: true });
                loadVentasBudgetRefCentro(c);
            };
            if (window.Swal) {
                Swal.fire({
                    icon: 'question',
                    title: '¿Actualizar venta ' + (CC.state.anioGasto || '') + '?',
                    text: 'Se consultará SAP y se reemplazará el snapshot local de este cliente.',
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0a0a0a'
                }).then(function (r) { if (r.isConfirmed) go(); });
                return;
            }
            if (window.confirm('¿Actualizar venta ' + (CC.state.anioGasto || '') + ' desde SAP?')) go();
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
            setText('cc-page-title', 'VENTAS');
            if (sub) { sub.hidden = false; setText('cc-page-sub', 'Elige un cliente desde el visor.'); }
            if (facts) facts.hidden = true;
            return;
        }
        var stt = statsDeCentro(c);
        var dep = deptoDeCentro(c);
        var barCls = semaforoAvance(stt.avance);
        var codigoCli = String(c.codigo || '').trim();
        var nombreCli = nombreClienteDetalle(c);
        setText('cc-page-kicker', 'Detalle · ' + (c.empresa || '—') + (dep && dep !== '—' ? ' · ' + dep : '') + (codigoCli ? ' · ' + codigoCli : ''));
        var title = document.getElementById('cc-page-title');
        if (title) {
            var pieza = codigoCli;
            if (nombreCli) pieza = codigoCli ? (codigoCli + ' · ' + nombreCli) : nombreCli;
            title.innerHTML = '<span class="cc-title-kind">VENTAS</span> · ' + escapeHtml(pieza || 'Cliente');
        }
        if (sub) sub.hidden = true;
        if (facts) {
            facts.hidden = false;
            facts.innerHTML =
                factHtml('Empresa', escapeHtml(c.empresa || '—')) +
                factHtml('TOTAL VENTA', textoImporteVisor(totalVentaAnioCliente(c))) +
                factHtml('TOTAL PROYECTADO', textoImporteVisor(totalProyectadoCliente(c))) +
                factHtml('Usuario', htmlPilaUsuarios(usuariosCapturaDetalle(), true)) +
                factHtml('Fecha modif', escapeHtml(c.fecha || '—')) +
                factHtml('Progreso', '<span class="cc-semaforo-' + barCls + '">' + stt.avance + '%</span>',
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

    function gastoRealListo(c) {
        if (!c) return false;
        var key = gastoCacheKey(c);
        return !!(CC.state.gastoCache && Object.prototype.hasOwnProperty.call(CC.state.gastoCache, key));
    }

    /** Importe de lo vendido el año de referencia (SAP), todos los productos del cliente. */
    function totalVentaAnioCliente(c) {
        if (!gastoRealListo(c)) return null;
        var por = (CC.state.gastoCache && CC.state.gastoCache[gastoCacheKey(c)]) || {};
        var seen = {};
        var total = 0;
        Object.keys(por).forEach(function (k) {
            var row = por[k];
            if (!row || typeof row !== 'object') return;
            var codigo = String(row.codigo || k || '').trim();
            if (!codigo || seen[codigo]) return;
            seen[codigo] = true;
            total += importeVentaVista({
                importe: row.importe,
                importeUsd: row.importe_usd,
                importe_usd: row.importe_usd,
                gasto: row.gasto,
                precioLista: 0,
                precioMoneda: 'MXN'
            });
        });
        return total;
    }

    /** Importe de la proyección que ya se capturó en el ciclo. */
    function totalProyectadoCliente(c) {
        var s = statsDeCentro(c);
        return s.totPVista != null ? s.totPVista : (s.totP || 0);
    }

    function textoImporteVisor(n) {
        if (n == null) return '…';
        return moneyGasto(n);
    }

    function paintVisorLabels() {
        var g = CC.state.anioGasto || '';
        var p = CC.state.anioPresupuesto || '';
        setText('visor-kpi-gasto-lbl', 'Total venta' + (g ? ' ' + g : ''));
        setText('visor-kpi-ppto-lbl', 'Total proyectado' + (p ? ' ' + p : ''));
        setText('visor-th-venta', 'Total venta' + (g ? ' ' + g : ''));
        setText('visor-th-proy', 'Total proyectado' + (p ? ' ' + p : ''));
    }

    var visorGastoInflight = {};

    function queueVisorGastos() {
        if (!CC.state.gastoUrl || CC.state.page !== 'control') return;
        var year = CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || 2026;
        var pending = (CC.state.centros || []).filter(function (c) {
            var key = gastoCacheKey(c);
            if (gastoRealListo(c) || visorGastoInflight[key]) return false;
            return true;
        });
        if (!pending.length) return;
        var inflight = 0;
        var max = 3;
        var dirty = false;
        var paintTimer = null;
        function schedulePaint() {
            if (paintTimer) return;
            paintTimer = setTimeout(function () {
                paintTimer = null;
                if (CC.state.page === 'control' && dirty) renderVisorTable();
            }, 350);
        }
        function kick() {
            while (inflight < max && pending.length) {
                var c = pending.shift();
                var key = gastoCacheKey(c);
                if (visorGastoInflight[key]) continue;
                if (control.centro && gastoCacheKey(control.centro) === key && control._gastoLoading === key) continue;
                if (CC.state.gastoCache && Object.prototype.hasOwnProperty.call(CC.state.gastoCache, key)) continue;
                visorGastoInflight[key] = true;
                inflight += 1;
                (function (centro, cacheKey, yearFrozen) {
                    fetch(CC.state.gastoUrl + '?empresa=' + encodeURIComponent(centro.empresa || '') +
                        '&cc=' + encodeURIComponent(String(centro.codigo || '').trim()) +
                        '&year=' + encodeURIComponent(yearFrozen), {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function (res) { return res.json(); }).then(function (json) {
                        // No pisar la venta SAP del cliente abierto en Captura.
                        if (control.centro && gastoCacheKey(control.centro) === cacheKey && control._gastoLoading === cacheKey) {
                            return;
                        }
                        CC.state.gastoCache = CC.state.gastoCache || {};
                        CC.state.gastoCache[cacheKey] = (json && json.por_cuenta) || {};
                        CC.state.gastoLookup = CC.state.gastoLookup || {};
                        CC.state.gastoLookup[cacheKey] = mapFromPorCuenta(CC.state.gastoCache[cacheKey]);
                        dirty = true;
                        schedulePaint();
                    }).catch(function () {
                        if (control.centro && gastoCacheKey(control.centro) === cacheKey) return;
                        CC.state.gastoCache = CC.state.gastoCache || {};
                        CC.state.gastoCache[cacheKey] = {};
                        dirty = true;
                    }).then(function () {
                        delete visorGastoInflight[cacheKey];
                        inflight -= 1;
                        if (!pending.length && !inflight && dirty) renderVisorTable();
                        else kick();
                    });
                })(c, key, year);
            }
        }
        kick();
    }

    function visorTotales(rows) {
        var totG = 0, totP = 0, capturadas = 0, totalCtas = 0, listos = 0, ventaPend = 0;
        (rows || []).forEach(function (c) {
            var s = statsDeCentro(c);
            var venta = totalVentaAnioCliente(c);
            if (venta == null) ventaPend += 1;
            else totG += venta;
            totP += totalProyectadoCliente(c);
            capturadas += s.capturadas;
            totalCtas += s.total;
            if (!s.pendientes) listos++;
        });
        var n = (rows || []).length;
        return {
            n: n,
            totG: totG,
            totP: totP,
            ventaPend: ventaPend,
            capturadas: capturadas,
            totalCtas: totalCtas,
            listos: listos,
            pendientes: n - listos,
            avance: pct(capturadas, totalCtas || 1)
        };
    }

    function textoTotalVentaVisor(tot) {
        if (!tot || !tot.n) return '—';
        if (tot.ventaPend && tot.ventaPend === tot.n) return '…';
        var txt = moneyGasto(tot.totG);
        return tot.ventaPend ? (txt === '—' ? '…' : txt + '…') : txt;
    }

    function paintVisorResumen(tot) {
        paintVisorLabels();
        setText('visor-avance', tot.n ? (tot.avance + '%') : '—');
        setText('visor-avance-meta', tot.n
            ? (tot.listos + ' de ' + tot.n + ' clientes listos · ' + tot.capturadas + ' de ' + tot.totalCtas + ' productos capturados')
            : 'No hay clientes para sumar');
        if (tot.n) paintBarraProgreso('visor-avance-wrap', 'visor-avance-bar', tot.avance, 'visor-avance');
        else {
            var bar0 = document.getElementById('visor-avance-bar');
            var wrap0 = document.getElementById('visor-avance-wrap');
            if (bar0) bar0.style.width = '0%';
            if (wrap0) wrap0.className = 'cc-progress';
            var label0 = document.getElementById('visor-avance');
            if (label0) label0.className = '';
        }
        setText('visor-kpi-n', tot.n || '0');
        setText('visor-kpi-gasto', textoTotalVentaVisor(tot));
        setText('visor-kpi-ppto', tot.n ? moneyGasto(tot.totP) : '—');
        setText('visor-kpi-pend', tot.pendientes);
        var tf = document.getElementById('visor-tfoot');
        if (!tf) return;
        if (!tot.n) {
            tf.innerHTML = '';
            return;
        }
        tf.innerHTML = '<tr>' +
            '<td colspan="3">Totales · ' + tot.n + (tot.n === 1 ? ' cliente' : ' clientes') + '</td>' +
            '<td class="num">' + textoTotalVentaVisor(tot) + '</td>' +
            '<td class="num">' + moneyGasto(tot.totP) + '</td>' +
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
        queueVisorGastos();
        if (!rows.length) {
            tb.innerHTML = '<tr><td colspan="9"><div class="cc-empty"><i class="fa-solid fa-inbox"></i>No hay clientes con esos filtros</div></td></tr>';
            return;
        }
        var anioVenta = CC.state.anioGasto || '';
        tb.innerHTML = rows.map(function (c) {
            var stt = statsDeCentro(c);
            var barCls = semaforoAvance(stt.avance);
            var fecha = c.fecha || '—';
            var venta = totalVentaAnioCliente(c);
            var proy = totalProyectadoCliente(c);
            return '<tr data-key="' + escapeHtml(centroKey(c)) + '">' +
                '<td><a class="cc-btn cc-btn-detalle" href="' + detalleHref(c) + '"><i class="fa-solid fa-eye"></i> Detalle</a></td>' +
                '<td>' + escapeHtml(c.empresa || '—') + '</td>' +
                '<td>' + htmlNombreCodigo(c.nombre, c.codigo) + '</td>' +
                '<td class="num" title="' + escapeHtml('Vendido en ' + anioVenta) + '">' + textoImporteVisor(venta) + '</td>' +
                '<td class="num" title="Proyección capturada">' + textoImporteVisor(proy) + '</td>' +
                '<td>' + userCell(c.usuario) + '</td>' +
                '<td>' + escapeHtml(fecha) + '</td>' +
                '<td><div class="cc-visor-avance"><div class="meta"><span>' + stt.capturadas + '/' + stt.total + '</span><strong class="cc-semaforo-' + barCls + '">' + stt.avance + '%</strong></div>' +
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

    var catalogoEmpresaCache = {};

    function miAsignacionCliente() {
        var c = control.centro;
        if (!c) return null;
        var list = asigsDe(c).filter(function (a) { return a && a.id; });
        if (!list.length) return null;
        return list.filter(function (a) { return a.es_principal; })[0] || list[0];
    }

    function productoYaAsignado(codigo) {
        var c = control.centro;
        var asig = c ? asigDe(c) : null;
        var key = String(codigo || '').toUpperCase();
        if (!key || !asig) return false;
        return (asig.cuentas || []).some(function (x) {
            return String(x.codigo || '').toUpperCase() === key;
        });
    }

    function cargarCatalogoEmpresa(done) {
        var c = control.centro;
        if (!c) {
            done([]);
            return;
        }
        var year = CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || new Date().getFullYear();
        var key = String(c.empresa || '').toLowerCase() + '|' + year;
        if (catalogoEmpresaCache[key]) {
            done(catalogoEmpresaCache[key]);
            return;
        }
        var url = '/ProyeccionesVentas/api/cuentas?empresa=' + encodeURIComponent(c.empresa) +
            '&year=' + encodeURIComponent(year) + '&todas=1';
        fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var rows = (json && json.cuentas) || [];
                if (json && json.ok && rows.length) catalogoEmpresaCache[key] = rows;
                done(rows, json && json.mensaje);
            })
            .catch(function () { done([], 'No se pudo cargar el catálogo'); });
    }

    function pintarResultadosAgregar(q, rows, mensaje) {
        var box = document.getElementById('ctl-add-list');
        if (!box) return;
        q = normSearch(q || '');
        var pending = (rows || []).filter(function (p) { return !productoYaAsignado(p.codigo); });
        var hits = q
            ? pending.filter(function (p) {
                return normSearch((p.codigo || '') + ' ' + (p.nombre || '') + ' ' + (p.grupo || '')).indexOf(q) !== -1;
            })
            : pending;
        var limit = q ? 40 : 30;
        var shown = hits.slice(0, limit);
        if (!shown.length) {
            box.innerHTML = '<div class="cc-add-prod-empty">' + escapeHtml(mensaje || (q
                ? 'Sin productos con ese texto, o ya están en el cliente.'
                : 'Este catálogo no tiene productos para agregar.')) + '</div>';
            return;
        }
        var more = '';
        if (hits.length > shown.length) {
            more = '<div class="cc-add-prod-empty">' + (q
                ? ('Mostrando ' + shown.length + ' de ' + hits.length + '. Sigue escribiendo para acotar.')
                : ('Mostrando ' + shown.length + ' de ' + hits.length + '. Usa el buscador para encontrar el producto.')) + '</div>';
        }
        box.innerHTML = shown.map(function (p) {
            return '<button type="button" class="cc-add-prod-item" data-add-codigo="' + escapeHtml(p.codigo) + '">' +
                '<span class="cc-cta-name">' + escapeHtml(p.nombre || p.codigo) + '</span>' +
                '<span class="cc-cta-code">' + escapeHtml(p.codigo) + '</span>' +
                '<span class="cc-add-prod-go">Agregar</span></button>';
        }).join('') + more;
    }

    function buscarCatalogoParaAgregar(q) {
        var box = document.getElementById('ctl-add-list');
        if (box && !catalogoEmpresaCache[catalogoKeyActual()]) {
            box.innerHTML = '<div class="cc-add-prod-empty">Cargando productos…</div>';
        }
        cargarCatalogoEmpresa(function (rows, mensaje) {
            var input = document.getElementById('ctl-add-q');
            if (!input || normSearch(input.value) !== normSearch(q)) return;
            pintarResultadosAgregar(q, rows, mensaje);
        });
    }

    function catalogoKeyActual() {
        var c = control.centro;
        if (!c) return '';
        var year = CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || new Date().getFullYear();
        return String(c.empresa || '').toLowerCase() + '|' + year;
    }

    function abrirModalAgregarProducto() {
        if (!control.centro || control.locked) {
            toast('warning', 'Elige un cliente', 'Selecciona empresa y cliente para agregar productos.');
            return;
        }
        var sub = document.getElementById('ctl-add-sub');
        if (sub) {
            sub.textContent = 'Catálogo de ' + String(control.centro.empresa || 'la empresa') +
                ', aunque no se le haya vendido a este cliente.';
        }
        var input = document.getElementById('ctl-add-q');
        if (input) input.value = '';
        var box = document.getElementById('ctl-add-list');
        if (box) box.innerHTML = '<div class="cc-add-prod-empty">Cargando productos…</div>';
        showModal('modalAgregarProducto');
        cargarCatalogoEmpresa(function (rows, mensaje) {
            pintarResultadosAgregar('', rows, mensaje);
            if (input) input.focus();
        });
    }

    function enfocarProductoAgregado(codigo) {
        control.prodQuery = '';
        var filtro = document.getElementById('ctl-prod-q');
        if (filtro) filtro.value = '';
        var addQ = document.getElementById('ctl-add-q');
        if (addQ) addQ.value = '';
        hideModal('modalAgregarProducto');
        control.cuenta = codigo;
        loadControlCentro();
        selectCuenta(codigo);
        var row = document.querySelector('#ctl-matrix-tbody tr.cc-matrix-row[data-cta="' + cssEscape(codigo) + '"]');
        if (row && row.scrollIntoView) row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        var first = row && row.querySelector('input[data-m]:not([disabled])');
        if (first && !first.disabled) first.focus();
    }

    function agregarProductoAlCliente(codigo) {
        var c = control.centro;
        var asig = miAsignacionCliente();
        if (!c || !asig || control.locked) {
            toast('warning', 'No se puede agregar', 'Este cliente no está abierto para captura.');
            return;
        }
        var year = CC.state.anioGasto || (CC.state.period && CC.state.period.anioReferencia) || new Date().getFullYear();
        var cacheKey = String(c.empresa || '').toLowerCase() + '|' + year;
        var prod = (catalogoEmpresaCache[cacheKey] || []).filter(function (p) {
            return String(p.codigo) === String(codigo);
        })[0];
        if (!prod) {
            toast('warning', 'Producto', 'No se encontró el producto en el catálogo.');
            return;
        }
        if (productoYaAsignado(prod.codigo)) {
            enfocarProductoAgregado(prod.codigo);
            toast('warning', 'Ya está en el cliente', prod.nombre || prod.codigo);
            return;
        }
        var cuentas = (asig.cuentas || []).map(function (x) {
            return {
                codigo: x.codigo,
                nombre: x.nombre || '',
                agrupacion: x.agrupacion || x.grupo || ''
            };
        });
        cuentas.push({
            codigo: prod.codigo,
            nombre: prod.nombre || prod.codigo,
            agrupacion: prod.grupo || ''
        });
        var ciclo = cicloActualCodigo();
        var box = document.getElementById('ctl-add-list');
        if (box) box.innerHTML = '<div class="cc-add-prod-empty">Agregando ' + escapeHtml(prod.nombre || prod.codigo) + '…</div>';
        fetch('/Ventas/Asignaciones/' + encodeURIComponent(ciclo) + '/asignaciones/' + encodeURIComponent(asig.id), {
            method: 'PUT',
            headers: apiJsonHeaders(),
            body: JSON.stringify({ cuentas: cuentas })
        }).then(function (r) {
            return r.json().then(function (json) { return { ok: r.ok, json: json }; });
        }).then(function (res) {
            if (!res.ok || !res.json || !res.json.asignacion) {
                throw new Error((res.json && res.json.message) || 'No se pudo asignar el producto');
            }
            var saved = res.json.asignacion;
            CC.state.misAsignaciones = (CC.state.misAsignaciones || []).map(function (a) {
                return String(a.id) === String(saved.id) ? saved : a;
            });
            enfocarProductoAgregado(prod.codigo);
            toast('success', 'Producto agregado', (prod.nombre || prod.codigo) + ' ya se puede capturar');
        }).catch(function (err) {
            var input = document.getElementById('ctl-add-q');
            buscarCatalogoParaAgregar(input ? input.value : '');
            toast('error', 'No se agregó', err && err.message ? err.message : 'Error de red');
        });
    }

    function bindAgregarProducto() {
        var openBtn = document.getElementById('ctl-add-btn');
        var input = document.getElementById('ctl-add-q');
        var list = document.getElementById('ctl-add-list');
        if (!openBtn || openBtn._addBound) return;
        openBtn._addBound = true;
        openBtn.addEventListener('click', function () {
            if (openBtn.disabled) return;
            abrirModalAgregarProducto();
        });
        if (input) {
            var timer = null;
            input.addEventListener('input', function () {
                clearTimeout(timer);
                var q = input.value;
                timer = setTimeout(function () { buscarCatalogoParaAgregar(q); }, 220);
            });
        }
        if (list) {
            list.addEventListener('click', function (ev) {
                var btn = ev.target.closest ? ev.target.closest('[data-add-codigo]') : null;
                if (!btn) return;
                agregarProductoAlCliente(btn.getAttribute('data-add-codigo'));
            });
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
                    syncBudgetTools();
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
                    syncBudgetTools();
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
        // Importes en moneda de vista (igual que totales / sticky), no unidades.
        var g = MONTHS.map(function (_, i) {
            return ctas.reduce(function (a, x) {
                return a + (Number(importeMesVentaVista(x, i)) || 0);
            }, 0);
        });
        var p = MONTHS.map(function (_, i) {
            return ctas.reduce(function (a, x) {
                var mp = importeMesProyVista(x, i);
                return a + (mp != null ? (Number(mp) || 0) : 0);
            }, 0);
        });
        var d = g.map(function (gv, i) { return deltaPct(p[i], gv); });
        if (CC.state.charts[chartKey]) CC.state.charts[chartKey].destroy();
        CC.state.charts[chartKey] = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: MONTHS,
                datasets: [
                    { type: 'bar', label: 'Ventas ' + CC.state.anioGasto, data: g, backgroundColor: '#0a0a0a', borderRadius: 4, yAxisID: 'y' },
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
                                // Valores ya en moneda de vista: no volver a aplicar TC.
                                return ctx.dataset.label + ': ' + moneyVista(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        ticks: {
                            callback: function (v) {
                                return moneyVista(v);
                            }
                        },
                        grid: { color: '#f1f1f3' }
                    },
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
        if (months) {
            var cells = MONTHS.map(function (m) { return '<span>' + m + '</span>'; }).join('');
            months.innerHTML = months.classList.contains('is-labeled')
                ? '<span></span><span class="cc-heat-months-cells">' + cells + '</span>'
                : cells;
        }
    }

    function clienteFilterKey(c) {
        return String((c && c.empresa) || '').toUpperCase().trim() + '|' + String((c && c.codigo) || '').trim();
    }

    function fillSelectKeepEscaped(el, items, extra) {
        if (!el) return false;
        var prev = el.value;
        var html = extra || '';
        (items || []).forEach(function (it) {
            if (typeof it === 'string') {
                html += '<option value="' + escapeHtml(it) + '">' + escapeHtml(it) + '</option>';
                return;
            }
            html += '<option value="' + escapeHtml(it.codigo) + '">' + escapeHtml(it.nombre) + '</option>';
        });
        var changed = el.innerHTML !== html;
        if (changed) el.innerHTML = html;
        if (prev) el.value = prev;
        return changed;
    }

    function analisisSelectSearchReady() {
        return !!(window.jQuery && jQuery.fn && jQuery.fn.select2);
    }

    function analisisSelectSearchOpts() {
        return {
            theme: 'bootstrap-5',
            width: '100%',
            minimumResultsForSearch: 0,
            dropdownCssClass: 'cc-select-search-drop',
            dropdownParent: window.jQuery ? jQuery(document.body) : undefined,
            language: {
                noResults: function () { return 'Sin coincidencias'; },
                searching: function () { return 'Buscando…'; }
            },
            matcher: function (params, data) {
                if (data.id == null || data.id === undefined) return null;
                if (!params.term || !String(params.term).trim()) return data;
                var q = normSearch(params.term);
                var t = normSearch((data.text || '') + ' ' + (data.id || ''));
                return t.indexOf(q) !== -1 ? data : null;
            }
        };
    }

    function syncAnalisisSelectSearch(el, optionsChanged) {
        if (!el || !analisisSelectSearchReady()) return;
        var $el = jQuery(el);
        var val = el.value;
        CC._anFillingFilters = true;
        try {
            if (!$el.hasClass('select2-hidden-accessible') || optionsChanged) {
                if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
                $el.select2(analisisSelectSearchOpts());
            }
            $el.val(val).trigger('change.select2');
        } finally {
            CC._anFillingFilters = false;
        }
    }

    var AN_EMP_TODAS = '*';

    function empresaAnalisisConcreta(raw) {
        var emp = raw == null ? val('an-empresa') : raw;
        if (!emp || emp === AN_EMP_TODAS) return '';
        return emp;
    }

    function fillAnalisisFilters() {
        var rows = (CC.state.centros || []).map(mergedCentro);
        var empEl = document.getElementById('an-empresa');
        var cliEl = document.getElementById('an-cliente');
        var userEl = document.getElementById('an-user');
        var emp = empEl ? empEl.value : '';
        var cli = cliEl ? cliEl.value : '';
        var user = userEl ? userEl.value : '';

        var emps = [];
        rows.forEach(function (c) {
            if (c.empresa && emps.indexOf(c.empresa) === -1) emps.push(c.empresa);
        });
        emps.sort();
        if (emp && emp !== AN_EMP_TODAS && emps.indexOf(emp) === -1) emp = '';
        var empConcreta = empresaAnalisisConcreta(emp);
        var empresaElegida = emp === AN_EMP_TODAS || !!empConcreta;

        var scoped = rows.filter(function (c) {
            return !empConcreta || c.empresa === empConcreta;
        });
        if (cli && !scoped.some(function (c) { return clienteFilterKey(c) === cli; })) cli = '';

        var forUsers = scoped.filter(function (c) {
            return !cli || clienteFilterKey(c) === cli;
        });
        var users = [];
        forUsers.forEach(function (c) {
            var names = (c.usuarios && c.usuarios.length) ? c.usuarios : (c.usuario ? [c.usuario] : []);
            names.forEach(function (n) {
                if (n && users.indexOf(n) === -1) users.push(n);
            });
        });
        users.sort();
        if (user && users.indexOf(user) === -1) user = '';

        var forClients = scoped.filter(function (c) {
            if (!user) return true;
            return (c.usuarios || []).indexOf(user) !== -1 || c.usuario === user;
        });
        var seenCli = {};
        var clients = [];
        forClients.forEach(function (c) {
            var key = clienteFilterKey(c);
            if (!key || seenCli[key]) return;
            seenCli[key] = true;
            var label = (c.nombre && c.nombre !== c.codigo)
                ? (c.nombre + ' — ' + c.codigo)
                : (c.codigo || c.nombre || '—');
            if (!empConcreta && c.empresa) label = c.empresa + ' · ' + label;
            clients.push({ codigo: key, nombre: label });
        });
        clients.sort(function (a, b) {
            return String(a.nombre).localeCompare(String(b.nombre), 'es');
        });
        if (!empresaElegida) {
            clients = [];
            users = [];
            cli = '';
            user = '';
        }

        var empChanged = fillSelectKeepEscaped(empEl, emps, '<option value="">Seleccionar</option><option value="' + AN_EMP_TODAS + '">Todas</option>');
        var cliChanged = fillSelectKeepEscaped(cliEl, clients, '<option value="">' + (empresaElegida ? 'Todos los clientes' : 'Seleccionar') + '</option>');
        var userChanged = fillSelectKeepEscaped(userEl, users.map(function (n) {
            return { codigo: n, nombre: n };
        }), '<option value="">' + (empresaElegida ? 'Todos los usuarios' : 'Seleccionar') + '</option>');
        if (empEl) empEl.value = emp;
        if (cliEl) cliEl.value = cli;
        if (userEl) userEl.value = user;
        syncAnalisisSelectSearch(empEl, empChanged);
        syncAnalisisSelectSearch(cliEl, cliChanged);
        syncAnalisisSelectSearch(userEl, userChanged);
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
        CC._anGastoTried = {};
        CC._anVentasBudget = {};
        CC._anVentasBudgetTried = {};
        CC._anGastoGen = (CC._anGastoGen || 0) + 1;
        CC._anGastoRunning = false;
        CC._anGastoLeft = 0;
        CC._anGastoTotal = 0;
        CC._anGastoPhase = '';
        CC._anGastoFilterKey = '';
        CC.initAnalisis();
    }

    function bindAnalisisCurrency() {
        var mon = document.getElementById('ctl-moneda');
        if (mon) mon.value = CC.state.currency || 'MXN';
        paintFxBadge();
        if (CC._anMonedaBound) return;
        CC._anMonedaBound = true;
        if (!mon) return;
        mon.addEventListener('change', function () {
            CC.state.currency = this.value || 'MXN';
            saveJSON(SK.currency, CC.state.currency);
            paintFxBadge();
            renderAnalisis();
        });
    }

    CC.initAnalisis = function () {
        bindAnalisisCurrency();
        renderAnalisisCiclos();
        paintAnalisisYears();
        if (!CC._analisisBound) {
            ['an-q', 'an-chart-producto'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.addEventListener('input', renderAnalisis);
                if (el && el.tagName === 'SELECT') el.addEventListener('change', renderAnalisis);
            });
            ['an-empresa', 'an-cliente', 'an-user'].forEach(function (id) {
                var el = document.getElementById(id);
                if (!el) return;
                el.addEventListener('change', function () {
                    kickAnalisisVista();
                });
            });
            if (window.jQuery) {
                jQuery('#an-empresa, #an-cliente, #an-user').on('change.anKick', function () {
                    kickAnalisisVista();
                });
            }
            var heatQ = document.getElementById('an-heat-q');
            if (heatQ) heatQ.addEventListener('input', applyHeatFilter);
            var cicloEl = document.getElementById('an-ciclo');
            if (cicloEl) cicloEl.addEventListener('change', function () {
                applyAnalisisCiclo(this.value);
            });
            var empBox = document.getElementById('an-empresas');
            if (empBox) {
                empBox.addEventListener('click', function (e) {
                    var hit = e.target.closest('[data-emp]');
                    if (!hit) return;
                    toggleAnalisisEmpresa(hit.getAttribute('data-emp') || '');
                });
            }
            var heatBox = document.getElementById('an-heat');
            if (heatBox) {
                heatBox.addEventListener('click', function (e) {
                    var hit = e.target.closest('[data-emp]');
                    if (!hit) return;
                    toggleAnalisisEmpresa(hit.getAttribute('data-emp') || '');
                });
            }
            CC._analisisBound = true;
        }

        var ciclo = val('an-ciclo') || cicloAnalisisPreferido();
        var cicloEl = document.getElementById('an-ciclo');
        if (cicloEl && ciclo) cicloEl.value = ciclo;
        CC.state.cicloCodigo = ciclo || CC.state.cicloCodigo || '';
        var cicloKey = String(CC.state.cicloCodigo || '');
        if (CC._anCapturaCiclo !== cicloKey) {
            CC._anCapturaCiclo = cicloKey;
            paintAnalisisLoading();
            loadOverlaysForCycle().then(function () {
                if (CC.state.page !== 'analisis') return;
                if (String(CC.state.cicloCodigo || '') !== cicloKey) return;
                if (CC._anHoldBusy && analisisTieneFiltro()) {
                    maybeQueueAnalisisGastos();
                    return;
                }
                renderAnalisis();
                maybeQueueAnalisisGastos();
            });
        }

        paintAnalisisLoading();
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
            CC._anAsigGen = (CC._anAsigGen || 0) + 1;
            var asigGen = CC._anAsigGen;
            CC._anAsigLoading = true;
            fetchAnalisisAsignaciones(ciclo).then(function (list) {
                if (asigGen !== CC._anAsigGen) return;
                CC._anAsigLoading = false;
                if (String(val('an-ciclo') || CC.state.cicloCodigo || '').toUpperCase() !== String(ciclo).toUpperCase()) return;
                CC.state.misAsignaciones = list || [];
                CC.state.centros = centrosAgrupadosDesdeAsignaciones(asignacionesDelCiclo(ciclo));
                CC._anAsigLoaded = true;
                fillAnalisisFilters();
                if (analisisTieneFiltro()) {
                    maybeQueueAnalisisGastos();
                    return;
                }
                renderAnalisis();
                maybeQueueAnalisisGastos();
            });
            return;
        }

        CC.state.centros = centrosAgrupadosDesdeAsignaciones(asignacionesDelCiclo(ciclo));
        fillAnalisisFilters();
        if (CC._anHoldBusy && analisisTieneFiltro()) {
            maybeQueueAnalisisGastos();
            return;
        }
        renderAnalisis();
        maybeQueueAnalisisGastos();
    };

    function fetchJsonOk(url) {
        return fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) {
            if (!r.ok) throw new Error(String(r.status));
            return r.json();
        });
    }

    function asigPuedeRevisar(a) {
        return !!(a && (a.revisar || ((a.permisos || []).indexOf('revisar') !== -1)));
    }

    /** Empresas (y clientes, si la revisión no es de toda la empresa) que el usuario actual puede revisar. */
    function alcanceRevisionPropio(list) {
        var uid = Number(CC.state.usuarioActualId || 0);
        var scope = {};
        (list || []).forEach(function (a) {
            if (!uid || Number(a.user_id) !== uid) return;
            if (!asigPuedeRevisar(a)) return;
            var e = String(a.empresa || '').toUpperCase().trim();
            if (!e) return;
            if (!scope[e]) scope[e] = { wide: false, clientes: {} };
            var code = codigoClienteAsig(a);
            if (isEmpresaCompleta(code)) scope[e].wide = true;
            else if (code) scope[e].clientes[code] = true;
        });
        return scope;
    }

    function asignacionesVisiblesAnalisis(list) {
        var scope = alcanceRevisionPropio(list);
        CC._anScopeRevision = scope;
        CC._anSinRevision = !!(list && list.length) && !Object.keys(scope).length;
        return (list || []).filter(function (a) {
            var e = String(a.empresa || '').toUpperCase().trim();
            var rule = scope[e];
            if (!rule) return false;
            var code = codigoClienteAsig(a);
            if (!code || isEmpresaCompleta(code)) return false;
            if (rule.wide) return true;
            return !!rule.clientes[code];
        });
    }

    function fetchAnalisisAsignaciones(ciclo) {
        var todas = '/Ventas/Asignaciones/' + encodeURIComponent(ciclo) + '/asignaciones';
        var mias = '/ProyeccionesVentas/api/mis-asignaciones';
        return fetchJsonOk(todas).then(function (json) {
            var list = (json && json.asignaciones) || [];
            if (list.length) return asignacionesVisiblesAnalisis(list);
            return fetchJsonOk(mias).then(function (mine) {
                return asignacionesVisiblesAnalisis((mine && mine.asignaciones) || []);
            }).catch(function () { return asignacionesVisiblesAnalisis([]); });
        }).catch(function () {
            return fetchJsonOk(mias).then(function (mine) {
                return asignacionesVisiblesAnalisis((mine && mine.asignaciones) || []);
            }).catch(function () { return asignacionesVisiblesAnalisis(CC.state.misAsignaciones || []); });
        });
    }

    function analisisPageEl() {
        return document.querySelector('.cc-page');
    }

    function showAnalisisBusy() {
        CC._anHoldBusy = true;
        var page = analisisPageEl();
        if (page) page.classList.add('is-an-busy');
        var busy = document.getElementById('an-busy');
        if (busy) busy.setAttribute('aria-hidden', 'false');
        var empty = document.getElementById('an-empty');
        if (empty) empty.hidden = true;
    }

    function hideAnalisisBusy() {
        CC._anHoldBusy = false;
        var page = analisisPageEl();
        if (page) page.classList.remove('is-an-busy');
        var busy = document.getElementById('an-busy');
        if (busy) busy.setAttribute('aria-hidden', 'true');
    }

    function afterAnalisisPaint(fn) {
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                setTimeout(fn, 90);
            });
        });
    }

    function paintAnalisisLoading() {
        var loading = document.getElementById('an-loading');
        if (loading) loading.hidden = true;
        if (CC._anHoldBusy && analisisTieneFiltro()) showAnalisisBusy();
        else hideAnalisisBusy();
    }

    function paintAnalisisSkeleton() {
        var empty = document.getElementById('an-empty');
        var work = document.getElementById('an-work');
        if (empty) empty.hidden = true;
        if (work) work.hidden = false;
        setLoading('an-kpi-avance');
        setLoading('an-kpi-poco');
        setLoading('an-kpi-over');
        setLoading('an-kpi-yoy');
        var empBox = document.getElementById('an-empresas');
        if (empBox) empBox.innerHTML = '<div class="cc-busy">' + htmlCargando('lg') + '</div>';
        var tb = document.getElementById('an-tbody');
        if (tb) tb.innerHTML = '<tr><td colspan="10"><div class="cc-busy">' + htmlCargando('lg') + '</div></td></tr>';
        var tf = document.getElementById('an-tfoot');
        if (tf) tf.innerHTML = '';
        var heat = document.getElementById('an-heat');
        if (heat) heat.innerHTML = '<div class="cc-busy">' + htmlCargando('lg') + '</div>';
    }

    function kickAnalisisVista() {
        if (CC._anFillingFilters) return;
        CC._anKickSeq = (CC._anKickSeq || 0) + 1;
        var seq = CC._anKickSeq;
        if (!analisisTieneFiltro()) {
            hideAnalisisBusy();
            afterAnalisisPaint(function () {
                if (seq !== CC._anKickSeq || CC.state.page !== 'analisis' || analisisTieneFiltro()) return;
                renderAnalisis();
            });
            return;
        }
        if (filtroAnalisisEnCache()) {
            hideAnalisisBusy();
            renderAnalisis();
            return;
        }
        showAnalisisBusy();
        afterAnalisisPaint(function () {
            if (seq !== CC._anKickSeq || CC.state.page !== 'analisis' || !analisisTieneFiltro()) return;
            maybeQueueAnalisisGastos();
        });
    }

    function filtroAnalisisEnCache() {
        if (!analisisTieneFiltro()) return false;
        var centros = centrosObjetivoAnalisis();
        if (!centros.length) return false;
        return centros.every(function (c) {
            return !!gastoCacheEntry(gastoCacheKey(c));
        });
    }

    function soltarAnalisisBusy() {
        hideAnalisisBusy();
        if (CC.state.page !== 'analisis') return;
        CC._anLightPaint = false;
        renderAnalisis();
    }

    function ventaCentroLista(c) {
        var entry = gastoCacheEntry(gastoCacheKey(c));
        if (!entry || !entry.por) return false;
        return Object.keys(entry.por).length > 0;
    }

    function htmlCargando(size) {
        var cls = 'cc-dots' + (size ? ' is-' + size : '');
        return '<span class="' + cls + '" role="status" aria-label="Cargando">' +
            '<span></span><span></span><span></span><span></span><span></span><span></span><span></span></span>';
    }

    function setLoading(id, size) {
        var el = document.getElementById(id);
        if (el) el.innerHTML = htmlCargando(size || 'lg');
    }

    /** Empresa concreta, Todas, cliente o usuario. “Seleccionar” no consulta ventas. */
    function analisisTieneFiltro() {
        return !!(val('an-empresa') || val('an-cliente') || val('an-user'));
    }

    function maybeQueueAnalisisGastos() {
        if (CC.state.page !== 'analisis') return;
        queueAnalisisGastos();
    }

    function centroPermitidoAnalisis(c) {
        var scope = CC._anScopeRevision || {};
        var e = String((c && c.empresa) || '').toUpperCase().trim();
        var rule = scope[e];
        if (!rule) return false;
        if (rule.wide) return true;
        return !!rule.clientes[String((c && c.codigo) || '').trim()];
    }

    function centrosObjetivoAnalisis() {
        var rows = CC.state.centros || [];
        if (!analisisTieneFiltro()) return rows.slice();
        var emp = empresaAnalisisConcreta();
        var cli = val('an-cliente');
        var user = val('an-user');
        return rows.filter(function (c) {
            if (emp && String(c.empresa || '').toUpperCase() !== String(emp).toUpperCase()) return false;
            if (cli && clienteFilterKey(c) !== cli) return false;
            if (user && (c.usuarios || []).indexOf(user) === -1 && c.usuario !== user) return false;
            return true;
        });
    }

    /** El snapshot ya trae importe en oct, nov o dic: no se vuelve a pedir ventas-budget. */
    function snapshotTieneUltimos3(c) {
        var entry = gastoCacheEntry(gastoCacheKey(c));
        if (!entry || !entry.por) return false;
        var keys = Object.keys(entry.por);
        for (var k = 0; k < keys.length; k++) {
            var row = entry.por[keys[k]];
            if (!row || typeof row !== 'object') continue;
            var gasto = row.gasto || [];
            var imp = row.importe || [];
            var usd = row.importe_usd || [];
            for (var i = 9; i <= 11; i++) {
                if (Number(gasto[i]) || Number(imp[i]) || Number(usd[i])) return true;
            }
        }
        return false;
    }

    /** Oct–dic ya bajados y con importe. La marca sola no basta: un refresh no debe dejarlos en cero. */
    function budgetCompletoEnCache(c) {
        var entry = gastoCacheEntry(gastoCacheKey(c));
        if (!(entry && entry.meta && entry.meta.budget_completo)) return false;
        var por = entry.por || {};
        var keys = Object.keys(por);
        for (var k = 0; k < keys.length; k++) {
            var row = por[keys[k]];
            if (!row || typeof row !== 'object') continue;
            var gasto = row.gasto || [];
            var imp = row.importe || [];
            var usd = row.importe_usd || [];
            for (var i = 9; i <= 11; i++) {
                if (Number(gasto[i]) || Number(imp[i]) || Number(usd[i])) return true;
            }
        }
        return false;
    }

    /** Copia oct–dic de ventas-budget al caché de venta real de esta sesión. */
    function aplicarVentasBudgetEnGasto(c, porArticulo) {
        var cacheKey = gastoCacheKey(c);
        var entry = gastoCacheEntry(cacheKey);
        var por = collapsePorCuenta((entry && entry.por) || {});
        Object.keys(porArticulo || {}).forEach(function (k) {
            var hit = porArticulo[k];
            if (!hit) return;
            var cod = String(hit.codigo || k || '').trim();
            if (!cod || (hit.codigo && String(hit.codigo) !== String(k) && String(hit.codigo).toUpperCase() !== String(k).toUpperCase())) {
                return;
            }
            var clave = claveProductoVenta(cod);
            var row = por[clave] && typeof por[clave] === 'object' ? Object.assign({}, por[clave]) : {
                codigo: cod,
                nombre: hit.nombre || '',
                gasto: zeros12(),
                importe: zeros12(),
                importe_usd: zeros12()
            };
            function serie(src) {
                var out = (src || []).slice(0, 12);
                while (out.length < 12) out.push(0);
                return out;
            }
            var gasto = serie(row.gasto);
            var importe = serie(row.importe);
            var usd = serie(row.importe_usd);
            for (var i = 9; i <= 11; i++) {
                var q = Number(hit.meses && hit.meses[i]) || 0;
                var mx = Number(hit.importe && hit.importe[i]) || 0;
                var us = Number(hit.importe_usd && hit.importe_usd[i]) || 0;
                if (!Number(gasto[i]) && q) gasto[i] = q;
                if (!Number(importe[i]) && mx) importe[i] = mx;
                if (!Number(usd[i]) && us) usd[i] = us;
            }
            row.codigo = cod;
            row.gasto = gasto;
            row.importe = importe;
            row.importe_usd = usd;
            if (hit.nombre && !row.nombre) row.nombre = hit.nombre;
            por[clave] = row;
        });
        rememberGastoCache(cacheKey, por, {
            fuente: (entry && entry.meta && entry.meta.fuente) || 'snapshot',
            synced_at: (entry && entry.meta && entry.meta.synced_at) || null,
            year: CC.state.anioGasto || 2026,
            mensaje: null,
            budget_completo: true
        });
        CC.state.gastoLookup = CC.state.gastoLookup || {};
        CC.state.gastoLookup[cacheKey] = mapFromPorCuenta(por);
    }

    /**
     * Oct–Dic (tipo Budget): carga /captura/ventas-budget por cliente filtrado
     * para rellenar estacionalidad cuando gasto-real aún no tiene esos meses.
     */
    function loadAnalisisVentasBudget(centros, gen, done) {
        done = typeof done === 'function' ? done : function () {};
        if (!esBudgetTipo()) {
            done();
            return;
        }
        var url = CC.state.ventasBudgetUrl || '/ProyeccionesVentas/api/captura/ventas-budget';
        CC._anVentasBudget = CC._anVentasBudget || {};
        var list = (centros || []).filter(function (c) {
            var key = analisisVentasBudgetKey(c);
            if (!key || key === '|') return false;
            if (budgetCompletoEnCache(c) || snapshotTieneUltimos3(c)) return false;
            if (Object.prototype.hasOwnProperty.call(CC._anVentasBudget, key)) return false;
            if (CC._anVentasBudgetTried && CC._anVentasBudgetTried[key]) return false;
            return true;
        });
        if (!list.length) {
            done();
            return;
        }
        CC._anVentasBudgetTried = CC._anVentasBudgetTried || {};
        var left = list.length;
        var dirty = false;
        var concurrency = 3;
        var idx = 0;

        function oneDone() {
            left -= 1;
            if (left <= 0) {
                if (gen === CC._anGastoGen && dirty) renderAnalisis();
                done();
            } else {
                kick();
            }
        }

        function kick() {
            while (idx < list.length && concurrency > 0) {
                concurrency -= 1;
                (function (c) {
                    var key = analisisVentasBudgetKey(c);
                    CC._anVentasBudgetTried[key] = true;
                    var year = CC.state.anioGasto || 2026;
                    var qs = '?empresa=' + encodeURIComponent(c.empresa || '') +
                        '&cliente=' + encodeURIComponent(c.codigo || '') +
                        '&year=' + encodeURIComponent(year) +
                        '&meses=10,11,12';
                    fetch(url + qs, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function (r) {
                        return r.json().then(function (json) {
                            if (!r.ok || (json && json.ok === false)) {
                                throw new Error((json && (json.message || json.mensaje)) || 'ventas-budget');
                            }
                            return json;
                        });
                    }).then(function (json) {
                        if (gen !== CC._anGastoGen) return;
                        var porArt = (json && json.por_articulo) || {};
                        CC._anVentasBudget[key] = porArt;
                        aplicarVentasBudgetEnGasto(c, porArt);
                        dirty = true;
                    }).catch(function () {
                        if (gen !== CC._anGastoGen) return;
                        CC._anVentasBudget[key] = {};
                    }).then(function () {
                        concurrency += 1;
                        oneDone();
                    });
                })(list[idx++]);
            }
        }
        kick();
    }

    function queueAnalisisGastos() {
        if (CC.state.page !== 'analisis') {
            CC._anGastoLeft = 0;
            CC._anGastoTotal = 0;
            CC._anGastoPhase = '';
            CC._anGastoRunning = false;
            paintAnalisisLoading();
            return;
        }
        if (!analisisTieneFiltro()) {
            hideAnalisisBusy();
            return;
        }
        var year = CC.state.anioGasto || 2026;
        var silencioso = false;
        var centros = centrosObjetivoAnalisis();
        var filterKey = [val('an-empresa') || '', val('an-cliente') || '', val('an-user') || '', year].join('|');
        if (filtroAnalisisEnCache()) {
            hideAnalisisBusy();
            renderAnalisis();
            return;
        }
        showAnalisisBusy();
        var needGasto = centros.filter(function (c) {
            var gKey = gastoCacheKey(c);
            if (gastoCacheEntry(gKey)) return false;
            if (CC._anGastoTried && CC._anGastoTried[gKey]) return false;
            return true;
        });
        var needPrecio = centros.filter(function (c) {
            if (!CC.state.listasPreciosUrl) return false;
            var pKey = preciosCacheKey(c);
            return !(CC.state.preciosCache && Object.prototype.hasOwnProperty.call(CC.state.preciosCache, pKey));
        });

        if (CC._anGastoRunning && CC._anGastoFilterKey === filterKey) {
            return;
        }

        CC._anGastoGen = (CC._anGastoGen || 0) + 1;
        var gen = CC._anGastoGen;
        CC._anGastoFilterKey = filterKey;
        CC._anGastoRunning = true;
        CC._anGastoPhase = needGasto.length ? 'snapshot' : '';
        CC._anGastoTotal = 0;
        CC._anGastoLeft = 0;
        paintAnalisisLoading();

        function clearLoading() {
            if (gen !== CC._anGastoGen) return;
            CC._anGastoLeft = 0;
            CC._anGastoTotal = 0;
            CC._anGastoPhase = '';
            CC._anGastoRunning = false;
            paintAnalisisLoading();
        }

        function hydrateGastoBatch(json, onlyCentros) {
            var map = (json && json.por_cliente) || {};
            var index = {};
            Object.keys(map).forEach(function (k) {
                var parts = String(k).split('|');
                if (parts.length < 2) return;
                index[String(parts[0]).toUpperCase() + '|' + String(parts[1]).trim().toUpperCase()] = map[k];
            });
            var missing = [];
            var rellenar = [];
            (onlyCentros || needGasto).forEach(function (c) {
                var emp = String(c.empresa || '').toUpperCase();
                var cc = String(c.codigo || '').trim();
                var hit = index[emp + '|' + cc.toUpperCase()] || map[emp + '|' + cc] || null;
                var cacheKey = gastoCacheKey(c);
                if (hit && hit.por_cuenta && typeof hit.por_cuenta === 'object') {
                    rememberGastoCache(cacheKey, hit.por_cuenta, {
                        fuente: hit.fuente || 'snapshot',
                        synced_at: hit.synced_at || null,
                        year: year,
                        mensaje: null,
                        budget_completo: !!hit.budget_completo
                    });
                    CC.state.gastoLookup = CC.state.gastoLookup || {};
                    CC.state.gastoLookup[cacheKey] = mapFromPorCuenta(hit.por_cuenta);
                    if (hit.rellenar) rellenar.push(c);
                } else {
                    missing.push(c);
                }
            });
            return { missing: missing, rellenar: rellenar };
        }

        /** Fase B: faltantes vía /gasto-real (SAP → snapshot), con progreso. */
        function fetchGastoIndividual(centrosMissing, done) {
            if (gen !== CC._anGastoGen) return;
            if (!centrosMissing.length) {
                done();
                return;
            }
            CC._anGastoPhase = 'sap';
            CC._anGastoTotal = centrosMissing.length;
            CC._anGastoLeft = centrosMissing.length;
            CC._anGastoRunning = true;
            paintAnalisisLoading();

            var pending = centrosMissing.slice();
            var inflight = 0;
            var max = 2;
            var dirty = false;
            var paintTimer = null;
            function schedulePaint() {
                if (paintTimer) return;
                paintTimer = setTimeout(function () {
                    paintTimer = null;
                    if (gen !== CC._anGastoGen) return;
                    if (CC.state.page === 'analisis' && dirty && analisisTieneFiltro()) {
                        dirty = false;
                        renderAnalisis();
                    }
                }, 500);
            }
            function kickOne() {
                if (gen !== CC._anGastoGen) return;
                if (!pending.length && !inflight) {
                    done();
                    return;
                }
                while (inflight < max && pending.length) {
                    var c = pending.shift();
                    inflight += 1;
                    (function (centro) {
                        var cacheKey = gastoCacheKey(centro);
                        var finished = false;
                        function doneOne() {
                            if (finished) return;
                            finished = true;
                            if (gen !== CC._anGastoGen) return;
                            inflight -= 1;
                            CC._anGastoLeft = Math.max(0, (CC._anGastoLeft || 0) - 1);
                            paintAnalisisLoading();
                            schedulePaint();
                            if (!pending.length && !inflight) done();
                            else kickOne();
                        }
                        var timer = setTimeout(doneOne, 180000);
                        fetch((CC.state.gastoUrl || '/ProyeccionesVentas/api/gasto-real') +
                            '?empresa=' + encodeURIComponent(centro.empresa || '') +
                            '&cc=' + encodeURIComponent(centro.codigo || '') +
                            '&year=' + encodeURIComponent(year) +
                            '&ligero=1', {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        }).then(function (res) { return res.json(); }).then(function (json) {
                            if (gen !== CC._anGastoGen) return;
                            var por = (json && json.por_cuenta) || {};
                            if (json && json.ok === false) {
                                CC._anGastoTried = CC._anGastoTried || {};
                                CC._anGastoTried[cacheKey] = true;
                                return;
                            }
                            if (por && Object.keys(por).length) {
                                rememberGastoCache(cacheKey, por, {
                                    fuente: (json && json.fuente) || (json && json.ok ? 'api' : 'error'),
                                    synced_at: (json && json.synced_at) || null,
                                    year: (json && json.year) || year,
                                    mensaje: (json && json.mensaje) || null,
                                    budget_completo: !!(json && json.budget_completo)
                                });
                                CC.state.gastoLookup = CC.state.gastoLookup || {};
                                CC.state.gastoLookup[cacheKey] = mapFromPorCuenta(por);
                                dirty = true;
                            } else {
                                CC._anGastoTried = CC._anGastoTried || {};
                                CC._anGastoTried[cacheKey] = true;
                            }
                        }).catch(function () {
                            if (gen !== CC._anGastoGen) return;
                            CC._anGastoTried = CC._anGastoTried || {};
                            CC._anGastoTried[cacheKey] = true;
                        }).then(function () {
                            clearTimeout(timer);
                            doneOne();
                        });
                    })(c);
                }
            }
            kickOne();
        }

        function queuePreciosBackground() {
            if (gen !== CC._anGastoGen) return;
            if (!needPrecio.length || !CC.state.listasPreciosUrl) return;
            var pending = needPrecio.slice();
            var inflight = 0;
            var max = 4;
            var dirty = false;
            var paintTimer = null;
            function schedulePaint() {
                if (paintTimer) return;
                paintTimer = setTimeout(function () {
                    paintTimer = null;
                    if (gen !== CC._anGastoGen) return;
                    if (CC.state.page === 'analisis' && dirty && analisisTieneFiltro()) renderAnalisis();
                }, 400);
            }
            function kick() {
                if (gen !== CC._anGastoGen) return;
                while (inflight < max && pending.length) {
                    var centro = pending.shift();
                    inflight += 1;
                    (function (c) {
                        var pKey = preciosCacheKey(c);
                        var itemCodes = [];
                        try {
                            (cuentasDeCentro(c) || []).forEach(function (cta) {
                                var cod = String((cta && cta.codigo) || '').trim();
                                if (cod && itemCodes.indexOf(cod) === -1) itemCodes.push(cod);
                            });
                        } catch (eItems) { /* ignore */ }
                        var preciosQs = '?empresa=' + encodeURIComponent(c.empresa || '') +
                            '&cliente=' + encodeURIComponent(c.codigo || '') +
                            '&year=' + encodeURIComponent(year);
                        if (itemCodes.length) {
                            preciosQs += '&items=' + encodeURIComponent(itemCodes.join(','));
                        }
                        var finished = false;
                        function donePrecio() {
                            if (finished) return;
                            finished = true;
                            if (gen !== CC._anGastoGen) return;
                            inflight -= 1;
                            if (!pending.length && !inflight) {
                                if (dirty) renderAnalisis();
                            } else {
                                kick();
                            }
                        }
                        var timer = setTimeout(function () {
                            if (gen !== CC._anGastoGen) return;
                            CC.state.preciosCache = CC.state.preciosCache || {};
                            if (!Object.prototype.hasOwnProperty.call(CC.state.preciosCache, pKey)) {
                                CC.state.preciosCache[pKey] = {};
                            }
                            donePrecio();
                        }, 20000);
                        fetch(CC.state.listasPreciosUrl + preciosQs, {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        }).then(function (res) { return res.json(); }).then(function (json) {
                            if (gen !== CC._anGastoGen) return;
                            CC.state.preciosCache = CC.state.preciosCache || {};
                            CC.state.preciosCache[pKey] = mapaPreciosArticulo((json && json.por_articulo) || {});
                            dirty = true;
                            schedulePaint();
                        }).catch(function () {
                            if (gen !== CC._anGastoGen) return;
                            CC.state.preciosCache = CC.state.preciosCache || {};
                            CC.state.preciosCache[pKey] = {};
                        }).then(function () {
                            clearTimeout(timer);
                            donePrecio();
                        });
                    })(centro);
                }
            }
            kick();
        }

        function finishAll() {
            if (gen !== CC._anGastoGen) return;
            clearLoading();
            if (silencioso && !analisisTieneFiltro()) return;
            soltarAnalisisBusy();
            loadAnalisisVentasBudget(centros, gen, function () {
                if (gen !== CC._anGastoGen) return;
                if (CC.state.page === 'analisis' && analisisTieneFiltro()) renderAnalisis();
            });
        }

        if (!CC._anAsigLoaded) {
            return;
        }
        if (!needGasto.length) {
            finishAll();
            return;
        }

        var batchUrl = CC.state.gastoBatchUrl || '/ProyeccionesVentas/api/gasto-real-batch';
        var chunkSize = 80;
        var slices = [];
        for (var cut = 0; cut < needGasto.length; cut += chunkSize) {
            slices.push(needGasto.slice(cut, cut + chunkSize));
        }
        var sliceAt = 0;
        var inflightChunks = 0;
        var maxChunks = 3;
        var missingAll = [];
        var rellenarAll = [];

        function postChunk(slice) {
            return fetch(batchUrl, {
                method: 'POST',
                headers: apiJsonHeaders(),
                credentials: 'same-origin',
                body: JSON.stringify({
                    year: year,
                    clientes: slice.map(function (c) {
                        return { empresa: c.empresa || '', cc: c.codigo || '' };
                    })
                })
            }).then(function (r) {
                return r.json().then(function (json) {
                    if (!r.ok || (json && json.ok === false)) {
                        throw new Error((json && (json.message || json.mensaje)) || 'batch gasto');
                    }
                    return json;
                });
            });
        }

        function pumpChunks() {
            if (gen !== CC._anGastoGen) return;
            if (sliceAt >= slices.length && !inflightChunks) {
                fetchGastoIndividual(missingAll, function () {
                    finishAll();
                    fetchGastoIndividual(rellenarAll, function () {
                        if (gen !== CC._anGastoGen) return;
                        if (CC.state.page === 'analisis' && analisisTieneFiltro()) renderAnalisis();
                    });
                });
                return;
            }
            while (inflightChunks < maxChunks && sliceAt < slices.length) {
                inflightChunks += 1;
                (function (slice) {
                    postChunk(slice).then(function (json) {
                        if (gen !== CC._anGastoGen) return;
                        var parted = hydrateGastoBatch(json, slice) || {};
                        (parted.missing || []).forEach(function (c) { missingAll.push(c); });
                        (parted.rellenar || []).forEach(function (c) { rellenarAll.push(c); });
                    }).catch(function () {
                        if (gen !== CC._anGastoGen) return;
                        slice.forEach(function (c) { missingAll.push(c); });
                    }).then(function () {
                        inflightChunks -= 1;
                        pumpChunks();
                    });
                })(slices[sliceAt++]);
            }
        }

        pumpChunks();
        return;
    }

    function snapshotCentros() {
        var light = !!CC._anLightPaint || !analisisTieneFiltro();
        return CC.state.centros.map(mergedCentro).map(function (c) {
            var users = (c.usuarios && c.usuarios.length) ? c.usuarios : (c.usuario ? [c.usuario] : []);
            if (light) {
                return Object.assign({}, c, {
                    gasto: 0,
                    ppto: 0,
                    cuentas: 0,
                    capturadas: 0,
                    avance: 0,
                    yoY: 0,
                    over: false,
                    ventaLista: false,
                    proyLista: false,
                    departamento: deptoDeCentro(c),
                    usuarios: users,
                    monthGasto: MONTHS.map(function () { return 0; }),
                    productos: []
                });
            }
            var ctas = cuentasDeCentro(c);
            var enriched = cuentasEnriquecidas(c);
            var gasto = 0;
            var pptoVista = 0;
            enriched.forEach(function (x) {
                gasto += Number(x.importeVentaVista) || 0;
                pptoVista += Number(x.importeProyVista) || 0;
            });
            var ppto = totPptoCentro(c, ctas);
            var filled = ctas.filter(function (cta) {
                return mesesTodosLlenos(pptoDe(c.empresa, c.codigo, cta, cta.gasto)) || cuentaMarcada(c.empresa, c.codigo, cta.codigo);
            }).length;
            return Object.assign({}, c, {
                gasto: gasto,
                ppto: ppto,
                pptoVista: pptoVista,
                cuentas: ctas.length,
                capturadas: filled,
                avance: pct(filled, ctas.length || 1),
                yoY: deltaPct(pptoVista, gasto),
                over: gasto > 0 && pptoVista > gasto * 1.1,
                ventaLista: ventaCentroLista(c),
                proyLista: !!CC._capturaReady,
                departamento: deptoDeCentro(c),
                usuarios: users,
                monthGasto: MONTHS.map(function (_, i) {
                    return enriched.reduce(function (a, x) { return a + importeMesVentaVista(x, i); }, 0);
                }),
                productos: enriched.map(function (cta) {
                    return {
                        codigo: cta.codigo,
                        nombre: cta.nombre || cta.codigo,
                        months: MONTHS.map(function (_, i) { return importeMesVentaVista(cta, i); })
                    };
                })
            });
        });
    }

    function toggleAnalisisEmpresa(next) {
        var sel = document.getElementById('an-empresa');
        if (!sel) return;
        sel.value = sel.value === next ? '' : next;
        if (window.jQuery && jQuery.fn.select2 && jQuery(sel).hasClass('select2-hidden-accessible')) {
            CC._anFillingFilters = true;
            jQuery(sel).val(sel.value).trigger('change.select2');
            CC._anFillingFilters = false;
        }
        kickAnalisisVista();
    }

    function applyHeatFilter() {
        var q = (val('an-heat-q') || '').toLowerCase().trim();
        var heat = document.getElementById('an-heat');
        var empty = document.getElementById('an-heat-empty');
        if (!heat) return;
        var rows = heat.querySelectorAll('.cc-heat-row');
        var shown = 0;
        rows.forEach(function (row) {
            var nameEl = row.querySelector('.cc-heat-name');
            var name = ((nameEl && nameEl.textContent) || '').toLowerCase();
            var ok = !q || name.indexOf(q) !== -1;
            row.hidden = !ok;
            if (ok) shown += 1;
        });
        if (empty) empty.hidden = !q || shown > 0;
    }

    function heatMonthCells(monthG, max) {
        max = max || 1;
        return (monthG || MONTHS.map(function () { return 0; })).map(function (v, i) {
            var t = (v || 0) / max;
            var bg = 'rgba(10,10,10,' + (0.12 + t * 0.88) + ')';
            // v es importe de venta real (ya en moneda de vista), no unidades.
            return '<span title="' + MONTHS[i] + ': ' + moneyGasto(v || 0) + '" style="background:' + bg + '"></span>';
        }).join('');
    }

    function emptyMonthCells() {
        return MONTHS.map(function () {
            return '<span style="background:rgba(10,10,10,.08)"></span>';
        }).join('');
    }

    function textoImporteReal(importe, unidades) {
        var txt = moneyDosDecimales(importe);
        if (txt !== '—') return txt;
        var u = Number(unidades) || 0;
        if (u) return qtyLabel(u) + ' uds';
        return '—';
    }

    function productosDeAnalisis(rows) {
        var out = [];
        (rows || []).forEach(function (c) {
            if (!c.proyLista && !c.ventaLista) return;
            cuentasEnriquecidas(c).forEach(function (cta) {
                var monthsVenta = MONTHS.map(function (_, i) { return round2(importeMesVentaVista(cta, i)); });
                var gasto = round2(monthsVenta.reduce(function (a, v) { return a + v; }, 0));
                var ppto = importeProyeccionVista(cta);
                var monthsProy = MONTHS.map(function (_, i) {
                    var m = importeMesProyVista(cta, i);
                    return m == null ? 0 : (Number(m) || 0);
                });
                var filled = (cta.ppto || []).filter(mesLleno).length;
                var listo = !!cta.listo;
                var capturadas = listo ? 12 : filled;
                out.push({
                    centro: c,
                    empresa: c.empresa || '',
                    clienteCodigo: c.codigo || '',
                    clienteNombre: c.nombre || c.codigo || '',
                    departamento: c.departamento || '',
                    usuario: c.usuario || '',
                    usuarios: c.usuarios || [],
                    estado: c.estado,
                    productoCodigo: cta.codigo || '',
                    productoNombre: cta.nombre || cta.codigo || '',
                    gasto: gasto,
                    ppto: ppto,
                    unidadesVenta: Number(cta.totG) || 0,
                    unidadesProy: cta.unidadesAnio != null ? Number(cta.unidadesAnio) || 0 : unidadesAnuales(cta.ppto),
                    monthsVenta: monthsVenta,
                    monthsProy: monthsProy,
                    yoY: deltaPct(ppto, gasto),
                    over: gasto > 0 && ppto > gasto * 1.1,
                    capturadas: capturadas,
                    avance: listo ? 100 : pct(filled, 12),
                    ventaLista: !!c.ventaLista,
                    proyLista: !!c.proyLista
                });
            });
        });
        out.sort(function (a, b) {
            var byEmp = String(a.empresa).localeCompare(String(b.empresa), 'es');
            if (byEmp) return byEmp;
            var byCli = String(a.clienteNombre).localeCompare(String(b.clienteNombre), 'es');
            if (byCli) return byCli;
            return String(a.productoNombre).localeCompare(String(b.productoNombre), 'es');
        });
        return out;
    }

    function fillAnalisisChartProducto(productRows) {
        var sel = document.getElementById('an-chart-producto');
        if (!sel) return '';
        var prev = sel.value;
        var seen = {};
        var items = [];
        (productRows || []).forEach(function (p) {
            var code = String(p.productoCodigo || '');
            if (!code || seen[code]) return;
            seen[code] = true;
            var name = p.productoNombre && p.productoNombre !== code
                ? (p.productoNombre + ' — ' + code)
                : (code || '—');
            items.push({ codigo: code, nombre: name });
        });
        items.sort(function (a, b) {
            return String(a.nombre).localeCompare(String(b.nombre), 'es');
        });
        fillSelectKeepEscaped(sel, items, '<option value="">Todos los productos</option>');
        if (prev && seen[prev]) sel.value = prev;
        else sel.value = '';
        return sel.value;
    }

    function paintAnalisisChart(rows, productRows, gYear, pYear) {
        var prod = fillAnalisisChartProducto(productRows);
        var hint = document.getElementById('an-chart-hint');
        var title = document.getElementById('an-chart-emp-title');
        if (title) {
            title.innerHTML = '<i class="fa-solid fa-chart-column"></i> Venta ' + gYear + ' vs proyección ' + pYear;
        }
        var labels = [];
        var venta = [];
        var proy = [];
        if (prod) {
            var chosen = (productRows || []).filter(function (p) {
                return String(p.productoCodigo) === String(prod);
            });
            var name = (chosen[0] && (chosen[0].productoNombre || chosen[0].productoCodigo)) || prod;
            var byCli = {};
            chosen.forEach(function (p) {
                var key = p.clienteNombre || p.clienteCodigo || '—';
                byCli[key] = byCli[key] || { venta: 0, proy: 0, ventaOk: false, proyOk: false };
                if (p.ventaLista) {
                    byCli[key].venta += Number(p.gasto) || 0;
                    byCli[key].ventaOk = true;
                }
                if (p.proyLista) {
                    byCli[key].proy += Number(p.ppto) || 0;
                    byCli[key].proyOk = true;
                }
            });
            labels = Object.keys(byCli).sort(function (a, b) {
                return String(a).localeCompare(String(b), 'es');
            });
            venta = labels.map(function (k) { return byCli[k].ventaOk ? byCli[k].venta : null; });
            proy = labels.map(function (k) { return byCli[k].proyOk ? byCli[k].proy : null; });
            if (!labels.length) {
                labels = [name];
                venta = [0];
                proy = [0];
            }
            if (hint) hint.textContent = 'Venta y proyección · ' + name;
        } else {
            var byChart = {};
            var source = (productRows && productRows.length) ? productRows : null;
            if (source) {
                source.forEach(function (p) {
                    var key = p.empresa || '—';
                    byChart[key] = byChart[key] || { venta: 0, proy: 0, ventaOk: false, proyOk: false };
                    if (p.ventaLista) {
                        byChart[key].venta += Number(p.gasto) || 0;
                        byChart[key].ventaOk = true;
                    }
                    if (p.proyLista) {
                        byChart[key].proy += Number(p.ppto) || 0;
                        byChart[key].proyOk = true;
                    }
                });
            } else {
                (rows || []).forEach(function (c) {
                    var key = c.empresa || '—';
                    byChart[key] = byChart[key] || { venta: 0, proy: 0, ventaOk: false, proyOk: false };
                    if (c.ventaLista) {
                        byChart[key].venta += Number(c.gasto) || 0;
                        byChart[key].ventaOk = true;
                    }
                    if (c.proyLista) {
                        byChart[key].proy += Number(c.ppto) || 0;
                        byChart[key].proyOk = true;
                    }
                });
            }
            labels = Object.keys(byChart).sort(function (a, b) {
                return String(a).localeCompare(String(b), 'es');
            });
            venta = labels.map(function (e) { return byChart[e].ventaOk ? byChart[e].venta : null; });
            proy = labels.map(function (e) { return byChart[e].proyOk ? byChart[e].proy : null; });
            if (hint) {
                hint.textContent = labels.length
                    ? 'Venta y proyección por empresa'
                    : 'Sin venta ni proyección con esos filtros';
            }
        }
        drawBar('chart-empresas', labels, [
            { label: 'Venta ' + gYear, data: venta, backgroundColor: '#0a0a0a' },
            { label: 'Proyección ' + pYear, data: proy, backgroundColor: '#a1a1aa' }
        ]);
    }

    function renderAnalisis() {
        if (!CC._anLightPaint) fillAnalisisFilters();
        if (CC._anHoldBusy && analisisTieneFiltro()) {
            showAnalisisBusy();
            return;
        }
        var q = normSearch(val('an-q') || '');
        var empRaw = val('an-empresa');
        var emp = empresaAnalisisConcreta(empRaw);
        var cli = val('an-cliente');
        var user = val('an-user');
        var gYear = CC.state.anioGasto || 2026;
        var pYear = CC.state.anioPresupuesto || 2027;
        var empty = document.getElementById('an-empty');
        var emptyMsg = document.getElementById('an-empty-msg');
        var emptyIcon = document.getElementById('an-empty-icon');
        var work = document.getElementById('an-work');
        var all = CC._anAsigLoaded ? snapshotCentros() : [];
        var needsFilter = CC._anAsigLoaded && !!all.length && !analisisTieneFiltro();
        paintAnalisisLoading();
        if (empty) {
            if (!CC._anAsigLoaded) {
                empty.hidden = false;
                if (analisisTieneFiltro()) {
                    if (emptyMsg) emptyMsg.innerHTML = htmlCargando('lg');
                    if (emptyIcon) emptyIcon.hidden = true;
                } else {
                    if (emptyMsg) emptyMsg.textContent = 'Selecciona una empresa, o Todas, para ver el análisis.';
                    if (emptyIcon) {
                        emptyIcon.hidden = false;
                        emptyIcon.className = 'fa-solid fa-filter';
                    }
                }
            } else if (!all.length) {
                empty.hidden = false;
                if (emptyMsg) {
                    emptyMsg.textContent = CC._anSinRevision
                        ? 'No tienes permiso de revisar ninguna empresa en este ciclo.'
                        : 'No hay clientes asignados en este ciclo. Asigna usuarios y productos en Proyecciones de ventas.';
                }
                if (emptyIcon) {
                    emptyIcon.hidden = false;
                    emptyIcon.className = 'fa-solid fa-chart-line';
                }
            } else if (needsFilter) {
                empty.hidden = false;
                if (emptyMsg) {
                    emptyMsg.textContent = 'Selecciona una empresa, o Todas, para ver el análisis.';
                }
                if (emptyIcon) {
                    emptyIcon.hidden = false;
                    emptyIcon.className = 'fa-solid fa-filter';
                }
            } else {
                empty.hidden = true;
            }
        }
        if (work) work.hidden = !CC._anAsigLoaded || !all.length || needsFilter;

        var rows = all.filter(function (c) {
            if (emp && c.empresa !== emp) return false;
            if (cli && clienteFilterKey(c) !== cli) return false;
            if (user && (c.usuarios || []).indexOf(user) === -1 && c.usuario !== user) return false;
            return true;
        });
        if (needsFilter) {
            setText('an-kpi-avance', '—');
            setText('an-kpi-poco', '—');
            setText('an-kpi-over', '—');
            setText('an-kpi-yoy', '—');
            var tfWait = document.getElementById('an-tfoot');
            if (tfWait) tfWait.innerHTML = '';
            return;
        }
        var productRows = rows.length > 40 ? [] : productosDeAnalisis(rows);
        if (!productRows.length && rows.length) {
            productRows = rows.map(function (c) {
                return {
                    centro: c,
                    empresa: c.empresa || '',
                    clienteCodigo: c.codigo || '',
                    clienteNombre: c.nombre || c.codigo || '',
                    departamento: c.departamento || '',
                    usuario: c.usuario || '',
                    usuarios: c.usuarios || [],
                    estado: c.estado,
                    productoCodigo: '',
                    productoNombre: 'Sin productos asignados',
                    gasto: Number(c.gasto) || 0,
                    ppto: Number(c.pptoVista != null ? c.pptoVista : c.ppto) || 0,
                    unidadesVenta: 0,
                    unidadesProy: 0,
                    yoY: c.yoY || 0,
                    over: !!c.over,
                    capturadas: c.capturadas || 0,
                    avance: c.avance || 0,
                    ventaLista: !!c.ventaLista,
                    proyLista: !!c.proyLista,
                    monthsVenta: (c.monthGasto || []).slice()
                };
            });
        }
        // Tabla de detalle: 1 fila por cliente (agregado), no por producto.
        var tableRows = rows.filter(function (c) {
            if (!q) return true;
            var blob = normSearch([
                c.codigo, c.nombre, c.empresa, c.departamento, c.usuario, (c.usuarios || []).join(' ')
            ].join(' '));
            return blob.indexOf(q) !== -1;
        }).slice().sort(function (a, b) {
            var byEmp = String(a.empresa || '').localeCompare(String(b.empresa || ''), 'es');
            if (byEmp) return byEmp;
            return String(a.nombre || a.codigo || '').localeCompare(String(b.nombre || b.codigo || ''), 'es');
        });

        if (!rows.length) {
            setText('an-kpi-avance', '—');
            setText('an-kpi-poco', all.length ? '0' : '—');
            setText('an-kpi-over', all.length ? '0' : '—');
            setText('an-kpi-yoy', '—');
        } else {
            var listosProy = rows.filter(function (c) { return c.proyLista; });
            var listosVenta = rows.filter(function (c) { return c.ventaLista; });
            if (!listosProy.length) {
                setLoading('an-kpi-avance');
                setLoading('an-kpi-poco');
            } else {
                var tot = listosProy.length;
                var avgAv = Math.round(listosProy.reduce(function (a, c) { return a + c.avance; }, 0) / tot);
                var poco = listosProy.filter(function (c) { return c.avance < 40; }).length;
                setText('an-kpi-avance', avgAv + '%');
                setText('an-kpi-poco', poco);
            }
            if (!listosVenta.length) {
                setLoading('an-kpi-over');
                setLoading('an-kpi-yoy');
            } else {
                var over = listosVenta.filter(function (c) { return c.over; }).length;
                var withGasto = listosVenta.filter(function (c) { return c.gasto > 0; });
                var avgYoy = withGasto.length
                    ? Math.round(withGasto.reduce(function (a, c) { return a + c.yoY; }, 0) / withGasto.length * 10) / 10
                    : 0;
                setText('an-kpi-over', over);
                setText('an-kpi-yoy', withGasto.length ? ((avgYoy > 0 ? '+' : '') + avgYoy + '%') : '—');
            }
        }

        var byEmp = {};
        rows.forEach(function (c) {
            byEmp[c.empresa] = byEmp[c.empresa] || { n: 0, av: 0, avN: 0, gasto: 0, ppto: 0, ventaPend: 0, proyPend: 0 };
            byEmp[c.empresa].n += 1;
            if (c.proyLista) {
                byEmp[c.empresa].av += c.avance;
                byEmp[c.empresa].avN += 1;
                byEmp[c.empresa].ppto += c.ppto;
            } else {
                byEmp[c.empresa].proyPend += 1;
            }
            if (c.ventaLista) byEmp[c.empresa].gasto += c.gasto;
            else byEmp[c.empresa].ventaPend += 1;
        });
        var empBox = document.getElementById('an-empresas');
        if (empBox) {
            empBox.innerHTML = Object.keys(byEmp).map(function (e) {
                var info = byEmp[e];
                var av = info.avN ? Math.round(info.av / info.avN) : null;
                var cls = av == null ? '' : (av < 40 ? 'warn' : (av > 85 ? 'good' : ''));
                var on = empRaw === e ? ' is-on' : '';
                var avTxt = av == null ? htmlCargando('sm') : (av + '% capturado · ' + info.n + ' clientes');
                var ventaTxt = info.ventaPend
                    ? (info.ventaPend === info.n ? htmlCargando('sm') : (moneyVista(info.gasto) + ' · ' + htmlCargando('sm')))
                    : moneyVista(info.gasto);
                var proyTxt = info.proyPend
                    ? (info.proyPend === info.n ? htmlCargando('sm') : (money(info.ppto) + ' · ' + htmlCargando('sm')))
                    : money(info.ppto);
                var bar = av == null
                    ? '<div class="mt-1">' + htmlCargando('sm') + '</div>'
                    : '<div class="cc-progress ' + cls + ' mt-1"><span style="width:' + av + '%"></span></div>';
                return '<div class="mb-3 cc-an-emp' + on + '" data-emp="' + escapeHtml(e) + '"><div class="d-flex justify-content-between"><strong>' + escapeHtml(e) + '</strong><span>' + avTxt + '</span></div>' +
                    bar +
                    '<div class="text-muted mt-1" style="font-size:.75rem">Venta ' + gYear + ' ' + ventaTxt + ' · Proyecciones ' + pYear + ' ' + proyTxt + '</div></div>';
            }).join('') || '<div class="cc-empty">Sin empresas</div>';
        }

        var tb = document.getElementById('an-tbody');
        if (tb) {
            tb.innerHTML = tableRows.map(function (c) {
                var avance = Number(c.avance) || 0;
                var cls = avance < 40 ? 'bad' : (avance < 80 ? 'warn' : 'good');
                var userLabel = (c.usuarios && c.usuarios.length > 1)
                    ? ((c.usuario || c.usuarios[0] || '') + ' +' + (c.usuarios.length - 1))
                    : (c.usuario || (c.usuarios && c.usuarios[0]) || '');
                var cliSub = [c.empresa, c.departamento].filter(Boolean).join(' · ');
                var nCtas = Number(c.cuentas) || 0;
                var capt = Number(c.capturadas) || 0;
                var capCell = c.proyLista
                    ? ('<div class="d-flex justify-content-between"><span>' + capt + '/' + nCtas + '</span><span class="cc-semaforo-' + cls + '">' + avance + '%</span></div><div class="cc-progress ' + cls + ' mt-1"><span style="width:' + Math.min(avance, 100) + '%"></span></div>')
                    : htmlCargando('sm');
                var ventaCell = c.ventaLista ? moneyDosDecimales(c.gasto) : htmlCargando('sm');
                var pptoVal = c.pptoVista != null ? c.pptoVista : c.ppto;
                var proyCell = c.proyLista ? moneyGasto(pptoVal) : htmlCargando('sm');
                var yoY = Number(c.yoY) || 0;
                var deltaCell = (c.ventaLista && c.proyLista)
                    ? (Number(c.gasto) ? ((yoY > 0 ? '+' : '') + yoY + '%') : '—')
                    : htmlCargando('sm');
                var limiteCell = (c.ventaLista && c.proyLista)
                    ? (c.over ? '<span class="cc-badge cc-badge-rechazado">Sobre límite</span>' : '<span class="cc-badge cc-badge-aceptado">Dentro</span>')
                    : htmlCargando('sm');
                return '<tr><td>' + htmlNombreCodigo(c.nombre || c.codigo, c.codigo) +
                    (cliSub ? '<div class="text-muted" style="font-size:.75rem">' + escapeHtml(cliSub) + '</div>' : '') + '</td>' +
                    '<td>' + userCell(userLabel) + '</td>' +
                    '<td>' + renderBadge(c.estado) + '</td>' +
                    '<td>' + capCell + '</td>' +
                    '<td class="num">' + ventaCell + '</td>' +
                    '<td class="num">' + proyCell + '</td>' +
                    '<td class="num ' + (yoY > 10 ? 'text-danger' : '') + '">' + deltaCell + '</td>' +
                    '<td>' + limiteCell + '</td>' +
                    '<td><a class="cc-btn" href="' + detalleHref(c) + '">Ver</a></td></tr>';
            }).join('') || '<tr><td colspan="9"><div class="cc-empty">Sin clientes con esos filtros</div></td></tr>';
        }

        var tf = document.getElementById('an-tfoot');
        if (tf) {
            if (!tableRows.length) {
                tf.innerHTML = '';
            } else {
                var sumGasto = 0;
                var sumPpto = 0;
                var ventaPend = false;
                var proyPend = false;
                tableRows.forEach(function (c) {
                    if (c.ventaLista) sumGasto += Number(c.gasto) || 0;
                    else ventaPend = true;
                    if (c.proyLista) sumPpto += Number(c.pptoVista != null ? c.pptoVista : c.ppto) || 0;
                    else proyPend = true;
                });
                var yoyTot = (!ventaPend && !proyPend) ? deltaPct(sumPpto, sumGasto) : null;
                var nCli = tableRows.length;
                var gastoFoot = ventaPend ? (tableRows.some(function (c) { return c.ventaLista; }) ? (moneyDosDecimales(sumGasto) + ' · ' + htmlCargando('sm')) : htmlCargando('sm')) : moneyDosDecimales(sumGasto);
                var pptoFoot = proyPend ? (tableRows.some(function (c) { return c.proyLista; }) ? (moneyGasto(sumPpto) + ' · ' + htmlCargando('sm')) : htmlCargando('sm')) : moneyGasto(sumPpto);
                var deltaFoot = yoyTot == null ? htmlCargando('sm') : (sumGasto ? ((yoyTot > 0 ? '+' : '') + yoyTot + '%') : '—');
                tf.innerHTML = '<tr>' +
                    '<td colspan="4">Totales · ' + nCli + (nCli === 1 ? ' cliente' : ' clientes') + '</td>' +
                    '<td class="num">' + gastoFoot + '</td>' +
                    '<td class="num">' + pptoFoot + '</td>' +
                    '<td class="num' + (yoyTot > 10 ? ' text-danger' : '') + '">' + deltaFoot + '</td>' +
                    '<td colspan="2"></td>' +
                    '</tr>';
            }
        }

        paintAnalisisChart(rows, productRows, gYear, pYear);
        drawDoughnut('chart-estados',
            ['Abierto', 'Capturado', 'En revisión', 'Terminado'],
            [
                rows.filter(function (c) { return normalizeEstado(c.estado) === 'abierto'; }).length,
                rows.filter(function (c) { return normalizeEstado(c.estado) === 'en_proceso'; }).length,
                rows.filter(function (c) { return normalizeEstado(c.estado) === 'en_revision'; }).length,
                rows.filter(function (c) { return normalizeEstado(c.estado) === 'terminado'; }).length
            ]
        );

        var heat = document.getElementById('an-heat');
        if (heat) {
            var heatItems = [];
            var heatSource = (productRows && productRows.length) ? productRows : null;
            if (emp) {
                var byProd = {};
                if (heatSource) {
                    heatSource.forEach(function (p) {
                        if (String(p.empresa || '').toUpperCase() !== String(emp).toUpperCase()) return;
                        var key = String(p.productoCodigo || p.productoNombre || '');
                        if (!key) return;
                        if (!byProd[key]) {
                            byProd[key] = {
                                label: (p.productoCodigo || '') + (p.productoNombre && p.productoNombre !== p.productoCodigo ? (' ' + p.productoNombre) : ''),
                                months: MONTHS.map(function () { return 0; }),
                                pending: false,
                                ready: false
                            };
                        }
                        if (!p.ventaLista) byProd[key].pending = true;
                        else {
                            byProd[key].ready = true;
                            (p.monthsVenta || []).forEach(function (v, i) {
                                byProd[key].months[i] += Number(v) || 0;
                            });
                        }
                    });
                } else {
                    rows.forEach(function (c) {
                        (c.productos || []).forEach(function (p) {
                            var key = String(p.codigo || p.nombre || '');
                            if (!key) return;
                            if (!byProd[key]) {
                                byProd[key] = {
                                    label: (p.codigo || '') + (p.nombre && p.nombre !== p.codigo ? (' ' + p.nombre) : ''),
                                    months: MONTHS.map(function () { return 0; }),
                                    pending: false,
                                    ready: false
                                };
                            }
                            if (!c.ventaLista) byProd[key].pending = true;
                            else {
                                byProd[key].ready = true;
                                (p.months || []).forEach(function (v, i) {
                                    byProd[key].months[i] += v || 0;
                                });
                            }
                        });
                    });
                }
                heatItems = Object.keys(byProd).map(function (k) {
                    var item = byProd[k];
                    var tot = item.months.reduce(function (a, v) { return a + (v || 0); }, 0);
                    return { label: item.label, months: item.months, tot: tot, attr: '', pending: item.pending && !item.ready };
                }).sort(function (a, b) { return b.tot - a.tot; });
                setText('an-heat-label', 'Venta ' + gYear + ' · por producto · ' + emp
                    + (esBudgetTipo() ? ' · Oct–Dic ventas-budget' : ''));
                var heatInput = document.getElementById('an-heat-q');
                if (heatInput) heatInput.placeholder = 'Buscar producto…';
            } else {
                var byEmpHeat = {};
                rows.forEach(function (c) {
                    var key = c.empresa || '—';
                    byEmpHeat[key] = byEmpHeat[key] || { months: MONTHS.map(function () { return 0; }), pending: false, ready: false };
                    if (!c.ventaLista) {
                        byEmpHeat[key].pending = true;
                        return;
                    }
                    byEmpHeat[key].ready = true;
                    (c.monthGasto || []).forEach(function (v, i) {
                        byEmpHeat[key].months[i] += Number(v) || 0;
                    });
                });
                if (heatSource) {
                    heatSource.forEach(function (p) {
                        if (!p.ventaLista || !(p.monthsVenta && p.monthsVenta.length)) return;
                        var key = p.empresa || '—';
                        byEmpHeat[key] = byEmpHeat[key] || { months: MONTHS.map(function () { return 0; }), pending: false, ready: false };
                        byEmpHeat[key].ready = true;
                        var hasRowMonths = (byEmpHeat[key].months || []).some(function (v) { return Number(v) > 0; });
                        if (hasRowMonths) return;
                        (p.monthsVenta || []).forEach(function (v, i) {
                            byEmpHeat[key].months[i] += Number(v) || 0;
                        });
                    });
                }
                heatItems = Object.keys(byEmpHeat).map(function (e) {
                    var item = byEmpHeat[e];
                    var months = item.months || MONTHS.map(function () { return 0; });
                    var tot = months.reduce(function (a, v) { return a + (v || 0); }, 0);
                    return {
                        label: e,
                        months: months,
                        tot: tot,
                        attr: ' data-emp="' + escapeHtml(e) + '"',
                        pending: !!(item.pending && !item.ready)
                    };
                }).sort(function (a, b) { return b.tot - a.tot; });
                setText('an-heat-label', 'Venta ' + gYear + ' · por empresa'
                    + (esBudgetTipo() ? ' · Oct–Dic ventas-budget' : ''));
                var heatInputAll = document.getElementById('an-heat-q');
                if (heatInputAll) heatInputAll.placeholder = 'Buscar empresa o producto…';
            }

            if (!heatItems.length) {
                heat.innerHTML = '<div class="cc-heat-row"><div class="cc-heat-name">—</div><div class="cc-heat">' + emptyMonthCells() + '</div></div>';
            } else {
                var maxHeat = 1;
                heatItems.forEach(function (item) {
                    var local = Math.max.apply(null, (item.months || []).concat([0]));
                    if (local > maxHeat) maxHeat = local;
                });
                heat.innerHTML = heatItems.map(function (item) {
                    var click = item.attr ? ' is-click' : '';
                    var cells = item.pending
                        ? htmlCargando('sm')
                        : heatMonthCells(item.months, maxHeat);
                    return '<div class="cc-heat-row' + click + '"' + item.attr + '>' +
                        '<div class="cc-heat-name" title="' + escapeHtml(item.label) + '">' + escapeHtml(item.label) + '</div>' +
                        '<div class="cc-heat">' + cells + '</div>' +
                        '</div>';
                }).join('');
            }
            applyHeatFilter();
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
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function (v) { return moneyVista(v); } },
                        grid: { color: '#f1f1f3' }
                    }
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
    function isEmpresaCompleta(codigo) {
        return String(codigo || '').replace(/\s+/g, '').toUpperCase() === 'EMPRESA';
    }
    function labelNombreCodigo(nombre, codigo) {
        if (isEmpresaCompleta(codigo)) return 'Toda la empresa';
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
                    tipoBudget: 'BUDGET',
                    observaciones: ''
                };
            setVal('p-codigo', p.codigo);
            setVal('p-nombre', p.nombre);
            setVal('p-anio-ref', p.anioReferencia || 2026);
            setVal('p-anio', p.anio || 2027);
            setVal('p-inicio', p.inicio); setVal('p-fin', p.fin);
            setVal('p-captura', p.capturaHasta); setVal('p-revision', p.revisionDesde);
            setVal('p-estado', normalizeCicloEstado(p.estado)); setVal('p-inflacion', p.inflacion); setVal('p-tc', p.tipoCambio);
            setVal('p-tipo-budget', p.tipoBudget || 'BUDGET');
            setVal('p-obs', p.observaciones || '');
            var tipoBudgetEl = document.getElementById('p-tipo-budget');
            var tipoFijo = editing && !!normalizeTipoBudgetClient(p.tipoBudget);
            if (tipoBudgetEl) {
                tipoBudgetEl.disabled = tipoFijo;
                tipoBudgetEl.title = tipoFijo
                    ? 'El tipo queda fijo al crear el ciclo'
                    : (editing
                        ? 'Este ciclo aún no tenía tipo: elige Budget, Forecast o SIOP y guarda'
                        : 'Budget: meses en blanco · Forecast: venta real + Budget · SIOP: precarga proyección del año (dato o 0), editable');
            }
            syncTipoBudgetModalUi();
            if (editing) {
                var tipoHint = document.getElementById('p-tipo-budget-hint');
                if (tipoHint) {
                    tipoHint.textContent = tipoFijo
                        ? ('Fijo: ' + labelTipoBudget(p.tipoBudget) + ' (no se puede cambiar)')
                        : 'Aún sin tipo: elige Budget / 3+9 / 6+6 / 9+3 / SIOP y guarda para fijarlo.';
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
        var puedeEditar = !!CC.state.puedeEditarCostos;
        var items = [];
        var page = 1;
        var lastPage = 1;
        var total = 0;
        var from = 0;
        var to = 0;
        var searchTimer = null;
        var anioSel = document.getElementById('pv-costos-anio');
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

        // Botones que modifican precios: solo con permiso editar_ventas_costos.
        ['pv-costos-excel', 'pv-costos-import-api', 'pv-costos-import-lista'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (!puedeEditar) {
                el.hidden = true;
                el.disabled = true;
            }
        });
        ['pcm-save', 'pcm-apply-all', 'pcm-reset'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (!puedeEditar) {
                el.hidden = true;
                el.disabled = true;
            }
        });

        function costosAnio() {
            var fromSel = anioSel ? Number(anioSel.value) : 0;
            if (fromSel >= 2000 && fromSel <= 2100) return fromSel;
            return Number(CC.state.costosAnio || CC.state.anioPresupuesto) || 2027;
        }

        function paintAnioLabel(anio) {
            var a = Number(anio) || costosAnio();
            CC.state.costosAnio = a;
            var anioLbl = document.getElementById('pv-costos-anio-label');
            if (anioLbl) anioLbl.textContent = String(a);
            if (anioSel && String(anioSel.value) !== String(a)) {
                // se sincroniza en fillAnios
            }
        }

        function fillAnios(list, selected) {
            if (!anioSel) return;
            var cur = Number(selected) || costosAnio();
            var set = {};
            (list || []).forEach(function (y) {
                var n = Number(y);
                if (n >= 2000 && n <= 2100) set[n] = true;
            });
            (CC.state.ciclos || []).forEach(function (c) {
                var n = Number(c.anio || c.anioPresupuesto);
                if (n >= 2000 && n <= 2100) set[n] = true;
            });
            set[cur] = true;
            set[Number(CC.state.anioPresupuesto) || 2027] = true;
            var years = Object.keys(set).map(Number).sort(function (a, b) { return b - a; });
            anioSel.innerHTML = years.map(function (y) {
                var tag = (y === Number(CC.state.anioPresupuesto)) ? ' · ciclo actual' : '';
                return '<option value="' + y + '"' + (y === cur ? ' selected' : '') + '>Proy. ' + y + tag + '</option>';
            }).join('');
            anioSel.value = String(cur);
            paintAnioLabel(cur);
        }

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
                tbody.innerHTML = '<tr><td colspan="12"><div class="cc-empty">Sin productos para mostrar</div></td></tr>';
                return;
            }
            tbody.innerHTML = items.map(function (it, idx) {
                var mon = String(it.moneda || 'MXN').toUpperCase();
                var costoTxt = (mon === 'USD' ? 'US$' : '$') +
                    (Number(it.costo_unitario) || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                var listaPrecio = it.precio_lista != null && it.precio_lista !== '' ? Number(it.precio_lista) : null;
                var listaMon = String(it.moneda_lista || mon || 'MXN').toUpperCase();
                var listaTxt = (listaPrecio != null && isFinite(listaPrecio) && listaPrecio > 0)
                    ? ((listaMon === 'USD' ? 'US$' : '$') +
                        listaPrecio.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))
                    : '—';
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
                    '<td class="num" title="Precio lista SAP">' + escapeHtml(listaTxt) + '</td>' +
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
                    (puedeEditar
                        ? ('<button type="button" class="cc-btn cc-btn-sm" data-sync-api-costo' +
                    ' data-emp="' + escapeHtml(it.empresa) + '"' +
                    ' data-cod="' + escapeHtml(codigo) + '"' +
                    ' data-nom="' + escapeHtml(nombre || codigo) + '"' +
                    ' data-card="' + escapeHtml(it.card_code || '') + '"' +
                    ' data-mes="0"' +
                    ' title="Actualizar precio global desde listaPreciosventa">' +
                    '<i class="fa-solid fa-cloud-arrow-down"></i></button>' +
                    '<button type="button" class="cc-btn cc-btn-sm" data-sync-precio-lista' +
                    ' data-emp="' + escapeHtml(it.empresa) + '"' +
                    ' data-cod="' + escapeHtml(codigo) + '"' +
                    ' data-nom="' + escapeHtml(nombre || codigo) + '"' +
                    ' data-card="' + escapeHtml(it.card_code || '') + '"' +
                    ' data-mon="' + escapeHtml(mon) + '"' +
                    ' title="Actualizar Precio lista de este producto">' +
                    '<i class="fa-solid fa-tag"></i></button>' +
                    '<button type="button" class="cc-btn cc-btn-sm" data-edit-meses-costo data-idx="' + idx + '"' +
                    ' title="Editar precios por mes">' +
                    '<i class="fa-solid fa-calendar-days"></i></button>')
                        : '') +
                    '</div></td>' +
                    '</tr>';
            }).join('');
            tbody.querySelectorAll('[data-edit-meses-costo]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!puedeEditar) return;
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
                    if (!puedeEditar) return;
                    actualizarPrecioDesdeApi(btn);
                });
            });
            tbody.querySelectorAll('[data-sync-precio-lista]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!puedeEditar) return;
                    actualizarPrecioListaProducto(btn);
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
            CC._pcmPrevGlobal = base > 0 ? base : precioModaDeMeses(meses);

            setVal('pcm-empresa', it.empresa || '');
            setVal('pcm-codigo', codigo);
            setVal('pcm-nombre', nombre || codigo);
            setVal('pcm-card', it.card_code || '');
            setVal('pcm-card-name', it.card_name || '');
            setVal('pcm-base', base > 0 ? base.toFixed(2) : '');
            setVal('pcm-moneda', mon);
            setText('pcm-sub', (nombre || codigo) + ' · ' + codigo +
                (it.card_code ? (' · ' + it.card_code) : '') +
                (it.empresa ? (' · ' + it.empresa) : '') +
                (' · proy. ' + costosAnio()));
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
                    var custom = isFinite(ov) && ov > 0 && Math.abs(ov - base) > 0.005;
                    var has = isFinite(ov) && ov > 0;
                    return '<div class="cc-tc-month' + (custom ? ' is-custom' : '') + '">' +
                        '<label for="pcm-m-' + i + '">' + m + '</label>' +
                        '<input id="pcm-m-' + i + '" type="number" step="0.01" min="0" placeholder="' +
                        (base > 0 ? base.toFixed(2) : '0') + '" value="' + (has ? ov.toFixed(2) : '') + '">' +
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
                var custom = raw !== '' && isFinite(v) && v > 0 && Math.abs(v - base) > 0.005;
                wrap.classList.toggle('is-custom', custom || (raw !== '' && isFinite(v) && v > 0));
            }
        }

        function readCostoMesesFromEditor() {
            var out = [];
            for (var i = 0; i < 12; i++) {
                var el = document.getElementById('pcm-m-' + i);
                var raw = el ? String(el.value || '').trim() : '';
                var v = Number(raw);
                out.push(raw !== '' && isFinite(v) && v > 0 ? Math.round(v * 100) / 100 : null);
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
            var base2 = base > 0 ? Math.round(base * 100) / 100 : 0;
            var prevGlobal = Number(CC._pcmPrevGlobal) || 0;
            var globalCambio = base2 > 0 && prevGlobal > 0 && Math.abs(base2 - prevGlobal) > 0.005;
            for (var i = 0; i < 12; i++) {
                var p = meses[i];
                // Si cambió el global, los meses que tenían el global anterior pasan al nuevo
                // (los que difieren, p.ej. Dic=7, se conservan).
                if (globalCambio && p !== null && p > 0 && Math.abs(p - prevGlobal) < 0.005) {
                    p = base2;
                }
                if (p === null || !(p > 0)) continue;
                jobs.push({
                    empresa: emp,
                    producto_codigo: cod,
                    producto_nombre: nom || cod,
                    card_code: card,
                    card_name: cardName,
                    mes: i + 1,
                    anio: costosAnio(),
                    costo_unitario: Math.round(p * 100) / 100,
                    moneda: mon
                });
            }
            // Si no hay ningún mes capturado pero sí global, guarda los 12 con el global.
            if (!jobs.length && base2 > 0) {
                for (var j = 0; j < 12; j++) {
                    jobs.push({
                        empresa: emp,
                        producto_codigo: cod,
                        producto_nombre: nom || cod,
                        card_code: card,
                        card_name: cardName,
                        mes: j + 1,
                        anio: costosAnio(),
                        costo_unitario: base2,
                        moneda: mon
                    });
                }
            }
            // Fila global (mes=0): siempre el valor del campo Global.
            var globalSave = base2 > 0 ? base2 : precioModaDeMeses(meses);
            if (globalSave > 0) {
                jobs.push({
                    empresa: emp,
                    producto_codigo: cod,
                    producto_nombre: nom || cod,
                    card_code: card,
                    card_name: cardName,
                    mes: 0,
                    anio: costosAnio(),
                    costo_unitario: Math.round(globalSave * 100) / 100,
                    moneda: mon
                });
            }
            if (!jobs.length) {
                toast('warning', 'Sin precios', 'Captura al menos un mes o un precio global.');
                return;
            }
            var saveBtn = document.getElementById('pcm-save');
            var saveHtml = saveBtn ? saveBtn.innerHTML : '';
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.setAttribute('aria-busy', 'true');
                saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…';
            }
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
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.removeAttribute('aria-busy');
                    saveBtn.innerHTML = saveHtml || '<i class="fa-solid fa-check"></i> Guardar precios';
                }
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
                    if (el) el.value = base.toFixed(2);
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
                monthsBox.addEventListener('blur', function (ev) {
                    if (ev.target && ev.target.id && ev.target.id.indexOf('pcm-m-') === 0) {
                        var raw = String(ev.target.value || '').trim();
                        var v = Number(raw);
                        if (raw !== '' && isFinite(v) && v > 0) {
                            ev.target.value = v.toFixed(2);
                        }
                    }
                }, true);
            }
            var baseEl = document.getElementById('pcm-base');
            if (baseEl) {
                baseEl.addEventListener('input', highlightCostoMesesEditor);
                baseEl.addEventListener('blur', function () {
                    var v = Number(baseEl.value);
                    if (isFinite(v) && v > 0) baseEl.value = v.toFixed(2);
                });
            }
        }

        bindCostoMesesUi();

        /** Actualiza solo precio_lista de UN producto vía /listaPreciosventa/{EMPRESA}. */
        function actualizarPrecioListaProducto(btn) {
            var emp = String(btn.getAttribute('data-emp') || '').toUpperCase();
            var cod = String(btn.getAttribute('data-cod') || '').trim();
            var card = String(btn.getAttribute('data-card') || '').trim();
            var nom = btn.getAttribute('data-nom') || cod;
            var mon = String(btn.getAttribute('data-mon') || 'MXN').toUpperCase() || 'MXN';
            var loteUrl = CC.state.costosSyncPrecioListaLoteUrl || (url + '/sincronizar-precio-lista-lote');
            if (!emp || !cod) {
                toast('error', 'Datos incompletos', 'Falta empresa o ItemCode.');
                return;
            }
            if (!card) {
                toast('error', 'Sin CardCode', 'Se necesita CardCode para consultar listaPreciosventa.');
                return;
            }
            var go = function () {
                var prevHtml = btn.innerHTML;
                btn.disabled = true;
                btn.setAttribute('aria-busy', 'true');
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                fetch(loteUrl, {
                    method: 'POST',
                    headers: apiJsonHeaders(),
                    body: JSON.stringify({
                        empresa: emp,
                        card_code: card,
                        anio_proyeccion: costosAnio(),
                        solo_vacios: false,
                        sync_precio_global: true,
                        items: [{
                            item_code: cod,
                            producto: nom || cod,
                            moneda: mon
                        }]
                    })
                }).then(function (r) {
                    return r.text().then(function (txt) {
                        var json = null;
                        try { json = txt ? JSON.parse(txt) : null; } catch (e) { json = null; }
                        if (!r.ok) {
                            throw new Error((json && json.message) || ('HTTP ' + r.status));
                        }
                        return json || {};
                    });
                }).then(function (json) {
                    var n = Number(json.actualizados) || 0;
                    var nG = Number(json.actualizados_global) || 0;
                    var omit = Number(json.omitidos_sin_lista) || 0;
                    if (n > 0) {
                        toast('success', 'Sincronizado con SAP',
                            (nom || cod) + ' · ' + card +
                            (nG ? (' · Precio global: ' + nG) : '') +
                            (json.message ? (' · ' + json.message) : ''));
                        load(page);
                    } else if (omit > 0) {
                        toast('warning', 'Sin precio en SAP',
                            'No hay Precio lista en listaPreciosventa para ' + cod + ' / ' + card + '.');
                    } else {
                        toast('info', 'Sin cambios', json.message || 'No se actualizó el Precio lista.');
                    }
                }).catch(function (err) {
                    toast('error', 'No se actualizó', err && err.message ? err.message : 'Error');
                }).then(function () {
                    btn.disabled = false;
                    btn.removeAttribute('aria-busy');
                    btn.innerHTML = prevHtml || '<i class="fa-solid fa-tag"></i>';
                });
            };
            if (window.Swal) {
                Swal.fire({
                    icon: 'question',
                    title: '¿Actualizar Precio lista?',
                    html: '<div style="text-align:left;font-size:.9rem">' +
                        '<div><strong>' + escapeHtml(nom || cod) + '</strong></div>' +
                        '<div class="text-muted">ItemCode: ' + escapeHtml(cod) +
                        ' · CardCode: ' + escapeHtml(card) +
                        ' · ' + escapeHtml(emp) + '</div>' +
                        '<div class="mt-2">Consulta <code>/listaPreciosventa/' + escapeHtml(emp) +
                        '</code>, actualiza <strong>Precio lista</strong> y alinea <strong>Precio global</strong> si difiere de SAP.</div>' +
                        '</div>',
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0a0a0a'
                }).then(function (r) { if (r.isConfirmed) go(); });
                return;
            }
            if (window.confirm('¿Actualizar Precio lista de ' + cod + ' (' + card + ')?')) go();
        }

        function actualizarPrecioDesdeApi(btn) {
            var emp = btn.getAttribute('data-emp') || '';
            var cod = btn.getAttribute('data-cod') || '';
            var card = btn.getAttribute('data-card') || '';
            var nom = btn.getAttribute('data-nom') || cod;
            if (!emp || !cod) {
                toast('error', 'Datos incompletos', 'Falta empresa o ItemCode.');
                return;
            }
            if (!card) {
                toast('error', 'Sin CardCode', 'Se necesita CodigoCliente para consultar listaPreciosventa.');
                return;
            }
            var prevHtml = btn.innerHTML;
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            var payloadBase = {
                empresa: emp,
                card_code: card,
                producto_codigo: cod,
                mes: 0,
                anio: CC.state.anioGasto || new Date().getFullYear(),
                anio_proyeccion: costosAnio()
            };
            function parseJsonResponse(r) {
                return r.text().then(function (txt) {
                    var json = null;
                    try {
                        json = txt ? JSON.parse(txt) : null;
                    } catch (e) {
                        json = null;
                    }
                    if (!r.ok) {
                        var msg = (json && json.message)
                            || (r.status === 404
                                ? 'No se encontró el producto en listaPreciosventa.'
                                : (r.status === 504 || r.status === 408
                                    ? 'La consulta a SAP tardó demasiado. Intenta de nuevo.'
                                    : ('Error HTTP ' + r.status + (txt && !json ? ' (respuesta no JSON)' : ''))));
                        throw new Error(msg);
                    }
                    if (!json) {
                        throw new Error('La respuesta del servidor no es JSON válido.');
                    }
                    return json;
                });
            }
            fetch(url + '/actualizar-desde-api', {
                method: 'POST',
                headers: apiJsonHeaders(),
                body: JSON.stringify(Object.assign({}, payloadBase, { confirmar: false }))
            }).then(parseJsonResponse).then(function (json) {
                if (json.igual) {
                    toast('info', 'Sin cambios', 'El precio global ya coincide con la API (' +
                        moneyTxt(json.precio_api, json.moneda_api) + ').');
                    return;
                }
                var msg = 'Producto: ' + (nom || cod) + '\n' +
                    'ItemCode: ' + cod + ' · CardCode: ' + card + '\n\n' +
                    'Precio global local: ' + moneyTxt(json.precio_local, json.moneda_local) + '\n' +
                    'API (listaPreciosventa): ' + moneyTxt(json.precio_api, json.moneda_api) + '\n\n' +
                    '¿Actualizar solo el precio global con el valor de la API?';
                if (!window.confirm(msg)) return;

                return fetch(url + '/actualizar-desde-api', {
                    method: 'POST',
                    headers: apiJsonHeaders(),
                    body: JSON.stringify(Object.assign({}, payloadBase, { confirmar: true }))
                }).then(parseJsonResponse).then(function (j2) {
                    if (j2.actualizado) {
                        toast('success', 'Precio global actualizado',
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
                btn.removeAttribute('aria-busy');
                btn.innerHTML = prevHtml || '<i class="fa-solid fa-cloud-arrow-down"></i>';
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
            params.push('anio=' + encodeURIComponent(String(costosAnio())));
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
                    fillAnios((json && json.anios) || [], (json && json.anio) || costosAnio());
                    fillEmpresas();
                    render();
                })
                .catch(function () {
                    items = [];
                    total = 0;
                    from = 0;
                    to = 0;
                    lastPage = 1;
                    tbody.innerHTML = '<tr><td colspan="12"><div class="cc-empty">No se pudieron cargar los precios</div></td></tr>';
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
            qs += '&anio=' + encodeURIComponent(String(costosAnio()));
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
                    anio: costosAnio(),
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

        if (anioSel) anioSel.addEventListener('change', function () {
            paintAnioLabel(costosAnio());
            load(1);
        });
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
            function mesNombresCortos(meses) {
                var names = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                return (meses || []).map(function (m) {
                    var i = Number(m) - 1;
                    return (i >= 0 && i < 12) ? names[i] : String(m);
                }).join(', ');
            }
            function payloadImportApi(preview) {
                var emp = empSel ? String(empSel.value || '') : '';
                return {
                    empresa: emp,
                    todas_empresas: !emp,
                    anio: CC.state.anioGasto || 2026,
                    anio_proyeccion: costosAnio(),
                    solo_vacios: false,
                    preview: !!preview
                };
            }
            function htmlListaAfectados(json) {
                var list = (json && json.afectados) || [];
                var total = Number((json && json.afectados_total) || list.length) || 0;
                var anioProy = (json && json.anio_proyeccion) || costosAnio();
                var anioApi = (json && json.anio) || (CC.state.anioGasto || '');
                var html = '<div style="text-align:left;font-size:.88rem">' +
                    '<p style="margin:0 0 .6rem">Se actualizarán solo los <b>precios por mes (Ene–Dic)</b> de productos que ya están en tu maestro de la <b>proyección ' + escapeHtml(String(anioProy)) +
                    '</b> (API venta ' + escapeHtml(String(anioApi)) + '). El <b>precio global no se modifica</b>.</p>' +
                    '<p style="margin:0 0 .75rem"><b>' + total + '</b> producto(s) de tu tabla' +
                    (json.precios_filas ? (' · ' + json.precios_filas + ' fila(s) mensuales') : '') +
                    (json.omitidos_no_en_maestro ? (' · <span style="color:#b45309">' + json.omitidos_no_en_maestro + ' de la API omitidos (no están en tu tabla)</span>') : '') +
                    '</p>';
                if (!list.length) {
                    html += '<p class="text-muted" style="margin:0">Ningún producto de tu maestro coincide con la API.</p></div>';
                    return html;
                }
                html += '<div style="max-height:260px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px;padding:.5rem .65rem;background:#fafafa">';
                html += '<table style="width:100%;border-collapse:collapse;font-size:.78rem"><thead><tr>' +
                    '<th style="text-align:left;padding:.25rem">Empresa</th>' +
                    '<th style="text-align:left;padding:.25rem">Cliente</th>' +
                    '<th style="text-align:left;padding:.25rem">Producto</th>' +
                    '<th style="text-align:left;padding:.25rem">Meses</th>' +
                    '</tr></thead><tbody>';
                list.forEach(function (p) {
                    var cliente = (p.card_code || '') + (p.cliente ? (' · ' + p.cliente) : '');
                    var prod = (p.item_code || '') + (p.producto && p.producto !== p.item_code ? (' · ' + p.producto) : '');
                    var badge = p.es_nuevo ? ' <span style="color:#047857;font-weight:600">nuevo</span>' : '';
                    html += '<tr style="border-top:1px solid #eee">' +
                        '<td style="padding:.3rem .25rem;vertical-align:top">' + escapeHtml(p.empresa || '') + badge + '</td>' +
                        '<td style="padding:.3rem .25rem;vertical-align:top">' + escapeHtml(cliente) + '</td>' +
                        '<td style="padding:.3rem .25rem;vertical-align:top">' + escapeHtml(prod) + '</td>' +
                        '<td style="padding:.3rem .25rem;vertical-align:top">' + escapeHtml(mesNombresCortos(p.meses) || '—') + '</td>' +
                        '</tr>';
                });
                html += '</tbody></table>';
                if (total > list.length) {
                    html += '<div class="text-muted" style="margin-top:.45rem;font-size:.75rem">… y ' + (total - list.length) + ' más</div>';
                }
                html += '</div></div>';
                return html;
            }
            function ejecutarImportApi() {
                importBtn.disabled = true;
                if (hint) hint.textContent = 'Importando precios desde API…';
                return fetch(url + '/importar-api', {
                    method: 'POST',
                    headers: apiJsonHeaders(),
                    body: JSON.stringify(payloadImportApi(false))
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
            }
            importBtn.addEventListener('click', function () {
                importBtn.disabled = true;
                if (hint) hint.textContent = 'Consultando productos a actualizar…';
                fetch(url + '/importar-api', {
                    method: 'POST',
                    headers: apiJsonHeaders(),
                    body: JSON.stringify(payloadImportApi(true))
                }).then(function (r) {
                    return r.json().then(function (json) {
                        if (!r.ok) throw new Error((json && json.message) || 'No se pudo consultar la API');
                        return json;
                    });
                }).then(function (json) {
                    importBtn.disabled = false;
                    if (hint) hint.textContent = '';
                    var total = Number(json.afectados_total || (json.afectados && json.afectados.length) || 0) || 0;
                    if (!total) {
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Sin cambios',
                                text: json.message || 'No hay precios en la API para esta proyección.',
                                confirmButtonColor: '#0a0a0a'
                            });
                        } else {
                            window.alert(json.message || 'No hay precios para aplicar.');
                        }
                        return;
                    }
                    var html = htmlListaAfectados(json);
                    if (window.Swal) {
                        return Swal.fire({
                            icon: 'question',
                            title: '¿Actualizar precios?',
                            html: html,
                            width: '42rem',
                            showCancelButton: true,
                            focusCancel: true,
                            confirmButtonText: 'Sí, actualizar ' + total + ' producto(s)',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#0a0a0a',
                            cancelButtonColor: '#6b7280'
                        }).then(function (res) {
                            if (res && res.isConfirmed) ejecutarImportApi();
                        });
                    }
                    if (window.confirm((json.message || '') + '\n\n¿Deseas continuar?')) {
                        ejecutarImportApi();
                    }
                }).catch(function (err) {
                    importBtn.disabled = false;
                    toast('error', 'No se consultó', err && err.message ? err.message : 'Error');
                    if (hint) hint.textContent = 'Error al consultar API';
                });
            });
        }

        var listaBtn = document.getElementById('pv-costos-import-lista');
        if (listaBtn) {
            var listaUrl = CC.state.costosImportListaUrl || (url + '/importar-lista-precios');
            var EMPRESAS_LISTA = ['AUSTIN', 'IMSA', 'PITIC', 'SYDNEY'];
            function empresasListaTarget() {
                var emp = empSel ? String(empSel.value || '').toUpperCase() : '';
                if (emp) return [emp];
                var fromBoot = (CC.state.empresasLocales || []).map(function (e) {
                    return String(e.codigo || e.id || e.nombre || '').toUpperCase();
                }).filter(Boolean);
                var set = {};
                (fromBoot.length ? fromBoot : EMPRESAS_LISTA).forEach(function (k) { set[k] = true; });
                EMPRESAS_LISTA.forEach(function (k) { set[k] = true; });
                return Object.keys(set);
            }
            function payloadListaEmp(preview, emp) {
                var cliente = clienteEl ? String(clienteEl.value || '').trim() : '';
                var itemcode = itemEl ? String(itemEl.value || '').trim() : '';
                var payload = {
                    empresa: emp || '',
                    todas_empresas: false,
                    anio_proyeccion: costosAnio(),
                    preview: !!preview
                };
                // Respeta filtros de la pantalla (si no, reconsulta TODO IMSA y el guardado se cae/timeout).
                if (cliente) payload.cliente = cliente;
                if (itemcode) payload.itemcode = itemcode;
                return payload;
            }
            function parseListaResp(r) {
                return r.text().then(function (txt) {
                    var json = null;
                    try { json = txt ? JSON.parse(txt) : null; } catch (e) { json = null; }
                    if (!r.ok) {
                        var msg = (json && json.message) || ('HTTP ' + r.status);
                        if (r.status === 504 || r.status === 502 || /maximum execution|timeout/i.test(txt || '')) {
                            msg = 'La consulta a SAP agotó el tiempo. Intenta de nuevo (puede tardar varios minutos por empresa).';
                        }
                        throw new Error(msg);
                    }
                    return json || {};
                });
            }
            function mergeListaJson(acc, part, emp) {
                if (!acc) {
                    acc = {
                        ok: true,
                        preview: !!(part && part.preview),
                        anio_proyeccion: (part && part.anio_proyeccion) || costosAnio(),
                        afectados: [],
                        afectados_total: 0,
                        omitidos_sin_lista: 0,
                        omitidos_sin_card: 0,
                        clientes_consultados: 0,
                        productos_unicos: 0,
                        importados: 0,
                        propagadas: 0,
                        errores_clientes: 0,
                        empresas_ok: [],
                        empresas_error: []
                    };
                }
                if (!part || part.ok === false) {
                    acc.empresas_error.push(emp + ': ' + ((part && part.message) || 'error'));
                    return acc;
                }
                acc.empresas_ok.push(emp);
                acc.afectados = (acc.afectados || []).concat((part.afectados || []).slice(0, 80));
                acc.afectados_total += Number(part.afectados_total || 0) || 0;
                acc.omitidos_sin_lista += Number(part.omitidos_sin_lista || 0) || 0;
                acc.omitidos_sin_card += Number(part.omitidos_sin_card || 0) || 0;
                acc.clientes_consultados += Number(part.clientes_consultados || 0) || 0;
                acc.productos_unicos += Number(part.productos_unicos || 0) || 0;
                acc.importados += Number(part.importados || 0) || 0;
                acc.propagadas += Number(part.propagadas || 0) || 0;
                acc.errores_clientes += Number(part.errores_clientes || 0) || 0;
                return acc;
            }
            function consultarListaPorEmpresas(preview, onProgress) {
                var empresas = empresasListaTarget();
                var i = 0;
                var acc = null;
                function next() {
                    if (i >= empresas.length) {
                        if (acc) {
                            var nEmp = (acc.empresas_ok || []).length;
                            acc.message = preview
                                ? ('Vista previa: ' + (acc.afectados_total || 0) + ' producto(s) con precio en lista SAP'
                                    + (nEmp > 1 ? (' · ' + nEmp + ' empresas') : '')
                                    + (acc.omitidos_sin_card ? (' · ' + acc.omitidos_sin_card + ' sin CardCode') : '')
                                    + '.')
                                : ('Precio global actualizado en ' + (acc.importados || 0) + ' producto(s)'
                                    + (nEmp > 1 ? (' · ' + nEmp + ' empresas') : '') + '.');
                            if ((acc.empresas_error || []).length) {
                                acc.message += ' Avisos: ' + acc.empresas_error.slice(0, 3).join('; ');
                            }
                        }
                        return Promise.resolve(acc || { ok: true, afectados_total: 0, afectados: [], message: 'Sin datos.' });
                    }
                    var emp = empresas[i++];
                    if (typeof onProgress === 'function') onProgress(emp, i, empresas.length);
                    return fetch(listaUrl, {
                        method: 'POST',
                        headers: apiJsonHeaders(),
                        body: JSON.stringify(payloadListaEmp(preview, emp))
                    }).then(parseListaResp).then(function (json) {
                        acc = mergeListaJson(acc, json, emp);
                        return next();
                    }).catch(function (err) {
                        acc = mergeListaJson(acc, { ok: false, message: err && err.message ? err.message : 'Error' }, emp);
                        return next();
                    });
                }
                return next();
            }
            function htmlListaGlobal(json) {
                var list = (json && json.afectados) || [];
                var total = Number((json && json.afectados_total) || list.length) || 0;
                var anioProy = (json && json.anio_proyeccion) || costosAnio();
                var html = '<div style="text-align:left;font-size:.88rem">' +
                    '<p style="margin:0 0 .6rem">Se actualizará solo el <b>precio global</b> (mes = 0) desde la <b>lista de precios SAP</b> ' +
                    'de cada cliente, en la proyección <b>' + escapeHtml(String(anioProy)) + '</b>. ' +
                    'Los precios por mes no se tocan. Solo productos de tu maestro' +
                    (json.omitidos_sin_card ? (' (filas sin CardCode no se pueden sincronizar)') : '') +
                    '.</p>' +
                    '<p style="margin:0 0 .75rem"><b>' + total + '</b> producto(s)' +
                    (json.omitidos_sin_lista ? (' · <span style="color:#b45309">' + json.omitidos_sin_lista + ' sin precio en lista</span>') : '') +
                    (json.omitidos_sin_card ? (' · <span style="color:#b45309">' + json.omitidos_sin_card + ' sin CardCode</span>') : '') +
                    (json.clientes_consultados ? (' · ' + json.clientes_consultados + ' cliente(s)') : '') +
                    ((json.empresas_ok && json.empresas_ok.length > 1) ? (' · empresas: ' + json.empresas_ok.join(', ')) : '') +
                    '</p>';
                if (!list.length) {
                    html += '<p class="text-muted" style="margin:0">Ningún producto coincide con la lista de precios.</p></div>';
                    return html;
                }
                html += '<div style="max-height:260px;overflow:auto;border:1px solid #e5e7eb;border-radius:8px;padding:.5rem .65rem;background:#fafafa">';
                html += '<table style="width:100%;border-collapse:collapse;font-size:.78rem"><thead><tr>' +
                    '<th style="text-align:left;padding:.25rem">Empresa</th>' +
                    '<th style="text-align:left;padding:.25rem">Cliente</th>' +
                    '<th style="text-align:left;padding:.25rem">Producto</th>' +
                    '<th style="text-align:right;padding:.25rem">Antes</th>' +
                    '<th style="text-align:right;padding:.25rem">Lista</th>' +
                    '</tr></thead><tbody>';
                list.forEach(function (p) {
                    var cliente = (p.card_code || '') + (p.cliente ? (' · ' + p.cliente) : '');
                    var prod = (p.item_code || '') + (p.producto && p.producto !== p.item_code ? (' · ' + p.producto) : '');
                    var mon = String(p.moneda || 'MXN').toUpperCase();
                    var fmt = function (n) {
                        var v = Number(n) || 0;
                        return (mon === 'USD' ? 'US$' : '$') + v.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    };
                    html += '<tr style="border-top:1px solid #eee">' +
                        '<td style="padding:.3rem .25rem;vertical-align:top">' + escapeHtml(p.empresa || '') + '</td>' +
                        '<td style="padding:.3rem .25rem;vertical-align:top">' + escapeHtml(cliente) + '</td>' +
                        '<td style="padding:.3rem .25rem;vertical-align:top">' + escapeHtml(prod) +
                        (p.lista ? ('<div class="text-muted" style="font-size:.7rem">' + escapeHtml((p.no_lista ? ('#' + p.no_lista + ' ') : '') + p.lista) + '</div>') : '') +
                        '</td>' +
                        '<td style="padding:.3rem .25rem;vertical-align:top;text-align:right">' + escapeHtml(fmt(p.precio_anterior)) + '</td>' +
                        '<td style="padding:.3rem .25rem;vertical-align:top;text-align:right;font-weight:600">' + escapeHtml(fmt(p.precio)) + '</td>' +
                        '</tr>';
                });
                html += '</tbody></table>';
                if (total > list.length) {
                    html += '<div class="text-muted" style="margin-top:.45rem;font-size:.75rem">… y ' + (total - list.length) + ' más</div>';
                }
                html += '</div></div>';
                return html;
            }
            function ejecutarLista(afectadosPreview) {
                listaBtn.disabled = true;
                if (hint) hint.textContent = 'Guardando precios en el maestro…';
                var emp = empSel ? String(empSel.value || '').toUpperCase() : (empresasListaTarget()[0] || '');
                var list = Array.isArray(afectadosPreview) ? afectadosPreview : [];
                // Guarda lo del preview (sin reconsultar SAP).
                if (list.length) {
                    return fetch(listaUrl, {
                        method: 'POST',
                        headers: apiJsonHeaders(),
                        body: JSON.stringify({
                            empresa: emp,
                            todas_empresas: false,
                            anio_proyeccion: costosAnio(),
                            preview: false,
                            afectados: list.map(function (p) {
                                return {
                                    empresa: p.empresa || emp,
                                    card_code: p.card_code || '',
                                    cliente: p.cliente || '',
                                    item_code: p.item_code || '',
                                    producto: p.producto || '',
                                    precio: Number(p.precio) || 0,
                                    moneda: p.moneda || 'MXN'
                                };
                            })
                        })
                    }).then(parseListaResp).then(function (json) {
                        var n = Number(json && json.importados) || 0;
                        if (n < 1) {
                            var failMsg = (json && json.message) || 'No se guardó ningún precio.';
                            toast('error', 'No se guardó', failMsg);
                            if (hint) hint.textContent = failMsg;
                            return;
                        }
                        toast('success', 'Lista de precios', json.message || ('Guardados ' + n + ' producto(s).'));
                        if (hint) hint.textContent = '';
                        load();
                    }).catch(function (err) {
                        toast('error', 'No se actualizó', err && err.message ? err.message : 'Error');
                        if (hint) hint.textContent = 'Error al guardar desde lista';
                    }).then(function () {
                        listaBtn.disabled = false;
                    });
                }
                // Fallback: reconsultar API (mismo filtro de pantalla).
                return consultarListaPorEmpresas(false, function (e, idx, totalEmp) {
                    if (hint) hint.textContent = 'Guardando ' + e + ' (' + idx + '/' + totalEmp + ')…';
                }).then(function (json) {
                    var n = Number(json && json.importados) || 0;
                    var errs = (json && json.empresas_error) || [];
                    if (n < 1) {
                        var failMsg = (errs.length ? errs.join('; ') : '') ||
                            (json && json.message) ||
                            'No se guardó ningún precio. Revisa permisos o vuelve a intentar.';
                        toast('error', 'No se guardó', failMsg);
                        if (hint) hint.textContent = failMsg.length > 90 ? (failMsg.slice(0, 87) + '…') : failMsg;
                        return;
                    }
                    toast('success', 'Lista de precios', (json && json.message) || ('Precio global actualizado en ' + n + ' producto(s).'));
                    if (hint) hint.textContent = '';
                    load();
                }).catch(function (err) {
                    toast('error', 'No se actualizó', err && err.message ? err.message : 'Error');
                    if (hint) hint.textContent = 'Error al actualizar desde lista';
                }).then(function () {
                    listaBtn.disabled = false;
                });
            }
            listaBtn.addEventListener('click', function () {
                listaBtn.disabled = true;
                var targets = empresasListaTarget();
                var clienteFiltro = clienteEl ? String(clienteEl.value || '').trim() : '';
                if (hint) {
                    hint.textContent = targets.length > 1
                        ? ('Consultando listas por empresa (1/' + targets.length + ')…')
                        : (clienteFiltro
                            ? ('Consultando lista SAP de ' + clienteFiltro + '…')
                            : 'Consultando listas de precios…');
                }
                consultarListaPorEmpresas(true, function (emp, idx, totalEmp) {
                    if (hint) hint.textContent = 'Consultando ' + emp + ' (' + idx + '/' + totalEmp + ')…';
                }).then(function (json) {
                    listaBtn.disabled = false;
                    if (hint) hint.textContent = '';
                    var total = Number(json.afectados_total || (json.afectados && json.afectados.length) || 0) || 0;
                    var afectadosPreview = (json && json.afectados) ? json.afectados.slice() : [];
                    if (!total) {
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Sin cambios',
                                text: json.message || 'Ningún producto de tu maestro tiene precio en lista.',
                                confirmButtonColor: '#0a0a0a'
                            });
                        } else {
                            window.alert(json.message || 'Sin coincidencias.');
                        }
                        return;
                    }
                    if (afectadosPreview.length < total) {
                        toast('warning', 'Vista parcial', 'Se muestran ' + afectadosPreview.length + ' de ' + total +
                            '. Filtra por CardCode para guardar el lote completo con seguridad.');
                    }
                    var filtroTxt = clienteFiltro
                        ? ('<p style="margin:0 0 .75rem;color:#b45309">Filtro activo: <b>' + escapeHtml(clienteFiltro) + '</b>. Solo se actualizarán filas de ese cliente.</p>')
                        : '<p style="margin:0 0 .75rem;color:#b45309">Sin filtro de cliente: conviene filtrar por CardCode (ej. D139) antes de guardar.</p>';
                    if (window.Swal) {
                        return Swal.fire({
                            icon: 'question',
                            title: '¿Guardar precio global?',
                            html: filtroTxt + htmlListaGlobal(json),
                            width: '46rem',
                            showCancelButton: true,
                            focusCancel: true,
                            confirmButtonText: 'Sí, guardar ' + Math.min(total, afectadosPreview.length) + ' producto(s)',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#0a0a0a',
                            cancelButtonColor: '#6b7280'
                        }).then(function (res) {
                            if (res && res.isConfirmed) return ejecutarLista(afectadosPreview);
                        });
                    }
                    if (window.confirm((json.message || '') + '\n\n¿Deseas guardar estos precios?')) {
                        return ejecutarLista(afectadosPreview);
                    }
                }).catch(function (err) {
                    listaBtn.disabled = false;
                    var msg = (err && err.message) ? String(err.message) : 'Error al consultar listas';
                    if (/timeout|timed out|network|failed to fetch|aborterror/i.test(msg)) {
                        msg = 'La consulta a SAP agotó el tiempo. Filtra por CardCode (ej. D139) e inténtalo de nuevo.';
                    }
                    toast('error', 'No se consultó', msg);
                    if (hint) hint.textContent = msg.length > 90 ? (msg.slice(0, 87) + '…') : msg;
                });
            });
        }

        var cerosBtn = document.getElementById('pv-costos-rellenar-ceros');
        if (cerosBtn) {
            var vaciosUrl = CC.state.costosVaciosUrl || (url + '/vacios');
            var loteUrl = CC.state.costosRellenarVaciosLoteUrl || (url + '/rellenar-vacios-lote');
            var cerosEmpEl = document.getElementById('pv-ceros-empresa');
            var cerosAnioLabel = document.getElementById('pv-ceros-anio-label');
            var cerosHint = document.getElementById('pv-ceros-hint');
            var cerosEjecutar = document.getElementById('pv-ceros-ejecutar');
            var cerosCancelar = document.getElementById('pv-ceros-cancelar');
            var cerosProgWrap = document.getElementById('pv-ceros-progress-wrap');
            var cerosProgLabel = document.getElementById('pv-ceros-progress-label');
            var cerosProgPct = document.getElementById('pv-ceros-progress-pct');
            var cerosProgBar = document.getElementById('pv-ceros-progress-bar');
            var cerosProgDetail = document.getElementById('pv-ceros-progress-detail');
            var cerosBusy = false;
            var cerosAbort = false;

            function openCerosModal() {
                cerosAbort = false;
                if (cerosAnioLabel) cerosAnioLabel.textContent = 'Proy. ' + costosAnio();
                if (cerosEmpEl) {
                    var empFiltro = empSel ? String(empSel.value || '').toUpperCase() : '';
                    if (empFiltro) cerosEmpEl.value = empFiltro;
                }
                if (cerosProgWrap) cerosProgWrap.style.display = 'none';
                if (cerosProgBar) cerosProgBar.style.width = '0%';
                if (cerosHint) {
                    cerosHint.textContent = 'Se procesa un CardCode por request para evitar timeouts.';
                }
                showModal('modalPvRellenarCeros');
            }

            function parseCerosResp(r) {
                return r.text().then(function (txt) {
                    var json = null;
                    try { json = txt ? JSON.parse(txt) : null; } catch (e) { json = null; }
                    if (!r.ok) {
                        var msg = (json && json.message) || ('HTTP ' + r.status);
                        if (r.status === 504 || r.status === 502 || /maximum execution|timeout/i.test(txt || '')) {
                            msg = 'Timeout en este cliente. Se puede continuar con el siguiente.';
                        }
                        throw new Error(msg);
                    }
                    return json || {};
                });
            }

            function setCerosProgress(idx, total, card, extra) {
                var pct = total > 0 ? Math.round((idx / total) * 100) : 0;
                if (cerosProgWrap) cerosProgWrap.style.display = '';
                if (cerosProgLabel) {
                    cerosProgLabel.textContent = card
                        ? ('Cliente ' + idx + '/' + total + ' · ' + card)
                        : ('Preparando…');
                }
                if (cerosProgPct) cerosProgPct.textContent = pct + '%';
                if (cerosProgBar) cerosProgBar.style.width = pct + '%';
                if (cerosProgDetail) cerosProgDetail.textContent = extra || '';
                if (hint) hint.textContent = extra || ('Rellenando $0… ' + pct + '%');
            }

            function procesarLotesPorCliente(emp, clientes) {
                var i = 0;
                var totalCli = clientes.length;
                var importados = 0;
                var omitidos = 0;
                var errores = [];
                var okCli = 0;

                function next() {
                    if (cerosAbort) {
                        return Promise.resolve({
                            aborted: true,
                            importados: importados,
                            omitidos_sin_lista: omitidos,
                            errores: errores,
                            clientes_ok: okCli,
                            clientes_total: totalCli
                        });
                    }
                    if (i >= totalCli) {
                        return Promise.resolve({
                            aborted: false,
                            importados: importados,
                            omitidos_sin_lista: omitidos,
                            errores: errores,
                            clientes_ok: okCli,
                            clientes_total: totalCli
                        });
                    }
                    var cli = clientes[i++];
                    var card = String(cli.card_code || '').trim();
                    var items = Array.isArray(cli.items) ? cli.items : [];
                    setCerosProgress(i, totalCli, card,
                        'Actualizados: ' + importados +
                        (omitidos ? (' · sin lista: ' + omitidos) : '') +
                        (errores.length ? (' · errores: ' + errores.length) : ''));
                    if (cerosHint) {
                        cerosHint.textContent = 'Consultando SAP · ' + card +
                            (cli.cliente ? (' · ' + cli.cliente) : '') +
                            ' (' + items.length + ' ítem(s) en $0)…';
                    }

                    return fetch(loteUrl, {
                        method: 'POST',
                        headers: apiJsonHeaders(),
                        body: JSON.stringify({
                            empresa: emp,
                            card_code: card,
                            cliente: cli.cliente || '',
                            anio_proyeccion: costosAnio(),
                            items: items.map(function (it) {
                                return {
                                    item_code: it.item_code || it.codigo || '',
                                    producto: it.producto || '',
                                    moneda: it.moneda || 'MXN'
                                };
                            })
                        })
                    }).then(parseCerosResp).then(function (json) {
                        okCli++;
                        importados += Number(json.importados) || 0;
                        omitidos += Number(json.omitidos_sin_lista) || 0;
                        return next();
                    }).catch(function (err) {
                        errores.push(card + ': ' + (err && err.message ? err.message : 'Error'));
                        // Continúa con el siguiente cliente aunque falle uno.
                        return next();
                    });
                }

                return next();
            }

            cerosBtn.addEventListener('click', function () {
                openCerosModal();
            });

            if (cerosCancelar) {
                cerosCancelar.addEventListener('click', function () {
                    if (cerosBusy) cerosAbort = true;
                });
            }

            if (cerosEjecutar) {
                cerosEjecutar.addEventListener('click', function () {
                    if (cerosBusy) return;
                    var emp = cerosEmpEl ? String(cerosEmpEl.value || '').toUpperCase() : '';
                    if (!emp) {
                        toast('warning', 'Empresa', 'Selecciona una empresa.');
                        return;
                    }
                    cerosBusy = true;
                    cerosAbort = false;
                    cerosEjecutar.disabled = true;
                    cerosBtn.disabled = true;
                    if (cerosEmpEl) cerosEmpEl.disabled = true;
                    var prevHtml = cerosEjecutar.innerHTML;
                    cerosEjecutar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Armando JSON…';
                    setCerosProgress(0, 1, '', 'Leyendo maestro local (solo $0)…');
                    if (cerosHint) cerosHint.textContent = 'Armando JSON local de ' + emp + '…';

                    var qs = '?empresa=' + encodeURIComponent(emp) +
                        '&anio_proyeccion=' + encodeURIComponent(String(costosAnio()));

                    fetch(vaciosUrl + qs, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(parseCerosResp).then(function (json) {
                        var clientes = (json && json.clientes) || [];
                        var total = Number(json.total) || 0;
                        var sinCard = Number(json.sin_card) || 0;
                        if (!clientes.length) {
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Nada que rellenar',
                                    text: json.message || ('No hay productos en $0 con CardCode en ' + emp + '.'),
                                    confirmButtonColor: '#0a0a0a'
                                });
                            } else {
                                window.alert(json.message || 'Nada que rellenar.');
                            }
                            return null;
                        }
                        var askHtml = '<div style="text-align:left;font-size:.9rem">' +
                            '<p style="margin:0 0 .5rem">Empresa <b>' + escapeHtml(emp) + '</b> · proyección <b>' +
                            escapeHtml(String(costosAnio())) + '</b>.</p>' +
                            '<p style="margin:0 0 .5rem"><b>' + total + '</b> producto(s) en $0 · <b>' +
                            clientes.length + '</b> cliente(s) a consultar en SAP.</p>' +
                            (sinCard ? ('<p style="margin:0;color:#b45309">' + sinCard +
                                ' sin CardCode se omiten.</p>') : '') +
                            '<p style="margin:.65rem 0 0;color:#71717a;font-size:.82rem">Se actualizará un CardCode a la vez. Puedes cancelar; lo ya guardado se conserva.</p></div>';

                        var ask = window.Swal
                            ? Swal.fire({
                                icon: 'question',
                                title: '¿Rellenar precios en $0?',
                                html: askHtml,
                                width: '36rem',
                                showCancelButton: true,
                                focusCancel: true,
                                confirmButtonText: 'Sí, procesar ' + clientes.length + ' cliente(s)',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#0a0a0a',
                                cancelButtonColor: '#6b7280'
                            }).then(function (res) { return !!(res && res.isConfirmed); })
                            : Promise.resolve(window.confirm((json.message || '') + '\n\n¿Continuar?'));

                        return ask.then(function (ok) {
                            if (!ok) return null;
                            cerosEjecutar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando…';
                            return procesarLotesPorCliente(emp, clientes);
                        });
                    }).then(function (res) {
                        if (!res) return;
                        var n = Number(res.importados) || 0;
                        var msg = (res.aborted ? 'Detenido. ' : '') +
                            n + ' producto(s) actualizado(s)' +
                            ' · ' + (res.clientes_ok || 0) + '/' + (res.clientes_total || 0) + ' cliente(s)' +
                            (res.omitidos_sin_lista ? (' · ' + res.omitidos_sin_lista + ' sin lista SAP') : '') +
                            ((res.errores && res.errores.length) ? (' · ' + res.errores.length + ' con error') : '');
                        if (n > 0) {
                            toast('success', res.aborted ? 'Parcial' : 'Precios rellenados', msg);
                            if (empSel) empSel.value = emp;
                            load();
                        } else if (res.errores && res.errores.length) {
                            toast('error', 'No se rellenó', res.errores.slice(0, 2).join('; '));
                        } else {
                            toast('info', 'Sin cambios', msg || 'Ningún precio se actualizó (sin match en lista SAP).');
                        }
                        if (!res.aborted && n > 0) {
                            hideModal('modalPvRellenarCeros');
                        }
                    }).catch(function (err) {
                        toast('error', 'No se rellenó', err && err.message ? err.message : 'Error');
                    }).then(function () {
                        cerosBusy = false;
                        cerosAbort = false;
                        cerosEjecutar.disabled = false;
                        cerosBtn.disabled = false;
                        if (cerosEmpEl) cerosEmpEl.disabled = false;
                        cerosEjecutar.innerHTML = prevHtml || '<i class="fa-solid fa-cloud-arrow-down"></i> Consultar y rellenar';
                        if (cerosHint) {
                            cerosHint.textContent = 'Se procesa un CardCode por request para evitar timeouts.';
                        }
                        if (hint) hint.textContent = '';
                    });
                });
            }
        }

        var plistaBtn = document.getElementById('pv-costos-sync-precio-lista');
        if (plistaBtn) {
            var plistaListUrl = CC.state.costosSinPrecioListaUrl || (url + '/sin-precio-lista');
            var plistaLoteUrl = CC.state.costosSyncPrecioListaLoteUrl || (url + '/sincronizar-precio-lista-lote');
            var plistaEmpEl = document.getElementById('pv-plista-empresa');
            var plistaAnioLabel = document.getElementById('pv-plista-anio-label');
            var plistaHint = document.getElementById('pv-plista-hint');
            var plistaEjecutar = document.getElementById('pv-plista-ejecutar');
            var plistaCancelar = document.getElementById('pv-plista-cancelar');
            var plistaProgWrap = document.getElementById('pv-plista-progress-wrap');
            var plistaProgLabel = document.getElementById('pv-plista-progress-label');
            var plistaProgPct = document.getElementById('pv-plista-progress-pct');
            var plistaProgBar = document.getElementById('pv-plista-progress-bar');
            var plistaProgDetail = document.getElementById('pv-plista-progress-detail');
            var plistaBusy = false;
            var plistaAbort = false;
            var plistaSyncGlobalEl = document.getElementById('pv-plista-sync-global');
            var plistaTodosEl = document.getElementById('pv-plista-todos');

            function openPlistaModal() {
                plistaAbort = false;
                if (plistaAnioLabel) plistaAnioLabel.textContent = 'Proy. ' + costosAnio();
                if (plistaEmpEl) {
                    var empFiltro = empSel ? String(empSel.value || '').toUpperCase() : '';
                    if (empFiltro) plistaEmpEl.value = empFiltro;
                }
                if (plistaProgWrap) plistaProgWrap.style.display = 'none';
                if (plistaProgBar) plistaProgBar.style.width = '0%';
                if (plistaHint) {
                    plistaHint.textContent = 'Endpoint: /listaPreciosventa/' + (plistaEmpEl && plistaEmpEl.value ? plistaEmpEl.value : '{EMPRESA}');
                }
                showModal('modalPvSyncPrecioLista');
            }

            function parsePlistaResp(r) {
                return r.text().then(function (txt) {
                    var json = null;
                    try { json = txt ? JSON.parse(txt) : null; } catch (e) { json = null; }
                    if (!r.ok) {
                        var msg = (json && json.message) || ('HTTP ' + r.status);
                        if (r.status === 504 || r.status === 502 || /maximum execution|timeout/i.test(txt || '')) {
                            msg = 'Timeout en este cliente. Se puede continuar con el siguiente.';
                        }
                        throw new Error(msg);
                    }
                    return json || {};
                });
            }

            function setPlistaProgress(idx, total, card, extra) {
                var pct = total > 0 ? Math.round((idx / total) * 100) : 0;
                if (plistaProgWrap) plistaProgWrap.style.display = '';
                if (plistaProgLabel) {
                    plistaProgLabel.textContent = card
                        ? ('Cliente ' + idx + '/' + total + ' · ' + card)
                        : 'Preparando…';
                }
                if (plistaProgPct) plistaProgPct.textContent = pct + '%';
                if (plistaProgBar) plistaProgBar.style.width = pct + '%';
                if (plistaProgDetail) plistaProgDetail.textContent = extra || '';
                if (hint) hint.textContent = extra || ('Obteniendo Precio lista… ' + pct + '%');
            }

            function procesarPlistaLotes(emp, clientes, opts) {
                opts = opts || {};
                var soloVacios = opts.solo_vacios !== false;
                var syncGlobal = opts.sync_precio_global !== false;
                var i = 0;
                var totalCli = clientes.length;
                var actualizados = 0;
                var actualizadosGlobal = 0;
                var omitidos = 0;
                var errores = [];
                var okCli = 0;

                function next() {
                    if (plistaAbort) {
                        return Promise.resolve({
                            aborted: true,
                            actualizados: actualizados,
                            actualizados_global: actualizadosGlobal,
                            omitidos_sin_lista: omitidos,
                            errores: errores,
                            clientes_ok: okCli,
                            clientes_total: totalCli
                        });
                    }
                    if (i >= totalCli) {
                        return Promise.resolve({
                            aborted: false,
                            actualizados: actualizados,
                            actualizados_global: actualizadosGlobal,
                            omitidos_sin_lista: omitidos,
                            errores: errores,
                            clientes_ok: okCli,
                            clientes_total: totalCli
                        });
                    }
                    var cli = clientes[i++];
                    var card = String(cli.card_code || '').trim();
                    var items = Array.isArray(cli.items) ? cli.items : [];
                    setPlistaProgress(i, totalCli, card,
                        'Actualizados: ' + actualizados +
                        (actualizadosGlobal ? (' · global: ' + actualizadosGlobal) : '') +
                        (omitidos ? (' · sin lista: ' + omitidos) : '') +
                        (errores.length ? (' · errores: ' + errores.length) : ''));
                    if (plistaHint) {
                        plistaHint.textContent = 'Consultando /listaPreciosventa/' + emp + ' · ' + card +
                            ' (' + items.length + ' ítem(s))…';
                    }

                    return fetch(plistaLoteUrl, {
                        method: 'POST',
                        headers: apiJsonHeaders(),
                        body: JSON.stringify({
                            empresa: emp,
                            card_code: card,
                            cliente: cli.cliente || '',
                            anio_proyeccion: costosAnio(),
                            solo_vacios: soloVacios,
                            sync_precio_global: syncGlobal,
                            items: items.map(function (it) {
                                return {
                                    item_code: it.item_code || it.codigo || '',
                                    producto: it.producto || '',
                                    moneda: it.moneda || 'MXN'
                                };
                            })
                        })
                    }).then(parsePlistaResp).then(function (json) {
                        okCli++;
                        actualizados += Number(json.actualizados) || 0;
                        actualizadosGlobal += Number(json.actualizados_global) || 0;
                        omitidos += Number(json.omitidos_sin_lista) || 0;
                        return next();
                    }).catch(function (err) {
                        errores.push(card + ': ' + (err && err.message ? err.message : 'Error'));
                        return next();
                    });
                }

                return next();
            }

            plistaBtn.addEventListener('click', function () {
                if (!puedeEditar) {
                    toast('warning', 'Sin permiso', 'No puedes editar precios del maestro.');
                    return;
                }
                openPlistaModal();
            });

            if (plistaCancelar) {
                plistaCancelar.addEventListener('click', function () {
                    if (plistaBusy) plistaAbort = true;
                });
            }

            if (plistaEjecutar) {
                plistaEjecutar.addEventListener('click', function () {
                    if (plistaBusy) return;
                    var emp = plistaEmpEl ? String(plistaEmpEl.value || '').toUpperCase() : '';
                    if (!emp) {
                        toast('warning', 'Empresa', 'Selecciona una empresa.');
                        return;
                    }
                    plistaBusy = true;
                    plistaAbort = false;
                    plistaEjecutar.disabled = true;
                    plistaBtn.disabled = true;
                    if (plistaEmpEl) plistaEmpEl.disabled = true;
                    var prevHtml = plistaEjecutar.innerHTML;
                    plistaEjecutar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Armando…';
                    // Si no hay DOM de opciones, asumir ambas activas (caso típico: corregir vs SAP).
                    var soloVaciosPlista = plistaTodosEl ? !plistaTodosEl.checked : false;
                    var syncGlobalPlista = plistaSyncGlobalEl ? !!plistaSyncGlobalEl.checked : true;
                    setPlistaProgress(0, 1, '', soloVaciosPlista
                        ? 'Leyendo maestro local (sin Precio lista)…'
                        : 'Leyendo maestro local (todos los productos)…');

                    var qs = '?empresa=' + encodeURIComponent(emp) +
                        '&anio_proyeccion=' + encodeURIComponent(String(costosAnio())) +
                        '&solo_vacios=' + (soloVaciosPlista ? '1' : '0');

                    fetch(plistaListUrl + qs, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(parsePlistaResp).then(function (json) {
                        var clientes = (json && json.clientes) || [];
                        var total = Number(json.total) || 0;
                        var sinCard = Number(json.sin_card) || 0;
                        if (!clientes.length) {
                            if (window.Swal) {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Nada que sincronizar',
                                    text: json.message || ('No hay productos sin Precio lista en ' + emp + '.'),
                                    confirmButtonColor: '#0a0a0a'
                                });
                            } else {
                                window.alert(json.message || 'Nada que sincronizar.');
                            }
                            return null;
                        }
                        var askHtml = '<div style="text-align:left;font-size:.9rem">' +
                            '<p style="margin:0 0 .5rem">Empresa <b>' + escapeHtml(emp) + '</b> · ' +
                            '<code>/listaPreciosventa/' + escapeHtml(emp) + '</code></p>' +
                            '<p style="margin:0 0 .5rem"><b>' + total + '</b> producto(s) · <b>' +
                            clientes.length + '</b> cliente(s).</p>' +
                            (sinCard ? ('<p style="margin:0;color:#b45309">' + sinCard +
                                ' sin CardCode se omiten.</p>') : '') +
                            '<p style="margin:.65rem 0 0;color:#71717a;font-size:.82rem">' +
                            (syncGlobalPlista
                                ? 'Se actualiza Precio lista y Precio global si difiere de SAP.'
                                : 'Solo se actualiza Precio lista (Precio global no cambia).') +
                            '</p></div>';

                        var ask = window.Swal
                            ? Swal.fire({
                                icon: 'question',
                                title: '¿Sincronizar con SAP?',
                                html: askHtml,
                                width: '36rem',
                                showCancelButton: true,
                                focusCancel: true,
                                confirmButtonText: 'Sí, procesar ' + clientes.length + ' cliente(s)',
                                cancelButtonText: 'Cancelar',
                                confirmButtonColor: '#0a0a0a',
                                cancelButtonColor: '#6b7280'
                            }).then(function (res) { return !!(res && res.isConfirmed); })
                            : Promise.resolve(window.confirm((json.message || '') + '\n\n¿Continuar?'));

                        return ask.then(function (ok) {
                            if (!ok) return null;
                            plistaEjecutar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando…';
                            return procesarPlistaLotes(emp, clientes, {
                                solo_vacios: soloVaciosPlista,
                                sync_precio_global: syncGlobalPlista
                            });
                        });
                    }).then(function (res) {
                        if (!res) return;
                        var n = Number(res.actualizados) || 0;
                        var nG = Number(res.actualizados_global) || 0;
                        var msg = (res.aborted ? 'Detenido. ' : '') +
                            n + ' fila(s) actualizada(s)' +
                            (nG ? (' · Precio global: ' + nG) : '') +
                            ' · ' + (res.clientes_ok || 0) + '/' + (res.clientes_total || 0) + ' cliente(s)' +
                            (res.omitidos_sin_lista ? (' · ' + res.omitidos_sin_lista + ' sin lista SAP') : '') +
                            ((res.errores && res.errores.length) ? (' · ' + res.errores.length + ' con error') : '');
                        if (n > 0) {
                            toast('success', res.aborted ? 'Parcial' : 'Sincronización lista', msg);
                            if (empSel) empSel.value = emp;
                            load();
                        } else if (res.errores && res.errores.length) {
                            toast('error', 'No se actualizó', res.errores.slice(0, 2).join('; '));
                        } else {
                            toast('info', 'Sin cambios', msg || 'Ningún Precio lista se actualizó.');
                        }
                        if (!res.aborted && n > 0) {
                            hideModal('modalPvSyncPrecioLista');
                        }
                    }).catch(function (err) {
                        toast('error', 'No se actualizó', err && err.message ? err.message : 'Error');
                    }).then(function () {
                        plistaBusy = false;
                        plistaAbort = false;
                        plistaEjecutar.disabled = false;
                        plistaBtn.disabled = false;
                        if (plistaEmpEl) plistaEmpEl.disabled = false;
                        plistaEjecutar.innerHTML = prevHtml || '<i class="fa-solid fa-cloud-arrow-down"></i> Obtener precios';
                        if (plistaHint) {
                            plistaHint.textContent = 'Endpoint por empresa: /listaPreciosventa/AUSTIN|IMSA|PITIC|SYDNEY';
                        }
                        if (hint) hint.textContent = '';
                    });
                });
            }
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
                var qs = '?anio=' + encodeURIComponent(String(costosAnio()));
                if (emp) qs += '&empresa=' + encodeURIComponent(emp);
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
                fd.append('anio', String(costosAnio()));
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

        fillAnios([], costosAnio());
        load();
    }

    CC.boot = function (boot) {
        CC.state.page = boot.page;
        CC.state.anioGasto = boot.anioGasto || 2026;
        CC.state.anioPresupuesto = boot.anioPresupuesto || 2027;
        CC.state.costosAnio = boot.anioPresupuesto || 2027;
        CC.state.usuarios = boot.usuarios || [];
        CC.state.centroInicial = boot.centroInicial || '';
        CC.state.empresaInicial = boot.empresaInicial || '';
        CC.state.cicloInicial = boot.cicloInicial || '';
        CC.state.vistaInicial = boot.vistaInicial || '';
        CC.state.detalleUrl = boot.detalleUrl || '/Ventas/Captura/detalle';
        CC.state.gastoUrl = boot.gastoUrl || '/ProyeccionesVentas/api/gasto-real';
        CC.state.gastoBatchUrl = boot.gastoBatchUrl || '/ProyeccionesVentas/api/gasto-real-batch';
        CC.state.listasPreciosUrl = boot.listasPreciosUrl || '/ProyeccionesVentas/api/listas-precios';
        CC.state.capturaBootstrapUrl = boot.capturaBootstrapUrl || '/ProyeccionesVentas/api/captura/bootstrap';
        CC.state.ventasBudgetUrl = boot.ventasBudgetUrl || '/ProyeccionesVentas/api/captura/ventas-budget';
        CC.state.costosUrl = boot.costosUrl || '/ProyeccionesVentas/api/costos';
        CC.state.costosPlantillaUrl = boot.costosPlantillaUrl || '/ProyeccionesVentas/api/costos/plantilla';
        CC.state.costosImportExcelUrl = boot.costosImportExcelUrl || '/ProyeccionesVentas/api/costos/importar-excel';
        CC.state.costosImportListaUrl = boot.costosImportListaUrl || '/ProyeccionesVentas/api/costos/importar-lista-precios';
        CC.state.costosVaciosUrl = boot.costosVaciosUrl || '/ProyeccionesVentas/api/costos/vacios';
        CC.state.costosRellenarVaciosLoteUrl = boot.costosRellenarVaciosLoteUrl || '/ProyeccionesVentas/api/costos/rellenar-vacios-lote';
        CC.state.costosSinPrecioListaUrl = boot.costosSinPrecioListaUrl || '/ProyeccionesVentas/api/costos/sin-precio-lista';
        CC.state.costosSyncPrecioListaLoteUrl = boot.costosSyncPrecioListaLoteUrl || '/ProyeccionesVentas/api/costos/sincronizar-precio-lista-lote';
        CC.state.costosHistorialUrl = boot.costosHistorialUrl || '/ProyeccionesVentas/api/costos/historial';
        CC.state.puedeEditarCostos = boot.hasOwnProperty('puedeEditarCostos')
            ? !!boot.puedeEditarCostos
            : true;
        CC.state.puedeEditarPreciosCaptura = boot.hasOwnProperty('puedeEditarPreciosCaptura')
            ? !!boot.puedeEditarPreciosCaptura
            : false;
        CC.state.empresasLocales = boot.empresasLocales || [];
        CC.state.unidadesMedida = boot.unidadesMedida || {};
        CC.state.gastoCache = {};
        CC.state.gastoMeta = {};
        CC.state.gastoLookup = {};
        CC.state.preciosCache = {};
        CC.state.cicloCodigo = boot.cicloCodigo || boot.cicloInicial || '';
        CC.state.usuarioActual = boot.usuarioActual || '';
        CC.state.usuarioActualId = Number(boot.usuarioActualId || 0);
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
        // Detalle: sin catálogo SAP (todo local vía captura/bootstrap).
        if (CC.state.page === 'control') refreshSap(boot.catalogoUrl);
    };

    function refreshSap(url) {
        if (!url) return;
        // Nunca rehidratar Detalle desde SAP: evita doble init y lentitud.
        if (CC.state.page === 'detalle') return;
        fetch(url + '?per_page=120', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) { return res.json(); }).then(function (data) {
            if (!data) return;
            var lock = CC.state.page === 'control' || CC.state.page === 'analisis';
            if (!lock && !(data.centros || []).length) return;
            mergeSap(Object.assign({}, data, { sapOk: !!data.sapOk }), { lockCentros: lock });
            var flag = document.getElementById('sap-flag');
            if (flag && (data.sapOk || (data.centros || []).length || (data.cuentas || []).length)) {
                flag.textContent = 'Catálogo SAP';
                flag.className = 'cc-sap-flag';
            }
            if (CC.state.page === 'control') CC.initControl();
            if (CC.state.page === 'analisis') CC.initAnalisis();
        }).catch(function () { /* se queda el catálogo local */ });
    }

    window.CC = CC;
})(window, document);
