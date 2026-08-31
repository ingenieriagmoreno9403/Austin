<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (!Schema::hasTable('tbl_causas_falla')) {
            Schema::create('tbl_causas_falla', function (Blueprint $table) {
                $table->increments('id');
                $table->string('clave', 20)->unique();
                $table->string('nombre', 150);
                $table->string('estatus', 1)->default('A');
                $table->unsignedSmallInteger('orden')->default(0);
                $table->timestamps();
            });
        }

        $causas = [
            ['clave' => 'CF-01', 'nombre' => 'Falla mecánica', 'orden' => 1],
            ['clave' => 'CF-02', 'nombre' => 'Falla eléctrica', 'orden' => 2],
            ['clave' => 'CF-03', 'nombre' => 'Falla electrónica/control', 'orden' => 3],
            ['clave' => 'CF-04', 'nombre' => 'Falla hidráulica', 'orden' => 4],
            ['clave' => 'CF-05', 'nombre' => 'Falla neumática', 'orden' => 5],
            ['clave' => 'CF-06', 'nombre' => 'Desgaste de componente', 'orden' => 6],
            ['clave' => 'CF-07', 'nombre' => 'Sobrecalentamiento', 'orden' => 7],
            ['clave' => 'CF-08', 'nombre' => 'Falla de husillo/tornillo', 'orden' => 8],
            ['clave' => 'CF-09', 'nombre' => 'Falla de resistencia/calefacción', 'orden' => 9],
            ['clave' => 'CF-10', 'nombre' => 'Falla de motor', 'orden' => 10],
            ['clave' => 'CF-11', 'nombre' => 'Atascamiento/obstrucción', 'orden' => 11],
            ['clave' => 'CF-12', 'nombre' => 'Otro', 'orden' => 12],
        ];

        foreach ($causas as $causa) {
            $existe = DB::table('tbl_causas_falla')->where('clave', $causa['clave'])->exists();
            if ($existe) {
                DB::table('tbl_causas_falla')->where('clave', $causa['clave'])->update([
                    'nombre' => $causa['nombre'],
                    'estatus' => 'A',
                    'orden' => $causa['orden'],
                    'updated_at' => $now,
                ]);
                continue;
            }

            DB::table('tbl_causas_falla')->insert([
                'clave' => $causa['clave'],
                'nombre' => $causa['nombre'],
                'estatus' => 'A',
                'orden' => $causa['orden'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('tbl_mantenimientos') && !Schema::hasColumn('tbl_mantenimientos', 'causa_falla_id')) {
            Schema::table('tbl_mantenimientos', function (Blueprint $table) {
                $table->unsignedInteger('causa_falla_id')->nullable();
            });

            // FK aparte por si la columna ya existe sin FK.
            Schema::table('tbl_mantenimientos', function (Blueprint $table) {
                $table->foreign('causa_falla_id')
                    ->references('id')
                    ->on('tbl_causas_falla')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_mantenimientos') && Schema::hasColumn('tbl_mantenimientos', 'causa_falla_id')) {
            Schema::table('tbl_mantenimientos', function (Blueprint $table) {
                $table->dropForeign(['causa_falla_id']);
                $table->dropColumn('causa_falla_id');
            });
        }

        Schema::dropIfExists('tbl_causas_falla');
    }
};
