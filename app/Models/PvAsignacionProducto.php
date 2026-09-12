<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PvAsignacionProducto extends Model
{
    public $table = 'tbl_pv_asignacion_productos';

    protected $fillable = [
        'asignacion_id',
        'producto_codigo',
        'producto_nombre',
        'linea',
    ];

    public function getCuentaCodigoAttribute()
    {
        return $this->attributes['producto_codigo'] ?? null;
    }

    public function setCuentaCodigoAttribute($value)
    {
        $this->attributes['producto_codigo'] = $value;
    }

    public function getCuentaNombreAttribute()
    {
        return $this->attributes['producto_nombre'] ?? null;
    }

    public function setCuentaNombreAttribute($value)
    {
        $this->attributes['producto_nombre'] = $value;
    }

    public function getAgrupacionAttribute()
    {
        return $this->attributes['linea'] ?? null;
    }

    public function setAgrupacionAttribute($value)
    {
        $this->attributes['linea'] = $value;
    }
}
