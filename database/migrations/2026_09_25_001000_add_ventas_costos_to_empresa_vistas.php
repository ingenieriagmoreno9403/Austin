<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tblempresa_vistas')) {
            return;
        }

        $vistaPrecios = (int) (DB::table('tblvistas')->where('descripcion', 'Ventas/Costos')->value('id') ?? 0);
        if ($vistaPrecios <= 0) {
            return;
        }

        $otrasVentas = DB::table('tblvistas as v')
            ->join('tbldepartamentos as d', 'd.id', '=', 'v.iddepartamento')
            ->where('d.nombre', 'Ventas')
            ->where('v.descripcion', '!=', 'Ventas/Costos')
            ->pluck('v.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (! $otrasVentas) {
            return;
        }

        $empresas = DB::table('tblempresa_vistas')
            ->whereIn('id_vista', $otrasVentas)
            ->distinct()
            ->pluck('id_empresa');

        $now = now();
        foreach ($empresas as $idEmpresa) {
            $existe = DB::table('tblempresa_vistas')
                ->where('id_empresa', $idEmpresa)
                ->where('id_vista', $vistaPrecios)
                ->exists();
            if ($existe) {
                continue;
            }
            $row = [
                'id_empresa' => (int) $idEmpresa,
                'id_vista' => $vistaPrecios,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('tblempresa_vistas', 'created_by')) {
                $row['created_by'] = 'migration';
            }
            DB::table('tblempresa_vistas')->insert($row);
        }
    }

    public function down(): void
    {
        // No se revierte: dejar el módulo comprado.
    }
};
