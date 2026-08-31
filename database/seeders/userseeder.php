<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class userseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('users')->insert([
            'name' => 'gmoreno',
            'email'=>'gmoreno@gmail.com',
            'password'=>bcrypt('gamo@2022'),
            'idempleado' => '1',
            'estado_user'=>'A', ],);  

        \DB::table('users')->insert([
            'name' => 'mgarcia',
            'email'=>'mgarcia3@gmail.com',
            'password'=>bcrypt('mgarcia@2022'),
            'idempleado' => '2',
            'estado_user'=>'A', ],);  

        \DB::table('users')->insert([
            'name' => 'fincre',
            'email'=>'fincre123@gmail.com',
            'password'=>bcrypt('fincre@2022'),
            'idempleado' => '3',
            'estado_user'=>'A', ],);  
        
        
     
    }
}
