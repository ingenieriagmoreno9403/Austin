<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_ordenes')) {
            return;
        }

        Schema::create('tbl_ordenes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('folio', 50)->nullable();
            $table->date('fecha')->nullable();
            $table->unsignedInteger('pedido_id')->nullable();
            $table->integer('maquina_id')->nullable();
            $table->unsignedInteger('turno_id')->nullable();
            $table->unsignedBigInteger('operador_id')->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->enum('estatus', ['ABIERTA', 'EN_PROCESO', 'CERRADA', 'CANCELADA'])->default('ABIERTA');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('pedido_id')->references('id')->on('tbl_pedidos')->nullOnDelete();
            $table->foreign('maquina_id')->references('id')->on('tbl_maquinas')->nullOnDelete();
            $table->foreign('turno_id')->references('id')->on('tbl_turnos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_ordenes');
    }
};
