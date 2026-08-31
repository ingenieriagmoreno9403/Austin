@php
    $referencia = $no_poliza;
    $asientos = [];
    $tipo_etiqueta = 'NOMINA EXCEDENTE';

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
        if ($nom->sueldo_excedente > 0) {
            $asientos[] = $linea('Sueldo Excedente', $nom->sueldo_excedente);
        }
        if ($nom->dias_pendiente > 0) {
            $asientos[] = $linea('Dias Pendientes', $nom->dias_pendiente);
        }
        if ($nom->pago_prima_vacacional_exce > 0) {
            $asientos[] = $linea('Prima Vacacional Excedente', $nom->pago_prima_vacacional_exce);
        }
        if ($nom->bono > 0) {
            $asientos[] = $linea('Bono', $nom->bono);
        }
        if (($nom->viaticos ?? 0) > 0) {
            $asientos[] = $linea('Viaticos', $nom->viaticos);
        }
        if ($nom->transporte > 0) {
            $asientos[] = $linea('Transporte', $nom->transporte);
        }
        if ($nom->otros > 0) {
            $asientos[] = $linea('Otros Excedente', $nom->otros);
        }
        if ($nom->deudores_no_fiscal > 0) {
            $asientos[] = $linea('Deudores Diversos (pagos)', null, $nom->deudores_no_fiscal);
        }
        if ($nom->total_apagar_excedente > 0) {
            $asientos[] = $linea('CAJA O BOVEDA ' . $nombre1, null, $nom->total_apagar_excedente);
        }
    }

    $nota = !empty($descripcion) ? 'CUENTA DE ' . $concepto . ' - ' . $descripcion : null;
@endphp

@include('Tesoreria.Movimientos.PDF.partials.poliza_diario_shell', ['nota' => $nota])
