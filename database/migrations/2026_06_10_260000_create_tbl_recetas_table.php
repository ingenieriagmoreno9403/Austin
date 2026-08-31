<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_recetas')) {
            return;
        }

        Schema::create('tbl_recetas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('producto_id');
            $table->string('version', 20)->nullable();
            $table->enum('estatus', ['ACTIVA', 'INACTIVA'])->default('ACTIVA');
            $table->text('observaciones')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index(['producto_id', 'estatus'], 'receta_producto_estatus_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_recetas');
    }
};
