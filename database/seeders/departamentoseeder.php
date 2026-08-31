<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class departamentoseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tbldepartamentos')->insert([
            'nombre' => 'Sistemas',
            'descripcion'=>'Area encargada de sistemas'],);
        
            DB::table('tbldepartamentos')->insert([
            'nombre' => 'Recursos humanos',
            'descripcion'=>'Area encargada del personal' ],);
    }
}
