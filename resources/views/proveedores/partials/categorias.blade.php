@php
    $categoriaActual = (int) ($seleccionado ?? 0);
@endphp
<div data-categoria-box>
    <label class="form-label">Categoría</label>
    <div class="input-group mb-2">
        <input type="text" class="form-control js-categoria-nombre" maxlength="120" placeholder="Nueva categoría, ej. Sistemas" autocomplete="off">
        <button type="button" class="btn btn-dark js-categoria-agregar" title="Agregar categoría">
            <i class="fa-solid fa-plus"></i>
        </button>
    </div>
    <div class="border rounded-3" style="max-height: 180px; overflow: auto;">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th style="width: 42px;"></th>
                    <th>Categoría</th>
                </tr>
            </thead>
            <tbody class="js-categoria-tabla">
                <tr>
                    <td>
                        <input type="radio" name="categoria_id" value="" {{ $categoriaActual === 0 ? 'checked' : '' }}>
                    </td>
                    <td>Sin categoría</td>
                </tr>
                @foreach ($categorias ?? [] as $categoria)
                    <tr>
                        <td>
                            <input type="radio" name="categoria_id" value="{{ $categoria->id }}" {{ $categoriaActual === (int) $categoria->id ? 'checked' : '' }}>
                        </td>
                        <td>{{ $categoria->nombre }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
(function () {
    if (window.__categoriaProveedorLista) return;
    window.__categoriaProveedorLista = true;
    document.addEventListener('click', function (event) {
        var boton = event.target.closest('.js-categoria-agregar');
        if (!boton) return;
        var caja = boton.closest('[data-categoria-box]');
        var input = caja.querySelector('.js-categoria-nombre');
        var nombre = (input.value || '').trim();
        if (!nombre) {
            input.focus();
            return;
        }
        var token = document.querySelector('meta[name="csrf-token"]');
        fetch(@json(route('proveedores.categoria')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.getAttribute('content') : '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ nombre: nombre })
        }).then(function (res) {
            if (!res.ok) throw new Error('categoria');
            return res.json();
        }).then(function (data) {
            document.querySelectorAll('.js-categoria-tabla').forEach(function (tabla) {
                var propia = tabla.closest('[data-categoria-box]') === caja;
                var existe = false;
                tabla.querySelectorAll('input[name="categoria_id"]').forEach(function (radio) {
                    if (String(radio.value) === String(data.id)) {
                        existe = true;
                        if (propia) radio.checked = true;
                    }
                });
                if (!existe) {
                    var fila = document.createElement('tr');
                    var celdaRadio = document.createElement('td');
                    var radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = 'categoria_id';
                    radio.value = data.id;
                    radio.checked = propia;
                    celdaRadio.appendChild(radio);
                    var celdaNombre = document.createElement('td');
                    celdaNombre.textContent = data.nombre;
                    fila.appendChild(celdaRadio);
                    fila.appendChild(celdaNombre);
                    tabla.appendChild(fila);
                }
            });
            input.value = '';
        }).catch(function () {
            window.alert('No se pudo agregar la categoría.');
        });
    });
})();
</script>
