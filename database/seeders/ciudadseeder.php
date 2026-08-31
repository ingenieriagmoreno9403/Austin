<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class ciudadseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tblciudades')->insert([
            'nombre' => 'Torreon ',
            'descripcion'=>'ciudad de torreon ',
            'idestado'=>'1'],);

            DB::table('tblciudades')->insert([
            'nombre' => 'Gomez Palacio ',
            'descripcion'=>'ciudad de gomez palacio ',
            'idestado'=>'2'],);
    }
}
