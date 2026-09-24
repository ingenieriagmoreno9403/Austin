<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaVista extends Model
{
    public $table = 'tblempresa_vistas';

    protected $fillable = [
        'id_empresa',
        'id_vista',
        'created_by',
    ];
}
