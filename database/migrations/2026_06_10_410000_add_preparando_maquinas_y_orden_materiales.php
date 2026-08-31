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
                    'EMPACANDO',
                    'TERMINADA',
                    'CANCELADA'
                ) NOT NULL DEFAULT 'PREPARANDO_MAQUINAS'
            ");

            Schema::table('tbl_ordenes', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_ordenes', 'orden_materiales_ruta')) {
                    $table->string('orden_materiales_ruta', 255)->nullable()->after('observaciones');
                }
                if (!Schema::hasColumn('tbl_ordenes', 'orden_materiales_nombre')) {
                    $table->string('orden_materiales_nombre', 255)->nullable()->after('orden_materiales_ruta');
                }
                if (!Schema::hasColumn('tbl_ordenes', 'materiales_tomados_at')) {
                    $table->timestamp('materiales_tomados_at')->nullable()->after('orden_materiales_nombre');
                }
                if (!Schema::hasColumn('tbl_ordenes', 'materiales_tomados_by')) {
                    $table->unsignedBigInteger('materiales_tomados_by')->nullable()->after('materiales_tomados_at');
                }
            });
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
                    'EMPACANDO',
                    'TERMINADA',
                    'CANCELADA'
                ) NOT NULL DEFAULT 'CALENTANDO_MAQUINA'
            ");
        }

        if (Schema::hasTable('tbltipos_movimientos_inventario')) {
            $existe = DB::table('tbltipos_movimientos_inventario')
                ->where('nombre_movimiento', 'Salida a producción')
                ->exists();

            if (!$existe) {
                DB::table('tbltipos_movimientos_inventario')->insert([
                    'nombre_movimiento' => 'Salida a producción',
                    'descripcion_movimiento' => 'Consumo de materia prima para orden de producción',
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_ordenes')) {
            DB::table('tbl_ordenes')
                ->where('estatus', 'PREPARANDO_MAQUINAS')
                ->update(['estatus' => 'CALENTANDO_MAQUINA']);

            Schema::table('tbl_ordenes', function (Blueprint $table) {
                foreach (['materiales_tomados_by', 'materiales_tomados_at', 'orden_materiales_nombre', 'orden_materiales_ruta'] as $col) {
                    if (Schema::hasColumn('tbl_ordenes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });

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
            DB::table('tbl_ordenes_maquinas')
                ->where('estatus', 'PREPARANDO_MAQUINAS')
                ->update(['estatus' => 'CALENTANDO_MAQUINA']);

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
};
