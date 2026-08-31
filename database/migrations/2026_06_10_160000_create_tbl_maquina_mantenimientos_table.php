<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_maquina_mantenimientos')) {
            return;
        }

        Schema::create('tbl_maquina_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_maquina');
            $table->enum('tipo', ['P', 'C', 'R'])->default('P');
            $table->date('fecha_mantenimiento');
            $table->string('tecnico_responsable', 150)->nullable();
            $table->text('trabajo_realizado');
            $table->text('observaciones')->nullable();
            $table->enum('estatus', ['P', 'C', 'V'])->default('C');
            $table->string('verificado_por', 150)->nullable();
            $table->dateTime('fecha_verificacion')->nullable();
            $table->string('registrado_por', 150)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_maquina_mantenimientos');
    }
};
