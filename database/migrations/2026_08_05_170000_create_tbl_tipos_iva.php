<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_tipos_iva')) {
            Schema::create('tbl_tipos_iva', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 30)->unique();
                $table->string('nombre', 120);
                $table->decimal('porcentaje', 5, 2)->default(16);
                $table->string('descripcion', 255)->nullable();
                $table->string('estatus', 10)->default('A');
                $table->timestamps();
            });

            DB::table('tbl_tipos_iva')->insert([
                [
                    'codigo' => 'GENERAL',
                    'nombre' => 'IVA general 16%',
                    'porcentaje' => 16,
                    'descripcion' => 'Tasa general',
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'FRONTERA',
                    'nombre' => 'IVA frontera 8%',
                    'porcentaje' => 8,
                    'descripcion' => 'Tasa fronteriza',
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'EXENTO',
                    'nombre' => 'Exento 0%',
                    'porcentaje' => 0,
                    'descripcion' => 'Sin IVA',
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        $generalId = DB::table('tbl_tipos_iva')->where('codigo', 'GENERAL')->value('id');

        if (Schema::hasTable('tbl_cotizaciones') && !Schema::hasColumn('tbl_cotizaciones', 'tipo_iva_id')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) use ($generalId) {
                $table->unsignedBigInteger('tipo_iva_id')->nullable()->after('iva_en_precios');
                $table->decimal('porcentaje_iva', 5, 2)->default(16)->after('tipo_iva_id');
            });

            if ($generalId) {
                DB::table('tbl_cotizaciones')->whereNull('tipo_iva_id')->update([
                    'tipo_iva_id' => $generalId,
                    'porcentaje_iva' => 16,
                ]);
            }

            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->foreign('tipo_iva_id')->references('id')->on('tbl_tipos_iva')->nullOnDelete();
            });
        }

        if (Schema::hasTable('tbl_pedidos') && !Schema::hasColumn('tbl_pedidos', 'tipo_iva_id')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->unsignedBigInteger('tipo_iva_id')->nullable()->after('iva_en_precios');
                $table->decimal('porcentaje_iva', 5, 2)->default(16)->after('tipo_iva_id');
            });

            if ($generalId) {
                DB::table('tbl_pedidos')->whereNull('tipo_iva_id')->update([
                    'tipo_iva_id' => $generalId,
                    'porcentaje_iva' => 16,
                ]);
            }

            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->foreign('tipo_iva_id')->references('id')->on('tbl_tipos_iva')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_cotizaciones') && Schema::hasColumn('tbl_cotizaciones', 'tipo_iva_id')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->dropForeign(['tipo_iva_id']);
                $table->dropColumn(['tipo_iva_id', 'porcentaje_iva']);
            });
        }

        if (Schema::hasTable('tbl_pedidos') && Schema::hasColumn('tbl_pedidos', 'tipo_iva_id')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->dropForeign(['tipo_iva_id']);
                $table->dropColumn(['tipo_iva_id', 'porcentaje_iva']);
            });
        }

        Schema::dropIfExists('tbl_tipos_iva');
    }
};
