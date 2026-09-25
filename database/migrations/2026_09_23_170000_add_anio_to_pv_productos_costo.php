<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Precios del maestro por año de proyección (anio_presupuesto: 2027, 2028…).
 * Clave: anio + empresa + card_code + producto_codigo + mes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $anioDefault = 2027;
        if (Schema::hasTable('tbl_pv_ciclos')) {
            $fromCiclo = (int) (DB::table('tbl_pv_ciclos')->max('anio_presupuesto') ?: 0);
            if ($fromCiclo >= 2000 && $fromCiclo <= 2100) {
                $anioDefault = $fromCiclo;
            }
        }

        if (Schema::hasTable('tbl_pv_productos_costo')
            && ! Schema::hasColumn('tbl_pv_productos_costo', 'anio')) {
            Schema::table('tbl_pv_productos_costo', function (Blueprint $table) use ($anioDefault) {
                $table->unsignedSmallInteger('anio')->default($anioDefault)->after('empresa');
            });
            DB::table('tbl_pv_productos_costo')
                ->where(function ($q) {
                    $q->whereNull('anio')->orWhere('anio', 0);
                })
                ->update(['anio' => $anioDefault]);

            try {
                Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                    $table->dropUnique('pv_prod_costo_unica_mes');
                });
            } catch (\Throwable $e) {
            }

            try {
                Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                    $table->unique(
                        ['anio', 'empresa', 'card_code', 'producto_codigo', 'mes'],
                        'pv_prod_costo_unica_anio'
                    );
                });
            } catch (\Throwable $e) {
            }

            try {
                Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                    $table->index('anio', 'pv_prod_costo_anio');
                });
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasTable('tbl_pv_productos_costo_historial')
            && ! Schema::hasColumn('tbl_pv_productos_costo_historial', 'anio')) {
            Schema::table('tbl_pv_productos_costo_historial', function (Blueprint $table) use ($anioDefault) {
                $table->unsignedSmallInteger('anio')->default($anioDefault)->after('empresa');
            });
            DB::table('tbl_pv_productos_costo_historial')
                ->where(function ($q) {
                    $q->whereNull('anio')->orWhere('anio', 0);
                })
                ->update(['anio' => $anioDefault]);
            try {
                Schema::table('tbl_pv_productos_costo_historial', function (Blueprint $table) {
                    $table->index('anio', 'pv_prod_costo_hist_anio');
                });
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_pv_productos_costo') && Schema::hasColumn('tbl_pv_productos_costo', 'anio')) {
            try {
                Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                    $table->dropUnique('pv_prod_costo_unica_anio');
                });
            } catch (\Throwable $e) {
            }
            try {
                Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                    $table->dropIndex('pv_prod_costo_anio');
                });
            } catch (\Throwable $e) {
            }
            Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                $table->dropColumn('anio');
            });
            try {
                Schema::table('tbl_pv_productos_costo', function (Blueprint $table) {
                    $table->unique(
                        ['empresa', 'card_code', 'producto_codigo', 'mes'],
                        'pv_prod_costo_unica_mes'
                    );
                });
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasTable('tbl_pv_productos_costo_historial')
            && Schema::hasColumn('tbl_pv_productos_costo_historial', 'anio')) {
            try {
                Schema::table('tbl_pv_productos_costo_historial', function (Blueprint $table) {
                    $table->dropIndex('pv_prod_costo_hist_anio');
                });
            } catch (\Throwable $e) {
            }
            Schema::table('tbl_pv_productos_costo_historial', function (Blueprint $table) {
                $table->dropColumn('anio');
            });
        }
    }
};
