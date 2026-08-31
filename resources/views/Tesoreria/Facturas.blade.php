@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.0/css/fixedColumns.dataTables.min.css">
@endsection

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    /* Solo comportamientos propios de la tabla expandible */
    .factura-row { transition: background .15s; }
    .factura-row:hover { background: var(--primary-soft-bg, #f3f4f6); }
    .factura-row.has-relacionados { cursor: pointer; }
    .factura-row.expanded { background: rgba(17, 24, 39, 0.06); }

    .factura-rel-child {
        background: #f8f9fa;
        padding: .75rem 1rem 1rem 1.25rem;
    }

    .rel-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .rel-list li {
        display: flex;
        align-items: center;
        gap: .75rem;
        flex-wrap: wrap;
        padding: .55rem .75rem;
        border-bottom: 1px dashed rgba(17, 24, 39, 0.1);
        font-size: .9rem;
    }
    .rel-list li:last-child { border-bottom: none; }
    .rel-list .rel-folio { font-weight: 600; min-width: 110px; }
    .rel-list .rel-monto { margin-left: auto; font-weight: 600; }
    .rel-list .rel-uuid {
        max-width: 140px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        color: var(--text-muted, #6b7280);
        font-size: .8rem;
    }

    .uuid-cell {
        max-width: 130px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .badge-rel-count {
        font-size: .7rem;
        vertical-align: middle;
    }

    .facturas-table-card .td-actions {
        white-space: nowrap !important;
    }
    .facturas-table-card .td-actions .btn {
        display: inline-flex !important;
        margin: 0 2px !important;
    }

    .facturas-table-card.table-responsive {
        overflow: visible !important;
    }

    .facturas-table-card div.dt-scroll,
    .facturas-table-card div.dataTables_scroll {
        border: 0 !important;
        margin: 0 !important;
        position: relative !important;
        width: 100% !important;
    }

    .facturas-table-card div.dt-scroll-head,
    .facturas-table-card div.dataTables_scrollHead {
        margin-bottom: 0 !important;
        overflow: hidden !important;
        border-bottom: 0 !important;
    }

    .facturas-table-card div.dt-scroll-body,
    .facturas-table-card div.dataTables_scrollBody {
        border-top: 0 !important;
        margin-top: 0 !important;
        overflow-x: auto !important;
        overflow-y: auto !important;
        width: 100% !important;
    }

    .facturas-table-card div.dt-scroll-head table,
    .facturas-table-card div.dataTables_scrollHead table,
    .facturas-table-card div.dt-scroll-body table,
    .facturas-table-card div.dataTables_scrollBody table {
        margin: 0 !important;
    }

    body.facturas-index-page table.dataTable tr > .dtfc-fixed-start,
    body.facturas-index-page table.dataTable tr > .dtfc-fixed-end,
    body.facturas-index-page table.dataTable tr > .dtfc-fixed-left,
    body.facturas-index-page table.dataTable tr > .dtfc-fixed-right {
        position: sticky !important;
        background-clip: padding-box !important;
    }

    body.facturas-index-page table.dataTable thead tr > .dtfc-fixed-start,
    body.facturas-index-page table.dataTable thead tr > .dtfc-fixed-end {
        background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%) !important;
        z-index: 5 !important;
    }

    body.facturas-index-page table.dataTable tbody tr > .dtfc-fixed-start,
    body.facturas-index-page table.dataTable tbody tr > .dtfc-fixed-end {
        background: #fff !important;
        z-index: 4 !important;
    }

    body.facturas-index-page table.dataTable tbody tr:hover > .dtfc-fixed-start,
    body.facturas-index-page table.dataTable tbody tr:hover > .dtfc-fixed-end {
        background: #f3f4f6 !important;
    }

    body.facturas-index-page div.dtfh-floatingparent {
        overflow: hidden !important;
        z-index: 1030 !important;
    }

    body.facturas-index-page div.dtfh-floatingparent table.dataTable {
        margin: 0 !important;
        background: #fff !important;
    }

    body.facturas-index-page div.dtfh-floatingparent thead th {
        background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%) !important;
        box-sizing: border-box !important;
    }

    body.facturas-index-page div.dtfh-floatingparent thead tr > .dtfc-fixed-start,
    body.facturas-index-page div.dtfh-floatingparent thead tr > .dtfc-fixed-end {
        position: sticky !important;
        background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%) !important;
        z-index: 6 !important;
    }

    .facturas-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .facturas-filters form {
        gap: 0.35rem 0.65rem;
    }
    .facturas-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
        max-width: none;
    }
    .facturas-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .facturas-filters .form-control-sm,
    .facturas-filters .form-select-sm {
        min-height: 28px;
        height: 28px;
        font-size: 0.78rem;
        padding: 0.15rem 0.4rem;
        width: auto;
        min-width: 120px;
        max-width: 180px;
        background: #ffffff !important;
        border: 1px solid var(--table-border, #e5e7eb) !important;
        border-radius: 10px !important;
        color: var(--table-text, #111827) !important;
    }
    .facturas-filters .filter-field-wide .form-control-sm {
        min-width: 160px;
        max-width: 220px;
    }
    .facturas-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    @media (max-width: 768px) {
        .facturas-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .facturas-filters .form-control-sm,
        .facturas-filters .form-select-sm {
            flex: 1;
            max-width: none;
        }
    }
</style>

<script>
    document.body.classList.add('facturas-index-page');
</script>

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/Panel" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Panel
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Facturas</h2>
                        <p class="text-muted mb-0">Consulta de CFDI de ingreso emitidos y timbrados. Control fiscal y seguimiento de cobranza.</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('finanzas.facturas') }}" class="btn btn-baseColor">
                        <i class="fa-solid fa-coins"></i> Cobranza en Finanzas
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 alert-dismissible fade show">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4 alert-dismissible fade show">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 alert-dismissible fade show">
            <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mt-3">
        <div class="table-responsive facturas-table-card">
            <div class="filters facturas-filters">
                <form method="GET" action="{{ route('facturas.index') }}" class="d-flex flex-wrap align-items-center" id="filtroFacturas">
                    <div class="filter-field filter-field-wide">
                        <label class="form-label" for="filtro_cliente">Cliente / RFC</label>
                        <input type="text" name="cliente" id="filtro_cliente" class="form-control form-control-sm"
                               value="{{ request('cliente') }}" placeholder="Nombre o RFC" aria-label="Filtrar por cliente o RFC">
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_folio">Folio</label>
                        <input type="text" name="folio" id="filtro_folio" class="form-control form-control-sm"
                               value="{{ request('folio') }}" placeholder="Folio" aria-label="Filtrar por folio">
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_metodo_pago">Método</label>
                        <select name="metodo_pago" id="filtro_metodo_pago" class="form-select form-select-sm" aria-label="Filtrar por método de pago">
                            <option value="">Todos</option>
                            <option value="PUE" @selected(request('metodo_pago') === 'PUE')>PUE</option>
                            <option value="PPD" @selected(request('metodo_pago') === 'PPD')>PPD</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_fecha_desde">Desde</label>
                        <input type="date" name="fecha_desde" id="filtro_fecha_desde" class="form-control form-control-sm"
                               value="{{ request('fecha_desde') }}" aria-label="Fecha desde">
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_fecha_hasta">Hasta</label>
                        <input type="date" name="fecha_hasta" id="filtro_fecha_hasta" class="form-control form-control-sm"
                               value="{{ request('fecha_hasta') }}" aria-label="Fecha hasta">
                    </div>
                    <button type="submit" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    @if(request()->hasAny(['cliente', 'folio', 'metodo_pago', 'fecha_desde', 'fecha_hasta']))
                        <a href="{{ route('facturas.index') }}" class="btn btn-outline-secondary btn-sm flex-shrink-0" title="Limpiar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                    @if(isset($facturasEmitidas))
                        <span class="badge bg-secondary ms-auto">{{ $facturasEmitidas->count() }} registro(s)</span>
                    @endif
                </form>
            </div>

            <table class="table table-striped table-hover display modern-table w-100 align-middle" id="table">
                <thead>
                    <tr>
                        <th class="text-center">Acciones</th>
                        <th>Folio</th>
                        <th>Cobranza</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>RFC</th>
                        <th>Método</th>
                        <th>Forma</th>
                        <th>UUID</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($facturasEmitidas ?? [] as $f)
                        @php
                            $tieneRel = !empty($f->tiene_relacionados);
                            $relId = 'rel-factura-' . $f->id;
                        @endphp
                        <tr class="factura-row {{ $tieneRel ? 'has-relacionados' : '' }}"
                            data-factura-id="{{ $f->id }}"
                            @if($tieneRel) data-target="{{ $relId }}" @endif>
                            <td class="td-actions text-center text-nowrap">
                                <a href="{{ route('finanzas.facturas') }}?cliente={{ urlencode($f->reciver_nombre ?? '') }}"
                                   class="btn btn-sm btn-outline-success"
                                   title="Ver en cobranza Finanzas">
                                    <i class="fa-solid fa-coins"></i>
                                </a>
                                @if(!empty($f->facturama_id) || !empty($f->Uuid))
                                    @if(!empty($f->Uuid))
                                        <a href="{{ route('facturas.representacion', $f->id) }}"
                                           class="btn btn-sm btn-outline-dark"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           title="Representación impresa personalizada">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </a>
                                    @endif
                                    @if(!empty($f->facturama_id))
                                    <a href="{{ route('facturas.ver', $f->id) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       title="Ver CFDI">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('facturas.pdf', $f->id) }}"
                                       class="btn btn-sm btn-outline-danger"
                                       title="Descargar PDF">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                    @endif
                                @endif
                                @if(($f->cancelada ?? 'A') === 'A' && !empty($f->Uuid))
                                    <a href="{{ route('facturas.cancelacion', $f->id) }}"
                                       class="btn btn-sm btn-outline-warning"
                                       title="Cancelar CFDI (elegir motivo SAT 01–04)">
                                        <i class="fa-solid fa-ban"></i>
                                    </a>
                                    <a href="{{ route('facturas.sustitucion', $f->id) }}"
                                       class="btn btn-sm btn-outline-secondary"
                                       title="Sustitución CFDI (motivo 01)">
                                        <i class="fa-solid fa-right-left"></i>
                                    </a>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <strong>{{ $f->folio ?? '-' }}</strong>
                                @if($tieneRel)
                                    <span class="badge bg-secondary badge-rel-count" title="Documentos relacionados">{{ $f->total_relacionados }}</span>
                                @endif
                            </td>
                            <td>
                                @if(($f->cancelada ?? 'A') !== 'A')
                                    <span class="badge bg-danger">Cancelada</span>
                                @else
                                    @php
                                        $estadoCobranza = $f->estado ?? 'Pendiente';
                                        $badgeCobranza = match($estadoCobranza) {
                                            'Pendiente' => 'warning',
                                            'Pagado' => 'success',
                                            'Cobrando' => 'info',
                                            'Activo' => 'secondary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeCobranza }}">{{ $estadoCobranza }}</span>
                                @endif
                            </td>
                            <td data-order="{{ !empty($f->date) ? \Carbon\Carbon::parse($f->date)->format('Y-m-d') : '' }}">
                                {{ !empty($f->date) ? \Carbon\Carbon::parse($f->date)->format('d/m/Y') : '-' }}
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width:180px" title="{{ $f->reciver_nombre ?? '' }}">
                                    {{ $f->reciver_nombre ?? '-' }}
                                </div>
                            </td>
                            <td><small class="text-muted">{{ $f->reciver_rfc ?? '-' }}</small></td>
                            <td>
                                @if(($f->metodo_pago ?? '') === 'PUE')
                                    <span class="badge bg-dark">PUE</span>
                                @elseif(($f->metodo_pago ?? '') === 'PPD')
                                    <span class="badge bg-secondary">PPD</span>
                                @else
                                    <span class="badge bg-secondary">{{ $f->metodo_pago ?? '-' }}</span>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ $f->forma_pago ?? '—' }}</span></td>
                            <td>
                                <small class="text-muted uuid-cell d-inline-block" title="{{ $f->Uuid ?? '' }}">
                                    {{ $f->Uuid ?? '-' }}
                                </small>
                            </td>
                            <td class="text-end" data-order="{{ $f->subtotal ?? 0 }}">${{ number_format($f->subtotal ?? 0, 2) }}</td>
                            <td class="text-end fw-semibold" data-order="{{ $f->total ?? 0 }}">${{ number_format($f->total ?? 0, 2) }}</td>
                        </tr>
                        @if($tieneRel)
                            <template id="{{ $relId }}">
                                <div class="factura-rel-child">
                                    <div class="small text-muted mb-2">
                                        <i class="fa-solid fa-link me-1"></i>
                                        Documentos relacionados con folio <strong>{{ $f->folio ?? $f->id }}</strong>
                                    </div>
                                    <ul class="rel-list">
                                        @foreach($f->documentos_relacionados as $rel)
                                            <li>
                                                <span class="badge bg-{{ $rel->badge }}">{{ $rel->tipo_label }}</span>
                                                <span class="rel-folio">{{ $rel->folio }}</span>
                                                <span class="text-muted">
                                                    {{ $rel->fecha ? \Carbon\Carbon::parse($rel->fecha)->format('d/m/Y') : '—' }}
                                                </span>
                                                <span class="text-muted text-truncate" style="max-width:220px" title="{{ $rel->detalle }}">
                                                    {{ $rel->detalle }}
                                                </span>
                                                <span class="badge bg-light text-dark border">{{ $rel->estado }}</span>
                                                @if(!empty($rel->uuid))
                                                    <span class="rel-uuid" title="{{ $rel->uuid }}">{{ $rel->uuid }}</span>
                                                @endif
                                                <span class="rel-monto
                                                    @if($rel->tipo === 'nota_credito') text-danger
                                                    @elseif(in_array($rel->tipo, ['nota_debito','complemento'])) text-success
                                                    @endif">
                                                    @if($rel->tipo === 'nota_credito')
                                                        −${{ number_format($rel->monto, 2) }}
                                                    @elseif(in_array($rel->tipo, ['nota_debito','complemento']))
                                                        +${{ number_format($rel->monto, 2) }}
                                                    @else
                                                        ${{ number_format($rel->monto, 2) }}
                                                    @endif
                                                </span>
                                                <span class="text-nowrap">
                                                    @if(!empty($rel->ver_url))
                                                        <a href="{{ $rel->ver_url }}"
                                                           class="btn btn-sm btn-outline-primary"
                                                           target="_blank"
                                                           rel="noopener noreferrer"
                                                           title="Ver CFDI">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </a>
                                                    @endif
                                                    @if(!empty($rel->pdf_url))
                                                        <a href="{{ $rel->pdf_url }}"
                                                           class="btn btn-sm btn-outline-danger"
                                                           title="Descargar PDF">
                                                            <i class="fa-solid fa-file-pdf"></i>
                                                        </a>
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </template>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
<script>
$(document).ready(function () {
    var isMobileTable = window.matchMedia('(max-width: 768px)').matches;

    var table = $('#table').DataTable({
        responsive: false,
        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,
        select: true,
        order: [[3, 'desc']],
        scrollY: isMobileTable ? false : '500px',
        paging: true,
        columnDefs: [
            {
                targets: 0,
                orderable: false,
                className: 'td-actions',
                width: isMobileTable ? '160px' : '200px'
            },
            {
                targets: 1,
                width: '110px'
            },
            {
                targets: 2,
                width: '100px'
            },
            {
                targets: 4,
                width: '180px'
            }
        ],
        fixedColumns: isMobileTable ? false : { start: 3, end: 0 },
        fixedHeader: isMobileTable
            ? false
            : {
                header: true,
                footer: false
            },
        layout: {
            topStart: [
                'pageLength',
                {
                    buttons: [
                        {
                            extend: 'copy',
                            text: '<i class="fa-regular fa-copy"></i>',
                            titleAttr: 'Copiar',
                            className: 'btn btn-tool-copy push'
                        },
                        {
                            extend: 'excel',
                            text: '<i class="fa-regular fa-file-excel"></i>',
                            titleAttr: 'Excel',
                            className: 'btn btn-tool-excel push'
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="fa-regular fa-file-pdf"></i>',
                            titleAttr: 'PDF',
                            className: 'btn btn-tool-pdf push'
                        },
                        {
                            extend: 'print',
                            text: '<i class="fa-solid fa-print"></i>',
                            titleAttr: 'Imprimir',
                            className: 'btn btn-tool-print push'
                        },
                        {
                            extend: 'colvis',
                            text: '<i class="fa-solid fa-filter"></i>',
                            titleAttr: 'Filtrar',
                            className: 'btn btn-tool-colvis push'
                        }
                    ]
                }
            ],
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging'
        },
        oLanguage: {
            sSearch: '<i class="fa-solid fa-magnifying-glass"></i>'
        },
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json'
        },
        initComplete: function () {
            adjustFacturasTable(this.api());
        }
    });

    function adjustFacturasTable(api) {
        api = api || table;
        api.columns.adjust();

        if (!isMobileTable && typeof api.fixedColumns === 'function') {
            var fc = api.settings()[0]._fixedColumns;
            if (fc && typeof fc.start === 'function') {
                fc.start(fc.start());
            }
        }

        if (typeof api.fixedHeader === 'function') {
            try {
                var fh = api.fixedHeader();
                if (fh && typeof fh.adjust === 'function') {
                    fh.adjust();
                }
            } catch (e) {
                // FixedHeader puede no estar activo con scrollY
            }
        }
    }

    function toggleRelacionados(tr) {
        if (!tr || !tr.classList.contains('has-relacionados')) return;

        var row = table.row(tr);
        var targetId = tr.dataset.target;
        var tpl = targetId ? document.getElementById(targetId) : null;

        if (row.child.isShown()) {
            row.child.hide();
            tr.classList.remove('expanded');
        } else if (tpl) {
            var content = tpl.content ? tpl.content.cloneNode(true) : tpl.cloneNode(true);
            row.child(content).show();
            tr.classList.add('expanded');
        }

        adjustFacturasTable();
    }

    $('#table tbody').on('click', 'tr.factura-row.has-relacionados', function (e) {
        if ($(e.target).closest('a, button, input, select, textarea').length) {
            return;
        }
        toggleRelacionados(this);
    });

    $(window).on('resize', function () {
        adjustFacturasTable();
    });

    table.on('draw.dt column-visibility.dt length.dt', function () {
        adjustFacturasTable();
    });
});
</script>
@endsection
