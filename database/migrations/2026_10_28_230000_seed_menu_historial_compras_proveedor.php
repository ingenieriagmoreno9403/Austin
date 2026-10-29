<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEPT_COMPRAS = 10;
    private const DESCRIPCION = 'compras/historial-proveedores';
    private const ACCION = 'ver_historial_compras_proveedor';

    public function up(): void
    {
        $now = now();

        $vistaId = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->value('id');
        if ($vistaId) {
            DB::table('tblvistas')->where('id', $vistaId)->update([
                'nombre' => 'Historial de proveedores',
                'iddepartamento' => self::DEPT_COMPRAS,
                'orden' => 7,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $vistaId;
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Historial de proveedores',
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_COMPRAS,
                'orden' => 7,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (! $accionId) {
            $accion = [
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Ver historial de compras por proveedor',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('tblacciones', 'created_by')) {
                $accion['created_by'] = 'migration';
            }
            $accionId = (int) DB::table('tblacciones')->insertGetId($accion);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver historial de compras por proveedor',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        $vistaOc = DB::table('tblvistas')->where('descripcion', 'OrdenCompras')->value('id');
        $perfiles = [1];
        if ($vistaOc) {
            $accionesOc = DB::table('tblacciones')->where('idvista', $vistaOc)->pluck('id');
            if ($accionesOc->isNotEmpty()) {
                $perfiles = array_values(array_unique(array_merge(
                    $perfiles,
                    DB::table('tblperfil_acciones')->whereIn('idaccion', $accionesOc)->distinct()->pluck('idperfil')->map(fn ($id) => (int) $id)->all()
                )));
            }
        }

        foreach ($perfiles as $idPerfil) {
            if (! DB::table('tblperfiles')->where('id', $idPerfil)->exists()) {
                continue;
            }
            $ya = DB::table('tblperfil_acciones')
                ->where('idperfil', $idPerfil)
                ->where('idaccion', $accionId)
                ->exists();
            if ($ya) {
                continue;
            }
            $row = [
                'idperfil' => $idPerfil,
                'idaccion' => $accionId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('tblperfil_acciones', 'created_by')) {
                $row['created_by'] = 'migration';
            }
            DB::table('tblperfil_acciones')->insert($row);
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
