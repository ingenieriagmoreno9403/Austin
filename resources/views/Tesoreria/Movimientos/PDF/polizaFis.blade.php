@php
    $referencia = $no_poliza;
    $asientos = [];
    $tipo_etiqueta = 'NOMINA FISCAL';

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
        if ($nom->sueldo_fiscal > 0) {
            $asientos[] = $linea('Sueldo Fiscal', $nom->sueldo_fiscal);
        }
        if ($nom->pago_prima_vacacional > 0) {
            $asientos[] = $linea('Prima Vacacional', $nom->pago_prima_vacacional);
        }
        if ($nom->pago_subsidio > 0) {
            $asientos[] = $linea('Otros', $nom->pago_subsidio);
        }
        if ($nom->pago_isr > 0) {
            $asientos[] = $linea('Retención ISR SyS', null, $nom->pago_isr);
        }
        if ($nom->pago_imss > 0) {
            $asientos[] = $linea('Retención IMSS', null, $nom->pago_imss);
        }
        if ($nom->pago_infonavit > 0) {
            $asientos[] = $linea('Retención INFONAVIT', null, $nom->pago_infonavit);
        }
        if ($nom->deudores_fiscal > 0) {
            $asientos[] = $linea('Deudores Diversos (pagos)', null, $nom->deudores_fiscal);
        }
        if ($nom->total_nomina_fiscal > 0) {
            $asientos[] = $linea('Banco ' . $nombre1, null, $nom->total_nomina_fiscal);
        }
    }

    $nota = !empty($descripcion) ? 'CUENTA DE ' . $concepto . ' - ' . $descripcion : null;
@endphp

@include('Tesoreria.Movimientos.PDF.partials.poliza_diario_shell', ['nota' => $nota])
