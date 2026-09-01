<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcTipoPermiso extends Model
{
    public $table = 'tbl_cc_tipos_permiso';

    protected $fillable = ['clave', 'nombre', 'descripcion', 'orden'];
}
