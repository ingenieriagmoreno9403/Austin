<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
                    'TERMINADA',
                    'CANCELADA'
                ) NOT NULL DEFAULT 'PREPARANDO_MAQUINAS'
            ");

            Schema::table('tbl_ordenes', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_ordenes', 'tipo_empaque')) {
                    $table->string('tipo_empaque', 20)->nullable()->after('observaciones');
                }
                if (!Schema::hasColumn('tbl_ordenes', 'longitud_objetivo_m')) {
                    $table->decimal('longitud_objetivo_m', 12, 3)->nullable()->after('tipo_empaque');
                }
                if (!Schema::hasColumn('tbl_ordenes', 'nave_destino')) {
                    $table->string('nave_destino', 20)->nullable()->after('longitud_objetivo_m');
                }
            });

            // Órdenes que quedaron en EMPACANDO genérico pasan al primer paso del flujo D.
            DB::table('tbl_ordenes')
                ->where('estatus', 'EMPACANDO')
                ->update(['estatus' => 'PREPARAR_EMPAQUE']);
        }

        if (Schema::hasTable('tbl_ordenes_maquinas')) {
            DB::statement("
                ALTER TABLE tbl_ordenes_maquinas
                MODIFY COLUMN estatus ENUM(
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
                    'TERMINADA',
                    'CANCELADA'
                ) NOT NULL DEFAULT 'CALENTANDO_MAQUINA'
            ");
        }
    }

    public function down(): void
    {
        //
    }
};
