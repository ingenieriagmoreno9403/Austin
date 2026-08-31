<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deudas_cobrar') && !Schema::hasColumn('deudas_cobrar', 'id_factura')) {
            Schema::table('deudas_cobrar', function (Blueprint $table) {
                $table->unsignedBigInteger('id_factura')->nullable()->after('id_venta');
                $table->index('id_factura');
            });
        }

        if (Schema::hasTable('ingresos') && !Schema::hasColumn('ingresos', 'id_factura')) {
            Schema::table('ingresos', function (Blueprint $table) {
                $table->unsignedBigInteger('id_factura')->nullable()->after('id_venta');
                $table->index('id_factura');
            });
        }

        if (Schema::hasTable('tblfacturacionproductos')) {
            DB::table('tblfacturacionproductos')
                ->where(function ($q) {
                    $q->whereNull('cancelada')->orWhere('cancelada', '');
                })
                ->update(['cancelada' => 'A']);

            $facturasActivas = DB::table('tblfacturacionproductos')
                ->where('estado', 'Activo')
                ->whereNotNull('Uuid')
                ->where('Uuid', '!=', '')
                ->get(['id', 'metodo_pago']);

            foreach ($facturasActivas as $factura) {
                $nuevoEstado = strtoupper(trim($factura->metodo_pago ?? '')) === 'PPD'
                    ? 'Pendiente'
                    : 'Pagado';

                DB::table('tblfacturacionproductos')
                    ->where('id', $factura->id)
                    ->update(['estado' => $nuevoEstado]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deudas_cobrar') && Schema::hasColumn('deudas_cobrar', 'id_factura')) {
            Schema::table('deudas_cobrar', function (Blueprint $table) {
                $table->dropIndex(['id_factura']);
                $table->dropColumn('id_factura');
            });
        }

        if (Schema::hasTable('ingresos') && Schema::hasColumn('ingresos', 'id_factura')) {
            Schema::table('ingresos', function (Blueprint $table) {
                $table->dropIndex(['id_factura']);
                $table->dropColumn('id_factura');
            });
        }
    }
};
