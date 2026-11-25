<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbllicitacion_enc') && ! Schema::hasColumn('tbllicitacion_enc', 'condiciones')) {
            Schema::table('tbllicitacion_enc', function (Blueprint $table) {
                $table->mediumText('condiciones')->nullable()->after('descripcion_detalle');
            });
        }

        if (Schema::hasTable('tbllicitacion_proveedor')) {
            Schema::table('tbllicitacion_proveedor', function (Blueprint $table) {
                if (! Schema::hasColumn('tbllicitacion_proveedor', 'condiciones_aceptadas')) {
                    $table->boolean('condiciones_aceptadas')->default(false)->after('estado_provext');
                }
                if (! Schema::hasColumn('tbllicitacion_proveedor', 'condiciones_aceptadas_at')) {
                    $table->timestamp('condiciones_aceptadas_at')->nullable()->after('condiciones_aceptadas');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbllicitacion_enc') && Schema::hasColumn('tbllicitacion_enc', 'condiciones')) {
            Schema::table('tbllicitacion_enc', function (Blueprint $table) {
                $table->dropColumn('condiciones');
            });
        }

        if (Schema::hasTable('tbllicitacion_proveedor')) {
            Schema::table('tbllicitacion_proveedor', function (Blueprint $table) {
                if (Schema::hasColumn('tbllicitacion_proveedor', 'condiciones_aceptadas_at')) {
                    $table->dropColumn('condiciones_aceptadas_at');
                }
                if (Schema::hasColumn('tbllicitacion_proveedor', 'condiciones_aceptadas')) {
                    $table->dropColumn('condiciones_aceptadas');
                }
            });
        }
    }
};
