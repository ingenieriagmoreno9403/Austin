<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblcomplementos_pago', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_factura_original');
            $table->string('factura_uuid', 100);
            $table->string('folio_factura', 25)->nullable();
            $table->string('folio_complemento', 25)->nullable();
            $table->string('facturama_id', 60)->nullable();
            $table->string('uuid', 100)->nullable();
            $table->dateTime('fecha_pago');
            $table->string('forma_pago', 5);
            $table->unsignedInteger('numero_parcialidad')->default(1);
            $table->decimal('saldo_anterior', 14, 2)->default(0);
            $table->decimal('monto_pagado', 14, 2)->default(0);
            $table->decimal('saldo_insoluto', 14, 2)->default(0);
            $table->string('receptor_rfc', 60)->nullable();
            $table->string('receptor_nombre', 120)->nullable();
            $table->enum('estado', ['Timbrado', 'Error', 'Pendiente'])->default('Pendiente');
            $table->text('respuesta_api')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();

            $table->index('id_factura_original');
            $table->index('factura_uuid');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tblcomplementos_pago');
    }
};
