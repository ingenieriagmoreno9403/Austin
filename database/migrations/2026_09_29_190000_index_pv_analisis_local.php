<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para Análisis de proyecciones: la venta real y los precios
 * se leen de tablas locales filtrando por año + empresa + cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex(
            'tbl_pv_venta_real_snapshot',
            'pv_venta_snap_anio_emp_cli',
            'ALTER TABLE `tbl_pv_venta_real_snapshot` ADD INDEX `pv_venta_snap_anio_emp_cli` (`anio`, `empresa`, `cliente_codigo`)'
        );
        if (Schema::hasTable('tbl_pv_productos_costo') && Schema::hasColumn('tbl_pv_productos_costo', 'anio')) {
            $this->addIndex(
                'tbl_pv_productos_costo',
                'pv_prod_costo_anio_upd',
                'ALTER TABLE `tbl_pv_productos_costo` ADD INDEX `pv_prod_costo_anio_upd` (`anio`, `updated_at`)'
            );
        }
    }

    public function down(): void
    {
        $this->dropIndex('tbl_pv_venta_real_snapshot', 'pv_venta_snap_anio_emp_cli');
        $this->dropIndex('tbl_pv_productos_costo', 'pv_prod_costo_anio_upd');
    }

    protected function addIndex(string $table, string $index, string $sql): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $index)) {
            return;
        }
        DB::statement($sql);
    }

    protected function dropIndex(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $index)) {
            return;
        }
        DB::statement('ALTER TABLE `'.$table.'` DROP INDEX `'.$index.'`');
    }

    protected function indexExists(string $table, string $index): bool
    {
        $db = DB::getDatabaseName();
        $found = DB::select(
            'SELECT INDEX_NAME FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$db, $table, $index]
        );

        return $found !== [];
    }
};
