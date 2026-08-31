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
                            <h2 class="mb-0 text-marino fw-bold">Proveedores</h2>
                            <p class="text-muted mb-0">Administración de proveedores</p>
                        </div>
                    </div>
                    <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalInsert">
                            <i class="fa-solid fa-plus"></i> Nuevo Proveedor
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="table-responsive">
                <table class="table table-stripped table-hover display" id="table">
                    <thead>
                        <tr class="table-light text-secondary">
                            {{-- <th class="text-center text-truncate fw-bold">No.</th> --}}
                            <th class="text-center text-truncate fw-bold">Nombre</th>
                            <th class="text-center text-truncate fw-bold">Telefono</th>
                            <th class="text-center text-truncate fw-bold">Direccion</th>
                            <th class="text-center text-truncate fw-bold">Estado</th>
                            <th class="text-center text-truncate fw-bold">Alias</th>
                            <th class="text-center text-truncate fw-bold">RFC</th>
                            <th class="text-center text-truncate fw-bold">Modo de Pago</th>
                            <th class="text-center text-truncate fw-bold">Herramientas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proveedores as $ltsproveedores)
                            <tr>
                                {{-- <td class="fw-bold">{{ $ltsproveedores->id }}</td> --}}
                                <td>{{ $ltsproveedores->nombre }}</td>
                                <td>{{ $ltsproveedores->telefono}}</td>
                                <td>{{ $ltsproveedores->direccion }}</td>
                                <td>
                                    @if ($ltsproveedores->estado == 'A')
                                        <span class="badge badge-success fw-normal fs-9"> Activo</span>
                                    @elseif($ltsproveedores->estado == 'C')
                                        <span class="badge badge-secondary fw-normal fs-9"> Cancelado</span>
                                        @elseif($ltsproveedores->estado == 'I')
                                        <span class="badge badge-danger fw-normal fs-9"> Inactivo</span>
                                    @endif
                                </td>
                                <td>{{ $ltsproveedores->nit }}</td>
                                <td>{{ $ltsproveedores->rfc }}</td>
                                <td class="text-center">
                                    @if($ltsproveedores->modo_pago == 'credito')
                                        <span class="badge bg-warning text-dark">Crédito</span>
                                        <b class="fs-10">{{ $ltsproveedores->dias_credito }} días</b>
                                    @else
                                        <span class="badge bg-success">Contado</span>
                                    @endif
                                </td>
                               
                                <td class="text-truncate">
                                    <a href="/editar_proveedor/{{$ltsproveedores->id}}"
                                        class="btn btn-primary" title="Editar proveedor">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </a>

                                    <button type="button" data-bs-toggle="modal" data-bs-target="#PersonasAtencion{{$ltsproveedores->id}}"
                                        class="btn btn-baseColor" title="Ver personas de atención">
                                        <i class="fa-solid fa-users fs-8"></i>
                                    </button>

                                    <a href="/ProductosProveedor/{{$ltsproveedores->id}}"
                                        class="btn btn-success" title="Productos del proveedor">
                                        <i class="fa-solid fa-bag-shopping fs-8"></i>
                                    </a>

                                    

                                    <button type="button" data-bs-toggle="modal" data-bs-target="#Eliminar{{$ltsproveedores->id}}"
                                        class="btn btn-danger" title="Eliminar proveedor">
                                        <i class="fa-solid fa-trash fs-8"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach ($proveedores as $ltsproveedores)
        <!-- Modal confirmacion -->
        <div class="modal fade" id="Eliminar{{$ltsproveedores->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="p-4 pt-0 pb-0 text-center">
                         <h5>¿Estás seguro de eliminar el proveedor?</h5>
                        </div>
                    </div>

                    <div class="row justify-content-center mt-4 mb-3">
                        <a class="btn btn-baseColor fs-8 col-8" href="/EliminarProveedor/{{$ltsproveedores->id}}" >
                            <i class="fa-solid fa-check"></i> Aceptar
                        </a>
                    </div><br>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($proveedores as $ltsproveedores)
        <!-- Modal Personas de Atención -->
        <div class="modal fade" id="PersonasAtencion{{$ltsproveedores->id}}" tabindex="-1" aria-labelledby="personasAtencionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="personasAtencionModalLabel">
                            <i class="fa-solid fa-users me-2"></i>
                             Personas de Atención  <b class="fs-9">{{ $ltsproveedores->nombre }}</b>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
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
                                            {{-- <th class="text-center">Acciones</th> --}}
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
                                                {{-- <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="eliminarPersonaAtencion({{ $persona->id }}, '{{ $persona->primer_nombre }} {{ $persona->apellido_paterno }}')" 
                                                            title="Eliminar persona de atención">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td> --}}
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
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-danger fs-9" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
{{-- 
                        <button type="button" class="btn btn-success fs-9" onclick="mostrarFormularioNuevaPersona({{ $ltsproveedores->id }})">
                            <i class="fa-solid fa-plus me-1"></i> Agregar Nueva Persona
                        </button> --}}
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($proveedores as $ltsproveedores)
        <!-- Modal Formulario Nueva Persona de Atención -->
        {{-- <div class="modal fade" id="NuevaPersonaAtencion{{$ltsproveedores->id}}" tabindex="-1" aria-labelledby="nuevaPersonaAtencionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="nuevaPersonaAtencionModalLabel">
                            Nueva Persona de Atención  <b class="fs-9">{{ $ltsproveedores->nombre }}</b>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('insertarPersonaAtencion') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <input type="hidden" name="id_proveedor" value="{{ $ltsproveedores->id }}">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="primer_nombre" class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" name="primer_nombre" id="primer_nombre" 
                                           required maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                    <div class="invalid-feedback">
                                        Por favor, ingrese el primer nombre.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="segundo_nombre" class="form-label">Segundo Nombre</label>
                                    <input type="text" class="form-control text-uppercase" name="segundo_nombre" id="segundo_nombre" 
                                           maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="apellido_paterno" class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" name="apellido_paterno" id="apellido_paterno" 
                                           required maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                    <div class="invalid-feedback">
                                        Por favor, ingrese el apellido paterno.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellido_materno" class="form-label">Apellido Materno</label>
                                    <input type="text" class="form-control text-uppercase" name="apellido_materno" id="apellido_materno" required
                                           maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="puesto" class="form-label">Puesto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" name="puesto" id="puesto" 
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
        </div> --}}
    @endforeach

    <!-- Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title text-dark fs-5" id="exampleModalLabel">Eliminar nomina</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 class="text-dark">¿Está seguro que desea eliminar esta nomina?</h6>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger fs-8" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> Cerrar
                    </button>
                    
                </div>
            </div>
        </div>
    </div> 
    

    <!-- Insertar Modal-->
    <div class="modal fade" id="modalInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h4>Nuevo Proveedor</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="insertarproveedor" method="POST" class="form g-3 needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="p-4 pt-0 pb-0">
                            <div class="row mb-3">
                                <div class="row">
                                    <div class="col mb-2">
                                        <div class="form-outline">
                                            <label class="form-label" for="form8Example4">Nombre</label>
                                            <input type="text" class="form-control text" name="nombre"
                                                id="nombre" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col mb-2">
                                        <div class="form-outline">
                                            <label class="form-label">Telefono</label>
                                            <input type="text" id="telefono" name="telefono"
                                                class="form-control" required maxlength="10" />
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
                                    <div class="form-outline">
                                        <label class="form-label">Correo</label>
                                        <input type="email" id="correo" name="correo"
                                            class="form-control" required maxlength="100" required/>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="form-outline">
                                            <label class="form-label">Dirección</label>
                                            <input type="text" id="direccion" name="direccion"
                                                class="form-control text" required />
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
                                    <div class="col-6 mb-2">
                                        <div class="form-outline">
                                                <label class="form-label">Alias</label>
                                                <input type="text" id="nit" name="nit"
                                                class="form-control" required />
                                            <div class="valid-feedback">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-6 mb-2">
                                        <div class="form-outline">
                                            <label class="form-label">Giro</label>
                                            <input type="text" id="giro" name="giro"
                                                class="form-control" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>
            
                                    <div class="col-12 mb-2">
                                        <div class="form-outline">
                                            <label class="form-label">RFC</label>
                                            <input type="text" id="rfc" name="rfc" maxlength="13"
                                                class="form-control" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-6 mb-2">
                                        <div class="form-outline">
                                            <label class="form-label">Modo de Pago <span class="text-danger">*</span></label>
                                            <select class="form-select" id="modo_pago" name="modo_pago" required>
                                                <option value="">Seleccione...</option>
                                                <option value="contado">Contado</option>
                                                <option value="credito">Crédito</option>
                                            </select>
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, seleccione el modo de pago.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-6 mb-2" id="dias_credito_container" style="display: none;">
                                        <div class="form-outline">
                                            <label class="form-label">Días de Crédito <span class="text-danger">*</span></label>
                                            <input type="number" id="dias_credito" name="dias_credito" 
                                                class="form-control" min="1" max="365" />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, ingrese los días de crédito.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                     <!-- Guardar -->
                     <div class="row justify-content-center mt-3 mb-4">
                        <button class="btn btn-baseColor fs-8 col-8" type="submit"><i
                                class="fa-solid fa-check"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Función para mostrar/ocultar el campo de días de crédito
        document.addEventListener('DOMContentLoaded', function() {
            const modoPagoSelect = document.getElementById('modo_pago');
            const diasCreditoContainer = document.getElementById('dias_credito_container');
            const diasCreditoInput = document.getElementById('dias_credito');
            
            if (modoPagoSelect && diasCreditoContainer && diasCreditoInput) {
                modoPagoSelect.addEventListener('change', function() {
                    if (this.value === 'credito') {
                        diasCreditoContainer.style.display = 'block';
                        diasCreditoInput.required = true;
                    } else {
                        diasCreditoContainer.style.display = 'none';
                        diasCreditoInput.required = false;
                        diasCreditoInput.value = '';
                    }
                });
            }
        });
    </script>


    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
    
    <script>
        // Función para editar persona de atención
        function editarPersonaAtencion(personaId) {
            // Aquí puedes implementar la lógica para editar
            // Por ahora solo mostramos un alert
            alert('Función de edición para persona ID: ' + personaId + ' - Implementar según necesidades');
        }

        // Función para eliminar persona de atención
        function eliminarPersonaAtencion(personaId, nombreCompleto) {
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

        // Función para mostrar formulario de nueva persona de atención
        function mostrarFormularioNuevaPersona(proveedorId) {
            // Cerrar el modal actual de personas de atención
            const modalPersonas = document.getElementById('PersonasAtencion' + proveedorId);
            if (modalPersonas) {
                const modal = bootstrap.Modal.getInstance(modalPersonas);
                if (modal) {
                    modal.hide();
                }
            }
            
            // Mostrar el modal del formulario
            const modalFormulario = new bootstrap.Modal(document.getElementById('NuevaPersonaAtencion' + proveedorId));
            modalFormulario.show();
        }

        // Función para agregar nueva persona de atención (mantenida por compatibilidad)
        function agregarNuevaPersona(proveedorId) {
            mostrarFormularioNuevaPersona(proveedorId);
        }
    </script>
    
@endsection
