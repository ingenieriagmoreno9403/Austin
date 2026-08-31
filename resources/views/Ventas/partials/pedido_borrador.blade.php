@php
    $enc = $borradorPedido['encabezado'] ?? [];
    $idUbicacion = (int) ($enc['id_ubicacion'] ?? 0);
    $tipoVenta = $enc['tipo_venta'] ?? 'EXISTENCIA';
    $esProduccion = $tipoVenta === 'PRODUCCION';
@endphp

@if ($errors->any())
    <div class="alert alert-danger border-0 rounded-3 py-2 mb-2">
        <ul class="mb-0 ps-3 fs-8">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div id="pedido-alerta-ajax" class="alert border-0 rounded-3 py-2 mb-2 fs-8 d-none" role="alert"></div>

<div class="border rounded-3 p-3 mb-3 {{ $esProduccion ? 'border-warning bg-warning-subtle' : 'bg-white' }}">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="fw-semibold text-marino fs-8"><i class="fa-solid fa-code-branch me-1"></i> Tipo de venta:</span>
        <form method="POST" action="{{ route('ventas.pedido.tipo_venta') }}" class="d-flex gap-2 mb-0">
            @csrf
            <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
            <div class="btn-group btn-group-sm" role="group">
                <input type="radio" class="btn-check" name="tipo_venta" id="pv-existencia" value="EXISTENCIA"
                    {{ !$esProduccion ? 'checked' : '' }} onchange="this.form.submit()">
                <label class="btn btn-outline-primary" for="pv-existencia"><i class="fa-solid fa-warehouse me-1"></i> Existencia</label>
                <input type="radio" class="btn-check" name="tipo_venta" id="pv-produccion" value="PRODUCCION"
                    {{ $esProduccion ? 'checked' : '' }} onchange="this.form.submit()">
                <label class="btn btn-outline-warning" for="pv-produccion"><i class="fa-solid fa-industry me-1"></i> Producción</label>
            </div>
        </form>
        <span class="text-muted fs-8">
            @if ($esProduccion)
                Se validan los <strong>materiales de la receta</strong> del producto (no el stock del terminado).
            @else
                Se valida la <strong>existencia</strong> del producto terminado en el almacén general.
            @endif
        </span>
    </div>
</div>

<div class="border rounded-3 p-3 mb-3 bg-light" id="pedido-encabezado"
    data-url-recalcular="{{ route('ventas.pedido.recalcular') }}">
    <h6 class="text-marino mb-2"><i class="fa-solid fa-clipboard-list me-1"></i> Datos del pedido</h6>

    <div class="row g-2">
        <div class="col-md-5">
            <label class="form-label fs-8">Cliente <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" name="cliente_id">
                <option value="">— Seleccione —</option>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}"
                        data-moneda-id="{{ $cliente->moneda_id ?? '' }}"
                        data-condicion-pago-id="{{ $cliente->condicion_pago_id ?? '' }}"
                        data-persona-atencion="{{ $cliente->persona_atencion_nombre ?? $cliente->nombre ?? '' }}"
                        {{ (int) old('cliente_id', $enc['cliente_id'] ?? 0) === (int) $cliente->id ? 'selected' : '' }}>
                        {{ $cliente->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fs-8">Persona de atención</label>
            <input type="text" class="form-control form-control-sm" name="persona_atencion" id="pedido-persona-atencion"
                maxlength="255" placeholder="Se toma del cliente"
                value="{{ old('persona_atencion', $enc['persona_atencion'] ?? '') }}">
            <div class="form-text fs-9">Se carga del cliente; puede ajustarla en este pedido.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label fs-8">Cotización relacionada (opcional)</label>
            <select class="form-select form-select-sm" name="cotizacion_id">
                <option value="">— Sin cotización —</option>
                @foreach ($cotizacionesDisponiblesPedido as $cotizacion)
                    <option value="{{ $cotizacion->id }}"
                        {{ (int) old('cotizacion_id', $enc['cotizacion_id'] ?? 0) === (int) $cotizacion->id ? 'selected' : '' }}>
                        {{ $cotizacion->folio }} — {{ $cotizacion->cliente->nombre ?? '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Descuento global (%)</label>
            <input type="number" class="form-control form-control-sm" name="descuento" min="0" max="100" step="0.01"
                value="{{ old('descuento', $enc['descuento'] ?? 0) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Moneda</label>
            <select class="form-select form-select-sm" name="moneda_id" id="pedido-moneda-id">
                @foreach (($monedas ?? []) as $moneda)
                    <option value="{{ $moneda->id }}"
                        {{ (int) old('moneda_id', $enc['moneda_id'] ?? 0) === (int) $moneda->id ? 'selected' : '' }}>
                        {{ $moneda->abreviacion }} — {{ $moneda->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Condiciones de pago</label>
            <select class="form-select form-select-sm" name="condicion_pago_id" id="pedido-condicion-pago-id">
                <option value="">— Seleccione —</option>
                @foreach (($condicionesPago ?? []) as $condicionPago)
                    <option value="{{ $condicionPago->id }}"
                        {{ (int) old('condicion_pago_id', $enc['condicion_pago_id'] ?? 0) === (int) $condicionPago->id ? 'selected' : '' }}>
                        {{ $condicionPago->nombre }}
                    </option>
                @endforeach
            </select>
            <div class="form-text fs-9">Se toma del cliente; puede cambiarla en este pedido.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Tipo de flete</label>
            <div class="input-group input-group-sm">
                <select class="form-select form-select-sm js-select-tipo-flete" name="tipo_flete_id">
                    <option value="">— Seleccione —</option>
                    @foreach (($tiposFlete ?? []) as $tipoFlete)
                        <option value="{{ $tipoFlete->id }}"
                            {{ (int) old('tipo_flete_id', $enc['tipo_flete_id'] ?? 0) === (int) $tipoFlete->id ? 'selected' : '' }}>
                            {{ $tipoFlete->nombre }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-blue-light" data-bs-toggle="modal" data-bs-target="#modalNuevoTipoFlete" title="Agregar tipo de flete">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label fs-8">Importe flete</label>
            <input type="number" class="form-control form-control-sm" name="importe_flete" min="0" step="0.01"
                value="{{ old('importe_flete', $enc['importe_flete'] ?? 0) }}">
            <div class="form-check mt-1">
                <input type="hidden" name="flete_en_precios" value="0">
                <input class="form-check-input" type="checkbox" name="flete_en_precios" value="1"
                    id="pedido-flete-en-precios"
                    {{ !empty(old('flete_en_precios', $enc['flete_en_precios'] ?? false)) ? 'checked' : '' }}>
                <label class="form-check-label fs-9" for="pedido-flete-en-precios">
                    Incluir flete en precios
                </label>
            </div>
            <div class="form-text fs-9">Si se marca, se reparte en productos.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Tiempo de entrega</label>
            <input type="text" class="form-control form-control-sm" name="tiempo_entrega" maxlength="255"
                placeholder="Ej. 12 días viaje consolidado"
                value="{{ old('tiempo_entrega', $enc['tiempo_entrega'] ?? '') }}">
            <label class="form-label fs-8 mt-2">Tipo de IVA</label>
            <div class="input-group input-group-sm">
                <select class="form-select form-select-sm js-select-tipo-iva" name="tipo_iva_id" id="pedido-tipo-iva-id">
                    @foreach (($tiposIva ?? []) as $tipoIva)
                        <option value="{{ $tipoIva->id }}"
                            data-porcentaje="{{ $tipoIva->porcentaje }}"
                            {{ (int) old('tipo_iva_id', $enc['tipo_iva_id'] ?? 0) === (int) $tipoIva->id ? 'selected' : '' }}>
                            {{ $tipoIva->nombre }}
                        </option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-blue-light" data-bs-target="#modalNuevoTipoIva" title="Agregar tipo de IVA">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            <div class="form-check mt-1">
                <input type="hidden" name="iva_en_precios" value="0">
                <input class="form-check-input" type="checkbox" name="iva_en_precios" value="1"
                    id="pedido-iva-en-precios"
                    {{ !empty(old('iva_en_precios', $enc['iva_en_precios'] ?? false)) ? 'checked' : '' }}>
                <label class="form-check-label fs-9" for="pedido-iva-en-precios">
                    Incluir IVA en los productos
                </label>
            </div>
            <div class="form-text fs-9">Si se marca, el IVA va en cada precio.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Fecha del pedido <span class="text-danger">*</span></label>
            <input type="date" class="form-control form-control-sm" name="fecha_pedido"
                value="{{ old('fecha_pedido', $enc['fecha_pedido'] ?? date('Y-m-d')) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Hora del pedido</label>
            <input type="time" class="form-control form-control-sm" name="hora_pedido"
                value="{{ old('hora_pedido', $enc['hora_pedido'] ?? date('H:i')) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Fecha de entrega</label>
            <input type="date" class="form-control form-control-sm" name="fecha_entrega"
                value="{{ old('fecha_entrega', $enc['fecha_entrega'] ?? '') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Hora de entrega</label>
            <input type="time" class="form-control form-control-sm" name="hora_entrega"
                value="{{ old('hora_entrega', $enc['hora_entrega'] ?? '') }}">
        </div>
        <div class="col-12">
            <label class="form-label fs-8">Observaciones</label>
            <input type="text" class="form-control form-control-sm" name="observaciones" maxlength="1000"
                value="{{ old('observaciones', $enc['observaciones'] ?? '') }}">
        </div>
    </div>
</div>

@if ($almacenGeneral)
    <div class="border rounded-3 p-3 mb-3"
        id="pedido-busqueda-productos"
        data-tipo-venta="{{ $tipoVenta }}"
        data-url-coincidencias="{{ route('ventas.cotizacion.productos_coincidencias') }}"
        data-url-coincidencias-produccion="{{ route('ventas.produccion.productos_coincidencias') }}"
        data-url-detalle="{{ url('/pedidos/cotizacion/producto') }}"
        data-url-materiales="{{ url('/pedidos/produccion/producto') }}"
        data-url-agregar="{{ route('ventas.pedido.agregar_linea') }}"
        data-vendedor-id="{{ $vendedorId }}">
        @if ($esProduccion)
            <h6 class="text-marino mb-2" id="productos-pedido">
                <i class="fa-solid fa-industry me-1"></i> Agregar producto a fabricar
            </h6>
            <p class="text-muted fs-8 mb-2">
                Solo se listan productos con <strong>receta activa</strong>. Se verifican los materiales (materia prima)
                disponibles en inventario para producir la cantidad solicitada.
            </p>
            <div class="row g-2 align-items-end">
                <div class="col-md-12">
                    <label class="form-label fs-8">Producto a producir (nombre o SKU)</label>
                    <div class="position-relative">
                        <input type="text" class="form-control form-control-sm" id="pedido-buscar-producto"
                            maxlength="120" placeholder="Escriba al menos 2 caracteres..." autocomplete="off">
                        <div id="pedido-sugerencias" class="list-group position-absolute w-100 cotizacion-sugerencias d-none shadow-sm"></div>
                    </div>
                </div>
            </div>
        @else
            <h6 class="text-marino mb-2" id="productos-pedido">
                <i class="fa-solid fa-warehouse me-1"></i> Agregar producto del almacén general
            </h6>
            <p class="text-muted fs-8 mb-2">
                Almacén: <strong>{{ $almacenGeneral->folio_interno }}</strong>.
                Se valida existencia en la <strong>ubicación</strong> y en el <strong>almacén general</strong>.
            </p>
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fs-8">Ubicación <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="pedido-id-ubicacion" name="id_ubicacion">
                        <option value="">— Seleccione —</option>
                        @foreach ($ubicaciones as $ubicacion)
                            <option value="{{ $ubicacion->id_ubicacion }}"
                                {{ (int) $idUbicacion === (int) $ubicacion->id_ubicacion ? 'selected' : '' }}>
                                {{ $ubicacion->folio_interno }} — {{ $ubicacion->descripcion }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label fs-8">Producto (nombre, SKU o código)</label>
                    <div class="position-relative">
                        <input type="text" class="form-control form-control-sm" id="pedido-buscar-producto"
                            maxlength="120" placeholder="Ej. HER, refacción, SKU..." autocomplete="off"
                            {{ $idUbicacion <= 0 ? 'disabled' : '' }}>
                        <div id="pedido-sugerencias" class="list-group position-absolute w-100 cotizacion-sugerencias d-none shadow-sm"></div>
                    </div>
                </div>
            </div>
        @endif

        <div id="pedido-producto-seleccionado" class="cotizacion-producto-seleccionado border rounded-3 p-3 mt-3 d-none"></div>
    </div>
@else
    <div class="alert alert-warning mb-3 fs-8">No se encontró el almacén general activo.</div>
@endif

<h6 class="text-marino mb-2" id="pedido-detalle-titulo"><i class="fa-solid fa-list me-1"></i> Detalle del pedido</h6>
<div class="table-responsive mb-3">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Producto</th>
                <th style="width:90px;">Cant.</th>
                <th style="width:110px;">P. unit.</th>
                <th style="width:90px;">Desc.</th>
                <th style="width:100px;">Importe</th>
                <th style="width:70px;"></th>
            </tr>
        </thead>
        <tbody id="pedido-lineas-tbody">
            @include('Ventas.partials.pedido_lineas_tbody', [
                'puedeEditarPrecio' => $puedeEditarPrecio ?? false,
            ])
        </tbody>
    </table>
</div>

<div class="row justify-content-end">
    <div class="col-md-5">
        <table class="table table-sm mb-0" id="pedido-totales">
            @include('Ventas.partials.cotizacion_totales', ['borrador' => $borradorPedido, 'totales' => $totalesPedido])
        </table>
    </div>
</div>
