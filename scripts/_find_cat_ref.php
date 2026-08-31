<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (DB::table('tblcategoria_producto')->where('nombre', 'like', '%refacc%')->orWhere('nombre', 'like', '%repuest%')->orWhere('subcategoria', 'like', '%refacc%')->get() as $c) {
    echo json_encode($c, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
echo "total cats: " . DB::table('tblcategoria_producto')->count() . PHP_EOL;
// show more
foreach (DB::table('tblcategoria_producto')->orderBy('id')->get() as $c) {
    if (preg_match('/refacc|repuest|mant|consum|pieza|mecanic/i', $c->nombre . ' ' . ($c->subcategoria ?? ''))) {
        echo $c->id . ' | ' . $c->nombre . ' | ' . $c->subcategoria . PHP_EOL;
    }
}
