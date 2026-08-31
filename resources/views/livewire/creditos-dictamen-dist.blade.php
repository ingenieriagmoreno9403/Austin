<div>
    
    <center class="border-0 bg-body mt-3">
        
        <div class="table-responsive marginTable float-search" id="mydatatable-container">
            <div class="d-flex justify-content-end mb-2">
                <div class="w-25">
                    <select id="permisoSelect" class="form-select form-select-sm" wire:model="selectedPermiso">
                        <option value="">Seleccione un permiso</option>
                        @foreach($permisos as $permiso)
                            <option value="{{ $permiso->id }}">{{ ucfirst($permiso->descripcion) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
             <table class="table table-light table-stripped display" id="table3"  style="width:100%!important;">
                <thead>
                    <tr class="text-tr">
                        @if($editar_credito == "A")
                        <th class="text-center fw-bold ">Editar</th>
                        @endif
                        {{-- @if($enviar_mesa_cred == "A")
                        <th class="text-center fw-bold ">Dictamen</th>
                        @endif --}}
                        @if($revisar_solicitud == "A")
                        <th class="text-center fw-bold ">Revisar</th>
                        @endif
                        <th class="text-center fw-bold ">Estado</th>
                        <th class="text-center fw-bold ">#</th>
                        <th class="text-center fw-bold ">Nombre Completo</th>
                        @if($descargar_contrato == "A")
                        <th class="text-center fw-bold ">Contrato</th>
                        @endif
                        <th class="text-center fw-bold ">Perfil</th>
                        <th class="text-center fw-bold ">Tipo</th>
                        <th class="text-center fw-bold ">Capital Solicitado</th>
                        <th class="text-center fw-bold ">Capital Autorizado</th>
                        <th class="text-center fw-bold ">Capital Liberado</th>
                        <th class="text-center fw-bold ">Sucursal</th>
                        <th class="text-center fw-bold ">Coordinador</th>
                        <th class="text-center fw-bold ">Fecha de creación</th>
                        <th class="text-center fw-bold ">Ultima actualización</th>
                    </tr>
                </thead>
                
                <tbody>
                    @foreach($varmesadecredito as $mesacred)
                        @foreach($varSucursalesUser as $item)
                            @if($item->idsucursal == $mesacred->idsucursal)
                                <tr class="boder-sec">
                                    @if($editar_credito == "A")
                                        <td class="">
                                            @if($mesacred->status =='pro_rev' || $mesacred->status =='pro_rec')
                                                <a class="btn text-primary border-0 text-truncate fs-9 " title="Editar" href="/vales/ActualizarDistribuidor/{{$mesacred->id}}" data-bs-toggle="tooltip" data-bs-placement="right""><i class="fa-solid fa-pen"></i>  Editar</a>
                                            @elseif($mesacred->status == 'pro_dic' || $mesacred->status == 'pro')
                                                <a class="btn text-secondary border-0 text-truncate fs-9 " title="Editar" href="/vales/ActualizarDistribuidor/{{$mesacred->id}}" data-bs-toggle="tooltip" data-bs-placement="right""><i class="fa-solid fa-pen"></i>  Editar</a>
                                            @elseif($mesacred->status == 'pro_aut' || $mesacred->status == 'pro_valRev' || $mesacred->status == 'val_dec')
                                                <a class="btn text-primary border-0 text-truncate fs-9 " title="Editar" href="/vales/getactualizarValidacion/{{$mesacred->id}}" data-bs-toggle="tooltip" data-bs-placement="right"><i class="fa-solid fa-pen"></i>  Editar</a>
                                            @else
                                                <a class="btn text-secondary border-0 text-truncate fs-9 " title="Editar" href="/vales/getactualizarValidacion/{{$mesacred->id}}" data-bs-toggle="tooltip" data-bs-placement="right"><i class="fa-solid fa-pen"></i>  Editar</a>
                                            @endif
                                        </td>
                                    @endif

                                    {{-- @if($enviar_mesa_cred == "A")
                                        <td class=" ">
                                            <center>
                                                @if($mesacred->status =='pro_rev')
                                                    <form action="/vales/enviaramesa_credito_act/{{$mesacred->id}}">
                                                        <button class="btn  fs-9 rounded-3 text-primary  border-0" title="enviar a mesa de credito" type="submit"><i class="fa-solid fa-send"></i> Enviar</button>
                                                    </form>
                                                @elseif( $mesacred->status =='pro_valRev')
                                                    <form action="/vales/enviaramesa_credito_val/{{$mesacred->id}}">
                                                        <button class="btn  fs-9 rounded-3 text-primary  border-0" title="enviar a mesa de credito" type="submit"><i class="fa-solid fa-send"></i> Enviar</button>
                                                    </form>
                                                @else
                                                    <button class="btn  fs-9 rounded-3 text-secondary  border-0" title="enviar a mesa de credito" type="button" disabled><i class="fa-solid fa-send"></i> Enviar</button>
                                                @endif
                                            </center>
                                        </td>
                                    @endif --}}

                                    @if($revisar_solicitud == "A")
                                    <td class=" ">
                                        <form action="/vales/GestionFase2/SolicitudMesaCredito/{{$mesacred->id}}">
                                        <center>
                                            @if($mesacred->status == 'pro_dic' || $mesacred->status == 'pro_rec' || $mesacred->status == 'pro_valDic' || $mesacred->status == 'val_dec')
                                                <button class="btn text-primary border-0  text-truncate fs-9 " title="Revisar Solicitud" type="submit" data-bs-toggle="tooltip" data-bs-placement="right" title="Ver"><i class="fa-solid fa-eye"></i> Revisar</button>
                                            @else
                                                <button class="btn text-secondary border-0 text-truncate fs-9 " title="Revisar Solicitud" type="submit" data-bs-toggle="tooltip" data-bs-placement="right" title="Ver"><i class="fa-solid fa-eye"></i> Revisar</button>
                                            @endif
                                        </center>
                                        </form>
                                    </td>
                                    @endif

                                    <td class="text-center">
                                        <center>
                                        @if($mesacred->status == 'pro_rev')
                                        <button class="btn btn-primary rounded-5 fs-11 text-truncate" title="Solicitud en revisión"><i class="fa-solid fa-user"></i>&nbsp; En revisión</button>
                                        @endif
                                        @if($mesacred->status == 'pro_dic')
                                        <button class="btn btn-warning text-secondary rounded-5 fs-11 text-truncate " title="Solicitud en dictamen"><i class="fa-solid fa-gavel"></i>&nbsp; En dictamen</button>
                                        @endif
                                        @if($mesacred->status == 'pro_rec')
                                        <button class="btn btn-danger rounded-5 fs-11 text-truncate" title="Solicitud rechazada"><i class="fa-solid fa-xmark"></i>&nbsp; Rechazado</button>
                                        {{-- <button class="btn btn-danger rounded-5 fs-9 " type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom1" aria-controls="offcanvasBottom"title="Subir Archivo"><i class="fa-solid fa-xmark"></i>&nbsp; Rechazado</button> --}}
                                        @endif
                                        @if($mesacred->status == 'pro_aut')
                                        <button class="btn btn-success rounded-5 fs-11 text-truncate" title="Subir archivos"><i class="fa-solid fa-check"></i>&nbsp; Autorizado</button>
                                        @endif
                                        @if($mesacred->status == 'pro_valRev')
                                        <button class="btn btn-success rounded-5 fs-11 text-truncate" title="Validación en revisión"><i class="fa-solid fa-user"></i>&nbsp; Autorizado</button>
                                        @endif
                                        @if($mesacred->status == 'pro_valDic')
                                        <button class="btn btn-success rounded-5 fs-11 text-truncate" title="Validación en dictamen"><i class="fa-solid fa-gavel"></i>&nbsp;  Autorizado</button>
                                        @endif
                                        @if($mesacred->status == 'val')
                                        <button class="btn btn-s rounded-5 fs-11 text-truncate" title="Validado"><i class="fa-solid fa-check"></i>&nbsp; Validado</button>
                                        @endif
                                        @if($mesacred->status == 'val_dec')
                                        <button class="btn btn-danger rounded-5 fs-11 text-truncate" title="Validación declinada"><i class="fa-solid fa-xmark"></i>&nbsp; Declinado</button>
                                        @endif
                                    </center>
                                    </td>

                                    <td class="text-secondary fw-bold ">{{$mesacred->id}}</td>
                                    <td class="text-secondary text-start ">{{$mesacred->nombre}}</td>

                                    @if($descargar_contrato == "A")
                                    <td class="text-secondary ">
                                        @if($mesacred->status == 'pro' || $mesacred->status == 'pro_rev' || $mesacred->status == 'pro_dic' || $mesacred->status == 'pro_rec')
                                            <button class="btn text-primary border-0 text-truncate fs-9 rounded-3" type="button" disabled>
                                                <i class="fa-solid fa-download"></i> Descargar
                                            </button>
                                        @else
                                            <form action="/generar_contrato/{{$mesacred->id}}">
                                                <button class="btn text-primary border-0 text-truncate fs-9 rounded-3" type="submit">
                                                    <i class="fa-solid fa-download"></i> Descargar
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                    @endif

                                    <td class="text-secondary fs-10">{{$mesacred->nombre_perfil}}</td>
                                    
                                    <td class="text-secondary fs-9">
                                        <center class="fs-9">
                                            @if($mesacred->tipo_dis =='BRONCE')
                                                <button class="btn bg_coffe text-coffe rounded-5 fs-11 text-truncate" for="btn-dia"><i class="fa-solid fa-award"></i> Bronce</button>
                                            @elseif($mesacred->tipo_dis =='ORO')
                                                <button class="btn bg_orange text-orange rounded-5 fs-11 text-truncate" for="btn-oro"><i class="fa-solid fa-trophy"></i> Oro</button> 
                                            @elseif($mesacred->tipo_dis =='PLATA')
                                                <button class="btn bg_secondary text-secondary rounded-5 fs-11 text-truncate" for="btn-plat"><i class="fa-solid fa-award"></i> Plata</button>
                                            @elseif($mesacred->tipo_dis =='PLATINO')
                                                <button class="btn bg_secondary text-secondary rounded-5 fs-11 text-truncate" for="btn-plat"><i class="fa-solid fa-trophy"></i> Platino</button>
                                            @elseif($mesacred->tipo_dis =='DIAMANTE')
                                                <button class="btn bg_primary text-primary rounded-5 fs-11 text-truncate" for="btn-plat"><i class="fa-solid fa-gem"></i> Diamante</button>
                                            @endif
                                       </center>
                                    </td>
                                   
                                    <td class="text-secondary">$ {{ number_format($mesacred->capital_solicitado, 2)}}</td>    
                                    <td class="text-dark fw-bold ">$ {{ number_format($mesacred->capital_autorizado, 2)}}</td> 
                                    <td class="text-success fw-bold ">$ {{ number_format($mesacred->capital, 2)}}</td> 
                                    <td class="text-secondary ">{{$mesacred->nombresuc}}</td>
                                    <td class="text-secondary ">{{$mesacred->nombreCoord}}</td>
                                    <td class="text-secondary ">{{$mesacred->created_at}}</td> 
                                    <td class="text-secondary ">{{$mesacred->updated_at}}</td> 
                                </tr>
                            @endif
                        @endforeach
                    @endforeach        
                </tbody>
            </table>
        </div> 
    </center> 
</div>
