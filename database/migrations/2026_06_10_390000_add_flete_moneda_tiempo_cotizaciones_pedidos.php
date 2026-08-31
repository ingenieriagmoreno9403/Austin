<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tblmonedas')) {
            Schema::create('tblmonedas', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 10)->unique();
                $table->string('nombre', 80);
                $table->string('abreviacion', 10);
                $table->string('estatus', 10)->default('A');
                $table->timestamps();
            });

            DB::table('tblmonedas')->insert([
                [
                    'codigo' => 'MXN',
                    'nombre' => 'Peso mexicano',
                    'abreviacion' => 'MXN',
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'USD',
                    'nombre' => 'Dólar americano',
                    'abreviacion' => 'USD',
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (!Schema::hasTable('tbl_tipos_flete')) {
            Schema::create('tbl_tipos_flete', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 30)->unique();
                $table->string('nombre', 120);
                $table->string('descripcion', 255)->nullable();
                $table->string('estatus', 10)->default('A');
                $table->timestamps();
            });

            DB::table('tbl_tipos_flete')->insert([
                [
                    'codigo' => 'LAB',
                    'nombre' => 'Flete L.A.B.',
                    'descripcion' => 'Libre a bordo / Free on board',
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'PAGADO',
                    'nombre' => 'Flete pagado',
                    'descripcion' => 'Flete cubierto por el vendedor',
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'POR_COBRAR',
                    'nombre' => 'Flete por cobrar',
                    'descripcion' => 'Flete a cargo del cliente',
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'RECOGEN',
                    'nombre' => 'Cliente recoge',
                    'descripcion' => 'Sin envío; el cliente recoge en planta',
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (!Schema::hasColumn('tblclientes', 'moneda_id')) {
            Schema::table('tblclientes', function (Blueprint $table) {
                $table->unsignedBigInteger('moneda_id')->nullable()->after('cp');
                $table->foreign('moneda_id')->references('id')->on('tblmonedas')->nullOnDelete();
            });

            $mxnId = DB::table('tblmonedas')->where('codigo', 'MXN')->value('id');
            if ($mxnId) {
                DB::table('tblclientes')->whereNull('moneda_id')->update(['moneda_id' => $mxnId]);
            }
        }

        if (!Schema::hasColumn('tbl_cotizaciones', 'tiempo_entrega')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->string('tiempo_entrega', 255)->nullable()->after('observaciones');
                $table->unsignedBigInteger('moneda_id')->nullable()->after('tiempo_entrega');
                $table->unsignedBigInteger('tipo_flete_id')->nullable()->after('moneda_id');
                $table->decimal('importe_flete', 12, 2)->default(0)->after('tipo_flete_id');

                $table->foreign('moneda_id')->references('id')->on('tblmonedas')->nullOnDelete();
                $table->foreign('tipo_flete_id')->references('id')->on('tbl_tipos_flete')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('tbl_pedidos', 'tiempo_entrega')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->string('tiempo_entrega', 255)->nullable()->after('observaciones');
                $table->unsignedBigInteger('moneda_id')->nullable()->after('tiempo_entrega');
                $table->unsignedBigInteger('tipo_flete_id')->nullable()->after('moneda_id');
                $table->decimal('importe_flete', 12, 2)->default(0)->after('tipo_flete_id');

                $table->foreign('moneda_id')->references('id')->on('tblmonedas')->nullOnDelete();
                $table->foreign('tipo_flete_id')->references('id')->on('tbl_tipos_flete')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_pedidos', 'tiempo_entrega')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->dropForeign(['moneda_id']);
                $table->dropForeign(['tipo_flete_id']);
                $table->dropColumn(['tiempo_entrega', 'moneda_id', 'tipo_flete_id', 'importe_flete']);
            });
        }

        if (Schema::hasColumn('tbl_cotizaciones', 'tiempo_entrega')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->dropForeign(['moneda_id']);
                $table->dropForeign(['tipo_flete_id']);
                $table->dropColumn(['tiempo_entrega', 'moneda_id', 'tipo_flete_id', 'importe_flete']);
            });
        }

        if (Schema::hasColumn('tblclientes', 'moneda_id')) {
            Schema::table('tblclientes', function (Blueprint $table) {
                $table->dropForeign(['moneda_id']);
                $table->dropColumn('moneda_id');
            });
        }

        Schema::dropIfExists('tbl_tipos_flete');
        Schema::dropIfExists('tblmonedas');
    }
};
