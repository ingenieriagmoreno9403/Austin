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
                    <h4 class="mt-1 animate__animated animate__backInLeft">Gestión Coordinadores</h4>
                </div>
            </div>
        </div>

        <div class="border-0 mt-5">
            <div class="table-responsive float-search " id="mydatatable-container">
                {{--  
      @if ($permiso1 == 'exportar_reporteCoord')
        <table id="table2" class="table table-light table-stripped display" style="width: 100%;">
      @else
        <table id="table3" class="table table-light table-stripped display" style="width: 100%;">
      @endif
      --}}
                <table id="tableGestionCoord" class="table table-light table-stripped display">
                    <thead>
                        <tr class="text-tr">
                            <th class="text-center fw-bold">SUCURSAL</th>
                            <th class="text-center fw-bold">COORDINADOR</th>
                            <th class="text-center fw-bold">DV ACTIVAS</th>
                            <th class="text-center fw-bold">LINEA DE CREDITO</th>
                            <th class="text-center fw-bold">CREDITO ACTIVO</th>
                            <th class="text-center fw-bold">% CREDITO ACTIVO</th>
                            <th class="text-center fw-bold">SALDO</th>
                            <th class="text-center fw-bold">SALDO ATRASADO 1 A 7</th>
                            <th class="text-center fw-bold">SALDO EN RIESGO</th>
                            <th class="text-center fw-bold">% SALDO EN RIESGO</th>
                            <th class="text-center fw-bold">PAGO REQUERIDO AL CORTE</th>
                            <th class="text-center fw-bold">COBRANZA RECIBIDA</th>
                            <th class="text-center fw-bold">FECHA DE INGRESO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($saldo = 0)
                        @foreach ($consulta_1 as $item)
                            <tr class="text-tr">
                                @php($saldo_actiboxcor = 0)
                                @foreach($consulta_2 as $item2)
                                  @if($item2->id_responsable == $item->id_responsable)
                                      @php($saldo_actiboxcor = $item2->saldo_actiboxcor)
                                  @endif
                                @endforeach
                                
                                
                                @php($abono = 0)
                                @php($prestamo = 0)
                                @php($saldocordinador = 0)
                                @foreach($consulta_3 as $item3)
                                  @if($item3->id_responsable == $item->id_responsable)
                                      @php($prestamo = $prestamo + $item3->prestamo)
                                      @php($abono = $abono + $item3->abono)
                                  @endif
                                @endforeach
                                @php($saldocordinador = $prestamo - $abono )
                                
                                @php($saldo_atrasado = 0)
                                @php($prestamoatr = 0)
                                @php($pagadoatr = 0)
              
                                @foreach($consulta_4 as $item4)
                                  @if($item4->id_responsable == $item->id_responsable)
                                      @php($prestamoatr = $item4->prestamo + $prestamoatr)
                                      @php($pagadoatr = $item4->pagado + $pagadoatr)
                                  @endif
                                @endforeach
                                @php($saldo_atrasado = $prestamoatr-$pagadoatr)
                                
                                
                                @php($saldoriesgo = 0)
                                @php($spres = 0)
                                @php($spaga = 0)
                                @foreach($consulta_6 as $item6)
                                  @if($item6->id_responsable == $item->id_responsable)
                                      @php($spres = $spres + $item6->prestamo)
                                      @php($spaga = $spaga + $item6->pagado)
                                  @endif
                                @endforeach
                                @php($saldoriesgo = $spres - $spaga )
                                
                                @php($porcesaldoriesgo =  0)
                                @if($saldoriesgo > 0)
                                  @php($porcesaldoriesgo =   substr( $saldoriesgo / $saldocordinador,0,10))
                                @endif
                                
                                @php($Saldoquincenal = 0)
                                @foreach($consulta_5 as $item5)
                                  @if($item5->id_responsable == $item->id_responsable)
                                      @php($Saldoquincenal = $item5->Saldoquincenal)
                                  @endif
                                @endforeach
                                
                                @php($pagadoquincena = 0)
                                @foreach($consulta_7 as $item7)
                                  @if($item7->id_responsable == $item->id_responsable)
                                      @php($pagadoquincena = $item7->pagadoquincena)
                                  @endif
                                @endforeach


                                <td>{{ $item->nombre }}</td>
                                <td>{{ $item->nombre_cordinador }}</td>
                                <td>{{ $item->distriuidoras_activas }}</td>
                                <td class="text-truncate">$ {{ number_format($item->linea_credito, 2) }}</td>
                                <td class="text-truncate">$ {{ number_format($saldo_actiboxcor, 2) }}</td>
                                @php($porcecredit = substr($saldo_actiboxcor / $item->linea_credito, 0, 10))
                                <td class="text-truncate">% {{ number_format($porcecredit * 100, 2) }}</td>
                                <td class="text-truncate">$ {{ number_format($saldocordinador, 2) }}</td>
                                <td class="text-truncate">$ {{ number_format($saldo_atrasado, 2) }}</td>
                                <td class="text-truncate">$ {{ number_format($saldoriesgo, 2) }}</td>
                                <td class="text-truncate">% {{ number_format($porcesaldoriesgo * 100, 2) }}</td>
                                <td class="text-truncate">$ {{ number_format($Saldoquincenal, 2) }}</td>
                                <td class="text-truncate">$ {{ number_format($pagadoquincena, 2) }}</td>
                                <td>{{ $item->fecha_ingreso }}</td>
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
