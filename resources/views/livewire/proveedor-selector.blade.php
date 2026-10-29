<div class="prov-selector">
    <div class="prov-selector__panel">
        <div class="prov-selector__panel-head">
            <h6 class="prov-selector__panel-title">
                <i class="fa-solid fa-building"></i>
                Disponibles
            </h6>
            <span class="prov-selector__count">{{ $proveedoresDisponibles->total() }}</span>
        </div>

        <div class="prov-selector__search">
            <select class="form-select mb-2" wire:model="categoriaId">
                <option value="">Todas las categorías</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                @endforeach
            </select>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" wire:model.debounce.300ms="buscar" placeholder="Buscar proveedor..." class="form-control">
            </div>
        </div>

        <div class="prov-selector__list">
            @forelse($proveedoresDisponibles as $proveedor)
                <div class="prov-selector__item">
                    <p class="prov-selector__item-name" title="{{ $proveedor->nombre }}">
                        {{ $proveedor->nombre }}
                        @if (!empty($proveedor->preferido))
                            <span class="badge bg-primary">Preferido</span>
                        @endif
                        @if (\App\Support\HomologacionProveedor::normalizar($proveedor->homologacion ?? '') === 'condicionado')
                            <span class="badge bg-warning text-dark">Condicionado</span>
                        @endif
                        @if (!empty($nombresCategoria[$proveedor->categoria_id]))
                            <span class="badge bg-light text-dark border">{{ $nombresCategoria[$proveedor->categoria_id] }}</span>
                        @endif
                    </p>
                    @if(!$sololectura)
                        <div class="prov-selector__item-actions">
                            <button type="button" wire:click="agregar({{ $proveedor->id }})" class="btn btn-success btn-sm" title="Agregar">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="prov-selector__empty">
                    <div class="prov-selector__empty-icon" aria-hidden="true">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <p class="prov-selector__empty-title">Sin resultados</p>
                    <p class="prov-selector__empty-text">No hay proveedores disponibles con esa categoría o búsqueda.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="prov-selector__panel prov-selector__panel--selected">
        <div class="prov-selector__panel-head">
            <h6 class="prov-selector__panel-title">
                <i class="fa-solid fa-circle-check"></i>
                Seleccionados
            </h6>
            <span class="prov-selector__count">{{ count($proveedoresSeleccionadosDatos) }}</span>
        </div>

        @if(count($proveedoresSeleccionadosDatos) === 0)
            <div class="prov-selector__empty">
                <div class="prov-selector__empty-icon" aria-hidden="true">
                    <i class="fa-solid fa-hand-pointer"></i>
                </div>
                <p class="prov-selector__empty-title">Selecciona proveedores</p>
                <p class="prov-selector__empty-text">
                    Usa el botón <strong>+</strong> de la izquierda para invitar al menos un proveedor a esta comparativa.
                </p>
            </div>
        @else
            <div class="prov-selector__list">
                @foreach($proveedoresSeleccionadosDatos as $proveedor)
                    @php $precio = $preciosAplicados[$proveedor->id] ?? null; @endphp
                    <div class="prov-selector__item {{ $precio ? 'prov-selector__item--con-precios' : '' }}">
                        <div class="prov-selector__item-body">
                            <p class="prov-selector__item-name" title="{{ $proveedor->nombre }}">
                                <i class="fa-regular fa-circle-check text-success me-1"></i>{{ $proveedor->nombre }}
                                @if (!empty($proveedor->preferido))
                                    <span class="badge bg-primary">Preferido</span>
                                @endif
                                @if (\App\Support\HomologacionProveedor::normalizar($proveedor->homologacion ?? '') === 'condicionado')
                                    <span class="badge bg-warning text-dark">Condicionado</span>
                                @endif
                            </p>
                            @if($precio)
                                <div class="prov-selector__precios">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>Precios aplicados</span>
                                    <span class="prov-selector__precios-meta">
                                        {{ (int) $precio->lineas }} {{ (int) $precio->lineas === 1 ? 'producto' : 'productos' }}
                                        · ${{ number_format((float) $precio->importe, 2) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="prov-selector__item-actions">
                            @if($licitacionId)
                                <a class="btn btn-primary btn-sm"
                                   href="/showlicitacion/{{ $licitacionId }}/{{ $proveedor->id }}"
                                   title="Aplicar precios">
                                    <i class="fa-solid fa-dollar-sign me-1"></i> Aplicar precios
                                </a>
                            @endif
                            @if(!$sololectura)
                                <button type="button" class="btn btn-danger btn-sm"
                                        wire:click="quitar({{ $proveedor->id }})"
                                        title="Eliminar">
                                    <i class="fa-solid fa-trash me-1"></i> Eliminar
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
