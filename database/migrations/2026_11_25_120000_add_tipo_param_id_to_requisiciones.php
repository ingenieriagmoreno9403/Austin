<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_requisiciones') && ! Schema::hasColumn('tbl_requisiciones', 'tipo_param_id')) {
            Schema::table('tbl_requisiciones', function (Blueprint $table) {
                $table->unsignedBigInteger('tipo_param_id')->nullable()->index()->after('departamento');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_requisiciones') && Schema::hasColumn('tbl_requisiciones', 'tipo_param_id')) {
            Schema::table('tbl_requisiciones', function (Blueprint $table) {
                $table->dropColumn('tipo_param_id');
            });
        }
    }
};
