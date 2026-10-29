<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RenameMenuComparativaALicitaciones extends Migration
{
    public function up()
    {
        DB::table('tblvistas')
            ->where('descripcion', 'licitaciones')
            ->where('nombre', 'Comparativa de Precios')
            ->update(['nombre' => 'Licitaciones']);
    }

    public function down()
    {
        DB::table('tblvistas')
            ->where('descripcion', 'licitaciones')
            ->where('nombre', 'Licitaciones')
            ->update(['nombre' => 'Comparativa de Precios']);
    }
}
