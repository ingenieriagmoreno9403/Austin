@php
    $reg = $registroOee ?? null;
    $detalleProd = $orden->detalles->first(fn ($d) => $d->esFabricar());
    $capacidadMaq = $orden->maquina?->capacidad_pr_hora;
    $esPiezaOee = $orden->esFlange();
    $piezasBuenasOee = (int) (($piezasOee['buenas'] ?? null) ?? ($reg?->piezas_buenas ?? 0));
    $piezasMalasOee = (int) (($piezasOee['malas'] ?? null) ?? ($reg?->piezas_malas ?? 0));
    $fmtDt = function ($v) {
        if (!$v) return '';
        try {
            return \Carbon\Carbon::parse($v)->format('Y-m-d\TH:i');
        } catch (\Throwable $e) {
            return '';
        }
    };
    $oeePct = $reg?->oee_pct !== null ? number_format((float) $reg->oee_pct, 1) . '%' : '—';
@endphp

<div class="orden-oee-card" id="registro-oee">
    <div class="oee-head">
        <div class="d-flex align-items-start gap-3">
            <div class="oee-icon">
                <i class="fa-solid fa-gauge-high"></i>
            </div>
            <div>
                <h5 class="mb-1 fw-bold text-marino" style="font-size:1.05rem;">
                    Registro de producción (OEE)
                </h5>
                <p class="text-muted fs-8 mb-0">
                    @if ($esPiezaOee)
                        Piezas desde salidas; calidad = buenas ÷ (buenas+malas). Kg se estiman con peso ref. si no captura.
                    @else
                        Metros y capacidad se toman de salidas / máquina.
                    @endif
                </p>
            </div>
        </div>
        <div class="oee-score" title="Overall Equipment Effectiveness">
            <span class="score-label">OEE</span>
            <span class="score-value" id="oee-score-ro">{{ $oeePct }}</span>
        </div>
    </div>

    <div class="oee-legend">
        <span class="cap"><i class="fa-solid fa-pen me-1"></i> Azul = captura</span>
        <span class="calc"><i class="fa-solid fa-calculator me-1"></i> Negro = cálculo automático</span>
    </div>

    <div class="oee-meta">
        <div class="meta-item">
            <span class="label">Orden</span>
            <span class="value">{{ $orden->folio }}</span>
        </div>
        <div class="meta-item">
            <span class="label">Fecha</span>
            <span class="value">{{ $orden->fecha?->format('d/m/Y') }}</span>
        </div>
        <div class="meta-item">
            <span class="label">Turno</span>
            <span class="value">{{ $orden->turno?->nombre ?? '—' }}</span>
        </div>
        <div class="meta-item">
            <span class="label">Máquina</span>
            <span class="value">{{ $orden->maquina?->codigo ?? $orden->maquina?->nombre ?? '—' }}</span>
        </div>
        <div class="meta-item">
            <span class="label">Operador</span>
            <span class="value">
                @if ($orden->operador)
                    {{ $orden->operador->primer_nombre }} {{ $orden->operador->apellido_paterno }}
                @else
                    —
                @endif
            </span>
        </div>
        <div class="meta-item">
            <span class="label">Capacidad kg/h</span>
            <span class="value">{{ $capacidadMaq !== null ? number_format((float) $capacidadMaq, 2) : '—' }}</span>
        </div>
        <div class="meta-item">
            <span class="label">Producto</span>
            <span class="value">{{ $detalleProd?->producto?->sku ?? $detalleProd?->producto?->nombre ?? '—' }}</span>
        </div>
        <div class="meta-item">
            <span class="label">Ø / RD</span>
            <span class="value">{{ ($detalleProd?->diametro ?? '—') . ' / ' . ($detalleProd?->rd ?? '—') }}</span>
        </div>
        <div class="meta-item">
            <span class="label">{{ $esPiezaOee ? 'Peso kg/pza' : 'Peso kg/m' }}</span>
            <span class="value">{{ $detalleProd?->kg_metro !== null ? number_format((float) $detalleProd->kg_metro, 3) : '—' }}</span>
        </div>
        @if ($esPiezaOee)
            <div class="meta-item">
                <span class="label">Piezas buenas</span>
                <span class="value" id="oee-piezas-b-ro">{{ $piezasBuenasOee }}</span>
            </div>
            <div class="meta-item">
                <span class="label">Piezas malas</span>
                <span class="value" id="oee-piezas-m-ro">{{ $piezasMalasOee }}</span>
            </div>
        @else
            <div class="meta-item">
                <span class="label">Metros salidas</span>
                <span class="value" id="oee-metros-ro">{{ number_format((float) ($metrosOee ?? 0), 2) }}</span>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('produccion.ordenes.registro_oee', $orden->id) }}" id="form-registro-oee" class="modern-form">
        @csrf

        <div class="oee-section">
            <div class="oee-section-title">
                <i class="fa-solid fa-clock"></i> Tiempos
            </div>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label text-primary">Hora inicio</label>
                    <input type="datetime-local" class="form-control form-control-sm oee-in" name="hora_inicio" id="oee_hora_inicio"
                        value="{{ old('hora_inicio', $fmtDt($reg?->hora_inicio)) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-primary">Hora fin</label>
                    <input type="datetime-local" class="form-control form-control-sm oee-in" name="hora_fin" id="oee_hora_fin"
                        value="{{ old('hora_fin', $fmtDt($reg?->hora_fin)) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-primary">T. Programado (h)</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm oee-in" name="t_programado_h" id="oee_t_programado"
                        value="{{ old('t_programado_h', $reg?->t_programado_h) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-primary">T. Arranque (h)</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm oee-in" name="t_arranque_h" id="oee_t_arranque"
                        value="{{ old('t_arranque_h', $reg?->t_arranque_h) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">T. Paros (h)</label>
                    <input type="text" class="form-control form-control-sm" id="oee_t_paros" readonly
                        value="{{ number_format((float) ($reg?->t_paros_h ?? $orden->paros->sum('duracion_h')), 3) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">T. Operando (h)</label>
                    <input type="text" class="form-control form-control-sm" id="oee_t_operando" readonly value="{{ $reg?->t_operando_h !== null ? number_format((float) $reg->t_operando_h, 3) : '—' }}">
                </div>
            </div>
        </div>

        <div class="oee-section">
            <div class="oee-section-title">
                <i class="fa-solid fa-boxes-stacked"></i> Material
            </div>
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label text-primary">Mat. inicial (kg)</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm oee-in" name="mat_inicial_kg" id="oee_mat_inicial"
                        value="{{ old('mat_inicial_kg', $reg?->mat_inicial_kg) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-primary">Mat. reciclado (kg)</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm oee-in" name="mat_reciclado_kg" id="oee_mat_reciclado"
                        value="{{ old('mat_reciclado_kg', $reg?->mat_reciclado_kg) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-primary">Aditivos (kg)</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm oee-in" name="aditivos_kg" id="oee_aditivos"
                        value="{{ old('aditivos_kg', $reg?->aditivos_kg) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-primary">Mat. sobrante (kg)</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm oee-in" name="mat_sobrante_kg" id="oee_mat_sobrante"
                        value="{{ old('mat_sobrante_kg', $reg?->mat_sobrante_kg) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mat. utilizado (kg)</label>
                    <input type="text" class="form-control form-control-sm" id="oee_mat_utilizado" readonly value="{{ $reg?->mat_utilizado_kg !== null ? number_format((float) $reg->mat_utilizado_kg, 3) : '—' }}">
                </div>
            </div>
        </div>

        <div class="oee-section">
            <div class="oee-section-title">
                <i class="fa-solid fa-industry"></i> Producción
            </div>
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label text-primary">Prod. bueno (kg)</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm oee-in" name="prod_bueno_kg" id="oee_bueno"
                        value="{{ old('prod_bueno_kg', $reg?->prod_bueno_kg) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-primary">Prod. defectuoso (kg)</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm oee-in" name="prod_defectuoso_kg" id="oee_defectuoso"
                        value="{{ old('prod_defectuoso_kg', $reg?->prod_defectuoso_kg) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Total producido</label>
                    <input type="text" class="form-control form-control-sm" id="oee_total" readonly value="{{ $reg?->total_producido_kg !== null ? number_format((float) $reg->total_producido_kg, 3) : '—' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">% Merma</label>
                    <input type="text" class="form-control form-control-sm" id="oee_merma" readonly value="{{ $reg?->porcentaje_merma !== null ? number_format((float) $reg->porcentaje_merma, 2).'%' : '—' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rendimiento %</label>
                    <input type="text" class="form-control form-control-sm" id="oee_rend" readonly value="{{ $reg?->rendimiento_pct !== null ? number_format((float) $reg->rendimiento_pct, 2).'%' : '—' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Prod/hora (kg/h)</label>
                    <input type="text" class="form-control form-control-sm" id="oee_prod_hora" readonly value="{{ $reg?->prod_hora_kg !== null ? number_format((float) $reg->prod_hora_kg, 3) : '—' }}">
                </div>
                @if ($esPiezaOee)
                    <div class="col-md-2">
                        <label class="form-label">Prod/hora (pzas/h)</label>
                        <input type="text" class="form-control form-control-sm" id="oee_prod_hora_pzas" readonly value="{{ $reg?->prod_hora_piezas !== null ? number_format((float) $reg->prod_hora_piezas, 3) : '—' }}">
                    </div>
                @endif
            </div>
        </div>

        <div class="oee-section">
            <div class="oee-section-title">
                <i class="fa-solid fa-chart-line"></i> OEE y costos
            </div>
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Disponibilidad %</label>
                    <input type="text" class="form-control form-control-sm" id="oee_disp" readonly value="{{ $reg?->disponibilidad_pct !== null ? number_format((float) $reg->disponibilidad_pct, 2).'%' : '—' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Rendimiento OEE %</label>
                    <input type="text" class="form-control form-control-sm" id="oee_rend_oee" readonly value="{{ $reg?->rendimiento_oee_pct !== null ? number_format((float) $reg->rendimiento_oee_pct, 2).'%' : '—' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Calidad %</label>
                    <input type="text" class="form-control form-control-sm" id="oee_calidad" readonly value="{{ $reg?->calidad_pct !== null ? number_format((float) $reg->calidad_pct, 2).'%' : '—' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">OEE %</label>
                    <input type="text" class="form-control form-control-sm fw-bold" id="oee_oee" readonly value="{{ $reg?->oee_pct !== null ? number_format((float) $reg->oee_pct, 2).'%' : '—' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-primary">Costo PEAD ($/kg)</label>
                    <input type="number" step="0.0001" min="0" class="form-control form-control-sm oee-in" name="costo_pead_kg" id="oee_costo_pead"
                        value="{{ old('costo_pead_kg', $reg?->costo_pead_kg ?? $costoPeadDefault) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Costo material ($)</label>
                    <input type="text" class="form-control form-control-sm" id="oee_costo_mat" readonly value="{{ $reg?->costo_material !== null ? '$'.number_format((float) $reg->costo_material, 2) : '—' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ $esPiezaOee ? 'Costo unit. ($/pza)' : 'Costo unit. ($/m)' }}</label>
                    <input type="text" class="form-control form-control-sm" id="oee_costo_m" readonly
                        value="{{ $esPiezaOee
                            ? ($reg?->costo_unit_pieza !== null ? '$'.number_format((float) $reg->costo_unit_pieza, 4) : '—')
                            : ($reg?->costo_unit_m !== null ? '$'.number_format((float) $reg->costo_unit_m, 4) : '—') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-primary">kWh (opcional)</label>
                    <input type="number" step="0.001" min="0" class="form-control form-control-sm oee-in" name="kwh" id="oee_kwh"
                        value="{{ old('kwh', $reg?->kwh) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">kWh/kg</label>
                    <input type="text" class="form-control form-control-sm" id="oee_kwh_kg" readonly value="{{ $reg?->kwh_kg !== null ? number_format((float) $reg->kwh_kg, 4) : '—' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-primary">Observaciones</label>
                    <input type="text" class="form-control form-control-sm" name="observaciones" maxlength="2000"
                        value="{{ old('observaciones', $reg?->observaciones) }}">
                </div>
            </div>
        </div>

        <div class="mt-2 mb-1">
            <button type="submit" class="btn btn-baseColor">
                <i class="fa-solid fa-floppy-disk"></i> Guardar registro OEE
            </button>
        </div>
    </form>

    <div class="oee-paros">
        <div class="oee-paros-title">
            <i class="fa-solid fa-pause me-1"></i> Registro de paros (CP-xx)
        </div>

        <form method="POST" action="{{ route('produccion.ordenes.paros.store', $orden->id) }}" class="modern-form row g-2 align-items-end mb-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Causa (CP-xx)</label>
                <select name="causa_paro_id" class="form-select form-select-sm" required>
                    <option value="">— Seleccione —</option>
                    @foreach ($causasParo as $cp)
                        <option value="{{ $cp->id }}">{{ $cp->clave }} — {{ $cp->nombre }} ({{ $cp->tipo_texto }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Inicio</label>
                <input type="datetime-local" name="hora_inicio" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fin</label>
                <input type="datetime-local" name="hora_fin" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Comentario</label>
                <input type="text" name="comentario" class="form-control form-control-sm" maxlength="500">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-baseColor-light w-100" title="Agregar paro">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </form>

        <div class="table-responsive oee-table">
            <table class="table table-sm table-hover align-middle mb-0 fs-8">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Causa</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th class="text-end">Duración (h)</th>
                        <th>Comentario</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orden->paros->sortByDesc('hora_inicio') as $paro)
                        <tr>
                            <td>{{ $paro->fecha?->format('d/m/Y') }}</td>
                            <td>{{ $paro->causaParo?->clave }} — {{ $paro->causaParo?->nombre }}</td>
                            <td>{{ $paro->hora_inicio?->format('d/m H:i') }}</td>
                            <td>{{ $paro->hora_fin?->format('d/m H:i') }}</td>
                            <td class="text-end">{{ number_format((float) $paro->duracion_h, 3) }}</td>
                            <td>{{ $paro->comentario ?: '—' }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('produccion.ordenes.paros.destroy', [$orden->id, $paro->id]) }}" class="modern-form"
                                    onsubmit="return confirm('¿Eliminar este paro?');">
                                    @csrf
                                    <button class="btn btn-outline-danger btn-sm py-0 px-1" type="submit"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted text-center py-3">Sin paros registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    const capacidad = {{ (float) ($capacidadMaq ?? 0) }};
    const metros = {{ (float) ($metrosOee ?? 0) }};
    const esPieza = {{ $esPiezaOee ? 'true' : 'false' }};
    const piezasB = {{ $piezasBuenasOee }};
    const piezasM = {{ $piezasMalasOee }};
    const pesoPza = {{ (float) ($detalleProd?->kg_metro ?? 0) }};
    const n = (id) => {
        const el = document.getElementById(id);
        const v = parseFloat(el && el.value);
        return isNaN(v) ? 0 : v;
    };
    const set = (id, val, suffix) => {
        const el = document.getElementById(id);
        if (!el) return;
        if (val === null || val === undefined || isNaN(val)) { el.value = '—'; return; }
        el.value = (typeof val === 'number' ? val.toFixed(suffix === '%' ? 2 : 3) : val) + (suffix || '');
    };
    const setMoney = (id, val, dec) => {
        const el = document.getElementById(id);
        if (!el) return;
        if (val === null || isNaN(val)) { el.value = '—'; return; }
        el.value = '$' + val.toFixed(dec || 2);
    };
    const setScore = (val) => {
        const el = document.getElementById('oee-score-ro');
        if (!el) return;
        if (val === null || val === undefined || isNaN(val)) {
            el.textContent = '—';
            return;
        }
        el.textContent = val.toFixed(1) + '%';
    };

    function recalc() {
        let tProg = n('oee_t_programado');
        const inicio = document.getElementById('oee_hora_inicio')?.value;
        const fin = document.getElementById('oee_hora_fin')?.value;
        if ((!tProg || tProg <= 0) && inicio && fin) {
            const a = new Date(inicio), b = new Date(fin);
            if (!isNaN(a) && !isNaN(b) && b > a) {
                tProg = (b - a) / 36e5;
            }
        }
        const tArr = n('oee_t_arranque');
        const tParos = n('oee_t_paros');
        const tOp = Math.max(0, tProg - tArr - tParos);
        set('oee_t_operando', tProg > 0 || tParos > 0 ? tOp : null);

        const matU = n('oee_mat_inicial') + n('oee_mat_reciclado') + n('oee_aditivos') - n('oee_mat_sobrante');
        set('oee_mat_utilizado', matU);

        let bueno = n('oee_bueno');
        let def = n('oee_defectuoso');
        if (esPieza && pesoPza > 0) {
            const buenoEl = document.getElementById('oee_bueno');
            const defEl = document.getElementById('oee_defectuoso');
            if (buenoEl && !buenoEl.value) bueno = piezasB * pesoPza;
            if (defEl && !defEl.value) def = piezasM * pesoPza;
        }
        const total = bueno + def;
        const piezasTot = piezasB + piezasM;
        set('oee_total', total);
        if (esPieza && piezasTot > 0) {
            set('oee_merma', (piezasM / piezasTot) * 100, '%');
        } else {
            set('oee_merma', total > 0 ? (def / total) * 100 : null, '%');
        }
        set('oee_rend', matU > 0 ? (bueno / matU) * 100 : null, '%');
        const prodH = tOp > 0 ? bueno / tOp : null;
        set('oee_prod_hora', prodH);
        if (esPieza) {
            set('oee_prod_hora_pzas', tOp > 0 && piezasB > 0 ? piezasB / tOp : null);
        }

        const disp = tProg > 0 ? (tOp / tProg) * 100 : null;
        const rendOee = (prodH !== null && capacidad > 0) ? (prodH / capacidad) * 100 : null;
        const calidad = (esPieza && piezasTot > 0)
            ? (piezasB / piezasTot) * 100
            : (total > 0 ? (bueno / total) * 100 : null);
        set('oee_disp', disp, '%');
        set('oee_rend_oee', rendOee, '%');
        set('oee_calidad', calidad, '%');
        const oee = (disp !== null && rendOee !== null && calidad !== null)
            ? (disp / 100) * (rendOee / 100) * (calidad / 100) * 100 : null;
        set('oee_oee', oee, '%');
        setScore(oee);

        const pead = n('oee_costo_pead');
        const costoMat = pead > 0 ? matU * pead : null;
        setMoney('oee_costo_mat', costoMat, 2);
        if (esPieza) {
            setMoney('oee_costo_m', (costoMat !== null && piezasB > 0) ? costoMat / piezasB : null, 4);
        } else {
            setMoney('oee_costo_m', (costoMat !== null && metros > 0) ? costoMat / metros : null, 4);
        }

        const kwh = n('oee_kwh');
        set('oee_kwh_kg', (kwh > 0 && bueno > 0) ? kwh / bueno : null);
    }

    document.querySelectorAll('.oee-in').forEach(el => el.addEventListener('input', recalc));
    recalc();
})();
</script>
