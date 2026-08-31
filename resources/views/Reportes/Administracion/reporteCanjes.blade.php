@extends('layouts.app')
@section('content')
    <div class="container-fluid format_page Global bg-body">
        <div>
            <div class="row">
                <div class="col-md-2 col-12">
                    <div class="center">
                        <h4 class="mt-1 animate__animated animate__backInLeft">Historial de Canjes</h4>
                    </div>
                </div>

                <div class="col">
                    <div class="row rounded-3 m-1 p-4 pt-2 card-tools">
                        <div class="fs-6 fw-normal text-center text-secondary"><i class="fa-solid fa-filter"></i> Filtros
                        </div>
                        <form action="/ReportesAdministracion/HistorialCanjes" enctype="multipart/form-data"
                            class="form g-3 needs-validation mt-1 text-start" novalidate>
                            @csrf
                            <div class="row">
                                <div class="col-lg-2 col-6">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Fecha Inicio</label>
                                        <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio"
                                            value="{{ $fecha_inicio }}" required />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-2 col-6">
                                    <div class="form-outline">
                                        <label class="form-label" for="form8Example4">Fecha Fin</label>
                                        <input type="date" class="form-control" name="fecha_fin" id="fecha_fin"
                                            value="{{ $fecha_fin }}" required />
                                        <div class="valid-feedback">¡Se ve bien!</div>
                                        <div class="invalid-feedback">Por favor, completa la información requerida.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-2 col-6">
                                    <label for="exampleDataList" class="form-label">Sucursal</label>
                                    <select class="form-select" aria-label="Default select example" name="sucursal"
                                        id="sucursal">
                                        <option value="0" @if ($sucursal == null) selected @endif>TODO
                                        </option>
                                        @foreach ($sucursales as $item)
                                            <option value="{{ $item->id }}"
                                                @if ($sucursal == $item->id) selected @endif>{{ $item->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-2 col-6">
                                    <label for="exampleDataList" class="form-label">Coordinadores</label>
                                    <select class="form-select" aria-label="Default select example" name="coordinador"
                                        id="coordinador">
                                        <option value="0" @if ($coordinador == null) selected @endif>TODO
                                        </option>
                                        @foreach ($coordinadores as $item)
                                            <option value="{{ $item->id }}"
                                                @if ($coordinador == $item->id) selected @endif>
                                                {{ $item->primer_nombre . ' ' . $item->apellido_paterno }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-2 col-6">
                                    <label for="exampleDataList" class="form-label">Distribuidores</label>
                                    <select class="form-select" aria-label="Default select example" name="distribuidor"
                                        id="distribuidor">
                                        <option value="0" @if ($distribuidor == null) selected @endif>TODO
                                        </option>
                                        @foreach ($distribuidores as $item)
                                            <option value="{{ $item->id }}"
                                                @if ($distribuidor == $item->id) selected @endif>
                                                {{ $item->primer_nombre . ' ' . $item->apellido_paterno }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-2 col-6">
                                    <div class="center mt-4">
                                        <button class="btn btn-secondary fs-8"><i class="fa-solid fa-search"></i>
                                            Buscar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-0 mt-2 mb-5">
            <div class="table-responsive float-search " id="mydatatable-container">
                {{--
                @if ($permiso1 == 'exportar_reporteCanjes')
                    <table id="table2" class="table-striped table-hover" style="width: 100%;">
                    @else
                        <table id="table3" class="table-striped table-hover" style="width: 100%;">
                @endif
                --}}
                <table id="tableReportesHistorialCanjes" class="table table-light table-stripped display">
                    <thead>
                        <tr>
                            <th class="text-center fw-bold">SUCURSAL</th>
                            <th class="text-center fw-bold">COORDINADORES</th>
                            <th class="text-center fw-bold">DISTRIBUIDOR</th>
                            <th class="text-center fw-bold">CLIENTE</th>
                            <th class="text-center fw-bold">IMPORTE</th>
                            <th class="text-center fw-bold">FECHA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reporte_canjes as $item)
                            <tr>
                                <td>{{ $item->sucursal }}</td>
                                <td>{{ $item->coordinador }}</td>
                                <td>{{ $item->distribuidor }}</td>
                                <td>{{ $item->cliente }}</td>
                                <td>$ {{ number_format($item->monto, 2) }}</td>
                                <td>{{ $item->fecha_canje }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3"></th>
                            <th style="text-align: start-center">Subtotal</th>
                            <th></th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="3"></th>
                            <th style="text-align: start-center">Total</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>


    <script>
        $(document).ready(function() {
            setTimeout(() => {
                $('#sucursal').select2({
                    theme: "bootstrap-5",
                    selectionCssClass: "select2--small",
                    dropdownCssClass: "select2--small",
                });
                $('#coordinador').select2({
                    theme: "bootstrap-5",
                    selectionCssClass: "select2--small",
                    dropdownCssClass: "select2--small",
                });
                $('#distribuidor').select2({
                    theme: "bootstrap-5",
                    selectionCssClass: "select2--small",
                    dropdownCssClass: "select2--small",
                });
            }, 100);
        });
    </script>
    <script src="{{ asset('js/tableX.js') }}"></script>
    <script src="{{ asset('js/validation.js') }}"></script>
@endsection
