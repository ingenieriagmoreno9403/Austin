<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEPT_VENTAS = 28;
    private const DESCRIPCION = 'Ventas/Costos';
    private const ACCION = 'editar_ventas_costos';

    public function up(): void
    {
        $now = now();

        $vistaId = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->value('id');
        if (! $vistaId) {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Precios',
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_VENTAS,
                'orden' => 4,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $vistaId = (int) $vistaId;
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (! $accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Editar precios de productos',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Editar precios de productos',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        // No se liga a perfiles: se asigna solo por acción puntual (tblusuario_acciones).
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
