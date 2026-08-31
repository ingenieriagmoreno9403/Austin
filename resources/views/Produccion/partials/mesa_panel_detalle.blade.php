@php
    $estatusBadgeProd = [
        'PREPARANDO_MAQUINAS' => 'bg-info text-dark',
        'CALENTANDO_MAQUINA' => 'badge-warning-dark',
        'EN_ESPERA_MATERIALES' => 'badge-warning text-dark',
        'EN_PRODUCCION' => 'badge-secondary',
        'PAUSADA' => 'bg-warning text-dark',
        'REVISION_CALIDAD' => 'bg-info text-dark',
        'PREPARAR_EMPAQUE' => 'bg-primary',
        'ENROLLANDO' => 'bg-primary',
        'A_LONGITUD' => 'bg-primary',
        'CORTAR_FLEJAR' => 'bg-primary',
        'EMPACANDO' => 'bg-primary',
        'RUTA_TORNO' => 'bg-primary',
        'RUTA_TORNO_EXTERNO' => 'bg-warning text-dark',
        'RUTA_CORTE' => 'bg-primary',
        'CALIDAD_FINAL' => 'bg-info text-dark',
        'ENTREGA_ALMACEN' => 'bg-secondary',
        'TERMINADA' => 'badge-success-dark',
        'CANCELADA' => 'badge-danger-dark',
    ];
    $entregaVencida = $orden->pedido?->fecha_entrega?->isPast() ?? false;
@endphp

<div class="mesa-panel-detalle h-100 d-flex flex-column">
    {{-- Header --}}
    <div class="mesa-panel-header">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="min-w-0">
                <div class="mesa-panel-kicker">Detalle de orden</div>
                <div class="mesa-panel-folio text-truncate" title="{{ $orden->folio }}">{{ $orden->folio }}</div>
                <div class="d-flex flex-wrap align-items-center gap-1 mt-2">
                    <span class="badge {{ $estatusBadgeProd[$orden->estatus] ?? 'badge-secondary' }}">
                        {{ $orden->estatus_texto }}
                    </span>
                    @if ($orden->esConexion())
                        <span class="badge bg-info text-dark">CONEXIÓN</span>
                    @elseif ($orden->esSoloFlange())
                        <span class="badge bg-warning text-dark">FLANGE</span>
                    @endif
                </div>
            </div>
            <button type="button" class="btn mesa-panel-close" id="mesa-panel-cerrar" title="Cerrar panel" aria-label="Cerrar panel">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    {{-- Info del pedido --}}
    <section class="mesa-panel-section">
        <div class="mesa-panel-section-title">
            <i class="fa-solid fa-file-invoice"></i>
            <span>Información del pedido</span>
        </div>
        <div class="mesa-panel-info-grid">
            <div class="mesa-panel-info-item">
                <span class="mesa-panel-info-label">Pedido</span>
                <span class="mesa-panel-info-value">{{ $orden->pedido?->folio ?? '—' }}</span>
            </div>
            <div class="mesa-panel-info-item">
                <span class="mesa-panel-info-label">Fecha orden</span>
                <span class="mesa-panel-info-value">{{ $orden->fecha?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="mesa-panel-info-item mesa-panel-info-item--full">
                <span class="mesa-panel-info-label">Cliente</span>
                <span class="mesa-panel-info-value">{{ $orden->pedido?->cliente?->nombre ?? '—' }}</span>
            </div>
            <div class="mesa-panel-info-item">
                <span class="mesa-panel-info-label">Fecha entrega</span>
                <span class="mesa-panel-info-value {{ $entregaVencida ? 'text-danger' : '' }}">
                    {{ $orden->pedido?->fecha_entrega?->format('d/m/Y') ?? '—' }}
                    @if ($entregaVencida)
                        <i class="fa-solid fa-triangle-exclamation ms-1" title="Entrega vencida"></i>
                    @endif
                </span>
            </div>
            <div class="mesa-panel-info-item">
                <span class="mesa-panel-info-label">Máquina</span>
                <span class="mesa-panel-info-value">{{ $orden->maquina?->codigo ?? 'Sin asignar' }}</span>
            </div>
        </div>
    </section>

    {{-- Productos --}}
    <section class="mesa-panel-section">
        <div class="mesa-panel-section-title">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>Productos</span>
            <span class="mesa-panel-count">{{ $orden->detalles->count() }}</span>
        </div>
        <div class="mesa-panel-products">
            @forelse ($orden->detalles as $detalle)
                @php
                    $lineaPedido = $orden->pedido?->detalles?->firstWhere('producto_id', $detalle->producto_id);
                    $meta = $lineaPedido ? (float) $lineaPedido->cantidad : 0;
                    $producido = max((float) $detalle->piezas_producidas, (float) $detalle->metros_producidos);
                    $nombreProducto = $detalle->producto?->nombre ?? $detalle->observaciones ?? 'Producto';
                @endphp
                <div class="mesa-panel-product">
                    <div class="mesa-panel-product-name" title="{{ $nombreProducto }}">{{ $nombreProducto }}</div>
                    <div class="mesa-panel-product-meta">
                        <span>Cant. <strong>{{ $meta > 0 ? number_format($meta, 2) : '—' }}</strong></span>
                        <span>Prod. <strong>{{ number_format($producido, 2) }}</strong></span>
                    </div>
                </div>
            @empty
                <div class="mesa-panel-empty">Sin productos en esta orden</div>
            @endforelse
        </div>
    </section>

    @if ($editable)
        <form method="POST" action="{{ route('produccion.ordenes.cabecera', $orden->id) }}"
            class="modern-form mesa-panel-form flex-grow-1 d-flex flex-column">
            @csrf
            <section class="mesa-panel-section flex-grow-1">
                <div class="mesa-panel-section-title">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Programación</span>
                </div>

                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label" for="mesa-maquina-id">Máquina inicial</label>
                        <select class="form-select form-select-sm" id="mesa-maquina-id" name="maquina_id" {{ $bloqueaMaquina ? 'disabled' : '' }}>
                            <option value="">— Sin asignar —</option>
                            @foreach ($maquinas as $maq)
                                <option value="{{ $maq->id }}" {{ (int) $orden->maquina_id === (int) $maq->id ? 'selected' : '' }}>
                                    {{ $maq->codigo }} — {{ $maq->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @if ($bloqueaMaquina)
                            <div class="form-text text-warning">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                Pedido pendiente de materiales: no asigne máquina aún.
                            </div>
                        @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="mesa-turno-id">Turno</label>
                        <select class="form-select form-select-sm" id="mesa-turno-id" name="turno_id" required>
                            @foreach ($turnos as $turno)
                                <option value="{{ $turno->id }}" {{ (int) $orden->turno_id === (int) $turno->id ? 'selected' : '' }}>
                                    {{ $turno->nombre }} ({{ substr($turno->hora_inicio, 0, 5) }} - {{ substr($turno->hora_fin, 0, 5) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="mesa-operador-id">Operador</label>
                        <select class="form-select form-select-sm" id="mesa-operador-id" name="operador_id">
                            <option value="">—</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}" {{ (int) $orden->operador_id === (int) $empleado->id ? 'selected' : '' }}>
                                    {{ $empleado->primer_nombre }} {{ $empleado->apellido_paterno }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="mesa-supervisor-id">Supervisor</label>
                        <select class="form-select form-select-sm" id="mesa-supervisor-id" name="supervisor_id">
                            <option value="">—</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}" {{ (int) $orden->supervisor_id === (int) $empleado->id ? 'selected' : '' }}>
                                    {{ $empleado->primer_nombre }} {{ $empleado->apellido_paterno }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="mesa-observaciones">Observaciones</label>
                        <textarea class="form-control form-control-sm" id="mesa-observaciones" name="observaciones" rows="2" maxlength="500">{{ $orden->observaciones }}</textarea>
                    </div>
                </div>
            </section>

            <div class="mesa-panel-actions">
                <div class="d-flex gap-2 mb-2">
                    @if ($orden->estatus === \App\Models\OrdenProduccion::ESTATUS_REVISION_CALIDAD)
                        <a href="{{ route('produccion.ordenes.detalle', $orden->id) }}" class="btn btn-baseColor-light btn-sm flex-grow-1">
                            <i class="fa-solid fa-clipboard-check me-1"></i> Calidad
                        </a>
                    @else
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1" disabled
                            title="Solo disponible cuando la orden esté en Revisión de calidad">
                            <i class="fa-solid fa-clipboard-check me-1"></i> Calidad
                        </button>
                    @endif

                    @if (in_array($orden->estatus, \App\Models\OrdenProduccion::ESTATUS_EMPAQUE, true))
                        <a href="{{ route('produccion.ordenes.detalle', $orden->id) }}" class="btn btn-baseColor-light btn-sm flex-grow-1">
                            <i class="fa-solid fa-box-open me-1"></i> Empaque
                        </a>
                    @else
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1" disabled
                            title="Solo disponible en las etapas de empaque (preparar, enrollar, longitud, cortar/flejar)">
                            <i class="fa-solid fa-box-open me-1"></i> Empaque
                        </button>
                    @endif
                </div>
                <button type="submit" class="btn btn-baseColor btn-sm w-100">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar programación
                </button>
            </div>
        </form>
    @else
        <div class="mesa-panel-readonly">
            <i class="fa-solid fa-lock me-2"></i>
            Orden cerrada. Solo consulta.
        </div>
        <div class="mesa-panel-actions">
            <button type="button" class="btn btn-outline-secondary btn-sm w-100" disabled
                title="Solo disponible cuando la orden esté en Revisión de calidad">
                <i class="fa-solid fa-clipboard-check me-1"></i> Revisión de calidad
            </button>
        </div>
    @endif
</div>
