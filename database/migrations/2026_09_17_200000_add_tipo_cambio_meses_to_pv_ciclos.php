<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_pv_ciclos')) {
            return;
        }
        Schema::table('tbl_pv_ciclos', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_pv_ciclos', 'tipo_cambio_meses')) {
                $table->json('tipo_cambio_meses')->nullable()->after('tipo_cambio');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_pv_ciclos')) {
            return;
        }
        Schema::table('tbl_pv_ciclos', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_pv_ciclos', 'tipo_cambio_meses')) {
                $table->dropColumn('tipo_cambio_meses');
            }
        });
    }
};
