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
                <h2 class="mt-1 animate_animated animate_backInLeft">Creación de Proyecto - Costos</h2>
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

                <a href="/Servicios/Captura/MaterialesProyectos/{{$tipo}}/{{$id}}" class="row pointer">
                <div class="row">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleBlue"> 2</div>
                    </div>

                    <div class="col-md-10 col-6 p-3 pb-0 d-none d-md-block">
                        <h6 class="text-dark">Materiales del Proyecto</h6>
                    </div>
                </div>
                </a>

                <div class="lineBlue d-none d-md-block"></div>

                <div class="row">
                    <div class="col-md-2 col-2 d-none d-md-block">
                        <div class="circleBlue">3</div>
                    </div>

                    <div class="col-md-10 col-12 d p-3 pb-0">
                        <h6>Costos Calculados</h6>
                    </div>
                </div>
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

                {{-- CONCEPTOS POR PROYECTO --}}
                <div class="col-md-9">
                    <h5 class="mb-3">Conceptos por Proyecto</h5>
                        {{-- CONCEPTOS --}}
                         <div class="row p-3">
                        <div class="table-responsive">
                        <form action="/Captura/insertar_conceptosproyectos/{{$id}}" method="POST">
                  
                            @csrf
                            <table class="table table-bordered" id="dynamicTable" style="width: 100%!importatn">
                                <thead>
                                    <tr>
                                        <th>Partida</th>
                                        <th>Concepto</th>
                                        <th>Descripción</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unitario</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="dynamicTableBody">
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="partida[]" value="1" checked>
                                            </div>
                                        </td>
                                        <td><input type="text" name="concepto[]" class="form-control" required></td>
                                        <td><input type="text" name="descripcion[]" class="form-control" required></td>
                                        <td><input type="number" name="cantidad[]" class="form-control" min="1" step="any" required></td>
                                        <td><input type="number" name="precio_unitario[]" class="form-control" min="0" step="any" required></td>
                                        <td><input type="number" name="total[]" class="form-control" min="0" step="any" readonly></td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-success btn-sm" id="addColumnBtn"><i class="fa fa-plus"></i> Agregar Columna</button>
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
                                <th>Total Conceptos</td>
                                <td id="total-conceptos">$ {{number_format(0,2)}}</td>
                            </tr>
                            <tr>
                                <th>Total Salarios</td>
                                <td id="total-salarios">$ {{number_format(0,2)}}</td>
                            </tr>
                            <tr>
                                <th>Total Suministros</td>
                                <td id="total-suministros">$ {{number_format($costo_sumisnitros ?? 0,2)}}</td>
                            </tr>
                            <tr>
                                <th>Total Costos</td>
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
                    <div class="col-auto p-1">
                        <button class="btn btn-baseColor fs-7 d-flex align-items-center" style="min-width: 150px; height: 38px;" type="button" onclick="guardarYContinuar()">
                            <i class="fa-solid fa-circle-arrow-right me-2"></i> Continuar
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
                // Recalcular totales generales cuando cambien los conceptos
                calcularTotales();
            }
        });
        
        // Evento para checkboxes de partida
        document.getElementById('dynamicTableBody').addEventListener('change', function(e) {
            if (e.target.name === "partida[]") {
                // Recalcular totales cuando se marque/desmarque un checkbox
                calcularTotales();
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
                if (input.type === 'checkbox') {
                    input.checked = true; // Mantener el checkbox marcado por defecto
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
        
        // Inicializar cálculos
        // Calcular totales de todas las filas existentes
        document.querySelectorAll('#dynamicTableBody tr').forEach(function(row) {
            updateRowTotal(row);
        });
        // Calcular totales generales
        calcularTotalesProyecto();
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
    
    function calcularTotales() {
        let totalConceptos = 0;
        let totalConceptosParaUtilidad = 0;
        
        // Sumar todos los totales de la tabla dinámica
        document.querySelectorAll('#dynamicTableBody tr').forEach(function(row) {
            const checkbox = row.querySelector('input[name="partida[]"]');
            const totalInput = row.querySelector('input[name="total[]"]');
            const total = parseFloat(totalInput.value) || 0;
            
            // Para todos los totales (incluyendo conceptos desmarcados)
            totalConceptos += total;
            
            // Solo para el cálculo de utilidad (solo conceptos marcados)
            if (checkbox && checkbox.checked) {
                totalConceptosParaUtilidad += total;
            }
        });
        
        // Calcular total de salarios de empleados
        let totalSalarios = 0;
        puestos.forEach(puesto => {
            totalSalarios += puesto.salario_bruto || 0;
        });
        
        // Obtener costo de suministros
        const costoSuministros = {{ $costo_sumisnitros ?? 0 }};
        
        // Calcular total de costos (TODOS los conceptos + salarios + suministros)
        const totalCostos = totalConceptos + totalSalarios + costoSuministros;
        
        // Calcular total para utilidad (solo conceptos marcados + salarios + suministros)
        const totalParaUtilidad = totalConceptosParaUtilidad + totalSalarios + costoSuministros;
        
        // Obtener el porcentaje de utilidad
        const porcentaje = parseFloat(document.getElementById('porcentaje').value) || 0;
        
        // Calcular ganancia (solo sobre conceptos marcados)
        const ganancia = totalParaUtilidad * (porcentaje / 100);
        
        // Calcular total de facturación
        const totalFacturacion = totalCostos + ganancia;
        
        // Calcular IVA integrado (16%)
        const ivaIntegrado = totalFacturacion * 0.16;
        
        // Actualizar la tabla de porcentaje de utilidad
        document.getElementById('porcentajetext').textContent = porcentaje + " %";
        document.getElementById('total-conceptos').textContent = "$ " + totalConceptos.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('total-salarios').textContent = "$ " + totalSalarios.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('total-suministros').textContent = "$ " + costoSuministros.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('total-costos').textContent = "$ " + totalCostos.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('total-facturacion').textContent = "$ " + totalFacturacion.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('total-utilidad').textContent = "$ " + ganancia.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('iva-integrado').textContent = "$ " + ivaIntegrado.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        // Actualizar desglose con los conceptos de la tabla dinámica
        let desgloseHTML = '';
        document.querySelectorAll('#dynamicTableBody tr').forEach(function(row, index) {
            const checkbox = row.querySelector('input[name="partida[]"]');
            const concepto = row.querySelector('input[name="concepto[]"]').value;
            const cantidad = parseFloat(row.querySelector('input[name="cantidad[]"]').value) || 0;
            const precio = parseFloat(row.querySelector('input[name="precio_unitario[]"]').value) || 0;
            const total = parseFloat(row.querySelector('input[name="total[]"]').value) || 0;
            
            // Mostrar todos los conceptos en el desglose, pero diferenciar los marcados
            if (concepto && total > 0) {
                if (checkbox && checkbox.checked) {
                    desgloseHTML += `<span class="text-success">• ${concepto}: $ ${total.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span><br>`;
                } else {
                    desgloseHTML += `<span class="text-muted">• ${concepto}: $ ${total.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})} <small>(excluido de utilidad)</small></span><br>`;
                }
            }
        });
        
        // Agregar información de salarios al desglose
        if (totalSalarios > 0) {
            desgloseHTML += `<span class="text-info">• Salarios de empleados: $ ${totalSalarios.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span><br>`;
        }
        
        // Agregar información de suministros al desglose
        if (costoSuministros > 0) {
            desgloseHTML += `<span class="text-warning">• Suministros: $ ${costoSuministros.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span><br>`;
        }
        
        document.getElementById('conceptos-dinamicos-resumen').innerHTML = desgloseHTML;
    }
    
    function guardarYContinuar() {
        // Guardar conceptos de proyecto
        guardarConceptosProyecto();
    }
    
    function guardarConceptosProyecto() {
        // Obtener todos los datos de la tabla dinámica (solo conceptos marcados)
        const conceptos = [];
        const descripciones = [];
        const cantidades = [];
        const precios_unitarios = [];
        const totales = [];
        const partidas = [];
        
        document.querySelectorAll('#dynamicTableBody tr').forEach(function(row) {
            const checkbox = row.querySelector('input[name="partida[]"]');
            const concepto = row.querySelector('input[name="concepto[]"]').value.trim();
            const descripcion = row.querySelector('input[name="descripcion[]"]').value.trim();
            const cantidad = row.querySelector('input[name="cantidad[]"]').value;
            const precio_unitario = row.querySelector('input[name="precio_unitario[]"]').value;
            const total = row.querySelector('input[name="total[]"]').value;
            
            // Solo incluir si el checkbox está marcado y hay datos
            if (checkbox && checkbox.checked && concepto && total > 0) {
                conceptos.push(concepto);
                descripciones.push(descripcion);
                cantidades.push(cantidad);
                precios_unitarios.push(precio_unitario);
                totales.push(total);
                partidas.push('1'); // Marcar como partida activa
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
            formData.append('partida[]', partidas[index]);
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

    // Variables para gestión de puestos
    let puestos = [];
    let empleadoSeleccionado = null;
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
        
        // Ocultar todas las secciones primero
        seccionPuestos.style.display = 'none';
        tablaPuestosContainer.style.display = 'none';
        
        // Mostrar secciones según el tipo seleccionado
        switch(tipoSeleccionado) {
            case 'interno':
            case 'mixto':
                // Mostrar sección de puestos
                seccionPuestos.style.display = 'block';
                tablaPuestosContainer.style.display = 'block';
                break;
                
            case 'externo':
                // Ocultar sección de puestos
                seccionPuestos.style.display = 'none';
                tablaPuestosContainer.style.display = 'none';
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
        
        // Recalcular totales después de actualizar la tabla
        calcularTotales();
    }
    
    // Inicializar cuando se carga la página
    document.addEventListener('DOMContentLoaded', function() {
        // Aplicar configuración inicial del formulario
        cambiarTipoFormulario();
        
        // Cargar datos guardados
        actualizarTablaPuestos();
        
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
</script>
@endsection 