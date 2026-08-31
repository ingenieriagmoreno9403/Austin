const val = document.querySelector('#producto');

val.innerHTML = `
<div class="row mb-2">
    <table class="table table-striped table-dark table-responsive-lg" id="tabla">
        <thead>
            <tr>
                <th scope="col">Codigo</th>
                <th scope="col">Descripcíon</th>
                <th scope="col">UM</th>
                <th scope="col">Cantidad</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
                <tr class="fila-fija">
                <td width="20%">
                    <select name="codigo[]" required>
                        <option>-- Selecciona --</option>
                        <option value="1">Código 1</option>
                        <option value="2">Código 2</option>
                        <option value="3">Código 3</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control" name="descripcion[]"  required />
                    <div class="invalid-feedback">
                        campo requerido
                    </div>
                </td>
                <td width="8%">
                    <input type="text" class="form-control" name="um[]" required />
                    <div class="invalid-feedback">
                        campo requerido
                    </div>
                </td>
                <td width="10%"><input type="number" min="10" pattern="^[0-9]+" class="form-control cantidad" name="cantidad[]" required/>
                <div class="invalid-feedback">
                        min 10
                    </div>
                    </td>
                <td width="7%" class="eliminar"><input type="button" class="btn btn-danger elimina" value=" - " disabled></td>
            </tr>
        </tbody>
    </table>

    <div id="btnAdd" class="row">
        <div class="col-md-12 col-12">  
            <label>Puedes agremar mas articulos :</label> <button type="button" id="adicional" name="adicional" class="btn btn-warning ml-2  mr-2"> Más + </button>
        </div>
        <div id="respuesta" class="col-md-6 col-12"></div>
    </div>
</div>

`
    // Esta puede ser una constante global, porque no se modifica posteriormente
    const numSus =+1 ;

    // Escuchar cambios en select, delegando con el método .on()
    $('#tabla tbody').on('change', 'select', function() {
        // Obtener valor seleccionado
        let value = $(this).val();
        // Encontrar campo de descripción, buscando en la misma fila
        let desc = $(this).closest('tr').find('[name="descripcion[]"]');
        // Asignar valor
        $(desc).val(`Valor seleccionado: ${value}`);
    });
    
    $('#adicional').on('click', function() {
        let suscripcion = document.getElementsByName("options");
        // Se necesita el número para activar el botón de esta fila
        let num = $('#tabla tbody tr.fila-fija').length;
    
        for(var i = 0; i < suscripcion.length; i++) {
            if(suscripcion[i].checked) {
                suscripcionSelect = suscripcion[i].value;
            }
        }
        // Clonar primera fila y agregar a tabla
        $('#tabla tbody tr:eq(0)').clone().appendTo('#tabla');
        // Activar el botón de esta fila
        $(`#tabla tbody .elimina:eq(${num})`).attr('disabled', false);
        if(num == numSus - 1) {
            // Desactivar botón cuando ya hay el máximo de filas
            $('#adicional').attr('disabled', true);
        }
    });
    
    
    $(document).on('click', '.elimina',function() {
        // Remoer directamente la fila
        $(this).closest('tr').remove();
        // Activar nuevamente el botón para agregar filas
        $('#adicional').attr('disabled', false);
    });