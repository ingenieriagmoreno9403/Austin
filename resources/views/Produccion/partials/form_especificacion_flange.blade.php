@php
    $spec = $spec ?? null;
@endphp
<div class="mb-2">
    <label class="form-label fs-8">Producto <span class="text-danger">*</span></label>
    <select name="producto_id" class="form-select form-select-sm" required>
        <option value="">— Seleccione —</option>
        @foreach ($productosCatalogo as $p)
            <option value="{{ $p->id }}" @selected((int) old('producto_id', $spec->producto_id ?? 0) === (int) $p->id)>
                {{ $p->sku }} — {{ \Illuminate\Support\Str::limit($p->nombre, 55) }}
            </option>
        @endforeach
    </select>
</div>
<div class="row g-2">
    <div class="col-md-4">
        <label class="form-label fs-8">Diámetro <span class="text-danger">*</span></label>
        <input type="text" name="diametro_nominal" class="form-control form-control-sm" required maxlength="50"
            placeholder='4" / 6"'
            value="{{ old('diametro_nominal', $spec->diametro_nominal ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fs-8">RD</label>
        <input type="number" step="0.001" min="0" name="rd" class="form-control form-control-sm"
            value="{{ old('rd', $spec->rd ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fs-8">Peso kg/pza</label>
        <input type="number" step="0.001" min="0" name="peso_kg_pieza" class="form-control form-control-sm"
            value="{{ old('peso_kg_pieza', $spec->peso_kg_pieza ?? '') }}">
    </div>
</div>
<div class="mb-2 mt-2">
    <label class="form-label fs-8">Descripción</label>
    <input type="text" name="descripcion" class="form-control form-control-sm" maxlength="255"
        value="{{ old('descripcion', $spec->descripcion ?? '') }}">
</div>
<div class="mb-0">
    <label class="form-label fs-8">Estatus</label>
    <select name="estatus" class="form-select form-select-sm" required>
        <option value="ACTIVO" @selected(old('estatus', $spec->estatus ?? 'ACTIVO') === 'ACTIVO')>ACTIVO</option>
        <option value="INACTIVO" @selected(old('estatus', $spec->estatus ?? '') === 'INACTIVO')>INACTIVO</option>
    </select>
</div>
