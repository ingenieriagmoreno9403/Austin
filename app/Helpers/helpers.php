<?php

if (!function_exists('formatNumero')) {
    function formatNumero($value) {
        if ($value === null || $value === '') {
            return $value;
        }

        $num = (float) $value;

        if ($num == (int) $num) {
            return (string) (int) $num;
        }

        $formatted = rtrim(rtrim(sprintf('%.10f', $num), '0'), '.');

        if (str_contains($formatted, '.')) {
            [, $decimals] = explode('.', $formatted, 2);
            if (strlen($decimals) === 1) {
                $formatted .= '0';
            }
        }

        return $formatted;
    }
}

if (!function_exists('ocultarConAsteriscos')) {
    function ocultarConAsteriscos($texto, $mostrarInicio = 0, $mostrarFinal = 0) {
        $longitud = strlen($texto);
        $ocultar = $longitud - ($mostrarInicio + $mostrarFinal);
        
        if ($ocultar <= 0) {
            return $texto; // No ocultar nada si no hay suficiente longitud
        }

        return substr($texto, 0, $mostrarInicio) 
             . str_repeat('*', $ocultar) 
             . substr($texto, -$mostrarFinal);
    }
}