@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.0/css/fixedColumns.dataTables.min.css">
<link href="{{ asset('css/inputfile.css') }}" rel="stylesheet">
@endsection

@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    /* Estilos solo para FixedColumns / FixedHeader en empleados.
       El card visual lo da .table-responsive (igual que Catalogos/Gastos). */
    .empleados-table-card .td-actions {
        white-space: nowrap !important;
    }

    .empleados-table-card .td-actions .btn {
        display: inline-flex !important;
        margin: 0 2px !important;
    }

    /* Nombre: truncar dentro de ancho fijo para no desalinear FixedColumns */
    body.empleados-index-page th.td-nombre,
    body.empleados-index-page td.td-nombre {
        width: 220px !important;
        max-width: 220px !important;
        min-width: 160px !important;
        overflow: hidden !important;
        vertical-align: middle !important;
        box-sizing: border-box !important;
    }

    body.empleados-index-page td.td-nombre .td-nombre-text {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }

    @media (max-width: 768px) {
        body.empleados-index-page th.td-nombre,
        body.empleados-index-page td.td-nombre {
            width: 160px !important;
            max-width: 160px !important;
            min-width: 140px !important;
        }
    }

    /* Mantener el padding del card como en Gastos; overflow visible solo para FixedColumns */
    .empleados-table-card.table-responsive {
        overflow: visible !important;
    }

    .empleados-table-card div.dt-scroll,
    .empleados-table-card div.dataTables_scroll {
        border: 0 !important;
        margin: 0 !important;
        position: relative !important;
        width: 100% !important;
    }

    .empleados-table-card div.dt-scroll-head,
    .empleados-table-card div.dataTables_scrollHead {
        margin-bottom: 0 !important;
        overflow: hidden !important;
        border-bottom: 0 !important;
    }

    .empleados-table-card div.dt-scroll-body,
    .empleados-table-card div.dataTables_scrollBody {
        border-top: 0 !important;
        margin-top: 0 !important;
        overflow-x: auto !important;
        overflow-y: auto !important;
        width: 100% !important;
    }

    .empleados-table-card div.dt-scroll-head table,
    .empleados-table-card div.dataTables_scrollHead table,
    .empleados-table-card div.dt-scroll-body table,
    .empleados-table-card div.dataTables_scrollBody table {
        margin: 0 !important;
    }

    body.empleados-index-page table.dataTable tr > .dtfc-fixed-start,
    body.empleados-index-page table.dataTable tr > .dtfc-fixed-end,
    body.empleados-index-page table.dataTable tr > .dtfc-fixed-left,
    body.empleados-index-page table.dataTable tr > .dtfc-fixed-right {
        position: sticky !important;
        background-clip: padding-box !important;
    }

    body.empleados-index-page table.dataTable thead tr > .dtfc-fixed-start,
    body.empleados-index-page table.dataTable thead tr > .dtfc-fixed-end {
        background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%) !important;
        z-index: 5 !important;
    }

    body.empleados-index-page table.dataTable tbody tr > .dtfc-fixed-start,
    body.empleados-index-page table.dataTable tbody tr > .dtfc-fixed-end {
        background: #fff !important;
        z-index: 4 !important;
    }

    body.empleados-index-page table.dataTable tbody tr:hover > .dtfc-fixed-start,
    body.empleados-index-page table.dataTable tbody tr:hover > .dtfc-fixed-end {
        background: #f3f4f6 !important;
    }

    body.empleados-index-page div.dtfh-floatingparent {
        overflow: hidden !important;
        z-index: 1030 !important;
    }

    body.empleados-index-page div.dtfh-floatingparent table.dataTable {
        margin: 0 !important;
        background: #fff !important;
    }

    body.empleados-index-page div.dtfh-floatingparent thead th {
        background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%) !important;
        box-sizing: border-box !important;
    }

    body.empleados-index-page div.dtfh-floatingparent thead tr > .dtfc-fixed-start,
    body.empleados-index-page div.dtfh-floatingparent thead tr > .dtfc-fixed-end {
        position: sticky !important;
        background: linear-gradient(180deg, #ffffff 0%, #f9fafb 100%) !important;
        z-index: 6 !important;
    }

    #modalExportarSua .modal-dialog {
        max-height: calc(100vh - 2rem);
        margin: 1rem auto;
    }

    #modalExportarSua .modal-content,
    #modalExportarSua form.modal-content {
        max-height: calc(100vh - 2rem);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    #modalExportarSua .modal-body {
        overflow-y: auto;
        overscroll-behavior: contain;
        flex: 1 1 auto;
        min-height: 0;
    }

    #modalExportarSua .modal-header,
    #modalExportarSua .modal-footer {
        flex: 0 0 auto;
    }

    .sua-tipo-list {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        padding: 0.65rem 0.85rem;
        border: 1px solid var(--table-border, #e5e7eb);
        border-radius: 10px;
        background: #f9fafb;
    }

    .sua-tipo-list .form-check {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        min-height: 1.6rem;
        margin: 0;
        padding: 0.2rem 0;
        padding-left: 0 !important;
    }

    .sua-tipo-list .form-check-input {
        float: none;
        position: static;
        margin: 0 !important;
        flex: 0 0 auto;
    }

    .sua-tipo-list .form-check-label {
        margin: 0;
        line-height: 1.3;
    }

    .sua-empleados-list {
        max-height: min(420px, 48vh);
        overflow-y: auto;
        background: #fff;
    }

    .sua-empleado-item {
        display: grid;
        grid-template-columns: auto 1fr;
        column-gap: 0.65rem;
        row-gap: 0.45rem;
        align-items: start;
        padding: 0.7rem 0.75rem;
        margin: 0;
        border-bottom: 1px solid var(--table-border, #e5e7eb);
    }

    .sua-empleado-item:last-child {
        border-bottom: 0;
    }

    .sua-empleado-item:hover {
        background: #f9fafb;
    }

    .sua-empleado-item > .sua-empleado-check {
        margin-top: 0.35rem;
    }

    .sua-empleado-main {
        min-width: 0;
    }

    .sua-empleado-nombre {
        display: block;
        font-weight: 600;
        color: #1f2937;
        font-size: 0.9rem;
    }

    .sua-empleado-meta {
        display: block;
        color: #6b7280;
        font-size: 0.78rem;
        margin-top: 0.1rem;
    }

    .sua-empleado-meta .sua-rp-ok {
        color: #047857;
        font-weight: 600;
    }

    .sua-empleado-meta .sua-rp-bad {
        color: #b45309;
        font-weight: 600;
    }

    .sua-empleado-fields {
        grid-column: 2;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.45rem 0.65rem;
    }

    .sua-empleado-fields.sua-campos-movimientos,
    .sua-empleado-fields.sua-campos-credito,
    .sua-empleado-fields.sua-campos-incapacitados {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sua-empleado-fields .form-label {
        margin-bottom: 0.15rem;
        font-size: 0.72rem;
        font-weight: 600;
        color: #6b7280;
    }

    .sua-empleado-fields .form-select,
    .sua-empleado-fields .form-control {
        font-size: 0.8rem;
        min-height: 32px;
        padding-top: 0.2rem;
        padding-bottom: 0.2rem;
    }

    @media (max-width: 576px) {
        .sua-empleado-fields,
        .sua-empleado-fields.sua-campos-movimientos,
        .sua-empleado-fields.sua-campos-credito,
        .sua-empleado-fields.sua-campos-incapacitados {
            grid-template-columns: 1fr;
        }
    }

    .empleados-filters {
        background: var(--table-soft, #f9fafb);
        border: 1px solid var(--table-border, #e5e7eb);
        margin-bottom: 0.85rem;
        padding: 0.55rem 0.75rem !important;
        border-radius: 14px !important;
        box-shadow: none;
    }
    .empleados-filters form {
        gap: 0.35rem 0.65rem;
    }
    .empleados-filters .filter-field {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 1 auto;
        max-width: none;
    }
    .empleados-filters .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--table-muted, #6b7280);
        margin-bottom: 0;
        white-space: nowrap;
    }
    .empleados-filters .form-select-sm {
        min-height: 28px;
        height: 28px;
        font-size: 0.78rem;
        padding: 0.15rem 0.4rem;
        width: auto;
        min-width: 120px;
        max-width: 180px;
        background: #ffffff !important;
        border: 1px solid var(--table-border, #e5e7eb) !important;
        border-radius: 10px !important;
        color: var(--table-text, #111827) !important;
    }
    .empleados-filters .btn-sm {
        min-height: 28px;
        padding: 0.15rem 0.55rem;
        font-size: 0.78rem;
    }
    @media (max-width: 768px) {
        .empleados-filters .filter-field {
            flex: 1 1 calc(50% - 0.5rem);
        }
        .empleados-filters .form-select-sm {
            flex: 1;
            max-width: none;
        }
    }
</style>

<script>
    document.body.classList.add('empleados-index-page');
</script>

<div class="container-fluid format_page">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Catálogo de Empleados</h2>
                        <p class="text-muted mb-0">Gestión y administración de empleados</p>
                    </div>
                </div>
                <div class="header-actions d-flex flex-wrap align-items-center gap-2">
                    @if ($permisos1 == 'alta_empleados')
                        <button class="btn btn-baseColor" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasBottom"
                            aria-controls="offcanvasBottom">
                            <i class="fa-solid fa-plus"></i> Ingreso
                        </button>

                        <!-- <div class="dropdown d-inline-block">
                            <button class="btn btn-baseColor-light dropdown-toggle" type="button" id="dropdownImportEmpleados"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-file-import"></i> Importar
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownImportEmpleados">
                                <li>
                                    <button class="dropdown-item" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasImportEmpleados">
                                        <i class="fa-solid fa-users me-2"></i> Importar empleados
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasImportNomina">
                                        <i class="fa-solid fa-file-invoice-dollar me-2"></i> Importar nómina
                                    </button>
                                </li>
                            </ul>
                        </div> -->
                    @else
                        <button class="btn btn-baseColor" disabled type="button">
                            <i class="fa-solid fa-plus"></i> Ingreso
                        </button>
                    @endif

                    <a class="btn btn-baseColor-light" href="{{ route('Empleados.incapacidades') }}">
                        <i class="fa-solid fa-stethoscope"></i> Incapacidades
                    </a>

                    <a class="btn btn-baseColor-light" href="{{ route('Empleados.vacaciones') }}">
                        <i class="fa-solid fa-umbrella-beach"></i> Vacaciones
                    </a>

                    <button class="btn btn-baseColor-light" type="button" data-bs-toggle="modal" data-bs-target="#modalExportarSua">
                        <i class="fa-solid fa-file-export"></i> Exportar SUA
                    </button>

                    @if ($permisos2 == 'graficar_empleados')
                        <a href="{{ route('Empleados.grafica_empleados') }}" class="btn btn-baseColor-light">
                            <i class="fa-solid fa-chart-pie"></i> Gráfica
                        </a>
                    @else
                        <button class="btn btn-baseColor-light" disabled>
                            <i class="fa-solid fa-chart-pie"></i> Gráfica
                        </button>
                    @endif

                    @include('Empleados.partials.contratos-por-vencer-campana')
                </div>
            </div>
        </div>
    </div>

    <!-- Main Section -->
    <div class="row mt-3">
        <div class="table-responsive empleados-table-card">
            <div class="filters empleados-filters">
                <form method="GET" action="{{ route('verempleados') }}" class="d-flex flex-wrap align-items-center" id="filtroEmpleados">
                    <div class="filter-field">
                        <label class="form-label" for="filtro_estado">Estado</label>
                        <select class="form-select form-select-sm" id="filtro_estado" name="filtro" aria-label="Filtrar por estado">
                            <option value="A" {{ ($filtro ?? 'A') === 'A' ? 'selected' : '' }}>Activo</option>
                            <option value="I" {{ ($filtro ?? '') === 'I' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_sucursal">Sucursal</label>
                        <select class="form-select form-select-sm" id="filtro_sucursal" name="sucursal" aria-label="Filtrar por sucursal">
                            <option value="">Todas</option>
                            @foreach ($varsucursales as $sucursalOption)
                                <option value="{{ $sucursalOption->id }}" {{ (string) ($filtroSucursal ?? '') === (string) $sucursalOption->id ? 'selected' : '' }}>
                                    {{ $sucursalOption->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label" for="filtro_puesto">Puesto</label>
                        <select class="form-select form-select-sm" id="filtro_puesto" name="puesto" aria-label="Filtrar por puesto">
                            <option value="">Todos</option>
                            @foreach ($varpuestos as $puestoOption)
                                <option value="{{ $puestoOption->id }}" {{ (string) ($filtroPuesto ?? '') === (string) $puestoOption->id ? 'selected' : '' }}>
                                    {{ $puestoOption->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-baseColor-light btn-sm flex-shrink-0">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                </form>
            </div>

            @php
                $totalSalarios = $visorFiscalActivo
                    ? $varlistaempleados->sum('salario_fijo')
                    : $varlistaempleados->sum('salario_bruto');
                $totalEmpleados = $varlistaempleados->count();
            @endphp
            <table class="table table-striped table-hover display modern-table w-100" id="table">
                <thead>
                    <tr>
                        <th class="text-center">Acciones</th>
                        <th>Foto</th>
                        <th class="td-nombre">Nombre</th>
                        <th>Estado</th>
                        <th>Telefono</th>
                        <th>Ciudad</th>
                        <th>Puesto</th>
                        <th>Sucursal</th>
                        <th>Zona</th>
                        <th class="text-end">{{ $visorFiscalActivo ? 'Salario Diario Fiscal' : 'Sueldo Mensual' }}</th>
                        <th>Contratación</th>
                        <th>Vencimiento contrato</th>
                    </tr>
                </thead>

                        <tbody>
                            @foreach ($varlistaempleados as $vis)
                                <tr>
                                    <td class="td-actions text-center">
                                        <button class="btn btn-secondary m-0" type="button" data-bs-toggle="modal" data-bs-target="#datosClientes{{ $vis->idempleado }}" title="Ver Datos Cliente">
                                           <i class="fa-solid fa-circle-info fs-8"></i>
                                        </button>

                                        @if ($vis->estado == 'A')
                                            <a class="btn btn-primary m-0" title="Editar Empleado" href="/Empleados/{{ $vis->idempleado }}/edit">
                                                <i class="fa-solid fa-pen fs-8"></i>
                                            </a>
                                        @else
                                            @if ($permisos5 == 'reactivar_empleados')
                                                <a class="btn btn-success m-0" title="Reactivar Empleado" href="/Empleados/ReactivarEmpleado/{{ $vis->idempleado }}">
                                                    <i class="fa-solid fa-user-check fs-8"></i>
                                                </a>
                                            @else
                                                <button class="btn btn-success m-0" title="Reactivar Empleado" disabled>
                                                    <i class="fa-solid fa-user-check fs-8"></i>
                                                </button>
                                            @endif
                                        @endif

                                        @if ($vis->estado == 'A')
                                            @if ($permisos4 == 'eliminar_empleados')
                                                <a class="btn btn-danger m-0" href="/Empleados/Baja/{{ $vis->idempleado }}" title="Baja Empleado">
                                                    <i class="fa-solid fa-trash fs-8"></i>
                                                </a>
                                            @else
                                                <button class="btn btn-danger m-0" title="Baja Empleado" disabled>
                                                    <i class="fa-solid fa-trash fs-8"></i>
                                                </button>
                                            @endif
                                        @else
                                            @if ($vis->archivo_baja == '1')
                                                <a class="btn btn-danger m-0" target="_blank" title="Ver Docuemnto Baja" href="DetallesEmpleados/bajas/baja_{{ $vis->idempleado }}.pdf">
                                                    <i class="fa-solid fa-file fs-8"></i>
                                                </a>
                                            @else
                                                <button class="btn btn-danger m-0" title="Subir Baja Firmada" type="button" data-bs-toggle="modal" data-bs-target="#modalBajaFirmada{{ $vis->idempleado }}" id="{{ $vis->idempleado }}">
                                                    <i class="fa-solid fa-arrow-up-from-bracket fs-8"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="p-0 text-center">
                                        <button class="btn border-0 m-0" type="button"
                                            data-bs-toggle="modal" data-bs-target="#ModalFoto{{ $vis->idempleado }}">
                                            <img class="pImage border" src="{{ asset('Images/Perfil/' . $vis->nombre_foto) }}"
                                                alt="foto" />
                                        </button>
                                    </td>

                                    @php
                                        $nombreCompleto = trim(collect([
                                            $vis->primer_nombre,
                                            $vis->segundo_nombre,
                                            $vis->apellido_paterno,
                                            $vis->apellido_materno,
                                        ])->filter()->implode(' '));
                                    @endphp
                                    <td class="td-nombre" title="{{ $nombreCompleto }}">
                                        <span class="td-nombre-text">{{ $nombreCompleto }}</span>
                                    </td>

                                    <td>
                                        @if ($vis->estado == 'A')
                                            <span class="badge badge-success-dark fs-9">Activo</span>
                                        @else
                                            <span class="badge badge-danger-dark fs-9">Inactivo</span>
                                        @endif
                                    </td>

                                    <td>{{ $vis->telefono }}</td>
                                    <td>{{ $vis->ciudad }}</td>
                                    <td>{{ $vis->puesto }}</td>
                                    <td>{{ $vis->sucursal }}</td>
                                    <td class="text-nowrap">
                                        @if (($vis->zona ?? '') === 'ZFN')
                                            <span class="badge badge-primary fs-9" title="Zona Libre de la Frontera Norte">ZFN</span>
                                        @elseif (($vis->zona ?? '') === 'RP')
                                            <span class="badge badge-orange fs-9" title="Resto del país">RP</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    
                                    <td class="text-end text-nowrap" data-order="{{ $visorFiscalActivo ? $vis->salario_fijo : $vis->salario_bruto }}">
                                        $ {{ number_format($visorFiscalActivo ? $vis->salario_fijo : $vis->salario_bruto, 2) }}
                                    </td>

                                    <td>
                                        @if ($vis->tipo_contratacion == 'INDEFINIDA')
                                            <span class="badge badge-primary fs-9">INDEFINIDA</span>
                                        @else
                                            <span class="badge badge-orange fs-9">DEFINIDA</span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        @if ($vis->tipo_contratacion == 'DEFINIDA' && filled($vis->fecha_determinado))
                                            {{ $vis->fecha_determinado }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-muted fw-normal fs-8">
                                    {{ $totalEmpleados }} empleado{{ $totalEmpleados === 1 ? '' : 's' }}
                                    <span class="float-end text-dark">Subtotal (página)</span>
                                </th>
                                <th colspan="6"></th>
                                <th class="text-end"></th>
                                <th colspan="2"></th>
                            </tr>
                            <tr>
                                <th colspan="3" class="text-end text-nowrap fw-bold">Total general</th>
                                <th colspan="6"></th>
                                <th class="text-end fw-bold">
                                    $ {{ number_format($totalSalarios, 2) }}
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
        </div>
    </div>
</div>

@foreach ($varlistaempleados as $vis)
    @include('Empleados.partials.detalle-modal', ['vis' => $vis])

    <!-- Modal foto-->
    <div class="modal fade" id="ModalFoto{{ $vis->idempleado }}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="card mb-3" style="max-width: 540px;">
                <div class="row g-0">
                    <div class="col-md-4">
                        <img src="{{ asset('Images/Perfil/' . $vis->nombre_foto) }}"
                            class="img-fluid rounded-start"
                            style="width:100%;height:200px!important;object-fit:cover;"
                            alt="foto">
                    </div>
                    <div class="col-md-8">
                        <div class="card-body text-start">
                            <h6 class="card-title fw-bold fw-normal mb-1">
                                <span class="badge badge-orange fs-9">Empleado</span>
                            </h6>

                            <div class="card-text">
                                <h5 class="text-dark">{{ $vis->primer_nombre }}
                                    {{ $vis->segundo_nombre }}
                                    {{ $vis->apellido_paterno }}
                                    {{ $vis->apellido_materno }}
                                </h5>
                                <span class="text-dark fs-8">
                                    <b>{{ $vis->puesto }}</b>
                                    <i class="fa-solid fa-chevron-right"></i>
                                    {{ $vis->sucursal }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subir Baja Firmada Modal -->
    <div class="modal fade" id="modalBajaFirmada{{ $vis->idempleado }}" tabindex="-1"
        aria-labelledby="modalBajaFirmadaLabel{{ $vis->idempleado }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <h5 class="modal-title text-marino fw-bold mb-0" id="modalBajaFirmadaLabel{{ $vis->idempleado }}">
                            Baja Firmada
                        </h5>
                        <a class="btn btn-outline-primary fs-8" target="_blank" href="/Empleados/getdownloadBaja/{{ $vis->idempleado }}">
                            <i class="fa-solid fa-download"></i> Descargar Archivo
                        </a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <form action="/Empleados/guardarBajaEmpleado/{{ $vis->idempleado }}" method="POST"
                        enctype="multipart/form-data" class="g-3 form msform needs-validation" novalidate>
                        @csrf
                        <div class="modern-file-input mt-2" data-input-id="baja-firmada-{{ $vis->idempleado }}">
                            <div class="file-input-wrapper" id="wrapper-baja-firmada-{{ $vis->idempleado }}">
                                <div class="file-input-content">
                                    <div class="file-input-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <p class="file-input-text">Arrastra tu archivo aquí</p>
                                    <p class="file-input-subtext">o haz clic para seleccionar</p>
                                    <small class="file-input-subtext d-block mt-2">Solo se admite formato .pdf</small>
                                </div>
                                <input type="file" name="bajaFirmada" id="baja-firmada-{{ $vis->idempleado }}"
                                    class="hidden-file-input" required accept=".pdf"/>
                            </div>
                            <div class="file-preview" id="preview-baja-firmada-{{ $vis->idempleado }}">
                                <div class="file-preview-item">
                                    <div class="file-preview-info">
                                        <div class="file-preview-icon">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <div class="file-preview-details">
                                            <h6 id="filename-baja-firmada-{{ $vis->idempleado }}"></h6>
                                            <small id="filesize-baja-firmada-{{ $vis->idempleado }}"></small>
                                        </div>
                                    </div>
                                    <button type="button" class="file-preview-remove"
                                        onclick="removeFile('baja-firmada-{{ $vis->idempleado }}')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" id="progress-baja-firmada-{{ $vis->idempleado }}"></div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-baseColor fs-7">
                                <i class="fas fa-arrow-up-from-bracket"></i> Subir
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Exportar SUA -->
<div class="modal fade" id="modalExportarSua" tabindex="-1" aria-labelledby="modalExportarSuaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="{{ route('Empleados.exportar_sua') }}" method="POST" id="formExportarSua" class="modal-content" novalidate>
            @csrf
            <div class="modal-header">
                <h5 class="modal-title text-marino fw-bold" id="modalExportarSuaLabel">
                    <i class="fa-solid fa-file-export text-orange"></i> Exportar a SUA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted fs-8 mb-3">
                    Genere archivos TXT de longitud fija para importar en SUA.
                    Seleccione el tipo de exportación y los empleados a incluir.
                </p>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Seleccione el tipo de Importación que desee:</label>
                    <div class="sua-tipo-list">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_exportacion" id="sua_tipo_trabajadores" value="trabajadores" checked>
                            <label class="form-check-label" for="sua_tipo_trabajadores">Trabajadores (altas)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_exportacion" id="sua_tipo_afiliatorios" value="afiliatorios">
                            <label class="form-check-label" for="sua_tipo_afiliatorios">Datos Afiliatorios</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_exportacion" id="sua_tipo_movimientos" value="movimientos">
                            <label class="form-check-label" for="sua_tipo_movimientos">Movimientos</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_exportacion" id="sua_tipo_credito" value="credito">
                            <label class="form-check-label" for="sua_tipo_credito">Movimientos de Crédito</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipo_exportacion" id="sua_tipo_incapacitados" value="incapacitados">
                            <label class="form-check-label" for="sua_tipo_incapacitados">Datos de Incapacidades</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <label class="form-label fw-semibold mb-0">Empleados a exportar</label>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="sua_seleccionar_todos">
                        <label class="form-check-label" for="sua_seleccionar_todos">Seleccionar visibles</label>
                    </div>
                </div>
                <div class="mb-3 form">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" class="form-control" id="sua_buscar_empleado"
                            placeholder="Buscar por nombre, NSS, puesto o empresa..." autocomplete="off">
                    </div>
                    <small class="text-muted d-block mt-1" id="sua_contador_empleados"></small>
                </div>
                <div class="mb-2 d-none" id="sua_panel_movimientos">
                    <label class="form-label" for="sua_tipo_movimiento">Tipo de Movimiento</label>
                    <select class="form-select" name="tipo_movimiento" id="sua_tipo_movimiento">
                        @foreach (($tiposMovimientoSuaOpciones ?? []) as $codigoMov => $etiquetaMov)
                            <option value="{{ $codigoMov }}">{{ $etiquetaMov }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2 d-none" id="sua_panel_credito">
                    <label class="form-label" for="sua_tipo_movimiento_credito">Tipo de Movimiento de Crédito</label>
                    <select class="form-select" name="tipo_movimiento_credito" id="sua_tipo_movimiento_credito">
                        @foreach (($tiposMovimientoCreditoSuaOpciones ?? []) as $codigoMovCred => $etiquetaMovCred)
                            <option value="{{ $codigoMovCred }}">{{ $etiquetaMovCred }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-muted fs-8 mb-2" id="sua_ayuda_trabajadores">
                    Trabajadores (altas): solo empleados activos. Registro Patronal, Tipo de Trabajador y Jornada por empleado.
                </p>
                <p class="text-muted fs-8 mb-2 d-none" id="sua_ayuda_afiliatorios">
                    Datos Afiliatorios (80 caracteres): solo empleados activos. RP, NSS, C.P., fecha/lugar nacimiento, ocupación, sexo y tipo de salario.
                </p>
                <p class="text-muted fs-8 mb-2 d-none" id="sua_ayuda_movimientos">
                    Movimientos (49 caracteres): Baja lista solo empleados con estado I; los demás tipos solo activos.
                </p>
                <p class="text-muted fs-8 mb-2 d-none" id="sua_ayuda_credito">
                    Movimientos de Crédito INFONAVIT (52 caracteres): solo empleados con crédito INFONAVIT en nómina; el número se precarga automáticamente.
                </p>
                <div class="mb-2 d-none" id="sua_panel_incapacitados">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="exportar_todas_incapacidades" id="sua_exportar_todas_incap" value="1" checked>
                        <label class="form-check-label" for="sua_exportar_todas_incap">
                            Exportar todas las incapacidades registradas de cada empleado
                        </label>
                    </div>
                </div>
                <p class="text-muted fs-8 mb-2 d-none" id="sua_ayuda_incapacitados">
                    Datos de Incapacidades (57 caracteres): folio, fechas, días, %, rama, riesgo, secuela y control. Los campos editan la más reciente; el resto sale de la base.
                </p>

                <div class="sua-empleados-list border rounded">
                    @forelse (($varlistaempleadosSua ?? $varlistaempleados) as $empSua)
                        @php
                            $idEmpSua = (int) $empSua->idempleado;
                            $nombreSua = trim(collect([
                                $empSua->apellido_paterno,
                                $empSua->apellido_materno,
                                $empSua->primer_nombre,
                                $empSua->segundo_nombre,
                            ])->filter()->implode(' '));
                            $jornadaEmp = (string) ($jornadasSuaSugeridas[$idEmpSua] ?? $jornadasSuaSugeridas[$empSua->idempleado] ?? '0');
                            $registroPatronalEmp = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) ($empSua->registro_patronal_imss ?? '')) ?? '');
                            $rpValido = strlen($registroPatronalEmp) === 11;
                            $tipoEmpDefault = strtoupper(trim((string) ($empSua->tipo_contratacion ?? ''))) === 'INDEFINIDA' ? '1' : '2';
                            $bajaSua = ($bajasSua ?? [])[$idEmpSua] ?? null;
                            $incSua = ($incapacidadesSua ?? [])[$idEmpSua] ?? null;
                            $tieneBajaSua = (($empSua->estado ?? '') === 'I');
                            $tieneIncapacidadSua = (bool) $incSua;
                            $fechaBajaSua = (string) ($bajaSua->fecha_baja_iso ?? '');
                            $fechaIncapacidadSua = (string) ($incSua->fecha_inicio_iso ?? '');
                            $fechaMovDefault = $fechaIncapacidadSua ?: $fechaBajaSua;
                            $folioMovDefault = strtoupper(substr(preg_replace('/\s+/', '', (string) ($incSua->folio ?? '')) ?? '', 0, 8));
                            $diasMovDefault = (int) ($incSua->dias_calculados ?? 0);
                            $sdiMovDefault = number_format((float) ($empSua->salario_fijo ?? 0), 2, '.', '');
                            $numeroCreditoDefault = preg_replace('/\D+/', '', (string) ($empSua->numero_credito_infonavit ?? '')) ?? '';
                            if ($numeroCreditoDefault !== '' && (int) $numeroCreditoDefault > 0) {
                                $numeroCreditoDefault = str_pad(substr($numeroCreditoDefault, 0, 10), 10, '0', STR_PAD_LEFT);
                            } else {
                                $numeroCreditoDefault = '';
                            }
                            $tieneCreditoSua = $numeroCreditoDefault !== '';
                            $tipoDescDefault = \App\Services\SuaExportService::tipoDescuentoDesdeCatalogo(
                                (string) ($empSua->nombreinfonavit ?? ''),
                                $empSua->id_tipoinfonavit ?? null
                            );
                            // Si no hay tipo INFONAVIT válido, no forzar Porcentaje.
                            if ($tipoDescDefault === '0' && $tieneCreditoSua) {
                                $tipoDescDefault = '2';
                            }
                            $valorDescRaw = (float) ($empSua->descuento_quincenal ?? 0);
                            if ($valorDescRaw <= 0) {
                                $valorDescRaw = (float) ($empSua->factor_sua ?? 0);
                            }
                            $valorDescDefault = number_format($valorDescRaw, 4, '.', '');
                            $fechaCreditoDefault = '';
                            if (!empty($empSua->fecha_ingreso_imss) && $empSua->fecha_ingreso_imss !== '0000-00-00') {
                                try {
                                    $rawFechaCred = (string) $empSua->fecha_ingreso_imss;
                                    $fechaCreditoDefault = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawFechaCred)
                                        ? \Carbon\Carbon::createFromFormat('d/m/Y', $rawFechaCred)->format('Y-m-d')
                                        : \Carbon\Carbon::parse($rawFechaCred)->format('Y-m-d');
                                } catch (\Throwable $e) {
                                    $fechaCreditoDefault = '';
                                }
                            }
                            $fechaIniIncapDefault = '';
                            $fechaFinIncapDefault = '';
                            if (!empty($incSua?->fecha_inicio)) {
                                try {
                                    $fechaIniIncapDefault = \Carbon\Carbon::parse($incSua->fecha_inicio)->format('Y-m-d');
                                } catch (\Throwable $e) {
                                    $fechaIniIncapDefault = '';
                                }
                            }
                            if (!empty($incSua?->fecha_fin)) {
                                try {
                                    $fechaFinIncapDefault = \Carbon\Carbon::parse($incSua->fecha_fin)->format('Y-m-d');
                                } catch (\Throwable $e) {
                                    $fechaFinIncapDefault = '';
                                }
                            }
                            $folioIncapDatosDefault = strtoupper(substr(preg_replace('/\s+/', '', (string) ($incSua?->folio ?? '')) ?? '', 0, 8));
                            $diasIncapDefault = (int) ($incSua?->dias_calculados ?? 0);
                            $ramaIncapDefault = (string) ($incSua?->rama_sua ?? '2');
                            $controlIncapDefault = (string) ($incSua?->control_sua ?? '2');
                            $riesgoIncapDefault = $ramaIncapDefault === '1' ? '1' : '0';
                            $secuelaIncapDefault = $ramaIncapDefault === '1' ? '1' : '0';
                            $textoBusquedaSua = mb_strtolower(trim(implode(' ', [
                                $nombreSua,
                                $empSua->nss ?? '',
                                $empSua->puesto ?? '',
                                $empSua->nombre_empresa ?? '',
                                $empSua->tipo_contratacion ?? '',
                                $registroPatronalEmp,
                                $numeroCreditoDefault,
                            ])), 'UTF-8');
                        @endphp
                        <div class="sua-empleado-item"
                            data-search="{{ $textoBusquedaSua }}"
                            data-estado="{{ $empSua->estado ?? '' }}"
                            data-tiene-baja="{{ $tieneBajaSua ? '1' : '0' }}"
                            data-tiene-incapacidad="{{ $tieneIncapacidadSua ? '1' : '0' }}"
                            data-tiene-credito="{{ $tieneCreditoSua ? '1' : '0' }}"
                            data-numero-credito="{{ $numeroCreditoDefault }}"
                            data-tipo-descuento="{{ $tipoDescDefault }}"
                            data-valor-descuento="{{ $valorDescDefault }}"
                            data-nombre-infonavit="{{ $empSua->nombreinfonavit ?? '' }}"
                            data-fecha-baja="{{ $fechaBajaSua }}"
                            data-fecha-incapacidad="{{ $fechaIncapacidadSua }}"
                            data-folio-incapacidad="{{ $folioMovDefault }}"
                            data-dias-incapacidad="{{ $diasMovDefault }}">
                            <input class="form-check-input sua-empleado-check" type="checkbox"
                                name="empleados[]" value="{{ $idEmpSua }}"
                                id="sua_emp_{{ $idEmpSua }}"
                                data-registro-patronal="{{ $registroPatronalEmp }}">
                            <div class="sua-empleado-main">
                                <label class="sua-empleado-nombre" for="sua_emp_{{ $idEmpSua }}">{{ $nombreSua }}</label>
                                <span class="sua-empleado-meta">
                                    NSS: {{ $empSua->nss ?: '—' }}
                                    · {{ $empSua->puesto ?: 'Sin puesto' }}
                                    · {{ $empSua->tipo_contratacion ?: 'Sin contrato' }}
                                    · {{ $empSua->nombre_empresa ?: 'Sin empresa' }}
                                    · RP:
                                    @if ($rpValido)
                                        <span class="sua-rp-ok">{{ $registroPatronalEmp }}</span>
                                    @elseif ($registroPatronalEmp !== '')
                                        <span class="sua-rp-bad">{{ $registroPatronalEmp }} (debe ser 11)</span>
                                    @else
                                        <span class="sua-rp-bad">Sin capturar en empresa</span>
                                    @endif
                                    @if (($empSua->estado ?? '') === 'I' || $tieneBajaSua)
                                        · <span class="text-danger">Baja{{ $fechaBajaSua ? ' ' . \Carbon\Carbon::parse($fechaBajaSua)->format('d/m/Y') : '' }}</span>
                                    @endif
                                    @if ($tieneIncapacidadSua)
                                        · <span class="text-warning">Incapacidad{{ $fechaIncapacidadSua ? ' ' . \Carbon\Carbon::parse($fechaIncapacidadSua)->format('d/m/Y') : '' }}</span>
                                    @endif
                                    @if ($tieneCreditoSua)
                                        · <span class="sua-rp-ok">Crédito {{ $numeroCreditoDefault }}</span>
                                        @if (!empty($empSua->nombreinfonavit) && strtoupper((string) $empSua->nombreinfonavit) !== 'N/A')
                                            · {{ $empSua->nombreinfonavit }}
                                        @endif
                                    @endif
                                </span>
                                <div class="sua-empleado-fields mt-1 sua-campos-trabajadores">
                                    <div>
                                        <label class="form-label" for="sua_tipo_{{ $idEmpSua }}">Tipo de Trabajador</label>
                                        <select class="form-select" name="tipo_trabajador[{{ $idEmpSua }}]" id="sua_tipo_{{ $idEmpSua }}">
                                            @foreach (($tiposTrabajadorSuaOpciones ?? []) as $codigoTipo => $etiquetaTipo)
                                                <option value="{{ $codigoTipo }}" @selected((string) $codigoTipo === $tipoEmpDefault)>
                                                    {{ $etiquetaTipo }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_jornada_{{ $idEmpSua }}">Jornada/Sem. Red.</label>
                                        <select class="form-select" name="jornada[{{ $idEmpSua }}]" id="sua_jornada_{{ $idEmpSua }}">
                                            @foreach (($jornadasSuaOpciones ?? []) as $codigoJornada => $etiquetaJornada)
                                                <option value="{{ $codigoJornada }}" @selected((string) $codigoJornada === $jornadaEmp)>
                                                    {{ $etiquetaJornada }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="sua-empleado-fields mt-1 sua-campos-movimientos d-none">
                                    <div>
                                        <label class="form-label" for="sua_fecha_mov_{{ $idEmpSua }}">Fecha movimiento</label>
                                        <input type="date" class="form-control" name="fecha_movimiento[{{ $idEmpSua }}]"
                                            id="sua_fecha_mov_{{ $idEmpSua }}"
                                            value="{{ $fechaMovDefault }}">
                                    </div>
                                    <div class="sua-field-folio d-none">
                                        <label class="form-label" for="sua_folio_{{ $idEmpSua }}">Folio incapacidad</label>
                                        <input type="text" class="form-control text-uppercase" name="folio_incapacidad[{{ $idEmpSua }}]"
                                            id="sua_folio_{{ $idEmpSua }}" maxlength="8"
                                            value="{{ $folioMovDefault }}" placeholder="XX999999">
                                    </div>
                                    <div class="sua-field-dias d-none">
                                        <label class="form-label" for="sua_dias_{{ $idEmpSua }}">Días incidencia</label>
                                        <input type="number" class="form-control" name="dias_incidencia[{{ $idEmpSua }}]"
                                            id="sua_dias_{{ $idEmpSua }}" min="0" max="99"
                                            value="{{ $diasMovDefault }}">
                                    </div>
                                    <div class="sua-field-sdi d-none">
                                        <label class="form-label" for="sua_sdi_{{ $idEmpSua }}">Salario diario integrado</label>
                                        <input type="number" class="form-control" name="sdi_movimiento[{{ $idEmpSua }}]"
                                            id="sua_sdi_{{ $idEmpSua }}" min="0" step="0.01"
                                            value="{{ $sdiMovDefault }}">
                                    </div>
                                </div>
                                <div class="sua-empleado-fields mt-1 sua-campos-credito d-none">
                                    <div>
                                        <label class="form-label" for="sua_credito_{{ $idEmpSua }}">Núm. crédito</label>
                                        <input type="text" class="form-control sua-input-credito" name="numero_credito[{{ $idEmpSua }}]"
                                            id="sua_credito_{{ $idEmpSua }}" maxlength="10" inputmode="numeric"
                                            value="{{ $numeroCreditoDefault }}" placeholder="10 dígitos">
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_fecha_cred_{{ $idEmpSua }}">Fecha movimiento</label>
                                        <input type="date" class="form-control" name="fecha_movimiento_credito[{{ $idEmpSua }}]"
                                            id="sua_fecha_cred_{{ $idEmpSua }}"
                                            value="{{ $fechaCreditoDefault }}" min="1973-01-01">
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_tipo_desc_{{ $idEmpSua }}">Tipo descuento</label>
                                        <select class="form-select sua-select-tipo-desc" name="tipo_descuento[{{ $idEmpSua }}]" id="sua_tipo_desc_{{ $idEmpSua }}">
                                            @foreach (($tiposDescuentoSuaOpciones ?? []) as $codigoDesc => $etiquetaDesc)
                                                <option value="{{ $codigoDesc }}" @selected((string) $codigoDesc === (string) $tipoDescDefault)>
                                                    {{ $etiquetaDesc }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (!empty($empSua->nombreinfonavit))
                                            <small class="text-muted">Nómina: {{ $empSua->nombreinfonavit }}</small>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_valor_desc_{{ $idEmpSua }}">Valor descuento</label>
                                        <input type="number" class="form-control sua-input-valor-desc" name="valor_descuento[{{ $idEmpSua }}]"
                                            id="sua_valor_desc_{{ $idEmpSua }}" min="0" step="0.0001"
                                            value="{{ $valorDescDefault }}">
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_aplica_{{ $idEmpSua }}">Aplica tabla %</label>
                                        <select class="form-select" name="aplica_tabla[{{ $idEmpSua }}]" id="sua_aplica_{{ $idEmpSua }}">
                                            <option value="N" selected>N</option>
                                            <option value="S">S</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="sua-empleado-fields mt-1 sua-campos-incapacitados d-none">
                                    <div>
                                        <label class="form-label" for="sua_fi_incap_{{ $idEmpSua }}">Fecha inicio</label>
                                        <input type="date" class="form-control" name="fecha_inicio_incapacidad[{{ $idEmpSua }}]"
                                            id="sua_fi_incap_{{ $idEmpSua }}" value="{{ $fechaIniIncapDefault }}" min="1998-01-01">
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_ft_incap_{{ $idEmpSua }}">Fecha término</label>
                                        <input type="date" class="form-control" name="fecha_termino_incapacidad[{{ $idEmpSua }}]"
                                            id="sua_ft_incap_{{ $idEmpSua }}" value="{{ $fechaFinIncapDefault }}" min="1998-01-01">
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_folio_incap_{{ $idEmpSua }}">Folio</label>
                                        <input type="text" class="form-control text-uppercase sua-input-folio-incap" name="folio_incapacidad_datos[{{ $idEmpSua }}]"
                                            id="sua_folio_incap_{{ $idEmpSua }}" maxlength="8"
                                            value="{{ $folioIncapDatosDefault }}" placeholder="XX999999">
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_dias_incap_{{ $idEmpSua }}">Días subsidiados</label>
                                        <input type="number" class="form-control" name="dias_subsidiados[{{ $idEmpSua }}]"
                                            id="sua_dias_incap_{{ $idEmpSua }}" min="0" max="999"
                                            value="{{ $diasIncapDefault > 0 ? $diasIncapDefault : '0' }}">
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_pct_incap_{{ $idEmpSua }}">Porcentaje</label>
                                        <input type="number" class="form-control" name="porcentaje_incapacidad[{{ $idEmpSua }}]"
                                            id="sua_pct_incap_{{ $idEmpSua }}" min="0" max="100" value="0">
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_rama_{{ $idEmpSua }}">Rama</label>
                                        <select class="form-select" name="rama_incapacidad[{{ $idEmpSua }}]" id="sua_rama_{{ $idEmpSua }}">
                                            @foreach (($ramasIncapacidadSuaOpciones ?? []) as $codRama => $etiRama)
                                                <option value="{{ $codRama }}" @selected((string) $codRama === $ramaIncapDefault)>{{ $etiRama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_riesgo_{{ $idEmpSua }}">Tipo riesgo</label>
                                        <select class="form-select" name="tipo_riesgo[{{ $idEmpSua }}]" id="sua_riesgo_{{ $idEmpSua }}">
                                            @foreach (($tiposRiesgoSuaOpciones ?? []) as $codRiesgo => $etiRiesgo)
                                                <option value="{{ $codRiesgo }}" @selected((string) $codRiesgo === $riesgoIncapDefault)>{{ $etiRiesgo }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_secuela_{{ $idEmpSua }}">Secuela</label>
                                        <select class="form-select" name="secuela_incapacidad[{{ $idEmpSua }}]" id="sua_secuela_{{ $idEmpSua }}">
                                            @foreach (($secuelasIncapacidadSuaOpciones ?? []) as $codSec => $etiSec)
                                                <option value="{{ $codSec }}" @selected((string) $codSec === $secuelaIncapDefault)>{{ $etiSec }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label" for="sua_control_{{ $idEmpSua }}">Control</label>
                                        <select class="form-select" name="control_incapacidad[{{ $idEmpSua }}]" id="sua_control_{{ $idEmpSua }}">
                                            @foreach (($controlesIncapacidadSuaOpciones ?? []) as $codCtrl => $etiCtrl)
                                                <option value="{{ $codCtrl }}" @selected((string) $codCtrl === $controlIncapDefault)>{{ $etiCtrl }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-3 text-muted">No hay empleados en el filtro actual.</div>
                    @endforelse
                    <div class="p-3 text-muted d-none" id="sua_sin_resultados">No hay empleados que coincidan con la búsqueda.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-baseColor" id="btnExportarSua">
                    <i class="fa-solid fa-download"></i> Descargar TXT
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Importar Empleados -->
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasImportEmpleados" style="height:55vh">
    <div class="offcanvas-header border-0">
        <h5 class="offcanvas-title text-marino fw-bold">
            <i class="fa-solid fa-file-import text-orange"></i> Importar catálogo de empleados
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <p class="text-muted fs-8">
            Carga un archivo Excel (.xlsx) con las columnas del catálogo. Los campos vacíos se respetan como nulos.
            Si no existen puesto, sucursal, ciudad o banco, se crean automáticamente en sus catálogos.
        </p>
        <form action="{{ route('Empleados.importar_empleados') }}" method="POST" enctype="multipart/form-data" class="g-3 form msform needs-validation" novalidate>
            @csrf
            <div class="modern-file-input mt-2" data-input-id="import-empleados">
                <div class="file-input-wrapper" id="wrapper-import-empleados">
                    <div class="file-input-content">
                        <div class="file-input-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="file-input-text">Arrastra tu archivo aquí</p>
                        <p class="file-input-subtext">o haz clic para seleccionar</p>
                        <small class="file-input-subtext d-block mt-2">Formato: Catalogo de Empleados.xlsx</small>
                    </div>
                    <input type="file" name="archivo_empleados" id="import-empleados" class="hidden-file-input" accept=".xlsx,.xls" required/>
                </div>
                <div class="file-preview" id="preview-import-empleados">
                    <div class="file-preview-item">
                        <div class="file-preview-info">
                            <div class="file-preview-icon">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <div class="file-preview-details">
                                <h6 id="filename-import-empleados"></h6>
                                <small id="filesize-import-empleados"></small>
                            </div>
                        </div>
                        <button type="button" class="file-preview-remove" onclick="removeFile('import-empleados')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-import-empleados"></div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="submit" class="btn btn-baseColor fs-7">
                    <i class="fa-solid fa-upload"></i> Importar empleados
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Importar Nómina -->
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="offcanvasImportNomina" style="height:55vh">
    <div class="offcanvas-header border-0">
        <h5 class="offcanvas-title text-marino fw-bold">
            <i class="fa-solid fa-file-import text-orange"></i> Importar datos de nómina
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <p class="text-muted fs-8">
            Carga un archivo Excel (.xlsx) vinculado por id de empleado. Los campos vacíos se respetan como nulos.
            Si no existen empresa, banco o tipo infonavit, se crean en sus catálogos correspondientes.
        </p>
        <form action="{{ route('Empleados.importar_nomina') }}" method="POST" enctype="multipart/form-data" class="g-3 form msform needs-validation" novalidate>
            @csrf
            <div class="modern-file-input mt-2" data-input-id="import-nomina">
                <div class="file-input-wrapper" id="wrapper-import-nomina">
                    <div class="file-input-content">
                        <div class="file-input-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <p class="file-input-text">Arrastra tu archivo aquí</p>
                        <p class="file-input-subtext">o haz clic para seleccionar</p>
                        <small class="file-input-subtext d-block mt-2">Formato: Catalogo de Nomina.xlsx</small>
                    </div>
                    <input type="file" name="archivo_nomina" id="import-nomina" class="hidden-file-input" accept=".xlsx,.xls" required/>
                </div>
                <div class="file-preview" id="preview-import-nomina">
                    <div class="file-preview-item">
                        <div class="file-preview-info">
                            <div class="file-preview-icon">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <div class="file-preview-details">
                                <h6 id="filename-import-nomina"></h6>
                                <small id="filesize-import-nomina"></small>
                            </div>
                        </div>
                        <button type="button" class="file-preview-remove" onclick="removeFile('import-nomina')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-import-nomina"></div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="submit" class="btn btn-baseColor fs-7">
                    <i class="fa-solid fa-upload"></i> Importar nómina
                </button>
            </div>
        </form>
    </div>
</div>


<!-- Insertar Modal-->
<div class="offcanvas offcanvas-bottom empleado-ingreso-offcanvas" tabindex="-1" id="offcanvasBottom"
    aria-labelledby="offcanvasBottomLabel" style="height:100vh">
    <div class="empleado-ingreso-header">
        <div class="d-flex align-items-start justify-content-between gap-3 px-4 py-3 border-bottom">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span class="badge bg-warning text-dark">Alta</span>
                    <h5 class="offcanvas-title text-marino fw-bold mb-0" id="offcanvasBottomLabel">
                        <i class="fa-solid fa-user-plus text-orange me-1"></i> Nuevo Empleado
                    </h5>
                </div>
                <p class="text-muted fs-8 mb-0">Complete los 3 pasos para registrar al colaborador</p>
            </div>
            <button type="button" class="btn-close mt-1" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="empleado-ingreso-toolbar px-4 py-2 border-bottom">
            <nav id="navbar-example2" class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <ul class="nav nav-pills empleado-step-nav gap-1 mb-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="#paso1"><span>1</span> General</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#paso2"><span>2</span> Contratación</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#paso3"><span>3</span> Salario</a>
                    </li>
                </ul>
                <div class="empleado-ingreso-progress-wrap">
                    <div class="d-flex justify-content-between fs-8 text-muted mb-1">
                        <span>Avance</span>
                        <span id="empleadoFormProgressText">0%</span>
                    </div>
                    <div class="progress empleado-ingreso-progress">
                        <div id="empleadoFormProgress" class="progress-bar" role="progressbar"
                            style="width:0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <div class="offcanvas-body empleado-ingreso-body p-0">
        <form action="/Empleados/store" method="POST" enctype="multipart/form-data"
            class="empleado-ingreso-form g-3 form needs-validation modern-form" novalidate>
            @csrf

            <div class="empleado-ingreso-scroll" tabindex="0" id="empleadoFormScroll">
                <div class="container-fluid py-4 px-lg-5">

                <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-ingreso-card">
                    <div class="card-body p-4 bg-white rounded-3">
                        <h4 id="paso1" class="empleado-section-title">
                            <i class="fa-solid fa-user text-orange"></i>
                            <span><b class="text-orange">Paso 1.</b> General</span>
                        </h4>
                        <div class="row">
                        <div class="col-md-3 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label" for="form8Example4">Primer Nombre</label>
                                <input type="text" class="form-control text" name="primer_nombre"
                                    id="primer_nombre" minlength="3" maxlength="20" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Segundo Nombre</label>
                                <input type="text" id="segundo_nombre" name="segundo_nombre"
                                    class="form-control text" minlength="3" maxlength="20" />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Apellido Paterno</label>
                                <input type="text" name="apellido_paterno" id="apellido_paterno"
                                    class="form-control text" minlength="3" maxlength="20" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Apellido Materno</label>
                                <input type="text" name="apellido_materno" id="apellido_materno"
                                    class="form-control text" minlength="3" maxlength="20" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 col-6 mt-2">
                            <div class="form-outline">
                                <label class="form-label" for="form8Example4">Telefono</label>
                                <input type="numeric" name="telefono" id="telefono" class="form-control text"
                                    minlength="6" maxlength="10" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-6 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Correo</label>
                                <input type="text" name="correo" id="correo" class="form-control text"
                                    minlength="3" maxlength="40" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-6 mt-2">
                            <!-- Name input -->
                            <div class="form-outline">
                                <label class="form-label">Grado de estudio</label>
                                <input type="text" name="grado_estudio" id="grado_estudio"
                                    class="form-control text" minlength="3" maxlength="100" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-6 mt-2">
                            <!-- Name input -->
                            <div class="form-outline">
                                <label class="form-label">Nacionalidad</label>
                                <input type="text" name="nacionalidad" id="nacionalidad"
                                    class="form-control text" minlength="3" maxlength="100" required/>
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-6 mt-2">
                            <!-- Name input -->
                            <div class="form-outline">
                                <label class="form-label">Ciudad</label>
                                <select name="ciudad" id="ciudad" class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    @foreach ($varciudades as $obtenerciudad)
                                        <option value="{{ $obtenerciudad->id }}">{{ $obtenerciudad->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-6 mt-2">
                            <!-- Email input -->
                            <div class="form-outline">
                                <label class="form-label" for="form8Example4">Colonia</label>
                                <input type="text" name="colonia" id="colonia" class="form-control text"
                                    minlength="3" maxlength="60" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 col-6 mt-2">
                            <!-- Email input -->
                            <div class="form-outline">
                                <label class="form-label" for="form8Example4">Calle</label>
                                <input type="text" name="calle" id="calle" class="form-control text"
                                    minlength="3" maxlength="60" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        
                        <div class="col-md-2 col-6 mt-2">
                            <!-- Email input -->
                            <div class="form-outline">
                                <label class="form-label">Numero Interior</label>
                                <input type="text" name="numero_interior" id="numero_interior"
                                    class="form-control text" maxlength="10" placeholder="#00" />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-6 mt-2">
                            <!-- Name input -->
                            <div class="form-outline">
                                <label class="form-label">Numero Exterior</label>
                                <input type="text" class="form-control text" name="numero_exterior"
                                    id="numero_exterior" maxlength="10" placeholder="#00" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-6 mt-2">
                            <!-- Email input -->
                            <div class="form-outline">
                                <label class="form-label">Codigo Postal</label>
                                <input type="text" name="codigo_postal" id="codigo_postal"
                                    class="form-control text" maxlength="6" placeholder="00000" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 col-4 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Estado Civil</label>
                                <select class="form-select" name="estado_civil" id="estado_civil" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="SOLTERO">SOLTERO</option>
                                    <option value="CASADO">CASADO</option>
                                    <option value="UNION_LIBRE">UNION LIBRE</option>
                                </select>
                            </div>
                        </div>


                        <div class="col-md-4 col-4 mt-2">
                            <!-- Email input -->
                            <div class="form-outline">
                                <label class="form-label">Sexo</label>
                                <select class="form-select" id="sexo" name="sexo"required>
                                    <option value="">Seleccionar...</option>
                                    <option value="M">M</option>
                                    <option value="F">F</option>
                                </select>
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-4 mt-2">
                            <!-- Email input -->
                            <div class="form-outline">
                                <label class="form-label">Tipo de Sangre</label>
                                <input type="text" name="tipo_sangre" id="tipo_sangre"
                                    class="form-control text" placeholder="+" maxlength="2" />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-6 mt-2">
                            <!-- Email input -->
                            <div class="form-outline">
                                <label class="form-label">Fecha de Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento"
                                    class="form-control" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-6 mt-2">
                            <!-- Email input -->
                            <div class="form-outline">
                                <label class="form-label">Fecha de ingreso</label>
                                <input type="date" name="fecha_alta" id="fecha_alta" class="form-control"
                                    required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-12 mt-2">
                            <!-- Name input -->
                            <div class="form-outline">
                                <label class="form-label">Contacto de Emergencias</label>
                                <input type="text" name="contacto_emergencias" id="contacto_emergencias"
                                    class="form-control text" placeholder="Nombre de la persona"
                                    maxlength="50" />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>

                            </div>
                        </div>

                        <div class="col-md-6 col-12 mt-2">
                            <!-- Email input -->
                            <div class="form-outline">
                                <label class="form-label">Telefono de emergencia </label>
                                <input type="text" name="telefono_emergencia" id="telefono_emergencia"
                                    class="form-control text" placeholder="00000000000" maxlength="10" />
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

                <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-ingreso-card">
                    <div class="card-body p-4 bg-white rounded-3">
                        <h4 id="paso2" class="empleado-section-title">
                            <i class="fa-solid fa-briefcase text-orange"></i>
                            <span><b class="text-orange">Paso 2.</b> Datos de Contratación</span>
                        </h4>

                    <div class="mb-2 pb-4 border-bottom">
                        <div class="row">
                            <div class="col-md-4 col-4 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Puesto</label>
                                    <select name="puesto" id="puesto" class="form-select"required>
                                        <option value="">Seleccionar...</option>
                                        @foreach ($varpuestos as $obtenerpuestos)
                                            <option value="{{ $obtenerpuestos->id }}">{{ $obtenerpuestos->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 col-4 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Sucursal</label>
                                    <select name="sucursal" id="sucursal" class="form-select"required>
                                        <option value="">Seleccionar...</option>
                                        @foreach ($varsucursales as $obtenersucursal)
                                            <option value="{{ $obtenersucursal->id }}">
                                                {{ $obtenersucursal->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 col-4 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Tipo Contratacion</label>
                                    <select name="tipo_contratacion" id="tipo_contratacion_ingreso" class="form-select" required onchange="toggleContratacionIngreso()">
                                        <option value="">Seleccionar...</option>
                                        <option value="INDEFINIDA">INDEFINIDA</option>
                                        <option value="DEFINIDA">DEFINIDA</option>
                                    </select>

                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback"> Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row" id="fecha_determinado_ingreso_wrap" style="display:none;">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Fecha de vencimiento de contrato</label>
                                    <input type="date" name="fecha_determinado" id="fecha_determinado_ingreso" class="form-control" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">CURP</label>
                                    <input type="text" name="curp" id="curp" class="form-control text"
                                        placeholder="xxxxxxxxxx" maxlength="18"required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-6 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">NSS</label>
                                    <input type="text" name="nss" id="numero_seguro_social"
                                        class="form-control text" placeholder="xxxxxxxxxx" maxlength="12" required />
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

                    <div class="mb-2 pb-4 border-bottom">
                        <h6 class="empleado-subsection-title">Datos Fiscales</h6>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">RFC</label>
                                    <input type="text" name="rfc" id="rfc" class="form-control text"
                                        placeholder="0000000000000" maxlength="13" required />
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
    
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Colonia</label>
                                    <input type="text" name="colonia_f" id="colonia_f" class="form-control text" maxlength="50" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>


                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Calle</label>
                                    <input type="text" name="calle_f" id="calle_f" class="form-control text" maxlength="50" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">No. Interior</label>
                                    <input type="text" name="no_interior_f" id="no_interior_f" class="form-control text" maxlength="10" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">No. Exterior</label>
                                    <input type="text" name="no_exterior_f" id="no_exterior_f" class="form-control text" maxlength="10"  />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>


                            <div class="col-md-4 col-12 mt-2">
                                <div class="form-outline">
                                    <label class="form-label">Codigo Postal</label>
                                    <input type="number" name="codigo_postal_f" id="codigo_postal_f" class="form-control text" maxlength="11" />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                     <div class="mb-2 pb-2">
                        <h6 class="empleado-subsection-title">Datos Bancarios</h6>

                            <div class="row">
                                <div class="col-md-2 col-6 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Banco</label>
                                        <select name="banco" id="banco" class="form-select" required>
                                            <option value="">Seleccionar...</option>
                                            @foreach ($varbancos as $obtenerbanco)
                                                <option value="{{ $obtenerbanco->id }}">{{ $obtenerbanco->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-2 col-6 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">ID Banca</label>
                                        <input type="text" name="idbanca" value="0"
                                            class="form-control text" maxlength="11" required/>
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-6 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Numero Tarjeta</label>
                                        <input type="text" name="numero_tarjeta" id="numero_tarjeta"
                                            class="form-control text" maxlength="16" placeholder="00.00" />
                                        <div class="valid-feedback">
                                            ¡Se ve bien!
                                        </div>
                                        <div class="invalid-feedback">
                                            Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-6 mt-2">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Numero de Cuenta</label>
                                    <input type="text" name="numero_cuenta" id="numero_cuenta"
                                        class="form-control text" maxlength="18"/>
                                    <div class="valid-feedback">
                                        ¡Se ve bien!
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, completa la información requerida.
                                    </div>
                                </div>
                            </div>

                                <div class="col-md-2 col-6 mt-2">
                                    <div class="form-outline">
                                        <label class="form-label">Tipo de Tranfererencia</label>
                                        <select name="tipo_tranferencia" id="banco" class="form-select" required>
                                            <option value="1">Transferencia SPEI</option>
                                            <option value="2">Transferencia TEF</option>
                                            <option value="3">Traspaso cuentas BAZ</option>
                                        </select>
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

                    @include('Empleados.partials.horarios-semana', [
                        'vardias' => $vardias,
                        'varhorarios' => $varhorarios,
                        'horariosDefaults' => [
                            1 => 7,
                            2 => 1,
                            3 => 1,
                            4 => 1,
                            5 => 1,
                            6 => 1,
                            7 => 8,
                        ],
                    ])
                    </div>
                </div>
                
                <div class="card shadow-sm border-0 mb-4 rounded-4 empleado-ingreso-card">
                    <div class="card-body p-4 bg-white rounded-3">
                        <h4 id="paso3" class="empleado-section-title">
                            <i class="fa-solid fa-coins text-orange"></i>
                            <span><b class="text-orange">Paso 3.</b> Salario</span>
                        </h4>

                    <div class="row">
                        <div class="col-md-4 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Fecha de ingreso a IMSS</label>
                                <input type="date" name="fecha_ingreso_imss" id="fecha_ingreso_imss"
                                    class="form-control" maxlength="12" />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>

                            </div>
                        </div>

                        <div class="col-md-4 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Empresa</label>
                                <select class="form-select mb-3" id="cmbempresas" name="cmbempresas"
                                    aria-label="Ejemplo de .form-select-lg" minlength="1"required>
                                    @foreach ($varempresas as $obtenerempresa)
                                        @if ($obtenerempresa->efectivo == 1)
                                            <option value="{{ $obtenerempresa->id }}">Efectivo -
                                                {{ $obtenerempresa->nombre_empresa }}</option>
                                        @else
                                            <option value="{{ $obtenerempresa->id }}">
                                                {{ $obtenerempresa->nombre_empresa }}</option>
                                        @endif
                                    @endforeach
                                </select>

                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Zona</label>
                                <select class="form-select mb-3" name="zona" id="zona" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="ZFN">Zona Libre de la Frontera Norte (ZFN)</option>
                                    <option value="RP">Resto del país (RP)</option>
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 col-12 mt-2">
                            <div class="form-outline">
                                <label class="form-label">Sueldo Mensual</label>
                                <input type="text" name="salario_bruto" id="salario_bruto"
                                    class="form-control text" maxlength="10" placeholder="00.00" required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>

                            </div>
                        </div>

                        <div class="col-md-4 col-12 mt-2" id="salario_fijo">
                            <div class="form-outline">
                                <label class="form-label">Salario Diario</label>
                                <input type="text" name="salario_fijo" id="salario_fijo"
                                    class="form-control text" maxlength="10" placeholder="00.00"
                                    required />
                                <div class="valid-feedback">
                                    ¡Se ve bien!
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        @unless($visorFiscalActivo)
                        <div class="col-md-4 col-12 mt-2" id="excedente">
                            <label class="form-label">Salario Diario Excedente</label>
                            <input type="text" name="excedente" id="excedente" class="form-control text"
                                placeholder="00.00" value="0.00" maxlength="8" required />
                            <div class="valid-feedback">
                                ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                            </div>
                        </div>
                        @else
                        <input type="hidden" name="excedente" value="0.00">
                        @endunless

                        {{-- <div class="col-md-4 col-12 mt-2" id="Efectivo">
                            <label class="form-label">Salario Diario Efectivo</label>
                            <input type="text" name="efectivo" id="efectivo" class="form-control text"
                                placeholder="00.00"maxlength="8" value="0.00" required />
                            <div class="valid-feedback">
                                ¡Se ve bien!
                            </div>
                            <div class="invalid-feedback">
                                Por favor, completa la información requerida.
                            </div>
                        </div> --}}
                    </div>

                    <div class="empleado-infonavit-toggle mt-4 mb-4">
                        <div class="form-check form-switch d-flex align-items-center justify-content-center gap-2 mb-0">
                            <input class="form-check-input" type="checkbox" id="terminos" value="1" onclick="chekinfonavit(this)">
                            <label class="form-check-label fw-semibold mb-0" for="terminos">Aplicar crédito Infonavit</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 col-6 mt-2" id="tipo_descuento_infonavit">
                            <div class="form-outline">
                                <label class="form-label">Tipo de descuento infonavit</label>
                                <select class="form-select mb-3" name="tipo_infonavit">
                                    @foreach ($vartipodescinfo as $obtenertipo)
                                        @if ($obtenertipo->Nombre == 'N/A')
                                            <option value="{{ $obtenertipo->id }}" selected>
                                                {{ $obtenertipo->Nombre }}</option>
                                        @else
                                            <option value="{{ $obtenertipo->id }}">{{ $obtenertipo->Nombre }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4 col-6 mt-2" id="numero_credito_infonavit">
                            <div class="form-outline">
                                <label class="form-label">Numero de credito infonavit</label>
                                <input type="text" name="numero_credito_infonavit"
                                    class="form-control text"value="0" maxlength="8" required />
                            </div>
                        </div>

                        <div class="col-md-4 col-6 mt-2" id="factor_sua">
                            <div class="form-outline">
                                <label class="form-label">Cuota fija</label>
                                <input type="text" name="factor_sua" class="form-control text" value="0.00"
                                    maxlength="8" required />
                            </div>
                        </div>

                        {{-- <div class="col-md-3 col-6 mt-2" id="descuento_quincenal">
                            <div class="form-outline">
                                <label class="form-label">Descuento quincenal</label>
                                <input type="text" name="descuento_quincenal" class="form-control text"
                                    value="0.00" maxlength="8" required />
                            </div>
                        </div> --}}
                    </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="empleado-ingreso-footer border-top bg-white px-4 py-3">
                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-baseColor px-4">
                        <i class="fa-solid fa-check"></i> Guardar empleado
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
    function toggleContratacionIngreso() {
        var tipo = document.getElementById('tipo_contratacion_ingreso').value;
        var wrap = document.getElementById('fecha_determinado_ingreso_wrap');
        var fechaInput = document.getElementById('fecha_determinado_ingreso');

        if (tipo === 'DEFINIDA') {
            wrap.style.display = '';
            fechaInput.required = true;
        } else {
            wrap.style.display = 'none';
            fechaInput.required = false;
            fechaInput.value = '';
        }
    }

    $("#cmbempresas").change(function(){
        var id = $(this).val(); 
        var empresa = $(this).find('option:selected').text(); 
        var tipo = empresa.substring(0,8);
        var visorFiscalActivo = {{ $visorFiscalActivo ? 'true' : 'false' }};
    
        if(tipo == "Efectivo")
        {
            $('#Efectivo').show();
            $('#excedente').hide();
            $('#salario_fijo').hide();
        }
        else if (visorFiscalActivo)
        {
            $('#Efectivo').hide();
            $('#excedente').hide();
            $('#salario_fijo').show();
        }
        else
        {
            $('#Efectivo').hide();
            $('#excedente').show();
            $('#salario_fijo').show();
        }
    });

    $('#tipo_descuento_infonavit').hide();
    $('#factor_sua').hide();
    $('#numero_credito_infonavit').hide();
    $("#factor_sua").val(0);

    function chekinfonavit(checkbox){
        //Si está marcada ejecuta la condición verdadera.
        if(checkbox.checked){
            $('#tipo_descuento_infonavit').show();
            $('#factor_sua').show();
            $('#numero_credito_infonavit').show();

        }
        //Si se ha desmarcado se ejecuta el siguiente mensaje.
        else{
            $('#tipo_descuento_infonavit').hide();
            $('#factor_sua').hide();
            $('#numero_credito_infonavit').hide();
            $("#factor_sua").val(0);
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('empleadoFormScroll');
        const progressBar = document.getElementById('empleadoFormProgress');
        const progressText = document.getElementById('empleadoFormProgressText');
        const steps = ['paso1', 'paso2', 'paso3'];

        if (!container) {
            return;
        }

        const updateProgress = () => {
            const maxScroll = container.scrollHeight - container.clientHeight;
            const percent = maxScroll > 0 ? Math.min(100, Math.round((container.scrollTop / maxScroll) * 100)) : 0;

            if (progressBar) {
                progressBar.style.width = percent + '%';
                progressBar.setAttribute('aria-valuenow', percent.toString());
            }

            if (progressText) {
                progressText.textContent = percent + '%';
            }

            steps.forEach((stepId, index) => {
                const section = document.getElementById(stepId);
                const navLink = document.querySelector(`#navbar-example2 a[href="#${stepId}"]`);
                const nextStep = steps[index + 1] ? document.getElementById(steps[index + 1]) : null;

                if (!section || !navLink) {
                    return;
                }

                const containerRect = container.getBoundingClientRect();
                const sectionTop = section.getBoundingClientRect().top - containerRect.top + container.scrollTop;
                const nextTop = nextStep
                    ? nextStep.getBoundingClientRect().top - containerRect.top + container.scrollTop
                    : container.scrollHeight;
                const isActive = container.scrollTop >= sectionTop - 24 && container.scrollTop < nextTop - 24;

                navLink.classList.toggle('active', isActive);
            });
        };

        document.querySelectorAll('#navbar-example2 a[href^="#"]').forEach((link) => {
            link.addEventListener('click', (event) => {
                const targetId = link.getAttribute('href');
                const target = targetId ? document.querySelector(targetId) : null;

                if (!target) {
                    return;
                }

                event.preventDefault();
                const containerRect = container.getBoundingClientRect();
                const targetRect = target.getBoundingClientRect();
                const scrollTop = container.scrollTop + (targetRect.top - containerRect.top) - 12;

                container.scrollTo({
                    top: scrollTop,
                    behavior: 'smooth'
                });
            });
        });

        container.addEventListener('scroll', updateProgress);
        updateProgress();
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const requiredFields = document.querySelectorAll('#offcanvasBottom form [required]');

        requiredFields.forEach((field) => {
            const wrapper = field.closest('.form-outline');
            const label = wrapper ? wrapper.querySelector('label') : null;

            if (!label) {
                return;
            }

            if (label.querySelector('.required-asterisk')) {
                return;
            }

            const asterisk = document.createElement('span');
            asterisk.className = 'required-asterisk text-danger';
            asterisk.textContent = ' *';
            label.appendChild(asterisk);
        });
    });
</script>

<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/files.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    ['offcanvasImportEmpleados', 'offcanvasImportNomina'].forEach(function(offcanvasId) {
        const el = document.getElementById(offcanvasId);
        if (!el) return;
        el.addEventListener('shown.bs.offcanvas', function() {
            if (typeof bindModernFileInputs === 'function') {
                bindModernFileInputs(el);
            }
        });
    });
    document.querySelectorAll('[id^="modalBajaFirmada"]').forEach(function(el) {
        el.addEventListener('shown.bs.modal', function() {
            if (typeof bindModernFileInputs === 'function') {
                bindModernFileInputs(el);
            }
        });
    });

    const formSua = document.getElementById('formExportarSua');
    const selectAllSua = document.getElementById('sua_seleccionar_todos');
    const buscarSua = document.getElementById('sua_buscar_empleado');
    const contadorSua = document.getElementById('sua_contador_empleados');
    const sinResultadosSua = document.getElementById('sua_sin_resultados');
    const itemsSua = () => Array.from(document.querySelectorAll('.sua-empleado-item[data-search]'));
    const checksSua = () => Array.from(document.querySelectorAll('.sua-empleado-check'));
    const checksSuaVisibles = () => checksSua().filter(function(chk) {
        const item = chk.closest('.sua-empleado-item');
        return item && !item.classList.contains('d-none');
    });
    const ayudaTrabajadores = document.getElementById('sua_ayuda_trabajadores');
    const ayudaAfiliatorios = document.getElementById('sua_ayuda_afiliatorios');
    const ayudaMovimientos = document.getElementById('sua_ayuda_movimientos');
    const ayudaCredito = document.getElementById('sua_ayuda_credito');
    const ayudaIncapacitados = document.getElementById('sua_ayuda_incapacitados');
    const panelMovimientos = document.getElementById('sua_panel_movimientos');
    const panelCredito = document.getElementById('sua_panel_credito');
    const panelIncapacitados = document.getElementById('sua_panel_incapacitados');
    const tipoMovimientoSelect = document.getElementById('sua_tipo_movimiento');

    function tipoExportacionSua() {
        const checked = document.querySelector('input[name="tipo_exportacion"]:checked');
        return checked ? checked.value : 'trabajadores';
    }

    function actualizarCamposMovimientoSua() {
        const tipoMov = tipoMovimientoSelect ? tipoMovimientoSelect.value : '02';
        const showFolio = tipoMov === '12';
        const showDias = tipoMov === '11' || tipoMov === '12';
        const showSdi = tipoMov === '07' || tipoMov === '08';

        document.querySelectorAll('.sua-field-folio').forEach(function(el) {
            el.classList.toggle('d-none', !showFolio);
        });
        document.querySelectorAll('.sua-field-dias').forEach(function(el) {
            el.classList.toggle('d-none', !showDias);
        });
        document.querySelectorAll('.sua-field-sdi').forEach(function(el) {
            el.classList.toggle('d-none', !showSdi);
        });

        aplicarDatosMovimientoSua(tipoMov);
        filtrarEmpleadosSua();
    }

    function aplicarDatosMovimientoSua(tipoMov) {
        itemsSua().forEach(function(item) {
            const fechaInput = item.querySelector('input[name^="fecha_movimiento["]');
            const folioInput = item.querySelector('input[name^="folio_incapacidad["]');
            const diasInput = item.querySelector('input[name^="dias_incidencia["]');

            if (tipoMov === '02') {
                if (fechaInput && item.dataset.fechaBaja) {
                    fechaInput.value = item.dataset.fechaBaja;
                }
            } else if (tipoMov === '12') {
                if (fechaInput && item.dataset.fechaIncapacidad) {
                    fechaInput.value = item.dataset.fechaIncapacidad;
                }
                if (folioInput && item.dataset.folioIncapacidad) {
                    folioInput.value = item.dataset.folioIncapacidad;
                }
                if (diasInput && item.dataset.diasIncapacidad) {
                    diasInput.value = item.dataset.diasIncapacidad;
                }
            }
        });
    }

    function actualizarUiTipoSua() {
        const tipo = tipoExportacionSua();
        const esTrabajadores = tipo === 'trabajadores';
        const esMovimientos = tipo === 'movimientos';
        const esCredito = tipo === 'credito';
        const esIncapacitados = tipo === 'incapacitados';

        document.querySelectorAll('.sua-campos-trabajadores').forEach(function(el) {
            el.classList.toggle('d-none', !esTrabajadores);
        });
        document.querySelectorAll('.sua-campos-movimientos').forEach(function(el) {
            el.classList.toggle('d-none', !esMovimientos);
        });
        document.querySelectorAll('.sua-campos-credito').forEach(function(el) {
            el.classList.toggle('d-none', !esCredito);
        });
        document.querySelectorAll('.sua-campos-incapacitados').forEach(function(el) {
            el.classList.toggle('d-none', !esIncapacitados);
        });
        if (panelMovimientos) panelMovimientos.classList.toggle('d-none', !esMovimientos);
        if (panelCredito) panelCredito.classList.toggle('d-none', !esCredito);
        if (panelIncapacitados) panelIncapacitados.classList.toggle('d-none', !esIncapacitados);
        if (ayudaTrabajadores) ayudaTrabajadores.classList.toggle('d-none', !esTrabajadores);
        if (ayudaAfiliatorios) ayudaAfiliatorios.classList.toggle('d-none', tipo !== 'afiliatorios');
        if (ayudaMovimientos) ayudaMovimientos.classList.toggle('d-none', !esMovimientos);
        if (ayudaCredito) ayudaCredito.classList.toggle('d-none', !esCredito);
        if (ayudaIncapacitados) ayudaIncapacitados.classList.toggle('d-none', !esIncapacitados);

        if (esMovimientos) {
            actualizarCamposMovimientoSua();
        } else {
            if (esCredito) {
                aplicarDatosCreditoSua();
            }
            filtrarEmpleadosSua();
        }
    }

    function aplicarDatosCreditoSua() {
        itemsSua().forEach(function(item) {
            const inputCredito = item.querySelector('.sua-input-credito');
            if (inputCredito && item.dataset.numeroCredito) {
                inputCredito.value = item.dataset.numeroCredito;
            }
            const selectTipo = item.querySelector('.sua-select-tipo-desc');
            if (selectTipo && item.dataset.tipoDescuento && item.dataset.tipoDescuento !== '0') {
                selectTipo.value = item.dataset.tipoDescuento;
            }
            const inputValor = item.querySelector('.sua-input-valor-desc');
            if (inputValor && item.dataset.valorDescuento) {
                inputValor.value = item.dataset.valorDescuento;
            }
        });
    }

    function actualizarContadorSua() {
        const visibles = checksSuaVisibles();
        const seleccionados = checksSua().filter(function(c) { return c.checked; }).length;
        if (contadorSua) {
            contadorSua.textContent = visibles.length + ' visibles · ' + seleccionados + ' seleccionados';
        }
        if (selectAllSua) {
            selectAllSua.checked = visibles.length > 0 && visibles.every(function(c) { return c.checked; });
            selectAllSua.indeterminate = !selectAllSua.checked && visibles.some(function(c) { return c.checked; });
        }
        if (sinResultadosSua) {
            sinResultadosSua.classList.toggle('d-none', itemsSua().some(function(item) {
                return !item.classList.contains('d-none');
            }) || itemsSua().length === 0);
        }
    }

    function filtrarEmpleadosSua() {
        const q = (buscarSua ? buscarSua.value : '').trim().toLowerCase();
        const tipo = tipoExportacionSua();
        const tipoMov = tipoMovimientoSelect ? tipoMovimientoSelect.value : '';

        itemsSua().forEach(function(item) {
            const hayBusqueda = !q || String(item.dataset.search || '').includes(q);
            let hayContexto = true;

            const esActivo = String(item.dataset.estado || '').toUpperCase() === 'A';
            const esInactivo = String(item.dataset.estado || '').toUpperCase() === 'I';

            if (tipo === 'trabajadores' || tipo === 'afiliatorios') {
                hayContexto = esActivo;
            } else if (tipo === 'movimientos') {
                if (tipoMov === '02') {
                    // Baja: solo tblempleados.estado = I
                    hayContexto = esInactivo;
                } else if (tipoMov === '12') {
                    // Incapacidad: activos con incapacidad
                    hayContexto = esActivo && item.dataset.tieneIncapacidad === '1';
                } else {
                    // 07, 08, 11, etc.: solo activos
                    hayContexto = esActivo;
                }
            } else if (tipo === 'credito') {
                hayContexto = esActivo && item.dataset.tieneCredito === '1';
            } else if (tipo === 'incapacitados') {
                hayContexto = esActivo && item.dataset.tieneIncapacidad === '1';
            }

            const visible = hayBusqueda && hayContexto;
            item.classList.toggle('d-none', !visible);

            if (!visible) {
                const chk = item.querySelector('.sua-empleado-check');
                if (chk) chk.checked = false;
            }
        });
        actualizarContadorSua();
    }

    document.querySelectorAll('input[name="tipo_exportacion"]').forEach(function(radio) {
        radio.addEventListener('change', actualizarUiTipoSua);
    });
    if (tipoMovimientoSelect) {
        tipoMovimientoSelect.addEventListener('change', actualizarCamposMovimientoSua);
    }
    actualizarUiTipoSua();

    document.querySelectorAll('input[name^="folio_incapacidad"]').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
        });
    });

    document.querySelectorAll('.sua-input-credito').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });
    });

    document.querySelectorAll('.sua-input-folio-incap').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 8);
        });
    });

    if (buscarSua) {
        buscarSua.addEventListener('input', filtrarEmpleadosSua);
    }

    if (selectAllSua) {
        selectAllSua.addEventListener('change', function() {
            checksSuaVisibles().forEach(function(chk) {
                chk.checked = selectAllSua.checked;
            });
            actualizarContadorSua();
        });
    }

    checksSua().forEach(function(chk) {
        chk.addEventListener('change', actualizarContadorSua);
    });

    const modalSuaEl = document.getElementById('modalExportarSua');
    if (modalSuaEl) {
        modalSuaEl.addEventListener('shown.bs.modal', function() {
            if (buscarSua) {
                buscarSua.value = '';
                filtrarEmpleadosSua();
                buscarSua.focus();
            } else {
                actualizarContadorSua();
            }
        });
    }
    actualizarContadorSua();

    if (formSua) {
        formSua.addEventListener('submit', function(e) {
            const selected = checksSua().filter(function(c) { return c.checked; });
            if (selected.length === 0) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin empleados',
                        text: 'Seleccione al menos un empleado para exportar.',
                    });
                } else {
                    alert('Seleccione al menos un empleado para exportar.');
                }
                return;
            }

            const sinRp = selected.filter(function(chk) {
                return String(chk.dataset.registroPatronal || '').length !== 11;
            });
            if (sinRp.length > 0) {
                e.preventDefault();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Registro patronal',
                        text: 'Hay empleados sin Registro Patronal IMSS de 11 caracteres en su empresa. Captúrelo en Catálogo de Empresas.',
                    });
                } else {
                    alert('Hay empleados sin Registro Patronal IMSS de 11 caracteres en su empresa. Captúrelo en Catálogo de Empresas.');
                }
                return;
            }

            // Descarga por fetch: evita superar max_input_vars enviando solo seleccionados.
            e.preventDefault();
            const btn = document.getElementById('btnExportarSua');
            if (btn) {
                btn.disabled = true;
                btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generando...';
            }

            const formData = new FormData();
            const globales = [
                'tipo_exportacion',
                'tipo_movimiento',
                'tipo_movimiento_credito',
                'exportar_todas_incapacidades',
            ];
            const csrf = formSua.querySelector('input[name="_token"]');
            if (csrf) formData.append('_token', csrf.value);

            globales.forEach(function(name) {
                const el = formSua.querySelector('[name="' + name + '"]');
                if (!el) return;
                if (el.type === 'radio') {
                    const checked = formSua.querySelector('[name="' + name + '"]:checked');
                    if (checked) formData.append(name, checked.value);
                    return;
                }
                if (el.type === 'checkbox') {
                    if (el.checked) formData.append(name, el.value || '1');
                    return;
                }
                formData.append(name, el.value);
            });

            selected.forEach(function(chk) {
                const id = String(chk.value);
                formData.append('empleados[]', id);
                const item = chk.closest('.sua-empleado-item');
                if (!item) return;
                item.querySelectorAll('input[name], select[name], textarea[name]').forEach(function(el) {
                    if (el.classList.contains('sua-empleado-check')) return;
                    if (!el.name) return;
                    if (el.disabled) return;
                    if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
                    formData.append(el.name, el.value);
                });
            });

            fetch(formSua.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/octet-stream, application/json, text/plain, */*',
                },
                credentials: 'same-origin',
            }).then(async function(response) {
                const contentType = String(response.headers.get('Content-Type') || '');
                if (!response.ok) {
                    let message = 'No se pudo exportar el archivo SUA.';
                    if (contentType.includes('application/json')) {
                        const data = await response.json();
                        message = data.message || (data.errors ? Object.values(data.errors).flat()[0] : message);
                    } else {
                        const text = await response.text();
                        if (text && text.length < 400) message = text.replace(/<[^>]+>/g, ' ').trim();
                    }
                    throw new Error(message);
                }

                if (contentType.includes('text/html') && !contentType.includes('text/plain')) {
                    throw new Error('La exportación no devolvió un archivo TXT. Revise el registro patronal y los datos SUA.');
                }

                let text = await response.text();
                // Quita warnings PHP si se filtraron al body.
                text = text.replace(/<br\s*\/?>/gi, '\n')
                    .replace(/<\/?b>/gi, '')
                    .replace(/Warning:[\s\S]*?on line\s+\d+/gi, '')
                    .replace(/^\s+/, '');
                // Conserva solo líneas de longitud fija SUA (sin HTML).
                const lineas = text.split(/\r?\n/).filter(function(line) {
                    const t = line.trim();
                    return t !== '' && !t.includes('<') && !/warning|php request startup/i.test(t);
                });
                text = lineas.join('\r\n') + (lineas.length ? '\r\n' : '');

                if (!text.trim()) {
                    throw new Error('El archivo SUA quedó vacío. Verifique los datos de los empleados seleccionados.');
                }

                let filename = response.headers.get('X-SUA-Filename') || 'SUA_exportacion.txt';
                const disposition = response.headers.get('Content-Disposition') || '';
                const match = disposition.match(/filename=\"?([^\";]+)\"?/i);
                if (match && match[1]) filename = match[1];

                const blob = new Blob([text], { type: 'text/plain;charset=ISO-8859-1' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);

                const modalEl = document.getElementById('modalExportarSua');
                if (modalEl && window.bootstrap) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }
            }).catch(function(err) {
                const message = (err && err.message) ? err.message : 'No se pudo exportar el archivo SUA.';
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', title: 'Error', text: message });
                } else {
                    alert(message);
                }
            }).finally(function() {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = btn.dataset.originalHtml || '<i class="fa-solid fa-download"></i> Descargar TXT';
                }
            });
        });
    }
});
</script>

@include('Empleados.partials.contratos-por-vencer-offcanvas')
@endsection

@section('js')
<script src="https://cdn.datatables.net/fixedcolumns/5.0.0/js/dataTables.fixedColumns.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>
<script src="{{ asset('js/tableFixed.js') }}"></script>
@endsection
