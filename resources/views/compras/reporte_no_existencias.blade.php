@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $estatusBadge = [
        'PENDIENTE' => 'bg-secondary',
        'EN_COMPRA' => 'bg-warning text-dark',
        'ATENDIDO' => 'bg-success',
        'CANCELADO' => 'bg-danger',
    ];
    $estatusOpciones = ['PENDIENTE', 'EN_COMPRA', 'ATENDIDO', 'CANCELADO'];
@endphp

<style>
    .rne-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .rne-filters form {
        gap: 0.35rem 0.65rem;
    }
    .rne-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
        max-width: none;
    }
    .rne-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .rne-filters .form-select-sm {
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
    .rne-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    @media (max-width: 768px) {
        .rne-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .rne-filters .form-select-sm {
            flex: 1;
            max-width: none;
        }
    }
</style>

<div class="container-fluid format_page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Reporte de no existencias</h2>
                        <p class="text-muted mb-0">Faltantes de productos de compra y de materia prima detectados en pedidos y cotizaciones.</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" form="form-generar-oc" class="btn btn-blue fs-7 mb-2" id="btn-generar-oc" disabled>
                        <i class="fa-solid fa-cart-plus"></i> Generar Orden de Compra (<span id="conteo-seleccion">0</span>)
                    </button>
                    <a class="btn btn-blue-light fs-7 mb-2" href="{{ route('ordcompras.index') }}">
                        <i class="fa-solid fa-file-invoice"></i> Órdenes de Compra
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 compras-kpi-row">
        <div class="col-6 col-md-3">
            <div class="compras-kpi compras-kpi--secondary">
                <div class="compras-kpi__body">
                    <span class="compras-kpi__label">Pendientes</span>
                    <span class="compras-kpi__value">{{ $resumen['pendiente'] }}</span>
                </div>
                <div class="compras-kpi__icon" aria-hidden="true">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="compras-kpi compras-kpi--warning">
                <div class="compras-kpi__body">
                    <span class="compras-kpi__label">En compra</span>
                    <span class="compras-kpi__value">{{ $resumen['en_compra'] }}</span>
                </div>
                <div class="compras-kpi__icon" aria-hidden="true">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="compras-kpi compras-kpi--success">
                <div class="compras-kpi__body">
                    <span class="compras-kpi__label">Atendidos</span>
                    <span class="compras-kpi__value">{{ $resumen['atendido'] }}</span>
                </div>
                <div class="compras-kpi__icon" aria-hidden="true">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="compras-kpi compras-kpi--danger">
                <div class="compras-kpi__body">
                    <span class="compras-kpi__label">Cancelados</span>
                    <span class="compras-kpi__value">{{ $resumen['cancelado'] }}</span>
                </div>
                <div class="compras-kpi__icon" aria-hidden="true">
                    <i class="fa-solid fa-ban"></i>
                </div>
            </div>
        </div>
    </div>

    <form id="form-generar-oc" method="POST" action="{{ route('compras.reporte_no_existencias.generar_oc') }}">
        @csrf
    </form>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-orange">Listado de no existencias</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <div class="filters rne-filters">
                    <form method="GET" action="{{ route('compras.reporte_no_existencias.index') }}" class="d-flex flex-wrap align-items-center" id="filtroNoExistencias">
                        <div class="filter-field">
                            <label class="form-label" for="filtro_estatus">Estatus</label>
                            <select class="form-select form-select-sm" id="filtro_estatus" name="estatus" aria-label="Filtrar por estatus">
                                <option value="">Todos</option>
                                @foreach ($estatusOpciones as $opt)
                                    <option value="{{ $opt }}" {{ $estatus === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                            <i class="fa-solid fa-filter"></i> Filtrar
                        </button>
                        <a href="{{ route('compras.reporte_no_existencias.index') }}" class="btn btn-outline-secondary btn-sm flex-shrink-0">
                            Limpiar
                        </a>
                    </form>
                </div>

                <table class="table table-striped table-hover display modern-table w-100" id="tableNoExistencias">
                    <thead>
                        <tr>
                            <th style="width:36px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="chk-todos" title="Seleccionar todo">
                            </th>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Pedido / Cliente</th>
                            <th class="text-end">Solicitado</th>
                            <th class="text-end">Disp.</th>
                            <th class="text-end">Faltante</th>
                            <th>Orden compra</th>
                            <th>Estatus</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reportes as $reporte)
                            <tr>
                                <td class="text-center">
                                    @if ($reporte->orden_compra_id)
                                        <i class="fa-solid fa-lock text-muted" title="Ya está en una orden de compra"></i>
                                    @else
                                        <input type="checkbox" class="form-check-input chk-reporte" name="reportes[]"
                                            value="{{ $reporte->id }}" form="form-generar-oc">
                                    @endif
                                </td>
                                <td class="fw-semibold">{{ $reporte->folio ?: '—' }}</td>
                                <td class="fs-8" data-order="{{ $reporte->created_at ? $reporte->created_at->timestamp : 0 }}">
                                    {{ $reporte->created_at ? $reporte->created_at->format('d/m/Y') : '—' }} <br>
                                    <span class="text-muted d-block"><i class="fa-solid fa-clock"></i> {{ $reporte->created_at ? $reporte->created_at->format('H:i A') : '—' }}</span>
                                    <span class="text-muted d-block">{{ $reporte->created_at ? $reporte->created_at->diffForHumans() : '' }}</span>
                                </td>
                                <td>
                                    <div>
                                        {{ $reporte->descripcion ?: ($reporte->producto->nombre ?? '—') }}
                                    </div>

                                    @if ($reporte->producto && $reporte->producto->sku)
                                        <span class="text-muted fs-9">{{ $reporte->producto->sku }}</span>
                                    @endif  <br>

                                    @if (($reporte->tipo ?? 'PRODUCTO') === 'MATERIA_PRIMA')
                                        <span class="badge badge-secondary"><i class="fa-solid fa-flask me-1"></i>Materia prima</span>
                                    @endif
                                </td>
                                <td class="fs-8">
                                    @if ($reporte->pedido)
                                        <div>Pedido {{ $reporte->pedido->folio ?? '—' }}</div>
                                        <span class="text-muted">{{ $reporte->pedido->cliente->nombre ?? '' }}</span>
                                    @elseif ($reporte->cotizacion)
                                        <div>Cotización {{ $reporte->cotizacion->folio ?? '—' }}</div>
                                        <span class="text-muted">{{ $reporte->cotizacion->cliente->nombre ?? '' }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end" data-order="{{ $reporte->cantidad_solicitada }}">{{ number_format($reporte->cantidad_solicitada, 2) }}</td>
                                <td class="text-end" data-order="{{ $reporte->existencia_disponible }}">{{ number_format($reporte->existencia_disponible, 2) }}</td>
                                <td class="text-end text-danger fw-semibold" data-order="{{ $reporte->cantidad_faltante }}">{{ number_format($reporte->cantidad_faltante, 2) }}</td>
                                <td class="fs-8">
                                    @if ($reporte->ordenCompra)
                                        <a href="{{ route('ordcompras.editar', $reporte->ordenCompra->id) }}">
                                            {{ $reporte->ordenCompra->folio }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $estatusBadge[$reporte->estatus] ?? 'bg-secondary' }}">
                                        {{ $reporte->estatus }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('compras.reporte_no_existencias.estatus', $reporte->id) }}"
                                        class="d-flex gap-1 justify-content-end">
                                        @csrf
                                        <select name="estatus" class="form-select form-select-sm" style="width:auto;">
                                            @foreach ($estatusOpciones as $opt)
                                                <option value="{{ $opt }}" {{ $reporte->estatus === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-blue-light btn-sm" title="Actualizar estatus">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function () {
    var boton = document.getElementById('btn-generar-oc');
    var conteo = document.getElementById('conteo-seleccion');
    var formGenerar = document.getElementById('form-generar-oc');
    var enviandoOc = false;

    function seleccionadas() {
        return Array.prototype.slice.call(document.querySelectorAll('.chk-reporte')).filter(function (c) { return c.checked; });
    }

    function actualizar() {
        var n = seleccionadas().length;
        if (conteo) conteo.textContent = n;
        if (boton) boton.disabled = n === 0 || enviandoOc;
    }

    var $table = $('#tableNoExistencias');
    if ($table.length && !$.fn.DataTable.isDataTable($table)) {
        $table.DataTable({
            responsive: false,
            scrollX: true,
            autoWidth: false,
            order: [[2, 'desc']],
            paging: true,
            pageLength: 25,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, className: 'text-center' },
                { targets: 10, orderable: false, searchable: false }
            ],
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
                                titleAttr: 'Columnas',
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
                url: 'https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json',
                emptyTable: 'Sin reportes de no existencias.'
            }
        });
    }

    $(document).on('change', '#chk-todos', function () {
        var checked = this.checked;
        $table.find('tbody .chk-reporte').each(function () {
            this.checked = checked;
        });
        actualizar();
    });

    $(document).on('change', '.chk-reporte', function () {
        actualizar();
    });

    if (formGenerar) {
        formGenerar.addEventListener('submit', function (e) {
            if (enviandoOc) {
                return;
            }

            e.preventDefault();

            var n = seleccionadas().length;
            if (n === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin selección',
                    text: 'Seleccione al menos una línea para generar la orden de compra.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#1e3a5f'
                });
                return;
            }

            Swal.fire({
                icon: 'question',
                title: '¿Generar orden de compra?',
                text: 'Se generará una orden de compra con las ' + n + ' línea(s) seleccionada(s). Se quitarán del reporte.',
                showCancelButton: true,
                confirmButtonText: 'Sí, generar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#1e3a5f',
                cancelButtonColor: '#6c757d',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    enviandoOc = true;
                    actualizar();
                    formGenerar.submit();
                }
            });
        });
    }

    actualizar();

    @if (session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: @json(session('success')),
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#1e3a5f'
        });
    @elseif (session('warning'))
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: @json(session('warning')),
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#1e3a5f'
        });
    @endif
});
</script>
@endsection
