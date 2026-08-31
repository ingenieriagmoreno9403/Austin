<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class estadoseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('tblestados')->insert([
            'nombre'=>'coahuila de zaragoza',
            'descripcion'=>'estado coahuila'],);
        
        
        DB::table('tblestados')->insert([
            'nombre'=>'durango dgo',
            'descripcion'=>'estado durango '],);
    }
}
