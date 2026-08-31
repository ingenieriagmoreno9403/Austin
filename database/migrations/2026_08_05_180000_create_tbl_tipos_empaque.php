<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_tipos_empaque')) {
            Schema::create('tbl_tipos_empaque', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 30)->unique();
                $table->string('nombre', 120);
                $table->string('descripcion', 255)->nullable();
                $table->boolean('requiere_enrollar')->default(false);
                $table->unsignedSmallInteger('orden')->default(0);
                $table->string('estatus', 10)->default('A');
                $table->timestamps();
            });
        }

        $now = now();
        $seed = [
            [
                'codigo' => 'ROLLO',
                'nombre' => 'Rollo (> 50 m)',
                'descripcion' => 'Tubería en rollo; requiere enrollador.',
                'requiere_enrollar' => 1,
                'orden' => 1,
                'estatus' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'codigo' => 'TRAMOS',
                'nombre' => 'Tramos (≤ 20 m)',
                'descripcion' => 'Tramos cortos; sin enrollador.',
                'requiere_enrollar' => 0,
                'orden' => 2,
                'estatus' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($seed as $fila) {
            $existe = DB::table('tbl_tipos_empaque')->where('codigo', $fila['codigo'])->exists();
            if (!$existe) {
                DB::table('tbl_tipos_empaque')->insert($fila);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_tipos_empaque');
    }
};
