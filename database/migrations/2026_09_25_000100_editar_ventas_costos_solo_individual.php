<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $accionId = DB::table('tblacciones')->where('nombre_accion', 'editar_ventas_costos')->value('id');
        if (! $accionId) {
            return;
        }
        // Solo asignación individual (acción puntual), no por perfil.
        DB::table('tblperfil_acciones')->where('idaccion', $accionId)->delete();
    }

    public function down(): void
    {
        // No se restaura en perfiles a propósito.
    }
};
