<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RenamePvVentasTablasClienteProducto extends Migration
{
    public function up()
    {
        Schema::dropIfExists('tbl_pv_grupo_cuentas');
        Schema::dropIfExists('tbl_pv_grupos_cuenta');

        $this->renameAsignaciones();
        $this->renameAsignacionProductos();
        $this->renameCapturaClientes();
        $this->renameProyecciones();
    }

    protected function renameAsignaciones()
    {
        if (! Schema::hasTable('tbl_pv_asignaciones')) {
            return;
        }
        if (Schema::hasColumn('tbl_pv_asignaciones', 'cliente_codigo')) {
            return;
        }
        $this->dropIndexIfExists('tbl_pv_asignaciones', 'pv_asig_unica');
        DB::statement('ALTER TABLE tbl_pv_asignaciones CHANGE centro_codigo cliente_codigo VARCHAR(40) NOT NULL');
        if (Schema::hasColumn('tbl_pv_asignaciones', 'centro_nombre')) {
            DB::statement('ALTER TABLE tbl_pv_asignaciones CHANGE centro_nombre cliente_nombre VARCHAR(180) NULL');
        }
        Schema::table('tbl_pv_asignaciones', function (Blueprint $table) {
            $table->unique(['ciclo_codigo', 'empresa', 'user_id', 'cliente_codigo'], 'pv_asig_unica');
        });
    }

    protected function renameAsignacionProductos()
    {
        if (Schema::hasTable('tbl_pv_asignacion_cuentas') && ! Schema::hasTable('tbl_pv_asignacion_productos')) {
            Schema::rename('tbl_pv_asignacion_cuentas', 'tbl_pv_asignacion_productos');
        }
        if (! Schema::hasTable('tbl_pv_asignacion_productos')) {
            return;
        }
        if (Schema::hasColumn('tbl_pv_asignacion_productos', 'producto_codigo')) {
            return;
        }
        $this->dropIndexIfExists('tbl_pv_asignacion_productos', 'pv_asig_cta_unica');
        DB::statement('ALTER TABLE tbl_pv_asignacion_productos CHANGE cuenta_codigo producto_codigo VARCHAR(80) NOT NULL');
        if (Schema::hasColumn('tbl_pv_asignacion_productos', 'cuenta_nombre')) {
            DB::statement('ALTER TABLE tbl_pv_asignacion_productos CHANGE cuenta_nombre producto_nombre VARCHAR(180) NULL');
        }
        if (Schema::hasColumn('tbl_pv_asignacion_productos', 'agrupacion')) {
            DB::statement('ALTER TABLE tbl_pv_asignacion_productos CHANGE agrupacion linea VARCHAR(80) NULL');
        }
        Schema::table('tbl_pv_asignacion_productos', function (Blueprint $table) {
            $table->unique(['asignacion_id', 'producto_codigo'], 'pv_asig_prod_unica');
        });
    }

    protected function renameCapturaClientes()
    {
        if (Schema::hasTable('tbl_pv_captura_centros') && ! Schema::hasTable('tbl_pv_captura_clientes')) {
            Schema::rename('tbl_pv_captura_centros', 'tbl_pv_captura_clientes');
        }
        if (! Schema::hasTable('tbl_pv_captura_clientes')) {
            return;
        }
        if (Schema::hasColumn('tbl_pv_captura_clientes', 'cliente_codigo')) {
            return;
        }
        $this->dropIndexIfExists('tbl_pv_captura_clientes', 'pv_captura_centro_unica');
        $this->dropIndexIfExists('tbl_pv_captura_clientes', 'pv_captura_centro_ciclo_emp');
        DB::statement('ALTER TABLE tbl_pv_captura_clientes CHANGE centro_codigo cliente_codigo VARCHAR(40) NOT NULL');
        Schema::table('tbl_pv_captura_clientes', function (Blueprint $table) {
            $table->unique(['ciclo_codigo', 'empresa', 'cliente_codigo'], 'pv_captura_cliente_unica');
            $table->index(['ciclo_codigo', 'empresa'], 'pv_captura_cliente_ciclo_emp');
        });
    }

    protected function renameProyecciones()
    {
        if (Schema::hasTable('tbl_pv_presupuestos') && ! Schema::hasTable('tbl_pv_proyecciones')) {
            Schema::rename('tbl_pv_presupuestos', 'tbl_pv_proyecciones');
        }
        if (! Schema::hasTable('tbl_pv_proyecciones')) {
            return;
        }
        if (Schema::hasColumn('tbl_pv_proyecciones', 'producto_codigo')) {
            return;
        }
        $this->dropIndexIfExists('tbl_pv_proyecciones', 'pv_ppto_unica');
        $this->dropIndexIfExists('tbl_pv_proyecciones', 'pv_ppto_ciclo_emp_cc');
        DB::statement('ALTER TABLE tbl_pv_proyecciones CHANGE centro_codigo cliente_codigo VARCHAR(40) NOT NULL');
        DB::statement('ALTER TABLE tbl_pv_proyecciones CHANGE cuenta_codigo producto_codigo VARCHAR(80) NOT NULL');
        if (Schema::hasColumn('tbl_pv_proyecciones', 'cuenta_nombre')) {
            DB::statement('ALTER TABLE tbl_pv_proyecciones CHANGE cuenta_nombre producto_nombre VARCHAR(180) NULL');
        }
        Schema::table('tbl_pv_proyecciones', function (Blueprint $table) {
            $table->unique(['ciclo_codigo', 'empresa', 'cliente_codigo', 'producto_codigo'], 'pv_proy_unica');
            $table->index(['ciclo_codigo', 'empresa', 'cliente_codigo'], 'pv_proy_ciclo_emp_cli');
        });
    }

    protected function dropIndexIfExists(string $table, string $index)
    {
        $db = DB::getDatabaseName();
        $found = DB::select('SELECT INDEX_NAME FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1', [$db, $table, $index]);
        if ($found) {
            DB::statement('ALTER TABLE `'.$table.'` DROP INDEX `'.$index.'`');
        }
    }

    public function down()
    {
        // No revert: nombres de ventas son el esquema correcto.
    }
}
