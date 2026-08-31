<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class usuario_pantallaseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tblusuario_pantallas')->insert([
            'idusuario' => '1',
            'idvista'=>'1',
            'iddepartamento' => '2',
            'estado'=>'A' ],);

            DB::table('tblusuario_pantallas')->insert([
                'idusuario' => '1',
                'idvista'=>'2',
                'iddepartamento' => '2',
                'estado'=>'A' ],);

                DB::table('tblusuario_pantallas')->insert([
                    'idusuario' => '2',
                    'idvista'=>'1',
                    'iddepartamento' => '2',
                    'estado'=>'A' ],);
        
                   
    }
}
