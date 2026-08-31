<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_refacciones_maquina')) {
            return;
        }

        Schema::create('tbl_refacciones_maquina', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('mantenimiento_id')->nullable();
            $table->unsignedInteger('producto_id')->nullable();
            $table->decimal('cantidad', 12, 3)->nullable();
            $table->decimal('costo_unitario', 14, 2)->nullable();
            $table->decimal('costo_total', 14, 2)->nullable();
            $table->text('observaciones')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_refacciones_maquina');
    }
};
