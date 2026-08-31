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
            MODIFY COLUMN estatus ENUM('CONFIRMADO', 'EN_PRODUCCION', 'PENDIENTE_OC')
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
            MODIFY COLUMN estatus ENUM('CONFIRMADO', 'EN_PRODUCCION')
            NOT NULL DEFAULT 'CONFIRMADO'
        ");
    }
};

