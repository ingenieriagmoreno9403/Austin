<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_ordenes')) {
            return;
        }

        // 1) Ampliar el enum incluyendo los valores nuevos y los legados temporalmente.
        DB::statement("ALTER TABLE tbl_ordenes MODIFY estatus ENUM(
            'CALENTANDO_MAQUINA','EN_PRODUCCION','PAUSADA','REVISION_CALIDAD','EMPACANDO','TERMINADA','CANCELADA',
            'ABIERTA','EN_PROCESO','CERRADA'
        ) NOT NULL DEFAULT 'CALENTANDO_MAQUINA'");

        // 2) Migrar los datos existentes al nuevo vocabulario.
        DB::table('tbl_ordenes')->where('estatus', 'ABIERTA')->update(['estatus' => 'CALENTANDO_MAQUINA']);
        DB::table('tbl_ordenes')->where('estatus', 'EN_PROCESO')->update(['estatus' => 'EN_PRODUCCION']);
        DB::table('tbl_ordenes')->where('estatus', 'CERRADA')->update(['estatus' => 'TERMINADA']);

        // 3) Dejar el enum solo con los valores finales.
        DB::statement("ALTER TABLE tbl_ordenes MODIFY estatus ENUM(
            'CALENTANDO_MAQUINA','EN_PRODUCCION','PAUSADA','REVISION_CALIDAD','EMPACANDO','TERMINADA','CANCELADA'
        ) NOT NULL DEFAULT 'CALENTANDO_MAQUINA'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_ordenes')) {
            return;
        }

        DB::statement("ALTER TABLE tbl_ordenes MODIFY estatus ENUM(
            'CALENTANDO_MAQUINA','EN_PRODUCCION','PAUSADA','REVISION_CALIDAD','EMPACANDO','TERMINADA','CANCELADA',
            'ABIERTA','EN_PROCESO','CERRADA'
        ) NOT NULL DEFAULT 'ABIERTA'");

        DB::table('tbl_ordenes')->where('estatus', 'CALENTANDO_MAQUINA')->update(['estatus' => 'ABIERTA']);
        DB::table('tbl_ordenes')->whereIn('estatus', ['EN_PRODUCCION', 'PAUSADA', 'REVISION_CALIDAD', 'EMPACANDO'])->update(['estatus' => 'EN_PROCESO']);
        DB::table('tbl_ordenes')->where('estatus', 'TERMINADA')->update(['estatus' => 'CERRADA']);

        DB::statement("ALTER TABLE tbl_ordenes MODIFY estatus ENUM(
            'ABIERTA','EN_PROCESO','CERRADA','CANCELADA'
        ) NOT NULL DEFAULT 'ABIERTA'");
    }
};
