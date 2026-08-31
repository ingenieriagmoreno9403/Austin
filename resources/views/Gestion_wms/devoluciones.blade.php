@extends('layouts.app')
@section('content')
    <div class="container-fluid format_page">
        <div class="row mb-3">
            <div class="col-lg-8 col-12 start-center">
                <h3 class="mt-1 animate__animated animate__backInLeft">Devoluciones</h3>
                <span class="p-0 m-0 d-none d-md-block fs-8">Gestion de devoluciones parciales y completas con cancelacion de factura.</span>
            </div>
        </div>

        <div class="bg-body rounded-2 p-4 mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Numero de pedido</th>
                            <th>Cliente</th>
                            <th>Tipo de devolucion</th>
                            <th>Motivo</th>
                            <th>Estatus devolucion</th>
                            <th>Factura</th>
                            <th>Cancelacion factura</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($devoluciones as $devolucion)
                            <tr>
                                <td>{{ $devolucion['numero_pedido'] }}</td>
                                <td>
                                    <div>{{ $devolucion['nombre_cliente'] }}</div>
                                    <small class="text-muted">{{ $devolucion['numero_cliente'] }}</small>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm">
                                        <option value="Parcial"
                                            {{ $devolucion['tipo_devolucion'] === 'Parcial' ? 'selected' : '' }}>
                                            Parcial
                                        </option>
                                        <option value="Completa"
                                            {{ $devolucion['tipo_devolucion'] === 'Completa' ? 'selected' : '' }}>
                                            Completa
                                        </option>
                                    </select>
                                </td>
                                <td>{{ $devolucion['motivo'] }}</td>
                                <td><span class="badge bg-secondary">{{ $devolucion['estatus_devolucion'] }}</span></td>
                                <td>{{ $devolucion['folio_factura'] }}</td>
                                <td>
                                    <select class="form-select form-select-sm">
                                        <option value="Pendiente"
                                            {{ $devolucion['cancelacion_factura'] === 'Pendiente' ? 'selected' : '' }}>
                                            Pendiente
                                        </option>
                                        <option value="En proceso"
                                            {{ $devolucion['cancelacion_factura'] === 'En proceso' ? 'selected' : '' }}>
                                            En proceso
                                        </option>
                                        <option value="Cancelada"
                                            {{ $devolucion['cancelacion_factura'] === 'Cancelada' ? 'selected' : '' }}>
                                            Cancelada
                                        </option>
                                        <option value="No requerida"
                                            {{ $devolucion['cancelacion_factura'] === 'No requerida' ? 'selected' : '' }}>
                                            No requerida
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal"
                                        data-bs-target="#modalDevolucion{{ $devolucion['id'] }}">
                                        Ver detalle
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No hay devoluciones por el momento.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @foreach ($devoluciones as $devolucion)
            <div class="modal fade" id="modalDevolucion{{ $devolucion['id'] }}" tabindex="-1"
                aria-labelledby="modalDevolucionLabel{{ $devolucion['id'] }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDevolucionLabel{{ $devolucion['id'] }}">
                                Detalle de devolucion - {{ $devolucion['numero_pedido'] }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 bg-light h-100">
                                        <p class="mb-1"><strong>Tipo:</strong> {{ $devolucion['tipo_devolucion'] }}</p>
                                        <p class="mb-1"><strong>Motivo:</strong> {{ $devolucion['motivo'] }}</p>
                                        <p class="mb-0"><strong>Estatus:</strong> {{ $devolucion['estatus_devolucion'] }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 bg-light h-100">
                                        <p class="mb-1"><strong>Folio factura:</strong> {{ $devolucion['folio_factura'] }}</p>
                                        <p class="mb-1"><strong>Cancelacion:</strong> {{ $devolucion['cancelacion_factura'] }}</p>
                                        <p class="mb-0"><strong>Monto referencia:</strong> {{ $devolucion['monto_referencia'] }}</p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <h6 class="mb-2">Productos devueltos</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th style="width: 120px;">Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($devolucion['productos_devueltos'] as $producto)
                                                    <tr>
                                                        <td>{{ $producto['nombre'] }}</td>
                                                        <td>{{ $producto['cantidad'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <h6 class="mb-2">Cancelacion de factura (diseno)</h6>
                                    <div class="border rounded-3 p-3">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label mb-1">Motivo SAT</label>
                                                <select class="form-select form-select-sm">
                                                    <option>01 - Comprobantes emitidos con errores con relacion</option>
                                                    <option>02 - Comprobantes emitidos con errores sin relacion</option>
                                                    <option>03 - No se llevo a cabo la operacion</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label mb-1">Folio sustitucion</label>
                                                <input type="text" class="form-control form-control-sm" placeholder="UUID o folio">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <button type="button" class="btn btn-danger w-100">
                                                    Cancelar factura
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
