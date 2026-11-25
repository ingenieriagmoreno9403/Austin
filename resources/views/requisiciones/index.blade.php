@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
    .req-acciones-row {
        display: inline-flex;
        flex-wrap: wrap;
        gap: .25rem;
        justify-content: center;
        align-items: center;
    }
    .req-acciones-row .btn {
        padding: .25rem .45rem;
        line-height: 1;
    }
    .req-badge {
        display: inline-block;
        border-radius: 999px;
        padding: .15rem .5rem;
        font-size: .72rem;
        font-weight: 700;
    }
    .st-borrador { background: #f3f4f6; color: #374151; }
    .st-pendiente_autorizacion, .st-reenviada { background: #fffbeb; color: #b45309; }
    .st-devuelta { background: #eff6ff; color: #1d4ed8; }
    .st-rechazada, .st-cancelada { background: #fef2f2; color: #b91c1c; }
    .st-autorizada { background: #ecfdf5; color: #047857; }
    .st-en_proceso_compra, .st-atendida_parcial { background: #f5f3ff; color: #6d28d9; }
    .st-atendida { background: #ecfdf5; color: #065f46; }
    .req-filters .form-label {
        font-size: .75rem;
        font-weight: 700;
        color: #6b7280;
        margin-bottom: .15rem;
    }
</style>

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Requisiciones</h2>
                        <p class="text-muted mb-0">
                            @if($vista === 'historial')
                                Autorizadas (en licitaciones), atendidas, rechazadas o canceladas
                            @elseif($vista === 'autorizar')
                                Pendientes de autorización asignadas a tu usuario
                            @else
                                Solicitud y autorización por niveles (antes de pasar a licitaciones)
                            @endif
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('requisiciones.create') }}" class="btn btn-baseColor">
                        <i class="fa-solid fa-plus"></i> Nueva requisición
                    </a>
                    <a href="{{ route('requisiciones.index', ['vista' => 'historial']) }}"
                       class="btn {{ $vista === 'historial' ? 'btn-baseColor' : 'btn-baseColor-light' }}">
                        <i class="fa-solid fa-clock-rotate-left"></i> Historial de requisiciones
                    </a>
                    @if(!empty($puedeConfigurar))
                        <a href="{{ route('requisiciones.config') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-route"></i> Rutas
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning py-2">{{ session('warning') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="row g-3 compras-kpi-row mb-3">
        <div class="col-6 col-md">
            <a href="{{ route('requisiciones.index', ['vista' => !empty($puedeVerTodas) ? 'todas' : 'activas']) }}" class="text-decoration-none">
                <div class="compras-kpi compras-kpi--success {{ in_array($vista, ['activas', 'todas'], true) ? 'border border-2' : '' }}">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">{{ !empty($puedeVerTodas) ? 'Todas' : 'Mis activas' }}</span>
                        <span class="compras-kpi__value">{{ $kpis['mias'] ?? 0 }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true"><i class="fa-solid fa-folder-open"></i></div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md">
            <a href="{{ route('requisiciones.index', ['vista' => 'autorizar']) }}" class="text-decoration-none">
                <div class="compras-kpi compras-kpi--warning {{ $vista === 'autorizar' ? 'border border-2' : '' }}">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Por autorizar</span>
                        <span class="compras-kpi__value">{{ $kpis['autorizar'] ?? 0 }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true"><i class="fa-solid fa-user-check"></i></div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md">
            <a href="{{ route('requisiciones.index', ['vista' => 'historial']) }}" class="text-decoration-none">
                <div class="compras-kpi compras-kpi--secondary {{ $vista === 'historial' ? 'border border-2' : '' }}">
                    <div class="compras-kpi__body">
                        <span class="compras-kpi__label">Historial</span>
                        <span class="compras-kpi__value">{{ $kpis['historial'] ?? 0 }}</span>
                    </div>
                    <div class="compras-kpi__icon" aria-hidden="true"><i class="fa-solid fa-clock-rotate-left"></i></div>
                </div>
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="m-0 font-weight-bold text-orange">
                @if($vista === 'historial')
                    Historial de requisiciones
                @elseif($vista === 'autorizar')
                    Bandeja de autorización
                @elseif($vista === 'todas')
                    Requisiciones en curso
                @else
                    Listado de requisiciones
                @endif
            </h6>
            @if(!empty($puedeVerTodas) && $vista !== 'historial')
                <a href="{{ route('requisiciones.index', ['vista' => 'todas']) }}"
                   class="btn btn-sm {{ $vista === 'todas' ? 'btn-baseColor' : 'btn-outline-secondary' }}">
                    Ver todas
                </a>
            @endif
        </div>
        <div class="card-body">
            <form class="req-filters row g-2 align-items-end mb-3" method="get">
                <input type="hidden" name="vista" value="{{ $vista }}">
                <div class="col-md-2">
                    <label class="form-label">Folio</label>
                    <input type="text" name="folio" value="{{ $filtros['folio'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @php
                            $estadosVista = $vista === 'historial'
                                ? \App\Models\Requisicion::ESTADOS_HISTORIAL
                                : ($vista === 'autorizar'
                                    ? ['pendiente_autorizacion', 'reenviada']
                                    : \App\Models\Requisicion::ESTADOS_ACTIVAS);
                        @endphp
                        @foreach($estadosVista as $k)
                            <option value="{{ $k }}" {{ ($filtros['estado'] ?? '') === $k ? 'selected' : '' }}>{{ $estados[$k] ?? $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <input type="text" name="departamento" value="{{ $filtros['departamento'] ?? '' }}" class="form-control form-control-sm" placeholder="Tecnologías…">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-baseColor-light btn-sm flex-grow-1">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('requisiciones.index', ['vista' => $vista]) }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover display modern-table w-100 align-middle">
                    <thead>
                        <tr>
                            <th class="text-center">Acciones</th>
                            <th class="text-center">Folio</th>
                            <th class="text-center">Fecha</th>
                            <th class="text-center">Solicitante</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Prioridad</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Responsable</th>
                            <th class="text-center">Requerida</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($lista as $r)
                            <tr>
                                <td class="text-center">
                                    <div class="req-acciones-row">
                                        <a class="btn btn-baseColor btn-sm" href="{{ route('requisiciones.show', $r->id) }}" title="Ver">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>

                                        @if($r->puede_editar)
                                            <a class="btn btn-primary btn-sm" href="{{ route('requisiciones.edit', $r->id) }}" title="Editar">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <form method="post" action="{{ route('requisiciones.enviar', $r->id) }}" class="d-inline"
                                                  onsubmit="return confirm('¿Enviar {{ $r->folio }} a autorización?');">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" title="Enviar a autorización">
                                                    <i class="fa-solid fa-paper-plane"></i>
                                                </button>
                                            </form>
                                        @endif

                                        @if($r->puede_decidir)
                                            <button type="button" class="btn btn-success btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalAutorizar{{ $r->id }}"
                                                    title="Autorizar">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-warning btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalDevolver{{ $r->id }}"
                                                    title="Devolver">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalRechazar{{ $r->id }}"
                                                    title="Rechazar">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        @endif

                                        @if($r->puede_cancelar)
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                    data-bs-toggle="modal" data-bs-target="#modalCancelar{{ $r->id }}"
                                                    title="Cancelar">
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                        @endif

                                        @if($r->licitacion)
                                            <a class="btn btn-baseColor btn-sm"
                                               href="{{ route('licitaciones.editar', $r->licitacion->id) }}"
                                               title="Ir a licitación {{ $r->licitacion->folio }}">
                                                <i class="fa-solid fa-scale-balanced"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center fw-bold">{{ $r->folio }}</td>
                                <td class="text-center">{{ optional($r->fecha_solicitud)->format('d/m/Y') }}</td>
                                <td class="text-center">{{ $r->solicitante->name ?? '—' }}</td>
                                <td class="text-center">{{ $r->departamento ?: '—' }}</td>
                                <td class="text-center">{{ $r->prioridadLabel() }}</td>
                                <td class="text-center"><span class="req-badge st-{{ $r->estado }}">{{ $r->estadoLabel() }}</span></td>
                                <td class="text-center">{{ $r->responsableActual->name ?? '—' }}</td>
                                <td class="text-center">{{ optional($r->fecha_requerida)->format('d/m/Y') ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Sin requisiciones en esta vista.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $lista->links() }}</div>
        </div>
    </div>
</div>

{{-- Modales de proceso por fila --}}
@foreach($lista as $r)
    @if($r->puede_decidir)
        <div class="modal fade" id="modalAutorizar{{ $r->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="{{ route('requisiciones.autorizar', $r->id) }}">
                        @csrf
                        <div class="modal-header border-0">
                            <h5 class="modal-title">Autorizar {{ $r->folio }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Comentario (opcional)</label>
                            <textarea name="comentario" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Autorizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalDevolver{{ $r->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="{{ route('requisiciones.devolver', $r->id) }}">
                        @csrf
                        <div class="modal-header border-0">
                            <h5 class="modal-title">Devolver {{ $r->folio }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Motivo (obligatorio)</label>
                            <textarea name="comentario" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-warning"><i class="fa-solid fa-rotate-left"></i> Devolver</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalRechazar{{ $r->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="{{ route('requisiciones.rechazar', $r->id) }}">
                        @csrf
                        <div class="modal-header border-0">
                            <h5 class="modal-title">Rechazar {{ $r->folio }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Motivo (obligatorio)</label>
                            <textarea name="comentario" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Rechazar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if($r->puede_cancelar)
        <div class="modal fade" id="modalCancelar{{ $r->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post" action="{{ route('requisiciones.cancelar', $r->id) }}">
                        @csrf
                        <div class="modal-header border-0">
                            <h5 class="modal-title">Cancelar {{ $r->folio }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-2">Esta acción no se puede deshacer.</p>
                            <label class="form-label">Motivo de cancelación</label>
                            <textarea name="comentario" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Cancelar requisición</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
