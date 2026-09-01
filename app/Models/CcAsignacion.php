<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CcAsignacion extends Model
{
    public $table = 'tbl_cc_asignaciones';

    protected $fillable = [
        'ciclo_codigo',
        'empresa',
        'user_id',
        'centro_codigo',
        'centro_nombre',
        'es_principal',
        'parent_id',
        'created_by',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cuentas()
    {
        return $this->hasMany(CcAsignacionCuenta::class, 'asignacion_id');
    }

    public function permisos()
    {
        return $this->hasMany(CcAsignacionPermiso::class, 'asignacion_id');
    }

    public function principal()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function colaboradores()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
