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
    .proceso-step {
        display: flex;
        gap: .75rem;
        padding: .55rem 0;
        border-bottom: 1px dashed rgba(17, 24, 39, 0.1);
    }
    .proceso-step:last-child { border-bottom: none; }
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
                        <h2 class="mb-0 text-marino fw-bold">Cancelación motivo 04</h2>
                        <p class="text-muted mb-0">Operación nominativa de una factura global. Relación SAT: SÍ (Indirecto).</p>
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

    @if(!($infoSat['es_publico_general'] ?? false))
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            <i class="fa-solid fa-circle-info me-1"></i>
            El RFC de esta factura (<code>{{ $infoSat['rfc'] ?? '—' }}</code>) no parece público en general
            (<code>XAXX010101000</code> / <code>XEXX010101000</code>).
            El motivo 04 suele aplicarse a facturas globales. Verifique antes de continuar.
        </div>
    @endif

    @if(!empty($resultadoCancelacion))
        <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
            <div class="card-header bg-body border-0 pb-0">
                <h5 class="mb-1 {{ ($resultadoCancelacion['ok'] ?? false) ? 'text-success' : 'text-danger' }}">
                    <i class="fa-solid {{ ($resultadoCancelacion['ok'] ?? false) ? 'fa-circle-check' : 'fa-circle-xmark' }} me-2"></i>
                    Resultado motivo 04
                </h5>
            </div>
            <div class="card-body">
                <div>Folio global: <strong>{{ $resultadoCancelacion['folio'] ?? '—' }}</strong></div>
                <div class="small text-muted">UUID global: <code>{{ $resultadoCancelacion['uuid'] ?? '—' }}</code></div>
                @if(!empty($resultadoCancelacion['uuid_relacion']))
                    <div class="small text-muted mt-1">UUID nominativo: <code>{{ $resultadoCancelacion['uuid_relacion'] }}</code></div>
                @endif
                @if($resultadoCancelacion['ok'] ?? false)
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
                        <span class="canc-step-num">1</span> Factura global a cancelar
                    </h5>
                    <p class="text-muted fs-8 mb-0">Datos del comprobante global seleccionado.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><strong>Folio:</strong> {{ $factura->folio }}</div>
                        <div class="col-md-4"><strong>Total:</strong> ${{ number_format($factura->total ?? 0, 2) }}</div>
                        <div class="col-md-4"><strong>RFC:</strong> {{ $factura->reciver_rfc }}</div>
                        <div class="col-12"><strong>UUID:</strong> <code>{{ $factura->Uuid }}</code></div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('facturas.cancelacion.04.procesar', $factura->id) }}" id="formCanc04">
                @csrf
                <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5">
                    <div class="card-header bg-body border-0 pb-0">
                        <h5 class="text-secondary mb-1">
                            <span class="canc-step-num">2</span> CFDI nominativo (relación indirecta)
                        </h5>
                        <p class="text-muted fs-8 mb-0">Capture el UUID del CFDI nominativo ya timbrado.</p>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info border-0 shadow-sm rounded-4 small">
                            Orden recomendado: <strong>1)</strong> emitir la factura nominativa del cliente
                            (relacionada a la global) → <strong>2)</strong> cancelar la global con motivo <strong>04</strong>
                            indicando el UUID nominativo.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">UUID del CFDI nominativo *</label>
                            <input type="text" name="uuid_nominativo" id="uuid_nominativo" class="form-control"
                                   maxlength="36" minlength="36" required
                                   placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
                                   pattern="[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}">
                            <small class="text-muted">Debe ser el UUID de la factura individual ya timbrada.</small>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="confirmacion_motivo" id="c1" value="1" required>
                            <label class="form-check-label small" for="c1">
                                Confirmo el supuesto: venta en factura global que ahora tiene factura nominativa (clave 04).
                            </label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirmacion_relacion" id="c2" value="1" required>
                            <label class="form-check-label small" for="c2">
                                Confirmo que el UUID nominativo ya está timbrado y corresponde a esta operación (relación indirecta).
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observaciones (opcional)</label>
                            <textarea name="observaciones" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                        <button type="submit" class="btn btn-baseColor btn-lg" id="btnCanc04">
                            <i class="fa-solid fa-ban me-2"></i> Cancelar global con motivo 04
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow p-3 mt-2 mb-4 bg-body rounded-5 sticky-top" style="top:1rem">
                <div class="card-header bg-body border-0 pb-0">
                    <h5 class="text-secondary mb-1">
                        <i class="fa-solid fa-diagram-project me-2"></i> Flujo 04
                    </h5>
                </div>
                <div class="card-body small">
                    <div class="proceso-step">
                        <span class="badge bg-secondary">1</span>
                        <div>Cliente pide factura individual de una venta incluida en la global.</div>
                    </div>
                    <div class="proceso-step">
                        <span class="badge bg-secondary">2</span>
                        <div>Timbrar CFDI nominativo (con relación a la global).</div>
                    </div>
                    <div class="proceso-step">
                        <span class="badge bg-secondary">3</span>
                        <div>Cancelar la global con motivo <strong>04</strong> + UUID nominativo.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
document.getElementById('formCanc04')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const uuid = (document.getElementById('uuid_nominativo')?.value || '').trim();
    if (!/^[0-9a-fA-F-]{36}$/.test(uuid)) {
        Swal.fire('UUID inválido', 'Capture el UUID completo del CFDI nominativo (36 caracteres).', 'warning');
        return;
    }
    const ok = await Swal.fire({
        title: '¿Cancelar con motivo 04?',
        html: `Se cancelará la factura global vinculándola al UUID nominativo:<br><code>${uuid}</code>`,
        icon: 'question', showCancelButton: true,
        confirmButtonText: 'Sí, continuar', cancelButtonText: 'No'
    });
    if (!ok.isConfirmed) return;
    const btn = document.getElementById('btnCanc04');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cancelando...';
    this.submit();
});
</script>
@endsection
