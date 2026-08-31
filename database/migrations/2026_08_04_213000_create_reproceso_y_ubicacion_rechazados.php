<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEPT_PRODUCCION = 26;
    private const PERFIL_MASTER = 1;
    private const DESCRIPCION = 'produccion/reproceso';
    private const ACCION = 'ver_reproceso_produccion';
    private const UBICACION = 'RECHAZADOS POR CALIDAD';

    public function up(): void
    {
        $now = now();

        // 1) Ubicación fija de tubería no conforme / a reprocesar.
        $almacen = DB::table('tblalmacenes')
            ->where('estado', 'A')
            ->whereRaw('UPPER(folio_interno) LIKE ?', ['%ALMACEN GENERAL%'])
            ->first();

        if (!$almacen) {
            $almacen = DB::table('tblalmacenes')->where('estado', 'A')->orderBy('id')->first();
        }

        if ($almacen) {
            $existe = DB::table('tblubicaciones')
                ->where('id_almacen', $almacen->id)
                ->whereRaw('UPPER(folio_interno) = ?', [self::UBICACION])
                ->exists();

            if (!$existe) {
                DB::table('tblubicaciones')->insert([
                    'id_almacen' => $almacen->id,
                    'folio_interno' => self::UBICACION,
                    'descripcion' => 'Tubería rechazada por calidad o merma de extrusión pendiente de reproceso / triturado',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 2) Tabla de lotes de reproceso.
        if (!Schema::hasTable('tbl_reprocesos')) {
            Schema::create('tbl_reprocesos', function (Blueprint $table) {
                $table->increments('id');
                $table->string('folio', 40)->unique();
                $table->enum('origen', ['CALIDAD', 'EXTRUSION'])->default('CALIDAD');
                $table->enum('estatus', [
                    'DISPONIBLE',
                    'CORTADA',
                    'PESADA',
                    'REPORTADA',
                    'TRITURADA',
                    'CANCELADA',
                ])->default('DISPONIBLE');
                $table->unsignedInteger('orden_id')->nullable();
                $table->unsignedInteger('orden_detalle_id')->nullable();
                $table->unsignedInteger('salida_id')->nullable();
                $table->unsignedInteger('inspeccion_id')->nullable();
                $table->unsignedInteger('ubicacion_id')->nullable();
                $table->unsignedInteger('producto_id')->nullable();
                $table->decimal('metros', 12, 3)->nullable();
                $table->decimal('kg_estimado', 12, 3)->nullable();
                $table->decimal('kg_pesado', 12, 3)->nullable();
                $table->decimal('kg_reportado', 12, 3)->nullable();
                $table->string('observaciones', 1000)->nullable();
                $table->unsignedInteger('creado_por')->nullable();
                $table->unsignedInteger('reportado_por')->nullable();
                $table->timestamp('reportado_at')->nullable();
                $table->unsignedInteger('triturado_por')->nullable();
                $table->timestamp('triturado_at')->nullable();
                $table->timestamps();

                $table->unique('salida_id', 'uk_reproceso_salida');
                $table->index(['estatus', 'origen'], 'idx_reproceso_estatus_origen');
                $table->index('orden_id', 'idx_reproceso_orden');
            });
        }

        // 3) Menú Producción → Reproceso.
        $existente = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->first();
        if ($existente) {
            DB::table('tblvistas')->where('id', $existente->id)->update([
                'nombre' => 'Reproceso',
                'iddepartamento' => self::DEPT_PRODUCCION,
                'orden' => 5,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $existente->id;
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Reproceso',
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_PRODUCCION,
                'orden' => 5,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (!$accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Ver y operar reproceso / triturado de tubería',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver y operar reproceso / triturado de tubería',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        $perfiles = [self::PERFIL_MASTER];
        $accProd = DB::table('tblacciones')->where('nombre_accion', 'ver_produccion')->value('id');
        if ($accProd) {
            $extra = DB::table('tblperfil_acciones')
                ->where('idaccion', $accProd)
                ->distinct()
                ->pluck('idperfil')
                ->all();
            $perfiles = array_values(array_unique(array_merge($perfiles, array_map('intval', $extra))));
        }

        foreach ($perfiles as $idPerfil) {
            $ya = DB::table('tblperfil_acciones')
                ->where('idperfil', $idPerfil)
                ->where('idaccion', $accionId)
                ->exists();
            if (!$ya) {
                DB::table('tblperfil_acciones')->insert([
                    'idperfil' => $idPerfil,
                    'idaccion' => $accionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => 'migration',
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

        Schema::dropIfExists('tbl_reprocesos');
    }
};
