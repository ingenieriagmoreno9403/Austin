<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERFIL_MASTER = 1;
    private const VISTA = 'Sistemas/Usuarios';
    private const ACCION = 'editar_credenciales_usuario';

    public function up(): void
    {
        $now = now();
        $deptoId = DB::table('tbldepartamentos')->where('nombre', 'Sistemas')->value('id');
        if (!$deptoId) {
            return;
        }

        $vistaId = DB::table('tblvistas')->where('descripcion', self::VISTA)->value('id');
        if ($vistaId) {
            DB::table('tblvistas')->where('id', $vistaId)->update([
                'nombre' => 'Nombre y contraseña',
                'iddepartamento' => (int) $deptoId,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $vistaId;
        } else {
            $orden = (int) (DB::table('tblvistas')->where('iddepartamento', $deptoId)->max('orden') ?? 0) + 1;
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Nombre y contraseña',
                'descripcion' => self::VISTA,
                'iddepartamento' => (int) $deptoId,
                'orden' => $orden,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (!$accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Cambiar nombre o contraseña de usuarios',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Cambiar nombre o contraseña de usuarios',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        $ya = DB::table('tblperfil_acciones')
            ->where('idperfil', self::PERFIL_MASTER)
            ->where('idaccion', $accionId)
            ->exists();
        if (!$ya) {
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
        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if ($accionId) {
            DB::table('tblperfil_acciones')->where('idaccion', $accionId)->delete();
            DB::table('tblacciones')->where('id', $accionId)->delete();
        }
        DB::table('tblvistas')->where('descripcion', self::VISTA)->delete();
    }
};
