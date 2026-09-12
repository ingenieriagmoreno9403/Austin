<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvUsuarioPermiso extends Model
{
    public $table = 'tbl_pv_usuario_permisos';

    protected $fillable = [
        'ciclo_codigo',
        'user_id',
        'permiso_id',
    ];

    public function tipo()
    {
        return $this->belongsTo(PvTipoPermiso::class, 'permiso_id');
    }
}
