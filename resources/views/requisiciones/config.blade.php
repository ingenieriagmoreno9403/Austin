@extends('layouts.app')

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">
<style>
    .req-wrap { max-width: 1100px; margin: 0 auto; }
    .req-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1rem; margin-bottom:.9rem; }
    .req-card h3 { font-size:.95rem; font-weight:800; margin:0 0 .75rem; }
</style>

<div class="container-fluid py-3 req-wrap">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <div class="text-muted" style="font-size:.78rem">
                <a href="{{ route('requisiciones.index') }}">Requisiciones</a> / Rutas de autorización
            </div>
            <h1 class="h4 mb-0 fw-bold">Configuración de rutas</h1>
        </div>
        <a href="{{ route('requisiciones.index') }}" class="btn btn-sm btn-outline-secondary">Lista</a>
    </div>

    @if(session('success')) <div class="alert alert-success py-2">{{ session('success') }}</div> @endif

    <div class="req-card">
        <h3>Nueva ruta</h3>
        <form method="post" action="{{ route('requisiciones.config.rutas.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" required placeholder="Ej. Ruta Mantenimiento">
            </div>
            <div class="col-md-3">
                <label class="form-label">Departamento (opcional)</label>
                <input type="text" name="departamento" class="form-control" placeholder="Vacío = genérica">
            </div>
            <div class="col-md-2">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="es_default" value="1" id="es_default">
                    <label class="form-check-label" for="es_default">Default</label>
                </div>
            </div>
            <div class="col-md-3">
                <button class="btn btn-dark w-100">Crear ruta</button>
            </div>
        </form>
    </div>

    @foreach($rutas as $ruta)
        <div class="req-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h3 class="mb-0">{{ $ruta->nombre }}
                    @if($ruta->es_default)<span class="badge bg-dark ms-1">Default</span>@endif
                    @if($ruta->departamento)<span class="badge bg-secondary ms-1">{{ $ruta->departamento }}</span>@endif
                </h3>
                <span class="text-muted" style="font-size:.8rem">{{ $ruta->activo ? 'Activa' : 'Inactiva' }}</span>
            </div>

            @foreach($ruta->niveles as $nivel)
                <form method="post" action="{{ route('requisiciones.config.niveles.update', $nivel->id) }}" class="border rounded-3 p-2 mb-2">
                    @csrf
                    @method('PUT')
                    <div class="row g-2 align-items-end">
                        <div class="col-md-1"><strong>{{ $nivel->orden }}</strong></div>
                        <div class="col-md-3">
                            <label class="form-label">Nivel</label>
                            <input type="text" name="nombre_nivel" class="form-control form-control-sm" value="{{ $nivel->nombre_nivel }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Responsable</label>
                            <select name="responsable_user_id" class="form-select form-select-sm">
                                <option value="">— Sin asignar —</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" @selected((int)$nivel->responsable_user_id === (int)$u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Suplente</label>
                            <select name="suplente_user_id" class="form-select form-select-sm">
                                <option value="">—</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="obligatorio" value="1" id="ob-{{ $nivel->id }}" @checked($nivel->obligatorio)>
                                <label class="form-check-label" for="ob-{{ $nivel->id }}">Oblig.</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-outline-dark w-100">Guardar</button>
                        </div>
                    </div>
                    @if($nivel->suplentes->count())
                        <div class="mt-1 text-muted" style="font-size:.75rem">
                            Suplentes: {{ $nivel->suplentes->map(function ($s) { return optional($s->usuario)->name; })->filter()->implode(', ') }}
                        </div>
                    @endif
                </form>
            @endforeach
        </div>
    @endforeach
</div>
@endsection
