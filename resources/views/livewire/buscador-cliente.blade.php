<div class="buscador-cliente">
    <!-- Buscador de Cliente -->
    <div class="mb-3">
        <label for="searchCliente" class="form-label">
            <i class="fa-solid fa-building me-2 text-info"></i> Cliente
        </label>
        <div class="input-group">
            <input type="text" 
                   class="form-control @error('searchCliente') is-invalid @enderror" 
                   id="searchCliente" 
                   wire:model.debounce.300ms="searchCliente"
                                       placeholder="Escriba para buscar cliente por nombre, razón social o alias..."
                   autocomplete="off">
            @if($clienteSeleccionado)
                <button type="button" class="btn btn-outline-secondary" wire:click="limpiarCliente">
                    <i class="fa-solid fa-times"></i>
                </button>
            @endif
        </div>
        
        @error('searchCliente') 
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <!-- Resultados de búsqueda de clientes -->
        @if(strlen($searchCliente) >= 1 && !$clienteSeleccionado)
            <div class="dropdown-menu show w-100 mt-1" style="position: static; transform: none;">
                @forelse($clientes as $cliente)
                    <a href="#" 
                       class="dropdown-item" 
                       wire:click.prevent="seleccionarCliente({{ $cliente->id }})">
                        <div class="d-flex align-items-center border-bottom">
                            <i class="fa-solid fa-building text-primary me-2"></i>
                            <div>
                                @if($cliente->razon_social)
                                    <strong class="text-dark">{{ $cliente->razon_social }}</strong>
                                @endif
                                @if($cliente->alias)
                                    <br><small class="text-muted"><b>Alias</b> {{ $cliente->alias }}</small>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="dropdown-item text-muted">
                        <i class="fa-solid fa-search me-1"></i>No se encontraron clientes
                    </div>
                @endforelse
            </div>
        @endif

        <!-- Cliente seleccionado -->
        @if($clienteSeleccionado)
            <div class="alert alert-success mt-2 mb-0">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    <div>
                        <strong>Cliente seleccionado</strong> {{ $clienteSeleccionado->razon_social }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Buscador de Persona de Atención (solo visible si hay cliente seleccionado) -->
    @if($clienteSeleccionado)
        <div class="mb-3">
            <label for="searchPersona" class="form-label">
                <i class="fa-solid fa-user-tie me-1"></i>Persona de Atención
            </label>
            <div class="input-group">
                <input type="text" 
                       class="form-control @error('searchPersona') is-invalid @enderror" 
                       id="searchPersona" 
                       wire:model.debounce.300ms="searchPersona"
                                               placeholder="Escriba para buscar persona de atención existente..."
                       autocomplete="off">
                @if($personaSeleccionada)
                    <button type="button" class="btn btn-outline-secondary" wire:click="limpiarPersona">
                        <i class="fa-solid fa-times"></i>
                    </button>
                @endif
            </div>
            
            @error('searchPersona') 
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            <!-- Resultados de búsqueda de personas -->
            @if(strlen($searchPersona) >= 2 && !$personaSeleccionada)
                <div class="dropdown-menu show w-100 mt-1" style="position: static; transform: none;">
                    @forelse($personasAtencion->filter(function($persona) {
                        return stripos($persona->nombre_completo, $this->searchPersona) !== false || 
                               stripos($persona->primer_nombre, $this->searchPersona) !== false ||
                               stripos($persona->apellido_paterno, $this->searchPersona) !== false;
                    }) as $persona)
                        <a href="#" 
                           class="dropdown-item" 
                           wire:click.prevent="seleccionarPersona({{ $persona->id }})">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-user-tie text-success me-2"></i>
                                <div>
                                    <strong>{{ $persona->nombre_completo }}</strong>
                                    @if($persona->telefono)
                                        <br><small class="text-info">📞 {{ $persona->telefono }}</small>
                                    @endif
                                    @if($persona->correo)
                                        <br><small class="text-primary">✉️ {{ $persona->correo }}</small>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="dropdown-item text-muted">
                            <i class="fa-solid fa-search me-1"></i>No se encontraron personas de atención
                        </div>
                    @endforelse
                </div>
            @endif

            <!-- Persona seleccionada -->
            @if($personaSeleccionada)
                <div class="alert alert-info mt-2 mb-0">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-user-check me-2"></i>
                        <div>
                            <strong>Persona seleccionada:</strong> {{ $personaSeleccionada->nombre_completo }}
                            @if($personaSeleccionada->telefono)
                                <br><small>📞 {{ $personaSeleccionada->telefono }}</small>
                            @endif
                            @if($personaSeleccionada->correo)
                                <br><small>✉️ {{ $personaSeleccionada->correo }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Botón para agregar nueva persona -->
            <div class="mt-3">
                <button type="button" 
                        class="btn btn-outline-primary" 
                        wire:click="mostrarFormularioNuevaPersona">
                    <i class="fa-solid fa-plus me-2"></i>Nueva Persona
                </button>
            </div>
        </div>

        <!-- Formulario para nueva persona -->
        @if($mostrarFormNuevaPersona)
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fa-solid fa-user-plus me-2"></i>Registrar Nueva Persona de Atención
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fa-solid fa-user me-1"></i>Primer Nombre *
                            </label>
                            <input type="text" 
                                   class="form-control @error('nuevaPersona.primer_nombre') is-invalid @enderror" 
                                   wire:model="nuevaPersona.primer_nombre" 
                                   placeholder="Ingrese el primer nombre"
                                   style="text-transform: uppercase;"
                                   oninput="this.value = this.value.toUpperCase()">
                            @error('nuevaPersona.primer_nombre') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fa-solid fa-user me-1"></i>Segundo Nombre
                            </label>
                            <input type="text" 
                                   class="form-control @error('nuevaPersona.segundo_nombre') is-invalid @enderror" 
                                   wire:model="nuevaPersona.segundo_nombre" 
                                   placeholder="Segundo nombre (opcional)"
                                   style="text-transform: uppercase;"
                                   oninput="this.value = this.value.toUpperCase()">
                            @error('nuevaPersona.segundo_nombre') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fa-solid fa-user me-1"></i>Apellido Paterno *
                            </label>
                            <input type="text" 
                                   class="form-control @error('nuevaPersona.apellido_paterno') is-invalid @enderror" 
                                   wire:model="nuevaPersona.apellido_paterno" 
                                   placeholder="Ingrese el apellido paterno"
                                   style="text-transform: uppercase;"
                                   oninput="this.value = this.value.toUpperCase()">
                            @error('nuevaPersona.apellido_paterno') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fa-solid fa-user me-1"></i>Apellido Materno
                            </label>
                            <input type="text" 
                                   class="form-control @error('nuevaPersona.apellido_materno') is-invalid @enderror" 
                                   wire:model="nuevaPersona.apellido_materno" 
                                   placeholder="Apellido materno (opcional)"
                                   style="text-transform: uppercase;"
                                   oninput="this.value = this.value.toUpperCase()">
                            @error('nuevaPersona.apellido_materno') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fa-solid fa-phone me-1"></i>Teléfono
                            </label>
                            <input type="tel" 
                                   class="form-control @error('nuevaPersona.telefono') is-invalid @enderror" 
                                   wire:model="nuevaPersona.telefono" maxlength="10"
                                   placeholder="Ej: 55-1234-5678">
                            @error('nuevaPersona.telefono') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                <i class="fa-solid fa-envelope me-1"></i>Correo Electrónico
                            </label>
                            <input type="email" 
                                   class="form-control @error('nuevaPersona.correo') is-invalid @enderror" 
                                   wire:model="nuevaPersona.correo" maxlength="50"
                                   placeholder="correo@ejemplo.com">
                            @error('nuevaPersona.correo') 
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="d-flex gap-3 justify-content-end">
                        <button type="button" 
                                class="btn btn-secondary" 
                                wire:click="mostrarFormNuevaPersona = false">
                            <i class="fa-solid fa-times me-2"></i>Cancelar
                        </button>
                        <button type="button" 
                                class="btn btn-primary" 
                                wire:click="guardarNuevaPersona">
                            <i class="fa-solid fa-save me-2"></i>Guardar Persona
                        </button>
                    </div>
                </div>
            </div>
        @endif


    @endif

    <!-- Campos ocultos para el formulario -->
    <input type="hidden" name="cliente" value="{{ $clienteSeleccionado ? $clienteSeleccionado->id : '' }}">
    <input type="hidden" name="id_atencion" value="{{ $personaSeleccionada ? $personaSeleccionada->id : '' }}">
</div>

@push('scripts')
<script>
// Función para convertir texto a mayúsculas
function convertirAMayusculas(input) {
    input.value = input.value.toUpperCase();
}

// Aplicar conversión a mayúsculas a todos los inputs del formulario de nueva persona
document.addEventListener('livewire:load', function () {
    // Aplicar a inputs existentes
    aplicarMayusculas();
    
    // Aplicar cuando se muestre el formulario de nueva persona
    Livewire.on('formularioNuevaPersonaMostrado', function () {
        setTimeout(aplicarMayusculas, 100);
    });
});

function aplicarMayusculas() {
    const inputs = document.querySelectorAll('input[wire\\:model*="nuevaPersona"]');
    inputs.forEach(input => {
        if (input.type === 'text') {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
            input.addEventListener('blur', function() {
                this.value = this.value.toUpperCase();
            });
        }
    });
}
</script>
<script>
document.addEventListener('livewire:load', function () {
    // Evento cuando se selecciona un cliente
    window.addEventListener('clienteSeleccionado', function (event) {
        console.log('Cliente seleccionado:', event.detail);
        // Aquí puedes agregar lógica adicional si es necesario
    });

    // Evento cuando se selecciona una persona
    window.addEventListener('personaSeleccionada', function (event) {
        console.log('Persona seleccionada:', event.detail);
        // Aquí puedes agregar lógica adicional si es necesario
    });

    // Evento cuando se crea una nueva persona
    window.addEventListener('personaCreada', function (event) {
        // Mostrar notificación de éxito
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: event.detail.message,
                timer: 2000,
                showConfirmButton: false
            });
        }
    });

    // Evento de error
    window.addEventListener('error', function (event) {
        // Mostrar notificación de error
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: event.detail.message
            });
        }
    });
});
</script>
@endpush
