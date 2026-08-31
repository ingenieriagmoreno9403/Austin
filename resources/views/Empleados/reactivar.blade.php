@extends('layouts.app')
@section('content')
<div class="container-fluid format_page">


  @foreach($obtenerempleado as $empleado)
      <div class="card p-4 pt-3 mb-4">
          <div class="row">
              <h3 class="col-md-7 col-12 center text-orange">Reactivar Empleado</h3>

              <div class="col-md-5 col-12 border-4 border-end border-warning rounded-2 p-2 bg-light mb-3 row text-orange" role="alert">
                <h3 class="col-1"><i class="fas fa-exclamation-triangle"></i></h3>
                <h6 class="text-secondary col-11 mt-2"> Este empleado esta inactivo en este momento</h6>
              </div>
          </div>

          
        
          <div class="row mb-2">
              <div>
                <form action="{{ route('Empleados.updateReactivar', $empleado->idempleado) }}" method="POST" enctype="multipart/form-data" class="g-3 needs-validation form" novalidate>
                  @csrf
                  @method('PUT')

                      <!-- Detalles de reingreso-->
                      <div class="row mb-4">
                        <h5 class="text-dark"><b class="fs-4">1.</b> Detalles Generales</h5>
                        <input type="text" hidden class="form-control text" name="idnomina"  value="{{$empleado->idnom}}" required />
                        <input type="text" hidden class="form-control text" name="rfc" value="{{$empleado->rfc}}" required/>

                        <div class="col">
                          <div class="form-outline">
                          <label class="form-label" >Fecha de reingreso</label>
                            <input type="date" name="fecha_alta" id="fecha_alta" class="form-control"  value="{{$empleado->fecha_ingreso}}"  required />
                            <div class="valid-feedback">
                              ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                              Por favor, completa la información requerida.
                            </div>
                          </div>
                        </div>

                        <div class="col">
                          <div class="form-outline">
                          <label class="form-label" >Fecha de ingreso a IMSS</label>
                            <input type="date" name="fecha_ingreso_imss" id="fecha_ingreso_imss" class="form-control" value="{{$empleado->fecha_ingreso_imss}}" maxlength="12" />
                            <div class="valid-feedback">
                              ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                              Por favor, completa la información requerida.
                            </div>
                          </div>
                        </div>
                      </div>
                    
                      <h5 class="text-dark"><b class="fs-4">2.</b> Salario</h5>

                      <div class="row mb-2"> 
                          <div class="col">
                            <div class="form-outline">
                                <label class="form-label">Empresa</label>
                                <select class="form-select form-select mb-3" id="cmbempresas" name="cmbempresas"  required>
                                  @foreach($varempresas as $obtenerempresa)
                                    @if($empleado->id == $obtenerempresa->id)
                                        @if ($obtenerempresa->efectivo == 1)
                                          <option value="{{$obtenerempresa->id}}" selected>Efectivo - {{$obtenerempresa->nombre_empresa}}</option>
                                        @else
                                          <option value="{{$obtenerempresa->id}}" selected>{{$obtenerempresa->nombre_empresa}}</option>
                                        @endif 
                                    @else
                                        @if ($obtenerempresa->efectivo == 1)
                                          <option value="{{$obtenerempresa->id}}">Efectivo - {{$obtenerempresa->nombre_empresa}}</option>
                                        @else
                                          <option value="{{$obtenerempresa->id}}">{{$obtenerempresa->nombre_empresa}}</option>
                                        @endif 
                                    @endif
                                  @endforeach
                              
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                          </div>
                
                          <div class="col-md-3 mb-2">
                            <div class="form-outline">
                              <label class="form-label" >Sueldo Mensual</label>
                              <input type="text" name="salario_bruto" id="salario_bruto" class="form-control text" maxlength="8"  value="{{$empleado->salario_bruto}}"   required />
                              <div class="valid-feedback">
                                ¡Se ve bien!
                              </div>
                              <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                              </div>
                            </div>
                          </div>

                          <div class="col-md-3 mb-2">
                            <div class="form-outline">
                              <label class="form-label" >Salario Diario</label>
                                <input type="text" name="salario_fijo" class="form-control text" maxlength="8" value="{{$empleado->salario_fijo}}" required/>
                                <div class="valid-feedback">
                                  ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                  Por favor, completa la información requerida.
                                </div>
                            </div>
                          </div>

                          @unless($visorFiscalActivo)
                          <div class="col-md-3 mb-2">
                            <div class="form-outline">
                              <label class="form-label" >Salario Diario Excedente</label>
                                <input type="text" name="excedente" class="form-control text" maxlength="8" value="{{$empleado->excedente}}" required/>
                                <div class="valid-feedback">
                                  ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                  Por favor, completa la información requerida.
                                </div>
                            </div>
                          </div>
                          @else
                          <input type="hidden" name="excedente" value="{{ $empleado->excedente }}">
                          @endunless
                      </div>

                      {{-- Descuento de Infonavit --}}
                      <div class="row text-center mt-4">
                        <h6>Aplicar credito Infonavit 
                          <input style="border: .5px solid rgb(165, 165, 165);width:15px;height:15px;" class="form-check-input" type="checkbox" id="terminos" value="1" onclick="chekinfonavit(this)" />
                        </h6>
                      </div>
                  
                      <div class="row mb-2">
                        <div class="col-md-4 p-1" id="tipo_descuento_infonavit">
                            <div class="form-outline">
                                <label class="form-label">Tipo de descuento infonavit</label>
                                <select class="form-select" name="tipo_infonavit" >
                                  @foreach($vartipodescinfo as $obtenertipo)
                                    @if($empleado->idinfonavit == $obtenertipo->id)
                                      <option value="{{$empleado->idinfonavit}}" selected>{{$empleado->nombreinfonavit}}</option>  
                                    @else
                                      <option value="{{$obtenertipo->id}}">{{$obtenertipo->Nombre}}</option>
                                    @endif
                                  @endforeach
                                </select>   
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>

                        <div class="col-md-4 p-1"  id="factor_sua">
                          <div class="form-outline">
                            <label class="form-label">Cuota fija</label>
                              <input type="text" name="factor_sua" class="form-control text" value="{{$empleado->factor_sua}}"  maxlength="8" />
                          </div>
                        </div>

                        {{-- <div class="col-md-3"  id="descuento_quincenal">
                          <div class="row">
                            <label class="form-label">Descuento quincenal</label>
                              <input type="text" name="descuento_quincenal" class="form-control text" value="{{$empleado->descuento_quincenal}}"  maxlength="8" />
                          </div>
                        </div> --}}

                        <div class="col-md-4 p-1"  id="numero_credito_infonavit">
                          <div class="form-outline">
                              <label class="form-label">Numero de credito infonavit</label>
                              <input type="text" name="numero_credito_infonavit" class="form-control text" value="{{$empleado->numero_credito_infonavit}}" maxlength="8"  />
                          </div>
                        </div>
                      </div>

                      <div class="text-end mt-3">
                        <div>
                          <button type="submit" class="btn btn-baseColor fs-8" ><i class="fas fa-user-check"></i>&nbsp;&nbsp;Activar empleado</button>
                        </div>
                      </div>
                </form>
              </div>                       
          </div>
      </div>
  @endforeach
</div>

<script src="{{ asset('js/btnBack1.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js')}}"></script>
<script src="{{ asset('js/reactivarEmpleado.js')}}"></script>
@endsection
