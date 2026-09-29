<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo ligero de clientes (CardCode/CardName) por empresa para Asignaciones PV.
 * Evita recorrer todas las líneas de /ventas solo para armar el listado.
 */
class CreateTblPvClienteCatalogo extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_pv_cliente_catalogo')) {
            return;
        }

        Schema::create('tbl_pv_cliente_catalogo', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 40);
            $table->string('codigo', 40);
            $table->string('nombre', 180)->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->string('origen', 20)->default('sap');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['empresa', 'codigo'], 'pv_cli_cat_unica');
            $table->index(['empresa', 'anio'], 'pv_cli_cat_emp_anio');
            $table->index('nombre', 'pv_cli_cat_nombre');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_pv_cliente_catalogo');
    }
}
