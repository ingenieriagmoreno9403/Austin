@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="cc-page">
    <div class="cc-header">
        <div>
            <div class="cc-kicker" id="cc-page-kicker">Captura mensual · Gasto {{ $bootstrap['anioGasto'] }} vs ppto {{ $bootstrap['anioPresupuesto'] }}</div>
            <h1 class="cc-title" id="cc-page-title">Captura e Indicadores</h1>
            <p class="cc-sub" id="cc-page-sub">Elige presupuesto, empresa y centro. Captura cuenta por cuenta y ve cómo se arma el detalle y el contraste mensual.</p>
        </div>
        <div class="cc-header-actions">
            <span id="sap-flag" class="cc-sap-flag off">Catálogo</span>
            <select id="ctl-ciclo" class="cc-select cc-header-ciclo" aria-label="Presupuesto"></select>
            <button type="button" class="cc-btn" onclick="CC.showModal('modalIndicadores')">
                <i class="fa-solid fa-circle-info"></i> Indicadores
            </button>
        </div>
    </div>

    <nav class="cc-subnav" id="ctl-vistas" aria-label="Vistas de captura">
        <button type="button" class="is-active" data-vista="captura">
            <i class="fa-solid fa-pen-to-square me-1"></i> Captura de presupuestos
        </button>
        <button type="button" data-vista="visor">
            <i class="fa-solid fa-table me-1"></i> Visor de centros
        </button>
    </nav>

    <div id="ctl-empty" class="cc-panel" hidden>
        <div class="cc-empty" style="padding:2.2rem 1rem">
            <i class="fa-solid fa-user-lock"></i>
            No tienes centros de costo asignados en ningún ciclo.
            Pide a contabilidad que te asigne usuario, centro, cuentas y permiso de captura.
        </div>
    </div>

    <div id="ctl-work">
        <div class="cc-visor-progress">
            <div class="cc-visor-progress-top">
                <span>Avance de captura</span>
                <strong id="visor-avance">—</strong>
            </div>
            <div class="cc-progress warn" id="visor-avance-wrap"><span id="visor-avance-bar" style="width:0%"></span></div>
            <div class="cc-visor-progress-meta" id="visor-avance-meta">Elige un presupuesto para ver el avance</div>
        </div>
        <div id="vista-captura">
        <div class="cc-captura-layout">
            <aside class="cc-panel cc-captura-nav">
                <div class="cc-panel-head">
                    <h3><i class="fa-solid fa-sitemap"></i> Empresa y centro</h3>
                </div>
                <label class="cc-nav-field">
                    <span>Empresa <em class="cc-nav-status" id="ctl-emp-status" hidden></em></span>
                    <select id="ctl-empresa" class="cc-select">
                        <option value="">Elige empresa…</option>
                    </select>
                </label>
                <label class="cc-nav-field">
                    <span>Centro de costos <em class="cc-nav-status" id="ctl-cc-status" hidden></em></span>
                    <select id="ctl-centro" class="cc-select" disabled>
                        <option value="">Elige una empresa primero…</option>
                    </select>
                </label>
                <label class="cc-nav-field">
                    <span>Cuenta <em class="cc-nav-status" id="ctl-cta-status" hidden></em></span>
                    <select id="ctl-cuenta" class="cc-select" disabled>
                        <option value="">Elige un centro primero…</option>
                    </select>
                </label>
                <div class="cc-nav-tools">
                    <select id="ctl-moneda" class="cc-select" aria-label="Moneda">
                        <option value="MXN">MXN</option>
                        <option value="USD">USD</option>
                    </select>
                    <label class="cc-nav-pend-toggle">
                        <input type="checkbox" id="ctl-pendientes"> Solo pendientes
                    </label>
                </div>
                <div class="cc-nav-progress">
                    <div class="cc-nav-progress-top">
                        <div class="cc-nav-pend-label">Progreso del presupuesto</div>
                        <strong id="kpi-ctl-avance">—</strong>
                    </div>
                    <div class="cc-progress warn" id="ctl-avance-wrap"><span id="ctl-avance-bar" style="width:0%"></span></div>
                    <div class="cc-nav-pend-sub" id="ctl-progress-meta">0 de 0 cuentas capturadas</div>
                </div>
                <div class="cc-nav-pend" id="ctl-nav-pend">
                    <div class="cc-nav-pend-label">Cuentas pendientes</div>
                    <div class="cc-nav-pend-value" id="ctl-nav-pend-n">—</div>
                    <div class="cc-nav-pend-sub" id="ctl-nav-pend-sub">Elige un centro para ver cuántas cuentas faltan por presupuestar</div>
                </div>
                <div class="cc-nav-stats">
                    <div class="cc-nav-stat">
                        <small>Gasto {{ $bootstrap['anioGasto'] }}</small>
                        <strong id="kpi-ctl-gasto">—</strong>
                    </div>
                    <div class="cc-nav-stat">
                        <small>Ppto {{ $bootstrap['anioPresupuesto'] }}</small>
                        <strong id="kpi-ctl-ppto">—</strong>
                    </div>
                </div>
                <span id="kpi-ctl-pend" hidden></span>
                <span id="ctl-perms" hidden></span>
            </aside>

            <div class="cc-panel cc-captura-main">
                <div class="cc-captura-form-center">
                    <div id="ctl-form-empty" class="cc-empty cc-captura-form-empty">
                        <i class="fa-solid fa-list"></i>
                        Elige empresa, centro y cuenta a la izquierda para capturar el presupuesto.
                    </div>

                    <div id="ctl-form-body" hidden>
                        <div class="cc-cta-selected">
                            <div class="cc-cta-selected-row">
                                <div>
                                    <div class="cc-form-kicker" id="ctl-form-grupo">—</div>
                                    <div class="cc-cta-names">
                                        <h4 id="ctl-form-cc" class="cc-cta-cc">—</h4>
                                        <h4 id="ctl-form-cta">Cuenta</h4>
                                    </div>
                                </div>
                                <span class="cc-badge" id="ctl-form-estado">Pendiente</span>
                            </div>
                            <div class="cc-cta-fill">
                                <div class="cc-cta-fill-top">
                                    <span>Llenado de la cuenta</span>
                                    <strong id="ctl-form-avance">—</strong>
                                </div>
                                <div class="cc-progress warn" id="ctl-form-avance-wrap"><span id="ctl-form-avance-bar" style="width:0%"></span></div>
                                <div class="cc-cta-fill-meta" id="ctl-form-avance-meta">0 de 12 meses capturados</div>
                            </div>
                        </div>
                        <div class="cc-cta-compare">
                            <div class="cc-cta-compare-card">
                                <small>Se gastó {{ $bootstrap['anioGasto'] }}</small>
                                <strong id="ctl-form-gasto">—</strong>
                                <span>Real del año anterior</span>
                            </div>
                            <div class="cc-cta-compare-card is-now">
                                <small>Le pondré {{ $bootstrap['anioPresupuesto'] }}</small>
                                <strong id="ctl-form-ppto">—</strong>
                                <span id="ctl-form-delta">vs año pasado</span>
                            </div>
                        </div>
                        <div class="cc-month-row">
                            <div class="cc-month-grid" id="ctl-month-grid"></div>
                            <div class="cc-captura-quick">
                                <button type="button" class="cc-btn" id="ctl-copy-year">
                                    <i class="fa-solid fa-clone"></i> Copiar {{ $bootstrap['anioGasto'] }}
                                </button>
                                <button type="button" class="cc-btn" id="ctl-clear-year">
                                    <i class="fa-solid fa-eraser"></i> Limpiar
                                </button>
                                <button type="button" class="cc-btn" id="ctl-apply-infl">
                                    <i class="fa-solid fa-percent"></i> + inflación
                                </button>
                                <button type="button" class="cc-btn" id="ctl-btn-fijo" onclick="CC.showModal('modalFijo')">
                                    <i class="fa-solid fa-copy"></i> Gasto fijo
                                </button>
                                <button type="button" class="cc-btn" id="ctl-btn-dispersar" onclick="CC.showModal('modalDispersar')">
                                    <i class="fa-solid fa-share-nodes"></i> Dispersar
                                </button>
                                <button type="button" class="cc-btn" id="ctl-next-pend">
                                    <i class="fa-solid fa-arrow-right"></i> Siguiente pendiente
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="cc-captura-save">
                        <button type="button" class="cc-btn cc-btn-ink" id="ctl-guardar">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="cc-panel cc-captura-results">
            <div class="cc-panel-head">
                <h3><i class="fa-solid fa-table"></i> Detalle por cuenta y mes</h3>
                <div class="cc-legend">
                    <span><i style="background:#fffbeb"></i> Sin capturar</span>
                    <span><i style="background:#ecfdf5"></i> Capturado</span>
                    <span><i style="background:#fef2f2"></i> +20% vs {{ $bootstrap['anioGasto'] }}</span>
                </div>
            </div>
            <p class="text-muted" style="font-size:.8rem;margin:-.35rem 0 .7rem">Se va llenando sola conforme capturas cada cuenta. Haz clic en una fila para editarla arriba.</p>
            <div class="cc-table-wrap">
                <table class="cc-table">
                    <thead id="ctl-thead"></thead>
                    <tbody id="ctl-tbody"></tbody>
                </table>
            </div>
            <div class="cc-sticky-totales">
                <div>Total gasto {{ $bootstrap['anioGasto'] }} <strong id="ctl-tot-gasto">—</strong></div>
                <div>Total ppto {{ $bootstrap['anioPresupuesto'] }} <strong id="ctl-tot-ppto">—</strong></div>
                <div>Cuentas pendientes <strong id="ctl-pend">—</strong></div>
            </div>
        </div>

        <div class="cc-panel cc-captura-chart">
            <div class="cc-panel-head">
                <h3><i class="fa-solid fa-chart-column"></i> Contraste mes a mes</h3>
                <span class="text-muted" style="font-size:.78rem" id="ctl-chart-hint">Gasto {{ $bootstrap['anioGasto'] }} vs presupuesto {{ $bootstrap['anioPresupuesto'] }}</span>
            </div>
            <div class="cc-chart"><canvas id="chart-control"></canvas></div>
        </div>
        </div>

        <div id="vista-visor" hidden>
            <div class="cc-visor-kpis">
                <div class="cc-visor-kpi">
                    <small>Centros</small>
                    <strong id="visor-kpi-n">—</strong>
                </div>
                <div class="cc-visor-kpi">
                    <small>Tot Gasto</small>
                    <strong id="visor-kpi-gasto">—</strong>
                </div>
                <div class="cc-visor-kpi">
                    <small>Tot Presupuesto</small>
                    <strong id="visor-kpi-ppto">—</strong>
                </div>
                <div class="cc-visor-kpi">
                    <small>Pendientes</small>
                    <strong id="visor-kpi-pend">—</strong>
                </div>
            </div>
            <div class="cc-panel cc-visor-panel">
                <div class="cc-panel-head">
                    <h3><i class="fa-solid fa-building"></i> Centros de costos</h3>
                    <button type="button" class="cc-btn" id="visor-filtro-btn">
                        <i class="fa-solid fa-filter"></i> Filtro
                    </button>
                </div>
                <div class="cc-visor-filters" id="visor-filters" hidden>
                    <input id="visor-q" class="cc-input" type="search" placeholder="Buscar empresa, centro o usuario…">
                    <select id="visor-empresa" class="cc-select"></select>
                    <select id="visor-depto" class="cc-select"></select>
                    <select id="visor-estado" class="cc-select"></select>
                </div>
                <div class="cc-table-wrap cc-visor-wrap">
                    <table class="cc-table cc-visor-table">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Empresa</th>
                                <th>Centro de Costos</th>
                                <th>Departamento</th>
                                <th class="num">Tot Gasto</th>
                                <th class="num">Tot Pres</th>
                                <th>Usuario</th>
                                <th>Fecha Modif</th>
                                <th>Progreso</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="visor-tbody"></tbody>
                        <tfoot id="visor-tfoot"></tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade cc-modal" id="modalDispersar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="form-dispersar">
            <div class="modal-header">
                <h5 class="modal-title">Dispersar costo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12"><label class="form-label">Cuenta</label><select id="d-cuenta" class="form-select"></select></div>
                <div class="col-12"><label class="form-label">Monto total</label><input id="d-monto" class="form-control" type="number" min="0" step="0.01" required></div>
                <div class="col-6"><label class="form-label">Desde</label>
                    <select id="d-desde" class="form-select">
                        @foreach(['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'] as $i => $m)
                            <option value="{{ $i }}">{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6"><label class="form-label">Hasta</label>
                    <select id="d-hasta" class="form-select">
                        @foreach(['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'] as $i => $m)
                            <option value="{{ $i }}" @if($i===11) selected @endif>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Método</label>
                    <select id="d-modo" class="form-select">
                        <option value="igual">Partes iguales</option>
                        <option value="gasto">Proporcional al gasto {{ $bootstrap['anioGasto'] }}</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="cc-btn cc-btn-ink">Dispersar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade cc-modal" id="modalFijo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="form-fijo">
            <div class="modal-header">
                <h5 class="modal-title">Programar gasto fijo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12"><label class="form-label">Cuenta</label><select id="fijo-cuenta" class="form-select"></select></div>
                <div class="col-12"><label class="form-label">Monto mensual</label><input id="fijo-monto" class="form-control" type="number" min="0" step="0.01" required></div>
                <p class="text-muted mb-0" style="font-size:.82rem">Se copia el mismo monto a los 12 meses (arrendamientos, licencias, etc.).</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="cc-btn cc-btn-ink">Aplicar 12 meses</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade cc-modal" id="modalIndicadores" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Indicadores económicos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="cc-table">
                    <tbody>
                        <tr><td>Inflación</td><td class="num fw-semibold">4.0 % (Informe Banxico)</td></tr>
                        <tr><td>Tipo de cambio MXP/USD</td><td class="num fw-semibold">$ 20.00</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn cc-btn-ink" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/centros-costos.js') }}"></script>
<script>
    CC.boot(Object.assign(@json($bootstrap), { page: 'control' }));
</script>
@endsection
