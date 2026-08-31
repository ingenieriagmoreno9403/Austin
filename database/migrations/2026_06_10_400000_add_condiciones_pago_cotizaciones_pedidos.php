<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_condiciones_pago')) {
            Schema::create('tbl_condiciones_pago', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 30)->unique();
                $table->string('nombre', 120);
                $table->string('descripcion', 255)->nullable();
                $table->unsignedSmallInteger('dias_credito')->default(0);
                $table->string('estatus', 10)->default('A');
                $table->timestamps();
            });

            DB::table('tbl_condiciones_pago')->insert([
                [
                    'codigo' => 'CONTADO',
                    'nombre' => 'Contado',
                    'descripcion' => 'Pago de contado',
                    'dias_credito' => 0,
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'CREDITO_15',
                    'nombre' => 'Crédito 15 días',
                    'descripcion' => 'Crédito a 15 días',
                    'dias_credito' => 15,
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'CREDITO_30',
                    'nombre' => 'Crédito 30 días',
                    'descripcion' => 'Crédito a 30 días',
                    'dias_credito' => 30,
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'CREDITO_45',
                    'nombre' => 'Crédito 45 días',
                    'descripcion' => 'Crédito a 45 días',
                    'dias_credito' => 45,
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'CREDITO_60',
                    'nombre' => 'Crédito 60 días',
                    'descripcion' => 'Crédito a 60 días',
                    'dias_credito' => 60,
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'codigo' => 'ANTICIPO_50',
                    'nombre' => 'Anticipo 50%',
                    'descripcion' => '50% anticipo y 50% contra entrega',
                    'dias_credito' => 0,
                    'estatus' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (!Schema::hasColumn('tbl_cotizaciones', 'condicion_pago_id')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->unsignedBigInteger('condicion_pago_id')->nullable()->after('tipo_flete_id');
                $table->foreign('condicion_pago_id')->references('id')->on('tbl_condiciones_pago')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('tbl_pedidos', 'condicion_pago_id')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->unsignedBigInteger('condicion_pago_id')->nullable()->after('tipo_flete_id');
                $table->foreign('condicion_pago_id')->references('id')->on('tbl_condiciones_pago')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_pedidos', 'condicion_pago_id')) {
            Schema::table('tbl_pedidos', function (Blueprint $table) {
                $table->dropForeign(['condicion_pago_id']);
                $table->dropColumn('condicion_pago_id');
            });
        }

        if (Schema::hasColumn('tbl_cotizaciones', 'condicion_pago_id')) {
            Schema::table('tbl_cotizaciones', function (Blueprint $table) {
                $table->dropForeign(['condicion_pago_id']);
                $table->dropColumn('condicion_pago_id');
            });
        }

        Schema::dropIfExists('tbl_condiciones_pago');
    }
};
