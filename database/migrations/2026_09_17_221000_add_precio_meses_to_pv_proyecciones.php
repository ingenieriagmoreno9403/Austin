<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrecioMesesToPvProyecciones extends Migration
{
    public function up()
    {
        foreach (['tbl_pv_proyecciones', 'tbl_pv_presupuestos'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            if (! Schema::hasColumn($tableName, 'precio_meses')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->json('precio_meses')->nullable()->after('costo_unitario');
                });
            }
        }
    }

    public function down()
    {
        foreach (['tbl_pv_proyecciones', 'tbl_pv_presupuestos'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            if (Schema::hasColumn($tableName, 'precio_meses')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('precio_meses');
                });
            }
        }
    }
}
