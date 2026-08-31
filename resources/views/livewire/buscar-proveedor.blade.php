    <div>
        <div class="row mb-3" wire:key="proveedor-row-{{ $proveedor_id ?? 'empty' }}">
            <div class="col-md-12">
                <div class="position-relative" style="z-index: 1;">
                    <label for="search-proveedor" class="form-label text-secondary"><i class="fa-solid fa-user-tie text-orange me-1"></i>  Proveedor <span class="text-danger">*</span></label>
                    
                    @if(empty($proveedor_id))
                        <div class="input-group mb-2 position-relative" wire:key="proveedor-search-container">
                            <input type="text"
                                id="search-proveedor"
                                class="form-control {{ !empty($mensaje_error) ? 'is-invalid' : '' }}"
                                placeholder="Buscar proveedor por nombre o ID..."
                                wire:model.debounce.500ms="search"
                                autocomplete="off"
                                {{ $sololectura ? 'readonly' : '' }} />
                            
                            @if(!empty($search) && !$sololectura)
                                <button type="button" class="btn btn-outline-secondary" wire:click="limpiarProveedor" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="limpiarProveedor">
                                        <i class="fa-solid fa-times"></i>
                                    </span>
                                    <span wire:loading wire:target="limpiarProveedor">
                                        <i class="fa-solid fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                            @endif
                        </div>
                        
                        @if(!empty($mensaje_error))
                            <div class="invalid-feedback d-block">
                                {{ $mensaje_error }}
                            </div>
                        @endif
                        
                        <!-- Mensaje de validación del proveedor -->
                        <div class="alert alert-warning mt-2 p-2 fs-8">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            <strong>Proveedor requerido</strong> <br> Debe seleccionar un proveedor para continuar.
                        </div>
                    @endif
                    
                    @if(!$sololectura && !empty($search) && count($resultados) > 0)
                        <div class="list-group position-absolute w-100" 
                            wire:key="resultados-proveedor-{{ count($resultados) }}"
                            style="max-height: 200px; overflow-y: auto; z-index: 9999; background-color: white; border: 1px solid #dee2e6; border-radius: 0.375rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); margin-top: 2px;">
                            @foreach($resultados as $proveedor)
                                <button type="button" 
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                    wire:click="seleccionarProveedor({{ $proveedor->id }})"
                                    wire:key="proveedor-{{ $proveedor->id }}"
                                    style="cursor: pointer; z-index: 10000;">
                                    <span>{{ $proveedor->nombre }}</span>
                                    <small class="text-muted">ID: {{ $proveedor->id }}</small>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    
                                    @if(!empty($proveedor_id))
                        <input type="hidden" name="proveedor_id" value="{{ $proveedor_id }}">
                        @if(!empty($personaAtencionSeleccionada))
                            <input type="hidden" name="persona_atencion_id" value="{{ $personaAtencionSeleccionada }}">
                        @endif
                        <div class="alert alert-light p-2 mb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fa-solid fa-check-circle m-2 text-success"></i>
                                    <strong>{{ $nombre_seleccionado }}</strong>
                                </div>
                                @if(!$sololectura)
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            wire:click="limpiarProveedor"
                                            wire:loading.attr="disabled"
                                            wire:target="limpiarProveedor">
                                        <span wire:loading.remove wire:target="limpiarProveedor">
                                            <i class="fa-solid fa-times"></i>
                                        </span>
                                        <span wire:loading wire:target="limpiarProveedor">
                                            <i class="fa-solid fa-spinner fa-spin"></i>
                                        </span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Sección de Personas de Atención -->
        @if(!empty($proveedor_id))
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fa-solid fa-users me-2"></i>
                                Personas de Atención
                                @if($personaAtencionSeleccionada)
                                    <span class="badge bg-success ms-2">
                                        <i class="fa-solid fa-check me-1"></i>Completo
                                    </span>
                                @else
                                    <span class="badge bg-warning ms-2">
                                        <i class="fa-solid fa-exclamation-triangle me-1"></i>Pendiente
                                    </span>
                                @endif
                            </h6>
                            @if(!$sololectura)
                                                            <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                            id="nuevaPersonaCheck" 
                                            wire:click="toggleNuevaPersona"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleNuevaPersona"
                                            {{ $mostrarNuevaPersona ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nuevaPersonaCheck">
                                        <span wire:loading.remove wire:target="toggleNuevaPersona">
                                            Agregar nueva persona
                                        </span>
                                        <span wire:loading wire:target="toggleNuevaPersona" class="text-muted">
                                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Procesando...
                                        </span>
                                    </label>
                                </div>
                            @endif
                        </div>
                        
                        <div class="card-body">
                            <!-- Mostrar persona de atención seleccionada actualmente -->
                            @if($personaAtencionSeleccionada && !$mostrarNuevaPersona)
                                @php
                                    $personaActual = $personasAtencion->firstWhere('id', $personaAtencionSeleccionada);
                                @endphp
                                @if($personaActual)
                                    <div class="bg-light rounded-2 border mb-3 p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="fs-8">
                                                <strong class="text-secondary">Persona de atención actual</strong><br>
                                                <span class="fs-8">{{ $personaActual->nombre_completo }}</span><br>
                                                <small class="text-muted">{{ $personaActual->puesto }}</small>
                                            </div>
                                            @if(!$sololectura)
                                                <div>
                                                    <button type="button" class="btn btn-primary btn-sm me-2 fs-8" 
                                                        wire:click="toggleNuevaPersona"
                                                        wire:loading.attr="disabled"
                                                        wire:target="toggleNuevaPersona">
                                                        <span wire:loading.remove wire:target="toggleNuevaPersona">
                                                            <i class="fa-solid fa-edit me-1"></i> Cambiar
                                                        </span>
                                                        <span wire:loading wire:target="toggleNuevaPersona">
                                                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Procesando...
                                                        </span>
                                                    </button>

                                                    <button type="button" class="btn btn-danger btn-sm fs-8" 
                                                            wire:click="cambiarPersonaAtencion"
                                                            wire:loading.attr="disabled"
                                                            wire:target="cambiarPersonaAtencion">
                                                            <span wire:loading.remove wire:target="cambiarPersonaAtencion">
                                                                <i class="fa-solid fa-times me-1"></i> Remover
                                                            </span>
                                                            <span wire:loading wire:target="cambiarPersonaAtencion">
                                                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Procesando...
                                                            </span>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endif
                            
                            @if($mostrarNuevaPersona)
                                <!-- Formulario para nueva persona -->
                                <div class="row mb-3 form" wire:loading.class="opacity-50" wire:target="toggleNuevaPersona">
                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0 text-primary">
                                                <i class="fa-solid fa-user-plus me-2"></i>
                                                Agregar nueva persona de atención
                                            </h6>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                    wire:click="toggleNuevaPersona"
                                                    wire:loading.attr="disabled"
                                                    wire:target="toggleNuevaPersona">
                                                <span wire:loading.remove wire:target="toggleNuevaPersona">
                                                    <i class="fa-solid fa-arrow-left me-1"></i> Seleccionar Existente
                                                </span>
                                                <span wire:loading wire:target="toggleNuevaPersona">
                                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Procesando...
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase" 
                                            wire:model="nuevaPersona.primer_nombre" 
                                            placeholder="PRIMER NOMBRE"
                                            oninput="this.value = this.value.toUpperCase()">
                                        @error('nuevaPersona.primer_nombre') 
                                            <small class="text-danger">{{ $message }}</small> 
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Segundo Nombre</label>
                                        <input type="text" class="form-control text-uppercase" 
                                            wire:model="nuevaPersona.segundo_nombre" 
                                            placeholder="SEGUNDO NOMBRE (OPCIONAL)"
                                            oninput="this.value = this.value.toUpperCase()">
                                        @error('nuevaPersona.segundo_nombre') 
                                            <small class="text-danger">{{ $message }}</small> 
                                        @enderror
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase" 
                                            wire:model="nuevaPersona.apellido_paterno" 
                                            placeholder="APELLIDO PATERNO"
                                            oninput="this.value = this.value.toUpperCase()">
                                        @error('nuevaPersona.apellido_paterno') 
                                            <small class="text-danger">{{ $message }}</small> 
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Apellido Materno </label>
                                        <input type="text" class="form-control text-uppercase" 
                                            wire:model="nuevaPersona.apellido_materno" 
                                            placeholder="APELLIDO MATERNO (OPCIONAL)"
                                            oninput="this.value = this.value.toUpperCase()">
                                        @error('nuevaPersona.apellido_materno') 
                                            <small class="text-danger">{{ $message }}</small> 
                                        @enderror
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label class="form-label">Puesto <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase" 
                                            wire:model="nuevaPersona.puesto" 
                                            placeholder="CARGO O PUESTO"
                                            oninput="this.value = this.value.toUpperCase()">
                                        @error('nuevaPersona.puesto') 
                                            <small class="text-danger">{{ $message }}</small> 
                                        @enderror
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            wire:click="guardarNuevaPersona"
                                            wire:loading.attr="disabled"
                                            wire:target="guardarNuevaPersona">
                                        <span wire:loading.remove wire:target="guardarNuevaPersona">
                                            <i class="fa-solid fa-check me-1"></i>
                                            Guardar
                                        </span>
                                        <span wire:loading wire:target="guardarNuevaPersona">
                                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...
                                        </span>
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm ms-2" 
                                            wire:click="toggleNuevaPersona"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleNuevaPersona">
                                        <span wire:loading.remove wire:target="toggleNuevaPersona">
                                            <i class="fa-solid fa-times me-1"></i>
                                            Cancelar
                                        </span>
                                        <span wire:loading wire:target="toggleNuevaPersona">
                                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Procesando...
                                        </span>
                                    </button>
                                </div>
                            @else
                                <!-- Mostrar mensaje si hay persona ya seleccionada -->
                                @if($personaAtencionSeleccionada)
                                    <!-- Solo mostrar este mensaje si NO hay persona seleccionada -->
                                @endif
                                
                                <!-- Lista de personas de atención existentes -->
                                @if(count($personasAtencion) > 0)
                                    <!-- Solo mostrar el encabezado y la lista si NO hay persona seleccionada -->
                                    @if(!$personaAtencionSeleccionada)
                                        <div class="mb-3">
                                            <h6 class="text-muted mb-2 fs-8">
                                                <i class="fa-solid fa-list me-2"></i>
                                                Seleccione una persona de atención:
                                            </h6>
                                        </div>
                                        <div class="row">
                                            @foreach($personasAtencion as $persona)
                                                <div class="col-md-6 mb-2">
                                                    <div class="card {{ $personaAtencionSeleccionada == $persona->id ? 'border-primary bg-primary bg-opacity-10' : 'border-light' }}">
                                                        <div class="card-body p-2">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" 
                                                                    name="personaAtencion" 
                                                                    id="persona_{{ $persona->id }}"
                                                                    value="{{ $persona->id }}"
                                                                    wire:click="seleccionarPersonaAtencion({{ $persona->id }})"
                                                                    {{ $personaAtencionSeleccionada == $persona->id ? 'checked' : '' }}
                                                                    {{ $sololectura ? 'disabled' : '' }}>
                                                                <label class="form-check-label" for="persona_{{ $persona->id }}">
                                                                    <strong>{{ $persona->nombre_completo }}</strong><br>
                                                                    <small class="text-muted">{{ $persona->puesto }}</small>
                                                                    @if($personaAtencionSeleccionada == $persona->id)
                                                                        <br><span class="badge bg-primary">Seleccionada</span>
                                                                    @endif
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    @if($personaAtencionSeleccionada)
                                    <input type="hidden" name="persona_atencion_id" value="{{ $personaAtencionSeleccionada }}">
                                        {{-- <div class="alert alert-info mt-3 p-3">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-check-circle me-3 text-info"></i>
                                                    <div>
                                                        <strong>Persona seleccionada:</strong><br>
                                                        @php
                                                            $personaSeleccionada = $personasAtencion->firstWhere('id', $personaAtencionSeleccionada);
                                                        @endphp
                                                        @if($personaSeleccionada)
                                                            <span class="fs-6">{{ $personaSeleccionada->nombre_completo }}</span><br>
                                                            <small class="text-muted">{{ $personaSeleccionada->puesto }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if(!$sololectura)
                                                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="cambiarPersonaAtencion">
                                                        <i class="fa-solid fa-edit me-1"></i> Cambiar
                                                    </button>
                                                @endif
                                            </div>
                                        </div> --}}
                                    @endif 
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fa-solid fa-info-circle fa-2x mb-3 text-primary"></i>
                                        <h6 class="text-muted">No hay personas de atención registradas</h6>
                                        <p class="small">Este proveedor no tiene personas de contacto registradas.</p>
                                        @if(!$sololectura)
                                            <button type="button" class="btn btn-primary btn-sm" wire:click="toggleNuevaPersona">
                                                <i class="fa-solid fa-user-plus me-1"></i> Agregar primera persona
                                            </button>
                                        @endif
                                    </div>
                                @endif
                                
                                <!-- Mensaje cuando no hay persona seleccionada -->
                                @if(count($personasAtencion) > 0 && !$personaAtencionSeleccionada)
                                    {{-- <div class="alert alert-warning mt-3 p-2">
                                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                                        <strong>Atención:</strong> No hay persona de atención seleccionada. 
                                        Por favor, seleccione una persona de la lista o agregue una nueva.
                                    </div> --}}
                                @endif
                                
                                <!-- Mensaje de validación requerida -->
                                @if(!$personaAtencionSeleccionada && count($personasAtencion) > 0)
                                    {{-- <div class="alert alert-danger mt-3 p-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-times-circle me-3 text-danger"></i>
                                            <div>
                                                <strong>Validación Requerida:</strong><br>
                                                <span class="fs-6">Debe seleccionar una persona de atención para continuar.</span>
                                            </div>
                                        </div>
                                    </div> --}}
                                @endif
                            @endif
                            
                            <!-- Mensaje cuando se está creando una nueva persona -->
                            @if($mostrarNuevaPersona && count($personasAtencion) == 0)
                                <div class="alert alert-warning mt-3 p-2">
                                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                                    <strong>Creando primera persona de atención</strong><br>
                                    <small>Esta será la primera persona de contacto registrada para este proveedor.</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Loader para mostrar cuando se está buscando -->
        <div wire:loading wire:target="search">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <span class="text-primary ms-2">Buscando proveedores...</span>
        </div>
        
        <!-- Mensajes de éxito o error -->
        @if(session()->has('message'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-check-circle me-3 text-success"></i>
                    <div>
                        <strong>¡Éxito!</strong><br>
                        {{ session('message') }}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if(session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-exclamation-triangle me-3 text-danger"></i>
                    <div>
                        <strong>¡Error!</strong><br>
                        {{ session('error') }}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    </div>

    <script>
         document.addEventListener('DOMContentLoaded', function() {
             let wasTyping = false;
             let lastSearchValue = '';
             
             // Detectar cuando el usuario está escribiendo
             document.addEventListener('input', function(e) {
                 if (e.target.id === 'search-proveedor') {
                     wasTyping = true;
                     lastSearchValue = e.target.value;
                 }
             });
             
             // Escuchar eventos de Livewire para restaurar el foco y el valor
             if (typeof Livewire !== 'undefined') {
                 Livewire.hook('message.processed', (message, component) => {
                     setTimeout(() => {
                         const input = document.getElementById('search-proveedor');
                         if (input && wasTyping) {
                             // Restaurar el valor si se perdió
                             if (input.value !== lastSearchValue && lastSearchValue.length > 0) {
                                 input.value = lastSearchValue;
                             }
                             
                             // Restaurar el foco
                             if (document.activeElement !== input) {
                                 input.focus();
                                 const len = input.value.length;
                                 input.setSelectionRange(len, len);
                             }
                             
                             wasTyping = false;
                         }
                     }, 100);
                 });
                 
                 // También escuchar cuando Livewire actualiza el DOM
                 Livewire.hook('element.updated', (el, component) => {
                     if (el.id === 'search-proveedor' && wasTyping) {
                         setTimeout(() => {
                             const input = document.getElementById('search-proveedor');
                             if (input && input.value !== lastSearchValue && lastSearchValue.length > 0) {
                                 input.value = lastSearchValue;
                                 input.focus();
                                 const len = input.value.length;
                                 input.setSelectionRange(len, len);
                             }
                         }, 50);
                     }
                 });
             }
             
             // Escuchar eventos de validación del formulario
             window.addEventListener('formularioIncompleto', event => {
                 const errores = event.detail;
                 Swal.fire({
                     icon: 'warning',
                     title: 'Formulario Incompleto',
                     html: '<ul class="text-start">' + 
                            errores.map(error => '<li>' + error + '</li>').join('') + 
                            '</ul>',
                     confirmButtonColor: '#3085d6',
                     confirmButtonText: 'Entendido'
                 });
             });
             
             window.addEventListener('formularioValido', event => {
                 console.log('Formulario válido, se puede continuar');
             });
         });
         
         // Función para validar el formulario antes de enviar
         function validarFormularioAntesDeEnviar() {
             // Usar emitTo para evitar problemas con Livewire.find
             if (typeof Livewire !== 'undefined') {
                 Livewire.emitTo('buscar-proveedor', 'validarFormulario');
                 return false; // Prevenir envío por defecto
             }
             
             // Si no se puede validar, mostrar mensaje
             Swal.fire({
                 icon: 'error',
                 title: 'Error de Validación',
                 text: 'No se pudo validar el formulario. Verifique que todos los campos estén completos.',
                 confirmButtonColor: '#d33'
             });
             return false;
         }
    </script>
     
     <style>
         /* Asegurar que los resultados de búsqueda estén por encima de otros elementos */
         .list-group.position-absolute {
             z-index: 9999 !important;
         }
         
         /* Mejorar la visibilidad de los resultados */
         .list-group-item-action:hover {
             background-color: #f8f9fa !important;
             cursor: pointer !important;
         }
         
         /* Asegurar que el contenedor del proveedor tenga el z-index correcto */
         #buscar-proveedor-root {
             position: relative;
             z-index: 1;
         }
     </style>
