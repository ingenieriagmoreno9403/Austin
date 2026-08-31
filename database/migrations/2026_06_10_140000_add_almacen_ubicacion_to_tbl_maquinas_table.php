<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_maquinas', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_maquinas', 'id_almacen')) {
                $table->unsignedBigInteger('id_almacen')->nullable()->after('ubicacion');
            }
            if (!Schema::hasColumn('tbl_maquinas', 'id_ubicacion')) {
                $table->unsignedBigInteger('id_ubicacion')->nullable()->after('id_almacen');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_maquinas', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_maquinas', 'id_ubicacion')) {
                $table->dropColumn('id_ubicacion');
            }
            if (Schema::hasColumn('tbl_maquinas', 'id_almacen')) {
                $table->dropColumn('id_almacen');
            }
        });
    }
};
