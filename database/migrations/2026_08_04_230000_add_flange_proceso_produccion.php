<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tipo de proceso en producto.
        if (Schema::hasTable('tblproductos') && !Schema::hasColumn('tblproductos', 'tipo_proceso')) {
            Schema::table('tblproductos', function (Blueprint $table) {
                $table->string('tipo_proceso', 20)->default('TUBO')->after('id_categoria');
            });

            // Categorías de bridas / flange → FLANGE
            DB::table('tblproductos')
                ->whereIn('id_categoria', [16, 17, 25])
                ->update(['tipo_proceso' => 'FLANGE']);

            // Con especificación de tubo activa → TUBO
            $idsTubo = DB::table('tbl_producto_tubo_especificaciones')
                ->where('estatus', 'ACTIVO')
                ->distinct()
                ->pluck('producto_id');
            if ($idsTubo->isNotEmpty()) {
                DB::table('tblproductos')
                    ->whereIn('id', $idsTubo)
                    ->update(['tipo_proceso' => 'TUBO']);
            }
        }

        // 2) Specs flange.
        if (!Schema::hasTable('tbl_producto_flange_especificaciones')) {
            Schema::create('tbl_producto_flange_especificaciones', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('producto_id');
                $table->string('diametro_nominal', 20); // 4", 6"
                $table->decimal('rd', 8, 3)->nullable();
                $table->decimal('peso_kg_pieza', 12, 3)->nullable();
                $table->string('descripcion', 255)->nullable();
                $table->enum('estatus', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
                $table->timestamps();

                $table->index(['producto_id', 'estatus'], 'idx_flange_prod_estatus');
            });
        }

        // 3) OP: tipo_proceso + ruta_flange + nuevos estatus.
        if (Schema::hasTable('tbl_ordenes')) {
            if (!Schema::hasColumn('tbl_ordenes', 'tipo_proceso')) {
                Schema::table('tbl_ordenes', function (Blueprint $table) {
                    $table->string('tipo_proceso', 20)->default('TUBO')->after('estatus');
                });
            }
            if (!Schema::hasColumn('tbl_ordenes', 'ruta_flange')) {
                Schema::table('tbl_ordenes', function (Blueprint $table) {
                    $table->string('ruta_flange', 30)->nullable()->after('tipo_proceso');
                });
            }

            DB::statement("ALTER TABLE tbl_ordenes MODIFY COLUMN estatus ENUM(
                'PREPARANDO_MAQUINAS',
                'CALENTANDO_MAQUINA',
                'EN_ESPERA_MATERIALES',
                'EN_PRODUCCION',
                'PAUSADA',
                'REVISION_CALIDAD',
                'PREPARAR_EMPAQUE',
                'ENROLLANDO',
                'A_LONGITUD',
                'CORTAR_FLEJAR',
                'EMPACANDO',
                'RUTA_TORNO',
                'RUTA_TORNO_EXTERNO',
                'RUTA_CORTE',
                'CALIDAD_FINAL',
                'ENTREGA_ALMACEN',
                'TERMINADA',
                'CANCELADA'
            ) NOT NULL DEFAULT 'PREPARANDO_MAQUINAS'");
        }

        // 4) Avance flange en salidas (piezas).
        if (Schema::hasTable('tbl_ordenes_detalle_salidas')) {
            Schema::table('tbl_ordenes_detalle_salidas', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_ordenes_detalle_salidas', 'piezas_buenas')) {
                    $table->unsignedInteger('piezas_buenas')->nullable()->after('piezas');
                }
                if (!Schema::hasColumn('tbl_ordenes_detalle_salidas', 'piezas_malas')) {
                    $table->unsignedInteger('piezas_malas')->nullable()->after('piezas_buenas');
                }
                if (!Schema::hasColumn('tbl_ordenes_detalle_salidas', 'kg_retrabajo')) {
                    $table->decimal('kg_retrabajo', 12, 3)->nullable()->after('kg_merma');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_ordenes')) {
            DB::table('tbl_ordenes')
                ->whereIn('estatus', ['RUTA_TORNO', 'RUTA_TORNO_EXTERNO', 'RUTA_CORTE', 'CALIDAD_FINAL', 'ENTREGA_ALMACEN'])
                ->update(['estatus' => 'EN_PRODUCCION']);

            DB::statement("ALTER TABLE tbl_ordenes MODIFY COLUMN estatus ENUM(
                'PREPARANDO_MAQUINAS',
                'CALENTANDO_MAQUINA',
                'EN_ESPERA_MATERIALES',
                'EN_PRODUCCION',
                'PAUSADA',
                'REVISION_CALIDAD',
                'PREPARAR_EMPAQUE',
                'ENROLLANDO',
                'A_LONGITUD',
                'CORTAR_FLEJAR',
                'EMPACANDO',
                'TERMINADA',
                'CANCELADA'
            ) NOT NULL DEFAULT 'PREPARANDO_MAQUINAS'");

            Schema::table('tbl_ordenes', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_ordenes', 'ruta_flange')) {
                    $table->dropColumn('ruta_flange');
                }
                if (Schema::hasColumn('tbl_ordenes', 'tipo_proceso')) {
                    $table->dropColumn('tipo_proceso');
                }
            });
        }

        if (Schema::hasTable('tbl_ordenes_detalle_salidas')) {
            Schema::table('tbl_ordenes_detalle_salidas', function (Blueprint $table) {
                foreach (['piezas_buenas', 'piezas_malas', 'kg_retrabajo'] as $col) {
                    if (Schema::hasColumn('tbl_ordenes_detalle_salidas', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('tbl_producto_flange_especificaciones');

        if (Schema::hasTable('tblproductos') && Schema::hasColumn('tblproductos', 'tipo_proceso')) {
            Schema::table('tblproductos', function (Blueprint $table) {
                $table->dropColumn('tipo_proceso');
            });
        }
    }
};
