<?php

namespace App\Imports;

use App\Models\Nominas_pagosdet;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Http\Request;
use App\Http\Controllers\NominasController;
use Illuminate\Http\RedirectResponse;
use  Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use DB;

class BoTranImport implements ToModel,WithHeadingRow
{

   
    protected $id;
    function __construct($id) {
            $this->id = $id;
    }
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function model(array $row)
    {

        $update =  DB::select('update tblnominas_pagodet set 
        bono = ?,
        viaticos = ?,
        horas_extras = ?,
        dias_descanso = ?,
        dias_prima_dominical = ?, 
        percepcion_extraordinaria = ?,
        deudores_fiscal = ?, 
        fonacot = ?,
        dias_prima_vacacional = ?
        where idempleado = ? and 
        idpagonomina = ?;', 

        [$row['bono'] ?? 0,
        $row['viaticos'] ?? 0,
        $row['horas_extras'] ?? 0,
        $row['dias_descanso'] ?? 0, 
        $row['dias_prima_dominical'] ?? 0, 
        $row['percepcion_exenta'] ?? 0,
        $row['prestamo_empresarial'] ?? 0, 
        $row['fonacot'] ?? 0,
        $row['dias_prima_vacacional'] ?? 0,
        $row['id'],
        $this->id]);
    }
}
