<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "productos cols sample relevant:\n";
$cols = Schema::getColumnListing('tblproductos');
echo implode(', ', $cols) . PHP_EOL;
$p = DB::table('tblproductos')->orderByDesc('id')->first();
echo "sample producto: " . json_encode($p, JSON_UNESCAPED_UNICODE) . PHP_EOL;

echo "\nexistencias cols: " . implode(', ', Schema::getColumnListing('tblexistencias')) . PHP_EOL;
$e = DB::table('tblexistencias')->orderByDesc('id')->first();
echo "sample existencia: " . json_encode($e, JSON_UNESCAPED_UNICODE) . PHP_EOL;

echo "\nunidades:\n";
foreach (DB::table('tblunidadesmedida')->limit(15)->get() as $u) {
    echo $u->id . ' ' . ($u->nombre ?? '') . ' ' . ($u->abreviacion ?? '') . PHP_EOL;
}

echo "\nmaquina_refacciones: " . (Schema::hasTable('tbl_maquina_refacciones') ? 'si' : 'no') . PHP_EOL;
if (Schema::hasTable('tbl_maquina_refacciones')) {
    echo implode(', ', Schema::getColumnListing('tbl_maquina_refacciones')) . PHP_EOL;
}

echo "\nubicaciones REFACCIONES:\n";
foreach (DB::table('tblubicaciones')->where('folio_interno', 'like', 'REFACCIONES %')->orderBy('folio_interno')->get(['id','folio_interno','id_almacen']) as $u) {
    echo "{$u->id} {$u->folio_interno} alm={$u->id_almacen}\n";
}

echo "\nmaquinas tipo:\n";
foreach (DB::table('tbl_maquinas')->whereIn('codigo', ['EXT-01','TRI-01','PEL-01'])->get(['id','codigo','otrosconceptos1','id_ubicacion']) as $m) {
    echo json_encode($m, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
