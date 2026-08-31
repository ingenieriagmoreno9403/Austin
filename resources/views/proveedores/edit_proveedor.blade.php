@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid format_page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-truck-field"></i>
                        </div>
                        <div>
                            <div class="mb-1">
                                <a href="/proveedores" class="text-muted text-decoration-none fs-8">
                                    <i class="fa-solid fa-chevron-left me-1"></i>Proveedores
                                </a>
                            </div>
                            <h2 class="mb-0 text-marino fw-bold">
                                Proveedores <i class="fa-solid fa-chevron-right fs-6"></i> Editar
                            </h2>
                            <p class="text-muted mb-0">Actualización de información del proveedor</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($proveedores as $ltsproveedores)
            <div data-bs-spy="scroll" data-bs-target="#navbar-example" data-bs-offset="0" class="scrollspy-example"
                tabindex="0">

                <form action="{{ route('updateproveedor', $ltsproveedores->id) }}" method="POST"
                    enctype="multipart/form-data" class="g-3 needs-validation form" novalidate>
                    @csrf
                    <!-- Datos generales-->
                    <div class="bg-body border rounded-2 p-4 pt-2 pb-2 mb-4">
                        <div class="row">
                            <div class="col-md-8 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Nombre</label>
                                    <input type="text" hidden class="form-control text" name="nombre"
                                        value="{{ $ltsproveedores->nombre }}"required />
                                    <input type="text" class="form-control text" name="primer_nombre"
                                        id="primer_nombre" value="{{ $ltsproveedores->nombre }}" minlength="3"
                                        maxlength="20" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Telefono</label>
                                    <input type="number" name="telefono" id="telefono"
                                        class="form-control text" value="{{ $ltsproveedores->telefono }}"
                                        minlength="6" maxlength="10" />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Correo</label>
                                    <input type="text" name="correo" id="correo"
                                        class="form-control text" value="{{ $ltsproveedores->otrosconceptos1 }}"
                                        minlength="6" maxlength="1000" required/>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" name="direccion" id="direccion"
                                        class="form-control text" value="{{ $ltsproveedores->direccion }}" minlength="3"
                                        maxlength="60" />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-2 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Estado</label>
                                        <select class="form-select" name="estado" id="estado" required>
                                            <option value="{{ $ltsproveedores->estado }}" selected>
                                                {{ $ltsproveedores->estado}}</option>
                                            <option value="A">(A) Activo</option>
                                            <option value="I">(I) Inactivo</option>
                                        </select>
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-6 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Alias</label>
                                    <input type="text" name="nit" id="nit"
                                        class="form-control text" value="{{ $ltsproveedores->nit}}"
                                        minlength="3" maxlength="100" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-6 mt-2">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Giro</label>
                                    <input type="text" name="giro" id="giro" class="form-control text"
                                        value="{{$ltsproveedores->giro }}" minlength="3" maxlength="60" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">RFC</label>
                                    <input type="text" name="rfc" id="rfc" class="form-control text"
                                        value="{{ $ltsproveedores->rfc}}" minlength="3" maxlength="60" required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-3 col-12 mt-2">
                                <label class="form-label">Evaluacion Proveedor</label>
                                <select class="form-select" name="evaluacion" id="evaluacion" required>
                                    <option value="{{ $ltsproveedores->calificacion_proveedor }}" selected>
                                        {{ $ltsproveedores->calificacion_proveedor}}</option>
                                    <option value="EXCELENTE">EXCELENTE</option>
                                    <option value="BUENO">BUENO</option>
                                    <option value="REGULAR">REGULAR</option>
                                    <option value="MALO">MALO</option>
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                            
                            <div class="col-md-3 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Modo de Pago <span class="text-danger">*</span></label>
                                    <select class="form-select" name="modo_pago" id="modo_pago" required>
                                        <option value="contado" {{ $ltsproveedores->modo_pago == 'contado' ? 'selected' : '' }}>Contado</option>
                                        <option value="credito" {{ $ltsproveedores->modo_pago == 'credito' ? 'selected' : '' }}>Crédito</option>
                                    </select>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, seleccione el modo de pago.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row" id="dias_credito_row" style="display: {{ $ltsproveedores->modo_pago == 'credito' ? 'flex' : 'none' }};">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Días de Crédito <span class="text-danger">*</span></label>
                                    <input type="number" name="dias_credito" id="dias_credito" 
                                        class="form-control text" value="{{ $ltsproveedores->dias_credito ?? '' }}" 
                                        min="1" max="365" {{ $ltsproveedores->modo_pago == 'credito' ? 'required' : '' }} />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, ingrese los días de crédito.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Guardar-->
                        <div class="mt-4 text-end">
                            <div>
                                <button class="btn btn-baseColor fs-8 push" type="submit">&nbsp;<i
                                        class="fa-solid fa-check"></i> Guardar cambios </button>
                                <br><br>
                            </div>
                        </div>
                    </div>
                </form>

                <!--Personas de Atención para Edición -->
                <div class="bg-body border rounded-2 p-4 pt-4 pb-2 mb-4">
                    <div class="row">
                        <h5 class="col">
                            <i class="fa-solid fa-users me-2"></i>  Personas de Atención  
                        </h5>

                        <div class="p-3 col center-end">
                            <button type="button" class="btn btn-success fs-9" onclick="mostrarFormularioNuevaPersonaEdit({{ $ltsproveedores->id }})">
                                    <i class="fa-solid fa-plus me-1"></i> Agregar
                            </button>
                        </div>

                        <div class="row">
                            @php
                                $personasAtencion = \App\Models\ProveedoresAtencion::where('id_proveedor', $ltsproveedores->id)->get();
                            @endphp
                            
                            @if($personasAtencion->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nombre Completo</th>
                                                <th>Puesto</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($personasAtencion as $persona)
                                                <tr data-persona-id="{{ $persona->id }}">
                                                    <td>
                                                        <strong>{{ strtoupper($persona->primer_nombre) }}
                                                        @if($persona->segundo_nombre)
                                                            {{ strtoupper($persona->segundo_nombre) }}
                                                        @endif
                                                        {{ strtoupper($persona->apellido_paterno) }}
                                                        @if($persona->apellido_materno)
                                                            {{ strtoupper($persona->apellido_materno) }}
                                                        @endif</strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">{{ strtoupper($persona->puesto) }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-warning me-1" 
                                                                onclick="editarPersonaAtencionEdit({{ $persona->id }}, '{{ $persona->primer_nombre }}', '{{ $persona->segundo_nombre }}', '{{ $persona->apellido_paterno }}', '{{ $persona->apellido_materno }}', '{{ $persona->puesto }}')" 
                                                                title="Editar persona de atención">
                                                            <i class="fa-solid fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                onclick="eliminarPersonaAtencionEdit({{ $persona->id }}, '{{ $persona->primer_nombre }} {{ $persona->apellido_paterno }}')" 
                                                                title="Eliminar persona de atención">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fa-solid fa-users fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No hay personas de atención registradas</h6>
                                    <p class="text-muted">Este proveedor aún no tiene personas de atención asignadas.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                

                <!-- Modal Formulario Nueva Persona de Atención para Edición -->
                <div class="modal fade" id="NuevaPersonaAtencionEdit{{$ltsproveedores->id}}" tabindex="-1" aria-labelledby="nuevaPersonaAtencionEditModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title" id="nuevaPersonaAtencionEditModalLabel">
                                     Nueva Persona de Atención  <b class="fs-9">{{ $ltsproveedores->nombre }}</b>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('insertarPersonaAtencion') }}" method="POST" class="needs-validation" novalidate id="formPersonaAtencionEdit{{$ltsproveedores->id}}">
                                @csrf
                                <input type="hidden" name="id_proveedor" value="{{ $ltsproveedores->id }}">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="primer_nombre_edit" class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control text-uppercase" name="primer_nombre" id="primer_nombre_edit" 
                                                   required maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                            <div class="invalid-feedback">
                                                Por favor, ingrese el primer nombre.
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="segundo_nombre_edit" class="form-label">Segundo Nombre</label>
                                            <input type="text" class="form-control text-uppercase" name="segundo_nombre" id="segundo_nombre_edit" 
                                                   maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="apellido_paterno_edit" class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control text-uppercase" name="apellido_paterno" id="apellido_paterno_edit" 
                                                   required maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                            <div class="invalid-feedback">
                                                Por favor, ingrese el apellido paterno.
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="apellido_materno_edit" class="form-label">Apellido Materno</label>
                                            <input type="text" class="form-control text-uppercase" name="apellido_materno" id="apellido_materno_edit" 
                                                   maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="puesto_edit" class="form-label">Puesto <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control text-uppercase" name="puesto" id="puesto_edit" 
                                                   required maxlength="100" oninput="this.value = this.value.toUpperCase()">
                                            <div class="invalid-feedback">
                                                Por favor, ingrese el puesto.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-outline-danger fs-9" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cancelar</button>
                                    <button type="submit" class="btn btn-success fs-9">
                                        <i class="fa-solid fa-check me-1"></i> Guardar Persona
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Editar Persona de Atención -->
                <div class="modal fade" id="EditarPersonaAtencion{{$ltsproveedores->id}}" tabindex="-1" aria-labelledby="editarPersonaAtencionModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title" id="editarPersonaAtencionModalLabel">
                                     Editar Persona de Atención  <b class="fs-9">{{ $ltsproveedores->nombre }}</b>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="" method="POST" class="needs-validation" novalidate id="formEditarPersonaAtencion{{$ltsproveedores->id}}">
                                @csrf
                                <input type="hidden" name="id_proveedor" value="{{ $ltsproveedores->id }}">
                                <input type="hidden" name="persona_id" id="persona_id_edit" value="">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="primer_nombre_edit_modal" class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control text-uppercase" name="primer_nombre" id="primer_nombre_edit_modal" 
                                                   required maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                            <div class="invalid-feedback">
                                                Por favor, ingrese el primer nombre.
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="segundo_nombre_edit_modal" class="form-label">Segundo Nombre</label>
                                            <input type="text" class="form-control text-uppercase" name="segundo_nombre" id="segundo_nombre_edit_modal" 
                                                   maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="apellido_paterno_edit_modal" class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control text-uppercase" name="apellido_paterno" id="apellido_paterno_edit_modal" 
                                                   required maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                            <div class="invalid-feedback">
                                                Por favor, ingrese el apellido paterno.
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="apellido_materno_edit_modal" class="form-label">Apellido Materno</label>
                                            <input type="text" class="form-control text-uppercase" name="apellido_materno" id="apellido_materno_edit_modal" 
                                                   maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label for="puesto_edit_modal" class="form-label">Puesto <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control text-uppercase" name="puesto" id="puesto_edit_modal" 
                                                   required maxlength="100" oninput="this.value = this.value.toUpperCase()">
                                            <div class="invalid-feedback">
                                                Por favor, ingrese el puesto.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-outline-danger fs-9" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cancelar</button>
                                    <button type="submit" class="btn btn-warning fs-9">
                                        <i class="fa-solid fa-save me-1"></i> Actualizar Persona
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                    // Función para mostrar/ocultar el campo de días de crédito en edición
                    document.addEventListener('DOMContentLoaded', function() {
                        const modoPagoSelect = document.getElementById('modo_pago');
                        const diasCreditoRow = document.getElementById('dias_credito_row');
                        const diasCreditoInput = document.getElementById('dias_credito');
                        
                        if (modoPagoSelect && diasCreditoRow && diasCreditoInput) {
                            modoPagoSelect.addEventListener('change', function() {
                                if (this.value === 'credito') {
                                    diasCreditoRow.style.display = 'flex';
                                    diasCreditoInput.required = true;
                                } else {
                                    diasCreditoRow.style.display = 'none';
                                    diasCreditoInput.required = false;
                                    diasCreditoInput.value = '';
                                }
                            });
                        }
                    });

                    // Funciones para el modal de personas de atención en edición
                    function editarPersonaAtencionEdit(personaId, primerNombre, segundoNombre, apellidoPaterno, apellidoMaterno, puesto) {
                        // Llenar el formulario con los datos actuales
                        document.getElementById('persona_id_edit').value = personaId;
                        document.getElementById('primer_nombre_edit_modal').value = primerNombre;
                        document.getElementById('segundo_nombre_edit_modal').value = segundoNombre || '';
                        document.getElementById('apellido_paterno_edit_modal').value = apellidoPaterno;
                        document.getElementById('apellido_materno_edit_modal').value = apellidoMaterno || '';
                        document.getElementById('puesto_edit_modal').value = puesto;
                        
                        // Configurar la acción del formulario
                        const form = document.getElementById('formEditarPersonaAtencion{{ $ltsproveedores->id }}');
                        form.action = '/actualizarPersonaAtencion/' + personaId;
                        
                        // Mostrar el modal
                        const modal = new bootstrap.Modal(document.getElementById('EditarPersonaAtencion{{ $ltsproveedores->id }}'));
                        modal.show();
                    }

                    // Función para recargar la lista de personas de atención
                    function recargarListaPersonasAtencion() {
                        // Recargar la página para mostrar la nueva persona
                        location.reload();
                    }

                    // Manejar el envío del formulario de nueva persona
                    document.addEventListener('DOMContentLoaded', function() {
                        const form = document.getElementById('formPersonaAtencionEdit{{ $ltsproveedores->id }}');
                        if (form) {
                            form.addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                const formData = new FormData(this);
                                
                                // Validar formulario
                                if (!this.checkValidity()) {
                                    this.classList.add('was-validated');
                                    return false;
                                }
                                
                                // Enviar formulario
                                fetch(this.action, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    }
                                })
                                .then(response => {
                                    if (response.ok) {
                                        // Mostrar mensaje de éxito
                                        if (typeof Swal !== 'undefined') {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Éxito',
                                                text: 'Persona de atención agregada correctamente',
                                                timer: 2000,
                                                showConfirmButton: false
                                            }).then(() => {
                                                // Cerrar modal y recargar lista
                                                const modal = bootstrap.Modal.getInstance(document.getElementById('NuevaPersonaAtencionEdit{{ $ltsproveedores->id }}'));
                                                if (modal) {
                                                    modal.hide();
                                                }
                                                recargarListaPersonasAtencion();
                                            });
                                        } else {
                                            alert('Persona de atención agregada correctamente');
                                            // Cerrar modal y recargar lista
                                            const modal = bootstrap.Modal.getInstance(document.getElementById('NuevaPersonaAtencionEdit{{ $ltsproveedores->id }}'));
                                            if (modal) {
                                                modal.hide();
                                            }
                                            recargarListaPersonasAtencion();
                                        }
                                    } else {
                                        throw new Error('Error en la respuesta del servidor');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'Error al agregar persona de atención'
                                        });
                                    } else {
                                        alert('Error al agregar persona de atención');
                                    }
                                });
                            });
                        }

                        // Manejar el envío del formulario de editar persona
                        const formEditar = document.getElementById('formEditarPersonaAtencion{{ $ltsproveedores->id }}');
                        if (formEditar) {
                            formEditar.addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                const formData = new FormData(this);
                                
                                // Validar formulario
                                if (!this.checkValidity()) {
                                    this.classList.add('was-validated');
                                    return false;
                                }
                                
                                // Enviar formulario
                                fetch(this.action, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    }
                                })
                                .then(response => {
                                    if (response.ok) {
                                        return response.json();
                                    } else {
                                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                                    }
                                })
                                .then(data => {
                                    // Mostrar mensaje de éxito
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Éxito',
                                            text: data.message || 'Persona de atención actualizada correctamente',
                                            timer: 2000,
                                            showConfirmButton: false
                                        }).then(() => {
                                            // Cerrar modal y recargar lista
                                            const modal = bootstrap.Modal.getInstance(document.getElementById('EditarPersonaAtencion{{ $ltsproveedores->id }}'));
                                            if (modal) {
                                                modal.hide();
                                            }
                                            recargarListaPersonasAtencion();
                                        });
                                    } else {
                                        alert(data.message || 'Persona de atención actualizada correctamente');
                                        // Cerrar modal y recargar lista
                                        const modal = bootstrap.Modal.getInstance(document.getElementById('EditarPersonaAtencion{{ $ltsproveedores->id }}'));
                                        if (modal) {
                                            modal.hide();
                                        }
                                        recargarListaPersonasAtencion();
                                    }
                                })
                                .catch(error => {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: `Error al actualizar persona de atención: ${error.message}`
                                        });
                                    } else {
                                        alert(`Error al actualizar persona de atención: ${error.message}`);
                                    }
                                });
                            });
                        }
                    });

                    function eliminarPersonaAtencionEdit(personaId, nombreCompleto) {
                        // Función para mostrar mensajes
                        const mostrarMensaje = (titulo, mensaje, tipo = 'success') => {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: titulo,
                                    text: mensaje,
                                    icon: tipo,
                                    confirmButtonText: 'Aceptar'
                                });
                            } else {
                                alert(tipo === 'success' ? mensaje : `${titulo}: ${mensaje}`);
                            }
                        };

                        // Función para confirmar eliminación
                        const confirmarEliminacion = () => {
                            if (typeof Swal !== 'undefined') {
                                return Swal.fire({
                                    title: '¿Eliminar persona de atención?',
                                    text: `¿Estás seguro de que deseas eliminar a ${nombreCompleto} de la lista de personas de atención?`,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#3085d6',
                                    confirmButtonText: 'Sí, eliminar',
                                    cancelButtonText: 'Cancelar'
                                });
                            } else {
                                return Promise.resolve({
                                    isConfirmed: confirm(`¿Estás seguro de que deseas eliminar a ${nombreCompleto} de la lista de personas de atención?`)
                                });
                            }
                        };

                        confirmarEliminacion().then((result) => {
                            if (result.isConfirmed) {
                                // Realizar la petición para eliminar
                                fetch('/eliminarPersonaAtencion/' + personaId, {
                                    method: 'GET',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    }
                                })
                                .then(response => {
                                    if (response.ok) {
                                        // Mostrar mensaje de éxito
                                        mostrarMensaje('Éxito', 'Persona de atención eliminada correctamente', 'success');
                                        
                                        // Encontrar y eliminar la fila de la tabla
                                        const fila = document.querySelector(`tr[data-persona-id="${personaId}"]`);
                                        if (fila) {
                                            fila.remove();
                                        }
                                        
                                        // Si no hay más personas, mostrar mensaje de "no hay personas"
                                        const tbody = document.querySelector('.table tbody');
                                        if (tbody && tbody.children.length === 0) {
                                            tbody.innerHTML = `
                                                <tr>
                                                    <td colspan="3" class="text-center py-4">
                                                        <i class="fa-solid fa-users fa-3x text-muted mb-3"></i>
                                                        <h6 class="text-muted">No hay personas de atención registradas</h6>
                                                        <p class="text-muted">Este proveedor aún no tiene personas de atención asignadas.</p>
                                                    </td>
                                                </tr>
                                            `;
                                        }
                                    } else {
                                        // Mostrar mensaje de error
                                        mostrarMensaje('Error', 'Error al eliminar la persona de atención', 'error');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    mostrarMensaje('Error', 'Error al eliminar la persona de atención', 'error');
                                });
                            }
                        });
                    }

                    function mostrarFormularioNuevaPersonaEdit(proveedorId) {
                        // Cerrar el modal actual de personas de atención
                        const modalPersonas = document.getElementById('PersonasAtencionEdit' + proveedorId);
                        if (modalPersonas) {
                            const modal = bootstrap.Modal.getInstance(modalPersonas);
                            if (modal) {
                                modal.hide();
                            }
                        }
                        
                        // Mostrar el modal del formulario
                        const modalFormulario = new bootstrap.Modal(document.getElementById('NuevaPersonaAtencionEdit' + proveedorId));
                        modalFormulario.show();
                    }

                    function agregarNuevaPersonaEdit(proveedorId) {
                        mostrarFormularioNuevaPersonaEdit(proveedorId);
                    }
                </script>
            </div>
        @endforeach
    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/validaPDF.js') }}"></script>
@endsection
