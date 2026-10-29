@php
    $costosPorProveedor = $costos->groupBy('proveedor');
@endphp

@if ($costos->isEmpty())
    <div class="alert alert-warning mb-0">
        No hay costos registrados para esta licitación.
    </div>
@else
    <div class="mb-4">
        <h6 class="mb-3">Resumen por proveedor</h6>
        <div class="row">
            @foreach ($costosPorProveedor as $proveedor => $items)
                <div class="col-md-4 mb-2">
                    <div class="card">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-0">{{ $proveedor }}</h6>
                            <p class="mb-0 text-muted">{{ $items->count() }} productos</p>
                            <h5 class="text-primary mb-0">${{ number_format($items->sum('costo'), 2) }}</h5>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Detalle de productos</h6>
        <a href="{{ route('licitaciones.exportarCostos', $id) }}" class="btn btn-sm btn-dark">Exportar a Excel</a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Producto</th>
                    <th class="text-end">Costo</th>
                    <th>Marca</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($costos as $costo)
                    <tr>
                        <td>{{ $costo->proveedor }}</td>
                        <td>{{ $costo->producto }}</td>
                        <td class="text-end">${{ number_format($costo->costo, 2) }}</td>
                        <td>{{ $costo->marca }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
