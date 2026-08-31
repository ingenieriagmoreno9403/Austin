@php
    $pasos = [
        \App\Models\RutaCarga::ESTATUS_ARMADA => 'Armada',
        \App\Models\RutaCarga::ESTATUS_MATERIAL_UBICADO => 'Ubicar material',
        \App\Models\RutaCarga::ESTATUS_CARGANDO => 'Chofer / carga',
        \App\Models\RutaCarga::ESTATUS_CHECKLIST => 'Checklist',
        \App\Models\RutaCarga::ESTATUS_EVIDENCIA => 'Evidencia',
        \App\Models\RutaCarga::ESTATUS_REMISION => 'Remisiones',
        \App\Models\RutaCarga::ESTATUS_CERRADA => 'Salida',
    ];
    $idxActual = array_search($rutaSeleccionada->estatus, array_keys($pasos), true);
    if ($idxActual === false) {
        $idxActual = 0;
    }
    $cerrada = $rutaSeleccionada->estaCerrada();
@endphp

<div class="panel-hint mb-3 mt-3">
    <div class="fw-semibold text-marino mb-2">
        <i class="fa-solid fa-diagram-project me-1"></i> Operación de carga
    </div>
    <div class="d-flex flex-wrap gap-1 mb-2">
        @foreach ($pasos as $est => $label)
            @php $i = $loop->index; @endphp
            <span class="badge {{ $i < $idxActual ? 'bg-success' : ($i === $idxActual ? 'bg-dark' : 'bg-secondary') }}">
                {{ $loop->iteration }}. {{ $label }}
            </span>
        @endforeach
    </div>
    <div class="fs-8 text-muted">
        Responsable operativo: <strong>{{ $responsableAlmacen ?? 'AUXILIAR DE ALMACEN' }}</strong>
        · Evidencia: <strong>{{ $responsableEvidencia ?? 'SUPERVISOR DE ALMACEN' }}</strong>
    </div>
</div>

{{-- 1) Ubicar material --}}
@if ($rutaSeleccionada->estatus === \App\Models\RutaCarga::ESTATUS_ARMADA && $rutaSeleccionada->cargas->isNotEmpty())
    <div class="panel-hint mb-3">
        <div class="fw-semibold text-marino mb-1">Paso · Ubicar material</div>
        <div class="fs-8 text-muted mb-2">Confirme que el material de las órdenes ya está localizado en nave/almacén.</div>
        <form method="POST" action="{{ route('almacen.cargas.ubicar', $rutaSeleccionada->id) }}">
            @csrf
            <button type="submit" class="btn btn-baseColor btn-sm">
                <i class="fa-solid fa-box-open"></i> Material ubicado
            </button>
        </form>
    </div>
@endif

{{-- 2) Llegada chofer --}}
@if (in_array($rutaSeleccionada->estatus, [\App\Models\RutaCarga::ESTATUS_ARMADA, \App\Models\RutaCarga::ESTATUS_MATERIAL_UBICADO], true) && $rutaSeleccionada->cargas->isNotEmpty())
    <div class="panel-hint mb-3">
        <div class="fw-semibold text-marino mb-1">Paso · Llega el chofer y procede a cargar</div>
        <form method="POST" action="{{ route('almacen.cargas.iniciar', $rutaSeleccionada->id) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label fs-8 mb-0">Camión</label>
                <select name="camion_id" class="form-select form-select-sm">
                    <option value="">— Seleccione —</option>
                    @foreach (($camiones ?? collect()) as $camion)
                        <option value="{{ $camion->id }}" {{ (int) ($rutaSeleccionada->camion_id ?? 0) === (int) $camion->id ? 'selected' : '' }}>
                            {{ $camion->etiqueta }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fs-8 mb-0">Chofer</label>
                <select name="chofer_id" class="form-select form-select-sm" required>
                    <option value="">— Seleccione —</option>
                    @foreach (($choferes ?? collect()) as $chofer)
                        <option value="{{ $chofer->id }}" {{ (int) ($rutaSeleccionada->chofer_id ?? 0) === (int) $chofer->id ? 'selected' : '' }}>
                            {{ $chofer->etiqueta }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-0">Tipo (si captura manual)</label>
                <select name="chofer_tipo" class="form-select form-select-sm">
                    <option value="">—</option>
                    <option value="INTERNO" {{ $rutaSeleccionada->chofer_tipo === 'INTERNO' ? 'selected' : '' }}>Interno</option>
                    <option value="EXTERNO" {{ $rutaSeleccionada->chofer_tipo === 'EXTERNO' ? 'selected' : '' }}>Externo</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-baseColor btn-sm w-100">Registrar llegada</button>
            </div>
        </form>
        <div class="form-text fs-9 mt-1">Si el chofer/camión no aparece, créelos con el botón + al armar la ruta.</div>
    </div>
@endif

{{-- 3) Checklist --}}
@if ($rutaSeleccionada->estatus === \App\Models\RutaCarga::ESTATUS_CARGANDO)
    <div class="panel-hint mb-3">
        <div class="fw-semibold text-marino mb-1">Paso · Checklist de seguridad</div>
        <form method="POST" action="{{ route('almacen.cargas.checklist', $rutaSeleccionada->id) }}" id="formChecklistCarga">
            @csrf
            <div class="mb-2">
                <label class="form-label fs-8 mb-0">Tipo de chofer</label>
                <select name="chofer_tipo" id="choferTipoChecklist" class="form-select form-select-sm" required style="max-width:220px">
                    <option value="INTERNO" {{ $rutaSeleccionada->chofer_tipo === 'INTERNO' ? 'selected' : '' }}>Interno</option>
                    <option value="EXTERNO" {{ $rutaSeleccionada->chofer_tipo === 'EXTERNO' ? 'selected' : '' }}>Externo</option>
                </select>
            </div>

            <div id="checklistExterno" class="{{ $rutaSeleccionada->chofer_tipo === 'EXTERNO' ? '' : 'd-none' }}">
                <div class="fs-8 text-muted mb-1">Chofer externo: debe contar con:</div>
                @foreach ([
                    'check_seguro_vehiculo' => 'Seguro de vehículo',
                    'check_seguro_imss' => 'Seguro IMSS',
                    'check_botas' => 'Botas',
                    'check_casco' => 'Casco',
                    'check_chaleco' => 'Chaleco',
                ] as $campo => $label)
                    <div class="form-check fs-8">
                        <input class="form-check-input" type="checkbox" name="{{ $campo }}" value="1" id="{{ $campo }}"
                            {{ $rutaSeleccionada->$campo ? 'checked' : '' }}>
                        <label class="form-check-label" for="{{ $campo }}">{{ $label }}</label>
                    </div>
                @endforeach
            </div>

            <div id="checklistInterno" class="{{ $rutaSeleccionada->chofer_tipo !== 'EXTERNO' ? '' : 'd-none' }}">
                <div class="fs-8 text-muted mb-1">Chofer interno: revise que porte su EPP.</div>
                <div class="form-check fs-8">
                    <input class="form-check-input" type="checkbox" name="check_epp" value="1" id="check_epp"
                        {{ $rutaSeleccionada->check_epp ? 'checked' : '' }}>
                    <label class="form-check-label" for="check_epp">EPP completo</label>
                </div>
            </div>

            <button type="submit" class="btn btn-baseColor btn-sm mt-2">
                <i class="fa-solid fa-clipboard-check"></i> Checklist OK
            </button>
        </form>
    </div>
    <script>
        (function () {
            var sel = document.getElementById('choferTipoChecklist');
            var ext = document.getElementById('checklistExterno');
            var inn = document.getElementById('checklistInterno');
            if (!sel) return;
            function sync() {
                var externo = sel.value === 'EXTERNO';
                ext.classList.toggle('d-none', !externo);
                inn.classList.toggle('d-none', externo);
            }
            sel.addEventListener('change', sync);
            sync();
        })();
    </script>
@endif

{{-- 4) Evidencia --}}
@if (in_array($rutaSeleccionada->estatus, [\App\Models\RutaCarga::ESTATUS_CHECKLIST, \App\Models\RutaCarga::ESTATUS_EVIDENCIA], true))
    <div class="panel-hint mb-3">
        <div class="fw-semibold text-marino mb-1">Paso · Evidencia de la carga</div>
        <div class="fs-8 text-muted mb-2">
            Suba fotos/PDF de la carga (responsable: {{ $responsableEvidencia ?? 'SUPERVISOR DE ALMACEN' }}).
        </div>
        <form method="POST" action="{{ route('almacen.cargas.evidencia', $rutaSeleccionada->id) }}" enctype="multipart/form-data" class="mb-2">
            @csrf
            <input type="file" name="evidencias[]" class="form-control form-control-sm mb-2" accept=".jpg,.jpeg,.png,.webp,.pdf" multiple required>
            <button type="submit" class="btn btn-baseColor btn-sm">
                <i class="fa-solid fa-camera"></i> Subir evidencia
            </button>
        </form>
        @if ($rutaSeleccionada->evidencias->isNotEmpty())
            <ul class="fs-8 mb-0">
                @foreach ($rutaSeleccionada->evidencias as $ev)
                    <li>
                        <a href="{{ asset('storage/' . $ev->archivo_path) }}" target="_blank" rel="noopener">
                            {{ $ev->archivo_nombre ?: basename($ev->archivo_path) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

{{-- 5) Remisiones --}}
@if (in_array($rutaSeleccionada->estatus, [\App\Models\RutaCarga::ESTATUS_EVIDENCIA, \App\Models\RutaCarga::ESTATUS_REMISION], true))
    <div class="panel-hint mb-3">
        <div class="fw-semibold text-marino mb-1">Paso · Chofer firma remisiones</div>
        <div class="fs-8 text-muted mb-2">Marque cada pedido/carga cuando el chofer firme la remisión (puede adjuntar escaneo).</div>
        @foreach ($rutaSeleccionada->cargas->sortBy('orden_entrega') as $carga)
            <div class="d-flex flex-wrap align-items-center gap-2 border-bottom py-2 fs-8">
                <div class="flex-grow-1">
                    <strong>{{ $carga->folio }}</strong>
                    · {{ $carga->pedido->folio ?? ('#' . $carga->pedido_id) }}
                    · {{ $carga->pedido->cliente->nombre ?? '—' }}
                    @if ($carga->remision_firmada)
                        <span class="badge bg-success">Firmada</span>
                        @if ($carga->remision_archivo)
                            <a href="{{ asset('storage/' . $carga->remision_archivo) }}" target="_blank" class="ms-1">Ver</a>
                        @endif
                    @else
                        <span class="badge bg-warning text-dark">Pendiente</span>
                    @endif
                </div>
                @if (!$carga->remision_firmada)
                    <form method="POST" action="{{ route('almacen.cargas.remision', [$rutaSeleccionada->id, $carga->id]) }}" enctype="multipart/form-data" class="d-flex gap-1 align-items-center">
                        @csrf
                        <input type="file" name="remision_archivo" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp,.pdf" style="max-width:180px">
                        <button type="submit" class="btn btn-baseColor-light btn-sm">Firmar</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
@endif

{{-- 6) Salida --}}
@if ($rutaSeleccionada->estatus === \App\Models\RutaCarga::ESTATUS_REMISION && $rutaSeleccionada->remisionesCompletas())
    <div class="panel-hint mb-3 border-success">
        <div class="fw-semibold text-marino mb-1">Paso · Dar salida al chofer</div>
        <div class="fs-8 text-muted mb-2">Cierra la ruta y marca los pedidos como DESPACHADO.</div>
        <form method="POST" action="{{ route('almacen.cargas.salida', $rutaSeleccionada->id) }}" onsubmit="return confirm('¿Confirmar salida del chofer y cierre de la ruta?');">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="fa-solid fa-door-open"></i> Dar salida
            </button>
        </form>
    </div>
@endif

@if ($cerrada)
    <div class="alert alert-success border-0 fs-8">
        <i class="fa-solid fa-circle-check me-1"></i>
        Ruta cerrada.
        @if ($rutaSeleccionada->salida_at)
            Salida: {{ $rutaSeleccionada->salida_at->format('d/m/Y H:i') }}.
        @endif
    </div>
@endif
