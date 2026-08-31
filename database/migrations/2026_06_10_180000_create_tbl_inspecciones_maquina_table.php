<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_inspecciones_maquina')) {
            return;
        }

        Schema::create('tbl_inspecciones_maquina', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('maquina_id')->nullable();
            $table->date('fecha_inspeccion')->nullable();
            $table->unsignedInteger('inspector_id')->nullable();
            $table->unsignedInteger('supervisor_id')->nullable();
            $table->enum('resultado_general', ['EXCELENTE', 'BUENO', 'REGULAR', 'MALO', 'CRITICO'])->nullable();
            $table->text('observaciones_generales')->nullable();
            $table->tinyInteger('requiere_mantenimiento')->default(0);
            $table->enum('estatus', ['ABIERTA', 'CERRADA'])->default('ABIERTA');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_inspecciones_maquina');
    }
};
