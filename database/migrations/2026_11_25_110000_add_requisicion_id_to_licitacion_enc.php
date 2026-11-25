<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbllicitacion_enc') && ! Schema::hasColumn('tbllicitacion_enc', 'requisicion_id')) {
            Schema::table('tbllicitacion_enc', function (Blueprint $table) {
                $table->unsignedBigInteger('requisicion_id')->nullable()->index()->after('tipo_param_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbllicitacion_enc') && Schema::hasColumn('tbllicitacion_enc', 'requisicion_id')) {
            Schema::table('tbllicitacion_enc', function (Blueprint $table) {
                $table->dropColumn('requisicion_id');
            });
        }
    }
};
