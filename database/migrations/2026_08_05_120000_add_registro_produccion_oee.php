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

        if (!Schema::hasTable('tbl_causas_paro')) {
            Schema::create('tbl_causas_paro', function (Blueprint $table) {
                $table->increments('id');
                $table->string('clave', 20)->unique();
                $table->string('nombre', 150);
                $table->string('tipo', 20)->default('NO_PLANEADO');
                $table->string('estatus', 1)->default('A');
                $table->unsignedSmallInteger('orden')->default(0);
                $table->timestamps();
            });
        }

        $causas = [
            ['clave' => 'CP-01', 'nombre' => 'Falla mecánica', 'tipo' => 'NO_PLANEADO', 'orden' => 1],
            ['clave' => 'CP-02', 'nombre' => 'Falla eléctrica', 'tipo' => 'NO_PLANEADO', 'orden' => 2],
            ['clave' => 'CP-03', 'nombre' => 'Falta de material', 'tipo' => 'NO_PLANEADO', 'orden' => 3],
            ['clave' => 'CP-04', 'nombre' => 'Cambio de medida/molde (setup)', 'tipo' => 'PLANEADO', 'orden' => 4],
            ['clave' => 'CP-05', 'nombre' => 'Arranque/Calentamiento', 'tipo' => 'PLANEADO', 'orden' => 5],
            ['clave' => 'CP-06', 'nombre' => 'Limpieza', 'tipo' => 'PLANEADO', 'orden' => 6],
            ['clave' => 'CP-07', 'nombre' => 'Ajuste de calidad', 'tipo' => 'NO_PLANEADO', 'orden' => 7],
            ['clave' => 'CP-08', 'nombre' => 'Mantenimiento programado', 'tipo' => 'PLANEADO', 'orden' => 8],
            ['clave' => 'CP-09', 'nombre' => 'Falta de operador', 'tipo' => 'NO_PLANEADO', 'orden' => 9],
            ['clave' => 'CP-10', 'nombre' => 'Falta de energía', 'tipo' => 'NO_PLANEADO', 'orden' => 10],
            ['clave' => 'CP-11', 'nombre' => 'Otro', 'tipo' => 'NO_PLANEADO', 'orden' => 11],
        ];

        foreach ($causas as $causa) {
            $existe = DB::table('tbl_causas_paro')->where('clave', $causa['clave'])->exists();
            if ($existe) {
                DB::table('tbl_causas_paro')->where('clave', $causa['clave'])->update([
                    'nombre' => $causa['nombre'],
                    'tipo' => $causa['tipo'],
                    'estatus' => 'A',
                    'orden' => $causa['orden'],
                    'updated_at' => $now,
                ]);
                continue;
            }

            DB::table('tbl_causas_paro')->insert([
                'clave' => $causa['clave'],
                'nombre' => $causa['nombre'],
                'tipo' => $causa['tipo'],
                'estatus' => 'A',
                'orden' => $causa['orden'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!Schema::hasTable('tbl_costo_pead_mensual')) {
            Schema::create('tbl_costo_pead_mensual', function (Blueprint $table) {
                $table->increments('id');
                $table->string('mes', 7)->unique();
                $table->decimal('costo_kg', 12, 4);
                $table->string('proveedor', 150)->nullable();
                $table->string('observaciones', 500)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tbl_registro_produccion')) {
            Schema::create('tbl_registro_produccion', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('orden_id')->unique();
                $table->dateTime('hora_inicio')->nullable();
                $table->dateTime('hora_fin')->nullable();
                $table->decimal('t_programado_h', 12, 3)->nullable();
                $table->decimal('t_arranque_h', 12, 3)->nullable();
                $table->decimal('t_paros_h', 12, 3)->nullable();
                $table->decimal('t_operando_h', 12, 3)->nullable();
                $table->decimal('mat_inicial_kg', 14, 3)->nullable();
                $table->decimal('mat_reciclado_kg', 14, 3)->nullable();
                $table->decimal('aditivos_kg', 14, 3)->nullable();
                $table->decimal('mat_sobrante_kg', 14, 3)->nullable();
                $table->decimal('mat_utilizado_kg', 14, 3)->nullable();
                $table->decimal('prod_bueno_kg', 14, 3)->nullable();
                $table->decimal('prod_defectuoso_kg', 14, 3)->nullable();
                $table->decimal('total_producido_kg', 14, 3)->nullable();
                $table->decimal('metros', 14, 3)->nullable();
                $table->decimal('porcentaje_merma', 10, 3)->nullable();
                $table->decimal('rendimiento_pct', 10, 3)->nullable();
                $table->decimal('prod_hora_kg', 12, 3)->nullable();
                $table->decimal('disponibilidad_pct', 10, 3)->nullable();
                $table->decimal('rendimiento_oee_pct', 10, 3)->nullable();
                $table->decimal('calidad_pct', 10, 3)->nullable();
                $table->decimal('oee_pct', 10, 3)->nullable();
                $table->decimal('costo_pead_kg', 12, 4)->nullable();
                $table->decimal('costo_material', 14, 2)->nullable();
                $table->decimal('costo_unit_m', 14, 4)->nullable();
                $table->decimal('kwh', 12, 3)->nullable();
                $table->decimal('kwh_kg', 12, 4)->nullable();
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->foreign('orden_id')->references('id')->on('tbl_ordenes')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('tbl_paros_produccion')) {
            Schema::create('tbl_paros_produccion', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('orden_id');
                $table->integer('maquina_id')->nullable();
                $table->unsignedInteger('causa_paro_id');
                $table->date('fecha');
                $table->dateTime('hora_inicio');
                $table->dateTime('hora_fin')->nullable();
                $table->decimal('duracion_h', 12, 3)->nullable();
                $table->string('comentario', 500)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('orden_id');
                $table->foreign('orden_id')->references('id')->on('tbl_ordenes')->cascadeOnDelete();
                $table->foreign('causa_paro_id')->references('id')->on('tbl_causas_paro')->restrictOnDelete();
            });
        }

        if (Schema::hasTable('tbl_maquinas')) {
            Schema::table('tbl_maquinas', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_maquinas', 'potencia_kw')) {
                    $table->decimal('potencia_kw', 12, 3)->nullable();
                }
                if (!Schema::hasColumn('tbl_maquinas', 'horas_turno')) {
                    $table->decimal('horas_turno', 8, 2)->nullable();
                }
                if (!Schema::hasColumn('tbl_maquinas', 'turnos_dia')) {
                    $table->unsignedTinyInteger('turnos_dia')->nullable();
                }
                if (!Schema::hasColumn('tbl_maquinas', 'operador_responsable_id')) {
                    $table->unsignedBigInteger('operador_responsable_id')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_paros_produccion');
        Schema::dropIfExists('tbl_registro_produccion');
        Schema::dropIfExists('tbl_costo_pead_mensual');
        Schema::dropIfExists('tbl_causas_paro');

        if (Schema::hasTable('tbl_maquinas')) {
            Schema::table('tbl_maquinas', function (Blueprint $table) {
                foreach (['potencia_kw', 'horas_turno', 'turnos_dia', 'operador_responsable_id'] as $col) {
                    if (Schema::hasColumn('tbl_maquinas', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
