<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tblfacturacionproductos', 'metod_pago')
            && !Schema::hasColumn('tblfacturacionproductos', 'metodo_pago')) {
            DB::statement('ALTER TABLE tblfacturacionproductos CHANGE metod_pago metodo_pago VARCHAR(5) NULL');
        }

        if (!Schema::hasColumn('tblfacturacionproductos', 'metodo_pago')) {
            Schema::table('tblfacturacionproductos', function ($table) {
                $table->string('metodo_pago', 5)->nullable()->after('referencia_factura');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tblfacturacionproductos', 'metodo_pago')
            && !Schema::hasColumn('tblfacturacionproductos', 'metod_pago')) {
            DB::statement('ALTER TABLE tblfacturacionproductos CHANGE metodo_pago metod_pago VARCHAR(5) NULL');
        }
    }
};
