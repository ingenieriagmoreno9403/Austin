<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1) Ubicación "MATERIAL REGRESADO" dentro del almacén general.
        $almacen = DB::table('tblalmacenes')
            ->where('estado', 'A')
            ->whereRaw('UPPER(folio_interno) LIKE ?', ['%ALMACEN GENERAL%'])
            ->first();

        if ($almacen) {
            $existeUbicacion = DB::table('tblubicaciones')
                ->where('id_almacen', $almacen->id)
                ->whereRaw('UPPER(folio_interno) = ?', ['MATERIAL REGRESADO'])
                ->exists();

            if (!$existeUbicacion) {
                DB::table('tblubicaciones')->insert([
                    'id_almacen' => $almacen->id,
                    'folio_interno' => 'MATERIAL REGRESADO',
                    'descripcion' => 'Materia prima reintegrada por cancelación de OP (en resguardo hasta traslado)',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 2) Tipo de movimiento para el reintegro.
        $existeTipo = DB::table('tbltipos_movimientos_inventario')
            ->where('nombre_movimiento', 'Reintegro por cancelación')
            ->exists();

        if (!$existeTipo) {
            DB::table('tbltipos_movimientos_inventario')->insert([
                'nombre_movimiento' => 'Reintegro por cancelación',
                'descripcion_movimiento' => 'Entrada de MP por cancelación de orden de producción',
                'created_at' => $now,
            ]);
        }

        // 3) Permiso (acción) para cancelar órdenes de producción, ligado a la vista 78.
        $accionId = DB::table('tblacciones')
            ->where('nombre_accion', 'cancelar_orden_produccion')
            ->value('id');

        if (!$accionId) {
            $accionId = DB::table('tblacciones')->insertGetId([
                'nombre_accion' => 'cancelar_orden_produccion',
                'descripcion_accion' => 'Cancelar orden de producción',
                'idvista' => 78,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Asignar el permiso a los perfiles que ya pueden ver producción (acción ver_produccion),
        // para que quien administra producción pueda cancelar; el resto se asigna desde el módulo de permisos.
        $perfilesProduccion = DB::table('tblperfil_acciones')
            ->whereIn('idaccion', DB::table('tblacciones')->where('nombre_accion', 'ver_produccion')->pluck('id'))
            ->distinct()
            ->pluck('idperfil');

        foreach ($perfilesProduccion as $idPerfil) {
            $yaAsignado = DB::table('tblperfil_acciones')
                ->where('idperfil', $idPerfil)
                ->where('idaccion', $accionId)
                ->exists();

            if (!$yaAsignado) {
                DB::table('tblperfil_acciones')->insert([
                    'idperfil' => $idPerfil,
                    'idaccion' => $accionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $accionId = DB::table('tblacciones')
            ->where('nombre_accion', 'cancelar_orden_produccion')
            ->value('id');

        if ($accionId) {
            DB::table('tblperfil_acciones')->where('idaccion', $accionId)->delete();
            DB::table('tblacciones')->where('id', $accionId)->delete();
        }

        DB::table('tbltipos_movimientos_inventario')
            ->where('nombre_movimiento', 'Reintegro por cancelación')
            ->delete();

        // La ubicación MATERIAL REGRESADO se conserva por seguridad de inventario.
    }
};
