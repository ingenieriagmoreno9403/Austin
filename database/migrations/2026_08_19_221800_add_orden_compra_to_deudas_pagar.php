<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrdenCompraToDeudasPagar extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('deudas_pagar')) {
            return;
        }

        Schema::table('deudas_pagar', function (Blueprint $table) {
            if (! Schema::hasColumn('deudas_pagar', 'orden_compra_id')) {
                $table->unsignedBigInteger('orden_compra_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('deudas_pagar', 'proveedor_id')) {
                $table->unsignedBigInteger('proveedor_id')->nullable()->after('orden_compra_id');
            }
            if (! Schema::hasColumn('deudas_pagar', 'tipo_moneda')) {
                $table->string('tipo_moneda', 10)->nullable()->after('monto_pagado');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('deudas_pagar')) {
            return;
        }

        Schema::table('deudas_pagar', function (Blueprint $table) {
            foreach (['orden_compra_id', 'proveedor_id', 'tipo_moneda'] as $col) {
                if (Schema::hasColumn('deudas_pagar', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
