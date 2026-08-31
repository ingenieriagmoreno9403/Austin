<?php

namespace App\Services;

use App\Models\ProductoConexionEspecificacion;
use App\Models\Productos;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarConexionEspecificacionesService
{
    private const HOJA = 'Catalogo_Conexiones';

    /**
     * @return array{importados:int, productos_nuevos:int, productos_actualizados:int, omitidos:int}
     */
    public function importar(string $rutaArchivo): array
    {
        if (!is_file($rutaArchivo)) {
            throw new \InvalidArgumentException("No se encontró el archivo: {$rutaArchivo}");
        }

        $spreadsheet = IOFactory::load($rutaArchivo);
        $hoja = $spreadsheet->getSheetByName(self::HOJA) ?? $spreadsheet->getActiveSheet();
        $rows = $hoja->toArray(null, true, true, false);

        $importados = 0;
        $nuevos = 0;
        $actualizados = 0;
        $omitidos = 0;

        $categoriaId = (int) (DB::table('tblcategoria_producto')->orderBy('id')->value('id') ?? 0);
        $unidadId = (int) (
            DB::table('tblunidadesmedida')->where('nombre', 'like', '%pieza%')->value('id')
            ?: DB::table('tblunidadesmedida')->orderBy('id')->value('id')
            ?: 0
        );

        DB::transaction(function () use (
            $rows,
            $categoriaId,
            $unidadId,
            &$importados,
            &$nuevos,
            &$actualizados,
            &$omitidos
        ) {
            for ($i = 3; $i < count($rows); $i++) {
                $row = $rows[$i];
                $codigo = trim((string) ($row[0] ?? ''));
                $tipo = trim((string) ($row[1] ?? ''));
                if ($codigo === '' || $tipo === '') {
                    $omitidos++;
                    continue;
                }

                $diamMm = $this->num($row[2] ?? null);
                $diamPulg = trim((string) ($row[3] ?? ''));
                $rdRaw = trim((string) ($row[4] ?? ''));
                $rd = $this->parseRd($rdRaw);
                $pesoTubo = $this->num($row[5] ?? null);
                $pesoPieza = $this->num($row[6] ?? null);
                $material = trim((string) ($row[7] ?? 'PE-100')) ?: 'PE-100';

                $resultado = $this->crearOActualizarProducto(
                    $codigo,
                    $tipo,
                    $diamPulg,
                    $rdRaw,
                    $material,
                    $categoriaId,
                    $unidadId
                );
                if ($resultado['nuevo']) {
                    $nuevos++;
                } else {
                    $actualizados++;
                }

                ProductoConexionEspecificacion::updateOrCreate(
                    ['producto_id' => $resultado['producto']->id],
                    [
                        'codigo' => $codigo,
                        'tipo' => $tipo,
                        'diametro_mm' => $diamMm,
                        'diametro_nominal' => $diamPulg !== '' ? $diamPulg : null,
                        'rd' => $rd,
                        'peso_tubo_kg_m' => $pesoTubo,
                        'peso_kg_pieza' => $pesoPieza,
                        'material' => $material,
                        'estatus' => 'ACTIVO',
                    ]
                );
                $importados++;
            }
        });

        return [
            'importados' => $importados,
            'productos_nuevos' => $nuevos,
            'productos_actualizados' => $actualizados,
            'omitidos' => $omitidos,
        ];
    }

    /**
     * @return array{producto: Productos, nuevo: bool}
     */
    private function crearOActualizarProducto(
        string $codigo,
        string $tipo,
        string $diamPulg,
        string $rdRaw,
        string $material,
        int $categoriaId,
        int $unidadId
    ): array {
        $nombre = trim("{$tipo} HDPE {$diamPulg} {$rdRaw}");
        $producto = Productos::where('sku', $codigo)->first();

        if ($producto) {
            DB::table('tblproductos')->where('id', $producto->id)->update([
                'tipo_proceso' => Productos::TIPO_PROCESO_CONEXION,
                'updated_at' => now(),
            ]);
            if (empty($producto->descripcion)) {
                DB::table('tblproductos')->where('id', $producto->id)->update([
                    'descripcion' => "Conexión {$tipo} {$material} {$diamPulg} {$rdRaw}",
                ]);
            }

            return ['producto' => $producto->fresh(), 'nuevo' => false];
        }

        $id = DB::table('tblproductos')->insertGetId([
            'sku' => $codigo,
            'nombre' => $nombre,
            'descripcion' => "Conexión {$tipo} {$material} {$diamPulg} {$rdRaw}",
            'codigo_barras' => $codigo,
            'precio_unitario' => 0,
            'id_categoria' => $categoriaId ?: null,
            'tipo_proceso' => Productos::TIPO_PROCESO_CONEXION,
            'id_unidad_medida' => $unidadId ?: null,
            'id_proveedor' => null,
            'costo_compra' => 0,
            'costo_venta' => 0,
            'minima_existencia' => 0,
            'maxima_existencia' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['producto' => Productos::findOrFail($id), 'nuevo' => true];
    }

    private function parseRd(?string $raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (preg_match('/([\d.]+)/', $raw, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    private function num(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        return (float) $v;
    }
}
