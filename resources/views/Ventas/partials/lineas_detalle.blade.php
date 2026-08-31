<div class="table-responsive mt-2">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Producto</th>
                <th style="width: 100px;">Cantidad</th>
                <th style="width: 120px;">Precio unit.</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < 3; $i++)
                <tr>
                    <td>
                        <select class="form-select form-select-sm">
                            <option value="">— Seleccione —</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.001" min="0" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" maxlength="255">
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>
