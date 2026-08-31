/**
 * Funcionalidad para gestionar personas de atención de clientes
 */

// Función para cargar personas de atención
function cargarPersonasAtencion(clienteId) {
    console.log('Cargando personas de atención para cliente:', clienteId);
    
    fetch(`/Clientes/PersonasAtencion/${clienteId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            
            // Buscar en el modal independiente
            const tbodyIndependiente = document.getElementById(`tbodyPersonas${clienteId}`);
            if (tbodyIndependiente) {
                console.log('Actualizando tabla del modal independiente');
                actualizarTablaPersonas(tbodyIndependiente, data);
            }
            
            // Buscar en la pestaña del modal de edición
            const tbodyPestaña = document.getElementById(`tbodyPersonasEdit${clienteId}`);
            if (tbodyPestaña) {
                console.log('Actualizando tabla de la pestaña');
                actualizarTablaPersonas(tbodyPestaña, data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Mostrar error en ambas tablas
            const tbodyIndependiente = document.getElementById(`tbodyPersonas${clienteId}`);
            const tbodyPestaña = document.getElementById(`tbodyPersonasEdit${clienteId}`);
            
            if (tbodyIndependiente) {
                tbodyIndependiente.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error al cargar datos</td></tr>';
            }
            
            if (tbodyPestaña) {
                tbodyPestaña.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error al cargar datos</td></tr>';
            }
        });
}

// Función para actualizar la tabla de personas
function actualizarTablaPersonas(tbody, data) {
    console.log('Actualizando tabla con', data.length, 'personas');
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay personas de atención registradas</td></tr>';
        return;
    }
    
    data.forEach(persona => {
        const nombreCompleto = `${persona.primer_nombre} ${persona.segundo_nombre || ''} ${persona.apellido_paterno} ${persona.apellido_materno || ''}`.trim();
        
        const row = `
            <tr data-persona-id="${persona.id}">
                <td>${nombreCompleto}</td>
                <td>${persona.telefono || 'N/A'}</td>
                <td>${persona.correo || 'N/A'}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-warning me-1" 
                            onclick="editarPersonaAtencionCliente(${persona.id}, '${persona.primer_nombre}', '${persona.segundo_nombre || ''}', '${persona.apellido_paterno}', '${persona.apellido_materno || ''}', '${persona.telefono || ''}', '${persona.correo || ''}')" 
                            title="Editar persona de atención">
                        <i class="fa-solid fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" 
                            onclick="eliminarPersonaAtencionCliente(${persona.id}, '${nombreCompleto}')" 
                            title="Eliminar persona de atención">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

// Función para recargar personas de atención después de agregar una nueva
function recargarPersonasAtencion(clienteId) {
    setTimeout(() => {
        cargarPersonasAtencion(clienteId);
    }, 500);
}

// Función para limpiar formularios de personas de atención
function limpiarFormularioPersonaAtencion(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        // Remover clases de validación
        form.classList.remove('was-validated');
    }
}

// Función para probar si el modal existe
function probarModal(clienteId) {
    const modal = document.getElementById(`modalPersonasAtencion${clienteId}`);
    if (modal) {
        console.log(`Modal encontrado para cliente ${clienteId}:`, modal);
        return true;
    } else {
        console.log(`Modal NO encontrado para cliente ${clienteId}`);
        return false;
    }
}

// Función para abrir modal manualmente (PRUEBA)
function abrirModalManual(clienteId) {
    console.log('Intentando abrir modal manualmente para cliente:', clienteId);
    
    const modal = document.getElementById(`modalPersonasAtencion${clienteId}`);
    if (modal) {
        console.log('Modal encontrado, abriendo...');
        
        // Usar Bootstrap 5 para abrir el modal
        if (typeof bootstrap !== 'undefined') {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            console.log('Modal abierto con Bootstrap');
        } else {
            console.error('Bootstrap no disponible');
        }
    } else {
        console.error('Modal no encontrado');
    }
}

// Función para editar persona de atención de cliente
function editarPersonaAtencionCliente(personaId, primerNombre, segundoNombre, apellidoPaterno, apellidoMaterno, telefono, correo) {
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

    // Llenar el formulario de edición con los datos actuales
    const form = document.getElementById('formEditarPersonaAtencionCliente');
    if (form) {
        document.getElementById('persona_id_edit_cliente').value = personaId;
        document.getElementById('primer_nombre_edit_cliente').value = primerNombre;
        document.getElementById('segundo_nombre_edit_cliente').value = segundoNombre || '';
        document.getElementById('apellido_paterno_edit_cliente').value = apellidoPaterno;
        document.getElementById('apellido_materno_edit_cliente').value = apellidoMaterno || '';
        document.getElementById('telefono_edit_cliente').value = telefono;
        document.getElementById('correo_edit_cliente').value = correo;
        
        // Configurar la acción del formulario
        form.action = '/Clientes/ActualizarPersonaAtencion/' + personaId;
        
        // Mostrar el modal de edición
        const modal = new bootstrap.Modal(document.getElementById('modalEditarPersonaAtencionCliente'));
        modal.show();
    } else {
        mostrarMensaje('Error', 'No se pudo abrir el formulario de edición', 'error');
    }
}

// Función para eliminar persona de atención de cliente
function eliminarPersonaAtencionCliente(personaId, nombreCompleto) {
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

    // Confirmar eliminación
    const confirmar = typeof Swal !== 'undefined' 
        ? Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas eliminar a "${nombreCompleto}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        })
        : confirm(`¿Deseas eliminar a "${nombreCompleto}"?`);

    if (typeof Swal !== 'undefined') {
        confirmar.then((result) => {
            if (result.isConfirmed) {
                ejecutarEliminacion(personaId);
            }
        });
    } else {
        if (confirmar) {
            ejecutarEliminacion(personaId);
        }
    }
}

// Función para ejecutar la eliminación
function ejecutarEliminacion(personaId) {
    fetch(`/Clientes/EliminarPersonaAtencion/${personaId}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensaje de éxito
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert(data.message);
            }
            
            // Recargar la página para actualizar la lista
            location.reload();
        } else {
            throw new Error(data.message || 'Error al eliminar');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const mensaje = typeof Swal !== 'undefined' 
            ? Swal.fire({
                icon: 'error',
                title: 'Error',
                text: `Error al eliminar: ${error.message}`
            })
            : alert(`Error al eliminar: ${error.message}`);
    });
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando funcionalidad de personas de atención...');
    
    // Verificar que Bootstrap esté disponible
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap no está disponible');
        return;
    }
    
    // Para el modal independiente de personas de atención
    const modales = document.querySelectorAll('[id^="modalPersonasAtencion"]');
    console.log('Modales encontrados:', modales.length);
    
    // Probar cada modal individualmente
    modales.forEach((modal, index) => {
        console.log(`Modal ${index + 1}:`, modal.id, modal);
        
        // Verificar que el modal tenga el evento show.bs.modal
        modal.addEventListener('show.bs.modal', function(e) {
            const clienteId = this.id.replace('modalPersonasAtencion', '');
            console.log('Abriendo modal para cliente:', clienteId);
            console.log('Evento show.bs.modal disparado');
            
            // Cargar personas cuando se abra el modal
            setTimeout(() => {
                cargarPersonasAtencion(clienteId);
            }, 100);
        });
        
        // Limpiar formulario cuando se cierre el modal
        modal.addEventListener('hidden.bs.modal', function() {
            const clienteId = this.id.replace('modalPersonasAtencion', '');
            limpiarFormularioPersonaAtencion(`formPersonasAtencion${clienteId}`);
        });
    });
    
    // Para la pestaña de personas de atención en el modal de edición
    const tabsPersonas = document.querySelectorAll('[id^="personas-tab"]');
    console.log('Pestañas encontradas:', tabsPersonas.length);
    
    tabsPersonas.forEach(tab => {
        tab.addEventListener('click', function() {
            const clienteId = this.id.replace('personas-tab', '');
            console.log('Haciendo clic en pestaña para cliente:', clienteId);
            // Pequeño delay para asegurar que la pestaña esté activa
            setTimeout(() => {
                cargarPersonasAtencion(clienteId);
            }, 200);
        });
    });
    
    // Manejar envío de formularios de personas de atención
    const formsPersonasAtencion = document.querySelectorAll('form[action="/Clientes/InsertarPersonaAtencion"]');
    console.log('Formularios encontrados:', formsPersonasAtencion.length);
    
    formsPersonasAtencion.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const clienteId = formData.get('id_cliente');
            
            console.log('Enviando formulario para cliente:', clienteId);
            
            // Validar formulario
            if (!this.checkValidity()) {
                this.classList.add('was-validated');
                return false;
            }
            
            // Si la validación pasa, enviar y recargar
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.text();
            })
            .then(() => {
                console.log('Persona agregada exitosamente');
                
                // Recargar la lista de personas
                recargarPersonasAtencion(clienteId);
                // Limpiar formulario
                this.reset();
                this.classList.remove('was-validated');
                
                // Mostrar mensaje de éxito
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Persona de atención agregada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert('Persona de atención agregada correctamente');
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
    });
    
    // Probar todos los modales después de un delay
    setTimeout(() => {
        console.log('=== PRUEBA DE MODALES ===');
        const clientes = document.querySelectorAll('[id^="modalPersonasAtencion"]');
        clientes.forEach(modal => {
            const clienteId = modal.id.replace('modalPersonasAtencion', '');
            probarModal(clienteId);
        });
        
        // Agregar botón de prueba al primer modal encontrado
        if (clientes.length > 0) {
            const primerCliente = clientes[0];
            const clienteId = primerCliente.id.replace('modalPersonasAtencion', '');
            
            // Agregar botón de prueba en la consola
            console.log('Para probar el modal, ejecuta en la consola:');
            console.log(`abrirModalManual(${clienteId})`);
            console.log(`cargarPersonasAtencion(${clienteId})`);
        }
    }, 1000);
    
    console.log('Funcionalidad de personas de atención inicializada correctamente');
});
