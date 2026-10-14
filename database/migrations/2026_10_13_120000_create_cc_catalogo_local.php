<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCcCatalogoLocal extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_cc_catalogo_centros')) {
            Schema::create('tbl_cc_catalogo_centros', function (Blueprint $table) {
                $table->id();
                $table->string('empresa', 40);
                $table->string('codigo', 40);
                $table->string('nombre', 180)->nullable();
                $table->string('departamento', 80)->nullable();
                $table->boolean('activo')->default(true);
                $table->string('origen', 20)->default('sap');
                $table->timestamp('synced_at')->nullable();
                $table->unsignedBigInteger('synced_by')->nullable();
                $table->timestamps();

                $table->unique(['empresa', 'codigo'], 'cc_cat_centros_unica');
                $table->index(['empresa'], 'cc_cat_centros_emp');
                $table->index(['nombre'], 'cc_cat_centros_nombre');
            });
        }

        if (! Schema::hasTable('tbl_cc_catalogo_cuentas')) {
            Schema::create('tbl_cc_catalogo_cuentas', function (Blueprint $table) {
                $table->id();
                $table->string('empresa', 40);
                $table->string('codigo', 40);
                $table->string('nombre', 180)->nullable();
                $table->string('grupo', 80)->nullable();
                $table->string('grupo_id', 40)->nullable();
                $table->string('origen', 20)->default('sap');
                $table->timestamp('synced_at')->nullable();
                $table->unsignedBigInteger('synced_by')->nullable();
                $table->timestamps();

                $table->unique(['empresa', 'codigo'], 'cc_cat_cuentas_unica');
                $table->index(['empresa'], 'cc_cat_cuentas_emp');
                $table->index(['empresa', 'grupo_id'], 'cc_cat_cuentas_grupo');
                $table->index(['nombre'], 'cc_cat_cuentas_nombre');
            });
        }

        if (! Schema::hasTable('tbl_cc_catalogo_sync')) {
            Schema::create('tbl_cc_catalogo_sync', function (Blueprint $table) {
                $table->id();
                $table->string('empresa', 40);
                $table->unsignedInteger('centros')->default(0);
                $table->unsignedInteger('cuentas')->default(0);
                $table->boolean('ok')->default(false);
                $table->string('mensaje', 255)->nullable();
                $table->string('origen', 20)->default('sap');
                $table->timestamp('synced_at')->nullable();
                $table->unsignedBigInteger('synced_by')->nullable();
                $table->timestamps();

                $table->unique(['empresa'], 'cc_cat_sync_unica');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_cc_catalogo_sync');
        Schema::dropIfExists('tbl_cc_catalogo_cuentas');
        Schema::dropIfExists('tbl_cc_catalogo_centros');
    }
}
