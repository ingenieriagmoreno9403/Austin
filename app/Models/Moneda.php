<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Moneda extends Model
{
    protected $table = 'tblmonedas';

    protected $fillable = [
        'codigo',
        'nombre',
        'abreviacion',
        'estatus',
    ];
}
