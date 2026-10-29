<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddHomologacionContratoProveedor extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tblprovedores')) {
            if (! Schema::hasColumn('tblprovedores', 'homologacion')) {
                DB::statement("ALTER TABLE `tblprovedores` ADD `homologacion` VARCHAR(20) NOT NULL DEFAULT 'aprobado'");
            }
            if (! Schema::hasColumn('tblprovedores', 'preferido')) {
                DB::statement('ALTER TABLE `tblprovedores` ADD `preferido` TINYINT(1) NOT NULL DEFAULT 0');
            }
            if (! Schema::hasColumn('tblprovedores', 'contrato_valor')) {
                DB::statement('ALTER TABLE `tblprovedores` ADD `contrato_valor` DECIMAL(14,2) NULL');
            }
            if (! Schema::hasColumn('tblprovedores', 'contrato_inicio')) {
                DB::statement('ALTER TABLE `tblprovedores` ADD `contrato_inicio` DATE NULL');
            }
            if (! Schema::hasColumn('tblprovedores', 'contrato_fin')) {
                DB::statement('ALTER TABLE `tblprovedores` ADD `contrato_fin` DATE NULL');
            }
            if (! Schema::hasColumn('tblprovedores', 'contrato_archivo')) {
                DB::statement('ALTER TABLE `tblprovedores` ADD `contrato_archivo` VARCHAR(255) NULL');
            }

            DB::statement("UPDATE `tblprovedores` SET `homologacion` = 'aprobado' WHERE `homologacion` IS NULL OR `homologacion` = ''");
        }

        if (! Schema::hasTable('tblproveedor_documentos')) {
            Schema::create('tblproveedor_documentos', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('proveedor_id');
                $table->string('nombre', 150);
                $table->date('vigencia')->nullable();
                $table->string('archivo', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index('proveedor_id');
            });
        }

        if (! Schema::hasTable('tblproveedor_acciones')) {
            Schema::create('tblproveedor_acciones', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('proveedor_id');
                $table->string('descripcion', 500);
                $table->date('fecha')->nullable();
                $table->tinyInteger('cerrada')->default(0);
                $table->timestamp('created_at')->useCurrent();
                $table->index('proveedor_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tblproveedor_acciones');
        Schema::dropIfExists('tblproveedor_documentos');

        if (! Schema::hasTable('tblprovedores')) {
            return;
        }

        foreach (['contrato_archivo', 'contrato_fin', 'contrato_inicio', 'contrato_valor', 'preferido', 'homologacion'] as $columna) {
            if (Schema::hasColumn('tblprovedores', $columna)) {
                DB::statement('ALTER TABLE `tblprovedores` DROP `'.$columna.'`');
            }
        }
    }
}
