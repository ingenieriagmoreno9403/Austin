<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $deptId = DB::table('tbldepartamentos')->where('nombre', 'Compras')->value('id');
        if (! $deptId) {
            return;
        }

        $vistaId = DB::table('tblvistas')->where('descripcion', 'requisiciones')->value('id');
        if ($vistaId) {
            DB::table('tblvistas')->where('id', $vistaId)->update([
                'nombre' => 'Requisiciones',
                'iddepartamento' => $deptId,
                'orden' => 2,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $vistaId;
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Requisiciones',
                'descripcion' => 'requisiciones',
                'iddepartamento' => $deptId,
                'orden' => 2,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Empujar un poco Licitaciones / OC si chocan en orden
        DB::table('tblvistas')
            ->where('iddepartamento', $deptId)
            ->where('descripcion', 'licitaciones')
            ->update(['orden' => 3, 'updated_at' => $now]);

        $accionId = DB::table('tblacciones')->where('nombre_accion', 'ver_requisiciones')->value('id');
        if (! $accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => 'ver_requisiciones',
                'descripcion_accion' => 'Ver módulo de Requisiciones',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver módulo de Requisiciones',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        // Perfil Master (id 1) si existe
        if (DB::table('tblperfiles')->where('id', 1)->exists()) {
            $ya = DB::table('tblperfil_acciones')
                ->where('idperfil', 1)
                ->where('idaccion', $accionId)
                ->exists();
            if (! $ya) {
                DB::table('tblperfil_acciones')->insert([
                    'idperfil' => 1,
                    'idaccion' => $accionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Copiar permiso a perfiles que ya ven Licitaciones
        $vistaLic = DB::table('tblvistas')->where('descripcion', 'licitaciones')->value('id');
        if ($vistaLic) {
            $accionesLic = DB::table('tblacciones')->where('idvista', $vistaLic)->pluck('id');
            $perfiles = DB::table('tblperfil_acciones')
                ->whereIn('idaccion', $accionesLic)
                ->distinct()
                ->pluck('idperfil');
            foreach ($perfiles as $perfilId) {
                $existe = DB::table('tblperfil_acciones')
                    ->where('idperfil', $perfilId)
                    ->where('idaccion', $accionId)
                    ->exists();
                if (! $existe) {
                    DB::table('tblperfil_acciones')->insert([
                        'idperfil' => $perfilId,
                        'idaccion' => $accionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $vistaId = DB::table('tblvistas')->where('descripcion', 'requisiciones')->value('id');
        if (! $vistaId) {
            return;
        }
        $accionIds = DB::table('tblacciones')->where('idvista', $vistaId)->pluck('id');
        if ($accionIds->isNotEmpty()) {
            DB::table('tblperfil_acciones')->whereIn('idaccion', $accionIds)->delete();
            DB::table('tblacciones')->whereIn('id', $accionIds)->delete();
        }
        DB::table('tblvistas')->where('id', $vistaId)->delete();
    }
};
