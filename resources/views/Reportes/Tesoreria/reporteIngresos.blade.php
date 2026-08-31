@extends('layouts.app')
@section('content')
    <div class="social-bar">
        <a href="/ReportesTesoreria" class="iconn icon-user-tie btn border-0">&nbsp;&nbsp;<b><i
                    class="fa-solid fa-chevron-left"></i></b>&nbsp;&nbsp;&nbsp; </a>
    </div>

    <div class="container-fluid format_page bg-body Global">
        <div class="marginLeft">
            <div class="row">
                <div class="center col-lg-3 col-12">
                    <h3 class="mt-1 animate__animated animate__backInLeft">Reporte de Ingresos</h3>
                </div>

                <div class="col-lg-3 col-12 mt-1">
                    <div class="rounded-3 m-1 p-2 card-tools">
                        <p class="fs-6 fw-normal m-1 text-center text-tools"><i class="fa-solid fa-screwdriver-wrench"></i>
                            Herramientas</p>
                        <div class="row text-center p-2">
                            @if ($permiso1 == 'exportar_reporteIngr')
                                <a class="col btn push2 fs-6 btn-success rounded-3 m-2 mt-0"
                                    href="/ExportarIngresos/{{ $fecha_inicio }}/{{ $fecha_fin }}">
                                    <b class="fs-6"><i class="fa-solid fa-file-excel"></i></b><br> Exportar
                                </a>
                            @else
                                <button class="col btn push2 fs-6 btn-success rounded-3 m-2 mt-0" disabled>
                                    <b class="fs-6"><i class="fa-solid fa-file-excel"></i></b><br> Exportar
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-12 mt-1">
                    <div class="rounded-3 m-1 p-3 pt-1 card-tools row">
                        <div class="fs-8 fw-normal text-center text-tools d-none d-md-block"><i
                                class="fa-solid fa-filter"></i> Filtros</div>

                        {{-- FORMULARIO DE FILTROS --}}
                        <div class="row">
                            <form action="/ReportesTesoreria/Ingresos" method="" enctype="multipart/form-data"
                                class="text-start form needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-4 col-6">
                                        <div class="form-outline">
                                            <label for="">Fecha Inicio</label>
                                            <input type="date" class="form-control fs-mini" value="{{ $fecha_inicio }}"
                                                name="fecha_inicio" required>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-6">
                                        <div class="form-outline">
                                            <label for="">Fecha Final</label>
                                            <input type="date" class="form-control fs-mini" value="{{ $fecha_fin }}"
                                                name="fecha_fin" required>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-12">
                                        <div class="form-outline">
                                            <label>Sucursal</label>
                                            <select class="form-select" name="sucursal" id="sucursal" required>
                                                <option @if ($sucursalId == null) selected @endif value="0">
                                                    Todos</option>
                                                @foreach ($sucursales as $sucursal)
                                                    <option value="{{ $sucursal->id }}"
                                                        @if ($sucursal->id == $sucursalId) selected @endif>
                                                        {{ $sucursal->nombre }}
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

                                    <div class="col-md-4 col-12">
                                        <div class="center mt-3">
                                            <div class="btn-group">
                                                <button class="col btn btn-secondary fs-6 push2"><i
                                                        class="fa-solid fa-search"></i> Buscar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>

        <div class="border-0 bg-body mt-3">
            <div class="table-responsive marginTable" id="mydatatable-container">
                <table class="table table-light table-stripped display" id="tableReporteIngresos"
                    style="width:100%!important;">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-center fw-bold">Folio Vale</th>
                            <th class="text-center fw-bold">Cuenta Ingreso</th>
                            <th class="text-center fw-bold">Cuenta Desembolso</th>
                            <th class="text-center fw-bold">Sucursal</th>
                            <th class="text-center fw-bold">Nombre Distribuidor</th>
                            <th class="text-center fw-bold">Referencia</th>
                            <th class="text-center fw-bold">Fecha Pago</th>
                            <th class="text-center fw-bold">Pago Total</th>
                            <th class="text-center fw-bold">Importe de Entrada</th>

                            <th class="text-center fw-bold">Capital</th>
                            <th class="text-center fw-bold">Intereses</th>
                            <th class="text-center fw-bold">IVA Interes</th>
                            <th class="text-center fw-bold">Cobertura</th>
                            <th class="text-center fw-bold">IVA Cobertura</th>
                            <th class="text-center fw-bold">Otros</th>
                            <th class="text-center fw-bold">Comisión por Transacción</th>
                            <th class="text-center fw-bold">Protección de Saldo</th>
                            <th class="text-center fw-bold">Bonificación</th>
                            <th class="text-center fw-bold">Importe Condonado</th>
                            <th class="text-center fw-bold">Tipo de Pago</th>
                            <th class="text-center fw-bold">Intereses y Cobertura</th>
                            <th class="text-center fw-bold">IVA Intereses e IVA Cobertura</th>
                            <th class="text-center fw-bold">Fecha Desembolso</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($reporte_ingresos as $dato)
                            <tr class="boder-sec">
                                <td class = "text-dark">{{ $dato->folio_vale }}</td>
                                <td class = "text-dark text-start">{{ $dato->cuenta_ingreso }}</td>
                                <td class = "text-dark">{{ $dato->cuenta_desembolso }}</td>
                                <td class = "text-dark text-start">{{ $dato->sucursal }}</td>
                                <td class = "text-dark text-start">{{ $dato->nombres_dis }}</td>
                                <td class = "text-dark">{{ $dato->referencia_pago }}</td>
                                <td class = "text-dark">{{ $dato->fecha_pago }}</td>
                                <td class = "text-dark">${{ number_format($dato->pago_total, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->Importe_entrada_Cuenta, 2) }}</td>


                                <td class = "text-dark">${{ number_format($dato->capital, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->interes, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->ivainteres, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->cobertura, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->ivacobertura, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->otros, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->costo_transaccion, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->proteccion_saldo, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->comision, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->Importe_Condonado, 2) }}</td>
                                <td class = "text-dark">{{ $dato->tipo_pago }}</td>
                                <td class = "text-dark">${{ number_format($dato->interes_cobertura, 2) }}</td>
                                <td class = "text-dark">${{ number_format($dato->ivainteres_ivacobertura, 2) }} </td>
                                <td class = "text-dark">{{ $dato->fecha_desembolso }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6"></th>
                            <th>Subtotal</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        <tr>
                            <th colspan="6"></th>
                            <th>Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>



    <!-- Modal Reporte de Ingresos-->
    <div class="modal fade" id="modalIngresos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark" id="exampleModalLabel">Reporte de Ingresos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/ReportesTesoreria/Ingresos" enctype="multipart/form-data"
                    class="form g-3 needs-validation mt-1 text-start" novalidate>
                    @csrf
                    <div class="modal-body">

                        <div class="row">
                            <div class="col">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Fecha Inicio</label>
                                    <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio"
                                        required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>

                            <div class="col">
                                <div class="form-outline">
                                    <label class="form-label" for="form8Example4">Fecha Fin</label>
                                    <input type="date" class="form-control" name="fecha_fin" id="fecha_fin"
                                        required />
                                    <div class="valid-feedback">¡Se ve bien!</div>
                                    <div class="invalid-feedback">Por favor, completa la información requerida.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-danger fs-8 rounded-5" data-bs-dismiss="modal"><i
                                class="fa-solid fa-xmark"></i> Cerrar</button>
                        <button type="submit" class="btn btn-primary fs-8 rounded-5"><i
                                class="fa-solid fa-magnifying-glass"></i> Buscar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <script src="{{ asset('js/tableX.js') }}"></script>
@endsection
