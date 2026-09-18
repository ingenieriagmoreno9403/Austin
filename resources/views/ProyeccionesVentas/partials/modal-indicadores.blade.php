<div class="modal fade cc-modal" id="modalIndicadores" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tipo de cambio por mes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:.85rem">
                    El valor base sale del indicador del ciclo. Puedes dejarlo igual en varios meses
                    y cambiar solo los que fluctúen.
                </p>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-sm-5">
                        <label class="form-label" for="ind-tc-base">TC base (MXN/USD)</label>
                        <input id="ind-tc-base" class="form-control" type="number" step="0.01" min="0.01" placeholder="20.00">
                    </div>
                    <div class="col-sm-7 d-flex gap-2 flex-wrap">
                        <button type="button" class="cc-btn" id="ind-tc-apply-all">Aplicar base a los 12 meses</button>
                        <button type="button" class="cc-btn" id="ind-tc-reset">Restablecer base</button>
                    </div>
                </div>
                <div class="cc-tc-months" id="ind-tc-months"></div>
                <p class="text-muted mb-0 mt-3" style="font-size:.82rem">
                    Las cantidades no cambian. El importe (unidades × precio) usa el TC de cada mes al ver USD/MXN.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="cc-btn cc-btn-ink" id="ind-tc-save">Guardar TC</button>
            </div>
        </div>
    </div>
</div>
