<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicParamTipo extends Model
{
    protected $table = 'tbl_lic_param_tipos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function plantilla(): HasMany
    {
        return $this->hasMany(LicParamPlantilla::class, 'tipo_id')->orderBy('orden');
    }

    public function plantillaActiva(): HasMany
    {
        return $this->plantilla()->where('activo', true);
    }

    public function sumaPesos(): float
    {
        return (float) $this->plantillaActiva()->sum('peso_pct');
    }
}
