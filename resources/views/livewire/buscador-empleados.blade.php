<div class="form-outline col-md-5 col-12 p-4 pt-2">
    <label for="vendedor">Empleado <span class="text-danger">*</span></label>
    <div class="position-relative buscador-container">
        <input 
            type="text" 
            wire:model.debounce.300ms="busquedaEmpleado"
            wire:keyup="buscarEmpleados"
            class="form-control" 
            placeholder="Buscar empleado por nombre, apellido o puesto..."
            required
            autocomplete="off"
        >
        
        <!-- Campo oculto para el formulario -->
        <input type="hidden" name="vendedor" value="{{ $empleadoId }}">
        
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
