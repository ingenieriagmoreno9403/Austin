@extends('layouts.app')
@section('content')
    @if ($permiso1 == 'exportar_reporteCierres')
        @php($table = 'table2')
    @else
        @php($table = 'table3')
    @endif



    <div class="container-fluid format_page Global bg-body posAll">
        <div>
            <div class="row">
                <div class="center">
                    <h4 class="mt-1 animate__animated animate__backInLeft">Gestión Sucursales</h4>
                </div>
            </div>
        </div>

        <div class="border-0 mt-5">
            <div class="table-responsive float-search " id="mydatatable-container">
                {{--
      @if ($permiso1 == 'exportar_reporteSuc')
        <table id="table2" class="table table-light table-stripped display" style="width: 100%;">
      @else
        <table id="table3" class="table table-light table-stripped display" style="width: 100%;">
      @endif
      --}}
                <table id="tableReportesSucursales" class="table table-light table-stripped display">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-center fw-bold">SUCURSAL</th>
                            <th class="text-center fw-bold">C. ACTIVOS</th>
                            <th class="text-center fw-bold">D. ACTIVOS</th>
                            <th class="text-center fw-bold">LINEA DE CREDITO</th>
                            <th class="text-center fw-bold">CREDITO ACTIVO</th>
                            <th class="text-center fw-bold">% CREDITO ACTIVO</th>
                            <th class="text-center fw-bold">SALDO</th>
                            <th class="text-center fw-bold">CANJES DEL DÍA</th>
                            <th class="text-center fw-bold">SALDO ATRASADO 1-7</th>
                            <th class="text-center fw-bold">SALDO EN RIESGO</th>
                            <th class="text-center fw-bold">% SALDO EN RIESGO</th>
                            <th class="text-center fw-bold">PAGO REQUERIDO CORTE</th>
                            <th class="text-center fw-bold">COBRANZA RECIBIDA</th>
                            <th class="text-center fw-bold">COMISIONES CIERRE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($saldo = 0)
                        @foreach ($consulta_1 as $item)
                            <tr class="text-tr">
                                @php($coordactivos = 0)
                                @foreach($consulta as $ite)
                                  @if($ite->idsucursal == $item->idsucursal)
                                      @php($coordactivos = $coordactivos + 1)
                                  @endif
                                @endforeach
                  
                                @php($credito_activosuc = 0)
                                @foreach($consulta_2 as $item2)
                                  @if($item2->idsucursal == $item->idsucursal)
                                      @php($credito_activosuc = $item2->credito_activosuc)
                                  @endif
                                @endforeach
                  
                                @php($prestamos = 0)
                                @php($abonados = 0)
                                @php($saldocordinador = 0)
                                @foreach($consulta_3 as $item3)
                                  @if($item3->idsuc == $item->idsucursal)
                                      @php($prestamos = $prestamos+ $item3->prestamos)
                                      @php($abonados = $abonados + $item3->abonados)
                                  @endif
                                @endforeach
                                 @php($saldocordinador = $prestamos-$abonados)
                  
                                @php($totalcanjes = 0)
                                @foreach($consulta_4 as $item4)
                                  @if($item4->idsucursal == $item->idsucursal)
                                      @php($totalcanjes = $item4->totalcanjes)
                                  @endif
                                @endforeach
                  
                                @php($saldo_atrasado = 0)
                                @php($prestamoatr = 0)
                                @php($pagadoatr = 0)
                                @foreach($consulta_5 as $item5)
                                  @if($item5->idsucursal == $item->idsucursal)
                                    @php($prestamoatr = $prestamoatr + $item5->prestamo)
                                    @php($pagadoatr = $pagadoatr + $item5->pagado)
                                  @endif
                                @endforeach
                                @php($saldo_atrasado = $prestamoatr-$pagadoatr)
                  
                                @php($saldoriesgo = 0)
                                @php($sprestamo = 0)
                                @php($spagado = 0)
                                @foreach($consulta_6 as $item6)
                                  @if($item6->idsucursal == $item->idsucursal)
                                      @php($sprestamo = $sprestamo + $item6->prestamo)
                                      @php($spagado = $spagado + $item6->pagado)
                                  @endif
                                @endforeach
                                 @php($saldoriesgo = $sprestamo-$spagado)
                  
                                @php($porcesaldoriesgo =  0)
                                @if($saldoriesgo > 0)
                                  @php($porcesaldoriesgo =  substr( $saldoriesgo / $saldocordinador,0,10))
                                @endif
                  
                                @php($saldocorte = 0)
                                @foreach($consulta_7 as $item7)
                                  @if($item7->idsucursal == $item->idsucursal)
                                      @php($saldocorte = $item7->saldocorte)
                                  @endif
                                @endforeach
                                
                                @php($pagoalcorte = 0)
                                @foreach($consulta_9 as $item9)
                                  @if($item9->idsucursal == $item->idsucursal)
                                      @php($pagoalcorte = $item9->pagoalcorte)
                                  @endif
                                @endforeach
                  
                                @php($pagocomisiones = 0)
                                @foreach($consulta_8 as $item8)
                                  @if($item8->idsucursal == $item->idsucursal)
                                      @php($pagocomisiones = $item8->pagocomisiones)
                                  @endif
                                @endforeach

                                <td>{{ $item->nombre }}</td>
                                <td>{{ $coordactivos }}</td>
                                <td>{{ $item->dvactivas }}</td>
                                <td>$ {{ number_format($item->linea_credito, 2) }}</td>
                                <td>$ {{ number_format($credito_activosuc, 2) }}</td>
                                @php($porcecredito = substr($credito_activosuc / $item->linea_credito, 0, 10))
                                <td>% {{ number_format($porcecredito * 100, 2) }}</td>
                                <td>$ {{ number_format($saldocordinador, 2) }}</td>
                                <td>$ {{ number_format($totalcanjes) }}</td>
                                <td>$ {{ number_format($saldo_atrasado, 2) }}</td>
                                <td>$ {{ number_format($saldoriesgo, 2) }}</td>
                                <td>% {{ number_format($porcesaldoriesgo * 100, 2) }}</td>
                                <td>$ {{ number_format($saldocorte, 2) }}</td>
                                <td>$ {{ number_format($pagoalcorte, 2) }}</td>
                                <td>$ {{ number_format($pagocomisiones, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2"></th>
                            <th>Subtotal</th>
                            <th></th>
                            <th></th>
                            <th></th>
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
                            <th colspan="2"></th>
                            <th>Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
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



        <style>
            #export {
                display: none;
            }
        </style>

        <script src="{{ asset('js/tableX.js') }}"></script>
        <script src="{{ asset('js/validation.js') }}"></script>
        <script>
            function exportar() {
                document.getElementById("export").click();
            }
        </script>
    @endsection
