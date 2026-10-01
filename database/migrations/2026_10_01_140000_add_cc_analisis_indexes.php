<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El análisis filtra por ciclo. Si la tabla se creó sin el índice único,
 * estas consultas recorrían toda la tabla.
 */
class AddCcAnalisisIndexes extends Migration
{
    public function up()
    {
        $this->ensureLeadingIndex('tbl_cc_presupuestos', 'ciclo_codigo', 'cc_ppto_ciclo');
        $this->ensureLeadingIndex('tbl_cc_captura_centros', 'ciclo_codigo', 'cc_captura_ciclo');
        $this->ensureLeadingIndex('tbl_cc_asignaciones', 'ciclo_codigo', 'cc_asig_ciclo');
        $this->ensureLeadingIndex('tbl_cc_asignacion_cuentas', 'asignacion_id', 'cc_analisis_cta_asig');
        $this->ensureLeadingIndex('tbl_cc_asignacion_permisos', 'asignacion_id', 'cc_analisis_perm_asig');
    }

    public function down()
    {
        $this->dropIndexIfExists('tbl_cc_presupuestos', 'cc_ppto_ciclo');
        $this->dropIndexIfExists('tbl_cc_captura_centros', 'cc_captura_ciclo');
        $this->dropIndexIfExists('tbl_cc_asignaciones', 'cc_asig_ciclo');
        $this->dropIndexIfExists('tbl_cc_asignacion_cuentas', 'cc_analisis_cta_asig');
        $this->dropIndexIfExists('tbl_cc_asignacion_permisos', 'cc_analisis_perm_asig');
    }

    private function ensureLeadingIndex(string $table, string $column, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }
        if ($this->hasLeadingIndex($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($column, $name) {
            $table->index($column, $name);
        });
    }

    private function hasLeadingIndex(string $table, string $column): bool
    {
        $schema = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND column_name = ? AND seq_in_index = 1 LIMIT 1',
            [$schema, $table, $column]
        );

        return $rows !== [];
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        $schema = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$schema, $table, $name]
        );
        if ($rows === []) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($name) {
            $table->dropIndex($name);
        });
    }
}
