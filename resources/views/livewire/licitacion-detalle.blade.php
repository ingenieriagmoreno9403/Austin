<div class="bg-body shadow-sm rounded-3 p-4 oc-productos-panel">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="mb-0"><b class="fs-3 text-orange">2. </b> Productos</h4>
        <div class="producto-status">
            <span class="badge bg-{{ $hayProductosValidos ? 'success' : 'danger' }} productos-contador">
                <i class="fa-solid fa-{{ $hayProductosValidos ? 'check' : 'triangle-exclamation' }} me-1"></i>
                {{ count(array_filter($filas, function($fila) { return !empty($fila['producto_id']); })) }}
                producto(s)
            </span>
        </div>
    </div>

    @if(count($filasInvalidas) > 0)
        <div class="alert alert-danger mb-3">
            <i class="fa-solid fa-exclamation-triangle me-2"></i>
            <strong>Atención:</strong> Hay productos que no se han seleccionado correctamente. Por favor, seleccione los productos de la lista desplegable.
        </div>
    @endif

    <div class="oc-productos-table-wrap">
        <table class="table oc-productos-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="oc-col-producto">Producto</th>
                        <th class="oc-col-cantidad">Cantidad</th>
                        <th class="oc-col-umed">U. Med</th>
                        <th class="oc-col-obs">Observaciones</th>
                        @if(!$eslicitacion)
                            <th class="oc-col-costo">Costo U.</th>
                            <th class="oc-col-subtotal">SubTotal</th>
                        @endif
                        <th class="oc-col-opciones text-center">Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $i => $fila)
                        <tr class="oc-producto-row {{ in_array($i, $filasInvalidas) ? 'is-invalid-row' : (empty($fila['producto_id']) && !$sololectura ? 'is-pending-row' : '') }}">
                            <td class="oc-col-producto" style="position: relative;">
                                <input type="hidden" wire:model="filas.{{ $i }}.producto_id" />

                                <div class="oc-producto-search {{ !empty($fila['producto_id']) ? 'is-selected' : '' }}">
                                    <span class="oc-producto-search__icon">
                                        <i class="fa-solid fa-{{ !empty($fila['producto_id']) ? 'box' : 'magnifying-glass' }}"></i>
                                    </span>
                                    <input type="text"
                                           class="form-control oc-producto-search__input {{ in_array($i, $filasInvalidas) ? 'border-danger is-invalid' : (empty($fila['producto_id']) && !$sololectura ? 'border-warning' : '') }}"
                                           placeholder="Escribe para buscar producto..."
                                           wire:model.debounce.300ms="filas.{{ $i }}.nombre"
                                           autocomplete="off" required
                                           @if(!empty($fila['producto_id'])) readonly @endif />
                                    @if(!$sololectura && !empty($fila['producto_id']))
                                        <button type="button"
                                                class="btn oc-producto-search__clear"
                                                wire:click="limpiarProducto({{ $i }})"
                                                title="Cambiar producto">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    @endif
                                </div>

                                @if(in_array($i, $filasInvalidas))
                                    <div class="invalid-feedback d-block mt-1">
                                        Seleccione un producto válido de la lista.
                                    </div>
                                @endif

                                @if(!$sololectura)
                                    @if (!empty($fila['nombre']) && isset($resultados[$i]) && empty($fila['producto_id']))
                                        @if (count($resultados[$i]) > 0)
                                            <ul class="oc-producto-dropdown list-unstyled mb-0">
                                                @foreach ($resultados[$i] as $producto)
                                                    <li class="oc-producto-dropdown__item"
                                                        wire:click="seleccionarProducto({{ $i }}, {{ $producto->id }})">
                                                        <div class="oc-producto-dropdown__name">{{ $producto->nombre }}</div>
                                                        <span class="oc-producto-dropdown__meta">
                                                            <i class="fa-solid fa-ruler-horizontal me-1"></i>
                                                            {{ $producto->unidad->nombre ?? 'Sin unidad' }}
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="oc-producto-dropdown oc-producto-dropdown--empty">
                                                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                                No se encontraron productos con ese nombre.
                                            </div>
                                        @endif
                                    @endif

                                    @if (isset($buscando[$i]) && $buscando[$i])
                                        <div class="oc-producto-searching">
                                            <div class="spinner-border spinner-border-sm text-secondary" role="status">
                                                <span class="visually-hidden">Buscando...</span>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <td class="oc-col-cantidad">
                                <input type="number"
                                       class="form-control oc-input"
                                       wire:model="filas.{{ $i }}.cantidad"
                                       min="1"
                                       @if($sololectura) readonly @endif>
                            </td>
                            <td class="oc-col-umed">
                                <input type="text"
                                       class="form-control oc-input oc-input--muted"
                                       wire:model="filas.{{ $i }}.umed"
                                       readonly>
                            </td>
                            <td class="oc-col-obs">
                                <input type="text"
                                       class="form-control oc-input"
                                       wire:model="filas.{{ $i }}.observaciones"
                                       placeholder="Opcional"
                                       @if($sololectura) readonly @endif>
                            </td>
                            @if(!$eslicitacion)
                                <td class="oc-col-costo">
                                    <input type="number"
                                           class="form-control oc-input"
                                           wire:model="filas.{{ $i }}.costo"
                                           step="0.01"
                                           min="0"
                                           @if($sololectura) readonly @endif>
                                </td>
                                <td class="oc-col-subtotal">
                                    <div class="oc-subtotal">
                                        {{ $this->formatearSubtotal($fila['subtotal']) }}
                                    </div>
                                </td>
                            @endif
                            @if (!$sololectura)
                                <td class="oc-col-opciones text-center">
                                    <button type="button"
                                            class="btn oc-btn-eliminar"
                                            wire:click="eliminarFila({{ $i }})"
                                            title="Eliminar línea">
                                        <i class="fa-solid fa-trash-alt"></i>
                                    </button>
                                </td>
                            @else
                                <td class="oc-col-opciones"></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                @if(!$eslicitacion)
                    <tfoot>
                        <tr class="oc-total-row">
                            <td colspan="4" class="text-end border-0"></td>
                            <td class="text-end fw-semibold border-0">Total</td>
                            <td class="border-0">
                                <div class="oc-total-value">
                                    {{ number_format($totalGeneral, 2) }}
                                </div>
                            </td>
                            <td class="border-0"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
    </div>

    @if (!$sololectura)
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
            <button type="button" class="btn btn-success btn-mode fs-9" wire:click="agregarFila">
                <i class="fa-solid fa-plus me-1"></i> Agregar línea
            </button>

            @if(!$hayProductosValidos)
                <div class="alert alert-warning py-1 px-3 mb-0 d-inline-flex align-items-center">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>
                    <small>Debe agregar al menos un producto</small>
                </div>
            @endif
        </div>
    @endif
</div>

<style>
    .oc-productos-panel {
        --oc-border: rgba(17, 24, 39, 0.1);
        --oc-surface: #f8fafc;
        --oc-accent: #111827;
        --oc-muted: #6b7280;
        --oc-radius: 12px;
    }

    .oc-productos-table-wrap {
        border: 1px solid var(--oc-border);
        border-radius: var(--oc-radius);
        overflow-x: auto;
        overflow-y: visible;
        background: #fff;
    }

    .oc-productos-table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 960px;
        width: 100%;
    }

    /* Evita que el dropdown de búsqueda quede recortado */
    .oc-productos-table tbody {
        position: relative;
    }

    .oc-productos-table tbody tr {
        position: relative;
    }

    .oc-col-producto {
        overflow: visible;
    }

    .oc-productos-table thead th {
        background: var(--oc-surface);
        color: #374151;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        border-bottom: 1px solid var(--oc-border);
        padding: 0.85rem 1rem;
        white-space: nowrap;
    }

    .oc-productos-table tbody td {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid rgba(17, 24, 39, 0.06);
        vertical-align: middle;
        background: #fff;
    }

    .oc-producto-row:last-child td {
        border-bottom: none;
    }

    .oc-producto-row:hover td {
        background: #fafbfc;
    }

    .oc-producto-row.is-pending-row td {
        background: #fffbeb;
    }

    .oc-producto-row.is-invalid-row td {
        background: #fef2f2;
    }

    .oc-col-producto {
        min-width: 320px;
        width: 38%;
    }

    .oc-col-cantidad,
    .oc-col-costo {
        min-width: 110px;
        width: 110px;
    }

    .oc-col-umed {
        min-width: 100px;
        width: 100px;
    }

    .oc-col-obs {
        min-width: 160px;
    }

    .oc-col-subtotal {
        min-width: 130px;
        width: 130px;
    }

    .oc-col-opciones {
        min-width: 70px;
        width: 70px;
    }

    .oc-producto-search {
        position: relative;
        display: flex;
        align-items: stretch;
        border: 1.5px solid var(--oc-border);
        border-radius: 10px;
        background: #fff;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        min-height: 48px;
    }

    .oc-producto-search:focus-within {
        border-color: var(--oc-accent);
        box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.1);
    }

    .oc-producto-search.is-selected {
        border-color: #86efac;
        background: #f0fdf4;
    }

    .oc-producto-search__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        color: var(--oc-muted);
        flex-shrink: 0;
        border-right: 1px solid rgba(17, 24, 39, 0.06);
    }

    .oc-producto-search.is-selected .oc-producto-search__icon {
        color: #16a34a;
    }

    .oc-producto-search .form-control.oc-producto-search__input,
    .form .oc-producto-search .form-control.oc-producto-search__input,
    .oc-producto-search .oc-producto-search__input.border-warning,
    .oc-producto-search .oc-producto-search__input.border-danger,
    .oc-producto-search .oc-producto-search__input.is-invalid {
        border: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        font-size: 1rem !important;
        padding: 0.7rem 0.85rem !important;
        min-height: 46px !important;
        height: auto !important;
        outline: none !important;
    }

    .oc-producto-search .form-control.oc-producto-search__input:focus,
    .form .oc-producto-search .form-control.oc-producto-search__input:focus {
        border: 0 !important;
        box-shadow: none !important;
        outline: none !important;
    }

    /* Estados en el contenedor, no en el input (evita doble borde) */
    .oc-producto-search:has(.border-warning) {
        border-color: #f59e0b;
        background: #fffbeb;
    }

    .oc-producto-search:has(.border-danger),
    .oc-producto-search:has(.is-invalid) {
        border-color: #ef4444;
        background: #fef2f2;
    }

    .oc-producto-search__input[readonly] {
        cursor: default;
        font-weight: 600;
        color: #111827;
    }

    .oc-producto-search__clear {
        border: none;
        background: transparent;
        color: var(--oc-muted);
        padding: 0 0.85rem;
        line-height: 1;
    }

    .oc-producto-search__clear:hover {
        color: #dc2626;
    }

    .oc-producto-dropdown {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        z-index: 30;
        max-height: 280px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid var(--oc-border);
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(17, 24, 39, 0.14);
        padding: 0.35rem;
    }

    .oc-producto-dropdown__item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.75rem 0.9rem;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.12s ease;
    }

    .oc-producto-dropdown__item:hover {
        background: #f3f4f6;
    }

    .oc-producto-dropdown__name {
        font-size: 0.95rem;
        font-weight: 500;
        color: #111827;
        line-height: 1.3;
    }

    .oc-producto-dropdown__meta {
        flex-shrink: 0;
        font-size: 0.75rem;
        color: var(--oc-muted);
        background: #f3f4f6;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
        white-space: nowrap;
    }

    .oc-producto-dropdown--empty {
        padding: 0.85rem 1rem;
        color: #92400e;
        background: #fffbeb;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
    }

    .oc-producto-searching {
        position: absolute;
        right: 3.25rem;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .oc-input {
        border: 1.5px solid var(--oc-border);
        border-radius: 10px;
        min-height: 44px;
        padding: 0.55rem 0.75rem;
        font-size: 0.95rem;
        background: #fff;
    }

    .oc-input:focus {
        border-color: var(--oc-accent);
        box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.1);
    }

    .oc-input--muted {
        background: #f8fafc;
        color: #4b5563;
    }

    .oc-subtotal {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding: 0.55rem 0.85rem;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid rgba(17, 24, 39, 0.06);
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: #111827;
    }

    .oc-btn-eliminar {
        width: 40px;
        height: 40px;
        padding: 0;
        border-radius: 10px;
        border: 1px solid rgba(220, 38, 38, 0.2);
        background: #fef2f2;
        color: #dc2626;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s ease, transform 0.15s ease;
    }

    .oc-btn-eliminar:hover {
        background: #fee2e2;
        color: #b91c1c;
        transform: translateY(-1px);
    }

    .oc-total-row td {
        background: #f8fafc !important;
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }

    .oc-total-value {
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding: 0.55rem 0.85rem;
        border-radius: 10px;
        background: #111827;
        color: #fff;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        font-size: 1.05rem;
    }

    @media (max-width: 768px) {
        .oc-col-producto {
            min-width: 260px;
        }

        .oc-producto-search__input {
            font-size: 0.95rem;
        }
    }
</style>

<script>
    // Escuchar el evento de validación de productos y actualizar la UI
    window.addEventListener('productos-validados', event => {
        const alertaProductosVacios = document.getElementById('alerta-productos-vacios');
        if (alertaProductosVacios) {
            alertaProductosVacios.style.display = event.detail.validos ? 'none' : 'block';
        }
    });

    // Escuchar el evento de error
    window.addEventListener('error-evento', event => {
        Swal.fire({
            icon: 'error',
            title: '¡Error!',
            text: event.detail.mensaje,
            confirmButtonColor: '#3085d6',
        });
    });

    // Escuchar el evento que establece el detalle_json
    window.addEventListener('set-detalle-json', event => {
        document.getElementById('detalle_json').value = event.detail.detalle;
    });

    // Función para formatear números como moneda
    function formatearMoneda(valor) {
        return new Intl.NumberFormat('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(valor);
    }

    // Actualizar subtotales en tiempo real
    document.addEventListener('DOMContentLoaded', function() {
        const tabla = document.querySelector('.oc-productos-table');
        if (tabla) {
            tabla.addEventListener('input', function(e) {
                if (e.target.matches('input[wire\\:model*="cantidad"], input[wire\\:model*="costo"]')) {
                    // Forzar la actualización de Livewire después de un pequeño delay
                    setTimeout(() => {
                        // Usar emitTo para evitar problemas con Livewire.find
                        if (typeof Livewire !== 'undefined') {
                            Livewire.emitTo('licitacion-detalle', 'recalcularTodosLosSubtotales');
                        }
                    }, 100);
                }
            });
        }
    });
</script>
