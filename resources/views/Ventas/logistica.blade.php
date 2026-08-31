@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .logistica-page .ruta-list-item {
        border: 1px solid var(--card-border, rgba(17, 24, 39, 0.09));
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
        margin-bottom: 0.5rem;
        background: #fff;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        text-decoration: none;
        color: inherit;
        display: block;
        cursor: pointer;
        width: 100%;
        text-align: left;
    }
    .logistica-page .ruta-list-item:hover {
        border-color: rgba(17, 24, 39, 0.22);
        box-shadow: 0 2px 8px rgba(17, 24, 39, 0.06);
    }
    .logistica-page .ops-scroll {
        max-height: 260px;
        overflow: auto;
        border: 1px solid var(--card-border, rgba(17, 24, 39, 0.09));
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
        background: #f9fafb;
    }
    .logistica-page .empty-panel {
        text-align: center;
        padding: 2.5rem 1.5rem;
        color: #6b7280;
    }
    .logistica-page .empty-panel i {
        font-size: 2rem;
        margin-bottom: 0.75rem;
        opacity: 0.45;
        display: block;
    }
    .logistica-page .destino-line {
        font-size: 0.78rem;
        color: #4b5563;
    }
    .logistica-page .modal-ruta .table {
        font-size: 0.85rem;
    }
    .logistica-page .modal-ruta .costo-transporte-box {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 0.85rem 1rem;
    }
</style>

<script>document.body.classList.add('logistica-page');</script>

<div class="container-fluid acciones-config-page logistica-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start header flex-wrap gap-2">
                <div class="d-flex align-items-center min-w-0">
                    <div class="header-icon me-3 flex-shrink-0">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="mb-1">
                            <a href="{{ route('ventas.pedidos') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Pedidos
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Logística</h2>
                        <p class="text-muted mb-0">
                            Rutas de envío con pedidos <strong>facturados</strong> y <strong>confirmados</strong>.
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap gap-2">
                    <a href="{{ route('ventas.pedidos.logistica', ['nueva' => 1]) }}" class="btn btn-baseColor">
                        <i class="fa-solid fa-plus"></i> Nueva ruta
                    </a>
                    <a href="{{ route('almacen.cargas') }}" class="btn btn-baseColor-light">
                        <i class="fa-solid fa-warehouse"></i> Cargas almacén
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-xmark me-2"></i>
            <ul class="mb-0 ps-3 d-inline-block">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 text-marino fw-bold">
                            <i class="fa-solid fa-route me-1"></i> Rutas armadas
                        </h6>
                        <span class="badge bg-secondary">{{ $rutas->count() }}</span>
                    </div>

                    @forelse ($rutas as $ruta)
                        <button type="button"
                                class="ruta-list-item"
                                data-bs-toggle="modal"
                                data-bs-target="#modalRuta{{ $ruta->id }}">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <strong>{{ $ruta->folio }}</strong>
                                    <div class="text-muted fs-8">
                                        {{ optional($ruta->fecha)->format('d/m/Y') ?: '—' }}
                                        · {{ $ruta->cargas_count }} pedido(s)
                                    </div>
                                    <div class="text-muted fs-8">
                                        {{ $ruta->unidad ?: ($ruta->camion->placas ?? 'Sin unidad') }}
                                        @if ($ruta->chofer_nombre)
                                            · {{ $ruta->chofer_nombre }}
                                        @endif
                                    </div>
                                </div>
                                <span class="badge bg-dark">{{ $ruta->estatus_texto }}</span>
                            </div>
                        </button>
                    @empty
                        <div class="text-muted fs-8 text-center py-4">
                            Aún no hay rutas armadas. Cree una con pedidos facturados y confirmados.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            @if ($abrirNuevaRuta || request()->boolean('nueva'))
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-marino fw-bold">
                                <i class="fa-solid fa-plus me-1"></i> Nueva ruta de envío
                            </h6>
                            <a href="{{ route('ventas.pedidos.logistica') }}" class="btn btn-sm btn-outline-secondary">Cancelar</a>
                        </div>

                        <p class="text-muted fs-8 mb-3">
                            Solo aparecen pedidos <span class="badge bg-dark">CONFIRMADO</span>
                            con CFDI vigente y sin carga asignada.
                            Una ruta puede llevar varios pedidos a distintos destinos.
                        </p>

                        <form method="POST" action="{{ route('ventas.pedidos.logistica.crear_ruta') }}">
                            @csrf
                            <input type="hidden" name="abrir_nueva_ruta" value="1">

                            <div class="row g-2 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fs-8">Fecha</label>
                                    <input type="date" name="fecha" class="form-control form-control-sm"
                                           value="{{ old('fecha', now()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-8">Camión</label>
                                    <select name="camion_id" class="form-select form-select-sm">
                                        <option value="">— Opcional —</option>
                                        @foreach ($camiones as $camion)
                                            <option value="{{ $camion->id }}" @selected((string) old('camion_id') === (string) $camion->id)>
                                                {{ $camion->etiqueta_con_capacidad ?? $camion->etiqueta ?? $camion->placas }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-8">Chofer</label>
                                    <select name="chofer_id" class="form-select form-select-sm">
                                        <option value="">— Opcional —</option>
                                        @foreach ($choferes as $chofer)
                                            <option value="{{ $chofer->id }}" @selected((string) old('chofer_id') === (string) $chofer->id)>
                                                {{ $chofer->etiqueta ?? $chofer->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fs-8">Observaciones</label>
                                    <input type="text" name="observaciones" class="form-control form-control-sm"
                                           value="{{ old('observaciones') }}" maxlength="1000"
                                           placeholder="Notas de la ruta">
                                </div>
                            </div>

                            <label class="form-label fs-8 mb-1">
                                Pedidos a enviar en esta ruta <span class="text-danger">*</span>
                            </label>
                            <div class="ops-scroll mb-3">
                                @forelse ($pedidosDisponibles as $pedido)
                                    @php
                                        $direccion = $pedido->cliente?->direccionCompleta() ?: '';
                                        $checked = in_array((string) $pedido->id, array_map('strval', (array) old('pedido_ids', [])), true);
                                    @endphp
                                    <div class="form-check fs-8 mb-2">
                                        <input class="form-check-input" type="checkbox" name="pedido_ids[]"
                                               value="{{ $pedido->id }}" id="log_ped_{{ $pedido->id }}" @checked($checked)>
                                        <label class="form-check-label" for="log_ped_{{ $pedido->id }}">
                                            <strong>{{ $pedido->folio ?? ('Ped #' . $pedido->id) }}</strong>
                                            · {{ $pedido->cliente->nombre ?? '—' }}
                                            <span class="badge bg-dark">CONFIRMADO</span>
                                            <span class="badge bg-success">Facturado</span>
                                            <span class="destino-line d-block">
                                                <i class="fa-solid fa-location-dot me-1"></i>
                                                {{ $direccion !== '' ? $direccion : 'Sin dirección en el cliente' }}
                                                · Entrega: {{ optional($pedido->fecha_entrega)->format('d/m/Y') ?: '—' }}
                                                · ${{ number_format((float) $pedido->total, 2) }}
                                            </span>
                                        </label>
                                    </div>
                                @empty
                                    <div class="text-muted fs-8 py-2">
                                        No hay pedidos disponibles. Se requieren: estatus CONFIRMADO, CFDI vigente y sin carga.
                                    </div>
                                @endforelse
                            </div>

                            <button type="submit" class="btn btn-baseColor" @disabled($pedidosDisponibles->isEmpty())>
                                <i class="fa-solid fa-route me-1"></i> Crear ruta
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body empty-panel">
                        <i class="fa-solid fa-truck-fast"></i>
                        <p class="mb-2 fw-semibold">Seleccione una ruta armada</p>
                        <p class="mb-3 fs-8">
                            Haga clic en una ruta de la izquierda para ver el detalle en un modal,
                            capturar el costo de transporte y timbrar carta porte.
                        </p>
                        <p class="mb-3 fs-8 text-muted">
                            Pedidos disponibles para nuevas rutas: <strong>{{ $pedidosDisponibles->count() }}</strong>
                        </p>
                        <a href="{{ route('ventas.pedidos.logistica', ['nueva' => 1]) }}" class="btn btn-baseColor">
                            <i class="fa-solid fa-plus me-1"></i> Nueva ruta
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Modales de detalle por ruta armada --}}
@foreach ($rutas as $ruta)
    <div class="modal fade modal-ruta" id="modalRuta{{ $ruta->id }}" tabindex="-1"
         aria-labelledby="modalRutaLabel{{ $ruta->id }}" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title text-marino fw-bold" id="modalRutaLabel{{ $ruta->id }}">
                            {{ $ruta->folio }}
                        </h5>
                        <div class="text-muted fs-8">
                            {{ optional($ruta->fecha)->format('d/m/Y') }}
                            · {{ $ruta->estatus_texto }}
                            @if ($ruta->unidad) · {{ $ruta->unidad }} @endif
                            @if ($ruta->chofer_nombre) · Chofer: {{ $ruta->chofer_nombre }} @endif
                        </div>
                        @if ($ruta->observaciones)
                            <div class="text-muted fs-8 mt-1">{{ $ruta->observaciones }}</div>
                        @endif
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h6 class="mb-0 text-marino fw-bold">
                            Pedidos a enviar
                            <span class="badge bg-secondary">{{ $ruta->cargas->count() }}</span>
                        </h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('almacen.cargas', ['ruta_id' => $ruta->id]) }}"
                               class="btn btn-sm btn-baseColor-light">
                                <i class="fa-solid fa-warehouse me-1"></i> Operar en almacén
                            </a>
                            <form method="POST"
                                  action="{{ route('ventas.pedidos.logistica.eliminar', $ruta->id) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar la ruta {{ $ruta->folio }} y liberar sus pedidos para reasignarlos?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fa-solid fa-trash me-1"></i> Eliminar ruta
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Pedido</th>
                                    <th>Cliente</th>
                                    <th>Destino</th>
                                    <th class="text-end">Total</th>
                                    <th>Estatus</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($ruta->cargas as $carga)
                                    @php $pedido = $carga->pedido; @endphp
                                    <tr>
                                        <td>{{ $carga->orden_entrega ?? '—' }}</td>
                                        <td>
                                            @if ($pedido)
                                                <a href="{{ route('ventas.pedidos', ['pedido_id' => $pedido->id, 'abrir_pedido' => 1]) }}">
                                                    {{ $pedido->folio ?? ('#' . $pedido->id) }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $pedido->cliente->nombre ?? '—' }}</td>
                                        <td class="destino-line">{{ $carga->destino_texto ?: '—' }}</td>
                                        <td class="text-end">${{ number_format((float) ($pedido->total ?? 0), 2) }}</td>
                                        <td><span class="badge bg-secondary">{{ $carga->estatus_texto }}</span></td>
                                        <td class="text-center">
                                            @if ($carga->estatus !== \App\Models\Carga::ESTATUS_DESPACHADA)
                                                <form method="POST"
                                                      action="{{ route('ventas.pedidos.logistica.quitar_pedido', [$ruta->id, $carga->id]) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('¿Liberar este pedido de la ruta para reasignarlo?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Liberar pedido">
                                                        <i class="fa-solid fa-unlock"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Esta ruta aún no tiene pedidos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($pedidosDisponibles->isNotEmpty())
                        <div class="mb-3">
                            <h6 class="mb-2 text-marino fw-bold fs-8">
                                <i class="fa-solid fa-plus me-1"></i> Agregar pedidos a esta ruta
                            </h6>
                            <form method="POST" action="{{ route('ventas.pedidos.logistica.agregar', $ruta->id) }}">
                                @csrf
                                <div class="ops-scroll mb-2">
                                    @foreach ($pedidosDisponibles as $pedido)
                                        @php $direccion = $pedido->cliente?->direccionCompleta() ?: ''; @endphp
                                        <div class="form-check fs-8 mb-2">
                                            <input class="form-check-input" type="checkbox" name="pedido_ids[]"
                                                   value="{{ $pedido->id }}" id="add_ped_{{ $ruta->id }}_{{ $pedido->id }}">
                                            <label class="form-check-label" for="add_ped_{{ $ruta->id }}_{{ $pedido->id }}">
                                                <strong>{{ $pedido->folio }}</strong>
                                                · {{ $pedido->cliente->nombre ?? '—' }}
                                                <span class="destino-line d-block">
                                                    <i class="fa-solid fa-location-dot me-1"></i>
                                                    {{ $direccion !== '' ? $direccion : 'Sin dirección' }}
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-baseColor btn-sm">
                                    <i class="fa-solid fa-plus me-1"></i> Agregar a la ruta
                                </button>
                            </form>
                        </div>
                    @endif

                    <div class="costo-transporte-box mb-3">
                        <label class="form-label fw-semibold mb-1" for="costo_transporte_{{ $ruta->id }}">
                            Costo del transporte
                        </label>
                        <p class="text-muted fs-8 mb-2">
                            Monto único (total del CFDI con IVA) que aplica a todos los pedidos de esta ruta.
                        </p>
                        <div class="input-group" style="max-width: 280px;">
                            <span class="input-group-text">$</span>
                            <input type="number"
                                   class="form-control"
                                   id="costo_transporte_{{ $ruta->id }}"
                                   name="costo_transporte"
                                   min="0.01"
                                   step="0.01"
                                   placeholder="0.00"
                                   inputmode="decimal">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    @php
                        $primerPedidoId = (int) ($ruta->cargas->first()->pedido_id ?? 0);
                    @endphp
                    <button type="button"
                            class="btn btn-baseColor js-timbrar-carta-porte"
                            data-pedido-id="{{ $primerPedidoId }}"
                            data-ruta-id="{{ $ruta->id }}"
                            data-costo-input="#costo_transporte_{{ $ruta->id }}"
                            @disabled($primerPedidoId <= 0)>
                        <i class="fa-solid fa-file-invoice me-1"></i> Timbrar carta porte
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    @if ($rutaSeleccionada)
        var modalEl = document.getElementById('modalRuta{{ $rutaSeleccionada->id }}');
        if (modalEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    @endif

    document.querySelectorAll('.js-timbrar-carta-porte').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var pedidoId = parseInt(btn.getAttribute('data-pedido-id') || '0', 10);
            var rutaId = parseInt(btn.getAttribute('data-ruta-id') || '0', 10);
            var input = document.querySelector(btn.getAttribute('data-costo-input') || '');
            var costo = input ? parseFloat(String(input.value || '').replace(/,/g, '')) : 0;

            if (!pedidoId || !rutaId) {
                alert('La ruta no tiene pedidos para timbrar.');
                return;
            }
            if (!costo || costo <= 0) {
                alert('Capture el costo del transporte antes de timbrar carta porte.');
                if (input) input.focus();
                return;
            }

            var url = @json(url('/facturacion')) + '/' + pedidoId + '/pedido/carta-porte'
                + '?ruta_id=' + encodeURIComponent(rutaId)
                + '&costo_transporte=' + encodeURIComponent(costo.toFixed(2));
            window.location.href = url;
        });
    });
});
</script>
@endsection
