<div class="col-md-{{ $cols ?? 6 }} col-12 mt-2">
    <label class="form-label">Incidencia relacionada</label>
    <select class="form-select" name="incidencia_id">
        <option value="">— Sin incidencia —</option>
        @foreach ($incidencias as $incidencia)
            <option value="{{ $incidencia->id }}"
                {{ (int) old('incidencia_id', $selected ?? '') === (int) $incidencia->id ? 'selected' : '' }}>
                #{{ $incidencia->id }} — {{ $incidencia->titulo }}
                @if ($incidencia->fecha)
                    ({{ $incidencia->fecha->format('d/m/Y') }} — {{ $incidencia->estatus_texto }})
                @else
                    ({{ $incidencia->estatus_texto }})
                @endif
            </option>
        @endforeach
    </select>
    <div class="form-text">Opcional. Seleccione una incidencia registrada de esta máquina.</div>
</div>
