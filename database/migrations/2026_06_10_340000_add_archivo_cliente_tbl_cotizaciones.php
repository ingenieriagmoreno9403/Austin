<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_cotizaciones')) {
            return;
        }

        Schema::table('tbl_cotizaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_cotizaciones', 'archivo_cliente_ruta')) {
                $table->string('archivo_cliente_ruta', 500)->nullable()->after('observaciones');
            }
            if (!Schema::hasColumn('tbl_cotizaciones', 'archivo_cliente_nombre')) {
                $table->string('archivo_cliente_nombre', 255)->nullable()->after('archivo_cliente_ruta');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_cotizaciones')) {
            return;
        }

        Schema::table('tbl_cotizaciones', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_cotizaciones', 'archivo_cliente_nombre')) {
                $table->dropColumn('archivo_cliente_nombre');
            }
            if (Schema::hasColumn('tbl_cotizaciones', 'archivo_cliente_ruta')) {
                $table->dropColumn('archivo_cliente_ruta');
            }
        });
    }
};
