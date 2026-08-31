@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@php
    $estatusClase = [
        'ACEPTADA' => 'bg-success',
        'ENVIADA' => 'bg-primary',
        'BORRADOR' => 'bg-secondary',
        'RECHAZADA' => 'bg-danger',
        'VENCIDA' => 'bg-warning text-dark',
        'CONVERTIDA' => 'bg-info text-dark',
    ];
@endphp

<div class="container-fluid acciones-config-page">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Historial de cotizaciones</h2>
                        <p class="text-muted mb-0">
                            @if ($esMaster)
                                Consulta todas las cotizaciones del sistema. Puede filtrar por vendedor.
                            @else
                                Cotizaciones registradas por su usuario.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-blue fs-7 mb-2" href="{{ route('ventas.pedidos', ['vendedor_id' => $vendedorId]) }}">
                        <i class="fa-solid fa-cart-shopping"></i> Pedidos
                    </a>
                    <a class="btn btn-blue-light fs-7 mb-2" href="javascript:history.back()">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="card border-0 shadow p-3 bg-body rounded-5">
        <form method="GET" action="{{ route('ventas.cotizaciones.historial') }}" class="row g-2 mb-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fs-8 mb-1 text-muted">Buscar</label>
                <input type="text" class="form-control form-control-sm" name="buscar" value="{{ $buscar }}"
                    placeholder="Folio, cliente u observaciones">
            </div>
            <div class="col-md-2">
                <label class="form-label fs-8 mb-1 text-muted">Estatus</label>
                <select class="form-select form-select-sm" name="estatus">
                    <option value="">Todos</option>
                    @foreach (['BORRADOR', 'ENVIADA', 'ACEPTADA', 'RECHAZADA', 'VENCIDA', 'CONVERTIDA'] as $opt)
                        <option value="{{ $opt }}" {{ $estatus === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            @if ($esMaster)
                <div class="col-md-3">
                    <label class="form-label fs-8 mb-1 text-muted">Vendedor</label>
                    <select class="form-select form-select-sm" name="vendedor_id">
                        <option value="">— Todos —</option>
                        @foreach ($vendedores as $vendedor)
                            <option value="{{ $vendedor->id }}" {{ (int) $vendedorId === (int) $vendedor->id ? 'selected' : '' }}>
                                {{ $vendedor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="vendedor_id" value="{{ auth()->id() }}">
            @endif
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-blue btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
                <a href="{{ route('ventas.cotizaciones.historial') }}" class="btn btn-blue-light btn-sm">Limpiar</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        @if ($esMaster)
                            <th>Vendedor</th>
                        @endif
                        <th>Partidas</th>
                        <th class="text-end">Total</th>
                        <th>Estatus</th>
                        <th>Archivo cliente</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cotizaciones as $cotizacion)
                        <tr>
                            <td class="fw-semibold">{{ $cotizacion->folio ?: '—' }}</td>
                            <td class="fs-8">{{ $cotizacion->fecha ? $cotizacion->fecha->format('d/m/Y H:i') : '—' }}</td>
                            <td>{{ $cotizacion->cliente->nombre ?? '—' }}</td>
                            @if ($esMaster)
                                <td class="fs-8">{{ $cotizacion->usuario->name ?? '—' }}</td>
                            @endif
                            <td>{{ $cotizacion->detalles_count }}</td>
                            <td class="text-end fw-semibold">${{ number_format($cotizacion->total, 2) }}</td>
                            <td>
                                <span class="badge {{ $estatusClase[$cotizacion->estatus] ?? 'bg-secondary' }}">
                                    {{ $cotizacion->estatus }}
                                </span>
                            </td>
                            <td>
                                @if ($cotizacion->archivo_cliente_ruta)
                                    <a href="{{ route('ventas.cotizacion.archivo_cliente', $cotizacion->id) }}"
                                        class="btn btn-sm btn-outline-primary" target="_blank" title="Descargar archivo del cliente">
                                        <i class="fa-solid fa-paperclip"></i>
                                        {{ \Illuminate\Support\Str::limit($cotizacion->archivo_cliente_nombre ?: 'Archivo', 24) }}
                                    </a>
                                @else
                                    <span class="text-muted fs-8">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end flex-wrap">
                                    <form method="POST" action="{{ route('ventas.cotizacion.cargar', $cotizacion->id) }}">
                                        @csrf
                                        <input type="hidden" name="vendedor_id" value="{{ $cotizacion->usuario_id }}">
                                        <button type="submit" class="btn btn-blue-light btn-sm">
                                            <i class="fa-solid fa-eye"></i> Ver
                                        </button>
                                    </form>
                                    <a href="{{ route('ventas.cotizacion.pdf', ['id' => $cotizacion->id, 'vendedor_id' => $cotizacion->usuario_id]) }}"
                                        class="btn btn-outline-danger btn-sm" target="_blank">
                                        <i class="fa-solid fa-file-pdf"></i> PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $esMaster ? 9 : 8 }}" class="text-center text-muted py-4">
                                No hay cotizaciones en el historial.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cotizaciones->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $cotizaciones->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
