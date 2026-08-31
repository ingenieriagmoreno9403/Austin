@component('mail::message')
# Nueva Orden de Compra #{{ $ordenCompra->id }}

Estimado proveedor {{ $proveedor->nombre }},

Se ha generado una nueva orden de compra para su empresa. A continuación, encontrará los detalles de la misma:

**Número de Orden:** {{ $ordenCompra->id }}  
**Fecha de emisión:** {{ \Carbon\Carbon::parse($ordenCompra->fecha_creacion)->format('d/m/Y') }}  
**Fecha límite de entrega:** {{ \Carbon\Carbon::parse($ordenCompra->fecha_limite)->format('d/m/Y') }}

## Detalles de los productos solicitados:

@component('mail::table')
| Producto | Cantidad | Precio Unitario | Subtotal |
| -------- | -------- | --------------- | -------- |
@foreach ($detalles as $detalle)
| {{ $detalle->producto->nombre }} | {{ $detalle->cantidad }} {{ $detalle->unidad_medida ?? 'unidades' }} | ${{ number_format($detalle->costo, 2) }} | ${{ number_format($detalle->cantidad * $detalle->costo, 2) }} |
@endforeach
@endcomponent

**Total de la orden:** ${{ number_format($detalles->sum(function($detalle) { return $detalle->cantidad * $detalle->costo; }), 2) }}

@component('mail::button', ['url' => url("/proveedores/ordenes/{$ordenCompra->id}")])
Ver Orden Completa
@endcomponent

Por favor, confirme la recepción de esta orden de compra y su capacidad para cumplir con los plazos establecidos.

Gracias por su atención,<br>
{{ config('app.name') }}
@endcomponent 