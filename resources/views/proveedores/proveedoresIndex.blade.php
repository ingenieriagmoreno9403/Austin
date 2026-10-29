@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid format_page">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-truck-field"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Proveedores</h2>
                            <p class="text-muted mb-0">Administración de proveedores</p>
                        </div>
                    </div>
                    <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#modalInsert">
                            <i class="fa-solid fa-plus"></i> Nuevo Proveedor
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if ((isset($alertasContratos) && $alertasContratos->isNotEmpty()) || (isset($alertasDocumentos) && $alertasDocumentos->isNotEmpty()))
            <div class="alert alert-warning">
                <div class="fw-bold mb-2">Vigencias por vencer o vencidas</div>
                @foreach ($alertasContratos ?? [] as $alerta)
                    <div>
                        Contrato de {{ $alerta->nombre }}:
                        {{ \Carbon\Carbon::parse($alerta->fecha_fin ?? $alerta->contrato_fin)->format('d/m/Y') }}
                        <a href="/editar_proveedor/{{ $alerta->id }}">Abrir ficha</a>
                    </div>
                @endforeach
                @foreach ($alertasDocumentos ?? [] as $alerta)
                    <div>
                        {{ $alerta->nombre }} de {{ $alerta->proveedor }}:
                        {{ \Carbon\Carbon::parse($alerta->vigencia)->format('d/m/Y') }}
                        <a href="/editar_proveedor/{{ $alerta->proveedor_id }}">Abrir ficha</a>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="row mt-3">
            <div class="table-responsive">
                <table class="table table-stripped table-hover display" id="table">
                    <thead>
                        <tr class="table-light text-secondary">
                            {{-- <th class="text-center text-truncate fw-bold">No.</th> --}}
                            <th class="text-center text-truncate fw-bold">Nombre</th>
                            <th class="text-center text-truncate fw-bold">Telefono</th>
                            <th class="text-center text-truncate fw-bold">Direccion</th>
                            <th class="text-center text-truncate fw-bold">Estado</th>
                            <th class="text-center text-truncate fw-bold">Homologación</th>
                            <th class="text-center text-truncate fw-bold">Categoría</th>
                            <th class="text-center text-truncate fw-bold">Alias</th>
                            <th class="text-center text-truncate fw-bold">RFC</th>
                            <th class="text-center text-truncate fw-bold">Modo de Pago</th>
                            <th class="text-center text-truncate fw-bold">Herramientas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proveedores as $ltsproveedores)
                            <tr>
                                {{-- <td class="fw-bold">{{ $ltsproveedores->id }}</td> --}}
                                <td>{{ $ltsproveedores->nombre }}</td>
                                <td>{{ $ltsproveedores->telefono}}</td>
                                <td>{{ $ltsproveedores->direccion }}</td>
                                <td>
                                    @if ($ltsproveedores->estado == 'A')
                                        <span class="badge badge-success fw-normal fs-9"> Activo</span>
                                    @elseif($ltsproveedores->estado == 'C')
                                        <span class="badge badge-secondary fw-normal fs-9"> Cancelado</span>
                                        @elseif($ltsproveedores->estado == 'I')
                                        <span class="badge badge-danger fw-normal fs-9"> Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php $homologacion = \App\Support\HomologacionProveedor::normalizar($ltsproveedores->homologacion ?? ''); @endphp
                                    <span class="badge {{ in_array($homologacion, ['suspendido', 'bloqueado'], true) ? 'bg-danger' : ($homologacion === 'condicionado' ? 'bg-warning text-dark' : ($homologacion === 'aprobado' ? 'bg-success' : 'bg-secondary')) }}">
                                        {{ \App\Support\HomologacionProveedor::etiqueta($homologacion) }}
                                    </span>
                                    @if (!empty($ltsproveedores->preferido))
                                        <span class="badge bg-primary">Preferido</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{ optional(collect($categorias ?? [])->firstWhere('id', $ltsproveedores->categoria_id ?? null))->nombre ?: '—' }}
                                </td>
                                <td>{{ $ltsproveedores->nit }}</td>
                                <td>{{ $ltsproveedores->rfc }}</td>
                                <td class="text-center">
                                    @if($ltsproveedores->modo_pago == 'credito')
                                        <span class="badge bg-warning text-dark">Crédito</span>
                                        <b class="fs-10">{{ $ltsproveedores->dias_credito }} días</b>
                                    @else
                                        <span class="badge bg-success">Contado</span>
                                    @endif
                                </td>
                               
                                <td class="text-truncate">
                                    <a href="/editar_proveedor/{{$ltsproveedores->id}}"
                                        class="btn btn-primary" title="Editar proveedor">
                                        <i class="fa-solid fa-pen fs-8"></i>
                                    </a>

                                    <button type="button" data-bs-toggle="modal" data-bs-target="#PersonasAtencion{{$ltsproveedores->id}}"
                                        class="btn btn-baseColor" title="Ver personas de atención">
                                        <i class="fa-solid fa-users fs-8"></i>
                                    </button>

                                    <a href="/ProductosProveedor/{{$ltsproveedores->id}}"
                                        class="btn btn-success" title="Productos del proveedor">
                                        <i class="fa-solid fa-bag-shopping fs-8"></i>
                                    </a>

                                    

                                    <button type="button" data-bs-toggle="modal" data-bs-target="#Eliminar{{$ltsproveedores->id}}"
                                        class="btn btn-danger" title="Eliminar proveedor">
                                        <i class="fa-solid fa-trash fs-8"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @foreach ($proveedores as $ltsproveedores)
        <!-- Modal confirmacion -->
        <div class="modal fade" id="Eliminar{{$ltsproveedores->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="p-4 pt-0 pb-0 text-center">
                         <h5>¿Estás seguro de eliminar el proveedor?</h5>
                        </div>
                    </div>

                    <div class="row justify-content-center mt-4 mb-3">
                        <a class="btn btn-baseColor fs-8 col-8" href="/EliminarProveedor/{{$ltsproveedores->id}}" >
                            <i class="fa-solid fa-check"></i> Aceptar
                        </a>
                    </div><br>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($proveedores as $ltsproveedores)
        <!-- Modal Personas de Atención -->
        <div class="modal fade" id="PersonasAtencion{{$ltsproveedores->id}}" tabindex="-1" aria-labelledby="personasAtencionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="personasAtencionModalLabel">
                            <i class="fa-solid fa-users me-2"></i>
                             Personas de Atención  <b class="fs-9">{{ $ltsproveedores->nombre }}</b>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $personasAtencion = \App\Models\ProveedoresAtencion::where('id_proveedor', $ltsproveedores->id)->get();
                        @endphp
                        
                        @if($personasAtencion->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nombre Completo</th>
                                            <th>Puesto</th>
                                            {{-- <th class="text-center">Acciones</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($personasAtencion as $persona)
                                            <tr data-persona-id="{{ $persona->id }}">
                                                <td>
                                                    <strong>{{ strtoupper($persona->primer_nombre) }}
                                                    @if($persona->segundo_nombre)
                                                        {{ strtoupper($persona->segundo_nombre) }}
                                                    @endif
                                                    {{ strtoupper($persona->apellido_paterno) }}
                                                    @if($persona->apellido_materno)
                                                        {{ strtoupper($persona->apellido_materno) }}
                                                    @endif</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">{{ strtoupper($persona->puesto) }}</span>
                                                </td>
                                                {{-- <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="eliminarPersonaAtencion({{ $persona->id }}, '{{ $persona->primer_nombre }} {{ $persona->apellido_paterno }}')" 
                                                            title="Eliminar persona de atención">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td> --}}
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fa-solid fa-users fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No hay personas de atención registradas</h6>
                                <p class="text-muted">Este proveedor aún no tiene personas de atención asignadas.</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-danger fs-9" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
{{-- 
                        <button type="button" class="btn btn-success fs-9" onclick="mostrarFormularioNuevaPersona({{ $ltsproveedores->id }})">
                            <i class="fa-solid fa-plus me-1"></i> Agregar Nueva Persona
                        </button> --}}
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($proveedores as $ltsproveedores)
        <!-- Modal Formulario Nueva Persona de Atención -->
        {{-- <div class="modal fade" id="NuevaPersonaAtencion{{$ltsproveedores->id}}" tabindex="-1" aria-labelledby="nuevaPersonaAtencionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="nuevaPersonaAtencionModalLabel">
                            Nueva Persona de Atención  <b class="fs-9">{{ $ltsproveedores->nombre }}</b>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('insertarPersonaAtencion') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <input type="hidden" name="id_proveedor" value="{{ $ltsproveedores->id }}">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="primer_nombre" class="form-label">Primer Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" name="primer_nombre" id="primer_nombre" 
                                           required maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                    <div class="invalid-feedback">
                                        Por favor, ingrese el primer nombre.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="segundo_nombre" class="form-label">Segundo Nombre</label>
                                    <input type="text" class="form-control text-uppercase" name="segundo_nombre" id="segundo_nombre" 
                                           maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="apellido_paterno" class="form-label">Apellido Paterno <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" name="apellido_paterno" id="apellido_paterno" 
                                           required maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                    <div class="invalid-feedback">
                                        Por favor, ingrese el apellido paterno.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellido_materno" class="form-label">Apellido Materno</label>
                                    <input type="text" class="form-control text-uppercase" name="apellido_materno" id="apellido_materno" required
                                           maxlength="50" oninput="this.value = this.value.toUpperCase()">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="puesto" class="form-label">Puesto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control text-uppercase" name="puesto" id="puesto" 
                                           required maxlength="100" oninput="this.value = this.value.toUpperCase()">
                                    <div class="invalid-feedback">
                                        Por favor, ingrese el puesto.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-outline-danger fs-9" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cancelar</button>

                            <button type="submit" class="btn btn-success fs-9">
                                <i class="fa-solid fa-check me-1"></i> Guardar Persona
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div> --}}
    @endforeach

    <!-- Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title text-dark fs-5" id="exampleModalLabel">Eliminar nomina</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 class="text-dark">¿Está seguro que desea eliminar esta nomina?</h6>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger fs-8" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark"></i> Cerrar
                    </button>
                    
                </div>
            </div>
        </div>
    </div> 
    

    <style>
        #modalInsert .modal-dialog {
            max-width: 980px;
            height: calc(100% - 2rem);
            margin: 1rem auto;
        }
        #modalInsert .np-shell {
            display: flex;
            flex-direction: column;
            height: 100%;
            max-height: calc(100vh - 2rem);
            overflow: hidden;
            border: 0;
            border-radius: 22px;
            background: #f3f4f6;
            box-shadow: 0 28px 70px rgba(17, 24, 39, .22);
        }
        #modalInsert .np-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.15rem 1.35rem;
            color: #fff;
            background: linear-gradient(135deg, #030712 0%, #111827 62%, #374151 100%);
        }
        #modalInsert .np-head h4 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -.02em;
        }
        #modalInsert .np-head p {
            margin: .15rem 0 0;
            color: rgba(255,255,255,.72);
            font-size: .82rem;
        }
        #modalInsert .np-head .btn-close {
            filter: invert(1);
            opacity: .85;
        }
        #modalInsert .np-form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
        }
        #modalInsert .modal-body {
            overflow-y: auto;
            flex: 1 1 auto;
            min-height: 0;
            padding: 1.15rem;
        }
        #modalInsert .modal-body::-webkit-scrollbar { width: 10px; }
        #modalInsert .modal-body::-webkit-scrollbar-thumb {
            background: #c5c9d1;
            border-radius: 999px;
            border: 2px solid #f3f4f6;
        }
        #modalInsert .np-card {
            background: #fff;
            border: 1px solid rgba(17, 24, 39, .08);
            border-radius: 16px;
            padding: 1rem 1rem .35rem;
            margin-bottom: .85rem;
            box-shadow: 0 1px 2px rgba(17, 24, 39, .04);
        }
        #modalInsert .np-card h6 {
            margin: 0 0 .85rem;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #6b7280;
        }
        #modalInsert .form-label {
            font-size: .78rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: .3rem;
        }
        #modalInsert .form-control,
        #modalInsert .form-select {
            border-radius: 10px;
            border-color: #e5e7eb;
            min-height: 42px;
            background: #fafafa;
        }
        #modalInsert .form-control:focus,
        #modalInsert .form-select:focus {
            background: #fff;
            border-color: #111827;
            box-shadow: 0 0 0 .2rem rgba(17, 24, 39, .08);
        }
        #modalInsert .np-note {
            margin: -.35rem 0 .85rem;
            color: #6b7280;
            font-size: .78rem;
        }
        #modalInsert .np-preferido {
            display: flex;
            align-items: center;
            gap: .6rem;
            height: 42px;
            margin-top: 1.45rem;
            padding: 0 .8rem;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fafafa;
        }
        #modalInsert .np-foot {
            display: flex;
            justify-content: flex-end;
            gap: .6rem;
            padding: .85rem 1.15rem;
            background: #fff;
            border-top: 1px solid rgba(17, 24, 39, .08);
        }
        #modalInsert .np-foot .btn {
            border-radius: 10px;
            min-width: 120px;
            min-height: 40px;
        }
    </style>

    <!-- Insertar Modal-->
    <div class="modal fade" id="modalInsert" tabindex="-1" aria-labelledby="altaProveedorTitulo" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content np-shell">
                <div class="np-head">
                    <div>
                        <h4 id="altaProveedorTitulo">Nuevo proveedor</h4>
                        <p>Completa la ficha. Contrato, documento y acción son opcionales.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form action="insertarproveedor" method="POST" class="np-form needs-validation" enctype="multipart/form-data" autocomplete="off" novalidate>
                    @csrf
                    <div class="modal-body">
                        <section class="np-card">
                            <h6>Identidad</h6>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label" for="nombre">Nombre</label>
                                    <input type="text" class="form-control" name="nombre" id="nombre" required autocomplete="off">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="telefono">Teléfono</label>
                                    <input type="text" id="telefono" name="telefono" class="form-control" required maxlength="10" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="correo">Correo</label>
                                    <input type="email" id="correo" name="correo" class="form-control" required maxlength="100" autocomplete="off">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="direccion">Dirección</label>
                                    <input type="text" id="direccion" name="direccion" class="form-control" required autocomplete="off">
                                </div>
                            </div>
                        </section>

                        <section class="np-card">
                            <h6>Comercial</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="nit">Alias</label>
                                    <input type="text" id="nit" name="nit" class="form-control" required autocomplete="off">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="giro">Giro</label>
                                    <input type="text" id="giro" name="giro" class="form-control" required maxlength="60" autocomplete="off">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="rfc">RFC</label>
                                    <input type="text" id="rfc" name="rfc" maxlength="13" class="form-control" required autocomplete="off">
                                </div>
                                <div class="col-12">
                                    @include('proveedores.partials.categorias', ['categorias' => $categorias ?? [], 'seleccionado' => 0])
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="alta_homologacion">Homologación</label>
                                    <select class="form-select" id="alta_homologacion" name="homologacion">
                                        @foreach (\App\Support\HomologacionProveedor::ESTADOS as $claveHom => $nombreHom)
                                            <option value="{{ $claveHom }}" {{ $claveHom === 'aprobado' ? 'selected' : '' }}>{{ $nombreHom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <p class="np-note mb-2">Prospecto y en revisión no se invitan. Suspendido y bloqueado no cotizan ni generan orden.</p>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="modo_pago">Modo de pago</label>
                                    <select class="form-select" id="modo_pago" name="modo_pago" required>
                                        <option value="">Seleccione...</option>
                                        <option value="contado">Contado</option>
                                        <option value="credito">Crédito</option>
                                    </select>
                                </div>
                                <div class="col-md-4" id="dias_credito_container" style="display: none;">
                                    <label class="form-label" for="dias_credito">Días de crédito</label>
                                    <input type="number" id="dias_credito" name="dias_credito" class="form-control" min="1" max="365">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="alta_evaluacion">Evaluación</label>
                                    <select class="form-select" id="alta_evaluacion" name="evaluacion" required>
                                        <option value="">Seleccione...</option>
                                        <option value="EXCELENTE">Excelente</option>
                                        <option value="BUENO">Bueno</option>
                                        <option value="REGULAR">Regular</option>
                                        <option value="MALO">Malo</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="alta_estado">Estado</label>
                                    <select class="form-select" id="alta_estado" name="estado" required>
                                        <option value="A" selected>Activo</option>
                                        <option value="I">Inactivo</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="np-preferido">
                                        <input class="form-check-input mt-0" type="checkbox" name="preferido" id="alta_preferido" value="1">
                                        <label class="form-check-label" for="alta_preferido">Proveedor preferido</label>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="np-card">
                            <h6>Contrato</h6>
                            <p class="np-note">Opcional. Si lo dejas vacío, las compras siguen igual.</p>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="alta_contrato_valor">Valor tope</label>
                                    <input type="number" step="0.01" min="0" id="alta_contrato_valor" name="contrato_valor" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="alta_contrato_inicio">Inicio</label>
                                    <input type="date" id="alta_contrato_inicio" name="contrato_inicio" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="alta_contrato_fin">Vencimiento</label>
                                    <input type="date" id="alta_contrato_fin" name="contrato_fin" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="alta_contrato_archivo">Archivo</label>
                                    <input type="file" id="alta_contrato_archivo" name="contrato_archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                        </section>

                        <section class="np-card">
                            <h6>Documento con vigencia</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="alta_documento_nombre">Documento</label>
                                    <input type="text" id="alta_documento_nombre" name="documento_nombre" class="form-control" maxlength="150" placeholder="Ej. Opinión de cumplimiento" autocomplete="off">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label" for="alta_documento_vigencia">Vigencia</label>
                                    <input type="date" id="alta_documento_vigencia" name="documento_vigencia" class="form-control">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="alta_documento_archivo">Archivo</label>
                                    <input type="file" id="alta_documento_archivo" name="documento_archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                </div>
                            </div>
                        </section>

                        <section class="np-card mb-0">
                            <h6>Acción correctiva</h6>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label" for="alta_accion_descripcion">Descripción</label>
                                    <input type="text" id="alta_accion_descripcion" name="accion_descripcion" class="form-control" maxlength="500" autocomplete="off">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="alta_accion_fecha">Fecha</label>
                                    <input type="date" id="alta_accion_fecha" name="accion_fecha" class="form-control">
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="np-foot">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-dark" type="submit">Guardar proveedor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Función para mostrar/ocultar el campo de días de crédito
        document.addEventListener('DOMContentLoaded', function() {
            const modoPagoSelect = document.getElementById('modo_pago');
            const diasCreditoContainer = document.getElementById('dias_credito_container');
            const diasCreditoInput = document.getElementById('dias_credito');
            
            if (modoPagoSelect && diasCreditoContainer && diasCreditoInput) {
                modoPagoSelect.addEventListener('change', function() {
                    if (this.value === 'credito') {
                        diasCreditoContainer.style.display = 'block';
                        diasCreditoInput.required = true;
                    } else {
                        diasCreditoContainer.style.display = 'none';
                        diasCreditoInput.required = false;
                        diasCreditoInput.value = '';
                    }
                });
            }
        });
    </script>


    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
    
    <script>
        // Función para editar persona de atención
        function editarPersonaAtencion(personaId) {
            // Aquí puedes implementar la lógica para editar
            // Por ahora solo mostramos un alert
            alert('Función de edición para persona ID: ' + personaId + ' - Implementar según necesidades');
        }

        // Función para eliminar persona de atención
        function eliminarPersonaAtencion(personaId, nombreCompleto) {
            // Función para mostrar mensajes
            const mostrarMensaje = (titulo, mensaje, tipo = 'success') => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: titulo,
                        text: mensaje,
                        icon: tipo,
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    alert(tipo === 'success' ? mensaje : `${titulo}: ${mensaje}`);
                }
            };

            // Función para confirmar eliminación
            const confirmarEliminacion = () => {
                if (typeof Swal !== 'undefined') {
                    return Swal.fire({
                        title: '¿Eliminar persona de atención?',
                        text: `¿Estás seguro de que deseas eliminar a ${nombreCompleto} de la lista de personas de atención?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    });
                } else {
                    return Promise.resolve({
                        isConfirmed: confirm(`¿Estás seguro de que deseas eliminar a ${nombreCompleto} de la lista de personas de atención?`)
                    });
                }
            };

            confirmarEliminacion().then((result) => {
                if (result.isConfirmed) {
                    // Realizar la petición para eliminar
                    fetch('/eliminarPersonaAtencion/' + personaId, {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => {
                        if (response.ok) {
                            // Mostrar mensaje de éxito
                            mostrarMensaje('Éxito', 'Persona de atención eliminada correctamente', 'success');
                            
                            // Encontrar y eliminar la fila de la tabla
                            const fila = document.querySelector(`tr[data-persona-id="${personaId}"]`);
                            if (fila) {
                                fila.remove();
                            }
                            
                            // Si no hay más personas, mostrar mensaje de "no hay personas"
                            const tbody = document.querySelector('.table tbody');
                            if (tbody && tbody.children.length === 0) {
                                tbody.innerHTML = `
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            <i class="fa-solid fa-users fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No hay personas de atención registradas</h6>
                                            <p class="text-muted">Este proveedor aún no tiene personas de atención asignadas.</p>
                                        </td>
                                    </tr>
                                `;
                            }
                        } else {
                            // Mostrar mensaje de error
                            mostrarMensaje('Error', 'Error al eliminar la persona de atención', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        mostrarMensaje('Error', 'Error al eliminar la persona de atención', 'error');
                    });
                }
            });
        }

        // Función para mostrar formulario de nueva persona de atención
        function mostrarFormularioNuevaPersona(proveedorId) {
            // Cerrar el modal actual de personas de atención
            const modalPersonas = document.getElementById('PersonasAtencion' + proveedorId);
            if (modalPersonas) {
                const modal = bootstrap.Modal.getInstance(modalPersonas);
                if (modal) {
                    modal.hide();
                }
            }
            
            // Mostrar el modal del formulario
            const modalFormulario = new bootstrap.Modal(document.getElementById('NuevaPersonaAtencion' + proveedorId));
            modalFormulario.show();
        }

        // Función para agregar nueva persona de atención (mantenida por compatibilidad)
        function agregarNuevaPersona(proveedorId) {
            mostrarFormularioNuevaPersona(proveedorId);
        }
    </script>
    
@endsection
