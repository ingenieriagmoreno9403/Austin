<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_ordenes_detalle_salidas')) {
            return;
        }

        Schema::create('tbl_ordenes_detalle_salidas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('orden_detalle_id');
            $table->string('diametro_real', 50)->nullable();
            $table->string('rd_real', 50)->nullable();
            $table->decimal('espesor_real', 10, 3)->nullable();
            $table->decimal('metros', 12, 3)->default(0);
            $table->decimal('largo_tramo', 10, 3)->nullable();
            $table->integer('piezas')->default(0);
            $table->decimal('kg_real', 12, 3)->nullable();
            $table->decimal('kg_teorico', 12, 3)->nullable();
            $table->decimal('kg_merma', 12, 3)->nullable();
            $table->decimal('porcentaje_merma', 8, 3)->nullable();
            $table->integer('ubicacion_destino_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('orden_detalle_id')->references('id')->on('tbl_ordenes_detalle')->cascadeOnDelete();
            $table->index('orden_detalle_id');
        });

        if (!Schema::hasTable('tbl_ordenes_detalle')) {
            return;
        }

        $lineas = DB::table('tbl_ordenes_detalle')
            ->where('tipo_linea', 'FABRICAR')
            ->where(function ($query) {
                $query->where('metros_producidos', '>', 0)
                    ->orWhereNotNull('kg_real');
            })
            ->get();

        foreach ($lineas as $linea) {
            DB::table('tbl_ordenes_detalle_salidas')->insert([
                'orden_detalle_id' => $linea->id,
                'diametro_real' => $linea->diametro,
                'rd_real' => $linea->rd,
                'espesor_real' => $linea->espesor,
                'metros' => $linea->metros_producidos ?? 0,
                'largo_tramo' => $linea->largo_tramo,
                'piezas' => $linea->piezas_producidas ?? 0,
                'kg_real' => $linea->kg_real,
                'kg_teorico' => $linea->kg_teorico,
                'kg_merma' => $linea->kg_merma,
                'porcentaje_merma' => $linea->porcentaje_merma,
                'created_at' => $linea->created_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_ordenes_detalle_salidas');
    }
};
