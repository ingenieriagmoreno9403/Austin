<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class empleadoseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tblempleados')->insert([
            'primer_nombre' => 'Gilberto',
            'segundo_nombre'=>'Alejandro',
            'apellido_paterno' => 'Moreno',
            'apellido_materno'=>'Orona',
            'telefono' => '8712598974',
            'correo'=>'gilberto.moreno.orona@gmail.com',
            'idpuesto' => '1',
            'idsucursal'=>'1',
            'idciudad' => '1',
            'idbanco'=>'1',
            'calle' => 'agustinos',
            'colonia'=>'fracc. misiones miravalle',
            'numero_interior' => '2711',
            'numero_exterior'=>'2711',
            'codigo_postal' => '35050',
            'sexo'=>'m',
            'fecha_nacimiento' => '1994/03/08',
            'foto'=>'gmoreno.jpg',
            'rfc' => 'moog940308is2',
            'nss'=>'1324567998',
            'tipo_sangre' => 'a+',
            'contacto_emergencia'=>'cynthia ibarra gonzalez(esposa) ',
            'telefono_emergencia' => '8713335343'],);   

        DB::table('tblempleados')->insert([
            'primer_nombre' => 'Marisol',
            'segundo_nombre'=>'Alejandra',
            'apellido_paterno' => 'Gonzales',
            'apellido_materno'=>'Urrutia',
            'telefono' => '8712598974',
            'correo'=>'mari.glz.urr@gmail.com',
            'idpuesto' => '2',
            'idsucursal'=>'1',
            'idciudad' => '1',
            'idbanco'=>'1',
            'calle' => 'jacarandas',
            'colonia'=>'miravalle',
            'numero_interior' => '80',
            'numero_exterior'=>'80',
            'codigo_postal' => '3050',
            'sexo'=>'m',
            'fecha_nacimiento' => '2022/04/09',
            'foto'=>'mgarcia.jpg',
            'rfc' => 'moog940308is2',
            'nss'=>'1324567998',
            'tipo_sangre' => 'a+',
            'contacto_emergencia'=>'roberto mtz (papá) ',
            'telefono_emergencia' => '8718735343'],);

            DB::table('tblempleados')->insert([
                'primer_nombre' => 'Fincre',
                'segundo_nombre'=>'Laguna',
                'apellido_paterno' => 'Gonzales',
                'apellido_materno'=>'Urrutia',
                'telefono' => '8712598974',
                'correo'=>'fincre@gmail.com',
                'idpuesto' => '4',
                'idsucursal'=>'1',
                'idciudad' => '1',
                'idbanco'=>'1',
                'calle' => 'jacarandas',
                'colonia'=>'miravalle',
                'numero_interior' => '80',
                'numero_exterior'=>'80',
                'codigo_postal' => '3050',
                'sexo'=>'m',
                'fecha_nacimiento' => '2022/04/09',
                'foto'=>'mgarcia.jpg',
                'rfc' => 'moog940308is2',
                'nss'=>'1324567998',
                'tipo_sangre' => 'a+',
                'contacto_emergencia'=>'roberto mtz (papá) ',
                'telefono_emergencia' => '8718735343'],);
    }
}
