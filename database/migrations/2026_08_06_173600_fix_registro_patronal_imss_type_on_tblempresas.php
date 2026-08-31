<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tblempresas', 'registro_patronal_imss')) {
            Schema::table('tblempresas', function ($table) {
                $table->string('registro_patronal_imss', 11)->nullable()->after('rfc');
            });
            return;
        }

        // El campo llegó a crearse como INT; SUA requiere alfanumérico de 11 (ej. B2827500102).
        DB::statement('ALTER TABLE tblempresas MODIFY registro_patronal_imss VARCHAR(11) NULL');

        // Limpia valores numéricos residuales inválidos (0, 11, etc.).
        DB::table('tblempresas')
            ->whereRaw("registro_patronal_imss REGEXP '^[0-9]+$'")
            ->whereRaw('CHAR_LENGTH(registro_patronal_imss) <> 11')
            ->update(['registro_patronal_imss' => null]);
    }

    public function down(): void
    {
        // No se revierte a INT para no perder datos alfanuméricos.
    }
};
