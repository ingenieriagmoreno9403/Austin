<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_reprocesos')) {
            return;
        }

        Schema::table('tbl_reprocesos', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_reprocesos', 'maquina_peletizado_id')) {
                $table->unsignedBigInteger('maquina_peletizado_id')->nullable()->after('maquina_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_reprocesos')) {
            return;
        }

        Schema::table('tbl_reprocesos', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_reprocesos', 'maquina_peletizado_id')) {
                $table->dropColumn('maquina_peletizado_id');
            }
        });
    }
};
