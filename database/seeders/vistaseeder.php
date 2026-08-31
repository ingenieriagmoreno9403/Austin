<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class vistaseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tblvistas')->insert([
            'nombre' => 'Index',
            'descripcion'=>'Vista de catalogo de empleados',
            'estado' => '1',
            'iddepartamento'=>'2',],); 
        

        DB::table('tblvistas')->insert([
            'nombre' => 'create',
            'descripcion'=>'Vista de alta de empleados',
            'estado' => '1',
            'iddepartamento'=>'2',],);
    }
}
