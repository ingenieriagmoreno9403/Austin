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
                'SURTIDO',
                'ENTREGADO',
                'CANCELADO'
            ) NOT NULL DEFAULT 'NUEVO'
        ");
    }
};

