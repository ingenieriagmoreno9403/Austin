<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tblvistas')
            ->where('descripcion', 'cotizaciones')
            ->update(['estado' => 0, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('tblvistas')
            ->where('descripcion', 'cotizaciones')
            ->update(['estado' => 1, 'updated_at' => now()]);
    }
};
