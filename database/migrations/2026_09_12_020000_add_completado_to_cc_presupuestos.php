<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCompletadoToCcPresupuestos extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_cc_presupuestos')) {
            return;
        }
        if (! Schema::hasColumn('tbl_cc_presupuestos', 'completado')) {
            Schema::table('tbl_cc_presupuestos', function (Blueprint $table) {
                $table->boolean('completado')->default(false)->after('mes_12');
            });
        }

        DB::table('tbl_cc_presupuestos')
            ->where('completado', false)
            ->where(function ($q) {
                $q->where('mes_01', '!=', 0)
                    ->orWhere('mes_02', '!=', 0)
                    ->orWhere('mes_03', '!=', 0)
                    ->orWhere('mes_04', '!=', 0)
                    ->orWhere('mes_05', '!=', 0)
                    ->orWhere('mes_06', '!=', 0)
                    ->orWhere('mes_07', '!=', 0)
                    ->orWhere('mes_08', '!=', 0)
                    ->orWhere('mes_09', '!=', 0)
                    ->orWhere('mes_10', '!=', 0)
                    ->orWhere('mes_11', '!=', 0)
                    ->orWhere('mes_12', '!=', 0);
            })
            ->update(['completado' => true]);
    }

    public function down()
    {
        if (Schema::hasTable('tbl_cc_presupuestos') && Schema::hasColumn('tbl_cc_presupuestos', 'completado')) {
            Schema::table('tbl_cc_presupuestos', function (Blueprint $table) {
                $table->dropColumn('completado');
            });
        }
    }
}
