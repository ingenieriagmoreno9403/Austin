<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_maquinas')) {
            return;
        }

        Schema::table('tbl_maquinas', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_maquinas', 'para_reproceso')) {
                $table->boolean('para_reproceso')->default(false)->after('estatus');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_maquinas')) {
            return;
        }

        Schema::table('tbl_maquinas', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_maquinas', 'para_reproceso')) {
                $table->dropColumn('para_reproceso');
            }
        });
    }
};
