<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERFIL_MASTER = 1;
    private const VISTA = 'Sistemas/VerTodasEmpresas';
    private const ACCION = 'ver_todas_empresas';

    public function up(): void
    {
        $now = now();
        $deptoId = DB::table('tbldepartamentos')->where('nombre', 'Sistemas')->value('id');
        if (!$deptoId) {
            return;
        }

        $vistaId = DB::table('tblvistas')->where('descripcion', self::VISTA)->value('id');
        if ($vistaId) {
            DB::table('tblvistas')->where('id', $vistaId)->update([
                'nombre' => 'Ver todas las empresas',
                'iddepartamento' => (int) $deptoId,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $vistaId;
        } else {
            $orden = (int) (DB::table('tblvistas')->where('iddepartamento', $deptoId)->max('orden') ?? 0) + 1;
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => 'Ver todas las empresas',
                'descripcion' => self::VISTA,
                'iddepartamento' => (int) $deptoId,
                'orden' => $orden,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (!$accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Ver todas las empresas',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver todas las empresas',
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

        if (Schema::hasTable('tblempresa_vistas')) {
            $vistasModulo = DB::table('tblvistas')
                ->whereIn('descripcion', ['ControlCentros', 'Ventas/Captura', 'AnalisisProgreso', 'Ventas/Analisis'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if ($vistasModulo) {
                $empresas = DB::table('tblempresa_vistas')
                    ->whereIn('id_vista', $vistasModulo)
                    ->distinct()
                    ->pluck('id_empresa');
                foreach ($empresas as $idEmpresa) {
                    $existe = DB::table('tblempresa_vistas')
                        ->where('id_empresa', (int) $idEmpresa)
                        ->where('id_vista', $vistaId)
                        ->exists();
                    if ($existe) {
                        continue;
                    }
                    $row = [
                        'id_empresa' => (int) $idEmpresa,
                        'id_vista' => $vistaId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (Schema::hasColumn('tblempresa_vistas', 'created_by')) {
                        $row['created_by'] = 'migration';
                    }
                    DB::table('tblempresa_vistas')->insert($row);
                }
            }
        }
    }

    public function down(): void
    {
        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if ($accionId) {
            DB::table('tblperfil_acciones')->where('idaccion', $accionId)->delete();
            if (Schema::hasTable('tblusuario_acciones')) {
                DB::table('tblusuario_acciones')->where('idacciones', $accionId)->delete();
            }
            DB::table('tblacciones')->where('id', $accionId)->delete();
        }
        $vistaId = DB::table('tblvistas')->where('descripcion', self::VISTA)->value('id');
        if ($vistaId && Schema::hasTable('tblempresa_vistas')) {
            DB::table('tblempresa_vistas')->where('id_vista', $vistaId)->delete();
        }
        DB::table('tblvistas')->where('descripcion', self::VISTA)->delete();
    }
};
