<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_camiones')) {
            Schema::create('tbl_camiones', function (Blueprint $table) {
                $table->increments('id');
                $table->string('placas', 40)->unique();
                $table->string('nombre', 120)->nullable();
                $table->string('tipo', 60)->nullable(); // Torton, Rabón, Trailer, etc.
                $table->decimal('capacidad_ton', 10, 2)->nullable();
                $table->boolean('activo')->default(true);
                $table->string('observaciones', 255)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tbl_choferes')) {
            Schema::create('tbl_choferes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 120);
                $table->enum('tipo', ['INTERNO', 'EXTERNO'])->default('INTERNO');
                $table->string('telefono', 40)->nullable();
                $table->string('licencia', 60)->nullable();
                $table->string('empresa_externa', 120)->nullable();
                $table->unsignedInteger('empleado_id')->nullable();
                $table->boolean('activo')->default(true);
                $table->string('observaciones', 255)->nullable();
                $table->timestamps();

                $table->index(['tipo', 'activo'], 'idx_chofer_tipo_activo');
            });
        }

        if (Schema::hasTable('tbl_rutas_carga')) {
            Schema::table('tbl_rutas_carga', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_rutas_carga', 'camion_id')) {
                    $table->unsignedInteger('camion_id')->nullable()->after('unidad');
                }
                if (!Schema::hasColumn('tbl_rutas_carga', 'chofer_id')) {
                    $table->unsignedInteger('chofer_id')->nullable()->after('chofer_nombre');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_rutas_carga')) {
            Schema::table('tbl_rutas_carga', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_rutas_carga', 'camion_id')) {
                    $table->dropColumn('camion_id');
                }
                if (Schema::hasColumn('tbl_rutas_carga', 'chofer_id')) {
                    $table->dropColumn('chofer_id');
                }
            });
        }

        Schema::dropIfExists('tbl_choferes');
        Schema::dropIfExists('tbl_camiones');
    }
};
