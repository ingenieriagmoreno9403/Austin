<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblnominas_pagodet')) {
            return;
        }

        Schema::table('tblnominas_pagodet', function (Blueprint $table) {
            if (!Schema::hasColumn('tblnominas_pagodet', 'entrega')) {
                $table->tinyInteger('entrega')->default(0)->after('total_apagar');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tblnominas_pagodet')) {
            return;
        }

        Schema::table('tblnominas_pagodet', function (Blueprint $table) {
            if (Schema::hasColumn('tblnominas_pagodet', 'entrega')) {
                $table->dropColumn('entrega');
            }
        });
    }
};
