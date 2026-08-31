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
            ->where('descripcion', 'Tesoreria/NotasCredito')
            ->first();

        if ($vistaExistente) {
            $vistaId = $vistaExistente->id;
        } else {
            $vistaId = DB::table('tblvistas')->insertGetId([
                'nombre' => 'Notas de Credito',
                'descripcion' => 'Tesoreria/NotasCredito',
                'estado' => '1',
                'iddepartamento' => $departamentoTesoreria,
                'orden' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $accionExistente = DB::table('tblacciones')
            ->where('nombre_accion', 'gestion_notas_credito')
            ->first();

        if ($accionExistente) {
            $accionId = $accionExistente->id;
        } else {
            $accionId = DB::table('tblacciones')->insertGetId([
                'nombre_accion' => 'gestion_notas_credito',
                'descripcion_accion' => 'Gestion Notas de Credito',
                'idvista' => $vistaId,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'system',
            ]);
        }

        $perfiles = [1, 6];

        foreach ($perfiles as $perfilId) {
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
            ->where('nombre_accion', 'gestion_notas_credito')
            ->first();

        if ($accion) {
            DB::table('tblperfil_acciones')->where('idaccion', $accion->id)->delete();
            DB::table('tblacciones')->where('id', $accion->id)->delete();
        }

        DB::table('tblvistas')->where('descripcion', 'Tesoreria/NotasCredito')->delete();
    }
};
