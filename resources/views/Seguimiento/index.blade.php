@extends('layouts.app')
@section('content')

<div class="container-fluid format_page">
    <!-- Encabezado -->
    <div class="row">
        <div class="col-lg-3 col-12 start-center">
            <h3 class="mt-1 animate_animated animate_backInLeft">Seguimiento</h3>
            <span class="p-0 m-0 d-none d-md-block fs-8">Suministros, Integraciones y Proyectos.</span>
        </div>

        <div class="col-lg-9 col-12 center-end">
            <button class="btn btn-baseColor" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasGraficas" aria-controls="offcanvasGraficas">
                 <i class="fa-solid fa-chart-simple"></i> Gráficas
            </button>
        </div>
    </div>

    
    {{-- SECCIÓN DE FILTROS --}}
    <div class="row mt-3 mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-filter me-2"></i>Filtros de Búsqueda
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Filtro Fecha Entrega --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fa-solid fa-calendar-days me-1"></i>Fecha Entrega
                            </label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="date" class="form-control form-control-sm" id="filtroFechaEntregaDesde" placeholder="Desde">
                                </div>
                                <div class="col-6">
                                    <input type="date" class="form-control form-control-sm" id="filtroFechaEntregaHasta" placeholder="Hasta">
                                </div>
                            </div>
                        </div>

                        {{-- Filtro Fecha Límite Pago Cliente --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fa-solid fa-calendar-check me-1"></i>F. Lim. Pago Cliente
                            </label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="date" class="form-control form-control-sm" id="filtroFechaLimPagoDesde" placeholder="Desde">
                                </div>
                                <div class="col-6">
                                    <input type="date" class="form-control form-control-sm" id="filtroFechaLimPagoHasta" placeholder="Hasta">
                                </div>
                            </div>
                        </div>

                        {{-- Filtro F. Tentativa Pago Prov --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fa-solid fa-calendar-alt me-1"></i>F. Tent. Pago Prov.
                            </label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="date" class="form-control form-control-sm" id="filtroFechaTentPagoDesde" placeholder="Desde">
                                </div>
                                <div class="col-6">
                                    <input type="date" class="form-control form-control-sm" id="filtroFechaTentPagoHasta" placeholder="Hasta">
                                </div>
                            </div>
                        </div>

                        {{-- Filtro Estado --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fa-solid fa-tag me-1"></i>Estado
                            </label>
                            <select class="form-select form-select-sm" id="filtroEstado">
                                <option value="">Todos</option>
                                @php
                                    $estadosUnicos = $pedidos->pluck('estado')->unique()->filter()->sort();
                                @endphp
                                @foreach($estadosUnicos as $estado)
                                    <option value="{{ $estado }}">{{ $estado }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filtro Vendedor --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fa-solid fa-user-tie me-1"></i>Vendedor
                            </label>
                            <select class="form-select form-select-sm" id="filtroVendedor">
                                <option value="">Todos</option>
                                @php
                                    $vendedoresUnicos = $pedidos->pluck('vendedor')->unique()->filter()->sort();
                                @endphp
                                @foreach($vendedoresUnicos as $vendedor)
                                    <option value="{{ $vendedor }}">{{ $vendedor }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filtro Cliente --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fa-solid fa-building me-1"></i>Cliente
                            </label>
                            <select class="form-select form-select-sm" id="filtroCliente">
                                <option value="">Todos</option>
                                @php
                                    $clientesUnicos = $pedidos->pluck('cliente')->unique()->filter()->sort();
                                @endphp
                                @foreach($clientesUnicos as $cliente)
                                    <option value="{{ $cliente }}">{{ $cliente }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filtro Tipo --}}
                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fa-solid fa-layer-group me-1"></i>Tipo
                            </label>
                            <select class="form-select form-select-sm" id="filtroTipo">
                                <option value="">Todos</option>
                                @php
                                    $tiposUnicos = $pedidos->pluck('tipo')->unique()->filter()->sort();
                                @endphp
                                @foreach($tiposUnicos as $tipo)
                                    <option value="{{ $tipo }}">{{ $tipo }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Botones de Acción --}}
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-sm me-2" id="btnAplicarFiltros">
                                <i class="fa-solid fa-filter me-1"></i>Aplicar Filtros
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnLimpiarFiltros">
                                <i class="fa-solid fa-eraser me-1"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Offcanvas para Gráficas --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasGraficas" aria-labelledby="offcanvasGraficasLabel" style="width: 600px;">
        <div class="offcanvas-header bg-primary text-white">
            <h5 class="offcanvas-title" id="offcanvasGraficasLabel">
                <i class="fa-solid fa-chart-simple me-2"></i>Análisis de Datos
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="mb-4">
                <h6 class="text-muted mb-3">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    Las gráficas se actualizan automáticamente según los filtros aplicados
                </h6>
            </div>

            {{-- Gráfica: Top 5 Pedidos con Más Ingresos --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fa-solid fa-trophy me-2"></i>Top 5 Pedidos con Más Ingresos
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="chartTopPedidos" height="250"></canvas>
                </div>
            </div>

            {{-- Gráfica: Top 5 Vendedores (por cantidad de pedidos) --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fa-solid fa-users me-2"></i>Top 5 Vendedores (Más Pedidos)
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="chartTopVendedores" height="250"></canvas>
                </div>
            </div>

            {{-- Gráfica: Top 5 Clientes (por cantidad de pedidos) --}}
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fa-solid fa-building me-2"></i>Top 5 Clientes (Más Pedidos)
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="chartTopClientes" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>


    {{-- Tabla de seguimiento de pedidos --}}
    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="tableSeguimiento">
                <thead class="tableSeguimiento" id="navbar">
                    <tr>
                        <th>ID</th>
                        <th>Estado</th>
                        <th>Cliente</th>
                        <th>Comprador</th>
                        <th>No. Pedido</th>
                        <th>Tipo</th>
                        <th>Moneda</th>
                        <th class="text-truncate">Ref. SAP</th>
                        <th class="text-truncate">Fecha Recepción</th>
                        <th class="text-truncate">Fecha Entrega</th>
                        <th>Descripción</th>
                        <th>Pedido</th>
                        <th>Proveedores</th>
                        <th class="text-truncate">OC Proveedor</th>
                        <th class="text-truncate">F. Entrega Prov.</th>
                        <th class="text-truncate">Pago Proveedor</th>
                        <th class="text-truncate">F. Tent. Pago Prov.</th>
                        <th class="text-truncate">F. Pago Prov.</th>
                        <th class="text-truncate">Monto por Facturar</th>
                        <th class="text-truncate">Folio Factura</th>
                        <th class="text-truncate">Monto Facturado</th>
                        <th class="text-truncate">F. Entrega Realizada</th>
                        <th class="text-truncate">Aceptó Factura</th>
                        <th class="text-truncate">F. Lim. Pago Cliente</th>
                        <th class="text-truncate">F. Pago Cliente</th>
                        <th class="text-truncate">Monto Pagado</th>
                        <th>Extras</th>
                        <th>Vendedor</th>
                        <th colspan="2">Comisión</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        $totalPedido = 0;
                        $totalPagoProveedor = 0;
                        $totalMontoPorFacturar = 0;
                        $totalMontoFacturado = 0;
                        $totalMontoPagado = 0;
                        $totalExtras = 0;
                        $totalComision = 0;
                    @endphp
                    @forelse($pedidos as $pedido)
                        @php
                            // Calcular comisión para este pedido
                            $porcentaje = ($pedido->porc_utilidad_vendedor ?? 0) / 100;
                            $v_oc = round((($pedido->monto_pagado ?? 0) / 1.16), 2);
                            $v_compra = round((($pedido->pago_proveedor ?? 0) / 1.16), 2);
                            $v_extras = round((($pedido->extras ?? 0) / 1.16), 2);
                            $v_utilidad = round(($v_oc - $v_compra - $v_extras), 2);
                            $v_comision = $v_utilidad > 0 ? round(($v_utilidad * $porcentaje), 2) : 0;
                            
                            // Sumar al total de comisión
                            $totalPedido += $pedido->pedido ?? 0;
                            $totalPagoProveedor += $pedido->pago_proveedor ?? 0;
                            $totalMontoPorFacturar += $pedido->monto_por_facturar ?? 0;
                            $totalMontoFacturado += $pedido->monto_facturado ?? 0;
                            $totalMontoPagado += $pedido->monto_pagado ?? 0;
                            $totalExtras += $pedido->extras ?? 0;
                            $totalComision += $v_comision;
                        @endphp
                        <tr>
                            <td class="text-truncate">{{ $pedido->id_serv_enc ?? '-' }}</td>
                            <td class="estado-cell" style="font-size:11px!important;">
                                <span class="badge estado-badge">{{ $pedido->estado ?? '-'}}</span>
                            </td>
                            <td style="font-size:11px!important;">{{ $pedido->cliente ?? '-' }}</td>
                            <td style="font-size:11px!important;">{{ $pedido->comprador ?? '-' }}</td>
                            <td class="text-truncate">{{ $pedido->numero_pedido ?? '-' }}</td>
                            <td class="text-truncate tipo-cell" style="font-size:11px!important;">
                                @if($pedido->tipo && $pedido->tipo != '-')
                                    <span class="badge tipo-badge" data-tipo="{{ strtolower($pedido->tipo) }}">
                                        {{ $pedido->tipo }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">-</span>
                                @endif
                            </td>
                            <td class="text-truncate">{{ $pedido->moneda ?? '-' }}</td>
                            <td class="text-truncate">{{ $pedido->ref_sap ?? '-' }}</td>
                            <td class="text-truncate" style="font-size:11px!important;">
                                @if($pedido->fecha_recepcion)
                                    {{date('d/m/Y', strtotime($pedido->fecha_recepcion))}}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="fecha-cell" style="font-size:11px!important;"
                                @if($pedido->fecha_entrega)
                                    data-date="{{ \Carbon\Carbon::parse($pedido->fecha_entrega)->format('Y-m-d') }}"
                                @endif>
                                @if($pedido->fecha_entrega)
                                    {{date('d/m/Y', strtotime($pedido->fecha_entrega))}}
                                @else
                                    -
                                @endif
                            </td>
                            <td style="font-size:11px!important;">{{ $pedido->descripcion ?? '-' }}</td>
                            <td class="cantidad-cell" style="font-size:11px!important;">
                                ${{ number_format($pedido->pedido ?? 0, 2) }}
                            </td>
                            <td style="font-size:11px!important;">{{ $pedido->proveedores ?? '-' }}</td>
                            <td style="font-size:11px!important;">{{ $pedido->oc_prov ?? '-' }}</td>
                            <td class="fecha-cell" style="font-size:11px!important;"
                                @if($pedido->f_entrega_prov)
                                    data-date="{{ \Carbon\Carbon::parse($pedido->f_entrega_prov)->format('Y-m-d') }}"
                                @endif>
                                @if($pedido->f_entrega_prov)
                                    {{date('d/m/Y', strtotime($pedido->f_entrega_prov))}}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="cantidad-cell" style="font-size:11px!important;">
                                ${{ number_format($pedido->pago_proveedor ?? 0, 2) }}
                            </td>
                            <td class="fecha-cell" style="font-size:11px!important;"
                                @if($pedido->fecha_tent_pago_prov)
                                    data-date="{{ \Carbon\Carbon::parse($pedido->fecha_tent_pago_prov)->format('Y-m-d') }}"
                                @endif>
                                @if($pedido->fecha_tent_pago_prov)
                                    {{date('d/m/Y', strtotime($pedido->fecha_tent_pago_prov))}}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="fecha-cell" style="font-size:11px!important;"
                                @if($pedido->fecha_pago_prov && $pedido->fecha_pago_prov != '')
                                    data-date="{{ \Carbon\Carbon::parse($pedido->fecha_pago_prov)->format('Y-m-d') }}"
                                @endif>
                                @if($pedido->fecha_pago_prov)
                                    {{date('d/m/Y', strtotime($pedido->fecha_pago_prov))}}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="cantidad-cell" style="font-size:11px!important;">
                                ${{ number_format($pedido->monto_por_facturar ?? 0, 2) }}
                            </td>
                            <td class="text-truncate">{{ $pedido->folio_factura ?? '-' }}</td>
                            <td class="cantidad-cell" style="font-size:11px!important;">
                                ${{ number_format($pedido->monto_facturado ?? 0, 2) }}
                            </td>
                            <td class="fecha-cell" style="font-size:11px!important;"
                                @if($pedido->fecha_entrega_realizada)
                                    data-date="{{ \Carbon\Carbon::parse($pedido->fecha_entrega_realizada)->format('Y-m-d') }}"
                                @endif>
                                @if($pedido->fecha_entrega_realizada)
                                    {{date('d/m/Y', strtotime($pedido->fecha_entrega_realizada))}}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($pedido->acepto_factura == 'ok')
                                    <span class="badge bg-success fs-10">Sí</span>
                                @else
                                    <span class="badge bg-secondary fs-10">No</span>
                                @endif
                            </td>
                            <td class="fecha-cell" style="font-size:11px!important;"
                                @if($pedido->fecha_lim_pago_cli)
                                    data-date="{{ \Carbon\Carbon::parse($pedido->fecha_lim_pago_cli)->format('Y-m-d') }}"
                                @endif>

                                @if($pedido->fecha_lim_pago_cli)
                                    {{ date('d/m/Y', strtotime($pedido->fecha_lim_pago_cli))}}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="fecha-cell" style="font-size:11px!important;"
                                @if($pedido->fecha_pago_cli && $pedido->fecha_pago_cli != '')
                                    data-date="{{ \Carbon\Carbon::parse($pedido->fecha_pago_cli)->format('Y-m-d') }}"
                                @endif>

                                @if($pedido->fecha_pago_cli)
                                    {{date('d/m/Y', strtotime($pedido->fecha_pago_cli))}}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="cantidad-cell" style="font-size:11px!important;">
                                ${{ number_format($pedido->monto_pagado ?? 0, 2) }}
                            </td>

                            <td class="cantidad-cell" style="font-size:11px!important;">
                                ${{ number_format($pedido->extras ?? 0, 2) }}
                            </td>

                            <td style="font-size:11px!important;">{{ $pedido->vendedor ?? '-' }}</td>
                            
                        
                            <td class="cantidad-cell">
                                 <!-- Button trigger modal -->
                                <button type="button" class="btn btn-primary fs-9 p-1" data-bs-toggle="modal" data-bs-target="#modalcomision{{$pedido->id_serv_enc}}">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </td>

                            <td class="cantidad-cell comision-cell" id="comision-{{$pedido->id_serv_enc}}" style="font-size:11px!important;">
                                ${{ number_format($v_comision, 2) }} 
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="28" class="text-center">No hay registros de seguimiento disponibles</td>
                        </tr>
                    @endforelse
                </tbody>
                
                <tfoot class="table-footer">
                    <tr>
                        <th colspan="11" class="text-end fw-bold footer-label-cell">
                            <div class="d-flex flex-column">
                                <span>TOTALES (Página Actual):</span>
                                <small class="footer-info-text" id="footer-info-page"></small>
                            </div>
                        </th>
                        <th class="cantidad-total-cell fw-bold" id="total-pedido">
                            $0.00
                        </th>
                        <th colspan="3"></th>
                        <th class="cantidad-total-cell fw-bold" id="total-pago-proveedor">
                            $0.00
                        </th>
                        <th colspan="2"></th>
                        <th class="cantidad-total-cell fw-bold" id="total-monto-facturar">
                            $0.00
                        </th>
                        <th></th>
                        <th class="cantidad-total-cell fw-bold" id="total-monto-facturado">
                            $0.00
                        </th>
                        <th colspan="4"></th>
                        <th class="cantidad-total-cell fw-bold" id="total-monto-pagado">
                            $0.00
                        </th>
                        <th class="cantidad-total-cell fw-bold" id="total-extras">
                            $0.00
                        </th>
                        <th></th>
                        <th></th>
                        <th class="cantidad-total-cell fw-bold" id="total-comision">
                            $0.00
                        </th>
                    </tr>
                    <tr class="footer-totales-globales">
                        <th colspan="11" class="text-end fw-bold footer-label-cell-global">
                            <div class="d-flex flex-column">
                                <span>TOTALES (Todos los Registros):</span>
                                <small class="footer-info-text" id="footer-info-total"></small>
                            </div>
                        </th>
                        <th class="cantidad-total-cell-global fw-bold" id="total-pedido-global">
                            ${{ number_format($totalPedido, 2) }}
                        </th>
                        <th colspan="3"></th>
                        <th class="cantidad-total-cell-global fw-bold" id="total-pago-proveedor-global">
                            ${{ number_format($totalPagoProveedor, 2) }}
                        </th>
                        <th colspan="2"></th>
                        <th class="cantidad-total-cell-global fw-bold" id="total-monto-facturar-global">
                            ${{ number_format($totalMontoPorFacturar, 2) }}
                        </th>
                        <th></th>
                        <th class="cantidad-total-cell-global fw-bold" id="total-monto-facturado-global">
                            ${{ number_format($totalMontoFacturado, 2) }}
                        </th>
                        <th colspan="4"></th>
                        <th class="cantidad-total-cell-global fw-bold" id="total-monto-pagado-global">
                            ${{ number_format($totalMontoPagado, 2) }}
                        </th>
                        <th class="cantidad-total-cell-global fw-bold" id="total-extras-global">
                            ${{ number_format($totalExtras, 2) }}
                        </th>
                        <th></th>
                        <th></th>
                        <th class="cantidad-total-cell-global fw-bold" id="total-comision-global">
                            ${{ number_format($totalComision, 2) }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@foreach($pedidos as $pedido)
{{-- modal comision --}}
    <div class="modal fade modal-comision" id="modalcomision{{$pedido->id_serv_enc}}" tabindex="-1" aria-labelledby="modalComisionLabel{{$pedido->id_serv_enc}}" aria-hidden="true"
        data-id="{{$pedido->id_serv_enc}}"
        data-monto-pagado="{{$pedido->monto_pagado ?? 0}}"
        data-pago-proveedor="{{$pedido->pago_proveedor ?? 0}}"
        data-extras="{{$pedido->extras ?? 0}}"
        data-porcentaje="{{$pedido->porc_utilidad_vendedor ?? 0}}"
        data-comision-actual="{{$pedido->comision ?? 0}}">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0 modal-comision-content">
                <div class="modal-header modal-comision-header">
                    <div>
                        <h5 class="modal-title mb-1" id="modalComisionLabel{{$pedido->id_serv_enc}}">
                            <i class="fa-solid fa-calculator me-2"></i> Cálculo de Comisión
                        </h5>
                        <small class="text-white-50">ID: {{$pedido->id_serv_enc}} | Cliente: {{$pedido->cliente ?? 'N/A'}}</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="info-box p-3 rounded">
                                <label class="form-label fw-bold text-primary mb-2">
                                    <i class="fa-solid fa-user-tie me-2"></i>Vendedor
                                </label>
                                <div class="fs-5 fw-semibold text-dark">{{ $pedido->vendedor ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-primary mb-2">
                                <i class="fa-solid fa-percent me-2"></i>Porcentaje de Comisión (%)
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-primary text-white border-primary">
                                    <i class="fa-solid fa-percent"></i>
                                </span>
                                <input type="number" 
                                    min="0" 
                                    max="100" 
                                    step="0.01" 
                                    class="form-control form-control-lg input-porc-comision border-primary" 
                                    value="{{$pedido->porc_utilidad_vendedor ?? 0}}" 
                                    data-id="{{$pedido->id_serv_enc}}"
                                    placeholder="0.00">
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                Ajusta el porcentaje para recalcular la comisión automáticamente.
                            </small>
                        </div>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                        <i class="fa-solid fa-circle-info me-2 fs-5"></i>
                        <div>
                            <strong>Valores Base (con IVA):</strong>
                            Monto Pagado: ${{number_format($pedido->monto_pagado ?? 0,2)}} | 
                            Pago Proveedor: ${{number_format($pedido->pago_proveedor ?? 0,2)}} | 
                            Extras: ${{number_format($pedido->extras ?? 0,2)}}
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr class="table-primary">
                                    <th class="text-center fw-bold py-3">
                                        <i class="fa-solid fa-receipt me-2"></i>Valor OC<br>
                                        <small class="text-muted">(sin IVA)</small>
                                    </th>
                                    <th class="text-center fw-bold py-3">
                                        <i class="fa-solid fa-shopping-cart me-2"></i>Valor Compra<br>
                                        <small class="text-muted">(sin IVA)</small>
                                    </th>
                                    <th class="text-center fw-bold py-3">
                                        <i class="fa-solid fa-plus-circle me-2"></i>Extras<br>
                                        <small class="text-muted">(sin IVA)</small>
                                    </th>
                                    <th class="text-center fw-bold py-3 bg-success text-white">
                                        <i class="fa-solid fa-chart-line me-2"></i>Utilidad<br>
                                        <small class="text-white-50">(OC - Compra - Extras)</small>
                                    </th>
                                    <th class="text-center fw-bold py-3 bg-primary text-white">
                                        <i class="fa-solid fa-dollar-sign me-2"></i>Comisión<br>
                                        <small class="text-white">(Utilidad × %)</small>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-light">
                                    <td class="text-center fw-bold fs-5" id="modal-oc-{{$pedido->id_serv_enc}}">
                                        ${{number_format(round((($pedido->monto_pagado ?? 0)/1.16),2), 2)}}
                                    </td>
                                    <td class="text-center fw-bold fs-5" id="modal-compra-{{$pedido->id_serv_enc}}">
                                        ${{number_format(round((($pedido->pago_proveedor ?? 0)/1.16),2), 2)}}
                                    </td>
                                    <td class="text-center fw-bold fs-5" id="modal-extras-{{$pedido->id_serv_enc}}">
                                        ${{number_format(round((($pedido->extras ?? 0)/1.16),2), 2)}}
                                    </td>
                                    <td class="text-center fw-bold fs-5 text-success" id="modal-utilidad-{{$pedido->id_serv_enc}}">
                                        ${{number_format(round((($pedido->monto_pagado ?? 0)/1.16) - (($pedido->pago_proveedor ?? 0)/1.16) - (($pedido->extras ?? 0)/1.16), 2), 2)}}
                                    </td>
                                    <td class="text-center fw-bold fs-5" id="modal-comision-{{$pedido->id_serv_enc}}" style="color: #0066cc !important;">
                                        ${{number_format($v_comision, 2)}}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 p-3 bg-light rounded">
                        <div class="row text-center">
                            <div class="col-md-12">
                                <small class="text-muted d-block">Comisión Calculada</small>
                                <strong class="fs-4 text-primary" id="comision-nueva-display-{{$pedido->id_serv_enc}}">
                                    ${{number_format($v_comision, 2)}}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer modal-comision-footer">
                    <button type="button" class="btn btn-secondary btn-lg" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary btn-lg btn-guardar-comision" data-id="{{$pedido->id_serv_enc}}">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach


<style>
    #tableSeguimiento_wrapper {
        overflow-x: auto;
        width: 100%;
    }
    #tableSeguimiento {
        min-width: 100%;
    }
    .dataTables_scrollHeadInner {
        width: 100% !important;
    }
    .dataTables_scrollBody {
        overflow-x: auto !important;
    }
    /* Estilos para indicadores de tiempo en fechas */
    .fecha-cell.fecha-cerca {
        background-color: #fff3cd !important;
        color: #856404 !important;
        font-weight: 600;
    }
    .fecha-cell.fecha-vencida {
        background-color: #f8d7da !important;
        color: #721c24 !important;
        font-weight: 600;
    }
    /* Estilos para resaltar cantidades */
    .cantidad-cell {
        background-color: #e7f3ff !important;
        color: #004085 !important;
        font-weight: 700 !important;
        text-align: right !important;
        padding: 8px 12px !important;
        border-left: 3px solid #0066cc !important;
    }
    .cantidad-cell:hover {
        background-color: #cce7ff !important;
        transform: scale(1.02);
        transition: all 0.2s ease;
    }
    /* Estilos para resaltar estados */
    .estado-cell {
        text-align: center !important;
        padding: 8px 12px !important;
    }
    .estado-badge {
        font-weight: 700 !important;
        font-size: 11px !important;
        padding: 6px 12px !important;
        border-radius: 20px !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: inline-block;
        min-width: 80px;
    }
    /* Estilos modal comisión */
    .modal-comision-content {
        border-radius: 15px;
        overflow: hidden;
    }
    .modal-comision-header {
        background: linear-gradient(135deg, #0066cc 0%, #004085 100%);
        color: white;
        padding: 1.5rem;
        border-bottom: none;
    }
    .modal-comision-header .modal-title {
        color: white;
        font-weight: 700;
        font-size: 1.5rem;
    }
    .modal-comision .info-box {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-left: 4px solid #0066cc;
    }
    .modal-comision .input-porc-comision {
        font-size: 1.25rem;
        font-weight: 600;
        text-align: center;
    }
    .modal-comision .input-porc-comision:focus {
        border-color: #0066cc;
        box-shadow: 0 0 0 0.2rem rgba(0, 102, 204, 0.25);
    }
    .modal-comision .table {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .modal-comision .table th {
        font-size: 0.9rem;
        vertical-align: middle;
    }
    .modal-comision .table td {
        font-size: 1rem;
        vertical-align: middle;
        padding: 1rem;
    }
    .modal-comision-footer {
        background: #f8f9fa;
        border-top: 2px solid #dee2e6;
        padding: 1.25rem;
    }
    .modal-comision .btn-lg {
        padding: 0.75rem 2rem;
        font-weight: 600;
        border-radius: 8px;
    }
    .modal-comision .btn-primary {
        background: linear-gradient(135deg, #0066cc 0%, #004085 100%);
        border: none;
        box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
        transition: all 0.3s ease;
    }
    .modal-comision .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 102, 204, 0.4);
    }
    .modal-comision .alert {
        border-left: 4px solid #0066cc;
        border-radius: 8px;
    }
    /* Estilos para el footer con totales */
    
   
    .cantidad-total-cell {
        background-color: #0066cc !important;
        color: white !important;
        font-weight: 700 !important;
        text-align: right !important;
        padding: 12px 12px !important;
        font-size: 13px !important;
        border-left: 3px solid #004085 !important;
    }
    .footer-label-cell {
        background-color: #ff9500 !important;
        color: white !important;
        padding: 12px !important;
        font-weight: 700 !important;
        vertical-align: middle !important;
    }
    .footer-label-cell-global {
        background-color: #28a745 !important;
        color: white !important;
        padding: 12px !important;
        font-weight: 700 !important;
        vertical-align: middle !important;
    }
    .footer-info-text {
        font-size: 0.75rem;
        opacity: 0.9;
        font-weight: 400;
    }
    .cantidad-total-cell-global {
        background-color: #28a745 !important;
        color: white !important;
        font-weight: 700 !important;
        text-align: right !important;
        padding: 12px 12px !important;
        font-size: 13px !important;
        border-left: 3px solid #1e7e34 !important;
    }
    .footer-totales-globales {
        border-top: 2px solid #1e7e34 !important;
    }
    /* Estilos para badges de tipo */
    .tipo-badge {
        font-weight: 600 !important;
        font-size: 10px !important;
        padding: 5px 10px !important;
        border-radius: 12px !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: inline-block;
    }
    .tipo-cell {
        text-align: center !important;
    }
</style>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    let table;
    $(document).ready( function () {
    table = $('#tableSeguimiento').DataTable( {
        scrollX: true,
        scrollY: false,
        scrollCollapse: false,
        autoWidth: false,
        fixedColumns: false,
        select: false,
        order: [[0, 'desc']], // Ordenar por la primera columna (ID) en orden descendente
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        layout: {
                topStart: {
                    buttons: [ 
                        {
                            extend: 'copy',
                            text:'<i class="fa-regular fa-copy"></i>',
                            titleAttr:'Copiar',
                            className:'btn btn-tool-copy push'
                        },
    
                        {
                            extend: 'excel',
                            text:'<i class="fa-regular fa-file-excel"></i>',
                            titleAttr:'Excel',
                            className:'btn btn-tool-excel push'
                        },
    
                        {
                            extend: 'pdf',
                            text:'<i class="fa-regular fa-file-pdf"></i>',
                            titleAttr:'PDF',
                            className:'btn btn-tool-pdf push'
                        },
    
                        {
                            extend: 'print',
                            text:'<i class="fa-solid fa-print"></i>',
                            titleAttr:'Imprimir',
                            className:'btn btn-tool-print push'
                        },
    
                        {
                            extend: 'colvis',
                            text:'<i class="fa-solid fa-filter"></i>',
                            titleAttr:'Filtrar',
                            className:'btn btn-tool-colvis push'
                        }
                    ]
            },
            topEnd: 'search',
            bottomStart: 'pageLength',
            bottomEnd: 'paging'
        },
        "oLanguage": {
        "sSearch": '<i class="fa-solid fa-magnifying-glass"></i>'
        },
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.5/i18n/es-MX.json"
        },
        initComplete: function() {
            // Crear contenedor para los selects de filtro
            var filterContainer = $('<div class="d-flex flex-wrap gap-2 mb-2" id="columnFilters"></div>');
            var searchContainer = $('#tableSeguimiento_wrapper .dataTables_filter');
            
            // Agregar contenedor antes del search
            if (searchContainer.length) {
                searchContainer.before(filterContainer);
            } else {
                $('#tableSeguimiento_wrapper .topEnd').prepend(filterContainer);
            }
            
            // Agregar selects individuales para filtrar columnas
            this.api().columns([1, 2, 5, 27]).every(function() {
                var column = this;
                var columnIndex = column.index();
                var columnTitle = $(column.header()).text();
                
                // Crear select
                var select = $('<select class="form-select form-select-sm" style="min-width: 150px;"><option value="">Todos: ' + columnTitle + '</option></select>')
                    .appendTo(filterContainer)
                    .on('change', function() {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                        recalcularFooter();
                    });
                
                // Llenar select con valores únicos
                column.data().unique().sort().each(function(d) {
                    var text = '';
                    if (typeof d === 'string') {
                        // Para estados y tipos, extraer el texto del badge si existe
                        if (columnIndex === 1 || columnIndex === 5) {
                            var tempDiv = $('<div>').html(d);
                            text = tempDiv.text().trim();
                        } else {
                            text = d.trim();
                        }
                    } else {
                        text = String(d).trim();
                    }
                    
                    if (text && text !== '-' && text !== '' && text !== 'Todos') {
                        // Evitar duplicados
                        if (select.find('option[value="' + text + '"]').length === 0) {
                            select.append('<option value="' + text + '">' + text + '</option>');
                        }
                    }
                });
            });
        },
        footerCallback: function (row, data, start, end, display) {
            recalcularFooter();
        },
        drawCallback: function() {
            // Función para evaluar y colorear fechas
            evaluarFechas();
            // Función para colorear estados
            colorearEstados();
            // Función para colorear tipos
            colorearTipos();
        }
    } );
    
    // Función para evaluar fechas y aplicar estilos
    function evaluarFechas() {
        var hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        
        // Obtener todas las celdas con clase fecha-cell
        $('.fecha-cell').each(function() {
            var $celda = $(this);
            var fechaStr = $celda.attr('data-date');
            
            // Remover clases anteriores
            $celda.removeClass('fecha-cerca fecha-vencida');
            
            // Si tiene fecha válida
            if (fechaStr && fechaStr !== '') {
                var fecha = new Date(fechaStr);
                fecha.setHours(0, 0, 0, 0);
                
                // Calcular diferencia en días
                var diffTime = fecha - hoy;
                var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                // Si la fecha ya pasó o es hoy (diferencia <= 0)
                if (diffDays <= 0) {
                    $celda.addClass('fecha-vencida');
                }
                // Si la fecha está cerca (entre 1 y 5 días)
                else if (diffDays <= 5) {
                    $celda.addClass('fecha-cerca');
                }
            }
        });
    }
    
    // Función para colorear estados dinámicamente
    function colorearEstados() {
        $('.estado-badge').each(function() {
            var $badge = $(this);
            var estado = $badge.text().toLowerCase().trim();
            
            // Remover clases de color anteriores
            $badge.removeClass('bg-success bg-warning bg-danger bg-info bg-primary bg-secondary');
            
            // Aplicar colores según el estado
            if (estado.includes('completado') || estado.includes('finalizado') || estado.includes('cerrado') || estado.includes('aprobado')) {
                $badge.addClass('bg-success text-white');
            } else if (estado.includes('pendiente') || estado.includes('proceso') || estado.includes('en curso') || estado.includes('en trámite')) {
                $badge.addClass('bg-warning text-dark');
            } else if (estado.includes('cancelado') || estado.includes('rechazado') || estado.includes('anulado')) {
                $badge.addClass('bg-danger text-white');
            } else if (estado.includes('revisión') || estado.includes('revisando')) {
                $badge.addClass('bg-info text-white');
            } else {
                // Color por defecto para estados no reconocidos
                $badge.addClass('bg-primary text-white');
            }
        });
    }
    // Formato moneda
    function formatearNumero(num) {
        return '$' + (num || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    // Recalcular footer con DataTables API
    function recalcularFooter() {
        var intVal = function (i) {
            if (typeof i === 'string') {
                // Remover símbolos de moneda, comas, espacios y caracteres no numéricos excepto punto y guión
                var cleaned = i.replace(/[\$,\s]/g, '').trim();
                // Si está vacío o es solo un guión, retornar 0
                if (cleaned === '' || cleaned === '-') {
                    return 0;
                }
                var num = parseFloat(cleaned);
                return isNaN(num) ? 0 : num;
            }
            return typeof i === 'number' ? (isNaN(i) ? 0 : i) : 0;
        };

        var calcularTotal = function(columnIndex, scope) {
            var options = scope === 'all' ? { search: 'applied' } : { page: 'current', search: 'applied' };
            var total = 0;
            var columnData = table.column(columnIndex, options).data();
            
            columnData.each(function(value) {
                total += intVal(value);
            });
            
            return Math.round(total * 100) / 100; // Redondear a 2 decimales
        };

        // Totales de la página actual (con filtros aplicados)
        // Índices de columnas: 0=ID, 1=Estado, 2=Cliente, 3=Comprador, 4=No.Pedido, 5=Tipo, 6=Moneda, 7=Ref.SAP, 8=Fecha Recepción, 9=Fecha Entrega, 10=Descripción, 11=Pedido, 12=Proveedores, 13=OC Proveedor, 14=F.Entrega Prov, 15=Pago Proveedor, 16=F.Tent.Pago Prov, 17=F.Pago Prov, 18=Monto por Facturar, 19=Folio Factura, 20=Monto Facturado, 21=F.Entrega Realizada, 22=Aceptó Factura, 23=F.Lim.Pago Cliente, 24=F.Pago Cliente, 25=Monto Pagado, 26=Extras, 27=Vendedor, 28=Botón Comisión, 29=Comisión
        var totalPedido = calcularTotal(11, 'page');
        var totalPagoProveedor = calcularTotal(15, 'page');
        var totalMontoPorFacturar = calcularTotal(18, 'page');
        var totalMontoFacturado = calcularTotal(20, 'page');
        var totalMontoPagado = calcularTotal(25, 'page');
        var totalExtras = calcularTotal(26, 'page');
        var totalComision = calcularTotal(29, 'page');

        // Totales de todos los registros (con filtros aplicados)
        var totalPedidoAll = calcularTotal(11, 'all');
        var totalPagoProveedorAll = calcularTotal(15, 'all');
        var totalMontoPorFacturarAll = calcularTotal(18, 'all');
        var totalMontoFacturadoAll = calcularTotal(20, 'all');
        var totalMontoPagadoAll = calcularTotal(25, 'all');
        var totalExtrasAll = calcularTotal(26, 'all');
        var totalComisionAll = calcularTotal(29, 'all');

        // Actualizar totales de página actual
        $('#total-pedido').html(formatearNumero(totalPedido));
        $('#total-pago-proveedor').html(formatearNumero(totalPagoProveedor));
        $('#total-monto-facturar').html(formatearNumero(totalMontoPorFacturar));
        $('#total-monto-facturado').html(formatearNumero(totalMontoFacturado));
        $('#total-monto-pagado').html(formatearNumero(totalMontoPagado));
        $('#total-extras').html(formatearNumero(totalExtras));
        $('#total-comision').html(formatearNumero(totalComision));

        // Actualizar totales globales (con filtros)
        $('#total-pedido-global').html(formatearNumero(totalPedidoAll));
        $('#total-pago-proveedor-global').html(formatearNumero(totalPagoProveedorAll));
        $('#total-monto-facturar-global').html(formatearNumero(totalMontoPorFacturarAll));
        $('#total-monto-facturado-global').html(formatearNumero(totalMontoFacturadoAll));
        $('#total-monto-pagado-global').html(formatearNumero(totalMontoPagadoAll));
        $('#total-extras-global').html(formatearNumero(totalExtrasAll));
        $('#total-comision-global').html(formatearNumero(totalComisionAll));

        // Actualizar información de registros
        var pageInfo = table.page.info();
        var recordsDisplay = pageInfo.recordsDisplay;
        var recordsTotal = pageInfo.recordsTotal;
        var start = pageInfo.start + 1;
        var end = pageInfo.end;
        var currentPage = pageInfo.page + 1;
        var totalPages = pageInfo.pages;

        $('#footer-info-page').text('Mostrando ' + start + ' a ' + end + ' de ' + recordsDisplay + ' registros (Página ' + currentPage + ' de ' + totalPages + ')');
        $('#footer-info-total').text('Total de ' + recordsDisplay + ' registros filtrados de ' + recordsTotal + ' totales');
    }

    // Calcula comisión dentro del modal según porcentaje ingresado
    function calcularComisionModal(id) {
        var modal = $('#modalcomision' + id);
        var porcentajeInput = modal.find('.input-porc-comision');
        var porcentaje = parseFloat(porcentajeInput.val() || 0) / 100;

        // Validar que el porcentaje esté entre 0 y 100
        if (porcentaje < 0) porcentaje = 0;
        if (porcentaje > 1) porcentaje = 1;

        var montoPagado = parseFloat(modal.data('monto-pagado') || 0);
        var pagoProveedor = parseFloat(modal.data('pago-proveedor') || 0);
        var extras = parseFloat(modal.data('extras') || 0);

        var valorOC = Math.round((montoPagado / 1.16) * 100) / 100;
        var valorCompra = Math.round((pagoProveedor / 1.16) * 100) / 100;
        var valorExtras = Math.round((extras / 1.16) * 100) / 100;
        var utilidad = Math.round((valorOC - valorCompra - valorExtras) * 100) / 100;
        var comision = utilidad > 0 ? Math.round((utilidad * porcentaje) * 100) / 100 : 0;

        // Actualizar valores en la tabla del modal
        $('#modal-oc-' + id).text(formatearNumero(valorOC));
        $('#modal-compra-' + id).text(formatearNumero(valorCompra));
        $('#modal-extras-' + id).text(formatearNumero(valorExtras));
        
        // Actualizar utilidad con color
        var $utilidad = $('#modal-utilidad-' + id);
        $utilidad.text(formatearNumero(utilidad > 0 ? utilidad : 0));
        $utilidad.removeClass('text-success text-danger');
        $utilidad.addClass(utilidad > 0 ? 'text-success' : 'text-danger');
        
        // Actualizar comisión con color primary
        var $comision = $('#modal-comision-' + id);
        $comision.text(formatearNumero(comision));
        $comision.removeClass('text-warning text-danger text-primary');
        $comision.addClass('text-primary');
        $comision.css('color', '#0066cc'); // Asegurar color primary

        // Actualizar display de comisión nueva
        $('#comision-nueva-display-' + id).text(formatearNumero(comision));

        return { comision: comision, porcentaje: porcentaje * 100 };
    }

    // Guardar cambios de comisión (front-end y backend)
    $(document).on('click', '.btn-guardar-comision', function() {
        var id = $(this).data('id');
        var result = calcularComisionModal(id);
        var comisionFormateada = formatearNumero(result.comision);
        var btn = $(this);
        var originalHtml = btn.html();

        // Deshabilitar botón mientras se procesa
        btn.html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando...').prop('disabled', true);

        // Enviar datos al backend
        $.ajax({
            url: '{{ route("seguimiento.actualizar-comision") }}',
            method: 'POST',
            data: {
                id_serv_enc: id,
                porc_utilidad_vendedor: result.porcentaje,
                comision: result.comision,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar la celda de comisión en la tabla
                    $('#comision-' + id).html(comisionFormateada + '<br>');
                    
                    // Actualizar el atributo data-comision-actual del modal
                    $('#modalcomision' + id).data('comision-actual', result.comision);
                    $('#modalcomision' + id).data('porcentaje', result.porcentaje);
                    
                    // Actualizar el display de comisión actual
                    $('#comision-actual-display-' + id).text(comisionFormateada);

                    // Recalcular el total de comisión en el footer
                    recalcularFooter();

                    // Mostrar mensaje de éxito
                    btn.html('<i class="fa-solid fa-check me-2"></i>Guardado!').removeClass('btn-primary').addClass('btn-success');
                    
                    setTimeout(function() {
                        btn.html(originalHtml).removeClass('btn-success').addClass('btn-primary').prop('disabled', false);
                        $('#modalcomision' + id).modal('hide');
                    }, 1500);
                } else {
                    throw new Error(response.message || 'Error al guardar');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Error al guardar los cambios';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                btn.html('<i class="fa-solid fa-exclamation-triangle me-2"></i>Error').removeClass('btn-primary').addClass('btn-danger');
                
                // Mostrar alerta de error
                alert('Error: ' + errorMsg);
                
                setTimeout(function() {
                    btn.html(originalHtml).removeClass('btn-danger').addClass('btn-primary').prop('disabled', false);
                }, 2000);
            }
        });
    });

    // Recalcular en tiempo real cuando cambia el porcentaje
    $(document).on('input change', '.input-porc-comision', function() {
        var id = $(this).data('id');
        calcularComisionModal(id);
    });

    // Inicializar cálculo cuando se abre el modal
    $(document).on('shown.bs.modal', '.modal-comision', function() {
        var id = $(this).data('id');
        calcularComisionModal(id);
    });

    // Función para colorear badges de tipo
    function colorearTipos() {
        $('.tipo-badge').each(function() {
            var $badge = $(this);
            var tipo = $badge.data('tipo') || $badge.text().toLowerCase().trim();
            
            // Remover clases de color anteriores
            $badge.removeClass('bg-primary bg-success bg-info bg-warning bg-danger bg-secondary bg-dark');
            
            // Aplicar colores según el tipo
            if (tipo.includes('suministro') || tipo.includes('supply')) {
                $badge.addClass('bg-primary text-white');
            } else if (tipo.includes('integración') || tipo.includes('integra') || tipo.includes('integration')) {
                $badge.addClass('bg-success text-white');
            } else if (tipo.includes('proyecto') || tipo.includes('project')) {
                $badge.addClass('bg-info text-white');
            } else if (tipo.includes('servicio') || tipo.includes('service')) {
                $badge.addClass('bg-warning text-dark');
            } else if (tipo.includes('mantenimiento') || tipo.includes('maintenance')) {
                $badge.addClass('bg-danger text-white');
            } else {
                // Color por defecto
                $badge.addClass('bg-secondary text-white');
            }
        });
    }

    // Variable para almacenar el filtro personalizado
    var filtroPersonalizado = null;

    // Función para extraer fecha de una celda
    function extraerFecha(html) {
        if (!html) return '';
        // Buscar atributo data-date
        var match = html.match(/data-date="([^"]+)"/);
        if (match) {
            return match[1];
        }
        // Si no tiene data-date, intentar extraer de formato dd/mm/yyyy
        var fechaMatch = html.match(/(\d{2})\/(\d{2})\/(\d{4})/);
        if (fechaMatch) {
            return fechaMatch[3] + '-' + fechaMatch[2] + '-' + fechaMatch[1];
        }
        return '';
    }

    // Función para aplicar filtros
    function aplicarFiltros() {
        // Remover filtro anterior si existe
        if (filtroPersonalizado !== null) {
            $.fn.dataTable.ext.search.pop();
            filtroPersonalizado = null;
        }

        var fechaEntregaDesde = $('#filtroFechaEntregaDesde').val();
        var fechaEntregaHasta = $('#filtroFechaEntregaHasta').val();
        var fechaLimPagoDesde = $('#filtroFechaLimPagoDesde').val();
        var fechaLimPagoHasta = $('#filtroFechaLimPagoHasta').val();
        var fechaTentPagoDesde = $('#filtroFechaTentPagoDesde').val();
        var fechaTentPagoHasta = $('#filtroFechaTentPagoHasta').val();
        var estado = $('#filtroEstado').val();
        var vendedor = $('#filtroVendedor').val();
        var cliente = $('#filtroCliente').val();
        var tipo = $('#filtroTipo').val();

        // Solo agregar filtro si hay algún valor seleccionado
        if (fechaEntregaDesde || fechaEntregaHasta || fechaLimPagoDesde || fechaLimPagoHasta || 
            fechaTentPagoDesde || fechaTentPagoHasta || estado || vendedor || cliente || tipo) {
            
            // Función de filtrado personalizado
            filtroPersonalizado = function(settings, data, dataIndex) {
                // Solo aplicar a nuestra tabla
                if (settings.nTable.id !== 'tableSeguimiento') {
                    return true;
                }
                
                var row = table.row(dataIndex).node();
                
                // Filtro por Estado (columna 1)
                if (estado && estado !== '') {
                    var estadoCell = $(row).find('td:eq(1)').text().trim();
                    if (estadoCell !== estado) {
                        return false;
                    }
                }
                
                // Filtro por Cliente (columna 2)
                if (cliente && cliente !== '') {
                    var clienteCell = $(row).find('td:eq(2)').text().trim();
                    if (clienteCell !== cliente) {
                        return false;
                    }
                }
                
                // Filtro por Tipo (columna 5)
                if (tipo && tipo !== '') {
                    var tipoCell = $(row).find('td:eq(5)').text().trim();
                    // Remover guiones y espacios para comparación
                    tipoCell = tipoCell.replace(/-/g, '').trim();
                    var tipoFiltro = tipo.replace(/-/g, '').trim();
                    if (tipoCell !== tipoFiltro && !tipoCell.includes(tipoFiltro)) {
                        return false;
                    }
                }
                
                // Filtro por Fecha Entrega (columna 9)
                if (fechaEntregaDesde || fechaEntregaHasta) {
                    var fechaEntregaCell = $(row).find('td:eq(9)');
                    var fechaEntrega = extraerFecha(fechaEntregaCell.html());
                    
                    if (fechaEntrega === '') return false;
                    
                    if (fechaEntregaDesde && fechaEntrega < fechaEntregaDesde) {
                        return false;
                    }
                    if (fechaEntregaHasta && fechaEntrega > fechaEntregaHasta) {
                        return false;
                    }
                }
                
                // Filtro por F. Lim. Pago Cliente (columna 22)
                if (fechaLimPagoDesde || fechaLimPagoHasta) {
                    var fechaLimPagoCell = $(row).find('td:eq(22)');
                    var fechaLimPago = extraerFecha(fechaLimPagoCell.html());
                    
                    if (fechaLimPago === '') return false;
                    
                    if (fechaLimPagoDesde && fechaLimPago < fechaLimPagoDesde) {
                        return false;
                    }
                    if (fechaLimPagoHasta && fechaLimPago > fechaLimPagoHasta) {
                        return false;
                    }
                }
                
                // Filtro por F. Tent. Pago Prov (columna 15)
                if (fechaTentPagoDesde || fechaTentPagoHasta) {
                    var fechaTentPagoCell = $(row).find('td:eq(15)');
                    var fechaTentPago = extraerFecha(fechaTentPagoCell.html());
                    
                    if (fechaTentPago === '') return false;
                    
                    if (fechaTentPagoDesde && fechaTentPago < fechaTentPagoDesde) {
                        return false;
                    }
                    if (fechaTentPagoHasta && fechaTentPago > fechaTentPagoHasta) {
                        return false;
                    }
                }
                
                // Filtro por Vendedor (columna 27)
                if (vendedor && vendedor !== '') {
                    var vendedorCell = $(row).find('td:eq(27)').text().trim();
                    // Comparación flexible (case-insensitive y permite coincidencias parciales)
                    if (vendedorCell.toLowerCase() !== vendedor.toLowerCase() && 
                        !vendedorCell.toLowerCase().includes(vendedor.toLowerCase())) {
                        return false;
                    }
                }
                
                return true;
            };
            
            $.fn.dataTable.ext.search.push(filtroPersonalizado);
        }
        
        table.draw();
        recalcularFooter();
        // Actualizar gráficas si el offcanvas está abierto
        if ($('#offcanvasGraficas').hasClass('show')) {
            setTimeout(function() { actualizarGraficas(); }, 100);
        }
    }

    // Función para limpiar filtros
    function limpiarFiltros() {
        $('#filtroFechaEntregaDesde').val('');
        $('#filtroFechaEntregaHasta').val('');
        $('#filtroFechaLimPagoDesde').val('');
        $('#filtroFechaLimPagoHasta').val('');
        $('#filtroFechaTentPagoDesde').val('');
        $('#filtroFechaTentPagoHasta').val('');
        $('#filtroEstado').val('');
        $('#filtroVendedor').val('');
        $('#filtroCliente').val('');
        $('#filtroTipo').val('');
        
        // Remover filtro personalizado
        if (filtroPersonalizado !== null) {
            $.fn.dataTable.ext.search.pop();
            filtroPersonalizado = null;
        }
        
        table.draw();
        recalcularFooter();
        // Actualizar gráficas si el offcanvas está abierto
        if ($('#offcanvasGraficas').hasClass('show')) {
            setTimeout(function() { actualizarGraficas(); }, 100);
        }
    }

    // Event listeners para filtros
    $('#btnAplicarFiltros').on('click', function() {
        aplicarFiltros();
    });
    
    $('#btnLimpiarFiltros').on('click', function() {
        limpiarFiltros();
    });
    
    // Aplicar filtros al cambiar fechas
    $('#filtroFechaEntregaDesde, #filtroFechaEntregaHasta, #filtroFechaLimPagoDesde, #filtroFechaLimPagoHasta, #filtroFechaTentPagoDesde, #filtroFechaTentPagoHasta').on('change', function() {
        aplicarFiltros();
    });
    
    // Ejecutar al cargar la página
    evaluarFechas();
    colorearEstados();
    colorearTipos();
    } );

    // Variables globales para las gráficas
    var chartTopPedidos = null;
    var chartTopVendedores = null;
    var chartTopClientes = null;
    var graficasInicializadas = false;

    // Función para extraer datos de la tabla filtrada
    function obtenerDatosTabla() {
        var datos = {
            pedidos: [],
            vendedores: {},
            clientes: {}
        };

        // Obtener todas las filas visibles (con filtros aplicados)
        table.rows({ search: 'applied' }).every(function() {
            var row = this.node();
            
            // Extraer datos de cada fila
            var idPedido = $(row).find('td:eq(0)').text().trim();
            var cliente = $(row).find('td:eq(2)').text().trim();
            var numeroPedido = $(row).find('td:eq(4)').text().trim();
            var montoPagadoCell = $(row).find('td:eq(25)').text().trim();
            var vendedor = $(row).find('td:eq(27)').text().trim();
            
            // Convertir monto pagado a número
            var montoPagado = parseFloat(montoPagadoCell.replace(/[\$,\s]/g, '')) || 0;
            
            // Agregar pedido (solo si tiene monto pagado)
            if (montoPagado > 0 && cliente && cliente !== '-') {
                datos.pedidos.push({
                    id: idPedido,
                    cliente: cliente,
                    numeroPedido: numeroPedido,
                    vendedor: vendedor,
                    montoPagado: montoPagado
                });
            }
            
            // Contar pedidos por vendedor
            if (vendedor && vendedor !== '-') {
                if (!datos.vendedores[vendedor]) {
                    datos.vendedores[vendedor] = 0;
                }
                datos.vendedores[vendedor]++;
            }
            
            // Contar pedidos por cliente
            if (cliente && cliente !== '-') {
                if (!datos.clientes[cliente]) {
                    datos.clientes[cliente] = 0;
                }
                datos.clientes[cliente]++;
            }
        });
        
        return datos;
    }

    // Función para actualizar gráficas
    function actualizarGraficas() {
        if (!graficasInicializadas || typeof Chart === 'undefined') {
            console.log('Gráficas no inicializadas aún');
            return;
        }

        try {
            var datos = obtenerDatosTabla();
            
            // Ordenar pedidos por monto pagado descendente y tomar top 5
            var topPedidos = datos.pedidos
                .sort(function(a, b) { return b.montoPagado - a.montoPagado; })
                .slice(0, 5);
            
            // Ordenar vendedores por cantidad de pedidos descendente y tomar top 5
            var vendedoresArray = Object.keys(datos.vendedores).map(function(key) {
                return { nombre: key, cantidadPedidos: datos.vendedores[key] };
            }).sort(function(a, b) { return b.cantidadPedidos - a.cantidadPedidos; }).slice(0, 5);
            
            // Ordenar clientes por cantidad de pedidos descendente y tomar top 5
            var clientesArray = Object.keys(datos.clientes).map(function(key) {
                return { nombre: key, cantidadPedidos: datos.clientes[key] };
            }).sort(function(a, b) { return b.cantidadPedidos - a.cantidadPedidos; }).slice(0, 5);
            
            // Actualizar gráfica de pedidos
            if (chartTopPedidos && topPedidos.length > 0) {
                chartTopPedidos.data.labels = topPedidos.map(function(p) {
                    var label = p.cliente || 'Sin cliente';
                    var vendedor = p.vendedor && p.vendedor !== '-' ? p.vendedor : 'Sin vendedor';
                    return label + ' - ' + vendedor;
                });
                chartTopPedidos.data.datasets[0].data = topPedidos.map(function(p) { return p.montoPagado; });
                chartTopPedidos.update('none'); // 'none' para animación más rápida
            }
            
            // Actualizar gráfica de vendedores
            if (chartTopVendedores && vendedoresArray.length > 0) {
                chartTopVendedores.data.labels = vendedoresArray.map(function(v) { return v.nombre; });
                chartTopVendedores.data.datasets[0].data = vendedoresArray.map(function(v) { return v.cantidadPedidos; });
                chartTopVendedores.update('none');
            }
            
            // Actualizar gráfica de clientes
            if (chartTopClientes && clientesArray.length > 0) {
                chartTopClientes.data.labels = clientesArray.map(function(c) { return c.nombre; });
                chartTopClientes.data.datasets[0].data = clientesArray.map(function(c) { return c.cantidadPedidos; });
                chartTopClientes.update('none');
            }
        } catch (error) {
            console.error('Error al actualizar gráficas:', error);
        }
    }

    // Función para inicializar gráficas
    function inicializarGraficas() {
        if (graficasInicializadas) {
            actualizarGraficas();
            return;
        }

        // Verificar si Chart.js está disponible
        if (typeof Chart === 'undefined') {
            console.error('Chart.js no está disponible. Por favor recarga la página.');
            alert('Error: Chart.js no se cargó correctamente. Por favor recarga la página.');
            return;
        }

        // Verificar que los elementos canvas existan
        var ctxPedidos = document.getElementById('chartTopPedidos');
        var ctxVendedores = document.getElementById('chartTopVendedores');
        var ctxClientes = document.getElementById('chartTopClientes');

        if (!ctxPedidos) {
            console.error('No se encontró el elemento chartTopPedidos');
        }
        if (!ctxVendedores) {
            console.error('No se encontró el elemento chartTopVendedores');
        }
        if (!ctxClientes) {
            console.error('No se encontró el elemento chartTopClientes');
        }

        if (!ctxPedidos || !ctxVendedores || !ctxClientes) {
            console.error('Uno o más elementos canvas no existen. Esperando...');
            setTimeout(function() { inicializarGraficas(); }, 500);
            return;
        }

        try {
            crearGraficas();
            graficasInicializadas = true;
            console.log('Gráficas inicializadas correctamente');
        } catch (error) {
            console.error('Error al inicializar gráficas:', error);
        }
    }

    function crearGraficas() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js no está disponible');
            return;
        }

        // Gráfica de Top 5 Pedidos (por monto pagado)
        var ctxPedidos = document.getElementById('chartTopPedidos');
        if (ctxPedidos) {
            try {
                chartTopPedidos = new Chart(ctxPedidos, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Monto Pagado ($)',
                        data: [],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    indexAxis: 'y', // Barras horizontales
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Monto Pagado: $' + context.parsed.x.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString('es-MX');
                                }
                            }
                        },
                        y: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
            } catch (error) {
                console.error('Error al crear gráfica de pedidos:', error);
            }
        }

        // Gráfica de Top 5 Vendedores (por cantidad de pedidos)
        var ctxVendedores = document.getElementById('chartTopVendedores');
        if (ctxVendedores) {
            try {
                chartTopVendedores = new Chart(ctxVendedores, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Cantidad de Pedidos',
                        data: [],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(0, 123, 255, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)',
                            'rgba(23, 162, 184, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(0, 123, 255, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(23, 162, 184, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    var value = context.parsed || 0;
                                    var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                    var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + value + ' pedido(s) (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
            } catch (error) {
                console.error('Error al crear gráfica de vendedores:', error);
            }
        }

        // Gráfica de Top 5 Clientes (por cantidad de pedidos)
        var ctxClientes = document.getElementById('chartTopClientes');
        if (ctxClientes) {
            try {
                chartTopClientes = new Chart(ctxClientes, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Cantidad de Pedidos',
                        data: [],
                        backgroundColor: [
                            'rgba(23, 162, 184, 0.8)',
                            'rgba(0, 123, 255, 0.8)',
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)'
                        ],
                        borderColor: [
                            'rgba(23, 162, 184, 1)',
                            'rgba(0, 123, 255, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Pedidos: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
            } catch (error) {
                console.error('Error al crear gráfica de clientes:', error);
            }
        }

        // Actualizar gráficas inicialmente
        setTimeout(function() {
            actualizarGraficas();
        }, 100);
    }

    // Actualizar gráficas cuando se aplican filtros, cambia de página, o se busca
    if (typeof table !== 'undefined') {
        table.on('draw', function() {
            if ($('#offcanvasGraficas').hasClass('show') && graficasInicializadas) {
                setTimeout(function() { actualizarGraficas(); }, 200);
            }
        });
    }

    // Inicializar y actualizar gráficas cuando se abre el offcanvas
    $('#offcanvasGraficas').on('shown.bs.offcanvas', function() {
        // Esperar un momento para que el offcanvas termine de renderizarse
        setTimeout(function() {
            if (!graficasInicializadas) {
                if (typeof Chart !== 'undefined') {
                    inicializarGraficas();
                } else {
                    console.error('Chart.js no está disponible. Esperando carga...');
                    // Esperar a que Chart.js se cargue
                    var checkChart = setInterval(function() {
                        if (typeof Chart !== 'undefined') {
                            clearInterval(checkChart);
                            inicializarGraficas();
                        }
                    }, 100);
                    // Timeout de seguridad
                    setTimeout(function() { clearInterval(checkChart); }, 5000);
                }
            } else {
                actualizarGraficas();
            }
        }, 300);
    });
</script>

@endsection
