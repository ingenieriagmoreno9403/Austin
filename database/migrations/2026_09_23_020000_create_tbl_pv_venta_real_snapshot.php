<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblPvVentaRealSnapshot extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_pv_venta_real_snapshot')) {
            return;
        }

        Schema::create('tbl_pv_venta_real_snapshot', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 40);
            $table->string('cliente_codigo', 40);
            $table->unsignedSmallInteger('anio');
            $table->json('por_cuenta');
            $table->string('origen', 20)->default('api');
            $table->timestamp('synced_at')->nullable();
            $table->unsignedBigInteger('synced_by')->nullable();
            $table->timestamps();

            $table->unique(['empresa', 'cliente_codigo', 'anio'], 'pv_venta_snap_unica');
            $table->index(['empresa', 'anio'], 'pv_venta_snap_emp_anio');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_pv_venta_real_snapshot');
    }
}
