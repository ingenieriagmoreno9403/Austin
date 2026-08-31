@php
    $esFabricar = $detalle->esFabricar();
    $esFlange = $orden->esFlange();
    $esConexion = $orden->esConexion();
    $etiquetaPieza = $esConexion ? 'conexión' : 'flange';
    $espNominal = (float) ($detalle->espesor ?? 0);
    $rangoEsp = \App\Models\InspeccionCalidadOrdenTrabajo::rangoEspesor($espNominal > 0 ? $espNominal : null);

    $cantidadEsperada = 0.0;
    $cantidadMaxima = 0.0;
    $origenCantidad = null;
    $productoIdLinea = (int) ($detalle->producto_id ?? 0);
    if ($esFlange) {
        if ($orden->pedido && $productoIdLinea > 0) {
            $lineaPed = $orden->pedido->detalles->firstWhere('producto_id', $productoIdLinea);
            if ($lineaPed && (float) $lineaPed->cantidad > 0) {
                $cantidadEsperada = (float) $lineaPed->cantidad;
                $origenCantidad = 'pedido';
            }
        }
    } else {
        if ($orden->longitud_objetivo_m && (float) $orden->longitud_objetivo_m > 0) {
            $cantidadEsperada = (float) $orden->longitud_objetivo_m;
            $origenCantidad = 'longitud objetivo';
        } elseif ($orden->pedido && $productoIdLinea > 0) {
            $lineaPed = $orden->pedido->detalles->firstWhere('producto_id', $productoIdLinea);
            if ($lineaPed && (float) $lineaPed->cantidad > 0) {
                $cantidadEsperada = (float) $lineaPed->cantidad;
                $origenCantidad = 'pedido';
            }
        }
    }
    if ($cantidadEsperada > 0) {
        $holgura = max($cantidadEsperada * 0.05, $esFlange ? 1.0 : 0.5);
        $cantidadMaxima = round($cantidadEsperada + $holgura, 3);
    }
    $acumuladoActual = $esFlange
        ? (float) ($detalle->piezas_producidas ?? 0)
        : (float) ($detalle->metros_producidos ?? 0);
    $restanteMax = $cantidadMaxima > 0
        ? max(round($cantidadMaxima - $acumuladoActual, 3), 0)
        : null;
    $kgMetro = (float) ($detalle->kg_metro ?? 0);
@endphp

@if ($esFabricar)
<div class="orden-linea-card">
    <div class="linea-head">
        <div class="flex-grow-1">
            <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                <span class="badge bg-primary">A fabricar</span>
                @if ($esConexion)
                    <span class="badge bg-info text-dark">Conexión</span>
                @elseif ($esFlange)
                    <span class="badge bg-warning text-dark">Flange</span>
                @endif
                @if ($detalle->producto?->sku)
                    <span class="badge bg-light text-dark border">{{ $detalle->producto->sku }}</span>
                @endif
            </div>
            <div class="fw-bold text-marino fs-6">{{ $detalle->producto?->nombre ?? '—' }}</div>
            <div class="text-muted fs-8 mt-1">
                Ø {{ $detalle->diametro ?: '—' }}
                · RD {{ $detalle->rd ?: '—' }}
                @if ($esFlange)
                    · Peso ref. {{ $detalle->kg_metro !== null ? number_format((float) $detalle->kg_metro, 3) . ' kg/pza' : '—' }}
                @else
                    · Esp. nom. {{ $espNominal > 0 ? number_format($espNominal, 3) : '—' }}
                    @if ($rangoEsp)
                        · Rango OK {{ number_format($rangoEsp['min'], 3) }} – {{ number_format($rangoEsp['max'], 3) }}
                    @endif
                    @if ($kgMetro > 0)
                        · {{ number_format($kgMetro, 4) }} kg/m
                    @endif
                @endif
            </div>
        </div>

        <div class="linea-kpis">
            <div class="linea-kpi">
                <span class="kpi-label">{{ $esFlange ? 'Esperado' : 'Esperado' }}</span>
                <span class="kpi-value">
                    @if ($cantidadEsperada > 0)
                        {{ number_format($cantidadEsperada, $esFlange ? 0 : 2) }}
                        <small class="fs-9 fw-semibold text-muted">{{ $esFlange ? 'pzas' : 'm' }}</small>
                    @else
                        —
                    @endif
                </span>
            </div>
            <div class="linea-kpi">
                <span class="kpi-label">{{ $esFlange ? 'Buenas' : 'Acumulado' }}</span>
                <span class="kpi-value">
                    @if ($esFlange)
                        {{ (int) $detalle->piezas_producidas }}
                    @else
                        {{ number_format($detalle->metros_producidos, 2) }}
                        <small class="fs-9 fw-semibold text-muted">m</small>
                    @endif
                </span>
            </div>
            <div class="linea-kpi">
                <span class="kpi-label">{{ $esFlange ? 'Restante' : 'Merma' }}</span>
                <span class="kpi-value">
                    @if ($esFlange)
                        {{ $restanteMax !== null ? number_format($restanteMax, 0) : '—' }}
                    @else
                        {{ $detalle->porcentaje_merma !== null ? number_format($detalle->porcentaje_merma, 2) . '%' : '—' }}
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div class="linea-body">
        <div class="linea-section-title">
            <i class="fa-solid {{ $esFlange ? 'fa-boxes-stacked' : 'fa-ruler-combined' }}"></i>
            {{ $esFlange
                ? ($esConexion ? 'Lotes de fabricación y calidad' : 'Lotes de inyección y calidad')
                : 'Salidas y proceso de calidad' }}
        </div>

        @forelse ($detalle->salidas as $salida)
            @php
                $insp = $salida->inspeccion;
                $estatus = $insp->estatus_calidad ?? 'MEDICION_ESPESORES';
                $badgeClass = match ($estatus) {
                    'ACEPTADO' => 'bg-success',
                    'RECHAZADO' => 'bg-danger',
                    'AJUSTAR_PARAMETROS' => 'bg-warning text-dark',
                    'CO_EXTRUSORA', 'TATUADORA' => 'bg-info text-dark',
                    default => 'bg-secondary',
                };
            @endphp
            <div class="orden-salida-card {{ $estatus === 'RECHAZADO' ? 'border-danger' : ($estatus === 'ACEPTADO' ? 'border-success' : '') }}">
                <div class="salida-head">
                    <div class="fs-8">
                        <strong>{{ $esFlange ? 'Lote' : 'Salida' }} #{{ $loop->iteration }}</strong>
                        @if ($esFlange)
                            <span class="text-muted ms-1">
                                · Buenas {{ (int) ($salida->piezas_buenas ?? 0) }}
                                · Malas {{ (int) ($salida->piezas_malas ?? 0) }}
                                @if ($salida->kg_retrabajo !== null)
                                    · Retrabajo {{ number_format((float) $salida->kg_retrabajo, 2) }} kg
                                @endif
                            </span>
                        @else
                            <span class="text-muted ms-1">
                                · {{ number_format($salida->metros, 2) }} m
                                · Kg real {{ $salida->kg_real !== null ? number_format($salida->kg_real, 3) : '—' }}
                                · Merma {{ $salida->porcentaje_merma !== null ? number_format($salida->porcentaje_merma, 2) . '%' : '—' }}
                            </span>
                        @endif
                    </div>
                    <span class="badge {{ $badgeClass }} fs-9">
                        {{ $insp?->estatus_calidad_texto ?? ($esFlange ? 'Pendiente calidad' : 'Pendiente de medición') }}
                    </span>
                </div>

                <div class="salida-body">
                    @if ($editable && $estatus !== 'ACEPTADO')
                        @if ($esFlange)
                            <form method="POST" action="{{ route('produccion.ordenes.salida.inspeccion', [$orden->id, $salida->id]) }}" class="modern-form">
                                @csrf
                                <div class="alert alert-info border-0 fs-8 d-flex gap-2 align-items-start py-2 mb-3">
                                    <i class="fa-solid fa-user-shield mt-1"></i>
                                    <div>
                                        <div class="text-uppercase fw-semibold" style="letter-spacing:.3px;">
                                            Responsable: {{ ($responsablesProceso['REVISION_CALIDAD'] ?? null) ?: 'Auditor de Calidad' }}
                                        </div>
                                        <div>Revisar malformaciones / burbujas. Las rechazadas van a reproceso.</div>
                                    </div>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Piezas buenas</label>
                                        <input type="number" min="0" name="piezas_buenas" class="form-control"
                                               value="{{ old('piezas_buenas', $salida->piezas_buenas) }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Piezas malas</label>
                                        <input type="number" min="0" name="piezas_malas" class="form-control"
                                               value="{{ old('piezas_malas', $salida->piezas_malas) }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Kg retrabajo</label>
                                        <input type="number" step="0.001" min="0" name="kg_retrabajo" class="form-control"
                                               value="{{ old('kg_retrabajo', $salida->kg_retrabajo) }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Observaciones</label>
                                        <input type="text" name="observaciones" class="form-control" maxlength="1000"
                                               value="{{ old('observaciones', $insp->observaciones ?? '') }}">
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" name="accion" value="aceptar" class="btn btn-success"
                                        onclick="return confirm('¿Aceptar este lote de {{ $etiquetaPieza }}?');">
                                        <i class="fa-solid fa-check me-1"></i> Aceptar
                                    </button>
                                    <button type="submit" name="accion" value="aceptar_con_detalle" class="btn btn-outline-warning"
                                        onclick="var o=this.form.querySelector('[name=observaciones]'); if(!o||!String(o.value||'').trim()){alert('Capture el detalle en Observaciones.'); if(o){o.focus();} return false;} return confirm('¿Aceptar CON DETALLE?');">
                                        Aceptado con detalle
                                    </button>
                                    <button type="submit" name="accion" value="rechazar" class="btn btn-outline-danger"
                                        onclick="return confirm('¿Rechazar y enviar a reproceso?');">
                                        <i class="fa-solid fa-xmark me-1"></i> Rechazar → reproceso
                                    </button>
                                </div>
                            </form>
                        @else
                        @php
                            $tol = number_format(\App\Models\InspeccionCalidadOrdenTrabajo::TOLERANCIA_ERROR_PCT, 0);
                            $pasosCalidad = [
                                'MEDICION_ESPESORES' => 'Medir 8 puntos de espesor alrededor del tubo con vernier calibrado y verificar que estén dentro de la tolerancia ±' . $tol . '%.',
                                'AJUSTAR_PARAMETROS' => 'Espesores fuera de rango. Ajustar los parámetros de extrusor y jalador y volver a medir los 8 puntos.',
                                'CO_EXTRUSORA' => 'Encender la co-extrusora (línea de identificación) y verificar que el color de línea sea el correcto según especificación.',
                                'TATUADORA' => 'Encender la tatuadora y verificar la leyenda impresa: diámetro, RD, lote, fecha y hora.',
                            ];
                            $instruccionCalidad = $pasosCalidad[$estatus] ?? $pasosCalidad['MEDICION_ESPESORES'];
                        @endphp
                        <form method="POST" action="{{ route('produccion.ordenes.salida.inspeccion', [$orden->id, $salida->id]) }}" class="modern-form">
                            @csrf

                            <div class="alert alert-info border-0 fs-8 d-flex gap-2 align-items-start py-2 mb-3">
                                <i class="fa-solid fa-user-shield mt-1"></i>
                                <div>
                                    <div class="text-uppercase fw-semibold" style="letter-spacing:.3px;">
                                        Responsable: {{ ($responsablesProceso['REVISION_CALIDAD'] ?? null) ?: 'Auditor de Calidad' }}
                                    </div>
                                    <div>{{ $instruccionCalidad }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="fw-semibold fs-8 text-secondary mb-2">
                                    1. ¿Los espesores están dentro de rango ±{{ number_format(\App\Models\InspeccionCalidadOrdenTrabajo::TOLERANCIA_ERROR_PCT, 0) }}%? (8 mediciones con vernier)
                                </div>
                                <div class="row g-2">
                                    @for ($i = 1; $i <= 8; $i++)
                                        <div class="col">
                                            <label class="form-label">E{{ $i }}</label>
                                            <input type="number" step="0.0001" min="0"
                                                class="form-control form-control-sm"
                                                name="espesor_{{ $i }}"
                                                value="{{ old('espesor_'.$i, $insp?->{'espesor_'.$i} ?? '') }}"
                                                {{ in_array($estatus, ['CO_EXTRUSORA', 'TATUADORA'], true) ? 'readonly' : '' }}
                                                required>
                                        </div>
                                    @endfor
                                </div>
                                @if ($estatus === 'AJUSTAR_PARAMETROS')
                                    <div class="alert alert-warning border-0 fs-8 py-2 mt-2 mb-0">
                                        Fuera de rango. Configure parámetros de <strong>extrusor y jalador</strong> y vuelva a medir.
                                    </div>
                                @endif
                            </div>

                            @if (in_array($estatus, ['CO_EXTRUSORA', 'TATUADORA', 'AJUSTAR_PARAMETROS', 'MEDICION_ESPESORES'], true) || !$insp)
                                @if ($estatus === 'CO_EXTRUSORA' || $estatus === 'TATUADORA')
                                    <div class="mb-3">
                                        <div class="fw-semibold fs-8 text-secondary mb-2">2. Encender co-extrusora (línea de identificación)</div>
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label">Color de línea</label>
                                                <select name="color_linea" class="form-select" {{ $estatus === 'TATUADORA' ? '' : 'required' }}>
                                                    <option value="">— Seleccione —</option>
                                                    @foreach (\App\Models\InspeccionCalidadOrdenTrabajo::$coloresLinea as $cod => $nom)
                                                        <option value="{{ $cod }}" {{ ($insp->color_linea ?? '') === $cod ? 'selected' : '' }}>{{ $nom }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($estatus === 'TATUADORA')
                                    <div class="mb-3">
                                        <div class="fw-semibold fs-8 text-secondary mb-2">3. Encender tatuadora (verificar leyenda)</div>
                                        <div class="row g-2">
                                            <div class="col-md-2">
                                                <label class="form-label">Diámetro</label>
                                                <input type="text" class="form-control" name="tatuaje_diametro"
                                                    value="{{ old('tatuaje_diametro', $insp->tatuaje_diametro ?? $salida->diametro_real ?? $detalle->diametro) }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">RD</label>
                                                <input type="text" class="form-control" name="tatuaje_rd"
                                                    value="{{ old('tatuaje_rd', $insp->tatuaje_rd ?? $salida->rd_real ?? $detalle->rd) }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Lote <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="tatuaje_lote" required
                                                    value="{{ old('tatuaje_lote', $insp->tatuaje_lote ?? '') }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Fecha</label>
                                                <input type="date" class="form-control" name="tatuaje_fecha"
                                                    value="{{ old('tatuaje_fecha', optional($insp->tatuaje_fecha)->format('Y-m-d') ?? date('Y-m-d')) }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Hora</label>
                                                <input type="time" class="form-control" name="tatuaje_hora"
                                                    value="{{ old('tatuaje_hora', $insp->tatuaje_hora ?? date('H:i')) }}">
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Observaciones</label>
                                <input type="text" class="form-control" name="observaciones" maxlength="1000"
                                    value="{{ old('observaciones', $insp->observaciones ?? '') }}">
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                @if (!$insp || in_array($estatus, ['MEDICION_ESPESORES', 'AJUSTAR_PARAMETROS'], true))
                                    <button type="submit" name="accion" value="evaluar_espesores" class="btn btn-baseColor">
                                        <i class="fa-solid fa-check-double me-1"></i> Evaluar 8 espesores
                                    </button>
                                @elseif ($estatus === 'CO_EXTRUSORA')
                                    <button type="submit" name="accion" value="co_extrusora" class="btn btn-info text-dark">
                                        <i class="fa-solid fa-paint-roller me-1"></i> Confirmar co-extrusora
                                    </button>
                                @elseif ($estatus === 'TATUADORA')
                                    <button type="submit" name="accion" value="aceptar" class="btn btn-success">
                                        <i class="fa-solid fa-stamp me-1"></i> Confirmar tatuadora y aceptar
                                    </button>
                                @endif

                                <button type="submit" name="accion" value="aceptar_con_detalle" class="btn btn-outline-warning"
                                    onclick="var o=this.form.querySelector('[name=observaciones]'); if(!o||!String(o.value||'').trim()){alert('Capture el detalle / motivo en Observaciones para aceptar aunque no cumpla.'); if(o){o.focus();} return false;} return confirm('¿Aceptar esta salida CON DETALLE aunque no cumpla la tolerancia?');">
                                    <i class="fa-solid fa-clipboard-check me-1"></i> Aceptado con detalle
                                </button>

                                <button type="submit" name="accion" value="rechazar" class="btn btn-outline-danger"
                                    onclick="return confirm('¿Rechazar esta salida como merma / no conforme?');">
                                    <i class="fa-solid fa-xmark me-1"></i> Rechazado
                                </button>
                            </div>
                        </form>
                        @endif
                    @elseif ($insp && $estatus === 'ACEPTADO')
                        <div class="fs-8 {{ str_contains((string) ($insp->observaciones ?? ''), 'ACEPTADO CON DETALLE') ? 'text-warning' : 'text-success' }}">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            @if ($esFlange)
                                @if (str_contains((string) ($insp->observaciones ?? ''), 'ACEPTADO CON DETALLE'))
                                    Lote aceptado con detalle
                                @else
                                    Lote aceptado
                                @endif
                                · {{ (int) ($salida->piezas_buenas ?? 0) }} buenas
                                @if ($insp->observaciones)
                                    <div class="text-muted mt-1">{{ $insp->observaciones }}</div>
                                @endif
                            @else
                                @if (str_contains((string) ($insp->observaciones ?? ''), 'ACEPTADO CON DETALLE'))
                                    Aceptada con detalle
                                    @if (!$insp->espesores_en_rango)
                                        <span class="badge bg-warning text-dark">fuera de rango</span>
                                    @endif
                                @else
                                    Aceptada
                                @endif
                                · Color {{ $insp->color_linea ?? '—' }}
                                · Leyenda: Ø {{ $insp->tatuaje_diametro }} · RD {{ $insp->tatuaje_rd }}
                                · Lote {{ $insp->tatuaje_lote }}
                                · {{ optional($insp->tatuaje_fecha)->format('d/m/Y') }} {{ $insp->tatuaje_hora }}
                                @if ($insp->observaciones)
                                    <div class="text-muted mt-1">{{ $insp->observaciones }}</div>
                                @endif
                            @endif
                        </div>
                    @elseif ($insp && $estatus === 'RECHAZADO')
                        <div class="fs-8 text-danger fw-semibold">
                            <i class="fa-solid fa-circle-xmark me-1"></i> Rechazada por calidad → inventario de merma
                            @if ($insp->observaciones) — {{ $insp->observaciones }} @endif
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted fs-8 mb-3">
                {{ $esFlange
                    ? ($esConexion ? 'Sin avances. Registre piezas de conexión abajo.' : 'Sin avances. Registre piezas inyectadas abajo.')
                    : 'Sin salidas. Registre medidas reales abajo.' }}
            </p>
        @endforelse

        @if ($editable && in_array($orden->estatus, ['PREPARANDO_MAQUINAS', 'EN_PRODUCCION', 'REVISION_CALIDAD', 'CALENTANDO_MAQUINA'], true))
            <form action="{{ route('produccion.ordenes.detalle.salida', [$orden->id, $detalle->id]) }}" method="POST" class="modern-form orden-detalle-actions">
                @csrf
                @if ($esFlange)
                    <div class="fs-8 text-secondary fw-semibold mb-2">
                        <i class="fa-solid fa-plus me-1"></i>
                        {{ $esConexion ? 'Registrar avance de conexiones (piezas)' : 'Registrar avance de inyección (piezas)' }}
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Diám.</label>
                            <input type="text" class="form-control" name="diametro_real" maxlength="50"
                                value="{{ old('diametro_real', $detalle->diametro) }}">
                                <br>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">RD</label>
                            <input type="text" class="form-control" name="rd_real" maxlength="50"
                                value="{{ old('rd_real', $detalle->rd) }}">
                                <br>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Buenas <span class="text-danger">*</span></label>
                            <input type="number" min="0"
                                @if ($restanteMax !== null && $restanteMax > 0) max="{{ (int) $restanteMax }}" @endif
                                class="form-control" name="piezas_buenas" required>
                            @if ($restanteMax !== null)
                                <div class="form-text fs-9">Máx. total avance {{ (int) $restanteMax }} pzas</div>
                            @endif
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Malas</label>
                            <input type="number" min="0" class="form-control" name="piezas_malas" value="0">
                            <br>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Kg retrabajo</label>
                            <input type="number" step="0.001" min="0" class="form-control" name="kg_retrabajo">
                            <br>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-baseColor w-100">
                                <i class="fa-solid fa-industry"></i> Registrar avance
                            </button>
                            <br><br>
                        </div>
                    </div>
                @else
                    <div class="fs-8 text-secondary fw-semibold mb-2">
                        <i class="fa-solid fa-plus me-1"></i>Registrar salida de producción (medidas reales)
                    </div>
                    <div class="row g-2 align-items-end js-salida-tubo"
                        data-kg-metro="{{ $kgMetro }}"
                        data-restante-max="{{ $restanteMax !== null ? $restanteMax : '' }}">
                        <div class="col-md-2">
                            <label class="form-label">Diám. real</label>
                            <input type="text" class="form-control" name="diametro_real" maxlength="50"
                                value="{{ old('diametro_real', $detalle->diametro) }}">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">RD real</label>
                            <input type="text" class="form-control" name="rd_real" maxlength="50"
                                value="{{ old('rd_real', $detalle->rd) }}">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Esp. real</label>
                            <input type="number" step="0.001" min="0" class="form-control" name="espesor_real"
                                value="{{ old('espesor_real', $detalle->espesor) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Metros <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" min="0.001"
                                @if ($restanteMax !== null && $restanteMax > 0) max="{{ $restanteMax }}" @endif
                                class="form-control js-metros-salida" name="metros"
                                value="{{ old('metros') }}" required>
                            @if ($restanteMax !== null)
                                <div class="form-text fs-9">Máx. {{ number_format($restanteMax, 2) }} m</div>
                            @endif
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Kg real <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" min="0" class="form-control js-kg-real-salida"
                                name="kg_real" value="{{ old('kg_real') }}" required>
                            <div class="form-text fs-9 js-kg-teorico-hint">Teórico: —</div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ubic. embalaje</label>
                            <select class="form-select" name="ubicacion_destino_id">
                                <option value="">— Opcional —</option>
                                @foreach ($ubicaciones as $ubicacion)
                                    <option value="{{ $ubicacion->id_ubicacion }}">
                                        {{ $ubicacion->folio_interno }} — {{ $ubicacion->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-baseColor w-100"
                                @if ($restanteMax !== null && $restanteMax <= 0) disabled title="Ya se alcanzó el tope de metros del pedido" @endif>
                                <i class="fa-solid fa-industry"></i> Registrar salida
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        @endif
    </div>
</div>
@endif

@once
@push('scripts')
<script>
document.addEventListener('input', function (e) {
    var metrosInput = e.target.closest && e.target.classList.contains('js-metros-salida') ? e.target : null;
    if (!metrosInput) return;
    var wrap = metrosInput.closest('.js-salida-tubo');
    if (!wrap) return;
    var kgMetro = parseFloat(wrap.getAttribute('data-kg-metro') || '0') || 0;
    var metros = parseFloat(metrosInput.value || '0') || 0;
    var teorico = kgMetro > 0 && metros > 0 ? (metros * kgMetro) : 0;
    var maxKg = teorico > 0 ? (teorico * 1.15) : 0;
    var hint = wrap.querySelector('.js-kg-teorico-hint');
    var kgInput = wrap.querySelector('.js-kg-real-salida');
    if (hint) {
        hint.textContent = teorico > 0
            ? ('Teórico: ' + teorico.toFixed(2) + ' kg · máx +15%: ' + maxKg.toFixed(2) + ' kg')
            : 'Teórico: —';
    }
    if (kgInput && maxKg > 0) {
        kgInput.setAttribute('max', maxKg.toFixed(3));
    }
});
</script>
@endpush
@endonce
