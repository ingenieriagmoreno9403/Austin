<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_rutas_carga')) {
            Schema::create('tbl_rutas_carga', function (Blueprint $table) {
                $table->increments('id');
                $table->string('folio', 40)->unique();
                $table->date('fecha');
                $table->string('estatus', 40)->default('ARMADA');
                $table->string('unidad', 80)->nullable();
                $table->string('chofer_nombre', 120)->nullable();
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('creado_por')->nullable();
                $table->timestamps();

                $table->foreign('creado_por', 'fk_ruta_creado_por')
                    ->references('id')->on('users')->nullOnDelete();
                $table->index('estatus', 'idx_ruta_estatus');
                $table->index('fecha', 'idx_ruta_fecha');
            });
        }

        Schema::table('tbl_cargas', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_cargas', 'ruta_id')) {
                $table->unsignedInteger('ruta_id')->nullable()->after('orden_produccion_id');
            }
            if (!Schema::hasColumn('tbl_cargas', 'orden_entrega')) {
                $table->unsignedSmallInteger('orden_entrega')->nullable()->after('ruta_id');
            }
            if (!Schema::hasColumn('tbl_cargas', 'orden_carga')) {
                $table->unsignedSmallInteger('orden_carga')->nullable()->after('orden_entrega');
            }
            if (!Schema::hasColumn('tbl_cargas', 'distancia_km')) {
                $table->decimal('distancia_km', 10, 2)->nullable()->after('orden_carga');
            }
            if (!Schema::hasColumn('tbl_cargas', 'destino_texto')) {
                $table->string('destino_texto', 255)->nullable()->after('distancia_km');
            }
        });

        // Agregar FK si aún no existe.
        $fkExiste = collect(DB::select('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?', [
            'tbl_cargas', 'fk_carga_ruta', 'FOREIGN KEY',
        ]))->isNotEmpty();

        if (!$fkExiste && Schema::hasColumn('tbl_cargas', 'ruta_id')) {
            Schema::table('tbl_cargas', function (Blueprint $table) {
                $table->foreign('ruta_id', 'fk_carga_ruta')
                    ->references('id')->on('tbl_rutas_carga')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $fkExiste = collect(DB::select('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?', [
            'tbl_cargas', 'fk_carga_ruta', 'FOREIGN KEY',
        ]))->isNotEmpty();

        Schema::table('tbl_cargas', function (Blueprint $table) use ($fkExiste) {
            if ($fkExiste) {
                $table->dropForeign('fk_carga_ruta');
            }
            foreach (['ruta_id', 'orden_entrega', 'orden_carga', 'distancia_km', 'destino_texto'] as $col) {
                if (Schema::hasColumn('tbl_cargas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('tbl_rutas_carga');
    }
};
