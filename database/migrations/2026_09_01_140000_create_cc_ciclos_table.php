<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCcCiclosTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tbl_cc_ciclos')) {
            return;
        }

        Schema::create('tbl_cc_ciclos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 180);
            $table->unsignedSmallInteger('anio_referencia');
            $table->unsignedSmallInteger('anio_presupuesto');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->date('captura_hasta')->nullable();
            $table->date('revision_desde')->nullable();
            $table->string('estado', 30)->default('abierto');
            $table->decimal('inflacion', 6, 2)->default(0);
            $table->decimal('tipo_cambio', 12, 4)->default(0);
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('estado');
            $table->index('anio_presupuesto');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_cc_ciclos');
    }
}
