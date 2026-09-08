<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCcCapturaPresupuesto extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_cc_captura_centros')) {
            Schema::create('tbl_cc_captura_centros', function (Blueprint $table) {
                $table->id();
                $table->string('ciclo_codigo', 40);
                $table->string('empresa', 40);
                $table->string('centro_codigo', 40);
                $table->string('estado', 30)->default('en_proceso');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['ciclo_codigo', 'empresa', 'centro_codigo'], 'cc_captura_centro_unica');
                $table->index(['ciclo_codigo', 'empresa'], 'cc_captura_centro_ciclo_emp');
            });
        }

        if (! Schema::hasTable('tbl_cc_presupuestos')) {
            Schema::create('tbl_cc_presupuestos', function (Blueprint $table) {
                $table->id();
                $table->string('ciclo_codigo', 40);
                $table->string('empresa', 40);
                $table->string('centro_codigo', 40);
                $table->string('cuenta_codigo', 40);
                $table->string('cuenta_nombre', 180)->nullable();
                $table->decimal('mes_01', 18, 2)->default(0);
                $table->decimal('mes_02', 18, 2)->default(0);
                $table->decimal('mes_03', 18, 2)->default(0);
                $table->decimal('mes_04', 18, 2)->default(0);
                $table->decimal('mes_05', 18, 2)->default(0);
                $table->decimal('mes_06', 18, 2)->default(0);
                $table->decimal('mes_07', 18, 2)->default(0);
                $table->decimal('mes_08', 18, 2)->default(0);
                $table->decimal('mes_09', 18, 2)->default(0);
                $table->decimal('mes_10', 18, 2)->default(0);
                $table->decimal('mes_11', 18, 2)->default(0);
                $table->decimal('mes_12', 18, 2)->default(0);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['ciclo_codigo', 'empresa', 'centro_codigo', 'cuenta_codigo'], 'cc_ppto_unica');
                $table->index(['ciclo_codigo', 'empresa', 'centro_codigo'], 'cc_ppto_ciclo_emp_cc');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_cc_presupuestos');
        Schema::dropIfExists('tbl_cc_captura_centros');
    }
}
