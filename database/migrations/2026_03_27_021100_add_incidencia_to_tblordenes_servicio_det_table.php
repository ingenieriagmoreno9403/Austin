<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIncidenciaToTblordenesServicioDetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblordenes_servicio_det', function (Blueprint $table) {
            $table->unsignedBigInteger('id_incidencia')->nullable()->after('id_empleado_asignado');
            $table->foreign('id_incidencia')->references('id')->on('tblincidencias_servicio_catalogo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblordenes_servicio_det', function (Blueprint $table) {
            $table->dropForeign(['id_incidencia']);
            $table->dropColumn('id_incidencia');
        });
    }
}

