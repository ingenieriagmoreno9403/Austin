<div>
    <!-- Buscador de Cliente -->
    <div class="form-outline col-md-5 col-12 p-4" id="existenteCliente">
        <label for="cliente">Cliente <span class="text-danger">*</span></label>
        <div class="position-relative buscador-container">
            <input 
                type="text" 
                wire:model.debounce.300ms="busquedaCliente"
                wire:keyup="buscarClientes"
                class="form-control" 
                placeholder="Buscar cliente por nombre o razón social..."
                style="max-width: 400px!important"
                required
                autocomplete="off"
            >
            
            <!-- Campo oculto para el formulario -->
            <input type="hidden" name="cliente" value="{{ $clienteId }}">
            
            <!-- Botón para limpiar -->
            @if($clienteId)
                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" 
                        style="right: 5px; top: 50%; transform: translateY(-50%);"
                        wire:click="limpiarBusqueda">
                    <i class="fa-solid fa-times"></i>
                </button>
            @endif
            
            <!-- Resultados de búsqueda -->
            @if($mostrarResultados && count($clientesFiltrados) > 0)
                <div class="position-absolute w-100 bg-white border rounded shadow-sm resultados-busqueda" 
                     style="top: 100%; left: 0; z-index: 1000; max-height: 200px; overflow-y: auto;">
                    @foreach($clientesFiltrados as $cliente)
                        <div class="p-2 border-bottom cursor-pointer hover-bg-light"
                             wire:click="seleccionarClienteBuscador({{ $cliente->id }}, '{{ $cliente->razon_social }}', '{{ $cliente->nombre }}')"
                             style="cursor: pointer;">
                            <div class="fw-bold">{{ $cliente->razon_social }}</div>
                            <small class="text-muted">{{ $cliente->nombre }}</small>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        
        @if($clienteId)
            <div class="valid-feedback">¡Se ve bien!</div>
        @else
            <div class="invalid-feedback">Por favor, busca y selecciona un cliente.</div>
        @endif
    </div>

    <!-- Campo de Persona de Atención -->
    @if($id_cliente)
        <div class="form-outline col-md-5 col-12 p-4" id="personaAtencionDiv">
            <label for="id_atencion">Persona de Atención <span class="text-danger">*</span></label>
            <div class="row">
                <div class="col-md-8">
                    <select wire:model="personaSeleccionada" name="id_atencion" id="id_atencion" class="select-form select2" style="max-width: 300px!important" required>
                        <option value="">Seleccione una persona...</option>
                        @foreach($personasAtencion as $persona)
                            <option value="{{ $persona->id }}" {{ $personaPreSeleccionada == $persona->id ? 'selected' : '' }}>
                                {{ trim($persona->primer_nombre . ' ' . ($persona->segundo_nombre ?: '') . ' ' . $persona->apellido_paterno . ' ' . ($persona->apellido_materno ?: '')) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-sm btn-primary" wire:click="abrirModal">
                        <i class="fa-solid fa-plus"></i> Nueva
                    </button>
                </div>
            </div>
            <div class="valid-feedback">¡Se ve bien!</div>
            <div class="invalid-feedback">Por favor, selecciona una persona de atención.</div>
        </div>
    @else
        <div class="alert alert-warning fs-9">
            No se ha seleccionado un cliente aún. Selecciona un cliente para ver las personas de atención.
        </div>
    @endif

      <!-- Mensajes de alerta -->
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show fs-9" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show fs-9" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Modal para crear nueva persona de atención -->
    @if($mostrarModal)
        <div class="modal fade show" style="display: block;" tabindex="-1" aria-labelledby="modalNuevaPersonaLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalNuevaPersonaLabel">Nueva Persona de Atención</h5>
                        <button type="button" class="btn-close" wire:click="cerrarModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form wire:submit.prevent="guardarPersona">
                                                         <div class="mb-3">
                                 <label for="primer_nombre" class="form-label">Primer Nombre *</label>
                                 <input type="text" class="form-control @error('primer_nombre') is-invalid @enderror" 
                                        id="primer_nombre" wire:model="primer_nombre" required>
                                 @error('primer_nombre')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>

                             <div class="mb-3">
                                 <label for="segundo_nombre" class="form-label">Segundo Nombre</label>
                                 <input type="text" class="form-control" id="segundo_nombre" wire:model="segundo_nombre">
                             </div>

                             <div class="mb-3">
                                 <label for="apellido_paterno" class="form-label">Apellido Paterno *</label>
                                 <input type="text" class="form-control @error('apellido_paterno') is-invalid @enderror" 
                                        id="apellido_paterno" wire:model="apellido_paterno" required>
                                 @error('apellido_paterno')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>

                             <div class="mb-3">
                                 <label for="apellido_materno" class="form-label">Apellido Materno</label>
                                 <input type="text" class="form-control" id="apellido_materno" wire:model="apellido_materno">
                             </div>

                             <div class="mb-3">
                                 <label for="telefono" class="form-label">Teléfono</label>
                                 <input type="text" class="form-control @error('telefono') is-invalid @enderror" 
                                        id="telefono" wire:model="telefono" maxlength="10">
                                 @error('telefono')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>

                             <div class="mb-3">
                                 <label for="correo" class="form-label">Correo Electrónico</label>
                                 <input type="email" class="form-control @error('correo') is-invalid @enderror" 
                                        id="correo" wire:model="correo">
                                 @error('correo')
                                     <div class="invalid-feedback">{{ $message }}</div>
                                 @enderror
                             </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="cerrarModal">Cancelar</button>
                        <button type="button" class="btn btn-primary" wire:click="guardarPersona">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Scripts para el modal -->
    <script>
        // Escuchar eventos de Livewire
        document.addEventListener('livewire:load', function () {
            Livewire.on('personaSeleccionada', function (idPersona) {
                // Aquí puedes agregar lógica adicional si es necesario
                console.log('Persona seleccionada:', idPersona);
            });
        });
    </script>

    <script>
        $(function() {
            $('input[type=text]').keyup(function() {
                this.value = this.value.toLocaleUpperCase();
            });
        });
  
        $(function() {
            $('textarea').keyup(function() {
                this.value = this.value.toLocaleUpperCase();
            });
        });
    </script>
    
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
</div>
