@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $estatusBadgeProd = [
        'REVISION_CALIDAD' => 'bg-info text-dark',
        'PREPARAR_EMPAQUE' => 'bg-primary',
        'ENROLLANDO' => 'bg-primary',
        'A_LONGITUD' => 'bg-primary',
        'CORTAR_FLEJAR' => 'bg-primary',
        'EMPACANDO' => 'bg-primary',
        'TERMINADA' => 'badge-success-dark',
        'CANCELADA' => 'badge-danger-dark',
    ];
    $pasosEmpaque = [
        'PREPARAR_EMPAQUE' => 'Verificar si la tubería será en tramos cortos (≤ 20 m) o en rollos (> 50 m). Según el caso, acercar las camas de rodillos o el enrollador a la línea de producción.',
        'REVISION_CALIDAD' => 'Verificar si la tubería será en tramos cortos (≤ 20 m) o en rollos (> 50 m). Según el caso, acercar las camas de rodillos o el enrollador a la línea de producción.',
        'ENROLLANDO' => 'Esperar que la tubería recorra determinada distancia, tomar la punta y sujetarla al enrollador; encender el enrollador y comenzar a enrollar la tubería.',
        'A_LONGITUD' => 'Encender el contador de metros para observar cuántos metros se han fabricado. Si es tramo, medir con flexómetro la cantidad de metros fabricados.',
        'CORTAR_FLEJAR' => 'Cortar la tubería al cumplir la longitud (guillotina o cortador mecánico). Tomar fleje y flejadora y flejar; según la requisición será paquete de tramos o rollo individual.',
        'TERMINADA' => 'Producto ya almacenado en la ubicación de almacén seleccionada.',
    ];
    $instruccionActual = $pasosEmpaque[$orden->estatus] ?? null;
@endphp

<style>
    .empaque-detalle-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .empaque-detalle-summary .summary-item {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        border-radius: 12px;
        padding: 0.65rem 0.85rem;
    }
    .empaque-detalle-summary .summary-item .label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0.15rem;
    }
    .empaque-detalle-summary .summary-item .value {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--table-text, #111827);
    }
    .empaque-detalle-form {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        border-radius: 14px;
        padding: 0.85rem 1rem;
    }
    .empaque-detalle-form .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0.2rem;
    }
    .empaque-detalle-card {
        border: 1px solid var(--table-border, #e5e7eb);
        border-radius: 16px;
        background: #fff;
        padding: 1rem 1.15rem;
        margin-bottom: 1rem;
    }
    .empaque-detalle-card .card-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e3a5f;
        margin-bottom: 0.25rem;
    }
    @media (max-width: 992px) {
        .empaque-detalle-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 576px) {
        .empaque-detalle-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('produccion.empaque') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Empaque
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Empaque · {{ $orden->folio }}</h2>
                        <p class="text-muted mb-0">
                            {{ $orden->pedido?->folio ?? 'Sin pedido' }}
                            · {{ $orden->pedido?->cliente?->nombre ?? '—' }}
                        </p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    <a class="btn btn-baseColor-light" href="{{ route('produccion.tipos_empaque') }}">
                        <i class="fa-solid fa-boxes-packing"></i> Tipos
                    </a>
                    <a class="btn btn-baseColor-light" href="{{ route('produccion.ordenes.detalle', $orden->id) }}">
                        <i class="fa-solid fa-clipboard-check"></i> Detalle producción
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
    @if (session('warning'))
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
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

    <div class="empaque-detalle-card">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h5 class="card-title mb-1">
                    <i class="fa-solid fa-box-open me-2"></i>Empaque y almacenamiento
                </h5>
                <p class="text-muted fs-8 mb-0">
                    P-FT-PDN-0001, punto D — ¿Rollo (&gt; 50 m) o tramos (≤ 20 m)? → enrollar → longitud → cortar/flejar → almacenar
                </p>
            </div>
        </div>

        @if ($instruccionActual)
            <div class="alert alert-primary border-0 fs-8 d-flex gap-2 align-items-start">
                <i class="fa-solid fa-person-digging mt-1"></i>
                <div>
                    <div class="text-uppercase fw-semibold" style="letter-spacing:.3px;">
                        Responsable: {{ ($responsablesProceso[$orden->estatus] ?? null) ?: 'Operador de Empaque' }}
                    </div>
                    <div>{{ $instruccionActual }}</div>
                </div>
            </div>
        @endif

        <div class="empaque-detalle-summary">
            <div class="summary-item">
                <span class="label">Tipo empaque</span>
                <span class="value">{{ \App\Models\TipoEmpaque::etiqueta($orden->tipo_empaque) }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Longitud objetivo</span>
                <span class="value">{{ $orden->longitud_objetivo_m !== null ? number_format((float) $orden->longitud_objetivo_m, 2) . ' m' : '—' }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Nave / ubicación destino</span>
                <span class="value">{{ $orden->etiquetaNaveDestino() }}</span>
            </div>
            <div class="summary-item">
                <span class="label">Estatus</span>
                <span class="badge {{ $estatusBadgeProd[$orden->estatus] ?? 'badge-secondary' }} fs-9">
                    {{ $orden->estatus_texto }}
                </span>
            </div>
        </div>

        @if ($editable && $orden->estatus !== 'TERMINADA')
            <form method="POST" action="{{ route('produccion.empaque.avanzar', $orden->id) }}" class="empaque-detalle-form">
                @csrf
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-3">
                        <label class="form-label" for="selectTipoEmpaque">Tipo de empaque</label>
                        <div class="input-group input-group-sm">
                            <select name="tipo_empaque" id="selectTipoEmpaque" class="form-select form-select-sm">
                                <option value="">— Seleccione —</option>
                                @foreach (($tiposEmpaque ?? collect()) as $tipo)
                                    <option value="{{ $tipo->codigo }}"
                                        {{ $orden->tipo_empaque === $tipo->codigo ? 'selected' : '' }}
                                        data-enrollar="{{ $tipo->requiere_enrollar ? '1' : '0' }}">
                                        {{ $tipo->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-secondary" title="Nuevo tipo de empaque"
                                data-bs-toggle="modal" data-bs-target="#modalNuevoTipoEmpaque">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <div class="form-text fs-9">
                            <a href="{{ route('produccion.tipos_empaque') }}">Administrar catálogo</a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Longitud requerida (m)</label>
                        <input type="number" step="0.001" min="0.001" class="form-control form-control-sm"
                            name="longitud_objetivo_m"
                            value="{{ old('longitud_objetivo_m', $orden->longitud_objetivo_m) }}">
                        <div class="form-text fs-9">Contador de metros (rollo) o flexómetro (tramo)</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="selectUbicacionDestinoOp">Ubicación almacén (nave)</label>
                        <div class="input-group input-group-sm">
                            <select name="ubicacion_destino_id" id="selectUbicacionDestinoOp" class="form-select form-select-sm">
                                <option value="">— Seleccione —</option>
                                @foreach ($ubicaciones as $ubicacion)
                                    <option value="{{ $ubicacion->id_ubicacion }}"
                                        {{ (int) ($orden->ubicacion_destino_id ?? 0) === (int) $ubicacion->id_ubicacion ? 'selected' : '' }}>
                                        {{ $ubicacion->folio_interno }} — {{ $ubicacion->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-secondary" title="Nueva ubicación"
                                data-bs-toggle="modal" data-bs-target="#modalNuevaUbicacionOp">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nota</label>
                        <input type="text" class="form-control form-control-sm" name="observaciones" maxlength="1000"
                            placeholder="Opcional">
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if (in_array($orden->estatus, ['REVISION_CALIDAD', 'PREPARAR_EMPAQUE', 'EMPACANDO'], true))
                        <button type="submit" name="accion" value="preparar" class="btn btn-baseColor btn-sm">
                            <i class="fa-solid fa-clipboard-list me-1"></i> Preparar empaque (rollo/tramos)
                        </button>
                    @endif
                    @if ($orden->estatus === 'ENROLLANDO')
                        <button type="submit" name="accion" value="enrollar" class="btn btn-baseColor btn-sm">
                            <i class="fa-solid fa-rotate me-1"></i> Sujetar tubería al enrollador
                        </button>
                    @endif
                    @if ($orden->estatus === 'A_LONGITUD')
                        <button type="submit" name="accion" value="longitud" class="btn btn-baseColor btn-sm">
                            <i class="fa-solid fa-ruler-horizontal me-1"></i> Longitud requerida alcanzada
                        </button>
                    @endif
                    @if ($orden->estatus === 'CORTAR_FLEJAR')
                        <button type="submit" name="accion" value="cortar_flejar" class="btn btn-baseColor-light btn-sm">
                            <i class="fa-solid fa-scissors me-1"></i> Confirmar corte y flejado
                        </button>
                        <button type="submit" name="accion" value="terminar" class="btn btn-success btn-sm">
                            <i class="fa-solid fa-warehouse me-1"></i> Almacenar en ubicación / Terminar
                        </button>
                    @endif
                </div>
            </form>
        @elseif ($orden->estatus === 'TERMINADA')
            <div class="alert alert-success border-0 fs-8 mb-0">
                <i class="fa-solid fa-circle-check me-1"></i>
                Tubería almacenada en {{ $orden->etiquetaNaveDestino() }}. Orden terminada.
                <a class="ms-2" href="{{ route('almacen.cargas') }}">Ir a Cargas (Almacén)</a>
            </div>
        @else
            <div class="alert alert-warning border-0 fs-8 mb-0">
                Esta orden aún no está en etapa de empaque. Complete calidad en el detalle de producción.
                <a href="{{ route('produccion.ordenes.detalle', $orden->id) }}">Abrir producción</a>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="modalNuevoTipoEmpaque" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-marino fw-bold">
                    <i class="fa-solid fa-boxes-packing me-2"></i>Nuevo tipo de empaque
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label fs-8">Nombre <span class="text-danger">*</span></label>
                    <input type="text" id="nuevoTipoEmpaqueNombre" class="form-control form-control-sm" maxlength="120"
                        placeholder="Ej. Rollo especial">
                </div>
                <div class="mb-2">
                    <label class="form-label fs-8">Código</label>
                    <input type="text" id="nuevoTipoEmpaqueCodigo" class="form-control form-control-sm" maxlength="30"
                        placeholder="Opcional (se genera del nombre)">
                </div>
                <div class="mb-2">
                    <label class="form-label fs-8">Descripción</label>
                    <input type="text" id="nuevoTipoEmpaqueDesc" class="form-control form-control-sm" maxlength="255">
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="nuevoTipoEmpaqueEnrollar" value="1">
                    <label class="form-check-label fs-8" for="nuevoTipoEmpaqueEnrollar">Requiere enrollador</label>
                </div>
                <div id="nuevoTipoEmpaqueError" class="alert alert-danger border-0 fs-8 d-none py-2"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-baseColor btn-sm" id="btnGuardarNuevoTipoEmpaque">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar y seleccionar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaUbicacionOp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-marino fw-bold">
                    <i class="fa-solid fa-location-dot me-2"></i>Nueva ubicación de almacén
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label fs-8">Almacén <span class="text-danger">*</span></label>
                    <select id="nuevaUbiAlmacen" class="form-select form-select-sm">
                        <option value="">— Seleccione —</option>
                        @foreach ($almacenes as $almacen)
                            <option value="{{ $almacen->id }}">{{ $almacen->folio_interno }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label fs-8">Folio / nombre (ej. NAVE 2) <span class="text-danger">*</span></label>
                    <input type="text" id="nuevaUbiFolio" class="form-control form-control-sm" maxlength="100">
                </div>
                <div class="mb-2">
                    <label class="form-label fs-8">Descripción <span class="text-danger">*</span></label>
                    <input type="text" id="nuevaUbiDesc" class="form-control form-control-sm" maxlength="100">
                </div>
                <div class="mb-2">
                    <label class="form-label fs-8">Tipo <span class="text-danger">*</span></label>
                    <select id="nuevaUbiTipo" class="form-select form-select-sm">
                        <option value="">— Seleccione —</option>
                        @foreach ($tiposUbicacion as $tipo)
                            <option value="{{ $tipo->id }}" {{ (int) $tipo->id === 1 ? 'selected' : '' }}>{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="nuevaUbiError" class="alert alert-danger border-0 fs-8 d-none py-2"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-baseColor btn-sm" id="btnGuardarNuevaUbicacionOp">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Guardar y seleccionar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const btn = document.getElementById('btnGuardarNuevaUbicacionOp');
    const selectDestino = document.getElementById('selectUbicacionDestinoOp');
    const errBox = document.getElementById('nuevaUbiError');
    if (!btn || !selectDestino) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value;

    btn.addEventListener('click', function () {
        const payload = {
            id_almacen: document.getElementById('nuevaUbiAlmacen')?.value || '',
            folio_interno: (document.getElementById('nuevaUbiFolio')?.value || '').trim(),
            descripcion: (document.getElementById('nuevaUbiDesc')?.value || '').trim(),
            id_tipo_ubicacion: document.getElementById('nuevaUbiTipo')?.value || '',
        };
        if (errBox) { errBox.classList.add('d-none'); errBox.textContent = ''; }
        if (!payload.id_almacen || !payload.folio_interno || !payload.descripcion || !payload.id_tipo_ubicacion) {
            if (errBox) { errBox.textContent = 'Complete almacén, folio, descripción y tipo.'; errBox.classList.remove('d-none'); }
            return;
        }
        btn.disabled = true;
        fetch(@json(route('produccion.empaque.ubicacion.store', $orden->id)), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
                if (!res.ok || !res.data.ok) {
                    throw new Error(res.data.message || (res.data.errors ? Object.values(res.data.errors).flat().join(' ') : 'No se pudo crear'));
                }
                const u = res.data.ubicacion;
                const opt = document.createElement('option');
                opt.value = String(u.id);
                opt.textContent = u.etiqueta || (u.folio_interno + ' — ' + u.descripcion);
                opt.selected = true;
                selectDestino.appendChild(opt);
                selectDestino.value = String(u.id);
                const modalEl = document.getElementById('modalNuevaUbicacionOp');
                if (modalEl && window.bootstrap) {
                    (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).hide();
                }
            })
            .catch(function (e) {
                if (errBox) { errBox.textContent = e.message || 'Error'; errBox.classList.remove('d-none'); }
            })
            .finally(function () { btn.disabled = false; });
    });
})();

(function () {
    const btn = document.getElementById('btnGuardarNuevoTipoEmpaque');
    const selectTipo = document.getElementById('selectTipoEmpaque');
    const errBox = document.getElementById('nuevoTipoEmpaqueError');
    if (!btn || !selectTipo) return;

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value;

    btn.addEventListener('click', function () {
        const payload = {
            nombre: (document.getElementById('nuevoTipoEmpaqueNombre')?.value || '').trim(),
            codigo: (document.getElementById('nuevoTipoEmpaqueCodigo')?.value || '').trim(),
            descripcion: (document.getElementById('nuevoTipoEmpaqueDesc')?.value || '').trim(),
            requiere_enrollar: document.getElementById('nuevoTipoEmpaqueEnrollar')?.checked ? 1 : 0,
        };
        if (errBox) { errBox.classList.add('d-none'); errBox.textContent = ''; }
        if (!payload.nombre) {
            if (errBox) { errBox.textContent = 'Capture el nombre del tipo de empaque.'; errBox.classList.remove('d-none'); }
            return;
        }
        btn.disabled = true;
        fetch(@json(route('produccion.tipos_empaque.store')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
                if (!res.ok || !res.data.success) {
                    throw new Error(res.data.message || (res.data.errors ? Object.values(res.data.errors).flat().join(' ') : 'No se pudo crear'));
                }
                const t = res.data.tipo;
                const opt = document.createElement('option');
                opt.value = t.codigo;
                opt.textContent = t.nombre;
                opt.dataset.enrollar = t.requiere_enrollar ? '1' : '0';
                opt.selected = true;
                selectTipo.appendChild(opt);
                selectTipo.value = t.codigo;
                const modalEl = document.getElementById('modalNuevoTipoEmpaque');
                if (modalEl && window.bootstrap) {
                    (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).hide();
                }
                document.getElementById('nuevoTipoEmpaqueNombre').value = '';
                document.getElementById('nuevoTipoEmpaqueCodigo').value = '';
                document.getElementById('nuevoTipoEmpaqueDesc').value = '';
                document.getElementById('nuevoTipoEmpaqueEnrollar').checked = false;
            })
            .catch(function (e) {
                if (errBox) { errBox.textContent = e.message || 'Error'; errBox.classList.remove('d-none'); }
            })
            .finally(function () { btn.disabled = false; });
    });
})();
</script>
@endsection
