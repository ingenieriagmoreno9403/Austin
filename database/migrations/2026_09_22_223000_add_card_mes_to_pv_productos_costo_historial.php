<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_pv_productos_costo_historial')) {
            return;
        }

        Schema::table('tbl_pv_productos_costo_historial', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_pv_productos_costo_historial', 'card_code')) {
                $table->string('card_code', 40)->default('')->after('empresa');
            }
            if (! Schema::hasColumn('tbl_pv_productos_costo_historial', 'card_name')) {
                $table->string('card_name', 180)->nullable()->after('card_code');
            }
            if (! Schema::hasColumn('tbl_pv_productos_costo_historial', 'mes')) {
                $table->unsignedTinyInteger('mes')->default(0)->after('producto_nombre');
            }
        });

        try {
            Schema::table('tbl_pv_productos_costo_historial', function (Blueprint $table) {
                $table->index(['empresa', 'card_code', 'producto_codigo', 'mes'], 'pv_prod_costo_hist_card_mes');
            });
        } catch (\Throwable $e) {
            // ok
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_pv_productos_costo_historial')) {
            return;
        }

        try {
            Schema::table('tbl_pv_productos_costo_historial', function (Blueprint $table) {
                $table->dropIndex('pv_prod_costo_hist_card_mes');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('tbl_pv_productos_costo_historial', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_pv_productos_costo_historial', 'mes')) {
                $table->dropColumn('mes');
            }
            if (Schema::hasColumn('tbl_pv_productos_costo_historial', 'card_name')) {
                $table->dropColumn('card_name');
            }
            if (Schema::hasColumn('tbl_pv_productos_costo_historial', 'card_code')) {
                $table->dropColumn('card_code');
            }
        });
    }
};
