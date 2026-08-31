<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_mantenimientos_fotos')) {
            return;
        }

        Schema::create('tbl_mantenimientos_fotos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('mantenimiento_id')->nullable();
            $table->string('nombre_archivo', 255)->nullable();
            $table->string('ruta', 500);
            $table->text('descripcion')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_mantenimientos_fotos');
    }
};
