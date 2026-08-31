<div class="buscador-empleado">
    <!-- Campo de búsqueda -->
    <div class="position-relative">
        <input type="text" 
               class="form-control" 
               wire:model="searchEmpleado" 
               wire:keyup="mostrarResultados"
               placeholder="Buscar empleado por nombre, apellido o RFC..."
               autocomplete="off">
        @if($empleadoSeleccionado)
            <button type="button" 
                    class="btn btn-outline-danger btn-sm position-absolute" 
                    style="right: 5px; top: 50%; transform: translateY(-50%); z-index: 10;"
                    wire:click="limpiarSeleccion"
                    title="Limpiar selección">
                <i class="fa-solid fa-times"></i>
            </button>
        @endif

        <!-- Resultados de búsqueda -->
        @if($mostrarResultados && $empleados->count() > 0)
            <div class="dropdown-menu show w-100 position-absolute" style="z-index: 1050; top: 100%;">
                @foreach($empleados as $empleado)
                    <button type="button" 
                            class="dropdown-item d-flex align-items-center py-2" 
                            wire:click="seleccionarEmpleado({{ $empleado->id }})">
                        <div class="me-3">
                            <i class="fa-solid fa-user-tie text-primary"></i>
                        </div>
                                                 <div class="flex-grow-1">
                             <div class="fw-semibold">
                                 {{ $empleado->primer_nombre }} 
                                 @if($empleado->segundo_nombre){{ $empleado->segundo_nombre }} @endif
                                 {{ $empleado->apellido_paterno }} 
                                 @if($empleado->apellido_materno){{ $empleado->apellido_materno }}@endif
                             </div>
                             @if($empleado->rfc)
                                 <small class="text-muted">{{ $empleado->rfc }}</small>
                             @endif
                         </div>
                    </button>
                @endforeach
            </div>
        @endif

        <!-- Mensaje cuando no hay resultados -->
        @if($mostrarResultados && $searchEmpleado && $empleados->count() === 0)
            <div class="dropdown-menu show w-100 position-absolute" style="z-index: 1050; top: 100%;">
                <div class="dropdown-item text-muted py-3 text-center">
                    <i class="fa-solid fa-search me-2"></i>
                    No se encontraron empleados con "{{ $searchEmpleado }}"
                </div>
            </div>
        @endif
    </div>

    <!-- Empleado seleccionado -->
    @if($empleadoSeleccionado)
        <div class="mt-2 p-2 bg-light rounded border">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-user-check text-success me-2"></i>
                                 <div class="flex-grow-1">
                     <strong>
                         {{ $empleadoSeleccionado->primer_nombre }} 
                         @if($empleadoSeleccionado->segundo_nombre){{ $empleadoSeleccionado->segundo_nombre }} @endif
                         {{ $empleadoSeleccionado->apellido_paterno }} 
                         @if($empleadoSeleccionado->apellido_materno){{ $empleadoSeleccionado->apellido_materno }}@endif
                     </strong>
                     @if($empleadoSeleccionado->rfc)
                         <br><small class="text-muted">RFC: {{ $empleadoSeleccionado->rfc }}</small>
                     @endif
                 </div>
            </div>
        </div>
    @endif
</div>
