<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvAsignacionPermiso extends Model
{
    public $table = 'tbl_pv_asignacion_permisos';

    protected $fillable = ['asignacion_id', 'permiso_id'];

    public function tipo()
    {
        return $this->belongsTo(PvTipoPermiso::class, 'permiso_id');
    }
}
