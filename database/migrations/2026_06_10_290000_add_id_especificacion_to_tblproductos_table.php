<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tblproductos', 'id_especificacion')) {
            Schema::table('tblproductos', function (Blueprint $table) {
                $table->unsignedInteger('id_especificacion')->nullable()->after('id_unidad_medida');
                $table->index('id_especificacion', 'tblproductos_id_especificacion_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tblproductos', 'id_especificacion')) {
            Schema::table('tblproductos', function (Blueprint $table) {
                $table->dropIndex('tblproductos_id_especificacion_idx');
                $table->dropColumn('id_especificacion');
            });
        }
    }
};
