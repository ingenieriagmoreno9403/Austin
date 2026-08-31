<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPT_PEDIDOS = 25;
    private const PERFIL_MASTER = 1;
    private const DESCRIPCION = 'pedidos/logistica';
    private const ACCION = 'ver_logistica_pedidos';

    public function up(): void
    {
        $now = now();

        $existente = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->first();
        if ($existente) {
            DB::table('tblvistas')->where('id', $existente->id)->update([
                'nombre' => 'Logística',
                'iddepartamento' => self::DEPT_PEDIDOS,
                'orden' => 2,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $existente->id;
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Logística',
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_PEDIDOS,
                'orden' => 2,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (!$accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Ver pedidos ya timbrados (Logística)',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver pedidos ya timbrados (Logística)',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        $perfiles = [self::PERFIL_MASTER];

        // Mismos perfiles que ya ven Pedidos.
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
