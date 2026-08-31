<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('inspeccion_de_calidad_por_ordenes_de_trabajo');

        Schema::create('inspeccion_de_calidad_por_ordenes_de_trabajo', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('orden_id');
            $table->unsignedInteger('orden_detalle_id')->nullable();
            $table->unsignedInteger('salida_id')->nullable();
            $table->enum('resultado', ['ACEPTADO', 'RECHAZADO', 'PENDIENTE']);
            $table->string('diametro_real', 50)->nullable();
            $table->string('rd_real', 50)->nullable();
            $table->decimal('espesor_real', 10, 3)->nullable();
            $table->decimal('metros', 12, 3)->nullable();
            $table->decimal('kg_real', 12, 3)->nullable();
            $table->decimal('kg_teorico', 12, 3)->nullable();
            $table->decimal('kg_merma', 12, 3)->nullable();
            $table->decimal('porcentaje_merma', 8, 3)->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('auditor_id')->nullable();
            $table->timestamp('inspeccionado_at')->nullable();
            $table->timestamps();

            $table->foreign('orden_id', 'fk_icq_orden')->references('id')->on('tbl_ordenes')->cascadeOnDelete();
            $table->foreign('orden_detalle_id', 'fk_icq_detalle')->references('id')->on('tbl_ordenes_detalle')->nullOnDelete();
            $table->foreign('salida_id', 'fk_icq_salida')->references('id')->on('tbl_ordenes_detalle_salidas')->nullOnDelete();
            $table->foreign('auditor_id', 'fk_icq_auditor')->references('id')->on('users')->nullOnDelete();

            $table->unique('salida_id', 'uq_icq_salida');
            $table->index(['orden_id', 'resultado'], 'idx_icq_orden_resultado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspeccion_de_calidad_por_ordenes_de_trabajo');
    }
};
