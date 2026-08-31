@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/productos" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Productos
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">
                            Productos <i class="fa-solid fa-chevron-right fs-6"></i> Editar
                        </h2>
                        <p class="text-muted mb-0">Modificación de información del producto</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-4 pb-0 bg-body rounded">
        @foreach($Listadoproductosxid as $pro)
        <form class="needs-validation msform" method="post" action="{{route('mactualizaprod')}}" enctype="multipart/form-data" novalidate>
            @csrf
                <div class="row">
                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" >Fecha Ultima entrada</label> 
                            <input type="date"  name="fecha_entrada" id="fecha_entrada" class="form-control text"   value = "{{$pro->fecha_ultima_entrada}}"  minlength="3" maxlength="100" required />  
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <input type="text"  value="{{$pro->idproducto}}" hidden name ="id">
                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                        <label class="form-label" for="form8Example4">SKU</label>
                            <input type="text"  class="form-control text" name="sku" id="sku" minlength="3" maxlength="100" value = "{{$pro->sku}}"  required />
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>                      
                        </div>
                    </div>


                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" for="form8Example4">Codigo Barras</label>
                            <input type="text" name="codigo_barras" id="codigo_barras" class="form-control text" minlength="50" value = "{{$pro->codigo_barras}}" maxlength="100"required />
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" >Proveedor</label> 
                            <select name="id_proveedor" id="" class="form-control">
                            <option value="0" {{ (int) ($pro->id_proveedor ?? 0) === 0 ? 'selected' : '' }}>Por definir</option>
                            @foreach($listadoprovedores as $ltsprov)
                            @if($pro->id_proveedor == $ltsprov->id)
                            <option selected value="{{$ltsprov->id}}">{{$ltsprov->nombre}}</option>
                            @else
                            <option  value="{{$ltsprov->id}}">{{$ltsprov->nombre}}</option>
                            @endif
                            @endforeach
                            </select> 
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label">Nombre</label>
                            <input type="text"  name="nombre" id="nombre" class="form-control text" minlength="3" maxlength="60"  value = "{{$pro->nombre}}"  required />
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>

                    <div class="col-md-8 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" for="descripcion">Descripción <span class="text-danger">*</span></label>
                            <textarea name="descripcion" id="descripcion" class="form-control text @error('descripcion') is-invalid @enderror" minlength="3" maxlength="100" required>{{ old('descripcion', $pro->descripcion) }}</textarea>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">La descripción es obligatoria (mínimo 3 caracteres).</div>
                            @error('descripcion')
                                <div class="text-danger fs-8 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" >Categoría</label> 
                            <select name="id_categoria" id="" class="form-select" required>
                                <option value="">...</option>
                                @foreach($Listacategoriaproductos as $cat)
                                @if($pro->id_categoria == $cat->id)
                                <option selected value="{{$cat->id}}">{{$cat->nombre}}</option>
                                @else
                                <option  value="{{$cat->id}}">{{$cat->nombre}}</option>
                                @endif
                                @endforeach
                            </select> 
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>


                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" >Unidad de medida</label> 
                            <select name="id_unidad_medida" id="" class="form-select" required>
                                <option value="">...</option>
                                @foreach($Listadounidadesmedida as $umedida)
                                @if($pro->id_unidad_medida == $umedida->id)
                                <option selected value="{{$umedida->id}}">{{$umedida->nombre}}</option>
                                @else
                                <option  value="{{$umedida->id}}">{{$umedida->nombre}}</option>
                                @endif
                                @endforeach
                            </select> 
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                        <label class="form-label" for="form8Example4">Piezas por unidad de medida</label>
                            <input type="number"  class="form-control text" name="piezasxuni" id="piezasxuni" value ="{{$pro->piezasxunidmedida}}"   required/>
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>                      
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" for="form8Example4">Costo Unitario</label>
                            <input type="number" step="any" placeholder="00.00" name="precio_unitario" id="precio_unitario" class="form-control text" minlength="50" value = "{{$pro->precio_unitario}}"  maxlength="100"required />
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" >Costo Compra</label> 
                            <input type="number" step="any" placeholder="00.00"  name="costo_compra" id="costo_compra" class="form-control text"  value = "{{$pro->costo_compra}}"  minlength="3" maxlength="100"  required />  
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" >Costo venta</label> 
                            <input type="number" step="any" placeholder="00.00"  name="costo_venta" id="costo_venta" class="form-control text"  value = "{{$pro->costo_venta}}"  minlength="3"  maxlength="100"  required />  
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" >Existencia Minima</label> 
                            <input type="number" step="0.01" min="0" placeholder="0" name="minima_existencia" id="minima_existencia" class="form-control text" value="{{ $pro->minima_existencia }}" required />  
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>

                    <div class="col-md-6 col-12 mb-3">
                        <div class="form-outline">
                            <label class="form-label" >Existencia Maxima</label> 
                            <input type="number" step="0.01" min="0" placeholder="0" name="maxima_existencia" id="maxima_existencia" class="form-control text" value="{{ $pro->maxima_existencia }}" required />  
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-4 col-12 mb-3 p-3">
                        <div class="form-outline border rounded-1 p-3">
                            <label class="form-label" >Imagen 1</label> 
                            @php($foto1 = $pro->ruta_img1)

                            @if($pro->ruta_img1=='')
                            <center><img src="{{ asset('Images/Productos/producto.jpg') }}" class="rounded-3 shadow"style="height: 120px;width: 120px;object-fit: cover;" alt="foto_perfil"></center>
                            @else
                            <center><img src="{{ asset('Images/Productos/'.$pro->sku.'/'.$foto1) }}" class="rounded-3 shadow"style="height: 120px;width: 120px;object-fit: cover;" alt="foto_perfil"></center>
                            @endif
                            <br>

                            <input type="file"  name="ruta_img1" id="ruta_img1" class="form-control validaFOTO"   minlength="3" maxlength="100"   />
                            <input type="text" hidden name="ruta_img1old" value = "{{$pro->ruta_img1}}"> 
                            
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mb-3 p-3">
                        <div class="form-outline border rounded-1 p-3">
                            <label class="form-label" >Imagen 2</label> 
                            @php($foto2 = $pro->ruta_img2)
                            @if($pro->ruta_img2=='')
                            <center><img src="{{ asset('Images/Productos/producto.jpg') }}" class="rounded-3 shadow"style="height: 120px;width: 120px;object-fit: cover;" alt="foto_perfil"></center>
                            @else
                            <center><img src="{{ asset('Images/Productos/'.$pro->sku.'/'.$foto2) }}" class="rounded-3 shadow"style="height: 120px;width: 120px;object-fit: cover;" alt="foto_perfil"></center>
                            @endif
                            <br>  

                            <input type="file"  name="ruta_img2" id="ruta_img2" class="form-control validaFOTO"  value = "{{$pro->ruta_img2}}" minlength="3" maxlength="100"   />
                            <input type="text" hidden name="ruta_img2old" value = "{{$pro->ruta_img2}}"> 
                            
                            
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>

                    <div class="col-md-4 col-12 mb-3 p-3">
                        <div class="form-outline border rounded-1 p-3">
                            <label class="form-label" >Imagen 3</label> 
                            @php($foto3 = $pro->ruta_img3)
                            @if($pro->ruta_img3=='')
                            <center><img src="{{ asset('Images/Productos/producto.jpg') }}" class="rounded-3 shadow"style="height: 120px;width: 120px;object-fit: cover;" alt="foto_perfil"></center>
                            @else
                            <center><img src="{{ asset('Images/Productos/'.$pro->sku.'/'.$foto3) }}" class="rounded-3 shadow"style="height: 120px;width: 120px;object-fit: cover;" alt="foto_perfil"></center>
                            @endif
                            <br>

                            <input type="file"  name="ruta_img3" id="ruta_img3" class="form-control validaFOTO" value = "{{$pro->ruta_img3}}"  minlength="3" maxlength="100"   />  
                            <input type="text" hidden name="ruta_img3old" value = "{{$pro->ruta_img3}}"> 
                            
                            <div class="valid-feedback">¡Se ve bien!</div>
                            <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                        </div>
                    </div>
                </div>
                
                <div class="row justify-content-center mt-5 mb-5">
                    <button class="btn btn-baseColor fs-8 col-3" type="submit" id="btn">
                        <i class="fa-solid fa-check"></i>&nbsp; Guardar
                    </button>
                </div>
        </form>
        @endforeach
    </div>
</div>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
@endsection