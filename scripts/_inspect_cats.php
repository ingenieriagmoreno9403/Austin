<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (Schema::hasTable('tblcategorias')) {
    foreach (DB::table('tblcategorias')->limit(30)->get() as $c) {
        echo json_encode($c, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
} elseif (Schema::hasTable('tblcategoria')) {
    echo 'tblcategoria\n';
} else {
    echo "no categorias table\n";
    // find category tables
}
