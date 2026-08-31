<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPT_INVENTARIOS = 14;
    private const PERFIL_MASTER = 1;
    private const PERFIL_ALMACENISTA = 13;
    private const DESCRIPCION = 'almacen/cargas';
    private const ACCION = 'ver_cargas_almacen';

    public function up(): void
    {
        $now = now();

        $existente = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->first();
        if ($existente) {
            DB::table('tblvistas')->where('id', $existente->id)->update([
                'nombre' => 'Cargas',
                'iddepartamento' => self::DEPT_INVENTARIOS,
                'orden' => 7,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $existente->id;
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Cargas',
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_INVENTARIOS,
                'orden' => 7,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (!$accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Ver y operar cargas / rutas de camion',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver y operar cargas / rutas de camion',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        $perfiles = [self::PERFIL_MASTER, self::PERFIL_ALMACENISTA];

        $auxId = DB::table('tblperfiles')->where('nombre', 'Aux. Almacen')->value('id');
        if ($auxId) {
            $perfiles[] = (int) $auxId;
        }

        // Comercial (perfil que ve pedidos).
        $vistaPedidosId = DB::table('tblvistas')->where('descripcion', 'pedidos')->value('id');
        if ($vistaPedidosId) {
            $accionesPedido = DB::table('tblacciones')->where('idvista', $vistaPedidosId)->pluck('id');
            if ($accionesPedido->isNotEmpty()) {
                $perfilesComercial = DB::table('tblperfil_acciones')
                    ->whereIn('idaccion', $accionesPedido)
                    ->distinct()
                    ->pluck('idperfil')
                    ->all();
                $perfiles = array_values(array_unique(array_merge($perfiles, array_map('intval', $perfilesComercial))));
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
