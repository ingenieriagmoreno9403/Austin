@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

    <div class="container-fluid format_page">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div>
                            <div class="mb-1">
                                <a href="/Nominas" class="text-muted text-decoration-none fs-8">
                                    <i class="fa-solid fa-chevron-left me-1"></i>Nómina
                                </a>
                            </div>
                            <h2 class="mb-0 text-marino fw-bold">Gestión de Aguinaldos</h2>
                            <p class="text-muted mb-0">Administración del cálculo y entrega de aguinaldos</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-baseColor fs-7 mb-2" type="button" onclick="abrirNuevoAguinaldo()">
                            <i class="fa-solid fa-plus me-1"></i> Nuevo
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <div class="row mt-3">
            <div class="table-responsive">
                <table class="table table-stripped table-hover display" id="tablenomina">
                    <thead>
                        <tr>
                            <th class="text-truncate">Año Aplicación</th>
                            <th class="text-truncate">Nombre</th>
                            <th class="text-truncate">Fecha Aplicación</th>
                            <th class="text-truncate">Fecha Cierre</th>
                            <th class="text-truncate">Estado</th>
                            <th class="text-truncate">Descargables</th>
                            <th class="text-truncate">Opciones</th>
                            <th class="text-truncate">Creado</th>
                            <th class="text-truncate">Actualizado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($varaguinaldos as $item)
                            <tr>
                                <td class="text-truncate fw-bold">{{date('Y', strtotime($item->fecha_pago)) }}</td>
                                <td class="text-truncate">{{ $item->nombre }}</td>
                                <td class="text-truncate">{{date('d/m/Y', strtotime($item->fecha_pago)) }}</td>
                                <td class="text-truncate">
                                    @if($item->fecha_cierre == null)
                                        -
                                    @else
                                        {{ date('d/m/Y', strtotime($item->fecha_cierre))}}
                                    @endif
                                </td>

                                <td class="text-truncate">
                                    @if ($item->estado == 'CREADO')
                                        <span class="badge badge-success-dark fs-9 fw-bold"> Creado</span>
                                    @elseif($item->estado == 'EDICION')
                                        <span class="badge badge-primary-dark fs-9 fw-bold"> Edición</span>
                                    @elseif($item->estado == 'CERRADO')
                                        <span class="badge badge-danger-dark fs-9 fw-bold"> Cerrado</span>
                                    @endif
                                </td>

                                {{-- EXPORTACIONES --}}
                                <td>
                                    <div class="dropdown">
                                        <a class="btn btn-success dropdown-toggle fs-8" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-download "></i> Exportar 
                                        </a>

                                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                            <li><a class="dropdown-item fs-8" href="/Nominas/Aguinaldos/exportar_excel/{{$item->id}}">Excel Aguinaldos</a></li>
                                                @if($item->estado == "Cerrada")
                                                <li><a class="dropdown-item fs-8" href="/ExportarlayoutAguinaldo/{{ $item->id}}">Layout Dispersión</a></li>
                                                <li><a class="dropdown-item fs-8" href="/Nominas/Aguinaldos/exportarComprobantes/{{ $item->id}}">Recibos de Pago</a></li>
                                                @else
                                                <li><a class="dropdown-item disabled fs-8" href="#">Layout Dispersión</a></li>
                                                <li><a class="dropdown-item disabled fs-8" href="#">Recibos de Pago</a></li>
                                                @endif
                                        </ul>
                                    </div>
                                </td>

                                {{-- ACCIONES --}}
                                <td class="text-truncate">
                                  
                                    {{-- editar --}}
                                    @if($item->estado=== 'EDICION')
                                        <a class="btn btn-primary m-0" href="/Nominas/Aguinaldos/Editar/{{ $item->id }}" title="Editar">
                                            <i class="fa-solid fa-pen fs-8 me-1"></i> Editar
                                        </a>

                                        {{-- <button class="btn btn-danger m-0" type="button" title="Eliminar" data-bs-toggle="modal" data-bs-target="#cancelModal{{$item->id}}">
                                            <i class="fa-solid fa-trash fs-8"></i>
                                        </button> --}}

                                    {{-- terminada --}}
                                    @elseif($item->estado=== 'CERRADO')
                                        <a class="btn btn-primary m-0" href="/Nominas/Aguinaldos/Editar/{{ $item->id }}" title="Editar">
                                            <i class="fa-solid fa-eye fs-8 me-1"></i> Ver
                                        </a>
                                        
                                        {{-- <button class="btn btn-danger m-0" type="button" title="Eliminar" data-bs-toggle="modal" data-bs-target="#cancelModal{{ $item->id }}">
                                            <i class="fa-solid fa-trash fs-8"></i>
                                        </button> --}}
                                    @endif
                                </td>
                                
                                <td class="text-truncate">{{date('d/m/Y h:i:s A ', strtotime($item->created_at ))}} - {{ $item->created_by }}</td>
                                <td class="text-truncate">{{date('d/m/Y h:i:s A ', strtotime($item->updated_at ))}} - {{ $item->updated_by }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function abrirNuevoAguinaldo() {
        Swal.fire({
            title: 'Nuevo aguinaldo',
            html: `
                <div class="text-start">
                    <label class="form-label fw-semibold" for="swal_nombre_aguinaldo">Nombre</label>
                    <input type="text" class="form-control mb-3" id="swal_nombre_aguinaldo" maxlength="50" placeholder="Nombre del aguinaldo">
                    <label class="form-label fw-semibold" for="swal_fecha_pago">Fecha de pago</label>
                    <input type="date" class="form-control" id="swal_fecha_pago">
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#475569',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusConfirm: false,
            preConfirm: () => {
                const nombre = document.getElementById('swal_nombre_aguinaldo')?.value.trim();
                const fechaPago = document.getElementById('swal_fecha_pago')?.value;
                if (!nombre) {
                    Swal.showValidationMessage('Ingrese el nombre del aguinaldo.');
                    return false;
                }
                if (!fechaPago) {
                    Swal.showValidationMessage('Seleccione la fecha de pago.');
                    return false;
                }
                return { nombre, fechaPago };
            }
        }).then((result) => {
            if (!result.isConfirmed) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/Nominas/Aguinaldos/Crear';
            form.innerHTML = `
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="nombre" value="${result.value.nombre.replace(/"/g, '&quot;')}">
                <input type="hidden" name="fecha_pago" value="${result.value.fechaPago}">
            `;
            document.body.appendChild(form);
            form.submit();
        });
    }
    </script>

    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/table.js') }}"></script>
@endsection
