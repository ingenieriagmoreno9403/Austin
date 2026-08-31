<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPT_PRODUCCION = 26;
    private const PERFIL_MASTER = 1;
    private const DESCRIPCION = 'produccion/reportes/mermas';
    private const ACCION = 'ver_reporte_mermas_produccion';

    public function up(): void
    {
        $now = now();

        $existente = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->first();
        if ($existente) {
            DB::table('tblvistas')->where('id', $existente->id)->update([
                'nombre' => 'Reporte de mermas',
                'iddepartamento' => self::DEPT_PRODUCCION,
                'orden' => 8,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $existente->id;
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Reporte de mermas',
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_PRODUCCION,
                'orden' => 8,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (!$accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Ver reporte de mermas producción',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver reporte de mermas producción',
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
    }
};
