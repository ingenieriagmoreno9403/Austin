<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MontoSocio extends Model
{
    protected $table = 'tblmontos_socio';

    protected $fillable = [
        'id_tipo_socio',
        'id_grupo',
        'periodicidad',
        'monto',
        'status',
        'created_by',
        'updated_by',
    ];
}
