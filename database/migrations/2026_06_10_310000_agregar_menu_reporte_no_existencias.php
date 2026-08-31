<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPT_COMERCIAL = 10;

    private const PERFIL_MASTER = 1;

    private const DESCRIPCION = 'compras/reporte-no-existencias';

    public function up(): void
    {
        $now = now();

        $existente = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->first();

        if ($existente) {
            DB::table('tblvistas')->where('id', $existente->id)->update([
                'nombre' => 'Reporte de no existencias',
                'iddepartamento' => self::DEPT_COMERCIAL,
                'orden' => 5,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = $existente->id;
        } else {
            $vistaId = DB::table('tblvistas')->insertGetId([
                'nombre' => 'Reporte de no existencias',
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_COMERCIAL,
                'orden' => 5,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accion = DB::table('tblacciones')->where('idvista', $vistaId)->first();

        if ($accion) {
            $accionId = $accion->id;
        } else {
            $accionId = DB::table('tblacciones')->insertGetId([
                'nombre_accion' => 'ver_reporte_no_existencias',
                'descripcion_accion' => 'Reporte de no existencias',
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

    public function down(): void
    {
        $vista = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->first();

        if (! $vista) {
            return;
        }

        $acciones = DB::table('tblacciones')->where('idvista', $vista->id)->pluck('id');

        if ($acciones->isNotEmpty()) {
            DB::table('tblperfil_acciones')->whereIn('idaccion', $acciones)->delete();
            DB::table('tblacciones')->whereIn('id', $acciones)->delete();
        }

        DB::table('tblvistas')->where('id', $vista->id)->delete();
    }
};
