<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo ligero de productos por empresa+cliente para Asignaciones PV.
 */
class CreateTblPvProductoClienteCatalogo extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_pv_producto_cliente_catalogo')) {
            return;
        }

        Schema::create('tbl_pv_producto_cliente_catalogo', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 40);
            $table->string('cliente_codigo', 40);
            $table->string('codigo', 80);
            $table->string('nombre', 220)->nullable();
            $table->string('grupo', 80)->nullable();
            $table->decimal('costo', 18, 6)->default(0);
            $table->unsignedSmallInteger('anio')->nullable();
            $table->string('origen', 20)->default('sap');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['empresa', 'cliente_codigo', 'codigo'], 'pv_prod_cli_cat_unica');
            $table->index(['empresa', 'cliente_codigo'], 'pv_prod_cli_cat_emp_cli');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_pv_producto_cliente_catalogo');
    }
}
