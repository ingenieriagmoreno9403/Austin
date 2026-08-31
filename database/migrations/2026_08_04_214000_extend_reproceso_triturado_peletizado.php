<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Ubicaciones del tramo triturado → peletizado
        $almacen = DB::table('tblalmacenes')
            ->where('estado', 'A')
            ->whereRaw('UPPER(folio_interno) LIKE ?', ['%ALMACEN GENERAL%'])
            ->first();
        if (!$almacen) {
            $almacen = DB::table('tblalmacenes')->where('estado', 'A')->orderBy('id')->first();
        }

        if ($almacen) {
            foreach ([
                'MATERIAL TRITURADO' => 'Sacas de material triturado listas / cerca del área de peletizado',
                'PELETIZADO' => 'Área de peletizado (material en proceso o peletizado)',
            ] as $folio => $desc) {
                $existe = DB::table('tblubicaciones')
                    ->where('id_almacen', $almacen->id)
                    ->whereRaw('UPPER(folio_interno) = ?', [$folio])
                    ->exists();
                if (!$existe) {
                    DB::table('tblubicaciones')->insert([
                        'id_almacen' => $almacen->id,
                        'folio_interno' => $folio,
                        'descripcion' => $desc,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if (!Schema::hasTable('tbl_reprocesos')) {
            return;
        }

        // Ampliar ENUM de estatus (MySQL).
        DB::statement("ALTER TABLE tbl_reprocesos MODIFY COLUMN estatus ENUM(
            'DISPONIBLE',
            'CORTADA',
            'PESADA',
            'REPORTADA',
            'TRITURADA',
            'SACA_PESADA',
            'IDENTIFICADA',
            'LISTO_PELETIZADO',
            'EN_PELETIZADO',
            'PELETIZADO',
            'CANCELADA'
        ) NOT NULL DEFAULT 'DISPONIBLE'");

        Schema::table('tbl_reprocesos', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_reprocesos', 'kg_saca_triturada')) {
                $table->decimal('kg_saca_triturada', 12, 3)->nullable()->after('kg_reportado');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'etiqueta_triturado')) {
                $table->string('etiqueta_triturado', 80)->nullable()->after('kg_saca_triturada');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'reporte_triturado')) {
                $table->string('reporte_triturado', 80)->nullable()->after('etiqueta_triturado');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'identificacion_material')) {
                $table->string('identificacion_material', 255)->nullable()->after('reporte_triturado');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'reporte_peletizado')) {
                $table->string('reporte_peletizado', 80)->nullable()->after('identificacion_material');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'peletizado_inicio_at')) {
                $table->timestamp('peletizado_inicio_at')->nullable()->after('triturado_at');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'peletizado_fin_at')) {
                $table->timestamp('peletizado_fin_at')->nullable()->after('peletizado_inicio_at');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'peletizado_por')) {
                $table->unsignedInteger('peletizado_por')->nullable()->after('triturado_por');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_reprocesos')) {
            return;
        }

        // Regresar lotes avanzados a TRITURADA antes de achicar el ENUM.
        DB::table('tbl_reprocesos')
            ->whereIn('estatus', ['SACA_PESADA', 'IDENTIFICADA', 'LISTO_PELETIZADO', 'EN_PELETIZADO', 'PELETIZADO'])
            ->update(['estatus' => 'TRITURADA']);

        Schema::table('tbl_reprocesos', function (Blueprint $table) {
            foreach ([
                'kg_saca_triturada',
                'etiqueta_triturado',
                'reporte_triturado',
                'identificacion_material',
                'reporte_peletizado',
                'peletizado_inicio_at',
                'peletizado_fin_at',
                'peletizado_por',
            ] as $col) {
                if (Schema::hasColumn('tbl_reprocesos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        DB::statement("ALTER TABLE tbl_reprocesos MODIFY COLUMN estatus ENUM(
            'DISPONIBLE',
            'CORTADA',
            'PESADA',
            'REPORTADA',
            'TRITURADA',
            'CANCELADA'
        ) NOT NULL DEFAULT 'DISPONIBLE'");
    }
};
