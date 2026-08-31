<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_inspecciones_fotos')) {
            return;
        }

        Schema::create('tbl_inspecciones_fotos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('inspeccion_id')->nullable();
            $table->string('nombre_archivo', 255)->nullable();
            $table->string('ruta', 500);
            $table->text('descripcion')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_inspecciones_fotos');
    }
};
