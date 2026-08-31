@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

<style>
    .canc-step-num {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--primary-color, #111827);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .85rem;
        flex-shrink: 0;
        margin-right: .5rem;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center header">
                <div class="d-flex align-items-center">
                    <div class="header-icon me-3">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 text-marino fw-bold">Cancelación motivo 03</h2>
                        <p class="text-muted mb-0">La operación no se concretó. Relación SAT: NO.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a class="btn btn-baseColor-light fs-7 mb-2" href="{{ route('facturas.cancelacion', $factura->id) }}">
                        <i class="fa-solid fa-arrow-left"></i> Cambiar motivo
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 alert-dismissible fade show">
            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 alert-dismissible fade show">
            <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="alert alert-danger border-0 shadow-sm rounded-4">
        <i class="fa-solid fa-triangle-exclamation me-1"></i>
        <strong>Advertencia SAT:</strong> abusar de la clave 03 puede interpretarse como simulación de operaciones.
        Úsela solo si la compra/servicio realmente no se realizó.
    </div>

    @if(!empty($resultadoCancelacion))
        <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
            <div class="card-header bg-body border-0 pb-0">
                <h5 class="mb-1 {{ ($resultadoCancelacion['ok'] ?? false) ? 'text-success' : 'text-danger' }}">
                    <i class="fa-solid {{ ($resultadoCancelacion['ok'] ?? false) ? 'fa-circle-check' : 'fa-circle-xmark' }} me-2"></i>
                    Resultado motivo 03
                </h5>
            </div>
            <div class="card-body">
                <div>Folio: <strong>{{ $resultadoCancelacion['folio'] ?? '—' }}</strong></div>
                <div class="small text-muted">UUID: <code>{{ $resultadoCancelacion['uuid'] ?? '—' }}</code></div>
                @if(!($resultadoCancelacion['ok'] ?? false))
                    <div class="text-danger small mt-2">{{ $resultadoCancelacion['mensaje'] ?? '' }}</div>
                @else
                    <a href="{{ route('facturas.index') }}" class="btn btn-baseColor mt-3">Ir a facturas</a>
                @endif
            </div>
        </div>
    @endif

    @if(empty($resultadoCancelacion) || !($resultadoCancelacion['ok'] ?? false))
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
                <div class="card-header bg-body border-0 pb-0">
                    <h5 class="text-secondary mb-1">
                        <span class="canc-step-num">1</span> CFDI a cancelar
                    </h5>
                    <p class="text-muted fs-8 mb-0">Datos del comprobante seleccionado.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Folio:</strong> {{ $factura->folio }}</div>
                        <div class="col-md-4"><strong>Total:</strong> ${{ number_format($factura->total ?? 0, 2) }}</div>
                        <div class="col-md-4"><strong>Cliente:</strong> {{ $factura->reciver_nombre }}</div>
                        <div class="col-12"><strong>UUID:</strong> <code>{{ $factura->Uuid }}</code></div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('facturas.cancelacion.03.procesar', $factura->id) }}" id="formCanc03">
                @csrf
                <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
                    <div class="card-header bg-body border-0 pb-0">
                        <h5 class="text-secondary mb-1">
                            <span class="canc-step-num">2</span> Confirmar supuesto 03
                        </h5>
                        <p class="text-muted fs-8 mb-0">Revise y confirme antes de enviar la cancelación al SAT.</p>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="confirmacion_motivo" id="c1" value="1" required>
                            <label class="form-check-label small" for="c1">
                                Confirmo que la operación <strong>no se llevó a cabo</strong> (clave 03).
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="confirmacion_sin_relacion" id="c2" value="1" required>
                            <label class="form-check-label small" for="c2">
                                Entiendo que este motivo <strong>no lleva relación</strong> con otro CFDI.
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirmacion_riesgo" id="c3" value="1" required>
                            <label class="form-check-label small" for="c3">
                                Acepto el riesgo de revisión SAT / posibles multas si el motivo no es real.
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo operativo (opcional)</label>
                            <textarea name="observaciones" class="form-control" rows="2" maxlength="500" placeholder="Ej. Cliente canceló el pedido antes de la entrega"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-lg" id="btnCanc03">
                            <i class="fa-solid fa-ban me-2"></i> Cancelar CFDI con motivo 03
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5 sticky-top" style="top:1rem">
                <div class="card-header bg-body border-0 pb-0">
                    <h5 class="text-secondary mb-1">
                        <i class="fa-solid fa-shield-halved me-2"></i> Guía motivo 03
                    </h5>
                </div>
                <div class="card-body small text-muted">
                    <ul class="ps-3 mb-0">
                        <li class="mb-2">No se relaciona con ningún CFDI.</li>
                        <li class="mb-2">No sustituye ni corrige datos: declara que no hubo operación.</li>
                        <li>Si solo hubo error de datos y la venta sí procede, use <strong>01</strong> o <strong>02</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
document.getElementById('formCanc03')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const ok = await Swal.fire({
        title: '¿Cancelar con motivo 03?',
        html: 'Confirma que <strong>la operación no se concretó</strong>. Este motivo es sensible ante el SAT.',
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Sí, cancelar', cancelButtonText: 'No',
        confirmButtonColor: '#dc2626'
    });
    if (!ok.isConfirmed) return;
    const btn = document.getElementById('btnCanc03');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cancelando...';
    this.submit();
});
</script>
@endsection
