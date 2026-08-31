<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPT_PRODUCCION = 26;

    private const VISTA_ORDENES = 78;

    private const PERFIL_MASTER = 1;

    public function up(): void
    {
        $now = now();

        DB::table('tbldepartamentos')
            ->where('id', self::DEPT_PRODUCCION)
            ->update([
                'icon' => 'fa-solid fa-industry',
                'updated_at' => $now,
            ]);

        DB::table('tblvistas')
            ->where('id', self::VISTA_ORDENES)
            ->update([
                'nombre' => 'Ordenes de Produccion',
                'descripcion' => 'produccion',
                'orden' => 1,
                'estado' => 1,
                'updated_at' => $now,
            ]);

        DB::table('tblacciones')
            ->where('idvista', self::VISTA_ORDENES)
            ->update([
                'nombre_accion' => 'ver_produccion',
                'descripcion_accion' => 'Ordenes de produccion',
                'updated_at' => $now,
            ]);

        $vistasNuevas = [
            [
                'nombre' => 'Especificaciones de tubo',
                'descripcion' => 'produccion/especificaciones-tubo',
                'orden' => 2,
                'accion' => 'ver_especificaciones_tubo',
                'accion_desc' => 'Especificaciones de tubo',
            ],
            [
                'nombre' => 'Recetas de produccion',
                'descripcion' => 'produccion/recetas',
                'orden' => 3,
                'accion' => 'ver_recetas_produccion',
                'accion_desc' => 'Recetas de produccion',
            ],
        ];

        foreach ($vistasNuevas as $vista) {
            $existente = DB::table('tblvistas')
                ->where('descripcion', $vista['descripcion'])
                ->first();

            if ($existente) {
                DB::table('tblvistas')
                    ->where('id', $existente->id)
                    ->update([
                        'nombre' => $vista['nombre'],
                        'iddepartamento' => self::DEPT_PRODUCCION,
                        'orden' => $vista['orden'],
                        'estado' => 1,
                        'updated_at' => $now,
                    ]);

                $vistaId = $existente->id;
            } else {
                $vistaId = DB::table('tblvistas')->insertGetId([
                    'nombre' => $vista['nombre'],
                    'descripcion' => $vista['descripcion'],
                    'iddepartamento' => self::DEPT_PRODUCCION,
                    'orden' => $vista['orden'],
                    'estado' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $accion = DB::table('tblacciones')
                ->where('idvista', $vistaId)
                ->first();

            if ($accion) {
                $accionId = $accion->id;
            } else {
                $accionId = DB::table('tblacciones')->insertGetId([
                    'nombre_accion' => $vista['accion'],
                    'descripcion_accion' => $vista['accion_desc'],
                    'idvista' => $vistaId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => 'migration',
                ]);
            }

            $tienePermiso = DB::table('tblperfil_acciones')
                ->where('idperfil', self::PERFIL_MASTER)
                ->where('idaccion', $accionId)
                ->exists();

            if (! $tienePermiso) {
                DB::table('tblperfil_acciones')->insert([
                    'idperfil' => self::PERFIL_MASTER,
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
        $now = now();

        DB::table('tbldepartamentos')
            ->where('id', self::DEPT_PRODUCCION)
            ->update([
                'icon' => 'fa',
                'updated_at' => $now,
            ]);

        DB::table('tblvistas')
            ->where('id', self::VISTA_ORDENES)
            ->update([
                'nombre' => 'Tablero de Control',
                'descripcion' => 'tablero',
                'updated_at' => $now,
            ]);

        DB::table('tblacciones')
            ->where('idvista', self::VISTA_ORDENES)
            ->update([
                'nombre_accion' => 'ver_tablero',
                'descripcion_accion' => 'ver tablero',
                'updated_at' => $now,
            ]);

        foreach (['produccion/especificaciones-tubo', 'produccion/recetas'] as $descripcion) {
            $vista = DB::table('tblvistas')->where('descripcion', $descripcion)->first();

            if (! $vista) {
                continue;
            }

            $acciones = DB::table('tblacciones')->where('idvista', $vista->id)->pluck('id');

            if ($acciones->isNotEmpty()) {
                DB::table('tblperfil_acciones')->whereIn('idaccion', $acciones)->delete();
                DB::table('tblacciones')->whereIn('id', $acciones)->delete();
            }

            DB::table('tblvistas')->where('id', $vista->id)->delete();
        }
    }
};
