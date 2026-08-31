@php
    $enc = $borrador['encabezado'] ?? [];
    $lineas = $borrador['lineas'] ?? [];
    $idUbicacion = (int) ($enc['id_ubicacion'] ?? 0);
    $buscar = $enc['buscar'] ?? '';
    $soloLectura = $soloLectura ?? false;
    $puedeEditarPrecio = $puedeEditarPrecio ?? false;
@endphp

@forelse ($lineas as $indice => $linea)
    @php
        $esLineaProduccion = !empty($linea['es_produccion']);
        $existencia = (float) ($linea['existencia_disponible'] ?? 0);
        $cantidadLinea = (float) ($linea['cantidad'] ?? 0);
        $sinPrecio = round((float) ($linea['precio_unitario'] ?? 0), 2) <= 0;
        $materialesSuficientes = $linea['materiales_suficientes'] ?? true;
        $excedeExistencia = !$esLineaProduccion && ($cantidadLinea > $existencia || !empty($linea['sin_existencia']));
        $filaRoja = $sinPrecio || $excedeExistencia || ($esLineaProduccion && !$materialesSuficientes);
    @endphp
    <tr class="cotizacion-linea {{ $filaRoja ? 'table-danger' : ($esLineaProduccion ? 'table-warning' : '') }}"
        data-existencia="{{ $existencia }}"
        data-precio-unitario="{{ $linea['precio_unitario'] ?? 0 }}"
        data-produccion="{{ $esLineaProduccion ? '1' : '0' }}">
        <td>
            <strong>{{ $linea['sku'] ?: '—' }}</strong> — {{ $linea['nombre'] }}
            @if ($soloLectura)
                @if (!empty($linea['descripcion']))
                    <div class="fs-8 text-muted mt-1">{{ $linea['descripcion'] }}</div>
                @endif
            @else
                <div class="mt-1">
                    <input type="text"
                        form="form-actualizar-linea-cotizacion-{{ $indice }}"
                        name="descripcion"
                        class="form-control form-control-sm"
                        maxlength="1000"
                        value="{{ $linea['descripcion'] ?? '' }}"
                        placeholder="Descripción por producto">
                </div>
            @endif
            @if ($esLineaProduccion)
                <span class="badge bg-warning text-dark ms-1"><i class="fa-solid fa-industry me-1"></i>Producción</span>
                <div class="fs-8 mt-1 {{ $materialesSuficientes ? 'text-muted' : 'text-danger fw-semibold' }}">
                    <i class="fa-solid fa-flask me-1"></i>
                    @if (empty($linea['tiene_receta']))
                        Fabricable por especificación (tubo / flange / conexión). Sin receta de materiales.
                        <a class="btn btn-outline-primary btn-sm py-0 px-2 ms-1"
                           href="{{ route('produccion.recetas', ['producto_id' => $linea['producto_id'], 'nueva' => 1]) }}"
                           target="_blank" rel="noopener"
                           title="Abrir alta de receta para este producto">
                            <i class="fa-solid fa-flask me-1"></i>Añadir receta
                        </a>
                    @elseif ($materialesSuficientes)
                        Materiales de receta disponibles para producir.
                    @else
                        @php
                            $faltantesMat = collect($linea['materiales'] ?? [])->filter(fn ($m) => empty($m['suficiente']));
                        @endphp
                        Faltan materiales:
                        {{ $faltantesMat->map(fn ($m) => ($m['sku'] ?: $m['nombre']) . ' (falta ' . number_format((float) $m['faltante'], 2) . ')')->implode(', ') }}
                    @endif
                </div>
            @else
                <div class="fs-8 mt-1 {{ $excedeExistencia ? 'text-danger fw-semibold' : 'text-muted' }}">
                    <i class="fa-solid fa-boxes-stacked me-1"></i>
                    Existencia actual: <strong>{{ number_format($existencia, 2) }}</strong>
                    @if (!empty($linea['unidad']))
                        <span>{{ $linea['unidad'] }}</span>
                    @endif
                    @if (!empty($linea['ubicacion']))
                        <span>— {{ $linea['ubicacion'] }}</span>
                    @elseif ((int) ($linea['id_ubicacion'] ?? 0) <= 0)
                        <span>— Almacén general (total)</span>
                    @endif
                    @if ($excedeExistencia)
                        <span class="d-block">No hay tantos en existencia (disponible: {{ number_format($existencia, 2) }}@if (!empty($linea['unidad'])) {{ $linea['unidad'] }}@endif).</span>
                    @endif
                </div>
            @endif
            @if ($sinPrecio)
                <div class="fs-8 mt-1 text-danger fw-semibold">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                    Sin precio unitario. Registre el costo de venta del producto en el catálogo.
                </div>
            @endif
        </td>
        @if ($soloLectura)
            <td>
                {{ number_format($linea['cantidad'], 2) }}
                @if (!empty($linea['unidad']))
                    <span class="text-muted fs-8">{{ $linea['unidad'] }}</span>
                @endif
            </td>
            <td>${{ number_format($linea['precio_unitario'], 2) }}</td>
            <td>${{ number_format($linea['descuento'], 2) }}</td>
            <td><strong>${{ number_format($linea['importe'], 2) }}</strong></td>
        @else
            <td colspan="4">
                <form method="POST" action="{{ route('ventas.cotizacion.actualizar_linea') }}"
                    id="form-actualizar-linea-cotizacion-{{ $indice }}"
                    class="row g-1 align-items-center js-form-actualizar-linea-cotizacion">
                    @csrf
                    <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                    <input type="hidden" name="indice" value="{{ $indice }}">
                    <input type="hidden" name="id_ubicacion" value="{{ $linea['id_ubicacion'] ?? $idUbicacion }}">
                    <input type="hidden" name="buscar" value="{{ $buscar }}">
                    <input type="hidden" name="cliente_id" value="{{ $enc['cliente_id'] ?? '' }}">
                    <input type="hidden" name="fecha_vencimiento" value="{{ $enc['fecha_vencimiento'] ?? '' }}">
                    <input type="hidden" name="observaciones" value="{{ $enc['observaciones'] ?? '' }}">
                    <input type="hidden" name="descuento_global" value="{{ $enc['descuento'] ?? 0 }}">
                    <div class="col-3">
                        <div class="input-group input-group-sm">
                            <input type="number" name="cantidad"
                                class="form-control form-control-sm js-cotizacion-cantidad {{ $excedeExistencia ? 'is-invalid' : '' }}"
                                value="{{ $linea['cantidad'] }}" min="0.01" step="0.01" required
                                title="Disponible: {{ number_format($existencia, 2) }}">
                            @if (!empty($linea['unidad']))
                                <span class="input-group-text fs-8">{{ $linea['unidad'] }}</span>
                            @endif
                        </div>
                        <div class="cotizacion-existencia-alerta invalid-feedback d-block fs-8 {{ $excedeExistencia ? '' : 'd-none' }}">
                            No hay tantos en existencia (disponible: {{ number_format($existencia, 2) }}).
                        </div>
                    </div>
                    <div class="col-3">
                        @if ($puedeEditarPrecio)
                            <input type="number" name="precio_unitario"
                                class="form-control form-control-sm text-end js-cotizacion-precio {{ $sinPrecio ? 'is-invalid' : '' }}"
                                value="{{ number_format((float) $linea['precio_unitario'], 2, '.', '') }}"
                                min="0" step="0.01" required
                                title="Permiso especial: puede modificar el precio">
                        @else
                            <input type="hidden" name="precio_unitario" value="{{ $linea['precio_unitario'] }}">
                            <div class="form-control form-control-sm bg-light text-end {{ $sinPrecio ? 'is-invalid' : '' }}">
                                ${{ number_format($linea['precio_unitario'], 2) }}
                            </div>
                        @endif
                    </div>
                    <div class="col-3">
                        <input type="number" name="descuento" class="form-control form-control-sm"
                            value="{{ $linea['descuento'] }}" min="0" step="0.01">
                    </div>
                    <div class="col-3 d-flex align-items-center gap-1">
                        @php
                            $importeMostrar = !empty($linea['mostrar_precio_efectivo'])
                                ? (float) ($linea['importe_visible'] ?? $linea['importe'])
                                : (float) $linea['importe'];
                        @endphp
                        <strong title="{{ !empty($linea['mostrar_precio_efectivo']) ? 'Importe con flete/IVA según encabezado' : 'Importe de línea' }}">
                            ${{ number_format($importeMostrar, 2) }}
                        </strong>
                        <button type="submit" class="btn btn-blue-light btn-sm"
                            title="Actualizar línea y totales (IVA / flete)">
                            <i class="fa-solid fa-rotate"></i>
                        </button>
                    </div>
                </form>
            </td>
            <td class="text-end">
                <form method="POST" action="{{ route('ventas.cotizacion.quitar_linea') }}">
                    @csrf
                    <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                    <input type="hidden" name="indice" value="{{ $indice }}">
                    <button type="submit" class="btn btn-danger btn-sm" title="Quitar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
            </td>
        @endif
    </tr>
@empty
    <tr>
        <td colspan="{{ $soloLectura ? 5 : 6 }}" class="text-center text-muted py-3">
            @if ($soloLectura)
                Esta cotización no tiene partidas.
            @elseif (($enc['tipo_venta'] ?? 'EXISTENCIA') === 'PRODUCCION')
                Agregue productos a fabricar (con receta o especificación de tubo/flange/conexión).
            @else
                Agregue productos desde el inventario del almacén general.
            @endif
        </td>
    </tr>
@endforelse
