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

        // 1) Asegurar los puestos base de producción en el catálogo de RH (tblpuestos).
        $puestosBase = [
            'OPERADOR DE PRODUCCION' => 'Operación de línea de extrusión y empaque',
            'AUDITOR DE CALIDAD' => 'Inspección y liberación de calidad',
        ];

        foreach ($puestosBase as $nombre => $descripcion) {
            $existe = DB::table('tblpuestos')->whereRaw('UPPER(nombre) = ?', [$nombre])->exists();
            if (!$existe) {
                DB::table('tblpuestos')->insert([
                    'nombre' => $nombre,
                    'estado' => 'A',
                    'descripcion' => $descripcion,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 2) Tabla de enlace proceso (estatus) → puesto responsable.
        if (!Schema::hasTable('tbl_procesos_produccion_responsables')) {
            Schema::create('tbl_procesos_produccion_responsables', function (Blueprint $table) {
                $table->id();
                $table->string('estatus', 40)->unique();
                $table->unsignedBigInteger('puesto_id')->nullable();
                $table->timestamps();

                $table->foreign('puesto_id', 'fk_ppr_puesto')
                    ->references('id')->on('tblpuestos')
                    ->nullOnDelete();
            });
        }

        // 3) Sembrar el mapeo por defecto proceso → puesto.
        $idOperador = DB::table('tblpuestos')->whereRaw('UPPER(nombre) = ?', ['OPERADOR DE PRODUCCION'])->value('id');
        $idAuditor = DB::table('tblpuestos')->whereRaw('UPPER(nombre) = ?', ['AUDITOR DE CALIDAD'])->value('id');
        $idAlmacen = DB::table('tblpuestos')->whereRaw('UPPER(nombre) = ?', ['AUXILIAR DE ALMACEN'])->value('id');

        $mapa = [
            'EN_ESPERA_MATERIALES' => $idAlmacen ?: $idOperador,
            'PREPARANDO_MAQUINAS' => $idOperador,
            'CALENTANDO_MAQUINA' => $idOperador,
            'EN_PRODUCCION' => $idOperador,
            'REVISION_CALIDAD' => $idAuditor,
            'PREPARAR_EMPAQUE' => $idOperador,
            'ENROLLANDO' => $idOperador,
            'A_LONGITUD' => $idOperador,
            'CORTAR_FLEJAR' => $idOperador,
            'TERMINADA' => $idOperador,
        ];

        foreach ($mapa as $estatus => $puestoId) {
            $existe = DB::table('tbl_procesos_produccion_responsables')->where('estatus', $estatus)->exists();
            if (!$existe) {
                DB::table('tbl_procesos_produccion_responsables')->insert([
                    'estatus' => $estatus,
                    'puesto_id' => $puestoId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_procesos_produccion_responsables');
        // Los puestos creados en RH se conservan.
    }
};
