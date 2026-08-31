<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEPT_PRODUCCION = 26;
    private const PERFIL_MASTER = 1;
    private const DESCRIPCION = 'produccion/especificaciones-conexiones';
    private const ACCION = 'ver_especificaciones_conexiones';

    public function up(): void
    {
        $now = now();

        if (!Schema::hasTable('tbl_producto_conexion_especificaciones')) {
            Schema::create('tbl_producto_conexion_especificaciones', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('producto_id');
                $table->string('codigo', 80)->nullable()->index();
                $table->string('tipo', 80);
                $table->decimal('diametro_mm', 12, 3)->nullable();
                $table->string('diametro_nominal', 30)->nullable();
                $table->decimal('rd', 8, 3)->nullable();
                $table->decimal('peso_tubo_kg_m', 12, 4)->nullable();
                $table->decimal('peso_kg_pieza', 12, 4)->nullable();
                $table->string('material', 80)->nullable();
                $table->enum('estatus', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
                $table->timestamps();

                $table->index(['producto_id', 'estatus'], 'idx_conexion_prod_estatus');
                $table->index(['tipo', 'diametro_nominal'], 'idx_conexion_tipo_diam');
            });
        }

        $vistaId = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->value('id');
        if ($vistaId) {
            DB::table('tblvistas')->where('id', $vistaId)->update([
                'nombre' => 'Especificaciones conexiones',
                'iddepartamento' => self::DEPT_PRODUCCION,
                'orden' => 3,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $vistaId;
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Especificaciones conexiones',
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_PRODUCCION,
                'orden' => 3,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (!$accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Ver especificaciones de conexiones HDPE',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        $perfiles = [self::PERFIL_MASTER];
        foreach (['ver_produccion', 'ver_especificaciones_tubo'] as $accNombre) {
            $acc = DB::table('tblacciones')->where('nombre_accion', $accNombre)->value('id');
            if ($acc) {
                $extra = DB::table('tblperfil_acciones')
                    ->where('idaccion', $acc)
                    ->distinct()
                    ->pluck('idperfil')
                    ->all();
                $perfiles = array_values(array_unique(array_merge($perfiles, $extra)));
            }
        }

        foreach ($perfiles as $perfilId) {
            $existe = DB::table('tblperfil_acciones')
                ->where('idperfil', $perfilId)
                ->where('idaccion', $accionId)
                ->exists();
            if (!$existe) {
                DB::table('tblperfil_acciones')->insert([
                    'idperfil' => $perfilId,
                    'idaccion' => $accionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if ($accionId) {
            DB::table('tblperfil_acciones')->where('idaccion', $accionId)->delete();
            DB::table('tblacciones')->where('id', $accionId)->delete();
        }
        DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->delete();
        Schema::dropIfExists('tbl_producto_conexion_especificaciones');
    }
};
