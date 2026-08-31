<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblordenesServicioEncTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblordenes_servicio_enc', function (Blueprint $table) {
            $table->id();
            $table->string('no_orden', 30)->unique();
            $table->string('tipo_vehiculo', 80);
            $table->string('codigo_torre', 20);
            $table->string('estado', 30)->default('Proceso');
            $table->dateTime('fecha_hora');
            $table->dateTime('fecha_promesa')->nullable();
            $table->timestamps();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblordenes_servicio_enc');
    }
}

