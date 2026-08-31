<?php

/**
 * Limpieza de datos transaccionales: cotizaciones → pedidos → OP / satélites.
 * NO toca catálogos (productos, clientes, specs, recetas, máquinas, etc.).
 *
 * Uso: php scripts/limpiar_flujo_ventas_prueba.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function tableExists(string $table): bool
{
    return Schema::hasTable($table);
}

function countSafe(string $table): int
{
    if (!tableExists($table)) {
        return -1;
    }
    return (int) DB::table($table)->count();
}

$before = [];
$tablesWatch = [
    'tbl_reprocesos',
    'inspeccion_de_calidad_por_ordenes_de_trabajo',
    'tbl_ordenes_detalle_salidas',
    'tbl_ordenes_maquinas',
    'tbl_ordenes_detalle',
    'tbl_ruta_carga_evidencias',
    'tbl_cargas',
    'tbl_rutas_carga',
    'tbl_ordenes',
    'tbl_reporte_no_existencias',
    'tbl_pedidos_detalle',
    'tbl_pedidos',
    'tbl_cotizaciones_detalle',
    'tbl_cotizaciones',
];

echo "=== CONTEO ANTES ===\n";
foreach ($tablesWatch as $t) {
    $n = countSafe($t);
    $before[$t] = $n;
    echo $t . ': ' . ($n < 0 ? 'NO EXISTE' : $n) . "\n";
}

$foliosOp = tableExists('tbl_ordenes')
    ? DB::table('tbl_ordenes')->pluck('folio')->filter()->values()->all()
    : [];

echo "\nFolios OP a limpiar movimientos: " . (count($foliosOp) ? implode(', ', $foliosOp) : '(ninguno)') . "\n";

DB::beginTransaction();

try {
    // 1) Reproceso / calidad / salidas / etapas OP
    if (tableExists('tbl_reprocesos')) {
        $deleted['tbl_reprocesos'] = DB::table('tbl_reprocesos')->delete();
    }
    if (tableExists('inspeccion_de_calidad_por_ordenes_de_trabajo')) {
        $deleted['inspeccion_de_calidad_por_ordenes_de_trabajo'] = DB::table('inspeccion_de_calidad_por_ordenes_de_trabajo')->delete();
    }
    if (tableExists('tbl_ordenes_detalle_salidas')) {
        $deleted['tbl_ordenes_detalle_salidas'] = DB::table('tbl_ordenes_detalle_salidas')->delete();
    }
    if (tableExists('tbl_ordenes_maquinas')) {
        $deleted['tbl_ordenes_maquinas'] = DB::table('tbl_ordenes_maquinas')->delete();
    }
    if (tableExists('tbl_ordenes_detalle')) {
        $deleted['tbl_ordenes_detalle'] = DB::table('tbl_ordenes_detalle')->delete();
    }

    // 2) Cargas / rutas (flujo pedido → entrega)
    if (tableExists('tbl_ruta_carga_evidencias')) {
        $deleted['tbl_ruta_carga_evidencias'] = DB::table('tbl_ruta_carga_evidencias')->delete();
    }
    if (tableExists('tbl_cargas')) {
        $deleted['tbl_cargas'] = DB::table('tbl_cargas')->delete();
    }
    // Rutas de carga: solo las que queden sin cargas (o todas si son solo de prueba)
    if (tableExists('tbl_rutas_carga')) {
        $deleted['tbl_rutas_carga'] = DB::table('tbl_rutas_carga')->delete();
    }

    // 3) Movimientos de inventario ligados a OP (antes de borrar órdenes)
    $movDeleted = 0;
    if (tableExists('tblmovimientos_inventario') && count($foliosOp) > 0) {
        $movDeleted = DB::table('tblmovimientos_inventario')
            ->where(function ($q) use ($foliosOp) {
                foreach ($foliosOp as $folio) {
                    $q->orWhere('documento_referencia', $folio)
                        ->orWhere('documento_referencia', 'like', '%' . $folio . '%')
                        ->orWhere('observaciones', 'like', '%' . $folio . '%');
                }
            })
            ->delete();
    }
    $deleted['tblmovimientos_inventario_op'] = $movDeleted;

    // 4) Órdenes de producción
    if (tableExists('tbl_ordenes')) {
        $deleted['tbl_ordenes'] = DB::table('tbl_ordenes')->delete();
    }

    // 5) Reportes de no existencias (de pedidos/cotizaciones)
    if (tableExists('tbl_reporte_no_existencias')) {
        $deleted['tbl_reporte_no_existencias'] = DB::table('tbl_reporte_no_existencias')->delete();
    }

    // 6) Pedidos
    if (tableExists('tbl_pedidos_detalle')) {
        $deleted['tbl_pedidos_detalle'] = DB::table('tbl_pedidos_detalle')->delete();
    }
    if (tableExists('tbl_pedidos')) {
        $deleted['tbl_pedidos'] = DB::table('tbl_pedidos')->delete();
    }

    // 7) Cotizaciones
    if (tableExists('tbl_cotizaciones_detalle')) {
        $deleted['tbl_cotizaciones_detalle'] = DB::table('tbl_cotizaciones_detalle')->delete();
    }
    if (tableExists('tbl_cotizaciones')) {
        $deleted['tbl_cotizaciones'] = DB::table('tbl_cotizaciones')->delete();
    }

    DB::commit();

    echo "\n=== BORRADOS ===\n";
    foreach ($deleted as $t => $n) {
        echo $t . ': ' . (int) $n . "\n";
    }

    echo "\n=== CONTEO DESPUÉS ===\n";
    foreach ($tablesWatch as $t) {
        $n = countSafe($t);
        echo $t . ': ' . ($n < 0 ? 'NO EXISTE' : $n) . "\n";
    }

    echo "\nOK: flujo cotizaciones → pedidos → OP limpio. Catálogos intactos.\n";
    echo "NOTA: existencias (tblexistencias) NO se truncaron; si se descontó MP en pruebas, el stock puede quedar bajo.\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
