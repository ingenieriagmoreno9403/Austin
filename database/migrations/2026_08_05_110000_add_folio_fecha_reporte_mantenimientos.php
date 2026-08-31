<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_mantenimientos')) {
            return;
        }

        Schema::table('tbl_mantenimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_mantenimientos', 'folio')) {
                $table->string('folio', 40)->nullable()->unique();
            }
            if (!Schema::hasColumn('tbl_mantenimientos', 'fecha_reporte')) {
                $table->date('fecha_reporte')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_mantenimientos')) {
            return;
        }

        Schema::table('tbl_mantenimientos', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_mantenimientos', 'fecha_reporte')) {
                $table->dropColumn('fecha_reporte');
            }
            if (Schema::hasColumn('tbl_mantenimientos', 'folio')) {
                $table->dropColumn('folio');
            }
        });
    }
};
