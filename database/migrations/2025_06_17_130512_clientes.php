<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Clientes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblclientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre',100);
            $table->string('alias',50);
            $table->string('estado',1);
            $table->string('tipo',10);
            $table->string('razon_social',100);
            $table->string('rfc',13);
            $table->string('telefono',10);
            $table->string('correo_electronico',100);
            $table->unsignedBigInteger('id_ciudad'); 
            $table->string('colonia',50);
            $table->string('calle',50);
            $table->string('numero_int',10)->null();
            $table->string('numero_ext',10);
            $table->string('cp',10);
            $table->timestamps();
            $table->string('created_by',100)->null();
            $table->string('updated_by',100)->null();

            $table->foreign('id_ciudad')->references('id')->on('tblciudades');
        }); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblclientes'); 
    }
}
