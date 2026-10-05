<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCcGastoRealSnapshot extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_cc_gasto_real_carga')) {
            Schema::create('tbl_cc_gasto_real_carga', function (Blueprint $table) {
                $table->id();
                $table->string('empresa', 40);
                $table->unsignedSmallInteger('anio');
                $table->boolean('completa')->default(false);
                $table->unsignedInteger('filas')->default(0);
                $table->string('origen', 20)->default('archivo');
                $table->timestamp('synced_at')->nullable();
                $table->unsignedBigInteger('synced_by')->nullable();
                $table->timestamps();

                $table->unique(['empresa', 'anio'], 'cc_gasto_carga_unica');
            });
        }

        if (! Schema::hasTable('tbl_cc_gasto_real_cc')) {
            Schema::create('tbl_cc_gasto_real_cc', function (Blueprint $table) {
                $table->id();
                $table->string('empresa', 40);
                $table->unsignedSmallInteger('anio');
                $table->string('centro_codigo', 40);
                $table->unsignedInteger('cuentas')->default(0);
                $table->string('origen', 20)->default('api');
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();

                $table->unique(['empresa', 'anio', 'centro_codigo'], 'cc_gasto_cc_unica');
                $table->index(['empresa', 'anio'], 'cc_gasto_cc_emp_anio');
            });
        }

        if (! Schema::hasTable('tbl_cc_gasto_real_snap')) {
            Schema::create('tbl_cc_gasto_real_snap', function (Blueprint $table) {
                $table->id();
                $table->string('empresa', 40);
                $table->unsignedSmallInteger('anio');
                $table->string('centro_codigo', 40);
                $table->string('cuenta_codigo', 40);
                $table->string('cuenta_nombre', 180)->nullable();
                $table->string('depto', 80)->nullable();
                $table->string('group_mask', 20)->nullable();
                for ($i = 1; $i <= 12; $i++) {
                    $col = 'mes_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    $table->decimal($col, 18, 2)->default(0);
                    $table->decimal('usd_'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 18, 2)->default(0);
                }
                $table->string('origen', 20)->default('api');
                $table->timestamp('synced_at')->nullable();
                $table->unsignedBigInteger('synced_by')->nullable();
                $table->timestamps();

                $table->unique(['empresa', 'anio', 'centro_codigo', 'cuenta_codigo'], 'cc_gasto_snap_unica');
                $table->index(['empresa', 'anio'], 'cc_gasto_snap_emp_anio');
            });
        }

        if (! Schema::hasTable('tbl_cc_gasto_real_sap')) {
            Schema::create('tbl_cc_gasto_real_sap', function (Blueprint $table) {
                $table->id();
                $table->string('empresa', 40);
                $table->unsignedSmallInteger('anio');
                $table->string('centro_codigo', 40);
                $table->string('cuenta_codigo', 40);
                $table->string('cuenta_nombre', 180)->nullable();
                $table->string('depto', 80)->nullable();
                $table->string('group_mask', 20)->nullable();
                $table->date('fecha')->nullable();
                $table->unsignedTinyInteger('mes')->nullable();
                $table->decimal('importe', 18, 2)->default(0);
                $table->decimal('importe_usd', 18, 2)->default(0);
                $table->json('fila');
                $table->string('origen', 20)->default('archivo');
                $table->unsignedBigInteger('synced_by')->nullable();
                $table->timestamps();

                $table->index(['empresa', 'anio', 'centro_codigo'], 'cc_gasto_sap_emp_anio_cc');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_cc_gasto_real_sap');
        Schema::dropIfExists('tbl_cc_gasto_real_snap');
        Schema::dropIfExists('tbl_cc_gasto_real_cc');
        Schema::dropIfExists('tbl_cc_gasto_real_carga');
    }
}
