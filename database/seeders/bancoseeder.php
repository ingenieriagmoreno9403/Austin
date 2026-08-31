<?php




namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class bancoseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tblbancos')->insert([
                'nombre' => 'bvva bancomer',
                'descripcion'=>'banco mercantil bancomer'
            ],);
            \DB::table('tblbancos')->insert([
                'nombre' => 'banorte',
                'descripcion'=>'Banco del norte de mexico'
            ],);
            \DB::table('tblbancos')->insert([
                'nombre' => 'banco azteca',
                'descripcion'=>'banco azteca de mexico'
            ],);
    }
}
