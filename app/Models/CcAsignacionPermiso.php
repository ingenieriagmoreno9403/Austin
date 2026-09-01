<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcAsignacionPermiso extends Model
{
    public $table = 'tbl_cc_asignacion_permisos';

    protected $fillable = ['asignacion_id', 'permiso_id'];

    public function tipo()
    {
        return $this->belongsTo(CcTipoPermiso::class, 'permiso_id');
    }
}
