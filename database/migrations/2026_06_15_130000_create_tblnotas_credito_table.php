<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tblnotas_credito', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_factura_original');
            $table->string('factura_uuid', 100);
            $table->string('folio_factura', 25)->nullable();
            $table->string('folio_nota', 25)->nullable();
            $table->string('facturama_id', 60)->nullable();
            $table->string('uuid', 100)->nullable();
            $table->string('motivo', 50);
            $table->text('motivo_descripcion');
            $table->string('tipo_relacion', 2)->default('01');
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('iva', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('receptor_rfc', 60)->nullable();
            $table->string('receptor_nombre', 120)->nullable();
            $table->enum('estado', ['Timbrada', 'Error', 'Pendiente'])->default('Pendiente');
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
        Schema::dropIfExists('tblnotas_credito');
    }
};
