@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $estatusBadge = [
        'DISPONIBLE' => 'bg-warning text-dark',
        'CORTADA' => 'bg-info text-dark',
        'PESADA' => 'bg-primary',
        'REPORTADA' => 'bg-secondary',
        'TRITURADA' => 'bg-dark',
        'SACA_PESADA' => 'bg-info text-dark',
        'IDENTIFICADA' => 'bg-primary',
        'LISTO_PELETIZADO' => 'bg-warning text-dark',
        'EN_PELETIZADO' => 'bg-primary',
        'PELETIZADO' => 'bg-dark',
        'SACA_RESINA_DESMONTADA' => 'bg-info text-dark',
        'RESINA_IDENTIFICADA' => 'bg-primary',
        'SACA_RESINA_PESADA' => 'bg-secondary',
        'RESINA_ALMACENADA' => 'bg-warning text-dark',
        'PRODUCCION_REPORTADA' => 'bg-secondary',
        'KILOS_RESINA_SISTEMA' => 'bg-info text-dark',
        'STOCK_RESINA' => 'badge-success-dark',
        'CANCELADA' => 'badge-danger-dark',
    ];
    $flujo = \App\Models\Reproceso::$flujo;
    $pasos = \App\Models\Reproceso::$pasosTexto;
    $idxActual = array_search($lote->estatus, $flujo, true);
    $siguiente = $lote->siguiente_estatus;
    $btnSiguiente = [
        'CORTADA' => ['Cortar tubería', 'fa-scissors', 'Operador de producción'],
        'PESADA' => ['Registrar peso (báscula)', 'fa-weight-hanging', 'Operador de producción'],
        'REPORTADA' => ['Reportar reproceso', 'fa-clipboard-list', 'Supervisor de producción'],
        'TRITURADA' => ['Confirmar triturado', 'fa-recycle', 'Operadores de triturado'],
        'SACA_PESADA' => ['Pesar saca triturado', 'fa-weight-hanging', 'Operadores de triturado'],
        'IDENTIFICADA' => ['Identificar material', 'fa-tags', 'Operadores de triturado'],
        'LISTO_PELETIZADO' => ['Registrar cerca de peletizado', 'fa-box', 'Operadores de triturado'],
        'EN_PELETIZADO' => ['Trasladar a peletizadora', 'fa-truck', 'Operadores de peletizado'],
        'PELETIZADO' => ['Confirmar peletizado', 'fa-cubes', 'Operadores de peletizado'],
        'SACA_RESINA_DESMONTADA' => ['Desmontar saca de resina', 'fa-box-open', 'Operadores de peletizado'],
        'RESINA_IDENTIFICADA' => ['Identificar resina', 'fa-tags', 'Operadores de peletizado'],
        'SACA_RESINA_PESADA' => ['Pesar y registrar saca', 'fa-weight-hanging', 'Operadores de peletizado'],
        'RESINA_ALMACENADA' => ['Colocar en Nave 2', 'fa-warehouse', 'Operadores de peletizado'],
        'PRODUCCION_REPORTADA' => ['Registrar kilos producción', 'fa-clipboard-check', 'Supervisor de producción'],
        'KILOS_RESINA_SISTEMA' => ['Generar kilos resina en sistema', 'fa-database', 'Auxiliar administrativo'],
        'STOCK_RESINA' => ['Generar stock de resina', 'fa-boxes-stacked', 'Auxiliar de almacén'],
    ];
    $ubicacionesResina = $ubicacionesResina ?? collect();
    $maquinas = $maquinas ?? collect();
    $maquinasPeletizado = $maquinasPeletizado ?? collect();
    $maquinasOcupadas = $maquinasOcupadas ?? [];
    $productosResina = $productosResina ?? collect();
@endphp

<div class="container-fluid acciones-config-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-recycle"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Reproceso · {{ $lote->folio }}</h2>
                        <p class="text-muted mb-0 fs-8">
                            {{ $lote->origen_texto }}
                            @if ($lote->orden)
                                · OP <a href="{{ route('produccion.ordenes.detalle', $lote->orden_id) }}">{{ $lote->orden->folio }}</a>
                            @endif
                        </p>
                    </div>
                </div>
                <div>
                    <a class="btn btn-blue-light fs-7" href="{{ route('produccion.reproceso') }}">
                        <i class="fa-solid fa-list"></i> Cola reproceso
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body">
                    <h6 class="text-marino mb-3">Resumen</h6>
                    <dl class="row fs-8 mb-0">
                        <dt class="col-5 text-muted">Estatus</dt>
                        <dd class="col-7">
                            <span class="badge {{ $estatusBadge[$lote->estatus] ?? 'bg-secondary' }}">{{ $lote->estatus_texto }}</span>
                        </dd>
                        <dt class="col-5 text-muted">Máquina trit.</dt>
                        <dd class="col-7">
                            @if ($lote->maquina)
                                <strong>{{ $lote->maquina->codigo ? $lote->maquina->codigo . ' — ' : '' }}{{ $lote->maquina->nombre }}</strong>
                            @else
                                <span class="text-danger">Sin asignar</span>
                            @endif
                        </dd>
                        <dt class="col-5 text-muted">Máquina pelet.</dt>
                        <dd class="col-7">
                            @if ($lote->maquinaPeletizado)
                                <strong>{{ $lote->maquinaPeletizado->codigo ? $lote->maquinaPeletizado->codigo . ' — ' : '' }}{{ $lote->maquinaPeletizado->nombre }}</strong>
                            @else
                                <span class="text-muted">Sin asignar</span>
                            @endif
                        </dd>
                        <dt class="col-5 text-muted">Ubicación</dt>
                        <dd class="col-7">{{ $lote->ubicacion->folio_interno ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Kg saca trit.</dt>
                        <dd class="col-7">{{ $lote->kg_saca_triturada !== null ? number_format((float) $lote->kg_saca_triturada, 2) : '—' }}</dd>
                        <dt class="col-5 text-muted">Etiqueta trit.</dt>
                        <dd class="col-7">{{ $lote->etiqueta_triturado ?: '—' }}</dd>
                        <dt class="col-5 text-muted">Etiqueta resina</dt>
                        <dd class="col-7">{{ $lote->etiqueta_resina ?: '—' }}</dd>
                        <dt class="col-5 text-muted">Tipo resina</dt>
                        <dd class="col-7">{{ $lote->identificacion_resina ?: '—' }}</dd>
                        <dt class="col-5 text-muted">Kg saca resina</dt>
                        <dd class="col-7"><strong>{{ $lote->kg_saca_resina !== null ? number_format((float) $lote->kg_saca_resina, 2) : '—' }}</strong></dd>
                        <dt class="col-5 text-muted">Kg prod. report.</dt>
                        <dd class="col-7"><strong>{{ $lote->kg_produccion_reportado !== null ? number_format((float) $lote->kg_produccion_reportado, 2) : '—' }}</strong></dd>
                        <dt class="col-5 text-muted">Kg resina sist.</dt>
                        <dd class="col-7"><strong>{{ $lote->kg_resina_sistema !== null ? number_format((float) $lote->kg_resina_sistema, 2) : '—' }}</strong></dd>
                        <dt class="col-5 text-muted">Producto resina</dt>
                        <dd class="col-7">{{ $lote->productoResina->nombre ?? '—' }}</dd>
                    </dl>
                    @if ($lote->observaciones)
                        <hr>
                        <p class="fs-8 mb-0"><span class="text-muted">Obs:</span> {{ $lote->observaciones }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-body">
                    <h6 class="text-marino mb-3">Flujo de reproceso</h6>

                    @if (!$lote->tieneMaquina() && !$lote->estaCerrada())
                        <div class="border border-danger rounded-4 p-3 mb-3 bg-danger bg-opacity-10">
                            <h6 class="text-danger mb-1"><i class="fa-solid fa-industry me-1"></i> Máquina obligatoria al inicio</h6>
                            <p class="fs-8 mb-2">Seleccione la máquina de reproceso (triturado/peletizado). Debe estar dada de alta y activa.</p>
                            <form method="POST" action="{{ route('produccion.reproceso.maquina', $lote->id) }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-8">
                                    <select name="maquina_id" class="form-select form-select-sm" required>
                                        <option value="">— Seleccione —</option>
                                        @foreach ($maquinas as $maq)
                                            @php
                                                $ocupada = in_array((int) $maq->id, $maquinasOcupadas ?? [], true);
                                            @endphp
                                            <option value="{{ $maq->id }}" {{ $ocupada ? 'disabled' : '' }}>
                                                {{ $maq->codigo ? $maq->codigo . ' — ' : '' }}{{ $maq->nombre }}{{ $ocupada ? ' (en uso)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-danger btn-sm w-100">Asignar máquina</button>
                                </div>
                            </form>
                            @if ($maquinas->isEmpty())
                                <div class="text-danger fs-8 mt-2">
                                    No hay máquinas de reproceso. En <strong>Maquinaria</strong>, edite la trituradora/peletizadora y marque
                                    “Usar en reproceso”.
                                </div>
                            @endif
                        </div>
                    @endif

                    <ol class="list-group list-group-numbered mb-3" style="max-height:320px;overflow:auto">
                        @foreach ($flujo as $i => $est)
                            @php
                                $hecho = $idxActual !== false && $i < $idxActual;
                                $actual = $lote->estatus === $est;
                                $cls = $actual ? 'list-group-item-primary' : ($hecho ? 'list-group-item-success' : '');
                            @endphp
                            <li class="list-group-item {{ $cls }} fs-8">
                                <strong>{{ \App\Models\Reproceso::$estatuses[$est] ?? $est }}</strong>
                                <div class="text-muted">{{ $pasos[$est] ?? '' }}</div>
                            </li>
                        @endforeach
                    </ol>

                    @php
                        $puedeAvanzarSinMaquina = $siguiente === 'TRITURADA';
                    @endphp
                    @if (!$lote->tieneMaquina() && !$lote->estaCerrada() && !$puedeAvanzarSinMaquina)
                        <div class="alert alert-warning border-0 mb-0 fs-8">
                            Asigne la máquina de reproceso para poder avanzar el flujo.
                        </div>
                    @elseif ($siguiente && isset($btnSiguiente[$siguiente]))
                        @php [$label, $icon, $rol] = $btnSiguiente[$siguiente]; @endphp
                        <div class="border rounded-4 p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-0 text-marino">Siguiente paso</h6>
                                    <p class="fs-8 text-muted mb-0">Responsable: {{ $rol }}</p>
                                </div>
                                <span class="badge bg-warning text-dark">{{ \App\Models\Reproceso::$estatuses[$siguiente] ?? $siguiente }}</span>
                            </div>
                            <p class="fs-8">{{ $pasos[$siguiente] ?? '' }}</p>

                            <form method="POST" action="{{ route('produccion.reproceso.avanzar', $lote->id) }}">
                                @csrf
                                @if ($siguiente === 'TRITURADA')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Máquina de triturado <span class="text-danger">*</span></label>
                                        <select name="maquina_id" class="form-select form-select-sm" required>
                                            <option value="">— Seleccione en cuál se triturará —</option>
                                            @foreach ($maquinas as $maq)
                                                @php
                                                    $esMia = (string) $lote->maquina_id === (string) $maq->id;
                                                    $ocupada = in_array((int) $maq->id, $maquinasOcupadas ?? [], true) && !$esMia;
                                                    $sel = (string) old('maquina_id', $lote->maquina_id) === (string) $maq->id;
                                                @endphp
                                                <option value="{{ $maq->id }}" {{ $ocupada ? 'disabled' : '' }} {{ $sel ? 'selected' : '' }}>
                                                    {{ $maq->codigo ? $maq->codigo . ' — ' : '' }}{{ $maq->nombre }}{{ $ocupada ? ' (en uso)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($maquinas->isEmpty())
                                            <div class="text-danger fs-8 mt-1">
                                                No hay máquinas de reproceso. Márquelas en <strong>Maquinaria</strong> como “Usar en reproceso”.
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                @if ($siguiente === 'PESADA')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Kg en báscula <span class="text-danger">*</span></label>
                                        <input type="number" step="0.001" min="0.001" name="kg_pesado" class="form-control form-control-sm"
                                               value="{{ old('kg_pesado', $lote->kg_estimado) }}" required>
                                    </div>
                                @endif
                                @if ($siguiente === 'REPORTADA')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Kg a reportar <span class="text-danger">*</span></label>
                                        <input type="number" step="0.001" min="0.001" name="kg_reportado" class="form-control form-control-sm"
                                               value="{{ old('kg_reportado', $lote->kg_pesado ?? $lote->kg_estimado) }}" required>
                                    </div>
                                @endif
                                @if ($siguiente === 'SACA_PESADA')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Kg saca triturado <span class="text-danger">*</span></label>
                                        <input type="number" step="0.001" min="0.001" name="kg_saca_triturada" class="form-control form-control-sm"
                                               value="{{ old('kg_saca_triturada', $lote->kg_reportado ?? $lote->kg_pesado) }}" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Folio etiqueta {{ \App\Models\Reproceso::DOC_ETIQUETA_TRIT }} <span class="text-danger">*</span></label>
                                        <input type="text" name="etiqueta_triturado" class="form-control form-control-sm"
                                               value="{{ old('etiqueta_triturado') }}" maxlength="80" required>
                                    </div>
                                @endif
                                @if ($siguiente === 'IDENTIFICADA')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Identificación / datos de saca <span class="text-danger">*</span></label>
                                        <input type="text" name="identificacion_material" class="form-control form-control-sm"
                                               value="{{ old('identificacion_material') }}" maxlength="255" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Reporte {{ \App\Models\Reproceso::DOC_REPORTE_TRIT }}</label>
                                        <input type="text" name="reporte_triturado" class="form-control form-control-sm"
                                               value="{{ old('reporte_triturado', \App\Models\Reproceso::DOC_REPORTE_TRIT) }}" maxlength="80">
                                    </div>
                                @endif
                                @if ($siguiente === 'LISTO_PELETIZADO' || $siguiente === 'EN_PELETIZADO')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Máquina de peletizado <span class="text-danger">*</span></label>
                                        <select name="maquina_peletizado_id" class="form-select form-select-sm" required>
                                            <option value="">— Seleccione en cuál se peletizará —</option>
                                            @foreach ($maquinasPeletizado as $maq)
                                                @php
                                                    $esMia = (string) $lote->maquina_peletizado_id === (string) $maq->id;
                                                    $ocupada = in_array((int) $maq->id, $maquinasOcupadas, true) && !$esMia;
                                                    $sel = (string) old('maquina_peletizado_id', $lote->maquina_peletizado_id) === (string) $maq->id;
                                                @endphp
                                                <option value="{{ $maq->id }}" {{ $ocupada ? 'disabled' : '' }} {{ $sel ? 'selected' : '' }}>
                                                    {{ $maq->codigo ? $maq->codigo . ' — ' : '' }}{{ $maq->nombre }}{{ $ocupada ? ' (en uso)' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($maquinasPeletizado->isEmpty())
                                            <div class="text-danger fs-8 mt-1">
                                                No hay peletizadoras. Márquelas en <strong>Maquinaria</strong> como “Usar en reproceso”.
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                @if ($siguiente === 'EN_PELETIZADO')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Hora inicio peletizado</label>
                                        <input type="datetime-local" name="peletizado_inicio_at" class="form-control form-control-sm"
                                               value="{{ old('peletizado_inicio_at', now()->format('Y-m-d\TH:i')) }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Reporte {{ \App\Models\Reproceso::DOC_REPORTE_PELLET }}</label>
                                        <input type="text" name="reporte_peletizado" class="form-control form-control-sm"
                                               value="{{ old('reporte_peletizado', \App\Models\Reproceso::DOC_REPORTE_PELLET) }}" maxlength="80">
                                    </div>
                                @endif
                                @if ($siguiente === 'SACA_RESINA_DESMONTADA')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">
                                            Folio etiqueta {{ \App\Models\Reproceso::DOC_ETIQUETA_RESINA }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="etiqueta_resina" class="form-control form-control-sm"
                                               value="{{ old('etiqueta_resina', $lote->etiqueta_resina) }}"
                                               maxlength="80" required placeholder="Ej. EMP-PELLET-00045">
                                    </div>
                                @endif
                                @if ($siguiente === 'RESINA_IDENTIFICADA')
                                    @php $resinaManual = (string) old('resina_manual', '0') === '1'; @endphp
                                    <div class="mb-2 form-check">
                                        <input type="checkbox" class="form-check-input" id="resinaManualCheck" name="resina_manual" value="1"
                                               {{ $resinaManual ? 'checked' : '' }}>
                                        <label class="form-check-label fs-8" for="resinaManualCheck">Capturar tipo de resina manualmente</label>
                                    </div>
                                    <div class="mb-2" id="bloqueResinaCatalogo" style="{{ $resinaManual ? 'display:none' : '' }}">
                                        <label class="form-label fs-8">Resina (catálogo de productos) <span class="text-danger">*</span></label>
                                        <select name="producto_resina_id" id="selectProductoResina" class="form-select form-select-sm" {{ $resinaManual ? '' : 'required' }}>
                                            <option value="">— Seleccione resina —</option>
                                            @foreach ($productosResina as $prod)
                                                @php $codResina = $prod->sku ?: $prod->codigo_barras; @endphp
                                                <option value="{{ $prod->id }}" {{ (string) old('producto_resina_id', $lote->producto_resina_id) === (string) $prod->id ? 'selected' : '' }}>
                                                    {{ $codResina ? $codResina . ' — ' : '' }}{{ $prod->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($productosResina->isEmpty())
                                            <div class="text-danger fs-8 mt-1">No hay productos en la categoría RESINA.</div>
                                        @endif
                                    </div>
                                    <div class="mb-2" id="bloqueResinaManual" style="{{ $resinaManual ? '' : 'display:none' }}">
                                        <label class="form-label fs-8">Tipo de resina <span class="text-danger">*</span></label>
                                        <input type="text" name="identificacion_resina" id="inputTipoResina" class="form-control form-control-sm"
                                               value="{{ old('identificacion_resina', $lote->identificacion_resina) }}"
                                               maxlength="255" placeholder="Ej. PEAD negro, PVC, etc."
                                               {{ $resinaManual ? 'required' : '' }}>
                                    </div>
                                @endif
                                @if ($siguiente === 'SACA_RESINA_PESADA')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Kg saca resina (báscula) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.001" min="0.001" name="kg_saca_resina" class="form-control form-control-sm"
                                               value="{{ old('kg_saca_resina', $lote->kg_saca_triturada) }}" required>
                                    </div>
                                @endif
                                @if ($siguiente === 'RESINA_ALMACENADA')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Ubicación Nave 2 / área resina</label>
                                        <select name="ubicacion_id" class="form-select form-select-sm">
                                            <option value="">— {{ \App\Models\Reproceso::UBICACION_NAVE2_RESINA }} (default) —</option>
                                            @foreach ($ubicacionesResina as $ub)
                                                <option value="{{ $ub->id }}" @selected((string) old('ubicacion_id') === (string) $ub->id)>
                                                    {{ $ub->folio_interno }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                @if ($siguiente === 'PRODUCCION_REPORTADA')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Kg a registrar en reporte de producción <span class="text-danger">*</span></label>
                                        <input type="number" step="0.001" min="0.001" name="kg_produccion_reportado" class="form-control form-control-sm"
                                               value="{{ old('kg_produccion_reportado', $lote->kg_saca_resina) }}" required>
                                    </div>
                                @endif
                                @if ($siguiente === 'KILOS_RESINA_SISTEMA')
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Producto resina (catálogo) <span class="text-danger">*</span></label>
                                        <select name="producto_resina_id" class="form-select form-select-sm" required>
                                            <option value="">— Seleccione resina —</option>
                                            @foreach ($productosResina as $prod)
                                                @php $codResina = $prod->sku ?: $prod->codigo_barras; @endphp
                                                <option value="{{ $prod->id }}" {{ (string) old('producto_resina_id', $lote->producto_resina_id) === (string) $prod->id ? 'selected' : '' }}>
                                                    {{ $codResina ? $codResina . ' — ' : '' }}{{ $prod->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if ($productosResina->isEmpty())
                                            <div class="text-danger fs-8 mt-1">No hay productos en la categoría RESINA.</div>
                                        @else
                                            <div class="form-text fs-9">Productos de la categoría RESINA del catálogo.</div>
                                        @endif
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fs-8">Kg resina en sistema <span class="text-danger">*</span></label>
                                        <input type="number" step="0.001" min="0.001" name="kg_resina_sistema" class="form-control form-control-sm"
                                               value="{{ old('kg_resina_sistema', $lote->kg_produccion_reportado ?? $lote->kg_saca_resina) }}" required>
                                    </div>
                                @endif
                                @if ($siguiente === 'STOCK_RESINA')
                                    <div class="alert alert-info border-0 fs-8 py-2">
                                        Se generará stock de
                                        <strong>{{ number_format((float) ($lote->kg_resina_sistema ?? 0), 2) }} kg</strong>
                                        del producto
                                        <strong>{{ $lote->productoResina->nombre ?? '—' }}</strong>
                                        en {{ $lote->ubicacion->folio_interno ?? 'Nave 2 Resina' }} (almacén IOHISA).
                                    </div>
                                @endif
                                <div class="mb-2">
                                    <label class="form-label fs-8">Observaciones</label>
                                    <input type="text" name="observaciones" class="form-control form-control-sm"
                                           value="{{ old('observaciones', $lote->observaciones) }}" maxlength="1000">
                                </div>
                                <button type="submit" class="btn btn-blue btn-sm">
                                    <i class="fa-solid {{ $icon }} me-1"></i>{{ $label }}
                                </button>
                            </form>
                        </div>
                    @elseif ($lote->estatus === 'STOCK_RESINA')
                        <div class="alert alert-success border-0 mb-0 fs-8">
                            Flujo cerrado · stock de resina generado
                            ({{ number_format((float) $lote->kg_resina_sistema, 2) }} kg)
                            @if ($lote->stock_at)
                                el {{ $lote->stock_at->format('d/m/Y H:i') }}.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@if ($siguiente === 'RESINA_IDENTIFICADA')
<script>
(function () {
    var check = document.getElementById('resinaManualCheck');
    var bloqueCat = document.getElementById('bloqueResinaCatalogo');
    var bloqueMan = document.getElementById('bloqueResinaManual');
    var select = document.getElementById('selectProductoResina');
    var input = document.getElementById('inputTipoResina');
    if (!check || !bloqueCat || !bloqueMan) return;

    function sync() {
        var manual = check.checked;
        bloqueCat.style.display = manual ? 'none' : '';
        bloqueMan.style.display = manual ? '' : 'none';
        if (select) {
            select.required = !manual;
            if (manual) select.value = '';
        }
        if (input) {
            input.required = manual;
            if (!manual) input.value = input.value; // keep text if they toggle back
        }
    }

    check.addEventListener('change', sync);
    sync();
})();
</script>
@endif
@endsection
