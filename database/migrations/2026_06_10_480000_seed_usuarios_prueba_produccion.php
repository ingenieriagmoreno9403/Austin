<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Crea usuarios de prueba para probar el flujo de producción con permisos
     * acotados. El usuario "master" conserva su acceso total (no se modifica).
     *
     * Credenciales de prueba (contraseña común): Prueba1234
     *   - operador.prod@iohisa.test   -> Operador de Producción (puesto 43)
     *   - auditor.calidad@iohisa.test -> Auditor de Calidad     (puesto 44)
     */
    public function up(): void
    {
        $now = now();

        // Acciones del módulo de producción.
        $accVerProduccion = 221;   // ver_produccion
        $accVerRecetas = 223;      // ver_recetas_produccion
        $accCancelar = 225;        // cancelar_orden_produccion (solo master)

        // Asegurar que el master (perfil 1) tenga las acciones de producción.
        foreach ([$accVerProduccion, $accVerRecetas, $accCancelar] as $acc) {
            $existe = DB::table('tblperfil_acciones')
                ->where('idperfil', 1)->where('idaccion', $acc)->exists();
            if (!$existe) {
                DB::table('tblperfil_acciones')->insert([
                    'idperfil' => 1,
                    'idaccion' => $acc,
                    'created_by' => 'master',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 1) Perfiles acotados (uno por rol de producción).
        $perfilOperadorId = $this->perfilId('Operador Prod.', 'Prod. operacion', $now);
        $perfilAuditorId = $this->perfilId('Auditor Calidad', 'Prod. calidad', $now);

        // Acciones por perfil (sin cancelar: eso queda solo para el master).
        $this->asignarAccion($perfilOperadorId, $accVerProduccion, $now);
        $this->asignarAccion($perfilOperadorId, $accVerRecetas, $now);
        $this->asignarAccion($perfilAuditorId, $accVerProduccion, $now);

        // 2) Empleados de prueba ligados a su puesto de RH.
        $empOperadorId = $this->empleadoId('OPERADOR', 'PRUEBA', 43, 'operador.prod@iohisa.test', $now);
        $empAuditorId = $this->empleadoId('AUDITOR', 'PRUEBA', 44, 'auditor.calidad@iohisa.test', $now);

        // 3) Usuarios de prueba + enlace a su perfil.
        $this->usuario('Operador Producción', 'operador.prod@iohisa.test', $empOperadorId, $perfilOperadorId, $now);
        $this->usuario('Auditor Calidad', 'auditor.calidad@iohisa.test', $empAuditorId, $perfilAuditorId, $now);
    }

    public function down(): void
    {
        $emails = ['operador.prod@iohisa.test', 'auditor.calidad@iohisa.test'];

        $userIds = DB::table('users')->whereIn('email', $emails)->pluck('id')->all();
        if (!empty($userIds)) {
            DB::table('tblusuario_perfiles')->whereIn('id_usuario', $userIds)->delete();
            DB::table('users')->whereIn('id', $userIds)->delete();
        }

        DB::table('tblempleados')->whereIn('correo', $emails)->delete();

        $perfilIds = DB::table('tblperfiles')
            ->whereIn('nombre', ['Operador Prod.', 'Auditor Calidad'])->pluck('id')->all();
        if (!empty($perfilIds)) {
            DB::table('tblperfil_acciones')->whereIn('idperfil', $perfilIds)->delete();
            DB::table('tblperfiles')->whereIn('id', $perfilIds)->delete();
        }
    }

    private function perfilId(string $nombre, string $descripcion, $now): int
    {
        $id = DB::table('tblperfiles')->where('nombre', $nombre)->value('id');
        if ($id) {
            return (int) $id;
        }

        return (int) DB::table('tblperfiles')->insertGetId([
            'nombre' => $nombre,
            'descripcion' => substr($descripcion, 0, 20),
            'created_by' => 'master',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function asignarAccion(int $perfilId, int $accionId, $now): void
    {
        $existe = DB::table('tblperfil_acciones')
            ->where('idperfil', $perfilId)->where('idaccion', $accionId)->exists();
        if (!$existe) {
            DB::table('tblperfil_acciones')->insert([
                'idperfil' => $perfilId,
                'idaccion' => $accionId,
                'created_by' => 'master',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function empleadoId(string $nombre, string $apellido, int $idpuesto, string $correo, $now): int
    {
        $id = DB::table('tblempleados')->where('correo', $correo)->value('id');
        if ($id) {
            DB::table('tblempleados')->where('id', $id)->update(['idpuesto' => $idpuesto]);
            return (int) $id;
        }

        return (int) DB::table('tblempleados')->insertGetId([
            'primer_nombre' => $nombre,
            'apellido_paterno' => $apellido,
            'apellido_materno' => 'PRUEBA',
            'estado' => 'A',
            'idpuesto' => $idpuesto,
            'idsucursal' => 12,
            'idciudad' => 1,
            'idbanco' => 5,
            'calle' => 'N/D',
            'colonia' => 'N/D',
            'numero_exterior' => 'S/N',
            'codigo_postal' => '00000',
            'sexo' => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'correo' => $correo,
            'tipo_contratacion' => 'INDEFINIDA',
            'nacionalidad' => 'MEXICANA',
            'fecha_ingreso' => $now->toDateString(),
            'created_by' => 'master',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function usuario(string $name, string $email, int $idempleado, int $perfilId, $now): void
    {
        $userId = DB::table('users')->where('email', $email)->value('id');

        if (!$userId) {
            $userId = (int) DB::table('users')->insertGetId([
                'estado_user' => 'A',
                'name' => $name,
                'email' => $email,
                'tipo' => 'empleado',
                'id_tipo' => $idempleado,
                'idempleado' => $idempleado,
                'password' => Hash::make('Prueba1234'),
                'created_by' => 'master',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('users')->where('id', $userId)->update([
                'estado_user' => 'A',
                'id_tipo' => $idempleado,
                'idempleado' => $idempleado,
                'updated_at' => $now,
            ]);
        }

        $existe = DB::table('tblusuario_perfiles')
            ->where('id_usuario', $userId)->where('id_perfil', $perfilId)->exists();
        if (!$existe) {
            DB::table('tblusuario_perfiles')->insert([
                'id_usuario' => $userId,
                'id_perfil' => $perfilId,
                'created_by' => 'master',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
