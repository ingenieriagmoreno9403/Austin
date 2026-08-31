<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoSocio extends Model
{
    protected $table = 'tbltipos_socio';

    protected $fillable = [
        'tipo_socio',
        'descripcion',
        'created_by',
        'updated_by',
    ];
}
