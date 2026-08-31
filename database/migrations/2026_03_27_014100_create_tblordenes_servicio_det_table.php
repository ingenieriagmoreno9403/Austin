<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblordenesServicioDetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblordenes_servicio_det', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_orden_enc');
            $table->integer('no_s');
            $table->string('servicio', 120);
            $table->string('descripcion', 255);
            $table->decimal('t_tab', 8, 2)->default(0);
            $table->decimal('t_real', 8, 2)->default(0);
            $table->timestamps();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();

            $table->foreign('id_orden_enc')->references('id')->on('tblordenes_servicio_enc')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblordenes_servicio_det');
    }
}

