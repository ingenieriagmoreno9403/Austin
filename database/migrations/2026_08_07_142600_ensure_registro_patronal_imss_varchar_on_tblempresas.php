<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblempresas')) {
            return;
        }

        // En el server a veces no existe la columna, o se creó como numérica y se pierden los ceros.
        if (!Schema::hasColumn('tblempresas', 'registro_patronal_imss')) {
            Schema::table('tblempresas', function (Blueprint $table) {
                if (Schema::hasColumn('tblempresas', 'rfc')) {
                    $table->string('registro_patronal_imss', 15)->nullable()->after('rfc');
                } else {
                    $table->string('registro_patronal_imss', 15)->nullable();
                }
            });
            return;
        }

        DB::statement('ALTER TABLE tblempresas MODIFY registro_patronal_imss VARCHAR(15) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        // No eliminar: puede haber datos ya capturados en producción.
    }
};
