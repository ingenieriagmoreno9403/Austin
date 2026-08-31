<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descuento extends Model
{
    protected $table = 'tbldescuentos';

    protected $fillable = [
        'tipo_socio',
        'descripcion',
        'porcentaje_des',
        'created_by',
        'updated_by',
    ];
}
