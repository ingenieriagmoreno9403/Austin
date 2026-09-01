<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcAsignacionCuenta extends Model
{
    public $table = 'tbl_cc_asignacion_cuentas';

    protected $fillable = ['asignacion_id', 'cuenta_codigo', 'cuenta_nombre', 'agrupacion'];
}
