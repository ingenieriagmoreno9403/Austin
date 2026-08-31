<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_programacion_mantenimiento')) {
            return;
        }

        Schema::create('tbl_programacion_mantenimiento', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('maquina_id')->nullable();
            $table->string('actividad', 200);
            $table->enum('frecuencia', ['SEMANAL', 'QUINCENAL', 'MENSUAL', 'TRIMESTRAL', 'SEMESTRAL', 'ANUAL']);
            $table->date('ultima_ejecucion')->nullable();
            $table->date('proxima_ejecucion')->nullable();
            $table->unsignedInteger('responsable_id')->nullable();
            $table->tinyInteger('activo')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_programacion_mantenimiento');
    }
};
