@extends('layouts.app')
@section('content')
@if($mensaje = Session::get('success'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 8000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "success",title: "¡Éxito!", text: "' . $mensaje . '"});';
            echo '</script>'; 
    @endphp
@endif

<div class="container-fluid format_page">
    <!-- Módulo de Alerta - Errores de Validación -->
    @if($mensaje = Session::get('facturaerror'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-danger border-0 shadow-sm" role="alert">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="alert-heading mb-0">
                                <i class="fas fa-times-circle me-2"></i>
                                Error al Timbrar Factura
                            </h5>
                            <button type="button" class="btn-close" onclick="this.closest('.alert').remove()" aria-label="Close"></button>
                        </div>
                        
                        <div class="error-content">
                            {!! $mensaje !!}
                        </div>
                        
                        <div class="mt-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-lightbulb text-warning me-2"></i>
                                <small class="text-muted">
                                    <strong>Sugerencias:</strong> Verifique los datos del receptor, régimen fiscal y uso CFDI antes de intentar timbrar nuevamente.
                                </small>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-danger btn-sm me-2" onclick="mostrarDetallesError()">
                                <i class="fas fa-info-circle me-1"></i>
                                Ver Detalles Técnicos
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="this.closest('.alert').remove()">
                                <i class="fas fa-times me-1"></i>
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Módulo de Alerta - Factura ya timbrada -->
    @php
        if (!isset($facturasTimbradas)) {
            if (($datos['servicio']['tipo'] ?? '') === 'pedido_venta') {
                $facturasTimbradas = \App\Models\facturacionproductos::where('id_serv_enc', $id)
                    ->where('tipo_serv', 'pedido_venta')
                    ->whereNotNull('Uuid')
                    ->orderByDesc('id')
                    ->get();
            } else {
                $facturasTimbradas = \App\Models\facturacionproductos::where('folio', 'like', $datos['servicio']['folio'] . '%')->orderBy('id', 'desc')->get();
            }
        }
        $facturaExistente = $facturasTimbradas->first();
    @endphp
    
    @if($facturaExistente)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle fa-2x text-info me-3"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="alert-heading mb-3">
                                    <i class="fas fa-stamp text-success me-2"></i>
                                    Ultima factura ya timbrada
                                </h5>
                                <p class="mb-2">
                                    <strong>Esta factura ya ha sido timbrada anteriormente.</strong> 
                                    A continuación se muestran los detalles del CFDI generado:
                                </p>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <strong class="text-dark">Folio:</strong>
                                            <span class="badge bg-primary ms-2">{{ $facturaExistente->folio ?? 'N/A' }}</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong class="text-dark">UUID:</strong>
                                            <span class="badge bg-secondary ms-2">{{ $facturaExistente->Uuid ?? 'N/A' }}</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong class="text-dark">Facturacion ID:</strong>
                                            <span class="text-muted ms-2">{{ $facturaExistente->facturama_id ?? 'N/A' }}</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong class="text-dark">RFC Receptor:</strong>
                                            <span class="text-muted ms-2">{{ $facturaExistente->reciver_rfc ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <strong class="text-dark">Nombre Receptor:</strong>
                                            <span class="text-muted ms-2">{{ $facturaExistente->reciver_nombre ?? 'N/A' }}</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong class="text-dark">Subtotal:</strong>
                                            <span class="text-success ms-2">${{ number_format($facturaExistente->subtotal ?? 0, 2) }}</span>
                                        </div>
                                        <div class="mb-2">
                                            <strong class="text-dark">Total:</strong>
                                            <span class="text-success fw-bold ms-2">${{ number_format($facturaExistente->total ?? 0, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Fecha de timbrado: {{ $facturaExistente->date ? \Carbon\Carbon::parse($facturaExistente->date)->format('d/m/Y H:i:s') : 'N/A' }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4 d-flex align-items-center justify-content-end">
                                <div class="text-center">
                                    <button class="btn btn-success btn-lg mb-2" onclick="obtenerFacturaImpresion()">
                                        <i class="fas fa-print me-2"></i>
                                        Obtener Factura Impresa
                                    </button>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-download me-1"></i>
                                        Descargar PDF del CFDI
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($facturasTimbradas->count() > 1)
        <div class="mb-3 text-end">
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalFacturasTimbradas">
                <i class="fas fa-list"></i> Ver todas las facturas timbradas
            </button>
        </div>
    @endif

    <!-- Modal de facturas timbradas -->
    <div class="modal fade" id="modalFacturasTimbradas" tabindex="-1" aria-labelledby="modalFacturasTimbradasLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalFacturasTimbradasLabel">Facturas Timbradas Relacionadas</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Folio</th>
                    <th>UUID</th>
                    <th>RFC Receptor</th>
                    <th>Nombre Receptor</th>
                    <th>Subtotal</th>
                    <th>Total</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($facturasTimbradas as $factura)
                    <tr>
                      <td>{{ $factura->folio }}</td>
                      <td>{{ $factura->Uuid }}</td>
                      <td>{{ $factura->reciver_rfc }}</td>
                      <td>{{ $factura->reciver_nombre }}</td>
                      <td>${{ number_format($factura->subtotal, 2) }}</td>
                      <td>${{ number_format($factura->total, 2) }}</td>
                      <td>
                        <button class="btn btn-success btn-sm" onclick="window.open('/verfacturaproductos/{{ $factura->folio }}/{{ $factura->Uuid }}', '_blank')">
                          <i class="fas fa-print"></i> Obtener Factura Impresa
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

    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-lg-6 col-12">
            <h3 class="text-orange">
                @if(($datos['servicio']['tipo'] ?? '') === 'pedido_venta')
                    FACTURA — PEDIDO
                @else
                    FACTURA
                @endif
            </h3>
            @if(($datos['servicio']['tipo'] ?? '') === 'pedido_venta')
                <a href="{{ route('ventas.pedidos', ['vendedor_id' => auth()->id()]) }}" class="btn btn-outline-secondary btn-sm mt-1">
                    <i class="fa-solid fa-arrow-left me-1"></i> Volver a pedidos
                </a>
                @if(!empty($requiereCartaPorte) || !empty($datos['servicio']['requiere_carta_porte']))
                    <div class="alert alert-info border-0 py-2 px-3 mt-2 mb-0 d-inline-block">
                        <i class="fa-solid fa-truck-fast me-1"></i>
                        Facturación con <strong>Carta Porte</strong>
                    </div>
                @endif
            @endif
        </div>
        <div class="col-lg-6 col-12 text-end">
            <h4>FOLIO: <span class="text-orange">{{ $datos['servicio']['folio'] }}</span></h4>
        </div>
    </div>

    <!-- Información Principal -->
    <div class="row">
        <!-- Datos del Emisor -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Emisor:</h5>
                    <div class="mb-2">
                        <strong>Nombre:</strong> <span>{{ $datos['emisor']['nombre'] ?? '' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>RFC:</strong> <span>{{ $datos['emisor']['rfc'] ?? '' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Dirección:</strong> <span>{{ $datos['emisor']['direccion'] ?? '' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Lugar de Expedición:</strong> <span>{{ $datos['emisor']['lugar_expedicion'] ?? '' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Régimen Fiscal:</strong> <span>{{ $datos['emisor']['regimen_fiscal'] ?? '' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Teléfono:</strong> <span id="telefono">{{ $datos['emisor']['telefono'] ?? '' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Datos del Receptor -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Receptor:</h5>
                    <div class="mb-2">
                        <strong>Nombre:</strong> <span>{{ $datos['receptor']['nombre'] ?? '' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>RFC:</strong> <span>{{ $datos['receptor']['rfc'] ?? '' }}</span>
                        <input type="hidden" id="rfc_receptor" value="{{ $datos['receptor']['rfc'] ?? '' }}">
                    </div>
                    <div class="mb-2">
                        <strong>Dirección:</strong> <span>{{ $datos['receptor']['direccion'] ?? '' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Código Postal:</strong> <span>{{ $datos['receptor']['codigo_postal'] ?? '' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Uso del CFDI:</strong> 
                        <div class="input-group input-group-sm">
                            <select class="form-select" id="uso_cfdi" name="uso_cfdi">
                                <option value="">Seleccionar uso CFDI</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <strong>Régimen Fiscal:</strong> 
                        <div class="input-group input-group-sm">
                            <select class="form-select" id="regimen_fiscal_receptor" name="regimen_fiscal_receptor">
                                <option value="">Seleccionar régimen fiscal</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Conceptos -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title m-0">Conceptos</h5>
                        <button type="button" class="btn btn-success btn-sm" onclick="agregarConcepto()">
                            <i class="fas fa-plus"></i> Agregar Concepto
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Unidad</th>
                                    <th>Concepto(s)</th>
                                    <th>Precio U</th>
                                    <th>Importe</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaConceptos">
                                @php
                                    $unidadesSatCatalogo = [
                                        'H87' => 'H87 - Pieza',
                                        'E48' => 'E48 - Unidad de servicio',
                                        'EA' => 'EA - Elemento',
                                        'KGM' => 'KGM - Kilogramo',
                                        'MTR' => 'MTR - Metro',
                                    ];
                                @endphp
                                @foreach($datos['conceptos'] as $index => $concepto)
                                @php
                                    $productoSat = trim($concepto['producto'] ?? '');
                                    $productoDisplay = $productoSat !== '' ? $productoSat : 'Buscar producto SAT';
                                    $unidadSeleccionada = trim($concepto['unidad'] ?? 'H87');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control form-control-sm" value="{{ $productoDisplay }}" onchange="actualizarTotales()">
                                            <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaProducto({{ $index }})">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm cantidad" value="{{ $concepto['cantidad'] }}" min="1" onchange="calcularImporte(this)">
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select form-select-sm" onchange="actualizarTotales()">
                                                @foreach($unidadesSatCatalogo as $codigoUnidad => $etiquetaUnidad)
                                                    <option value="{{ $codigoUnidad }}" {{ strtoupper($unidadSeleccionada) === strtoupper($codigoUnidad) ? 'selected' : '' }}>
                                                        {{ $etiquetaUnidad }}
                                                    </option>
                                                @endforeach
                                                @if($unidadSeleccionada !== '' && !array_key_exists(strtoupper($unidadSeleccionada), array_change_key_case($unidadesSatCatalogo, CASE_UPPER)))
                                                    <option value="{{ $unidadSeleccionada }}" selected>{{ $unidadSeleccionada }}</option>
                                                @endif
                                            </select>
                                            <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaUnidad({{ $index }})">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" value="{{ $concepto['concepto'] }}">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm precio" value="{{ str_replace(['$', ','], '', $concepto['precio']) }}" step="0.01" min="0" onchange="calcularImporte(this)">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm importe" value="{{ str_replace(['$', ','], '', $concepto['importe']) }}" readonly>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarConcepto(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                    <td colspan="2">$<span id="subtotal">{{ $datos['totales']['subtotal'] }}</span></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>IVA 16%:</strong></td>
                                    <td colspan="2">$<span id="iva">{{ $datos['totales']['iva'] }}</span></td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                    <td colspan="2">$<span id="total">{{ $datos['totales']['total'] }}</span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Concepto General -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if(strtolower($datos['servicio']['tipo']) == 'integracion' || strtolower($datos['servicio']['tipo']) == 'integración')
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="habilitar_concepto_general" name="habilitar_concepto_general" onchange="toggleConceptoGeneral()">
                            <label class="form-check-label fw-bold" for="habilitar_concepto_general">
                                <i class="fas fa-edit me-2"></i>Habilitar Conceptos Generales
                            </label>
                        </div>
                    @endif
                    
                    <div id="concepto_general_container" style="display: none;">
                        <!-- Tabla de Concepto General -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-success btn-sm" onclick="agregarFilaConceptoGeneral()">
                                <i class="fas fa-plus me-2"></i>Agregar Concepto
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Unidad</th>
                                        @if(strtolower($datos['servicio']['tipo']) == 'integracion' || strtolower($datos['servicio']['tipo']) == 'integración')
                                            <th>Concepto General</th>
                                        @endif
                                        <th>Precio U</th>
                                        <th>Importe</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="concepto_general_tbody">
                                    <tr class="concepto-general-fila" data-fila="1">
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control form-control-sm concepto-general-producto" 
                                                       value="Buscar producto SAT" onchange="actualizarTotalesConceptoGeneral()">
                                                <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaProductoConceptoGeneral(this)">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm cantidad-concepto-general" 
                                                   value="1" min="1" onchange="calcularImporteConceptoGeneral(this)">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <select class="form-select form-select-sm concepto-general-unidad" onchange="actualizarTotalesConceptoGeneral()">
                                                    <option value="H87">H87 - Pieza</option>
                                                    <option value="E48">E48 - Unidad de servicio</option>
                                                    <option value="EA">EA - Elemento</option>
                                                    <option value="KGM">KGM - Kilogramo</option>
                                                    <option value="MTR">MTR - Metro</option>
                                                </select>
                                                <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaUnidadConceptoGeneral(this)">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </td>
                                        @if(strtolower($datos['servicio']['tipo']) == 'integracion' || strtolower($datos['servicio']['tipo']) == 'integración')
                                            <td>
                                                <textarea class="form-control form-control-sm concepto-general-textarea" name="concepto_general[]" rows="3" 
                                                          placeholder="Ingrese aquí el concepto general..."></textarea>
                                            </td>
                                        @endif
                                        <td>
                                            <input type="number" class="form-control form-control-sm precio-concepto-general" 
                                                   value="0" step="0.01" min="0" onchange="calcularImporteConceptoGeneral(this)">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm importe-concepto-general" 
                                                   value="0" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFilaConceptoGeneral(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        @if(strtolower($datos['servicio']['tipo']) == 'integracion' || strtolower($datos['servicio']['tipo']) == 'integración')
                                            <td colspan="4" class="text-end"><strong>Subtotal Concepto General:</strong></td>
                                            <td colspan="2">$<span id="subtotal_concepto_general">0.00</span></td>
                                        @else
                                            <td colspan="3" class="text-end"><strong>Subtotal Concepto General:</strong></td>
                                            <td colspan="2">$<span id="subtotal_concepto_general">0.00</span></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        @if(strtolower($datos['servicio']['tipo']) == 'integracion' || strtolower($datos['servicio']['tipo']) == 'integración')
                                            <td colspan="4" class="text-end"><strong>IVA 16%:</strong></td>
                                            <td colspan="2">$<span id="iva_concepto_general">0.00</span></td>
                                        @else
                                            <td colspan="3" class="text-end"><strong>IVA 16%:</strong></td>
                                            <td colspan="2">$<span id="iva_concepto_general">0.00</span></td>
                                        @endif
                                    </tr>
                                    <tr>
                                        @if(strtolower($datos['servicio']['tipo']) == 'integracion' || strtolower($datos['servicio']['tipo']) == 'integración')
                                            <td colspan="4" class="text-end"><strong>Total Concepto General:</strong></td>
                                            <td colspan="2">$<span id="total_concepto_general">0.00</span></td>
                                        @else
                                            <td colspan="3" class="text-end"><strong>Total Concepto General:</strong></td>
                                            <td colspan="2">$<span id="total_concepto_general">0.00</span></td>
                                        @endif
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Concepto General Textarea (solo para tipos que no sean Integración) -->
                        @if(strtolower($datos['servicio']['tipo']) != 'integracion' && strtolower($datos['servicio']['tipo']) != 'integración')
                            <div class="mt-3">
                                <label for="concepto_general" class="form-label fw-bold">
                                    <i class="fas fa-file-text me-2"></i>Concepto General
                                </label>
                                <textarea class="form-control" id="concepto_general" name="concepto_general" rows="4" 
                                          placeholder="Ingrese aquí el concepto general de la factura..."></textarea>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Este concepto se mostrará como información adicional en la factura.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de Pago -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Información de Pago</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <strong>Moneda:</strong> 
                                <div class="input-group input-group-sm">
                                    <select class="form-select" id="moneda" name="moneda">
                                        <option value="MXN - Peso Mexicano" {{ ($datos['pago']['moneda'] ?? 'MXN - Peso Mexicano') == 'MXN - Peso Mexicano' ? 'selected' : '' }}>MXN - Peso Mexicano</option>
                                        <option value="USD - Dolar americano" {{ ($datos['pago']['moneda'] ?? '') == 'USD - Dolar americano' ? 'selected' : '' }}>USD - Dolar americano</option>
                                    </select>
                                    <button class="btn btn-outline-secondary" type="button" onclick="abrirConversorMoneda()" title="Convertir moneda">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <strong>Forma de Pago:</strong> 
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="forma_pago" value="{{ $datos['pago']['forma_pago'] ?? '99 Por definir' }}" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaFormaPago()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <strong>Método de Pago:</strong> 
                                <div class="input-group input-group-sm">
                                    <select class="form-select" id="metodo_pago" name="metodo_pago">
                                        <option value="">Seleccionar método de pago</option>
                                        <option value="PPD" {{ ($datos['pago']['metodo_pago'] ?? '') == 'PPD' ? 'selected' : '' }}>PPD - Pago en parcialidades ó diferido</option>
                                        <option value="PUE" {{ ($datos['pago']['metodo_pago'] ?? '') == 'PUE' ? 'selected' : '' }}>PUE - Pago en una sola exhibición</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="row">
        <div class="col-12 text-end">
            <button class="btn btn-secondary me-2" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
            {{-- <button class="btn btn-primary me-2" onclick="descargarPDF()">
                <i class="fas fa-download"></i> Descargar PDF
            </button> --}}
            <button class="btn btn-success" onclick="timbrarFactura()">
                <i class="fa-solid fa-stamp"></i> Timbrar Factura
            </button>
        </div>
    </div>

    <!-- Formulario Oculto para Enviar Datos -->
    <form id="formFactura" action="{{ route('procesar.factura') }}" method="POST" >
        @csrf
        
        <!-- Datos del Emisor -->
        <input type="hidden" name="emisor_nombre" value="{{ $datos['emisor']['nombre'] ?? '' }}">
        <input type="hidden" name="emisor_rfc" value="{{ $datos['emisor']['rfc'] ?? '' }}">
        <input type="hidden" name="emisor_direccion" value="{{ $datos['emisor']['direccion'] ?? '' }}">
        <input type="hidden" name="emisor_lugar_expedicion" value="{{ $datos['emisor']['lugar_expedicion'] ?? '' }}">
        <input type="hidden" name="emisor_regimen_fiscal" value="{{ $datos['emisor']['regimen_fiscal'] ?? '' }}">
        <input type="hidden" name="emisor_telefono" value="{{ $datos['emisor']['telefono'] ?? '' }}">
        
        <!-- Datos del Servicio -->
        <input type="hidden" name="folio" value="{{ $datos['servicio']['folio'] ?? '' }}">
        <!-- ID del Servicio para inventario -->
        <input  name="id_servicio_enc" value="{{ $id }}" hidden required>
        <!-- tipo de servicio -->
        <input  name="tipo_servicio" value="{{ $datos['servicio']['tipo'] ?? '' }}" hidden required>
        <!-- Datos del Receptor -->
        <input type="hidden" name="receptor_nombre" value="{{ $datos['receptor']['nombre'] ?? '' }}">
        <input type="hidden" name="receptor_rfc" value="{{ $datos['receptor']['rfc'] ?? '' }}">
        <input type="hidden" name="receptor_direccion" value="{{ $datos['receptor']['direccion'] ?? '' }}">
        <input type="hidden" name="receptor_codigo_postal" value="{{ $datos['receptor']['codigo_postal'] ?? '' }}">
        <input type="hidden" name="receptor_uso_cfdi" value="{{ $datos['receptor']['uso_cfdi'] ?? '' }}">
        <input type="hidden" name="receptor_regimen_fiscal" value="{{ $datos['receptor']['regimen_fiscal'] ?? '' }}">
        
        <!-- Totales -->
        <input type="hidden" name="subtotal" value="{{ $datos['totales']['subtotal'] ?? '0' }}">
        <input type="hidden" name="iva" value="{{ $datos['totales']['iva'] ?? '0' }}">
        <input type="hidden" name="total" value="{{ $datos['totales']['total'] ?? '0' }}">
        
        <!-- Información de Pago -->
        <input type="hidden" name="forma_pago" value="{{ $datos['pago']['forma_pago'] ?? '' }}">
        <input type="hidden" name="metodo_pago" value="{{ $datos['pago']['metodo_pago'] ?? '' }}">
        <input type="hidden" name="moneda" value="{{ $datos['pago']['moneda'] ?? 'MXN - Peso Mexicano' }}">
        
        <!-- Los conceptos se agregarán dinámicamente -->
    </form>
</div>

<!-- Modal de Búsqueda de Productos -->
<div class="modal fade" id="modalBuscarProducto" tabindex="-1" aria-labelledby="modalBuscarProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarProductoLabel">Buscar Producto/Servicio SAT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchProducto" placeholder="Buscar producto o servicio...">
                    <button class="btn btn-primary" type="button" onclick="buscarProducto()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <div id="resultadosBusqueda" class="list-group">
                    <!-- Los resultados se mostrarán aquí -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Búsqueda de Unidades de Medida -->
<div class="modal fade" id="modalBuscarUnidad" tabindex="-1" aria-labelledby="modalBuscarUnidadLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarUnidadLabel">Buscar Unidad de Medida SAT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchUnidad" placeholder="Buscar unidad de medida...">
                    <button class="btn btn-primary" type="button" onclick="buscarUnidad()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <div id="resultadosBusquedaUnidad" class="list-group">
                    <!-- Los resultados se mostrarán aquí -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Búsqueda de Usos CFDI -->
<!--
<div class="modal fade" id="modalBuscarUsoCFDI" tabindex="-1" aria-labelledby="modalBuscarUsoCFDILabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarUsoCFDILabel">Buscar Uso CFDI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchUsoCFDI" placeholder="Buscar uso CFDI...">
                    <button class="btn btn-primary" type="button" onclick="buscarUsoCFDI()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <div id="resultadosBusquedaUsoCFDI" class="list-group">
                    <!-- Los resultados se mostrarán aquí -->
                </div>
            </div>
        </div>
    </div>
</div>
-->

<!-- Modal de Búsqueda de Formas de Pago -->
<div class="modal fade" id="modalBuscarFormaPago" tabindex="-1" aria-labelledby="modalBuscarFormaPagoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarFormaPagoLabel">Seleccionar Forma de Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchFormaPago" placeholder="Buscar forma de pago...">
                    <button class="btn btn-primary" type="button" onclick="buscarFormaPago()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <div id="resultadosBusquedaFormaPago" class="list-group">
                    <!-- Los resultados se mostrarán aquí -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Conversión de Moneda -->
<div class="modal fade" id="modalConversorMoneda" tabindex="-1" aria-labelledby="modalConversorMonedaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConversorMonedaLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Conversor de Moneda
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="tipoConversion" id="usdToMxn" value="usdToMxn" checked>
                            <label class="btn btn-outline-primary" for="usdToMxn">
                                <i class="fas fa-arrow-right me-1"></i>USD → MXN
                            </label>
                            
                            <input type="radio" class="btn-check" name="tipoConversion" id="mxnToUsd" value="mxnToUsd">
                            <label class="btn btn-outline-primary" for="mxnToUsd">
                                <i class="fas fa-arrow-left me-1"></i>MXN → USD
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="cantidadOrigen" class="form-label fw-bold" id="labelCantidadOrigen">Cantidad en USD:</label>
                            <div class="input-group">
                                <span class="input-group-text" id="simboloOrigen">$</span>
                                <input type="number" class="form-control" id="cantidadOrigen" placeholder="0.00" step="0.01" min="0" onchange="convertirMoneda()">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tipoCambio" class="form-label fw-bold">Tipo de Cambio:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="tipoCambio" placeholder="0.00" step="0.01" min="0" onchange="convertirMoneda()">
                                <span class="input-group-text" id="simboloTipoCambio">MXN/USD</span>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Última actualización: <span id="fechaActualizacion">-</span>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calculator fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Resultado de la Conversión:</h6>
                                    <p class="mb-0 fs-5 fw-bold text-primary" id="resultadoConversion">$0.00 MXN</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success" onclick="aplicarConversion()">
                                <i class="fas fa-check me-2"></i>Aplicar Conversión
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="actualizarTipoCambio()">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar Tipo de Cambio
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
function numeroALetras($numero) {
    $numero = str_replace(['$', ','], '', $numero);
    $numero = floatval($numero);
    
    $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    
    $parteEntera = floor($numero);
    $parteDecimal = round(($numero - $parteEntera) * 100);
    
    $letras = '';
    
    if ($parteEntera == 0) {
        $letras = 'CERO';
    } else {
        if ($parteEntera > 999) {
            $miles = floor($parteEntera / 1000);
            // Validar que el índice existe en el array
            if ($miles == 1) {
                $letras .= 'MIL ';
            } elseif (isset($unidades[$miles])) {
                $letras .= $unidades[$miles] . ' MIL ';
            } else {
                $letras .= 'MIL '; // Fallback si el número es muy grande
            }
            $parteEntera = $parteEntera % 1000;
        }
        
        if ($parteEntera > 99) {
            $centenaIndex = floor($parteEntera / 100);
            // Validar que el índice existe en el array
            if (isset($centenas[$centenaIndex])) {
                $letras .= $centenas[$centenaIndex] . ' ';
            }
            $parteEntera = $parteEntera % 100;
        }
        
        if ($parteEntera > 9) {
            $decenaIndex = floor($parteEntera / 10);
            // Validar que el índice existe en el array
            if (isset($decenas[$decenaIndex])) {
                $letras .= $decenas[$decenaIndex] . ' ';
            }
            $parteEntera = $parteEntera % 10;
        }
        
        if ($parteEntera > 0) {
            // Validar que el índice existe en el array
            if (isset($unidades[$parteEntera])) {
                $letras .= $unidades[$parteEntera];
            }
        }
    }
    
    return trim($letras) . ' PESOS ' . str_pad($parteDecimal, 2, '0', STR_PAD_LEFT) . '/100 M.N.';
}
@endphp

<script>
function descargarPDF() {
    // Implementar la lógica para descargar PDF
    alert('Función de descarga PDF en desarrollo');
}

function obtenerFacturaImpresion() {
    // Mostrar indicador de carga
    Swal.fire({
        title: 'Generando factura impresa...',
        text: 'Por favor espere mientras se genera el PDF',
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Obtener el folio y UUID de la factura existente
    const folio = '{{ $facturaExistente->folio ?? "" }}';
    const uuid = '{{ $facturaExistente->Uuid ?? "" }}';
    
    // Verificar que tenemos tanto el folio como el UUID
    if (!folio || !uuid) {
        Swal.close();
        Swal.fire({
            title: 'Error',
            text: 'No se encontró la información completa de la factura (folio o UUID)',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
        return;
    }
    
    // Redirigir a la ruta para ver la factura
    window.open(`/verfacturaproductos/${folio}/${uuid}`, '_blank');
    
    // Cerrar modal de carga
    Swal.close();
    
    // Mostrar mensaje de éxito
    Swal.fire({
        title: '¡Factura abierta!',
        text: 'La factura se ha abierto en una nueva pestaña',
        icon: 'success',
        confirmButtonText: 'Aceptar'
    });
}

function timbrarFactura() {
    console.log('Función timbrarFactura ejecutada');
    
    // Obtener el tipo de servicio
    const tipoServicio = '{{ strtolower($datos["servicio"]["tipo"]) }}';
    console.log('Tipo de servicio:', tipoServicio);
    
    const checkboxConceptoGeneral = document.getElementById('habilitar_concepto_general');
    const conceptoGeneralHabilitado = checkboxConceptoGeneral ? checkboxConceptoGeneral.checked : false;
    
    console.log('Concepto general habilitado:', conceptoGeneralHabilitado);
    
    // Validar campos según el tipo de servicio
    if (tipoServicio === 'integracion' || tipoServicio === 'integración') {
        // Para integración, validar según si está habilitado el concepto general
        if (conceptoGeneralHabilitado) {
            // Validar campos del concepto general
            const filasConceptoGeneral = document.querySelectorAll('.concepto-general-fila');
            let productosVacios = [];
            
            filasConceptoGeneral.forEach((fila, index) => {
                const productoInput = fila.querySelector('.concepto-general-producto');
                const productoValue = productoInput ? productoInput.value.trim() : '';
                
                // Verificar si el campo está vacío o tiene el valor por defecto
                if (!productoValue || productoValue === 'Buscar producto SAT') {
                    productosVacios.push(index + 1);
                }
            });
            
            // Si hay productos vacíos, mostrar error
            if (productosVacios.length > 0) {
                Swal.fire({
                    title: 'Campos de producto incompletos',
                    html: `
                        <div class="text-start">
                            <p>Los siguientes conceptos generales no tienen un producto seleccionado:</p>
                            <ul class="text-danger">
                                ${productosVacios.map(num => `<li>Concepto General ${num}</li>`).join('')}
                            </ul>
                            <p class="text-muted">Por favor, seleccione un producto válido para cada concepto antes de timbrar la factura.</p>
                        </div>
                    `,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#d33'
                });
                return;
            }
        } else {
            // Validar que todos los campos de producto estén llenos en la tabla principal
            const filas = document.querySelectorAll('#tablaConceptos tr');
            let productosVacios = [];
            
            filas.forEach((fila, index) => {
                const productoInput = fila.querySelector('td:nth-child(1) input');
                const productoValue = productoInput ? productoInput.value.trim() : '';
                
                // Verificar si el campo está vacío o tiene el valor por defecto
                if (!productoValue || productoValue === 'Buscar producto SAT') {
                    productosVacios.push(index + 1);
                }
            });
            
            // Si hay productos vacíos, mostrar error
            if (productosVacios.length > 0) {
                Swal.fire({
                    title: 'Campos de producto incompletos',
                    html: `
                        <div class="text-start">
                            <p>Los siguientes conceptos no tienen un producto seleccionado:</p>
                            <ul class="text-danger">
                                ${productosVacios.map(num => `<li>Concepto ${num}</li>`).join('')}
                            </ul>
                            <p class="text-muted">Por favor, seleccione un producto válido para cada concepto antes de timbrar la factura.</p>
                        </div>
                    `,
                    icon: 'error',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#d33'
                });
                return;
            }
        }
    } else if (tipoServicio === 'suministro' || tipoServicio === 'suministros') {
        // Para suministros, validar la tabla principal
        console.log('Validando suministros...');
        const filas = document.querySelectorAll('#tablaConceptos tr');
        let productosVacios = [];
        
        console.log('Número de filas encontradas:', filas.length);
        
        filas.forEach((fila, index) => {
            const productoInput = fila.querySelector('td:nth-child(1) input');
            const productoValue = productoInput ? productoInput.value.trim() : '';
            
            console.log(`Fila ${index + 1} - Producto: "${productoValue}"`);
            
            // Verificar si el campo está vacío o tiene el valor por defecto
            if (!productoValue || productoValue === 'Buscar producto SAT') {
                productosVacios.push(index + 1);
            }
        });
        
        // Si hay productos vacíos, mostrar error
        if (productosVacios.length > 0) {
            Swal.fire({
                title: 'Campos de producto incompletos',
                html: `
                    <div class="text-start">
                        <p>Los siguientes conceptos no tienen un producto seleccionado:</p>
                        <ul class="text-danger">
                            ${productosVacios.map(num => `<li>Concepto ${num}</li>`).join('')}
                        </ul>
                        <p class="text-muted">Por favor, seleccione un producto válido para cada concepto antes de timbrar la factura.</p>
                    </div>
                `,
                icon: 'error',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#d33'
            });
            return;
        }
        
        console.log('Validación de suministros completada exitosamente');
    } else {
        // Para otros tipos de servicio, validar la tabla principal
        console.log('Validando otros tipos de servicio...');
        const filas = document.querySelectorAll('#tablaConceptos tr');
        let productosVacios = [];
        
        filas.forEach((fila, index) => {
            const productoInput = fila.querySelector('td:nth-child(1) input');
            const productoValue = productoInput ? productoInput.value.trim() : '';
            
            // Verificar si el campo está vacío o tiene el valor por defecto
            if (!productoValue || productoValue === 'Buscar producto SAT') {
                productosVacios.push(index + 1);
            }
        });
        
        // Si hay productos vacíos, mostrar error
        if (productosVacios.length > 0) {
            Swal.fire({
                title: 'Campos de producto incompletos',
                html: `
                    <div class="text-start">
                        <p>Los siguientes conceptos no tienen un producto seleccionado:</p>
                        <ul class="text-danger">
                            ${productosVacios.map(num => `<li>Concepto ${num}</li>`).join('')}
                        </ul>
                        <p class="text-muted">Por favor, seleccione un producto válido para cada concepto antes de timbrar la factura.</p>
                    </div>
                `,
                icon: 'error',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#d33'
            });
            return;
        }
    }
    
    // Mostrar confirmación antes de proceder
    Swal.fire({
        title: '¿Está seguro de timbrar la factura?',
        text: conceptoGeneralHabilitado ? 
            'Esta acción generará un CFDI válido ante el SAT usando solo el concepto general. Una vez timbrada, la factura no podrá ser modificada.' :
            'Esta acción generará un CFDI válido ante el SAT. Una vez timbrada, la factura no podrá ser modificada.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, timbrar factura',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar barra de progreso detallada
            mostrarProgresoTimbrado();
        }
    });
}

function mostrarProgresoTimbrado() {
    let progreso = 0;
    const pasos = [
        'Validando datos de la factura...',
        'Preparando información para el SAT...',
        'Conectando con el servicio de facturación...',
        'Generando CFDI...',
        'Timbrando factura...',
        'Finalizando proceso...'
    ];
    
    const pasoActual = 0;
    
    Swal.fire({
        title: 'Procesando timbrado de factura',
        html: `
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">${pasos[pasoActual]}</span>
                    <span class="text-primary">${progreso}%</span>
                </div>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                         role="progressbar" 
                         style="width: ${progreso}%" 
                         aria-valuenow="${progreso}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Este proceso puede tardar entre 30 segundos y 2 minutos dependiendo del servidor del SAT.
                </small>
            </div>
        `,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            // Simular progreso mientras se procesa
            const progressBar = Swal.getHtmlContainer().querySelector('.progress-bar');
            const progressText = Swal.getHtmlContainer().querySelector('.text-primary');
            const stepText = Swal.getHtmlContainer().querySelector('.text-muted');
            
            const interval = setInterval(() => {
                progreso += Math.random() * 15 + 5; // Incremento variable entre 5-20%
                
                if (progreso >= 100) {
                    progreso = 100;
                    clearInterval(interval);
                }
                
                // Actualizar barra de progreso
                progressBar.style.width = progreso + '%';
                progressBar.setAttribute('aria-valuenow', progreso);
                progressText.textContent = Math.round(progreso) + '%';
                
                // Cambiar mensaje según el progreso
                const pasoIndex = Math.floor((progreso / 100) * pasos.length);
                if (pasoIndex < pasos.length) {
                    stepText.textContent = pasos[pasoIndex];
                }
                
                // Cambiar color de la barra según el progreso
                if (progreso < 30) {
                    progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-info';
                } else if (progreso < 70) {
                    progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-warning';
                } else if (progreso < 100) {
                    progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-success';
                }
                
                // Si llegó al 100%, proceder con el timbrado real
                if (progreso >= 100) {
                    setTimeout(() => {
                        procesarTimbradoReal();
                    }, 500);
                }
            }, 800); // Actualizar cada 800ms
        }
    });
}

function procesarTimbradoReal() {
    // Actualizar mensaje final
    Swal.update({
        title: 'Finalizando timbrado...',
        html: `
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Guardando factura en la base de datos...</span>
                    <span class="text-success">100%</span>
                </div>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                         role="progressbar" 
                         style="width: 100%" 
                         aria-valuenow="100" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <small class="text-success">
                    <i class="fas fa-check-circle"></i> 
                    Proceso completado exitosamente.
                </small>
            </div>
        `
    });
    
    // Proceder con el timbrado real después de un breve delay
    setTimeout(() => {
        procesarTimbrado();
    }, 1000);
}

function procesarTimbrado() {
    // Recopilar datos del formulario
    const formData = new FormData();
    
    // Datos del emisor
    formData.append('emisor_nombre', document.querySelector('input[name="emisor_nombre"]').value);
    formData.append('emisor_rfc', document.querySelector('input[name="emisor_rfc"]').value);
    formData.append('emisor_direccion', document.querySelector('input[name="emisor_direccion"]').value);
    formData.append('emisor_lugar_expedicion', document.querySelector('input[name="emisor_lugar_expedicion"]').value);
    formData.append('emisor_regimen_fiscal', document.querySelector('input[name="emisor_regimen_fiscal"]').value);
    formData.append('emisor_telefono', document.querySelector('input[name="emisor_telefono"]').value);
    
    // Datos del servicio
    formData.append('folio', document.querySelector('input[name="folio"]').value);
    
    // Datos del receptor
    formData.append('receptor_nombre', document.querySelector('input[name="receptor_nombre"]').value);
    formData.append('receptor_rfc', document.querySelector('input[name="receptor_rfc"]').value);
    formData.append('receptor_direccion', document.querySelector('input[name="receptor_direccion"]').value);
    formData.append('receptor_codigo_postal', document.querySelector('input[name="receptor_codigo_postal"]').value);
    formData.append('receptor_uso_cfdi', document.querySelector('#uso_cfdi').value);
    formData.append('receptor_regimen_fiscal', document.querySelector('#regimen_fiscal_receptor').value);
    
    console.log('Datos del receptor:', {
        nombre: document.querySelector('input[name="receptor_nombre"]').value,
        rfc: document.querySelector('input[name="receptor_rfc"]').value,
        direccion: document.querySelector('input[name="receptor_direccion"]').value,
        codigo_postal: document.querySelector('input[name="receptor_codigo_postal"]').value,
        uso_cfdi: document.querySelector('#uso_cfdi').value,
        regimen_fiscal: document.querySelector('#regimen_fiscal_receptor').value
    });
    
    // Verificar si está habilitado el concepto general
    const checkboxConceptoGeneral = document.getElementById('habilitar_concepto_general');
    const conceptoGeneralHabilitado = checkboxConceptoGeneral ? checkboxConceptoGeneral.checked : false;
    
    let conceptos = [];
    let subtotal = 0;
    let iva = 0;
    let total = 0;
    
    if (conceptoGeneralHabilitado) {
        // Usar solo los conceptos generales
        const filasConceptoGeneral = document.querySelectorAll('.concepto-general-fila');
        
        filasConceptoGeneral.forEach((fila, index) => {
            const productoInput = fila.querySelector('.concepto-general-producto');
            const cantidadInput = fila.querySelector('.cantidad-concepto-general');
            const unidadSelect = fila.querySelector('.concepto-general-unidad');
            const precioInput = fila.querySelector('.precio-concepto-general');
            const importeInput = fila.querySelector('.importe-concepto-general');
            const textarea = fila.querySelector('.concepto-general-textarea');
            
            if (productoInput && cantidadInput && unidadSelect && precioInput && importeInput) {
                const conceptoGeneral = {
                    producto: productoInput.value.trim(),
                    cantidad: cantidadInput.value.trim(),
                    unidad: unidadSelect.value.trim(),
                    concepto: textarea ? textarea.value.trim() : '',
                    precio: precioInput.value.trim(),
                    importe: importeInput.value.trim()
                };
                conceptos.push(conceptoGeneral);
                console.log(`Concepto general ${index + 1}:`, conceptoGeneral);
            }
        });
        
        // Usar los totales del concepto general
        subtotal = parseFloat(document.getElementById('subtotal_concepto_general').textContent.replace(/[$,]/g, '')) || 0;
        iva = parseFloat(document.getElementById('iva_concepto_general').textContent.replace(/[$,]/g, '')) || 0;
        total = parseFloat(document.getElementById('total_concepto_general').textContent.replace(/[$,]/g, '')) || 0;
        
        console.log('Usando conceptos generales:', conceptos);
        console.log('Totales del concepto general:', { subtotal, iva, total });
    } else {
        // Usar la tabla de conceptos principal
        const filas = document.querySelectorAll('#tablaConceptos tr');
        
        console.log('Número de filas encontradas:', filas.length);
        
        filas.forEach((fila, index) => {
            console.log('Procesando fila:', index);
            
            // Obtener los inputs de cada fila
            const productoInput = fila.querySelector('td:nth-child(1) input');
            const cantidadInput = fila.querySelector('td:nth-child(2) input');
            const unidadSelect = fila.querySelector('td:nth-child(3) select');
            const conceptoInput = fila.querySelector('td:nth-child(4) input');
            const precioInput = fila.querySelector('td:nth-child(5) input');
            const importeInput = fila.querySelector('td:nth-child(6) input');
            
            console.log('Inputs encontrados:', {
                producto: !!productoInput,
                cantidad: !!cantidadInput,
                unidad: !!unidadSelect,
                concepto: !!conceptoInput,
                precio: !!precioInput,
                importe: !!importeInput
            });
            
            if (productoInput && cantidadInput && unidadSelect && conceptoInput && precioInput && importeInput) {
                const concepto = {
                    producto: productoInput.value.trim(),
                    cantidad: cantidadInput.value.trim(),
                    unidad: unidadSelect.value.trim(),
                    concepto: conceptoInput.value.trim(),
                    precio: precioInput.value.trim(),
                    importe: importeInput.value.trim()
                };
                conceptos.push(concepto);
                console.log('Concepto agregado:', concepto);
            } else {
                console.log('Fila ignorada - faltan inputs');
            }
        });
        
        // Usar los totales de la tabla principal
        subtotal = parseFloat(document.querySelector('input[name="subtotal"]').value);
        iva = parseFloat(document.querySelector('input[name="iva"]').value);
        total = parseFloat(document.querySelector('input[name="total"]').value);
    }
    
    console.log('Total de conceptos recopilados:', conceptos.length);
    console.log('Conceptos:', conceptos);
    
    // Actualizar los totales en el formulario
    formData.append('subtotal', subtotal.toString());
    formData.append('iva', iva.toString());
    formData.append('total', total.toString());
    
    // Información de pago
    formData.append('forma_pago', document.querySelector('#forma_pago').value);
    formData.append('metodo_pago', document.querySelector('#metodo_pago').value);
    formData.append('moneda', document.querySelector('#moneda').value);
    
    console.log('Datos de pago:', {
        forma_pago: document.querySelector('#forma_pago').value,
        metodo_pago: document.querySelector('#metodo_pago').value,
        moneda: document.querySelector('#moneda').value
    });
    
    // Agregar conceptos al formulario
    formData.append('conceptos', JSON.stringify(conceptos));
    
    // Crear y enviar formulario
    const form = document.getElementById('formFactura');
    
    // Limpiar campos ocultos existentes
    const camposOcultos = form.querySelectorAll('input[type="hidden"]');
    camposOcultos.forEach(campo => {
        if (campo.name !== '_token') {
            campo.remove();
        }
    });
    
    // Agregar los nuevos datos
    for (let [key, value] of formData.entries()) {
        if (key !== '_token') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
    }
    
    // Enviar formulario
    form.submit();
}

let productoSeleccionadoIndex = null;
let unidadSeleccionadaIndex = null;
let productoSeleccionadoConceptoGeneral = false;
let unidadSeleccionadaConceptoGeneral = false;
let filaActualConceptoGeneral = null;

function abrirBusquedaProducto(index) {
    productoSeleccionadoIndex = index;
    $('#modalBuscarProducto').modal('show');
}

function abrirBusquedaUnidad(index) {
    unidadSeleccionadaIndex = index;
    $('#modalBuscarUnidad').modal('show');
}

function abrirBusquedaProductoConceptoGeneral(btn) {
    productoSeleccionadoConceptoGeneral = true;
    filaActualConceptoGeneral = btn.closest('.concepto-general-fila');
    $('#modalBuscarProducto').modal('show');
}

function abrirBusquedaUnidadConceptoGeneral(btn) {
    unidadSeleccionadaConceptoGeneral = true;
    filaActualConceptoGeneral = btn.closest('.concepto-general-fila');
    $('#modalBuscarUnidad').modal('show');
}

function abrirBusquedaFormaPago() {
    // Cargar todas las formas de pago al abrir el modal
    const searchInput = document.getElementById('searchFormaPago');
    searchInput.value = '';
    
    // Realizar la búsqueda automáticamente para mostrar todas las opciones
    setTimeout(() => buscarFormaPago(), 100);
    
    $('#modalBuscarFormaPago').modal('show');
}

function buscarProducto() {
    const keyword = document.getElementById('searchProducto').value;
    if (keyword.length < 3) {
        Swal.fire('Error', 'Ingresa al menos 3 caracteres para buscar', 'error');
        return;
    }

    fetch(`/buscar-producto-servicio?keyword=${encodeURIComponent(keyword)}`)
        .then(response => response.json())
        .then(data => {
            const resultadosDiv = document.getElementById('resultadosBusqueda');
            resultadosDiv.innerHTML = '';

            data.forEach(item => {
                const button = document.createElement('button');
                button.className = 'list-group-item list-group-item-action';
                button.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${item.Name}</h6>
                        <small class="text-muted">${item.Value}</small>
                    </div>
                    <p class="mb-1">${item.Description || 'Sin descripción'}</p>
                `;
                button.onclick = () => seleccionarProducto(item);
                resultadosDiv.appendChild(button);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Ocurrió un error al buscar productos', 'error');
        });
}

function buscarUnidad() {
    const keyword = document.getElementById('searchUnidad').value;
    if (keyword.length < 3) {
        Swal.fire('Error', 'Ingresa al menos 3 caracteres para buscar', 'error');
        return;
    }

    fetch(`/buscar-unidad-medida?keyword=${encodeURIComponent(keyword)}`)
        .then(response => response.json())
        .then(data => {
            const resultadosDiv = document.getElementById('resultadosBusquedaUnidad');
            resultadosDiv.innerHTML = '';

            data.forEach(item => {
                const button = document.createElement('button');
                button.className = 'list-group-item list-group-item-action';
                button.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${item.ShortName}</h6>
                        <small class="text-muted">${item.Value}</small>
                    </div>
                    <p class="mb-1">${item.Name}</p>
                `;
                button.onclick = () => seleccionarUnidad(item);
                resultadosDiv.appendChild(button);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Ocurrió un error al buscar unidades', 'error');
        });
}

function buscarFormaPago() {
    const keyword = document.getElementById('searchFormaPago').value;
    
    fetch(`/buscar-forma-pago${keyword ? '?keyword=' + encodeURIComponent(keyword) : ''}`)
        .then(response => response.json())
        .then(data => {
            const resultadosDiv = document.getElementById('resultadosBusquedaFormaPago');
            resultadosDiv.innerHTML = '';

            // Filtrar resultados si hay keyword
            let formasPago = data;
            if (keyword) {
                formasPago = data.filter(item => 
                    item.Name.toLowerCase().includes(keyword.toLowerCase()) ||
                    item.Value.includes(keyword)
                );
            }

            formasPago.forEach(item => {
                const button = document.createElement('button');
                button.className = 'list-group-item list-group-item-action';
                button.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${item.Value} - ${item.Name}</h6>
                    </div>
                `;
                button.onclick = () => seleccionarFormaPago(item);
                resultadosDiv.appendChild(button);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error', 'Ocurrió un error al buscar formas de pago', 'error');
        });
}

function seleccionarProducto(producto) {
    if (productoSeleccionadoIndex !== null) {
        const fila = document.querySelectorAll('#tablaConceptos tr')[productoSeleccionadoIndex];
        const inputProducto = fila.querySelector('td:first-child input');
        inputProducto.value = `${producto.Value} - ${producto.Name}`;
        $('#modalBuscarProducto').modal('hide');
        productoSeleccionadoIndex = null;
    } else if (productoSeleccionadoConceptoGeneral) {
        const inputProducto = filaActualConceptoGeneral.querySelector('.concepto-general-producto');
        inputProducto.value = `${producto.Value} - ${producto.Name}`;
        $('#modalBuscarProducto').modal('hide');
        productoSeleccionadoConceptoGeneral = false;
        filaActualConceptoGeneral = null;
    }
}

function seleccionarUnidad(unidad) {
    if (unidadSeleccionadaIndex !== null) {
        const fila = document.querySelectorAll('#tablaConceptos tr')[unidadSeleccionadaIndex];
        const selectUnidad = fila.querySelector('td:nth-child(3) select');
        
        // Crear nueva opción
        const nuevaOpcion = document.createElement('option');
        nuevaOpcion.value = unidad.Value;
        nuevaOpcion.textContent = `${unidad.Value} - ${unidad.ShortName}`;
        nuevaOpcion.selected = true;
        
        // Limpiar opciones existentes y agregar la nueva
        selectUnidad.innerHTML = '';
        selectUnidad.appendChild(nuevaOpcion);
        
        $('#modalBuscarUnidad').modal('hide');
        unidadSeleccionadaIndex = null;
    } else if (unidadSeleccionadaConceptoGeneral) {
        const selectUnidad = filaActualConceptoGeneral.querySelector('.concepto-general-unidad');
        
        // Crear nueva opción
        const nuevaOpcion = document.createElement('option');
        nuevaOpcion.value = unidad.Value;
        nuevaOpcion.textContent = `${unidad.Value} - ${unidad.ShortName}`;
        nuevaOpcion.selected = true;
        
        // Limpiar opciones existentes y agregar la nueva
        selectUnidad.innerHTML = '';
        selectUnidad.appendChild(nuevaOpcion);
        
        $('#modalBuscarUnidad').modal('hide');
        unidadSeleccionadaConceptoGeneral = false;
        filaActualConceptoGeneral = null;
    }
}

function seleccionarFormaPago(formaPago) {
    const inputFormaPago = document.getElementById('forma_pago');
    inputFormaPago.value = `${formaPago.Value} - ${formaPago.Name}`;
    $('#modalBuscarFormaPago').modal('hide');
}

function cargarMetodosPago() {
    fetch('/obtener-metodos-pago')
        .then(response => response.json())
        .then(data => {
            const selectMetodoPago = document.getElementById('metodo_pago');
            
            // Limpiar opciones existentes excepto la primera
            selectMetodoPago.innerHTML = '<option value="">Seleccionar método de pago</option>';
            
            // Agregar opciones dinámicamente
            data.forEach(metodo => {
                const option = document.createElement('option');
                option.value = metodo.Value;
                option.textContent = `${metodo.Value} - ${metodo.Name}`;
                selectMetodoPago.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error al cargar métodos de pago:', error);
        });
}

function cargarUsosCFDI() {
    console.log('Iniciando carga de usos CFDI...');
    
    // Usar un keyword vacío o común para obtener todos los usos CFDI
    fetch('/buscar-uso-cfdi?keyword=G')
        .then(response => {
            console.log('Respuesta de la API de usos CFDI:', response.status, response.statusText);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos de la API de usos CFDI:', data);
            
            const selectUsoCFDI = document.getElementById('uso_cfdi');
            
            // Limpiar opciones existentes excepto la primera
            selectUsoCFDI.innerHTML = '<option value="">Seleccionar uso CFDI</option>';
            
            // Verificar si hay datos y no es un error
            if (Array.isArray(data) && data.length > 0) {
                // Agregar opciones dinámicamente
                data.forEach(uso => {
                    const option = document.createElement('option');
                    option.value = `${uso.Value} - ${uso.Name}`;
                    option.textContent = `${uso.Value} - ${uso.Name}`;
                    
                    // Marcar como seleccionado si coincide con el valor actual
                    if (option.value === '{{ $datos["receptor"]["uso_cfdi"] ?? "" }}') {
                        option.selected = true;
                    }
                    
                    selectUsoCFDI.appendChild(option);
                });
                
                console.log('Usos CFDI cargados exitosamente desde la API:', data.length, 'opciones');
            } else {
                console.warn('No se obtuvieron datos válidos de usos CFDI de la API, cargando opciones por defecto');
                console.log('Tipo de datos recibidos:', typeof data);
                console.log('Contenido de datos:', data);
                cargarUsosCFDIPorDefecto();
            }
        })
        .catch(error => {
            console.error('Error al cargar usos CFDI:', error);
            console.error('Detalles del error:', error.message);
            cargarUsosCFDIPorDefecto();
        });
}

function cargarUsosCFDIPorDefecto() {
    console.log('Cargando usos CFDI desde opciones por defecto...');
    
    const selectUsoCFDI = document.getElementById('uso_cfdi');
    selectUsoCFDI.innerHTML = '<option value="">Seleccionar uso CFDI</option>';
    
    const opcionesPorDefecto = [
        { Value: 'G03', Name: 'Gastos en general' },
        { Value: 'G01', Name: 'Adquisición de mercancías' },
        { Value: 'G02', Name: 'Devoluciones, descuentos o bonificaciones' },
        { Value: 'I01', Name: 'Honorarios médicos, dentales y gastos hospitalarios' },
        { Value: 'I02', Name: 'Gastos médicos por incapacidad o discapacidad' },
        { Value: 'I03', Name: 'Gastos funerales' },
        { Value: 'I04', Name: 'Donativos' },
        { Value: 'I05', Name: 'Intereses reales efectivamente pagados por créditos hipotecarios' },
        { Value: 'I06', Name: 'Aportaciones voluntarias al SAR' },
        { Value: 'I07', Name: 'Transporte escolar' },
        { Value: 'I08', Name: 'Depósitos en cuenta de ahorro' },
        { Value: 'D01', Name: 'Honorarios médicos, dentales y gastos hospitalarios' },
        { Value: 'D02', Name: 'Gastos médicos por incapacidad o discapacidad' },
        { Value: 'D03', Name: 'Gastos funerales' },
        { Value: 'D04', Name: 'Donativos' },
        { Value: 'D05', Name: 'Intereses reales efectivamente pagados por créditos hipotecarios' },
        { Value: 'D06', Name: 'Aportaciones voluntarias al SAR' },
        { Value: 'D07', Name: 'Transporte escolar' },
        { Value: 'D08', Name: 'Depósitos en cuenta de ahorro' },
        { Value: 'D09', Name: 'Pagos por servicios educativos' },
        { Value: 'D10', Name: 'Por definir' }
    ];
    
    opcionesPorDefecto.forEach(uso => {
        const option = document.createElement('option');
        option.value = `${uso.Value} - ${uso.Name}`;
        option.textContent = `${uso.Value} - ${uso.Name}`;
        
        // Marcar como seleccionado si coincide con el valor actual
        if (option.value === '{{ $datos["receptor"]["uso_cfdi"] ?? "" }}') {
            option.selected = true;
        }
        
        selectUsoCFDI.appendChild(option);
    });
    
    console.log('Usos CFDI cargados desde opciones por defecto');
}

function obtenerMetodoPagoSeleccionado() {
    const selectMetodoPago = document.getElementById('metodo_pago');
    return {
        value: selectMetodoPago.value,
        text: selectMetodoPago.options[selectMetodoPago.selectedIndex].text
    };
}

function obtenerUsoCFDISeleccionado() {
    const selectUsoCFDI = document.getElementById('uso_cfdi');
    return {
        value: selectUsoCFDI.value,
        text: selectUsoCFDI.options[selectUsoCFDI.selectedIndex].text
    };
}

function obtenerRegimenFiscalSeleccionado() {
    const selectRegimenFiscal = document.getElementById('regimen_fiscal_receptor');
    return {
        value: selectRegimenFiscal.value,
        text: selectRegimenFiscal.options[selectRegimenFiscal.selectedIndex].text
    };
}

function agregarConcepto() {
    const tbody = document.getElementById('tablaConceptos');
    const newRow = document.createElement('tr');
    const rowIndex = tbody.children.length;
    
    newRow.innerHTML = `
        <td>
            <div class="input-group input-group-sm">
                <input type="text" class="form-control form-control-sm" onchange="actualizarTotales()">
                <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaProducto(${rowIndex})">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm cantidad" value="1" min="1" onchange="calcularImporte(this)">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <select class="form-select form-select-sm" onchange="actualizarTotales()">
                    <option value="H87">H87 - Pieza</option>
                    <option value="E48">E48 - Unidad de servicio</option>
                    <option value="EA">EA - Elemento</option>
                    <option value="KGM">KGM - Kilogramo</option>
                    <option value="MTR">MTR - Metro</option>
                </select>
                <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaUnidad(${rowIndex})">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm precio" value="0" step="0.01" min="0" onchange="calcularImporte(this)">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm importe" value="0" readonly>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm" onclick="eliminarConcepto(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    actualizarTotales();
}

function eliminarConcepto(button) {
    const row = button.closest('tr');
    row.remove();
    actualizarTotales();
}

function calcularImporte(input) {
    const row = input.closest('tr');
    const cantidad = parseFloat(row.querySelector('.cantidad').value) || 0;
    const precio = parseFloat(row.querySelector('.precio').value) || 0;
    const importe = cantidad * precio;
    row.querySelector('.importe').value = importe.toFixed(2);
    actualizarTotales();
}

function actualizarTotales() {
    let subtotal = 0;
    const filas = document.querySelectorAll('#tablaConceptos tr');
    
    filas.forEach(fila => {
        const importe = fila.querySelector('.importe');
        if (importe && importe.value) {
            subtotal += parseFloat(importe.value.replace(/[^0-9.-]+/g, ''));
        }
    });

    const iva = subtotal * 0.16;
    const total = subtotal + iva;

    document.getElementById('subtotal').textContent = formatearPrecio(subtotal);
    document.getElementById('iva').textContent = formatearPrecio(iva);
    document.getElementById('total').textContent = formatearPrecio(total);
    
    // Actualizar total en letras
    actualizarTotalEnLetras();
}

// Event listeners cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado');
    console.log('ModalManager disponible:', typeof modalManager !== 'undefined');
    
    // Cargar métodos de pago al iniciar la página
    cargarMetodosPago();
    
    // Cargar usos CFDI al iniciar la página
    cargarUsosCFDI();
    
    // Cargar regímenes fiscales al iniciar la página
    cargarRegimenesFiscales();
    
    // Evento para la tecla Enter en el campo de búsqueda de productos
    const searchProducto = document.getElementById('searchProducto');
    if (searchProducto) {
        searchProducto.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarProducto();
            }
        });
    }

    // Evento para la tecla Enter en el campo de búsqueda de unidades
    const searchUnidad = document.getElementById('searchUnidad');
    if (searchUnidad) {
        searchUnidad.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarUnidad();
            }
        });
    }

    // Evento para la tecla Enter en el campo de búsqueda de formas de pago
    const searchFormaPago = document.getElementById('searchFormaPago');
    if (searchFormaPago) {
        searchFormaPago.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarFormaPago();
            }
        });
    }

    // Verificar si las funciones están disponibles
    console.log('abrirBusquedaProducto:', typeof abrirBusquedaProducto);
    console.log('buscarProducto:', typeof buscarProducto);
    console.log('modalManager:', typeof modalManager);
});

// Función para limpiar el modal de unidades cuando se cierra
$('#modalBuscarUnidad').on('hidden.bs.modal', function () {
    document.getElementById('searchUnidad').value = '';
    document.getElementById('resultadosBusquedaUnidad').innerHTML = '';
});

// Función para limpiar el modal de formas de pago cuando se cierra
$('#modalBuscarFormaPago').on('hidden.bs.modal', function () {
    document.getElementById('searchFormaPago').value = '';
    document.getElementById('resultadosBusquedaFormaPago').innerHTML = '';
});

// Función para manejar errores de la API
function handleApiError(error) {
    console.error('Error:', error);
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Ocurrió un error al buscar productos. Por favor, intente nuevamente.',
    });
}

// Función para formatear el precio con dos decimales
function formatearPrecio(precio) {
    return parseFloat(precio).toFixed(2);
}

// Función para calcular el importe de un concepto
function calcularImporte(input) {
    const fila = input.closest('tr');
    const cantidad = parseFloat(fila.querySelector('.cantidad').value) || 0;
    const precio = parseFloat(fila.querySelector('.precio').value) || 0;
    const importe = cantidad * precio;
    
    fila.querySelector('.importe').value = formatearPrecio(importe);
    actualizarTotales();
}

// Función para eliminar un concepto
function eliminarConcepto(button) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Se eliminará este concepto de la factura",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            button.closest('tr').remove();
            actualizarTotales();
        }
    });
}

function cargarRegimenesFiscales() {
    console.log('Iniciando carga de regímenes fiscales...');
    console.log('Valor actual del régimen fiscal:', '{{ $datos["receptor"]["regimen_fiscal"] ?? "" }}');
    
    fetch('/buscar-regimen-fiscal')
        .then(response => {
            console.log('Respuesta de la API de regímenes fiscales:', response.status, response.statusText);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos de la API de regímenes fiscales:', data);
            
            const selectRegimenFiscal = document.getElementById('regimen_fiscal_receptor');
            
            // Limpiar opciones existentes excepto la primera
            selectRegimenFiscal.innerHTML = '<option value="">Seleccionar régimen fiscal</option>';
            
            // Verificar si hay datos y no es un error
            if (Array.isArray(data) && data.length > 0) {
                // Agregar opciones dinámicamente
                data.forEach(regimen => {
                    const option = document.createElement('option');
                    option.value = `${regimen.Value} - ${regimen.Name}`;
                    option.textContent = `${regimen.Value} - ${regimen.Name}`;
                    
                    // Marcar como seleccionado si coincide con el valor actual
                    if (option.value === '{{ $datos["receptor"]["regimen_fiscal"] ?? "" }}') {
                        option.selected = true;
                    }
                    
                    selectRegimenFiscal.appendChild(option);
                });
                
                console.log('Regímenes fiscales cargados exitosamente desde la API:', data.length, 'opciones');
            } else {
                console.warn('No se obtuvieron datos válidos de regímenes fiscales de la API, cargando opciones por defecto');
                console.log('Tipo de datos recibidos:', typeof data);
                console.log('Contenido de datos:', data);
                cargarRegimenesFiscalesPorDefecto();
            }
        })
        .catch(error => {
            console.error('Error al cargar regímenes fiscales:', error);
            console.error('Detalles del error:', error.message);
            cargarRegimenesFiscalesPorDefecto();
        });
}

function cargarRegimenesFiscalesPorDefecto() {
    console.log('Cargando regímenes fiscales desde opciones por defecto...');
    console.log('Valor actual del régimen fiscal:', '{{ $datos["receptor"]["regimen_fiscal"] ?? "" }}');
    
    const selectRegimenFiscal = document.getElementById('regimen_fiscal_receptor');
    selectRegimenFiscal.innerHTML = '<option value="">Seleccionar régimen fiscal</option>';
    
    const opcionesPorDefecto = [
        { Value: '601', Name: 'General de Ley Personas Morales' },
        { Value: '603', Name: 'Personas Morales con Fines no Lucrativos' },
        { Value: '605', Name: 'Sueldos y Salarios e Ingresos Asimilados a Salarios' },
        { Value: '606', Name: 'Arrendamiento' },
        { Value: '608', Name: 'Demás ingresos' },
        { Value: '609', Name: 'Consolidación' },
        { Value: '610', Name: 'Residentes en el Extranjero sin Establecimiento Permanente en México' },
        { Value: '611', Name: 'Ingresos por Dividendos (socios y accionistas)' },
        { Value: '612', Name: 'Personas Físicas con Actividades Empresariales y Profesionales' },
        { Value: '614', Name: 'Ingresos por intereses' },
        { Value: '616', Name: 'Sin obligaciones fiscales' },
        { Value: '620', Name: 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos' },
        { Value: '621', Name: 'Incorporación Fiscal' },
        { Value: '622', Name: 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras' },
        { Value: '623', Name: 'Opcional para Grupos de Sociedades' },
        { Value: '624', Name: 'Coordinados' },
        { Value: '625', Name: 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas' },
        { Value: '626', Name: 'Régimen Simplificado de Confianza' }
    ];
    
    opcionesPorDefecto.forEach(regimen => {
        const option = document.createElement('option');
        option.value = `${regimen.Value} - ${regimen.Name}`;
        option.textContent = `${regimen.Value} - ${regimen.Name}`;
        
        // Marcar como seleccionado si coincide con el valor actual
        if (option.value === '{{ $datos["receptor"]["regimen_fiscal"] ?? "" }}') {
            option.selected = true;
        }
        
        selectRegimenFiscal.appendChild(option);
    });
    
    console.log('Regímenes fiscales cargados desde opciones por defecto');
}

// Función para manejar el checkbox de concepto general
function toggleConceptoGeneral() {
    const checkbox = document.getElementById('habilitar_concepto_general');
    const container = document.getElementById('concepto_general_container');
    const tablaConceptos = document.getElementById('tablaConceptos').closest('.card');
    
    if (checkbox && checkbox.checked) {
        container.style.display = 'block';
        
        // Ocultar la tabla de conceptos principal
        tablaConceptos.style.display = 'none';
        
        // Obtener el subtotal de la tabla de conceptos principal
        const subtotalPrincipal = parseFloat(document.getElementById('subtotal').textContent.replace(/[$,]/g, '')) || 0;
        
        // Llenar automáticamente el campo "Precio U" de la primera fila con el subtotal
        const primeraFila = document.querySelector('.concepto-general-fila');
        if (primeraFila) {
            const precioInput = primeraFila.querySelector('.precio-concepto-general');
            const cantidadInput = primeraFila.querySelector('.cantidad-concepto-general');
            const importeInput = primeraFila.querySelector('.importe-concepto-general');
            
            if (precioInput && cantidadInput && importeInput) {
                precioInput.value = subtotalPrincipal.toFixed(2);
                
                // Calcular automáticamente el importe (cantidad * precio)
                const cantidad = parseFloat(cantidadInput.value) || 1;
                const importe = cantidad * subtotalPrincipal;
                importeInput.value = formatearPrecio(importe);
                
                // Actualizar los totales del concepto general
                actualizarTotalesConceptoGeneral();
            }
        }
        
        // Mostrar mensaje informativo
        Swal.fire({
            icon: 'info',
            title: 'Conceptos Generales Habilitados',
            html: `
                <div class="text-start">
                    <p><strong>Se han habilitado los conceptos generales.</strong></p>
                    <p>El campo "Precio U" de la primera fila se ha llenado automáticamente con el subtotal de la tabla principal: <strong>$${subtotalPrincipal.toFixed(2)}</strong></p>
                    <p class="text-muted small">Puede modificar este valor si es necesario y agregar más conceptos usando el botón "Agregar Concepto".</p>
                </div>
            `,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3085d6'
        });
        
    } else {
        container.style.display = 'none';
        
        // Limpiar todas las filas del concepto general
        const filas = document.querySelectorAll('.concepto-general-fila');
        filas.forEach(fila => {
            const productoInput = fila.querySelector('.concepto-general-producto');
            const cantidadInput = fila.querySelector('.cantidad-concepto-general');
            const unidadSelect = fila.querySelector('.concepto-general-unidad');
            const precioInput = fila.querySelector('.precio-concepto-general');
            const importeInput = fila.querySelector('.importe-concepto-general');
            const textarea = fila.querySelector('.concepto-general-textarea');
            
            if (productoInput) productoInput.value = 'Buscar producto SAT';
            if (cantidadInput) cantidadInput.value = '1';
            if (unidadSelect) {
                unidadSelect.innerHTML = `
                    <option value="H87">H87 - Pieza</option>
                    <option value="E48">E48 - Unidad de servicio</option>
                    <option value="EA">EA - Elemento</option>
                    <option value="KGM">KGM - Kilogramo</option>
                    <option value="MTR">MTR - Metro</option>
                `;
            }
            if (precioInput) precioInput.value = '0';
            if (importeInput) importeInput.value = '0';
            if (textarea) textarea.value = '';
        });
        
        // Limpiar totales
        document.getElementById('subtotal_concepto_general').textContent = '0.00';
        document.getElementById('iva_concepto_general').textContent = '0.00';
        document.getElementById('total_concepto_general').textContent = '0.00';
        
        // Mostrar la tabla de conceptos principal
        tablaConceptos.style.display = 'block';
    }
}

// Event listener para el checkbox de concepto general
document.addEventListener('DOMContentLoaded', function() {
    const checkboxConceptoGeneral = document.getElementById('habilitar_concepto_general');
    if (checkboxConceptoGeneral) {
        checkboxConceptoGeneral.addEventListener('change', toggleConceptoGeneral);
    }
});

// Función para calcular el importe del concepto general (actualizada para filas dinámicas)
function calcularImporteConceptoGeneral(input) {
    const fila = input.closest('.concepto-general-fila');
    const cantidad = parseFloat(fila.querySelector('.cantidad-concepto-general').value) || 0;
    const precio = parseFloat(fila.querySelector('.precio-concepto-general').value) || 0;
    const importe = cantidad * precio;
    
    fila.querySelector('.importe-concepto-general').value = formatearPrecio(importe);
    actualizarTotalesConceptoGeneral();
}

// Función para actualizar los totales del concepto general
function actualizarTotalesConceptoGeneral() {
    const filas = document.querySelectorAll('.concepto-general-fila');
    let subtotal = 0;
    
    filas.forEach(fila => {
        const importeInput = fila.querySelector('.importe-concepto-general');
        const importe = parseFloat(importeInput.value.replace(/[$,]/g, '')) || 0;
        subtotal += importe;
    });
    
    const iva = subtotal * 0.16;
    const total = subtotal + iva;

    document.getElementById('subtotal_concepto_general').textContent = formatearPrecio(subtotal);
    document.getElementById('iva_concepto_general').textContent = formatearPrecio(iva);
    document.getElementById('total_concepto_general').textContent = formatearPrecio(total);
}

// Función para agregar nueva fila de concepto general
function agregarFilaConceptoGeneral() {
    const tbody = document.getElementById('concepto_general_tbody');
    const filas = tbody.querySelectorAll('.concepto-general-fila');
    const nuevaFilaNumero = filas.length + 1;
    
    const esIntegracion = {{ strtolower($datos['servicio']['tipo']) == 'integracion' || strtolower($datos['servicio']['tipo']) == 'integración' ? 'true' : 'false' }};
    
    let nuevaFila = `
        <tr class="concepto-general-fila" data-fila="${nuevaFilaNumero}">
            <td>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm concepto-general-producto" 
                           value="Buscar producto SAT" onchange="actualizarTotalesConceptoGeneral()">
                    <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaProductoConceptoGeneral(this)">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm cantidad-concepto-general" 
                       value="1" min="1" onchange="calcularImporteConceptoGeneral(this)">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <select class="form-select form-select-sm concepto-general-unidad" onchange="actualizarTotalesConceptoGeneral()">
                        <option value="H87">H87 - Pieza</option>
                        <option value="E48">E48 - Unidad de servicio</option>
                        <option value="EA">EA - Elemento</option>
                        <option value="KGM">KGM - Kilogramo</option>
                        <option value="MTR">MTR - Metro</option>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaUnidadConceptoGeneral(this)">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </td>`;
    
    if (esIntegracion) {
        nuevaFila += `
            <td>
                <textarea class="form-control form-control-sm concepto-general-textarea" name="concepto_general[]" rows="3" 
                          placeholder="Ingrese aquí el concepto general..."></textarea>
            </td>`;
    }
    
    nuevaFila += `
            <td>
                <input type="number" class="form-control form-control-sm precio-concepto-general" 
                       value="0" step="0.01" min="0" onchange="calcularImporteConceptoGeneral(this)">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm importe-concepto-general" 
                       value="0" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarFilaConceptoGeneral(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    
    tbody.insertAdjacentHTML('beforeend', nuevaFila);
}

// Función para eliminar fila de concepto general
function eliminarFilaConceptoGeneral(btn) {
    const fila = btn.closest('.concepto-general-fila');
    const tbody = document.getElementById('concepto_general_tbody');
    const filas = tbody.querySelectorAll('.concepto-general-fila');
    
    // No permitir eliminar si solo hay una fila
    if (filas.length <= 1) {
        alert('Debe mantener al menos un concepto.');
        return;
    }
    
    fila.remove();
    actualizarTotalesConceptoGeneral();
}

// Función para calcular el importe del concepto general (actualizada para filas dinámicas)
function calcularImporteConceptoGeneral(input) {
    const fila = input.closest('.concepto-general-fila');
    const cantidad = parseFloat(fila.querySelector('.cantidad-concepto-general').value) || 0;
    const precio = parseFloat(fila.querySelector('.precio-concepto-general').value) || 0;
    const importe = cantidad * precio;
    
    fila.querySelector('.importe-concepto-general').value = formatearPrecio(importe);
    actualizarTotalesConceptoGeneral();
}

// Función para actualizar el total en letras dinámicamente
function actualizarTotalEnLetras() {
    const checkboxConceptoGeneral = document.getElementById('habilitar_concepto_general');
    const conceptoGeneralHabilitado = checkboxConceptoGeneral ? checkboxConceptoGeneral.checked : false;
    
    let total = 0;
    
    if (conceptoGeneralHabilitado) {
        // Usar el total del concepto general
        total = parseFloat(document.getElementById('total_concepto_general').textContent) || 0;
    } else {
        // Usar el total de la tabla principal
        total = parseFloat(document.getElementById('total').textContent) || 0;
    }
    
    // Convertir el número a letras usando la función PHP
    const totalEnLetras = numeroALetras(total);
    document.getElementById('total_letras_dinamico').textContent = totalEnLetras;
}

// Función JavaScript equivalente a numeroALetras de PHP
function numeroALetras(numero) {
    numero = numero.toString().replace(/[$,]/g, '');
    numero = parseFloat(numero);
    
    const unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    const decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    const centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    
    const parteEntera = Math.floor(numero);
    const parteDecimal = Math.round((numero - parteEntera) * 100);
    
    let letras = '';
    
    if (parteEntera === 0) {
        letras = 'CERO';
    } else {
        if (parteEntera > 999) {
            const miles = Math.floor(parteEntera / 1000);
            if (miles === 1) {
                letras += 'MIL ';
            } else if (unidades[miles]) {
                letras += unidades[miles] + ' MIL ';
            } else {
                letras += 'MIL ';
            }
            parteEntera = parteEntera % 1000;
        }
        
        if (parteEntera > 99) {
            const centenaIndex = Math.floor(parteEntera / 100);
            if (centenas[centenaIndex]) {
                letras += centenas[centenaIndex] + ' ';
            }
            parteEntera = parteEntera % 100;
        }
        
        if (parteEntera > 9) {
            const decenaIndex = Math.floor(parteEntera / 10);
            if (decenas[decenaIndex]) {
                letras += decenas[decenaIndex] + ' ';
            }
            parteEntera = parteEntera % 10;
        }
        
        if (parteEntera > 0) {
            if (unidades[parteEntera]) {
                letras += unidades[parteEntera];
            }
        }
    }
    
    return letras.trim() + ' PESOS ' + parteDecimal.toString().padStart(2, '0') + '/100 M.N.';
}

// Funciones para el conversor de moneda
function abrirConversorMoneda() {
    // Cargar tipo de cambio al abrir el modal
    actualizarTipoCambio();
    
    // Configurar event listeners para los radio buttons
    document.getElementById('usdToMxn').addEventListener('change', cambiarTipoConversion);
    document.getElementById('mxnToUsd').addEventListener('change', cambiarTipoConversion);
    
    $('#modalConversorMoneda').modal('show');
}

function cambiarTipoConversion() {
    const esUsdToMxn = document.getElementById('usdToMxn').checked;
    const labelCantidadOrigen = document.getElementById('labelCantidadOrigen');
    const simboloOrigen = document.getElementById('simboloOrigen');
    const simboloTipoCambio = document.getElementById('simboloTipoCambio');
    const cantidadOrigen = document.getElementById('cantidadOrigen');
    
    if (esUsdToMxn) {
        labelCantidadOrigen.textContent = 'Cantidad en USD:';
        simboloOrigen.textContent = '$';
        simboloTipoCambio.textContent = 'MXN/USD';
        cantidadOrigen.placeholder = '0.00 USD';
    } else {
        labelCantidadOrigen.textContent = 'Cantidad en MXN:';
        simboloOrigen.textContent = '$';
        simboloTipoCambio.textContent = 'USD/MXN';
        cantidadOrigen.placeholder = '0.00 MXN';
    }
    
    // Limpiar campos y reconvertir si hay valores
    cantidadOrigen.value = '';
    document.getElementById('resultadoConversion').textContent = esUsdToMxn ? '$0.00 MXN' : '$0.00 USD';
}

function actualizarTipoCambio() {
    // Mostrar indicador de carga
    const tipoCambioInput = document.getElementById('tipoCambio');
    const fechaActualizacion = document.getElementById('fechaActualizacion');
    
    tipoCambioInput.value = '';
    fechaActualizacion.textContent = 'Cargando...';
    
    // Usar la API de exchangerate-api.com (gratuita)
    fetch('https://api.exchangerate-api.com/v4/latest/USD')
        .then(response => response.json())
        .then(data => {
            if (data.rates && data.rates.MXN) {
                const tipoCambio = data.rates.MXN;
                tipoCambioInput.value = tipoCambio.toFixed(4);
                fechaActualizacion.textContent = new Date().toLocaleString('es-MX');
                
                // Convertir si hay cantidad ingresada
                if (document.getElementById('cantidadOrigen').value) {
                    convertirMoneda();
                }
                
                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Tipo de cambio actualizado',
                    text: `USD/MXN: ${tipoCambio.toFixed(4)}`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            } else {
                throw new Error('No se pudo obtener el tipo de cambio');
            }
        })
        .catch(error => {
            console.error('Error al obtener tipo de cambio:', error);
            
            // Usar tipo de cambio por defecto (aproximado)
            tipoCambioInput.value = '17.50';
            fechaActualizacion.textContent = 'Tipo de cambio por defecto';
            
            Swal.fire({
                icon: 'warning',
                title: 'Error al obtener tipo de cambio',
                text: 'Se está usando un tipo de cambio por defecto. Puede actualizarlo manualmente.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000
            });
        });
}

function convertirMoneda() {
    const cantidadOrigen = parseFloat(document.getElementById('cantidadOrigen').value) || 0;
    const tipoCambio = parseFloat(document.getElementById('tipoCambio').value) || 0;
    const esUsdToMxn = document.getElementById('usdToMxn').checked;
    
    if (cantidadOrigen > 0 && tipoCambio > 0) {
        let resultado;
        if (esUsdToMxn) {
            // USD → MXN
            resultado = cantidadOrigen * tipoCambio;
            document.getElementById('resultadoConversion').textContent = `$${formatearPrecio(resultado)} MXN`;
        } else {
            // MXN → USD
            resultado = cantidadOrigen / tipoCambio;
            document.getElementById('resultadoConversion').textContent = `$${formatearPrecio(resultado)} USD`;
        }
    } else {
        const textoResultado = esUsdToMxn ? '$0.00 MXN' : '$0.00 USD';
        document.getElementById('resultadoConversion').textContent = textoResultado;
    }
}

function aplicarConversion() {
    const cantidadOrigen = parseFloat(document.getElementById('cantidadOrigen').value) || 0;
    const tipoCambio = parseFloat(document.getElementById('tipoCambio').value) || 0;
    const esUsdToMxn = document.getElementById('usdToMxn').checked;
    
    if (cantidadOrigen <= 0) {
        const moneda = esUsdToMxn ? 'USD' : 'MXN';
        Swal.fire({
            icon: 'error',
            title: 'Cantidad inválida',
            text: `Por favor, ingrese una cantidad válida en ${moneda}.`,
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    if (tipoCambio <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Tipo de cambio inválido',
            text: 'Por favor, ingrese un tipo de cambio válido.',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    let resultado;
    if (esUsdToMxn) {
        resultado = cantidadOrigen * tipoCambio;
    } else {
        resultado = cantidadOrigen / tipoCambio;
    }
    
    // Preguntar al usuario qué hacer con la conversión
    const monedaOrigen = esUsdToMxn ? 'USD' : 'MXN';
    const monedaDestino = esUsdToMxn ? 'MXN' : 'USD';
    
    Swal.fire({
        title: '¿Qué desea hacer con la conversión?',
        html: `
            <div class="text-start">
                <p><strong>Cantidad en ${monedaOrigen}:</strong> $${formatearPrecio(cantidadOrigen)}</p>
                <p><strong>Tipo de cambio:</strong> ${tipoCambio.toFixed(4)} ${esUsdToMxn ? 'MXN/USD' : 'USD/MXN'}</p>
                <p><strong>Resultado en ${monedaDestino}:</strong> $${formatearPrecio(resultado)}</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: `Cambiar a ${monedaDestino}`,
        denyButtonText: `Cambiar a ${monedaOrigen}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Cambiar moneda a la moneda destino y actualizar totales
            if (esUsdToMxn) {
                document.getElementById('moneda').value = 'MXN - Peso Mexicano';
            } else {
                document.getElementById('moneda').value = 'USD - Dolar americano';
            }
            
            // Actualizar el total principal si no está habilitado el concepto general
            const checkboxConceptoGeneral = document.getElementById('habilitar_concepto_general');
            if (!checkboxConceptoGeneral || !checkboxConceptoGeneral.checked) {
                document.getElementById('total').textContent = formatearPrecio(resultado);
                document.querySelector('input[name="total"]').value = resultado.toString();
                
                // Calcular subtotal e IVA
                const subtotal = resultado / 1.16;
                const iva = resultado - subtotal;
                
                document.getElementById('subtotal').textContent = formatearPrecio(subtotal);
                document.getElementById('iva').textContent = formatearPrecio(iva);
                document.querySelector('input[name="subtotal"]').value = subtotal.toString();
                document.querySelector('input[name="iva"]').value = iva.toString();
            } else {
                // Actualizar el total del concepto general
                document.getElementById('total_concepto_general').textContent = formatearPrecio(resultado);
                document.getElementById('subtotal_concepto_general').textContent = formatearPrecio(resultado / 1.16);
                document.getElementById('iva_concepto_general').textContent = formatearPrecio(resultado - (resultado / 1.16));
            }
            
            $('#modalConversorMoneda').modal('hide');
            
            Swal.fire({
                icon: 'success',
                title: 'Conversión aplicada',
                text: `La factura ahora está en ${monedaDestino === 'MXN' ? 'pesos mexicanos' : 'dólares americanos'}.`,
                confirmButtonText: 'Aceptar'
            });
        } else if (result.isDenied) {
            // Cambiar moneda a la moneda origen
            if (esUsdToMxn) {
                document.getElementById('moneda').value = 'USD - Dolar americano';
            } else {
                document.getElementById('moneda').value = 'MXN - Peso Mexicano';
            }
            $('#modalConversorMoneda').modal('hide');
            
            Swal.fire({
                icon: 'success',
                title: 'Moneda cambiada',
                text: `La factura ahora está en ${monedaOrigen === 'USD' ? 'dólares americanos' : 'pesos mexicanos'}.`,
                confirmButtonText: 'Aceptar'
            });
        }
    });
}

// Event listener para actualizar tipo de cambio automáticamente al cargar la página
// document.addEventListener('DOMContentLoaded', function() {
//     // Cargar tipo de cambio inicial
//     setTimeout(() => {
//         if (document.getElementById('tipoCambio')) {
//             actualizarTipoCambio();
//         }
//     }, 1000);
// });

// Función para mostrar detalles técnicos del error
function mostrarDetallesError() {
    const errorContent = document.querySelector('.error-content');
    if (errorContent) {
        const errorText = errorContent.innerHTML;
        
        Swal.fire({
            title: 'Detalles Técnicos del Error',
            html: `
                <div class="text-start">
                    <div class="alert alert-info">
                        <strong>Información del Error:</strong><br>
                        Este error fue generado por el servicio de facturación del SAT. 
                        Los detalles técnicos se muestran a continuación:
                    </div>
                    <div class="mt-3">
                        <strong>Mensaje de Error:</strong>
                        <div class="bg-light p-3 rounded mt-2" style="font-family: monospace; font-size: 0.9em;">
                            ${errorText}
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Si el problema persiste, contacte al administrador del sistema.
                        </small>
                    </div>
                </div>
            `,
            icon: 'info',
            width: '600px',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#3085d6'
        });
    }
}

// Función para cerrar automáticamente el alert de error después de un tiempo
document.addEventListener('DOMContentLoaded', function() {
    const errorAlert = document.querySelector('.alert-danger');
    if (errorAlert) {
        // Auto-cerrar después de 30 segundos
        setTimeout(() => {
            if (errorAlert && errorAlert.parentNode) {
                errorAlert.style.transition = 'opacity 0.5s ease-out';
                errorAlert.style.opacity = '0';
                setTimeout(() => {
                    if (errorAlert.parentNode) {
                        errorAlert.remove();
                    }
                }, 500);
            }
        }, 30000);
    }
});
</script>

<style>
/* Estilos personalizados para el módulo de errores */
.alert-danger {
    border-left: 4px solid #dc3545;
    background: linear-gradient(135deg, #fff5f5 0%, #ffe6e6 100%);
}

.alert-danger .alert-heading {
    color: #721c24;
    font-weight: 600;
}

.error-content {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 8px;
    padding: 15px;
    margin: 10px 0;
    border-left: 3px solid #dc3545;
}

.error-content strong {
    color: #721c24;
}

.error-content ul {
    margin-bottom: 0;
    padding-left: 20px;
}

.error-content li {
    margin-bottom: 5px;
    color: #721c24;
}

.btn-close {
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

.btn-close:hover {
    opacity: 1;
}

/* Animación de entrada para el alert */
.alert {
    animation: slideInDown 0.5s ease-out;
}

@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Estilos para el modal de detalles técnicos */
.swal2-popup {
    font-size: 0.9em;
}

.swal2-popup .bg-light {
    background-color: #f8f9fa !important;
    border: 1px solid #dee2e6;
}

.swal2-popup .text-start {
    text-align: left;
}
</style>
@endsection 