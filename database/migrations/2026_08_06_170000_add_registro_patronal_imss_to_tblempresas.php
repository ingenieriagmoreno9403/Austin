<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tblempresas', 'registro_patronal_imss')) {
            Schema::table('tblempresas', function (Blueprint $table) {
                $table->string('registro_patronal_imss', 15)->nullable()->after('rfc');
            });
        } else {
            // Por si el campo ya existía con tipo incorrecto (INT) y se pierden ceros.
            DB::statement('ALTER TABLE tblempresas MODIFY registro_patronal_imss VARCHAR(15) NULL DEFAULT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tblempresas', 'registro_patronal_imss')) {
            Schema::table('tblempresas', function (Blueprint $table) {
                $table->dropColumn('registro_patronal_imss');
            });
        }
    }
};
