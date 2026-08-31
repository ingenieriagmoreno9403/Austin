<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_inspecciones_detalle')) {
            return;
        }

        Schema::create('tbl_inspecciones_detalle', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('inspeccion_id')->nullable();
            $table->string('concepto', 150);
            $table->enum('resultado', ['OK', 'OBSERVACION', 'CRITICO']);
            $table->text('observaciones')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_inspecciones_detalle');
    }
};
