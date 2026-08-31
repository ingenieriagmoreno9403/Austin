<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_maquina_mantenimiento_archivos')) {
            return;
        }

        Schema::create('tbl_maquina_mantenimiento_archivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_mantenimiento');
            $table->enum('tipo_archivo', ['documento', 'fotografia'])->default('documento');
            $table->string('nombre_original', 255)->nullable();
            $table->string('ruta_archivo', 500);
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_maquina_mantenimiento_archivos');
    }
};
