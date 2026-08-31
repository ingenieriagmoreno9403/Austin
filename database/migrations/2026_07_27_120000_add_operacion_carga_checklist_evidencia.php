<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pedido despachado al dar salida al chofer.
        DB::statement("ALTER TABLE tbl_pedidos MODIFY estatus ENUM(
            'CONFIRMADO',
            'EN_PRODUCCION',
            'PENDIENTE_OC',
            'LISTO_PARA_CARGA',
            'CARGA_AVISADA',
            'DESPACHADO'
        ) NOT NULL DEFAULT 'CONFIRMADO'");

        Schema::table('tbl_rutas_carga', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_rutas_carga', 'chofer_tipo')) {
                $table->string('chofer_tipo', 20)->nullable()->after('chofer_nombre'); // INTERNO|EXTERNO
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'chofer_llegada_at')) {
                $table->timestamp('chofer_llegada_at')->nullable()->after('chofer_tipo');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'check_seguro_vehiculo')) {
                $table->boolean('check_seguro_vehiculo')->default(false)->after('chofer_llegada_at');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'check_seguro_imss')) {
                $table->boolean('check_seguro_imss')->default(false)->after('check_seguro_vehiculo');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'check_botas')) {
                $table->boolean('check_botas')->default(false)->after('check_seguro_imss');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'check_casco')) {
                $table->boolean('check_casco')->default(false)->after('check_botas');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'check_chaleco')) {
                $table->boolean('check_chaleco')->default(false)->after('check_casco');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'check_epp')) {
                $table->boolean('check_epp')->default(false)->after('check_chaleco');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'checklist_ok_at')) {
                $table->timestamp('checklist_ok_at')->nullable()->after('check_epp');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'checklist_por')) {
                $table->unsignedBigInteger('checklist_por')->nullable()->after('checklist_ok_at');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'evidencia_ok_at')) {
                $table->timestamp('evidencia_ok_at')->nullable()->after('checklist_por');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'evidencia_por')) {
                $table->unsignedBigInteger('evidencia_por')->nullable()->after('evidencia_ok_at');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'remision_ok_at')) {
                $table->timestamp('remision_ok_at')->nullable()->after('evidencia_por');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'salida_at')) {
                $table->timestamp('salida_at')->nullable()->after('remision_ok_at');
            }
            if (!Schema::hasColumn('tbl_rutas_carga', 'salida_por')) {
                $table->unsignedBigInteger('salida_por')->nullable()->after('salida_at');
            }
        });

        // FKs opcionales a users.
        foreach ([
            'fk_ruta_checklist_por' => 'checklist_por',
            'fk_ruta_evidencia_por' => 'evidencia_por',
            'fk_ruta_salida_por' => 'salida_por',
        ] as $fk => $col) {
            $existe = collect(DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
                ['tbl_rutas_carga', $fk, 'FOREIGN KEY']
            ))->isNotEmpty();
            if (!$existe && Schema::hasColumn('tbl_rutas_carga', $col)) {
                Schema::table('tbl_rutas_carga', function (Blueprint $table) use ($fk, $col) {
                    $table->foreign($col, $fk)->references('id')->on('users')->nullOnDelete();
                });
            }
        }

        if (!Schema::hasTable('tbl_ruta_carga_evidencias')) {
            Schema::create('tbl_ruta_carga_evidencias', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('ruta_id');
                $table->string('archivo_path', 255);
                $table->string('archivo_nombre', 180)->nullable();
                $table->unsignedBigInteger('subido_por')->nullable();
                $table->timestamps();

                $table->foreign('ruta_id', 'fk_evid_ruta')
                    ->references('id')->on('tbl_rutas_carga')->cascadeOnDelete();
                $table->foreign('subido_por', 'fk_evid_user')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }

        Schema::table('tbl_cargas', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_cargas', 'remision_firmada')) {
                $table->boolean('remision_firmada')->default(false)->after('observaciones');
            }
            if (!Schema::hasColumn('tbl_cargas', 'remision_archivo')) {
                $table->string('remision_archivo', 255)->nullable()->after('remision_firmada');
            }
            if (!Schema::hasColumn('tbl_cargas', 'remision_firmada_at')) {
                $table->timestamp('remision_firmada_at')->nullable()->after('remision_archivo');
            }
            if (!Schema::hasColumn('tbl_cargas', 'remision_firmada_por')) {
                $table->unsignedBigInteger('remision_firmada_por')->nullable()->after('remision_firmada_at');
            }
        });

        $fkRem = collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            ['tbl_cargas', 'fk_carga_remision_por', 'FOREIGN KEY']
        ))->isNotEmpty();
        if (!$fkRem && Schema::hasColumn('tbl_cargas', 'remision_firmada_por')) {
            Schema::table('tbl_cargas', function (Blueprint $table) {
                $table->foreign('remision_firmada_por', 'fk_carga_remision_por')
                    ->references('id')->on('users')->nullOnDelete();
            });
        }

        // Puesto para evidencia (antes “Lalo”) si no existe.
        $now = now();
        $existePuesto = DB::table('tblpuestos')
            ->whereRaw('UPPER(nombre) = ?', ['SUPERVISOR DE ALMACEN'])
            ->exists();
        if (!$existePuesto) {
            DB::table('tblpuestos')->insert([
                'nombre' => 'SUPERVISOR DE ALMACEN',
                'estado' => 'A',
                'descripcion' => 'Evidencia y liberacion de cargas',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tbl_cargas', function (Blueprint $table) {
            try { $table->dropForeign('fk_carga_remision_por'); } catch (\Throwable $e) {}
            foreach (['remision_firmada', 'remision_archivo', 'remision_firmada_at', 'remision_firmada_por'] as $col) {
                if (Schema::hasColumn('tbl_cargas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('tbl_ruta_carga_evidencias');

        Schema::table('tbl_rutas_carga', function (Blueprint $table) {
            foreach (['fk_ruta_checklist_por', 'fk_ruta_evidencia_por', 'fk_ruta_salida_por'] as $fk) {
                try { $table->dropForeign($fk); } catch (\Throwable $e) {}
            }
            foreach ([
                'chofer_tipo', 'chofer_llegada_at',
                'check_seguro_vehiculo', 'check_seguro_imss', 'check_botas', 'check_casco', 'check_chaleco', 'check_epp',
                'checklist_ok_at', 'checklist_por', 'evidencia_ok_at', 'evidencia_por',
                'remision_ok_at', 'salida_at', 'salida_por',
            ] as $col) {
                if (Schema::hasColumn('tbl_rutas_carga', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        DB::table('tbl_pedidos')->where('estatus', 'DESPACHADO')->update(['estatus' => 'CARGA_AVISADA']);
        DB::statement("ALTER TABLE tbl_pedidos MODIFY estatus ENUM(
            'CONFIRMADO',
            'EN_PRODUCCION',
            'PENDIENTE_OC',
            'LISTO_PARA_CARGA',
            'CARGA_AVISADA'
        ) NOT NULL DEFAULT 'CONFIRMADO'");
    }
};
