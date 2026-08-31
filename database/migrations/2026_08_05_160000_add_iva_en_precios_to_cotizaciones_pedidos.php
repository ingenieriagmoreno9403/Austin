<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_cotizaciones') && !Schema::hasColumn('tbl_cotizaciones', 'iva_en_precios')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->boolean('iva_en_precios')->default(false)->after('flete_en_precios');
            });
        }

        if (Schema::hasTable('tbl_pedidos') && !Schema::hasColumn('tbl_pedidos', 'iva_en_precios')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->boolean('iva_en_precios')->default(false)->after('flete_en_precios');
            });
        }

        if (Schema::hasTable('tbl_cotizaciones_detalle')) {
            Schema::table('tbl_cotizaciones_detalle', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_cotizaciones_detalle', 'precio_unitario_con_iva')) {
                    $table->decimal('precio_unitario_con_iva', 12, 2)->nullable()->after('precio_unitario');
                }
                if (!Schema::hasColumn('tbl_cotizaciones_detalle', 'importe_con_iva')) {
                    $table->decimal('importe_con_iva', 12, 2)->nullable()->after('importe');
                }
            });
        }

        if (Schema::hasTable('tbl_pedidos_detalle')) {
            Schema::table('tbl_pedidos_detalle', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_pedidos_detalle', 'precio_unitario_con_iva')) {
                    $table->decimal('precio_unitario_con_iva', 12, 2)->nullable()->after('precio_unitario');
                }
                if (!Schema::hasColumn('tbl_pedidos_detalle', 'importe_con_iva')) {
                    $table->decimal('importe_con_iva', 12, 2)->nullable()->after('importe');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_cotizaciones') && Schema::hasColumn('tbl_cotizaciones', 'iva_en_precios')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->dropColumn('iva_en_precios');
            });
        }

        if (Schema::hasTable('tbl_pedidos') && Schema::hasColumn('tbl_pedidos', 'iva_en_precios')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->dropColumn('iva_en_precios');
            });
        }

        if (Schema::hasTable('tbl_cotizaciones_detalle')) {
            Schema::table('tbl_cotizaciones_detalle', function (Blueprint $table) {
                foreach (['precio_unitario_con_iva', 'importe_con_iva'] as $col) {
                    if (Schema::hasColumn('tbl_cotizaciones_detalle', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('tbl_pedidos_detalle')) {
            Schema::table('tbl_pedidos_detalle', function (Blueprint $table) {
                foreach (['precio_unitario_con_iva', 'importe_con_iva'] as $col) {
                    if (Schema::hasColumn('tbl_pedidos_detalle', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
