<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCicloBaseToPvCiclos extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_pv_ciclos')) {
            return;
        }
        if (! Schema::hasColumn('tbl_pv_ciclos', 'ciclo_base')) {
            Schema::table('tbl_pv_ciclos', function (Blueprint $table) {
                $table->string('ciclo_base', 40)->nullable()->after('tipo_budget');
            });
        }
        if (! Schema::hasColumn('tbl_pv_ciclos', 'venta_real_sap')) {
            Schema::table('tbl_pv_ciclos', function (Blueprint $table) {
                $table->boolean('venta_real_sap')->default(false)->after('ciclo_base');
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('tbl_pv_ciclos')) {
            return;
        }
        Schema::table('tbl_pv_ciclos', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_pv_ciclos', 'venta_real_sap')) {
                $table->dropColumn('venta_real_sap');
            }
            if (Schema::hasColumn('tbl_pv_ciclos', 'ciclo_base')) {
                $table->dropColumn('ciclo_base');
            }
        });
    }
}
