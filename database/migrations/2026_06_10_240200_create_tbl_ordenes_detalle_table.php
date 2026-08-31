<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_ordenes_detalle')) {
            return;
        }

        Schema::create('tbl_ordenes_detalle', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('orden_id')->nullable();
            $table->integer('producto_id')->nullable();
            $table->string('diametro', 50)->nullable();
            $table->string('rd', 50)->nullable();
            $table->decimal('psi', 10, 2)->nullable();
            $table->decimal('espesor', 10, 3)->nullable();
            $table->decimal('kg_metro', 12, 4)->nullable();
            $table->decimal('metros_producidos', 12, 3)->default(0);
            $table->decimal('kg_teorico', 12, 3)->nullable();
            $table->decimal('kg_real', 12, 3)->nullable();
            $table->decimal('kg_merma', 12, 3)->nullable();
            $table->decimal('porcentaje_merma', 8, 3)->nullable();
            $table->integer('piezas_producidas')->default(0);
            $table->decimal('largo_tramo', 10, 3)->nullable();
            $table->text('observaciones')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('orden_id')->references('id')->on('tbl_ordenes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_ordenes_detalle');
    }
};
