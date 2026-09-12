<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvAsignacion extends Model
{
    public $table = 'tbl_pv_asignaciones';

    protected $fillable = [
        'ciclo_codigo',
        'empresa',
        'user_id',
        'cliente_codigo',
        'cliente_nombre',
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
        return $this->productos();
    }

    public function productos()
    {
        return $this->hasMany(PvAsignacionProducto::class, 'asignacion_id');
    }

    public function permisos()
    {
        return $this->hasMany(PvAsignacionPermiso::class, 'asignacion_id');
    }

    public function principal()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function colaboradores()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getCentroCodigoAttribute()
    {
        return $this->attributes['cliente_codigo'] ?? null;
    }

    public function setCentroCodigoAttribute($value)
    {
        $this->attributes['cliente_codigo'] = $value;
    }

    public function getCentroNombreAttribute()
    {
        return $this->attributes['cliente_nombre'] ?? null;
    }

    public function setCentroNombreAttribute($value)
    {
        $this->attributes['cliente_nombre'] = $value;
    }
}
