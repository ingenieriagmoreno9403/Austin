<?php



namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class puestoseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
            DB::table('tblpuestos')->insert([
            'nombre' => 'Ingeniero en programacion',
            'descripcion'=>'encargado del area de programacion' ],);

            DB::table('tblpuestos')->insert([
            'nombre' => 'Auxliar de sistemas',
            'descripcion'=>'Ayudante de sistemas']);

            DB::table('tblpuestos')->insert([
            'nombre' => 'cajero',
            'descripcion'=>'cajero de sucursal'],);

            DB::table('tblpuestos')->insert([
            'nombre' => 'Master',
            'descripcion'=>'usuario maestro'],);
    }
}
