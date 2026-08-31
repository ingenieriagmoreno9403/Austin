<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_reporte_no_existencias')) {
            return;
        }

        Schema::create('tbl_reporte_no_existencias', function (Blueprint $table) {
            $table->increments('id');
            $table->string('folio', 50)->nullable();
            $table->unsignedInteger('pedido_id')->nullable();
            $table->unsignedInteger('pedido_detalle_id')->nullable();
            $table->unsignedInteger('cliente_id')->nullable();
            $table->unsignedInteger('producto_id');
            $table->string('descripcion', 255)->nullable();
            $table->decimal('cantidad_solicitada', 14, 2)->default(0);
            $table->decimal('existencia_disponible', 14, 2)->default(0);
            $table->decimal('cantidad_faltante', 14, 2)->default(0);
            $table->enum('estatus', ['PENDIENTE', 'EN_COMPRA', 'ATENDIDO', 'CANCELADO'])->default('PENDIENTE');
            $table->unsignedBigInteger('orden_compra_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();

            $table->index('pedido_id', 'tbl_reporte_no_exist_pedido_idx');
            $table->index('producto_id', 'tbl_reporte_no_exist_producto_idx');
            $table->index('orden_compra_id', 'tbl_reporte_no_exist_oc_idx');
            $table->index('estatus', 'tbl_reporte_no_exist_estatus_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_reporte_no_existencias');
    }
};
