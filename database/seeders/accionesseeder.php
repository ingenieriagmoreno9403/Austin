<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class accionesseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('tblacciones')->insert(
            [
                'nombre_accion' => 'ver_empleados ',
                'descripcion'=>'Acceso a ver catalogo de empleados',
            ], 
            [
                'nombre_accion' => 'insertar_empleados',
                'descripcion'=>'Agregar nuevos empleados a la base de datos',
            ], 
            [
                'nombre_accion' => 'editar_empleados',
                'descripcion'=>'Editar campos del catalogo de algun empleado',
            ], 
            [
                'nombre_accion' => 'baja_empleados',
                'descripcion'=>'inactivar empleados por diferentes razones',
            ], 
        );
    }
}
