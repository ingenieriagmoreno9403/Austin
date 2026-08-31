<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_reporte_no_existencias')) {
            return;
        }

        Schema::table('tbl_reporte_no_existencias', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_reporte_no_existencias', 'cotizacion_id')) {
                $table->unsignedInteger('cotizacion_id')->nullable()->after('pedido_detalle_id');
                $table->index('cotizacion_id', 'tbl_reporte_no_exist_cotizacion_idx');
            }
            if (!Schema::hasColumn('tbl_reporte_no_existencias', 'tipo')) {
                $table->enum('tipo', ['PRODUCTO', 'MATERIA_PRIMA'])->default('PRODUCTO')->after('producto_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_reporte_no_existencias')) {
            return;
        }

        Schema::table('tbl_reporte_no_existencias', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_reporte_no_existencias', 'cotizacion_id')) {
                $table->dropIndex('tbl_reporte_no_exist_cotizacion_idx');
                $table->dropColumn('cotizacion_id');
            }
            if (Schema::hasColumn('tbl_reporte_no_existencias', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });
    }
};
