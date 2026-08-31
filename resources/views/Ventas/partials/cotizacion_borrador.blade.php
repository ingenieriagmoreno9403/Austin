@php
    $enc = $borrador['encabezado'] ?? [];
    $lineas = $borrador['lineas'] ?? [];
    $idUbicacion = (int) ($enc['id_ubicacion'] ?? 0);
    $soloLectura = $soloLectura ?? false;
    $tipoVenta = $enc['tipo_venta'] ?? 'EXISTENCIA';
    $esProduccion = $tipoVenta === 'PRODUCCION';
    $estatusOpciones = $soloLectura
        ? [$enc['estatus'] ?? 'BORRADOR']
        : ['BORRADOR', 'ENVIADA', 'ACEPTADA', 'RECHAZADA', 'VENCIDA'];
    if (!$soloLectura && ($enc['estatus'] ?? '') === 'CONVERTIDA') {
        $estatusOpciones[] = 'CONVERTIDA';
    }
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

@if (session('cotizacion_errores'))
    <div class="alert alert-danger border-0 rounded-3 py-2 mb-2">
        <ul class="mb-0 ps-3 fs-8">
            @foreach (session('cotizacion_errores') as $errores)
                @foreach ((array) $errores as $error)
                    <li>{{ $error }}</li>
                @endforeach
            @endforeach
        </ul>
    </div>
@endif

<div id="cotizacion-alerta-ajax" class="alert border-0 rounded-3 py-2 mb-2 fs-8 d-none" role="alert"></div>

@if (!$soloLectura)
    <div class="border rounded-3 p-3 mb-3 {{ $esProduccion ? 'border-warning bg-warning-subtle' : 'bg-white' }}">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold text-marino fs-8"><i class="fa-solid fa-code-branch me-1"></i> Tipo de venta:</span>
            <form method="POST" action="{{ route('ventas.cotizacion.tipo_venta') }}" class="d-flex gap-2 mb-0">
                @csrf
                <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                <div class="btn-group btn-group-sm" role="group">
                    <input type="radio" class="btn-check" name="tipo_venta" id="cv-existencia" value="EXISTENCIA"
                        {{ !$esProduccion ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="btn btn-outline-primary" for="cv-existencia"><i class="fa-solid fa-warehouse me-1"></i> Existencia</label>
                    <input type="radio" class="btn-check" name="tipo_venta" id="cv-produccion" value="PRODUCCION"
                        {{ $esProduccion ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="btn btn-outline-warning" for="cv-produccion"><i class="fa-solid fa-industry me-1"></i> Producción</label>
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
@endif

<div class="border rounded-3 p-3 mb-3 bg-light" id="cotizacion-encabezado"
    data-url-recalcular="{{ route('ventas.cotizacion.recalcular') }}">
    <h6 class="text-marino mb-2"><i class="fa-solid fa-file-lines me-1"></i> Datos de la cotización</h6>

    <div class="row g-2">
        <div class="col-md-5">
            <label class="form-label fs-8">Cliente <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" name="cliente_id" {{ $soloLectura ? 'disabled' : '' }}>
                <option value="">— Seleccione —</option>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}"
                        data-moneda-id="{{ $cliente->moneda_id ?? '' }}"
                        data-condicion-pago-id="{{ $cliente->condicion_pago_id ?? '' }}"
                        data-persona-atencion="{{ $cliente->persona_atencion_nombre ?? $cliente->nombre ?? '' }}"
                        {{ (int) ($enc['cliente_id'] ?? 0) === (int) $cliente->id ? 'selected' : '' }}>
                        {{ $cliente->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fs-8">Persona de atención</label>
            <input type="text" class="form-control form-control-sm" name="persona_atencion" id="cotizacion-persona-atencion"
                maxlength="255" placeholder="Se toma del cliente"
                value="{{ $enc['persona_atencion'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}>
            <div class="form-text fs-9">Se carga del cliente; puede ajustarla en esta cotización.</div>
        </div>
        <div class="col-md-2">
            <label class="form-label fs-8">Vencimiento</label>
            <input type="date" class="form-control form-control-sm" name="fecha_vencimiento"
                value="{{ $enc['fecha_vencimiento'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}>
        </div>
        <div class="col-md-2">
            <label class="form-label fs-8">Descuento global (%)</label>
            <input type="number" class="form-control form-control-sm" name="descuento" min="0" max="100" step="0.01"
                value="{{ $enc['descuento'] ?? 0 }}" {{ $soloLectura ? 'readonly' : '' }}>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Estatus</label>
            <select class="form-select form-select-sm" name="estatus" {{ $soloLectura ? 'disabled' : '' }}>
                @foreach ($estatusOpciones as $est)
                    <option value="{{ $est }}" {{ ($enc['estatus'] ?? 'BORRADOR') === $est ? 'selected' : '' }}>{{ $est }}</option>
                @endforeach
            </select>
            @if (!$soloLectura)
                <div class="form-text fs-9">Si elige <strong>ACEPTADA</strong>, al guardar se crea el pedido automáticamente.</div>
            @endif
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Moneda</label>
            <select class="form-select form-select-sm" name="moneda_id" id="cotizacion-moneda-id" {{ $soloLectura ? 'disabled' : '' }}>
                @foreach (($monedas ?? []) as $moneda)
                    <option value="{{ $moneda->id }}"
                        {{ (int) ($enc['moneda_id'] ?? 0) === (int) $moneda->id ? 'selected' : '' }}>
                        {{ $moneda->abreviacion }} — {{ $moneda->nombre }}
                    </option>
                @endforeach
            </select>
            <div class="form-text fs-9">Se toma del cliente; puede cambiarla en esta cotización.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Condiciones de pago</label>
            <select class="form-select form-select-sm" name="condicion_pago_id" id="cotizacion-condicion-pago-id" {{ $soloLectura ? 'disabled' : '' }}>
                <option value="">— Seleccione —</option>
                @foreach (($condicionesPago ?? []) as $condicionPago)
                    <option value="{{ $condicionPago->id }}"
                        {{ (int) ($enc['condicion_pago_id'] ?? 0) === (int) $condicionPago->id ? 'selected' : '' }}>
                        {{ $condicionPago->nombre }}
                    </option>
                @endforeach
            </select>
            <div class="form-text fs-9">Se toma del cliente; puede cambiarla en esta cotización.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Tipo de flete</label>
            <div class="input-group input-group-sm">
                <select class="form-select form-select-sm js-select-tipo-flete" name="tipo_flete_id" {{ $soloLectura ? 'disabled' : '' }}>
                    <option value="">— Seleccione —</option>
                    @foreach (($tiposFlete ?? []) as $tipoFlete)
                        <option value="{{ $tipoFlete->id }}"
                            {{ (int) ($enc['tipo_flete_id'] ?? 0) === (int) $tipoFlete->id ? 'selected' : '' }}>
                            {{ $tipoFlete->nombre }}
                        </option>
                    @endforeach
                </select>
                @if (!$soloLectura)
                    <button type="button" class="btn btn-blue-light" data-bs-toggle="modal" data-bs-target="#modalNuevoTipoFlete" title="Agregar tipo de flete">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label fs-8">Importe flete</label>
            <input type="number" class="form-control form-control-sm" name="importe_flete" min="0" step="0.01"
                value="{{ $enc['importe_flete'] ?? 0 }}" {{ $soloLectura ? 'readonly' : '' }}
                title="Importe del flete">
            <div class="form-check mt-1">
                <input type="hidden" name="flete_en_precios" value="0">
                <input class="form-check-input" type="checkbox" name="flete_en_precios" value="1"
                    id="cotizacion-flete-en-precios"
                    {{ !empty($enc['flete_en_precios']) ? 'checked' : '' }}
                    {{ $soloLectura ? 'disabled' : '' }}>
                <label class="form-check-label fs-9" for="cotizacion-flete-en-precios">
                    Incluir flete en precios
                </label>
            </div>
            <div class="form-text fs-9 js-flete-ayuda">
                @if (!empty($enc['flete_en_precios']))
                    Se reparte en los productos; no aparece como concepto.
                @else
                    Se muestra como concepto de flete en el formato.
                @endif
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label fs-8">Tiempo de entrega</label>
            <input type="text" class="form-control form-control-sm" name="tiempo_entrega" maxlength="255"
                placeholder="Ej. 12 días viaje consolidado"
                value="{{ $enc['tiempo_entrega'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}>
            <label class="form-label fs-8 mt-2">Tipo de IVA</label>
            <div class="input-group input-group-sm">
                <select class="form-select form-select-sm js-select-tipo-iva" name="tipo_iva_id"
                    id="cotizacion-tipo-iva-id" {{ $soloLectura ? 'disabled' : '' }}>
                    @foreach (($tiposIva ?? []) as $tipoIva)
                        <option value="{{ $tipoIva->id }}"
                            data-porcentaje="{{ $tipoIva->porcentaje }}"
                            {{ (int) ($enc['tipo_iva_id'] ?? 0) === (int) $tipoIva->id ? 'selected' : '' }}>
                            {{ $tipoIva->nombre }}
                        </option>
                    @endforeach
                </select>
                @if (!$soloLectura)
                    <button type="button" class="btn btn-blue-light" data-bs-target="#modalNuevoTipoIva" title="Agregar tipo de IVA">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                @endif
            </div>
            <div class="form-check mt-1">
                <input type="hidden" name="iva_en_precios" value="0">
                <input class="form-check-input" type="checkbox" name="iva_en_precios" value="1"
                    id="cotizacion-iva-en-precios"
                    {{ !empty($enc['iva_en_precios']) ? 'checked' : '' }}
                    {{ $soloLectura ? 'disabled' : '' }}>
                <label class="form-check-label fs-9" for="cotizacion-iva-en-precios">
                    Incluir IVA en los productos
                </label>
            </div>
            <div class="form-text fs-9 js-iva-ayuda">
                @if (!empty($enc['iva_en_precios']))
                    El IVA va en cada precio; no aparece como concepto aparte.
                @else
                    El IVA se muestra como concepto aparte en el formato.
                @endif
            </div>
        </div>
        <div class="col-12">
            <label class="form-label fs-8">Observaciones</label>
            <input type="text" class="form-control form-control-sm" name="observaciones" maxlength="1000"
                value="{{ $enc['observaciones'] ?? '' }}" {{ $soloLectura ? 'readonly' : '' }}>
        </div>
        <div class="col-md-6">
            <label class="form-label fs-8">Archivo del cliente (opcional)</label>
            @if (!$soloLectura)
                <input type="file" class="form-control form-control-sm" name="archivo_cliente" id="cotizacion-archivo-cliente"
                    form="form-guardar-cotizacion"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.rar,.txt">
                <div class="form-text fs-8">Solicitud, lista o documento que envió el cliente para cotizar (máx. 15 MB).</div>
            @endif
            @if (!empty($enc['archivo_cliente_ruta']) && (int) ($enc['cotizacion_id'] ?? 0) > 0)
                <div class="mt-1">
                    <a href="{{ route('ventas.cotizacion.archivo_cliente', ['id' => $enc['cotizacion_id'] ?? 0, 'vendedor_id' => $vendedorId]) }}"
                        class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="fa-solid fa-paperclip me-1"></i>
                        {{ $enc['archivo_cliente_nombre'] ?? 'Ver archivo del cliente' }}
                    </a>
                </div>
            @elseif ($soloLectura)
                <div class="text-muted fs-8">Sin archivo adjunto.</div>
            @endif
        </div>
    </div>
</div>

@if (!$soloLectura && $almacenGeneral)
    <div class="border rounded-3 p-3 mb-3"
        id="cotizacion-busqueda-productos"
        data-tipo-venta="{{ $tipoVenta }}"
        data-url-coincidencias="{{ route('ventas.cotizacion.productos_coincidencias') }}"
        data-url-coincidencias-produccion="{{ route('ventas.produccion.productos_coincidencias') }}"
        data-url-detalle="{{ url('/pedidos/cotizacion/producto') }}"
        data-url-materiales="{{ url('/pedidos/produccion/producto') }}"
        data-url-agregar="{{ route('ventas.cotizacion.agregar_linea') }}"
        data-vendedor-id="{{ $vendedorId }}">
        @if ($esProduccion)
            <h6 class="text-marino mb-2" id="productos-cotizacion">
                <i class="fa-solid fa-industry me-1"></i> Agregar producto a fabricar
            </h6>
            <p class="text-muted fs-8 mb-2">
                Solo se listan productos con <strong>receta activa</strong>. Se verifican los materiales disponibles
                para producir la cantidad solicitada.
            </p>
            <div class="row g-2 align-items-end">
                <div class="col-md-12">
                    <label class="form-label fs-8">Producto a producir (nombre o SKU)</label>
                    <div class="position-relative">
                        <input type="text" class="form-control form-control-sm" id="cotizacion-buscar-producto"
                            maxlength="120" placeholder="Escriba al menos 2 caracteres..." autocomplete="off">
                        <div id="cotizacion-sugerencias" class="list-group position-absolute w-100 cotizacion-sugerencias d-none shadow-sm"></div>
                    </div>
                </div>
            </div>
        @else
            <h6 class="text-marino mb-2" id="productos-cotizacion">
                <i class="fa-solid fa-warehouse me-1"></i> Agregar producto del almacén general
            </h6>
            <p class="text-muted fs-8 mb-2">
                Almacén: <strong>{{ $almacenGeneral->folio_interno }}</strong>.
                Elija ubicación, escriba al menos 2 caracteres y seleccione el producto de la lista.
            </p>
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fs-8">Ubicación <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="cotizacion-id-ubicacion" name="id_ubicacion">
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
                        <input type="text" class="form-control form-control-sm" id="cotizacion-buscar-producto"
                            maxlength="120" placeholder="Ej. HER, refacción, SKU..." autocomplete="off"
                            {{ $idUbicacion <= 0 ? 'disabled' : '' }}>
                        <div id="cotizacion-sugerencias" class="list-group position-absolute w-100 cotizacion-sugerencias d-none shadow-sm"></div>
                    </div>
                </div>
            </div>
        @endif

        <div id="cotizacion-producto-seleccionado" class="cotizacion-producto-seleccionado border rounded-3 p-3 mt-3 d-none"></div>
    </div>
@elseif (!$almacenGeneral && !$soloLectura)
    <div class="alert alert-warning mb-3 fs-8">No se encontró el almacén general activo.</div>
@endif

<h6 class="text-marino mb-2" id="cotizacion-detalle-titulo"><i class="fa-solid fa-list me-1"></i> Detalle de la cotización</h6>
<div class="table-responsive mb-3">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Producto</th>
                @if ($soloLectura)
                    <th style="width:90px;">Cant.</th>
                    <th style="width:110px;">P. unit.</th>
                    <th style="width:90px;">Desc.</th>
                    <th style="width:100px;">Importe</th>
                @else
                    <th style="width:90px;">Cant.</th>
                    <th style="width:110px;">P. unit.</th>
                    <th style="width:90px;">Desc.</th>
                    <th style="width:100px;">Importe</th>
                    <th style="width:70px;"></th>
                @endif
            </tr>
        </thead>
        <tbody id="cotizacion-lineas-tbody">
            @include('Ventas.partials.cotizacion_lineas_tbody', [
                'soloLectura' => $soloLectura,
                'puedeEditarPrecio' => $puedeEditarPrecio ?? false,
            ])
        </tbody>
    </table>
</div>

<div class="row justify-content-end">
    <div class="col-md-5">
        <table class="table table-sm mb-0" id="cotizacion-totales">
            @include('Ventas.partials.cotizacion_totales')
        </table>
    </div>
</div>
