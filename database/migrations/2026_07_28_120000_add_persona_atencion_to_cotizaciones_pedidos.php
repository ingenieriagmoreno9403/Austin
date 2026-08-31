<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_cotizaciones') && !Schema::hasColumn('tbl_cotizaciones', 'persona_atencion')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->string('persona_atencion', 255)->nullable()->after('cliente_id');
            });
        }

        if (Schema::hasTable('tbl_pedidos') && !Schema::hasColumn('tbl_pedidos', 'persona_atencion')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->string('persona_atencion', 255)->nullable()->after('cliente_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_cotizaciones') && Schema::hasColumn('tbl_cotizaciones', 'persona_atencion')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->dropColumn('persona_atencion');
            });
        }

        if (Schema::hasTable('tbl_pedidos') && Schema::hasColumn('tbl_pedidos', 'persona_atencion')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->dropColumn('persona_atencion');
            });
        }
    }
};
