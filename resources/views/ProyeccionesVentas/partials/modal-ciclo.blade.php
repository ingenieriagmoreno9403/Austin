<div class="modal fade cc-modal" id="modalPeriodo" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="form-periodo">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-ciclo-title">Abrir nuevo ciclo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:.85rem">Identifica el ciclo, el tipo de cambio y la ventana en la que se puede capturar la proyección.</p>
                <div class="row g-3">
                    <div class="col-12"><div class="cc-form-kicker">Identidad</div></div>
                    <div class="col-md-4">
                        <label class="form-label" for="p-codigo">Código</label>
                        <input id="p-codigo" class="form-control" type="text" required placeholder="PRY-2027" maxlength="20">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="p-nombre">Nombre</label>
                        <input id="p-nombre" class="form-control" type="text" required placeholder="Proyección de ventas 2027">
                    </div>

                    <div class="col-12"><div class="cc-form-kicker">Años y parámetros</div></div>
                    <div class="col-md-6">
                        <label class="form-label" for="p-anio-ref">Año de referencia (venta real)</label>
                        <input id="p-anio-ref" class="form-control" type="number" min="2000" max="2100" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="p-anio">Año de proyección</label>
                        <input id="p-anio" class="form-control" type="number" min="2000" max="2100" required>
                    </div>
                    <div class="col-md-6" hidden>
                        <label class="form-label" for="p-inflacion">Ajuste</label>
                        <input id="p-inflacion" class="form-control" type="number" step="0.1" value="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="p-tc">Tipo de cambio USD</label>
                        <div class="input-group">
                            <span class="input-group-text">MXN</span>
                            <input id="p-tc" class="form-control" type="number" step="0.01" min="0" placeholder="20.00">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="p-tipo-budget">Tipo de budget</label>
                        <select id="p-tipo-budget" class="form-select" required>
                            <option value="3+9">3 + 9 · copia ene–mar (bloqueados) · edita abr–dic</option>
                            <option value="6+6">6 + 6 · copia ene–jun (bloqueados) · edita jul–dic</option>
                            <option value="9+3">9 + 3 · copia ene–sep (editables) · edita también oct–dic</option>
                        </select>
                        <small class="text-muted" id="p-tipo-budget-hint">Se fija al abrir el ciclo y no se puede cambiar después.</small>
                    </div>

                    <div class="col-12"><div class="cc-form-kicker">Ventana de captura</div></div>
                    <div class="col-md-4">
                        <label class="form-label" for="p-estado">Estado del periodo</label>
                        <select id="p-estado" class="form-select">
                            <option value="abierto">Abierto</option>
                            <option value="en_revision">En revisión</option>
                            <option value="cerrado">Cerrado</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="p-inicio">Inicio captura</label>
                        <input id="p-inicio" class="form-control" type="date">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="p-fin">Fin captura</label>
                        <input id="p-fin" class="form-control" type="date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="p-captura">Pueden capturar hasta</label>
                        <input id="p-captura" class="form-control" type="date">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="p-revision">Solo revisar desde</label>
                        <input id="p-revision" class="form-control" type="date">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="p-obs">Observaciones</label>
                        <textarea id="p-obs" class="form-control" rows="2" placeholder="Notas del ciclo, supuestos o recados para contabilidad…"></textarea>
                    </div>
                </div>
            </div>
            <div class="cc-modal-actions">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="cc-btn cc-btn-ink" id="btn-guardar-ciclo">Abrir ciclo</button>
            </div>
        </form>
    </div>
</div>
