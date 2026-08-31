<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblCotizacionesDetalleTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_cotizaciones_detalle')) {
            return;
        }

        Schema::create('tbl_cotizaciones_detalle', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cotizacion_id')->nullable();
            $table->unsignedInteger('producto_id')->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->decimal('cantidad', 12, 2)->nullable();
            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('importe', 12, 2)->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_cotizaciones_detalle');
    }
}
