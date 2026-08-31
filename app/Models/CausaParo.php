<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CausaParo extends Model
{
    protected $table = 'tbl_causas_paro';

    protected $fillable = [
        'clave',
        'nombre',
        'tipo',
        'estatus',
        'orden',
    ];

    public function scopeActivas($query)
    {
        return $query->where('estatus', 'A')->orderBy('orden')->orderBy('clave');
    }

    public function paros(): HasMany
    {
        return $this->hasMany(ParoProduccion::class, 'causa_paro_id');
    }

    public function getEtiquetaAttribute(): string
    {
        return trim($this->clave . ' — ' . $this->nombre);
    }

    public function getTipoTextoAttribute(): string
    {
        return $this->tipo === 'PLANEADO' ? 'Planeado' : 'No planeado';
    }
}
