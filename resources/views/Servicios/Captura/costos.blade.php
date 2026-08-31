@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('PDFwarning'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡Seleccione el archivo correcto!", text: "El formato permitido de archivos admitido es .pdf"});';
            echo '</script>'; 
    @endphp
@endif

<div class="container-fluid format_page">
    <!-- Encabezado -->
    <form action="#" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation" novalidate>
      @csrf
        <div class="row">
            <div class="col-lg-6 col-12 start-center">
                <h2 class="mt-1 animate_animated animate_backInLeft">Creación de {{ucfirst(strtolower($tipo))}}</h2>
                <a href="/Servicios" class="btn btn-primary fs-8"><i class="fa-solid fa-house"></i> Inicio</a> 
            </div>

            <div class="col-lg-6 col-12 d-none d-md-block start-center">
               @foreach($servicio_encxid as $key)
                    <div class="row">
                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" type="text" value="{{$key->nombre}}" placeholder="nombre" maxlength="20" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>

                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Folio</label>
                            <input class="form-control" type="text" value="{{$key->folio}}" placeholder="83901" maxlength="10" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    
                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Fecha Inicio</label>
                            <input class="form-control" type="date" value="{{$key->fecha_inicio}}" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>

                        <div class="col-md-3 col-6 form-outline inputform">
                            <label class="form-label">Fecha Limite</label>
                            <input class="form-control" type="date" value="{{$key->fecha_limite}}" required disabled>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                        <label class="form-label">Costo en Suministros</label>
                        <input class="form-control" type="text" value="{{$costo_sumisnitros}}" required disabled>
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 p-3 start-center">
                <div class="row">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleBlue"> 1</div>
                    </div>

                    <div class="col-md-10 col-6 p-3 pb-0 d-none d-md-block">
                        <h6>Datos Informativos</h6>
                    </div>
                </div>

                <div class="lineBlue d-none d-md-block"></div>

                <a href="/Servicios/Captura/Materiales/{{$tipo}}/{{$id}}" class="row pointer">
                <div class="row">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleBlue"> 2</div>
                    </div>

                    <div class="col-md-10 col-6 p-3 pb-0 d-none d-md-block">
                        <h6 class="text-dark">Materiales y Suministro</h6>
                    </div>
                </div>
                </a>

                 @if($tipo == "Integración")
                <div class="lineBlue d-none d-md-block"></div>

                <div class="row">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleBlue">3</div>
                    </div>

                    <div class="col-md-10 col-12 d p-3 pb-0">
                        <h6>Costos Calculados</h6>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-md-9">
                {{-- SELECTOR DE TIPO DE FORMULARIO --}}
                 <div class="row p-3">
                    <div class="border bg-light p-3 rounded-1">
                        <h6 class="mb-3">Tipo de Formulario:</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipoFormulario" id="interno" value="interno" @if(isset($tipo_formulario) && $tipo_formulario=='interno') checked @elseif(!isset($tipo_formulario)) checked @endif onchange="cambiarTipoFormulario()">
                                    <label class="form-check-label" for="interno">
                                        <strong>Interno</strong><br>
                                        <small class="text-muted">Puestos → Conceptos → Utilidad</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipoFormulario" id="externo" value="externo" @if(isset($tipo_formulario) && $tipo_formulario=='externo') checked @endif onchange="cambiarTipoFormulario()">
                                    <label class="form-check-label" for="externo">
                                        <strong>Externo</strong><br>
                                        <small class="text-muted">Solo Conceptos y Utilidad</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipoFormulario" id="mixto" value="mixto" @if(isset($tipo_formulario) && $tipo_formulario=='mixto') checked @endif onchange="cambiarTipoFormulario()">
                                    <label class="form-check-label" for="mixto">
                                        <strong>Mixto</strong><br>
                                        <small class="text-muted">Todo el formulario</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SECCIÓN PUESTOS (se muestra según el tipo) --}}
                <div class="row p-3" id="seccion-puestos">
                    <h5 class="mb-3">Gestión de Puestos</h5>
                    <div class="border bg-light p-3 rounded-1">
                        <div class="row">
                            <div class="col-md-8 col-6 form-outline">
                                <label class="form-label">Buscar Empleado</label>
                                <input class="form-control" type="text" name="buscar_empleado" id="buscar_empleado" placeholder="Buscar por nombre..." onkeyup="filtrarEmpleados()">
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="col-md-4 col-6 form-outline">
                                <label class="form-label">Salario</label>
                                <input class="form-control" type="number" name="salario" id="salario" placeholder="0" maxlength="10" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-12">
                                    <button class="btn btn-primary fs-8" type="button" onclick="mostrarModalEmpleados()">
                                        <i class="fa-solid fa-search"></i> Ver Empleados Disponibles
                                    </button>
                                    <button class="btn btn-baseColor fs-8" type="button" onclick="agregarPuesto()">
                                        <i class="fa-solid fa-plus"></i> Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal de Empleados -->
                <div class="modal fade" id="modalEmpleados" tabindex="-1" aria-labelledby="modalEmpleadosLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalEmpleadosLabel">Empleados Disponibles</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="tabla-empleados">
                        <thead>
                            <tr>
                                                <th>ID</th>
                                                <th>Nombre Completo</th>
                                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                                            @foreach($empleados_puestos as $empleado)
                                            <tr class="empleado-row">
                                                <td>{{ $empleado->idempleado }}</td>
                                <td>
                                                    {{ $empleado->primer_nombre }} 
                                                    {{ $empleado->segundo_nombre ? $empleado->segundo_nombre . ' ' : '' }}
                                                    {{ $empleado->apellido_paterno }} 
                                                    {{ $empleado->apellido_materno }}
                                </td>
                                                
                                                <td>
                                                    <button class="btn btn-success btn-sm" onclick="seleccionarEmpleado('{{ $empleado->idempleado }}', '{{ $empleado->primer_nombre }} {{ $empleado->segundo_nombre ? $empleado->segundo_nombre . ' ' : '' }}{{ $empleado->apellido_paterno }} {{ $empleado->apellido_materno }}', {{ $empleado->salario_fijo }})">
                                                        <i class="fa-solid fa-check"></i> Seleccionar
                                                    </button>
                                </td>
                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row p-3" id="tabla-puestos-container">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <td class="fw-bold">ID Empleado</td>
                                <td class="fw-bold">Nombre Completo</td>
                                <td class="fw-bold">Salario</td>
                                <td class="fw-bold">Acciones</td>
                            </tr>
                        </thead>

                        <tbody id="tabla-puestos">
                            <!-- Los puestos se agregarán dinámicamente aquí -->
                        </tbody>
                    </table>
                </div>

                {{-- CONCEPTOS --}}
                @if($tipo == "Proyecto")
                <div class="col-md-9">
                    <h4 class="text-center">CONCEPTOS POR PROYECTO</h4>
                        {{-- CONCEPTOS --}}
                         <div class="row p-3">
                        <div class="table-responsive">
                        <form action="/Captura/insertar_conceptosproyectos/{{$id}}" method="POST">
                  
                            @csrf
                            <table class="table table-bordered" id="dynamicTable">
                                <thead>
                                    <tr>
                                        <th>Concepto</th>
                                        <th>Descripción</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unitario</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="dynamicTableBody">
                                    @if($tipo == "Proyecto" && $conceptos_proyecto->count() > 0)
                                        @foreach($conceptos_proyecto as $concepto)
                                        <tr>
                                            <td><input type="text" name="concepto[]" class="form-control" value="{{ $concepto->nombre_concepto }}" required></td>
                                            <td><input type="text" name="descripcion[]" class="form-control" value="{{ $concepto->descripcion_concepto }}" required></td>
                                            <td><input type="number" name="cantidad[]" class="form-control" min="1" step="any" value="{{ $concepto->cantidad }}" required></td>
                                            <td><input type="number" name="precio_unitario[]" class="form-control" min="0" step="any" value="{{ $concepto->precio_unitario }}" required></td>
                                            <td><input type="number" name="total[]" class="form-control" min="0" step="any" value="{{ $concepto->costo_concepto }}" readonly></td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    @else
                                    <tr>
                                        <td><input type="text" name="concepto[]" class="form-control" required></td>
                                        <td><input type="text" name="descripcion[]" class="form-control" required></td>
                                        <td><input type="number" name="cantidad[]" class="form-control" min="1" step="any" required></td>
                                        <td><input type="number" name="precio_unitario[]" class="form-control" min="0" step="any" required></td>
                                        <td><input type="number" name="total[]" class="form-control" min="0" step="any" readonly></td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-success btn-sm" id="addColumnBtn"><i class="fa fa-plus"></i> Agregar Columna</button>
                        </div>
        
                </div>
                @else
                 <div class="row p-3" id="seccion-conceptos">
                    <h5 class="mb-3">Conceptos de Costos</h5>
                    
                    <!-- Botón para agregar conceptos -->
                    <div class="row mb-3">
                        <div class="col-12">
                            @if($tipo != "Proyecto")
                            <button class="btn btn-primary" type="button" onclick="mostrarModalConceptos()">
                                <i class="fa-solid fa-plus"></i> Agregar Concepto
                            </button>
                            <button class="btn btn-info d-none" type="button" onclick="mostrarResumenConceptos()">
                                <i class="fa-solid fa-list"></i> Ver Resumen
                            </button>
                            <button class="btn btn-warning" type="button" onclick="actualizarMontoManoObra()">
                                <i class="fa-solid fa-sync-alt"></i> Actualizar Mano de Obra
                            </button>
                            <button class="btn btn-success" type="button" onclick="actualizarMontoMateriales()">
                                <i class="fa-solid fa-tools"></i> Actualizar Materiales
                            </button>
                            <button class="btn btn-primary d-none" type="button" onclick="actualizarConceptosAutomaticos()">
                                <i class="fa-solid fa-sync"></i> Actualizar Ambos
                            </button>
                            <button class="btn btn-secondary d-none" type="button" onclick="verificarEstadoManoObra()">
                                <i class="fa-solid fa-search"></i> Verificar Mano de Obra
                            </button>
                            <button class="btn btn-dark d-none" type="button" onclick="verificarEstadoMateriales()">
                                <i class="fa-solid fa-search"></i> Verificar Materiales
                            </button>
                            <button class="btn btn-danger" type="button" onclick="limpiarDuplicados(); actualizarTablaConceptos();">
                                <i class="fa-solid fa-trash"></i> Limpiar Duplicados
                            </button>
                            @endif
                        </div>
                    </div>
                    
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <td class="fw-bold text-center">Incluir</td>
                                <td class="fw-bold">Concepto</td>
                                <td class="fw-bold">Monto</td>
                                <td class="fw-bold">Acciones</td>
                            </tr>
                        </thead>

                        <tbody id="tabla-conceptos">
                            <!-- Los conceptos se cargarán dinámicamente aquí -->
                        </tbody>
                    </table>
                </div>

                {{-- COSTO POR PROVEEDOR EXTERNO (solo para formulario mixto) --}}
                <div class="row p-3" id="seccion-proveedor-externo" style="display: none;">
                    <h5 class="mb-3">Costo por Proveedor Externo</h5>
                    <div class="border bg-light p-3 rounded-1">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-outline">
                                    <label class="form-label">Costo por Proveedor Externo</label>
                                    <input class="form-control" type="number" name="costo_proveedor_externo" id="costo_proveedor_externo" 
                                           placeholder="0.00" min="0" step="any" onchange="calcular()" value="{{ isset($costo_externo) ? $costo_externo : '' }}">
                                    <div class="form-text">Ingrese el costo adicional por proveedor externo</div>
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, ingrese un valor válido.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-end h-100">
                                    <button class="btn btn-info" type="button" onclick="actualizarCostoProveedorExterno()">
                                        <i class="fa-solid fa-calculator"></i> Actualizar Cálculos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Modal de Conceptos -->
                <div class="modal fade" id="modalConceptos" tabindex="-1" aria-labelledby="modalConceptosLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalConceptosLabel">Conceptos Disponibles</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <input type="text" class="form-control" id="filtroConceptos" placeholder="Buscar concepto..." onkeyup="filtrarConceptos()">
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="tabla-conceptos-disponibles">
                                        <thead>
                                            <tr>
                                                <th>Concepto</th>
                                                <th>Descripción</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($conceptos_servicios as $concepto)
                                            <tr class="concepto-row" data-id="{{ $concepto->id }}" data-nombre="{{ $concepto->nombre }}" data-descripcion="{{ $concepto->descripcion }}">
                                                <td>{{ $concepto->nombre }}</td>
                                                <td>{{ $concepto->descripcion }}</td>
                                                <td>
                                                    <button class="btn btn-success btn-sm" onclick="agregarConcepto('{{ $concepto->id }}', '{{ $concepto->nombre }}', '{{ $concepto->descripcion }}')">
                                                        <i class="fa-solid fa-plus"></i> Agregar
                                                    </button>
                                </td>
                            </tr>
                                            @endforeach
                        </tbody>
                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUBIDA DE ARCHIVOS DE INGENIERÍA --}}
                <div class="row p-3" id="seccion-ingenieria">
                    <h5 class="mb-3">Documentos de Ingeniería</h5>
                    <div class="border bg-light p-3 rounded-1">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-outline">
                                    <label class="form-label">Subir Documento de Ingeniería</label>
                                    <input class="form-control" type="file" name="archivo_ingenieria" id="archivo_ingenieria" 
                                           accept=".pdf,.jpg,.jpeg,.png" onchange="validarArchivoIngenieria()">
                                    <div class="form-text">Formatos permitidos: PDF, JPG, JPEG y PNG. Tamaño máximo: 500MB</div>
                                    <div class="valid-feedback">¡Archivo válido!</div>
                                    <div class="invalid-feedback">Por favor, seleccione un archivo válido.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-end h-100">
                                    <button class="btn btn-primary" type="button" onclick="subirArchivoIngenieria()" id="btn-subir-ingenieria" disabled>
                                        <i class="fa-solid fa-upload"></i> Subir Archivo
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Área de vista previa -->
                        <div class="row mt-3" id="vista-previa-ingenieria" style="display: none;">
                            <div class="col-12">
                                <div class="border rounded p-2">
                                    <h6 class="mb-2">Vista Previa:</h6>
                                    <div id="contenido-vista-previa"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Lista de archivos subidos -->
                        <div class="row mt-3" id="archivos-subidos-container" style="display: none;">
                            <div class="col-12">
                                <h6 class="mb-2">Archivos Subidos:</h6>
                                <div class="mb-2">
                                    <button class="btn btn-sm btn-info" type="button" onclick="verificarArchivoExistente()">
                                        <i class="fa-solid fa-search"></i> Verificar Archivo
                                    </button>
                                </div>
                                <div id="lista-archivos-subidos" class="border rounded p-2">
                                    <!-- Los archivos se mostrarán aquí dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PORCENTAJE UTLIDAD --}}
                <div class="row p-3" id="seccion-utilidad">
                    <h5 class="mb-3">Porcentaje de Utilidad</h5>
                    <div class="row justify-content-start mb-2">
                        <div class="col-3">
                            <input class="form-control" type="text" name="" id="porcentaje" placeholder="Porcentaje" required onchange="calcular()" value="{{ isset($utilidad_porcentaje) ? $utilidad_porcentaje : '' }}">
                        </div>
                        <button class="col-1 btn btn-primary fs-7 p-1" style="margin-top: -5px; padding:1px;" type="button" onclick="calcular()">Calcular</button>
                    </div>

                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <td class="fw-bold">Porcentaje de Utilidad</td>
                                <td class="fw-bold text-primary" id="porcentajetext">0%</td>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <th>Total</td>
                                <td id="total-costos">$ {{number_format(0,2)}}</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-muted small">
                                    <strong>Desglose:</strong><br>
                                    <span id="conceptos-dinamicos-resumen"></span>
                                </td>
                            </tr>
                            <tr>
                                 <th>Total Facturación</td>
                                <td id="total-facturacion">$ {{number_format(0,2)}}</td>
                            </tr>
                            <tr>
                                <td class="text-success fw-bold">Total Utilidad</td>
                                <td class="text-success fw-bold" id="total-utilidad">$ {{number_format(0,2)}}</td>
                            </tr>
                            <tr>
                                 <td colspan="2"></td>
                            </tr>
                            <tr>
                                <th class="fst-italic">Iva Integrado</td>
                                <td class="fst-italic" id="iva-integrado">$ {{number_format(0,2)}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end p-3">
                     @if(!is_null($existe_cotizacion_integracion))
                        <div class="col-auto p-1">
                            <a href="/Servicios/Ver/Inicio/{{$id}}/{{$tipo}}" class="btn btn-success fs-7 d-flex align-items-center" style="min-width: 180px; height: 38px;">
                                <i class="fa-solid fa-file-excel me-2"></i> Plantilla de Cotizaciones
                            </a>
                        </div>
                    @endif
                    <div class="col-auto p-1">
                        <button class="btn btn-baseColor fs-7 d-flex align-items-center" style="min-width: 150px; height: 38px;" type="button" onclick="guardarYContinuar()">
                            <i class="fa-solid fa-check me-2"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
                            </div>

                     
                    
                        
                    

                    
                    
                                    
           


                      <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Calcular total al cambiar cantidad o precio
                            function updateRowTotal(row) {
                                let cantidad = parseFloat(row.querySelector('input[name="cantidad[]"]').value) || 0;
                                let precio = parseFloat(row.querySelector('input[name="precio_unitario[]"]').value) || 0;
                                row.querySelector('input[name="total[]"]').value = (cantidad * precio).toFixed(2);
                            }
    
                            // Delegación de eventos para inputs
                            document.getElementById('dynamicTableBody').addEventListener('input', function(e) {
                                if (e.target.name === "cantidad[]" || e.target.name === "precio_unitario[]") {
                                    updateRowTotal(e.target.closest('tr'));
                                }
                            });
    
                            // Eliminar fila
                            document.getElementById('dynamicTableBody').addEventListener('click', function(e) {
                                if (e.target.classList.contains('removeRow') || e.target.closest('.removeRow')) {
                                    let btn = e.target.classList.contains('removeRow') ? e.target : e.target.closest('.removeRow');
                                    let row = btn.closest('tr');
                                    if (document.querySelectorAll('#dynamicTableBody tr').length > 1) {
                                        row.remove();
                                        // Recalcular totales después de eliminar
                                        calcularTotalesProyecto();
                                    }
                                }
                            });
    
                            // Agregar columna (fila)
                            document.getElementById('addColumnBtn').addEventListener('click', function() {
                                let tbody = document.getElementById('dynamicTableBody');
                                let newRow = tbody.rows[0].cloneNode(true);
                                // Limpiar los valores de los inputs
                                Array.from(newRow.querySelectorAll('input')).forEach(function(input) {
                                    if (input.type === 'number' || input.type === 'text') {
                                        input.value = '';
                                    }
                                    if (input.name === 'total[]') {
                                        input.value = '0.00';
                                    }
                                });
                                tbody.appendChild(newRow);
                                // Recalcular totales después de agregar
                                calcularTotalesProyecto();
                            });
                            
                            // Función para calcular totales de proyecto
                            function calcularTotalesProyecto() {
                                let totalCostos = 0;
                                
                                // Sumar todos los totales de la tabla dinámica
                                document.querySelectorAll('#dynamicTableBody input[name="total[]"]').forEach(function(input) {
                                    totalCostos += parseFloat(input.value) || 0;
                                });
                                
                                // Obtener el porcentaje de utilidad
                                const porcentaje = parseFloat(document.getElementById('porcentaje').value) || 0;
                                
                                // Calcular ganancia
                                const ganancia = totalCostos * (porcentaje / 100);
                                
                                // Calcular total de facturación
                                const totalFacturacion = totalCostos + ganancia;
                                
                                // Calcular IVA integrado (16%)
                                const ivaIntegrado = totalFacturacion * 0.16;
                                
                                // Actualizar la tabla de porcentaje de utilidad
                                document.getElementById('porcentajetext').textContent = porcentaje + " %";
                                document.getElementById('total-costos').textContent = "$ " + totalCostos.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                document.getElementById('total-facturacion').textContent = "$ " + totalFacturacion.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                document.getElementById('total-utilidad').textContent = "$ " + ganancia.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                document.getElementById('iva-integrado').textContent = "$ " + ivaIntegrado.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                
                                // Actualizar desglose con los conceptos de la tabla dinámica
                                let desgloseHTML = '';
                                document.querySelectorAll('#dynamicTableBody tr').forEach(function(row, index) {
                                    const concepto = row.querySelector('input[name="concepto[]"]').value;
                                    const cantidad = parseFloat(row.querySelector('input[name="cantidad[]"]').value) || 0;
                                    const precio = parseFloat(row.querySelector('input[name="precio_unitario[]"]').value) || 0;
                                    const total = parseFloat(row.querySelector('input[name="total[]"]').value) || 0;
                                    
                                    if (concepto && total > 0) {
                                        desgloseHTML += `<span class="text-success">• ${concepto}: $ ${total.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span><br>`;
                                    }
                                });
                                
                                document.getElementById('conceptos-dinamicos-resumen').innerHTML = desgloseHTML;
                            }
                            
                            // Inicializar cálculos si hay datos existentes
                            @if($tipo == "Proyecto" && $conceptos_proyecto->count() > 0)
                            // Calcular totales de todas las filas existentes
                            document.querySelectorAll('#dynamicTableBody tr').forEach(function(row) {
                                updateRowTotal(row);
                            });
                            // Calcular totales generales
                            calcularTotalesProyecto();
                            @endif
                        });
                    </script>
    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
    <script>
        function calcular(){
            porcetaje = $("#porcentaje").val();
            document.getElementById('porcentajetext').textContent = porcetaje+" %";
            
            // Usar la función unificada que maneja tanto conceptos predefinidos como dinámicos
            calcularTotales();
        }
    </script>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/table.js') }}"></script>
<script>
    // Array para almacenar los puestos
    let puestos = [];
    let empleadoSeleccionado = null;
    let conceptosAgregados = []; // Array para conceptos adicionales
    const servicioId = {{ $id }};
    
    // Cargar empleados guardados al iniciar
    @if($empleados_servicio)
        @foreach($empleados_servicio as $emp)
            puestos.push({
                id: '{{ $emp->id_empleado }}',
                nombre: '{{ $emp->nombre_completo }}',
                salario_bruto: {{ $emp->sueldo_semanal }}
            });
        @endforeach
    @endif
    
    // Cargar conceptos guardados al iniciar
    @if($conceptos_servicio)
        @foreach($conceptos_servicio as $concepto)
            conceptosAgregados.push({
                id: '{{ $concepto->id_concepto }}',
                nombre: '{{ $concepto->nombre }}',
                descripcion: '{{ $concepto->descripcion }}',
                monto: {{ $concepto->monto }},
                habilitado: {{ isset($concepto->estado) ? ($concepto->estado == 1 ? 'true' : 'false') : 'true' }} // Refleja el estado real
            });
        @endforeach
    @endif
    
    function mostrarModalEmpleados() {
        const modal = new bootstrap.Modal(document.getElementById('modalEmpleados'));
        modal.show();
    }
    
    function filtrarEmpleados() {
        const filtro = document.getElementById('buscar_empleado').value.toLowerCase();
        const filas = document.querySelectorAll('.empleado-row');
        
        filas.forEach(fila => {
            const nombre = fila.querySelector('td:nth-child(2)').textContent.toLowerCase();
            if (nombre.includes(filtro)) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }
    
    function seleccionarEmpleado(id, nombre, salario) {
        empleadoSeleccionado = {
            id: id,
            nombre: nombre,
            salario_bruto: salario
        };
        
        document.getElementById('buscar_empleado').value = nombre;
        document.getElementById('salario').value = salario;
        
        // Cerrar el modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEmpleados'));
        modal.hide();
        
        // Enfocar en el campo de salario
        document.getElementById('salario').focus();
    }
    
    function cambiarTipoFormulario() {
        const tipoSeleccionado = document.querySelector('input[name="tipoFormulario"]:checked').value;
        const seccionPuestos = document.getElementById('seccion-puestos');
        const tablaPuestosContainer = document.getElementById('tabla-puestos-container');
        const seccionConceptos = document.getElementById('seccion-conceptos');
        const seccionIngenieria = document.getElementById('seccion-ingenieria');
        const seccionUtilidad = document.getElementById('seccion-utilidad');
        const seccionProveedorExterno = document.getElementById('seccion-proveedor-externo');
        
        // Ocultar todas las secciones primero
        seccionPuestos.style.display = 'none';
        tablaPuestosContainer.style.display = 'none';
        seccionConceptos.style.display = 'none';
        seccionIngenieria.style.display = 'none';
        seccionUtilidad.style.display = 'none';
        seccionProveedorExterno.style.display = 'none';
        
        // Mostrar secciones según el tipo seleccionado
        switch(tipoSeleccionado) {
            case 'interno':
                // Orden: Puestos → Conceptos → Ingeniería → Utilidad
                seccionPuestos.style.display = 'block';
                tablaPuestosContainer.style.display = 'block';
                seccionConceptos.style.display = 'block';
                seccionIngenieria.style.display = 'block';
                seccionUtilidad.style.display = 'block';
                
                // Reordenar elementos
                seccionPuestos.parentNode.insertBefore(seccionPuestos, seccionConceptos);
                tablaPuestosContainer.parentNode.insertBefore(tablaPuestosContainer, seccionConceptos);
                break;
                
            case 'externo':
                // Solo: Conceptos → Ingeniería → Utilidad
                seccionConceptos.style.display = 'block';
                seccionIngenieria.style.display = 'block';
                seccionUtilidad.style.display = 'block';
                break;
                
            case 'mixto':
                // Todo el formulario en orden original + Proveedor Externo
                seccionPuestos.style.display = 'block';
                tablaPuestosContainer.style.display = 'block';
                seccionConceptos.style.display = 'block';
                seccionProveedorExterno.style.display = 'block';
                seccionIngenieria.style.display = 'block';
                seccionUtilidad.style.display = 'block';
                break;
        }
    }
    
    function agregarPuesto() {
        const salario = document.getElementById('salario').value.trim();
        
        // Validar que se haya seleccionado un empleado
        if (!empleadoSeleccionado) {
            alert('Por favor, seleccione un empleado primero');
            return;
        }
        
        // Validar que la cantidad esté llena
        if (!salario) {
            alert('Por favor, complete el salario');
            return;
        }
        
        // Validar que la cantidad sea un número válido
        if (isNaN(salario) || salario <= 0) {
            alert('Por favor, ingrese un salario válido mayor a 0');
            return;
        }
        
        // Verificar si el empleado ya existe
        const empleadoExistente = puestos.find(p => p.id === empleadoSeleccionado.id);
        if (empleadoExistente) {
            alert('Este empleado ya ha sido agregado');
            return;
        }
        
        // Guardar en la base de datos
        guardarEmpleadoEnBD(empleadoSeleccionado.id, empleadoSeleccionado.nombre, salario);
    }
    
    function guardarEmpleadoEnBD(idEmpleado, nombreCompleto, salario) {
        const formData = new FormData();
        formData.append('id_servicio_enc', servicioId);
        formData.append('id_empleado', idEmpleado);
        formData.append('nombre_completo', nombreCompleto);
        formData.append('sueldo_semanal', salario);
        formData.append('_token', '{{ csrf_token() }}');
        
        fetch('/Servicios/Captura/GuardarEmpleado', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Agregar el empleado al array local
                puestos.push({
                    id: idEmpleado,
                    nombre: nombreCompleto,
                    salario_bruto: parseFloat(salario)
                });
                
                // Actualizar la tabla
                actualizarTablaPuestos();
                
                // Limpiar los campos
                document.getElementById('buscar_empleado').value = '';
                document.getElementById('salario').value = '';
                empleadoSeleccionado = null;
                document.getElementById('buscar_empleado').focus();
                
                // Mostrar mensaje de éxito
                alert('Empleado agregado correctamente');
            } else {
                alert(data.message || 'Error al agregar empleado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar empleado');
        });
    }
    
    function eliminarPuesto(id) {
        // Eliminar de la base de datos
        eliminarEmpleadoDeBD(id);
    }
    
    function eliminarEmpleadoDeBD(idEmpleado) {
        const formData = new FormData();
        formData.append('id_servicio_enc', servicioId);
        formData.append('id_empleado', idEmpleado);
        formData.append('_token', '{{ csrf_token() }}');
        
        fetch('/Servicios/Captura/EliminarEmpleado', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Encontrar y eliminar el puesto del array
                puestos = puestos.filter(p => p.id !== idEmpleado);
                
                // Actualizar la tabla
                actualizarTablaPuestos();
                
                // Mostrar mensaje de éxito
                alert('Empleado eliminado correctamente');
            } else {
                alert(data.message || 'Error al eliminar empleado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar empleado');
        });
    }
    
    function actualizarTotalManoObra() {
        // Actualizar todos los conceptos principales
        actualizarConceptosPrincipales();
    }
    
    function actualizarTablaPuestos() {
        const tbody = document.getElementById('tabla-puestos');
        tbody.innerHTML = '';
        
        puestos.forEach(puesto => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${puesto.id}</td>
                <td>${puesto.nombre}</td>
                <td>$ ${puesto.salario_bruto.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="eliminarPuesto('${puesto.id}')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    function mostrarModalConceptos() {
        const modal = new bootstrap.Modal(document.getElementById('modalConceptos'));
        modal.show();
    }
    
    function filtrarConceptos() {
        const filtro = document.getElementById('filtroConceptos').value.toLowerCase();
        const filas = document.querySelectorAll('.concepto-row');
        
        filas.forEach(fila => {
            const nombre = fila.querySelector('td:nth-child(1)').textContent.toLowerCase();
            if (nombre.includes(filtro)) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    }
    
    function verificarDuplicados(nombre, id) {
        // Verificar conceptos dinámicos por ID
        const conceptoExistentePorId = conceptosAgregados.find(c => c.id === id);
        if (conceptoExistentePorId) {
            return {
                esDuplicado: true,
                mensaje: 'Este concepto ya ha sido agregado'
            };
        }
        
        // Verificar conceptos dinámicos por nombre
        const conceptoExistentePorNombre = conceptosAgregados.find(c => c.nombre === nombre);
        if (conceptoExistentePorNombre) {
            return {
                esDuplicado: true,
                mensaje: 'Ya existe un concepto con el mismo nombre'
            };
        }
        
        return {
            esDuplicado: false,
            mensaje: ''
        };
    }
    
    function agregarConcepto(id, nombre, descripcion) {
        // Verificar duplicados
        const validacion = verificarDuplicados(nombre, id);
        if (validacion.esDuplicado) {
            alert(`No se puede agregar el concepto: ${validacion.mensaje}`);
            return;
        }
        // Por defecto, el checkbox estará habilitado al agregar
        guardarConceptoEnBD(id, nombre, descripcion, 0, true);
    }
    
    function guardarConceptoEnBD(idConcepto, nombre, descripcion, monto, habilitado) {
        const formData = new FormData();
        formData.append('id_servicio_enc', servicioId);
        formData.append('id_concepto', idConcepto);
        formData.append('monto', monto);
        formData.append('estado', habilitado ? 1 : 0);
        formData.append('_token', '{{ csrf_token() }}');
        
        fetch('/Servicios/Captura/GuardarConcepto', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Agregar el concepto al array local
                conceptosAgregados.push({
                    id: idConcepto,
                    nombre: nombre,
                    descripcion: descripcion,
                    monto: parseFloat(monto),
                    habilitado: !!habilitado
                });
                // Actualizar la tabla
                actualizarTablaConceptos();
                // Cerrar el modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalConceptos'));
                modal.hide();
                // Mostrar mensaje de éxito
                alert('Concepto agregado correctamente');
            } else {
                alert(data.message || 'Error al agregar concepto');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar concepto');
        });
    }
    
    function eliminarConcepto(id) {
        // Encontrar el concepto para mostrar su nombre en la confirmación
        const concepto = conceptosAgregados.find(c => c.id === id);
        if (!concepto) {
            alert('Error: No se pudo encontrar el concepto a eliminar');
            return;
        }
        
        // Mostrar confirmación con el nombre del concepto
        const confirmacion = confirm(`¿Está seguro de que desea eliminar el concepto "${concepto.nombre}"?\n\nEsta acción no se puede deshacer.`);
        
        if (confirmacion) {
            // Eliminar de la base de datos
            eliminarConceptoDeBD(id);
        }
    }
    
    function eliminarConceptoDeBD(idConcepto) {
        // Validar que el ID del concepto sea válido
        if (!idConcepto || idConcepto <= 0) {
            alert('Error: ID de concepto inválido');
            return;
        }
        
        const formData = new FormData();
        formData.append('id_servicio_enc', servicioId);
        formData.append('id_concepto', idConcepto);
        formData.append('_token', '{{ csrf_token() }}');
        
        fetch('/Servicios/Captura/EliminarConcepto', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Encontrar y eliminar el concepto del array
                conceptosAgregados = conceptosAgregados.filter(c => c.id !== idConcepto);
                
                // Actualizar la tabla
                actualizarTablaConceptos();
                
                // Recalcular totales
                calcularTotales();
                
                // Mostrar mensaje de éxito
                alert('Concepto eliminado correctamente');
            } else {
                alert(data.message || 'Error al eliminar concepto');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al eliminar concepto. Por favor, intente nuevamente.');
        });
    }
    
    function actualizarConceptoMonto(id, monto) {
        const concepto = conceptosAgregados.find(c => c.id === id);
        if (concepto) {
            // Para todos los conceptos, usar el valor ingresado
            concepto.monto = parseFloat(monto) || 0;
            
            // Actualizar en la base de datos
            actualizarConceptoEnBD(id, monto);
            
            // Recalcular totales
            calcularTotales();
        }
    }
    
    function actualizarConceptoEnBD(idConcepto, monto) {
        const formData = new FormData();
        formData.append('id_servicio_enc', servicioId);
        formData.append('id_concepto', idConcepto);
        formData.append('monto', monto);
        formData.append('_token', '{{ csrf_token() }}');
        
        fetch('/Servicios/Captura/ActualizarConcepto', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Concepto actualizado correctamente en BD:', idConcepto, monto);
            } else {
                console.error('Error al actualizar concepto en BD:', data.message);
            }
        })
        .catch(error => {
            console.error('Error de conexión al actualizar concepto:', error);
        });
    }
    
    function actualizarTablaConceptos() {
        const tbody = document.getElementById('tabla-conceptos');
        tbody.innerHTML = '';
        
        // Agregar todos los conceptos
        conceptosAgregados.forEach(concepto => {
            const row = document.createElement('tr');
            row.setAttribute('data-concepto-id', concepto.id);
            
            row.innerHTML = `
                <td class="text-center">
                    <input type="checkbox" 
                           class="form-check-input concepto-checkbox" 
                           id="checkbox_${concepto.id}"
                           ${concepto.habilitado ? 'checked' : ''}
                           onchange="toggleConcepto('${concepto.id}', this.checked)">
                </td>
                <td>${concepto.nombre}</td>
                <td>
                    <input type="number" step="any" class="form-control concepto-monto" 
                           name="concepto_${concepto.id}" id="concepto_${concepto.id}" 
                           value="${concepto.monto}" required 
                           onchange="actualizarConceptoMonto('${concepto.id}', this.value)"
                           ${!concepto.habilitado ? 'disabled' : ''}>
                </td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="eliminarConcepto('${concepto.id}')">
                        <i class="fa-solid fa-trash"></i> Eliminar
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    function calcularTotales() {
        // Calcular el total de TODOS los conceptos (habilitados y deshabilitados)
        let totalCostosTodos = 0;
        let totalCostosHabilitados = 0;
        let materialesSuministroIndex = -1;
        let valorMaterialesSuministro = 0;

        // Buscar el input de materiales y suministro en la tabla de conceptos y tomar su valor directamente
        document.querySelectorAll('#tabla-conceptos tr').forEach(function(row, idx) {
            const nombre = row.querySelector('td:nth-child(2)') ? row.querySelector('td:nth-child(2)').textContent.trim().toUpperCase() : '';
            if (nombre === 'MATERIALES Y SUMINISTRO') {
                const inputMonto = row.querySelector('input.concepto-monto');
                if (inputMonto) {
                    valorMaterialesSuministro = parseFloat(inputMonto.value) || 0;
                    materialesSuministroIndex = idx;
                }
            }
        });

        // Sumar conceptos, pero para materiales y suministro usar solo el valor del input
        conceptosAgregados.forEach((concepto, idx) => {
            let monto = parseFloat(concepto.monto) || 0;
            if (idx === materialesSuministroIndex) {
                monto = valorMaterialesSuministro;
            }
            totalCostosTodos += monto;
            if (concepto.habilitado) {
                totalCostosHabilitados += monto;
            }
        });

        // Sumar también los conceptos de la tabla dinámica si existe
        let totalConceptosDinamicos = 0;
        if (document.getElementById('dynamicTableBody')) {
            document.querySelectorAll('#dynamicTableBody input[name="total[]"]').forEach(function(input) {
                totalConceptosDinamicos += parseFloat(input.value) || 0;
            });
        }

        // Total de costos: TODOS los conceptos (habilitados y deshabilitados) + conceptos dinámicos
        const totalCostosCombinados = totalCostosTodos + totalConceptosDinamicos;
        // Total de utilidad: SOLO conceptos habilitados + conceptos dinámicos
        const totalCostosUtilidad = totalCostosHabilitados + totalConceptosDinamicos;

        const porcentaje = parseFloat(document.getElementById('porcentaje').value) || 0;

        // Calcular ganancia basada en el total de utilidad
        const ganancia = totalCostosUtilidad * (porcentaje / 100);

        // Calcular total de facturación como Total + Utilidad
        const totalFacturacion = totalCostosCombinados + ganancia;

        // Calcular IVA integrado (16% del total de facturación)
        const ivaIntegrado = totalFacturacion * 0.16;

        // Actualizar la tabla de porcentaje de utilidad
        document.getElementById('porcentajetext').textContent = porcentaje + " %";
        document.getElementById('total-costos').textContent = "$ " + totalCostosCombinados.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('total-facturacion').textContent = "$ " + totalFacturacion.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('total-utilidad').textContent = "$ " + ganancia.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('iva-integrado').textContent = "$ " + ivaIntegrado.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        // Actualizar desglose con todos los conceptos (predefinidos, dinámicos y proveedor externo)
        let desgloseHTML = '';

        // Agregar conceptos predefinidos (todos, habilitados y deshabilitados)
        conceptosAgregados.forEach((concepto, idx) => {
            let monto = parseFloat(concepto.monto) || 0;
            if (idx === materialesSuministroIndex) {
                monto = valorMaterialesSuministro;
            }
            if (monto > 0) {
                const estadoClase = concepto.habilitado ? 'text-success' : 'text-muted';
                const estadoIcono = concepto.habilitado ? '✓' : '✗';
                const estadoTexto = concepto.habilitado ? ' (Incluido)' : ' (Excluido)';
                desgloseHTML += `<span class=\"${estadoClase}\">• ${concepto.nombre}: $ ${monto.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})} ${estadoIcono}${estadoTexto}</span><br>`;
            }
        });

        // Agregar conceptos dinámicos
        if (document.getElementById('dynamicTableBody')) {
            document.querySelectorAll('#dynamicTableBody tr').forEach(function(row, index) {
                const concepto = row.querySelector('input[name="concepto[]"]').value;
                const total = parseFloat(row.querySelector('input[name="total[]"]').value) || 0;
                if (concepto && total > 0) {
                    desgloseHTML += `<span class=\"text-success\">• ${concepto}: $ ${total.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})} ✓ (Dinámico)</span><br>`;
                }
            });
        }

        document.getElementById('conceptos-dinamicos-resumen').innerHTML = desgloseHTML;
    }
    
    function actualizarCostoProveedorExterno() {
        // Esta función se puede usar para validaciones adicionales o cálculos específicos
        // Por ahora, simplemente recalcula los totales
        calcularTotales();
        
        // Mostrar mensaje informativo
        const costoProveedor = parseFloat(document.getElementById('costo_proveedor_externo').value) || 0;
        if (costoProveedor > 0) {
            alert(`Costo por Proveedor Externo actualizado: $${costoProveedor.toFixed(2)}\n\nLos cálculos han sido actualizados automáticamente.`);
        } else {
            alert('Por favor, ingrese un valor válido para el costo del proveedor externo.');
        }
    }
    
    function calcular() {
        calcularTotales();
    }
    
    function guardarYContinuar() {
        // Verificar si es tipo proyecto
        const esProyecto = {{ $tipo == "Proyecto" ? 'true' : 'false' }};
        const esIntegracion = {{ $tipo == "Integración" ? 'true' : 'false' }};
        
        // Obtener el valor del radio button de tipo de formulario
        const tipoFormulario = document.querySelector('input[name="tipoFormulario"]:checked') ? document.querySelector('input[name="tipoFormulario"]:checked').value : '';
        
        if (esProyecto) {
            // Guardar conceptos de proyecto primero
            guardarConceptosProyecto();
            return;
        }

        // Validar formulario de conceptos antes de continuar
        if (!validarFormularioConceptos()) {
            return;
        }
        
        // Obtener los valores actuales de los totales
        const totalFacturacion = parseFloat(document.getElementById('total-facturacion').textContent.replace('$ ', '').replace(/,/g, '')) || 0;
        const totalCostos = parseFloat(document.getElementById('total-costos').textContent.replace('$ ', '').replace(/,/g, '')) || 0;
        const totalUtilidad = parseFloat(document.getElementById('total-utilidad').textContent.replace('$ ', '').replace(/,/g, '')) || 0;
        const ivaIntegrado = parseFloat(document.getElementById('iva-integrado').textContent.replace('$ ', '').replace(/,/g, '')) || 0;
        const porcentajeUtilidad = parseFloat(document.getElementById('porcentaje').value) || 0;
        const montoUtilidad = totalUtilidad;
        
        // Obtener el nombre del servicio desde el input
        const nombreInput = document.querySelector('input[value*="{{$key->nombre}}"]');
        const nombreServicio = nombreInput ? nombreInput.value : '{{$key->nombre}}';
        
        // Verificar que los valores se obtuvieron correctamente
        console.log('Valores a guardar:', {
            nombre: nombreServicio,
            total_facturacion: totalFacturacion,
            total_costos: totalCostos,
            total_utilidad: totalUtilidad,
            iva_integrado: ivaIntegrado,
            utilidad_porcentaje: porcentajeUtilidad,
            tipo_formulario: tipoFormulario,
            monto_utilidad: montoUtilidad
        });
        
        // Crear FormData para enviar los datos
        const formData = new FormData();
        formData.append('id_servicio_enc', servicioId);
        formData.append('nombre', nombreServicio);
        formData.append('total_facturacion', totalFacturacion);
        formData.append('total_costos', totalCostos);
        formData.append('total_utilidad', totalUtilidad);
        formData.append('iva_integrado', ivaIntegrado);
        formData.append('utilidad_porcentaje', porcentajeUtilidad);
        formData.append('tipo_formulario', tipoFormulario);
        // Agregar costo por proveedor externo
        const costoProveedorExterno = document.getElementById('costo_proveedor_externo') ? document.getElementById('costo_proveedor_externo').value : '';
        formData.append('costo_proveedor_externo', costoProveedorExterno);
        formData.append('monto_utilidad', montoUtilidad);
        formData.append('_token', '{{ csrf_token() }}');
        
        // Enviar datos al servidor
        fetch('/Servicios/Captura/GuardarServicioDet', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                alert('Información guardada correctamente');
                // Redirigir a la página de servicios
                window.location.href = '/Servicios';
            } else {
                alert(data.message || 'Error al guardar información');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar información');
        });
    }
    
    function guardarConceptosProyecto() {
        // Obtener todos los datos de la tabla dinámica
        const conceptos = [];
        const descripciones = [];
        const cantidades = [];
        const precios_unitarios = [];
        const totales = [];
        
        document.querySelectorAll('#dynamicTableBody tr').forEach(function(row) {
            const concepto = row.querySelector('input[name="concepto[]"]').value.trim();
            const descripcion = row.querySelector('input[name="descripcion[]"]').value.trim();
            const cantidad = row.querySelector('input[name="cantidad[]"]').value;
            const precio_unitario = row.querySelector('input[name="precio_unitario[]"]').value;
            const total = row.querySelector('input[name="total[]"]').value;
            
            if (concepto && total > 0) {
                conceptos.push(concepto);
                descripciones.push(descripcion);
                cantidades.push(cantidad);
                precios_unitarios.push(precio_unitario);
                totales.push(total);
            }
        });
        
        if (conceptos.length === 0) {
            alert('Debe agregar al menos un concepto antes de continuar');
            return;
        }
        
        // Crear FormData para enviar los datos
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        
        conceptos.forEach((concepto, index) => {
            formData.append('concepto[]', concepto);
            formData.append('descripcion[]', descripciones[index]);
            formData.append('cantidad[]', cantidades[index]);
            formData.append('precio_unitario[]', precios_unitarios[index]);
            formData.append('total[]', totales[index]);
        });
        
        // Enviar datos al servidor
        fetch('/Servicios/Captura/GuardarConceptosProyectos/{{ $id }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                alert('Conceptos de proyecto guardados correctamente');
                // Redirigir a la página de servicios
                window.location.href = '/Servicios';
            } else {
                alert(data.message || 'Error al guardar conceptos de proyecto');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar conceptos de proyecto');
        });
    }
    
    function mostrarResumenConceptos() {
        const totalConceptos = conceptosAgregados.length;
        
        let mensaje = `Resumen de Conceptos:\n\n`;
        mensaje += `• Total de Conceptos: ${totalConceptos}\n\n`;
        
        if (totalConceptos > 0) {
            mensaje += `Conceptos Agregados:\n`;
            conceptosAgregados.forEach((concepto, index) => {
                mensaje += `${index + 1}. ${concepto.nombre} - $${parseFloat(concepto.monto).toFixed(2)}\n`;
            });
        } else {
            mensaje += `No hay conceptos agregados.`;
        }
        
        alert(mensaje);
    }
    
    function validarFormularioConceptos() {
        // Verificar que al menos un concepto tenga un monto mayor a 0
        const totalConceptos = conceptosAgregados.reduce((total, concepto) => {
            return total + (parseFloat(concepto.monto) || 0);
        }, 0);
        
        if (totalConceptos <= 0) {
            alert('Advertencia: El total de todos los conceptos es $0.00. Considere agregar montos a los conceptos.');
            return false;
        }
        
        return true;
    }
    
    function limpiarDuplicados() {
        const conceptosUnicos = [];
        const idsVistos = new Set();
        const nombresVistos = new Set();
        
        conceptosAgregados.forEach(concepto => {
            const esDuplicado = idsVistos.has(concepto.id) || nombresVistos.has(concepto.nombre);
            
            if (!esDuplicado) {
                conceptosUnicos.push(concepto);
                idsVistos.add(concepto.id);
                nombresVistos.add(concepto.nombre);
            } else {
                console.log('Eliminando duplicado:', concepto.nombre, concepto.id);
            }
        });
        
        conceptosAgregados = conceptosUnicos;
        console.log('Conceptos después de limpiar duplicados:', conceptosAgregados.length);
    }
    
    function actualizarMontoManoObra() {
        // Calcular el total de mano de obra desde los puestos
        const totalManoObra = puestos.reduce((total, puesto) => {
            return total + parseFloat(puesto.salario_bruto);
        }, 0);
        
        // Buscar el concepto MANO DE OBRA
        const conceptoManoObra = conceptosAgregados.find(c => c.nombre === 'MANO DE OBRA');
        
        if (conceptoManoObra) {
            const montoAnterior = conceptoManoObra.monto;
            conceptoManoObra.monto = totalManoObra;
            
            // Actualizar en BD
            actualizarConceptoEnBD(conceptoManoObra.id, totalManoObra);
            
            // Mostrar mensaje informativo
            alert(`Monto de MANO DE OBRA actualizado:\n\nValor anterior: $${montoAnterior.toFixed(2)}\nValor nuevo: $${totalManoObra.toFixed(2)}\n\nCalculado desde ${puestos.length} puestos.`);
            
            // Actualizar la tabla y recalcular totales
            actualizarTablaConceptos();
            calcularTotales();
        } else {
            console.log('No se encontró el concepto MANO DE OBRA');
            alert('No se encontró el concepto MANO DE OBRA en la lista de conceptos.\n\nPara que funcione la actualización, agregue el concepto "MANO DE OBRA" desde la lista de conceptos disponibles.');
        }
    }
    
    function verificarEstadoManoObra() {
        const conceptoManoObra = conceptosAgregados.find(c => c.nombre === 'MANO DE OBRA');
        const totalPuestos = puestos.reduce((total, puesto) => {
            return total + parseFloat(puesto.salario_bruto);
        }, 0);
        
        let mensaje = '=== ESTADO DEL CONCEPTO MANO DE OBRA ===\n\n';
        
        if (conceptoManoObra) {
            mensaje += `✓ Concepto MANO DE OBRA encontrado\n`;
            mensaje += `  ID: ${conceptoManoObra.id}\n`;
            mensaje += `  Monto actual: $${parseFloat(conceptoManoObra.monto).toFixed(2)}\n`;
            mensaje += `  Total de puestos: ${puestos.length}\n`;
            mensaje += `  Suma de salarios: $${totalPuestos.toFixed(2)}\n\n`;
            
            if (parseFloat(conceptoManoObra.monto) === totalPuestos) {
                mensaje += `✓ Estado: Sincronizado con los puestos`;
            } else {
                mensaje += `✗ Estado: Desincronizado con los puestos\n`;
                mensaje += `  Diferencia: $${Math.abs(parseFloat(conceptoManoObra.monto) - totalPuestos).toFixed(2)}\n\n`;
                mensaje += `Puede editar manualmente el valor o usar el botón "Actualizar Mano de Obra" para sincronizar con los puestos.`;
            }
        } else {
            mensaje += `✗ Concepto MANO DE OBRA no encontrado\n`;
            mensaje += `  Total de puestos: ${puestos.length}\n`;
            mensaje += `  Suma de salarios: $${totalPuestos.toFixed(2)}\n\n`;
            mensaje += `Para agregar el concepto, use la lista de conceptos disponibles. Una vez agregado, podrá editarlo manualmente.`;
        }
        
        console.log(mensaje);
        alert(mensaje);
    }
    
    function actualizarMontoMateriales() {
        // Obtener el valor del input de Costo en Suministros
        const inputCostoSuministros = document.querySelector('input[value*="{{$costo_sumisnitros}}"]');
        let valorMateriales = 0;
        
        if (inputCostoSuministros) {
            // Si existe el input, usar su valor
            valorMateriales = parseFloat(inputCostoSuministros.value.replace(/[^0-9.-]+/g, '')) || 0;
        } else {
            // Si no existe el input, usar la variable PHP
            valorMateriales = {{ $costo_sumisnitros ?? 0 }};
        }
        
        // Buscar el concepto MATERIALES Y SUMINISTRO
        const conceptoMateriales = conceptosAgregados.find(c => c.nombre === 'MATERIALES Y SUMINISTRO');
        
        if (conceptoMateriales) {
            const montoAnterior = conceptoMateriales.monto;
            conceptoMateriales.monto = valorMateriales;
            
            // Actualizar en BD
            actualizarConceptoEnBD(conceptoMateriales.id, valorMateriales);
            
            // Mostrar mensaje informativo
            alert(`Monto de MATERIALES Y SUMINISTRO actualizado:\n\nValor anterior: $${montoAnterior.toFixed(2)}\nValor nuevo: $${valorMateriales.toFixed(2)}\n\nValor obtenido del costo de suministros.`);
            
            // Actualizar la tabla y recalcular totales
            actualizarTablaConceptos();
            calcularTotales();
        } else {
            console.log('No se encontró el concepto MATERIALES Y SUMINISTRO');
            alert('No se encontró el concepto MATERIALES Y SUMINISTRO en la lista de conceptos.\n\nPara que funcione la actualización, agregue el concepto "MATERIALES Y SUMINISTRO" desde la lista de conceptos disponibles.');
        }
    }
    
    function verificarEstadoMateriales() {
        // Obtener el valor del input de Costo en Suministros
        const inputCostoSuministros = document.querySelector('input[value*="{{$costo_sumisnitros}}"]');
        let valorMateriales = 0;
        
        if (inputCostoSuministros) {
            // Si existe el input, usar su valor
            valorMateriales = parseFloat(inputCostoSuministros.value.replace(/[^0-9.-]+/g, '')) || 0;
        } else {
            // Si no existe el input, usar la variable PHP
            valorMateriales = {{ $costo_sumisnitros ?? 0 }};
        }
        
        const conceptoMateriales = conceptosAgregados.find(c => c.nombre === 'MATERIALES Y SUMINISTRO');
        
        let mensaje = '=== ESTADO DEL CONCEPTO MATERIALES Y SUMINISTRO ===\n\n';
        
        if (conceptoMateriales) {
            mensaje += `✓ Concepto MATERIALES Y SUMINISTRO encontrado\n`;
            mensaje += `  ID: ${conceptoMateriales.id}\n`;
            mensaje += `  Monto actual: $${parseFloat(conceptoMateriales.monto).toFixed(2)}\n`;
            mensaje += `  Valor de suministros: $${valorMateriales.toFixed(2)}\n\n`;
            
            if (parseFloat(conceptoMateriales.monto) === valorMateriales) {
                mensaje += `✓ Estado: Sincronizado con el costo de suministros`;
            } else {
                mensaje += `✗ Estado: Desincronizado con el costo de suministros\n`;
                mensaje += `  Diferencia: $${Math.abs(parseFloat(conceptoMateriales.monto) - valorMateriales).toFixed(2)}\n\n`;
                mensaje += `Puede editar manualmente el valor o usar el botón "Actualizar Materiales" para sincronizar con el costo de suministros.`;
            }
        } else {
            mensaje += `✗ Concepto MATERIALES Y SUMINISTRO no encontrado\n`;
            mensaje += `  Valor de suministros: $${valorMateriales.toFixed(2)}\n\n`;
            mensaje += `Para agregar el concepto, use la lista de conceptos disponibles. Una vez agregado, podrá editarlo manualmente.`;
        }
        
        console.log(mensaje);
        alert(mensaje);
    }
    
    function actualizarConceptosAutomaticos() {
        // Actualizar ambos conceptos automáticos manualmente
        console.log('Actualizando conceptos automáticos manualmente...');
        
        // Actualizar MANO DE OBRA
        actualizarMontoManoObra();
        
        // Pequeña pausa para que se procese la primera actualización
        setTimeout(() => {
            // Actualizar MATERIALES Y SUMINISTRO
            actualizarMontoMateriales();
        }, 500);
    }
    
    // ===== FUNCIONES PARA SUBIDA DE ARCHIVOS DE INGENIERÍA =====
    
    // Array para almacenar archivos subidos (solo un archivo por servicio)
    let archivosIngenieria = [];
    
    // Función para cargar archivo existente
    function cargarArchivoExistente() {
        // Obtener el archivo existente desde la base de datos
        fetch(`/Servicios/Captura/ObtenerArchivoIngenieria/${servicioId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.archivo) {
                archivosIngenieria = [{
                    nombre: data.archivo.nombre_original || 'Archivo de Ingeniería',
                    ruta: data.archivo.ruta,
                    fecha: data.archivo.fecha || 'Archivo existente'
                }];
                actualizarListaArchivos();
            }
        })
        .catch(error => {
            console.error('Error al cargar archivo existente:', error);
        });
    }
    
    function validarArchivoIngenieria() {
        const input = document.getElementById('archivo_ingenieria');
        const file = input.files[0];
        const btnSubir = document.getElementById('btn-subir-ingenieria');
        
        if (!file) {
            btnSubir.disabled = true;
            return;
        }
        
        // Validar tipo de archivo
        const tiposPermitidos = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!tiposPermitidos.includes(file.type)) {
            alert('Error: Solo se permiten archivos PDF, JPG, JPEG y PNG.');
            input.value = '';
            btnSubir.disabled = true;
            return;
        }
        
        // Validar tamaño (10MB = 10 * 1024 * 1024 bytes)
        const maxSize = 500 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Error: El archivo es demasiado grande. El tamaño máximo es 500MB.');
            input.value = '';
            btnSubir.disabled = true;
            return;
        }
        
        // Mostrar vista previa
        mostrarVistaPrevia(file);
        
        // Habilitar botón de subida
        btnSubir.disabled = false;
    }
    
    function mostrarVistaPrevia(file) {
        const vistaPrevia = document.getElementById('vista-previa-ingenieria');
        const contenido = document.getElementById('contenido-vista-previa');
        
        if (file.type === 'application/pdf') {
            // Para PDF, mostrar información del archivo
            contenido.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-file-pdf text-danger me-2" style="font-size: 2rem;"></i>
                                <div>
                        <strong>${file.name}</strong><br>
                        <small class="text-muted">Tamaño: ${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                                </div>
                            </div>
            `;
        } else {
            // Para imágenes, mostrar la imagen
            const reader = new FileReader();
            reader.onload = function(e) {
                contenido.innerHTML = `
                    <div class="d-flex align-items-center">
                        <img src="${e.target.result}" alt="Vista previa" style="max-width: 200px; max-height: 150px; object-fit: contain;" class="me-2">
                        <div>
                            <strong>${file.name}</strong><br>
                            <small class="text-muted">Tamaño: ${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                        </div>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        }
        
        vistaPrevia.style.display = 'block';
    }
    
    function subirArchivoIngenieria() {
        const input = document.getElementById('archivo_ingenieria');
        const file = input.files[0];
        
        if (!file) {
            alert('Por favor, seleccione un archivo primero.');
            return;
        }
        
        // Crear FormData para enviar el archivo
        const formData = new FormData();
        formData.append('archivo', file);
        formData.append('id_servicio_enc', servicioId);
        formData.append('_token', '{{ csrf_token() }}');
        
        // Mostrar indicador de carga
        const btnSubir = document.getElementById('btn-subir-ingenieria');
        const textoOriginal = btnSubir.innerHTML;
        btnSubir.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Subiendo...';
        btnSubir.disabled = true;
        
        // Enviar archivo al servidor
        fetch('/Servicios/Captura/SubirArchivoIngenieria', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar archivo en la lista local (solo un archivo por servicio)
                archivosIngenieria = [{
                    nombre: file.name,
                    ruta: data.ruta,
                    fecha: new Date().toLocaleString()
                }];
                
                // Actualizar lista de archivos
                actualizarListaArchivos();
                
                // Limpiar formulario
                input.value = '';
                document.getElementById('vista-previa-ingenieria').style.display = 'none';
                
                // Mostrar mensaje de éxito
                alert('Archivo subido correctamente');
            } else {
                alert(data.message || 'Error al subir archivo');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al subir archivo');
        })
        .finally(() => {
            // Restaurar botón
            btnSubir.innerHTML = textoOriginal;
            btnSubir.disabled = true;
        });
    }
    
    function actualizarListaArchivos() {
        const container = document.getElementById('archivos-subidos-container');
        const lista = document.getElementById('lista-archivos-subidos');
        
        if (archivosIngenieria.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        
        let html = '';
        archivosIngenieria.forEach((archivo, index) => {
            const extension = archivo.nombre.split('.').pop().toLowerCase();
            let icono = 'fa-file';
            
            if (extension === 'pdf') {
                icono = 'fa-file-pdf text-danger';
            } else if (['jpg', 'jpeg', 'png'].includes(extension)) {
                icono = 'fa-file-image text-primary';
            }
            
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid ${icono} me-2"></i>
                        <div>
                            <strong>${archivo.nombre}</strong><br>
                            <small class="text-muted">Subido: ${archivo.fecha}</small>
                        </div>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="descargarArchivo('${archivo.ruta}', '${archivo.nombre}')">
                            <i class="fa-solid fa-download"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarArchivo(${index})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        lista.innerHTML = html;
    }
    
    function descargarArchivo(ruta, nombre) {
        try {
            console.log('=== INICIANDO DESCARGA ===');
            console.log('Ruta del archivo:', ruta);
            console.log('Nombre del archivo:', nombre);
            
            // Verificar que tenemos una ruta válida
            if (!ruta) {
                console.error('Ruta del archivo inválida:', ruta);
                alert('Error: Ruta del archivo inválida');
                return;
            }
            
            // Construir la URL completa
            const baseUrl = window.location.origin;
            const urlCompleta = `${baseUrl}/${ruta}`;
            console.log('URL completa:', urlCompleta);
            
            // Método directo: Abrir en nueva pestaña
            console.log('Abriendo archivo en nueva pestaña...');
            window.open(urlCompleta, '_blank');
            
            console.log('Descarga iniciada');
            
        } catch (error) {
            console.error('Error general en descargarArchivo:', error);
            alert('Error al descargar el archivo. Verifique la consola para más detalles.');
        }
    }
    
    function eliminarArchivo(index) {
        const confirmacion = confirm('¿Está seguro de que desea eliminar este archivo?');
        
        if (confirmacion) {
            const formData = new FormData();
            formData.append('id_servicio_enc', servicioId);
            formData.append('_token', '{{ csrf_token() }}');
            
            fetch('/Servicios/Captura/EliminarArchivoIngenieria', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Eliminar de la lista local
                    archivosIngenieria.splice(index, 1);
                    actualizarListaArchivos();
                    alert('Archivo eliminado correctamente');
                } else {
                    alert(data.message || 'Error al eliminar archivo');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al eliminar archivo');
            });
        }
    }
    
    function verificarArchivoExistente() {
        console.log('=== VERIFICANDO ARCHIVO EXISTENTE ===');
        console.log('Servicio ID:', servicioId);
        console.log('Archivos en lista local:', archivosIngenieria);
        
        if (archivosIngenieria.length === 0) {
            console.log('No hay archivos en la lista local');
            return;
        }
        
        const archivo = archivosIngenieria[0];
        console.log('Archivo a verificar:', archivo);
        
        // Verificar si el archivo existe en el servidor
        fetch(`/Servicios/Captura/ObtenerArchivoIngenieria/${servicioId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor:', data);
            
            if (data.success && data.archivo) {
                console.log('✓ Archivo encontrado en el servidor:', data.archivo);
                alert(`Archivo encontrado:\n\nNombre: ${data.archivo.nombre_original}\nRuta: ${data.archivo.ruta}\nFecha: ${data.archivo.fecha}`);
            } else {
                console.log('✗ Archivo no encontrado en el servidor');
                alert('Archivo no encontrado en el servidor');
            }
        })
        .catch(error => {
            console.error('Error al verificar archivo:', error);
            alert('Error al verificar archivo en el servidor');
        });
    }
    
    function toggleConcepto(id, habilitado) {
        const concepto = conceptosAgregados.find(c => c.id === id);
        if (concepto) {
            concepto.habilitado = habilitado;
            // Habilitar/deshabilitar el input de monto
            const inputMonto = document.getElementById(`concepto_${id}`);
            if (inputMonto) {
                inputMonto.disabled = !habilitado;
            }
            // Recalcular totales
            calcularTotales();
            // Actualizar estado en la base de datos
            actualizarEstadoConceptoEnBD(id, habilitado);
        }
    }

    function actualizarEstadoConceptoEnBD(idConcepto, habilitado) {
        const formData = new FormData();
        formData.append('id_servicio_enc', servicioId);
        formData.append('id_concepto', idConcepto);
        formData.append('estado', habilitado ? 1 : 0);
        formData.append('_token', '{{ csrf_token() }}');
        fetch('/Servicios/Captura/ActualizarConcepto', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Error al actualizar el estado del concepto');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar el estado del concepto');
        });
    }
    
    // Permitir agregar puesto con Enter
    document.addEventListener('DOMContentLoaded', function() {
        console.log('=== INICIALIZACIÓN DE LA PÁGINA ===');
        
        // Limpiar duplicados si existen
        limpiarDuplicados();
        
        // Verificar conceptos existentes
        console.log('Conceptos existentes:', conceptosAgregados.length);
        
        // Esperar un momento para que se procesen las operaciones de BD
        setTimeout(() => {
            console.log('=== PROCESAMIENTO POST-CARGA ===');
            console.log('Nota: Todos los conceptos son editables manualmente. Los botones de actualización sincronizan con valores calculados.');
            
            // Calcular totales
            calcularTotales();
            
            // Aplicar configuración inicial del formulario
            cambiarTipoFormulario();
            
            // Cargar datos guardados
            actualizarTablaPuestos();
            actualizarTablaConceptos();
            
            // Cargar archivo de ingeniería existente
            cargarArchivoExistente();
            
            console.log('=== INICIALIZACIÓN COMPLETADA ===');
        }, 500);
        
        // Evento para agregar puesto con Enter
        document.getElementById('buscar_empleado').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                mostrarModalEmpleados();
            }
        });
        
        document.getElementById('salario').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                agregarPuesto();
            }
        });
    });

    function actualizarInputMaterialesSuministro() {
        // Buscar el input de MATERIALES Y SUMINISTRO
        let costoProveedorExterno = 0;
        const inputCostoProveedor = document.getElementById('costo_proveedor_externo');
        if (inputCostoProveedor && inputCostoProveedor.style.display !== 'none') {
            costoProveedorExterno = parseFloat(inputCostoProveedor.value) || 0;
        }
        // Buscar el input de materiales y suministro en la tabla de conceptos
        document.querySelectorAll('#tabla-conceptos tr').forEach(function(row) {
            const nombre = row.querySelector('td:nth-child(2)') ? row.querySelector('td:nth-child(2)').textContent.trim().toUpperCase() : '';
            if (nombre === 'MATERIALES Y SUMINISTRO') {
                const inputMonto = row.querySelector('input.concepto-monto');
                if (inputMonto) {
                    // Obtener el valor base (sin el costo externo)
                    let valorBase = parseFloat(inputMonto.getAttribute('data-base')) || 0;
                    // Si no hay data-base, lo inicializamos
                    if (!inputMonto.hasAttribute('data-base')) {
                        inputMonto.setAttribute('data-base', inputMonto.value);
                        valorBase = parseFloat(inputMonto.value) || 0;
                    }
                    // Actualizar el valor mostrado (base + costo externo)
                    inputMonto.value = (valorBase + costoProveedorExterno).toFixed(2);
                }
            }
        });
    }

    // Llamar esta función cada vez que cambie el costo externo o el input de materiales
    document.addEventListener('DOMContentLoaded', function() {
        const inputCostoProveedor = document.getElementById('costo_proveedor_externo');
        if (inputCostoProveedor) {
            inputCostoProveedor.addEventListener('input', function() {
                actualizarInputMaterialesSuministro();
                calcularTotales();
            });
        }
        // También al cambiar el input de materiales, actualizar el data-base
        document.getElementById('tabla-conceptos').addEventListener('input', function(e) {
            if (e.target.classList.contains('concepto-monto')) {
                const row = e.target.closest('tr');
                const nombre = row.querySelector('td:nth-child(2)') ? row.querySelector('td:nth-child(2)').textContent.trim().toUpperCase() : '';
                if (nombre === 'MATERIALES Y SUMINISTRO') {
                    // Guardar el valor base sin el costo externo
                    e.target.setAttribute('data-base', parseFloat(e.target.value) - (parseFloat(document.getElementById('costo_proveedor_externo').value) || 0));
                }
            }
        });
        // Inicializar al cargar
        actualizarInputMaterialesSuministro();
    });

    // Modificar calcularTotales para que solo tome el valor mostrado en el input de materiales y suministro
    // (ya sumado el costo externo)

</script>
@endsection
