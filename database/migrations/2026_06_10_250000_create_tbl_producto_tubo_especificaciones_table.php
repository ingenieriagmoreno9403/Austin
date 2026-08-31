<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_producto_tubo_especificaciones')) {
            return;
        }

        Schema::create('tbl_producto_tubo_especificaciones', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('producto_id');
            $table->string('material', 100)->nullable();
            $table->string('diametro_nominal', 50);
            $table->decimal('diametro_exterior_pulg', 12, 6)->nullable();
            $table->decimal('psi', 10, 2);
            $table->decimal('rd', 8, 2)->nullable();
            $table->decimal('espesor_pulg', 12, 6)->nullable();
            $table->decimal('peso_kg_m', 12, 4)->nullable();
            $table->enum('estatus', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['producto_id', 'diametro_nominal', 'psi'], 'tubo_spec_unique');
            $table->index(['producto_id', 'diametro_nominal'], 'tubo_spec_prod_diam_idx');
            $table->index(['producto_id', 'psi'], 'tubo_spec_prod_psi_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_producto_tubo_especificaciones');
    }
};
