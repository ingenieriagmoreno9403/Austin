<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pedido: estatus posteriores a producción (paso 1 de Cargas).
        DB::statement("ALTER TABLE tbl_pedidos MODIFY estatus ENUM(
            'CONFIRMADO',
            'EN_PRODUCCION',
            'PENDIENTE_OC',
            'LISTO_PARA_CARGA',
            'CARGA_AVISADA'
        ) NOT NULL DEFAULT 'CONFIRMADO'");

        // Pedidos cuya OP ya terminó → listos para que Ventas avise la carga.
        DB::table('tbl_pedidos')
            ->where('estatus', 'EN_PRODUCCION')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('tbl_ordenes')
                    ->whereColumn('tbl_ordenes.pedido_id', 'tbl_pedidos.id')
                    ->where('tbl_ordenes.estatus', 'TERMINADA');
            })
            ->update(['estatus' => 'LISTO_PARA_CARGA', 'updated_at' => now()]);

        // Limpia intento fallido previo (sin FKs correctas).
        Schema::dropIfExists('tbl_cargas');

        Schema::create('tbl_cargas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('folio', 40)->unique();
            // tbl_pedidos.id y tbl_ordenes.id son INT UNSIGNED (no bigint).
            $table->unsignedInteger('pedido_id');
            $table->unsignedInteger('orden_produccion_id')->nullable();
            $table->string('estatus', 40)->default('PROGRAMADA');
            $table->date('fecha_programada')->nullable();
            $table->time('hora_programada')->nullable();
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('avisado_por')->nullable();
            $table->timestamp('avisado_at')->nullable();
            $table->timestamps();

            $table->foreign('pedido_id', 'fk_carga_pedido')
                ->references('id')->on('tbl_pedidos')->cascadeOnDelete();
            $table->foreign('orden_produccion_id', 'fk_carga_op')
                ->references('id')->on('tbl_ordenes')->nullOnDelete();
            $table->foreign('avisado_por', 'fk_carga_avisado_por')
                ->references('id')->on('users')->nullOnDelete();

            $table->index('estatus', 'idx_carga_estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_cargas');

        DB::table('tbl_pedidos')
            ->whereIn('estatus', ['LISTO_PARA_CARGA', 'CARGA_AVISADA'])
            ->update(['estatus' => 'EN_PRODUCCION']);

        DB::statement("ALTER TABLE tbl_pedidos MODIFY estatus ENUM(
            'CONFIRMADO',
            'EN_PRODUCCION',
            'PENDIENTE_OC'
        ) NOT NULL DEFAULT 'CONFIRMADO'");
    }
};
