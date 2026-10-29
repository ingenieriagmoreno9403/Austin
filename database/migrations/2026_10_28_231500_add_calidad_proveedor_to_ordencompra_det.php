<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCalidadProveedorToOrdencompraDet extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tblordencompra_det')) {
            return;
        }

        if (! Schema::hasColumn('tblordencompra_det', 'calidad_proveedor')) {
            DB::statement("ALTER TABLE `tblordencompra_det` ADD `calidad_proveedor` VARCHAR(20) NULL");
        }

        DB::statement("
            UPDATE tblordencompra_det d
            INNER JOIN tblordencompra_enc e ON e.id = d.orden_compra_id
            INNER JOIN tblprovedores p ON p.id = e.proveedor_id
            SET d.calidad_proveedor = UPPER(TRIM(p.calificacion_proveedor))
            WHERE (d.calidad_proveedor IS NULL OR d.calidad_proveedor = '')
              AND UPPER(TRIM(COALESCE(p.calificacion_proveedor, ''))) IN ('EXCELENTE', 'BUENO', 'REGULAR', 'MALO')
        ");
    }

    public function down()
    {
        if (! Schema::hasTable('tblordencompra_det') || ! Schema::hasColumn('tblordencompra_det', 'calidad_proveedor')) {
            return;
        }

        DB::statement('ALTER TABLE `tblordencompra_det` DROP `calidad_proveedor`');
    }
}
