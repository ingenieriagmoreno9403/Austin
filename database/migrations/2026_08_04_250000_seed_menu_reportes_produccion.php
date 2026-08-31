<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPT_PRODUCCION = 26;
    private const PERFIL_MASTER = 1;
    private const DESCRIPCION_NUEVA = 'produccion/reportes';
    private const DESCRIPCION_VIEJA = 'produccion/reportes/mermas';
    private const ACCION = 'ver_reportes_produccion';
    private const ACCION_VIEJA = 'ver_reporte_mermas_produccion';

    public function up(): void
    {
        $now = now();

        $vista = DB::table('tblvistas')
            ->whereIn('descripcion', [self::DESCRIPCION_NUEVA, self::DESCRIPCION_VIEJA])
            ->orderByRaw("CASE WHEN descripcion = ? THEN 0 ELSE 1 END", [self::DESCRIPCION_NUEVA])
            ->first();

        if ($vista) {
            DB::table('tblvistas')->where('id', $vista->id)->update([
                'nombre' => 'Reportes de produccion',
                'descripcion' => self::DESCRIPCION_NUEVA,
                'iddepartamento' => self::DEPT_PRODUCCION,
                'orden' => 8,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $vista->id;

            // Si quedó otra vista vieja duplicada, desactivarla.
            DB::table('tblvistas')
                ->where('descripcion', self::DESCRIPCION_VIEJA)
                ->where('id', '!=', $vistaId)
                ->update(['estado' => 0, 'updated_at' => $now]);
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Reportes de produccion',
                'descripcion' => self::DESCRIPCION_NUEVA,
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
                'descripcion_accion' => 'Ver reportes de producción',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver reportes de producción',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        // Reusar perfiles de la acción vieja de mermas / producción.
        $perfiles = [self::PERFIL_MASTER];
        foreach ([self::ACCION_VIEJA, 'ver_produccion'] as $accionNombre) {
            $acc = DB::table('tblacciones')->where('nombre_accion', $accionNombre)->value('id');
            if ($acc) {
                $extra = DB::table('tblperfil_acciones')
                    ->where('idaccion', $acc)
                    ->distinct()
                    ->pluck('idperfil')
                    ->all();
                $perfiles = array_values(array_unique(array_merge($perfiles, array_map('intval', $extra))));
            }
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

        // Mantener acción vieja apuntando a la misma vista (compatibilidad).
        $accionVieja = DB::table('tblacciones')->where('nombre_accion', self::ACCION_VIEJA)->value('id');
        if ($accionVieja) {
            DB::table('tblacciones')->where('id', $accionVieja)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver reportes producción',
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if ($accionId) {
            DB::table('tblperfil_acciones')->where('idaccion', $accionId)->delete();
            DB::table('tblacciones')->where('id', $accionId)->delete();
        }

        DB::table('tblvistas')
            ->where('descripcion', self::DESCRIPCION_NUEVA)
            ->update([
                'nombre' => 'Reporte de mermas',
                'descripcion' => self::DESCRIPCION_VIEJA,
                'updated_at' => now(),
            ]);
    }
};
