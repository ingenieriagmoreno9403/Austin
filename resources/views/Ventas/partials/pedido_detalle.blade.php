@php
    $pedidoProduccionCancelada = !empty($pedidoSeleccionado->ordenProduccion)
        && $pedidoSeleccionado->ordenProduccion->estatus === \App\Models\OrdenProduccion::ESTATUS_CANCELADA;
    $pedidoEnFlujoProduccion = in_array($pedidoSeleccionado->estatus, ['EN_PRODUCCION', 'PENDIENTE_OC', 'LISTO_PARA_CARGA', 'CARGA_AVISADA', 'DESPACHADO'], true);
    $tieneOrdenProduccion = !empty($pedidoSeleccionado->ordenProduccion);
    $pedidoSoloLectura = $pedidoEnFlujoProduccion && !$pedidoProduccionCancelada;
    $opTerminada = $tieneOrdenProduccion
        && $pedidoSeleccionado->ordenProduccion->estatus === \App\Models\OrdenProduccion::ESTATUS_TERMINADA;
    $puedeAvisarCarga = $opTerminada
        && empty($pedidoSeleccionado->carga)
        && in_array($pedidoSeleccionado->estatus, ['EN_PRODUCCION', 'LISTO_PARA_CARGA'], true);
    $estatusOpciones = ['CONFIRMADO', 'EN_PRODUCCION'];
    if (in_array($pedidoSeleccionado->estatus, ['PENDIENTE_OC', 'LISTO_PARA_CARGA', 'CARGA_AVISADA', 'DESPACHADO'], true)) {
        $estatusOpciones[] = $pedidoSeleccionado->estatus;
    }
    $fechaPedido = $pedidoSeleccionado->fecha ? $pedidoSeleccionado->fecha->format('Y-m-d') : '';
    $horaPedido = $pedidoSeleccionado->fecha ? $pedidoSeleccionado->fecha->format('H:i') : '';
    $horaEntrega = $pedidoSeleccionado->hora_entrega
        ? substr((string) $pedidoSeleccionado->hora_entrega, 0, 5)
        : '';
@endphp

<div class="border rounded-3 p-3 mb-3 bg-light">
    <div class="row g-2 fs-8">
        <div class="col-md-3">
            <span class="text-muted d-block">Folio pedido</span>
            <strong>{{ $pedidoSeleccionado->folio ?: '—' }}</strong>
        </div>
        <div class="col-md-3">
            <span class="text-muted d-block">Cotización</span>
            <strong>{{ $pedidoSeleccionado->cotizacion->folio ?? '—' }}</strong>
        </div>
        <div class="col-md-3">
            <span class="text-muted d-block">Vendedor</span>
            <strong>{{ $pedidoSeleccionado->usuario->name ?? '—' }}</strong>
        </div>
        <div class="col-md-3">
            <span class="text-muted d-block">Total</span>
            <strong>${{ number_format($pedidoSeleccionado->total, 2) }}</strong>
        </div>
    </div>
</div>

@if ($pedidoSoloLectura)
    <div class="alert alert-secondary border-0 rounded-3 py-2 mb-3 fs-8">
        <i class="fa-solid fa-lock me-1"></i>
        Pedido bloqueado por producción. Solo se puede editar si la orden de producción es cancelada.
    </div>
@endif

<form method="POST" action="{{ route('ventas.pedido.actualizar', $pedidoSeleccionado->id) }}" id="form-actualizar-pedido">
    @csrf
    <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">

    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <label class="form-label fs-8">Cliente</label>
            <select class="form-select form-select-sm" name="cliente_id" {{ $pedidoSoloLectura ? 'disabled' : '' }} required>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}"
                        data-moneda-id="{{ $cliente->moneda_id ?? '' }}"
                        data-condicion-pago-id="{{ $cliente->condicion_pago_id ?? '' }}"
                        data-persona-atencion="{{ $cliente->persona_atencion_nombre ?? $cliente->nombre ?? '' }}"
                        {{ (int) $pedidoSeleccionado->cliente_id === (int) $cliente->id ? 'selected' : '' }}>
                        {{ $cliente->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Persona de atención</label>
            <input type="text" class="form-control form-control-sm" name="persona_atencion" maxlength="255"
                value="{{ $pedidoSeleccionado->persona_atencion ?? '' }}" {{ $pedidoSoloLectura ? 'readonly' : '' }}>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Estatus</label>
            <select class="form-select form-select-sm" name="estatus" {{ $pedidoSoloLectura ? 'disabled' : '' }}>
                @foreach ($estatusOpciones as $est)
                    <option value="{{ $est }}" {{ $pedidoSeleccionado->estatus === $est ? 'selected' : '' }}>{{ $est }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Moneda</label>
            <select class="form-select form-select-sm" name="moneda_id" {{ $pedidoSoloLectura ? 'disabled' : '' }}>
                @foreach (($monedas ?? []) as $moneda)
                    <option value="{{ $moneda->id }}"
                        {{ (int) ($pedidoSeleccionado->moneda_id ?? 0) === (int) $moneda->id ? 'selected' : '' }}>
                        {{ $moneda->abreviacion }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Condiciones de pago</label>
            <select class="form-select form-select-sm" name="condicion_pago_id" {{ $pedidoSoloLectura ? 'disabled' : '' }}>
                <option value="">—</option>
                @foreach (($condicionesPago ?? []) as $condicionPago)
                    <option value="{{ $condicionPago->id }}"
                        {{ (int) ($pedidoSeleccionado->condicion_pago_id ?? 0) === (int) $condicionPago->id ? 'selected' : '' }}>
                        {{ $condicionPago->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Tipo de flete</label>
            <div class="input-group input-group-sm">
                <select class="form-select form-select-sm js-select-tipo-flete" name="tipo_flete_id" {{ $pedidoSoloLectura ? 'disabled' : '' }}>
                    <option value="">—</option>
                    @foreach (($tiposFlete ?? []) as $tipoFlete)
                        <option value="{{ $tipoFlete->id }}"
                            {{ (int) ($pedidoSeleccionado->tipo_flete_id ?? 0) === (int) $tipoFlete->id ? 'selected' : '' }}>
                            {{ $tipoFlete->nombre }}
                        </option>
                    @endforeach
                </select>
                @if (!$pedidoSoloLectura)
                    <button type="button" class="btn btn-blue-light" data-bs-toggle="modal" data-bs-target="#modalNuevoTipoFlete" title="Agregar tipo de flete">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label fs-8">Importe flete</label>
            <input type="number" class="form-control form-control-sm" name="importe_flete" min="0" step="0.01"
                value="{{ $pedidoSeleccionado->importe_flete ?? 0 }}" {{ $pedidoSoloLectura ? 'readonly' : '' }}>
            <div class="form-check mt-1">
                <input type="hidden" name="flete_en_precios" value="0">
                <input class="form-check-input" type="checkbox" name="flete_en_precios" value="1"
                    id="pedido-detalle-flete-en-precios"
                    {{ !empty($pedidoSeleccionado->flete_en_precios) ? 'checked' : '' }}
                    {{ $pedidoSoloLectura ? 'disabled' : '' }}>
                <label class="form-check-label fs-9" for="pedido-detalle-flete-en-precios">
                    Incluir flete en precios
                </label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Tiempo de entrega</label>
            <input type="text" class="form-control form-control-sm" name="tiempo_entrega" maxlength="255"
                value="{{ $pedidoSeleccionado->tiempo_entrega }}" {{ $pedidoSoloLectura ? 'readonly' : '' }}>
            <label class="form-label fs-8 mt-2">Tipo de IVA</label>
            <div class="input-group input-group-sm">
                <select class="form-select form-select-sm js-select-tipo-iva" name="tipo_iva_id"
                    {{ $pedidoSoloLectura ? 'disabled' : '' }}>
                    @foreach (($tiposIva ?? []) as $tipoIva)
                        <option value="{{ $tipoIva->id }}"
                            data-porcentaje="{{ $tipoIva->porcentaje }}"
                            {{ (int) ($pedidoSeleccionado->tipo_iva_id ?? 0) === (int) $tipoIva->id ? 'selected' : '' }}>
                            {{ $tipoIva->nombre }}
                        </option>
                    @endforeach
                </select>
                @if (!$pedidoSoloLectura)
                    <button type="button" class="btn btn-blue-light" data-bs-target="#modalNuevoTipoIva" title="Agregar tipo de IVA">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                @endif
            </div>
            <div class="form-check mt-1">
                <input type="hidden" name="iva_en_precios" value="0">
                <input class="form-check-input" type="checkbox" name="iva_en_precios" value="1"
                    id="pedido-detalle-iva-en-precios"
                    {{ !empty($pedidoSeleccionado->iva_en_precios) ? 'checked' : '' }}
                    {{ $pedidoSoloLectura ? 'disabled' : '' }}>
                <label class="form-check-label fs-9" for="pedido-detalle-iva-en-precios">
                    Incluir IVA en los productos
                </label>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Observaciones</label>
            <input type="text" class="form-control form-control-sm" name="observaciones" maxlength="1000"
                value="{{ $pedidoSeleccionado->observaciones }}" {{ $pedidoSoloLectura ? 'readonly' : '' }}>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <label class="form-label fs-8">Fecha del pedido</label>
            <input type="date" class="form-control form-control-sm" name="fecha_pedido"
                value="{{ $fechaPedido }}" {{ $pedidoSoloLectura ? 'readonly' : '' }} required>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Hora del pedido</label>
            <input type="time" class="form-control form-control-sm" name="hora_pedido"
                value="{{ $horaPedido }}" {{ $pedidoSoloLectura ? 'readonly' : '' }}>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Fecha de entrega</label>
            <input type="date" class="form-control form-control-sm" name="fecha_entrega"
                value="{{ $pedidoSeleccionado->fecha_entrega ? $pedidoSeleccionado->fecha_entrega->format('Y-m-d') : '' }}"
                {{ $pedidoSoloLectura ? 'readonly' : '' }}>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Hora de entrega</label>
            <input type="time" class="form-control form-control-sm" name="hora_entrega"
                value="{{ $horaEntrega }}" {{ $pedidoSoloLectura ? 'readonly' : '' }}>
        </div>
    </div>
</form>

<h6 class="text-marino mb-2"><i class="fa-solid fa-list me-1"></i> Productos del pedido</h6>
<div class="table-responsive mb-2">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-end">Cant.</th>
                <th class="text-end">P. unit.</th>
                <th class="text-end">Desc.</th>
                <th class="text-end">Importe</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pedidoSeleccionado->detalles as $detalle)
                <tr>
                    <td>{{ $detalle->descripcion ?: ($detalle->producto->nombre ?? '—') }}</td>
                    <td class="text-end">
                        {{ number_format($detalle->cantidad, 2) }}
                        @php
                            $um = $detalle->producto?->unidadMedida;
                            $umTexto = $um ? trim((string) ($um->abreviacion ?: $um->nombre ?: '')) : '';
                        @endphp
                        @if ($umTexto !== '')
                            <span class="text-muted fs-8">{{ $umTexto }}</span>
                        @endif
                    </td>
                    <td class="text-end">${{ number_format($detalle->precio_unitario, 2) }}</td>
                    <td class="text-end">${{ number_format($detalle->descuento, 2) }}</td>
                    <td class="text-end"><strong>${{ number_format($detalle->importe, 2) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">Sin partidas en el pedido.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@php
    $faltantes = $faltantesPedido ?? ['producibles' => [], 'comprables' => [], 'total' => 0];
@endphp

@if (!$pedidoSoloLectura && !$tieneOrdenProduccion && ($faltantes['total'] ?? 0) > 0)
    <div class="border rounded-3 p-3 mb-3" style="background:#fff7ed;border-color:#fed7aa !important;">
        <div class="d-flex align-items-center mb-2">
            <i class="fa-solid fa-triangle-exclamation text-warning me-2 fs-5"></i>
            <div>
                <h6 class="mb-0 text-marino">Productos sin existencia suficiente</h6>
                <span class="text-muted fs-8">
                    Los producibles se envían a producción al cambiar el estatus a <strong>EN_PRODUCCION</strong> y guardar.
                    Los de compra pueden mandarse al reporte de no existencias.
                </span>
            </div>
        </div>

        @if (!empty($faltantes['producibles']))
            <div class="mb-3">
                <div class="mb-1">
                    <span class="fw-semibold text-marino fs-8">
                        <i class="fa-solid fa-industry me-1"></i> Producibles ({{ count($faltantes['producibles']) }})
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 bg-white rounded-3">
                        <thead>
                            <tr class="fs-8 text-muted">
                                <th>Producto</th>
                                <th class="text-end">Solicitado</th>
                                <th class="text-end">Disponible</th>
                                <th class="text-end">Faltante</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($faltantes['producibles'] as $fila)
                                <tr class="fs-8">
                                    <td>{{ $fila['nombre'] }}</td>
                                    <td class="text-end">{{ number_format($fila['cantidad'], 2) }}</td>
                                    <td class="text-end">{{ number_format($fila['disponible'], 2) }}</td>
                                    <td class="text-end text-danger fw-semibold">{{ number_format($fila['faltante'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (!empty($faltantes['comprables']))
            <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-semibold text-marino fs-8">
                        <i class="fa-solid fa-cart-plus me-1"></i> De compra ({{ count($faltantes['comprables']) }})
                    </span>
                    <form method="POST" action="{{ route('ventas.pedido.enviar_compras', $pedidoSeleccionado->id) }}"
                        onsubmit="return confirm('¿Registrar reporte de no existencias y generar un borrador de orden de compra?');">
                        @csrf
                        <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                        <button type="submit" class="btn btn-warning btn-sm text-white">
                            <i class="fa-solid fa-file-circle-plus"></i> Reporte a compras
                        </button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 bg-white rounded-3">
                        <thead>
                            <tr class="fs-8 text-muted">
                                <th>Producto</th>
                                <th class="text-end">Solicitado</th>
                                <th class="text-end">Disponible</th>
                                <th class="text-end">Faltante</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($faltantes['comprables'] as $fila)
                                <tr class="fs-8">
                                    <td>{{ $fila['nombre'] }}</td>
                                    <td class="text-end">{{ number_format($fila['cantidad'], 2) }}</td>
                                    <td class="text-end">{{ number_format($fila['disponible'], 2) }}</td>
                                    <td class="text-end text-danger fw-semibold">{{ number_format($fila['faltante'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endif

@if ($tieneOrdenProduccion)
    <div class="alert alert-info border-0 rounded-3 py-2 mb-3 fs-8">
        <i class="fa-solid fa-industry me-1"></i>
        Orden de producción <strong>{{ $pedidoSeleccionado->ordenProduccion->folio }}</strong>
        ({{ $pedidoSeleccionado->ordenProduccion->estatusTexto ?? $pedidoSeleccionado->ordenProduccion->estatus }}).
        @if ($pedidoSeleccionado->estatus === 'PENDIENTE_OC')
            El pedido quedó pendiente de orden de compra por materiales faltantes.
        @endif
        @if ($pedidoSeleccionado->estatus === 'LISTO_PARA_CARGA')
            Producción terminada. Puede avisar la carga a Almacén.
        @endif
        @if ($pedidoSeleccionado->estatus === 'CARGA_AVISADA' && !empty($pedidoSeleccionado->carga))
            Carga avisada: <strong>{{ $pedidoSeleccionado->carga->folio }}</strong>
            ({{ $pedidoSeleccionado->carga->estatusTexto }}).
            @if ($pedidoSeleccionado->carga->ruta_id)
                En ruta · entrega #{{ $pedidoSeleccionado->carga->orden_entrega }}
                · carga camión #{{ $pedidoSeleccionado->carga->orden_carga }}.
            @else
                <a href="{{ route('almacen.cargas') }}" class="ms-1">Asignar a ruta de camión</a>
            @endif
        @endif
    </div>
@endif

@if ($puedeAvisarCarga)
    <div class="border rounded-3 p-3 mb-3 bg-white">
        <div class="d-flex align-items-start gap-2 mb-2">
            <i class="fa-solid fa-truck-ramp-box text-primary mt-1"></i>
            <div>
                <div class="fw-semibold text-marino">Paso 1 · Avisar carga</div>
                <div class="fs-8 text-muted">
                    Ventas notifica a Almacén que habrá una carga de este pedido.
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('ventas.pedido.avisar_carga', $pedidoSeleccionado->id) }}" class="row g-2 align-items-end">
            @csrf
            <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
            <div class="col-md-3">
                <label class="form-label fs-8 mb-0">Fecha programada</label>
                <input type="date" class="form-control form-control-sm" name="fecha_programada"
                    value="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-0">Hora</label>
                <input type="time" class="form-control form-control-sm" name="hora_programada">
            </div>
            <div class="col-md-4">
                <label class="form-label fs-8 mb-0">Observaciones</label>
                <input type="text" class="form-control form-control-sm" name="observaciones" maxlength="1000"
                    placeholder="Ej. Cliente pasa por material">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-blue btn-sm w-100">
                    <i class="fa-solid fa-bell"></i> Avisar carga
                </button>
            </div>
        </form>
    </div>
@endif

<div class="row g-2 mb-3 fs-8">
    <div class="col-md-4">
        <span class="text-muted d-block">Tipo de flete</span>
        <strong>{{ $pedidoSeleccionado->tipoFlete->nombre ?? '—' }}</strong>
    </div>
    <div class="col-md-4">
        <span class="text-muted d-block">Tiempo de entrega</span>
        <strong>{{ $pedidoSeleccionado->tiempo_entrega ?: '—' }}</strong>
    </div>
    <div class="col-md-4">
        <span class="text-muted d-block">Moneda</span>
        <strong>{{ $pedidoSeleccionado->moneda->abreviacion ?? 'MXN' }}</strong>
    </div>
</div>

<div class="row justify-content-end">
    <div class="col-md-5">
        @php
            $pctPed = (float) ($pedidoSeleccionado->porcentaje_iva ?? 16);
            $etiqIvaPed = rtrim(rtrim(number_format($pctPed, 2, '.', ''), '0'), '.');
            $ivaEnPreciosPed = !empty($pedidoSeleccionado->iva_en_precios);
        @endphp
        <table class="table table-sm mb-0">
            <tr>
                <th class="text-end">{{ $ivaEnPreciosPed ? 'Subtotal (c/IVA)' : 'Subtotal' }}</th>
                <td class="text-end">${{ number_format($pedidoSeleccionado->subtotal, 2) }}</td>
            </tr>
            <tr>
                <th class="text-end">Descuento</th>
                <td class="text-end">-${{ number_format($pedidoSeleccionado->descuento, 2) }}</td>
            </tr>
            @if (!$ivaEnPreciosPed)
            <tr>
                <th class="text-end">IVA ({{ $etiqIvaPed }}%)</th>
                <td class="text-end">${{ number_format($pedidoSeleccionado->iva, 2) }}</td>
            </tr>
            @endif
            <tr class="fw-bold text-marino">
                <th class="text-end">Total ({{ $pedidoSeleccionado->moneda->abreviacion ?? 'MXN' }})</th>
                <td class="text-end">${{ number_format($pedidoSeleccionado->total, 2) }}</td>
            </tr>
        </table>
    </div>
</div>
