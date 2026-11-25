<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_lic_param_tipos')) {
            Schema::create('tbl_lic_param_tipos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 120);
                $table->string('descripcion', 500)->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tbl_lic_param_plantilla')) {
            Schema::create('tbl_lic_param_plantilla', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tipo_id');
                $table->string('nombre', 160);
                $table->string('clave', 80)->nullable();
                $table->decimal('peso_pct', 8, 2)->default(0);
                $table->string('sentido', 20)->default('menor_mejor'); // menor_mejor | mayor_mejor
                $table->string('tipo_dato', 20)->default('numero'); // numero | porcentaje | moneda | dias
                $table->boolean('obligatorio')->default(true);
                $table->unsignedInteger('orden')->default(1);
                $table->boolean('activo')->default(true);
                $table->timestamps();

                $table->foreign('tipo_id')->references('id')->on('tbl_lic_param_tipos')->onDelete('cascade');
                $table->index(['tipo_id', 'orden']);
            });
        }

        if (! Schema::hasTable('tbl_lic_parametros')) {
            Schema::create('tbl_lic_parametros', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('licitacion_id');
                $table->unsignedBigInteger('plantilla_id')->nullable();
                $table->string('nombre', 160);
                $table->decimal('peso_pct', 8, 2)->default(0);
                $table->string('sentido', 20)->default('menor_mejor');
                $table->string('tipo_dato', 20)->default('numero');
                $table->boolean('obligatorio')->default(true);
                $table->unsignedInteger('orden')->default(1);
                $table->timestamps();

                $table->index(['licitacion_id', 'orden']);
            });
        }

        if (! Schema::hasTable('tbl_lic_param_scores')) {
            Schema::create('tbl_lic_param_scores', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('licitacion_id');
                $table->unsignedBigInteger('parametro_id');
                $table->unsignedBigInteger('proveedor_id');
                $table->decimal('valor', 18, 4)->nullable();
                $table->decimal('score_normalizado', 8, 2)->nullable();
                $table->string('comentario', 500)->nullable();
                $table->timestamps();

                $table->unique(['parametro_id', 'proveedor_id'], 'lic_param_score_unique');
                $table->index(['licitacion_id', 'proveedor_id']);
            });
        }

        if (Schema::hasTable('tbllicitacion_enc') && ! Schema::hasColumn('tbllicitacion_enc', 'tipo_param_id')) {
            Schema::table('tbllicitacion_enc', function (Blueprint $table) {
                $table->unsignedBigInteger('tipo_param_id')->nullable()->after('estado');
            });
        }

        $this->seedTecnologias();
    }

    protected function seedTecnologias(): void
    {
        if (DB::table('tbl_lic_param_tipos')->where('nombre', 'Tecnologías')->exists()) {
            return;
        }

        $now = now();
        $tipoId = DB::table('tbl_lic_param_tipos')->insertGetId([
            'nombre' => 'Tecnologías',
            'descripcion' => 'Evaluación típica para compras de sistemas / tecnología',
            'activo' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $params = [
            ['nombre' => 'Precio', 'clave' => 'precio', 'peso_pct' => 40, 'sentido' => 'menor_mejor', 'tipo_dato' => 'moneda', 'orden' => 1],
            ['nombre' => 'Tiempo de entrega', 'clave' => 'entrega', 'peso_pct' => 25, 'sentido' => 'menor_mejor', 'tipo_dato' => 'dias', 'orden' => 2],
            ['nombre' => 'Garantía', 'clave' => 'garantia', 'peso_pct' => 20, 'sentido' => 'mayor_mejor', 'tipo_dato' => 'dias', 'orden' => 3],
            ['nombre' => 'Soporte', 'clave' => 'soporte', 'peso_pct' => 15, 'sentido' => 'mayor_mejor', 'tipo_dato' => 'numero', 'orden' => 4],
        ];

        foreach ($params as $p) {
            DB::table('tbl_lic_param_plantilla')->insert([
                'tipo_id' => $tipoId,
                'nombre' => $p['nombre'],
                'clave' => $p['clave'],
                'peso_pct' => $p['peso_pct'],
                'sentido' => $p['sentido'],
                'tipo_dato' => $p['tipo_dato'],
                'obligatorio' => 1,
                'orden' => $p['orden'],
                'activo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Tipo Proyectos de ejemplo
        if (! DB::table('tbl_lic_param_tipos')->where('nombre', 'Proyectos')->exists()) {
            $tipoProj = DB::table('tbl_lic_param_tipos')->insertGetId([
                'nombre' => 'Proyectos',
                'descripcion' => 'Evaluación para materiales / proyectos de obra',
                'activo' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $projParams = [
                ['nombre' => 'Precio', 'clave' => 'precio', 'peso_pct' => 50, 'sentido' => 'menor_mejor', 'tipo_dato' => 'moneda', 'orden' => 1],
                ['nombre' => 'Tiempo de entrega', 'clave' => 'entrega', 'peso_pct' => 30, 'sentido' => 'menor_mejor', 'tipo_dato' => 'dias', 'orden' => 2],
                ['nombre' => 'Calidad / especificación', 'clave' => 'calidad', 'peso_pct' => 20, 'sentido' => 'mayor_mejor', 'tipo_dato' => 'numero', 'orden' => 3],
            ];
            foreach ($projParams as $p) {
                DB::table('tbl_lic_param_plantilla')->insert([
                    'tipo_id' => $tipoProj,
                    'nombre' => $p['nombre'],
                    'clave' => $p['clave'],
                    'peso_pct' => $p['peso_pct'],
                    'sentido' => $p['sentido'],
                    'tipo_dato' => $p['tipo_dato'],
                    'obligatorio' => 1,
                    'orden' => $p['orden'],
                    'activo' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_lic_param_scores');
        Schema::dropIfExists('tbl_lic_parametros');
        Schema::dropIfExists('tbl_lic_param_plantilla');
        Schema::dropIfExists('tbl_lic_param_tipos');

        if (Schema::hasTable('tbllicitacion_enc') && Schema::hasColumn('tbllicitacion_enc', 'tipo_param_id')) {
            Schema::table('tbllicitacion_enc', function (Blueprint $table) {
                $table->dropColumn('tipo_param_id');
            });
        }
    }
};
