<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEntregaFieldsToTblPedidosTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tbl_pedidos')) {
            return;
        }

        Schema::table('tbl_pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_pedidos', 'fecha_entrega')) {
                $table->date('fecha_entrega')->nullable()->after('fecha');
            }
            if (!Schema::hasColumn('tbl_pedidos', 'hora_entrega')) {
                $table->time('hora_entrega')->nullable()->after('fecha_entrega');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('tbl_pedidos')) {
            return;
        }

        Schema::table('tbl_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('tbl_pedidos', 'hora_entrega')) {
                $table->dropColumn('hora_entrega');
            }
            if (Schema::hasColumn('tbl_pedidos', 'fecha_entrega')) {
                $table->dropColumn('fecha_entrega');
            }
        });
    }
}
