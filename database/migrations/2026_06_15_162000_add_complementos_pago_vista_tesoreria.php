<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $departamentoTesoreria = DB::table('tbldepartamentos')
            ->where('nombre', 'like', '%esorer%')
            ->value('id');

        if (!$departamentoTesoreria) {
            return;
        }

        $vistaExistente = DB::table('tblvistas')
            ->where('descripcion', 'Tesoreria/ComplementosPago')
            ->first();

        if ($vistaExistente) {
            $vistaId = $vistaExistente->id;
        } else {
            $vistaId = DB::table('tblvistas')->insertGetId([
                'nombre' => 'Complementos de Pago',
                'descripcion' => 'Tesoreria/ComplementosPago',
                'estado' => '1',
                'iddepartamento' => $departamentoTesoreria,
                'orden' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $accionExistente = DB::table('tblacciones')
            ->where('nombre_accion', 'gestion_complementos_pago')
            ->first();

        if ($accionExistente) {
            $accionId = $accionExistente->id;
        } else {
            $accionId = DB::table('tblacciones')->insertGetId([
                'nombre_accion' => 'gestion_complementos_pago',
                'descripcion_accion' => 'Gestion Complementos de Pago',
                'idvista' => $vistaId,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'system',
            ]);
        }

        foreach ([1, 6] as $perfilId) {
            $yaAsignado = DB::table('tblperfil_acciones')
                ->where('idperfil', $perfilId)
                ->where('idaccion', $accionId)
                ->exists();

            if (!$yaAsignado) {
                DB::table('tblperfil_acciones')->insert([
                    'idperfil' => $perfilId,
                    'idaccion' => $accionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => 'system',
                ]);
            }
        }
    }

    public function down(): void
    {
        $accion = DB::table('tblacciones')
            ->where('nombre_accion', 'gestion_complementos_pago')
            ->first();

        if ($accion) {
            DB::table('tblperfil_acciones')->where('idaccion', $accion->id)->delete();
            DB::table('tblacciones')->where('id', $accion->id)->delete();
        }

        DB::table('tblvistas')->where('descripcion', 'Tesoreria/ComplementosPago')->delete();
    }
};
