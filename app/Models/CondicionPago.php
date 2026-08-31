<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CondicionPago extends Model
{
    protected $table = 'tbl_condiciones_pago';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'dias_credito',
        'estatus',
    ];

    protected $casts = [
        'dias_credito' => 'integer',
    ];
}
