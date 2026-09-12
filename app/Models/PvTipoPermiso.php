<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvTipoPermiso extends Model
{
    public $table = 'tbl_pv_tipos_permiso';

    protected $fillable = ['clave', 'nombre', 'descripcion', 'orden'];
}
