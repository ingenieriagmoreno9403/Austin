<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocioCheckoutDet extends Model
{
    protected $table = 'tblsocios_checkout_det';

    protected $fillable = [
        'id_checkout_enc',
        'tipo_evento',
        'fecha_hora_evento',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    public function encabezado(): BelongsTo
    {
        return $this->belongsTo(SocioCheckoutEnc::class, 'id_checkout_enc');
    }
}
