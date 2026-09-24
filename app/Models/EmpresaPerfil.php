<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaPerfil extends Model
{
    public $table = 'tblempresa_perfiles';

    protected $fillable = [
        'id_empresa',
        'id_perfil',
        'created_by',
    ];
}
