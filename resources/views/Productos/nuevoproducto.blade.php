@extends('layouts.app')
@section('content')  

@if($mensaje = Session::get('PDFwarning'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "warning",title: "¡Seleccione el archivo correcto!", text: "El formato permitido de archivos admitido es .pdf"});';
            echo '</script>'; 
    @endphp
@elseif($mensaje = Session::get('Errorskuocodb'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "info",title: "¡Existen Productos con el misnmos Sku o Codigo de barras!", text: "Favor de revisar bien los datos"});';
            echo '</script>'; 
    @endphp
    @elseif($mensaje = Session::get('error'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}});';
            echo 'Toast.fire({ icon: "info",title: "¡Ocurrio un error al regisrar el producto!", text: "Favor de revisar bien los datos"});';
            echo '</script>'; 
    @endphp
@endif
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="/productos" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Productos
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">
                            Productos <i class="fa-solid fa-chevron-right fs-6"></i> Nuevo
                        </h2>
                        <p class="text-muted mb-0">Registro de un nuevo producto</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-4 pb-0 bg-body rounded">
        <form class="needs-validation msform" method="post" action="{{route('minsertapro')}}" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="row">
                <div class="col-md-4 col-12 mb-3">
                    <div class="form-outline">
                    <label class="form-label" for="form8Example4">SKU</label>
                        <input type="text"  class="form-control text" name="sku" id="sku" minlength="3" maxlength="100"  required />
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>                      
                    </div>
                </div>

                <div class="col-md-4 col-12 mb-3">
                    <div class="form-outline">
                    <label class="form-label" for="form8Example4">Codigo Barras</label>
                        <input type="number"  class="form-control text" name="codigo_barras" id="codigo_barras" minlength="3" maxlength="100"  required />
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>                      
                    </div>
                </div>

                <div class="col-md-4 col-12 mb-3">
                    <div class="form-outline">
                        <label class="form-label" >Proveedor</label> 
                        <select name="id_proveedor" id="" class="form-control">
                        <option selected value="0">Por definir</option>
                        @foreach($listadoprovedores as $ltsprov)
                        <option value="{{$ltsprov->id}}">{{$ltsprov->nombre}}</option>
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
                        <label class="form-label">Nombre Producto</label>
                        <input type="text"  name="nombre" id="nombre" class="form-control text" minlength="3" maxlength="60"   required />
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>

                <div class="col-md-8 col-12 mb-3">
                    <div class="form-outline">
                    <label class="form-label" for="descripcion">Descripción <span class="text-danger">*</span></label>
                        <textarea class="form-control text @error('descripcion') is-invalid @enderror" name="descripcion" id="descripcion" minlength="3" maxlength="100" required>{{ old('descripcion') }}</textarea>
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
                        <label class="form-label" for="id_categoria_select">Categoría</label>
                        <div class="d-flex gap-2 align-items-stretch flex-wrap">
                            <select name="id_categoria" id="id_categoria_select" class="form-select flex-grow-1" required>
                                <option value="">...</option>
                                @foreach($Listacategoriaproductos as $cat)
                                <option value="{{$cat->id}}">{{$cat->nombre}}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary text-nowrap" id="btnAgregarCategoriaProducto" title="Registrar una nueva categoría">
                                <i class="fa-solid fa-plus"></i> Agregar categoría
                            </button>
                        </div>
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
                            <option value="{{$umedida->id}}">{{$umedida->nombre}}</option>
                            @endforeach
                        </select> 
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>

                <div class="col-md-4 col-12 mb-3">
                    <div class="form-outline">
                    <label class="form-label" for="form8Example4">Piezas por unidad de medida</label>
                        <input type="number"  class="form-control text" name="piezasxuni" id="piezasxuni" required/>
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>                      
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 col-12 mb-3">
                    <div class="form-outline">
                        <label class="form-label" for="form8Example4">Costo Unitario</label>
                        <input type="number" step="any" name="precio_unitario" id="precio_unitario" class="form-control text" required/>
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>

                <div class="col-md-4 col-12 mb-3">
                    <div class="form-outline">
                        <label class="form-label" >Costo Compra</label> 
                        <input type="number"  step="any" name="costo_compra" id="costo_compra" class="form-control text"   minlength="3" maxlength="100"  required />  
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>

                <div class="col-md-4 col-12 mb-3">
                    <div class="form-outline">
                        <label class="form-label" >Costo venta</label> 
                        <input type="number"  step="any" name="costo_venta" id="costo_venta" class="form-control text"   minlength="3" maxlength="100"  required />  
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 col-12 mb-3">
                    <div class="form-outline">
                        <label class="form-label" >Existencia Minima</label> 
                        <input type="number" step="0.01" min="0" placeholder="0" name="minima_existencia" id="minima_existencia" class="form-control text" required />  
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>

                <div class="col-md-6 col-12 mb-3">
                    <div class="form-outline">
                        <label class="form-label" >Existencia Maxima</label> 
                        <input type="number" step="0.01" min="0" placeholder="0" name="maxima_existencia" id="maxima_existencia" class="form-control text" required />  
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 col-12 mb-3">
                    <div class="form-outline">
                        <label class="form-label" >Imagen 1</label> 
                        <input type="file"  name="ruta_img1" id="ruta_img1" class="form-control validaFOTO"   minlength="3" maxlength="100"   />  
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>

                <div class="col-md-4 col-12 mb-3">
                    <div class="form-outline">
                        <label class="form-label" >Imagen 2</label> 
                        <input type="file"  name="ruta_img2" id="ruta_img2" class="form-control validaFOTO"   minlength="3" maxlength="100"   />  
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>

                <div class="col-md-4 col-12 mb-3">
                    <div class="form-outline">
                        <label class="form-label" >Imagen 3</label> 
                        <input type="file"  name="ruta_img3" id="ruta_img3" class="form-control validaFOTO"   minlength="3" maxlength="100"   />  
                        <div class="valid-feedback">¡Se ve bien!</div>
                        <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center mt-3 mb-4">
                <button class="btn btn-baseColor fs-8 col-3" type="submit" id="btn">
                    <i class="fa-solid fa-check"></i>&nbsp; Guardar
                </button>
            </div>
        </form>
    </div>
</div>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btnCat = document.getElementById('btnAgregarCategoriaProducto');
    var selCat = document.getElementById('id_categoria_select');
    var urlStore = @json(route('productos.categoria.store'));
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    var csrf = tokenMeta ? tokenMeta.getAttribute('content') : '';

    if (!btnCat || !selCat) {
        return;
    }

    btnCat.addEventListener('click', function () {
        if (typeof Swal === 'undefined') {
            var n = window.prompt('Nombre de la nueva categoría:');
            if (!n || !String(n).trim()) {
                return;
            }
            guardarCategoriaProducto(String(n).trim());
            return;
        }
        Swal.fire({
            title: 'Nueva categoría',
            input: 'text',
            inputLabel: 'Nombre',
            inputPlaceholder: 'Ej. Ferretería',
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#fd7e14',
            inputValidator: function (value) {
                if (!value || !String(value).trim()) {
                    return 'Escribe un nombre';
                }
            }
        }).then(function (result) {
            if (result.isConfirmed && result.value) {
                guardarCategoriaProducto(String(result.value).trim());
            }
        });
    });

    function guardarCategoriaProducto(nombre) {
        fetch(urlStore, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ nombre: nombre })
        }).then(function (r) {
            return r.json().then(function (j) {
                return { ok: r.ok, status: r.status, body: j };
            });
        }).then(function (res) {
            if (!res.ok) {
                var msg = (res.body && res.body.message) ? res.body.message : 'No se pudo guardar.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: msg });
                } else {
                    window.alert(msg);
                }
                return;
            }
            var id = res.body.id;
            var nom = res.body.nombre || nombre;
            var existeOpt = selCat.querySelector('option[value="' + String(id) + '"]');
            if (!existeOpt) {
                var opt = document.createElement('option');
                opt.value = String(id);
                opt.textContent = nom;
                selCat.appendChild(opt);
            }
            selCat.value = String(id);
            selCat.dispatchEvent(new Event('change', { bubbles: true }));
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Listo', text: res.body.message || 'Categoría agregada.', timer: 2000, showConfirmButton: false });
            }
        }).catch(function () {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error de red', text: 'Intenta de nuevo.' });
            } else {
                window.alert('Error de red.');
            }
        });
    }
});
</script>
@endsection