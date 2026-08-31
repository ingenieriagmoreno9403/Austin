<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_registro_produccion')) {
            return;
        }

        Schema::table('tbl_registro_produccion', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_registro_produccion', 'piezas_buenas')) {
                $table->unsignedInteger('piezas_buenas')->nullable()->after('metros');
            }
            if (!Schema::hasColumn('tbl_registro_produccion', 'piezas_malas')) {
                $table->unsignedInteger('piezas_malas')->nullable()->after('piezas_buenas');
            }
            if (!Schema::hasColumn('tbl_registro_produccion', 'costo_unit_pieza')) {
                $table->decimal('costo_unit_pieza', 14, 4)->nullable()->after('costo_unit_m');
            }
            if (!Schema::hasColumn('tbl_registro_produccion', 'prod_hora_piezas')) {
                $table->decimal('prod_hora_piezas', 12, 3)->nullable()->after('prod_hora_kg');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_registro_produccion')) {
            return;
        }

        Schema::table('tbl_registro_produccion', function (Blueprint $table) {
            foreach (['piezas_buenas', 'piezas_malas', 'costo_unit_pieza', 'prod_hora_piezas'] as $col) {
                if (Schema::hasColumn('tbl_registro_produccion', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
