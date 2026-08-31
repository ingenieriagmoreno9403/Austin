<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblnomina_asistencias')) {
            return;
        }

        Schema::table('tblnomina_asistencias', function (Blueprint $table) {
            if (!Schema::hasColumn('tblnomina_asistencias', 'id_horario')) {
                $table->unsignedBigInteger('id_horario')->nullable()->after('salida');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tblnomina_asistencias')) {
            return;
        }

        Schema::table('tblnomina_asistencias', function (Blueprint $table) {
            if (Schema::hasColumn('tblnomina_asistencias', 'id_horario')) {
                $table->dropColumn('id_horario');
            }
        });
    }
};
