<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CausaFalla extends Model
{
    protected $table = 'tbl_causas_falla';

    protected $fillable = [
        'clave',
        'nombre',
        'estatus',
        'orden',
    ];

    public function scopeActivas($query)
    {
        return $query->where('estatus', 'A')->orderBy('orden')->orderBy('clave');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'causa_falla_id');
    }

    public function getEtiquetaAttribute(): string
    {
        return trim($this->clave . ' — ' . $this->nombre);
    }
}
