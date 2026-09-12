<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcUsuarioPermiso extends Model
{
    public $table = 'tbl_cc_usuario_permisos';

    protected $fillable = [
        'ciclo_codigo',
        'user_id',
        'permiso_id',
    ];

    public function tipo()
    {
        return $this->belongsTo(CcTipoPermiso::class, 'permiso_id');
    }
}
