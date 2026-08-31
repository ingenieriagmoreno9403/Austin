<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class sucursalseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tblsucursales')->insert([
            'nombre' => 'Corporativo Torreon',
            'telefono'=>'8713598974',
            'idciudad' => '1',
            'calle'=>'blvd independencia #556 ',
            'colonia' => 'colonia centro',
            'numero_interior'=>'750',
            'numero_exterior' => '750',
            'codigo_postal'=>'27000', ],); 
        
        
            DB::table('tblsucursales')->insert([
            'nombre' => 'Sucursal Gomez Palacio',
            'telefono'=>'8715598974',
            'idciudad' => '2',
            'calle'=>'blvd independencia #556 ',
            'colonia' => 'colonia centro',
            'numero_interior'=>'50',
            'numero_exterior' => '50',
            'codigo_postal'=>'27100', ],);
    }
}
