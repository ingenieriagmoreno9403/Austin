<div>
    <div class="socio-search-box">
        <div class="socio-search-input-wrap">
            <i class="fa-solid fa-magnifying-glass socio-search-icon"></i>
            <input type="text"
                wire:model.debounce.350ms="busqueda"
                class="form-control text socio-search-input"
                placeholder="Ej. 1001, Juan Perez...">
            <span class="spinner-border spinner-border-sm text-primary socio-search-loader"
                role="status"
                aria-hidden="true"
                wire:loading
                wire:target="busqueda,seleccionarSocio"></span>
        </div>

        @if(trim((string) $busqueda) !== '' && !$resultados->isEmpty())
            <div class="socio-search-results">
                @foreach($resultados as $socio)
                    <button type="button"
                        wire:key="checkout-socio-{{ $socio['id'] }}"
                        wire:click="seleccionarSocio({{ $socio['id'] }})"
                        wire:loading.attr="disabled"
                        wire:target="seleccionarSocio({{ $socio['id'] }})"
                        class="socio-result-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div class="text-start">
                                <div class="fw-bold text-marino">{{ $socio['nombre_completo'] ?: '-' }}</div>
                                <div class="text-muted fs-8">
                                    Socio: {{ $socio['numero_socio'] ?: '-' }}
                                    @if($socio['numero_dependiente'])
                                        | Dependiente: {{ $socio['numero_dependiente'] }}
                                    @endif
                                    | Status: {{ $socio['status'] ?: '-' }}
                                </div>
                                <small class="text-muted">{{ $socio['motivo_pago'] }}</small>
                            </div>
                            <div class="text-end">
                                @if($socio['pago_al_corriente'])
                                    <span class="badge bg-success">Al corriente</span>
                                @else
                                    <span class="badge bg-danger">Atrasado</span>
                                @endif
                                <div class="text-muted fs-8 mt-1">
                                    <i class="fa-solid fa-arrow-pointer me-1"></i>Seleccionar
                                </div>
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if(trim((string) $busqueda) === '')
        <div class="socio-search-help fs-8 mt-2">
            <i class="fa-solid fa-bolt me-1"></i>Escribe para buscar y selecciona el socio para validar el acceso.
        </div>
    @elseif($resultados->isEmpty())
        <div class="socio-search-empty">
            <i class="fa-solid fa-circle-info me-1"></i>Sin resultados para esta busqueda.
        </div>
    @endif
</div>
