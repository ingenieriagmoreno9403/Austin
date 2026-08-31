<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoFlete extends Model
{
    protected $table = 'tbl_tipos_flete';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'estatus',
    ];
}
