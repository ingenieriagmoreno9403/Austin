<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_recetas_detalle')) {
            return;
        }

        Schema::create('tbl_recetas_detalle', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('receta_id');
            $table->integer('producto_mp_id');
            $table->decimal('cantidad', 12, 4)->default(0);
            $table->integer('unidad_id')->nullable();
            $table->decimal('porcentaje', 8, 4)->nullable();

            $table->foreign('receta_id')->references('id')->on('tbl_recetas')->cascadeOnDelete();
            $table->index('producto_mp_id', 'receta_det_mp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_recetas_detalle');
    }
};
