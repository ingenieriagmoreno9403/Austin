<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProcesoEmpleadoToTblordenesServicioDetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tblordenes_servicio_det', function (Blueprint $table) {
            $table->string('proceso_estado', 30)->default('Pendiente')->after('t_real');
            $table->unsignedBigInteger('id_empleado_asignado')->nullable()->after('proceso_estado');
            $table->foreign('id_empleado_asignado')->references('id')->on('tblempleados');
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
            $table->dropForeign(['id_empleado_asignado']);
            $table->dropColumn(['proceso_estado', 'id_empleado_asignado']);
        });
    }
}

