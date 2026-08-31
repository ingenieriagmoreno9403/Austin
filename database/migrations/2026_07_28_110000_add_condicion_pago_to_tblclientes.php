<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tblclientes', 'condicion_pago_id')) {
            Schema::table('tblclientes', function (Blueprint $table) {
                $table->unsignedBigInteger('condicion_pago_id')->nullable()->after('moneda_id');
            });
        }

        $fkExiste = collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            ['tblclientes', 'fk_cliente_condicion_pago', 'FOREIGN KEY']
        ))->isNotEmpty();

        if (!$fkExiste && Schema::hasColumn('tblclientes', 'condicion_pago_id')) {
            // Ajustar tipo si la PK de condiciones no es bigint.
            $tipo = DB::selectOne("SHOW COLUMNS FROM tbl_condiciones_pago WHERE Field = 'id'");
            if ($tipo && stripos((string) $tipo->Type, 'int') !== false && stripos((string) $tipo->Type, 'bigint') === false) {
                DB::statement('ALTER TABLE tblclientes MODIFY condicion_pago_id INT UNSIGNED NULL');
            }

            Schema::table('tblclientes', function (Blueprint $table) {
                $table->foreign('condicion_pago_id', 'fk_cliente_condicion_pago')
                    ->references('id')->on('tbl_condiciones_pago')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tblclientes', 'condicion_pago_id')) {
            Schema::table('tblclientes', function (Blueprint $table) {
                try {
                    $table->dropForeign('fk_cliente_condicion_pago');
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->dropColumn('condicion_pago_id');
            });
        }
    }
};
