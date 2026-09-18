<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoBudgetToPvCiclos extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_pv_ciclos')) {
            return;
        }
        if (! Schema::hasColumn('tbl_pv_ciclos', 'tipo_budget')) {
            Schema::table('tbl_pv_ciclos', function (Blueprint $table) {
                $table->string('tipo_budget', 10)->nullable()->after('tipo_cambio_meses');
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('tbl_pv_ciclos')) {
            return;
        }
        if (Schema::hasColumn('tbl_pv_ciclos', 'tipo_budget')) {
            Schema::table('tbl_pv_ciclos', function (Blueprint $table) {
                $table->dropColumn('tipo_budget');
            });
        }
    }
}
