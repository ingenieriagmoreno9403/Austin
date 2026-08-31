<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTbltorresTallerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbltorres_taller', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_torre', 20)->unique();
            $table->string('nombre_ubicacion', 80)->nullable();
            $table->string('estado', 1)->default('A');
            $table->timestamps();
            $table->string('created_by', 100)->nullable();
            $table->string('updated_by', 100)->nullable();
        });

        DB::table('tbltorres_taller')->insert([
            ['codigo_torre' => '01BCA', 'nombre_ubicacion' => 'TORRE 01', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['codigo_torre' => '02BCA', 'nombre_ubicacion' => 'TORRE 02', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['codigo_torre' => '03BCA', 'nombre_ubicacion' => 'TORRE 03', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['codigo_torre' => '04ZED', 'nombre_ubicacion' => 'AREA ZED', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
            ['codigo_torre' => '05LMN', 'nombre_ubicacion' => 'LINEA LMN', 'estado' => 'A', 'created_by' => 'system', 'updated_by' => 'system', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbltorres_taller');
    }
}

