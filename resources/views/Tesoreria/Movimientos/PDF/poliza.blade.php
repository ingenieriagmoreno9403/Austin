@php
    $referencia = $no_poliza;
    $asientos = [];
    $tipo_etiqueta = strtoupper($tipoMovimiento);

    $linea = function (string $nombre, $debe = null, $haber = null, string $cuenta = '') use ($referencia) {
        return [
            'cuenta' => $cuenta,
            'nombre' => $nombre,
            'referencia' => $referencia,
            'debe' => $debe,
            'haber' => $haber,
        ];
    };

    if ($tipoMovimiento == 'TRANSFERENCIA' || $tipoMovimiento == 'ENTREGA') {
        $asientos[] = $linea($nombre2, $saldo);
        $asientos[] = $linea($nombre1, null, $saldo);
        $tipo_etiqueta = 'TRANSFERENCIA';
    } elseif ($tipoMovimiento == 'GASTO') {
        $total_iva = 0;
        $total_ret_iva = 0;
        $total_ret_isr = 0;
        $total_ret_isr_resico = 0;
        $total_neto = 0;
        $importe = 0;
        $porce_iva = 0;
        $porce_ret_iva = 0;
        $porce_ret_isr = 0;
        $porce_ret_isr_resico = 0;

        foreach ($varporcent as $item) {
            $total_iva = $item->total_iva;
            $total_ret_iva = $item->total_ret_iva;
            $total_ret_isr = $item->total_ret_isr;
            $total_ret_isr_resico = $item->total_ret_isr_resico;
            $total_neto = $item->egreso;

            $suma = $total_iva;
            $resta = $total_ret_iva + $total_ret_isr + $total_ret_isr_resico;
            $importe = ($total_neto + $resta) - $suma;

            $porce_iva = $importe > 0 ? ($total_iva / $importe) * 100 : 0;
            $porce_ret_iva = $importe > 0 ? ($total_ret_iva / $importe) * 100 : 0;
            $porce_ret_isr = $importe > 0 ? ($total_ret_isr / $importe) * 100 : 0;
            $porce_ret_isr_resico = $importe > 0 ? ($total_ret_isr_resico / $importe) * 100 : 0;

            $importe = round($importe, 2);
            $porce_iva = round($porce_iva, 2);
            $porce_ret_iva = round($porce_ret_iva, 2);
            $porce_ret_isr = round($porce_ret_isr, 2);
            $porce_ret_isr_resico = round($porce_ret_isr_resico, 2);
        }

        if ($importe > 0) {
            $asientos[] = $linea($concepto, $importe);
        }

        if ($total_iva > 0) {
            $asientos[] = $linea('IVA al % ' . $porce_iva, $total_iva);
        }

        if ($total_ret_iva > 0) {
            $asientos[] = $linea('RET IVA al % ' . $porce_ret_iva, null, $total_ret_iva);
        }

        if ($total_ret_isr > 0) {
            $asientos[] = $linea('RET ISR al % ' . $porce_ret_isr, null, $total_ret_isr);
        }

        if ($total_ret_isr_resico > 0) {
            $asientos[] = $linea('RET ISR RESICO al % ' . $porce_ret_isr_resico, null, $total_ret_isr_resico);
        }

        if ($total_neto > 0) {
            $asientos[] = $linea($nombre1, null, $total_neto);
        }

        $tipo_etiqueta = 'PASIVO/COMPRA';
    } elseif ($tipoMovimiento == 'PAGO') {
        $asientos[] = $linea($concepto ?: 'PAGO', $saldo);
        $asientos[] = $linea($nombre1, null, $saldo);
        $tipo_etiqueta = 'PAGO';
    } else {
        $nombreMovimiento = trim(($concepto ? $concepto . ' - ' : '') . $nombre1);
        $asientos[] = $linea($nombreMovimiento, $saldo);
        $asientos[] = $linea(strtoupper($tipoMovimiento), null, $saldo);
    }

    $nota = !empty($descripcion) ? $descripcion : null;
@endphp

@include('Tesoreria.Movimientos.PDF.partials.poliza_diario_shell', ['nota' => $nota])
