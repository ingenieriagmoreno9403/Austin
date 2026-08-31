<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VincularCatalogoMaestroTuberiaService
{
    /**
     * @return array<string, int>
     */
    public function vincular(string $archivo, bool $actualizarExistentes = true): array
    {
        if (! is_readable($archivo)) {
            throw new \InvalidArgumentException("No se puede leer el archivo: {$archivo}");
        }

        $index = $this->construirIndiceCatalogo($archivo);
        $productos = DB::table('tblproductos')
            ->where(function ($query) {
                $query->where('sku', 'like', 'TUB%')
                    ->orWhere('id_categoria', 50);
            })
            ->get(['id', 'sku', 'nombre']);

        $stats = [
            'catalogo_combos' => count($index),
            'productos_analizados' => $productos->count(),
            'especificaciones_creadas' => 0,
            'productos_vinculados' => 0,
            'ya_vinculados' => 0,
            'sin_coincidencia' => 0,
            'sin_parseo' => 0,
        ];

        $now = now();

        DB::transaction(function () use ($productos, $index, $actualizarExistentes, &$stats, $now) {
            foreach ($productos as $producto) {
                $specCatalogo = $this->resolverSpecCatalogo((string) $producto->sku, (string) $producto->nombre, $index);

                if (! $specCatalogo) {
                    if ($this->parseNombreTubo((string) $producto->nombre) || $this->parseSkuTubo($producto->sku)) {
                        $stats['sin_coincidencia']++;
                    } else {
                        $stats['sin_parseo']++;
                    }
                    continue;
                }

                $existente = DB::table('tbl_producto_tubo_especificaciones')
                    ->where('producto_id', $producto->id)
                    ->where('diametro_nominal', $specCatalogo['diametro_nominal'])
                    ->where('psi', $specCatalogo['psi'])
                    ->first();

                if ($existente) {
                    $specId = $existente->id;
                    if ($actualizarExistentes) {
                        DB::table('tbl_producto_tubo_especificaciones')
                            ->where('id', $specId)
                            ->update([
                                'material' => $specCatalogo['material'],
                                'diametro_exterior_pulg' => $specCatalogo['diametro_exterior_pulg'],
                                'rd' => $specCatalogo['rd'],
                                'espesor_pulg' => $specCatalogo['espesor_pulg'],
                                'peso_kg_m' => $specCatalogo['peso_kg_m'],
                                'estatus' => 'ACTIVO',
                                'updated_at' => $now,
                            ]);
                    }
                } else {
                    $specId = DB::table('tbl_producto_tubo_especificaciones')->insertGetId([
                        'producto_id' => $producto->id,
                        'material' => $specCatalogo['material'],
                        'diametro_nominal' => $specCatalogo['diametro_nominal'],
                        'diametro_exterior_pulg' => $specCatalogo['diametro_exterior_pulg'],
                        'psi' => $specCatalogo['psi'],
                        'rd' => $specCatalogo['rd'],
                        'espesor_pulg' => $specCatalogo['espesor_pulg'],
                        'peso_kg_m' => $specCatalogo['peso_kg_m'],
                        'estatus' => 'ACTIVO',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $stats['especificaciones_creadas']++;
                }

                $productoActual = DB::table('tblproductos')->where('id', $producto->id)->first(['id_especificacion']);
                if ($productoActual?->id_especificacion) {
                    $stats['ya_vinculados']++;
                }

                DB::table('tblproductos')
                    ->where('id', $producto->id)
                    ->update([
                        'id_especificacion' => $specId,
                        'updated_at' => $now,
                    ]);

                $stats['productos_vinculados']++;
            }
        });

        return $stats;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function construirIndiceCatalogo(string $archivo): array
    {
        $spreadsheet = IOFactory::load($archivo);
        $sheet = $spreadsheet->getSheetByName('Catalogo_Maestro_Tuberia');

        if (! $sheet) {
            throw new \RuntimeException('No se encontró la hoja Catalogo_Maestro_Tuberia.');
        }

        $index = [];

        foreach (range(4, (int) $sheet->getHighestDataRow()) as $row) {
            $codigo = trim((string) $sheet->getCell("A{$row}")->getCalculatedValue());
            if ($codigo === '' || stripos($codigo, 'código') !== false) {
                continue;
            }

            $diametroNominal = $this->normalizarDiametroCatalogo((string) $sheet->getCell("E{$row}")->getCalculatedValue());
            $rd = $this->parseRd($sheet->getCell("F{$row}")->getCalculatedValue());
            $psi = (float) $sheet->getCell("G{$row}")->getCalculatedValue();
            $bridada = strtolower(trim((string) $sheet->getCell("B{$row}")->getCalculatedValue())) === 'bridada';
            $diametroExterior = round(((float) $sheet->getCell("D{$row}")->getCalculatedValue()) / 25.4, 6);
            $pesoKgM = (float) $sheet->getCell("H{$row}")->getCalculatedValue();
            $material = trim((string) $sheet->getCell("N{$row}")->getCalculatedValue()) ?: 'HDPE PE4710';

            $key = $this->claveSpec($diametroNominal, $rd, $bridada);

            if (! isset($index[$key])) {
                $index[$key] = [
                    'codigo_catalogo' => $codigo,
                    'diametro_nominal' => $diametroNominal,
                    'diametro_exterior_pulg' => $diametroExterior,
                    'rd' => $rd,
                    'psi' => $psi,
                    'peso_kg_m' => $pesoKgM,
                    'material' => $material,
                    'espesor_pulg' => $rd > 0 ? round($diametroExterior / $rd, 6) : null,
                ];
            }
        }

        return $index;
    }

    /**
     * @param array<string, array<string, mixed>> $index
     * @return array<string, mixed>|null
     */
    private function resolverSpecCatalogo(string $sku, string $nombre, array $index): ?array
    {
        $parsed = $this->parseNombreTubo($nombre);
        if ($parsed) {
            $key = $this->claveSpec($parsed['diam'], $parsed['rd'], $parsed['bridada']);
            if (isset($index[$key])) {
                return $index[$key];
            }
        }

        $skuParsed = $this->parseSkuTubo($sku);
        if ($skuParsed) {
            $bridada = $skuParsed['bridada'] || stripos($nombre, 'BRID') !== false;
            $key = $this->claveSpec($skuParsed['diam'], $skuParsed['rd'], $bridada);
            if (isset($index[$key])) {
                return $index[$key];
            }
        }

        $codigo = strtoupper(trim($sku));
        foreach ($index as $spec) {
            if (strtoupper($spec['codigo_catalogo']) === $codigo) {
                return $spec;
            }
        }

        return null;
    }

    /**
     * @return array{diam:string, rd:float, bridada:bool}|null
     */
    private function parseNombreTubo(string $nombre): ?array
    {
        if (! preg_match('/DE\s+(.+?)\s+RD\s*([\d.]+)/i', $nombre, $matches)) {
            return null;
        }

        return [
            'diam' => $this->normalizarDiametroCatalogo(trim($matches[1])),
            'rd' => (float) $matches[2],
            'bridada' => (bool) preg_match('/BRID/i', $nombre),
        ];
    }

    /**
     * @return array{diam:string, rd:float, bridada:bool}|null
     */
    private function parseSkuTubo(string $sku): ?array
    {
        if (! preg_match('/^TUBO(BRI|PEAD)(\d{2})(\d{3,4})/i', $sku, $matches)) {
            return null;
        }

        $diam = $this->diametroDesdeCodigo($matches[3]);
        if (! $diam) {
            return null;
        }

        return [
            'diam' => $diam,
            'rd' => (float) $matches[2],
            'bridada' => strtoupper($matches[1]) === 'BRI',
        ];
    }

    private function diametroDesdeCodigo(string $code): ?string
    {
        $map = [
            '012' => '1/2"',
            '034' => '3/4"',
            '100' => '1"',
            '114' => '1-1/4"',
            '112' => '1-1/2"',
            '200' => '2"',
            '212' => '2-1/2"',
            '300' => '3"',
            '400' => '4"',
            '500' => '5"',
            '600' => '6"',
            '800' => '8"',
            '1000' => '10"',
            '1200' => '12"',
            '1400' => '14"',
            '1600' => '16"',
            '1800' => '18"',
            '2000' => '20"',
            '2400' => '24"',
        ];

        if (isset($map[$code])) {
            return $map[$code];
        }

        if (strlen($code) === 4 && isset($map[substr($code, -3)])) {
            return $map[substr($code, -3)];
        }

        return null;
    }

    private function normalizarDiametroCatalogo(string $diametro): string
    {
        $diametro = trim($diametro);
        $diametro = str_replace(['  ', 'PULG'], [' ', ''], $diametro);

        if (! str_ends_with($diametro, '"')) {
            $diametro .= '"';
        }

        return preg_replace('/\s+/', ' ', $diametro) ?? $diametro;
    }

    private function claveSpec(string $diametro, float $rd, bool $bridada): string
    {
        return $this->normalizarDiametroCatalogo($diametro) . '|' . $rd . '|' . ($bridada ? '1' : '0');
    }

    private function parseRd(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_numeric($valor)) {
            return (float) $valor;
        }

        if (preg_match('/([\d.]+)/', (string) $valor, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }
}
