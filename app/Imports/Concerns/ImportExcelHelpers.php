<?php

namespace App\Imports\Concerns;

use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

trait ImportExcelHelpers
{
    protected function normalizarHeader(string $txt): string
    {
        $t = mb_strtolower(trim($txt), 'UTF-8');
        $reemplazos = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ];
        $t = strtr($t, $reemplazos);
        $t = preg_replace('/[^a-z0-9]+/u', '_', $t) ?? '';

        return trim($t, '_');
    }

    protected function valorFila(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if ($key === '') {
                continue;
            }
            if (!array_key_exists($key, $row)) {
                continue;
            }
            $val = $row[$key];
            if ($val === null || $val === '') {
                continue;
            }

            return trim((string) $val);
        }

        return null;
    }

    protected function extraerIdNumerico(array $row, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }
            $val = $row[$key];
            if ($val === null || $val === '') {
                continue;
            }
            if (is_numeric($val)) {
                $id = (int) $val;
                if ($id > 0) {
                    return $id;
                }
            }
        }

        return null;
    }

    protected function filaVacia(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function parseFechaExcel($value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $raw = trim((string) $value);

        if (is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $formatos = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y', 'm-d-Y', 'n/j/Y'];
        foreach ($formatos as $formato) {
            $dt = \DateTime::createFromFormat($formato, $raw);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        }

        $ts = strtotime($raw);

        return $ts === false ? null : date('Y-m-d', $ts);
    }

    protected function parseDecimal($value): ?float
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);
        $raw = str_replace(['$', ',', ' '], '', $raw);

        if ($raw === '' || !is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    protected function parseEstadoEmpleado(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $estado = mb_strtoupper(trim($value), 'UTF-8');

        if (in_array($estado, ['A', 'ACTIVO'], true)) {
            return 'A';
        }

        if (in_array($estado, ['I', 'INACTIVO', 'BAJA'], true)) {
            return 'I';
        }

        return $estado;
    }

    protected function parseTipoContratacion(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $tipo = mb_strtoupper(trim($value), 'UTF-8');

        if ($tipo === 'INDETERMINADA') {
            return 'INDEFINIDA';
        }

        return $tipo;
    }

    protected function mapRowByHeading(array $row): array
    {
        $mapped = [];
        foreach ($row as $key => $value) {
            $mapped[$this->normalizarHeader((string) $key)] = $value;
        }

        return $mapped;
    }

    protected function usuarioImportacion(): string
    {
        return auth()->user()->name ?? 'importacion';
    }
}
