<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_maquina_refacciones')) {
            return;
        }

        Schema::create('tbl_maquina_refacciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_maquina');
            $table->unsignedBigInteger('id_producto');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['id_maquina', 'id_producto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_maquina_refacciones');
    }
};
