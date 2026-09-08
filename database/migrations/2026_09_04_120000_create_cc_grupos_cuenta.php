<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCcGruposCuenta extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tbl_cc_grupos_cuenta')) {
            Schema::create('tbl_cc_grupos_cuenta', function (Blueprint $table) {
                $table->id();
                $table->string('empresa', 40);
                $table->string('clave', 40);
                $table->string('nombre', 180);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['empresa', 'clave'], 'cc_grupo_emp_clave');
                $table->index('empresa', 'cc_grupo_emp');
            });
        }

        if (! Schema::hasTable('tbl_cc_grupo_cuentas')) {
            Schema::create('tbl_cc_grupo_cuentas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('grupo_id');
                $table->string('cuenta_codigo', 40);
                $table->string('cuenta_nombre', 180)->nullable();
                $table->timestamps();

                $table->index('grupo_id', 'cc_grupo_cta_grupo');
                $table->unique(['grupo_id', 'cuenta_codigo'], 'cc_grupo_cta_unica');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tbl_cc_grupo_cuentas');
        Schema::dropIfExists('tbl_cc_grupos_cuenta');
    }
}
