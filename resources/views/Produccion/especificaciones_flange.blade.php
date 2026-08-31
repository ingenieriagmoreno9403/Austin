@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-circle-nodes"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.ordenes') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Producción
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Especificaciones flange</h2>
                        <p class="text-muted mb-0">
                            Diámetro / RD → ruta (torno, torno externo o corte) —
                            {{ number_format($totalEspecificaciones) }} registradas
                        </p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-blue fs-7 mb-2" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevaFlange">
                        <i class="fa-solid fa-plus"></i> Nueva especificación
                    </button>
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

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('produccion.especificaciones.flange') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fs-8">Buscar</label>
                    <input type="text" class="form-control form-control-sm" name="buscar" value="{{ $buscar }}"
                        placeholder="SKU, nombre, diámetro…">
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-8">Producto</label>
                    <select class="form-select form-select-sm" name="producto_id">
                        <option value="">— Todos —</option>
                        @foreach ($productos as $p)
                            <option value="{{ $p->id }}" @selected((int) $productoId === (int) $p->id)>
                                {{ $p->sku }} — {{ \Illuminate\Support\Str::limit($p->nombre, 40) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-8">Estatus</label>
                    <select class="form-select form-select-sm" name="estatus">
                        <option value="ACTIVO" @selected(request('estatus', 'ACTIVO') === 'ACTIVO')>Activos</option>
                        <option value="INACTIVO" @selected(request('estatus') === 'INACTIVO')>Inactivos</option>
                        <option value="TODOS" @selected(request('estatus') === 'TODOS')>Todos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-blue btn-sm w-100" type="submit">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow p-3 bg-body rounded-5">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr class="text-muted fs-8">
                        <th>Producto</th>
                        <th>Diámetro</th>
                        <th>RD</th>
                        <th>Peso kg/pza</th>
                        <th>Ruta</th>
                        <th>Estatus</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($especificaciones as $spec)
                        @php
                            $ruta = \App\Models\ProductoFlangeEspecificacion::resolverRuta($spec->diametro_nominal, $spec->rd);
                        @endphp
                        <tr>
                            <td class="fs-8">
                                <strong>{{ $spec->producto->sku ?? '—' }}</strong>
                                <div class="text-muted">{{ \Illuminate\Support\Str::limit($spec->producto->nombre ?? '', 50) }}</div>
                            </td>
                            <td>{{ $spec->diametro_nominal }}</td>
                            <td>{{ $spec->rd !== null ? number_format((float) $spec->rd, 3) : '—' }}</td>
                            <td>{{ $spec->peso_kg_pieza !== null ? number_format((float) $spec->peso_kg_pieza, 3) : '—' }}</td>
                            <td><span class="badge bg-secondary">{{ $ruta }}</span></td>
                            <td>
                                <span class="badge {{ $spec->estatus === 'ACTIVO' ? 'bg-success' : 'bg-light text-dark border' }}">
                                    {{ $spec->estatus }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#modalEditFlange{{ $spec->id }}">
                                    Editar
                                </button>
                            </td>
                        </tr>

                        <div class="modal fade" id="modalEditFlange{{ $spec->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('produccion.especificaciones.flange.update', $spec->id) }}">
                                        @csrf
                                        <div class="modal-header border-0">
                                            <h5 class="modal-title">Editar flange #{{ $spec->id }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            @include('Produccion.partials.form_especificacion_flange', [
                                                'spec' => $spec,
                                                'productosCatalogo' => $productosCatalogo,
                                            ])
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-blue btn-sm">Guardar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Sin especificaciones flange.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $especificaciones->links() }}</div>
    </div>
</div>

<div class="modal fade" id="modalNuevaFlange" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('produccion.especificaciones.flange.store') }}">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title">Nueva especificación flange</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info border-0 fs-8">
                        4" → torno (RD 6/7/7.3 → externo). 6" → corte de tapa. El producto queda tipificado como FLANGE.
                    </div>
                    @include('Produccion.partials.form_especificacion_flange', [
                        'spec' => null,
                        'productosCatalogo' => $productosCatalogo,
                    ])
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-blue btn-sm">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
