@extends('layouts.app')
@section('content')
<div class="container-fluid format_page">
    <div class="row">
        <div class="col-lg-3 col-12 start-center">
            <h3 class="mt-1 animate__animated animate__backInLeft">Reporte de Gastos</h3>
            <span class="p-0 m-0 d-none d-md-block fs-8"> Gestión y control de gastos, con visualización de
                detalle.</span>
        </div>

        <div class="col-lg-2 col-12 center-end mt-2 mb-2">
            @if ($permiso1 == 'exportar_reporteGast')
                <a class="btn btn-baseColor fs-7"  href="/ExportarGastos/{{ $fecha_inicio }}/{{ $fecha_fin }}">
                    <i class="fa-solid fa-file-excel"></i> Exportar
                </a> 
            @else
                <button class="btn btn-baseColor fs-7"  disabled>
                    <i class="fa-solid fa-file-excel"></i> Exportar
                </button> 
            @endif
        </div>

        <div class="col-lg-7 col-12">
            <div class="rounded-3 m-1 p-3 pt-1 card-tools">
                <div class="fs-7 fw-normal text-start text-orange">
                    <i class="fa-solid fa-filter"></i> Filtros
                </div>

                <form action="/ReportesTesoreria/Gastos" method="GET" enctype="multipart/form-data"
                    class="text-start form needs-validation p-1" novalidate>
                    <div class="row">
                        <div class="col-md-2 col-6">
                            <div class="form-outline">
                                <label style="font-size:11px!important;">Fecha Inicio</label>
                                <input type="date" class="form-control fs-mini fs-9" name="fecha_inicio"
                                    value="{{ $fecha_inicio }}" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2 col-6">
                            <div class="form-outline">
                                <label style="font-size:11px!important;">Fecha Final</label>
                                <input type="date" class="form-control fs-mini fs-9" name="fecha_fin"
                                    value="{{ $fecha_fin }}" required>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-6">
                            <div class="form-outline">
                                <label style="font-size:11px!important;">Sucursal</label>
                                <select class="form-select fs-mini fs-9" name="sucursal" required>
                                    <option value="0" @if ($sucursalesId == 0) selected @endif>
                                        Todos</option>
                                    @foreach ($sucursales as $sucursal)
                                        <option value="{{ $sucursal->id }}"
                                            @if ($sucursalesId == $sucursal->id) selected @endif>
                                            {{ $sucursal->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-2 col-6">
                            <div class="form-outline">
                                <label style="font-size:11px!important;">Estado</label>
                                <select class="form-select fs-mini fs-9" name="estado" required>
                                    <option value="A" @if ($estado == 'A') selected @endif>
                                        Autorizado</option>
                                    <option value="E" @if ($estado == 'E') selected @endif>
                                        Pendiente</option>
                                </select>
                                <div class="valid-feedback">¡Se ve bien!</div>
                                <div class="invalid-feedback">Por favor, completa la información requerida.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-12">
                            <div class="center mt-4">
                                <button class="btn btn-baseColor-light fs-8"><i
                                        class="fa-solid fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
            
    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="tableReporteGastos">
                <thead>
                    <tr class="text-tr">
                        <th class="text-center fw-bold">Cuenta / Caja</th>
                        <th class="text-center fw-bold">Sucursal</th>
                        <th class="text-center fw-bold">No. Poliza</th>
                        <th class="text-center fw-bold">Concepto</th>
                        <th class="text-center fw-bold">Importe</th>
                        <th class="text-center fw-bold">Total</th>
                        <th class="text-center fw-bold">Nombre Gasto</th>
                        {{-- <th class="text-center fw-bold">Usuario Registrado</th> --}}
                        <th class="text-center fw-bold">Nombre de Usuario</th>
                        <th class="text-center fw-bold">Fecha de Movimiento</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($reporte_gastos as $dato)
                        <tr class="boder-sec">
                            <td class = "text-dark text-center">{{ $dato->nombre }}</td>
                            <td class = "text-dark">{{ $dato->nombre_sucursal }}</td>

                            <td class = "text-dark text-truncate">
                                @if ($dato->numero_poliza != 0)
                                    <form
                                        action="/Tesoreria/Movimientos/Poliza/{{ $dato->tipo_movimiento }}/{{ $dato->numero_poliza }}">
                                        <input type="text" name="idmov" hidden
                                            value="{{ $dato->movimiento_id }}">
                                        <input type="text" name="id1" hidden value="{{ $dato->id_tipo }}">
                                        <input type="text" name="nombre1" hidden value="{{ $dato->nombre }}">
                                        <input type="text" name="id2" hidden
                                            value="{{ $dato->numero_referencia }}">
                                        <input type="text" name="tipo1" hidden value="{{ $dato->tipo }}">
                                        <input type="text" name="tipo2" hidden value="{{ $dato->tipo }}">
                                        @if ($dato->ingreso != 0)
                                            <input type="text" name="saldo" hidden
                                                value="{{ $dato->ingreso }}">
                                        @else
                                            <input type="text" name="saldo" hidden value="{{ $dato->egreso }}">
                                        @endif
                                        <input type="text" name="saldo_inicial" hidden
                                            value="{{ $dato->saldo_inicial }}">
                                        <input type="text" name="saldo_actual" hidden
                                            value="{{ $dato->saldo_actual }}">
                                        <input type="text" name="concepto" hidden value="{{ $dato->concepto }}">
                                        <input type="text" name="descripcion" hidden
                                            value="{{ $dato->descripcion }}">
                                        <input type="text" name="usuario" hidden value="{{ $dato->created_by }}">
                                        <input type="text" name="empresa" hidden value="{{ $dato->id_empresa }}">
                                        <input type="text" name="pertenece" hidden
                                            value="{{ $dato->nombre_prestenencia }}">
                                        <button type="submit" class="btn border-0 fs-8" href=""> <i
                                                class="fa-solid fa-download fs-6 text-primary"></i>
                                            {{ $dato->numero_poliza }}</a>
                                    </form>
                                @endif
                            </td>

                            <td class = "text-dark">
                                <p class="btn border-0 fs-8 m-0 text-start text-truncate" style="max-width: 150px;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#Modalcomentario{{ $dato->movimiento_id }}">
                                    <i class="fs-6 text-primary fa-solid fa-circle-info"></i>&nbsp;&nbsp;
                                </p>
                            </td>

                            @php($total_neto = $dato->egreso)
                            @php($suma = $dato->total_iva)
                            @php($resta = $dato->total_ret_iva + $dato->total_ret_isr + $dato->total_ret_isr_resico)
                            @php($importe = $total_neto + $resta - $suma)

                            <td class = "text-dark text-truncate">$ {{ number_format($importe, 2) }}</td>
                            <td class = "text-dark text-truncate">$ {{ number_format($total_neto, 2) }}</td>
                            <td class = "text-dark">{{ $dato->nombre_gasto }}</td>
                            {{-- <td class = "text-dark">{{ $dato->usuario_registrado }}</td> --}}
                            <td class = "text-dark">{{ $dato->nombre_usuario }}</td>
                            <td class = "text-dark">{{ $dato->fecha }}</td>
                        </tr>

                        <!-- Modal -->
                        <div class="modal fade" id="Modalcomentario{{ $dato->movimiento_id }}" tabindex="-1"
                            aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title text-dark" id="exampleModalLabel">Detalles de
                                            Movimiento</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="badge">
                                            <p class="text-dark text-wrap fw-normal"
                                                style="width: 100%;text-align: start!important;font-size:12px!important;">
                                                <b class="text-dark fs-7">CONCEPTO </b><br>
                                                {{ $dato->concepto }}
                                            </p>

                                            <br>

                                            <p class="text-dark text-wrap fw-normal"
                                                style="width: 100%;text-align: start!important;font-size:12px!important;">
                                                <b class="text-dark fs-7">DESCRIPCION </b><br>
                                                {{ $dato->descripcion }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="3"></th>
                        <th>Subtotal</th>
                        <th></th>
                        <th></th>
                        <th colspan="3"></th>
                    </tr>
                    <tr>
                        <th colspan="3"></th>
                        <th>Total</th>
                        <th></th>
                        <th></th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>


<!-- Modal Reporte de Gastos-->
<div class="modal fade" id="modalGatos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark" id="exampleModalLabel">Busqueda de Gastos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/ReportesTesoreria/Gastos" enctype="multipart/form-data"
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

<script>
        $(document).ready(function() {
            var table = $('#table3').DataTable({
                "dom": 'B<"float-left"l><"float-right"f>t<"float-left"i><"float-right"p><"clearfix">',
                responsive: true,
                // columnDefs: [{ width: '20%', targets: 0 }],
                scrollCollapse: true,
                scrollY: '70vh',
                scrollX: false,
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
