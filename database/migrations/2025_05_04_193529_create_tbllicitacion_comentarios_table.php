<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbllicitacion_comentarios', function (Blueprint $table) {
            $table->id();
            $table->integer('licitacion_id');
            $table->integer('proveedor_id');
            $table->text('comentarios')->nullable();
            $table->timestamps();
            
            // Índice para búsquedas rápidas
            $table->index(['licitacion_id', 'proveedor_id']);
            
            // Clave única para asegurar un solo registro por licitación y proveedor
            $table->unique(['licitacion_id', 'proveedor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbllicitacion_comentarios');
    }
};
