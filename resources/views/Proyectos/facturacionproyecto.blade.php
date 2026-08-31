@extends('layouts.app')
<meta name="csrf-token" content="{{ csrf_token() }}">
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
                                Error al Timbrar Factura de Proyecto
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
        $folioProyecto = $proyecto->folio ?? '';
        $numeroDivision = $partida->plazo ?? '';
        $folioBusqueda = $folioProyecto . '-DIV' . $numeroDivision;
        
        $facturasTimbradas = \App\Models\facturacionproductos::where('folio', 'like', $folioBusqueda . '%')->orderBy('id', 'desc')->get();
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
                                    Factura de proyecto ya timbrada
                                </h5>
                                <p class="mb-2">
                                    <strong>Esta factura de proyecto ya ha sido timbrada anteriormente.</strong> 
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
            <h5 class="modal-title" id="modalFacturasTimbradasLabel">Facturas de Proyecto Timbradas Relacionadas</h5>
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
    <div class="row">
        <div class="col-lg-3 col-12 start-center">
            <h3 class="mt-1 animate_animated animate_backInLeft">Facturación de Proyecto</h3>
            <span class="p-0 m-0 d-none d-md-block fs-8">Generación de factura para proyecto y partida específica.</span>
        </div>

        <div class="col-lg-9 col-12 center-end">
            <a href="{{ route('proyectos.index') }}" class="btn btn-secondary fs-7 mb-2">
                <i class="fa-solid fa-arrow-left"></i> Volver a Proyectos
            </a>
        </div>
    </div>

    <!-- Información del Proyecto y Partida -->
    <div class="row mt-3 mb-3">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="mb-1">Cliente</h6>
                            <h5 class="mb-0">{{ $cliente->nombre ?? 'N/A' }}</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Folio del Proyecto</h6>
                            <h5 class="mb-0">{{ $proyecto->folio ?? 'N/A' }}</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">División #{{ $partida->plazo ?? 'N/A' }}</h6>
                            <h5 class="mb-0">${{ number_format($partida->monto ?? 0, 2) }}</h5>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-1">Fecha</h6>
                            <h5 class="mb-0">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fa-solid fa-file-invoice"></i> Formulario de Facturación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fa-solid fa-info-circle"></i>
                        <strong>Información del Proyecto:</strong> 
                        <ul class="mb-0 mt-2">
                            <li><strong>Proyecto:</strong> {{ $proyecto->nombre ?? 'N/A' }}</li>
                            <li><strong>Cliente:</strong> {{ $cliente->nombre ?? 'N/A' }}</li>
                            <li><strong>División:</strong> #{{ $partida->plazo ?? 'N/A' }} - ${{ number_format($partida->monto ?? 0, 2) }}</li>
                            <li><strong>Fecha Inicio División:</strong> {{ \Carbon\Carbon::parse($partida->otros_conceptos1 ?? now())->format('d/m/Y') }}</li>
                            <li><strong>Fecha Fin División:</strong> {{ \Carbon\Carbon::parse($partida->otros_concetpos2 ?? now())->format('d/m/Y') }}</li>
                        </ul>
                    </div>
                    
                    <!-- Información de Partidas -->
                    @if($partidas->count() > 0)
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fa-solid fa-list"></i> Partidas Registradas ({{ $partidas->count() }})
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead class="table-success">
                                        <tr>
                                            <th>#</th>
                                            <th>Tipo de Ingreso</th>
                                            <th>Tipo de Operación</th>
                                            <th>Monto</th>
                                            <th>Cantidad</th>
                                            <th>Producto</th>
                                            <th>Empleado</th>
                                            <th>Mano Externa</th>
                                            <th>Documento</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($partidas as $index => $partida)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>
                                                <span class="badge bg-primary">{{ $partida->tipo_ingreso }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $partida->tipo_operacion }}</span>
                                            </td>
                                            <td class="text-end">
                                                <strong>${{ number_format($partida->monto_ingreso, 2) }}</strong>
                                            </td>
                                            <td class="text-center">
                                                @if($partida->cantidadsumi)
                                                    <span class="badge bg-secondary">{{ $partida->cantidadsumi }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($partida->id_producto)
                                                    @php
                                                        $producto = \App\Models\Producto::find($partida->id_producto);
                                                    @endphp
                                                    @if($producto)
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-box text-primary me-2"></i>
                                                            <div>
                                                                <strong>{{ $producto->nombre }}</strong>
                                                                @if($producto->sku)
                                                                    <br><small class="text-muted">SKU: {{ $producto->sku }}</small>
                                                                @endif
                                                                @if($producto->descripcion)
                                                                    <br><small class="text-muted">{{ Str::limit($producto->descripcion, 50) }}</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @else
                                                        <span class="text-warning">Producto no encontrado (ID: {{ $partida->id_producto }})</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($partida->flagempleado)
                                                    @php
                                                        $empleado = \App\Models\Empleado::find($partida->flagempleado);
                                                    @endphp
                                                    @if($empleado)
                                                        <span class="badge bg-success">
                                                            {{ $empleado->primer_nombre ?? '' }} {{ $empleado->apellido_paterno ?? '' }}
                                                        </span>
                                                    @else
                                                        <span class="text-warning">Empleado no encontrado</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($partida->manooexterna)
                                                    <span class="badge bg-warning text-dark">{{ $partida->manooexterna }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($partida->ruta_documento)
                                                    <a href="{{ route('proyectos.documento', $partida->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       target="_blank"
                                                       title="Ver documento">
                                                        <i class="fas fa-file-alt"></i>
                                                        Ver
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($partida->created_at)->format('d/m/Y H:i') }}
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                @if(strtoupper($partida->tipo_ingreso) === 'SUMINISTRO' && $partida->id_producto)
                                                    <button type="button" 
                                                            class="btn btn-warning btn-sm" 
                                                            onclick="bajarInventario({{ $partida->id }}, {{ $partida->id_producto }}, '{{ $partida->tipo_ingreso }}', {{ $partida->cantidadsumi ?? 1 }})"
                                                            title="Bajar inventario del producto">
                                                        <i class="fas fa-arrow-down"></i>
                                                        Bajar Inventario
                                                    </button>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td class="text-end">
                                                <strong class="text-success">${{ number_format($partidas->sum('monto_ingreso'), 2) }}</strong>
                                            </td>
                                            <td colspan="7"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <!-- Resumen por Tipo de Ingreso -->
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Resumen por Tipo de Ingreso</h6>
                                        </div>
                                        <div class="card-body">
                                            @php
                                                $resumenPorTipo = $partidas->groupBy('tipo_ingreso')->map(function($grupo) {
                                                    return [
                                                        'cantidad' => $grupo->count(),
                                                        'total' => $grupo->sum('monto_ingreso')
                                                    ];
                                                });
                                            @endphp
                                            @foreach($resumenPorTipo as $tipo => $datos)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-primary">{{ $tipo }}</span>
                                                <div class="text-end">
                                                    <small class="text-muted">{{ $datos['cantidad'] }} partida(s)</small><br>
                                                    <strong>${{ number_format($datos['total'], 2) }}</strong>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Resumen por Tipo de Operación</h6>
                                        </div>
                                        <div class="card-body">
                                            @php
                                                $resumenPorOperacion = $partidas->groupBy('tipo_operacion')->map(function($grupo) {
                                                    return [
                                                        'cantidad' => $grupo->count(),
                                                        'total' => $grupo->sum('monto_ingreso')
                                                    ];
                                                });
                                            @endphp
                                            @foreach($resumenPorOperacion as $operacion => $datos)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-info">{{ $operacion }}</span>
                                                <div class="text-end">
                                                    <small class="text-muted">{{ $datos['cantidad'] }} partida(s)</small><br>
                                                    <strong>${{ number_format($datos['total'], 2) }}</strong>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        <strong>Atención:</strong> No hay partidas registradas para esta división.
                    </div>
                    @endif

                    <!-- Encabezado de Factura -->
                    <div class="row mb-4">
                        <div class="col-lg-6 col-12">
                            <h3 class="text-orange">FACTURA DE PROYECTO</h3>
                        </div>
                        <div class="col-lg-6 col-12 text-end">
                            <h4>FOLIO: <span class="text-orange">{{ $proyecto->folio ?? 'N/A' }}-DIV{{ $partida->plazo ?? 'N/A' }}</span></h4>
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
                                        <strong>Nombre:</strong> <span>UM Mining</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>RFC:</strong> <span>UMM-123456-ABC</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Dirección:</strong> <span>Av. Principal #123, Col. Centro</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Lugar de Expedición:</strong> <span>Monterrey, N.L.</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Régimen Fiscal:</strong> <span>601 - General de Ley Personas Morales</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Teléfono:</strong> <span id="telefono">(81) 1234-5678</span>
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
                                        <strong>Nombre:</strong> <span>{{ $cliente->nombre ?? 'N/A' }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>RFC:</strong> <span>{{ $cliente->rfc ?? 'XAXX010101000' }}</span>
                                        <input type="hidden" id="rfc_receptor" value="{{ $cliente->rfc ?? 'XAXX010101000' }}">
                                    </div>
                                    <div class="mb-2">
                                        <strong>Dirección:</strong> <span>{{ $cliente->calle."  #".$cliente->numero_int." ".$cliente->colonia ?? 'Sin especificar' }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Código Postal:</strong> <span>{{ $cliente->cp ?? '64000' }}</span>
                                        <input type="hidden" id="cp_receptor" value="{{ $cliente->cp ?? '27000' }}">
                                    </div>
                                    <div class="mb-2">
                                        <strong>Uso del CFDI:</strong> 
                                        <div class="input-group input-group-sm">
                                            <select class="form-select" id="uso_cfdi-proyecto" name="uso_cfdi">
                                                <option value="">Seleccionar uso CFDI</option>
                                                <optgroup label="Gastos (G)">
                                                    <option value="G01 - Adquisición de mercancías">G01 - Adquisición de mercancías</option>
                                                    <option value="G02 - Devoluciones, descuentos o bonificaciones">G02 - Devoluciones, descuentos o bonificaciones</option>
                                                    <option value="G03 - Gastos en general" selected>G03 - Gastos en general</option>
                                                </optgroup>
                                                <optgroup label="Ingresos (I)">
                                                    <option value="I01 - Honorarios médicos, dentales y gastos hospitalarios">I01 - Honorarios médicos, dentales y gastos hospitalarios</option>
                                                    <option value="I02 - Gastos médicos por incapacidad o discapacidad">I02 - Gastos médicos por incapacidad o discapacidad</option>
                                                    <option value="I03 - Gastos funerales">I03 - Gastos funerales</option>
                                                    <option value="I04 - Donativos">I04 - Donativos</option>
                                                    <option value="I05 - Intereses reales efectivamente pagados por créditos hipotecarios">I05 - Intereses reales efectivamente pagados por créditos hipotecarios</option>
                                                    <option value="I06 - Aportaciones voluntarias al SAR">I06 - Aportaciones voluntarias al SAR</option>
                                                    <option value="I07 - Transporte escolar">I07 - Transporte escolar</option>
                                                    <option value="I08 - Depósitos en cuenta de ahorro">I08 - Depósitos en cuenta de ahorro</option>
                                                </optgroup>
                                                <optgroup label="Deducciones (D)">
                                                    <option value="D01 - Honorarios médicos, dentales y gastos hospitalarios">D01 - Honorarios médicos, dentales y gastos hospitalarios</option>
                                                    <option value="D02 - Gastos médicos por incapacidad o discapacidad">D02 - Gastos médicos por incapacidad o discapacidad</option>
                                                    <option value="D03 - Gastos funerales">D03 - Gastos funerales</option>
                                                    <option value="D04 - Donativos">D04 - Donativos</option>
                                                    <option value="D05 - Intereses reales efectivamente pagados por créditos hipotecarios">D05 - Intereses reales efectivamente pagados por créditos hipotecarios</option>
                                                    <option value="D06 - Aportaciones voluntarias al SAR">D06 - Aportaciones voluntarias al SAR</option>
                                                    <option value="D07 - Transporte escolar">D07 - Transporte escolar</option>
                                                    <option value="D08 - Depósitos en cuenta de ahorro">D08 - Depósitos en cuenta de ahorro</option>
                                                    <option value="D09 - Pagos por servicios educativos">D09 - Pagos por servicios educativos</option>
                                                    <option value="D10 - Por definir">D10 - Por definir</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Régimen Fiscal:</strong> 
                                        <div class="input-group input-group-sm">
                                            <select class="form-select" id="regimen_fiscal_receptor-proyecto" name="regimen_fiscal_receptor">
                                                <option value="">Seleccionar régimen fiscal</option>
                                            </select>
                                            <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaRegimenFiscalProyecto()">
                                                <i class="fas fa-search"></i>
                                            </button>
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
                                        <h5 class="card-title m-0">Conceptos del Proyecto</h5>
                                        <button type="button" class="btn btn-success btn-sm" onclick="agregarConceptoProyecto()">
                                            <i class="fas fa-plus"></i> Agregar Concepto
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Producto/Servicio</th>
                                                    <th>Cantidad</th>
                                                    <th>Unidad</th>
                                                    <th>Concepto(s)</th>
                                                    <th>Precio U</th>
                                                    <th>Importe</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaConceptosProyecto">
                                                <tr>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control form-control-sm" value="Servicios de Proyecto - División {{ $partida->plazo ?? 'N/A' }}" readonly>
                                                            <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaProductoProyecto(0)">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm cantidad-proyecto" value="1" min="1" onchange="calcularImporteProyecto(this)">
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <select class="form-select form-select-sm" onchange="actualizarTotalesProyecto()">
                                                                <option value="E48" selected>E48 - Unidad de servicio</option>
                                                                <option value="H87">H87 - Pieza</option>
                                                                <option value="EA">EA - Elemento</option>
                                                                <option value="KGM">KGM - Kilogramo</option>
                                                                <option value="MTR">MTR - Metro</option>
                                                            </select>
                                                            <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaUnidadProyecto(0)">
                                                                <i class="fas fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" value="Servicios profesionales de proyecto - División {{ $partida->plazo ?? 'N/A' }}" readonly>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm precio-proyecto" value="{{ $partidas->sum('monto_ingreso') }}" step="0.01" min="0" onchange="calcularImporteProyecto(this)" readonly>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm importe-proyecto" value="{{ $partidas->sum('monto_ingreso') }}" readonly>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarConceptoProyecto(this)" disabled>
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                                                    <td colspan="2">$<span id="subtotal-proyecto">{{ number_format(($partidas->sum('monto_ingreso') ?? 0) / 1.16, 2) }}</span></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="5" class="text-end"><strong>IVA 16%:</strong></td>
                                                    <td colspan="2">$<span id="iva-proyecto">{{ number_format(($partidas->sum('monto_ingreso') ?? 0) - (($partidas->sum('monto_ingreso') ?? 0) / 1.16), 2) }}</span></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                                    <td colspan="2">$<span id="total-proyecto">{{ number_format($partidas->sum('monto_ingreso') ?? 0, 2) }}</span></td>
                                                </tr>
                                            </tfoot>
                                        </table>
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
                                                    <select class="form-select" id="moneda-proyecto" name="moneda">
                                                        <option value="MXN - Peso Mexicano" selected>MXN - Peso Mexicano</option>
                                                        <option value="USD - Dolar americano">USD - Dolar americano</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                                                                 <div class="col-md-4">
                                             <div class="mb-3">
                                                 <strong>Forma de Pago:</strong> 
                                                 <select class="form-control form-control-sm" id="forma_pago-proyecto" name="forma_pago" required onchange="actualizarFormaPagoOculta(this.value)">
                                                     <option value="">Seleccionar forma de pago</option>
                                                     <option value="01 - Efectivo">01 - Efectivo</option>
                                                     <option value="02 - Cheque nominativo">02 - Cheque nominativo</option>
                                                     <option value="03 - Transferencia electrónica de fondos">03 - Transferencia electrónica de fondos</option>
                                                     <option value="04 - Tarjeta de crédito">04 - Tarjeta de crédito</option>
                                                     <option value="05 - Monedero electrónico">05 - Monedero electrónico</option>
                                                     <option value="06 - Dinero electrónico">06 - Dinero electrónico</option>
                                                     <option value="08 - Vales de despensa">08 - Vales de despensa</option>
                                                     <option value="12 - Dación en pago">12 - Dación en pago</option>
                                                     <option value="13 - Pago por subrogación">13 - Pago por subrogación</option>
                                                     <option value="14 - Pago por consignación">14 - Pago por consignación</option>
                                                     <option value="15 - Condonación">15 - Condonación</option>
                                                     <option value="17 - Compensación">17 - Compensación</option>
                                                     <option value="23 - Novación">23 - Novación</option>
                                                     <option value="24 - Confusión">24 - Confusión</option>
                                                     <option value="25 - Remisión de deuda">25 - Remisión de deuda</option>
                                                     <option value="26 - Prescripción o caducidad">26 - Prescripción o caducidad</option>
                                                     <option value="27 - A satisfacción del acreedor">27 - A satisfacción del acreedor</option>
                                                     <option value="28 - Tarjeta de débito">28 - Tarjeta de débito</option>
                                                     <option value="29 - Tarjeta de servicios">29 - Tarjeta de servicios</option>
                                                     <option value="30 - Aplicación de anticipos">30 - Aplicación de anticipos</option>
                                                     <option value="31 - Intermediarios">31 - Intermediarios</option>
                                                     <option value="99 - Por definir">99 - Por definir</option>
                                                 </select>
                                             </div>
                                         </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <strong>Método de Pago:</strong> 
                                                <div class="input-group input-group-sm">
                                                    <select class="form-select" id="metodo_pago-proyecto" name="metodo_pago">
                                                        <option value="">Seleccionar método de pago</option>
                                                        <option value="PPD">PPD - Pago en parcialidades ó diferido</option>
                                                        <option value="PUE" selected>PUE - Pago en una sola exhibición</option>
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
                            <button class="btn btn-success" onclick="timbrarFacturaProyecto()">
                                <i class="fa-solid fa-stamp"></i> Timbrar Factura
                            </button>
                        </div>
                    </div>

                    <!-- Formulario Oculto para Enviar Datos -->
                    <form id="formFacturaProyecto" action="{{ route('procesar.factura.proyecto') }}" method="POST" style="display: none;">
                        @csrf
                        
                        <!-- Datos del Emisor -->
                        <input type="hidden" name="emisor_nombre" value="UM Mining">
                        <input type="hidden" name="emisor_rfc" value="UMM-123456-ABC">
                        <input type="hidden" name="emisor_direccion" value="Av. Principal #123, Col. Centro">
                        <input type="hidden" name="emisor_lugar_expedicion" value="Monterrey, N.L.">
                        <input type="hidden" name="emisor_regimen_fiscal" value="601 - General de Ley Personas Morales">
                        <input type="hidden" name="emisor_telefono" value="(81) 1234-5678">
                        
                        <!-- Datos del Proyecto -->
                        <input type="hidden" name="folio" value="{{ $proyecto->folio ?? 'N/A' }}-DIV{{ $partida->plazo ?? 'N/A' }}">
                        <input type="hidden" name="id_proyecto" value="{{ $idProyecto }}">
                        <input type="hidden" name="id_partida" value="{{ $idPartida }}">
                        <input type="hidden" name="tipo_servicio" value="proyecto">
                        
                        <!-- Datos del Receptor -->
                        <input type="hidden" name="receptor_nombre" value="{{ $cliente->nombre ?? 'N/A' }}">
                        <input type="hidden" name="receptor_rfc" value="{{ $cliente->rfc ?? 'XAXX010101000' }}">
                        <input type="hidden" name="receptor_direccion" value="{{  $cliente->calle."  #".$cliente->numero_int." ".$cliente->colonia ?? 'Sin especificar' }}">
                        <input type="hidden" name="receptor_codigo_postal" value="{{ $cliente->cp ?? '64000' }}">
                        <!-- Los valores de uso_cfdi y regimen_fiscal se establecerán dinámicamente -->
                        <input type="hidden" name="receptor_uso_cfdi" id="hidden_uso_cfdi" value="">
                        <input type="hidden" name="receptor_regimen_fiscal" id="hidden_regimen_fiscal" value="">
                        
                        <!-- Totales -->
                        <input type="hidden" name="subtotal" value="{{ ($partidas->sum('monto_ingreso') ?? 0) / 1.16 }}">
                        <input type="hidden" name="iva" value="{{ ($partidas->sum('monto_ingreso') ?? 0) - (($partidas->sum('monto_ingreso') ?? 0) / 1.16) }}">
                        <input type="hidden" name="total" value="{{ $partidas->sum('monto_ingreso') ?? 0 }}">
                        
                                                 <!-- Información de Pago -->
                         <input type="hidden" name="forma_pago" id="hidden_forma_pago" value="">
                        <input type="hidden" name="metodo_pago" value="PUE">
                        <input type="hidden" name="moneda" value="MXN - Peso Mexicano">
                        
                        <!-- Los conceptos se agregarán dinámicamente -->
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Búsqueda de Productos -->
<div class="modal fade" id="modalBuscarProductoProyecto" tabindex="-1" aria-labelledby="modalBuscarProductoProyectoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarProductoProyectoLabel">Buscar Producto/Servicio SAT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchProductoProyecto" placeholder="Buscar producto o servicio...">
                    <button class="btn btn-primary" type="button" onclick="buscarProductoProyecto()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <div id="resultadosBusquedaProyecto" class="list-group">
                    <!-- Los resultados se mostrarán aquí -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Búsqueda de Unidades de Medida -->
<div class="modal fade" id="modalBuscarUnidadProyecto" tabindex="-1" aria-labelledby="modalBuscarUnidadProyectoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarUnidadProyectoLabel">Buscar Unidad de Medida SAT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchUnidadProyecto" placeholder="Buscar unidad de medida...">
                    <button class="btn btn-primary" type="button" onclick="buscarUnidadProyecto()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <div id="resultadosBusquedaUnidadProyecto" class="list-group">
                    <!-- Los resultados se mostrarán aquí -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Búsqueda de Forma de Pago -->
<div class="modal fade" id="modalBuscarFormaPagoProyecto" tabindex="-1" aria-labelledby="modalBuscarFormaPagoProyectoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarFormaPagoProyectoLabel">Buscar Forma de Pago SAT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchFormaPagoProyecto" placeholder="Buscar forma de pago...">
                    <button class="btn btn-primary" type="button" onclick="buscarFormaPagoProyecto()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <div id="resultadosBusquedaFormaPagoProyecto" class="list-group">
                    <!-- Los resultados se mostrarán aquí -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Búsqueda de Régimen Fiscal -->
<div class="modal fade" id="modalBuscarRegimenFiscalProyecto" tabindex="-1" aria-labelledby="modalBuscarRegimenFiscalProyectoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBuscarRegimenFiscalProyectoLabel">Seleccionar Régimen Fiscal SAT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="searchRegimenFiscalProyecto" placeholder="Buscar régimen fiscal...">
                    <button class="btn btn-primary" type="button" onclick="buscarRegimenFiscalProyecto()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
                <div id="resultadosBusquedaRegimenFiscalProyecto" class="list-group">
                    <!-- Los resultados se mostrarán aquí -->
                </div>
            </div>
        </div>
    </div>
</div>



@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Variables globales
        const idProyecto = {{ $idProyecto ?? 'null' }};
        const idPartida = {{ $idPartida ?? 'null' }};
        
        console.log('ID del Proyecto:', idProyecto);
        console.log('ID de la Partida:', idPartida);
        
        // Inicializar totales con el valor total de las partidas
        const totalPartidas = {{ $partidas->sum('monto_ingreso') ?? 0 }};
        document.getElementById('subtotal-proyecto').textContent = (totalPartidas / 1.16).toFixed(2);
        document.getElementById('iva-proyecto').textContent = (totalPartidas - (totalPartidas / 1.16)).toFixed(2);
        document.getElementById('total-proyecto').textContent = totalPartidas.toFixed(2);
        
        // Inicializar totales
        actualizarTotalesProyecto();
        
        // Cargar regímenes fiscales al iniciar la página
        cargarRegimenesFiscalesProyecto();
        
        // Establecer valor por defecto para forma de pago
        document.getElementById('forma_pago-proyecto').value = '99';
        

    });

    // Funciones para el manejo de conceptos del proyecto
    function agregarConceptoProyecto() {
        const tbody = document.getElementById('tablaConceptosProyecto');
        const newRow = document.createElement('tr');
        const rowIndex = tbody.children.length;
        
        newRow.innerHTML = `
            <td>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm" value="Buscar producto SAT" onchange="actualizarTotalesProyecto()">
                    <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaProductoProyecto(${rowIndex})">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm cantidad-proyecto" value="1" min="1" onchange="calcularImporteProyecto(this)">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <select class="form-select form-select-sm" onchange="actualizarTotalesProyecto()">
                        <option value="E48">E48 - Unidad de servicio</option>
                        <option value="H87">H87 - Pieza</option>
                        <option value="EA">EA - Elemento</option>
                        <option value="KGM">KGM - Kilogramo</option>
                        <option value="MTR">MTR - Metro</option>
                    </select>
                    <button class="btn btn-outline-secondary" type="button" onclick="abrirBusquedaUnidadProyecto(${rowIndex})">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" placeholder="Concepto detallado">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm precio-proyecto" value="0" step="0.01" min="0" onchange="calcularImporteProyecto(this)">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm importe-proyecto" value="0" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminarConceptoProyecto(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(newRow);
        actualizarTotalesProyecto();
    }

    function eliminarConceptoProyecto(button) {
        const row = button.closest('tr');
        row.remove();
        actualizarTotalesProyecto();
    }

    function calcularImporteProyecto(input) {
        const row = input.closest('tr');
        const cantidad = parseFloat(row.querySelector('.cantidad-proyecto').value) || 0;
        const precio = parseFloat(row.querySelector('.precio-proyecto').value) || 0;
        const importe = cantidad * precio;
        row.querySelector('.importe-proyecto').value = importe.toFixed(2);
        actualizarTotalesProyecto();
    }

    function actualizarTotalesProyecto() {
        let subtotal = 0;
        const filas = document.querySelectorAll('#tablaConceptosProyecto tr');
        
        filas.forEach(fila => {
            const importe = fila.querySelector('.importe-proyecto');
            if (importe && importe.value) {
                subtotal += parseFloat(importe.value.replace(/[^0-9.-]+/g, ''));
            }
        });

        const iva = subtotal * 0.16;
        const total = subtotal + iva;

        document.getElementById('subtotal-proyecto').textContent = subtotal.toFixed(2);
        document.getElementById('iva-proyecto').textContent = iva.toFixed(2);
        document.getElementById('total-proyecto').textContent = total.toFixed(2);
    }

    function timbrarFacturaProyecto() {
        // Validar campos obligatorios antes de proceder
        const errores = [];
        
        // Validar datos del receptor
        const receptorNombre = document.querySelector('input[name="receptor_nombre"]').value;
        const receptorRfc = document.querySelector('input[name="receptor_rfc"]').value;
        const usoCfdi = document.querySelector('#uso_cfdi-proyecto').value;
        const regimenFiscal = document.querySelector('#regimen_fiscal_receptor-proyecto').value;
        
        if (!receptorNombre || receptorNombre === 'N/A') {
            errores.push('El nombre del receptor es obligatorio');
        }
        
        if (!receptorRfc || receptorRfc === 'XAXX010101000') {
            errores.push('El RFC del receptor es obligatorio');
        }
        
        if (!usoCfdi) {
            errores.push('Debe seleccionar un Uso CFDI');
        }
        
        if (!regimenFiscal) {
            errores.push('Debe seleccionar un Régimen Fiscal');
        }
        
        // Validar que hay al menos un concepto
        const filas = document.querySelectorAll('#tablaConceptosProyecto tr');
        let conceptosValidos = 0;
        
        filas.forEach((fila, index) => {
            const productoInput = fila.querySelector('td:nth-child(1) input');
            const cantidadInput = fila.querySelector('td:nth-child(2) input');
            const precioInput = fila.querySelector('td:nth-child(5) input');
            
            if (productoInput && cantidadInput && precioInput) {
                const producto = productoInput.value.trim();
                const cantidad = parseFloat(cantidadInput.value) || 0;
                const precio = parseFloat(precioInput.value) || 0;
                
                if (producto && producto !== 'Buscar producto SAT' && cantidad > 0 && precio > 0) {
                    conceptosValidos++;
                }
            }
        });
        
        if (conceptosValidos === 0) {
            errores.push('Debe agregar al menos un concepto válido con producto, cantidad y precio');
        }
        
        // Si hay errores, mostrarlos
        if (errores.length > 0) {
            Swal.fire({
                title: 'Errores de Validación',
                html: `
                    <div class="text-start">
                        <p><strong>Por favor, corrija los siguientes errores antes de timbrar la factura:</strong></p>
                        <ul class="text-danger">
                            ${errores.map(error => `<li>${error}</li>`).join('')}
                        </ul>
                    </div>
                `,
                icon: 'error',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#d33'
            });
            return;
        }
        
        // Mostrar confirmación antes de proceder
        Swal.fire({
            title: '¿Está seguro de timbrar la factura del proyecto?',
            text: 'Esta acción generará un CFDI válido ante el SAT. Una vez timbrada, la factura no podrá ser modificada.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, timbrar factura',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar barra de progreso
                mostrarProgresoTimbradoProyecto();
            }
        });
    }

    function mostrarProgresoTimbradoProyecto() {
        let progreso = 0;
        const pasos = [
            'Validando datos del proyecto...',
            'Preparando información para el SAT...',
            'Conectando con el servicio de facturación...',
            'Generando CFDI del proyecto...',
            'Timbrando factura...',
            'Finalizando proceso...'
        ];
        
        Swal.fire({
            title: 'Procesando timbrado de factura del proyecto',
            html: `
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">${pasos[0]}</span>
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
                            procesarTimbradoProyecto();
                        }, 500);
                    }
                }, 800); // Actualizar cada 800ms
            }
        });
    }

    function procesarTimbradoProyecto() {
        // Actualizar mensaje final
        Swal.update({
            title: 'Finalizando timbrado del proyecto...',
            html: `
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Guardando factura del proyecto en la base de datos...</span>
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
            enviarFacturaProyecto();
        }, 1000);
    }

    function enviarFacturaProyecto() {
        // Recopilar datos del formulario
        const formData = new FormData();
        
        // Datos del emisor
        formData.append('emisor_nombre', 'UMMININGN');
        formData.append('emisor_rfc', 'UMM200127ME3');
        formData.append('emisor_direccion', 'paseo del algodon 325 Los Viñedos, CP: 27023, México');
        formData.append('emisor_lugar_expedicion', '27023');
        formData.append('emisor_regimen_fiscal', '601 - General de Ley Personas Morales');
        formData.append('emisor_telefono', '8715141213');
        
        
        // Datos del proyecto
        formData.append('folio', '{{ $proyecto->folio}}');
        formData.append('id_proyecto', '{{ $idProyecto }}');
        formData.append('id_partida', '{{ $idPartida }}');
        formData.append('tipo_servicio', 'proyecto');
        
        // Datos del receptor
        formData.append('receptor_nombre', '{{ $cliente->nombre ?? "N/A" }}');
        formData.append('receptor_rfc', '{{ $cliente->rfc ?? "XAXX010101000" }}');
        formData.append('receptor_direccion', '{{  $cliente->calle."  #".$cliente->numero_int." ".$cliente->colonia ?? 'Sin especificar'}}');
        formData.append('receptor_codigo_postal', '{{ $cliente->cp ?? "64000" }}');
        
        // Obtener valores de los campos de selección
        const usoCfdi = document.querySelector('#uso_cfdi-proyecto').value;
        const regimenFiscal = document.querySelector('#regimen_fiscal_receptor-proyecto').value;
        
        formData.append('receptor_uso_cfdi', usoCfdi);
        formData.append('receptor_regimen_fiscal', regimenFiscal);
        
        // Actualizar campos ocultos del formulario
        document.getElementById('hidden_uso_cfdi').value = usoCfdi;
        document.getElementById('hidden_regimen_fiscal').value = regimenFiscal;
        
        // Totales
        const subtotal = parseFloat(document.getElementById('subtotal-proyecto').textContent);
        const iva = parseFloat(document.getElementById('iva-proyecto').textContent);
        const total = parseFloat(document.getElementById('total-proyecto').textContent);
        
        formData.append('subtotal', subtotal.toString());
        formData.append('iva', iva.toString());
        formData.append('total', total.toString());
        
        // Información de pago
        const formaPagoSeleccionada = document.querySelector('#forma_pago-proyecto').value;
        formData.append('forma_pago', formaPagoSeleccionada);
        formData.append('metodo_pago', document.querySelector('#metodo_pago-proyecto').value);
        formData.append('moneda', document.querySelector('#moneda-proyecto').value);
        
        // Actualizar campos ocultos del formulario
        document.getElementById('hidden_forma_pago').value = formaPagoSeleccionada;
        
        // Recopilar conceptos
        const conceptos = [];
        const filas = document.querySelectorAll('#tablaConceptosProyecto tr');
        
        filas.forEach((fila, index) => {
            const productoInput = fila.querySelector('td:nth-child(1) input');
            const cantidadInput = fila.querySelector('td:nth-child(2) input');
            const unidadSelect = fila.querySelector('td:nth-child(3) select');
            const conceptoInput = fila.querySelector('td:nth-child(4) input');
            const precioInput = fila.querySelector('td:nth-child(5) input');
            const importeInput = fila.querySelector('td:nth-child(6) input');
            
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
            }
        });
        
        // Agregar conceptos al formulario
        formData.append('conceptos', JSON.stringify(conceptos));
        
        // Crear y enviar formulario
        const form = document.getElementById('formFacturaProyecto');
        
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



    // Variables globales para el buscador
    let productoSeleccionadoIndexProyecto = null;
    let unidadSeleccionadaIndexProyecto = null;
    let formaPagoSeleccionadaProyecto = null;

    // Funciones para abrir los modales de búsqueda
    function abrirBusquedaProductoProyecto(index) {
        productoSeleccionadoIndexProyecto = index;
        $('#modalBuscarProductoProyecto').modal('show');
    }

    function abrirBusquedaUnidadProyecto(index) {
        unidadSeleccionadaIndexProyecto = index;
        $('#modalBuscarUnidadProyecto').modal('show');
    }

    function abrirBusquedaFormaPagoProyecto() {
        formaPagoSeleccionadaProyecto = true;
        
        // Cargar todas las formas de pago al abrir el modal
        const searchInput = document.getElementById('searchFormaPagoProyecto');
        searchInput.value = '';
        
        // Realizar la búsqueda automáticamente para mostrar todas las opciones
        setTimeout(() => buscarFormaPagoProyecto(), 100);
        
        $('#modalBuscarFormaPagoProyecto').modal('show');
    }

    function abrirBusquedaRegimenFiscalProyecto() {
        // Cargar todos los regímenes fiscales al abrir el modal
        const searchInput = document.getElementById('searchRegimenFiscalProyecto');
        searchInput.value = '';
        
        // Realizar la búsqueda automáticamente para mostrar todas las opciones
        setTimeout(() => buscarRegimenFiscalProyecto(), 100);
        
        $('#modalBuscarRegimenFiscalProyecto').modal('show');
    }



    // Funciones para buscar productos y unidades
    function buscarProductoProyecto() {
        const keyword = document.getElementById('searchProductoProyecto').value;
        if (keyword.length < 3) {
            Swal.fire('Error', 'Ingresa al menos 3 caracteres para buscar', 'error');
            return;
        }

        fetch(`/buscar-producto-servicio?keyword=${encodeURIComponent(keyword)}`)
            .then(response => response.json())
            .then(data => {
                const resultadosDiv = document.getElementById('resultadosBusquedaProyecto');
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
                    button.onclick = () => seleccionarProductoProyecto(item);
                    resultadosDiv.appendChild(button);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Ocurrió un error al buscar productos', 'error');
            });
    }

    function buscarUnidadProyecto() {
        const keyword = document.getElementById('searchUnidadProyecto').value;
        if (keyword.length < 3) {
            Swal.fire('Error', 'Ingresa al menos 3 caracteres para buscar', 'error');
            return;
        }

        fetch(`/buscar-unidad-medida?keyword=${encodeURIComponent(keyword)}`)
            .then(response => response.json())
            .then(data => {
                const resultadosDiv = document.getElementById('resultadosBusquedaUnidadProyecto');
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
                    button.onclick = () => seleccionarUnidadProyecto(item);
                    resultadosDiv.appendChild(button);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Ocurrió un error al buscar unidades', 'error');
            });
    }

    function buscarFormaPagoProyecto() {
        const keyword = document.getElementById('searchFormaPagoProyecto').value;
        
        // Solo validar longitud mínima si hay keyword
        if (keyword && keyword.length < 3) {
            Swal.fire('Error', 'Ingresa al menos 3 caracteres para buscar', 'error');
            return;
        }

        fetch(`/buscar-forma-pago${keyword ? '?keyword=' + encodeURIComponent(keyword) : ''}`)
            .then(response => response.json())
            .then(data => {
                const resultadosDiv = document.getElementById('resultadosBusquedaFormaPagoProyecto');
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
                    button.onclick = () => seleccionarFormaPagoProyecto(item);
                    resultadosDiv.appendChild(button);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Ocurrió un error al buscar formas de pago', 'error');
            });
    }

    function buscarRegimenFiscalProyecto() {
        const keyword = document.getElementById('searchRegimenFiscalProyecto').value;
        
        // Solo validar longitud mínima si hay keyword
        if (keyword && keyword.length < 3) {
            Swal.fire('Error', 'Ingresa al menos 3 caracteres para buscar', 'error');
            return;
        }

        fetch(`/buscar-regimen-fiscal${keyword ? '?keyword=' + encodeURIComponent(keyword) : ''}`)
            .then(response => response.json())
            .then(data => {
                const resultadosDiv = document.getElementById('resultadosBusquedaRegimenFiscalProyecto');
                resultadosDiv.innerHTML = '';

                // Filtrar resultados si hay keyword
                let regimenesFiscales = data;
                if (keyword) {
                    regimenesFiscales = data.filter(item => 
                        item.Name.toLowerCase().includes(keyword.toLowerCase()) ||
                        item.Value.includes(keyword)
                    );
                }

                regimenesFiscales.forEach(item => {
                    const button = document.createElement('button');
                    button.className = 'list-group-item list-group-item-action';
                    button.innerHTML = `
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${item.Value} - ${item.Name}</h6>
                        </div>
                    `;
                    button.onclick = () => seleccionarRegimenFiscalProyecto(item);
                    resultadosDiv.appendChild(button);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Ocurrió un error al buscar regímenes fiscales', 'error');
            });
    }



    // Funciones para seleccionar productos y unidades
    function seleccionarProductoProyecto(producto) {
        if (productoSeleccionadoIndexProyecto !== null) {
            const fila = document.querySelectorAll('#tablaConceptosProyecto tr')[productoSeleccionadoIndexProyecto];
            const inputProducto = fila.querySelector('td:first-child input');
            inputProducto.value = `${producto.Value} - ${producto.Name}`;
            $('#modalBuscarProductoProyecto').modal('hide');
            productoSeleccionadoIndexProyecto = null;
        }
    }

    function seleccionarUnidadProyecto(unidad) {
        if (unidadSeleccionadaIndexProyecto !== null) {
            const fila = document.querySelectorAll('#tablaConceptosProyecto tr')[unidadSeleccionadaIndexProyecto];
            const selectUnidad = fila.querySelector('td:nth-child(3) select');
            
            // Crear nueva opción
            const nuevaOpcion = document.createElement('option');
            nuevaOpcion.value = unidad.Value;
            nuevaOpcion.textContent = `${unidad.Value} - ${unidad.ShortName}`;
            nuevaOpcion.selected = true;
            
            // Limpiar opciones existentes y agregar la nueva
            selectUnidad.innerHTML = '';
            selectUnidad.appendChild(nuevaOpcion);
            
            $('#modalBuscarUnidadProyecto').modal('hide');
            unidadSeleccionadaIndexProyecto = null;
        }
    }

    function seleccionarFormaPagoProyecto(formaPago) {
        if (formaPagoSeleccionadaProyecto) {
            const inputFormaPago = document.getElementById('forma_pago-proyecto');
            inputFormaPago.value = `${formaPago.Value} - ${formaPago.Name}`;
            $('#modalBuscarFormaPagoProyecto').modal('hide');
            formaPagoSeleccionadaProyecto = null;
        }
    }

    function seleccionarRegimenFiscalProyecto(regimenFiscal) {
        const selectRegimenFiscal = document.getElementById('regimen_fiscal_receptor-proyecto');
        selectRegimenFiscal.value = `${regimenFiscal.Value} - ${regimenFiscal.Name}`;
        $('#modalBuscarRegimenFiscalProyecto').modal('hide');
    }



    // Funciones para cargar regímenes fiscales
    function cargarRegimenesFiscalesProyecto() {
        console.log('Iniciando carga de regímenes fiscales para proyecto...');
        
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
                
                const selectRegimenFiscal = document.getElementById('regimen_fiscal_receptor-proyecto');
                
                // Limpiar opciones existentes excepto la primera
                selectRegimenFiscal.innerHTML = '<option value="">Seleccionar régimen fiscal</option>';
                
                // Verificar si hay datos y no es un error
                if (Array.isArray(data) && data.length > 0) {
                    // Agregar opciones dinámicamente
                    data.forEach(regimen => {
                        const option = document.createElement('option');
                        option.value = `${regimen.Value} - ${regimen.Name}`;
                        option.textContent = `${regimen.Value} - ${regimen.Name}`;
                        selectRegimenFiscal.appendChild(option);
                    });
                    
                    console.log('Regímenes fiscales cargados exitosamente desde la API:', data.length, 'opciones');
                } else {
                    console.warn('No se obtuvieron datos válidos de regímenes fiscales de la API, cargando opciones por defecto');
                    cargarRegimenesFiscalesPorDefectoProyecto();
                }
            })
            .catch(error => {
                console.error('Error al cargar regímenes fiscales:', error);
                console.error('Detalles del error:', error.message);
                cargarRegimenesFiscalesPorDefectoProyecto();
            });
    }

    function cargarRegimenesFiscalesPorDefectoProyecto() {
        console.log('Cargando regímenes fiscales desde opciones por defecto para proyecto...');
        
        const selectRegimenFiscal = document.getElementById('regimen_fiscal_receptor-proyecto');
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
            selectRegimenFiscal.appendChild(option);
        });
        
        console.log('Regímenes fiscales cargados desde opciones por defecto para proyecto');
    }



    // Event listeners cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado para facturación de proyecto');
        
        // Evento para la tecla Enter en el campo de búsqueda de productos
        const searchProductoProyecto = document.getElementById('searchProductoProyecto');
        if (searchProductoProyecto) {
            searchProductoProyecto.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarProductoProyecto();
                }
            });
        }

        // Evento para la tecla Enter en el campo de búsqueda de unidades
        const searchUnidadProyecto = document.getElementById('searchUnidadProyecto');
        if (searchUnidadProyecto) {
            searchUnidadProyecto.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarUnidadProyecto();
                }
            });
        }

        // Evento para la tecla Enter en el campo de búsqueda de forma de pago
        const searchFormaPagoProyecto = document.getElementById('searchFormaPagoProyecto');
        if (searchFormaPagoProyecto) {
            searchFormaPagoProyecto.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarFormaPagoProyecto();
                }
            });
        }

        // Evento para la tecla Enter en el campo de búsqueda de régimen fiscal
        const searchRegimenFiscalProyecto = document.getElementById('searchRegimenFiscalProyecto');
        if (searchRegimenFiscalProyecto) {
            searchRegimenFiscalProyecto.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarRegimenFiscalProyecto();
                }
            });
        }


    });

    // Función para limpiar el modal de productos cuando se cierra
    $('#modalBuscarProductoProyecto').on('hidden.bs.modal', function () {
        document.getElementById('searchProductoProyecto').value = '';
        document.getElementById('resultadosBusquedaProyecto').innerHTML = '';
    });

    // Función para limpiar el modal de unidades cuando se cierra
    $('#modalBuscarUnidadProyecto').on('hidden.bs.modal', function () {
        document.getElementById('searchUnidadProyecto').value = '';
        document.getElementById('resultadosBusquedaUnidadProyecto').innerHTML = '';
    });

    // Función para limpiar el modal de forma de pago cuando se cierra
    $('#modalBuscarFormaPagoProyecto').on('hidden.bs.modal', function () {
        document.getElementById('searchFormaPagoProyecto').value = '';
        document.getElementById('resultadosBusquedaFormaPagoProyecto').innerHTML = '';
    });

    // Función para limpiar el modal de regímenes fiscales cuando se cierra
    $('#modalBuscarRegimenFiscalProyecto').on('hidden.bs.modal', function () {
        document.getElementById('searchRegimenFiscalProyecto').value = '';
        document.getElementById('resultadosBusquedaRegimenFiscalProyecto').innerHTML = '';
    });



    // Función para obtener factura impresa
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

    // Función para bajar inventario de productos
    function bajarInventario(partidaId, productoId, tipoIngreso, cantidad) {
        // Validar que sea un suministro
        if (tipoIngreso.toUpperCase() !== 'SUMINISTRO') {
            Swal.fire({
                title: 'Acción no permitida',
                text: 'Solo se puede bajar inventario para partidas de tipo SUMINISTRO',
                icon: 'warning',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        // Confirmar la acción
        Swal.fire({
            title: '¿Bajar Inventario?',
            html: `
                <div class="text-start">
                    <p><strong>¿Estás seguro de que deseas bajar el inventario?</strong></p>
                    <div class="alert alert-info">
                        <strong>Detalles de la operación:</strong><br>
                        • Partida ID: ${partidaId}<br>
                        • Producto ID: ${productoId}<br>
                        • Cantidad a bajar: ${cantidad}<br>
                        • Tipo: ${tipoIngreso}
                    </div>
                    <p class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Esta acción reducirá la cantidad disponible en el inventario del producto.
                    </p>
                    <p class="text-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Nota:</strong> Esta operación se puede realizar múltiples veces.
                    </p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, Bajar Inventario',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Bajando inventario del producto',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Realizar la petición AJAX
                fetch(`/proyectos/bajar-inventario`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        partida_id: partidaId,
                        producto_id: productoId,
                        cantidad: cantidad,
                        tipo_ingreso: tipoIngreso
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: '¡Inventario Bajado!',
                            html: `
                                <div class="text-start">
                                    <div class="alert alert-success">
                                        <strong>Operación exitosa:</strong><br>
                                        • Producto: ${data.producto_nombre}<br>
                                        • Cantidad bajada: ${cantidad}<br>
                                        • Inventario anterior: ${data.inventario_anterior}<br>
                                        • Inventario actual: ${data.inventario_actual}
                                    </div>
                                    <p class="text-success">
                                        <i class="fas fa-check-circle"></i>
                                        El inventario se ha actualizado correctamente.
                                    </p>
                                </div>
                            `,
                            icon: 'success',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#28a745'
                        });
                        
                        // Opcional: recargar la página para mostrar datos actualizados
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        Swal.fire({
                            title: 'Error al Bajar Inventario',
                            html: `
                                <div class="text-start">
                                    <div class="alert alert-danger">
                                        <strong>Error:</strong> ${data.message}
                                    </div>
                                    <p class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Verifique que el producto tenga suficiente inventario disponible.
                                    </p>
                                </div>
                            `,
                            icon: 'error',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error de Conexión',
                        text: 'Ocurrió un error al comunicarse con el servidor. Intente nuevamente.',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#dc3545'
                    });
                });
            }
        });
    }
    
    // Función para actualizar el campo oculto de forma de pago
    function actualizarFormaPagoOculta(valor) {
        const campoOculto = document.getElementById('hidden_forma_pago');
        if (campoOculto) {
            campoOculto.value = valor;
        }
    }
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

/* Estilos para el combo box de Uso CFDI */
#uso_cfdi-proyecto {
    font-size: 0.9em;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

#uso_cfdi-proyecto:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

#uso_cfdi-proyecto optgroup {
    font-weight: bold;
    color: #495057;
    background-color: #f8f9fa;
}

#uso_cfdi-proyecto option {
    font-weight: normal;
    color: #212529;
    padding: 4px 8px;
}

#uso_cfdi-proyecto option:hover {
    background-color: #e9ecef;
}
</style>
@endpush
