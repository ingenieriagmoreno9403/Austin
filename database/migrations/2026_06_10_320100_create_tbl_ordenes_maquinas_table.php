<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_ordenes_maquinas')) {
            return;
        }

        Schema::create('tbl_ordenes_maquinas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('orden_id');
            $table->integer('maquina_id')->nullable();
            $table->unsignedInteger('secuencia')->default(1);
            $table->enum('estatus', [
                'CALENTANDO_MAQUINA',
                'EN_PRODUCCION',
                'PAUSADA',
                'REVISION_CALIDAD',
                'EMPACANDO',
                'TERMINADA',
                'CANCELADA',
            ])->default('CALENTANDO_MAQUINA');
            $table->unsignedBigInteger('operador_id')->nullable();
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();

            $table->index('orden_id', 'tbl_ordenes_maquinas_orden_idx');
            $table->index('maquina_id', 'tbl_ordenes_maquinas_maquina_idx');
            $table->index('estatus', 'tbl_ordenes_maquinas_estatus_idx');

            $table->foreign('orden_id')->references('id')->on('tbl_ordenes')->cascadeOnDelete();
            $table->foreign('maquina_id')->references('id')->on('tbl_maquinas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_ordenes_maquinas');
    }
};
