@extends('layouts.app')
@section('content')
    <div class="container-fluid format_page">
        <div class="row mb-3">
            <div class="col-lg-8 col-12 start-center">
                <h3 class="mt-1 animate__animated animate__backInLeft">Empaque</h3>
                <span class="p-0 m-0 d-none d-md-block fs-8">Selecciona un pedido y registra visualmente los productos empacados.</span>
            </div>
        </div>

        <div class="bg-body rounded-2 p-4 mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Numero de pedido</th>
                            <th>Numero de cliente</th>
                            <th>Nombre del cliente</th>
                            <th>Estatus pedido</th>
                            <th>Prioridad</th>
                            <th>Ciudad</th>
                            <th>Telefono cliente</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pedidos as $pedido)
                            <tr>
                                <td>{{ $pedido['numero_pedido'] }}</td>
                                <td>{{ $pedido['numero_cliente'] }}</td>
                                <td>{{ $pedido['nombre_cliente'] }}</td>
                                <td><span class="badge bg-primary">Empaque</span></td>
                                <td><span class="badge bg-light text-dark border">{{ $pedido['prioridad'] ?? 'Normal' }}</span></td>
                                <td>{{ $pedido['ciudad'] }}</td>
                                <td>{{ $pedido['telefono_cliente'] }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal"
                                        data-bs-target="#modalEmpaquePedido{{ $pedido['id'] }}">
                                        Detalle / Empacar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No hay pedidos disponibles por el momento.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @foreach ($pedidos as $pedido)
            <div class="modal fade" id="modalEmpaquePedido{{ $pedido['id'] }}" tabindex="-1"
                aria-labelledby="modalEmpaquePedidoLabel{{ $pedido['id'] }}" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalEmpaquePedidoLabel{{ $pedido['id'] }}">
                                Empaque - Pedido {{ $pedido['numero_pedido'] }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-4">
                                <div class="col-lg-7">
                                    <h6 class="mb-3">Detalle de productos del pedido</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped align-middle">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px;">#</th>
                                                    <th>Producto</th>
                                                    <th style="width: 120px;">Solicitado</th>
                                                    <th style="width: 140px;">Empacar ahora</th>
                                                    <th style="width: 90px;">Agregar</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($pedido['detalle_productos'] as $index => $producto)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ $producto['nombre'] }}</td>
                                                        <td>{{ $producto['cantidad'] }}</td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm"
                                                                value="{{ $producto['cantidad'] > 5 ? 5 : $producto['cantidad'] }}"
                                                                min="1">
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-primary">
                                                                +
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted">Sin productos en este pedido.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <h6 class="mb-3">Lista de empaque (simulada)</h6>
                                    <div class="border rounded-3 p-3 bg-light mb-3">
                                        <p class="mb-1"><strong>Pedido:</strong> {{ $pedido['numero_pedido'] }}</p>
                                        <p class="mb-1"><strong>Cliente:</strong> {{ $pedido['nombre_cliente'] }}</p>
                                        <p class="mb-0"><strong>Estado de empaque:</strong> En proceso</p>
                                    </div>

                                    <div class="table-responsive mb-3">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Producto agregado</th>
                                                    <th style="width: 110px;">Cantidad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach (array_slice($pedido['detalle_productos'], 0, 2) as $productoEmpacado)
                                                    <tr>
                                                        <td>{{ $productoEmpacado['nombre'] }}</td>
                                                        <td>{{ $productoEmpacado['cantidad'] > 5 ? 5 : $productoEmpacado['cantidad'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="d-grid">
                                        <button type="button" class="btn btn-success">Terminar</button>
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
