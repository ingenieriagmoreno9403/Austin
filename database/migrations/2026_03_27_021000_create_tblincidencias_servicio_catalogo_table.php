<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTblincidenciasServicioCatalogoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblincidencias_servicio_catalogo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_incidencia', 180)->unique();
            $table->string('estado', 1)->default('A');
            $table->timestamps();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
        });

        DB::table('tblincidencias_servicio_catalogo')->insert([
            ['nombre_incidencia' => 'COMUNICACION INSUFICIENTE ENTRE CLIENTE Y ASESOR', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_incidencia' => 'NO SE CONFIRMO LA FALLA', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_incidencia' => 'FALTA DE CONOCIMIENTO DEL TECNICO', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_incidencia' => 'FALTA DE HABILIDAD DEL TECNICO', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_incidencia' => 'FALTA DE PARTES Y/O MATERIAL', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_incidencia' => 'FALTA DE INSPECCION FINAL DE CALIDAD', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_incidencia' => 'OTROS', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_incidencia' => 'DESAJUSTES Y/O FALTA DE HERRAMIENTAS/EQUIPOS', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_incidencia' => 'EXPLICACION INADECUADA DEL ASESOR', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_incidencia' => 'INFORMACION DE FABRICA NO ES SUFICIENTE', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_incidencia' => 'NO SE CONFIRMO EL TRABAJO REALIZADO', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblincidencias_servicio_catalogo');
    }
}

