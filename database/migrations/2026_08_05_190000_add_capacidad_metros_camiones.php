<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_camiones') && !Schema::hasColumn('tbl_camiones', 'capacidad_metros')) {
            Schema::table('tbl_camiones', function (Blueprint $table) {
                $table->decimal('capacidad_metros', 12, 2)->nullable()->after('capacidad_ton');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_camiones') && Schema::hasColumn('tbl_camiones', 'capacidad_metros')) {
            Schema::table('tbl_camiones', function (Blueprint $table) {
                $table->dropColumn('capacidad_metros');
            });
        }
    }
};
