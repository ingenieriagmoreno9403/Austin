<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DEPT_ALMACENES = 13;
    private const DEPT_INVENTARIOS = 14;
    private const DESCRIPCION = 'almacen/cargas';

    public function up(): void
    {
        $now = now();

        // Cargas vive en Gestion de Inventarios (donde ya está el resto de almacén).
        DB::table('tblvistas')
            ->where('descripcion', self::DESCRIPCION)
            ->update([
                'nombre' => 'Cargas',
                'iddepartamento' => self::DEPT_INVENTARIOS,
                'orden' => 7,
                'estado' => 1,
                'updated_at' => $now,
            ]);

        // El menú "Almacenes" (dept 13) es redundante: ocultar cualquier vista que quede ahí.
        DB::table('tblvistas')
            ->where('iddepartamento', self::DEPT_ALMACENES)
            ->where('descripcion', '!=', self::DESCRIPCION)
            ->update([
                'estado' => 0,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        DB::table('tblvistas')
            ->where('descripcion', self::DESCRIPCION)
            ->update([
                'iddepartamento' => self::DEPT_ALMACENES,
                'orden' => 1,
                'updated_at' => now(),
            ]);
    }
};
