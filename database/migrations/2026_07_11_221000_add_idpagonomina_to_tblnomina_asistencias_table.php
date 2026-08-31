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
            if (!Schema::hasColumn('tblnomina_asistencias', 'idpagonomina')) {
                $table->unsignedBigInteger('idpagonomina')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tblnomina_asistencias')) {
            return;
        }

        Schema::table('tblnomina_asistencias', function (Blueprint $table) {
            if (Schema::hasColumn('tblnomina_asistencias', 'idpagonomina')) {
                $table->dropColumn('idpagonomina');
            }
        });
    }
};
