<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_ordenes_detalle')) {
            return;
        }

        Schema::table('tbl_ordenes_detalle', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_ordenes_detalle', 'tipo_linea')) {
                $table->enum('tipo_linea', ['FABRICAR', 'EXISTENCIA'])->default('EXISTENCIA')->after('producto_id');
            }
            if (!Schema::hasColumn('tbl_ordenes_detalle', 'ubicacion_destino_id')) {
                $table->integer('ubicacion_destino_id')->nullable()->after('largo_tramo');
            }
        });

        if (Schema::hasTable('tbl_producto_tubo_especificaciones')) {
            $tuboProductoIds = DB::table('tbl_producto_tubo_especificaciones')
                ->distinct()
                ->pluck('producto_id');

            if ($tuboProductoIds->isNotEmpty()) {
                DB::table('tbl_ordenes_detalle')
                    ->whereIn('producto_id', $tuboProductoIds)
                    ->update(['tipo_linea' => 'FABRICAR']);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_ordenes_detalle')) {
            return;
        }

        Schema::table('tbl_ordenes_detalle', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_ordenes_detalle', 'ubicacion_destino_id')) {
                $table->dropColumn('ubicacion_destino_id');
            }
            if (Schema::hasColumn('tbl_ordenes_detalle', 'tipo_linea')) {
                $table->dropColumn('tipo_linea');
            }
        });
    }
};
