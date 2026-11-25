<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicParamScore extends Model
{
    protected $table = 'tbl_lic_param_scores';

    protected $fillable = [
        'licitacion_id',
        'parametro_id',
        'proveedor_id',
        'valor',
        'score_normalizado',
        'comentario',
    ];

    protected $casts = [
        'valor' => 'float',
        'score_normalizado' => 'float',
    ];

    public function parametro(): BelongsTo
    {
        return $this->belongsTo(LicParametro::class, 'parametro_id');
    }

    public function licitacion(): BelongsTo
    {
        return $this->belongsTo(Licitacion::class, 'licitacion_id');
    }
}
