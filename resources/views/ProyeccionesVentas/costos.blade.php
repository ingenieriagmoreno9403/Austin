@extends('layouts.app')

@section('css')
<link href="{{ asset('css/centros-costos.css') }}?v={{ (int) @filemtime(public_path('css/centros-costos.css')) }}" rel="stylesheet">
@endsection

@section('content')
<div class="cc-page" id="cc-app">
    <div class="cc-header">
        <div>
            <div class="cc-kicker">Ventas · precio local</div>
            <h1 class="cc-title">Precios de productos</h1>
            <p class="cc-sub">
                Maestro local por <strong>Empresa</strong>, <strong>CardCode</strong> e <strong>ItemCode</strong>
                (1 fila). Los precios mensuales se editan en el modal; en BD siguen guardados por mes.
            </p>
        </div>
    </div>

    @include('ProyeccionesVentas.partials.nav')

    <div class="cc-panel">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-tags"></i> Maestro de precios</h3>
            <span class="text-muted" style="font-size:.8rem" id="pv-costos-hint">Cargando…</span>
        </div>
        <div class="cc-filters" style="grid-template-columns: 150px 1fr 1fr 90px auto auto auto;">
            <select id="pv-costos-empresa" class="cc-select" title="Filtrar por empresa">
                <option value="">Todas las empresas</option>
            </select>
            <input id="pv-costos-cliente" class="cc-input" type="search" placeholder="Cliente (CardCode o nombre)…" autocomplete="off">
            <input id="pv-costos-itemcode" class="cc-input" type="search" placeholder="ItemCode o producto…" autocomplete="off">
            <select id="pv-costos-per-page" class="cc-select" title="Registros por página">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <button type="button" class="cc-btn" id="pv-costos-reload">
                <i class="fa-solid fa-rotate"></i> Actualizar
            </button>
            <button type="button" class="cc-btn" id="pv-costos-excel" title="Descargar o subir plantilla Excel (Formato Precios)">
                <i class="fa-solid fa-file-excel"></i> Plantilla Excel
            </button>
            <button type="button" class="cc-btn cc-btn-ink" id="pv-costos-import-api" title="Carga precios-mensuales SAP (Empresa, CardCode, ItemCode, Mes, Precio) a tu BD local">
                <i class="fa-solid fa-cloud-arrow-down"></i> Cargar desde API
            </button>
        </div>
        <div class="cc-table-wrap">
            <table class="cc-table" id="pv-costos-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>CardCode</th>
                        <th>Cliente</th>
                        <th>ItemCode</th>
                        <th>Producto</th>
                        <th>Meses</th>
                        <th class="num">Precio global</th>
                        <th>Moneda</th>
                        <th>Estado</th>
                        <th>Actualizado</th>
                        <th class="num">Acciones</th>
                    </tr>
                </thead>
                <tbody id="pv-costos-tbody">
                    <tr><td colspan="11"><div class="cc-empty">Cargando productos…</div></td></tr>
                </tbody>
            </table>
        </div>
        <div class="cc-pager" id="pv-costos-pager" style="display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-top:.85rem;">
            <div class="text-muted" style="font-size:.8rem" id="pv-costos-page-info">—</div>
            <div class="cc-actions-row" id="pv-costos-page-btns">
                <button type="button" class="cc-btn cc-btn-sm" id="pv-costos-prev" disabled>
                    <i class="fa-solid fa-chevron-left"></i> Anterior
                </button>
                <span class="text-muted" style="font-size:.8rem;min-width:4.5rem;text-align:center" id="pv-costos-page-label">1 / 1</span>
                <button type="button" class="cc-btn cc-btn-sm" id="pv-costos-next" disabled>
                    Siguiente <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade cc-modal" id="modalPvCostoMeses" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Precio por mes</h5>
                    <p class="text-muted mb-0 mt-1" id="pcm-sub" style="font-size:.82rem">Producto</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:.85rem">
                    El precio global aplica a todo el año. Solo captura un valor en los meses que deban diferir.
                    Cada mes se guarda como una fila en el maestro (Empresa + CardCode + ItemCode + Mes).
                </p>
                <input type="hidden" id="pcm-empresa">
                <input type="hidden" id="pcm-codigo">
                <input type="hidden" id="pcm-nombre">
                <input type="hidden" id="pcm-card">
                <input type="hidden" id="pcm-card-name">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-sm-4">
                        <label class="form-label" for="pcm-base">Precio global</label>
                        <input id="pcm-base" class="form-control" type="number" step="0.0001" min="0">
                        <small class="text-muted" id="pcm-base-hint"></small>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label" for="pcm-moneda">Moneda</label>
                        <select id="pcm-moneda" class="form-select">
                            <option value="MXN">MXN</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div class="col-sm-5 d-flex gap-2 flex-wrap">
                        <button type="button" class="cc-btn" id="pcm-apply-all">Usar global en los 12</button>
                        <button type="button" class="cc-btn" id="pcm-reset">Limpiar casillas</button>
                    </div>
                </div>
                <div class="cc-tc-months" id="pcm-months"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="cc-btn cc-btn-ink" id="pcm-save">
                    <i class="fa-solid fa-check"></i> Guardar precios
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade cc-modal" id="modalPvCosto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">Editar precio del producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="cc-form-kicker" id="pv-costo-prod-label">—</div>
                <input type="hidden" id="pv-costo-empresa">
                <input type="hidden" id="pv-costo-codigo">
                <input type="hidden" id="pv-costo-nombre">
                <div class="row g-3 mt-1">
                    <div class="col-6">
                        <label class="form-label">CardCode</label>
                        <input type="text" class="form-control" id="pv-costo-card" maxlength="40" placeholder="Ej. A005SF">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Mes</label>
                        <select class="form-select" id="pv-costo-mes">
                            <option value="0">Sin mes</option>
                            <option value="1">Ene</option>
                            <option value="2">Feb</option>
                            <option value="3">Mar</option>
                            <option value="4">Abr</option>
                            <option value="5">May</option>
                            <option value="6">Jun</option>
                            <option value="7">Jul</option>
                            <option value="8">Ago</option>
                            <option value="9">Sep</option>
                            <option value="10">Oct</option>
                            <option value="11">Nov</option>
                            <option value="12">Dic</option>
                        </select>
                    </div>
                    <div class="col-8">
                        <label class="form-label">Precio unitario</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="pv-costo-valor">
                    </div>
                    <div class="col-4">
                        <label class="form-label">Moneda</label>
                        <select class="form-select" id="pv-costo-moneda">
                            <option value="MXN">MXN</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Nombre cliente (CardName)</label>
                        <input type="text" class="form-control" id="pv-costo-card-name" maxlength="180">
                    </div>
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size:.8rem">
                    Se guarda en BD local por Empresa + CardCode + ItemCode + Mes y se propaga a ciclos abiertos.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="cc-btn cc-btn-ink" id="pv-costo-guardar">
                    <i class="fa-solid fa-check"></i> Guardar precio
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade cc-modal" id="modalPvHistorialPrecio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Historial de precio</h5>
                    <div class="cc-form-kicker mt-1" id="pv-hist-prod-label">—</div>
                    <div class="d-flex flex-wrap gap-3 mt-2" style="font-size:.8rem" id="pv-hist-meta">
                        <span><span class="text-muted">Producto:</span> <strong id="pv-hist-nombre">—</strong></span>
                        <span><span class="text-muted">ItemCode:</span> <code id="pv-hist-itemcode">—</code></span>
                        <span><span class="text-muted">Mes:</span> <strong id="pv-hist-mes">—</strong></span>
                        <span><span class="text-muted">Cliente:</span> <span id="pv-hist-cliente">—</span></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="text-muted mb-3" style="font-size:.8rem" id="pv-hist-hint">
                    Movimientos del precio unitario en el maestro local.
                </div>
                <div class="cc-table-wrap">
                    <table class="cc-table" id="pv-hist-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Mes</th>
                                <th>ItemCode</th>
                                <th>Producto</th>
                                <th class="num">Precio anterior</th>
                                <th class="num">Precio nuevo</th>
                                <th class="num">Variación</th>
                                <th>Origen</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody id="pv-hist-tbody">
                            <tr><td colspan="9"><div class="cc-empty">Cargando historial…</div></td></tr>
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

<div class="modal fade cc-modal" id="modalPvPreciosExcel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mb-0">Plantilla de precios (Excel)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3" style="font-size:.9rem">
                    Formato: <code>Empresa · CardCode · ItemCode · Mes · Precio</code>.
                </p>
                <ol class="mb-3" style="font-size:.85rem; padding-left:1.2rem">
                    <li>Descarga la plantilla con los datos de tu BD.</li>
                    <li>Edita Precio / CardCode / Mes.</li>
                    <li>Sube el archivo para actualizar el maestro local.</li>
                </ol>
                <div class="d-grid gap-2 mb-3">
                    <button type="button" class="cc-btn cc-btn-ink" id="pv-precios-descargar">
                        <i class="fa-solid fa-download"></i> Descargar plantilla con datos
                    </button>
                </div>
                <label class="form-label">Subir Excel editado</label>
                <input type="file" class="form-control" id="pv-precios-archivo" accept=".xlsx,.xls,.csv">
                <div class="text-muted mt-2" style="font-size:.78rem" id="pv-precios-excel-hint">
                    Se actualiza por Empresa + CardCode + ItemCode + Mes.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="cc-btn cc-btn-ink" id="pv-precios-subir">
                    <i class="fa-solid fa-upload"></i> Subir y actualizar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('js/proyecciones-ventas.js') }}?v={{ (int) @filemtime(public_path('js/proyecciones-ventas.js')) }}"></script>
<script>
    CC.boot(Object.assign(@json($bootstrap), { page: 'costos' }));
</script>
@endsection
