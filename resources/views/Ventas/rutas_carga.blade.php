@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .cargas-page .ruta-list-item {
        border: 1px solid var(--card-border, rgba(17, 24, 39, 0.09));
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
        margin-bottom: 0.5rem;
        background: #fff;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .cargas-page .ruta-list-item:hover {
        border-color: rgba(17, 24, 39, 0.22);
        box-shadow: 0 2px 8px rgba(17, 24, 39, 0.06);
    }
    .cargas-page .ruta-list-item.is-active {
        border-color: #111827;
        background: #f3f4f6;
    }
    .cargas-page .ops-scroll {
        max-height: 220px;
        overflow: auto;
        border: 1px solid var(--card-border, rgba(17, 24, 39, 0.09));
        border-radius: 10px;
        padding: 0.65rem 0.75rem;
        background: #f9fafb;
    }
    .cargas-page .ops-scroll--warning {
        max-height: 180px;
        border-color: rgba(245, 158, 11, 0.35);
        background: rgba(245, 158, 11, 0.08);
    }
    .cargas-page .panel-hint {
        background: #f9fafb;
        border: 1px solid var(--card-border, rgba(17, 24, 39, 0.09));
        border-radius: 10px;
        padding: 0.75rem 0.85rem;
    }
    .cargas-page .empty-panel {
        text-align: center;
        padding: 2.5rem 1.5rem;
        color: #6b7280;
    }
    .cargas-page .empty-panel i {
        font-size: 2rem;
        margin-bottom: 0.75rem;
        opacity: 0.45;
        display: block;
    }
</style>

<div class="container-fluid acciones-config-page cargas-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-start header">
                <div class="d-flex align-items-center min-w-0">
                    <div class="header-icon me-3 flex-shrink-0">
                        <i class="fa-solid fa-truck"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="mb-1">
                            <a href="{{ route('ventas.pedidos') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Pedidos y cotizaciones
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Cargas (Almacén)</h2>
                        <p class="text-muted mb-0">
                            Crea la ruta (camión + chofer), carga OP terminadas y acomoda el orden de entrega/carga.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-xmark me-2"></i>
            <ul class="mb-0 ps-3 d-inline-block">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="modern-card mb-3">
                <div class="card-header d-flex align-items-center">
                    <div class="card-icon me-2"><i class="fa-solid fa-plus"></i></div>
                    <div>
                        <h6 class="mb-0 text-marino">Nueva ruta</h6>
                        <p class="text-muted fs-9 mb-0">Camión + chofer interno/externo</p>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('almacen.cargas.crear') }}" id="formNuevaRutaCarga" class="modern-form needs-validation" novalidate>
                        @csrf
                        @error('pedido_ids')
                            <div class="alert alert-danger border-0 fs-8 py-2">{{ $message }}</div>
                        @enderror
                        @error('carga_ids')
                            <div class="alert alert-danger border-0 fs-8 py-2">{{ $message }}</div>
                        @enderror
                        <div class="mb-2">
                            <label class="form-label fs-8">Fecha</label>
                            <input type="date" name="fecha" class="form-control form-control-sm" value="{{ old('fecha', now()->toDateString()) }}" required>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fs-8 mb-0">Camión</label>
                            <div class="input-group input-group-sm">
                                <select name="camion_id" id="selectCamionRuta" class="form-select form-select-sm">
                                    <option value="" data-cap-m="" data-cap-t="">— Seleccione o cree —</option>
                                    @foreach (($camiones ?? collect()) as $camion)
                                        <option value="{{ $camion->id }}"
                                            data-cap-m="{{ $camion->capacidad_metros !== null ? (float) $camion->capacidad_metros : '' }}"
                                            data-cap-t="{{ $camion->capacidad_ton !== null ? (float) $camion->capacidad_ton : '' }}">
                                            {{ $camion->etiqueta_con_capacidad }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary" title="Nuevo camión"
                                    data-bs-toggle="modal" data-bs-target="#modalNuevoCamion">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                            <div id="panelCapacidadNuevaRuta" class="alert border-0 fs-8 py-2 mt-2 mb-0 d-none"></div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fs-8 mb-0">Chofer (interno / externo)</label>
                            <div class="input-group input-group-sm">
                                <select name="chofer_id" id="selectChoferRuta" class="form-select form-select-sm">
                                    <option value="">— Seleccione o cree —</option>
                                    @foreach (($choferes ?? collect()) as $chofer)
                                        <option value="{{ $chofer->id }}" data-tipo="{{ $chofer->tipo }}">{{ $chofer->etiqueta }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary" title="Nuevo chofer"
                                    data-bs-toggle="modal" data-bs-target="#modalNuevoChofer">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                            <div class="form-text fs-9">Interno = EPP. Externo = seguro vehículo, IMSS, botas, casco, chaleco.</div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fs-8">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control form-control-sm" maxlength="1000">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-8 mb-1">
                                <i class="fa-solid fa-industry me-1"></i> OP terminadas a cargar en esta ruta
                                <span class="text-danger">*</span>
                            </label>
                            <div class="form-text fs-9 mb-1">Obligatorio: marque al menos una OP o una carga avisada.</div>
                            <div class="ops-scroll">
                                @forelse ($pedidosListos as $pedido)
                                    @php
                                        $op = $pedido->ordenProduccion;
                                        $metros = $op ? $op->total_metros_producidos : (float) $pedido->detalles->sum('cantidad');
                                        $piezas = $op ? $op->total_piezas_producidas : null;
                                        $direccion = $pedido->cliente?->direccionCompleta() ?: '';
                                        $checked = in_array((string) $pedido->id, array_map('strval', (array) old('pedido_ids', [])), true);
                                    @endphp
                                    <div class="form-check fs-8 mb-2">
                                        <input class="form-check-input js-ruta-op js-pedido-metros" type="checkbox" name="pedido_ids[]"
                                            value="{{ $pedido->id }}" id="ped_new_{{ $pedido->id }}"
                                            data-metros="{{ (float) $metros }}" @checked($checked)>
                                        <label class="form-check-label" for="ped_new_{{ $pedido->id }}">
                                            <strong>{{ $pedido->folio ?? ('Ped #' . $pedido->id) }}</strong>
                                            · {{ $pedido->cliente->nombre ?? '—' }}
                                            @if ($op)
                                                <span class="badge bg-success">OP {{ $op->folio }}</span>
                                            @else
                                                <span class="badge bg-secondary">Sin OP (existencia)</span>
                                            @endif
                                            <span class="text-muted d-block" style="font-size:10px">
                                                {{ number_format((float) $metros, 1) }} m
                                                @if ($piezas !== null) · {{ (int) $piezas }} pzas @endif
                                                @if ($op && $op->nave_destino) · {{ $op->nave_destino }} @endif
                                                · Entrega: {{ optional($pedido->fecha_entrega)->format('d/m/Y') ?: '—' }}
                                            </span>
                                            <span class="d-block {{ $direccion ? 'text-marino' : 'text-danger' }}" style="font-size:10px">
                                                <i class="fa-solid fa-location-dot me-1"></i>
                                                {{ $direccion !== '' ? $direccion : 'Sin dirección en el cliente' }}
                                            </span>
                                        </label>
                                    </div>
                                @empty
                                    <div class="text-muted fs-8">No hay OP terminadas pendientes de planificar.</div>
                                @endforelse
                            </div>
                        </div>

                        @if (($pedidosEnEmpaque ?? collect())->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label fs-8 mb-1 text-warning">
                                    <i class="fa-solid fa-hourglass-half me-1"></i> En empaque (aún no listos para cargar)
                                </label>
                                <div class="ops-scroll ops-scroll--warning">
                                    @foreach ($pedidosEnEmpaque as $pedido)
                                        @php $op = $pedido->ordenProduccion; @endphp
                                        <div class="fs-8 mb-2">
                                            <strong>{{ $pedido->folio ?? ('Ped #' . $pedido->id) }}</strong>
                                            · {{ $pedido->cliente->nombre ?? '—' }}
                                            @if ($op)
                                                <span class="badge bg-warning text-dark">{{ $op->folio }} · {{ $op->estatus_texto }}</span>
                                            @endif
                                            <div class="text-muted" style="font-size:10px">
                                                Falta terminar empaque (corte/fleje + nave). Luego aparecerá arriba para acomodar.
                                                @if ($op)
                                                    <a href="{{ route('produccion.ordenes.detalle', $op->id) }}">Abrir OP</a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mb-2">
                            <label class="form-label fs-8">Cargas ya avisadas (sin ruta)</label>
                            @forelse ($cargasDisponibles as $carga)
                                @php
                                    $opC = $carga->ordenProduccion;
                                    $metrosC = $carga->metrosCarga();
                                    $direccionC = $carga->destino_texto
                                        ?: ($carga->pedido?->cliente?->direccionCompleta() ?: '');
                                    $checkedCarga = in_array((string) $carga->id, array_map('strval', (array) old('carga_ids', [])), true);
                                @endphp
                                <div class="form-check fs-8">
                                    <input class="form-check-input js-ruta-op js-carga-metros" type="checkbox" name="carga_ids[]"
                                        value="{{ $carga->id }}" id="carga_new_{{ $carga->id }}"
                                        data-metros="{{ (float) $metrosC }}" @checked($checkedCarga)>
                                    <label class="form-check-label" for="carga_new_{{ $carga->id }}">
                                        <strong>{{ $carga->folio }}</strong>
                                        · {{ $carga->pedido->folio ?? ('Ped #' . $carga->pedido_id) }}
                                        · {{ $carga->pedido->cliente->nombre ?? '—' }}
                                        <span class="text-muted d-block" style="font-size:10px">
                                            {{ number_format((float) $metrosC, 1) }} m
                                            @if ($opC) · OP {{ $opC->folio }} · {{ (int) $opC->total_piezas_producidas }} pzas @endif
                                        </span>
                                        <span class="d-block {{ $direccionC ? 'text-marino' : 'text-danger' }}" style="font-size:10px">
                                            <i class="fa-solid fa-location-dot me-1"></i>
                                            {{ $direccionC !== '' ? $direccionC : 'Sin dirección' }}
                                        </span>
                                    </label>
                                </div>
                            @empty
                                <div class="text-muted fs-8">No hay cargas avisadas pendientes de ruta.</div>
                            @endforelse
                        </div>

                        <div id="errorNuevaRutaOps" class="alert alert-danger border-0 fs-8 py-2 d-none">
                            Seleccione al menos una OP terminada o una carga avisada.
                        </div>

                        <button type="submit" class="btn btn-baseColor btn-sm w-100">
                            <i class="fa-solid fa-truck"></i> Crear ruta y acomodar
                        </button>
                    </form>
                </div>
            </div>

            <div class="modern-card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="text-marino mb-0">
                            <i class="fa-solid fa-boxes-stacked me-1"></i>
                            Cola para carga
                        </h6>
                        <span class="badge bg-dark">{{ $pedidosListos->count() + $cargasDisponibles->count() }}</span>
                    </div>
                    <p class="text-muted fs-8 mb-0">
                        Marque las OP al crear la ruta. Defina <strong>km</strong> y pulse «Ordenar por distancia»:
                        entrega de <em>cerca a lejos</em>; carga al revés (lo más lejos al fondo del camión).
                    </p>
                </div>
            </div>

            <div class="modern-card">
                <div class="card-header d-flex align-items-center">
                    <div class="card-icon me-2"><i class="fa-solid fa-route"></i></div>
                    <div>
                        <h6 class="mb-0 text-marino">Rutas recientes</h6>
                        <p class="text-muted fs-9 mb-0">Seleccione una para acomodar</p>
                    </div>
                </div>
                <div class="card-body">
                    @forelse ($rutas as $ruta)
                        @php $activa = !empty($rutaSeleccionada) && (int) $rutaSeleccionada->id === (int) $ruta->id; @endphp
                        <a href="{{ route('almacen.cargas', ['ruta_id' => $ruta->id]) }}"
                           class="ruta-list-item {{ $activa ? 'is-active' : '' }}">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <strong class="text-marino">{{ $ruta->folio }}</strong>
                                <span class="badge bg-secondary fs-9">{{ $ruta->cargas_count }} ord.</span>
                            </div>
                            <div class="fs-8 text-muted mt-1">
                                {{ optional($ruta->fecha)->format('d/m/Y') }} · {{ $ruta->estatusTexto }}
                                @if ($ruta->camion) · {{ $ruta->camion->placas }}
                                @elseif ($ruta->unidad) · {{ $ruta->unidad }}
                                @endif
                                @if ($ruta->chofer) · {{ $ruta->chofer->nombre }}
                                @elseif ($ruta->chofer_nombre) · {{ $ruta->chofer_nombre }}
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="text-muted fs-8 text-center py-3">Aún no hay rutas.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            @if (empty($rutaSeleccionada))
                <div class="modern-card mb-3">
                    <div class="card-body empty-panel">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                        <div class="fw-semibold text-marino mb-1">Sin ruta seleccionada</div>
                        <p class="mb-0 fs-8">Seleccione o cree una ruta para acomodar cómo irán los pedidos en el camión.</p>
                    </div>
                </div>
                @if (($pedidosEnEmpaque ?? collect())->isNotEmpty())
                    <div class="modern-card mb-3">
                        <div class="card-header d-flex align-items-center">
                            <div class="card-icon me-2"><i class="fa-solid fa-hourglass-half"></i></div>
                            <div>
                                <h6 class="mb-0 text-marino">En empaque — aún no listos</h6>
                                <p class="text-muted fs-9 mb-0">Falta terminar corte/fleje + nave. Al marcar TERMINADA aparecen para cargar.</p>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover modern-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Cliente</th>
                                            <th>OP / estatus</th>
                                            <th class="text-end">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pedidosEnEmpaque as $pedido)
                                            @php $op = $pedido->ordenProduccion; @endphp
                                            <tr>
                                                <td><strong>{{ $pedido->folio ?? ('#' . $pedido->id) }}</strong></td>
                                                <td>{{ $pedido->cliente->nombre ?? '—' }}</td>
                                                <td>
                                                    @if ($op)
                                                        {{ $op->folio }}
                                                        <span class="badge bg-warning text-dark">{{ $op->estatus_texto }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if ($op)
                                                        <a class="btn btn-baseColor-light btn-sm" href="{{ route('produccion.ordenes.detalle', $op->id) }}">Abrir OP</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($pedidosListos->isNotEmpty())
                    <div class="modern-card">
                        <div class="card-header d-flex align-items-center">
                            <div class="card-icon me-2"><i class="fa-solid fa-clipboard-check"></i></div>
                            <div>
                                <h6 class="mb-0 text-marino">OP terminadas pendientes de carga</h6>
                                <p class="text-muted fs-9 mb-0">Márquelas en el formulario al crear la ruta, o agréguelas a una ruta existente.</p>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover modern-table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Cliente / dirección</th>
                                            <th>OP</th>
                                            <th>Metros / pzas</th>
                                            <th>Entrega</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pedidosListos as $pedido)
                                            @php
                                                $op = $pedido->ordenProduccion;
                                                $metros = $op ? $op->total_metros_producidos : (float) $pedido->detalles->sum('cantidad');
                                                $direccion = $pedido->cliente?->direccionCompleta() ?: '';
                                            @endphp
                                            <tr>
                                                <td><strong>{{ $pedido->folio ?? ('#' . $pedido->id) }}</strong></td>
                                                <td>
                                                    {{ $pedido->cliente->nombre ?? '—' }}
                                                    <div class="{{ $direccion ? 'text-muted' : 'text-danger' }} fs-9">
                                                        <i class="fa-solid fa-location-dot me-1"></i>
                                                        {{ $direccion !== '' ? $direccion : 'Sin dirección en el cliente' }}
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($op)
                                                        <span class="badge badge-success-dark">{{ $op->folio }}</span>
                                                        @if ($op->nave_destino)
                                                            <div class="text-muted fs-9">{{ $op->nave_destino }}</div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Existencia</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ number_format((float) $metros, 1) }} m
                                                    @if ($op) · {{ (int) $op->total_piezas_producidas }} pzas @endif
                                                </td>
                                                <td>{{ optional($pedido->fecha_entrega)->format('d/m/Y') ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                @php $capRuta = $rutaSeleccionada->resumenCapacidad(); @endphp
                <div class="modern-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div class="d-flex align-items-center">
                            <div class="card-icon me-2"><i class="fa-solid fa-route"></i></div>
                            <div>
                                <h5 class="mb-0 text-marino">{{ $rutaSeleccionada->folio }}</h5>
                                <div class="fs-8 text-muted">
                                    {{ optional($rutaSeleccionada->fecha)->format('d/m/Y') }}
                                    · {{ $rutaSeleccionada->estatusTexto }}
                                    @if ($rutaSeleccionada->camion)
                                        · Camión: {{ $rutaSeleccionada->camion->etiqueta_con_capacidad }}
                                    @elseif ($rutaSeleccionada->unidad)
                                        · {{ $rutaSeleccionada->unidad }}
                                    @endif
                                    @if ($rutaSeleccionada->chofer)
                                        · Chofer: {{ $rutaSeleccionada->chofer->etiqueta }}
                                    @elseif ($rutaSeleccionada->chofer_nombre)
                                        · Chofer: {{ $rutaSeleccionada->chofer_nombre }}
                                        @if ($rutaSeleccionada->chofer_tipo) ({{ $rutaSeleccionada->chofer_tipo }}) @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if ($rutaSeleccionada->cargas->isNotEmpty())
                            <a class="btn btn-baseColor-light btn-sm"
                               href="{{ route('almacen.cargas.pdf', $rutaSeleccionada->id) }}"
                               target="_blank" rel="noopener">
                                <i class="fa-solid fa-file-pdf"></i> PDF secuencia
                            </a>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="alert {{ $capRuta['cabe'] === false ? 'alert-danger' : ($capRuta['cabe'] === true ? 'alert-success' : 'alert-warning') }} border-0 fs-8 mb-3">
                            <div class="fw-semibold mb-1"><i class="fa-solid fa-ruler-combined me-1"></i> Capacidad del camión</div>
                            {{ $capRuta['mensaje'] }}
                            @if ($capRuta['capacidad_metros'] !== null)
                                <div class="progress mt-2" style="height:8px">
                                    <div class="progress-bar {{ $capRuta['cabe'] ? 'bg-success' : 'bg-danger' }}"
                                         style="width: {{ min(100, (float) ($capRuta['porcentaje'] ?? 0)) }}%"></div>
                                </div>
                                <div class="text-muted mt-1 fs-9">
                                    Usados {{ number_format($capRuta['metros_usados'], 1) }} m
                                    · Capacidad {{ number_format($capRuta['capacidad_metros'], 1) }} m
                                    @if ($capRuta['metros_disponibles'] !== null)
                                        · {{ $capRuta['metros_disponibles'] >= 0 ? 'Libres' : 'Exceso' }}
                                        {{ number_format(abs($capRuta['metros_disponibles']), 1) }} m
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="panel-hint fs-8 mb-3">
                            <strong class="text-marino">Cómo se acomoda la ruta:</strong>
                            1) Capture <em>Km</em> a cada destino y pulse «Ordenar por distancia».
                            2) <strong>Entrega #1</strong> = más cerca (primera baja).
                            3) <strong>Carga #1</strong> = más lejos (fondo del camión).
                            También puede arrastrar filas para ajustar a mano.
                        </div>

                        @if ($cargasDisponibles->isNotEmpty() || $pedidosListos->isNotEmpty())
                            <form method="POST" action="{{ route('almacen.cargas.agregar', $rutaSeleccionada->id) }}" class="modern-form panel-hint mb-3">
                                @csrf
                                <div class="fw-semibold fs-8 mb-2 text-marino">
                                    <i class="fa-solid fa-plus me-1"></i> Agregar órdenes terminadas / cargas a esta ruta
                                </div>

                                @if ($pedidosListos->isNotEmpty())
                                    <div class="mb-2">
                                        <div class="text-muted fs-8 mb-1">OP / pedidos listos (sin avisar)</div>
                                        <div class="row g-2">
                                            @foreach ($pedidosListos as $pedido)
                                                @php
                                                    $op = $pedido->ordenProduccion;
                                                    $metros = $op ? $op->total_metros_producidos : (float) $pedido->detalles->sum('cantidad');
                                                    $direccion = $pedido->cliente?->direccionCompleta() ?: '';
                                                @endphp
                                                <div class="col-md-6">
                                                    <div class="form-check fs-8">
                                                        <input class="form-check-input" type="checkbox" name="pedido_ids[]" value="{{ $pedido->id }}" id="add_ped_{{ $pedido->id }}">
                                                        <label class="form-check-label" for="add_ped_{{ $pedido->id }}">
                                                            {{ $pedido->folio ?? ('#' . $pedido->id) }} · {{ $pedido->cliente->nombre ?? '—' }}
                                                            <span class="text-muted d-block fs-9">
                                                                {{ number_format((float) $metros, 1) }} m
                                                                @if ($op) · OP {{ $op->folio }} · {{ (int) $op->total_piezas_producidas }} pzas @endif
                                                            </span>
                                                            <span class="d-block {{ $direccion ? 'text-marino' : 'text-danger' }} fs-9">
                                                                <i class="fa-solid fa-location-dot me-1"></i>
                                                                {{ $direccion !== '' ? $direccion : 'Sin dirección' }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if ($cargasDisponibles->isNotEmpty())
                                    <div class="mb-1">
                                        <div class="text-muted fs-8 mb-1">Cargas avisadas</div>
                                        <div class="row g-2">
                                            @foreach ($cargasDisponibles as $carga)
                                                @php
                                                    $direccionC = $carga->destino_texto
                                                        ?: ($carga->pedido?->cliente?->direccionCompleta() ?: '');
                                                @endphp
                                                <div class="col-md-6">
                                                    <div class="form-check fs-8">
                                                        <input class="form-check-input" type="checkbox" name="carga_ids[]" value="{{ $carga->id }}" id="add_{{ $carga->id }}">
                                                        <label class="form-check-label" for="add_{{ $carga->id }}">
                                                            {{ $carga->folio }} · {{ $carga->pedido->cliente->nombre ?? '—' }}
                                                            <span class="text-muted d-block fs-9">
                                                                {{ number_format($carga->metrosCarga(), 1) }} m
                                                            </span>
                                                            <span class="d-block {{ $direccionC ? 'text-marino' : 'text-danger' }} fs-9">
                                                                <i class="fa-solid fa-location-dot me-1"></i>
                                                                {{ $direccionC !== '' ? $direccionC : 'Sin dirección' }}
                                                            </span>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <button type="submit" class="btn btn-baseColor-light btn-sm mt-2">
                                    <i class="fa-solid fa-plus"></i> Agregar a ruta
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('almacen.cargas.ordenes', $rutaSeleccionada->id) }}" id="formOrdenesRuta"
                              class="modern-form"
                              data-reordenar-url="{{ route('almacen.cargas.reordenar', $rutaSeleccionada->id) }}"
                              data-ruta-cerrada="{{ $rutaSeleccionada->estaCerrada() ? '1' : '0' }}">
                            @csrf
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fs-8 text-muted" id="hintDragRuta">
                                    <i class="fa-solid fa-arrows-up-down"></i> Arrastre por el asa ⋮⋮ para reordenar entregas
                                </div>
                                <div class="fs-8 text-success d-none" id="statusDragRuta">
                                    <i class="fa-solid fa-circle-check"></i> Orden guardado
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover modern-table align-middle mb-0" id="tablaOrdenesRuta">
                                    <thead>
                                        <tr>
                                            <th style="width:36px"></th>
                                            <th>Pedido / OP</th>
                                            <th>Producción</th>
                                            <th>Destino</th>
                                            <th style="width:90px">Km</th>
                                            <th style="width:90px">Entrega #</th>
                                            <th style="width:80px">Carga #</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyOrdenesRuta">
                                        @forelse ($rutaSeleccionada->cargas as $idx => $carga)
                                            @php
                                                $op = $carga->ordenProduccion;
                                                $destino = $carga->destino_texto
                                                    ?: ($carga->pedido?->cliente?->direccionCompleta() ?: '');
                                            @endphp
                                            <tr class="fila-carga-ruta" data-carga-id="{{ $carga->id }}">
                                                <td class="text-center text-muted drag-handle" style="cursor:grab" title="Arrastrar">
                                                    <i class="fa-solid fa-grip-vertical"></i>
                                                </td>
                                                <td>
                                                    <strong class="js-pedido-folio">{{ $carga->pedido->folio ?? ('Ped #' . $carga->pedido_id) }}</strong>
                                                    <div class="text-muted fs-9">{{ $carga->folio }}</div>
                                                    <span class="js-cliente">{{ $carga->pedido->cliente->nombre ?? '—' }}</span>
                                                    @if ($op)
                                                        <div><span class="badge badge-success-dark">OP {{ $op->folio }}</span></div>
                                                    @endif
                                                    <input type="hidden" name="items[{{ $idx }}][carga_id]" value="{{ $carga->id }}" class="js-carga-id">
                                                </td>
                                                <td>
                                                    {{ number_format($carga->metrosCarga(), 1) }} m<br>
                                                    @if ($op)
                                                        {{ (int) $op->total_piezas_producidas }} pzas
                                                        @if ($op->nave_destino)
                                                            <div class="text-muted fs-9">{{ $op->nave_destino }}</div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">Existencia / pedido</span>
                                                    @endif
                                                </td>
                                                <td class="js-destino">
                                                    @if ($destino !== '')
                                                        <i class="fa-solid fa-location-dot text-marino me-1"></i>{{ $destino }}
                                                    @else
                                                        <span class="text-danger">Sin dirección en el cliente</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                                        name="items[{{ $idx }}][distancia_km]"
                                                        value="{{ $carga->distancia_km }}"
                                                        title="Km desde planta / base al destino">
                                                </td>
                                                <td>
                                                    <input type="number" min="1" class="form-control form-control-sm js-orden-entrega"
                                                        name="items[{{ $idx }}][orden_entrega]"
                                                        value="{{ $carga->orden_entrega }}"
                                                        title="1 = más cerca (primera descarga)">
                                                    <div class="text-muted fs-9">cerca→lejos</div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary js-orden-carga">{{ $carga->orden_carga ?? '—' }}</span>
                                                    <div class="text-muted fs-9">lejos→fondo</div>
                                                </td>
                                                <td>
                                                    <button type="submit" form="form-quitar-{{ $carga->id }}" class="btn btn-outline-danger btn-sm" title="Quitar">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="sin-ordenes-ruta">
                                                <td colspan="8" class="text-muted text-center py-4">Esta ruta aún no tiene órdenes. Agréguelas desde la cola de OP terminadas.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($rutaSeleccionada->cargas->isNotEmpty())
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <button type="submit" class="btn btn-baseColor btn-sm">
                                        <i class="fa-solid fa-floppy-disk"></i> Guardar km / órdenes
                                    </button>
                                    <button type="submit" name="ordenar_por_distancia" value="1" class="btn btn-baseColor-light btn-sm">
                                        <i class="fa-solid fa-arrow-down-short-wide"></i> Ordenar por distancia
                                    </button>
                                    <a class="btn btn-baseColor-light btn-sm"
                                       href="{{ route('almacen.cargas.pdf', $rutaSeleccionada->id) }}"
                                       target="_blank" rel="noopener">
                                        <i class="fa-solid fa-print"></i> Imprimir / PDF
                                    </a>
                                </div>
                            @endif
                        </form>

                        @foreach ($rutaSeleccionada->cargas as $carga)
                            <form id="form-quitar-{{ $carga->id }}" method="POST"
                                action="{{ route('almacen.cargas.quitar', [$rutaSeleccionada->id, $carga->id]) }}" class="d-none">
                                @csrf
                            </form>
                        @endforeach

                        @if ($rutaSeleccionada->cargas->isNotEmpty())
                            <hr>
                            <div class="row g-3" id="guiasSecuenciaRuta">
                                <div class="col-md-6">
                                    <div class="panel-hint h-100">
                                        <h6 class="text-marino mb-1">1) Cómo cargar el camión</h6>
                                        <p class="text-muted fs-8 mb-2">Suba en este orden (1 = al fondo; sale al final).</p>
                                        <ol class="fs-8 mb-0" id="listaOrdenCarga">
                                            @foreach ($rutaSeleccionada->cargas->sortBy('orden_carga') as $carga)
                                                @php $op = $carga->ordenProduccion; @endphp
                                                <li class="mb-1" data-carga-id="{{ $carga->id }}">
                                                    <strong class="js-num">#{{ $carga->orden_carga }}</strong>
                                                    <span class="js-txt">{{ $carga->pedido->cliente->nombre ?? '—' }}
                                                    · {{ $carga->pedido->folio ?? $carga->folio }}
                                                    @if ($op)
                                                        <span class="text-muted">(OP {{ $op->folio }} · {{ number_format((float) $op->total_metros_producidos, 1) }} m)</span>
                                                    @endif
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="panel-hint h-100">
                                        <h6 class="text-marino mb-1">2) Cómo entregar (ruta)</h6>
                                        <p class="text-muted fs-8 mb-2">Paradas de cerca a lejos.</p>
                                        <ol class="fs-8 mb-0" id="listaOrdenEntrega">
                                            @foreach ($rutaSeleccionada->cargas->sortBy('orden_entrega') as $carga)
                                                <li class="mb-1" data-carga-id="{{ $carga->id }}">
                                                    <strong class="js-num">#{{ $carga->orden_entrega }}</strong>
                                                    <span class="js-txt">{{ $carga->pedido->cliente->nombre ?? '—' }}
                                                    <span class="text-muted">
                                                        @if ($carga->distancia_km !== null) · {{ number_format((float) $carga->distancia_km, 1) }} km @endif
                                                        · {{ \Illuminate\Support\Str::limit($carga->destino_texto ?: 'Sin destino', 40) }}
                                                    </span>
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @include('Ventas.partials.ruta_carga_operacion')
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if (!empty($rutaSeleccionada) && $rutaSeleccionada->cargas->isNotEmpty() && !$rutaSeleccionada->estaCerrada())
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const form = document.getElementById('formOrdenesRuta');
    const tbody = document.getElementById('tbodyOrdenesRuta');
    if (!form || !tbody || typeof Sortable === 'undefined') return;

    const url = form.getAttribute('data-reordenar-url');
    const token = form.querySelector('input[name="_token"]')?.value;
    const statusEl = document.getElementById('statusDragRuta');
    let saving = false;

    function reindexInputs() {
        const rows = [...tbody.querySelectorAll('tr.fila-carga-ruta')];
        const total = rows.length;
        rows.forEach((row, i) => {
            const entrega = i + 1;
            const carga = total - i;
            row.querySelectorAll('[name^="items["]').forEach((input) => {
                input.name = input.name.replace(/items\[\d+]/, 'items[' + i + ']');
            });
            const inpEntrega = row.querySelector('.js-orden-entrega');
            if (inpEntrega) inpEntrega.value = entrega;
            const badge = row.querySelector('.js-orden-carga');
            if (badge) badge.textContent = String(carga);
        });
        return rows.map((row) => parseInt(row.getAttribute('data-carga-id'), 10));
    }

    function pintarGuias(cargas) {
        const listaCarga = document.getElementById('listaOrdenCarga');
        const listaEntrega = document.getElementById('listaOrdenEntrega');
        if (!listaCarga || !listaEntrega || !Array.isArray(cargas)) return;

        const porCarga = [...cargas].sort((a, b) => (a.orden_carga || 0) - (b.orden_carga || 0));
        const porEntrega = [...cargas].sort((a, b) => (a.orden_entrega || 0) - (b.orden_entrega || 0));

        listaCarga.innerHTML = porCarga.map((c) => {
            const extra = c.op_folio
                ? ` <span class="text-muted">(OP ${c.op_folio}${c.metros != null ? ' · ' + c.metros + ' m' : ''})</span>`
                : '';
            return `<li class="mb-1" data-carga-id="${c.id}"><strong>#${c.orden_carga}</strong> ${c.cliente} · ${c.pedido_folio}${extra}</li>`;
        }).join('');

        listaEntrega.innerHTML = porEntrega.map((c) => {
            const km = c.distancia_km != null ? ` · ${Number(c.distancia_km).toFixed(1)} km` : '';
            const dest = c.destino ? ` · ${String(c.destino).slice(0, 40)}` : '';
            return `<li class="mb-1" data-carga-id="${c.id}"><strong>#${c.orden_entrega}</strong> ${c.cliente}<span class="text-muted">${km}${dest}</span></li>`;
        }).join('');
    }

    function guardarOrden(ids) {
        if (saving || !url || !token) return;
        saving = true;
        if (statusEl) {
            statusEl.classList.remove('d-none', 'text-success', 'text-danger');
            statusEl.classList.add('text-muted');
            statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando orden…';
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ carga_ids: ids })
        })
            .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.ok) throw new Error(data.message || 'No se pudo guardar');
                if (data.cargas) {
                    data.cargas.forEach((c) => {
                        const row = tbody.querySelector(`tr[data-carga-id="${c.id}"]`);
                        if (!row) return;
                        const inp = row.querySelector('.js-orden-entrega');
                        if (inp) inp.value = c.orden_entrega;
                        const badge = row.querySelector('.js-orden-carga');
                        if (badge) badge.textContent = String(c.orden_carga);
                    });
                    pintarGuias(data.cargas);
                }
                if (statusEl) {
                    statusEl.classList.remove('text-muted', 'text-danger');
                    statusEl.classList.add('text-success');
                    statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Orden guardado';
                    statusEl.classList.remove('d-none');
                    setTimeout(() => statusEl.classList.add('d-none'), 2500);
                }
            })
            .catch((err) => {
                if (statusEl) {
                    statusEl.classList.remove('text-muted', 'text-success');
                    statusEl.classList.add('text-danger');
                    statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + (err.message || 'Error al guardar');
                    statusEl.classList.remove('d-none');
                }
            })
            .finally(() => { saving = false; });
    }

    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'table-active',
        onEnd: function () {
            const ids = reindexInputs();
            guardarOrden(ids);
        }
    });
})();
</script>
@endif

{{-- Alta rápida camión --}}
<div class="modal fade" id="modalNuevoCamion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-marino"><i class="fa-solid fa-truck me-2"></i>Nuevo camión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label fs-8">Placas <span class="text-danger">*</span></label>
                    <input type="text" id="nuevoCamionPlacas" class="form-control form-control-sm" maxlength="40" placeholder="ABC-123-A">
                </div>
                <div class="mb-2">
                    <label class="form-label fs-8">Nombre / alias</label>
                    <input type="text" id="nuevoCamionNombre" class="form-control form-control-sm" maxlength="120" placeholder="Torton 1">
                </div>
                <div class="mb-2">
                    <label class="form-label fs-8">Tipo</label>
                    <input type="text" id="nuevoCamionTipo" class="form-control form-control-sm" maxlength="60" placeholder="Torton, Rabón, Trailer…">
                </div>
                <div class="mb-2">
                    <label class="form-label fs-8">Capacidad (metros de tubo) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" id="nuevoCamionCapM" class="form-control form-control-sm"
                        placeholder="Ej. 1200">
                    <div class="form-text fs-9">Se usa para validar si caben los metros de las OP en la ruta.</div>
                </div>
                <div class="mb-2">
                    <label class="form-label fs-8">Capacidad (ton)</label>
                    <input type="number" step="0.01" min="0" id="nuevoCamionCap" class="form-control form-control-sm">
                </div>
                <hr class="my-2">
                <div class="fs-8 text-muted mb-2">Datos Carta Porte (opcional)</div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label fs-8">PermSCT</label>
                        <input type="text" id="nuevoCamionPermSct" class="form-control form-control-sm" maxlength="20" placeholder="TPAF01" value="TPAF01">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fs-8">No. permiso SCT</label>
                        <input type="text" id="nuevoCamionNumPermiso" class="form-control form-control-sm" maxlength="80">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-8">Config. vehicular</label>
                        <input type="text" id="nuevoCamionConfig" class="form-control form-control-sm" maxlength="20" placeholder="VL" value="VL">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-8">Año modelo</label>
                        <input type="text" id="nuevoCamionAnio" class="form-control form-control-sm" maxlength="4" placeholder="{{ date('Y') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-8">Aseguradora RC</label>
                        <input type="text" id="nuevoCamionAseguradora" class="form-control form-control-sm" maxlength="120">
                    </div>
                    <div class="col-12">
                        <label class="form-label fs-8">Póliza RC</label>
                        <input type="text" id="nuevoCamionPoliza" class="form-control form-control-sm" maxlength="80">
                    </div>
                </div>
                <div id="nuevoCamionError" class="alert alert-danger border-0 fs-8 d-none py-2 mt-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-baseColor-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-baseColor btn-sm" id="btnGuardarCamion">Guardar y seleccionar</button>
            </div>
        </div>
    </div>
</div>

{{-- Alta rápida chofer --}}
<div class="modal fade" id="modalNuevoChofer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-marino"><i class="fa-solid fa-id-card me-2"></i>Nuevo chofer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label fs-8">Tipo <span class="text-danger">*</span></label>
                    <select id="nuevoChoferTipo" class="form-select form-select-sm">
                        <option value="INTERNO">Interno (empleado)</option>
                        <option value="EXTERNO">Externo (alta aquí)</option>
                    </select>
                </div>

                <div id="bloqueChoferInterno">
                    <div class="mb-2">
                        <label class="form-label fs-8">Empleado dado de alta <span class="text-danger">*</span></label>
                        <select id="nuevoChoferEmpleado" class="form-select form-select-sm">
                            <option value="">— Seleccione empleado —</option>
                            @foreach (($empleados ?? collect()) as $emp)
                                @php
                                    $nomEmp = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                                        $emp->primer_nombre ?? null,
                                        $emp->segundo_nombre ?? null,
                                        $emp->apellido_paterno ?? null,
                                        $emp->apellido_materno ?? null,
                                    ]))));
                                @endphp
                                <option value="{{ $emp->id }}" data-tel="{{ $emp->telefono ?? '' }}">{{ $nomEmp }}</option>
                            @endforeach
                        </select>
                        <div class="form-text fs-9">Solo empleados activos de RH.</div>
                    </div>
                </div>

                <div id="bloqueChoferExterno" class="d-none">
                    <div class="mb-2">
                        <label class="form-label fs-8">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" id="nuevoChoferNombre" class="form-control form-control-sm" maxlength="120">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fs-8">Empresa <span class="text-danger">*</span></label>
                        <input type="text" id="nuevoChoferEmpresa" class="form-control form-control-sm" maxlength="120">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fs-8">Teléfono</label>
                        <input type="text" id="nuevoChoferTel" class="form-control form-control-sm" maxlength="40">
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fs-8">Licencia</label>
                    <input type="text" id="nuevoChoferLic" class="form-control form-control-sm" maxlength="60">
                </div>
                <div class="mb-2">
                    <label class="form-label fs-8">RFC (Carta Porte)</label>
                    <input type="text" id="nuevoChoferRfc" class="form-control form-control-sm" maxlength="13" placeholder="XAXX010101000">
                </div>

                <div id="nuevoChoferError" class="alert alert-danger border-0 fs-8 d-none py-2"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-baseColor-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-baseColor btn-sm" id="btnGuardarChofer">Guardar y seleccionar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('#formNuevaRutaCarga input[name="_token"]')?.value;

    const formNuevaRuta = document.getElementById('formNuevaRutaCarga');
    if (formNuevaRuta) {
        formNuevaRuta.addEventListener('submit', function (e) {
            const err = document.getElementById('errorNuevaRutaOps');
            const hayOp = [...formNuevaRuta.querySelectorAll('.js-ruta-op:checked')].length > 0;
            if (!hayOp) {
                e.preventDefault();
                if (err) err.classList.remove('d-none');
                return false;
            }
            if (err) err.classList.add('d-none');
        });
        formNuevaRuta.querySelectorAll('.js-ruta-op').forEach(function (cb) {
            cb.addEventListener('change', function () {
                const err = document.getElementById('errorNuevaRutaOps');
                if (err && [...formNuevaRuta.querySelectorAll('.js-ruta-op:checked')].length > 0) {
                    err.classList.add('d-none');
                }
                actualizarCapacidadNuevaRuta();
            });
        });
    }

    function actualizarCapacidadNuevaRuta() {
        const panel = document.getElementById('panelCapacidadNuevaRuta');
        const sel = document.getElementById('selectCamionRuta');
        if (!panel || !sel) return;

        const opt = sel.options[sel.selectedIndex];
        const capM = parseFloat(opt?.dataset?.capM || '');
        let metros = 0;
        document.querySelectorAll('#formNuevaRutaCarga .js-ruta-op:checked').forEach(function (cb) {
            metros += parseFloat(cb.dataset.metros || '0') || 0;
        });
        metros = Math.round(metros * 100) / 100;

        if (!sel.value) {
            panel.className = 'alert alert-secondary border-0 fs-8 py-2 mt-2 mb-0';
            panel.textContent = metros > 0
                ? ('Selección actual: ' + metros.toFixed(1) + ' m. Elija un camión para validar capacidad.')
                : 'Elija un camión y marque pedidos para ver si caben.';
            panel.classList.remove('d-none');
            return;
        }

        if (!(capM > 0)) {
            panel.className = 'alert alert-warning border-0 fs-8 py-2 mt-2 mb-0';
            panel.innerHTML = 'Camión sin capacidad en metros. Capture <strong>capacidad (m)</strong> al dar de alta el camión. Selección: '
                + metros.toFixed(1) + ' m.';
            panel.classList.remove('d-none');
            return;
        }

        const libre = Math.round((capM - metros) * 100) / 100;
        const cabe = metros <= capM;
        panel.className = 'alert ' + (cabe ? 'alert-success' : 'alert-danger') + ' border-0 fs-8 py-2 mt-2 mb-0';
        panel.innerHTML = (cabe
            ? '<i class="fa-solid fa-circle-check me-1"></i> <strong>Sí caben.</strong> '
            : '<i class="fa-solid fa-triangle-exclamation me-1"></i> <strong>No caben.</strong> ')
            + metros.toFixed(1) + ' m de ' + capM.toFixed(1) + ' m'
            + (libre >= 0
                ? (' · libres ' + libre.toFixed(1) + ' m')
                : (' · exceso ' + Math.abs(libre).toFixed(1) + ' m'));
        panel.classList.remove('d-none');
    }

    const selCamionCap = document.getElementById('selectCamionRuta');
    if (selCamionCap) {
        selCamionCap.addEventListener('change', actualizarCapacidadNuevaRuta);
        actualizarCapacidadNuevaRuta();
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body)
        }).then(function (r) {
            return r.json().then(function (data) { return { ok: r.ok, data: data }; });
        });
    }

    const btnCamion = document.getElementById('btnGuardarCamion');
    const selCamion = document.getElementById('selectCamionRuta');
    if (btnCamion && selCamion) {
        btnCamion.addEventListener('click', function () {
            const err = document.getElementById('nuevoCamionError');
            const payload = {
                placas: (document.getElementById('nuevoCamionPlacas')?.value || '').trim(),
                nombre: (document.getElementById('nuevoCamionNombre')?.value || '').trim(),
                tipo: (document.getElementById('nuevoCamionTipo')?.value || '').trim(),
                capacidad_ton: document.getElementById('nuevoCamionCap')?.value || null,
                capacidad_metros: document.getElementById('nuevoCamionCapM')?.value || null,
                perm_sct: (document.getElementById('nuevoCamionPermSct')?.value || '').trim(),
                num_permiso_sct: (document.getElementById('nuevoCamionNumPermiso')?.value || '').trim(),
                config_vehicular: (document.getElementById('nuevoCamionConfig')?.value || '').trim(),
                anio_modelo: (document.getElementById('nuevoCamionAnio')?.value || '').trim(),
                asegura_resp_civil: (document.getElementById('nuevoCamionAseguradora')?.value || '').trim(),
                poliza_resp_civil: (document.getElementById('nuevoCamionPoliza')?.value || '').trim()
            };
            if (err) { err.classList.add('d-none'); err.textContent = ''; }
            if (!payload.placas) {
                if (err) { err.textContent = 'Capture las placas.'; err.classList.remove('d-none'); }
                return;
            }
            btnCamion.disabled = true;
            postJson(@json(route('almacen.cargas.camiones.store')), payload)
                .then(function (res) {
                    if (!res.ok || !res.data.ok) {
                        throw new Error(res.data.message || (res.data.errors ? Object.values(res.data.errors).flat().join(' ') : 'No se pudo guardar'));
                    }
                    const c = res.data.camion;
                    const opt = document.createElement('option');
                    opt.value = String(c.id);
                    opt.textContent = c.etiqueta_capacidad || c.etiqueta;
                    opt.dataset.capM = c.capacidad_metros != null ? String(c.capacidad_metros) : '';
                    opt.dataset.capT = c.capacidad_ton != null ? String(c.capacidad_ton) : '';
                    opt.selected = true;
                    selCamion.appendChild(opt);
                    selCamion.value = String(c.id);
                    actualizarCapacidadNuevaRuta();
                    const modalEl = document.getElementById('modalNuevoCamion');
                    if (modalEl && window.bootstrap) {
                        (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).hide();
                    }
                })
                .catch(function (e) {
                    if (err) { err.textContent = e.message || 'Error'; err.classList.remove('d-none'); }
                })
                .finally(function () { btnCamion.disabled = false; });
        });
    }

    const btnChofer = document.getElementById('btnGuardarChofer');
    const selChofer = document.getElementById('selectChoferRuta');
    const tipoChofer = document.getElementById('nuevoChoferTipo');
    const bloqueInt = document.getElementById('bloqueChoferInterno');
    const bloqueExt = document.getElementById('bloqueChoferExterno');

    function syncTipoChofer() {
        const externo = (tipoChofer?.value || 'INTERNO') === 'EXTERNO';
        if (bloqueInt) bloqueInt.classList.toggle('d-none', externo);
        if (bloqueExt) bloqueExt.classList.toggle('d-none', !externo);
    }
    if (tipoChofer) {
        tipoChofer.addEventListener('change', syncTipoChofer);
        syncTipoChofer();
    }

    if (btnChofer && selChofer) {
        btnChofer.addEventListener('click', function () {
            const err = document.getElementById('nuevoChoferError');
            const tipo = tipoChofer?.value || 'INTERNO';
            const payload = { tipo: tipo };
            if (tipo === 'INTERNO') {
                payload.empleado_id = document.getElementById('nuevoChoferEmpleado')?.value || '';
            } else {
                payload.nombre = (document.getElementById('nuevoChoferNombre')?.value || '').trim();
                payload.empresa_externa = (document.getElementById('nuevoChoferEmpresa')?.value || '').trim();
                payload.telefono = (document.getElementById('nuevoChoferTel')?.value || '').trim();
            }
            payload.licencia = (document.getElementById('nuevoChoferLic')?.value || '').trim();
            payload.rfc = (document.getElementById('nuevoChoferRfc')?.value || '').trim().toUpperCase();
            if (err) { err.classList.add('d-none'); err.textContent = ''; }
            if (tipo === 'INTERNO' && !payload.empleado_id) {
                if (err) { err.textContent = 'Seleccione el empleado interno.'; err.classList.remove('d-none'); }
                return;
            }
            if (tipo === 'EXTERNO' && (!payload.nombre || !payload.empresa_externa)) {
                if (err) { err.textContent = 'Capture nombre y empresa del chofer externo.'; err.classList.remove('d-none'); }
                return;
            }
            btnChofer.disabled = true;
            postJson(@json(route('almacen.cargas.choferes.store')), payload)
                .then(function (res) {
                    if (!res.ok || !res.data.ok) {
                        throw new Error(res.data.message || (res.data.errors ? Object.values(res.data.errors).flat().join(' ') : 'No se pudo guardar'));
                    }
                    const c = res.data.chofer;
                    let opt = [...selChofer.options].find(function (o) { return o.value === String(c.id); });
                    if (!opt) {
                        opt = document.createElement('option');
                        opt.value = String(c.id);
                        selChofer.appendChild(opt);
                    }
                    opt.textContent = c.etiqueta;
                    opt.setAttribute('data-tipo', c.tipo);
                    opt.selected = true;
                    selChofer.value = String(c.id);
                    document.querySelectorAll('select[name="chofer_id"]').forEach(function (sel) {
                        if (sel === selChofer) return;
                        if ([...sel.options].some(function (o) { return o.value === String(c.id); })) return;
                        const o = document.createElement('option');
                        o.value = String(c.id);
                        o.textContent = c.etiqueta;
                        o.setAttribute('data-tipo', c.tipo);
                        sel.appendChild(o);
                    });
                    const modalEl = document.getElementById('modalNuevoChofer');
                    if (modalEl && window.bootstrap) {
                        (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).hide();
                    }
                })
                .catch(function (e) {
                    if (err) { err.textContent = e.message || 'Error'; err.classList.remove('d-none'); }
                })
                .finally(function () { btnChofer.disabled = false; });
        });
    }
})();
</script>
@endsection
