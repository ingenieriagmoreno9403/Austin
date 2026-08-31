<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblfacturasXmlTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tblfacturas_xml', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('licitacion_id')->nullable()->comment('ID de la licitación asociada');
            $table->unsignedBigInteger('proveedor_id')->nullable()->comment('ID del proveedor');
            $table->string('uuid', 36)->nullable()->comment('UUID de la factura (TimbreFiscalDigital)');
            $table->string('rfc_emisor', 20)->nullable()->comment('RFC del emisor de la factura');
            $table->string('nombre_emisor', 150)->nullable()->comment('Nombre del emisor de la factura');
            $table->string('rfc_receptor', 20)->nullable()->comment('RFC del receptor de la factura');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('Subtotal de la factura');
            $table->decimal('total', 12, 2)->default(0)->comment('Total de la factura');
            $table->string('nombre_archivo', 255)->nullable()->comment('Nombre del archivo XML almacenado');
            $table->string('ruta_archivo', 255)->nullable()->comment('Ruta del archivo XML en el sistema de archivos');
            $table->enum('estado', ['Pendiente', 'Procesada', 'Rechazada'])->default('Pendiente')->comment('Estado de la factura');
            $table->text('observaciones')->nullable()->comment('Observaciones o comentarios sobre la factura');
            $table->timestamps();
            
            // Índices
            $table->index('uuid');
            $table->index('rfc_emisor');
            $table->index(['licitacion_id', 'proveedor_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tblfacturas_xml');
    }
}
