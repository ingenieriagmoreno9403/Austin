<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERFIL_MASTER = 1;

    public function up(): void
    {
        $now = now();

        $this->actualizarDescripcion('ver_control_costos', 'Captura de presupuesto');
        $this->actualizarDescripcion('ver_analisis_centros', 'Análisis de presupuesto');
        $this->actualizarDescripcion('ver_admin_centro_costos', 'Budgets');
        $this->actualizarDescripcion('ver_ventas_capturas', 'Captura de proyecciones');
        $this->actualizarDescripcion('ver_ventas_analisis', 'Análisis de proyecciones');
        $this->actualizarDescripcion('ver_ventas_asignaciones', 'Asignaciones');

        $this->asegurarAccion('visor_centros', 'Visor de presupuesto', 'ControlCentros', 'ver_control_costos', $now, true);
        $this->asegurarAccion('eliminar_ciclo_centros', 'Eliminar presupuesto', 'AdminCentros', 'ver_admin_centro_costos', $now, false);
        $this->asegurarAccion('visor_ventas', 'Visor de proyecciones', 'Ventas/Captura', 'ver_ventas_capturas', $now, true);
        $this->asegurarAccion('eliminar_ciclo_ventas', 'Eliminar proyección', 'Ventas/Asignaciones', 'ver_ventas_asignaciones', $now, false);
    }

    public function down(): void
    {
        foreach (['visor_centros', 'eliminar_ciclo_centros', 'visor_ventas', 'eliminar_ciclo_ventas'] as $nombre) {
            $id = DB::table('tblacciones')->where('nombre_accion', $nombre)->value('id');
            if (!$id) {
                continue;
            }
            DB::table('tblperfil_acciones')->where('idaccion', $id)->delete();
            if (Schema::hasTable('tblusuario_acciones')) {
                DB::table('tblusuario_acciones')->where('idacciones', $id)->delete();
            }
            DB::table('tblacciones')->where('id', $id)->delete();
        }
    }

    private function actualizarDescripcion(string $nombre, string $descripcion): void
    {
        DB::table('tblacciones')->where('nombre_accion', $nombre)->update([
            'descripcion_accion' => $descripcion,
            'updated_at' => now(),
        ]);
    }

    private function asegurarAccion(
        string $nombre,
        string $descripcion,
        string $vistaDescripcion,
        string $accionHermana,
        $now,
        bool $copiarDesdeHermana
    ): void {
        $vistaId = DB::table('tblvistas')->where('descripcion', $vistaDescripcion)->value('id');
        if (!$vistaId) {
            return;
        }

        $accionId = DB::table('tblacciones')->where('nombre_accion', $nombre)->value('id');
        if (!$accionId) {
            $accionId = (int) DB::table('tblacciones')->insertGetId([
                'nombre_accion' => $nombre,
                'descripcion_accion' => $descripcion,
                'idvista' => (int) $vistaId,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => 'migration',
            ]);
        } else {
            DB::table('tblacciones')->where('id', $accionId)->update([
                'idvista' => (int) $vistaId,
                'descripcion_accion' => $descripcion,
                'updated_at' => $now,
            ]);
            $accionId = (int) $accionId;
        }

        $perfiles = [self::PERFIL_MASTER];
        $hermanaId = DB::table('tblacciones')->where('nombre_accion', $accionHermana)->value('id');
        if ($copiarDesdeHermana && $hermanaId) {
            $extra = DB::table('tblperfil_acciones')
                ->where('idaccion', $hermanaId)
                ->distinct()
                ->pluck('idperfil')
                ->all();
            $perfiles = array_values(array_unique(array_merge($perfiles, array_map('intval', $extra))));
        }

        foreach ($perfiles as $idPerfil) {
            $ya = DB::table('tblperfil_acciones')
                ->where('idperfil', $idPerfil)
                ->where('idaccion', $accionId)
                ->exists();
            if (!$ya) {
                DB::table('tblperfil_acciones')->insert([
                    'idperfil' => $idPerfil,
                    'idaccion' => $accionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by' => 'migration',
                ]);
            }
        }

        if ($copiarDesdeHermana && $hermanaId && Schema::hasTable('tblusuario_acciones')) {
            $usuarios = DB::table('tblusuario_acciones')
                ->where('idacciones', $hermanaId)
                ->distinct()
                ->pluck('idusuario')
                ->all();
            foreach ($usuarios as $idUsuario) {
                $ya = DB::table('tblusuario_acciones')
                    ->where('idusuario', $idUsuario)
                    ->where('idacciones', $accionId)
                    ->exists();
                if (!$ya) {
                    $row = [
                        'idusuario' => (int) $idUsuario,
                        'idacciones' => $accionId,
                    ];
                    if (Schema::hasColumn('tblusuario_acciones', 'created_at')) {
                        $row['created_at'] = $now;
                        $row['updated_at'] = $now;
                    }
                    if (Schema::hasColumn('tblusuario_acciones', 'created_by')) {
                        $row['created_by'] = 'migration';
                    }
                    DB::table('tblusuario_acciones')->insert($row);
                }
            }
        }
    }
};
