<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblCotizacionesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_cotizaciones')) {
            return;
        }

        Schema::create('tbl_cotizaciones', function (Blueprint $table) {
            $table->increments('id');
            $table->string('folio', 50)->nullable();
            $table->unsignedInteger('cliente_id')->nullable();
            $table->unsignedInteger('usuario_id')->nullable();
            $table->dateTime('fecha')->useCurrent();
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('estatus', ['BORRADOR', 'ENVIADA', 'ACEPTADA', 'RECHAZADA', 'VENCIDA', 'CONVERTIDA'])->default('BORRADOR');
            $table->text('observaciones')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_cotizaciones');
    }
}
