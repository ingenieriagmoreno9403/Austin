@extends('layouts.app')
@section('content')

    <div class="container-fluid format_page">
        <div class="row mb-4">
            <div class="col-md-8">
                <h3 class="mt-1 animate_animated animate_backInLeft">
                    Responder a Comparativa &nbsp;
                    <span class="badge bg-orange fs-6"> Folio: {{ $licitacion->folio }}</span>
                </h3>
                <p class="text-muted fs-8">Proporciona tus precios para cada producto solicitado.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('licitaciones.editar',[$licitacion->id]) }}" class="btn btn-baseColor-light">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card mb-4">
                    <div class="card-header bg-light text-white">
                        <h6 class="text-dark mb-0">Información de la Licitación</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <p class="fs-8">
                                        <b>Nombre de la Licitación </b><br>
                                        {{ $licitacion->nombre }}
                                    </p>
                                </div>
                                <div class="mb-3">
                                    <p class="fs-8">
                                        <b>Descripción</b><br>
                                        {{ $licitacion->descripcion_detalle }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <p class="fs-8">
                                                <b>Fecha de Creación</b><br>
                                                {{ \Carbon\Carbon::parse($licitacion->fecha_creacion)->format('d/m/Y') }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3 fs-8">
                                            <b>Fecha Límite</b><br>
                                            <p class="{{ \Carbon\Carbon::parse($licitacion->fecha_limite)->isPast() ? 'text-danger' : '' }}">
                                                {{ \Carbon\Carbon::parse($licitacion->fecha_limite)->format('d/m/Y') }}
                                                @php
                                                    $fechaLimite = \Carbon\Carbon::parse($licitacion->fecha_limite);
                                                    $diasRestantes = now()->diffInDays($fechaLimite, false);
                                                @endphp
                                                
                                                @if($diasRestantes < 0)
                                                    <span class="badge bg-danger">Vencida ({{ abs($diasRestantes) }} días)</span>
                                                @elseif($diasRestantes <= 3)
                                                    <span class="badge bg-warning text-dark">{{ $diasRestantes }} días restantes</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 fs-8">
                                    <b class="fw-bold">Estado Adjudicación</b>
                                    @php
                                        $estado = DB::table('tbllicitacion_proveedor')
                                            ->where('licitacion_id', $licitacion->id)
                                            ->where('proveedor_id', $proveedor_id)
                                            ->value('estado_provext');
                                            
                                        // Verificar si la licitación está adjudicada a este proveedor
                                        $isAdjudicada = ($licitacion->estado == 'adjudicada');
                                        $isAdjudicadaToThisProvider = $isAdjudicada && 
                                            ($licitacion->proveedor_adjudicado == $proveedor_id);
                                    @endphp
                                    <p class="fs-8">
                                        @if($estado == 'Enviada')
                                            <span class="badge bg-success">Enviada</span>
                                        @else
                                            <span class="badge bg-primary">Pendiente</span>
                                        @endif
                                        
                                        @if($isAdjudicadaToThisProvider)
                                            <span class="badge bg-success">Adjudicada a tu empresa</span>
                                        @elseif($isAdjudicada)
                                            <span class="badge bg-secondary">Adjudicada a otro proveedor</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-light text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="text-dark mb-0">Productos Solicitados</h6>
                            <span class="badge bg-light text-dark" id="progressBadge">
                                Progreso: <span id="progressText">0/0</span>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <form action="/ext_licitaciones/update/{{$licitacion->id}}/{{$proveedor_id}}"  method="POST" enctype="multipart/form-data"  class="g-3 needs-validation form"  id="licitacionForm"  novalidate>
                            @csrf

                            @php($partidas = 0)
                            @foreach ($detalles_calculados as $fila)
                               @php($envio = $fila->envio)
                               @php($partidas = $partidas + 1)
                            @endforeach

                            <div class="row justify-content-end pb-3">

                                <div class="col-md-9 col-12 mb-3 pt-1">
                                    <div class="border-4 border-start border-warning p-3 fs-8 shadow rounded-2 text-start">
                                        <i class="fa-solid fa-info-circle text-orange fs-7"></i> &nbsp; Introduce el precio y marca para cada producto. Recuerda guardar tus cambios periódicamente.
                                    </div>
                                </div>

                                <div class="col-md-3 col-12 form-outline text-start">
                                    <label class="form-label" for="margen">Flete</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-solid fa-percent"></i></span>
                                        <input type="text" class="form-control" value="{{$envio}}" name="envio" id="flete1" oninput="myFunction()" required />
                                    </div>
                                    <div class="form-text">Aplique el flete a dispersar</div>
                                </div>
                            </div>

                            <!-- Campo oculto para guardar borrador -->
                            <input type="hidden" name="is_draft" id="is_draft" value="0">

                            <!-- Implementación LiveWire -->
                            {{-- @livewire('licitacion-detalle-prov-ext', [
                                'detalle' => old('detalle', $detalle ?? []),
                                'sololectura' => false,
                                'licitacion_id' => $licitacion_id, 
                                'proveedor_id' => $proveedor_id
                            ]) --}}

                             <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-truncate">Producto</th>
                                        <th class="text-truncate">Cantidad</th>
                                        <th class="text-truncate">U. Med</th>
                                        <th class="text-truncate">Costo U.</th>
                                        <th class="text-truncate">Margen</th>
                                        <th class="text-truncate">Envio</th>
                                        <th class="text-truncate fw-bold">Cost + Margen</th>
                                        <th class="text-truncate fw-bold">Subtotal</th>
                                        <th class="text-truncate fw-bold">Total</th>
                                        <th class="text-truncate">Marca</th>
                                        <th class="text-truncate">Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <input type="hidden" name="detalle_json" value="{{ json_encode($detalles_calculados) }}">
                                    @foreach ($detalles_calculados as $fila)
                                        <tr>
                                            <td style="position: relative;">
                                                {{$fila->nombre}}
                                            </td>

                                            <td>{{$fila->cantidad}}</td>
                                            <td>{{$fila->umed}}</td>
                                            
                                            <td>
                                                <input type="number" 
                                                    step="0.01" 
                                                    min="0" 
                                                    class="form-control" 
                                                    value="{{$fila->costo}}" 
                                                    required>
                                            </td>

                                            

                                            <td>
                                                <input type="number" 
                                                    step="0.01" 
                                                    min="0" 
                                                    class="form-control" 
                                                     value="{{$fila->margen}}" 
                                                    required>
                                            </td>

                                           

                                            <td class="table-primary">
                                                {{$enviototal = round(($envio / $partidas) / $fila->cantidad, 2)}}
                                            </td>

                                            <td class="table-warning">
                                               {{$margentcost = round($fila->costo / (1 - ($fila->margen/100)) + $enviototal, 2)}}
                                            </td>
                                            
                                            <td class="table-primary">
                                                {{$subtotal = round($fila->costo + $enviototal, 2)}}
                                            </td>
                                            <td class="table-success">
                                                {{$total = round(($fila->costo + $enviototal) * $fila->cantidad, 2)}}
                                            </td>
                                            <td><input type="text" class="form-control" value="{{$fila->marca}}"></td>
                                            <td><input type="text" class="form-control" value="{{$fila->observaciones}}"  maxlength="100"></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="row justify-content-center p-3">
                                <button class="col-md-2 col-6 btn btn-success fs-8" type="submit"><i class="fa-solid fa-calculator"></i> Calcular</button>
                            </div>
                            
                            <!-- Sección para comentarios adicionales -->
                            <div class="mt-4">
                                <h5>Información Adicional</h5>
                                <div class="mb-3">
                                    <label for="comentarios" class="form-label">Comentarios o Aclaraciones</label>
                                    <textarea name="comentarios" id="comentarios" class="form-control" rows="3" 
                                        placeholder="Incluye cualquier información adicional que consideres relevante...">{{ $comentarios ?? '' }}</textarea>
                                </div>
                                
                                @if($isAdjudicadaToThisProvider)
                                <!-- Mostrar la sección de documentos solo si la licitación está adjudicada a este proveedor -->
                                <div class="mb-3">
                                    <label for="documentacion" class="form-label">Documentación Adicional (Facturas, XML, etc.)</label>
                                    <input type="file" class="form-control" id="documentacion" name="documentacion">
                                    <div class="form-text">Como proveedor adjudicado, puedes adjuntar documentos como facturas, XML, etc. (Máx. 5MB, PDF)</div>
                                    
                                    @if(!empty($documento_adjunto))
                                        <div class="mt-2">
                                            <span class="badge bg-success">Documento adjunto</span>
                                            <a href="{{ asset('storage/licitaciones/docs/' . $documento_adjunto) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa-solid fa-eye"></i> Ver documento
                                            </a>
                                        </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                                
                            <!-- Botones de acción -->
                            <div class="d-flex justify-content-between mt-4">
                                <div>
                                    {{-- <button type="button" class="btn btn-outline-secondary" id="btnGuardarBorrador">
                                        <i class="fa-solid fa-save"></i> Guardar borrador
                                    </button> --}}
                                </div>
                                
                                <div>
                                    <a href="{{ route('licitaciones.editar',[$licitacion->id]) }}" 
                                        class="btn btn-baseColor-light me-2 fs-8">
                                        <i class="fa-solid fa-arrow-left"></i>&nbsp; Salir
                                    </a>
                                    
                                    <button type="submit" class="btn btn-baseColor fs-8" id="btnGuardar">
                                        <i class="fa-solid fa-check"></i> Guardar cambios
                                    </button>
                                    
                                    {{-- <button type="button" class="btn btn-success" id="btnEnviar">
                                        <i class="fa-solid fa-paper-plane"></i> Guardar y Enviar
                                    </button> --}}
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para enviar -->
    <div class="modal fade" id="modalConfirmarEnvio" tabindex="-1" aria-labelledby="modalConfirmarEnvioLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalConfirmarEnvioLabel">Confirmar Envío</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-exclamation-triangle"></i> <strong>Importante:</strong> Una vez enviada la licitación, no podrás modificar los precios.
                    </div>
                    <p>¿Estás seguro de que deseas enviar esta licitación con los siguientes datos?</p>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-end">Precio</th>
                                    <th>Marca</th>
                                </tr>
                            </thead>
                            <tbody id="resumenProductos">
                                <!-- Se completa dinámicamente con JS -->
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <th>Total</th>
                                    <th class="text-end" id="totalResumen">$0.00</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="btnConfirmarEnvio">
                        <i class="fa-solid fa-paper-plane"></i> Confirmar y Enviar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Referencias a elementos del DOM
        const form = document.getElementById('licitacionForm');
        const btnGuardarBorrador = document.getElementById('btnGuardarBorrador');
        const btnEnviar = document.getElementById('btnEnviar');
        const btnGuardar = document.getElementById('btnGuardar');
        const btnConfirmarEnvio = document.getElementById('btnConfirmarEnvio');
        const isDraftInput = document.getElementById('is_draft');
        
        // Asegurar que los subtotales se calculen automáticamente al cargar la página
        window.addEventListener('load', function() {
            if (typeof Livewire !== 'undefined') {
                // Calcular todos los subtotales automáticamente
                Livewire.emit('calcularSubtotales');
            }
        });
        
        // Función para actualizar el progreso
        function actualizarProgreso() {
            const filas = document.querySelectorAll('table tbody tr');
            let completados = 0;
            let total = filas.length;
            
            filas.forEach(fila => {
                const inputCosto = fila.querySelector('input[wire\\:model*=".costo"]');
                if (inputCosto && parseFloat(inputCosto.value) > 0) {
                    completados++;
                }
            });
            
            const porcentaje = total > 0 ? Math.round((completados / total) * 100) : 0;
            const progressBadge = document.getElementById('progressBadge');
            const progressText = document.getElementById('progressText');
            
            progressText.textContent = `${completados}/${total} (${porcentaje}%)`;
            
            // Cambiar color según progreso
            progressBadge.className = 'badge';
            if (porcentaje === 100) {
                progressBadge.classList.add('bg-success', 'text-white');
            } else if (porcentaje > 50) {
                progressBadge.classList.add('bg-primary', 'text-white');
            } else if (porcentaje > 0) {
                progressBadge.classList.add('bg-warning', 'text-dark');
            } else {
                progressBadge.classList.add('bg-danger', 'text-white');
            }
            
            // Habilitar/deshabilitar botón de enviar
            btnEnviar.disabled = porcentaje !== 100;
            if (porcentaje !== 100) {
                btnEnviar.setAttribute('title', 'Debes completar todos los productos para enviar');
            } else {
                btnEnviar.removeAttribute('title');
            }
        }
        
        // Inicializar el progreso
        window.addEventListener('load', actualizarProgreso);
        
        // Escuchar cambios en los inputs de costo
        document.addEventListener('input', function(e) {
            if (e.target && e.target.getAttribute('wire:model') && e.target.getAttribute('wire:model').includes('.costo')) {
                actualizarProgreso();
            }
        });
        
        // Manejar guardado de borrador
        btnGuardar.addEventListener('click', function() {
            isDraftInput.value = "1";
            form.submit();
        });
        
        // Manejar clic en botón enviar para mostrar modal
        btnEnviar.addEventListener('click', function() {
            // Generar resumen de productos
            const filas = document.querySelectorAll('table tbody tr');
            const resumenProductos = document.getElementById('resumenProductos');
            let total = 0;
            
            // Limpiar contenido previo
            resumenProductos.innerHTML = '';
            
            filas.forEach(fila => {
                const inputNombre = fila.querySelector('input[wire\\:model*=".nombre"]');
                const inputCosto = fila.querySelector('input[wire\\:model*=".costo"]');
                const inputMarca = fila.querySelector('input[wire\\:model*=".marca"]');
                const inputCantidad = fila.querySelector('input[wire\\:model*=".cantidad"]');
                
                if (inputNombre && inputCosto && inputMarca && inputCantidad) {
                    const nombre = inputNombre.value;
                    const costo = parseFloat(inputCosto.value) || 0;
                    const marca = inputMarca.value || '-';
                    const cantidad = parseFloat(inputCantidad.value) || 0;
                    const subtotal = costo * cantidad;
                    
                    total += subtotal;
                    
                    // Crear fila en el resumen
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${nombre}</td>
                        <td class="text-end">$${costo.toFixed(2)}</td>
                        <td>${marca}</td>
                    `;
                    resumenProductos.appendChild(tr);
                }
            });
            
            // Actualizar total
            document.getElementById('totalResumen').textContent = `$${total.toFixed(2)}`;
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEnvio'));
            modal.show();
        });
        
        // Manejar confirmación de envío
        btnConfirmarEnvio.addEventListener('click', function() {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('modalConfirmarEnvio')).hide();
            
            // Establecer acción de envío y enviar formulario
            isDraftInput.value = "0";
            
            // Crear un formulario oculto para hacer el POST a la ruta de enviar
            const enviarForm = document.createElement('form');
            enviarForm.method = 'POST';
            enviarForm.action = "{{ route('ext_licitaciones.enviar', $licitacion->id) }}";
            enviarForm.style.display = 'none';
            
            // Añadir token CSRF
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            enviarForm.appendChild(csrfToken);
            
            // Añadir al documento y enviar
            document.body.appendChild(enviarForm);
            enviarForm.submit();
        });
    });
    </script>
 @endsection
