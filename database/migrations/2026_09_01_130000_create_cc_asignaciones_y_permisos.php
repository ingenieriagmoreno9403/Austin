<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCcAsignacionesYPermisos extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_cc_tipos_permiso')) {
            Schema::create('tbl_cc_tipos_permiso', function (Blueprint $table) {
                $table->id();
                $table->string('clave', 40)->unique();
                $table->string('nombre', 80);
                $table->string('descripcion', 255)->nullable();
                $table->unsignedInteger('orden')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('tbl_cc_tipos_permiso') && DB::table('tbl_cc_tipos_permiso')->count() === 0) {
            $now = now();
            DB::table('tbl_cc_tipos_permiso')->insert([
                ['clave' => 'capturar', 'nombre' => 'Capturar', 'descripcion' => 'Puede capturar presupuesto mensual', 'orden' => 1, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'editar', 'nombre' => 'Editar', 'descripcion' => 'Puede modificar montos ya capturados', 'orden' => 2, 'created_at' => $now, 'updated_at' => $now],
                ['clave' => 'revisar', 'nombre' => 'Revisar', 'descripcion' => 'Puede consultar y revisar sin editar', 'orden' => 3, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        if (! Schema::hasTable('tbl_cc_asignaciones')) {
            Schema::create('tbl_cc_asignaciones', function (Blueprint $table) {
                $table->id();
                $table->string('ciclo_codigo', 40);
                $table->string('empresa', 40);
                $table->unsignedBigInteger('user_id');
                $table->string('centro_codigo', 40);
                $table->string('centro_nombre', 180)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->unique(['ciclo_codigo', 'empresa', 'user_id', 'centro_codigo'], 'cc_asig_unica');
                $table->index(['ciclo_codigo', 'empresa'], 'cc_asig_ciclo_emp');
                $table->index('user_id', 'cc_asig_user');
            });
        }

        if (! Schema::hasTable('tbl_cc_asignacion_cuentas')) {
            Schema::create('tbl_cc_asignacion_cuentas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('asignacion_id');
                $table->string('cuenta_codigo', 40);
                $table->string('cuenta_nombre', 180)->nullable();
                $table->string('agrupacion', 80)->nullable();
                $table->timestamps();

                $table->index('asignacion_id', 'cc_asig_cta_asig');
                $table->unique(['asignacion_id', 'cuenta_codigo'], 'cc_asig_cta_unica');
            });
        }

        if (! Schema::hasTable('tbl_cc_asignacion_permisos')) {
            Schema::create('tbl_cc_asignacion_permisos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('asignacion_id');
                $table->unsignedBigInteger('permiso_id');
                $table->timestamps();

                $table->unique(['asignacion_id', 'permiso_id'], 'cc_asig_perm_unica');
                $table->index('permiso_id', 'cc_asig_perm_tipo');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_cc_asignacion_permisos');
        Schema::dropIfExists('tbl_cc_asignacion_cuentas');
        Schema::dropIfExists('tbl_cc_asignaciones');
        Schema::dropIfExists('tbl_cc_tipos_permiso');
    }
}
