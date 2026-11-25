<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_req_rutas')) {
            Schema::create('tbl_req_rutas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 120);
                $table->string('departamento', 120)->nullable()->index();
                $table->string('tipo_requisicion', 80)->nullable();
                $table->boolean('activo')->default(true)->index();
                $table->boolean('es_default')->default(false)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tbl_req_niveles')) {
            Schema::create('tbl_req_niveles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ruta_id')->index();
                $table->unsignedSmallInteger('orden')->default(1);
                $table->string('nombre_nivel', 120);
                $table->unsignedBigInteger('responsable_user_id')->nullable()->index();
                $table->boolean('obligatorio')->default(true);
                $table->timestamps();
                $table->unique(['ruta_id', 'orden'], 'req_nivel_ruta_orden_uq');
            });
        }

        if (! Schema::hasTable('tbl_req_suplentes')) {
            Schema::create('tbl_req_suplentes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('nivel_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->date('vigencia_desde')->nullable();
                $table->date('vigencia_hasta')->nullable();
                $table->boolean('activo')->default(true)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tbl_requisiciones')) {
            Schema::create('tbl_requisiciones', function (Blueprint $table) {
                $table->id();
                $table->string('folio', 40)->unique();
                $table->unsignedBigInteger('solicitante_id')->index();
                $table->string('departamento', 120)->nullable()->index();
                $table->string('centro_costo', 120)->nullable();
                $table->string('almacen_destino', 120)->nullable();
                $table->string('prioridad', 20)->default('normal')->index();
                $table->date('fecha_solicitud')->nullable();
                $table->date('fecha_requerida')->nullable()->index();
                $table->text('justificacion')->nullable();
                $table->string('estado', 40)->default('borrador')->index();
                $table->unsignedBigInteger('ruta_id')->nullable()->index();
                $table->unsignedSmallInteger('nivel_actual_orden')->nullable()->index();
                $table->unsignedBigInteger('responsable_actual_id')->nullable()->index();
                $table->timestamp('enviado_at')->nullable();
                $table->timestamp('autorizado_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tbl_requisicion_detalles')) {
            Schema::create('tbl_requisicion_detalles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('requisicion_id')->index();
                $table->unsignedBigInteger('producto_id')->nullable()->index();
                $table->string('codigo', 80)->nullable();
                $table->string('descripcion', 255);
                $table->string('unidad_medida', 40)->nullable();
                $table->decimal('cantidad', 18, 4)->default(0);
                $table->decimal('cantidad_comprada', 18, 4)->default(0);
                $table->text('especificaciones')->nullable();
                $table->string('motivo', 255)->nullable();
                $table->date('fecha_requerida')->nullable();
                $table->text('observaciones')->nullable();
                $table->boolean('es_no_catalogado')->default(false);
                $table->unsignedSmallInteger('orden')->default(1);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tbl_requisicion_aprobaciones')) {
            Schema::create('tbl_requisicion_aprobaciones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('requisicion_id')->index();
                $table->unsignedBigInteger('nivel_id')->nullable()->index();
                $table->unsignedSmallInteger('orden')->default(1);
                $table->string('nombre_nivel', 120)->nullable();
                $table->unsignedBigInteger('responsable_id')->nullable()->index();
                $table->string('estado', 30)->default('pendiente')->index();
                $table->string('decision', 30)->nullable();
                $table->text('comentario')->nullable();
                $table->timestamp('fecha_decision')->nullable();
                $table->boolean('obligatorio')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tbl_requisicion_historial')) {
            Schema::create('tbl_requisicion_historial', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('requisicion_id')->index();
                $table->unsignedBigInteger('usuario_id')->nullable()->index();
                $table->string('accion', 60);
                $table->string('estado_anterior', 40)->nullable();
                $table->string('estado_nuevo', 40)->nullable();
                $table->text('comentario')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        $this->seedRutaDefault();
    }

    protected function seedRutaDefault(): void
    {
        if (DB::table('tbl_req_rutas')->where('es_default', 1)->exists()) {
            return;
        }

        $now = now();
        $rutaId = DB::table('tbl_req_rutas')->insertGetId([
            'nombre' => 'Ruta general',
            'departamento' => null,
            'tipo_requisicion' => null,
            'activo' => 1,
            'es_default' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $niveles = [
            [1, 'Jefe de departamento', true],
            [2, 'Gerencia', true],
            [3, 'Dirección', false],
        ];
        foreach ($niveles as [$orden, $nombre, $obligatorio]) {
            DB::table('tbl_req_niveles')->insert([
                'ruta_id' => $rutaId,
                'orden' => $orden,
                'nombre_nivel' => $nombre,
                'responsable_user_id' => null,
                'obligatorio' => $obligatorio ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_requisicion_historial');
        Schema::dropIfExists('tbl_requisicion_aprobaciones');
        Schema::dropIfExists('tbl_requisicion_detalles');
        Schema::dropIfExists('tbl_requisiciones');
        Schema::dropIfExists('tbl_req_suplentes');
        Schema::dropIfExists('tbl_req_niveles');
        Schema::dropIfExists('tbl_req_rutas');
    }
};
