<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tblfacturacionproductos', 'forma_pago')) {
            Schema::table('tblfacturacionproductos', function (Blueprint $table) {
                $table->string('forma_pago', 5)->nullable()->after('metodo_pago');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tblfacturacionproductos', 'forma_pago')) {
            Schema::table('tblfacturacionproductos', function (Blueprint $table) {
                $table->dropColumn('forma_pago');
            });
        }
    }
};
