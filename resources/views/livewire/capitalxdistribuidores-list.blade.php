    @php($varphp =0)
    <div class="col">
        <div class="form-outline">
         <label class="form-label" for="form8Example4">Distribuidor</label>
            @php($id='')
            <select name="distribuidor"  class="form-select select2"  wire:model="distribuidores" onchange="tipo_dis(this.value)" required  style="width: 100%!important;">
              <option selected value="">Selecciona un distribuidor</option>
                @foreach($distribuidor as $distri)
                    @if($distri->id != 00)
                        <option  value="{{$distri->id}}">{{$distri->id}} - {{$distri->primer_nombre}} {{$distri->segundo_nombre}} {{$distri->apellido_paterno}} {{$distri->apellido_materno}}</option>
                    @endif
                @php($id=$distri->id)
                @endforeach
            </select>
            <div class="valid-feedback">¡Se ve bien!</div>
            <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
        </div>
    </div>

