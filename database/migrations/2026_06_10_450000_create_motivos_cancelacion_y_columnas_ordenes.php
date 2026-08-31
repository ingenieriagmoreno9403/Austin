<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbl_motivos_cancelacion_produccion')) {
            Schema::create('tbl_motivos_cancelacion_produccion', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 40)->unique();
                $table->string('nombre', 150);
                $table->string('categoria', 40);
                $table->boolean('requiere_nota')->default(false);
                $table->boolean('activo')->default(true);
                $table->unsignedInteger('orden')->default(0);
                $table->timestamps();
            });
        }

        $now = now();
        $motivos = [
            // Comercial / pedido
            ['CLIENTE_CANCELO', 'Cliente canceló el pedido', 'COMERCIAL', false, 10],
            ['CAMBIO_CANTIDAD', 'Cambio de cantidad solicitada por el cliente', 'COMERCIAL', false, 20],
            ['CAMBIO_ESPECIFICACION', 'Cambio de especificación (Ø / RD / color)', 'COMERCIAL', false, 30],
            ['PEDIDO_DUPLICADO', 'Pedido u orden duplicada', 'COMERCIAL', false, 40],
            // Planeación
            ['REPROGRAMACION', 'Reprogramación a otra máquina o turno', 'PLANEACION', false, 50],
            ['PRIORIDAD', 'Reasignación por pedido prioritario / urgente', 'PLANEACION', false, 60],
            ['ERROR_CAPTURA_OP', 'Error de captura en la orden de producción', 'PLANEACION', true, 70],
            // Materiales
            ['FALTA_MP', 'Falta de materia prima sin fecha de surtido', 'MATERIALES', false, 80],
            ['MP_NO_CONFORME', 'Materia prima no conforme (calidad de resina)', 'MATERIALES', true, 90],
            // Técnica / calidad
            ['FALLA_MAQUINA', 'Falla de máquina / mantenimiento correctivo', 'TECNICA', true, 100],
            ['FALLA_HERRAMENTAL', 'Falla de herramental (dado / calibrador)', 'TECNICA', true, 110],
            ['FUERA_ESPEC', 'Producto fuera de especificación irrecuperable (merma total)', 'CALIDAD', true, 120],
            // Otro
            ['OTRO', 'Otro motivo (especificar)', 'OTRO', true, 999],
        ];

        foreach ($motivos as [$codigo, $nombre, $categoria, $requiereNota, $orden]) {
            $existe = DB::table('tbl_motivos_cancelacion_produccion')->where('codigo', $codigo)->exists();
            if (!$existe) {
                DB::table('tbl_motivos_cancelacion_produccion')->insert([
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'categoria' => $categoria,
                    'requiere_nota' => $requiereNota,
                    'activo' => true,
                    'orden' => $orden,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::table('tbl_ordenes', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_ordenes', 'motivo_cancelacion_id')) {
                $table->unsignedBigInteger('motivo_cancelacion_id')->nullable()->after('estatus');
            }
            if (!Schema::hasColumn('tbl_ordenes', 'motivo_cancelacion_nota')) {
                $table->string('motivo_cancelacion_nota', 500)->nullable()->after('motivo_cancelacion_id');
            }
            if (!Schema::hasColumn('tbl_ordenes', 'cancelada_por')) {
                $table->unsignedBigInteger('cancelada_por')->nullable()->after('motivo_cancelacion_nota');
            }
            if (!Schema::hasColumn('tbl_ordenes', 'cancelada_at')) {
                $table->timestamp('cancelada_at')->nullable()->after('cancelada_por');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_ordenes', function (Blueprint $table) {
            foreach (['motivo_cancelacion_id', 'motivo_cancelacion_nota', 'cancelada_por', 'cancelada_at'] as $col) {
                if (Schema::hasColumn('tbl_ordenes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('tbl_motivos_cancelacion_produccion');
    }
};
