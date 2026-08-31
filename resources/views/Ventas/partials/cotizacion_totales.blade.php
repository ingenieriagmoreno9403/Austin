@php
    $enc = $borrador['encabezado'] ?? [];
    $monedaCodigo = 'MXN';
    if (!empty($enc['moneda_id']) && !empty($monedas)) {
        $monedaSel = collect($monedas)->firstWhere('id', (int) $enc['moneda_id']);
        if ($monedaSel) {
            $monedaCodigo = $monedaSel->abreviacion ?: $monedaSel->codigo;
        }
    }
    $mostrarFlete = !empty($totales['mostrar_concepto_flete']);
    $mostrarIva = array_key_exists('mostrar_concepto_iva', $totales)
        ? !empty($totales['mostrar_concepto_iva'])
        : empty($totales['iva_en_precios']);
    $ivaEnPrecios = !empty($totales['iva_en_precios']);
    $porcentajeIva = (float) ($totales['porcentaje_iva'] ?? $enc['porcentaje_iva'] ?? 16);
    $factorIva = 1 + ($porcentajeIva / 100);
    $subtotalMostrar = $ivaEnPrecios
        ? round(((float) ($totales['subtotal'] ?? 0)) * $factorIva, 2)
        : (float) ($totales['subtotal'] ?? 0);
    $descuentoMostrar = $ivaEnPrecios
        ? round(((float) ($totales['descuento_importe'] ?? 0)) * $factorIva, 2)
        : (float) ($totales['descuento_importe'] ?? 0);
    $etiquetaIva = rtrim(rtrim(number_format($porcentajeIva, 2, '.', ''), '0'), '.');
@endphp

<tr>
    <th class="text-end">{{ $ivaEnPrecios ? 'Subtotal (c/IVA)' : 'Subtotal' }}</th>
    <td class="text-end">${{ number_format($subtotalMostrar, 2) }}</td>
</tr>
<tr>
    <th class="text-end">
        Descuento global
        @if (($totales['descuento_porcentaje'] ?? 0) > 0)
            ({{ number_format($totales['descuento_porcentaje'], 2) }}%)
        @endif
    </th>
    <td class="text-end">-${{ number_format($descuentoMostrar, 2) }}</td>
</tr>
@if ($mostrarFlete)
<tr>
    <th class="text-end">Flete</th>
    <td class="text-end">${{ number_format($totales['importe_flete_visible'] ?? $totales['importe_flete'] ?? 0, 2) }}</td>
</tr>
@endif
@if ($mostrarIva)
<tr>
    <th class="text-end">IVA ({{ $etiquetaIva }}%)</th>
    <td class="text-end">${{ number_format($totales['iva_visible'] ?? $totales['iva'] ?? 0, 2) }}</td>
</tr>
@endif
<tr class="fw-bold text-marino">
    <th class="text-end">Total ({{ $monedaCodigo }})</th>
    <td class="text-end">${{ number_format($totales['total'], 2) }}</td>
</tr>
