@php
    $referencia = $no_poliza;
    $asientos = [];
    $tipo_etiqueta = 'NOMINA EFECTIVO';

    $linea = function (string $nombre, $debe = null, $haber = null, string $cuenta = '') use ($referencia) {
        return [
            'cuenta' => $cuenta,
            'nombre' => $nombre,
            'referencia' => $referencia,
            'debe' => $debe,
            'haber' => $haber,
        ];
    };

    foreach ($varpagonom as $nom) {
        if ($nom->sueldo_efectivo > 0) {
            $asientos[] = $linea('Sueldo Efectivo', $nom->sueldo_efectivo);
        }
        if ($nom->dias_pendiente > 0) {
            $asientos[] = $linea('Dias Pendientes', $nom->dias_pendiente);
        }
        if ($nom->pago_prima_vacacional > 0) {
            $asientos[] = $linea('Prima Vacacional Efectivo', $nom->pago_prima_vacacional);
        }
        if ($nom->bono > 0) {
            $asientos[] = $linea('Bono', $nom->bono);
        }
        if ($nom->transporte > 0) {
            $asientos[] = $linea('Transporte', $nom->transporte);
        }
        if ($nom->otros > 0) {
            $asientos[] = $linea('Otros Efectivo', $nom->otros);
        }
        if ($nom->deudores_no_fiscal > 0) {
            $asientos[] = $linea('Deudores Diversos (pagos)', null, $nom->deudores_no_fiscal);
        }
        if ($nom->total_efectivo > 0) {
            $asientos[] = $linea('CAJA O BOVEDA ' . $nombre1, null, $nom->total_efectivo);
        }
    }

    $nota = !empty($descripcion) ? 'CUENTA DE ' . $concepto . ' - ' . $descripcion : null;
@endphp

@include('Tesoreria.Movimientos.PDF.partials.poliza_diario_shell', ['nota' => $nota])
