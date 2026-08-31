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

        $almacen = DB::table('tblalmacenes')
            ->where('estado', 'A')
            ->whereRaw('UPPER(folio_interno) LIKE ?', ['%ALMACEN GENERAL%'])
            ->first();
        if (!$almacen) {
            $almacen = DB::table('tblalmacenes')->where('estado', 'A')->orderBy('id')->first();
        }

        if ($almacen) {
            $folio = 'NAVE 2 RESINA';
            $existe = DB::table('tblubicaciones')
                ->where('id_almacen', $almacen->id)
                ->whereRaw('UPPER(folio_interno) = ?', [$folio])
                ->exists();
            if (!$existe) {
                DB::table('tblubicaciones')->insert([
                    'id_almacen' => $almacen->id,
                    'folio_interno' => $folio,
                    'descripcion' => 'Nave dos · área de resina / material peletizado según tipo',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (!Schema::hasTable('tbl_reprocesos')) {
            return;
        }

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
            'SACA_RESINA_DESMONTADA',
            'RESINA_IDENTIFICADA',
            'SACA_RESINA_PESADA',
            'RESINA_ALMACENADA',
            'PRODUCCION_REPORTADA',
            'CANCELADA'
        ) NOT NULL DEFAULT 'DISPONIBLE'");

        Schema::table('tbl_reprocesos', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_reprocesos', 'etiqueta_resina')) {
                $table->string('etiqueta_resina', 80)->nullable()->after('reporte_peletizado');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'identificacion_resina')) {
                $table->string('identificacion_resina', 255)->nullable()->after('etiqueta_resina');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'kg_saca_resina')) {
                $table->decimal('kg_saca_resina', 12, 3)->nullable()->after('identificacion_resina');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'kg_produccion_reportado')) {
                $table->decimal('kg_produccion_reportado', 12, 3)->nullable()->after('kg_saca_resina');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'produccion_reportado_por')) {
                $table->unsignedInteger('produccion_reportado_por')->nullable()->after('peletizado_por');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'produccion_reportado_at')) {
                $table->timestamp('produccion_reportado_at')->nullable()->after('peletizado_fin_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_reprocesos')) {
            return;
        }

        DB::table('tbl_reprocesos')
            ->whereIn('estatus', [
                'SACA_RESINA_DESMONTADA',
                'RESINA_IDENTIFICADA',
                'SACA_RESINA_PESADA',
                'RESINA_ALMACENADA',
                'PRODUCCION_REPORTADA',
            ])
            ->update(['estatus' => 'PELETIZADO']);

        Schema::table('tbl_reprocesos', function (Blueprint $table) {
            foreach ([
                'etiqueta_resina',
                'identificacion_resina',
                'kg_saca_resina',
                'kg_produccion_reportado',
                'produccion_reportado_por',
                'produccion_reportado_at',
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
            'SACA_PESADA',
            'IDENTIFICADA',
            'LISTO_PELETIZADO',
            'EN_PELETIZADO',
            'PELETIZADO',
            'CANCELADA'
        ) NOT NULL DEFAULT 'DISPONIBLE'");
    }
};
