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
                El precio unitario se guarda en tu base local. Al cambiarlo, se actualiza en las proyecciones de
                <strong>ciclos abiertos</strong>; los ciclos cerrados conservan el histórico.
            </p>
        </div>
    </div>

    @include('ProyeccionesVentas.partials.nav')

    <div class="cc-panel">
        <div class="cc-panel-head">
            <h3><i class="fa-solid fa-tags"></i> Maestro de precios</h3>
            <span class="text-muted" style="font-size:.8rem" id="pv-costos-hint">Cargando…</span>
        </div>
        <div class="cc-filters" style="grid-template-columns: 180px 1fr auto auto auto;">
            <select id="pv-costos-empresa" class="cc-select">
                <option value="">Todas las empresas</option>
            </select>
            <input id="pv-costos-q" class="cc-input" type="search" placeholder="Buscar producto por código o nombre…">
            <button type="button" class="cc-btn" id="pv-costos-reload">
                <i class="fa-solid fa-rotate"></i> Actualizar
            </button>
            <button type="button" class="cc-btn" id="pv-costos-excel" title="Descargar o subir plantilla Excel (Formato Precios)">
                <i class="fa-solid fa-file-excel"></i> Plantilla Excel
            </button>
            <button type="button" class="cc-btn cc-btn-ink" id="pv-costos-import-api" title="Toma precios unitarios de venta SAP (Price / importe÷uds) de todas las empresas y los guarda en tu BD local">
                <i class="fa-solid fa-cloud-arrow-down"></i> Cargar desde API
            </button>
        </div>
        <div class="cc-table-wrap">
            <table class="cc-table" id="pv-costos-table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Producto</th>
                        <th class="num">Precio unitario</th>
                        <th>Moneda</th>
                        <th>Estado</th>
                        <th>Actualizado</th>
                        <th class="num">Acciones</th>
                    </tr>
                </thead>
                <tbody id="pv-costos-tbody">
                    <tr><td colspan="7"><div class="cc-empty">Cargando productos…</div></td></tr>
                </tbody>
            </table>
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
                </div>
                <p class="text-muted mt-3 mb-0" style="font-size:.8rem">
                    Se guardará en tu BD local y se propagará solo a proyecciones de ciclos abiertos.
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
                                <th class="num">Precio anterior</th>
                                <th class="num">Precio nuevo</th>
                                <th class="num">Variación</th>
                                <th>Origen</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody id="pv-hist-tbody">
                            <tr><td colspan="6"><div class="cc-empty">Cargando historial…</div></td></tr>
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
                    Usa el mismo formato que <strong>Formato Precios.xlsx</strong>:
                    <code>Empresa · CardCode · ItemCode · Mes · Precio</code>.
                </p>
                <ol class="mb-3" style="font-size:.85rem; padding-left:1.2rem">
                    <li>Descarga la plantilla con los precios actuales de tu BD.</li>
                    <li>Edita la columna <strong>Precio</strong> (CardCode y Mes puedes dejarlos vacíos).</li>
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
                    Se actualiza por Empresa + ItemCode y se propaga a ciclos abiertos.
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
