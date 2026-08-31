@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid format_page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-file-lines"></i>
                        </div>
                        <div>
                            <div class="mb-1">
                                <a href="{{ route('ordcompras.index') }}" class="text-muted text-decoration-none fs-8">
                                    <i class="fa-solid fa-chevron-left me-1"></i>Órdenes de compra
                                </a>
                            </div>
                            <h2 class="mb-0 text-marino fw-bold">
                                Detalle de Orden de Compra
                                <span class="badge bg-orange fs-6">Folio: {{ $OrdenCompra->folio }}</span>
                            </h2>
                            <p class="text-muted mb-0">Consulta de información de la orden de compra</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success_msg'))
        <div class="alert alert-success border-0 shadow-sm mb-3">{{ session('success_msg') }}</div>
        @endif
        @if(session('warning_msg'))
        <div class="alert alert-warning border-0 shadow-sm mb-3">{{ session('warning_msg') }}</div>
        @endif

        <!-- Formulario discreto de decisión de revisión al inicio -->
        @if($OrdenCompra->estado == 'revision' &&  $permiso2 == 'revision_ordenesCompra')
        <div class="bg-light border rounded-2 p-3 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-exclamation-triangle text-warning me-2"></i>
                        <span class="fw-semibold text-secondary">Esta orden está en revisión</span>
                    </div>
                    <small class="text-muted">Debes decidir si aprobarla o rechazarla para continuar.</small>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-success btn-sm me-2" onclick="cambiarEstado('aceptado')">
                        <i class="fa-solid fa-check"></i> Aprobar
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="cambiarEstado('rechazado')">
                        <i class="fa-solid fa-times"></i> Rechazar
                    </button>
                </div>
            </div>
        </div>
        @endif

        <div data-bs-spy="scroll" data-bs-target="#navbar-example" data-bs-offset="0" class="scrollspy-example"
            tabindex="0">

            <form enctype="multipart/form-data" class="g-3 needs-validation form" novalidate>
                @csrf

                <!-- Datos generales-->
                <div class="bg-body shadow-sm rounded-2 p-4 mb-4">
                    <div class="row">
                        <h4 id="paso1"><b class="fs-3 text-orange">1. </b> General 
                            <a class="btn btn-secondary btn-sm m-0 ms-2 fs-9" 
                                href="{{ route('ordcompras.pdf', $OrdenCompra->id) }}" 
                                target="_blank"
                                title="Generar Reporte PDF">
                                <i class="fa-solid fa-file-pdf fs-8"></i> Orden Compra
                            </a>
                        </h4>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-12 order-lg-first pb-5 pt-4">
                            <b>Información</b>
                            <div class="row">
                                <div class="col-md-12 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Nombre</label>
                                        {{-- <input type="text" hidden class="form-control text" name="idnomina"
                                            value="{{ $OrdenCompra->idnom }}"required /> --}}
                                        <input type="text" class="form-control text" name="nombre"
                                            id="nombre" value="{{ $OrdenCompra->nombre }}" minlength="3"
                                            maxlength="200" required />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-6 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Fecha Creación</label>
                                        <input type="date" id="fecha_creacion" name="fecha_creacion"
                                            class="form-control text" value="{{ $OrdenCompra->fecha_creacion }}" required/>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-6 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Fecha Entrega</label>
                                        <input type="text" name="fecha_limite" id="fecha_limite"
                                            class="form-control text" value="{{ $OrdenCompra->fecha_limite }}"
                                            required readonly />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-6 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Fecha Tentativa de Pago</label>
                                        <input type="text" name="fecha_tentativa_pago" id="fecha_tentativa_pago"
                                            class="form-control text" value="{{ $OrdenCompra->fecha_tentativa_pago ?? 'No especificada' }}"
                                            readonly />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 col-12 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Descripción</label>
                                        <textarea name="descripcion_detalle" id="descripcion_detalle"
                                            class="form-control text" required>{{ $OrdenCompra->descripcion_detalle }}</textarea>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        
                        <!-- Nuevos campos adicionales -->
                        <div class="col-md-6 col-12 p-4 bg-light border rounded-2">
                            <b>Adicional</b>
                            <div class="row mt-3">
                                <div class="col-md-8 col-8 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Tipo de Moneda</label>
                                        <input type="text" class="form-control text" value="{{ $OrdenCompra->tipo_moneda ?? 'MXN' }}" readonly />
                                    </div>
                                </div>

                                <div class="col-md-4 col-4 mb-2 pt-4">
                                    <div class="form-outline">
                                        <label class="form-label">Aplicar IVA</label>
                                        <select name="iva_aplicado" id="iva_aplicado" class="form-select text" required>
                                            @if($OrdenCompra->iva_aplicado == 1)
                                                <option value="1" selected>Si</option>
                                                <option value="0">No</option>
                                            @else
                                                <option value="1">Si</option>
                                                <option value="0" selected>No</option>
                                            @endif
                                        </select>

                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, selecciona el tipo de moneda.</div>
                                    </div>
                                </div>

                                <div class="col-md-6 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Condiciones de Entrega</label>
                                        <textarea class="form-control text" rows="3" readonly>{{ $OrdenCompra->condiciones_entrega ?? 'No especificado' }}</textarea>
                                    </div>
                                </div>

                                <div class="col-md-6 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Observaciones</label>
                                        <textarea class="form-control text" rows="3" readonly>{{ $OrdenCompra->observaciones ?? 'Sin observaciones' }}</textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                @livewire('buscar-proveedor',[
                                    'proveedor_id'=>$OrdenCompra->proveedor_id ?? null,
                                    'sololectura' => true,
                                    'nombre_seleccionado' => $OrdenCompra->proveedor->nombre ?? null,
                                    'persona_atencion_id' => $OrdenCompra->persona_atencion_id ?? null
                                ])
                            </div>
                        </div>
                    </div>

                </div>

                @livewire('licitacion-detalle', ['detalle' => old('detalle', $detalle ?? []),'eslicitacion'=>false,'sololectura' => true])

                @php
                    $resumenDeuda = $resumenDeuda ?? ['existe' => false];
                    $totalesOc = $totalesOc ?? ['subtotal' => 0, 'iva' => 0, 'total' => 0];
                    $abonosDeuda = $abonosDeuda ?? collect();
                    $cuentasBancarias = $cuentasBancarias ?? collect();
                    $puedeAbonar = ($resumenDeuda['existe'] ?? false)
                        && ($resumenDeuda['saldo'] ?? 0) > 0
                        && !in_array($resumenDeuda['estado'] ?? '', ['pagada', 'anulada'], true);
                    $diasTxt = '—';
                    if (isset($resumenDeuda['dias']) && $resumenDeuda['dias'] !== null) {
                        if ($resumenDeuda['dias'] > 0) {
                            $diasTxt = $resumenDeuda['dias'] . ' días restantes';
                        } elseif ($resumenDeuda['dias'] === 0) {
                            $diasTxt = 'Vence hoy';
                        } else {
                            $diasTxt = 'Vencida hace ' . abs($resumenDeuda['dias']) . ' días';
                        }
                    }
                @endphp

                <div class="bg-body shadow-sm rounded-2 p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="mb-0"><b class="fs-3 text-orange">2. </b> Cuenta por pagar</h4>
                        @if($resumenDeuda['existe'] ?? false)
                            <a href="{{ route('finanzas') }}" class="btn btn-outline-secondary btn-sm">
                                Ver en Finanzas
                            </a>
                        @endif
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="border rounded-2 p-3 h-100">
                                <div class="text-muted fs-8">Total OC</div>
                                <div class="fw-bold fs-5">${{ number_format($totalesOc['total'], 2) }} <small class="text-muted">{{ $OrdenCompra->tipo_moneda ?? 'MXN' }}</small></div>
                                <div class="fs-8 text-muted">Subtotal ${{ number_format($totalesOc['subtotal'], 2) }} · IVA ${{ number_format($totalesOc['iva'], 2) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded-2 p-3 h-100">
                                <div class="text-muted fs-8">Por pagar</div>
                                @if($resumenDeuda['existe'] ?? false)
                                    <div class="fw-bold fs-5">${{ number_format($resumenDeuda['saldo'], 2) }}</div>
                                    <div class="fs-8">Pagado ${{ number_format($resumenDeuda['pagado'], 2) }}</div>
                                @else
                                    <div class="fw-bold">Sin deuda</div>
                                    <div class="fs-8 text-muted">Se crea al enviar al proveedor</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded-2 p-3 h-100">
                                <div class="text-muted fs-8">Vencimiento</div>
                                @if(!empty($resumenDeuda['fecha_vencimiento']))
                                    <div class="fw-bold">{{ \Carbon\Carbon::parse($resumenDeuda['fecha_vencimiento'])->format('d/m/Y') }}</div>
                                    <div class="fs-8 {{ ($resumenDeuda['dias'] ?? 1) < 0 ? 'text-danger' : 'text-muted' }}">{{ $diasTxt }}</div>
                                @else
                                    <div class="fw-bold">{{ $OrdenCompra->fecha_tentativa_pago ? \Carbon\Carbon::parse($OrdenCompra->fecha_tentativa_pago)->format('d/m/Y') : 'No definida' }}</div>
                                    <div class="fs-8 text-muted">Tentativa de pago de la OC</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded-2 p-3 h-100">
                                <div class="text-muted fs-8">Estado</div>
                                @if($resumenDeuda['existe'] ?? false)
                                    <span class="badge bg-{{ ($resumenDeuda['estado'] ?? '') === 'pagada' ? 'success' : (($resumenDeuda['estado'] ?? '') === 'parcial' ? 'info' : 'warning') }}">
                                        {{ ucfirst($resumenDeuda['estado'] ?? '') }}
                                    </span>
                                    <div class="fs-8 text-muted mt-1">Deuda #{{ $resumenDeuda['id'] }}</div>
                                @else
                                    <span class="badge bg-secondary">{{ $OrdenCompra->estado_legible }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($puedeAbonar)
                    <form method="POST" action="{{ route('finanzas.deuda-pagar.abonar', $resumenDeuda['id']) }}" class="border rounded-2 p-3 mb-3 bg-light">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label fs-8">Monto *</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $resumenDeuda['saldo'] }}" name="monto" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8">Fecha *</label>
                                <input type="date" name="fecha" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8">Método</label>
                                <select name="metodo_pago" class="form-select form-select-sm">
                                    <option value="Transferencia electrónica">Transferencia</option>
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Cheque nominativo">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-8">Cuenta</label>
                                <select name="cuenta_id" class="form-select form-select-sm">
                                    <option value="">Sin movimiento de cuenta</option>
                                    @foreach($cuentasBancarias as $cta)
                                        <option value="{{ $cta->id }}">{{ $cta->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-8">Referencia</label>
                                <input type="text" name="referencia" class="form-control form-control-sm" maxlength="120">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-success btn-sm w-100">Abonar</button>
                            </div>
                        </div>
                    </form>
                    @endif

                    @if($abonosDeuda->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-end">Monto</th>
                                    <th>Método</th>
                                    <th>Cuenta / egreso</th>
                                    <th>Referencia</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($abonosDeuda as $abono)
                                    <tr>
                                        <td>{{ optional($abono->fecha)->format('d/m/Y') }}</td>
                                        <td class="text-end">${{ number_format((float)$abono->monto, 2) }}</td>
                                        <td>{{ $abono->metodo_pago }}</td>
                                        <td>
                                            @if($abono->egreso_id)
                                                Egreso #{{ $abono->egreso_id }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $abono->referencia ?: '—' }}</td>
                                        <td>{{ optional($abono->creador)->name ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif($resumenDeuda['existe'] ?? false)
                        <p class="text-muted mb-0 fs-8">Aún no hay abonos registrados.</p>
                    @endif
                </div>
                <br><br>
            </form>

        </div>

    </div>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/validaPDF.js') }}"></script>
    
    <script>
        // Función para cambiar estado desde la pantalla de visualización
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
    </script>

 @endsection
