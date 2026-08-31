@php
    $spec = $spec ?? null;
    $esEdicion = $spec !== null;
    $productoIdValor = old('producto_id', $spec->producto_id ?? '');
@endphp

<div class="row">
    <div class="col-md-8 col-12 mt-2">
        <label class="form-label">Producto (tubo) <span class="text-danger">*</span></label>
        <select class="form-select" name="producto_id" required {{ $esEdicion ? 'disabled' : '' }}>
            <option value="">— Seleccione —</option>
            @foreach ($productosTubo as $producto)
                <option value="{{ $producto->id }}" {{ (string) $productoIdValor === (string) $producto->id ? 'selected' : '' }}>
                    {{ $producto->sku }} — {{ \Illuminate\Support\Str::limit($producto->nombre, 60) }}
                </option>
            @endforeach
        </select>
        @if ($esEdicion)
            <input type="hidden" name="producto_id" value="{{ $spec->producto_id }}">
        @endif
    </div>
    <div class="col-md-4 col-12 mt-2">
        <label class="form-label">Material</label>
        <input type="text" class="form-control" name="material" maxlength="100"
            value="{{ old('material', $spec->material ?? $materialDefault) }}">
    </div>
    <div class="col-md-3 col-12 mt-2">
        <label class="form-label">Diámetro nominal <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="diametro_nominal" maxlength="50" required
            placeholder='Ej. 4" o 2 1/2"'
            value="{{ old('diametro_nominal', $spec->diametro_nominal ?? '') }}">
    </div>
    <div class="col-md-3 col-12 mt-2">
        <label class="form-label">Diám. exterior (pulg)</label>
        <input type="number" class="form-control" name="diametro_exterior_pulg" step="0.0001" min="0"
            value="{{ old('diametro_exterior_pulg', $spec->diametro_exterior_pulg ?? '') }}">
    </div>
    <div class="col-md-2 col-12 mt-2">
        <label class="form-label">PSI <span class="text-danger">*</span></label>
        <input type="number" class="form-control" name="psi" step="0.01" min="0" required
            value="{{ old('psi', $spec->psi ?? '') }}">
    </div>
    <div class="col-md-2 col-12 mt-2">
        <label class="form-label">RD</label>
        <input type="number" class="form-control" name="rd" step="0.1" min="0"
            value="{{ old('rd', $spec->rd ?? '') }}">
    </div>
    <div class="col-md-2 col-12 mt-2">
        <label class="form-label">Estatus <span class="text-danger">*</span></label>
        <select class="form-select" name="estatus" required>
            <option value="ACTIVO" {{ old('estatus', $spec->estatus ?? 'ACTIVO') === 'ACTIVO' ? 'selected' : '' }}>Activo</option>
            <option value="INACTIVO" {{ old('estatus', $spec->estatus ?? '') === 'INACTIVO' ? 'selected' : '' }}>Inactivo</option>
        </select>
    </div>
    <div class="col-md-3 col-12 mt-2">
        <label class="form-label">Espesor (pulg)</label>
        <input type="number" class="form-control" name="espesor_pulg" step="0.0001" min="0"
            value="{{ old('espesor_pulg', $spec->espesor_pulg ?? '') }}">
        <div class="form-text">Si se deja vacío y hay RD + diám. exterior, se calcula automáticamente.</div>
    </div>
    <div class="col-md-3 col-12 mt-2">
        <label class="form-label">Peso (kg/m)</label>
        <input type="number" class="form-control" name="peso_kg_m" step="0.0001" min="0"
            value="{{ old('peso_kg_m', $spec->peso_kg_m ?? '') }}">
    </div>
</div>
