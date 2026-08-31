<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblordenesServicioFotosTable extends Migration
{
    public function up()
    {
        Schema::create('tblordenes_servicio_fotos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_orden_enc');
            $table->unsignedBigInteger('id_detalle');
            $table->string('foto_path', 255);
            $table->string('foto_url', 255);
            $table->timestamps();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();

            $table->foreign('id_orden_enc')->references('id')->on('tblordenes_servicio_enc')->onDelete('cascade');
            $table->foreign('id_detalle')->references('id')->on('tblordenes_servicio_det')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tblordenes_servicio_fotos');
    }
}

