<div class="modal fade cc-modal" id="modalIndicadores" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tipo de cambio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="cc-table">
                    <tbody>
                        <tr hidden>
                            <td>Inflación</td>
                            <td class="num fw-semibold" id="ind-inflacion">0 %</td>
                        </tr>
                        <tr>
                            <td>Tipo de cambio MXP/USD</td>
                            <td class="num fw-semibold" id="ind-tc">$ 20.00</td>
                        </tr>
                    </tbody>
                </table>
                <p class="text-muted mb-0 mt-3" style="font-size:.82rem">
                    Se proyectan cantidades. El importe es cantidad × costo, consultable en MXN o USD con el tipo de cambio del ciclo.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="cc-btn cc-btn-ink" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
