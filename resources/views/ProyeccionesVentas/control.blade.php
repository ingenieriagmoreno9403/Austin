@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}?v={{ (int) @filemtime(public_path('css/centros-costos.css')) }}" rel="stylesheet">
@endsection

@section('content')
<div class="cc-page">
    <div class="cc-header">
        <div>
            <div class="cc-kicker" id="cc-page-kicker">Captura mensual · Venta {{ $bootstrap['anioGasto'] }} vs proyección {{ $bootstrap['anioPresupuesto'] }}</div>
            <h1 class="cc-title" id="cc-page-title">Captura de proyecciones</h1>
            <p class="cc-sub" id="cc-page-sub">Elige ciclo, empresa y cliente. Captura las cantidades de todos los productos en la misma tabla.</p>
        </div>
        <div class="cc-header-actions">
            <span id="sap-flag" class="cc-sap-flag off">Catálogo</span>
            <select id="ctl-ciclo" class="cc-select cc-header-ciclo" aria-label="Ciclo"></select>
            <div id="ctl-import-tools" class="cc-import-tools" hidden>
                <button type="button" class="cc-btn" id="ctl-plantilla">
                    <i class="fa-solid fa-file-arrow-down"></i> Plantilla
                </button>
                <button type="button" class="cc-btn cc-btn-ink" id="ctl-importar">
                    <i class="fa-solid fa-file-arrow-up"></i> Importar
                </button>
                <input type="file" id="ctl-import-file" accept=".xlsx,.xls,.csv" hidden>
            </div>
            <button type="button" class="cc-btn" onclick="CC.showModal('modalIndicadores')">
                <i class="fa-solid fa-circle-info"></i> Indicadores
            </button>
        </div>
    </div>

    <nav class="cc-subnav" id="ctl-vistas" aria-label="Vistas de captura">
        <button type="button" class="is-active" data-vista="captura">
            <i class="fa-solid fa-pen-to-square me-1"></i> Captura de proyecciones
        </button>
        <button type="button" data-vista="visor">
            <i class="fa-solid fa-table me-1"></i> Visor de clientes
        </button>
    </nav>

    <div id="ctl-empty" class="cc-panel" hidden>
        <div class="cc-empty" style="padding:2.2rem 1rem">
            <i class="fa-solid fa-user-lock"></i>
            No tienes clientes asignados en ningún ciclo.
            Pide que te asignen usuario, cliente, productos y permiso de captura.
        </div>
    </div>

    <div id="ctl-work">
        <div class="cc-visor-progress">
            <div class="cc-visor-progress-top">
                <span>Avance de captura</span>
                <strong id="visor-avance">—</strong>
            </div>
            <div class="cc-progress warn" id="visor-avance-wrap"><span id="visor-avance-bar" style="width:0%"></span></div>
            <div class="cc-visor-progress-meta" id="visor-avance-meta">Elige un ciclo para ver el avance</div>
        </div>
        <div id="vista-captura">
        <div class="cc-captura-layout">
            <aside class="cc-panel cc-captura-nav">
                <div class="cc-panel-head">
                    <h3><i class="fa-solid fa-sitemap"></i> Empresa y cliente</h3>
                </div>
                <div class="cc-nav-field">
                    <label for="ctl-empresa">Empresa <em class="cc-nav-status" id="ctl-emp-status" hidden></em></label>
                    <select id="ctl-empresa" class="cc-select">
                        <option value="">Elige empresa…</option>
                    </select>
                </div>
                <div class="cc-nav-field">
                    <label for="ctl-centro">Cliente <em class="cc-nav-status" id="ctl-cc-status" hidden></em></label>
                    <select id="ctl-centro" class="cc-select" disabled>
                        <option value="">Elige una empresa primero…</option>
                    </select>
                </div>
                <select id="ctl-cuenta" hidden aria-hidden="true" tabindex="-1">
                    <option value="">Elige producto…</option>
                </select>
                <em class="cc-nav-status" id="ctl-cta-status" hidden></em>
                <div class="cc-nav-tools">
                    <div class="cc-nav-currency">
                        <select id="ctl-moneda" class="cc-select" aria-label="Moneda">
                            <option value="MXN">MXN</option>
                            <option value="USD">USD</option>
                        </select>
                        <span class="cc-fx-chip" id="ctl-fx-nav" title="Tipo de cambio del ciclo">TC —</span>
                    </div>
                    <label class="cc-nav-pend-toggle">
                        <input type="checkbox" id="ctl-pendientes"> Solo pendientes
                    </label>
                    <button type="button" class="cc-btn cc-btn-block" id="ctl-btn-ventas-pasadas" disabled>
                        <i class="fa-solid fa-clock-rotate-left"></i> Ventas pasadas
                    </button>
                    <button type="button" class="cc-btn cc-btn-block" id="ctl-add-btn" disabled>
                        <i class="fa-solid fa-plus"></i> Agregar producto
                    </button>
                </div>
                <div class="cc-nav-progress">
                    <div class="cc-nav-progress-top">
                        <div class="cc-nav-pend-label">Progreso de la proyección</div>
                        <strong id="kpi-ctl-avance">—</strong>
                    </div>
                    <div class="cc-progress warn" id="ctl-avance-wrap"><span id="ctl-avance-bar" style="width:0%"></span></div>
                    <div class="cc-nav-pend-sub" id="ctl-progress-meta">0 de 0 productos capturados</div>
                </div>
                <div class="cc-nav-pend" id="ctl-nav-pend">
                    <div class="cc-nav-pend-label">Productos pendientes</div>
                    <div class="cc-nav-pend-value" id="ctl-nav-pend-n">—</div>
                    <div class="cc-nav-pend-sub" id="ctl-nav-pend-sub">Elige un cliente para ver cuántos productos faltan por proyectar</div>
                </div>
                <div class="cc-nav-stats">
                    <div class="cc-nav-stat">
                        <small>Venta {{ $bootstrap['anioGasto'] }}</small>
                        <strong id="kpi-ctl-gasto">—</strong>
                    </div>
                    <div class="cc-nav-stat">
                        <small>Proy. {{ $bootstrap['anioPresupuesto'] }}</small>
                        <strong id="kpi-ctl-ppto">—</strong>
                    </div>
                </div>
                <span id="kpi-ctl-pend" hidden></span>
                <span id="ctl-perms" hidden></span>
            </aside>

            <div class="cc-panel cc-captura-main">
                <div class="cc-captura-form-center">
                    <div id="ctl-form-empty" class="cc-empty cc-captura-form-empty">
                        <i class="fa-solid fa-table"></i>
                        Elige empresa y cliente a la izquierda para capturar todos los productos en una sola tabla.
                    </div>

                    <div id="ctl-form-body" hidden>
                        <div class="cc-cta-selected">
                            <div class="cc-cta-selected-row">
                                <div class="cc-cta-idents">
                                    <div class="cc-cta-ident is-centro">
                                        <span class="cc-cta-ident-label">Cliente</span>
                                        <h4 id="ctl-form-cc" class="cc-cta-cc">—</h4>
                                    </div>
                                    <div class="cc-cta-ident is-cuenta">
                                        <span class="cc-cta-ident-label">Productos</span>
                                        <h4 id="ctl-form-cta">—</h4>
                                        <em class="cc-form-kicker" id="ctl-form-grupo">Edita Ene–Dic por fila</em>
                                    </div>
                                </div>
                                <span class="cc-badge" id="ctl-form-estado">Pendiente</span>
                            </div>
                        </div>
                        <div class="cc-cta-compare">
                            <div class="cc-cta-compare-card">
                                <small>Se vendió {{ $bootstrap['anioGasto'] }}</small>
                                <strong id="ctl-form-gasto">—</strong>
                                <span>Importe de venta real</span>
                            </div>
                            <div class="cc-cta-compare-card is-now">
                                <small id="ctl-form-ppto-label">Proyección</small>
                                <strong id="ctl-form-ppto">—</strong>
                                <span id="ctl-form-delta">Unidades × precio unitario</span>
                            </div>
                        </div>
                        <div class="cc-cta-fill mb-3">
                            <div class="cc-cta-fill-top">
                                <span>Llenado del cliente</span>
                                <strong id="ctl-form-avance">—</strong>
                            </div>
                            <div class="cc-progress warn" id="ctl-form-avance-wrap"><span id="ctl-form-avance-bar" style="width:0%"></span></div>
                            <div class="cc-cta-fill-meta" id="ctl-form-avance-meta">0 de 0 productos capturados</div>
                        </div>
                        <div class="cc-matrix-toolbar">
                            <div class="cc-captura-quick cc-captura-quick-inline">
                                <button type="button" class="cc-btn" id="ctl-copy-year">
                                    <i class="fa-solid fa-clone"></i> Copiar {{ $bootstrap['anioGasto'] }}
                                </button>
                                <button type="button" class="cc-btn" id="ctl-btn-dispersar" onclick="CC.showModal('modalDispersar')">
                                    <i class="fa-solid fa-share-nodes"></i> Dispersar
                                </button>
                                <button type="button" class="cc-btn" id="ctl-clear-year">
                                    <i class="fa-solid fa-eraser"></i> Limpiar
                                </button>
                                <button type="button" class="cc-btn" id="ctl-completar">
                                    <i class="fa-solid fa-check"></i> Completado
                                </button>
                                <label class="cc-prod-filter" for="ctl-prod-q">
                                    <span>Buscar</span>
                                    <input id="ctl-prod-q" class="cc-input" type="search" placeholder="Código o nombre…" disabled>
                                </label>
                                <div class="cc-ajuste-ctrl" title="Sugerir % sobre la venta real. Se aplica a los productos visibles (o al seleccionado). Puede ser negativo.">
                                    <label for="ctl-ajuste-pct">% venta</label>
                                    <input id="ctl-ajuste-pct" type="number" step="0.1" placeholder="-30" aria-label="Porcentaje de ajuste sobre la venta real">
                                    <span>%</span>
                                    <button type="button" class="cc-btn cc-btn-ink" id="ctl-apply-infl">Aplicar</button>
                                </div>
                            </div>
                            <div class="cc-matrix-fx">
                                <span class="cc-fx-badge" id="ctl-fx-badge">Vista MXN · TC —</span>
                                <small class="cc-matrix-hint" id="ctl-matrix-hint">Arriba de cada mes ves la venta del año de referencia; abajo capturas la proyección. Clic en el indicador TC para ajustar el dólar por mes. Los cambios se guardan al salir de cada celda.</small>
                            </div>
                        </div>
                        <div class="cc-matrix-legend" id="ctl-matrix-legend" aria-label="Indicadores de color">
                            <span class="cc-legend-label">Indicadores</span>
                            <span class="cc-legend-item is-ok"><span class="cc-legend-swatch" aria-hidden="true"></span> <span id="ctl-legend-ok">Mayor o igual a venta pasada</span></span>
                            <span class="cc-legend-item is-over"><span class="cc-legend-swatch" aria-hidden="true"></span> <span id="ctl-legend-over">Menor a venta pasada</span></span>
                            <span class="cc-legend-item is-empty"><span class="cc-legend-swatch" aria-hidden="true"></span> Pendiente de capturar</span>
                        </div>
                        <div class="cc-matrix-wrap">
                            <table class="cc-matrix-table" id="ctl-matrix-table">
                                <thead id="ctl-matrix-thead"></thead>
                                <tbody id="ctl-matrix-tbody">
                                    <tr><td colspan="17"><div class="cc-empty">Sin productos</div></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="ctl-month-grid" hidden></div>
                    </div>
                    <div class="cc-captura-save">
                        <button type="button" class="cc-btn" id="ctl-guardar">
                            <i class="fa-solid fa-check"></i> Guardar
                        </button>
                        <button type="button" class="cc-btn cc-btn-ink" id="ctl-guardar-seguir">
                            <i class="fa-solid fa-arrow-right"></i> Guardar y seguir
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="cc-panel cc-captura-results">
            <div class="cc-panel-head">
                <h3><i class="fa-solid fa-table"></i> Detalle por producto y mes</h3>
                <div class="cc-panel-head-tools">
                    <div class="cc-scope-toggle" id="ctl-scope-tabla" data-for="tabla" role="group" aria-label="Filtro del detalle">
                        <button type="button" data-scope-val="cuenta">Este producto</button>
                        <button type="button" data-scope-val="centro" class="is-on">Todo el cliente</button>
                    </div>
                    <label class="cc-scope-check" id="ctl-chart-todas-wrap" hidden>
                        <input type="checkbox" id="ctl-chart-todas"> Ver todas
                    </label>
                    <div class="cc-legend">
                        <span><i style="background:#fffbeb"></i> Sin capturar</span>
                        <span><i style="background:#ecfdf5"></i> Capturado</span>
                        <span><i style="background:#fef2f2"></i> +20% vs {{ $bootstrap['anioGasto'] }}</span>
                    </div>
                </div>
            </div>
            <p class="text-muted" id="ctl-detalle-hint" style="font-size:.8rem;margin:-.35rem 0 .7rem">Resumen del cliente. Elige una fila en la tabla de captura para filtrar a un producto.</p>
            <div class="cc-table-wrap">
                <table class="cc-table">
                    <thead id="ctl-thead"></thead>
                    <tbody id="ctl-tbody"></tbody>
                </table>
            </div>
            <div class="cc-sticky-totales">
                <div>Total venta {{ $bootstrap['anioGasto'] }} <strong id="ctl-tot-gasto">—</strong></div>
                <div>Total proyección {{ $bootstrap['anioPresupuesto'] }} <strong id="ctl-tot-ppto">—</strong></div>
                <div>Productos pendientes <strong id="ctl-pend">—</strong></div>
            </div>
            <div class="cc-sticky-meses" id="ctl-tot-meses" aria-label="Totales por mes"></div>
        </div>

        <div class="cc-panel cc-captura-chart">
            <div class="cc-panel-head">
                <h3><i class="fa-solid fa-chart-column"></i> Contraste mes a mes</h3>
                <span class="text-muted" style="font-size:.78rem" id="ctl-chart-hint">Venta {{ $bootstrap['anioGasto'] }} vs proyección {{ $bootstrap['anioPresupuesto'] }}</span>
            </div>
            <div class="cc-chart"><canvas id="chart-control"></canvas></div>
        </div>
        </div>

        <div id="vista-visor" hidden>
            <div class="cc-visor-kpis">
                <div class="cc-visor-kpi">
                    <small>Clientes</small>
                    <strong id="visor-kpi-n">—</strong>
                </div>
                <div class="cc-visor-kpi">
                    <small>Tot Venta</small>
                    <strong id="visor-kpi-gasto">—</strong>
                </div>
                <div class="cc-visor-kpi">
                    <small>Tot Proyección</small>
                    <strong id="visor-kpi-ppto">—</strong>
                </div>
                <div class="cc-visor-kpi">
                    <small>Pendientes</small>
                    <strong id="visor-kpi-pend">—</strong>
                </div>
            </div>
            <div class="cc-panel cc-visor-panel">
                <div class="cc-panel-head">
                    <h3><i class="fa-solid fa-building"></i> Clientes</h3>
                    <button type="button" class="cc-btn" id="visor-filtro-btn">
                        <i class="fa-solid fa-filter"></i> Filtro
                    </button>
                </div>
                <div class="cc-visor-filters" id="visor-filters" hidden>
                    <input id="visor-q" class="cc-input" type="search" placeholder="Buscar empresa, cliente o usuario…">
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
                                <th>Cliente</th>
                                <th>Departamento</th>
                                <th class="num">Tot Venta</th>
                                <th class="num">Tot Proy.</th>
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
                <div class="col-12"><label class="form-label" id="d-monto-label">Monto total</label><input id="d-monto" class="form-control" type="number" min="0" step="0.01" required></div>
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
                        <option value="mismo">Cantidad fija</option>
                        <option value="gasto">Proporcional a la venta {{ $bootstrap['anioGasto'] }}</option>
                    </select>
                    <p class="text-muted mb-0 mt-1" id="d-modo-hint" style="font-size:.82rem">El total se reparte entre los meses del rango.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="cc-btn cc-btn-ink">Dispersar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade cc-modal" id="modalVentasPasadas" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Ventas pasadas</h5>
                    <p class="text-muted mb-0 mt-1" id="vp-sub" style="font-size:.82rem">Elige empresa y cliente para consultar el historial.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="cc-vp-tools">
                    <div class="cc-vp-field">
                        <label for="vp-anio">Año</label>
                        <select id="vp-anio" class="cc-select"></select>
                    </div>
                    <div class="cc-vp-field">
                        <label for="vp-metric">Mostrar</label>
                        <select id="vp-metric" class="cc-select">
                            <option value="qty">Cantidad (uds)</option>
                            <option value="mxn">Importe MXN</option>
                            <option value="usd">Importe USD</option>
                        </select>
                    </div>
                    <div class="cc-vp-field cc-vp-search">
                        <label for="vp-q">Buscar</label>
                        <input id="vp-q" class="cc-input" type="search" placeholder="Producto…">
                    </div>
                    <button type="button" class="cc-btn cc-btn-ink" id="vp-cargar">
                        <i class="fa-solid fa-rotate"></i> Cargar
                    </button>
                </div>
                <div class="cc-vp-meta" id="vp-meta">—</div>
                <div class="cc-table-wrap cc-vp-table-wrap">
                    <table class="cc-table cc-vp-table">
                        <thead id="vp-thead"></thead>
                        <tbody id="vp-tbody">
                            <tr><td colspan="16"><div class="cc-empty">Elige un año y pulsa Cargar</div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade cc-modal" id="modalAgregarProducto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Agregar producto</h5>
                    <p class="text-muted mb-0 mt-1" id="ctl-add-sub" style="font-size:.82rem">Catálogo de la empresa, aunque no se le haya vendido a este cliente.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="cc-pick-search">
                    <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input id="ctl-add-q" class="cc-input" type="search" placeholder="Buscar por código o nombre…" autocomplete="off">
                </div>
                <div class="cc-add-prod-list" id="ctl-add-list">
                    <div class="cc-add-prod-empty">Cargando productos…</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@include('ProyeccionesVentas.partials.modal-indicadores')

<div class="modal fade cc-modal" id="modalPrecioMeses" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Precio por mes</h5>
                    <p class="text-muted mb-0 mt-1" id="pm-sub" style="font-size:.82rem">Producto</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:.85rem">
                    El precio global aplica a todo el año. Solo captura un valor en los meses que deban cambiar.
                </p>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-sm-5">
                        <label class="form-label" for="pm-base">Precio global (lista)</label>
                        <input id="pm-base" class="form-control" type="number" step="0.0001" min="0" readonly>
                        <small class="text-muted" id="pm-base-hint"></small>
                    </div>
                    <div class="col-sm-7 d-flex gap-2 flex-wrap">
                        <button type="button" class="cc-btn" id="pm-apply-all">Usar global en los 12</button>
                        <button type="button" class="cc-btn" id="pm-reset">Limpiar overrides</button>
                    </div>
                </div>
                <div class="cc-tc-months" id="pm-months"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="cc-btn cc-btn-ink" id="pm-save">Guardar precios</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/proyecciones-ventas.js') }}?v={{ (int) @filemtime(public_path('js/proyecciones-ventas.js')) }}"></script>
<script>
    CC.boot(Object.assign(@json($bootstrap), { page: 'control' }));
</script>
@endsection
