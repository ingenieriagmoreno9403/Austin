// Función para calcular división de proyecto (por partidas, sin plazos ni fechas)
function calcularDivisionProyecto() {
    // Sanear y parsear TOTAL CON UTILIDAD desde input de texto
    let totalRaw = ($('#total_con_utilidad').val() || '').toString();
    let totalSan = totalRaw.replace(/[^0-9.,-]/g, '');
    if (totalSan.indexOf(',') >= 0 && totalSan.indexOf('.') === -1) {
        totalSan = totalSan.replace(/,/g, '.');
    } else {
        totalSan = totalSan.replace(/,/g, '');
    }
    const costoTotal = parseFloat(totalSan) || 0; // ahora base = total con utilidad
    const numPartidas = parseInt($('#num_partidas').val()) || 0;
    const idServicio = $('input[name="id_servicio"]').val();
    // Tomar base sin utilidad del input de costo_total
    let baseRaw = ($('#costo_total').val() || '').toString();
    let baseSan = baseRaw.replace(/[^0-9.,-]/g, '');
    if (baseSan.indexOf(',') >= 0 && baseSan.indexOf('.') === -1) {
        baseSan = baseSan.replace(/,/g, '.');
    } else {
        baseSan = baseSan.replace(/,/g, '');
    }
    const baseSinUtilidad = parseFloat(baseSan) || 0;

    if (!costoTotal || !numPartidas || !idServicio) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos Requeridos',
            text: 'Por favor, ingrese el costo total, el número de partidas y verifique el ID del servicio.'
        });
        return;
    }

    // Validar número de partidas
    const maxPartidas = 50;
    if (numPartidas <= 0 || numPartidas > maxPartidas) {
        Swal.fire({
            icon: 'error',
            title: 'Número de Partidas Inválido',
            text: `El número de partidas debe estar entre 1 y ${maxPartidas}.`
        });
        return;
    }

    // Calcular montos
    const montoPorPartida = costoTotal / numPartidas;
    const porcentajePorPartida = 100 / numPartidas;

    // Generar tabla dinámica
    let tablaHTML = '';
    let totalCalculado = 0;
    for (let i = 1; i <= numPartidas; i++) {
        const montoPartida = i === numPartidas ? (costoTotal - totalCalculado) : montoPorPartida;
        totalCalculado += montoPartida;
        tablaHTML += `
            <tr>
                <td>${i}</td>
                <td><span class="badge bg-primary">Partida ${i}</span></td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control form-control-sm division-monto" id="division_monto_${i}" data-index="${i}" value="${montoPartida.toFixed(2)}" />
                    </div>
                </td>
                <td><span class="badge bg-info" id="porcentaje_${i}">${porcentajePorPartida.toFixed(1)}%</span></td>
                <td>
                    <input type="text" class="form-control form-control-sm division-nombre" id="division_nombre_${i}" data-index="${i}" placeholder="Nombre de la división" />
                </td>
                <td><span class="badge bg-warning">Pendiente</span></td>
            </tr>
        `;
    }

    $('#tbodyDivisionProyecto').html(tablaHTML);
    $('#totalCalculado').text('$' + costoTotal.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#seccionTablaDivision').show();

    // Guardar datos para envío
    window.divisionData = {
        costo_total: costoTotal, // total con utilidad
        base_sin_utilidad: baseSinUtilidad,
        num_partidas: numPartidas,
        id_servicio: idServicio,
        utilidad_porcentaje: (function(){
            let u = parseFloat($('#utilidad_porcentaje').val());
            if (isNaN(u) || u < 0) u = 0;
            if (u > 100) u = 100;
            return u;
        })(),
        divisiones: []
    };

    // Generar array de divisiones
    for (let i = 1; i <= numPartidas; i++) {
        const montoPartida = i === numPartidas ? (costoTotal - (montoPorPartida * (i - 1))) : montoPorPartida;
        window.divisionData.divisiones.push({
            numero_plazo: i,
            monto: parseFloat(montoPartida.toFixed(2)),
            porcentaje: parseFloat(porcentajePorPartida.toFixed(2)),
            estado: 'Pendiente',
            nombre: ''
        });
    }

    // Escuchar cambios en montos para recalcular totales y porcentajes
    const inputsMonto = document.querySelectorAll('.division-monto');
    inputsMonto.forEach(function(input) {
        input.addEventListener('input', function() {
            const idx = parseInt(this.getAttribute('data-index')) - 1;
            let raw = (this.value || '').toString();
            let sanitized = raw.replace(/[^0-9.,-]/g, '');
            // Tratar coma como decimal si existe
            if (sanitized.indexOf(',') >= 0 && sanitized.indexOf('.') === -1) {
                sanitized = sanitized.replace(/,/g, '.');
            } else {
                sanitized = sanitized.replace(/,/g, '');
            }
            let val = parseFloat(sanitized);
            if (isNaN(val) || val < 0) val = 0;

            if (window.divisionData && window.divisionData.divisiones && window.divisionData.divisiones[idx]) {
                window.divisionData.divisiones[idx].monto = val;
            }
            actualizarTotalesYPorcentajes();
        });
        input.addEventListener('blur', function() {
            let raw = (this.value || '').toString();
            let sanitized = raw.replace(/[^0-9.,-]/g, '');
            if (sanitized.indexOf(',') >= 0 && sanitized.indexOf('.') === -1) {
                sanitized = sanitized.replace(/,/g, '.');
            } else {
                sanitized = sanitized.replace(/,/g, '');
            }
            let val = parseFloat(sanitized);
            if (isNaN(val) || val < 0) val = 0;
            this.value = val.toFixed(2);
            actualizarTotalesYPorcentajes();
        });
    });
}

function actualizarTotalesYPorcentajes() {
    // Usar el total con utilidad para porcentajes
    let totalRaw = ($('#total_con_utilidad').val() || '').toString();
    let totalSan = totalRaw.replace(/[^0-9.,-]/g, '');
    if (totalSan.indexOf(',') >= 0 && totalSan.indexOf('.') === -1) {
        totalSan = totalSan.replace(/,/g, '.');
    } else {
        totalSan = totalSan.replace(/,/g, '');
    }
    const costoTotal = parseFloat(totalSan) || 0;
    let suma = 0;
    if (window.divisionData && Array.isArray(window.divisionData.divisiones)) {
        window.divisionData.divisiones.forEach(function(div, idx) {
            const input = document.getElementById('division_monto_' + (idx + 1));
            const monto = input ? parseFloat(input.value) || 0 : (parseFloat(div.monto) || 0);
            suma += monto;

            // Calcular porcentaje y pintar
            const porcentaje = costoTotal > 0 ? (monto / costoTotal) * 100 : 0;
            const pctEl = document.getElementById('porcentaje_' + (idx + 1));
            if (pctEl) pctEl.textContent = porcentaje.toFixed(1) + '%';
            div.porcentaje = parseFloat(porcentaje.toFixed(2));
            div.monto = parseFloat(monto.toFixed(2));
        });
    }

    $('#totalCalculado').text('$' + (suma).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    // Opcional: marcar desajuste visual
    const totalCell = document.getElementById('totalCalculado');
    if (Math.abs(suma - costoTotal) > 0.01) {
        totalCell.classList.add('text-danger');
        totalCell.classList.remove('text-success');
    } else {
        totalCell.classList.remove('text-danger');
        totalCell.classList.add('text-success');
    }
} 