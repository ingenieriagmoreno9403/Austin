@extends('layouts.app')
@section('content')
    <div class="container-fluid format_page">
        <div class="row mb-3">
            <div class="col-lg-8 col-12 start-center">
                <h3 class="mt-1 animate__animated animate__backInLeft">Embarques</h3>
                <span class="p-0 m-0 d-none d-md-block fs-8">Pedidos listos para recoleccion y entrega al cliente con control de traslado.</span>
            </div>
        </div>

        <div class="bg-body rounded-2 p-4 mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Numero de pedido</th>
                            <th>Cliente</th>
                            <th>Ubicacion para recoger</th>
                            <th>Estatus de entrega</th>
                            <th>Chofer</th>
                            <th>Unidad / Placas</th>
                            <th>Ruta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($embarques as $embarque)
                            <tr>
                                <td>{{ $embarque['numero_pedido'] }}</td>
                                <td>
                                    <div>{{ $embarque['nombre_cliente'] }}</div>
                                    <small class="text-muted">{{ $embarque['numero_cliente'] }}</small>
                                </td>
                                <td>{{ $embarque['ubicacion_recoleccion'] }}</td>
                                <td>
                                    <select class="form-select form-select-sm">
                                        <option value="En proceso de embarque"
                                            {{ $embarque['estatus_entrega'] === 'En proceso de embarque' ? 'selected' : '' }}>
                                            En proceso de embarque
                                        </option>
                                        <option value="En camino al cliente"
                                            {{ $embarque['estatus_entrega'] === 'En camino al cliente' ? 'selected' : '' }}>
                                            En camino al cliente
                                        </option>
                                        <option value="Entregado"
                                            {{ $embarque['estatus_entrega'] === 'Entregado' ? 'selected' : '' }}>
                                            Entregado
                                        </option>
                                    </select>
                                </td>
                                <td>{{ $embarque['chofer'] }}</td>
                                <td>{{ $embarque['unidad'] }}<br><small class="text-muted">{{ $embarque['placas'] }}</small></td>
                                <td>{{ $embarque['ruta'] }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal"
                                        data-bs-target="#modalTraslado{{ $embarque['id'] }}">
                                        Info traslado
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No hay pedidos en embarque por el momento.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @foreach ($embarques as $embarque)
            <div class="modal fade" id="modalTraslado{{ $embarque['id'] }}" tabindex="-1"
                aria-labelledby="modalTrasladoLabel{{ $embarque['id'] }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTrasladoLabel{{ $embarque['id'] }}">
                                Informacion de traslado - {{ $embarque['numero_pedido'] }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <p class="mb-1"><strong>Pedido:</strong> {{ $embarque['numero_pedido'] }}</p>
                                        <p class="mb-1"><strong>Cliente:</strong> {{ $embarque['nombre_cliente'] }}</p>
                                        <p class="mb-1"><strong>Ubicacion:</strong> {{ $embarque['ubicacion_recoleccion'] }}</p>
                                        <p class="mb-0"><strong>Estatus:</strong> {{ $embarque['estatus_entrega'] }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100 bg-light">
                                        <p class="mb-1"><strong>Chofer:</strong> {{ $embarque['chofer'] }}</p>
                                        <p class="mb-1"><strong>Unidad:</strong> {{ $embarque['unidad'] }}</p>
                                        <p class="mb-1"><strong>Placas:</strong> {{ $embarque['placas'] }}</p>
                                        <p class="mb-0"><strong>Ruta:</strong> {{ $embarque['ruta'] }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <p class="mb-1"><strong>Salida programada:</strong></p>
                                        <p class="mb-0">{{ $embarque['fecha_salida'] }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <p class="mb-1"><strong>Entrega estimada:</strong></p>
                                        <p class="mb-0">{{ $embarque['fecha_entrega_estimada'] }}</p>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="border rounded-3 p-3">
                                        <p class="mb-1"><strong>Tracking:</strong> {{ $embarque['tracking'] }}</p>
                                        <p class="mb-0"><strong>Observaciones:</strong> {{ $embarque['observaciones'] }}</p>
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
