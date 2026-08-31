<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tblnomina_asistencias')) {
            return;
        }

        Schema::create('tblnomina_asistencias', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('idpagonomina');
            $table->unsignedBigInteger('idempleado');
            $table->date('fecha');
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            $table->timestamps();

            $table->unique(['idpagonomina', 'idempleado', 'fecha'], 'uniq_nomina_asistencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblnomina_asistencias');
    }
};
