<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoSocio extends Model
{
    protected $table = 'grupos_socios';

    protected $fillable = [
        'tipo_socio',
        'descripcion',
        'created_by',
        'updated_by',
    ];
}
