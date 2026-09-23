<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_pv_productos_costo_historial')) {
            return;
        }

        Schema::create('tbl_pv_productos_costo_historial', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 40);
            $table->string('producto_codigo', 80);
            $table->string('producto_nombre', 180)->nullable();
            $table->decimal('precio_anterior', 18, 4)->nullable();
            $table->decimal('precio_nuevo', 18, 4)->default(0);
            $table->string('moneda_anterior', 8)->nullable();
            $table->string('moneda_nueva', 8)->default('MXN');
            $table->string('origen', 20)->default('manual');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['empresa', 'producto_codigo', 'created_at'], 'pv_prod_costo_hist_prod');
            $table->index('origen', 'pv_prod_costo_hist_origen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pv_productos_costo_historial');
    }
};
