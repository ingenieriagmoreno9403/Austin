<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicParamPlantilla extends Model
{
    protected $table = 'tbl_lic_param_plantilla';

    protected $fillable = [
        'tipo_id',
        'nombre',
        'clave',
        'peso_pct',
        'sentido',
        'tipo_dato',
        'obligatorio',
        'orden',
        'activo',
    ];

    protected $casts = [
        'peso_pct' => 'float',
        'obligatorio' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public const SENTIDOS = [
        'menor_mejor' => 'Menor es mejor',
        'mayor_mejor' => 'Mayor es mejor',
    ];

    public const TIPOS_DATO = [
        'numero' => 'Número',
        'porcentaje' => 'Porcentaje',
        'moneda' => 'Moneda',
        'dias' => 'Días',
    ];

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(LicParamTipo::class, 'tipo_id');
    }
}
