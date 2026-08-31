<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspeccionParametro extends Model
{
    protected $table = 'tbl_inspeccion_parametros';

    protected $fillable = [
        'nombre',
        'orden',
        'estatus',
    ];

    public function scopeActivos($query)
    {
        return $query->where('estatus', 'A')->orderBy('orden')->orderBy('id');
    }

    public function getEstatusTextoAttribute(): string
    {
        return $this->estatus === 'A' ? 'Activo' : 'Inactivo';
    }
}
