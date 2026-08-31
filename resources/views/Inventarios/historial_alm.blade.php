@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<div class="container-fluid format_page">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Movimientos de Inventarios</h2>
                        <p class="text-muted mb-0">Historial de los movimientos realizados</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="table-responsive">
            <table class="table table-stripped table-hover display" id="table">
                <thead>
                    <tr>
                        <th class="text-center fw-bold text-truncate">No.</th>
                        <th class="text-center fw-bold text-truncate">Producto</th>
                        <th class="text-center fw-bold text-truncate">Tipo Movimiento</th>
                        <th class="text-center fw-bold text-truncate">Almacen</th>
                        <th class="text-center fw-bold text-truncate">Cantidad Producto</th>
                        <th class="text-center fw-bold text-truncate">Ubicacion</th>
                        <th class="text-center fw-bold text-truncate">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($minv as $mv)
                        <tr>
                            <td class="text-center">{{$mv->numero_movimineto}}</td>
                            <td class="text-center">{{$mv->nombre}}</td>
                            <td class="text-center">
                                @if($mv->nombre_movimiento == "Transferencia")
                                    <span class="badge badge-orange fw-normal fs-9">{{$mv->nombre_movimiento}}</span>
                                @elseif($mv->nombre_movimiento == "Recepcion Tranferencia")
                                    <span class="badge badge-primary fw-normal fs-9">{{$mv->nombre_movimiento}}</span>
                                @elseif($mv->nombre_movimiento == "Transferencia entre ubicaciones")
                                    <span class="badge badge-success fw-normal fs-9">{{$mv->nombre_movimiento}}</span>
                                @else
                                    <span class="badge badge-secondary fw-normal fs-9">{{$mv->nombre_movimiento}}</span>
                                @endif
                            </td>
                            <td class="text-center">{{$mv->almacen}}</td>
                            <td class="text-center">{{$mv->cantidadp_movida}}</td>
                            <td class="text-center">{{$mv->ubicacion}}</td>
                            <td class="text-center">
                                <p class="btn border-0 text-truncate m-0 fs-8" style="max-width: 400px;" data-bs-toggle="modal" data-bs-target="#ModalDetalle{{$mv->numero_movimineto}}">
                                    <b class="text-orange"><i class="fa-solid fa-circle-info"></i></b> {{$mv->observaciones}}
                                </p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($minv as $mv)
    <div class="modal fade" id="ModalDetalle{{$mv->numero_movimineto}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row justify-content-center">
                        <div class="col-11">
                            <h6 class="text-dark mb-4">Detalle</h6>
                            <p class="text-dark text-wrap fw-normal fs-8" style="text-align: justify!important">
                                {{$mv->observaciones}}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<script src="{{ asset('js/table.js') }}"></script>
@endsection
