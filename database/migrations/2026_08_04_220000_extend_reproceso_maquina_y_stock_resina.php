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

        if (!DB::table('tbltipos_movimientos_inventario')
            ->where('nombre_movimiento', 'Entrada por reproceso')
            ->exists()) {
            DB::table('tbltipos_movimientos_inventario')->insert([
                'nombre_movimiento' => 'Entrada por reproceso',
                'descripcion_movimiento' => 'Stock de resina generada por peletizado / reproceso de tubería',
                'created_at' => $now,
            ]);
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
            'KILOS_RESINA_SISTEMA',
            'STOCK_RESINA',
            'CANCELADA'
        ) NOT NULL DEFAULT 'DISPONIBLE'");

        Schema::table('tbl_reprocesos', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_reprocesos', 'maquina_id')) {
                $table->unsignedBigInteger('maquina_id')->nullable()->after('ubicacion_id');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'producto_resina_id')) {
                $table->unsignedInteger('producto_resina_id')->nullable()->after('producto_id');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'kg_resina_sistema')) {
                $table->decimal('kg_resina_sistema', 12, 3)->nullable()->after('kg_produccion_reportado');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'kilos_sistema_por')) {
                $table->unsignedInteger('kilos_sistema_por')->nullable()->after('produccion_reportado_por');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'kilos_sistema_at')) {
                $table->timestamp('kilos_sistema_at')->nullable()->after('produccion_reportado_at');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'stock_por')) {
                $table->unsignedInteger('stock_por')->nullable()->after('kilos_sistema_por');
            }
            if (!Schema::hasColumn('tbl_reprocesos', 'stock_at')) {
                $table->timestamp('stock_at')->nullable()->after('kilos_sistema_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_reprocesos')) {
            return;
        }

        DB::table('tbl_reprocesos')
            ->whereIn('estatus', ['KILOS_RESINA_SISTEMA', 'STOCK_RESINA'])
            ->update(['estatus' => 'PRODUCCION_REPORTADA']);

        Schema::table('tbl_reprocesos', function (Blueprint $table) {
            foreach ([
                'maquina_id',
                'producto_resina_id',
                'kg_resina_sistema',
                'kilos_sistema_por',
                'kilos_sistema_at',
                'stock_por',
                'stock_at',
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
            'SACA_RESINA_DESMONTADA',
            'RESINA_IDENTIFICADA',
            'SACA_RESINA_PESADA',
            'RESINA_ALMACENADA',
            'PRODUCCION_REPORTADA',
            'CANCELADA'
        ) NOT NULL DEFAULT 'DISPONIBLE'");
    }
};
