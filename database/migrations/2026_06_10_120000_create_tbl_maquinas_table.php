<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_maquinas')) {
            return;
        }

        Schema::create('tbl_maquinas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre', 150)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('ubicacion', 150)->nullable();
            $table->decimal('capacidad_pr_hora', 12, 3)->nullable();
            $table->enum('estatus', ['A', 'I', 'M'])->default('A');
            $table->text('ruta_manual')->nullable();
            $table->string('otrosconceptos1', 100)->nullable();
            $table->string('otrosconceptos2', 200)->nullable();
            $table->string('otrosconceptos3', 250)->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_maquinas');
    }
};
