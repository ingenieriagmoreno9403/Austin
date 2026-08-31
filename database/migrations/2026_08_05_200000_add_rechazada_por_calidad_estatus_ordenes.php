<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_ordenes')) {
            return;
        }

        DB::statement("ALTER TABLE tbl_ordenes MODIFY COLUMN estatus ENUM(
            'PREPARANDO_MAQUINAS',
            'CALENTANDO_MAQUINA',
            'EN_ESPERA_MATERIALES',
            'EN_PRODUCCION',
            'PAUSADA',
            'REVISION_CALIDAD',
            'PREPARAR_EMPAQUE',
            'ENROLLANDO',
            'A_LONGITUD',
            'CORTAR_FLEJAR',
            'EMPACANDO',
            'RUTA_TORNO',
            'RUTA_TORNO_EXTERNO',
            'RUTA_CORTE',
            'CALIDAD_FINAL',
            'ENTREGA_ALMACEN',
            'TERMINADA',
            'CANCELADA',
            'RECHAZADA_POR_CALIDAD'
        ) NOT NULL DEFAULT 'PREPARANDO_MAQUINAS'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_ordenes')) {
            return;
        }

        DB::table('tbl_ordenes')
            ->where('estatus', 'RECHAZADA_POR_CALIDAD')
            ->update(['estatus' => 'CANCELADA']);

        DB::statement("ALTER TABLE tbl_ordenes MODIFY COLUMN estatus ENUM(
            'PREPARANDO_MAQUINAS',
            'CALENTANDO_MAQUINA',
            'EN_ESPERA_MATERIALES',
            'EN_PRODUCCION',
            'PAUSADA',
            'REVISION_CALIDAD',
            'PREPARAR_EMPAQUE',
            'ENROLLANDO',
            'A_LONGITUD',
            'CORTAR_FLEJAR',
            'EMPACANDO',
            'RUTA_TORNO',
            'RUTA_TORNO_EXTERNO',
            'RUTA_CORTE',
            'CALIDAD_FINAL',
            'ENTREGA_ALMACEN',
            'TERMINADA',
            'CANCELADA'
        ) NOT NULL DEFAULT 'PREPARANDO_MAQUINAS'");
    }
};
