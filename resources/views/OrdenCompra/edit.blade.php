@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid format_page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-pen-to-square"></i>
                        </div>
                        <div>
                            <div class="mb-1">
                                <a href="{{ route('ordcompras.index') }}" class="text-muted text-decoration-none fs-8">
                                    <i class="fa-solid fa-chevron-left me-1"></i>Órdenes de compra
                                </a>
                            </div>
                            <h2 class="mb-0 text-marino fw-bold">
                                Editar Orden de Compra
                                <span class="badge bg-orange fs-6">Folio: {{ $OrdenCompra->folio }}</span>
                            </h2>
                            <p class="text-muted mb-0">Modificación de la orden de compra</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario discreto de decisión de revisión al inicio -->
        @if($OrdenCompra->estado == 'revision' &&  $permiso2 == 'revision_ordenesCompra')
        <div class="oc-general bg-body shadow-sm rounded-3 p-3 mb-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="d-flex align-items-center mb-1">
                        <i class="fa-solid fa-exclamation-triangle text-warning me-2"></i>
                        <span class="fw-semibold text-secondary">Esta orden está en revisión</span>
                    </div>
                    <small class="text-muted">Decide si aprobarla o rechazarla para continuar.</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm" onclick="cambiarEstado('aceptado')">
                        <i class="fa-solid fa-check me-1"></i> Aprobar
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="cambiarEstado('rechazado')">
                        <i class="fa-solid fa-times me-1"></i> Rechazar
                    </button>
                </div>
            </div>
        </div>
        @endif

        <div data-bs-spy="scroll" data-bs-target="#navbar-example" data-bs-offset="0" class="scrollspy-example" tabindex="0">

            <form action="{{ route('ordcompras.update', $OrdenCompra->id) }}" method="POST" enctype="multipart/form-data"
                class="g-3 needs-validation form" id="form-orden-compra" novalidate>
                @csrf
                @method('PUT')
                <input type="hidden" name="detalle_json" id="detalle_json">

                <!-- Datos generales-->
                <div class="bg-body shadow-sm rounded-3 p-4 mb-4 oc-general">
                    <div class="oc-general__header">
                        <h4 id="paso1" class="mb-0"><b class="fs-3 text-orange">1. </b> General</h4>
                        <a class="btn btn-outline-secondary btn-sm"
                            href="{{ route('ordcompras.pdf', $OrdenCompra->id) }}"
                            target="_blank"
                            title="Generar Reporte PDF">
                            <i class="fa-solid fa-file-pdf me-1"></i> Ver PDF
                        </a>
                    </div>

                    <div class="oc-general__grid">
                        {{-- Columna izquierda: datos principales --}}
                        <div class="oc-general__stack">
                            <div class="oc-general__card">
                                <h5 class="oc-general__card-title">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                    Datos de la orden
                                </h5>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="oc-general__field">
                                            <label for="folio" class="oc-general__label">
                                                <i class="fa-solid fa-hashtag"></i> Folio <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" minlength="2" maxlength="20"
                                                class="form-control" placeholder="Ej. OC-001"
                                                id="folio" name="folio" value="{{ $OrdenCompra->folio }}" required />
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="oc-general__field">
                                            <label class="oc-general__label" for="nombre">
                                                <i class="fa-solid fa-pen"></i> Nombre <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" name="nombre" id="nombre"
                                                value="{{ $OrdenCompra->nombre }}"
                                                placeholder="Nombre o referencia de la orden"
                                                minlength="3" maxlength="200" required />
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="oc-general__field">
                                            <label class="oc-general__label" for="fecha_creacion">
                                                <i class="fa-solid fa-calendar-day"></i> Fecha creación <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" id="fecha_creacion" name="fecha_creacion"
                                                class="form-control" value="{{ $OrdenCompra->fecha_creacion }}" required />
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="oc-general__field">
                                            <label class="oc-general__label" for="fecha_limite">
                                                <i class="fa-solid fa-calendar-check"></i> Fecha entrega <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" name="fecha_limite" id="fecha_limite"
                                                class="form-control" value="{{ $OrdenCompra->fecha_limite }}" required />
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="oc-general__field">
                                            <label class="oc-general__label" for="fecha_tentativa_pago">
                                                <i class="fa-solid fa-calendar-days"></i> Fecha tentativa de pago
                                            </label>
                                            <div class="input-group">
                                                <input type="date" name="fecha_tentativa_pago" id="fecha_tentativa_pago"
                                                    class="form-control" value="{{ $OrdenCompra->fecha_tentativa_pago ?? '' }}" />
                                                <button type="button" class="btn btn-outline-secondary oc-general__calc-btn"
                                                    id="btnCalcularFechaPago"
                                                    title="Calcular con días de crédito del proveedor">
                                                    <i class="fa-solid fa-calculator"></i>
                                                </button>
                                            </div>
                                            <small class="oc-general__hint">
                                                Entrega + días de crédito del proveedor
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="oc-general__card">
                                <h5 class="oc-general__card-title">
                                    <i class="fa-solid fa-users"></i>
                                    Comprador y proveedor
                                </h5>

                                <div class="oc-general__field">
                                    <label class="oc-general__label" for="comprador_id">
                                        <i class="fa-solid fa-user-tie"></i> Comprador <span class="text-danger">*</span>
                                    </label>
                                    <select name="comprador_id" id="comprador_id" class="form-select" required>
                                        <option value="">Selecciona un comprador...</option>
                                        @foreach($empleados as $empleado)
                                            @php
                                                $nombreCompleto = trim(
                                                    $empleado->primer_nombre . ' ' .
                                                    ($empleado->segundo_nombre ?: '') . ' ' .
                                                    $empleado->apellido_paterno . ' ' .
                                                    ($empleado->apellido_materno ?: '')
                                                );
                                            @endphp
                                            <option value="{{ $empleado->id }}"
                                                {{ $OrdenCompra->comprador_id == $empleado->id ? 'selected' : '' }}>
                                                {{ $nombreCompleto }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, selecciona un comprador.</div>
                                </div>

                                <div class="oc-general__field" id="buscar-proveedor-root">
                                    @livewire('buscar-proveedor', [
                                        'proveedor_id' => $OrdenCompra->proveedor_id ?? null,
                                        'eslicitacion' => false,
                                        'sololectura' => false,
                                        'nombre_seleccionado' => $OrdenCompra->proveedor->nombre ?? null,
                                        'persona_atencion_id' => $OrdenCompra->persona_atencion_id ?? null
                                    ])
                                </div>
                            </div>
                        </div>

                        {{-- Columna derecha: adicional + archivo --}}
                        <div class="oc-general__stack">
                            <div class="oc-general__card">
                                <h5 class="oc-general__card-title">
                                    <i class="fa-solid fa-sliders"></i>
                                    Detalles adicionales
                                </h5>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="oc-general__field">
                                            <label class="oc-general__label" for="condiciones_entrega">
                                                <i class="fa-solid fa-truck-fast"></i> Condiciones de entrega
                                            </label>
                                            <textarea name="condiciones_entrega" id="condiciones_entrega"
                                                class="form-control" rows="3"
                                                placeholder="Ej: Entrega en 15 días hábiles, FOB origen...">{{ $OrdenCompra->condiciones_entrega }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="oc-general__field">
                                            <label class="oc-general__label" for="observaciones">
                                                <i class="fa-solid fa-note-sticky"></i> Observaciones / Identificador
                                            </label>
                                            <textarea name="observaciones" id="observaciones"
                                                class="form-control" rows="3"
                                                placeholder="Observaciones adicionales...">{{ $OrdenCompra->observaciones }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="oc-general__field">
                                            <label class="oc-general__label" for="descripcion_detalle">
                                                <i class="fa-solid fa-comment-dots"></i> Comentarios
                                            </label>
                                            <textarea name="descripcion_detalle" id="descripcion_detalle"
                                                class="form-control" rows="2"
                                                placeholder="Notas o comentarios de la orden...">{{ $OrdenCompra->descripcion_detalle }}</textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="oc-general__field">
                                            <label class="oc-general__label" for="tipo_moneda">
                                                <i class="fa-solid fa-coins"></i> Tipo de moneda <span class="text-danger">*</span>
                                            </label>
                                            <select name="tipo_moneda" id="tipo_moneda" class="form-select" required>
                                                <option value="MXN" {{ $OrdenCompra->tipo_moneda == 'MXN' ? 'selected' : '' }}>Peso Mexicano (MXN)</option>
                                                <option value="USD" {{ $OrdenCompra->tipo_moneda == 'USD' ? 'selected' : '' }}>Dólar Estadounidense (USD)</option>
                                                <option value="EUR" {{ $OrdenCompra->tipo_moneda == 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                                                <option value="CAD" {{ $OrdenCompra->tipo_moneda == 'CAD' ? 'selected' : '' }}>Dólar Canadiense (CAD)</option>
                                            </select>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, selecciona el tipo de moneda.</div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="oc-general__field">
                                            <label class="oc-general__label" for="iva_aplicado">
                                                <i class="fa-solid fa-percent"></i> Aplicar IVA <span class="text-danger">*</span>
                                            </label>
                                            <select name="iva_aplicado" id="iva_aplicado" class="form-select" required>
                                                @if($OrdenCompra->iva_aplicado == 1)
                                                    <option value="1" selected>Sí</option>
                                                    <option value="0">No</option>
                                                @else
                                                    <option value="1">Sí</option>
                                                    <option value="0" selected>No</option>
                                                @endif
                                            </select>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, selecciona una opción.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="oc-general__card">
                                <h5 class="oc-general__card-title">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    Archivo del cliente
                                </h5>

                                <div class="oc-general__file">
                                    <label class="oc-general__label" for="archivo_orden_cliente">
                                        Orden de compra del cliente (PDF)
                                    </label>

                                    @if($OrdenCompra->ruta_orden_cliente)
                                        <div class="oc-general__file-current">
                                            <div>
                                                <i class="fa-solid fa-file-pdf text-danger me-2"></i>
                                                <strong>Actual:</strong>
                                                <span class="text-muted">{{ basename($OrdenCompra->ruta_orden_cliente) }}</span>
                                            </div>
                                            <a href="{{ asset('OC_CLIENTES/' . $OrdenCompra->ruta_orden_cliente) }}"
                                                target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-eye me-1"></i> Ver
                                            </a>
                                        </div>
                                    @endif

                                    <input type="file"
                                        name="archivo_orden_cliente"
                                        id="archivo_orden_cliente"
                                        class="form-control"
                                        accept=".pdf" />
                                    <small class="oc-general__hint">
                                        @if($OrdenCompra->ruta_orden_cliente)
                                            Sube un nuevo archivo para reemplazar el actual.
                                        @else
                                            Opcional. Solo archivos PDF.
                                        @endif
                                    </small>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, selecciona un archivo PDF válido.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                @livewire('licitacion-detalle', ['detalle' => old('detalle', $detalle ?? []), 'eslicitacion'=>false,'sololectura' => false])

                <!-- Guardar-->
                <div class="mb-5 text-center" style="padding:10px;">
                    <div>
                        <button type="button" class="btn btn-baseColor-light"
                            onclick="window.location='{{ route('ordcompras.index') }}'">
                            &nbsp;<i class="fa-solid fa-arrow-left me-2"></i>Regresar
                        </button>

                        <button type="button" id="btnGuardar" class="btn btn-baseColor push" onclick="validarYGuardar()">
                            &nbsp;<i class="fa-solid fa-check me-2"></i>Guardar cambios
                        </button>
                        <br>
                        <br>
                    </div>
                </div>

            </form>

        </div>

    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/validaPDF.js') }}"></script>
    <script>
        window.addEventListener('producto-duplicado', event => {
            alert(event.detail.mensaje);
        });
        
        window.addEventListener('proveedores-validos', function () {
            // Cuando los proveedores estén validados, procede con el flujo Livewire
            // para que el componente detalle emita 'set-detalle-json' y luego se envíe el formulario
            if (typeof Livewire !== 'undefined') {
                Livewire.emit('guardarTodo');
            }
        });
        
        window.addEventListener('proveedores-no-validos', function (event) {
            alert(event.detail.mensaje);
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar el botón guardar para usar la validación personalizada
            document.querySelector('#btnGuardar').addEventListener('click', function(e) {
                e.preventDefault();
                validarYGuardar();
            });
            
            // Escuchar el evento set-detalle-json de Livewire
            window.addEventListener('set-detalle-json', event => {
                document.querySelector('#detalle_json').value = event.detail.detalle;
                
                // Prevenir recarga automática para ver errores
                const form = document.querySelector('#form-orden-compra');
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.text();
                        } else {
                            throw new Error('Error del servidor: ' + response.status);
                        }
                    })
                    .then(data => {
                        // Si es exitoso, redirigir
                        if (data.includes('success') || data.includes('redirect')) {
                            window.location.href = '{{ route("ordcompras.index") }}';
                        } else {
                            // Mostrar error del servidor
                            Swal.fire({
                                icon: 'error',
                                title: 'Error al guardar',
                                html: '<pre style="text-align: left; font-size: 12px;">' + data + '</pre>',
                                confirmButtonColor: '#d33',
                                width: '80%'
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al guardar',
                            text: 'Hubo un error al guardar la orden de compra: ' + error.message,
                            confirmButtonColor: '#d33'
                        });
                    });
                });
                
                form.submit();
            });

            // Escuchar eventos emitidos por Livewire (component emit), no browser events
            if (typeof Livewire !== 'undefined') {
                Livewire.on('formularioValido', () => {
                    Livewire.emit('guardarTodo');
                });
                Livewire.on('formularioIncompleto', (errores) => {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Formulario Incompleto',
                        html: '<ul class="text-start">' + 
                               (errores || []).map(error => '<li>' + error + '</li>').join('') + 
                               '</ul>',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Entendido'
                    });
                });
            }
            
            // El campo select de comprador ya no necesita eventos especiales

            window.addEventListener('error-evento', event => {
                alert(event.detail.mensaje);
            });
        });
        
        // Función para cambiar estado desde el formulario de edición
        function cambiarEstado(nuevoEstado) {
            const estadoTexto = nuevoEstado === 'aceptado' ? 'aceptada' : 'rechazada';
            const icono = nuevoEstado === 'aceptado' ? 'success' : 'warning';
            const titulo = nuevoEstado === 'aceptado' ? 'Aprobar Orden' : 'Rechazar Orden';
            const mensaje = nuevoEstado === 'aceptado' 
                ? '¿Estás seguro de que deseas aprobar esta orden de compra?' 
                : '¿Estás seguro de que deseas rechazar esta orden de compra?';
            
            Swal.fire({
                title: titulo,
                text: mensaje,
                icon: icono,
                showCancelButton: true,
                confirmButtonColor: nuevoEstado === 'aceptado' ? '#28a745' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: nuevoEstado === 'aceptado' ? 'Sí, aprobar' : 'Sí, rechazar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Crear formulario para enviar el cambio de estado
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("ordcompras.cambiarEstado", $OrdenCompra->id) }}';
                    
                    // Agregar token CSRF
                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);
                    
                    // Agregar método PUT
                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'PUT';
                    form.appendChild(methodField);
                    
                    // Agregar nuevo estado
                    const estadoField = document.createElement('input');
                    estadoField.type = 'hidden';
                    estadoField.name = 'estado';
                    estadoField.value = nuevoEstado;
                    form.appendChild(estadoField);
                    
                    // Enviar formulario
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Función para calcular fecha tentativa de pago
        function calcularFechaTentativaPago() {
            const proveedorId = document.querySelector('input[name="proveedor_id"]')?.value;
            const fechaLimite = document.getElementById('fecha_limite')?.value;
            const fechaTentativaPago = document.getElementById('fecha_tentativa_pago');
            
            if (!proveedorId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Proveedor no seleccionado',
                    text: 'Debes seleccionar un proveedor primero para calcular la fecha de pago.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            
            if (!fechaLimite) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha de entrega requerida',
                    text: 'Debes ingresar la fecha de entrega primero.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            
            // Hacer petición AJAX para obtener días de crédito
            fetch(`/api/proveedor/${proveedorId}/dias-credito`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.dias_credito) {
                    // Calcular fecha sumando días de crédito a fecha límite
                    const fechaLimiteObj = new Date(fechaLimite);
                    fechaLimiteObj.setDate(fechaLimiteObj.getDate() + parseInt(data.dias_credito));
                    
                    // Formatear fecha en formato YYYY-MM-DD
                    const year = fechaLimiteObj.getFullYear();
                    const month = String(fechaLimiteObj.getMonth() + 1).padStart(2, '0');
                    const day = String(fechaLimiteObj.getDate()).padStart(2, '0');
                    const fechaCalculada = `${year}-${month}-${day}`;
                    
                    fechaTentativaPago.value = fechaCalculada;
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Fecha calculada',
                        text: `Fecha tentativa de pago calculada: ${day}/${month}/${year} (${data.dias_credito} días de crédito)`,
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin días de crédito',
                        text: 'El proveedor no tiene días de crédito configurados. Puedes ingresar la fecha manualmente.',
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'Entendido'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al calcular fecha',
                    text: 'No se pudo obtener la información del proveedor. Intenta de nuevo.',
                    confirmButtonColor: '#d33'
                });
            });
        }
        
        // Asignar evento al botón de calcular
        document.addEventListener('DOMContentLoaded', function() {
            const btnCalcular = document.getElementById('btnCalcularFechaPago');
            if (btnCalcular) {
                btnCalcular.addEventListener('click', calcularFechaTentativaPago);
            }
        });

        // Función para validar y guardar
        function validarYGuardar() {
            const form = document.getElementById('form-orden-compra');
            
            // Validar que exista comprador seleccionado
            const compradorSelect = document.getElementById('comprador_id');
            if (!compradorSelect || !compradorSelect.value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona un comprador',
                    text: 'Debes seleccionar un comprador antes de guardar.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // Validación HTML5 de campos requeridos
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                Swal.fire({
                    icon: 'warning',
                    title: 'Formulario incompleto',
                    text: 'Revisa los campos requeridos marcados en el formulario.',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            
            // Pedir validación usando evento global
            if (typeof Livewire !== 'undefined') {
                Livewire.emit('validarFormulario');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Sistema',
                    text: 'Livewire no está disponible. Recarga la página e intenta de nuevo.',
                    confirmButtonColor: '#d33'
                });
                return;
            }
        }
    </script>
@endsection
