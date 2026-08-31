<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $name = array_values((array) $t)[0];
    if (stripos($name, 'categ') !== false) {
        echo $name . PHP_EOL;
        echo '  cols: ' . implode(', ', Schema::getColumnListing($name)) . PHP_EOL;
        foreach (DB::table($name)->limit(5)->get() as $r) {
            echo '  ' . json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
    }
}
echo "id_categoria mode: " . DB::table('tblproductos')->select('id_categoria')->groupBy('id_categoria')->orderByDesc(DB::raw('count(*)'))->limit(5)->pluck('id_categoria')->implode(',') . PHP_EOL;
