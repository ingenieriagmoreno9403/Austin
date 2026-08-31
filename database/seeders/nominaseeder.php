<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class nominaseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tblnominas')->insert([
            'idempresa' => '2',
            'idempleado'=>'1',
            'idbancos' => '1',
            'salario_bruto'=>'500',
            'salario_fijo'=>'500',
            'pago_imss' => '200',
            'excedente'=>'300',
            'efectivo' => '0',
            'numero_tarjeta'=>'135689745631555',
            'numero_cuenta' => '1234567891'],);
            

            DB::table('tblnominas')->insert([
            'idempresa' => '1',
            'idempleado'=>'2',
            'idbancos' => '1',
            'salario_bruto'=>'500',
            'salario_neto' => '500',
            'salario_fijo'=>'500',
            'pago_imss' => '200',
            'excedente'=>'0',
            'efectivo' => '0',
            'numero_tarjeta'=>'13568845631555',
            'numero_cuenta' => '123867891' ],);
    }
}
