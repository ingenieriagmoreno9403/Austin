<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende maestro de precios: CardCode (cliente), Mes.
 * Clave: empresa + card_code + producto_codigo (ItemCode) + mes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return;
        }

        Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_pv_productos_costo', 'card_code')) {
                $table->string('card_code', 40)->default('')->after('empresa');
            }
            if (! Schema::hasColumn('tbl_pv_productos_costo', 'card_name')) {
                $table->string('card_name', 180)->nullable()->after('card_code');
            }
            if (! Schema::hasColumn('tbl_pv_productos_costo', 'mes')) {
                $table->unsignedTinyInteger('mes')->default(0)->after('producto_nombre');
            }
        });

        DB::table('tbl_pv_productos_costo')->whereNull('card_code')->update(['card_code' => '']);
        DB::statement('UPDATE tbl_pv_productos_costo SET mes = 0 WHERE mes IS NULL');

        try {
            Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                $table->dropUnique('pv_prod_costo_unica');
            });
        } catch (\Throwable $e) {
            // ok
        }

        try {
            Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                $table->unique(
                    ['empresa', 'card_code', 'producto_codigo', 'mes'],
                    'pv_prod_costo_unica_mes'
                );
            });
        } catch (\Throwable $e) {
            // ya existe
        }

        try {
            Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                $table->index('card_code', 'pv_prod_costo_card');
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                $table->index('mes', 'pv_prod_costo_mes');
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tbl_pv_productos_costo')) {
            return;
        }

        try {
            Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                $table->dropUnique('pv_prod_costo_unica_mes');
            });
        } catch (\Throwable $e) {
        }
        try {
            Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                $table->dropIndex('pv_prod_costo_card');
            });
        } catch (\Throwable $e) {
        }
        try {
            Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                $table->dropIndex('pv_prod_costo_mes');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_pv_productos_costo', 'mes')) {
                $table->dropColumn('mes');
            }
            if (Schema::hasColumn('tbl_pv_productos_costo', 'card_name')) {
                $table->dropColumn('card_name');
            }
            if (Schema::hasColumn('tbl_pv_productos_costo', 'card_code')) {
                $table->dropColumn('card_code');
            }
        });

        try {
            Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                $table->unique(['empresa', 'producto_codigo'], 'pv_prod_costo_unica');
            });
        } catch (\Throwable $e) {
        }
    }
};
