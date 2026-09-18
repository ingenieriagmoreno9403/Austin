<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERFIL_MASTER = 1;

    public function up(): void
    {
        $now = now();
        $nombres = [
            'ver_control_costos',
            'visor_centros',
            'ver_analisis_centros',
            'ver_admin_centro_costos',
            'eliminar_ciclo_centros',
            'ver_ventas_capturas',
            'visor_ventas',
            'ver_ventas_analisis',
            'ver_ventas_asignaciones',
            'eliminar_ciclo_ventas',
        ];

        foreach ($nombres as $nombre) {
            $accionId = DB::table('tblacciones')->where('nombre_accion', $nombre)->value('id');
            if (!$accionId) {
                continue;
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
    }

    public function down(): void
    {
        // No se quitan del master: ya existían o son de operación.
    }
};
