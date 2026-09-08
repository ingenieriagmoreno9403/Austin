<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcCapturaCentro extends Model
{
    public $table = 'tbl_cc_captura_centros';

    protected $fillable = [
        'ciclo_codigo',
        'empresa',
        'centro_codigo',
        'estado',
        'updated_by',
    ];
}
