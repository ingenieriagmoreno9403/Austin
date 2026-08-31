<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Usuario de prueba para la etapa de materiales (Auxiliar de Almacén).
     * Contraseña: Prueba1234
     *   - almacen.prod@iohisa.test -> Auxiliar de Almacén (puesto 39)
     */
    public function up(): void
    {
        $now = now();
        $accVerProduccion = 221; // ver_produccion (incluye ingresar materiales)

        $perfilId = DB::table('tblperfiles')->where('nombre', 'Aux. Almacen')->value('id');
        if (!$perfilId) {
            $perfilId = (int) DB::table('tblperfiles')->insertGetId([
                'nombre' => 'Aux. Almacen',
                'descripcion' => 'Prod. materiales',
                'created_by' => 'master',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!DB::table('tblperfil_acciones')->where('idperfil', $perfilId)->where('idaccion', $accVerProduccion)->exists()) {
            DB::table('tblperfil_acciones')->insert([
                'idperfil' => $perfilId,
                'idaccion' => $accVerProduccion,
                'created_by' => 'master',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $correo = 'almacen.prod@iohisa.test';
        $empId = DB::table('tblempleados')->where('correo', $correo)->value('id');
        if ($empId) {
            DB::table('tblempleados')->where('id', $empId)->update(['idpuesto' => 39]);
        } else {
            $empId = (int) DB::table('tblempleados')->insertGetId([
                'primer_nombre' => 'ALMACEN',
                'apellido_paterno' => 'PRUEBA',
                'apellido_materno' => 'PRUEBA',
                'estado' => 'A',
                'idpuesto' => 39,
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

        $userId = DB::table('users')->where('email', $correo)->value('id');
        if (!$userId) {
            $userId = (int) DB::table('users')->insertGetId([
                'estado_user' => 'A',
                'name' => 'Almacen Prueba',
                'email' => $correo,
                'tipo' => 'empleado',
                'id_tipo' => $empId,
                'idempleado' => $empId,
                'password' => Hash::make('Prueba1234'),
                'created_by' => 'master',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('users')->where('id', $userId)->update([
                'estado_user' => 'A',
                'id_tipo' => $empId,
                'idempleado' => $empId,
                'updated_at' => $now,
            ]);
        }

        if (!DB::table('tblusuario_perfiles')->where('id_usuario', $userId)->where('id_perfil', $perfilId)->exists()) {
            DB::table('tblusuario_perfiles')->insert([
                'id_usuario' => $userId,
                'id_perfil' => $perfilId,
                'created_by' => 'master',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $correo = 'almacen.prod@iohisa.test';
        $userId = DB::table('users')->where('email', $correo)->value('id');
        if ($userId) {
            DB::table('tblusuario_perfiles')->where('id_usuario', $userId)->delete();
            DB::table('users')->where('id', $userId)->delete();
        }
        DB::table('tblempleados')->where('correo', $correo)->delete();

        $perfilId = DB::table('tblperfiles')->where('nombre', 'Aux. Almacen')->value('id');
        if ($perfilId) {
            DB::table('tblperfil_acciones')->where('idperfil', $perfilId)->delete();
            DB::table('tblperfiles')->where('id', $perfilId)->delete();
        }
    }
};
