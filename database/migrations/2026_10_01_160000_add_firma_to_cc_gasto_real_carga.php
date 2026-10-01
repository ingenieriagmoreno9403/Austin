<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirmaToCcGastoRealCarga extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_cc_gasto_real_carga') || Schema::hasColumn('tbl_cc_gasto_real_carga', 'firma')) {
            return;
        }

        Schema::table('tbl_cc_gasto_real_carga', function (Blueprint $table) {
            $table->string('firma', 64)->nullable()->after('origen');
        });
    }

    public function down()
    {
        if (! Schema::hasTable('tbl_cc_gasto_real_carga') || ! Schema::hasColumn('tbl_cc_gasto_real_carga', 'firma')) {
            return;
        }

        Schema::table('tbl_cc_gasto_real_carga', function (Blueprint $table) {
            $table->dropColumn('firma');
        });
    }
}
