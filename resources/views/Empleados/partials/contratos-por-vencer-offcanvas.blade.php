@if(isset($contratosPorVencer) && $contratosPorVencer->isNotEmpty())
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasContratosVencer"
        aria-labelledby="offcanvasContratosVencerLabel" style="width: min(420px, 100vw);">
        <div class="offcanvas-header border-bottom">
            <div>
                <h5 class="offcanvas-title text-marino fw-bold mb-1" id="offcanvasContratosVencerLabel">
                    <i class="fa-solid fa-bell text-orange me-1"></i> Contratos por vencer
                </h5>
                <p class="text-muted fs-8 mb-0">
                    {{ $contratosPorVencer->count() }} empleado{{ $contratosPorVencer->count() === 1 ? '' : 's' }}
                    con contrato definido que vence en 7 días o menos
                </p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush">
                @foreach ($contratosPorVencer as $contrato)
                    @php
                        $dias = (int) $contrato->dias_restantes;
                        if ($dias < 0) {
                            $estadoLabel = 'Vencido hace ' . abs($dias) . ' día' . (abs($dias) === 1 ? '' : 's');
                            $estadoClass = 'badge-danger-dark';
                        } elseif ($dias === 0) {
                            $estadoLabel = 'Vence hoy';
                            $estadoClass = 'badge-danger-dark';
                        } elseif ($dias === 1) {
                            $estadoLabel = 'Vence mañana';
                            $estadoClass = 'badge-orange';
                        } elseif ($dias <= 3) {
                            $estadoLabel = 'Vence en ' . $dias . ' días';
                            $estadoClass = 'badge-orange';
                        } else {
                            $estadoLabel = 'Vence en ' . $dias . ' días';
                            $estadoClass = 'badge-warning text-dark';
                        }
                    @endphp
                    <div class="list-group-item py-3 px-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ $contrato->nombre }}</div>
                                <div class="text-muted fs-8 text-truncate">{{ $contrato->puesto }} · {{ $contrato->sucursal }}</div>
                            </div>
                            <span class="badge {{ $estadoClass }} fs-9 text-nowrap">{{ $estadoLabel }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <span class="fs-8 text-muted">
                                <i class="fa-regular fa-calendar me-1"></i>{{ $contrato->fecha_vencimiento_fmt }}
                            </span>
                            <a href="/Empleados/{{ $contrato->idempleado }}/edit"
                                class="btn btn-baseColor btn-sm fs-8 m-0">
                                <i class="fa-solid fa-pen"></i> Revisar
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
