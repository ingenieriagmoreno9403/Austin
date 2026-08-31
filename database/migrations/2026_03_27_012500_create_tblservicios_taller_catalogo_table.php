<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTblserviciosTallerCatalogoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblservicios_taller_catalogo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_servicio', 120)->unique();
            $table->string('estado', 1)->default('A');
            $table->timestamps();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
        });

        DB::table('tblservicios_taller_catalogo')->insert([
            ['nombre_servicio' => 'M.O.SUSPENSION.', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_servicio' => 'M.O.NEGATIVO.', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_servicio' => 'M.O.POSITIVO.', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_servicio' => 'CAMBIO DE AMORTIGUADOR', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_servicio' => 'ALINEACION Y BALANCEO', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblservicios_taller_catalogo');
    }
}

