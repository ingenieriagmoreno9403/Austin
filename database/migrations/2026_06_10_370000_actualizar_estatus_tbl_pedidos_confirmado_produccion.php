<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_pedidos')) {
            return;
        }

        // 1) Amplía temporalmente el ENUM para permitir la conversión de valores.
        DB::statement("
            ALTER TABLE tbl_pedidos
            MODIFY COLUMN estatus ENUM(
                'NUEVO',
                'CONFIRMADO',
                'EN_PROCESO',
                'EN_PRODUCCION',
                'PENDIENTE_MATERIALES',
                'SURTIDO',
                'ENTREGADO',
                'CANCELADO'
            ) NOT NULL DEFAULT 'CONFIRMADO'
        ");

        // Mapeo de estatus legados al nuevo flujo de venta.
        DB::table('tbl_pedidos')->whereIn('estatus', ['NUEVO', 'SURTIDO', 'ENTREGADO', 'CANCELADO'])->update(['estatus' => 'CONFIRMADO']);
        DB::table('tbl_pedidos')->whereIn('estatus', ['EN_PROCESO', 'PENDIENTE_MATERIALES'])->update(['estatus' => 'EN_PRODUCCION']);

        // 2) Deja el ENUM final solo con los estatus solicitados.
        DB::statement("
            ALTER TABLE tbl_pedidos
            MODIFY COLUMN estatus ENUM('CONFIRMADO', 'EN_PRODUCCION')
            NOT NULL DEFAULT 'CONFIRMADO'
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_pedidos')) {
            return;
        }

        DB::statement("
            ALTER TABLE tbl_pedidos
            MODIFY COLUMN estatus ENUM(
                'NUEVO',
                'CONFIRMADO',
                'EN_PROCESO',
                'PENDIENTE_MATERIALES',
                'SURTIDO',
                'ENTREGADO',
                'CANCELADO'
            ) NOT NULL DEFAULT 'NUEVO'
        ");
    }
};

