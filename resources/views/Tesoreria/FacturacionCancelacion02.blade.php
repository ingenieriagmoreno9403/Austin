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
                        <h2 class="mb-0 text-marino fw-bold">Cancelación motivo 02</h2>
                        <p class="text-muted mb-0">Error sin sustitución directa. Relación SAT: SÍ (Posterior).</p>
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

    @if(!empty($resultadoCancelacion))
        <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
            <div class="card-header bg-body border-0 pb-0">
                <h5 class="mb-1 {{ ($resultadoCancelacion['ok'] ?? false) ? 'text-success' : 'text-danger' }}">
                    <i class="fa-solid {{ ($resultadoCancelacion['ok'] ?? false) ? 'fa-circle-check' : 'fa-circle-xmark' }} me-2"></i>
                    Resultado motivo 02
                </h5>
            </div>
            <div class="card-body">
                <div>Folio: <strong>{{ $resultadoCancelacion['folio'] ?? '—' }}</strong></div>
                <div class="small text-muted">UUID cancelado: <code>{{ $resultadoCancelacion['uuid'] ?? '—' }}</code></div>
                @if($resultadoCancelacion['ok'] ?? false)
                    <div class="alert alert-info border-0 shadow-sm rounded-4 small mt-3 mb-0">
                        La relación es <strong>posterior</strong>: si después emite un CFDI correcto, puede relacionarlo
                        a este UUID cancelado (según el tipo de relación aplicable).
                    </div>
                    <a href="{{ route('facturas.index') }}" class="btn btn-baseColor mt-3">Ir a facturas</a>
                @else
                    <div class="text-danger small mt-2">{{ $resultadoCancelacion['mensaje'] ?? '' }}</div>
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

            <form method="POST" action="{{ route('facturas.cancelacion.02.procesar', $factura->id) }}" id="formCanc02">
                @csrf
                <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
                    <div class="card-header bg-body border-0 pb-0">
                        <h5 class="text-secondary mb-1">
                            <span class="canc-step-num">2</span> Confirmar supuesto 02
                        </h5>
                        <p class="text-muted fs-8 mb-0">Revise y confirme antes de enviar la cancelación al SAT.</p>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info border-0 shadow-sm rounded-4 small">
                            Use este motivo cuando hubo error y <strong>no</strong> va a sustituir de inmediato,
                            o cuando el RFC corresponde a <strong>otro cliente</strong> (no conviene relación previa tipo 01).
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="confirmacion_motivo" id="c1" value="1" required>
                            <label class="form-check-label small" for="c1">
                                Confirmo que el supuesto es <strong>error sin sustitución directa</strong> (clave 02).
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirmacion_relacion" id="c2" value="1" required>
                            <label class="form-check-label small" for="c2">
                                Entiendo que la relación SAT es <strong>posterior</strong>: se cancela ahora y,
                                si aplica, un CFDI nuevo se relacionará después.
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observaciones (opcional)</label>
                            <textarea name="observaciones" class="form-control" rows="2" maxlength="500" placeholder="Ej. Error de RFC; se emitirá factura al cliente correcto después"></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-lg" id="btnCanc02">
                            <i class="fa-solid fa-ban me-2"></i> Cancelar CFDI con motivo 02
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5 sticky-top" style="top:1rem">
                <div class="card-header bg-body border-0 pb-0">
                    <h5 class="text-secondary mb-1">
                        <i class="fa-solid fa-circle-info me-2"></i> Guía motivo 02
                    </h5>
                </div>
                <div class="card-body small text-muted">
                    <ul class="ps-3 mb-0">
                        <li class="mb-2">No requiere UUID de sustituto al cancelar.</li>
                        <li class="mb-2">Si necesita factura correcta después, emítala y relacione al UUID cancelado.</li>
                        <li>Si el error es corregible con sustitución inmediata del mismo cliente, use <strong>01</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
document.getElementById('formCanc02')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const ok = await Swal.fire({
        title: '¿Cancelar con motivo 02?',
        text: 'Se enviará la cancelación al SAT sin UUID de reemplazo (relación posterior).',
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Sí, cancelar', cancelButtonText: 'No'
    });
    if (!ok.isConfirmed) return;
    const btn = document.getElementById('btnCanc02');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cancelando...';
    this.submit();
});
</script>
@endsection
