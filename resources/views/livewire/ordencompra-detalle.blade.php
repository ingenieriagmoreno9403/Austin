<div>
    <input type="hidden" name="detalle_json" value="{{ json_encode($filas) }}">

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th class="text-truncate">Producto</th>
                <th class="text-truncate">Cantidad</th>
                <th class="text-truncate">U. Med</th>
                <th class="text-truncate">Observaciones</th>
                <th class="text-truncate">Opciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filas as $i => $fila)
                <tr>
                    <td style="position: relative;">
                        <input type="hidden" wire:model="filas.{{ $i }}.producto_id" />

                        <input type="text"
                               class="form-control"
                               placeholder="Buscar producto..."
                               wire:model="filas.{{ $i }}.nombre"
                               autocomplete="off"
                               @if(!empty($fila['producto_id'])) readonly @endif />
                        @if(!$sololectura)
                            @if(!empty($fila['producto_id']))
                                <button type="button" class="btn btn-sm btn-link text-danger p-0"
                                        wire:click="limpiarProducto({{ $i }})"
                                        style="position: absolute; top: 6px; right: 10px;">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            @endif

                            @if (!empty($fila['nombre']) && isset($resultados[$i]) && empty($fila['producto_id']))
                                @if (count($resultados[$i]) > 0)
                                    <ul class="list-group position-absolute z-3 w-100"
                                        style="max-height: 150px; overflow-y: auto;">
                                        @foreach ($resultados[$i] as $producto)
                                            <li class="list-group-item list-group-item-action"
                                                style="cursor: pointer;"
                                                wire:click="seleccionarProducto({{ $i }}, {{ $producto->id }})">
                                                {{ $producto->nombre }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="alert alert-warning d-flex align-items-center gap-2 mt-1 p-2 small"
                                        style="position: absolute; width: 100%;">
                                        <i class="fa-solid fa-triangle-exclamation me-2"></i>
                                        <span>No se encontraron productos con ese nombre.</span>
                                    </div>
                                @endif
                            @endif
                        @endif
                    </td>

                    <td><input type="number" class="form-control" wire:model="filas.{{ $i }}.cantidad"  @if($sololectura) readonly @endif></td>
                    <td><input type="text" class="form-control" wire:model="filas.{{ $i }}.umed" readonly></td>
                    <td><input type="text" class="form-control" wire:model="filas.{{ $i }}.observaciones" @if($sololectura) readonly @endif ></td>
                    @if (!$sololectura)
                        <td><button type="button" class="btn btn-danger btn-sm"
                                wire:click="eliminarFila({{ $i }})">Eliminar</button></td>
                    @endif
                </tr>
            @endforeach 
        </tbody>
    </table>
    @if (!$sololectura)
        <button type="button" class="btn btn-primary btn-sm mt-2" wire:click="agregarFila">Agregar línea</button>
    @endif
</div>
