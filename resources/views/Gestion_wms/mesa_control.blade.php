@extends('layouts.app')
@section('content')
    <div class="container-fluid format_page">
        <div class="row mb-3">
            <div class="col-lg-8 col-12 start-center">
                <h3 class="mt-1 animate__animated animate__backInLeft">Mesa de control</h3>
                <span class="p-0 m-0 d-none d-md-block fs-8">Listado general de pedidos para asignar acciones operativas.</span>
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
                                <td>
                                    <span class="badge bg-secondary">{{ $pedido['estatus_pedido'] }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $pedido['prioridad'] ?? 'Normal' }}</span>
                                </td>
                                <td>{{ $pedido['ciudad'] }}</td>
                                <td>{{ $pedido['telefono_cliente'] }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#modalAsignarSurtidor{{ $pedido['id'] }}">
                                            Asignar surtidor
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#modalAsignarPrioridad{{ $pedido['id'] }}">
                                            Asignar prioridad
                                        </button>
                                        <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal"
                                            data-bs-target="#modalDetallePedido{{ $pedido['id'] }}">
                                            Detalle de pedido
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger">Cancelar</button>
                                    </div>
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
            <div class="modal fade" id="modalAsignarSurtidor{{ $pedido['id'] }}" tabindex="-1"
                aria-labelledby="modalAsignarSurtidorLabel{{ $pedido['id'] }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalAsignarSurtidorLabel{{ $pedido['id'] }}">
                                Surtidor - Pedido {{ $pedido['numero_pedido'] }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            @if (empty($pedido['surtidor_asignado']))
                                <div class="mb-3">
                                    <label class="form-label">Selecciona un surtidor</label>
                                    <select class="form-select">
                                        <option value="" selected disabled>Seleccionar empleado...</option>
                                        @foreach ($empleadosSurtidor as $empleado)
                                            <option value="{{ $empleado }}">{{ $empleado }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary">
                                        Asignar surtidor al pedido
                                    </button>
                                </div>
                            @else
                                <div class="alert alert-info mb-3">
                                    <strong>Surtidor asignado:</strong> {{ $pedido['surtidor_asignado'] }}
                                </div>
                                <div class="border rounded-3 p-3 bg-light">
                                    <p class="mb-2">
                                        <strong>Avance:</strong>
                                        Producto {{ $pedido['progreso_surtido']['actual'] }} de
                                        {{ $pedido['progreso_surtido']['total'] }}
                                    </p>
                                    <p class="mb-0">
                                        <strong>Producto actual:</strong> {{ $pedido['producto_actual'] }}
                                    </p>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalAsignarPrioridad{{ $pedido['id'] }}" tabindex="-1"
                aria-labelledby="modalAsignarPrioridadLabel{{ $pedido['id'] }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalAsignarPrioridadLabel{{ $pedido['id'] }}">
                                Prioridad - Pedido {{ $pedido['numero_pedido'] }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Selecciona la prioridad</label>
                                <select class="form-select">
                                    <option value="" selected disabled>Seleccionar prioridad...</option>
                                    <option value="Baja">Baja</option>
                                    <option value="Normal">Normal</option>
                                    <option value="Alta">Alta</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-warning">
                                    Asignar prioridad
                                </button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalDetallePedido{{ $pedido['id'] }}" tabindex="-1"
                aria-labelledby="modalDetallePedidoLabel{{ $pedido['id'] }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalDetallePedidoLabel{{ $pedido['id'] }}">
                                Detalle de pedido {{ $pedido['numero_pedido'] }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>Producto</th>
                                            <th style="width: 140px;">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($pedido['detalle_productos'] as $index => $producto)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $producto['nombre'] }}</td>
                                                <td>{{ $producto['cantidad'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Sin productos en este pedido.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
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
