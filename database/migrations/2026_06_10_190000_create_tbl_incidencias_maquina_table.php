<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_incidencias_maquina')) {
            return;
        }

        Schema::create('tbl_incidencias_maquina', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('maquina_id')->nullable();
            $table->unsignedInteger('inspeccion_id')->nullable();
            $table->date('fecha')->nullable();
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->enum('prioridad', ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])->default('MEDIA');
            $table->unsignedInteger('responsable_id')->nullable();
            $table->date('fecha_compromiso')->nullable();
            $table->date('fecha_cierre')->nullable();
            $table->enum('estatus', ['ABIERTA', 'EN_PROCESO', 'CERRADA'])->default('ABIERTA');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_incidencias_maquina');
    }
};
