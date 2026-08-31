<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class ProveedorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Verificar si la tabla existe
        if (!Schema::hasTable('tblprovedores')) {
            // Si no existe, creamos la tabla
            Schema::create('tblprovedores', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('direccion')->nullable();
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->string('rfc')->nullable();
                $table->string('estado')->default('activo');
                $table->timestamps();
            });
        }

        // Proveedores para prueba
        $proveedores = [
            ['nombre' => 'Aceros y Materiales S.A.', 'direccion' => 'Av. Industrial 123', 'telefono' => '555-1234', 'email' => 'contacto@aceros.com', 'rfc' => 'ACMA123456AB'],
            ['nombre' => 'Ferretería Industrial', 'direccion' => 'Calle Comercial 456', 'telefono' => '555-5678', 'email' => 'ventas@ferreteria.com', 'rfc' => 'FIND789012BC'],
            ['nombre' => 'Minera del Norte', 'direccion' => 'Carretera Norte Km 10', 'telefono' => '555-9012', 'email' => 'info@mineranorte.com', 'rfc' => 'MINO345678CD'],
            ['nombre' => 'Suministros Mineros', 'direccion' => 'Blvd. Minero 789', 'telefono' => '555-3456', 'email' => 'contacto@suministros.com', 'rfc' => 'SUMI901234DE'],
            ['nombre' => 'Equipos Pesados S.A.', 'direccion' => 'Av. Maquinaria 234', 'telefono' => '555-7890', 'email' => 'ventas@equipospesados.com', 'rfc' => 'EQUI567890EF'],
        ];

        foreach ($proveedores as $proveedor) {
            DB::table('tblprovedores')->updateOrInsert(
                ['nombre' => $proveedor['nombre']],
                [
                    'direccion' => $proveedor['direccion'],
                    'telefono' => $proveedor['telefono'],
                    'email' => $proveedor['email'],
                    'rfc' => $proveedor['rfc'],
                    'estado' => 'activo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
} 