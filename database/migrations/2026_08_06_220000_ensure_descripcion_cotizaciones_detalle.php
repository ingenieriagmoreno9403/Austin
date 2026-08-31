<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureDescripcionCotizacionesDetalle extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_cotizaciones_detalle')) {
            return;
        }

        if (! Schema::hasColumn('tbl_cotizaciones_detalle', 'descripcion')) {
            DB::statement('ALTER TABLE `tbl_cotizaciones_detalle` ADD `descripcion` TEXT NULL AFTER `producto_id`');

            return;
        }

        DB::statement('ALTER TABLE `tbl_cotizaciones_detalle` MODIFY `descripcion` TEXT NULL');
    }

    public function down()
    {
        if (! Schema::hasTable('tbl_cotizaciones_detalle') || ! Schema::hasColumn('tbl_cotizaciones_detalle', 'descripcion')) {
            return;
        }

        DB::statement('ALTER TABLE `tbl_cotizaciones_detalle` MODIFY `descripcion` VARCHAR(255) NULL');
    }
}
