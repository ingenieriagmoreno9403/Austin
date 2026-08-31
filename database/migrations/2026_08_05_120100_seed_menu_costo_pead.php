<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPT_PRODUCCION = 26;
    private const PERFIL_MASTER = 1;
    private const DESCRIPCION = 'produccion/costo-pead';
    private const ACCION = 'ver_costo_pead';

    public function up(): void
    {
        $now = now();

        $vistaId = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->value('id');
        if ($vistaId) {
            DB::table('tblvistas')->where('id', $vistaId)->update([
                'nombre' => 'Costo PEAD mensual',
                'iddepartamento' => self::DEPT_PRODUCCION,
                'orden' => 9,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $vistaId;
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Costo PEAD mensual',
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_PRODUCCION,
                'orden' => 9,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (!$accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Ver y capturar costo PEAD mensual',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver y capturar costo PEAD mensual',
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
            $perfiles = array_values(array_unique(array_merge($perfiles, $extra)));
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
    }
};
