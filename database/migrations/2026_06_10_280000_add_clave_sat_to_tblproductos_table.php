<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tblproductos', 'clave_sat')) {
            Schema::table('tblproductos', function (Blueprint $table) {
                $table->string('clave_sat', 20)->nullable()->after('codigo_barras');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tblproductos', 'clave_sat')) {
            Schema::table('tblproductos', function (Blueprint $table) {
                $table->dropColumn('clave_sat');
            });
        }
    }
};
