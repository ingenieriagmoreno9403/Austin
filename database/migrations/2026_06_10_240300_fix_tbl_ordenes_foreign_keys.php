<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_ordenes')) {
            return;
        }

        DB::statement('ALTER TABLE tbl_ordenes MODIFY maquina_id INT NULL');
        DB::statement('ALTER TABLE tbl_ordenes MODIFY operador_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE tbl_ordenes MODIFY supervisor_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE tbl_ordenes MODIFY created_by BIGINT UNSIGNED NULL');

        $fks = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_ordenes' AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        ))->pluck('CONSTRAINT_NAME');

        if (!$fks->contains('tbl_ordenes_maquina_id_foreign')) {
            Schema::table('tbl_ordenes', function (Blueprint $table) {
                $table->foreign('maquina_id')->references('id')->on('tbl_maquinas')->nullOnDelete();
            });
        }

        if (!$fks->contains('tbl_ordenes_turno_id_foreign')) {
            Schema::table('tbl_ordenes', function (Blueprint $table) {
                $table->foreign('turno_id')->references('id')->on('tbl_turnos')->nullOnDelete();
            });
        }

        if (Schema::hasTable('tbl_ordenes_detalle')) {
            DB::statement('ALTER TABLE tbl_ordenes_detalle MODIFY producto_id INT NULL');
        }

        if (!DB::table('migrations')->where('migration', '2026_06_10_240100_create_tbl_ordenes_table')->exists()) {
            DB::table('migrations')->insert([
                'migration' => '2026_06_10_240100_create_tbl_ordenes_table',
                'batch' => (int) DB::table('migrations')->max('batch') + 1,
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('tbl_ordenes')) {
            return;
        }

        Schema::table('tbl_ordenes', function (Blueprint $table) {
            if ($this->foreignKeyExists('tbl_ordenes', 'tbl_ordenes_maquina_id_foreign')) {
                $table->dropForeign(['maquina_id']);
            }
            if ($this->foreignKeyExists('tbl_ordenes', 'tbl_ordenes_turno_id_foreign')) {
                $table->dropForeign(['turno_id']);
            }
        });
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [$table, $constraint]
        ))->isNotEmpty();
    }
};
