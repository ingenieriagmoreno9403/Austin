<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_ordenes')) {
            return;
        }

        Schema::table('tbl_ordenes', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_ordenes', 'archivo_op_ruta')) {
                $table->string('archivo_op_ruta', 500)->nullable()->after('orden_materiales_nombre');
            }
            if (!Schema::hasColumn('tbl_ordenes', 'archivo_op_nombre')) {
                $table->string('archivo_op_nombre', 255)->nullable()->after('archivo_op_ruta');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_ordenes')) {
            return;
        }

        Schema::table('tbl_ordenes', function (Blueprint $table) {
            foreach (['archivo_op_nombre', 'archivo_op_ruta'] as $col) {
                if (Schema::hasColumn('tbl_ordenes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
