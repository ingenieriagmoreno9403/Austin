<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeCcPresupuestoMesesNullable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_cc_presupuestos')) {
            return;
        }

        $doneSql = Schema::hasColumn('tbl_cc_presupuestos', 'completado')
            ? 'AND (`completado` = 0 OR `completado` IS NULL)'
            : '';

        for ($i = 1; $i <= 12; $i++) {
            $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            DB::statement("ALTER TABLE `tbl_cc_presupuestos` MODIFY `{$col}` DECIMAL(18,2) NULL");
            DB::statement("UPDATE `tbl_cc_presupuestos` SET `{$col}` = NULL WHERE `{$col}` = 0 {$doneSql}");
        }
    }

    public function down()
    {
        if (! Schema::hasTable('tbl_cc_presupuestos')) {
            return;
        }

        for ($i = 1; $i <= 12; $i++) {
            $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            DB::statement("UPDATE `tbl_cc_presupuestos` SET `{$col}` = 0 WHERE `{$col}` IS NULL");
            DB::statement("ALTER TABLE `tbl_cc_presupuestos` MODIFY `{$col}` DECIMAL(18,2) NOT NULL DEFAULT 0");
        }
    }
}
