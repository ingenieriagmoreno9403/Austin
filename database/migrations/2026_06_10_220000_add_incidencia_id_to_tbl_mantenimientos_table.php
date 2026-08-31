<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_mantenimientos')) {
            return;
        }

        Schema::table('tbl_mantenimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_mantenimientos', 'incidencia_id')) {
                $table->unsignedInteger('incidencia_id')->nullable()->after('responsable_id');
                $table->foreign('incidencia_id')
                    ->references('id')
                    ->on('tbl_incidencias_maquina')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_mantenimientos')) {
            return;
        }

        Schema::table('tbl_mantenimientos', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_mantenimientos', 'incidencia_id')) {
                $table->dropForeign(['incidencia_id']);
                $table->dropColumn('incidencia_id');
            }
        });
    }
};
