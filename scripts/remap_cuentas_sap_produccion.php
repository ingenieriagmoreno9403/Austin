<?php

/**
 * Producción: respaldo + remap de cuentas CC al FormatCode SAP
 * (únicos, pensiones 6000 e indemnizaciones 879/945 → par SAP).
 *
 * Sube también:
 *   app/Console/Commands/RemapCuentasSapCommand.php
 *   app/Http/Controllers/CentrosCostosController.php
 *   public/js/centros-costos.js
 *
 * Uso (SSH, carpeta del proyecto):
 *   php scripts/remap_cuentas_sap_produccion.php
 *
 * El respaldo queda en storage/app/cc_backup_cuentas_AAAAMMDD_HHMMSS.sql
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo por consola.');
}

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tablas = [
    'tbl_cc_asignacion_cuentas',
    'tbl_cc_presupuestos',
    'tbl_cc_grupo_cuentas',
];

foreach ($tablas as $tabla) {
    if (! Schema::hasTable($tabla)) {
        fwrite(STDERR, "Falta la tabla {$tabla}.\n");
        exit(1);
    }
}

$comando = 'app/Console/Commands/RemapCuentasSapCommand.php';
if (! is_file(dirname(__DIR__).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $comando))) {
    fwrite(STDERR, "Sube primero {$comando}\n");
    exit(1);
}

$dir = storage_path('app');
if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
    fwrite(STDERR, "No se pudo usar storage/app.\n");
    exit(1);
}

$backup = $dir.DIRECTORY_SEPARATOR.'cc_backup_cuentas_'.date('Ymd_His').'.sql';
$fh = fopen($backup, 'wb');
if ($fh === false) {
    fwrite(STDERR, "No se pudo crear el respaldo.\n");
    exit(1);
}

fwrite($fh, "-- Respaldo CC antes de remap SAP ".date('c')."\n");
fwrite($fh, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

foreach ($tablas as $tabla) {
    echo "Respaldo {$tabla}...\n";
    fwrite($fh, "DELETE FROM `{$tabla}`;\n");
    DB::table($tabla)->orderBy('id')->chunkById(200, function ($rows) use ($fh, $tabla) {
        foreach ($rows as $row) {
            $vals = [];
            foreach ((array) $row as $val) {
                if ($val === null) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = "'".str_replace(["\\", "'"], ["\\\\", "\\'"], (string) $val)."'";
                }
            }
            fwrite($fh, 'INSERT INTO `'.$tabla.'` VALUES ('.implode(',', $vals).");\n");
        }
    });
    fwrite($fh, "\n");
}

fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
fclose($fh);
echo "Respaldo: {$backup}\n\n";

echo "Remap SAP + indemnizaciones...\n";
$codigo = Artisan::call('cc:remap-cuentas-sap', ['--indemnizaciones' => true]);
echo Artisan::output();

if ($codigo !== 0) {
    fwrite(STDERR, "El remap terminó con código {$codigo}. El respaldo no se tocó.\n");
    exit($codigo);
}

echo "Listo. Si algo salió mal, importa el SQL del respaldo en esas 3 tablas.\n";
exit(0);
