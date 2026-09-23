<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_pv_productos_costo')) {
            return;
        }

        Schema::create('tbl_pv_productos_costo', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 40);
            $table->string('producto_codigo', 80);
            $table->string('producto_nombre', 180)->nullable();
            $table->decimal('costo_unitario', 18, 4)->default(0);
            $table->string('moneda', 8)->default('MXN');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['empresa', 'producto_codigo'], 'pv_prod_costo_unica');
            $table->index('empresa', 'pv_prod_costo_emp');
            $table->index('producto_codigo', 'pv_prod_costo_prod');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pv_productos_costo');
    }
};
