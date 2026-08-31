@extends('layouts.app')
@section('content')
    @if($mensaje = Session::get('warningSaldo'))
        @php
                echo '<script language="JavaScript">';
                echo 'const Toast = Swal.mixin({';
                echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
                echo 'didOpen: (toast) => {';
                echo '  toast.onmouseenter = Swal.stopTimer;';
                echo '  toast.onmouseleave = Swal.resumeTimer;}});';
                echo 'Toast.fire({ icon: "warning",title: "¡Saldo Insuficiente!", text: "No se a efectuado la acción, necesita un mayor saldo para hacer la transferencia"});';
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
                            <i class="fas fa-building"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Empresas</h2>
                            <p class="text-muted mb-0">Control de empresas con respecto a razón social</p>
                        </div>
                    </div>
                    <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                        @if ($permisos1 == 'nueva_empresa')
                            <button class="btn btn-baseColor" type="button" data-bs-toggle="modal" data-bs-target="#ModalNuevaEmpresa">
                                <i class="fa-solid fa-plus"></i> Nueva Empresa
                            </button>
                        @else
                            <button class="btn btn-baseColor" disabled type="button">
                                <i class="fa-solid fa-plus"></i> Nueva Empresa
                            </button>
                        @endif

                        @if ($permisos4 == 'exportar_empresa')
                            <a class="btn btn-baseColor-light" href="/CatalogoGeneral/Empresas/Exportar">
                                <i class="fa-solid fa-file-excel"></i> Exportar
                            </a>
                        @else
                            <button class="btn btn-baseColor-light" disabled type="button">
                                <i class="fa-solid fa-file-excel"></i> Exportar
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="table-responsive">
                <table class="table table-stripped table-hover display" id="tableCaja">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-truncate text-start">Opciones</th>
                            <th class="text-truncate">Nombre Empresa</th>
                            <th class="text-truncate">Estado</th>
                            <th class="text-truncate">Nombre Corto</th>
                            <th class="text-truncate">Representante</th>
                            <th class="text-truncate">RFC</th>
                            <th class="text-truncate">Registro Patronal IMSS</th>
                            <th class="text-truncate">Dirección Fiscal</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($varempresas as $empresa)
                            <tr class="boder-sec">
                                <td class="text-truncate text-start text-nowrap">
                                    @if ($permisos2 == 'editar_empresa')
                                        <button class="btn btn-primary border-0 m-0" type="button" data-bs-toggle="modal"  data-bs-target="#ModalEditar{{ $empresa->id }}">
                                            <i class="fa-solid fa-pen fs-8"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-primary border-0 m-0" type="button" disabled>
                                            <i class="fa-solid fa-pen fs-8"></i>
                                        </button>
                                    @endif

                                    @if ($permisos3 == 'eliminar_empresa')
                                        <button class="btn btn-danger m-0" type="button" data-bs-toggle="modal" data-bs-target="#eliminar{{ $empresa->id }}">
                                            <i class="fa-solid fa-ban fs-8"></i>
                                        </button>
                                    @else
                                        <button class="btn btn-secondary m-0" type="button" disabled>
                                            <i class="fa-solid fa-ban fs-8"></i>
                                        </button>
                                    @endif
                                </td>

                                <td class="text-truncate text-start">
                                    @if ($empresa->efectivo == 1)
                                        <i class="text-primary fa-solid fa-dollar-sign"></i>&nbsp; &nbsp;
                                        {{ $empresa->nombre_empresa }}
                                    @else
                                        <i class="text-primary fa-solid fa-building-columns"></i>&nbsp; &nbsp;
                                        {{ $empresa->nombre_empresa }}
                                    @endif
                                </td>

                                <td>
                                    @if ($empresa->estado == 'A')
                                        <span class="badge badge-success-dark fs-9">Activa</span>
                                    @else
                                        <span class="badge badge-danger-dark fs-9">Cancelada</span>
                                    @endif
                                </td>

                                <td>{{ $empresa->descripcion }}</td>
                                <td>{{ $empresa->representada }}</td>
                                <td>{{ $empresa->rfc }}</td>
                                <td>{{ $empresa->registro_patronal_imss }}</td>
                                <td>{{ $empresa->direccion_fiscal }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @foreach ($varempresas as $empresa)
            <!-- Modal Editar Empresa -->
            <div class="modal fade" id="ModalEditar{{ $empresa->id }}" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <h5 class="modal-title text-dark" id="exampleModalLabel">Editar Empresa <b class="fs-8 text-secondary"> (Razon Social)</b></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="/CatalogoGeneral/Empresas/EditarEmpresa/{{ $empresa->id }}" method="POST"
                            enctype="multipart/form-data" class="g-3 form needs-validation mt-1" novalidate>
                            @csrf
                            <div class="modal-body">
                                <div class="container">
                                    <div class="row mb-2">
                                        <div class="col">
                                            <div class="form-outline">
                                                <label class="form-label text-dark" for="form8Example4">Nombre
                                                    Oficial</label>
                                                <input type="text" class="form-control text" name="nombre_empresa"
                                                    value="{{ $empresa->nombre_empresa }}" id="nombre_empresa"
                                                    maxlength="100" required />
                                                <div class="valid-feedback">
                                                    ¡Se ve bien!
                                                </div>
                                                <div class="invalid-feedback">
                                                    Por favor, completa la información
                                                    requerida.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col">
                                            <div class="form-outline">
                                                <label class="form-label text-dark" for="form8Example4">Nombre
                                                    Corto</label>
                                                <input type="text" class="form-control text" name="descripcion"
                                                    id="descripcion" value="{{ $empresa->descripcion }}" maxlength="100" />
                                                <div class="valid-feedback">
                                                    ¡Se ve bien!
                                                </div>
                                                <div class="invalid-feedback">
                                                    Por favor, completa la información
                                                    requerida.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-1">
                                            <div class="form-outline text-start">
                                                <label class="form-label text-dark" for="form8Example4">Efectivo </label>
                                                <div class="form-check form-switch">
                                                    @if ($empresa->efectivo == '1')
                                                        <input class="form-check-input"
                                                            style="width: 45px!important;height: 18px!important;"
                                                            type="checkbox" value="1" role="switch" name="efectivo"
                                                            id="efectivo" checked>
                                                    @else
                                                        <input class="form-check-input"
                                                            style="width: 45px!important;height: 18px!important;"
                                                            type="checkbox" value="1" role="switch" name="efectivo"
                                                            id="efectivo">
                                                    @endif
                                                </div>
                                                <div class="valid-feedback">
                                                    ¡Se ve bien!
                                                </div>
                                                <div class="invalid-feedback">
                                                    Por favor, completa la información
                                                    requerida.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-1 mb-2">
                                            <div class="form-outline text-start">
                                                <label class="form-label text-dark" for="form8Example4">Estado</label>
                                                <div class="form-check form-switch">
                                                    @if ($empresa->estado == 'A')
                                                        <input class="form-check-input"
                                                            style="width: 45px!important;height: 18px!important;"
                                                            type="checkbox" value="1" role="switch" name="estado"
                                                            id="estado" checked>
                                                    @else
                                                        <input class="form-check-input"
                                                            style="width: 45px!important;height: 18px!important;"
                                                            type="checkbox" value="1" role="switch" name="estado"
                                                            id="estado">
                                                    @endif
                                                </div>
                                                <div class="valid-feedback">
                                                    ¡Se ve bien!
                                                </div>
                                                <div class="invalid-feedback">
                                                    Por favor, completa la información
                                                    requerida.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <div class="form-outline">
                                                <label class="form-label text-dark" for="form8Example4">Representante</label>
                                                <input type="text" class="form-control text" name="representada"
                                                    id="representada" value="{{ $empresa->representada }}" maxlength="100"
                                                    required />
                                                <div class="valid-feedback">
                                                    ¡Se ve bien!
                                                </div>
                                                <div class="invalid-feedback">
                                                    Por favor, completa la información
                                                    requerida.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-outline">
                                                <label class="form-label text-dark" for="rfc_{{ $empresa->id }}">RFC</label>
                                                <input type="text" class="form-control text text-uppercase" name="rfc"
                                                    id="rfc_{{ $empresa->id }}" maxlength="12"
                                                    value="{{ \Illuminate\Support\Str::substr((string) $empresa->rfc, 0, 12) }}"
                                                    required autocomplete="off" />
                                                <div class="valid-feedback">
                                                    ¡Se ve bien!
                                                </div>
                                                <div class="invalid-feedback">
                                                    Por favor, completa la información
                                                    requerida.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-outline">
                                                <label class="form-label text-dark" for="registro_patronal_imss_{{ $empresa->id }}">Registro Patronal IMSS</label>
                                                <input type="text" class="form-control text text-uppercase" name="registro_patronal_imss"
                                                    id="registro_patronal_imss_{{ $empresa->id }}"
                                                    value="{{ \Illuminate\Support\Str::substr((string) ($empresa->registro_patronal_imss ?? ''), 0, 15) }}"
                                                    maxlength="15" autocomplete="off"
                                                    title="Hasta 15 caracteres (letras y números, incl. 0)"
                                                    placeholder="Ej. B2827500102" />
                                                <div class="form-text text-muted">Hasta 15 caracteres. Acepta letras y ceros (0).</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-12">
                                            <div class="form-outline">
                                                <label class="form-label text-dark" for="form8Example4">Dirección
                                                    Fiscal</label>
                                                <input type="text" class="form-control text" name="direccion_fiscal"
                                                    id="direccion_fiscal" value="{{ $empresa->direccion_fiscal }}"
                                                    maxlength="200" required />
                                                <div class="valid-feedback">
                                                    ¡Se ve bien!
                                                </div>
                                                <div class="invalid-feedback">
                                                    Por favor, completa la información
                                                    requerida.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="container">
                                <div class="row justify-content-center p-3">
                                    <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Modal Eliminar Empresa -->
            <div class="modal fade" id="eliminar{{ $empresa->id }}" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <h5 class="modal-title text-dark" id="exampleModalLabel">¿Qué
                                desea hacer?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="/CatalogoGeneral/Empresas/EliminarEmpresa/{{ $empresa->id }}" method="POST"
                            enctype="multipart/form-data" class="g-3 form needs-validation mt-1" novalidate>
                            @csrf
                            <div class="modal-body">
                                <div class="container">
                                    <div class="row mb-2">

                                        <div class="text-start">
                                            <div class="form-check">
                                                <input class="form-check-input" style="margin-top:5px;" type="radio"
                                                    value="borrar" name="eliminaraccion" id="eliminaraccion" required>
                                                <label class="form-check-label text-dark" for="eliminaraccion">
                                                    Borrar Definitivamente
                                                </label>
                                                <div class="valid-feedback">
                                                    ¡Se ve bien!
                                                </div>
                                                <div class="invalid-feedback">
                                                    Por favor, completa la información
                                                    requerida.
                                                </div>
                                            </div>

                                            <div class="form-check">
                                                @if ($empresa->estado == 'A')
                                                    <input class="form-check-input" type="radio" style="margin-top:5px;"
                                                        value="inactivar" name="eliminaraccion" id="eliminaraccion" required>
                                                @else
                                                    <input class="form-check-input" type="radio" style="margin-top:5px;"
                                                        value="inactivar" name="eliminaraccion" id="eliminaraccion" disabled>
                                                @endif
                                                <label class="form-check-label text-dark" for="eliminaraccion">
                                                    Inactivar Completamente
                                                </label>
                                                <div class="valid-feedback">
                                                    ¡Se ve bien!
                                                </div>
                                                <div class="invalid-feedback">
                                                    Por favor, completa la información
                                                    requerida.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="container">
                                <div class="row justify-content-center p-3">
                                    <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach


        <!-- Modal Nueva Empresa -->
        <div class="modal fade" id="ModalNuevaEmpresa" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="exampleModalLabel"> Nueva Empresa <b class="fs-8 text-secondary"> (Razon Social)</b></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="/CatalogoGeneral/Empresas/NuevaEmpresa" method="POST" enctype="multipart/form-data"
                        class="g-3 form needs-validation mt-1" novalidate>
                        @csrf
                        <div class="modal-body">
                            <div class="container">
                                <div class="row mb-2">
                                    <div class="col">
                                        <div class="form-outline">
                                            <label class="form-label text-dark fs-8" for="form8Example4">Nombre
                                                Oficial</label>
                                            <input type="text" class="form-control text" name="nombre_empresa"
                                                id="nombre_empresa" maxlength="100" required />
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
                                            <label class="form-label text-dark fs-8" for="form8Example4">Nombre Corto</label>
                                            <input type="text" class="form-control text" name="descripcion"
                                                id="descripcion" maxlength="100" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-outline text-start">
                                            <label class="form-label text-dark fs-8" for="form8Example4">Efectivo </label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input"
                                                    style="width: 45px!important;height: 18px!important;" type="checkbox"
                                                    value="1" role="switch" name="efectivo" id="efectivo">
                                            </div>
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col">
                                        <div class="form-outline">
                                            <label class="form-label text-dark fs-8" for="form8Example4">Representante</label>
                                            <input type="text" class="form-control text" name="representada"
                                                id="representada" maxlength="100" required />
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
                                            <label class="form-label text-dark fs-8" for="rfc_nueva">RFC</label>
                                            <input type="text" class="form-control text text-uppercase" name="rfc" id="rfc_nueva"
                                                maxlength="12" required autocomplete="off" />
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
                                            <label class="form-label text-dark fs-8" for="registro_patronal_imss_nueva">Registro Patronal IMSS</label>
                                            <input type="text" class="form-control text text-uppercase" name="registro_patronal_imss"
                                                id="registro_patronal_imss_nueva" maxlength="15" autocomplete="off"
                                                title="Hasta 15 caracteres (letras y números, incl. 0)"
                                                placeholder="Ej. B2827500102" />
                                            <div class="form-text text-muted">Hasta 15 caracteres. Acepta letras y ceros (0).</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col">
                                        <div class="form-outline">
                                            <label class="form-label text-dark fs-8" for="form8Example4">Dirección
                                                Fiscal</label>
                                            <input type="text" class="form-control text" name="direccion_fiscal"
                                                id="direccion_fiscal" maxlength="200" required />
                                            <div class="valid-feedback">
                                                ¡Se ve bien!
                                            </div>
                                            <div class="invalid-feedback">
                                                Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="container">
                            <div class="row justify-content-center p-3">
                                <button type="submit" class="col-6 btn btn-baseColor fs-8 rounded-1 m-2 mt-0"><i class="fa-solid fa-check"></i> Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    

    <script src="{{ asset('js/tableX.js') }}"></script>
    <script src="{{ asset('js/validation.js') }}"></script>
@endsection
