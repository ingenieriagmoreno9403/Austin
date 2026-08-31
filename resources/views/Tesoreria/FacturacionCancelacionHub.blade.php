@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .motivo-opt {
        display: block;
        border: 1px solid var(--card-border, rgba(17, 24, 39, 0.09));
        border-radius: 12px;
        padding: 1rem;
        height: 100%;
        text-decoration: none;
        color: inherit;
        background: #fff;
        transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .motivo-opt:hover {
        border-color: var(--primary-color, #111827);
        box-shadow: 0 0 0 2px rgba(17, 24, 39, 0.1);
        background: var(--primary-soft-bg, #f3f4f6);
        color: inherit;
    }
    .motivo-opt .clave {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--secondary-color, #374151);
    }
    .motivo-opt .rel { font-size: .8rem; }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div>
                        <div class="mb-1">
                            <a href="{{ route('facturas.index') }}" class="text-muted text-decoration-none fs-8">
                                <i class="fa-solid fa-chevron-left me-1"></i>Facturas
                            </a>
                        </div>
                        <h2 class="mb-0 text-marino fw-bold">Cancelación de CFDI</h2>
                        <p class="text-muted mb-0">Elija el motivo oficial SAT según el supuesto de aplicación.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
        <div class="card-header bg-body border-0 pb-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-file-invoice me-2"></i>Factura a cancelar
            </h5>
            <p class="text-muted fs-8 mb-0">Datos del CFDI seleccionado para cancelación.</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><strong>Folio:</strong> {{ $factura->folio ?? '—' }}</div>
                <div class="col-md-3"><strong>Fecha:</strong> {{ $factura->date ? \Carbon\Carbon::parse($factura->date)->format('d/m/Y') : '—' }}</div>
                <div class="col-md-3"><strong>Total:</strong> ${{ number_format($factura->total ?? 0, 2) }}</div>
                <div class="col-md-3"><strong>RFC:</strong> {{ $factura->reciver_rfc ?? '—' }}</div>
                <div class="col-md-6"><strong>Cliente:</strong> {{ $factura->reciver_nombre ?? '—' }}</div>
                <div class="col-12"><strong>UUID:</strong> <code>{{ $factura->Uuid ?? '—' }}</code></div>
            </div>
            @if(!($infoSat['dentro_facilidad'] ?? true))
                <div class="alert alert-danger border-0 shadow-sm rounded-4 small mt-3 mb-0">
                    <i class="fa-solid fa-circle-xmark me-2"></i>Fuera de plazo de facilidad administrativa. Cancelar puede generar multas.
                </div>
            @elseif(!($infoSat['dentro_plazo_legal'] ?? true))
                <div class="alert alert-warning border-0 shadow-sm rounded-4 small mt-3 mb-0">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Dentro de facilidad administrativa (límite {{ $infoSat['limite_facilidad'] ?? '—' }}).
                </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
        <div class="card-header bg-body border-0 pb-0">
            <h5 class="text-secondary mb-1">
                <i class="fa-solid fa-list-check me-2"></i>Seleccione el supuesto de aplicación
            </h5>
            <p class="text-muted fs-8 mb-0">Cada motivo abre su flujo guiado según la relación SAT requerida.</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <a href="{{ route('facturas.sustitucion', $factura->id) }}" class="motivo-opt">
                        <div class="clave">01 — Error con sustitución</div>
                        <div class="text-muted small mt-1">Error que requiere sustituir el CFDI. La operación sí se realiza.</div>
                        <div class="rel mt-2"><span class="badge bg-dark">Relación: SÍ (Previa)</span></div>
                        <div class="small text-muted mt-2">Flujo: timbrar sustituto (04) → cancelar original (01).</div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('facturas.cancelacion.02', $factura->id) }}" class="motivo-opt">
                        <div class="clave">02 — Error sin sustitución directa</div>
                        <div class="text-muted small mt-1">Error sin reemplazo inmediato, o RFC de otro cliente.</div>
                        <div class="rel mt-2"><span class="badge bg-secondary">Relación: SÍ (Posterior)</span></div>
                        <div class="small text-muted mt-2">Se cancela primero; un CFDI nuevo puede relacionarse después.</div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('facturas.cancelacion.03', $factura->id) }}" class="motivo-opt">
                        <div class="clave">03 — Operación no concretada</div>
                        <div class="text-muted small mt-1">La compra/servicio no se realizó. Usar con precaución.</div>
                        <div class="rel mt-2"><span class="badge bg-secondary">Relación: NO</span></div>
                        <div class="small text-muted mt-2">Cancelación simple sin CFDI relacionado.</div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="{{ route('facturas.cancelacion.04', $factura->id) }}" class="motivo-opt">
                        <div class="clave">04 — Nominativa de factura global</div>
                        <div class="text-muted small mt-1">Venta en factura global que luego pide factura individual.</div>
                        <div class="rel mt-2"><span class="badge bg-warning text-dark">Relación: SÍ (Indirecto)</span></div>
                        <div class="small text-muted mt-2">Requiere UUID del CFDI nominativo ya emitido.</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
