    <div>
        <div>
            @if(!$validaAutorizar->isEmpty())
                @foreach($validaAutorizar as $item)
                  @if($item->estado != "Autorizado")
                    @php($validaAut = "no")
                  @else
                    @php($validaAut = "si")
                  @endif
                @endforeach
            @else
                @php($validaAut = "si")
            @endif

            <div class="modal fade" id="modalArqueo" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-xl">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title text-dark" id="exampleModalLabel">Relación de Efectivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <form action="/Tesoreria/Movimientos/InsertArqueoCaja/{{$tipo}}/{{$empresaid}}/{{$recordId}}/{{$fecha}}" method="POST" class="g-3 needs-validation mt-1 text-start" novalidate>
                    @csrf
                    <div class="modal-body">
                      <div class="container">
                          <div class="row text-center">
                            <h6 class="text-center fs-8 fw-bold">TOTAL BILLETES</h6>
                                <div class="row">
                                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                    <img src="{{ asset('Images/billetes/mil.jpg') }}" width="100px" alt="billete">
                                    <br>1,000.00 <br><br><input type="number" class="form-control fs-8" name="mil" id="mil" required >
                                  </div>

                                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                    <img src="{{ asset('Images/billetes/quinientos.jpg') }}" width="100px" alt="billete">
                                    <br>500.00 <br><br><input type="number" class="form-control fs-8" name="quinientos" id="quinientos" required >
                                  </div>

                                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                    <img src="{{ asset('Images/billetes/doscientos.jpg') }}" width="100px" alt="billete">
                                    <br>200.00 <br><br><input type="number" class="form-control fs-8" name="doscientos" id="doscientos" required >
                                  </div>

                                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                    <img src="{{ asset('Images/billetes/cien.jpg') }}" width="100px" alt="billete">
                                    <br>100.00 <br><br><input type="number" class="form-control fs-8" name="cien" id="cien" required >
                                  </div>

                                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                    <img src="{{ asset('Images/billetes/cincuenta.jpeg') }}" width="100px" alt="billete">
                                    <br>50.00 <br><br><input type="number" class="form-control fs-8" name="cincuenta" id="cincuenta" required >
                                  </div>

                                  <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                    <img src="{{ asset('Images/billetes/veinte.jpg') }}" width="100px" alt="billete">
                                    <br>20.00 <br><br><input type="number" class="form-control fs-8" name="veinte" id="veinte" required >
                                  </div>
                                </div>
                          </div>
                          <br>
                          <hr>

                          <div class="row text-center">
                            <h6 class="text-center fs-8 fw-bold">TOTAL MONEDAS</h6>

                            <div class="row">
                              <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                <img src="{{ asset('Images/monedas/diez.jpg') }}" width="50px" alt="moneda">
                                    <br>10.00 <br><br><input type="number" class="form-control fs-8" name="diez" id="diez" required >
                              </div>

                              <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                <img src="{{ asset('Images/monedas/cinco.jpg') }}" width="40px" alt="moneda">
                                    <br>5.00 <br><br><input type="number" class="form-control mt-2 fs-8" name="cinco" id="cinco" required >
                              </div>

                              <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                <img src="{{ asset('Images/monedas/dos.jpg') }}" width="30px" alt="moneda">
                                    <br>2.00 <br><br><input type="number" class="form-control mt-3 fs-8" name="dos" id="dos" required >
                              </div>

                              <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                <img src="{{ asset('Images/monedas/uno.jpg') }}" width="30px" alt="moneda">
                                    <br>1.00 <br><br><input type="number" class="form-control mt-3 fs-8" name="uno" id="uno" required >
                              </div>

                              <div class="col m-1 rounded-3 p-3 shadow bg-light text-center push">
                                <img src="{{ asset('Images/monedas/cincuentacentavos.jpg') }}" width="25px" alt="moneda">
                                    <br>0.50 <br><br><input type="number" class="form-control mt-3 fs-8" name="cincuentacentavos" id="cincuentacentavos" required >
                              </div>
                            </div>
                          </div>
                      </div>
                    </div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-danger fs-8 rounded-5" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
                      <button type="submit" class="btn btn-success fs-8 rounded-5"><i class="fa-solid fa-check"></i> Confirmar</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            {{-- encabezado --}}
            <div class="row start-center">
                <h6 class="mt-1 mb-1 animate__animated animate__backInLeft text-truncate text-dark fw-normal">
                  <b class="fs-4 text-orange fw-normal">{{$mesNombre}}</b>
                    Control de {{$tipo}} 
                </h6> 
            </div>

            <div class="row start-center">
              <div>
                @if($validaAut == "si")
                  <button type="button" class="btn btn-primary m-1 rounded-3 fs-8 push" data-bs-toggle="modal" data-bs-target="#modalGasto">
                    <i class="fa-solid fa-circle-dollar-to-slot"></i> Aplicar Gasto
                  </button>
                @else
                  <button type="button" class="btn btn-primary m-1 rounded-3 fs-8 push" disabled>
                    <i class="fa-solid fa-circle-dollar-to-slot"></i> Aplicar Gasto
                  </button>
                @endif

                @if($validaAut == "si")
                  <button type="button" class="btn btn-baseColor m-1 rounded-3 fs-8 push border-0" data-bs-toggle="modal" data-bs-target="#modalEntrega">
                    <i class="fa-solid fa-dollar"></i> Entrega de Efectivo
                  </button>
                @else
                  <button disabled class="btn btn-baseColor m-1 rounded-3 fs-8 push border-0">
                    <i class="fa-solid fa-dollar"></i> Entrega de Efectivo
                  </button>
                @endif

                @if($varaqueo == "null")
                    @if($validaAut == "si")
                      <button type="button" class="btn btn-success m-1 rounded-3 fs-8 push border-0" data-bs-toggle="modal" data-bs-target="#modalArqueo">
                        <i class="fa-solid fa-chart-line"></i> Realizar Arqueo
                      </button>
                    @else
                      <button type="button" class="btn btn-success m-1 rounded-3 fs-8 push border-0" disabled>
                        <i class="fa-solid fa-chart-line"></i> Realizar Arqueo
                      </button>
                    @endif
                    
                @else
                    @if($validaAut == "si")
                      <a href="/Tesoreria/Movimientos/ArqueoCaja/{{$tipo}}/{{$empresaid}}/{{$id}}/{{$fecha}}" class="btn btn-success m-1 rounded-3 fs-8 push">
                        <i class="fa-solid fa-chart-line"></i> Ver Arqueo
                      </a>
                    @else
                      <button type="button" class="btn btn-success m-1 rounded-3 fs-8 push border-0" disabled>
                        <i class="fa-solid fa-chart-line"></i> Realizar Arqueo
                      </button>
                    @endif
                @endif

                <button type="button" class="btn btn-outline-success rounded-3 fs-8 m-0 text-truncate" data-bs-toggle="offcanvas" data-bs-target="#ModalArqueos" aria-controls="offcanvasTop">
                  <i class="fa-solid fa-magnifying-glass"></i> Arqueos Realizados
                </button>
              </div>
            </div>
        </div>

        <div class="row text-end">
            @if ($permisos1 != "filtrar_arqueos")
                @if($validaAut == "si")
                  <div class="p-3 text-end">
                      <button type="button" class="btn btn-secondary m-1 fs-9 push text-white" data-bs-toggle="modal" data-bs-target="#modalSolicitud">
                          <i class="fa-solid fa-info"></i> Arqueo en otra fecha
                      </button>
                  </div>
                @else
                  <div class="p-3 text-end">
                    <button type="button" class="btn btn-secondary m-1 fs-9 push text-white" data-bs-toggle="modal" data-bs-target="#modalSolicitud" disabled>
                        <i class="fa-solid fa-info"></i> Arqueo en otra fecha
                    </button>
                  </div>
                @endif
            @else
                @if($validaAut == "si")
                  <div class="p-4 text-end">
                    <label class="text-black">Fecha de arqueo</label>
                    <div class="input-group">
                        <input type="date" wire:model="fecha" class="form-control form-control-sm" required />
                    </div>
                  </div>
                @else
                  <div class="p-4 text-end">
                    <label class="text-black">Fecha de arqueo</label>
                    <div class="input-group">
                        <input type="date" wire:model="fecha" class="form-control form-control-sm" required disabled/>
                    </div>
                  </div>
                @endif
            @endif
        </div>
       
        <div class="row">
          <div class="table-responsive">
              <table class="table table-stripped table-hover display" id="table">
                  <thead>
                    <tr>
                        <th class="text-center fw-bold ">Fecha</th>
                        <th class="text-center fw-bold ">Cuenta Afectada</th>
                        <th class="text-center fw-bold ">Tipo</th>
                        <th class="text-center fw-bold ">Ingreso</th>
                        <th class="text-center fw-bold ">Egreso</th>
                        <th class="text-center fw-bold ">Saldo</th>
                        <th class="text-center  fw-bold">Importe</th>
                        <th class="text-center  fw-bold">IVA</th>
                        <th class="text-center  fw-bold">RET IVA</th>
                        <th class="text-center  fw-bold">RET ISR</th>
                        <th class="text-center  fw-bold">RET ISR RESICO</th>
                        <th class="text-center fw-bold ">Titular</th>
                        <th class="text-center fw-bold ">Información</th>
                        <th class="text-center fw-bold ">Estado</th>
                        <th class="text-center fw-bold ">No. Poliza</th>
                        @if($tipo == 'Caja Chica')
                        <th class="text-center fw-bold ">Comprobante</th>
                        @endif
                        <th class="text-center fw-bold ">No. Referencia</th>
                        <th class="text-center fw-bold ">Pertenece</th>
                        <th class="text-center fw-bold ">Realizado Por</th>
                    </tr>
                  </thead>

                  <tbody>
                      @foreach($obtenerHistorial as $datos)
                      <tr>
                          <td class="bg-0 text-secondary text-truncate">{{$datos->created_at}}</td>
                          <td class="table-light text-start fw-bold text-secondary">{{$datos->cuenta}}</td>
                          <td class="table-light text-secondary text-truncate">
                              @if($datos->tipo_movimiento == "APERTURA")
                              <button class="btn btn-success fs-10 pointer_none rounded-5"><i class="fa-solid fa-check"></i>&nbsp; {{$datos->tipo_movimiento}}</button>
                              @elseif($datos->tipo_movimiento == "CANCELACION")
                              <button class="btn btn-danger fs-10 pointer_none rounded-5"><i class="fa-solid fa-ban"></i>&nbsp; {{$datos->tipo_movimiento}}</button>
                              @elseif($datos->tipo_movimiento == "TRANSFERENCIA")
                              <button class="btn btn-baseColor fs-10 pointer_none rounded-5"><i class="fa-solid fa-right-left"></i>&nbsp; {{$datos->tipo_movimiento}}</button>
                              @elseif($datos->tipo_movimiento == "ENTREGA")
                              <button class="btn btn-baseColor fs-10 pointer_none rounded-5"><i class="fa-solid fa-arrow-right"></i>&nbsp; {{$datos->tipo_movimiento}}</button>
                              @elseif($datos->tipo_movimiento == "INGRESO")
                              <button class="btn btn-success fs-10 pointer_none rounded-5"><i class="fa-solid fa-plus"></i>&nbsp; {{$datos->tipo_movimiento}}</button>
                              @elseif($datos->tipo_movimiento == "PAGO")
                              <button class="btn btn-success fs-10 pointer_none rounded-5"><i class="fa-solid fa-plus"></i>&nbsp; {{$datos->tipo_movimiento}}</button>
                              @elseif($datos->tipo_movimiento == "CARGO" || $datos->tipo_movimiento == "GASTO")
                              <button class="btn btn-danger fs-10 pointer_none rounded-5"><i class="fa-solid fa-minus"></i>&nbsp; {{$datos->tipo_movimiento}}</button>
                              @elseif($datos->tipo_movimiento == "DESEMBOLSO")
                              <button class="btn btn-danger fs-10 pointer_none rounded-5"><i class="fa-solid fa-minus"></i>&nbsp; {{$datos->tipo_movimiento}}</button>
                              @elseif($datos->tipo_movimiento == "RETIRO")
                              <button class="btn btn-danger fs-10 pointer_none rounded-5"><i class="fa-solid fa-minus"></i>&nbsp; {{$datos->tipo_movimiento}}</button>
                              @else
                              <button class="btn btn-success fs-10 pointer_none rounded-5"><i class="fa-solid fa-money-bill"></i>&nbsp; </button>
                              @endif
                          </td>
                          <td class="bg-0 text-secondary text-truncate text-start">
                              @if($datos->ingreso == 0)
                              @else
                              <p class="fs-9 text-success fw-bold p-0 m-0 b-0"> + ${{$formattedNum = number_format($datos->ingreso, 2)}}</p>
                              @endif
                          </td>
                          <td class="bg-0 text-secondary text-truncate text-start">
                              @if($datos->egreso == 0)
                              @else
                              <p class="fs-9 text-danger fw-bold p-0 m-0 b-0"> - ${{$formattedNum = number_format($datos->egreso, 2)}}</p>
                              @endif
                          </td>
                          <td class="bg-0 text-dark text-truncate fw-bold text-start"> ${{$formattedNum = number_format($datos->saldo, 2)}}</td>
                          @php($total_neto = $datos->egreso)
                          @php($suma = $datos->total_iva)
                          @php($resta = $datos->total_ret_iva + $datos->total_ret_isr + $datos->total_ret_isr_resico)
                          @php($importe = ($total_neto + $resta) - $suma)
                          <td class="table-light text-dark text-truncate">${{$formattedNum = number_format($importe, 2)}}</td>
                          <td class="table-light text-dark text-truncate">{{$datos->total_iva}}</td>
                          <td class="table-light text-dark text-truncate">{{$datos->total_ret_iva}}</td>
                          <td class="table-light text-dark text-truncate">{{$datos->total_ret_isr}}</td>
                          <td class="table-light text-dark text-truncate">{{$datos->total_ret_isr_resico}}</td>
                          <td class="table-light fw-bold text-secondary text-truncate">{{$datos->responsable}}</td>
                          <td class="table-light text-start text-secondary text-truncate">
                              <p class="btn border-0 text-truncate fs-9 m-0" style="max-width: 150px;" data-bs-toggle="modal" data-bs-target="#Modalcomentario{{$datos->id}}">{{$datos->concepto}}</p>
                          </td>
                          <td class="table-light text-secondary text-truncate">
                              @if($datos->estado == "A")
                              <button class="btn btn-success fs-10 pointer_none rounded-5"><i class="fa-solid fa-check"></i>&nbsp; AUTORIZADO</button>
                              @elseif($datos->estado == "D")
                              <button class="btn btn-danger fs-10 pointer_none rounded-5"><i class="fa-solid fa-ban"></i>&nbsp; DECLINADO</button>
                              @elseif($datos->estado == "E")
                              <button class="btn btn-baseColor fs-10 pointer_none rounded-5"><i class="fa-solid fa-pause"></i>&nbsp;EN ESPERA</button>
                              @else
                              <button class="btn btn-primary fs-10 pointer_none rounded-5"><i class="fa-solid fa-exclamation"></i>&nbsp;INCONCLUSO</button>
                              @endif
                          </td>
                          <td class="bg-0 text-secondary text-truncate">
                              @if($datos->numero_poliza != 0)
                              <form action="/Tesoreria/Movimientos/Poliza/{{$datos->tipo_movimiento}}/{{$datos->numero_poliza}}">
                                  <input type="text" name="idmov" hidden value="{{$datos->id}}">
                                  <button type="submit" class="btn border-0 fs-9"><i class="fa-solid fa-download fs-9 text-primary"></i> {{$datos->numero_poliza}}</button>
                              </form>
                              @endif
                          </td>
                          @if($tipo == 'Caja Chica')
                          <td class="bg-0 text-secondary text-truncate">
                              @if(!is_null($datos->ruta_evidencia))
                              <a target="_blank" class="btn btn-primary border-0 m-0 fs-9 rounded-5" href="{{asset('Tesoreria/Gastos/Cajas/'.$datos->ruta_evidencia)}}"><i class="fa-solid fa-eye"></i> Ver</a>
                              @endif
                          </td>
                          @endif
                          <td class="bg-0 text-secondary text-truncate">{{$datos->numero_referencia}}</td>
                          <td class="bg-0 text-secondary text-truncate">{{$datos->pertenencia}}</td>
                          <td class="table-light text-secondary text-truncate">{{$datos->created_by}} - {{$datos->created_at}}</td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
        </div>
    </div>    
    </div>
</div>
