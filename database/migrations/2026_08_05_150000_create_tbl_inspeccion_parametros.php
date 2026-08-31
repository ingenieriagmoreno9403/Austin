<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_inspeccion_parametros')) {
            Schema::create('tbl_inspeccion_parametros', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 200);
                $table->unsignedSmallInteger('orden')->default(0);
                $table->string('estatus', 1)->default('A');
                $table->timestamps();
            });
        }

        $defaults = [
            'Limpieza general',
            'Motores',
            'Bandas',
            'Tornillería',
            'Sistema eléctrico',
            'Sistema neumático',
            'Sistema hidráulico',
            'Sensores',
            'Protecciones de seguridad',
            'Fugas',
            'Vibraciones',
            'Ruidos anormales',
            'Lubricación',
        ];

        $now = now();
        foreach ($defaults as $i => $nombre) {
            $existe = DB::table('tbl_inspeccion_parametros')->where('nombre', $nombre)->exists();
            if ($existe) {
                continue;
            }
            DB::table('tbl_inspeccion_parametros')->insert([
                'nombre' => $nombre,
                'orden' => $i + 1,
                'estatus' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_inspeccion_parametros');
    }
};
