<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_mantenimientos')) {
            return;
        }

        Schema::create('tbl_mantenimientos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('maquina_id')->nullable();
            $table->enum('tipo', ['PREVENTIVO', 'CORRECTIVO', 'PREDICTIVO']);
            $table->date('fecha_programada')->nullable();
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->unsignedInteger('responsable_id')->nullable();
            $table->text('descripcion')->nullable();
            $table->decimal('horas_paro', 8, 2)->nullable();
            $table->decimal('costo_mano_obra', 14, 2)->nullable();
            $table->decimal('costo_refacciones', 14, 2)->nullable();
            $table->decimal('costo_total', 14, 2)->nullable();
            $table->text('resultado')->nullable();
            $table->enum('estatus', ['PENDIENTE', 'EN_PROCESO', 'FINALIZADO', 'CANCELADO'])->default('PENDIENTE');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_mantenimientos');
    }
};
