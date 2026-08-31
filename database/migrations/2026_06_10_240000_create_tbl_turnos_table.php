<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_turnos')) {
            return;
        }

        Schema::create('tbl_turnos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->decimal('horas_turno', 6, 2)->nullable();
            $table->enum('estatus', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
        });

        DB::table('tbl_turnos')->insert([
            [
                'nombre' => 'Matutino',
                'hora_inicio' => '06:00:00',
                'hora_fin' => '14:00:00',
                'horas_turno' => 8,
                'estatus' => 'ACTIVO',
                'created_at' => now(),
            ],
            [
                'nombre' => 'Vespertino',
                'hora_inicio' => '14:00:00',
                'hora_fin' => '22:00:00',
                'horas_turno' => 8,
                'estatus' => 'ACTIVO',
                'created_at' => now(),
            ],
            [
                'nombre' => 'Nocturno',
                'hora_inicio' => '22:00:00',
                'hora_fin' => '06:00:00',
                'horas_turno' => 8,
                'estatus' => 'ACTIVO',
                'created_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_turnos');
    }
};
