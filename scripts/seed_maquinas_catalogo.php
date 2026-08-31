<?php

/**
 * Alta del catálogo de máquinas IOHISA.
 * Tipo → otrosconceptos1 | Área → ubicacion + otrosconceptos2 | Marca/Modelo/Año → otrosconceptos3 (vacío)
 *
 * Uso: php scripts/seed_maquinas_catalogo.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$now = now();

// Reutilizar almacén/ubicación de una máquina existente si hay.
$ref = DB::table('tbl_maquinas')->whereNotNull('id_almacen')->orderBy('id')->first();
$idAlmacen = $ref->id_almacen ?? null;
$idUbicacion = $ref->id_ubicacion ?? null;

$maquinas = [
    ['codigo' => 'EXT-01', 'nombre' => 'Extrusora 1', 'tipo' => 'Extrusora', 'area' => 'Extrusión'],
    ['codigo' => 'EXT-02', 'nombre' => 'Extrusora 2', 'tipo' => 'Extrusora', 'area' => 'Extrusión'],
    ['codigo' => 'EXT-03', 'nombre' => 'Extrusora 3', 'tipo' => 'Extrusora', 'area' => 'Extrusión'],
    ['codigo' => 'EXT-04', 'nombre' => 'Extrusora 4', 'tipo' => 'Extrusora', 'area' => 'Extrusión'],
    ['codigo' => 'EXT-05', 'nombre' => 'Extrusora 5', 'tipo' => 'Extrusora', 'area' => 'Extrusión'],
    ['codigo' => 'INY-01', 'nombre' => 'Inyectora 1', 'tipo' => 'Inyectora', 'area' => 'Inyección'],
    ['codigo' => 'INY-02', 'nombre' => 'Inyectora 2', 'tipo' => 'Inyectora', 'area' => 'Inyección'],
    ['codigo' => 'CNC-01', 'nombre' => 'Torno CNC', 'tipo' => 'Torno CNC', 'area' => 'Maquinado'],
    ['codigo' => 'TRI-01', 'nombre' => 'Trituradora', 'tipo' => 'Trituradora', 'area' => 'Reciclado', 'para_reproceso' => true],
    ['codigo' => 'PEL-01', 'nombre' => 'Peletizadora', 'tipo' => 'Peletizadora', 'area' => 'Reciclado', 'para_reproceso' => true],
    ['codigo' => 'TER-01', 'nombre' => 'Termofusión 1', 'tipo' => 'Equipo termofusión', 'area' => 'Conexiones'],
    ['codigo' => 'TER-02', 'nombre' => 'Termofusión 2', 'tipo' => 'Equipo termofusión', 'area' => 'Conexiones'],
    ['codigo' => 'TER-03', 'nombre' => 'Termofusión 3', 'tipo' => 'Equipo termofusión', 'area' => 'Conexiones'],
    ['codigo' => 'TER-04', 'nombre' => 'Termofusión 4', 'tipo' => 'Equipo termofusión', 'area' => 'Conexiones'],
    ['codigo' => 'TER-05', 'nombre' => 'Termofusión 5', 'tipo' => 'Equipo termofusión', 'area' => 'Conexiones'],
    ['codigo' => 'TER-06', 'nombre' => 'Termofusión 6', 'tipo' => 'Equipo termofusión', 'area' => 'Conexiones'],
    ['codigo' => 'TER-07', 'nombre' => 'Termofusión 7', 'tipo' => 'Equipo termofusión', 'area' => 'Conexiones'],
    ['codigo' => 'TER-08', 'nombre' => 'Termofusión 8', 'tipo' => 'Equipo termofusión', 'area' => 'Conexiones'],
    ['codigo' => 'TER-09', 'nombre' => 'Termofusión 9', 'tipo' => 'Equipo termofusión', 'area' => 'Conexiones'],
    ['codigo' => 'TER-10', 'nombre' => 'Termofusión 10', 'tipo' => 'Equipo termofusión', 'area' => 'Conexiones'],
];

DB::beginTransaction();

try {
    $creadas = 0;
    $actualizadas = 0;

    foreach ($maquinas as $m) {
        $paraReproceso = !empty($m['para_reproceso']);
        $payload = [
            'nombre' => $m['nombre'],
            'descripcion' => $m['tipo'] . ' · ' . $m['area'],
            'ubicacion' => $m['area'],
            'id_almacen' => $idAlmacen,
            'id_ubicacion' => $idUbicacion,
            'estatus' => 'A', // Operativa
            'para_reproceso' => $paraReproceso ? 1 : 0,
            'otrosconceptos1' => $m['tipo'],           // Tipo
            'otrosconceptos2' => $m['area'],           // Área
            'otrosconceptos3' => null,                 // Marca / Modelo / Año (opcional, vacío)
            'updated_at' => $now,
        ];

        $existente = DB::table('tbl_maquinas')->where('codigo', $m['codigo'])->first();
        if ($existente) {
            DB::table('tbl_maquinas')->where('id', $existente->id)->update($payload);
            $actualizadas++;
            echo "UPD {$m['codigo']} #{$existente->id} — {$m['nombre']}\n";
            continue;
        }

        $id = DB::table('tbl_maquinas')->insertGetId(array_merge($payload, [
            'codigo' => $m['codigo'],
            'capacidad_pr_hora' => null,
            'ruta_manual' => null,
            'created_at' => $now,
        ]));
        $creadas++;
        echo "OK  {$m['codigo']} #{$id} — {$m['nombre']} ({$m['tipo']} / {$m['area']})"
            . ($paraReproceso ? ' [reproceso]' : '') . "\n";
    }

    DB::commit();

    echo "\nCreadas: {$creadas} | Actualizadas: {$actualizadas}\n";
    echo "Total máquinas: " . DB::table('tbl_maquinas')->count() . "\n";
    echo "Activas (A): " . DB::table('tbl_maquinas')->where('estatus', 'A')->count() . "\n";
    echo "Para reproceso: " . DB::table('tbl_maquinas')->where('para_reproceso', 1)->count() . "\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
