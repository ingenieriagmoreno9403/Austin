<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

class AsistenciasFormatoDetector
{
    public static function detectarDesdeArchivo(string $rutaArchivo): string
    {
        $spreadsheet = IOFactory::load($rutaArchivo);
        $sheet = $spreadsheet->getActiveSheet();
        $maxFilas = min(30, (int) $sheet->getHighestRow());

        for ($fila = 1; $fila <= $maxFilas; $fila++) {
            $valor = trim((string) $sheet->getCell('A' . $fila)->getFormattedValue());

            if ($valor === '') {
                continue;
            }

            if (
                str_starts_with($valor, 'Total de Asistencia')
                || str_starts_with($valor, 'Empleado:')
                || str_starts_with($valor, 'Fecha desde')
            ) {
                return 'reloj';
            }

            if (self::esEncabezadoPlantilla($valor)) {
                return 'plantilla';
            }
        }

        return 'plantilla';
    }

    protected static function esEncabezadoPlantilla(string $valor): bool
    {
        $normalizado = strtolower(str_replace([' ', '-'], '_', $valor));

        return in_array($normalizado, ['id_empleado', 'id'], true);
    }
}
