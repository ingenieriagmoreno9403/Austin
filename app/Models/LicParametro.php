<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicParametro extends Model
{
    protected $table = 'tbl_lic_parametros';

    protected $fillable = [
        'licitacion_id',
        'plantilla_id',
        'nombre',
        'peso_pct',
        'sentido',
        'tipo_dato',
        'obligatorio',
        'orden',
    ];

    protected $casts = [
        'peso_pct' => 'float',
        'obligatorio' => 'boolean',
        'orden' => 'integer',
    ];

    public function licitacion(): BelongsTo
    {
        return $this->belongsTo(Licitacion::class, 'licitacion_id');
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(LicParamPlantilla::class, 'plantilla_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(LicParamScore::class, 'parametro_id');
    }
}
