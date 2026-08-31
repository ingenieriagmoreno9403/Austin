<div>
    <input type="hidden" name="detalle_json" value="{{ json_encode($filas) }}">
    <input type="hidden" name="flete_interno" value="{{ $fleteInterno  ?? 0 }}">
    <input type="hidden" name="flete_externo" value="{{ $fleteExterno   ?? 0}}">
    <input type="hidden" name="margenGlobal" id="margenGlobalHidden" value="{{ $margenGlobal ?? 0 }}">

    @if(isset($flete_interno))
        <script>
            document.addEventListener('livewire:load', function () {
                if (@this.hasOwnProperty('fleteInterno')) {
                    @this.set('fleteInterno', {{ floatval($flete_interno) }});
                }
            });
        </script>
    @endif
    @if(isset($flete_externo))
        <script>
            document.addEventListener('livewire:load', function () {
                if (@this.hasOwnProperty('fleteExterno')) {
                    @this.set('fleteExterno', {{ floatval($flete_externo) }});
                }
            });
        </script>
    @endif

    <div class="row justify-content-start pb-3">

        <div class="col-md-8 col-12 mb-3 pt-1">
            <div class="border-4 border-start border-warning p-3 fs-8 shadow rounded-2 text-start">
                <i class="fa-solid fa-info-circle text-orange fs-7"></i> &nbsp; Introduce el precio y marca para cada producto. Recuerda guardar tus cambios periódicamente.
            </div>
        </div>

        <div class="col-md-12 col-12 form-outline text-start">
            <div class="row g-2 align-items-end mb-3">
                <div class="col">
                     <!-- Input Margen Global -->
                        <label for="margenGlobal" class="form-label fw-bold" style="font-size: 0.95em;">Margen General (%)</label>
                        <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-percent"></i></span>
                        <input type="number"
                            step="0.01"
                            min="0"
                            class="form-control"
                            style="max-width: 200px;"
                            id="margenGlobal"
                            wire:model="margenGlobal"
                            wire:change="actualizarMargenGlobalHidden"
                            placeholder="Margen general para todos los productos">
                        </div>
                </div>
                <div class="col">
                    <label for="fleteInterno" class="form-label mb-1" style="font-size: 0.95em;">Flete Interno</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-truck"></i></span>
                        <input type="text"
                               class="form-control formato-numero"
                               style="width: 120px;"
                               data-wire-model="fleteInterno"
                               id="fleteInterno"
                               value="{{ number_format($fleteInterno ?? 0, 2, '.', ',') }}"
                               autocomplete="off"
                               required
                               placeholder="Interno" />
                    </div>
                </div>
                <div class="col">
                    <label for="fleteExterno" class="form-label mb-1" style="font-size: 0.95em;">Flete Externo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-truck-moving"></i></span>
                        <input type="text"
                               class="form-control formato-numero"
                               style="width: 120px;"
                               data-wire-model="fleteExterno"
                               id="fleteExterno"
                               value="{{ number_format($fleteExterno ?? 0, 2, '.', ',') }}"
                               autocomplete="off"
                               required
                               placeholder="Externo" />
                    </div>
                </div>
                <div class="col">
                    <label for="fleteUniversal" class="form-label mb-1" style="font-size: 0.95em;">Flete Total</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-dollar"></i></span>
                        <input type="text"
                               class="form-control formato-numero"
                               style="width: 120px;"
                               data-wire-model="fleteUniversal"
                               id="fleteUniversal"
                               value="{{ number_format($fleteUniversal ?? 0, 2, '.', ',') }}"
                               autocomplete="off"
                               required
                               placeholder="Universal" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th class="text-truncate">Producto</th>
                <th class="text-truncate">Cantidad</th>
                <th class="text-truncate">U. Med</th>
                <th class="text-truncate">Costo U.</th>
                <th class="text-truncate">Margen</th>
                <th class="text-truncate fw-bold">Cost + Margen</th>
                <th class="text-truncate">Envio</th>
                <th class="text-truncate fw-bold">Subtotal</th>
                <th class="text-truncate fw-bold">Total</th>
                <th class="text-truncate">Marca</th>
                <th class="text-truncate">Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filas as $i => $fila)
                <tr>
                    <td style="position: relative;">
                        <input type="hidden" wire:model="filas.{{ $i }}.producto_id" />

                        <input type="text" style="max-width: 200px!important"
                               class="inputInv text-truncate"
                               placeholder="Buscar producto..."
                               wire:model="filas.{{ $i }}.nombre"
                               autocomplete="off"
                               readonly />
                    </td>

                    <td><input type="number" class="inputInv" wire:model="filas.{{ $i }}.cantidad" readonly></td>
                    <td><input type="text" class="inputInv" wire:model="filas.{{ $i }}.umed" readonly></td>

                    
                    <td>
                        <input type="text" 
                               class="form-control formato-numero" 
                               data-index="{{ $i }}"
                               data-field="costo"
                               data-wire-model="filas.{{ $i }}.costo"
                               value="{{ number_format($fila['costo'] ?? 0, 2, '.', ',') }}"
                               required>
                    </td>

                    

                     <td>
                        <input type="text" 
                               class="form-control formato-numero" 
                               data-index="{{ $i }}"
                               data-field="margen"
                               data-wire-model="filas.{{ $i }}.margen"
                               value="{{ number_format($fila['margen'] ?? 0, 2, '.', ',') }}"
                               required>
                    </td>

                     <td class="table-warning">
                        <input type="text" 
                               class="inputInv formato-numero-readonly" 
                               data-index="{{ $i }}"
                               data-field="margencost"
                               value="{{ number_format($fila['margencost'] ?? 0, 2, '.', ',') }}"
                               readonly>
                    </td>

                    <td class="table-primary">
                        <input type="text" 
                               class="inputInv formato-numero-readonly"  
                               data-index="{{ $i }}"
                               data-field="enviototal"
                               value="{{ number_format($fila['enviototal'] ?? 0, 2, '.', ',') }}"
                               readonly>
                    </td>
                    
                    <td class="table-secondary">
                        <input type="text" 
                               class="inputInv formato-numero-readonly" 
                               data-index="{{ $i }}"
                               data-field="subtotal"
                               value="{{ number_format($fila['subtotal'] ?? 0, 2, '.', ',') }}"
                               tabindex="-1"
                               readonly>
                    </td>
                    <td class="table-success">
                        <input type="text" 
                               class="inputInv formato-numero-readonly" 
                               data-index="{{ $i }}"
                               data-field="total"
                               value="{{ number_format($fila['total'] ?? 0, 2, '.', ',') }}"
                               tabindex="-1"
                               readonly>
                    </td>
                    <td><input type="text" class="form-control" wire:model="filas.{{ $i }}.marca"></td>
                    <td><input type="text" class="form-control" wire:model="filas.{{ $i }}.observaciones" maxlength="100"></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Función para formatear número con separadores de miles y 2 decimales
    function formatearNumero(valor, permitirPuntoIncompleto = false) {
        if (!valor || valor === '' || valor === null || valor === undefined) return '';
        
        // Convertir a string y remover todo excepto números y punto decimal
        let numero = valor.toString().replace(/[^\d.]/g, '');
        
        // Permitir punto solo si el usuario está escribiendo (permitirPuntoIncompleto = true)
        if (numero === '' && !permitirPuntoIncompleto) return '';
        if (numero === '.' && permitirPuntoIncompleto) return '.';
        if (numero === '' || (numero === '.' && !permitirPuntoIncompleto)) return '';
        
        // Manejar múltiples puntos - solo permitir el primero
        let indicePunto = numero.indexOf('.');
        if (indicePunto !== -1) {
            let antesPunto = numero.substring(0, indicePunto);
            let despuesPunto = numero.substring(indicePunto + 1).replace(/\./g, '');
            numero = antesPunto + '.' + despuesPunto;
        }
        
        // Separar parte entera y decimal
        let partes = numero.split('.');
        let parteEntera = partes[0] || '0';
        let parteDecimal = partes[1] || '';
        
        // Limitar decimales a 2
        if (parteDecimal.length > 2) {
            parteDecimal = parteDecimal.substring(0, 2);
        }
        
        // Formatear parte entera con comas (solo si tiene contenido)
        if (parteEntera && parteEntera !== '0') {
            parteEntera = parteEntera.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        // Combinar
        if (parteDecimal || (numero.includes('.') && permitirPuntoIncompleto)) {
            return parteEntera + '.' + parteDecimal;
        }
        return parteEntera || '0';
    }
    
    // Función para obtener el valor numérico sin formato
    function obtenerValorNumerico(valor) {
        if (!valor || valor === '') return '0';
        let numero = valor.toString().replace(/[^\d.]/g, '');
        return numero === '' ? '0' : numero;
    }
    
    // Función para obtener el componente Livewire
    function getLivewireComponent() {
        let wireElement = document.querySelector('[wire\\:id]');
        if (wireElement) {
            return Livewire.find(wireElement.getAttribute('wire:id'));
        }
        return null;
    }
    
    // Aplicar formato a todos los campos con clase formato-numero
    function inicializarFormatoNumeros() {
        document.querySelectorAll('.formato-numero').forEach(function(input) {
            // Formatear al cargar
            if (input.value) {
                input.value = formatearNumero(input.value);
            }
            
            // Permitir teclas especiales: números, punto, backspace, delete, tab, flechas, etc.
            input.addEventListener('keydown', function(e) {
                // Permitir teclas de control (backspace, delete, tab, escape, enter, flechas, etc.)
                if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].indexOf(e.keyCode) !== -1 ||
                    // Permitir Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                    (e.keyCode === 65 && e.ctrlKey === true) ||
                    (e.keyCode === 67 && e.ctrlKey === true) ||
                    (e.keyCode === 86 && e.ctrlKey === true) ||
                    (e.keyCode === 88 && e.ctrlKey === true) ||
                    // Permitir números del teclado principal y numérico
                    (e.keyCode >= 48 && e.keyCode <= 57) ||
                    (e.keyCode >= 96 && e.keyCode <= 105)) {
                    return;
                }
                // Permitir punto decimal (códigos 110 del teclado numérico y 190 del teclado principal)
                if (e.keyCode === 110 || e.keyCode === 190) {
                    // Solo permitir si no hay ya un punto
                    if (this.value.indexOf('.') === -1) {
                        return;
                    } else {
                        // Si ya hay un punto, bloquear
                        e.preventDefault();
                        return;
                    }
                }
                // Bloquear cualquier otra tecla
                e.preventDefault();
            });
            
            // Formatear mientras escribe
            input.addEventListener('input', function(e) {
                let cursorPos = this.selectionStart;
                let valorOriginal = this.value;
                let valorActual = this.value;
                
                // Detectar si el usuario está escribiendo un punto decimal
                let tienePuntoIncompleto = valorActual.endsWith('.') && valorActual.split('.').length === 2;
                
                // Formatear permitiendo punto incompleto
                this.value = formatearNumero(this.value, true);
                
                // Si el usuario estaba escribiendo un punto, mantenerlo
                if (tienePuntoIncompleto && !this.value.includes('.')) {
                    this.value = this.value + '.';
                }
                
                // Ajustar posición del cursor de manera más precisa
                let nuevoValor = this.value;
                let diferencia = nuevoValor.length - valorOriginal.length;
                
                // Si el usuario está escribiendo después del punto, ajustar mejor la posición
                if (valorOriginal.includes('.') && cursorPos > valorOriginal.indexOf('.')) {
                    let posicionPuntoOriginal = valorOriginal.indexOf('.');
                    let posicionPuntoNuevo = nuevoValor.indexOf('.');
                    if (posicionPuntoNuevo !== -1) {
                        let offset = cursorPos - posicionPuntoOriginal;
                        let newPos = posicionPuntoNuevo + offset;
                        this.setSelectionRange(newPos, newPos);
                    } else {
                        let newPos = Math.max(0, cursorPos + diferencia);
                        this.setSelectionRange(newPos, newPos);
                    }
                } else {
                    let newPos = Math.max(0, cursorPos + diferencia);
                    this.setSelectionRange(newPos, newPos);
                }
            });
            
            // Actualizar Livewire cuando pierde el foco
            input.addEventListener('blur', function(e) {
                let valorNumerico = obtenerValorNumerico(this.value);
                let wireModel = this.getAttribute('data-wire-model');
                let index = this.getAttribute('data-index');
                let field = this.getAttribute('data-field');
                
                let component = getLivewireComponent();
                if (component && wireModel) {
                    let valor = parseFloat(valorNumerico) || 0;
                    
                    if (wireModel.includes('filas.') && index !== null && field) {
                        // Es un campo de fila
                        component.set('filas.' + index + '.' + field, valor);
                        // Llamar a calcularSubtotal si es costo o margen
                        if (field === 'costo' || field === 'margen') {
                            component.call('calcularSubtotal', parseInt(index));
                        }
                    } else {
                        // Es un campo directo (fleteInterno, fleteExterno, etc.)
                        component.set(wireModel, valor);
                        if (wireModel === 'fleteUniversal') {
                            component.call('calcularTodosLosSubtotales');
                        }
                    }
                }
                
                // Re-formatear después de actualizar
                this.value = formatearNumero(valorNumerico);
            });
        });
    }
    
    // Actualizar campos de solo lectura
    function actualizarCamposReadonly() {
        let component = getLivewireComponent();
        if (!component) return;
        
        document.querySelectorAll('.formato-numero-readonly').forEach(function(input) {
            let index = input.getAttribute('data-index');
            let field = input.getAttribute('data-field');
            
            if (index !== null && field) {
                try {
                    let valor = component.get('filas.' + index + '.' + field);
                    if (valor !== undefined && valor !== null) {
                        let valorFormateado = formatearNumero(valor.toString());
                        if (input.value !== valorFormateado) {
                            input.value = valorFormateado;
                        }
                    }
                } catch(e) {
                    // Ignorar errores
                }
            }
        });
    }
    
    // Inicializar cuando se carga el DOM
    inicializarFormatoNumeros();
    
    // Actualizar campos readonly cuando Livewire actualiza
    document.addEventListener('livewire:load', function() {
        inicializarFormatoNumeros();
        
        Livewire.hook('message.processed', (message, component) => {
            setTimeout(actualizarCamposReadonly, 100);
        });
    });
    
    // También escuchar actualizaciones de Livewire
    document.addEventListener('livewire:update', function() {
        setTimeout(actualizarCamposReadonly, 100);
    });
    
    // Actualizar periódicamente los campos readonly
    setInterval(actualizarCamposReadonly, 500);
    
    // Actualizar el campo hidden de margenGlobal cuando cambia
    document.addEventListener('livewire:load', function() {
        Livewire.hook('message.processed', (message, component) => {
            let margenGlobalInput = document.getElementById('margenGlobal');
            let margenGlobalHidden = document.getElementById('margenGlobalHidden');
            if (margenGlobalInput && margenGlobalHidden) {
                let component = getLivewireComponent();
                if (component) {
                    let valor = component.get('margenGlobal');
                    if (valor !== undefined && valor !== null) {
                        margenGlobalHidden.value = valor;
                    }
                }
            }
        });
    });
    
    // También actualizar cuando el usuario cambia el margen global
    let margenGlobalInput = document.getElementById('margenGlobal');
    if (margenGlobalInput) {
        margenGlobalInput.addEventListener('blur', function() {
            let margenGlobalHidden = document.getElementById('margenGlobalHidden');
            if (margenGlobalHidden) {
                let component = getLivewireComponent();
                if (component) {
                    let valor = component.get('margenGlobal');
                    if (valor !== undefined && valor !== null) {
                        margenGlobalHidden.value = valor;
                    }
                }
            }
        });
    }
});
</script>
