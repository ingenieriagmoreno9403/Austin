<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_cotizaciones') && !Schema::hasColumn('tbl_cotizaciones', 'flete_en_precios')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->boolean('flete_en_precios')->default(false)->after('importe_flete');
            });
        }

        if (Schema::hasTable('tbl_pedidos') && !Schema::hasColumn('tbl_pedidos', 'flete_en_precios')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->boolean('flete_en_precios')->default(false)->after('importe_flete');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_cotizaciones') && Schema::hasColumn('tbl_cotizaciones', 'flete_en_precios')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->dropColumn('flete_en_precios');
            });
        }

        if (Schema::hasTable('tbl_pedidos') && Schema::hasColumn('tbl_pedidos', 'flete_en_precios')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->dropColumn('flete_en_precios');
            });
        }
    }
};
