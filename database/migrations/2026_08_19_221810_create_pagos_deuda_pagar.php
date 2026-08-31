<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagosDeudaPagar extends Migration
{
    public function up()
    {
        if (Schema::hasTable('pagos_deuda_pagar')) {
            return;
        }

        Schema::create('pagos_deuda_pagar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deuda_pagar_id');
            $table->unsignedBigInteger('orden_compra_id')->nullable();
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->string('metodo_pago', 80)->nullable();
            $table->string('referencia', 120)->nullable();
            $table->unsignedBigInteger('cuenta_id')->nullable();
            $table->unsignedBigInteger('egreso_id')->nullable();
            $table->string('notas', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('deuda_pagar_id');
            $table->index('orden_compra_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pagos_deuda_pagar');
    }
}
