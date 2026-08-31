<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class empresaseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tblempresas')->insert([
            'nombre_empresa' => 'KAGDA',
            'rfc'=>'empresa 100% fiscal',
            'direccion_fiscal'=>'blvd independencia #533 colonia centro'],);
        
        DB::table('tblempresas')->insert([
            'nombre_empresa' => 'GURTHE',
            'rfc'=>'empresa sin caracteres',
            'direccion_fiscal'=>'blvd rodriguez triana'],);
        
        DB::table('tblempresas')->insert([
            'nombre_empresa' => 'LEON',
            'rfc'=>'usuario maestro',
            'direccion_fiscal'=>'encargado de programacion' ],);
        
        DB::table('tblempresas')->insert([
            'nombre_empresa' => 'CREDILAGUNA',
            'rfc'=>'empresa con fiscales',
            'direccion_fiscal'=>'blvd san antonio #566 '],);
    }
}
