@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
    .req-wrap { max-width: 1100px; margin: 0 auto; }
    .req-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1rem; margin-bottom:.9rem; }
    .req-card h3 { font-size:.95rem; font-weight:800; margin:0 0 .75rem; }
    .req-badge { display:inline-block; border-radius:999px; padding:.2rem .55rem; font-size:.75rem; font-weight:800; background:#f3f4f6; }
    .timeline { list-style:none; padding:0; margin:0; }
    .timeline li { border-left:2px solid #e5e7eb; padding:0 0 .85rem 1rem; position:relative; }
    .timeline li:before { content:''; position:absolute; left:-5px; top:.35rem; width:8px; height:8px; border-radius:50%; background:#111827; }
    .timeline small { color:#6b7280; }
</style>

<div class="container-fluid py-3 req-wrap">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <div class="text-muted" style="font-size:.78rem">
                <a href="{{ route('requisiciones.index') }}">Requisiciones</a> /
                {{ $requisicion->folio }}
            </div>
            <h1 class="h4 mb-1 fw-bold">{{ $requisicion->folio }}</h1>
            <span class="req-badge">{{ $requisicion->estadoLabel() }}</span>
            <span class="req-badge ms-1">{{ $requisicion->prioridadLabel() }}</span>
            @if(in_array($requisicion->estado, ['pendiente_autorizacion','reenviada'], true) && $requisicion->responsableActual)
                <span class="req-badge ms-1">Espera a: {{ $requisicion->responsableActual->name }}</span>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('requisiciones.index') }}" class="btn btn-sm btn-outline-secondary">Lista</a>
            @if($puedeEditar)
                <a href="{{ route('requisiciones.edit', $requisicion->id) }}" class="btn btn-sm btn-outline-dark">Editar</a>
            @endif
            @if($puedeEnviar)
                <form method="post" action="{{ route('requisiciones.enviar', $requisicion->id) }}">@csrf
                    <button class="btn btn-sm btn-dark">Enviar a autorización</button>
                </form>
            @endif
            @if($requisicion->licitacion)
                <a href="{{ route('licitaciones.editar', $requisicion->licitacion->id) }}"
                   class="btn btn-sm btn-baseColor"
                   title="Abrir licitación {{ $requisicion->licitacion->folio }}">
                    <i class="fa-solid fa-scale-balanced"></i> Ir a licitación
                </a>
            @elseif(in_array($requisicion->estado, ['autorizada', 'en_proceso_compra', 'atendida_parcial'], true))
                <form method="post" action="{{ route('licitaciones.desdeRequisicion', $requisicion->id) }}">
                    @csrf
                    <button class="btn btn-sm btn-baseColor" type="submit"
                            onclick="return confirm('¿Crear licitación con las partidas catalogadas de esta requisición?');">
                        <i class="fa-solid fa-scale-balanced"></i> Crear licitación
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success py-2">{{ session('success') }}</div> @endif
    @if(session('warning')) <div class="alert alert-warning py-2">{{ session('warning') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger py-2"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="req-card">
                <h3>Encabezado</h3>
                <div class="row g-2" style="font-size:.9rem">
                    <div class="col-md-6"><strong>Solicitante:</strong> {{ $requisicion->solicitante->name ?? '—' }}</div>
                    <div class="col-md-6"><strong>Tipo / familia:</strong> {{ optional($requisicion->tipoParam)->nombre ?: ($requisicion->departamento ?: '—') }}</div>
                    <div class="col-md-6"><strong>Centro de costos:</strong> {{ $requisicion->centro_costo ?: '—' }}</div>
                    <div class="col-md-6"><strong>Fecha solicitud:</strong> {{ optional($requisicion->fecha_solicitud)->format('d/m/Y') }}</div>
                    <div class="col-md-6"><strong>Fecha requerida:</strong> {{ optional($requisicion->fecha_requerida)->format('d/m/Y') ?: '—' }}</div>
                    <div class="col-md-6"><strong>Responsable actual:</strong> {{ $requisicion->responsableActual->name ?? '—' }}</div>
                    <div class="col-12"><strong>Justificación:</strong><br>{{ $requisicion->justificacion ?: '—' }}</div>
                </div>
            </div>

            <div class="req-card">
                <h3>Partidas</h3>
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:.85rem">
                        <thead><tr><th>#</th><th>Descripción</th><th>UM</th><th class="text-end">Cant.</th><th class="text-end">Comprada</th><th class="text-end">Pendiente</th></tr></thead>
                        <tbody>
                            @foreach($requisicion->detalles as $i => $d)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $d->descripcion }}</div>
                                        <div class="text-muted" style="font-size:.75rem">{{ $d->codigo ?: ($d->es_no_catalogado ? 'No catalogado' : '') }}</div>
                                        @if($d->especificaciones)<div class="text-muted" style="font-size:.75rem">{{ $d->especificaciones }}</div>@endif
                                    </td>
                                    <td>{{ $d->unidad_medida }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($d->cantidad, 4, '.', ','), '0'), '.') }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($d->cantidad_comprada, 4, '.', ','), '0'), '.') }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($d->cantidadPendiente(), 4, '.', ','), '0'), '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($puedeDecidir)
            <div class="req-card">
                <h3>Tu decisión</h3>
                <div class="row g-2">
                    <div class="col-md-4">
                        <form method="post" action="{{ route('requisiciones.autorizar', $requisicion->id) }}">
                            @csrf
                            <label class="form-label">Comentario (opcional)</label>
                            <textarea name="comentario" class="form-control form-control-sm mb-2" rows="2"></textarea>
                            <button class="btn btn-success w-100"><i class="fa-solid fa-check"></i> Autorizar</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="post" action="{{ route('requisiciones.devolver', $requisicion->id) }}">
                            @csrf
                            <label class="form-label">Motivo (obligatorio)</label>
                            <textarea name="comentario" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                            <button class="btn btn-warning w-100"><i class="fa-solid fa-rotate-left"></i> Devolver</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="post" action="{{ route('requisiciones.rechazar', $requisicion->id) }}">
                            @csrf
                            <label class="form-label">Motivo (obligatorio)</label>
                            <textarea name="comentario" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                            <button class="btn btn-danger w-100"><i class="fa-solid fa-xmark"></i> Rechazar</button>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="req-card">
                <h3>Niveles de esta requisición</h3>
                <p class="text-muted mb-2" style="font-size:.75rem">
                    La obligatoriedad es <strong>por requisición</strong>, no la plantilla global.
                </p>

                @if(!empty($puedeAjustarNiveles) && $requisicion->aprobaciones->count())
                    <form method="post" action="{{ route('requisiciones.niveles.update', $requisicion->id) }}">
                        @csrf
                        @foreach($requisicion->aprobaciones as $i => $a)
                            <div class="mb-2 p-2 border rounded-3" style="font-size:.82rem">
                                <input type="hidden" name="niveles[{{ $i }}][id]" value="{{ $a->id }}">
                                <div class="fw-bold">{{ $a->orden }}. {{ $a->nombre_nivel }}</div>
                                <div class="row g-1 align-items-center mt-1">
                                    <div class="col-12">
                                        <label class="form-label mb-0" style="font-size:.7rem">Responsable</label>
                                        @if($a->estado === 'pendiente')
                                            <select name="niveles[{{ $i }}][responsable_id]" class="form-select form-select-sm">
                                                @foreach($users as $u)
                                                    <option value="{{ $u->id }}" {{ (int)$a->responsable_id === (int)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <div>{{ $a->responsable->name ?? '—' }}</div>
                                            <input type="hidden" name="niveles[{{ $i }}][responsable_id]" value="{{ $a->responsable_id }}">
                                        @endif
                                    </div>
                                    <div class="col-12 mt-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="niveles[{{ $i }}][obligatorio]" value="1"
                                                   id="ob-req-{{ $a->id }}" {{ $a->obligatorio ? 'checked' : '' }}
                                                   {{ in_array($a->estado, ['autorizado','rechazado'], true) ? 'disabled' : '' }}>
                                            <label class="form-check-label" for="ob-req-{{ $a->id }}">Obligatorio</label>
                                        </div>
                                        @if(in_array($a->estado, ['autorizado','rechazado'], true))
                                            <input type="hidden" name="niveles[{{ $i }}][obligatorio]" value="{{ $a->obligatorio ? 1 : 0 }}">
                                        @endif
                                    </div>
                                </div>
                                <div class="text-muted mt-1">
                                    {{ ucfirst($a->estado) }}
                                    @if($a->fecha_decision)· {{ $a->fecha_decision->format('d/m/Y H:i') }}@endif
                                </div>
                                @if($a->comentario)<div class="mt-1">“{{ $a->comentario }}”</div>@endif
                            </div>
                        @endforeach
                        <button class="btn btn-sm btn-baseColor w-100" type="submit"
                                onclick="return confirm('¿Actualizar niveles de esta requisición? Si ya no quedan obligatorios pendientes, quedará autorizada y pasará a licitación.');">
                            Guardar niveles de la requisición
                        </button>
                    </form>
                @else
                    @forelse($requisicion->aprobaciones as $a)
                        <div class="mb-2 p-2 border rounded-3" style="font-size:.82rem">
                            <div class="fw-bold">{{ $a->orden }}. {{ $a->nombre_nivel }}
                                @if($a->obligatorio)<span class="badge bg-dark ms-1">Oblig.</span>@else<span class="badge bg-secondary ms-1">Opcional</span>@endif
                            </div>
                            <div>{{ $a->responsable->name ?? '—' }}</div>
                            <div class="text-muted">{{ ucfirst($a->estado) }} @if($a->fecha_decision)· {{ $a->fecha_decision->format('d/m/Y H:i') }}@endif</div>
                            @if($a->comentario)<div class="mt-1">“{{ $a->comentario }}”</div>@endif
                        </div>
                    @empty
                        <div class="text-muted" style="font-size:.85rem">Aún no se ha enviado a autorización.</div>
                    @endforelse
                @endif
            </div>

            <div class="req-card">
                <h3>Historial</h3>
                <ul class="timeline">
                    @forelse($requisicion->historial as $h)
                        <li>
                            <div class="fw-semibold" style="font-size:.85rem">{{ $h->accion }}</div>
                            <small>{{ optional($h->created_at)->format('d/m/Y H:i') }} · {{ $h->usuario->name ?? 'Sistema' }}</small>
                            @if($h->comentario)<div style="font-size:.82rem">{{ $h->comentario }}</div>@endif
                        </li>
                    @empty
                        <li><div class="text-muted">Sin eventos</div></li>
                    @endforelse
                </ul>
            </div>

            @if(in_array($requisicion->estado, ['borrador','devuelta','pendiente_autorizacion','reenviada','autorizada'], true))
            <div class="req-card">
                <h3>Cancelar</h3>
                <form method="post" action="{{ route('requisiciones.cancelar', $requisicion->id) }}" onsubmit="return confirm('¿Cancelar esta requisición?');">
                    @csrf
                    <textarea name="comentario" class="form-control form-control-sm mb-2" rows="2" required placeholder="Motivo de cancelación"></textarea>
                    <button class="btn btn-outline-danger btn-sm w-100">Cancelar requisición</button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
