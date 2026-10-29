<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGiroProveedor extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tblgiro_proveedor')) {
            Schema::create('tblgiro_proveedor', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 60);
                $table->unique('nombre');
            });
        }

        if (! Schema::hasTable('tblprovedores') || ! Schema::hasColumn('tblprovedores', 'giro')) {
            return;
        }

        $giros = DB::table('tblprovedores')
            ->whereNotNull('giro')
            ->where('giro', '!=', '')
            ->distinct()
            ->pluck('giro');

        foreach ($giros as $giro) {
            $nombre = mb_substr(trim((string) $giro), 0, 60);
            if ($nombre === '') {
                continue;
            }
            $existe = DB::table('tblgiro_proveedor')
                ->whereRaw('UPPER(nombre) = ?', [mb_strtoupper($nombre)])
                ->exists();
            if (! $existe) {
                DB::table('tblgiro_proveedor')->insert(['nombre' => $nombre]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('tblgiro_proveedor');
    }
}
