<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCategoriaProveedor extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tblcategoria_proveedor')) {
            Schema::create('tblcategoria_proveedor', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 120);
                $table->unique('nombre');
            });
        }

        if (Schema::hasTable('tblprovedores') && ! Schema::hasColumn('tblprovedores', 'categoria_id')) {
            DB::statement('ALTER TABLE `tblprovedores` ADD `categoria_id` INT UNSIGNED NULL');
        }
    }

    public function down()
    {
        if (Schema::hasTable('tblprovedores') && Schema::hasColumn('tblprovedores', 'categoria_id')) {
            DB::statement('ALTER TABLE `tblprovedores` DROP `categoria_id`');
        }

        Schema::dropIfExists('tblcategoria_proveedor');
    }
}
