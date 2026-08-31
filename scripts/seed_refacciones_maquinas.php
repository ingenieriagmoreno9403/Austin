<?php

/**
 * Alta de refacciones (producto + existencia 0 en ubicaciones de máquina + vínculo maquina-refaccion).
 * Uso: php scripts/seed_refacciones_maquinas.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$now = now();
$almacenId = (int) (DB::table('tblalmacenes')
    ->where('estado', 'A')
    ->whereRaw('UPPER(folio_interno) LIKE ?', ['%ALMACEN GENERAL%'])
    ->value('id') ?: 0);

if ($almacenId <= 0) {
    echo "ERROR: No hay almacén general.\n";
    exit(1);
}

$unidadPza = (int) (DB::table('tblunidadesmedida')->where('abreviacion', 'PZA')->value('id') ?: 2);
$unidadM = (int) (DB::table('tblunidadesmedida')->where('abreviacion', 'M')->value('id') ?: 3);
$categoriaId = (int) (DB::table('tblcategoria_producto')->where('id', 8)->value('id') ?: 8);

$refacciones = [
    [
        'sku' => 'REF-001',
        'nombre' => 'Resistencia de banda zona 1',
        'tipo_maq' => 'Extrusora',
        'unidad_id' => $unidadPza,
    ],
    [
        'sku' => 'REF-002',
        'nombre' => 'Husillo / tornillo extrusor',
        'tipo_maq' => 'Extrusora',
        'unidad_id' => $unidadPza,
    ],
    [
        'sku' => 'REF-003',
        'nombre' => 'Termopar tipo J',
        'tipo_maq' => 'Extrusora',
        'unidad_id' => $unidadPza,
    ],
    [
        'sku' => 'REF-004',
        'nombre' => 'Cuchilla de triturado',
        'tipo_maq' => 'Trituradora',
        'unidad_id' => $unidadPza,
    ],
    [
        'sku' => 'REF-005',
        'nombre' => 'Banda transportadora',
        'tipo_maq' => 'Peletizadora',
        'unidad_id' => $unidadM,
    ],
];

DB::beginTransaction();

try {
    $productosCreados = 0;
    $existenciasCreadas = 0;
    $vinculos = 0;

    foreach ($refacciones as $ref) {
        $producto = DB::table('tblproductos')->where('sku', $ref['sku'])->first();
        if (!$producto) {
            $productoId = (int) DB::table('tblproductos')->insertGetId([
                'sku' => $ref['sku'],
                'precio_unitario' => 0,
                'nombre' => strtoupper($ref['nombre']),
                'id_categoria' => $categoriaId,
                'tipo_proceso' => 'OTRO',
                'id_unidad_medida' => $ref['unidad_id'],
                'id_especificacion' => null,
                'id_proveedor' => 0,
                'costo_compra' => 0,
                'costo_venta' => 0,
                'minima_existencia' => 0,
                'maxima_existencia' => 0,
                'fecha_ultima_entrada' => null,
                'fecha_ultima_salida' => null,
                'ruta_img1' => null,
                'ruta_img2' => null,
                'ruta_img3' => null,
                'codigo_barras' => $ref['sku'],
                'clave_sat' => null,
                'otrosconceptos1' => 'REFACCION',
                'otrosconceptos2' => $ref['tipo_maq'],
                'otrosconceptos3' => null,
                'descripcion' => 'Refacción · aplica a ' . $ref['tipo_maq'] . ' · ' . $ref['nombre'],
                'created_at' => $now,
                'updated_at' => $now,
                'piezasxunidmedida' => null,
            ]);
            $productosCreados++;
            echo "OK producto {$ref['sku']} #{$productoId}\n";
        } else {
            $productoId = (int) $producto->id;
            DB::table('tblproductos')->where('id', $productoId)->update([
                'nombre' => strtoupper($ref['nombre']),
                'id_categoria' => $categoriaId,
                'id_unidad_medida' => $ref['unidad_id'],
                'otrosconceptos1' => 'REFACCION',
                'otrosconceptos2' => $ref['tipo_maq'],
                'descripcion' => 'Refacción · aplica a ' . $ref['tipo_maq'] . ' · ' . $ref['nombre'],
                'updated_at' => $now,
            ]);
            echo "UPD producto {$ref['sku']} #{$productoId}\n";
        }

        $maquinas = DB::table('tbl_maquinas')
            ->where('estatus', 'A')
            ->where('otrosconceptos1', $ref['tipo_maq'])
            ->whereNotNull('id_ubicacion')
            ->get(['id', 'codigo', 'id_ubicacion', 'id_almacen']);

        if ($maquinas->isEmpty()) {
            echo "  WARN: sin máquinas tipo {$ref['tipo_maq']}\n";
            continue;
        }

        foreach ($maquinas as $maquina) {
            $ubiId = (int) $maquina->id_ubicacion;
            $almId = (int) ($maquina->id_almacen ?: $almacenId);

            $ex = DB::table('tblexistencias')
                ->where('id_producto', $productoId)
                ->where('id_almacen', $almId)
                ->where('id_ubicacion', $ubiId)
                ->first();

            if (!$ex) {
                DB::table('tblexistencias')->insert([
                    'id_producto' => $productoId,
                    'id_almacen' => $almId,
                    'id_ubicacion' => $ubiId,
                    'cantidad_existente' => 0,
                    'cantidad_reservada' => 0,
                    'otros_conceptos1' => null,
                    'otros_conceptos2' => null,
                    'otros_conceptos3' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'id_estado_movinv' => 2,
                    'productos_arecibir' => 0,
                ]);
                $existenciasCreadas++;
                echo "  + existencia 0 en {$maquina->codigo} (ubi #{$ubiId})\n";
            } else {
                // Mantener stock actual si ya tenía; solo asegurar registro.
                echo "  = existencia ya existe en {$maquina->codigo} (qty {$ex->cantidad_existente})\n";
            }

            $yaVinculo = DB::table('tbl_maquina_refacciones')
                ->where('id_maquina', $maquina->id)
                ->where('id_producto', $productoId)
                ->exists();

            if (!$yaVinculo) {
                DB::table('tbl_maquina_refacciones')->insert([
                    'id_maquina' => $maquina->id,
                    'id_producto' => $productoId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $vinculos++;
            }
        }
    }

    DB::commit();

    echo "\nProductos creados: {$productosCreados}\n";
    echo "Existencias creadas (0): {$existenciasCreadas}\n";
    echo "Vínculos máquina-refacción: {$vinculos}\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
