<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('tblconceptos_nomina')) {
            return;
        }

        $exists = DB::table('tblconceptos_nomina')
            ->where('nombre', 'visor_fiscal')
            ->exists();

        if (!$exists) {
            DB::table('tblconceptos_nomina')->insert([
                'nombre' => 'visor_fiscal',
                'descripcion' => 'Visor Fiscal',
                'valor' => 0,
            ]);
        }
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('tblconceptos_nomina')) {
            return;
        }

        DB::table('tblconceptos_nomina')
            ->where('nombre', 'visor_fiscal')
            ->delete();
    }
};
