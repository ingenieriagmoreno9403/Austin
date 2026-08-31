<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTbltiposVehiculosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbltipos_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_vehiculo', 80)->unique();
            $table->string('estado', 1)->default('A');
            $table->timestamps();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
        });

        DB::table('tbltipos_vehiculos')->insert([
            ['tipo_vehiculo' => 'JETTA AX', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['tipo_vehiculo' => 'VENTO', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['tipo_vehiculo' => 'TIGUAN', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['tipo_vehiculo' => 'POLO', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['tipo_vehiculo' => 'PASSAT', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbltipos_vehiculos');
    }
}

