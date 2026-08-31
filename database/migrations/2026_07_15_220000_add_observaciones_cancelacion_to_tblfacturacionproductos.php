<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tblfacturacionproductos', 'observaciones_cancelacion')) {
            Schema::table('tblfacturacionproductos', function (Blueprint $table) {
                $table->string('observaciones_cancelacion', 500)->nullable()->after('dia_cancelacion');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tblfacturacionproductos', 'observaciones_cancelacion')) {
            Schema::table('tblfacturacionproductos', function (Blueprint $table) {
                $table->dropColumn('observaciones_cancelacion');
            });
        }
    }
};
