@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid format_page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Clientes</h2>
                            <p class="text-muted mb-0">Administración de clientes</p>
                        </div>
                    </div>
                    <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalInsert">
                            <i class="fa-solid fa-plus"></i> Nuevo Cliente
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success border-0 rounded-3 mt-2">{{ session('success') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning border-0 rounded-3 mt-2">{{ session('warning') }}</div>
        @endif
        @if (session('warningBD'))
            <div class="alert alert-danger border-0 rounded-3 mt-2">{{ session('warningBD') }}</div>
        @endif

        <div class="row mt-3">
            <div class="table-responsive">
                <table class="table table-stripped table-hover display" id="table">
                    <thead>
                        <tr class="table-light text-secondary">
                            {{-- <th class="text-center text-truncate fw-bold">No.</th> --}}
                            <th class="text-truncate">Herramientas</th>
                            <th class="text-truncate">Razón Social</th>
                            <th class="text-truncate">Alias</th>
                            <th class="text-truncate">Persona de atención</th>
                            <th class="text-truncate">Estado</th>
                            <th class="text-truncate">Tipo</th>
                            
                            <th class="text-truncate">RFC</th>
                            <th class="text-truncate">Telefono</th>
                            <th class="text-truncate">Correo Electrónico</th>
                            <th class="text-truncate">Ciudad</th>
                            <th class="text-truncate">Dirección</th>
                            <th class="text-truncate">Creado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($Clientes as $item)
                            <tr>
                                {{-- <td class="fw-bold">{{ $item->id }}</td> --}}
                                <td class="text-truncate">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEdit{{$item->id}}">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </button>

                                    <button type="button" class="btn btn-baseColor" data-bs-toggle="modal" data-bs-target="#modalPersonasAtencion{{$item->id}}">
                                        <i class="fa-solid fa-users fs-8"></i>
                                    </button>

                                    <button type="button" data-bs-toggle="modal" data-bs-target="#modalEliminar{{$item->id}}"
                                        class="btn btn-danger"><i class="fa-solid fa-trash fs-8"></i>
                                    </button>
                                </td>
                                <td class="text-truncate">{{ $item->razon_social }}</td>
                                <td>{{ $item->alias}}</td>
                                <td class="text-wrap">{{ $item->nombre_atencion_display ?? $item->nombre }}</td>
                                
                                <td>
                                    @if ($item->estado == 'A')
                                        <span class="badge badge-success-dark fw-normal fs-9"> Activo</span>
                                    @elseif($item->estado == 'C')
                                        <span class="badge badge-secondary-dark fw-normal fs-9"> Cancelado</span>
                                        @elseif($item->estado == 'I')
                                        <span class="badge badge-danger-dark fw-normal fs-9"> INACTIVO</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->tipo == 'EXCELENTE')
                                        <span class="badge badge-primary fw-normal fs-9"> Excelente</span>
                                    @elseif($item->tipo == 'BUENO')
                                        <span class="badge badge-success fw-normal fs-9"> Bueno</span>
                                    @elseif($item->tipo == 'INTERMEDIO')
                                        <span class="badge badge-orange fw-normal fs-9"> Intermedio</span>
                                    @elseif($item->tipo == 'MALO')
                                        <span class="badge badge-danger fw-normal fs-9"> Malo</span>
                                    @elseif($item->tipo == 'PESIMO')
                                        <span class="badge badge-secondary fw-normal fs-9"> Pesimo</span>
                                    @endif
                                </td>
                               
                                <td>{{ $item->rfc }}</td>
                                <td>{{ $item->telefono }}</td>
                                <td>{{ $item->correo_electronico }}</td>
                                <td>{{ $item->nombre_ciudad }},{{ $item->nombre_estado }}</td>

                                <td>
                                    @if(!is_null($item->numero_int))
                                         @php($direccion = "Col. ".$item->colonia.", Calle ".$item->calle." No. Int ".$item->numero_int." No. Ext".$item->numero_ext.", C.P.".$item->cp)
                                    @else
                                         @php($direccion = "Col. ".$item->colonia.", Calle ".$item->calle." No. Ext".$item->numero_ext.", C.P.".$item->cp)
                                    @endif

                                    @if (strlen($direccion) > 20)
                                        {{ substr($direccion,0, 20) }} ...
                                    @else
                                        {{ $direccion }}
                                    @endif
                                </td>
                                <td>{{ $item->created_by }} {{date("d/m/Y h:i:sa", strtotime($item->created_at))}}</td>
                                
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach ($Clientes as $item)
        <!-- Modal confirmacion -->
        <div class="modal fade" id="modalEliminar{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="p-4 pt-0 pb-0 text-center">
                         <h5>¿Estás seguro de eliminar el proveedor <b class="fs-8">"{{$item->razon_social}}"</b>?</h5>
                        </div>
                    </div>

                    <div class="row justify-content-center mt-4 mb-3">
                        <a class="btn btn-baseColor fs-8 col-8" href="/Clientes/Eliminar/{{$item->id}}" >
                            <i class="fa-solid fa-check"></i> Aceptar
                        </a>
                    </div><br>
                </div>
            </div>
        </div>

         <!-- Editar Modal-->
        <div class="modal fade" id="modalEdit{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h4>Editar Cliente <b class="fs-8">{{$item->razon_social}}</b></h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form action="/Clientes/Editar/{{$item->id}}" method="POST" class="form g-3 needs-validation" novalidate>
                        @csrf
                        <div class="modal-body">
                            <div class="p-4 pt-3 pb-0">
                                <div class="row">
                                    <div class="form-outline col-md-5 col-12">
                                        <label class="form-label" for="persona_atencion_edit_{{$item->id}}">Persona de atención</label>
                                        <input type="text" class="form-control text" id="persona_atencion_edit_{{$item->id}}"
                                            name="nombre" value="{{$item->nombre}}" required
                                            placeholder="Nombre de quien recibe cotizaciones" />
                                        <div class="form-text fs-9">Se carga sola en cotización y pedidos.</div>
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>
                                
                                    <div class="form-outline col-md-4 col-6">
                                        <label class="form-label">Alias</label>
                                        <input type="text" name="alias" value="{{$item->alias}}" class="form-control" required maxlength="50" />
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="form-outline col-md-3 col-6">
                                        <label class="form-label">Tipo</label>
                                        <select id="tipo" name="tipo" class="select-form" required>
                                            <option value="{{$item->tipo}}" selected>{{$item->tipo}}</option>
                                            <option value="EXCELENTE">EXCELENTE</option>
                                            <option value="BUENO">BUENO</option>
                                            <option value="INTERMEDIO">INTERMEDIO</option>
                                            <option value="MALO">MALO</option>
                                            <option value="PESIMO">PESIMO</option>
                                        </select>
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-outline col-md-6 col-12">
                                        <label class="form-label">Razon Social</label>
                                        <input type="text" id="razon_social" name="razon_social" value="{{$item->razon_social}}" maxlength="1000" class="form-control text" required />
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="form-outline col-md-6 col-12">
                                        <label class="form-label">RFC</label>
                                        <input type="text" value="{{$item->rfc}}" name="rfc" minlength="13" maxlength="13" class="form-control text" required />
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>
                                </div>
                                    
                                <div class="row">
                                    <div class="form-outline col-md-6 col-12">
                                        <label class="form-label">Telefono</label>
                                        <input type="text"  value="{{$item->telefono}}" name="telefono" class="form-control" maxlength="10" required />
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="form-outline col-md-6 col-12">
                                        <label class="form-label">Correo Electrónico</label>
                                        <input type="text"  value="{{$item->correo_electronico}}" name="correo_electronico" class="form-control" required />
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-outline col-md-4 col-12">
                                        <label class="form-label">Ciudad</label>
                                        <select id="id_ciudad" name="id_ciudad" class="select-form" required>
                                            <option value="{{$item->id_ciudad}}" selected>{{$item->nombre_ciudad}}</option>
                                            @foreach($Ciudades as $key)
                                            <option value="{{$key->id}}">{{$key->nombre}}</option>
                                            @endforeach
                                        </select>
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>
            
                                    <div class="form-outline col-md-4 col-6">
                                        <label class="form-label">Colonia </label>
                                        <input type="text" value="{{$item->colonia}}" name="colonia" maxlength="50" class="form-control" required />
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="form-outline col-md-4 col-6">
                                        <label class="form-label">Calle </label>
                                        <input type="text" value="{{$item->calle}}" name="calle" maxlength="50" class="form-control" required />
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-outline col-md-4 col-6">
                                        <label class="form-label">Numero Externo </label>
                                        <input type="text" value="{{$item->numero_ext}}" name="numero_ext" maxlength="10" class="form-control" required />
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="form-outline col-md-4 col-6">
                                        <label class="form-label">Numero Interno </label>
                                        <input type="text" value="{{$item->numero_int}}" name="numero_int" maxlength="10" class="form-control" />
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>

                                    <div class="form-outline col-md-4 col-12">
                                        <label class="form-label">C. P. </label>
                                        <input type="text" value="{{$item->cp}}" name="cp" maxlength="10" class="form-control" required />
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>
                                    <div class="form-outline col-md-4 col-12">
                                        <label class="form-label">Moneda</label>
                                        <select name="moneda_id" class="form-select" required>
                                            @foreach (($Monedas ?? []) as $moneda)
                                                <option value="{{ $moneda->id }}" {{ (int) ($item->moneda_id ?? 0) === (int) $moneda->id ? 'selected' : '' }}>
                                                    {{ $moneda->abreviacion }} — {{ $moneda->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-outline col-md-4 col-12">
                                        <label class="form-label">Condiciones de pago</label>
                                        <select name="condicion_pago_id" class="form-select">
                                            <option value="">— Sin definir —</option>
                                            @foreach (($CondicionesPago ?? []) as $condicion)
                                                <option value="{{ $condicion->id }}" {{ (int) ($item->condicion_pago_id ?? 0) === (int) $condicion->id ? 'selected' : '' }}>
                                                    {{ $condicion->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row justify-content-center">
                                    <div class="form-outline col-md-4 col-12">
                                        <label class="form-label">Estado</label>
                                        <select id="estado" name="estado" class="select-form" required>
                                            @if($item->estado == "A")
                                                <option value="A" selected>ACTIVO</option>
                                                <option value="I">INACTIVO</option>
                                                <option value="C">CANCELADO</option>

                                            @elseif($item->estado == "I")
                                                <option value="A">ACTIVO</option>
                                                <option value="I" selected>INACTIVO</option>
                                                <option value="C">CANCELADO</option>

                                            @elseif($item->estado == "C")
                                                <option value="A">ACTIVO</option>
                                                <option value="I">INACTIVO</option>
                                                <option value="C" selected>CANCELADO</option>
                                            @endif
                                        </select>
                                        <div class="valid-feedback"> ¡Se ve bien!</div>
                                        <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                    </div>
                                </div>

                                <div class="alert alert-light border rounded-3 py-2 px-3 mt-3 mb-0">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="fs-8 text-secondary">
                                            <i class="fa-solid fa-user-tie me-1"></i>
                                            Contactos adicionales (teléfono/correo) para este cliente.
                                        </div>
                                        <button type="button" class="btn btn-blue-light btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#modalPersonasAtencion{{$item->id}}">
                                            <i class="fa-solid fa-users"></i> Personas de atención
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Guardar -->
                        <div class="row justify-content-center mt-3 mb-4">
                            <button class="btn btn-baseColor fs-8 col-4" type="submit"><i
                                    class="fa-solid fa-check"></i> Guardar
                            </button>
                        </div>
                    </form>
               </div>
           </div>
       </div>

        <!-- Modal Personas de Atención -->
        <div class="modal fade" id="modalPersonasAtencion{{$item->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h4>Personas de Atención <b class="fs-8">{{$item->razon_social}}</b></h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="p-4 pt-0 pb-5">
                            <!-- Formulario para agregar nueva persona -->
                            <div class="card mb-3 mt-0">
                                <div class="card-header">
                                    <h6 class="mb-0">Agregar Nueva Persona de Atención</h6>
                                </div>
                                <div class="card-body">
                                    <form action="/Clientes/InsertarPersonaAtencion" method="POST" class="form g-3 needs-validation" novalidate id="formPersonasAtencion{{$item->id}}">
                                        @csrf
                                        <input type="hidden" name="id_cliente" value="{{$item->id}}">
                                        
                                        <div class="row">
                                            <div class="form-outline col-md-3 col-6">
                                                <label class="form-label">Primer Nombre</label>
                                                <input type="text" name="primer_nombre" class="form-control" required />
                                                <div class="valid-feedback">¡Se ve bien!</div>
                                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                            </div>
                                            
                                            <div class="form-outline col-md-3 col-6">
                                                <label class="form-label">Segundo Nombre</label>
                                                <input type="text" name="segundo_nombre" class="form-control" />
                                            </div>
                                            
                                            <div class="form-outline col-md-3 col-6">
                                                <label class="form-label">Apellido Paterno</label>
                                                <input type="text" name="apellido_paterno" class="form-control" required />
                                                <div class="valid-feedback">¡Se ve bien!</div>
                                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                            </div>
                                            
                                            <div class="form-outline col-md-3 col-6">
                                                <label class="form-label">Apellido Materno</label>
                                                <input type="text" name="apellido_materno" class="form-control" />
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="form-outline col-md-6 col-12">
                                                <label class="form-label">Teléfono</label>
                                                <input type="text" name="telefono" class="form-control" maxlength="10" required />
                                                <div class="valid-feedback">¡Se ve bien!</div>
                                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                            </div>
                                            
                                            <div class="form-outline col-md-6 col-12">
                                                <label class="form-label">Correo Electrónico</label>
                                                <input type="email" name="correo" class="form-control" required />
                                                <div class="valid-feedback">¡Se ve bien!</div>
                                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                            </div>
                                        </div>
                                        
                                        <div class="row justify-content-center mt-3">
                                            <button class="btn btn-baseColor fs-8 col-3" type="submit">
                                                <i class="fa-solid fa-plus"></i> Agregar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Tabla de personas de atención -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Personas de Atención Registradas</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="tablePersonas{{$item->id}}">
                                            <thead>
                                                <tr>
                                                    <th>Nombre Completo</th>
                                                    <th>Teléfono</th>
                                                    <th>Correo</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbodyPersonas{{$item->id}}">
                                                <!-- Las personas se cargarán dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Modal Editar Persona de Atención de Cliente -->
    <div class="modal fade" id="modalEditarPersonaAtencionCliente" tabindex="-1" aria-labelledby="editarPersonaAtencionClienteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="editarPersonaAtencionClienteModalLabel">
                         Editar Persona de Atención
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" class="needs-validation" novalidate id="formEditarPersonaAtencionCliente">
                    @csrf
                    <input type="hidden" name="persona_id" id="persona_id_edit_cliente" value="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="primer_nombre_edit_cliente" class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="primer_nombre" id="primer_nombre_edit_cliente" 
                                       required maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                <div class="invalid-feedback">
                                    Por favor, ingrese el primer nombre.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="segundo_nombre_edit_cliente" class="form-label">Segundo Nombre</label>
                                <input type="text" class="form-control text-uppercase" name="segundo_nombre" id="segundo_nombre_edit_cliente" 
                                       maxlength="50" oninput="this.value = this.value.toUpperCase()">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="apellido_paterno_edit_cliente" class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" name="apellido_paterno" id="apellido_paterno_edit_cliente" 
                                       required maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                <div class="invalid-feedback">
                                    Por favor, ingrese el apellido paterno.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="apellido_materno_edit_cliente" class="form-label">Apellido Materno</label>
                                <input type="text" class="form-control text-uppercase" name="apellido_materno" id="apellido_materno_edit_cliente" 
                                       maxlength="50" oninput="this.value = this.value.toUpperCase()">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefono_edit_cliente" class="form-label">Teléfono <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="telefono" id="telefono_edit_cliente" 
                                       maxlength="10" required>
                                <div class="invalid-feedback">
                                    Por favor, ingrese el teléfono.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="correo_edit_cliente" class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="correo" id="correo_edit_cliente" required>
                                <div class="invalid-feedback">
                                    Por favor, ingrese el correo electrónico.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-danger fs-9" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cancelar</button>
                        <button type="submit" class="btn btn-warning fs-9">
                            <i class="fa-solid fa-save me-1"></i> Actualizar Persona
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Insertar Modal-->
    <div class="modal fade" id="modalInsert" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h4>Nuevo Cliente</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="/Clientes/Insertar" method="POST" class="form g-3 needs-validation" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="p-4 pt-0 pb-0">
                            <div class="row">
                                <div class="form-outline col-md-5 col-12">
                                    <label class="form-label" for="nombre">Persona de atención</label>
                                    <input type="text" class="form-control text" name="nombre" id="nombre" required
                                        placeholder="Nombre de quien recibe cotizaciones" />
                                    <div class="form-text fs-9">Se carga sola en cotización y pedidos.</div>
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>
                            
                                <div class="form-outline col-md-4 col-6">
                                    <label class="form-label">Alias</label>
                                    <input type="text" id="alias" name="alias" class="form-control" required maxlength="50" />
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline col-md-3 col-6">
                                    <label class="form-label">Tipo</label>
                                    <select id="tipo" name="tipo" class="select-form" required>
                                        <option value="" selected>...</option>
                                        <option value="EXCELENTE">EXCELENTE</option>
                                        <option value="BUENO">BUENO</option>
                                        <option value="INTERMEDIO">INTERMEDIO</option>
                                        <option value="MALO">MALO</option>
                                        <option value="PESIMO">PESIMO</option>
                                    </select>
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-outline col-md-6 col-12">
                                    <label class="form-label">Razon Social</label>
                                    <input type="text" id="razon_social" name="razon_social" maxlength="1000" class="form-control text" required />
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline col-md-6 col-12">
                                    <label class="form-label">RFC</label>
                                    <input type="text" id="rfc" name="rfc" minlength="13" maxlength="13" class="form-control text" required />
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                                
                            <div class="row">
                                <div class="form-outline col-md-6 col-12">
                                    <label class="form-label">Telefono</label>
                                    <input type="text" id="telefono" name="telefono" class="form-control" maxlength="10" required />
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline col-md-6 col-12">
                                    <label class="form-label">Correo Electrónico</label>
                                    <input type="text" id="correo_electronico" name="correo_electronico" class="form-control" required />
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-outline col-md-4 col-12">
                                    <label class="form-label">Ciudad</label>
                                    <select id="id_ciudad" name="id_ciudad" class="select-form" required>
                                         <option value="" selected>...</option>
                                         @foreach($Ciudades as $key)
                                         <option value="{{$key->id}}">{{$key->nombre}}</option>
                                         @endforeach
                                    </select>
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>
        
                                <div class="form-outline col-md-4 col-6">
                                    <label class="form-label">Colonia </label>
                                    <input type="text" id="colonia" name="colonia" maxlength="50" class="form-control" required />
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline col-md-4 col-6">
                                    <label class="form-label">Calle </label>
                                    <input type="text" id="calle" name="calle" maxlength="50" class="form-control" required />
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="form-outline col-md-4 col-6">
                                    <label class="form-label">Numero Externo </label>
                                    <input type="text" id="numero_ext" name="numero_ext" maxlength="10" class="form-control" required />
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline col-md-4 col-6">
                                    <label class="form-label">Numero Interno </label>
                                    <input type="text" id="numero_int" name="numero_int" maxlength="10" class="form-control" />
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>

                                <div class="form-outline col-md-4 col-12">
                                    <label class="form-label">C. P. </label>
                                    <input type="text" id="cp" name="cp" maxlength="10" class="form-control" required />
                                    <div class="valid-feedback"> ¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>
                                <div class="form-outline col-md-4 col-12">
                                    <label class="form-label">Moneda</label>
                                    <select name="moneda_id" class="form-select" required>
                                        @foreach (($Monedas ?? []) as $moneda)
                                            <option value="{{ $moneda->id }}" {{ $moneda->codigo === 'MXN' ? 'selected' : '' }}>
                                                {{ $moneda->abreviacion }} — {{ $moneda->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-outline col-md-4 col-12">
                                    <label class="form-label">Condiciones de pago</label>
                                    <select name="condicion_pago_id" class="form-select">
                                        <option value="">— Sin definir —</option>
                                        @foreach (($CondicionesPago ?? []) as $condicion)
                                            <option value="{{ $condicion->id }}">{{ $condicion->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                     <!-- Guardar -->
                     <div class="row justify-content-center mt-3 mb-4">
                        <button class="btn btn-baseColor fs-8 col-5" type="submit"><i
                                class="fa-solid fa-check"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
    <script src="{{ asset('js/personas-atencion.js') }}"></script>

    <script>
        // Manejar el envío del formulario de editar persona de atención
        document.addEventListener('DOMContentLoaded', function() {
            const formEditar = document.getElementById('formEditarPersonaAtencionCliente');
            if (formEditar) {
                formEditar.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    
                    // Validar formulario
                    if (!this.checkValidity()) {
                        this.classList.add('was-validated');
                        return false;
                    }
                    
                    // Enviar formulario
                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        }
                    })
                    .then(data => {
                        // Mostrar mensaje de éxito
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Éxito',
                                text: data.message || 'Persona de atención actualizada correctamente',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Cerrar modal y recargar lista
                                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarPersonaAtencionCliente'));
                                if (modal) {
                                    modal.hide();
                                }
                                // Recargar la lista de personas de atención
                                location.reload();
                            });
                        } else {
                            alert(data.message || 'Persona de atención actualizada correctamente');
                            // Cerrar modal y recargar lista
                            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarPersonaAtencionCliente'));
                            if (modal) {
                                modal.hide();
                            }
                            // Recargar la lista de personas de atención
                            location.reload();
                        }
                    })
                    .catch(error => {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: `Error al actualizar persona de atención: ${error.message}`
                            });
                        } else {
                            alert(`Error al actualizar persona de atención: ${error.message}`);
                        }
                    });
                });
            }
        });
    </script>
@endsection
