@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid acciones-config-page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Cotizaciones de venta</h2>
                        <p class="text-muted mb-0">Alta, edición y consulta de cotizaciones comerciales.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-blue fs-7 mb-2" type="button" data-bs-toggle="modal" data-bs-target="#modalNuevaCotizacion">
                        <i class="fa-solid fa-plus"></i> Nueva cotización
                    </button>
                    <a class="btn btn-blue-light fs-7 mb-2" href="javascript:history.back()">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mt-2 mb-2 bg-body rounded-5">
        <div class="card-header text-start bg-body border-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-list-check me-2"></i>Listado de cotizaciones
            </h5>
            <p class="text-muted fs-8 mb-0">Consulta, edita o da de alta cotizaciones comerciales.</p>
        </div>
        <div class="table-responsive">
            <table class="table table-stripped table-hover display mb-0" id="table">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th>Total</th>
                        <th>Estatus</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-muted text-center py-4">Sin cotizaciones registradas.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaCotizacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-secondary"><i class="fa-solid fa-circle-plus me-2"></i>Nueva cotización</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-0 modern-form">
                <div class="row">
                    <div class="col-md-4 mt-2">
                        <label class="form-label">Cliente <span class="text-danger">*</span></label>
                        <select class="form-select">
                            <option value="">— Seleccione —</option>
                        </select>
                    </div>
                    <div class="col-md-4 mt-2">
                        <label class="form-label">Vendedor</label>
                        <select class="form-select">
                            <option value="">—</option>
                        </select>
                    </div>
                    <div class="col-md-2 mt-2">
                        <label class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="col-md-2 mt-2">
                        <label class="form-label">Vigencia</label>
                        <input type="date" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mt-2">
                        <label class="form-label">Estatus <span class="text-danger">*</span></label>
                        <select class="form-select">
                            <option value="BORRADOR" selected>Borrador</option>
                            <option value="ENVIADA">Enviada</option>
                            <option value="AUTORIZADA">Autorizada</option>
                            <option value="RECHAZADA">Rechazada</option>
                            <option value="CERRADA">Cerrada</option>
                        </select>
                    </div>
                    <div class="col-md-8 mt-2">
                        <label class="form-label">Nota</label>
                        <input type="text" class="form-control text">
                    </div>
                </div>
                <h6 class="text-marino mt-3 mb-1">Productos</h6>
                @include('Ventas.partials.lineas_detalle')
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-blue" data-bs-dismiss="modal">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/table.js') }}"></script>
@endsection
