<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePvProyeccionesVentas extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_pv_tipos_permiso')) {
            Schema::create('tbl_pv_tipos_permiso', function (Blueprint $table) {
                $table->id();
                $table->string('clave', 40)->unique();
                $table->string('nombre', 80);
                $table->string('descripcion', 255)->nullable();
                $table->unsignedInteger('orden')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('tbl_pv_tipos_permiso') && DB::table('tbl_pv_tipos_permiso')->count() === 0) {
            $now = now();
            DB::table('tbl_pv_tipos_permiso')->insert([
                ['clave' => 'capturar', 'nombre' => 'Capturar', 'descripcion' => 'Puede capturar proyección mensual de ventas', 'orden' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'editar', 'nombre' => 'Editar', 'descripcion' => 'Puede modificar cantidades ya capturadas', 'orden' => 2, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'revisar', 'nombre' => 'Revisar', 'descripcion' => 'Puede consultar y revisar sin editar', 'orden' => 3, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'importar', 'nombre' => 'Importar masivo', 'descripcion' => 'Puede descargar plantilla e importar proyecciones de todos sus clientes. Aplica al usuario en todo el ciclo.', 'orden' => 4, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        if (! Schema::hasTable('tbl_pv_ciclos')) {
            Schema::create('tbl_pv_ciclos', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 40)->unique();
                $table->string('nombre', 180);
                $table->unsignedSmallInteger('anio_referencia');
                $table->unsignedSmallInteger('anio_presupuesto');
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->date('captura_hasta')->nullable();
                $table->date('revision_desde')->nullable();
                $table->string('estado', 30)->default('abierto');
                $table->decimal('tipo_cambio', 12, 4)->default(0);
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index('estado');
                $table->index('anio_presupuesto');
            });
        }

        if (! Schema::hasTable('tbl_pv_asignaciones')) {
            Schema::create('tbl_pv_asignaciones', function (Blueprint $table) {
                $table->id();
                $table->string('ciclo_codigo', 40);
                $table->string('empresa', 40);
                $table->unsignedBigInteger('user_id');
                $table->string('centro_codigo', 40);
                $table->string('centro_nombre', 180)->nullable();
                $table->boolean('es_principal')->default(true);
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['ciclo_codigo', 'empresa', 'user_id', 'centro_codigo'], 'pv_asig_unica');
                $table->index(['ciclo_codigo', 'empresa'], 'pv_asig_ciclo_emp');
                $table->index('user_id', 'pv_asig_user');
                $table->index('parent_id', 'pv_asig_parent');
            });
        }

        if (! Schema::hasTable('tbl_pv_asignacion_cuentas')) {
            Schema::create('tbl_pv_asignacion_cuentas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('asignacion_id');
                $table->string('cuenta_codigo', 80);
                $table->string('cuenta_nombre', 180)->nullable();
                $table->string('agrupacion', 80)->nullable();
                $table->timestamps();

                $table->index('asignacion_id', 'pv_asig_cta_asig');
                $table->unique(['asignacion_id', 'cuenta_codigo'], 'pv_asig_cta_unica');
            });
        }

        if (! Schema::hasTable('tbl_pv_asignacion_permisos')) {
            Schema::create('tbl_pv_asignacion_permisos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('asignacion_id');
                $table->unsignedBigInteger('permiso_id');
                $table->timestamps();

                $table->unique(['asignacion_id', 'permiso_id'], 'pv_asig_perm_unica');
                $table->index('permiso_id', 'pv_asig_perm_tipo');
            });
        }

        if (! Schema::hasTable('tbl_pv_usuario_permisos')) {
            Schema::create('tbl_pv_usuario_permisos', function (Blueprint $table) {
                $table->id();
                $table->string('ciclo_codigo', 40);
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('permiso_id');
                $table->timestamps();

                $table->unique(['ciclo_codigo', 'user_id', 'permiso_id'], 'pv_user_perm_unica');
                $table->index(['ciclo_codigo', 'user_id'], 'pv_user_perm_ciclo_user');
            });
        }

        if (! Schema::hasTable('tbl_pv_grupos_cuenta')) {
            Schema::create('tbl_pv_grupos_cuenta', function (Blueprint $table) {
                $table->id();
                $table->string('empresa', 40);
                $table->string('clave', 40);
                $table->string('nombre', 180);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['empresa', 'clave'], 'pv_grupo_emp_clave');
                $table->index('empresa', 'pv_grupo_emp');
            });
        }

        if (! Schema::hasTable('tbl_pv_grupo_cuentas')) {
            Schema::create('tbl_pv_grupo_cuentas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('grupo_id');
                $table->string('cuenta_codigo', 80);
                $table->string('cuenta_nombre', 180)->nullable();
                $table->timestamps();

                $table->index('grupo_id', 'pv_grupo_cta_grupo');
                $table->unique(['grupo_id', 'cuenta_codigo'], 'pv_grupo_cta_unica');
            });
        }

        if (! Schema::hasTable('tbl_pv_captura_centros')) {
            Schema::create('tbl_pv_captura_centros', function (Blueprint $table) {
                $table->id();
                $table->string('ciclo_codigo', 40);
                $table->string('empresa', 40);
                $table->string('centro_codigo', 40);
                $table->string('estado', 30)->default('en_proceso');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['ciclo_codigo', 'empresa', 'centro_codigo'], 'pv_captura_centro_unica');
                $table->index(['ciclo_codigo', 'empresa'], 'pv_captura_centro_ciclo_emp');
            });
        }

        if (! Schema::hasTable('tbl_pv_presupuestos')) {
            Schema::create('tbl_pv_presupuestos', function (Blueprint $table) {
                $table->id();
                $table->string('ciclo_codigo', 40);
                $table->string('empresa', 40);
                $table->string('centro_codigo', 40);
                $table->string('cuenta_codigo', 80);
                $table->string('cuenta_nombre', 180)->nullable();
                $table->decimal('mes_01', 18, 4)->nullable();
                $table->decimal('mes_02', 18, 4)->nullable();
                $table->decimal('mes_03', 18, 4)->nullable();
                $table->decimal('mes_04', 18, 4)->nullable();
                $table->decimal('mes_05', 18, 4)->nullable();
                $table->decimal('mes_06', 18, 4)->nullable();
                $table->decimal('mes_07', 18, 4)->nullable();
                $table->decimal('mes_08', 18, 4)->nullable();
                $table->decimal('mes_09', 18, 4)->nullable();
                $table->decimal('mes_10', 18, 4)->nullable();
                $table->decimal('mes_11', 18, 4)->nullable();
                $table->decimal('mes_12', 18, 4)->nullable();
                $table->decimal('costo_unitario', 18, 4)->default(0);
                $table->string('moneda', 8)->default('MXN');
                $table->decimal('ajuste_pct', 8, 2)->default(0);
                $table->boolean('completado')->default(false);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['ciclo_codigo', 'empresa', 'centro_codigo', 'cuenta_codigo'], 'pv_ppto_unica');
                $table->index(['ciclo_codigo', 'empresa', 'centro_codigo'], 'pv_ppto_ciclo_emp_cc');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_pv_presupuestos');
        Schema::dropIfExists('tbl_pv_captura_centros');
        Schema::dropIfExists('tbl_pv_grupo_cuentas');
        Schema::dropIfExists('tbl_pv_grupos_cuenta');
        Schema::dropIfExists('tbl_pv_usuario_permisos');
        Schema::dropIfExists('tbl_pv_asignacion_permisos');
        Schema::dropIfExists('tbl_pv_asignacion_cuentas');
        Schema::dropIfExists('tbl_pv_asignaciones');
        Schema::dropIfExists('tbl_pv_ciclos');
        Schema::dropIfExists('tbl_pv_tipos_permiso');
    }
}
