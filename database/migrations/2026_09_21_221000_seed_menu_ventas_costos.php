<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEPT_VENTAS = 28;
    private const PERFIL_MASTER = 1;
    private const PERFIL_PROYECCIONES = 27;
    private const DESCRIPCION = 'Ventas/Costos';
    private const ACCION = 'ver_ventas_costos';
    private const MENU_NOMBRE = 'Precios';

    /** @var array<int, string> */
    private array $usuariosAsignar = ['master', 'Rene'];

    public function up(): void
    {
        $now = now();

        $vistaId = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->value('id');
        if ($vistaId) {
            DB::table('tblvistas')->where('id', $vistaId)->update([
                'nombre' => self::MENU_NOMBRE,
                'iddepartamento' => self::DEPT_VENTAS,
                'orden' => 4,
                'estado' => 1,
                'updated_at' => $now,
            ]);
            $vistaId = (int) $vistaId;
        } else {
            $vistaId = (int) DB::table('tblvistas')->insertGetId([
                'nombre' => self::MENU_NOMBRE,
                'descripcion' => self::DESCRIPCION,
                'iddepartamento' => self::DEPT_VENTAS,
                'orden' => 4,
                'estado' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        if (! $accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => self::ACCION,
                'descripcion_accion' => 'Ver Ventas / Costos (Precios de productos)',
                'idvista' => $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => $vistaId,
                'descripcion_accion' => 'Ver Ventas / Costos (Precios de productos)',
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        foreach ([self::PERFIL_MASTER, self::PERFIL_PROYECCIONES] as $perfilId) {
            if (! DB::table('tblperfiles')->where('id', $perfilId)->exists()) {
                continue;
            }
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

        // Permiso directo a usuarios solicitados (master, Rene).
        $userIds = DB::table('users')
            ->whereIn('name', $this->usuariosAsignar)
            ->pluck('id');

        foreach ($userIds as $userId) {
            if (Schema::hasTable('tblusuario_acciones')) {
                $ya = DB::table('tblusuario_acciones')
                    ->where('idusuario', $userId)
                    ->where('idacciones', $accionId)
                    ->exists();
                if (! $ya) {
                    $row = [
                        'idusuario' => $userId,
                        'idacciones' => $accionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (Schema::hasColumn('tblusuario_acciones', 'created_by')) {
                        $row['created_by'] = 'migration';
                    }
                    DB::table('tblusuario_acciones')->insert($row);
                }
            }

            if (Schema::hasTable('tblusuario_pantallas')) {
                $yaP = DB::table('tblusuario_pantallas')
                    ->where('idusuario', $userId)
                    ->where('idvista', $vistaId)
                    ->exists();
                if (! $yaP) {
                    $rowP = [
                        'idusuario' => $userId,
                        'idvista' => $vistaId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (Schema::hasColumn('tblusuario_pantallas', 'iddepartamento')) {
                        $rowP['iddepartamento'] = self::DEPT_VENTAS;
                    }
                    if (Schema::hasColumn('tblusuario_pantallas', 'created_by')) {
                        $rowP['created_by'] = 'migration';
                    }
                    DB::table('tblusuario_pantallas')->insert($rowP);
                }
            }

            // Asegurar perfil Proyecciones de Ventas si no lo tienen.
            if (Schema::hasTable('tblusuario_perfiles')
                && DB::table('tblperfiles')->where('id', self::PERFIL_PROYECCIONES)->exists()) {
                $yaPerfil = DB::table('tblusuario_perfiles')
                    ->where('id_usuario', $userId)
                    ->where('id_perfil', self::PERFIL_PROYECCIONES)
                    ->exists();
                if (! $yaPerfil) {
                    DB::table('tblusuario_perfiles')->insert([
                        'id_usuario' => $userId,
                        'id_perfil' => self::PERFIL_PROYECCIONES,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $accionId = DB::table('tblacciones')->where('nombre_accion', self::ACCION)->value('id');
        $vistaId = DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->value('id');

        if ($accionId) {
            DB::table('tblperfil_acciones')->where('idaccion', $accionId)->delete();
            if (Schema::hasTable('tblusuario_acciones')) {
                DB::table('tblusuario_acciones')->where('idacciones', $accionId)->delete();
            }
            DB::table('tblacciones')->where('id', $accionId)->delete();
        }
        if ($vistaId && Schema::hasTable('tblusuario_pantallas')) {
            DB::table('tblusuario_pantallas')->where('idvista', $vistaId)->delete();
        }
        DB::table('tblvistas')->where('descripcion', self::DESCRIPCION)->delete();
    }
};
