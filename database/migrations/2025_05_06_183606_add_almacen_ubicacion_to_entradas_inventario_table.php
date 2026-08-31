<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAlmacenUbicacionToEntradasInventarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblentradas_inventario', function (Blueprint $table) {
            $table->unsignedBigInteger('id_almacen')->nullable()->after('observaciones');
            $table->unsignedBigInteger('id_ubicacion')->nullable()->after('id_almacen');
            
            // Añadir claves foráneas
            $table->foreign('id_almacen')->references('id')->on('tblalmacenes')->onDelete('set null');
            $table->foreign('id_ubicacion')->references('id')->on('tblubicaciones')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblentradas_inventario', function (Blueprint $table) {
            $table->dropForeign(['id_almacen']);
            $table->dropForeign(['id_ubicacion']);
            $table->dropColumn(['id_almacen', 'id_ubicacion']);
        });
    }
}
