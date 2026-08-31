@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-flask-vial"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.recetas') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Recetas
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Receta #{{ $receta->id }}</h2>
                        <p class="text-muted mb-0">
                            {{ $receta->producto?->sku }} — {{ $receta->producto?->nombre }}
                            @if ($receta->version)
                                · v{{ $receta->version }}
                            @endif
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
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-gear me-2"></i>Datos de la receta
            </h5>
        </div>
        <div class="card-body pt-0">
            <form action="{{ route('produccion.recetas.actualizar', $receta->id) }}" method="POST" class="modern-form">
                @csrf
                <div class="row">
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">Versión</label>
                        <input type="text" class="form-control" name="version" value="{{ old('version', $receta->version) }}" maxlength="20">
                    </div>
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">Estatus</label>
                        <select class="form-select" name="estatus" required>
                            <option value="ACTIVA" {{ old('estatus', $receta->estatus) === 'ACTIVA' ? 'selected' : '' }}>Activa</option>
                            <option value="INACTIVA" {{ old('estatus', $receta->estatus) === 'INACTIVA' ? 'selected' : '' }}>Inactiva</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-12 mt-2">
                        <label class="form-label">Observaciones</label>
                        <input type="text" class="form-control text" name="observaciones" value="{{ old('observaciones', $receta->observaciones) }}">
                    </div>
                    <div class="col-md-2 col-12 mt-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-blue w-100">
                            <i class="fa-solid fa-save"></i> Guardar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-cubes me-2"></i>Componentes (materia prima)
            </h5>
            <p class="text-muted fs-8 mb-0">Productos de catálogo que se consumen para fabricar este tubo.</p>
        </div>
        <div class="card-body pt-0">
            @forelse ($receta->detalles as $detalle)
                <div class="border rounded-4 p-3 mb-3">
                    <form action="{{ route('produccion.recetas.detalle.actualizar', [$receta->id, $detalle->id]) }}" method="POST">
                        @csrf
                        <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                            <div>
                                <strong>{{ $detalle->productoMp?->nombre ?? '—' }}</strong>
                                <div class="text-muted fs-8">{{ $detalle->productoMp?->sku }}</div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-save"></i> Guardar
                            </button>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label fs-9">Cantidad</label>
                                <input type="number" step="0.0001" min="0" class="form-control form-control-sm" name="cantidad"
                                    value="{{ old('cantidad', $detalle->cantidad) }}" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-9">Unidad</label>
                                <select class="form-select form-select-sm" name="unidad_id">
                                    <option value="">— Default producto —</option>
                                    @foreach ($unidades as $unidad)
                                        <option value="{{ $unidad->id }}" {{ (int) $detalle->unidad_id === (int) $unidad->id ? 'selected' : '' }}>
                                            {{ $unidad->nombre }} ({{ $unidad->abreviacion }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fs-9">% mezcla</label>
                                <input type="number" step="0.0001" min="0" max="100" class="form-control form-control-sm" name="porcentaje"
                                    value="{{ old('porcentaje', $detalle->porcentaje) }}">
                            </div>
                        </div>
                    </form>
                    <form action="{{ route('produccion.recetas.detalle.eliminar', [$receta->id, $detalle->id]) }}" method="POST" class="mt-2 text-end"
                        onsubmit="return confirm('¿Eliminar este componente?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="fa-solid fa-trash"></i> Eliminar
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-muted text-center py-3 mb-0">Sin componentes. Agregue materia prima abajo.</p>
            @endforelse
        </div>
    </div>

    <div class="card border-0 shadow p-3 mb-3 bg-body rounded-5">
        <div class="card-header bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-circle-plus me-2"></i>Agregar componente
            </h5>
        </div>
        <div class="card-body pt-0">
            <form action="{{ route('produccion.recetas.detalle.store', $receta->id) }}" method="POST" class="modern-form" id="form-agregar-componente">
                @csrf
                <div class="row">
                    <div class="col-md-4 col-12 mt-2">
                        @php
                            $mpOldSel = $productosMp->firstWhere('id', (int) old('producto_mp_id'));
                            $mpOldLabel = $mpOldSel ? trim(($mpOldSel->sku ?: '—') . ' — ' . $mpOldSel->nombre) : '';
                        @endphp
                        <label class="form-label">Materia prima <span class="text-danger">*</span></label>
                        <div class="mp-autocomplete position-relative">
                            <input type="hidden" id="mp_producto_id" name="producto_mp_id" value="{{ old('producto_mp_id') }}">
                            <input type="text" id="mp_buscar" class="form-control mp-buscar" autocomplete="off"
                                placeholder="Buscar por SKU o nombre..." value="{{ $mpOldLabel }}">
                            <div id="mp_sugerencias" class="list-group position-absolute w-100 mp-sugerencias d-none shadow-sm"
                                style="z-index: 1080; max-height: 240px; overflow-y: auto;"></div>
                        </div>
                    </div>
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" min="0" class="form-control" name="cantidad" value="{{ old('cantidad') }}" required>
                    </div>
                    <div class="col-md-3 col-12 mt-2">
                        <label class="form-label">Unidad</label>
                        <select class="form-select" name="unidad_id" id="mp_unidad_id">
                            <option value="">— Default producto —</option>
                            @foreach ($unidades as $unidad)
                                <option value="{{ $unidad->id }}">{{ $unidad->nombre }} ({{ $unidad->abreviacion }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 col-12 mt-2">
                        <label class="form-label">% mezcla</label>
                        <input type="number" step="0.0001" min="0" max="100" class="form-control" name="porcentaje" value="{{ old('porcentaje') }}">
                    </div>
                    <div class="col-md-1 col-12 mt-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-blue w-100">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $mpCatalogo = $productosMp->where('id', '!=', (int) $receta->producto_id)->map(function ($mp) {
        return [
            'id' => (int) $mp->id,
            'unidad' => $mp->id_unidad_medida,
            'etiqueta' => trim(($mp->sku ?: '—') . ' — ' . $mp->nombre),
        ];
    })->values();
@endphp
<script>
    window.__RECETA_MP = {!! $mpCatalogo->toJson() !!};
</script>
<script>
(function () {
    var hidden = document.getElementById('mp_producto_id');
    var input = document.getElementById('mp_buscar');
    var caja = document.getElementById('mp_sugerencias');
    var unidadSelect = document.getElementById('mp_unidad_id');
    var CATALOGO = window.__RECETA_MP || [];
    var MAX_RESULTADOS = 15;

    if (!hidden || !input || !caja) return;

    function escaparHtml(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function ocultar() {
        caja.innerHTML = '';
        caja.classList.add('d-none');
    }

    function filtrar(termino) {
        termino = termino.trim().toLowerCase();
        if (termino.length < 1) return [];
        var palabras = termino.split(/\s+/);
        return CATALOGO.filter(function (mp) {
            var texto = (mp.etiqueta || '').toLowerCase();
            return palabras.every(function (p) { return texto.indexOf(p) !== -1; });
        }).slice(0, MAX_RESULTADOS);
    }

    function mostrar(resultados) {
        if (!resultados.length) {
            caja.innerHTML = '<div class="list-group-item fs-8 text-muted">Sin coincidencias</div>';
            caja.classList.remove('d-none');
            return;
        }
        caja.innerHTML = resultados.map(function (mp) {
            return '<button type="button" class="list-group-item list-group-item-action fs-8" ' +
                'data-id="' + mp.id + '" data-unidad="' + escaparHtml(mp.unidad || '') + '" ' +
                'data-etiqueta="' + escaparHtml(mp.etiqueta) + '">' + escaparHtml(mp.etiqueta) + '</button>';
        }).join('');
        caja.classList.remove('d-none');
    }

    input.addEventListener('input', function () {
        hidden.value = '';
        mostrar(filtrar(input.value));
    });
    input.addEventListener('focus', function () {
        if (input.value.trim().length) mostrar(filtrar(input.value));
    });
    input.addEventListener('blur', function () {
        window.setTimeout(ocultar, 150);
    });

    caja.addEventListener('mousedown', function (e) {
        var btn = e.target.closest('[data-id]');
        if (!btn) return;
        e.preventDefault();
        hidden.value = btn.getAttribute('data-id');
        input.value = btn.getAttribute('data-etiqueta');
        var unidadId = btn.getAttribute('data-unidad');
        if (unidadSelect && unidadId) unidadSelect.value = unidadId;
        ocultar();
    });

    var form = document.getElementById('form-agregar-componente');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!hidden.value) {
                e.preventDefault();
                alert('Seleccione la materia prima desde el buscador.');
                input.focus();
            }
        });
    }
})();
</script>
@endsection
