<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ACCION = 'editar_precio_venta';
    private const VISTA_PEDIDOS = 77;
    private const PERFIL_MASTER = 1;

    public function up(): void
    {
        $now = now();

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (!$accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Editar precio unitario en cotizacion/pedido',
                'idvista' => self::VISTA_PEDIDOS,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => self::VISTA_PEDIDOS,
                'descripcion_accion' => 'Editar precio unitario en cotizacion/pedido',
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
    }
};
