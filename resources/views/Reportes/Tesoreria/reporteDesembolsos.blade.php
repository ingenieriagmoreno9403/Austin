@extends('layouts.app')
@section('content')
    @if ($mensaje = Session::get('warningFecha'))
        @php
            echo '<script language="JavaScript">
                ';
                echo
                    'swal("¡No existen Movimientos Relacionados!","Puede probar con otro rango de fechas para encontrar conincidencias","warning", {buttons: false,timer: 4500});';
                echo '
            </script>';
        @endphp
    @endif

    <div class="container-fluid format_page bg-body Global">
        <div>
            <div class="row">
                <div class="col-lg-4 col-12">
                    <h3 class="mt-1 animate__animated animate__backInLeft">Reporte de Desembolsos</h3>
                </div>

                <div class="col-lg-2 col-12 mt-2">
                    <div class="rounded-3 m-1 p-2 card-tools">
                        <p class="fs-6 fw-normal m-1 text-center text-tools"><i class="fa-solid fa-screwdriver-wrench"></i>
                            Herramientas</p>
                        <div class="row text-center p-2">
                            {{--
                            @if ($permiso1 == 'exportar_reporteGast')
                                <a href="/ExportarGastos/{{ $fecha_inicio }}/{{ $fecha_fin }}"
                                    class="col btn push2 fs-9 btn-success rounded-3 m-2 mt-0"
                                    style="font-size: 13px!important;">
                                    <b class="fs-6"><i class="fa-solid fa-file-excel"></i></b><br>Exportar
                                </a>
                            @else
                                <button class="col btn push2 fs-9 btn-success rounded-3  m-2 mt-0"
                                    style="font-size: 13px!important;" disabled>
                                    <b class="fs-6"><i class="fa-solid fa-file-excel"></i></b><br>Exportar
                                </button>
                            @endif
                            --}}
                            @if ($permiso1 == 'exportar_reporteDesem')
                                <a class="col btn push2 fs-6 btn-success rounded-3 m-2 mt-0"
                                    href="/ExportarDesembolsos/{{ $fecha_inicio }}/{{ $fecha_fin }}">
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
                        <div class="fs-6 fw-normal text-center text-tools d-none d-md-block"><i
                                class="fa-solid fa-filter"></i> Filtros</div>

                        <div class="row">
                            <form action="/ReportesTesoreria/Desembolsos" method="GET" enctype="multipart/form-data"
                                class="text-start form needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-4 col-6">
                                        <div class="form-outline">
                                            <label for="">Fecha Inicio</label>
                                            <input type="date" class="form-control fs-mini" name="fecha_inicio"
                                                value="{{ $fecha_inicio }}" required>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-6">
                                        <div class="form-outline">
                                            <label for="">Fecha Final</label>
                                            <input type="date" class="form-control fs-mini" name="fecha_fin"
                                                value="{{ $fecha_fin }}" required>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-6">
                                        <div class="form-outline">
                                            <label for="">Estado</label>
                                            <select class="form-select" name="estatus" required>
                                                <option value="A" @if ($estatus == 'A') selected @endif>
                                                    Activo</option>
                                                <option value="C" @if ($estatus == 'C') selected @endif>
                                                    Cancelado</option>
                                                <option value="F" @if ($estatus == 'F') selected @endif>
                                                    Finado</option>
                                                <option value="P" @if ($estatus == 'P') selected @endif>
                                                    Pagado</option>
                                            </select>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-6">
                                        <div class="form-outline">
                                            <label for="">Sucursal</label>
                                            <select class="form-select fs-mini" name="sucursal" required>
                                                <option value="0" @if ($sucursalId == 0) selected @endif>
                                                    Todas</option>
                                                @foreach ($sucursales as $sucursal)
                                                    <option value="{{ $sucursal->id }}"
                                                        @if ($sucursal->id == $sucursalId) selected @endif>
                                                        {{ $sucursal->nombre }}</option>
                                                @endforeach
                                            </select>
                                            <div class="valid-feedback">¡Se ve bien!</div>
                                            <div class="invalid-feedback">Por favor, completa la información requerida.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 col-12">
                                        <div class="center mt-3">
                                            <div class="btn-group">
                                                <button class="col btn btn-secondary fs-6 push2"><i
                                                        class="fa-solid fa-search"></i> Buscar</button>
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

        <div class="border-0 bg-body mt-1">
            <div class="table-responsive float-search" id="mydatatable-container">
                <table class="table table-light table-stripped display" id="tableReporteDesembolsos"
                    style="width:100%; word-wrap: break-word;">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-center fw-bold">Folio Vale</th>
                            <th class="text-center fw-bold">Sucursal</th>
                            <th class="text-center fw-bold">Cliente</th>
                            <th class="text-center fw-bold">Distribuidor</th>
                            <th class="text-center fw-bold">Cuenta / Caja</th>
                            <th class="text-center fw-bold">Referencia</th>
                            <th class="text-center fw-bold">Importe Desembolsado</th>
                            <th class="text-center fw-bold">Capital</th>
                            <th class="text-center fw-bold">Importe Cartera</th>
                            <th class="text-center fw-bold">Interes Mensual</th>
                            <th class="text-center fw-bold">Intereses</th>
                            <th class="text-center fw-bold">IVA Intereses</th>
                            <th class="text-center fw-bold">Cobertura</th>
                            <th class="text-center fw-bold">IVA Cobertura</th>
                            <th class="text-center fw-bold">Intereses y Cobertura</th>
                            <th class="text-center fw-bold">IVA Intereses y Cobertura</th>
                            <th class="text-center fw-bold">No. Plazos</th>
                            <th class="text-center fw-bold">Otros</th>
                            <th class="text-center fw-bold">Fecha</th>
                            <th class="text-center fw-bold">Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($reporte_desembolsos as $dato)
                            <tr class="boder-sec">
                                @php($totalCalcu = 0)
                                <td>{{ $dato->numero_vale }}</td>
                                <td>{{ $dato->nombre_sucursal }}</td>
                                <td>{{ $dato->nombre_cliente }}</td>
                                <td>{{ $dato->Nombre_distribuidor }}</td>
                                <td>{{ $dato->cuenta }}</td>
                                <td>{{ $dato->referencia_odp }}</td>
                                <td>${{ $formattedNum = number_format($dato->capital, 2) }}</td>
                                <td>${{ $formattedNum = number_format($dato->capital, 2) }}</td>
                                <td>${{ $formattedNum = number_format($dato->total_cartera, 2) }}
                                </td>
                                <td>{{ $dato->interesmensual }}</td>
                                {{-- @php($totalCalcu = $dato->capital+$dato->intereses+$dato->iva_intereses+$dato->otros+$dato->cobertura+$dato->iva_cobertura)
                                @php($sumaCentecimas = $dato->total_cartera - $totalCalcu) --}}

                                {{-- @php($interes = ($dato->total_cartera-$dato->capital-$dato->otros-$dato->cobertura_total)/(1.16))
                                @php($iva_intereses = (($dato->total_cartera-$dato->capital-$dato->otros-$dato->cobertura_total)/(1.16))*.16) --}}

                                <td>${{ $formattedNum = number_format($dato->intereses, 2) }}</td>
                                <td>${{ $formattedNum = number_format($dato->iva_intereses, 2) }}</td>

                                {{-- @if ($sumaCentecimas <= 0)
                                @php($sumaCentecimas = 0)
                                @endif --}}

                                <td>${{ $formattedNum = number_format($dato->cobertura, 2) }}</td>
                                <td>${{ $formattedNum = number_format($dato->iva_cobertura, 2) }}
                                </td>
                                <td>
                                    ${{ $formattedNum = number_format($dato->totalintereses_cobertura, 2) }}</td>
                                <td>
                                    ${{ $formattedNum = number_format($dato->total_iva_deintereses_cober, 2) }}</td>
                                <td>{{ $dato->numero_plazos }}</td>
                                <td>${{ $formattedNum = number_format($dato->otros, 2) }}</td>
                                <td class = "text-truncate text-dark ">{{ $dato->fecha_desemboso }}</td>
                                <td>
                                    @if ($dato->estado == 'ACTIVO')
                                        <button
                                            class="btn bg_success text-success fs-8 rounded-5 ponter_none">Activo</button>
                                    @elseif($dato->estado == 'CANCELADO')
                                        <button
                                            class="btn bg_danger text-danger fs-8 rounded-5 ponter_none">Cancelado</button>
                                    @elseif($dato->estado == 'FINADO')
                                        <button
                                            class="btn bg_secondary text-secondary fs-8 rounded-5 ponter_none">Finado</button>
                                    @elseif($dato->estado == 'PAGADO')
                                        <button
                                            class="btn bg_orange text-orange fs-8 rounded-5 ponter_none">Pagado</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="5"></th>
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
                            <th colspan="5"></th>
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

        <!-- Modal Reporte de Desembolsos-->
        <div class="modal fade" id="modalDesembolsos" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-dark" id="exampleModalLabel">Nueva Consulta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="/ReportesTesoreria/Desembolsos" enctype="multipart/form-data"
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
                            <button type="button" class="btn btn-outline-danger fs-8 rounded-5"
                                data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Cerrar</button>
                            <button type="submit" class="btn btn-primary fs-8 rounded-5"><i
                                    class="fa-solid fa-magnifying-glass"></i> Buscar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/tableX.js') }}"></script>
    <script>
        $(document).ready(function() {
            var table = $('#table3').DataTable({
                "dom": 'B<"float-left"l><"float-right"f>t<"float-left"i><"float-right"p><"clearfix">',
                responsive: true,
                // columnDefs: [{ width: '20%', targets: 0 }],
                scrollCollapse: true,
                scrollY: '70vh',
                scrollX: true,
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
                },
                paging: false,
                select: false,
                buttons: [],
            });
        });
    </script>
@endsection
