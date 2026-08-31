<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_ordenes')) {
            DB::statement("
                ALTER TABLE tbl_ordenes
                MODIFY COLUMN estatus ENUM(
                    'CALENTANDO_MAQUINA',
                    'EN_ESPERA_MATERIALES',
                    'EN_PRODUCCION',
                    'PAUSADA',
                    'REVISION_CALIDAD',
                    'EMPACANDO',
                    'TERMINADA',
                    'CANCELADA'
                ) NOT NULL DEFAULT 'CALENTANDO_MAQUINA'
            ");
        }

        if (Schema::hasTable('tbl_ordenes_maquinas')) {
            DB::statement("
                ALTER TABLE tbl_ordenes_maquinas
                MODIFY COLUMN estatus ENUM(
                    'CALENTANDO_MAQUINA',
                    'EN_ESPERA_MATERIALES',
                    'EN_PRODUCCION',
                    'PAUSADA',
                    'REVISION_CALIDAD',
                    'EMPACANDO',
                    'TERMINADA',
                    'CANCELADA'
                ) NOT NULL DEFAULT 'CALENTANDO_MAQUINA'
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_ordenes')) {
            DB::statement("
                ALTER TABLE tbl_ordenes
                MODIFY COLUMN estatus ENUM(
                    'CALENTANDO_MAQUINA',
                    'EN_PRODUCCION',
                    'PAUSADA',
                    'REVISION_CALIDAD',
                    'EMPACANDO',
                    'TERMINADA',
                    'CANCELADA'
                ) NOT NULL DEFAULT 'CALENTANDO_MAQUINA'
            ");
        }

        if (Schema::hasTable('tbl_ordenes_maquinas')) {
            DB::statement("
                ALTER TABLE tbl_ordenes_maquinas
                MODIFY COLUMN estatus ENUM(
                    'CALENTANDO_MAQUINA',
                    'EN_PRODUCCION',
                    'PAUSADA',
                    'REVISION_CALIDAD',
                    'EMPACANDO',
                    'TERMINADA',
                    'CANCELADA'
                ) NOT NULL DEFAULT 'CALENTANDO_MAQUINA'
            ");
        }
    }
};

