<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPT_VENTAS = 28;
    private const PERFIL_MASTER = 1;
    private const DESCRIPCION = 'Ventas/Costos';
    private const ACCION = 'ver_ventas_costos';

    public function up(): void
    {
        $now = now();

        $vistaId = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->value('id');
        if ($vistaId) {
            DB::table('tblvistas')->where('id', $vistaId)->update([
                'nombre' => 'Precios de productos',
                'iddepartamento' => self::DEPT_VENTAS,
                'orden' => 4,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $vistaId;
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Precios de productos',
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_VENTAS,
                'orden' => 4,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (! $accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Administrar precios productos ventas',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Administrar precios productos ventas',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        $existe = DB::table('tblperfil_acciones')
            ->where('idperfil', self::PERFIL_MASTER)
            ->where('idaccion', $accionId)
            ->exists();
        if (! $existe) {
            DB::table('tblperfil_acciones')->insert([
                'idperfil' => self::PERFIL_MASTER,
                'idaccion' => $accionId,
                'created_at' => $now,
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
        DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->delete();
    }
};
