@extends('layouts.app')
@section('content')

<div class="container-fluid format_page">
    <div class="row">
        <div class="center">
            <h3 class="mt-1 animate__animated animate__backInLeft">Nueva Cotizacion</h3>
        </div>
    </div>
    <div class="offcanvas-body small">
        <div class="p-4 pt-0 pb-0">
            <div data-bs-spy="scroll" data-bs-target="#navbar-example" data-bs-offset="0" class="scrollspy-example"
                tabindex="0">
                <form action="{{ route('compras.store')}}" method="POST" class="form g-3 needs-validation" novalidate>
                    @csrf

                    <div class="row mb-3">
                        <h4 id="paso1">Paso 1. General</h4>
                        <div class="row">
                            <div class="col">
                                <!-- nombre -->
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Nombre de Cotización</label>
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
                            <div class="col">
                                <!-- fecha creacion-->
                                <div class="form-outline">
                                    <label class="form-label">Fecha de Inicio</label>
                                    <input type="date" id="fecha_creacion" name="fecha_creacion"
                                        class="form-control" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                            <div class="col">
                                <!-- fecha limite-->
                                <div class="form-outline">
                                    <label class="form-label">Fecha Fin</label>
                                    <input type="date" id="fecha_limite" name="fecha_limite"
                                        class="form-control" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                            <div class="col">
                                <!-- descripcion detalle -->
                                <div class="form-outline">
                                    <label class="form-label">Descripcion</label>
                                    <textarea class="form-control" id="descripcion_detalle" name="descripcion_detalle"
                                        rows="2" required></textarea>
                                    {{-- <input type="text" id="descripciondetalle" name="descripcion_detalle"
                                        class="form-control" required /> --}}
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>
                            
                            {{-- <div class="col">
                                <!-- Email input -->
                                <div class="form-outline">
                                    <label class="form-label">Tipo de Cotización</label>
                                    <select class="form-select" name="tipo_Cotizacion" required>
                                        <option value="">Seleccionar...</option>
                                    </select>
                                </div>
                            </div> --}}
                        </div>
                    </div>
                    
                    <div> <!-- Detalle --> 
                        <table id="detalle">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input name="detalle[0][producto]" required></td>
                                    <td><input type="number" name="detalle[0][cantidad]" value="1" onchange="calcularSubtotal(this)" required></td>
                                    <td><input type="number" name="detalle[0][precio]" value="0" step="0.01" onchange="calcularSubtotal(this)" required></td>
                                    <td><input type="number" name="detalle[0][subtotal]" value="0" readonly></td>
                                    <td><button type="button" onclick="eliminarFila(this)">Eliminar</button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                                    <td><input type="number" id="totalGeneral" readonly></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        <br>
                        <button type="button" onclick="agregarFila()">Agregar línea</button>
                        <button type="submit">Guardar Cotización</button>               
                    </div>


                    <!-- Guardar -->
                    <div class="offcanvas-footer text-end" style="padding:10px;">
                        <button class="btn btn-baseColor fs-8" style="border-radius: 10px;" type="submit"><i
                                class="fa-solid fa-check"></i>&nbsp;&nbsp;Crear Cotización
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/validaPDF.js') }}"></script>

    <script>
        let fila = 1;

        function agregarFila() {
            const tabla = document.getElementById('detalle').getElementsByTagName('tbody')[0];
            const nueva = tabla.insertRow();

            nueva.innerHTML = `
                <td><input name="detalle[${fila}][producto]" required></td>
                <td><input type="number" name="detalle[${fila}][cantidad]" value="1" onchange="calcularSubtotal(this)" required></td>
                <td><input type="number" name="detalle[${fila}][precio]" value="0" step="0.01" onchange="calcularSubtotal(this)" required></td>
                <td><input type="number" name="detalle[${fila}][subtotal]" value="0" readonly></td>
                <td><button type="button" onclick="eliminarFila(this)">Eliminar</button></td>
            `;
            fila++;
        }

        function eliminarFila(boton) {
            const fila = boton.closest('tr');
            fila.remove();
        }

        function calcularSubtotal(input) {
            const fila = input.closest('tr');
            const cantidad = parseFloat(fila.querySelector('[name*="[cantidad]"]').value) || 0;
            const precio = parseFloat(fila.querySelector('[name*="[precio]"]').value) || 0;
            const subtotal = cantidad * precio;
            fila.querySelector('[name*="[subtotal]"]').value = subtotal.toFixed(2);
            calcularTotalGeneral();
        }

        function calcularTotalGeneral() {
            let total = 0;
            document.querySelectorAll('[name*="[subtotal]"]').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            document.getElementById('totalGeneral').value = total.toFixed(2);
        }
    </script>
@endsection
