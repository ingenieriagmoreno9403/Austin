
<div>
    <div>
        <label class="form-label" for="form8Example4">Sucursal de Entrega</label>
        <select  class="form-select" wire:model="sucursales" name="sucursal" required>
            @foreach($selectsucursales as $suc)
                <option value="{{$suc->id}}">{{$suc->nombre}}</option>
            @endforeach
        </select>
        <div class="valid-feedback">¡Se ve bien! </div>
        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
    </div>
   
    @if(!is_null($selectcordinadores))
    <div class="mt-2">
        <label class="form-label" for="form8Example4">Coordinador encargado</label>
        <select  class="form-select" wire:model="cordinadores" name="cordinador" required> 
            <option value="">Selecciona...</option>
            @foreach($selectcordinadores as $cordi)
            <option value="{{$cordi->idem}}">{{$cordi->primer_nombre}} {{$cordi->segundo_nombre}} {{$cordi->apellido_paterno}} {{$cordi->apellido_materno}}</option>
            @endforeach
        </select>
        <div class="valid-feedback">¡Se ve bien! </div>
        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
    </div>
    @endif

    @if(!is_null($selecdistribuidores))
    <div class="mt-2">
        <label class="form-label" for="form8Example4">Distribuidor</label>
        <select  class="form-select"  name="distribuidor" id="distribuidor" required>
                @foreach($selecdistribuidores as $dis)
                    <option value="{{$dis->id}}">{{$dis->primer_nombre}} {{$dis->segundo_nombre}} {{$dis->apellido_paterno}} {{$dis->apellido_materno}}</option>
                @endforeach
        </select>
        <div class="valid-feedback">¡Se ve bien! </div>
        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
    </div>
    @endif 
    
     @if(!is_null($selecdistribuidores))
        <div class="mt-2">
        <div class="form-outline">
        <label class="form-label" for="form8Example4">Seleccione valera</label>
            <select  class="form-select" name="valera" required> 
                @foreach($selecvaleras as $val)
                    <option value="{{$val->id}}">{{$val->folio_inicio}} / {{$val->folio_fin}}</option>
                @endforeach
            </select>
           
            <div class="valid-feedback">¡Se ve bien! </div>
            <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
        </div>
    </div>
    @endif 
</div>






