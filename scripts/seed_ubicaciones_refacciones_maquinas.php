<?php

/**
 * Crea en ALMACÉN GENERAL una ubicación de refacciones por máquina
 * y la asigna a id_ubicacion / id_almacen de cada máquina del catálogo.
 *
 * Uso: php scripts/seed_ubicaciones_refacciones_maquinas.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$now = now();

$almacen = DB::table('tblalmacenes')
    ->where('estado', 'A')
    ->whereRaw('UPPER(folio_interno) LIKE ?', ['%ALMACEN GENERAL%'])
    ->first();

if (!$almacen) {
    echo "ERROR: No se encontró el almacén general.\n";
    exit(1);
}

$codigos = [
    'EXT-01', 'EXT-02', 'EXT-03', 'EXT-04', 'EXT-05',
    'INY-01', 'INY-02',
    'CNC-01',
    'TRI-01', 'PEL-01',
    'TER-01', 'TER-02', 'TER-03', 'TER-04', 'TER-05',
    'TER-06', 'TER-07', 'TER-08', 'TER-09', 'TER-10',
];

$maquinas = DB::table('tbl_maquinas')
    ->whereIn('codigo', $codigos)
    ->orderBy('codigo')
    ->get();

if ($maquinas->isEmpty()) {
    echo "ERROR: No hay máquinas del catálogo para vincular.\n";
    exit(1);
}

DB::beginTransaction();

try {
    $creadas = 0;
    $reutilizadas = 0;
    $vinculadas = 0;

    foreach ($maquinas as $maquina) {
        $folio = 'REFACCIONES ' . strtoupper(trim((string) $maquina->codigo));
        $tipo = $maquina->otrosconceptos1 ?: null;
        $area = $maquina->otrosconceptos2 ?: null;

        // descripcion/observaciones son varchar(100) en BD.
        $descripcion = substr('Refacciones ' . $maquina->nombre . ' (' . $maquina->codigo . ')', 0, 100);
        $observaciones = substr(
            trim('Refacciones máquina ' . $maquina->codigo . ($area ? ' · ' . $area : '')),
            0,
            100
        );

        $ubicacion = DB::table('tblubicaciones')
            ->where('id_almacen', $almacen->id)
            ->whereRaw('UPPER(folio_interno) = ?', [strtoupper($folio)])
            ->first();

        if (!$ubicacion) {
            $ubicacionId = (int) DB::table('tblubicaciones')->insertGetId([
                'id_almacen' => $almacen->id,
                'folio_interno' => $folio,
                'descripcion' => $descripcion,
                'id_tipo_ubicacion' => null,
                'capacidad' => null,
                'nivel' => null,
                'cordenadas' => null,
                'observaciones' => $observaciones,
                'otrosconceptos1' => substr((string) $maquina->codigo, 0, 100),
                'otrosconceptos2' => $tipo ? substr((string) $tipo, 0, 100) : null,
                'otrosconceptos3' => null, // decimal en esta tabla
                'espacio' => null,
                'ubicacion' => null, // int en esta tabla
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $creadas++;
            echo "OK  ubi #{$ubicacionId} {$folio}\n";
        } else {
            $ubicacionId = (int) $ubicacion->id;
            DB::table('tblubicaciones')->where('id', $ubicacionId)->update([
                'descripcion' => $descripcion,
                'observaciones' => $observaciones,
                'otrosconceptos1' => substr((string) $maquina->codigo, 0, 100),
                'otrosconceptos2' => $tipo ? substr((string) $tipo, 0, 100) : null,
                'updated_at' => $now,
            ]);
            $reutilizadas++;
            echo "UPD ubi #{$ubicacionId} {$folio}\n";
        }

        DB::table('tbl_maquinas')->where('id', $maquina->id)->update([
            'id_almacen' => $almacen->id,
            'id_ubicacion' => $ubicacionId,
            'updated_at' => $now,
        ]);
        $vinculadas++;
        echo "   → máquina {$maquina->codigo} (#{$maquina->id}) vinculada\n";
    }

    DB::commit();

    echo "\nAlmacén general: #{$almacen->id} {$almacen->folio_interno}\n";
    echo "Ubicaciones creadas: {$creadas}\n";
    echo "Ubicaciones reutilizadas/actualizadas: {$reutilizadas}\n";
    echo "Máquinas vinculadas: {$vinculadas}\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
