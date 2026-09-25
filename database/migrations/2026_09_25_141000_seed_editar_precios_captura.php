<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DESCRIPCION_VISTA = 'Ventas/Captura';
    private const ACCION = 'editar_precios_captura';

    public function up(): void
    {
        $now = now();
        $vistaId = (int) (DB::table('tblvistas')->where('descripcion', self::DESCRIPCION_VISTA)->value('id') ?? 0);
        if ($vistaId <= 0) {
            return;
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (! $accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Editar precios por mes (Captura)',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Editar precios por mes (Captura)',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        // Solo asignación individual (acción puntual).
        DB::table('tblperfil_acciones')->where('idaccion', $accionId)->delete();
    }

    public function down(): void
    {
        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (! $accionId) {
            return;
        }
        DB::table('tblperfil_acciones')->where('idaccion', $accionId)->delete();
        if (Schema::hasTable('tblusuario_acciones')) {
            DB::table('tblusuario_acciones')->where('idacciones', $accionId)->delete();
        }
        DB::table('tblacciones')->where('id', $accionId)->delete();
    }
};
