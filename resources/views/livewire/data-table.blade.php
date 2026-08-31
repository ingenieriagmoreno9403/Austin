<div class="row">
    <div class="col-md-6">
        <div class="row">
            <div class="col-md-3">
                {{-- <input type="checkbox" class="btn-check" id="pro" autocomplete="off" wire:model="active"/> --}}
                <label class="fs-9 lavel-control" for="status">Prospecto en revisión</label>
                <select wire:model="statusFilter" class="form-select fs-10 ">
                    <option value="">Selecciona...</option>
                    <option value="pro">Prospecto</option>
                    <option value="pro_valDic">Validación en Dictamen</option>
                    <option value="pro_apro">Prospecto aprobado</option>
                    <option value="valera_act">Activo</option>
                </select>
                
            </div>

            <div class="col-md-2">
                <input type="checkbox" class="btn-check" id="btn-dic" autocomplete="off">
                <label class="btn btn-outline-warning rounded-5 fs-10" for="btn-dic">En dictamen</label>
            </div>

            <div class="col-md-2">
                <input type="checkbox" class="btn-check" id="btn-rec" autocomplete="off">
                <label class="btn btn-outline-danger rounded-5 fs-10" for="btn-rec">Rechazado</label>
            </div>
            <div class="col-md-2">
                <input type="checkbox" class="btn-check" id="btn-aut" autocomplete="off">
                <label class="btn btn-outline-success rounded-5 fs-10" for="btn-aut">Autorizado</label>
            </div>
            <div class="col-md-2">
                <input type="checkbox" class="btn-check" id="btn-val" autocomplete="off">
                <label class="btn btn-outline-secondary rounded-5 fs-10" for="btn-val">Validado</label>
            </div>
        </div>
    
    </div>
    <div class="col"></div>
</div>

<div class="table-responsive pad-table" id="mydatatable-container" style="margin-top:-20px; "> 
    <table class="table table-hover " id="tblempleados">
        <thead>
            <tr class="tr-table"> 
                @if(Auth::user()->tipo =='sucursal')
                    <th class="text-center fw-light">Editar</th>
                @endif
                {{-- @if(Auth::user()->tipo =='sucursal')
                    <th class="text-center fw-light">Documentos</th>
                @endif --}}
                {{-- <th class="text-center fw-light">Acción</th> --}}
                @if(Auth::user()->tipo =='administrativo')
                    <th class="text-center fw-light">Revisar</th>
                @endif
                <th class="text-center fw-light">Estado</th>
                {{-- <th class="text-center fw-light">Historial</th> --}}
                <th class="text-center fw-light">No. Distribuidor</th>
                <th class="text-center fw-light">Nombre Completo</th>
                <th  class="text-center fw-light">Sucursal</th>
                <th  class="text-center fw-light">Capital Solicitado</th>
                <th  class="text-center fw-light">Capital Autizado</th>
            </tr>
        </thead>
        
        <tbody>
            @foreach($distribuidores as $mesacred)
                <tr>
                    <!-----Herramientas de la tabla---->
                    @if(Auth::user()->tipo =='sucursal')
                    <td class="bg-0">
                        @if($mesacred->status =='pro_rev')
                        <form action="/vales/getactualizardistribuidor/{{$mesacred->id}}">
                            <button  class="fa-solid fa-pen bor text-s btn border-0" title="Editar" type="submit" data-bs-toggle="tooltip" data-bs-placement="right"></button>
                            <span class="text-success fs-9 fst-italic fw-light"><i class="fa-solid fa-check"></i></span>
                        </form>
                        @elseif($mesacred->status =='pro_aut')
                        <form action="/vales/getSubirDocVal/{{$mesacred->id}}">
                            <button class="text-s btn fa-solid fa-arrow-up-from-bracket bor"  type="submit"></button>
                        </form>
                        @elseif($mesacred->status =='pro_valRev' || $mesacred->status =='pro_val')
                        <form action="/vales/getactualizarValidacion/{{$mesacred->id}}">
                            <button  class="fa-solid fa-pen bor text-s btn border-0" title="Editar" type="submit" data-bs-toggle="tooltip" data-bs-placement="right"></button>
                            <span class="text-success fs-9 fst-italic fw-light"><i class="fa-solid fa-check"></i></span>
                        </form>
                        @else
                        <form action="#">
                            <button  class="fa-solid fa-pen bor text-s btn  border-0" type="submit" data-bs-toggle="tooltip" data-bs-placement="right" disabled></button>
                            <span class="text-danger fs-9 fst-italic fw-light"><i class="fa-solid fa-xmark"></i></span>
                        </form>
                        @endif
                    </td>
                    @endif
                    {{-- @if(Auth::user()->tipo =='sucursal' )
                    <td class="bg-0">
                        @if($mesacred->status =='val')
                        <form action="/vales/getSubirDocVal/{{$mesacred->id}}">
                            <button class="text-s btn fa-solid fa-arrow-up-from-bracket bor"  type="submit"></button>
                        </form>
                        @else
                            <form action="{{route('vales.getverdoc', $mesacred->id) }}">
                              <button class="text-s btn fa-solid fa-eye bor" title="Ver" type="submit"></button>
                            </form>
                        @endif
                    </td>
                    @endif --}}
                   
                      {{-- <td class="bg-0">
                        <center>
                        @if(Auth::user()->tipo =='sucursal' )
                            @if($mesacred->status =='pro_rev')
                                <form action="/vales/enviaramesa_credito_act/{{$mesacred->id}}">
                                    <button class="btn btn-warning fs-9 rounded-5 text-secondary" type="submit"><i class="fa-solid fa-gavel"></i> Enviar a dictamen</button>
                                </form>
                            @elseif($mesacred->status =='pro_val' || $mesacred->status =='pro_valRev')
                                <form action="/vales/enviaramesa_credito_val/{{$mesacred->id}}">
                                    <button class="btn btn-warning fs-9 rounded-5 text-secondary" type="submit"><i class="fa-solid fa-gavel"></i> Enviar a dictamen</button>
                                </form>
                            @endif
                        @elseif(Auth::user()->tipo =='administrativo' && $mesacred->status =='pro_aut')
                            <form action="/vales/iniciarValidacion/{{$mesacred->id}}">
                                <button class="btn btn-light fs-9 rounded-5 text-secondary" type="submit"><i class="fa-solid fa-flag-checkered"></i> Comenzar validación</button>
                            </form>
                        @else
                        <p class="fs-8 text-secondary fw-light fst-italic"><i class="fa-solid fa-circle-exclamation"></i> Espere para usar</p>
                        @endif
                        </center>
                      </td> --}}
                  
                    @if(Auth::user()->tipo =='administrativo')
                    <td class="bg-0">
                        <form action="/vales/GestionFase2/SolicitudMesaCredito/{{$mesacred->id}}">
                            <button class="fa-regular fa-note-sticky bor text-s btn border-0" title="Revisar Solicitud" type="submit" data-bs-toggle="tooltip" data-bs-placement="right" title="Ver"></button>
                        </form>
                    </td>
                    @endif

                    <td class="bg-0 text-center">
                        <center>
                          @if($mesacred->status == 'pro_rev')
                          <button class="btn btn-primary rounded-5 fs-10">Prospecto en revisión</button>
                          {{-- <div class="col-md-6 cs1"></div> --}}
                          @endif
                          @if($mesacred->status == 'pro_dic')
                           <button class="btn btn-warning rounded-5 fs-10">En dictamen</button>
                          {{-- <div class="col-md-6 cs3"></div>                     --}}
                          @endif
                          @if($mesacred->status == 'pro_rec')
                           <button class="btn btn-danger rounded-5 fs-10">Rechazado</button>
                          {{-- <div class="col-md-6 cs4"></div>                     --}}
                          @endif
                          @if($mesacred->status == 'pro_aut')
                           <button class="btn btn-success rounded-5 fs-10">Autorizado</button>
                          {{-- <div class="col-md-6 cs5"></div>                     --}}
                          @endif
                          {{-- @if($mesacred->status == 'val' || $mesacred->status == 'pro_val')
                           <button class="btn btn-secondary rounded-5 fs-10">alidación</button>
                          <div class="col-md-6 cs6 fs-9"></div>                    
                          @endif --}}
                          @if($mesacred->status == 'pro_valRev')
                          <button class="btn btn-success rounded-5 fs-10">Autorizado</button>
                          {{-- <div class="col-md-6 cs6_rev"></div>                     --}}
                          @endif
                          @if($mesacred->status == 'pro_valDic')
                          <button class="btn btn-success rounded-5 fs-10">Autorizado</button>
                          {{-- <div class="col-md-6 cs6_dic"></div>                     --}}
                          @endif
                          @if($mesacred->status == 'pro_apro')
                           <button class="btn btn-secondary rounded-5 fs-10">Validado</button>
                          {{-- <div class="col-md-6 cs6_apro"></div>                     --}}
                          @endif
                          @if($mesacred->status == 'pro_den')
                           <button class="btn btn-secondary rounded-5 fs-10">Declinado</button>
                          {{-- <div class="col-md-6 cs6_den"></div>                     --}}
                          @endif
                       </center>
                      </td>

                    <td class="table-light text-secondary">{{$mesacred->id}}</td>
                    <td class="table-light text-secondary">{{$mesacred->primerNombre}}</td>
                    <td class="table-light text-secondary">{{$mesacred->idsucursal}}</td>
                    <td class="table-light text-secondary">{{$mesacred->capital}}</td> 
                    <td class="table-light text-secondary">{{$mesacred->capital_autorizado}}</td> 
                </tr>
            @endforeach        </tbody>
    </table>

</div> 
