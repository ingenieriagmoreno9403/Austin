<div class="row">
    <div class="col-md-6 col-12 mb-2">
        <div class="form-outline">
            <label class="form-label" for="form8Example4">Almacenes dispobibles</label>
            <select name="id_almacen" id="id_almacen" wire:model="almacenes" class="form-select">
                <option value="0">...</option>
                @foreach($almacen as $ltalm)
                <option value="{{$ltalm->id}}">{{$ltalm->folio_interno}}</option>
                @endforeach
            </select>
            
        </div>
    </div>

    <div class="col-md-6 col-12 mb-2">
        <div class="form-outline">
            <label class="form-label" for="form8Example4">Ubicaciones disponibles</label>
            <select name="id_ubicacion" id="id_ubicacion" wire:model="ubicaciones" class="form-select">
                @foreach($ubicacion as $ltub)
                <option value="{{$ltub->id}}">Ubicacion: {{$ltub->folio_interno}} Espacio: {{$ltub->espacio}} Nivel: {{$ltub->nivel}} Ubicacion: {{$ltub->ubicacion}}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
