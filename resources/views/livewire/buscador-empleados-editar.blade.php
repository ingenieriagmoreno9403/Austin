<div class="form-outline col-md-12 col-12">
    <!-- Debug info -->

    <div class="position-relative buscador-container">
        <input 
            type="text" 
            wire:model.debounce.300ms="busquedaEmpleado"
            wire:keyup="buscarEmpleados"
            class="form-control shadow-sm" 
            placeholder="Buscar empleado por nombre, apellido o puesto..."
            required
            autocomplete="off"
        >
        
        <!-- Campo oculto para el formulario -->
        <input type="hidden" name="vendedor" id="vendedor" value="{{ $empleadoId }}" required>
        
        <!-- Botón para limpiar -->
        @if($empleadoId)
            <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" 
                    style="right: 5px; top: 50%; transform: translateY(-50%);"
                    wire:click="limpiarBusqueda">
                <i class="fa-solid fa-times"></i>
            </button>
        @endif
        
        <!-- Resultados de búsqueda -->
        @if($mostrarResultados && count($empleadosFiltrados) > 0)
            <div class="position-absolute w-100 bg-white border rounded shadow-sm resultados-busqueda" 
                 style="top: 100%; left: 0; z-index: 1000; max-height: 200px; overflow-y: auto;">
                @foreach($empleadosFiltrados as $empleado)
                    @php
                        $nombreCompleto = trim(
                            $empleado->primer_nombre . ' ' . 
                            ($empleado->segundo_nombre ?: '') . ' ' . 
                            $empleado->apellido_paterno . ' ' . 
                            ($empleado->apellido_materno ?: '')
                        );
                    @endphp
                    <div class="p-2 border-bottom cursor-pointer hover-bg-light"
                         wire:click="seleccionarEmpleado({{ $empleado->id }}, '{{ $nombreCompleto }}')"
                         style="cursor: pointer;">
                        <div class="fw-bold">{{ $nombreCompleto }}</div>
                        <small class="text-muted">ID: {{ $empleado->id }}</small>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    
    @if($empleadoId)
        <div class="valid-feedback">¡Se ve bien!</div>
    @else
        <div class="invalid-feedback">Por favor, busca y selecciona un empleado.</div>
    @endif
</div>

<!-- Estilos CSS para el buscador -->
<style>
    .hover-bg-light:hover {
        background-color: #f8f9fa !important;
    }
    
    .cursor-pointer {
        cursor: pointer;
    }
    
    /* Ocultar resultados cuando se hace clic fuera */
    .buscador-container:focus-within .resultados-busqueda {
        display: block;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function actualizarCampoVendedor() {
        const campoVendedor = document.querySelector('input[name="vendedor"]');
        if (campoVendedor) {
            // Obtener el valor del atributo value (que viene de Livewire)
            const empleadoId = campoVendedor.getAttribute('value');
            if (empleadoId) {
                campoVendedor.value = empleadoId;
                console.log('Campo vendedor sincronizado:', empleadoId);
            }
        }
    }
    
    // Sincronizar el valor inicial
    actualizarCampoVendedor();
    
    // Escuchar eventos de Livewire para actualizar el campo vendedor
    window.addEventListener('actualizarVendedor', function(event) {
        const campoVendedor = document.querySelector('input[name="vendedor"]');
        if (campoVendedor && event.detail && event.detail.vendedor_id !== undefined) {
            campoVendedor.value = event.detail.vendedor_id;
            campoVendedor.setAttribute('value', event.detail.vendedor_id);
            console.log('Campo vendedor actualizado desde evento:', event.detail.vendedor_id);
        }
    });
    
    // Asegurar que el campo vendedor se actualice cuando Livewire actualice el componente
    if (typeof Livewire !== 'undefined') {
        // Usar el hook de Livewire 2.x o 3.x
        if (Livewire.hook) {
            Livewire.hook('message.processed', (message, component) => {
                setTimeout(actualizarCampoVendedor, 100);
            });
        }
        
        // También escuchar eventos de Livewire
        Livewire.on('actualizarVendedor', (data) => {
            const campoVendedor = document.querySelector('input[name="vendedor"]');
            if (campoVendedor && data && data.vendedor_id !== undefined) {
                campoVendedor.value = data.vendedor_id;
                campoVendedor.setAttribute('value', data.vendedor_id);
            }
        });
    }
    
    // Observar cambios en el atributo value del campo hidden
    const campoVendedor = document.querySelector('input[name="vendedor"]');
    if (campoVendedor) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                    const newValue = campoVendedor.getAttribute('value');
                    if (newValue && campoVendedor.value !== newValue) {
                        campoVendedor.value = newValue;
                        console.log('Campo vendedor actualizado por MutationObserver:', newValue);
                    }
                }
            });
        });
        
        observer.observe(campoVendedor, { 
            attributes: true, 
            attributeFilter: ['value'] 
        });
    }
});
</script>











