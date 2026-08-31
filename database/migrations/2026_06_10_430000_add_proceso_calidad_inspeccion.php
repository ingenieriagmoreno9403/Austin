<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inspeccion_de_calidad_por_ordenes_de_trabajo')) {
            return;
        }

        Schema::table('inspeccion_de_calidad_por_ordenes_de_trabajo', function (Blueprint $table) {
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'estatus_calidad')) {
                $table->string('estatus_calidad', 40)->default('MEDICION_ESPESORES')->after('resultado');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'espesor_nominal')) {
                $table->decimal('espesor_nominal', 10, 4)->nullable()->after('estatus_calidad');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'espesor_min')) {
                $table->decimal('espesor_min', 10, 4)->nullable()->after('espesor_nominal');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'espesor_max')) {
                $table->decimal('espesor_max', 10, 4)->nullable()->after('espesor_min');
            }
            for ($i = 1; $i <= 8; $i++) {
                $col = 'espesor_' . $i;
                if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', $col)) {
                    $table->decimal($col, 10, 4)->nullable()->after($i === 1 ? 'espesor_max' : 'espesor_' . ($i - 1));
                }
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'espesores_en_rango')) {
                $table->boolean('espesores_en_rango')->nullable()->after('espesor_8');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'color_linea')) {
                $table->string('color_linea', 20)->nullable()->after('espesores_en_rango');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'co_extrusora_ok')) {
                $table->boolean('co_extrusora_ok')->default(false)->after('color_linea');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'tatuadora_ok')) {
                $table->boolean('tatuadora_ok')->default(false)->after('co_extrusora_ok');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'tatuaje_diametro')) {
                $table->string('tatuaje_diametro', 50)->nullable()->after('tatuadora_ok');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'tatuaje_rd')) {
                $table->string('tatuaje_rd', 50)->nullable()->after('tatuaje_diametro');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'tatuaje_lote')) {
                $table->string('tatuaje_lote', 100)->nullable()->after('tatuaje_rd');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'tatuaje_fecha')) {
                $table->date('tatuaje_fecha')->nullable()->after('tatuaje_lote');
            }
            if (!Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', 'tatuaje_hora')) {
                $table->string('tatuaje_hora', 10)->nullable()->after('tatuaje_fecha');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('inspeccion_de_calidad_por_ordenes_de_trabajo')) {
            return;
        }

        Schema::table('inspeccion_de_calidad_por_ordenes_de_trabajo', function (Blueprint $table) {
            $cols = [
                'estatus_calidad', 'espesor_nominal', 'espesor_min', 'espesor_max',
                'espesor_1', 'espesor_2', 'espesor_3', 'espesor_4',
                'espesor_5', 'espesor_6', 'espesor_7', 'espesor_8',
                'espesores_en_rango', 'color_linea', 'co_extrusora_ok', 'tatuadora_ok',
                'tatuaje_diametro', 'tatuaje_rd', 'tatuaje_lote', 'tatuaje_fecha', 'tatuaje_hora',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('inspeccion_de_calidad_por_ordenes_de_trabajo', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
