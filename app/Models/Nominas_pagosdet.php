<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nominas_pagosdet extends Model
{
    use HasFactory;
    public $table='tblnominas_pagodet';

    protected $fillable = [
        'idempleado',
        'bono',
        'viaticos',
        'transporte',
        'entrega',
    ];
}
