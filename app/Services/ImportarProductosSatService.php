<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarProductosSatService
{
    public function importar(string $archivo, bool $actualizarExistentes = true): array
    {
        if (! is_readable($archivo)) {
            throw new \InvalidArgumentException("No se puede leer el archivo: {$archivo}");
        }

        $spreadsheet = IOFactory::load($archivo);
        $sheet = null;

        foreach ($spreadsheet->getSheetNames() as $nombreHoja) {
            if (stripos($nombreHoja, 'art') !== false) {
                $sheet = $spreadsheet->getSheetByName($nombreHoja);
                break;
            }
        }

        if (! $sheet) {
            throw new \RuntimeException('No se encontró la hoja de artículos en el Excel.');
        }

        $rows = $sheet->toArray(null, true, true, true);
        array_shift($rows);

        $existentesPorSku = DB::table('tblproductos')->pluck('id', 'sku')->all();
        $categoriasValidas = DB::table('tblcategoria_producto')->pluck('id')->flip()->all();
        $unidadesValidas = DB::table('tblunidadesmedida')->pluck('id')->flip()->all();

        $stats = [
            'procesados' => 0,
            'insertados' => 0,
            'actualizados' => 0,
            'omitidos' => 0,
            'errores' => 0,
        ];

        $now = now();
        $lote = [];

        foreach ($rows as $row) {
            $stats['procesados']++;

            $sku = trim((string) ($row['A'] ?? ''));
            $clave = trim((string) ($row['H'] ?? ''));

            if ($sku === '') {
                $sku = $clave;
            }

            if ($sku === '') {
                $stats['omitidos']++;
                continue;
            }

            $nombre = trim((string) ($row['B'] ?? ''));
            if ($nombre === '') {
                $nombre = $sku;
            }

            $descripcion = trim((string) ($row['C'] ?? ''));
            if ($descripcion === '') {
                $descripcion = $nombre;
            }

            $idCategoria = (int) ($row['D'] ?? 0);
            $idUnidad = (int) ($row['E'] ?? 0);

            if (! isset($categoriasValidas[$idCategoria])) {
                $idCategoria = 51;
            }

            if (! isset($unidadesValidas[$idUnidad])) {
                $idUnidad = 2;
            }

            $claveSat = trim((string) ($row['G'] ?? ''));
            $claveSat = $claveSat !== '' ? substr($claveSat, 0, 20) : null;

            $costoCompra = $this->decimal($row['K'] ?? null);
            $costoVenta = $this->decimal($row['L'] ?? null);
            $precioUnitario = $costoVenta > 0 ? $costoVenta : ($costoCompra > 0 ? $costoCompra : 0);

            $payload = [
                'sku' => substr($sku, 0, 50),
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'id_categoria' => $idCategoria,
                'id_unidad_medida' => $idUnidad,
                'id_proveedor' => 0,
                'codigo_barras' => substr($clave !== '' ? $clave : $sku, 0, 50),
                'clave_sat' => $claveSat,
                'precio_unitario' => $precioUnitario,
                'costo_compra' => $costoCompra,
                'costo_venta' => $costoVenta,
                'minima_existencia' => $this->decimal($row['M'] ?? null),
                'maxima_existencia' => $this->decimal($row['N'] ?? null),
                'piezasxunidmedida' => $this->entero($row['O'] ?? null),
                'fecha_ultima_entrada' => $this->fecha($row['I'] ?? null),
                'updated_at' => $now,
            ];

            if (isset($existentesPorSku[$sku])) {
                if (! $actualizarExistentes) {
                    $stats['omitidos']++;
                    continue;
                }

                DB::table('tblproductos')
                    ->where('id', $existentesPorSku[$sku])
                    ->update($payload);

                $stats['actualizados']++;
                continue;
            }

            $payload['created_at'] = $now;
            $lote[] = $payload;

            if (count($lote) >= 200) {
                DB::table('tblproductos')->insert($lote);
                $stats['insertados'] += count($lote);
                $lote = [];
            }
        }

        if (! empty($lote)) {
            DB::table('tblproductos')->insert($lote);
            $stats['insertados'] += count($lote);
        }

        return $stats;
    }

    private function decimal(mixed $valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        return round((float) $valor, 2);
    }

    private function entero(mixed $valor): ?int
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (int) $valor;
    }

    private function fecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $valor);

            return $fecha->format('Y-m-d');
        }

        $texto = trim((string) $valor);
        if ($texto === '') {
            return null;
        }

        $timestamp = strtotime($texto);

        return $timestamp ? date('Y-m-d', $timestamp) : substr($texto, 0, 10);
    }
}
