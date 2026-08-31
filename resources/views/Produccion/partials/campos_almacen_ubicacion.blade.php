@php
    $suffix = $suffix ?? '';
    $idAlmacenSeleccionado = old('id_almacen', $idAlmacen ?? '');
    $idUbicacionSeleccionada = old('id_ubicacion', $idUbicacion ?? '');
    $selectAlmacenId = 'id_almacen' . $suffix;
    $selectUbicacionId = 'id_ubicacion' . $suffix;
@endphp

<div class="row campos-almacen-ubicacion" data-suffix="{{ $suffix }}">
    <div class="col-md-6 col-12 mt-2">
        <div class="form-outline">
            <label class="form-label" for="{{ $selectAlmacenId }}">Almacén de refacciones <span class="text-danger">*</span></label>
            <select class="form-select select-almacen-maquina" id="{{ $selectAlmacenId }}" name="id_almacen" required>
                <option value="">Seleccione un almacén</option>
                @foreach ($almacenes as $almacen)
                    <option value="{{ $almacen->id }}" {{ (string) $idAlmacenSeleccionado === (string) $almacen->id ? 'selected' : '' }}>
                        {{ $almacen->folio_interno }}
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback">Seleccione el almacén donde se consultarán las refacciones.</div>
        </div>
    </div>
    <div class="col-md-6 col-12 mt-2">
        <div class="form-outline">
            <label class="form-label" for="{{ $selectUbicacionId }}">Ubicación en almacén <span class="text-danger">*</span></label>
            <select class="form-select select-ubicacion-maquina" id="{{ $selectUbicacionId }}" name="id_ubicacion" required>
                <option value="">Seleccione una ubicación</option>
                @foreach ($ubicaciones as $ubicacion)
                    <option value="{{ $ubicacion->id_ubicacion }}"
                        data-almacen="{{ $ubicacion->id_almacen }}"
                        {{ (string) $idUbicacionSeleccionada === (string) $ubicacion->id_ubicacion ? 'selected' : '' }}>
                        {{ $ubicacion->folio_interno }} — {{ $ubicacion->descripcion }}
                    </option>
                @endforeach
            </select>
            <div class="invalid-feedback">Seleccione la ubicación del almacén para las refacciones.</div>
            <div class="form-text">Aquí se consultará si hay existencia de refacciones de esta máquina.</div>
        </div>
    </div>
</div>
