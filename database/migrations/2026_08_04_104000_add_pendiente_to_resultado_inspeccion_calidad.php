<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inspeccion_de_calidad_por_ordenes_de_trabajo')) {
            return;
        }

        // El flujo de calidad usa PENDIENTE mientras se ajustan parámetros / co-extrusora / tatuadora.
        DB::statement("ALTER TABLE inspeccion_de_calidad_por_ordenes_de_trabajo
            MODIFY COLUMN resultado ENUM('ACEPTADO', 'RECHAZADO', 'PENDIENTE') NOT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('inspeccion_de_calidad_por_ordenes_de_trabajo')) {
            return;
        }

        DB::table('inspeccion_de_calidad_por_ordenes_de_trabajo')
            ->where('resultado', 'PENDIENTE')
            ->update(['resultado' => 'RECHAZADO']);

        DB::statement("ALTER TABLE inspeccion_de_calidad_por_ordenes_de_trabajo
            MODIFY COLUMN resultado ENUM('ACEPTADO', 'RECHAZADO') NOT NULL");
    }
};
