@extends('layouts.app')
@section('content')
 @php 
    $partidas = 0;
 @endphp

 @foreach($productos as $producto)
    @php 
        $partidas = $partidas + 1;
    @endphp
 @endforeach

<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-chart-column"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">
                            Resultados de Comparativa
                            <span class="badge bg-orange fs-6">Folio: {{ $licitacion->folio }}</span>
                        </h2>
                        <p class="text-muted mb-0">Análisis y resultados de la comparativa de precios</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header bg-light text-white">
                    <h6 class="text-dark mb-0">Información de la Comparativa</h6>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <p class="fs-8">
                                    <b>Nombre de la Comparativa </b><br>
                                    {{ $licitacion->nombre }}
                                </p>
                            </div>
                            <div class="mb-3">
                                <p class="fs-8">
                                    <b>Descripción</b><br>
                                    {{ $licitacion->descripcion_detalle }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <p class="fs-8">
                                            <b>Fecha de Creación</b><br>
                                            {{ \Carbon\Carbon::parse($licitacion->fecha_creacion)->format('d/m/Y') }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3 fs-8">
                                        <b>Fecha Límite</b><br>
                                        <p class="{{ \Carbon\Carbon::parse($licitacion->fecha_limite)->isPast() ? 'text-danger' : '' }}">
                                            {{ \Carbon\Carbon::parse($licitacion->fecha_limite)->format('d/m/Y') }}
                                            @php
                                                $fechaLimite = \Carbon\Carbon::parse($licitacion->fecha_limite);
                                                $diasRestantes = now()->diffInDays($fechaLimite, false);
                                            @endphp
                                            
                                            @if($diasRestantes < 0)
                                                <span class="badge bg-danger">Vencida ({{ abs($diasRestantes) }} días)</span>
                                            @elseif($diasRestantes <= 3)
                                                <span class="badge bg-warning text-dark">{{ $diasRestantes }} días restantes</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 fs-8">
                                <b class="fw-bold">Estado</b>
                                
                                <p class="fs-8">
                                    @if($licitacion->estado == 'cerrada')
                                        <span class="badge bg-danger">Cerrada</span>
                                    @elseif($licitacion->estado == 'borrador')
                                        <span class="badge bg-success">Activa</span>
                                    @elseif($licitacion->estado == 'adjudicada')
                                        <span class="badge bg-info">Adjudicada</span>
                                    @else
                                        <span class="badge bg-primary">{{ucfirst($licitacion->estado)}}</span>
                                    @endif
                                
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card rounded-0 rounded-top border-bottom-0">
                <div class="card-header bg-light text-white">
                    <div class="row">
                        <h6 class="col-md-6 mb-0 text-dark">Tabla Comparativa de Precios</h6>

                        <div class="col-md-6 text-end">
                            <a href="/Comparativa/Cotizacion/{{$licitacion->id }}" class="btn btn-outline-success fs-8"><i class="fa-solid fa-file-excel"></i> Descargar</a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover ">
                        <thead>
                            <tr class="table-warning">
                                <th class="align-middle">Producto</th>
                                <th class="align-middle text-center">Cantidad</th>
                                <th class="align-middle text-center">Unidad</th>
                                @foreach($proveedores as $proveedor)
                                    <th class="text-center" colspan="4">{{ $proveedor->nombre }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                <th colspan="3"></th>
                                @foreach($proveedores as $proveedor)
                                    <th class="text-center">Precio</th>
                                    <th class="text-center">Envio</th>
                                    <th class="text-center">Cost + Marg</th>
                                    <th class="text-center">Marca</th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                          
                            @foreach($productos as $producto)
                                <tr>
                                    <td>
                                         @if (strlen($producto->nombre) > 30)
                                            <div class="divtooltip">{{ substr($producto->nombre,0, 30) }} ... <i class="text-secondary fa-solid fa-info-circle"></i>
                                                <span class="tooltiptext">{{ $producto->nombre }}</span>
                                            </div>
                                        @else
                                            {{ $producto->nombre }}
                                        @endif
                                    </td>
                                    <td class="text-center">{{ intval($producto->cantidad) }}</td>
                                    <td class="text-center">{{ $producto->unidad }}</td>
                                    
                                    @foreach($proveedores as $proveedor)
                                        @php
                                            $key = $producto->id . '-' . $proveedor->id;
                                            $cotizacion = $cotizaciones[$key] ?? null;
                                            $costo = $cotizacion ? $cotizacion[0]->costo : 0;
                                            $marca = $cotizacion ? $cotizacion[0]->marca : '-';
                                            $envio = $cotizacion ? $cotizacion[0]->envio : 0;
                                            $margen = $cotizacion ? $cotizacion[0]->margen : 0;

                                            // Encontrar el precio más bajo para este producto
                                            $precioMasBajo = PHP_FLOAT_MAX;
                                            foreach($proveedores as $p) {
                                                $k = $producto->id . '-' . $p->id;
                                                if(isset($cotizaciones[$k])) {
                                                    $c = $cotizaciones[$k][0]->costo;
                                                    if($c > 0 && $c < $precioMasBajo) {
                                                        $precioMasBajo = $c;
                                                    }
                                                }
                                            }
                                            
                                            // Clase para el precio
                                            $claseColumna = '';
                                            if($costo > 0) {
                                                if($costo == $precioMasBajo) {
                                                    $claseColumna = 'table-success';
                                                } elseif($costo > $precioMasBajo * 1.10) { // 10% más caro
                                                    $claseColumna = 'table-danger';
                                                } elseif($costo > $precioMasBajo) {
                                                    $claseColumna = 'table-warning';
                                                }
                                            }
                                        @endphp
                                        
                                        <td class="text-end {{ $claseColumna }}">
                                            @if($costo > 0)
                                                ${{ number_format($costo, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>

                                         <td class="text-end {{ $claseColumna }}">
                                            @if($envio > 0)
                                                @php
                                                    $envio = ($envio / $partidas) / $producto->cantidad;
                                                @endphp

                                                ${{ number_format($envio, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>

                                        <td class="text-end {{ $claseColumna }}">
                                            @if($margen > 0)
                                                @php
                                                    $factor = round((100 / (100 - $margen)), 2);
                                                    $margencost = round($costo * $factor, 2);
                                                @endphp

                                                ${{ number_format($margencost, 2) }}
                                            @else
                                                -
                                            @endif
                                        </td>

                                        <td class="text-center">{{ $marca }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-secondary">
                            <tr>
                                <th colspan="3" class="text-end">TOTAL POR PROVEEDOR:</th>
                                @foreach($proveedores as $proveedor)
                                    @php
                                        $total = 0;
                                        foreach($productos as $producto) {
                                            $key = $producto->id . '-' . $proveedor->id;
                                            if(isset($cotizaciones[$key])) {
                                                $total += $cotizaciones[$key][0]->costo * $producto->cantidad;
                                            }
                                        }
                                    @endphp
                                    <th class="text-end">${{ number_format($total, 2) }}</th>
                                    <th></th>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <div class="border bg-body p-3 rounded-0 rounded-bottom">
                <strong>Leyenda:</strong>
                <span class="badge bg-success">Mejor precio</span>
                <span class="badge bg-warning">Precio intermedio</span>
                <span class="badge bg-danger">Precio alto (+10%)</span>
            </div>

            <div class="mt-4 mb-4">
                <a href="{{ route('licitaciones.index') }}" class="btn btn-baseColor-light btn-mode fs-8">
                    <i class="fa-solid fa-house"></i> Inicio
                </a>

                <a href="{{ route('licitaciones.editar', $licitacion->id) }}" class="btn btn-baseColor btn-mode fs-8 me-2">
                    <i class="fa-solid fa-pen"></i> Editar
                </a>
                
                
                @if($licitacion->estado != 'adjudicada')
                    <div class="float-end">
                        <button type="button" class="btn btn-success btn-mode fs-8" data-bs-toggle="modal" data-bs-target="#modalAdjudicarPorProducto">
                            <i class="fa-solid fa-check"></i> Adjudicar por Producto
                        </button>
                    </div>
                @else
                    <div class="float-end">
                        <div class="alert alert-success p-2 mb-0">
                            <i class="fa-solid fa-check-circle"></i> Comparativa adjudicada
                        </div>
                    </div>
                    
                    <!-- Mostrar resumen de adjudicaciones -->
                    @php
                        $adjudicaciones = DB::table('tbllicitacion_adjudicaciones as la')
                            ->join('tblprovedores as p', 'p.id', '=', 'la.proveedor_id')
                            ->join('tblproductos as pr', 'pr.id', '=', 'la.producto_id')
                            ->where('la.licitacion_id', $licitacion->id)
                            ->select('la.*', 'p.nombre as proveedor_nombre', 'pr.nombre as producto_nombre')
                            ->get();
                    @endphp
                    
                    @if($adjudicaciones->isNotEmpty())
                        <div class="mt-3">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Resumen de Adjudicaciones</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Proveedor</th>
                                                    <th>Cantidad</th>
                                                    <th>Precio Unitario</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($adjudicaciones as $adj)
                                                    <tr>
                                                        <td>{{ $adj->producto_nombre }}</td>
                                                        <td>{{ $adj->proveedor_nombre }}</td>
                                                        <td>{{ $adj->cantidad_adjudicada }}</td>
                                                        <td>${{ number_format($adj->precio_unitario, 2) }}</td>
                                                        <td>${{ number_format($adj->subtotal, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-info">
                                                    <th colspan="4">Total Adjudicado:</th>
                                                    <th>${{ number_format($adjudicaciones->sum('subtotal'), 2) }}</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fa-solid fa-info-circle"></i>
                                            Adjudicado el {{ \Carbon\Carbon::parse($adjudicaciones->first()->fecha_adjudicacion)->format('d/m/Y H:i') }}
                                            por {{ $adjudicaciones->first()->usuario_adjudicador }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal para adjudicar por producto -->
@if($licitacion->estado != 'adjudicada')
<div class="modal fade" id="modalAdjudicarPorProducto" tabindex="-1" aria-labelledby="modalAdjudicarPorProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form action="{{ route('licitaciones.adjudicarPorProducto', $licitacion->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAdjudicarPorProductoLabel">Adjudicar por Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa-solid fa-info-circle"></i> 
                        <strong>Instrucciones:</strong> 
                        <ul class="mb-0 mt-2">
                            <li>Seleccione el proveedor y la cantidad para cada producto</li>
                            <li>Use el botón <i class="fa-solid fa-plus text-success"></i> para <strong>dividir un producto</strong> entre varios proveedores</li>
                            <li>Use el botón <i class="fa-solid fa-trash text-danger"></i> para eliminar divisiones adicionales</li>
                            <li>La cantidad total por producto no puede exceder lo solicitado</li>
                        </ul>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad Solicitada</th>
                                    <th>Proveedor</th>
                                    <th>Cantidad a Adjudicar</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal / Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="adjudicacionesTable">
                                                                                @foreach($productos as $producto)
                                                    <tr class="producto-row" data-producto-id="{{ $producto->id }}" data-producto-nombre="{{ $producto->nombre }}" data-cantidad-original="{{ $producto->cantidad }}">
                                                        <td class="producto-nombre">{{ $producto->nombre }}</td>
                                                        <td class="cantidad-solicitada">{{ $producto->cantidad }}</td>
                                                        <td>
                                                            <select class="form-select proveedor-select" name="adjudicaciones[{{ $producto->id }}][0][proveedor_id]" data-producto-id="{{ $producto->id }}" data-fila-index="0">
                                                                <option value="">-- Seleccionar Proveedor --</option>
                                                                @foreach($proveedores as $proveedor)
                                                                    <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control cantidad-input" 
                                                                   name="adjudicaciones[{{ $producto->id }}][0][cantidad]" 
                                                                   min="1" max="{{ $producto->cantidad }}" 
                                                                   value="{{ $producto->cantidad }}"
                                                                   data-producto-id="{{ $producto->id }}" data-fila-index="0">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control precio-input" 
                                                                   name="adjudicaciones[{{ $producto->id }}][0][precio_unitario]" 
                                                                   min="0" step="0.01" readonly
                                                                   data-producto-id="{{ $producto->id }}" data-fila-index="0">
                                                        </td>
                                                        <td class="subtotal-cell">
                                                            <span class="subtotal-display" data-producto-id="{{ $producto->id }}" data-fila-index="0">$0.00</span>
                                                            <button type="button" class="btn btn-sm btn-outline-success ms-2 btn-dividir" data-producto-id="{{ $producto->id }}" title="Dividir entre proveedores">
                                                                <i class="fa-solid fa-plus"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="alert alert-warning alert-sm" style="display: none;" id="alertCantidades">
                                <i class="fa-solid fa-exclamation-triangle"></i>
                                <span id="mensajeAlerta"></span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <h5>Total: <span id="totalAdjudicacion">$0.00</span></h5>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Adjudicar Productos</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.producto-row.table-secondary {
    background-color: #f8f9fa !important;
    border-left: 3px solid #6c757d;
}

.alert-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

.btn-dividir:hover {
    background-color: #198754;
    border-color: #198754;
    color: white;
}

.btn-eliminar-fila:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.table-danger {
    background-color: #f8d7da !important;
    border-color: #f5c2c7 !important;
}

.subtotal-cell {
    min-width: 150px;
}

.producto-nombre small {
    font-size: 0.75rem;
}
</style>

<script>
// Datos de cotizaciones para JavaScript
const cotizacionesData = @json($cotizaciones);
const proveedoresData = @json($proveedores);

// Función para obtener el precio del producto por proveedor
function obtenerPrecioProducto(productoId, proveedorId) {
    const key = productoId + '-' + proveedorId;
    return cotizacionesData[key] ? cotizacionesData[key][0].costo : 0;
}

// Evento cuando cambia el proveedor
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('proveedor-select')) {
        const productoId = e.target.getAttribute('data-producto-id');
        const filaIndex = e.target.getAttribute('data-fila-index');
        const proveedorId = e.target.value;
        const precioInput = document.querySelector(`input[data-producto-id="${productoId}"][data-fila-index="${filaIndex}"].precio-input`);
        
        if (proveedorId) {
            const precio = obtenerPrecioProducto(productoId, proveedorId);
            precioInput.value = precio.toFixed(2);
        } else {
            precioInput.value = '';
        }
        
        calcularSubtotal(productoId, filaIndex);
        validarCantidadesProducto(productoId);
    }
});

// Evento cuando cambia la cantidad
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('cantidad-input')) {
        const productoId = e.target.getAttribute('data-producto-id');
        const filaIndex = e.target.getAttribute('data-fila-index');
        calcularSubtotal(productoId, filaIndex);
        validarCantidadesProducto(productoId);
    }
});

// Evento para botones de dividir producto
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-dividir')) {
        const productoId = e.target.closest('.btn-dividir').getAttribute('data-producto-id');
        agregarFilaDivision(productoId);
    }
    
    if (e.target.closest('.btn-eliminar-fila')) {
        const fila = e.target.closest('tr');
        const productoId = fila.getAttribute('data-producto-id');
        fila.remove();
        validarCantidadesProducto(productoId);
        calcularTotal();
    }
});

// Función para calcular subtotal de una fila específica
function calcularSubtotal(productoId, filaIndex) {
    const cantidadInput = document.querySelector(`input[data-producto-id="${productoId}"][data-fila-index="${filaIndex}"].cantidad-input`);
    const precioInput = document.querySelector(`input[data-producto-id="${productoId}"][data-fila-index="${filaIndex}"].precio-input`);
    const subtotalDisplay = document.querySelector(`span[data-producto-id="${productoId}"][data-fila-index="${filaIndex}"].subtotal-display`);
    
    const cantidad = parseFloat(cantidadInput.value) || 0;
    const precio = parseFloat(precioInput.value) || 0;
    const subtotal = cantidad * precio;
    
    subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
    
    calcularTotal();
}

// Función para calcular el total
function calcularTotal() {
    let total = 0;
    document.querySelectorAll('.subtotal-display').forEach(function(element) {
        const subtotal = parseFloat(element.textContent.replace('$', '')) || 0;
        total += subtotal;
    });
    
    document.getElementById('totalAdjudicacion').textContent = '$' + total.toFixed(2);
}

// Función para agregar una fila de división
function agregarFilaDivision(productoId) {
    const filaOriginal = document.querySelector(`tr[data-producto-id="${productoId}"]`);
    const productoNombre = filaOriginal.getAttribute('data-producto-nombre');
    const cantidadOriginal = filaOriginal.getAttribute('data-cantidad-original');
    
    // Encontrar el índice más alto para este producto
    const filasExistentes = document.querySelectorAll(`tr[data-producto-id="${productoId}"]`);
    let maxIndex = 0;
    filasExistentes.forEach(function(fila) {
        const inputs = fila.querySelectorAll('input, select');
        inputs.forEach(function(input) {
            const filaIndex = parseInt(input.getAttribute('data-fila-index') || 0);
            if (filaIndex > maxIndex) maxIndex = filaIndex;
        });
    });
    
    const nuevoIndex = maxIndex + 1;
    
    // Crear nueva fila
    const nuevaFila = document.createElement('tr');
    nuevaFila.className = 'producto-row table-secondary';
    nuevaFila.setAttribute('data-producto-id', productoId);
    nuevaFila.setAttribute('data-producto-nombre', productoNombre);
    nuevaFila.setAttribute('data-cantidad-original', cantidadOriginal);
    
    // Calcular cantidad restante
    let cantidadAsignada = 0;
    filasExistentes.forEach(function(fila) {
        const cantidadInput = fila.querySelector('.cantidad-input');
        if (cantidadInput) {
            cantidadAsignada += parseFloat(cantidadInput.value) || 0;
        }
    });
    
    const cantidadRestante = Math.max(1, cantidadOriginal - cantidadAsignada);
    
    nuevaFila.innerHTML = `
        <td class="producto-nombre">
            <small class="text-muted">↳ División de:</small><br>
            ${productoNombre}
        </td>
        <td class="cantidad-solicitada">${cantidadOriginal}</td>
        <td>
            <select class="form-select proveedor-select" name="adjudicaciones[${productoId}][${nuevoIndex}][proveedor_id]" data-producto-id="${productoId}" data-fila-index="${nuevoIndex}">
                <option value="">-- Seleccionar Proveedor --</option>
                ${proveedoresData.map(proveedor => `<option value="${proveedor.id}">${proveedor.nombre}</option>`).join('')}
            </select>
        </td>
        <td>
            <input type="number" class="form-control cantidad-input" 
                   name="adjudicaciones[${productoId}][${nuevoIndex}][cantidad]" 
                   min="1" max="${cantidadRestante}" 
                   value="${cantidadRestante}"
                   data-producto-id="${productoId}" data-fila-index="${nuevoIndex}">
        </td>
        <td>
            <input type="number" class="form-control precio-input" 
                   name="adjudicaciones[${productoId}][${nuevoIndex}][precio_unitario]" 
                   min="0" step="0.01" readonly
                   data-producto-id="${productoId}" data-fila-index="${nuevoIndex}">
        </td>
        <td class="subtotal-cell">
            <span class="subtotal-display" data-producto-id="${productoId}" data-fila-index="${nuevoIndex}">$0.00</span>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2 btn-eliminar-fila" title="Eliminar división">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    `;
    
    // Insertar después de la última fila de este producto
    let ultimaFila = filaOriginal;
    filasExistentes.forEach(function(fila) {
        if (fila.compareDocumentPosition(ultimaFila) & Node.DOCUMENT_POSITION_FOLLOWING) {
            ultimaFila = fila;
        }
    });
    
    ultimaFila.parentNode.insertBefore(nuevaFila, ultimaFila.nextSibling);
    
    // Actualizar la cantidad de la fila original si es necesario
    const cantidadInputOriginal = filaOriginal.querySelector('.cantidad-input');
    if (cantidadAsignada >= cantidadOriginal) {
        cantidadInputOriginal.value = Math.max(1, cantidadOriginal - cantidadRestante);
    }
    
    validarCantidadesProducto(productoId);
}

// Función para validar que las cantidades no excedan lo solicitado
function validarCantidadesProducto(productoId) {
    const filasProducto = document.querySelectorAll(`tr[data-producto-id="${productoId}"]`);
    const cantidadOriginal = parseInt(filasProducto[0].getAttribute('data-cantidad-original'));
    
    let cantidadTotal = 0;
    filasProducto.forEach(function(fila) {
        const cantidadInput = fila.querySelector('.cantidad-input');
        if (cantidadInput) {
            cantidadTotal += parseFloat(cantidadInput.value) || 0;
        }
    });
    
    const alertDiv = document.getElementById('alertCantidades');
    const mensajeSpan = document.getElementById('mensajeAlerta');
    
    if (cantidadTotal > cantidadOriginal) {
        alertDiv.style.display = 'block';
        mensajeSpan.textContent = `La cantidad total adjudicada (${cantidadTotal}) excede la solicitada (${cantidadOriginal}) para el producto.`;
        
        // Resaltar filas con error
        filasProducto.forEach(function(fila) {
            fila.classList.add('table-danger');
        });
    } else {
        // Verificar si hay otros productos con problemas
        let hayOtrosErrores = false;
        document.querySelectorAll('.producto-row').forEach(function(fila) {
            const prodId = fila.getAttribute('data-producto-id');
            if (prodId !== productoId) {
                const filasOtroProducto = document.querySelectorAll(`tr[data-producto-id="${prodId}"]`);
                const cantidadOriginalOtro = parseInt(filasOtroProducto[0].getAttribute('data-cantidad-original'));
                
                let cantidadTotalOtro = 0;
                filasOtroProducto.forEach(function(filaOtra) {
                    const cantidadInputOtro = filaOtra.querySelector('.cantidad-input');
                    if (cantidadInputOtro) {
                        cantidadTotalOtro += parseFloat(cantidadInputOtro.value) || 0;
                    }
                });
                
                if (cantidadTotalOtro > cantidadOriginalOtro) {
                    hayOtrosErrores = true;
                }
            }
        });
        
        if (!hayOtrosErrores) {
            alertDiv.style.display = 'none';
        }
        
        // Quitar resaltado de error
        filasProducto.forEach(function(fila) {
            fila.classList.remove('table-danger');
        });
    }
}

// Validación antes de enviar el formulario
document.querySelector('form').addEventListener('submit', function(e) {
    let hayErrores = false;
    
    // Validar todas las cantidades
    const productosUnicos = [...new Set(Array.from(document.querySelectorAll('.producto-row')).map(fila => fila.getAttribute('data-producto-id')))];
    
    productosUnicos.forEach(function(productoId) {
        const filasProducto = document.querySelectorAll(`tr[data-producto-id="${productoId}"]`);
        const cantidadOriginal = parseInt(filasProducto[0].getAttribute('data-cantidad-original'));
        
        let cantidadTotal = 0;
        let tieneProveedorSeleccionado = false;
        
        filasProducto.forEach(function(fila) {
            const cantidadInput = fila.querySelector('.cantidad-input');
            const proveedorSelect = fila.querySelector('.proveedor-select');
            
            if (cantidadInput && proveedorSelect.value) {
                cantidadTotal += parseFloat(cantidadInput.value) || 0;
                tieneProveedorSeleccionado = true;
            }
        });
        
        if (cantidadTotal > cantidadOriginal) {
            hayErrores = true;
            alert(`Error: La cantidad total adjudicada (${cantidadTotal}) excede la solicitada (${cantidadOriginal}) para un producto.`);
        }
        
        if (tieneProveedorSeleccionado && cantidadTotal === 0) {
            hayErrores = true;
            alert('Error: Hay productos con proveedor seleccionado pero sin cantidad.');
        }
    });
    
    if (hayErrores) {
        e.preventDefault();
    }
});
</script>
@endif

@endsection 